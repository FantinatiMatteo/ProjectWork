<?php
/**
 * Migration: 002_create_tickets_table
 * Creates the tickets table with proper relationships and indexing
 */

if (!isset($this->pdo)) {
    global $pdo;
    $pdo = $this->pdo;
}

$sql = "
CREATE TABLE tickets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ticket_number VARCHAR(20) UNIQUE NOT NULL,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium' NOT NULL,
    status ENUM('pending', 'in_progress', 'resolved', 'closed') DEFAULT 'pending' NOT NULL,
    category VARCHAR(100) NULL,
    assigned_to INT NULL,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    closed_at TIMESTAMP NULL,
    
    -- Admin notes (only visible to admins)
    admin_notes TEXT NULL,
    
    -- File attachments (JSON array of file paths)
    attachments JSON NULL,
    
    -- Customer satisfaction rating (1-5 after resolution)
    satisfaction_rating TINYINT NULL CHECK (satisfaction_rating BETWEEN 1 AND 5),
    satisfaction_comment TEXT NULL,
    
    -- Foreign keys
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    
    -- Indexes for performance
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_priority (priority),
    INDEX idx_created_at (created_at),
    INDEX idx_ticket_number (ticket_number),
    INDEX idx_assigned_to (assigned_to),
    INDEX idx_category (category),
    INDEX idx_status_priority (status, priority),
    INDEX idx_user_status (user_id, status)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
";

$pdo->exec($sql);

// Create trigger to auto-generate ticket numbers
$triggerSql = "
CREATE TRIGGER generate_ticket_number 
BEFORE INSERT ON tickets 
FOR EACH ROW 
BEGIN 
    DECLARE next_id INT;
    SELECT AUTO_INCREMENT INTO next_id 
    FROM information_schema.TABLES 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tickets';
    
    SET NEW.ticket_number = CONCAT('TK-', YEAR(NOW()), '-', LPAD(next_id, 6, '0'));
END;
";

$pdo->exec($triggerSql);

// Insert sample tickets for demonstration
$stmt = $pdo->prepare("
    INSERT INTO tickets (user_id, title, description, priority, status, category) VALUES 
    (2, 'Computer won\\'t start', 'My computer doesn\\'t turn on when I press the power button. I\\'ve checked the power cable and it\\'s properly connected. The LED indicators are not lighting up.', 'high', 'pending', 'Hardware'),
    (2, 'Email not working', 'I can\\'t send or receive emails through Outlook. Getting error message: \"Cannot connect to server\". This has been happening since yesterday morning.', 'medium', 'in_progress', 'Software'),
    (2, 'Printer issues', 'The office printer (HP LaserJet Pro) is making strange grinding noises and printing completely blank pages. Already checked toner levels.', 'low', 'resolved', 'Hardware'),
    (2, 'VPN Connection Problems', 'Unable to connect to company VPN from home. Getting timeout errors. Worked fine last week.', 'medium', 'pending', 'Network'),
    (2, 'Software Installation Request', 'Need Adobe Photoshop installed on my workstation for the upcoming marketing campaign. Department head approval attached.', 'low', 'pending', 'Software')
");

$stmt->execute();

echo "✅ Tickets table created with sample data\n";
echo "   🎫 Auto-generating ticket numbers (TK-YYYY-XXXXXX format)\n";
echo "   📊 Categories: Hardware, Software, Network\n";
?>
