<?php
// Carica configurazione email se esiste (solo una volta)
if (file_exists('email_config_data.json') && !defined('SMTP_HOST')) {
    $config_data = json_decode(file_get_contents('email_config_data.json'), true);
    if ($config_data) {
        define('SMTP_HOST', 'smtp.gmail.com');
        define('SMTP_PORT', 587);
        define('SMTP_USERNAME', $config_data['smtp_username']);
        define('SMTP_PASSWORD', $config_data['smtp_password']);
        define('SMTP_FROM_EMAIL', $config_data['smtp_username']);
        define('SMTP_FROM_NAME', $config_data['smtp_from_name']);
    }
} elseif (file_exists('email_config.php') && !defined('SMTP_HOST')) {
    // Fallback alla vecchia configurazione se esiste
    include 'email_config.php';
} elseif (!defined('SMTP_HOST')) {
    // Configurazione di default funzionante
    define('SMTP_HOST', 'smtp.gmail.com');
    define('SMTP_PORT', 587);
    define('SMTP_USERNAME', 'info.ticketingit@gmail.com');
    define('SMTP_PASSWORD', 'tjgo zopg bzmt coht');
    define('SMTP_FROM_EMAIL', 'info.ticketingit@gmail.com');
    define('SMTP_FROM_NAME', 'ticketingit');
}

// Includi PHPMailer per invio email
require_once 'phpmailer/PHPMailer.php';

// Funzione per inviare email usando PHPMailer
function sendEmailNode($to_email, $subject, $html_body, $from_name = 'ticketingit') {
    $mail = new PHPMailer();
    
    // Configurazione SMTP da email_config.php se disponibile
    if (defined('SMTP_HOST')) {
        $mail->Host = SMTP_HOST;
        $mail->Port = SMTP_PORT;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->setFrom(SMTP_FROM_EMAIL, $from_name);
    } else {
        // Configurazione di default
        $mail->setFrom('noreply@localhost', $from_name);
    }
    
    $mail->addAddress($to_email);
    $mail->Subject = $subject;
    $mail->Body = $html_body;
    $mail->isHTML = true;
    
    return $mail->send();
}

// Template email per nuovo ticket
function getNewTicketEmailTemplate($ticket_id, $title, $user_name) {
    return "
    <!DOCTYPE html>
    <html lang='it'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Nuovo Ticket Creato</title>
        <link href='https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap' rel='stylesheet'>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { 
                font-family: 'Inter', Arial, sans-serif; 
                line-height: 1.6; 
                color: #374151; 
                background: #f8fafc;
                padding: 20px;
            }
            .email-container { 
                max-width: 600px; 
                margin: 0 auto; 
                background: white;
                border-radius: 20px;
                overflow: hidden;
                box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            }
            .header { 
                background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%);
                color: white; 
                padding: 40px 30px; 
                text-align: center; 
                position: relative;
            }
            .header::before {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                height: 4px;
                background: linear-gradient(90deg, #1d4ed8, #2563eb, #3b82f6);
            }
            .header h1 { 
                font-size: 28px; 
                font-weight: 700; 
                margin-bottom: 10px; 
            }
            .header p { 
                font-size: 16px; 
                opacity: 0.9; 
            }
            .content { 
                padding: 40px 30px; 
                background: white;
            }
            .greeting { 
                font-size: 18px; 
                margin-bottom: 20px; 
                color: #0f172a;
            }
            .ticket-card { 
                background: #f8fafc; 
                padding: 25px; 
                border-radius: 16px; 
                margin: 25px 0; 
                border-left: 5px solid #2563eb;
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            }
            .ticket-card h3 { 
                color: #2563eb; 
                font-size: 18px; 
                font-weight: 600; 
                margin-bottom: 15px;
                display: flex;
                align-items: center;
            }
            .ticket-info { 
                display: grid; 
                gap: 12px; 
            }
            .info-row { 
                display: flex; 
                justify-content: space-between; 
                align-items: center;
                padding: 8px 0;
                border-bottom: 1px solid #e2e8f0;
            }
            .info-row:last-child { border-bottom: none; }
            .info-label { 
                font-weight: 600; 
                color: #64748b; 
                font-size: 14px;
            }
            .info-value { 
                font-weight: 500; 
                color: #0f172a; 
                font-size: 14px;
            }
            .status-badge { 
                background: rgba(245, 158, 11, 0.1); 
                color: #d97706; 
                padding: 6px 12px; 
                border-radius: 20px; 
                font-size: 12px; 
                font-weight: 600;
            }
            .message { 
                font-size: 16px; 
                line-height: 1.7; 
                color: #4b5563; 
                margin: 20px 0;
            }
            .footer { 
                text-align: center; 
                padding: 30px; 
                background: #f8fafc; 
                color: #6b7280; 
                font-size: 14px;
                border-top: 1px solid #e2e8f0;
            }
            .footer-logo { 
                font-weight: 700; 
                color: #2563eb; 
                margin-bottom: 10px;
                font-size: 16px;
            }
            @media (max-width: 600px) {
                .email-container { margin: 10px; border-radius: 16px; }
                .header, .content { padding: 30px 20px; }
                .ticket-card { padding: 20px; }
            }
        </style>
    </head>
    <body>
        <div class='email-container'>
            <div class='header'>
                <h1>🎫 Ticket Creato con Successo</h1>
                <p>Il tuo ticket è stato preso in carico dal nostro team</p>
            </div>
            
            <div class='content'>
                <div class='greeting'>
                    Ciao <strong>$user_name</strong>,
                </div>
                
                <div class='message'>
                    Il tuo ticket è stato creato con successo e preso in carico dal nostro team di supporto. 
                    Riceverai una notifica via email ogni volta che lo status del tuo ticket cambierà.
                </div>
                
                <div class='ticket-card'>
                    <h3>📋 Dettagli del Ticket</h3>
                    <div class='ticket-info'>
                        <div class='info-row'>
                            <span class='info-label'>ID Ticket</span>
                            <span class='info-value'>#$ticket_id</span>
                        </div>
                        <div class='info-row'>
                            <span class='info-label'>Titolo</span>
                            <span class='info-value'>$title</span>
                        </div>
                        <div class='info-row'>
                            <span class='info-label'>Status</span>
                            <span class='status-badge'>In Attesa</span>
                        </div>
                        <div class='info-row'>
                            <span class='info-label'>Data Creazione</span>
                            <span class='info-value'>" . date('d/m/Y H:i') . "</span>
                        </div>
                    </div>
                </div>
                
                <div class='message'>
                    Il nostro team analizzerà la tua richiesta e ti risponderà il prima possibile. 
                    Grazie per aver utilizzato il nostro sistema di supporto!
                </div>
            </div>
            
            <div class='footer'>
                <div class='footer-logo'>ticketingit</div>
                <p>Questo è un messaggio automatico. Non rispondere a questa email.</p>
                <p style='margin-top: 10px; font-size: 12px; color: #9ca3af;'>
                    © 2025 ticketingit. Tutti i diritti riservati.
                </p>
            </div>
        </div>
    </body>
    </html>";
}

// Template email per cambio status
function getStatusChangeEmailTemplate($ticket_id, $title, $old_status, $new_status, $user_name, $admin_notes = '') {
    $status_messages = [
        'in_attesa' => '⏳ Il tuo ticket è in attesa di essere preso in carico',
        'in_lavorazione' => '🔧 Il tuo ticket è ora in lavorazione',
        'risolto' => '✅ Il tuo ticket è stato risolto con successo'
    ];
    
    $status_colors = [
        'in_attesa' => ['bg' => '#f59e0b', 'light' => 'rgba(245, 158, 11, 0.1)'],
        'in_lavorazione' => ['bg' => '#3b82f6', 'light' => 'rgba(59, 130, 246, 0.1)'], 
        'risolto' => ['bg' => '#10b981', 'light' => 'rgba(16, 185, 129, 0.1)']
    ];
    
    $status_labels = [
        'in_attesa' => 'In Attesa',
        'in_lavorazione' => 'In Lavorazione', 
        'risolto' => 'Risolto'
    ];
    
    $message = $status_messages[$new_status] ?? 'Status aggiornato';
    $color = $status_colors[$new_status]['bg'] ?? '#6b7280';
    $light_color = $status_colors[$new_status]['light'] ?? 'rgba(107, 114, 128, 0.1)';
    $new_status_label = $status_labels[$new_status] ?? ucfirst(str_replace('_', ' ', $new_status));
    $old_status_label = $status_labels[$old_status] ?? ucfirst(str_replace('_', ' ', $old_status));
    
    return "
    <!DOCTYPE html>
    <html lang='it'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Aggiornamento Ticket</title>
        <link href='https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap' rel='stylesheet'>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { 
                font-family: 'Inter', Arial, sans-serif; 
                line-height: 1.6; 
                color: #374151; 
                background: #f8fafc;
                padding: 20px;
            }
            .email-container { 
                max-width: 600px; 
                margin: 0 auto; 
                background: white;
                border-radius: 20px;
                overflow: hidden;
                box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            }
            .header { 
                background: linear-gradient(135deg, $color 0%, " . ($new_status === 'risolto' ? '#059669' : ($new_status === 'in_lavorazione' ? '#2563eb' : '#d97706')) . " 100%);
                color: white; 
                padding: 40px 30px; 
                text-align: center; 
                position: relative;
            }
            .header::before {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                height: 4px;
                background: linear-gradient(90deg, $color, " . ($new_status === 'risolto' ? '#10b981' : ($new_status === 'in_lavorazione' ? '#3b82f6' : '#f59e0b')) . ");
            }
            .header h1 { 
                font-size: 28px; 
                font-weight: 700; 
                margin-bottom: 10px; 
            }
            .header p { 
                font-size: 16px; 
                opacity: 0.9; 
            }
            .content { 
                padding: 40px 30px; 
                background: white;
            }
            .greeting { 
                font-size: 18px; 
                margin-bottom: 20px; 
                color: #0f172a;
            }
            .status-update { 
                background: $light_color; 
                padding: 25px; 
                border-radius: 16px; 
                margin: 25px 0; 
                text-align: center;
                border: 2px solid $color;
            }
            .status-update h3 { 
                color: $color; 
                font-size: 20px; 
                font-weight: 700; 
                margin-bottom: 10px;
            }
            .ticket-card { 
                background: #f8fafc; 
                padding: 25px; 
                border-radius: 16px; 
                margin: 25px 0; 
                border-left: 5px solid $color;
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            }
            .ticket-card h3 { 
                color: #2563eb; 
                font-size: 18px; 
                font-weight: 600; 
                margin-bottom: 15px;
            }
            .ticket-info { 
                display: grid; 
                gap: 12px; 
            }
            .info-row { 
                display: flex; 
                justify-content: space-between; 
                align-items: center;
                padding: 8px 0;
                border-bottom: 1px solid #e2e8f0;
            }
            .info-row:last-child { border-bottom: none; }
            .info-label { 
                font-weight: 600; 
                color: #64748b; 
                font-size: 14px;
            }
            .info-value { 
                font-weight: 500; 
                color: #0f172a; 
                font-size: 14px;
            }
            .status-badge { 
                background: $light_color; 
                color: $color; 
                padding: 6px 12px; 
                border-radius: 20px; 
                font-size: 12px; 
                font-weight: 600;
            }
            .old-status { 
                background: rgba(100, 116, 139, 0.1); 
                color: #64748b; 
                padding: 6px 12px; 
                border-radius: 20px; 
                font-size: 12px; 
                font-weight: 600;
                text-decoration: line-through;
            }
            .message { 
                font-size: 16px; 
                line-height: 1.7; 
                color: #4b5563; 
                margin: 20px 0;
            }
            .footer { 
                text-align: center; 
                padding: 30px; 
                background: #f8fafc; 
                color: #6b7280; 
                font-size: 14px;
                border-top: 1px solid #e2e8f0;
            }
            .footer-logo { 
                font-weight: 700; 
                color: #2563eb; 
                margin-bottom: 10px;
                font-size: 16px;
            }
            @media (max-width: 600px) {
                .email-container { margin: 10px; border-radius: 16px; }
                .header, .content { padding: 30px 20px; }
                .ticket-card, .status-update { padding: 20px; }
            }
        </style>
    </head>
    <body>
        <div class='email-container'>
            <div class='header'>
                <h1>📧 Aggiornamento Ticket</h1>
                <p>Il tuo ticket ha ricevuto un aggiornamento importante</p>
            </div>
            
            <div class='content'>
                <div class='greeting'>
                    Ciao <strong>$user_name</strong>,
                </div>
                
                <div class='status-update'>
                    <h3>$message</h3>
                </div>
                
                <div class='ticket-card'>
                    <h3>📋 Dettagli dell'Aggiornamento</h3>
                    <div class='ticket-info'>
                        <div class='info-row'>
                            <span class='info-label'>ID Ticket</span>
                            <span class='info-value'>#$ticket_id</span>
                        </div>
                        <div class='info-row'>
                            <span class='info-label'>Titolo</span>
                            <span class='info-value'>$title</span>
                        </div>
                        <div class='info-row'>
                            <span class='info-label'>Status Precedente</span>
                            <span class='old-status'>$old_status_label</span>
                        </div>
                        <div class='info-row'>
                            <span class='info-label'>Nuovo Status</span>
                            <span class='status-badge'>$new_status_label</span>
                        </div>
                        <div class='info-row'>
                            <span class='info-label'>Data Aggiornamento</span>
                            <span class='info-value'>" . date('d/m/Y H:i') . "</span>
                        </div>
                    </div>
                </div>
                
                <div class='message'>
                    " . ($new_status === 'risolto' ? 
                        'Il tuo ticket è stato risolto! Se hai altre domande, non esitare a creare un nuovo ticket.' : 
                        'Il nostro team sta lavorando al tuo ticket. Ti terremo aggiornato su eventuali progressi.'
                    ) . "
                </div>
            </div>
            
            <div class='footer'>
                <div class='footer-logo'>ticketingit</div>
                <p>Questo è un messaggio automatico. Non rispondere a questa email.</p>
                <p style='margin-top: 10px; font-size: 12px; color: #9ca3af;'>
                    © 2025 ticketingit. Tutti i diritti riservati.
                </p>
            </div>
        </div>
    </body>
    </html>";
}

// Funzione per inviare notifica nuovo ticket
function sendNewTicketNotification($ticket_id, $title, $user_email, $user_name) {
    $subject = "🎫 Nuovo Ticket Creato - #$ticket_id";
    $html_body = getNewTicketEmailTemplate($ticket_id, $title, $user_name);
    return sendEmailNode($user_email, $subject, $html_body, 'ticketingit');
}

// Funzione per inviare notifica cambio status
function sendStatusChangeNotification($ticket_id, $title, $old_status, $new_status, $user_email, $user_name, $admin_notes = '') {
    $subject = "📧 Aggiornamento Ticket #$ticket_id - " . ucfirst(str_replace('_', ' ', $new_status));
    $html_body = getStatusChangeEmailTemplate($ticket_id, $title, $old_status, $new_status, $user_name, $admin_notes);
    return sendEmailNode($user_email, $subject, $html_body, 'ticketingit');
}
?>
