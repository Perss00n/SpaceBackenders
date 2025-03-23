<?php
require_once __DIR__ . '/inc/session.php';
require_once __DIR__ . '/inc/connect.php';

if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
include __DIR__ . '/inc/head.php';
?>

<div class="wrapper">
    <div class="glass-card">
        <div class="post-header">
        </div>
        <h1 class="blog-title">Create new post</h1>
        <div class="blog-content">
            <?php
            if (isset($_SESSION['error_msg'])) {
                echo '<p class="error">' . $_SESSION['error_msg'] . '</p>';
                unset($_SESSION['error_msg']);
            }
            ?>
            <form class="new-post-form" method="POST" action="add-post.php">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <input name="new-post-title" class="new-post-title" type="text" placeholder="Title" required>
                <textarea name="new-post-content" class="new-post-content" placeholder="Add content here" required></textarea>
                <div>
                    <button name="create-post" class="create-post-btn" type="submit">Create post</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php include __DIR__ . '/inc/footer.php' ?>