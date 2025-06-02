<?php
require_once 'includes/init.php';

$password = 'admin123';
$stored_hash = '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN.jf9G.5/MYnGHGRHEei';

echo "Testing password verification:\n";
echo "Password: " . $password . "\n";
echo "Stored hash: " . $stored_hash . "\n";
echo "Verification result: " . (verifyPassword($password, $stored_hash) ? "Success" : "Failed") . "\n";

// Test generating a new hash
echo "\nGenerating new hash:\n";
$new_hash = generateHash($password);
echo "New hash: " . $new_hash . "\n";
echo "Verification with new hash: " . (verifyPassword($password, $new_hash) ? "Success" : "Failed") . "\n";

// Test database connection and user query
echo "\nTesting database query:\n";
$query = "SELECT id, username, password, role FROM users WHERE username = 'admin'";
$stmt = $db->query($query);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
echo "User data:\n";
print_r($user);
?>
