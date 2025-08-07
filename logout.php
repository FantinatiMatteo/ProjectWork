<?php
/**
 * IT Support Ticketing System - Logout
 * Secure session termination
 */
require_once 'config.php';

// Log logout event if user is logged in
if (isset($_SESSION['user_id'])) {
    logSecurityEvent('logout', 'User logged out', $_SESSION['user_id']);
    
    // Clean up session record in database
    try {
        $pdo = getDBConnection();
        $sessionId = session_id();
        $stmt = $pdo->prepare("UPDATE user_sessions SET is_active = FALSE WHERE id = ?");
        $stmt->execute([$sessionId]);
    } catch (Exception $e) {
        error_log("Logout cleanup error: " . $e->getMessage());
    }
}

// Destroy session completely
session_unset();
session_destroy();

// Clear session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Redirect to login with logout confirmation
header('Location: login.php?logout=1');
exit;
?>
