<?php
require_once '../includes/auth.php';

$tripId = $_GET['id'] ?? 0;

if (!$tripId) {
    header('Location: /');
    exit;
}


if (strpos($tripId, 'dynamic_') === 0) {

    $from = $_GET['from'] ?? '';
    $to = $_GET['to'] ?? '';
    $date = $_GET['date'] ?? '';
    $time = $_GET['time'] ?? '';
    $price = $_GET['price'] ?? 0;
    $company = $_GET['company'] ?? '';
    
    if (!$from || !$to || !$date || !$time || !$price || !$company) {
        header('Location: /');
        exit;
    }
    

    $trip = [
        'id' => $tripId,
        'departure_city' => $from,
        'destination_city' => $to,
        'departure_time' => $date . ' ' . $time . ':00',
        'arrival_time' => date('Y-m-d H:i:s', strtotime($date . ' ' . $time . ':00 +6 hours')),
        'price' => $price,
        'capacity' => 40,
        'firma_ad' => $company,
        'company_id' => 'dynamic'
    ];
} else {

    $stmt = $pdo->prepare("
        SELECT t.*, bc.name as firma_ad 
        FROM Trips t 
        JOIN Bus_Company bc ON t.company_id = bc.id 
        WHERE t.id = ?
    ");
    $stmt->execute([$tripId]);
    $trip = $stmt->fetch();

    if (!$trip) {
        header('Location: /');
        exit;
    }
}


if (strpos($tripId, 'dynamic_') === 0) {

    $availableSeats = range(1, $trip['capacity']);
    $occupiedSeats = [];
} else {
    $availableSeats = getAvailableSeats($tripId);
    $occupiedSeats = [];
    for ($i = 1; $i <= $trip['capacity']; $i++) {
        if (!in_array($i, $availableSeats)) {
            $occupiedSeats[] = $i;
        }
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
        $finalPrice = $trip['price'];
        $discount = 0;
        

        if ($couponCode) {
            $companyId = (strpos($tripId, 'dynamic_') === 0) ? null : $trip['company_id'];
            $coupon = validateCoupon($couponCode, $companyId);
            if ($coupon) {
                $discount = $finalPrice * $coupon['discount'];
                $finalPrice -= $discount;
            } else {
                $error = 'Geçersiz, süresi dolmuş veya bu firma için geçerli olmayan kupon kodu.';
            }
        }
        
        if (!$error) {

            if ($user['balance'] < $finalPrice) {
                $error = 'Yetersiz kredi. Mevcut kredi: ₺' . number_format($user['balance'], 2);
            } else {

                $pdo->beginTransaction();
                try {

                    if (strpos($tripId, 'dynamic_') === 0) {

                        $realTripId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                            mt_rand(0, 0xffff),
                            mt_rand(0, 0x0fff) | 0x4000,
                            mt_rand(0, 0x3fff) | 0x8000,
                            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                        );
                        

                        $stmt = $pdo->query("SELECT id FROM Bus_Company LIMIT 1");
                        $companyId = $stmt->fetchColumn();
                        

                        $stmt = $pdo->prepare("INSERT INTO Trips (id, company_id, departure_city, destination_city, departure_time, arrival_time, price, capacity) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$realTripId, $companyId, $trip['departure_city'], $trip['destination_city'], $trip['departure_time'], $trip['arrival_time'], $trip['price'], $trip['capacity']]);
                        
                        $tripId = $realTripId; // Artık gerçek ID'yi kullan
                    }
                    

                    $ticketId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                        mt_rand(0, 0xffff),
                        mt_rand(0, 0x0fff) | 0x4000,
                        mt_rand(0, 0x3fff) | 0x8000,
                        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                    );
                    
                    $seatId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                        mt_rand(0, 0xffff),
                        mt_rand(0, 0x0fff) | 0x4000,
                        mt_rand(0, 0x3fff) | 0x8000,
                        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                    );
                    

                    $stmt = $pdo->prepare("INSERT INTO Tickets (id, trip_id, user_id, total_price) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$ticketId, $tripId, $user['id'], $finalPrice]);
                    

                    $stmt = $pdo->prepare("INSERT INTO Booked_Seats (id, ticket_id, seat_number) VALUES (?, ?, ?)");
                    $stmt->execute([$seatId, $ticketId, $seatNo]);
                    

                    updateUserCredit($user['id'], -$finalPrice);
                    

                    if ($couponCode && $coupon) {
                        useCoupon($couponCode);
                    }
                    
                    $pdo->commit();
                    $success = 'Bilet başarıyla satın alındı! Biletlerim sayfasından PDF indirebilirsiniz.';
                    

                    header('Location: biletlerim.php');
                    exit;
                } catch (Exception $e) {
                    $pdo->rollback();
                    $error = 'Bilet satın alma sırasında bir hata oluştu: ' . $e->getMessage();
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
    <link href="css/style.css" rel="stylesheet">
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
                    <div class="navbar-user-info me-3">
                        <span class="user-welcome">
                            <?= htmlspecialchars($user['full_name']) ?>
                        </span>
                        <span class="user-balance">
                            Kredi: ₺<?= number_format($user['balance'], 2) ?>
                        </span>
                    </div>
                    <div class="navbar-buttons">
                        <a class="nav-btn" href="/">Ana Sayfa</a>
                        <a class="nav-btn nav-btn-logout" href="logout.php">Çıkış</a>
                    </div>
                <?php else: ?>
                    <div class="navbar-buttons">
                        <a class="nav-btn" href="login.php">Giriş</a>
                        <a class="nav-btn nav-btn-register" href="register.php">Kayıt</a>
                    </div>
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
                            <strong><?= htmlspecialchars($trip['departure_city']) ?></strong> → 
                            <strong><?= htmlspecialchars($trip['destination_city']) ?></strong>
                        </p>
                        <p>
                            <strong>TARİH:</strong> <?= date('d.m.Y', strtotime($trip['departure_time'])) ?><br>
                            <strong>SAAT:</strong> <?= date('H:i', strtotime($trip['departure_time'])) ?>
                        </p>
                        <p>
                            <strong>FİYAT:</strong> <span class="badge bg-success fs-6">₺<?= number_format($trip['price'], 2) ?></span>
                        </p>
                        <p>
                            <strong>KOLTUK:</strong> Toplam <?= $trip['capacity'] ?> | 
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
                                    <div class="legend-seat available"></div>
                                    <span>Müsait</span>
                                </div>
                                <div class="legend-item">
                                    <div class="legend-seat occupied"></div>
                                    <span>Dolu</span>
                                </div>
                                <div class="legend-item">
                                    <div class="legend-seat selected"></div>
                                    <span>Seçili</span>
                                </div>
                            </div>
                            
                            <form method="POST" id="ticketForm">
                                <div class="bus-container">
                                    <div class="seat-map" id="seatMap">
                                        <?php 
                                        $seatLayout = [
                                            [1, 2, 'aisle', 3, 4],
                                            [5, 6, 'aisle', 7, 8],
                                            [9, 10, 'aisle', 11, 12],
                                            [13, 14, 'aisle', 15, 16],
                                            [17, 18, 'aisle', 19, 20],
                                            [21, 22, 'aisle', 23, 24],
                                            [25, 26, 'aisle', 27, 28],
                                            [29, 30, 'aisle', 31, 32],
                                            [33, 34, 'aisle', 35, 36],
                                            [37, 38, 'aisle', 39, 40]
                                        ];
                                        
                                        foreach ($seatLayout as $row): ?>
                                            <div class="seat-row">
                                                <?php foreach ($row as $position): ?>
                                                    <?php if ($position === 'aisle'): ?>
                                                        <div class="aisle">
                                                            <div class="aisle-line"></div>
                                                        </div>
                                                    <?php elseif ($position <= $trip['capacity']): ?>
                                                        <?php if (in_array($position, $availableSeats)): ?>
                                                            <div class="seat available" data-seat="<?= $position ?>" onclick="selectSeat(<?= $position ?>)">
                                                                <?= $position ?>
                                                            </div>
                                                        <?php else: ?>
                                                            <div class="seat occupied">
                                                                <?= $position ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <div class="seat" style="visibility: hidden;"></div>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                
                                <input type="hidden" name="seat_no" id="selectedSeat" value="">
                                
                                <div class="mt-4">
                                    <div class="form-floating mb-3">
                                        <input type="text" class="form-control" id="coupon_code" name="coupon_code" placeholder="Kupon Kodu">
                                        <label for="coupon_code">Kupon Kodu (İsteğe Bağlı)</label>
                                    </div>
                                    
                                    <button type="button" class="btn btn-success w-100" id="buyButton" disabled onclick="showConfirmModal()">
                                        BİLET SATIN AL (₺<?= number_format($trip['price'], 2) ?>)
                                    </button>
                                    
                                    
                                    <button type="submit" id="hiddenSubmit" style="display: none;"></button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    
                    <div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content" style="background-color: #1a1a1a; border: 2px solid #ffffff; color: #ffffff;">
                                <div class="modal-header" style="border-bottom: 1px solid #333333;">
                                    <h5 class="modal-title" id="confirmModalLabel" style="color: #ffffff; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;">
                                        BİLET SATIN ALMA ONAYI
                                    </h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="confirmation-details">
                                        <div class="row mb-3">
                                            <div class="col-6"><strong>GÜZERGAH:</strong></div>
                                            <div class="col-6"><?= htmlspecialchars($trip['departure_city']) ?> → <?= htmlspecialchars($trip['destination_city']) ?></div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-6"><strong>TARİH:</strong></div>
                                            <div class="col-6"><?= date('d.m.Y', strtotime($trip['departure_time'])) ?></div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-6"><strong>SAAT:</strong></div>
                                            <div class="col-6"><?= date('H:i', strtotime($trip['departure_time'])) ?></div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-6"><strong>FİRMA:</strong></div>
                                            <div class="col-6"><?= htmlspecialchars($trip['firma_ad']) ?></div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-6"><strong>KOLTUK NO:</strong></div>
                                            <div class="col-6" id="modalSeatNo">-</div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-6"><strong>FİYAT:</strong></div>
                                            <div class="col-6" id="modalPrice">₺<?= number_format($trip['price'], 2) ?></div>
                                        </div>
                                        <div class="row mb-3" id="modalCouponRow" style="display: none;">
                                            <div class="col-6"><strong>KUPON:</strong></div>
                                            <div class="col-6" id="modalCoupon">-</div>
                                        </div>
                                        <hr style="border-color: #333333;">
                                        <div class="row">
                                            <div class="col-6"><strong style="font-size: 1.2em;">TOPLAM:</strong></div>
                                            <div class="col-6"><strong style="font-size: 1.2em; color: #28a745;" id="modalTotal">₺<?= number_format($trip['price'], 2) ?></strong></div>
                                        </div>
                                    </div>
                                    
                                    <div class="alert alert-warning mt-3" style="background-color: #333333; border-color: #ffc107; color: #ffffff;">
                                        <strong>⚠️ DİKKAT:</strong> Bilet satın aldıktan sonra iptal işlemi için kalkışa en az 1 saat kala işlem yapmanız gerekmektedir.
                                    </div>
                                </div>
                                <div class="modal-footer" style="border-top: 1px solid #333333;">
                                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" style="color: #ffffff; border-color: #ffffff;">
                                        İPTAL
                                    </button>
                                    <button type="button" class="btn btn-success" onclick="confirmPurchase()" style="background-color: #28a745; border-color: #28a745; font-weight: 700;">
                                        ONAYLIYORUM, BİLETİ SATIN AL
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php elseif (!isLoggedIn()): ?>
                    <div class="card mt-4">
                        <div class="card-body text-center">
                            <h5>Bilet Satın Almak İçin Giriş Yapın</h5>
                            <a href="login.php" class="btn btn-primary">GİRİŞ YAP</a>
                            <a href="register.php" class="btn btn-success">KAYIT OL</a>
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

                        $companyId = (strpos($tripId, 'dynamic_') === 0) ? null : $trip['company_id'];
                        
                        if ($companyId) {
                            $stmt = $pdo->prepare("SELECT * FROM Coupons WHERE (company_id IS NULL OR company_id = ?) ORDER BY 
                                CASE 
                                    WHEN expire_date >= datetime('now') AND usage_limit > 0 THEN 0 
                                    ELSE 1 
                                END, 
                                discount DESC");
                            $stmt->execute([$companyId]);
                        } else {

                            $stmt = $pdo->prepare("SELECT * FROM Coupons WHERE company_id IS NULL ORDER BY 
                                CASE 
                                    WHEN expire_date >= datetime('now') AND usage_limit > 0 THEN 0 
                                    ELSE 1 
                                END, 
                                discount DESC");
                            $stmt->execute();
                        }
                        $coupons = $stmt->fetchAll();
                        ?>
                        
                        <?php if ($coupons): ?>
                            <?php foreach ($coupons as $coupon): ?>
                                <?php
                                $isExpired = $coupon['expire_date'] < date('Y-m-d H:i:s');
                                $isLimitReached = $coupon['usage_limit'] <= 0;
                                $isActive = !$isExpired && !$isLimitReached;
                                ?>
                                <div class="alert <?= $isActive ? 'alert-info' : 'alert-secondary' ?> p-2 mb-2 position-relative">
                                    <strong><?= htmlspecialchars($coupon['code']) ?></strong>
                                    <?php if (!$coupon['company_id']): ?>
                                        <span class="badge bg-success ms-1" style="font-size: 0.6rem;">GLOBAL</span>
                                    <?php endif; ?>
                                    <br>
                                    <small>%<?= ($coupon['discount'] * 100) ?> indirim</small>
                                    
                                    <?php if ($isExpired): ?>
                                        <div class="position-absolute top-0 end-0 m-1">
                                            <span class="badge bg-danger" style="font-size: 0.7rem;">SÜRESİ DOLMUŞ</span>
                                        </div>
                                    <?php elseif ($isLimitReached): ?>
                                        <div class="position-absolute top-0 end-0 m-1">
                                            <span class="badge bg-warning text-dark" style="font-size: 0.7rem;">LİMİT DOLMUŞ</span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!$isActive): ?>
                                        <div style="opacity: 0.6;">
                                            <small class="text-muted d-block">
                                                <?php if ($isExpired): ?>
                                                    Son kullanma: <?= date('d.m.Y', strtotime($coupon['expire_date'])) ?>
                                                <?php elseif ($isLimitReached): ?>
                                                    Kullanım limiti dolmuş
                                                <?php endif; ?>
                                            </small>
                                        </div>
                                    <?php else: ?>
                                        <small class="text-muted d-block">
                                            Son kullanma: <?= date('d.m.Y', strtotime($coupon['expire_date'])) ?>
                                        </small>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted">Kupon bulunmuyor.</p>
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
            if (seatElement && seatElement.classList.contains('available')) {
                seatElement.classList.remove('available');
                seatElement.classList.add('selected');
                
                selectedSeatNo = seatNo;
                document.getElementById('selectedSeat').value = seatNo;
                document.getElementById('buyButton').disabled = false;
                

                const button = document.getElementById('buyButton');
                button.innerHTML = `${seatNo}. KOLTUĞU SATIN AL (₺<?= number_format($trip['price'], 2) ?>)`;
                

                seatElement.style.transform = 'scale(1.15)';
                setTimeout(() => {
                    if (seatElement.classList.contains('selected')) {
                        seatElement.style.transform = 'scale(1.1)';
                    }
                }, 200);
            }
        }
        
        function showConfirmModal() {
            if (!selectedSeatNo) {
                alert('Lütfen önce bir koltuk seçin.');
                return;
            }
            

            document.getElementById('modalSeatNo').textContent = selectedSeatNo;
            

            const couponCode = document.getElementById('coupon_code').value;
            if (couponCode) {
                document.getElementById('modalCouponRow').style.display = 'flex';
                document.getElementById('modalCoupon').textContent = couponCode;
            } else {
                document.getElementById('modalCouponRow').style.display = 'none';
            }
            

            const modal = new bootstrap.Modal(document.getElementById('confirmModal'));
            modal.show();
        }
        
        function confirmPurchase() {

            document.getElementById('hiddenSubmit').click();
        }
        

        document.addEventListener('DOMContentLoaded', function() {
            const seats = document.querySelectorAll('.seat');
            seats.forEach((seat, index) => {
                setTimeout(() => {
                    seat.style.opacity = '0';
                    seat.style.transform = 'translateY(20px)';
                    seat.style.transition = 'all 0.3s ease';
                    
                    setTimeout(() => {
                        seat.style.opacity = '1';
                        seat.style.transform = 'translateY(0)';
                    }, 50);
                }, index * 30);
            });
        });
        

        document.addEventListener('DOMContentLoaded', function() {
            const availableSeats = document.querySelectorAll('.seat.available');
            
            availableSeats.forEach(seat => {
                seat.addEventListener('mouseenter', function() {
                    if (!this.classList.contains('selected')) {
                        this.style.transform = 'scale(1.05)';
                        this.style.boxShadow = '0 0 15px rgba(40, 167, 69, 0.5)';
                    }
                });
                
                seat.addEventListener('mouseleave', function() {
                    if (!this.classList.contains('selected')) {
                        this.style.transform = 'scale(1)';
                        this.style.boxShadow = 'none';
                    }
                });
            });
        });
    </script>
</body>
</html>
