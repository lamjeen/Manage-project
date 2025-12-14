<?php
require_once 'db_connect.php';

$message = '';
$message_type = '';

// Check if admin already exists
$stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'ADMIN'");
$admin_count = $stmt->fetch()['count'];

$setup_completed = $admin_count > 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$setup_completed) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    $errors = [];

    if (empty($name)) $errors[] = 'Name is required';
    if (empty($email)) $errors[] = 'Email is required';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email format';
    if (empty($password)) $errors[] = 'Password is required';
    if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters';
    if ($password !== $confirm_password) $errors[] = 'Passwords do not match';

    // Check if email already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $errors[] = 'Email already exists';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, 'ADMIN')");
        $stmt->execute([$name, $email, $password]);

        $message = 'Admin user created successfully! File will be deleted automatically.';
        $message_type = 'success';

        // Auto-delete this file after successful setup
        register_shutdown_function(function() {
            $current_file = __FILE__;
            if (file_exists($current_file)) {
                unlink($current_file);
            }
        });
    } else {
        $message = implode('<br>', $errors);
        $message_type = 'error';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WeProject Setup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="text-center">WeProject Setup</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($setup_completed): ?>
                            <div class="alert alert-success">
                                Setup already completed! Admin user exists.
                            </div>
                            <div class="text-center mt-3">
                                <a href="login.php" class="btn btn-primary">Go to Login</a>
                            </div>
                        <?php else: ?>
                            <?php if (!empty($message)): ?>
                                <div class="alert alert-<?php echo $message_type === 'success' ? 'success' : 'danger'; ?>">
                                    <?php echo $message; ?>
                                </div>
                            <?php endif; ?>

                            <form method="post" action="">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Name</label>
                                    <input type="text" class="form-control" id="name" name="name" required>
                                </div>

                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>

                                <div class="mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>

                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirm Password</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>

                                <button type="submit" class="btn btn-primary w-100">Create Admin</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
