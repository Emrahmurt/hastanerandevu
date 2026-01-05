<?php
session_start();
require 'config.php';

$mesaj = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $kullanici_adi = $_POST['kullanici_adi'];
    $parola = $_POST['parola'];

    $stmt = $pdo->prepare("SELECT * FROM yoneticiler WHERE kullanici_adi = ?");
    $stmt->execute([$kullanici_adi]);
    $yonetici = $stmt->fetch();

    if ($yonetici && password_verify($parola, $yonetici['parola'])) {
        $_SESSION['yonetici_id'] = $yonetici['yonetici_id'];
        $_SESSION['yonetici_adi'] = $yonetici['kullanici_adi'];
        
        header("Location: admin_panel.php");
        exit;
    } else {
        $mesaj = "Hatalı kullanıcı adı veya parola!";
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Yönetici Girişi</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="auth-page" style="background-color: #2c3e50;"> <div class="auth-container">
        <h2 style="color: #2c3e50;">Yönetici Paneli</h2>
        
        <?php if ($mesaj): ?>
            <div class="mesaj mesaj-hata"><?php echo htmlspecialchars($mesaj); ?></div>
        <?php endif; ?>

        <form action="admin_login.php" method="post">
            <label>Kullanıcı Adı:</label>
            <input type="text" name="kullanici_adi" required>
            
            <label>Parola:</label>
            <input type="password" name="parola" required>
            
            <input type="submit" value="Panele Gir" style="background-color: #2c3e50;">
        </form>
        <p style="text-align:center; margin-top:15px;"><a href="index.php" style="color:#fff;">Ana Sayfaya Dön</a></p>
    </div>

</body>
</html>