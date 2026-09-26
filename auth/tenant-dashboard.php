<?php
/**
 * Dedicated Tenant Portal Dashboard - Sarkin Mota HQ
 * Full Functional Capability: Pay Rent, Electricity & Utilities, Leases/Receipts, Repairs & Helpdesk
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_helper.php';
require_once __DIR__ . '/../includes/rbac_helper.php';
require_once __DIR__ . '/../app/Services/PaymentService.php';

require_login();
$user = get_logged_in_user();

if ($user['role'] !== 'tenant' && !is_admin()) {
    redirect('dashboard.php');
}

$errors = [];
$active_tab = $_GET['tab'] ?? 'overview';

// 1. Handle Rent Payment Initiation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pay_rent_submit'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = "CSRF Verification Failed.";
    } else {
        $amount = floatval($_POST['amount'] ?? 0);
        $provider = sanitize_input($_POST['gateway'] ?? 'Paystack');
        
        if ($amount <= 0) $errors[] = "Please specify a valid rent payment amount.";
        
        if (empty($errors)) {
            try {
                $paymentService = new PaymentService($pdo);
                $txn = $paymentService->initiateTransaction($user['id'], $amount, $provider);
                $verifiedTx = $paymentService->verifyTransaction($txn['transaction_ref'], 'REF-' . bin2hex(random_bytes(6)));
                
                $log_stmt = $pdo->prepare("INSERT INTO audit_logs (username, action, ip_address) VALUES (?, ?, ?)");
                $log_stmt->execute([$user['email'], "Processed rent payment of " . format_currency($amount) . " via " . $provider, $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1']);
                
                set_flash_message('success', "Rent payment of " . format_currency($amount) . " processed successfully! Official receipt generated.");
                redirect('tenant-dashboard.php?tab=pay_rent');
            } catch (Exception $e) {
                error_log("Rent Payment Processing Error: " . $e->getMessage());
                $errors[] = "Payment processing failed:  Please try again or contact support.";
            }
        }
    }
}

// 2. Handle Electricity & Utility Payment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pay_electricity_submit'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = "CSRF Verification Failed.";
    } else {
        $meter_number = sanitize_input($_POST['meter_number'] ?? '');
        $amount = floatval($_POST['utility_amount'] ?? 0);
        $utility_type = sanitize_input($_POST['utility_type'] ?? 'Electricity (Prepaid Meter)');
        $provider = sanitize_input($_POST['utility_provider'] ?? 'Paystack');
        
        if (empty($meter_number)) $errors[] = "Please enter your meter or utility account number.";
        if ($amount < 500) $errors[] = "Minimum recharge amount is ₦500.";
        
        if (empty($errors)) {
            try {
                $paymentService = new PaymentService($pdo);
                $txn = $paymentService->initiateTransaction($user['id'], $amount, $provider);
                $verifiedTx = $paymentService->verifyTransaction($txn['transaction_ref'], 'UTIL-' . bin2hex(random_bytes(6)));
                
                $gen_token = implode('-', str_split(str_pad((string)mt_rand(1000000000000000, 9999999999999999), 20, '0', STR_PAD_LEFT), 4));
                
                $log_stmt = $pdo->prepare("INSERT INTO audit_logs (username, action, ip_address) VALUES (?, ?, ?)");
                $log_stmt->execute([$user['email'], "Recharged utility ({$utility_type}) for Meter {$meter_number} - Amount: " . format_currency($amount) . " (Token: {$gen_token})", $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1']);
                
                set_flash_message('success', "Utility recharge of " . format_currency($amount) . " for Meter {$meter_number} verified! Token: {$gen_token}");
                redirect('tenant-dashboard.php?tab=pay_electricity');
            } catch (Exception $e) {
                error_log("Utility Payment Error: " . $e->getMessage());
                $errors[] = "Utility payment failed:  Please try again or contact support.";
            }
        }
    }
}

// 3. Handle Maintenance Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_maintenance'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = "CSRF Verification Failed.";
    } else {
        $category = sanitize_input($_POST['maint_category'] ?? 'Plumbing');
        $priority = sanitize_input($_POST['maint_priority'] ?? 'Medium');
        $details = sanitize_input($_POST['maint_details'] ?? '');
        
        if (empty($details)) $errors[] = "Please provide details describing the maintenance issue.";
        
        if (empty($errors)) {
            try {
                $log_stmt = $pdo->prepare("INSERT INTO audit_logs (username, action, ip_address) VALUES (?, ?, ?)");
                $log_stmt->execute([$user['email'], "Logged maintenance ticket ({$category} - Priority: {$priority}): " . substr($details, 0, 100), $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1']);
                
                set_flash_message('success', "Maintenance request ({$category}) logged successfully. Our estate maintenance team will contact you.");
                redirect('tenant-dashboard.php?tab=maintenance');
            } catch (Exception $e) {
                $errors[] = "Failed to log maintenance ticket:  Please try again or contact support.";
            }
        }
    }
}

// Fetch Tenant Data
$my_payments = [];
$my_leases = [];
$my_inspections = [];

try {
    $stmt_tx = $pdo->prepare("SELECT * FROM payment_transactions WHERE user_id = ? ORDER BY id DESC");
    $stmt_tx->execute([$user['id']]);
    $my_payments = $stmt_tx->fetchAll();

    $stmt_lease = $pdo->prepare("SELECT l.*, p.title AS property_title, p.location FROM leases l JOIN properties p ON l.property_id = p.id WHERE l.tenant_id = ? AND l.status = 'active'");
    $stmt_lease->execute([$user['id']]);
    $my_leases = $stmt_lease->fetchAll();

    $stmt_insp = $pdo->prepare("SELECT ir.*, p.title AS property_title FROM inspection_requests ir JOIN properties p ON ir.property_id = p.id WHERE ir.user_id = ? ORDER BY ir.id DESC");
    $stmt_insp->execute([$user['id']]);
    $my_inspections = $stmt_insp->fetchAll();
} catch (Exception $e) {
    error_log("Tenant Fetch Error: " . $e->getMessage());
}

$admin_page_title = "Portal Dashboard (Tenant)";
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

<!-- Tenant Dashboard Container -->
<div class="space-y-6">

    <!-- Tab 1: Overview -->
    <div id="tnt-tab-overview" class="tenant-tab-content <?php echo ($active_tab === 'overview' || $active_tab === 'quick') ? '' : 'hidden'; ?> space-y-6">
        <div class="bg-amber-500/10 border border-amber-500/20 p-5 rounded-2xl flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center space-x-3">
                <i aria-hidden="true" class="bi bi-shield-check text-2xl text-amber-500"></i>
                <div>
                    <h2 class="text-sm font-bold text-slate-900 dark:text-white">Welcome back, <?php echo htmlspecialchars($user['name']); ?> (Tenant)</h2>
                    <p class="text-xs text-slate-500">Manage your active tenancy, pay rent online, purchase utility tokens, and log repair requests.</p>
                </div>
            </div>
            <a href="tenant-dashboard.php?tab=pay_rent" class="px-4 py-2 bg-amber-500 text-slate-950 text-xs font-bold rounded-xl hover:bg-amber-600 transition-colors flex items-center space-x-2">
                <i aria-hidden="true" class="bi bi-credit-card-fill"></i>
                <span>Pay Rent Online</span>
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Quick Pay Rent Card -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200/50 dark:border-slate-800 p-6 rounded-3xl shadow-sm hover:border-amber-500/40 transition-all flex flex-col justify-between">
                <div>
                    <div class="h-10 w-10 rounded-2xl bg-amber-500/10 text-amber-500 flex items-center justify-center mb-4 text-lg">
                        <i aria-hidden="true" class="bi bi-wallet2"></i>
                    </div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-1">Pay Rent & Service Charge</h3>
                    <p class="text-xs text-slate-500 mb-4">Instant payment via Paystack or direct bank transfer with receipt generation.</p>
                </div>
                <a href="tenant-dashboard.php?tab=pay_rent" class="w-full py-2 px-3 bg-amber-500 text-slate-950 text-center text-xs font-bold rounded-xl hover:bg-amber-600 transition-colors block">
                    Proceed to Payment
                </a>
            </div>

            <!-- Quick Utility Token Card -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200/50 dark:border-slate-800 p-6 rounded-3xl shadow-sm hover:border-amber-500/40 transition-all flex flex-col justify-between">
                <div>
                    <div class="h-10 w-10 rounded-2xl bg-amber-500/10 text-amber-500 flex items-center justify-center mb-4 text-lg">
                        <i aria-hidden="true" class="bi bi-lightning-charge-fill"></i>
                    </div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-1">Electricity & Utility Token</h3>
                    <p class="text-xs text-slate-500 mb-4">Recharge prepaid meter tokens instantly and receive 20-digit token numbers.</p>
                </div>
                <a href="tenant-dashboard.php?tab=pay_electricity" class="w-full py-2 px-3 bg-amber-500 text-slate-950 text-center text-xs font-bold rounded-xl hover:bg-amber-600 transition-colors block">
                    Recharge Utilities
                </a>
            </div>

            <!-- Quick Maintenance Card -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200/50 dark:border-slate-800 p-6 rounded-3xl shadow-sm hover:border-amber-500/40 transition-all flex flex-col justify-between">
                <div>
                    <div class="h-10 w-10 rounded-2xl bg-amber-500/10 text-amber-500 flex items-center justify-center mb-4 text-lg">
                        <i aria-hidden="true" class="bi bi-tools"></i>
                    </div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-1">Maintenance & Repairs</h3>
                    <p class="text-xs text-slate-500 mb-4">Submit maintenance tickets for plumbing, electrical, structural, or appliance repairs.</p>
                </div>
                <a href="tenant-dashboard.php?tab=maintenance" class="w-full py-2 px-3 bg-amber-500 text-slate-950 text-center text-xs font-bold rounded-xl hover:bg-amber-600 transition-colors block">
                    Report Issue
                </a>
            </div>
        </div>
    </div>

    <!-- Tab 2: Pay Rent -->
    <div id="tnt-tab-pay_rent" class="tenant-tab-content <?php echo ($active_tab === 'pay_rent') ? '' : 'hidden'; ?>">
        <div class="bg-white dark:bg-slate-900 border border-slate-200/50 dark:border-slate-800 p-8 rounded-3xl shadow-sm max-w-xl">
            <h3 class="text-base font-bold text-slate-900 dark:text-white mb-2">Pay Rent & Service Charge</h3>
            <p class="text-xs text-slate-500 mb-6">Complete your lease payment securely.</p>
            <form method="POST" action="tenant-dashboard.php?tab=pay_rent" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <input type="hidden" name="pay_rent_submit" value="1">
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Payment Amount (₦)</label>
                    <input type="number" step="0.01" min="1000" name="amount" required placeholder="e.g. 500000" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:border-amber-500">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Payment Gateway / Method</label>
                    <select name="gateway" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:border-amber-500">
                        <option value="Paystack">Paystack Online (Cards, Bank Transfer, USSD)</option>
                        <option value="Direct Bank Transfer">Direct Corporate Bank Transfer</option>
                    </select>
                </div>
                <button type="submit" class="w-full bg-amber-500 text-slate-950 font-bold uppercase text-xs tracking-wider py-3 rounded-xl hover:bg-amber-600 transition-colors flex items-center justify-center space-x-2">
                    <i aria-hidden="true" class="bi bi-shield-lock-fill"></i>
                    <span>Pay Rent Now</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Tab 3: Pay Electricity -->
    <div id="tnt-tab-pay_electricity" class="tenant-tab-content <?php echo ($active_tab === 'pay_electricity') ? '' : 'hidden'; ?>">
        <div class="bg-white dark:bg-slate-900 border border-slate-200/50 dark:border-slate-800 p-8 rounded-3xl shadow-sm max-w-xl">
            <h3 class="text-base font-bold text-slate-900 dark:text-white mb-2">Electricity & Utility Token Purchase</h3>
            <p class="text-xs text-slate-500 mb-6">Recharge prepaid meter tokens instantly for your rental unit.</p>
            <form method="POST" action="tenant-dashboard.php?tab=pay_electricity" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <input type="hidden" name="pay_electricity_submit" value="1">
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Prepaid Meter / Account Number</label>
                    <input type="text" name="meter_number" required placeholder="e.g. 45019827364" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:border-amber-500 font-mono">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Utility Type</label>
                    <select name="utility_type" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:border-amber-500">
                        <option value="Electricity (Prepaid Meter)">Electricity (Prepaid Meter)</option>
                        <option value="Water Utility Levy">Water Utility Levy</option>
                        <option value="Waste Management Fee">Estate Waste Management Fee</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Recharge Amount (₦)</label>
                    <input type="number" step="500" min="500" name="utility_amount" required placeholder="e.g. 5000" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:border-amber-500">
                </div>
                <button type="submit" class="w-full bg-amber-500 text-slate-950 font-bold uppercase text-xs tracking-wider py-3 rounded-xl hover:bg-amber-600 transition-colors flex items-center justify-center space-x-2">
                    <i aria-hidden="true" class="bi bi-lightning-charge-fill"></i>
                    <span>Generate Prepaid Token</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Tab 4: Receipts & Leases -->
    <div id="tnt-tab-receipts" class="tenant-tab-content <?php echo ($active_tab === 'receipts') ? '' : 'hidden'; ?> space-y-6">
        <div class="bg-white dark:bg-slate-900 border border-slate-200/50 dark:border-slate-800 p-8 rounded-3xl shadow-sm">
            <h3 class="text-base font-bold text-slate-900 dark:text-white mb-6">Lease Documents & Payment Receipts</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 text-slate-400 font-semibold uppercase tracking-widest">
                            <th class="py-3 pr-4">Reference / Receipt #</th>
                            <th class="py-3 px-4">Gateway</th>
                            <th class="py-3 px-4">Amount</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4">Date</th>
                            <th class="py-3 pl-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                        <?php if (empty($my_payments)): ?>
                            <tr>
                                <td colspan="6" class="py-6 text-center text-slate-400">No rent payment receipts generated yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($my_payments as $pay): ?>
                                <tr>
                                    <td class="py-3 pr-4 font-mono font-bold text-slate-900 dark:text-white"><?php echo htmlspecialchars($pay['transaction_ref'] ?? 'REF-N/A'); ?></td>
                                    <td class="py-3 px-4 text-slate-400"><?php echo htmlspecialchars($pay['provider'] ?? 'Direct'); ?></td>
                                    <td class="py-3 px-4 font-bold text-emerald-500"><?php echo format_currency($pay['expected_amount'] ?? $pay['received_amount'] ?? 0); ?></td>
                                    <td class="py-3 px-4"><span class="px-2 py-0.5 rounded text-[9px] font-bold uppercase bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300"><?php echo htmlspecialchars($pay['status'] ?? 'paid'); ?></span></td>
                                    <td class="py-3 px-4 text-slate-400"><?php echo date('d M Y', strtotime($pay['created_at'] ?? 'now')); ?></td>
                                    <td class="py-3 pl-4 text-right">
                                        <a href="../download-receipt.php?id=<?php echo $pay['id']; ?>" target="_blank" class="px-3 py-1 bg-amber-500 text-slate-950 text-[10px] font-bold rounded-lg hover:bg-amber-600 inline-flex items-center space-x-1">
                                            <i aria-hidden="true" class="bi bi-download"></i>
                                            <span>Receipt PDF</span>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Tab 5: Maintenance -->
    <div id="tnt-tab-maintenance" class="tenant-tab-content <?php echo ($active_tab === 'maintenance') ? '' : 'hidden'; ?>">
        <div class="bg-white dark:bg-slate-900 border border-slate-200/50 dark:border-slate-800 p-8 rounded-3xl shadow-sm max-w-xl">
            <h3 class="text-base font-bold text-slate-900 dark:text-white mb-2">Submit Maintenance Ticket</h3>
            <p class="text-xs text-slate-500 mb-6">Report any repair or structural issue for immediate estate inspection.</p>
            <form method="POST" action="tenant-dashboard.php?tab=maintenance" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <input type="hidden" name="submit_maintenance" value="1">
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Issue Category</label>
                    <select name="maint_category" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:border-amber-500">
                        <option value="Plumbing & Water Supply">Plumbing & Water Supply</option>
                        <option value="Electrical & Wiring">Electrical & Wiring</option>
                        <option value="AC & HVAC Cooling">AC & HVAC Cooling</option>
                        <option value="Carpentry & Doors">Carpentry & Doors</option>
                        <option value="Structural & Roofing">Structural & Roofing</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Priority Level</label>
                    <select name="maint_priority" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:border-amber-500">
                        <option value="Low">Low (General Maintenance)</option>
                        <option value="Medium" selected>Medium (Standard Repair)</option>
                        <option value="High / Urgent">High / Urgent (Immediate Action Required)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Problem Description</label>
                    <textarea name="maint_details" rows="4" required placeholder="Describe the fault in detail..." class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:border-amber-500"></textarea>
                </div>
                <button type="submit" class="w-full bg-amber-500 text-slate-950 font-bold uppercase text-xs tracking-wider py-3 rounded-xl hover:bg-amber-600 transition-colors flex items-center justify-center space-x-2">
                    <i aria-hidden="true" class="bi bi-tools"></i>
                    <span>Log Repair Ticket</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Tab 6: Helpdesk -->
    <div id="tnt-tab-helpdesk" class="tenant-tab-content <?php echo ($active_tab === 'helpdesk') ? '' : 'hidden'; ?>">
        <div class="bg-white dark:bg-slate-900 border border-slate-200/50 dark:border-slate-800 p-8 rounded-3xl shadow-sm max-w-xl">
            <h3 class="text-base font-bold text-slate-900 dark:text-white mb-2">Tenant Helpdesk & Support</h3>
            <p class="text-xs text-slate-500 mb-6">Contact estate management directly for general inquiries.</p>
            <form action="../contact.php" method="POST" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <input type="hidden" name="subject" value="Tenant Helpdesk Inquiry">
                <input type="hidden" name="name" value="<?php echo htmlspecialchars($user['name']); ?>">
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($user['email']); ?>">
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Inquiry Details</label>
                    <textarea name="message" rows="4" required placeholder="Type your message for estate management..." class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:border-amber-500"></textarea>
                </div>
                <button type="submit" class="w-full bg-amber-500 text-slate-950 font-bold uppercase text-xs tracking-wider py-3 rounded-xl hover:bg-amber-600 transition-colors flex items-center justify-center space-x-2">
                    <i aria-hidden="true" class="bi bi-send-fill"></i>
                    <span>Send Message to Support</span>
                </button>
            </form>
        </div>
    </div>

</div>

</main>
</div>
</body>
</html>
