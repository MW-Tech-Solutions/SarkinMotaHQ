<?php
/**
 * Generic / Custom Division Viewer Page - Sarkin Mota HQ
 * Dynamic Division Page — Managed by Super Admin
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/divisions_helper.php';

$requested_slug = trim($_GET['slug'] ?? '');
$division = null;
foreach (get_divisions(true) as $item) {
    if ($item['slug'] === $requested_slug) {
        $division = $item;
        break;
    }
}

if (!$division) {
    http_response_code(404);
}

// Fetch published projects for this division if valid
$division_projects = [];
if ($division) {
    try {
        $stmt_proj = $pdo->prepare("SELECT * FROM projects WHERE (division = ? OR division = ?) AND status = 'completed' ORDER BY id DESC LIMIT 8");
        $stmt_proj->execute([$division['slug'], $division['name']]);
        $division_projects = $stmt_proj->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Division projects error: " . $e->getMessage());
    }
}

$page_title = $division ? (htmlspecialchars($division['name']) . " | " . setting('company_short_name', 'Sarkin Mota HQ')) : ('Division Not Found | ' . setting('company_short_name', 'Sarkin Mota HQ'));
require_once __DIR__ . '/../includes/header.php';
?>

<?php if ($division): ?>
    <!-- Header Banner -->
    <section class="py-20 bg-slate-950 text-white relative">
        <div class="absolute inset-0 bg-cover bg-center opacity-20" style="background-image: url('<?php echo htmlspecialchars(resolve_image_url($division['banner_image'] ?? 'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?auto=format&fit=crop&w=1200&q=80')); ?>');"></div>
        <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <span class="text-xs font-semibold uppercase tracking-widest text-gold-500 mb-3 block">Corporate Divisions</span>
            <h1 class="text-4xl sm:text-5xl font-bold tracking-tight mb-4"><?php echo htmlspecialchars($division['name']); ?></h1>
            <p class="max-w-xl mx-auto text-sm text-slate-400"><?php echo htmlspecialchars($division['tagline'] ?? $division['description']); ?></p>
        </div>
    </section>

    <!-- Division Overview & Capabilities -->
    <section class="py-24 bg-white dark:bg-slate-900">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center mb-20">
                <div>
                    <span class="text-xs font-semibold uppercase tracking-widest text-gold-500 mb-3 block">Division Overview</span>
                    <h2 class="text-3xl font-bold text-slate-900 dark:text-white mb-6"><?php echo htmlspecialchars($division['name']); ?></h2>
                    <div class="prose dark:prose-invert text-slate-600 dark:text-slate-400 text-sm leading-relaxed whitespace-pre-line space-y-4">
                        <?php echo htmlspecialchars($division['content']); ?>
                    </div>
                </div>
                
                <div class="relative">
                    <div class="aspect-w-16 aspect-h-9 rounded-3xl overflow-hidden bg-slate-100 dark:bg-slate-800 shadow-xl border border-slate-200/50 dark:border-slate-800">
                        <img src="<?php echo htmlspecialchars(resolve_image_url($division['banner_image'] ?? 'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?auto=format&fit=crop&w=800&q=80')); ?>" alt="<?php echo htmlspecialchars($division['name']); ?>" class="w-full h-full object-cover">
                    </div>
                </div>
            </div>

            <!-- Division Projects Gallery (Dynamic from Super Admin Projects) -->
            <div class="border-t border-slate-100 dark:border-slate-800/80 pt-16">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
                    <div>
                        <span class="text-xs font-semibold uppercase tracking-widest text-gold-500 mb-1 block">Division Portfolio</span>
                        <h3 class="text-2xl font-bold text-slate-900 dark:text-white"><?php echo htmlspecialchars($division['name']); ?> Projects</h3>
                    </div>
                    <a href="../projects.php" class="text-xs font-bold text-gold-500 hover:text-gold-600 flex items-center gap-1">
                        View All Projects <i aria-hidden="true" class="bi bi-arrow-right"></i>
                    </a>
                </div>

                <?php if (!empty($division_projects)): ?>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                        <?php foreach ($division_projects as $proj): ?>
                            <div class="bg-slate-50 dark:bg-gray-950 border border-slate-200/50 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm hover:shadow-md transition-all">
                                <div class="h-44 bg-slate-200 dark:bg-slate-800 relative">
                                    <img src="<?php echo htmlspecialchars(resolve_image_url($proj['image_url'] ?? 'assets/images/logo.png')); ?>" alt="<?php echo htmlspecialchars($proj['title']); ?>" class="w-full h-full object-cover">
                                </div>
                                <div class="p-4 space-y-2">
                                    <h4 class="font-bold text-slate-900 dark:text-white text-xs truncate"><?php echo htmlspecialchars($proj['title']); ?></h4>
                                    <p class="text-[11px] text-slate-500 dark:text-slate-400 line-clamp-2"><?php echo htmlspecialchars($proj['description']); ?></p>
                                    <?php if (!empty($proj['location'])): ?>
                                        <span class="text-[10px] text-gold-500 font-bold block"><i aria-hidden="true" class="bi bi-geo-alt-fill mr-1"></i><?php echo htmlspecialchars($proj['location']); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="p-8 text-center text-xs text-slate-400 bg-slate-50 dark:bg-gray-950 rounded-2xl border border-slate-200 dark:border-slate-800">
                        No dedicated division projects published yet. Check back soon for project updates.
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </section>

    <!-- Call to Action -->
    <section class="py-20 bg-slate-50 dark:bg-gray-950 border-t border-slate-200/50 dark:border-slate-800/50">
        <div class="max-w-4xl mx-auto px-4 text-center">
            <h3 class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white mb-4">Request Consultation for <?php echo htmlspecialchars($division['name']); ?></h3>
            <p class="text-sm text-slate-500 dark:text-slate-400 mb-8 max-w-xl mx-auto">
                Get certified feasibility reports, planning audits, and strategic advisory provided by Sarkin Mota HQ experts.
            </p>
            <a href="../services.php" class="px-8 py-3.5 bg-gold-500 hover:bg-gold-600 text-slate-950 rounded-xl text-xs font-bold uppercase tracking-wider transition-colors inline-block shadow-md">
                Book <?php echo htmlspecialchars($division['name']); ?> Consultation
            </a>
        </div>
    </section>
<?php else: ?>
    <section class="py-24 bg-white dark:bg-slate-900 text-center">
        <div class="max-w-xl mx-auto px-4">
            <i aria-hidden="true" class="bi bi-exclamation-triangle text-5xl text-amber-500 mb-4 block"></i>
            <h1 class="text-3xl font-bold text-slate-900 dark:text-white mb-2">Division Not Found</h1>
            <p class="text-sm text-slate-500 mb-6">The requested division page does not exist or has been disabled.</p>
            <a href="../index.php" class="px-6 py-3 bg-gold-500 text-slate-950 rounded-xl text-xs font-bold uppercase tracking-wider transition-colors inline-block">Return Home</a>
        </div>
    </section>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
