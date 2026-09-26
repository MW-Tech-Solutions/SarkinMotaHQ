<?php
/**
 * Global Branding & Theme Management Module
 * Enterprise Edition — Sarkin Mota HQ
 * Restricted strictly to Super Admin users.
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_helper.php';
require_once __DIR__ . '/../includes/rbac_helper.php';

require_super_admin();
$user = get_logged_in_user();

$errors = [];
$success = '';

// Handle POST submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = "CSRF Verification Failed. Please retry.";
    } else {
        // 1. Reset Defaults Action
        if (isset($_POST['reset_branding'])) {
            reset_branding_defaults($user['email']);
            set_flash_message('success', 'Global branding and theme settings restored to system defaults.');
            redirect('manage-branding.php');
        }

        // 2. Save Settings Action
        if (isset($_POST['save_branding'])) {
            // General Company Information
            $company_name = sanitize_input($_POST['company_name'] ?? '');
            $company_short_name = sanitize_input($_POST['company_short_name'] ?? '');
            $company_tagline = sanitize_input($_POST['company_tagline'] ?? '');
            $company_email = filter_var($_POST['company_email'] ?? '', FILTER_VALIDATE_EMAIL) ? sanitize_input($_POST['company_email']) : setting('company_email');
            $company_phone = sanitize_input($_POST['company_phone'] ?? '');
            $company_address = sanitize_input($_POST['company_address'] ?? '');
            $website_url = filter_var($_POST['website_url'] ?? '', FILTER_VALIDATE_URL) ? sanitize_input($_POST['website_url']) : setting('website_url');

            if (empty($company_name)) $errors[] = "Company Name is a mandatory field.";
            if (empty($company_short_name)) $errors[] = "Short Company Name is a mandatory field.";

            // Colors Validation
            $primary_color = validate_hex_color($_POST['primary_color'] ?? '', '#f59e0b');
            $primary_hover_color = validate_hex_color($_POST['primary_hover_color'] ?? '', '#d97706');
            $secondary_color = validate_hex_color($_POST['secondary_color'] ?? '', '#0f172a');
            $accent_color = validate_hex_color($_POST['accent_color'] ?? '', '#eab308');
            $header_bg = validate_hex_color($_POST['header_bg'] ?? '', '#ffffff');
            $header_text = validate_hex_color($_POST['header_text'] ?? '', '#0f172a');
            $sidebar_bg = validate_hex_color($_POST['sidebar_bg'] ?? '', '#0f172a');
            $sidebar_text = validate_hex_color($_POST['sidebar_text'] ?? '', '#f8fafc');
            $button_color = validate_hex_color($_POST['button_color'] ?? '', '#f59e0b');
            $button_text = validate_hex_color($_POST['button_text'] ?? '', '#0f172a');
            $link_color = validate_hex_color($_POST['link_color'] ?? '', '#d97706');
            $footer_bg = validate_hex_color($_POST['footer_bg'] ?? '', '#020617');
            $footer_text = validate_hex_color($_POST['footer_text'] ?? '', '#94a3b8');

            // Theme Settings
            $default_theme = in_array($_POST['default_theme'] ?? '', ['system', 'light', 'dark']) ? $_POST['default_theme'] : 'system';
            $allow_theme_switching = isset($_POST['allow_theme_switching']) ? '1' : '0';

            // Asset File Uploads
            $company_logo = setting('company_logo');
            $company_logo_dark = setting('company_logo_dark');
            $favicon = setting('favicon');

            try {
                if (isset($_FILES['company_logo']) && $_FILES['company_logo']['error'] === UPLOAD_ERR_OK) {
                    $company_logo = handle_brand_asset_upload('company_logo', $company_logo);
                }
                if (isset($_FILES['company_logo_dark']) && $_FILES['company_logo_dark']['error'] === UPLOAD_ERR_OK) {
                    $company_logo_dark = handle_brand_asset_upload('company_logo_dark', $company_logo_dark);
                }
                if (isset($_FILES['favicon']) && $_FILES['favicon']['error'] === UPLOAD_ERR_OK) {
                    $favicon = handle_brand_asset_upload('favicon', $favicon);
                }
            } catch (Exception $e) {
                $errors[] = "Asset Upload Error:  Please try again or contact support.";
            }

            if (empty($errors)) {
                update_setting('company_name', $company_name, $user['email']);
                update_setting('company_short_name', $company_short_name, $user['email']);
                update_setting('company_tagline', $company_tagline, $user['email']);
                update_setting('company_email', $company_email, $user['email']);
                update_setting('company_phone', $company_phone, $user['email']);
                update_setting('company_address', $company_address, $user['email']);
                update_setting('website_url', $website_url, $user['email']);

                update_setting('company_logo', $company_logo, $user['email']);
                update_setting('company_logo_dark', $company_logo_dark, $user['email']);
                update_setting('favicon', $favicon, $user['email']);

                update_setting('primary_color', $primary_color, $user['email']);
                update_setting('primary_hover_color', $primary_hover_color, $user['email']);
                update_setting('secondary_color', $secondary_color, $user['email']);
                update_setting('accent_color', $accent_color, $user['email']);

                update_setting('header_bg', $header_bg, $user['email']);
                update_setting('header_text', $header_text, $user['email']);
                update_setting('sidebar_bg', $sidebar_bg, $user['email']);
                update_setting('sidebar_text', $sidebar_text, $user['email']);

                update_setting('button_color', $button_color, $user['email']);
                update_setting('button_text', $button_text, $user['email']);
                update_setting('link_color', $link_color, $user['email']);
                update_setting('footer_bg', $footer_bg, $user['email']);
                update_setting('footer_text', $footer_text, $user['email']);

                update_setting('default_theme', $default_theme, $user['email']);
                update_setting('allow_theme_switching', $allow_theme_switching, $user['email']);

                set_flash_message('success', 'Global branding and theme settings updated successfully across the platform.');
                redirect('manage-branding.php');
            }
        }
    }
}

$admin_page_title = 'Global Branding & Theme Settings';
require_once __DIR__ . '/../includes/admin_sidebar.php';
?>

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

<!-- Header & Quick Actions -->
<div class="flex flex-wrap items-center justify-between gap-4 mb-8">
    <div>
        <h2 class="text-2xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
            <i aria-hidden="true" class="bi bi-palette-fill text-amber-500"></i> Global Branding & Theme Control
        </h2>
        <p class="text-xs text-slate-500 dark:text-slate-400">Centrally configure identity assets, brand colors, and appearance preferences applied across all public, portal, and administrative components.</p>
    </div>

    <div class="flex items-center gap-3">
        <form action="manage-branding.php" method="POST" onsubmit="return confirm('Restore all company branding and theme settings to original system defaults?');">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            <button type="submit" name="reset_branding" class="px-4 py-2 bg-slate-200 dark:bg-slate-800 hover:bg-slate-300 text-slate-700 dark:text-slate-300 rounded-xl text-xs font-bold transition-colors flex items-center gap-1.5">
                <i aria-hidden="true" class="bi bi-arrow-counterclockwise"></i> Reset to System Defaults
            </button>
        </form>
    </div>
</div>

<!-- Preset Themes Bar -->
<div class="bg-white dark:bg-slate-900 border border-slate-200/50 dark:border-slate-800 rounded-3xl p-6 mb-8 shadow-sm">
    <span class="text-[10px] font-extrabold uppercase tracking-widest text-amber-500 block mb-3">Quick Brand Color Presets</span>
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3 text-xs">
        <button type="button" onclick="applyPreset('#f59e0b', '#d97706', '#0f172a', '#eab308', '#ffffff', '#0f172a', '#0f172a', '#f8fafc', '#f59e0b', '#0f172a', '#d97706', '#020617', '#94a3b8')" class="p-3 rounded-2xl border border-slate-200 dark:border-slate-800 hover:border-amber-500 text-left transition-all group bg-slate-50 dark:bg-slate-950">
            <div class="flex items-center gap-1 mb-2">
                <span class="h-3.5 w-3.5 rounded-full inline-block bg-[#f59e0b]"></span>
                <span class="h-3.5 w-3.5 rounded-full inline-block bg-[#0f172a]"></span>
            </div>
            <span class="font-bold text-slate-900 dark:text-white block group-hover:text-amber-500">Sarkin Mota HQ Amber</span>
            <span class="text-[9px] text-slate-400">Default Corporate</span>
        </button>

        <button type="button" onclick="applyPreset('#2563eb', '#1d4ed8', '#0f172a', '#3b82f6', '#ffffff', '#0f172a', '#0f172a', '#f8fafc', '#2563eb', '#ffffff', '#2563eb', '#0f172a', '#94a3b8')" class="p-3 rounded-2xl border border-slate-200 dark:border-slate-800 hover:border-blue-500 text-left transition-all group bg-slate-50 dark:bg-slate-950">
            <div class="flex items-center gap-1 mb-2">
                <span class="h-3.5 w-3.5 rounded-full inline-block bg-[#2563eb]"></span>
                <span class="h-3.5 w-3.5 rounded-full inline-block bg-[#0f172a]"></span>
            </div>
            <span class="font-bold text-slate-900 dark:text-white block group-hover:text-blue-500">Corporate Blue</span>
            <span class="text-[9px] text-slate-400">Deloitte Executive</span>
        </button>

        <button type="button" onclick="applyPreset('#059669', '#047857', '#064e3b', '#10b981', '#ffffff', '#064e3b', '#064e3b', '#ecfdf5', '#059669', '#ffffff', '#059669', '#022c22', '#a7f3d0')" class="p-3 rounded-2xl border border-slate-200 dark:border-slate-800 hover:border-emerald-500 text-left transition-all group bg-slate-50 dark:bg-slate-950">
            <div class="flex items-center gap-1 mb-2">
                <span class="h-3.5 w-3.5 rounded-full inline-block bg-[#059669]"></span>
                <span class="h-3.5 w-3.5 rounded-full inline-block bg-[#064e3b]"></span>
            </div>
            <span class="font-bold text-slate-900 dark:text-white block group-hover:text-emerald-500">Emerald Estate</span>
            <span class="text-[9px] text-slate-400">Agribusiness & Eco</span>
        </button>

        <button type="button" onclick="applyPreset('#7c3aed', '#6d28d9', '#2e1065', '#8b5cf6', '#ffffff', '#2e1065', '#2e1065', '#f5f3ff', '#7c3aed', '#ffffff', '#7c3aed', '#1e1b4b', '#c4b5fd')" class="p-3 rounded-2xl border border-slate-200 dark:border-slate-800 hover:border-purple-500 text-left transition-all group bg-slate-50 dark:bg-slate-950">
            <div class="flex items-center gap-1 mb-2">
                <span class="h-3.5 w-3.5 rounded-full inline-block bg-[#7c3aed]"></span>
                <span class="h-3.5 w-3.5 rounded-full inline-block bg-[#2e1065]"></span>
            </div>
            <span class="font-bold text-slate-900 dark:text-white block group-hover:text-purple-500">Royal Purple</span>
            <span class="text-[9px] text-slate-400">Luxury High-End</span>
        </button>

        <button type="button" onclick="applyPreset('#38bdf8', '#0284c7', '#0f172a', '#7dd3fc', '#0f172a', '#f8fafc', '#0f172a', '#f8fafc', '#38bdf8', '#0f172a', '#38bdf8', '#020617', '#94a3b8')" class="p-3 rounded-2xl border border-slate-200 dark:border-slate-800 hover:border-sky-500 text-left transition-all group bg-slate-50 dark:bg-slate-950">
            <div class="flex items-center gap-1 mb-2">
                <span class="h-3.5 w-3.5 rounded-full inline-block bg-[#38bdf8]"></span>
                <span class="h-3.5 w-3.5 rounded-full inline-block bg-[#0f172a]"></span>
            </div>
            <span class="font-bold text-slate-900 dark:text-white block group-hover:text-sky-500">Modern Slate</span>
            <span class="text-[9px] text-slate-400">Minimal Tech</span>
        </button>
    </div>
</div>

<form action="manage-branding.php" method="POST" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Left 2 Columns: Settings Controls -->
        <div class="lg:col-span-2 space-y-8">
            
            <!-- Section 1: Company Identity -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200/50 dark:border-slate-800 rounded-3xl p-8 shadow-sm">
                <h3 class="text-base font-bold text-slate-900 dark:text-white mb-6 flex items-center gap-2 border-b border-slate-100 dark:border-slate-800 pb-4">
                    <i aria-hidden="true" class="bi bi-building text-amber-500"></i> Corporate Identity & Contact Details
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1.5">Company Name (Mandatory)</label>
                        <input type="text" id="input_company_name" name="company_name" required value="<?php echo htmlspecialchars(setting('company_name')); ?>" oninput="updateLivePreview()" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500">
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1.5">Short Company Name (Mandatory)</label>
                        <input type="text" id="input_company_short_name" name="company_short_name" required value="<?php echo htmlspecialchars(setting('company_short_name')); ?>" oninput="updateLivePreview()" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500">
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1.5">Company Tagline / Slogan</label>
                        <input type="text" id="input_company_tagline" name="company_tagline" value="<?php echo htmlspecialchars(setting('company_tagline')); ?>" oninput="updateLivePreview()" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500">
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1.5">Support Email Address</label>
                        <input type="email" name="company_email" value="<?php echo htmlspecialchars(setting('company_email')); ?>" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500">
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1.5">Official Phone Number</label>
                        <input type="text" name="company_phone" value="<?php echo htmlspecialchars(setting('company_phone')); ?>" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500">
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1.5">Headquarters Address</label>
                        <input type="text" name="company_address" value="<?php echo htmlspecialchars(setting('company_address')); ?>" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500">
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1.5">Canonical Website URL</label>
                        <input type="url" name="website_url" value="<?php echo htmlspecialchars(setting('website_url')); ?>" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500">
                    </div>
                </div>
            </div>

            <!-- Section 2: Logo & Visual Assets -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200/50 dark:border-slate-800 rounded-3xl p-8 shadow-sm">
                <h3 class="text-base font-bold text-slate-900 dark:text-white mb-6 flex items-center gap-2 border-b border-slate-100 dark:border-slate-800 pb-4">
                    <i aria-hidden="true" class="bi bi-image text-amber-500"></i> Brand Logos & Favicon Uploads
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Light Mode Logo -->
                    <div class="p-4 border border-slate-200/80 dark:border-slate-800 rounded-2xl space-y-3 bg-slate-50 dark:bg-slate-950">
                        <span class="block text-xs font-bold text-slate-900 dark:text-white">Light Mode Logo</span>
                        <div class="h-20 bg-white border border-slate-200 rounded-xl flex items-center justify-center p-3">
                            <img id="prev_logo_light" src="../<?php echo htmlspecialchars(setting('company_logo')); ?>" alt="Light Logo" class="max-h-14 w-auto object-contain">
                        </div>
                        <input type="file" name="company_logo" accept="image/png,image/jpeg,image/webp" onchange="previewImage(this, 'prev_logo_light')" class="block w-full text-[10px] text-slate-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-[10px] file:font-semibold file:bg-amber-500 file:text-slate-950 hover:file:bg-amber-600">
                        <span class="text-[9px] text-slate-400 block">PNG, JPG, WEBP (Max 5MB)</span>
                    </div>

                    <!-- Dark Mode Logo -->
                    <div class="p-4 border border-slate-200/80 dark:border-slate-800 rounded-2xl space-y-3 bg-slate-50 dark:bg-slate-950">
                        <span class="block text-xs font-bold text-slate-900 dark:text-white">Dark Mode Logo</span>
                        <div class="h-20 bg-slate-950 border border-slate-800 rounded-xl flex items-center justify-center p-3">
                            <img id="prev_logo_dark" src="../<?php echo htmlspecialchars(setting('company_logo_dark')); ?>" alt="Dark Logo" class="max-h-14 w-auto object-contain">
                        </div>
                        <input type="file" name="company_logo_dark" accept="image/png,image/jpeg,image/webp" onchange="previewImage(this, 'prev_logo_dark')" class="block w-full text-[10px] text-slate-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-[10px] file:font-semibold file:bg-amber-500 file:text-slate-950 hover:file:bg-amber-600">
                        <span class="text-[9px] text-slate-400 block">For dark headers & backgrounds</span>
                    </div>

                    <!-- Favicon -->
                    <div class="p-4 border border-slate-200/80 dark:border-slate-800 rounded-2xl space-y-3 bg-slate-50 dark:bg-slate-950">
                        <span class="block text-xs font-bold text-slate-900 dark:text-white">Favicon Icon</span>
                        <div class="h-20 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl flex items-center justify-center p-3">
                            <img id="prev_favicon" src="../<?php echo htmlspecialchars(setting('favicon')); ?>" alt="Favicon" class="h-8 w-8 object-contain">
                        </div>
                        <input type="file" name="favicon" accept="image/png,image/x-icon,image/vnd.microsoft.icon,image/webp" onchange="previewImage(this, 'prev_favicon')" class="block w-full text-[10px] text-slate-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-[10px] file:font-semibold file:bg-amber-500 file:text-slate-950 hover:file:bg-amber-600">
                        <span class="text-[9px] text-slate-400 block">ICO or PNG icon</span>
                    </div>
                </div>
            </div>

            <!-- Section 3: Quick Brand Color Presets & Centralized Tokens -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200/50 dark:border-slate-800 rounded-3xl p-8 shadow-sm">
                <h3 class="text-base font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2 border-b border-slate-100 dark:border-slate-800 pb-4">
                    <i aria-hidden="true" class="bi bi-palette-fill text-amber-500"></i> Quick Corporate Color Presets
                </h3>
                <p class="text-xs text-slate-400 mb-6">Select a pre-designed corporate palette to instantly apply luxury gold, slate, white, obsidian, emerald, or sapphire theme tokens across the platform.</p>

                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mb-8">
                    <!-- Preset 1: Sarkin Mota HQ Gold & Dark Slate -->
                    <button type="button" onclick="applyPreset('#f59e0b', '#d97706', '#0f172a', '#eab308', '#ffffff', '#0f172a', '#0f172a', '#f8fafc', '#f59e0b', '#0f172a', '#d97706', '#020617', '#94a3b8')" class="p-3 border border-slate-200 dark:border-slate-800 rounded-2xl text-left hover:border-amber-500 transition-all bg-slate-50 dark:bg-slate-950 group">
                        <div class="flex gap-1.5 mb-2">
                            <span class="h-4 w-4 rounded-full bg-[#f59e0b] shadow-sm"></span>
                            <span class="h-4 w-4 rounded-full bg-[#0f172a] shadow-sm"></span>
                            <span class="h-4 w-4 rounded-full bg-[#ffffff] border border-slate-200"></span>
                        </div>
                        <span class="block text-[10px] font-extrabold text-slate-900 dark:text-white group-hover:text-amber-500 transition-colors leading-tight">Gold & Slate (Default)</span>
                    </button>

                    <!-- Preset 2: Royal Gold & Pure White -->
                    <button type="button" onclick="applyPreset('#d4af37', '#aa7c11', '#ffffff', '#f59e0b', '#ffffff', '#111827', '#111827', '#ffffff', '#d4af37', '#111827', '#d4af37', '#111827', '#6b7280')" class="p-3 border border-slate-200 dark:border-slate-800 rounded-2xl text-left hover:border-amber-500 transition-all bg-slate-50 dark:bg-slate-950 group">
                        <div class="flex gap-1.5 mb-2">
                            <span class="h-4 w-4 rounded-full bg-[#d4af37] shadow-sm"></span>
                            <span class="h-4 w-4 rounded-full bg-[#ffffff] border border-slate-200"></span>
                            <span class="h-4 w-4 rounded-full bg-[#111827] shadow-sm"></span>
                        </div>
                        <span class="block text-[10px] font-extrabold text-slate-900 dark:text-white group-hover:text-amber-500 transition-colors leading-tight">Royal Gold & White</span>
                    </button>

                    <!-- Preset 3: Midnight Obsidian & Gold -->
                    <button type="button" onclick="applyPreset('#eab308', '#ca8a04', '#18181b', '#f59e0b', '#18181b', '#ffffff', '#18181b', '#f4f4f5', '#eab308', '#18181b', '#eab308', '#09090b', '#a1a1aa')" class="p-3 border border-slate-200 dark:border-slate-800 rounded-2xl text-left hover:border-amber-500 transition-all bg-slate-50 dark:bg-slate-950 group">
                        <div class="flex gap-1.5 mb-2">
                            <span class="h-4 w-4 rounded-full bg-[#18181b] shadow-sm"></span>
                            <span class="h-4 w-4 rounded-full bg-[#eab308] shadow-sm"></span>
                            <span class="h-4 w-4 rounded-full bg-[#09090b] shadow-sm"></span>
                        </div>
                        <span class="block text-[10px] font-extrabold text-slate-900 dark:text-white group-hover:text-amber-500 transition-colors leading-tight">Midnight Obsidian</span>
                    </button>

                    <!-- Preset 4: Regal Emerald & Gold -->
                    <button type="button" onclick="applyPreset('#059669', '#047857', '#064e3b', '#f59e0b', '#ffffff', '#064e3b', '#064e3b', '#ecfdf5', '#059669', '#ffffff', '#047857', '#022c22', '#6ee7b7')" class="p-3 border border-slate-200 dark:border-slate-800 rounded-2xl text-left hover:border-amber-500 transition-all bg-slate-50 dark:bg-slate-950 group">
                        <div class="flex gap-1.5 mb-2">
                            <span class="h-4 w-4 rounded-full bg-[#059669] shadow-sm"></span>
                            <span class="h-4 w-4 rounded-full bg-[#064e3b] shadow-sm"></span>
                            <span class="h-4 w-4 rounded-full bg-[#f59e0b] shadow-sm"></span>
                        </div>
                        <span class="block text-[10px] font-extrabold text-slate-900 dark:text-white group-hover:text-amber-500 transition-colors leading-tight">Regal Emerald</span>
                    </button>

                    <!-- Preset 5: Royal Sapphire & Amber -->
                    <button type="button" onclick="applyPreset('#1d4ed8', '#1e40af', '#1e1b4b', '#f59e0b', '#ffffff', '#1e1b4b', '#1e1b4b', '#eff6ff', '#1d4ed8', '#ffffff', '#1d4ed8', '#0f172a', '#93c5fd')" class="p-3 border border-slate-200 dark:border-slate-800 rounded-2xl text-left hover:border-amber-500 transition-all bg-slate-50 dark:bg-slate-950 group">
                        <div class="flex gap-1.5 mb-2">
                            <span class="h-4 w-4 rounded-full bg-[#1d4ed8] shadow-sm"></span>
                            <span class="h-4 w-4 rounded-full bg-[#1e1b4b] shadow-sm"></span>
                            <span class="h-4 w-4 rounded-full bg-[#f59e0b] shadow-sm"></span>
                        </div>
                        <span class="block text-[10px] font-extrabold text-slate-900 dark:text-white group-hover:text-amber-500 transition-colors leading-tight">Royal Sapphire</span>
                    </button>

                    <!-- Preset 6: Champagne Bronze -->
                    <button type="button" onclick="applyPreset('#c5a059', '#9a7b38', '#1c1917', '#d4af37', '#ffffff', '#1c1917', '#1c1917', '#fafaf9', '#c5a059', '#1c1917', '#9a7b38', '#0c0a09', '#a8a29e')" class="p-3 border border-slate-200 dark:border-slate-800 rounded-2xl text-left hover:border-amber-500 transition-all bg-slate-50 dark:bg-slate-950 group">
                        <div class="flex gap-1.5 mb-2">
                            <span class="h-4 w-4 rounded-full bg-[#c5a059] shadow-sm"></span>
                            <span class="h-4 w-4 rounded-full bg-[#1c1917] shadow-sm"></span>
                            <span class="h-4 w-4 rounded-full bg-[#d4af37] shadow-sm"></span>
                        </div>
                        <span class="block text-[10px] font-extrabold text-slate-900 dark:text-white group-hover:text-amber-500 transition-colors leading-tight">Champagne Bronze</span>
                    </button>
                </div>

                <h4 class="text-xs font-bold text-slate-900 dark:text-white mb-4 uppercase tracking-wider">Custom Color Tokens</h4>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
                    <!-- Primary Color -->
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1.5">Primary Brand Color</label>
                        <div class="flex items-center gap-2">
                            <input type="color" id="picker_primary_color" value="<?php echo htmlspecialchars(setting('primary_color')); ?>" oninput="syncColorInput('picker_primary_color', 'primary_color')" class="h-10 w-12 rounded-lg border border-slate-200 dark:border-slate-800 cursor-pointer">
                            <input type="text" id="primary_color" name="primary_color" value="<?php echo htmlspecialchars(setting('primary_color')); ?>" oninput="syncColorPicker('primary_color', 'picker_primary_color')" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3 py-2 text-xs font-mono text-slate-900 dark:text-white">
                        </div>
                    </div>

                    <!-- Primary Hover Color -->
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1.5">Primary Hover State</label>
                        <div class="flex items-center gap-2">
                            <input type="color" id="picker_primary_hover_color" value="<?php echo htmlspecialchars(setting('primary_hover_color')); ?>" oninput="syncColorInput('picker_primary_hover_color', 'primary_hover_color')" class="h-10 w-12 rounded-lg border border-slate-200 dark:border-slate-800 cursor-pointer">
                            <input type="text" id="primary_hover_color" name="primary_hover_color" value="<?php echo htmlspecialchars(setting('primary_hover_color')); ?>" oninput="syncColorPicker('primary_hover_color', 'picker_primary_hover_color')" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3 py-2 text-xs font-mono text-slate-900 dark:text-white">
                        </div>
                    </div>

                    <!-- Secondary Color -->
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1.5">Secondary Background</label>
                        <div class="flex items-center gap-2">
                            <input type="color" id="picker_secondary_color" value="<?php echo htmlspecialchars(setting('secondary_color')); ?>" oninput="syncColorInput('picker_secondary_color', 'secondary_color')" class="h-10 w-12 rounded-lg border border-slate-200 dark:border-slate-800 cursor-pointer">
                            <input type="text" id="secondary_color" name="secondary_color" value="<?php echo htmlspecialchars(setting('secondary_color')); ?>" oninput="syncColorPicker('secondary_color', 'picker_secondary_color')" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3 py-2 text-xs font-mono text-slate-900 dark:text-white">
                        </div>
                    </div>

                    <!-- Accent Color -->
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1.5">Accent Color</label>
                        <div class="flex items-center gap-2">
                            <input type="color" id="picker_accent_color" value="<?php echo htmlspecialchars(setting('accent_color')); ?>" oninput="syncColorInput('picker_accent_color', 'accent_color')" class="h-10 w-12 rounded-lg border border-slate-200 dark:border-slate-800 cursor-pointer">
                            <input type="text" id="accent_color" name="accent_color" value="<?php echo htmlspecialchars(setting('accent_color')); ?>" oninput="syncColorPicker('accent_color', 'picker_accent_color')" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3 py-2 text-xs font-mono text-slate-900 dark:text-white">
                        </div>
                    </div>

                    <!-- Header Background -->
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1.5">Header Background</label>
                        <div class="flex items-center gap-2">
                            <input type="color" id="picker_header_bg" value="<?php echo htmlspecialchars(setting('header_bg')); ?>" oninput="syncColorInput('picker_header_bg', 'header_bg')" class="h-10 w-12 rounded-lg border border-slate-200 dark:border-slate-800 cursor-pointer">
                            <input type="text" id="header_bg" name="header_bg" value="<?php echo htmlspecialchars(setting('header_bg')); ?>" oninput="syncColorPicker('header_bg', 'picker_header_bg')" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3 py-2 text-xs font-mono text-slate-900 dark:text-white">
                        </div>
                    </div>

                    <!-- Header Text -->
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1.5">Header Text Color</label>
                        <div class="flex items-center gap-2">
                            <input type="color" id="picker_header_text" value="<?php echo htmlspecialchars(setting('header_text')); ?>" oninput="syncColorInput('picker_header_text', 'header_text')" class="h-10 w-12 rounded-lg border border-slate-200 dark:border-slate-800 cursor-pointer">
                            <input type="text" id="header_text" name="header_text" value="<?php echo htmlspecialchars(setting('header_text')); ?>" oninput="syncColorPicker('header_text', 'picker_header_text')" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3 py-2 text-xs font-mono text-slate-900 dark:text-white">
                        </div>
                    </div>

                    <!-- Sidebar Background -->
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1.5">Sidebar Background</label>
                        <div class="flex items-center gap-2">
                            <input type="color" id="picker_sidebar_bg" value="<?php echo htmlspecialchars(setting('sidebar_bg')); ?>" oninput="syncColorInput('picker_sidebar_bg', 'sidebar_bg')" class="h-10 w-12 rounded-lg border border-slate-200 dark:border-slate-800 cursor-pointer">
                            <input type="text" id="sidebar_bg" name="sidebar_bg" value="<?php echo htmlspecialchars(setting('sidebar_bg')); ?>" oninput="syncColorPicker('sidebar_bg', 'picker_sidebar_bg')" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3 py-2 text-xs font-mono text-slate-900 dark:text-white">
                        </div>
                    </div>

                    <!-- Sidebar Text -->
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1.5">Sidebar Text Color</label>
                        <div class="flex items-center gap-2">
                            <input type="color" id="picker_sidebar_text" value="<?php echo htmlspecialchars(setting('sidebar_text')); ?>" oninput="syncColorInput('picker_sidebar_text', 'sidebar_text')" class="h-10 w-12 rounded-lg border border-slate-200 dark:border-slate-800 cursor-pointer">
                            <input type="text" id="sidebar_text" name="sidebar_text" value="<?php echo htmlspecialchars(setting('sidebar_text')); ?>" oninput="syncColorPicker('sidebar_text', 'picker_sidebar_text')" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3 py-2 text-xs font-mono text-slate-900 dark:text-white">
                        </div>
                    </div>

                    <!-- Button Color -->
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1.5">Button Color</label>
                        <div class="flex items-center gap-2">
                            <input type="color" id="picker_button_color" value="<?php echo htmlspecialchars(setting('button_color')); ?>" oninput="syncColorInput('picker_button_color', 'button_color')" class="h-10 w-12 rounded-lg border border-slate-200 dark:border-slate-800 cursor-pointer">
                            <input type="text" id="button_color" name="button_color" value="<?php echo htmlspecialchars(setting('button_color')); ?>" oninput="syncColorPicker('button_color', 'picker_button_color')" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3 py-2 text-xs font-mono text-slate-900 dark:text-white">
                        </div>
                    </div>

                    <!-- Button Text -->
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1.5">Button Text Color</label>
                        <div class="flex items-center gap-2">
                            <input type="color" id="picker_button_text" value="<?php echo htmlspecialchars(setting('button_text')); ?>" oninput="syncColorInput('picker_button_text', 'button_text')" class="h-10 w-12 rounded-lg border border-slate-200 dark:border-slate-800 cursor-pointer">
                            <input type="text" id="button_text" name="button_text" value="<?php echo htmlspecialchars(setting('button_text')); ?>" oninput="syncColorPicker('button_text', 'picker_button_text')" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3 py-2 text-xs font-mono text-slate-900 dark:text-white">
                        </div>
                    </div>

                    <!-- Link Color -->
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1.5">Link Text Color</label>
                        <div class="flex items-center gap-2">
                            <input type="color" id="picker_link_color" value="<?php echo htmlspecialchars(setting('link_color')); ?>" oninput="syncColorInput('picker_link_color', 'link_color')" class="h-10 w-12 rounded-lg border border-slate-200 dark:border-slate-800 cursor-pointer">
                            <input type="text" id="link_color" name="link_color" value="<?php echo htmlspecialchars(setting('link_color')); ?>" oninput="syncColorPicker('link_color', 'picker_link_color')" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3 py-2 text-xs font-mono text-slate-900 dark:text-white">
                        </div>
                    </div>

                    <!-- Footer Background -->
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1.5">Footer Background</label>
                        <div class="flex items-center gap-2">
                            <input type="color" id="picker_footer_bg" value="<?php echo htmlspecialchars(setting('footer_bg')); ?>" oninput="syncColorInput('picker_footer_bg', 'footer_bg')" class="h-10 w-12 rounded-lg border border-slate-200 dark:border-slate-800 cursor-pointer">
                            <input type="text" id="footer_bg" name="footer_bg" value="<?php echo htmlspecialchars(setting('footer_bg')); ?>" oninput="syncColorPicker('footer_bg', 'picker_footer_bg')" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3 py-2 text-xs font-mono text-slate-900 dark:text-white">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 4: Theme Preferences -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200/50 dark:border-slate-800 rounded-3xl p-8 shadow-sm">
                <h3 class="text-base font-bold text-slate-900 dark:text-white mb-6 flex items-center gap-2 border-b border-slate-100 dark:border-slate-800 pb-4">
                    <i aria-hidden="true" class="bi bi-moon-stars text-amber-500"></i> Theme & Mode Configuration
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-xs">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1.5">Default Application Theme Mode</label>
                        <select name="default_theme" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500">
                            <option value="system" <?php echo setting('default_theme') === 'system' ? 'selected' : ''; ?>>System Preference (Recommended)</option>
                            <option value="light" <?php echo setting('default_theme') === 'light' ? 'selected' : ''; ?>>Light Mode Only</option>
                            <option value="dark" <?php echo setting('default_theme') === 'dark' ? 'selected' : ''; ?>>Dark Mode Only</option>
                        </select>
                    </div>

                    <div class="flex items-center pt-4">
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" name="allow_theme_switching" value="1" <?php echo setting('allow_theme_switching') === '1' ? 'checked' : ''; ?> class="h-4 w-4 rounded border-slate-300 text-amber-500 focus:ring-amber-500">
                            <span class="text-xs font-semibold text-slate-800 dark:text-slate-200">Allow users to toggle between Light, Dark, and System modes</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="pt-4">
                <button type="submit" name="save_branding" class="w-full bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold py-4 rounded-2xl text-xs uppercase tracking-wider transition-colors shadow-lg flex items-center justify-center gap-2">
                    <i aria-hidden="true" class="bi bi-check-circle-fill"></i> Save & Apply Global Branding System
                </button>
            </div>
        </div>

        <!-- Right Column: Interactive Live Preview Container -->
        <div>
            <div class="bg-white dark:bg-slate-900 border border-slate-200/50 dark:border-slate-800 rounded-3xl p-6 shadow-md sticky top-8 space-y-6">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                    <h4 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">Live System Preview</h4>
                    <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-amber-500/10 text-amber-500">Real-Time Canvas</span>
                </div>

                <!-- Preview Mode Controls -->
                <div class="flex items-center justify-between text-[10px] font-bold text-slate-400">
                    <span>Target Interface:</span>
                    <div class="flex gap-1.5">
                        <button type="button" onclick="setPreviewDevice('desktop')" id="prev_btn_desktop" class="px-2.5 py-1 rounded-lg bg-amber-500 text-slate-950"><i class="bi bi-display ui-icon" aria-hidden="true"></i>Desktop</button>
                        <button type="button" onclick="setPreviewDevice('mobile')" id="prev_btn_mobile" class="px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800"><i class="bi bi-phone ui-icon" aria-hidden="true"></i>Mobile</button>
                    </div>
                </div>

                <!-- Live Component Card -->
                <div id="preview_wrapper" class="border border-slate-200/80 dark:border-slate-800 rounded-2xl overflow-hidden transition-all shadow-inner">
                    <!-- Dynamic Header -->
                    <div id="pv_header" class="p-3 border-b flex items-center justify-between" style="background: <?php echo setting('header_bg'); ?>; color: <?php echo setting('header_text'); ?>;">
                        <div class="flex items-center gap-2">
                            <span id="pv_company_short" class="font-extrabold text-xs"><?php echo htmlspecialchars(setting('company_short_name')); ?></span>
                        </div>
                        <div class="text-[9px] font-semibold flex items-center gap-2">
                            <span>Properties</span>
                            <span>News</span>
                            <span class="px-2 py-0.5 rounded text-[8px]" style="background: <?php echo setting('primary_color'); ?>; color: <?php echo setting('button_text'); ?>;">Portal</span>
                        </div>
                    </div>

                    <!-- Main Canvas Body -->
                    <div class="p-4 space-y-4 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white">
                        <div class="p-4 rounded-xl border bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 shadow-sm space-y-2">
                            <span class="text-[9px] font-bold uppercase tracking-widest text-amber-500">Corporate Listing</span>
                            <h5 id="pv_company_name" class="text-sm font-bold leading-tight"><?php echo htmlspecialchars(setting('company_name')); ?></h5>
                            <p id="pv_company_tagline" class="text-[10px] text-slate-500 dark:text-slate-400"><?php echo htmlspecialchars(setting('company_tagline')); ?></p>
                            
                            <div class="pt-2 flex items-center gap-2">
                                <button type="button" id="pv_button" class="px-3 py-1.5 rounded-lg text-[10px] font-bold uppercase tracking-wider" style="background: <?php echo setting('button_color'); ?>; color: <?php echo setting('button_text'); ?>;">
                                    Action Button
                                </button>
                                <a href="#" id="pv_link" class="text-[10px] font-semibold underline" style="color: <?php echo setting('link_color'); ?>;">Brand Link</a>
                            </div>
                        </div>
                    </div>

                    <!-- Dynamic Footer -->
                    <div id="pv_footer" class="p-3 text-[9px] text-center" style="background: <?php echo setting('footer_bg'); ?>; color: <?php echo setting('footer_text'); ?>;">
                        © <?php echo date('Y'); ?> <span id="pv_footer_name"><?php echo htmlspecialchars(setting('company_name')); ?></span>. All Rights Reserved.
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
function syncColorInput(pickerId, inputId) {
    const val = document.getElementById(pickerId).value;
    document.getElementById(inputId).value = val;
    updateLivePreview();
}

function syncColorPicker(inputId, pickerId) {
    const val = document.getElementById(inputId).value;
    if (/^#[0-9A-Fa-f]{6}$/.test(val)) {
        document.getElementById(pickerId).value = val;
    }
    updateLivePreview();
}

function applyPreset(p, ph, s, a, hb, ht, sb, st, bc, bt, lc, fb, ft) {
    const setVal = (id, pId, val) => {
        document.getElementById(id).value = val;
        document.getElementById(pId).value = val;
    };

    setVal('primary_color', 'picker_primary_color', p);
    setVal('primary_hover_color', 'picker_primary_hover_color', ph);
    setVal('secondary_color', 'picker_secondary_color', s);
    setVal('accent_color', 'picker_accent_color', a);
    setVal('header_bg', 'picker_header_bg', hb);
    setVal('header_text', 'picker_header_text', ht);
    setVal('sidebar_bg', 'picker_sidebar_bg', sb);
    setVal('sidebar_text', 'picker_sidebar_text', st);
    setVal('button_color', 'picker_button_color', bc);
    setVal('button_text', 'picker_button_text', bt);
    setVal('link_color', 'picker_link_color', lc);
    setVal('footer_bg', 'picker_footer_bg', fb);

    updateLivePreview();
}

function previewImage(input, targetId) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById(targetId).src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function updateLivePreview() {
    const cName = document.getElementById('input_company_name').value || 'Company Name';
    const cShort = document.getElementById('input_company_short_name').value || 'Short Name';
    const cTag = document.getElementById('input_company_tagline').value || 'Corporate Tagline';

    document.getElementById('pv_company_name').innerText = cName;
    document.getElementById('pv_company_short').innerText = cShort;
    document.getElementById('pv_company_tagline').innerText = cTag;
    document.getElementById('pv_footer_name').innerText = cName;

    const hb = document.getElementById('header_bg').value;
    const ht = document.getElementById('header_text').value;
    const bc = document.getElementById('button_color').value;
    const bt = document.getElementById('button_text').value;
    const lc = document.getElementById('link_color').value;
    const fb = document.getElementById('footer_bg').value;

    const pvH = document.getElementById('pv_header');
    if (pvH) { pvH.style.background = hb; pvH.style.color = ht; }

    const pvB = document.getElementById('pv_button');
    if (pvB) { pvB.style.background = bc; pvB.style.color = bt; }

    const pvL = document.getElementById('pv_link');
    if (pvL) { pvL.style.color = lc; }

    const pvF = document.getElementById('pv_footer');
    if (pvF) { pvF.style.background = fb; }
}

function setPreviewDevice(mode) {
    const wrapper = document.getElementById('preview_wrapper');
    const btnD = document.getElementById('prev_btn_desktop');
    const btnM = document.getElementById('prev_btn_mobile');

    if (mode === 'mobile') {
        wrapper.style.maxWidth = '280px';
        wrapper.style.margin = '0 auto';
        btnM.className = 'px-2.5 py-1 rounded-lg bg-amber-500 text-slate-950';
        btnD.className = 'px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800';
    } else {
        wrapper.style.maxWidth = '100%';
        btnD.className = 'px-2.5 py-1 rounded-lg bg-amber-500 text-slate-950';
        btnM.className = 'px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800';
    }
}
</script>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>

