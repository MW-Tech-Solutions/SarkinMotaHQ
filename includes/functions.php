<?php
/**
 * Global Utility and Security Helper Functions
 * Sarkin Mota HQ Enterprise Edition
 */
require_once __DIR__ . '/settings_helper.php';

// Start session securely if not already started
function start_secure_session() {
    if (ob_get_level() == 0) {
        ob_start();
    }
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.use_strict_mode', 1);
        ini_set('session.cookie_samesite', 'Lax');
        
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            ini_set('session.cookie_secure', 1);
        }
        
        session_start();
        
        if (!isset($_SESSION['created_time'])) {
            $_SESSION['created_time'] = time();
        } elseif (time() - $_SESSION['created_time'] > 1800) { // every 30 mins
            session_regenerate_id(true);
            $_SESSION['created_time'] = time();
        }
    }
}

// Clean and sanitize string inputs for HTML context
function sanitize_input($data) {
    if (!is_scalar($data)) return '';
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8', false);
}

// Generate CSRF Token for Forms
function generate_csrf_token() {
    start_secure_session();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verify CSRF Token
function verify_csrf_token($token) {
    start_secure_session();
    if (!isset($_SESSION['csrf_token']) || !is_string($token) || $token === '') {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

// Format Currency to Naira (NGN) or USD
function format_currency($amount, $currency = '₦') {
    return $currency . number_format(floatval($amount), 2);
}

// Highlight navbar links dynamically
function is_active_page($page_name) {
    $current_page = basename($_SERVER['PHP_SELF']);
    return ($current_page === $page_name) ? 'text-amber-500 font-semibold' : 'text-slate-600 dark:text-slate-300 hover:text-amber-500 dark:hover:text-amber-400 transition-colors';
}

// Limit words for summaries
function limit_words($text, $limit = 20) {
    $words = explode(' ', trim($text ?? ''));
    if (count($words) > $limit) {
        return implode(' ', array_slice($words, 0, $limit)) . '...';
    }
    return $text;
}

// Safe Redirect Helper
function redirect($url) {
    if (!headers_sent()) {
        header("Location: " . $url);
        exit;
    } else {
        echo "<script>window.location.href='" . addslashes($url) . "';</script>";
        echo "<noscript><meta http-equiv='refresh' content='0;url=" . htmlspecialchars($url) . "'></noscript>";
        exit;
    }
}

// Set alert messages in session
function set_flash_message($type, $message) {
    start_secure_session();
    $_SESSION['flash_message'] = [
        'type' => $type, // 'success', 'danger', 'info', 'warning'
        'message' => $message
    ];
}

// Display alert messages
function display_flash_message() {
    start_secure_session();
    if (isset($_SESSION['flash_message'])) {
        $flash = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        
        $bg_color = 'bg-blue-100 dark:bg-blue-950/20 border-blue-400 text-blue-700 dark:text-blue-300';
        $icon = 'info-circle';
        if ($flash['type'] === 'success') {
            $icon = 'check-circle';
            $bg_color = 'bg-emerald-50 dark:bg-emerald-950/20 border-emerald-500 text-emerald-800 dark:text-emerald-300';
        } elseif ($flash['type'] === 'danger' || $flash['type'] === 'error') {
            $icon = 'x-circle';
            $bg_color = 'bg-rose-50 dark:bg-rose-950/20 border-rose-500 text-rose-800 dark:text-rose-300';
        } elseif ($flash['type'] === 'warning') {
            $icon = 'exclamation-triangle';
            $bg_color = 'bg-amber-50 dark:bg-amber-950/20 border-amber-500 text-amber-800 dark:text-amber-300';
        }
        
        return '
        <div class="border-l-4 p-4 mb-6 rounded-r-md ' . $bg_color . '" role="alert">
            <p class="text-sm font-medium"><i class="bi bi-' . $icon . ' ui-icon" aria-hidden="true"></i>' . htmlspecialchars($flash['message']) . '</p>
        </div>';
    }
    return '';
}

/**
 * Secure Public Image Upload Processing (F10 Resolution)
 * Validates MIME type, file signatures, disallows SVG, re-encodes raster images, and generates random unguessable filenames.
 */
function handle_image_upload($file_input_name, $fallback_url = '') {
    if (isset($_FILES[$file_input_name]) && $_FILES[$file_input_name]['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES[$file_input_name]['tmp_name'];
        $file_name = $_FILES[$file_input_name]['name'];
        $file_size = $_FILES[$file_input_name]['size'];
        
        // Size check (Max 5MB)
        if ($file_size > 5 * 1024 * 1024) {
            return $fallback_url;
        }

        // Strict extension check (SVG explicitly prohibited for XSS prevention)
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_exts = ['jpg', 'jpeg', 'png', 'webp'];
        if (!in_array($file_ext, $allowed_exts)) {
            return $fallback_url;
        }

        // Validate actual MIME type using finfo
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file_tmp);
        finfo_close($finfo);

        $allowed_mimes = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp'
        ];

        if (!array_key_exists($mime_type, $allowed_mimes)) {
            return $fallback_url;
        }

        $target_dir = __DIR__ . '/../uploads/';
        if (!file_exists($target_dir)) {
            @mkdir($target_dir, 0755, true);
        }

        $new_filename = 'img_' . bin2hex(random_bytes(16)) . '.' . $allowed_mimes[$mime_type];
        $target_filepath = $target_dir . $new_filename;

        $dimensions = @getimagesize($file_tmp);
        if (!$dimensions || $dimensions[0] * $dimensions[1] > 16000000 || !is_uploaded_file($file_tmp)) return $fallback_url;

        // Re-encode image to strip potential embedded malicious payload
        $img = null;
        if ($mime_type === 'image/jpeg') {
            $img = @imagecreatefromjpeg($file_tmp);
        } elseif ($mime_type === 'image/png') {
            $img = @imagecreatefrompng($file_tmp);
        } elseif ($mime_type === 'image/webp') {
            $img = @imagecreatefromwebp($file_tmp);
        }

        if ($img) {
            if ($mime_type === 'image/jpeg') {
                imagejpeg($img, $target_filepath, 85);
            } elseif ($mime_type === 'image/png') {
                imagepng($img, $target_filepath, 8);
            } elseif ($mime_type === 'image/webp') {
                imagewebp($img, $target_filepath, 85);
            }
            imagedestroy($img);
            return 'uploads/' . $new_filename;
        }
    }
    return $fallback_url;
}

/**
 * Secure Private Document Upload Processing (CVs, Resumes, Private Contracts)
 * Stores files outside public web root in storage/uploads/private/
 */
function handle_document_upload($file_input_name) {
    if (isset($_FILES[$file_input_name]) && $_FILES[$file_input_name]['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES[$file_input_name]['tmp_name'];
        $file_name = $_FILES[$file_input_name]['name'];
        $file_size = $_FILES[$file_input_name]['size'];

        if ($file_size > 10 * 1024 * 1024) {
            return null;
        }

        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_exts = ['pdf', 'doc', 'docx'];
        if (!in_array($file_ext, $allowed_exts)) {
            return null;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file_tmp);
        finfo_close($finfo);

        $allowed_mimes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ];

        if (!in_array($mime_type, $allowed_mimes)) {
            return null;
        }

        $private_dir = __DIR__ . '/../storage/uploads/private/';
        if (!file_exists($private_dir)) {
            @mkdir($private_dir, 0750, true);
        }

        $new_filename = 'doc_' . bin2hex(random_bytes(20)) . '.' . $file_ext;
        $target_filepath = $private_dir . $new_filename;

        if (move_uploaded_file($file_tmp, $target_filepath)) {
            return $new_filename;
        }
    }
    return null;
}

/**
 * Universal Image URL Resolver
 * Guarantees correct relative/absolute URL resolution across web root and admin pages.
 */
function resolve_image_url($url, $fallback = 'assets/images/logo.png') {
    $url = trim($url ?? '');
    if (empty($url)) {
        $url = $fallback;
    }

    if (preg_match('~^(?:javascript|data|vbscript):|[<>\"\x00-\x1f]~i', $url) || str_contains($url, '..')) $url = $fallback;

    // Return safe absolute URLs
    if (strpos($url, 'http://') === 0 || strpos($url, 'https://') === 0) {
        return $url;
    }

    // Clean leading slashes
    $clean_path = ltrim($url, '/');

    // Resolve base path (e.g. /Sarkin Mota HQ)
    $app_url = env('APP_URL', 'http://localhost:8080/SarkinMota');
    $parsed_path = parse_url($app_url, PHP_URL_PATH);
    $base_path = rtrim($parsed_path ?: '/Sarkin Mota HQ', '/');

    return $base_path . '/' . $clean_path;
}


require_once __DIR__ . '/http.php';
require_once __DIR__ . '/request_security.php';
if (isset($pdo)) enforce_request_security($pdo);
