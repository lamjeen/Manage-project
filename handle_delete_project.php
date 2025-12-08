<?php
require_once 'auth_check.php';

require_once 'db_connect.php';

if ($_SESSION['user_role'] != 'ADMIN' && $_SESSION['user_role'] != 'MANAGER') {
    header("Location: projects.php");
    exit;
}

if (isset($_GET['id'])) {
    $project_id = $_GET['id'];
    
    $stmt = $pdo->prepare("SELECT manager_id FROM projects WHERE id = ?");
    $stmt->execute([$project_id]);
    $project = $stmt->fetch();
    
    if (!$project) {
        header("Location: projects.php");
        exit;
    }
    
    if ($_SESSION['user_role'] != 'ADMIN' && $project['manager_id'] != $_SESSION['user_id']) {
        header("Location: projects.php");
        exit;
    }
    
    $stmt = $pdo->prepare("DELETE FROM projects WHERE id = ?");
    $stmt->execute([$project_id]);
}

header("Location: projects.php");
exit;
?>