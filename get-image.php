<?php 
require_once __DIR__ . '/inc/session.php';
require_once __DIR__ . '/inc/connect.php';

$username = isset($_GET['username']) ? filter_var($_GET['username'], FILTER_SANITIZE_STRING) : null;

if ($username) {
    try {
        $image = $db->get("users", [
            "[>]images" => ["id" => "user_id"]
        ], [
            "images.image_data",
            "images.mime_type"
        ], [
            "users.username" => $username 
        ]);

        if (!empty($image['image_data'])) {
            header("Content-Type: " . $image["mime_type"]);
            echo $image["image_data"];
        } else {
            header("Content-Type: image/png");
            readfile(__DIR__ . "/images/profile-pic-wojak.png");
        }
    } catch (\Throwable $th) {
        error_log("Database error: " . $th->getMessage());
        echo "An error occurred. Please try again.";
    }
} else {
    echo "Invalid username.";
}



