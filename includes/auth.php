<?php
require_once 'db.php';
require_once 'session.php';

function registerUser($ad, $email, $sifre) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT id FROM User WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        return ['success' => false, 'message' => 'Bu email adresi zaten kullanılıyor.'];
    }
    
    $userId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
    
    $hashedPassword = password_hash($sifre, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO User (id, full_name, email, password, role, balance) VALUES (?, ?, ?, ?, 'user', 800)");
    
    if ($stmt->execute([$userId, $ad, $email, $hashedPassword])) {
        return ['success' => true, 'message' => 'Kayıt başarılı! Giriş yapabilirsiniz.'];
    } else {
        return ['success' => false, 'message' => 'Kayıt sırasında bir hata oluştu.'];
    }
}

function loginUser($email, $sifre) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM User WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($sifre, $user['password'])) {
        login($user['id']);
        return ['success' => true, 'message' => 'Giriş başarılı!', 'user' => $user];
    } else {
        return ['success' => false, 'message' => 'Email veya şifre hatalı.'];
    }
}

function validateCoupon($kod, $companyId = null) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM Coupons WHERE code = ? AND expire_date >= datetime('now') AND usage_limit > 0");
    $stmt->execute([$kod]);
    $coupon = $stmt->fetch();
    
    if (!$coupon) {
        return false;
    }
    
    if ($coupon['company_id'] && $companyId && $coupon['company_id'] !== $companyId) {
        return false;
    }
    
    return $coupon;
}

function useCoupon($kod) {
    global $pdo;
    
    $stmt = $pdo->prepare("UPDATE Coupons SET usage_limit = usage_limit - 1 WHERE code = ?");
    return $stmt->execute([$kod]);
}

function getAvailableSeats($tripId) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT bs.seat_number 
        FROM Booked_Seats bs 
        JOIN Tickets t ON bs.ticket_id = t.id 
        WHERE t.trip_id = ? AND t.status = 'active'
    ");
    $stmt->execute([$tripId]);
    $occupiedSeats = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $stmt = $pdo->prepare("SELECT capacity FROM Trips WHERE id = ?");
    $stmt->execute([$tripId]);
    $totalSeats = $stmt->fetchColumn();
    
    $availableSeats = [];
    for ($i = 1; $i <= $totalSeats; $i++) {
        if (!in_array($i, $occupiedSeats)) {
            $availableSeats[] = $i;
        }
    }
    
    return $availableSeats;
}

function canCancelTicket($ticketId) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT t.*, tr.departure_time 
        FROM Tickets t 
        JOIN Trips tr ON t.trip_id = tr.id 
        WHERE t.id = ? AND t.status = 'active'
    ");
    $stmt->execute([$ticketId]);
    $ticket = $stmt->fetch();
    
    if (!$ticket) {
        return false;
    }
    
    $tripDateTime = new DateTime($ticket['departure_time']);
    $now = new DateTime();
    $diff = $tripDateTime->getTimestamp() - $now->getTimestamp();
    
    return $diff > 3600;
}
?>
