<?php
require_once 'auth_check.php';

require_once 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $task_id = $_POST['task_id'];
    $status = $_POST['status'];

    $allowed_statuses = ['TO_DO', 'IN_PROGRESS', 'REVIEW', 'DONE'];
    
    if (!in_array($status, $allowed_statuses)) {
        header("Location: task_detail.php?id=$task_id&error=invalid_status");
        exit;
    }

    $stmt = $pdo->prepare("UPDATE tasks SET status = ? WHERE id = ?");
    if ($stmt->execute([$status, $task_id])) {
        header("Location: task_detail.php?id=$task_id&success=status_updated");
    } else {
        header("Location: task_detail.php?id=$task_id&error=update_failed");
    }
    exit;
}

header("Location: tasks.php");
exit;
?>
