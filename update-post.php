<?php
require_once __DIR__ . '/inc/session.php';
require_once __DIR__ . '/inc/connect.php';

if (!isset($_SESSION['user']) && isset($_POST['edit-post'])) {
    header("Location: index.php");
    exit();
}

$error = "";
$titleError = "";
$contentError = "";

if (isset($_POST['post-id'])) {
    $id = $_POST['post-id'];
} 
else if (filter_var($_POST['edit-post'], FILTER_VALIDATE_INT)){
    $id = $_POST['edit-post'];
}
else {
    $error = "Invalid post ID.";
}
$post = $db->get('posts', "*", [
    "id" => $id
  ]);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm-update-post'])) {

    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid CSRF token.";
    } else {
        $title = trim($_POST["update-post-title"]);
        $content = trim($_POST["update-post-content"]);

        if (empty($title)) {
            $titleError = "A title is required.";
        } else if (empty($content)) {
            $contentError = "Text content is required.";
        } else {
            try {
                $db->update("posts", [
                    "title" => $title,
                    "content" => $content
                ], [
                    "id" => $id
                ]);
                header("Location: ./post.php?post_id=" . $id);
                exit();
            } catch (PDOException $e) {
                error_log("Error when updating post: " . $e->getMessage());
                $error = "Something went wrong. Please try again.";
            }
        }
    }
}

$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

include __DIR__ . '/inc/head.php';
?>

<div class="wrapper">
    <div class="glass-card">
        <div class="post-header">
        </div>
        <h1 class="blog-title">Edit post</h1>
        <div class="blog-content">
            <?php
            if ($error) {
                echo '<p style="color:red;">' . htmlspecialchars($error) . '</p>';
            }
            ?>
            <form class="new-post-form" method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <input type="hidden" name="post-id" value="<?= $id ?>">
                <input name="update-post-title" class="new-post-title" type="text" value="<?= $post['title'] ?>" required>
                <div class="new-post-error">
                    <?php
                    if (!empty($titleError)) {
                        echo htmlspecialchars($titleError);
                    }
                    ?>
                </div>
                <textarea name="update-post-content" class="new-post-content" required><?= $post['content'] ?></textarea>
                <div class="new-post-error">
                    <div>
                        <?php
                        if (!empty($contentError)) {
                            echo htmlspecialchars($contentError);
                        }
                        ?>
                    </div>
                    <button name="confirm-update-post" class="create-post-btn" type="submit"><i class="fas fa-check"></i> Update post</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php include __DIR__ . '/inc/footer.php' ?>