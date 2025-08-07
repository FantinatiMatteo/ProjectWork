<?php
/**
 * Migration: 003_create_security_logs_table
 * Creates comprehensive security logging system
 */

if (!isset($this->pdo)) {
    global $pdo;
    $pdo = $this->pdo;
}

$sql = "
CREATE TABLE security_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    event_type VARCHAR(50) NOT NULL,
    event_category ENUM(
        'authentication', 
        'authorization', 
        'data_access', 
        'system', 
        'security_violation',
        'gdpr_compliance'
    ) NOT NULL,
    severity ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    
    -- Event details
    details TEXT,
    request_url VARCHAR(500) NULL,
    request_method VARCHAR(10) NULL,
    
    -- User information
    user_id INT NULL,
    user_email VARCHAR(255) NULL,
    
    -- Request information
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT NULL,
    session_id VARCHAR(128) NULL,
    
    -- Additional metadata (JSON format)
    metadata JSON NULL,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign keys
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    
    -- Indexes for performance and security analysis
    INDEX idx_event_type (event_type),
    INDEX idx_event_category (event_category),
    INDEX idx_severity (severity),
    INDEX idx_user_id (user_id),
    INDEX idx_ip_address (ip_address),
    INDEX idx_created_at (created_at),
    INDEX idx_user_email (user_email),
    INDEX idx_category_severity (event_category, severity),
    INDEX idx_ip_created (ip_address, created_at),
    INDEX idx_user_created (user_id, created_at)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
";

$pdo->exec($sql);

// Create table for tracking login attempts specifically
$loginAttemptsSql = "
CREATE TABLE login_attempts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    success BOOLEAN NOT NULL,
    failure_reason VARCHAR(100) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_email (email),
    INDEX idx_ip_address (ip_address),
    INDEX idx_success (success),
    INDEX idx_created_at (created_at),
    INDEX idx_email_ip (email, ip_address),
    INDEX idx_email_created (email, created_at)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
";

$pdo->exec($loginAttemptsSql);

// Insert initial system log
$stmt = $pdo->prepare("
    INSERT INTO security_logs (
        event_type, event_category, severity, details, 
        ip_address, user_agent, metadata
    ) VALUES (?, ?, ?, ?, ?, ?, ?)
");

$stmt->execute([
    'system_initialization',
    'system',
    'low',
    'Ticketing system database initialized successfully',
    '127.0.0.1',
    'Database Migration System',
    json_encode([
        'version' => '1.0.0',
        'migration_date' => date('Y-m-d H:i:s'),
        'tables_created' => ['security_logs', 'login_attempts']
    ])
]);

echo "✅ Security logging system created\n";
echo "   🔒 Comprehensive event tracking enabled\n";
echo "   📊 Login attempts monitoring active\n";
echo "   🛡️ Security violation detection ready\n";
?>
