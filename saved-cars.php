<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth-check.php';

$user = get_logged_user($pdo);

// Handle Save / Unsave Action
if (isset($_GET['toggle_id'])) {
    $car_id = (int)$_GET['toggle_id'];
    $check = $pdo->prepare("SELECT id FROM saved_cars WHERE user_id = ? AND car_id = ?");
    $check->execute([$user['id'], $car_id]);
    $existing = $check->fetch();

    if ($existing) {
        $del = $pdo->prepare("DELETE FROM saved_cars WHERE id = ?");
        $del->execute([$existing['id']]);
        flash_message('info', 'Vehicle removed from your saved list.');
    } else {
        $ins = $pdo->prepare("INSERT INTO saved_cars (user_id, car_id, created_at) VALUES (?, ?, CURRENT_TIMESTAMP)");
        $ins->execute([$user['id'], $car_id]);
        flash_message('success', 'Vehicle saved to your wishlist!');
    }
    header('Location: ' . base_url('saved-cars.php'));
    exit();
}

// Fetch All Saved Vehicles for Current User
$stmt = $pdo->prepare("SELECT s.id as saved_id, c.* 
    FROM saved_cars s 
    JOIN cars c ON s.car_id = c.id 
    WHERE s.user_id = ? 
    ORDER BY s.created_at DESC");
$stmt->execute([$user['id']]);
$saved_vehicles = $stmt->fetchAll();

$page_title = 'Saved Vehicles - DriveRent';
include 'includes/header.php';
include 'includes/navbar.php';
?>

<section class="bg-secondary-dark border-bottom border-dark-subtle page-header-section">
    <div class="container-fluid px-3 px-md-4 px-xl-5" style="max-width: 1600px;">
        <div class="luxury-header-card p-3 p-md-4 p-xl-4">
            <h2 class="fw-bold text-white mb-1 fs-3"><i class="fas fa-heart me-2 text-warning"></i>Saved Vehicles</h2>
            <p class="text-slate-400 mb-0 small">Your saved luxury cars and favorite rentals for quick booking</p>
        </div>
    </div>
</section>

<section class="py-4 bg-primary min-vh-100">
    <div class="container-fluid px-3 px-md-4 px-xl-5" style="max-width: 1600px;">
        <?php render_flash_messages(); ?>

        <?php if (empty($saved_vehicles)): ?>
            <div class="glass-card text-center py-5">
                <i class="fas fa-heart-crack fa-4x text-secondary mb-3"></i>
                <h4 class="fw-bold text-light">No Saved Vehicles Yet</h4>
                <p class="text-secondary mb-4">Browse our executive Surat fleet and click the heart icon to save vehicles!</p>
                <a href="<?php echo base_url('cars.php'); ?>" class="btn btn-gradient px-4 py-2.5">Browse Rental Fleet</a>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($saved_vehicles as $car): ?>
                    <div class="col-lg-4 col-md-6 reveal">
                        <div class="car-card h-100 d-flex flex-column">
                            <div class="car-card-image-wrap position-relative">
                                <img src="<?php echo get_car_image_url($car['image']); ?>" alt="Car" class="img-fluid w-100" style="height: 220px; object-fit: contain;">
                                <a href="<?php echo base_url('saved-cars.php?toggle_id=' . $car['id']); ?>" class="btn btn-danger btn-sm rounded-circle position-absolute top-0 end-0 m-3" title="Remove from Saved">
                                    <i class="fas fa-heart"></i>
                                </a>
                            </div>

                            <div class="p-4 d-flex flex-column flex-grow-1">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h5 class="fw-bold mb-0 text-light"><?php echo htmlspecialchars($car['brand'] . ' ' . $car['model']); ?></h5>
                                    <span class="text-secondary small fw-semibold"><?php echo $car['year']; ?></span>
                                </div>

                                <p class="text-secondary micro-text mb-3 flex-grow-1"><?php echo htmlspecialchars(substr($car['description'], 0, 95)) . '...'; ?></p>

                                <div class="row g-2 text-secondary small py-2 mb-3 border-top border-bottom border-dark-subtle">
                                    <div class="col-6"><i class="fas fa-cog text-accent me-1"></i><?php echo $car['transmission']; ?></div>
                                    <div class="col-6"><i class="fas fa-gas-pump text-accent me-1"></i><?php echo $car['fuel_type']; ?></div>
                                    <div class="col-6"><i class="fas fa-users text-accent me-1"></i><?php echo $car['seats']; ?> Seats</div>
                                    <div class="col-6"><i class="fas fa-tachometer-alt text-accent me-1"></i><?php echo $car['mileage']; ?></div>
                                </div>

                                <div class="d-flex align-items-center justify-content-between mt-auto">
                                    <div>
                                        <span class="text-secondary micro-text d-block">Per Day</span>
                                        <strong class="text-accent fs-5"><?php echo format_currency($car['price_per_day']); ?></strong>
                                    </div>
                                    <a href="<?php echo base_url('booking.php?id=' . $car['id']); ?>" class="btn btn-gradient btn-sm btn-pill px-3 py-2">Book Now</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
