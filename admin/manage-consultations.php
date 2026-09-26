<?php
/**
 * Admin Consultation Requests Manager
 * Sarkin Mota HQ — Enterprise Edition
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_helper.php';
require_once __DIR__ . '/../includes/rbac_helper.php';

require_permission('consultations.manage');
$user = get_logged_in_user();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_consultation_status'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = "CSRF Verification Failed.";
    } else {
        $cons_id = intval($_POST['consultation_id']);
        $new_status = sanitize_input($_POST['new_status']);
        $valid = ['unread', 'contacted', 'in_progress', 'completed'];

        if (in_array($new_status, $valid) && $cons_id > 0) {
            try {
                $up = $pdo->prepare("UPDATE consultation_requests SET status = ? WHERE id = ?");
                $up->execute([$new_status, $cons_id]);

                $log = $pdo->prepare("INSERT INTO audit_logs (username, action, ip_address) VALUES (?, ?, ?)");
                $log->execute([$user['email'], "Updated Consultation Request ID {$cons_id} status to " . strtoupper($new_status), $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1']);

                set_flash_message('success', 'Consultation status updated to ' . strtoupper($new_status));
                redirect('manage-consultations.php');
            } catch (Exception $e) {
                error_log("Consultation Update Error: " . $e->getMessage());
                $errors[] = "Failed to update consultation request.";
            }
        }
    }
}

// Fetch all consultations
$consultations = [];
try {
    $stmt = $pdo->query("SELECT * FROM consultation_requests ORDER BY id DESC");
    $consultations = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Consultation List Fetch Error: " . $e->getMessage());
}

$admin_page_title = 'Executive Consultation Requests';
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

<div class="bg-white dark:bg-slate-900 border border-slate-200/50 dark:border-slate-800 rounded-3xl p-8 shadow-sm">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h3 class="text-lg font-bold text-slate-900 dark:text-white">Division Consultations Log</h3>
            <p class="text-xs text-slate-400">Review advisory requests for estate valuation, agronomy, environmental impact, and development projects.</p>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse text-xs min-w-[600px]">
            <thead>
                <tr class="border-b border-slate-200 dark:border-slate-800 text-slate-400 font-semibold uppercase tracking-widest">
                    <th class="py-3 pr-4">Client</th>
                    <th class="py-3 px-4">Division</th>
                    <th class="py-3 px-4">Preferred Date</th>
                    <th class="py-3 px-4">Brief</th>
                    <th class="py-3 px-4">Status</th>
                    <th class="py-3 pl-4 text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                <?php if (empty($consultations)): ?>
                    <tr>
                        <td colspan="6" class="py-8 text-center text-slate-400">No consultation requests received yet.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($consultations as $c): ?>
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-850/50 transition-colors">
                            <td class="py-4 pr-4">
                                <span class="font-bold text-slate-900 dark:text-white block"><?php echo htmlspecialchars($c['name']); ?></span>
                                <span class="text-[10px] text-slate-400 block"><?php echo htmlspecialchars($c['email']); ?> | <?php echo htmlspecialchars($c['phone']); ?></span>
                            </td>
                            <td class="py-4 px-4 font-bold text-amber-500 uppercase">
                                <?php echo htmlspecialchars($c['division']); ?>
                            </td>
                            <td class="py-4 px-4 font-medium text-slate-700 dark:text-slate-300">
                                <?php echo date('d M Y', strtotime($c['preferred_date'])); ?>
                            </td>
                            <td class="py-4 px-4 max-w-xs text-slate-600 dark:text-slate-300">
                                <p class="truncate" title="<?php echo htmlspecialchars($c['message']); ?>"><?php echo htmlspecialchars($c['message']); ?></p>
                            </td>
                            <td class="py-4 px-4">
                                <?php
                                $badge = 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300';
                                if ($c['status'] === 'contacted') $badge = 'bg-blue-50 text-blue-700 dark:bg-blue-950/40 dark:text-blue-300';
                                elseif ($c['status'] === 'in_progress') $badge = 'bg-purple-50 text-purple-700 dark:bg-purple-950/40 dark:text-purple-300';
                                elseif ($c['status'] === 'completed') $badge = 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300';
                                ?>
                                <span class="px-2.5 py-1 rounded-md text-[10px] font-bold uppercase <?php echo $badge; ?>">
                                    <?php echo htmlspecialchars($c['status']); ?>
                                </span>
                            </td>
                            <td class="py-4 pl-4 text-right">
                                <form action="manage-consultations.php" method="POST" class="inline-flex items-center space-x-1">
                                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                    <input type="hidden" name="update_consultation_status" value="1">
                                    <input type="hidden" name="consultation_id" value="<?php echo $c['id']; ?>">
                                    <select name="new_status" onchange="this.form.submit()" class="bg-slate-100 dark:bg-slate-800 text-[10px] font-bold rounded px-2 py-1 border-none focus:outline-none">
                                        <option value="">Update Status...</option>
                                        <option value="contacted">Mark Contacted</option>
                                        <option value="in_progress">In Progress</option>
                                        <option value="completed">Mark Completed</option>
                                    </select>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>

