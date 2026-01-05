<?php
require 'header.php';
require 'sidebar.php';

$hasta_id = $_SESSION['hasta_id'];
$mesaj_durum = '';
$mesaj_tipi = ''; 

// 1. SİLME / İPTAL ETME İŞLEMİ
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['sil_id'])) {
    $sil_id = $_POST['sil_id'];
    
    // Güvenlik: Sadece kendi mesajını silebilir
    $stmt = $pdo->prepare("DELETE FROM mesajlar WHERE mesaj_id = ? AND hasta_id = ?");
    $sonuc = $stmt->execute([$sil_id, $hasta_id]);

    if ($sonuc) {
        $mesaj_durum = "İşlem başarılı.";
        $mesaj_tipi = 'basari';
    } else {
        $mesaj_durum = "İşlem başarısız oldu.";
        $mesaj_tipi = 'hata';
    }
}

// 2. MESAJ GÖNDERME
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['mesaj_gonder'])) {
    $konu = $_POST['konu'];
    $icerik = $_POST['icerik'];

    if (!empty($konu) && !empty($icerik)) {
        $stmt = $pdo->prepare("INSERT INTO mesajlar (hasta_id, konu, icerik) VALUES (?, ?, ?)");
        $stmt->execute([$hasta_id, $konu, $icerik]);
        $mesaj_durum = "Mesajınız yönetime iletildi.";
        $mesaj_tipi = 'basari';
    }
}

// MESAJLARI ÇEK
$stmt = $pdo->prepare("SELECT * FROM mesajlar WHERE hasta_id = ? ORDER BY tarih DESC");
$stmt->execute([$hasta_id]);
$mesajlar = $stmt->fetchAll();
?>

<main class="app-content">
    <h3>Yönetimle İletişim</h3>

    <?php if ($mesaj_durum): ?>
        <div class="mesaj <?php echo $mesaj_tipi == 'basari' ? 'mesaj-basari' : 'mesaj-hata'; ?>">
            <?php echo htmlspecialchars($mesaj_durum); ?>
        </div>
    <?php endif; ?>

    <form method="post" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
        <label>Konu:</label>
        <select name="konu" required>
            <option value="">-- Seçiniz --</option>
            <option value="Şikayet">Şikayet</option>
            <option value="Öneri">Öneri</option>
            <option value="Teknik Sorun">Teknik Sorun</option>
            <option value="Diğer">Diğer</option>
        </select>

        <label>Mesajınız:</label>
        <textarea name="icerik" rows="4" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-family: inherit;"></textarea>
        
        <div style="margin-top: 15px; display: flex; gap: 10px;">
            <button type="submit" name="mesaj_gonder" style="background-color: #007bff; color: white;">Gönder</button>
            
            <button type="reset" style="background-color: #6c757d; color: white;">İptal (Temizle)</button>
        </div>
    </form>

    <hr style="border: 0; border-top: 1px solid #eee; margin: 30px 0;">

    <h3>Mesaj Geçmişim</h3>
    <?php if (count($mesajlar) > 0): ?>
        <?php foreach ($mesajlar as $m): ?>
            <div style="background: white; padding: 20px; border-radius: 8px; margin-bottom: 15px; border-left: 5px solid <?php echo $m['cevap'] ? '#28a745' : '#ffc107'; ?>; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
                
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px;">
                    <div>
                        <strong style="color: #007bff; font-size: 1.1rem;"><?php echo htmlspecialchars($m['konu']); ?></strong>
                        <div style="color: #999; font-size: 0.85rem; margin-top: 3px;">
                            <?php echo date('d.m.Y H:i', strtotime($m['tarih'])); ?>
                        </div>
                    </div>
                    
                    <form method="post" style="margin: 0;">
                        <input type="hidden" name="sil_id" value="<?php echo $m['mesaj_id']; ?>">
                        
                        <?php if (!$m['cevap']): ?>
                            <button type="submit" onclick="return confirm('Bu mesaj talebini geri çekmek (iptal etmek) istediğinize emin misiniz?');" 
                                    style="background: transparent; border: 1px solid #ffc107; color: #e0a800; padding: 5px 10px; font-size: 0.8rem; border-radius: 4px;">
                                🚫 İptal Et
                            </button>
                        <?php else: ?>
                            <button type="submit" onclick="return confirm('Bu mesajı geçmişinizden silmek istediğinize emin misiniz?');" 
                                    style="background: transparent; border: 1px solid #dc3545; color: #dc3545; padding: 5px 10px; font-size: 0.8rem; border-radius: 4px;">
                                🗑️ Sil
                            </button>
                        <?php endif; ?>
                    </form>
                </div>

                <p style="margin: 0; color: #555; line-height: 1.5;">
                    <?php echo nl2br(htmlspecialchars($m['icerik'])); ?>
                </p>
                
                <?php if ($m['cevap']): ?>
                    <div style="background: #f1f9f4; padding: 15px; margin-top: 15px; border-radius: 5px; border: 1px solid #d1e7dd;">
                        <div style="display: flex; align-items: center; gap: 5px; margin-bottom: 5px;">
                            <span style="font-size: 1.2rem;">👨‍⚕️</span>
                            <strong style="color: #198754;">Yönetim Cevabı:</strong>
                        </div>
                        <p style="margin: 0; color: #333;"><?php echo nl2br(htmlspecialchars($m['cevap'])); ?></p>
                    </div>
                <?php else: ?>
                    <div style="margin-top: 15px; font-size: 0.9rem; color: #856404; background-color: #fff3cd; padding: 8px 12px; border-radius: 4px; display: inline-block;">
                        ⏳ Yönetimden cevap bekleniyor...
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p style="color: #666; font-style: italic;">Henüz bir mesaj göndermediniz.</p>
    <?php endif; ?>

</main>

<?php require 'footer.php'; ?>