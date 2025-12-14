<?php
/**
 * Team Module - Delete Team Handler
 * 2432003 - Muhammad Anugrah Wahyu Syahputra
 * Modul pengelolaan anggota dan struktur tim dalam aplikasi, termasuk role dan akses.
 */

require_once 'auth_check.php';
require_once 'db_connect.php';

if ($_SESSION['user_role'] != 'ADMIN' && $_SESSION['user_role'] != 'MANAGER') {
    header("Location: teams.php");
    exit;
}

if (isset($_GET['id'])) {
    $team_id = $_GET['id'];

    // Check if user has permission to delete
    if ($_SESSION['user_role'] != 'ADMIN') {
        $stmt = $pdo->prepare("SELECT team_head_id FROM teams WHERE id = ?");
        $stmt->execute([$team_id]);
        $team = $stmt->fetch();

        if (!$team || $team['team_head_id'] != $_SESSION['user_id']) {
            header("Location: teams.php");
            exit;
        }
    }

    // Get logo path for cleanup
    $stmt = $pdo->prepare("SELECT logo_path FROM teams WHERE id = ?");
    $stmt->execute([$team_id]);
    $team = $stmt->fetch();

    // Delete team members first
    $stmt = $pdo->prepare("DELETE FROM team_members WHERE team_id = ?");
    $stmt->execute([$team_id]);

    // Delete team
    $stmt = $pdo->prepare("DELETE FROM teams WHERE id = ?");
    $stmt->execute([$team_id]);

    // Delete logo file if exists
    if (!empty($team['logo_path'])) {
        $logo_path = 'uploads/' . $team['logo_path'];
        if (file_exists($logo_path)) {
            unlink($logo_path);
        }
    }

    header("Location: teams.php");
    exit;
}

header("Location: teams.php");
exit;
?>