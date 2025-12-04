<?php
// Memastikan pengguna sudah login sebelum mengakses file ini
require_once 'auth_check.php';

// Menghubungkan ke database
require_once 'db_connect.php';

// Memeriksa izin pengguna: Hanya Admin dan Manajer yang boleh menghapus proyek
if ($_SESSION['user_role'] != 'ADMIN' && $_SESSION['user_role'] != 'MANAGER') {
    header("Location: projects.php");
    exit;
}

// Memeriksa apakah ID proyek tersedia di URL
if (isset($_GET['id'])) {
    $project_id = $_GET['id'];
    
    // Mengambil data proyek untuk memeriksa manajer proyek
    $stmt = $pdo->prepare("SELECT manager_id FROM projects WHERE id = ?");
    $stmt->execute([$project_id]);
    $project = $stmt->fetch();
    
    if (!$project) {
        header("Location: projects.php");
        exit;
    }
    
    // Memeriksa izin spesifik: Jika bukan Admin, pengguna haruslah manajer dari proyek tersebut
    if ($_SESSION['user_role'] != 'ADMIN' && $project['manager_id'] != $_SESSION['user_id']) {
        header("Location: projects.php");
        exit;
    }
    
    // Menghapus proyek dari database (CASCADE delete akan menghapus tugas dan dokumen terkait jika diatur di database)
    $stmt = $pdo->prepare("DELETE FROM projects WHERE id = ?");
    $stmt->execute([$project_id]);
}

// Mengarahkan kembali ke daftar proyek setelah penghapusan
header("Location: projects.php");
exit;
?>