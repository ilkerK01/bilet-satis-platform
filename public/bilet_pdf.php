<?php
require_once '../includes/auth.php';

requireLogin();

$ticketId = $_GET['id'] ?? 0;
$user = getCurrentUser();

if (!$ticketId) {
    die('Geçersiz bilet ID');
}


$stmt = $pdo->prepare("
    SELECT t.*, tr.departure_city, tr.destination_city, tr.departure_time, tr.arrival_time, 
           bc.name as firma_ad, u.full_name as yolcu_ad, u.email, bs.seat_number
    FROM Tickets t
    JOIN Trips tr ON t.trip_id = tr.id
    JOIN Bus_Company bc ON tr.company_id = bc.id
    JOIN User u ON t.user_id = u.id
    LEFT JOIN Booked_Seats bs ON t.id = bs.ticket_id
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
        @import url(\'https://fonts.googleapis.com/css2?family=Roboto+Mono:wght@300;400;500;700&display=swap\');
        body { 
            font-family: \'Roboto Mono\', monospace; 
            margin: 0; 
            padding: 80px 20px 20px 20px; 
            background: #f5f5f5; 
            color: #000000; 
            min-height: 100vh;
        }
        .ticket { 
            border: 2px solid #000000; 
            border-radius: 0; 
            padding: 25px; 
            max-width: 650px; 
            margin: 0 auto; 
            background: #ffffff; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            position: relative;
            page-break-inside: avoid;
        }
        .ticket::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, #000000 25%, transparent 25%, transparent 50%, #000000 50%, #000000 75%, transparent 75%);
        }
        .header { 
            text-align: center; 
            border-bottom: 2px solid #000000; 
            padding-bottom: 15px; 
            margin-bottom: 20px; 
        }
        .logo { 
            font-size: 24px; 
            font-weight: 700; 
            color: #000000; 
            letter-spacing: 3px; 
            text-transform: uppercase; 
            margin-bottom: 8px;
        }
        .subtitle {
            font-size: 14px;
            font-weight: 500;
            color: #666666;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .info-grid { 
            display: grid; 
            grid-template-columns: 1fr 1fr; 
            gap: 15px; 
            margin-bottom: 15px;
        }
        .info-item { 
            padding: 12px; 
            background: #000000; 
            color: #ffffff; 
            border-radius: 0; 
            border: 1px solid #000000;
        }
        .info-label { 
            font-weight: 700; 
            color: #ffffff; 
            font-size: 10px; 
            text-transform: uppercase; 
            letter-spacing: 1px; 
            margin-bottom: 6px;
        }
        .info-value { 
            font-size: 14px; 
            font-weight: 500; 
            line-height: 1.2;
        }
        .route { 
            text-align: center; 
            font-size: 20px; 
            font-weight: 700; 
            margin: 20px 0; 
            text-transform: uppercase; 
            letter-spacing: 2px; 
            padding: 15px;
            background: #f8f8f8;
            border: 2px solid #000000;
        }
        .qr-section { 
            text-align: center; 
            margin-top: 20px; 
            padding-top: 15px; 
            border-top: 2px solid #000000; 
        }
        .control-code {
            font-family: \'Roboto Mono\', monospace; 
            margin-top: 10px; 
            font-size: 14px; 
            font-weight: 700; 
            letter-spacing: 1px;
            padding: 8px;
            background: #f0f0f0;
            border: 1px solid #ccc;
        }
        .footer { 
            text-align: center; 
            margin-top: 20px; 
            font-size: 9px; 
            color: #666666; 
            font-weight: 400; 
            line-height: 1.4;
        }
        .important-note {
            background: #fffacd;
            border: 1px solid #ffd700;
            padding: 10px;
            margin-top: 15px;
            text-align: center;
            font-weight: 600;
            font-size: 11px;
            color: #b8860b;
        }
        @media print {
            @page {
                size: A4;
                margin: 15mm;
            }
            
            body { 
                background: white; 
                margin: 0;
                padding: 0;
                font-size: 12px;
            }
            
            .ticket { 
                box-shadow: none; 
                transform: none !important;
                margin: 0;
                max-width: none;
                width: 100%;
                padding: 20px;
                border: 2px solid #000000;
                page-break-inside: avoid;
                height: auto;
                max-height: 250mm;
            }
            
            .header {
                margin-bottom: 15px;
                padding-bottom: 10px;
            }
            
            .logo {
                font-size: 20px;
                margin-bottom: 5px;
            }
            
            .subtitle {
                font-size: 12px;
            }
            
            .route {
                font-size: 18px;
                margin: 15px 0;
                padding: 12px;
            }
            
            .info-grid {
                gap: 10px;
                margin-bottom: 10px;
            }
            
            .info-item {
                padding: 10px;
            }
            
            .info-value {
                font-size: 12px;
            }
            
            .qr-section {
                margin-top: 15px;
                padding-top: 10px;
            }
            
            .control-code {
                font-size: 12px;
                padding: 6px;
                margin-top: 8px;
            }
            
            .footer {
                margin-top: 15px;
                font-size: 8px;
            }
            
            .important-note {
                padding: 8px;
                margin-top: 10px;
                font-size: 10px;
            }
            
            #pdfControls {
                display: none !important;
            }
        }
        
        
        #pdfControls button {
            transition: all 0.2s ease;
            font-family: \'Roboto Mono\', monospace;
            font-size: 12px;
        }
        
        #pdfControls button:hover {
            transform: scale(1.05);
            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
        }
        
        #pdfControls button:active {
            transform: scale(0.95);
        }
        
        
        .ticket {
            transition: transform 0.3s ease;
        }
    </style>
</head>
<body>
    <div class="ticket">
        <div class="header">
            <div class="logo">' . strtoupper(htmlspecialchars($ticket['firma_ad'])) . '</div>
            <div class="subtitle">OTOBÜS BİLETİ</div>
        </div>
        
        <div class="route">
            ' . htmlspecialchars($ticket['departure_city']) . ' → ' . htmlspecialchars($ticket['destination_city']) . '
        </div>
        
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">YOLCU ADI</div>
                <div class="info-value">' . htmlspecialchars($ticket['yolcu_ad']) . '</div>
            </div>
            
            <div class="info-item">
                <div class="info-label">BİLET NO</div>
                <div class="info-value">#' . strtoupper(substr($ticket['id'], 0, 8)) . '</div>
            </div>
            
            <div class="info-item">
                <div class="info-label">KALKIŞ TARİHİ</div>
                <div class="info-value">' . date('d.m.Y', strtotime($ticket['departure_time'])) . '</div>
            </div>
            
            <div class="info-item">
                <div class="info-label">KALKIŞ SAATİ</div>
                <div class="info-value">' . date('H:i', strtotime($ticket['departure_time'])) . '</div>
            </div>
            
            <div class="info-item">
                <div class="info-label">VARIŞ SAATİ</div>
                <div class="info-value">' . date('H:i', strtotime($ticket['arrival_time'])) . '</div>
            </div>
            
            <div class="info-item">
                <div class="info-label">KOLTUK NO</div>
                <div class="info-value">' . $ticket['seat_number'] . '</div>
            </div>
            
            <div class="info-item">
                <div class="info-label">ÖDENİLEN TUTAR</div>
                <div class="info-value">₺' . number_format($ticket['total_price'], 2) . '</div>
            </div>
        </div>';


$statusText = $ticket['status'] === 'active' ? 'AKTİF' : 'İPTAL EDİLDİ';
$statusColor = $ticket['status'] === 'active' ? '#000000' : '#ff0000';

$html .= '
        <div class="info-grid" style="margin-top: 15px;">
            <div class="info-item">
                <div class="info-label">BİLET DURUMU</div>
                <div class="info-value" style="color: ' . $statusColor . ';">' . $statusText . '</div>
            </div>
            
            <div class="info-item">
                <div class="info-label">YOLCU E-MAIL</div>
                <div class="info-value" style="font-size: 14px;">' . htmlspecialchars($ticket['email']) . '</div>
            </div>
        </div>';

$html .= '
        <div class="qr-section">
            <div style="font-size: 20px; font-weight: 700; letter-spacing: 2px; margin-bottom: 15px;">BİLET KONTROL KODU</div>
            <div class="control-code">' . strtoupper(substr(md5($ticket['id'] . $ticket['created_at']), 0, 16)) . '</div>
        </div>
        
        <div class="important-note">
            ⚠️ Bu bilet kişiye özeldir ve devredilemez. Seyahat sırasında kimlik belgesi ile birlikte ibraz edilmelidir.
        </div>
        
        <div class="footer">
            <div><strong>Bilet Oluşturma Tarihi:</strong> ' . date('d.m.Y H:i', strtotime($ticket['created_at'])) . '</div>
            <div style="margin-top: 10px;">Bu bilet elektronik ortamda oluşturulmuştur ve geçerlidir.</div>
            <div>Bilet iptal ve değişiklik işlemleri için müşteri hizmetleri ile iletişime geçiniz.</div>
            <div style="margin-top: 10px; font-weight: 600;">İyi yolculuklar dileriz!</div>
        </div>
    </div>
</body>
</html>';


header('Content-Type: text/html; charset=UTF-8');
echo $html;



?>


<div id="pdfControls" style="position: fixed; top: 20px; right: 20px; z-index: 1000; background: rgba(0,0,0,0.9); padding: 15px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.3);">
    <div style="display: flex; gap: 10px; align-items: center;">
        <button onclick="zoomOut()" style="background: #ffffff; color: #000000; border: none; padding: 8px 12px; border-radius: 4px; cursor: pointer; font-weight: 600;">
            🔍-
        </button>
        <span id="zoomLevel" style="color: #ffffff; font-weight: 600; min-width: 50px; text-align: center;">100%</span>
        <button onclick="zoomIn()" style="background: #ffffff; color: #000000; border: none; padding: 8px 12px; border-radius: 4px; cursor: pointer; font-weight: 600;">
            🔍+
        </button>
        <div style="width: 1px; height: 30px; background: #333; margin: 0 5px;"></div>
        <button onclick="downloadPDF()" style="background: #28a745; color: #ffffff; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer; font-weight: 600;">
            📥 İNDİR
        </button>
        <button onclick="printPDF()" style="background: #007bff; color: #ffffff; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer; font-weight: 600;">
            🖨️ YAZDIR
        </button>
        <button onclick="closePDF()" style="background: #dc3545; color: #ffffff; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer; font-weight: 600;">
            ✕ KAPAT
        </button>
    </div>
</div>

<script>
let currentZoom = 100;

function zoomIn() {
    if (currentZoom < 200) {
        currentZoom += 25;
        applyZoom();
    }
}

function zoomOut() {
    if (currentZoom > 50) {
        currentZoom -= 25;
        applyZoom();
    }
}

function applyZoom() {
    const ticket = document.querySelector('.ticket');
    const body = document.body;
    
    ticket.style.transform = `scale(${currentZoom / 100})`;
    ticket.style.transformOrigin = 'top center';
    

    const scaledHeight = ticket.offsetHeight * (currentZoom / 100);
    body.style.minHeight = scaledHeight + 200 + 'px';
    
    document.getElementById('zoomLevel').textContent = currentZoom + '%';
}

function downloadPDF() {

    if (confirm('PDF olarak indirmek için tarayıcınızın yazdırma menüsünden "PDF olarak kaydet" seçeneğini kullanın.\n\nYazdırma dialogunu açmak ister misiniz?')) {
        window.print();
    }
}

function printPDF() {
    window.print();
}

function closePDF() {
    if (confirm('Bilet sayfasını kapatmak istediğinizden emin misiniz?')) {
        window.close();

        setTimeout(() => {
            window.history.back();
        }, 100);
    }
}


document.addEventListener('keydown', function(e) {
    if (e.ctrlKey || e.metaKey) {
        switch(e.key) {
            case '+':
            case '=':
                e.preventDefault();
                zoomIn();
                break;
            case '-':
                e.preventDefault();
                zoomOut();
                break;
            case 'p':
                e.preventDefault();
                printPDF();
                break;
            case 's':
                e.preventDefault();
                downloadPDF();
                break;
        }
    }
    
    if (e.key === 'Escape') {
        closePDF();
    }
});


window.onload = function() {

    const controls = document.getElementById('pdfControls');
    controls.style.opacity = '0';
    controls.style.transition = 'opacity 0.5s ease';
    
    setTimeout(() => {
        controls.style.opacity = '1';
    }, 500);
    

    applyZoom();
}


window.addEventListener('beforeprint', function() {

    document.getElementById('pdfControls').style.display = 'none';

    document.querySelector('.ticket').style.transform = 'scale(1)';
});

window.addEventListener('afterprint', function() {

    document.getElementById('pdfControls').style.display = 'block';

    applyZoom();
});
</script>
