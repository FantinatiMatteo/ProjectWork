# Sistema Ticketing per Assistenza IT

Un sistema professionale di ticketing per l'assistenza IT sviluppato in PHP con MySQL. Questo progetto è stato realizzato per l'assignment ITS come sistema completo di gestione ticket con interfaccia moderna e funzionalità avanzate.

## 🚀 Caratteristiche Principali

### Funzionalità Utente
- **Registrazione e Login Sicuro**: Sistema di autenticazione con password hash e protezione CSRF
- **Dashboard Personalizzata**: Interfaccia moderna con statistiche e gestione ticket
- **Creazione Ticket**: Form intuitivo per segnalare problemi IT
- **Gestione Ticket**: Visualizzazione, filtri e commenti sui ticket
- **Profilo Utente**: Gestione dati personali con conformità GDPR

### Funzionalità Amministratore
- **Dashboard Amministrativa**: Overview completa con analytics e grafici
- **Gestione Ticket Avanzata**: Assegnazione, priorità, stati e commenti interni
- **Gestione Utenti**: Controllo account e permessi
- **Log di Sicurezza**: Monitoraggio eventi e attività utenti
- **Statistiche**: Grafici e report dettagliati

### Sicurezza e Conformità
- **Protezione CSRF**: Token di sicurezza per tutte le operazioni
- **Password Sicure**: Hash bcrypt e requisiti di complessità
- **Gestione Sessioni**: Sistema sicuro di autenticazione
- **Conformità GDPR**: Gestione consensi e privacy
- **Log di Sicurezza**: Tracciamento eventi e tentativi di accesso
- **Prevenzione SQL Injection**: Query preparate e sanitizzazione input

### Design e UI/UX
- **Responsive Design**: Bootstrap 5 per compatibilità mobile
- **Interfaccia Moderna**: Design professionale con Font Awesome
- **Animazioni CSS**: Transizioni fluide e hover effects
- **Dashboard Interattive**: Grafici e statistiche in tempo reale
- **Form Validation**: Validazione client-side e server-side

## 🛠️ Tecnologie Utilizzate

- **Backend**: PHP 8.2+ con PDO
- **Database**: MySQL 8.0+ con InnoDB
- **Frontend**: HTML5, CSS3, JavaScript ES6
- **Framework CSS**: Bootstrap 5.3.0
- **Icone**: Font Awesome 6.4.0
- **Charts**: Chart.js per grafici e statistiche
- **Fonts**: Google Fonts (Inter)

## 📋 Requisiti di Sistema

- **Web Server**: Apache 2.4+ o Nginx
- **PHP**: Versione 8.2 o superiore
- **MySQL**: Versione 8.0 o superiore
- **Estensioni PHP**: PDO, PDO_MySQL, Session, JSON, Filter
- **Ambiente di Sviluppo**: XAMPP, WAMP, LAMP o MAMP

## 🚀 Installazione

### 1. Download e Setup
```bash
# Clona o scarica il progetto nella cartella del web server
# Esempio per XAMPP:
C:\xampp\htdocs\ticketing-system\
```

### 2. Configurazione Database
1. Apri il file `setup.html` nel browser
2. Segui le istruzioni per creare il database
3. Copia e incolla i comandi SQL in phpMyAdmin
4. Modifica le credenziali in `config.php`

### 3. Configurazione PHP
Assicurati che il file `config.php` contenga le credenziali corrette:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');           // Il tuo username MySQL
define('DB_PASS', '');               // La tua password MySQL
define('DB_NAME', 'ticketing_system');
```

### 4. Test del Sistema
1. Naviga su `http://localhost/ticketing-system/`
2. Usa le credenziali di test:
   - **Admin**: admin@ticketing.local / Admin@123!
   - **Utente**: user@ticketing.local / User@123!

## 📁 Struttura del Progetto

```
ProjectWork/
├── config.php                 # Configurazione database e costanti
├── index.php                  # Entry point del sistema
├── login.php                  # Pagina di login
├── register.php               # Registrazione nuovi utenti
├── logout.php                 # Logout sicuro
├── user_dashboard.php          # Dashboard utente
├── admin_dashboard.php         # Dashboard amministratore
├── ticket_details.php          # Visualizzazione dettagli ticket
├── setup.html                 # Guida installazione database
├── README.md                  # Documentazione progetto
└── database/
    ├── migrate.php            # Sistema di migrazione
    └── migrations/            # File di migrazione database
        ├── 001_create_users_table.php
        ├── 002_create_tickets_table.php
        ├── 003_create_security_logs_table.php
        ├── 004_create_sessions_table.php
        └── 005_create_ticket_comments_table.php
```

## 👥 Account di Test

Il sistema include account preconfigurati per il testing:

### Amministratore
- **Email**: admin@ticketing.local
- **Password**: Admin@123!
- **Ruolo**: Amministratore completo

### Utente Standard
- **Email**: user@ticketing.local
- **Password**: User@123!
- **Ruolo**: Utente finale

## 🔧 Come Avviare il Sistema

### Step 1: Preparazione Ambiente
1. Installa XAMPP, WAMP o simile
2. Avvia Apache e MySQL
3. Copia i file nella cartella `htdocs` (o `www`)

### Step 2: Configurazione Database
1. Apri `http://localhost/ProjectWork/setup.html`
2. Segui le istruzioni passo-passo
3. Copia i comandi SQL in phpMyAdmin
4. Esegui tutti i comandi nell'ordine mostrato

### Step 3: Configurazione PHP
1. Modifica `config.php` con le tue credenziali MySQL
2. Assicurati che le estensioni PHP siano abilitate

### Step 4: Test del Sistema
1. Vai su `http://localhost/ProjectWork/`
2. Prova il login con le credenziali di test
3. Esplora le funzionalità di utente e admin

## 🐛 Risoluzione Problemi

### Errore di Connessione Database
- Verifica che MySQL sia avviato
- Controlla le credenziali in `config.php`
- Assicurati che il database `ticketing_system` esista

### Errori di Sessione
- Verifica che PHP abbia permessi di scrittura nella cartella temp
- Controlla la configurazione delle sessioni in PHP

### Problemi di Login
- Usa esattamente le credenziali di test fornite
- Controlla che i dati utente siano stati inseriti correttamente nel database

---

**Developed with ❤️ for ITS Assignment - Point 6: Sistema Ticketing per Assistenza IT**

*Sistema completo, professionale e con design moderno come richiesto dalla consegna.*