<?php
/**
 * Migration: 004_create_sessions_and_gdpr_tables
 * Creates session management and GDPR compliance tables
 */

if (!isset($this->pdo)) {
    global $pdo;
    $pdo = $this->pdo;
}

// Session management table for enhanced security
$sessionsSql = "
CREATE TABLE user_sessions (
    id VARCHAR(128) PRIMARY KEY,
    user_id INT NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent_hash VARCHAR(64) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    
    INDEX idx_user_id (user_id),
    INDEX idx_expires_at (expires_at),
    INDEX idx_last_activity (last_activity),
    INDEX idx_is_active (is_active),
    INDEX idx_user_active (user_id, is_active)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
";

$pdo->exec($sessionsSql);

// GDPR data requests table
$gdprRequestsSql = "
CREATE TABLE gdpr_requests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    request_type ENUM('data_export', 'data_deletion', 'data_correction') NOT NULL,
    status ENUM('pending', 'processing', 'completed', 'rejected') DEFAULT 'pending',
    
    -- Request details
    request_details TEXT NULL,
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_at TIMESTAMP NULL,
    processed_by INT NULL,
    
    -- Response details
    response_details TEXT NULL,
    export_file_path VARCHAR(500) NULL,
    
    -- Compliance tracking
    compliance_deadline DATE NOT NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE SET NULL,
    
    INDEX idx_user_id (user_id),
    INDEX idx_request_type (request_type),
    INDEX idx_status (status),
    INDEX idx_requested_at (requested_at),
    INDEX idx_compliance_deadline (compliance_deadline)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
";

$pdo->exec($gdprRequestsSql);

// Ticket comments/updates table for better tracking
$ticketUpdatesSql = "
CREATE TABLE ticket_updates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ticket_id INT NOT NULL,
    user_id INT NOT NULL,
    update_type ENUM('comment', 'status_change', 'priority_change', 'assignment_change', 'system_update') NOT NULL,
    
    -- Update content
    content TEXT NULL,
    old_value VARCHAR(100) NULL,
    new_value VARCHAR(100) NULL,
    
    -- Visibility
    is_internal BOOLEAN DEFAULT FALSE, -- Internal notes only visible to admins
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    
    INDEX idx_ticket_id (ticket_id),
    INDEX idx_user_id (user_id),
    INDEX idx_update_type (update_type),
    INDEX idx_created_at (created_at),
    INDEX idx_is_internal (is_internal),
    INDEX idx_ticket_created (ticket_id, created_at)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
";

$pdo->exec($ticketUpdatesSql);

// System settings table for configuration
$settingsSql = "
CREATE TABLE system_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT NOT NULL,
    setting_type ENUM('string', 'integer', 'boolean', 'json') DEFAULT 'string',
    description TEXT NULL,
    is_public BOOLEAN DEFAULT FALSE, -- Can be shown to users
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by INT NULL,
    
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
    
    INDEX idx_setting_key (setting_key),
    INDEX idx_is_public (is_public)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
";

$pdo->exec($settingsSql);

// Insert default system settings
$defaultSettings = [
    ['max_login_attempts', '5', 'integer', 'Maximum failed login attempts before lockout'],
    ['lockout_duration', '900', 'integer', 'Account lockout duration in seconds (15 minutes)'],
    ['session_timeout', '3600', 'integer', 'Session timeout in seconds (1 hour)'],
    ['gdpr_data_retention_days', '365', 'integer', 'Data retention period in days for GDPR compliance'],
    ['allow_user_registration', 'true', 'boolean', 'Allow new user registrations'],
    ['maintenance_mode', 'false', 'boolean', 'Enable maintenance mode'],
    ['system_name', 'IT Support Ticketing System', 'string', 'System name displayed to users'],
    ['support_email', 'support@company.com', 'string', 'Support contact email'],
    ['ticket_auto_close_days', '30', 'integer', 'Auto-close resolved tickets after N days']
];

$stmt = $pdo->prepare("
    INSERT INTO system_settings (setting_key, setting_value, setting_type, description) 
    VALUES (?, ?, ?, ?)
");

foreach ($defaultSettings as $setting) {
    $stmt->execute($setting);
}

echo "✅ Session management and GDPR compliance tables created\n";
echo "   🔐 Enhanced session security enabled\n";
echo "   📋 GDPR request tracking system ready\n";
echo "   💬 Ticket updates and comments system created\n";
echo "   ⚙️ System settings management ready\n";
?>
