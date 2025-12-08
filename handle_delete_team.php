<?php
require_once 'auth_check.php';

require_once 'db_connect.php';

if ($_SESSION['user_role'] != 'ADMIN' && $_SESSION['user_role'] != 'MANAGER') {
    header("Location: teams.php");
    exit;
}

if (isset($_GET['id'])) {
    $team_id = $_GET['id'];
    
    $stmt = $pdo->prepare("DELETE FROM teams WHERE id = ?");
    $stmt->execute([$team_id]);
}

header("Location: teams.php");
exit;
?>