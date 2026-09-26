<?php
/**
 * Database Connection Utility
 * Using PHP PDO for secure, prepared statement operations with environment config integration.
 * Sarkin Mota HQ — Enterprise Edition
 */
$appConfig = require_once __DIR__ . '/app.php';
$dbConfig = $appConfig['db'] ?? [];

if (!defined('DB_HOST')) define('DB_HOST', $dbConfig['host'] ?? env('DB_HOST', 'localhost'));
if (!defined('DB_PORT')) define('DB_PORT', $dbConfig['port'] ?? env('DB_PORT', '3306'));
if (!defined('DB_USER')) define('DB_USER', $dbConfig['username'] ?? env('DB_USERNAME', 'root'));
if (!defined('DB_PASS')) define('DB_PASS', $dbConfig['password'] ?? env('DB_PASSWORD', ''));
if (!defined('DB_NAME')) define('DB_NAME', $dbConfig['database'] ?? env('DB_DATABASE', 'sarkinmotahq_db'));

try {
    // Attempt PDO connection with configured port
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO(
        $dsn,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    // Fallback attempt without port if port connection fails
    try {
        $dsn_fallback = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO(
            $dsn_fallback,
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
    } catch (PDOException $e2) {
        error_log("Database Connection Error: " . $e2->getMessage());
        if (function_exists('env') && env('APP_DEBUG', false)) {
            die("Database Connection Failed: " . htmlspecialchars($e2->getMessage()));
        } else {
            die("Database Connection Failed. Please contact system administration.");
        }
    }
}
