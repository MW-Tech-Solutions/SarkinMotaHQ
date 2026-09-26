<?php
/**
 * Upgraded Global Premium Header with Centralized Branding & Theme Engine
 * Enterprise Edition — Sarkin Mota HQ
 */
$root_dir = realpath(__DIR__ . '/..');
$script_file = $_SERVER['SCRIPT_FILENAME'] ?? '';
$path_depth = '';
if (!empty($script_file)) {
    $script_dir = realpath(dirname($script_file));
    if ($root_dir && $script_dir && strpos($script_dir, $root_dir) === 0) {
        $rel = trim(substr($script_dir, strlen($root_dir)), '/\\');
        if ($rel !== '') {
            $parts = array_filter(explode('/', str_replace('\\', '/', $rel)));
            $path_depth = str_repeat('../', count($parts));
        }
    }
}
if ($path_depth === '') {
    $current_uri = $_SERVER['PHP_SELF'] ?? '';
    if (preg_match('#/(admin|auth|hr|sales|staff|divisions|error_pages)/#i', $current_uri)) {
        $path_depth = '../';
    }
}

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth_helper.php';
require_once __DIR__ . '/settings_helper.php';
require_once __DIR__ . '/divisions_helper.php';
$navigation_divisions = function_exists('get_all_divisions') ? get_all_divisions(true) : (function_exists('get_divisions') ? get_divisions(true) : []);

start_secure_session();
$user = get_logged_in_user();

$company_name = setting('company_name', 'Sarkin Mota HQ');
$company_short = setting('company_short_name', 'Sarkin Mota HQ');
$company_tagline = setting('company_tagline', 'Enterprise Real Estate & Strategic Consulting');
$company_logo = setting('company_logo', 'assets/images/logo.png');
$company_logo_dark = setting('company_logo_dark', 'assets/images/logo.png');
$favicon_path = setting('favicon', 'assets/images/favicon.ico');

$page_title_text = isset($page_title) ? $page_title : $company_name . ' | Enterprise Real Estate & Advisory';
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Instant Anti-Flash Theme Engine Initialization -->
    <script>
        (function() {
            const theme = localStorage.getItem('sarkinmota_theme') || '<?php echo htmlspecialchars(setting('default_theme', 'system')); ?>';
            let effectiveTheme = theme;
            if (theme === 'system') {
                effectiveTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            }
            document.documentElement.setAttribute('data-theme', effectiveTheme);
            if (effectiveTheme === 'dark') {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        })();
    </script>
    
    <title><?php echo htmlspecialchars($page_title_text); ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?php echo $path_depth . htmlspecialchars($favicon_path); ?>">

    <!-- Meta Descriptions for SEO -->
    <meta name="description" content="<?php echo htmlspecialchars($company_tagline); ?> — Corporate Consulting in Agriculture, Real Estate, Environmental & Development.">
    <meta name="keywords" content="Real Estate, Agriculture Consulting, Environmental Impact Assessment, Development consulting, <?php echo htmlspecialchars($company_short); ?>, Property Management">
    <meta name="author" content="<?php echo htmlspecialchars($company_name); ?>">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo htmlspecialchars(setting('website_url')); ?>">
    <meta property="og:title" content="<?php echo htmlspecialchars($page_title_text); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($company_tagline); ?>">
    <meta property="og:image" content="<?php echo $path_depth . htmlspecialchars($company_logo); ?>">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700;800&family=Open+Sans:wght@400;600;700&family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
    <?php require_once __DIR__ . '/icon_assets.php'; ?>
    
    <!-- Tailwind CSS Integration -->
    <link rel="stylesheet" href="<?php echo $path_depth; ?>assets/css/style.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        amber: { 500: '<?php echo setting("primary_color", "#f59e0b"); ?>', 600: '<?php echo setting("primary_hover_color", "#d97706"); ?>' },
                        gold: { 500: '<?php echo setting("primary_color", "#f59e0b"); ?>', 600: '<?php echo setting("primary_hover_color", "#d97706"); ?>' }
                    }
                }
            }
        }
    </script>
    
    <!-- Dynamic Centralized Brand CSS Variables Engine -->
    <style id="global-brand-styles">
        <?php echo generate_brand_css(); ?>
    </style>

    <!-- Global Theme JS Engine -->
    <script src="<?php echo $path_depth; ?>assets/js/theme.js" defer></script>
</head>
<body class="bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 flex flex-col min-h-screen transition-colors duration-300">

<!-- Accessibility Skip Link -->
<a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-2 focus:left-2 bg-amber-500 text-slate-950 px-4 py-2 rounded-md font-bold z-50">Skip to Main Content</a>

<?php 
$is_portal_view = isset($is_portal_page) && $is_portal_page === true;
if (!$is_portal_view) {
    $script_path = $_SERVER['PHP_SELF'] ?? '';
    if (strpos($script_path, '/admin/') !== false || strpos($script_path, '/hr/') !== false || strpos($script_path, 'dashboard.php') !== false) {
        $is_portal_view = true;
    }
}
if (!$is_portal_view): 
?>
<!-- Top Corporate Info Bar (Desktop / Tablet) -->
<div class="hidden sm:flex bg-slate-950 text-slate-300 text-xs py-2 px-4 sm:px-8 justify-between items-center border-b border-slate-800 z-40">
    <div class="flex items-center space-x-6">
        <span class="truncate"><i aria-hidden="true" class="bi bi-geo-alt-fill text-amber-500 mr-1.5"></i> <?php echo htmlspecialchars(setting('company_address')); ?></span>
        <span class="hidden md:inline"><i aria-hidden="true" class="bi bi-telephone-fill text-amber-500 mr-1.5"></i> <?php echo htmlspecialchars(setting('company_phone')); ?></span>
        <span class="hidden lg:inline"><i aria-hidden="true" class="bi bi-envelope-fill text-amber-500 mr-1.5"></i> <?php echo htmlspecialchars(setting('company_email')); ?></span>
    </div>
    
    <div class="flex items-center space-x-4">
        <?php if ($user): ?>
            <span class="text-amber-500 font-bold hidden md:inline"><?php echo htmlspecialchars($user['name']); ?></span>
            <a href="<?php echo $path_depth; ?>auth/dashboard.php" class="hover:text-amber-500 font-semibold">Portal Dashboard</a>
            <a href="<?php echo $path_depth; ?>auth/logout.php" class="hover:text-amber-500 font-semibold">Sign Out</a>
        <?php else: ?>
            <a href="<?php echo $path_depth; ?>auth/login.php" class="hover:text-amber-500 font-semibold">Sign In</a>
            <span>|</span>
            <a href="<?php echo $path_depth; ?>auth/register.php" class="hover:text-amber-500 font-semibold">Register Portal</a>
        <?php endif; ?>
    </div>
</div>

<!-- Main Mobile & Desktop Navigation Header -->
<header class="bg-white/95 dark:bg-slate-900/95 backdrop-blur-md sticky top-0 z-40 border-b border-slate-200/80 dark:border-slate-800 shadow-md transition-all duration-200">
    <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 h-16 sm:h-20 flex items-center justify-between gap-2 sm:gap-4">
        
        <!-- Dynamic Brand Logo -->
        <a href="<?php echo $path_depth; ?>index.php" class="flex items-center space-x-2 sm:space-x-3 shrink-0 mr-2 sm:mr-4 lg:mr-8 xl:mr-10">
            <img src="<?php echo $path_depth . htmlspecialchars($company_logo); ?>" alt="<?php echo htmlspecialchars($company_name); ?> Logo" class="h-7 sm:h-10 w-auto object-contain dark:hidden shrink-0">
            <img src="<?php echo $path_depth . htmlspecialchars($company_logo_dark); ?>" alt="<?php echo htmlspecialchars($company_name); ?> Dark Logo" class="h-7 sm:h-10 w-auto object-contain hidden dark:block shrink-0">
            <div class="flex flex-col shrink-0">
                <span class="text-sm sm:text-lg font-extrabold text-slate-900 dark:text-white tracking-tight leading-none uppercase whitespace-nowrap"><?php echo htmlspecialchars($company_short); ?></span>
                <span class="text-[7px] sm:text-[9px] font-bold uppercase tracking-widest text-slate-400 whitespace-nowrap">Consults Ltd.</span>
            </div>
        </a>

        <!-- Desktop Navigation Links -->
        <nav class="hidden lg:flex items-center space-x-3 xl:space-x-6 text-[11px] xl:text-xs font-bold uppercase tracking-wider shrink-0">
            <a href="<?php echo $path_depth; ?>index.php" class="whitespace-nowrap hover:text-amber-500 transition-colors <?php echo is_active_page('index.php'); ?>">Home</a>
            <a href="<?php echo $path_depth; ?>about.php" class="whitespace-nowrap hover:text-amber-500 transition-colors <?php echo is_active_page('about.php'); ?>">About Us</a>
            
            <?php if ($navigation_divisions): ?>
<!-- Divisions Dropdown -->
            <div class="relative group/drop py-2 shrink-0">
                <button class="flex items-center space-x-1 text-slate-700 dark:text-slate-200 hover:text-amber-500 transition-colors uppercase whitespace-nowrap">
                    <span>Divisions</span>
                    <i aria-hidden="true" class="bi bi-chevron-down text-[10px]"></i>
                </button>
                <div class="absolute left-0 top-full mt-1 w-64 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xl opacity-0 translate-y-2 pointer-events-none group-focus-within/drop:opacity-100 group-focus-within/drop:translate-y-0 group-focus-within/drop:pointer-events-auto group-hover/drop:opacity-100 group-hover/drop:translate-y-0 group-hover/drop:pointer-events-auto transition-all duration-300 z-50 p-2 text-left normal-case">
                    <?php foreach ($navigation_divisions as $nav_division): ?>
<a href="<?php echo htmlspecialchars($path_depth . division_url($nav_division)); ?>" class="block px-4 py-2.5 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800">
<span class="block font-bold text-xs text-slate-900 dark:text-white"><?php echo htmlspecialchars($nav_division['name']); ?></span>
<span class="block text-[10px] text-slate-400"><?php echo htmlspecialchars($nav_division['description']); ?></span>
</a>
<?php endforeach; ?>
                </div>
            </div>

            <?php endif; ?>
            <a href="<?php echo $path_depth; ?>services.php" class="whitespace-nowrap hover:text-amber-500 transition-colors <?php echo is_active_page('services.php'); ?>">Services</a>
            <a href="<?php echo $path_depth; ?>properties.php" class="whitespace-nowrap hover:text-amber-500 transition-colors <?php echo is_active_page('properties.php'); ?>">Properties</a>
            <a href="<?php echo $path_depth; ?>cars.php" class="whitespace-nowrap hover:text-amber-500 transition-colors <?php echo is_active_page('cars.php'); ?>">Cars & Autos</a>
            <a href="<?php echo $path_depth; ?>projects.php" class="whitespace-nowrap hover:text-amber-500 transition-colors <?php echo is_active_page('projects.php'); ?>">Projects</a>
            <a href="<?php echo $path_depth; ?>news.php" class="whitespace-nowrap hover:text-amber-500 transition-colors <?php echo is_active_page('news.php'); ?>">News</a>
            <a href="<?php echo $path_depth; ?>careers.php" class="whitespace-nowrap hover:text-amber-500 transition-colors <?php echo is_active_page('careers.php'); ?>">Careers</a>
            <a href="<?php echo $path_depth; ?>contact.php" class="whitespace-nowrap hover:text-amber-500 transition-colors <?php echo is_active_page('contact.php'); ?>">Contact</a>
        </nav>

        <!-- Right Header Action Controls: [Theme] [Account Menu] [Mobile Drawer Menu] -->
        <div class="flex items-center space-x-1.5 sm:space-x-3 shrink-0 ml-auto">
            
            <!-- Single Theme Button with Dropdown (Public Mobile & Desktop) -->
            <?php if (setting('allow_theme_switching', '1') === '1'): ?>
                <div class="relative inline-block text-left shrink-0">
                    <button type="button" onclick="toggleThemeDropdown('header-theme-dropdown')" aria-label="Toggle Theme Menu" class="theme-dropdown-btn p-2 text-slate-700 dark:text-slate-200 hover:text-amber-500 focus:outline-none rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors flex items-center justify-center">
                        <i class="bi bi-circle-half single-theme-toggle-icon text-lg"></i>
                    </button>
                    <div id="header-theme-dropdown" class="theme-dropdown-menu hidden absolute right-0 mt-2 w-36 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-xl z-50 p-1 space-y-0.5 text-xs font-semibold">
                        <button type="button" onclick="setThemeMode('light'); toggleThemeDropdown('header-theme-dropdown');" class="theme-switcher-btn w-full text-left px-3 py-2 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-200 flex items-center space-x-2.5" data-theme-mode="light">
                            <i class="bi bi-sun text-amber-500 text-sm"></i>
                            <span>Light</span>
                        </button>
                        <button type="button" onclick="setThemeMode('dark'); toggleThemeDropdown('header-theme-dropdown');" class="theme-switcher-btn w-full text-left px-3 py-2 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-200 flex items-center space-x-2.5" data-theme-mode="dark">
                            <i class="bi bi-moon-stars text-indigo-400 text-sm"></i>
                            <span>Dark</span>
                        </button>
                        <button type="button" onclick="setThemeMode('system'); toggleThemeDropdown('header-theme-dropdown');" class="theme-switcher-btn w-full text-left px-3 py-2 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-200 flex items-center space-x-2.5" data-theme-mode="system">
                            <i class="bi bi-circle-half text-slate-400 text-sm"></i>
                            <span>Auto</span>
                        </button>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Compact Account Icon Button & Dropdown -->
            <div class="relative inline-block text-left shrink-0">
                <button type="button" onclick="toggleAccountMenu()" aria-label="Account Options" class="p-2 text-slate-700 dark:text-slate-200 hover:text-amber-500 focus:outline-none rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors flex items-center justify-center">
                    <i aria-hidden="true" class="bi bi-person-circle text-xl"></i>
                </button>
                <div id="header-account-menu" class="hidden absolute right-0 mt-2 w-48 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xl z-50 p-2 space-y-1 text-xs font-semibold">
                    <?php if ($user): ?>
                        <div class="px-3 py-2 border-b border-slate-100 dark:border-slate-800">
                            <span class="block font-bold text-slate-900 dark:text-white truncate"><?php echo htmlspecialchars($user['name']); ?></span>
                            <span class="block text-[10px] text-amber-500 font-bold uppercase tracking-wider"><?php echo htmlspecialchars($user['role']); ?></span>
                        </div>
                        <a href="<?php echo $path_depth; ?>auth/dashboard.php" class="block px-3 py-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-200">
                            <i class="bi bi-speedometer2 mr-2 text-amber-500"></i> Portal Dashboard
                        </a>
                        <a href="<?php echo $path_depth; ?>auth/logout.php" class="block px-3 py-2 rounded-xl hover:bg-rose-50 dark:hover:bg-rose-950/40 text-rose-600 dark:text-rose-400">
                            <i class="bi bi-box-arrow-right mr-2"></i> Sign Out
                        </a>
                    <?php else: ?>
                        <a href="<?php echo $path_depth; ?>auth/login.php" class="block px-3 py-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-200">
                            <i class="bi bi-box-arrow-in-right mr-2 text-amber-500"></i> Sign In
                        </a>
                        <a href="<?php echo $path_depth; ?>auth/register.php" class="block px-3 py-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-200">
                            <i class="bi bi-person-plus mr-2 text-amber-500"></i> Register Account
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Desktop Browse CTA (Visible on XL screens 1280px+) -->
            <a href="<?php echo $path_depth; ?>properties.php" class="hidden xl:inline-block bg-amber-500 text-slate-950 font-bold text-xs px-4 py-2.5 rounded-xl uppercase tracking-wider hover:bg-amber-600 transition-colors shadow shrink-0 whitespace-nowrap">
                Browse Properties
            </a>

            <!-- Mobile Drawer Menu Toggle Button -->
            <button type="button" onclick="toggleMobileNav(true)" aria-label="Open Navigation Drawer" class="lg:hidden p-2 text-slate-700 dark:text-slate-200 hover:text-amber-500 focus:outline-none shrink-0">
                <i aria-hidden="true" class="bi bi-list text-2xl"></i>
            </button>
        </div>
    </div>

    <!-- Mobile Off-Canvas Drawer Backdrop -->
    <div id="mobile-nav-backdrop" onclick="toggleMobileNav(false)" class="fixed inset-0 bg-slate-950/80 backdrop-blur-md z-50 hidden transition-opacity duration-300 lg:hidden"></div>

    <!-- Off-Canvas Slide-Out Mobile Navigation Drawer -->
    <div id="mobile-nav" class="fixed top-0 right-0 bottom-0 w-80 max-w-[85vw] bg-white dark:bg-slate-900 border-l border-slate-200 dark:border-slate-800 z-50 overflow-y-auto p-5 sm:p-6 flex flex-col justify-between shadow-2xl transform translate-x-full transition-transform duration-300 ease-in-out lg:hidden">
        <div>
            <!-- Mobile Drawer Header -->
            <div class="pb-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between mb-6">
                <div class="flex items-center space-x-2 shrink-0">
                    <img src="<?php echo $path_depth . htmlspecialchars($company_logo); ?>" alt="<?php echo htmlspecialchars($company_name); ?>" class="h-7 w-auto object-contain dark:hidden shrink-0">
                    <img src="<?php echo $path_depth . htmlspecialchars($company_logo_dark); ?>" alt="<?php echo htmlspecialchars($company_name); ?>" class="h-7 w-auto object-contain hidden dark:block shrink-0">
                    <div class="flex flex-col">
                        <span class="text-sm font-extrabold uppercase text-slate-900 dark:text-white tracking-tight leading-none"><?php echo htmlspecialchars($company_short); ?></span>
                        <span class="text-[8px] font-bold uppercase tracking-widest text-slate-400">Consults Ltd.</span>
                    </div>
                </div>
                <button type="button" onclick="toggleMobileNav(false)" aria-label="Close Mobile Menu" class="p-2 text-slate-500 hover:text-slate-900 dark:hover:text-white rounded-lg focus:outline-none bg-slate-100 dark:bg-slate-800 transition-colors">
                    <i aria-hidden="true" class="bi bi-x-lg text-lg"></i>
                </button>
            </div>

            <!-- Navigation Links -->
            <nav class="space-y-1 text-xs font-bold uppercase tracking-wider">
                <a href="<?php echo $path_depth; ?>index.php" onclick="toggleMobileNav(false)" class="block px-3 py-2.5 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-800 dark:text-slate-200">Home</a>
                <a href="<?php echo $path_depth; ?>about.php" onclick="toggleMobileNav(false)" class="block px-3 py-2.5 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-800 dark:text-slate-200">About Us</a>
                
                <?php if ($navigation_divisions): ?><!-- Mobile Divisions Group -->
                <div class="py-2">
                    <span class="block px-3 text-[10px] font-bold text-amber-500 uppercase tracking-widest mb-1">Corporate Divisions</span>
                    <?php foreach ($navigation_divisions as $nav_division): ?>
<a href="<?php echo htmlspecialchars($path_depth . division_url($nav_division)); ?>" onclick="toggleMobileNav(false)" class="block px-3 py-2 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-400 text-xs normal-case"><?php echo htmlspecialchars($nav_division['name']); ?></a>
<?php endforeach; ?>
                </div><?php endif; ?>

                <a href="<?php echo $path_depth; ?>services.php" onclick="toggleMobileNav(false)" class="block px-3 py-2.5 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-800 dark:text-slate-200">Services</a>
                <a href="<?php echo $path_depth; ?>properties.php" onclick="toggleMobileNav(false)" class="block px-3 py-2.5 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-800 dark:text-slate-200">Properties</a>
                <a href="<?php echo $path_depth; ?>projects.php" onclick="toggleMobileNav(false)" class="block px-3 py-2.5 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-800 dark:text-slate-200">Projects</a>
                <a href="<?php echo $path_depth; ?>news.php" onclick="toggleMobileNav(false)" class="block px-3 py-2.5 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-800 dark:text-slate-200">News</a>
                <a href="<?php echo $path_depth; ?>careers.php" onclick="toggleMobileNav(false)" class="block px-3 py-2.5 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-800 dark:text-slate-200">Careers</a>
                <a href="<?php echo $path_depth; ?>contact.php" onclick="toggleMobileNav(false)" class="block px-3 py-2.5 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-800 dark:text-slate-200">Contact</a>
            </nav>
        </div>

        <!-- Drawer Footer: Auth Actions & Theme -->
        <div class="pt-6 border-t border-slate-100 dark:border-slate-800 space-y-4">
            <!-- Mobile Theme Switcher Buttons -->
            <div>
                <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Theme Mode</span>
                <div class="grid grid-cols-3 gap-2">
                    <button type="button" onclick="setThemeMode('light');" class="theme-switcher-btn py-2 px-2 rounded-xl border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-200 text-xs font-semibold flex items-center justify-center space-x-1" data-theme-mode="light">
                        <i class="bi bi-sun text-amber-500 text-xs"></i>
                        <span>Light</span>
                    </button>
                    <button type="button" onclick="setThemeMode('dark');" class="theme-switcher-btn py-2 px-2 rounded-xl border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-200 text-xs font-semibold flex items-center justify-center space-x-1" data-theme-mode="dark">
                        <i class="bi bi-moon-stars text-indigo-400 text-xs"></i>
                        <span>Dark</span>
                    </button>
                    <button type="button" onclick="setThemeMode('system');" class="theme-switcher-btn py-2 px-2 rounded-xl border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-200 text-xs font-semibold flex items-center justify-center space-x-1" data-theme-mode="system">
                        <i class="bi bi-circle-half text-slate-400 text-xs"></i>
                        <span>Auto</span>
                    </button>
                </div>
            </div>
            <?php if ($user): ?>
                <a href="<?php echo $path_depth; ?>auth/dashboard.php" onclick="toggleMobileNav(false)" class="block w-full text-center py-3 bg-amber-500 text-slate-950 font-bold text-xs uppercase tracking-wider rounded-xl shadow">
                    Portal Dashboard
                </a>
                <a href="<?php echo $path_depth; ?>auth/logout.php" onclick="toggleMobileNav(false)" class="block w-full text-center py-2.5 text-rose-600 dark:text-rose-400 font-semibold text-xs uppercase tracking-wider">
                    Sign Out
                </a>
            <?php else: ?>
                <div class="grid grid-cols-2 gap-2">
                    <a href="<?php echo $path_depth; ?>auth/login.php" onclick="toggleMobileNav(false)" class="text-center py-2.5 bg-slate-900 dark:bg-slate-800 text-white font-bold text-xs uppercase tracking-wider rounded-xl">
                        Sign In
                    </a>
                    <a href="<?php echo $path_depth; ?>auth/register.php" onclick="toggleMobileNav(false)" class="text-center py-2.5 bg-amber-500 text-slate-950 font-bold text-xs uppercase tracking-wider rounded-xl">
                        Register
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</header>
<?php endif; ?>

<script>
function toggleMobileNav(open) {
    const nav = document.getElementById('mobile-nav');
    const backdrop = document.getElementById('mobile-nav-backdrop');
    if (nav && backdrop) {
        if (open) {
            nav.classList.remove('translate-x-full');
            backdrop.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        } else {
            nav.classList.add('translate-x-full');
            backdrop.classList.add('hidden');
            document.body.style.overflow = '';
        }
    }
}

function toggleAccountMenu() {
    const menu = document.getElementById('header-account-menu');
    if (menu) menu.classList.toggle('hidden');
}

// Close account menu when clicking outside
document.addEventListener('click', function(e) {
    const accountBtn = e.target.closest('[onclick="toggleAccountMenu()"]');
    const accountMenu = document.getElementById('header-account-menu');
    if (!accountBtn && accountMenu && !accountMenu.contains(e.target)) {
        accountMenu.classList.add('hidden');
    }
});
</script>

<main id="main-content" class="flex-grow">

