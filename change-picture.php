<?php
require_once __DIR__ . '/inc/session.php';
require_once __DIR__ . '/inc/connect.php';
include 'update-picture.php';

if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}

// Kontrollera om en admin eller owner försöker ändra någon annans profilbild
$targetUsername = isset($_GET['user']) ? $_GET['user'] : $_SESSION['user']['username'];
if ($_SESSION['user']['username'] !== $targetUsername && !in_array($_SESSION['user']['role'], ['admin', 'owner'])) {
    $_SESSION['error_msg'] = "You do not have permission to change this user's profile picture!";
    header("Location: index.php");
    exit();
}

// Generera CSRF-token
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

require_once __DIR__ . '/inc/head.php';
?>

<div class="wrapper">
    <div class="glass-card">
        <div class="change-picture-showcase">
            <div class="profile-user-pic">
                <img class="profile-pic" src="get-image.php?username=<?php echo htmlspecialchars($targetUsername); ?>" alt="Profile Picture">
            </div>
            <div class="change-picture-file">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <input type="hidden" name="target_user" value="<?php echo htmlspecialchars($targetUsername); ?>">
                    <input name="file-upload" type="file" accept="image/*" required>
                    <button type="submit" name="change-picture-upload" class="change-picture-upload">Upload</button>
                </form>
            </div>
            <div class="image-error-msg">
                <?php
                echo $error_msg;
                ?>
            </div>
        </div>

        <a href="profile.php?user=<?php echo htmlspecialchars($targetUsername); ?>"><button class="change-picture-cancel">Back to profile</button></a>
    </div>
</div>

<?php require_once __DIR__ . '/inc/footer.php'; ?>