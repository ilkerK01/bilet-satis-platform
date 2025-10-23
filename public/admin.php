<?php
require_once '../includes/auth.php';

requireRole('admin');

$message = '';
$activeTab = $_GET['tab'] ?? 'dashboard';


if ($_POST && isset($_POST['add_firm'])) {
    $firmName = $_POST['firm_name'] ?? '';
    if ($firmName) {

        $firmId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
        
        $stmt = $pdo->prepare("INSERT INTO Bus_Company (id, name) VALUES (?, ?)");
        if ($stmt->execute([$firmId, $firmName])) {
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

        $userId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
        
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO User (id, full_name, email, password, role, balance, company_id) VALUES (?, ?, ?, ?, 'company', 500, ?)");
        if ($stmt->execute([$userId, $name, $email, $hashedPassword, $firmId])) {
            $message = 'Firma admin başarıyla eklendi.';
        }
    }
}


if ($_POST && isset($_POST['add_coupon'])) {
    $code = $_POST['coupon_code'] ?? '';
    $discount = $_POST['coupon_discount'] ?? '';
    $limit = $_POST['coupon_limit'] ?? '';
    $endDate = $_POST['coupon_end_date'] ?? '';
    $company = $_POST['coupon_company'] ?? '';
    
    if ($code && $discount && $limit && $endDate && $company) {

        $couponId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
        
        $discountDecimal = $discount / 100; // Yüzdeyi ondalık sayıya çevir
        $companyId = ($company === 'all') ? null : $company; // Global kupon için null
        
        $stmt = $pdo->prepare("INSERT INTO Coupons (id, code, discount, company_id, usage_limit, expire_date) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$couponId, $code, $discountDecimal, $companyId, $limit, $endDate . ' 23:59:59'])) {
            $companyName = ($company === 'all') ? 'Tüm Firmalar' : 'Seçilen Firma';
            $message = 'Kupon başarıyla eklendi. (' . $companyName . ' için)';
        }
    }
}


if ($_POST && isset($_POST['delete_coupon'])) {
    $couponId = $_POST['coupon_id'] ?? '';
    
    if ($couponId) {

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM User_Coupons WHERE coupon_id = ?");
        $stmt->execute([$couponId]);
        $usageCount = $stmt->fetchColumn();
        
        if ($usageCount > 0) {
            $message = 'Bu kupon silinemez! ' . $usageCount . ' kez kullanılmış.';
        } else {
            $stmt = $pdo->prepare("DELETE FROM Coupons WHERE id = ?");
            if ($stmt->execute([$couponId])) {
                $message = 'Kupon başarıyla silindi.';
            } else {
                $message = 'Kupon silinirken bir hata oluştu.';
            }
        }
    }
}


if ($_POST && isset($_POST['edit_firm'])) {
    $firmId = $_POST['firm_id'] ?? '';
    $newName = $_POST['new_firm_name'] ?? '';
    
    if ($firmId && $newName) {
        $stmt = $pdo->prepare("UPDATE Bus_Company SET name = ? WHERE id = ?");
        if ($stmt->execute([$newName, $firmId])) {
            $message = 'Firma adı başarıyla güncellendi.';
        } else {
            $message = 'Firma güncellenirken bir hata oluştu.';
        }
    }
}


if ($_POST && isset($_POST['delete_firm'])) {
    $firmId = $_POST['firm_id'] ?? '';
    
    if ($firmId) {

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM Trips WHERE company_id = ?");
        $stmt->execute([$firmId]);
        $tripCount = $stmt->fetchColumn();
        

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM User WHERE company_id = ?");
        $stmt->execute([$firmId]);
        $userCount = $stmt->fetchColumn();
        
        if ($tripCount > 0) {
            $message = 'Bu firma silinemez! Firmaya ait ' . $tripCount . ' adet sefer bulunmaktadır.';
        } elseif ($userCount > 0) {
            $message = 'Bu firma silinemez! Firmaya ait ' . $userCount . ' adet kullanıcı bulunmaktadır.';
        } else {
            $pdo->beginTransaction();
            try {

                $stmt = $pdo->prepare("DELETE FROM Coupons WHERE company_id = ?");
                $stmt->execute([$firmId]);
                

                $stmt = $pdo->prepare("DELETE FROM Bus_Company WHERE id = ?");
                $stmt->execute([$firmId]);
                
                $pdo->commit();
                $message = 'Firma başarıyla silindi.';
            } catch (Exception $e) {
                $pdo->rollback();
                $message = 'Firma silinirken bir hata oluştu.';
            }
        }
    }
}


if ($_POST && isset($_POST['delete_firm_admin'])) {
    $adminId = $_POST['admin_id'] ?? '';
    
    if ($adminId) {

        $stmt = $pdo->prepare("SELECT id FROM User WHERE id = ? AND role = 'company'");
        $stmt->execute([$adminId]);
        
        if ($stmt->fetch()) {
            $pdo->beginTransaction();
            try {

                $stmt = $pdo->prepare("DELETE FROM User_Coupons WHERE user_id = ?");
                $stmt->execute([$adminId]);
                

                $stmt = $pdo->prepare("DELETE FROM User WHERE id = ? AND role = 'company'");
                $stmt->execute([$adminId]);
                
                $pdo->commit();
                $message = 'Firma admin başarıyla silindi.';
            } catch (Exception $e) {
                $pdo->rollback();
                $message = 'Firma admin silinirken bir hata oluştu.';
            }
        } else {
            $message = 'Geçersiz kullanıcı ID veya yetkiniz yok.';
        }
    }
}


if ($_POST && isset($_POST['delete_user'])) {
    $userId = $_POST['user_id'] ?? '';
    
    if ($userId) {

        $stmt = $pdo->prepare("SELECT id FROM User WHERE id = ? AND role = 'user'");
        $stmt->execute([$userId]);
        
        if ($stmt->fetch()) {

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM Tickets WHERE user_id = ? AND status = 'active'");
            $stmt->execute([$userId]);
            $activeTickets = $stmt->fetchColumn();
            
            if ($activeTickets > 0) {
                $message = 'Bu kullanıcı silinemez! ' . $activeTickets . ' adet aktif bileti bulunmaktadır.';
            } else {
                $pdo->beginTransaction();
                try {

                    $stmt = $pdo->prepare("DELETE FROM Booked_Seats WHERE ticket_id IN (SELECT id FROM Tickets WHERE user_id = ?)");
                    $stmt->execute([$userId]);
                    

                    $stmt = $pdo->prepare("DELETE FROM Tickets WHERE user_id = ? AND status = 'canceled'");
                    $stmt->execute([$userId]);
                    

                    $stmt = $pdo->prepare("DELETE FROM User_Coupons WHERE user_id = ?");
                    $stmt->execute([$userId]);
                    

                    $stmt = $pdo->prepare("DELETE FROM User WHERE id = ? AND role = 'user'");
                    $stmt->execute([$userId]);
                    
                    $pdo->commit();
                    $message = 'Kullanıcı başarıyla silindi.';
                } catch (Exception $e) {
                    $pdo->rollback();
                    $message = 'Kullanıcı silinirken bir hata oluştu.';
                }
            }
        } else {
            $message = 'Geçersiz kullanıcı ID veya yetkiniz yok.';
        }
    }
}


$stats = [];
$stats['total_users'] = $pdo->query("SELECT COUNT(*) FROM User WHERE role = 'user'")->fetchColumn();
$stats['total_firms'] = $pdo->query("SELECT COUNT(*) FROM Bus_Company")->fetchColumn();
$stats['total_trips'] = $pdo->query("SELECT COUNT(*) FROM Trips")->fetchColumn();
$stats['total_tickets'] = $pdo->query("SELECT COUNT(*) FROM Tickets WHERE status = 'active'")->fetchColumn();
$stats['total_revenue'] = $pdo->query("SELECT SUM(total_price) FROM Tickets WHERE status = 'active'")->fetchColumn() ?? 0;


$firms = $pdo->query("SELECT * FROM Bus_Company ORDER BY name")->fetchAll();


$firmAdmins = $pdo->query("
    SELECT u.*, bc.name as firma_ad 
    FROM User u 
    LEFT JOIN Bus_Company bc ON u.company_id = bc.id 
    WHERE u.role = 'company' 
    ORDER BY u.full_name
")->fetchAll();


$coupons = $pdo->query("SELECT * FROM Coupons ORDER BY created_at DESC")->fetchAll();


$users = $pdo->query("
    SELECT u.*, 
           (SELECT COUNT(*) FROM Tickets WHERE user_id = u.id AND status = 'active') as ticket_count,
           (SELECT SUM(total_price) FROM Tickets WHERE user_id = u.id AND status = 'active') as total_spent
    FROM User u 
    WHERE u.role = 'user' 
    ORDER BY u.created_at DESC
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Bilet Satış Platformu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="/">
                ADMIN PANEL
            </a>
            <div class="navbar-nav ms-auto">
                <div class="navbar-buttons">
                    <a class="nav-btn" href="/">Ana Sayfa</a>
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
                        <a href="?tab=dashboard" class="list-group-item <?= $activeTab === 'dashboard' ? 'active' : '' ?>">
                            DASHBOARD
                        </a>
                        <a href="?tab=firms" class="list-group-item <?= $activeTab === 'firms' ? 'active' : '' ?>">
                            FİRMALAR
                        </a>
                        <a href="?tab=admins" class="list-group-item <?= $activeTab === 'admins' ? 'active' : '' ?>">
                            FİRMA ADMİNLERİ
                        </a>
                        <a href="?tab=users" class="list-group-item <?= $activeTab === 'users' ? 'active' : '' ?>">
                            KULLANICILAR
                        </a>
                        <a href="?tab=coupons" class="list-group-item <?= $activeTab === 'coupons' ? 'active' : '' ?>">
                            KUPONLAR
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
                                SELECT t.*, u.full_name as user_name, tr.departure_city, tr.destination_city, bc.name as firma_ad
                                FROM Tickets t
                                JOIN User u ON t.user_id = u.id
                                JOIN Trips tr ON t.trip_id = tr.id
                                JOIN Bus_Company bc ON tr.company_id = bc.id
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
                                                <td><?= htmlspecialchars($ticket['departure_city']) ?> → <?= htmlspecialchars($ticket['destination_city']) ?></td>
                                                <td><?= htmlspecialchars($ticket['firma_ad']) ?></td>
                                                <td>₺<?= number_format($ticket['total_price'], 2) ?></td>
                                                <td>
                                                    <span class="badge <?= $ticket['status'] === 'active' ? 'bg-success' : 'bg-danger' ?>">
                                                        <?= $ticket['status'] ?>
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
                                            <th>İşlemler</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($firms as $firm): ?>
                                            <?php
                                            $tripCount = $pdo->prepare("SELECT COUNT(*) FROM Trips WHERE company_id = ?");
                                            $tripCount->execute([$firm['id']]);
                                            $count = $tripCount->fetchColumn();
                                            ?>
                                            <tr>
                                                <td><?= substr($firm['id'], 0, 8) ?>...</td>
                                                <td>
                                                    <span id="firmName_<?= $firm['id'] ?>"><?= htmlspecialchars($firm['name']) ?></span>
                                                    <div id="editForm_<?= $firm['id'] ?>" style="display: none;">
                                                        <form method="POST" style="display: inline;">
                                                            <input type="hidden" name="firm_id" value="<?= $firm['id'] ?>">
                                                            <div class="input-group input-group-sm">
                                                                <input type="text" name="new_firm_name" class="form-control" value="<?= htmlspecialchars($firm['name']) ?>" required>
                                                                <button type="submit" name="edit_firm" class="btn btn-success btn-sm">✓</button>
                                                                <button type="button" class="btn btn-secondary btn-sm" onclick="cancelEdit('<?= $firm['id'] ?>')">✗</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </td>
                                                <td><?= date('d.m.Y', strtotime($firm['created_at'])) ?></td>
                                                <td><?= $count ?></td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="editFirm('<?= $firm['id'] ?>')" title="Düzenle">
                                                            ✏️
                                                        </button>
                                                        <?php

                                                        $userCount = $pdo->prepare("SELECT COUNT(*) FROM User WHERE company_id = ?");
                                                        $userCount->execute([$firm['id']]);
                                                        $users = $userCount->fetchColumn();
                                                        ?>
                                                        <?php if ($count > 0 || $users > 0): ?>
                                                            <button type="button" class="btn btn-outline-secondary btn-sm" disabled title="Sefer veya kullanıcı var">
                                                                🗑️
                                                            </button>
                                                        <?php else: ?>
                                                            <form method="POST" style="display: inline;" onsubmit="return confirmDeleteFirm('<?= htmlspecialchars($firm['name']) ?>')">
                                                                <input type="hidden" name="firm_id" value="<?= $firm['id'] ?>">
                                                                <button type="submit" name="delete_firm" class="btn btn-outline-danger btn-sm" title="Sil">
                                                                    🗑️
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
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
                                                <option value="<?= $firm['id'] ?>"><?= htmlspecialchars($firm['name']) ?></option>
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
                                            <th>İşlemler</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($firmAdmins as $admin): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($admin['full_name']) ?></td>
                                                <td><?= htmlspecialchars($admin['email']) ?></td>
                                                <td><?= htmlspecialchars($admin['firma_ad'] ?? 'Atanmamış') ?></td>
                                                <td><?= date('d.m.Y', strtotime($admin['created_at'])) ?></td>
                                                <td>
                                                    <form method="POST" style="display: inline;" onsubmit="return confirmDeleteFirmAdmin('<?= htmlspecialchars($admin['full_name']) ?>', '<?= htmlspecialchars($admin['email']) ?>')">
                                                        <input type="hidden" name="admin_id" value="<?= $admin['id'] ?>">
                                                        <button type="submit" name="delete_firm_admin" class="btn btn-sm btn-outline-danger" title="Firma Admini Sil">
                                                            🗑️ SİL
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                
                <?php elseif ($activeTab === 'users'): ?>
                    <div class="card">
                        <div class="card-header">
                            <h5>Kullanıcı Yönetimi (<?= count($users) ?> Kullanıcı)</h5>
                        </div>
                        <div class="card-body">
                            <div class="row mb-4">
                                <div class="col-md-3">
                                    <div class="stat-card">
                                        <h4><?= count($users) ?></h4>
                                        <p>Toplam Kullanıcı</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="stat-card">
                                        <h4><?= array_sum(array_column($users, 'ticket_count')) ?></h4>
                                        <p>Toplam Bilet</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="stat-card">
                                        <h4>₺<?= number_format(array_sum(array_column($users, 'total_spent')), 2) ?></h4>
                                        <p>Toplam Harcama</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="stat-card">
                                        <h4>₺<?= number_format(array_sum(array_column($users, 'balance')), 2) ?></h4>
                                        <p>Toplam Bakiye</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Ad Soyad</th>
                                            <th>Email</th>
                                            <th>Bakiye</th>
                                            <th>Bilet Sayısı</th>
                                            <th>Toplam Harcama</th>
                                            <th>Kayıt Tarihi</th>
                                            <th>Durum</th>
                                            <th>İşlemler</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($users as $user): ?>
                                            <tr>
                                                <td>
                                                    <strong><?= htmlspecialchars($user['full_name']) ?></strong>
                                                </td>
                                                <td><?= htmlspecialchars($user['email']) ?></td>
                                                <td>
                                                    <span class="badge <?= $user['balance'] > 0 ? 'bg-success' : 'bg-secondary' ?>">
                                                        ₺<?= number_format($user['balance'], 2) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($user['ticket_count'] > 0): ?>
                                                        <span class="badge bg-info"><?= $user['ticket_count'] ?> bilet</span>
                                                    <?php else: ?>
                                                        <span class="text-muted">Bilet yok</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($user['total_spent'] > 0): ?>
                                                        <strong>₺<?= number_format($user['total_spent'], 2) ?></strong>
                                                    <?php else: ?>
                                                        <span class="text-muted">₺0.00</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= date('d.m.Y', strtotime($user['created_at'])) ?></td>
                                                <td>
                                                    <?php
                                                    $daysSinceRegistration = (time() - strtotime($user['created_at'])) / (60 * 60 * 24);
                                                    if ($daysSinceRegistration <= 7): ?>
                                                        <span class="badge bg-success">Yeni</span>
                                                    <?php elseif ($user['ticket_count'] > 0): ?>
                                                        <span class="badge bg-primary">Aktif</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Pasif</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="showUserDetails('<?= $user['id'] ?>', '<?= htmlspecialchars($user['full_name']) ?>', '<?= htmlspecialchars($user['email']) ?>')" title="Detayları Gör">
                                                            👁️
                                                        </button>
                                                        <?php if ($user['ticket_count'] > 0): ?>
                                                            <button type="button" class="btn btn-outline-secondary btn-sm" disabled title="Aktif bilet var">
                                                                🗑️
                                                            </button>
                                                        <?php else: ?>
                                                            <form method="POST" style="display: inline;" onsubmit="return confirmDeleteUser('<?= htmlspecialchars($user['full_name']) ?>', '<?= htmlspecialchars($user['email']) ?>')">
                                                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                                <button type="submit" name="delete_user" class="btn btn-outline-danger btn-sm" title="Kullanıcıyı Sil">
                                                                    🗑️
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <?php if (empty($users)): ?>
                                <div class="text-center py-5">
                                    <h6>Henüz kayıtlı kullanıcı bulunmuyor.</h6>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    
                    <div class="modal fade" id="userDetailsModal" tabindex="-1" aria-labelledby="userDetailsModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content" style="background-color: #1a1a1a; border: 2px solid #ffffff; color: #ffffff;">
                                <div class="modal-header" style="border-bottom: 1px solid #333333;">
                                    <h5 class="modal-title" id="userDetailsModalLabel" style="color: #ffffff; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;">
                                        KULLANICI DETAYLARI
                                    </h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div id="userDetailsContent">
                                        <div class="text-center">
                                            <div class="spinner-border text-light" role="status">
                                                <span class="visually-hidden">Yükleniyor...</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer" style="border-top: 1px solid #333333;">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                                </div>
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
                                <div class="row mb-3">
                                    <div class="col-md-3">
                                        <label class="form-label">Kupon Kodu</label>
                                        <input type="text" name="coupon_code" class="form-control" placeholder="Kupon Kodu" required>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">İndirim (%)</label>
                                        <input type="number" name="coupon_discount" class="form-control" placeholder="İndirim %" min="1" max="100" required>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Kullanım Limiti</label>
                                        <input type="number" name="coupon_limit" class="form-control" placeholder="Limit" min="1" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Son Kullanma Tarihi</label>
                                        <input type="date" name="coupon_end_date" class="form-control" min="<?= date('Y-m-d') ?>" required>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">&nbsp;</label>
                                        <button type="submit" name="add_coupon" class="btn btn-success d-block w-100">Kupon Ekle</button>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <label class="form-label">Firma Seçimi</label>
                                        <select name="coupon_company" class="form-select" required>
                                            <option value="">Firma Seçin</option>
                                            <option value="all">🌐 Tüm Firmalar (Global Kupon)</option>
                                            <?php foreach ($firms as $firm): ?>
                                                <option value="<?= $firm['id'] ?>"><?= htmlspecialchars($firm['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Açıklama</label>
                                        <small class="form-text text-muted d-block">
                                            • <strong>Global Kupon:</strong> Tüm firmalarda kullanılabilir<br>
                                            • <strong>Firma Özel:</strong> Sadece seçilen firmada kullanılabilir
                                        </small>
                                    </div>
                                </div>
                            </form>
                            
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Kod</th>
                                            <th>İndirim</th>
                                            <th>Firma</th>
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
                                                <td>
                                                    <?php if ($coupon['company_id']): ?>
                                                        <?php
                                                        $companyStmt = $pdo->prepare("SELECT name FROM Bus_Company WHERE id = ?");
                                                        $companyStmt->execute([$coupon['company_id']]);
                                                        $companyName = $companyStmt->fetchColumn();
                                                        ?>
                                                        <span class="badge bg-info"><?= htmlspecialchars($companyName) ?></span>
                                                    <?php else: ?>
                                                        <span class="badge bg-success">🌐 Global</span>
                                                    <?php endif; ?>
                                                </td>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function editFirm(firmId) {

            document.getElementById('firmName_' + firmId).style.display = 'none';
            document.getElementById('editForm_' + firmId).style.display = 'block';
            

            const input = document.querySelector('#editForm_' + firmId + ' input[name="new_firm_name"]');
            input.focus();
            input.select();
        }
        
        function cancelEdit(firmId) {

            document.getElementById('editForm_' + firmId).style.display = 'none';
            document.getElementById('firmName_' + firmId).style.display = 'block';
        }
        
        function confirmDeleteFirm(firmName) {
            return confirm(
                'Bu firmayı silmek istediğinizden emin misiniz?\n\n' +
                'Firma: ' + firmName + '\n\n' +
                'Bu işlem geri alınamaz!\n' +
                'Firmaya ait tüm kuponlar da silinecektir.'
            );
        }
        
        function confirmDeleteCoupon(couponCode) {
            return confirm(
                'Bu kuponu silmek istediğinizden emin misiniz?\n\n' +
                'Kupon Kodu: ' + couponCode + '\n\n' +
                'Bu işlem geri alınamaz!'
            );
        }
        
        function confirmDeleteFirmAdmin(adminName, adminEmail) {
            return confirm(
                'Bu firma adminini silmek istediğinizden emin misiniz?\n\n' +
                'Ad: ' + adminName + '\n' +
                'Email: ' + adminEmail + '\n\n' +
                'Bu işlem geri alınamaz!\n' +
                'Kullanıcının tüm kupon kullanımları da silinecektir.'
            );
        }
        
        function confirmDeleteUser(userName, userEmail) {
            return confirm(
                'Bu kullanıcıyı silmek istediğinizden emin misiniz?\n\n' +
                'Ad: ' + userName + '\n' +
                'Email: ' + userEmail + '\n\n' +
                'Bu işlem geri alınamaz!\n' +
                'Kullanıcının iptal edilmiş biletleri ve kupon kullanımları da silinecektir.'
            );
        }
        
        function showUserDetails(userId, userName, userEmail) {

            const modal = new bootstrap.Modal(document.getElementById('userDetailsModal'));
            document.getElementById('userDetailsModalLabel').textContent = 'KULLANICI DETAYLARI - ' + userName;
            

            document.getElementById('userDetailsContent').innerHTML = `
                <div class="text-center">
                    <div class="spinner-border text-light" role="status">
                        <span class="visually-hidden">Yükleniyor...</span>
                    </div>
                </div>
            `;
            
            modal.show();
            

            fetch('get_user_details.php?user_id=' + userId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('userDetailsContent').innerHTML = `
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Kullanıcı Bilgileri</h6>
                                    <table class="table table-sm table-dark">
                                        <tr><td><strong>Ad Soyad:</strong></td><td>${data.user.full_name}</td></tr>
                                        <tr><td><strong>Email:</strong></td><td>${data.user.email}</td></tr>
                                        <tr><td><strong>Bakiye:</strong></td><td>₺${parseFloat(data.user.balance).toFixed(2)}</td></tr>
                                        <tr><td><strong>Kayıt Tarihi:</strong></td><td>${new Date(data.user.created_at).toLocaleDateString('tr-TR')}</td></tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <h6>İstatistikler</h6>
                                    <table class="table table-sm table-dark">
                                        <tr><td><strong>Toplam Bilet:</strong></td><td>${data.stats.total_tickets}</td></tr>
                                        <tr><td><strong>Aktif Bilet:</strong></td><td>${data.stats.active_tickets}</td></tr>
                                        <tr><td><strong>İptal Bilet:</strong></td><td>${data.stats.canceled_tickets}</td></tr>
                                        <tr><td><strong>Toplam Harcama:</strong></td><td>₺${parseFloat(data.stats.total_spent).toFixed(2)}</td></tr>
                                    </table>
                                </div>
                            </div>
                            
                            <h6 class="mt-4">Son Biletler</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-dark">
                                    <thead>
                                        <tr>
                                            <th>Güzergah</th>
                                            <th>Tarih</th>
                                            <th>Koltuk</th>
                                            <th>Fiyat</th>
                                            <th>Durum</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${data.tickets.map(ticket => `
                                            <tr>
                                                <td>${ticket.departure_city} → ${ticket.destination_city}</td>
                                                <td>${new Date(ticket.departure_time).toLocaleDateString('tr-TR')}</td>
                                                <td>${ticket.seat_number}</td>
                                                <td>₺${parseFloat(ticket.total_price).toFixed(2)}</td>
                                                <td>
                                                    <span class="badge ${ticket.status === 'active' ? 'bg-success' : 'bg-danger'}">
                                                        ${ticket.status === 'active' ? 'Aktif' : 'İptal'}
                                                    </span>
                                                </td>
                                            </tr>
                                        `).join('')}
                                    </tbody>
                                </table>
                            </div>
                        `;
                    } else {
                        document.getElementById('userDetailsContent').innerHTML = `
                            <div class="alert alert-danger">
                                Kullanıcı detayları yüklenirken hata oluştu.
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    document.getElementById('userDetailsContent').innerHTML = `
                        <div class="alert alert-danger">
                            Bağlantı hatası oluştu.
                        </div>
                    `;
                });
        }
        

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && e.target.name === 'new_firm_name') {
                e.target.closest('form').submit();
            }
            
            if (e.key === 'Escape' && e.target.name === 'new_firm_name') {
                const firmId = e.target.closest('form').querySelector('input[name="firm_id"]').value;
                cancelEdit(firmId);
            }
        });
    </script>
</body>
</html>
