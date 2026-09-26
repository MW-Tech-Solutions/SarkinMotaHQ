<?php
/**
 * Admin Property Inspections Manager
 * Sarkin Mota HQ — Enterprise Edition
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_helper.php';
require_once __DIR__ . '/../includes/rbac_helper.php';

require_permission('inspections.manage');
$user = get_logged_in_user();

$errors = [];
$success = '';

// Handle Status Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_inspection_status'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = "CSRF Verification Failed.";
    } else {
        $insp_id = intval($_POST['inspection_id']);
        $new_status = sanitize_input($_POST['new_status']);
        $valid_statuses = ['requested', 'under_review', 'scheduled', 'confirmed', 'completed', 'cancelled'];

        if (in_array($new_status, $valid_statuses) && $insp_id > 0) {
            try {
                $up = $pdo->prepare("UPDATE inspection_requests SET status = ? WHERE id = ?");
                $up->execute([$new_status, $insp_id]);

                // Audit Log
                $log = $pdo->prepare("INSERT INTO audit_logs (username, action, ip_address) VALUES (?, ?, ?)");
                $log->execute([$user['email'], "Updated Inspection Request ID {$insp_id} status to " . strtoupper($new_status), $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1']);

                set_flash_message('success', 'Inspection request status updated to ' . strtoupper($new_status));
                redirect('manage-inspections.php');
            } catch (Exception $e) {
                error_log("Inspection Update Error: " . $e->getMessage());
                $errors[] = "Failed to update inspection request.";
            }
        }
    }
}

// Fetch all inspection requests
$inspections = [];
try {
    $stmt = $pdo->query("
        SELECT ir.*, p.title AS property_title, p.location AS property_location 
        FROM inspection_requests ir
        JOIN properties p ON ir.property_id = p.id
        ORDER BY ir.id DESC
    ");
    $inspections = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Inspection List Fetch Error: " . $e->getMessage());
}

$admin_page_title = 'Property Inspection Requests';
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
            <h3 class="text-lg font-bold text-slate-900 dark:text-white">Site Inspection Schedule</h3>
            <p class="text-xs text-slate-400">Review and manage property tour requests submitted by prospective buyers and tenants.</p>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse text-xs">
            <thead>
                <tr class="border-b border-slate-200 dark:border-slate-800 text-slate-400 font-semibold uppercase tracking-widest">
                    <th class="py-3 pr-4">Property</th>
                    <th class="py-3 px-4">Applicant</th>
                    <th class="py-3 px-4">Date & Time</th>
                    <th class="py-3 px-4">Format</th>
                    <th class="py-3 px-4">Status</th>
                    <th class="py-3 pl-4 text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                <?php if (empty($inspections)): ?>
                    <tr>
                        <td colspan="6" class="py-8 text-center text-slate-400">No property inspection requests received yet.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($inspections as $ins): ?>
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-850/50 transition-colors">
                            <td class="py-4 pr-4">
                                <span class="font-bold text-slate-900 dark:text-white block"><?php echo htmlspecialchars($ins['property_title']); ?></span>
                                <span class="text-[10px] text-slate-400 block"><?php echo htmlspecialchars($ins['property_location']); ?></span>
                            </td>
                            <td class="py-4 px-4">
                                <span class="font-semibold text-slate-800 dark:text-slate-200 block"><?php echo htmlspecialchars($ins['name']); ?></span>
                                <span class="text-[10px] text-slate-400 block"><?php echo htmlspecialchars($ins['email']); ?> | <?php echo htmlspecialchars($ins['phone']); ?></span>
                            </td>
                            <td class="py-4 px-4 font-medium text-slate-700 dark:text-slate-300">
                                <?php echo date('d M Y', strtotime($ins['preferred_date'])); ?><br>
                                <span class="text-[10px] text-amber-500 font-bold"><?php echo date('h:i A', strtotime($ins['preferred_time'])); ?></span>
                            </td>
                            <td class="py-4 px-4">
                                <span class="px-2.5 py-1 rounded-md text-[10px] font-bold uppercase <?php echo $ins['inspection_type'] === 'in_person' ? 'bg-blue-50 text-blue-700 dark:bg-blue-950/40 dark:text-blue-300' : 'bg-purple-50 text-purple-700 dark:bg-purple-950/40 dark:text-purple-300'; ?>">
                                    <?php echo $ins['inspection_type'] === 'in_person' ? 'Physical Site' : 'Virtual Tour'; ?>
                                </span>
                            </td>
                            <td class="py-4 px-4">
                                <?php
                                $badge = 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300';
                                if ($ins['status'] === 'confirmed') $badge = 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300';
                                elseif ($ins['status'] === 'completed') $badge = 'bg-blue-50 text-blue-700 dark:bg-blue-950/40 dark:text-blue-300';
                                elseif ($ins['status'] === 'cancelled') $badge = 'bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300';
                                ?>
                                <span class="px-2.5 py-1 rounded-md text-[10px] font-bold uppercase <?php echo $badge; ?>">
                                    <?php echo htmlspecialchars($ins['status']); ?>
                                </span>
                            </td>
                            <td class="py-4 pl-4 text-right">
                                <form action="manage-inspections.php" method="POST" class="inline-flex items-center space-x-1">
                                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                    <input type="hidden" name="update_inspection_status" value="1">
                                    <input type="hidden" name="inspection_id" value="<?php echo $ins['id']; ?>">
                                    <select name="new_status" onchange="this.form.submit()" class="bg-slate-100 dark:bg-slate-800 text-[10px] font-bold rounded px-2 py-1 border-none focus:outline-none">
                                        <option value="">Update Status...</option>
                                        <option value="under_review">Under Review</option>
                                        <option value="scheduled">Scheduled</option>
                                        <option value="confirmed">Confirm Tour</option>
                                        <option value="completed">Mark Completed</option>
                                        <option value="cancelled">Cancel Request</option>
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

