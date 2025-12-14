<?php
require_once 'auth_check.php';

require_once 'db_connect.php';

if ($_SESSION['user_role'] != 'ADMIN') {
    header("Location: dashboard.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_POST['id'];
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];

    $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, role = ? WHERE id = ?");
    $stmt->execute([$name, $email, $role, $user_id]);

    header("Location: users.php");
    exit;
}

header("Location: users.php");
exit;
?>
