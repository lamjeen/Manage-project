<?php
// Modul Tugas - Handler untuk membuat tugas baru

require_once '../../auth_check.php';
require_once '../../db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $priority = $_POST['priority'];
    $status = $_POST['status'];
    $due_date = $_POST['due_date'];
    $project_id = $_POST['project_id'];
    $assignee = $_POST['assignee'] ?? null;
    $created_by_id = $_SESSION['user_id'];

    // Check if user has permission to create task in this project
    // Only ADMIN, MANAGER, or team members of the project can create tasks
    if ($_SESSION['user_role'] != 'ADMIN' && $_SESSION['user_role'] != 'MANAGER') {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count FROM team_members tm
            JOIN project_team pt ON tm.team_id = pt.team_id
            WHERE pt.project_id = ? AND tm.user_id = ?
        ");
        $stmt->execute([$project_id, $_SESSION['user_id']]);
        $is_team_member = $stmt->fetch()['count'] > 0;

        if (!$is_team_member) {
            header("Location: ../../projects.php");
            exit;
        }
    }

    $stmt = $pdo->prepare("
        INSERT INTO tasks (title, description, priority, status, due_date, project_id, created_by_id, assignee)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$title, $description, $priority, $status, $due_date, $project_id, $created_by_id, $assignee]);

    $new_task_id = $pdo->lastInsertId();
    header("Location: ../../task_detail.php?id=$new_task_id");
    exit;
}

header("Location: ../../projects.php");
exit;
?>