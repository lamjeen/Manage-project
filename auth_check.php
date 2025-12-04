<?php
// Memulai sesi untuk mengakses variabel $_SESSION
session_start();

// Memeriksa apakah pengguna sudah login dengan mengecek keberadaan user_id dalam sesi
// Jika tidak ada, arahkan kembali ke halaman login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Menghubungkan ke database menggunakan file db_connect.php
require_once 'db_connect.php';

// Mengambil data pengguna terbaru dari database berdasarkan ID sesi
// Menggunakan PDO prepared statement untuk keamanan
 $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
 $stmt->execute([$_SESSION['user_id']]);
 $user = $stmt->fetch();

// Jika data pengguna tidak ditemukan (misalnya dihapus), hancurkan sesi dan logout
if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit;
}

// Memperbarui data sesi dengan informasi terbaru dari database
 $_SESSION['user_name'] = $user['name'];
 $_SESSION['user_role'] = $user['role'];
?>