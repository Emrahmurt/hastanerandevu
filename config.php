<?php
$host = 'localhost'; // Sunucu adı
$dbname = 'hastane_randevu'; // Veritabanı adı (yukarıda oluşturduğunuz)
$username = 'root'; // XAMPP varsayılan kullanıcı adı
$password = ''; // XAMPP varsayılan şifresi (boş)
$charset = 'utf8mb4';

// PDO (PHP Data Objects) ile güvenli bağlantı
$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $username, $password, $options);
} catch (\PDOException $e) {
     throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
?>