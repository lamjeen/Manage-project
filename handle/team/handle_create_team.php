<?php
/**
 * Team Module - Create Team Handler
 * 2432003 - Muhammad Anugrah Wahyu Syahputra
 * Modul pengelolaan anggota dan struktur tim dalam aplikasi, termasuk role dan akses.
 */

require_once 'auth_check.php';
require_once 'db_connect.php';

if ($_SESSION['user_role'] != 'ADMIN' && $_SESSION['user_role'] != 'MANAGER') {
    header("Location: dashboard.php");
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
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['logo']['name'];
        $filetype = pathinfo($filename, PATHINFO_EXTENSION);

        if (in_array(strtolower($filetype), $allowed)) {
            $new_filename = 'team_logo_' . uniqid() . '.' . $filetype;
            $upload_path = 'uploads/' . $new_filename;

            if (move_uploaded_file($_FILES['logo']['tmp_name'], $upload_path)) {
                $logo_path = $new_filename;
            }
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

    header("Location: team_detail.php?id=$team_id");
    exit;
}

header("Location: teams.php");
exit;
?>