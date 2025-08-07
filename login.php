<?php
/**
 * IT Support Ticketing System - Login Page
 * Secure authentication with advanced security features
 */
require_once 'config.php';

// Se già loggato, redirect alla dashboard appropriata
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: admin_dashboard.php');
    } else {
        header('Location: user_dashboard.php');
    }
    exit;
}

$error = '';
$success = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Protection
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = 'Token di sicurezza non valido. Riprova.';
    } else {
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'] ?? '';
        
        if ($email && $password) {
            try {
                $pdo = getDBConnection();
                
                // Check if user exists and get user data
                $stmt = $pdo->prepare("
                    SELECT id, email, password_hash, first_name, last_name, role, 
                           failed_login_attempts, locked_until, is_active, email_verified 
                    FROM users 
                    WHERE email = ?
                ");
                $stmt->execute([$email]);
                $user = $stmt->fetch();
                
                if ($user) {
                    // Check if account is active
                    if (!$user['is_active']) {
                        $error = 'Account disattivato. Contatta l\'amministratore.';
                        logSecurityEvent('login_failed', "Inactive account login attempt: $email");
                    }
                    // Check if account is locked
                    elseif ($user['locked_until'] && new DateTime() < new DateTime($user['locked_until'])) {
                        $locktime = new DateTime($user['locked_until']);
                        $remaining = $locktime->diff(new DateTime())->format('%i minuti');
                        $error = "Account temporaneamente bloccato. Riprova tra $remaining.";
                        logSecurityEvent('login_failed', "Locked account login attempt: $email");
                    }
                    // Check if email is verified
                    elseif (!$user['email_verified']) {
                        $error = 'Email non verificata. Controlla la tua casella di posta.';
                        logSecurityEvent('login_failed', "Unverified email login attempt: $email");
                    }
                    // Verify password
                    elseif (password_verify($password, $user['password_hash'])) {
                        // Login successful!
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['email'] = $user['email'];
                        $_SESSION['first_name'] = $user['first_name'];
                        $_SESSION['last_name'] = $user['last_name'];
                        $_SESSION['role'] = $user['role'];
                        $_SESSION['login_time'] = time();
                        
                        // Generate new session ID for security
                        session_regenerate_id(true);
                        
                        // Reset failed attempts
                        $stmt = $pdo->prepare("
                            UPDATE users 
                            SET failed_login_attempts = 0, locked_until = NULL, 
                                last_login = NOW(), last_login_ip = ? 
                            WHERE id = ?
                        ");
                        $stmt->execute([$_SERVER['REMOTE_ADDR'], $user['id']]);
                        
                        // Create secure session record
                        $sessionId = session_id();
                        $userAgentHash = hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? '');
                        $expiresAt = date('Y-m-d H:i:s', time() + SESSION_TIMEOUT);
                        
                        $stmt = $pdo->prepare("
                            INSERT INTO user_sessions (id, user_id, ip_address, user_agent_hash, expires_at) 
                            VALUES (?, ?, ?, ?, ?)
                            ON DUPLICATE KEY UPDATE 
                            last_activity = NOW(), expires_at = ?
                        ");
                        $stmt->execute([$sessionId, $user['id'], $_SERVER['REMOTE_ADDR'], $userAgentHash, $expiresAt, $expiresAt]);
                        
                        // Log successful login
                        logSecurityEvent('login_success', "Successful login for user: $email", $user['id']);
                        
                        // Redirect based on role
                        if ($user['role'] === 'admin') {
                            header('Location: admin_dashboard.php');
                        } else {
                            header('Location: user_dashboard.php');
                        }
                        exit;
                    } else {
                        // Invalid password - increment failed attempts
                        $failed_attempts = $user['failed_login_attempts'] + 1;
                        $locked_until = null;
                        
                        if ($failed_attempts >= MAX_LOGIN_ATTEMPTS) {
                            $locked_until = date('Y-m-d H:i:s', time() + LOCKOUT_TIME);
                            $error = 'Troppi tentativi falliti. Account bloccato per ' . (LOCKOUT_TIME/60) . ' minuti.';
                        } else {
                            $remaining = MAX_LOGIN_ATTEMPTS - $failed_attempts;
                            $error = "Credenziali non valide. Tentativi rimasti: $remaining";
                        }
                        
                        $stmt = $pdo->prepare("
                            UPDATE users 
                            SET failed_login_attempts = ?, locked_until = ? 
                            WHERE id = ?
                        ");
                        $stmt->execute([$failed_attempts, $locked_until, $user['id']]);
                        
                        // Log failed login
                        logSecurityEvent('login_failed', "Invalid password for user: $email", $user['id']);
                        
                        // Also log in login_attempts table
                        $stmt = $pdo->prepare("
                            INSERT INTO login_attempts (email, ip_address, success, failure_reason, user_agent) 
                            VALUES (?, ?, FALSE, 'invalid_password', ?)
                        ");
                        $stmt->execute([$email, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT'] ?? '']);
                    }
                } else {
                    // User not found
                    $error = 'Credenziali non valide.';
                    
                    // Log failed login attempt
                    logSecurityEvent('login_failed', "Login attempt with non-existent email: $email");
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO login_attempts (email, ip_address, success, failure_reason, user_agent) 
                        VALUES (?, ?, FALSE, 'user_not_found', ?)
                    ");
                    $stmt->execute([$email, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT'] ?? '']);
                }
            } catch (PDOException $e) {
                error_log("Login error: " . $e->getMessage());
                $error = 'Errore del sistema. Riprova più tardi.';
                logSecurityEvent('system_error', "Database error during login: " . $e->getMessage());
            }
        } else {
            $error = 'Inserisci email e password.';
        }
    }
}

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo SYSTEM_NAME; ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #4f46e5;
            --primary-dark: #3730a3;
            --secondary-color: #6366f1;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
            --dark-color: #1f2937;
            --light-color: #f8fafc;
        }
        
        * {
            font-family: 'Inter', sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .login-container {
            max-width: 450px;
            width: 100%;
            margin: 20px;
        }
        
        .login-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            backdrop-filter: blur(10px);
        }
        
        .login-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .login-header h1 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
        }
        
        .login-header p {
            margin: 0.5rem 0 0 0;
            opacity: 0.9;
            font-size: 0.95rem;
        }
        
        .login-body {
            padding: 2rem;
        }
        
        .form-floating {
            margin-bottom: 1.5rem;
        }
        
        .form-control {
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 1rem;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(79, 70, 229, 0.1);
        }
        
        .btn-login {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: 12px;
            padding: 1rem;
            font-size: 1rem;
            font-weight: 600;
            width: 100%;
            color: white;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(79, 70, 229, 0.3);
            color: white;
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .alert {
            border: none;
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .alert-danger {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger-color);
            border-left: 4px solid var(--danger-color);
        }
        
        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
            border-left: 4px solid var(--success-color);
        }
        
        .login-footer {
            background: #f8fafc;
            padding: 1.5rem 2rem;
            text-align: center;
            color: #6b7280;
            font-size: 0.9rem;
        }
        
        .demo-accounts {
            background: rgba(79, 70, 229, 0.05);
            border-radius: 12px;
            padding: 1rem;
            margin-top: 1rem;
            border-left: 4px solid var(--primary-color);
        }
        
        .demo-accounts h6 {
            color: var(--primary-color);
            margin-bottom: 0.5rem;
            font-weight: 600;
        }
        
        .demo-accounts small {
            color: #6b7280;
            display: block;
            margin-bottom: 0.3rem;
        }
        
        .loading-spinner {
            display: none;
        }
        
        .btn-login.loading .loading-spinner {
            display: inline-block;
        }
        
        .btn-login.loading .btn-text {
            display: none;
        }
        
        @media (max-width: 576px) {
            .login-container {
                margin: 10px;
            }
            
            .login-header, .login-body {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <i class="fas fa-headset fa-2x mb-3"></i>
                <h1><?php echo SYSTEM_NAME; ?></h1>
                <p>Sistema Professionale di Ticketing IT</p>
            </div>
            
            <div class="login-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="" id="loginForm" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    
                    <div class="form-floating">
                        <input type="email" class="form-control" id="email" name="email" 
                               placeholder="nome@esempio.com" required 
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                        <label for="email">
                            <i class="fas fa-envelope me-2"></i>Indirizzo Email
                        </label>
                        <div class="invalid-feedback">
                            Inserisci un indirizzo email valido.
                        </div>
                    </div>
                    
                    <div class="form-floating">
                        <input type="password" class="form-control" id="password" name="password" 
                               placeholder="Password" required minlength="6">
                        <label for="password">
                            <i class="fas fa-lock me-2"></i>Password
                        </label>
                        <div class="invalid-feedback">
                            La password deve essere di almeno 6 caratteri.
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-login">
                        <span class="btn-text">
                            <i class="fas fa-sign-in-alt me-2"></i>Accedi al Sistema
                        </span>
                        <span class="loading-spinner">
                            <i class="fas fa-spinner fa-spin me-2"></i>Autenticazione...
                        </span>
                    </button>
                </form>
                
                <!-- Demo Accounts Info -->
                <div class="demo-accounts">
                    <h6><i class="fas fa-info-circle me-2"></i>Account Demo</h6>
                    <small><strong>Admin:</strong> admin@ticketing.local / Admin@123!</small>
                    <small><strong>Utente:</strong> user@ticketing.local / User@123!</small>
                </div>
            </div>
            
            <div class="login-footer">
                <p class="mb-0">
                    <i class="fas fa-shield-alt me-1"></i>
                    Sistema sicuro con autenticazione a due fattori
                </p>
                <small class="text-muted">
                    © <?php echo date('Y'); ?> <?php echo COMPANY_NAME; ?> - Tutti i diritti riservati
                </small>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('loginForm');
            const submitBtn = form.querySelector('.btn-login');
            
            // Form validation
            form.addEventListener('submit', function(e) {
                if (!form.checkValidity()) {
                    e.preventDefault();
                    e.stopPropagation();
                }
                
                form.classList.add('was-validated');
                
                if (form.checkValidity()) {
                    // Show loading state
                    submitBtn.classList.add('loading');
                    submitBtn.disabled = true;
                }
            });
            
            // Real-time validation
            const inputs = form.querySelectorAll('input[required]');
            inputs.forEach(input => {
                input.addEventListener('blur', function() {
                    if (input.checkValidity()) {
                        input.classList.remove('is-invalid');
                        input.classList.add('is-valid');
                    } else {
                        input.classList.remove('is-valid');
                        input.classList.add('is-invalid');
                    }
                });
            });
            
            // Auto-focus on first input
            document.getElementById('email').focus();
            
            // Prevent form resubmission on page refresh
            if (window.history.replaceState) {
                window.history.replaceState(null, null, window.location.href);
            }
        });
        
        // Security: Clear form data on page unload
        window.addEventListener('beforeunload', function() {
            document.getElementById('password').value = '';
        });
    </script>
</body>
</html>
