<?php
$page_title = 'Add New Vehicle - Admin Panel';
include 'includes/header.php';
include 'includes/sidebar.php';

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

    // Handle Image Upload
    $image_name = 'bmw_5series.jpg'; // default fallback
    if (isset($_FILES['car_image']) && $_FILES['car_image']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['car_image']['name'], PATHINFO_EXTENSION);
        $new_filename = strtolower(preg_replace('/[^a-zA-Z0-9]/', '_', $brand . '_' . $model)) . '_' . time() . '.' . $ext;
        $target = __DIR__ . '/../assets/images/cars/' . $new_filename;
        if (move_uploaded_file($_FILES['car_image']['tmp_name'], $target)) {
            $image_name = $new_filename;
        }
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO cars 
            (brand, model, category, year, price_per_day, price_per_hour, seats, transmission, fuel_type, mileage, color, description, image, status, featured) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $brand, $model, $category, $year, $price_per_day, $price_per_hour,
            $seats, $transmission, $fuel_type, $mileage, $color, $description,
            $image_name, $status, $featured
        ]);

        flash_message('success', 'Vehicle ' . htmlspecialchars($brand . ' ' . $model) . ' added successfully!');
        header('Location: ' . base_url('admin/cars.php'));
        exit();
    } catch (PDOException $e) {
        $error = 'Database error adding vehicle: ' . $e->getMessage();
    }
}
?>

<div class="admin-main-wrapper">
    <!-- Mobile Top Navigation Bar -->
    <div class="d-lg-none px-3 py-2.5 bg-dark border-bottom border-slate-800 d-flex align-items-center justify-content-between">
        <button id="adminSidebarToggle" class="btn btn-outline-secondary btn-sm"><i class="fas fa-bars"></i></button>
        <span class="text-white fw-bold small"><i class="fas fa-plus-circle text-cyan me-1"></i>New Vehicle</span>
        <a href="<?php echo base_url('admin/cars.php'); ?>" class="btn btn-outline-cyan btn-sm rounded-pill"><i class="fas fa-arrow-left"></i></a>
    </div>

    <div class="admin-container pt-3">
        <!-- Luxury Header Banner Card -->
        <div class="luxury-header-card p-3 p-md-4 mb-4">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 gap-md-4">
                <div class="d-flex align-items-center gap-3 gap-md-4">
                    <div class="rounded-circle border border-2 border-cyan shadow-lg d-flex align-items-center justify-content-center text-cyan flex-shrink-0" style="width: 66px; height: 66px; background: rgba(14, 165, 233, 0.15); font-size: 1.85rem; box-shadow: 0 0 24px rgba(14, 165, 233, 0.25);">
                        <i class="fas fa-plus-circle"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                            <h2 class="fw-bold mb-0 text-white fs-3">Add New Fleet Vehicle</h2>
                            <span class="badge rounded-pill bg-cyan bg-opacity-20 text-cyan border border-cyan border-opacity-30 px-2.5 py-1 micro-text fw-bold">
                                Inventory Registration
                            </span>
                        </div>
                        <div class="text-slate-400 small d-flex align-items-center gap-2 flex-wrap">
                            <span>Register vehicle specifications, rental rates, and imagery for live booking</span>
                            <span>&bull;</span>
                            <span class="badge bg-dark text-slate-300 font-monospace border border-slate-700 text-nowrap py-1 px-2">
                                <i class="fas fa-car text-cyan me-1"></i>Surat Fleet
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
            <div class="alert alert-danger mb-4 rounded-3"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="admin-table-card p-4 p-md-5">
            <form action="<?php echo base_url('admin/add-car.php'); ?>" method="POST" enctype="multipart/form-data">
                <div class="row g-4 mb-4">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Brand</label>
                        <input type="text" name="brand" class="form-control" placeholder="e.g. BMW" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Model</label>
                        <input type="text" name="model" class="form-control" placeholder="e.g. M5 Competition" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Category</label>
                        <select name="category" class="form-select" required>
                            <option value="Luxury">Luxury</option>
                            <option value="Sedan">Sedan</option>
                            <option value="SUV">SUV</option>
                            <option value="Sports">Sports</option>
                            <option value="Economy">Economy</option>
                            <option value="Electric">Electric</option>
                        </select>
                    </div>
                </div>

                <div class="row g-4 mb-4">
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Model Year</label>
                        <input type="number" name="year" class="form-control" value="2025" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Price / Day (₹)</label>
                        <input type="number" step="0.01" name="price_per_day" class="form-control" placeholder="6999.00" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Price / Hour (₹)</label>
                        <input type="number" step="0.01" name="price_per_hour" class="form-control" placeholder="799.00" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Seats</label>
                        <select name="seats" class="form-select" required>
                            <option value="2">2 Seats</option>
                            <option value="5" selected>5 Seats</option>
                            <option value="7">7 Seats</option>
                        </select>
                    </div>
                </div>

                <div class="row g-4 mb-4">
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Transmission</label>
                        <select name="transmission" class="form-select" required>
                            <option value="Automatic">Automatic</option>
                            <option value="Manual">Manual</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Fuel Type</label>
                        <select name="fuel_type" class="form-select" required>
                            <option value="Petrol">Petrol</option>
                            <option value="Diesel">Diesel</option>
                            <option value="Electric">Electric</option>
                            <option value="Hybrid">Hybrid</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Mileage</label>
                        <input type="text" name="mileage" class="form-control" placeholder="15 KM/L" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Exterior Color</label>
                        <input type="text" name="color" class="form-control" placeholder="Phytonic Blue" required>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">Vehicle Description</label>
                    <textarea name="description" rows="4" class="form-control" placeholder="Enter executive features, digital cockpit specs, air suspension options..." required></textarea>
                </div>

                <div class="row g-4 mb-4">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Status</label>
                        <select name="status" class="form-select" required>
                            <option value="available">Available</option>
                            <option value="booked">Booked</option>
                            <option value="maintenance">Maintenance</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Upload Vehicle Image</label>
                        <input type="file" name="car_image" id="car_image_input" class="form-control" accept="image/*">
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <div class="form-check form-switch pb-2">
                            <input class="form-check-input" type="checkbox" name="featured" id="featuredSwitch">
                            <label class="form-check-label text-white fw-bold ms-2" for="featuredSwitch">Showcase as Featured Car</label>
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <img id="car_image_preview" src="" alt="Preview" class="img-fluid rounded border border-slate-700 p-1 bg-slate-900 d-none" style="max-height: 140px;">
                </div>

                <button type="submit" class="btn btn-primary rounded-pill px-5 py-3 fw-bold shadow"><i class="fas fa-plus-circle me-2"></i>Add Vehicle to Fleet</button>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
