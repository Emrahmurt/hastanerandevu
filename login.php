<?php
session_start();
require 'config.php';

$mesaj = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Formdan gelen veri 'kullanici_girisi' olarak alınıyor
    $kullanici_girisi = $_POST['kullanici_girisi']; 
    $parola_girdisi = $_POST['parola'];

    try {
        // Hem E-posta hem de TC Kimlik No sütununda arama yapıyoruz
        $stmt = $pdo->prepare("SELECT * FROM hastalar WHERE email = ? OR tc_no = ?");
        $stmt->execute([$kullanici_girisi, $kullanici_girisi]);
        $hasta = $stmt->fetch();

        if ($hasta && password_verify($parola_girdisi, $hasta['parola'])) {
            $_SESSION['hasta_id'] = $hasta['hasta_id'];
            $_SESSION['ad'] = $hasta['ad'];
            $_SESSION['profil_resmi'] = $hasta['profil_resmi'];
            
            header("Location: index.php");
            exit;
        } else {
            $mesaj = "Hatalı TC Kimlik No, E-posta veya Parola!";
        }
    } catch (PDOException $e) {
        $mesaj = "Veritabanı hatası: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasta Giriş</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="auth-page" style="background-color: gray;">
    <div class="auth-container">
        <h2 style="text-align:center; margin-bottom: 30px;">Hasta Giriş</h2>
        
        <?php if ($mesaj): ?>
            <div class="mesaj mesaj-hata">
                <?php echo htmlspecialchars($mesaj); ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="post">
            <input type="text" 
                   id="kullanici_girisi" 
                   name="kullanici_girisi" 
                   placeholder="TC Kimlik No (11 Haneli) veya E-posta" 
                   style="padding: 15px;" 
                   required
                   oninput="if(!isNaN(this.value) && this.value.length > 11) this.value = this.value.slice(0, 11);">
            
            <input type="password" id="parola" name="parola" placeholder="Parola" style="padding: 15px;" required>
            
            <input type="submit" value="Giriş Yap" style="margin-top: 10px;">
        </form>
        
        <p style="margin-top: 20px; text-align: center;">
            Hesabınız yok mu? <a href="kayit.php">Kayıt Olun</a>
        </p>

        <div style="margin-top: 30px; text-align: center; border-top: 1px solid #eee; padding-top: 15px; display: flex; justify-content: center; gap: 15px;">
            <a href="admin_login.php" style="color: #7f8c8d; font-size: 0.9rem; text-decoration: none; font-weight: bold;">
                🔒 Yönetici Girişi
            </a>
            <span style="color: #ddd;">|</span>
            <a href="doktor_login.php" style="color: #dc3545; font-size: 0.9rem; text-decoration: none; font-weight: bold;">
                🩺 Doktor Girişi
            </a>
        </div>
    </div>

    <script src="script.js"></script>
</body>
</html>