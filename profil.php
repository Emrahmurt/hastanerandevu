<?php
require 'header.php';
require 'sidebar.php';

$hasta_id = $_SESSION['hasta_id'];
$mesaj = '';
$mesaj_tipi = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $ad = $_POST['ad'];
    $soyad = $_POST['soyad'];
    $email = $_POST['email'];
    
    // Temel güncelleme sorgusu
    $sql = "UPDATE hastalar SET ad=?, soyad=?, email=?";
    $params = [$ad, $soyad, $email];

    // 1. Güvenlik Sorusu (Varsa Güncelle)
    if (isset($_POST['guvenlik_sorusu'])) {
        $sql .= ", guvenlik_sorusu=?";
        $params[] = $_POST['guvenlik_sorusu'];
    }

    // 2. Güvenlik Cevabı (Doluysa Güncelle)
    if (!empty($_POST['guvenlik_cevabi'])) {
        $sql .= ", guvenlik_cevabi=?";
        $params[] = password_hash(mb_strtolower($_POST['guvenlik_cevabi']), PASSWORD_DEFAULT);
    }

    // 3. Resim Yükleme
    if (!empty($_FILES['profil_resmi']['name'])) {
        $hedef_klasor = "uploads/";
        // Klasör yoksa oluştur
        if (!file_exists($hedef_klasor)) { mkdir($hedef_klasor, 0777, true); }
        
        $dosya_adi = time() . "_" . basename($_FILES["profil_resmi"]["name"]);
        $hedef_dosya = $hedef_klasor . $dosya_adi;
        $uzanti = strtolower(pathinfo($hedef_dosya, PATHINFO_EXTENSION));

        if (in_array($uzanti, ['jpg', 'jpeg', 'png', 'gif'])) {
            if (move_uploaded_file($_FILES["profil_resmi"]["tmp_name"], $hedef_dosya)) {
                $sql .= ", profil_resmi=?";
                $params[] = $hedef_dosya;
                $_SESSION['profil_resmi'] = $hedef_dosya; 
            } else {
                $mesaj = "Resim yüklenemedi. Klasör izni hatası olabilir.";
                $mesaj_tipi = "hata";
            }
        } else {
            $mesaj = "Sadece resim dosyaları (jpg, png) yüklenebilir.";
            $mesaj_tipi = "hata";
        }
    }

    // 4. Şifre Güncelleme
    if (!empty($_POST['yeni_parola'])) {
        $sql .= ", parola=?";
        $params[] = password_hash($_POST['yeni_parola'], PASSWORD_DEFAULT);
    }

    // WHERE koşulunu ekle
    $sql .= " WHERE hasta_id=?";
    $params[] = $hasta_id;

    try {
        if (empty($mesaj) || $mesaj_tipi != 'hata') {
            $stmt = $pdo->prepare($sql);
            $sonuc = $stmt->execute($params);
            
            if ($sonuc) {
                $mesaj = "Bilgileriniz başarıyla güncellendi!";
                $mesaj_tipi = "basari";
                $_SESSION['ad'] = $ad; // Ekrandaki ismi güncelle
            } else {
                $mesaj = "Güncelleme yapılamadı. Değişiklik yapmamış olabilirsiniz.";
                $mesaj_tipi = "hata";
            }
        }
    } catch (PDOException $e) {
        $mesaj = "Veritabanı Hatası: " . $e->getMessage();
        $mesaj_tipi = "hata";
    }
}

// Mevcut Bilgileri Çek
$stmt = $pdo->prepare("SELECT * FROM hastalar WHERE hasta_id = ?");
$stmt->execute([$hasta_id]);
$uye = $stmt->fetch();
?>

<main class="app-content">
    <h3>Profil Bilgilerim</h3>
    
    <?php if ($mesaj): ?>
        <div class="mesaj <?php echo $mesaj_tipi == 'basari' ? 'mesaj-basari' : 'mesaj-hata'; ?>">
            <?php echo htmlspecialchars($mesaj); ?>
        </div>
    <?php endif; ?>

    <form action="profil.php" method="post" enctype="multipart/form-data" style="background:white; padding:30px; border-radius:10px; box-shadow:0 2px 5px rgba(0,0,0,0.05);">
        
        <div style="text-align:center; margin-bottom:30px;">
            <?php 
                $resim_yolu = !empty($uye['profil_resmi']) ? $uye['profil_resmi'] : 'https://via.placeholder.com/150?text=Profil'; 
            ?>
            <img src="<?php echo $resim_yolu; ?>" style="width:120px; height:120px; border-radius:50%; object-fit:cover; border:4px solid #f1f1f1; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
            <br>
            <label for="profil_resmi" style="cursor:pointer; color:#007bff; font-weight:600; margin-top:10px; display:inline-block; padding: 5px 10px; border:1px dashed #007bff; border-radius:5px;">📷 Fotoğraf Yükle / Değiştir</label>
            <input type="file" name="profil_resmi" id="profil_resmi" style="display:none;" onchange="document.querySelector('img').src = window.URL.createObjectURL(this.files[0])">
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div>
                <label>TC Kimlik No:</label>
                <input type="text" value="<?php echo htmlspecialchars($uye['tc_no'] ?? ''); ?>" disabled style="background-color: #e9ecef; cursor: not-allowed;">
            </div>
            <div>
                <label>E-posta:</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($uye['email']); ?>" required>
            </div>
            <div>
                <label>Ad:</label>
                <input type="text" name="ad" value="<?php echo htmlspecialchars($uye['ad']); ?>" required>
            </div>
            <div>
                <label>Soyad:</label>
                <input type="text" name="soyad" value="<?php echo htmlspecialchars($uye['soyad']); ?>" required>
            </div>
        </div>

        <hr style="margin: 30px 0; border:0; border-top:1px solid #eee;">
        <h4 style="color:#2c3e50; font-size:1.1rem; margin-bottom:20px;">🔒 Güvenlik & Şifre</h4>

        <label>Güvenlik Sorusu:</label>
        <select name="guvenlik_sorusu">
            <?php $gs = $uye['guvenlik_sorusu'] ?? ''; ?>
            <option value="İlkokul öğretmeninizin adı?" <?php echo $gs == 'İlkokul öğretmeninizin adı?' ? 'selected' : ''; ?>>İlkokul öğretmeninizin adı?</option>
            <option value="İlk evcil hayvanınızın adı?" <?php echo $gs == 'İlk evcil hayvanınızın adı?' ? 'selected' : ''; ?>>İlk evcil hayvanınızın adı?</option>
            <option value="En sevdiğiniz yemek?" <?php echo $gs == 'En sevdiğiniz yemek?' ? 'selected' : ''; ?>>En sevdiğiniz yemek?</option>
            <option value="Doğduğunuz şehir?" <?php echo $gs == 'Doğduğunuz şehir?' ? 'selected' : ''; ?>>Doğduğunuz şehir?</option>
        </select>

        <label>Güvenlik Cevabı (Değiştirmek istemiyorsanız boş bırakın):</label>
        <input type="text" name="guvenlik_cevabi" placeholder="Cevabınızı güncellemek için yazın...">

        <label>Yeni Parola (Değiştirmek istemiyorsanız boş bırakın):</label>
        <input type="password" name="yeni_parola" placeholder="Yeni parolanız...">

        <button type="submit" style="width:100%; margin-top:20px; height: 50px; background-color: #27ae60; font-size: 1.1rem;">Bilgileri Güncelle</button>
    </form>
</main>

<?php require 'footer.php'; ?>