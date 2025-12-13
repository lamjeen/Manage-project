<?php
session_start();

require_once 'db_connect.php';

 $error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    $role = 'MEMBER';

    if ($password != $confirm_password) {
        $error = "Passwords do not match!";
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->fetch()) {
            $error = "Email already registered!";
        } else {
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $email, $password, $role]);
            
            header("Location: login.php?registered=1");
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - WeProject</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%) !important;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .register-container {
            max-width: 400px;
            width: 100%;
            padding: 40px;
            background-color: var(--surface-color);
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
            border: 1px solid var(--border-color);
        }
        .register-container h2 {
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 1.5rem;
        }
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--accent-color) 100%) !important;
            border: none !important;
            border-radius: 25px !important;
            padding: 0.75rem 2rem !important;
            font-weight: 500 !important;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, var(--primary-hover) 0%, var(--primary-color) 100%) !important;
            transform: translateY(-1px);
            box-shadow: 0 6px 12px rgba(37, 99, 235, 0.3) !important;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <h2 class="text-center mb-4">Register</h2>
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <form action="register.php" method="post">
            <div class="mb-3">
                <label for="name" class="form-label">Full Name</label>
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
            
            <button type="submit" class="btn btn-primary w-100">Register</button>
        </form>
        <div class="text-center mt-3">
            <p>Already have an account? <a href="login.php">Login</a></p>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>