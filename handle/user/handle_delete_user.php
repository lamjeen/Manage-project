<?php
// Modul User - Handler untuk hapus user

require_once '../../auth_check.php';
require_once '../../db_connect.php';

if ($_SESSION['user_role'] != 'ADMIN') {
    header("Location: ../../dashboard.php");
    exit;
}

if (isset($_POST['id'])) {
    $user_id = $_POST['id'];
    
    if ($user_id == $_SESSION['user_id']) {
        header("Location: ../../users.php");
        exit;
    }
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM projects WHERE manager_id = ?");
    $stmt->execute([$user_id]);
    $manages_projects = $stmt->fetch()['count'] > 0;

    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM tasks WHERE created_by_id = ?");
    $stmt->execute([$user_id]);
    $created_tasks = $stmt->fetch()['count'] > 0;

    if ($manages_projects || $created_tasks) {
        header("Location: ../../users.php");
        exit;
    }

    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
}

header("Location: ../../users.php");
exit;
?>