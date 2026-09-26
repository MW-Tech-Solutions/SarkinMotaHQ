<?php
/**
 * General Corporate Staff Portal
 * Sarkin Mota HQ — Enterprise Edition
 */
require_once __DIR__ . '/../includes/auth_helper.php';
require_login();

$user = get_logged_in_user();

// Redirect super admins to admin dashboard if they hit staff portal home directly
if ($user['role'] === 'super_admin') {
    header('Location: ../auth/admin-dashboard.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/divisions_helper.php';

$admin_page_title = 'Staff Portal Workspace';
require_once __DIR__ . '/../includes/admin_sidebar.php';

// Fetch staff assigned divisions
$user_divisions = get_user_divisions((int)$user['id']);

// Fetch task metrics
$stmt_metrics = $pdo->prepare("
    SELECT 
        COUNT(*) as total_assigned,
        SUM(CASE WHEN t.status = 'Assigned' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN t.status = 'In Progress' THEN 1 ELSE 0 END) as in_progress,
        SUM(CASE WHEN t.status = 'Submitted' THEN 1 ELSE 0 END) as submitted,
        SUM(CASE WHEN t.status = 'Revision Required' THEN 1 ELSE 0 END) as revision_required,
        SUM(CASE WHEN t.status = 'Accepted' THEN 1 ELSE 0 END) as accepted
    FROM staff_tasks t
    JOIN task_assignees ta ON t.id = ta.task_id
    WHERE ta.user_id = ?
");
$stmt_metrics->execute([$user['id']]);
$m = $stmt_metrics->fetch(PDO::FETCH_ASSOC);
?>

<div class="space-y-6">
    <!-- Header Welcome Banner -->
    <div class="p-6 md:p-8 bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 rounded-3xl text-white shadow-2xl relative overflow-hidden">
        <div class="absolute -right-10 -bottom-10 opacity-10 text-indigo-500">
            <i class="bi bi-person-badge-fill text-[16rem]"></i>
        </div>
        <div class="relative z-10 space-y-3">
            <div class="flex items-center space-x-3">
                <span class="px-3 py-1 bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 rounded-full text-xs font-extrabold uppercase tracking-widest">
                    <i class="bi bi-shield-check mr-1"></i> Staff Portal Workspace
                </span>
                <span class="text-xs text-slate-300 font-semibold">• <?php echo htmlspecialchars($user['name']); ?></span>
            </div>
            <h1 class="text-2xl md:text-4xl font-black text-white tracking-tight">Corporate Staff Console</h1>
            <p class="text-xs md:text-sm text-slate-300 max-w-2xl font-normal leading-relaxed">
                Welcome to your personal corporate staff portal. View your assigned tasks, work reports, division projects, and submission review notifications.
            </p>
        </div>
    </div>

    <!-- Metrics Overview -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-1">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Total Assigned Tasks</span>
            <span class="text-2xl font-black text-slate-900 dark:text-white"><?php echo number_format($m['total_assigned'] ?? 0); ?></span>
            <span class="text-[11px] font-semibold text-indigo-500 block">Work Items</span>
        </div>

        <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-1">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">In Progress</span>
            <span class="text-2xl font-black text-amber-500"><?php echo number_format($m['in_progress'] ?? 0); ?></span>
            <span class="text-[11px] font-semibold text-slate-400 block">Active Work</span>
        </div>

        <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-1">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Revision Required</span>
            <span class="text-2xl font-black text-rose-500"><?php echo number_format($m['revision_required'] ?? 0); ?></span>
            <span class="text-[11px] font-semibold text-rose-500 block">Needs Correction</span>
        </div>

        <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-1">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Accepted Work</span>
            <span class="text-2xl font-black text-emerald-500"><?php echo number_format($m['accepted'] ?? 0); ?></span>
            <span class="text-[11px] font-semibold text-emerald-500 block">Completed</span>
        </div>
    </div>

    <!-- My Divisions & Quick Actions -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Assigned Corporate Divisions -->
        <div class="bg-white dark:bg-slate-900 p-6 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                <h3 class="text-sm font-extrabold text-slate-900 dark:text-white">My Corporate Divisions</h3>
                <span class="text-xs font-bold text-amber-500"><?php echo count($user_divisions); ?> Division(s)</span>
            </div>
            <?php if (empty($user_divisions)): ?>
                <p class="text-xs text-slate-400 italic">You have not been assigned to a corporate division yet. Contact Super Admin for division assignment.</p>
            <?php else: ?>
                <div class="space-y-2">
                    <?php foreach ($user_divisions as $div): ?>
                        <div class="p-3 bg-slate-50 dark:bg-slate-950 rounded-xl flex items-center justify-between border border-slate-200/60 dark:border-slate-800">
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 rounded-lg bg-amber-500/10 text-amber-500 flex items-center justify-center font-bold text-sm">
                                    <i class="bi <?php echo htmlspecialchars($div['icon'] ?: 'bi-building'); ?>"></i>
                                </div>
                                <div>
                                    <span class="text-xs font-bold text-slate-900 dark:text-white block"><?php echo htmlspecialchars($div['name']); ?></span>
                                    <span class="text-[10px] text-slate-400 font-mono"><?php echo htmlspecialchars($div['code']); ?></span>
                                </div>
                            </div>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-emerald-500/10 text-emerald-500 border border-emerald-500/20">Active</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Quick Access -->
        <div class="bg-white dark:bg-slate-900 p-6 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-4">
            <h3 class="text-sm font-extrabold text-slate-900 dark:text-white pb-3 border-b border-slate-100 dark:border-slate-800">Staff Portal Actions</h3>
            <div class="space-y-3">
                <a href="../hr/my-tasks.php" class="w-full py-3 px-4 bg-amber-500 hover:bg-amber-600 text-slate-950 text-xs font-bold rounded-xl transition-colors flex items-center justify-between">
                    <span class="flex items-center space-x-2">
                        <i class="bi bi-check2-square text-base"></i>
                        <span>My Assigned Tasks & Work Submissions</span>
                    </span>
                    <i class="bi bi-arrow-right"></i>
                </a>
                <a href="../sales/index.php" class="w-full py-3 px-4 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 text-xs font-bold rounded-xl transition-colors flex items-center justify-between">
                    <span class="flex items-center space-x-2">
                        <i class="bi bi-shop text-base text-amber-500"></i>
                        <span>Sales Portal (Real Estate & Car Sales)</span>
                    </span>
                    <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
