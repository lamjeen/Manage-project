<?php
// Memastikan pengguna sudah login sebelum mengakses file ini
require_once 'auth_check.php';

// Menghubungkan ke database
require_once 'db_connect.php';

// Memeriksa apakah ID dokumen tersedia di URL
if (isset($_GET['id'])) {
    $document_id = $_GET['id'];
    
    // Mengambil informasi dokumen (path file, pengunggah, project ID) sebelum menghapus
    $stmt = $pdo->prepare("SELECT file_path, uploaded_by_id, project_id FROM documents WHERE id = ?");
    $stmt->execute([$document_id]);
    $document = $stmt->fetch();
    
    if ($document) {
        // Memeriksa izin pengguna: Admin, Manajer, atau Pengunggah dokumen dapat menghapus
        if ($_SESSION['user_role'] == 'ADMIN' || $_SESSION['user_role'] == 'MANAGER' || $document['uploaded_by_id'] == $_SESSION['user_id']) {
            
            // Menghapus file fisik dari server jika ada
            $file_path = 'uploads/' . $document['file_path'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
            
            // Menghapus data dokumen dari database
            $stmt = $pdo->prepare("DELETE FROM documents WHERE id = ?");
            $stmt->execute([$document_id]);
            
            // Menentukan URL redirect: kembali ke detail proyek jika dokumen terkait proyek, atau ke daftar dokumen
            $redirect_url = "documents.php";
            if ($document['project_id']) {
                $redirect_url = "project_detail.php?id=" . $document['project_id'] . "#documents";
            }
            header("Location: " . $redirect_url);
            exit;
        }
    }
}

// Jika ID tidak ada atau dokumen tidak ditemukan, kembali ke daftar dokumen
header("Location: documents.php");
exit;
?>