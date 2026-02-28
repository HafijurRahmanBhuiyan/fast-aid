<?php

$previous_page = isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], $_SERVER['HTTP_HOST']) !== false 
    ? $_SERVER['HTTP_REFERER'] 
    : 'index.php';

if (isset($_SESSION['user_id'])) {
    $name = $_SESSION['name'] ?? 'User';
    $_SESSION['flash_message'] = "Goodbye, " . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . "! You have been logged out.";
    $_SESSION['flash_type'] = 'info';
}

$_SESSION = array();

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

header("Location: " . $previous_page);
exit;
