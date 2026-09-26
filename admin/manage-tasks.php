<?php
/**
 * Task Management & Staff Assignment Control Center
 * Sarkin Mota HQ — Enterprise Edition
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_helper.php';
require_once __DIR__ . '/../includes/rbac_helper.php';
require_once __DIR__ . '/../includes/divisions_helper.php';
require_once __DIR__ . '/../includes/notification_helper.php';

require_login();
$user = get_logged_in_user();

if (!has_permission('assign_tasks') && !has_permission('view_all_tasks')) {
    set_flash_message('danger', 'Access Denied: You do not have permission to access task management.');
    redirect('auth/dashboard.php');
}

$errors = [];
$success_msg = '';

$divisions = get_all_divisions(true);
$preselected_project_id = intval($_GET['project_id'] ?? 0);

// Handle Form Submission: Create & Assign Task
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = "CSRF Token Validation Failed. Please retry.";
    } else {
        $action = sanitize_input($_POST['action'] ?? '');

        if ($action === 'create_task') {
            $division_id = intval($_POST['division_id'] ?? 0);
            $project_id = intval($_POST['project_id'] ?? 0) ?: null;
            $staff_ids = $_POST['staff_ids'] ?? [];
            $title = sanitize_input($_POST['title'] ?? '');
            $description = sanitize_input($_POST['description'] ?? '');
            $instructions = sanitize_input($_POST['instructions'] ?? '');
            $priority = sanitize_input($_POST['priority'] ?? 'Normal');
            $start_date = !empty($_POST['start_date']) ? sanitize_input($_POST['start_date']) : date('Y-m-d');
            $due_date = !empty($_POST['due_date']) ? sanitize_input($_POST['due_date']) : date('Y-m-d', strtotime('+7 days'));

            if ($division_id <= 0) $errors[] = "Please select a Corporate Division.";
            if (empty($staff_ids) || !is_array($staff_ids)) $errors[] = "Please select at least one Staff member to assign.";
            if (empty($title)) $errors[] = "Task title is required.";
            if (strtotime($due_date) < strtotime($start_date)) $errors[] = "Due date cannot be earlier than start date.";

            if (empty($errors)) {
                try {
                    $pdo->beginTransaction();

                    // Get division code
                    $stmt_div = $pdo->prepare("SELECT code FROM corporate_divisions WHERE id = ?");
                    $stmt_div->execute([$division_id]);
                    $div_code = $stmt_div->fetchColumn() ?: 'TSK';

                    $task_ref = generate_task_reference($div_code);

                    $stmt_t = $pdo->prepare("
                        INSERT INTO staff_tasks (task_reference, division_id, project_id, title, description, instructions, priority, status, start_date, due_date, created_by)
                        VALUES (?, ?, ?, ?, ?, ?, ?, 'Assigned', ?, ?, ?)
                    ");
                    $stmt_t->execute([$task_ref, $division_id, $project_id, $title, $description, $instructions, $priority, $start_date, $due_date, $user['id']]);
                    $task_id = $pdo->lastInsertId();

                    // Assign to multiple staff members
                    $stmt_ass = $pdo->prepare("INSERT INTO task_assignees (task_id, user_id, status) VALUES (?, ?, 'Assigned')");
                    $assigned_users = [];

                    foreach ($staff_ids as $staff_id) {
                        $sid = intval($staff_id);
                        if ($sid > 0) {
                            $stmt_ass->execute([$task_id, $sid]);

                            $stmt_u = $pdo->prepare("SELECT id, name, email FROM users WHERE id = ?");
                            $stmt_u->execute([$sid]);
                            $u_info = $stmt_u->fetch(PDO::FETCH_ASSOC);
                            if ($u_info) {
                                $assigned_users[] = $u_info;
                            }
                        }
                    }

                    // Audit Log
                    $log = $pdo->prepare("INSERT INTO audit_logs (username, action, ip_address) VALUES (?, ?, ?)");
                    $log->execute([$user['email'], "Assigned Task [{$task_ref}] to " . count($assigned_users) . " staff member(s)", $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1']);

                    $pdo->commit();

                    // Non-blocking Email & In-App Notification dispatch
                    $task_info = [
                        'id' => $task_id,
                        'task_reference' => $task_ref,
                        'title' => $title,
                        'due_date' => $due_date,
                        'priority' => $priority,
                        'instructions' => $instructions,
                        'description' => $description
                    ];

                    foreach ($assigned_users as $staff_user) {
                        notify_task_assignment($task_info, $staff_user);
                    }

                    $success_msg = "Task [{$task_ref}] assigned successfully to " . count($assigned_users) . " staff member(s). Email notifications dispatched.";
                } catch (Exception $e) {
                    if ($pdo->inTransaction()) $pdo->rollBack();
                    $errors[] = "Failed to assign task: " . $e->getMessage();
                }
            }
        }
    }
}

// Query Tasks List
$tasks = [];
try {
    $stmt_tasks = $pdo->query("
        SELECT t.*, d.name as division_name, d.code as division_code, p.title as project_title,
               (SELECT COUNT(*) FROM task_assignees WHERE task_id = t.id) as total_assignees,
               (SELECT GROUP_CONCAT(u.name SEPARATOR ', ') FROM task_assignees ta JOIN users u ON ta.user_id = u.id WHERE ta.task_id = t.id) as assignee_names
        FROM staff_tasks t
        JOIN corporate_divisions d ON t.division_id = d.id
        LEFT JOIN corporate_projects p ON t.project_id = p.id
        ORDER BY t.id DESC
    ");
    $tasks = $stmt_tasks->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $errors[] = "Failed to load tasks: " . $e->getMessage();
}

// Fetch all staff users with their assigned division IDs for dynamic dropdown filtering
$all_staff_members = [];
try {
    $stmt_s = $pdo->query("
        SELECT u.id, u.name, u.email, u.role, GROUP_CONCAT(ud.division_id) as division_ids
        FROM users u
        LEFT JOIN user_divisions ud ON u.id = ud.user_id
        WHERE u.role IN ('staff', 'hr_manager', 'hr_officer', 'department_manager', 'interviewer') AND u.status = 'active'
        GROUP BY u.id
        ORDER BY u.name ASC
    ");
    $all_staff_members = $stmt_s->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $all_staff_members = [];
}

$page_title = "Task Management — Sarkin Mota HQ";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="flex min-h-screen bg-slate-100 dark:bg-slate-950">
    <?php require_once __DIR__ . '/../includes/admin_sidebar.php'; ?>

    <main class="flex-1 p-6 sm:p-10 max-w-7xl mx-auto">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
            <div>
                <span class="text-xs font-semibold uppercase tracking-widest text-amber-500 mb-1 block">Workforce Command</span>
                <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white">Staff Task Control Center</h1>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Assign tasks across Real Estate, Automobile, and corporate divisions with real-time review tracking.</p>
            </div>
            <button onclick="openTaskModal()" class="px-5 py-2.5 bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold rounded-xl text-xs uppercase tracking-wider transition-all flex items-center space-x-2 shadow-md">
                <i class="bi bi-plus-circle-fill"></i>
                <span>Assign New Task</span>
            </button>
        </div>

        <!-- Alerts -->
        <?php if (!empty($success_msg)): ?>
            <div class="mb-6 p-4 rounded-xl bg-emerald-50 border-l-4 border-emerald-500 text-emerald-800 text-xs font-semibold">
                <?php echo htmlspecialchars($success_msg); ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($errors)): ?>
            <div class="mb-6 p-4 rounded-xl bg-rose-50 border-l-4 border-rose-500 text-rose-800 text-xs font-semibold">
                <?php foreach ($errors as $err) echo "<div>" . htmlspecialchars($err) . "</div>"; ?>
            </div>
        <?php endif; ?>

        <!-- Task List Table -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-slate-950 text-slate-500 dark:text-slate-400 uppercase tracking-wider border-b border-slate-200 dark:border-slate-800">
                            <th class="p-4 font-bold">Task Ref</th>
                            <th class="p-4 font-bold">Task Title</th>
                            <th class="p-4 font-bold">Division / Project</th>
                            <th class="p-4 font-bold">Assigned Staff</th>
                            <th class="p-4 font-bold">Priority</th>
                            <th class="p-4 font-bold">Due Date</th>
                            <th class="p-4 font-bold">Status</th>
                            <th class="p-4 font-bold text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        <?php if (empty($tasks)): ?>
                            <tr>
                                <td colspan="8" class="p-8 text-center text-slate-400 text-xs">No tasks currently created. Click "Assign New Task" to begin.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($tasks as $t): ?>
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/40 transition-colors">
                                    <td class="p-4 font-mono font-bold text-amber-500"><?php echo htmlspecialchars($t['task_reference']); ?></td>
                                    <td class="p-4 font-bold text-slate-900 dark:text-white"><?php echo htmlspecialchars($t['title']); ?></td>
                                    <td class="p-4">
                                        <span class="block font-semibold text-slate-800 dark:text-slate-200"><?php echo htmlspecialchars($t['division_name']); ?></span>
                                        <span class="text-[10px] text-slate-400"><?php echo htmlspecialchars($t['project_title'] ?: 'General Task'); ?></span>
                                    </td>
                                    <td class="p-4 text-slate-600 dark:text-slate-300">
                                        <?php echo htmlspecialchars($t['assignee_names'] ?: 'Unassigned'); ?>
                                    </td>
                                    <td class="p-4">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase <?php echo $t['priority'] === 'Urgent' ? 'bg-rose-500/10 text-rose-500' : ($t['priority'] === 'High' ? 'bg-amber-500/10 text-amber-500' : 'bg-blue-500/10 text-blue-500'); ?>">
                                            <?php echo htmlspecialchars($t['priority']); ?>
                                        </span>
                                    </td>
                                    <td class="p-4 font-mono text-slate-500">
                                        <?php echo $t['due_date'] ? date('M d, Y', strtotime($t['due_date'])) : 'No Limit'; ?>
                                    </td>
                                    <td class="p-4">
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase <?php echo str_contains($t['status'], 'Submitted') || str_contains($t['status'], 'Review') ? 'bg-amber-500/10 text-amber-500 border border-amber-500/20' : (str_contains($t['status'], 'Accepted') || str_contains($t['status'], 'Completed') ? 'bg-emerald-500/10 text-emerald-500 border border-emerald-500/20' : 'bg-slate-500/10 text-slate-400'); ?>">
                                            <?php echo htmlspecialchars($t['status']); ?>
                                        </span>
                                    </td>
                                    <td class="p-4 text-right">
                                        <a href="<?php echo $portal_depth; ?>admin/review-tasks.php?id=<?php echo $t['id']; ?>" class="px-3 py-1.5 bg-slate-100 dark:bg-slate-800 hover:bg-amber-500 hover:text-slate-950 font-bold rounded-lg text-[11px] transition-all inline-block">
                                            Review / Details
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<!-- Modal for Task Creation -->
<div id="taskModal" class="fixed inset-0 bg-slate-950/70 backdrop-blur-sm z-50 flex items-center justify-center hidden p-4">
    <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 max-w-2xl w-full p-6 sm:p-8 shadow-2xl relative max-h-[90vh] overflow-y-auto">
        <button onclick="closeTaskModal()" class="absolute top-6 right-6 text-slate-400 hover:text-slate-600 text-lg">
            <i class="bi bi-x-lg"></i>
        </button>
        <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-6">Assign Staff Task</h2>

        <form action="manage-tasks.php" method="POST" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            <input type="hidden" name="action" value="create_task">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Corporate Division *</label>
                    <select name="division_id" id="task_div_id" required onchange="filterStaffByDivision(this.value)" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500">
                        <option value="">-- Choose Division --</option>
                        <?php foreach ($divisions as $div): ?>
                            <option value="<?php echo $div['id']; ?>"><?php echo htmlspecialchars($div['name']); ?> [<?php echo htmlspecialchars($div['code']); ?>]</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Priority Level</label>
                    <select name="priority" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-xs text-slate-900 dark:text-white focus:outline-none">
                        <option value="Normal">Normal</option>
                        <option value="Low">Low</option>
                        <option value="High">High</option>
                        <option value="Urgent">Urgent</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Assign Staff Members * (Hold Ctrl/Cmd to select multiple)</label>
                <select name="staff_ids[]" id="task_staff_select" multiple required class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl p-3 text-xs text-slate-900 dark:text-white focus:outline-none h-28">
                    <?php foreach ($all_staff_members as $sm): ?>
                        <option value="<?php echo $sm['id']; ?>" data-divisions="<?php echo htmlspecialchars($sm['division_ids'] ?? ''); ?>">
                            <?php echo htmlspecialchars($sm['name']); ?> (<?php echo htmlspecialchars($sm['email']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Task Title *</label>
                <input type="text" name="title" required placeholder="e.g. Conduct Site Inspection on Plot 14 & Upload Photos" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Start Date</label>
                    <input type="date" name="start_date" value="<?php echo date('Y-m-d'); ?>" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2 text-xs text-slate-900 dark:text-white focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Due Date</label>
                    <input type="date" name="due_date" value="<?php echo date('Y-m-d', strtotime('+7 days')); ?>" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2 text-xs text-slate-900 dark:text-white focus:outline-none">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Instructions & Guidelines</label>
                <textarea name="instructions" rows="4" placeholder="Provide step-by-step instructions for staff execution..." class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-xs text-slate-900 dark:text-white focus:outline-none"></textarea>
            </div>

            <div class="pt-4 flex items-center justify-end gap-3">
                <button type="button" onclick="closeTaskModal()" class="px-5 py-2.5 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-700 dark:text-slate-300 font-bold rounded-xl text-xs">Cancel</button>
                <button type="submit" class="px-6 py-2.5 bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold rounded-xl text-xs uppercase tracking-wider shadow-md">Assign Task & Dispatch Email</button>
            </div>
        </form>
    </div>
</div>

<script>
function openTaskModal() {
    document.getElementById('taskModal').classList.remove('hidden');
}

function closeTaskModal() {
    document.getElementById('taskModal').classList.add('hidden');
}

function filterStaffByDivision(divId) {
    const select = document.getElementById('task_staff_select');
    const options = select.options;

    for (let i = 0; i < options.length; i++) {
        const divs = (options[i].getAttribute('data-divisions') || '').split(',');
        if (!divId || divs.includes(divId.toString()) || divs.includes('')) {
            options[i].style.display = 'block';
        } else {
            options[i].style.display = 'none';
        }
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
