<?php
session_start(); // Oturumu başlat
session_unset(); // Tüm session değişkenlerini sil
session_destroy(); // Oturumu sonlandır

header("Location: login.php"); // Giriş sayfasına yönlendir
exit;
?>