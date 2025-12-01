<?php
class Auth {
    private $db;
    private $maxLoginAttempts = 5;
    private $lockoutTime = 900; // 15 minutes
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    // ==================== REGISTRATION ====================
    public function register($userData) {
        // Validate input
        $errors = $this->validateRegistration($userData);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        // Check rate limiting
        $ip = $_SERVER['REMOTE_ADDR'];
        if (!Security::checkRateLimit("register_$ip", 3, 3600)) {
            return ['success' => false, 'errors' => ['Too many registration attempts. Try again later.']];
        }
        
        try {
            // Check if user exists
            $stmt = $this->db->prepare("SELECT id FROM elm_users WHERE username = ? OR email = ?");
            $stmt->execute([$userData['username'], $userData['email']]);
            
            if ($stmt->rowCount() > 0) {
                return ['success' => false, 'errors' => ['Username or email already exists']];
            }
            
            // Create user
            $hashed_password = password_hash($userData['password'], PASSWORD_DEFAULT);
            $verification_token = Security::generateToken();
            
            $stmt = $this->db->prepare("INSERT INTO elm_users (username, email, password_hash, first_name, last_name, verification_token, is_verified) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $userData['username'],
                $userData['email'],
                $hashed_password,
                $userData['first_name'],
                $userData['last_name'],
                $verification_token,
                1 // Auto-verify for development
            ]);
            
            return ['success' => true, 'message' => 'Account created successfully! You can now login.'];
            
        } catch (Exception $e) {
            error_log("Registration error: " . $e->getMessage());
            return ['success' => false, 'errors' => ['Registration failed. Please try again.']];
        }
    }
    

    public function login($username, $password, $remember = false) {
        // Rate limiting check
        $ip = $_SERVER['REMOTE_ADDR'];
        if (!Security::checkRateLimit("login_$ip", $this->maxLoginAttempts, $this->lockoutTime)) {
            $remainingTime = $this->lockoutTime - (time() - $_SESSION["rate_limit_login_$ip"]['first_attempt']);
            $minutes = ceil($remainingTime / 60);
            return ['success' => false, 'error' => "Too many login attempts. Try again in $minutes minutes."];
        }
        
        try {
            $stmt = $this->db->prepare("SELECT id, username, email, password_hash, is_active, is_verified FROM elm_users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
             if ($user && password_verify($password, $user['password_hash'])) {
                 if (!$user['is_verified']) {
                     return ['success' => false, 'error' => 'Please verify your email before logging in.'];
                 }
                
                if (!$user['is_active']) {
                    return ['success' => false, 'error' => 'Account deactivated. Contact support.'];
                }
                
                // Successful login - reset rate limiting
                unset($_SESSION["rate_limit_login_$ip"]);
                
                // Create session
                $this->createUserSession($user);
                
                // Set longer session if "remember me" is checked
                if ($remember) {
                    // Set cookie to expire in 7 days
                    setcookie(session_name(), session_id(), time() + 604800, "/");
                }
                
                return ['success' => true, 'user' => $user];
            }
            
            return ['success' => false, 'error' => 'Invalid username or password'];
            
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Login failed. Please try again.'];
        }
    }
    
    // ==================== SESSION MANAGEMENT ====================
    private function createUserSession($user) {
    // Ensure session is properly configured
    Security::configureSession();
    
    Security::regenerateSession();
    
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['logged_in'] = true;
    $_SESSION['login_time'] = time();
    $_SESSION['session_id'] = session_id();
    $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
    
    // Force immediate session write
    session_write_close();
    
    // Update last login
    $stmt = $this->db->prepare("UPDATE elm_users SET last_login = NOW() WHERE id = ?");
    $stmt->execute([$user['id']]);
}
    
    public function isLoggedIn() {
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            return false;
        }
        
        // Check session hijacking
        if ($_SESSION['ip_address'] !== $_SERVER['REMOTE_ADDR']) {
            $this->logout();
            return false;
        }
        
        if ($_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
            $this->logout();
            return false;
        }
        
        // Check session timeout (8 hours)
        if (time() - $_SESSION['login_time'] > 28800) {
            $this->logout();
            return false;
        }
        
        return true;
    }
    
    public function logout() {
        // Clear all session variables
        $_SESSION = [];
        
        // Delete session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // Destroy session
        session_destroy();
    }
    
    public function requireAuth() {
        if (!$this->isLoggedIn()) {
            header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
            exit;
        }
    }
    
    public function requireGuest() {
        if ($this->isLoggedIn()) {
            header('Location: dashboard.php');
            exit;
        }
    }
    
    // ==================== USER MANAGEMENT ====================
    public function getUser($user_id = null) {
        if ($user_id === null) {
            $user_id = $_SESSION['user_id'] ?? null;
        }
        
        if (!$user_id) {
            return null;
        }
        
        try {
            $stmt = $this->db->prepare("SELECT id, username, email, first_name, last_name, university, created_at, last_login FROM elm_users WHERE id = ?");
            $stmt->execute([$user_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Get user error: " . $e->getMessage());
            return null;
        }
    }
    
    public function updateProfile($userData) {
        if (!$this->isLoggedIn()) {
            return ['success' => false, 'error' => 'Not logged in'];
        }
        
        $errors = $this->validateProfileUpdate($userData);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        try {
            $stmt = $this->db->prepare("UPDATE elm_users SET first_name = ?, last_name = ?, university = ? WHERE id = ?");
            $stmt->execute([
                $userData['first_name'],
                $userData['last_name'],
                $userData['university'],
                $_SESSION['user_id']
            ]);
            
            // Update session if username/email changed
            if (isset($userData['username'])) {
                $_SESSION['username'] = $userData['username'];
            }
            if (isset($userData['email'])) {
                $_SESSION['email'] = $userData['email'];
            }
            
            return ['success' => true, 'message' => 'Profile updated successfully'];
            
        } catch (Exception $e) {
            error_log("Profile update error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Profile update failed'];
        }
    }
    
    public function changePassword($currentPassword, $newPassword) {
        if (!$this->isLoggedIn()) {
            return ['success' => false, 'error' => 'Not logged in'];
        }
        
        // Validate new password
        if (!Validator::validatePassword($newPassword)) {
            return ['success' => false, 'error' => 'New password must be at least 8 characters with uppercase, lowercase, and numbers'];
        }
        
        try {
            // Verify current password
            $stmt = $this->db->prepare("SELECT password_hash FROM elm_users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
                return ['success' => false, 'error' => 'Current password is incorrect'];
            }
            
            // Update password
            $hashed_password = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $this->db->prepare("UPDATE elm_users SET password_hash = ? WHERE id = ?");
            $stmt->execute([$hashed_password, $_SESSION['user_id']]);
            
            return ['success' => true, 'message' => 'Password changed successfully'];
            
        } catch (Exception $e) {
            error_log("Password change error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Password change failed'];
        }
    }
    
    // ==================== VALIDATION METHODS ====================
    private function validateRegistration($userData) {
        $errors = [];
        
        if (!Validator::validateUsername($userData['username'])) {
            $errors[] = 'Username must be 3-20 characters (letters, numbers, underscores only)';
        }
        
        if (!Validator::validateEmail($userData['email'])) {
            $errors[] = 'Valid Ashesi email required (@ashesi.edu.gh)';
        }
        
        if (!Validator::validatePassword($userData['password'])) {
            $errors[] = 'Password must be at least 8 characters with uppercase, lowercase, and numbers';
        }
        
        if (!empty($userData['first_name']) && !preg_match('/^[a-zA-Z\s\-]{1,50}$/', $userData['first_name'])) {
            $errors[] = 'First name contains invalid characters';
        }
        
        if (!empty($userData['last_name']) && !preg_match('/^[a-zA-Z\s\-]{1,50}$/', $userData['last_name'])) {
            $errors[] = 'Last name contains invalid characters';
        }
        
        return $errors;
    }
    
    private function validateProfileUpdate($userData) {
        $errors = [];
        
        if (!empty($userData['username']) && !Validator::validateUsername($userData['username'])) {
            $errors[] = 'Username must be 3-20 characters (letters, numbers, underscores only)';
        }
        
        if (!empty($userData['email']) && !Validator::validateEmail($userData['email'])) {
            $errors[] = 'Valid Ashesi email required (@ashesi.edu.gh)';
        }
        
        if (!empty($userData['first_name']) && !preg_match('/^[a-zA-Z\s\-]{1,50}$/', $userData['first_name'])) {
            $errors[] = 'First name contains invalid characters';
        }
        
        if (!empty($userData['last_name']) && !preg_match('/^[a-zA-Z\s\-]{1,50}$/', $userData['last_name'])) {
            $errors[] = 'Last name contains invalid characters';
        }
        
        return $errors;
    }
    
    // ==================== SECURITY METHODS ====================
    public function verifyEmail($token) {
        try {
            $stmt = $this->db->prepare("UPDATE elm_users SET is_verified = 1, verification_token = NULL WHERE verification_token = ?");
            $stmt->execute([$token]);
            
            return $stmt->rowCount() > 0;
            
        } catch (Exception $e) {
            error_log("Email verification error: " . $e->getMessage());
            return false;
        }
    }
    
    public function deactivateAccount($password) {
        if (!$this->isLoggedIn()) {
            return ['success' => false, 'error' => 'Not logged in'];
        }
        
        try {
            // Verify password
            $stmt = $this->db->prepare("SELECT password_hash FROM elm_users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user || !password_verify($password, $user['password_hash'])) {
                return ['success' => false, 'error' => 'Password is incorrect'];
            }
            
            // Deactivate account
            $stmt = $this->db->prepare("UPDATE elm_users SET is_active = 0 WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            
            $this->logout();
            
            return ['success' => true, 'message' => 'Account deactivated successfully'];
            
        } catch (Exception $e) {
            error_log("Account deactivation error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Account deactivation failed'];
        }
    }
    
    // ==================== UTILITY METHODS ====================
    public function getLoginAttempts($identifier) {
        $key = "rate_limit_login_$identifier";
        return $_SESSION[$key]['attempts'] ?? 0;
    }
    
    public function getRemainingLockoutTime($identifier) {
        $key = "rate_limit_login_$identifier";
        if (!isset($_SESSION[$key])) {
            return 0;
        }
        
        $remaining = $this->lockoutTime - (time() - $_SESSION[$key]['first_attempt']);
        return max(0, $remaining);
    }
}
?>