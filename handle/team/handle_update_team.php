<?php
/**
 * Team Module - Update Team Handler
 * 2432003 - Muhammad Anugrah Wahyu Syahputra
 * Modul pengelolaan anggota dan struktur tim dalam aplikasi, termasuk role dan akses.
 */

require_once '../../auth_check.php';
require_once '../../db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $team_id = $_POST['team_id'];
    $name = $_POST['name'];
    $description = $_POST['description'];

    if (isset($_POST['make_me_head']) && $_POST['make_me_head'] == '1') {
        $team_head_id = $_SESSION['user_id'];
    } else {
        $team_head_id = $_POST['team_head_id'];
    }

    $members = $_POST['members'] ?? [];

    // Check if user has permission to update
    if ($_SESSION['user_role'] != 'ADMIN') {
        $stmt = $pdo->prepare("SELECT team_head_id FROM teams WHERE id = ?");
        $stmt->execute([$team_id]);
        $team = $stmt->fetch();

        if (!$team || $team['team_head_id'] != $_SESSION['user_id']) {
            header("Location: ../../teams.php");
            exit;
        }
    }

    // Get current logo path for cleanup
    $stmt = $pdo->prepare("SELECT logo_path FROM teams WHERE id = ?");
    $stmt->execute([$team_id]);
    $current_team = $stmt->fetch();
    $logo_path = $current_team['logo_path'];

    // Handle logo upload
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png'];
        $max_size = 10 * 1024 * 1024; // 10MB in bytes
        $filename = $_FILES['logo']['name'];
        $filetype = pathinfo($filename, PATHINFO_EXTENSION);
        $filesize = $_FILES['logo']['size'];

        if (!in_array(strtolower($filetype), $allowed)) {
            // Invalid file type - redirect back with error
            header("Location: ../../form_team.php?id=$team_id&error=invalid_file_type");
            exit;
        }

        if ($filesize > $max_size) {
            // File too large - redirect back with error
            header("Location: ../../form_team.php?id=$team_id&error=file_too_large");
            exit;
        }

        // Delete old logo
        if (!empty($logo_path)) {
            $old_logo_path = '../../uploads/' . $logo_path;
            if (file_exists($old_logo_path)) {
                unlink($old_logo_path);
            }
        }

        $new_filename = 'team_logo_' . uniqid() . '.' . $filetype;
        $upload_path = '../../uploads/' . $new_filename;

        if (move_uploaded_file($_FILES['logo']['tmp_name'], $upload_path)) {
            $logo_path = $new_filename;
        }
    }

    $stmt = $pdo->prepare("
        UPDATE teams SET
            name = ?,
            description = ?,
            team_head_id = ?,
            logo_path = ?
        WHERE id = ?
    ");
    $stmt->execute([$name, $description, $team_head_id, $logo_path, $team_id]);

    // Update team members
    $stmt = $pdo->prepare("DELETE FROM team_members WHERE team_id = ?");
    $stmt->execute([$team_id]);

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