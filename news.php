<?php
/**
 * Upgraded Corporate News & Thought Leadership - Sarkin Mota HQ
 * Enterprise Edition
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/pagination_helper.php';

// Retrieve search & category filter parameters
$search_query = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$cat_filter = isset($_GET['category']) ? sanitize_input($_GET['category']) : '';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;

$news_meta = ['data' => [], 'total' => 0, 'page' => 1, 'last_page' => 1];
try {
    $sql = "SELECT * FROM news WHERE status = 'published'";
    $params = [];
    
    if (!empty($search_query)) {
        $sql .= " AND (title LIKE ? OR content LIKE ? OR summary LIKE ?)";
        $params[] = "%$search_query%";
        $params[] = "%$search_query%";
        $params[] = "%$search_query%";
    }
    
    if (!empty($cat_filter)) {
        $sql .= " AND category = ?";
        $params[] = $cat_filter;
    }
    
    $sql .= " ORDER BY published_at DESC";
    
    $news_meta = paginate_query($pdo, $sql, $params, $page, 6);
} catch (Exception $e) {
    error_log("News Query Error: " . $e->getMessage());
}

$page_title = "News & Thought Leadership — Sarkin Mota HQ";
require_once __DIR__ . '/includes/header.php';
?>

<!-- Header Banner -->
<section class="py-20 bg-slate-950 text-white relative">
    <div class="absolute inset-0 bg-[url('https://images.unsplash.com/photo-1451187580459-43490279c0fa?auto=format&fit=crop&w=1200&q=80')] bg-cover bg-center opacity-10"></div>
    <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <span class="text-xs font-semibold uppercase tracking-widest text-amber-500 mb-3 block">Corporate Insights</span>
        <h1 class="text-4xl sm:text-5xl font-bold tracking-tight mb-4">News & Publications</h1>
        <p class="max-w-xl mx-auto text-sm text-slate-400">Thought leadership and publications addressing agricultural assets, real estate valuations, and sustainable development.</p>
    </div>
</section>

<!-- Search & Category Filters -->
<section class="py-8 bg-slate-100 dark:bg-slate-900 border-b border-slate-200/50 dark:border-slate-800/50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col md:flex-row md:items-center justify-between gap-6">
        
        <!-- Categories tabs link -->
        <div class="flex flex-wrap gap-2.5 text-xs font-semibold uppercase tracking-wider">
            <a href="news.php" class="px-4 py-2 rounded-lg <?php echo empty($cat_filter) ? 'bg-amber-500 text-slate-950 shadow-sm' : 'bg-white dark:bg-slate-950 text-slate-600 dark:text-slate-300 hover:bg-slate-200 transition-colors'; ?>">All Categories</a>
            <a href="news.php?category=Agriculture<?php echo !empty($search_query) ? '&search='.urlencode($search_query) : ''; ?>" class="px-4 py-2 rounded-lg <?php echo ($cat_filter === 'Agriculture') ? 'bg-amber-500 text-slate-950 shadow-sm' : 'bg-white dark:bg-slate-950 text-slate-600 dark:text-slate-300 hover:bg-slate-200 transition-colors'; ?>">Agriculture</a>
            <a href="news.php?category=Real%20Estate<?php echo !empty($search_query) ? '&search='.urlencode($search_query) : ''; ?>" class="px-4 py-2 rounded-lg <?php echo ($cat_filter === 'Real Estate') ? 'bg-amber-500 text-slate-950 shadow-sm' : 'bg-white dark:bg-slate-950 text-slate-600 dark:text-slate-300 hover:bg-slate-200 transition-colors'; ?>">Real Estate</a>
            <a href="news.php?category=Environment<?php echo !empty($search_query) ? '&search='.urlencode($search_query) : ''; ?>" class="px-4 py-2 rounded-lg <?php echo ($cat_filter === 'Environment') ? 'bg-amber-500 text-slate-950 shadow-sm' : 'bg-white dark:bg-slate-950 text-slate-600 dark:text-slate-300 hover:bg-slate-200 transition-colors'; ?>">Environment</a>
            <a href="news.php?category=Investment<?php echo !empty($search_query) ? '&search='.urlencode($search_query) : ''; ?>" class="px-4 py-2 rounded-lg <?php echo ($cat_filter === 'Investment') ? 'bg-amber-500 text-slate-950 shadow-sm' : 'bg-white dark:bg-slate-950 text-slate-600 dark:text-slate-300 hover:bg-slate-200 transition-colors'; ?>">Investment</a>
        </div>

        <!-- Keyword search form -->
        <form action="news.php" method="GET" class="flex gap-2">
            <?php if (!empty($cat_filter)): ?>
                <input type="hidden" name="category" value="<?php echo htmlspecialchars($cat_filter); ?>">
            <?php endif; ?>
            <input type="text" name="search" value="<?php echo htmlspecialchars($search_query); ?>" placeholder="Search articles..." class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg px-4 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500">
            <button type="submit" class="bg-amber-500 text-slate-950 font-bold px-4 py-2 text-xs rounded-lg uppercase tracking-wider hover:bg-amber-600 transition-colors">
                Search
            </button>
        </form>

    </div>
</section>

<!-- Articles Catalog Grid -->
<section class="py-20 bg-white dark:bg-slate-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <?php if (empty($news_meta['data'])): ?>
            <!-- Genuine Empty State (F13 Resolution) -->
            <div class="text-center py-24 bg-slate-50 dark:bg-gray-950 border border-slate-200/50 dark:border-slate-800 rounded-3xl p-12 max-w-xl mx-auto">
                <i aria-hidden="true" class="bi bi-newspaper text-5xl text-slate-300 dark:text-slate-700 mb-4 block"></i>
                <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-2">No Articles Found</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mb-6">No published articles match your specified category or search criteria.</p>
                <a href="news.php" class="inline-block bg-amber-500 text-slate-950 text-xs font-bold px-6 py-3 rounded-lg uppercase tracking-wider">Reset Search Filters</a>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach ($news_meta['data'] as $article): ?>
                    <article class="bg-slate-50 dark:bg-gray-950 border border-slate-200/50 dark:border-slate-800 rounded-3xl overflow-hidden shadow-sm flex flex-col justify-between hover:shadow-md transition-all">
                        <div>
                            <div class="h-48 overflow-hidden relative">
                                <img src="<?php echo htmlspecialchars($article['image_url'] ?: 'assets/images/logo.png'); ?>" alt="<?php echo htmlspecialchars($article['title']); ?>" class="w-full h-full object-cover">
                                <span class="absolute top-4 left-4 bg-amber-500 text-slate-950 font-bold text-[9px] uppercase tracking-wider px-3 py-1 rounded-full shadow">
                                    <?php echo htmlspecialchars($article['category']); ?>
                                </span>
                            </div>
                            
                            <div class="p-6">
                                <span class="text-[10px] text-slate-400 block mb-2 font-semibold"><?php echo date('d M Y', strtotime($article['published_at'])); ?> • By <?php echo htmlspecialchars($article['author']); ?></span>
                                <h2 class="text-base font-bold text-slate-900 dark:text-white mb-3 line-clamp-2"><?php echo htmlspecialchars($article['title']); ?></h2>
                                <p class="text-xs text-slate-500 dark:text-slate-400 line-clamp-3 leading-relaxed"><?php echo htmlspecialchars($article['summary']); ?></p>
                            </div>
                        </div>
                        <div class="px-6 pb-6">
                            <a href="news-detail.php?id=<?php echo $article['id']; ?>" class="inline-block text-xs font-bold text-amber-500 hover:underline uppercase tracking-wider">Read Full Article <i class="bi bi-arrow-right ui-icon" aria-hidden="true"></i></a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <!-- Pagination Links -->
            <?php echo render_pagination_links($news_meta, "news.php?category=" . urlencode($cat_filter) . "&search=" . urlencode($search_query)); ?>
        <?php endif; ?>

    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
