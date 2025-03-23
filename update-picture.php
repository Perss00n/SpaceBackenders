<?php
$targetUsername = isset($_POST['target_user']) ? $_POST['target_user'] : $_SESSION['user']['username'];
$userid = $db->get('users', 'id', ['username' => $targetUsername]);
$error_msg = "";

if (!$userid) {
    $error_msg = "User not found.";
    return;
}


if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change-picture-upload'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid CSRF token.";
    } else {
        if (isset($_FILES['file-upload']) && $_FILES['file-upload']['error'] == UPLOAD_ERR_OK) {
            $profilepic = $_FILES['file-upload'];

            $imageFileType = strtolower(pathinfo($profilepic['name'], PATHINFO_EXTENSION));
            if (!in_array($imageFileType, ['jpg', 'jpeg', 'png'])) {
                $error_msg = "Sorry, not a valid image.";
            }

            $check = getimagesize($profilepic["tmp_name"]);
            if ($check === false) {
                $error_msg = "Sorry, the file is not a valid image.";
            }

            $imageData = file_get_contents($profilepic["tmp_name"]);
            $imageType = mime_content_type($profilepic["tmp_name"]);

            try {
                if ($db->has('images', ['user_id' => $userid])) {
                    $db->update('images', [
                        'image_data' => $imageData,
                        'mime_type' => $imageType
                    ], [
                        'user_id' => $userid
                    ]);
                } else {
                    $db->insert('images', [
                        'user_id' => $userid,
                        'image_data' => $imageData,
                        'mime_type' => $imageType
                    ]);
                }
                header('Location: change-picture.php?user=' . $targetUsername);
            } catch (\Throwable $th) {
                echo "Database error: " . $th->getMessage();
            }
        }
    }
}
