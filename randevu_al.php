<?php
require 'header.php';
require 'sidebar.php';

// 1. BRANŞLARI (POLİKLİNİKLERİ) ÇEK (Tekrarsız)
$branslar = $pdo->query("SELECT DISTINCT uzmanlik_alani FROM doktorlar ORDER BY uzmanlik_alani ASC")->fetchAll(PDO::FETCH_COLUMN);

// SEÇİMLERİ AL
$secilen_brans = isset($_GET['brans']) ? $_GET['brans'] : null;
$secilen_doktor = isset($_GET['doktor_id']) ? $_GET['doktor_id'] : null;
$secilen_tarih = isset($_GET['tarih']) ? $_GET['tarih'] : date('Y-m-d');

// 2. DOKTORLARI FİLTRELE
// Eğer branş seçildiyse sadece o branşın doktorlarını getir, yoksa boş gelsin (veya hepsi)
$doktorlar = [];
if ($secilen_brans) {
    $stmt = $pdo->prepare("SELECT * FROM doktorlar WHERE uzmanlik_alani = ? ORDER BY ad ASC");
    $stmt->execute([$secilen_brans]);
    $doktorlar = $stmt->fetchAll();
}

// SAATLERİ OLUŞTURMA FONKSİYONU
function saatleri_olustur() {
    $saatler = [];
    $baslangic = strtotime('09:00'); 
    $bitis = strtotime('17:00');     
    $aralik = 30 * 60;               

    while ($baslangic < $bitis) {
        $saat_str = date('H:i', $baslangic);
        if ($saat_str != '12:30' && $saat_str != '13:00') { // Öğle arası
            $saatler[] = $saat_str;
        }
        $baslangic += $aralik;
    }
    return $saatler;
}

// 3. DOLU SAATLERİ BUL
$dolu_saatler = [];
if ($secilen_doktor && $secilen_tarih) {
    $sql = "SELECT DATE_FORMAT(randevu_tarihi, '%H:%i') as saat FROM randevular 
            WHERE doktor_id = ? AND DATE(randevu_tarihi) = ? AND durum != 'İptal Edildi'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$secilen_doktor, $secilen_tarih]);
    $dolu_saatler = $stmt->fetchAll(PDO::FETCH_COLUMN);
}
// RANDEVU KAYDETME İŞLEMİ
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['saat_sec'])) {
    $randevu_tam_tarih = $_POST['tarih'] . ' ' . $_POST['saat'];
    $doktor_id = $_POST['doktor_id'];
    $hasta_id = $_SESSION['hasta_id'];

    // 1. HAFTA SONU KONTROLÜ (YENİ)
    // date('N') fonksiyonu haftanın gününü verir (1=Pazartesi, ..., 6=Cumartesi, 7=Pazar)
    if (date('N', strtotime($_POST['tarih'])) >= 6) {
        echo "<script>alert('Hafta sonları (Cumartesi/Pazar) polikliniklerimiz kapalıdır. Lütfen hafta içi bir gün seçiniz.'); window.location.href='randevu_al.php';</script>";
        exit;
    }

    // 2. GEÇMİŞ ZAMAN KONTROLÜ
    if (strtotime($randevu_tam_tarih) < time()) {
        echo "<script>alert('Geçmiş bir zamana randevu alamazsınız!'); window.location.href='randevu_al.php';</script>";
        exit;
    }

    // ... (Kodun geri kalanı aynı devam eder: Çifte kontrol ve Kayıt) ...

    // Çifte rezervasyon kontrolü
    $check = $pdo->prepare("SELECT count(*) FROM randevular WHERE doktor_id=? AND randevu_tarihi=? AND durum!='İptal Edildi'");
    $check->execute([$doktor_id, $randevu_tam_tarih]);
    
    if ($check->fetchColumn() > 0) {
        echo "<script>alert('Üzgünüz, bu saat az önce doldu!');</script>";
    } else {
        $stmt = $pdo->prepare("INSERT INTO randevular (hasta_id, doktor_id, randevu_tarihi) VALUES (?, ?, ?)");
        $stmt->execute([$hasta_id, $doktor_id, $randevu_tam_tarih]);
        echo "<script>window.location.href='index.php?durum=basarili';</script>";
        exit;
    }
}
?>

<main class="app-content">
    <h3>Randevu Al</h3>
    
    <form method="get" style="background: white; padding: 25px; border-radius: 8px; margin-bottom: 20px; display: flex; flex-wrap: wrap; gap: 20px; align-items: flex-end; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
        
        <div style="flex: 1; min-width: 200px;">
            <label style="margin-bottom: 8px; display:block; font-weight:600;">Poliklinik (Branş):</label>
            <select name="brans" onchange="this.form.submit()" required style="margin-bottom: 0; width:100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                <option value="">-- Önce Poliklinik Seçin --</option>
                <?php foreach ($branslar as $brans): ?>
                    <option value="<?php echo htmlspecialchars($brans); ?>" <?php echo $secilen_brans == $brans ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($brans); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="flex: 1; min-width: 200px;">
            <label style="margin-bottom: 8px; display:block; font-weight:600;">Doktor Seçin:</label>
            <select name="doktor_id" onchange="this.form.submit()" required style="margin-bottom: 0; width:100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                <?php if ($secilen_brans): ?>
                    <option value="">-- Doktor Seçiniz --</option>
                    <?php foreach ($doktorlar as $dr): ?>
                        <option value="<?php echo $dr['doktor_id']; ?>" <?php echo $secilen_doktor == $dr['doktor_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($dr['ad'] . ' ' . $dr['soyad']); ?>
                        </option>
                    <?php endforeach; ?>
                <?php else: ?>
                    <option value="">Lütfen önce branş seçiniz</option>
                <?php endif; ?>
            </select>
        </div>

        <div style="flex: 1; min-width: 200px;">
            <label style="margin-bottom: 8px; display:block; font-weight:600;">Tarih Seçin:</label>
            <input type="date" name="tarih" value="<?php echo $secilen_tarih; ?>" min="<?php echo date('Y-m-d'); ?>" onchange="this.form.submit()" required style="margin-bottom: 0; width:100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
        </div>

        <div>
            <button type="submit" style="height: 45px; background-color: #007bff; color: white; padding: 0 30px; border-radius: 5px; font-weight: bold; border:none; cursor:pointer; transition: background 0.2s;">Getir</button>
        </div>
    </form>

    <?php if ($secilen_doktor): ?>
        <div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
            <h4 style="margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 10px;">Müsait Saatler (<?php echo date('d.m.Y', strtotime($secilen_tarih)); ?>)</h4>
            <div style="display: flex; flex-wrap: wrap; gap: 12px; margin-top: 15px;">
                <?php 
                $tum_saatler = saatleri_olustur();
                foreach ($tum_saatler as $saat): 
                    $dolu_mu = in_array($saat, $dolu_saatler);
                    $gecmis_mi = (strtotime("$secilen_tarih $saat") < time());
                    
                    $class = "saat-btn";
                    $disabled = "";
                    if ($dolu_mu) { $class .= " dolu"; $disabled = "disabled"; $text = "$saat (Dolu)"; }
                    elseif ($gecmis_mi) { $class .= " gecmis"; $disabled = "disabled"; $text = "$saat"; }
                    else { $class .= " bos"; $text = "$saat"; }
                ?>
                    <form method="post" style="margin:0;">
                        <input type="hidden" name="doktor_id" value="<?php echo $secilen_doktor; ?>">
                        <input type="hidden" name="tarih" value="<?php echo $secilen_tarih; ?>">
                        <input type="hidden" name="saat" value="<?php echo $saat; ?>">
                        <button type="submit" name="saat_sec" class="<?php echo $class; ?>" <?php echo $disabled; ?> onclick="return confirm('<?php echo $saat; ?> saati için randevuyu onaylıyor musunuz?');">
                            <?php echo $text; ?>
                        </button>
                    </form>
                <?php endforeach; ?>
            </div>
        </div>
    <?php elseif ($secilen_brans): ?>
        <p class="mesaj" style="background:#e9ecef; color:#495057;">Lütfen yukarıdan bir doktor seçiniz.</p>
    <?php else: ?>
        <p class="mesaj" style="background:#e9ecef; color:#495057;">Lütfen randevu almak için önce bir poliklinik seçiniz.</p>
    <?php endif; ?>
</main>

<?php require 'footer.php'; ?>