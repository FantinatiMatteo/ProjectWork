<?php
/**
 * Rollback: 001_create_users_table
 */

if (!isset($this->pdo)) {
    global $pdo;
    $pdo = $this->pdo;
}

$pdo->exec("DROP TABLE IF EXISTS users");
echo "✅ Users table dropped\n";
?>
