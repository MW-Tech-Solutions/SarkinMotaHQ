<?php
/**
 * Structured Application Logging Service
 * Sarkin Mota HQ — Enterprise Edition
 */

class Logger {
    private static $logFile = __DIR__ . '/../storage/logs/app.log';

    public static function log($level, $message, array $context = []) {
        $dir = dirname(self::$logFile);
        if (!file_exists($dir)) {
            @mkdir($dir, 0755, true);
        }

        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' ' . json_encode($context) : '';
        $formatted = "[{$timestamp}] [{$level}] {$message}{$contextStr}" . PHP_EOL;

        @file_put_contents(self::$logFile, $formatted, FILE_APPEND | LOCK_EX);
    }

    public static function info($message, array $context = []) {
        self::log('INFO', $message, $context);
    }

    public static function warning($message, array $context = []) {
        self::log('WARNING', $message, $context);
    }

    public static function error($message, array $context = []) {
        self::log('ERROR', $message, $context);
    }
}

