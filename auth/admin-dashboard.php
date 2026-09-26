<?php
/**
 * Dedicated Admin Portal Dashboard - Sarkin Mota HQ
 * Administrative Control Center for Super Admin, System Admin & Staff
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_helper.php';
require_once __DIR__ . '/../includes/rbac_helper.php';
require_once __DIR__ . '/../includes/pagination_helper.php';

require_login();
$user = get_logged_in_user();

$staff_roles = ['staff', 'hr_manager', 'hr_officer', 'department_manager', 'interviewer'];
if (!is_admin() && !in_array($user['role'] ?? '', $staff_roles)) {
    redirect('dashboard.php');
}

$errors = [];
$active_tab = $_GET['tab'] ?? 'quick';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;

// 1. Handle Admin User Creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_add_user'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = "CSRF Verification Failed.";
    } else if (!is_admin()) {
        $errors[] = "Access denied. Only Super Admin or System Admin can create accounts.";
    } elseif (!has_permission('users.create')) {
        $errors[] = "Access denied: You lack permission to create user accounts.";
    } else {
        $name = sanitize_input($_POST['name'] ?? '');
        $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL) ? sanitize_input($_POST['email']) : '';
        $password = $_POST['password'] ?? '';
        $target_role = sanitize_input($_POST['role'] ?? 'client');

        if ($target_role === 'super_admin' && !can_manage_user(0, 'super_admin', 'assign_super_admin')) {
            $errors[] = "Access denied: Only Super Administrators can create Super Admin accounts.";
        }

        if (empty($name)) $errors[] = "User name is required.";
        if (empty($email)) $errors[] = "Valid email address is required.";
        if (strlen($password) < 8) $errors[] = "Password must be at least 8 characters.";

        if (empty($errors)) {
            try {
                $pdo->beginTransaction();

                $chk = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $chk->execute([$email]);
                if ($chk->fetch()) {
                    $errors[] = "A user account with this email address already exists.";
                    $pdo->rollBack();
                } else {
                    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                    $ins = $pdo->prepare("INSERT INTO users (name, email, password_hash, role, status) VALUES (?, ?, ?, ?, 'active')");
                    $ins->execute([$name, $email, $hash, $target_role]);
                    $new_user_id = $pdo->lastInsertId();

                    $role_stmt = $pdo->prepare("SELECT id FROM roles WHERE name = ?");
                    $role_stmt->execute([$target_role]);
                    $role_id = $role_stmt->fetchColumn();
                    if ($role_id) {
                        $ur = $pdo->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)");
                        $ur->execute([$new_user_id, $role_id]);
                    }

                    $log = $pdo->prepare("INSERT INTO audit_logs (username, action, ip_address) VALUES (?, ?, ?)");
                    $log->execute([$user['email'], "Created user account {$email} with role " . strtoupper($target_role), $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1']);

                    $pdo->commit();
                    set_flash_message('success', "New user account '{$name}' created successfully with role " . strtoupper($target_role));
                    redirect('admin-dashboard.php?tab=users');
                }
            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                error_log("Add User Error: " . $e->getMessage());
                $errors[] = "Failed to create user account:  Please try again or contact support.";
            }
        }
    }
}

// Data Loading
$dashboard_data = [
    'users_meta' => null,
    'payments_meta' => null,
    'audit_trail' => [],
    'users_count' => 0,
    'properties_count' => 0,
    'applications_count' => 0,
    'total_ledger' => 0.00
];

try {
    if (has_permission('users.view')) {
        $dashboard_data['users_meta'] = paginate_query($pdo, "SELECT id, name, email, role, status, created_at FROM users ORDER BY id DESC", [], $page, 8);
        $dashboard_data['users_count'] = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    }

    if (has_permission('payments.view')) {
        $dashboard_data['payments_meta'] = paginate_query($pdo, "SELECT pt.*, u.name AS user_name FROM payment_transactions pt JOIN users u ON pt.user_id = u.id ORDER BY pt.id DESC", [], $page, 8);
        $sum = $pdo->query("SELECT SUM(received_amount) FROM payment_transactions WHERE status = 'paid'")->fetchColumn();
        $dashboard_data['total_ledger'] = $sum ? floatval($sum) : 0.00;
    }

    try {
        $dashboard_data['audit_trail'] = $pdo->query("SELECT * FROM audit_logs ORDER BY id DESC LIMIT 20")->fetchAll();
    } catch (Exception $e) {
        $dashboard_data['audit_trail'] = [];
    }

    try {
        $dashboard_data['applications_count'] = $pdo->query("SELECT COUNT(*) FROM applications")->fetchColumn();
    } catch (Exception $e) {
        $dashboard_data['applications_count'] = 0;
    }

    $dashboard_data['properties_count'] = $pdo->query("SELECT COUNT(*) FROM properties WHERE listing_status = 'active'")->fetchColumn();
} catch (Exception $e) {
    error_log("Admin Data Fetch Error: " . $e->getMessage());
}

$all_users = $dashboard_data['users_meta']['data'] ?? [];
$payment_records = $dashboard_data['payments_meta']['data'] ?? [];
$audit_trail = is_array($dashboard_data['audit_trail'] ?? null) ? $dashboard_data['audit_trail'] : [];
$properties_count = intval($dashboard_data['properties_count'] ?? 0);
$applications_count = intval($dashboard_data['applications_count'] ?? 0);
$users_count = intval($dashboard_data['users_count'] ?? 0);
$total_ledger_income = $dashboard_data['total_ledger'] ?? 0.00;

$admin_page_title = "Portal Dashboard (" . ucfirst($user['role']) . ")";
require_once __DIR__ . '/../includes/admin_sidebar.php';
?>

<!-- Alerts Section -->
<?php if (!empty($errors)): ?>
    <div class="mb-4 border-l-4 border-rose-500 bg-rose-50 dark:bg-rose-950/20 p-4 rounded-r-md">
        <ul class="list-disc pl-5 text-xs text-rose-800 dark:text-rose-300 space-y-1">
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>
<?php echo display_flash_message(); ?>

<div class="space-y-6">

    <!-- Business Owner Guide: Simple 5-Step Workflow -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 p-6 rounded-3xl shadow-sm space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
            <div class="flex items-center space-x-3">
                <div class="p-2 bg-amber-500/10 text-amber-500 rounded-xl text-lg"><i class="bi bi-compass-fill"></i></div>
                <div>
                    <h2 class="text-base font-extrabold text-slate-900 dark:text-white">Business Owner Quick Guide</h2>
                    <p class="text-xs text-slate-400">Simple 5-step guide to manage your company operations easily</p>
                </div>
            </div>
            <span class="px-3 py-1 bg-amber-500/10 text-amber-500 rounded-full text-xs font-bold uppercase tracking-wider">Business Owner Mode</span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
            <!-- Step 1 -->
            <a href="../admin/manage-divisions.php" class="p-4 bg-slate-50 dark:bg-slate-950 rounded-2xl border border-slate-200/60 dark:border-slate-800 hover:border-amber-500/50 transition-all space-y-2 block">
                <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-wider bg-amber-500 text-slate-950">Step 1</span>
                <h3 class="text-xs font-bold text-slate-900 dark:text-white flex items-center justify-between">
                    <span>Manage Divisions</span>
                    <i class="bi bi-diagram-3 text-amber-500"></i>
                </h3>
                <p class="text-[11px] text-slate-500 dark:text-slate-400">Real Estate, Automobile, or add new branches.</p>
            </a>

            <!-- Step 2 -->
            <a href="../admin/manage-projects.php" class="p-4 bg-slate-50 dark:bg-slate-950 rounded-2xl border border-slate-200/60 dark:border-slate-800 hover:border-amber-500/50 transition-all space-y-2 block">
                <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-wider bg-amber-500 text-slate-950">Step 2</span>
                <h3 class="text-xs font-bold text-slate-900 dark:text-white flex items-center justify-between">
                    <span>Create Projects</span>
                    <i class="bi bi-folder-check text-amber-500"></i>
                </h3>
                <p class="text-[11px] text-slate-500 dark:text-slate-400">Add land plots, house listings, or vehicle inventory.</p>
            </a>

            <!-- Step 3 -->
            <a href="admin-dashboard.php?tab=users" class="p-4 bg-slate-50 dark:bg-slate-950 rounded-2xl border border-slate-200/60 dark:border-slate-800 hover:border-amber-500/50 transition-all space-y-2 block">
                <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-wider bg-amber-500 text-slate-950">Step 3</span>
                <h3 class="text-xs font-bold text-slate-900 dark:text-white flex items-center justify-between">
                    <span>Assign Staff</span>
                    <i class="bi bi-people-fill text-amber-500"></i>
                </h3>
                <p class="text-[11px] text-slate-500 dark:text-slate-400">Assign staff members to their respective divisions.</p>
            </a>

            <!-- Step 4 -->
            <a href="../admin/manage-tasks.php" class="p-4 bg-slate-50 dark:bg-slate-950 rounded-2xl border border-slate-200/60 dark:border-slate-800 hover:border-amber-500/50 transition-all space-y-2 block">
                <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-wider bg-amber-500 text-slate-950">Step 4</span>
                <h3 class="text-xs font-bold text-slate-900 dark:text-white flex items-center justify-between">
                    <span>Assign Tasks</span>
                    <i class="bi bi-list-task text-amber-500"></i>
                </h3>
                <p class="text-[11px] text-slate-500 dark:text-slate-400">Give tasks to staff with instructions and due dates.</p>
            </a>

            <!-- Step 5 -->
            <a href="../admin/review-tasks.php" class="p-4 bg-slate-50 dark:bg-slate-950 rounded-2xl border border-slate-200/60 dark:border-slate-800 hover:border-amber-500/50 transition-all space-y-2 block">
                <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-wider bg-amber-500 text-slate-950">Step 5</span>
                <h3 class="text-xs font-bold text-slate-900 dark:text-white flex items-center justify-between">
                    <span>Review & Approve</span>
                    <i class="bi bi-check-circle-fill text-amber-500"></i>
                </h3>
                <p class="text-[11px] text-slate-500 dark:text-slate-400">Review staff reports & photos. Accept or Request Revision.</p>
            </a>
        </div>
    </div>

    <!-- Tab 1: Quick Stats Overview -->
    <div id="adm-tab-quick" class="admin-tab-content <?php echo ($active_tab === 'quick' || $active_tab === 'overview') ? '' : 'hidden'; ?> space-y-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="bg-white dark:bg-slate-900 border border-slate-200/50 dark:border-slate-800 p-6 rounded-3xl shadow-sm">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-widest block mb-1">Registered Users</span>
                <span class="text-3xl font-extrabold text-slate-900 dark:text-white block"><?php echo number_format($users_count); ?></span>
            </div>
            <div class="bg-white dark:bg-slate-900 border border-slate-200/50 dark:border-slate-800 p-6 rounded-3xl shadow-sm">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-widest block mb-1">Total Ledger Income</span>
                <span class="text-3xl font-extrabold text-emerald-500 block"><?php echo format_currency($total_ledger_income); ?></span>
            </div>
            <div class="bg-white dark:bg-slate-900 border border-slate-200/50 dark:border-slate-800 p-6 rounded-3xl shadow-sm">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-widest block mb-1">Active Property Catalog</span>
                <span class="text-3xl font-extrabold text-amber-500 block"><?php echo number_format($properties_count); ?></span>
            </div>
            <div class="bg-white dark:bg-slate-900 border border-slate-200/50 dark:border-slate-800 p-6 rounded-3xl shadow-sm">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-widest block mb-1">Applications / Inquiries</span>
                <span class="text-3xl font-extrabold text-amber-500 block"><?php echo number_format($applications_count); ?></span>
            </div>
        </div>

        <!-- System Quick Actions & Modules -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <a href="../hr/index.php" class="bg-white dark:bg-slate-900 border border-slate-200/50 dark:border-slate-800 p-6 rounded-3xl shadow-sm hover:border-amber-500/40 transition-all flex items-center space-x-4">
                <div class="h-12 w-12 rounded-2xl bg-amber-500/10 text-amber-500 flex items-center justify-center text-xl">
                    <i aria-hidden="true" class="bi bi-person-workspace text-amber-500"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">HR & Recruitment</h3>
                    <p class="text-xs text-slate-500">Vacancies, candidates, interviews & onboarding.</p>
                </div>
            </a>

            <a href="../admin/manage-properties.php" class="bg-white dark:bg-slate-900 border border-slate-200/50 dark:border-slate-800 p-6 rounded-3xl shadow-sm hover:border-amber-500/40 transition-all flex items-center space-x-4">
                <div class="h-12 w-12 rounded-2xl bg-amber-500/10 text-amber-500 flex items-center justify-center text-xl">
                    <i aria-hidden="true" class="bi bi-building text-amber-500"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Property Management</h3>
                    <p class="text-xs text-slate-500">Edit, activate, or post property listings.</p>
                </div>
            </a>

            <a href="../admin/manage-branding.php" class="bg-white dark:bg-slate-900 border border-slate-200/50 dark:border-slate-800 p-6 rounded-3xl shadow-sm hover:border-amber-500/40 transition-all flex items-center space-x-4">
                <div class="h-12 w-12 rounded-2xl bg-amber-500/10 text-amber-500 flex items-center justify-center text-xl">
                    <i aria-hidden="true" class="bi bi-palette-fill text-amber-500"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Corporate Theme & Branding</h3>
                    <p class="text-xs text-slate-500">Customize brand presets & colors.</p>
                </div>
            </a>

            <a href="../admin/view-messages.php" class="bg-white dark:bg-slate-900 border border-slate-200/50 dark:border-slate-800 p-6 rounded-3xl shadow-sm hover:border-amber-500/40 transition-all flex items-center space-x-4">
                <div class="h-12 w-12 rounded-2xl bg-amber-500/10 text-amber-500 flex items-center justify-center text-xl">
                    <i aria-hidden="true" class="bi bi-chat-left-dots-fill text-amber-500"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Client Inquiries & Tickets</h3>
                    <p class="text-xs text-slate-500">Respond to client consultation tickets.</p>
                </div>
            </a>
        </div>
    </div>

    <!-- Tab 2: User Accounts & Roles -->
    <div id="adm-tab-users" class="admin-tab-content <?php echo ($active_tab === 'users') ? '' : 'hidden'; ?> space-y-6">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 bg-white dark:bg-slate-900 border border-slate-200/50 dark:border-slate-800 p-8 rounded-3xl shadow-sm">
                <h3 class="text-base font-bold text-slate-900 dark:text-white mb-6">User Accounts Roster</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-800 text-slate-400 font-semibold uppercase tracking-widest">
                                <th class="py-3 pr-4">User</th>
                                <th class="py-3 px-4">Role</th>
                                <th class="py-3 px-4">Status</th>
                                <th class="py-3 px-4">Joined Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                            <?php foreach ($all_users as $u): ?>
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-850/50">
                                    <td class="py-3 pr-4">
                                        <span class="font-bold text-slate-900 dark:text-white block"><?php echo htmlspecialchars($u['name']); ?></span>
                                        <span class="text-[10px] text-slate-400 block"><?php echo htmlspecialchars($u['email']); ?></span>
                                    </td>
                                    <td class="py-3 px-4 font-bold uppercase text-amber-500"><?php echo htmlspecialchars($u['role']); ?></td>
                                    <td class="py-3 px-4"><span class="px-2 py-0.5 rounded text-[9px] font-bold uppercase bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300"><?php echo htmlspecialchars($u['status']); ?></span></td>
                                    <td class="py-3 px-4 text-slate-400"><?php echo date('d M Y', strtotime($u['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Create Account Card -->
            <?php if (has_permission('users.create')): ?>
                <div class="bg-white dark:bg-slate-900 border border-slate-200/50 dark:border-slate-800 p-8 rounded-3xl shadow-sm">
                    <h3 class="text-base font-bold text-slate-900 dark:text-white mb-2">Create New Account</h3>
                    <p class="text-xs text-slate-500 mb-6">Add staff, landlord, tenant, or client accounts.</p>
                    <form method="POST" action="admin-dashboard.php?tab=users" class="space-y-4">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        <input type="hidden" name="admin_add_user" value="1">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Full Name</label>
                            <input type="text" name="name" required placeholder="e.g. Abubakar Bello" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:border-amber-500">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Email Address</label>
                            <input type="email" name="email" required placeholder="e.g. abubakar.bello@sarkinmotahq.com" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:border-amber-500">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Account Role</label>
                            <select name="role" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:border-amber-500">
                                <option value="client">Client (Normal User)</option>
                                <option value="tenant">Tenant</option>
                                <option value="landlord">Landlord</option>
                                <option value="staff">Staff</option>
                                <option value="admin">System Admin</option>
                                <?php if (is_super_admin()): ?>
                                    <option value="super_admin">Super Admin</option>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Initial Password</label>
                            <input type="password" name="password" required minlength="8" placeholder="At least 8 characters" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:border-amber-500">
                        </div>
                        <button type="submit" class="w-full bg-amber-500 text-slate-950 font-bold uppercase text-xs tracking-wider py-3 rounded-xl hover:bg-amber-600 transition-colors flex items-center justify-center space-x-2">
                            <i aria-hidden="true" class="bi bi-person-plus-fill"></i>
                            <span>Create Account</span>
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Tab 3: Payment Ledger -->
    <div id="adm-tab-payments" class="admin-tab-content <?php echo ($active_tab === 'payments') ? '' : 'hidden'; ?>">
        <div class="bg-white dark:bg-slate-900 border border-slate-200/50 dark:border-slate-800 p-8 rounded-3xl shadow-sm">
            <h3 class="text-base font-bold text-slate-900 dark:text-white mb-6">Verified Financial Transactions</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 text-slate-400 font-semibold uppercase tracking-widest">
                            <th class="py-3 pr-4">Reference</th>
                            <th class="py-3 px-4">Payer</th>
                            <th class="py-3 px-4">Gateway</th>
                            <th class="py-3 px-4">Amount</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 pl-4 text-right">Receipt</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                        <?php if (empty($payment_records)): ?>
                            <tr>
                                <td colspan="6" class="py-6 text-center text-slate-400">No payment transactions recorded.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($payment_records as $pt): ?>
                                <tr>
                                    <td class="py-3 pr-4 font-mono font-bold text-slate-900 dark:text-white"><?php echo htmlspecialchars($pt['transaction_ref']); ?></td>
                                    <td class="py-3 px-4 font-medium"><?php echo htmlspecialchars($pt['user_name']); ?></td>
                                    <td class="py-3 px-4 text-slate-400"><?php echo htmlspecialchars($pt['provider']); ?></td>
                                    <td class="py-3 px-4 font-bold text-emerald-500"><?php echo format_currency($pt['expected_amount']); ?></td>
                                    <td class="py-3 px-4"><span class="px-2 py-0.5 rounded text-[9px] font-bold uppercase <?php echo $pt['status'] === 'paid' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300' : 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300'; ?>"><?php echo htmlspecialchars($pt['status']); ?></span></td>
                                    <td class="py-3 pl-4 text-right">
                                        <?php if ($pt['status'] === 'paid'): ?>
                                            <a href="../download-receipt.php?id=<?php echo $pt['id']; ?>" target="_blank" class="px-2.5 py-1 bg-amber-500 text-slate-950 text-[10px] font-bold rounded hover:bg-amber-600 inline-flex items-center space-x-1">
                                                <i aria-hidden="true" class="bi bi-download"></i>
                                                <span>Receipt PDF</span>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-slate-400 text-[10px]">Unverified</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Tab 4: Security Audit Logs -->
    <div id="adm-tab-audits" class="admin-tab-content <?php echo ($active_tab === 'audits') ? '' : 'hidden'; ?>">
        <div class="bg-white dark:bg-slate-900 border border-slate-200/50 dark:border-slate-800 p-8 rounded-3xl shadow-sm">
            <h3 class="text-base font-bold text-slate-900 dark:text-white mb-6">Security Audit Logs</h3>
            <div class="overflow-x-auto text-xs">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 text-slate-400">
                            <th class="py-2.5">IP Address</th>
                            <th class="py-2.5 px-4">User</th>
                            <th class="py-2.5 px-4">Action Event</th>
                            <th class="py-2.5 px-4">Timestamp</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($audit_trail)): ?>
                            <tr>
                                <td colspan="4" class="py-6 text-center text-slate-400">No security audit logs recorded yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($audit_trail as $log): ?>
                                <tr class="border-b border-slate-100 dark:border-slate-850">
                                    <td class="py-3 font-mono font-bold text-slate-700 dark:text-white"><?php echo sanitize_input($log['ip_address'] ?? '127.0.0.1'); ?></td>
                                    <td class="py-3 px-4"><?php echo sanitize_input($log['username'] ?? 'System'); ?></td>
                                    <td class="py-3 px-4 font-semibold text-slate-800 dark:text-slate-250"><?php echo sanitize_input($log['action'] ?? ''); ?></td>
                                    <td class="py-3 px-4"><?php echo isset($log['created_at']) ? date('d M Y, H:i:s', strtotime($log['created_at'])) : 'N/A'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

</main>
</div>
</body>
</html>
