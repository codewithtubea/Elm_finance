<?php
/**
 * Authentication Debugging Tool
 * Only for development - shows session and auth status
 * Remove this file in production
 */

require_once 'config/database.php';
require_once 'includes/security.php';

Security::configureSession();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/auth.php';

// Only allow access from localhost
if ($_SERVER['REMOTE_ADDR'] !== '127.0.0.1' && $_SERVER['REMOTE_ADDR'] !== 'localhost') {
    die('Access denied. This tool is only available locally.');
}

$auth = new Auth($pdo);
$isLoggedIn = $auth->isLoggedIn();
$user = $isLoggedIn ? $auth->getUser() : null;

// Log session details
$sessionData = [
    'Session ID' => session_id(),
    'Logged In' => $isLoggedIn ? 'YES' : 'NO',
    'User ID' => $_SESSION['user_id'] ?? 'Not set',
    'Username' => $_SESSION['username'] ?? 'Not set',
    'IP Address' => $_SERVER['REMOTE_ADDR'],
    'Stored IP' => $_SESSION['ip_address'] ?? 'Not set',
    'IP Match' => ($_SESSION['ip_address'] ?? '') === $_SERVER['REMOTE_ADDR'] ? 'YES' : 'NO',
    'Login Time' => $_SESSION['login_time'] ?? 'Not set',
    'Session Age' => isset($_SESSION['login_time']) ? time() - $_SESSION['login_time'] . ' seconds' : 'N/A',
    'Session Timeout (8 hours)' => isset($_SESSION['login_time']) && time() - $_SESSION['login_time'] > 28800 ? 'EXPIRED' : 'OK',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'logout') {
    $auth->logout();
    header('Location: test-auth.php');
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auth Debug - Elm Finance</title>
    <style>
        body {
            font-family: monospace;
            background: #1e1e1e;
            color: #e0e0e0;
            padding: 20px;
            margin: 0;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
        }
        h1 {
            color: #00ff88;
            text-shadow: 0 0 10px #00ff88;
        }
        .status {
            background: #2d2d2d;
            border-left: 4px solid #00ff88;
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
        }
        .status.ok {
            border-left-color: #00ff88;
        }
        .status.error {
            border-left-color: #ff4444;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th, td {
            text-align: left;
            padding: 10px;
            border-bottom: 1px solid #3d3d3d;
        }
        th {
            background: #2d2d2d;
            color: #00ff88;
            font-weight: bold;
        }
        .success {
            color: #00ff88;
        }
        .error {
            color: #ff4444;
        }
        .warning {
            color: #ffaa00;
        }
        .btn {
            background: #00ff88;
            color: #1e1e1e;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            border-radius: 4px;
            font-weight: bold;
            margin: 5px;
        }
        .btn:hover {
            background: #00dd66;
        }
        .btn-danger {
            background: #ff4444;
            color: white;
        }
        .btn-danger:hover {
            background: #dd2222;
        }
        a {
            color: #00ffff;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 Authentication Debug Dashboard</h1>
        
        <div class="status <?php echo $isLoggedIn ? 'ok' : 'error'; ?>">
            <strong>Auth Status:</strong> 
            <span class="<?php echo $isLoggedIn ? 'success' : 'error'; ?>">
                <?php echo $isLoggedIn ? '✓ LOGGED IN' : '✗ NOT LOGGED IN'; ?>
            </span>
        </div>
        
        <?php if ($isLoggedIn && $user): ?>
            <div class="status ok">
                <strong>User:</strong> <?php echo htmlspecialchars($user['username']); ?> (ID: <?php echo $user['id']; ?>)<br>
                <strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?><br>
                <strong>Last Login:</strong> <?php echo $user['last_login'] ?: 'Never'; ?>
            </div>
        <?php endif; ?>
        
        <h2>Session Information</h2>
        <table>
            <tr>
                <th>Parameter</th>
                <th>Value</th>
                <th>Status</th>
            </tr>
            <?php foreach ($sessionData as $key => $value): ?>
                <tr>
                    <td><?php echo $key; ?></td>
                    <td><?php echo htmlspecialchars((string)$value); ?></td>
                    <td>
                        <?php
                        if (strpos($key, 'Match') !== false) {
                            echo $value === 'YES' ? '<span class="success">✓</span>' : '<span class="error">✗</span>';
                        } elseif ($key === 'Session Timeout (8 hours)') {
                            echo $value === 'OK' ? '<span class="success">✓</span>' : '<span class="error">✗ EXPIRED</span>';
                        } elseif (strpos($key, 'Not set') === false && trim($value)) {
                            echo '<span class="success">✓</span>';
                        } else {
                            echo '<span class="warning">⚠</span>';
                        }
                        ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
        
        <h2>Raw Session Data</h2>
        <pre style="background: #2d2d2d; padding: 15px; border-radius: 4px; overflow-x: auto;">
<?php var_dump($_SESSION); ?>
        </pre>
        
        <h2>Quick Actions</h2>
        <div>
            <a href="login.php"><button class="btn">→ Go to Login</button></a>
            <a href="dashboard.php"><button class="btn">→ Go to Dashboard</button></a>
            <a href="register.php"><button class="btn">→ Go to Register</button></a>
            <?php if ($isLoggedIn): ?>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="logout">
                    <button type="submit" class="btn btn-danger">→ Logout</button>
                </form>
            <?php endif; ?>
        </div>
        
        <hr style="border: 1px solid #3d3d3d; margin: 30px 0;">
        
        <p style="color: #666; font-size: 0.9em;">
            ⚠️ <strong>Security Notice:</strong> This debug page should be removed from production.<br>
            Only accessible from localhost (127.0.0.1).
        </p>
    </div>
</body>
</html>
