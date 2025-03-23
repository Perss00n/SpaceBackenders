<?php
require_once __DIR__ . '/inc/session.php';
require_once __DIR__ . '/inc/connect.php';

session_unset();
session_destroy();
header("Location: ./login.php");
exit();
