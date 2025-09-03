<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ticketingit IT - Gestione Assistenza Moderna</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #64748b;
            --accent-color: #0ea5e9;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --gray-50: #f8fafc;
            --gray-100: #f1f5f9;
            --gray-900: #0f172a;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--gray-50);
            color: var(--gray-900);
            line-height: 1.6;
        }

        /* HEADER MODERNO */
        .navbar-clean {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(37, 99, 235, 0.1);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .navbar-brand {
            font-weight: 700;
            color: var(--primary-color) !important;
            font-size: 1.5rem;
        }

        .nav-link {
            color: var(--secondary-color) !important;
            font-weight: 500;
            padding: 0.75rem 1.25rem !important;
            border-radius: 8px;
            transition: all 0.2s ease;
        }

        .nav-link:hover {
            background: var(--gray-100);
            color: var(--primary-color) !important;
        }

        .btn-primary-clean {
            background: var(--primary-color);
            border: none;
            border-radius: 8px;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            color: white;
            text-decoration: none;
            transition: all 0.2s ease;
            box-shadow: 0 1px 3px rgba(37, 99, 235, 0.2);
        }

        .btn-primary-clean:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
            color: white;
        }

        .btn-outline-clean {
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
            background: transparent;
            border-radius: 8px;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .btn-outline-clean:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-1px);
        }

        /* HERO SECTION */
        .hero-clean {
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.05) 0%, rgba(59, 130, 246, 0.05) 100%);
            padding: 6rem 0;
            position: relative;
            overflow: hidden;
        }

        .hero-clean::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(37, 99, 235, 0.03) 0%, transparent 70%);
            animation: rotate 20s linear infinite;
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .hero-content {
            position: relative;
            z-index: 2;
        }

        .hero-title {
            font-size: 3.5rem;
            font-weight: 700;
            color: var(--gray-900);
            line-height: 1.2;
            margin-bottom: 1.5rem;
        }

        .hero-subtitle {
            font-size: 1.25rem;
            color: var(--secondary-color);
            margin-bottom: 2.5rem;
            max-width: 600px;
        }

        /* CARDS PULITE */
        .card-clean {
            background: white;
            border: 1px solid rgba(226, 232, 240, 0.8);
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
            transition: all 0.3s ease;
            height: 100%;
        }

        .card-clean:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            border-color: rgba(37, 99, 235, 0.2);
        }

        .card-icon {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--gray-900);
            margin-bottom: 1rem;
        }

        .card-text {
            color: var(--secondary-color);
            line-height: 1.6;
        }

        /* STATISTICHE */
        .stats-section {
            background: white;
            padding: 4rem 0;
            border-top: 1px solid var(--gray-100);
            border-bottom: 1px solid var(--gray-100);
        }

        .stat-card {
            text-align: center;
            padding: 2rem 1rem;
        }

        .stat-number {
            font-size: 3rem;
            font-weight: 700;
            color: var(--primary-color);
            display: block;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: var(--secondary-color);
            font-weight: 500;
            text-transform: uppercase;
            font-size: 0.875rem;
            letter-spacing: 0.05em;
        }

        /* FOOTER PULITO */
        .footer-clean {
            background: var(--gray-900);
            color: var(--gray-100);
            padding: 3rem 0 2rem;
            margin-top: 4rem;
        }

        .footer-brand {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .footer-text {
            color: var(--secondary-color);
            margin-bottom: 2rem;
        }

        .footer-links {
            list-style: none;
            padding: 0;
        }

        .footer-links li {
            margin-bottom: 0.5rem;
        }

        .footer-links a {
            color: var(--gray-100);
            text-decoration: none;
            transition: color 0.2s ease;
        }

        .footer-links a:hover {
            color: var(--accent-color);
        }

        .footer-bottom {
            border-top: 1px solid rgba(100, 116, 139, 0.2);
            padding-top: 2rem;
            margin-top: 2rem;
            text-align: center;
            color: var(--secondary-color);
        }

        /* RESPONSIVE */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.5rem;
            }
            
            .hero-subtitle {
                font-size: 1.125rem;
            }
            
            .stat-number {
                font-size: 2.5rem;
            }
        }

        /* ANIMAZIONI PULITE */
        .fade-in {
            opacity: 0;
            transform: translateY(20px);
            animation: fadeInUp 0.6s ease forwards;
        }

        .fade-in:nth-child(1) { animation-delay: 0.1s; }
        .fade-in:nth-child(2) { animation-delay: 0.2s; }
        .fade-in:nth-child(3) { animation-delay: 0.3s; }

        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* BADGE PULITI */
        .badge-clean {
            background: rgba(37, 99, 235, 0.1);
            color: var(--primary-color);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.875rem;
            display: inline-block;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <!-- NAVBAR PULITA -->
    <nav class="navbar navbar-expand-lg navbar-clean fixed-top">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-ticket-alt me-2"></i>
                TicketingIT
            </a>
            
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#features">Funzionalità</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#stats">Statistiche</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#contact">Contatti</a>
                    </li>
                </ul>
                
                <div class="d-flex gap-2">
                    <a href="login.php" class="btn-outline-clean">
                        <i class="fas fa-sign-in-alt me-2"></i>Accedi
                    </a>
                    <a href="register.php" class="btn-primary-clean">
                        <i class="fas fa-user-plus me-2"></i>Registrati
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- HERO SECTION -->
    <section class="hero-clean">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 hero-content">
                    <div class="badge-clean fade-in">
                        <i class="fas fa-rocket me-2"></i>Sistema Avanzato 2025
                    </div>
                    
                    <h1 class="hero-title fade-in">
                        Gestione Ticket<br>
                        <span style="color: var(--primary-color);">Semplificata</span>
                    </h1>
                    
                    <p class="hero-subtitle fade-in">
                        Sistema moderno di assistenza IT con interfaccia pulita, 
                        gestione efficiente e analisi avanzate per ottimizzare il supporto tecnico.
                    </p>
                    
                    <div class="d-flex flex-wrap gap-3 fade-in">
                        <a href="register.php" class="btn-primary-clean">
                            <i class="fas fa-play me-2"></i>Inizia Subito
                        </a>
                        <a href="#features" class="btn-outline-clean">
                            <i class="fas fa-info-circle me-2"></i>Scopri di Più
                        </a>
                    </div>
                </div>
                
                <div class="col-lg-6">
                    <div class="text-center mt-5 mt-lg-0">
                        <div class="position-relative">
                            <div style="width: 400px; height: 300px; background: linear-gradient(135deg, var(--primary-color), var(--accent-color)); border-radius: 20px; margin: 0 auto; display: flex; align-items: center; justify-content: center; color: white; font-size: 4rem;">
                                <i class="fas fa-desktop"></i>
                            </div>
                            <div style="position: absolute; top: 20px; right: 20px; width: 60px; height: 60px; background: var(--success-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white;">
                                <i class="fas fa-check"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FEATURES SECTION -->
    <section id="features" class="py-5">
        <div class="container">
            <div class="row text-center mb-5">
                <div class="col-lg-8 mx-auto">
                    <h2 class="display-5 fw-bold mb-3">Funzionalità Moderne</h2>
                    <p class="lead text-muted">
                        Strumenti professionali per una gestione efficiente del supporto IT
                    </p>
                </div>
            </div>
            
            <div class="row g-4">
                <div class="col-md-4 fade-in">
                    <div class="card-clean">
                        <div class="card-icon">
                            <i class="fas fa-ticket-alt"></i>
                        </div>
                        <h3 class="card-title">Creazione Ticket Rapida</h3>
                        <p class="card-text">
                            Interfaccia intuitiva per creare ticket con categorizzazione automatica 
                            e priorità intelligente per risoluzioni più veloci.
                        </p>
                    </div>
                </div>
                
                <div class="col-md-4 fade-in">
                    <div class="card-clean">
                        <div class="card-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h3 class="card-title">Analytics Avanzate</h3>
                        <p class="card-text">
                            Dashboard con metriche real-time, grafici interattivi e report 
                            dettagliati per monitorare le performance del supporto.
                        </p>
                    </div>
                </div>
                
                <div class="col-md-4 fade-in">
                    <div class="card-clean">
                        <div class="card-icon">
                            <i class="fas fa-bell"></i>
                        </div>
                        <h3 class="card-title">Notifiche Smart</h3>
                        <p class="card-text">
                            Sistema di notifiche intelligenti via email e in-app per 
                            aggiornamenti immediati sullo stato dei ticket.
                        </p>
                    </div>
                </div>
                
                <div class="col-md-4 fade-in">
                    <div class="card-clean">
                        <div class="card-icon">
                            <i class="fas fa-search"></i>
                        </div>
                        <h3 class="card-title">Ricerca Avanzata</h3>
                        <p class="card-text">
                            Filtri multipli e ricerca full-text per trovare rapidamente 
                            ticket specifici in archivi di grandi dimensioni.
                        </p>
                    </div>
                </div>
                
                <div class="col-md-4 fade-in">
                    <div class="card-clean">
                        <div class="card-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h3 class="card-title">Sicurezza Avanzata</h3>
                        <p class="card-text">
                            Protezione dati con crittografia, autenticazione sicura e 
                            controlli di accesso granulari per privacy garantita.
                        </p>
                    </div>
                </div>
                
                <div class="col-md-4 fade-in">
                    <div class="card-clean">
                        <div class="card-icon">
                            <i class="fas fa-mobile-alt"></i>
                        </div>
                        <h3 class="card-title">Design Responsive</h3>
                        <p class="card-text">
                            Interfaccia ottimizzata per desktop, tablet e smartphone 
                            con esperienza utente fluida su ogni dispositivo.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- STATS SECTION -->
    <section id="stats" class="stats-section">
        <div class="container">
            <div class="row text-center">
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <span class="stat-number" data-target="1200">0</span>
                        <div class="stat-label">Ticket Risolti</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <span class="stat-number" data-target="98">0</span>
                        <div class="stat-label">% Soddisfazione</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <span class="stat-number" data-target="24">0</span>
                        <div class="stat-label">Ore Supporto</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <span class="stat-number" data-target="156">0</span>
                        <div class="stat-label">Utenti Attivi</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FOOTER -->
    <footer id="contact" class="footer-clean">
        <div class="container">
            <div class="row">
                <div class="col-lg-4">
                    <div class="footer-brand">TicketingIT</div>
                    <p class="footer-text">
                        Sistema di gestione ticket moderno e professionale 
                        per supporto IT efficiente e organizzato.
                    </p>
                </div>
                
                <div class="col-lg-2 col-md-6">
                    <h6 class="fw-bold mb-3">Prodotto</h6>
                    <ul class="footer-links">
                        <li><a href="#features">Funzionalità</a></li>
                        <li><a href="#stats">Statistiche</a></li>
                        <li><a href="register.php">Registrati</a></li>
                        <li><a href="login.php">Accedi</a></li>
                    </ul>
                </div>
                
                <div class="col-lg-2 col-md-6">
                    <h6 class="fw-bold mb-3">Supporto</h6>
                    <ul class="footer-links">
                        <li><a href="#">Documentazione</a></li>
                        <li><a href="#">Guide</a></li>
                        <li><a href="#">FAQ</a></li>
                        <li><a href="#">Contatti</a></li>
                    </ul>
                </div>
                
                <div class="col-lg-4">
                    <h6 class="fw-bold mb-3">Contatti</h6>
                    <div class="d-flex align-items-center mb-2">
                        <i class="fas fa-envelope me-3"></i>
                        <span>supporto@ticketingit.com</span>
                    </div>
                    <div class="d-flex align-items-center mb-2">
                        <i class="fas fa-phone me-3"></i>
                        <span>+39 02 1234 5678</span>
                    </div>
                    <div class="d-flex gap-3 mt-3">
                        <i class="fab fa-linkedin fs-5"></i>
                        <i class="fab fa-twitter fs-5"></i>
                        <i class="fab fa-github fs-5"></i>
                    </div>
                </div>
            </div>
            
            <div class="footer-bottom">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <p class="mb-0">&copy; 2025 TicketingIT. Tutti i diritti riservati.</p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <div class="footer-links">
                            <a href="privacy_policy.php" target="_blank" class="text-decoration-none me-3">
                                <i class="fas fa-shield-alt me-1"></i>Privacy Policy
                            </a>
                            <a href="mailto:info.ticketingit@gmail.com" class="text-decoration-none">
                                <i class="fas fa-envelope me-1"></i>Contatti GDPR
                            </a>
                        </div>
                        <small class="text-muted d-block mt-1">
                            <i class="fas fa-check-circle me-1"></i>
                            Conforme al GDPR • Dati protetti • Server UE
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // ANIMAZIONE CONTATORI
        function animateCounters() {
            const counters = document.querySelectorAll('.stat-number');
            
            counters.forEach(counter => {
                const target = parseInt(counter.getAttribute('data-target'));
                const increment = target / 100;
                let current = 0;
                
                const updateCounter = () => {
                    current += increment;
                    if (current < target) {
                        counter.textContent = Math.floor(current);
                        requestAnimationFrame(updateCounter);
                    } else {
                        counter.textContent = target;
                    }
                };
                
                updateCounter();
            });
        }

        // OBSERVER PER ANIMAZIONI
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting && entry.target.classList.contains('stats-section')) {
                    animateCounters();
                    observer.unobserve(entry.target);
                }
            });
        });

        // AVVIA OSSERVAZIONE
        document.addEventListener('DOMContentLoaded', () => {
            const statsSection = document.querySelector('.stats-section');
            if (statsSection) {
                observer.observe(statsSection);
            }
        });

        // SMOOTH SCROLL
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>
</body>
</html>
