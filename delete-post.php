<?php
require_once __DIR__ . '/inc/session.php';
require_once __DIR__ . '/inc/connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete-post'])) {
    $postId = filter_var($_POST['post_id'], FILTER_VALIDATE_INT);

    if (!isset($_SESSION['user'])) {
        $_SESSION['error_msg'] = "You need to be logged in to delete a post!";
        header('Location: ./index.php');
        exit();
    }

    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error_msg'] = "Invalid CSRF token!";
        header('Location: ./index.php');
        exit();
    }

    if (!$postId) {
        $_SESSION['error_msg'] = "Invalid post ID!";
        header('Location: ./index.php');
        exit();
    }

    try {
        // Kontrollera om användaren har rätt att ta bort inlägget
        $post = $db->get('posts', ['user_id'], ['id' => $postId]);
        if ($post && ($_SESSION['user']['role'] === 'admin' || $_SESSION['user']['role'] === 'owner' || $_SESSION['user']['id'] === $post['user_id'])) {
            $db->delete('posts', ['id' => $postId]);
        } else {
            $_SESSION['error_msg'] = "You do not have permission to delete this post!";
        }
    } catch (PDOException $e) {
        error_log("Error when deleting post from db: " . $e->getMessage());
        $_SESSION['error_msg'] = "Something went wrong when trying to delete the post. Please try again.";
    }

    header('Location: ./index.php');
    exit();
}
