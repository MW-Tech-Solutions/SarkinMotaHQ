<?php
/**
 * Public Automobile / Car Sales Catalog Page
 * Sarkin Mota HQ — Enterprise Edition
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/divisions_helper.php';

$page_title = "Automobile & Car Sales Catalog | Sarkin Mota HQ";
require_once __DIR__ . '/includes/header.php';

// Filter inputs
$make_filter = sanitize_input($_GET['make'] ?? '');
$condition_filter = sanitize_input($_GET['condition'] ?? '');
$search_filter = sanitize_input($_GET['q'] ?? '');

$sql = "
    SELECT p.*, d.make, d.model, d.year, d.vehicle_type, d.transmission, d.fuel_type, d.mileage, d.color, d.vehicle_condition, d.price, d.location, d.cover_image, d.availability
    FROM corporate_projects p
    JOIN automobile_project_details d ON p.id = d.project_id
    WHERE p.status = 'active'
";
$params = [];

if (!empty($make_filter)) {
    $sql .= " AND d.make = ?";
    $params[] = $make_filter;
}
if (!empty($condition_filter)) {
    $sql .= " AND d.vehicle_condition = ?";
    $params[] = $condition_filter;
}
if (!empty($search_filter)) {
    $sql .= " AND (p.title LIKE ? OR d.make LIKE ? OR d.model LIKE ?)";
    $params[] = "%$search_filter%";
    $params[] = "%$search_filter%";
    $params[] = "%$search_filter%";
}

$sql .= " ORDER BY p.id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch available makes for filter
$stmt_makes = $pdo->query("SELECT DISTINCT make FROM automobile_project_details ORDER BY make ASC");
$makes = $stmt_makes->fetchAll(PDO::FETCH_COLUMN);
?>

<main id="main-content" class="flex-grow py-8 sm:py-12 bg-slate-50 dark:bg-slate-950 transition-colors">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

        <!-- Page Hero Banner -->
        <div class="p-8 sm:p-12 bg-gradient-to-r from-slate-950 via-slate-900 to-amber-950 rounded-3xl text-white shadow-2xl relative overflow-hidden">
            <div class="absolute -right-10 -bottom-10 opacity-10 text-amber-500">
                <i class="bi bi-car-front-fill text-[18rem]"></i>
            </div>
            <div class="relative z-10 max-w-2xl space-y-4">
                <span class="px-3.5 py-1 bg-amber-500/20 text-amber-400 border border-amber-500/30 rounded-full text-xs font-extrabold uppercase tracking-widest inline-flex items-center gap-1.5">
                    <i class="bi bi-shield-check"></i> Sarkin Mota Autos & Fleet Sales
                </span>
                <h1 class="text-3xl sm:text-5xl font-black tracking-tight text-white">Automobile & Vehicle Catalog</h1>
                <p class="text-sm sm:text-base text-slate-300 font-normal leading-relaxed">
                    Explore our premier inventory of brand new and certified pre-owned luxury sedans, SUVs, and commercial vehicle fleets. All vehicles are inspected with transparent pricing and warranty coverage.
                </p>
            </div>
        </div>

        <!-- Filter & Search Bar -->
        <div class="bg-white dark:bg-slate-900 p-6 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm">
            <form method="GET" action="cars.php" class="grid grid-cols-1 sm:grid-cols-4 gap-4 items-end">
                <div>
                    <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-2">Search Vehicle</label>
                    <input type="text" name="q" value="<?php echo htmlspecialchars($search_filter); ?>" placeholder="e.g., G63 AMG, Camry, LX600..." class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-xs font-semibold text-slate-900 dark:text-white focus:outline-none focus:border-amber-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-2">Filter Make</label>
                    <select name="make" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-xs font-semibold text-slate-900 dark:text-white focus:outline-none focus:border-amber-500">
                        <option value="">All Car Makes</option>
                        <?php foreach ($makes as $m): ?>
                            <option value="<?php echo htmlspecialchars($m); ?>" <?php echo $make_filter === $m ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($m); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-2">Vehicle Condition</label>
                    <select name="condition" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-xs font-semibold text-slate-900 dark:text-white focus:outline-none focus:border-amber-500">
                        <option value="">All Conditions</option>
                        <option value="brand_new" <?php echo $condition_filter === 'brand_new' ? 'selected' : ''; ?>>Brand New</option>
                        <option value="foreign_used" <?php echo $condition_filter === 'foreign_used' ? 'selected' : ''; ?>>Foreign Used (Tokunbo)</option>
                        <option value="local_used" <?php echo $condition_filter === 'local_used' ? 'selected' : ''; ?>>Local Used</option>
                    </select>
                </div>

                <div class="flex items-center space-x-2">
                    <button type="submit" class="flex-1 py-2.5 bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold text-xs rounded-xl transition-colors flex items-center justify-center space-x-2 shadow-sm">
                        <i class="bi bi-search"></i>
                        <span>Filter Vehicles</span>
                    </button>
                    <?php if (!empty($make_filter) || !empty($condition_filter) || !empty($search_filter)): ?>
                        <a href="cars.php" class="py-2.5 px-3 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 font-bold text-xs rounded-xl hover:text-rose-500">
                            Reset
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Vehicles Grid -->
        <?php if (empty($vehicles)): ?>
            <div class="p-12 text-center bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 space-y-3">
                <div class="w-16 h-16 bg-slate-100 dark:bg-slate-800 text-slate-400 rounded-full flex items-center justify-center mx-auto text-2xl">
                    <i class="bi bi-car-front-fill"></i>
                </div>
                <h3 class="text-base font-bold text-slate-900 dark:text-white">No Vehicles Match Your Search Criteria</h3>
                <p class="text-xs text-slate-400 max-w-md mx-auto">Try resetting filters or adjusting search parameters to browse available automobile listings.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($vehicles as $v): ?>
                    <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 overflow-hidden shadow-sm hover:shadow-xl transition-all flex flex-col justify-between group">
                        
                        <div>
                            <!-- Car Cover Photo -->
                            <div class="relative h-56 w-full bg-slate-950 overflow-hidden">
                                <?php if (!empty($v['cover_image']) && file_exists(__DIR__ . '/' . $v['cover_image'])): ?>
                                    <img src="<?php echo htmlspecialchars($v['cover_image']); ?>" alt="<?php echo htmlspecialchars($v['title']); ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                                <?php else: ?>
                                    <div class="w-full h-full flex flex-col items-center justify-center bg-slate-900 text-slate-600 space-y-2">
                                        <i class="bi bi-car-front text-4xl"></i>
                                        <span class="text-xs font-semibold">Vehicle Photograph</span>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="absolute top-3 left-3 flex items-center space-x-2">
                                    <span class="px-3 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-slate-950/80 backdrop-blur-md text-amber-400 border border-amber-500/30">
                                        <?php echo htmlspecialchars(str_replace('_', ' ', $v['vehicle_condition'])); ?>
                                    </span>
                                </div>
                                <div class="absolute top-3 right-3">
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase bg-slate-950/80 backdrop-blur-md text-white border border-slate-800">
                                        <?php echo htmlspecialchars($v['year']); ?>
                                    </span>
                                </div>
                            </div>

                            <!-- Content Details -->
                            <div class="p-6 space-y-4">
                                <div>
                                    <span class="text-[10px] font-bold text-amber-500 uppercase tracking-widest block mb-1">
                                        <?php echo htmlspecialchars($v['make'] . ' • ' . $v['model']); ?>
                                    </span>
                                    <h2 class="text-lg font-black text-slate-900 dark:text-white line-clamp-1 group-hover:text-amber-500 transition-colors">
                                        <?php echo htmlspecialchars($v['title']); ?>
                                    </h2>
                                </div>

                                <!-- Key Specs Badges -->
                                <div class="grid grid-cols-3 gap-2 py-3 border-y border-slate-100 dark:border-slate-800 text-center">
                                    <div class="space-y-0.5">
                                        <span class="text-[9px] font-bold text-slate-400 uppercase block">Engine</span>
                                        <span class="text-xs font-bold text-slate-800 dark:text-slate-200 block truncate"><?php echo htmlspecialchars($v['engine'] ?: 'V6 / V8'); ?></span>
                                    </div>
                                    <div class="space-y-0.5 border-x border-slate-100 dark:border-slate-800">
                                        <span class="text-[9px] font-bold text-slate-400 uppercase block">Trans</span>
                                        <span class="text-xs font-bold text-slate-800 dark:text-slate-200 block truncate"><?php echo ucfirst($v['transmission']); ?></span>
                                    </div>
                                    <div class="space-y-0.5">
                                        <span class="text-[9px] font-bold text-slate-400 uppercase block">Fuel</span>
                                        <span class="text-xs font-bold text-slate-800 dark:text-slate-200 block truncate"><?php echo ucfirst($v['fuel_type']); ?></span>
                                    </div>
                                </div>

                                <p class="text-xs text-slate-500 dark:text-slate-400 line-clamp-2 leading-relaxed">
                                    <?php echo htmlspecialchars($v['description'] ?: 'High quality vehicle specification with complete documentation.'); ?>
                                </p>
                            </div>
                        </div>

                        <!-- Price & Action CTA -->
                        <div class="p-6 pt-0 flex items-center justify-between border-t border-slate-100 dark:border-slate-800/60 mt-2">
                            <div>
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Price</span>
                                <span class="text-xl font-black text-emerald-600 dark:text-emerald-400">
                                    ₦<?php echo number_format($v['price'], 2); ?>
                                </span>
                            </div>

                            <a href="contact.php?subject=Inquiry+for+<?php echo urlencode($v['title']); ?>" class="px-4 py-2.5 bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold text-xs rounded-xl transition-colors flex items-center space-x-1.5 shadow-sm">
                                <i class="bi bi-telephone-fill"></i>
                                <span>Inquire Now</span>
                            </a>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
