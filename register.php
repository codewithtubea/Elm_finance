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
                    $stmt = $pdo->prepare("INSERT INTO elm_users (username, email, password_hash, first_name, last_name) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$username, $email, $hashed_password, $first_name, $last_name]);
                    
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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Elm Finance</title>
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
            max-width: 480px;
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
        
        .login-link { 
            text-align: center; 
            margin-top: 2rem;
            color: var(--text-secondary);
            font-size: 0.9rem;
        }
        
        .login-link a { 
            color: var(--neon-cyan); 
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            text-shadow: 0 0 10px rgba(0, 255, 255, 0.3);
        }
        
        .login-link a:hover {
            color: var(--neon-green);
            text-shadow: 0 0 15px rgba(0, 255, 136, 0.5);
        }
        
        .password-strength {
            margin-top: 0.5rem;
            font-size: 0.8rem;
            color: var(--text-secondary);
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
    </style>
</head>
<body>
    <div class="container glow">
        <div class="logo float">
            <h1>🌿 Elm Finance</h1>
            <p>Next-gen financial intelligence</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert success"><?php echo $success; ?></div>
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
                           title="3-20 characters, letters, numbers, underscores only">
                </div>
            </div>
            
            <div class="form-group">
                <label for="email">Email</label>
                <div class="input-wrapper">
                    <div class="input-icon">✉️</div>
                    <input type="email" id="email" name="email" required
                           placeholder="john@ashesi.edu.gh"
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-wrapper">
                    <div class="input-icon">🔒</div>
                    <input type="password" id="password" name="password" required
                           placeholder="••••••••"
                           minlength="8">
                </div>
                <div class="password-strength">Minimum 8 characters</div>
            </div>
            
            <div class="form-group">
                <label for="first_name">First Name</label>
                <div class="input-wrapper">
                    <div class="input-icon">👋</div>
                    <input type="text" id="first_name" name="first_name"
                           placeholder="John (optional)"
                           value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label for="last_name">Last Name</label>
                <div class="input-wrapper">
                    <div class="input-icon">👋</div>
                    <input type="text" id="last_name" name="last_name"
                           placeholder="Doe (optional)"
                           value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>">
                </div>
            </div>
            
            <button type="submit" class="btn" id="submitBtn">
                <span id="btnText">Create Account</span>
            </button>
        </form>
        
        <div class="login-link">
            Already part of Elm? <a href="login.php">Access your dashboard</a>
        </div>
    </div>

    <script>
        // Real-time form validation with neon effects
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const submitBtn = document.getElementById('submitBtn');
            const btnText = document.getElementById('btnText');
            
            if (password.length < 8) {
                e.preventDefault();
                document.getElementById('password').style.borderColor = 'var(--error)';
                document.getElementById('password').style.boxShadow = '0 0 20px rgba(255, 68, 68, 0.3)';
                
                setTimeout(() => {
                    document.getElementById('password').style.borderColor = '';
                    document.getElementById('password').style.boxShadow = '';
                }, 2000);
            } else {
                // Add loading state
                btnText.textContent = 'Creating Account...';
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
        
        // Add floating animation to container
        const container = document.querySelector('.container');
        setInterval(() => {
            container.classList.toggle('float');
        }, 3000);
    </script>
</body>
</html>