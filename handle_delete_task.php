<?php
require_once 'auth_check.php';

require_once 'db_connect.php';

if (isset($_GET['id'])) {
    $task_id = $_GET['id'];
    
    $stmt = $pdo->prepare("SELECT created_by_id FROM tasks WHERE id = ?");
    $stmt->execute([$task_id]);
    $task = $stmt->fetch();
    
    if (!$task) {
        header("Location: tasks.php");
        exit;
    }
    
    if ($_SESSION['user_role'] != 'ADMIN' && $_SESSION['user_role'] != 'MANAGER' && $task['created_by_id'] != $_SESSION['user_id']) {
        header("Location: tasks.php");
        exit;
    }
    
    $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ?");
    $stmt->execute([$task_id]);
}

header("Location: tasks.php");
exit;
?>