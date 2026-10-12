<?php
// DriveRent - Admin Header Shell
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

// Require Admin Privileges
if (!is_admin() && basename($_SERVER['PHP_SELF']) !== 'login.php') {
    flash_message('danger', 'Administrator privileges required.');
    header('Location: ' . base_url('admin/login.php'));
    exit();
}

$admin_user = is_admin() ? get_logged_user($pdo) : null;
$page_title = isset($page_title) ? $page_title . ' - Admin Panel' : 'DriveRent Executive Admin Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>

    <!-- Google Fonts: Plus Jakarta Sans, Outfit & Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Outfit:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

    <!-- Admin CSS -->
    <link rel="stylesheet" href="<?php echo base_url('assets/css/admin.css?v=' . time()); ?>">
</head>
<body class="admin-body">
