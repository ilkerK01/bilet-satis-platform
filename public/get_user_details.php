<?php
require_once '../includes/auth.php';


requireRole('admin');

header('Content-Type: application/json');

$userId = $_GET['user_id'] ?? '';

if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'Kullanıcı ID gerekli']);
    exit;
}

try {

    $stmt = $pdo->prepare("SELECT * FROM User WHERE id = ? AND role = 'user'");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Kullanıcı bulunamadı']);
        exit;
    }
    

    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_tickets,
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_tickets,
            SUM(CASE WHEN status = 'canceled' THEN 1 ELSE 0 END) as canceled_tickets,
            SUM(CASE WHEN status = 'active' THEN total_price ELSE 0 END) as total_spent
        FROM Tickets 
        WHERE user_id = ?
    ");
    $stmt->execute([$userId]);
    $stats = $stmt->fetch();
    

    $stmt = $pdo->prepare("
        SELECT t.*, tr.departure_city, tr.destination_city, tr.departure_time, bs.seat_number
        FROM Tickets t
        JOIN Trips tr ON t.trip_id = tr.id
        LEFT JOIN Booked_Seats bs ON t.id = bs.ticket_id
        WHERE t.user_id = ?
        ORDER BY t.created_at DESC
        LIMIT 10
    ");
    $stmt->execute([$userId]);
    $tickets = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'user' => $user,
        'stats' => [
            'total_tickets' => (int)$stats['total_tickets'],
            'active_tickets' => (int)$stats['active_tickets'],
            'canceled_tickets' => (int)$stats['canceled_tickets'],
            'total_spent' => (float)$stats['total_spent']
        ],
        'tickets' => $tickets
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası']);
}
?>
