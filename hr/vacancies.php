<?php
/**
 * Vacancy Management Workflow Module
 * Sarkin Mota HQ — Enterprise Edition
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_helper.php';
require_once __DIR__ . '/../includes/rbac_helper.php';

require_login();
require_permission('hr.vacancies.manage');
$user = get_logged_in_user();

$errors = [];

// Handle Vacancy Operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = "CSRF Token Validation Failed.";
    } else {
        // Create / Update Vacancy
        if (isset($_POST['save_vacancy'])) {
            $title = sanitize_input($_POST['title'] ?? '');
            $department_id = intval($_POST['department_id'] ?? 0);
            $employment_type = sanitize_input($_POST['employment_type'] ?? 'full_time');
            $location = sanitize_input($_POST['location'] ?? '');
            $openings_count = intval($_POST['openings_count'] ?? 1);
            $closing_date = !empty($_POST['closing_date']) ? sanitize_input($_POST['closing_date']) : null;
            $hiring_manager_id = intval($_POST['hiring_manager_id'] ?? 0);
            $notification_email = filter_var($_POST['notification_email'] ?? '', FILTER_VALIDATE_EMAIL) ? sanitize_input($_POST['notification_email']) : null;
            $description = sanitize_input($_POST['description'] ?? '');
            $requirements = sanitize_input($_POST['requirements'] ?? '');
            $responsibilities = sanitize_input($_POST['responsibilities'] ?? '');
            $status = sanitize_input($_POST['status'] ?? 'draft');

            if (empty($title)) $errors[] = "Job title is required.";
            if ($department_id <= 0) $errors[] = "Please assign a valid corporate department.";

            if (empty($errors)) {
                try {
                    $dept_name = $pdo->query("SELECT name FROM departments WHERE id = {$department_id}")->fetchColumn() ?: 'General';
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO careers (title, department, department_id, employment_type, location, openings_count, closing_date, hiring_manager_id, notification_email, description, requirements, responsibilities, status, created_by)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$title, $dept_name, $department_id, $employment_type, $location, $openings_count, $closing_date, $hiring_manager_id ?: null, $notification_email, $description, $requirements, $responsibilities, $status, $user['id']]);
                    $vacancy_id = $pdo->lastInsertId();

                    $log = $pdo->prepare("INSERT INTO hr_audit_logs (user_id, username, action, entity_type, entity_id, details, ip_address) VALUES (?, ?, 'Created Vacancy Posting', 'vacancy', ?, ?, ?)");
                    $log->execute([$user['id'], $user['email'], $vacancy_id, "Title: {$title} (Status: {$status})", $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1']);

                    set_flash_message('success', "Vacancy '{$title}' created successfully.");
                    redirect('vacancies.php');
                } catch (Exception $e) {
                    error_log("Save Vacancy Error: " . $e->getMessage());
                    $errors[] = "Failed to save vacancy:  Please try again or contact support.";
                }
            }
        }

        // Update Vacancy Status (Draft -> Approval -> Published -> Closed -> Cancelled)
        if (isset($_POST['update_status'])) {
            $vacancy_id = intval($_POST['vacancy_id'] ?? 0);
            $new_status = sanitize_input($_POST['new_status'] ?? '');
            $valid_statuses = ['draft', 'approval_pending', 'published', 'closed', 'cancelled'];

            if (in_array($new_status, $valid_statuses) && $vacancy_id > 0) {
                if ($new_status === 'published' && !has_permission('hr.vacancies.approve')) {
                    $errors[] = "Access Denied: You lack approval permission to publish vacancies.";
                } else {
                    try {
                        $up = $pdo->prepare("UPDATE careers SET status = ? WHERE id = ?");
                        $up->execute([$new_status, $vacancy_id]);

                        $log = $pdo->prepare("INSERT INTO hr_audit_logs (user_id, username, action, entity_type, entity_id, details, ip_address) VALUES (?, ?, 'Updated Vacancy Status', 'vacancy', ?, ?, ?)");
                        $log->execute([$user['id'], $user['email'], $vacancy_id, "Status updated to " . strtoupper($new_status), $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1']);

                        set_flash_message('success', "Vacancy status updated to " . strtoupper($new_status) . ".");
                        redirect('vacancies.php');
                    } catch (Exception $e) {
                        $errors[] = "Failed to update status:  Please try again or contact support.";
                    }
                }
            }
        }
    }
}

// Fetch Vacancies, Departments & Managers
$vacancies = [];
$departments = [];
$hiring_managers = [];

try {
    $stmt_vac = $pdo->query("
        SELECT c.*, d.name AS department_name, u.name AS hiring_manager_name,
        (SELECT COUNT(*) FROM applications a WHERE a.job_id = c.id) AS applicants_count
        FROM careers c
        LEFT JOIN departments d ON c.department_id = d.id
        LEFT JOIN users u ON c.hiring_manager_id = u.id
        ORDER BY c.id DESC
    ");
    $vacancies = $stmt_vac->fetchAll();

    $departments = $pdo->query("SELECT * FROM departments ORDER BY name ASC")->fetchAll();
    $hiring_managers = $pdo->query("SELECT id, name, role FROM users WHERE role IN ('super_admin', 'admin', 'hr_manager', 'department_manager', 'staff') ORDER BY name ASC")->fetchAll();
} catch (Exception $e) {
    error_log("Fetch Vacancies Error: " . $e->getMessage());
}

$admin_page_title = "Vacancy Management Workflow";
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
    
    <!-- Vacancy List (Left 2 Cols) -->
    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white dark:bg-slate-900 border border-slate-200/50 dark:border-slate-800 p-8 rounded-3xl shadow-sm">
            <h3 class="text-base font-bold text-slate-900 dark:text-white mb-6">Corporate Job Vacancies Catalog</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 text-slate-400 font-semibold uppercase tracking-widest">
                            <th class="py-3 pr-4">Position</th>
                            <th class="py-3 px-4">Department</th>
                            <th class="py-3 px-4">Openings</th>
                            <th class="py-3 px-4">Applicants</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 pl-4 text-right">Workflow</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                        <?php if (empty($vacancies)): ?>
                            <tr>
                                <td colspan="6" class="py-6 text-center text-slate-400">No vacancies created yet. Use the form to post a new opening.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($vacancies as $vac): ?>
                                <tr>
                                    <td class="py-3 pr-4">
                                        <span class="font-bold text-slate-900 dark:text-white block"><?php echo htmlspecialchars($vac['title']); ?></span>
                                        <span class="text-[10px] text-slate-400 block"><?php echo htmlspecialchars($vac['location']); ?> • <?php echo htmlspecialchars(str_replace('_', ' ', $vac['employment_type'])); ?></span>
                                    </td>
                                    <td class="py-3 px-4 text-slate-400 font-medium"><?php echo htmlspecialchars($vac['department_name'] ?? $vac['department']); ?></td>
                                    <td class="py-3 px-4 font-bold text-slate-700 dark:text-slate-200"><?php echo intval($vac['openings_count']); ?></td>
                                    <td class="py-3 px-4 font-bold text-amber-500"><?php echo intval($vac['applicants_count']); ?></td>
                                    <td class="py-3 px-4">
                                        <?php
                                        $vac_status = !empty($vac['status']) ? strtolower($vac['status']) : 'published';
                                        $badge_class = 'bg-emerald-100 text-emerald-800 border border-emerald-300';
                                        if ($vac_status === 'published' || $vac_status === 'open') {
                                            $badge_class = 'bg-emerald-100 text-emerald-800 border border-emerald-300';
                                        } elseif ($vac_status === 'approval_pending' || $vac_status === 'draft') {
                                            $badge_class = 'bg-amber-100 text-amber-800 border border-amber-300';
                                        } elseif ($vac_status === 'closed' || $vac_status === 'cancelled') {
                                            $badge_class = 'bg-rose-100 text-rose-800 border border-rose-300';
                                        }
                                        ?>
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider <?php echo $badge_class; ?>">
                                            <?php echo htmlspecialchars($vac_status); ?>
                                        </span>
                                    </td>
                                    <td class="py-3 pl-4 text-right">
                                        <form method="POST" action="vacancies.php" class="inline-flex space-x-1">
                                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                            <input type="hidden" name="update_status" value="1">
                                            <input type="hidden" name="vacancy_id" value="<?php echo $vac['id']; ?>">
                                            
                                            <?php if ($vac_status === 'draft' || $vac_status === 'approval_pending'): ?>
                                                <button type="submit" name="new_status" value="published" class="px-2.5 py-1 bg-amber-500 text-slate-950 text-[10px] font-bold rounded-lg hover:bg-amber-600 shadow-sm">
                                                    Publish
                                                </button>
                                            <?php endif; ?>
                                            
                                            <?php if ($vac_status === 'published' || $vac_status === 'open'): ?>
                                                <button type="submit" name="new_status" value="closed" class="px-2.5 py-1 bg-rose-500 text-white text-[10px] font-bold rounded-lg hover:bg-rose-600 shadow-sm">
                                                    Close Vacancy
                                                </button>
                                            <?php endif; ?>

                                            <?php if ($vac_status === 'closed' || $vac_status === 'cancelled'): ?>
                                                <button type="submit" name="new_status" value="published" class="px-2.5 py-1 bg-emerald-600 text-white text-[10px] font-bold rounded-lg hover:bg-emerald-700 shadow-sm">
                                                    Re-open
                                                </button>
                                            <?php endif; ?>
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

    <!-- Create Vacancy Form (Right 1 Col) -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200/50 dark:border-slate-800 p-8 rounded-3xl shadow-sm h-fit space-y-6">
        <h3 class="text-base font-bold text-slate-900 dark:text-white">Create New Vacancy Posting</h3>
        <form method="POST" action="vacancies.php" class="space-y-4 text-xs">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            <input type="hidden" name="save_vacancy" value="1">
            
            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Job Title</label>
                <input type="text" name="title" required placeholder="e.g. Senior Property Valuer" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:border-amber-500">
            </div>

            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Department</label>
                <select name="department_id" required class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:border-amber-500">
                    <option value="">Select Department...</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Employment Type</label>
                    <select name="employment_type" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:border-amber-500">
                        <option value="full_time">Full-Time</option>
                        <option value="part_time">Part-Time</option>
                        <option value="contract">Contract</option>
                        <option value="internship">Internship</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Openings Count</label>
                    <input type="number" name="openings_count" value="1" min="1" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:border-amber-500">
                </div>
            </div>

            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Office Location</label>
                <input type="text" name="location" required placeholder="e.g. Jimeta-Yola Office" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:border-amber-500">
            </div>

            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Assigned Hiring Manager</label>
                <select name="hiring_manager_id" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:border-amber-500">
                    <option value="0">Unassigned</option>
                    <?php foreach ($hiring_managers as $hm): ?>
                        <option value="<?php echo $hm['id']; ?>"><?php echo htmlspecialchars($hm['name']); ?> (<?php echo htmlspecialchars($hm['role']); ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Notification Recipient Email</label>
                <input type="email" name="notification_email" placeholder="e.g. hr.legal@sarkinmotahq.com (Optional)" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:border-amber-500">
                <span class="text-[9px] text-slate-400 mt-1 block">Specify custom email address to receive application alerts for this role.</span>
            </div>

            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Closing Date</label>
                <input type="date" name="closing_date" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:border-amber-500">
            </div>

            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Initial Workflow Status</label>
                <select name="status" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:border-amber-500">
                    <option value="draft">Draft (Saved Internally)</option>
                    <option value="published" selected>Published (Publicly Listed)</option>
                    <option value="approval_pending">Pending Approval</option>
                </select>
            </div>

            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Job Overview & Description</label>
                <textarea name="description" rows="3" placeholder="Summary of role..." class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:border-amber-500"></textarea>
            </div>

            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Requirements & Qualifications</label>
                <textarea name="requirements" rows="3" placeholder="Key skills & certifications..." class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:border-amber-500"></textarea>
            </div>

            <button type="submit" class="w-full bg-amber-500 text-slate-950 font-bold uppercase text-xs tracking-wider py-3 rounded-xl hover:bg-amber-600 transition-colors flex items-center justify-center space-x-2 shadow-sm">
                <i aria-hidden="true" class="bi bi-briefcase-fill"></i>
                <span>Save Vacancy Posting</span>
            </button>
        </form>
    </div>

</div>

</main>
</div>
</body>
</html>
