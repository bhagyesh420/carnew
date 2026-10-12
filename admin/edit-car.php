<?php
$page_title = 'Edit Vehicle - Admin Panel';
include 'includes/header.php';
include 'includes/sidebar.php';

$car_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

try {
    $stmt = $pdo->prepare("SELECT * FROM cars WHERE id = ?");
    $stmt->execute([$car_id]);
    $car = $stmt->fetch();

    if (!$car) {
        header('Location: ' . base_url('admin/cars.php'));
        exit();
    }
} catch (PDOException $e) {
    header('Location: ' . base_url('admin/cars.php'));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $brand = sanitize($_POST['brand']);
    $model = sanitize($_POST['model']);
    $category = sanitize($_POST['category']);
    $year = (int)$_POST['year'];
    $price_per_day = (float)$_POST['price_per_day'];
    $price_per_hour = (float)$_POST['price_per_hour'];
    $seats = (int)$_POST['seats'];
    $transmission = sanitize($_POST['transmission']);
    $fuel_type = sanitize($_POST['fuel_type']);
    $mileage = sanitize($_POST['mileage']);
    $color = sanitize($_POST['color']);
    $description = sanitize($_POST['description']);
    $status = sanitize($_POST['status']);
    $featured = isset($_POST['featured']) ? 1 : 0;

    $image_name = $car['image'];
    if (isset($_FILES['car_image']) && $_FILES['car_image']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['car_image']['name'], PATHINFO_EXTENSION);
        $new_filename = strtolower(preg_replace('/[^a-zA-Z0-9]/', '_', $brand . '_' . $model)) . '_' . time() . '.' . $ext;
        $target = __DIR__ . '/../assets/images/cars/' . $new_filename;
        if (move_uploaded_file($_FILES['car_image']['tmp_name'], $target)) {
            $image_name = $new_filename;
        }
    }

    try {
        $stmt = $pdo->prepare("UPDATE cars SET 
            brand = ?, model = ?, category = ?, year = ?, price_per_day = ?, price_per_hour = ?, seats = ?, transmission = ?, fuel_type = ?, mileage = ?, color = ?, description = ?, image = ?, status = ?, featured = ? 
            WHERE id = ?");
        $stmt->execute([
            $brand, $model, $category, $year, $price_per_day, $price_per_hour,
            $seats, $transmission, $fuel_type, $mileage, $color, $description,
            $image_name, $status, $featured, $car_id
        ]);

        flash_message('success', 'Vehicle updated successfully!');
        header('Location: ' . base_url('admin/cars.php'));
        exit();
    } catch (PDOException $e) {
        $error = 'Database error updating vehicle.';
    }
}
?>

<div class="admin-main-wrapper">
    <!-- Mobile Top Navigation Bar -->
    <div class="d-lg-none px-3 py-2.5 bg-dark border-bottom border-slate-800 d-flex align-items-center justify-content-between">
        <button id="adminSidebarToggle" class="btn btn-outline-secondary btn-sm"><i class="fas fa-bars"></i></button>
        <span class="text-white fw-bold small"><i class="fas fa-pen-to-square text-cyan me-1"></i>Edit Vehicle</span>
        <a href="<?php echo base_url('admin/cars.php'); ?>" class="btn btn-outline-cyan btn-sm rounded-pill"><i class="fas fa-arrow-left"></i></a>
    </div>

    <div class="admin-container pt-3">
        <!-- Luxury Header Banner Card -->
        <div class="luxury-header-card p-3 p-md-4 mb-4">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 gap-md-4">
                <div class="d-flex align-items-center gap-3 gap-md-4">
                    <div class="rounded-circle border border-2 border-cyan shadow-lg d-flex align-items-center justify-content-center text-cyan flex-shrink-0" style="width: 66px; height: 66px; background: rgba(14, 165, 233, 0.15); font-size: 1.85rem; box-shadow: 0 0 24px rgba(14, 165, 233, 0.25);">
                        <i class="fas fa-pen-to-square"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                            <h2 class="fw-bold mb-0 text-white fs-3">Edit Vehicle: <?php echo htmlspecialchars($car['brand'] . ' ' . $car['model']); ?></h2>
                            <span class="badge rounded-pill bg-cyan bg-opacity-20 text-cyan border border-cyan border-opacity-30 px-2.5 py-1 micro-text fw-bold">
                                #ID: <?php echo $car_id; ?>
                            </span>
                        </div>
                        <div class="text-slate-400 small d-flex align-items-center gap-2 flex-wrap">
                            <span>Update vehicle specifications, pricing, imagery, and operational status</span>
                            <span>&bull;</span>
                            <span class="badge bg-dark text-slate-300 font-monospace border border-slate-700 text-nowrap py-1 px-2">
                                <i class="fas fa-car text-cyan me-1"></i><?php echo htmlspecialchars($car['category']); ?>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-2.5 flex-wrap">
                    <a href="<?php echo base_url('admin/cars.php'); ?>" class="btn btn-outline-light rounded-pill px-3.5 py-2 fw-semibold d-inline-flex align-items-center gap-2">
                        <i class="fas fa-arrow-left"></i> Back to Fleet
                    </a>
                </div>
            </div>
        </div>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger mb-4"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="admin-table-card p-4 p-md-5">
            <form action="<?php echo base_url('admin/edit-car.php?id=' . $car_id); ?>" method="POST" enctype="multipart/form-data">
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label text-secondary small fw-semibold">Brand</label>
                        <input type="text" name="brand" class="form-control bg-secondary text-light border-secondary" value="<?php echo htmlspecialchars($car['brand']); ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-secondary small fw-semibold">Model</label>
                        <input type="text" name="model" class="form-control bg-secondary text-light border-secondary" value="<?php echo htmlspecialchars($car['model']); ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-secondary small fw-semibold">Category</label>
                        <select name="category" class="form-select bg-secondary text-light border-secondary" required>
                            <?php foreach (['Luxury', 'Sedan', 'SUV', 'Sports', 'Economy', 'Electric'] as $cat): ?>
                                <option value="<?php echo $cat; ?>" <?php echo $car['category'] === $cat ? 'selected' : ''; ?>><?php echo $cat; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-3">
                        <label class="form-label text-secondary small fw-semibold">Model Year</label>
                        <input type="number" name="year" class="form-control bg-secondary text-light border-secondary" value="<?php echo $car['year']; ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-secondary small fw-semibold">Price / Day (₹)</label>
                        <input type="number" step="0.01" name="price_per_day" class="form-control bg-secondary text-light border-secondary" value="<?php echo $car['price_per_day']; ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-secondary small fw-semibold">Price / Hour (₹)</label>
                        <input type="number" step="0.01" name="price_per_hour" class="form-control bg-secondary text-light border-secondary" value="<?php echo $car['price_per_hour']; ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-secondary small fw-semibold">Seats</label>
                        <select name="seats" class="form-select bg-secondary text-light border-secondary" required>
                            <option value="2" <?php echo $car['seats'] == 2 ? 'selected' : ''; ?>>2 Seats</option>
                            <option value="5" <?php echo $car['seats'] == 5 ? 'selected' : ''; ?>>5 Seats</option>
                            <option value="7" <?php echo $car['seats'] == 7 ? 'selected' : ''; ?>>7 Seats</option>
                        </select>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-3">
                        <label class="form-label text-secondary small fw-semibold">Transmission</label>
                        <select name="transmission" class="form-select bg-secondary text-light border-secondary" required>
                            <option value="Automatic" <?php echo $car['transmission'] === 'Automatic' ? 'selected' : ''; ?>>Automatic</option>
                            <option value="Manual" <?php echo $car['transmission'] === 'Manual' ? 'selected' : ''; ?>>Manual</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-secondary small fw-semibold">Fuel Type</label>
                        <select name="fuel_type" class="form-select bg-secondary text-light border-secondary" required>
                            <option value="Petrol" <?php echo $car['fuel_type'] === 'Petrol' ? 'selected' : ''; ?>>Petrol</option>
                            <option value="Diesel" <?php echo $car['fuel_type'] === 'Diesel' ? 'selected' : ''; ?>>Diesel</option>
                            <option value="Electric" <?php echo $car['fuel_type'] === 'Electric' ? 'selected' : ''; ?>>Electric</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-secondary small fw-semibold">Mileage</label>
                        <input type="text" name="mileage" class="form-control bg-secondary text-light border-secondary" value="<?php echo htmlspecialchars($car['mileage']); ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-secondary small fw-semibold">Color</label>
                        <input type="text" name="color" class="form-control bg-secondary text-light border-secondary" value="<?php echo htmlspecialchars($car['color']); ?>" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label text-secondary small fw-semibold">Description</label>
                    <textarea name="description" rows="4" class="form-control bg-secondary text-light border-secondary" required><?php echo htmlspecialchars($car['description']); ?></textarea>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label text-secondary small fw-semibold">Status</label>
                        <select name="status" class="form-select bg-secondary text-light border-secondary" required>
                            <option value="available" <?php echo $car['status'] === 'available' ? 'selected' : ''; ?>>Available</option>
                            <option value="booked" <?php echo $car['status'] === 'booked' ? 'selected' : ''; ?>>Booked</option>
                            <option value="maintenance" <?php echo $car['status'] === 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-secondary small fw-semibold">Replace Image (Optional)</label>
                        <input type="file" name="car_image" id="car_image_input" class="form-control bg-secondary text-light border-secondary" accept="image/*">
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <div class="form-check form-switch pb-2">
                            <input class="form-check-input" type="checkbox" name="featured" id="featuredSwitch" <?php echo $car['featured'] ? 'checked' : ''; ?>>
                            <label class="form-check-label text-light fw-bold ms-2" for="featuredSwitch">Featured Status</label>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <img id="car_image_preview" src="<?php echo get_car_image_url($car['image']); ?>" alt="Current Image" class="img-fluid rounded" style="max-height: 120px;">
                </div>

                <button type="submit" class="btn btn-primary px-5 py-2.5 fw-bold"><i class="fas fa-save me-2"></i>Update Vehicle Specifications</button>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
