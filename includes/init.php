<?php
// Start session
session_start();

// Load configurations
require_once 'config/config.php';
require_once 'config/database.php';

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set default timezone
date_default_timezone_set('Asia/Jakarta');

// Set current language
if (isset($_SESSION['language'])) {
    define('CURRENT_LANG', $_SESSION['language']);
} else {
    define('CURRENT_LANG', DEFAULT_LANG);
    $_SESSION['language'] = DEFAULT_LANG;
}

// Load helper functions
require_once 'includes/functions.php';

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Load language system
require_once 'includes/language.php';
Language::loadFromDatabase($db);

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/login.php');
        exit();
    }
}

// Check user access rights
function checkAccess($menuId, $permission = 'view') {
    if (!isLoggedIn()) return false;
    
    global $db;
    $userId = $_SESSION['user_id'];
    
    $query = "SELECT can_$permission FROM user_access 
              WHERE user_id = :user_id AND menu_id = :menu_id";
    
    $stmt = $db->prepare($query);
    $stmt->execute([
        ':user_id' => $userId,
        ':menu_id' => $menuId
    ]);
    
    return $stmt->fetchColumn() == 1;
}
?>
