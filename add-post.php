<?php

require_once __DIR__ . '/inc/session.php';
require_once __DIR__ . '/inc/connect.php';

if (!isset($_SESSION['user'])) {
    $_SESSION['error_msg'] = "You need to be logged in to add a post!";
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create-post'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error_msg'] = "Invalid CSRF token!";
        header("Location: new-post.php");
        exit();
    }

    $title = trim($_POST['new-post-title']);
    $content = trim($_POST['new-post-content']);
    $userId = $_SESSION['user']['id'];

    if (empty($title) || empty($content)) {
        $_SESSION['error_msg'] = "Title and content cannot be empty!";
        header("Location: new-post.php");
        exit();
    }

    try {
        // Lägg till inlägget i databasen
        $db->insert('posts', [
            'title' => $title,
            'content' => $content,
            'user_id' => $userId,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        header("Location: index.php");
        exit();
    } catch (PDOException $e) {
        error_log("Error when adding post to db: " . $e->getMessage());
        $_SESSION['error_msg'] = "Something went wrong when trying to add the post. Please try again.";
        header("Location: new-post.php");
        exit();
    }
}
