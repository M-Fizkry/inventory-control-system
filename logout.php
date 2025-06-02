<?php
require_once 'includes/init.php';

// Clear all session data
session_unset();
session_destroy();

// Redirect to login page
redirect('login.php');
?>
