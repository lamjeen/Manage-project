<?php
// Memastikan pengguna sudah login sebelum mengakses file ini
require_once 'auth_check.php';

// Menghubungkan ke database
require_once 'db_connect.php';

// Memproses permintaan hanya jika metode request adalah POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Mengambil data dari form
    $task_id = $_POST['task_id'];
    $author_id = $_SESSION['user_id'];
    $content = $_POST['content'];
    $type = $_POST['type'];
    $privacy = $_POST['privacy'];

    // Menentukan status pin komentar (1 jika dicentang, 0 jika tidak)
    $is_pinned = isset($_POST['is_pinned']) ? 1 : 0;

    // Inisialisasi variabel untuk file lampiran
    $file_path = null;
    $file_name = null;
    $file_size = null;
    $file_type = null;

    // Memproses upload file jika ada file yang diunggah dan tidak ada error
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] == 0) {
        // Daftar ekstensi file yang diizinkan
        $allowed = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'zip', 'rar', 'jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['attachment']['name'];
        $filetype = pathinfo($filename, PATHINFO_EXTENSION);

        // Memeriksa apakah tipe file diizinkan
        if (in_array(strtolower($filetype), $allowed)) {
            // Membuat nama file unik untuk menghindari duplikasi
            $new_filename = 'comment_' . uniqid() . '.' . $filetype;
            $upload_path = 'uploads/' . $new_filename;

            // Memindahkan file yang diunggah ke direktori tujuan
            if (move_uploaded_file($_FILES['attachment']['tmp_name'], $upload_path)) {
                $file_path = $new_filename;
                $file_name = $filename;
                $file_size = $_FILES['attachment']['size'];
                $file_type = $filetype;
            }
        }
    }

    // Menyimpan komentar baru ke dalam database menggunakan prepared statement
    $stmt = $pdo->prepare("INSERT INTO comments (content, task_id, author_id, type, privacy, file_path, file_name, file_size, file_type, is_pinned) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$content, $task_id, $author_id, $type, $privacy, $file_path, $file_name, $file_size, $file_type, $is_pinned]);

    // Mengarahkan kembali ke halaman detail tugas setelah berhasil
    header("Location: task_detail.php?id=$task_id");
    exit;
}

// Jika bukan request POST, arahkan kembali ke daftar tugas
header("Location: tasks.php");
exit;
?>