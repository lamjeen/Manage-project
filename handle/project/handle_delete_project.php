<?php
/**
 * Project Module - Delete Project Handler
 * 2432012 - Devin Ng
 * Modul yang mengelola proyek, termasuk informasi, tujuan, timeline, dan struktur keseluruhan.
 */

require_once '../../auth_check.php';
require_once '../../db_connect.php';

if ($_SESSION['user_role'] != 'ADMIN' && $_SESSION['user_role'] != 'MANAGER') {
    header("Location: ../../projects.php");
    exit;
}

if (isset($_POST['id'])) {
    $project_id = $_POST['id'];

    $stmt = $pdo->prepare("SELECT manager_id FROM projects WHERE id = ?");
    $stmt->execute([$project_id]);
    $project = $stmt->fetch();

    if (!$project) {
        header("Location: ../../projects.php");
        exit;
    }

    if ($_SESSION['user_role'] != 'ADMIN' && $project['manager_id'] != $_SESSION['user_id']) {
        header("Location: ../../projects.php");
        exit;
    }

    // Delete project teams first
    $stmt = $pdo->prepare("DELETE FROM project_team WHERE project_id = ?");
    $stmt->execute([$project_id]);

    // Delete project
    $stmt = $pdo->prepare("DELETE FROM projects WHERE id = ?");
    $stmt->execute([$project_id]);

    header("Location: ../../projects.php");
    exit;
}

header("Location: projects.php");
exit;
?>