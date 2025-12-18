<?php
require_once 'config/database.php';

// Start session for CSRF protection
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Security: Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Security validation failed";
    } else {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $university = trim($_POST['university'] ?? 'Ashesi University');
        
        // Enhanced validation
        $errors = [];
        
        if (empty($username) || !preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
            $errors[] = "Username: 3-20 characters (letters, numbers, underscores)";
        }
        
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Valid email required";
        }
        
        if (empty($password) || strlen($password) < 8) {
            $errors[] = "Password must be at least 8 characters";
        }
        
        if (!empty($first_name) && !preg_match('/^[a-zA-Z\s]{1,50}$/', $first_name)) {
            $errors[] = "First name contains invalid characters";
        }
        
        if (empty($errors)) {
            try {
                // Check if user exists
                $stmt = $pdo->prepare("SELECT id FROM elm_users WHERE username = ? OR email = ?");
                $stmt->execute([$username, $email]);
                
                if ($stmt->rowCount() > 0) {
                    $error = "Username or email already exists";
                } else {
                    // Create user with prepared statement
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO elm_users (username, email, password_hash, first_name, last_name, university) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$username, $email, $hashed_password, $first_name, $last_name, $university]);
                    
                    $success = "Account created successfully! You can now login.";
                    // Clear form
                    $_POST = [];
                }
            } catch (Exception $e) {
                $error = "Registration failed. Please try again.";
                error_log("Registration error: " . $e->getMessage());
            }
        } else {
            $error = implode("<br>", $errors);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Elm Finance</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* Theme Variables */
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
            --neon-glow: 0 0 20px rgba(0, 255, 136, 0.3);
            --input-bg: rgba(255, 255, 255, 0.05);
            --input-border: rgba(255, 255, 255, 0.1);
            --neon-cyan: #00ffff;
            --neon-purple: #b967ff;
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
            --neon-glow: 0 0 15px rgba(0, 204, 102, 0.08);
            --input-bg: rgba(0, 0, 0, 0.03);
            --input-border: rgba(0, 0, 0, 0.08);
            --neon-cyan: #00a8a8;
            --neon-purple: #8a4fff;
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
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; 
            font-weight: 300;
            font-size: 1.1rem;
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            color: var(--text-primary);
            transition: background-color 0.3s ease, color 0.3s ease;
            position: relative;
            overflow-y: auto;
            overflow-x: hidden;
        }
        
        /* Dark mode background */
        [data-theme="dark"] body {
            background: 
                radial-gradient(circle at 20% 50%, rgba(0, 255, 136, 0.05) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(0, 255, 136, 0.03) 0%, transparent 50%),
                var(--bg-primary);
        }
        
        [data-theme="dark"] body::before {
            content: '';
            position: absolute;
            width: 400px;
            height: 400px;
            background: var(--accent-primary);
            filter: blur(100px);
            opacity: 0.05;
            border-radius: 50%;
            top: -100px;
            right: -100px;
            z-index: 0;
        }
        
        /* Light mode background */
        [data-theme="light"] body {
            background: 
                radial-gradient(circle at 20% 80%, rgba(0, 204, 102, 0.03) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(0, 168, 84, 0.02) 0%, transparent 50%),
                var(--bg-primary);
        }
        
        /* Glassmorphism */
        .glass-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            box-shadow: var(--glass-shadow);
        }
        
        /* Container */
        .container { 
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            padding: 4rem 3rem;
            width: 100%;
            max-width: 480px;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
            z-index: 1;
            box-shadow: var(--glass-shadow);
        }
        
        .container::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(0, 255, 136, 0.1), transparent);
            transition: left 0.6s ease;
        }
        
        .container:hover::before {
            left: 100%;
        }
        
        /* Logo */
        .logo { 
            text-align: center; 
            margin-bottom: 3rem; 
        }
        
        .logo h1 {
            background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 2.8rem;
            font-weight: 700;
            margin-bottom: 0.8rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }
        
        .logo-icon {
            width: 32px;
            height: 32px;
            color: var(--accent-primary);
        }
        
        .logo p {
            color: var(--text-secondary);
            font-size: 1.1rem;
            font-weight: 300;
        }
        
        /* Form */
        .form-group { 
            margin-bottom: 2rem; 
            position: relative;
        }
        
        label { 
            display: block; 
            margin-bottom: 0.8rem; 
            font-weight: 400;
            color: var(--text-primary);
            font-size: 1.1rem;
        }
        
        .input-wrapper {
            position: relative;
        }
        
        input, select { 
            width: 100%; 
            padding: 1.2rem 1rem 1.2rem 4rem; 
            border: 1px solid var(--input-border);
            border-radius: 12px;
            font-size: 1.1rem;
            background: var(--input-bg);
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
            color: var(--text-primary);
            font-weight: 300;
        }
        
        select {
            padding-left: 1.2rem;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2300ff88' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 1.2rem;
            padding-right: 3rem;
            cursor: pointer;
        }
        
        [data-theme="light"] select {
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2300cc66' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
        }
        
        input::placeholder {
            color: var(--text-secondary);
            opacity: 0.7;
            font-weight: 300;
        }
        
        input:focus, select:focus {
            outline: none;
            border-color: var(--accent-primary);
            background: rgba(0, 255, 136, 0.02);
        }
        
        [data-theme="dark"] input:focus, [data-theme="dark"] select:focus {
            box-shadow: 
                0 0 20px rgba(0, 255, 136, 0.2),
                inset 0 0 0 1px rgba(0, 255, 136, 0.1);
        }
        
        [data-theme="light"] input:focus, [data-theme="light"] select:focus {
            box-shadow: 
                0 0 15px rgba(0, 204, 102, 0.15),
                inset 0 0 0 1px rgba(0, 204, 102, 0.1);
            background: rgba(0, 204, 102, 0.02);
        }
        
        .input-icon {
            position: absolute;
            left: 1.5rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--accent-primary);
            z-index: 2;
            font-size: 1.4rem;
        }
        
        .password-strength {
            margin-top: 0.8rem;
            font-size: 1rem;
            color: var(--text-secondary);
            font-weight: 300;
        }
        
        /* Buttons */
        .btn { 
            width: 100%; 
            padding: 1.2rem 2.4rem;
            background: var(--accent-primary);
            color: #0a0a0a;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-family: 'Poppins', sans-serif;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.8rem;
            margin-top: 1rem;
        }
        
        .btn::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.6s ease;
        }
        
        .btn:hover::after {
            left: 100%;
        }
        
        .btn:hover { 
            transform: translateY(-2px);
        }
        
        [data-theme="dark"] .btn {
            box-shadow: 0 4px 20px rgba(0, 255, 136, 0.3);
        }
        
        [data-theme="dark"] .btn:hover {
            box-shadow: 0 8px 30px rgba(0, 255, 136, 0.4);
        }
        
        [data-theme="light"] .btn {
            box-shadow: 0 4px 20px rgba(0, 204, 102, 0.2);
            color: white;
        }
        
        [data-theme="light"] .btn:hover {
            box-shadow: 0 8px 30px rgba(0, 204, 102, 0.3);
        }
        
        .btn:active {
            transform: translateY(0);
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
        
        .error { 
            background: rgba(255, 68, 68, 0.1); 
            color: var(--error);
            border-color: rgba(255, 68, 68, 0.3);
        }
        
        .success { 
            background: rgba(0, 255, 136, 0.1); 
            color: var(--success);
            border-color: rgba(0, 255, 136, 0.3);
        }
        
        [data-theme="dark"] .error {
            box-shadow: 0 0 20px rgba(255, 68, 68, 0.1);
        }
        
        [data-theme="dark"] .success {
            box-shadow: 0 0 20px rgba(0, 255, 136, 0.1);
        }
        
        .alert strong {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        /* Links */
        .login-link { 
            text-align: center; 
            margin-top: 2.5rem;
            color: var(--text-secondary);
            font-size: 1.1rem;
            font-weight: 300;
        }
        
        .login-link a { 
            color: var(--accent-primary); 
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        [data-theme="dark"] .login-link a {
            text-shadow: 0 0 10px rgba(0, 255, 136, 0.3);
        }
        
        .login-link a:hover {
            color: var(--accent-secondary);
        }
        
        [data-theme="dark"] .login-link a:hover {
            text-shadow: 0 0 15px rgba(0, 204, 102, 0.5);
        }
        
        /* Theme toggle */
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
        
        [data-theme="dark"] .theme-toggle:hover {
            box-shadow: 0 0 15px rgba(0, 255, 136, 0.3);
        }
        
        [data-theme="light"] .theme-toggle:hover {
            box-shadow: 0 0 10px rgba(0, 204, 102, 0.2);
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
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-5px); }
        }
        
        .float {
            animation: float 3s ease-in-out infinite;
        }
        
        .shake {
            animation: shake 0.5s ease-in-out;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .animate-fadeInUp {
            animation: fadeInUp 0.6s ease-out forwards;
        }
        
        /* Password Strength */
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
        
        /* Responsive */
        @media (max-width: 768px) {
            html {
                font-size: 55%;
            }
            
            .container {
                padding: 3rem 2rem;
                margin: 0 1rem;
            }
            
            .theme-toggle {
                top: 1.5rem;
                right: 1.5rem;
            }
        }
        
        @media (max-width: 480px) {
            html {
                font-size: 50%;
            }
            
            .logo h1 {
                font-size: 2.4rem;
            }
            
            .container {
                padding: 2.5rem 1.5rem;
            }
            
            input, select {
                padding: 1.2rem 1rem 1.2rem 3.5rem;
            }
            
            .form-group {
                margin-bottom: 1.5rem;
            }
           
        @media (min-height: 800px) {
            body {
                padding-top: 2rem;
                padding-bottom: 2rem;
            }
        }


                    .container {
                        max-height: 90vh;
                        overflow-y: auto;
                        /* Hide scrollbar but keep functionality */
                        scrollbar-width: thin;
                        scrollbar-color: var(--accent-primary) transparent;
                    }

            .container::-webkit-scrollbar {
                width: 6px;
            }

            .container::-webkit-scrollbar-track {
                background: transparent;
            }

            .container::-webkit-scrollbar-thumb {
                background: var(--accent-primary);
                border-radius: 3px;
            }
        }
    </style>
</head>
<body>
    <!-- Theme Toggle Button -->
    <button class="theme-toggle" id="themeToggle">
        <span id="themeIcon">🌙</span>
    </button>

    <div class="container glass-card animate-fadeInUp">
        <div class="logo float">
            <h1>
                <svg class="logo-icon" viewBox="0 0 24 24">
                    <path d="M12 4 L16 8 L14 16 L10 18 L6 16 L8 8 Z" fill="currentColor"/>
                </svg>
                Elm Finance
            </h1>
            <p>Create your student financial dashboard</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert error">
                <strong>⚠️ Registration Failed</strong>
                <div><?php echo $error; ?></div>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert success">
                <strong>✓ Account Created</strong>
                <div><?php echo $success; ?></div>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="" id="registerForm">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <div class="form-group">
                <label for="username">Username</label>
                <div class="input-wrapper">
                    <div class="input-icon">👤</div>
                    <input type="text" id="username" name="username" required 
                           placeholder="johndoe"
                           value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                           pattern="[a-zA-Z0-9_]{3,20}"
                           title="3-20 characters, letters, numbers, underscores only"
                           autocomplete="username">
                </div>
            </div>
            
            <div class="form-group">
                <label for="email">University Email</label>
                <div class="input-wrapper">
                    <div class="input-icon"></div>
                    <input type="email" id="email" name="email" required
                           placeholder="john@ashesi.edu.gh"
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                           autocomplete="email">
                </div>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-wrapper">
                    <div class="input-icon"></div>
                    <input type="password" id="password" name="password" required
                           placeholder="••••••••"
                           minlength="8"
                           autocomplete="new-password">
                </div>
                <div class="password-strength">Minimum 8 characters</div>
                <div class="strength-meter">
                    <div class="strength-fill" id="strengthFill"></div>
                </div>
            </div>
            
            <div class="form-group">
                <label for="first_name">First Name</label>
                <div class="input-wrapper">
                    <div class="input-icon"></div>
                    <input type="text" id="first_name" name="first_name"
                           placeholder="John (optional)"
                           value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>"
                           autocomplete="given-name">
                </div>
            </div>
            
            <div class="form-group">
                <label for="last_name">Last Name</label>
                <div class="input-wrapper">
                    <div class="input-icon"></div>
                    <input type="text" id="last_name" name="last_name"
                           placeholder="Doe (optional)"
                           value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>"
                           autocomplete="family-name">
                </div>
            </div>
            
            <div class="form-group">
                <label for="university">University</label>
                <div class="input-wrapper">
                    <div class="input-icon"></div>
                    <select id="university" name="university" required>
                        <option value="Ashesi University" <?php echo ($_POST['university'] ?? 'Ashesi University') === 'Ashesi University' ? 'selected' : ''; ?>>Ashesi University</option>
                        <option value="University of Ghana" <?php echo ($_POST['university'] ?? '') === 'University of Ghana' ? 'selected' : ''; ?>>University of Ghana</option>
                        <option value="KNUST" <?php echo ($_POST['university'] ?? '') === 'KNUST' ? 'selected' : ''; ?>>KNUST</option>
                        <option value="University of Cape Coast" <?php echo ($_POST['university'] ?? '') === 'University of Cape Coast' ? 'selected' : ''; ?>>University of Cape Coast</option>
                        <option value="Other" <?php echo ($_POST['university'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>
            </div>
            
            <button type="submit" class="btn" id="submitBtn">
                <span id="btnText">Create Account</span>
            </button>
        </form>
        
        <div class="login-link">
            Already part of Elm? <a href="login.php">Login</a>
        </div>
    </div>

    <script>
        // Theme
        // Load saved theme from localStorage (persists across all pages)
        function loadTheme() {
            const savedTheme = localStorage.getItem('theme') || 'dark';
            const html = document.documentElement;
            const themeIcon = document.getElementById('themeIcon');
            
            // Apply theme to HTML element
            html.setAttribute('data-theme', savedTheme);
            
            // Update theme toggle icon
            if (themeIcon) {
                themeIcon.textContent = savedTheme === 'dark' ? '🌙' : '☀️';
            }
        }
        
        // Toggle theme function
        function toggleTheme() {
            const html = document.documentElement;
            const currentTheme = html.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            const themeIcon = document.getElementById('themeIcon');
            
            // Apply new theme
            html.setAttribute('data-theme', newTheme);
            
            // Update icon
            if (themeIcon) {
                themeIcon.textContent = newTheme === 'dark' ? '🌙' : '☀️';
            }
            
            // Save to localStorage (this makes it persist across ALL pages)
            localStorage.setItem('theme', newTheme);
            
            // Add transition class for smooth change
            html.classList.add('theme-transition');
            setTimeout(() => {
                html.classList.remove('theme-transition');
            }, 300);
        }
        
        // Initialize theme on page load
        document.addEventListener('DOMContentLoaded', () => {
            loadTheme();
            
            // Add theme toggle button listener
            const themeToggle = document.getElementById('themeToggle');
            if (themeToggle) {
                themeToggle.addEventListener('click', toggleTheme);
            }
        });
        
        // Listen for theme changes from other tabs/windows
        window.addEventListener('storage', (e) => {
            if (e.key === 'theme') {
                loadTheme();
            }
        });
        
        // ============ PASSWORD STRENGTH METER ============
        const passwordInput = document.getElementById('password');
        const strengthFill = document.getElementById('strengthFill');
        
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            
            // Length check
            if (password.length >= 8) strength += 25;
            if (password.length >= 12) strength += 15;
            
            // Complexity checks
            if (/[A-Z]/.test(password)) strength += 20;
            if (/[a-z]/.test(password)) strength += 20;
            if (/[0-9]/.test(password)) strength += 20;
            if (/[^A-Za-z0-9]/.test(password)) strength += 20;
            
            // Cap at 100
            strength = Math.min(strength, 100);
            
            // Update visual
            strengthFill.style.width = strength + '%';
            
            // Update color based on strength
            if (strength < 40) {
                strengthFill.style.background = 'var(--error)';
            } else if (strength < 70) {
                strengthFill.style.background = 'var(--warning)';
            } else {
                strengthFill.style.background = 'var(--success)';
            }
        });
        
        // ============ FORM VALIDATION ============
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const submitBtn = document.getElementById('submitBtn');
            const btnText = document.getElementById('btnText');
            
            if (password.length < 8) {
                e.preventDefault();
                passwordInput.style.borderColor = 'var(--error)';
                passwordInput.style.boxShadow = '0 0 20px rgba(255, 68, 68, 0.3)';
                
                setTimeout(() => {
                    passwordInput.style.borderColor = '';
                    passwordInput.style.boxShadow = '';
                }, 2000);
            } else {
                // Add loading state
                btnText.textContent = 'Creating Account...';
                submitBtn.style.opacity = '0.8';
                submitBtn.disabled = true;
            }
        });
        
        // ============ INPUT VALIDATION ============
        const usernameInput = document.getElementById('username');
        
        usernameInput.addEventListener('input', function() {
            const isValid = /^[a-zA-Z0-9_]{3,20}$/.test(this.value);
            if (this.value && !isValid) {
                this.style.borderColor = 'var(--error)';
            } else {
                this.style.borderColor = '';
            }
        });
        
        // Input focus effects
        const inputs = document.querySelectorAll('input, select');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                const theme = document.documentElement.getAttribute('data-theme');
                if (theme === 'dark') {
                    this.style.boxShadow = '0 0 25px rgba(0, 255, 136, 0.3)';
                } else {
                    this.style.boxShadow = '0 0 20px rgba(0, 204, 102, 0.2)';
                }
            });
            
            input.addEventListener('blur', function() {
                this.style.boxShadow = '';
            });
        });
        
        // Enter key submission
        document.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                document.getElementById('registerForm').dispatchEvent(new Event('submit'));
            }
        });
        
        // Add floating animation to logo
        const logo = document.querySelector('.logo');
        setInterval(() => {
            logo.classList.toggle('float');
        }, 3000);
    </script>
</body>
</html>