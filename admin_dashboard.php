<?php
/**
 * IT Support Ticketing System - Admin Dashboard
 * Professional admin interface for ticket management and system monitoring
 */
require_once 'config.php';

// Check authentication and admin role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
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
$admin_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Handle ticket assignment and status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Protection
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = 'Token di sicurezza non valido.';
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'update_ticket') {
            $ticket_id = (int)($_POST['ticket_id'] ?? 0);
            $new_status = $_POST['new_status'] ?? '';
            $admin_notes = trim($_POST['admin_notes'] ?? '');
            $assigned_to = $_POST['assigned_to'] ?? null;
            
            if ($ticket_id && $new_status) {
                try {
                    // Get current ticket data
                    $stmt = $pdo->prepare("SELECT * FROM tickets WHERE id = ?");
                    $stmt->execute([$ticket_id]);
                    $ticket = $stmt->fetch();
                    
                    if ($ticket) {
                        // Update ticket
                        $update_sql = "UPDATE tickets SET status = ?, updated_at = NOW()";
                        $params = [$new_status];
                        
                        if ($assigned_to !== null) {
                            $update_sql .= ", assigned_to = ?";
                            $params[] = $assigned_to ?: null;
                        }
                        
                        if ($admin_notes) {
                            $update_sql .= ", admin_notes = ?";
                            $params[] = $admin_notes;
                        }
                        
                        if ($new_status === 'resolved') {
                            $update_sql .= ", resolved_at = NOW()";
                        } elseif ($new_status === 'closed') {
                            $update_sql .= ", closed_at = NOW()";
                        }
                        
                        $update_sql .= " WHERE id = ?";
                        $params[] = $ticket_id;
                        
                        $stmt = $pdo->prepare($update_sql);
                        $stmt->execute($params);
                        
                        // Log the changes
                        $changes = [];
                        if ($ticket['status'] !== $new_status) {
                            $changes[] = "Status: {$ticket['status']} → $new_status";
                        }
                        if ($assigned_to !== null && $ticket['assigned_to'] != $assigned_to) {
                            $old_assigned = $ticket['assigned_to'] ? "User #{$ticket['assigned_to']}" : "Nessuno";
                            $new_assigned = $assigned_to ? "User #$assigned_to" : "Nessuno";
                            $changes[] = "Assegnato: $old_assigned → $new_assigned";
                        }
                        
                        // Add update record
                        $stmt = $pdo->prepare("
                            INSERT INTO ticket_updates (ticket_id, user_id, update_type, content, is_internal) 
                            VALUES (?, ?, 'system_update', ?, TRUE)
                        ");
                        $stmt->execute([
                            $ticket_id, 
                            $admin_id, 
                            "Admin update: " . implode(", ", $changes) . ($admin_notes ? "\nNote: $admin_notes" : "")
                        ]);
                        
                        logSecurityEvent('ticket_updated', "Admin updated ticket #{$ticket['ticket_number']}: " . implode(", ", $changes), $admin_id);
                        
                        $success = 'Ticket aggiornato con successo.';
                    } else {
                        $error = 'Ticket non trovato.';
                    }
                } catch (PDOException $e) {
                    error_log("Admin ticket update error: " . $e->getMessage());
                    $error = 'Errore durante l\'aggiornamento del ticket.';
                }
            }
        } elseif ($action === 'delete_ticket') {
            $ticket_id = (int)($_POST['ticket_id'] ?? 0);
            
            if ($ticket_id) {
                try {
                    // Get ticket info before deletion
                    $stmt = $pdo->prepare("SELECT ticket_number FROM tickets WHERE id = ?");
                    $stmt->execute([$ticket_id]);
                    $ticket_number = $stmt->fetchColumn();
                    
                    if ($ticket_number) {
                        // Delete ticket (cascade will handle related records)
                        $stmt = $pdo->prepare("DELETE FROM tickets WHERE id = ?");
                        $stmt->execute([$ticket_id]);
                        
                        logSecurityEvent('ticket_deleted', "Admin deleted ticket #$ticket_number", $admin_id);
                        
                        $success = "Ticket #$ticket_number eliminato con successo.";
                    } else {
                        $error = 'Ticket non trovato.';
                    }
                } catch (PDOException $e) {
                    error_log("Admin ticket deletion error: " . $e->getMessage());
                    $error = 'Errore durante l\'eliminazione del ticket.';
                }
            }
        }
    }
}

// Get dashboard statistics
$stmt = $pdo->query("
    SELECT 
        COUNT(*) as total_tickets,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_tickets,
        SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_tickets,
        SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_tickets,
        SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed_tickets,
        SUM(CASE WHEN priority = 'urgent' AND status NOT IN ('resolved', 'closed') THEN 1 ELSE 0 END) as urgent_tickets,
        COUNT(DISTINCT user_id) as active_users
    FROM tickets
");
$stats = $stmt->fetch();

// Get recent tickets with pagination
$page = (int)($_GET['page'] ?? 1);
$per_page = 15;
$offset = ($page - 1) * $per_page;

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$priority_filter = $_GET['priority'] ?? '';
$assigned_filter = $_GET['assigned'] ?? '';
$search = trim($_GET['search'] ?? '');

// Build query with filters
$where_conditions = ["1=1"];
$params = [];

if ($status_filter) {
    $where_conditions[] = "t.status = ?";
    $params[] = $status_filter;
}

if ($priority_filter) {
    $where_conditions[] = "t.priority = ?";
    $params[] = $priority_filter;
}

if ($assigned_filter === 'unassigned') {
    $where_conditions[] = "t.assigned_to IS NULL";
} elseif ($assigned_filter === 'me') {
    $where_conditions[] = "t.assigned_to = ?";
    $params[] = $admin_id;
} elseif (is_numeric($assigned_filter)) {
    $where_conditions[] = "t.assigned_to = ?";
    $params[] = $assigned_filter;
}

if ($search) {
    $where_conditions[] = "(t.title LIKE ? OR t.description LIKE ? OR t.ticket_number LIKE ? OR u.email LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

$where_clause = implode(' AND ', $where_conditions);

// Get tickets with user information
$stmt = $pdo->prepare("
    SELECT t.*, 
           u.first_name, u.last_name, u.email,
           assigned.first_name as assigned_first_name, assigned.last_name as assigned_last_name,
           (SELECT COUNT(*) FROM ticket_updates tu WHERE tu.ticket_id = t.id) as update_count
    FROM tickets t 
    JOIN users u ON t.user_id = u.id
    LEFT JOIN users assigned ON t.assigned_to = assigned.id
    WHERE $where_clause 
    ORDER BY 
        CASE WHEN t.priority = 'urgent' THEN 1 
             WHEN t.priority = 'high' THEN 2 
             WHEN t.priority = 'medium' THEN 3 
             ELSE 4 END,
        t.created_at DESC 
    LIMIT ? OFFSET ?
");
$params[] = $per_page;
$params[] = $offset;
$stmt->execute($params);
$tickets = $stmt->fetchAll();

// Get total count for pagination
$count_params = array_slice($params, 0, -2);
$stmt = $pdo->prepare("SELECT COUNT(*) FROM tickets t JOIN users u ON t.user_id = u.id WHERE $where_clause");
$stmt->execute($count_params);
$total_tickets = $stmt->fetchColumn();
$total_pages = ceil($total_tickets / $per_page);

// Get all admin users for assignment dropdown
$stmt = $pdo->query("SELECT id, first_name, last_name FROM users WHERE role = 'admin' AND is_active = 1 ORDER BY first_name");
$admin_users = $stmt->fetchAll();

// Get recent security events
$stmt = $pdo->prepare("
    SELECT sl.*, u.first_name, u.last_name 
    FROM security_logs sl 
    LEFT JOIN users u ON sl.user_id = u.id 
    ORDER BY sl.created_at DESC 
    LIMIT 10
");
$stmt->execute();
$security_events = $stmt->fetchAll();

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
    <title>Dashboard Admin - <?php echo SYSTEM_NAME; ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Chart.js for analytics -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #dc2626;
            --primary-dark: #b91c1c;
            --secondary-color: #ef4444;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
            --info-color: #3b82f6;
            --dark-color: #1f2937;
            --light-color: #f8fafc;
            --sidebar-width: 300px;
        }
        
        * {
            font-family: 'Inter', sans-serif;
        }
        
        body {
            background-color: var(--light-color);
            font-size: 14px;
        }
        
        .admin-sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            position: fixed;
            left: 0;
            top: 0;
            z-index: 1000;
            overflow-y: auto;
            box-shadow: 4px 0 15px rgba(0, 0, 0, 0.1);
        }
        
        .admin-sidebar-header {
            padding: 2rem 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
        }
        
        .admin-sidebar-header h4 {
            color: white;
            margin: 0;
            font-weight: 700;
            font-size: 1.3rem;
        }
        
        .admin-sidebar-header .admin-badge {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            margin-top: 0.5rem;
            display: inline-block;
        }
        
        .admin-sidebar-header p {
            color: rgba(255, 255, 255, 0.9);
            margin: 0.5rem 0 0 0;
            font-size: 0.9rem;
        }
        
        .admin-menu {
            padding: 1rem 0;
        }
        
        .admin-menu .nav-link {
            color: rgba(255, 255, 255, 0.9);
            padding: 1rem 1.5rem;
            border: none;
            border-radius: 0;
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }
        
        .admin-menu .nav-link:hover,
        .admin-menu .nav-link.active {
            background: rgba(255, 255, 255, 0.15);
            color: white;
            border-left-color: white;
            transform: translateX(5px);
        }
        
        .admin-menu .nav-link i {
            width: 24px;
            margin-right: 0.75rem;
            text-align: center;
        }
        
        .admin-menu .menu-section {
            padding: 0.5rem 1.5rem;
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 1rem;
        }
        
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 2rem;
            min-height: 100vh;
        }
        
        .admin-top-bar {
            background: white;
            border-radius: 15px;
            padding: 1.5rem 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .admin-welcome h1 {
            margin: 0;
            color: var(--dark-color);
            font-weight: 700;
            font-size: 1.8rem;
        }
        
        .admin-welcome p {
            margin: 0.25rem 0 0 0;
            color: #6b7280;
            font-size: 1rem;
        }
        
        .admin-actions {
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        
        .admin-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .admin-stat-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
            border-left: 5px solid;
            transition: transform 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .admin-stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        .admin-stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100px;
            height: 100px;
            background: linear-gradient(45deg, rgba(255,255,255,0.1), transparent);
            transform: translateX(50%) translateY(-50%) rotate(45deg);
        }
        
        .admin-stat-card.total { border-left-color: var(--info-color); }
        .admin-stat-card.pending { border-left-color: var(--warning-color); }
        .admin-stat-card.progress { border-left-color: var(--primary-color); }
        .admin-stat-card.urgent { border-left-color: var(--danger-color); }
        
        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }
        
        .stat-number {
            font-size: 3rem;
            font-weight: 800;
            margin: 0;
            line-height: 1;
        }
        
        .stat-icon {
            font-size: 2.5rem;
            opacity: 0.8;
        }
        
        .stat-label {
            color: #6b7280;
            font-size: 1rem;
            margin: 0;
            font-weight: 500;
        }
        
        .stat-change {
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }
        
        .content-section {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f3f4f6;
        }
        
        .section-header h2 {
            margin: 0;
            color: var(--dark-color);
            font-weight: 700;
            font-size: 1.5rem;
        }
        
        .btn-admin-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: 10px;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
            color: white;
        }
        
        .btn-admin-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(220, 38, 38, 0.3);
            color: white;
        }
        
        .admin-filter-bar {
            background: linear-gradient(135deg, #f8fafc, #f1f5f9);
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
            border: 1px solid #e2e8f0;
        }
        
        .admin-ticket-card {
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .admin-ticket-card:hover {
            border-color: var(--primary-color);
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        .admin-ticket-card.urgent {
            border-left: 6px solid var(--danger-color);
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.02), white);
        }
        
        .admin-ticket-card.high {
            border-left: 6px solid var(--warning-color);
        }
        
        .ticket-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1.5rem;
        }
        
        .ticket-number {
            font-weight: 700;
            color: var(--primary-color);
            font-size: 1.2rem;
        }
        
        .ticket-priority-urgent {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.7; }
            100% { opacity: 1; }
        }
        
        .ticket-title {
            font-weight: 600;
            color: var(--dark-color);
            margin: 0.75rem 0;
            font-size: 1.1rem;
        }
        
        .ticket-user {
            color: #6b7280;
            font-size: 0.95rem;
            margin-bottom: 1rem;
        }
        
        .ticket-description {
            color: #6b7280;
            margin-bottom: 1.5rem;
            line-height: 1.6;
        }
        
        .ticket-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .ticket-actions {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
        }
        
        .badge {
            border-radius: 25px;
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .badge.status-pending { background: rgba(245, 158, 11, 0.15); color: var(--warning-color); }
        .badge.status-in_progress { background: rgba(79, 70, 229, 0.15); color: var(--info-color); }
        .badge.status-resolved { background: rgba(16, 185, 129, 0.15); color: var(--success-color); }
        .badge.status-closed { background: rgba(107, 114, 128, 0.15); color: #6b7280; }
        
        .badge.priority-low { background: rgba(16, 185, 129, 0.15); color: var(--success-color); }
        .badge.priority-medium { background: rgba(245, 158, 11, 0.15); color: var(--warning-color); }
        .badge.priority-high { background: rgba(239, 68, 68, 0.15); color: var(--danger-color); }
        .badge.priority-urgent { 
            background: var(--danger-color); 
            color: white; 
            animation: pulse 2s infinite;
        }
        
        .alert {
            border: none;
            border-radius: 12px;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
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
            box-shadow: 0 0 0 0.2rem rgba(220, 38, 38, 0.1);
        }
        
        .security-event {
            padding: 1rem;
            border-left: 4px solid;
            margin-bottom: 0.75rem;
            border-radius: 0 8px 8px 0;
            background: #f8fafc;
        }
        
        .security-event.login_success { border-left-color: var(--success-color); }
        .security-event.login_failed { border-left-color: var(--danger-color); }
        .security-event.ticket_created { border-left-color: var(--info-color); }
        .security-event.ticket_updated { border-left-color: var(--warning-color); }
        
        @media (max-width: 768px) {
            .admin-sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            
            .admin-sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
            
            .admin-stats {
                grid-template-columns: 1fr;
            }
            
            .admin-top-bar {
                flex-direction: column;
                text-align: center;
                gap: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Admin Sidebar -->
    <nav class="admin-sidebar">
        <div class="admin-sidebar-header">
            <h4><i class="fas fa-shield-alt me-2"></i>Admin Panel</h4>
            <div class="admin-badge">AMMINISTRATORE</div>
            <p>Benvenuto, <?php echo htmlspecialchars($_SESSION['first_name']); ?>!</p>
        </div>
        
        <div class="admin-menu">
            <nav class="nav flex-column">
                <div class="menu-section">Dashboard</div>
                <a class="nav-link active" href="#dashboard">
                    <i class="fas fa-tachometer-alt"></i>Panoramica
                </a>
                <a class="nav-link" href="#analytics">
                    <i class="fas fa-chart-bar"></i>Analytics
                </a>
                
                <div class="menu-section">Gestione Ticket</div>
                <a class="nav-link" href="#all-tickets">
                    <i class="fas fa-ticket-alt"></i>Tutti i Ticket
                    <?php if ($stats['pending_tickets'] > 0): ?>
                        <span class="badge bg-warning ms-auto"><?php echo $stats['pending_tickets']; ?></span>
                    <?php endif; ?>
                </a>
                <a class="nav-link" href="#urgent-tickets">
                    <i class="fas fa-exclamation-triangle"></i>Urgenti
                    <?php if ($stats['urgent_tickets'] > 0): ?>
                        <span class="badge bg-danger ms-auto"><?php echo $stats['urgent_tickets']; ?></span>
                    <?php endif; ?>
                </a>
                <a class="nav-link" href="#my-tickets">
                    <i class="fas fa-user-check"></i>Assegnati a Me
                </a>
                
                <div class="menu-section">Sistema</div>
                <a class="nav-link" href="#users">
                    <i class="fas fa-users"></i>Gestione Utenti
                </a>
                <a class="nav-link" href="#security-logs" data-bs-toggle="modal" data-bs-target="#securityLogsModal">
                    <i class="fas fa-shield-alt"></i>Log di Sicurezza
                </a>
                <a class="nav-link" href="#settings">
                    <i class="fas fa-cog"></i>Impostazioni
                </a>
                <a class="nav-link" href="#reports">
                    <i class="fas fa-file-alt"></i>Report
                </a>
                
                <div class="menu-section">Account</div>
                <a class="nav-link" href="#profile">
                    <i class="fas fa-user"></i>Il Mio Profilo
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
        <div class="admin-top-bar">
            <div class="admin-welcome">
                <h1>Dashboard Amministratore</h1>
                <p>Sistema di gestione completa per l'assistenza IT</p>
            </div>
            <div class="admin-actions">
                <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#securityLogsModal">
                    <i class="fas fa-shield-alt me-2"></i>Log Sicurezza
                </button>
                <button class="btn btn-admin-primary" data-bs-toggle="modal" data-bs-target="#quickActionsModal">
                    <i class="fas fa-bolt me-2"></i>Azioni Rapide
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
        
        <!-- Statistics -->
        <div class="admin-stats">
            <div class="admin-stat-card total">
                <div class="stat-header">
                    <div>
                        <h3 class="stat-number" style="color: var(--info-color);">
                            <?php echo $stats['total_tickets']; ?>
                        </h3>
                        <p class="stat-label">Ticket Totali</p>
                        <div class="stat-change text-info">
                            <i class="fas fa-arrow-up me-1"></i>+12% questo mese
                        </div>
                    </div>
                    <i class="fas fa-ticket-alt stat-icon" style="color: var(--info-color);"></i>
                </div>
            </div>
            
            <div class="admin-stat-card pending">
                <div class="stat-header">
                    <div>
                        <h3 class="stat-number" style="color: var(--warning-color);">
                            <?php echo $stats['pending_tickets']; ?>
                        </h3>
                        <p class="stat-label">In Attesa</p>
                        <div class="stat-change text-warning">
                            <i class="fas fa-clock me-1"></i>Richiede attenzione
                        </div>
                    </div>
                    <i class="fas fa-hourglass-half stat-icon" style="color: var(--warning-color);"></i>
                </div>
            </div>
            
            <div class="admin-stat-card progress">
                <div class="stat-header">
                    <div>
                        <h3 class="stat-number" style="color: var(--primary-color);">
                            <?php echo $stats['in_progress_tickets']; ?>
                        </h3>
                        <p class="stat-label">In Lavorazione</p>
                        <div class="stat-change text-primary">
                            <i class="fas fa-cogs me-1"></i>Attivamente gestiti
                        </div>
                    </div>
                    <i class="fas fa-tools stat-icon" style="color: var(--primary-color);"></i>
                </div>
            </div>
            
            <div class="admin-stat-card urgent">
                <div class="stat-header">
                    <div>
                        <h3 class="stat-number ticket-priority-urgent" style="color: var(--danger-color);">
                            <?php echo $stats['urgent_tickets']; ?>
                        </h3>
                        <p class="stat-label">Urgenti Aperti</p>
                        <div class="stat-change text-danger">
                            <i class="fas fa-exclamation-triangle me-1"></i>Priorità massima
                        </div>
                    </div>
                    <i class="fas fa-fire stat-icon" style="color: var(--danger-color);"></i>
                </div>
            </div>
        </div>
        
        <!-- Tickets Management Section -->
        <div class="content-section">
            <div class="section-header">
                <h2><i class="fas fa-list-alt me-2"></i>Gestione Ticket</h2>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#adminFilterBar">
                        <i class="fas fa-filter me-1"></i>Filtri Avanzati
                    </button>
                    <div class="dropdown">
                        <button class="btn btn-admin-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-download me-1"></i>Esporta
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#"><i class="fas fa-file-excel me-2"></i>Excel</a></li>
                            <li><a class="dropdown-item" href="#"><i class="fas fa-file-pdf me-2"></i>PDF</a></li>
                            <li><a class="dropdown-item" href="#"><i class="fas fa-file-csv me-2"></i>CSV</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Advanced Filter Bar -->
            <div class="collapse admin-filter-bar" id="adminFilterBar">
                <form method="GET" class="row g-3">
                    <div class="col-md-2">
                        <label class="form-label">Stato</label>
                        <select name="status" class="form-select">
                            <option value="">Tutti</option>
                            <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>In Attesa</option>
                            <option value="in_progress" <?php echo $status_filter === 'in_progress' ? 'selected' : ''; ?>>In Lavorazione</option>
                            <option value="resolved" <?php echo $status_filter === 'resolved' ? 'selected' : ''; ?>>Risolto</option>
                            <option value="closed" <?php echo $status_filter === 'closed' ? 'selected' : ''; ?>>Chiuso</option>
                        </select>
                    </div>
                    
                    <div class="col-md-2">
                        <label class="form-label">Priorità</label>
                        <select name="priority" class="form-select">
                            <option value="">Tutte</option>
                            <option value="urgent" <?php echo $priority_filter === 'urgent' ? 'selected' : ''; ?>>Urgente</option>
                            <option value="high" <?php echo $priority_filter === 'high' ? 'selected' : ''; ?>>Alta</option>
                            <option value="medium" <?php echo $priority_filter === 'medium' ? 'selected' : ''; ?>>Media</option>
                            <option value="low" <?php echo $priority_filter === 'low' ? 'selected' : ''; ?>>Bassa</option>
                        </select>
                    </div>
                    
                    <div class="col-md-2">
                        <label class="form-label">Assegnazione</label>
                        <select name="assigned" class="form-select">
                            <option value="">Tutti</option>
                            <option value="unassigned" <?php echo $assigned_filter === 'unassigned' ? 'selected' : ''; ?>>Non assegnati</option>
                            <option value="me" <?php echo $assigned_filter === 'me' ? 'selected' : ''; ?>>Assegnati a me</option>
                            <?php foreach ($admin_users as $admin): ?>
                                <option value="<?php echo $admin['id']; ?>" 
                                        <?php echo $assigned_filter == $admin['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">Ricerca Globale</label>
                        <input type="text" name="search" class="form-control" 
                               placeholder="Cerca in titolo, descrizione, numero ticket, email utente..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-admin-primary">
                                <i class="fas fa-search me-1"></i>Cerca
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Tickets List -->
            <div class="tickets-list">
                <?php if (empty($tickets)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-ticket-alt fa-4x text-muted mb-3"></i>
                        <h4 class="text-muted">Nessun ticket trovato</h4>
                        <p class="text-muted">Modifica i filtri per visualizzare altri ticket.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($tickets as $ticket): ?>
                        <div class="admin-ticket-card <?php echo $ticket['priority']; ?>">
                            <div class="ticket-header">
                                <div>
                                    <div class="ticket-number">
                                        #<?php echo htmlspecialchars($ticket['ticket_number']); ?>
                                        <?php if ($ticket['priority'] === 'urgent'): ?>
                                            <i class="fas fa-fire ms-2 text-danger ticket-priority-urgent"></i>
                                        <?php endif; ?>
                                    </div>
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
                                                'urgent' => 'URGENTE'
                                            ];
                                            echo $priority_labels[$ticket['priority']] ?? $ticket['priority'];
                                            ?>
                                        </span>
                                        <?php if ($ticket['category']): ?>
                                            <span class="badge bg-secondary"><?php echo htmlspecialchars($ticket['category']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <h4 class="ticket-title"><?php echo htmlspecialchars($ticket['title']); ?></h4>
                            
                            <div class="ticket-user">
                                <i class="fas fa-user me-2"></i>
                                <strong><?php echo htmlspecialchars($ticket['first_name'] . ' ' . $ticket['last_name']); ?></strong>
                                (<?php echo htmlspecialchars($ticket['email']); ?>)
                            </div>
                            
                            <p class="ticket-description">
                                <?php echo htmlspecialchars(substr($ticket['description'], 0, 200)) . 
                                          (strlen($ticket['description']) > 200 ? '...' : ''); ?>
                            </p>
                            
                            <div class="ticket-meta">
                                <div>
                                    <small class="text-muted">
                                        <i class="fas fa-calendar me-1"></i>Creato: 
                                        <?php echo date('d/m/Y H:i', strtotime($ticket['created_at'])); ?>
                                    </small>
                                </div>
                                <div>
                                    <small class="text-muted">
                                        <i class="fas fa-clock me-1"></i>Aggiornato: 
                                        <?php echo date('d/m/Y H:i', strtotime($ticket['updated_at'])); ?>
                                    </small>
                                </div>
                                <div>
                                    <small class="text-muted">
                                        <i class="fas fa-user-cog me-1"></i>Assegnato: 
                                        <?php 
                                        if ($ticket['assigned_to']) {
                                            echo htmlspecialchars($ticket['assigned_first_name'] . ' ' . $ticket['assigned_last_name']);
                                        } else {
                                            echo '<span class="text-warning">Non assegnato</span>';
                                        }
                                        ?>
                                    </small>
                                </div>
                                <?php if ($ticket['update_count'] > 0): ?>
                                    <div>
                                        <small class="text-muted">
                                            <i class="fas fa-comments me-1"></i>
                                            <?php echo $ticket['update_count']; ?> aggiornamenti
                                        </small>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($ticket['admin_notes']): ?>
                                <div class="alert alert-info mt-3">
                                    <small><strong>Note Admin:</strong> <?php echo htmlspecialchars($ticket['admin_notes']); ?></small>
                                </div>
                            <?php endif; ?>
                            
                            <div class="ticket-actions">
                                <button class="btn btn-outline-primary btn-sm" 
                                        onclick="editTicket(<?php echo $ticket['id']; ?>)">
                                    <i class="fas fa-edit me-1"></i>Modifica
                                </button>
                                
                                <?php if ($ticket['status'] === 'pending'): ?>
                                    <button class="btn btn-warning btn-sm" 
                                            onclick="updateTicketStatus(<?php echo $ticket['id']; ?>, 'in_progress')">
                                        <i class="fas fa-play me-1"></i>Inizia Lavorazione
                                    </button>
                                <?php endif; ?>
                                
                                <?php if ($ticket['status'] === 'in_progress'): ?>
                                    <button class="btn btn-success btn-sm" 
                                            onclick="updateTicketStatus(<?php echo $ticket['id']; ?>, 'resolved')">
                                        <i class="fas fa-check me-1"></i>Risolvi
                                    </button>
                                <?php endif; ?>
                                
                                <?php if ($ticket['status'] === 'resolved'): ?>
                                    <button class="btn btn-secondary btn-sm" 
                                            onclick="updateTicketStatus(<?php echo $ticket['id']; ?>, 'closed')">
                                        <i class="fas fa-archive me-1"></i>Chiudi
                                    </button>
                                <?php endif; ?>
                                
                                <div class="dropdown d-inline">
                                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" 
                                            type="button" data-bs-toggle="dropdown">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="#" onclick="assignTicket(<?php echo $ticket['id']; ?>)">
                                            <i class="fas fa-user-check me-2"></i>Assegna
                                        </a></li>
                                        <li><a class="dropdown-item" href="#" onclick="viewHistory(<?php echo $ticket['id']; ?>)">
                                            <i class="fas fa-history me-2"></i>Cronologia
                                        </a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item text-danger" href="#" 
                                               onclick="deleteTicket(<?php echo $ticket['id']; ?>, '<?php echo htmlspecialchars($ticket['ticket_number']); ?>')">
                                            <i class="fas fa-trash me-2"></i>Elimina
                                        </a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <nav>
                            <ul class="pagination justify-content-center">
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
    
    <!-- Security Logs Modal -->
    <div class="modal fade" id="securityLogsModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-shield-alt me-2"></i>Log di Sicurezza
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            Ultimi 10 eventi di sicurezza registrati dal sistema
                        </small>
                    </div>
                    
                    <?php foreach ($security_events as $event): ?>
                        <div class="security-event <?php echo $event['event_type']; ?>">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <strong><?php echo htmlspecialchars($event['event_type']); ?></strong>
                                    <?php if ($event['first_name']): ?>
                                        - <?php echo htmlspecialchars($event['first_name'] . ' ' . $event['last_name']); ?>
                                    <?php endif; ?>
                                    <br>
                                    <small class="text-muted">
                                        <?php echo htmlspecialchars($event['details']); ?>
                                    </small>
                                </div>
                                <small class="text-muted">
                                    <?php echo date('d/m/Y H:i:s', strtotime($event['created_at'])); ?>
                                    <br>
                                    <i class="fas fa-globe me-1"></i><?php echo htmlspecialchars($event['ip_address']); ?>
                                </small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Edit Ticket Modal -->
    <div class="modal fade" id="editTicketModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>Modifica Ticket
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="editTicketForm">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="action" value="update_ticket">
                    <input type="hidden" name="ticket_id" id="editTicketId">
                    
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="editStatus" class="form-label">Stato</label>
                                    <select class="form-select" id="editStatus" name="new_status">
                                        <option value="pending">In Attesa</option>
                                        <option value="in_progress">In Lavorazione</option>
                                        <option value="resolved">Risolto</option>
                                        <option value="closed">Chiuso</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="editAssigned" class="form-label">Assegna a</label>
                                    <select class="form-select" id="editAssigned" name="assigned_to">
                                        <option value="">Non assegnato</option>
                                        <?php foreach ($admin_users as $admin): ?>
                                            <option value="<?php echo $admin['id']; ?>">
                                                <?php echo htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="editAdminNotes" class="form-label">Note Amministrative</label>
                            <textarea class="form-control" id="editAdminNotes" name="admin_notes" rows="4" 
                                      placeholder="Aggiungi note visibili solo agli amministratori..."></textarea>
                            <div class="form-text">
                                <i class="fas fa-lock me-1"></i>
                                Queste note sono visibili solo agli amministratori
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Annulla
                        </button>
                        <button type="submit" class="btn btn-admin-primary">
                            <i class="fas fa-save me-2"></i>Salva Modifiche
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Hidden Forms -->
    <form method="POST" id="quickUpdateForm" style="display: none;">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        <input type="hidden" name="action" value="update_ticket">
        <input type="hidden" name="ticket_id" id="quickTicketId">
        <input type="hidden" name="new_status" id="quickNewStatus">
    </form>
    
    <form method="POST" id="deleteForm" style="display: none;">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        <input type="hidden" name="action" value="delete_ticket">
        <input type="hidden" name="ticket_id" id="deleteTicketId">
    </form>
    
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script>
        // Quick ticket status update
        function updateTicketStatus(ticketId, newStatus) {
            document.getElementById('quickTicketId').value = ticketId;
            document.getElementById('quickNewStatus').value = newStatus;
            document.getElementById('quickUpdateForm').submit();
        }
        
        // Edit ticket modal
        function editTicket(ticketId) {
            document.getElementById('editTicketId').value = ticketId;
            
            // In a real application, you would load the current ticket data here
            // For now, we just show the modal
            const modal = new bootstrap.Modal(document.getElementById('editTicketModal'));
            modal.show();
        }
        
        // Delete ticket with confirmation
        function deleteTicket(ticketId, ticketNumber) {
            if (confirm(`Sei sicuro di voler eliminare il ticket #${ticketNumber}?\n\nQuesta azione non può essere annullata e eliminerà anche tutti i commenti e gli allegati associati.`)) {
                document.getElementById('deleteTicketId').value = ticketId;
                document.getElementById('deleteForm').submit();
            }
        }
        
        // Assign ticket (placeholder)
        function assignTicket(ticketId) {
            editTicket(ticketId);
        }
        
        // View ticket history (placeholder)
        function viewHistory(ticketId) {
            alert('Visualizza cronologia ticket #' + ticketId + ' - Funzionalità da implementare');
        }
        
        // Auto-refresh page every 2 minutes for real-time updates
        setTimeout(function() {
            location.reload();
        }, 120000);
        
        // Mobile sidebar toggle
        function toggleAdminSidebar() {
            document.querySelector('.admin-sidebar').classList.toggle('show');
        }
        
        // Add mobile menu button for small screens
        if (window.innerWidth <= 768) {
            const welcomeDiv = document.querySelector('.admin-welcome');
            const menuBtn = document.createElement('button');
            menuBtn.innerHTML = '<i class="fas fa-bars"></i>';
            menuBtn.className = 'btn btn-outline-primary btn-sm me-3';
            menuBtn.onclick = toggleAdminSidebar;
            welcomeDiv.parentNode.insertBefore(menuBtn, welcomeDiv);
        }
        
        // Update statistics in real-time (every 30 seconds)
        setInterval(function() {
            // In a real application, you would make an AJAX call here
            // to update the statistics without refreshing the page
        }, 30000);
    </script>
</body>
</html>
