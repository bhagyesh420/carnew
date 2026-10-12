<?php
$page_title = 'Browse Fleet - DriveRent Car Rental';
$meta_description = 'Explore DriveRent full rental fleet in Surat City. Filter by category, brand, seating, price range, and transmission.';
include 'includes/header.php';
include 'includes/navbar.php';

// Filter Variables - Clean without double HTML encoding
$search = isset($_GET['search']) ? trim(strip_tags($_GET['search'])) : '';
$category = isset($_GET['category']) ? trim(strip_tags($_GET['category'])) : '';
$brand = isset($_GET['brand']) ? trim(strip_tags($_GET['brand'])) : '';
$fuel = isset($_GET['fuel_type']) ? trim(strip_tags($_GET['fuel_type'])) : '';
$transmission = isset($_GET['transmission']) ? trim(strip_tags($_GET['transmission'])) : '';
$seats = isset($_GET['seats']) ? (int)$_GET['seats'] : 0;
$max_price = (isset($_GET['max_price']) && $_GET['max_price'] !== '') ? (float)$_GET['max_price'] : 0;
$sort = isset($_GET['sort']) ? trim(strip_tags($_GET['sort'])) : 'id_desc';
$surat_location = isset($_GET['surat_location']) ? trim(strip_tags($_GET['surat_location'])) : (isset($_GET['pickup_location']) ? trim(strip_tags($_GET['pickup_location'])) : '');

// Build Dynamic SQL Query
$query = "SELECT * FROM cars WHERE status = 'available'";
$params = [];

if (!empty($search)) {
    $query .= " AND (brand LIKE ? OR model LIKE ? OR description LIKE ?)";
    $term = '%' . $search . '%';
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}
if (!empty($category)) {
    $query .= " AND category = ?";
    $params[] = $category;
}
if (!empty($brand)) {
    $query .= " AND brand = ?";
    $params[] = $brand;
}
if (!empty($fuel)) {
    $query .= " AND fuel_type = ?";
    $params[] = $fuel;
}
if (!empty($transmission)) {
    $query .= " AND transmission = ?";
    $params[] = $transmission;
}
if ($seats > 0) {
    $query .= " AND seats = ?";
    $params[] = $seats;
}
if ($max_price > 0) {
    $query .= " AND price_per_day <= ?";
    $params[] = $max_price;
}

// Sorting Clause
switch ($sort) {
    case 'price_low':
        $query .= " ORDER BY price_per_day ASC";
        break;
    case 'price_high':
        $query .= " ORDER BY price_per_day DESC";
        break;
    case 'popular':
        $query .= " ORDER BY featured DESC, price_per_day ASC";
        break;
    default:
        $query .= " ORDER BY id DESC";
        break;
}

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $cars = $stmt->fetchAll();

    // Fetch Brands for Filter
    $brands_stmt = $pdo->query("SELECT DISTINCT brand FROM cars ORDER BY brand ASC");
    $all_brands = $brands_stmt->fetchAll(PDO::FETCH_COLUMN);

    // Fetch Counts per category for pills
    $cat_counts_stmt = $pdo->query("SELECT category, COUNT(*) as cnt FROM cars WHERE status = 'available' GROUP BY category");
    $cat_counts = $cat_counts_stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    $total_fleet_count = array_sum($cat_counts);

    // Fetch User's Saved Cars if logged in
    $saved_car_ids = [];
    if (is_logged_in() && isset($_SESSION['user_id'])) {
        $saved_stmt = $pdo->prepare("SELECT car_id FROM saved_cars WHERE user_id = ?");
        $saved_stmt->execute([$_SESSION['user_id']]);
        $saved_car_ids = $saved_stmt->fetchAll(PDO::FETCH_COLUMN);
    }
} catch (PDOException $e) {
    $cars = [];
    $all_brands = [];
    $cat_counts = [];
    $total_fleet_count = 0;
    $saved_car_ids = [];
}

// Active Filter Chips Builder
$active_filters = [];
if (!empty($search)) $active_filters['search'] = ['label' => 'Search: "' . $search . '"', 'key' => 'search'];
if (!empty($category)) $active_filters['category'] = ['label' => 'Category: ' . $category, 'key' => 'category'];
if (!empty($brand)) $active_filters['brand'] = ['label' => 'Brand: ' . $brand, 'key' => 'brand'];
if (!empty($surat_location)) $active_filters['surat_location'] = ['label' => 'Hub: ' . $surat_location, 'key' => 'surat_location'];
if (!empty($transmission)) $active_filters['transmission'] = ['label' => 'Gearbox: ' . $transmission, 'key' => 'transmission'];
if (!empty($fuel)) $active_filters['fuel_type'] = ['label' => 'Fuel: ' . $fuel, 'key' => 'fuel_type'];
if ($seats > 0) $active_filters['seats'] = ['label' => $seats . ' Seats', 'key' => 'seats'];
if ($max_price > 0 && $max_price < 35000) $active_filters['max_price'] = ['label' => 'Max ₹' . number_format($max_price), 'key' => 'max_price'];
?>

<!-- Header Banner -->
<section class="banner-section border-bottom border-dark-subtle page-header-section" style="background: linear-gradient(180deg, #0d1424 0%, #0B0F19 100%);">
    <div class="container-fluid px-3 px-md-4 px-xl-5" style="max-width: 1600px;">
        <div class="luxury-header-card p-3 p-md-4 p-xl-4 text-center">
            <h1 class="fw-bold mb-2 text-white fs-2">Browse Surat Car Fleet</h1>
            <p class="text-slate-400 max-w-600 mx-auto mb-0 small">
                60 verified luxury, sports, SUV, sedan, electric, and economy vehicles with instant 30-min doorstep delivery across Surat City.
            </p>
        </div>
    </div>
</section>

<!-- Main Listing Section with Sticky Stationary Left Filter & Scrolling Car Fleet -->
<section class="py-4 fleet-catalog-section" style="background-color: #0B0F19;">
    <div class="container-fluid px-3 px-md-4 px-xl-5" style="max-width: 1600px;">

        <!-- Mobile Filter Toggle Bar (Visible only on screens < 992px) -->
        <div class="d-lg-none mb-3">
            <button class="btn btn-gradient w-100 py-2.5 px-3 d-flex align-items-center justify-content-between rounded-3 shadow" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarFilterCollapse" aria-expanded="false" aria-controls="sidebarFilterCollapse">
                <span class="d-flex align-items-center gap-2">
                    <i class="fas fa-sliders text-cyan"></i>
                    <span class="fw-bold text-white">Filter & Search Fleet</span>
                </span>
                <span class="badge bg-slate-900 border border-slate-700 text-cyan px-2.5 py-1 rounded-pill" style="font-size: 0.75rem;">
                    <?php echo count($active_filters) > 0 ? count($active_filters) . ' Active' : 'Configure'; ?> <i class="fas fa-chevron-down ms-1"></i>
                </span>
            </button>
        </div>

        <div class="row g-4 align-items-start">
            <!-- Left Column: Big, Luxurious, Full-Detail Filter Sidebar -->
            <aside class="col-12 col-lg-4 col-xl-4 sticky-filter-column">
                <div class="collapse d-lg-block" id="sidebarFilterCollapse">
                    <div class="side-filter-card">
                        <form action="<?php echo base_url('cars.php'); ?>" method="GET" id="catalogFilterForm">
                            <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort); ?>">

                            <!-- Header / Title -->
                            <div class="d-flex align-items-center justify-content-between pb-3 mb-3 border-bottom border-secondary">
                                <div class="d-flex align-items-center gap-2.5">
                                    <div class="filter-header-badge">
                                        <i class="fas fa-sliders-h"></i>
                                    </div>
                                    <div>
                                        <h5 class="fw-bold text-white mb-0" style="letter-spacing: 0.5px;">Filter Vehicles</h5>
                                        <small class="text-cyan fw-semibold" style="font-size: 0.74rem;">Surat Hub Fleet &bull; <?php echo $total_fleet_count > 0 ? $total_fleet_count : count($cars); ?> Available</small>
                                    </div>
                                </div>
                                <?php if (!empty($active_filters)): ?>
                                    <a href="<?php echo base_url('cars.php'); ?>" class="btn btn-outline-danger btn-sm rounded-pill px-3 py-1 fw-bold" style="font-size: 0.75rem;">
                                        <i class="fas fa-rotate-left me-1"></i>Reset
                                    </a>
                                <?php endif; ?>
                            </div>

                            <!-- 1. Search Vehicle -->
                            <div class="mb-3">
                                <label class="filter-label"><i class="fas fa-search text-cyan"></i> Search Vehicle</label>
                                <div class="position-relative">
                                    <input type="text" name="search" class="form-control" style="padding-left: 42px !important;" placeholder="Search model or brand (e.g. BMW, Fortuner)..." value="<?php echo htmlspecialchars($search); ?>">
                                    <i class="fas fa-search position-absolute text-slate-400" style="left: 14px; top: 50%; transform: translateY(-50%); font-size: 0.9rem; pointer-events: none;"></i>
                                </div>
                            </div>

                            <!-- 2. Surat Hub Drop -->
                            <div class="mb-3">
                                <label class="filter-label"><i class="fas fa-location-dot text-cyan"></i> Surat Hub Location</label>
                                <select name="surat_location" class="form-select" onchange="document.getElementById('catalogFilterForm').submit()">
                                    <option value="">All Surat Hubs (Instant Handover)</option>
                                    <?php foreach (get_surat_locations() as $loc): ?>
                                        <option value="<?php echo htmlspecialchars($loc); ?>" <?php echo $surat_location === $loc ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($loc); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- 3. Category -->
                            <div class="mb-3">
                                <label class="filter-label"><i class="fas fa-layer-group text-cyan"></i> Vehicle Category</label>
                                <select name="category" class="form-select" onchange="document.getElementById('catalogFilterForm').submit()">
                                    <option value="">All Categories (<?php echo $total_fleet_count > 0 ? $total_fleet_count : '60'; ?>)</option>
                                    <?php foreach (['Economy', 'Sedan', 'SUV', 'Luxury', 'Sports', 'Electric'] as $cat): ?>
                                        <option value="<?php echo $cat; ?>" <?php echo $category === $cat ? 'selected' : ''; ?>>
                                            <?php echo $cat; ?> (<?php echo $cat_counts[$cat] ?? 0; ?> Vehicles)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- 4. Brand -->
                            <div class="mb-3">
                                <label class="filter-label"><i class="fas fa-copyright text-cyan"></i> Vehicle Brand</label>
                                <select name="brand" class="form-select" onchange="document.getElementById('catalogFilterForm').submit()">
                                    <option value="">All Brands (<?php echo count($all_brands); ?> Makes)</option>
                                    <?php foreach ($all_brands as $b): ?>
                                        <option value="<?php echo $b; ?>" <?php echo $brand === $b ? 'selected' : ''; ?>>
                                            <?php echo $b; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- 5. Gearbox & Fuel -->
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label class="filter-label"><i class="fas fa-cog text-cyan"></i> Gearbox</label>
                                    <select name="transmission" class="form-select" onchange="document.getElementById('catalogFilterForm').submit()">
                                        <option value="">Any</option>
                                        <option value="Automatic" <?php echo $transmission === 'Automatic' ? 'selected' : ''; ?>>Automatic</option>
                                        <option value="Manual" <?php echo $transmission === 'Manual' ? 'selected' : ''; ?>>Manual</option>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label class="filter-label"><i class="fas fa-gas-pump text-cyan"></i> Fuel Type</label>
                                    <select name="fuel_type" class="form-select" onchange="document.getElementById('catalogFilterForm').submit()">
                                        <option value="">Any</option>
                                        <option value="Petrol" <?php echo $fuel === 'Petrol' ? 'selected' : ''; ?>>Petrol</option>
                                        <option value="Diesel" <?php echo $fuel === 'Diesel' ? 'selected' : ''; ?>>Diesel</option>
                                        <option value="Electric" <?php echo $fuel === 'Electric' ? 'selected' : ''; ?>>Electric EV</option>
                                        <option value="Hybrid" <?php echo $fuel === 'Hybrid' ? 'selected' : ''; ?>>Hybrid</option>
                                    </select>
                                </div>
                            </div>

                            <!-- 6. Seating Capacity -->
                            <div class="mb-3">
                                <label class="filter-label"><i class="fas fa-users text-cyan"></i> Seating Capacity</label>
                                <div class="seats-toggle-group">
                                    <a href="<?php echo base_url('cars.php?' . http_build_query(array_merge($_GET, ['seats' => 0]))); ?>" class="btn-seat-toggle flex-fill <?php echo $seats === 0 ? 'active' : ''; ?>">All</a>
                                    <a href="<?php echo base_url('cars.php?' . http_build_query(array_merge($_GET, ['seats' => 2]))); ?>" class="btn-seat-toggle flex-fill <?php echo $seats === 2 ? 'active' : ''; ?>">2 Seats</a>
                                    <a href="<?php echo base_url('cars.php?' . http_build_query(array_merge($_GET, ['seats' => 5]))); ?>" class="btn-seat-toggle flex-fill <?php echo $seats === 5 ? 'active' : ''; ?>">5 Seats</a>
                                    <a href="<?php echo base_url('cars.php?' . http_build_query(array_merge($_GET, ['seats' => 7]))); ?>" class="btn-seat-toggle flex-fill <?php echo $seats === 7 ? 'active' : ''; ?>">7 Seats</a>
                                </div>
                            </div>

                            <!-- 7. Max Daily Budget -->
                            <div class="mb-3 bg-slate-900 p-3 rounded-3 border border-secondary">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="filter-label mb-0"><i class="fas fa-indian-rupee-sign text-cyan"></i> Max Daily Tariff</label>
                                    <span class="badge bg-slate-800 text-cyan border border-slate-700 px-2.5 py-1 fw-bold fs-7" id="sidePriceVal">
                                        <?php echo $max_price > 0 ? '₹' . number_format($max_price) . '/day' : 'Up to ₹35,000/day'; ?>
                                    </span>
                                </div>
                                <input type="range" class="form-range custom-range-slider" name="max_price" min="1500" max="35000" step="500" value="<?php echo $max_price > 0 ? $max_price : 35000; ?>" oninput="document.getElementById('sidePriceVal').innerText = '₹' + parseInt(this.value).toLocaleString() + '/day'" onchange="document.getElementById('catalogFilterForm').submit()">
                                <div class="d-flex justify-content-between text-secondary micro-text mt-1">
                                    <span>₹1,500</span>
                                    <span>₹35,000+</span>
                                </div>
                            </div>

                            <!-- 8. Submit & Reset Actions -->
                            <div class="d-grid gap-2 mb-3">
                                <button type="submit" class="btn btn-gradient py-2.5 fw-bold shadow rounded-pill">
                                    <i class="fas fa-filter me-2"></i>Apply Filters
                                </button>
                                <a href="<?php echo base_url('cars.php'); ?>" class="btn btn-outline-secondary py-2 text-slate-300 rounded-pill small">
                                    <i class="fas fa-rotate-left me-1.5"></i>Reset All Filters
                                </a>
                            </div>

                            <!-- 9. Quick Surat Area Hub Badges -->
                            <div class="pt-3 border-top border-secondary">
                                <label class="filter-label mb-2"><i class="fas fa-map-pin text-cyan"></i> Popular Surat Hubs</label>
                                <div class="d-flex flex-wrap gap-1.5">
                                    <a href="<?php echo base_url('cars.php?' . http_build_query(array_merge($_GET, ['surat_location' => 'Surat International Airport (STV)']))); ?>" class="quick-hub-badge <?php echo $surat_location === 'Surat International Airport (STV)' ? 'active' : ''; ?>">Airport (STV)</a>
                                    <a href="<?php echo base_url('cars.php?' . http_build_query(array_merge($_GET, ['surat_location' => 'Vesu']))); ?>" class="quick-hub-badge <?php echo $surat_location === 'Vesu' ? 'active' : ''; ?>">Vesu VIP</a>
                                    <a href="<?php echo base_url('cars.php?' . http_build_query(array_merge($_GET, ['surat_location' => 'Adajan']))); ?>" class="quick-hub-badge <?php echo $surat_location === 'Adajan' ? 'active' : ''; ?>">Adajan</a>
                                    <a href="<?php echo base_url('cars.php?' . http_build_query(array_merge($_GET, ['surat_location' => 'Piplod']))); ?>" class="quick-hub-badge <?php echo $surat_location === 'Piplod' ? 'active' : ''; ?>">Piplod VR</a>
                                    <a href="<?php echo base_url('cars.php?' . http_build_query(array_merge($_GET, ['surat_location' => 'Surat Railway Station']))); ?>" class="quick-hub-badge <?php echo $surat_location === 'Surat Railway Station' ? 'active' : ''; ?>">Station</a>
                                    <a href="<?php echo base_url('cars.php?' . http_build_query(array_merge($_GET, ['surat_location' => 'Pal & Althan']))); ?>" class="quick-hub-badge <?php echo $surat_location === 'Pal & Althan' ? 'active' : ''; ?>">Pal/Althan</a>
                                    <a href="<?php echo base_url('cars.php?' . http_build_query(array_merge($_GET, ['surat_location' => 'Dumas Road']))); ?>" class="quick-hub-badge <?php echo $surat_location === 'Dumas Road' ? 'active' : ''; ?>">Dumas</a>
                                    <a href="<?php echo base_url('cars.php?' . http_build_query(array_merge($_GET, ['surat_location' => 'Varachha & Katargam']))); ?>" class="quick-hub-badge <?php echo $surat_location === 'Varachha & Katargam' ? 'active' : ''; ?>">Varachha</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </aside>

            <!-- Right Column: Car Listings & Results -->
            <main class="col-12 col-lg-8 col-xl-8">

                <!-- Results Header & Sorting Bar -->
                <div class="d-flex flex-wrap align-items-center justify-content-between p-3 glass-card mb-3 gap-2">
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <span class="text-slate-300 small">
                            Showing <strong class="text-white fs-6"><?php echo count($cars); ?></strong> verified vehicles
                            <?php if (!empty($surat_location)): ?>
                                available at <span class="text-cyan fw-bold"><?php echo htmlspecialchars($surat_location); ?></span>
                            <?php else: ?>
                                across Surat City
                            <?php endif; ?>
                        </span>

                        <!-- Active Filter Tags Display -->
                        <?php if (!empty($active_filters)): ?>
                            <div class="d-flex flex-wrap gap-1.5 ms-2 align-items-center">
                                <?php foreach ($active_filters as $f): 
                                    $remove_query = array_diff_key($_GET, [$f['key'] => '']);
                                    $remove_url = base_url('cars.php?' . http_build_query($remove_query));
                                ?>
                                    <a href="<?php echo $remove_url; ?>" class="filter-tag-chip" title="Click to remove">
                                        <?php echo htmlspecialchars($f['label']); ?> <i class="fas fa-times ms-1"></i>
                                    </a>
                                <?php endforeach; ?>
                                <a href="<?php echo base_url('cars.php'); ?>" class="text-secondary small ms-1 text-decoration-underline" style="font-size: 0.72rem;">Clear all</a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Sorting Dropdown -->
                    <div class="d-flex align-items-center gap-2 ms-auto">
                        <label class="text-slate-400 small fw-semibold text-nowrap mb-0" style="font-size: 0.78rem;">
                            <i class="fas fa-sort me-1 text-cyan"></i>Sort:
                        </label>
                        <select class="form-select form-select-sm bg-slate-900 border-slate-700 text-white" style="width: auto; font-size: 0.8rem; height: 34px;" onchange="location.href=this.value;">
                            <option value="<?php echo base_url('cars.php?' . http_build_query(array_merge($_GET, ['sort' => 'id_desc']))); ?>" <?php echo $sort === 'id_desc' ? 'selected' : ''; ?>>Newest Arrivals</option>
                            <option value="<?php echo base_url('cars.php?' . http_build_query(array_merge($_GET, ['sort' => 'price_low']))); ?>" <?php echo $sort === 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                            <option value="<?php echo base_url('cars.php?' . http_build_query(array_merge($_GET, ['sort' => 'price_high']))); ?>" <?php echo $sort === 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                            <option value="<?php echo base_url('cars.php?' . http_build_query(array_merge($_GET, ['sort' => 'popular']))); ?>" <?php echo $sort === 'popular' ? 'selected' : ''; ?>>Most Popular</option>
                        </select>
                    </div>
                </div>

                <!-- Cars Grid -->
                <?php if (empty($cars)): ?>
                    <div class="text-center py-5 glass-card">
                        <i class="fas fa-car-crash fa-4x text-slate-500 mb-3"></i>
                        <h4 class="fw-bold text-white">No Vehicles Found</h4>
                        <p class="text-slate-400 mb-4">No available cars match your selected filter criteria. Try resetting filters.</p>
                        <a href="<?php echo base_url('cars.php'); ?>" class="btn btn-gradient px-4 py-2">Reset Filters</a>
                    </div>
                <?php else: ?>
                    <div class="row g-4">
                        <?php foreach ($cars as $car): 
                            $cat_class = 'cat-' . strtolower($car['category']);
                            $is_saved = in_array($car['id'], $saved_car_ids);
                            $book_url = base_url('booking.php?car_id=' . $car['id']);
                            if (!empty($surat_location)) {
                                $book_url .= '&pickup_location=' . urlencode($surat_location);
                            }
                        ?>
                            <div class="col-12 col-md-6">
                                <div class="car-card h-100 d-flex flex-column">
                                    <!-- Car Image Wrap (Large, Clear, Badges Removed) -->
                                    <div class="car-card-image-wrap position-relative">
                                        <a href="<?php echo is_logged_in() ? base_url('saved-cars.php?toggle_id=' . $car['id']) : base_url('login.php'); ?>" class="car-card-heart-btn <?php echo $is_saved ? 'active' : ''; ?>" title="<?php echo $is_saved ? 'Remove from Saved' : 'Save Vehicle'; ?>">
                                            <i class="fas fa-heart"></i>
                                        </a>
                                        <img src="<?php echo get_car_image_url($car['image']); ?>" alt="<?php echo htmlspecialchars($car['brand'] . ' ' . $car['model']); ?>" class="car-card-img" loading="lazy" decoding="async">
                                    </div>

                                    <div class="car-card-body d-flex flex-column flex-grow-1">
                                        <div class="d-flex align-items-center justify-content-between mb-2">
                                            <span class="text-cyan small fw-bold text-uppercase" style="letter-spacing: 0.5px; font-size: 0.78rem;">
                                                <i class="fas fa-tag me-1"></i><?php echo htmlspecialchars($car['brand']); ?> • <?php echo htmlspecialchars($car['category']); ?>
                                            </span>
                                            <div class="rating-stars text-amber small" style="font-size: 0.82rem;"><i class="fas fa-star"></i> 4.9 <span class="text-secondary" style="font-size: 0.75rem;">(48)</span></div>
                                        </div>

                                        <h4 class="car-card-title text-white mb-2" title="<?php echo htmlspecialchars($car['brand'] . ' ' . $car['model']); ?>">
                                            <?php echo htmlspecialchars($car['brand'] . ' ' . $car['model']); ?>
                                        </h4>

                                        <!-- Spec Grid -->
                                        <div class="car-specs-grid-4 mb-2.5">
                                            <div class="car-spec-item" title="Seating Capacity">
                                                <i class="fas fa-user"></i>
                                                <span><?php echo $car['seats']; ?> Seats</span>
                                            </div>
                                            <div class="car-spec-item" title="Transmission">
                                                <i class="fas fa-cog"></i>
                                                <span><?php echo $car['transmission']; ?></span>
                                            </div>
                                            <div class="car-spec-item" title="Fuel Type">
                                                <i class="fas fa-gas-pump"></i>
                                                <span><?php echo $car['fuel_type']; ?></span>
                                            </div>
                                            <div class="car-spec-item" title="Mileage / Range">
                                                <i class="fas fa-tachometer-alt"></i>
                                                <span><?php echo $car['mileage']; ?></span>
                                            </div>
                                        </div>

                                        <div class="car-assurance-chip mb-3">
                                            <i class="fas fa-shield-halved me-1"></i> 100% Insured • Free Doorstep Handover
                                        </div>

                                        <!-- Price & Actions at Bottom -->
                                        <div class="car-card-footer mt-auto pt-3 border-top border-dark-subtle">
                                            <div class="d-flex align-items-baseline justify-content-between mb-2.5">
                                                <span class="text-secondary small fw-semibold" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;">Daily Rate</span>
                                                <div class="text-end">
                                                    <span class="fw-bold text-white fs-4" style="letter-spacing: -0.3px;">₹<?php echo number_format($car['price_per_day']); ?></span>
                                                    <small class="text-secondary" style="font-size: 0.78rem;">/day</small>
                                                </div>
                                            </div>
                                            <div class="d-flex gap-2.5">
                                                <a href="<?php echo base_url('car-details.php?id=' . $car['id']); ?>" class="btn btn-outline-light w-50 py-2 fw-semibold" style="font-size: 0.84rem; border-radius: 50px;">Details</a>
                                                <a href="<?php echo $book_url; ?>" class="btn btn-gradient w-50 py-2 fw-bold" style="font-size: 0.84rem; border-radius: 50px;">Book Now</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
