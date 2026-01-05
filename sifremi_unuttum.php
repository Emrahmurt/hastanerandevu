<?php
session_start();
require 'config.php';

$adim = isset($_GET['adim']) ? $_GET['adim'] : 1;
$mesaj = '';
$mesaj_tipi = '';

// ADIM 1: E-POSTA KONTROLÜ
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['email_kontrol'])) {
    $email = $_POST['email'];
    $stmt = $pdo->prepare("SELECT * FROM hastalar WHERE email = ?");
    $stmt->execute([$email]);
    $uye = $stmt->fetch();

    if ($uye) {
        // Kullanıcı bulundu, bilgileri oturuma alıp 2. adıma geç
        $_SESSION['reset_id'] = $uye['hasta_id'];
        $_SESSION['reset_soru'] = $uye['guvenlik_sorusu'];
        header("Location: sifremi_unuttum.php?adim=2");
        exit;
    } else {
        $mesaj = "Bu e-posta adresiyle kayıtlı kullanıcı bulunamadı.";
        $mesaj_tipi = 'hata';
    }
}

// ADIM 2: CEVAP KONTROLÜ VE ŞİFRE DEĞİŞTİRME
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['sifre_degistir'])) {
    $cevap = mb_strtolower($_POST['cevap']); // Küçük harfe çevir
    $yeni_parola = $_POST['yeni_parola'];
    $hasta_id = $_SESSION['reset_id'];

    // Veritabanındaki cevabı çek
    $stmt = $pdo->prepare("SELECT guvenlik_cevabi FROM hastalar WHERE hasta_id = ?");
    $stmt->execute([$hasta_id]);
    $gercek_cevap_hash = $stmt->fetchColumn();

    // Cevap doğru mu?
    if (password_verify($cevap, $gercek_cevap_hash)) {
        // Şifreyi güncelle
        $yeni_hash = password_hash($yeni_parola, PASSWORD_DEFAULT);
        $upd = $pdo->prepare("UPDATE hastalar SET parola = ? WHERE hasta_id = ?");
        $upd->execute([$yeni_hash, $hasta_id]);

        $mesaj = "Şifreniz başarıyla değiştirildi! Giriş yapabilirsiniz.";
        $mesaj_tipi = 'basari';
        // Oturumu temizle
        session_unset();
        $adim = 3; // Başarı ekranı
    } else {
        $mesaj = "Güvenlik sorusunun cevabı yanlış!";
        $mesaj_tipi = 'hata';
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Şifremi Unuttum</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <h2>Şifremi Unuttum</h2>
        
        <?php if ($mesaj): ?>
            <div class="mesaj <?php echo $mesaj_tipi == 'basari' ? 'mesaj-basari' : 'mesaj-hata'; ?>">
                <?php echo htmlspecialchars($mesaj); ?>
            </div>
        <?php endif; ?>

        <?php if ($adim == 1): ?>
            <p>Lütfen hesabınıza ait e-posta adresinizi girin.</p>
            <form method="post">
                <label>E-posta:</label>
                <input type="email" name="email" required>
                <input type="submit" name="email_kontrol" value="Devam Et">
            </form>
            <p><a href="login.php">Giriş'e Dön</a></p>

        <?php elseif ($adim == 2): ?>
            <p>Lütfen güvenlik sorusunu cevaplayın.</p>
            <form method="post">
                <label style="color:#007bff;">Soru: <?php echo htmlspecialchars($_SESSION['reset_soru']); ?></label>
                
                <label>Cevabınız:</label>
                <input type="text" name="cevap" required placeholder="Yanıtınızı buraya yazın">
                
                <label>Yeni Parola:</label>
                <input type="password" name="yeni_parola" required placeholder="Yeni şifrenizi belirleyin">
                
                <input type="submit" name="sifre_degistir" value="Şifreyi Değiştir">
            </form>
            <p><a href="sifremi_unuttum.php">Geri Dön</a></p>

        <?php elseif ($adim == 3): ?>
            <p style="text-align:center;">
                <a href="login.php" style="background:#28a745; color:white; padding:10px 20px; text-decoration:none; border-radius:5px;">Giriş Yap</a>
            </p>
        <?php endif; ?>
        
    </div>
</body>
</html>