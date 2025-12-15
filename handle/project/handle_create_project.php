<?php
// Modul Projek - Handler untuk buat projek baru

require_once '../../auth_check.php';
require_once '../../db_connect.php';

if ($_SESSION['user_role'] != 'ADMIN' && $_SESSION['user_role'] != 'MANAGER') {
    header("Location: ../../dashboard.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $status = $_POST['status'];
    $priority = $_POST['priority'];
    $manager_id = $_POST['manager_id'];
    $team_ids = $_POST['team_ids'] ?? [];

    $stmt = $pdo->prepare("
        INSERT INTO projects (name, description, start_date, end_date, status, priority, manager_id)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$name, $description, $start_date, $end_date, $status, $priority, $manager_id]);

    $project_id = $pdo->lastInsertId();

    if (!empty($team_ids)) {
        $stmt = $pdo->prepare("INSERT INTO project_team (project_id, team_id) VALUES (?, ?)");
        foreach ($team_ids as $team_id) {
            $stmt->execute([$project_id, $team_id]);
        }
    }

    header("Location: ../../project_detail.php?id=$project_id");
    exit;
}

header("Location: ../../projects.php");
exit;
?>