<?php
// database/config.php - Central Database Configuration for RuXt AI

if (!defined('DB_DRIVER')) {
    // Set to 'mysql' to use MySQL (MariaDB) in XAMPP, or 'sqlite' for local SQLite file
    define('DB_DRIVER', 'mysql');
}

// MySQL Configuration
if (!defined('DB_HOST')) {
    define('DB_HOST', '127.0.0.1');
}

if (!defined('DB_PORT')) {
    define('DB_PORT', '3306');
}

if (!defined('DB_NAME')) {
    define('DB_NAME', 'private_assistance');
}

if (!defined('DB_USER')) {
    define('DB_USER', 'root');
}

if (!defined('DB_PASS')) {
    define('DB_PASS', '');
}

// SQLite Configuration (fallback or standalone)
if (!defined('DB_SQLITE_PATH')) {
    define('DB_SQLITE_PATH', __DIR__ . '/assistant.db');
}
