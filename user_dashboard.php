<?php
/**
 * IT Support Ticketing System - User Dashboard
 * Professional user interface for ticket management
 */
require_once 'config.php';

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header('Location: login.php');
    exit;
}

// Session security check
if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > SESSION_TIMEOUT) {
    session_destroy();
    header('Location: login.php?expired=1');
    exit;
}

$pdo = getDBConnection();
$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Handle new ticket submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_ticket') {
    // CSRF Protection
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = 'Token di sicurezza non valido.';
    } else {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $priority = $_POST['priority'] ?? 'medium';
        $category = trim($_POST['category'] ?? '');
        
        // Validation
        if (empty($title) || empty($description)) {
            $error = 'Titolo e descrizione sono obbligatori.';
        } elseif (strlen($title) < 5) {
            $error = 'Il titolo deve essere di almeno 5 caratteri.';
        } elseif (strlen($description) < 20) {
            $error = 'La descrizione deve essere di almeno 20 caratteri.';
        } else {
            try {
                // Create new ticket
                $stmt = $pdo->prepare("
                    INSERT INTO tickets (user_id, title, description, priority, category, status) 
                    VALUES (?, ?, ?, ?, ?, 'pending')
                ");
                $stmt->execute([$user_id, $title, $description, $priority, $category]);
                
                $ticket_id = $pdo->lastInsertId();
                
                // Get the generated ticket number
                $stmt = $pdo->prepare("SELECT ticket_number FROM tickets WHERE id = ?");
                $stmt->execute([$ticket_id]);
                $ticket_number = $stmt->fetchColumn();
                
                // Log ticket creation
                logSecurityEvent('ticket_created', "New ticket created: $ticket_number", $user_id);
                
                // Add initial update
                $stmt = $pdo->prepare("
                    INSERT INTO ticket_updates (ticket_id, user_id, update_type, content) 
                    VALUES (?, ?, 'comment', ?)
                ");
                $stmt->execute([$ticket_id, $user_id, "Ticket creato dall'utente"]);
                
                $success = "Ticket creato con successo! Numero: $ticket_number";
                
                // Clear form data
                $_POST = [];
            } catch (PDOException $e) {
                error_log("Ticket creation error: " . $e->getMessage());
                $error = 'Errore durante la creazione del ticket. Riprova.';
            }
        }
    }
}

// Handle ticket actions (close, reopen)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_ticket') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = 'Token di sicurezza non valido.';
    } else {
        $ticket_id = (int)($_POST['ticket_id'] ?? 0);
        $new_status = $_POST['new_status'] ?? '';
        
        if ($ticket_id && $new_status) {
            try {
                // Verify ticket ownership
                $stmt = $pdo->prepare("SELECT id, status, ticket_number FROM tickets WHERE id = ? AND user_id = ?");
                $stmt->execute([$ticket_id, $user_id]);
                $ticket = $stmt->fetch();
                
                if ($ticket) {
                    // Update ticket status
                    $stmt = $pdo->prepare("UPDATE tickets SET status = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$new_status, $ticket_id]);
                    
                    // Add update record
                    $stmt = $pdo->prepare("
                        INSERT INTO ticket_updates (ticket_id, user_id, update_type, old_value, new_value, content) 
                        VALUES (?, ?, 'status_change', ?, ?, ?)
                    ");
                    $stmt->execute([
                        $ticket_id, 
                        $user_id, 
                        $ticket['status'], 
                        $new_status, 
                        "Stato modificato da utente: {$ticket['status']} → $new_status"
                    ]);
                    
                    logSecurityEvent('ticket_updated', "Ticket {$ticket['ticket_number']} status changed to $new_status", $user_id);
                    
                    $success = 'Ticket aggiornato con successo.';
                } else {
                    $error = 'Ticket non trovato o non autorizzato.';
                }
            } catch (PDOException $e) {
                error_log("Ticket update error: " . $e->getMessage());
                $error = 'Errore durante l\'aggiornamento del ticket.';
            }
        }
    }
}

// Get user's tickets with pagination
$page = (int)($_GET['page'] ?? 1);
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$priority_filter = $_GET['priority'] ?? '';
$search = trim($_GET['search'] ?? '');

// Build query with filters
$where_conditions = ["t.user_id = ?"];
$params = [$user_id];

if ($status_filter) {
    $where_conditions[] = "t.status = ?";
    $params[] = $status_filter;
}

if ($priority_filter) {
    $where_conditions[] = "t.priority = ?";
    $params[] = $priority_filter;
}

if ($search) {
    $where_conditions[] = "(t.title LIKE ? OR t.description LIKE ? OR t.ticket_number LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

$where_clause = implode(' AND ', $where_conditions);

// Get tickets
$stmt = $pdo->prepare("
    SELECT t.*, 
           (SELECT COUNT(*) FROM ticket_updates tu WHERE tu.ticket_id = t.id AND tu.is_internal = 0) as update_count
    FROM tickets t 
    WHERE $where_clause 
    ORDER BY t.created_at DESC 
    LIMIT ? OFFSET ?
");
$params[] = $per_page;
$params[] = $offset;
$stmt->execute($params);
$tickets = $stmt->fetchAll();

// Get total count for pagination
$count_params = array_slice($params, 0, -2); // Remove LIMIT and OFFSET
$stmt = $pdo->prepare("SELECT COUNT(*) FROM tickets t WHERE $where_clause");
$stmt->execute($count_params);
$total_tickets = $stmt->fetchColumn();
$total_pages = ceil($total_tickets / $per_page);

// Get user statistics
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
        SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved,
        SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed
    FROM tickets 
    WHERE user_id = ?
");
$stmt->execute([$user_id]);
$stats = $stmt->fetch();

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Utente - <?php echo SYSTEM_NAME; ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #4f46e5;
            --primary-dark: #3730a3;
            --secondary-color: #6366f1;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
            --info-color: #3b82f6;
            --dark-color: #1f2937;
            --light-color: #f8fafc;
            --sidebar-width: 280px;
        }
        
        * {
            font-family: 'Inter', sans-serif;
        }
        
        body {
            background-color: var(--light-color);
            font-size: 14px;
        }
        
        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            position: fixed;
            left: 0;
            top: 0;
            z-index: 1000;
            overflow-y: auto;
        }
        
        .sidebar-header {
            padding: 2rem 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-header h4 {
            color: white;
            margin: 0;
            font-weight: 600;
            font-size: 1.2rem;
        }
        
        .sidebar-header p {
            color: rgba(255, 255, 255, 0.8);
            margin: 0.5rem 0 0 0;
            font-size: 0.9rem;
        }
        
        .sidebar-menu {
            padding: 1rem 0;
        }
        
        .sidebar-menu .nav-link {
            color: rgba(255, 255, 255, 0.9);
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 0;
            transition: all 0.3s ease;
        }
        
        .sidebar-menu .nav-link:hover,
        .sidebar-menu .nav-link.active {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            transform: translateX(5px);
        }
        
        .sidebar-menu .nav-link i {
            width: 20px;
            margin-right: 0.75rem;
        }
        
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 2rem;
            min-height: 100vh;
        }
        
        .top-bar {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            display: flex;
            justify-content: between;
            align-items: center;
        }
        
        .welcome-message h2 {
            margin: 0;
            color: var(--dark-color);
            font-weight: 600;
        }
        
        .welcome-message p {
            margin: 0.25rem 0 0 0;
            color: #6b7280;
        }
        
        .stats-row {
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease;
            border-left: 4px solid;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card.total { border-left-color: var(--info-color); }
        .stat-card.pending { border-left-color: var(--warning-color); }
        .stat-card.progress { border-left-color: var(--primary-color); }
        .stat-card.resolved { border-left-color: var(--success-color); }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            margin: 0;
        }
        
        .stat-label {
            color: #6b7280;
            font-size: 0.9rem;
            margin: 0;
        }
        
        .stat-icon {
            font-size: 2rem;
            opacity: 0.8;
        }
        
        .content-section {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .section-header h3 {
            margin: 0;
            color: var(--dark-color);
            font-weight: 600;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: 10px;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(79, 70, 229, 0.3);
        }
        
        .filter-bar {
            background: #f8fafc;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .ticket-card {
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }
        
        .ticket-card:hover {
            border-color: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .ticket-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .ticket-number {
            font-weight: 600;
            color: var(--primary-color);
            font-size: 1.1rem;
        }
        
        .ticket-title {
            font-weight: 600;
            color: var(--dark-color);
            margin: 0.5rem 0;
        }
        
        .ticket-description {
            color: #6b7280;
            margin-bottom: 1rem;
            line-height: 1.5;
        }
        
        .ticket-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .badge {
            border-radius: 20px;
            padding: 0.5rem 1rem;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .badge.status-pending { background: rgba(245, 158, 11, 0.1); color: var(--warning-color); }
        .badge.status-in_progress { background: rgba(79, 70, 229, 0.1); color: var(--primary-color); }
        .badge.status-resolved { background: rgba(16, 185, 129, 0.1); color: var(--success-color); }
        .badge.status-closed { background: rgba(107, 114, 128, 0.1); color: #6b7280; }
        
        .badge.priority-low { background: rgba(16, 185, 129, 0.1); color: var(--success-color); }
        .badge.priority-medium { background: rgba(245, 158, 11, 0.1); color: var(--warning-color); }
        .badge.priority-high { background: rgba(239, 68, 68, 0.1); color: var(--danger-color); }
        .badge.priority-urgent { background: var(--danger-color); color: white; }
        
        .alert {
            border: none;
            border-radius: 12px;
            padding: 1rem 1.5rem;
        }
        
        .modal-content {
            border-radius: 15px;
            border: none;
        }
        
        .modal-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 15px 15px 0 0;
        }
        
        .form-control, .form-select {
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            padding: 0.75rem;
            transition: border-color 0.3s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(79, 70, 229, 0.1);
        }
        
        .pagination {
            justify-content: center;
            margin-top: 2rem;
        }
        
        .page-link {
            border: none;
            color: var(--primary-color);
            padding: 0.75rem 1rem;
            margin: 0 0.25rem;
            border-radius: 8px;
        }
        
        .page-link:hover {
            background: var(--primary-color);
            color: white;
        }
        
        .page-item.active .page-link {
            background: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
            
            .stat-card {
                margin-bottom: 1rem;
            }
            
            .ticket-meta {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <nav class="sidebar">
        <div class="sidebar-header">
            <h4><i class="fas fa-headset me-2"></i>IT Support</h4>
            <p>Benvenuto, <?php echo htmlspecialchars($_SESSION['first_name']); ?>!</p>
        </div>
        
        <div class="sidebar-menu">
            <nav class="nav flex-column">
                <a class="nav-link active" href="#dashboard">
                    <i class="fas fa-tachometer-alt"></i>Dashboard
                </a>
                <a class="nav-link" href="#tickets">
                    <i class="fas fa-ticket-alt"></i>I Miei Ticket
                </a>
                <a class="nav-link" href="#new-ticket" data-bs-toggle="modal" data-bs-target="#newTicketModal">
                    <i class="fas fa-plus"></i>Nuovo Ticket
                </a>
                <a class="nav-link" href="#search">
                    <i class="fas fa-search"></i>Ricerca Avanzata
                </a>
                <div class="dropdown-divider" style="border-color: rgba(255,255,255,0.1); margin: 1rem 0;"></div>
                <a class="nav-link" href="#profile">
                    <i class="fas fa-user"></i>Il Mio Profilo
                </a>
                <a class="nav-link" href="#settings">
                    <i class="fas fa-cog"></i>Impostazioni
                </a>
                <a class="nav-link" href="logout.php">
                    <i class="fas fa-sign-out-alt"></i>Logout
                </a>
            </nav>
        </div>
    </nav>
    
    <!-- Main Content -->
    <main class="main-content">
        <!-- Top Bar -->
        <div class="top-bar">
            <div class="welcome-message">
                <h2>Dashboard Utente</h2>
                <p>Gestisci le tue richieste di assistenza IT</p>
            </div>
            <div class="top-actions">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newTicketModal">
                    <i class="fas fa-plus me-2"></i>Nuovo Ticket
                </button>
            </div>
        </div>
        
        <!-- Alerts -->
        <?php if ($error): ?>
            <div class="alert alert-danger" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <!-- Statistics Row -->
        <div class="row stats-row">
            <div class="col-md-3">
                <div class="stat-card total">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="stat-number" style="color: var(--info-color);">
                                <?php echo $stats['total']; ?>
                            </h3>
                            <p class="stat-label">Ticket Totali</p>
                        </div>
                        <i class="fas fa-ticket-alt stat-icon" style="color: var(--info-color);"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="stat-card pending">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="stat-number" style="color: var(--warning-color);">
                                <?php echo $stats['pending']; ?>
                            </h3>
                            <p class="stat-label">In Attesa</p>
                        </div>
                        <i class="fas fa-clock stat-icon" style="color: var(--warning-color);"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="stat-card progress">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="stat-number" style="color: var(--primary-color);">
                                <?php echo $stats['in_progress']; ?>
                            </h3>
                            <p class="stat-label">In Lavorazione</p>
                        </div>
                        <i class="fas fa-cogs stat-icon" style="color: var(--primary-color);"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="stat-card resolved">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="stat-number" style="color: var(--success-color);">
                                <?php echo $stats['resolved']; ?>
                            </h3>
                            <p class="stat-label">Risolti</p>
                        </div>
                        <i class="fas fa-check-circle stat-icon" style="color: var(--success-color);"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Tickets Section -->
        <div class="content-section">
            <div class="section-header">
                <h3><i class="fas fa-list me-2"></i>I Miei Ticket</h3>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-primary btn-sm" data-bs-toggle="collapse" data-bs-target="#filterBar">
                        <i class="fas fa-filter me-1"></i>Filtri
                    </button>
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#newTicketModal">
                        <i class="fas fa-plus me-1"></i>Nuovo
                    </button>
                </div>
            </div>
            
            <!-- Filter Bar -->
            <div class="collapse filter-bar" id="filterBar">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Stato</label>
                        <select name="status" class="form-select">
                            <option value="">Tutti gli stati</option>
                            <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>In Attesa</option>
                            <option value="in_progress" <?php echo $status_filter === 'in_progress' ? 'selected' : ''; ?>>In Lavorazione</option>
                            <option value="resolved" <?php echo $status_filter === 'resolved' ? 'selected' : ''; ?>>Risolto</option>
                            <option value="closed" <?php echo $status_filter === 'closed' ? 'selected' : ''; ?>>Chiuso</option>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label">Priorità</label>
                        <select name="priority" class="form-select">
                            <option value="">Tutte le priorità</option>
                            <option value="low" <?php echo $priority_filter === 'low' ? 'selected' : ''; ?>>Bassa</option>
                            <option value="medium" <?php echo $priority_filter === 'medium' ? 'selected' : ''; ?>>Media</option>
                            <option value="high" <?php echo $priority_filter === 'high' ? 'selected' : ''; ?>>Alta</option>
                            <option value="urgent" <?php echo $priority_filter === 'urgent' ? 'selected' : ''; ?>>Urgente</option>
                        </select>
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">Ricerca</label>
                        <input type="text" name="search" class="form-control" 
                               placeholder="Cerca per titolo, descrizione o numero..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Tickets List -->
            <div class="tickets-list">
                <?php if (empty($tickets)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-ticket-alt fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Nessun ticket trovato</h5>
                        <p class="text-muted">Crea il tuo primo ticket di assistenza!</p>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newTicketModal">
                            <i class="fas fa-plus me-2"></i>Crea Nuovo Ticket
                        </button>
                    </div>
                <?php else: ?>
                    <?php foreach ($tickets as $ticket): ?>
                        <div class="ticket-card">
                            <div class="ticket-header">
                                <div>
                                    <div class="ticket-number">#<?php echo htmlspecialchars($ticket['ticket_number']); ?></div>
                                    <div class="d-flex gap-2 mt-2">
                                        <span class="badge status-<?php echo $ticket['status']; ?>">
                                            <?php 
                                            $status_labels = [
                                                'pending' => 'In Attesa',
                                                'in_progress' => 'In Lavorazione', 
                                                'resolved' => 'Risolto',
                                                'closed' => 'Chiuso'
                                            ];
                                            echo $status_labels[$ticket['status']] ?? $ticket['status'];
                                            ?>
                                        </span>
                                        <span class="badge priority-<?php echo $ticket['priority']; ?>">
                                            <?php 
                                            $priority_labels = [
                                                'low' => 'Bassa',
                                                'medium' => 'Media',
                                                'high' => 'Alta', 
                                                'urgent' => 'Urgente'
                                            ];
                                            echo $priority_labels[$ticket['priority']] ?? $ticket['priority'];
                                            ?>
                                        </span>
                                        <?php if ($ticket['category']): ?>
                                            <span class="badge bg-secondary"><?php echo htmlspecialchars($ticket['category']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="ticket-actions">
                                    <div class="dropdown">
                                        <button class="btn btn-outline-secondary btn-sm" type="button" 
                                                data-bs-toggle="dropdown">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item" href="#" 
                                                   onclick="viewTicket(<?php echo $ticket['id']; ?>)">
                                                <i class="fas fa-eye me-2"></i>Visualizza
                                            </a></li>
                                            <?php if ($ticket['status'] === 'resolved'): ?>
                                                <li><a class="dropdown-item" href="#" 
                                                       onclick="closeTicket(<?php echo $ticket['id']; ?>)">
                                                    <i class="fas fa-check me-2"></i>Chiudi
                                                </a></li>
                                                <li><a class="dropdown-item" href="#" 
                                                       onclick="reopenTicket(<?php echo $ticket['id']; ?>)">
                                                    <i class="fas fa-redo me-2"></i>Riapri
                                                </a></li>
                                            <?php endif; ?>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            
                            <h5 class="ticket-title"><?php echo htmlspecialchars($ticket['title']); ?></h5>
                            <p class="ticket-description">
                                <?php echo htmlspecialchars(substr($ticket['description'], 0, 150)) . 
                                          (strlen($ticket['description']) > 150 ? '...' : ''); ?>
                            </p>
                            
                            <div class="ticket-meta">
                                <div class="d-flex gap-3 text-muted small">
                                    <span><i class="fas fa-calendar me-1"></i>
                                          <?php echo date('d/m/Y H:i', strtotime($ticket['created_at'])); ?></span>
                                    <span><i class="fas fa-clock me-1"></i>
                                          <?php echo date('d/m/Y H:i', strtotime($ticket['updated_at'])); ?></span>
                                    <?php if ($ticket['update_count'] > 0): ?>
                                        <span><i class="fas fa-comments me-1"></i>
                                              <?php echo $ticket['update_count']; ?> aggiornamenti</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <nav>
                            <ul class="pagination">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page-1; ?>&<?php echo http_build_query($_GET); ?>">
                                            <i class="fas fa-chevron-left"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php for ($i = max(1, $page-2); $i <= min($total_pages, $page+2); $i++): ?>
                                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>&<?php echo http_build_query($_GET); ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page+1; ?>&<?php echo http_build_query($_GET); ?>">
                                            <i class="fas fa-chevron-right"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>
    
    <!-- New Ticket Modal -->
    <div class="modal fade" id="newTicketModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus me-2"></i>Crea Nuovo Ticket
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="newTicketForm">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="action" value="create_ticket">
                    
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="title" class="form-label">Titolo del Problema *</label>
                                    <input type="text" class="form-control" id="title" name="title" 
                                           placeholder="Descrivi brevemente il problema..." required minlength="5">
                                    <div class="invalid-feedback">
                                        Il titolo deve essere di almeno 5 caratteri.
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="priority" class="form-label">Priorità</label>
                                    <select class="form-select" id="priority" name="priority">
                                        <option value="low">Bassa</option>
                                        <option value="medium" selected>Media</option>
                                        <option value="high">Alta</option>
                                        <option value="urgent">Urgente</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="category" class="form-label">Categoria</label>
                            <select class="form-select" id="category" name="category">
                                <option value="">Seleziona categoria...</option>
                                <option value="Hardware">Hardware</option>
                                <option value="Software">Software</option>
                                <option value="Network">Rete</option>
                                <option value="Email">Email</option>
                                <option value="Stampanti">Stampanti</option>
                                <option value="Account">Account Utente</option>
                                <option value="Altro">Altro</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Descrizione Dettagliata *</label>
                            <textarea class="form-control" id="description" name="description" rows="6" 
                                      placeholder="Descrivi il problema in dettaglio: cosa stavi facendo quando si è verificato, eventuali messaggi di errore, tentativi di risoluzione già effettuati..." 
                                      required minlength="20"></textarea>
                            <div class="invalid-feedback">
                                La descrizione deve essere di almeno 20 caratteri.
                            </div>
                            <div class="form-text">
                                <i class="fas fa-info-circle me-1"></i>
                                Più dettagli fornisci, più veloce sarà la risoluzione del problema.
                            </div>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-lightbulb me-2"></i>
                            <strong>Suggerimento:</strong> Includi informazioni come sistema operativo, 
                            browser utilizzato, messaggi di errore esatti e passi per riprodurre il problema.
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Annulla
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-2"></i>Crea Ticket
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Update Ticket Form (hidden) -->
    <form method="POST" id="updateTicketForm" style="display: none;">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        <input type="hidden" name="action" value="update_ticket">
        <input type="hidden" name="ticket_id" id="updateTicketId">
        <input type="hidden" name="new_status" id="updateTicketStatus">
    </form>
    
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script>
        // Form validation
        document.getElementById('newTicketForm').addEventListener('submit', function(e) {
            if (!this.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            this.classList.add('was-validated');
        });
        
        // Ticket actions
        function viewTicket(ticketId) {
            // In a real application, this would open a detailed view
            alert('Visualizza ticket #' + ticketId + ' - Funzionalità da implementare');
        }
        
        function closeTicket(ticketId) {
            if (confirm('Sei sicuro di voler chiudere questo ticket?')) {
                document.getElementById('updateTicketId').value = ticketId;
                document.getElementById('updateTicketStatus').value = 'closed';
                document.getElementById('updateTicketForm').submit();
            }
        }
        
        function reopenTicket(ticketId) {
            if (confirm('Sei sicuro di voler riaprire questo ticket?')) {
                document.getElementById('updateTicketId').value = ticketId;
                document.getElementById('updateTicketStatus').value = 'pending';
                document.getElementById('updateTicketForm').submit();
            }
        }
        
        // Auto-refresh page every 5 minutes to show updates
        setTimeout(function() {
            location.reload();
        }, 300000);
        
        // Character counter for description
        document.getElementById('description').addEventListener('input', function() {
            const current = this.value.length;
            const min = 20;
            const max = 2000;
            
            if (current < min) {
                this.setCustomValidity(`Inserisci almeno ${min - current} caratteri in più.`);
            } else if (current > max) {
                this.setCustomValidity(`Hai superato il limite di ${max} caratteri.`);
            } else {
                this.setCustomValidity('');
            }
        });
        
        // Mobile menu toggle
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('show');
        }
        
        // Add mobile menu button for small screens
        if (window.innerWidth <= 768) {
            const topBar = document.querySelector('.top-bar .welcome-message');
            const menuBtn = document.createElement('button');
            menuBtn.innerHTML = '<i class="fas fa-bars"></i>';
            menuBtn.className = 'btn btn-outline-primary btn-sm me-3';
            menuBtn.onclick = toggleSidebar;
            topBar.parentNode.insertBefore(menuBtn, topBar);
        }
    </script>
</body>
</html>
