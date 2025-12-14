<?php
/**
 * Comment/Notes Module - Update Comment Handler
 * 2432050 - Nicholas Syahputra
 * Modul yang menyediakan fitur komentar dan catatan sebagai ruang komunikasi antar anggota.
 */

require_once 'auth_check.php';
require_once 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $comment_id = $_POST['comment_id'];
    $content = $_POST['content'];
    $is_pinned = isset($_POST['is_pinned']) ? 1 : 0;

    // Check if user has permission to update
    $stmt = $pdo->prepare("SELECT author_id, task_id FROM comments WHERE id = ?");
    $stmt->execute([$comment_id]);
    $comment = $stmt->fetch();

    if (!$comment) {
        header("Location: dashboard.php");
        exit;
    }

    if ($_SESSION['user_role'] != 'ADMIN' && $_SESSION['user_role'] != 'MANAGER' && $comment['author_id'] != $_SESSION['user_id']) {
        header("Location: task_detail.php?id=" . $comment['task_id']);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE comments SET content = ?, is_pinned = ? WHERE id = ?");
    $stmt->execute([$content, $is_pinned, $comment_id]);

    header("Location: task_detail.php?id=" . $comment['task_id']);
    exit;
}

header("Location: projects.php");
exit;
?>