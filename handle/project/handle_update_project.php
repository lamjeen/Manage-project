<?php
// Modul Projek - Handler untuk update projek

require_once '../../auth_check.php';
require_once '../../db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $project_id = $_POST['project_id'];
    $name = $_POST['name'];
    $description = $_POST['description'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $status = $_POST['status'];
    $priority = $_POST['priority'];
    $manager_id = $_POST['manager_id'];
    $team_ids = $_POST['team_ids'] ?? [];

    // Check if user has permission to update
    if ($_SESSION['user_role'] != 'ADMIN') {
        $stmt = $pdo->prepare("SELECT manager_id FROM projects WHERE id = ?");
        $stmt->execute([$project_id]);
        $project = $stmt->fetch();

        if (!$project || $project['manager_id'] != $_SESSION['user_id']) {
            header("Location: ../../projects.php");
            exit;
        }
    }

    $stmt = $pdo->prepare("
        UPDATE projects SET
            name = ?,
            description = ?,
            start_date = ?,
            end_date = ?,
            status = ?,
            priority = ?,
            manager_id = ?
        WHERE id = ?
    ");
    $stmt->execute([$name, $description, $start_date, $end_date, $status, $priority, $manager_id, $project_id]);

    // Update project teams
    $stmt = $pdo->prepare("DELETE FROM project_team WHERE project_id = ?");
    $stmt->execute([$project_id]);

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