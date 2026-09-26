<?php
/**
 * Security Registration Portal - Sarkin Mota HQ
 * Enterprise Edition
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_helper.php';

$errors = [];
if (is_logged_in()) {
    redirect('/SarkinMota/auth/dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = "CSRF Token Validation Failed. Please retry.";
    } else {
        $name = sanitize_input($_POST['name'] ?? '');
        $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL) ? sanitize_input($_POST['email']) : '';
        $role = sanitize_input($_POST['role'] ?? 'client');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        $allowed_roles = ['client', 'tenant', 'landlord'];
        if (!in_array($role, $allowed_roles)) {
            $role = 'client';
        }

        if (empty($name)) $errors[] = "Please enter your full name.";
        if (empty($email)) $errors[] = "Please enter a valid email address.";
        if (strlen($password) < 8) $errors[] = "Password must be at least 8 characters long.";
        if ($password !== $confirm_password) $errors[] = "Passwords do not match.";

        if (empty($errors)) {
            try {
                $pdo->beginTransaction();

                // Check existing email
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    $errors[] = "An account with this email address already exists.";
                    $pdo->rollBack();
                } else {
                    // Hash password with high-cost BCRYPT
                    $password_hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                    $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, role, status) VALUES (?, ?, ?, ?, 'active')");
                    $stmt->execute([$name, $email, $password_hash, $role]);
                    $user_id = $pdo->lastInsertId();

                    // Assign user_roles mapping in RBAC table
                    $role_stmt = $pdo->prepare("SELECT id FROM roles WHERE name = ?");
                    $role_stmt->execute([$role]);
                    $role_id = $role_stmt->fetchColumn();
                    if ($role_id) {
                        $ur_stmt = $pdo->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)");
                        $ur_stmt->execute([$user_id, $role_id]);
                    }

                    // Create domain profile if landlord or tenant
                    if ($role === 'landlord') {
                        $l_stmt = $pdo->prepare("INSERT INTO landlords (user_id) VALUES (?)");
                        $l_stmt->execute([$user_id]);
                    } elseif ($role === 'tenant') {
                        $t_stmt = $pdo->prepare("INSERT INTO tenants (user_id) VALUES (?)");
                        $t_stmt->execute([$user_id]);
                    }

                    // Audit Log
                    $log_stmt = $pdo->prepare("INSERT INTO audit_logs (username, action, ip_address) VALUES (?, ?, ?)");
                    $log_stmt->execute([$email, "Public User Registered as " . strtoupper($role), $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1']);

                    $pdo->commit();
                    
                    set_flash_message('success', "Account created successfully as " . ucfirst($role) . ". You can now sign in.");
                    redirect('/SarkinMota/auth/login.php');
                }
            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                error_log("Registration Error: " . $e->getMessage());
                $errors[] = "Failed to create account. Please contact system administration.";
            }
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<section class="py-12 sm:py-24 bg-slate-50 dark:bg-gray-950 min-h-[75vh] flex items-center justify-center">
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

        <div class="bg-white dark:bg-slate-900 border border-slate-200/50 dark:border-slate-800 p-6 sm:p-10 rounded-3xl shadow-xl">
            <div class="text-center mb-8">
                <img src="../<?php echo htmlspecialchars(setting('company_logo')); ?>" alt="<?php echo htmlspecialchars(setting('company_name')); ?> Logo" class="h-10 sm:h-12 w-auto mx-auto mb-4 object-contain dark:hidden">
                <img src="../<?php echo htmlspecialchars(setting('company_logo_dark')); ?>" alt="<?php echo htmlspecialchars(setting('company_name')); ?> Dark Logo" class="h-10 sm:h-12 w-auto mx-auto mb-4 object-contain hidden dark:block">
                <span class="text-[10px] font-bold text-amber-500 uppercase tracking-widest block mb-1"><?php echo htmlspecialchars(setting('company_short_name')); ?> Portal Registration</span>
                <h2 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white">Create Portal Account</h2>
            </div>
            
            <form action="register.php" method="POST" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                
                <div>
                    <label for="name" class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Full Name</label>
                    <input type="text" id="name" name="name" required placeholder="e.g. Amina Okonkwo" class="w-full bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500">
                </div>

                <div>
                    <label for="email" class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Email Address</label>
                    <input type="email" id="email" name="email" required placeholder="e.g. amina.okonkwo@sarkinmotahq.com" class="w-full bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500">
                </div>

                <div>
                    <label for="role" class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Account Role Classification</label>
                    <select id="role" name="role" required class="w-full bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500">
                        <option value="client">Client (Property Buyer / Investor)</option>
                        <option value="tenant">Tenant (Renting Property)</option>
                        <option value="landlord">Landlord (Property Owner)</option>
                    </select>
                </div>

                <div>
                    <label for="password" class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Password (Min 8 chars)</label>
                    <input type="password" id="password" name="password" required class="w-full bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500">
                </div>

                <div>
                    <label for="confirm_password" class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required class="w-full bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500">
                </div>

                <button type="submit" class="w-full bg-amber-500 text-slate-950 font-bold uppercase tracking-wider text-xs py-3.5 rounded-xl hover:bg-amber-600 transition-colors shadow-md mt-4">
                    Create Account
                </button>
            </form>

            <div class="mt-8 pt-6 border-t border-slate-200/50 dark:border-slate-800/80 text-center text-xs">
                <span class="text-slate-400">Already registered?</span>
                <a href="login.php" class="text-amber-500 font-semibold hover:underline ml-1">Sign In Gateway</a>
            </div>
        </div>

    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
