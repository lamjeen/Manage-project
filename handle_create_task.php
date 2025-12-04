<?php
// Memastikan pengguna sudah login sebelum mengakses file ini
require_once 'auth_check.php';

// Menghubungkan ke database
require_once 'db_connect.php';

// Memproses permintaan hanya jika metode request adalah POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Mengambil data dari form pembuatan tugas
    $title = $_POST['title'];
    $description = $_POST['description'];
    $priority = $_POST['priority'];
    $status = $_POST['status'];
    $due_date = $_POST['due_date'];
    $project_id = $_POST['project_id'];
    $assignee = $_POST['assignee'] ?? null;
    $created_by_id = $_SESSION['user_id'];

    // Melakukan validasi dasar untuk memastikan field wajib terisi
    if (empty($title) || empty($project_id) || empty($status) || empty($priority)) {
        // Jika ada field yang kosong, arahkan kembali dengan pesan error
        header("Location: project_detail.php?id=$project_id&error=missing_fields");
        exit;
    }

    try {
        // Menyimpan tugas baru ke dalam database
        $stmt = $pdo->prepare("INSERT INTO tasks (title, description, priority, status, due_date, project_id, created_by_id, assignee) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $description, $priority, $status, $due_date, $project_id, $created_by_id, $assignee]);
        
        // Mengarahkan kembali ke halaman detail proyek setelah berhasil
        header("Location: project_detail.php?id=$project_id");
        exit;
    } catch (PDOException $e) {
        // Menangani kesalahan database jika terjadi
        header("Location: project_detail.php?id=$project_id&error=db_error");
        exit;
    }
} else {
    // Jika bukan request POST, arahkan kembali ke daftar proyek
    header("Location: projects.php");
    exit;
}
?>
