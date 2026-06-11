<?php

declare(strict_types=1);

// Load .env file
$envFile = APP_ROOT . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        if (!str_contains($line, '=')) continue;
        [$key, $value] = explode('=', $line, 2);
        $key   = trim($key);
        $value = trim($value, " \t\n\r\0\x0B\"'");
        if (!array_key_exists($key, $_ENV)) {
            $_ENV[$key] = $value;
            putenv("{$key}={$value}");
        }
    }
}

// Error reporting
$debug = ($_ENV['APP_DEBUG'] ?? 'false') === 'true';
if ($debug) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL);
    ini_set('log_errors', '1');
    ini_set('error_log', APP_ROOT . '/storage/php_errors.log');
}

// Timezone
date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'Africa/Lusaka');

// Determine base path from script location
// When accessed via rewrite: SCRIPT_NAME = /ExpedientLog/public/index.php
// BASE_PATH should be /ExpedientLog
$scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');          // /ExpedientLog/public
$basePath  = dirname($scriptDir);                              // /ExpedientLog
if ($basePath === '/' || $basePath === '\\' || $basePath === '.') {
    $basePath = '';
}
define('BASE_PATH', $basePath);
define('APP_NAME',  $_ENV['APP_NAME'] ?? 'ExpedientLog');
