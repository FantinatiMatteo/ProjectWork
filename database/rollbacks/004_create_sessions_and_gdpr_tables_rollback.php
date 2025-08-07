<?php
/**
 * Rollback: 004_create_sessions_and_gdpr_tables
 */

if (!isset($this->pdo)) {
    global $pdo;
    $pdo = $this->pdo;
}

$pdo->exec("DROP TABLE IF EXISTS ticket_updates");
$pdo->exec("DROP TABLE IF EXISTS system_settings");
$pdo->exec("DROP TABLE IF EXISTS gdpr_requests");
$pdo->exec("DROP TABLE IF EXISTS user_sessions");
echo "✅ Session and GDPR tables dropped\n";
?>
