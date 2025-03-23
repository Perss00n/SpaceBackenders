<?php
require_once __DIR__ . '/inc/session.php';
require_once __DIR__ . '/inc/connect.php';

try {
  $getUser = $_GET['user'];

  $userProfile = $db->get('users', "*", [
    "username" => $getUser
  ]);

  if (!$userProfile) {
    //Skicka dem till en error sida eller visa error-meddelande
    header('Location: ./index.php');
    exit();
  }
} catch (PDOException $e) {
  //Skicka dem till en error sida eller visa error-meddelande
  error_log("Error when fetching user profile: " . $e->getMessage());
  header('Location: ./index.php');
  exit();
}

if (isset($_SESSION['user'])) {
  $isAdminOrOwner = in_array($_SESSION['user']['role'], ['admin', 'owner']);
  $isSameUser = $_SESSION['user']['username'] === $userProfile['username'];

  // Tillåt ägare att ändra sin egen profilbild eller ta bort sitt eget konto
  if ($isSameUser) {
    $permissions = true;
  } else {
    // Endast en owner kan redigera eller ta bort en annan owner's avatar eller konto
    $permissions = $isAdminOrOwner && $userProfile['role'] !== 'owner';
  }
}


// Räkna antal inlägg av användaren
try {
  $userPosts = $db->count('posts', [
    "user_id" => $userProfile['id']
  ]);
} catch (PDOException $e) {
  //Skicka dem till en error sida eller visa error-meddelande
  error_log("Error when fetching user post count: " . $e->getMessage());
  header('Location: ./index.php');
  exit();
}

// Hämta alla inlägg av användaren
try {
  $userPost = $db->select('posts', "*", [
    "user_id" => $userProfile['id']
  ]);
} catch (PDOException $e) {
  //Skicka dem till en error sida eller visa error-meddelande
  error_log("Error when fetching user posts: " . $e->getMessage());
  header('Location: ./index.php');
  exit();
}

// Delete om användaren/admin vill ta bort det här kontot
if (isset($_POST['delete']) && $_POST['delete'] == 'delete') {
  $db->delete('users', ["username" => $userProfile['username']]);
  if ($_SESSION['user']['username'] == $userProfile['username']) {
    session_unset();
    session_destroy();
  }
  header("Location: ./index.php");
  exit();
}

include __DIR__ . '/inc/head.php';
?>

<div class="wrapper">
  <div class="profile-card">
    <!-- username, userprofile, usertype -->
    <div class="profile-user-pic-wrapper">
      <div class="profile-user-pic">
        <img class="profile-pic" src="get-image.php?username=<?php echo htmlspecialchars($userProfile['username']); ?>" alt="Profile Picture">
      </div>
      <?php if ($permissions): ?>
        <a class="change-picture-btn" href="change-picture.php?user=<?php echo htmlspecialchars($userProfile['username']); ?>">
          <button>Change picture</button>
        </a>
      <?php endif ?>
    </div>

    <div class="profile-user-info">
      <?= "<h1>" . htmlspecialchars($userProfile['username']) . "</h1>" ?>
      <!-- bild för roll -->
      <div class="profile-role-container">
        <?php
        if ($userProfile['role'] == 'owner') {
          echo '<i class="fas fa-crown"></i>';
          echo "<h3>Owner</h3>";
        } else if ($userProfile['role'] == 'admin') {
          echo '<i class="fas fa-user-astronaut"></i>';
          echo "<h3>Admin</h3>";
        } else {
          echo "<i class='fas fa-user'></i>";
          echo "<h3>User</h3>";
        } ?>
      </div>
      <div class="profile-stats">
        <!-- Bara till att slänga in mer stats -->
        <div>
          <p>Posts</p>
          <p><?= $userPosts; ?></p>
        </div>
        <div>
          <p>Joined</h3>
          <p><?= $userProfile['created_at'] ?></p>
        </div>
      </div>
    </div>
    <!-- Knapp för att ta bort konto -->
    <?php
    if ($permissions) {
      echo "<a id='delete-button' href='#popup1'>Delete Account</a>";
    }
    ?>
  </div>


  <!-- Tidigare inlägg -->
  <div class="profile-posts">
    <?php
    if (empty($userPosts)) {
      echo "<h1>" . $userProfile['username'] . " hasn't posted anything yet! </h1>";
    } else {
      echo "<h2>Posts by " . $userProfile['username'] . "</h2>";
    }
    ?>
    <?php
    if (!empty($userPosts)) {
      //Skriver ut alla inlägg av användaren
      echo "<ul>";
      foreach ($userPost as $post) {
        echo "<li>";
        echo "<a href='./post.php?post_id=" . $post['id'] . "'>" . htmlspecialchars($post['title']) . "</a>";
        echo "</li>";
      }
      echo "</ul>";
    }
    ?>
  </div>
  <!-- Slut på wrapper -->
</div>


<!-- Popup för delete-knappen -->
<div id="popup1" class="delete-overlay">
  <div class="delete-popup">
    <a class="close" href="#">&times;</a>
    <div class="delete-content">
      <h3>Are you sure you want to delete this account?</h3>
      <div>
        <form action="" method="post">
          <button id="yes-delete" type="submit" name="delete" value="delete">Yes</button>
        </form>
        <button id="no-delete"><a href="#">No</a></button>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/inc/footer.php' ?>