<?php
require_once 'config.php';
require_once 'phpmailer/PHPMailer.php';
requireAdmin();

$success = '';
$error = '';

// Percorso del file di configurazione
$config_file = 'email_config_data.json';

// Configurazione di default funzionante
$default_config = [
    'smtp_username' => 'info.ticketingit@gmail.com',
    'smtp_password' => 'tjgo zopg bzmt coht',
    'smtp_from_name' => 'ticketingit'
];

// Carica configurazione personalizzata se esiste, altrimenti usa default
$current_config = $default_config;

if (file_exists($config_file)) {
    $config_data = json_decode(file_get_contents($config_file), true);
    if ($config_data) {
        $current_config = array_merge($default_config, $config_data);
    }
}

// Salva configurazione
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $smtp_username = trim($_POST['smtp_username'] ?? '');
    $smtp_password = trim($_POST['smtp_password'] ?? '');
    $smtp_from_name = trim($_POST['smtp_from_name'] ?? '');

    if (empty($smtp_username) || empty($smtp_password)) {
        $error = 'Email e App Password sono obbligatori';
    } else {
        $config_data = [
            'smtp_username' => $smtp_username,
            'smtp_password' => $smtp_password,
            'smtp_from_name' => $smtp_from_name
        ];

        if (file_put_contents($config_file, json_encode($config_data, JSON_PRETTY_PRINT))) {
            $success = 'Configurazione email salvata con successo!';
            $current_config = $config_data;
        } else {
            $error = 'Errore nel salvataggio della configurazione';
        }
    }
}

// Test invio email
if (isset($_POST['test_email'])) {
    // Usa configurazione personalizzata se esiste, altrimenti default
    $test_config = $current_config;
    
    $mail = new PHPMailer();
    
    // Configurazione SMTP
    $mail->Host = 'smtp.gmail.com';
    $mail->Port = 587;
    $mail->Username = $test_config['smtp_username'];
    $mail->Password = $test_config['smtp_password'];
    $mail->setFrom($test_config['smtp_username'], $test_config['smtp_from_name']);
    $mail->addAddress($test_config['smtp_username']);
            
            $test_html = "
            <html>
            <head>
                <style>
                    body { font-family: 'Inter', Arial, sans-serif; line-height: 1.6; color: #374151; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: #2563eb; color: white; padding: 30px 20px; text-align: center; border-radius: 8px; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>🧪 Email di Test</h1>
                        <p>Se ricevi questa email, la configurazione funziona correttamente!</p>
                        <p>Data/Ora: " . date('d/m/Y H:i:s') . "</p>
                    </div>
                </div>
            </body>
            </html>";
            
            $mail->Subject = "🧪 Test Email ticketingit";
            $mail->Body = $test_html;
            $mail->isHTML = true;
            
            if ($mail->send()) {
                $success = 'Email di test inviata con successo! Controlla la tua casella di posta.';
            } else {
                $error = 'Errore nell\'invio dell\'email di test: ' . $mail->getLastError();
            }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurazione Email - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #64748b;
            --gray-50: #f8fafc;
            --gray-100: #f1f5f9;
            --gray-900: #0f172a;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem 0;
        }

        .container {
            max-width: 800px;
        }

        .config-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .card-header {
            background: var(--primary-color);
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .card-body {
            padding: 2rem;
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

        .btn-primary {
            background: var(--primary-color);
            border: none;
            border-radius: 12px;
            padding: 1rem 2rem;
            font-weight: 600;
        }

        .btn-success {
            border-radius: 12px;
            padding: 1rem 2rem;
            font-weight: 600;
        }

        .alert {
            border: none;
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }

        .info-box {
            background: var(--gray-50);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .back-btn {
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

        .back-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }
    </style>
</head>
<body>
    <a href="admin_dashboard.php" class="back-btn">
        <i class="fas fa-arrow-left me-2"></i>Torna alla Dashboard
    </a>

    <div class="container">
        <div class="config-card">
            <div class="card-header">
                <h1><i class="fas fa-cog me-3"></i>Configurazione Email</h1>
                <p class="mb-0">Configura le notifiche email del sistema</p>
            </div>
            
            <div class="card-body">
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($success) ?>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Sistema Pronto:</strong> Il sistema è già configurato con email di default funzionante. 
                    Puoi usarlo così com'è o personalizzare con le tue credenziali Gmail.
                </div>

                <div class="info-box">
                    <h5><i class="fas fa-info-circle me-2"></i>Come ottenere una App Password Gmail:</h5>
                    <ol>
                        <li>Vai su <strong>Google Account</strong> → <strong>Sicurezza</strong></li>
                        <li>Attiva la <strong>Verifica in due passaggi</strong> (se non l'hai già fatto)</li>
                        <li>Vai su <strong>Password per le app</strong></li>
                        <li>Seleziona <strong>App: Posta</strong> e <strong>Dispositivo: Altri</strong></li>
                        <li>Inserisci il nome "ticketingit"</li>
                        <li>Copia la password generata (16 caratteri) e incollala qui sotto</li>
                    </ol>
                    <p class="text-warning"><i class="fas fa-exclamation-triangle me-2"></i><strong>Importante:</strong> Non usare la tua password Gmail normale!</p>
                </div>

                <form method="POST">
                    <div class="form-floating">
                        <input type="email" class="form-control" id="smtp_username" name="smtp_username" 
                               value="<?= htmlspecialchars($current_config['smtp_username']) ?>" 
                               placeholder="esempio@gmail.com" required>
                        <label for="smtp_username">
                            <i class="fas fa-envelope me-2"></i>Email Gmail
                        </label>
                    </div>

                    <div class="form-floating">
                        <input type="password" class="form-control" id="smtp_password" name="smtp_password" 
                               value="<?= htmlspecialchars($current_config['smtp_password']) ?>" 
                               placeholder="App Password (16 caratteri)" required>
                        <label for="smtp_password">
                            <i class="fas fa-key me-2"></i>App Password Gmail
                        </label>
                    </div>

                    <div class="form-floating">
                        <input type="text" class="form-control" id="smtp_from_name" name="smtp_from_name" 
                               value="<?= htmlspecialchars($current_config['smtp_from_name']) ?>" 
                               placeholder="ticketingit" required>
                        <label for="smtp_from_name">
                            <i class="fas fa-signature me-2"></i>Nome Mittente
                        </label>
                    </div>

                    <div class="d-grid gap-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Salva Configurazione
                        </button>
                        
                        <?php if (file_exists($config_file)): ?>
                            <button type="submit" name="test_email" class="btn btn-success">
                                <i class="fas fa-paper-plane me-2"></i>Invia Email di Test
                            </button>
                        <?php endif; ?>
                    </div>
                </form>

                <?php if (file_exists($config_file)): ?>
                    <div class="alert alert-success mt-4">
                        <i class="fas fa-check-circle me-2"></i>
                        <strong>Configurazione personalizzata attiva:</strong> Stai usando le tue credenziali Gmail personalizzate.
                    </div>
                <?php else: ?>
                    <div class="alert alert-success mt-4">
                        <i class="fas fa-check-circle me-2"></i>
                        <strong>Configurazione di default attiva:</strong> Il sistema usa le credenziali Gmail di default e funziona correttamente.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
