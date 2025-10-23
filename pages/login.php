<?php
require_once '../includes/auth.php';

$error = '';
$success = '';

if ($_POST) {
    $email = $_POST['email'] ?? '';
    $sifre = $_POST['sifre'] ?? '';
    
    if ($email && $sifre) {
        $result = loginUser($email, $sifre);
        if ($result['success']) {
            header('Location: /');
            exit;
        } else {
            $error = $result['message'];
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
    <title>Giriş Yap - Bilet Satış Platformu</title>
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
                        <h4>GİRİŞ YAP</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="form-floating mb-3">
                                <input type="email" class="form-control" id="email" name="email" placeholder="Email" required>
                                <label for="email">Email</label>
                            </div>
                            
                            <div class="form-floating mb-3">
                                <input type="password" class="form-control" id="sifre" name="sifre" placeholder="Şifre" required>
                                <label for="sifre">Şifre</label>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100 mb-3">Giriş Yap</button>
                        </form>
                        
                        <div class="text-center">
                            <p>Hesabınız yok mu? <a href="register.php">Kayıt olun</a></p>
                            <p><a href="/">Ana Sayfaya Dön</a></p>
                        </div>
                        
                        <hr>
                        <div class="text-center">
                            <small class="text-muted">
                                <strong>Test Hesapları:</strong><br>
                                Admin: admin@admin.com / 123456<br>
                                Firma: firma@firma.com / 123456<br>
                                Şoför: sofor1@metro.com / 123456<br>
                                User: user@user.com / 123456
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
