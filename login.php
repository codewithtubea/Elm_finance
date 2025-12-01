<?php
// Configure session FIRST, before starting it
require_once 'config/database.php';
require_once 'includes/security.php';

// Configure session settings BEFORE session starts
Security::configureSession();

// Now start the session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
    Security::regenerateSession();
}

// Now include Auth class (after session is started)
require_once 'includes/auth.php';

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = Security::generateToken();
}

// Check if user is already logged in
$auth = new Auth($pdo);
if ($auth->isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Security validation failed";
    } else {
        $username = Validator::sanitizeInput($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);
        
        if (empty($username) || empty($password)) {
            $error = "Please enter both username and password";
        } else {
            $result = $auth->login($username, $password, $remember);
            
            if ($result['success']) {
                $success = "Login successful! Redirecting...";
                
                // Redirect to dashboard after 1 second
                header("Refresh: 1; url=dashboard.php");
            } else {
                $error = $result['error'];
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Elm Finance</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --neon-green: #00ff88;
            --neon-cyan: #00ffff;
            --neon-purple: #b967ff;
            --dark-bg: #0a0a0a;
            --card-bg: rgba(15, 15, 15, 0.8);
            --card-border: rgba(0, 255, 136, 0.2);
            --text-primary: #ffffff;
            --text-secondary: #a0a0a0;
            --error: #ff4444;
            --success: #00ff88;
        }
        
        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
        }
        
        body { 
            font-family: 'Inter', sans-serif; 
            background: 
                radial-gradient(circle at 20% 80%, rgba(0, 255, 136, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(0, 255, 255, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(185, 103, 255, 0.05) 0%, transparent 50%),
                var(--dark-bg);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            color: var(--text-primary);
        }
        
        .container { 
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--card-border);
            padding: 3rem 2.5rem;
            border-radius: 24px;
            box-shadow: 
                0 0 50px rgba(0, 255, 136, 0.1),
                0 0 0 1px rgba(0, 255, 136, 0.1),
                inset 0 0 0 1px rgba(255, 255, 255, 0.05);
            width: 100%;
            max-width: 440px;
            position: relative;
            overflow: hidden;
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
        
        .logo { 
            text-align: center; 
            margin-bottom: 2.5rem; 
        }
        
        .logo h1 {
            background: linear-gradient(135deg, var(--neon-green), var(--neon-cyan));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            text-shadow: 0 0 30px rgba(0, 255, 136, 0.3);
        }
        
        .logo p {
            color: var(--text-secondary);
            font-size: 0.95rem;
            letter-spacing: 0.5px;
        }
        
        .form-group { 
            margin-bottom: 1.5rem; 
            position: relative;
        }
        
        label { 
            display: block; 
            margin-bottom: 0.5rem; 
            font-weight: 500;
            color: var(--text-primary);
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .input-wrapper {
            position: relative;
        }
        
        input { 
            width: 100%; 
            padding: 1rem 1rem 1rem 3rem; 
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            font-size: 1rem;
            background: rgba(255, 255, 255, 0.05);
            transition: all 0.3s ease;
            font-family: 'Inter', sans-serif;
            color: var(--text-primary);
        }
        
        input::placeholder {
            color: var(--text-secondary);
        }
        
        input:focus {
            outline: none;
            border-color: var(--neon-green);
            box-shadow: 
                0 0 20px rgba(0, 255, 136, 0.2),
                inset 0 0 0 1px rgba(0, 255, 136, 0.1);
            background: rgba(0, 255, 136, 0.02);
        }
        
        .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--neon-green);
            z-index: 2;
            filter: drop-shadow(0 0 5px var(--neon-green));
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: auto;
            transform: scale(1.2);
        }
        
        .checkbox-group label {
            margin-bottom: 0;
            text-transform: none;
            font-size: 0.9rem;
        }
        
        .btn { 
            width: 100%; 
            padding: 1rem; 
            background: linear-gradient(135deg, var(--neon-green), var(--neon-cyan));
            color: var(--dark-bg);
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Inter', sans-serif;
            position: relative;
            overflow: hidden;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s ease;
        }
        
        .btn:hover::before {
            left: 100%;
        }
        
        .btn:hover { 
            transform: translateY(-2px);
            box-shadow: 
                0 10px 30px rgba(0, 255, 136, 0.3),
                0 0 0 1px rgba(0, 255, 136, 0.2);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        .alert { 
            padding: 1rem 1.5rem; 
            border-radius: 12px;
            margin-bottom: 1.5rem;
            border: 1px solid;
            animation: slideDown 0.3s ease;
            backdrop-filter: blur(10px);
        }
        
        .error { 
            background: rgba(255, 68, 68, 0.1); 
            color: var(--error);
            border-color: rgba(255, 68, 68, 0.3);
            box-shadow: 0 0 20px rgba(255, 68, 68, 0.1);
        }
        
        .success { 
            background: rgba(0, 255, 136, 0.1); 
            color: var(--success);
            border-color: rgba(0, 255, 136, 0.3);
            box-shadow: 0 0 20px rgba(0, 255, 136, 0.1);
        }
        
        .links { 
            text-align: center; 
            margin-top: 2rem;
            color: var(--text-secondary);
            font-size: 0.9rem;
        }
        
        .links a { 
            color: var(--neon-cyan); 
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            text-shadow: 0 0 10px rgba(0, 255, 255, 0.3);
        }
        
        .links a:hover {
            color: var(--neon-green);
            text-shadow: 0 0 15px rgba(0, 255, 136, 0.5);
        }
        
        .security-notice {
            font-size: 0.75rem;
            color: var(--text-secondary);
            text-align: center;
            margin-top: 1rem;
            opacity: 0.7;
        }
        
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
        
        @keyframes glow {
            0%, 100% { box-shadow: 0 0 20px rgba(0, 255, 136, 0.2); }
            50% { box-shadow: 0 0 30px rgba(0, 255, 136, 0.4); }
        }
        
        .glow {
            animation: glow 2s ease-in-out infinite;
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
    </style>
</head>
<body>
    <div class="container glow">
        <div class="logo float">
            <h1>🌿 Elm Finance</h1>
            <p>Access your financial dashboard</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="" id="loginForm">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <div class="form-group">
                <label for="username">Username or Email</label>
                <div class="input-wrapper">
                    <div class="input-icon">👤</div>
                    <input type="text" id="username" name="username" required 
                           placeholder="johndoe or john@ashesi.edu.gh"
                           value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
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
            </button>
        </form>
        
        <div class="security-notice">
            🔒 Protected by rate limiting & CSRF tokens
        </div>
        
        <div class="links">
            New to Elm? <a href="register.php">Create your account</a><br>
            <a href="forgot-password.php" style="font-size: 0.8rem; margin-top: 0.5rem; display: inline-block;">Forgot your password?</a>
        </div>
    </div>

    <script>
        // Real-time form validation
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;
            const submitBtn = document.getElementById('submitBtn');
            const btnText = document.getElementById('btnText');
            
            if (!username || !password) {
                e.preventDefault();
                
                if (!username) {
                    document.getElementById('username').classList.add('shake');
                    setTimeout(() => {
                        document.getElementById('username').classList.remove('shake');
                    }, 500);
                }
                
                if (!password) {
                    document.getElementById('password').classList.add('shake');
                    setTimeout(() => {
                        document.getElementById('password').classList.remove('shake');
                    }, 500);
                }
            } else {
                // Add loading state
                btnText.textContent = 'Authenticating...';
                submitBtn.style.background = 'linear-gradient(135deg, var(--neon-purple), var(--neon-cyan))';
                submitBtn.disabled = true;
            }
        });
        
        // Input focus effects with neon glow
        const inputs = document.querySelectorAll('input');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.style.boxShadow = '0 0 25px rgba(0, 255, 136, 0.3)';
            });
            
            input.addEventListener('blur', function() {
                this.style.boxShadow = '';
            });
        });
        
        // Enter key submission
        document.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                document.getElementById('loginForm').dispatchEvent(new Event('submit'));
            }
        });
        
        // Add floating animation to container
        const container = document.querySelector('.container');
        setInterval(() => {
            container.classList.toggle('float');
        }, 3000);
    </script>
</body>
</html>