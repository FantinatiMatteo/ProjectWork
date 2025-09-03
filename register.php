<?php
require_once 'config.php';

if (isLoggedIn()) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: admin_dashboard.php');
    } else {
        header('Location: user_dashboard.php');
    }
    exit;
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $privacy_consent = isset($_POST['privacy_consent']);

    if (empty($email) || empty($password) || empty($first_name) || empty($last_name)) {
        $error = 'Tutti i campi sono obbligatori';
    } elseif (!$privacy_consent) {
        $error = 'Devi accettare l\'informativa sulla privacy per registrarti';
    } elseif ($password !== $confirm_password) {
        $error = 'Le password non coincidono';
    } elseif (strlen($password) < 6) {
        $error = 'La password deve essere di almeno 6 caratteri';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'Questa email è già registrata';
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (email, password_hash, first_name, last_name, role, privacy_consent_date) VALUES (?, ?, ?, ?, 'user', NOW())");
            
            if ($stmt->execute([$email, $password_hash, $first_name, $last_name])) {
                // Log consenso privacy per GDPR
                try {
                    $log_stmt = $pdo->prepare("INSERT INTO security_logs (user_email, action, ip_address, user_agent, details) VALUES (?, 'privacy_consent', ?, ?, ?)");
                    $log_stmt->execute([
                        $email, 
                        $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                        $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                        json_encode(['consent_date' => date('Y-m-d H:i:s'), 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'])
                    ]);
                } catch (Exception $e) {
                    error_log("Errore log consenso privacy: " . $e->getMessage());
                }
                
                $success = 'Registrazione completata! Puoi ora fare il login.';
            } else {
                $error = 'Errore durante la registrazione. Riprova.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrati - TicketingIT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #64748b;
            --success-color: #10b981;
            --gray-50: #f8fafc;
            --gray-100: #f1f5f9;
            --gray-900: #0f172a;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, var(--primary-color) 0%, #3b82f6 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 0;
        }

        .register-container {
            background: white;
            border-radius: 20px;
            padding: 3rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 500px;
            position: relative;
            overflow: hidden;
        }

        .register-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), #3b82f6);
        }

        .brand-section {
            text-align: center;
            margin-bottom: 2rem;
        }

        .brand-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--primary-color), #3b82f6);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            color: white;
            font-size: 2rem;
        }

        .brand-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 0.5rem;
        }

        .brand-subtitle {
            color: var(--secondary-color);
            font-size: 0.95rem;
        }

        .form-floating {
            margin-bottom: 1.5rem;
        }

        .form-control {
            border: 2px solid var(--gray-100);
            border-radius: 12px;
            padding: 1rem;
            font-size: 1rem;
            transition: all 0.2s ease;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(37, 99, 235, 0.15);
        }

        .btn-register {
            background: var(--primary-color);
            border: none;
            border-radius: 12px;
            padding: 1rem;
            font-weight: 600;
            font-size: 1rem;
            color: white;
            width: 100%;
            transition: all 0.2s ease;
            margin-bottom: 1.5rem;
        }

        .btn-register:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }

        .divider {
            text-align: center;
            margin: 1.5rem 0;
            position: relative;
            color: var(--secondary-color);
            font-size: 0.875rem;
        }

        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: var(--gray-100);
            z-index: 1;
        }

        .divider span {
            background: white;
            padding: 0 1rem;
            position: relative;
            z-index: 2;
        }

        .login-link {
            text-align: center;
            color: var(--secondary-color);
        }

        .login-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        .privacy-section {
            margin: 1.5rem 0;
            padding: 1rem;
            background: var(--gray-50);
            border-radius: 12px;
            border: 1px solid var(--gray-100);
        }

        .privacy-check {
            margin-bottom: 0.75rem;
        }

        .privacy-check .form-check-input {
            margin-top: 0.2rem;
            border-radius: 4px;
            border: 2px solid var(--gray-100);
        }

        .privacy-check .form-check-input:checked {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .privacy-check .form-check-input:focus {
            box-shadow: 0 0 0 0.2rem rgba(37, 99, 235, 0.15);
        }

        .privacy-link {
            color: var(--primary-color) !important;
            text-decoration: none;
            font-weight: 600;
        }

        .privacy-link:hover {
            text-decoration: underline !important;
        }

        .privacy-info {
            padding: 0.5rem 0;
            border-top: 1px solid var(--gray-100);
            margin-top: 0.75rem;
        }

        .privacy-info small {
            line-height: 1.4;
        }

        .alert-clean {
            border: none;
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
            border-left: 4px solid var(--success-color);
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.1);
            color: #dc2626;
            border-left: 4px solid #dc2626;
        }

        .back-home {
            position: absolute;
            top: 20px;
            left: 20px;
            color: white;
            text-decoration: none;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.1);
            transition: all 0.2s ease;
        }

        .back-home:hover {
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }

        @media (max-width: 576px) {
            .register-container {
                margin: 1rem;
                padding: 2rem;
            }
        }
    </style>
</head>
<body>
    <a href="index.php" class="back-home">
        <i class="fas fa-arrow-left me-2"></i>Homepage
    </a>

    <div class="register-container">
        <div class="brand-section">
            <div class="brand-icon">
                <i class="fas fa-user-plus"></i>
            </div>
            <h1 class="brand-title">Unisciti a noi!</h1>
            <p class="brand-subtitle">Crea il tuo account TicketingIT</p>
        </div>

        <?php if ($success): ?>
            <div class="alert-clean alert-success">
                <i class="fas fa-check-circle me-2"></i>
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert-clean alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if (!$success): ?>
            <form method="POST">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="text" class="form-control" id="first_name" name="first_name" 
                                   placeholder="Nome" required>
                            <label for="first_name">
                                <i class="fas fa-user me-2"></i>Nome
                            </label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="text" class="form-control" id="last_name" name="last_name" 
                                   placeholder="Cognome" required>
                            <label for="last_name">
                                <i class="fas fa-user me-2"></i>Cognome
                            </label>
                        </div>
                    </div>
                </div>

                <div class="form-floating">
                    <input type="email" class="form-control" id="email" name="email" 
                           placeholder="nome@esempio.com" required>
                    <label for="email">
                        <i class="fas fa-envelope me-2"></i>Email
                    </label>
                </div>

                <div class="form-floating">
                    <input type="password" class="form-control" id="password" name="password" 
                           placeholder="Password" minlength="6" required>
                    <label for="password">
                        <i class="fas fa-lock me-2"></i>Password
                    </label>
                </div>

                <div class="form-floating">
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                           placeholder="Conferma Password" minlength="6" required>
                    <label for="confirm_password">
                        <i class="fas fa-lock me-2"></i>Conferma Password
                    </label>
                </div>

                <!-- Checkbox Privacy GDPR -->
                <div class="privacy-section">
                    <div class="form-check privacy-check">
                        <input class="form-check-input" type="checkbox" id="privacy_consent" name="privacy_consent" required>
                        <label class="form-check-label" for="privacy_consent">
                            <small>
                                Ho letto e accetto l'<a href="privacy_policy.php" target="_blank" class="privacy-link">
                                    <i class="fas fa-shield-alt me-1"></i>Informativa sulla Privacy
                                </a> e autorizzo il trattamento dei miei dati personali secondo quanto previsto dal GDPR.
                            </small>
                        </label>
                    </div>
                    <div class="privacy-info">
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            I tuoi dati saranno utilizzati solo per la gestione dei ticket di assistenza e non saranno condivisi con terze parti.
                        </small>
                    </div>
                </div>

                <button type="submit" class="btn-register">
                    <i class="fas fa-user-plus me-2"></i>Registrati
                </button>
            </form>
        <?php endif; ?>

        <div class="divider">
            <span>oppure</span>
        </div>

        <div class="login-link">
            <?php if ($success): ?>
                <a href="login.php">
                    <i class="fas fa-sign-in-alt me-1"></i>Vai al Login
                </a>
            <?php else: ?>
                Hai già un account? 
                <a href="login.php">
                    <i class="fas fa-sign-in-alt me-1"></i>Accedi qui
                </a>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-focus sul primo campo
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('first_name').focus();
        });

        // Validazione password in tempo reale
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('confirm_password');

        function validatePasswords() {
            if (confirmPassword.value && password.value !== confirmPassword.value) {
                confirmPassword.setCustomValidity('Le password non coincidono');
            } else {
                confirmPassword.setCustomValidity('');
            }
        }

        password.addEventListener('input', validatePasswords);
        confirmPassword.addEventListener('input', validatePasswords);

        // Animazione di entrata
        document.querySelector('.register-container').style.opacity = '0';
        document.querySelector('.register-container').style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            document.querySelector('.register-container').style.transition = 'all 0.6s ease';
            document.querySelector('.register-container').style.opacity = '1';
            document.querySelector('.register-container').style.transform = 'translateY(0)';
        }, 100);
    </script>
</body>
</html>
