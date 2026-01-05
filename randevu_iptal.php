<?php
session_start();
require 'config.php';

if (!isset($_SESSION['hasta_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $randevu_id = $_POST['randevu_id'];
    $hasta_id = $_SESSION['hasta_id'];

    try {
        $sql = "DELETE FROM randevular WHERE randevu_id = ? AND hasta_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$randevu_id, $hasta_id]);
        
        header("Location: index.php?durum=iptal_basarili");
        exit;
    } catch (PDOException $e) {
        die("Hata: " . $e->getMessage());
    }
}
?>