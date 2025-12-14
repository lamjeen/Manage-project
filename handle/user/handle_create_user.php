<?php
require_once '../../auth_check.php';

require_once '../../db_connect.php';

if ($_SESSION['user_role'] != 'ADMIN') {
    header("Location: ../../dashboard.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];

    if (isset($_POST['id']) && !empty($_POST['id'])) {
        // Update existing user
        $user_id = $_POST['id'];
        $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, role = ? WHERE id = ?");
        $stmt->execute([$name, $email, $role, $user_id]);
    } else {
        // Create new user
        $password = $_POST['password'];
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $email, $password, $role]);
    }

    header("Location: ../../users.php");
    exit;
}

header("Location: users.php");
exit;
?>
