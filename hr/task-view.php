<?php
/**
 * Staff Task Execution, Acknowledgement & Work Report Portal
 * Sarkin Mota HQ — Enterprise Edition
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_helper.php';
require_once __DIR__ . '/../includes/rbac_helper.php';
require_once __DIR__ . '/../includes/divisions_helper.php';
require_once __DIR__ . '/../includes/notification_helper.php';

require_login();
$user = get_logged_in_user();
$user_id = (int) $user['id'];
$task_id = intval($_GET['id'] ?? 0);

$errors = [];
$success_msg = '';

// Handle Task Actions: Acknowledge, Start Work, Submit Work Report
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = "CSRF Token Validation Failed. Please retry.";
    } else {
        $action = sanitize_input($_POST['action'] ?? '');

        if ($action === 'acknowledge_task') {
            try {
                $stmt = $pdo->prepare("UPDATE task_assignees SET status = 'Acknowledged', acknowledged_at = NOW() WHERE task_id = ? AND user_id = ?");
                $stmt->execute([$task_id, $user_id]);

                $stmt_t = $pdo->prepare("UPDATE staff_tasks SET status = 'Acknowledged' WHERE id = ? AND status = 'Assigned'");
                $stmt_t->execute([$task_id]);

                $success_msg = "Task receipt acknowledged successfully.";
            } catch (Exception $e) {
                $errors[] = "Failed to acknowledge task: " . $e->getMessage();
            }
        } elseif ($action === 'start_work') {
            try {
                $stmt = $pdo->prepare("UPDATE task_assignees SET status = 'In Progress', started_at = NOW() WHERE task_id = ? AND user_id = ?");
                $stmt->execute([$task_id, $user_id]);

                $stmt_t = $pdo->prepare("UPDATE staff_tasks SET status = 'In Progress' WHERE id = ?");
                $stmt_t->execute([$task_id]);

                $success_msg = "Task status updated to In Progress.";
            } catch (Exception $e) {
                $errors[] = "Failed to update status: " . $e->getMessage();
            }
        } elseif ($action === 'submit_report') {
            $summary = sanitize_input($_POST['summary'] ?? '');
            $report = sanitize_input($_POST['report'] ?? '');
            $challenges = sanitize_input($_POST['challenges'] ?? '');

            if (empty($summary)) $errors[] = "Please provide an executive summary of your work.";
            if (empty($report)) $errors[] = "Please enter your detailed work report.";

            if (empty($errors)) {
                try {
                    $pdo->beginTransaction();

                    // Get last submission number for this staff/task
                    $stmt_num = $pdo->prepare("SELECT COUNT(*) FROM task_submissions WHERE task_id = ? AND staff_id = ?");
                    $stmt_num->execute([$task_id, $user_id]);
                    $sub_number = ((int)$stmt_num->fetchColumn()) + 1;

                    // Insert Task Submission
                    $stmt_sub = $pdo->prepare("
                        INSERT INTO task_submissions (task_id, staff_id, submission_number, summary, report, challenges, status, submitted_at)
                        VALUES (?, ?, ?, ?, ?, ?, 'Submitted', NOW())
                    ");
                    $stmt_sub->execute([$task_id, $user_id, $sub_number, $summary, $report, $challenges]);
                    $submission_id = $pdo->lastInsertId();

                    // Handle Optional File / Photo Evidence Uploads
                    if (!empty($_FILES['evidence_files']['name'][0])) {
                        $allowed_extensions = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'xls', 'xlsx'];
                        $allowed_mimes = [
                            'image/jpeg', 'image/png', 'application/pdf', 
                            'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                        ];

                        $target_dir = __DIR__ . '/../storage/private/task_evidence/';
                        if (!file_exists($target_dir)) {
                            mkdir($target_dir, 0755, true);
                        }

                        $file_count = count($_FILES['evidence_files']['name']);
                        for ($i = 0; $i < $file_count; $i++) {
                            if ($_FILES['evidence_files']['error'][$i] === UPLOAD_ERR_OK) {
                                $orig_name = sanitize_input($_FILES['evidence_files']['name'][$i]);
                                $tmp_name = $_FILES['evidence_files']['tmp_name'][$i];
                                $file_size = $_FILES['evidence_files']['size'][$i];
                                $ext = strtolower(pathinfo($orig_name, PATHINFO_EXTENSION));

                                if (!in_array($ext, $allowed_extensions)) {
                                    throw new Exception("File type '.{$ext}' is not permitted. Allowed: JPG, PNG, PDF, DOC, XLS.");
                                }
                                if ($file_size > 15 * 1024 * 1024) {
                                    throw new Exception("File '{$orig_name}' exceeds maximum size limit of 15MB.");
                                }

                                $stored_name = 'EVIDENCE_' . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
                                $dest_path = $target_dir . $stored_name;

                                if (move_uploaded_file($tmp_name, $dest_path)) {
                                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                                    $mime_type = finfo_file($finfo, $dest_path) ?: 'application/octet-stream';
                                    finfo_close($finfo);

                                    $stmt_att = $pdo->prepare("
                                        INSERT INTO task_attachments (task_id, task_submission_id, uploaded_by, original_name, stored_name, mime_type, size, path, attachment_type)
                                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'submission')
                                    ");
                                    $stmt_att->execute([$task_id, $submission_id, $user_id, $orig_name, $stored_name, $mime_type, $file_size, $dest_path]);
                                }
                            }
                        }
                    }

                    // Update Task & Assignee Status
                    $stmt_u_ass = $pdo->prepare("UPDATE task_assignees SET status = 'Submitted', submitted_at = NOW() WHERE task_id = ? AND user_id = ?");
                    $stmt_u_ass->execute([$task_id, $user_id]);

                    $stmt_u_task = $pdo->prepare("UPDATE staff_tasks SET status = 'Submitted' WHERE id = ?");
                    $stmt_u_task->execute([$task_id]);

                    // Audit Log
                    $log = $pdo->prepare("INSERT INTO audit_logs (username, action, ip_address) VALUES (?, ?, ?)");
                    $log->execute([$user['email'], "Submitted Task Report ID: {$task_id} Submission #{$sub_number}", $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1']);

                    $pdo->commit();

                    // Dispatch Email Notification to Super Admin
                    $stmt_tinfo = $pdo->prepare("SELECT * FROM staff_tasks WHERE id = ?");
                    $stmt_tinfo->execute([$task_id]);
                    $task_info = $stmt_tinfo->fetch(PDO::FETCH_ASSOC);

                    $sub_info = ['summary' => $summary, 'report' => $report];
                    notify_task_submission($task_info, $user, $sub_info);

                    $success_msg = "Work report and evidence files submitted successfully. Super Admin has been notified for review.";
                } catch (Exception $e) {
                    if ($pdo->inTransaction()) $pdo->rollBack();
                    $errors[] = "Failed to submit work report: " . $e->getMessage();
                }
            }
        }
    }
}

// Query Task Details & Assignee Status
$task = null;
$assignee_info = null;
$submissions = [];
$attachments = [];
$reviews = [];

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
        // Fetch assignee status for logged in staff
        $stmt_ass = $pdo->prepare("SELECT * FROM task_assignees WHERE task_id = ? AND user_id = ?");
        $stmt_ass->execute([$task_id, $user_id]);
        $assignee_info = $stmt_ass->fetch(PDO::FETCH_ASSOC);

        // Server-side authorization: Super Admin OR Assigned Staff
        $is_super_admin = in_array($user['role'] ?? '', ['super_admin', 'admin']);
        if (!$is_super_admin && !$assignee_info) {
            set_flash_message('danger', 'Access Denied: You are not assigned to this task.');
            redirect('/SarkinMota/hr/my-tasks.php');
        }

        // Submissions
        $stmt_sub = $pdo->prepare("SELECT s.*, u.name as staff_name FROM task_submissions s JOIN users u ON s.staff_id = u.id WHERE s.task_id = ? ORDER BY s.id DESC");
        $stmt_sub->execute([$task_id]);
        $submissions = $stmt_sub->fetchAll(PDO::FETCH_ASSOC);

        // Attachments
        $stmt_att = $pdo->prepare("SELECT * FROM task_attachments WHERE task_id = ? ORDER BY id DESC");
        $stmt_att->execute([$task_id]);
        $attachments = $stmt_att->fetchAll(PDO::FETCH_ASSOC);

        // Reviews
        $stmt_rev = $pdo->prepare("SELECT r.*, u.name as reviewer_name FROM task_reviews r JOIN users u ON r.reviewed_by = u.id WHERE r.task_id = ? ORDER BY r.id DESC");
        $stmt_rev->execute([$task_id]);
        $reviews = $stmt_rev->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    $errors[] = "Failed to load task: " . $e->getMessage();
}

$current_status = $assignee_info['status'] ?? $task['status'] ?? 'Assigned';

$page_title = "Task Brief & Report Submission — Sarkin Mota HQ";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="py-10 bg-slate-50 dark:bg-slate-950 min-h-screen">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Navigation Header -->
        <div class="mb-8">
            <a href="my-tasks.php" class="text-xs text-amber-500 font-bold uppercase tracking-wider flex items-center gap-1.5 mb-2 hover:underline">
                <i class="bi bi-arrow-left"></i> Back to My Tasks List
            </a>
            <?php if ($task): ?>
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                    <div>
                        <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white flex items-center gap-3">
                            <span><?php echo htmlspecialchars($task['title']); ?></span>
                            <span class="text-xs font-mono text-amber-500 bg-amber-500/10 px-3 py-1 rounded-full border border-amber-500/20"><?php echo htmlspecialchars($task['task_reference']); ?></span>
                        </h1>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Division: <?php echo htmlspecialchars($task['division_name']); ?> &bull; Project: <?php echo htmlspecialchars($task['project_title'] ?: 'General Advisory'); ?></p>
                    </div>

                    <!-- Workflow Status Badge -->
                    <span class="px-4 py-2 rounded-full text-xs font-bold uppercase tracking-wider <?php echo str_contains($current_status, 'Accepted') ? 'bg-emerald-500/10 text-emerald-500 border border-emerald-500/20' : (str_contains($current_status, 'Revision') ? 'bg-rose-500/10 text-rose-500 border border-rose-500/20' : 'bg-amber-500/10 text-amber-500 border border-amber-500/20'); ?>">
                        Status: <?php echo htmlspecialchars($current_status); ?>
                    </span>
                </div>
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
                <!-- Main Task Brief & Report Form -->
                <div class="lg:col-span-2 space-y-8">
                    <!-- Task Instructions Box -->
                    <div class="bg-white dark:bg-slate-900 p-6 sm:p-8 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-sm space-y-4">
                        <h3 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider border-b border-slate-100 dark:border-slate-800 pb-3">Assignment Guidelines & Instructions</h3>
                        <div class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed bg-slate-50 dark:bg-slate-950 p-4 rounded-2xl border border-slate-200/50 dark:border-slate-800">
                            <?php echo nl2br(htmlspecialchars($task['instructions'] ?: $task['description'] ?: 'Please review task requirements and submit report upon completion.')); ?>
                        </div>

                        <!-- Acknowledgement / Action Controls -->
                        <?php if ($current_status === 'Assigned'): ?>
                            <form action="task-view.php?id=<?php echo $task['id']; ?>" method="POST" class="pt-4">
                                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                <input type="hidden" name="action" value="acknowledge_task">
                                <button type="submit" class="w-full py-3 bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold rounded-xl text-xs uppercase tracking-wider shadow-md">
                                    <i class="bi bi-check-lg mr-1"></i> Acknowledge Task Receipt
                                </button>
                            </form>
                        <?php elseif ($current_status === 'Acknowledged'): ?>
                            <form action="task-view.php?id=<?php echo $task['id']; ?>" method="POST" class="pt-4">
                                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                <input type="hidden" name="action" value="start_work">
                                <button type="submit" class="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl text-xs uppercase tracking-wider shadow-md">
                                    <i class="bi bi-play-fill mr-1"></i> Mark Work as In Progress
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>

                    <!-- Latest Review Rejection Reason (If Revision Required) -->
                    <?php if (str_contains($current_status, 'Revision') || str_contains($current_status, 'Rejected')): ?>
                        <div class="p-6 rounded-3xl bg-rose-50 dark:bg-rose-950/20 border-l-4 border-rose-500 text-xs space-y-2">
                            <h4 class="font-bold text-rose-800 dark:text-rose-400 uppercase tracking-wider flex items-center gap-1.5">
                                <i class="bi bi-exclamation-octagon-fill text-base"></i> Revision Required By Super Admin
                            </h4>
                            <?php $latest_rev = reset($reviews); ?>
                            <p class="text-rose-900 dark:text-rose-200 leading-relaxed font-semibold">
                                <?php echo htmlspecialchars($latest_rev['reason'] ?? 'Super Admin requested revision on your submission.'); ?>
                            </p>
                        </div>
                    <?php endif; ?>

                    <!-- Work Report Submission Form -->
                    <?php if (in_array($current_status, ['Acknowledged', 'In Progress', 'Revision Required', 'Rejected'])): ?>
                        <div class="bg-white dark:bg-slate-900 p-6 sm:p-8 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-sm space-y-6">
                            <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider border-b border-slate-100 dark:border-slate-800 pb-3">Submit Work Report & Evidence</h3>

                            <form action="task-view.php?id=<?php echo $task['id']; ?>" method="POST" enctype="multipart/form-data" class="space-y-4">
                                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                <input type="hidden" name="action" value="submit_report">

                                <div>
                                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Executive Summary *</label>
                                    <input type="text" name="summary" required placeholder="e.g. Completed Plot 14 survey, verified site boundary pillars" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500">
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Detailed Work Report *</label>
                                    <textarea name="report" rows="5" required placeholder="Describe the execution details, site findings, or results..." class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-3 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500"></textarea>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Challenges or Special Observations</label>
                                    <input type="text" name="challenges" placeholder="e.g. Minor rain delay; site access clear" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-xs text-slate-900 dark:text-white focus:outline-none">
                                </div>

                                <!-- Phone Camera & Multi-File Upload -->
                                <div class="p-4 bg-slate-50 dark:bg-slate-950 rounded-2xl border border-dashed border-slate-300 dark:border-slate-800">
                                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">
                                        Attach Photos or Evidence Files (Optional)
                                    </label>
                                    <input type="file" name="evidence_files[]" multiple accept="image/*,.pdf,.doc,.docx,.xls,.xlsx" class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-amber-500 file:text-slate-950 hover:file:bg-amber-600">
                                    <p class="text-[10px] text-slate-400 mt-2">Take photos directly from phone or select documents (Max 15MB each: JPG, PNG, PDF, DOC, XLS).</p>
                                </div>

                                <button type="submit" class="w-full py-3.5 bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold uppercase tracking-wider text-xs rounded-xl transition-all shadow-md">
                                    Submit Work Report for Super Admin Review
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>

                    <!-- Previous Submissions List -->
                    <?php if (!empty($submissions)): ?>
                        <div class="bg-white dark:bg-slate-900 p-6 sm:p-8 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-sm space-y-4">
                            <h3 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider border-b border-slate-100 dark:border-slate-800 pb-3">My Submission History</h3>
                            <div class="space-y-4">
                                <?php foreach ($submissions as $sub): ?>
                                    <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-xs space-y-2">
                                        <div class="flex justify-between items-center">
                                            <span class="font-bold text-slate-900 dark:text-white">Submission #<?php echo $sub['submission_number']; ?></span>
                                            <span class="text-[10px] text-slate-400"><?php echo date('M d, Y g:i A', strtotime($sub['submitted_at'])); ?></span>
                                        </div>
                                        <p><strong>Summary:</strong> <?php echo htmlspecialchars($sub['summary']); ?></p>
                                        <p class="text-slate-600 dark:text-slate-300"><?php echo nl2br(htmlspecialchars($sub['report'])); ?></p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Sidebar Metadata -->
                <div class="space-y-6">
                    <div class="bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-sm space-y-4 text-xs">
                        <h4 class="font-bold text-slate-900 dark:text-white uppercase tracking-wider border-b border-slate-100 dark:border-slate-800 pb-3">Task Parameters</h4>
                        
                        <div><span class="text-slate-400 block text-[10px] uppercase">Task Reference</span><span class="font-mono font-bold text-amber-500"><?php echo htmlspecialchars($task['task_reference']); ?></span></div>
                        <div><span class="text-slate-400 block text-[10px] uppercase">Corporate Division</span><span class="font-bold text-slate-800 dark:text-slate-200"><?php echo htmlspecialchars($task['division_name']); ?></span></div>
                        <div><span class="text-slate-400 block text-[10px] uppercase">Assigned By</span><span class="font-bold text-slate-800 dark:text-slate-200"><?php echo htmlspecialchars($task['creator_name']); ?></span></div>
                        <div><span class="text-slate-400 block text-[10px] uppercase">Created Date</span><span class="font-bold text-slate-800 dark:text-slate-200"><?php echo date('M d, Y', strtotime($task['created_at'])); ?></span></div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
