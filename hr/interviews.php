<?php
/**
 * Interview Scheduling & Assessment Module
 * Sarkin Mota HQ — Enterprise Edition
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_helper.php';
require_once __DIR__ . '/../includes/rbac_helper.php';

require_login();
require_permission('hr.interviews.manage');
$user = get_logged_in_user();

$errors = [];
$preselect_app_id = intval($_GET['app_id'] ?? 0);

// Handle Interview Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = "CSRF Token Validation Failed.";
    } else {
        // Schedule New Interview
        if (isset($_POST['schedule_interview'])) {
            $application_id = intval($_POST['application_id'] ?? 0);
            $round_number = intval($_POST['round_number'] ?? 1);
            $title = sanitize_input($_POST['title'] ?? 'Technical Assessment');
            $interviewer_id = intval($_POST['interviewer_id'] ?? 0);
            $scheduled_at = sanitize_input($_POST['scheduled_at'] ?? '');
            $location_link = sanitize_input($_POST['location_link'] ?? '');

            if ($application_id <= 0) $errors[] = "Please select a candidate application.";
            if ($interviewer_id <= 0) $errors[] = "Please assign an interviewer.";
            if (empty($scheduled_at)) $errors[] = "Interview date and time are required.";

            if (empty($errors)) {
                try {
                    $pdo->beginTransaction();

                    $stmt = $pdo->prepare("
                        INSERT INTO interviews (application_id, round_number, title, interviewer_id, scheduled_at, location_link, status, decision)
                        VALUES (?, ?, ?, ?, ?, ?, 'scheduled', 'pending')
                    ");
                    $stmt->execute([$application_id, $round_number, $title, $interviewer_id, $scheduled_at, $location_link]);
                    $interview_id = $pdo->lastInsertId();

                    // Update application status to interviewing
                    $up_app = $pdo->prepare("UPDATE applications SET status = 'interviewing' WHERE id = ?");
                    $up_app->execute([$application_id]);

                    $log = $pdo->prepare("INSERT INTO hr_audit_logs (user_id, username, action, entity_type, entity_id, details, ip_address) VALUES (?, ?, 'Scheduled Interview', 'interview', ?, ?, ?)");
                    $log->execute([$user['id'], $user['email'], $interview_id, "Round {$round_number}: {$title}", $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1']);

                    $pdo->commit();
                    set_flash_message('success', 'Interview round scheduled successfully.');
                    redirect('interviews.php');
                } catch (Exception $e) {
                    if ($pdo->inTransaction()) $pdo->rollBack();
                    error_log("Schedule Interview Error: " . $e->getMessage());
                    $errors[] = "Failed to schedule interview:  Please try again or contact support.";
                }
            }
        }

        // Submit Scorecard & Evaluation
        if (isset($_POST['submit_evaluation'])) {
            $interview_id = intval($_POST['interview_id'] ?? 0);
            $score = intval($_POST['score'] ?? 0);
            $feedback = sanitize_input($_POST['feedback'] ?? '');
            $decision = sanitize_input($_POST['decision'] ?? 'pass');

            if ($interview_id > 0) {
                try {
                    $stmt = $pdo->prepare("
                        UPDATE interviews 
                        SET score = ?, feedback = ?, decision = ?, status = 'completed'
                        WHERE id = ?
                    ");
                    $stmt->execute([$score, $feedback, $decision, $interview_id]);

                    $log = $pdo->prepare("INSERT INTO hr_audit_logs (user_id, username, action, entity_type, entity_id, details, ip_address) VALUES (?, ?, 'Evaluated Interview', 'interview', ?, ?, ?)");
                    $log->execute([$user['id'], $user['email'], $interview_id, "Score: {$score}/10 (Decision: {$decision})", $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1']);

                    set_flash_message('success', 'Interview scorecard and feedback submitted successfully.');
                    redirect('interviews.php');
                } catch (Exception $e) {
                    error_log("Submit Evaluation Error: " . $e->getMessage());
                    $errors[] = "Failed to record evaluation:  Please try again or contact support.";
                }
            }
        }
    }
}

// Fetch Scheduled & Completed Interviews
$interviews = [];
$candidates = [];
$interviewers = [];

try {
    // If interviewer role, restrict visibility to assigned interviews
    $where_sql = "1=1";
    $params = [];
    if ($user['role'] === 'interviewer') {
        $where_sql = "i.interviewer_id = ?";
        $params[] = $user['id'];
    }

    $stmt_inv = $pdo->prepare("
        SELECT i.*, a.name AS candidate_name, a.email AS candidate_email, c.title AS job_title, u.name AS interviewer_name
        FROM interviews i
        JOIN applications a ON i.application_id = a.id
        LEFT JOIN careers c ON a.job_id = c.id
        LEFT JOIN users u ON i.interviewer_id = u.id
        WHERE {$where_sql}
        ORDER BY i.scheduled_at DESC
    ");
    $stmt_inv->execute($params);
    $interviews = $stmt_inv->fetchAll();

    $candidates = $pdo->query("SELECT a.id, a.name, a.email, c.title AS job_title FROM applications a LEFT JOIN careers c ON a.job_id = c.id WHERE a.status NOT IN ('rejected', 'withdrawn') ORDER BY a.id DESC")->fetchAll();
    $interviewers = $pdo->query("SELECT id, name, role FROM users WHERE role IN ('super_admin', 'admin', 'hr_manager', 'hr_officer', 'department_manager', 'interviewer', 'staff') ORDER BY name ASC")->fetchAll();
} catch (Exception $e) {
    error_log("Fetch Interviews Error: " . $e->getMessage());
}

$admin_page_title = "Interview Scheduling & Scorecards";
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

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    
    <!-- Scheduled Interviews List (Left 2 Cols) -->
    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white dark:bg-slate-900 border border-slate-200/50 dark:border-slate-800 p-8 rounded-3xl shadow-sm">
            <h3 class="text-base font-bold text-slate-900 dark:text-white mb-6">Scheduled & Completed Candidate Assessments</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 text-slate-400 font-semibold uppercase tracking-widest">
                            <th class="py-3 pr-4">Candidate / Round</th>
                            <th class="py-3 px-4">Interviewer</th>
                            <th class="py-3 px-4">Scheduled Date</th>
                            <th class="py-3 px-4">Scorecard</th>
                            <th class="py-3 px-4">Decision</th>
                            <th class="py-3 pl-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                        <?php if (empty($interviews)): ?>
                            <tr>
                                <td colspan="6" class="py-6 text-center text-slate-400">No interviews scheduled yet. Use the form to schedule a candidate assessment round.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($interviews as $inv): ?>
                                <tr>
                                    <td class="py-3 pr-4">
                                        <span class="font-bold text-slate-900 dark:text-white block"><?php echo htmlspecialchars($inv['candidate_name']); ?></span>
                                        <span class="text-[10px] text-slate-400 block">Round <?php echo intval($inv['round_number']); ?>: <?php echo htmlspecialchars($inv['title']); ?></span>
                                    </td>
                                    <td class="py-3 px-4 text-slate-400 font-medium"><?php echo htmlspecialchars($inv['interviewer_name'] ?? 'Assigned Team'); ?></td>
                                    <td class="py-3 px-4 text-slate-400"><?php echo date('d M Y, H:i', strtotime($inv['scheduled_at'])); ?></td>
                                    <td class="py-3 px-4 font-bold text-amber-500">
                                        <?php echo ($inv['score'] !== null) ? intval($inv['score']) . ' / 10' : '<span class="text-slate-400 text-[10px]">Pending</span>'; ?>
                                    </td>
                                    <td class="py-3 px-4">
                                        <?php
                                        $badge_class = 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300';
                                        if ($inv['decision'] === 'pass') $badge_class = 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300';
                                        if ($inv['decision'] === 'fail') $badge_class = 'bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300';
                                        ?>
                                        <span class="px-2.5 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider <?php echo $badge_class; ?>">
                                            <?php echo htmlspecialchars($inv['decision']); ?>
                                        </span>
                                    </td>
                                    <td class="py-3 pl-4 text-right">
                                        <?php if ($inv['status'] === 'scheduled'): ?>
                                            <button type="button" onclick="document.getElementById('eval-modal-<?php echo $inv['id']; ?>').classList.remove('hidden')" class="px-3 py-1 bg-amber-500 text-slate-950 text-[10px] font-bold rounded-lg hover:bg-amber-600">
                                                Evaluate
                                            </button>
                                        <?php else: ?>
                                            <span class="text-slate-400 text-[10px]">Completed</span>
                                        <?php endif; ?>

                                        <!-- Evaluation Modal -->
                                        <div id="eval-modal-<?php echo $inv['id']; ?>" class="fixed inset-0 bg-slate-950/70 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
                                            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-3xl max-w-md w-full text-left space-y-4 shadow-2xl">
                                                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                                                    <h4 class="font-bold text-slate-900 dark:text-white text-sm">Evaluate Candidate Scorecard</h4>
                                                    <button type="button" onclick="document.getElementById('eval-modal-<?php echo $inv['id']; ?>').classList.add('hidden')" class="text-slate-400 hover:text-white"><i class="bi bi-x-lg"></i></button>
                                                </div>

                                                <form method="POST" action="interviews.php" class="space-y-4">
                                                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                                    <input type="hidden" name="submit_evaluation" value="1">
                                                    <input type="hidden" name="interview_id" value="<?php echo $inv['id']; ?>">

                                                    <div>
                                                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Score Rating (1 - 10)</label>
                                                        <input type="number" name="score" min="1" max="10" value="8" required class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-amber-500 font-bold text-amber-500">
                                                    </div>

                                                    <div>
                                                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Final Decision</label>
                                                        <select name="decision" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-amber-500">
                                                            <option value="pass">Pass (Proceed Next Round / Offer)</option>
                                                            <option value="fail">Fail (Do Not Proceed)</option>
                                                        </select>
                                                    </div>

                                                    <div>
                                                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Detailed Interview Feedback</label>
                                                        <textarea name="feedback" rows="3" required placeholder="Candidate strengths, technical proficiency..." class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-amber-500"></textarea>
                                                    </div>

                                                    <button type="submit" class="w-full bg-amber-500 text-slate-950 font-bold uppercase text-xs tracking-wider py-2.5 rounded-xl hover:bg-amber-600">
                                                        Submit Scorecard
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Schedule Interview Form (Right 1 Col) -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200/50 dark:border-slate-800 p-8 rounded-3xl shadow-sm h-fit space-y-6">
        <h3 class="text-base font-bold text-slate-900 dark:text-white">Schedule Candidate Assessment</h3>
        <form method="POST" action="interviews.php" class="space-y-4 text-xs">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            <input type="hidden" name="schedule_interview" value="1">

            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Candidate Application</label>
                <select name="application_id" required class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:border-amber-500">
                    <option value="">Select Candidate...</option>
                    <?php foreach ($candidates as $cand): ?>
                        <option value="<?php echo $cand['id']; ?>" <?php echo ($preselect_app_id === $cand['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cand['name']); ?> (<?php echo htmlspecialchars($cand['job_title']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Round Number</label>
                    <input type="number" name="round_number" value="1" min="1" max="5" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:border-amber-500 font-bold">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Round Title</label>
                    <input type="text" name="title" value="Technical & Competency Review" required class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:border-amber-500">
                </div>
            </div>

            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Assigned Interviewer</label>
                <select name="interviewer_id" required class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:border-amber-500">
                    <option value="">Select Interviewer...</option>
                    <?php foreach ($interviewers as $itr): ?>
                        <option value="<?php echo $itr['id']; ?>"><?php echo htmlspecialchars($itr['name']); ?> (<?php echo htmlspecialchars($itr['role']); ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Interview Date & Time</label>
                <input type="datetime-local" name="scheduled_at" required class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:border-amber-500">
            </div>

            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Location or Video Meeting Link</label>
                <input type="text" name="location_link" placeholder="e.g. Executive Boardroom / Google Meet URL" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:border-amber-500">
            </div>

            <button type="submit" class="w-full bg-amber-500 text-slate-950 font-bold uppercase text-xs tracking-wider py-3 rounded-xl hover:bg-amber-600 transition-colors flex items-center justify-center space-x-2 shadow-sm">
                <i aria-hidden="true" class="bi bi-calendar-plus-fill"></i>
                <span>Schedule Interview Round</span>
            </button>
        </form>
    </div>

</div>

</main>
</div>
</body>
</html>
