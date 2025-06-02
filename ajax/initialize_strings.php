<?php
require_once '../includes/init.php';

// Check if request is AJAX
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest') {
    header('HTTP/1.0 403 Forbidden');
    exit('Access forbidden');
}

// Check if user has required access
if (!checkAccess('settings', 'edit')) {
    echo json_encode([
        'success' => false,
        'message' => Language::get('access_denied')
    ]);
    exit;
}

try {
    // Initialize language strings
    if (initializeLanguageStrings()) {
        echo json_encode([
            'success' => true,
            'message' => Language::get('strings_initialized')
        ]);
    } else {
        throw new Exception(Language::get('initialization_failed'));
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
