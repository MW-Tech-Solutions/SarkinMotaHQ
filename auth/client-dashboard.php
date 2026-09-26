<?php
/**
 * Dedicated Client Portal Dashboard - Sarkin Mota HQ
 * Full Capabilities for Normal Registered Users: Apply for Housing/Plots, Tour Inspections & Consultations
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_helper.php';
require_once __DIR__ . '/../includes/rbac_helper.php';

require_login();
$user = get_logged_in_user();

$errors = [];
$active_tab = $_GET['tab'] ?? 'overview';

// Fetch Client Data
$my_inspections = [];
$my_consultations = [];

try {
    $stmt_insp = $pdo->prepare("SELECT ir.*, p.title AS property_title FROM inspection_requests ir JOIN properties p ON ir.property_id = p.id WHERE ir.user_id = ? OR ir.email = ? ORDER BY ir.id DESC");
    $stmt_insp->execute([$user['id'], $user['email']]);
    $my_inspections = $stmt_insp->fetchAll();

    $stmt_cons = $pdo->prepare("SELECT * FROM consultation_requests WHERE user_id = ? OR email = ? ORDER BY id DESC");
    $stmt_cons->execute([$user['id'], $user['email']]);
    $my_consultations = $stmt_cons->fetchAll();
} catch (Exception $e) {
    error_log("Client Fetch Error: " . $e->getMessage());
}

$admin_page_title = "Portal Dashboard (Client)";
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

<div class="space-y-6">

    <!-- Tab 1: Overview -->
    <div id="clt-tab-overview" class="client-tab-content <?php echo ($active_tab === 'overview' || $active_tab === 'quick') ? '' : 'hidden'; ?> space-y-6">
        <div class="bg-amber-500/10 border border-amber-500/20 p-5 rounded-2xl flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center space-x-3">
                <i aria-hidden="true" class="bi bi-person-badge-fill text-2xl text-amber-500"></i>
                <div>
                    <h2 class="text-sm font-bold text-slate-900 dark:text-white">Welcome, <?php echo htmlspecialchars($user['name']); ?>!</h2>
                    <p class="text-xs text-slate-500">Explore premium housing, acquire agricultural land fields, schedule inspection tours, or book executive consulting sessions.</p>
                </div>
            </div>
            <a href="../properties.php" class="px-4 py-2 bg-amber-500 text-slate-950 text-xs font-bold rounded-xl hover:bg-amber-600 transition-colors flex items-center space-x-2">
                <i aria-hidden="true" class="bi bi-building"></i>
                <span>Explore Housing Catalog</span>
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Apply for Housing Card -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200/50 dark:border-slate-800 p-6 rounded-3xl shadow-sm hover:border-amber-500/40 transition-all flex flex-col justify-between">
                <div>
                    <div class="h-10 w-10 rounded-2xl bg-amber-500/10 text-amber-500 flex items-center justify-center mb-4 text-lg">
                        <i aria-hidden="true" class="bi bi-house-door-fill"></i>
                    </div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-1">Apply for Housing</h3>
                    <p class="text-xs text-slate-500 mb-4">Browse residential listings, apartments, and land plots ready for acquisition.</p>
                </div>
                <a href="../properties.php" class="w-full py-2 px-3 bg-amber-500 text-slate-950 text-center text-xs font-bold rounded-xl hover:bg-amber-600 transition-colors block">
                    Browse Properties
                </a>
            </div>

            <!-- Fields & Agribusiness Card -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200/50 dark:border-slate-800 p-6 rounded-3xl shadow-sm hover:border-amber-500/40 transition-all flex flex-col justify-between">
                <div>
                    <div class="h-10 w-10 rounded-2xl bg-amber-500/10 text-amber-500 flex items-center justify-center mb-4 text-lg">
                        <i aria-hidden="true" class="bi bi-tree-fill"></i>
                    </div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-1">Fields Purchase & Farm Land</h3>
                    <p class="text-xs text-slate-500 mb-4">Invest in commercial agricultural land fields and development projects.</p>
                </div>
                <a href="../projects.php" class="w-full py-2 px-3 bg-amber-500 text-slate-950 text-center text-xs font-bold rounded-xl hover:bg-amber-600 transition-colors block">
                    View Land Projects
                </a>
            </div>

            <!-- Inspection Tours Card -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200/50 dark:border-slate-800 p-6 rounded-3xl shadow-sm hover:border-amber-500/40 transition-all flex flex-col justify-between">
                <div>
                    <div class="h-10 w-10 rounded-2xl bg-amber-500/10 text-amber-500 flex items-center justify-center mb-4 text-lg">
                        <i aria-hidden="true" class="bi bi-calendar-check"></i>
                    </div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-1">Schedule Inspection</h3>
                    <p class="text-xs text-slate-500 mb-4">Book guided site inspections for properties and agricultural fields.</p>
                </div>
                <a href="client-dashboard.php?tab=inspections" class="w-full py-2 px-3 bg-amber-500 text-slate-950 text-center text-xs font-bold rounded-xl hover:bg-amber-600 transition-colors block">
                    My Inspections (<?php echo count($my_inspections); ?>)
                </a>
            </div>

            <!-- Consultation Bookings Card -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200/50 dark:border-slate-800 p-6 rounded-3xl shadow-sm hover:border-amber-500/40 transition-all flex flex-col justify-between">
                <div>
                    <div class="h-10 w-10 rounded-2xl bg-amber-500/10 text-amber-500 flex items-center justify-center mb-4 text-lg">
                        <i aria-hidden="true" class="bi bi-headset"></i>
                    </div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-1">Corporate Consultation</h3>
                    <p class="text-xs text-slate-500 mb-4">Book executive consulting on real estate development and environmental studies.</p>
                </div>
                <a href="client-dashboard.php?tab=consultations" class="w-full py-2 px-3 bg-amber-500 text-slate-950 text-center text-xs font-bold rounded-xl hover:bg-amber-600 transition-colors block">
                    Book Consultation
                </a>
            </div>
        </div>
    </div>

    <!-- Tab 2: Inspection Tours -->
    <div id="clt-tab-inspections" class="client-tab-content <?php echo ($active_tab === 'inspections') ? '' : 'hidden'; ?>">
        <div class="bg-white dark:bg-slate-900 border border-slate-200/50 dark:border-slate-800 p-8 rounded-3xl shadow-sm">
            <h3 class="text-base font-bold text-slate-900 dark:text-white mb-6">Property Tour Inspections</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 text-slate-400 font-semibold uppercase tracking-widest">
                            <th class="py-3 pr-4">Property</th>
                            <th class="py-3 px-4">Preferred Date</th>
                            <th class="py-3 px-4">Preferred Time</th>
                            <th class="py-3 px-4">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                        <?php if (empty($my_inspections)): ?>
                            <tr>
                                <td colspan="4" class="py-6 text-center text-slate-400">No inspection tour requests found. Browse properties in the catalog to schedule your first tour!</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($my_inspections as $insp): ?>
                                <tr>
                                    <td class="py-3 pr-4 font-bold text-slate-900 dark:text-white"><?php echo htmlspecialchars($insp['property_title'] ?? 'Listing'); ?></td>
                                    <td class="py-3 px-4 text-slate-400"><?php echo htmlspecialchars($insp['preferred_date'] ?? 'N/A'); ?></td>
                                    <td class="py-3 px-4 text-slate-400"><?php echo htmlspecialchars($insp['preferred_time'] ?? 'Standard'); ?></td>
                                    <td class="py-3 px-4"><span class="px-2 py-0.5 rounded text-[9px] font-bold uppercase bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300"><?php echo htmlspecialchars($insp['status'] ?? 'Pending'); ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Tab 3: Consultation Bookings -->
    <div id="clt-tab-consultations" class="client-tab-content <?php echo ($active_tab === 'consultations') ? '' : 'hidden'; ?>">
        <div class="bg-white dark:bg-slate-900 border border-slate-200/50 dark:border-slate-800 p-8 rounded-3xl shadow-sm max-w-xl">
            <h3 class="text-base font-bold text-slate-900 dark:text-white mb-2">Book Corporate Consultation</h3>
            <p class="text-xs text-slate-500 mb-6">Schedule an executive consulting brief with Sarkin Mota HQ directors.</p>
            <form action="../contact.php" method="POST" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <input type="hidden" name="subject" value="Client Consultation Booking">
                <input type="hidden" name="name" value="<?php echo htmlspecialchars($user['name']); ?>">
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($user['email']); ?>">
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Consultation Topic / Focus Area</label>
                    <select name="consultation_topic" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:border-amber-500">
                        <option value="Real Estate Development & Investment">Real Estate Development & Investment</option>
                        <option value="Agricultural Fields & Farm Acquisition">Agricultural Fields & Farm Acquisition</option>
                        <option value="Environmental Impact Studies (EIA)">Environmental Impact Studies (EIA)</option>
                        <option value="General Corporate Briefing">General Corporate Briefing</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Consulting Requirements</label>
                    <textarea name="message" rows="4" required placeholder="Outline your project scope or investment requirements..." class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:border-amber-500"></textarea>
                </div>
                <button type="submit" class="w-full bg-amber-500 text-slate-950 font-bold uppercase text-xs tracking-wider py-3 rounded-xl hover:bg-amber-600 transition-colors flex items-center justify-center space-x-2">
                    <i aria-hidden="true" class="bi bi-calendar-event-fill"></i>
                    <span>Submit Consultation Booking</span>
                </button>
            </form>
        </div>
    </div>

</div>

</main>
</div>
</body>
</html>
