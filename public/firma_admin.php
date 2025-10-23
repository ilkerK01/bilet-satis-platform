<?php
require_once '../includes/auth.php';

requireRole('company');

$user = getCurrentUser();
$message = '';
$activeTab = $_GET['tab'] ?? 'trips';

$stmt = $pdo->prepare("SELECT * FROM Bus_Company WHERE id = ?");
$stmt->execute([$user['company_id']]);
$firm = $stmt->fetch();
if ($_POST && isset($_POST['add_trip'])) {
    $kalkis = $_POST['kalkis'] ?? '';
    $varis = $_POST['varis'] ?? '';
    $tarih = $_POST['tarih'] ?? '';
    $saat = $_POST['saat'] ?? '';
    $fiyat = $_POST['fiyat'] ?? '';
    
    if ($kalkis && $varis && $tarih && $saat && $fiyat) {
        $tripId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
        
        $departureTime = $tarih . ' ' . $saat . ':00';
        $arrivalTime = date('Y-m-d H:i:s', strtotime($departureTime . ' +6 hours'));
        
        $stmt = $pdo->prepare("INSERT INTO Trips (id, company_id, departure_city, destination_city, departure_time, arrival_time, price, capacity) VALUES (?, ?, ?, ?, ?, ?, ?, 40)");
        if ($stmt->execute([$tripId, $user['company_id'], $kalkis, $varis, $departureTime, $arrivalTime, $fiyat])) {
            $message = 'Sefer başarıyla eklendi.';
        }
    }
}
if ($_POST && isset($_POST['edit_trip'])) {
    $tripId = $_POST['trip_id'] ?? '';
    $kalkis = $_POST['edit_kalkis'] ?? '';
    $varis = $_POST['edit_varis'] ?? '';
    $tarih = $_POST['edit_tarih'] ?? '';
    $saat = $_POST['edit_saat'] ?? '';
    $fiyat = $_POST['edit_fiyat'] ?? '';
    
    if ($tripId && $kalkis && $varis && $tarih && $saat && $fiyat) {

        $stmt = $pdo->prepare("SELECT id FROM Trips WHERE id = ? AND company_id = ?");
        $stmt->execute([$tripId, $user['company_id']]);
        
        if ($stmt->fetch()) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM Tickets WHERE trip_id = ? AND status = 'active'");
            $stmt->execute([$tripId]);
            $activeTickets = $stmt->fetchColumn();
            
            if ($activeTickets > 0) {
                $message = 'Bu sefer düzenlenemez! Aktif biletler bulunmaktadır. (' . $activeTickets . ' adet)';
            } else {
                $departureTime = $tarih . ' ' . $saat . ':00';
                $arrivalTime = date('Y-m-d H:i:s', strtotime($departureTime . ' +6 hours'));
                
                $stmt = $pdo->prepare("UPDATE Trips SET departure_city = ?, destination_city = ?, departure_time = ?, arrival_time = ?, price = ? WHERE id = ? AND company_id = ?");
                if ($stmt->execute([$kalkis, $varis, $departureTime, $arrivalTime, $fiyat, $tripId, $user['company_id']])) {
                    $message = 'Sefer başarıyla güncellendi.';
                } else {
                    $message = 'Sefer güncellenirken bir hata oluştu.';
                }
            }
        } else {
            $message = 'Geçersiz sefer ID veya yetkiniz yok.';
        }
    }
}
if ($_POST && isset($_POST['add_coupon'])) {
    $code = $_POST['coupon_code'] ?? '';
    $discount = $_POST['coupon_discount'] ?? '';
    $limit = $_POST['coupon_limit'] ?? '';
    $endDate = $_POST['coupon_end_date'] ?? '';
    
    if ($code && $discount && $limit && $endDate) {
        $couponId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
        
        $discountDecimal = $discount / 100;
        $stmt = $pdo->prepare("INSERT INTO Coupons (id, code, discount, company_id, usage_limit, expire_date) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$couponId, $code, $discountDecimal, $user['company_id'], $limit, $endDate . ' 23:59:59'])) {
            $message = 'Kupon başarıyla eklendi.';
        }
    }
}


if ($_POST && isset($_POST['delete_coupon'])) {
    $couponId = $_POST['coupon_id'] ?? '';
    
    if ($couponId) {

        $stmt = $pdo->prepare("SELECT id FROM Coupons WHERE id = ? AND company_id = ?");
        $stmt->execute([$couponId, $user['company_id']]);
        
        if ($stmt->fetch()) {

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM User_Coupons WHERE coupon_id = ?");
            $stmt->execute([$couponId]);
            $usageCount = $stmt->fetchColumn();
            
            if ($usageCount > 0) {
                $message = 'Bu kupon silinemez! ' . $usageCount . ' kez kullanılmış.';
            } else {
                $stmt = $pdo->prepare("DELETE FROM Coupons WHERE id = ? AND company_id = ?");
                if ($stmt->execute([$couponId, $user['company_id']])) {
                    $message = 'Kupon başarıyla silindi.';
                } else {
                    $message = 'Kupon silinirken bir hata oluştu.';
                }
            }
        } else {
            $message = 'Geçersiz kupon ID veya yetkiniz yok.';
        }
    }
}


if ($_POST && isset($_POST['delete_trip'])) {
    $tripId = $_POST['trip_id'] ?? '';
    
    if ($tripId) {

        $stmt = $pdo->prepare("SELECT id FROM Trips WHERE id = ? AND company_id = ?");
        $stmt->execute([$tripId, $user['company_id']]);
        
        if ($stmt->fetch()) {

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM Tickets WHERE trip_id = ? AND status = 'active'");
            $stmt->execute([$tripId]);
            $activeTickets = $stmt->fetchColumn();
            
            if ($activeTickets > 0) {
                $message = 'Bu sefer silinemez! Aktif biletler bulunmaktadır. (' . $activeTickets . ' adet)';
            } else {
                $pdo->beginTransaction();
                try {

                    $stmt = $pdo->prepare("DELETE FROM Booked_Seats WHERE ticket_id IN (SELECT id FROM Tickets WHERE trip_id = ?)");
                    $stmt->execute([$tripId]);
                    
                    $stmt = $pdo->prepare("DELETE FROM Tickets WHERE trip_id = ?");
                    $stmt->execute([$tripId]);
                    

                    $stmt = $pdo->prepare("DELETE FROM Trips WHERE id = ? AND company_id = ?");
                    $stmt->execute([$tripId, $user['company_id']]);
                    
                    $pdo->commit();
                    $message = 'Sefer başarıyla silindi.';
                } catch (Exception $e) {
                    $pdo->rollback();
                    $message = 'Sefer silinirken bir hata oluştu.';
                }
            }
        } else {
            $message = 'Geçersiz sefer ID veya yetkiniz yok.';
        }
    }
}


$stmt = $pdo->prepare("SELECT * FROM Trips WHERE company_id = ? ORDER BY departure_time DESC");
$stmt->execute([$user['company_id']]);
$trips = $stmt->fetchAll();

$cities = [
    'Adana', 'Adıyaman', 'Afyonkarahisar', 'Ağrı', 'Aksaray', 'Amasya', 'Ankara', 'Antalya', 'Ardahan', 'Artvin',
    'Aydın', 'Balıkesir', 'Bartın', 'Batman', 'Bayburt', 'Bilecik', 'Bingöl', 'Bitlis', 'Bolu', 'Burdur',
    'Bursa', 'Çanakkale', 'Çankırı', 'Çorum', 'Denizli', 'Diyarbakır', 'Düzce', 'Edirne', 'Elazığ', 'Erzincan',
    'Erzurum', 'Eskişehir', 'Gaziantep', 'Giresun', 'Gümüşhane', 'Hakkari', 'Hatay', 'Iğdır', 'Isparta', 'İstanbul',
    'İzmir', 'Kahramanmaraş', 'Karabük', 'Karaman', 'Kars', 'Kastamonu', 'Kayseri', 'Kırıkkale', 'Kırklareli', 'Kırşehir',
    'Kilis', 'Kocaeli', 'Konya', 'Kütahya', 'Malatya', 'Manisa', 'Mardin', 'Mersin', 'Muğla', 'Muş',
    'Nevşehir', 'Niğde', 'Ordu', 'Osmaniye', 'Rize', 'Sakarya', 'Samsun', 'Siirt', 'Sinop', 'Sivas',
    'Şanlıurfa', 'Şırnak', 'Tekirdağ', 'Tokat', 'Trabzon', 'Tunceli', 'Uşak', 'Van', 'Yalova', 'Yozgat', 'Zonguldak'
];
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Firma Admin Panel - <?= htmlspecialchars($firm['name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark" style="background-color: #28a745 !important;">
        <div class="container">
            <a class="navbar-brand" href="/" style="color: #ffffff !important;">
                <?= strtoupper(htmlspecialchars($firm['name'])) ?> - ADMIN PANEL
            </a>
            <div class="navbar-nav ms-auto">
                <div class="navbar-user-info me-3" style="background: rgba(255, 255, 255, 0.2); border-color: rgba(255, 255, 255, 0.3);">
                    <span class="user-welcome" style="color: #ffffff;">
                        <?= htmlspecialchars($user['full_name']) ?>
                    </span>
                </div>
                <div class="navbar-buttons">
                    <a class="nav-btn" href="/" style="background: #ffffff; color: #28a745 !important; border-color: #ffffff;">Ana Sayfa</a>
                    <a class="nav-btn nav-btn-logout" href="logout.php">Çıkış</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-md-3">
                <div class="card">
                    <div class="card-header">
                        <h6>Menü</h6>
                    </div>
                    <div class="list-group list-group-flush">
                        <a href="?tab=trips" class="list-group-item <?= $activeTab === 'trips' ? 'active' : '' ?>">
                            SEFERLER
                        </a>
                        <a href="?tab=add_trip" class="list-group-item <?= $activeTab === 'add_trip' ? 'active' : '' ?>">
                            SEFER EKLE
                        </a>
                        <a href="?tab=coupons" class="list-group-item <?= $activeTab === 'coupons' ? 'active' : '' ?>">
                            KUPONLAR
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-9">
                <?php if ($message): ?>
                    <div class="alert <?= strpos($message, 'başarıyla') !== false ? 'alert-success' : 'alert-danger' ?> alert-dismissible fade show">
                        <?= htmlspecialchars($message) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($activeTab === 'add_trip'): ?>
                    <div class="card">
                        <div class="card-header">
                            <h5>Yeni Sefer Ekle</h5>
                        </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Kalkış</label>
                                <select name="kalkis" class="form-select" required>
                                    <option value="">Şehir Seçin</option>
                                    <?php foreach ($cities as $city): ?>
                                        <option value="<?= $city ?>"><?= $city ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Varış</label>
                                <select name="varis" class="form-select" required>
                                    <option value="">Şehir Seçin</option>
                                    <?php foreach ($cities as $city): ?>
                                        <option value="<?= $city ?>"><?= $city ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Tarih</label>
                                <input type="date" name="tarih" class="form-control" min="<?= date('Y-m-d') ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Saat</label>
                                <input type="time" name="saat" class="form-control" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Fiyat (₺)</label>
                                <input type="number" name="fiyat" class="form-control" step="0.01" min="1" required>
                            </div>
                            
                            <button type="submit" name="add_trip" class="btn btn-success">Sefer Ekle</button>
                        </form>
                    </div>
                </div>
                
                <?php elseif ($activeTab === 'trips'): ?>
                    <div class="card">
                        <div class="card-header">
                            <h5>Seferlerim (<?= count($trips) ?>)</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($trips)): ?>
                                <div class="text-center py-5">
                                    <h6>Henüz sefer eklenmemiş.</h6>
                                    <a href="?tab=add_trip" class="btn btn-success">İlk Seferi Ekle</a>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Güzergah</th>
                                                <th>Tarih/Saat</th>
                                                <th>Fiyat</th>
                                                <th>Koltuk</th>
                                                <th>Satılan</th>
                                                <th>İşlemler</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($trips as $trip): ?>
                                                <?php
                                                $soldTickets = $pdo->prepare("SELECT COUNT(*) FROM Tickets WHERE trip_id = ? AND status = 'active'");
                                                $soldTickets->execute([$trip['id']]);
                                                $sold = $soldTickets->fetchColumn();
                                                ?>
                                                <tr>
                                                    <td>
                                                        <strong><?= htmlspecialchars($trip['departure_city']) ?></strong> → 
                                                        <strong><?= htmlspecialchars($trip['destination_city']) ?></strong>
                                                    </td>
                                                    <td>
                                                        <?= date('d.m.Y', strtotime($trip['departure_time'])) ?><br>
                                                        <small><?= date('H:i', strtotime($trip['departure_time'])) ?></small>
                                                    </td>
                                                    <td>₺<?= number_format($trip['price'], 2) ?></td>
                                                    <td><?= $trip['capacity'] ?></td>
                                                    <td>
                                                        <span class="badge <?= $sold > 0 ? 'bg-success' : 'bg-secondary' ?>">
                                                            <?= $sold ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php if ($sold > 0): ?>
                                                            <small class="text-muted">Aktif bilet var</small>
                                                        <?php else: ?>
                                                            <button type="button" class="btn btn-sm btn-outline-primary me-1" 
                                                                    onclick="editTrip('<?= $trip['id'] ?>', '<?= htmlspecialchars($trip['departure_city']) ?>', '<?= htmlspecialchars($trip['destination_city']) ?>', '<?= date('Y-m-d', strtotime($trip['departure_time'])) ?>', '<?= date('H:i', strtotime($trip['departure_time'])) ?>', '<?= $trip['price'] ?>')" 
                                                                    title="Seferi Düzenle">
                                                                ✏️ DÜZENLE
                                                            </button>
                                                            <form method="POST" style="display: inline;" onsubmit="return confirmDelete('<?= htmlspecialchars($trip['departure_city']) ?> → <?= htmlspecialchars($trip['destination_city']) ?>', '<?= date('d.m.Y H:i', strtotime($trip['departure_time'])) ?>')">
                                                                <input type="hidden" name="trip_id" value="<?= $trip['id'] ?>">
                                                                <button type="submit" name="delete_trip" class="btn btn-sm btn-outline-danger" title="Seferi Sil">
                                                                    🗑️ SİL
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                
                <?php elseif ($activeTab === 'coupons'): ?>
                    <div class="card">
                        <div class="card-header">
                            <h5>Kupon Yönetimi</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" class="mb-4">
                                <div class="row">
                                    <div class="col-md-3">
                                        <input type="text" name="coupon_code" class="form-control" placeholder="Kupon Kodu" required>
                                    </div>
                                    <div class="col-md-2">
                                        <input type="number" name="coupon_discount" class="form-control" placeholder="İndirim %" min="1" max="100" required>
                                    </div>
                                    <div class="col-md-2">
                                        <input type="number" name="coupon_limit" class="form-control" placeholder="Limit" min="1" required>
                                    </div>
                                    <div class="col-md-3">
                                        <input type="date" name="coupon_end_date" class="form-control" min="<?= date('Y-m-d') ?>" required>
                                    </div>
                                    <div class="col-md-2">
                                        <button type="submit" name="add_coupon" class="btn btn-success">Ekle</button>
                                    </div>
                                </div>
                            </form>
                            
                            <?php
                            $stmt = $pdo->prepare("SELECT * FROM Coupons WHERE company_id = ? ORDER BY created_at DESC");
                            $stmt->execute([$user['company_id']]);
                            $coupons = $stmt->fetchAll();
                            ?>
                            
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Kod</th>
                                            <th>İndirim</th>
                                            <th>Limit</th>
                                            <th>Kullanılan</th>
                                            <th>Son Tarih</th>
                                            <th>Durum</th>
                                            <th>İşlemler</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($coupons as $coupon): ?>
                                            <tr>
                                                <td><strong><?= htmlspecialchars($coupon['code']) ?></strong></td>
                                                <td>%<?= ($coupon['discount'] * 100) ?></td>
                                                <td><?= $coupon['usage_limit'] ?></td>
                                                <td>
                                                    <?php
                                                    $usedCount = $pdo->prepare("SELECT COUNT(*) FROM User_Coupons WHERE coupon_id = ?");
                                                    $usedCount->execute([$coupon['id']]);
                                                    echo $usedCount->fetchColumn();
                                                    ?>
                                                </td>
                                                <td><?= date('d.m.Y', strtotime($coupon['expire_date'])) ?></td>
                                                <td>
                                                    <?php if ($coupon['expire_date'] < date('Y-m-d H:i:s')): ?>
                                                        <span class="badge bg-danger">Süresi Dolmuş</span>
                                                    <?php elseif ($coupon['usage_limit'] <= 0): ?>
                                                        <span class="badge bg-warning">Limit Dolmuş</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-success">Aktif</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php
                                                    $usedCount = $pdo->prepare("SELECT COUNT(*) FROM User_Coupons WHERE coupon_id = ?");
                                                    $usedCount->execute([$coupon['id']]);
                                                    $used = $usedCount->fetchColumn();
                                                    ?>
                                                    <?php if ($used > 0): ?>
                                                        <small class="text-muted"><?= $used ?>x kullanılmış</small>
                                                    <?php else: ?>
                                                        <form method="POST" style="display: inline;" onsubmit="return confirmDeleteCoupon('<?= htmlspecialchars($coupon['code']) ?>')">
                                                            <input type="hidden" name="coupon_id" value="<?= $coupon['id'] ?>">
                                                            <button type="submit" name="delete_coupon" class="btn btn-sm btn-outline-danger" title="Kuponu Sil">
                                                                🗑️ SİL
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    
    <div class="modal fade" id="editTripModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Sefer Düzenle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="trip_id" id="edit_trip_id">
                        
                        <div class="mb-3">
                            <label class="form-label">Kalkış</label>
                            <select name="edit_kalkis" id="edit_kalkis" class="form-select" required>
                                <option value="">Şehir Seçin</option>
                                <?php foreach ($cities as $city): ?>
                                    <option value="<?= $city ?>"><?= $city ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Varış</label>
                            <select name="edit_varis" id="edit_varis" class="form-select" required>
                                <option value="">Şehir Seçin</option>
                                <?php foreach ($cities as $city): ?>
                                    <option value="<?= $city ?>"><?= $city ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Tarih</label>
                            <input type="date" name="edit_tarih" id="edit_tarih" class="form-control" min="<?= date('Y-m-d') ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Saat</label>
                            <input type="time" name="edit_saat" id="edit_saat" class="form-control" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Fiyat (₺)</label>
                            <input type="number" name="edit_fiyat" id="edit_fiyat" class="form-control" step="0.01" min="1" required>
                        </div>
                        
                        <div class="alert alert-warning">
                            <small>
                                <strong>Uyarı:</strong> Bu sefer için aktif bilet bulunmadığından düzenleme yapabilirsiniz. 
                                Aktif biletli seferler düzenlenemez.
                            </small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" name="edit_trip" class="btn btn-primary">Güncelle</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function confirmDelete(route, datetime) {
            return confirm(
                'Bu seferi silmek istediğinizden emin misiniz?\n\n' +
                'Güzergah: ' + route + '\n' +
                'Tarih/Saat: ' + datetime + '\n\n' +
                'Bu işlem geri alınamaz!'
            );
        }
        
        function confirmDeleteCoupon(couponCode) {
            return confirm(
                'Bu kuponu silmek istediğinizden emin misiniz?\n\n' +
                'Kupon Kodu: ' + couponCode + '\n\n' +
                'Bu işlem geri alınamaz!'
            );
        }
        
        function editTrip(tripId, departure, destination, date, time, price) {

            document.getElementById('edit_trip_id').value = tripId;
            document.getElementById('edit_kalkis').value = departure;
            document.getElementById('edit_varis').value = destination;
            document.getElementById('edit_tarih').value = date;
            document.getElementById('edit_saat').value = time;
            document.getElementById('edit_fiyat').value = price;
            

            const modal = new bootstrap.Modal(document.getElementById('editTripModal'));
            modal.show();
        }
        

        document.addEventListener('DOMContentLoaded', function() {
            const dateInputs = document.querySelectorAll('input[type="date"]');
            const today = new Date().toISOString().split('T')[0];
            
            dateInputs.forEach(function(input) {
                input.min = today;
            });
        });
    </script>
</body>
</html>
