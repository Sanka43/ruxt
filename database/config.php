<?php
// database/config.php - Central Database Configuration for RuXt AI

// Automatically load .env file if present in project root
$envFilePath = dirname(__DIR__) . '/.env';
if (file_exists($envFilePath) && is_readable($envFilePath)) {
    $envLines = file($envFilePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($envLines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || $line[0] === ';') continue;
        if (strpos($line, '=') !== false) {
            list($envKey, $envVal) = explode('=', $line, 2);
            $envKey = trim($envKey);
            $envVal = trim($envVal, " \t\n\r\0\x0B\"'");
            putenv("{$envKey}={$envVal}");
            $_ENV[$envKey] = $envVal;
            $_SERVER[$envKey] = $envVal;
        }
    }
}

if (!defined('DB_DRIVER')) {
    define('DB_DRIVER', getenv('DB_DRIVER') ?: 'mysql');
}

// MySQL Configuration
if (!defined('DB_HOST')) {
    define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
}

if (!defined('DB_PORT')) {
    define('DB_PORT', getenv('DB_PORT') ?: '3306');
}

if (!defined('DB_NAME')) {
    define('DB_NAME', getenv('DB_NAME') ?: 'private_assistance');
}

if (!defined('DB_USER')) {
    define('DB_USER', getenv('DB_USER') ?: 'root');
}

if (!defined('DB_PASS')) {
    define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
}

// SQLite Configuration (fallback or standalone)
if (!defined('DB_SQLITE_PATH')) {
    define('DB_SQLITE_PATH', __DIR__ . '/assistant.db');
}
