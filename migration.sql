-- =====================================
-- TICKETING IT - MIGRATION COMPLETA
-- Sistema di gestione ticket assistenza IT
-- Conforme GDPR - ITS Project Work
-- =====================================

-- Creazione database
CREATE DATABASE IF NOT EXISTS ticketing_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ticketing_system;

-- =====================================
-- TABELLA UTENTI
-- =====================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    privacy_consent_date DATETIME NULL,
    is_active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP NULL,
    failed_login_attempts INT DEFAULT 0,
    locked_until TIMESTAMP NULL,
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_created_at (created_at),
    INDEX idx_active_users (is_active, role)
) ENGINE=InnoDB;

-- =====================================
-- TABELLA TICKET
-- =====================================
CREATE TABLE IF NOT EXISTS tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    priority ENUM('bassa', 'media', 'alta', 'critica') NOT NULL DEFAULT 'media',
    status ENUM('in_attesa', 'in_lavorazione', 'risolto', 'chiuso') NOT NULL DEFAULT 'in_attesa',
    category VARCHAR(100) DEFAULT 'Generale',
    admin_notes TEXT NULL,
    assigned_to INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    ticket_number VARCHAR(20) UNIQUE,
    estimated_hours DECIMAL(5,2) NULL,
    actual_hours DECIMAL(5,2) NULL,
    customer_satisfaction TINYINT CHECK (customer_satisfaction BETWEEN 1 AND 5),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_priority (priority),
    INDEX idx_created_at (created_at),
    INDEX idx_assigned_to (assigned_to),
    INDEX idx_ticket_number (ticket_number),
    INDEX idx_status_priority (status, priority),
    INDEX idx_user_status (user_id, status)
) ENGINE=InnoDB;

-- =====================================
-- TABELLA COMMENTI TICKET
-- =====================================
CREATE TABLE IF NOT EXISTS ticket_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    user_id INT NOT NULL,
    comment TEXT NOT NULL,
    is_internal BOOLEAN DEFAULT FALSE,
    is_solution BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    attachment_path VARCHAR(500) NULL,
    comment_type ENUM('comment', 'status_change', 'assignment', 'resolution') DEFAULT 'comment',
    FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_ticket_id (ticket_id),
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at),
    INDEX idx_internal (is_internal),
    INDEX idx_ticket_user (ticket_id, user_id)
) ENGINE=InnoDB;

-- =====================================
-- TABELLA LOG DI SICUREZZA
-- =====================================
CREATE TABLE IF NOT EXISTS security_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_email VARCHAR(255) NULL,
    action VARCHAR(100) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT NULL,
    success BOOLEAN DEFAULT TRUE,
    details JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    session_id VARCHAR(128) NULL,
    risk_level ENUM('low', 'medium', 'high', 'critical') DEFAULT 'low',
    INDEX idx_user_email (user_email),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at),
    INDEX idx_ip_address (ip_address),
    INDEX idx_success (success),
    INDEX idx_risk_level (risk_level),
    INDEX idx_action_success (action, success)
) ENGINE=InnoDB;

-- =====================================
-- TABELLA SESSIONI SICURE
-- =====================================
CREATE TABLE IF NOT EXISTS user_sessions (
    id VARCHAR(128) PRIMARY KEY,
    user_id INT NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    csrf_token VARCHAR(64) NOT NULL,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_expires_at (expires_at),
    INDEX idx_is_active (is_active),
    INDEX idx_last_activity (last_activity)
) ENGINE=InnoDB;

-- =====================================
-- TABELLA CONSENSI GDPR
-- =====================================
CREATE TABLE IF NOT EXISTS gdpr_consents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    consent_type ENUM('privacy_policy', 'email_marketing', 'data_processing') NOT NULL,
    consent_given BOOLEAN NOT NULL,
    consent_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT NULL,
    withdrawn_date TIMESTAMP NULL,
    legal_basis ENUM('consent', 'legitimate_interest', 'contract', 'legal_obligation') DEFAULT 'consent',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_consent_type (consent_type),
    INDEX idx_consent_date (consent_date),
    INDEX idx_user_consent_type (user_id, consent_type)
) ENGINE=InnoDB;

-- =====================================
-- TABELLA AGGIORNAMENTI TICKET
-- =====================================
CREATE TABLE IF NOT EXISTS ticket_updates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    user_id INT NOT NULL,
    update_type ENUM('comment', 'status_change', 'priority_change', 'assignment_change', 'system_update') NOT NULL,
    old_value VARCHAR(255) NULL,
    new_value VARCHAR(255) NULL,
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_automated BOOLEAN DEFAULT FALSE,
    email_sent BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_ticket_id (ticket_id),
    INDEX idx_user_id (user_id),
    INDEX idx_update_type (update_type),
    INDEX idx_created_at (created_at),
    INDEX idx_ticket_created (ticket_id, created_at)
) ENGINE=InnoDB;

-- =====================================
-- TABELLA ALLEGATI
-- =====================================
CREATE TABLE IF NOT EXISTS ticket_attachments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    comment_id INT NULL,
    user_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    download_count INT DEFAULT 0,
    FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
    FOREIGN KEY (comment_id) REFERENCES ticket_comments(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_ticket_id (ticket_id),
    INDEX idx_comment_id (comment_id),
    INDEX idx_user_id (user_id),
    INDEX idx_uploaded_at (uploaded_at)
) ENGINE=InnoDB;

-- =====================================
-- TRIGGER PER AUTO-GENERAZIONE NUMERO TICKET
-- =====================================
DELIMITER $$

CREATE TRIGGER generate_ticket_number 
BEFORE INSERT ON tickets 
FOR EACH ROW 
BEGIN 
    DECLARE next_number INT;
    DECLARE current_year CHAR(4);
    
    SET current_year = YEAR(NOW());
    
    SELECT COALESCE(MAX(CAST(SUBSTRING(ticket_number, 6) AS UNSIGNED)), 0) + 1 
    INTO next_number 
    FROM tickets 
    WHERE ticket_number LIKE CONCAT(current_year, '-%');
    
    SET NEW.ticket_number = CONCAT(current_year, '-', LPAD(next_number, 6, '0'));
END$$

DELIMITER ;

-- =====================================
-- TRIGGER PER LOG AUTOMATICO AGGIORNAMENTI
-- =====================================
DELIMITER $$

CREATE TRIGGER log_ticket_status_change
AFTER UPDATE ON tickets
FOR EACH ROW
BEGIN
    IF OLD.status != NEW.status THEN
        INSERT INTO ticket_updates (ticket_id, user_id, update_type, old_value, new_value, description, is_automated)
        VALUES (NEW.id, NEW.user_id, 'status_change', OLD.status, NEW.status, 
                CONCAT('Status cambiato da ', OLD.status, ' a ', NEW.status), TRUE);
    END IF;
    
    IF OLD.priority != NEW.priority THEN
        INSERT INTO ticket_updates (ticket_id, user_id, update_type, old_value, new_value, description, is_automated)
        VALUES (NEW.id, NEW.user_id, 'priority_change', OLD.priority, NEW.priority, 
                CONCAT('Priorità cambiata da ', OLD.priority, ' a ', NEW.priority), TRUE);
    END IF;
END$$

DELIMITER ;

-- =====================================
-- VISTE PER STATISTICHE
-- =====================================

-- Vista statistiche generali
CREATE OR REPLACE VIEW ticket_statistics AS
SELECT 
    COUNT(*) as total_tickets,
    COUNT(CASE WHEN status = 'in_attesa' THEN 1 END) as pending_tickets,
    COUNT(CASE WHEN status = 'in_lavorazione' THEN 1 END) as in_progress_tickets,
    COUNT(CASE WHEN status = 'risolto' THEN 1 END) as resolved_tickets,
    COUNT(CASE WHEN status = 'chiuso' THEN 1 END) as closed_tickets,
    COUNT(CASE WHEN priority = 'critica' THEN 1 END) as critical_priority,
    COUNT(CASE WHEN priority = 'alta' THEN 1 END) as high_priority,
    COUNT(CASE WHEN priority = 'media' THEN 1 END) as medium_priority,
    COUNT(CASE WHEN priority = 'bassa' THEN 1 END) as low_priority,
    COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as last_30_days,
    AVG(CASE WHEN resolved_at IS NOT NULL THEN 
        TIMESTAMPDIFF(HOUR, created_at, resolved_at) END) as avg_resolution_hours
FROM tickets;

-- Vista dettagli utenti
CREATE OR REPLACE VIEW user_details AS
SELECT 
    u.id,
    u.email,
    u.first_name,
    u.last_name,
    u.role,
    u.created_at,
    u.last_login,
    u.is_active,
    COUNT(t.id) as total_tickets,
    COUNT(CASE WHEN t.status = 'in_attesa' THEN 1 END) as pending_tickets,
    COUNT(CASE WHEN t.status = 'risolto' THEN 1 END) as resolved_tickets
FROM users u
LEFT JOIN tickets t ON u.id = t.user_id
GROUP BY u.id;

-- =====================================
-- DATI DEMO E ADMIN
-- =====================================

-- Admin principale (password: Admin@123!)
INSERT INTO users (email, password_hash, first_name, last_name, role, privacy_consent_date, is_active) VALUES 
('admin@ticketing.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'Sistema', 'admin', NOW(), TRUE);

-- Utente demo (password: User@123!)
INSERT INTO users (email, password_hash, first_name, last_name, role, privacy_consent_date, is_active) VALUES 
('user@ticketing.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Mario', 'Rossi', 'user', NOW(), TRUE);

-- Secondo admin per test
INSERT INTO users (email, password_hash, first_name, last_name, role, privacy_consent_date, is_active) VALUES 
('admin2@ticketing.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Giulia', 'Verdi', 'admin', NOW(), TRUE);

-- Utenti aggiuntivi per demo
INSERT INTO users (email, password_hash, first_name, last_name, role, privacy_consent_date, is_active) VALUES 
('luca.bianchi@test.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Luca', 'Bianchi', 'user', NOW(), TRUE),
('anna.ferrari@test.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Anna', 'Ferrari', 'user', NOW(), TRUE);

-- =====================================
-- TICKET DEMO
-- =====================================

-- Ticket demo per mostrare funzionalità
INSERT INTO tickets (user_id, title, description, priority, status, category, admin_notes) VALUES 
(2, 'Problema stampante ufficio', 'La stampante HP LaserJet non stampa e mostra errore carta inceppata, ma non c\'è carta inceppata visibile.', 'media', 'in_lavorazione', 'Hardware', 'Verificato: problema driver. Aggiornamento in corso.'),
(2, 'Account email non funziona', 'Non riesco ad accedere al mio account email aziendale da questa mattina. Errore password incorretta.', 'alta', 'in_attesa', 'Account', NULL),
(4, 'Software gestionale lento', 'Il software di gestione ordini è molto lento, impiega oltre 30 secondi per aprire ogni finestra.', 'media', 'risolto', 'Software', 'Risolto: aumentata RAM workstation e ottimizzato database.'),
(5, 'Richiesta nuova licenza Office', 'Necessito di una licenza Microsoft Office per il nuovo dipendente del reparto contabilità.', 'bassa', 'in_attesa', 'Licenze', NULL),
(2, 'PC non si avvia', 'Il computer dell\'ufficio amministrazione non si accende più da ieri sera. LED alimentazione spento.', 'critica', 'in_lavorazione', 'Hardware', 'Sostituzione alimentatore ordinato. Arrivo previsto domani.'),
(4, 'VPN aziendale disconnette', 'La connessione VPN si disconnette ogni 10-15 minuti durante il lavoro da remoto.', 'alta', 'risolto', 'Rete', 'Configurato keepalive. Problema risolto.'),
(5, 'Backup automatico fallisce', 'Il backup automatico dei file condivisi non funziona da una settimana. Ultimo backup riuscito il 25/08.', 'alta', 'in_attesa', 'Sistema', NULL);

-- =====================================
-- COMMENTI DEMO
-- =====================================

INSERT INTO ticket_comments (ticket_id, user_id, comment, is_internal, comment_type) VALUES 
(1, 3, 'Ticket preso in carico. Verifico stato driver stampante.', TRUE, 'assignment'),
(1, 2, 'Grazie per la presa in carico. La stampante è nel secondo piano, ufficio 205.', FALSE, 'comment'),
(1, 3, 'Driver aggiornato e test di stampa eseguito con successo.', TRUE, 'resolution'),
(3, 3, 'RAM aumentata da 8GB a 16GB. Database ottimizzato.', TRUE, 'resolution'),
(3, 4, 'Perfetto! Ora il software è molto più veloce. Grazie!', FALSE, 'comment'),
(5, 3, 'Alimentatore difettoso confermato. Sostituzione in corso.', TRUE, 'status_change'),
(6, 2, 'Configurazione keepalive funziona perfettamente. Problema risolto.', FALSE, 'comment');

-- =====================================
-- CONSENSI GDPR DEMO
-- =====================================

INSERT INTO gdpr_consents (user_id, consent_type, consent_given, ip_address, legal_basis) VALUES 
(1, 'privacy_policy', TRUE, '127.0.0.1', 'consent'),
(2, 'privacy_policy', TRUE, '127.0.0.1', 'consent'),
(3, 'privacy_policy', TRUE, '127.0.0.1', 'consent'),
(4, 'privacy_policy', TRUE, '127.0.0.1', 'consent'),
(5, 'privacy_policy', TRUE, '127.0.0.1', 'consent');

-- =====================================
-- LOG DI SICUREZZA DEMO
-- =====================================

INSERT INTO security_logs (user_email, action, ip_address, success, details, risk_level) VALUES 
('admin@ticketing.local', 'login', '127.0.0.1', TRUE, '{"user_agent": "Chrome/118.0", "timestamp": "2025-09-03 10:00:00"}', 'low'),
('user@ticketing.local', 'login', '127.0.0.1', TRUE, '{"user_agent": "Firefox/117.0", "timestamp": "2025-09-03 10:15:00"}', 'low'),
('admin@ticketing.local', 'ticket_status_change', '127.0.0.1', TRUE, '{"ticket_id": 1, "old_status": "in_attesa", "new_status": "in_lavorazione"}', 'low'),
('unknown@test.com', 'login_failed', '192.168.1.100', FALSE, '{"reason": "invalid_password", "attempts": 3}', 'medium'),
('user@ticketing.local', 'privacy_consent', '127.0.0.1', TRUE, '{"consent_date": "2025-09-03 09:30:00", "ip": "127.0.0.1"}', 'low');

-- =====================================
-- STORED PROCEDURES UTILI
-- =====================================

DELIMITER $$

-- Procedura per statistiche dashboard
CREATE PROCEDURE GetDashboardStats()
BEGIN
    SELECT 
        (SELECT COUNT(*) FROM tickets) as total_tickets,
        (SELECT COUNT(*) FROM tickets WHERE status = 'in_attesa') as pending_tickets,
        (SELECT COUNT(*) FROM tickets WHERE status = 'in_lavorazione') as in_progress_tickets,
        (SELECT COUNT(*) FROM tickets WHERE status = 'risolto') as resolved_tickets,
        (SELECT COUNT(*) FROM users WHERE role = 'user' AND is_active = TRUE) as active_users,
        (SELECT COUNT(*) FROM users WHERE role = 'admin' AND is_active = TRUE) as active_admins,
        (SELECT COUNT(*) FROM tickets WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)) as tickets_last_week,
        (SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, resolved_at)) FROM tickets WHERE status = 'risolto') as avg_resolution_hours;
END$$

-- Procedura per pulizia sessioni scadute
CREATE PROCEDURE CleanExpiredSessions()
BEGIN
    DELETE FROM user_sessions 
    WHERE expires_at < NOW() OR last_activity < DATE_SUB(NOW(), INTERVAL 24 HOUR);
    
    SELECT ROW_COUNT() as cleaned_sessions;
END$$

DELIMITER ;

-- =====================================
-- OTTIMIZZAZIONI E INDICI AGGIUNTIVI
-- =====================================

-- Indici compositi per performance
ALTER TABLE tickets ADD INDEX idx_user_status_priority (user_id, status, priority);
ALTER TABLE tickets ADD INDEX idx_created_status (created_at, status);
ALTER TABLE security_logs ADD INDEX idx_date_action (created_at, action);
ALTER TABLE ticket_comments ADD INDEX idx_ticket_internal (ticket_id, is_internal);

-- =====================================
-- CONFIGURAZIONI FINALI
-- =====================================

-- Imposta timezone
SET time_zone = '+01:00';

-- Abilita event scheduler per pulizie automatiche
SET GLOBAL event_scheduler = ON;

-- Event per pulizia automatica sessioni scadute
CREATE EVENT IF NOT EXISTS cleanup_expired_sessions
ON SCHEDULE EVERY 1 HOUR
DO CALL CleanExpiredSessions();

-- Event per pulizia log di sicurezza vecchi (dopo 1 anno)
CREATE EVENT IF NOT EXISTS cleanup_old_security_logs
ON SCHEDULE EVERY 1 DAY
DO DELETE FROM security_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 YEAR);

-- =====================================
-- COMMIT E MESSAGGIO FINALE
-- =====================================

COMMIT;

SELECT 'Database ticketing_system creato con successo!' as Status,
       'Admin: admin@ticketing.local / Admin@123!' as Admin_Login,
       'User: user@ticketing.local / User@123!' as User_Login,
       'Sistema pronto per l\'uso!' as Message;

-- =====================================
-- FINE MIGRATION
-- =====================================
