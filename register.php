<?php
require_once __DIR__ . '/inc/session.php';
require_once __DIR__ . '/inc/connect.php';

if (isset($_SESSION['user'])) {
  header("Location: index.php");
  exit();
}


$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['addnewaccount'])) {
  if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $error = "Invalid CSRF token.";
  } else {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $passwordagain = trim($_POST['passwordagain']);

    $checkusername = $db->get('users', 'username', [
      'username' => $username
    ]);
    $checkemail = $db->get('users', 'email', [
      'email' => $email
    ]);

    if ($checkusername) {
      $error = "The username is already in use.";
    } else if ($checkemail) {
      $error = "The email is already in use.";
    } else if (empty($username) || empty($email) || empty($password)) {
      $error = "All fields must be filled.";
    } elseif (strlen($password) < 8) {
      $error = "The password has to contain at least 8 characters.";
    } elseif ($password !== $passwordagain) {
      $error = "The passwords do not match.";
    } else {
      $hashed_password = password_hash($password, PASSWORD_DEFAULT);

      try {
        $db->insert('users', [
          'username' => $username,
          'email' => $email,
          'password' => $hashed_password
        ]);

        // Logga in användaren direkt efter att kontot har skapats
        $user = $db->get('users', '*', [
          'username' => $username
        ]);

        if (!empty($user)) {
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
  <div class="register-wrapper">
    <div class="register-container">
      <h1><i class="fas fa-user-plus"></i> New Account</h1>
      <?php
      if ($error) {
        echo '<p style="color:red;">' . htmlspecialchars($error) . '</p>';
      }
      ?>
      <form action="" method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
        <label for="username"><i class="fas fa-user"></i> Username:</label>
        <input type="text" id="username" name="username" placeholder="Enter your desired username" required> <br><br>
        <label for="email"><i class="fas fa-envelope"></i> Email:</label>
        <input type="email" id="email" name="email" placeholder="Enter your email" required> <br><br>
        <label for="password"><i class="fas fa-key"></i> Password:</label>
        <input type="password" id="password" name="password" placeholder="Enter your desired password" required> <br><br>
        <label for="passwordagain"><i class="fas fa-key"></i> Confirm Password:</label>
        <input type="password" id="passwordagain" name="passwordagain" placeholder="Enter your password again" required> <br><br>
        <div class="register-button-wrapper">
          <button type="submit" name="addnewaccount">Create Account</button>
        </div>
      </form>
      <p> Already have a account?</p>
      <a href="login.php"><i class="fas fa-key"></i> Click Here To Login</a>
    </div>
  </div>
</div>

<?php include __DIR__ . '/inc/footer.php' ?>