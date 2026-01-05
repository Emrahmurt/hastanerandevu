<?php
session_start();
require 'config.php';

$mesaj = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $parola = $_POST['parola'];

    $stmt = $pdo->prepare("SELECT * FROM doktorlar WHERE email = ?");
    $stmt->execute([$email]);
    $doktor = $stmt->fetch();

    if ($doktor && password_verify($parola, $doktor['parola'])) {
        // Doktor oturumu başlat
        $_SESSION['doktor_id'] = $doktor['doktor_id'];
        $_SESSION['doktor_ad'] = $doktor['ad'] . ' ' . $doktor['soyad'];
        
        header("Location: doktor_panel.php");
        exit;
    } else {
        $mesaj = "Hatalı e-posta veya parola!";
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Doktor Girişi</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="auth-page" style="background-color: #e9ecef;"> <div class="auth-container">
        <h2 style="color: #dc3545;">Doktor Girişi</h2> <?php if ($mesaj): ?>
            <div class="mesaj mesaj-hata"><?php echo htmlspecialchars($mesaj); ?></div>
        <?php endif; ?>

        <form action="doktor_login.php" method="post">
            <label>Kurumsal E-posta:</label>
            <input type="email" name="email" required placeholder="ad@hastane.com">
            
            <label>Parola:</label>
            <input type="password" name="parola" required>
            
            <input type="submit" value="Giriş Yap" style="background-color: #dc3545;">
        </form>
        <p style="text-align:center; margin-top:15px;"><a href="login.php">Hasta Girişine Dön</a></p>
    </div>

</body>
</html>