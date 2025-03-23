<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="stylesheets/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
  <title>SpaceBackenders</title>
</head>

<body>
  <header>
    <nav class="navbar">
      <a href="index.php" class="logo">
        <i class="fas fa-rocket"></i> SpaceBackenders
      </a>
      <ul class="nav-links">
        <li><a href="./index.php">Home</a></li>
        <?php
        if (isset($_SESSION['user'])) {
          echo '<li><a href="./profile.php?user=' . $_SESSION['user']['username'] . '">Profile</a></li>';
          echo '<li><a href="./new-post.php">New Post</a></li>';
          if ($_SESSION['user']['role'] === 'admin' || $_SESSION['user']['role'] == 'owner')
            echo '<li><a href="./adminpanel.php">Admin Panel</a></li>';
          echo '<li><a href="./logout.php">Log Out</a></li>';
        } else {
          echo '<li><a href="./register.php">Register</a></li>';
          echo '<li><a href="./login.php">Log In</a></li>';
        }
        ?>
      </ul>
    </nav>
  </header>