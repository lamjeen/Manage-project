<?php
// Modul Tugas - Handler untuk update tugas

require_once '../../auth_check.php';
require_once '../../db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $task_id = $_POST['task_id'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    $priority = $_POST['priority'];
    $status = $_POST['status'];
    $due_date = $_POST['due_date'];
    $project_id = $_POST['project_id'];
    $assignee = $_POST['assignee'] ?? null;

    // Check if user has permission to update task
    $stmt = $pdo->prepare("SELECT created_by_id, project_id FROM tasks WHERE id = ?");
    $stmt->execute([$task_id]);
    $task = $stmt->fetch();

    if (!$task) {
        header("Location: ../../projects.php");
        exit;
    }

    // Only ADMIN, MANAGER, or task creator can update task details
    if ($_SESSION['user_role'] != 'ADMIN' && $_SESSION['user_role'] != 'MANAGER' && $task['created_by_id'] != $_SESSION['user_id']) {
        header("Location: ../../task_detail.php?id=$task_id");
        exit;
    }

    $stmt = $pdo->prepare("
        UPDATE tasks SET
            title = ?,
            description = ?,
            priority = ?,
            status = ?,
            due_date = ?,
            project_id = ?,
            assignee = ?
        WHERE id = ?
    ");
    $stmt->execute([$title, $description, $priority, $status, $due_date, $project_id, $assignee, $task_id]);

    header("Location: ../../task_detail.php?id=$task_id");
    exit;
}

header("Location: ../../projects.php");
exit;
?>