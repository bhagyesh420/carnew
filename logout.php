<?php
// DriveRent - Logout Handler
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

if (isset($_SESSION['user_id'])) {
    clear_remember_token($pdo, $_SESSION['user_id']);
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

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

flash_message('info', 'You have been signed out successfully.');
header('Location: ' . base_url('login.php'));
exit();
?>
