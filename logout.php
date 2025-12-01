<?php
require_once 'config/database.php';
require_once 'includes/security.php';
require_once 'includes/auth.php';

// Configure session security
Security::configureSession();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Logout user
$auth = new Auth($pdo);
$auth->logout();

// Redirect to login page
header('Location: login.php?logout=1');
exit;
?>