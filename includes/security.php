<?php
// includes/security.php
class Security {
    
    // Configure Session
    public static function configureSession() {
        // Only configure if session hasn't been started
        if (session_status() === PHP_SESSION_NONE) {
            // Session security settings - MUST be set BEFORE session_start()
            ini_set('session.use_only_cookies', 1);
            ini_set('session.cookie_httponly', 1);
            ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on');
            ini_set('session.cookie_samesite', 'Lax'); // Changed from Strict to Lax for better AJAX compatibility
            ini_set('session.use_strict_mode', 1);
            ini_set('session.cookie_path', '/'); // Ensure cookie is accessible from all paths
            ini_set('session.cookie_domain', ''); // Use default domain (empty = current domain)
            
            // Start session - PHP will automatically set the cookie with the above parameters
            session_start();
        }
    }
    
    // Rate limit
    public static function checkRateLimit($identifier, $maxAttempts = 5, $timeWindow = 900) {
        $key = "rate_limit_{$identifier}";
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = [
                'attempts' => 0,
                'first_attempt' => time()
            ];
        }
        
        $rateData = $_SESSION[$key];
        
        // Reset if time window passed
        if (time() - $rateData['first_attempt'] > $timeWindow) {
            $_SESSION[$key] = [
                'attempts' => 1,
                'first_attempt' => time()
            ];
            return true;
        }
        
        // Check if exceeded
        if ($rateData['attempts'] >= $maxAttempts) {
            return false;
        }
        
        // Increment attempts
        $_SESSION[$key]['attempts']++;
        return true;
    }
    
    // Regenerate
    public static function regenerateSession() {
        session_regenerate_id(true);
    }
    
    // Tokens
    public static function generateToken($length = 32) {
        return bin2hex(random_bytes($length));
    }
    
    // CSRF
    public static function validateCSRF($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    // Create CSRF token
    public static function createCSRFToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = self::generateToken(32);
        }
        return $_SESSION['csrf_token'];
    }
    
    // Sanitize
    public static function sanitizeOutput($data) {
        if (is_array($data)) {
            return array_map([self::class, 'sanitizeOutput'], $data);
        }
        return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }
}

class Validator {
    public static function validateEmail($email) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        
        // Check domain (for Ashesi students)
        $domain = explode('@', $email)[1] ?? '';
        if (!in_array(strtolower($domain), ['ashesi.edu.gh'])) {
            return false;
        }
        
        return true;
    }
    
    public static function validatePassword($password) {
        // At least 8 chars, 1 uppercase, 1 lowercase, 1 number
        return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', $password);
    }
    
    public static function validateUsername($username) {
        // 3-20 chars, alphanumeric and underscores only
        return preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username);
    }
    
    public static function sanitizeInput($input) {
        if (is_array($input)) {
            return array_map([self::class, 'sanitizeInput'], $input);
        }
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    // Additional validation methods
    public static function validateName($name) {
        return preg_match('/^[a-zA-Z\s\-]{1,50}$/', $name);
    }
    
    public static function validateAmount($amount) {
        return is_numeric($amount) && $amount > 0;
    }
    
    public static function validateDate($date) {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
}
?>