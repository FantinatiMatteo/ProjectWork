<?php
require_once 'config.php';
require_once 'email_notifications.php';
requireLogin();

$success = '';
$error = '';

// CREAZIONE TICKET
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_ticket'])) {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $priority = $_POST['priority'] ?? 'media';

    if (empty($title) || empty($description)) {
        $error = 'Titolo e descrizione sono obbligatori';
    } elseif (strlen($title) < 5) {
        $error = 'Il titolo deve essere di almeno 5 caratteri';
    } elseif (strlen($description) < 20) {
        $error = 'La descrizione deve essere di almeno 20 caratteri';
    } else {
        $stmt = $pdo->prepare("INSERT INTO tickets (user_id, title, description, priority) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$_SESSION['user_id'], $title, $description, $priority])) {
            $ticket_id = $pdo->lastInsertId();
            $success = 'Ticket creato con successo! ID: #' . $ticket_id;
            
            // Invia notifica email all'utente
            try {
                $email_sent = sendNewTicketNotification($ticket_id, $title, $_SESSION['email'], $_SESSION['name']);
                if ($email_sent) {
                    $success .= ' Email di conferma inviata.';
                } else {
                    $success .= ' (Email di conferma non inviata)';
                }
            } catch (Exception $e) {
                error_log("Errore invio email nuovo ticket: " . $e->getMessage());
                $success .= ' (Email di conferma non inviata)';
            }
        } else {
            $error = 'Errore nella creazione del ticket';
        }
    }
}

// RICERCA E FILTRI
$search = $_GET['search'] ?? '';
$filter_priority = $_GET['priority'] ?? '';
$filter_status = $_GET['status'] ?? '';

$where_conditions = ["user_id = ?"];
$params = [$_SESSION['user_id']];

if (!empty($search)) {
    $where_conditions[] = "(title LIKE ? OR description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($filter_priority)) {
    $where_conditions[] = "priority = ?";
    $params[] = $filter_priority;
}

if (!empty($filter_status)) {
    $where_conditions[] = "status = ?";
    $params[] = $filter_status;
}

$where_clause = implode(' AND ', $where_conditions);

$stmt = $pdo->prepare("SELECT * FROM tickets WHERE $where_clause ORDER BY created_at DESC");
$stmt->execute($params);
$tickets = $stmt->fetchAll();

// STATISTICHE
$stats_stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'risolto' THEN 1 ELSE 0 END) as resolved,
        SUM(CASE WHEN priority = 'alta' THEN 1 ELSE 0 END) as `high_priority`
    FROM tickets WHERE user_id = ?
");
$stats_stmt->execute([$_SESSION['user_id']]);
$stats = $stats_stmt->fetch();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Utente - TicketingIT</title>
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

        body {
            font-family: 'Inter', sans-serif;
            background: var(--gray-50);
            color: var(--gray-900);
            line-height: 1.6;
        }

        /* HEADER */
        .navbar-clean {
            background: white;
            border-bottom: 1px solid var(--gray-100);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            padding: 1rem 0;
        }

        .navbar-brand {
            font-weight: 700;
            color: var(--primary-color) !important;
            font-size: 1.5rem;
        }

        .user-info {
            color: var(--secondary-color);
            font-size: 0.9rem;
        }

        .btn-clean {
            background: var(--primary-color);
            border: none;
            border-radius: 8px;
            padding: 0.5rem 1rem;
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s ease;
            font-size: 0.9rem;
        }

        .btn-clean:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
            color: white;
        }

        .btn-outline-clean {
            border: 1px solid var(--gray-100);
            color: var(--secondary-color);
            background: white;
            border-radius: 8px;
            padding: 0.5rem 1rem;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s ease;
            font-size: 0.9rem;
        }

        .btn-outline-clean:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }

        /* CARDS */
        .card-clean {
            background: white;
            border: 1px solid var(--gray-100);
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
            transition: all 0.3s ease;
            margin-bottom: 1.5rem;
        }

        .card-clean:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            border-color: rgba(37, 99, 235, 0.1);
        }

        .card-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--gray-900);
            margin-bottom: 0.5rem;
        }

        /* FORMS */
        .form-control {
            border: 1px solid var(--gray-100);
            border-radius: 8px;
            padding: 0.75rem;
            font-size: 0.95rem;
            transition: all 0.2s ease;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(37, 99, 235, 0.1);
        }

        .form-select {
            border: 1px solid var(--gray-100);
            border-radius: 8px;
            padding: 0.75rem;
            font-size: 0.95rem;
        }

        /* STATS */
        .stats-card {
            background: white;
            border: 1px solid var(--gray-100);
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
            transition: all 0.3s ease;
        }

        .stats-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .stats-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
            display: block;
            margin-bottom: 0.5rem;
        }

        .stats-label {
            color: var(--secondary-color);
            font-size: 0.85rem;
            font-weight: 500;
        }

        /* TICKETS */
        .ticket-item {
            background: white;
            border: 1px solid var(--gray-100);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
            border-left: 4px solid var(--gray-100);
        }

        .ticket-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .ticket-item.priority-alta { border-left-color: var(--danger-color); }
        .ticket-item.priority-media { border-left-color: var(--warning-color); }
        .ticket-item.priority-bassa { border-left-color: var(--success-color); }

        .ticket-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--gray-900);
            margin-bottom: 0.5rem;
        }

        .ticket-meta {
            color: var(--secondary-color);
            font-size: 0.85rem;
            margin-bottom: 1rem;
        }

        .ticket-description {
            color: var(--secondary-color);
            font-size: 0.95rem;
            line-height: 1.5;
        }

        /* BADGES */
        .badge-clean {
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .badge-alta { background: rgba(239, 68, 68, 0.1); color: var(--danger-color); }
        .badge-media { background: rgba(245, 158, 11, 0.1); color: var(--warning-color); }
        .badge-bassa { background: rgba(16, 185, 129, 0.1); color: var(--success-color); }
        .badge-in_attesa { background: rgba(100, 116, 139, 0.1); color: var(--secondary-color); }
        .badge-in_lavorazione { background: rgba(245, 158, 11, 0.1); color: var(--warning-color); }
        .badge-risolto { background: rgba(16, 185, 129, 0.1); color: var(--success-color); }

        /* ALERTS */
        .alert-clean {
            border: none;
            border-radius: 8px;
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
            color: var(--danger-color);
            border-left: 4px solid var(--danger-color);
        }

        /* SEARCH */
        .search-container {
            background: white;
            border: 1px solid var(--gray-100);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .search-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--gray-900);
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <!-- HEADER -->
    <nav class="navbar navbar-expand-lg navbar-clean">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-ticket-alt me-2"></i>
                TicketingIT
            </a>
            
            <div class="d-flex align-items-center gap-3">
                <div class="user-info">
                    <i class="fas fa-user me-2"></i>
                    Benvenuto, <strong><?= htmlspecialchars($_SESSION['name']) ?></strong>
                </div>
                <a href="logout.php" class="btn-outline-clean">
                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- MESSAGGI -->
        <?php if ($success): ?>
            <div class="alert-clean alert-success">
                <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert-clean alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- STATISTICHE -->
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="stats-card">
                    <span class="stats-number"><?= $stats['total'] ?></span>
                    <div class="stats-label">Ticket Totali</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card">
                    <span class="stats-number"><?= $stats['resolved'] ?></span>
                    <div class="stats-label">Risolti</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card">
                    <span class="stats-number"><?= $stats['high_priority'] ?></span>
                    <div class="stats-label">Alta Priorità</div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- NUOVO TICKET -->
            <div class="col-lg-5">
                <div class="card-clean">
                    <h3 class="card-title">
                        <i class="fas fa-plus-circle me-2 text-primary"></i>
                        Nuovo Ticket
                    </h3>
                    <p class="text-muted mb-3">Crea una nuova richiesta di assistenza</p>

                    <form method="POST">
                        <div class="mb-3">
                            <label for="title" class="form-label">Titolo</label>
                            <input type="text" class="form-control" id="title" name="title" 
                                   placeholder="Descrivi brevemente il problema" required>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Descrizione</label>
                            <textarea class="form-control" id="description" name="description" rows="4" 
                                      placeholder="Fornisci una descrizione dettagliata del problema" required></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="priority" class="form-label">Priorità</label>
                            <select class="form-select" id="priority" name="priority">
                                <option value="bassa">🟢 Bassa</option>
                                <option value="media" selected>🟡 Media</option>
                                <option value="alta">🔴 Alta</option>
                            </select>
                        </div>

                        <button type="submit" name="create_ticket" class="btn-clean w-100">
                            <i class="fas fa-paper-plane me-2"></i>
                            Invia Ticket
                        </button>
                    </form>
                </div>
            </div>

            <!-- RICERCA E FILTRI -->
            <div class="col-lg-7">
                <div class="search-container">
                    <h4 class="search-title">
                        <i class="fas fa-search me-2 text-primary"></i>
                        Cerca e Filtra
                    </h4>

                    <form method="GET" class="row g-3">
                        <div class="col-md-6">
                            <input type="text" name="search" class="form-control" 
                                   placeholder="Cerca nei tuoi ticket..." 
                                   value="<?= htmlspecialchars($search) ?>">
                        </div>
                        <div class="col-md-3">
                            <select name="priority" class="form-select">
                                <option value="">Tutte le priorità</option>
                                <option value="alta" <?= $filter_priority === 'alta' ? 'selected' : '' ?>>🔴 Alta</option>
                                <option value="media" <?= $filter_priority === 'media' ? 'selected' : '' ?>>🟡 Media</option>
                                <option value="bassa" <?= $filter_priority === 'bassa' ? 'selected' : '' ?>>🟢 Bassa</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select name="status" class="form-select">
                                <option value="">Tutti gli stati</option>
                                <option value="in_attesa" <?= $filter_status === 'in_attesa' ? 'selected' : '' ?>>In Attesa</option>
                                <option value="in_lavorazione" <?= $filter_status === 'in_lavorazione' ? 'selected' : '' ?>>In Lavorazione</option>
                                <option value="risolto" <?= $filter_status === 'risolto' ? 'selected' : '' ?>>Risolto</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn-clean me-2">
                                <i class="fas fa-filter me-2"></i>Filtra
                            </button>
                            <a href="user_dashboard.php" class="btn-outline-clean">
                                <i class="fas fa-times me-2"></i>Reset
                            </a>
                        </div>
                    </form>
                </div>

                <!-- LISTA TICKET -->
                <div class="card-clean">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="card-title mb-0">
                            <i class="fas fa-list me-2 text-primary"></i>
                            I Tuoi Ticket
                        </h4>
                        <span class="badge-clean badge-secondary"><?= count($tickets) ?> ticket</span>
                    </div>

                    <?php if (empty($tickets)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <h6 class="text-muted">Nessun ticket trovato</h6>
                            <p class="text-muted">Non hai ancora creato ticket o nessun ticket corrisponde ai filtri.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($tickets as $ticket): ?>
                            <div class="ticket-item priority-<?= $ticket['priority'] ?>">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div class="ticket-title">
                                        #<?= $ticket['id'] ?> - <?= htmlspecialchars($ticket['title']) ?>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <span class="badge-clean badge-<?= $ticket['priority'] ?>">
                                            <?= ucfirst($ticket['priority']) ?>
                                        </span>
                                        <span class="badge-clean badge-<?= $ticket['status'] ?>">
                                            <?= ucfirst(str_replace('_', ' ', $ticket['status'])) ?>
                                        </span>
                                    </div>
                                </div>

                                <div class="ticket-meta">
                                    <i class="fas fa-calendar me-1"></i>
                                    Creato il <?= date('d/m/Y H:i', strtotime($ticket['created_at'])) ?>
                                </div>

                                <div class="ticket-description">
                                    <?= htmlspecialchars(substr($ticket['description'], 0, 150)) ?><?= strlen($ticket['description']) > 150 ? '...' : '' ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-focus sul form
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('title').focus();
        });

        // Animazioni di entrata
        const cards = document.querySelectorAll('.card-clean, .stats-card');
        cards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                card.style.transition = 'all 0.6s ease';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 100);
        });
    </script>
</body>
</html>
