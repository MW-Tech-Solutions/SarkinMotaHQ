<?php
/**
 * Upgraded Homepage - Sarkin Mota HQ
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/header.php';

// Fetch featured properties (limit 3)
$featured_properties = [];
try {
    $stmt = $pdo->query("SELECT * FROM properties WHERE status = 'active' ORDER BY id DESC LIMIT 3");
    $featured_properties = $stmt->fetchAll();
} catch (Exception $e) {
    // Graceful error handling
}

// Fallback if database empty
if (empty($featured_properties)) {
    $featured_properties = [
        [
            'id' => 1,
            'title' => 'Rolls-Royce Residences & Sky Villas',
            'description' => 'Ultra-luxury duplex designed with automated green grids and private showroom garage.',
            'price' => 450000000.00,
            'location' => 'Maitama Extension, Abuja, Nigeria',
            'beds' => 6,
            'baths' => 7,
            'area_sqft' => 7500,
            'land_size_sqft' => 15000,
            'type' => 'sale',
            'category' => 'residential',
            'image_url' => 'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=800&q=80'
        ],
        [
            'id' => 2,
            'title' => 'Maybach Executive Corporate Towers',
            'description' => 'Grade-A corporate office suites with executive lounges and fiber optics.',
            'price' => 12500000.00,
            'location' => 'Victoria Island Commercial Hub, Lagos, Nigeria',
            'beds' => 0,
            'baths' => 6,
            'area_sqft' => 14000,
            'land_size_sqft' => 28000,
            'type' => 'rent',
            'category' => 'commercial',
            'image_url' => 'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?auto=format&fit=crop&w=800&q=80'
        ],
        [
            'id' => 3,
            'title' => 'Porsche Design Crest Estate',
            'description' => 'Aerodynamic luxury smart villas featuring floor-to-ceiling glass curtain walls.',
            'price' => 290000000.00,
            'location' => 'Katampe Extension, Abuja, Nigeria',
            'beds' => 5,
            'baths' => 5,
            'area_sqft' => 6200,
            'land_size_sqft' => 12000,
            'type' => 'sale',
            'category' => 'residential',
            'image_url' => 'https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?auto=format&fit=crop&w=800&q=80'
        ]
    ];
}

// Fetch latest news (limit 2)
$latest_news = [];
try {
    $stmt = $pdo->query("SELECT * FROM news WHERE status = 'published' ORDER BY published_at DESC LIMIT 2");
    $latest_news = $stmt->fetchAll();
} catch (Exception $e) {
    // Fallback
    $latest_news = [
        [
            'id' => 1,
            'title' => 'Agricultural Assets and Credit Readiness in Nigeria',
            'summary' => 'Exploring how crop forecasting, soil auditing, and mechanized asset valuation enable local farmers to access bank credit loans.',
            'author' => 'Dr. Ken Davies',
            'published_at' => '2026-08-15 10:00:00',
            'image_url' => 'https://images.unsplash.com/photo-1593113598332-cd288d649433?auto=format&fit=crop&w=800&q=80'
        ],
        [
            'id' => 2,
            'title' => 'The Future of Real Estate Valuation Post-2026',
            'summary' => 'Analyzing how sustainability audits, solar integrations, and green structural indices impact property asset values.',
            'author' => 'Arc. Aminu Yola',
            'published_at' => '2026-07-28 09:30:00',
            'image_url' => 'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?auto=format&fit=crop&w=800&q=80'
        ]
    ];
}
?>



<!-- Hero Section with Background Video -->
<section class="relative min-h-[85vh] py-16 sm:py-24 flex items-center justify-center overflow-hidden bg-slate-950 text-white">
    <!-- Background Video Player -->
    <video autoplay muted loop playsinline class="absolute inset-0 w-full h-full object-cover opacity-35">
        <source src="<?php echo $path_depth; ?>assets/videos/hero_background.mp4" type="video/mp4">
        <source src="https://assets.mixkit.co/videos/preview/mixkit-modern-buildings-in-a-business-district-41846-large.mp4" type="video/mp4">
    </video>
    <div class="absolute inset-0 bg-gradient-to-t from-gray-950 via-gray-950/60 to-transparent"></div>
    
    <div class="relative z-10 max-w-5xl mx-auto px-4 sm:px-6 text-center">
        <span class="inline-block px-3.5 py-1 mb-4 sm:mb-6 text-[10px] sm:text-xs font-semibold uppercase tracking-widest bg-amber-500/10 border border-amber-500/30 text-amber-400 rounded-full animate-fade-in-up">
            Consultancy Excellence
        </span>
        <h1 class="text-3xl sm:text-5xl lg:text-7xl font-bold tracking-tight mb-3 sm:mb-4 leading-tight animate-fade-in-up animation-delay-100">
            SARKIN MOTA <span class="text-gradient-gold">HQ</span>
        </h1>
        <!-- Corporate Slogan -->
        <p class="text-[10px] sm:text-xs font-semibold uppercase tracking-[0.15em] sm:tracking-[0.2em] text-emerald-400 mb-6 sm:mb-8 animate-fade-in-up animation-delay-200">
            Agriculture | Estate & Property Management | Environmental & Development Consulting
        </p>
        <p class="max-w-2xl mx-auto text-xs sm:text-base md:text-lg text-slate-300 mb-8 sm:mb-10 leading-relaxed animate-fade-in-up animation-delay-300">
            Positioning corporate assets, agricultural resources, and infrastructure investments for maximum growth across Africa.
        </p>
        <div class="flex flex-col sm:flex-row justify-center items-center gap-3.5 sm:gap-4 animate-fade-in-up animation-delay-300 w-full max-w-md sm:max-w-none mx-auto">
            <a href="properties.php" class="w-full sm:w-auto px-6 sm:px-8 py-3.5 rounded-xl bg-amber-500 text-slate-950 font-bold uppercase tracking-wider text-xs hover:bg-amber-600 hover:scale-[1.02] transition-all duration-300 shadow-lg shadow-amber-500/20 text-center">
                Explore Property Catalog
            </a>
            <a href="contact.php" class="w-full sm:w-auto px-6 sm:px-8 py-3.5 rounded-xl border border-slate-400/80 text-white font-bold uppercase tracking-wider text-xs hover:bg-white hover:text-slate-950 hover:scale-[1.02] transition-all duration-300 text-center">
                Request Consultation
            </a>
        </div>
    </div>
</section>

<!-- Animated Statistics -->
<section class="stats-section bg-white dark:bg-slate-900 border-y border-slate-200/50 dark:border-slate-800/50 py-10 sm:py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-6 sm:gap-8 text-center">
            <div>
                <span class="block text-3xl sm:text-5xl font-extrabold text-slate-900 dark:text-white mb-1.5 sm:mb-2"><span class="stat-counter" data-target="150">0</span>+</span>
                <span class="block text-[10px] sm:text-xs uppercase tracking-widest text-slate-400 font-semibold">Projects Completed</span>
            </div>
            <div>
                <span class="block text-3xl sm:text-5xl font-extrabold text-slate-900 dark:text-white mb-1.5 sm:mb-2"><span class="stat-counter" data-target="4">0</span></span>
                <span class="block text-[10px] sm:text-xs uppercase tracking-widest text-slate-400 font-semibold">Specialized Divisions</span>
            </div>
            <div>
                <span class="block text-3xl sm:text-5xl font-extrabold text-slate-900 dark:text-white mb-1.5 sm:mb-2"><span class="stat-counter" data-target="10000">0</span>+</span>
                <span class="block text-[10px] sm:text-xs uppercase tracking-widest text-slate-400 font-semibold">Hectares Advisory</span>
            </div>
            <div>
                <span class="block text-3xl sm:text-5xl font-extrabold text-slate-900 dark:text-white mb-1.5 sm:mb-2">₦<span class="stat-counter" data-target="10">0</span>B+</span>
                <span class="block text-[10px] sm:text-xs uppercase tracking-widest text-slate-400 font-semibold">Assets Evaluated</span>
            </div>
        </div>
    </div>
</section>

<!-- Services Overview Segment -->
<section class="py-24 bg-slate-50 dark:bg-gray-950">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-3xl mx-auto mb-20">
            <span class="text-xs font-semibold uppercase tracking-widest text-gold-500 mb-3 block">Corporate Divisions</span>
            <h2 class="text-3xl sm:text-5xl font-bold tracking-tight text-slate-900 dark:text-white mb-4">Our Multidisciplinary Consulting</h2>
            <p class="text-slate-500 dark:text-slate-400">Delivering structural consulting across agricultural, property, and environmental sectors.</p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
            <div class="p-8 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/50 dark:border-slate-800 shadow-md">
                <span class="text-3xl mb-4 block text-emerald-500"><i aria-hidden="true" class="bi bi-flower1"></i></span>
                <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-3">Agriculture</h3>
                <p class="text-xs text-slate-500 leading-relaxed mb-6">Crop science studies, commercial farming layouts, and agricultural risk profiling.</p>
                <a href="divisions/agriculture.php" class="text-xs font-semibold text-gold-500 hover:underline">Explore Division <i class="bi bi-arrow-right ui-icon" aria-hidden="true"></i></a>
            </div>
            <div class="p-8 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/50 dark:border-slate-800 shadow-md">
                <span class="text-3xl mb-4 block text-gold-500"><i aria-hidden="true" class="bi bi-building"></i></span>
                <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-3">Property Management</h3>
                <p class="text-xs text-slate-500 leading-relaxed mb-6">Accredited property valuations, real estate agency, and building facility management.</p>
                <a href="divisions/estate-management.php" class="text-xs font-semibold text-gold-500 hover:underline">Explore Division <i class="bi bi-arrow-right ui-icon" aria-hidden="true"></i></a>
            </div>
            <div class="p-8 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/50 dark:border-slate-800 shadow-md">
                <span class="text-3xl mb-4 block text-emerald-600"><i aria-hidden="true" class="bi bi-eyedropper"></i></span>
                <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-3">Environmental</h3>
                <p class="text-xs text-slate-500 leading-relaxed mb-6">Environmental Impact Assessments (EIA), waste management, and reclamation audits.</p>
                <a href="divisions/environmental.php" class="text-xs font-semibold text-gold-500 hover:underline">Explore Division <i class="bi bi-arrow-right ui-icon" aria-hidden="true"></i></a>
            </div>
            <div class="p-8 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/50 dark:border-slate-800 shadow-md">
                <span class="text-3xl mb-4 block text-gold-500"><i aria-hidden="true" class="bi bi-map"></i></span>
                <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-3">Development</h3>
                <p class="text-xs text-slate-500 leading-relaxed mb-6">Urban master zoning, socio-economic surveys, and public sector capacity workshops.</p>
                <a href="divisions/development.php" class="text-xs font-semibold text-gold-500 hover:underline">Explore Division <i class="bi bi-arrow-right ui-icon" aria-hidden="true"></i></a>
            </div>
        </div>
    </div>
</section>

<!-- Featured Projects (Highlighting Case Studies) -->
<section class="py-24 bg-white dark:bg-slate-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-end mb-16">
            <div>
                <span class="text-xs font-semibold uppercase tracking-widest text-gold-500 mb-3 block">Corporate Showcase</span>
                <h2 class="text-3xl sm:text-5xl font-bold tracking-tight text-slate-900 dark:text-white">Featured Projects</h2>
            </div>
            <a href="projects.php" class="text-xs font-semibold text-gold-500 hover:underline flex items-center">
                All Case Studies <i class="bi bi-arrow-right ui-icon" aria-hidden="true"></i>
            </a>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div class="luxury-card bg-slate-50 dark:bg-gray-950 border border-slate-200/50 dark:border-slate-800 rounded-3xl overflow-hidden shadow-md">
                <div class="h-64 bg-slate-800 relative">
                    <img src="https://images.unsplash.com/photo-1500937386664-56d1dfef3854?auto=format&fit=crop&w=800&q=80" alt="Agro Hub" class="w-full h-full object-cover">
                    <span class="absolute top-4 left-4 bg-slate-950/90 text-white text-[10px] font-bold px-3 py-1 rounded-full uppercase tracking-wider">Agriculture</span>
                </div>
                <div class="p-8">
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-2">Adamawa Agribusiness Mechanized Hub</h3>
                    <p class="text-xs text-slate-400 mb-4 flex items-center"><i aria-hidden="true" class="bi bi-geo-alt-fill text-gold-500 mr-1"></i> Ganye Zone, Adamawa State</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed mb-4">A comprehensive 5,000-hectare mechanized agronomic advisory outlining soil capabilities, irrigation pipelines, and crop forecast layouts.</p>
                </div>
            </div>
            <div class="luxury-card bg-slate-50 dark:bg-gray-950 border border-slate-200/50 dark:border-slate-800 rounded-3xl overflow-hidden shadow-md">
                <div class="h-64 bg-slate-800 relative">
                    <img src="https://images.unsplash.com/photo-1545324418-cc1a3fa10c00?auto=format&fit=crop&w=800&q=80" alt="Urban planning project" class="w-full h-full object-cover">
                    <span class="absolute top-4 left-4 bg-slate-950/90 text-white text-[10px] font-bold px-3 py-1 rounded-full uppercase tracking-wider">Development</span>
                </div>
                <div class="p-8">
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-2">Jimeta Urban Master Plan Update</h3>
                    <p class="text-xs text-slate-400 mb-4 flex items-center"><i aria-hidden="true" class="bi bi-geo-alt-fill text-gold-500 mr-1"></i> Jimeta Metro, Adamawa State</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed mb-4">Collaborating with state zoning boards to update municipal structural guidelines, mapping transportation grids, zoning limits, and housing sectors.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Featured Properties Segment (Knight Frank Style) -->
<section class="py-24 bg-slate-50 dark:bg-gray-950 border-t border-slate-200/50 dark:border-slate-800/50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-end mb-16">
            <div>
                <span class="text-xs font-semibold uppercase tracking-widest text-gold-500 mb-3 block">Premium Real Estate</span>
                <h2 class="text-3xl sm:text-5xl font-bold tracking-tight text-slate-900 dark:text-white">Featured Properties</h2>
            </div>
            <a href="properties.php" class="text-xs font-semibold text-gold-500 hover:underline">
                Explore Listings <i class="bi bi-arrow-right ui-icon" aria-hidden="true"></i>
            </a>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <?php foreach ($featured_properties as $property): ?>
                <div class="luxury-card bg-white dark:bg-slate-900 border border-slate-200/50 dark:border-slate-800 rounded-2xl overflow-hidden shadow-md">
                    <div class="relative h-64 overflow-hidden bg-slate-800">
                        <img src="<?php echo htmlspecialchars(resolve_image_url($property['image_url'] ?? 'assets/images/logo.png')); ?>" alt="<?php echo sanitize_input($property['title']); ?>" class="w-full h-full object-cover">
                        <span class="absolute top-4 left-4 bg-slate-900/90 text-white text-[10px] font-bold px-3 py-1 rounded-full uppercase tracking-wider">
                            For <?php echo ucfirst(sanitize_input($property['type'])); ?>
                        </span>
                        <?php if (isset($property['is_short_let']) && $property['is_short_let']): ?>
                            <span class="absolute top-4 right-4 bg-emerald-600 text-white text-[10px] font-bold px-3 py-1 rounded-full">
                                SHORT LET
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="p-6">
                        <h3 class="text-base font-bold text-slate-900 dark:text-white mb-2 leading-snug"><?php echo sanitize_input($property['title']); ?></h3>
                        <p class="text-slate-400 text-[10px] flex items-center mb-3"><i aria-hidden="true" class="bi bi-geo-alt-fill text-gold-500 mr-1"></i> <?php echo sanitize_input($property['location']); ?></p>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mb-6 leading-relaxed"><?php echo limit_words(sanitize_input($property['description']), 14); ?></p>
                        
                        <div class="flex items-center space-x-6 border-t border-slate-200/50 dark:border-slate-800/50 pt-4 mb-6 text-[10px] text-slate-500">
                            <?php if ($property['beds'] > 0 || $property['baths'] > 0): ?>
                                <span><i aria-hidden="true" class="bi bi-house-door-fill text-gold-500 mr-1"></i> <?php echo $property['beds']; ?> Beds</span>
                                <span><i aria-hidden="true" class="bi bi-droplet-fill text-gold-500 mr-1"></i> <?php echo $property['baths']; ?> Baths</span>
                            <?php endif; ?>
                            <span><i aria-hidden="true" class="bi bi-rulers text-gold-500 mr-1"></i> Land: <?php echo number_format($property['land_size_sqft'] ?? 0); ?> sqft</span>
                        </div>
                        
                        <div class="flex items-center justify-between">
                            <span class="text-base font-extrabold text-gold-500"><?php echo format_currency($property['price']); ?></span>
                            <a href="property-detail.php?id=<?php echo $property['id']; ?>" class="text-[10px] font-bold text-slate-900 dark:text-white uppercase tracking-wider hover:text-gold-500 transition-colors">Details <i class="bi bi-arrow-right ui-icon" aria-hidden="true"></i></a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Latest News Segment -->
<section class="py-24 bg-white dark:bg-slate-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-end mb-16">
            <div>
                <span class="text-xs font-semibold uppercase tracking-widest text-gold-500 mb-3 block">Corporate Insights</span>
                <h2 class="text-3xl sm:text-5xl font-bold tracking-tight text-slate-900 dark:text-white">Latest Publications</h2>
            </div>
            <a href="news.php" class="text-xs font-semibold text-gold-500 hover:underline">
                Read All Articles <i class="bi bi-arrow-right ui-icon" aria-hidden="true"></i>
            </a>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
            <?php foreach ($latest_news as $art): ?>
                <article class="luxury-card bg-slate-50 dark:bg-gray-950 border border-slate-200/50 dark:border-slate-800 rounded-3xl overflow-hidden shadow-sm p-8 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center text-[10px] text-slate-400 mb-3 space-x-3">
                            <span><i aria-hidden="true" class="bi bi-pencil-square text-gold-500 mr-1"></i> <?php echo sanitize_input($art['author']); ?></span>
                            <span>•</span>
                            <span><i aria-hidden="true" class="bi bi-calendar3 text-gold-500 mr-1"></i> <?php echo date('d M Y', strtotime($art['published_at'])); ?></span>
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-3 hover:text-gold-500 transition-colors">
                            <a href="news-detail.php?id=<?php echo $art['id']; ?>"><?php echo sanitize_input($art['title']); ?></a>
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed mb-6"><?php echo sanitize_input($art['summary']); ?></p>
                    </div>
                    <a href="news-detail.php?id=<?php echo $art['id']; ?>" class="text-[10px] font-bold text-gold-500 hover:underline uppercase tracking-wider">Read Full Article <i class="bi bi-arrow-right ui-icon" aria-hidden="true"></i></a>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Client Testimonials carousel -->
<section class="py-24 bg-slate-50 dark:bg-gray-950 border-t border-slate-200/50 dark:border-slate-800/50">
    <div class="max-w-4xl mx-auto px-4 text-center">
        <span class="text-xs font-semibold uppercase tracking-widest text-gold-500 mb-3 block">Endorsements</span>
        <h2 class="text-3xl font-bold text-slate-900 dark:text-white mb-12">Client Testimonials</h2>
        
        <div id="testimonialCarousel" class="carousel slide relative" data-bs-ride="carousel">
            <!-- Modern Testimonial Card Container -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200/60 dark:border-slate-800 rounded-3xl p-8 sm:p-14 shadow-sm relative overflow-hidden">
                
                <!-- Decorative Quote Icon -->
                <i aria-hidden="true" class="bi bi-quote text-6xl text-gold-500/10 absolute top-4 left-6 pointer-events-none"></i>
                
                <div class="carousel-inner relative z-10 px-4 sm:px-12">
                    <div class="carousel-item active">
                        <p class="text-base sm:text-lg italic text-slate-600 dark:text-slate-300 leading-relaxed mb-6">
                            "The agricultural advisory team at Sarkin Mota HQ optimized our farm grids in Ganye, resulting in a 35% crop yield increase. Their mechanized planning and soil mapping are world-class."
                        </p>
                        <span class="block font-extrabold text-slate-900 dark:text-white text-sm">Alhaji Ibrahim Musa</span>
                        <span class="block text-slate-400 text-xs mt-0.5">Managing Director, Adamawa Grain Holdings</span>
                    </div>
                    <div class="carousel-item">
                        <p class="text-base sm:text-lg italic text-slate-600 dark:text-slate-300 leading-relaxed mb-6">
                            "We commissioned Sarkin Mota HQ for the structural valuation of our offices in Jimeta. Their certified documentation met international auditing compliance standard checks."
                        </p>
                        <span class="block font-extrabold text-slate-900 dark:text-white text-sm">Mrs. Florence Clark</span>
                        <span class="block text-slate-400 text-xs mt-0.5">CFO, West African Logistics Ltd.</span>
                    </div>
                </div>

                <!-- Previous Arrow Button (Positioned Safely Away From Text) -->
                <button class="absolute left-2 sm:left-4 top-1/2 -translate-y-1/2 h-10 w-10 rounded-full bg-slate-100 dark:bg-slate-800 hover:bg-gold-500 hover:text-slate-950 text-slate-600 dark:text-slate-300 flex items-center justify-center transition-all shadow-sm z-20 focus:outline-none border border-slate-200 dark:border-slate-700" type="button" data-bs-target="#testimonialCarousel" data-bs-slide="prev" aria-label="Previous testimonial">
                    <i aria-hidden="true" class="bi bi-chevron-left text-sm font-bold"></i>
                </button>
                
                <!-- Next Arrow Button (Positioned Safely Away From Text) -->
                <button class="absolute right-2 sm:right-4 top-1/2 -translate-y-1/2 h-10 w-10 rounded-full bg-slate-100 dark:bg-slate-800 hover:bg-gold-500 hover:text-slate-950 text-slate-600 dark:text-slate-300 flex items-center justify-center transition-all shadow-sm z-20 focus:outline-none border border-slate-200 dark:border-slate-700" type="button" data-bs-target="#testimonialCarousel" data-bs-slide="next" aria-label="Next testimonial">
                    <i aria-hidden="true" class="bi bi-chevron-right text-sm font-bold"></i>
                </button>
            </div>
        </div>
    </div>
</section>

<!-- Industry Partners grid -->
<section class="py-16 bg-white dark:bg-slate-900 border-t border-slate-200/50 dark:border-slate-800/50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <p class="text-center text-[10px] uppercase tracking-[0.25em] text-slate-400 font-bold mb-10">Trusted Partners & Accreditations</p>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-8 items-center justify-items-center opacity-55 dark:opacity-40">
            <!-- NIESV Standard -->
            <div class="text-slate-700 dark:text-slate-300 font-bold tracking-widest text-sm text-center">
                <span>NIESV NIGERIA</span>
            </div>
            <!-- RICS accredited -->
            <div class="text-slate-700 dark:text-slate-300 font-bold tracking-widest text-sm text-center">
                <span>RICS ACCREDITED</span>
            </div>
            <!-- Ministry of environment -->
            <div class="text-slate-700 dark:text-slate-300 font-bold tracking-widest text-sm text-center">
                <span>ENV MINISTRY</span>
            </div>
            <!-- COREN Engineering -->
            <div class="text-slate-700 dark:text-slate-300 font-bold tracking-widest text-sm text-center">
                <span>COREN COUNCIL</span>
            </div>
        </div>
    </div>
</section>

<!-- Interactive Map Section -->
<section class="py-24 bg-slate-50 dark:bg-gray-950 border-t border-slate-200/50 dark:border-slate-800/50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-3xl mx-auto mb-16">
            <span class="text-xs font-semibold uppercase tracking-widest text-gold-500 mb-3 block">Regional Scope</span>
            <h2 class="text-3xl font-bold text-slate-900 dark:text-white">Active Project Locations</h2>
            <p class="text-xs text-slate-500 mt-2">Explore our consultancies, audits, and real estate assets mapped live across the federation.</p>
        </div>
        
        <!-- Map Container -->
        <div id="interactive-map" class="h-96 sm:h-[480px] w-full rounded-3xl overflow-hidden border border-slate-200/60 dark:border-slate-800 shadow-lg bg-slate-100 dark:bg-slate-900 z-10">
            <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3940.11559561203!2d7.4753661000000005!3d9.0532195!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x104e0b007c1fa41d%3A0x75c564b5037c4ada!2sSarkinMota%20Autos!5e0!3m2!1sen!2sng!4v1790427341094!5m2!1sen!2sng" class="w-full h-full" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="strict-origin-when-cross-origin"></iframe>
        </div>
    </div>
</section>

<!-- Call-to-action Banner -->
<section class="py-20 bg-slate-900 text-white relative overflow-hidden">
    <div class="absolute inset-0 bg-[url('https://images.unsplash.com/photo-1500937386664-56d1dfef3854?auto=format&fit=crop&w=1200&q=80')] bg-cover bg-center opacity-10"></div>
    <div class="max-w-5xl mx-auto px-4 text-center relative z-10">
        <h2 class="text-3xl font-bold tracking-tight mb-4">Partner With African Consultancy Leaders</h2>
        <p class="max-w-2xl mx-auto text-xs text-slate-400 mb-8 leading-relaxed">
            Ready to design a new municipal layout, register land documents, commission a crop forecast model, or evaluate machinery assets? Contact our team.
        </p>
        <div class="flex flex-col sm:flex-row justify-center items-center gap-4">
            <a href="contact.php" class="w-full sm:w-auto px-8 py-3.5 bg-gold-500 text-slate-950 font-bold uppercase tracking-wider text-xs rounded-lg hover:bg-gold-600 transition-all">
                Initiate Project Brief
            </a>
            <a href="about.php" class="w-full sm:w-auto px-8 py-3.5 border border-slate-700 text-slate-350 hover:text-white hover:border-white font-bold uppercase tracking-wider text-xs rounded-lg transition-all">
                Learn Corporate Governance
            </a>
        </div>
    </div>
</section>

<!-- Mobile App Landing CTA Segment -->
<section class="py-24 bg-slate-950 text-white border-t border-b border-slate-800/80 relative overflow-hidden">
    <!-- Ambient Radial Glow Effects -->
    <div class="h-96 w-96 rounded-full bg-amber-500/10 blur-3xl absolute -top-10 -right-10 pointer-events-none"></div>
    <div class="h-96 w-96 rounded-full bg-emerald-500/10 blur-3xl absolute -bottom-10 -left-10 pointer-events-none"></div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-16 items-center">
            
            <!-- Left Column: Feature Highlights & Download CTAs (7 Cols) -->
            <div class="lg:col-span-7 space-y-8">
                <div>
                    <div class="flex items-center space-x-2 mb-3">
                        <span class="px-3 py-1 text-[10px] font-extrabold uppercase tracking-widest bg-amber-500/10 text-amber-400 rounded-full border border-amber-500/20 inline-flex items-center gap-1.5">
                            <i aria-hidden="true" class="bi bi-phone-vibrate-fill"></i> Corporate Mobility
                        </span>
                        <span class="text-xs text-slate-500 font-semibold">• Enterprise Digital Ecosystem</span>
                    </div>

                    <h2 class="text-3xl sm:text-5xl font-black tracking-tight text-white leading-tight">
                        <?php echo htmlspecialchars(setting('company_short_name', 'Sarkin Mota HQ')); ?> <span class="text-gradient-gold">on the Go</span>
                    </h2>

                    <p class="text-xs sm:text-sm text-slate-300 leading-relaxed mt-4 max-w-2xl">
                        Access our full suite of corporate consulting databases, real-time property portfolios, physical inspection tour schedules, digital lease signatures, and tenant billing portals directly from your smartphone. Built natively for iOS and Android with absolute performance, push notification updates, offline catalog caching, and built-in GPS mapping parameters.
                    </p>

                    <!-- Trust Metrics Badge Bar -->
                    <div class="flex flex-wrap items-center gap-6 mt-6 pt-4 border-t border-slate-800/60 text-xs">
                        <div class="flex items-center space-x-2">
                            <div class="flex text-amber-400 text-xs">
                                <i aria-hidden="true" class="bi bi-star-fill"></i>
                                <i aria-hidden="true" class="bi bi-star-fill"></i>
                                <i aria-hidden="true" class="bi bi-star-fill"></i>
                                <i aria-hidden="true" class="bi bi-star-fill"></i>
                                <i aria-hidden="true" class="bi bi-star-fill"></i>
                            </div>
                            <span class="font-bold text-white">4.9/5</span>
                            <span class="text-slate-400 text-[11px]">(1,450+ Reviews)</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span class="h-2 w-2 rounded-full bg-emerald-400 animate-pulse"></span>
                            <span class="font-semibold text-slate-300">10,000+ Active Mobile Downloads</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <i aria-hidden="true" class="bi bi-shield-lock-fill text-indigo-400"></i>
                            <span class="font-semibold text-slate-300">Bank-Grade Encryption</span>
                        </div>
                    </div>
                </div>

                <!-- 2x2 Feature Cards Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="p-4 rounded-2xl bg-slate-900/90 border border-slate-800/80 hover:border-amber-500/40 transition-all shadow-sm group">
                        <div class="h-10 w-10 rounded-xl bg-amber-500/10 text-amber-400 flex items-center justify-center text-lg mb-3 group-hover:scale-110 transition-transform">
                            <i aria-hidden="true" class="bi bi-bell-fill"></i>
                        </div>
                        <h3 class="text-xs font-bold text-white mb-1">Instant Push Notifications</h3>
                        <p class="text-[11px] text-slate-400 leading-normal">Real-time mobile updates for rent invoices, instant receipts, and new luxury listing announcements.</p>
                    </div>

                    <div class="p-4 rounded-2xl bg-slate-900/90 border border-slate-800/80 hover:border-emerald-500/40 transition-all shadow-sm group">
                        <div class="h-10 w-10 rounded-xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center text-lg mb-3 group-hover:scale-110 transition-transform">
                            <i aria-hidden="true" class="bi bi-geo-alt-fill"></i>
                        </div>
                        <h3 class="text-xs font-bold text-white mb-1">Turn-by-Turn GPS Navigation</h3>
                        <p class="text-[11px] text-slate-400 leading-normal">Integrated turn-by-turn GPS navigation for physical property inspection tours and site visits.</p>
                    </div>

                    <div class="p-4 rounded-2xl bg-slate-900/90 border border-slate-800/80 hover:border-indigo-500/40 transition-all shadow-sm group">
                        <div class="h-10 w-10 rounded-xl bg-indigo-500/10 text-indigo-400 flex items-center justify-center text-lg mb-3 group-hover:scale-110 transition-transform">
                            <i aria-hidden="true" class="bi bi-cloud-arrow-down-fill"></i>
                        </div>
                        <h3 class="text-xs font-bold text-white mb-1">Offline Access & Vault</h3>
                        <p class="text-[11px] text-slate-400 leading-normal">Offline access to cached property descriptions, land maps, C of O certificates, and lease documents.</p>
                    </div>

                    <div class="p-4 rounded-2xl bg-slate-900/90 border border-slate-800/80 hover:border-blue-500/40 transition-all shadow-sm group">
                        <div class="h-10 w-10 rounded-xl bg-blue-500/10 text-blue-400 flex items-center justify-center text-lg mb-3 group-hover:scale-110 transition-transform">
                            <i aria-hidden="true" class="bi bi-headset"></i>
                        </div>
                        <h3 class="text-xs font-bold text-white mb-1">24/7 Portal & Agronomy Desk</h3>
                        <p class="text-[11px] text-slate-400 leading-normal">One-tap maintenance requests, utility token purchases, and agricultural feasibility consults.</p>
                    </div>
                </div>

                <!-- Download Store Buttons & Quick Scan -->
                <div class="flex flex-wrap items-center gap-4 pt-2">
                    <!-- Apple App Store -->
                    <a href="javascript:void(0)" onclick="alert('iOS App is ready for distribution on Apple TestFlight & App Store.')" class="px-5 py-3.5 bg-slate-900 hover:bg-slate-800 border border-slate-800 hover:border-amber-500/40 text-white rounded-2xl transition-all inline-flex items-center space-x-3 shadow-md">
                        <i aria-hidden="true" class="bi bi-apple text-2xl text-white"></i>
                        <div class="text-left">
                            <span class="block text-[9px] font-semibold text-slate-400 uppercase tracking-wider leading-none">Download on the</span>
                            <span class="block text-xs font-bold text-white leading-tight mt-0.5">App Store</span>
                        </div>
                    </a>

                    <!-- Google Play Store -->
                    <a href="javascript:void(0)" onclick="alert('Android App is available on Google Play Console.')" class="px-5 py-3.5 bg-slate-900 hover:bg-slate-800 border border-slate-800 hover:border-amber-500/40 text-white rounded-2xl transition-all inline-flex items-center space-x-3 shadow-md">
                        <i aria-hidden="true" class="bi bi-google-play text-2xl text-emerald-400"></i>
                        <div class="text-left">
                            <span class="block text-[9px] font-semibold text-slate-400 uppercase tracking-wider leading-none">GET IT ON</span>
                            <span class="block text-xs font-bold text-white leading-tight mt-0.5">Google Play</span>
                        </div>
                    </a>

                    <!-- Direct Enterprise APK -->
                    <a href="javascript:void(0)" onclick="alert('Enterprise Android APK build downloading...')" class="px-5 py-3.5 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-slate-950 font-extrabold text-xs uppercase tracking-wider rounded-2xl transition-all shadow-lg inline-flex items-center space-x-2">
                        <i aria-hidden="true" class="bi bi-android2 text-base"></i>
                        <span>Download Android APK</span>
                    </a>
                </div>
            </div>

            <!-- Right Column: High-Fidelity Smartphone Mockup UI (5 Cols) -->
            <div class="lg:col-span-5 flex justify-center lg:justify-end">
                <div class="w-full max-w-[330px] rounded-[44px] border-[5px] border-slate-700/80 bg-slate-900 shadow-2xl overflow-hidden relative p-4 space-y-3.5">
                    
                    <!-- Top Status Bar & iPhone Dynamic Island Notch -->
                    <div class="flex items-center justify-between px-3 pt-0.5">
                        <span class="text-[10px] font-bold text-slate-300">9:41</span>
                        <div class="w-24 h-4 bg-slate-950 rounded-full flex items-center justify-center space-x-2 shadow-inner">
                            <div class="h-1.5 w-1.5 rounded-full bg-slate-800"></div>
                            <div class="h-2 w-2 rounded-full bg-slate-900 border border-slate-800"></div>
                        </div>
                        <div class="flex items-center space-x-1 text-[10px] text-slate-300">
                            <i aria-hidden="true" class="bi bi-wifi"></i>
                            <i aria-hidden="true" class="bi bi-battery-full text-emerald-400"></i>
                        </div>
                    </div>

                    <!-- App Header Profile -->
                    <div class="flex items-center justify-between px-2 pt-1">
                        <div class="flex items-center space-x-2.5">
                            <div class="h-8 w-8 rounded-full bg-gradient-to-tr from-amber-500 to-gold-400 text-slate-950 font-extrabold flex items-center justify-center text-xs shadow-md">
                                KH
                            </div>
                            <div>
                                <span class="block text-[11px] font-extrabold text-white leading-tight">Amina Okonkwo</span>
                                <span class="block text-[9px] text-emerald-400 font-semibold">• Active Client / Tenant</span>
                            </div>
                        </div>
                        <div class="h-7 w-7 rounded-xl bg-slate-800 text-slate-300 flex items-center justify-center text-xs border border-slate-700 relative">
                            <i aria-hidden="true" class="bi bi-bell-fill text-[11px] text-amber-400"></i>
                            <span class="absolute -top-1 -right-1 h-2 w-2 rounded-full bg-amber-500"></span>
                        </div>
                    </div>

                    <!-- App Quick Action Grid -->
                    <div class="grid grid-cols-4 gap-1.5 text-center">
                        <div class="p-2.5 bg-slate-950 rounded-xl border border-slate-800/80 hover:border-amber-500/40 transition-colors">
                            <i aria-hidden="true" class="bi bi-wallet2 text-amber-400 text-sm block mb-1"></i>
                            <span class="text-[8px] text-slate-300 font-semibold block">Pay Rent</span>
                        </div>
                        <div class="p-2.5 bg-slate-950 rounded-xl border border-slate-800/80 hover:border-emerald-500/40 transition-colors">
                            <i aria-hidden="true" class="bi bi-compass-fill text-emerald-400 text-sm block mb-1"></i>
                            <span class="text-[8px] text-slate-300 font-semibold block">GPS Tours</span>
                        </div>
                        <div class="p-2.5 bg-slate-950 rounded-xl border border-slate-800/80 hover:border-indigo-500/40 transition-colors">
                            <i aria-hidden="true" class="bi bi-file-earmark-check-fill text-indigo-400 text-sm block mb-1"></i>
                            <span class="text-[8px] text-slate-300 font-semibold block">Vault</span>
                        </div>
                        <div class="p-2.5 bg-slate-950 rounded-xl border border-slate-800/80 hover:border-blue-500/40 transition-colors">
                            <i aria-hidden="true" class="bi bi-headset text-blue-400 text-sm block mb-1"></i>
                            <span class="text-[8px] text-slate-300 font-semibold block">Support</span>
                        </div>
                    </div>

                    <!-- Active Inspection Tour Widget -->
                    <div class="bg-gradient-to-br from-slate-950 via-slate-900 to-slate-950 border border-slate-800 p-3 rounded-2xl space-y-2 shadow-inner">
                        <div class="flex items-center justify-between">
                            <span class="text-[8px] font-extrabold uppercase tracking-wider text-amber-400 flex items-center gap-1">
                                <i aria-hidden="true" class="bi bi-calendar2-check-fill"></i> Upcoming Inspection
                            </span>
                            <span class="px-2 py-0.5 rounded-full text-[7px] font-extrabold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">Scheduled</span>
                        </div>
                        <div>
                            <span class="block font-bold text-white text-[11px] leading-tight">Maitama Duplex Tour</span>
                            <div class="flex items-center space-x-2 text-[9px] text-slate-400 mt-0.5">
                                <i aria-hidden="true" class="bi bi-clock-history text-amber-400"></i>
                                <span>Tomorrow, 10:00 AM • GPS Active</span>
                            </div>
                        </div>
                    </div>

                    <!-- Rent Payment Status Widget -->
                    <div class="bg-slate-950 border border-slate-800 p-2.5 rounded-2xl flex items-center justify-between">
                        <div class="flex items-center space-x-2.5">
                            <div class="h-7 w-7 rounded-full bg-emerald-500/10 text-emerald-400 flex items-center justify-center text-xs shrink-0 border border-emerald-500/20">
                                <i aria-hidden="true" class="bi bi-check-lg font-bold"></i>
                            </div>
                            <div>
                                <span class="block text-[9px] font-bold text-white leading-tight">Rent Receipt Issued</span>
                                <span class="block text-[8px] text-slate-400 font-mono">INV-8032 • ₦180,000,000</span>
                            </div>
                        </div>
                        <span class="px-2 py-0.5 rounded-full text-[8px] text-emerald-400 bg-emerald-500/10 font-bold border border-emerald-500/20">Rent Paid</span>
                    </div>

                    <!-- Mobile Property Preview Card -->
                    <div class="bg-slate-950 rounded-2xl border border-slate-800 overflow-hidden shadow-sm">
                        <div class="relative">
                            <img src="https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?auto=format&fit=crop&w=400&q=80" alt="Rolls-Royce Residences" class="h-20 w-full object-cover">
                            <span class="absolute top-2 left-2 bg-slate-950/80 backdrop-blur-sm text-white text-[7px] font-bold px-2 py-0.5 rounded-md uppercase tracking-wider border border-slate-700">
                                Featured Listing
                            </span>
                        </div>
                        <div class="p-2 flex items-center justify-between">
                            <div>
                                <span class="block font-bold text-white text-[10px] truncate">Rolls-Royce Residences</span>
                                <span class="block text-[8px] text-slate-400">Maitama, Abuja</span>
                            </div>
                            <span class="px-2 py-1 bg-amber-500 text-slate-950 font-extrabold text-[8px] rounded-lg shadow-sm">₦180M/yr</span>
                        </div>
                    </div>

                    <!-- App Navigation Bar -->
                    <div class="pt-1 flex items-center justify-around text-slate-500 text-[11px] border-t border-slate-800/80">
                        <div class="text-amber-400 flex flex-col items-center">
                            <i aria-hidden="true" class="bi bi-house-door-fill"></i>
                            <span class="text-[6px] font-semibold mt-0.5">Home</span>
                        </div>
                        <div class="flex flex-col items-center hover:text-slate-300">
                            <i aria-hidden="true" class="bi bi-building"></i>
                            <span class="text-[6px] font-semibold mt-0.5">Catalog</span>
                        </div>
                        <div class="flex flex-col items-center hover:text-slate-300">
                            <i aria-hidden="true" class="bi bi-receipt"></i>
                            <span class="text-[6px] font-semibold mt-0.5">Billing</span>
                        </div>
                        <div class="flex flex-col items-center hover:text-slate-300">
                            <i aria-hidden="true" class="bi bi-person-circle"></i>
                            <span class="text-[6px] font-semibold mt-0.5">Account</span>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
