<?php
// 1. ŞABLONLARI ÇAĞIR
require 'header.php'; 
require 'sidebar.php'; 

// 2. GEREKLİ VERİLERİ ÇEK
if (!isset($_SESSION['hasta_id'])) {
    // Eğer oturum yoksa login'e at (Ekstra güvenlik)
    echo "<script>window.location.href='login.php';</script>";
    exit;
}
$hasta_id = $_SESSION['hasta_id'];

// Randevu İptal/Başarı Mesajı Kontrolü
$mesaj = '';
$mesaj_tipi = '';
if (isset($_GET['durum'])) {
    if ($_GET['durum'] == 'iptal_basarili') {
        $mesaj = "Randevu başarıyla iptal edildi.";
        $mesaj_tipi = 'basari';
    } elseif ($_GET['durum'] == 'basari') {
        $mesaj = "Randevunuz başarıyla oluşturuldu!";
        $mesaj_tipi = 'basari';
    }
}

// Mevcut Randevuları Listele
$stmt = $pdo->prepare("
    SELECT r.randevu_id, r.randevu_tarihi, r.durum, d.ad, d.soyad, d.uzmanlik_alani
    FROM randevular r
    JOIN doktorlar d ON r.doktor_id = d.doktor_id
    WHERE r.hasta_id = ?
    ORDER BY r.randevu_tarihi DESC
");
$stmt->execute([$hasta_id]);
$randevular = $stmt->fetchAll();
?>

<main class="app-content">

    <div style="text-align: center; padding: 40px; background: white; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-bottom: 40px;">
        <h3 style="margin-bottom: 10px;">Hemen Randevu Alın</h3>
        <p style="color: #666; margin-bottom: 20px;">Doktorlarımızın müsaitlik durumunu görmek ve randevu almak için tıklayın.</p>
        <a href="randevu_al.php" style="background-color: #007bff; color: white; padding: 12px 30px; text-decoration: none; border-radius: 50px; font-weight: bold; font-size: 1.1rem; box-shadow: 0 4px 10px rgba(0,123,255,0.3); transition: all 0.3s;">
            📅 Yeni Randevu Oluştur
        </a>
    </div>

    <?php if ($mesaj): ?>
        <div class="mesaj <?php echo $mesaj_tipi == 'basari' ? 'mesaj-basari' : 'mesaj-hata'; ?>">
            <?php echo htmlspecialchars($mesaj); ?>
        </div>
    <?php endif; ?>

    <h3 style="margin-bottom: 20px;">Mevcut Randevularınız</h3>

    <?php if (count($randevular) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Doktor</th>
                    <th>Uzmanlık Alanı</th>
                    <th>Randevu Tarihi</th>
                    <th>Durum</th>
                    <th>İşlem</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($randevular as $randevu): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($randevu['ad'] . ' ' . $randevu['soyad']); ?></td>
                        <td><?php echo htmlspecialchars($randevu['uzmanlik_alani']); ?></td>
                        <td><?php echo date('d.m.Y H:i', strtotime($randevu['randevu_tarihi'])); ?></td>
                        <td>
                            <span style="font-weight: bold; color: <?php echo $randevu['durum'] == 'Onaylandı' ? '#27ae60' : ($randevu['durum'] == 'Beklemede' ? '#f39c12' : '#c0392b'); ?>">
                                <?php echo htmlspecialchars($randevu['durum']); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($randevu['durum'] == 'Beklemede'): ?>
                                <form action="randevu_iptal.php" method="post" onsubmit="return confirm('Bu randevuyu iptal etmek istediğinize emin misiniz?');" style="margin:0;">
                                    <input type="hidden" name="randevu_id" value="<?php echo $randevu['randevu_id']; ?>">
                                    <button type="submit" class="btn-danger" style="font-size: 0.85rem; padding: 6px 12px; background-color: #dc3545; color: white; border: none; border-radius: 4px; cursor: pointer;">İptal Et</button>
                                </form>
                            <?php elseif ($randevu['durum'] == 'Onaylandı'): ?>
                                <a href="degerlendir.php?randevu_id=<?php echo $randevu['randevu_id']; ?>" 
                                   style="background-color: #f39c12; color: white; padding: 6px 12px; font-size: 0.85rem; text-decoration: none; border-radius: 4px; display: inline-block;">
                                   ⭐ Değerlendir
                                </a>
                            <?php else: ?>
                                <span style="color: #999;">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div style="background: white; padding: 20px; border-radius: 8px; text-align: center; color: #777; border: 1px dashed #ccc;">
            Henüz aktif bir randevunuz bulunmamaktadır.
        </div>
    <?php endif; ?>

</main>

<?php require 'footer.php'; ?>