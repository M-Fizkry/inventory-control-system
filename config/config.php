<?php
// Application configuration
define('BASE_URL', 'http://localhost:8000');
define('APP_NAME', 'Inventory Control System');
define('APP_VERSION', '1.0.0');

// Default language
define('DEFAULT_LANG', 'en');

// Available languages
$available_languages = [
    'en' => 'English',
    'id' => 'Bahasa Indonesia'
];

// Database configuration (SQLite)
define('DB_FILE', __DIR__ . '/../db/inventory.sqlite');

// Session configuration
define('SESSION_NAME', 'inventory_session');
define('SESSION_LIFETIME', 7200); // 2 hours

// Security
define('HASH_COST', 12); // For password hashing

// Debug mode
define('DEBUG_MODE', true);

// Error logging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error.log');

// Set session parameters
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
ini_set('session.cookie_samesite', 'Strict');

// Set timezone
date_default_timezone_set('Asia/Jakarta');
?>
