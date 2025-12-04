<?php
// Memastikan pengguna sudah login sebelum mengakses file ini
require_once 'auth_check.php';

// Menghubungkan ke database
require_once 'db_connect.php';

// Memeriksa izin pengguna: Hanya Admin dan Manajer yang boleh menghapus tim
if ($_SESSION['user_role'] != 'ADMIN' && $_SESSION['user_role'] != 'MANAGER') {
    header("Location: teams.php");
    exit;
}

// Memeriksa apakah ID tim tersedia di URL
if (isset($_GET['id'])) {
    $team_id = $_GET['id'];
    
    // Menghapus tim dari database
    // Data anggota tim (team_members) akan terhapus otomatis jika ada constraint ON DELETE CASCADE
    $stmt = $pdo->prepare("DELETE FROM teams WHERE id = ?");
    $stmt->execute([$team_id]);
}

// Mengarahkan kembali ke daftar tim setelah penghapusan
header("Location: teams.php");
exit;
?>