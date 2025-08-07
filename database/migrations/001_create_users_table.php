<?php
/**
 * Migration: 001_create_users_table
 * Creates the users table with secure authentication fields
 */

if (!isset($this->pdo)) {
    global $pdo;
    $pdo = $this->pdo;
}

$sql = "
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user' NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    email_verified BOOLEAN DEFAULT FALSE,
    email_verification_token VARCHAR(255) NULL,
    password_reset_token VARCHAR(255) NULL,
    password_reset_expires TIMESTAMP NULL,
    failed_login_attempts INT DEFAULT 0,
    locked_until TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    last_login_ip VARCHAR(45) NULL,
    gdpr_consent BOOLEAN DEFAULT FALSE NOT NULL,
    gdpr_consent_date TIMESTAMP NULL,
    gdpr_consent_ip VARCHAR(45) NULL,
    data_retention_date DATE NULL,
    
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_is_active (is_active),
    INDEX idx_email_verification_token (email_verification_token),
    INDEX idx_password_reset_token (password_reset_token),
    INDEX idx_created_at (created_at),
    INDEX idx_last_login (last_login)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
";

$pdo->exec($sql);

// Insert default admin user with secure password
$adminPassword = password_hash('Admin@123!', PASSWORD_DEFAULT);
$userPassword = password_hash('User@123!', PASSWORD_DEFAULT);

$stmt = $pdo->prepare("
    INSERT INTO users (
        email, password_hash, first_name, last_name, role, 
        is_active, email_verified, gdpr_consent, gdpr_consent_date, gdpr_consent_ip
    ) VALUES 
    (?, ?, ?, ?, ?, TRUE, TRUE, TRUE, NOW(), '127.0.0.1'),
    (?, ?, ?, ?, ?, TRUE, TRUE, TRUE, NOW(), '127.0.0.1')
");

$stmt->execute([
    'admin@ticketing.local', $adminPassword, 'System', 'Administrator', 'admin',
    'user@ticketing.local', $userPassword, 'Demo', 'User', 'user'
]);

echo "✅ Users table created with default accounts\n";
echo "   📧 Admin: admin@ticketing.local / Admin@123!\n";
echo "   📧 User: user@ticketing.local / User@123!\n";
?>
