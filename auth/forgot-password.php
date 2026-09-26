<?php
/**
 * Forgot Password Gateway - Sarkin Mota HQ
 * Enterprise Edition
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_helper.php';
require_once __DIR__ . '/../includes/mail_helper.php';

$errors = [];
$success_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = "CSRF Token Validation Failed.";
    } else {
        $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL) ? sanitize_input($_POST['email']) : '';
        
        if (empty($email)) {
            $errors[] = "Please enter a valid email address.";
        } else {
            try {
                $stmt = $pdo->prepare("SELECT id, name FROM users WHERE email = ? AND status = 'active'");
                $stmt->execute([$email]);
                $user = $stmt->fetch();

                if ($user) {
                    $token = bin2hex(random_bytes(32));
                    $token_hash = hash('sha256', $token);
                    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

                    $up = $pdo->prepare("UPDATE users SET password_reset_token = ?, password_reset_expires = ? WHERE id = ?");
                    $up->execute([$token_hash, $expires, $user['id']]);

                    // Audit Log
                    $log = $pdo->prepare("INSERT INTO audit_logs (username, action, ip_address) VALUES (?, ?, ?)");
                    $log->execute([$email, "Requested Password Reset Token", $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1']);

                    $reset_link = rtrim(env('APP_URL', ''), '/') . '/auth/reset-password.php?token=' . $token;

                    $mail_body = "Hello " . htmlspecialchars($user['name']) . ",\n\n" .
                                "A password reset request was initiated for your Sarkin Mota HQ account.\n\n" .
                                "Please click the link below or copy it into your browser to set a new password:\n" .
                                $reset_link . "\n\n" .
                                "This link is valid for 1 hour. If you did not request this password reset, please ignore this email.";

                    send_system_email($email, "Password Reset Request", $mail_body, 'password_reset', $user['name']);
                }

                // Uniform response to prevent email enumeration and token disclosure
                $success_msg = "If an active account exists with that email address, password reset instructions have been sent to your inbox.";
            } catch (Exception $e) {
                error_log("Forgot Password Error: " . $e->getMessage());
                $errors[] = "An unexpected error occurred. Please try again later.";
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

        <?php if (!empty($success_msg)): ?>
            <div class="mb-6 border-l-4 border-emerald-500 bg-emerald-50 dark:bg-emerald-950/20 p-4 rounded-r-md text-xs text-emerald-800 dark:text-emerald-300">
                <?php echo $success_msg; ?>
            </div>
        <?php endif; ?>

        <div class="bg-white dark:bg-slate-900 border border-slate-200/50 dark:border-slate-800 p-10 rounded-3xl shadow-xl">
            <div class="text-center mb-8">
                <span class="text-[10px] font-bold text-amber-500 uppercase tracking-widest block mb-2">Account Recovery</span>
                <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Reset Password</h2>
                <p class="text-xs text-slate-500 mt-2">Enter your registered email address to receive reset instructions.</p>
            </div>
            
            <form action="forgot-password.php" method="POST" class="space-y-6">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                
                <div>
                    <label for="email" class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Registered Email Address</label>
                    <input type="email" id="email" name="email" required placeholder="e.g. user@example.com" class="w-full bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg px-4 py-3 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500">
                </div>

                <button type="submit" class="w-full bg-amber-500 text-slate-950 font-bold uppercase tracking-wider text-xs py-3.5 rounded-lg hover:bg-amber-600 transition-colors shadow-md">
                    Send Reset Link
                </button>
            </form>

            <div class="mt-8 pt-6 border-t border-slate-200/50 dark:border-slate-800/80 text-center text-xs">
                <a href="login.php" class="text-amber-500 font-semibold hover:underline"><i class="bi bi-arrow-left ui-icon" aria-hidden="true"></i> Back to Login</a>
            </div>
        </div>

    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

