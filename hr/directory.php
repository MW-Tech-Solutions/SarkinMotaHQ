<?php
/**
 * Sarkin Mota HQ HR Module - Staff Directory & Organization Management
 * Handles staff roster, department management, department transfers, role adjustments, and lifecycle actions.
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_helper.php';
require_once __DIR__ . '/../includes/rbac_helper.php';

require_login();

$user = get_logged_in_user();
$user_id = $user['id'];

if (!has_permission('hr.staff.view') && !has_permission('hr.view_dashboard')) {
    redirect('../auth/dashboard.php?error=' . urlencode("Unauthorized access to Staff Directory."));
    exit;
}

$can_manage_staff = has_permission('hr.staff.manage') || $user['role'] === 'super_admin' || $user['role'] === 'admin';
$can_manage_depts = has_permission('hr.departments.manage') || $user['role'] === 'super_admin' || $user['role'] === 'admin';

$flash_success = '';
$flash_error = '';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $csrf_token = $_POST['csrf_token'] ?? '';

    if (!verify_csrf_token($csrf_token)) {
        $flash_error = "Invalid CSRF token. Please refresh and try again.";
    } else {
        try {
            if ($action === 'create_department' && $can_manage_depts) {
                $dept_name = trim($_POST['name'] ?? '');
                $dept_code = strtoupper(trim($_POST['code'] ?? ''));
                $dept_desc = trim($_POST['description'] ?? '');

                if (empty($dept_name) || empty($dept_code)) {
                    throw new Exception("Department Name and Code are required.");
                }

                $stmt_check = $pdo->prepare("SELECT id FROM departments WHERE code = ? OR name = ?");
                $stmt_check->execute([$dept_code, $dept_name]);
                if ($stmt_check->fetch()) {
                    throw new Exception("A department with this Name or Code already exists.");
                }

                $stmt_ins = $pdo->prepare("INSERT INTO departments (name, code, description, created_at) VALUES (?, ?, ?, NOW())");
                $stmt_ins->execute([$dept_name, $dept_code, $dept_desc]);

                logHRAudit($pdo, $user_id, 'create_department', 'departments', $pdo->lastInsertId(), "Created department '$dept_name' ($dept_code)");
                $flash_success = "Department '$dept_name' created successfully.";

            } elseif ($action === 'transfer_department' && $can_manage_staff) {
                $employee_id = (int)($_POST['employee_id'] ?? 0);
                $new_dept_id = (int)($_POST['new_department_id'] ?? 0);
                $new_manager_id = !empty($_POST['new_reporting_manager_id']) ? (int)$_POST['new_reporting_manager_id'] : null;
                $reason = trim($_POST['reason'] ?? '');

                if ($employee_id <= 0 || $new_dept_id <= 0) {
                    throw new Exception("Invalid employee or department selected for transfer.");
                }

                $pdo->beginTransaction();

                $stmt_emp = $pdo->prepare("SELECT ep.*, u.id AS user_id, u.full_name, d.name AS old_dept_name FROM employee_profiles ep JOIN users u ON ep.user_id = u.id LEFT JOIN departments d ON ep.department_id = d.id WHERE ep.id = ?");
                $stmt_emp->execute([$employee_id]);
                $emp = $stmt_emp->fetch(PDO::FETCH_ASSOC);

                if (!$emp) {
                    $pdo->rollBack();
                    throw new Exception("Employee record not found.");
                }

                $stmt_dept = $pdo->prepare("SELECT name FROM departments WHERE id = ?");
                $stmt_dept->execute([$new_dept_id]);
                $new_dept = $stmt_dept->fetchColumn();

                $stmt_upd = $pdo->prepare("UPDATE employee_profiles SET department_id = ?, reporting_manager_id = ?, updated_at = NOW() WHERE id = ?");
                $stmt_upd->execute([$new_dept_id, $new_manager_id, $employee_id]);

                // Also update department_id in users if column exists
                try {
                    $stmt_u_dept = $pdo->prepare("UPDATE users SET department_id = ? WHERE id = ?");
                    $stmt_u_dept->execute([$new_dept_id, $emp['user_id']]);
                } catch (Exception $e) {
                    // column might not exist or already updated
                }

                logHRAudit($pdo, $user_id, 'transfer_department', 'employee_profiles', $employee_id, "Transferred employee {$emp['full_name']} from '{$emp['old_dept_name']}' to '$new_dept'. Reason: $reason");

                $pdo->commit();
                $flash_success = "Employee {$emp['full_name']} transferred to $new_dept successfully.";

            } elseif ($action === 'change_role' && $can_manage_staff) {
                $employee_id = (int)($_POST['employee_id'] ?? 0);
                $new_role_id = (int)($_POST['new_role_id'] ?? 0);

                if ($employee_id <= 0 || $new_role_id <= 0) {
                    throw new Exception("Invalid employee or role selection.");
                }

                $stmt_role = $pdo->prepare("SELECT name FROM roles WHERE id = ?");
                $stmt_role->execute([$new_role_id]);
                $role_name = $stmt_role->fetchColumn();

                if (in_array($role_name, ['admin', 'super_admin']) && $user['role'] !== 'super_admin') {
                    throw new Exception("HR personnel cannot assign Administrator or Super Admin roles.");
                }

                $stmt_emp = $pdo->prepare("SELECT ep.*, u.id AS user_id, u.full_name FROM employee_profiles ep JOIN users u ON ep.user_id = u.id WHERE ep.id = ?");
                $stmt_emp->execute([$employee_id]);
                $emp = $stmt_emp->fetch(PDO::FETCH_ASSOC);

                if (!$emp) {
                    throw new Exception("Employee not found.");
                }

                $pdo->beginTransaction();

                // Delete old user_roles entry and insert new
                $stmt_del_ur = $pdo->prepare("DELETE FROM user_roles WHERE user_id = ?");
                $stmt_del_ur->execute([$emp['user_id']]);

                $stmt_ins_ur = $pdo->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)");
                $stmt_ins_ur->execute([$emp['user_id'], $new_role_id]);

                // Update users.role fallback column if role_name is valid legacy enum
                $valid_legacy = ['client', 'tenant', 'landlord', 'admin', 'super_admin', 'staff', 'hr_manager', 'hr_officer', 'department_manager', 'interviewer'];
                if (in_array($role_name, $valid_legacy)) {
                    $stmt_u_role = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
                    $stmt_u_role->execute([$role_name, $emp['user_id']]);
                }

                logHRAudit($pdo, $user_id, 'change_role', 'user_roles', $emp['user_id'], "Changed role for employee {$emp['full_name']} to '$role_name'");

                $pdo->commit();
                $flash_success = "Role updated to '$role_name' for {$emp['full_name']}.";

            } elseif ($action === 'change_account_status' && $can_manage_staff) {
                $employee_id = (int)($_POST['employee_id'] ?? 0);
                $new_status = trim($_POST['status'] ?? '');
                $reason = trim($_POST['reason'] ?? '');

                $valid_statuses = ['active', 'suspended', 'transferred', 'departed'];
                if ($employee_id <= 0 || !in_array($new_status, $valid_statuses)) {
                    throw new Exception("Invalid status requested.");
                }

                $stmt_emp = $pdo->prepare("SELECT ep.*, u.id AS user_id, u.full_name, u.email FROM employee_profiles ep JOIN users u ON ep.user_id = u.id WHERE ep.id = ?");
                $stmt_emp->execute([$employee_id]);
                $emp = $stmt_emp->fetch(PDO::FETCH_ASSOC);

                if (!$emp) {
                    throw new Exception("Employee record not found.");
                }

                $pdo->beginTransaction();

                $is_active_flag = ($new_status === 'active') ? 1 : 0;

                $stmt_upd_ep = $pdo->prepare("UPDATE employee_profiles SET employment_status = ?, updated_at = NOW() WHERE id = ?");
                $stmt_upd_ep->execute([$new_status, $employee_id]);

                $stmt_upd_u = $pdo->prepare("UPDATE users SET is_active = ? WHERE id = ?");
                $stmt_upd_u->execute([$is_active_flag, $emp['user_id']]);

                logHRAudit($pdo, $user_id, 'change_account_status', 'employee_profiles', $employee_id, "Changed account status for {$emp['full_name']} to '$new_status'. Reason: $reason");

                $pdo->commit();
                $flash_success = "Account status for {$emp['full_name']} updated to '$new_status'.";
            } elseif ($action === 'assign_divisions' && $can_manage_staff) {
                require_once __DIR__ . '/../includes/divisions_helper.php';
                $target_user_id = (int)($_POST['target_user_id'] ?? 0);
                $division_ids = $_POST['division_ids'] ?? [];

                if ($target_user_id <= 0) {
                    throw new Exception("Invalid target staff user selected.");
                }

                if (set_user_divisions($target_user_id, $division_ids, $user_id)) {
                    logHRAudit($pdo, $user_id, 'assign_divisions', 'user_divisions', $target_user_id, "Updated corporate division assignments for User ID {$target_user_id}");
                    $flash_success = "Corporate division assignments updated successfully for staff member.";
                } else {
                    throw new Exception("Failed to update division assignments.");
                }
            }
        } catch (Exception $ex) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $flash_error = $ex->getMessage();
        }
    }
}

// Search and Filter Params
$filter_dept = (int)($_GET['department_id'] ?? 0);
$filter_status = trim($_GET['status'] ?? '');
$search_q = trim($_GET['q'] ?? '');

// Fetch Departments
$stmt_depts = $pdo->query("SELECT d.*, COUNT(ep.id) AS employee_count FROM departments d LEFT JOIN employee_profiles ep ON d.id = ep.department_id GROUP BY d.id ORDER BY d.name ASC");
$departments = $stmt_depts->fetchAll(PDO::FETCH_ASSOC);

// Fetch Staff Directory Query
$where_clauses = ["1=1"];
$params = [];

if ($filter_dept > 0) {
    $where_clauses[] = "ep.department_id = ?";
    $params[] = $filter_dept;
}

if (!empty($filter_status)) {
    $where_clauses[] = "ep.employment_status = ?";
    $params[] = $filter_status;
}

if (!empty($search_q)) {
    $where_clauses[] = "(u.full_name LIKE ? OR u.email LIKE ? OR ep.employee_number LIKE ? OR ep.job_designation LIKE ?)";
    $q_like = "%$search_q%";
    $params = array_merge($params, [$q_like, $q_like, $q_like, $q_like]);
}

$where_sql = implode(' AND ', $where_clauses);

$sql_staff = "
    SELECT ep.*, 
           u.full_name, u.email, u.phone, u.is_active AS user_active, u.role AS user_legacy_role,
           d.name AS department_name, d.code AS department_code,
           mgr.full_name AS reporting_manager_name,
           r.name AS rbac_role_name, r.id AS rbac_role_id
    FROM employee_profiles ep
    JOIN users u ON ep.user_id = u.id
    LEFT JOIN departments d ON ep.department_id = d.id
    LEFT JOIN users mgr ON ep.reporting_manager_id = mgr.id
    LEFT JOIN user_roles ur ON u.id = ur.user_id
    LEFT JOIN roles r ON ur.role_id = r.id
    WHERE $where_sql
    ORDER BY ep.created_at DESC
";
$stmt_staff = $pdo->prepare($sql_staff);
$stmt_staff->execute($params);
$employees = $stmt_staff->fetchAll(PDO::FETCH_ASSOC);

// Fetch all system roles for role assignment modal
$stmt_roles = $pdo->query("SELECT id, name, description FROM roles WHERE name NOT IN ('admin', 'super_admin') ORDER BY name ASC");
$roles_list = $stmt_roles->fetchAll(PDO::FETCH_ASSOC);

// Helper for HR audit logging if not already defined in scope
if (!function_exists('logHRAudit')) {
    function logHRAudit($pdo, $user_id, $action, $target_type = null, $target_id = null, $details = null) {
        try {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $stmt = $pdo->prepare("INSERT INTO hr_audit_logs (user_id, action, target_type, target_id, details, ip_address, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$user_id, $action, $target_type, $target_id, $details, $ip]);
        } catch (Exception $e) {
            error_log("HR Audit Log error: " . $e->getMessage());
        }
    }
}

$page_title = "Staff Directory & Organization Management - Sarkin Mota HQ HR";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body class="bg-gray-50 text-gray-800 font-sans">

<div class="flex min-h-screen">
    <!-- Admin Sidebar Include -->
    <?php 
    $admin_page_title = "Staff Directory";
    require_once __DIR__ . '/../includes/admin_sidebar.php'; 
    ?>

    <!-- Main Content Area -->
    <main class="flex-1 p-6 md:p-8 ml-0 md:ml-64">
        <!-- Top Bar -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8 pb-4 border-b border-gray-200">
            <div>
                <h1 class="text-2xl md:text-3xl font-bold text-gray-900 flex items-center gap-2">
                    <i class="bi bi-people-fill text-amber-500"></i>
                    Staff Directory & Corporate Structure
                </h1>
                <p class="text-sm text-gray-500 mt-1">Manage employee profiles, departments, reporting lines, and account lifecycles.</p>
            </div>
            
            <div class="flex items-center gap-3">
                <a href="index.php" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold rounded-lg flex items-center gap-2 transition">
                    <i class="bi bi-arrow-left text-amber-500"></i> HR Dashboard
                </a>
                <?php if ($can_manage_depts): ?>
                    <button onclick="document.getElementById('modal-create-dept').classList.remove('hidden')" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-sm font-semibold rounded-lg flex items-center gap-2 shadow-sm transition">
                        <i class="bi bi-building-add"></i> Add Department
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Flash Messages -->
        <?php if (!empty($flash_success)): ?>
            <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-lg flex items-center gap-3">
                <i class="bi bi-check-circle-fill text-emerald-500 text-xl"></i>
                <div><?= htmlspecialchars($flash_success) ?></div>
            </div>
        <?php endif; ?>

        <?php if (!empty($flash_error)): ?>
            <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-800 rounded-lg flex items-center gap-3">
                <i class="bi bi-exclamation-triangle-fill text-red-500 text-xl"></i>
                <div><?= htmlspecialchars($flash_error) ?></div>
            </div>
        <?php endif; ?>

        <!-- Departments Summary Grid -->
        <div class="mb-8">
            <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                <i class="bi bi-diagram-3-fill text-amber-500"></i> Corporate Departments (<?= count($departments) ?>)
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                <?php foreach ($departments as $dept): ?>
                    <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm hover:border-amber-400 transition">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-bold px-2 py-0.5 bg-amber-100 text-amber-800 rounded">
                                <?= htmlspecialchars($dept['code']) ?>
                            </span>
                            <span class="text-xs text-gray-500">
                                <i class="bi bi-people text-amber-500"></i> <?= $dept['employee_count'] ?> Staff
                            </span>
                        </div>
                        <h3 class="font-bold text-gray-800 text-sm truncate"><?= htmlspecialchars($dept['name']) ?></h3>
                        <p class="text-xs text-gray-500 mt-1 line-clamp-2"><?= htmlspecialchars($dept['description'] ?? 'No description.') ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Filter & Search Bar -->
        <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm mb-6">
            <form method="GET" class="flex flex-col md:flex-row gap-4 items-center justify-between">
                <div class="flex flex-1 flex-col sm:flex-row gap-3 w-full">
                    <div class="relative flex-1">
                        <i class="bi bi-search absolute left-3 top-2.5 text-gray-400"></i>
                        <input type="text" name="q" value="<?= htmlspecialchars($search_q) ?>" placeholder="Search name, email, employee ID..." class="w-full pl-9 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-500 focus:outline-none">
                    </div>
                    
                    <select name="department_id" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-500 focus:outline-none">
                        <option value="0">All Departments</option>
                        <?php foreach ($departments as $d): ?>
                            <option value="<?= $d['id'] ?>" <?= $filter_dept == $d['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($d['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <select name="status" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-500 focus:outline-none">
                        <option value="">All Employment Statuses</option>
                        <option value="active" <?= $filter_status === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="suspended" <?= $filter_status === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                        <option value="transferred" <?= $filter_status === 'transferred' ? 'selected' : '' ?>>Transferred</option>
                        <option value="departed" <?= $filter_status === 'departed' ? 'selected' : '' ?>>Departed</option>
                    </select>
                </div>

                <div class="flex gap-2 w-full md:w-auto justify-end">
                    <button type="submit" class="px-4 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-lg flex items-center gap-1 transition">
                        <i class="bi bi-filter"></i> Filter
                    </button>
                    <?php if ($filter_dept || !empty($filter_status) || !empty($search_q)): ?>
                        <a href="directory.php" class="px-3 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 text-sm font-medium rounded-lg flex items-center gap-1 transition">
                            Reset
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Staff Roster Table -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="p-4 border-b border-gray-200 flex justify-between items-center">
                <h3 class="font-bold text-gray-900 flex items-center gap-2">
                    <i class="bi bi-person-lines-fill text-amber-500"></i> Employee Roster (<?= count($employees) ?>)
                </h3>
            </div>

            <?php if (empty($employees)): ?>
                <div class="p-12 text-center">
                    <div class="w-16 h-16 bg-amber-50 rounded-full flex items-center justify-center mx-auto mb-3">
                        <i class="bi bi-person-x text-amber-500 text-3xl"></i>
                    </div>
                    <h4 class="font-bold text-gray-700">No Employee Records Found</h4>
                    <p class="text-sm text-gray-500 mt-1">Try clearing filters or onboarding candidates through the HR pipeline.</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-sm">
                        <thead>
                            <tr class="bg-gray-50 text-gray-600 border-b border-gray-200">
                                <th class="py-3 px-4 font-semibold">Employee</th>
                                <th class="py-3 px-4 font-semibold">Department & Position</th>
                                <th class="py-3 px-4 font-semibold">RBAC Role</th>
                                <th class="py-3 px-4 font-semibold">Reporting Line</th>
                                <th class="py-3 px-4 font-semibold">Status</th>
                                <th class="py-3 px-4 font-semibold text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php foreach ($employees as $emp): ?>
                                <tr class="hover:bg-gray-50/80 transition">
                                    <td class="py-3 px-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-full bg-amber-100 text-amber-800 font-bold flex items-center justify-center text-sm shadow-inner">
                                                <?= strtoupper(substr($emp['full_name'], 0, 2)) ?>
                                            </div>
                                            <div>
                                                <div class="font-bold text-gray-900"><?= htmlspecialchars($emp['full_name']) ?></div>
                                                <div class="text-xs text-gray-500 flex items-center gap-2">
                                                    <span><i class="bi bi-card-heading text-amber-500"></i> <?= htmlspecialchars($emp['employee_number']) ?></span>
                                                    <span>•</span>
                                                    <span><?= htmlspecialchars($emp['email']) ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="font-semibold text-gray-800"><?= htmlspecialchars($emp['job_designation']) ?></div>
                                        <div class="text-xs text-gray-500">
                                            <span class="font-medium text-amber-700 bg-amber-50 px-1.5 py-0.5 rounded border border-amber-200">
                                                <?= htmlspecialchars($emp['department_name'] ?? 'Unassigned') ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-50 text-blue-800 border border-blue-200">
                                            <i class="bi bi-shield-lock text-amber-500 mr-1"></i>
                                            <?= htmlspecialchars($emp['rbac_role_name'] ?? $emp['user_legacy_role']) ?>
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-xs text-gray-600">
                                        <?php if (!empty($emp['reporting_manager_name'])): ?>
                                            <div class="flex items-center gap-1 font-medium text-gray-800">
                                                <i class="bi bi-person-badge text-amber-500"></i> <?= htmlspecialchars($emp['reporting_manager_name']) ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-gray-400 italic">None Assigned</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3 px-4">
                                        <?php
                                        $status_colors = [
                                            'active' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                                            'suspended' => 'bg-red-100 text-red-800 border-red-200',
                                            'transferred' => 'bg-blue-100 text-blue-800 border-blue-200',
                                            'departed' => 'bg-gray-100 text-gray-800 border-gray-200'
                                        ];
                                        $badge_cls = $status_colors[$emp['employment_status']] ?? 'bg-gray-100 text-gray-700';
                                        ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium border <?= $badge_cls ?>">
                                            <?= ucfirst($emp['employment_status']) ?>
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-right">
                                        <?php if ($can_manage_staff): ?>
                                            <div class="flex justify-end items-center gap-1">
                                                <!-- Transfer Dept Button -->
                                                <button onclick="openTransferModal(<?= $emp['id'] ?>, '<?= htmlspecialchars(addslashes($emp['full_name'])) ?>', <?= $emp['department_id'] ?>)" title="Transfer Department" class="p-1.5 text-gray-500 hover:text-amber-600 rounded hover:bg-gray-100 transition">
                                                    <i class="bi bi-arrow-left-right text-base"></i>
                                                </button>

                                                <!-- Change Role Button -->
                                                <button onclick="openRoleModal(<?= $emp['id'] ?>, '<?= htmlspecialchars(addslashes($emp['full_name'])) ?>', <?= $emp['rbac_role_id'] ?? 0 ?>)" title="Change RBAC Role" class="p-1.5 text-gray-500 hover:text-blue-600 rounded hover:bg-gray-100 transition">
                                                    <i class="bi bi-shield-check text-base"></i>
                                                </button>

                                                <!-- Status Action Button -->
                                                <button onclick="openStatusModal(<?= $emp['id'] ?>, '<?= htmlspecialchars(addslashes($emp['full_name'])) ?>', '<?= $emp['employment_status'] ?>')" title="Account Lifecycle" class="p-1.5 text-gray-500 hover:text-red-600 rounded hover:bg-gray-100 transition">
                                                    <i class="bi bi-gear-fill text-base"></i>
                                                </button>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-xs text-gray-400">Read-only</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<!-- Modal: Create Department -->
<?php if ($can_manage_depts): ?>
<div id="modal-create-dept" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-xl max-w-md w-full p-6 shadow-xl relative">
        <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
            <i class="bi bi-building-add text-amber-500"></i> Create Corporate Department
        </h3>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
            <input type="hidden" name="action" value="create_department">

            <div class="space-y-4 text-sm">
                <div>
                    <label class="block font-medium text-gray-700 mb-1">Department Name *</label>
                    <input type="text" name="name" required placeholder="e.g. Environmental Consulting" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:outline-none">
                </div>
                <div>
                    <label class="block font-medium text-gray-700 mb-1">Department Code *</label>
                    <input type="text" name="code" required placeholder="e.g. ENV" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:outline-none uppercase">
                </div>
                <div>
                    <label class="block font-medium text-gray-700 mb-1">Description</label>
                    <textarea name="description" rows="3" placeholder="Brief summary of department responsibilities..." class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:outline-none"></textarea>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('modal-create-dept').classList.add('hidden')" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-medium text-sm">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg font-semibold text-sm">Save Department</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Modal: Department Transfer -->
<div id="modal-transfer" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-xl max-w-md w-full p-6 shadow-xl">
        <h3 class="text-lg font-bold text-gray-900 mb-2 flex items-center gap-2">
            <i class="bi bi-arrow-left-right text-amber-500"></i> Department Transfer
        </h3>
        <p id="transfer-emp-name" class="text-sm font-semibold text-amber-700 mb-4"></p>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
            <input type="hidden" name="action" value="transfer_department">
            <input type="hidden" name="employee_id" id="transfer-employee-id">

            <div class="space-y-4 text-sm">
                <div>
                    <label class="block font-medium text-gray-700 mb-1">New Department *</label>
                    <select name="new_department_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:outline-none">
                        <?php foreach ($departments as $d): ?>
                            <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?> (<?= $d['code'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block font-medium text-gray-700 mb-1">New Reporting Manager</label>
                    <select name="new_reporting_manager_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:outline-none">
                        <option value="">No Reporting Manager</option>
                        <?php foreach ($employees as $m): ?>
                            <option value="<?= $m['user_id'] ?>"><?= htmlspecialchars($m['full_name']) ?> (<?= htmlspecialchars($m['job_designation']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block font-medium text-gray-700 mb-1">Transfer Reason / Audit Note *</label>
                    <textarea name="reason" required rows="2" placeholder="Explain organizational restructuring or transfer rationale..." class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:outline-none"></textarea>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('modal-transfer').classList.add('hidden')" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg font-medium text-sm">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg font-semibold text-sm">Execute Transfer</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Role Change -->
<div id="modal-role" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-xl max-w-md w-full p-6 shadow-xl">
        <h3 class="text-lg font-bold text-gray-900 mb-2 flex items-center gap-2">
            <i class="bi bi-shield-check text-amber-500"></i> Update Access Role
        </h3>
        <p id="role-emp-name" class="text-sm font-semibold text-amber-700 mb-4"></p>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
            <input type="hidden" name="action" value="change_role">
            <input type="hidden" name="employee_id" id="role-employee-id">

            <div class="space-y-4 text-sm">
                <div>
                    <label class="block font-medium text-gray-700 mb-1">Assign Permission Role *</label>
                    <select name="new_role_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:outline-none">
                        <?php foreach ($roles_list as $r): ?>
                            <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['name']) ?> - <?= htmlspecialchars($r['description'] ?? '') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('modal-role').classList.add('hidden')" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg font-medium text-sm">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold text-sm">Update Role</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Status & Account Lifecycle -->
<div id="modal-status" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-xl max-w-md w-full p-6 shadow-xl">
        <h3 class="text-lg font-bold text-gray-900 mb-2 flex items-center gap-2">
            <i class="bi bi-gear-fill text-amber-500"></i> Account Status & Lifecycle
        </h3>
        <p id="status-emp-name" class="text-sm font-semibold text-amber-700 mb-4"></p>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
            <input type="hidden" name="action" value="change_account_status">
            <input type="hidden" name="employee_id" id="status-employee-id">

            <div class="space-y-4 text-sm">
                <div>
                    <label class="block font-medium text-gray-700 mb-1">Account Employment Status *</label>
                    <select name="status" id="status-select" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:outline-none">
                        <option value="active">Active (Full Portal Access)</option>
                        <option value="suspended">Suspended (Access Disabled)</option>
                        <option value="departed">Departed / Terminated (Account Closed)</option>
                    </select>
                </div>
                <div>
                    <label class="block font-medium text-gray-700 mb-1">Reason / Notes *</label>
                    <textarea name="reason" required rows="2" placeholder="Record reason for suspension or departure..." class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:outline-none"></textarea>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('modal-status').classList.add('hidden')" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg font-medium text-sm">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-semibold text-sm">Save Status</button>
            </div>
        </form>
    </div>
</div>

<script>
function openTransferModal(empId, empName, currentDeptId) {
    document.getElementById('transfer-employee-id').value = empId;
    document.getElementById('transfer-emp-name').innerText = empName;
    document.getElementById('modal-transfer').classList.remove('hidden');
}

function openRoleModal(empId, empName, currentRoleId) {
    document.getElementById('role-employee-id').value = empId;
    document.getElementById('role-emp-name').innerText = empName;
    document.getElementById('modal-role').classList.remove('hidden');
}

function openStatusModal(empId, empName, currentStatus) {
    document.getElementById('status-employee-id').value = empId;
    document.getElementById('status-emp-name').innerText = empName;
    document.getElementById('status-select').value = currentStatus;
    document.getElementById('modal-status').classList.remove('hidden');
}
</script>

</body>
</html>
