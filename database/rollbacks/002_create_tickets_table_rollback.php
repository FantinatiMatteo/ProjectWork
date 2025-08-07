<?php
/**
 * Rollback: 002_create_tickets_table
 */

if (!isset($this->pdo)) {
    global $pdo;
    $pdo = $this->pdo;
}

$pdo->exec("DROP TRIGGER IF EXISTS generate_ticket_number");
$pdo->exec("DROP TABLE IF EXISTS tickets");
echo "✅ Tickets table and trigger dropped\n";
?>
