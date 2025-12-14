<?php
/**
 * Comment/Notes Module - Create Comment Handler
 * 2432050 - Nicholas Syahputra
 * Modul yang menyediakan fitur komentar dan catatan sebagai ruang komunikasi antar anggota.
 */

require_once '../../auth_check.php';
require_once '../../db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $task_id = $_POST['task_id'];
    $author_id = $_SESSION['user_id'];
    $content = $_POST['content'];
    $type = $_POST['type'];
    $privacy = $_POST['privacy'];

    $is_pinned = isset($_POST['is_pinned']) ? 1 : 0;

    $file_path = null;
    $file_name = null;
    $file_size = null;
    $file_type = null;

    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] == 0) {
        $allowed = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'zip', 'rar', 'jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['attachment']['name'];
        $filetype = pathinfo($filename, PATHINFO_EXTENSION);

        if (in_array(strtolower($filetype), $allowed)) {
            $new_filename = 'comment_' . uniqid() . '.' . $filetype;
            $upload_path = '../../uploads/' . $new_filename;

            if (move_uploaded_file($_FILES['attachment']['tmp_name'], $upload_path)) {
                $file_path = $new_filename;
                $file_name = $filename;
                $file_size = $_FILES['attachment']['size'];
                $file_type = $filetype;
            }
        }
    }

    $stmt = $pdo->prepare("
        INSERT INTO comments (content, task_id, author_id, type, privacy, file_path, file_name, file_size, file_type, is_pinned)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$content, $task_id, $author_id, $type, $privacy, $file_path, $file_name, $file_size, $file_type, $is_pinned]);

    header("Location: ../../task_detail.php?id=$task_id");
    exit;
}

header("Location: ../../projects.php");
exit;
?>