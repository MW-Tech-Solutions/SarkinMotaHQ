<?php
/**
 * Session Termination - Sarkin Mota HQ
 * Enterprise Edition
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    require_once __DIR__ . '/../includes/header.php';
    echo '<section class="max-w-md mx-auto py-24 px-6"><h1 class="text-2xl font-bold mb-6">Sign out of your account?</h1><form method="post"><input type="hidden" name="csrf_token" value="'.generate_csrf_token().'"><button class="bg-amber-500 rounded-lg px-6 py-3">Sign out</button></form></section>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}
start_secure_session();

if (isset($_SESSION['user_email'])) {
    try {
        $log_stmt = $pdo->prepare("INSERT INTO audit_logs (username, action, ip_address) VALUES (?, ?, ?)");
        $log_stmt->execute([$_SESSION['user_email'], "User Signed Out", $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1']);
    } catch (Exception $e) {
        error_log("Logout log error: " . $e->getMessage());
    }
}

revoke_current_session();

start_secure_session();
set_flash_message('success', 'You have been successfully signed out.');
redirect('auth/login.php');
