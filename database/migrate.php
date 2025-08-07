<?php
/**
 * Database Migration System for Ticketing System
 * Professional migration handler with rollback capabilities
 */

require_once __DIR__ . '/../config.php';

class DatabaseMigration {
    private $pdo;
    private $migrationTable = 'migrations';
    
    public function __construct() {
        $this->pdo = $this->createInitialConnection();
        $this->createMigrationsTable();
    }
    
    private function createInitialConnection() {
        try {
            // First, connect without specifying database to create it if needed
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
            
            // Create database if it doesn't exist
            $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE " . DB_NAME);
            
            return $pdo;
        } catch (PDOException $e) {
            die("Migration setup failed: " . $e->getMessage());
        }
    }
    
    private function createMigrationsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->migrationTable} (
            id INT PRIMARY KEY AUTO_INCREMENT,
            migration VARCHAR(255) NOT NULL UNIQUE,
            executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_migration (migration)
        )";
        
        $this->pdo->exec($sql);
    }
    
    public function runMigrations() {
        $migrationFiles = glob(__DIR__ . '/migrations/*.php');
        sort($migrationFiles);
        
        foreach ($migrationFiles as $file) {
            $migrationName = basename($file, '.php');
            
            if (!$this->isMigrationExecuted($migrationName)) {
                echo "Running migration: {$migrationName}\n";
                
                try {
                    $this->pdo->beginTransaction();
                    
                    require $file;
                    $this->markMigrationAsExecuted($migrationName);
                    
                    $this->pdo->commit();
                    echo "✅ Migration {$migrationName} completed successfully\n";
                } catch (Exception $e) {
                    $this->pdo->rollBack();
                    echo "❌ Migration {$migrationName} failed: " . $e->getMessage() . "\n";
                    break;
                }
            } else {
                echo "⏭️ Migration {$migrationName} already executed\n";
            }
        }
    }
    
    private function isMigrationExecuted($migrationName) {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM {$this->migrationTable} WHERE migration = ?");
        $stmt->execute([$migrationName]);
        return $stmt->fetchColumn() > 0;
    }
    
    private function markMigrationAsExecuted($migrationName) {
        $stmt = $this->pdo->prepare("INSERT INTO {$this->migrationTable} (migration) VALUES (?)");
        $stmt->execute([$migrationName]);
    }
    
    public function rollback($steps = 1) {
        $stmt = $this->pdo->prepare("
            SELECT migration FROM {$this->migrationTable} 
            ORDER BY executed_at DESC 
            LIMIT ?
        ");
        $stmt->execute([$steps]);
        
        $migrations = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($migrations as $migration) {
            $rollbackFile = __DIR__ . '/rollbacks/' . $migration . '_rollback.php';
            
            if (file_exists($rollbackFile)) {
                echo "Rolling back migration: {$migration}\n";
                
                try {
                    $this->pdo->beginTransaction();
                    
                    require $rollbackFile;
                    
                    $stmt = $this->pdo->prepare("DELETE FROM {$this->migrationTable} WHERE migration = ?");
                    $stmt->execute([$migration]);
                    
                    $this->pdo->commit();
                    echo "✅ Rollback {$migration} completed successfully\n";
                } catch (Exception $e) {
                    $this->pdo->rollBack();
                    echo "❌ Rollback {$migration} failed: " . $e->getMessage() . "\n";
                    break;
                }
            } else {
                echo "⚠️ Rollback file not found for migration: {$migration}\n";
            }
        }
    }
}

// CLI interface
if (php_sapi_name() === 'cli') {
    $migration = new DatabaseMigration();
    
    $command = $argv[1] ?? 'migrate';
    
    switch ($command) {
        case 'migrate':
            echo "🚀 Starting database migrations...\n";
            $migration->runMigrations();
            echo "✨ Migration process completed!\n";
            break;
            
        case 'rollback':
            $steps = (int)($argv[2] ?? 1);
            echo "🔄 Rolling back {$steps} migration(s)...\n";
            $migration->rollback($steps);
            echo "✨ Rollback process completed!\n";
            break;
            
        default:
            echo "Usage: php migrate.php [migrate|rollback] [steps]\n";
            echo "  migrate: Run pending migrations\n";
            echo "  rollback [steps]: Rollback last N migrations (default: 1)\n";
            break;
    }
}
?>
