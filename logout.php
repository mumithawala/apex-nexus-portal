<?php
// Start session
session_start();

// Include URLs
require_once 'includes/urls.php';

// Unset all session variables
$_SESSION = [];

// Destroy session completely
session_destroy();

// Destroy session cookie (important)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Redirect to login page
header("Location: " . $BASE_URL . "/login.php");
exit;