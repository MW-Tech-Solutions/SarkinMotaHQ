<?php
/**
 * Corporate Sales Portal & Division Sales Console
 * Real Estate & Automobile / Car Sales Divisions
 * Sarkin Mota HQ — Enterprise Edition
 */
require_once __DIR__ . '/../includes/auth_helper.php';
require_login();

$user = get_logged_in_user();
$allowed_roles = ['super_admin', 'admin', 'sales_manager', 'sales_executive', 'staff', 'general_staff'];
if (!in_array($user['role'], $allowed_roles)) {
    set_flash_message('danger', 'Access Denied: You are not authorized to view the Sales Portal.');
    header('Location: ../auth/dashboard.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/divisions_helper.php';

$admin_page_title = 'Sales Portal & Division Hub';
require_once __DIR__ . '/../includes/admin_sidebar.php';

// Fetch divisions accessible to user
$user_div_ids = get_user_division_ids((int)$user['id']);
$user_divisions = get_user_divisions((int)$user['id']);
if ($user['role'] === 'super_admin' || $user['role'] === 'admin') {
    $user_divisions = get_all_divisions(true);
    $user_div_ids = array_column($user_divisions, 'id');
}

$re_div = get_corporate_division_by_slug($pdo, 'real-estate');
$auto_div = get_corporate_division_by_slug($pdo, 'automobile');

// Fetch Metrics for Real Estate and Automobile projects & leads
$re_project_count = 0;
$auto_project_count = 0;
$total_sales_tasks = 0;
$pending_tasks = 0;

if ($re_div) {
    $stmt_re = $pdo->prepare("SELECT COUNT(*) FROM corporate_projects WHERE division_id = ? AND status = 'active'");
    $stmt_re->execute([$re_div['id']]);
    $re_project_count = (int)$stmt_re->fetchColumn();
}

if ($auto_div) {
    $stmt_auto = $pdo->prepare("SELECT COUNT(*) FROM corporate_projects WHERE division_id = ? AND status = 'active'");
    $stmt_auto->execute([$auto_div['id']]);
    $auto_project_count = (int)$stmt_auto->fetchColumn();
}

// Fetch assigned tasks count
if ($user['role'] === 'super_admin' || $user['role'] === 'admin') {
    $stmt_t = $pdo->query("SELECT COUNT(*) FROM staff_tasks");
    $total_sales_tasks = (int)$stmt_t->fetchColumn();
} else {
    $stmt_t = $pdo->prepare("
        SELECT COUNT(*) FROM staff_tasks t 
        JOIN task_assignees ta ON t.id = ta.task_id 
        WHERE ta.user_id = ?
    ");
    $stmt_t->execute([$user['id']]);
    $total_sales_tasks = (int)$stmt_t->fetchColumn();
}

// Fetch recent sales division projects
$sales_projects = [];
if (!empty($user_div_ids)) {
    $in_placeholders = implode(',', array_fill(0, count($user_div_ids), '?'));
    $stmt_p = $pdo->prepare("
        SELECT p.*, d.name as division_name, d.slug as division_slug, d.icon as division_icon
        FROM corporate_projects p
        JOIN corporate_divisions d ON p.division_id = d.id
        WHERE p.division_id IN ($in_placeholders)
        ORDER BY p.id DESC LIMIT 6
    ");
    $stmt_p->execute($user_div_ids);
    $sales_projects = $stmt_p->fetchAll(PDO::FETCH_ASSOC);
}
?>

<div class="space-y-6">
    <!-- Header Banner -->
    <div class="p-6 md:p-8 bg-gradient-to-r from-slate-900 via-amber-950 to-slate-900 rounded-3xl text-white shadow-2xl relative overflow-hidden">
        <div class="absolute -right-10 -bottom-10 opacity-10 text-amber-500">
            <i class="bi bi-cart-check-fill text-[16rem]"></i>
        </div>
        <div class="relative z-10 space-y-3">
            <div class="flex items-center space-x-3">
                <span class="px-3 py-1 bg-amber-500/20 text-amber-400 border border-amber-500/30 rounded-full text-xs font-extrabold uppercase tracking-widest">
                    <i class="bi bi-shop mr-1"></i> Corporate Sales Division
                </span>
                <span class="text-xs text-slate-300 font-semibold">• Real Estate & Automobile Sales Hub</span>
            </div>
            <h1 class="text-2xl md:text-4xl font-black text-white tracking-tight">Sales Operations & Division Console</h1>
            <p class="text-xs md:text-sm text-slate-300 max-w-2xl font-normal leading-relaxed">
                Welcome to your sales workspace. Manage corporate property listings, vehicle sales inventory, client leads, and division sales tasks.
            </p>
        </div>
    </div>

    <!-- Metrics Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Real Estate Projects -->
        <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm flex items-center justify-between">
            <div class="space-y-1">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Real Estate Listings</span>
                <span class="text-2xl font-black text-slate-900 dark:text-white"><?php echo number_format($re_project_count); ?></span>
                <span class="text-[11px] font-semibold text-amber-500 block">Active Property Projects</span>
            </div>
            <div class="w-12 h-12 bg-amber-500/10 text-amber-500 rounded-xl flex items-center justify-center text-xl">
                <i class="bi bi-houses-fill"></i>
            </div>
        </div>

        <!-- Automobile Listings -->
        <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm flex items-center justify-between">
            <div class="space-y-1">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Vehicle Inventory</span>
                <span class="text-2xl font-black text-slate-900 dark:text-white"><?php echo number_format($auto_project_count); ?></span>
                <span class="text-[11px] font-semibold text-emerald-500 block">Automobile Projects</span>
            </div>
            <div class="w-12 h-12 bg-emerald-500/10 text-emerald-500 rounded-xl flex items-center justify-center text-xl">
                <i class="bi bi-car-front-fill"></i>
            </div>
        </div>

        <!-- My Sales Tasks -->
        <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm flex items-center justify-between">
            <div class="space-y-1">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">My Sales Tasks</span>
                <span class="text-2xl font-black text-slate-900 dark:text-white"><?php echo number_format($total_sales_tasks); ?></span>
                <span class="text-[11px] font-semibold text-indigo-500 block">Assigned Work</span>
            </div>
            <div class="w-12 h-12 bg-indigo-500/10 text-indigo-500 rounded-xl flex items-center justify-center text-xl">
                <i class="bi bi-check2-square"></i>
            </div>
        </div>

        <!-- Division Access -->
        <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm flex items-center justify-between">
            <div class="space-y-1">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">My Assigned Divisions</span>
                <span class="text-2xl font-black text-slate-900 dark:text-white"><?php echo count($user_divisions); ?></span>
                <span class="text-[11px] font-semibold text-sky-500 block">Active Access</span>
            </div>
            <div class="w-12 h-12 bg-sky-500/10 text-sky-500 rounded-xl flex items-center justify-center text-xl">
                <i class="bi bi-diagram-3-fill"></i>
            </div>
        </div>
    </div>

    <!-- Quick Action Launchpad -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white dark:bg-slate-900 p-6 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-4">
            <div class="flex items-center space-x-3">
                <div class="p-3 bg-amber-500/10 text-amber-500 rounded-xl text-lg"><i class="bi bi-houses-fill"></i></div>
                <div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Real Estate Sales Hub</h3>
                    <p class="text-xs text-slate-400">Plots, houses, and commercial property listings</p>
                </div>
            </div>
            <a href="../admin/manage-properties.php" class="w-full py-2.5 px-4 bg-amber-500 hover:bg-amber-600 text-slate-950 text-xs font-bold rounded-xl transition-colors flex items-center justify-center space-x-2">
                <i class="bi bi-building"></i>
                <span>Browse Property Listings</span>
            </a>
        </div>

        <div class="bg-white dark:bg-slate-900 p-6 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-4">
            <div class="flex items-center space-x-3">
                <div class="p-3 bg-emerald-500/10 text-emerald-500 rounded-xl text-lg"><i class="bi bi-car-front-fill"></i></div>
                <div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Automobile / Car Sales</h3>
                    <p class="text-xs text-slate-400">Procurements, fleet, and vehicle inventory</p>
                </div>
            </div>
            <a href="../admin/manage-projects.php?division=automobile" class="w-full py-2.5 px-4 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl transition-colors flex items-center justify-center space-x-2">
                <i class="bi bi-car-front-fill"></i>
                <span>Browse Vehicle Inventory</span>
            </a>
        </div>

        <div class="bg-white dark:bg-slate-900 p-6 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-4">
            <div class="flex items-center space-x-3">
                <div class="p-3 bg-indigo-500/10 text-indigo-500 rounded-xl text-lg"><i class="bi bi-check2-square"></i></div>
                <div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Sales Task Console</h3>
                    <p class="text-xs text-slate-400">Inspection tours, client followups, reports</p>
                </div>
            </div>
            <a href="../hr/my-tasks.php" class="w-full py-2.5 px-4 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl transition-colors flex items-center justify-center space-x-2">
                <i class="bi bi-list-task"></i>
                <span>Open My Assigned Tasks</span>
            </a>
        </div>
    </div>

    <!-- Recent Division Projects Grid -->
    <div class="bg-white dark:bg-slate-900 p-6 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-4">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800">
            <div>
                <h2 class="text-base font-extrabold text-slate-900 dark:text-white">Active Corporate Sales Projects</h2>
                <p class="text-xs text-slate-400">Recent Real Estate and Automobile listings assigned across your divisions</p>
            </div>
            <a href="../admin/manage-projects.php" class="px-3 py-1.5 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-bold rounded-lg hover:bg-amber-500 hover:text-slate-950 transition-colors">
                View All Projects <i class="bi bi-arrow-right"></i>
            </a>
        </div>

        <?php if (empty($sales_projects)): ?>
            <div class="p-8 text-center space-y-2">
                <div class="w-12 h-12 bg-slate-100 dark:bg-slate-800 text-slate-400 rounded-full flex items-center justify-center mx-auto text-xl">
                    <i class="bi bi-folder-x"></i>
                </div>
                <p class="text-sm font-semibold text-slate-600 dark:text-slate-400">No active sales projects available for your assigned divisions.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach ($sales_projects as $prj): ?>
                    <div class="p-4 bg-slate-50 dark:bg-slate-950/60 rounded-xl border border-slate-200/60 dark:border-slate-800 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-amber-500/10 text-amber-500 border border-amber-500/20">
                                <i class="bi <?php echo htmlspecialchars($prj['division_icon']); ?> mr-1"></i>
                                <?php echo htmlspecialchars($prj['division_name']); ?>
                            </span>
                            <span class="text-[10px] font-mono font-bold text-slate-400"><?php echo htmlspecialchars($prj['reference_number']); ?></span>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-900 dark:text-white truncate"><?php echo htmlspecialchars($prj['title']); ?></h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 line-clamp-2 mt-1"><?php echo htmlspecialchars($prj['description'] ?: 'No description specified.'); ?></p>
                        </div>
                        <div class="pt-2 border-t border-slate-200/60 dark:border-slate-800 flex items-center justify-between text-xs">
                            <span class="text-[10px] font-bold text-emerald-500 uppercase tracking-wider">Status: <?php echo htmlspecialchars($prj['status']); ?></span>
                            <a href="../admin/manage-projects.php" class="text-amber-500 font-bold hover:underline">Details &rarr;</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
