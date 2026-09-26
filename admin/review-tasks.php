<?php
/**
 * Super Admin Task Review & Quality Control Interface
 * Sarkin Mota HQ — Enterprise Edition
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_helper.php';
require_once __DIR__ . '/../includes/rbac_helper.php';
require_once __DIR__ . '/../includes/divisions_helper.php';
require_once __DIR__ . '/../includes/notification_helper.php';

require_login();
$user = get_logged_in_user();

if (!has_permission('review_task_report') && !has_permission('approve_task_report')) {
    set_flash_message('danger', 'Access Denied: You do not have permission to review task reports.');
    redirect('/SarkinMota/auth/dashboard.php');
}

$task_id = intval($_GET['id'] ?? 0);
$errors = [];
$success_msg = '';

// Handle Review Determination (Accept vs Reject with Mandatory Reason)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = "CSRF Token Validation Failed. Please retry.";
    } else {
        $action = sanitize_input($_POST['action'] ?? '');
        $submission_id = intval($_POST['submission_id'] ?? 0);
        $decision = sanitize_input($_POST['decision'] ?? '');
        $reason = trim(sanitize_input($_POST['reason'] ?? ''));

        if ($submission_id <= 0 || !in_array($decision, ['accepted', 'rejected', 'revision_required'])) {
            $errors[] = "Invalid review determination parameters.";
        }

        // MANDATORY Rejection Reason Check
        if (($decision === 'rejected' || $decision === 'revision_required') && empty($reason)) {
            $errors[] = "Mandatory Requirement: You must enter a clear reason for rejection/revision required.";
        }

        if (empty($errors)) {
            try {
                $pdo->beginTransaction();

                // 1. Fetch submission details
                $stmt_s = $pdo->prepare("SELECT * FROM task_submissions WHERE id = ?");
                $stmt_s->execute([$submission_id]);
                $submission = $stmt_s->fetch(PDO::FETCH_ASSOC);

                if (!$submission) {
                    throw new Exception("Task submission record not found.");
                }

                $tid = (int) $submission['task_id'];
                $staff_id = (int) $submission['staff_id'];

                // 2. Record Review History
                $stmt_rev = $pdo->prepare("
                    INSERT INTO task_reviews (task_submission_id, task_id, reviewed_by, decision, reason, comments)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt_rev->execute([$submission_id, $tid, $user['id'], $decision, $reason, $reason]);

                // 3. Update Submission & Task Statuses
                if ($decision === 'accepted') {
                    $new_sub_status = 'Accepted';
                    $new_task_status = 'Accepted';
                } else {
                    $new_sub_status = 'Revision Required';
                    $new_task_status = 'Revision Required';
                }

                $stmt_u_sub = $pdo->prepare("UPDATE task_submissions SET status = ? WHERE id = ?");
                $stmt_u_sub->execute([$new_sub_status, $submission_id]);

                $stmt_u_task = $pdo->prepare("UPDATE staff_tasks SET status = ? WHERE id = ?");
                $stmt_u_task->execute([$new_task_status, $tid]);

                $stmt_u_ass = $pdo->prepare("UPDATE task_assignees SET status = ?, reviewed_at = NOW() WHERE task_id = ? AND user_id = ?");
                $stmt_u_ass->execute([$new_task_status, $tid, $staff_id]);

                // Audit Log
                $log = $pdo->prepare("INSERT INTO audit_logs (username, action, ip_address) VALUES (?, ?, ?)");
                $log->execute([$user['email'], "Reviewed Task ID {$tid} [Decision: {$decision}]", $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1']);

                $pdo->commit();

                // Fetch Task & Staff User details for Notifications
                $stmt_tinfo = $pdo->prepare("SELECT * FROM staff_tasks WHERE id = ?");
                $stmt_tinfo->execute([$tid]);
                $task_info = $stmt_tinfo->fetch(PDO::FETCH_ASSOC);

                $stmt_uinfo = $pdo->prepare("SELECT id, name, email FROM users WHERE id = ?");
                $stmt_uinfo->execute([$staff_id]);
                $staff_info = $stmt_uinfo->fetch(PDO::FETCH_ASSOC);

                if ($task_info && $staff_info) {
                    notify_task_review_decision($task_info, $staff_info, $decision, $reason ?: 'Work verified and accepted.');
                }

                $success_msg = $is_accepted ? "Task submission marked as ACCEPTED." : "Task marked as REVISION REQUIRED. Rejection notice emailed to staff.";
            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $errors[] = "Failed to process review: " . $e->getMessage();
            }
        }
    }
}

// Fetch Task, Submissions, Attachments, and Review History
$task = null;
$submissions = [];
$attachments = [];
$reviews = [];

if ($task_id > 0) {
    try {
        $stmt_t = $pdo->prepare("
            SELECT t.*, d.name as division_name, p.title as project_title, u.name as creator_name
            FROM staff_tasks t
            JOIN corporate_divisions d ON t.division_id = d.id
            LEFT JOIN corporate_projects p ON t.project_id = p.id
            JOIN users u ON t.created_by = u.id
            WHERE t.id = ?
        ");
        $stmt_t->execute([$task_id]);
        $task = $stmt_t->fetch(PDO::FETCH_ASSOC);

        if ($task) {
            // Fetch Submissions
            $stmt_sub = $pdo->prepare("
                SELECT s.*, u.name as staff_name, u.email as staff_email
                FROM task_submissions s
                JOIN users u ON s.staff_id = u.id
                WHERE s.task_id = ?
                ORDER BY s.id DESC
            ");
            $stmt_sub->execute([$task_id]);
            $submissions = $stmt_sub->fetchAll(PDO::FETCH_ASSOC);

            // Fetch Evidence Attachments
            $stmt_att = $pdo->prepare("
                SELECT * FROM task_attachments WHERE task_id = ? ORDER BY id DESC
            ");
            $stmt_att->execute([$task_id]);
            $attachments = $stmt_att->fetchAll(PDO::FETCH_ASSOC);

            // Fetch Review Log History
            $stmt_rev_log = $pdo->prepare("
                SELECT r.*, u.name as reviewer_name
                FROM task_reviews r
                JOIN users u ON r.reviewed_by = u.id
                WHERE r.task_id = ?
                ORDER BY r.id DESC
            ");
            $stmt_rev_log->execute([$task_id]);
            $reviews = $stmt_rev_log->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (Exception $e) {
        $errors[] = "Failed to load task review details: " . $e->getMessage();
    }
}

$page_title = "Review Task Submission — Sarkin Mota HQ";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="flex min-h-screen bg-slate-100 dark:bg-slate-950">
    <?php require_once __DIR__ . '/../includes/admin_sidebar.php'; ?>

    <main class="flex-1 p-6 sm:p-10 max-w-7xl mx-auto">
        <!-- Breadcrumb & Header -->
        <div class="mb-8">
            <a href="/SarkinMota/admin/manage-tasks.php" class="text-xs text-amber-500 font-bold uppercase tracking-wider flex items-center gap-1.5 mb-2 hover:underline">
                <i class="bi bi-arrow-left"></i> Back to Task Control Center
            </a>
            <?php if ($task): ?>
                <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white flex items-center gap-3">
                    <span><?php echo htmlspecialchars($task['title']); ?></span>
                    <span class="text-sm font-mono text-amber-500 bg-amber-500/10 px-3 py-1 rounded-full border border-amber-500/20"><?php echo htmlspecialchars($task['task_reference']); ?></span>
                </h1>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Division: <?php echo htmlspecialchars($task['division_name']); ?> &bull; Project: <?php echo htmlspecialchars($task['project_title'] ?: 'General'); ?></p>
            <?php else: ?>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Task Review</h1>
            <?php endif; ?>
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

        <?php if ($task): ?>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Task Details & Submission Panel -->
                <div class="lg:col-span-2 space-y-8">
                    <!-- Task Brief -->
                    <div class="bg-white dark:bg-slate-900 p-6 sm:p-8 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm space-y-4">
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider border-b border-slate-100 dark:border-slate-800 pb-3">Original Task Brief & Instructions</h3>
                        <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed"><?php echo nl2br(htmlspecialchars($task['instructions'] ?: $task['description'] ?: 'No detailed instructions written.')); ?></p>
                        
                        <div class="grid grid-cols-3 gap-3 pt-4 border-t border-slate-100 dark:border-slate-800 text-xs">
                            <div><span class="text-slate-400 block text-[10px] uppercase">Priority</span><span class="font-bold text-amber-500"><?php echo htmlspecialchars($task['priority']); ?></span></div>
                            <div><span class="text-slate-400 block text-[10px] uppercase">Start Date</span><span class="font-bold text-slate-700 dark:text-slate-300"><?php echo htmlspecialchars($task['start_date'] ?: 'N/A'); ?></span></div>
                            <div><span class="text-slate-400 block text-[10px] uppercase">Due Date</span><span class="font-bold text-slate-700 dark:text-slate-300"><?php echo htmlspecialchars($task['due_date'] ?: 'N/A'); ?></span></div>
                        </div>
                    </div>

                    <!-- Staff Submissions List -->
                    <div class="bg-white dark:bg-slate-900 p-6 sm:p-8 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm space-y-6">
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider border-b border-slate-100 dark:border-slate-800 pb-3">Staff Submissions & Work Reports</h3>

                        <?php if (empty($submissions)): ?>
                            <p class="text-xs text-slate-400 italic py-4">No work reports submitted yet by assigned staff.</p>
                        <?php else: ?>
                            <?php foreach ($submissions as $sub): ?>
                                <div class="p-6 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 space-y-4">
                                    <div class="flex justify-between items-center">
                                        <div>
                                            <h4 class="font-bold text-slate-900 dark:text-white text-xs"><?php echo htmlspecialchars($sub['staff_name']); ?></h4>
                                            <span class="text-[10px] text-slate-400">Submission #<?php echo $sub['submission_number']; ?> &bull; <?php echo date('M d, Y g:i A', strtotime($sub['submitted_at'])); ?></span>
                                        </div>
                                        <span class="px-2.5 py-1 text-[10px] font-bold uppercase rounded-full <?php echo $sub['status'] === 'Accepted' ? 'bg-emerald-500/10 text-emerald-500' : 'bg-amber-500/10 text-amber-500'; ?>">
                                            <?php echo htmlspecialchars($sub['status']); ?>
                                        </span>
                                    </div>

                                    <div class="text-xs space-y-2">
                                        <p><strong>Executive Summary:</strong> <?php echo htmlspecialchars($sub['summary']); ?></p>
                                        <div class="bg-white dark:bg-slate-900 p-3 rounded-lg border border-slate-200 dark:border-slate-800">
                                            <strong>Detailed Work Report:</strong><br>
                                            <p class="mt-1 leading-relaxed text-slate-600 dark:text-slate-300"><?php echo nl2br(htmlspecialchars($sub['report'])); ?></p>
                                        </div>
                                        <?php if (!empty($sub['challenges'])): ?>
                                            <p class="text-rose-500"><strong>Challenges Encountered:</strong> <?php echo htmlspecialchars($sub['challenges']); ?></p>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Uploaded Evidence Files -->
                                    <div class="pt-3 border-t border-slate-200 dark:border-slate-800">
                                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-2">Uploaded Evidence Attachments:</span>
                                        <div class="flex flex-wrap gap-2">
                                            <?php 
                                            $sub_files = array_filter($attachments, fn($a) => (int)$a['task_submission_id'] === (int)$sub['id']);
                                            ?>
                                            <?php if (empty($sub_files)): ?>
                                                <span class="text-[11px] text-slate-400 italic">No files attached to this submission.</span>
                                            <?php else: ?>
                                                <?php foreach ($sub_files as $f): ?>
                                                    <a href="/SarkinMota/download.php?type=task_attachment&id=<?php echo $f['id']; ?>" class="px-3 py-1.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg text-xs font-semibold text-amber-500 hover:bg-amber-500 hover:text-slate-950 transition-all flex items-center space-x-1.5">
                                                        <i class="bi bi-paperclip"></i>
                                                        <span><?php echo htmlspecialchars($f['original_name']); ?></span>
                                                    </a>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- Review Determination Form (Super Admin) -->
                                    <?php if ($sub['status'] === 'Submitted' || $sub['status'] === 'Under Review'): ?>
                                        <form action="review-tasks.php?id=<?php echo $task['id']; ?>" method="POST" class="pt-4 border-t border-slate-200 dark:border-slate-800 space-y-3">
                                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                            <input type="hidden" name="submission_id" value="<?php echo $sub['id']; ?>">

                                            <label class="block text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">Super Admin Quality Review Determination</label>
                                            
                                            <textarea name="reason" rows="3" placeholder="Enter review comments or mandatory rejection reason..." class="w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-3 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500"></textarea>

                                            <div class="flex gap-3">
                                                <button type="submit" name="decision" value="accepted" class="flex-1 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs uppercase tracking-wider transition-all shadow-sm">
                                                    <i class="bi bi-check-circle-fill mr-1"></i> Accept & Mark Complete
                                                </button>
                                                <button type="submit" name="decision" value="revision_required" class="flex-1 py-2.5 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-xl text-xs uppercase tracking-wider transition-all shadow-sm">
                                                    <i class="bi bi-x-circle-fill mr-1"></i> Reject & Require Revision
                                                </button>
                                            </div>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Sidebar: Audit & Review History -->
                <div class="space-y-6">
                    <div class="bg-white dark:bg-slate-900 p-6 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm space-y-4">
                        <h3 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider border-b border-slate-100 dark:border-slate-800 pb-3">Review History & Audit Trail</h3>

                        <?php if (empty($reviews)): ?>
                            <p class="text-xs text-slate-400 italic">No previous review determinations recorded.</p>
                        <?php else: ?>
                            <div class="space-y-3">
                                <?php foreach ($reviews as $r): ?>
                                    <div class="p-3 rounded-lg bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-xs">
                                        <div class="flex justify-between items-center mb-1">
                                            <span class="font-bold text-slate-900 dark:text-white"><?php echo htmlspecialchars($r['reviewer_name']); ?></span>
                                            <span class="text-[10px] font-mono font-bold uppercase <?php echo $r['decision'] === 'accepted' ? 'text-emerald-500' : 'text-rose-500'; ?>"><?php echo htmlspecialchars($r['decision']); ?></span>
                                        </div>
                                        <p class="text-slate-500 text-[11px] leading-relaxed"><?php echo htmlspecialchars($r['reason']); ?></p>
                                        <span class="text-[9px] text-slate-400 block mt-1"><?php echo date('M d, Y g:i A', strtotime($r['reviewed_at'])); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
