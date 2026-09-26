<?php
/**
 * Services Directory - Sarkin Mota HQ
 * Services Directory & Advisory Consultation Booking
 * Sarkin Mota HQ — Enterprise Edition
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth_helper.php';

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_consultation'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = "CSRF Token Verification Failed.";
    } else {
        $name = sanitize_input($_POST['name'] ?? '');
        $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL) ? sanitize_input($_POST['email']) : '';
        $phone = sanitize_input($_POST['phone'] ?? '');
        $division = sanitize_input($_POST['division'] ?? 'estate');
        $preferred_date = sanitize_input($_POST['preferred_date'] ?? '');
        $message = sanitize_input($_POST['message'] ?? '');
        $user_id = is_logged_in() ? $_SESSION['user_id'] : null;

        if (empty($name)) $errors[] = "Please enter your full name.";
        if (empty($email)) $errors[] = "Please enter a valid email address.";
        if (empty($phone)) $errors[] = "Please enter your phone number.";
        if (empty($preferred_date)) $errors[] = "Please choose a preferred date.";
        if (empty($message)) $errors[] = "Please describe your advisory requirement.";

        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO consultation_requests (user_id, name, email, phone, division, preferred_date, message, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'unread')
                ");
                $stmt->execute([$user_id, $name, $email, $phone, $division, $preferred_date, $message]);

                // Audit log
                $log = $pdo->prepare("INSERT INTO audit_logs (username, action, ip_address) VALUES (?, ?, ?)");
                $log->execute([$email, "Requested Consultation for Division: " . strtoupper($division), $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1']);

                set_flash_message('success', 'Your advisory consultation request has been submitted. Our senior consultants will review and contact you.');
                redirect('services.php?booked=1');
            } catch (Exception $e) {
                error_log("Consultation Request Error: " . $e->getMessage());
                $errors[] = "Failed to submit consultation request. Please try again later.";
            }
        }
    }
}

$page_title = "Corporate Services — Sarkin Mota HQ";
require_once __DIR__ . '/includes/header.php';
?>

<!-- Header Banner -->
<section class="py-16 md:py-20 bg-slate-950 text-white relative">
    <div class="absolute inset-0 bg-[url('https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?auto=format&fit=crop&w=1200&q=80')] bg-cover bg-center opacity-10"></div>
    <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <span class="text-[10px] sm:text-xs font-semibold uppercase tracking-widest text-amber-500 mb-2 sm:mb-3 block">Expertise Directory</span>
        <h1 class="text-3xl sm:text-4xl md:text-5xl font-bold tracking-tight mb-3 sm:mb-4">Our Professional Services</h1>
        <p class="max-w-xl mx-auto text-xs sm:text-sm text-slate-400 leading-relaxed">Institutional advisory designed to build compliance, secure asset values, and optimize African developments.</p>
    </div>
</section>

<!-- Services Grid -->
<section class="py-12 sm:py-20 md:py-24 bg-white dark:bg-slate-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <?php echo display_flash_message(); ?>

        <?php if (!empty($errors)): ?>
            <div class="mb-8 border-l-4 border-rose-500 bg-rose-50 dark:bg-rose-950/20 p-4 rounded-r-md">
                <ul class="list-disc pl-5 text-xs text-rose-800 dark:text-rose-300 space-y-1">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Services 2x2 Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 sm:gap-8 md:gap-12 mb-16 md:mb-20">
            
            <!-- Category 1: Agriculture -->
            <div class="p-6 sm:p-8 rounded-3xl bg-slate-50 dark:bg-gray-950 border border-slate-200/50 dark:border-slate-800 shadow-sm flex flex-col justify-between luxury-card">
                <div>
                    <span class="text-emerald-500 text-3xl mb-4 block"><i aria-hidden="true" class="bi bi-flower1"></i></span>
                    <h3 class="text-lg sm:text-xl font-bold text-slate-900 dark:text-white mb-3 sm:mb-4">Agriculture Advisory</h3>
                    <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mb-6 leading-relaxed">
                        Maximizing profitability and crop yields through modern technological integration and asset management.
                    </p>
                    <ul class="space-y-3 text-xs sm:text-sm text-slate-600 dark:text-slate-400 mb-8">
                        <li class="flex items-center"><i class="bi bi-dot text-amber-500 mr-2 text-base" aria-hidden="true"></i> Soil Analysis & Crop Selection Mapping</li>
                        <li class="flex items-center"><i class="bi bi-dot text-amber-500 mr-2 text-base" aria-hidden="true"></i> Agribusiness Financial Feasibility Studies</li>
                        <li class="flex items-center"><i class="bi bi-dot text-amber-500 mr-2 text-base" aria-hidden="true"></i> Irrigation & Mechanized Farming Plans</li>
                        <li class="flex items-center"><i class="bi bi-dot text-amber-500 mr-2 text-base" aria-hidden="true"></i> Plantation & Farm Machinery Valuations</li>
                    </ul>
                </div>
                <a href="divisions/agriculture.php" class="inline-flex items-center text-xs font-bold text-emerald-600 hover:text-emerald-700 uppercase tracking-widest">
                    Agriculture Division <i class="bi bi-arrow-right ml-2" aria-hidden="true"></i>
                </a>
            </div>

            <!-- Category 2: Estate & Property -->
            <div class="p-6 sm:p-8 rounded-3xl bg-slate-50 dark:bg-gray-950 border border-slate-200/50 dark:border-slate-800 shadow-sm flex flex-col justify-between luxury-card">
                <div>
                    <span class="text-amber-500 text-3xl mb-4 block"><i aria-hidden="true" class="bi bi-building"></i></span>
                    <h3 class="text-lg sm:text-xl font-bold text-slate-900 dark:text-white mb-3 sm:mb-4">Estate & Property Management</h3>
                    <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mb-6 leading-relaxed">
                        Determining real property values, managing commercial structures, and coordinating leasing transactions.
                    </p>
                    <ul class="space-y-3 text-xs sm:text-sm text-slate-600 dark:text-slate-400 mb-8">
                        <li class="flex items-center"><i class="bi bi-dot text-amber-500 mr-2 text-base" aria-hidden="true"></i> Certified Asset Valuations (RICS/NIESV standard)</li>
                        <li class="flex items-center"><i class="bi bi-dot text-amber-500 mr-2 text-base" aria-hidden="true"></i> Property Agency, Leasing & Brokerage</li>
                        <li class="flex items-center"><i class="bi bi-dot text-amber-500 mr-2 text-base" aria-hidden="true"></i> Comprehensive Building & Estate Facility Management</li>
                        <li class="flex items-center"><i class="bi bi-dot text-amber-500 mr-2 text-base" aria-hidden="true"></i> Land Registry Acquisition & Title Regularization</li>
                    </ul>
                </div>
                <a href="divisions/estate-management.php" class="inline-flex items-center text-xs font-bold text-amber-500 hover:text-amber-600 uppercase tracking-widest">
                    Estate Division <i class="bi bi-arrow-right ml-2" aria-hidden="true"></i>
                </a>
            </div>

            <!-- Category 3: Environmental -->
            <div class="p-6 sm:p-8 rounded-3xl bg-slate-50 dark:bg-gray-950 border border-slate-200/50 dark:border-slate-800 shadow-sm flex flex-col justify-between luxury-card">
                <div>
                    <span class="text-emerald-600 text-3xl mb-4 block"><i aria-hidden="true" class="bi bi-eyedropper"></i></span>
                    <h3 class="text-lg sm:text-xl font-bold text-slate-900 dark:text-white mb-3 sm:mb-4">Environmental Advisory</h3>
                    <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mb-6 leading-relaxed">
                        Analyzing environmental parameters and securing state permits to safeguard biodiversity and resource compliance.
                    </p>
                    <ul class="space-y-3 text-xs sm:text-sm text-slate-600 dark:text-slate-400 mb-8">
                        <li class="flex items-center"><i class="bi bi-dot text-amber-500 mr-2 text-base" aria-hidden="true"></i> Environmental Impact Assessment (EIA) Reports</li>
                        <li class="flex items-center"><i class="bi bi-dot text-amber-500 mr-2 text-base" aria-hidden="true"></i> Post-Impact Mitigation Audit Reporting</li>
                        <li class="flex items-center"><i class="bi bi-dot text-amber-500 mr-2 text-base" aria-hidden="true"></i> Waste Management & Discharges Auditing</li>
                        <li class="flex items-center"><i class="bi bi-dot text-amber-500 mr-2 text-base" aria-hidden="true"></i> Soil Remediation & Degraded Land Reclamation Plans</li>
                    </ul>
                </div>
                <a href="divisions/environmental.php" class="inline-flex items-center text-xs font-bold text-emerald-600 hover:text-emerald-700 uppercase tracking-widest">
                    Environmental Division <i class="bi bi-arrow-right ml-2" aria-hidden="true"></i>
                </a>
            </div>

            <!-- Category 4: Development -->
            <div class="p-6 sm:p-8 rounded-3xl bg-slate-50 dark:bg-gray-950 border border-slate-200/50 dark:border-slate-800 shadow-sm flex flex-col justify-between luxury-card">
                <div>
                    <span class="text-amber-500 text-3xl mb-4 block"><i aria-hidden="true" class="bi bi-map"></i></span>
                    <h3 class="text-lg sm:text-xl font-bold text-slate-900 dark:text-white mb-3 sm:mb-4">Development Consults</h3>
                    <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mb-6 leading-relaxed">
                        Guiding municipal development, urban layout design, and institutional reform policy drafting.
                    </p>
                    <ul class="space-y-3 text-xs sm:text-sm text-slate-600 dark:text-slate-400 mb-8">
                        <li class="flex items-center"><i class="bi bi-dot text-amber-500 mr-2 text-base" aria-hidden="true"></i> Master Planning & City Zoning blueprints</li>
                        <li class="flex items-center"><i class="bi bi-dot text-amber-500 mr-2 text-base" aria-hidden="true"></i> Demographics & Socio-Economic Field Research</li>
                        <li class="flex items-center"><i class="bi bi-dot text-amber-500 mr-2 text-base" aria-hidden="true"></i> State Administrative Capacity Building Manuals</li>
                        <li class="flex items-center"><i class="bi bi-dot text-amber-500 mr-2 text-base" aria-hidden="true"></i> Infrastructure Project Management & Supervision</li>
                    </ul>
                </div>
                <a href="divisions/development.php" class="inline-flex items-center text-xs font-bold text-amber-500 hover:text-amber-600 uppercase tracking-widest">
                    Development Division <i class="bi bi-arrow-right ml-2" aria-hidden="true"></i>
                </a>
            </div>

        </div>

        <!-- Book Consultation Request Section -->
        <div class="bg-slate-50 dark:bg-gray-950 border border-slate-200/50 dark:border-slate-800 rounded-3xl p-6 sm:p-10 md:p-12 shadow-lg max-w-4xl mx-auto">
            <div class="max-w-2xl mx-auto text-center mb-8">
                <span class="text-[10px] font-bold text-amber-500 uppercase tracking-widest block mb-2">Book Corporate Advisory</span>
                <h2 class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white">Request Executive Consultation</h2>
                <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-2">Schedule a formal consultation session with our certified principal consultants.</p>
            </div>

            <form action="services.php" method="POST" class="max-w-2xl mx-auto space-y-4">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <input type="hidden" name="request_consultation" value="1">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="cons_name" class="block text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-1">Full Name</label>
                        <input type="text" id="cons_name" name="name" required placeholder="Fatima Umar" value="<?php echo is_logged_in() ? htmlspecialchars($_SESSION['user_name'] ?? '') : ''; ?>" class="w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500">
                    </div>

                    <div>
                        <label for="cons_email" class="block text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-1">Email Address</label>
                        <input type="email" id="cons_email" name="email" required placeholder="fatima.umar@sarkinmotahq.com" value="<?php echo is_logged_in() ? htmlspecialchars($_SESSION['user_email'] ?? '') : ''; ?>" class="w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label for="cons_phone" class="block text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-1">Phone Number</label>
                        <input type="text" id="cons_phone" name="phone" required placeholder="+234 800 000 0000" class="w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500">
                    </div>
                    <div>
                        <label for="cons_division" class="block text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-1">Division</label>
                        <select id="cons_division" name="division" class="w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500">
                            <option value="estate">Estate & Property Management</option>
                            <option value="agriculture">Agriculture Advisory</option>
                            <option value="environmental">Environmental Advisory</option>
                            <option value="development">Development Consults</option>
                        </select>
                    </div>
                    <div>
                        <label for="cons_date" class="block text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-1">Preferred Date</label>
                        <input type="date" id="cons_date" name="preferred_date" required min="<?php echo date('Y-m-d'); ?>" class="w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500">
                    </div>
                </div>

                <div>
                    <label for="cons_message" class="block text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-1">Project Brief / Details</label>
                    <textarea id="cons_message" name="message" rows="4" required placeholder="Describe your property, land asset, valuation, or environmental audit requirements..." class="w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500"></textarea>
                </div>

                <button type="submit" class="w-full bg-amber-500 text-slate-950 font-bold uppercase tracking-wider text-xs py-3.5 rounded-xl hover:bg-amber-600 transition-colors shadow-md mt-2">
                    Submit Consultation Request
                </button>
            </form>
        </div>

    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
