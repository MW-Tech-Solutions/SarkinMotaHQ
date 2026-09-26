<?php
/**
 * Authorized Private File Stream Controller
 * Sarkin Mota HQ
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth_helper.php';
require_once __DIR__ . '/includes/rbac_helper.php';

require_login();
$user = get_logged_in_user();

$type = sanitize_input($_GET['type'] ?? '');
$id = intval($_GET['id'] ?? 0);

if (empty($type) || $id <= 0) {
    http_response_code(400);
    die("Invalid file request parameters.");
}

$filepath = null;
$filename = 'document';
$mime_type = 'application/octet-stream';

try {
    if ($type === 'resume') {
        // Must possess applications.view permission to download candidate resumes
        if (!has_permission('applications.view')) {
            set_flash_message('danger', 'Access Denied: You do not have permission to access private job application resumes.');
            redirect('/SarkinMota/auth/dashboard.php');
        }

        $stmt = $pdo->prepare("SELECT resume_path, name FROM applications WHERE id = ?");
        $stmt->execute([$id]);
        $app = $stmt->fetch();

        if (!$app || empty($app['resume_path'])) {
            http_response_code(404);
            die("Application document not found.");
        }

        $raw_filename = basename($app['resume_path']);
        $filepath = __DIR__ . '/storage/uploads/private/' . $raw_filename;


        $filename = 'Resume_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $app['name']) . '.' . pathinfo($raw_filename, PATHINFO_EXTENSION);
    } elseif ($type === 'task_attachment') {
        $stmt = $pdo->prepare("
            SELECT ta.*, st.title as task_title, st.division_id 
            FROM task_attachments ta
            JOIN staff_tasks st ON ta.task_id = st.id
            WHERE ta.id = ?
        ");
        $stmt->execute([$id]);
        $attachment = $stmt->fetch();

        if (!$attachment) {
            http_response_code(404);
            die("Task attachment file not found.");
        }

        // Authorization check: Super Admin/Admin OR Uploader OR Assigned Staff
        $user_id = (int)$user['id'];
        $user_role = $user['role'] ?? '';
        $is_admin = in_array($user_role, ['super_admin', 'admin']);
        $is_uploader = ((int)$attachment['uploaded_by'] === $user_id);

        $is_assignee = false;
        if (!$is_admin && !$is_uploader) {
            $chk_ass = $pdo->prepare("SELECT COUNT(*) FROM task_assignees WHERE task_id = ? AND user_id = ?");
            $chk_ass->execute([$attachment['task_id'], $user_id]);
            $is_assignee = ((int)$chk_ass->fetchColumn() > 0);
        }

        if (!$is_admin && !$is_uploader && !$is_assignee) {
            set_flash_message('danger', 'Access Denied: You are not authorized to access this private task evidence file.');
            redirect('/SarkinMota/auth/dashboard.php');
        }

        $filepath = $attachment['path'];
        if (!file_exists($filepath)) {
            // Check fallback path
            $filepath = __DIR__ . '/storage/private/task_evidence/' . basename($attachment['stored_name']);
        }

        $filename = $attachment['original_name'];
    } else {
        http_response_code(400);
        die("Unsupported file type requested.");
    }

    if (!file_exists($filepath) || !is_file($filepath)) {
        http_response_code(404);
        die("Requested document file does not exist on disk.");
    }

    // Determine MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $filepath);
    finfo_close($finfo);

    // Audit Log File Access
    $log = $pdo->prepare("INSERT INTO audit_logs (username, action, ip_address) VALUES (?, ?, ?)");
    $log->execute([$user['email'], "Downloaded Private Document ID: {$id} Type: {$type}", $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1']);

    // Clear output buffer and stream file headers
    if (ob_get_level()) {
        ob_end_clean();
    }

    header('Content-Description: File Transfer');
    header('Content-Type: ' . $mime_type);
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Expires: 0');
    header('Cache-Control: private, no-store');
    header('X-Content-Type-Options: nosniff');
    header('Content-Length: ' . filesize($filepath));
    readfile($filepath);
    exit;

} catch (Exception $e) {
    error_log("Private Download Error: " . $e->getMessage());
    http_response_code(500);
    die("Server error streaming private file.");
}

