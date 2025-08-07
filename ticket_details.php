<?php
/**
 * IT Support Ticketing System - Ticket Details
 * View and manage individual ticket details
 */
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$pdo = getDBConnection();
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];
$ticket_id = $_GET['id'] ?? null;

if (!$ticket_id || !is_numeric($ticket_id)) {
    header('Location: ' . ($user_role === 'admin' ? 'admin_dashboard.php' : 'user_dashboard.php'));
    exit;
}

// Get ticket details
$sql = "
    SELECT 
        t.*,
        u.first_name as user_first_name,
        u.last_name as user_last_name,
        u.email as user_email,
        a.first_name as assigned_first_name,
        a.last_name as assigned_last_name,
        a.email as assigned_email
    FROM tickets t
    LEFT JOIN users u ON t.user_id = u.id
    LEFT JOIN users a ON t.assigned_to = a.id
    WHERE t.id = ?
";

// If not admin, only show user's own tickets
if ($user_role !== 'admin') {
    $sql .= " AND t.user_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$ticket_id, $user_id]);
} else {
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$ticket_id]);
}

$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ticket) {
    header('Location: ' . ($user_role === 'admin' ? 'admin_dashboard.php' : 'user_dashboard.php'));
    exit;
}

// Handle comment submission
$comment_error = '';
$comment_success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_comment'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $comment_error = 'Token di sicurezza non valido.';
    } else {
        $comment = trim($_POST['comment'] ?? '');
        $is_internal = isset($_POST['is_internal']) && $user_role === 'admin';
        
        if (empty($comment)) {
            $comment_error = 'Il commento non può essere vuoto.';
        } else {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO ticket_comments (ticket_id, user_id, comment, is_internal, created_at)
                    VALUES (?, ?, ?, ?, NOW())
                ");
                $stmt->execute([$ticket_id, $user_id, $comment, $is_internal ? 1 : 0]);
                
                // Update ticket's last activity
                $stmt = $pdo->prepare("UPDATE tickets SET updated_at = NOW() WHERE id = ?");
                $stmt->execute([$ticket_id]);
                
                $comment_success = 'Commento aggiunto con successo.';
                
                // Log activity
                logSecurityEvent('comment_added', "Comment added to ticket #$ticket_id", $user_id);
                
                // Refresh page to show new comment
                header("Location: ticket_details.php?id=$ticket_id");
                exit;
            } catch (PDOException $e) {
                error_log("Comment error: " . $e->getMessage());
                $comment_error = 'Errore durante l\'aggiunta del commento.';
            }
        }
    }
}

// Handle status/priority updates (admin only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_ticket']) && $user_role === 'admin') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $comment_error = 'Token di sicurezza non valido.';
    } else {
        $new_status = $_POST['status'] ?? '';
        $new_priority = $_POST['priority'] ?? '';
        $assigned_to = $_POST['assigned_to'] ?? null;
        
        if ($assigned_to === '') $assigned_to = null;
        
        try {
            $stmt = $pdo->prepare("
                UPDATE tickets 
                SET status = ?, priority = ?, assigned_to = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$new_status, $new_priority, $assigned_to, $ticket_id]);
            
            $comment_success = 'Ticket aggiornato con successo.';
            
            // Refresh ticket data
            header("Location: ticket_details.php?id=$ticket_id");
            exit;
        } catch (PDOException $e) {
            error_log("Ticket update error: " . $e->getMessage());
            $comment_error = 'Errore durante l\'aggiornamento del ticket.';
        }
    }
}

// Get ticket comments
$stmt = $pdo->prepare("
    SELECT 
        tc.*,
        u.first_name,
        u.last_name,
        u.email,
        u.role
    FROM ticket_comments tc
    LEFT JOIN users u ON tc.user_id = u.id
    WHERE tc.ticket_id = ? AND (tc.is_internal = 0 OR ? = 'admin')
    ORDER BY tc.created_at ASC
");
$stmt->execute([$ticket_id, $user_role]);
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get available technicians for assignment (admin only)
$technicians = [];
if ($user_role === 'admin') {
    $stmt = $pdo->prepare("SELECT id, first_name, last_name, email FROM users WHERE role = 'admin' AND status = 'active'");
    $stmt->execute();
    $technicians = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Status and priority arrays
$statuses = [
    'open' => ['label' => 'Aperto', 'class' => 'primary'],
    'in_progress' => ['label' => 'In Lavorazione', 'class' => 'warning'],
    'waiting_user' => ['label' => 'In Attesa Utente', 'class' => 'info'],
    'resolved' => ['label' => 'Risolto', 'class' => 'success'],
    'closed' => ['label' => 'Chiuso', 'class' => 'secondary']
];

$priorities = [
    'low' => ['label' => 'Bassa', 'class' => 'success'],
    'medium' => ['label' => 'Media', 'class' => 'warning'],
    'high' => ['label' => 'Alta', 'class' => 'danger'],
    'urgent' => ['label' => 'Urgente', 'class' => 'danger']
];
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket #<?php echo $ticket['id']; ?> - <?php echo SYSTEM_NAME; ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #10b981;
            --primary-dark: #059669;
            --secondary-color: #34d399;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
            --info-color: #3b82f6;
            --dark-color: #1f2937;
            --light-color: #f8fafc;
        }
        
        * {
            font-family: 'Inter', sans-serif;
        }
        
        body {
            background-color: var(--light-color);
            min-height: 100vh;
        }
        
        .navbar {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
        }
        
        .main-content {
            padding: 2rem 0;
        }
        
        .ticket-header {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .ticket-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 1rem;
        }
        
        .ticket-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .meta-item {
            background: var(--light-color);
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.9rem;
        }
        
        .meta-label {
            font-weight: 600;
            color: #6b7280;
        }
        
        .meta-value {
            color: var(--dark-color);
        }
        
        .ticket-content {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
        }
        
        .content-header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid #e5e7eb;
            font-weight: 600;
            color: var(--dark-color);
        }
        
        .content-body {
            padding: 2rem;
        }
        
        .ticket-description {
            background: #f8fafc;
            border-left: 4px solid var(--primary-color);
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            line-height: 1.6;
        }
        
        .comments-section {
            max-height: 500px;
            overflow-y: auto;
        }
        
        .comment {
            background: #f8fafc;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            border-left: 4px solid var(--primary-color);
        }
        
        .comment.internal {
            background: #fef3c7;
            border-left-color: var(--warning-color);
        }
        
        .comment-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }
        
        .comment-author {
            font-weight: 600;
            color: var(--dark-color);
        }
        
        .comment-date {
            color: #6b7280;
        }
        
        .comment-body {
            line-height: 1.6;
            color: var(--dark-color);
        }
        
        .form-control {
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            transition: border-color 0.3s;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(16, 185, 129, 0.1);
        }
        
        .btn-primary {
            background: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background: var(--primary-dark);
            border-color: var(--primary-dark);
        }
        
        .btn-outline-primary {
            color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-outline-primary:hover {
            background: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .priority-badge {
            padding: 0.4rem 0.8rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .admin-controls {
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .back-button {
            background: white;
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
            text-decoration: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .back-button:hover {
            background: var(--primary-color);
            color: white;
            text-decoration: none;
        }
        
        @media (max-width: 768px) {
            .ticket-header, .content-body {
                padding: 1.5rem;
            }
            
            .ticket-meta {
                flex-direction: column;
            }
            
            .meta-item {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand fw-bold" href="<?php echo $user_role === 'admin' ? 'admin_dashboard.php' : 'user_dashboard.php'; ?>">
                <i class="fas fa-headset me-2"></i><?php echo SYSTEM_NAME; ?>
            </a>
            
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">
                    <i class="fas fa-user me-1"></i>
                    <?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?>
                </span>
                <a href="logout.php" class="btn btn-light btn-sm">
                    <i class="fas fa-sign-out-alt me-1"></i>Logout
                </a>
            </div>
        </div>
    </nav>
    
    <div class="container main-content">
        <!-- Back Button -->
        <div class="mb-3">
            <a href="<?php echo $user_role === 'admin' ? 'admin_dashboard.php' : 'user_dashboard.php'; ?>" class="back-button">
                <i class="fas fa-arrow-left"></i>
                Torna alla Dashboard
            </a>
        </div>
        
        <!-- Ticket Header -->
        <div class="ticket-header">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div class="ticket-title">
                    <i class="fas fa-ticket-alt me-2 text-primary"></i>
                    Ticket #<?php echo $ticket['id']; ?>: <?php echo htmlspecialchars($ticket['title']); ?>
                </div>
                <div class="d-flex gap-2">
                    <span class="status-badge bg-<?php echo $statuses[$ticket['status']]['class']; ?> text-white">
                        <?php echo $statuses[$ticket['status']]['label']; ?>
                    </span>
                    <span class="priority-badge bg-<?php echo $priorities[$ticket['priority']]['class']; ?> text-white">
                        <?php echo $priorities[$ticket['priority']]['label']; ?>
                    </span>
                </div>
            </div>
            
            <div class="ticket-meta">
                <div class="meta-item">
                    <div class="meta-label">Creato da:</div>
                    <div class="meta-value">
                        <i class="fas fa-user me-1"></i>
                        <?php echo htmlspecialchars($ticket['user_first_name'] . ' ' . $ticket['user_last_name']); ?>
                    </div>
                </div>
                
                <div class="meta-item">
                    <div class="meta-label">Email:</div>
                    <div class="meta-value">
                        <i class="fas fa-envelope me-1"></i>
                        <?php echo htmlspecialchars($ticket['user_email']); ?>
                    </div>
                </div>
                
                <div class="meta-item">
                    <div class="meta-label">Data Creazione:</div>
                    <div class="meta-value">
                        <i class="fas fa-calendar me-1"></i>
                        <?php echo date('d/m/Y H:i', strtotime($ticket['created_at'])); ?>
                    </div>
                </div>
                
                <div class="meta-item">
                    <div class="meta-label">Ultimo Aggiornamento:</div>
                    <div class="meta-value">
                        <i class="fas fa-clock me-1"></i>
                        <?php echo date('d/m/Y H:i', strtotime($ticket['updated_at'])); ?>
                    </div>
                </div>
                
                <?php if ($ticket['assigned_to']): ?>
                <div class="meta-item">
                    <div class="meta-label">Assegnato a:</div>
                    <div class="meta-value">
                        <i class="fas fa-user-tie me-1"></i>
                        <?php echo htmlspecialchars($ticket['assigned_first_name'] . ' ' . $ticket['assigned_last_name']); ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Admin Controls -->
        <?php if ($user_role === 'admin'): ?>
        <div class="admin-controls">
            <h5 class="mb-3">
                <i class="fas fa-cogs me-2"></i>Controlli Amministratore
            </h5>
            
            <?php if ($comment_error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo htmlspecialchars($comment_error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($comment_success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo htmlspecialchars($comment_success); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="row g-3">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                
                <div class="col-md-3">
                    <label for="status" class="form-label">Stato</label>
                    <select class="form-select" id="status" name="status">
                        <?php foreach ($statuses as $value => $status): ?>
                            <option value="<?php echo $value; ?>" <?php echo $ticket['status'] === $value ? 'selected' : ''; ?>>
                                <?php echo $status['label']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="priority" class="form-label">Priorità</label>
                    <select class="form-select" id="priority" name="priority">
                        <?php foreach ($priorities as $value => $priority): ?>
                            <option value="<?php echo $value; ?>" <?php echo $ticket['priority'] === $value ? 'selected' : ''; ?>>
                                <?php echo $priority['label']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-4">
                    <label for="assigned_to" class="form-label">Assegna a</label>
                    <select class="form-select" id="assigned_to" name="assigned_to">
                        <option value="">Nessuno</option>
                        <?php foreach ($technicians as $tech): ?>
                            <option value="<?php echo $tech['id']; ?>" <?php echo $ticket['assigned_to'] == $tech['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($tech['first_name'] . ' ' . $tech['last_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" name="update_ticket" class="btn btn-primary w-100">
                        <i class="fas fa-save me-1"></i>Aggiorna
                    </button>
                </div>
            </form>
        </div>
        <?php endif; ?>
        
        <!-- Ticket Description -->
        <div class="ticket-content">
            <div class="content-header">
                <i class="fas fa-file-alt me-2"></i>Descrizione del Problema
            </div>
            <div class="content-body">
                <div class="ticket-description">
                    <?php echo nl2br(htmlspecialchars($ticket['description'])); ?>
                </div>
            </div>
        </div>
        
        <!-- Comments Section -->
        <div class="ticket-content">
            <div class="content-header">
                <i class="fas fa-comments me-2"></i>
                Commenti e Comunicazioni (<?php echo count($comments); ?>)
            </div>
            <div class="content-body">
                <!-- Existing Comments -->
                <div class="comments-section mb-4">
                    <?php if (empty($comments)): ?>
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-comment-slash fa-2x mb-2"></i>
                            <p>Nessun commento presente per questo ticket.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($comments as $comment): ?>
                            <div class="comment <?php echo $comment['is_internal'] ? 'internal' : ''; ?>">
                                <div class="comment-header">
                                    <div class="comment-author">
                                        <i class="fas fa-user-circle me-1"></i>
                                        <?php echo htmlspecialchars($comment['first_name'] . ' ' . $comment['last_name']); ?>
                                        <?php if ($comment['role'] === 'admin'): ?>
                                            <span class="badge bg-primary ms-1">Staff</span>
                                        <?php endif; ?>
                                        <?php if ($comment['is_internal']): ?>
                                            <span class="badge bg-warning ms-1">Interno</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="comment-date">
                                        <i class="fas fa-clock me-1"></i>
                                        <?php echo date('d/m/Y H:i', strtotime($comment['created_at'])); ?>
                                    </div>
                                </div>
                                <div class="comment-body">
                                    <?php echo nl2br(htmlspecialchars($comment['comment'])); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <!-- Add New Comment -->
                <div class="border-top pt-4">
                    <h6 class="mb-3">
                        <i class="fas fa-plus me-2"></i>Aggiungi Commento
                    </h6>
                    
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        
                        <div class="mb-3">
                            <textarea class="form-control" id="comment" name="comment" rows="4" 
                                      placeholder="Scrivi il tuo commento qui..." required></textarea>
                        </div>
                        
                        <?php if ($user_role === 'admin'): ?>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="is_internal" name="is_internal">
                            <label class="form-check-label" for="is_internal">
                                <i class="fas fa-eye-slash me-1"></i>
                                Commento interno (visibile solo al personale)
                            </label>
                        </div>
                        <?php endif; ?>
                        
                        <button type="submit" name="add_comment" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-1"></i>Invia Commento
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-scroll comments to bottom
            const commentsSection = document.querySelector('.comments-section');
            if (commentsSection && commentsSection.children.length > 0) {
                commentsSection.scrollTop = commentsSection.scrollHeight;
            }
            
            // Auto-resize textarea
            const textarea = document.getElementById('comment');
            if (textarea) {
                textarea.addEventListener('input', function() {
                    this.style.height = 'auto';
                    this.style.height = (this.scrollHeight) + 'px';
                });
            }
        });
    </script>
</body>
</html>
