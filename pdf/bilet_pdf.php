<?php
require_once '../includes/auth.php';

requireLogin();

$ticketId = $_GET['id'] ?? 0;
$user = getCurrentUser();

if (!$ticketId) {
    die('Geçersiz bilet ID');
}


$stmt = $pdo->prepare("
    SELECT t.*, tr.kalkis, tr.varis, tr.tarih, tr.saat, f.ad as firma_ad, u.ad as yolcu_ad, u.email
    FROM tickets t
    JOIN trips tr ON t.trip_id = tr.id
    JOIN firms f ON tr.firma_id = f.id
    JOIN users u ON t.user_id = u.id
    WHERE t.id = ? AND t.user_id = ?
");
$stmt->execute([$ticketId, $user['id']]);
$ticket = $stmt->fetch();

if (!$ticket) {
    die('Bilet bulunamadı veya erişim yetkiniz yok');
}


$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Otobüs Bileti</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Roboto+Mono:wght@300;400;500;700&display=swap');
        body { font-family: 'Roboto Mono', monospace; margin: 0; padding: 20px; background: #ffffff; color: #000000; }
        .ticket { border: 3px solid #000000; border-radius: 0; padding: 30px; max-width: 600px; margin: 0 auto; background: #ffffff; }
        .header { text-align: center; border-bottom: 2px solid #000000; padding-bottom: 20px; margin-bottom: 20px; }
        .logo { font-size: 28px; font-weight: 700; color: #000000; letter-spacing: 3px; text-transform: uppercase; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .info-item { padding: 15px; background: #000000; color: #ffffff; border-radius: 0; }
        .info-label { font-weight: 700; color: #ffffff; font-size: 12px; text-transform: uppercase; letter-spacing: 1px; }
        .info-value { font-size: 18px; margin-top: 8px; font-weight: 500; }
        .route { text-align: center; font-size: 24px; font-weight: 700; margin: 25px 0; text-transform: uppercase; letter-spacing: 2px; }
        .qr-section { text-align: center; margin-top: 25px; padding-top: 20px; border-top: 2px solid #000000; }
        .footer { text-align: center; margin-top: 25px; font-size: 11px; color: #000000; font-weight: 500; text-transform: uppercase; }
    </style>
</head>
<body>
    <div class="ticket">
        <div class="header">
            <div class="logo">' . strtoupper(htmlspecialchars($ticket['firma_ad'])) . '</div>
            <div>OTOBÜS BİLETİ</div>
        </div>
        
        <div class="route">
            ' . htmlspecialchars($ticket['kalkis']) . ' → ' . htmlspecialchars($ticket['varis']) . '
        </div>
        
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">YOLCU ADI</div>
                <div class="info-value">' . htmlspecialchars($ticket['yolcu_ad']) . '</div>
            </div>
            
            <div class="info-item">
                <div class="info-label">BİLET NO</div>
                <div class="info-value">#' . str_pad($ticket['id'], 6, '0', STR_PAD_LEFT) . '</div>
            </div>
            
            <div class="info-item">
                <div class="info-label">TARİH</div>
                <div class="info-value">' . date('d.m.Y', strtotime($ticket['tarih'])) . '</div>
            </div>
            
            <div class="info-item">
                <div class="info-label">SAAT</div>
                <div class="info-value">' . date('H:i', strtotime($ticket['saat'])) . '</div>
            </div>
            
            <div class="info-item">
                <div class="info-label">KOLTUK NO</div>
                <div class="info-value">' . $ticket['koltuk_no'] . '</div>
            </div>
            
            <div class="info-item">
                <div class="info-label">FİYAT</div>
                <div class="info-value">₺' . number_format($ticket['fiyat'], 2) . '</div>
            </div>
        </div>';

if ($ticket['kupon_kodu']) {
    $html .= '
        <div class="info-grid" style="margin-top: 15px;">
            <div class="info-item">
                <div class="info-label">KUPON KODU</div>
                <div class="info-value">' . htmlspecialchars($ticket['kupon_kodu']) . '</div>
            </div>
        </div>';
}

$html .= '
        <div class="qr-section">
            <div style="font-size: 24px; font-weight: 700; letter-spacing: 2px;">QR KOD</div>
            <div style="margin-top: 10px; font-weight: 500;">KONTROL KODU</div>
            <div style="font-family: \'Roboto Mono\', monospace; margin-top: 15px; font-size: 14px; font-weight: 700; letter-spacing: 1px;">' . strtoupper(md5($ticket['id'] . $ticket['created_at'])) . '</div>
        </div>
        
        <div class="footer">
            <div>Bilet Oluşturma: ' . date('d.m.Y H:i', strtotime($ticket['created_at'])) . '</div>
            <div>Bu bilet kişiye özeldir ve devredilemez.</div>
            <div>Seyahat sırasında kimlik belgesi ile birlikte ibraz edilmelidir.</div>
        </div>
    </div>
</body>
</html>';


header('Content-Type: text/html; charset=UTF-8');
echo $html;



?>

<script>

window.onload = function() {
    if (confirm('Bileti PDF olarak kaydetmek ister misiniz?')) {
        window.print();
    }
}
</script>
