<?php
// logout.php
session_start();
session_destroy();

// If AJAX request
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'redirect' => 'login.php']);
    exit;
}

// Regular request
header('Location: login.php');
exit;