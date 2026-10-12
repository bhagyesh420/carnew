<?php
// DriveRent - Delete Vehicle Action Handler
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (!is_admin()) {
    header('Location: ' . base_url('admin/login.php'));
    exit();
}

$car_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($car_id > 0) {
    try {
        $stmt = $pdo->prepare("DELETE FROM cars WHERE id = ?");
        $stmt->execute([$car_id]);
        flash_message('success', 'Vehicle record deleted successfully.');
    } catch (PDOException $e) {
        flash_message('danger', 'Cannot delete vehicle with active booking records.');
    }
}

header('Location: ' . base_url('admin/cars.php'));
exit();
?>
