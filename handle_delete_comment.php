<?php
// Memastikan pengguna sudah login sebelum mengakses file ini
require_once 'auth_check.php';

// Menghubungkan ke database
require_once 'db_connect.php';

// Memeriksa apakah ID komentar dan ID tugas tersedia di URL
if (isset($_GET['id']) && isset($_GET['task_id'])) {
    $comment_id = $_GET['id'];
    $task_id = $_GET['task_id'];
    
    // Mengambil data komentar untuk memeriksa kepemilikan
    $stmt = $pdo->prepare("SELECT author_id FROM comments WHERE id = ?");
    $stmt->execute([$comment_id]);
    $comment = $stmt->fetch();
    
    if ($comment) {
        // Memeriksa izin pengguna: Admin, Manajer, atau Pembuat komentar dapat menghapus
        if ($_SESSION['user_role'] == 'ADMIN' || $_SESSION['user_role'] == 'MANAGER' || $comment['author_id'] == $_SESSION['user_id']) {
            // Menghapus komentar dari database
            $stmt = $pdo->prepare("DELETE FROM comments WHERE id = ?");
            $stmt->execute([$comment_id]);
        }
    }
    
    // Mengarahkan kembali ke halaman detail tugas
    header("Location: task_detail.php?id=$task_id");
    exit;
}

// Jika parameter tidak lengkap, arahkan kembali ke daftar tugas
header("Location: tasks.php");
exit;
?>