<?php
session_start();
require 'config.php';

// Güvenlik: Doktor değilse at
if (!isset($_SESSION['doktor_id'])) { header("Location: doktor_login.php"); exit; }

$doktor_id = $_SESSION['doktor_id'];

// YORUMLARI ÇEK
$sql = "SELECT d.*, h.ad, h.soyad FROM degerlendirmeler d 
        JOIN hastalar h ON d.hasta_id = h.hasta_id 
        WHERE d.doktor_id = ? ORDER BY d.tarih DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$doktor_id]);
$yorumlar = $stmt->fetchAll();

// ORTALAMA PUANI HESAPLA
$ortalama = 0;
if (count($yorumlar) > 0) {
    $toplam_puan = array_sum(array_column($yorumlar, 'puan'));
    $ortalama = round($toplam_puan / count($yorumlar), 1);
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Hasta Yorumları</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <div class="app-container">
        <header class="app-header" style="border-bottom: 2px solid #dc3545;">
            <div class="logo" style="color: #dc3545;">Doktor Paneli</div>
            <div class="user-info">
                <a href="doktor_panel.php" style="margin-right:15px; color:#555;">« Randevular</a>
                <strong><?php echo htmlspecialchars($_SESSION['doktor_ad']); ?></strong>
                <a href="logout.php" class="header-cikis-btn" style="background-color:#333;">Çıkış</a>
            </div>
        </header>

        <main class="app-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3>Hasta Değerlendirmeleri</h3>
                <div style="background: #f39c12; color: white; padding: 10px 20px; border-radius: 20px; font-weight: bold;">
                    Ortalama Puan: <?php echo $ortalama; ?> / 5.0 ⭐
                </div>
            </div>

            <?php if (count($yorumlar) > 0): ?>
                <?php foreach ($yorumlar as $y): ?>
                    <div style="background: white; padding: 20px; border-radius: 8px; margin-bottom: 15px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                            <div>
                                <strong style="font-size: 1.1rem; color: #2c3e50;"><?php echo htmlspecialchars($y['ad'] . ' ' . $y['soyad']); ?></strong>
                                <span style="color:#f1c40f; margin-left: 10px;">
                                    <?php echo str_repeat('⭐', $y['puan']); ?>
                                </span>
                            </div>
                            <small style="color:#999;"><?php echo date('d.m.Y', strtotime($y['tarih'])); ?></small>
                        </div>
                        <p style="margin: 0; color: #555; font-style: italic;">
                            "<?php echo nl2br(htmlspecialchars($y['yorum'])); ?>"
                        </p>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Henüz bir değerlendirme almadınız.</p>
            <?php endif; ?>
        </main>
    </div>

</body>
</html>