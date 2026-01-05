<?php
session_start();
require 'config.php';

// Güvenlik: Giriş yapan DOKTOR mu?
if (!isset($_SESSION['doktor_id'])) {
    header("Location: doktor_login.php");
    exit;
}

$doktor_id = $_SESSION['doktor_id'];

// Randevu Durumunu Güncelleme
if (isset($_GET['islem']) && isset($_GET['id'])) {
    $yeni_durum = $_GET['islem'] == 'onayla' ? 'Onaylandı' : 'İptal Edildi';
    $randevu_id = $_GET['id'];
    
    // İşlem sonrası sayfada kalması için tarih bilgisini koruyalım
    $redirect_tarih = isset($_GET['tarih']) ? "&tarih=" . $_GET['tarih'] : "";
    
    $stmt = $pdo->prepare("UPDATE randevular SET durum = ? WHERE randevu_id = ? AND doktor_id = ?");
    $stmt->execute([$yeni_durum, $randevu_id, $doktor_id]);
    
    header("Location: doktor_panel.php?islem_sonuc=1" . $redirect_tarih);
    exit;
}

// FİLTRELEME MANTIĞI
// Eğer URL'de tarih varsa onu al, yoksa 'BUGÜN'ü varsayılan yap.
// Eğer 'tumu' seçildiyse tarih filtresini kaldır.
$filtre_tarih = isset($_GET['tarih']) ? $_GET['tarih'] : date('Y-m-d');
$tumu_goster = (isset($_GET['mod']) && $_GET['mod'] == 'tumu');

if ($tumu_goster) {
    // Tüm randevuları getir (Tarih kısıtlaması yok)
    $sql = "SELECT r.*, h.ad AS hasta_ad, h.soyad AS hasta_soyad, h.tc_no 
            FROM randevular r 
            JOIN hastalar h ON r.hasta_id = h.hasta_id 
            WHERE r.doktor_id = ? 
            ORDER BY r.randevu_tarihi DESC";
    $params = [$doktor_id];
    $baslik_tarih = "Tüm Zamanlar";
} else {
    // Sadece seçilen günü getir
    $sql = "SELECT r.*, h.ad AS hasta_ad, h.soyad AS hasta_soyad, h.tc_no 
            FROM randevular r 
            JOIN hastalar h ON r.hasta_id = h.hasta_id 
            WHERE r.doktor_id = ? AND DATE(r.randevu_tarihi) = ?
            ORDER BY r.randevu_tarihi ASC"; // Gün içindeki randevular saate göre sıralansın
    $params = [$doktor_id, $filtre_tarih];
    $baslik_tarih = date('d.m.Y', strtotime($filtre_tarih));
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$randevular = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Doktor Paneli</title>
    <link rel="stylesheet" href="style.css">
    <style> .container { max-width: 1000px; } </style>
</head>
<body>

    <div class="app-container">
        <header class="app-header" style="border-bottom: 2px solid #dc3545;">
            <div class="logo" style="color: #dc3545; font-weight:bold;">Doktor Paneli</div>
           <div class="user-info">
            Sayın <strong><?php echo htmlspecialchars($_SESSION['doktor_ad']); ?></strong>
            
            <a href="doktor_yorumlar.php" style="margin-right:10px; margin-left:15px; text-decoration:none; font-weight:bold; color:#f39c12;">⭐ Yorumlarım</a>
            
            <a href="doktor_profil.php" style="margin-right:10px; text-decoration:none; font-weight:bold; color:#dc3545;">Profilim</a>
            
            <a href="logout.php" class="header-cikis-btn" style="background-color:#333;">Çıkış</a>
        </div>
        </header>

        <main class="app-content">
            
            <div style="background: white; padding: 15px; border-radius: 8px; margin-bottom: 20px; display: flex; gap: 10px; align-items: center; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
                <strong style="color:#555;">Tarih Seç:</strong>
                <form method="get" style="margin:0; display:flex; gap:10px;">
                    <input type="date" name="tarih" value="<?php echo $tumu_goster ? date('Y-m-d') : $filtre_tarih; ?>" onchange="this.form.submit()" style="padding: 5px 10px; border: 1px solid #ddd; border-radius: 4px; margin:0;">
                </form>
                
                <span style="color:#ccc;">|</span>
                
                <a href="doktor_panel.php" style="text-decoration:none; font-weight:bold; color: #007bff;">Bugün</a>
                <a href="doktor_panel.php?tarih=<?php echo date('Y-m-d', strtotime('+1 day')); ?>" style="text-decoration:none; color: #555;">Yarın</a>
                <a href="doktor_panel.php?mod=tumu" style="text-decoration:none; color: #555;">Tümünü Göster</a>
            </div>

            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 15px;">
                <h3 style="margin:0;">Randevu Listesi <span style="font-size:0.8em; color:#777;">(<?php echo $baslik_tarih; ?>)</span></h3>
                <button onclick="window.print()" style="background:#f1c40f; color:#333; display:flex; align-items:center; gap:5px; border:none; padding: 8px 15px; border-radius: 5px; font-weight:bold; cursor:pointer;">
                    🖨️ Listeyi Yazdır
                </button>
            </div>

            <?php if (count($randevular) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Saat</th>
                            <th>Hasta Adı Soyadı</th>
                            <th>TC Kimlik No</th>
                            <th>Durum</th>
                            <th>İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($randevular as $randevu): ?>
                            <tr>
                                <td>
                                    <?php 
                                        // Eğer tümünü gösteriyorsak Tarih+Saat, yoksa sadece Saat gösterelim
                                        echo $tumu_goster ? date('d.m.Y H:i', strtotime($randevu['randevu_tarihi'])) : date('H:i', strtotime($randevu['randevu_tarihi'])); 
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($randevu['hasta_ad'] . ' ' . $randevu['hasta_soyad']); ?></td>
                                <td><?php echo htmlspecialchars($randevu['tc_no']); ?></td>
                                
                                <td style="font-weight:bold; color: <?php echo $randevu['durum']=='Onaylandı'?'green':($randevu['durum']=='Beklemede'?'orange':'red'); ?>">
                                    <?php echo htmlspecialchars($randevu['durum']); ?>
                                </td>
                                
                                <td>
                                    <?php if($randevu['durum'] == 'Beklemede'): ?>
                                        <a href="?islem=onayla&id=<?php echo $randevu['randevu_id']; ?>&tarih=<?php echo $filtre_tarih; ?>" style="color:green; margin-right:10px; text-decoration:none; font-weight:bold;">✔ Onayla</a>
                                        <a href="?islem=iptal&id=<?php echo $randevu['randevu_id']; ?>&tarih=<?php echo $filtre_tarih; ?>" style="color:red; text-decoration:none; font-weight:bold;" onclick="return confirm('Reddetmek istiyor musunuz?');">✖ Reddet</a>
                                    <?php else: ?>
                                        <span style="color:#ccc;">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="mesaj" style="background: white; border: 1px dashed #ccc; color: #777;">
                    Bu tarih için (<?php echo $baslik_tarih; ?>) kayıtlı randevu bulunmamaktadır.
                </div>
            <?php endif; ?>
        </main>
    </div>

</body>
</html>