<?php

// includes/session.php - Complete session management for Elm Finance

class SessionManager {

    

    private static $initialized = false;

    private static $isLocalhost = false;

    

    /**

     * Initialize and start session with proper configuration

     */

    public static function start() {

        if (self::$initialized) {

            return true;

        }

        

        // Detect if we're on localhost

        self::$isLocalhost = self::detectLocalhost();

        

        // Configure session settings BEFORE session_start()

        self::configureSession();

        

        // Start the session

        if (session_status() === PHP_SESSION_NONE) {

            session_start();

        }

        

        // Initialize session array if empty

        if (!isset($_SESSION['_initialized'])) {

            self::initializeNewSession();

        }

        

        // Validate existing session (but don't destroy on validation failure - let auth handle it)

        // self::validateSession(); // Commented out - too strict for now

        

        // Regenerate ID periodically for security (every 15 minutes)

        self::regenerateIfNeeded();

        

        self::$initialized = true;

        return true;

    }

    

    /**

     * Detect if we're running on localhost

     */

    private static function detectLocalhost() {

        $host = $_SERVER['HTTP_HOST'] ?? '';

        $server = $_SERVER['SERVER_NAME'] ?? '';

        

        // Common localhost identifiers

        $localhostPatterns = [

            'localhost',

            '127.0.0.1',

            '::1',

            '[::1]'

        ];

        

        foreach ($localhostPatterns as $pattern) {

            if (strpos($host, $pattern) !== false || strpos($server, $pattern) !== false) {

                return true;

            }

        }

        

        // Check if development environment

        if (isset($_SERVER['REMOTE_ADDR']) && ($_SERVER['REMOTE_ADDR'] === '::1' || $_SERVER['REMOTE_ADDR'] === '127.0.0.1')) {

            return true;

        }

        

        return false;

    }

    

    /**

     * Configure session settings based on environment

     */

    private static function configureSession() {

        // Custom session name

        session_name('ELMFINANCE_SESS');

        

        // Different settings for localhost vs production

        if (self::$isLocalhost) {

            // LOCALHOST SETTINGS - More permissive for development

            $cookieParams = [

                'lifetime' => 0, // Session cookie (expires when browser closes)

                'path' => '/', // CRITICAL: Root path so cookie accessible from all directories

                'domain' => '', // Empty for localhost - allows localhost and 127.0.0.1

                'secure' => false, // false for localhost (no HTTPS required)

                'httponly' => true, // Prevent JavaScript access (security)

                'samesite' => 'Lax' // Lax for localhost - allows cross-site GET requests

            ];

            

            // PHP ini settings for localhost

            ini_set('session.cookie_httponly', '1');

            ini_set('session.cookie_secure', '0'); // IMPORTANT: false for localhost

            ini_set('session.cookie_samesite', 'Lax');

            ini_set('session.use_only_cookies', '1');

            ini_set('session.use_strict_mode', '1');

            ini_set('session.use_trans_sid', '0');

            ini_set('session.cookie_path', '/'); // CRITICAL: Root path

            ini_set('session.cookie_domain', ''); // Empty for localhost

            

        } else {

            // PRODUCTION SETTINGS - Secure

            $cookieParams = [

                'lifetime' => 86400, // 24 hours

                'path' => '/',

                'domain' => $_SERVER['HTTP_HOST'] ?? '',

                'secure' => true, // true for HTTPS

                'httponly' => true,

                'samesite' => 'Strict' // Strict for production security

            ];

            

            // PHP ini settings for production

            ini_set('session.cookie_httponly', '1');

            ini_set('session.cookie_secure', '1');

            ini_set('session.cookie_samesite', 'Strict');

            ini_set('session.use_only_cookies', '1');

            ini_set('session.use_strict_mode', '1');

            ini_set('session.use_trans_sid', '0');

        }

        

        // Apply cookie parameters BEFORE session_start()

        session_set_cookie_params($cookieParams);

        

        // Set session save path (optional)

        self::setSavePath();

    }

    

    /**

     * Set custom session save path if needed

     */

    private static function setSavePath() {

        // Use default system temp directory for now

        // You can create a custom directory if needed:

        // $customPath = __DIR__ . '/../sessions';

        // if (!is_dir($customPath)) {

        //     @mkdir($customPath, 0777, true);

        // }

        // if (is_dir($customPath) && is_writable($customPath)) {

        //     session_save_path($customPath);

        // }

    }

    

    /**

     * Initialize a new session

     */

    private static function initializeNewSession() {

        $_SESSION = [];

        

        // Session metadata

        $_SESSION['_initialized'] = true;

        $_SESSION['_created'] = time();

        $_SESSION['_last_activity'] = time();

        $_SESSION['_ip_address'] = $_SERVER['REMOTE_ADDR'] ?? '';

        $_SESSION['_user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';

        

        // Don't regenerate on every initialization - only on login

    }

    

    /**

     * Validate existing session

     */

    private static function validateSession() {

        // Check if session is initialized

        if (!isset($_SESSION['_initialized']) || !$_SESSION['_initialized']) {

            return false;

        }

        

        // Check session age (8 hours max)

        if (isset($_SESSION['_created']) && (time() - $_SESSION['_created'] > 28800)) {

            return false;

        }

        

        // Update last activity

        $_SESSION['_last_activity'] = time();

        

        return true;

    }

    

    /**

     * Regenerate session ID periodically

     */

    private static function regenerateIfNeeded() {

        // Only regenerate if explicitly needed (don't auto-regenerate)

        // Regeneration should happen on login only

        if (isset($_SESSION['_last_regen'])) {

            // Regenerate every 15 minutes for security

            $lastRegen = $_SESSION['_last_regen'] ?? 0;

            if (time() - $lastRegen > 900) {

                session_regenerate_id(true);

                $_SESSION['_last_regen'] = time();

            }

        }

    }

    

    /**

     * Force regenerate session ID (for login)

     */

    public static function regenerateId() {

        self::start();

        session_regenerate_id(true);

        $_SESSION['_last_regen'] = time();

    }

    

    /**

     * Set a session value

     */

    public static function set($key, $value) {

        self::start();

        $_SESSION[$key] = $value;

        return true;

    }

    

    /**

     * Get a session value

     */

    public static function get($key, $default = null) {

        self::start();

        return $_SESSION[$key] ?? $default;

    }

    

    /**

     * Check if a session key exists

     */

    public static function has($key) {

        self::start();

        return isset($_SESSION[$key]);

    }

    

    /**

     * Remove a session value

     */

    public static function remove($key) {

        self::start();

        if (isset($_SESSION[$key])) {

            unset($_SESSION[$key]);

            return true;

        }

        return false;

    }

    

    /**

     * Get session ID

     */

    public static function getId() {

        self::start();

        return session_id();

    }

    

    /**

     * Get session name

     */

    public static function getName() {

        return 'ELMFINANCE_SESS';

    }

    

    /**

     * Destroy session completely

     */

    public static function destroy() {

        if (session_status() === PHP_SESSION_ACTIVE) {

            // Clear all session data

            $_SESSION = [];

            

            // Delete session cookie

            if (ini_get("session.use_cookies")) {

                $params = session_get_cookie_params();

                setcookie(

                    session_name(),

                    '',

                    time() - 42000,

                    $params["path"],

                    $params["domain"],

                    $params["secure"],

                    $params["httponly"]

                );

            }

            

            // Destroy session file

            session_destroy();

        }

        

        self::$initialized = false;

        

        return true;

    }

    

    /**

     * Flash message system (set message for next request only)

     */

    public static function flash($key, $value) {

        self::start();

        if (!isset($_SESSION['_flash'])) {

            $_SESSION['_flash'] = [];

        }

        $_SESSION['_flash'][$key] = $value;

    }

    

    /**

     * Get and clear flash message

     */

    public static function getFlash($key, $default = null) {

        self::start();

        $value = $_SESSION['_flash'][$key] ?? $default;

        if (isset($_SESSION['_flash'][$key])) {

            unset($_SESSION['_flash'][$key]);

        }

        return $value;

    }

    

    /**

     * Check if user is authenticated

     */

    public static function isAuthenticated() {

        self::start();

        return isset($_SESSION['user_id']) && 

               isset($_SESSION['logged_in']) && 

               $_SESSION['logged_in'] === true;

    }

    

    /**

     * Get authenticated user ID

     */

    public static function getUserId() {

        self::start();

        return $_SESSION['user_id'] ?? null;

    }

    

    /**

     * Get authenticated user data

     */

    public static function getUserData() {

        self::start();

        return [

            'user_id' => $_SESSION['user_id'] ?? null,

            'username' => $_SESSION['username'] ?? null,

            'email' => $_SESSION['email'] ?? null,

            'role' => $_SESSION['role'] ?? 'student'

        ];

    }

    

    /**

     * Debug function to see all session data

     */

    public static function debug() {

        self::start();

        return [

            'session_id' => session_id(),

            'session_name' => session_name(),

            'is_localhost' => self::$isLocalhost,

            'data' => $_SESSION,

            'cookie_params' => session_get_cookie_params(),

            'status' => session_status(),

            'cookies_received' => $_COOKIE

        ];

    }

}

?>


