<?php
/**
 * Security Login Portal - Sarkin Mota HQ
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
        $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL) ? sanitize_input($_POST['email']) : '';
        $password = $_POST['password'] ?? '';
        
        if (empty($email)) $errors[] = "Please enter a valid email address.";
        if (empty($password)) $errors[] = "Please enter your password.";
        
        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch();
                
                if ($user && isset($user['status']) && $user['status'] !== 'active') {
                    $errors[] = "Your account has been suspended or disabled. Please contact system administration.";
                } elseif ($user && password_verify($password, $user['password_hash'])) {
                    // Create secure authenticated session with unique DB session_token
                    create_authenticated_session($user);
                    
                    set_flash_message('success', "Welcome back, " . htmlspecialchars($user['name']) . "!");
                    
                    // Audit log successful authentication
                    $log_stmt = $pdo->prepare("INSERT INTO audit_logs (username, action, ip_address) VALUES (?, ?, ?)");
                    $log_stmt->execute([$user['email'], "User Authenticated Successfully", $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1']);

                    redirect('/SarkinMota/auth/dashboard.php');
                } else {
                    $errors[] = "Invalid login credentials. Please check your email and password.";
                    // Audit log failed login attempt
                    if (!empty($email)) {
                        $log_stmt = $pdo->prepare("INSERT INTO audit_logs (username, action, ip_address) VALUES (?, ?, ?)");
                        $log_stmt->execute([$email, "Failed Authentication Attempt", $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1']);
                    }
                }
            } catch (Exception $e) {
                error_log("Login Error: " . $e->getMessage());
                $errors[] = "An unexpected error occurred. Please try again later.";
            }
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<section class="py-12 sm:py-24 bg-slate-50 dark:bg-gray-950 min-h-[75vh] flex items-center justify-center">
    <div class="max-w-md w-full mx-auto px-4">
        
        <!-- Flash messages -->
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

        <div class="bg-white dark:bg-slate-900 border border-slate-200/50 dark:border-slate-800 p-6 sm:p-10 rounded-3xl shadow-xl">
            <div class="text-center mb-8">
                <img src="../<?php echo htmlspecialchars(setting('company_logo')); ?>" alt="<?php echo htmlspecialchars(setting('company_name')); ?> Logo" class="h-12 w-auto mx-auto mb-4 object-contain dark:hidden">
                <img src="../<?php echo htmlspecialchars(setting('company_logo_dark')); ?>" alt="<?php echo htmlspecialchars(setting('company_name')); ?> Dark Logo" class="h-12 w-auto mx-auto mb-4 object-contain hidden dark:block">
                <span class="text-[10px] font-bold text-amber-500 uppercase tracking-widest block mb-1"><?php echo htmlspecialchars(setting('company_short_name')); ?> Portal</span>
                <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Portal Sign In</h2>
            </div>
            
            <form action="login.php" method="POST" class="space-y-6">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                
                <div>
                    <label for="email" class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Email Address</label>
                    <input type="email" id="email" name="email" required placeholder="e.g. amina.okonkwo@sarkinmotahq.com" class="w-full bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg px-4 py-3 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500">
                </div>

                <div>
                    <div class="flex justify-between items-center mb-1.5">
                        <label for="password" class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest">Password</label>
                        <a href="forgot-password.php" class="text-[10px] text-slate-400 hover:text-amber-500 transition-colors">Forgot Password?</a>
                    </div>
                    <input type="password" id="password" name="password" required class="w-full bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg px-4 py-3 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500">
                </div>

                <button type="submit" class="w-full bg-amber-500 text-slate-950 font-bold uppercase tracking-wider text-xs py-3.5 rounded-lg hover:bg-amber-600 transition-colors shadow-md">
                    Authenticate Session
                </button>
            </form>

            <div class="mt-8 pt-6 border-t border-slate-200/50 dark:border-slate-800/80 text-center text-xs">
                <span class="text-slate-400">New User?</span>
                <a href="register.php" class="text-amber-500 font-semibold hover:underline ml-1">Create Account Portal</a>
            </div>
        </div>

        <!-- Quick One-Click Demo Role Accounts -->
        <div class="mt-8 bg-white dark:bg-slate-900 border border-slate-200/50 dark:border-slate-800 p-6 rounded-3xl shadow-lg">
            <div class="text-center mb-4">
                <span class="text-[10px] font-bold text-amber-500 uppercase tracking-widest block">Quick Demo Logins</span>
                <p class="text-xs text-slate-500 dark:text-slate-400">Select any role below to test portal features:</p>
            </div>
            
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 text-xs">
                <button type="button" onclick="quickFill('hrmanager@sarkinmotahq.com', 'HRManager@Sarkin Mota HQ2026')" class="p-2.5 bg-amber-50 dark:bg-amber-950/30 border border-amber-300/40 hover:bg-amber-500 hover:text-slate-950 rounded-lg text-left transition-colors group">
                    <span class="font-bold flex items-center text-slate-900 dark:text-white group-hover:text-slate-950"><i aria-hidden="true" class="bi bi-person-workspace text-amber-500 group-hover:text-slate-950 mr-1.5"></i> HR Manager</span>
                    <span class="text-[9px] text-slate-500 group-hover:text-slate-900 block truncate mt-0.5">hrmanager@sarkinmotahq.com</span>
                </button>

                <button type="button" onclick="quickFill('superadmin@sarkinmotahq.com', 'SuperAdmin@Sarkin Mota HQ2026')" class="p-2.5 bg-slate-100 dark:bg-slate-800 hover:bg-amber-500 hover:text-slate-950 rounded-lg text-left transition-colors group">
                    <span class="font-bold flex items-center text-slate-900 dark:text-white group-hover:text-slate-950"><i aria-hidden="true" class="bi bi-lightning-charge-fill text-amber-500 group-hover:text-slate-950 mr-1.5"></i> Super Admin</span>
                    <span class="text-[9px] text-slate-400 group-hover:text-slate-900 block truncate mt-0.5">superadmin@sarkinmotahq.com</span>
                </button>

                <button type="button" onclick="quickFill('admin@sarkinmotahq.com', 'Admin@Sarkin Mota HQ2026')" class="p-2.5 bg-slate-100 dark:bg-slate-800 hover:bg-amber-500 hover:text-slate-950 rounded-lg text-left transition-colors group">
                    <span class="font-bold flex items-center text-slate-900 dark:text-white group-hover:text-slate-950"><i aria-hidden="true" class="bi bi-shield-lock-fill text-amber-500 group-hover:text-slate-950 mr-1.5"></i> System Admin</span>
                    <span class="text-[9px] text-slate-400 group-hover:text-slate-900 block truncate mt-0.5">admin@sarkinmotahq.com</span>
                </button>

                <button type="button" onclick="quickFill('landlord@sarkinmotahq.com', 'Landlord@Sarkin Mota HQ2026')" class="p-2.5 bg-slate-100 dark:bg-slate-800 hover:bg-amber-500 hover:text-slate-950 rounded-lg text-left transition-colors group">
                    <span class="font-bold flex items-center text-slate-900 dark:text-white group-hover:text-slate-950"><i aria-hidden="true" class="bi bi-building text-amber-500 group-hover:text-slate-950 mr-1.5"></i> Landlord</span>
                    <span class="text-[9px] text-slate-400 group-hover:text-slate-900 block truncate mt-0.5">landlord@sarkinmotahq.com</span>
                </button>

                <button type="button" onclick="quickFill('tenant@sarkinmotahq.com', 'Tenant@Sarkin Mota HQ2026')" class="p-2.5 bg-slate-100 dark:bg-slate-800 hover:bg-amber-500 hover:text-slate-950 rounded-lg text-left transition-colors group">
                    <span class="font-bold flex items-center text-slate-900 dark:text-white group-hover:text-slate-950"><i aria-hidden="true" class="bi bi-house-door text-amber-500 group-hover:text-slate-950 mr-1.5"></i> Tenant</span>
                    <span class="text-[9px] text-slate-400 group-hover:text-slate-900 block truncate mt-0.5">tenant@sarkinmotahq.com</span>
                </button>
            </div>
        </div>
    </div>
</section>

<script>
function quickFill(email, password) {
    document.getElementById('email').value = email;
    document.getElementById('password').value = password;
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
