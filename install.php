#!/usr/bin/env php
<?php
/**
 * IT Support Ticketing System - Installation Script
 * 
 * This script sets up the complete ticketing system including:
 * - Database creation and migration
 * - Default user accounts
 * - Security configuration
 * - File structure setup
 */

echo "🎯 IT Support Ticketing System - Professional Installation\n";
echo "================================================================\n\n";

// Check PHP version
if (version_compare(PHP_VERSION, '7.4.0') < 0) {
    die("❌ PHP 7.4 or higher is required. Current version: " . PHP_VERSION . "\n");
}

// Check required extensions
$requiredExtensions = ['pdo', 'pdo_mysql', 'json', 'openssl', 'mbstring'];
$missingExtensions = [];

foreach ($requiredExtensions as $ext) {
    if (!extension_loaded($ext)) {
        $missingExtensions[] = $ext;
    }
}

if (!empty($missingExtensions)) {
    die("❌ Missing required PHP extensions: " . implode(', ', $missingExtensions) . "\n");
}

echo "✅ PHP version and extensions check passed\n";

// Load configuration
require_once __DIR__ . '/config.php';

// Create necessary directories
$directories = [
    __DIR__ . '/uploads',
    __DIR__ . '/uploads/tickets',
    __DIR__ . '/logs',
    __DIR__ . '/cache',
    __DIR__ . '/assets/css',
    __DIR__ . '/assets/js',
    __DIR__ . '/assets/images',
    __DIR__ . '/includes'
];

echo "\n📁 Creating directory structure...\n";
foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        if (mkdir($dir, 0755, true)) {
            echo "   ✅ Created: " . basename($dir) . "\n";
        } else {
            die("   ❌ Failed to create: " . basename($dir) . "\n");
        }
    } else {
        echo "   ⏭️ Already exists: " . basename($dir) . "\n";
    }
}

// Set proper permissions
echo "\n🔒 Setting file permissions...\n";
chmod(__DIR__ . '/uploads', 0755);
chmod(__DIR__ . '/logs', 0755);
chmod(__DIR__ . '/cache', 0755);
echo "   ✅ Upload, logs, and cache directories secured\n";

// Run database migrations
echo "\n🗄️ Setting up database...\n";
try {
    require_once __DIR__ . '/database/migrate.php';
    $migration = new DatabaseMigration();
    $migration->runMigrations();
    echo "   ✅ Database setup completed successfully\n";
} catch (Exception $e) {
    die("   ❌ Database setup failed: " . $e->getMessage() . "\n");
}

// Create .htaccess for security
$htaccessContent = "
# IT Support Ticketing System - Security Configuration
RewriteEngine On

# Deny access to sensitive files
<Files ~ \"^(config\.php|\.env|database\.sql)$\">
    Require all denied
</Files>

# Deny access to directories
RedirectMatch 403 ^/database/.*$
RedirectMatch 403 ^/logs/.*$
RedirectMatch 403 ^/cache/.*$

# Force HTTPS (uncomment for production)
# RewriteCond %{HTTPS} off
# RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Security headers
Header always set X-Content-Type-Options nosniff
Header always set X-Frame-Options DENY
Header always set X-XSS-Protection \"1; mode=block\"
Header always set Strict-Transport-Security \"max-age=31536000; includeSubDomains\"
Header always set Referrer-Policy \"strict-origin-when-cross-origin\"

# Hide server information
ServerTokens Prod
Header unset Server
";

file_put_contents(__DIR__ . '/.htaccess', $htaccessContent);
echo "\n🛡️ Security configuration (.htaccess) created\n";

// Create environment configuration template
$envContent = "
# IT Support Ticketing System - Environment Configuration
# Copy this file to .env and configure for your environment

# Database Configuration
DB_HOST=localhost
DB_USER=root
DB_PASS=
DB_NAME=ticketing_system

# Email Configuration (for notifications)
SMTP_HOST=localhost
SMTP_PORT=587
SMTP_USERNAME=
SMTP_PASSWORD=
SMTP_FROM_EMAIL=noreply@company.com

# Security Settings
SESSION_TIMEOUT=3600
MAX_LOGIN_ATTEMPTS=5
LOCKOUT_TIME=900

# System Settings
SYSTEM_NAME=\"IT Support Ticketing System\"
COMPANY_NAME=\"Your Company Name\"
SUPPORT_EMAIL=support@company.com

# Development/Production Mode
ENVIRONMENT=development
DEBUG_MODE=true
";

file_put_contents(__DIR__ . '/.env.example', $envContent);
echo "   ✅ Environment configuration template created (.env.example)\n";

// Generate sample README
$readmeContent = "
# IT Support Ticketing System

A professional, secure ticketing system for IT support departments.

## Features

- ✅ Secure user authentication with role-based access
- 🎫 Comprehensive ticket management system
- 🔒 Advanced security logging and monitoring
- 📊 Admin dashboard with analytics
- 🛡️ GDPR compliance features
- 📱 Responsive design
- 🔍 Advanced search functionality

## Default Accounts

After installation, you can login with:

**Administrator:**
- Email: admin@ticketing.local
- Password: Admin@123!

**Regular User:**
- Email: user@ticketing.local  
- Password: User@123!

⚠️ **IMPORTANT:** Change these passwords immediately after first login!

## Security Features

- Password hashing with PHP's password_hash()
- Session hijacking prevention
- CSRF token protection
- SQL injection prevention with prepared statements
- XSS protection with output encoding
- Failed login attempt monitoring
- Account lockout mechanism
- Comprehensive security logging

## GDPR Compliance

- User consent tracking
- Data retention policies
- Right to data export
- Right to data deletion
- Processing deadline tracking

## Installation

1. Run the installation script: `php install.php`
2. Configure your web server to point to this directory
3. Access the system via your web browser
4. Login with default credentials and change passwords

## File Structure

```
ProjectWork/
├── index.php              # Main entry point
├── config.php             # System configuration
├── install.php            # Installation script
├── database/              # Database migrations
├── includes/              # Core PHP classes
├── assets/                # CSS, JS, images
├── uploads/               # File uploads
└── logs/                  # System logs
```

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- Required PHP extensions: pdo, pdo_mysql, json, openssl, mbstring

## License

MIT License - Feel free to use and modify as needed.
";

file_put_contents(__DIR__ . '/README.md', $readmeContent);

echo "\n📚 Documentation (README.md) created\n";

// Installation complete
echo "\n🎉 Installation completed successfully!\n";
echo "================================================================\n";
echo "🌐 Access your ticketing system at: http://localhost/ProjectWork\n";
echo "📧 Admin login: admin@ticketing.local / Admin@123!\n";
echo "👤 User login: user@ticketing.local / User@123!\n";
echo "\n⚠️  IMPORTANT NEXT STEPS:\n";
echo "1. Change default passwords immediately\n";
echo "2. Configure email settings in config.php\n";
echo "3. Review security settings\n";
echo "4. Set up SSL/HTTPS for production\n";
echo "5. Configure regular database backups\n";
echo "\n✨ Your professional ticketing system is ready to use!\n";
?>
