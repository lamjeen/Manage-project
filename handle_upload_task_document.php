<?php
require_once 'auth_check.php';

require_once 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $task_id = $_POST['task_id'];
    $title = trim($_POST['title']);
    $description = trim($_POST['description'] ?? '');
    $category = $_POST['category'];
    $uploaded_by_id = $_SESSION['user_id'];

    // Validasi task exists dan user memiliki akses
    $stmt = $pdo->prepare("SELECT t.*, p.id as project_id FROM tasks t LEFT JOIN projects p ON t.project_id = p.id WHERE t.id = ?");
    $stmt->execute([$task_id]);
    $task = $stmt->fetch();

    if (!$task) {
        header("Location: projects.php?error=task_not_found");
        exit;
    }

    // Cek apakah user memiliki akses ke task ini
    $has_access = false;
    if ($_SESSION['user_role'] == 'ADMIN' || $_SESSION['user_role'] == 'MANAGER') {
        $has_access = true;
    } elseif ($task['assignee'] == $_SESSION['user_id']) {
        $has_access = true;
    } elseif ($task['created_by_id'] == $_SESSION['user_id']) {
        $has_access = true;
    }

    if (!$has_access) {
        header("Location: task_detail.php?id=$task_id&error=access_denied");
        exit;
    }

    // Validasi input
    if (empty($title) || empty($category)) {
        header("Location: task_detail.php?id=$task_id&error=empty_fields");
        exit;
    }

    // Validasi category sesuai ENUM di database
    $allowed_categories = ['Design', 'Document', 'Report', 'Other'];
    if (!in_array($category, $allowed_categories)) {
        header("Location: task_detail.php?id=$task_id&error=invalid_category");
        exit;
    }

    $file_path = '';
    $file_name = '';
    $file_size = 0;
    $file_type = '';

    // Handle file upload
    if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
        $allowed = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'zip', 'rar', 'jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['file']['name'];
        $filetype = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (in_array($filetype, $allowed)) {
            $new_filename = uniqid() . '.' . $filetype;
            $upload_path = 'uploads/' . $new_filename;

            if (move_uploaded_file($_FILES['file']['tmp_name'], $upload_path)) {
                $file_path = $new_filename;
                $file_name = $filename;
                $file_size = $_FILES['file']['size'];
                $file_type = $filetype;
            } else {
                header("Location: task_detail.php?id=$task_id&error=upload_failed");
                exit;
            }
        } else {
            header("Location: task_detail.php?id=$task_id&error=invalid_file_type");
            exit;
        }
    } else {
        header("Location: task_detail.php?id=$task_id&error=no_file");
        exit;
    }

    // Insert document
    $stmt = $pdo->prepare("INSERT INTO documents (title, description, file_path, file_name, file_size, file_type, category, uploaded_by_id, project_id, task_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$title, $description, $file_path, $file_name, $file_size, $file_type, $category, $uploaded_by_id, $task['project_id'], $task_id]);

    header("Location: task_detail.php?id=$task_id&success=document_uploaded");
    exit;
}

header("Location: task_detail.php?id=$task_id");
exit;
?>
