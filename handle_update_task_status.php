<?php
// Memastikan pengguna sudah login sebelum mengakses file ini
require_once 'auth_check.php';

// Menghubungkan ke database
require_once 'db_connect.php';

// Memproses permintaan hanya jika metode request adalah POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $task_id = $_POST['task_id'];
    $status = $_POST['status'];

    // Daftar status yang valid sesuai ENUM di database
    $allowed_statuses = ['TO_DO', 'IN_PROGRESS', 'REVIEW', 'DONE'];
    
    // Memvalidasi apakah status yang dikirim valid
    if (!in_array($status, $allowed_statuses)) {
        // Jika status tidak valid, arahkan kembali dengan pesan error
        header("Location: task_detail.php?id=$task_id&error=invalid_status");
        exit;
    }

    // Memperbarui status tugas di database
    $stmt = $pdo->prepare("UPDATE tasks SET status = ? WHERE id = ?");
    if ($stmt->execute([$status, $task_id])) {
        // Jika berhasil, arahkan kembali dengan pesan sukses
        header("Location: task_detail.php?id=$task_id&success=status_updated");
    } else {
        // Jika gagal, arahkan kembali dengan pesan error
        header("Location: task_detail.php?id=$task_id&error=update_failed");
    }
    exit;
}

// Jika bukan request POST, arahkan kembali ke daftar tugas
header("Location: tasks.php");
exit;
?>
