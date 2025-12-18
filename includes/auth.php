<?php
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/../config/database.php';

class Auth {
    private $db;
    private $maxLoginAttempts = 8;
    private $lockoutTime = 300; // 5 minutes
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    // Registration
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
            
            $stmt = $this->db->prepare("INSERT INTO elm_users (username, email, password_hash, first_name, last_name, university, role, is_verified) VALUES (?, ?, ?, ?, ?, ?, 'student', ?)");
            $stmt->execute([
                $userData['username'],
                $userData['email'],
                $hashed_password,
                $userData['first_name'] ?? '',
                $userData['last_name'] ?? '',
                $userData['university'] ?? 'Ashesi University',
                1 // Auto-verify for development
            ]);
            
            return ['success' => true, 'message' => 'Account created successfully! You can now login.'];
            
        } catch (Exception $e) {
            error_log("Registration error: " . $e->getMessage());
            return ['success' => false, 'errors' => ['Registration failed. Please try again.']];
        }
    }
    
    // Login
    public function login($username, $password, $remember = false) {
        try {
            // Find user - INCLUDING ROLE
            $stmt = $this->db->prepare("SELECT id, username, email, password_hash, role FROM elm_users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                return ['success' => false, 'error' => 'Invalid username or password'];
            }
            
            // Verify password
            if (!password_verify($password, $user['password_hash'])) {
                return ['success' => false, 'error' => 'Invalid username or password'];
            }
            
            // Create session with role
            if (class_exists('Security')) {
                Security::configureSession();
            }
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['logged_in'] = true;
            $_SESSION['login_time'] = time();
            $_SESSION['user_role'] = $user['role']; // Store role in session
            $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? '';
            $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
            
            // Regenerate session ID for security
            session_regenerate_id(true);
            
            // Set session cookie with root path
            $params = session_get_cookie_params();
            $cookieLifetime = $remember ? time() + 604800 : 0;
            
            setcookie(
                session_name(),
                session_id(),
                $cookieLifetime,
                '/', // Root path - important!
                $params["domain"] ?? '',
                $params["secure"] ?? false,
                $params["httponly"] ?? true
            );
            
            // Also set in $_COOKIE for immediate access
            $_COOKIE[session_name()] = session_id();
            
            // Update last login
            $this->updateLastLogin($user['id']);
            
            return [
                'success' => true, 
                'user' => $user,
                'role' => $user['role'] ?? 'student',
                'message' => 'Login successful'
            ];
            
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Login failed. Please try again.'];
        }
    }
    
    // Current User
    public function getCurrentUser() {
        // Check if user is logged in
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        // Get user ID from session
        $userId = $_SESSION['user_id'] ?? null;
        
        if (!$userId) {
            return null;
        }
        
        try {
            // Fetch user from database WITH ROLE
            $stmt = $this->db->prepare("SELECT * FROM elm_users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $user ?: null;
        } catch (Exception $e) {
            error_log("Error fetching current user: " . $e->getMessage());
            return null;
        }
    }
    
    // Session Management
    private function updateLastLogin($userId) {
        try {
            $stmt = $this->db->prepare("UPDATE elm_users SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$userId]);
        } catch (Exception $e) {
            error_log("Update last login error: " . $e->getMessage());
        }
    }
    
    public function isLoggedIn() {
        // Check if user is logged in - only check essential session variables
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            error_log("isLoggedIn: logged_in not set or false");
            return false;
        }
        
        // Check if user_id exists (required)
        if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
            error_log("isLoggedIn: user_id not set. Session: " . print_r($_SESSION, true));
            return false;
        }
        
        // Verify user still exists in database (security check)
        try {
            $stmt = $this->db->prepare("SELECT id FROM elm_users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            if (!$stmt->fetch()) {
                error_log("isLoggedIn: User ID " . $_SESSION['user_id'] . " not found in database");
                $this->logout();
                return false;
            }
        } catch (Exception $e) {
            error_log("isLoggedIn: Database check failed - " . $e->getMessage());
            return false;
        }
        
        // Check session timeout (8 hours)
        if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time'] > 28800)) {
            error_log("Session timeout");
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
                $params["secure"] ?? false, $params["httponly"]
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
            $user = $this->getCurrentUser();
            $role = $user['role'] ?? 'student';
            
            if ($role === 'admin') {
                header('Location: admin.php');
            } else {
                header('Location: dashboard.php');
            }
            exit;
        }
    }
    
    // User Management
    public function getUser($user_id = null) {
        if ($user_id === null) {
            $user_id = $_SESSION['user_id'] ?? null;
        }
        
        if (!$user_id) {
            return null;
        }
        
        try {
            // Fetch user WITH ROLE
            $stmt = $this->db->prepare("SELECT id, username, email, first_name, last_name, university, role, created_at, last_login FROM elm_users WHERE id = ?");
            $stmt->execute([$user_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Get user error: " . $e->getMessage());
            return null;
        }
    }

    // ==================== GET USER BY ID (alias for getUser) ====================
    public function getUserById($userId) {
        return $this->getUser($userId);
    }
    
    // Role Methods
    public function getRole($user_id = null) {
        if ($user_id === null) {
            $user_id = $_SESSION['user_id'] ?? null;
        }
        
        if (!$user_id) {
            return 'student'; // Default role
        }
        
        try {
            $stmt = $this->db->prepare("SELECT role FROM elm_users WHERE id = ?");
            $stmt->execute([$user_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result['role'] ?? 'student';
        } catch (Exception $e) {
            error_log("Get role error: " . $e->getMessage());
            return 'student';
        }
    }
    
    public function isAdmin($user_id = null) {
        $role = $this->getRole($user_id);
        return $role === 'admin';
    }
    
    public function requireAdmin() {
        if (!$this->isLoggedIn()) {
            header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
            exit;
        }
        
        if (!$this->isAdmin()) {
            // Show access denied page or redirect to dashboard
            header('Location: dashboard.php?error=access_denied');
            exit;
        }
    }
    
    // Profile
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
    
    // Validation
    private function validateRegistration($userData) {
        $errors = [];
        
        if (!Validator::validateUsername($userData['username'])) {
            $errors[] = 'Username must be 3-20 characters (letters, numbers, underscores only)';
        }
        
        if (!Validator::validateEmail($userData['email'])) {
            $errors[] = 'Valid Ashesi email (@ashesi.edu.gh) required';
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
            $errors[] = 'Valid Ashesi email (@ashesi.edu.gh) required';
        }
        
        if (!empty($userData['first_name']) && !preg_match('/^[a-zA-Z\s\-]{1,50}$/', $userData['first_name'])) {
            $errors[] = 'First name contains invalid characters';
        }
        
        if (!empty($userData['last_name']) && !preg_match('/^[a-zA-Z\s\-]{1,50}$/', $userData['last_name'])) {
            $errors[] = 'Last name contains invalid characters';
        }
        
        return $errors;
    }
    
    // Security
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
    
    // Utilities
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