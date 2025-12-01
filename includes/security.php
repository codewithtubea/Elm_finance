<?php
class Security {
    // Rate limiting
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
    
    // Session security - CALL THIS BEFORE session_start()
    public static function configureSession() {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', 1); // Only if using HTTPS
        ini_set('session.use_strict_mode', 1);
    }
    
    // Regenerate session ID - CALL THIS AFTER session_start()
    public static function regenerateSession() {
        session_regenerate_id(true);
    }
    
    // Generate secure tokens
    public static function generateToken($length = 32) {
        return bin2hex(random_bytes($length));
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
}
?>