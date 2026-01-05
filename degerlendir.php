<?php
require 'header.php';
require 'sidebar.php';

// Güvenlik kontrolleri
if (!isset($_GET['randevu_id'])) { header("Location: index.php"); exit; }
$randevu_id = $_GET['randevu_id'];
$hasta_id = $_SESSION['hasta_id'];

$mesaj = '';

// 1. DEĞERLENDİRME KAYDETME
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $puan = $_POST['puan'];
    $yorum = $_POST['yorum'];
    $doktor_id = $_POST['doktor_id'];

    try {
        $stmt = $pdo->prepare("INSERT INTO degerlendirmeler (randevu_id, doktor_id, hasta_id, puan, yorum) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$randevu_id, $doktor_id, $hasta_id, $puan, $yorum]);
        $mesaj = "Değerlendirmeniz alındı. Teşekkürler!";
        // İşlem bitince butonları gizlemek için bayrak
        $islem_bitti = true;
    } catch (PDOException $e) {
        $mesaj = "Hata: Bu randevuyu zaten değerlendirdiniz.";
    }
}

// 2. RANDEVU BİLGİLERİNİ ÇEK (Doktor adını göstermek için)
$stmt = $pdo->prepare("SELECT r.*, d.ad, d.soyad FROM randevular r 
                       JOIN doktorlar d ON r.doktor_id = d.doktor_id 
                       WHERE r.randevu_id = ? AND r.hasta_id = ?");
$stmt->execute([$randevu_id, $hasta_id]);
$randevu = $stmt->fetch();

// Eğer randevu yoksa veya başkasınınsa ana sayfaya at
if (!$randevu) { header("Location: index.php"); exit; }
?>

<main class="app-content">
    <h3>Doktor Değerlendir</h3>

    <?php if ($mesaj): ?>
        <div class="mesaj <?php echo isset($islem_bitti) ? 'mesaj-basari' : 'mesaj-hata'; ?>">
            <?php echo htmlspecialchars($mesaj); ?>
        </div>
        <?php if (isset($islem_bitti)): ?>
            <a href="index.php">« Randevularıma Dön</a>
        <?php endif; ?>
    <?php endif; ?>

    <?php if (!isset($islem_bitti)): ?>
        <div style="background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); max-width: 600px;">
            <h4 style="border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 20px;">
                👨‍⚕️ Dr. <?php echo htmlspecialchars($randevu['ad'] . ' ' . $randevu['soyad']); ?>
            </h4>
            <p style="color:#777; font-size:0.9rem;">
                <?php echo date('d.m.Y H:i', strtotime($randevu['randevu_tarihi'])); ?> tarihli randevunuz nasıldı?
            </p>

            <form method="post">
                <input type="hidden" name="doktor_id" value="<?php echo $randevu['doktor_id']; ?>">
                
                <label>Puanınız:</label>
                <div style="margin-bottom: 20px;">
                    <select name="puan" required style="padding: 10px; width: 100px; border: 1px solid #ddd; border-radius: 5px;">
                        <option value="5">⭐⭐⭐⭐⭐ (5 - Çok İyi)</option>
                        <option value="4">⭐⭐⭐⭐ (4 - İyi)</option>
                        <option value="3">⭐⭐⭐ (3 - Orta)</option>
                        <option value="2">⭐⭐ (2 - Kötü)</option>
                        <option value="1">⭐ (1 - Çok Kötü)</option>
                    </select>
                </div>

                <label>Yorumunuz:</label>
                <textarea name="yorum" rows="4" placeholder="Deneyiminizi buraya yazabilirsiniz..." style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; margin-bottom: 20px;"></textarea>

                <button type="submit" style="background-color: #28a745; color: white; padding: 12px 25px;">Değerlendirmeyi Gönder</button>
            </form>
        </div>
    <?php endif; ?>

</main>

<?php require 'footer.php'; ?>