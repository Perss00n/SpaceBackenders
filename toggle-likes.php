<?php
require_once __DIR__ . '/inc/session.php';
require_once __DIR__ . '/inc/connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['togglelike'])) {
  $postId = filter_var($_POST['postid'], FILTER_VALIDATE_INT);
  $commentId = filter_var($_POST['commentid'], FILTER_VALIDATE_INT);
  $likeType = $_POST['liketype'];
  $redirectUrl = filter_var($_POST['redirect_url'], FILTER_SANITIZE_URL);

  if ($likeType === 'post' && !$postId) {
    $_SESSION['error_msg'] = "Invalid post id!";
    header('Location: ' . $redirectUrl);
    exit();
  }

  if ($likeType === 'comment' && !$commentId) {
    $_SESSION['error_msg'] = "Invalid comment id!";
    header('Location: ' . $redirectUrl);
    exit();
  }

  if ($likeType !== 'post' && $likeType !== 'comment') {
    $_SESSION['error_msg'] = "Invalid like type!";
    header('Location: ' . $redirectUrl);
    exit();
  }

  if (!isset($_SESSION['user'])) {
    $_SESSION['error_msg'] = "You need to be logged in to like a post or comment!";
    header('Location: ' . $redirectUrl);
    exit();
  }

  if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error_msg'] = "Invalid CSRF token!";
    header('Location: ' . $redirectUrl);
    exit();
  }

  try {
    if ($likeType === 'post') {
      // Kontrollera om användaren redan har gillat inlägget
      $likeExists = $db->has('post_likes', [
        'post_id' => $postId,
        'user_id' => $_SESSION['user']['id']
      ]);

      if ($likeExists) {
        // Ta bort like om den redan finns
        $db->delete('post_likes', [
          'post_id' => $postId,
          'user_id' => $_SESSION['user']['id']
        ]);
      } else {
        // Annars lägg till en like om den inte finns
        $db->insert('post_likes', [
          'post_id' => $postId,
          'user_id' => $_SESSION['user']['id']
        ]);
      }
    } elseif ($likeType === 'comment') {
      // Kontrollera om användaren redan har gillat kommentaren
      $likeExists = $db->has('comment_likes', [
        'comment_id' => $commentId,
        'user_id' => $_SESSION['user']['id']
      ]);

      if ($likeExists) {
        // Ta bort like om den redan finns
        $db->delete('comment_likes', [
          'comment_id' => $commentId,
          'user_id' => $_SESSION['user']['id']
        ]);
      } else {
        // Annars lägg till en like om den inte finns
        $db->insert('comment_likes', [
          'comment_id' => $commentId,
          'user_id' => $_SESSION['user']['id']
        ]);
      }
    }
  } catch (PDOException $e) {
    error_log("Error when toggling like: " . $e->getMessage());
    $_SESSION['error_msg'] = "Something went wrong when trying to toggle like. Please try again!";
    header('Location: ' . $redirectUrl);
    exit();
  }

  header('Location: ' . $redirectUrl);
  exit();
}
