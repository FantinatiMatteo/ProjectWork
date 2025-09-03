<?php
/**
 * Migration: 006_add_privacy_consent_field
 * Adds privacy_consent_date field to users table for GDPR compliance
 */

class Migration006AddPrivacyConsentField {
    
    public function up($pdo) {
        echo "Adding privacy_consent_date field to users table...\n";
        
        // Add privacy_consent_date field to users table
        $pdo->exec("
            ALTER TABLE users 
            ADD COLUMN privacy_consent_date DATETIME NULL AFTER created_at
        ");
        
        // Update existing users with current timestamp for privacy consent
        // (assuming they implicitly consented by registering before this update)
        $pdo->exec("
            UPDATE users 
            SET privacy_consent_date = created_at 
            WHERE privacy_consent_date IS NULL
        ");
        
        echo "Added privacy_consent_date field to users table\n";
        echo "Updated existing users with consent date\n";
    }
    
    public function down($pdo) {
        echo "Removing privacy_consent_date field from users table...\n";
        
        $pdo->exec("
            ALTER TABLE users 
            DROP COLUMN privacy_consent_date
        ");
        
        echo "Removed privacy_consent_date field from users table\n";
    }
}

// Se eseguito direttamente
if (basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
    require_once __DIR__ . '/../../config.php';
    
    try {
        $migration = new Migration006AddPrivacyConsentField();
        $migration->up($pdo);
        echo "Migration 006 completed successfully!\n";
    } catch (Exception $e) {
        echo "Migration 006 failed: " . $e->getMessage() . "\n";
    }
}
?>
