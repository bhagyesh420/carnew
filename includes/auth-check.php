<?php
// DriveRent - Customer Authorization Guard
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

// Auto-login via Remember Me cookie if session expired
if (!is_logged_in() && isset($_COOKIE['driverent_remember']) && isset($pdo)) {
    check_remember_token($pdo);
}

if (!is_logged_in()) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    if (strpos($_SERVER['REQUEST_URI'], 'booking.php') !== false) {
        flash_message('info', 'Please sign in or create an account first. Once signed in, you will be redirected straight to your booking.');
    } else {
        flash_message('warning', 'Please sign in to access your dashboard and manage bookings.');
    }
    header('Location: ' . base_url('login.php'));
    exit();
}

$user = isset($pdo) ? get_logged_user($pdo) : null;
if (!$user) {
    // Session user ID does not exist in DB
    unset($_SESSION['user_id']);
    unset($_SESSION['user_name']);
    unset($_SESSION['user_email']);
    unset($_SESSION['user_role']);
    flash_message('warning', 'Your session expired. Please sign in again.');
    header('Location: ' . base_url('login.php'));
    exit();
}
?>
