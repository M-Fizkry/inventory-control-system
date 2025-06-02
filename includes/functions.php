<?php
// Security functions
function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function generateHash($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => HASH_COST]);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Navigation functions
function redirect($path) {
    header('Location: ' . BASE_URL . '/' . $path);
    exit();
}

function getCurrentPage() {
    $path = $_SERVER['REQUEST_URI'];
    $path = parse_url($path, PHP_URL_PATH);
    return basename($path);
}

// Message handling
function setMessage($type, $message) {
    $_SESSION['message'] = [
        'type' => $type,
        'text' => $message
    ];
}

function getMessage() {
    if (isset($_SESSION['message'])) {
        $message = $_SESSION['message'];
        unset($_SESSION['message']);
        return $message;
    }
    return null;
}

// Stock management functions
function updateStock($itemId, $quantity, $type = 'add') {
    global $db;
    
    try {
        $db->beginTransaction();
        
        // Get current stock
        $query = "SELECT current_stock FROM items WHERE id = :id FOR UPDATE";
        $stmt = $db->prepare($query);
        $stmt->execute([':id' => $itemId]);
        $currentStock = $stmt->fetchColumn();
        
        // Calculate new stock
        $newStock = $type === 'add' ? 
            $currentStock + $quantity : 
            $currentStock - $quantity;
        
        // Update stock
        $query = "UPDATE items SET current_stock = :stock WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->execute([
            ':stock' => $newStock,
            ':id' => $itemId
        ]);
        
        $db->commit();
        return true;
    } catch (Exception $e) {
        $db->rollBack();
        error_log("Error updating stock: " . $e->getMessage());
        return false;
    }
}

function checkStockStatus($itemId) {
    global $db;
    
    $query = "SELECT current_stock, min_stock, max_stock 
              FROM items WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->execute([':id' => $itemId]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($item) {
        if ($item['current_stock'] <= $item['min_stock']) {
            return 'low';
        } elseif ($item['current_stock'] >= $item['max_stock']) {
            return 'high';
        }
        return 'normal';
    }
    return false;
}

// BOM functions
function calculateMaterialRequirements($finishedItemId, $quantity) {
    global $db;
    
    $requirements = [];
    
    $query = "WITH RECURSIVE bom_tree AS (
                SELECT 
                    component_item_id,
                    finished_item_id,
                    quantity,
                    1 as level
                FROM bom
                WHERE finished_item_id = :item_id
                
                UNION ALL
                
                SELECT 
                    b.component_item_id,
                    b.finished_item_id,
                    b.quantity * bt.quantity,
                    bt.level + 1
                FROM bom b
                INNER JOIN bom_tree bt ON b.finished_item_id = bt.component_item_id
            )
            SELECT 
                i.id,
                i.code,
                i.name,
                i.type,
                SUM(bt.quantity) * :qty as required_quantity,
                i.current_stock,
                i.unit
            FROM bom_tree bt
            JOIN items i ON i.id = bt.component_item_id
            GROUP BY i.id";
    
    $stmt = $db->prepare($query);
    $stmt->execute([
        ':item_id' => $finishedItemId,
        ':qty' => $quantity
    ]);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Date/Time functions
function formatDate($date, $format = 'Y-m-d') {
    return date($format, strtotime($date));
}

function formatDateTime($datetime, $format = 'Y-m-d H:i:s') {
    return date($format, strtotime($datetime));
}

// File handling
function uploadFile($file, $destination, $allowedTypes = ['image/jpeg', 'image/png']) {
    try {
        if (!isset($file['error']) || is_array($file['error'])) {
            throw new RuntimeException('Invalid parameters.');
        }

        switch ($file['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_NO_FILE:
                throw new RuntimeException('No file sent.');
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                throw new RuntimeException('Exceeded filesize limit.');
            default:
                throw new RuntimeException('Unknown errors.');
        }

        if (!in_array($file['type'], $allowedTypes)) {
            throw new RuntimeException('Invalid file format.');
        }

        $filename = sprintf(
            '%s-%s.%s',
            uniqid(),
            date('YmdHis'),
            pathinfo($file['name'], PATHINFO_EXTENSION)
        );

        if (!move_uploaded_file(
            $file['tmp_name'],
            sprintf('%s/%s', $destination, $filename)
        )) {
            throw new RuntimeException('Failed to move uploaded file.');
        }

        return $filename;
    } catch (RuntimeException $e) {
        error_log($e->getMessage());
        return false;
    }
}

// Validation functions
function validateRequired($value, $fieldName) {
    if (empty($value)) {
        return sprintf('%s is required.', $fieldName);
    }
    return true;
}

function validateNumeric($value, $fieldName) {
    if (!is_numeric($value)) {
        return sprintf('%s must be a number.', $fieldName);
    }
    return true;
}

function validateEmail($email) {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return 'Invalid email format.';
    }
    return true;
}

// Pagination function
function getPagination($total, $page, $limit = 10) {
    $totalPages = ceil($total / $limit);
    $page = max(1, min($page, $totalPages));
    
    return [
        'current_page' => $page,
        'total_pages' => $totalPages,
        'limit' => $limit,
        'offset' => ($page - 1) * $limit
    ];
}

// Debug function (only if debug mode is enabled)
function debug($data) {
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        echo '<pre>';
        print_r($data);
        echo '</pre>';
    }
}
?>
