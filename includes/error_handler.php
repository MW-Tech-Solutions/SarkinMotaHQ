<?php
/**
 * Global Exception and Error Handler
 * Sarkin Mota HQ — Enterprise Edition (F14 Resolution)
 */
require_once __DIR__ . '/logger.php';

function custom_exception_handler($exception) {
    Logger::error("Uncaught Exception: " . $exception->getMessage(), [
        'file' => $exception->getFile(),
        'line' => $exception->getLine(),
        'trace' => $exception->getTraceAsString()
    ]);

    if (php_sapi_name() === 'cli') {
        echo "Error: An unexpected execution error occurred. Details logged.\n";
        exit(1);
    }

    if (!headers_sent()) {
        http_response_code(500);
    }

    if (file_exists(__DIR__ . '/../error_pages/500.php')) {
        require __DIR__ . '/../error_pages/500.php';
    } else {
        echo "<h1>500 Internal Server Error</h1><p>An unexpected application error occurred. Please contact system administrator.</p>";
    }
    exit;
}

function custom_error_handler($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) {
        return false;
    }
    Logger::warning("PHP Error [{$errno}]: {$errstr}", ['file' => $errfile, 'line' => $errline]);
    return true;
}

set_exception_handler('custom_exception_handler');
set_error_handler('custom_error_handler');

