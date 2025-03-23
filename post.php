<?php
require_once __DIR__ . '/inc/session.php';
require_once __DIR__ . '/inc/connect.php';

// Hämta inlägget från databasen
if (isset($_GET['post_id']) && filter_var($_GET['post_id'], FILTER_VALIDATE_INT)) {

    $id = $_GET['post_id'];

    try {
        $post = $db->get('posts', [
            '[>]users' => ['user_id' => 'id'], // Joina users tabellen för att få användarnamn och roll på den som skapat inlägget
            '[>]post_likes' => ['posts.id' => 'post_id'] // Joina post_likes tabellen för att räkna gillningar
        ], [
            'posts.id',
            'posts.title',
            'posts.content',
            'posts.created_at',
            'users.username',
            'users.role',
            'likes' => Medoo\Medoo::raw('COUNT(post_likes.id)') // Räknar antal gillningar
        ], [
            'posts.id' => $id,
            'GROUP' => 'posts.id' // Viktigt för korrekt räkning av likes
        ]);
    } catch (PDOException $e) {
        error_log("Error when fetching post from db: " . $e->getMessage());
        $_SESSION['error_msg'] = "Something went wrong when trying to fetch the post from the database. Please try again.";
        header('Location: ./index.php');
        exit();
    }
} else {
    $_SESSION['error_msg'] = "Invalid post ID!";
    header('Location: ./index.php');
    exit();
}

// Hämta alla kommentarer till inlägget
try {
    $comments = $db->select('comments', [
        '[>]users' => ['user_id' => 'id'], // Joina users tabellen för att få användarnamn och roll på den som skapat kommentaren
        '[>]comment_likes' => ['id' => 'comment_id'] // Joina comment_likes tabellen för att räkna gillningar
    ], [
        'users.username',
        'users.role',
        'comments.id',
        'comments.user_id',
        'comments.created_at',
        'comments.content',
        'likes' => Medoo\Medoo::raw('COUNT(comment_likes.id)') // Räknar antal gillningar
    ], [
        'comments.post_id' => $id,
        'GROUP' => 'comments.id', // Viktigt för korrekt räkning av likes
        'ORDER' => ['comments.created_at' => 'DESC']
    ]);
} catch (PDOException $e) {
    error_log("Error when fetching comments from db: " . $e->getMessage());
    $_SESSION['error_msg'] = "Something went wrong when trying to fetch comments from the database. Please try again.";
    header('Location: ./index.php');
    exit();
}


// Kommer att behöva skicka infon till en annan sida med antingen forms, eller en länk.
// Skicka med forms - 'edit-post' som en id/int till 'update-post.php'

// logik för att kolla om knappen ska visas (admin, owner eller om man har lagt upp)
$permission = false;
// Kollar om $_SESSION är satt och man är admin/owner eller om man är skapare av inlägget.
try {
    if (isset($_SESSION['user']) && ($_SESSION['user']['role'] !== 'user' || $_SESSION['user']['username'] === $post['username'])) {
        // Kollar om användaren är 'owner' och sedan om postaren inte är 'owner' då bara en 'owner' ska kunna ta bort en annan 'owner'.
        if ($post['role'] !== 'owner' || $_SESSION['user']['role'] === 'owner') {
            $permission = true;
        }
    }
} catch (\Throwable $th) {
}

$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

include __DIR__ . '/inc/head.php';
?>

<div class="wrapper">

    <?php if (isset($_SESSION['error_msg'])) : ?>
        <p class="error"><?= htmlspecialchars($_SESSION['error_msg']) ?></p>
        <?php unset($_SESSION['error_msg']); ?>
    <?php endif; ?>
    <div class="glass-card">
        <div class="post-header">
            <div class="user-info">
                <img class="profile-pic" src="get-image.php?username=<?php echo htmlspecialchars($post['username']); ?>" alt="Profile Picture">
                <div class="user-details">
                    <div class="user-name-wrapper">
                        <span class="user-name"><?= "<a href='./profile.php?user=" . $post['username'] . "'>" . htmlspecialchars($post['username']) . "</a>"; ?></span>
                        <?php if ($post['role'] === 'owner')
                            echo "<i class='fas fa-crown'></i>";
                        elseif ($post['role'] === 'admin')
                            echo "<i class='fas fa-user-astronaut'></i>";
                        else
                            echo "<i class='fas fa-user'></i>";
                        ?>
                    </div>
                    <span class="post-date">
                        <?php
                        $createdAt = new DateTime($post['created_at']);
                        echo htmlspecialchars($createdAt->format('j F, Y H:i'));
                        ?>
                    </span>
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

        <h1 class="blog-title"><?php echo htmlspecialchars($post['title']) ?></h1>
        <p class="post-content"><?php echo nl2br(htmlspecialchars($post['content'])) ?></p>
        <?php
        // Om användaren har rättigheter att ta bort eller redigera inlägget, visa knapparna
        if ($permission): ?>
            <div class="post-actions">
                <form action="./update-post.php" method="post">
                    <button type="submit" name="edit-post" value="<?= $id ?>"><i class="fas fa-pen"></i></button>
                </form>

                <form method="POST" action="delete-post.php" onsubmit="return confirm ('Are you sure you want to delete this post?');">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <input type="hidden" name="post_id" value="<?= $id; ?>">
                    <button name="delete-post">
                        <i class="fas fa-trash"></i>
                    </button>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <div class="glass-card">
        <div class="post-user-info">
            <div class="user-details">
                <div class="username-wrapper">
                    <?php if (isset($_SESSION['user'])): ?>
                        <div class="post-user-name-wrapper">
                            <img class="profile-pic" src="get-image.php?username=<?php echo htmlspecialchars($_SESSION['user']['username']); ?>" alt="Profile Picture">
                            <span class="user-name"><?= "<a href='./profile.php?user=" . htmlspecialchars($_SESSION['user']['username']) . "'>" . htmlspecialchars($_SESSION['user']['username']) . "</a>"; ?></span>
                            <?php if ($_SESSION['user']['role'] === 'owner')
                                echo "<i class='fas fa-crown'></i>";
                            elseif ($_SESSION['user']['role'] === 'admin')
                                echo "<i class='fas fa-user-astronaut'></i>";
                            else
                                echo "<i class='fas fa-user'></i>";
                            ?>
                        </div>
                        <form method="POST" action="add-comment.php">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                            <input type="hidden" name="post_id" value="<?= htmlspecialchars($post['id']) ?>">
                            <textarea name="comment-input" class="comment-input scroll" type="text" required></textarea>
                            <button name="add-comment" class="add-comment">Add comment</button>
                        </form>
                    <?php else: ?>
                        <div class="post-user-info">
                            <div class="post-user-details">
                                <div class="user-name-wrapper">
                                    <div class="comment-login">
                                        <span class="comment-login-text">
                                            You have to be logged in to comment
                                        </span>
                                        <form action="./login.php">
                                            <button class="comment-login-btn">Log in</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif ?>
                </div>
            </div>
        </div>

        <h2 class="comment-h2">Comments: </h2>
        <?php if (is_array($comments) && !empty($comments)):
            foreach ($comments as $comment): ?>
                <div class="user-details">
                    <div class="user-name-wrapper comment-wrapper">
                        <div class="post-user-name-wrapper">
                            <img class="profile-pic" src="get-image.php?username=<?php echo htmlspecialchars($comment['username']); ?>" alt="Profile Picture">
                            <span class="user-name"><?= "<a href='./profile.php?user=" . $comment['username'] . "'>" . htmlspecialchars($comment['username']) . "</a>"; ?></span>
                            <?php if ($comment['role'] === 'owner')
                                echo "<i class='fas fa-crown'></i>";
                            elseif ($comment['role'] === 'admin')
                                echo "<i class='fas fa-user-astronaut'></i>";
                            else
                                echo "<i class='fas fa-user'></i>";
                            ?>
                            <span class="post-date">
                                <?php
                                $createdAt = new DateTime($comment['created_at']);
                                echo htmlspecialchars($createdAt->format('j F, Y H:i'));
                                ?>
                            </span>
                        </div>

                        <!-- Om användaren är inloggad, gör hjärtat klickbart, annars visa det som en vanlig ikon -->
                        <div class="comment-like-section">
                            <span class="like-count"><?= htmlspecialchars($comment['likes']) ?></span>
                            <?php if (isset($_SESSION['user'])) : ?>
                                <form method="POST" action="toggle-likes.php" class="like-form">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                    <input type="hidden" name="commentid" value="<?= htmlspecialchars($comment['id']) ?>">
                                    <input type="hidden" name="postid" value="<?= htmlspecialchars($post['id']) ?>">
                                    <input type="hidden" name="liketype" value="comment">
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
                    <div class="glass-card" style="margin-top: 15px;">
                        <span class="comment-content"><?= nl2br(htmlspecialchars($comment['content'])) ?></span>
                        <div class="comment-actions">
                            <!-- kontrollerar om användaren är inloggad och om användaren är admin/owner eller om användaren är den som har lagt upp kommentaren.
                            Kontrollerar också om användaren är owner och om kommentaren är owner, då ska
                            bara en owner kunna ta bort en annan owner.  -->
                            <?php if (
                                isset($_SESSION['user']) &&
                                (
                                    ($_SESSION['user']['role'] === 'admin' && $comment['role'] !== 'owner') ||
                                    $_SESSION['user']['role'] === 'owner' ||
                                    $_SESSION['user']['id'] === $comment['user_id']
                                )
                            ): ?>
                                <form method="POST" action="delete-comment.php">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                    <input type="hidden" name="post_id" value="<?= htmlspecialchars($post['id']) ?>">
                                    <input type="hidden" name="comment_id" value="<?= htmlspecialchars($comment['id']) ?>">
                                    <button type="submit" name="delete-comment" class="delete-comment-button" onclick="return confirm('Are you sure you want to delete this comment?')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="post-header">
                    </div>
                </div>
            <?php endforeach ?>
    </div>
<?php else: ?>
    <div class="glass-card">
        <div class="no-comments">
            <p>No comments yet =(</p>
            <br>
            <p>Be the first one to comment!</p>
        </div>
    </div>
<?php endif ?>

</div>
</div>
<?php include __DIR__ . '/inc/footer.php'; ?>