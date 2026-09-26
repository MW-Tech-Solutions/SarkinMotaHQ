<?php
/**
 * Applicant Tracking & Candidate Profile Module
 * Sarkin Mota HQ — Enterprise Edition
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_helper.php';
require_once __DIR__ . '/../includes/rbac_helper.php';

require_login();
require_permission('hr.applicants.view');
$user = get_logged_in_user();

$errors = [];
$selected_app_id = intval($_GET['id'] ?? 0);
$status_filter = sanitize_input($_GET['status'] ?? 'all');
$search_query = sanitize_input($_GET['q'] ?? '');

// Handle Applicant Actions (Status Update, Screening Score, Internal Notes, Rejection)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_applicant'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = "CSRF Token Validation Failed.";
    } elseif (!has_permission('hr.applicants.manage')) {
        $errors[] = "Access Denied: You lack permission to manage candidate applications.";
    } else {
        $app_id = intval($_POST['app_id'] ?? 0);
        $new_status = sanitize_input($_POST['status'] ?? 'screening');
        $screening_score = intval($_POST['screening_score'] ?? 0);
        $rejection_reason = sanitize_input($_POST['rejection_reason'] ?? '');
        $internal_notes = sanitize_input($_POST['internal_notes'] ?? '');

        if ($app_id > 0) {
            try {
                $pdo->beginTransaction();

                $up = $pdo->prepare("UPDATE applications SET status = ?, screening_score = ?, rejection_reason = ? WHERE id = ?");
                $up->execute([$new_status, $screening_score, $rejection_reason, $app_id]);

                // Create or Update Candidate Profile internal notes
                $app_info = $pdo->query("SELECT name, email, phone FROM applications WHERE id = {$app_id}")->fetch();
                if ($app_info) {
                    $cp = $pdo->prepare("
                        INSERT INTO candidate_profiles (application_id, name, email, phone, internal_notes)
                        VALUES (?, ?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE internal_notes = VALUES(internal_notes)
                    ");
                    $cp->execute([$app_id, $app_info['name'], $app_info['email'], $app_info['phone'], $internal_notes]);
                }

                // Audit log
                $log = $pdo->prepare("INSERT INTO hr_audit_logs (user_id, username, action, entity_type, entity_id, details, ip_address) VALUES (?, ?, 'Updated Applicant Status', 'application', ?, ?, ?)");
                $log->execute([$user['id'], $user['email'], $app_id, "Status: " . strtoupper($new_status) . " (Score: {$screening_score})", $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1']);

                $pdo->commit();
                set_flash_message('success', "Applicant status updated to " . strtoupper($new_status) . ".");
                redirect("applicants.php?id={$app_id}");
            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                error_log("Update Applicant Error: " . $e->getMessage());
                $errors[] = "Failed to update candidate record:  Please try again or contact support.";
            }
        }
    }
}

// Fetch Candidates & Detailed Selected Candidate Profile
$applicants = [];
$selected_candidate = null;
$interview_history = [];

try {
    $where_clauses = ["1=1"];
    $params = [];

    if ($status_filter !== 'all') {
        $where_clauses[] = "a.status = ?";
        $params[] = $status_filter;
    }

    if (!empty($search_query)) {
        $where_clauses[] = "(a.name LIKE ? OR a.email LIKE ? OR c.title LIKE ?)";
        $params[] = "%{$search_query}%";
        $params[] = "%{$search_query}%";
        $params[] = "%{$search_query}%";
    }

    $where_sql = implode(' AND ', $where_clauses);
    $stmt_apps = $pdo->prepare("
        SELECT a.*, c.title AS job_title, c.department AS department_name
        FROM applications a
        LEFT JOIN careers c ON a.job_id = c.id
        WHERE {$where_sql}
        ORDER BY a.id DESC
    ");
    $stmt_apps->execute($params);
    $applicants = $stmt_apps->fetchAll();

    if ($selected_app_id > 0) {
        $stmt_sel = $pdo->prepare("
            SELECT a.*, c.title AS job_title, c.department AS department_name, cp.internal_notes
            FROM applications a
            LEFT JOIN careers c ON a.job_id = c.id
            LEFT JOIN candidate_profiles cp ON a.id = cp.application_id
            WHERE a.id = ?
        ");
        $stmt_sel->execute([$selected_app_id]);
        $selected_candidate = $stmt_sel->fetch();

        if ($selected_candidate) {
            $stmt_inv = $pdo->prepare("
                SELECT i.*, u.name AS interviewer_name
                FROM interviews i
                LEFT JOIN users u ON i.interviewer_id = u.id
                WHERE i.application_id = ?
                ORDER BY i.round_number ASC
            ");
            $stmt_inv->execute([$selected_app_id]);
            $interview_history = $stmt_inv->fetchAll();
        }
    }
} catch (Exception $e) {
    error_log("Fetch Applicants Error: " . $e->getMessage());
}

$admin_page_title = "Applicant Pipeline & Candidates";
require_once __DIR__ . '/../includes/admin_sidebar.php';
?>

<?php echo display_flash_message(); ?>

<?php if (!empty($errors)): ?>
    <div class="mb-6 border-l-4 border-rose-500 bg-rose-50 dark:bg-rose-950/20 p-4 rounded-r-md">
        <ul class="list-disc pl-5 text-xs text-rose-800 dark:text-rose-300 space-y-1">
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<!-- Filters & Search Bar -->
<div class="bg-white dark:bg-slate-900 border border-slate-200/50 dark:border-slate-800 p-6 rounded-3xl shadow-sm flex flex-wrap items-center justify-between gap-4">
    <div class="flex items-center space-x-2">
        <a href="applicants.php?status=all" class="px-3 py-1.5 rounded-xl text-xs font-bold <?php echo ($status_filter === 'all') ? 'bg-amber-500 text-slate-950' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400'; ?>">All Candidates</a>
        <a href="applicants.php?status=received" class="px-3 py-1.5 rounded-xl text-xs font-bold <?php echo ($status_filter === 'received') ? 'bg-amber-500 text-slate-950' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400'; ?>">Received</a>
        <a href="applicants.php?status=screening" class="px-3 py-1.5 rounded-xl text-xs font-bold <?php echo ($status_filter === 'screening') ? 'bg-amber-500 text-slate-950' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400'; ?>">Screening</a>
        <a href="applicants.php?status=shortlisted" class="px-3 py-1.5 rounded-xl text-xs font-bold <?php echo ($status_filter === 'shortlisted') ? 'bg-amber-500 text-slate-950' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400'; ?>">Shortlisted</a>
        <a href="applicants.php?status=interviewing" class="px-3 py-1.5 rounded-xl text-xs font-bold <?php echo ($status_filter === 'interviewing') ? 'bg-amber-500 text-slate-950' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400'; ?>">Interviewing</a>
        <a href="applicants.php?status=hired" class="px-3 py-1.5 rounded-xl text-xs font-bold <?php echo ($status_filter === 'hired') ? 'bg-amber-500 text-slate-950' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400'; ?>">Hired</a>
    </div>
    
    <form method="GET" action="applicants.php" class="flex items-center space-x-2">
        <input type="text" name="q" value="<?php echo htmlspecialchars($search_query); ?>" placeholder="Search candidate or job..." class="bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3 py-1.5 text-xs focus:outline-none focus:border-amber-500">
        <button type="submit" class="px-3 py-1.5 bg-amber-500 text-slate-950 text-xs font-bold rounded-xl hover:bg-amber-600">Search</button>
    </form>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

    <!-- Candidate Table List (Left 2 Cols) -->
    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white dark:bg-slate-900 border border-slate-200/50 dark:border-slate-800 p-8 rounded-3xl shadow-sm">
            <h3 class="text-base font-bold text-slate-900 dark:text-white mb-6">Candidate Applications Roster</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 text-slate-400 font-semibold uppercase tracking-widest">
                            <th class="py-3 pr-4">Candidate</th>
                            <th class="py-3 px-4">Applied Vacancy</th>
                            <th class="py-3 px-4">Score</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 pl-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                        <?php if (empty($applicants)): ?>
                            <tr>
                                <td colspan="5" class="py-6 text-center text-slate-400">No candidate applications found matching your criteria.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($applicants as $app): ?>
                                <tr class="<?php echo ($selected_app_id === $app['id']) ? 'bg-amber-500/10' : 'hover:bg-slate-50/50 dark:hover:bg-slate-850/50'; ?>">
                                    <td class="py-3 pr-4">
                                        <span class="font-bold text-slate-900 dark:text-white block"><?php echo htmlspecialchars($app['name']); ?></span>
                                        <span class="text-[10px] text-slate-400 block"><?php echo htmlspecialchars($app['email']); ?> • <?php echo htmlspecialchars($app['phone']); ?></span>
                                    </td>
                                    <td class="py-3 px-4 text-slate-400 font-medium"><?php echo htmlspecialchars($app['job_title'] ?? 'General Application'); ?></td>
                                    <td class="py-3 px-4 font-bold text-amber-500"><?php echo intval($app['screening_score']); ?> / 100</td>
                                    <td class="py-3 px-4">
                                        <span class="px-2.5 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                            <?php echo htmlspecialchars($app['status']); ?>
                                        </span>
                                    </td>
                                    <td class="py-3 pl-4 text-right">
                                        <a href="applicants.php?id=<?php echo $app['id']; ?>&status=<?php echo urlencode($status_filter); ?>" class="px-3 py-1 bg-amber-500 text-slate-950 text-[10px] font-bold rounded-lg hover:bg-amber-600 inline-flex items-center space-x-1">
                                            <i aria-hidden="true" class="bi bi-eye-fill"></i>
                                            <span>Inspect Profile</span>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Candidate Profile Inspector (Right 1 Col) -->
    <div class="space-y-6">
        <?php if ($selected_candidate): ?>
            <div class="bg-white dark:bg-slate-900 border border-slate-200/50 dark:border-slate-800 p-8 rounded-3xl shadow-sm space-y-6">
                
                <div class="border-b border-slate-100 dark:border-slate-800 pb-4">
                    <span class="text-[10px] font-bold text-amber-500 uppercase tracking-widest block mb-1">Candidate Profile</span>
                    <h3 class="text-base font-extrabold text-slate-900 dark:text-white"><?php echo htmlspecialchars($selected_candidate['name']); ?></h3>
                    <span class="text-xs text-slate-500 block"><?php echo htmlspecialchars($selected_candidate['email']); ?> • <?php echo htmlspecialchars($selected_candidate['phone']); ?></span>
                    <span class="inline-block mt-2 px-2.5 py-0.5 rounded-full text-[9px] font-bold uppercase bg-amber-500/10 text-amber-500 border border-amber-500/20">
                        Target Position: <?php echo htmlspecialchars($selected_candidate['job_title']); ?>
                    </span>
                </div>

                <!-- Protected CV Stream Button -->
                <div>
                    <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Protected CV / Resume Document</span>
                    <a href="../download.php?type=resume&id=<?php echo $selected_candidate['id']; ?>" target="_blank" class="w-full py-2.5 px-4 bg-slate-100 dark:bg-slate-800 hover:bg-amber-500 hover:text-slate-950 text-slate-800 dark:text-slate-200 text-xs font-bold rounded-xl transition-colors flex items-center justify-center space-x-2">
                        <i aria-hidden="true" class="bi bi-file-earmark-pdf-fill text-amber-500"></i>
                        <span>Download Resume Document</span>
                    </a>
                </div>

                <!-- Cover Letter -->
                <?php if (!empty($selected_candidate['cover_letter'])): ?>
                    <div>
                        <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Candidate Cover Letter</span>
                        <div class="p-4 bg-slate-50 dark:bg-slate-950 rounded-xl text-xs text-slate-600 dark:text-slate-300 leading-relaxed border border-slate-100 dark:border-slate-800">
                            <?php echo nl2br(htmlspecialchars($selected_candidate['cover_letter'])); ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Update Status & Evaluation Form -->
                <form method="POST" action="applicants.php?id=<?php echo $selected_candidate['id']; ?>" class="space-y-4 text-xs border-t border-slate-100 dark:border-slate-800 pt-6">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    <input type="hidden" name="update_applicant" value="1">
                    <input type="hidden" name="app_id" value="<?php echo $selected_candidate['id']; ?>">

                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Pipeline Status</label>
                        <select name="status" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:border-amber-500">
                            <option value="received" <?php echo ($selected_candidate['status'] === 'received') ? 'selected' : ''; ?>>Received</option>
                            <option value="screening" <?php echo ($selected_candidate['status'] === 'screening') ? 'selected' : ''; ?>>Screening</option>
                            <option value="shortlisted" <?php echo ($selected_candidate['status'] === 'shortlisted') ? 'selected' : ''; ?>>Shortlisted</option>
                            <option value="interviewing" <?php echo ($selected_candidate['status'] === 'interviewing') ? 'selected' : ''; ?>>Interviewing</option>
                            <option value="hired" <?php echo ($selected_candidate['status'] === 'hired') ? 'selected' : ''; ?>>Hired</option>
                            <option value="rejected" <?php echo ($selected_candidate['status'] === 'rejected') ? 'selected' : ''; ?>>Rejected</option>
                            <option value="withdrawn" <?php echo ($selected_candidate['status'] === 'withdrawn') ? 'selected' : ''; ?>>Withdrawn</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Screening Score (0 - 100)</label>
                        <input type="number" name="screening_score" min="0" max="100" value="<?php echo intval($selected_candidate['screening_score']); ?>" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:border-amber-500 font-bold text-amber-500">
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Internal Evaluation Notes</label>
                        <textarea name="internal_notes" rows="3" placeholder="Private assessment notes..." class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:border-amber-500"><?php echo htmlspecialchars($selected_candidate['internal_notes'] ?? ''); ?></textarea>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Rejection Reason (if applicable)</label>
                        <input type="text" name="rejection_reason" value="<?php echo htmlspecialchars($selected_candidate['rejection_reason'] ?? ''); ?>" placeholder="Reason for rejection..." class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:border-amber-500">
                    </div>

                    <button type="submit" class="w-full bg-amber-500 text-slate-950 font-bold uppercase text-xs tracking-wider py-3 rounded-xl hover:bg-amber-600 transition-colors flex items-center justify-center space-x-2">
                        <i aria-hidden="true" class="bi bi-save-fill"></i>
                        <span>Save Candidate Evaluation</span>
                    </button>
                </form>

                <!-- Next Action Links -->
                <div class="grid grid-cols-2 gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                    <a href="interviews.php?app_id=<?php echo $selected_candidate['id']; ?>" class="py-2.5 px-3 bg-indigo-50 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-300 font-bold text-[10px] rounded-xl text-center hover:bg-indigo-100 flex items-center justify-center space-x-1">
                        <i aria-hidden="true" class="bi bi-calendar-plus-fill"></i>
                        <span>Schedule Interview</span>
                    </a>
                    <a href="offers.php?app_id=<?php echo $selected_candidate['id']; ?>" class="py-2.5 px-3 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 font-bold text-[10px] rounded-xl text-center hover:bg-emerald-100 flex items-center justify-center space-x-1">
                        <i aria-hidden="true" class="bi bi-file-earmark-check-fill"></i>
                        <span>Prepare Offer</span>
                    </a>
                </div>

            </div>
        <?php else: ?>
            <div class="bg-white dark:bg-slate-900 border border-slate-200/50 dark:border-slate-800 p-8 rounded-3xl text-center space-y-3">
                <i aria-hidden="true" class="bi bi-person-bounding-box text-3xl text-slate-400"></i>
                <h4 class="text-sm font-bold text-slate-900 dark:text-white">No Candidate Selected</h4>
                <p class="text-xs text-slate-500">Click "Inspect Profile" on any candidate in the roster to view their credentials, resume document, interview history, and evaluation notes.</p>
            </div>
        <?php endif; ?>
    </div>

</div>

</main>
</div>
</body>
</html>
