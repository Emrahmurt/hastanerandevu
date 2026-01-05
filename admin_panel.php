<?php
session_start();
require 'config.php';

// Güvenlik: Admin değilse at
if (!isset($_SESSION['yonetici_id'])) {
    header("Location: admin_login.php");
    exit;
}

$mesaj = '';

// 1. RANDEVU İŞLEMLERİ (YENİ EKLENDİ)
if (isset($_GET['islem']) && isset($_GET['id'])) {
    $yeni_durum = $_GET['islem'] == 'onayla' ? 'Onaylandı' : 'İptal Edildi';
    $randevu_id = $_GET['id'];
    
    try {
        $stmt = $pdo->prepare("UPDATE randevular SET durum = ? WHERE randevu_id = ?");
        $stmt->execute([$yeni_durum, $randevu_id]);
        $mesaj = "Randevu durumu güncellendi: $yeni_durum";
    } catch (PDOException $e) {
        $mesaj = "Hata: " . $e->getMessage();
    }
}

// 2. DOKTOR SİLME İŞLEMİ
if (isset($_GET['sil_id'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM doktorlar WHERE doktor_id = ?");
        $stmt->execute([$_GET['sil_id']]);
        $mesaj = "Doktor başarıyla silindi.";
    } catch (PDOException $e) {
        $mesaj = "Hata: Bu doktorun randevuları olduğu için silinemiyor.";
    }
}

// 3. DOKTOR EKLEME İŞLEMİ
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['doktor_ekle'])) {
    $ad = $_POST['ad'];
    $soyad = $_POST['soyad'];
    $brans = $_POST['brans'];
    
    $email = strtolower($ad) . '.' . rand(100,999) . '@hastane.com';
    $parola_hash = '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm';

    try {
        $sql = "INSERT INTO doktorlar (ad, soyad, uzmanlik_alani, email, parola) VALUES (?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$ad, $soyad, $brans, $email, $parola_hash]);
        $mesaj = "Yeni doktor eklendi! Email: $email";
    } catch (PDOException $e) {
        $mesaj = "Hata: " . $e->getMessage();
    }
}

// GENEL İSTATİSTİKLER
$toplam_hasta = $pdo->query("SELECT COUNT(*) FROM hastalar")->fetchColumn();
$toplam_randevu = $pdo->query("SELECT COUNT(*) FROM randevular")->fetchColumn();

// DOKTORLARI ÇEK (Arama varsa filtrele)
$arama_terimi = isset($_GET['ara']) ? $_GET['ara'] : '';
if ($arama_terimi) {
    $stmt = $pdo->prepare("SELECT * FROM doktorlar WHERE ad LIKE ? OR soyad LIKE ? OR uzmanlik_alani LIKE ?");
    $stmt->execute(["%$arama_terimi%", "%$arama_terimi%", "%$arama_terimi%"]);
    $doktorlar = $stmt->fetchAll();
} else {
    $doktorlar = $pdo->query("SELECT * FROM doktorlar")->fetchAll();
}

// 4. TÜM RANDEVULARI ÇEK (YENİ EKLENDİ)
// Hem hasta hem doktor bilgilerini birleştirerek (JOIN) çekiyoruz
$randevu_sql = "SELECT r.*, h.ad AS hasta_ad, h.soyad AS hasta_soyad, d.ad AS doktor_ad, d.soyad AS doktor_soyad 
                FROM randevular r 
                JOIN hastalar h ON r.hasta_id = h.hasta_id 
                JOIN doktorlar d ON r.doktor_id = d.doktor_id 
                ORDER BY r.randevu_tarihi DESC LIMIT 50"; // Son 50 randevu
$tum_randevular = $pdo->query($randevu_sql)->fetchAll();

// GRAFİK VERİLERİ
$grafik_sql = "SELECT d.ad, d.soyad, COUNT(r.randevu_id) as sayi 
               FROM doktorlar d 
               LEFT JOIN randevular r ON d.doktor_id = r.doktor_id 
               GROUP BY d.doktor_id";
$grafik_veri = $pdo->query($grafik_sql)->fetchAll();
$doktor_isimleri = [];
$randevu_sayilari = [];
foreach ($grafik_veri as $veri) {
    $doktor_isimleri[] = $veri['ad'] . ' ' . $veri['soyad'];
    $randevu_sayilari[] = $veri['sayi'];
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Yönetici Paneli</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .admin-header { background: #2c3e50; color: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; }
        .admin-content { padding: 30px; max-width: 1100px; margin: 0 auto; }
        .stats-container { display: flex; gap: 20px; margin-bottom: 30px; }
        .stat-card { flex: 1; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); text-align: center; }
        .stat-number { font-size: 2.5rem; font-weight: bold; color: #3498db; margin-bottom: 5px; }
        .chart-container { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-bottom: 30px; }
        .add-doctor-box { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-bottom: 30px; }
        .form-row { display: flex; gap: 15px; align-items: flex-end; }
        .form-group { flex: 1; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; color: #2c3e50; }
        .form-group input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        
        /* Tablo Stilleri */
        table { width: 100%; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.05); border-collapse: collapse; margin-bottom: 30px; }
        table th { background: #34495e; color: white; padding: 15px; text-align: left; }
        table td { padding: 15px; border-bottom: 1px solid #ecf0f1; }
        
        /* Durum Renkleri */
        .durum-beklemede { color: #f39c12; font-weight: bold; }
        .durum-onaylandi { color: #27ae60; font-weight: bold; }
        .durum-iptal { color: #c0392b; font-weight: bold; }
    </style>
</head>
<body style="background-color: #ecf0f1; display: block;">

    <div class="admin-header">
        <div style="font-size:1.4rem; font-weight:bold;">YÖNETİCİ PANELİ</div>
        <div>
            Merhaba, Admin 
            <a href="logout.php" style="color:#e74c3c; margin-left:15px; text-decoration:none; background:white; padding:6px 15px; border-radius:20px; font-weight:bold; font-size:0.9rem;">Çıkış</a>
        </div>
    </div>

    <div class="admin-content">
        <a href="admin_mesajlar.php" style="background:#f39c12; color:white; padding:10px 20px; text-decoration:none; border-radius:5px; font-weight:bold; display:inline-block; margin-bottom:20px; margin-right:10px;">📩 Mesajları Oku</a>

<a href="admin_duyurular.php" style="background:#3498db; color:white; padding:10px 20px; text-decoration:none; border-radius:5px; font-weight:bold; display:inline-block; margin-bottom:20px;">📢 Duyuru Yönetimi</a>
        
        <div class="stats-container">
            <div class="stat-card"><div class="stat-number"><?php echo $toplam_hasta; ?></div><div>Kayıtlı Hasta</div></div>
            <div class="stat-card"><div class="stat-number"><?php echo count($doktorlar); ?></div><div>Görevli Doktor</div></div>
            <div class="stat-card"><div class="stat-number"><?php echo $toplam_randevu; ?></div><div>Toplam Randevu</div></div>
        </div>

        <div class="chart-container">
            <h3 style="border-bottom:1px solid #eee; padding-bottom:10px; margin-bottom:20px;">Doktor Performans Grafiği</h3>
            <canvas id="randevuGrafigi" style="max-height: 300px;"></canvas>
        </div>

        <?php if ($mesaj): ?>
            <div class="mesaj <?php echo strpos($mesaj, 'Hata') !== false ? 'mesaj-hata' : 'mesaj-basari'; ?>">
                <?php echo htmlspecialchars($mesaj); ?>
            </div>
        <?php endif; ?>

        <div class="add-doctor-box">
            <h3 style="margin-bottom:15px;">Yeni Doktor Ekle</h3>
            <form method="post" class="form-row">
                <div class="form-group"><label>Ad</label><input type="text" name="ad" required></div>
                <div class="form-group"><label>Soyad</label><input type="text" name="soyad" required></div>
                <div class="form-group"><label>Uzmanlık</label><input type="text" name="brans" required></div>
                <button type="submit" name="doktor_ekle" style="background: #27ae60; color: white; height: 42px; padding: 0 25px; border:none; border-radius:5px; font-weight:bold; cursor:pointer;">+ Ekle</button>
            </form>
        </div>

        <h3 style="margin-bottom:15px;">Son Randevular ve Yönetim</h3>
        <table>
            <thead>
                <tr>
                    <th>Tarih/Saat</th>
                    <th>Hasta Adı</th>
                    <th>Doktor Adı</th>
                    <th>Durum</th>
                    <th>İşlem</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tum_randevular as $randevu): ?>
                    <tr>
                        <td><?php echo date('d.m.Y H:i', strtotime($randevu['randevu_tarihi'])); ?></td>
                        <td><?php echo htmlspecialchars($randevu['hasta_ad'] . ' ' . $randevu['hasta_soyad']); ?></td>
                        <td><?php echo htmlspecialchars($randevu['doktor_ad'] . ' ' . $randevu['doktor_soyad']); ?></td>
                        <td class="<?php echo 'durum-' . strtolower(str_replace(' ', '', $randevu['durum'] == 'İptal Edildi' ? 'iptal' : ($randevu['durum'] == 'Onaylandı' ? 'onaylandi' : 'beklemede'))); ?>">
                            <?php echo htmlspecialchars($randevu['durum']); ?>
                        </td>
                        <td>
                            <?php if($randevu['durum'] == 'Beklemede'): ?>
                                <a href="?islem=onayla&id=<?php echo $randevu['randevu_id']; ?>" style="color:green; text-decoration:none; font-weight:bold; margin-right:10px;">✔ Onayla</a>
                                <a href="?islem=iptal&id=<?php echo $randevu['randevu_id']; ?>" style="color:red; text-decoration:none; font-weight:bold;" onclick="return confirm('Bu randevuyu iptal etmek istediğinize emin misiniz?');">✖ İptal</a>
                            <?php else: ?>
                                <span style="color:#999;">İşlem Yapıldı</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; margin-top:40px;">
            <h3>Doktor Listesi</h3>
            <form method="get" style="margin:0; display:flex; gap:5px;">
                <input type="text" name="ara" placeholder="Doktor ara..." value="<?php echo htmlspecialchars($arama_terimi); ?>" style="padding:8px; border:1px solid #ddd; border-radius:5px;">
                <button type="submit" style="background:#3498db; color:white; border:none; padding:8px 15px; border-radius:5px; cursor:pointer;">Ara</button>
            </form>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Ad Soyad</th>
                    <th>Uzmanlık</th>
                    <th>E-posta</th>
                    <th>İşlem</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($doktorlar as $dr): ?>
                    <tr>
                        <td>#<?php echo $dr['doktor_id']; ?></td>
                        <td><?php echo htmlspecialchars($dr['ad'] . ' ' . $dr['soyad']); ?></td>
                        <td><?php echo htmlspecialchars($dr['uzmanlik_alani']); ?></td>
                        <td><?php echo htmlspecialchars($dr['email']); ?></td>
                        <td>
                            <a href="?sil_id=<?php echo $dr['doktor_id']; ?>" 
                               onclick="return confirm('Bu doktoru silmek istediğinize emin misiniz?');"
                               style="color: #c0392b; font-weight: bold; text-decoration:none;">Sil 🗑️</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

    </div>

    <script>
        const ctx = document.getElementById('randevuGrafigi').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($doktor_isimleri); ?>,
                datasets: [{
                    label: 'Randevu Sayısı',
                    data: <?php echo json_encode($randevu_sayilari); ?>,
                    backgroundColor: 'rgba(52, 152, 219, 0.6)',
                    borderColor: 'rgba(52, 152, 219, 1)',
                    borderWidth: 1
                }]
            },
            options: { responsive: true, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
        });
    </script>

</body>
</html>