<?php
require_once '../includes/auth.php';

$error = '';
$success = '';

if ($_POST) {
    $ad = $_POST['ad'] ?? '';
    $email = $_POST['email'] ?? '';
    $sifre = $_POST['sifre'] ?? '';
    $sifre_tekrar = $_POST['sifre_tekrar'] ?? '';
    
    if ($ad && $email && $sifre && $sifre_tekrar) {
        if ($sifre !== $sifre_tekrar) {
            $error = 'Şifreler eşleşmiyor.';
        } elseif (strlen($sifre) < 6) {
            $error = 'Şifre en az 6 karakter olmalıdır.';
        } else {
            $result = registerUser($ad, $email, $sifre);
            if ($result['success']) {
                $success = $result['message'];
            } else {
                $error = $result['message'];
            }
        }
    } else {
        $error = 'Lütfen tüm alanları doldurun.';
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kayıt Ol - Bilet Satış Platformu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header text-center">
                        <div class="login-logo mb-3">
                            <img src="../public/images/logo.svg" alt="Logo" class="login-logo-img">
                        </div>
                        <h4>KAYIT OL</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                <?= htmlspecialchars($success) ?>
                                <br><a href="login.php">Giriş yapmak için tıklayın</a>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" id="ad" name="ad" placeholder="Ad Soyad" required>
                                <label for="ad">Ad Soyad</label>
                            </div>
                            
                            <div class="form-floating mb-3">
                                <input type="email" class="form-control" id="email" name="email" placeholder="Email" required>
                                <label for="email">Email</label>
                            </div>
                            
                            <div class="form-floating mb-3">
                                <input type="password" class="form-control" id="sifre" name="sifre" placeholder="Şifre" required minlength="6">
                                <label for="sifre">Şifre (en az 6 karakter)</label>
                            </div>
                            
                            <div class="form-floating mb-3">
                                <input type="password" class="form-control" id="sifre_tekrar" name="sifre_tekrar" placeholder="Şifre Tekrar" required>
                                <label for="sifre_tekrar">Şifre Tekrar</label>
                            </div>
                            
                            <button type="submit" class="btn btn-success w-100 mb-3">Kayıt Ol</button>
                        </form>
                        
                        <div class="text-center">
                            <p>Zaten hesabınız var mı? <a href="login.php">Giriş yapın</a></p>
                            <p><a href="/">Ana Sayfaya Dön</a></p>
                        </div>
                        
                        <div class="alert alert-info">
                            <small>
                                <strong>Kayıt Bonusu:</strong> Yeni üyelere 100₺ kredi hediye!
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
