<?php
require_once __DIR__ . '/inc/session.php';
require_once __DIR__ . '/inc/connect.php';

// Pagination inställningar
$postsPerPage = 5;
$currentPage = isset($_GET['page']) ? filter_var($_GET['page'], FILTER_VALIDATE_INT) : 1;
if (!$currentPage || $currentPage < 1) {
  $currentpage = 1;
}

$offset = ($currentPage - 1) * $postsPerPage;

// Räkna antalet inlägg
try {
  $totalPosts = $db->count('posts');
  $totalPages = ceil($totalPosts / $postsPerPage);
} catch (PDOException $e) {
  error_log("Error when counting posts from db: " . $e->getMessage());
  $_SESSION['error_msg'] = "Something went wrong when trying to count the posts from the database. Please try again.";
  header('Location: ./index.php');
  exit();
}

// Hämta alla inlägg
try {
  $posts = $db->select('posts', [
    '[>]users' => ['user_id' => 'id'], //Joina med users tabellen för att få användarnamn och roll på den som skapat inlägget
    '[>]post_likes' => ['id' => 'post_id'] // Joina med post_likes tabellen för att räkna gillningar
  ], [
    'posts.id',
    'posts.title',
    'posts.content',
    'posts.created_at',
    'users.username',
    'users.role',
    'likes' => Medoo\Medoo::raw('COUNT(post_likes.id)') // Räknar antal gillningar per inlägg
  ], [
    'GROUP' => 'posts.id', // Viktigt för att räkna gillningar korrekt
    'ORDER' => ['posts.created_at' => 'DESC'],
    'LIMIT' => [$offset, $postsPerPage]
  ]);
} catch (PDOException $e) {
  error_log("Error when fetching posts from db: " . $e->getMessage());
  $_SESSION['error_msg'] = "Something went wrong when trying to fetch posts from the database. Please try again.";
  header('Location: ./adminpanel.php');
  exit();
}

$_SESSION['csrf_token'] = bin2hex(random_bytes(32));


include __DIR__ . '/inc/head.php';
?>
<div class="wrapper">

  <?php if (isset($_SESSION['error_msg'])) : ?>
    <p class="error"><?= $_SESSION['error_msg'] ?></p>
    <?php unset($_SESSION['error_msg']); ?>
  <?php endif; ?>

  <?php if (!empty($posts)) : ?>
    <?php foreach ($posts as $post): ?>
      <div class="glass-card">
        <div class="post-header">
          <div class="user-info">
          <img class="profile-pic" src="get-image.php?username=<?php echo $post['username']; ?>" alt="Profile Picture">
            <div class="user-details">
              <div class="user-name-wrapper">
                <span class="user-name"><?= "<a href='./profile.php?user=" . $post['username'] . "'>" . htmlspecialchars($post['username']) . "</a>"; ?></span>
                <?php
                if ($post['role'] == 'owner') {
                  echo "<i class='fas fa-crown'></i>";
                } else if ($post['role'] == 'admin') {
                  echo "<i class='fas fa-user-astronaut'></i>";
                } else {
                  echo "<i class='fas fa-user'></i>";
                }
                ?>
              </div>
              <span class="post-date"><?= substr($post['created_at'], 0, 10); ?></span>
            </div>
          </div>

          <!-- Om användaren är inloggad, gör hjärtat klickbart, annars visa det som en vanlig ikon -->
          <div class="like-section">
            <span class="like-count"><?= htmlspecialchars($post['likes']) ?></span>
            <?php if (isset($_SESSION['user'])) : ?>
              <form method="POST" action="toggle-likes.php" class="like-form">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <input type="hidden" name="postid" value="<?= htmlspecialchars($post['id']) ?>">
                <input type="hidden" name="liketype" value="post">
                <input type="hidden" name="redirect_url" value="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>">
                <button type="submit" class="like-button-post" name="togglelike">
                  <i class="fas fa-heart"></i>
                </button>
              </form>
            <?php else : ?>
              <i class="fas fa-heart disabled"></i>
            <?php endif; ?>
          </div>

        </div>

        <h1 class="blog-title"><?= "<a href='./post.php?post_id=" . $post['id'] . "'>" . htmlspecialchars($post['title']) . "</a>"; ?></h1>
        <div class="blog-content">
          <p><?php echo nl2br(htmlspecialchars(substr($post['content'], 0, 400)));
              if (strlen($post['content']) > 400) {
                echo '<a class="read-more-link" href="./post.php?post_id=' . $post['id'] . '"> ... Read more</a>';
              }
              ?></p>
        </div>
      </div>
    <?php endforeach; ?>
  <?php else : ?>
    <div class="glass-card">
      <h1 style="text-align: center;">No posts found</h1>
    </div>
  <?php endif; ?>
  <!-- Visa endast sidknappar om det finns fler än 1 sida -->
  <?php if ($totalPages > 1): ?>
    <div class="page-buttons">
      <!-- Kontrollerar om den aktuella sidan är större än 1, om ja, visa en knapp som tar en till första sidan och en Prev knapp som då länkar till den nuvarande sidan minus 1 -->
      <?php if ($currentPage > 1): ?>
        <a class="prev-button" href="./index.php?page=1">&lt;&lt;</a>
        <a class="prev-button" href="./index.php?page=<?= $currentPage - 1 ?>">&lt; Prev</a>
      <?php endif; ?>

      <?php if ($currentPage > 2): ?>
        <a href="./index.php?page=<?= $currentPage - 2 ?>"><?= $currentPage - 2 ?></a>
      <?php endif; ?>

      <!-- Om den aktuella sidan är större än 1, visa en länk till föregående sida -->
      <?php if ($currentPage > 1): ?>
        <a href="./index.php?page=<?= $currentPage - 1 ?>"><?= $currentPage - 1 ?></a>
      <?php endif; ?>

      <!-- Visar en länk för den nuvarande sidan, markerad som aktiv -->
      <a class="active" href="./index.php?page=<?= $currentPage ?>"><?= $currentPage ?></a>

      <!-- Om den aktuella sidan är mindre än det totala antalet sidor, visa en länk till nästa sida -->
      <?php if ($currentPage < $totalPages): ?>
        <a href="./index.php?page=<?= $currentPage + 1 ?>"><?= $currentPage + 1 ?></a>
      <?php endif; ?>

      <?php if ($currentPage < $totalPages - 1): ?>
        <a href="./index.php?page=<?= $currentPage + 2 ?>"><?= $currentPage + 2 ?></a>
      <?php endif; ?>

      <!-- Om den aktuella sidan är mindre än det totala antalet sidor, visa en "Nästa"-knapp och en knapp som tar en till sista sidan -->
      <?php if ($currentPage < $totalPages): ?>
        <a class="next-button" href="./index.php?page=<?= $currentPage + 1 ?>">Next &gt;</a>
        <a class="next-button" href="./index.php?page=<?= $totalPages ?>">&gt;&gt;</a>
      <?php endif; ?>
    </div>
  <?php endif; ?>


</div>

<?php include __DIR__ . '/inc/footer.php' ?>