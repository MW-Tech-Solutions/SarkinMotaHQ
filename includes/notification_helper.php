<?php
/**
 * In-App & Email Notification System
 * Sarkin Mota HQ — Enterprise Edition
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/mail_helper.php';
require_once __DIR__ . '/settings_helper.php';

/**
 * Create an in-app notification for a user
 */
function create_notification(int $user_id, string $title, string $message, ?string $link = null, string $type = 'info'): bool {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, link, type, is_read) VALUES (?, ?, ?, ?, ?, 0)");
        return $stmt->execute([$user_id, $title, $message, $link, $type]);
    } catch (Exception $e) {
        error_log("Notification Create Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get unread notification count for a user
 */
function get_unread_notification_count(int $user_id): int {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$user_id]);
        return (int) $stmt->fetchColumn();
    } catch (Exception $e) {
        return 0;
    }
}

/**
 * Send Task Assignment Email & In-App Notification
 */
function notify_task_assignment(array $task, array $staff_user): void {
    global $pdo;

    $user_id = (int) $staff_user['id'];
    $task_title = $task['title'];
    $task_ref = $task['task_reference'];
    $due_date = $task['due_date'] ? date('M d, Y', strtotime($task['due_date'])) : 'N/A';
    $priority = $task['priority'] ?? 'Normal';
    $task_link = "hr/task-view.php?id=" . $task['id'];

    // In-App Notification
    create_notification(
        $user_id,
        "New Task Assigned: {$task_ref}",
        "You have been assigned to task '{$task_title}' (Priority: {$priority}). Due: {$due_date}.",
        $task_link,
        'task_assigned'
    );

    // Email Dispatch
    $subject = "New Task Assigned: [{$task_ref}] {$task_title}";
    $html_body = "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e2e8f0; rounded-lg: 12px;'>
        <div style='background-color: #0f172a; padding: 20px; text-align: center; border-radius: 8px 8px 0 0;'>
            <h2 style='color: #f59e0b; margin: 0; font-size: 20px;'>Sarkin Mota HQ</h2>
            <p style='color: #94a3b8; margin: 5px 0 0 0; font-size: 12px; text-transform: uppercase; letter-spacing: 1px;'>Corporate Operations Notice</p>
        </div>
        <div style='padding: 24px; background-color: #ffffff; color: #1e293b;'>
            <h3 style='color: #0f172a; margin-top: 0;'>Task Assignment Notification</h3>
            <p>Dear <strong>" . htmlspecialchars($staff_user['name']) . "</strong>,</p>
            <p>You have been assigned a new task within your corporate division portfolio:</p>
            <table style='width: 100%; border-collapse: collapse; margin: 20px 0; font-size: 13px;'>
                <tr><td style='padding: 8px; border-bottom: 1px solid #f1f5f9; font-weight: bold; width: 35%;'>Task Reference:</td><td style='padding: 8px; border-bottom: 1px solid #f1f5f9;'>" . htmlspecialchars($task_ref) . "</td></tr>
                <tr><td style='padding: 8px; border-bottom: 1px solid #f1f5f9; font-weight: bold;'>Task Title:</td><td style='padding: 8px; border-bottom: 1px solid #f1f5f9;'>" . htmlspecialchars($task_title) . "</td></tr>
                <tr><td style='padding: 8px; border-bottom: 1px solid #f1f5f9; font-weight: bold;'>Priority:</td><td style='padding: 8px; border-bottom: 1px solid #f1f5f9;'><span style='color: #d97706; font-weight: bold;'>" . htmlspecialchars($priority) . "</span></td></tr>
                <tr><td style='padding: 8px; border-bottom: 1px solid #f1f5f9; font-weight: bold;'>Due Date:</td><td style='padding: 8px; border-bottom: 1px solid #f1f5f9;'>" . htmlspecialchars($due_date) . "</td></tr>
            </table>
            <p><strong>Instructions:</strong></p>
            <div style='background-color: #f8fafc; padding: 12px; border-left: 4px solid #f59e0b; border-radius: 4px; font-size: 13px;'>
                " . nl2br(htmlspecialchars($task['instructions'] ?? $task['description'] ?? 'Please log in to review instructions.')) . "
            </div>
            <div style='margin-top: 24px; text-align: center;'>
                <a href='" . app_url("hr/task-view.php?id={$task['id']}") . "' style='background-color: #f59e0b; color: #0f172a; padding: 12px 24px; text-decoration: none; font-weight: bold; border-radius: 6px; display: inline-block; font-size: 13px;'>Acknowledge & View Task</a>
            </div>
        </div>
        <div style='background-color: #f8fafc; padding: 12px; text-align: center; font-size: 11px; color: #64748b; border-radius: 0 0 8px 8px;'>
            Sarkin Mota HQ Corporate Portal &bull; ADSYP 32, ATM Road, Yola
        </div>
    </div>";

    send_mail($staff_user['email'], $subject, $html_body, $staff_user['name'], 'task_assigned');
}

/**
 * Send Task Submission Email to Super Admin
 */
function notify_task_submission(array $task, array $staff_user, array $submission): void {
    global $pdo;

    // Notify all Super Admins
    $stmt = $pdo->query("SELECT id, email, name FROM users WHERE role IN ('super_admin', 'admin')");
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $task_ref = $task['task_reference'];
    $task_title = $task['title'];

    foreach ($admins as $admin) {
        // In-App
        create_notification(
            (int)$admin['id'],
            "Task Submitted: {$task_ref}",
            "Staff member " . $staff_user['name'] . " submitted work report for task '{$task_title}'.",
            "admin/review-tasks.php?id=" . $task['id'],
            'task_submitted'
        );

        // Email
        $subject = "Task Submitted for Review: [{$task_ref}] {$task_title}";
        $html_body = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 12px;'>
            <div style='background-color: #0f172a; padding: 20px; text-align: center; border-radius: 8px 8px 0 0;'>
                <h2 style='color: #f59e0b; margin: 0; font-size: 20px;'>Sarkin Mota HQ</h2>
                <p style='color: #94a3b8; margin: 5px 0 0 0; font-size: 12px; text-transform: uppercase;'>Super Admin Review Dispatch</p>
            </div>
            <div style='padding: 24px; background-color: #ffffff; color: #1e293b;'>
                <h3 style='color: #0f172a; margin-top: 0;'>Task Work Report Submitted</h3>
                <p>Hello <strong>" . htmlspecialchars($admin['name']) . "</strong>,</p>
                <p>Staff member <strong>" . htmlspecialchars($staff_user['name']) . "</strong> has submitted a work report requiring your review:</p>
                <div style='background-color: #f8fafc; padding: 16px; border-radius: 8px; font-size: 13px; margin: 16px 0;'>
                    <p><strong>Task:</strong> [" . htmlspecialchars($task_ref) . "] " . htmlspecialchars($task_title) . "</p>
                    <p><strong>Submission Summary:</strong> " . htmlspecialchars($submission['summary']) . "</p>
                </div>
                <div style='margin-top: 24px; text-align: center;'>
                    <a href='" . app_url("admin/review-tasks.php?id={$task['id']}") . "' style='background-color: #0f172a; color: #ffffff; padding: 12px 24px; text-decoration: none; font-weight: bold; border-radius: 6px; display: inline-block; font-size: 13px;'>Review Work Submission</a>
                </div>
            </div>
        </div>";

        send_mail($admin['email'], $subject, $html_body, $admin['name'], 'task_submitted');
    }
}

/**
 * Send Task Review Decision (Acceptance / Rejection with Reason) to Staff
 */
function notify_task_review_decision(array $task, array $staff_user, string $decision, string $reason): void {
    $user_id = (int) $staff_user['id'];
    $task_ref = $task['task_reference'];
    $task_title = $task['title'];
    $is_accepted = ($decision === 'accepted');

    $type = $is_accepted ? 'task_accepted' : 'task_rejected';
    $title = $is_accepted ? "Task Accepted: {$task_ref}" : "Task Requires Revision: {$task_ref}";
    $msg = $is_accepted 
        ? "Your work report for '{$task_title}' was reviewed and ACCEPTED." 
        : "Your submission for '{$task_title}' was REJECTED. Reason: {$reason}";

    create_notification($user_id, $title, $msg, "hr/task-view.php?id=" . $task['id'], $type);

    $subject = $is_accepted ? "Task Accepted: [{$task_ref}] {$task_title}" : "Task Requires Revision: [{$task_ref}] {$task_title}";
    $badge_color = $is_accepted ? "#10b981" : "#ef4444";
    $status_label = $is_accepted ? "ACCEPTED / COMPLETED" : "REJECTED / REVISION REQUIRED";

    $html_body = "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 12px;'>
        <div style='background-color: #0f172a; padding: 20px; text-align: center; border-radius: 8px 8px 0 0;'>
            <h2 style='color: #f59e0b; margin: 0; font-size: 20px;'>Sarkin Mota HQ</h2>
            <p style='color: #94a3b8; margin: 5px 0 0 0; font-size: 12px; text-transform: uppercase;'>Super Admin Review Result</p>
        </div>
        <div style='padding: 24px; background-color: #ffffff; color: #1e293b;'>
            <h3 style='color: #0f172a; margin-top: 0;'>Task Review Determination</h3>
            <p>Dear <strong>" . htmlspecialchars($staff_user['name']) . "</strong>,</p>
            <p>Your work submission for task <strong>[" . htmlspecialchars($task_ref) . "] " . htmlspecialchars($task_title) . "</strong> has been evaluated:</p>
            <div style='background-color: #f8fafc; padding: 16px; border-left: 4px solid {$badge_color}; border-radius: 4px; font-size: 13px; margin: 16px 0;'>
                <p style='margin: 0 0 8px 0; font-weight: bold; color: {$badge_color};'>Decision: {$status_label}</p>
                <p style='margin: 0;'><strong>Review Comments & Reason:</strong><br>" . nl2br(htmlspecialchars($reason)) . "</p>
            </div>
            <div style='margin-top: 24px; text-align: center;'>
                <a href='" . app_url("hr/task-view.php?id={$task['id']}") . "' style='background-color: #f59e0b; color: #0f172a; padding: 12px 24px; text-decoration: none; font-weight: bold; border-radius: 6px; display: inline-block; font-size: 13px;'>View Task Details</a>
            </div>
        </div>
    </div>";

    send_mail($staff_user['email'], $subject, $html_body, $staff_user['name'], $type);
}
