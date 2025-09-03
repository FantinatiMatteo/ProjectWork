-- DATABASE SECONDO CONSEGNA ESATTA
DROP DATABASE IF EXISTS ticketing_system;
CREATE DATABASE ticketing_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ticketing_system;

-- Tabella users (sistema autenticazione a due ruoli)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabella tickets (secondo consegna: titolo, descrizione, priorità)
CREATE TABLE tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    priority ENUM('bassa', 'media', 'alta') DEFAULT 'media',
    status ENUM('in_attesa', 'in_lavorazione', 'risolto') DEFAULT 'in_attesa',
    admin_notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Dati di test
INSERT INTO users (email, password_hash, first_name, last_name, role) VALUES 
('admin@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'Sistema', 'admin'),
('user@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Mario', 'Rossi', 'user');

-- Ticket di esempio
INSERT INTO tickets (user_id, title, description, priority, status) VALUES 
(2, 'Computer non si avvia', 'Il computer dell\'ufficio non si accende quando premo il pulsante di alimentazione. Ho provato a controllare i cavi ma sembra tutto collegato correttamente.', 'alta', 'in_attesa'),
(2, 'Problemi con la stampante', 'La stampante dell\'ufficio stampa fogli bianchi anche se invio documenti. Ho provato a spegnere e riaccendere ma il problema persiste.', 'media', 'in_lavorazione');

-- Password per entrambi gli account: password
