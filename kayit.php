<?php
require 'config.php';

$mesaj = '';
$mesaj_tipi = ''; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $ad = $_POST['ad'];
    $soyad = $_POST['soyad'];
    $email = $_POST['email'];
    $tc_no = $_POST['tc_no'];
    $parola = password_hash($_POST['parola'], PASSWORD_DEFAULT);
    
    // YENİ ALANLAR
    $guvenlik_sorusu = $_POST['guvenlik_sorusu'];
    $guvenlik_cevabi = password_hash(mb_strtolower($_POST['guvenlik_cevabi']), PASSWORD_DEFAULT);

    try {
        $sql = "INSERT INTO hastalar (ad, soyad, email, tc_no, parola, guvenlik_sorusu, guvenlik_cevabi) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$ad, $soyad, $email, $tc_no, $parola, $guvenlik_sorusu, $guvenlik_cevabi]);
        
        $mesaj = "Kayıt başarılı! Lütfen giriş yapın.";
        $mesaj_tipi = 'basari';
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), '1062') !== false) {
             $mesaj = "Hata: Bu E-posta veya TC Kimlik Numarası zaten kayıtlı.";
        } else {
             $mesaj = "Hata: " . $e->getMessage();
        }
        $mesaj_tipi = 'hata';
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasta Kayıt</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <h2>Hasta Kayıt Formu</h2>
        
        <?php if ($mesaj): ?>
            <div class="mesaj <?php echo $mesaj_tipi == 'basari' ? 'mesaj-basari' : 'mesaj-hata'; ?>">
                <?php echo htmlspecialchars($mesaj); ?>
            </div>
        <?php endif; ?>

        <form action="kayit.php" method="post">
            <label>Ad:</label>
            <input type="text" name="ad" required>
            
            <label>Soyad:</label>
            <input type="text" name="soyad" required>
            
            <label>Email:</label>
            <input type="email" name="email" required>
            
            <label>TC Kimlik No:</label>
            <input type="text" name="tc_no" 
                   required 
                   placeholder="11 haneli TCKN" 
                   maxlength="11" 
                   minlength="11" 
                   pattern="\d{11}" 
                   title="TC Kimlik Numarası 11 haneli olmalı ve sadece rakam içermelidir."
                   oninput="this.value = this.value.replace(/[^0-9]/g, '')"> <label>Parola:</label>
            <input type="password" name="parola" required>

            <label style="color:#d35400; font-weight:bold;">Güvenlik Sorusu (Şifre Kurtarma İçin):</label>
            <select name="guvenlik_sorusu" required>
                <option value="">-- Bir soru seçin --</option>
                <option value="İlkokul öğretmeninizin adı?">İlkokul öğretmeninizin adı?</option>
                <option value="İlk evcil hayvanınızın adı?">İlk evcil hayvanınızın adı?</option>
                <option value="En sevdiğiniz yemek?">En sevdiğiniz yemek?</option>
                <option value="Doğduğunuz şehir?">Doğduğunuz şehir?</option>
            </select>

            <input type="text" name="guvenlik_cevabi" placeholder="Cevabınız..." required>
            
            <input type="submit" value="Kayıt Ol">
        </form>
        <p>Zaten bir hesabınız var mı? <a href="login.php">Giriş Yapın</a></p>
    </div>
    
    <script src="script.js"></script>
</body>
</html>