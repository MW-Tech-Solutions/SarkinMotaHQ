<?php
/**
 * Staff Onboarding & Account Provisioning Module
 * Sarkin Mota HQ — Enterprise Edition
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_helper.php';
require_once __DIR__ . '/../includes/rbac_helper.php';

require_login();
require_permission('hr.onboarding.manage');
$user = get_logged_in_user();

$errors = [];

// 1. Handle Toggle Onboarding Checklist Item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_checklist'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = "CSRF Verification Failed.";
    } else {
        $item_id = intval($_POST['item_id'] ?? 0);
        $is_completed = intval($_POST['is_completed'] ?? 0);

        if ($item_id > 0) {
            try {
                $completed_at = ($is_completed === 1) ? date('Y-m-d H:i:s') : null;
                $completed_by = ($is_completed === 1) ? $user['id'] : null;

                $up = $pdo->prepare("UPDATE onboarding_checklists SET is_completed = ?, completed_at = ?, completed_by = ? WHERE id = ?");
                $up->execute([$is_completed, $completed_at, $completed_by, $item_id]);

                set_flash_message('success', 'Onboarding checklist updated.');
                redirect('onboarding.php');
            } catch (Exception $e) {
                $errors[] = "Failed to update checklist item:  Please try again or contact support.";
            }
        }
    }
}

// 2. Handle Provision Staff Account (Database Transaction Safety)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['provision_staff_account'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = "CSRF Verification Failed.";
    } elseif (!has_permission('hr.staff.create_account')) {
        $errors[] = "Access Denied: You lack permission to provision staff accounts.";
    } else {
        $application_id = intval($_POST['application_id'] ?? 0);
        $target_role = sanitize_input($_POST['role'] ?? 'staff');
        $valid_staff_roles = ['staff', 'hr_manager', 'hr_officer', 'department_manager', 'interviewer'];

        if (!in_array($target_role, $valid_staff_roles)) {
            $errors[] = "Invalid staff role selected. Public/Admin privileges cannot be assigned via HR provisioning.";
        }

        if ($application_id <= 0) $errors[] = "Invalid application reference.";

        if (empty($errors)) {
            try {
                $pdo->beginTransaction();

                // Fetch application & accepted offer
                $stmt_app = $pdo->prepare("
                    SELECT a.*, o.department_id, o.position_title, o.reporting_manager_id, o.start_date, o.status AS offer_status
                    FROM applications a
                    JOIN job_offers o ON a.id = o.application_id
                    WHERE a.id = ?
                ");
                $stmt_app->execute([$application_id]);
                $app_data = $stmt_app->fetch();

                if (!$app_data || $app_data['offer_status'] !== 'accepted') {
                    throw new Exception("Account provisioning requires an accepted job offer.");
                }

                // Check if employee profile already exists for this application
                $chk_emp = $pdo->prepare("SELECT id, user_id FROM employee_profiles WHERE application_id = ?");
                $chk_emp->execute([$application_id]);
                $existing_emp = $chk_emp->fetch();

                $staff_user_id = 0;

                if ($existing_emp) {
                    $staff_user_id = $existing_emp['user_id'];
                } else {
                    // Check if user account with email exists
                    $chk_user = $pdo->prepare("SELECT id, role FROM users WHERE email = ?");
                    $chk_user->execute([$app_data['email']]);
                    $existing_user = $chk_user->fetch();

                    if ($existing_user) {
                        $staff_user_id = $existing_user['id'];
                        // Update role to staff role if normal client
                        $up_u = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
                        $up_u->execute([$target_role, $staff_user_id]);
                    } else {
                        // Create new user account with pending activation status
                        $dummy_hash = password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT);
                        $ins_u = $pdo->prepare("INSERT INTO users (name, email, password_hash, role, status) VALUES (?, ?, ?, ?, 'pending_activation')");
                        $ins_u->execute([$app_data['name'], $app_data['email'], $dummy_hash, $target_role]);
                        $staff_user_id = $pdo->lastInsertId();
                    }

                    // Assign RBAC Role Mapping
                    $role_id = $pdo->query("SELECT id FROM roles WHERE name = '{$target_role}'")->fetchColumn();
                    if ($role_id) {
                        $ins_ur = $pdo->prepare("INSERT IGNORE INTO user_roles (user_id, role_id) VALUES (?, ?)");
                        $ins_ur->execute([$staff_user_id, $role_id]);
                    }

                    // Generate Unique Employee Number (EMP-2026-XXXX)
                    $employee_number = 'EMP-' . date('Y') . '-' . str_pad((string)mt_rand(100, 9999), 4, '0', STR_PAD_LEFT);

                    // Create Employee Profile
                    $ins_ep = $pdo->prepare("
                        INSERT INTO employee_profiles (employee_number, user_id, application_id, department_id, designation, reporting_manager_id, start_date, employment_status)
                        VALUES (?, ?, ?, ?, ?, ?, ?, 'active')
                    ");
                    $ins_ep->execute([
                        $employee_number,
                        $staff_user_id,
                        $application_id,
                        $app_data['department_id'],
                        $app_data['position_title'],
                        $app_data['reporting_manager_id'] ?: null,
                        $app_data['start_date']
                    ]);
                }

                // Generate Single-Use Tokenized Activation Invitation
                $invitation_token = bin2hex(random_bytes(32));
                $inv_expires = date('Y-m-d H:i:s', strtotime('+7 days'));

                $ins_inv = $pdo->prepare("INSERT INTO account_invitations (user_id, token, expires_at, is_used) VALUES (?, ?, ?, 0)");
                $ins_inv->execute([$staff_user_id, $invitation_token, $inv_expires]);

                // Audit log
                $log = $pdo->prepare("INSERT INTO hr_audit_logs (user_id, username, action, entity_type, entity_id, details, ip_address) VALUES (?, ?, 'Provisioned Staff Account', 'user', ?, ?, ?)");
                $log->execute([$user['id'], $user['email'], $staff_user_id, "Role: {$target_role} (Invitation Token Issued)", $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1']);

                $pdo->commit();
                set_flash_message('success', "Staff account provisioned successfully! Activation Token: {$invitation_token}");
                redirect('onboarding.php');
            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                error_log("Provision Account Error: " . $e->getMessage());
                $errors[] = "Failed to provision staff account:  Please try again or contact support.";
            }
        }
    }
}

// Fetch Active Onboarding Applicants & Checklists
$onboarding_candidates = [];
$checklists = [];

try {
    $stmt_cand = $pdo->query("
        SELECT a.*, o.position_title, o.start_date, d.name AS department_name, ep.employee_number, ep.user_id AS created_user_id, ai.token AS activation_token
        FROM applications a
        JOIN job_offers o ON a.id = o.application_id
        JOIN departments d ON o.department_id = d.id
        LEFT JOIN employee_profiles ep ON a.id = ep.application_id
        LEFT JOIN account_invitations ai ON (ep.user_id = ai.user_id AND ai.is_used = 0)
        WHERE o.status = 'accepted'
        ORDER BY a.id DESC
    ");
    $onboarding_candidates = $stmt_cand->fetchAll();

    $stmt_chk = $pdo->query("
        SELECT c.*, a.name AS candidate_name
        FROM onboarding_checklists c
        JOIN applications a ON c.application_id = a.id
        ORDER BY c.application_id DESC, c.id ASC
    ");
    $checklists = $stmt_chk->fetchAll();
} catch (Exception $e) {
    error_log("Fetch Onboarding Error: " . $e->getMessage());
}

$admin_page_title = "Staff Onboarding & Provisioning";
require_once __DIR__ . '/../includes/admin_sidebar.php';
?>

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

<div class="space-y-8">
    
    <!-- Onboarding Candidates List & Account Provisioning -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200/50 dark:border-slate-800 p-8 rounded-3xl shadow-sm">
        <h3 class="text-base font-bold text-slate-900 dark:text-white mb-6">Accepted Offers Ready for Account Provisioning</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-800 text-slate-400 font-semibold uppercase tracking-widest">
                        <th class="py-3 pr-4">Hired Staff Candidate</th>
                        <th class="py-3 px-4">Department & Role</th>
                        <th class="py-3 px-4">Start Date</th>
                        <th class="py-3 px-4">Employee ID</th>
                        <th class="py-3 pl-4 text-right">Account Provisioning</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                    <?php if (empty($onboarding_candidates)): ?>
                        <tr>
                            <td colspan="5" class="py-6 text-center text-slate-400">No accepted job offers waiting for onboarding account setup.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($onboarding_candidates as $cand): ?>
                            <tr>
                                <td class="py-3 pr-4">
                                    <span class="font-bold text-slate-900 dark:text-white block"><?php echo htmlspecialchars($cand['name']); ?></span>
                                    <span class="text-[10px] text-slate-400 block"><?php echo htmlspecialchars($cand['email']); ?></span>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="font-bold text-amber-500 block"><?php echo htmlspecialchars($cand['position_title']); ?></span>
                                    <span class="text-[10px] text-slate-400 block"><?php echo htmlspecialchars($cand['department_name']); ?></span>
                                </td>
                                <td class="py-3 px-4 text-slate-400"><?php echo date('d M Y', strtotime($cand['start_date'])); ?></td>
                                <td class="py-3 px-4 font-mono font-bold text-slate-700 dark:text-slate-200">
                                    <?php echo htmlspecialchars($cand['employee_number'] ?? 'NOT PROVISIONED'); ?>
                                </td>
                                <td class="py-3 pl-4 text-right">
                                    <?php if (!empty($cand['employee_number'])): ?>
                                        <div class="space-y-1">
                                            <span class="px-2.5 py-0.5 rounded-full text-[9px] font-bold uppercase bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300">
                                                Provisioned
                                            </span>
                                            <?php if (!empty($cand['activation_token'])): ?>
                                                <a href="../activate-account.php?token=<?php echo urlencode($cand['activation_token']); ?>" target="_blank" class="block text-[10px] font-bold text-amber-500 hover:underline">
                                                    Activation Link
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <form method="POST" action="onboarding.php" class="inline-flex space-x-2">
                                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                            <input type="hidden" name="provision_staff_account" value="1">
                                            <input type="hidden" name="application_id" value="<?php echo $cand['id']; ?>">
                                            
                                            <select name="role" class="bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg px-2 py-1 text-[10px] focus:outline-none">
                                                <option value="staff">Staff</option>
                                                <option value="hr_officer">HR Officer</option>
                                                <option value="hr_manager">HR Manager</option>
                                                <option value="department_manager">Dept Manager</option>
                                                <option value="interviewer">Interviewer</option>
                                            </select>

                                            <button type="submit" class="px-3 py-1 bg-amber-500 text-slate-950 text-[10px] font-bold rounded-lg hover:bg-amber-600 shadow-sm">
                                                Create Staff Account
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Interactive Onboarding Checklists -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200/50 dark:border-slate-800 p-8 rounded-3xl shadow-sm">
        <h3 class="text-base font-bold text-slate-900 dark:text-white mb-6">Staff Onboarding & Orientation Checklists</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-800 text-slate-400 font-semibold uppercase tracking-widest">
                        <th class="py-3 pr-4">Candidate / Employee</th>
                        <th class="py-3 px-4">Checklist Task Item</th>
                        <th class="py-3 px-4">Category</th>
                        <th class="py-3 px-4">Completion Status</th>
                        <th class="py-3 pl-4 text-right">Toggle Complete</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                    <?php if (empty($checklists)): ?>
                        <tr>
                            <td colspan="5" class="py-6 text-center text-slate-400">No active onboarding tasks assigned.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($checklists as $chk): ?>
                            <tr>
                                <td class="py-3 pr-4 font-bold text-slate-900 dark:text-white"><?php echo htmlspecialchars($chk['candidate_name']); ?></td>
                                <td class="py-3 px-4 text-slate-700 dark:text-slate-300 font-medium"><?php echo htmlspecialchars($chk['item_name']); ?></td>
                                <td class="py-3 px-4 font-mono uppercase text-amber-500"><?php echo htmlspecialchars($chk['category']); ?></td>
                                <td class="py-3 px-4">
                                    <?php if ($chk['is_completed'] == 1): ?>
                                        <span class="px-2.5 py-0.5 rounded-full text-[9px] font-bold uppercase bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300">
                                            Completed (<?php echo date('d M', strtotime($chk['completed_at'])); ?>)
                                        </span>
                                    <?php else: ?>
                                        <span class="px-2.5 py-0.5 rounded-full text-[9px] font-bold uppercase bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300">
                                            Pending
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 pl-4 text-right">
                                    <form method="POST" action="onboarding.php" class="inline">
                                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                        <input type="hidden" name="toggle_checklist" value="1">
                                        <input type="hidden" name="item_id" value="<?php echo $chk['id']; ?>">
                                        <input type="hidden" name="is_completed" value="<?php echo ($chk['is_completed'] == 1) ? 0 : 1; ?>">
                                        
                                        <button type="submit" class="px-2.5 py-1 <?php echo ($chk['is_completed'] == 1) ? 'bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300' : 'bg-emerald-500 text-white'; ?> text-[10px] font-bold rounded-lg">
                                            <?php echo ($chk['is_completed'] == 1) ? 'Mark Pending' : 'Mark Complete'; ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

</main>
</div>
</body>
</html>
