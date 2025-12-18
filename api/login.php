<?php
/**
 * API Login Endpoint (AJAX)
 * Returns JSON:
 * {
 *   success: true|false,
 *   error?: string,
 *   user?: object
 * }
 */

/* ================== ERROR HANDLING ================== */
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);

/* ================== OUTPUT BUFFER ================== */
ob_start();

/* ================== JSON RESPONSE HELPER ================== */
function sendJsonResponse(array $data, int $statusCode = 200): void {
    ob_clean();
    http_response_code($statusCode);
    header("Content-Type: application/json; charset=UTF-8");
    header("Cache-Control: no-store, no-cache, must-revalidate");
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/* ================== SESSION ================== */
/**
 * Session MUST start before anything else
 * SessionManager is the ONLY place allowed to configure sessions
 */
try {
    require_once "../includes/session.php";
    SessionManager::start();
} catch (Throwable $e) {
    error_log("Session error: " . $e->getMessage());
    sendJsonResponse([
        "success" => false,
        "error"   => "Server session error"
    ], 500);
}

/* ================== DEPENDENCIES ================== */
try {
    require_once "../config/database.php";
    require_once "../includes/auth.php";
} catch (Throwable $e) {
    error_log("Dependency load error: " . $e->getMessage());
    sendJsonResponse([
        "success" => false,
        "error"   => "Server configuration error"
    ], 500);
}

/* ================== REQUEST VALIDATION ================== */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse([
        "success" => false,
        "error"   => "Invalid request method"
    ], 405);
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$remember = isset($_POST['remember']);

if ($username === '' || $password === '') {
    sendJsonResponse([
        "success" => false,
        "error"   => "Username and password are required"
    ], 400);
}

/* ================== AUTHENTICATION ================== */
try {
    $auth   = new Auth($pdo);
    $result = $auth->login($username, $password, $remember);

    if (!is_array($result) || !isset($result['success'])) {
        error_log("Invalid auth response");
        sendJsonResponse([
            "success" => false,
            "error"   => "Authentication system error"
        ], 500);
    }

    if ($result['success'] === true) {

        // Final sanity check: session MUST exist
        if (!isset($_SESSION['user_id'])) {
            error_log("Login success but session missing");
            sendJsonResponse([
                "success" => false,
                "error"   => "Session initialization failed"
            ], 500);
        }

        // Successful login
        sendJsonResponse([
            "success" => true,
            "user"    => $result['user']
        ], 200);
    }

    // Invalid credentials
    sendJsonResponse([
        "success" => false,
        "error"   => $result['error'] ?? "Login failed"
    ], 401);

} catch (Throwable $e) {
    error_log("Login exception: " . $e->getMessage());
    sendJsonResponse([
        "success" => false,
        "error"   => "Login failed. Please try again."
    ], 500);
}
