<?php
/**
 * Professional IT Support Ticketing System
 * Configuration File
 * 
 * @author Fantinati Matteo
 * @version 1.0.0
 * @license MIT
 */

// Error reporting for development (disable in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Timezone setting
date_default_timezone_set('Europe/Rome');

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'ticketing_system');

// Security configuration
define('HASH_ALGORITHM', PASSWORD_DEFAULT);
define('SESSION_TIMEOUT', 3600); // 1 hour
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_TIME', 900); // 15 minutes
define('PASSWORD_MIN_LENGTH', 8);
define('PASSWORD_REQUIRE_SPECIAL', true);

// Session security
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 0); // Set to 1 for HTTPS
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Strict');

// File upload configuration
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_FILE_TYPES', ['pdf', 'doc', 'docx', 'txt', 'jpg', 'jpeg', 'png']);
define('UPLOAD_PATH', __DIR__ . '/uploads/');

// GDPR compliance
define('DATA_RETENTION_DAYS', 365);
define('GDPR_PROCESSING_DEADLINE_DAYS', 30);

// System configuration
define('SYSTEM_NAME', 'IT Support Ticketing System');
define('SYSTEM_VERSION', '1.0.0');
define('SUPPORT_EMAIL', 'support@company.com');
define('COMPANY_NAME', 'Your Company Name');

// Email configuration (for notifications)
define('SMTP_HOST', 'localhost');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', '');
define('SMTP_PASSWORD', '');
define('SMTP_FROM_EMAIL', 'noreply@company.com');
define('SMTP_FROM_NAME', 'IT Support System');

// Create database connection
function getDBConnection() {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
        return $pdo;
    } catch (PDOException $e) {
        error_log("Database connection failed: " . $e->getMessage());
        die("Database connection failed. Please try again later.");
    }
}

// Security function to sanitize output
function sanitizeOutput($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

// CSRF token generation and validation
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Log security events
function logSecurityEvent($event_type, $details = '', $user_id = null) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            INSERT INTO security_logs (event_type, details, user_id, ip_address, user_agent, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $event_type,
            $details,
            $user_id,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
    } catch (Exception $e) {
        error_log("Failed to log security event: " . $e->getMessage());
    }
}

// Check if user is locked out
function isUserLockedOut($email) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as attempts 
            FROM security_logs 
            WHERE event_type = 'failed_login' 
            AND details LIKE ? 
            AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)
        ");
        $stmt->execute(["%$email%", LOCKOUT_TIME]);
        $result = $stmt->fetch();
        
        return $result['attempts'] >= MAX_LOGIN_ATTEMPTS;
    } catch (Exception $e) {
        error_log("Failed to check lockout status: " . $e->getMessage());
        return false;
    }
}
?>
