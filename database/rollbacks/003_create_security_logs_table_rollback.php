<?php
/**
 * Rollback: 003_create_security_logs_table
 */

if (!isset($this->pdo)) {
    global $pdo;
    $pdo = $this->pdo;
}

$pdo->exec("DROP TABLE IF EXISTS login_attempts");
$pdo->exec("DROP TABLE IF EXISTS security_logs");
echo "✅ Security logging tables dropped\n";
?>
