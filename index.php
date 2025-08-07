<?php
/**
 * IT Support Ticketing System - Main Entry Point
 * Professional ticketing system for IT support departments
 */
require_once 'config.php';

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Redirect based on user role
if ($_SESSION['role'] === 'admin') {
    header('Location: admin_dashboard.php');
} else {
    header('Location: user_dashboard.php');
}
exit();
?>
