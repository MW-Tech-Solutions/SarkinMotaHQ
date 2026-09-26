<?php
/**
 * Projects Showcase - Sarkin Mota HQ
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/header.php';

// Filter by division
$selected_division = isset($_GET['division']) ? sanitize_input($_GET['division']) : '';

$projects = [];
try {
    if (!empty($selected_division)) {
        $stmt = $pdo->prepare("SELECT * FROM projects WHERE division = ? ORDER BY id DESC");
        $stmt->execute([$selected_division]);
    } else {
        $stmt = $pdo->query("SELECT * FROM projects ORDER BY id DESC");
    }
    $projects = $stmt->fetchAll();
} catch (Exception $e) {
    // Dynamic fallbacks
    $all_mock_projects = [
        [
            'id' => 1,
            'title' => 'Adamawa Agribusiness Mechanized Hub',
            'description' => 'A comprehensive 5,000-hectare maize cultivation advisory, mapping irrigation paths and outlining high-yield seeds.',
            'division' => 'agriculture',
            'location' => 'Ganye Zone, Adamawa State',
            'completion_date' => '2025-11-20',
            'status' => 'completed',
            'image_url' => 'https://images.unsplash.com/photo-1500937386664-56d1dfef3854?auto=format&fit=crop&w=800&q=80'
        ],
        [
            'id' => 2,
            'title' => 'Maitama Luxury Tower Valuations',
            'description' => 'Official structural audit and investment asset valuation of a 12-story high-end residential complex in Abuja.',
            'division' => 'estate',
            'location' => 'Maitama, Abuja, Nigeria',
            'completion_date' => '2026-03-10',
            'status' => 'completed',
            'image_url' => 'https://images.unsplash.com/photo-1545324418-cc1a3fa10c00?auto=format&fit=crop&w=800&q=80'
        ],
        [
            'id' => 3,
            'title' => 'Industrial EIA & Drainage Mapping',
            'description' => 'Environmental Impact Assessment audit and mitigation plan for a processing facility near the Benue River basin.',
            'division' => 'environmental',
            'location' => 'Yola North, Adamawa, Nigeria',
            'completion_date' => '2026-06-15',
            'status' => 'completed',
            'image_url' => 'https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?auto=format&fit=crop&w=800&q=80'
        ],
        [
            'id' => 4,
            'title' => 'Jimeta Urban Master Plan Update',
            'description' => 'A municipal structural mapping initiative to design traffic grids, zoning regulations, and housing lines.',
            'division' => 'development',
            'location' => 'Jimeta Metro, Adamawa, Nigeria',
            'completion_date' => null,
            'status' => 'ongoing',
            'image_url' => 'https://images.unsplash.com/photo-1503387762-592dedb8c310?auto=format&fit=crop&w=800&q=80'
        ]
    ];
    
    if (!empty($selected_division)) {
        $projects = array_filter($all_mock_projects, function($proj) use ($selected_division) {
            return $proj['division'] === $selected_division;
        });
    } else {
        $projects = $all_mock_projects;
    }
}
?>

<!-- Header Banner -->
<section class="py-20 bg-slate-950 text-white relative">
    <div class="absolute inset-0 bg-[url('https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?auto=format&fit=crop&w=1200&q=80')] bg-cover bg-center opacity-10"></div>
    <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <span class="text-xs font-semibold uppercase tracking-widest text-gold-500 mb-3 block">Case Studies</span>
        <h1 class="text-4xl sm:text-5xl font-bold tracking-tight mb-4">Our Corporate Projects</h1>
        <p class="max-w-xl mx-auto text-sm text-slate-400">Reviewing our footprint across agriculture advisory, real estate, and municipal development initiatives.</p>
    </div>
</section>

<!-- Filter Controls -->
<section class="py-10 bg-slate-50 dark:bg-gray-950 border-b border-slate-200/50 dark:border-slate-800/50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-wrap items-center justify-center gap-3">
            <a href="projects.php" class="px-5 py-2.5 rounded-full text-xs font-semibold uppercase tracking-wider <?php echo empty($selected_division) ? 'bg-gold-500 text-slate-950 shadow-md' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-350 border border-slate-200 dark:border-slate-800 hover:border-gold-500 transition-colors'; ?>">
                All Divisions
            </a>
            <a href="projects.php?division=agriculture" class="px-5 py-2.5 rounded-full text-xs font-semibold uppercase tracking-wider <?php echo ($selected_division === 'agriculture') ? 'bg-gold-500 text-slate-950 shadow-md' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-350 border border-slate-200 dark:border-slate-800 hover:border-gold-500 transition-colors'; ?>">
                Agriculture
            </a>
            <a href="projects.php?division=estate" class="px-5 py-2.5 rounded-full text-xs font-semibold uppercase tracking-wider <?php echo ($selected_division === 'estate') ? 'bg-gold-500 text-slate-950 shadow-md' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-350 border border-slate-200 dark:border-slate-800 hover:border-gold-500 transition-colors'; ?>">
                Estate Management
            </a>
            <a href="projects.php?division=environmental" class="px-5 py-2.5 rounded-full text-xs font-semibold uppercase tracking-wider <?php echo ($selected_division === 'environmental') ? 'bg-gold-500 text-slate-950 shadow-md' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-350 border border-slate-200 dark:border-slate-800 hover:border-gold-500 transition-colors'; ?>">
                Environmental
            </a>
            <a href="projects.php?division=development" class="px-5 py-2.5 rounded-full text-xs font-semibold uppercase tracking-wider <?php echo ($selected_division === 'development') ? 'bg-gold-500 text-slate-950 shadow-md' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-350 border border-slate-200 dark:border-slate-800 hover:border-gold-500 transition-colors'; ?>">
                Development
            </a>
        </div>
    </div>
</section>

<!-- Projects Grid -->
<section class="py-24 bg-white dark:bg-slate-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <?php if (empty($projects)): ?>
            <div class="text-center py-20">
                <i aria-hidden="true" class="bi bi-folder-x text-slate-400 text-5xl block mb-4"></i>
                <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-2">No Projects Listed</h3>
                <p class="text-sm text-slate-500">There are currently no listed case studies for this division selection.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
                <?php foreach ($projects as $project): ?>
                    <div class="luxury-card bg-slate-50 dark:bg-gray-950 border border-slate-200/50 dark:border-slate-800 rounded-3xl overflow-hidden shadow-md">
                        <div class="relative h-72 overflow-hidden bg-slate-800">
                            <img src="<?php echo sanitize_input($project['image_url']); ?>" alt="<?php echo sanitize_input($project['title']); ?>" class="w-full h-full object-cover">
                            <span class="absolute top-4 left-4 bg-slate-950/90 text-white text-xs font-bold px-3 py-1 rounded-full uppercase tracking-widest">
                                <?php echo sanitize_input($project['division']); ?>
                            </span>
                            <span class="absolute bottom-4 right-4 bg-white dark:bg-slate-900 text-slate-900 dark:text-white text-xs font-bold px-3 py-1 rounded-full border border-slate-200 dark:border-slate-800">
                                <?php echo ($project['status'] === 'completed') ? 'Completed' : 'Ongoing'; ?>
                            </span>
                        </div>
                        <div class="p-8">
                            <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-2"><?php echo sanitize_input($project['title']); ?></h3>
                            <p class="text-xs text-slate-400 flex items-center mb-4">
                                <i aria-hidden="true" class="bi bi-geo-alt-fill text-gold-500 mr-1.5"></i> <?php echo sanitize_input($project['location']); ?>
                                <?php if (!empty($project['completion_date'])): ?>
                                    <span class="mx-3 text-slate-300">|</span>
                                    <i aria-hidden="true" class="bi bi-calendar3 text-gold-500 mr-1.5"></i> Completed: <?php echo date('M Y', strtotime($project['completion_date'])); ?>
                                <?php endif; ?>
                            </p>
                            <p class="text-sm text-slate-500 dark:text-slate-400 leading-relaxed mb-6">
                                <?php echo sanitize_input($project['description']); ?>
                            </p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
