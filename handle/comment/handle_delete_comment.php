<?php
/**
 * Comment/Notes Module - Delete Comment Handler
 * 2432050 - Nicholas Syahputra
 * Modul yang menyediakan fitur komentar dan catatan sebagai ruang komunikasi antar anggota.
 */

require_once '../../auth_check.php';
require_once '../../db_connect.php';

if (isset($_POST['id']) && isset($_POST['task_id'])) {
    $comment_id = $_POST['id'];
    $task_id = $_POST['task_id'];

    $stmt = $pdo->prepare("SELECT author_id FROM comments WHERE id = ?");
    $stmt->execute([$comment_id]);
    $comment = $stmt->fetch();

    if ($comment) {
        if ($_SESSION['user_role'] == 'ADMIN' || $_SESSION['user_role'] == 'MANAGER' || $comment['author_id'] == $_SESSION['user_id']) {
            $stmt = $pdo->prepare("DELETE FROM comments WHERE id = ?");
            $stmt->execute([$comment_id]);
        }
    }

    header("Location: ../../task_detail.php?id=$task_id");
    exit;
}

header("Location: ../../projects.php");
exit;
?>