<?php
session_start();
require 'config.php';

if (!isset($_SESSION['yonetici_id'])) { header("Location: admin_login.php"); exit; }

// CEVAPLAMA İŞLEMİ
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['cevapla'])) {
    $mesaj_id = $_POST['mesaj_id'];
    $cevap = $_POST['cevap'];
    
    $stmt = $pdo->prepare("UPDATE mesajlar SET cevap = ? WHERE mesaj_id = ?");
    $stmt->execute([$cevap, $mesaj_id]);
}

// MESAJLARI ÇEK (Cevaplanmamışlar en üstte)
$sql = "SELECT m.*, h.ad, h.soyad, h.tc_no FROM mesajlar m 
        JOIN hastalar h ON m.hasta_id = h.hasta_id 
        ORDER BY (m.cevap IS NULL) DESC, m.tarih DESC";
$mesajlar = $pdo->query($sql)->fetchAll();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Yönetici Mesajları</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Admin paneline özel ufak stiller */
        body { background-color: #ecf0f1; }
        .msg-card { background: white; padding: 20px; margin-bottom: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .msg-header { display: flex; justify-content: space-between; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 10px; }
        .badge { padding: 5px 10px; border-radius: 15px; font-size: 0.8rem; color: white; }
        .bg-warning { background-color: #f1c40f; }
        .bg-success { background-color: #2ecc71; }
    </style>
</head>
<body>
    <div style="background: #2c3e50; color: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center;">
        <div style="font-weight: bold; font-size: 1.2rem;">Mesaj Kutusu</div>
        <div>
            <a href="admin_panel.php" style="color: white; margin-right: 15px;">« Panele Dön</a>
            <a href="logout.php" style="color: #e74c3c;">Çıkış</a>
        </div>
    </div>

    <div style="padding: 30px; max-width: 1000px; margin: 0 auto;">
        <h3>Gelen Mesajlar</h3>
        
        <?php foreach ($mesajlar as $m): ?>
            <div class="msg-card">
                <div class="msg-header">
                    <div>
                        <strong><?php echo htmlspecialchars($m['ad'] . ' ' . $m['soyad']); ?></strong> 
                        <span style="color:#7f8c8d; font-size:0.9rem;">(TC: <?php echo $m['tc_no']; ?>)</span>
                        <br>
                        <small style="color:#007bff;"><?php echo htmlspecialchars($m['konu']); ?></small>
                    </div>
                    <div style="text-align: right;">
                        <span class="badge <?php echo $m['cevap'] ? 'bg-success' : 'bg-warning'; ?>">
                            <?php echo $m['cevap'] ? 'Cevaplandı' : 'Bekliyor'; ?>
                        </span>
                        <br>
                        <small style="color:#999;"><?php echo date('d.m.Y H:i', strtotime($m['tarih'])); ?></small>
                    </div>
                </div>
                
                <p style="background:#f9f9f9; padding:15px; border-radius:5px; font-style:italic;">
                    "<?php echo nl2br(htmlspecialchars($m['icerik'])); ?>"
                </p>

                <?php if (!$m['cevap']): ?>
                    <form method="post" style="margin-top: 15px;">
                        <input type="hidden" name="mesaj_id" value="<?php echo $m['mesaj_id']; ?>">
                        <textarea name="cevap" rows="3" placeholder="Cevabınızı yazın..." required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:5px;"></textarea>
                        <button type="submit" name="cevapla" style="background:#27ae60; color:white; margin-top:5px;">Yanıtla</button>
                    </form>
                <?php else: ?>
                    <div style="border-top:1px solid #eee; padding-top:10px; color:#27ae60;">
                        <strong>Cevabınız:</strong> <?php echo nl2br(htmlspecialchars($m['cevap'])); ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</body>
</html>