<?php
/**
 * Staff Account Password Activation Portal (Single-Use Tokenized Invitation)
 * Sarkin Mota HQ — Enterprise Edition
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth_helper.php';

$token = sanitize_input($_GET['token'] ?? '');
$errors = [];
$success = false;

if (empty($token)) {
    die("Invalid activation link. Authorization token is required.");
}

$invitation = null;
$user_account = null;

try {
    $stmt = $pdo->prepare("
        SELECT i.*, u.name, u.email, u.role
        FROM account_invitations i
        JOIN users u ON i.user_id = u.id
        WHERE i.token = ?
    ");
    $stmt->execute([$token]);
    $invitation = $stmt->fetch();

    if (!$invitation) {
        die("Activation invitation not found or link has expired.");
    }

    if ($invitation['is_used'] == 1) {
        die("This account activation invitation has already been used. Please log in using your account credentials.");
    }

    if (strtotime($invitation['expires_at']) < time()) {
        die("This activation link has expired. Please contact HR to reissue an account invitation.");
    }
} catch (Exception $e) {
    error_log("Account Invitation Lookup Error: " . $e->getMessage());
    die("An unexpected error occurred while verifying your activation invitation.");
}

// Handle Password Setting
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_activation'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = "CSRF Token Validation Failed. Please try again.";
    } else {
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (strlen($password) < 8) {
            $errors[] = "Password must be at least 8 characters long.";
        }
        if ($password !== $confirm_password) {
            $errors[] = "Passwords do not match. Please re-enter.";
        }

        if (empty($errors)) {
            try {
                $pdo->beginTransaction();

                // Hash password securely
                $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

                // Update user account password & set status active
                $up_user = $pdo->prepare("UPDATE users SET password_hash = ?, status = 'active' WHERE id = ?");
                $up_user->execute([$hash, $invitation['user_id']]);

                // Mark invitation token as used
                $up_inv = $pdo->prepare("UPDATE account_invitations SET is_used = 1 WHERE id = ?");
                $up_inv->execute([$invitation['id']]);

                // Audit log
                $log = $pdo->prepare("INSERT INTO hr_audit_logs (user_id, username, action, entity_type, entity_id, details, ip_address) VALUES (?, ?, 'Account Activated & Password Set', 'user', ?, 'Staff account activation completed', ?)");
                $log->execute([$invitation['user_id'], $invitation['email'], $invitation['user_id'], $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1']);

                $pdo->commit();
                $success = true;
            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                error_log("Account Activation Error: " . $e->getMessage());
                $errors[] = "Failed to activate account: " . $e->getMessage();
            }
        }
    }
}

$page_title = "Activate Staff Account — " . setting('company_short_name', 'Sarkin Mota HQ');
require_once __DIR__ . '/includes/header.php';
?>

<section class="py-16 md:py-20 bg-slate-50 dark:bg-gray-950 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full mx-auto px-4">
        
        <!-- Header Badge -->
        <div class="text-center mb-8">
            <span class="text-[10px] font-extrabold uppercase tracking-widest text-amber-500 block mb-1">Corporate Staff Onboarding</span>
            <h1 class="text-2xl font-extrabold text-slate-900 dark:text-white">Activate Your Account</h1>
            <p class="text-xs text-slate-500 mt-1"><?php echo htmlspecialchars($invitation['email']); ?></p>
        </div>

        <?php if ($success): ?>
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-8 rounded-3xl text-center space-y-6 shadow-xl">
                <div class="h-16 w-16 rounded-3xl bg-emerald-500/10 text-emerald-500 flex items-center justify-center mx-auto text-3xl">
                    <i aria-hidden="true" class="bi bi-shield-check"></i>
                </div>
                <div>
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-2">Account Activated Successfully!</h2>
                    <p class="text-xs text-slate-500 leading-relaxed">Your password has been set securely. You may now log in to access your corporate staff workspace.</p>
                </div>
                <a href="auth/login.php" class="w-full py-3 bg-amber-500 text-slate-950 font-bold uppercase text-xs tracking-wider rounded-xl hover:bg-amber-600 transition-colors block text-center shadow-md">
                    Proceed to Login
                </a>
            </div>
        <?php else: ?>

            <?php if (!empty($errors)): ?>
                <div class="mb-6 border-l-4 border-rose-500 bg-rose-50 dark:bg-rose-950/20 p-4 rounded-r-md">
                    <ul class="list-disc pl-5 text-xs text-rose-800 dark:text-rose-300 space-y-1">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-8 rounded-3xl shadow-xl space-y-6">
                <div class="border-b border-slate-100 dark:border-slate-800 pb-4">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block mb-1">Employee Account</span>
                    <span class="text-sm font-bold text-slate-900 dark:text-white block"><?php echo htmlspecialchars($invitation['name']); ?></span>
                    <span class="text-xs text-amber-500 font-semibold uppercase tracking-wider block mt-0.5"><?php echo str_replace('_', ' ', htmlspecialchars($invitation['role'])); ?></span>
                </div>

                <form method="POST" action="activate-account.php?token=<?php echo urlencode($token); ?>" class="space-y-4">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    <input type="hidden" name="submit_activation" value="1">

                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">New Account Password</label>
                        <input type="password" name="password" required minlength="8" placeholder="At least 8 characters" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:border-amber-500">
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Confirm Password</label>
                        <input type="password" name="confirm_password" required minlength="8" placeholder="Re-enter password" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:border-amber-500">
                    </div>

                    <button type="submit" class="w-full bg-amber-500 text-slate-950 font-bold uppercase text-xs tracking-wider py-3.5 rounded-xl hover:bg-amber-600 transition-colors flex items-center justify-center space-x-2 shadow-md">
                        <i aria-hidden="true" class="bi bi-key-fill"></i>
                        <span>Set Password & Activate</span>
                    </button>
                </form>
            </div>

        <?php endif; ?>

    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
