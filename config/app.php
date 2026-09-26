<?php
/**
 * Application Core Configuration & Environment Loader
 * Sarkin Mota HQ Enterprise Edition
 */

// Simple .env parser helper
if (!function_exists('load_env')) {
    function load_env($filePath) {
        if (!file_exists($filePath)) {
            return;
        }
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, '#') === 0) {
                continue;
            }
            if (strpos($line, '=') !== false) {
                list($name, $value) = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value, " \t\n\r\0\x0B\"'");
                if (getenv($name) === false && !array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                    putenv("{$name}={$value}");
                    $_ENV[$name] = $value;
                    $_SERVER[$name] = $value;
                }
            }
        }
    }
}

// Load .env file from project root
load_env(__DIR__ . '/../.env');

// Helper to get environment variable with fallback
if (!function_exists('env')) {
    function env($key, $default = null) {
        $val = getenv($key);
        if ($val === false) {
            $val = $_ENV[$key] ?? $_SERVER[$key] ?? null;
        }
        if ($val === null) {
            return $default;
        }
        switch (strtolower($val)) {
            case 'true': return true;
            case 'false': return false;
            case 'empty': return '';
            case 'null': return null;
        }
        return $val;
    }
}

return [
    'app_name' => env('APP_NAME', 'Sarkin Mota HQ'),
    'app_env' => env('APP_ENV', 'development'),
    'app_debug' => env('APP_DEBUG', false),
    'app_url' => env('APP_URL', 'http://localhost/SarkinMota'),
    'app_key' => env('APP_KEY', ''),
    
    'db' => [
        'host' => env('DB_HOST', 'localhost'),
        'port' => env('DB_PORT', '3308'),
        'database' => env('DB_DATABASE', 'sarkinmotahq_db'),
        'username' => env('DB_USERNAME', 'root'),
        'password' => env('DB_PASSWORD', ''),
    ],
    
    'payment' => [
        'paystack_secret_key' => env('PAYSTACK_SECRET_KEY', ''),
        'flutterwave_secret_key' => env('FLUTTERWAVE_SECRET_KEY', ''),
        'flutterwave_webhook_hash' => env('FLUTTERWAVE_WEBHOOK_HASH', ''),
        'currency' => env('PAYMENT_CURRENCY', 'NGN'),
    ],
    
    'api' => [
        'secret_key' => env('API_SECRET_KEY', ''),
    ],
];

