<?php
/**
 * Admin client inquiries viewer - Sarkin Mota HQ
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_helper.php';

require_role(['admin', 'staff']);

$errors = [];

// Handle state updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = "CSRF Verification Failed.";
    } else {
        if (isset($_POST['toggle_status'])) {
            $msg_id = intval($_POST['msg_id']);
            $current_status = sanitize_input($_POST['current_status']);
            $new_status = ($current_status === 'unread') ? 'read' : 'unread';
            
            try {
                $stmt = $pdo->prepare("UPDATE contacts SET status = ? WHERE id = ?");
                $stmt->execute([$new_status, $msg_id]);
                set_flash_message('success', 'Message status updated.');
                redirect('view-messages.php');
            } catch (Exception $e) {
                $errors[] = "Database update query failed.";
            }
        }
        
        if (isset($_POST['delete_message'])) {
            $msg_id = intval($_POST['msg_id']);
            try {
                $stmt = $pdo->prepare("DELETE FROM contacts WHERE id = ?");
                $stmt->execute([$msg_id]);
                set_flash_message('success', 'Message deleted successfully.');
                redirect('view-messages.php');
            } catch (Exception $e) {
                $errors[] = "Database deletion query failed.";
            }
        }
    }
}

// Fetch all contacts
$messages = [];
try {
    $stmt = $pdo->query("SELECT * FROM contacts ORDER BY id DESC");
    $messages = $stmt->fetchAll();
} catch (Exception $e) {
    $errors[] = "Contacts database table not initialized yet. Execute schema.sql in MySQL.";
}
$admin_page_title = 'Client Inquiries';
require_once __DIR__ . '/../includes/admin_sidebar.php';
?>

<?php echo display_flash_message(); ?>

        <?php if (!empty($errors)): ?>
            <div class="mb-8 border-l-4 border-rose-500 bg-rose-50 dark:bg-rose-950/20 p-4 rounded-r-md">
                <ul class="list-disc pl-5 text-xs text-rose-800 dark:text-rose-350 space-y-1">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Messages list -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200/50 dark:border-slate-800 rounded-3xl p-8 shadow-sm">
            <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-6">Received Communications</h3>
            
            <div class="space-y-6">
                <?php if (empty($messages)): ?>
                    <p class="text-xs text-slate-500 py-6 text-center">No contact inquiries in database.</p>
                <?php else: ?>
                    <?php foreach ($messages as $msg): ?>
                        <div class="border border-slate-200/80 dark:border-slate-800/85 p-6 rounded-2xl <?php echo ($msg['status'] === 'unread') ? 'bg-slate-50/50 dark:bg-slate-950/10 border-l-4 border-l-gold-500' : ''; ?>">
                            <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
                                <div>
                                    <h4 class="font-bold text-slate-900 dark:text-white text-sm">
                                        <?php echo sanitize_input($msg['name']); ?> 
                                        <?php if ($msg['status'] === 'unread'): ?>
                                            <span class="ml-2 px-2 py-0.5 bg-gold-500/10 border border-gold-500/30 text-gold-500 text-[9px] font-bold uppercase rounded">New</span>
                                        <?php endif; ?>
                                    </h4>
                                    <p class="text-[10px] text-slate-450 mt-1">Subject: <span class="font-bold text-slate-700 dark:text-white"><?php echo sanitize_input($msg['subject']); ?></span></p>
                                </div>
                                
                                <div class="flex items-center space-x-2">
                                    <form action="view-messages.php" method="POST" class="inline">
                                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                        <input type="hidden" name="msg_id" value="<?php echo $msg['id']; ?>">
                                        <input type="hidden" name="current_status" value="<?php echo $msg['status']; ?>">
                                        <button type="submit" name="toggle_status" class="px-3.5 py-1.5 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-750 text-[10px] font-bold uppercase rounded-md transition-colors">
                                            Mark as <?php echo ($msg['status'] === 'unread') ? 'Read' : 'Unread'; ?>
                                        </button>
                                    </form>
                                    
                                    <form action="view-messages.php" method="POST" onsubmit="return confirm('Delete message?');" class="inline">
                                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                        <input type="hidden" name="msg_id" value="<?php echo $msg['id']; ?>">
                                        <button type="submit" name="delete_message" class="px-3.5 py-1.5 bg-rose-50 text-rose-700 hover:bg-rose-100 dark:bg-rose-950/20 dark:text-rose-300 dark:hover:bg-rose-950/40 text-[10px] font-bold uppercase rounded-md transition-colors">
                                            <i class="bi bi-trash ui-icon" aria-hidden="true"></i>Delete
                                        </button>
                                    </form>
                                </div>
                            </div>
                            
                            <div class="text-xs text-slate-500 dark:text-slate-400 space-y-1 mb-4 leading-relaxed border-t border-slate-100 dark:border-slate-850 pt-4">
                                <p><i aria-hidden="true" class="bi bi-envelope text-slate-400 mr-1"></i> Email: <?php echo sanitize_input($msg['email']); ?></p>
                                <p><i aria-hidden="true" class="bi bi-telephone text-slate-400 mr-1"></i> Phone: <?php echo sanitize_input($msg['phone']); ?></p>
                                <div class="mt-4 p-4 bg-white dark:bg-slate-950 border border-slate-200/50 dark:border-slate-800 rounded-xl">
                                    <p class="text-slate-700 dark:text-slate-300 whitespace-pre-wrap font-sans text-xs"><?php echo sanitize_input($msg['message']); ?></p>
                                </div>
                            </div>
                            <span class="text-[9px] text-slate-400 block text-right">Received at: <?php echo date('d M Y, H:i', strtotime($msg['created_at'])); ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
