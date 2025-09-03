-- Ticketing System Database Schema
-- Created for ITS Project Work - ticketingit per Assistenza IT

CREATE DATABASE IF NOT EXISTS ticketing_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ticketing_system;

-- Users table with secure authentication
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    gdpr_consent BOOLEAN DEFAULT FALSE,
    gdpr_consent_date TIMESTAMP NULL,
    INDEX idx_email (email),
    INDEX idx_role (role)
);

-- Tickets table
CREATE TABLE tickets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    status ENUM('pending', 'in_progress', 'resolved', 'closed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    admin_notes TEXT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_priority (priority),
    INDEX idx_created_at (created_at)
);

-- Security logs table for monitoring
CREATE TABLE security_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    event_type VARCHAR(50) NOT NULL,
    details TEXT,
    user_id INT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_event_type (event_type),
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at),
    INDEX idx_ip_address (ip_address)
);

-- Session management table (optional but recommended for security)
CREATE TABLE user_sessions (
    id VARCHAR(128) PRIMARY KEY,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_expires_at (expires_at)
);

-- Insert default admin user (password: admin123 - CHANGE IN PRODUCTION!)
INSERT INTO users (email, password_hash, first_name, last_name, role, gdpr_consent, gdpr_consent_date) 
VALUES (
    'admin@company.com', 
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- admin123
    'System', 
    'Administrator', 
    'admin',
    TRUE,
    NOW()
);

-- Insert sample user (password: user123)
INSERT INTO users (email, password_hash, first_name, last_name, role, gdpr_consent, gdpr_consent_date) 
VALUES (
    'user@company.com', 
    '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', -- user123
    'Test', 
    'User', 
    'user',
    TRUE,
    NOW()
);

-- Insert sample tickets for demonstration
INSERT INTO tickets (user_id, title, description, priority, status) VALUES
(2, 'Computer won\'t start', 'My computer doesn\'t turn on when I press the power button. I\'ve checked the power cable and it\'s properly connected.', 'high', 'pending'),
(2, 'Email not working', 'I can\'t send or receive emails. Getting error message about server connection.', 'medium', 'in_progress'),
(2, 'Printer issues', 'The office printer is making strange noises and printing blank pages.', 'low', 'resolved');

-- Log initial system setup
INSERT INTO security_logs (event_type, details, user_id, ip_address, user_agent) 
VALUES ('system_setup', 'Initial database setup completed', 1, '127.0.0.1', 'System Setup');

-- Create procedure for cleaning old security logs (GDPR compliance)
DELIMITER //
CREATE PROCEDURE CleanOldSecurityLogs()
BEGIN
    DELETE FROM security_logs 
    WHERE created_at < DATE_SUB(NOW(), INTERVAL 365 DAY);
END //
DELIMITER ;

-- Create procedure for user data export (GDPR compliance)
DELIMITER //
CREATE PROCEDURE ExportUserData(IN user_email VARCHAR(255))
BEGIN
    SELECT 'User Data' as data_type, u.id, u.email, u.first_name, u.last_name, u.created_at, u.gdpr_consent_date
    FROM users u WHERE u.email = user_email
    UNION ALL
    SELECT 'Tickets' as data_type, t.id, t.title, t.description, t.priority, t.status, t.created_at
    FROM tickets t 
    JOIN users u ON t.user_id = u.id 
    WHERE u.email = user_email;
END //
DELIMITER ;
