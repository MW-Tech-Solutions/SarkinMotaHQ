<?php
/**
 * Dedicated Landlord Portal Dashboard - Sarkin Mota HQ
 * Rent Collection Ledger, Tenant Roster & Property Uploads
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_helper.php';
require_once __DIR__ . '/../includes/rbac_helper.php';

require_login();
$user = get_logged_in_user();

if ($user['role'] !== 'landlord' && !is_admin()) {
    redirect('dashboard.php');
}

$errors = [];
$active_tab = $_GET['tab'] ?? 'overview';

// Handle Landlord Property Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['landlord_upload'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = "CSRF Verification Failed.";
    } else {
        $title = sanitize_input($_POST['title'] ?? '');
        $description = sanitize_input($_POST['description'] ?? '');
        $price = floatval($_POST['price'] ?? 0);
        $location = sanitize_input($_POST['location'] ?? '');
        $beds = intval($_POST['beds'] ?? 0);
        $baths = intval($_POST['baths'] ?? 0);
        $area_sqft = intval($_POST['area_sqft'] ?? 0);
        $type = sanitize_input($_POST['type'] ?? 'rent');
        $category = sanitize_input($_POST['category'] ?? 'residential');
        $image_url = handle_image_upload('image_file', 'assets/images/logo.png');
        
        if (empty($title)) $errors[] = "Property title is required.";
        if ($price <= 0) $errors[] = "Invalid price index.";
        
        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO properties (title, description, price, location, beds, baths, area_sqft, type, category, listing_status, image_url, agent_name, landlord_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending_review', ?, ?, ?)");
                $stmt->execute([$title, $description, $price, $location, $beds, $baths, $area_sqft, $type, $category, $image_url, $user['name'], $user['id']]);
                
                $log_stmt = $pdo->prepare("INSERT INTO audit_logs (username, action, ip_address) VALUES (?, ?, ?)");
                $log_stmt->execute([$user['email'], "Uploaded property: " . $title, $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1']);
                
                set_flash_message('success', 'Landlord property listing uploaded successfully.');
                redirect('landlord-dashboard.php?tab=overview');
            } catch (Exception $e) {
                error_log("Property Upload Error: " . $e->getMessage());
                $errors[] = "Failed to submit listing:  Please try again or contact support.";
            }
        }
    }
}

// Fetch Landlord Data
$my_properties = [];
$my_leases = [];

try {
    $stmt_props = $pdo->prepare("SELECT * FROM properties WHERE landlord_id = ? ORDER BY id DESC");
    $stmt_props->execute([$user['id']]);
    $my_properties = $stmt_props->fetchAll();

    $stmt_leases = $pdo->prepare("SELECT l.*, p.title AS property_title, u.name, u.email, u.phone, u.created_at FROM leases l JOIN properties p ON l.property_id = p.id JOIN users u ON l.tenant_id = u.id WHERE l.landlord_id = ?");
    $stmt_leases->execute([$user['id']]);
    $my_leases = $stmt_leases->fetchAll();
} catch (Exception $e) {
    error_log("Landlord Fetch Error: " . $e->getMessage());
}

$admin_page_title = "Portal Dashboard (Landlord)";
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
    <div id="lnd-tab-overview" class="landlord-tab-content <?php echo ($active_tab === 'overview' || $active_tab === 'quick') ? '' : 'hidden'; ?> space-y-6">
        <div class="bg-amber-500/10 border border-amber-500/20 p-5 rounded-2xl flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center space-x-3">
                <i aria-hidden="true" class="bi bi-building-check text-2xl text-amber-500"></i>
                <div>
                    <h2 class="text-sm font-bold text-slate-900 dark:text-white">Welcome back, <?php echo htmlspecialchars($user['name']); ?> (Landlord)</h2>
                    <p class="text-xs text-slate-500">Manage property listings, track rent collections, and inspect tenant lease rosters.</p>
                </div>
            </div>
            <a href="landlord-dashboard.php?tab=upload" class="px-4 py-2 bg-amber-500 text-slate-950 text-xs font-bold rounded-xl hover:bg-amber-600 transition-colors flex items-center space-x-2">
                <i aria-hidden="true" class="bi bi-plus-circle-fill"></i>
                <span>Upload New Property</span>
            </a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
            <div class="bg-white dark:bg-slate-900 border border-slate-200/50 dark:border-slate-800 p-6 rounded-3xl shadow-sm">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-widest block mb-1">My Listed Properties</span>
                <span class="text-3xl font-extrabold text-slate-900 dark:text-white block"><?php echo count($my_properties); ?></span>
            </div>
            <div class="bg-white dark:bg-slate-900 border border-slate-200/50 dark:border-slate-800 p-6 rounded-3xl shadow-sm">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-widest block mb-1">Active Leases</span>
                <span class="text-3xl font-extrabold text-amber-500 block"><?php echo count($my_leases); ?></span>
            </div>
            <div class="bg-white dark:bg-slate-900 border border-slate-200/50 dark:border-slate-800 p-6 rounded-3xl shadow-sm">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-widest block mb-1">Management Status</span>
                <span class="text-sm font-bold text-emerald-500 block">Active Landlord Partner</span>
            </div>
        </div>

        <!-- Listed Properties Table -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200/50 dark:border-slate-800 p-8 rounded-3xl shadow-sm">
            <h3 class="text-base font-bold text-slate-900 dark:text-white mb-6">My Property Portfolio</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 text-slate-400 font-semibold uppercase tracking-widest">
                            <th class="py-3 pr-4">Property</th>
                            <th class="py-3 px-4">Location</th>
                            <th class="py-3 px-4">Price</th>
                            <th class="py-3 px-4">Type</th>
                            <th class="py-3 px-4">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                        <?php if (empty($my_properties)): ?>
                            <tr>
                                <td colspan="5" class="py-6 text-center text-slate-400">No properties uploaded yet. Use the button above to post your first listing.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($my_properties as $prop): ?>
                                <tr>
                                    <td class="py-3 pr-4 font-bold text-slate-900 dark:text-white"><?php echo htmlspecialchars($prop['title']); ?></td>
                                    <td class="py-3 px-4 text-slate-400"><?php echo htmlspecialchars($prop['location']); ?></td>
                                    <td class="py-3 px-4 font-bold text-emerald-500"><?php echo format_currency($prop['price']); ?></td>
                                    <td class="py-3 px-4 uppercase font-semibold text-amber-500"><?php echo htmlspecialchars($prop['type']); ?></td>
                                    <td class="py-3 px-4"><span class="px-2 py-0.5 rounded text-[9px] font-bold uppercase bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300"><?php echo htmlspecialchars($prop['status'] ?? 'Active'); ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Tab 2: Tenant Roster -->
    <div id="lnd-tab-roster" class="landlord-tab-content <?php echo ($active_tab === 'roster' || $active_tab === 'ledger') ? '' : 'hidden'; ?>">
        <div class="bg-white dark:bg-slate-900 border border-slate-200/50 dark:border-slate-800 p-8 rounded-3xl shadow-sm">
            <h3 class="text-base font-bold text-slate-900 dark:text-white mb-6">Tenant Roster & Active Leases</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 text-slate-400 font-semibold uppercase tracking-widest">
                            <th class="py-3 pr-4">Tenant Name</th>
                            <th class="py-3 px-4">Property</th>
                            <th class="py-3 px-4">Contact</th>
                            <th class="py-3 px-4">Rent Amount</th>
                            <th class="py-3 px-4">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                        <?php if (empty($my_leases)): ?>
                            <tr>
                                <td colspan="5" class="py-6 text-center text-slate-400">No active tenants assigned to your properties yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($my_leases as $l): ?>
                                <tr>
                                    <td class="py-3 pr-4 font-bold text-slate-900 dark:text-white"><?php echo htmlspecialchars($l['name'] ?? 'N/A'); ?></td>
                                    <td class="py-3 px-4 text-slate-400"><?php echo htmlspecialchars($l['property_title'] ?? 'Unit'); ?></td>
                                    <td class="py-3 px-4 text-slate-400"><?php echo htmlspecialchars($l['email'] ?? $l['phone'] ?? ''); ?></td>
                                    <td class="py-3 px-4 font-bold text-emerald-500"><?php echo format_currency($l['rent_amount'] ?? 0); ?></td>
                                    <td class="py-3 px-4"><span class="px-2 py-0.5 rounded text-[9px] font-bold uppercase bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300"><?php echo htmlspecialchars($l['status'] ?? 'Active'); ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Tab 3: Upload Property -->
    <div id="lnd-tab-upload" class="landlord-tab-content <?php echo ($active_tab === 'upload') ? '' : 'hidden'; ?>">
        <div class="bg-white dark:bg-slate-900 border border-slate-200/50 dark:border-slate-800 p-8 rounded-3xl shadow-sm max-w-2xl">
            <h3 class="text-base font-bold text-slate-900 dark:text-white mb-2">Upload Property Listing</h3>
            <p class="text-xs text-slate-500 mb-6">List your property for rent or sale on Sarkin Mota HQ Catalog.</p>
            <form method="POST" action="landlord-dashboard.php?tab=upload" enctype="multipart/form-data" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <input type="hidden" name="landlord_upload" value="1">
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Property Title</label>
                    <input type="text" name="title" required placeholder="e.g. Luxury 3 Bedroom Apartment, Maitama" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:border-amber-500">
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Price Index (₦)</label>
                        <input type="number" step="1000" name="price" required placeholder="e.g. 2500000" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:border-amber-500">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Location</label>
                        <input type="text" name="location" required placeholder="e.g. Maitama, Abuja" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:border-amber-500">
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Bedrooms</label>
                        <input type="number" name="beds" value="3" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:border-amber-500">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Bathrooms</label>
                        <input type="number" name="baths" value="3" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:border-amber-500">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Listing Category</label>
                        <select name="category" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:border-amber-500">
                            <option value="rent">For Rent</option>
                            <option value="sale">For Sale</option>
                            <option value="lease">Long Lease</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Property Description</label>
                    <textarea name="description" rows="3" placeholder="Highlight key features, amenities, and terms..." class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:border-amber-500"></textarea>
                </div>
                <button type="submit" class="w-full bg-amber-500 text-slate-950 font-bold uppercase text-xs tracking-wider py-3 rounded-xl hover:bg-amber-600 transition-colors flex items-center justify-center space-x-2">
                    <i aria-hidden="true" class="bi bi-cloud-upload-fill"></i>
                    <span>Publish Listing</span>
                </button>
            </form>
        </div>
    </div>

</div>

</main>
</div>
</body>
</html>
