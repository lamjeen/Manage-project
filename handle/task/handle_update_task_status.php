<?php
// Modul Tugas - Handler untuk update status tugas

require_once '../../auth_check.php';
require_once '../../db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $task_id = $_POST['task_id'];
    $status = $_POST['status'];

    // query untuk mengambil pembuat tugas dan pengguna yang ditugaskan dari tugas
    $stmt = $pdo->prepare("SELECT created_by_id, assignee FROM tasks WHERE id = ?");
    $stmt->execute([$task_id]);
    $task = $stmt->fetch();

    if (!$task) {
        header("Location: ../../projects.php");
        exit;
    }

    // hanya admin, manager, pembuat tugas, atau pengguna yang ditugaskan yang dapat mengupdate status tugas
    $can_update = false;
    if ($_SESSION['user_role'] == 'ADMIN' || $_SESSION['user_role'] == 'MANAGER') {
        $can_update = true;
    } elseif ($task['created_by_id'] == $_SESSION['user_id']) {
        $can_update = true;
    } elseif ($task['assignee'] == $_SESSION['user_id']) {
        $can_update = true;
    }

    if (!$can_update) {
        header("Location: ../../task_detail.php?id=$task_id");
        exit;
    }

    $stmt = $pdo->prepare("UPDATE tasks SET status = ? WHERE id = ?");
    $stmt->execute([$status, $task_id]);

    header("Location: ../../task_detail.php?id=$task_id");
    exit;
}

header("Location: ../../task_detail.php?id=$task_id");
exit;
?>
