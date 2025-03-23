<?php
require_once __DIR__ . '/inc/session.php';
require_once __DIR__ . '/inc/connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete-comment'])) {
  $commentId = filter_var($_POST['comment_id'], FILTER_VALIDATE_INT);
  $postId = filter_var($_POST['post_id'], FILTER_VALIDATE_INT);

  if (!isset($_SESSION['user'])) {
    $_SESSION['error_msg'] = "You need to be logged in to delete a comment!";
    header('Location: ./post.php?post_id=' . $postId);
    exit();
  }


  if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error_msg'] = "Invalid CSRF token!";
    header('Location: ./post.php?post_id=' . $postId);
    exit();
  }

  if (!$commentId || !$postId) {
    $_SESSION['error_msg'] = "Invalid post or comment id!";
    header('Location: ./post.php?post_id=' . $postId);
    exit();
  }

  try {
    // Kontrollera om användaren är admin, owner eller skaparen av kommentaren
    $comment = $db->get('comments', ['user_id'], ['id' => $commentId]);
    if ($comment && ($_SESSION['user']['role'] === 'admin' || $_SESSION['user']['role'] === 'owner' || $_SESSION['user']['id'] === $comment['user_id'])) {
      $db->delete('comments', ['id' => $commentId]);
    } else {
      $_SESSION['error_msg'] = "You do not have permission to delete this comment!";
    }
  } catch (PDOException $e) {
    error_log("Error when deleting comment from db: " . $e->getMessage());
    $_SESSION['error_msg'] = "Something went wrong when trying to delete the comment. Please try again.";
  }

  header('Location: ./post.php?post_id=' . $postId);
  exit();
}
