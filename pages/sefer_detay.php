<?php
require_once '../includes/auth.php';

$tripId = $_GET['id'] ?? 0;

if (!$tripId) {
    header('Location: /');
    exit;
}


$stmt = $pdo->prepare("
    SELECT t.*, f.ad as firma_ad 
    FROM trips t 
    JOIN firms f ON t.firma_id = f.id 
    WHERE t.id = ?
");
$stmt->execute([$tripId]);
$trip = $stmt->fetch();

if (!$trip) {
    header('Location: /');
    exit;
}


$availableSeats = getAvailableSeats($tripId);
$occupiedSeats = [];
for ($i = 1; $i <= $trip['koltuk_sayisi']; $i++) {
    if (!in_array($i, $availableSeats)) {
        $occupiedSeats[] = $i;
    }
}

$error = '';
$success = '';


if ($_POST && isLoggedIn()) {
    $seatNo = $_POST['seat_no'] ?? 0;
    $couponCode = $_POST['coupon_code'] ?? '';
    
    if (!$seatNo || !in_array($seatNo, $availableSeats)) {
        $error = 'Geçersiz koltuk seçimi.';
    } else {
        $user = getCurrentUser();
        $finalPrice = $trip['fiyat'];
        $discount = 0;
        

        if ($couponCode) {
            $coupon = validateCoupon($couponCode);
            if ($coupon) {
                $discount = ($finalPrice * $coupon['oran']) / 100;
                $finalPrice -= $discount;
            } else {
                $error = 'Geçersiz veya süresi dolmuş kupon kodu.';
            }
        }
        
        if (!$error) {

            if ($user['kredi'] < $finalPrice) {
                $error = 'Yetersiz kredi. Mevcut kredi: ₺' . number_format($user['kredi'], 2);
            } else {

                $pdo->beginTransaction();
                try {

                    $stmt = $pdo->prepare("INSERT INTO tickets (user_id, trip_id, koltuk_no, fiyat, kupon_kodu) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$user['id'], $tripId, $seatNo, $finalPrice, $couponCode]);
                    

                    updateUserCredit($user['id'], -$finalPrice);
                    

                    if ($couponCode && $coupon) {
                        useCoupon($couponCode);
                    }
                    
                    $pdo->commit();
                    $success = 'Bilet başarıyla satın alındı! Biletlerim sayfasından PDF indirebilirsiniz.';
                    

                    header('Location: /pages/biletlerim.php');
                    exit;
                } catch (Exception $e) {
                    $pdo->rollback();
                    $error = 'Bilet satın alma sırasında bir hata oluştu.';
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sefer Detay - Bilet Satış Platformu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/css/style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="/">
                BILET SATIŞ PLATFORMU
            </a>
            <div class="navbar-nav ms-auto">
                <?php if (isLoggedIn()): ?>
                    <?php $user = getCurrentUser(); ?>
                    <span class="navbar-text me-3">
                        <?= htmlspecialchars($user['ad']) ?> (₺<?= number_format($user['kredi'], 2) ?>)
                    </span>
                    <a class="nav-link" href="/pages/logout.php">Çıkış</a>
                <?php else: ?>
                    <a class="nav-link" href="/pages/login.php">Giriş</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5>Sefer Detayları</h5>
                    </div>
                    <div class="card-body">
                        <h4><?= htmlspecialchars($trip['firma_ad']) ?></h4>
                        <p class="lead">
                            <strong><?= htmlspecialchars($trip['kalkis']) ?></strong> → 
                            <strong><?= htmlspecialchars($trip['varis']) ?></strong>
                        </p>
                        <p>
                            <strong>TARİH:</strong> <?= date('d.m.Y', strtotime($trip['tarih'])) ?><br>
                            <strong>SAAT:</strong> <?= date('H:i', strtotime($trip['saat'])) ?>
                        </p>
                        <p>
                            <strong>FİYAT:</strong> <span class="badge bg-success fs-6">₺<?= number_format($trip['fiyat'], 2) ?></span>
                        </p>
                        <p>
                            <strong>KOLTUK:</strong> Toplam <?= $trip['koltuk_sayisi'] ?> | 
                            Müsait <?= count($availableSeats) ?> | 
                            Dolu <?= count($occupiedSeats) ?>
                        </p>
                    </div>
                </div>

                <?php if (isLoggedIn() && hasRole('user')): ?>
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5>Koltuk Seçimi</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($error): ?>
                                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                            <?php endif; ?>
                            
                            <?php if ($success): ?>
                                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                            <?php endif; ?>
                            
                            <div class="seat-legend">
                                <div class="legend-item">
                                    <div class="legend-seat" style="background-color: #28a745;"></div>
                                    <span>Müsait</span>
                                </div>
                                <div class="legend-item">
                                    <div class="legend-seat" style="background-color: #dc3545;"></div>
                                    <span>Dolu</span>
                                </div>
                                <div class="legend-item">
                                    <div class="legend-seat" style="background-color: #007bff;"></div>
                                    <span>Seçili</span>
                                </div>
                            </div>
                            
                            <form method="POST" id="ticketForm">
                                <div class="seat-map" id="seatMap">
                                    <?php for ($i = 1; $i <= $trip['koltuk_sayisi']; $i++): ?>
                                        <?php if (in_array($i, $availableSeats)): ?>
                                            <div class="seat available" data-seat="<?= $i ?>" onclick="selectSeat(<?= $i ?>)">
                                                <?= $i ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="seat occupied">
                                                <?= $i ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                </div>
                                
                                <input type="hidden" name="seat_no" id="selectedSeat" value="">
                                
                                <div class="mt-4">
                                    <div class="form-floating mb-3">
                                        <input type="text" class="form-control" id="coupon_code" name="coupon_code" placeholder="Kupon Kodu">
                                        <label for="coupon_code">Kupon Kodu (İsteğe Bağlı)</label>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-success w-100" id="buyButton" disabled>
                                        BİLET SATIN AL (₺<?= number_format($trip['fiyat'], 2) ?>)
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php elseif (!isLoggedIn()): ?>
                    <div class="card mt-4">
                        <div class="card-body text-center">
                            <h5>Bilet Satın Almak İçin Giriş Yapın</h5>
                            <a href="/pages/login.php" class="btn btn-primary">GİRİŞ YAP</a>
                            <a href="/pages/register.php" class="btn btn-success">KAYIT OL</a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h6>Kupon Kodları</h6>
                    </div>
                    <div class="card-body">
                        <?php
                        $stmt = $pdo->prepare("SELECT * FROM coupons WHERE son_tarih >= DATE('now') AND kullanilan < limit_adet ORDER BY oran DESC");
                        $stmt->execute();
                        $coupons = $stmt->fetchAll();
                        ?>
                        
                        <?php if ($coupons): ?>
                            <?php foreach ($coupons as $coupon): ?>
                                <div class="alert alert-info p-2 mb-2">
                                    <strong><?= htmlspecialchars($coupon['kod']) ?></strong><br>
                                    <small>%<?= $coupon['oran'] ?> indirim</small>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted">Aktif kupon bulunmuyor.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let selectedSeatNo = null;
        
        function selectSeat(seatNo) {

            document.querySelectorAll('.seat.selected').forEach(seat => {
                seat.classList.remove('selected');
                seat.classList.add('available');
            });
            

            const seatElement = document.querySelector(`[data-seat="${seatNo}"]`);
            seatElement.classList.remove('available');
            seatElement.classList.add('selected');
            
            selectedSeatNo = seatNo;
            document.getElementById('selectedSeat').value = seatNo;
            document.getElementById('buyButton').disabled = false;
        }
    </script>
</body>
</html>
