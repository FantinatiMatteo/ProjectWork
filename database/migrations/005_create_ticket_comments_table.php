<?php
/**
 * Migration: Create ticket_comments table
 * This table stores comments and communications for tickets
 */

class Migration005CreateTicketCommentsTable {
    
    public function up($pdo) {
        $sql = "
            CREATE TABLE IF NOT EXISTS ticket_comments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                ticket_id INT NOT NULL,
                user_id INT NOT NULL,
                comment TEXT NOT NULL,
                is_internal BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                
                FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                
                INDEX idx_ticket_comments_ticket_id (ticket_id),
                INDEX idx_ticket_comments_user_id (user_id),
                INDEX idx_ticket_comments_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        $pdo->exec($sql);
        echo "Created ticket_comments table\n";
        
        // Insert some sample comments for existing tickets
        $sample_comments = [
            [
                'ticket_id' => 1,
                'user_id' => 1, // admin user
                'comment' => 'Grazie per la segnalazione. Stiamo verificando il problema con l\'accesso al sistema.',
                'is_internal' => 0
            ],
            [
                'ticket_id' => 1,
                'user_id' => 1, // admin user
                'comment' => 'Controllo interno: verificare configurazione proxy aziendale.',
                'is_internal' => 1
            ],
            [
                'ticket_id' => 2,
                'user_id' => 2, // regular user
                'comment' => 'Il problema persiste anche dopo aver riavviato il computer.',
                'is_internal' => 0
            ]
        ];
        
        $stmt = $pdo->prepare("
            INSERT INTO ticket_comments (ticket_id, user_id, comment, is_internal, created_at)
            VALUES (?, ?, ?, ?, DATE_SUB(NOW(), INTERVAL FLOOR(RAND() * 30) DAY))
        ");
        
        foreach ($sample_comments as $comment) {
            $stmt->execute([
                $comment['ticket_id'],
                $comment['user_id'],
                $comment['comment'],
                $comment['is_internal']
            ]);
        }
        
        echo "Inserted sample ticket comments\n";
    }
    
    public function down($pdo) {
        $pdo->exec("DROP TABLE IF EXISTS ticket_comments");
        echo "Dropped ticket_comments table\n";
    }
}
