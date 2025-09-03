<?php
// Configurazione database
session_start();

$host = 'localhost';
$dbname = 'ticketing_system';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Errore connessione database: " . $e->getMessage());
}

// Funzioni base
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function redirectTo($url) {
    header("Location: $url");
    exit;
}

function requireLogin() {
    if (!isLoggedIn()) {
        redirectTo('login.php');
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        die("Accesso negato: solo amministratori");
    }
}
?>
