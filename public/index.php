<?php
require_once '../includes/db.php';
require_once '../includes/session.php';

function generateDynamicTrips($fromCity, $toCity, $date) {
    global $pdo;
    $cityCoords = [
        'Adana' => ['lat' => 37.0000, 'lng' => 35.3213],
        'Adıyaman' => ['lat' => 37.7648, 'lng' => 38.2786],
        'Afyonkarahisar' => ['lat' => 38.7507, 'lng' => 30.5567],
        'Ağrı' => ['lat' => 39.7191, 'lng' => 43.0503],
        'Aksaray' => ['lat' => 38.3687, 'lng' => 34.0370],
        'Amasya' => ['lat' => 40.6499, 'lng' => 35.8353],
        'Ankara' => ['lat' => 39.9334, 'lng' => 32.8597],
        'Antalya' => ['lat' => 36.8969, 'lng' => 30.7133],
        'Ardahan' => ['lat' => 41.1105, 'lng' => 42.7022],
        'Artvin' => ['lat' => 41.1828, 'lng' => 41.8183],
        'Aydın' => ['lat' => 37.8560, 'lng' => 27.8416],
        'Balıkesir' => ['lat' => 39.6484, 'lng' => 27.8826],
        'Bartın' => ['lat' => 41.5811, 'lng' => 32.4610],
        'Batman' => ['lat' => 37.8812, 'lng' => 41.1351],
        'Bayburt' => ['lat' => 40.2552, 'lng' => 40.2249],
        'Bilecik' => ['lat' => 40.0567, 'lng' => 30.0665],
        'Bingöl' => ['lat' => 38.8854, 'lng' => 40.7696],
        'Bitlis' => ['lat' => 38.3938, 'lng' => 42.1232],
        'Bolu' => ['lat' => 40.5760, 'lng' => 31.5788],
        'Burdur' => ['lat' => 37.4613, 'lng' => 30.0665],
        'Bursa' => ['lat' => 40.1826, 'lng' => 29.0665],
        'Çanakkale' => ['lat' => 40.1553, 'lng' => 26.4142],
        'Çankırı' => ['lat' => 40.6013, 'lng' => 33.6134],
        'Çorum' => ['lat' => 40.5506, 'lng' => 34.9556],
        'Denizli' => ['lat' => 37.7765, 'lng' => 29.0864],
        'Diyarbakır' => ['lat' => 37.9144, 'lng' => 40.2306],
        'Düzce' => ['lat' => 40.8438, 'lng' => 31.1565],
        'Edirne' => ['lat' => 41.6818, 'lng' => 26.5623],
        'Elazığ' => ['lat' => 38.6810, 'lng' => 39.2264],
        'Erzincan' => ['lat' => 39.7500, 'lng' => 39.5000],
        'Erzurum' => ['lat' => 39.9334, 'lng' => 41.2769],
        'Eskişehir' => ['lat' => 39.7767, 'lng' => 30.5206],
        'Gaziantep' => ['lat' => 37.0662, 'lng' => 37.3833],
        'Giresun' => ['lat' => 40.9128, 'lng' => 38.3895],
        'Gümüşhane' => ['lat' => 40.4386, 'lng' => 39.5086],
        'Hakkari' => ['lat' => 37.5744, 'lng' => 43.7408],
        'Hatay' => ['lat' => 36.4018, 'lng' => 36.3498],
        'Iğdır' => ['lat' => 39.8880, 'lng' => 44.0048],
        'Isparta' => ['lat' => 37.7648, 'lng' => 30.5566],
        'İstanbul' => ['lat' => 41.0082, 'lng' => 28.9784],
        'İzmir' => ['lat' => 38.4192, 'lng' => 27.1287],
        'Kahramanmaraş' => ['lat' => 37.5858, 'lng' => 36.9371],
        'Karabük' => ['lat' => 41.2061, 'lng' => 32.6204],
        'Karaman' => ['lat' => 37.1759, 'lng' => 33.2287],
        'Kars' => ['lat' => 40.6013, 'lng' => 43.0975],
        'Kastamonu' => ['lat' => 41.3887, 'lng' => 33.7827],
        'Kayseri' => ['lat' => 38.7312, 'lng' => 35.4787],
        'Kırıkkale' => ['lat' => 39.8468, 'lng' => 33.5153],
        'Kırklareli' => ['lat' => 41.7333, 'lng' => 27.2167],
        'Kırşehir' => ['lat' => 39.1425, 'lng' => 34.1709],
        'Kilis' => ['lat' => 36.7184, 'lng' => 37.1212],
        'Kocaeli' => ['lat' => 40.8533, 'lng' => 29.8815],
        'Konya' => ['lat' => 37.8667, 'lng' => 32.4833],
        'Kütahya' => ['lat' => 39.4242, 'lng' => 29.9833],
        'Malatya' => ['lat' => 38.3552, 'lng' => 38.3095],
        'Manisa' => ['lat' => 38.6191, 'lng' => 27.4289],
        'Mardin' => ['lat' => 37.3212, 'lng' => 40.7245],
        'Mersin' => ['lat' => 36.8000, 'lng' => 34.6333],
        'Muğla' => ['lat' => 37.2153, 'lng' => 28.3636],
        'Muş' => ['lat' => 38.9462, 'lng' => 41.7539],
        'Nevşehir' => ['lat' => 38.6939, 'lng' => 34.6857],
        'Niğde' => ['lat' => 37.9667, 'lng' => 34.6833],
        'Ordu' => ['lat' => 40.9839, 'lng' => 37.8764],
        'Osmaniye' => ['lat' => 37.2130, 'lng' => 36.1763],
        'Rize' => ['lat' => 41.0201, 'lng' => 40.5234],
        'Sakarya' => ['lat' => 40.6940, 'lng' => 30.4358],
        'Samsun' => ['lat' => 41.2928, 'lng' => 36.3313],
        'Siirt' => ['lat' => 37.9333, 'lng' => 41.9500],
        'Sinop' => ['lat' => 42.0231, 'lng' => 35.1531],
        'Sivas' => ['lat' => 39.7477, 'lng' => 37.0179],
        'Şanlıurfa' => ['lat' => 37.1591, 'lng' => 38.7969],
        'Şırnak' => ['lat' => 37.4187, 'lng' => 42.4918],
        'Tekirdağ' => ['lat' => 40.9833, 'lng' => 27.5167],
        'Tokat' => ['lat' => 40.3167, 'lng' => 36.5500],
        'Trabzon' => ['lat' => 41.0015, 'lng' => 39.7178],
        'Tunceli' => ['lat' => 39.3074, 'lng' => 39.4388],
        'Uşak' => ['lat' => 38.6823, 'lng' => 29.4082],
        'Van' => ['lat' => 38.4891, 'lng' => 43.4089],
        'Yalova' => ['lat' => 40.6500, 'lng' => 29.2667],
        'Yozgat' => ['lat' => 39.8181, 'lng' => 34.8147],
        'Zonguldak' => ['lat' => 41.4564, 'lng' => 31.7987]
    ];
    
    if (!isset($cityCoords[$fromCity]) || !isset($cityCoords[$toCity])) {
        return [];
    }
    
    $fromCoords = $cityCoords[$fromCity];
    $toCoords = $cityCoords[$toCity];
    $distance = sqrt(
        pow($fromCoords['lat'] - $toCoords['lat'], 2) + 
        pow($fromCoords['lng'] - $toCoords['lng'], 2)
    ) * 111;
    
    $basePrice = max(60, $distance * 3.5);
    $basePrice = min($basePrice, 450);
    
    global $pdo;
    $stmt = $pdo->query("SELECT id, name FROM Bus_Company");
    $firms = [];
    while ($row = $stmt->fetch()) {
        $firms[$row['id']] = $row['name'];
    }
    
    $times = ['08:00', '12:00', '16:00', '20:00'];
    $dynamicTrips = [];
    for ($i = 0; $i < 3; $i++) {
        $firmId = array_rand($firms);
        $firmName = $firms[$firmId];
        $time = $times[$i % count($times)];
        
        $price = $basePrice;
        if (in_array($time, ['20:00'])) {
            $price *= 0.9;
        }
        
        $dynamicTrips[] = [
            'id' => 'dynamic_' . $i,
            'company_id' => $firmId,
            'firma_ad' => $firmName,
            'departure_city' => $fromCity,
            'destination_city' => $toCity,
            'departure_time' => $date . ' ' . $time . ':00',
            'price' => round($price, 2),
            'capacity' => 40
        ];
    }
    
    return $dynamicTrips;
}

$kalkis = $_GET['kalkis'] ?? '';
$varis = $_GET['varis'] ?? '';
$tarih = $_GET['tarih'] ?? date('Y-m-d');

$trips = [];
$searchPerformed = false;
if ($kalkis || $varis) {
    $searchPerformed = true;
    
    $whereClause = "WHERE 1=1";
    $params = [];

    if ($kalkis) {
        $whereClause .= " AND departure_city LIKE ?";
        $params[] = "%$kalkis%";
    }

    if ($varis) {
        $whereClause .= " AND destination_city LIKE ?";
        $params[] = "%$varis%";
    }

    if ($tarih) {
        $whereClause .= " AND DATE(departure_time) = ?";
        $params[] = $tarih;
    }

    $stmt = $pdo->prepare("
        SELECT t.*, bc.name as firma_ad 
        FROM Trips t 
        JOIN Bus_Company bc ON t.company_id = bc.id 
        $whereClause 
        ORDER BY t.departure_time
    ");
    $stmt->execute($params);
    $trips = $stmt->fetchAll();

    if (empty($trips) && $kalkis && $varis && $kalkis !== $varis) {
        $trips = generateDynamicTrips($kalkis, $varis, $tarih);
    }
}
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
    <title>Bilet Satış Platformu</title>
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
                    <?php if ($user): ?>
                        <div class="navbar-user-info me-3">
                            <span class="user-welcome">
                                Hoş geldin, <?= htmlspecialchars($user['full_name']) ?>
                            </span>
                            <span class="user-balance">
                                Kredi: ₺<?= number_format($user['balance'], 2) ?>
                            </span>
                        </div>
                        
                        <div class="navbar-buttons">
                            <?php if ($user['role'] === 'admin'): ?>
                                <a class="nav-btn" href="admin.php">Admin Panel</a>
                            <?php elseif ($user['role'] === 'company'): ?>
                                <a class="nav-btn" href="firma_admin.php">Firma Panel</a>
                            <?php else: ?>
                                <a class="nav-btn" href="biletlerim.php">Biletlerim</a>
                            <?php endif; ?>
                            
                            <a class="nav-btn nav-btn-logout" href="logout.php">Çıkış</a>
                        </div>
                    <?php else: ?>
                        <div class="navbar-buttons">
                            <a class="nav-btn" href="login.php">Giriş</a>
                            <a class="nav-btn nav-btn-register" href="register.php">Kayıt</a>
                        </div>
                    <?php endif; ?>
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

        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Sefer Ara</h5>
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Kalkış</label>
                        <select name="kalkis" class="form-select" required>
                            <option value="">Kalkış şehri seçin</option>
                            <?php foreach ($cities as $city): ?>
                                <option value="<?= $city ?>" <?= $kalkis === $city ? 'selected' : '' ?>>
                                    <?= $city ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Varış</label>
                        <select name="varis" class="form-select" required>
                            <option value="">Varış şehri seçin</option>
                            <?php foreach ($cities as $city): ?>
                                <option value="<?= $city ?>" <?= $varis === $city ? 'selected' : '' ?>>
                                    <?= $city ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Tarih</label>
                        <input type="date" name="tarih" class="form-control" value="<?= $tarih ?>" min="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary d-block w-100">Ara</button>
                    </div>
                </form>
            </div>
        </div>


        <div class="row">
            <?php if (!$searchPerformed): ?>
                <div class="col-12">
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <h4 class="mb-4">Sefer Arama</h4>
                            <p class="text-muted mb-4">Seyahat etmek istediğiniz kalkış ve varış noktalarını seçerek sefer arayabilirsiniz.</p>
                            <div class="row justify-content-center">
                                <div class="col-md-8">
                                    <div class="d-flex align-items-center justify-content-center gap-3">
                                        <div class="text-center">
                                            <div style="font-size: 2rem;">🚌</div>
                                            <small>Konforlu Yolculuk</small>
                                        </div>
                                        <div class="text-center">
                                            <div style="font-size: 2rem;">⚡</div>
                                            <small>Hızlı Rezervasyon</small>
                                        </div>
                                        <div class="text-center">
                                            <div style="font-size: 2rem;">💳</div>
                                            <small>Güvenli Ödeme</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php elseif (empty($trips)): ?>
                <div class="col-12">
                    <div class="alert alert-warning">
                        <h5>Sefer Bulunamadı</h5>
                        <p>Aradığınız kriterlere uygun sefer bulunamadı. Lütfen farklı tarih veya güzergah deneyin.</p>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($trips as $trip): ?>
                    <div class="col-md-6 mb-3">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($trip['firma_ad']) ?></h5>
                                <p class="card-text">
                                    <strong><?= htmlspecialchars($trip['departure_city']) ?></strong> → 
                                    <strong><?= htmlspecialchars($trip['destination_city']) ?></strong>
                                </p>
                                <p class="card-text">
                                    <strong>TARİH:</strong> <?= date('d.m.Y', strtotime($trip['departure_time'])) ?><br>
                                    <strong>SAAT:</strong> <?= date('H:i', strtotime($trip['departure_time'])) ?>
                                </p>
                                <p class="card-text">
                                    <span class="badge bg-success">₺<?= number_format($trip['price'], 2) ?></span>
                                </p>
                                <?php if (strpos($trip['id'], 'dynamic_') === 0): ?>
                                    <a href="sefer_detay.php?id=<?= $trip['id'] ?>&from=<?= urlencode($trip['departure_city']) ?>&to=<?= urlencode($trip['destination_city']) ?>&date=<?= date('Y-m-d', strtotime($trip['departure_time'])) ?>&time=<?= date('H:i', strtotime($trip['departure_time'])) ?>&price=<?= $trip['price'] ?>&company=<?= urlencode($trip['firma_ad']) ?>" class="btn btn-primary">
                                        DETAY GÖR
                                    </a>
                                <?php else: ?>
                                    <a href="sefer_detay.php?id=<?= $trip['id'] ?>" class="btn btn-primary">
                                        DETAY GÖR
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.querySelector('form').addEventListener('submit', function(e) {
            const kalkis = document.querySelector('select[name="kalkis"]').value;
            const varis = document.querySelector('select[name="varis"]').value;
            
            if (!kalkis || !varis) {
                e.preventDefault();
                alert('Lütfen kalkış ve varış şehirlerini seçin.');
                return false;
            }
            
            if (kalkis === varis) {
                e.preventDefault();
                alert('Kalkış ve varış şehirleri aynı olamaz.');
                return false;
            }
        });
        
        document.querySelector('select[name="kalkis"]').addEventListener('change', function() {
            const varisSelect = document.querySelector('select[name="varis"]');
            const selectedKalkis = this.value;
            
            Array.from(varisSelect.options).forEach(option => {
                if (option.value === selectedKalkis && option.value !== '') {
                    option.style.display = 'none';
                } else {
                    option.style.display = 'block';
                }
            });
            
            if (varisSelect.value === selectedKalkis) {
                varisSelect.value = '';
            }
        });
        
        document.querySelector('select[name="varis"]').addEventListener('change', function() {
            const kalkisSelect = document.querySelector('select[name="kalkis"]');
            const selectedVaris = this.value;
            
            Array.from(kalkisSelect.options).forEach(option => {
                if (option.value === selectedVaris && option.value !== '') {
                    option.style.display = 'none';
                } else {
                    option.style.display = 'block';
                }
            });
            
            if (kalkisSelect.value === selectedVaris) {
                kalkisSelect.value = '';
            }
        });
    </script>
</body>
</html>
