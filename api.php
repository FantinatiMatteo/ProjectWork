<?php
require_once 'config.php';

// API per notifiche real-time e analytics
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'notifications':
        if (isLoggedIn()) {
            $stmt = $pdo->prepare("
                SELECT n.*, t.title as ticket_title 
                FROM notifications n 
                LEFT JOIN tickets t ON n.ticket_id = t.id 
                WHERE n.user_id = ? AND n.read_status = FALSE 
                ORDER BY n.created_at DESC 
                LIMIT 10
            ");
            $stmt->execute([$_SESSION['user_id']]);
            $notifications = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'notifications' => $notifications,
                'count' => count($notifications)
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Not authenticated']);
        }
        break;

    case 'mark_read':
        if (isLoggedIn() && isset($_POST['notification_id'])) {
            $stmt = $pdo->prepare("UPDATE notifications SET read_status = TRUE WHERE id = ? AND user_id = ?");
            $stmt->execute([$_POST['notification_id'], $_SESSION['user_id']]);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false]);
        }
        break;

    case 'analytics':
        if (isAdmin()) {
            // Statistiche avanzate per admin
            $stats = [];
            
            // Ticket per giorno (ultimi 7 giorni)
            $stmt = $pdo->query("
                SELECT DATE(created_at) as date, COUNT(*) as count 
                FROM tickets 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                GROUP BY DATE(created_at)
                ORDER BY date DESC
            ");
            $stats['tickets_per_day'] = $stmt->fetchAll();
            
            // Distribuzione priorità
            $stmt = $pdo->query("
                SELECT priority, COUNT(*) as count 
                FROM tickets 
                GROUP BY priority
            ");
            $stats['priority_distribution'] = $stmt->fetchAll();
            
            // Tempo medio risoluzione
            $stmt = $pdo->query("
                SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, updated_at)) as avg_hours
                FROM tickets 
                WHERE status = 'risolto' AND updated_at > created_at
            ");
            $result = $stmt->fetch();
            $stats['avg_resolution_time'] = round($result['avg_hours'] ?? 0, 1);
            
            // Rating medio
            $stmt = $pdo->query("
                SELECT AVG(rating) as avg_rating, COUNT(*) as total_ratings
                FROM tickets 
                WHERE rating > 0
            ");
            $rating_data = $stmt->fetch();
            $stats['avg_rating'] = round($rating_data['avg_rating'] ?? 0, 1);
            $stats['total_ratings'] = $rating_data['total_ratings'] ?? 0;
            
            echo json_encode([
                'success' => true,
                'analytics' => $stats
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Admin access required']);
        }
        break;

    case 'live_stats':
        // Statistiche live per homepage
        $stmt = $pdo->query("
            SELECT 
                (SELECT COUNT(*) FROM tickets) as total_tickets,
                (SELECT COUNT(*) FROM users WHERE role = 'user') as total_users,
                (SELECT COUNT(*) FROM tickets WHERE status = 'risolto') as resolved_tickets,
                (SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, updated_at)) FROM tickets WHERE status = 'risolto') as avg_response_time
        ");
        $stats = $stmt->fetch();
        
        echo json_encode([
            'success' => true,
            'stats' => [
                'tickets' => $stats['total_tickets'] ?? 0,
                'users' => $stats['total_users'] ?? 0,
                'resolved' => $stats['resolved_tickets'] ?? 0,
                'response_time' => round($stats['avg_response_time'] ?? 2, 0)
            ]
        ]);
        break;

    case 'ticket_comments':
        if (isLoggedIn() && isset($_GET['ticket_id'])) {
            $ticket_id = (int)$_GET['ticket_id'];
            
            // Verifica che l'utente possa vedere questo ticket
            if (isAdmin()) {
                $ticket_check = true;
            } else {
                $stmt = $pdo->prepare("SELECT id FROM tickets WHERE id = ? AND user_id = ?");
                $stmt->execute([$ticket_id, $_SESSION['user_id']]);
                $ticket_check = $stmt->fetch();
            }
            
            if ($ticket_check) {
                $stmt = $pdo->prepare("
                    SELECT c.*, u.first_name, u.last_name, u.role
                    FROM ticket_comments c
                    JOIN users u ON c.user_id = u.id
                    WHERE c.ticket_id = ?
                    ORDER BY c.created_at ASC
                ");
                $stmt->execute([$ticket_id]);
                $comments = $stmt->fetchAll();
                
                echo json_encode([
                    'success' => true,
                    'comments' => $comments
                ]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Access denied']);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid request']);
        }
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
        break;
}
?>
