<?php
require_once __DIR__ . '/inc/session.php';
require_once __DIR__ . '/inc/connect.php';


// Kontrollera om användaren är inloggad och har admin eller owner roll
if (!isset($_SESSION['user']['role']) || !in_array($_SESSION['user']['role'], ['admin', 'owner'])) {
  header('Location: ./index.php');
  exit();
}

// Pagination inställningar
$users_per_page = 15;
$current_page = isset($_GET['page']) ? filter_var($_GET['page'], FILTER_VALIDATE_INT) : 1;
if (!$current_page || $current_page < 1) {
  $current_page = 1;
}

// Räkna ut offset för paginering
$offset = ($current_page - 1) * $users_per_page;

// Räkna antalet användare
try {
  $total_users = $db->count('users');
  $total_pages = ceil($total_users / $users_per_page);
} catch (PDOException $e) {
  error_log("Error when counting users from db: " . $e->getMessage());
  $_SESSION['error_msg'] = "Something went wrong when trying to count users from the database. Please try again.";
  header('Location: ./adminpanel.php');
  exit();
}

// Hämta alla användare
try {
  $users = $db->select('users', '*', [
    'ORDER' => ['role' => 'DESC'],
    'LIMIT' => [$offset, $users_per_page]
  ]);
} catch (PDOException $e) {
  error_log("Error when fetching users from db: " . $e->getMessage());
  $_SESSION['error_msg'] = "Something went wrong when trying to fetch users from the database. Please try again.";
  header('Location: ./adminpanel.php');
  exit();
}

// Ta bort användare
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['deleteuser']) && isset($_GET['userid'])) {

  $id = filter_var($_GET['userid'], FILTER_VALIDATE_INT);

  if (!$id) {
    $_SESSION['error_msg'] = "Invalid user id!";
    header('Location: ./adminpanel.php');
    exit();
  }

  // Hämta användarens data som ska raderas
  try {
    $userToDelete = $db->get('users', '*', ['id' => $id]);
  } catch (PDOException $e) {
    error_log("Error when fetching user data from db: " . $e->getMessage());
    $_SESSION['error_msg'] = "Something went wrong when trying to fetch user data from the database. Please try again!";
    header('Location: ./adminpanel.php');
    exit();
  }

  if (!$userToDelete) {
    $_SESSION['error_msg'] = "User not found!";
    header('Location: ./adminpanel.php');
    exit();
  }

  // Förhindra att en admin tar bort sig själv
  if ($_SESSION['user']['id'] == $id) {
    $_SESSION['error_msg'] = "You can't delete yourself!";
    header('Location: ./adminpanel.php');
    exit();
  }

  // Förhindra att en admin tar bort en ägare
  if ($userToDelete['role'] == 'owner') {
    $_SESSION['error_msg'] = "You can't delete the site owner!";
    header('Location: ./adminpanel.php');
    exit();
  }

  // Tillsist, radera användaren
  try {
    $db->delete('users', ['id' => $id]);

    // Om den raderade användaren är inloggad, avsluta deras session
    if (isset($_SESSION['user']) && $_SESSION['user']['id'] == $id) {
      session_unset();
      session_destroy();
      header('Location: ./index.php');
      exit();
    }

    $_SESSION['success_msg'] = "User successfully deleted!";
    header('Location: ./adminpanel.php');
    exit();
  } catch (PDOException $e) {
    error_log("Error when deleting user from db: " . $e->getMessage());
    $_SESSION['error_msg'] = "Something went wrong when trying to delete the user. Please try again!";
    header('Location: ./adminpanel.php');
    exit();
  }
}


if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['changerole']) && isset($_GET['id'])) {

  $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);

  if (!$id) {
    $_SESSION['error_msg'] = "Invalid user id!";
    header('Location: ./adminpanel.php');
    exit();
  }

  // Hämta användaren som ska uppdateras
  try {
    $userToChangeRole = $db->get('users', '*', ['id' => $id]);
  } catch (PDOException $e) {
    error_log("Error when fetching user data from db: " . $e->getMessage());
    $_SESSION['error_msg'] = "Something went wrong when trying to fetch user data from the database. Please try again!";
    header('Location: ./adminpanel.php');
    exit();
  }

  if (!$userToChangeRole) {
    $_SESSION['error_msg'] = "User not found!";
    header('Location: ./adminpanel.php');
    exit();
  }

  // Förhindra att en admin ändrar sin egen roll
  if ($_SESSION['user']['id'] == $id) {
    $_SESSION['error_msg'] = "You can't change your own role!";
    header('Location: ./adminpanel.php');
    exit();
  }

  // Förhindra att en admin ändrar en ägares roll
  if ($userToChangeRole['role'] == 'owner') {
    $_SESSION['error_msg'] = "You can't change the site owner's role!";
    header('Location: ./adminpanel.php');
    exit();
  }

  // Tillsist, ändra användarens roll
  try {
    // Tillåt bara att ändra rollen till admin eller user, inte owner
    $newRole = $userToChangeRole['role'] === 'user' ? 'admin' : 'user'; //// Om användaren är en admin, ändra till user och vice versa
    $db->update('users', ['role' => $newRole], ['id' => $id]);

    $_SESSION['success_msg'] = "User role successfully changed!";
    header('Location: ./adminpanel.php');
    exit();
  } catch (PDOException $e) {
    error_log("Error when changing user role in db: " . $e->getMessage());
    $_SESSION['error_msg'] = "Something went wrong when trying to change the users role. Please try again!";
    header('Location: ./adminpanel.php');
    exit();
  }
}




include __DIR__ . '/inc/head.php';
?>

<div class="wrapper">
  <div class="glass-card">
    <div class="admin-page-heading">
      <i class="fas fa-key"></i>
      <h1>Manage Users</h1>
      <i class="fas fa-key"></i>
    </div>
    <?php if (isset($_SESSION['error_msg'])) : ?>
      <p class="error"><?= htmlspecialchars($_SESSION['error_msg']) ?></p>
      <?php unset($_SESSION['error_msg']); ?>
    <?php elseif (isset($_SESSION['success_msg'])) : ?>
      <p class="success"><?= htmlspecialchars($_SESSION['success_msg']) ?></p>
      <?php unset($_SESSION['success_msg']); ?>
    <?php endif; ?>
    <table class="admin-table">
      <thead>
        <tr>
          <th>Username</th>
          <th>Email</th>
          <th>Role</th>
          <th>Joined</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($users) && is_array($users)) : ?>
          <?php foreach ($users as $user) : ?>
            <tr>
              <td><a href="profile.php?user=<?= htmlspecialchars($user['username']) ?>"><?= htmlspecialchars($user['username']) ?></a></td>
              <td><?= htmlspecialchars($user['email']) ?></td>
              <td><?= htmlspecialchars($user['role']) ?></td>
              <td><?= htmlspecialchars(date('d F, Y')) ?></td>
              <td>
                <form action="" method="get" class="inline-form">
                  <input type="hidden" name="userid" value="<?= htmlspecialchars($user['id']) ?>">
                  <input type="submit" name="deleteuser" value="Delete" onclick="return confirm('Are you sure you want to delete this user?')">
                </form>
                <form action="" method="get" class="inline-form">
                  <input type="hidden" name="id" value="<?= htmlspecialchars($user['id']) ?>">
                  <input type="submit" name="changerole" value="Change Role" onclick="return confirm('Are you sure you want to change this user\'s role?')">
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else : ?>
          <tr>
            <td colspan="5" style="text-align: center;">No users found =(</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>

    <!-- Visa endast sidknappar om det finns fler än 1 sida -->
    <?php if ($total_pages > 1): ?>
      <div class="page-buttons">

        <?php if ($current_page > 1): ?>
          <a class="prev-button" href="./adminpanel.php?page=1">&lt;&lt;</a>
          <a class="prev-button" href="./adminpanel.php?page=<?= $current_page - 1 ?>">&lt; Prev</a>
        <?php endif; ?>

        <?php if ($current_page > 2): ?>
          <a href="./adminpanel.php?page=<?= $current_page - 2 ?>"><?= $current_page - 2 ?></a>
        <?php endif; ?>

        <?php if ($current_page > 1): ?>
          <a href="./adminpanel.php?page=<?= $current_page - 1 ?>"><?= $current_page - 1 ?></a>
        <?php endif; ?>

        <a class="active" href="./adminpanel.php?page=<?= $current_page ?>"><?= $current_page ?></a>

        <?php if ($current_page < $total_pages): ?>
          <a href="./adminpanel.php?page=<?= $current_page + 1 ?>"><?= $current_page + 1 ?></a>
        <?php endif; ?>

        <?php if ($current_page < $total_pages - 1): ?>
          <a href="./adminpanel.php?page=<?= $current_page + 2 ?>"><?= $current_page + 2 ?></a>
        <?php endif; ?>

        <?php if ($current_page < $total_pages): ?>
          <a class="next-button" href="./adminpanel.php?page=<?= $current_page + 1 ?>">Next &gt;</a>
          <a class="next-button" href="./adminpanel.php?page=<?= $total_pages ?>">&gt;&gt;</a>
        <?php endif; ?>
      </div>
    <?php endif; ?>

  </div>
</div>

<?php include __DIR__ . '/inc/footer.php'; ?>