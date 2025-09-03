
# ticketingit per Assistenza IT

Sistema completo per la gestione di richieste di assistenza IT, sviluppato per l'assegnamento ITS. Include tutte le funzionalità richieste dalla consegna, sicurezza avanzata, email reali, interfaccia moderna e documentazione dettagliata.

## ✅ Requisiti consegna soddisfatti
- Interfaccia utente intuitiva e responsive
- Ricerca avanzata nei ticket
- Registrazione/login sicura con password hash (bcrypt)
- Due ruoli: utente e amministratore
- Gestione sicura dati personali, privacy e GDPR
- Form apertura ticket con priorità
- Eliminazione ticket da parte admin
- Dashboard admin con elenco, stato, priorità, note interne
- Home utente: lista ticket + form nuova richiesta
- Sicurezza: CSRF, SQL injection, sessioni sicure
- Email reali per apertura/cambio stato ticket
- **Privacy Policy completa e conformità GDPR**

## 📧 Email reali
Il sistema invia email reali agli utenti per ogni apertura e cambio stato ticket, tramite Node.js/Nodemailer integrato con PHP.

## 🗄️ Migrazione database
Per creare il database e le tabelle, usa il file `migration.sql` incluso. Contiene anche l'admin preconfigurato.

## 📄 Relazione e presentazione
Vedi `relazione.md` per la relazione tecnica dettagliata.

---

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
- **Conformità GDPR**: Gestione consensi, privacy policy completa e tracciamento consensi
- **Log di Sicurezza**: Tracciamento eventi e tentativi di accesso
- **Prevenzione SQL Injection**: Query preparate e sanitizzazione input
- **Privacy Policy**: Pagina dedicata con informazioni complete su trattamento dati
- **Consenso Privacy**: Checkbox obbligatorio in registrazione con logging GDPR

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
1. Apri `migration.sql` in phpMyAdmin o MySQL CLI
2. Esegui tutti i comandi per creare database, tabelle e admin
3. Modifica le credenziali in `config.php`

### 3. Configurazione PHP
Assicurati che il file `config.php` contenga le credenziali corrette:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');           // Il tuo username MySQL
define('DB_PASS', '');               // La tua password MySQL
define('DB_NAME', 'ticketing_system');
```


### 4. Test del Sistema
1. Naviga su `http://localhost/ProjectWork/`
2. Usa le credenziali di test:
   - **Admin**: admin@ticketing.local / Admin@123!

## 📁 Struttura del Progetto

```
ProjectWork/
├── config.php                 # Configurazione database e costanti
├── index.php                  # Entry point del sistema
├── login.php                  # Pagina di login
├── register.php               # Registrazione nuovi utenti
├── logout.php                 # Logout sicuro
├── privacy_policy.php          # Privacy Policy GDPR completa
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
        ├── 005_create_ticket_comments_table.php
        └── 006_add_privacy_consent_field.php
```

## 🛡️ Conformità GDPR

### Implementazione Privacy
- **Privacy Policy completa**: Pagina dedicata accessibile da tutti i form
- **Consenso esplicito**: Checkbox obbligatorio in registrazione
- **Tracciamento consensi**: Data e IP di consenso salvati per audit
- **Diritti dell'interessato**: Informazioni complete su accesso, rettifica, cancellazione
- **Base giuridica**: Consenso per servizio, interesse legittimo per sicurezza
- **Conservazione limitata**: Periodi definiti per diversi tipi di dati
- **Nessuna condivisione**: Dati NON condivisi con terze parti (tranne email provider)
- **Sicurezza dati**: Crittografia, HTTPS, backup protetti
- **Server UE**: Dati trattati solo nell'Unione Europea


## 👥 Account di Test

### Amministratore
- **Email**: admin@ticketing.local
- **Password**: Admin@123!
- **Ruolo**: Amministratore completo

## 🔧 Come Avviare il Sistema

### Step 1: Preparazione Ambiente
1. Installa XAMPP, WAMP o simile
2. Avvia Apache e MySQL
3. Copia i file nella cartella `htdocs` (o `www`)


### Step 2: Configurazione Database
1. Apri `migration.sql` in phpMyAdmin o MySQL CLI
2. Esegui tutti i comandi per creare database, tabelle e admin
3. Modifica le credenziali in `config.php`

### Step 3: Configurazione PHP
1. Modifica `config.php` con le tue credenziali MySQL
2. Assicurati che le estensioni PHP siano abilitate


### Step 4: Test del Sistema
1. Vai su `http://localhost/ProjectWork/`
2. Login come admin: admin@ticketing.local / Admin@123!
3. Esplora tutte le funzionalità: creazione ticket, cambio stato, email, dashboard, sicurezza


## 🐛 Troubleshooting

### Database
- MySQL deve essere avviato
- Credenziali corrette in `config.php`
- Il database `ticketing_system` deve esistere

### Sessioni
- PHP deve poter scrivere nella cartella temp
- Verifica configurazione sessioni PHP

### Login
- Usa le credenziali admin fornite
- Se non funziona, controlla che l'admin sia presente in `users`

### Email
- Verifica che Node.js e Nodemailer siano installati
- Controlla la configurazione Gmail in `sendMail.js`
- Diagnostica visibile in dashboard admin in caso di errore

### Privacy/GDPR
- Il sistema richiede consenso privacy in fase di registrazione
- I dati sono gestiti secondo normativa GDPR

### Sicurezza
- Password hash bcrypt
- Query preparate contro SQL injection
- Token CSRF per tutte le operazioni

## 🎬 Demo e Presentazione
- Demo guidata: login, creazione ticket, cambio stato, email, dashboard admin
- Relazione tecnica dettagliata in `relazione.md`
- Repository GitHub pubblico con codice, documentazione e report

## 🔗 Link Utili
- [Relazione tecnica](relazione.md)
- [Migrazione SQL](migration.sql)

---


---

**Developed with ❤️ for ITS Assignment - Point 6: ticketingit per Assistenza IT**

*Sistema completo, professionale, sicuro e conforme alla consegna.*