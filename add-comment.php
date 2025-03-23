<?php
require_once __DIR__ . '/inc/session.php';
require_once __DIR__ . '/inc/connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add-comment'])) {

  $content = trim($_POST["comment-input"]);
  $userid = filter_var($_SESSION["user"]["id"], FILTER_VALIDATE_INT);
  $id = filter_var($_POST["post_id"], FILTER_VALIDATE_INT);

  if (!isset($_SESSION['user'])) {
    $_SESSION['error_msg'] = "You need to be logged in to comment!";
    header('Location: ./post.php?post_id=' . $id);
    exit();
  }

  if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error_msg'] = "Invalid CSRF token.";
    header('Location: ./post.php?post_id=' . $id);
    exit();
  }

  if (!$id || !$userid) {
    $_SESSION['error_msg'] = "Invalid post or user id!";
    header('Location: ./post.php?post_id=' . $id);
    exit();
  }


  if (empty($content)) {
    $_SESSION['error_msg'] = "Comment cannot be empty!";
    header('Location: ./post.php?post_id=' . $id);
    exit();
  }

  try {
    $db->insert('comments', [
      'user_id' => $userid,
      'post_id' => $id,
      'content' => $content
    ]);
  } catch (PDOException $e) {
    error_log("Error when adding comment to db: " . $e->getMessage());
    $_SESSION['error_msg'] = "Something went wrong when trying to add the comment. Please try again.";
  }

  header('Location: ./post.php?post_id=' . $id);
  exit();
}
