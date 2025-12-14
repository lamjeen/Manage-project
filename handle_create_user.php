<?php
require_once 'auth_check.php';

require_once 'db_connect.php';

if ($_SESSION['user_role'] != 'ADMIN') {
    header("Location: dashboard.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role'];

    $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)");
    $stmt->execute([$name, $email, $password, $role]);

    header("Location: users.php");
    exit;
}

header("Location: users.php");
exit;
?>
