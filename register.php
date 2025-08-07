<?php
/**
 * IT Support Ticketing System - User Registration
 * Secure user registration with GDPR compliance
 */
require_once 'config.php';

// Redirect if already logged in
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

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Protection
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = 'Token di sicurezza non valido. Riprova.';
    } else {
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $gdpr_consent = isset($_POST['gdpr_consent']);
        $terms_consent = isset($_POST['terms_consent']);
        
        // Validation
        if (empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
            $error = 'Tutti i campi sono obbligatori.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Indirizzo email non valido.';
        } elseif (strlen($password) < 8) {
            $error = 'La password deve essere di almeno 8 caratteri.';
        } elseif ($password !== $confirm_password) {
            $error = 'Le password non coincidono.';
        } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/', $password)) {
            $error = 'La password deve contenere almeno: una lettera minuscola, una maiuscola, un numero e un carattere speciale.';
        } elseif (!$gdpr_consent) {
            $error = 'È necessario accettare l\'informativa sulla privacy per registrarsi.';
        } elseif (!$terms_consent) {
            $error = 'È necessario accettare i termini e condizioni per registrarsi.';
        } else {
            try {
                $pdo = getDBConnection();
                
                // Check if email already exists
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    $error = 'Questo indirizzo email è già registrato.';
                } else {
                    // Create new user
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    $email_verification_token = bin2hex(random_bytes(32));
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO users (
                            email, password_hash, first_name, last_name, 
                            email_verification_token, gdpr_consent, gdpr_consent_date, gdpr_consent_ip
                        ) VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)
                    ");
                    
                    $stmt->execute([
                        $email, 
                        $password_hash, 
                        $first_name, 
                        $last_name, 
                        $email_verification_token,
                        1, // gdpr_consent
                        $_SERVER['REMOTE_ADDR']
                    ]);
                    
                    $user_id = $pdo->lastInsertId();
                    
                    // Log registration
                    logSecurityEvent('user_registered', "New user registration: $email", $user_id);
                    
                    // In a real application, you would send an email verification here
                    // For demo purposes, we'll auto-verify
                    $stmt = $pdo->prepare("UPDATE users SET email_verified = TRUE WHERE id = ?");
                    $stmt->execute([$user_id]);
                    
                    $success = 'Registrazione completata con successo! Puoi ora accedere al sistema.';
                    
                    // Clear form data
                    $_POST = [];
                }
            } catch (PDOException $e) {
                error_log("Registration error: " . $e->getMessage());
                $error = 'Errore durante la registrazione. Riprova più tardi.';
            }
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
    <title>Registrazione - <?php echo SYSTEM_NAME; ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #10b981;
            --primary-dark: #059669;
            --secondary-color: #34d399;
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
            padding: 2rem 0;
        }
        
        .register-container {
            max-width: 600px;
            width: 100%;
            margin: 20px;
        }
        
        .register-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
            overflow: hidden;
        }
        
        .register-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .register-header h1 {
            margin: 0;
            font-size: 1.8rem;
            font-weight: 600;
        }
        
        .register-header p {
            margin: 0.5rem 0 0 0;
            opacity: 0.9;
            font-size: 0.95rem;
        }
        
        .register-body {
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
            box-shadow: 0 0 0 0.2rem rgba(16, 185, 129, 0.1);
        }
        
        .form-control.is-valid {
            border-color: var(--success-color);
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 8 8'%3e%3cpath fill='%2310b981' d='m2.3 6.73.02-.02 1.7-2 1.88-2.24A.75.75 0 0 1 7.08 3.3L4.9 5.8l-1.7 2c-.13.15-.33.15-.46 0l-.85-1c-.12-.14-.12-.36 0-.5s.33-.15.46 0l.58.68z'/%3e%3c/svg%3e");
        }
        
        .form-control.is-invalid {
            border-color: var(--danger-color);
        }
        
        .password-requirements {
            background: #f8fafc;
            border-radius: 8px;
            padding: 1rem;
            margin: 1rem 0;
            border-left: 4px solid var(--primary-color);
        }
        
        .requirement {
            font-size: 0.9rem;
            margin-bottom: 0.25rem;
            transition: color 0.3s ease;
        }
        
        .requirement.met {
            color: var(--success-color);
        }
        
        .requirement.not-met {
            color: #6b7280;
        }
        
        .btn-register {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: 12px;
            padding: 1rem;
            font-size: 1rem;
            font-weight: 600;
            width: 100%;
            color: white;
            transition: all 0.3s ease;
        }
        
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(16, 185, 129, 0.3);
            color: white;
        }
        
        .btn-register:disabled {
            opacity: 0.6;
            transform: none;
        }
        
        .form-check {
            margin-bottom: 1.5rem;
        }
        
        .form-check-input:checked {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .form-check-input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(16, 185, 129, 0.25);
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
        
        .register-footer {
            background: #f8fafc;
            padding: 1.5rem 2rem;
            text-align: center;
            color: #6b7280;
            font-size: 0.9rem;
        }
        
        .register-footer a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }
        
        .register-footer a:hover {
            text-decoration: underline;
        }
        
        .gdpr-notice {
            background: rgba(16, 185, 129, 0.05);
            border: 1px solid rgba(16, 185, 129, 0.2);
            border-radius: 12px;
            padding: 1rem;
            margin: 1rem 0;
            font-size: 0.9rem;
        }
        
        @media (max-width: 576px) {
            .register-container {
                margin: 10px;
            }
            
            .register-header, .register-body {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-card">
            <div class="register-header">
                <i class="fas fa-user-plus fa-2x mb-3"></i>
                <h1>Registrazione Utente</h1>
                <p>Crea il tuo account per accedere al sistema di ticketing</p>
            </div>
            
            <div class="register-body">
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
                        <div class="mt-2">
                            <a href="login.php" class="btn btn-success btn-sm">
                                <i class="fas fa-sign-in-alt me-1"></i>Accedi Ora
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <form method="POST" id="registrationForm" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="first_name" name="first_name" 
                                           placeholder="Nome" required pattern="[A-Za-zÀ-ÿ\s]{2,50}"
                                           value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>">
                                    <label for="first_name">
                                        <i class="fas fa-user me-2"></i>Nome
                                    </label>
                                    <div class="invalid-feedback">
                                        Inserisci un nome valido (2-50 caratteri).
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="last_name" name="last_name" 
                                           placeholder="Cognome" required pattern="[A-Za-zÀ-ÿ\s]{2,50}"
                                           value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>">
                                    <label for="last_name">
                                        <i class="fas fa-user me-2"></i>Cognome
                                    </label>
                                    <div class="invalid-feedback">
                                        Inserisci un cognome valido (2-50 caratteri).
                                    </div>
                                </div>
                            </div>
                        </div>
                        
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
                                   placeholder="Password" required minlength="8">
                            <label for="password">
                                <i class="fas fa-lock me-2"></i>Password
                            </label>
                            <div class="invalid-feedback">
                                La password deve rispettare i requisiti di sicurezza.
                            </div>
                        </div>
                        
                        <div class="password-requirements" id="passwordRequirements">
                            <h6><i class="fas fa-shield-alt me-2"></i>Requisiti Password:</h6>
                            <div class="requirement not-met" id="req-length">
                                <i class="fas fa-circle me-2"></i>Almeno 8 caratteri
                            </div>
                            <div class="requirement not-met" id="req-lowercase">
                                <i class="fas fa-circle me-2"></i>Una lettera minuscola
                            </div>
                            <div class="requirement not-met" id="req-uppercase">
                                <i class="fas fa-circle me-2"></i>Una lettera maiuscola
                            </div>
                            <div class="requirement not-met" id="req-number">
                                <i class="fas fa-circle me-2"></i>Un numero
                            </div>
                            <div class="requirement not-met" id="req-special">
                                <i class="fas fa-circle me-2"></i>Un carattere speciale (@$!%*?&)
                            </div>
                        </div>
                        
                        <div class="form-floating">
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                   placeholder="Conferma Password" required>
                            <label for="confirm_password">
                                <i class="fas fa-lock me-2"></i>Conferma Password
                            </label>
                            <div class="invalid-feedback">
                                Le password non coincidono.
                            </div>
                        </div>
                        
                        <div class="gdpr-notice">
                            <h6><i class="fas fa-shield-alt me-2"></i>Informativa Privacy (GDPR)</h6>
                            <p class="mb-0">
                                I tuoi dati personali saranno utilizzati esclusivamente per la gestione del tuo account 
                                e per fornirti assistenza tecnica. Non condivideremo i tuoi dati con terze parti senza 
                                il tuo consenso esplicito. Hai il diritto di accedere, modificare o eliminare i tuoi dati 
                                in qualsiasi momento.
                            </p>
                        </div>
                        
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="gdpr_consent" name="gdpr_consent" required>
                            <label class="form-check-label" for="gdpr_consent">
                                <i class="fas fa-shield-alt me-2"></i>
                                Accetto l'<strong>informativa sulla privacy</strong> e acconsento al trattamento 
                                dei miei dati personali secondo il GDPR.
                            </label>
                            <div class="invalid-feedback">
                                È necessario accettare l'informativa sulla privacy.
                            </div>
                        </div>
                        
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="terms_consent" name="terms_consent" required>
                            <label class="form-check-label" for="terms_consent">
                                <i class="fas fa-file-contract me-2"></i>
                                Accetto i <strong>termini e condizioni</strong> del servizio.
                            </label>
                            <div class="invalid-feedback">
                                È necessario accettare i termini e condizioni.
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-register" id="submitBtn">
                            <i class="fas fa-user-plus me-2"></i>Crea Account
                        </button>
                    </form>
                <?php endif; ?>
            </div>
            
            <div class="register-footer">
                <p class="mb-0">
                    Hai già un account? 
                    <a href="login.php">
                        <i class="fas fa-sign-in-alt me-1"></i>Accedi qui
                    </a>
                </p>
                <small class="text-muted mt-2 d-block">
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
            const form = document.getElementById('registrationForm');
            const passwordInput = document.getElementById('password');
            const confirmPasswordInput = document.getElementById('confirm_password');
            const submitBtn = document.getElementById('submitBtn');
            
            // Password requirement validation
            passwordInput.addEventListener('input', function() {
                const password = this.value;
                
                // Check each requirement
                updateRequirement('req-length', password.length >= 8);
                updateRequirement('req-lowercase', /[a-z]/.test(password));
                updateRequirement('req-uppercase', /[A-Z]/.test(password));
                updateRequirement('req-number', /\d/.test(password));
                updateRequirement('req-special', /[@$!%*?&]/.test(password));
                
                // Update input validity
                if (isPasswordValid(password)) {
                    this.classList.remove('is-invalid');
                    this.classList.add('is-valid');
                } else {
                    this.classList.remove('is-valid');
                    if (password.length > 0) {
                        this.classList.add('is-invalid');
                    }
                }
                
                // Check password confirmation
                validatePasswordConfirmation();
            });
            
            // Confirm password validation
            confirmPasswordInput.addEventListener('input', validatePasswordConfirmation);
            
            function updateRequirement(id, met) {
                const element = document.getElementById(id);
                const icon = element.querySelector('i');
                
                if (met) {
                    element.classList.remove('not-met');
                    element.classList.add('met');
                    icon.className = 'fas fa-check-circle me-2';
                } else {
                    element.classList.remove('met');
                    element.classList.add('not-met');
                    icon.className = 'fas fa-circle me-2';
                }
            }
            
            function isPasswordValid(password) {
                return password.length >= 8 &&
                       /[a-z]/.test(password) &&
                       /[A-Z]/.test(password) &&
                       /\d/.test(password) &&
                       /[@$!%*?&]/.test(password);
            }
            
            function validatePasswordConfirmation() {
                const password = passwordInput.value;
                const confirmPassword = confirmPasswordInput.value;
                
                if (confirmPassword.length > 0) {
                    if (password === confirmPassword) {
                        confirmPasswordInput.classList.remove('is-invalid');
                        confirmPasswordInput.classList.add('is-valid');
                    } else {
                        confirmPasswordInput.classList.remove('is-valid');
                        confirmPasswordInput.classList.add('is-invalid');
                    }
                }
            }
            
            // Form submission validation
            form.addEventListener('submit', function(e) {
                if (!form.checkValidity()) {
                    e.preventDefault();
                    e.stopPropagation();
                }
                
                form.classList.add('was-validated');
                
                if (form.checkValidity()) {
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Creazione Account...';
                    submitBtn.disabled = true;
                }
            });
            
            // Real-time validation for other inputs
            const inputs = form.querySelectorAll('input[required]');
            inputs.forEach(input => {
                input.addEventListener('blur', function() {
                    if (input.type !== 'password') {
                        if (input.checkValidity()) {
                            input.classList.remove('is-invalid');
                            input.classList.add('is-valid');
                        } else {
                            input.classList.remove('is-valid');
                            input.classList.add('is-invalid');
                        }
                    }
                });
            });
        });
    </script>
</body>
</html>
