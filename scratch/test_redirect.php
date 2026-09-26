<?php
function get_app_base_url() {
    $root_dir = realpath(__DIR__ . '/..');
    $doc_root = isset($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : false;

    if ($root_dir && $doc_root && strpos($root_dir, $doc_root) === 0) {
        $rel = str_replace('\\', '/', substr($root_dir, strlen($doc_root)));
        $base = '/' . ltrim(rtrim($rel, '/'), '/') . '/';
        return preg_replace('#/+#', '/', $base);
    }
    return '/';
}

function app_url($path = '') {
    $base = get_app_base_url();
    $clean_path = preg_replace('#^/+(SarkinMota/)?#i', '', $path);
    return $base . $clean_path;
}

function resolve_redirect_url($url) {
    if (strpos($url, 'http://') === 0 || strpos($url, 'https://') === 0) {
        return $url;
    }
    if (strpos($url, '/') !== 0) {
        $root_dir = realpath(__DIR__ . '/..');
        $script_file = $_SERVER['SCRIPT_FILENAME'] ?? '';
        if (!empty($script_file)) {
            $script_dir = realpath(dirname($script_file));
            if ($root_dir && $script_dir && strpos($script_dir, $root_dir) === 0) {
                $r = trim(substr($script_dir, strlen($root_dir)), '/\\');
                if ($r !== '') {
                    $rel_dir = str_replace('\\', '/', $r) . '/';
                    if (strpos($url, $rel_dir) !== 0) {
                        $url = $rel_dir . $url;
                    }
                }
            }
        }
    }
    return app_url($url);
}

// Test cases
$tests = [
    'admin-dashboard.php',
    'auth/admin-dashboard.php',
    '/auth/admin-dashboard.php',
    '/SarkinMota/auth/admin-dashboard.php',
];

$_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/../auth/dashboard.php';

echo "=== SCRIPT: auth/dashboard.php ===" . PHP_EOL;
foreach ($tests as $t) {
    echo $t . " => " . resolve_redirect_url($t) . PHP_EOL;
}
