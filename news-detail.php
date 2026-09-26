<?php
/**
 * News Article Details View - Sarkin Mota HQ
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/header.php';

$article_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$article = null;

// Mock list fallback
$mock_articles = [
    1 => [
        'id' => 1,
        'title' => 'Agricultural Assets and Credit Readiness in Nigeria',
        'content' => "Agricultural enterprise scale-up remains a critical pillar for food security across Sub-Saharan Africa. However, securing formal credit from commercial lenders historically poses significant challenges for local farmers.\n\nAt Sarkin Mota HQ, our Agronomy and Agribusiness Valuation teams have engineered a comprehensive advisory system to bridge this structural gap. By mapping soil capabilities, designing detailed crop cycle budgets, and certifying farm machinery asset valuations, we construct audit-ready corporate dossiers that satisfy formal bank risk profiles.\n\nThis publication examines the core guidelines of crop risk mitigation, mechanized asset cataloging, and soil sustainability audits needed to secure expansion credit from financial institutions.",
        'author' => 'Dr. Ken Davies',
        'published_at' => '2026-08-15 10:00:00',
        'image_url' => 'https://images.unsplash.com/photo-1593113598332-cd288d649433?auto=format&fit=crop&w=800&q=80'
    ],
    2 => [
        'id' => 2,
        'title' => 'The Future of Real Estate Valuation Post-2026',
        'content' => "The real estate landscapes of emerging markets are undergoing major structural transformations. Modern property assets are no longer valued solely on square footage or geographical positioning.\n\nToday, institutional buyers and certified valuation systems evaluate properties based on energy resilience, green gridding capabilities, environmental mitigation scores, and smart utility efficiencies.\n\nThis article outlines the emerging benchmarks of eco-audits, detailing how building materials and sustainable installations impact real estate valuations.",
        'author' => 'Arc. Aminu Yola',
        'published_at' => '2026-07-28 09:30:00',
        'image_url' => 'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?auto=format&fit=crop&w=800&q=80'
    ]
];

try {
    $is_staff_admin = is_logged_in() && (is_admin() || is_super_admin() || has_permission('news.manage'));
    if ($is_staff_admin) {
        $stmt = $pdo->prepare("SELECT * FROM news WHERE id = ?");
        $stmt->execute([$article_id]);
    } else {
        $stmt = $pdo->prepare("SELECT * FROM news WHERE id = ? AND status = 'published'");
        $stmt->execute([$article_id]);
    }
    $article = $stmt->fetch();
} catch (Exception $e) {
    // Graceful error logging
}

if (!$article && isset($mock_articles[$article_id])) {
    $article = $mock_articles[$article_id];
}

if (!$article) {
    set_flash_message('danger', 'The requested article could not be found.');
    redirect('news.php');
}
?>

<!-- Article Detail View -->
<article class="py-24 bg-white dark:bg-slate-900">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header metadata -->
        <div class="text-center mb-12">
            <span class="text-xs font-semibold text-gold-500 uppercase tracking-widest block mb-4">Corporate Insights</span>
            <h1 class="text-3xl sm:text-5xl font-extrabold text-slate-900 dark:text-white leading-tight tracking-tight mb-6">
                <?php echo sanitize_input($article['title']); ?>
            </h1>
            <div class="flex items-center justify-center text-xs text-slate-400 space-x-4">
                <span><i aria-hidden="true" class="bi bi-pencil-square text-gold-500 mr-1.5"></i> By <?php echo sanitize_input($article['author']); ?></span>
                <span>•</span>
                <span><i aria-hidden="true" class="bi bi-calendar3 text-gold-500 mr-1.5"></i> <?php echo date('F d, Y', strtotime($article['published_at'])); ?></span>
            </div>
        </div>
        
        <!-- Large Image -->
        <div class="h-[45vh] rounded-3xl overflow-hidden border border-slate-200/50 dark:border-slate-800 shadow-md mb-12 bg-slate-900">
            <img src="<?php echo sanitize_input($article['image_url']); ?>" alt="Article cover image" class="w-full h-full object-cover">
        </div>

        <!-- Full Text Body -->
        <div class="prose prose-slate dark:prose-invert max-w-none text-slate-600 dark:text-slate-350 text-sm leading-relaxed whitespace-pre-line space-y-6">
            <?php echo nl2br(sanitize_input($article['content'])); ?>
        </div>

        <!-- Post Footer Navigator -->
        <div class="border-t border-slate-200/50 dark:border-slate-800 pt-8 mt-16 flex justify-between items-center">
            <a href="news.php" class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider hover:text-gold-500 transition-colors">
                <i class="bi bi-arrow-left ui-icon" aria-hidden="true"></i> Back to Publications
            </a>
        </div>

    </div>
</article>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
