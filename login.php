<?php
// login.php - WITH AJAX SUPPORT & ADMIN ROLE REDIRECT
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session FIRST
session_start();

// Include required files
require_once 'config/database.php';
require_once 'includes/auth.php';

// Init
$db = new Database();
$pdo = $db->getPDO();
$auth = new Auth($pdo);

// Check if already logged in
if ($auth->isLoggedIn()) {
    // Get current user and redirect based on role
    $user = $auth->getCurrentUser();
    $role = $user['role'] ?? 'student';
    
    if ($role === 'admin') {
        header('Location: admin.php');
    } else {
        header('Location: dashboard.php');
    }
    exit();
}

// Handle AJAX login request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    
    // Get POST data
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    // Simple validation
    if (empty($username) || empty($password)) {
        echo json_encode([
            'success' => false,
            'error' => 'Please enter both username and password'
        ]);
        exit();
    }
    
    // Attempt login
    $result = $auth->login($username, $password, $remember);
    
    // Add redirect URL for admin vs student
    if ($result['success']) {
        $role = $result['user']['role'] ?? 'student';
        $result['redirect'] = $role === 'admin' ? 'admin.php' : 'dashboard.php';
    }
    
    // Return JSON response
    echo json_encode($result);
    exit();
}


// For traditional form submission (fallback)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['ajax'])) {
    // CSRF Check
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Security validation failed";
    } else {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);
        
        if (!empty($username) && !empty($password)) {
            $result = $auth->login($username, $password, $remember);
            
            if ($result['success']) {
                $role = $result['user']['role'] ?? 'student';
                $_SESSION['user_role'] = $role; // Just to be super sure
                
                if ($role === 'admin') {
                    header('Location: admin.php');
                } else {
                    header('Location: dashboard.php');
                }
                exit();
            } else {
                $error = $result['error'];
            }
        } else {
            $error = "Please enter both username and password";
        }
    }
}

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Initialize error variable for display
$error = $error ?? '';
?>

<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Elm Finance</title>
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
            overflow: hidden;
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
            max-width: 440px;
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
        
        input { 
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
        
        input::placeholder {
            color: var(--text-secondary);
            opacity: 0.7;
            font-weight: 300;
        }
        
        input:focus {
            outline: none;
            border-color: var(--accent-primary);
            background: rgba(0, 255, 136, 0.02);
        }
        
        [data-theme="dark"] input:focus {
            box-shadow: 
                0 0 20px rgba(0, 255, 136, 0.2),
                inset 0 0 0 1px rgba(0, 255, 136, 0.1);
        }
        
        [data-theme="light"] input:focus {
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
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            margin-bottom: 2rem;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: auto;
            transform: scale(1.2);
        }
        
        .checkbox-group label {
            margin-bottom: 0;
            font-size: 1.1rem;
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
        
        /* Spinner */
        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: var(--accent-primary);
            animation: spin 1s ease-in-out infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Links */
        .links { 
            text-align: center; 
            margin-top: 2.5rem;
            color: var(--text-secondary);
            font-size: 1.1rem;
            font-weight: 300;
        }
        
        .links a { 
            color: var(--accent-primary); 
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        [data-theme="dark"] .links a {
            text-shadow: 0 0 10px rgba(0, 255, 136, 0.3);
        }
        
        .links a:hover {
            color: var(--accent-secondary);
        }
        
        [data-theme="dark"] .links a:hover {
            text-shadow: 0 0 15px rgba(0, 204, 102, 0.5);
        }
        
        .security-notice {
            font-size: 1rem;
            color: var(--text-secondary);
            text-align: center;
            margin-top: 1.5rem;
            opacity: 0.7;
            font-weight: 300;
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
            
            input {
                padding: 1.2rem 1rem 1.2rem 3.5rem;
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
            <p>Access your financial dashboard</p>
        </div>
        
        <div id="loginAlert" style="display: none;">
            <!-- AJAX alerts will be inserted here -->
        </div>
        
        <?php if ($error): ?>
            <div class="alert error">
                <strong>⚠️ Authentication Failed</strong>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="" id="loginForm">
            <input type="hidden" name="csrf_token" id="csrfToken" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <div class="form-group">
                <label for="username">Username or Email</label>
                <div class="input-wrapper">
                    <div class="input-icon">👤</div>
                    <input type="text" id="username" name="username" required 
                           placeholder="johndoe or john@ashesi.edu.gh"
                           autocomplete="username">
                </div>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-wrapper">
                    <div class="input-icon">🔒</div>
                    <input type="password" id="password" name="password" required
                           placeholder="••••••••"
                           autocomplete="current-password">
                </div>
            </div>
            
            <div class="checkbox-group">
                <input type="checkbox" id="remember" name="remember">
                <label for="remember">Keep me logged in for 7 days</label>
            </div>
            
            <button type="submit" class="btn" id="submitBtn">
                <span id="btnText">Login</span>
                <span id="btnSpinner" style="display: none;" class="loading-spinner"></span>
            </button>
        </form>
        
        <div class="security-notice">
            🔒 Protected by rate limiting & CSRF tokens
        </div>
        
        <div class="links">
            New to Elm? <a href="register.php">Create your account</a><br>
            <a href="forgot-password.php" style="font-size: 1rem; margin-top: 1rem; display: inline-block;">Forgot your password?</a>
        </div>
    </div>

    <script>
        // Theme
        function loadTheme() {
            const savedTheme = localStorage.getItem('theme') || 'dark';
            const html = document.documentElement;
            const themeIcon = document.getElementById('themeIcon');
            
            html.setAttribute('data-theme', savedTheme);
            
            if (themeIcon) {
                themeIcon.textContent = savedTheme === 'dark' ? '🌙' : '☀️';
            }
        }
        
        function toggleTheme() {
            const html = document.documentElement;
            const currentTheme = html.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            const themeIcon = document.getElementById('themeIcon');
            
            html.setAttribute('data-theme', newTheme);
            
            if (themeIcon) {
                themeIcon.textContent = newTheme === 'dark' ? '🌙' : '☀️';
            }
            
            localStorage.setItem('theme', newTheme);
        }
        
        // Login
document.addEventListener('DOMContentLoaded', () => {
    loadTheme();
    
    // Add theme toggle button listener
    const themeToggle = document.getElementById('themeToggle');
    if (themeToggle) {
        themeToggle.addEventListener('click', toggleTheme);
    }
    
    // AJAX form submission
    const loginForm = document.getElementById('loginForm');
    const submitBtn = document.getElementById('submitBtn');
    const btnText = document.getElementById('btnText');
    const btnSpinner = document.getElementById('btnSpinner');
    const loginAlert = document.getElementById('loginAlert');
    
    loginForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        // Get form data as FormData (NOT JSON)
        const formData = new FormData(loginForm);
        formData.append('ajax', 'true'); // Add ajax flag
        
        // Show loading state
        submitBtn.disabled = true;
        btnText.textContent = 'Logging in...';
        btnSpinner.style.display = 'inline-block';
        
        try {
            // Send AJAX request - USE FormData NOT JSON
            const response = await fetch('login.php', {
                method: 'POST',
                body: formData  // ← THIS IS THE FIX: Send FormData directly
                // NO headers needed - browser sets them automatically for FormData
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.success) {
    // Show success
    loginAlert.innerHTML = `
        <div class="alert success">
            <strong>✓ Success</strong>
            ${data.message || 'Login successful! Redirecting...'}
        </div>
    `;
    loginAlert.style.display = 'block';
    
    // Update button to show success
    btnText.textContent = '✓ Success!';
    submitBtn.style.background = 'var(--success)';
    
    // Use redirect URL from server response (admin or student)
    const redirectUrl = data.redirect || 'dashboard.php';
    
    // Redirect after delay
    setTimeout(() => {
        window.location.href = redirectUrl;
    }, 1000);
}
                
            } else {
                loginAlert.innerHTML = `
                    <div class="alert error">
                        <strong>⚠️ Error</strong>
                        ${data.error || 'Login failed'}
                    </div>
                `;
                loginAlert.style.display = 'block';
                resetButton();
            }
            
        } catch (error) {
            console.error('Login error:', error);
            loginAlert.innerHTML = `
                <div class="alert error">
                    <strong>⚠️ Network Error</strong>
                    Please check your internet connection and try again.<br>
                    <small>Error: ${error.message}</small>
                </div>
            `;
            loginAlert.style.display = 'block';
            resetButton();
        }
    });
    
    function resetButton() {
        submitBtn.disabled = false;
        btnText.textContent = 'Login';
        btnSpinner.style.display = 'none';
    }
    
    // Input focus effects (keep existing)
    const inputs = document.querySelectorAll('input');
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
    
    // Add floating animation to logo
    const logo = document.querySelector('.logo');
    setInterval(() => {
        logo.classList.toggle('float');
    }, 3000);
});
        
        // Listen for theme changes from other tabs/windows
        window.addEventListener('storage', (e) => {
            if (e.key === 'theme') {
                loadTheme();
            }
        });
    </script>

</body>
</html>