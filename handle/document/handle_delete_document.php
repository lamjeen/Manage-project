<?php
/**
 * Document Module - Delete Document Handler
 * 2432059 - Xlhynz
 * Modul untuk mengupload, menyimpan, dan mengatur file serta dokumen yang terkait dengan proyek atau tugas.
 */

require_once '../../auth_check.php';
require_once '../../db_connect.php';

if (isset($_GET['id'])) {
    $document_id = $_GET['id'];

    $stmt = $pdo->prepare("SELECT file_path, uploaded_by_id, project_id, task_id FROM documents WHERE id = ?");
    $stmt->execute([$document_id]);
    $document = $stmt->fetch();

    if ($document) {
        if ($_SESSION['user_role'] == 'ADMIN' || $_SESSION['user_role'] == 'MANAGER' || $document['uploaded_by_id'] == $_SESSION['user_id']) {
            // Delete file from filesystem
            $file_path = '../../uploads/' . $document['file_path'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }

            // Delete from database
            $stmt = $pdo->prepare("DELETE FROM documents WHERE id = ?");
            $stmt->execute([$document_id]);

            $redirect_url = "../../projects.php";
            if ($document['task_id']) {
                $redirect_url = "../../task_detail.php?id=" . $document['task_id'];
            } elseif ($document['project_id']) {
                $redirect_url = "../../project_detail.php?id=" . $document['project_id'] . "#documents";
            }

            header("Location: " . $redirect_url);
            exit;
        }
    }
}

header("Location: ../../projects.php");
exit;
?>