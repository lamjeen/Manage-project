<?php
// Memulai sesi untuk mengakses data sesi saat ini
session_start();

// Menghancurkan semua data sesi untuk melogout pengguna
session_destroy();

// Mengarahkan pengguna kembali ke halaman login setelah logout
header("Location: login.php");
exit;
?>