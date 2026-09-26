<?php
/**
 * Global Branding & System Settings Helper
 * Enterprise Edition — Sarkin Mota HQ
 */
require_once __DIR__ . '/../config/db.php';

/**
 * Default fallback values for core settings when database key is missing or uninitialized.
 */
function get_default_settings_map() {
    return [
        'company_name' => 'Sarkin Mota HQ',
        'company_short_name' => 'Sarkin Mota HQ',
        'company_tagline' => 'Enterprise Real Estate & Strategic Consulting',
        'company_logo' => 'assets/images/logo.png',
        'company_logo_dark' => 'assets/images/logo.png',
        'favicon' => 'assets/images/favicon.ico',
        'company_email' => 'contact@sarkinmotahq.com',
        'company_phone' => '+234 800 5363 284',
        'company_address' => 'Victoria Island, Lagos, Nigeria',
        'website_url' => 'http://localhost/SarkinMota',
        'google_maps_iframe' => '<iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3940.11559561203!2d7.4753661000000005!3d9.0532195!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x104e0b007c1fa41d%3A0x75c564b5037c4ada!2sSarkinMota%20Autos!5e0!3m2!1sen!2sng!4v1790427341094!5m2!1sen!2sng" width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="strict-origin-when-cross-origin"></iframe>',
        'google_maps_url' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3940.11559561203!2d7.4753661000000005!3d9.0532195!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x104e0b007c1fa41d%3A0x75c564b5037c4ada!2sSarkinMota%20Autos!5e0!3m2!1sen!2sng!4v1790427341094!5m2!1sen!2sng',

        'primary_color' => '#f59e0b',
        'primary_hover_color' => '#d97706',
        'secondary_color' => '#0f172a',
        'accent_color' => '#eab308',
        'header_bg' => '#ffffff',
        'header_text' => '#0f172a',
        'sidebar_bg' => '#0f172a',
        'sidebar_text' => '#f8fafc',
        'button_color' => '#f59e0b',
        'button_text' => '#0f172a',
        'link_color' => '#d97706',
        'footer_bg' => '#020617',
        'footer_text' => '#94a3b8',

        'default_theme' => 'system',
        'allow_theme_switching' => '1',
        'supported_theme_modes' => 'light_dark'
    ];
}

/**
 * Load system settings into static memory (cached per-request).
 */
function load_system_settings($force_reload = false) {
    static $settings_cache = null;

    if ($settings_cache === null || $force_reload) {
        $defaults = get_default_settings_map();
        global $pdo;

        try {
            if ($pdo) {
                $stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings");
                $rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
                $settings_cache = array_merge($defaults, $rows);
            } else {
                $settings_cache = $defaults;
            }
        } catch (Exception $e) {
            error_log("Settings Load Error: " . $e->getMessage());
            $settings_cache = $defaults;
        }
    }

    return $settings_cache;
}

/**
 * Retrieve a global system setting.
 * Example: setting('company_name', 'Sarkin Mota HQ')
 */
function setting($key, $default = null) {
    $all = load_system_settings();
    if (array_key_exists($key, $all) && $all[$key] !== null && $all[$key] !== '') {
        return $all[$key];
    }
    
    if ($default !== null) {
        return $default;
    }

    $defaults = get_default_settings_map();
    return $defaults[$key] ?? '';
}

/**
 * Update a global system setting securely with audit logging.
 */
function update_setting($key, $value, $updated_by = 'System') {
    global $pdo;
    $old_value = setting($key, '');
    
    if ($old_value === $value) {
        return true;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value, updated_by) 
            VALUES (?, ?, ?) 
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_by = VALUES(updated_by)");
        $stmt->execute([$key, $value, $updated_by]);

        // Audit Log
        $log_stmt = $pdo->prepare("INSERT INTO audit_logs (username, action, ip_address) VALUES (?, ?, ?)");
        $log_stmt->execute([
            $updated_by,
            "Updated setting '{$key}': " . substr((string)$old_value, 0, 50) . " → " . substr((string)$value, 0, 50),
            $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
        ]);

        // Invalidate in-memory cache
        load_system_settings(true);
        return true;
    } catch (Exception $e) {
        error_log("Update Setting Error ({$key}): " . $e->getMessage());
        return false;
    }
}

/**
 * Reset all branding & color settings to original system defaults.
 */
function reset_branding_defaults($updated_by = 'System Super Admin') {
    $defaults = get_default_settings_map();
    foreach ($defaults as $key => $val) {
        update_setting($key, $val, $updated_by);
    }
}

/**
 * Secure Asset File Upload Handler for Logo & Favicon.
 * Verifies real MIME type, rejects unsafe SVG, validates dimensions & size, generates hex filename.
 */
function handle_brand_asset_upload($file_input_name, $current_path = '') {
    if (!isset($_FILES[$file_input_name]) || $_FILES[$file_input_name]['error'] !== UPLOAD_ERR_OK) {
        return $current_path;
    }

    $file = $_FILES[$file_input_name];
    $tmp_name = $file['tmp_name'];
    $orig_name = $file['name'];
    $file_size = $file['size'];

    // Size limit: 5MB for assets
    if ($file_size > 5 * 1024 * 1024) {
        throw new Exception("File size exceeds maximum permitted 5MB limit.");
    }

    $ext = strtolower(pathinfo($orig_name, PATHINFO_EXTENSION));

    // Reject SVG files unless strictly sanitized; default policy is reject SVG to prevent XSS
    if ($ext === 'svg') {
        throw new Exception("Raw SVG uploads are prohibited due to security policy. Please upload PNG, JPG/JPEG, or WEBP.");
    }

    $allowed_extensions = ['png', 'jpg', 'jpeg', 'webp', 'ico'];
    if (!in_array($ext, $allowed_extensions)) {
        throw new Exception("Invalid file extension. Permitted formats: PNG, JPG, JPEG, WEBP, ICO.");
    }

    // Verify Real MIME Type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $tmp_name);
    finfo_close($finfo);

    $allowed_mimes = [
        'image/png',
        'image/jpeg',
        'image/jpg',
        'image/webp',
        'image/x-icon',
        'image/vnd.microsoft.icon'
    ];

    if (!in_array($mime_type, $allowed_mimes)) {
        throw new Exception("MIME type validation failed. File signature ('{$mime_type}') is not a valid image format.");
    }

    // Verify Image Dimensions via getimagesize()
    $img_info = @getimagesize($tmp_name);
    if ($img_info === false && !in_array($mime_type, ['image/x-icon', 'image/vnd.microsoft.icon'])) {
        throw new Exception("Uploaded file failed binary image header validation.");
    }

    // Target storage directory inside assets
    $target_dir = __DIR__ . '/../assets/images/branding/';
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0755, true);
    }

    $new_filename = 'brand_' . bin2hex(random_bytes(12)) . '.' . $ext;
    $target_file = $target_dir . $new_filename;

    if (!move_uploaded_file($tmp_name, $target_file)) {
        throw new Exception("Failed to save uploaded brand asset to server storage.");
    }

    return 'assets/images/branding/' . $new_filename;
}

/**
 * Validate HEX Color code string
 */
function validate_hex_color($color, $fallback = '#f59e0b') {
    $color = trim($color);
    if (preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
        return strtolower($color);
    }
    if (preg_match('/^#[0-9A-Fa-f]{3}$/', $color)) {
        return '#' . $color[1].$color[1] . $color[2].$color[2] . $color[3].$color[3];
    }
    return $fallback;
}

/**
 * Dynamic CSS Generator for Root & Dark Theme variables
 */
function generate_brand_css() {
    $primary = validate_hex_color(setting('primary_color'), '#f59e0b');
    $primary_hover = validate_hex_color(setting('primary_hover_color'), '#d97706');
    $secondary = validate_hex_color(setting('secondary_color'), '#0f172a');
    $accent = validate_hex_color(setting('accent_color'), '#eab308');
    $header_bg = validate_hex_color(setting('header_bg'), '#ffffff');
    $header_text = validate_hex_color(setting('header_text'), '#0f172a');
    $sidebar_bg = validate_hex_color(setting('sidebar_bg'), '#0f172a');
    $sidebar_text = validate_hex_color(setting('sidebar_text'), '#f8fafc');
    $button_color = validate_hex_color(setting('button_color'), '#f59e0b');
    $button_text = validate_hex_color(setting('button_text'), '#0f172a');
    $link_color = validate_hex_color(setting('link_color'), '#d97706');
    $footer_bg = validate_hex_color(setting('footer_bg'), '#020617');
    $footer_text = validate_hex_color(setting('footer_text'), '#94a3b8');

    return "
    :root {
        --color-primary: {$primary};
        --color-primary-hover: {$primary_hover};
        --color-secondary: {$secondary};
        --color-accent: {$accent};

        --header-bg: {$header_bg};
        --header-text: {$header_text};

        --sidebar-bg: {$sidebar_bg};
        --sidebar-text: {$sidebar_text};

        --button-bg: {$button_color};
        --button-text: {$button_text};

        --link-color: {$link_color};

        --footer-bg: {$footer_bg};
        --footer-text: {$footer_text};

        --bg-page: #f8fafc;
        --bg-card: #ffffff;
        --bg-muted: #f1f5f9;
        --text-primary: #0f172a;
        --text-secondary: #475569;
        --text-muted: #94a3b8;
        --border-color: #e2e8f0;
    }

    [data-theme=\"dark\"], html.dark {
        --bg-page: #020617;
        --bg-card: #0f172a;
        --bg-muted: #1e293b;
        --text-primary: #f8fafc;
        --text-secondary: #cbd5e1;
        --text-muted: #64748b;
        --border-color: #1e293b;
        
        --header-bg: #0f172a;
        --header-text: #f8fafc;
    }
    ";
}

