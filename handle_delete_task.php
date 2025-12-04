<?php
// Memastikan pengguna sudah login sebelum mengakses file ini
require_once 'auth_check.php';

// Menghubungkan ke database
require_once 'db_connect.php';

// Memeriksa apakah ID tugas tersedia di URL
if (isset($_GET['id'])) {
    $task_id = $_GET['id'];
    
    // Mengambil data tugas untuk memeriksa pembuat tugas
    $stmt = $pdo->prepare("SELECT created_by_id FROM tasks WHERE id = ?");
    $stmt->execute([$task_id]);
    $task = $stmt->fetch();
    
    if (!$task) {
        header("Location: tasks.php");
        exit;
    }
    
    // Memeriksa izin pengguna: Admin, Manajer, atau Pembuat tugas dapat menghapus
    if ($_SESSION['user_role'] != 'ADMIN' && $_SESSION['user_role'] != 'MANAGER' && $task['created_by_id'] != $_SESSION['user_id']) {
        header("Location: tasks.php");
        exit;
    }
    
    // Menghapus tugas dari database
    $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ?");
    $stmt->execute([$task_id]);
}

// Mengarahkan kembali ke daftar tugas setelah penghapusan
header("Location: tasks.php");
exit;
?>