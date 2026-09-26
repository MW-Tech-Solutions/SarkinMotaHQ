<?php
/**
 * Admin Custom Email Dispatcher & Communication Center
 * Sarkin Mota HQ — Enterprise Edition
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_helper.php';
require_once __DIR__ . '/../includes/rbac_helper.php';
require_once __DIR__ . '/../includes/settings_helper.php';
require_once __DIR__ . '/../includes/mail_helper.php';

require_login();
$user = get_logged_in_user();
$user_id = $user['id'];
$user_role = $user['role'];

// Permission Guard: Super Admin, Admin, HR Manager, or users with mail permission
$can_send_mail = is_super_admin() || is_admin() || $user_role === 'hr_manager' || has_permission('users.view') || has_permission('hr.view_dashboard');

if (!$can_send_mail) {
    set_flash_message('danger', 'Access Denied: You do not have authorization to access the Email Dispatcher.');
    redirect('/SarkinMota/auth/dashboard.php');
}

$error = '';
$success = '';

// Handle Email Dispatch Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'CSRF validation failed. Please refresh and try again.';
    } else {
        $dispatch_type = sanitize_input($_POST['dispatch_type'] ?? 'single_user');
        $custom_email = trim(sanitize_input($_POST['custom_email'] ?? ''));
        $selected_user_id = (int)($_POST['user_id'] ?? 0);
        $target_role = sanitize_input($_POST['target_role'] ?? '');
        $subject = trim(sanitize_input($_POST['subject'] ?? ''));
        $body_content = trim($_POST['body_content'] ?? '');

        if (empty($subject)) {
            $error = 'Please enter an email subject line.';
        } elseif (empty($body_content)) {
            $error = 'Please enter the email message content.';
        } else {
            $sent_count = 0;

            // DISPATCH TO CUSTOM EMAIL ADDRESS
            if ($dispatch_type === 'custom_email') {
                if (empty($custom_email) || !filter_var($custom_email, FILTER_VALIDATE_EMAIL)) {
                    $error = 'Please enter a valid custom email address.';
                } else {
                    if (send_system_email($custom_email, $subject, $body_content, 'custom', 'Valued Recipient', $user_id)) {
                        $sent_count++;
                    }
                }
            }
            
            // DISPATCH TO SINGLE REGISTERED USER
            elseif ($dispatch_type === 'single_user') {
                if ($selected_user_id <= 0) {
                    $error = 'Please select a registered user recipient.';
                } else {
                    $stmt_u = $pdo->prepare("SELECT name, email FROM users WHERE id = ?");
                    $stmt_u->execute([$selected_user_id]);
                    $target_u = $stmt_u->fetch();

                    if (!$target_u || empty($target_u['email'])) {
                        $error = 'Selected user email not found.';
                    } else {
                        if (send_system_email($target_u['email'], $subject, $body_content, 'user_direct', $target_u['name'], $user_id)) {
                            $sent_count++;
                        }
                    }
                }
            }

            // DISPATCH BROADCAST TO ROLE GROUP
            elseif ($dispatch_type === 'role_group') {
                $sql = "SELECT name, email FROM users WHERE status = 'active'";
                $params = [];

                if ($target_role !== 'all' && !empty($target_role)) {
                    $sql .= " AND role = ?";
                    $params[] = $target_role;
                }

                $stmt_group = $pdo->prepare($sql);
                $stmt_group->execute($params);
                $recipients = $stmt_group->fetchAll();

                if (empty($recipients)) {
                    $error = 'No active users found matching the selected role group.';
                } else {
                    foreach ($recipients as $rec) {
                        if (!empty($rec['email']) && filter_var($rec['email'], FILTER_VALIDATE_EMAIL)) {
                            send_system_email($rec['email'], $subject, $body_content, 'broadcast_' . $target_role, $rec['name'], $user_id);
                            $sent_count++;
                        }
                    }
                }
            }

            if ($sent_count > 0 && empty($error)) {
                $success = "Successfully dispatched email to $sent_count recipient(s).";
                // Clear form
                $_POST = [];
            }
        }
    }
}

// Fetch all registered users for dropdown
$all_users = $pdo->query("SELECT id, name, email, role FROM users ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch recent sent emails log
$sql_logs = "
    SELECT 
        m.*,
        u.name AS sender_name
    FROM mail_logs m
    LEFT JOIN users u ON m.sender_user_id = u.id
    ORDER BY m.id DESC
    LIMIT 30
";
$mail_history = $pdo->query($sql_logs)->fetchAll(PDO::FETCH_ASSOC);

// Aggregate mail metrics
$total_sent = (int)$pdo->query("SELECT COUNT(*) FROM mail_logs")->fetchColumn();
$sent_today = (int)$pdo->query("SELECT COUNT(*) FROM mail_logs WHERE DATE(sent_at) = CURDATE()")->fetchColumn();
$recruitment_mails = (int)$pdo->query("SELECT COUNT(*) FROM mail_logs WHERE mail_type LIKE '%recruitment%' OR mail_type LIKE '%application%'")->fetchColumn();
$custom_mails = (int)$pdo->query("SELECT COUNT(*) FROM mail_logs WHERE mail_type = 'custom' OR mail_type = 'user_direct'")->fetchColumn();

$admin_page_title = 'Email Dispatcher & Communication Center';
require_once __DIR__ . '/../includes/admin_sidebar.php';
?>

<div class="space-y-6">

    <!-- Flash Notifications -->
    <?php if (!empty($success)): ?>
        <div class="p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 text-xs font-bold flex items-center justify-between shadow-sm">
            <div class="flex items-center space-x-2">
                <i aria-hidden="true" class="bi bi-check-circle-fill text-emerald-500 text-base"></i>
                <span><?php echo htmlspecialchars($success); ?></span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700" aria-label="Dismiss notification" title="Dismiss">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-300 text-xs font-bold flex items-center justify-between shadow-sm">
            <div class="flex items-center space-x-2">
                <i aria-hidden="true" class="bi bi-exclamation-triangle-fill text-rose-500 text-base"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-700" aria-label="Dismiss notification" title="Dismiss">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
    <?php endif; ?>

    <!-- Action Header Banner -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-3xl shadow-sm">
        <div>
            <div class="flex items-center space-x-2 mb-1">
                <span class="px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider bg-amber-500/10 text-amber-500 rounded-full border border-amber-500/20">
                    System Communication Engine
                </span>
                <span class="text-xs text-slate-400 font-semibold">• Email Dispatcher</span>
            </div>
            <h1 class="text-2xl font-extrabold text-slate-900 dark:text-white">Email Dispatcher & Communication Center</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Send custom email messages to specific users, role groups, custom email addresses, or applicant candidates.</p>
        </div>

        <div class="flex items-center space-x-2">
            <span class="px-3 py-1.5 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 text-xs font-extrabold rounded-xl inline-flex items-center gap-1.5">
                <i aria-hidden="true" class="bi bi-envelope-check-fill text-amber-500"></i>
                <span>Active Mail Dispatcher</span>
            </span>
        </div>
    </div>

    <!-- Analytics Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 rounded-3xl shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Total Sent Emails</span>
                    <h3 class="text-2xl font-black text-slate-900 dark:text-white mt-1"><?php echo $total_sent; ?></h3>
                </div>
                <div class="h-12 w-12 rounded-2xl bg-amber-500/10 text-amber-500 flex items-center justify-center text-xl font-bold">
                    <i aria-hidden="true" class="bi bi-send-fill"></i>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 rounded-3xl shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Dispatched Today</span>
                    <h3 class="text-2xl font-black text-slate-900 dark:text-white mt-1"><?php echo $sent_today; ?></h3>
                </div>
                <div class="h-12 w-12 rounded-2xl bg-emerald-500/10 text-emerald-500 flex items-center justify-center text-xl font-bold">
                    <i aria-hidden="true" class="bi bi-calendar-check-fill"></i>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 rounded-3xl shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Recruitment Alerts</span>
                    <h3 class="text-2xl font-black text-slate-900 dark:text-white mt-1"><?php echo $recruitment_mails; ?></h3>
                </div>
                <div class="h-12 w-12 rounded-2xl bg-indigo-500/10 text-indigo-500 flex items-center justify-center text-xl font-bold">
                    <i aria-hidden="true" class="bi bi-briefcase-fill"></i>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 rounded-3xl shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Direct / Custom Mail</span>
                    <h3 class="text-2xl font-black text-slate-900 dark:text-white mt-1"><?php echo $custom_mails; ?></h3>
                </div>
                <div class="h-12 w-12 rounded-2xl bg-blue-500/10 text-blue-500 flex items-center justify-center text-xl font-bold">
                    <i aria-hidden="true" class="bi bi-envelope-heart-fill"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Workspace Grid: Email Dispatcher Form & Guidelines -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

        <!-- Left / Form Column (8 Cols) -->
        <div class="lg:col-span-8 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 sm:p-8 shadow-sm space-y-6">
            
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-4">
                <div>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-amber-500">Corporate Mail Composer</span>
                    <h2 class="text-lg font-extrabold text-slate-900 dark:text-white">Compose & Dispatch Email</h2>
                </div>

                <!-- Template Selector Dropdown -->
                <div class="w-48 sm:w-56">
                    <select id="templatePresetSelect" onchange="applyTemplatePreset(this.value)" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3 py-2 text-xs font-bold text-slate-800 dark:text-slate-200 focus:outline-none focus:border-amber-500">
                        <option value="">-- Quick Message Preset --</option>
                        <option value="general_notice">General Corporate Announcement</option>
                        <option value="recruitment_update">Job Application Status Update</option>
                        <option value="interview_schedule">Interview Invitation</option>
                        <option value="account_notice">Portal Account Maintenance</option>
                    </select>
                </div>
            </div>

            <form action="send-mail.php" method="POST" class="space-y-5">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

                <!-- Recipient Target Mode Radio Group -->
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-2">
                        Select Recipient Type <span class="text-rose-500">*</span>
                    </label>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <label class="flex items-center space-x-3 p-3.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-2xl cursor-pointer hover:border-amber-500 transition-colors">
                            <input type="radio" name="dispatch_type" value="single_user" checked onclick="toggleRecipientFields('single_user')" class="h-4 w-4 text-amber-500 focus:ring-amber-500">
                            <div>
                                <span class="block text-xs font-extrabold text-slate-900 dark:text-white">Single User</span>
                                <span class="block text-[10px] text-slate-400">Select registered user</span>
                            </div>
                        </label>

                        <label class="flex items-center space-x-3 p-3.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-2xl cursor-pointer hover:border-amber-500 transition-colors">
                            <input type="radio" name="dispatch_type" value="custom_email" onclick="toggleRecipientFields('custom_email')" class="h-4 w-4 text-amber-500 focus:ring-amber-500">
                            <div>
                                <span class="block text-xs font-extrabold text-slate-900 dark:text-white">Custom Email</span>
                                <span class="block text-[10px] text-slate-400">Type any email address</span>
                            </div>
                        </label>

                        <label class="flex items-center space-x-3 p-3.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-2xl cursor-pointer hover:border-amber-500 transition-colors">
                            <input type="radio" name="dispatch_type" value="role_group" onclick="toggleRecipientFields('role_group')" class="h-4 w-4 text-amber-500 focus:ring-amber-500">
                            <div>
                                <span class="block text-xs font-extrabold text-slate-900 dark:text-white">Role Group</span>
                                <span class="block text-[10px] text-slate-400">Broadcast to group</span>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Recipient Field: Single User Dropdown -->
                <div id="field_single_user">
                    <label for="user_id" class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">
                        Select Registered User <span class="text-rose-500">*</span>
                    </label>
                    <select id="user_id" name="user_id" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500">
                        <option value="">-- Choose User Recipient --</option>
                        <?php foreach ($all_users as $usr): ?>
                            <option value="<?php echo $usr['id']; ?>">
                                <?php echo htmlspecialchars($usr['name']); ?> (<?php echo htmlspecialchars($usr['email']); ?> - <?php echo str_replace('_', ' ', $usr['role']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Recipient Field: Custom Email Input -->
                <div id="field_custom_email" class="hidden">
                    <label for="custom_email" class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">
                        Custom Recipient Email Address <span class="text-rose-500">*</span>
                    </label>
                    <input type="email" id="custom_email" name="custom_email" placeholder="e.g. candidate.name@domain.com or info@external.com" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500">
                    <p class="text-[10px] text-slate-400 mt-1">Type any custom email address. Useful for non-registered applicants or external partners.</p>
                </div>

                <!-- Recipient Field: Role Group Dropdown -->
                <div id="field_role_group" class="hidden">
                    <label for="target_role" class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">
                        Target User Group / Role <span class="text-rose-500">*</span>
                    </label>
                    <select id="target_role" name="target_role" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500">
                        <option value="all">Broadcast to All Active Portal Users</option>
                        <option value="tenant">All Tenants</option>
                        <option value="landlord">All Landlords</option>
                        <option value="client">All Registered Clients</option>
                        <option value="hr_manager">HR Managers & Officers</option>
                        <option value="admin">All System Administrators</option>
                    </select>
                </div>

                <!-- Email Subject Line -->
                <div>
                    <label for="mail_subject" class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">
                        Email Subject Line <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" id="mail_subject" name="subject" required placeholder="e.g. Official Update regarding your Sarkin Mota HQ Application" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500">
                </div>

                <!-- Email Body Content Area -->
                <div>
                    <label for="mail_body" class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">
                        Email Message Body (Plain text; formatted into corporate layout) <span class="text-rose-500">*</span>
                    </label>
                    <textarea id="mail_body" name="body_content" rows="9" required placeholder="Type your custom email message here..." class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-3 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500"></textarea>
                </div>

                <div class="flex items-center justify-end space-x-3 pt-2">
                    <button type="submit" class="w-full sm:w-auto px-8 py-3 bg-amber-500 hover:bg-amber-600 text-slate-950 font-extrabold text-xs uppercase tracking-wider rounded-2xl transition-all shadow-md hover:shadow-lg inline-flex items-center justify-center space-x-2">
                        <i aria-hidden="true" class="bi bi-send-fill text-sm"></i>
                        <span>Dispatch Email Message</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Right Column: Information & Recipient Guidelines (4 Cols) -->
        <div class="lg:col-span-4 space-y-6">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-sm space-y-4">
                <h3 class="text-sm font-extrabold text-slate-900 dark:text-white flex items-center gap-2">
                    <i aria-hidden="true" class="bi bi-info-circle-fill text-amber-500"></i>
                    <span>Mail Dispatch Features</span>
                </h3>
                <ul class="space-y-3 text-xs text-slate-600 dark:text-slate-400">
                    <li class="flex items-start gap-2">
                        <i aria-hidden="true" class="bi bi-check-circle-fill text-emerald-500 text-sm shrink-0 mt-0.5"></i>
                        <span><strong>Role-Specific Recruitment Routing:</strong> Job vacancies can now be configured with specific recipient email addresses for applicant notifications.</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <i aria-hidden="true" class="bi bi-check-circle-fill text-emerald-500 text-sm shrink-0 mt-0.5"></i>
                        <span><strong>Custom Email Support:</strong> Administrators can type any custom email address to send direct messages to candidates, vendors, or external partners.</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <i aria-hidden="true" class="bi bi-check-circle-fill text-emerald-500 text-sm shrink-0 mt-0.5"></i>
                        <span><strong>Outbox History Logging:</strong> All dispatched emails are stored in database logs for full auditability and inspection.</span>
                    </li>
                </ul>
            </div>
        </div>

    </div>

    <!-- Sent Mails Outbox Log Table -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl shadow-sm overflow-hidden">
        <div class="p-6 border-b border-slate-100 dark:border-slate-800/80 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <h2 class="text-base font-extrabold text-slate-900 dark:text-white">Dispatched Mail History Log</h2>
                <p class="text-xs text-slate-400">Recent email dispatches, candidate notifications, and broadcast messages.</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/50 dark:bg-slate-950/50 border-b border-slate-100 dark:border-slate-800 text-[10px] font-extrabold uppercase tracking-widest text-slate-400">
                        <th class="px-6 py-4">Dispatched At</th>
                        <th class="px-6 py-4">Recipient Name & Email</th>
                        <th class="px-6 py-4">Subject</th>
                        <th class="px-6 py-4">Mail Type</th>
                        <th class="px-6 py-4">Sender</th>
                        <th class="px-6 py-4 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-xs font-semibold">
                    <?php if (empty($mail_history)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-slate-400">
                                <i aria-hidden="true" class="bi bi-inbox text-4xl block mb-2 text-slate-300"></i>
                                No email dispatches recorded in history yet.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($mail_history as $mail): ?>
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-slate-400 text-[11px]">
                                    <?php echo date('M d, Y • H:i', strtotime($mail['sent_at'])); ?>
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="block font-bold text-slate-900 dark:text-white">
                                        <?php echo !empty($mail['recipient_name']) ? htmlspecialchars($mail['recipient_name']) : 'Recipient'; ?>
                                    </span>
                                    <span class="block text-[11px] text-amber-500 font-mono">
                                        <?php echo htmlspecialchars($mail['recipient_email']); ?>
                                    </span>
                                </td>

                                <td class="px-6 py-4">
                                    <span class="block font-bold text-slate-800 dark:text-slate-200 truncate max-w-xs">
                                        <?php echo htmlspecialchars($mail['subject']); ?>
                                    </span>
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-block px-2.5 py-0.5 rounded-full text-[9px] font-extrabold uppercase tracking-wider bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                                        <?php echo str_replace('_', ' ', htmlspecialchars($mail['mail_type'])); ?>
                                    </span>
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap text-slate-500 text-xs">
                                    <?php echo !empty($mail['sender_name']) ? htmlspecialchars($mail['sender_name']) : 'System'; ?>
                                </td>

                                <td class="px-6 py-4 text-center whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[9px] font-extrabold bg-emerald-500/10 text-emerald-500 border border-emerald-500/20 uppercase tracking-wider">
                                        <i aria-hidden="true" class="bi bi-check-circle-fill mr-1"></i> Dispatched
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script>
function toggleRecipientFields(mode) {
    document.getElementById('field_single_user').classList.add('hidden');
    document.getElementById('field_custom_email').classList.add('hidden');
    document.getElementById('field_role_group').classList.add('hidden');

    if (mode === 'single_user') {
        document.getElementById('field_single_user').classList.remove('hidden');
    } else if (mode === 'custom_email') {
        document.getElementById('field_custom_email').classList.remove('hidden');
    } else if (mode === 'role_group') {
        document.getElementById('field_role_group').classList.remove('hidden');
    }
}

function applyTemplatePreset(presetKey) {
    const subjectInput = document.getElementById('mail_subject');
    const bodyInput = document.getElementById('mail_body');

    if (presetKey === 'general_notice') {
        subjectInput.value = "Important Corporate Announcement from Sarkin Mota HQ";
        bodyInput.value = "Dear Valued User,\n\nWe are writing to share an important corporate update regarding our services and operations at Sarkin Mota HQ\n\n[Insert detailed message details here]\n\nIf you have any questions or require assistance, please feel free to reach out to our support team.\n\nWarm regards,\nSarkin Mota HQ Executive Management";
    } else if (presetKey === 'recruitment_update') {
        subjectInput.value = "Update Regarding Your Job Application at Sarkin Mota HQ";
        bodyInput.value = "Dear Candidate,\n\nThank you for applying for a career opening with Sarkin Mota HQ\n\nWe have reviewed your application profile and would like to inform you of the next steps in our recruitment process.\n\n[Insert application update / next steps details here]\n\nThank you for your interest in joining our team.\n\nBest regards,\nHR & Talent Acquisition Team";
    } else if (presetKey === 'interview_schedule') {
        subjectInput.value = "Interview Invitation — Sarkin Mota HQ";
        bodyInput.value = "Dear Candidate,\n\nFollowing our review of your application, we are pleased to invite you to an interview session with our selection panel.\n\nInterview Details:\n- Position:\n- Date & Time:\n- Venue / Meeting Link:\n\nPlease confirm your availability by replying to this email.\n\nBest regards,\nHR & Talent Acquisition Team";
    } else if (presetKey === 'account_notice') {
        subjectInput.value = "Portal Account Status Notice — Sarkin Mota HQ";
        bodyInput.value = "Dear User,\n\nThis is an automated notification regarding your Sarkin Mota HQ Enterprise Portal account.\n\n[Insert account notice details here]\n\nPlease log in to your portal dashboard at your earliest convenience to review your account status.\n\nBest regards,\nSystem Administration";
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
