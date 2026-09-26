<?php
/**
 * Upgraded Property Details View - Sarkin Mota HQ
 * Enterprise Edition
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth_helper.php';

$property_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$property = null;
$similar_properties = [];
$errors = [];

// Handle Property Tour Inspection Booking POST Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_inspection'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = "CSRF Verification Failed. Please try submitting again.";
    } else {
        $name = sanitize_input($_POST['name'] ?? '');
        $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL) ? sanitize_input($_POST['email']) : '';
        $phone = sanitize_input($_POST['phone'] ?? '');
        $booking_date = sanitize_input($_POST['booking_date'] ?? '');
        $booking_time = sanitize_input($_POST['booking_time'] ?? '');
        $inspection_type = sanitize_input($_POST['inspection_type'] ?? 'in_person');
        $user_id = is_logged_in() ? $_SESSION['user_id'] : null;

        if (empty($name)) $errors[] = "Please provide your full name.";
        if (empty($email)) $errors[] = "Please provide a valid email address.";
        if (empty($phone)) $errors[] = "Please provide your contact phone number.";
        if (empty($booking_date)) $errors[] = "Please select a preferred inspection date.";
        if (empty($booking_time)) $errors[] = "Please select a preferred inspection time.";

        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO inspection_requests (property_id, user_id, name, email, phone, preferred_date, preferred_time, inspection_type, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'requested')
                ");
                $stmt->execute([$property_id, $user_id, $name, $email, $phone, $booking_date, $booking_time, $inspection_type]);

                try {
                    $log = $pdo->prepare("INSERT INTO audit_logs (username, action, ip_address) VALUES (?, ?, ?)");
                    $log->execute([$email, "Requested Property Inspection ID: {$property_id}", $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1']);
                } catch (Exception $e) {}

                set_flash_message('success', 'Inspection request submitted successfully. Our team will review and confirm your scheduled date.');
                redirect("property-detail.php?id={$property_id}&booked=1");
                exit;
            } catch (Exception $e) {
                error_log("Inspection Booking Error: " . $e->getMessage());
                $errors[] = "Failed to submit inspection request. Please try again.";
            }
        }
    }
}

// Fetch active property from database
try {
    $is_staff_admin = is_logged_in() && (is_admin() || is_super_admin() || has_permission('properties.manage'));
    if ($is_staff_admin) {
        $stmt = $pdo->prepare("SELECT * FROM properties WHERE id = ?");
        $stmt->execute([$property_id]);
    } else {
        $stmt = $pdo->prepare("SELECT * FROM properties WHERE id = ? AND listing_status = 'active'");
        $stmt->execute([$property_id]);
    }
    $property = $stmt->fetch();
} catch (Exception $e) {
    error_log("Property View Error: " . $e->getMessage());
}

// Mock properties fallback catalog
$mock_properties = [
    1 => [
        'id' => 1,
        'title' => 'The Sarkin Mota HQ Oasis Estate',
        'description' => 'A state-of-the-art luxury duplex situated in Maitama Extension. Engineered with eco-conscious materials, integrated solar panel arrays, smart home interfaces, and robust multi-tiered automated security. Features panoramic city views, lush botanical gardens, and premium swimming pool facilities.',
        'price' => 180000000.00,
        'location' => 'Maitama Extension, Abuja, Nigeria',
        'beds' => 5,
        'baths' => 6,
        'area_sqft' => 5400,
        'land_size_sqft' => 10800,
        'type' => 'sale',
        'category' => 'residential',
        'agent_name' => 'Arc. Aminu Yola',
        'video_url' => 'https://www.w3schools.com/html/mov_bbb.mp4',
        'gallery_urls' => 'https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?auto=format&fit=crop&w=800&q=80,https://images.unsplash.com/photo-1600566753376-12c8ab7fb75b?auto=format&fit=crop&w=800&q=80',
        'image_url' => 'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=800&q=80',
        'is_short_let' => 0,
        'latitude' => 9.0765,
        'longitude' => 7.3986
    ],
    2 => [
        'id' => 2,
        'title' => 'Prime Commercial Office Complex',
        'description' => 'Grade-A luxury corporate spaces along the ATM Road commercial strip in Yola. Complete with centralized climate systems, executive lounges, massive secure basement parking, dedicated fiber optic connectivity, and complete dual generator backup sets.',
        'price' => 4500000.00,
        'location' => 'ATM Road, Jimeta-Yola, Adamawa, Nigeria',
        'beds' => 0,
        'baths' => 4,
        'area_sqft' => 12000,
        'land_size_sqft' => 24000,
        'type' => 'rent',
        'category' => 'commercial',
        'agent_name' => 'Dr. Ken Davies',
        'video_url' => 'https://www.w3schools.com/html/mov_bbb.mp4',
        'gallery_urls' => 'https://images.unsplash.com/photo-1497366216548-37526070297c?auto=format&fit=crop&w=800&q=80',
        'image_url' => 'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?auto=format&fit=crop&w=800&q=80',
        'is_short_let' => 0,
        'latitude' => 9.2035,
        'longitude' => 12.4954
    ],
    3 => [
        'id' => 3,
        'title' => 'Agro-Development Mechanized Hectares',
        'description' => 'Vast agricultural acreage with rich fertile loam, fully assessed by our Agronomy division. Optimal terrain configurations for mechanized crop routines. Complete boundary surveys and state certificates of occupancy (C of O) registered under corporate custody.',
        'price' => 15000000.00,
        'location' => 'Kofare Agric Zone, Yola, Nigeria',
        'beds' => 0,
        'baths' => 0,
        'area_sqft' => 435600,
        'land_size_sqft' => 871200,
        'type' => 'lease',
        'category' => 'land',
        'agent_name' => 'Dr. Ken Davies',
        'video_url' => null,
        'gallery_urls' => null,
        'image_url' => 'https://images.unsplash.com/photo-1500937386664-56d1dfef3854?auto=format&fit=crop&w=800&q=80',
        'is_short_let' => 0,
        'latitude' => 9.2300,
        'longitude' => 12.4400
    ],
    4 => [
        'id' => 4,
        'title' => 'The Sarkin Mota HQ Executive Suites',
        'description' => 'Premium fully-serviced short let apartments optimal for visiting executives. Includes premium DSTV, high speed wifi, daily laundry, private security guard, and standby solar power.',
        'price' => 75000.00,
        'location' => 'Off Jawa Street, Jimeta-Yola, Nigeria',
        'beds' => 2,
        'baths' => 2,
        'area_sqft' => 1200,
        'land_size_sqft' => 2400,
        'type' => 'rent',
        'category' => 'residential',
        'agent_name' => 'Arc. Aminu Yola',
        'video_url' => 'https://www.w3schools.com/html/mov_bbb.mp4',
        'gallery_urls' => 'https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?auto=format&fit=crop&w=800&q=80',
        'image_url' => 'https://images.unsplash.com/photo-1545324418-cc1a3fa10c00?auto=format&fit=crop&w=800&q=80',
        'is_short_let' => 1,
        'latitude' => 9.2100,
        'longitude' => 12.4800
    ]
];

if (!$property && isset($mock_properties[$property_id])) {
    $property = $mock_properties[$property_id];
}

if (!$property) {
    set_flash_message('danger', 'The requested property listing could not be found.');
    redirect('properties.php');
    exit;
}

// Ensure default fields
$property['beds'] = $property['beds'] ?? 0;
$property['baths'] = $property['baths'] ?? 0;
$property['area_sqft'] = $property['area_sqft'] ?? 0;
$property['land_size_sqft'] = $property['land_size_sqft'] ?? 0;
$property['agent_name'] = $property['agent_name'] ?? 'Sarkin Mota HQ Advisory Team';
$property['is_short_let'] = $property['is_short_let'] ?? 0;
$property['latitude'] = !empty($property['latitude']) ? floatval($property['latitude']) : 9.0765;
$property['longitude'] = !empty($property['longitude']) ? floatval($property['longitude']) : 7.3986;

// Parse gallery images
$gallery = [];
if (!empty($property['gallery_urls'])) {
    $gallery = array_map('trim', explode(',', $property['gallery_urls']));
}

// Similar properties catalog
try {
    $sim_stmt = $pdo->prepare("SELECT * FROM properties WHERE category = ? AND id != ? LIMIT 3");
    $sim_stmt->execute([$property['category'], $property_id]);
    $similar_properties = $sim_stmt->fetchAll();
} catch (Exception $e) {}

if (empty($similar_properties)) {
    foreach ($mock_properties as $p) {
        if ($p['id'] !== $property['id'] && $p['category'] === $property['category']) {
            $similar_properties[] = $p;
        }
    }
}

$page_title = htmlspecialchars($property['title']) . " — Sarkin Mota HQ";
require_once __DIR__ . '/includes/header.php';
?>



<!-- Listing Details Content -->
<section class="py-8 sm:py-12 bg-slate-50 dark:bg-slate-950 min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Breadcrumb Navigation -->
        <nav aria-label="Breadcrumb" class="flex mb-6 text-xs text-slate-400 space-x-2 items-center">
            <a href="index.php" class="hover:text-amber-500 transition-colors">Home</a>
            <span>/</span>
            <a href="properties.php" class="hover:text-amber-500 transition-colors">Properties</a>
            <span>/</span>
            <span class="text-slate-900 dark:text-white font-bold truncate max-w-xs sm:max-w-sm"><?php echo htmlspecialchars($property['title']); ?></span>
        </nav>

        <!-- Flash Messages & Form Errors -->
        <?php echo display_flash_message(); ?>
        <?php if (!empty($errors)): ?>
            <div class="mb-6 border-l-4 border-rose-500 bg-rose-50 dark:bg-rose-950/30 p-4 rounded-r-xl shadow-sm">
                <ul class="list-disc pl-5 text-xs text-rose-700 dark:text-rose-300 space-y-1 font-semibold">
                    <?php foreach ($errors as $err): ?>
                        <li><?php echo htmlspecialchars($err); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Header Information -->
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-8 bg-white dark:bg-slate-900 p-6 sm:p-8 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm">
            <div>
                <div class="flex flex-wrap gap-2 mb-3">
                    <span class="inline-block px-3 py-1 text-[10px] font-bold uppercase tracking-wider bg-amber-500/10 text-amber-600 dark:text-amber-400 rounded-full border border-amber-500/30">
                        FOR <?php echo strtoupper(htmlspecialchars($property['type'])); ?>
                    </span>
                    <span class="inline-block px-3 py-1 text-[10px] font-bold uppercase tracking-wider bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 rounded-full border border-slate-200 dark:border-slate-700">
                        <?php echo strtoupper(htmlspecialchars($property['category'])); ?>
                    </span>
                    <?php if ($property['is_short_let']): ?>
                        <span class="inline-block px-3 py-1 text-[10px] font-bold uppercase tracking-wider bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 rounded-full border border-emerald-500/30">
                            Short Let Suite
                        </span>
                    <?php endif; ?>
                </div>
                <h1 class="text-2xl sm:text-4xl font-extrabold text-slate-900 dark:text-white tracking-tight leading-tight mb-2">
                    <?php echo htmlspecialchars($property['title']); ?>
                </h1>
                <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 flex items-center font-medium">
                    <i aria-hidden="true" class="bi bi-geo-alt-fill text-amber-500 mr-2 text-base"></i> <?php echo htmlspecialchars($property['location']); ?>
                </p>
            </div>
            <div class="text-left md:text-right shrink-0 border-t md:border-t-0 pt-4 md:pt-0 border-slate-100 dark:border-slate-800">
                <span class="text-2xl sm:text-4xl font-extrabold text-amber-500 block tracking-tight"><?php echo format_currency($property['price']); ?></span>
                <span class="text-[10px] text-slate-400 uppercase tracking-widest font-bold"><?php echo ($property['type'] === 'rent') ? ($property['is_short_let'] ? 'per night' : 'per annum') : 'total listing price'; ?></span>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 sm:gap-12">
            
            <!-- Main Content Area: Media, Specs, Description & Map -->
            <div class="lg:col-span-2 space-y-8 sm:space-y-12">
                
                <!-- Main Cover Image -->
                <div class="h-[320px] sm:h-[450px] rounded-3xl overflow-hidden border border-slate-200/80 dark:border-slate-800 shadow-md bg-slate-900">
                    <img src="<?php echo htmlspecialchars(resolve_image_url($property['image_url'] ?? 'assets/images/logo.png')); ?>" alt="<?php echo htmlspecialchars($property['title']); ?>" class="w-full h-full object-cover">
                </div>

                <!-- Secondary Photo Gallery -->
                <?php if (!empty($gallery)): ?>
                    <div>
                        <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-3">Photo Gallery</h3>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 sm:gap-4">
                            <?php foreach ($gallery as $img): ?>
                                <div class="h-28 sm:h-36 rounded-2xl overflow-hidden border border-slate-200/80 dark:border-slate-800 shadow-sm bg-slate-900">
                                    <img src="<?php echo htmlspecialchars(resolve_image_url($img)); ?>" alt="Gallery Image" class="w-full h-full object-cover hover:scale-105 transition-transform duration-500">
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Key Specifications Grid -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-6 sm:p-8 rounded-3xl shadow-sm">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider mb-6">Key Specifications</h3>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                        <div class="p-4 bg-slate-50 dark:bg-slate-950 rounded-2xl border border-slate-100 dark:border-slate-800 text-center">
                            <i class="bi bi-door-closed text-amber-500 text-xl block mb-1"></i>
                            <span class="text-slate-400 block text-[9px] uppercase font-bold tracking-wider">Bedrooms</span>
                            <span class="font-extrabold text-slate-900 dark:text-white text-base sm:text-lg"><?php echo intval($property['beds']); ?></span>
                        </div>
                        <div class="p-4 bg-slate-50 dark:bg-slate-950 rounded-2xl border border-slate-100 dark:border-slate-800 text-center">
                            <i class="bi bi-droplet-half text-amber-500 text-xl block mb-1"></i>
                            <span class="text-slate-400 block text-[9px] uppercase font-bold tracking-wider">Bathrooms</span>
                            <span class="font-extrabold text-slate-900 dark:text-white text-base sm:text-lg"><?php echo intval($property['baths']); ?></span>
                        </div>
                        <div class="p-4 bg-slate-50 dark:bg-slate-950 rounded-2xl border border-slate-100 dark:border-slate-800 text-center">
                            <i class="bi bi-bounding-box text-amber-500 text-xl block mb-1"></i>
                            <span class="text-slate-400 block text-[9px] uppercase font-bold tracking-wider">Building Area</span>
                            <span class="font-extrabold text-slate-900 dark:text-white text-base sm:text-lg"><?php echo number_format($property['area_sqft']); ?> <span class="text-xs font-normal">sqft</span></span>
                        </div>
                        <div class="p-4 bg-slate-50 dark:bg-slate-950 rounded-2xl border border-slate-100 dark:border-slate-800 text-center">
                            <i class="bi bi-aspect-ratio text-amber-500 text-xl block mb-1"></i>
                            <span class="text-slate-400 block text-[9px] uppercase font-bold tracking-wider">Land Size</span>
                            <span class="font-extrabold text-slate-900 dark:text-white text-base sm:text-lg"><?php echo number_format($property['land_size_sqft']); ?> <span class="text-xs font-normal">sqft</span></span>
                        </div>
                    </div>
                </div>

                <!-- Description & Features -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-6 sm:p-8 rounded-3xl shadow-sm space-y-6">
                    <h3 class="text-lg font-extrabold text-slate-900 dark:text-white tracking-tight">Property Overview</h3>
                    <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-300 leading-relaxed whitespace-pre-line">
                        <?php echo htmlspecialchars($property['description']); ?>
                    </p>

                    <h4 class="text-xs font-bold text-slate-400 uppercase tracking-widest pt-4 border-t border-slate-100 dark:border-slate-800">Included Amenities & Features</h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs font-semibold text-slate-700 dark:text-slate-300">
                        <span class="flex items-center"><i aria-hidden="true" class="bi bi-lightning-charge-fill text-amber-500 mr-2.5 text-sm"></i> Solar Backup Energy Grid</span>
                        <span class="flex items-center"><i aria-hidden="true" class="bi bi-shield-check text-amber-500 mr-2.5 text-sm"></i> 24/7 Multi-Tier Automated Security</span>
                        <span class="flex items-center"><i aria-hidden="true" class="bi bi-wifi text-amber-500 mr-2.5 text-sm"></i> High-Speed Dedicated Fiber Link</span>
                        <span class="flex items-center"><i aria-hidden="true" class="bi bi-droplet-fill text-amber-500 mr-2.5 text-sm"></i> Dedicated Borehole Water Infrastructure</span>
                        <span class="flex items-center"><i aria-hidden="true" class="bi bi-car-front-fill text-amber-500 mr-2.5 text-sm"></i> Secure Underground Parking Bays</span>
                        <span class="flex items-center"><i aria-hidden="true" class="bi bi-tree-fill text-amber-500 mr-2.5 text-sm"></i> Eco-Friendly Landscaping</span>
                    </div>
                </div>

                <!-- Video Walkthrough -->
                <?php if (!empty($property['video_url'])): ?>
                    <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-6 sm:p-8 rounded-3xl shadow-sm">
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider mb-4">Video Walkthrough</h3>
                        <div class="rounded-2xl overflow-hidden border border-slate-200 dark:border-slate-800 shadow-md bg-slate-950 aspect-video">
                            <video controls class="w-full h-full object-cover">
                                <source src="<?php echo htmlspecialchars($property['video_url']); ?>" type="video/mp4">
                                Your browser does not support HTML5 video.
                            </video>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Interactive Map Location -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-6 sm:p-8 rounded-3xl shadow-sm">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider mb-2">Geographical Location</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mb-4"><?php echo htmlspecialchars($property['location']); ?></p>
                    <div id="property-map" class="h-72 sm:h-80 rounded-2xl overflow-hidden border border-slate-200 dark:border-slate-800 shadow-inner z-10">
                        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3940.11559561203!2d7.4753661000000005!3d9.0532195!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x104e0b007c1fa41d%3A0x75c564b5037c4ada!2sSarkinMota%20Autos!5e0!3m2!1sen!2sng!4v1790427341094!5m2!1sen!2sng" class="w-full h-full" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="strict-origin-when-cross-origin"></iframe>
                    </div>
                </div>

                <!-- Mortgage & Amortization Calculator -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-6 sm:p-8 rounded-3xl shadow-sm">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider mb-1">Mortgage & Amortization Calculator</h3>
                    <p class="text-xs text-slate-400 mb-6">Estimate your monthly payment schedule instantly.</p>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs mb-6">
                        <div>
                            <label for="calc-price" class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Property Value (₦)</label>
                            <input type="number" id="calc-price" value="<?php echo floatval($property['price']); ?>" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl p-3 text-xs font-semibold text-slate-900 dark:text-white focus:outline-none focus:border-amber-500">
                        </div>
                        <div>
                            <label for="calc-down-pct" class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Down Payment (%)</label>
                            <input type="number" id="calc-down-pct" value="20" min="0" max="100" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl p-3 text-xs font-semibold text-slate-900 dark:text-white focus:outline-none focus:border-amber-500">
                        </div>
                        <div>
                            <label for="calc-rate" class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Annual Interest Rate (%)</label>
                            <input type="number" id="calc-rate" value="15" step="0.5" min="1" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl p-3 text-xs font-semibold text-slate-900 dark:text-white focus:outline-none focus:border-amber-500">
                        </div>
                        <div>
                            <label for="calc-term" class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Loan Tenure (Years)</label>
                            <input type="number" id="calc-term" value="20" min="1" max="35" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl p-3 text-xs font-semibold text-slate-900 dark:text-white focus:outline-none focus:border-amber-500">
                        </div>
                    </div>
                    
                    <div class="p-4 bg-slate-50 dark:bg-slate-950 border border-slate-200/80 dark:border-slate-800 rounded-2xl flex justify-between items-center">
                        <span class="text-xs text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider">Estimated Monthly Payment:</span>
                        <span id="monthly-payment-res" class="text-lg sm:text-xl font-extrabold text-emerald-600 dark:text-emerald-400">₦0.00</span>
                    </div>
                </div>

            </div>

            <!-- Sidebar Booking Form & Advisory Desk -->
            <div class="space-y-6">
                <div class="lg:sticky lg:top-24 space-y-6">
                    
                    <!-- Inspection Booking Form -->
                    <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-6 sm:p-8 rounded-3xl shadow-sm">
                        <h3 class="text-lg font-extrabold text-slate-900 dark:text-white tracking-tight mb-1">Book Inspection Tour</h3>
                        <p class="text-xs text-slate-400 mb-6">Schedule a physical site visit or virtual video tour with our consultants.</p>
                        
                        <form action="property-detail.php?id=<?php echo $property_id; ?>" method="POST" class="space-y-4">
                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                            <input type="hidden" name="book_inspection" value="1">
                            
                            <div>
                                <label for="insp_name" class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Full Name</label>
                                <input type="text" id="insp_name" name="name" required placeholder="Babatunde Adeleke" value="<?php echo is_logged_in() ? htmlspecialchars($_SESSION['user_name'] ?? '') : ''; ?>" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3 py-2.5 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 font-medium">
                            </div>

                            <div>
                                <label for="insp_email" class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Email Address</label>
                                <input type="email" id="insp_email" name="email" required placeholder="babatunde.adeleke@sarkinmotahq.com" value="<?php echo is_logged_in() ? htmlspecialchars($_SESSION['user_email'] ?? '') : ''; ?>" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3 py-2.5 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 font-medium">
                            </div>

                            <div>
                                <label for="insp_phone" class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Phone Number</label>
                                <input type="text" id="insp_phone" name="phone" required placeholder="+234 800 000 0000" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3 py-2.5 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 font-medium">
                            </div>

                            <div>
                                <label for="insp_type" class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Inspection Format</label>
                                <select id="insp_type" name="inspection_type" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3 py-2.5 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 font-medium">
                                    <option value="in_person">Physical Site Tour</option>
                                    <option value="virtual">Virtual Live Video Tour</option>
                                </select>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label for="insp_date" class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Preferred Date</label>
                                    <input type="date" id="insp_date" name="booking_date" required min="<?php echo date('Y-m-d'); ?>" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3 py-2.5 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500">
                                </div>
                                <div>
                                    <label for="insp_time" class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Preferred Time</label>
                                    <input type="time" id="insp_time" name="booking_time" required class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3 py-2.5 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500">
                                </div>
                            </div>

                            <button type="submit" class="w-full bg-amber-500 text-slate-950 font-bold uppercase tracking-wider text-xs py-3.5 rounded-xl hover:bg-amber-600 transition-colors shadow-md mt-2">
                                Submit Inspection Request
                            </button>
                        </form>
                    </div>

                    <!-- Assigned Advisory Officer & WhatsApp Contact -->
                    <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-6 sm:p-8 rounded-3xl shadow-sm space-y-4">
                        <div class="flex items-center space-x-3 pb-4 border-b border-slate-100 dark:border-slate-800">
                            <div class="w-10 h-10 rounded-full bg-amber-500/10 text-amber-500 flex items-center justify-center font-bold text-lg">
                                <i class="bi bi-person-badge"></i>
                            </div>
                            <div>
                                <span class="block text-[9px] uppercase font-bold text-slate-400 tracking-wider">Assigned Advisory Agent</span>
                                <span class="font-extrabold text-slate-900 dark:text-white text-sm"><?php echo htmlspecialchars($property['agent_name']); ?></span>
                            </div>
                        </div>

                        <a href="https://wa.me/2348000000000?text=I%20am%20interested%20in%20property%20listing:%20<?php echo urlencode($property['title']); ?>" target="_blank" class="w-full py-3.5 bg-emerald-600 text-white font-bold uppercase tracking-wider text-xs rounded-xl hover:bg-emerald-700 transition-colors shadow-md flex items-center justify-center space-x-2">
                            <i aria-hidden="true" class="bi bi-whatsapp text-lg"></i>
                            <span>Contact Agent via WhatsApp</span>
                        </a>

                        <a href="contact.php" class="w-full py-3 border border-slate-200 dark:border-slate-800 hover:border-amber-500 text-slate-700 dark:text-slate-300 font-bold uppercase tracking-wider text-xs rounded-xl transition-all flex items-center justify-center space-x-2">
                            <i aria-hidden="true" class="bi bi-envelope mr-1"></i>
                            <span>Send Formal Inquiry</span>
                        </a>
                    </div>

                </div>
            </div>

        </div>

        <!-- Similar Properties Recommendation Panel -->
        <?php if (!empty($similar_properties)): ?>
            <div class="mt-16 sm:mt-24 pt-12 border-t border-slate-200/80 dark:border-slate-800">
                <h3 class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight mb-8">Similar Properties</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 sm:gap-8">
                    <?php foreach ($similar_properties as $sim): ?>
                        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-3xl overflow-hidden shadow-sm hover:shadow-md transition-shadow">
                            <div class="h-48 overflow-hidden bg-slate-900">
                                <img src="<?php echo htmlspecialchars(resolve_image_url($sim['image_url'] ?? 'assets/images/logo.png')); ?>" alt="<?php echo htmlspecialchars($sim['title']); ?>" class="w-full h-full object-cover">
                            </div>
                            <div class="p-6">
                                <span class="text-[9px] font-bold text-amber-500 uppercase tracking-wider block mb-1"><?php echo htmlspecialchars($sim['category']); ?></span>
                                <h4 class="font-bold text-slate-900 dark:text-white text-sm mb-2 truncate"><?php echo htmlspecialchars($sim['title']); ?></h4>
                                <span class="text-sm font-extrabold text-slate-900 dark:text-white block mb-4"><?php echo format_currency($sim['price']); ?></span>
                                <a href="property-detail.php?id=<?php echo $sim['id']; ?>" class="inline-flex items-center text-xs font-bold text-amber-500 hover:text-amber-600 uppercase tracking-wider">
                                    View Listing Details <i class="bi bi-arrow-right ml-1.5" aria-hidden="true"></i>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Mortgage Calculator Logic
    const priceInput = document.getElementById('calc-price');
    const downInput = document.getElementById('calc-down-pct');
    const rateInput = document.getElementById('calc-rate');
    const termInput = document.getElementById('calc-term');
    const resOutput = document.getElementById('monthly-payment-res');
    
    if (priceInput && downInput && rateInput && termInput && resOutput) {
        const calculateMortgage = () => {
            const principal = (+priceInput.value || 0) * (1 - ((+downInput.value || 0) / 100));
            const monthlyRate = ((+rateInput.value || 0) / 100) / 12;
            const totalMonths = (+termInput.value || 0) * 12;
            
            if (principal <= 0 || monthlyRate <= 0 || totalMonths <= 0) {
                resOutput.innerText = '₦0.00';
                return;
            }
            
            const monthlyPayment = principal * (monthlyRate * Math.pow(1 + monthlyRate, totalMonths)) / (Math.pow(1 + monthlyRate, totalMonths) - 1);
            resOutput.innerText = '₦' + monthlyPayment.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        };
        
        [priceInput, downInput, rateInput, termInput].forEach(inp => {
            inp.addEventListener('input', calculateMortgage);
        });
        
        calculateMortgage();
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
