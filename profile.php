<?php
// profile.php - User Profile Management
require_once 'includes/auth.php';
require_once 'config/database.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize Auth
$auth = new Auth($pdo);

// Check if user is logged in
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit();
}

// Get user data
$user = $auth->getUser();
if (!$user) {
    $auth->logout();
    header('Location: login.php');
    exit();
}

// Initialize messages
$success = '';
$error = '';
$errors = [];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Profile Update
    if ($action === 'update_profile') {
        $userData = [
            'first_name' => trim($_POST['first_name'] ?? ''),
            'last_name' => trim($_POST['last_name'] ?? ''),
            'university' => trim($_POST['university'] ?? ''),
            'email' => trim($_POST['email'] ?? '')
        ];
        
        $result = $auth->updateProfile($userData);
        if ($result['success']) {
            $success = $result['message'];
            // Refresh user data
            $user = $auth->getUser();
        } else {
            $error = $result['error'] ?? '';
            $errors = $result['errors'] ?? [];
        }
    }
    
    // Password Change
    elseif ($action === 'change_password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Validate passwords match
        if ($newPassword !== $confirmPassword) {
            $error = "New passwords don't match";
        } else {
            $result = $auth->changePassword($currentPassword, $newPassword);
            if ($result['success']) {
                $success = $result['message'];
            } else {
                $error = $result['error'];
            }
        }
    }
    
    // Username Change
    elseif ($action === 'change_username') {
        $newUsername = trim($_POST['new_username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($newUsername)) {
            $error = "Username cannot be empty";
        } elseif (strlen($newUsername) < 3 || strlen($newUsername) > 20) {
            $error = "Username must be 3-20 characters";
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $newUsername)) {
            $error = "Username can only contain letters, numbers, and underscores";
        } else {
            try {
                // Verify password first
                $stmt = $pdo->prepare("SELECT password_hash FROM elm_users WHERE id = ?");
                $stmt->execute([$user['id']]);
                $userData = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$userData || !password_verify($password, $userData['password_hash'])) {
                    $error = "Password is incorrect";
                } else {
                    // Check if username exists
                    $stmt = $pdo->prepare("SELECT id FROM elm_users WHERE username = ? AND id != ?");
                    $stmt->execute([$newUsername, $user['id']]);
                    if ($stmt->rowCount() > 0) {
                        $error = "Username already taken";
                    } else {
                        // Update username
                        $stmt = $pdo->prepare("UPDATE elm_users SET username = ? WHERE id = ?");
                        $stmt->execute([$newUsername, $user['id']]);
                        
                        // Update session
                        $_SESSION['username'] = $newUsername;
                        
                        $success = "Username updated successfully";
                        $user['username'] = $newUsername;
                    }
                }
            } catch (Exception $e) {
                error_log("Username change error: " . $e->getMessage());
                $error = "Failed to update username";
            }
        }
    }
    
    // Account Deactivation
    elseif ($action === 'deactivate_account') {
        $password = $_POST['password'] ?? '';
        
        if (empty($password)) {
            $error = "Password is required";
        } else {
            $result = $auth->deactivateAccount($password);
            if ($result['success']) {
                header('Location: logout.php?deactivated=true');
                exit();
            } else {
                $error = $result['error'];
            }
        }
    }
}

$pageTitle = "Profile Settings | Elm Finance";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* Use the same CSS as dashboard.php with additions for profile page */
        :root[data-theme="dark"] {
            --bg-primary: #0a0a0a;
            --bg-secondary: #121212;
            --bg-tertiary: #1a1a1a;
            --card-bg: rgba(26, 26, 26, 0.7);
            --text-primary: #ffffff;
            --text-secondary: #888888;
            --accent-primary: #00ff88;
            --accent-secondary: #00cc66;
            --success: #00ff88;
            --warning: #ffaa00;
            --error: #ff4444;
            --border-light: rgba(255, 255, 255, 0.08);
            --glass-bg: rgba(255, 255, 255, 0.05);
            --glass-border: rgba(255, 255, 255, 0.1);
            --glass-shadow: 0 8px 32px rgba(0, 255, 136, 0.1);
            --input-bg: rgba(255, 255, 255, 0.05);
            --input-border: rgba(255, 255, 255, 0.1);
        }

        :root[data-theme="light"] {
            --bg-primary: #f8fafc;
            --bg-secondary: #f1f5f9;
            --bg-tertiary: #e2e8f0;
            --card-bg: rgba(255, 255, 255, 0.95);
            --text-primary: #1e293b;
            --text-secondary: #475569;
            --accent-primary: #00cc66;
            --accent-secondary: #00a854;
            --success: #059669;
            --warning: #d97706;
            --error: #dc2626;
            --border-light: rgba(0, 0, 0, 0.06);
            --glass-bg: rgba(255, 255, 255, 0.85);
            --glass-border: rgba(0, 0, 0, 0.08);
            --glass-shadow: 0 8px 32px rgba(0, 0, 0, 0.04);
            --input-bg: rgba(0, 0, 0, 0.03);
            --input-border: rgba(0, 0, 0, 0.08);
        }
        
        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        
        html {
            font-size: 62.5%;
        }
        
        body { 
            font-family: 'Poppins', sans-serif; 
            font-weight: 300;
            font-size: 1.1rem;
            line-height: 1.6;
            min-height: 100vh;
            color: var(--text-primary);
            transition: background-color 0.3s ease, color 0.3s ease;
            position: relative;
            overflow-x: hidden;
            padding: 0;
        }
        
        /* ============ SIDEBAR ============ */
        .sidebar { position: fixed; left: 0; top: 0; width: 260px; height: 100vh; background: var(--card-bg); backdrop-filter: blur(20px); border-right: 1px solid var(--glass-border); z-index: 1000; padding: 2rem 0; display: flex; flex-direction: column; transition: transform 0.3s ease; }
        .sidebar-brand { padding: 0 2rem; margin-bottom: 3rem; display: flex; align-items: center; gap: 1rem; }
        .sidebar-logo { width: 40px; height: 40px; background: rgba(0, 255, 136, 0.1); border-radius: 10px; display: flex; align-items: center; justify-content: center; }
        .sidebar-brand h2 { font-size: 1.8rem; font-weight: 700; background: linear-gradient(135deg, #00ff88, #00cc66); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .sidebar-user { padding: 0 2rem 2rem; border-bottom: 1px solid var(--border-light); margin-bottom: 2rem; display: flex; align-items: center; gap: 1rem; }
        .sidebar-avatar { width: 48px; height: 48px; border-radius: 50%; background: linear-gradient(135deg, #00ff88, #00cc66); display: flex; align-items: center; justify-content: center; font-weight: 600; color: #0a0a0a; font-size: 1.6rem; }
        .sidebar-user-info { flex: 1; overflow: hidden; }
        .sidebar-username { font-weight: 600; font-size: 1.2rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .sidebar-user-role { font-size: 0.9rem; color: var(--text-secondary); text-transform: capitalize; }
        .sidebar-menu { flex: 1; overflow-y: auto; padding: 0 2rem; }
        .menu-section { margin-bottom: 2rem; }
        .section-title { font-size: 0.85rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 1rem; font-weight: 600; }
        .menu-item { display: flex; align-items: center; gap: 1rem; padding: 1rem 1.2rem; color: var(--text-primary); text-decoration: none; border-radius: 10px; margin-bottom: 0.5rem; transition: all 0.3s ease; position: relative; }
        .menu-item i { width: 20px; text-align: center; font-size: 1.2rem; color: var(--text-secondary); transition: color 0.3s ease; }
        .menu-item span { flex: 1; font-size: 1.1rem; font-weight: 400; }
        .menu-item:hover { background: var(--glass-bg); color: var(--accent-primary); }
        .menu-item:hover i { color: var(--accent-primary); }
        .menu-item.active { background: linear-gradient(135deg, rgba(0, 255, 136, 0.1), rgba(0, 255, 136, 0.05)); color: var(--accent-primary); border-left: 3px solid var(--accent-primary); }
        .menu-item.active i { color: var(--accent-primary); }
        .menu-badge { background: var(--accent-primary); color: #0a0a0a; font-size: 0.75rem; font-weight: 600; padding: 0.2rem 0.6rem; border-radius: 10px; min-width: 20px; text-align: center; }
        .sidebar-footer { padding: 2rem; border-top: 1px solid var(--border-light); }
        .logout-btn { display: flex; align-items: center; gap: 1rem; padding: 1rem 1.2rem; background: linear-gradient(135deg, #ff4444, #ff2222); color: white; text-decoration: none; border-radius: 10px; font-weight: 500; transition: all 0.3s ease; width: 100%; border: none; cursor: pointer; font-family: 'Poppins', sans-serif; font-size: 1.1rem; }
        .logout-btn:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(255, 68, 68, 0.3); }
        .main-content { margin-left: 260px; padding: 2rem; min-height: 100vh; transition: margin-left 0.3s ease; }
        .mobile-menu-btn { position: fixed; top: 1.5rem; left: 1.5rem; width: 44px; height: 44px; border-radius: 12px; background: var(--glass-bg); backdrop-filter: blur(10px); border: 1px solid var(--glass-border); color: var(--text-primary); font-size: 1.4rem; cursor: pointer; z-index: 999; display: none; align-items: center; justify-content: center; transition: all 0.3s ease; }
        .mobile-menu-btn:hover { border-color: var(--accent-primary); color: var(--accent-primary); }
        .sidebar-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.5); backdrop-filter: blur(5px); z-index: 998; display: none; }
        @media (max-width: 1024px) { .sidebar { transform: translateX(-100%); } .sidebar.active { transform: translateX(0); } .main-content { margin-left: 0; padding: 1.5rem; } .mobile-menu-btn { display: flex; } }
        @media (max-width: 768px) { .main-content { padding: 1rem; } }
        
        [data-theme="dark"] body {
            background: radial-gradient(circle at 20% 50%, rgba(0, 255, 136, 0.05) 0%, transparent 50%),
                var(--bg-primary);
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        /* Back Button */
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.8rem 1.5rem;
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            color: var(--text-primary);
            text-decoration: none;
            border-radius: 12px;
            margin-bottom: 2rem;
            transition: all 0.3s ease;
        }
        
        .back-btn:hover {
            border-color: var(--accent-primary);
            transform: translateX(-5px);
        }
        
        /* Header */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 3rem;
        }
        
        .header h1 {
            font-size: 2.4rem;
            font-weight: 600;
            background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        /* Profile Layout */
        .profile-layout {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 2rem;
        }
        
        @media (max-width: 768px) {
            .profile-layout {
                grid-template-columns: 1fr;
            }
        }
        
        /* Sidebar */
        .profile-sidebar {
            position: sticky;
            top: 2rem;
            height: fit-content;
        }
        
        .user-card {
            padding: 2rem;
            border-radius: 20px;
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .user-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.4rem;
            font-weight: 600;
            color: #0a0a0a;
            margin: 0 auto 1.5rem;
        }
        
        .user-name {
            font-size: 1.8rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }
        
        .user-email {
            color: var(--text-secondary);
            font-size: 1.1rem;
            margin-bottom: 1.5rem;
        }
        
        .account-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-top: 1.5rem;
        }
        
        .stat-item {
            text-align: center;
            padding: 1rem;
            background: var(--card-bg);
            border-radius: 12px;
        }
        
        .stat-value {
            font-size: 1.4rem;
            font-weight: 600;
            color: var(--accent-primary);
        }
        
        .stat-label {
            font-size: 0.9rem;
            color: var(--text-secondary);
        }
        
        /* Navigation */
        .profile-nav {
            padding: 2rem;
            border-radius: 20px;
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
        }
        
        .nav-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1.2rem;
            border-radius: 12px;
            color: var(--text-secondary);
            text-decoration: none;
            transition: all 0.3s ease;
            cursor: pointer;
            margin-bottom: 0.5rem;
        }
        
        .nav-item:hover {
            background: rgba(0, 255, 136, 0.05);
            color: var(--text-primary);
        }
        
        .nav-item.active {
            background: rgba(0, 255, 136, 0.1);
            color: var(--accent-primary);
            border: 1px solid rgba(0, 255, 136, 0.2);
        }
        
        /* Main Content */
        .profile-content {
            padding: 2rem;
            border-radius: 20px;
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
        }
        
        .section {
            margin-bottom: 3rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid var(--border-light);
        }
        
        .section:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .section-header h2 {
            font-size: 1.8rem;
            font-weight: 500;
        }
        
        /* Forms */
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
        
        label {
            display: block;
            margin-bottom: 0.8rem;
            font-weight: 400;
            color: var(--text-primary);
            font-size: 1.1rem;
        }
        
        input, select, textarea {
            width: 100%;
            padding: 1.2rem 1.5rem;
            border: 1px solid var(--input-border);
            border-radius: 12px;
            font-size: 1.1rem;
            background: var(--input-bg);
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
            color: var(--text-primary);
        }
        
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: var(--accent-primary);
            box-shadow: 0 0 20px rgba(0, 255, 136, 0.2);
        }
        
        /* Buttons */
        .btn {
            padding: 1rem 2rem;
            background: var(--accent-primary);
            color: #0a0a0a;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(0, 255, 136, 0.3);
        }
        
        .btn-secondary {
            background: var(--glass-bg);
            color: var(--text-primary);
            border: 1px solid var(--glass-border);
        }
        
        .btn-danger {
            background: rgba(255, 68, 68, 0.1);
            color: var(--error);
            border: 1px solid rgba(255, 68, 68, 0.3);
        }
        
        .btn-danger:hover {
            background: var(--error);
            color: white;
        }
        
        /* Alerts */
        .alert {
            padding: 1.2rem 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            border: 1px solid;
            animation: slideDown 0.3s ease;
            backdrop-filter: blur(10px);
        }
        
        .alert-success {
            background: rgba(0, 255, 136, 0.1);
            color: var(--success);
            border-color: rgba(0, 255, 136, 0.3);
        }
        
        .alert-error {
            background: rgba(255, 68, 68, 0.1);
            color: var(--error);
            border-color: rgba(255, 68, 68, 0.3);
        }
        
        /* Danger Zone */
        .danger-zone {
            padding: 2rem;
            border-radius: 16px;
            background: rgba(255, 68, 68, 0.05);
            border: 1px solid rgba(255, 68, 68, 0.2);
        }
        
        .danger-zone h3 {
            color: var(--error);
            margin-bottom: 1rem;
            font-size: 1.4rem;
        }
        
        .danger-zone p {
            color: var(--text-secondary);
            margin-bottom: 1.5rem;
            font-size: 1rem;
        }
        
        /* Password Strength */
        .password-strength {
            margin-top: 0.5rem;
            font-size: 0.9rem;
        }
        
        .strength-meter {
            height: 4px;
            background: var(--input-border);
            border-radius: 2px;
            margin-top: 0.5rem;
            overflow: hidden;
        }
        
        .strength-fill {
            height: 100%;
            width: 0%;
            border-radius: 2px;
            transition: width 0.3s ease, background 0.3s ease;
        }
        
        /* Theme Toggle */
        .theme-toggle {
            position: fixed;
            top: 2rem;
            right: 2rem;
            width: 44px;
            height: 44px;
            border-radius: 12px;
            border: 1px solid var(--glass-border);
            background: var(--glass-bg);
            color: var(--text-primary);
            cursor: pointer;
            font-size: 1.4rem;
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }
        
        .theme-toggle:hover {
            border-color: var(--accent-primary);
            transform: rotate(30deg);
        }
        
        /* Animations */
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }
        
        .fade-in {
            animation: fadeIn 0.5s ease;
        }
    </style>
</head>
<body>
    <!-- Theme Toggle -->
    <button class="theme-toggle" id="themeToggle">
        <span id="themeIcon">🌙</span>
    </button>

    <div class="container">
        <!-- Back Button -->
        <a href="dashboard.php" class="back-btn">
            ← Back to Dashboard
        </a>
        
        <!-- Header -->
        <div class="header">
            <h1>Account Settings</h1>
            <div class="header-actions">
                <span style="color: var(--text-secondary); font-size: 1.1rem;">
                    Member since <?php echo date('M Y', strtotime($user['created_at'])); ?>
                </span>
            </div>
        </div>
        
        <!-- Alerts -->
        <?php if ($success): ?>
            <div class="alert alert-success">
                <strong>✓ Success</strong>
                <p><?php echo htmlspecialchars($success); ?></p>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <strong>⚠️ Error</strong>
                <p><?php echo htmlspecialchars($error); ?></p>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <strong>⚠️ Please fix the following errors:</strong>
                <ul style="margin-top: 0.5rem; padding-left: 1.5rem;">
                    <?php foreach ($errors as $err): ?>
                        <li><?php echo htmlspecialchars($err); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <!-- Profile Layout -->
        <div class="profile-layout fade-in">
            <!-- Sidebar -->
            <div class="profile-sidebar">
                <!-- User Card -->
                <div class="user-card">
                    <div class="user-avatar">
                        <?php 
                        $initials = '';
                        if (!empty($user['first_name'])) {
                            $initials = strtoupper(substr($user['first_name'], 0, 1));
                            if (!empty($user['last_name'])) {
                                $initials .= strtoupper(substr($user['last_name'], 0, 1));
                            }
                        } else {
                            $initials = strtoupper(substr($user['username'], 0, 2));
                        }
                        echo $initials;
                        ?>
                    </div>
                    <div class="user-name">
                        <?php echo htmlspecialchars($user['first_name'] ? $user['first_name'] . ' ' . $user['last_name'] : $user['username']); ?>
                    </div>
                    <div class="user-email">
                        <?php echo htmlspecialchars($user['email']); ?>
                    </div>
                    <div class="account-stats">
                        <div class="stat-item">
                            <div class="stat-value">
                                <?php echo date('M d', strtotime($user['created_at'])); ?>
                            </div>
                            <div class="stat-label">Joined</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value">
                                <?php echo date('M d', strtotime($user['last_login'] ?? $user['created_at'])); ?>
                            </div>
                            <div class="stat-label">Last Login</div>
                        </div>
                    </div>
                </div>
                
                <!-- Navigation -->
                <div class="profile-nav">
                    <div class="nav-item active" onclick="showSection('profile')">
                        <span>👤</span>
                        <span>Profile Information</span>
                    </div>
                    <div class="nav-item" onclick="window.location.href='dashboard.php'">
                        <span>🏠</span>
                        <span>Back to Dashboard</span>
                    </div>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="profile-content">
                <!-- Profile Information -->
                <div class="section" id="profileSection">
                    <div class="section-header">
                        <h2>Profile Information</h2>
                        <button class="btn" onclick="document.getElementById('profileForm').submit()">
                            Save Changes
                        </button>
                    </div>
                    <form id="profileForm" method="POST">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="first_name">First Name</label>
                                <input type="text" id="first_name" name="first_name" 
                                       value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>"
                                       placeholder="John">
                            </div>
                            <div class="form-group">
                                <label for="last_name">Last Name</label>
                                <input type="text" id="last_name" name="last_name" 
                                       value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>"
                                       placeholder="Doe">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" required
                                   value="<?php echo htmlspecialchars($user['email']); ?>"
                                   placeholder="john@ashesi.edu.gh">
                            <small style="color: var(--text-secondary); font-size: 0.9rem; display: block; margin-top: 0.5rem;">
                                Must be a valid Ashesi University email
                            </small>
                        </div>
                        
                        <div class="form-group">
                            <label for="university">University</label>
                            <select id="university" name="university">
                                <option value="Ashesi University" <?php echo ($user['university'] ?? '') === 'Ashesi University' ? 'selected' : ''; ?>>Ashesi University</option>
                                <option value="University of Ghana" <?php echo ($user['university'] ?? '') === 'University of Ghana' ? 'selected' : ''; ?>>University of Ghana</option>
                                <option value="KNUST" <?php echo ($user['university'] ?? '') === 'KNUST' ? 'selected' : ''; ?>>KNUST</option>
                                <option value="University of Cape Coast" <?php echo ($user['university'] ?? '') === 'University of Cape Coast' ? 'selected' : ''; ?>>University of Cape Coast</option>
                                <option value="Other" <?php echo ($user['university'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="username">Username (Read-only)</label>
                            <input type="text" id="username" 
                                   value="<?php echo htmlspecialchars($user['username']); ?>"
                                   readonly
                                   style="background: var(--card-bg); cursor: not-allowed;">
                            <small style="color: var(--text-secondary); font-size: 0.9rem; display: block; margin-top: 0.5rem;">
                                To change your username, go to "Change Username" section
                            </small>
                        </div>
                    </form>
                </div>
                
                <!-- End of Content -->
            </div>
        </div>
    </div>

    <script>
        // ============ SECTION NAVIGATION ============
        function showSection(sectionId) {
            // Hide all sections
            document.querySelectorAll('.section').forEach(section => {
                section.style.display = 'none';
            });
            
            // Show selected section
            document.getElementById(sectionId + 'Section').style.display = 'block';
            
            // Update active nav item
            document.querySelectorAll('.nav-item').forEach(item => {
                item.classList.remove('active');
            });
            
            // Find and activate the clicked nav item
            document.querySelectorAll('.nav-item').forEach(item => {
                if (item.textContent.toLowerCase().includes(sectionId)) {
                    item.classList.add('active');
                }
            });
        }
        
        // ============ PASSWORD STRENGTH ============
        function checkPasswordStrength(password) {
            const strengthFill = document.getElementById('passwordStrengthFill');
            let strength = 0;
            
            if (password.length >= 8) strength += 25;
            if (password.length >= 12) strength += 15;
            if (/[A-Z]/.test(password)) strength += 20;
            if (/[a-z]/.test(password)) strength += 20;
            if (/[0-9]/.test(password)) strength += 20;
            
            strength = Math.min(strength, 100);
            if (strengthFill) {
                strengthFill.style.width = strength + '%';
                
                if (strength < 40) {
                    strengthFill.style.background = 'var(--error)';
                } else if (strength < 70) {
                    strengthFill.style.background = 'var(--warning)';
                } else {
                    strengthFill.style.background = 'var(--success)';
                }
            }
        }
        
        // ============ CONFIRM DEACTIVATION ============
        function confirmDeactivation() {
            return confirm('⚠️ WARNING: This will deactivate your account.\n\nYou will be logged out immediately and will not be able to access your account until you contact support to reactivate it.\n\nAre you absolutely sure?');
        }
        
        // ============ FORM VALIDATION ============
        document.addEventListener('DOMContentLoaded', () => {
            // Form validation
        });
    </script>
    <script src="public/js/theme-manager.js"></script>
</body>
</html>