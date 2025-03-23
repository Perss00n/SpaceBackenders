<?php
require_once __DIR__ . '/inc/session.php';
require_once __DIR__ . '/inc/connect.php';

if (isset($_SESSION['user'])) {
  header("Location: index.php");
  exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['loginuser'])) {
  if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $error = "Invalid CSRF token.";
  } else {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
      $error = "All fields must be filled.";
    } else {
      try {
        $user = $db->get('users', '*', ['username' => $username]);

        if (!$user || !password_verify($password, $user['password'])) {
          $error = "Username or password are incorrect.";
        } else {
          session_regenerate_id();
          unset($user['password']); // Ta bort lösenordet från arrayen så att det inte sparas i sessionen
          $_SESSION['user'] = $user;
          header("Location: index.php");
          exit();
        }
      } catch (Exception $e) {
        $error = "Something went wrong. Please try again.";
      }
    }
  }
}

$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

include __DIR__ . '/inc/head.php';
?>

<div class="wrapper">
  <div class="login-wrapper">
    <div class="login-container">
      <h1><i class="fas fa-lock"></i> Log In</h1>
      <?php
      if ($error) {
        echo '<p style="color:red;">' . htmlspecialchars($error) . '</p>';
      }
      ?>
      <form action="" method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
        <label for="username"><i class="fas fa-user"></i> Username:</label>
        <input type="text" name="username" id="username" placeholder="Enter your username" required><br><br>
        <label for="password"><i class="fas fa-key"></i> Password:</label>
        <input type="password" id="password" name="password" placeholder="Enter your password" required><br><br>
        <div class="login-button-wrapper">
          <button type="submit" name="loginuser">Login</button>
        </div>
      </form>

      <p> Don't have an account yet? Signup below:</p>
      <a href="register.php"><i class="fas fa-user-plus"></i> Create Account</a>
    </div>
  </div>
</div>

<?php include __DIR__ . '/inc/footer.php' ?>