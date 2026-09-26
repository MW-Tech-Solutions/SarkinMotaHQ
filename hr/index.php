<?php
/**
 * Dedicated HR & Recruitment Control Dashboard
 * Sarkin Mota HQ — Enterprise Edition
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_helper.php';
require_once __DIR__ . '/../includes/rbac_helper.php';

require_login();
require_permission('hr.view_dashboard');
$user = get_logged_in_user();

// Fetch HR Pipeline Analytics
$stats = [
    'total_vacancies' => 0,
    'published_vacancies' => 0,
    'total_applications' => 0,
    'pending_screening' => 0,
    'interviews_scheduled' => 0,
    'offers_issued' => 0,
    'offers_accepted' => 0,
    'active_onboarding' => 0,
    'total_staff' => 0
];

$recent_activity = [];

try {
    $stats['total_vacancies'] = $pdo->query("SELECT COUNT(*) FROM careers")->fetchColumn();
    $stats['published_vacancies'] = $pdo->query("SELECT COUNT(*) FROM careers WHERE status = 'published' OR status = 'open'")->fetchColumn();
    $stats['total_applications'] = $pdo->query("SELECT COUNT(*) FROM applications")->fetchColumn();
    $stats['pending_screening'] = $pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'received' OR status = 'screening'")->fetchColumn();
    $stats['interviews_scheduled'] = $pdo->query("SELECT COUNT(*) FROM interviews WHERE status = 'scheduled'")->fetchColumn();
    $stats['offers_issued'] = $pdo->query("SELECT COUNT(*) FROM job_offers WHERE status = 'issued'")->fetchColumn();
    $stats['offers_accepted'] = $pdo->query("SELECT COUNT(*) FROM job_offers WHERE status = 'accepted'")->fetchColumn();
    $stats['total_staff'] = $pdo->query("SELECT COUNT(*) FROM employee_profiles WHERE employment_status = 'active'")->fetchColumn();

    $stmt_act = $pdo->query("SELECT * FROM hr_audit_logs ORDER BY id DESC LIMIT 10");
    $recent_activity = $stmt_act->fetchAll();
} catch (Exception $e) {
    error_log("HR Analytics Error: " . $e->getMessage());
}

$admin_page_title = "HR & Recruitment Control Center";
require_once __DIR__ . '/../includes/admin_sidebar.php';
?>

<?php echo display_flash_message(); ?>

<div class="space-y-8">
    
    <!-- Top Welcome Banner -->
    <div class="bg-amber-500/10 border border-amber-500/20 p-6 rounded-3xl flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center space-x-4">
            <div class="h-12 w-12 rounded-2xl bg-amber-500/20 text-amber-500 flex items-center justify-center text-2xl font-bold">
                <i aria-hidden="true" class="bi bi-people-fill"></i>
            </div>
            <div>
                <h2 class="text-base font-extrabold text-slate-900 dark:text-white">HR & Talent Recruitment Control Center</h2>
                <p class="text-xs text-slate-500">Manage vacancies, candidate screening, interview scorecards, job offers, and staff onboarding.</p>
            </div>
        </div>
        <div class="flex items-center space-x-3">
            <a href="vacancies.php?action=create" class="px-4 py-2.5 bg-amber-500 text-slate-950 text-xs font-bold rounded-xl hover:bg-amber-600 transition-colors flex items-center space-x-2 shadow-sm">
                <i aria-hidden="true" class="bi bi-plus-circle-fill"></i>
                <span>Post Vacancy</span>
            </a>
            <a href="directory.php" class="px-4 py-2.5 bg-slate-200 dark:bg-slate-800 text-slate-800 dark:text-slate-200 text-xs font-bold rounded-xl hover:bg-slate-300 transition-colors flex items-center space-x-2">
                <i aria-hidden="true" class="bi bi-person-lines-fill text-amber-500"></i>
                <span>Staff Directory</span>
            </a>
        </div>
    </div>

    <!-- Analytics Stat Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white dark:bg-slate-900 border border-slate-200/50 dark:border-slate-800 p-6 rounded-3xl shadow-sm">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-widest block mb-1">Open Vacancies</span>
            <span class="text-3xl font-extrabold text-slate-900 dark:text-white block"><?php echo number_format($stats['published_vacancies']); ?></span>
            <span class="text-[10px] text-slate-400 block mt-1"><?php echo number_format($stats['total_vacancies']); ?> Total Vacancies</span>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200/50 dark:border-slate-800 p-6 rounded-3xl shadow-sm">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-widest block mb-1">Candidates & Applicants</span>
            <span class="text-3xl font-extrabold text-amber-500 block"><?php echo number_format($stats['total_applications']); ?></span>
            <span class="text-[10px] text-amber-500/80 font-bold block mt-1"><?php echo number_format($stats['pending_screening']); ?> Pending Screening</span>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200/50 dark:border-slate-800 p-6 rounded-3xl shadow-sm">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-widest block mb-1">Scheduled Interviews</span>
            <span class="text-3xl font-extrabold text-amber-500 block"><?php echo number_format($stats['interviews_scheduled']); ?></span>
            <span class="text-[10px] text-slate-400 block mt-1">Active Evaluation Rounds</span>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200/50 dark:border-slate-800 p-6 rounded-3xl shadow-sm">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-widest block mb-1">Active Staff Members</span>
            <span class="text-3xl font-extrabold text-emerald-500 block"><?php echo number_format($stats['total_staff']); ?></span>
            <span class="text-[10px] text-slate-400 block mt-1"><?php echo number_format($stats['offers_accepted']); ?> Onboarding Completed</span>
        </div>
    </div>

    <!-- Quick Action Links Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <a href="vacancies.php" class="bg-white dark:bg-slate-900 border border-slate-200/50 dark:border-slate-800 p-6 rounded-3xl shadow-sm hover:border-amber-500/40 transition-all flex items-center space-x-4">
            <div class="h-12 w-12 rounded-2xl bg-amber-500/10 text-amber-500 flex items-center justify-center text-xl">
                <i aria-hidden="true" class="bi bi-briefcase-fill text-amber-500"></i>
            </div>
            <div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Vacancy Workflow</h3>
                <p class="text-xs text-slate-500">Create, approve & publish job listings.</p>
            </div>
        </a>

        <a href="applicants.php" class="bg-white dark:bg-slate-900 border border-slate-200/50 dark:border-slate-800 p-6 rounded-3xl shadow-sm hover:border-amber-500/40 transition-all flex items-center space-x-4">
            <div class="h-12 w-12 rounded-2xl bg-amber-500/10 text-amber-500 flex items-center justify-center text-xl">
                <i aria-hidden="true" class="bi bi-person-lines-fill text-amber-500"></i>
            </div>
            <div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Candidate Pipeline</h3>
                <p class="text-xs text-slate-500">Screen CVs, shortlist & evaluate applicants.</p>
            </div>
        </a>

        <a href="offers.php" class="bg-white dark:bg-slate-900 border border-slate-200/50 dark:border-slate-800 p-6 rounded-3xl shadow-sm hover:border-amber-500/40 transition-all flex items-center space-x-4">
            <div class="h-12 w-12 rounded-2xl bg-amber-500/10 text-amber-500 flex items-center justify-center text-xl">
                <i aria-hidden="true" class="bi bi-file-earmark-check-fill text-amber-500"></i>
            </div>
            <div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Offers & Onboarding</h3>
                <p class="text-xs text-slate-500">Issue job offers & provision staff accounts.</p>
            </div>
        </a>
    </div>

    <!-- Recent HR Activity Stream -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200/50 dark:border-slate-800 p-8 rounded-3xl shadow-sm">
        <h3 class="text-base font-bold text-slate-900 dark:text-white mb-6">Recent Recruitment & HR Activity</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-800 text-slate-400 font-semibold uppercase tracking-widest">
                        <th class="py-3 pr-4">User</th>
                        <th class="py-3 px-4">Action Event</th>
                        <th class="py-3 px-4">Entity Type</th>
                        <th class="py-3 px-4">Timestamp</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                    <?php if (empty($recent_activity)): ?>
                        <tr>
                            <td colspan="4" class="py-6 text-center text-slate-400">No HR recruitment activity recorded yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recent_activity as $act): ?>
                            <tr>
                                <td class="py-3 pr-4 font-bold text-slate-900 dark:text-white"><?php echo htmlspecialchars($act['username']); ?></td>
                                <td class="py-3 px-4 text-slate-700 dark:text-slate-300 font-medium"><?php echo htmlspecialchars($act['action']); ?></td>
                                <td class="py-3 px-4 font-mono uppercase text-amber-500"><?php echo htmlspecialchars($act['entity_type']); ?></td>
                                <td class="py-3 px-4 text-slate-400"><?php echo date('d M Y, H:i:s', strtotime($act['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

</main>
</div>
</body>
</html>
