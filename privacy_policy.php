<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy - Sistema Ticketing</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .privacy-container {
            max-width: 800px;
            margin: 20px auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .content {
            padding: 40px;
        }
        .section {
            margin-bottom: 30px;
        }
        .section h3 {
            color: #2563eb;
            margin-bottom: 15px;
            font-weight: 600;
        }
        .section p, .section li {
            line-height: 1.7;
            color: #4b5563;
        }
        .highlight {
            background: #f3f4f6;
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid #2563eb;
            margin: 20px 0;
        }
        .back-btn {
            background: #2563eb;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }
        .back-btn:hover {
            background: #1d4ed8;
            color: white;
            transform: translateY(-1px);
        }
    </style>
</head>
<body>
    <div class="privacy-container">
        <div class="header">
            <h1><i class="fas fa-shield-alt me-3"></i>Privacy Policy</h1>
            <p class="mb-0">Informativa sulla Privacy - Sistema Ticketing IT</p>
        </div>
        
        <div class="content">
            <a href="index.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Torna alla Home
            </a>

            <div class="highlight">
                <strong>Ultimo aggiornamento:</strong> <?= date('d/m/Y') ?><br>
                <strong>Entrata in vigore:</strong> <?= date('d/m/Y') ?>
            </div>

            <div class="section">
                <h3>1. Titolare del Trattamento</h3>
                <p>Il Titolare del trattamento dei dati personali è <strong>Sistema Ticketing IT</strong>, con sede presso [INDIRIZZO], contattabile all'indirizzo email: <strong>info.ticketingit@gmail.com</strong></p>
            </div>

            <div class="section">
                <h3>2. Dati Personali Raccolti</h3>
                <p>Il nostro sistema raccoglie i seguenti dati personali:</p>
                <ul>
                    <li><strong>Dati di registrazione:</strong> Nome, Cognome, Email, Password (criptata)</li>
                    <li><strong>Dati del ticket:</strong> Titolo, Descrizione, Priorità, Data di creazione</li>
                    <li><strong>Dati tecnici:</strong> Indirizzo IP, Data e ora di accesso, User agent del browser</li>
                    <li><strong>Log di sicurezza:</strong> Tentativi di accesso, azioni eseguite nel sistema</li>
                </ul>
            </div>

            <div class="section">
                <h3>3. Finalità del Trattamento</h3>
                <p>I dati personali vengono trattati per le seguenti finalità:</p>
                <ul>
                    <li><strong>Gestione account:</strong> Registrazione, autenticazione e gestione del profilo utente</li>
                    <li><strong>Gestione ticket:</strong> Creazione, tracciamento e risoluzione delle richieste di assistenza</li>
                    <li><strong>Comunicazioni:</strong> Invio di notifiche email relative ai ticket</li>
                    <li><strong>Sicurezza:</strong> Prevenzione frodi, monitoraggio accessi, protezione sistema</li>
                    <li><strong>Miglioramento servizio:</strong> Analisi statistiche per ottimizzare il sistema</li>
                </ul>
            </div>

            <div class="section">
                <h3>4. Base Giuridica del Trattamento</h3>
                <p>Il trattamento dei dati personali si basa su:</p>
                <ul>
                    <li><strong>Consenso dell'interessato</strong> (Art. 6, par. 1, lett. a GDPR) per la registrazione e l'uso del servizio</li>
                    <li><strong>Interesse legittimo</strong> (Art. 6, par. 1, lett. f GDPR) per la sicurezza e il miglioramento del servizio</li>
                    <li><strong>Adempimento obbligo legale</strong> (Art. 6, par. 1, lett. c GDPR) per la conservazione dei log di sicurezza</li>
                </ul>
            </div>

            <div class="section">
                <h3>5. Conservazione dei Dati</h3>
                <p>I dati vengono conservati per i seguenti periodi:</p>
                <ul>
                    <li><strong>Dati account:</strong> Fino alla cancellazione dell'account da parte dell'utente</li>
                    <li><strong>Dati ticket:</strong> 5 anni dalla chiusura del ticket per finalità di supporto</li>
                    <li><strong>Log di sicurezza:</strong> 12 mesi dalla registrazione dell'evento</li>
                    <li><strong>Backup:</strong> I backup vengono conservati per 30 giorni e poi eliminati automaticamente</li>
                </ul>
            </div>

            <div class="section">
                <h3>6. Condivisione dei Dati</h3>
                <p>I dati personali <strong>NON vengono condivisi con terze parti</strong> ad eccezione di:</p>
                <ul>
                    <li><strong>Provider email:</strong> Gmail per l'invio delle notifiche (solo email di destinazione)</li>
                    <li><strong>Hosting provider:</strong> Per l'erogazione del servizio tecnico</li>
                    <li><strong>Autorità competenti:</strong> Solo se richiesto dalla legge</li>
                </ul>
                <div class="highlight">
                    <strong>Importante:</strong> Non utilizziamo cookie di tracciamento, non condividiamo dati con social network o servizi pubblicitari.
                </div>
            </div>

            <div class="section">
                <h3>7. Diritti dell'Interessato</h3>
                <p>Ai sensi del GDPR, hai diritto di:</p>
                <ul>
                    <li><strong>Accesso:</strong> Ottenere conferma del trattamento e copia dei tuoi dati</li>
                    <li><strong>Rettifica:</strong> Correggere dati inesatti o incompleti</li>
                    <li><strong>Cancellazione:</strong> Ottenere la cancellazione dei dati (diritto all'oblio)</li>
                    <li><strong>Limitazione:</strong> Limitare il trattamento in determinate circostanze</li>
                    <li><strong>Portabilità:</strong> Ricevere i dati in formato strutturato</li>
                    <li><strong>Opposizione:</strong> Opporti al trattamento basato su interesse legittimo</li>
                    <li><strong>Revoca consenso:</strong> Revocare il consenso in qualsiasi momento</li>
                </ul>
                <p>Per esercitare questi diritti, contatta: <strong>info.ticketingit@gmail.com</strong></p>
            </div>

            <div class="section">
                <h3>8. Sicurezza dei Dati</h3>
                <p>Implementiamo misure di sicurezza tecniche e organizzative appropriate:</p>
                <ul>
                    <li><strong>Crittografia:</strong> Password criptate con algoritmo bcrypt</li>
                    <li><strong>Connessioni sicure:</strong> HTTPS per tutte le comunicazioni</li>
                    <li><strong>Protezione accessi:</strong> Sistema di autenticazione sicura e token CSRF</li>
                    <li><strong>Monitoraggio:</strong> Log di sicurezza e rilevamento anomalie</li>
                    <li><strong>Backup:</strong> Backup crittografati con retention limitata</li>
                </ul>
            </div>

            <div class="section">
                <h3>9. Trasferimenti Internazionali</h3>
                <p>I dati vengono trattati esclusivamente nell'Unione Europea. L'unico trasferimento verso paesi terzi riguarda l'invio di email tramite Gmail (Google), che fornisce garanzie adeguate secondo il GDPR.</p>
            </div>

            <div class="section">
                <h3>10. Modifiche alla Privacy Policy</h3>
                <p>Ci riserviamo il diritto di modificare questa Privacy Policy. Le modifiche saranno comunicate tramite:</p>
                <ul>
                    <li>Notifica nella dashboard del sistema</li>
                    <li>Email agli utenti registrati</li>
                    <li>Aggiornamento della data in questa pagina</li>
                </ul>
            </div>

            <div class="section">
                <h3>11. Contatti</h3>
                <p>Per qualsiasi questione relativa alla privacy o per esercitare i tuoi diritti:</p>
                <ul>
                    <li><strong>Email:</strong> info.ticketingit@gmail.com</li>
                    <li><strong>Oggetto:</strong> "GDPR - Richiesta Privacy"</li>
                    <li><strong>Tempo di risposta:</strong> Entro 30 giorni dalla richiesta</li>
                </ul>
            </div>

            <div class="highlight">
                <h4><i class="fas fa-info-circle me-2"></i>Reclami</h4>
                <p>Hai il diritto di presentare un reclamo all'Autorità Garante per la protezione dei dati personali se ritieni che il trattamento dei tuoi dati violi il GDPR.</p>
                <p><strong>Garante Privacy:</strong> <a href="https://www.gpdp.it" target="_blank">www.gpdp.it</a></p>
            </div>

            <div class="text-center mt-4">
                <a href="index.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i> Torna alla Home
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
