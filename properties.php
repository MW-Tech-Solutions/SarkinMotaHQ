<?php
/**
 * Upgraded Real Estate Catalog - Sarkin Mota HQ
 * Enterprise Edition
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/pagination_helper.php';

// Retrieve search parameters
$search_query = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$type_filter = isset($_GET['type']) ? sanitize_input($_GET['type']) : '';
$cat_filter = isset($_GET['category']) ? sanitize_input($_GET['category']) : '';
$price_filter = isset($_GET['price_range']) ? sanitize_input($_GET['price_range']) : '';
$beds_filter = isset($_GET['beds']) ? sanitize_input($_GET['beds']) : '';
$baths_filter = isset($_GET['baths']) ? sanitize_input($_GET['baths']) : '';
$land_size_filter = isset($_GET['land_size']) ? sanitize_input($_GET['land_size']) : '';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;

$props_meta = ['data' => [], 'total' => 0, 'page' => 1, 'last_page' => 1];
try {
    // Only publicly active properties (F13 Resolution)
    $sql = "SELECT * FROM properties WHERE listing_status = 'active'";
    $params = [];
    
    if (!empty($search_query)) {
        $sql .= " AND (title LIKE ? OR location LIKE ? OR description LIKE ?)";
        $params[] = "%$search_query%";
        $params[] = "%$search_query%";
        $params[] = "%$search_query%";
    }
    
    if (!empty($type_filter)) {
        $sql .= " AND type = ?";
        $params[] = $type_filter;
    }
    
    if (!empty($cat_filter)) {
        $sql .= " AND category = ?";
        $params[] = $cat_filter;
    }
    
    if (!empty($price_filter)) {
        if ($price_filter === 'under_10m') {
            $sql .= " AND price < 10000000";
        } elseif ($price_filter === '10m_50m') {
            $sql .= " AND price BETWEEN 10000000 AND 50000000";
        } elseif ($price_filter === '50m_150m') {
            $sql .= " AND price BETWEEN 50000000 AND 150000000";
        } elseif ($price_filter === 'over_150m') {
            $sql .= " AND price > 150000000";
        }
    }
    
    if ($beds_filter !== '') {
        $sql .= " AND beds = ?";
        $params[] = intval($beds_filter);
    }
    
    if ($baths_filter !== '') {
        $sql .= " AND baths = ?";
        $params[] = intval($baths_filter);
    }
    
    if (!empty($land_size_filter)) {
        if ($land_size_filter === 'under_5k') {
            $sql .= " AND land_size_sqft < 5000";
        } elseif ($land_size_filter === '5k_20k') {
            $sql .= " AND land_size_sqft BETWEEN 5000 AND 20000";
        } elseif ($land_size_filter === 'over_20k') {
            $sql .= " AND land_size_sqft > 20000";
        }
    }
    
    $sql .= " ORDER BY id DESC";
    $props_meta = paginate_query($pdo, $sql, $params, $page, 6);
} catch (Exception $e) {
    error_log("Property Query Error: " . $e->getMessage());
}

$page_title = "Real Estate & Property Catalog — Sarkin Mota HQ";
require_once __DIR__ . '/includes/header.php';
?>

<!-- Header Banner -->
<section class="py-20 bg-slate-950 text-white relative">
    <div class="absolute inset-0 bg-[url('https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=1200&q=80')] bg-cover bg-center opacity-10"></div>
    <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <span class="text-xs font-semibold uppercase tracking-widest text-amber-500 mb-3 block">Asset Portfolio</span>
        <h1 class="text-4xl sm:text-5xl font-bold tracking-tight mb-4">Property Catalog</h1>
        <p class="max-w-xl mx-auto text-sm text-slate-400">Discover prime residential estates, commercial hubs, and agricultural acquisitions managed by Sarkin Mota HQ.</p>
    </div>
</section>

<!-- Advanced Filter Panel -->
<section class="py-8 bg-slate-100 dark:bg-slate-900 border-b border-slate-200/50 dark:border-slate-800/50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <form action="properties.php" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
            <div>
                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Search Keywords</label>
                <input type="text" name="search" value="<?php echo htmlspecialchars($search_query); ?>" placeholder="e.g. Victoria Island..." class="w-full bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg px-3 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500">
            </div>

            <div>
                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Property Type</label>
                <select name="type" class="w-full bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg px-3 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500">
                    <option value="">All Types</option>
                    <option value="residential" <?php echo ($type_filter === 'residential') ? 'selected' : ''; ?>>Residential</option>
                    <option value="commercial" <?php echo ($type_filter === 'commercial') ? 'selected' : ''; ?>>Commercial</option>
                    <option value="agricultural" <?php echo ($type_filter === 'agricultural') ? 'selected' : ''; ?>>Agricultural</option>
                </select>
            </div>

            <div>
                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Category</label>
                <select name="category" class="w-full bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg px-3 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500">
                    <option value="">Buy or Rent</option>
                    <option value="buy" <?php echo ($cat_filter === 'buy') ? 'selected' : ''; ?>>For Sale</option>
                    <option value="rent" <?php echo ($cat_filter === 'rent') ? 'selected' : ''; ?>>For Rent</option>
                </select>
            </div>

            <div>
                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Price Range</label>
                <select name="price_range" class="w-full bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg px-3 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500">
                    <option value="">All Prices</option>
                    <option value="under_10m" <?php echo ($price_filter === 'under_10m') ? 'selected' : ''; ?>>Under ₦10M</option>
                    <option value="10m_50m" <?php echo ($price_filter === '10m_50m') ? 'selected' : ''; ?>>₦10M - ₦50M</option>
                    <option value="50m_150m" <?php echo ($price_filter === '50m_150m') ? 'selected' : ''; ?>>₦50M - ₦150M</option>
                    <option value="over_150m" <?php echo ($price_filter === 'over_150m') ? 'selected' : ''; ?>>Above ₦150M</option>
                </select>
            </div>

            <div class="flex items-end gap-2">
                <button type="submit" class="w-full bg-amber-500 text-slate-950 font-bold py-2 text-xs rounded-lg uppercase tracking-wider hover:bg-amber-600 transition-colors shadow">
                    Apply Filter
                </button>
                <a href="properties.php" class="px-3 py-2 bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 rounded-lg text-xs font-bold hover:bg-slate-300 transition-colors">Reset</a>
            </div>
        </form>
    </div>
</section>

<!-- Property Grid Catalog -->
<section class="py-20 bg-white dark:bg-slate-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <?php if (empty($props_meta['data'])): ?>
            <!-- Genuine Empty State (F13 Resolution) -->
            <div class="text-center py-24 bg-slate-50 dark:bg-gray-950 border border-slate-200/50 dark:border-slate-800 rounded-3xl p-12 max-w-xl mx-auto">
                <i aria-hidden="true" class="bi bi-building-slash text-5xl text-slate-300 dark:text-slate-700 mb-4 block"></i>
                <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-2">No Matching Properties Found</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mb-6">There are currently no active property listings matching your specified search criteria.</p>
                <a href="properties.php" class="inline-block bg-amber-500 text-slate-950 text-xs font-bold px-6 py-3 rounded-lg uppercase tracking-wider">Reset Search Filters</a>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach ($props_meta['data'] as $prop): ?>
                    <div class="bg-slate-50 dark:bg-gray-950 border border-slate-200/50 dark:border-slate-800 rounded-3xl overflow-hidden shadow-sm flex flex-col justify-between hover:shadow-md transition-all">
                        <div>
                            <div class="h-56 overflow-hidden relative">
                                <img src="<?php echo htmlspecialchars(resolve_image_url($prop['image_url'] ?? 'assets/images/logo.png')); ?>" alt="<?php echo htmlspecialchars($prop['title']); ?>" class="w-full h-full object-cover">
                                <span class="absolute top-4 left-4 bg-amber-500 text-slate-950 font-bold text-[9px] uppercase tracking-wider px-3 py-1 rounded-full shadow">
                                    FOR <?php echo htmlspecialchars(strtoupper($prop['type'])); ?>
                                </span>
                            </div>
                            
                            <div class="p-6">
                                <span class="text-xs font-bold text-amber-500 block mb-1"><?php echo format_currency($prop['price']); ?></span>
                                <h3 class="text-base font-bold text-slate-900 dark:text-white mb-2 line-clamp-1"><?php echo htmlspecialchars($prop['title']); ?></h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400 flex items-center gap-1 mb-4">
                                    <i aria-hidden="true" class="bi bi-geo-alt-fill text-amber-500"></i> <?php echo htmlspecialchars($prop['location']); ?>
                                </p>
                                
                                <div class="flex items-center gap-4 text-xs text-slate-500 dark:text-slate-400 border-t border-slate-200/60 dark:border-slate-800 pt-3">
                                    <span><i aria-hidden="true" class="bi bi-door-closed text-amber-500"></i> <?php echo intval($prop['beds']); ?> Beds</span>
                                    <span><i aria-hidden="true" class="bi bi-droplet text-amber-500"></i> <?php echo intval($prop['baths']); ?> Baths</span>
                                    <span><i aria-hidden="true" class="bi bi-aspect-ratio text-amber-500"></i> <?php echo number_format(intval($prop['area_sqft'])); ?> sqft</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="px-6 pb-6 pt-2">
                            <a href="property-detail.php?id=<?php echo $prop['id']; ?>" class="block w-full text-center py-2.5 bg-slate-900 dark:bg-slate-800 hover:bg-amber-500 hover:text-slate-950 text-white font-bold text-xs rounded-xl uppercase tracking-wider transition-colors">
                                View Details & Inspection
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Server-Side Paginated Links (F14 Resolution) -->
            <?php echo render_pagination_links($props_meta, "properties.php?search=" . urlencode($search_query) . "&type=" . urlencode($type_filter) . "&category=" . urlencode($cat_filter) . "&price_range=" . urlencode($price_filter)); ?>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
