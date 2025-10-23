<?php
require_once '../includes/auth.php';

requireRole('user');

$user = getCurrentUser();
$message = '';


if ($_POST && isset($_POST['cancel_ticket'])) {
    $ticketId = $_POST['ticket_id'];
    

    $stmt = $pdo->prepare("SELECT * FROM Tickets WHERE id = ? AND user_id = ?");
    $stmt->execute([$ticketId, $user['id']]);
    $ticket = $stmt->fetch();
    
    if ($ticket && canCancelTicket($ticketId)) {
        $pdo->beginTransaction();
        try {

            $stmt = $pdo->prepare("UPDATE Tickets SET status = 'canceled' WHERE id = ?");
            $stmt->execute([$ticketId]);
            

            updateUserCredit($user['id'], $ticket['total_price']);
            
            $pdo->commit();
            $message = 'Bilet başarıyla iptal edildi. Kredi hesabınıza iade edilmiştir.';
        } catch (Exception $e) {
            $pdo->rollback();
            $message = 'Bilet iptal edilirken bir hata oluştu.';
        }
    } else {
        $message = 'Bu bilet iptal edilemez. (Kalkışa 1 saatten az kaldı)';
    }
}


$stmt = $pdo->prepare("
    SELECT t.*, tr.departure_city, tr.destination_city, tr.departure_time, bc.name as firma_ad,
           bs.seat_number
    FROM Tickets t
    JOIN Trips tr ON t.trip_id = tr.id
    JOIN Bus_Company bc ON tr.company_id = bc.id
    LEFT JOIN Booked_Seats bs ON t.id = bs.ticket_id
    WHERE t.user_id = ?
    ORDER BY tr.departure_time DESC
");
$stmt->execute([$user['id']]);
$tickets = $stmt->fetchAll();


$stmt = $pdo->prepare("SELECT balance FROM User WHERE id = ?");
$stmt->execute([$user['id']]);
$currentCredit = $stmt->fetchColumn();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Biletlerim - Bilet Satış Platformu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="/">
                BILET SATIŞ PLATFORMU
            </a>
            <div class="navbar-nav ms-auto">
                <div class="navbar-user-info me-3">
                    <span class="user-welcome">
                        <?= htmlspecialchars($user['full_name']) ?>
                    </span>
                    <span class="user-balance">
                        Kredi: ₺<?= number_format($currentCredit, 2) ?>
                    </span>
                </div>
                <div class="navbar-buttons">
                    <a class="nav-btn" href="/">Ana Sayfa</a>
                    <a class="nav-btn nav-btn-logout" href="logout.php">Çıkış</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-3">
                <div class="card">
                    <div class="card-header">
                        <h6>Hesap Bilgileri</h6>
                    </div>
                    <div class="card-body">
                        <p><strong>Ad:</strong> <?= htmlspecialchars($user['full_name']) ?></p>
                        <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
                        <p><strong>Kredi:</strong> <span class="badge bg-success">₺<?= number_format($currentCredit, 2) ?></span></p>
                        <p><strong>Toplam Bilet:</strong> <?= count($tickets) ?></p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-9">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5>Biletlerim</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($message): ?>
                            <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
                        <?php endif; ?>
                        
                        <?php if (empty($tickets)): ?>
                            <div class="text-center py-5">
                                <h6>Henüz biletiniz bulunmuyor.</h6>
                                <a href="/" class="btn btn-primary">Sefer Ara</a>
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($tickets as $ticket): ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="card ticket-card <?= $ticket['status'] === 'canceled' ? 'cancelled' : '' ?>">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <h6 class="card-title"><?= htmlspecialchars($ticket['firma_ad']) ?></h6>
                                                    <span class="badge <?= $ticket['status'] === 'active' ? 'bg-success' : 'bg-danger' ?>">
                                                        <?= $ticket['status'] === 'active' ? 'Aktif' : 'İptal' ?>
                                                    </span>
                                                </div>
                                                
                                                <p class="card-text">
                                                    <strong><?= htmlspecialchars($ticket['departure_city']) ?></strong> → 
                                                    <strong><?= htmlspecialchars($ticket['destination_city']) ?></strong>
                                                </p>
                                                
                                                <p class="card-text">
                                                    <strong>TARİH:</strong> <?= date('d.m.Y', strtotime($ticket['departure_time'])) ?><br>
                                                    <strong>SAAT:</strong> <?= date('H:i', strtotime($ticket['departure_time'])) ?><br>
                                                    <strong>KOLTUK:</strong> <?= $ticket['seat_number'] ?><br>
                                                    <strong>FİYAT:</strong> ₺<?= number_format($ticket['total_price'], 2) ?>
                                                </p>
                                                
                                                <div class="d-flex gap-2">
                                                    <a href="bilet_pdf.php?id=<?= $ticket['id'] ?>" 
                                                       class="btn btn-sm btn-outline-primary" target="_blank">
                                                        PDF İNDİR
                                                    </a>
                                                    
                                                    <?php if ($ticket['status'] === 'active' && canCancelTicket($ticket['id'])): ?>
                                                        <form method="POST" style="display: inline;" 
                                                              onsubmit="return confirm('Bu bileti iptal etmek istediğinizden emin misiniz?')">
                                                            <input type="hidden" name="ticket_id" value="<?= $ticket['id'] ?>">
                                                            <button type="submit" name="cancel_ticket" class="btn btn-sm btn-outline-danger">
                                                                İPTAL ET
                                                            </button>
                                                        </form>
                                                    <?php elseif ($ticket['status'] === 'active'): ?>
                                                        <small class="text-muted">İptal edilemez (1 saatten az kaldı)</small>
                                                    <?php endif; ?>
                                                </div>
                                                
                                                <small class="text-muted">
                                                    Satın alındı: <?= date('d.m.Y H:i', strtotime($ticket['created_at'])) ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
