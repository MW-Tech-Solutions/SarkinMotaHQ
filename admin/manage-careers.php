<?php
/**
 * Admin Career & Candidate Application Manager
 * Sarkin Mota HQ — Enterprise Edition
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_helper.php';
require_once __DIR__ . '/../includes/rbac_helper.php';

require_role(['admin', 'staff']);
require_permission('careers.manage');
$user = get_logged_in_user();

$errors = [];

// Handle job vacancy creation & deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = "CSRF Verification Failed.";
    } else {
        if (isset($_POST['add_vacancy'])) {
            $title = sanitize_input($_POST['title'] ?? '');
            $department = sanitize_input($_POST['department'] ?? '');
            $location = sanitize_input($_POST['location'] ?? '');
            $notification_email = filter_var($_POST['notification_email'] ?? '', FILTER_VALIDATE_EMAIL) ? sanitize_input($_POST['notification_email']) : null;
            $description = sanitize_input($_POST['description'] ?? '');
            $requirements = sanitize_input($_POST['requirements'] ?? '');
            
            if (empty($title)) $errors[] = "Title is required.";
            
            if (empty($errors)) {
                try {
                    $stmt = $pdo->prepare("INSERT INTO careers (title, department, location, notification_email, description, requirements, status) VALUES (?, ?, ?, ?, ?, ?, 'published')");
                    $stmt->execute([$title, $department, $location, $notification_email, $description, $requirements]);

                    $log = $pdo->prepare("INSERT INTO audit_logs (username, action, ip_address) VALUES (?, ?, ?)");
                    $log->execute([$user['email'], "Created Job Vacancy: " . $title, $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1']);

                    set_flash_message('success', 'Job posting created successfully.');
                    redirect('manage-careers.php');
                } catch (Exception $e) {
                    error_log("Vacancy Add Error: " . $e->getMessage());
                    $errors[] = "Failed to create job posting.";
                }
            }
        }
        
        // Handle deletion of posting
        if (isset($_POST['delete_vacancy'])) {
            $vacancy_id = intval($_POST['vacancy_id'] ?? 0);
            try {
                $stmt = $pdo->prepare("DELETE FROM careers WHERE id = ?");
                $stmt->execute([$vacancy_id]);

                $log = $pdo->prepare("INSERT INTO audit_logs (username, action, ip_address) VALUES (?, ?, ?)");
                $log->execute([$user['email'], "Deleted Vacancy ID: " . $vacancy_id, $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1']);

                set_flash_message('success', 'Job posting deleted.');
                redirect('manage-careers.php');
            } catch (Exception $e) {
                error_log("Vacancy Delete Error: " . $e->getMessage());
                $errors[] = "Failed to delete vacancy.";
            }
        }
    }
}

// Fetch vacancies and applications
$vacancies = [];
$applications = [];
try {
    $stmt = $pdo->query("SELECT * FROM careers ORDER BY id DESC");
    $vacancies = $stmt->fetchAll();
    
    $stmt2 = $pdo->query("SELECT a.*, c.title AS job_title FROM applications a LEFT JOIN careers c ON a.job_id = c.id ORDER BY a.id DESC");
    $applications = $stmt2->fetchAll();
} catch (Exception $e) {
    error_log("Fetch Applications Error: " . $e->getMessage());
}

$admin_page_title = 'Career Vacancies & Applications';
require_once __DIR__ . '/../includes/admin_sidebar.php';
?>

<?php echo display_flash_message(); ?>

<?php if (!empty($errors)): ?>
    <div class="mb-8 border-l-4 border-rose-500 bg-rose-50 dark:bg-rose-950/20 p-4 rounded-r-md">
        <ul class="list-disc pl-5 text-xs text-rose-800 dark:text-rose-300 space-y-1">
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-12">
    
    <!-- Left Side: CV Applications & Active Positions -->
    <div class="lg:col-span-2 space-y-10">
        
        <!-- Applications List -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200/50 dark:border-slate-800 rounded-3xl p-8 shadow-sm">
            <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-6">Job Applications Received</h3>
            
            <div class="space-y-6">
                <?php if (empty($applications)): ?>
                    <p class="text-xs text-slate-400">No applications received yet.</p>
                <?php else: ?>
                    <?php foreach ($applications as $app): ?>
                        <div class="border border-slate-200/80 dark:border-slate-800/80 p-6 rounded-2xl">
                            <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                                <div>
                                    <h4 class="font-bold text-slate-900 dark:text-white text-sm"><?php echo htmlspecialchars($app['name']); ?></h4>
                                    <p class="text-[10px] text-slate-400">Applied for: <span class="text-amber-500 font-semibold"><?php echo htmlspecialchars($app['job_title'] ?: 'General Application'); ?></span></p>
                                </div>
                                <!-- Authorized Private Download Route (F10 Resolution) -->
                                <a href="../download.php?type=resume&id=<?php echo $app['id']; ?>" class="px-4 py-1.5 bg-amber-500 hover:bg-amber-600 text-slate-950 text-[10px] font-bold uppercase rounded-md transition-colors flex items-center shadow-sm">
                                    <i aria-hidden="true" class="bi bi-shield-lock-fill mr-1"></i> Download Private CV
                                </a>
                            </div>
                            <div class="text-xs text-slate-500 dark:text-slate-400 space-y-1 mb-4">
                                <p><i aria-hidden="true" class="bi bi-envelope text-slate-400 mr-1"></i> Email: <?php echo htmlspecialchars($app['email']); ?></p>
                                <p><i aria-hidden="true" class="bi bi-telephone text-slate-400 mr-1"></i> Phone: <?php echo htmlspecialchars($app['phone']); ?></p>
                                <?php if (!empty($app['cover_letter'])): ?>
                                    <p class="mt-2 text-[11px] italic bg-slate-50 dark:bg-slate-950 p-2.5 rounded-lg">"<?php echo htmlspecialchars($app['cover_letter']); ?>"</p>
                                <?php endif; ?>
                            </div>
                            <span class="text-[9px] text-slate-400 block text-right">Submitted at: <?php echo date('d M Y, H:i', strtotime($app['submitted_at'])); ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Active Vacancies List -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200/50 dark:border-slate-800 rounded-3xl p-8 shadow-sm">
            <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-6">Active Vacancies</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 text-slate-400 font-semibold uppercase tracking-widest">
                            <th class="py-3 pr-4">Vacancy Title</th>
                            <th class="py-3 px-4">Department</th>
                            <th class="py-3 px-4">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                        <?php if (empty($vacancies)): ?>
                            <tr><td colspan="3" class="py-4 text-slate-400 text-center">No vacancies posted.</td></tr>
                        <?php else: ?>
                            <?php foreach ($vacancies as $vac): ?>
                                <tr>
                                    <td class="py-3 pr-4 font-semibold text-slate-800 dark:text-slate-200"><?php echo htmlspecialchars($vac['title']); ?></td>
                                    <td class="py-3 px-4 text-slate-500 capitalize"><?php echo htmlspecialchars($vac['department']); ?></td>
                                    <td class="py-3 px-4">
                                        <form action="manage-careers.php" method="POST" onsubmit="return confirm('Delete this job posting?');">
                                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                            <input type="hidden" name="vacancy_id" value="<?php echo $vac['id']; ?>">
                                            <button type="submit" name="delete_vacancy" class="text-rose-500 hover:text-rose-700 text-xs font-semibold"><i class="bi bi-trash ui-icon" aria-hidden="true"></i>Delete</button>
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

    <!-- Right Side: Post New Vacancy Form -->
    <div>
        <div class="bg-white dark:bg-slate-900 border border-slate-200/50 dark:border-slate-800 rounded-3xl p-8 shadow-sm sticky top-8">
            <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-6">Post New Vacancy</h3>
            
            <form action="manage-careers.php" method="POST" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Job Title</label>
                    <input type="text" name="title" required class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg px-3 py-2 text-xs text-slate-900 dark:text-white">
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Department</label>
                    <input type="text" name="department" placeholder="e.g. Engineering, Sales..." class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg px-3 py-2 text-xs text-slate-900 dark:text-white">
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Location</label>
                    <input type="text" name="location" placeholder="e.g. Lagos, Abuja, Remote..." class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg px-3 py-2 text-xs text-slate-900 dark:text-white">
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Notification Recipient Email</label>
                    <input type="email" name="notification_email" placeholder="e.g. hr.engineering@sarkinmotahq.com (Optional)" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg px-3 py-2 text-xs text-slate-900 dark:text-white">
                    <span class="text-[9px] text-slate-400 mt-1 block">Specify custom email address to receive application alerts for this role.</span>
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Job Description</label>
                    <textarea name="description" rows="3" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg px-3 py-2 text-xs text-slate-900 dark:text-white"></textarea>
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Requirements</label>
                    <textarea name="requirements" rows="3" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg px-3 py-2 text-xs text-slate-900 dark:text-white"></textarea>
                </div>

                <button type="submit" name="add_vacancy" class="w-full bg-amber-500 text-slate-950 font-bold text-xs py-3 rounded-lg uppercase tracking-wider hover:bg-amber-600 transition-colors shadow">
                    Publish Vacancy
                </button>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
