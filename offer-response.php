<?php
/**
 * Candidate Job Offer Acceptance Portal (Tokenized Response)
 * Sarkin Mota HQ — Enterprise Edition
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth_helper.php';

$token = sanitize_input($_GET['token'] ?? '');
$errors = [];
$success = false;

if (empty($token)) {
    die("Invalid offer response link. Missing authorization token.");
}

$offer = null;
$application = null;

try {
    $stmt = $pdo->prepare("
        SELECT o.*, d.name AS department_name, a.name AS candidate_name, a.email AS candidate_email, a.phone AS candidate_phone
        FROM job_offers o
        JOIN departments d ON o.department_id = d.id
        JOIN applications a ON o.application_id = a.id
        WHERE o.token = ?
    ");
    $stmt->execute([$token]);
    $offer = $stmt->fetch();

    if (!$offer) {
        die("Offer record not found or link has expired.");
    }

    if (strtotime($offer['expires_at']) < time() && $offer['status'] === 'issued') {
        $up = $pdo->prepare("UPDATE job_offers SET status = 'expired' WHERE id = ?");
        $up->execute([$offer['id']]);
        $offer['status'] = 'expired';
    }
} catch (Exception $e) {
    error_log("Offer Lookup Error: " . $e->getMessage());
    die("An error occurred while fetching your offer letter.");
}

// Handle Candidate Response (Accept / Decline)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_offer_response'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = "CSRF Token Validation Failed. Please try again.";
    } else {
        $decision = sanitize_input($_POST['decision'] ?? '');
        $comments = sanitize_input($_POST['comments'] ?? '');

        if (!in_array($decision, ['accepted', 'declined'])) {
            $errors[] = "Invalid offer decision.";
        }

        if ($offer['status'] !== 'issued' && $offer['status'] !== 'approved') {
            $errors[] = "This offer is no longer pending candidate action (Status: " . ucfirst($offer['status']) . ").";
        }

        if (empty($errors)) {
            try {
                $pdo->beginTransaction();

                // Update offer status
                $new_offer_status = ($decision === 'accepted') ? 'accepted' : 'declined';
                $up_stmt = $pdo->prepare("UPDATE job_offers SET status = ? WHERE id = ?");
                $up_stmt->execute([$new_offer_status, $offer['id']]);

                // Update application status
                if ($decision === 'accepted') {
                    $app_stmt = $pdo->prepare("UPDATE applications SET status = 'hired' WHERE id = ?");
                    $app_stmt->execute([$offer['application_id']]);

                    // Seed onboarding checklist items automatically
                    $checklist_items = [
                        ['National ID / Passport Verification', 'documents'],
                        ['Educational Credentials Audit', 'documents'],
                        ['Signed Offer & Non-Disclosure Agreement', 'policy'],
                        ['IT Workstation & Email Provisioning', 'account'],
                        ['Corporate Policy & Handbook Acknowledgement', 'policy'],
                        ['Departmental Orientation & Manager Briefing', 'orientation']
                    ];
                    $chk_ins = $pdo->prepare("INSERT INTO onboarding_checklists (application_id, item_name, category, is_completed) VALUES (?, ?, ?, 0)");
                    foreach ($checklist_items as $item) {
                        $chk_ins->execute([$offer['application_id'], $item[0], $item[1]]);
                    }
                } else {
                    $app_stmt = $pdo->prepare("UPDATE applications SET status = 'rejected', rejection_reason = 'Candidate declined offer' WHERE id = ?");
                    $app_stmt->execute([$offer['application_id']]);
                }

                // Log audit
                $log = $pdo->prepare("INSERT INTO hr_audit_logs (user_id, username, action, entity_type, entity_id, details, ip_address) VALUES (0, ?, ?, 'job_offer', ?, ?, ?)");
                $log->execute([$offer['candidate_email'], "Candidate Offer Decision: " . strtoupper($decision), $offer['id'], "Comments: " . substr($comments, 0, 200), $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1']);

                $pdo->commit();
                $offer['status'] = $new_offer_status;
                $success = true;
            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                error_log("Offer Decision Error: " . $e->getMessage());
                $errors[] = "Failed to record your decision: " . $e->getMessage();
            }
        }
    }
}

$page_title = "Employment Offer Response — " . setting('company_short_name', 'Sarkin Mota HQ');
require_once __DIR__ . '/includes/header.php';
?>

<section class="py-16 md:py-20 bg-slate-50 dark:bg-gray-950 min-h-screen">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header Brand Badge -->
        <div class="text-center mb-8">
            <span class="text-[10px] font-extrabold uppercase tracking-widest text-amber-500 block mb-1">Corporate Recruitment Division</span>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white">Formal Employment Offer Letter</h1>
            <p class="text-xs text-slate-500 mt-1"><?php echo htmlspecialchars(setting('company_name', 'Sarkin Mota HQ')); ?></p>
        </div>

        <?php if ($success): ?>
            <div class="bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-500/30 p-8 rounded-3xl text-center space-y-4 shadow-sm mb-8">
                <div class="h-14 w-14 rounded-2xl bg-emerald-500/10 text-emerald-500 flex items-center justify-center mx-auto text-2xl">
                    <i aria-hidden="true" class="bi bi-check-circle-fill"></i>
                </div>
                <h2 class="text-lg font-bold text-emerald-800 dark:text-emerald-300">Offer Decision Recorded</h2>
                <p class="text-xs text-slate-600 dark:text-slate-400 max-w-md mx-auto leading-relaxed">
                    Thank you, <?php echo htmlspecialchars($offer['candidate_name']); ?>! Your decision has been logged successfully. 
                    <?php if ($offer['status'] === 'accepted'): ?>
                        Our HR onboarding team will contact you shortly with your account activation details and orientation schedule.
                    <?php else: ?>
                        We appreciate your consideration of career opportunities with Sarkin Mota HQ.
                    <?php endif; ?>
                </p>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="mb-6 border-l-4 border-rose-500 bg-rose-50 dark:bg-rose-950/20 p-4 rounded-r-md">
                <ul class="list-disc pl-5 text-xs text-rose-800 dark:text-rose-300 space-y-1">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Printable / Viewable Offer Document Card -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-8 sm:p-10 rounded-3xl shadow-lg space-y-8">
            
            <div class="border-b border-slate-100 dark:border-slate-800 pb-6 flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-widest block">Candidate</span>
                    <span class="text-base font-extrabold text-slate-900 dark:text-white block"><?php echo htmlspecialchars($offer['candidate_name']); ?></span>
                    <span class="text-xs text-slate-500"><?php echo htmlspecialchars($offer['candidate_email']); ?> • <?php echo htmlspecialchars($offer['candidate_phone']); ?></span>
                </div>
                <div class="text-right flex flex-col items-end gap-2">
                    <div>
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-widest block">Offer Status</span>
                        <span class="inline-block px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-amber-500/10 text-amber-500 border border-amber-500/20">
                            <?php echo htmlspecialchars($offer['status']); ?>
                        </span>
                    </div>
                    <a href="hr/download-offer-pdf.php?token=<?php echo urlencode($token); ?>&pdf=1" target="_blank" class="px-3 py-1 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-lg inline-flex items-center space-x-1 shadow-sm">
                        <i aria-hidden="true" class="bi bi-file-earmark-pdf-fill"></i>
                        <span>Download Official PDF Letter</span>
                    </a>
                </div>
            </div>

            <!-- Position Terms Overview -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 bg-slate-50 dark:bg-slate-950 p-6 rounded-2xl border border-slate-100 dark:border-slate-800/80 text-xs">
                <div>
                    <span class="font-semibold text-slate-400 block mb-1">Position / Designation</span>
                    <span class="font-bold text-slate-900 dark:text-white block text-sm"><?php echo htmlspecialchars($offer['position_title']); ?></span>
                </div>
                <div>
                    <span class="font-semibold text-slate-400 block mb-1">Assigned Department</span>
                    <span class="font-bold text-slate-900 dark:text-white block text-sm"><?php echo htmlspecialchars($offer['department_name']); ?></span>
                </div>
                <div>
                    <span class="font-semibold text-slate-400 block mb-1">Employment Type</span>
                    <span class="font-bold text-slate-900 dark:text-white block"><?php echo htmlspecialchars($offer['employment_type']); ?></span>
                </div>
                <div>
                    <span class="font-semibold text-slate-400 block mb-1">Official Start Date</span>
                    <span class="font-bold text-slate-900 dark:text-white block"><?php echo date('d F Y', strtotime($offer['start_date'])); ?></span>
                </div>
            </div>

            <!-- Formal Letter Text -->
            <div class="prose dark:prose-invert text-xs text-slate-600 dark:text-slate-300 space-y-4 leading-relaxed">
                <p>Dear <strong><?php echo htmlspecialchars($offer['candidate_name']); ?></strong>,</p>
                <p>On behalf of <strong><?php echo htmlspecialchars(setting('company_name', 'Sarkin Mota HQ')); ?></strong>, we are pleased to extend this formal offer of employment for the position of <strong><?php echo htmlspecialchars($offer['position_title']); ?></strong> within our <strong><?php echo htmlspecialchars($offer['department_name']); ?></strong> division.</p>
                <p>In this role, you will report directly to executive departmental leadership and contribute towards property management, land development, and strategic client advisory across our operating regions.</p>
                <p>Please review the terms above and indicate your acceptance or decline using the secure decision module below prior to <strong><?php echo date('d F Y, H:i', strtotime($offer['expires_at'])); ?></strong>.</p>
            </div>

            <!-- Decision Form (Only if status is issued or approved) -->
            <?php if (!$success && in_array($offer['status'], ['issued', 'approved'])): ?>
                <form method="POST" action="offer-response.php?token=<?php echo urlencode($token); ?>" class="pt-6 border-t border-slate-100 dark:border-slate-800 space-y-6">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    <input type="hidden" name="submit_offer_response" value="1">
                    
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Select Decision</label>
                        <div class="grid grid-cols-2 gap-4">
                            <label class="p-4 bg-emerald-500/10 border border-emerald-500/30 rounded-2xl cursor-pointer flex items-center space-x-3 hover:bg-emerald-500/20 transition-colors">
                                <input type="radio" name="decision" value="accepted" checked class="accent-emerald-500 h-4 w-4">
                                <div>
                                    <span class="block text-xs font-bold text-emerald-800 dark:text-emerald-300">Accept Offer</span>
                                    <span class="block text-[10px] text-slate-500">Confirm position & start onboarding</span>
                                </div>
                            </label>
                            <label class="p-4 bg-rose-500/10 border border-rose-500/30 rounded-2xl cursor-pointer flex items-center space-x-3 hover:bg-rose-500/20 transition-colors">
                                <input type="radio" name="decision" value="declined" class="accent-rose-500 h-4 w-4">
                                <div>
                                    <span class="block text-xs font-bold text-rose-800 dark:text-rose-300">Decline Offer</span>
                                    <span class="block text-[10px] text-slate-500">Decline offer of employment</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Candidate Comments / Signature Notes</label>
                        <textarea name="comments" rows="3" placeholder="Optional comments regarding your acceptance or start date..." class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:border-amber-500"></textarea>
                    </div>

                    <button type="submit" class="w-full bg-amber-500 text-slate-950 font-bold uppercase text-xs tracking-wider py-3.5 rounded-xl hover:bg-amber-600 transition-colors flex items-center justify-center space-x-2 shadow-md">
                        <i aria-hidden="true" class="bi bi-send-fill"></i>
                        <span>Submit Final Offer Decision</span>
                    </button>
                </form>
            <?php endif; ?>

        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
