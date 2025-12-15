<?php
// Modul Tim - Handler untuk buat tim baru

require_once '../../auth_check.php';
require_once '../../db_connect.php';

if ($_SESSION['user_role'] != 'ADMIN' && $_SESSION['user_role'] != 'MANAGER') {
    header("Location: ../../dashboard.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $description = $_POST['description'];

    if (isset($_POST['make_me_head']) && $_POST['make_me_head'] == '1') {
        $team_head_id = $_SESSION['user_id'];
    } else {
        $team_head_id = $_POST['team_head_id'];
    }

    $members = $_POST['members'] ?? [];
    $logo_path = null;

    // Handle logo upload
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png'];
        $max_size = 10 * 1024 * 1024; // 10MB in bytes
        $filename = $_FILES['logo']['name'];
        $filetype = pathinfo($filename, PATHINFO_EXTENSION);
        $filesize = $_FILES['logo']['size'];

        if (!in_array(strtolower($filetype), $allowed)) {
            // Invalid file type - redirect back with error
            header("Location: ../../form_team.php?error=invalid_file_type");
            exit;
        }

        if ($filesize > $max_size) {
            // File too large - redirect back with error
            header("Location: ../../form_team.php?error=file_too_large");
            exit;
        }

        $new_filename = 'team_logo_' . uniqid() . '.' . $filetype;
        $upload_path = '../../uploads/' . $new_filename;

        if (move_uploaded_file($_FILES['logo']['tmp_name'], $upload_path)) {
            $logo_path = $new_filename;
        }
    }

    $stmt = $pdo->prepare("
        INSERT INTO teams (name, description, team_head_id, logo_path)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$name, $description, $team_head_id, $logo_path]);

    $team_id = $pdo->lastInsertId();

    // Add team members
    foreach ($members as $member_id) {
        $stmt = $pdo->prepare("INSERT INTO team_members (team_id, user_id) VALUES (?, ?)");
        $stmt->execute([$team_id, $member_id]);
    }

    header("Location: ../../team_detail.php?id=$team_id");
    exit;
}

header("Location: ../../teams.php");
exit;
?>