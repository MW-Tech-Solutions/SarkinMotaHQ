<?php
/**
 * Staff Task Dashboard & Work Assignment Portal
 * Sarkin Mota HQ — Enterprise Edition
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_helper.php';
require_once __DIR__ . '/../includes/rbac_helper.php';
require_once __DIR__ . '/../includes/divisions_helper.php';

require_login();
$user = get_logged_in_user();
$user_id = (int) $user['id'];

// Fetch User's Assigned Divisions
$assigned_divisions = get_user_divisions($user_id);
$user_div_ids = get_user_division_ids($user_id);

// Filter Parameters
$status_filter = sanitize_input($_GET['status'] ?? '');
$priority_filter = sanitize_input($_GET['priority'] ?? '');
$search_query = sanitize_input($_GET['search'] ?? '');

// Build Query for Staff Assigned Tasks (Server-Side Division Restricted)
$tasks = [];
$metrics = [
    'total' => 0,
    'assigned' => 0,
    'in_progress' => 0,
    'submitted' => 0,
    'accepted' => 0,
    'rejected' => 0,
    'overdue' => 0
];

try {
    // Calculate Summary Metrics
    $stmt_m = $pdo->prepare("
        SELECT ta.status, st.due_date 
        FROM task_assignees ta
        JOIN staff_tasks st ON ta.task_id = st.id
        WHERE ta.user_id = ?
    ");
    $stmt_m->execute([$user_id]);
    $all_user_tasks = $stmt_m->fetchAll(PDO::FETCH_ASSOC);

    $today = date('Y-m-d');
    foreach ($all_user_tasks as $ut) {
        $metrics['total']++;
        $st = $ut['status'];
        if ($st === 'Assigned') $metrics['assigned']++;
        if ($st === 'Acknowledged' || $st === 'In Progress') $metrics['in_progress']++;
        if ($st === 'Submitted' || $st === 'Under Review') $metrics['submitted']++;
        if ($st === 'Accepted' || $st === 'Completed') $metrics['accepted']++;
        if ($st === 'Rejected' || $st === 'Revision Required') $metrics['rejected']++;
        
        if ($ut['due_date'] && $ut['due_date'] < $today && !in_array($st, ['Accepted', 'Completed'])) {
            $metrics['overdue']++;
        }
    }

    // Query Filtered Task List
    $sql = "
        SELECT st.*, ta.status as assignee_status, ta.acknowledged_at, ta.submitted_at,
               cd.name as division_name, cd.code as division_code,
               cp.title as project_title, u.name as assigner_name
        FROM task_assignees ta
        JOIN staff_tasks st ON ta.task_id = st.id
        JOIN corporate_divisions cd ON st.division_id = cd.id
        LEFT JOIN corporate_projects cp ON st.project_id = cp.id
        JOIN users u ON st.created_by = u.id
        WHERE ta.user_id = ?
    ";

    $params = [$user_id];

    if (!empty($status_filter)) {
        if ($status_filter === 'overdue') {
            $sql .= " AND st.due_date < CURDATE() AND ta.status NOT IN ('Accepted', 'Completed')";
        } else {
            $sql .= " AND ta.status = ?";
            $params[] = $status_filter;
        }
    }

    if (!empty($priority_filter)) {
        $sql .= " AND st.priority = ?";
        $params[] = $priority_filter;
    }

    if (!empty($search_query)) {
        $sql .= " AND (st.title LIKE ? OR st.task_reference LIKE ?)";
        $params[] = '%' . $search_query . '%';
        $params[] = '%' . $search_query . '%';
    }

    $sql .= " ORDER BY st.id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    error_log("Staff Task Fetch Error: " . $e->getMessage());
}

$page_title = "My Assigned Tasks & Division Work — Sarkin Mota HQ";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="py-10 bg-slate-50 dark:bg-slate-950 min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Welcome Banner & Division Badges -->
        <div class="bg-slate-900 text-white p-6 sm:p-8 rounded-3xl shadow-xl border border-slate-800 mb-8 relative overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-r from-amber-500/10 to-transparent pointer-events-none"></div>
            <div class="relative z-10 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <span class="text-xs font-semibold uppercase tracking-widest text-amber-400 mb-1 block">Staff Work Execution Portal</span>
                    <h1 class="text-2xl sm:text-3xl font-bold">My Assigned Tasks</h1>
                    <p class="text-xs text-slate-400 mt-1">Execute task briefs, record work progress, upload photo evidence, and manage revisions.</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 self-center">Assigned Divisions:</span>
                    <?php if (empty($assigned_divisions)): ?>
                        <span class="px-3 py-1 rounded-full bg-slate-800 text-slate-300 text-xs font-semibold">General Staff</span>
                    <?php else: ?>
                        <?php foreach ($assigned_divisions as $ad): ?>
                            <span class="px-3 py-1 rounded-full bg-amber-500/20 text-amber-400 border border-amber-500/30 text-xs font-bold flex items-center space-x-1">
                                <i class="bi <?php echo htmlspecialchars($ad['icon'] ?: 'bi-building'); ?>"></i>
                                <span><?php echo htmlspecialchars($ad['name']); ?></span>
                            </span>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Metric Cards -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
            <a href="my-tasks.php" class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 text-center shadow-sm hover:border-amber-500 transition-all">
                <span class="block text-2xl font-bold text-slate-900 dark:text-white"><?php echo $metrics['total']; ?></span>
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Tasks</span>
            </a>
            <a href="my-tasks.php?status=Assigned" class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 text-center shadow-sm hover:border-amber-500 transition-all">
                <span class="block text-2xl font-bold text-blue-500"><?php echo $metrics['assigned']; ?></span>
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">New Assigned</span>
            </a>
            <a href="my-tasks.php?status=In Progress" class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 text-center shadow-sm hover:border-amber-500 transition-all">
                <span class="block text-2xl font-bold text-amber-500"><?php echo $metrics['in_progress']; ?></span>
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">In Progress</span>
            </a>
            <a href="my-tasks.php?status=Submitted" class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 text-center shadow-sm hover:border-amber-500 transition-all">
                <span class="block text-2xl font-bold text-indigo-500"><?php echo $metrics['submitted']; ?></span>
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Submitted</span>
            </a>
            <a href="my-tasks.php?status=Accepted" class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 text-center shadow-sm hover:border-amber-500 transition-all">
                <span class="block text-2xl font-bold text-emerald-500"><?php echo $metrics['accepted']; ?></span>
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Accepted</span>
            </a>
            <a href="my-tasks.php?status=Revision Required" class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 text-center shadow-sm hover:border-amber-500 transition-all">
                <span class="block text-2xl font-bold text-rose-500"><?php echo $metrics['rejected']; ?></span>
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Revisions Needed</span>
            </a>
        </div>

        <!-- Task List (Responsive Table for Desktop & Cards for Mobile) -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm">
            <div class="p-6 border-b border-slate-100 dark:border-slate-800 flex flex-col sm:flex-row justify-between items-center gap-4">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">Assigned Task Schedule</h3>
                <form action="my-tasks.php" method="GET" class="flex gap-2 w-full sm:w-auto">
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search_query); ?>" placeholder="Search task title/ref..." class="bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3 py-1.5 text-xs text-slate-900 dark:text-white focus:outline-none">
                    <button type="submit" class="px-4 py-1.5 bg-amber-500 text-slate-950 font-bold rounded-xl text-xs uppercase">Filter</button>
                </form>
            </div>

            <!-- Desktop View Table -->
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-slate-950 text-slate-500 uppercase tracking-wider border-b border-slate-200 dark:border-slate-800">
                            <th class="p-4 font-bold">Task Ref</th>
                            <th class="p-4 font-bold">Task Title</th>
                            <th class="p-4 font-bold">Division</th>
                            <th class="p-4 font-bold">Priority</th>
                            <th class="p-4 font-bold">Due Date</th>
                            <th class="p-4 font-bold">Status</th>
                            <th class="p-4 font-bold text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        <?php if (empty($tasks)): ?>
                            <tr><td colspan="7" class="p-8 text-center text-slate-400">No assigned tasks matching criteria.</td></tr>
                        <?php else: ?>
                            <?php foreach ($tasks as $t): ?>
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/40 transition-colors">
                                    <td class="p-4 font-mono font-bold text-amber-500"><?php echo htmlspecialchars($t['task_reference']); ?></td>
                                    <td class="p-4 font-bold text-slate-900 dark:text-white"><?php echo htmlspecialchars($t['title']); ?></td>
                                    <td class="p-4 text-slate-600 dark:text-slate-300"><?php echo htmlspecialchars($t['division_name']); ?></td>
                                    <td class="p-4">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase <?php echo $t['priority'] === 'Urgent' ? 'bg-rose-500/10 text-rose-500' : 'bg-blue-500/10 text-blue-500'; ?>"><?php echo htmlspecialchars($t['priority']); ?></span>
                                    </td>
                                    <td class="p-4 font-mono text-slate-500"><?php echo $t['due_date'] ? date('M d, Y', strtotime($t['due_date'])) : 'No Limit'; ?></td>
                                    <td class="p-4">
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase <?php echo str_contains($t['assignee_status'], 'Accepted') ? 'bg-emerald-500/10 text-emerald-500' : (str_contains($t['assignee_status'], 'Revision') ? 'bg-rose-500/10 text-rose-500' : 'bg-amber-500/10 text-amber-500'); ?>">
                                            <?php echo htmlspecialchars($t['assignee_status']); ?>
                                        </span>
                                    </td>
                                    <td class="p-4 text-right">
                                        <a href="task-view.php?id=<?php echo $t['id']; ?>" class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold rounded-xl text-xs uppercase tracking-wider transition-all inline-block shadow-sm">
                                            Open Task
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Mobile View Task Cards -->
            <div class="block md:hidden p-4 space-y-4">
                <?php if (empty($tasks)): ?>
                    <p class="text-center text-slate-400 text-xs py-6">No assigned tasks matching criteria.</p>
                <?php else: ?>
                    <?php foreach ($tasks as $t): ?>
                        <div class="bg-slate-50 dark:bg-slate-950 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 space-y-3">
                            <div class="flex justify-between items-start">
                                <span class="font-mono text-xs font-bold text-amber-500"><?php echo htmlspecialchars($t['task_reference']); ?></span>
                                <span class="px-2.5 py-0.5 text-[9px] font-bold uppercase rounded-full <?php echo str_contains($t['assignee_status'], 'Accepted') ? 'bg-emerald-500/10 text-emerald-500' : 'bg-amber-500/10 text-amber-500'; ?>">
                                    <?php echo htmlspecialchars($t['assignee_status']); ?>
                                </span>
                            </div>
                            <h4 class="font-bold text-slate-900 dark:text-white text-sm"><?php echo htmlspecialchars($t['title']); ?></h4>
                            <div class="text-[11px] text-slate-500 flex justify-between">
                                <span>Division: <?php echo htmlspecialchars($t['division_name']); ?></span>
                                <span>Due: <?php echo $t['due_date'] ? date('M d', strtotime($t['due_date'])) : 'N/A'; ?></span>
                            </div>
                            <a href="task-view.php?id=<?php echo $t['id']; ?>" class="w-full py-2.5 bg-amber-500 text-slate-950 font-bold rounded-xl text-xs uppercase text-center block shadow-sm">
                                View & Report Task
                            </a>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
