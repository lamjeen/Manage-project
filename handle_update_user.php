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

    if (empty($name) || empty($email) || empty($role)) {
        header("Location: users.php?error=empty_fields");
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: users.php?error=invalid_email");
        exit;
    }

    if (!in_array($role, ['MEMBER', 'MANAGER', 'ADMIN'])) {
        header("Location: users.php?error=invalid_role");
        exit;
    }

    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->execute([$email, $user_id]);
    if ($stmt->fetch()) {
        header("Location: users.php?error=email_exists");
        exit;
    }

    $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, role = ? WHERE id = ?");
    $stmt->execute([$name, $email, $role, $user_id]);

    header("Location: users.php?success=updated");
    exit;
}

header("Location: users.php");
exit;
?>
