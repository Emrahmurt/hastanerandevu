<?php
session_start();
require 'config.php';

if (!isset($_SESSION['yonetici_id'])) { header("Location: admin_login.php"); exit; }

// DUYURU EKLEME
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['duyuru_ekle'])) {
    $baslik = $_POST['baslik'];
    $icerik = $_POST['icerik'];
    
    $stmt = $pdo->prepare("INSERT INTO duyurular (baslik, icerik) VALUES (?, ?)");
    $stmt->execute([$baslik, $icerik]);
}

// DUYURU SİLME
if (isset($_GET['sil_id'])) {
    $stmt = $pdo->prepare("DELETE FROM duyurular WHERE duyuru_id = ?");
    $stmt->execute([$_GET['sil_id']]);
    header("Location: admin_duyurular.php");
    exit;
}

// DUYURULARI ÇEK
$duyurular = $pdo->query("SELECT * FROM duyurular ORDER BY tarih DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Duyuru Yönetimi</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { background-color: #ecf0f1; }
        .duyuru-karti { background: white; padding: 15px; margin-bottom: 15px; border-radius: 5px; border-left: 5px solid #3498db; display:flex; justify-content:space-between; align-items:center; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
    </style>
</head>
<body>
    <div style="background: #2c3e50; color: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center;">
        <div style="font-weight: bold; font-size: 1.2rem;">Duyuru Paneli</div>
        <div>
            <a href="admin_panel.php" style="color: white; margin-right: 15px;">« Panele Dön</a>
            <a href="logout.php" style="color: #e74c3c;">Çıkış</a>
        </div>
    </div>

    <div style="padding: 30px; max-width: 800px; margin: 0 auto;">
        
        <div style="background: white; padding: 20px; border-radius: 10px; margin-bottom: 30px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
            <h3>Yeni Duyuru Yayınla</h3>
            <form method="post">
                <label>Başlık:</label>
                <input type="text" name="baslik" required placeholder="Örn: Sistem Bakımı Hakkında">
                
                <label>İçerik:</label>
                <textarea name="icerik" rows="3" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:5px;"></textarea>
                
                <button type="submit" name="duyuru_ekle" style="background: #27ae60; color: white; margin-top:10px;">Yayınla</button>
            </form>
        </div>

        <h3>Yayındaki Duyurular</h3>
        <?php foreach ($duyurular as $d): ?>
            <div class="duyuru-karti">
                <div>
                    <strong style="font-size:1.1rem; color:#2c3e50;"><?php echo htmlspecialchars($d['baslik']); ?></strong>
                    <br>
                    <small style="color:#7f8c8d;"><?php echo date('d.m.Y H:i', strtotime($d['tarih'])); ?></small>
                    <p style="margin:5px 0 0 0; color:#555;"><?php echo nl2br(htmlspecialchars($d['icerik'])); ?></p>
                </div>
                <a href="?sil_id=<?php echo $d['duyuru_id']; ?>" onclick="return confirm('Silmek istiyor musunuz?')" style="color:#c0392b; font-weight:bold;">Sil 🗑️</a>
            </div>
        <?php endforeach; ?>

    </div>
</body>
</html>