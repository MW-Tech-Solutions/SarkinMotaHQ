<?php
/**
 * Corporate Departments Management
 * Sarkin Mota HQ — Enterprise HR Module
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_helper.php';
require_once __DIR__ . '/../includes/rbac_helper.php';
require_once __DIR__ . '/../includes/settings_helper.php';

require_login();
$user = get_logged_in_user();
$user_id = $user['id'];
$user_role = $user['role'];

// Permission Guard: Super Admin, Admin, HR Manager, or departments.manage permission
$can_manage_depts = is_super_admin() || is_admin() || $user_role === 'hr_manager' || has_permission('departments.manage') || has_permission('hr.departments.manage');

if (!$can_manage_depts) {
    set_flash_message('danger', 'Access Denied: You do not have permission to manage corporate departments.');
    redirect('auth/dashboard.php');
}

// Helper for HR Audit Logging
if (!function_exists('logHRAudit')) {
    function logHRAudit($pdo, $userId, $action, $targetType = null, $targetId = null, $details = null) {
        try {
            $stmt = $pdo->prepare("INSERT INTO hr_audit_logs (user_id, action, entity_type, entity_id, details, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $action, $targetType, $targetId, $details, $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1']);
        } catch (Exception $e) {
            error_log("HR Audit Log Error: " . $e->getMessage());
        }
    }
}

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash_message('danger', 'CSRF validation failed. Please try again.');
        redirect('hr/departments.php');
    }

    $action = $_POST['action'] ?? '';

    // CREATE DEPARTMENT
    if ($action === 'create_department') {
        $name = trim(sanitize_input($_POST['name'] ?? ''));
        $code = strtoupper(trim(sanitize_input($_POST['code'] ?? '')));
        $description = trim(sanitize_input($_POST['description'] ?? ''));
        $manager_id = !empty($_POST['manager_id']) ? (int)$_POST['manager_id'] : null;

        if (empty($name)) {
            set_flash_message('danger', 'Department name is required.');
            redirect('hr/departments.php');
        }

        if (empty($code)) {
            // Auto generate department code
            $code = 'DEPT-' . strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $name), 0, 4));
        }

        // Check uniqueness
        $stmt_check = $pdo->prepare("SELECT id FROM departments WHERE name = ? OR code = ?");
        $stmt_check->execute([$name, $code]);
        if ($stmt_check->fetch()) {
            set_flash_message('danger', "A department with the name '$name' or code '$code' already exists.");
            redirect('hr/departments.php');
        }

        try {
            $stmt_ins = $pdo->prepare("INSERT INTO departments (name, code, description, manager_id, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt_ins->execute([$name, $code, $description, $manager_id]);
            $new_dept_id = $pdo->lastInsertId();

            logHRAudit($pdo, $user_id, 'create_department', 'departments', $new_dept_id, "Created department '$name' ($code)");
            set_flash_message('success', "Department '$name' ($code) has been successfully created.");
        } catch (Exception $e) {
            error_log("Create Department Error: " . $e->getMessage());
            set_flash_message('danger', "Failed to create department: " . $e->getMessage());
        }
        redirect('hr/departments.php');
    }

    // EDIT DEPARTMENT
    elseif ($action === 'edit_department') {
        $dept_id = (int)($_POST['department_id'] ?? 0);
        $name = trim(sanitize_input($_POST['name'] ?? ''));
        $code = strtoupper(trim(sanitize_input($_POST['code'] ?? '')));
        $description = trim(sanitize_input($_POST['description'] ?? ''));
        $manager_id = !empty($_POST['manager_id']) ? (int)$_POST['manager_id'] : null;

        if ($dept_id <= 0 || empty($name) || empty($code)) {
            set_flash_message('danger', 'Invalid department details provided.');
            redirect('hr/departments.php');
        }

        // Check duplicate name or code for other records
        $stmt_check = $pdo->prepare("SELECT id FROM departments WHERE (name = ? OR code = ?) AND id != ?");
        $stmt_check->execute([$name, $code, $dept_id]);
        if ($stmt_check->fetch()) {
            set_flash_message('danger', "Another department already uses the name '$name' or code '$code'.");
            redirect('hr/departments.php');
        }

        try {
            $stmt_upd = $pdo->prepare("UPDATE departments SET name = ?, code = ?, description = ?, manager_id = ? WHERE id = ?");
            $stmt_upd->execute([$name, $code, $description, $manager_id, $dept_id]);

            logHRAudit($pdo, $user_id, 'update_department', 'departments', $dept_id, "Updated department '$name' ($code)");
            set_flash_message('success', "Department '$name' ($code) has been updated successfully.");
        } catch (Exception $e) {
            error_log("Update Department Error: " . $e->getMessage());
            set_flash_message('danger', "Failed to update department: " . $e->getMessage());
        }
        redirect('hr/departments.php');
    }

    // DELETE DEPARTMENT
    elseif ($action === 'delete_department') {
        $dept_id = (int)($_POST['department_id'] ?? 0);
        
        if ($dept_id <= 0) {
            set_flash_message('danger', 'Invalid department selection.');
            redirect('hr/departments.php');
        }

        // Check if department exists
        $stmt_dept = $pdo->prepare("SELECT name, code FROM departments WHERE id = ?");
        $stmt_dept->execute([$dept_id]);
        $dept_data = $stmt_dept->fetch();

        if (!$dept_data) {
            set_flash_message('danger', 'Department not found.');
            redirect('hr/departments.php');
        }

        // Check linked employees
        $stmt_emp_cnt = $pdo->prepare("SELECT COUNT(*) FROM employee_profiles WHERE department_id = ?");
        $stmt_emp_cnt->execute([$dept_id]);
        $emp_count = (int)$stmt_emp_cnt->fetchColumn();

        // Check linked vacancies
        $stmt_vac_cnt = $pdo->prepare("SELECT COUNT(*) FROM careers WHERE department_id = ?");
        $stmt_vac_cnt->execute([$dept_id]);
        $vac_count = (int)$stmt_vac_cnt->fetchColumn();

        if ($emp_count > 0 || $vac_count > 0) {
            set_flash_message('danger', "Cannot delete department '{$dept_data['name']}': $emp_count active employee(s) and $vac_count job vacancy/vacancies are assigned to it. Please reassign them first.");
            redirect('hr/departments.php');
        }

        try {
            $stmt_del = $pdo->prepare("DELETE FROM departments WHERE id = ?");
            $stmt_del->execute([$dept_id]);

            logHRAudit($pdo, $user_id, 'delete_department', 'departments', $dept_id, "Deleted department '{$dept_data['name']}' ({$dept_data['code']})");
            set_flash_message('success', "Department '{$dept_data['name']}' has been deleted.");
        } catch (Exception $e) {
            error_log("Delete Department Error: " . $e->getMessage());
            set_flash_message('danger', "Failed to delete department: " . $e->getMessage());
        }
        redirect('hr/departments.php');
    }
}

// Fetch all departments with headcount & manager details
$sql = "
    SELECT 
        d.*,
        u.name AS manager_name,
        u.email AS manager_email,
        COUNT(DISTINCT ep.id) AS employee_count,
        COUNT(DISTINCT c.id) AS vacancy_count
    FROM departments d
    LEFT JOIN users u ON d.manager_id = u.id
    LEFT JOIN employee_profiles ep ON d.id = ep.department_id
    LEFT JOIN careers c ON d.id = c.department_id AND c.status = 'published'
    GROUP BY d.id
    ORDER BY d.name ASC
";
$departments = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// Fetch potential department managers (Super Admins, Admins, HR Managers, Dept Managers, Staff)
$sql_managers = "
    SELECT u.id, u.name, u.email, u.role 
    FROM users u 
    WHERE u.role IN ('super_admin', 'admin', 'hr_manager', 'hr_officer', 'department_manager') 
      AND u.status = 'active'
    ORDER BY u.name ASC
";
$managers = $pdo->query($sql_managers)->fetchAll(PDO::FETCH_ASSOC);

// Total metrics calculation
$total_depts = count($departments);
$total_headcount = array_sum(array_column($departments, 'employee_count'));
$total_vacancies = array_sum(array_column($departments, 'vacancy_count'));
$depts_with_managers = count(array_filter($departments, fn($d) => !empty($d['manager_id'])));

$admin_page_title = "Dynamic Department Management";
require_once __DIR__ . '/../includes/admin_sidebar.php';
?>

<!-- Action Header & Statistics Overview Cards -->
<div class="space-y-6">

    <!-- Flash Notifications -->
    <?php echo display_flash_message(); ?>

    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-3xl shadow-sm">
        <div>
            <div class="flex items-center space-x-2">
                <span class="px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider bg-amber-500/10 text-amber-500 rounded-full border border-amber-500/20">
                    HR & Admin Operations
                </span>
                <span class="text-xs text-slate-400 font-semibold">• Corporate Directory</span>
            </div>
            <h1 class="text-2xl font-extrabold text-slate-900 dark:text-white mt-1">Corporate Department Management</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Configure, update, and manage company departments, headcount metrics, and assigned leadership.</p>
        </div>

        <button type="button" onclick="openAddDeptModal()" class="inline-flex items-center justify-center space-x-2 px-5 py-3 bg-amber-500 hover:bg-amber-600 text-slate-950 font-extrabold text-xs uppercase tracking-wider rounded-2xl transition-all shadow-md hover:shadow-lg hover:-translate-y-0.5">
            <i aria-hidden="true" class="bi bi-plus-circle-fill text-sm"></i>
            <span>Add New Department</span>
        </button>
    </div>

    <!-- Analytics Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 rounded-3xl shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Total Departments</span>
                    <h3 class="text-2xl font-black text-slate-900 dark:text-white mt-1"><?php echo $total_depts; ?></h3>
                </div>
                <div class="h-12 w-12 rounded-2xl bg-amber-500/10 text-amber-500 flex items-center justify-center text-xl font-bold">
                    <i aria-hidden="true" class="bi bi-diagram-3-fill"></i>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 rounded-3xl shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Assigned Headcount</span>
                    <h3 class="text-2xl font-black text-slate-900 dark:text-white mt-1"><?php echo $total_headcount; ?></h3>
                </div>
                <div class="h-12 w-12 rounded-2xl bg-emerald-500/10 text-emerald-500 flex items-center justify-center text-xl font-bold">
                    <i aria-hidden="true" class="bi bi-people-fill"></i>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 rounded-3xl shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Depts with Managers</span>
                    <h3 class="text-2xl font-black text-slate-900 dark:text-white mt-1"><?php echo $depts_with_managers; ?> / <?php echo $total_depts; ?></h3>
                </div>
                <div class="h-12 w-12 rounded-2xl bg-indigo-500/10 text-indigo-500 flex items-center justify-center text-xl font-bold">
                    <i aria-hidden="true" class="bi bi-person-badge-fill"></i>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 rounded-3xl shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Active Job Vacancies</span>
                    <h3 class="text-2xl font-black text-slate-900 dark:text-white mt-1"><?php echo $total_vacancies; ?></h3>
                </div>
                <div class="h-12 w-12 rounded-2xl bg-blue-500/10 text-blue-500 flex items-center justify-center text-xl font-bold">
                    <i aria-hidden="true" class="bi bi-briefcase-fill"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Departments Data Table -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl shadow-sm overflow-hidden">
        <div class="p-6 border-b border-slate-100 dark:border-slate-800/80 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <h2 class="text-base font-extrabold text-slate-900 dark:text-white">Active Corporate Departments</h2>
                <p class="text-xs text-slate-400">Detailed list of all registered organizational units and reporting personnel.</p>
            </div>
            
            <div class="w-full sm:w-64">
                <div class="relative">
                    <i aria-hidden="true" class="bi bi-search absolute left-3 top-2.5 text-slate-400 text-xs"></i>
                    <input type="text" id="deptSearchInput" onkeyup="filterDeptTable()" placeholder="Search department or code..." class="w-full pl-9 pr-4 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500">
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse" id="departmentsTable">
                <thead>
                    <tr class="bg-slate-50/50 dark:bg-slate-950/50 border-b border-slate-100 dark:border-slate-800 text-[10px] font-extrabold uppercase tracking-widest text-slate-400">
                        <th class="px-6 py-4">Dept Code</th>
                        <th class="px-6 py-4">Department Name & Info</th>
                        <th class="px-6 py-4">Department Manager</th>
                        <th class="px-6 py-4 text-center">Staff Count</th>
                        <th class="px-6 py-4 text-center">Open Jobs</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-xs font-semibold">
                    <?php if (empty($departments)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-slate-400">
                                <i aria-hidden="true" class="bi bi-diagram-3 text-4xl block mb-2 text-slate-300"></i>
                                No departments registered yet. Click <strong>"Add New Department"</strong> above to create your first department.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($departments as $dept): ?>
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-block px-3 py-1 bg-amber-500/10 text-amber-500 font-extrabold font-mono text-[11px] rounded-lg border border-amber-500/20">
                                        <?php echo htmlspecialchars($dept['code']); ?>
                                    </span>
                                </td>

                                <td class="px-6 py-4">
                                    <span class="block font-bold text-slate-900 dark:text-white text-sm">
                                        <?php echo htmlspecialchars($dept['name']); ?>
                                    </span>
                                    <?php if (!empty($dept['description'])): ?>
                                        <span class="block text-[11px] text-slate-400 truncate max-w-xs mt-0.5">
                                            <?php echo htmlspecialchars($dept['description']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="block text-[10px] text-slate-400 italic mt-0.5">No description set</span>
                                    <?php endif; ?>
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if (!empty($dept['manager_name'])): ?>
                                        <div class="flex items-center space-x-2.5">
                                            <div class="h-7 w-7 rounded-lg bg-indigo-500/10 text-indigo-500 flex items-center justify-center font-bold text-[10px]">
                                                <?php echo strtoupper(substr($dept['manager_name'], 0, 2)); ?>
                                            </div>
                                            <div>
                                                <span class="block text-xs font-bold text-slate-800 dark:text-slate-200 leading-tight">
                                                    <?php echo htmlspecialchars($dept['manager_name']); ?>
                                                </span>
                                                <span class="block text-[10px] text-slate-400 truncate max-w-[160px]">
                                                    <?php echo htmlspecialchars($dept['manager_email']); ?>
                                                </span>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <span class="inline-block px-2.5 py-1 text-[10px] font-semibold text-slate-400 bg-slate-100 dark:bg-slate-800 rounded-lg">
                                            <i aria-hidden="true" class="bi bi-exclamation-circle mr-1 text-amber-500"></i> Unassigned
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td class="px-6 py-4 text-center whitespace-nowrap">
                                    <span class="inline-flex items-center justify-center px-3 py-1 rounded-full text-xs font-extrabold bg-emerald-500/10 text-emerald-500 border border-emerald-500/20">
                                        <i aria-hidden="true" class="bi bi-people-fill mr-1.5 text-xs"></i>
                                        <?php echo $dept['employee_count']; ?> Staff
                                    </span>
                                </td>

                                <td class="px-6 py-4 text-center whitespace-nowrap">
                                    <span class="inline-flex items-center justify-center px-3 py-1 rounded-full text-xs font-extrabold bg-blue-500/10 text-blue-500 border border-blue-500/20">
                                        <i aria-hidden="true" class="bi bi-briefcase-fill mr-1.5 text-xs"></i>
                                        <?php echo $dept['vacancy_count']; ?> Jobs
                                    </span>
                                </td>

                                <td class="px-6 py-4 text-right whitespace-nowrap space-x-2">
                                    <button type="button" 
                                            onclick='openEditDeptModal(<?php echo json_encode($dept, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)' 
                                            class="px-3 py-1.5 bg-slate-100 hover:bg-amber-500 hover:text-slate-950 dark:bg-slate-800 dark:hover:bg-amber-500 dark:hover:text-slate-950 text-slate-700 dark:text-slate-200 text-xs font-bold rounded-xl transition-all inline-flex items-center space-x-1"
                                            title="Edit Department">
                                        <i aria-hidden="true" class="bi bi-pencil-square"></i>
                                        <span>Edit</span>
                                    </button>

                                    <button type="button" 
                                            onclick="openDeleteDeptModal(<?php echo $dept['id']; ?>, '<?php echo htmlspecialchars(addslashes($dept['name'])); ?>', <?php echo $dept['employee_count']; ?>, <?php echo $dept['vacancy_count']; ?>)" 
                                            class="px-3 py-1.5 bg-rose-50 hover:bg-rose-600 hover:text-white dark:bg-rose-950/30 dark:hover:bg-rose-600 dark:hover:text-white text-rose-600 dark:text-rose-300 text-xs font-bold rounded-xl transition-all inline-flex items-center space-x-1"
                                            title="Delete Department">
                                        <i aria-hidden="true" class="bi bi-trash-fill"></i>
                                        <span>Delete</span>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- ADD DEPARTMENT MODAL -->
<!-- ========================================================================= -->
<div id="addDeptModal" class="fixed inset-0 bg-slate-950/70 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 w-full max-w-lg rounded-3xl shadow-2xl p-6 sm:p-8 space-y-6">
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-4">
            <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-amber-500">Corporate Directory</span>
                <h3 class="text-lg font-extrabold text-slate-900 dark:text-white">Create New Department</h3>
            </div>
            <button type="button" onclick="closeAddDeptModal()" class="text-slate-400 hover:text-slate-900 dark:hover:text-white p-2 rounded-lg" aria-label="Close modal" title="Close">
                <i aria-hidden="true" class="bi bi-x-lg text-lg"></i>
            </button>
        </div>

        <form action="departments.php" method="POST" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            <input type="hidden" name="action" value="create_department">

            <div>
                <label for="add_name" class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Department Name <span class="text-rose-500">*</span></label>
                <input type="text" id="add_name" name="name" required placeholder="e.g. Legal & Advisory Services" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500">
            </div>

            <div>
                <label for="add_code" class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Department Code (Short Identifier)</label>
                <input type="text" id="add_code" name="code" placeholder="e.g. LEGAL (Leave blank to auto-generate)" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-xs font-mono uppercase text-slate-900 dark:text-white focus:outline-none focus:border-amber-500">
            </div>

            <div>
                <label for="add_manager_id" class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Assigned Department Manager</label>
                <select id="add_manager_id" name="manager_id" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500">
                    <option value="">-- No Manager Assigned Yet --</option>
                    <?php foreach ($managers as $mgr): ?>
                        <option value="<?php echo $mgr['id']; ?>">
                            <?php echo htmlspecialchars($mgr['name']); ?> (<?php echo htmlspecialchars($mgr['email']); ?> - <?php echo str_replace('_', ' ', $mgr['role']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="add_description" class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Department Scope & Description</label>
                <textarea id="add_description" name="description" rows="3" placeholder="Briefly describe the functions and scope of this department..." class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500"></textarea>
            </div>

            <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                <button type="button" onclick="closeAddDeptModal()" class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold rounded-xl transition-colors">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2.5 bg-amber-500 hover:bg-amber-600 text-slate-950 text-xs font-extrabold uppercase tracking-wider rounded-xl transition-colors shadow">
                    Create Department
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================================================= -->
<!-- EDIT DEPARTMENT MODAL -->
<!-- ========================================================================= -->
<div id="editDeptModal" class="fixed inset-0 bg-slate-950/70 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 w-full max-w-lg rounded-3xl shadow-2xl p-6 sm:p-8 space-y-6">
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-4">
            <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-amber-500">Modify Department</span>
                <h3 class="text-lg font-extrabold text-slate-900 dark:text-white">Edit Department Details</h3>
            </div>
            <button type="button" onclick="closeEditDeptModal()" class="text-slate-400 hover:text-slate-900 dark:hover:text-white p-2 rounded-lg" aria-label="Close modal" title="Close">
                <i aria-hidden="true" class="bi bi-x-lg text-lg"></i>
            </button>
        </div>

        <form action="departments.php" method="POST" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            <input type="hidden" name="action" value="edit_department">
            <input type="hidden" id="edit_dept_id" name="department_id">

            <div>
                <label for="edit_name" class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Department Name <span class="text-rose-500">*</span></label>
                <input type="text" id="edit_name" name="name" required class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500">
            </div>

            <div>
                <label for="edit_code" class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Department Code <span class="text-rose-500">*</span></label>
                <input type="text" id="edit_code" name="code" required class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-xs font-mono uppercase text-slate-900 dark:text-white focus:outline-none focus:border-amber-500">
            </div>

            <div>
                <label for="edit_manager_id" class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Assigned Department Manager</label>
                <select id="edit_manager_id" name="manager_id" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500">
                    <option value="">-- No Manager Assigned --</option>
                    <?php foreach ($managers as $mgr): ?>
                        <option value="<?php echo $mgr['id']; ?>">
                            <?php echo htmlspecialchars($mgr['name']); ?> (<?php echo htmlspecialchars($mgr['email']); ?> - <?php echo str_replace('_', ' ', $mgr['role']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="edit_description" class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Department Scope & Description</label>
                <textarea id="edit_description" name="description" rows="3" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500"></textarea>
            </div>

            <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                <button type="button" onclick="closeEditDeptModal()" class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold rounded-xl transition-colors">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2.5 bg-amber-500 hover:bg-amber-600 text-slate-950 text-xs font-extrabold uppercase tracking-wider rounded-xl transition-colors shadow">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================================================= -->
<!-- DELETE DEPARTMENT CONFIRMATION MODAL -->
<!-- ========================================================================= -->
<div id="deleteDeptModal" class="fixed inset-0 bg-slate-950/70 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 w-full max-w-md rounded-3xl shadow-2xl p-6 sm:p-8 space-y-6">
        <div class="text-center space-y-3">
            <div class="h-16 w-16 bg-rose-500/10 text-rose-500 rounded-2xl flex items-center justify-center text-2xl font-bold mx-auto">
                <i aria-hidden="true" class="bi bi-trash-fill"></i>
            </div>
            <h3 class="text-lg font-extrabold text-slate-900 dark:text-white">Delete Department</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400">
                Are you sure you want to remove <strong id="delete_dept_name_span" class="text-slate-900 dark:text-white"></strong>?
            </p>
        </div>

        <form action="departments.php" method="POST" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            <input type="hidden" name="action" value="delete_department">
            <input type="hidden" id="delete_dept_id" name="department_id">

            <div class="flex items-center justify-center space-x-3 pt-2">
                <button type="button" onclick="closeDeleteDeptModal()" class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold rounded-xl transition-colors">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2.5 bg-rose-600 hover:bg-rose-700 text-white text-xs font-extrabold uppercase tracking-wider rounded-xl transition-colors shadow">
                    Confirm Delete
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddDeptModal() {
    document.getElementById('addDeptModal').classList.remove('hidden');
    document.getElementById('addDeptModal').classList.add('flex');
}

function closeAddDeptModal() {
    document.getElementById('addDeptModal').classList.add('hidden');
    document.getElementById('addDeptModal').classList.remove('flex');
}

function openEditDeptModal(dept) {
    document.getElementById('edit_dept_id').value = dept.id;
    document.getElementById('edit_name').value = dept.name;
    document.getElementById('edit_code').value = dept.code;
    document.getElementById('edit_description').value = dept.description || '';
    document.getElementById('edit_manager_id').value = dept.manager_id || '';
    
    document.getElementById('editDeptModal').classList.remove('hidden');
    document.getElementById('editDeptModal').classList.add('flex');
}

function closeEditDeptModal() {
    document.getElementById('editDeptModal').classList.add('hidden');
    document.getElementById('editDeptModal').classList.remove('flex');
}

function openDeleteDeptModal(id, name, empCount, vacCount) {
    if (empCount > 0 || vacCount > 0) {
        alert("Cannot delete '" + name + "': " + empCount + " staff member(s) and " + vacCount + " job vacancy/vacancies are assigned to this department. Please reassign them first.");
        return;
    }
    document.getElementById('delete_dept_id').value = id;
    document.getElementById('delete_dept_name_span').textContent = name;
    document.getElementById('deleteDeptModal').classList.remove('hidden');
    document.getElementById('deleteDeptModal').classList.add('flex');
}

function closeDeleteDeptModal() {
    document.getElementById('deleteDeptModal').classList.add('hidden');
    document.getElementById('deleteDeptModal').classList.remove('flex');
}

function filterDeptTable() {
    const query = document.getElementById('deptSearchInput').value.toLowerCase();
    const rows = document.querySelectorAll('#departmentsTable tbody tr');
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(query) ? '' : 'none';
    });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
