<?php
require_once '../includes/auth.php';

requireRole('firma_admin');

$user = getCurrentUser();
$message = '';
$activeTab = $_GET['tab'] ?? 'trips';


if ($_POST && isset($_POST['add_trip'])) {
    $kalkis = $_POST['kalkis'] ?? '';
    $varis = $_POST['varis'] ?? '';
    $tarih = $_POST['tarih'] ?? '';
    $saat = $_POST['saat'] ?? '';
    $fiyat = $_POST['fiyat'] ?? '';
    $koltukSayisi = $_POST['koltuk_sayisi'] ?? 40;
    
    if ($kalkis && $varis && $tarih && $saat && $fiyat) {
        $stmt = $pdo->prepare("INSERT INTO trips (firma_id, kalkis, varis, tarih, saat, fiyat, koltuk_sayisi) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$user['firma_id'], $kalkis, $varis, $tarih, $saat, $fiyat, $koltukSayisi])) {
            $message = 'Sefer başarıyla eklendi.';
        }
    }
}


if ($_POST && isset($_POST['update_trip'])) {
    $tripId = $_POST['trip_id'] ?? '';
    $kalkis = $_POST['kalkis'] ?? '';
    $varis = $_POST['varis'] ?? '';
    $tarih = $_POST['tarih'] ?? '';
    $saat = $_POST['saat'] ?? '';
    $fiyat = $_POST['fiyat'] ?? '';
    $koltukSayisi = $_POST['koltuk_sayisi'] ?? 40;
    
    if ($tripId && $kalkis && $varis && $tarih && $saat && $fiyat) {
        $stmt = $pdo->prepare("UPDATE trips SET kalkis = ?, varis = ?, tarih = ?, saat = ?, fiyat = ?, koltuk_sayisi = ? WHERE id = ? AND firma_id = ?");
        if ($stmt->execute([$kalkis, $varis, $tarih, $saat, $fiyat, $koltukSayisi, $tripId, $user['firma_id']])) {
            $message = 'Sefer başarıyla güncellendi.';
        }
    }
}


if ($_POST && isset($_POST['delete_trip'])) {
    $tripId = $_POST['trip_id'] ?? '';
    
    if ($tripId) {

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE trip_id = ? AND durum = 'aktif'");
        $stmt->execute([$tripId]);
        $activeTickets = $stmt->fetchColumn();
        
        if ($activeTickets > 0) {
            $message = 'Bu seferde aktif biletler bulunduğu için silinemez.';
        } else {
            $stmt = $pdo->prepare("DELETE FROM trips WHERE id = ? AND firma_id = ?");
            if ($stmt->execute([$tripId, $user['firma_id']])) {
                $message = 'Sefer başarıyla silindi.';
            }
        }
    }
}


$stmt = $pdo->prepare("SELECT * FROM firms WHERE id = ?");
$stmt->execute([$user['firma_id']]);
$firm = $stmt->fetch();


$stmt = $pdo->prepare("SELECT * FROM trips WHERE firma_id = ? ORDER BY tarih DESC, saat DESC");
$stmt->execute([$user['firma_id']]);
$trips = $stmt->fetchAll();


$stats = [];
$stats['total_trips'] = count($trips);
$stats['active_tickets'] = $pdo->prepare("SELECT COUNT(*) FROM tickets t JOIN trips tr ON t.trip_id = tr.id WHERE tr.firma_id = ? AND t.durum = 'aktif'");
$stats['active_tickets']->execute([$user['firma_id']]);
$stats['active_tickets'] = $stats['active_tickets']->fetchColumn();

$stats['total_revenue'] = $pdo->prepare("SELECT SUM(t.fiyat) FROM tickets t JOIN trips tr ON t.trip_id = tr.id WHERE tr.firma_id = ? AND t.durum = 'aktif'");
$stats['total_revenue']->execute([$user['firma_id']]);
$stats['total_revenue'] = $stats['total_revenue']->fetchColumn() ?? 0;


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
    <title>Firma Admin Panel - <?= htmlspecialchars($firm['ad']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/css/style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-success">
        <div class="container">
            <a class="navbar-brand" href="/">
                <?= strtoupper(htmlspecialchars($firm['ad'])) ?> - ADMIN PANEL
            </a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3"><?= htmlspecialchars($user['ad']) ?></span>
                <a class="nav-link" href="/">Ana Sayfa</a>
                <a class="nav-link" href="/pages/logout.php">Çıkış</a>
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
                        <a href="?tab=tickets" class="list-group-item <?= $activeTab === 'tickets' ? 'active' : '' ?>">
                            BİLETLER
                        </a>
                    </div>
                </div>
                
                <div class="card mt-3">
                    <div class="card-header">
                        <h6>İstatistikler</h6>
                    </div>
                    <div class="card-body">
                        <div class="stat-card mb-2">
                            <h4><?= $stats['total_trips'] ?></h4>
                            <small>Toplam Sefer</small>
                        </div>
                        <div class="stat-card mb-2">
                            <h4><?= $stats['active_tickets'] ?></h4>
                            <small>Aktif Bilet</small>
                        </div>
                        <div class="stat-card">
                            <h4>₺<?= number_format($stats['total_revenue'], 2) ?></h4>
                            <small>Toplam Gelir</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-9">
                <?php if ($message): ?>
                    <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
                <?php endif; ?>
                
                <?php if ($activeTab === 'trips'): ?>
                    <div class="card">
                        <div class="card-header">
                            <h5>Seferler</h5>
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
                                                $soldTickets = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE trip_id = ? AND durum = 'aktif'");
                                                $soldTickets->execute([$trip['id']]);
                                                $sold = $soldTickets->fetchColumn();
                                                ?>
                                                <tr>
                                                    <td>
                                                        <strong><?= htmlspecialchars($trip['kalkis']) ?></strong> → 
                                                        <strong><?= htmlspecialchars($trip['varis']) ?></strong>
                                                    </td>
                                                    <td>
                                                        <?= date('d.m.Y', strtotime($trip['tarih'])) ?><br>
                                                        <small><?= date('H:i', strtotime($trip['saat'])) ?></small>
                                                    </td>
                                                    <td>₺<?= number_format($trip['fiyat'], 2) ?></td>
                                                    <td><?= $trip['koltuk_sayisi'] ?></td>
                                                    <td>
                                                        <span class="badge <?= $sold > 0 ? 'bg-success' : 'bg-secondary' ?>">
                                                            <?= $sold ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <button class="btn btn-sm btn-outline-primary" 
                                                                onclick="editTrip(<?= htmlspecialchars(json_encode($trip)) ?>)">
                                                            Düzenle
                                                        </button>
                                                        
                                                        <?php if ($sold == 0): ?>
                                                            <form method="POST" style="display: inline;" 
                                                                  onsubmit="return confirm('Bu seferi silmek istediğinizden emin misiniz?')">
                                                                <input type="hidden" name="trip_id" value="<?= $trip['id'] ?>">
                                                                <button type="submit" name="delete_trip" class="btn btn-sm btn-outline-danger">
                                                                    Sil
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
                
                <?php elseif ($activeTab === 'add_trip'): ?>
                    <div class="card">
                        <div class="card-header">
                            <h5>Yeni Sefer Ekle</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-floating mb-3">
                                            <select name="kalkis" class="form-select" required>
                                                <option value="">Kalkış Şehri Seçin</option>
                                                <?php foreach ($cities as $city): ?>
                                                    <option value="<?= $city ?>"><?= $city ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <label>Kalkış</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-floating mb-3">
                                            <select name="varis" class="form-select" required>
                                                <option value="">Varış Şehri Seçin</option>
                                                <?php foreach ($cities as $city): ?>
                                                    <option value="<?= $city ?>"><?= $city ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <label>Varış</label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-floating mb-3">
                                            <input type="date" name="tarih" class="form-control" min="2025-10-08" required>
                                            <label>Tarih</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-floating mb-3">
                                            <input type="time" name="saat" class="form-control" required>
                                            <label>Saat</label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-floating mb-3">
                                            <input type="number" name="fiyat" class="form-control" step="0.01" min="1" required>
                                            <label>Fiyat (₺)</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-floating mb-3">
                                            <input type="number" name="koltuk_sayisi" class="form-control" value="40" min="1" max="60" required>
                                            <label>Koltuk Sayısı</label>
                                        </div>
                                    </div>
                                </div>
                                
                                <button type="submit" name="add_trip" class="btn btn-success">Sefer Ekle</button>
                            </form>
                        </div>
                    </div>
                
                <?php elseif ($activeTab === 'tickets'): ?>
                    <div class="card">
                        <div class="card-header">
                            <h5>Biletler</h5>
                        </div>
                        <div class="card-body">
                            <?php
                            $tickets = $pdo->prepare("
                                SELECT t.*, tr.kalkis, tr.varis, tr.tarih, tr.saat, u.ad as yolcu_ad, u.email
                                FROM tickets t
                                JOIN trips tr ON t.trip_id = tr.id
                                JOIN users u ON t.user_id = u.id
                                WHERE tr.firma_id = ?
                                ORDER BY tr.tarih DESC, tr.saat DESC
                            ");
                            $tickets->execute([$user['firma_id']]);
                            $tickets = $tickets->fetchAll();
                            ?>
                            
                            <?php if (empty($tickets)): ?>
                                <div class="text-center py-5">
                                    <h6>Henüz bilet satışı yapılmamış.</h6>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Yolcu</th>
                                                <th>Güzergah</th>
                                                <th>Tarih/Saat</th>
                                                <th>Koltuk</th>
                                                <th>Fiyat</th>
                                                <th>Durum</th>
                                                <th>Satış Tarihi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($tickets as $ticket): ?>
                                                <tr>
                                                    <td>
                                                        <?= htmlspecialchars($ticket['yolcu_ad']) ?><br>
                                                        <small class="text-muted"><?= htmlspecialchars($ticket['email']) ?></small>
                                                    </td>
                                                    <td>
                                                        <?= htmlspecialchars($ticket['kalkis']) ?> → 
                                                        <?= htmlspecialchars($ticket['varis']) ?>
                                                    </td>
                                                    <td>
                                                        <?= date('d.m.Y', strtotime($ticket['tarih'])) ?><br>
                                                        <small><?= date('H:i', strtotime($ticket['saat'])) ?></small>
                                                    </td>
                                                    <td><?= $ticket['koltuk_no'] ?></td>
                                                    <td>₺<?= number_format($ticket['fiyat'], 2) ?></td>
                                                    <td>
                                                        <span class="badge <?= $ticket['durum'] === 'aktif' ? 'bg-success' : 'bg-danger' ?>">
                                                            <?= $ticket['durum'] ?>
                                                        </span>
                                                    </td>
                                                    <td><?= date('d.m.Y H:i', strtotime($ticket['created_at'])) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
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
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <select name="kalkis" id="edit_kalkis" class="form-select" required>
                                        <?php foreach ($cities as $city): ?>
                                            <option value="<?= $city ?>"><?= $city ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <label>Kalkış</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <select name="varis" id="edit_varis" class="form-select" required>
                                        <?php foreach ($cities as $city): ?>
                                            <option value="<?= $city ?>"><?= $city ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <label>Varış</label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <input type="date" name="tarih" id="edit_tarih" class="form-control" required>
                                    <label>Tarih</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <input type="time" name="saat" id="edit_saat" class="form-control" required>
                                    <label>Saat</label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <input type="number" name="fiyat" id="edit_fiyat" class="form-control" step="0.01" min="1" required>
                                    <label>Fiyat (₺)</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <input type="number" name="koltuk_sayisi" id="edit_koltuk_sayisi" class="form-control" min="1" max="60" required>
                                    <label>Koltuk Sayısı</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" name="update_trip" class="btn btn-primary">Güncelle</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editTrip(trip) {
            document.getElementById('edit_trip_id').value = trip.id;
            document.getElementById('edit_kalkis').value = trip.kalkis;
            document.getElementById('edit_varis').value = trip.varis;
            document.getElementById('edit_tarih').value = trip.tarih;
            document.getElementById('edit_saat').value = trip.saat;
            document.getElementById('edit_fiyat').value = trip.fiyat;
            document.getElementById('edit_koltuk_sayisi').value = trip.koltuk_sayisi;
            
            new bootstrap.Modal(document.getElementById('editTripModal')).show();
        }
    </script>
</body>
</html>
