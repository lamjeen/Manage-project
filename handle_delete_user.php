<?php
// Memastikan pengguna sudah login sebelum mengakses file ini
require_once 'auth_check.php';

// Menghubungkan ke database
require_once 'db_connect.php';

// Memeriksa izin pengguna: Hanya Admin yang boleh menghapus pengguna
if ($_SESSION['user_role'] != 'ADMIN') {
    header("Location: dashboard.php");
    exit;
}

// Memeriksa apakah ID pengguna tersedia di URL
if (isset($_GET['id'])) {
    $user_id = $_GET['id'];
    
    // Mencegah admin menghapus akunnya sendiri saat sedang login
    if ($user_id == $_SESSION['user_id']) {
        header("Location: users.php");
        exit;
    }
    
    // Memeriksa apakah pengguna mengelola proyek (tidak boleh dihapus jika ya)
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM projects WHERE manager_id = ?");
    $stmt->execute([$user_id]);
    $manages_projects = $stmt->fetch()['count'] > 0;

    // Memeriksa apakah pengguna membuat tugas (tidak boleh dihapus jika ya)
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM tasks WHERE created_by_id = ?");
    $stmt->execute([$user_id]);
    $created_tasks = $stmt->fetch()['count'] > 0;

    // Jika pengguna memiliki ketergantungan data penting, batalkan penghapusan
    if ($manages_projects || $created_tasks) {
        header("Location: users.php?error=cannot_delete");
        exit;
    }

    // Menghapus pengguna dari database
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
}

// Mengarahkan kembali ke daftar pengguna setelah penghapusan
header("Location: users.php");
exit;
?>