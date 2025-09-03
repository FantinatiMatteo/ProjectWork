<?php
/**
 * PHPMailer - Versione semplificata e funzionante per Gmail
 */

class PHPMailer
{
    public $Host = 'smtp.gmail.com';
    public $Port = 587;
    public $Username = '';
    public $Password = '';
    public $setFrom = '';
    public $FromName = '';
    public $to_email = '';
    public $to_name = '';
    public $Subject = '';
    public $Body = '';
    public $isHTML = true;
    private $last_error = '';

    public function setFrom($email, $name = '') {
        $this->setFrom = $email;
        $this->FromName = $name;
    }

    public function addAddress($email, $name = '') {
        $this->to_email = $email;
        $this->to_name = $name;
    }

    public function getLastError() {
        return $this->last_error;
    }

    public function send() {
        // Approccio diretto SMTP con gestione corretta di STARTTLS
        if (empty($this->Username) || empty($this->Password)) {
            $this->last_error = "Username o password mancanti";
            return false;
        }

        return $this->sendViaSMTP();
    }
    
    private function sendViaSMTP() {
        // Connessione al server SMTP Gmail
        $smtp = fsockopen($this->Host, $this->Port, $errno, $errstr, 30);
        if (!$smtp) {
            $this->last_error = "Impossibile connettersi a Gmail SMTP: $errstr ($errno)";
            error_log($this->last_error);
            return false;
        }

        // Leggi banner iniziale (220)
        if (!$this->readResponse($smtp, '220')) {
            fclose($smtp);
            return false;
        }

        // EHLO - può avere risposte multi-linea che iniziano con 250
        fputs($smtp, "EHLO localhost\r\n");
        if (!$this->readResponseMultiline($smtp, '250')) {
            fclose($smtp);
            return false;
        }

        // STARTTLS
        fputs($smtp, "STARTTLS\r\n");
        if (!$this->readResponse($smtp, '220')) {
            fclose($smtp);
            return false;
        }

        // Abilita crittografia TLS
        if (!stream_socket_enable_crypto($smtp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            $this->last_error = "Impossibile abilitare TLS";
            error_log($this->last_error);
            fclose($smtp);
            return false;
        }

        // EHLO dopo TLS - di nuovo può avere risposte multi-linea
        fputs($smtp, "EHLO localhost\r\n");
        if (!$this->readResponseMultiline($smtp, '250')) {
            fclose($smtp);
            return false;
        }

        // Autenticazione LOGIN
        fputs($smtp, "AUTH LOGIN\r\n");
        if (!$this->readResponse($smtp, '334')) {
            fclose($smtp);
            return false;
        }

        // Username
        fputs($smtp, base64_encode($this->Username) . "\r\n");
        if (!$this->readResponse($smtp, '334')) {
            fclose($smtp);
            return false;
        }

        // Password
        fputs($smtp, base64_encode($this->Password) . "\r\n");
        if (!$this->readResponse($smtp, '235')) {
            fclose($smtp);
            return false;
        }

        // MAIL FROM
        fputs($smtp, "MAIL FROM: <{$this->setFrom}>\r\n");
        if (!$this->readResponse($smtp, '250')) {
            fclose($smtp);
            return false;
        }

        // RCPT TO
        fputs($smtp, "RCPT TO: <{$this->to_email}>\r\n");
        if (!$this->readResponse($smtp, '250')) {
            fclose($smtp);
            return false;
        }

        // DATA
        fputs($smtp, "DATA\r\n");
        if (!$this->readResponse($smtp, '354')) {
            fclose($smtp);
            return false;
        }

        // Invio messaggio
        $message = $this->createEmailMessage();
        fputs($smtp, $message . "\r\n.\r\n");
        if (!$this->readResponse($smtp, '250')) {
            fclose($smtp);
            return false;
        }

        // QUIT
        fputs($smtp, "QUIT\r\n");
        $this->readResponse($smtp, '221');
        fclose($smtp);

        error_log("Email inviata con successo a: {$this->to_email}");
        return true;
    }
    
    private function readResponse($smtp, $expected_code) {
        $response = fgets($smtp, 515);
        $code = substr($response, 0, 3);
        
        error_log("SMTP Response: " . trim($response));
        
        if ($code !== $expected_code) {
            $this->last_error = "Errore SMTP: atteso $expected_code, ricevuto $code - " . trim($response);
            error_log($this->last_error);
            return false;
        }
        
        return true;
    }
    
    private function readResponseMultiline($smtp, $expected_code) {
        $response = '';
        do {
            $line = fgets($smtp, 515);
            $response .= $line;
            error_log("SMTP Response: " . trim($line));
            
            $code = substr($line, 0, 3);
            $separator = substr($line, 3, 1);
            
            // Se il codice non corrisponde, errore
            if ($code !== $expected_code) {
                $this->last_error = "Errore SMTP: atteso $expected_code, ricevuto $code - " . trim($line);
                error_log($this->last_error);
                return false;
            }
            
            // Se il separatore è spazio, è l'ultima riga
        } while ($separator === '-');
        
        return true;
    }
    
    private function createEmailMessage() {
        $message = "From: {$this->FromName} <{$this->setFrom}>\r\n";
        $message .= "To: {$this->to_email}\r\n";
        $message .= "Subject: =?UTF-8?B?" . base64_encode($this->Subject) . "?=\r\n";
        $message .= "MIME-Version: 1.0\r\n";
        $message .= "Content-Type: text/html; charset=UTF-8\r\n";
        $message .= "Content-Transfer-Encoding: 8bit\r\n";
        $message .= "Date: " . date('r') . "\r\n";
        $message .= "\r\n";
        $message .= $this->Body;
        return $message;
    }
}
?>
