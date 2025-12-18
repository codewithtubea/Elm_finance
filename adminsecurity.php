<?php
// adminsecurity.php - Security Dashboard
session_start();
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/security.php';

try {
    $db = new Database();
    $pdo = $db->getPDO();
    $auth = new Auth($pdo);
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Require admin access
$auth->requireAdmin();

$user = $auth->getCurrentUser();
if (!$user) {
    $auth->logout();
    header('Location: login.php');
    exit();
}

$userName = $user['username'];
$activePage = 'adminsecurity';

// Security Status Checks
$securityStatus = [
    'session_secure' => ini_get('session.cookie_secure') ? 'Enabled' : 'Disabled (Live only)',
    'httponly' => ini_get('session.cookie_httponly') ? 'Active' : 'Missing',
    'samesite' => ini_get('session.cookie_samesite') ?: 'Not Set',
    'strict_mode' => ini_get('session.use_strict_mode') ? 'On' : 'Off',
];

$pageTitle = 'Security Dashboard | Elm Finance';
?>

<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="public/css/style.css?v=<?php echo time(); ?>">
</head>
<body>
    <!-- Include Sidebar -->
    <?php include 'includes/sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="admin-container">
            <div class="admin-header glass-card">
                <h1>🛡️ Security Dashboard</h1>
                <p>System-wide security monitoring and session configuration overview.</p>
            </div>

            <div class="admin-stats-grid">
                <div class="stat-card glass-card">
                    <div class="stat-header">
                        <div class="stat-icon" style="background: rgba(0, 255, 136, 0.2); color: var(--accent-primary);">
                            <i class="fas fa-cookie-bite"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo $securityStatus['httponly']; ?></div>
                    <div class="stat-label">HttpOnly Cookies</div>
                </div>
                
                <div class="stat-card glass-card">
                    <div class="stat-header">
                        <div class="stat-icon" style="background: rgba(0, 255, 136, 0.2); color: var(--accent-primary);">
                            <i class="fas fa-shield-virus"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo $securityStatus['strict_mode']; ?></div>
                    <div class="stat-label">Session Strict Mode</div>
                </div>

                <div class="stat-card glass-card">
                    <div class="stat-header">
                        <div class="stat-icon" style="background: rgba(0, 255, 136, 0.2); color: var(--accent-primary);">
                            <i class="fas fa-user-shield"></i>
                        </div>
                    </div>
                    <div class="stat-value">Admin</div>
                    <div class="stat-label">Your Access Level</div>
                </div>
            </div>

            <div class="glass-card" style="padding: 2rem; margin-top: 2rem;">
                <h2 style="margin-bottom: 1.5rem;"><i class="fas fa-list-check"></i> Security Configuration Details</h2>
                <div class="admin-table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Directive</th>
                                <th>Value</th>
                                <th>Risk Level</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>session.cookie_secure</td>
                                <td><?php echo $securityStatus['session_secure']; ?></td>
                                <td><span class="role-badge student">Low/Med</span></td>
                                <td>Ensures cookies are only sent over HTTPS.</td>
                            </tr>
                            <tr>
                                <td>session.cookie_httponly</td>
                                <td><?php echo $securityStatus['httponly']; ?></td>
                                <td><span class="role-badge admin">Critical</span></td>
                                <td>Prevents JavaScript access to session cookies (XSS Protection).</td>
                            </tr>
                            <tr>
                                <td>session.cookie_samesite</td>
                                <td><?php echo $securityStatus['samesite']; ?></td>
                                <td><span class="role-badge student">Medium</span></td>
                                <td>Prevents CSRF attacks by controlling cross-site cookie behavior.</td>
                            </tr>
                            <tr>
                                <td>session.use_strict_mode</td>
                                <td><?php echo $securityStatus['strict_mode']; ?></td>
                                <td><span class="role-badge student">Low</span></td>
                                <td>Prevents session fixation attacks.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="glass-card" style="padding: 2rem; margin-top: 2rem; border-left: 4px solid var(--accent-primary);">
                <h3><i class="fas fa-info-circle"></i> Security Log Notice</h3>
                <p style="margin-top: 0.5rem; opacity: 0.8;">The centralized audit logging system is active. All administrative actions are recorded in the system logs for compliance and forensics.</p>
            </div>
        </div>
    </div>

    <script src="public/js/theme-manager.js"></script>
</body>
</html>
