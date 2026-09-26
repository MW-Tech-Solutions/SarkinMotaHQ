<?php
/**
 * Job Offer Management & Generation Module
 * Sarkin Mota HQ — Enterprise Edition
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_helper.php';
require_once __DIR__ . '/../includes/rbac_helper.php';

require_login();
require_permission('hr.offers.create');
$user = get_logged_in_user();

$errors = [];
$can_view_compensation = has_permission('hr.offers.view_compensation');
$preselect_app_id = intval($_GET['app_id'] ?? 0);

// Handle Offer Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = "CSRF Token Validation Failed.";
    } else {
        // Create / Prepare Job Offer
        if (isset($_POST['create_offer'])) {
            $application_id = intval($_POST['application_id'] ?? 0);
            $position_title = sanitize_input($_POST['position_title'] ?? '');
            $department_id = intval($_POST['department_id'] ?? 0);
            $reporting_manager_id = intval($_POST['reporting_manager_id'] ?? 0);
            $employment_type = sanitize_input($_POST['employment_type'] ?? 'Full-Time');
            $start_date = sanitize_input($_POST['start_date'] ?? '');
            $compensation_amount = floatval($_POST['compensation_amount'] ?? 0);
            $expiry_days = intval($_POST['expiry_days'] ?? 7);

            if ($application_id <= 0) $errors[] = "Please select a candidate application.";
            if (empty($position_title)) $errors[] = "Position title is required.";
            if ($department_id <= 0) $errors[] = "Please select a corporate department.";
            if (empty($start_date)) $errors[] = "Official start date is required.";
            if ($compensation_amount <= 0 && $can_view_compensation) $errors[] = "Please specify a valid compensation amount.";

            if (empty($errors)) {
                try {
                    $token = bin2hex(random_bytes(32));
                    $expires_at = date('Y-m-d H:i:s', strtotime("+{$expiry_days} days"));

                    $stmt = $pdo->prepare("
                        INSERT INTO job_offers (application_id, position_title, department_id, reporting_manager_id, employment_type, start_date, compensation_amount, token, expires_at, status, created_by)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'issued', ?)
                        ON DUPLICATE KEY UPDATE 
                        position_title = VALUES(position_title), department_id = VALUES(department_id), reporting_manager_id = VALUES(reporting_manager_id),
                        employment_type = VALUES(employment_type), start_date = VALUES(start_date), compensation_amount = VALUES(compensation_amount),
                        token = VALUES(token), expires_at = VALUES(expires_at), status = 'issued'
                    ");
                    $stmt->execute([$application_id, $position_title, $department_id, $reporting_manager_id ?: null, $employment_type, $start_date, $compensation_amount, $token, $expires_at, $user['id']]);
                    $offer_id = $pdo->lastInsertId();

                    $log = $pdo->prepare("INSERT INTO hr_audit_logs (user_id, username, action, entity_type, entity_id, details, ip_address) VALUES (?, ?, 'Generated Job Offer', 'job_offer', ?, ?, ?)");
                    $log->execute([$user['id'], $user['email'], $offer_id ?: $application_id, "Position: {$position_title} (Token Issued)", $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1']);

                    set_flash_message('success', 'Employment offer letter generated and issued successfully.');
                    redirect('offers.php');
                } catch (Exception $e) {
                    error_log("Create Offer Error: " . $e->getMessage());
                    $errors[] = "Failed to generate job offer:  Please try again or contact support.";
                }
            }
        }

        // Approve Job Offer (HR Approval Workflow)
        if (isset($_POST['approve_offer'])) {
            $offer_id = intval($_POST['offer_id'] ?? 0);
            if ($offer_id > 0) {
                if (!has_permission('hr.offers.approve')) {
                    $errors[] = "Access Denied: You lack permission to approve job offers.";
                } else {
                    try {
                        $up = $pdo->prepare("UPDATE job_offers SET status = 'approved', approved_by = ? WHERE id = ?");
                        $up->execute([$user['id'], $offer_id]);

                        $log = $pdo->prepare("INSERT INTO hr_audit_logs (user_id, username, action, entity_type, entity_id, details, ip_address) VALUES (?, ?, 'Approved Job Offer', 'job_offer', ?, 'Approved by HR Executive', ?)");
                        $log->execute([$user['id'], $user['email'], $offer_id, $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1']);

                        set_flash_message('success', 'Job offer approved.');
                        redirect('offers.php');
                    } catch (Exception $e) {
                        $errors[] = "Failed to approve offer:  Please try again or contact support.";
                    }
                }
            }
        }
    }
}

// Fetch Offers, Shortlisted Applicants, Departments & Managers
$offers = [];
$shortlisted_applicants = [];
$departments = [];
$managers = [];

try {
    $stmt_off = $pdo->query("
        SELECT o.*, a.name AS candidate_name, a.email AS candidate_email, d.name AS department_name, u.name AS manager_name
        FROM job_offers o
        JOIN applications a ON o.application_id = a.id
        LEFT JOIN departments d ON o.department_id = d.id
        LEFT JOIN users u ON o.reporting_manager_id = u.id
        ORDER BY o.id DESC
    ");
    $offers = $stmt_off->fetchAll();

    $shortlisted_applicants = $pdo->query("SELECT a.id, a.name, a.email, c.title AS job_title, c.department_id FROM applications a LEFT JOIN careers c ON a.job_id = c.id WHERE a.status IN ('shortlisted', 'interviewing', 'hired') ORDER BY a.id DESC")->fetchAll();
    $departments = $pdo->query("SELECT * FROM departments ORDER BY name ASC")->fetchAll();
    $managers = $pdo->query("SELECT id, name, role FROM users WHERE role IN ('super_admin', 'admin', 'hr_manager', 'department_manager', 'staff') ORDER BY name ASC")->fetchAll();
} catch (Exception $e) {
    error_log("Fetch Offers Error: " . $e->getMessage());
}

$admin_page_title = "Job Offers & Approvals Management";
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

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    
    <!-- Offers Table Roster (Left 2 Cols) -->
    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white dark:bg-slate-900 border border-slate-200/50 dark:border-slate-800 p-8 rounded-3xl shadow-sm">
            <h3 class="text-base font-bold text-slate-900 dark:text-white mb-6">Issued Employment Offer Letters</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 text-slate-400 font-semibold uppercase tracking-widest">
                            <th class="py-3 pr-4">Candidate / Position</th>
                            <th class="py-3 px-4">Department</th>
                            <th class="py-3 px-4">Compensation</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 pl-4 text-right">Offer Link / Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                        <?php if (empty($offers)): ?>
                            <tr>
                                <td colspan="5" class="py-6 text-center text-slate-400">No job offers generated yet. Use the form to prepare a formal offer letter.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($offers as $off): ?>
                                <tr>
                                    <td class="py-3 pr-4">
                                        <span class="font-bold text-slate-900 dark:text-white block"><?php echo htmlspecialchars($off['candidate_name']); ?></span>
                                        <span class="text-[10px] text-slate-400 block"><?php echo htmlspecialchars($off['position_title']); ?> • Start: <?php echo date('d M Y', strtotime($off['start_date'])); ?></span>
                                    </td>
                                    <td class="py-3 px-4 text-slate-400 font-medium"><?php echo htmlspecialchars($off['department_name'] ?? 'Corporate'); ?></td>
                                    <td class="py-3 px-4 font-bold text-emerald-500">
                                        <?php if ($can_view_compensation): ?>
                                            <?php echo format_currency($off['compensation_amount']); ?>
                                        <?php else: ?>
                                            <span class="text-slate-400 text-[10px] font-mono">CONFIDENTIAL</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3 px-4">
                                        <?php
                                        $badge_class = 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400';
                                        if ($off['status'] === 'accepted') $badge_class = 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300';
                                        if ($off['status'] === 'issued' || $off['status'] === 'approved') $badge_class = 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300';
                                        if ($off['status'] === 'declined' || $off['status'] === 'expired') $badge_class = 'bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300';
                                        ?>
                                        <span class="px-2.5 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider <?php echo $badge_class; ?>">
                                            <?php echo htmlspecialchars($off['status']); ?>
                                        </span>
                                    </td>
                                    <td class="py-3 pl-4 text-right">
                                        <div class="flex justify-end items-center gap-1.5">
                                            <a href="download-offer-pdf.php?id=<?php echo $off['id']; ?>&pdf=1" target="_blank" class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white text-[10px] font-bold rounded-lg inline-flex items-center space-x-1 shadow-sm">
                                                <i aria-hidden="true" class="bi bi-file-earmark-pdf-fill"></i>
                                                <span>Download PDF</span>
                                            </a>
                                            <a href="../offer-response.php?token=<?php echo urlencode($off['token']); ?>" target="_blank" class="px-2.5 py-1 bg-amber-500 hover:bg-amber-600 text-slate-950 text-[10px] font-bold rounded-lg inline-flex items-center space-x-1 shadow-sm">
                                                <i aria-hidden="true" class="bi bi-box-arrow-up-right"></i>
                                                <span>Online Portal</span>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Generate Job Offer Form (Right 1 Col) -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200/50 dark:border-slate-800 p-8 rounded-3xl shadow-sm h-fit space-y-6">
        <h3 class="text-base font-bold text-slate-900 dark:text-white">Prepare Offer Letter</h3>
        <form method="POST" action="offers.php" class="space-y-4 text-xs">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            <input type="hidden" name="create_offer" value="1">

            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Shortlisted Candidate</label>
                <select name="application_id" required class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:border-amber-500">
                    <option value="">Select Candidate...</option>
                    <?php foreach ($shortlisted_applicants as $cand): ?>
                        <option value="<?php echo $cand['id']; ?>" <?php echo ($preselect_app_id === $cand['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cand['name']); ?> (<?php echo htmlspecialchars($cand['job_title']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Official Position Title</label>
                <input type="text" name="position_title" required placeholder="e.g. Senior Agronomy Consultant" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:border-amber-500">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Assigned Department</label>
                    <select name="department_id" required class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:border-amber-500">
                        <option value="">Select Department...</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Employment Type</label>
                    <select name="employment_type" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:border-amber-500">
                        <option value="Full-Time">Full-Time</option>
                        <option value="Part-Time">Part-Time</option>
                        <option value="Contract">Contract</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Reporting Manager</label>
                <select name="reporting_manager_id" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:border-amber-500">
                    <option value="0">Unassigned</option>
                    <?php foreach ($managers as $m): ?>
                        <option value="<?php echo $m['id']; ?>"><?php echo htmlspecialchars($m['name']); ?> (<?php echo htmlspecialchars($m['role']); ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Official Start Date</label>
                    <input type="date" name="start_date" required class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:border-amber-500">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Offer Expiry (Days)</label>
                    <input type="number" name="expiry_days" value="7" min="1" max="30" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:border-amber-500">
                </div>
            </div>

            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Approved Compensation (Annual Salary ₦)</label>
                <?php if ($can_view_compensation): ?>
                    <input type="number" step="1000" name="compensation_amount" required placeholder="e.g. 4800000" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:border-amber-500 font-bold text-emerald-500">
                <?php else: ?>
                    <input type="text" disabled value="Restricted (HR Compensation Rights Required)" class="w-full bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-xs text-slate-400">
                <?php endif; ?>
            </div>

            <button type="submit" class="w-full bg-amber-500 text-slate-950 font-bold uppercase text-xs tracking-wider py-3 rounded-xl hover:bg-amber-600 transition-colors flex items-center justify-center space-x-2 shadow-sm">
                <i aria-hidden="true" class="bi bi-file-earmark-check-fill"></i>
                <span>Generate & Issue Offer Letter</span>
            </button>
        </form>
    </div>

</div>

</main>
</div>
</body>
</html>
