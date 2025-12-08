<?php
require_once 'auth_check.php';

require_once 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $priority = $_POST['priority'];
    $status = $_POST['status'];
    $due_date = $_POST['due_date'];
    $project_id = $_POST['project_id'];
    $assignee = $_POST['assignee'] ?? null;
    $created_by_id = $_SESSION['user_id'];

    if (empty($title) || empty($project_id) || empty($status) || empty($priority)) {
        header("Location: project_detail.php?id=$project_id&error=missing_fields");
        exit;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO tasks (title, description, priority, status, due_date, project_id, created_by_id, assignee) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $description, $priority, $status, $due_date, $project_id, $created_by_id, $assignee]);
        
        header("Location: project_detail.php?id=$project_id");
        exit;
    } catch (PDOException $e) {
        header("Location: project_detail.php?id=$project_id&error=db_error");
        exit;
    }
} else {
    header("Location: projects.php");
    exit;
}
?>
