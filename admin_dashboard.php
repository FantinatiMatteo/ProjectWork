<?php
require_once 'config.php';
require_once 'email_notifications.php';
requireAdmin();

$success = '';
$error = '';

// AGGIORNAMENTO TICKET
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_ticket'])) {
    $ticket_id = (int)$_POST['ticket_id'];
    $new_status = $_POST['status'];
    $admin_notes = trim($_POST['admin_notes'] ?? '');

    // Recupera dati ticket e utente
    $old_ticket_stmt = $pdo->prepare("SELECT t.status, t.title, u.email, u.first_name, u.last_name FROM tickets t JOIN users u ON t.user_id = u.id WHERE t.id = ?");
    $old_ticket_stmt->execute([$ticket_id]);
    $old_ticket = $old_ticket_stmt->fetch();
    
    if ($old_ticket) {
        $old_status = $old_ticket['status'];
        $user_name = $old_ticket['first_name'] . ' ' . $old_ticket['last_name'];
        $user_email = $old_ticket['email'];
        
        $stmt = $pdo->prepare("UPDATE tickets SET status = ?, admin_notes = ? WHERE id = ?");
        if ($stmt->execute([$new_status, $admin_notes, $ticket_id])) {
            $success = 'Ticket #' . $ticket_id . ' aggiornato con successo!';
            
            // Invia notifica email solo se lo status è cambiato
            if ($old_status !== $new_status) {
                try {
                    $email_sent = sendStatusChangeNotification(
                        $ticket_id, 
                        $old_ticket['title'], 
                        $old_status, 
                        $new_status, 
                        $user_email, 
                        $user_name, 
                        $admin_notes
                    );
                    
                    if ($email_sent) {
                        $success .= ' Email di notifica inviata a ' . $user_email;
                    } else {
                        $success .= ' (Errore nell\'invio dell\'email di notifica)';
                    }
                } catch (Exception $e) {
                    error_log("Errore invio email notifica: " . $e->getMessage());
                    $success .= ' (Errore nell\'invio dell\'email di notifica)';
                }
            }
        } else {
            $error = 'Errore nell\'aggiornamento del ticket';
        }
    } else {
        $error = 'Ticket non trovato';
    }
}

// ELIMINAZIONE TICKET
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_ticket'])) {
    $ticket_id = (int)$_POST['ticket_id'];
    
    $stmt = $pdo->prepare("DELETE FROM tickets WHERE id = ?");
    if ($stmt->execute([$ticket_id])) {
        $success = 'Ticket #' . $ticket_id . ' eliminato con successo!';
    } else {
        $error = 'Errore nell\'eliminazione del ticket';
    }
}

// FILTRI
$search = $_GET['search'] ?? '';
$filter_priority = $_GET['priority'] ?? '';
$filter_status = $_GET['status'] ?? '';
$filter_user = $_GET['user'] ?? '';

$where_conditions = ["1=1"];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(t.title LIKE ? OR t.description LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($filter_priority)) {
    $where_conditions[] = "t.priority = ?";
    $params[] = $filter_priority;
}

if (!empty($filter_status)) {
    $where_conditions[] = "t.status = ?";
    $params[] = $filter_status;
}

if (!empty($filter_user)) {
    $where_conditions[] = "u.id = ?";
    $params[] = $filter_user;
}

$where_clause = implode(' AND ', $where_conditions);

// CARICA TICKETS CON FILTRI
$stmt = $pdo->prepare("
    SELECT t.*, u.first_name, u.last_name, u.email,
           TIMESTAMPDIFF(HOUR, t.created_at, NOW()) as hours_ago
    FROM tickets t 
    JOIN users u ON t.user_id = u.id 
    WHERE $where_clause
    ORDER BY 
        CASE t.priority 
            WHEN 'alta' THEN 1 
            WHEN 'media' THEN 2 
            WHEN 'bassa' THEN 3 
        END,
        t.created_at DESC
");
$stmt->execute($params);
$tickets = $stmt->fetchAll();

// STATISTICHE
$stats_stmt = $pdo->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'in_attesa' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'in_lavorazione' THEN 1 ELSE 0 END) as in_progress,
        SUM(CASE WHEN status = 'risolto' THEN 1 ELSE 0 END) as resolved,
        SUM(CASE WHEN priority = 'alta' THEN 1 ELSE 0 END) as `high_priority`
    FROM tickets
");
$stats = $stats_stmt->fetch();

// UTENTI PER FILTRO
$users_stmt = $pdo->query("SELECT id, first_name, last_name FROM users WHERE role = 'user' ORDER BY first_name");
$users = $users_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - TicketingIT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

        .admin-badge {
            background: var(--danger-color);
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-left: 0.5rem;
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

        .btn-danger-clean {
            background: var(--danger-color);
            border: none;
            border-radius: 8px;
            padding: 0.4rem 0.8rem;
            color: white;
            font-weight: 500;
            transition: all 0.2s ease;
            font-size: 0.85rem;
        }

        .btn-danger-clean:hover {
            background: #dc2626;
            transform: translateY(-1px);
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
        .ticket-card {
            background: white;
            border: 1px solid var(--gray-100);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
            border-left: 4px solid var(--gray-100);
        }

        .ticket-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .ticket-card.priority-alta { border-left-color: var(--danger-color); }
        .ticket-card.priority-media { border-left-color: var(--warning-color); }
        .ticket-card.priority-bassa { border-left-color: var(--success-color); }

        .ticket-header {
            display: flex;
            justify-content: between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

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
            margin-bottom: 1rem;
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

        /* CHART CONTAINER */
        .chart-container {
            position: relative;
            height: 300px;
            margin: 1rem 0;
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
                <span class="admin-badge">ADMIN</span>
            </a>
            
            <div class="d-flex align-items-center gap-3">
                <a href="email_config.php" class="btn-clean">
                    <i class="fas fa-cog me-2"></i>Config Email
                </a>
                <div class="text-muted">
                    <i class="fas fa-user-shield me-2"></i>
                    <strong><?= htmlspecialchars($_SESSION['name']) ?></strong>
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
            <div class="col-lg-2 col-md-4">
                <div class="stats-card">
                    <span class="stats-number"><?= $stats['total'] ?></span>
                    <div class="stats-label">Totali</div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4">
                <div class="stats-card">
                    <span class="stats-number text-secondary"><?= $stats['pending'] ?></span>
                    <div class="stats-label">In Attesa</div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4">
                <div class="stats-card">
                    <span class="stats-number text-warning"><?= $stats['in_progress'] ?></span>
                    <div class="stats-label">In Lavorazione</div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4">
                <div class="stats-card">
                    <span class="stats-number text-success"><?= $stats['resolved'] ?></span>
                    <div class="stats-label">Risolti</div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4">
                <div class="stats-card">
                    <span class="stats-number text-danger"><?= $stats['high_priority'] ?></span>
                    <div class="stats-label">Alta Priorità</div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4">
                <div class="stats-card">
                    <span class="stats-number text-info"><?= count($tickets) ?></span>
                    <div class="stats-label">Visibili</div>
                </div>
            </div>
        </div>

        <!-- GRAFICI -->
        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="card-clean">
                    <h5 class="card-title">
                        <i class="fas fa-chart-pie me-2 text-primary"></i>
                        Distribuzione per Stato
                    </h5>
                    <div class="chart-container">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card-clean">
                    <h5 class="card-title">
                        <i class="fas fa-chart-bar me-2 text-primary"></i>
                        Distribuzione per Priorità
                    </h5>
                    <div class="chart-container">
                        <canvas id="priorityChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- FILTRI -->
        <div class="search-container">
            <h4 class="search-title">
                <i class="fas fa-filter me-2 text-primary"></i>
                Filtra Ticket
            </h4>

            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <input type="text" name="search" class="form-control" 
                           placeholder="🔍 Cerca ticket, utenti..." 
                           value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-2">
                    <select name="priority" class="form-select">
                        <option value="">Tutte le priorità</option>
                        <option value="alta" <?= $filter_priority === 'alta' ? 'selected' : '' ?>>🔴 Alta</option>
                        <option value="media" <?= $filter_priority === 'media' ? 'selected' : '' ?>>🟡 Media</option>
                        <option value="bassa" <?= $filter_priority === 'bassa' ? 'selected' : '' ?>>🟢 Bassa</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">Tutti gli stati</option>
                        <option value="in_attesa" <?= $filter_status === 'in_attesa' ? 'selected' : '' ?>>In Attesa</option>
                        <option value="in_lavorazione" <?= $filter_status === 'in_lavorazione' ? 'selected' : '' ?>>In Lavorazione</option>
                        <option value="risolto" <?= $filter_status === 'risolto' ? 'selected' : '' ?>>Risolto</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="user" class="form-select">
                        <option value="">Tutti gli utenti</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?= $user['id'] ?>" <?= $filter_user == $user['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn-clean w-100">
                        <i class="fas fa-search me-2"></i>Filtra
                    </button>
                </div>
            </form>
        </div>

        <!-- GESTIONE TICKET -->
        <div class="card-clean">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="card-title mb-0">
                    <i class="fas fa-tasks me-2 text-primary"></i>
                    Gestione Ticket
                </h4>
                <span class="badge-clean" style="background: var(--primary-color); color: white;">
                    <?= count($tickets) ?> ticket trovati
                </span>
            </div>

            <?php if (empty($tickets)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-search fa-3x text-muted mb-3"></i>
                    <h6 class="text-muted">Nessun ticket trovato</h6>
                    <p class="text-muted">Modifica i filtri per visualizzare altri ticket.</p>
                </div>
            <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($tickets as $ticket): ?>
                        <div class="col-lg-6">
                            <div class="ticket-card priority-<?= $ticket['priority'] ?>">
                                <div class="ticket-header">
                                    <div class="flex-grow-1">
                                        <div class="ticket-title">
                                            #<?= $ticket['id'] ?> - <?= htmlspecialchars($ticket['title']) ?>
                                        </div>
                                        <div class="ticket-meta">
                                            <i class="fas fa-user me-1"></i>
                                            <?= htmlspecialchars($ticket['first_name'] . ' ' . $ticket['last_name']) ?>
                                            <span class="mx-2">•</span>
                                            <i class="fas fa-clock me-1"></i>
                                            <?= $ticket['hours_ago'] ?> ore fa
                                        </div>
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

                                <div class="ticket-description">
                                    <?= htmlspecialchars(substr($ticket['description'], 0, 120)) ?><?= strlen($ticket['description']) > 120 ? '...' : '' ?>
                                </div>

                                <form method="POST" class="row g-2">
                                    <input type="hidden" name="ticket_id" value="<?= $ticket['id'] ?>">
                                    
                                    <div class="col-md-6">
                                        <select name="status" class="form-select form-select-sm">
                                            <option value="in_attesa" <?= $ticket['status'] === 'in_attesa' ? 'selected' : '' ?>>⏳ In Attesa</option>
                                            <option value="in_lavorazione" <?= $ticket['status'] === 'in_lavorazione' ? 'selected' : '' ?>>🔧 In Lavorazione</option>
                                            <option value="risolto" <?= $ticket['status'] === 'risolto' ? 'selected' : '' ?>>✅ Risolto</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="d-flex gap-2">
                                            <button type="submit" name="update_ticket" class="btn-clean btn-sm flex-grow-1">
                                                Aggiorna
                                            </button>
                                            <button type="submit" name="delete_ticket" class="btn-danger-clean btn-sm" 
                                                    onclick="return confirm('Eliminare questo ticket?')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="col-12">
                                        <textarea name="admin_notes" class="form-control form-control-sm" rows="2" 
                                                  placeholder="Note amministratore..."><?= htmlspecialchars($ticket['admin_notes'] ?? '') ?></textarea>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // GRAFICI
        const statusData = {
            labels: ['In Attesa', 'In Lavorazione', 'Risolti'],
            datasets: [{
                data: [<?= $stats['pending'] ?>, <?= $stats['in_progress'] ?>, <?= $stats['resolved'] ?>],
                backgroundColor: ['#64748b', '#f59e0b', '#10b981'],
                borderWidth: 0
            }]
        };

        const priorityData = {
            labels: ['Alta', 'Media', 'Bassa'],
            datasets: [{
                data: [
                    <?= count(array_filter($tickets, fn($t) => $t['priority'] === 'alta')) ?>,
                    <?= count(array_filter($tickets, fn($t) => $t['priority'] === 'media')) ?>,
                    <?= count(array_filter($tickets, fn($t) => $t['priority'] === 'bassa')) ?>
                ],
                backgroundColor: ['#ef4444', '#f59e0b', '#10b981'],
                borderWidth: 0
            }]
        };

        new Chart(document.getElementById('statusChart'), {
            type: 'doughnut',
            data: statusData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });

        new Chart(document.getElementById('priorityChart'), {
            type: 'bar',
            data: priorityData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });

        // Animazioni di entrata
        const cards = document.querySelectorAll('.card-clean, .stats-card, .ticket-card');
        cards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                card.style.transition = 'all 0.6s ease';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 50);
        });
    </script>
</body>
</html>
