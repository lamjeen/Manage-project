<?php
/**
 * Document Module - Update Document Handler
 * 2432059 - Xlhynz
 * Modul untuk mengupload, menyimpan, dan mengatur file serta dokumen yang terkait dengan proyek atau tugas.
 */

require_once '../../auth_check.php';
require_once '../../db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $document_id = $_POST['document_id'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    $related_to = $_POST['related_to'];
    $project_id = $related_to == 'project' ? $_POST['project_id'] : null;
    $task_id = $related_to == 'task' ? $_POST['task_id'] : null;
    $category = $_POST['category'];

    // Check if user has permission to update
    $stmt = $pdo->prepare("SELECT uploaded_by_id, project_id, task_id FROM documents WHERE id = ?");
    $stmt->execute([$document_id]);
    $document = $stmt->fetch();

    if (!$document) {
        header("Location: ../../projects.php");
        exit;
    }

    if ($_SESSION['user_role'] != 'ADMIN' && $_SESSION['user_role'] != 'MANAGER' && $document['uploaded_by_id'] != $_SESSION['user_id']) {
        header("Location: ../../projects.php");
        exit;
    }

    $file_path = $document['file_path'];
    $file_name = $document['file_name'];
    $file_size = $document['file_size'];
    $file_type = $document['file_type'];

    // Handle file update if new file is uploaded
    if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
        $allowed = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'zip', 'rar', 'jpg', 'jpeg', 'png', 'gif'];
        $max_size = 50 * 1024 * 1024; // 50MB in bytes
        $filename = $_FILES['file']['name'];
        $filetype = pathinfo($filename, PATHINFO_EXTENSION);
        $filesize = $_FILES['file']['size'];

        if (!in_array(strtolower($filetype), $allowed)) {
            // Invalid file type - redirect back with error
            header("Location: ../../form_document.php?id=$document_id&error=invalid_file_type");
            exit;
        }

        if ($filesize > $max_size) {
            // File too large - redirect back with error
            header("Location: ../../form_document.php?id=$document_id&error=file_too_large");
            exit;
        }

        $new_filename = uniqid() . '.' . $filetype;
        $upload_path = '../../uploads/' . $new_filename;

        if (move_uploaded_file($_FILES['file']['tmp_name'], $upload_path)) {
            // Delete old file
            if ($file_path && file_exists('../../uploads/' . $file_path)) {
                unlink('../../uploads/' . $file_path);
            }

            $file_path = $new_filename;
            $file_name = $filename;
            $file_size = $filesize;
            $file_type = $filetype;
        }
    }

    $stmt = $pdo->prepare("
        UPDATE documents SET
            title = ?,
            description = ?,
            file_path = ?,
            file_name = ?,
            file_size = ?,
            file_type = ?,
            category = ?,
            project_id = ?,
            task_id = ?
        WHERE id = ?
    ");
    $stmt->execute([$title, $description, $file_path, $file_name, $file_size, $file_type, $category, $project_id, $task_id, $document_id]);

    $redirect_url = "../../projects.php";
    if ($task_id) {
        $redirect_url = "../../task_detail.php?id=$task_id";
    } elseif ($project_id) {
        $redirect_url = "../../project_detail.php?id=$project_id#documents";
    }

    header("Location: $redirect_url");
    exit;
}

header("Location: projects.php");
exit;
?>