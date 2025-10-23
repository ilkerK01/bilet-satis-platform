<?php
require_once '../includes/auth.php';

requireRole('admin');

$message = '';
$activeTab = $_GET['tab'] ?? 'dashboard';
if ($_POST && isset($_POST['add_firm'])) {
    $firmName = $_POST['firm_name'] ?? '';
    if ($firmName) {
        $stmt = $pdo->prepare("INSERT INTO firms (ad) VALUES (?)");
        if ($stmt->execute([$firmName])) {
            $message = 'Firma başarıyla eklendi.';
        }
    }
}
if ($_POST && isset($_POST['add_firm_admin'])) {
    $name = $_POST['admin_name'] ?? '';
    $email = $_POST['admin_email'] ?? '';
    $password = $_POST['admin_password'] ?? '';
    $firmId = $_POST['firm_id'] ?? '';
    
    if ($name && $email && $password && $firmId) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (ad, email, sifre, rol, kredi, firma_id) VALUES (?, ?, ?, 'firma_admin', 0, ?)");
        if ($stmt->execute([$name, $email, $hashedPassword, $firmId])) {
            $message = 'Firma admin başarıyla eklendi.';
        }
    }
}
if ($_POST && isset($_POST['add_coupon'])) {
    $code = $_POST['coupon_code'] ?? '';
    $discount = $_POST['coupon_discount'] ?? '';
    $limit = $_POST['coupon_limit'] ?? '';
    $endDate = $_POST['coupon_end_date'] ?? '';
    
    if ($code && $discount && $limit && $endDate) {
        $stmt = $pdo->prepare("INSERT INTO coupons (kod, oran, limit_adet, son_tarih) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$code, $discount, $limit, $endDate])) {
            $message = 'Kupon başarıyla eklendi.';
        }
    }
}
$stats = [];
$stats['total_users'] = $pdo->query("SELECT COUNT(*) FROM users WHERE rol = 'user'")->fetchColumn();
$stats['total_firms'] = $pdo->query("SELECT COUNT(*) FROM firms")->fetchColumn();
$stats['total_trips'] = $pdo->query("SELECT COUNT(*) FROM trips")->fetchColumn();
$stats['total_tickets'] = $pdo->query("SELECT COUNT(*) FROM tickets WHERE durum = 'aktif'")->fetchColumn();
$stats['total_revenue'] = $pdo->query("SELECT SUM(fiyat) FROM tickets WHERE durum = 'aktif'")->fetchColumn() ?? 0;

$firms = $pdo->query("SELECT * FROM firms ORDER BY ad")->fetchAll();
$firmAdmins = $pdo->query("
    SELECT u.*, f.ad as firma_ad 
    FROM users u 
    LEFT JOIN firms f ON u.firma_id = f.id 
    WHERE u.rol = 'firma_admin' 
    ORDER BY u.ad
")->fetchAll();

$coupons = $pdo->query("SELECT * FROM coupons ORDER BY created_at DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Bilet Satış Platformu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/css/style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="/">
                ADMIN PANEL
            </a>
            <div class="navbar-nav ms-auto">
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
                        <a href="?tab=dashboard" class="list-group-item <?= $activeTab === 'dashboard' ? 'active' : '' ?>">
                            DASHBOARD
                        </a>
                        <a href="?tab=firms" class="list-group-item <?= $activeTab === 'firms' ? 'active' : '' ?>">
                            FİRMALAR
                        </a>
                        <a href="?tab=admins" class="list-group-item <?= $activeTab === 'admins' ? 'active' : '' ?>">
                            FİRMA ADMİNLERİ
                        </a>
                        <a href="?tab=coupons" class="list-group-item <?= $activeTab === 'coupons' ? 'active' : '' ?>">
                            KUPONLAR
                        </a>
                        <a href="?tab=drivers" class="list-group-item <?= $activeTab === 'drivers' ? 'active' : '' ?>">
                            ŞOFÖRLER
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-9">
                <?php if ($message): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
                <?php endif; ?>
                
                <?php if ($activeTab === 'dashboard'): ?>
                    <div class="admin-stats">
                        <h4 class="text-center mb-4">Sistem İstatistikleri</h4>
                        <div class="row">
                            <div class="col-md-2">
                                <div class="stat-card">
                                    <h3><?= $stats['total_users'] ?></h3>
                                    <p>Kullanıcı</p>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="stat-card">
                                    <h3><?= $stats['total_firms'] ?></h3>
                                    <p>Firma</p>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="stat-card">
                                    <h3><?= $stats['total_trips'] ?></h3>
                                    <p>Sefer</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stat-card">
                                    <h3><?= $stats['total_tickets'] ?></h3>
                                    <p>Aktif Bilet</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stat-card">
                                    <h3>₺<?= number_format($stats['total_revenue'], 2) ?></h3>
                                    <p>Toplam Gelir</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5>Son Biletler</h5>
                        </div>
                        <div class="card-body">
                            <?php
                            $recentTickets = $pdo->query("
                                SELECT t.*, u.ad as user_name, tr.kalkis, tr.varis, f.ad as firma_ad
                                FROM tickets t
                                JOIN users u ON t.user_id = u.id
                                JOIN trips tr ON t.trip_id = tr.id
                                JOIN firms f ON tr.firma_id = f.id
                                ORDER BY t.created_at DESC
                                LIMIT 10
                            ")->fetchAll();
                            ?>
                            
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Kullanıcı</th>
                                            <th>Güzergah</th>
                                            <th>Firma</th>
                                            <th>Fiyat</th>
                                            <th>Durum</th>
                                            <th>Tarih</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentTickets as $ticket): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($ticket['user_name']) ?></td>
                                                <td><?= htmlspecialchars($ticket['kalkis']) ?> → <?= htmlspecialchars($ticket['varis']) ?></td>
                                                <td><?= htmlspecialchars($ticket['firma_ad']) ?></td>
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
                        </div>
                    </div>
                
                <?php elseif ($activeTab === 'firms'): ?>
                    <div class="card">
                        <div class="card-header">
                            <h5>Firma Yönetimi</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" class="mb-4">
                                <div class="row">
                                    <div class="col-md-8">
                                        <input type="text" name="firm_name" class="form-control" placeholder="Firma Adı" required>
                                    </div>
                                    <div class="col-md-4">
                                        <button type="submit" name="add_firm" class="btn btn-success">Firma Ekle</button>
                                    </div>
                                </div>
                            </form>
                            
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Firma Adı</th>
                                            <th>Oluşturma Tarihi</th>
                                            <th>Sefer Sayısı</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($firms as $firm): ?>
                                            <?php
                                            $tripCount = $pdo->prepare("SELECT COUNT(*) FROM trips WHERE firma_id = ?");
                                            $tripCount->execute([$firm['id']]);
                                            $count = $tripCount->fetchColumn();
                                            ?>
                                            <tr>
                                                <td><?= $firm['id'] ?></td>
                                                <td><?= htmlspecialchars($firm['ad']) ?></td>
                                                <td><?= date('d.m.Y', strtotime($firm['created_at'])) ?></td>
                                                <td><?= $count ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                
                <?php elseif ($activeTab === 'admins'): ?>
                    <div class="card">
                        <div class="card-header">
                            <h5>Firma Admin Yönetimi</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" class="mb-4">
                                <div class="row">
                                    <div class="col-md-3">
                                        <input type="text" name="admin_name" class="form-control" placeholder="Ad Soyad" required>
                                    </div>
                                    <div class="col-md-3">
                                        <input type="email" name="admin_email" class="form-control" placeholder="Email" required>
                                    </div>
                                    <div class="col-md-2">
                                        <input type="password" name="admin_password" class="form-control" placeholder="Şifre" required>
                                    </div>
                                    <div class="col-md-2">
                                        <select name="firm_id" class="form-select" required>
                                            <option value="">Firma Seç</option>
                                            <?php foreach ($firms as $firm): ?>
                                                <option value="<?= $firm['id'] ?>"><?= htmlspecialchars($firm['ad']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <button type="submit" name="add_firm_admin" class="btn btn-success">Ekle</button>
                                    </div>
                                </div>
                            </form>
                            
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Ad</th>
                                            <th>Email</th>
                                            <th>Firma</th>
                                            <th>Oluşturma Tarihi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($firmAdmins as $admin): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($admin['ad']) ?></td>
                                                <td><?= htmlspecialchars($admin['email']) ?></td>
                                                <td><?= htmlspecialchars($admin['firma_ad'] ?? 'Atanmamış') ?></td>
                                                <td><?= date('d.m.Y', strtotime($admin['created_at'])) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
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
                                    <div class="col-md-2">
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
                                    <div class="col-md-3">
                                        <button type="submit" name="add_coupon" class="btn btn-success">Kupon Ekle</button>
                                    </div>
                                </div>
                            </form>
                            
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
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($coupons as $coupon): ?>
                                            <tr>
                                                <td><strong><?= htmlspecialchars($coupon['kod']) ?></strong></td>
                                                <td>%<?= $coupon['oran'] ?></td>
                                                <td><?= $coupon['limit_adet'] ?></td>
                                                <td><?= $coupon['kullanilan'] ?></td>
                                                <td><?= date('d.m.Y', strtotime($coupon['son_tarih'])) ?></td>
                                                <td>
                                                    <?php if ($coupon['son_tarih'] < date('Y-m-d')): ?>
                                                        <span class="badge bg-danger">Süresi Dolmuş</span>
                                                    <?php elseif ($coupon['kullanilan'] >= $coupon['limit_adet']): ?>
                                                        <span class="badge bg-warning">Limit Dolmuş</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-success">Aktif</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                
                <?php elseif ($activeTab === 'drivers'): ?>
                    <div class="card">
                        <div class="card-header">
                            <h5>Şoför Yönetimi</h5>
                        </div>
                        <div class="card-body">
                            <?php
                            $drivers = $pdo->query("
                                SELECT d.*, u.ad as user_name, u.email, f.ad as firma_ad
                                FROM drivers d
                                JOIN users u ON d.user_id = u.id
                                JOIN firms f ON d.firma_id = f.id
                                ORDER BY d.created_at DESC
                            ")->fetchAll();
                            ?>
                            
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Şoför Adı</th>
                                            <th>Email</th>
                                            <th>Firma</th>
                                            <th>Lisans No</th>
                                            <th>Telefon</th>
                                            <th>Durum</th>
                                            <th>Sefer Sayısı</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($drivers as $driver): ?>
                                            <?php
                                            $tripCount = $pdo->prepare("SELECT COUNT(*) FROM trips WHERE driver_id = ?");
                                            $tripCount->execute([$driver['id']]);
                                            $trips = $tripCount->fetchColumn();
                                            ?>
                                            <tr>
                                                <td><?= htmlspecialchars($driver['user_name']) ?></td>
                                                <td><?= htmlspecialchars($driver['email']) ?></td>
                                                <td><?= htmlspecialchars($driver['firma_ad']) ?></td>
                                                <td><?= htmlspecialchars($driver['lisans_no']) ?></td>
                                                <td><?= htmlspecialchars($driver['telefon']) ?></td>
                                                <td>
                                                    <span class="badge <?= $driver['durum'] === 'aktif' ? 'bg-success' : 'bg-danger' ?>">
                                                        <?= strtoupper($driver['durum']) ?>
                                                    </span>
                                                </td>
                                                <td><?= $trips ?></td>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
