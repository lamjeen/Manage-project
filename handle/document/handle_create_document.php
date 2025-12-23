<?php
// Modul Dokumen - Handler untuk upload dokumen baru

require_once '../../auth_check.php';
require_once '../../db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $related_to = $_POST['related_to'];
    $project_id = $related_to == 'project' ? $_POST['project_id'] : null;
    $task_id = $related_to == 'task' ? $_POST['task_id'] : null;
    $category = $_POST['category'];
    $uploaded_by_id = $_SESSION['user_id'];

    $file_path = '';
    $file_name = '';
    $file_size = 0;
    $file_type = '';

    if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
        $allowed = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'zip', 'rar', 'jpg', 'jpeg', 'png', 'gif'];
        $max_size = 50 * 1024 * 1024; // 50MB in bytes
        $filename = $_FILES['file']['name'];
        $filetype = pathinfo($filename, PATHINFO_EXTENSION);
        $filesize = $_FILES['file']['size'];

        if (!in_array(strtolower($filetype), $allowed)) {
            header("Location: ../../form_document.php?error=invalid_file_type");
            exit;
        }

        if ($filesize > $max_size) {
            header("Location: ../../form_document.php?error=file_too_large");
            exit;
        }

        $new_filename = uniqid() . '.' . $filetype;
        $upload_path = '../../uploads/' . $new_filename;

        if (move_uploaded_file($_FILES['file']['tmp_name'], $upload_path)) {
            $file_path = $new_filename;
            $file_name = $filename;
            $file_size = $filesize;
            $file_type = $filetype;
        }
    }

    $stmt = $pdo->prepare("
        INSERT INTO documents (title, description, file_path, file_name, file_size, file_type, category, uploaded_by_id, project_id, task_id)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$title, $description, $file_path, $file_name, $file_size, $file_type, $category, $uploaded_by_id, $project_id, $task_id]);

    $redirect_url = "projects.php";
    if ($task_id) {
        $redirect_url = "../../task_detail.php?id=$task_id";
    } elseif ($project_id) {
        $redirect_url = "../../project_detail.php?id=$project_id#documents";
    }

    header("Location: $redirect_url");
    exit;
}

header("Location: ../../projects.php");
exit;
?>