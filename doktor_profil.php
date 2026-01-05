<?php
session_start();
require 'config.php';

// Güvenlik: Doktor değilse at
if (!isset($_SESSION['doktor_id'])) {
    header("Location: doktor_login.php");
    exit;
}

$doktor_id = $_SESSION['doktor_id'];
$mesaj = '';

// GÜNCELLEME İŞLEMİ
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $ad = $_POST['ad'];
    $soyad = $_POST['soyad'];
    $email = $_POST['email'];
    
    // Şifre alanı doluysa güncelle
    if (!empty($_POST['yeni_parola'])) {
        $parola_hash = password_hash($_POST['yeni_parola'], PASSWORD_DEFAULT);
        $sql = "UPDATE doktorlar SET ad=?, soyad=?, email=?, parola=? WHERE doktor_id=?";
        $params = [$ad, $soyad, $email, $parola_hash, $doktor_id];
    } else {
        $sql = "UPDATE doktorlar SET ad=?, soyad=?, email=? WHERE doktor_id=?";
        $params = [$ad, $soyad, $email, $doktor_id];
    }

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $mesaj = "Bilgileriniz başarıyla güncellendi!";
        $_SESSION['doktor_ad'] = $ad . ' ' . $soyad; // Header'daki ismi güncelle
    } catch (PDOException $e) {
        $mesaj = "Hata: " . $e->getMessage();
    }
}

// MEVCUT BİLGİLERİ ÇEK
$stmt = $pdo->prepare("SELECT * FROM doktorlar WHERE doktor_id = ?");
$stmt->execute([$doktor_id]);
$dr = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Doktor Profili</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <div class="app-container">
        <header class="app-header" style="border-bottom: 2px solid #dc3545;">
            <div class="logo" style="color: #dc3545; font-weight:bold;">Doktor Paneli</div>
            <div class="user-info">
                Sayın <strong><?php echo htmlspecialchars($_SESSION['doktor_ad']); ?></strong>
                <a href="doktor_panel.php" style="margin-left:15px; text-decoration:none; color:#555;">« Randevular</a>
                <a href="logout.php" class="header-cikis-btn" style="background-color:#333;">Çıkış</a>
            </div>
        </header>

        <main class="app-content">
            <h3>Profil ve Şifre İşlemleri</h3>
            
            <?php if ($mesaj): ?>
                <div class="mesaj <?php echo strpos($mesaj, 'Hata') !== false ? 'mesaj-hata' : 'mesaj-basari'; ?>">
                    <?php echo htmlspecialchars($mesaj); ?>
                </div>
            <?php endif; ?>

            <form method="post" style="background: white; padding: 30px; border-radius: 8px; max-width: 600px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
                <label>Ad:</label>
                <input type="text" name="ad" value="<?php echo htmlspecialchars($dr['ad']); ?>" required>

                <label>Soyad:</label>
                <input type="text" name="soyad" value="<?php echo htmlspecialchars($dr['soyad']); ?>" required>

                <label>E-posta (Giriş için):</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($dr['email']); ?>" required>

                <hr style="margin: 20px 0; border:0; border-top:1px solid #ddd;">
                
                <label>Yeni Parola (Değiştirmek istemiyorsanız boş bırakın):</label>
                <input type="password" name="yeni_parola" placeholder="Yeni parolanız...">

                <button type="submit" style="background-color: #dc3545; color: white; width: 100%; margin-top: 10px;">Bilgileri Güncelle</button>
            </form>
        </main>
    </div>

</body>
</html>