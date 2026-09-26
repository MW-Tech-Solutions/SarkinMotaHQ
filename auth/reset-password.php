<?php
/**
 * Password Reset Controller - Sarkin Mota HQ
 * Enterprise Edition
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_helper.php';

$errors = [];
$token = sanitize_input($_GET['token'] ?? $_POST['token'] ?? '');
$valid_user = null;

if (!empty($token)) {
    try {
        $token_hash = hash('sha256', $token);
        $stmt = $pdo->prepare("SELECT id, name, email FROM users WHERE password_reset_token = ? AND password_reset_expires > NOW() AND status = 'active'");
        $stmt->execute([$token_hash]);
        $valid_user = $stmt->fetch();
    } catch (Exception $e) {
        error_log("Token Lookup Error: " . $e->getMessage());
    }
}

if (!$valid_user && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $errors[] = "Invalid or expired password reset token. Please request a new password reset.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valid_user) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = "CSRF Token Validation Failed.";
    } else {
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (strlen($password) < 8) {
            $errors[] = "Password must be at least 8 characters long.";
        } elseif (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password)) {
            $errors[] = "Password must contain uppercase, lowercase letters and numbers.";
        }
        if ($password !== $confirm_password) $errors[] = "Passwords do not match.";

        if (empty($errors)) {
            try {
                $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                $up = $pdo->prepare("UPDATE users SET password_hash = ?, password_reset_token = NULL, password_reset_expires = NULL, session_token = NULL WHERE id = ? AND password_reset_token = ? AND password_reset_expires > NOW()");
                $up->execute([$hash, $valid_user['id'], $token_hash]);

                if ($up->rowCount() !== 1) throw new RuntimeException('Reset link already used.');

                // Audit Log
                $log = $pdo->prepare("INSERT INTO audit_logs (username, action, ip_address) VALUES (?, ?, ?)");
                $log->execute([$valid_user['email'], "Successfully Reset Password via Recovery Token", $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1']);

                set_flash_message('success', 'Your password has been successfully updated. Please sign in with your new credentials.');
                redirect('login.php');
            } catch (Exception $e) {
                error_log("Password Reset Execution Error: " . $e->getMessage());
                $errors[] = "Failed to update password. Please try again.";
            }
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<section class="py-24 bg-slate-50 dark:bg-gray-950 min-h-[75vh] flex items-center">
    <div class="max-w-md w-full mx-auto px-4">
        
        <?php if (!empty($errors)): ?>
            <div class="mb-6 border-l-4 border-rose-500 bg-rose-50 dark:bg-rose-950/20 p-4 rounded-r-md">
                <ul class="list-disc pl-5 text-xs text-rose-800 dark:text-rose-300 space-y-1">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($valid_user): ?>
            <div class="bg-white dark:bg-slate-900 border border-slate-200/50 dark:border-slate-800 p-10 rounded-3xl shadow-xl">
                <div class="text-center mb-8">
                    <span class="text-[10px] font-bold text-amber-500 uppercase tracking-widest block mb-2">New Password</span>
                    <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Create New Password</h2>
                    <p class="text-xs text-slate-500 mt-1">Set a new strong password for <?php echo htmlspecialchars($valid_user['email']); ?></p>
                </div>
                
                <form action="reset-password.php" method="POST" class="space-y-4">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                    
                    <div>
                        <label for="password" class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">New Password</label>
                        <input type="password" id="password" name="password" required class="w-full bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg px-4 py-3 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500">
                    </div>

                    <div>
                        <label for="confirm_password" class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required class="w-full bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg px-4 py-3 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500">
                    </div>

                    <button type="submit" class="w-full bg-amber-500 text-slate-950 font-bold uppercase tracking-wider text-xs py-3.5 rounded-lg hover:bg-amber-600 transition-colors shadow-md mt-4">
                        Update Password
                    </button>
                </form>
            </div>
        <?php else: ?>
            <div class="bg-white dark:bg-slate-900 border border-slate-200/50 dark:border-slate-800 p-10 rounded-3xl shadow-xl text-center">
                <i aria-hidden="true" class="bi bi-exclamation-triangle text-amber-500 text-4xl mb-4 block"></i>
                <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-2">Invalid or Expired Token</h2>
                <p class="text-xs text-slate-500 mb-6">The password reset link is invalid or has expired for security reasons.</p>
                <a href="forgot-password.php" class="inline-block bg-amber-500 text-slate-950 font-bold text-xs px-6 py-3 rounded-lg uppercase tracking-wider">Request New Link</a>
            </div>
        <?php endif; ?>

    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

