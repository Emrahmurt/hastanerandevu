<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config.php';

if (!isset($_SESSION['hasta_id'])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hastane Randevu Sistemi</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    
    <div class="app-container">
        <header class="app-header">
            <div class="logo">
                <a href="index.php">Randevu Sistemi</a>
            </div>
            
            <div class="user-info">
                <span>Hoş geldiniz, <strong><?php echo htmlspecialchars($_SESSION['ad']); ?>!</strong></span>
                <a href="logout.php" class="header-cikis-btn">Çıkış Yap</a>
            </div>
            </header>
        
        <div class="app-body">