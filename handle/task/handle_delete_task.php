<?php
// Modul Tugas - Handler untuk hapus tugas

require_once '../../auth_check.php';
require_once '../../db_connect.php';

if (isset($_POST['id'])) {
    $task_id = $_POST['id'];

    // Check if user has permission to delete
    $stmt = $pdo->prepare("SELECT created_by_id, project_id FROM tasks WHERE id = ?");
    $stmt->execute([$task_id]);
    $task = $stmt->fetch();

    if (!$task) {
        header("Location: ../../projects.php");
        exit;
    }
    // only ADMIN, MANAGER, or task creator can delete task
    if ($_SESSION['user_role'] != 'ADMIN' && $_SESSION['user_role'] != 'MANAGER' && $task['created_by_id'] != $_SESSION['user_id']) {
        header("Location: ../../projects.php");
        exit;
    }

    $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ?");
    $stmt->execute([$task_id]);

    header("Location: ../../project_detail.php?id=" . $task['project_id']);
    exit;
}

header("Location: projects.php");
exit;
?>