<?php
$page_title = 'Fleet Management - Admin Panel';
include 'includes/header.php';
include 'includes/sidebar.php';

// Filtering parameters
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$filter_cat = isset($_GET['category']) ? sanitize($_GET['category']) : '';
$filter_status = isset($_GET['status']) ? sanitize($_GET['status']) : '';

$sql = "SELECT * FROM cars WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (brand LIKE ? OR model LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($filter_cat)) {
    $sql .= " AND category = ?";
    $params[] = $filter_cat;
}

if (!empty($filter_status)) {
    $sql .= " AND status = ?";
    $params[] = $filter_status;
}

$sql .= " ORDER BY id DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $cars = $stmt->fetchAll();

    // Fleet metric counts
    $total_fleet = $pdo->query("SELECT COUNT(*) FROM cars")->fetchColumn();
    $avail_fleet = $pdo->query("SELECT COUNT(*) FROM cars WHERE status = 'available'")->fetchColumn();
    $booked_fleet = $pdo->query("SELECT COUNT(*) FROM cars WHERE status = 'booked'")->fetchColumn();
} catch (PDOException $e) {
    $cars = [];
    $total_fleet = $avail_fleet = $booked_fleet = 0;
}
?>

<div class="admin-main-wrapper">
    <!-- Mobile Top Navigation Bar -->
    <div class="d-lg-none px-3 py-2.5 bg-dark border-bottom border-slate-800 d-flex align-items-center justify-content-between">
        <button id="adminSidebarToggle" class="btn btn-outline-secondary btn-sm"><i class="fas fa-bars"></i></button>
        <span class="text-white fw-bold small"><i class="fas fa-car text-cyan me-1"></i>Fleet Inventory</span>
        <a href="<?php echo base_url('admin/add-car.php'); ?>" class="btn btn-gradient btn-sm rounded-pill py-1 px-2.5" style="font-size: 0.76rem;"><i class="fas fa-plus"></i></a>
    </div>

    <div class="admin-container pt-3">
        <?php render_flash_messages(); ?>

        <!-- Luxury Header Banner Card -->
        <div class="luxury-header-card p-3 p-md-4 mb-4">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 gap-md-4">
                <div class="d-flex align-items-center gap-3 gap-md-4">
                    <div class="rounded-circle border border-2 border-cyan shadow-lg d-flex align-items-center justify-content-center text-cyan flex-shrink-0" style="width: 66px; height: 66px; background: rgba(14, 165, 233, 0.15); font-size: 1.85rem; box-shadow: 0 0 24px rgba(14, 165, 233, 0.25);">
                        <i class="fas fa-car"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                            <h2 class="fw-bold mb-0 text-white fs-3">Fleet Inventory Management</h2>
                            <span class="badge rounded-pill bg-cyan bg-opacity-20 text-cyan border border-cyan border-opacity-30 px-2.5 py-1 micro-text fw-bold">
                                <?php echo $total_fleet; ?> Vehicles
                            </span>
                        </div>
                        <div class="text-slate-400 small d-flex align-items-center gap-2 flex-wrap">
                            <span>Manage vehicle specifications, pricing, and live availability</span>
                            <span>&bull;</span>
                            <span class="badge bg-dark text-slate-300 font-monospace border border-slate-700 text-nowrap py-1 px-2">
                                <i class="fas fa-gauge-high text-cyan me-1"></i>Active Fleet
                            </span>
                        </div>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <a href="<?php echo base_url('admin/add-car.php'); ?>" class="btn btn-gradient rounded-pill px-4 py-2.5 shadow-cyan fw-bold d-inline-flex align-items-center gap-2">
                        <i class="fas fa-plus"></i> Add New Vehicle
                    </a>
                </div>
            </div>
        </div>

        <!-- Quick Metrics Ribbon -->
        <div class="row g-3 mb-4">
            <div class="col-sm-4">
                <div class="p-3 rounded-3 border border-slate-700 d-flex align-items-center justify-content-between" style="background: linear-gradient(145deg, #1E293B, #131E30);">
                    <div>
                        <span class="text-secondary text-uppercase fw-bold" style="font-size: 0.84rem; letter-spacing: 0.5px;">Total Vehicles</span>
                        <div class="fs-4 fw-bold text-white"><?php echo $total_fleet; ?></div>
                    </div>
                    <div class="stat-icon-wrapper stat-icon-blue" style="width: 46px; height: 46px; font-size: 1.2rem;">
                        <i class="fas fa-car"></i>
                    </div>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="p-3 rounded-3 border border-slate-700 d-flex align-items-center justify-content-between" style="background: linear-gradient(145deg, #1E293B, #131E30);">
                    <div>
                        <span class="text-secondary text-uppercase fw-bold" style="font-size: 0.84rem; letter-spacing: 0.5px;">Ready to Rent</span>
                        <div class="fs-4 fw-bold text-emerald"><?php echo $avail_fleet; ?></div>
                    </div>
                    <div class="stat-icon-wrapper stat-icon-emerald" style="width: 46px; height: 46px; font-size: 1.2rem;">
                        <i class="fas fa-circle-check"></i>
                    </div>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="p-3 rounded-3 border border-slate-700 d-flex align-items-center justify-content-between" style="background: linear-gradient(145deg, #1E293B, #131E30);">
                    <div>
                        <span class="text-secondary text-uppercase fw-bold" style="font-size: 0.84rem; letter-spacing: 0.5px;">Currently Booked</span>
                        <div class="fs-4 fw-bold text-amber"><?php echo $booked_fleet; ?></div>
                    </div>
                    <div class="stat-icon-wrapper stat-icon-amber" style="width: 46px; height: 46px; font-size: 1.2rem;">
                        <i class="fas fa-key"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter & Search Bar -->
        <div class="admin-table-card p-3 mb-4">
            <form action="<?php echo base_url('admin/cars.php'); ?>" method="GET" class="row g-2 align-items-center">
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="Search brand or model (e.g. BMW, Fortuner)..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="category" class="form-select" onchange="this.form.submit()">
                        <option value="">All Categories</option>
                        <option value="Sedan" <?php echo $filter_cat === 'Sedan' ? 'selected' : ''; ?>>Sedan</option>
                        <option value="SUV" <?php echo $filter_cat === 'SUV' ? 'selected' : ''; ?>>SUV</option>
                        <option value="Luxury" <?php echo $filter_cat === 'Luxury' ? 'selected' : ''; ?>>Luxury Flagship</option>
                        <option value="Sports" <?php echo $filter_cat === 'Sports' ? 'selected' : ''; ?>>Sports Performance</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="">All Statuses</option>
                        <option value="available" <?php echo $filter_status === 'available' ? 'selected' : ''; ?>>Available</option>
                        <option value="booked" <?php echo $filter_status === 'booked' ? 'selected' : ''; ?>>Booked</option>
                        <option value="maintenance" <?php echo $filter_status === 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-outline-cyan w-100 rounded-pill fw-semibold"><i class="fas fa-filter me-1.5"></i>Filter</button>
                    <?php if (!empty($search) || !empty($filter_cat) || !empty($filter_status)): ?>
                        <a href="<?php echo base_url('admin/cars.php'); ?>" class="btn btn-outline-secondary rounded-pill" title="Reset Filters"><i class="fas fa-times"></i></a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Fleet Table -->
        <div class="admin-table-card p-4">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h5 class="fw-bold mb-0 text-white d-flex align-items-center gap-2">
                    <i class="fas fa-list-check text-cyan"></i>
                    <span>Vehicle Fleet Directory (<?php echo count($cars); ?> Results)</span>
                </h5>
            </div>

            <div class="table-responsive">
                <table class="table table-dark-custom align-middle">
                    <thead>
                        <tr>
                            <th style="min-width: 280px;">Vehicle</th>
                            <th style="min-width: 210px;">Specifications</th>
                            <th style="min-width: 170px;">Daily Rate</th>
                            <th style="min-width: 170px;">Status & Tier</th>
                            <th class="text-end" style="min-width: 180px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($cars)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-5 text-secondary">
                                    <i class="fas fa-car-tunnel fa-3x mb-3 opacity-30"></i>
                                    <div>No vehicles match your search query.</div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($cars as $car): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="p-1 rounded-3 border border-slate-700 flex-shrink-0" style="background: rgba(15, 23, 42, 0.9);">
                                                <img src="<?php echo get_car_image_url($car['image']); ?>" alt="Car" style="width: 72px; height: 48px; object-fit: cover; border-radius: 6px;">
                                            </div>
                                            <div>
                                                <strong class="text-white d-block mb-1" style="font-size: 0.96rem; font-weight: 700;"><?php echo htmlspecialchars($car['brand'] . ' ' . $car['model']); ?></strong>
                                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                                    <span class="badge rounded-pill bg-cyan bg-opacity-20 text-cyan border border-cyan border-opacity-30 px-2.5 py-0.5" style="font-size: 0.82rem;">
                                                        <?php echo $car['category']; ?>
                                                    </span>
                                                    <span class="badge bg-slate-800 text-slate-300 border border-slate-700 px-2.5 py-0.5" style="font-size: 0.82rem;">
                                                        <?php echo $car['year']; ?>
                                                    </span>
                                                    <span class="small ms-1" style="font-size: 0.82rem; color: #CBD5E1;"><i class="fas fa-palette text-cyan me-1"></i><?php echo htmlspecialchars($car['color']); ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-nowrap">
                                        <div class="text-white fw-semibold mb-1" style="font-size: 0.90rem;">
                                            <i class="fas fa-users text-cyan me-2"></i><?php echo $car['seats']; ?> Seats
                                        </div>
                                        <div class="small" style="font-size: 0.86rem; color: #CBD5E1;">
                                            <i class="fas fa-gas-pump text-slate-400 me-2"></i><?php echo $car['fuel_type']; ?> &bull; <?php echo $car['transmission']; ?>
                                        </div>
                                    </td>
                                    <td class="text-nowrap">
                                        <div class="fw-bold text-emerald mb-1" style="font-size: 1.05rem;"><?php echo format_currency($car['price_per_day']); ?></div>
                                        <div class="small" style="font-size: 0.82rem; color: #CBD5E1;">per 24 hrs</div>
                                    </td>
                                    <td class="text-nowrap">
                                        <div class="d-flex flex-column align-items-start gap-1.5">
                                            <span class="badge-status <?php echo strtolower($car['status']); ?>"><?php echo strtoupper($car['status']); ?></span>
                                            <?php if ($car['featured']): ?>
                                                <span class="badge bg-amber bg-opacity-20 text-amber border border-amber border-opacity-30 rounded-pill px-2.5 py-0.5" style="font-size: 0.80rem; font-weight: 600;">
                                                    <i class="fas fa-star me-1 text-amber"></i>Featured
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="text-end text-nowrap">
                                        <div class="d-flex flex-column align-items-end">
                                            <a href="<?php echo base_url('admin/edit-car.php?id=' . $car['id']); ?>" class="btn btn-outline-cyan btn-sm rounded-pill px-3 py-1 mb-1.5 fw-semibold d-inline-flex align-items-center" style="font-size: 0.82rem;" title="Edit Vehicle">
                                                <i class="fas fa-pen-to-square me-1.5"></i>Edit
                                            </a>
                                            <div class="d-flex align-items-center gap-1.5">
                                                <a href="<?php echo base_url('car-details.php?id=' . $car['id']); ?>" target="_blank" class="btn btn-outline-light btn-sm rounded-pill px-2.5 py-1" style="font-size: 0.78rem;" title="View Live Page">
                                                    <i class="fas fa-arrow-up-right-from-square me-1"></i>View
                                                </a>
                                                <a href="<?php echo base_url('admin/delete-car.php?id=' . $car['id']); ?>" class="btn btn-outline-danger btn-sm rounded-pill px-2.5 py-1 btn-confirm-delete" style="font-size: 0.78rem;" data-confirm="Are you sure you want to delete <?php echo htmlspecialchars($car['brand'] . ' ' . $car['model']); ?>?" title="Delete Vehicle">
                                                    <i class="fas fa-trash-can"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
