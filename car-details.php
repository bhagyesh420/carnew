<?php
$page_title = 'Car Details - DriveRent';
$extra_js = 'booking.js';
include 'includes/header.php';
include 'includes/navbar.php';

$car_id = isset($_GET['id']) ? (int)$_GET['id'] : 1;

try {
    $stmt = $pdo->prepare("SELECT * FROM cars WHERE id = ?");
    $stmt->execute([$car_id]);
    $car = $stmt->fetch();

    if (!$car) {
        header('Location: ' . base_url('cars.php'));
        exit();
    }

    // Fetch Reviews for this car
    $rev_stmt = $pdo->prepare("SELECT r.*, u.name as user_name FROM reviews r JOIN users u ON r.user_id = u.id WHERE r.car_id = ? ORDER BY r.id DESC");
    $rev_stmt->execute([$car_id]);
    $reviews = $rev_stmt->fetchAll();

    // Fetch 3 Similar / Related Cars
    $sim_stmt = $pdo->prepare("SELECT * FROM cars WHERE id != ? AND (category = ? OR brand = ?) AND status = 'available' LIMIT 3");
    $sim_stmt->execute([$car_id, $car['category'], $car['brand']]);
    $similar_cars = $sim_stmt->fetchAll();
    if (count($similar_cars) < 3) {
        $sim_stmt_fallback = $pdo->prepare("SELECT * FROM cars WHERE id != ? AND status = 'available' LIMIT 3");
        $sim_stmt_fallback->execute([$car_id]);
        $similar_cars = $sim_stmt_fallback->fetchAll();
    }
} catch (PDOException $e) {
    header('Location: ' . base_url('cars.php'));
    exit();
}

$page_title = $car['brand'] . ' ' . $car['model'] . ' - DriveRent Details';
$cat_class = 'cat-' . strtolower($car['category']);
?>

<!-- Breadcrumb Header -->
<section class="bg-secondary-dark border-bottom border-dark-subtle page-header-section">
    <div class="container">
        <div class="luxury-header-card p-3 p-md-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="<?php echo base_url('index.php'); ?>" class="text-accent text-decoration-none">Home</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo base_url('cars.php'); ?>" class="text-accent text-decoration-none">Surat Fleet</a></li>
                    <li class="breadcrumb-item active text-white" aria-current="page"><?php echo htmlspecialchars($car['brand'] . ' ' . $car['model']); ?></li>
                </ol>
            </nav>
        </div>
    </div>
</section>

<!-- Details Layout -->
<section class="py-5" style="background-color: #0B0F19;">
    <div class="container">
        <div class="row g-5">
            <!-- Left Side Details & Specs -->
            <div class="col-lg-8">
                <!-- Vehicle Header -->
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="badge <?php echo $cat_class; ?> px-3 py-1.5 fw-bold text-uppercase"><?php echo htmlspecialchars($car['category']); ?> FLEET</span>
                        </div>
                        <h1 class="display-6 fw-bold mb-1 text-white"><?php echo htmlspecialchars($car['brand'] . ' ' . $car['model']); ?></h1>
                        <p class="text-slate-400 mb-0"><i class="fas fa-calendar-alt text-cyan me-1.5"></i>Model Year <?php echo $car['year']; ?> | <i class="fas fa-palette text-amber mx-1.5"></i><?php echo htmlspecialchars($car['color']); ?> | <i class="fas fa-location-dot text-rose mx-1.5"></i>Surat City Hubs</p>
                    </div>
                    <div class="text-md-end mt-3 mt-md-0">
                        <div class="fs-2 fw-bold text-emerald"><?php echo format_currency($car['price_per_day']); ?> <span class="fs-6 text-slate-400 fw-normal">/ day</span></div>
                        <div class="badge bg-slate-800 text-cyan border border-slate-700 px-2.5 py-1 mt-1"><?php echo format_currency($car['price_per_hour']); ?> / hour rate available</div>
                    </div>
                </div>

                <!-- Car Showcase Image Container -->
                <div class="glass-card p-4 text-center mb-5 position-relative overflow-hidden">
                    <div class="position-absolute top-0 start-0 w-100 h-100 bg-radial-glow opacity-25 pointer-events-none"></div>
                    <img src="<?php echo get_car_image_url($car['image']); ?>" alt="<?php echo htmlspecialchars($car['brand'] . ' ' . $car['model']); ?>" class="img-fluid rounded mb-2" fetchpriority="high" decoding="async" style="max-height: 400px; object-fit: contain;">
                </div>

                <!-- Specifications Badges Grid (8 Comprehensive Specs) -->
                <h4 class="fw-bold mb-3 text-white"><i class="fas fa-list-check text-cyan me-2"></i>Executive Technical Specifications</h4>
                <div class="row g-3 mb-5">
                    <div class="col-md-3 col-6">
                        <div class="detail-spec-card">
                            <i class="fas fa-users text-cyan"></i>
                            <div class="spec-label">Seating</div>
                            <div class="spec-val"><?php echo $car['seats']; ?> Persons</div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="detail-spec-card">
                            <i class="fas fa-cog text-accent"></i>
                            <div class="spec-label">Transmission</div>
                            <div class="spec-val"><?php echo $car['transmission']; ?></div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="detail-spec-card">
                            <i class="fas fa-gas-pump text-amber"></i>
                            <div class="spec-label">Fuel Engine</div>
                            <div class="spec-val"><?php echo $car['fuel_type']; ?></div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="detail-spec-card">
                            <i class="fas fa-tachometer-alt text-emerald"></i>
                            <div class="spec-label">Efficiency</div>
                            <div class="spec-val"><?php echo $car['mileage']; ?></div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="detail-spec-card">
                            <i class="fas fa-calendar-check text-purple"></i>
                            <div class="spec-label">Model Year</div>
                            <div class="spec-val"><?php echo $car['year']; ?> Edition</div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="detail-spec-card">
                            <i class="fas fa-shield-halved text-emerald"></i>
                            <div class="spec-label">Security Deposit</div>
                            <div class="spec-val">₹5,000 (Refundable)</div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="detail-spec-card">
                            <i class="fas fa-palette text-rose"></i>
                            <div class="spec-label">Exterior Finish</div>
                            <div class="spec-val"><?php echo htmlspecialchars($car['color']); ?></div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="detail-spec-card">
                            <i class="fas fa-clock text-cyan"></i>
                            <div class="spec-label">Hourly Option</div>
                            <div class="spec-val"><?php echo format_currency($car['price_per_hour']); ?>/hr</div>
                        </div>
                    </div>
                </div>

                <!-- Vehicle Overview & Features -->
                <h4 class="fw-bold mb-3 text-white"><i class="fas fa-info-circle text-accent me-2"></i>Vehicle Overview</h4>
                <p class="text-slate-300 leading-relaxed mb-5 fs-6"><?php echo nl2br(htmlspecialchars($car['description'])); ?></p>

                <h4 class="fw-bold mb-3 text-white"><i class="fas fa-star text-amber me-2"></i>Included Premium Amenities & In-Car Tech</h4>
                <div class="d-flex flex-wrap gap-2 mb-5">
                    <?php 
                    $features = [
                        'Dual Zone Automatic Climate Control',
                        'Apple CarPlay & Android Auto',
                        'High-Definition 360° Parking Camera',
                        'Panoramic Glass Sunroof',
                        'Adaptive Cruise & ADAS Assist',
                        'Ultra-Smooth Air Suspension',
                        'Ventilated Memory Leather Seats',
                        'Premium Meridian/Bose Audio',
                        'Keyless Smart Entry & Push Start',
                        'Fast Wireless Phone Charging'
                    ];
                    foreach ($features as $feat):
                    ?>
                        <div class="amenity-pill">
                            <i class="fas fa-check-circle"></i>
                            <span><?php echo $feat; ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Rental Inclusions Guarantee -->
                <h4 class="fw-bold mb-3 text-white"><i class="fas fa-shield-halved text-emerald me-2"></i>DriveRent Guarantees Included Free</h4>
                <div class="row g-3 mb-5">
                    <div class="col-md-6">
                        <div class="p-3 bg-slate-900 border border-slate-700 rounded-3 d-flex align-items-center gap-3">
                            <div class="w-10 h-10 rounded-circle bg-emerald-500 bg-opacity-20 text-emerald d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <div>
                                <h6 class="text-white fw-bold mb-0">100% Comprehensive Insurance</h6>
                                <small class="text-slate-400">Zero liability with third-party & bumper-to-bumper cover</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 bg-slate-900 border border-slate-700 rounded-3 d-flex align-items-center gap-3">
                            <div class="w-10 h-10 rounded-circle bg-cyan-500 bg-opacity-20 text-cyan d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                <i class="fas fa-truck-fast"></i>
                            </div>
                            <div>
                                <h6 class="text-white fw-bold mb-0">30-Minute Doorstep Drop</h6>
                                <small class="text-slate-400">Delivered right to your doorstep across Surat City</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 bg-slate-900 border border-slate-700 rounded-3 d-flex align-items-center gap-3">
                            <div class="w-10 h-10 rounded-circle bg-amber-500 bg-opacity-20 text-amber d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                <i class="fas fa-rotate-left"></i>
                            </div>
                            <div>
                                <h6 class="text-white fw-bold mb-0">Free Cancellation up to 24H</h6>
                                <small class="text-slate-400">100% instant refund if your plans change</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 bg-slate-900 border border-slate-700 rounded-3 d-flex align-items-center gap-3">
                            <div class="w-10 h-10 rounded-circle bg-primary bg-opacity-20 text-accent d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                <i class="fas fa-headset"></i>
                            </div>
                            <div>
                                <h6 class="text-white fw-bold mb-0">24/7 Dedicated Concierge Support</h6>
                                <small class="text-slate-400">Direct helpline & roadside assistance assistance</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Reviews Section -->
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <h4 class="fw-bold text-white mb-0"><i class="fas fa-comments text-accent me-2"></i>Verified Client Reviews (<?php echo count($reviews); ?>)</h4>
                    <div class="d-flex align-items-center gap-1 bg-slate-900 px-3 py-1.5 rounded-pill border border-slate-700">
                        <i class="fas fa-star text-amber"></i>
                        <span class="text-white fw-bold">4.9 / 5.0</span>
                        <span class="text-slate-400 small ms-1">(100% Recommended)</span>
                    </div>
                </div>

                <?php if (empty($reviews)): ?>
                    <div class="p-4 bg-slate-900 rounded-3 border border-slate-700 text-center text-slate-400 mb-5">
                        <i class="fas fa-comment-dots fa-2x mb-2 text-slate-500"></i>
                        <p class="mb-0">No reviews submitted yet for this vehicle. Be the first to rent and leave feedback!</p>
                    </div>
                <?php else: ?>
                    <div class="d-flex flex-column gap-3 mb-5">
                        <?php foreach ($reviews as $rev): ?>
                            <div class="glass-card p-4">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="w-8 h-8 rounded-circle bg-primary bg-opacity-20 text-accent fw-bold d-flex align-items-center justify-content-center" style="width: 34px; height: 34px;">
                                            <?php echo strtoupper(substr($rev['user_name'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <h6 class="fw-bold text-white mb-0"><?php echo htmlspecialchars($rev['user_name']); ?></h6>
                                            <small class="text-emerald"><i class="fas fa-circle-check me-1"></i>Verified Rental</small>
                                        </div>
                                    </div>
                                    <div class="rating-stars text-amber small">
                                        <?php for ($i=1; $i<=5; $i++): ?>
                                            <i class="fas fa-star <?php echo $i <= $rev['rating'] ? 'text-amber' : 'text-slate-600'; ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <p class="text-slate-300 small mb-2"><?php echo htmlspecialchars($rev['review']); ?></p>
                                <small class="text-slate-500"><i class="fas fa-calendar me-1"></i><?php echo date('M d, Y', strtotime($rev['created_at'])); ?></small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Similar Vehicles Showcase -->
                <?php if (!empty($similar_cars)): ?>
                    <h4 class="fw-bold mb-4 text-white"><i class="fas fa-car-side text-cyan me-2"></i>Similar Fleet Recommendations</h4>
                    <div class="row g-4">
                        <?php foreach ($similar_cars as $sim): ?>
                            <div class="col-md-4">
                                <div class="car-card h-100">
                                    <div class="car-card-image-wrap">
                                        <span class="car-badge-price"><?php echo format_currency($sim['price_per_day']); ?> / d</span>
                                        <img src="<?php echo get_car_image_url($sim['image']); ?>" alt="Car" class="car-card-img" loading="lazy">
                                    </div>
                                    <div class="car-card-body p-3">
                                        <h6 class="text-white fw-bold mb-2"><?php echo htmlspecialchars($sim['brand'] . ' ' . $sim['model']); ?></h6>
                                        <div class="d-flex justify-content-between small text-slate-400 mb-3">
                                            <span><?php echo $sim['seats']; ?> Seats</span>
                                            <span><?php echo $sim['transmission']; ?></span>
                                            <span><?php echo $sim['fuel_type']; ?></span>
                                        </div>
                                        <a href="<?php echo base_url('car-details.php?id=' . $sim['id']); ?>" class="btn btn-outline-info btn-sm w-100 py-1.5 rounded-pill">View Specs</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Right Sticky Booking Calculator Widget -->
            <div class="col-lg-4 sticky-calc-column">
                <div class="booking-calc-box">
                    <h5 class="fw-bold text-white mb-2.5 border-bottom border-slate-700 pb-2.5 d-flex align-items-center justify-content-between">
                        <span><i class="fas fa-calculator text-cyan me-2"></i>Live Pricing Calculator</span>
                        <span class="badge bg-slate-900 border border-slate-700 text-cyan px-2 py-0.5 rounded-pill" style="font-size: 0.7rem;">Surat Rates</span>
                    </h5>
                    
                    <form action="<?php echo base_url('booking.php'); ?>" method="GET" id="calcForm">
                        <input type="hidden" name="car_id" value="<?php echo $car['id']; ?>">
                        <input type="hidden" id="car_price_per_day" value="<?php echo $car['price_per_day']; ?>">

                        <div class="mb-2.5">
                            <label class="form-label text-slate-300 small fw-bold mb-1"><i class="fas fa-location-dot text-cyan me-1"></i> Surat Pickup Hub</label>
                            <select name="pickup_location" class="form-select form-select-sm" required>
                                <?php foreach (get_surat_locations() as $loc): ?>
                                    <option value="<?php echo htmlspecialchars($loc); ?>"><?php echo htmlspecialchars($loc); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-2.5">
                            <label class="form-label text-slate-300 small fw-bold mb-1"><i class="fas fa-map-pin text-cyan me-1"></i> Surat Drop Hub</label>
                            <select name="drop_location" class="form-select form-select-sm" required>
                                <?php foreach (get_surat_locations() as $loc): ?>
                                    <option value="<?php echo htmlspecialchars($loc); ?>"><?php echo htmlspecialchars($loc); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="row g-2 mb-2.5">
                            <div class="col-6">
                                <label class="form-label text-slate-300 small fw-bold mb-1">Pickup Date</label>
                                <input type="date" name="pickup_date" id="pickup_date" class="form-control form-control-sm" value="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required onchange="recalculatePrice()">
                            </div>
                            <div class="col-6">
                                <label class="form-label text-slate-300 small fw-bold mb-1">Return Date</label>
                                <input type="date" name="return_date" id="return_date" class="form-control form-control-sm" value="<?php echo date('Y-m-d', strtotime('+4 days')); ?>" required onchange="recalculatePrice()">
                            </div>
                        </div>

                        <!-- Live Cost Breakdown Container -->
                        <div class="bg-slate-900 p-3 rounded-3 mb-3 border border-slate-700">
                            <div class="calc-row">
                                <span class="text-slate-400">Duration:</span>
                                <strong class="text-white" id="disp_rental_days">3 Days</strong>
                            </div>
                            <div class="calc-row">
                                <span class="text-slate-400">Daily Tariff:</span>
                                <span class="text-white"><?php echo format_currency($car['price_per_day']); ?></span>
                            </div>
                            <div class="calc-row">
                                <span class="text-slate-400">Base Subtotal:</span>
                                <span class="text-white" id="disp_subtotal"><?php echo format_currency($car['price_per_day'] * 3); ?></span>
                            </div>
                            <div class="calc-row">
                                <span class="text-slate-400">GST (18%):</span>
                                <span class="text-white" id="disp_tax"><?php echo format_currency(($car['price_per_day'] * 3) * 0.18); ?></span>
                            </div>
                            <div class="calc-row">
                                <span class="text-slate-400">Refundable Deposit:</span>
                                <span class="text-white" id="disp_deposit">₹5,000.00</span>
                            </div>
                            <div class="calc-row total-row">
                                <span>Estimated Total:</span>
                                <span class="text-emerald" id="disp_total"><?php echo format_currency(($car['price_per_day'] * 3 * 1.18) + 5000); ?></span>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-gradient w-100 py-2.5 rounded-pill fw-bold shadow"><i class="fas fa-bolt me-2"></i>Proceed to Instant Booking</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
function recalculatePrice() {
    var pDate = new Date(document.getElementById('pickup_date').value);
    var rDate = new Date(document.getElementById('return_date').value);
    var rate = parseFloat(document.getElementById('car_price_per_day').value) || 0;

    var diffTime = rDate.getTime() - pDate.getTime();
    var days = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    if (days < 1) days = 1;

    var subtotal = days * rate;
    var tax = subtotal * 0.18;
    var deposit = 5000;
    var grandTotal = subtotal + tax + deposit;

    document.getElementById('disp_rental_days').innerText = days + (days === 1 ? ' Day' : ' Days');
    document.getElementById('disp_subtotal').innerText = '₹' + subtotal.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.getElementById('disp_tax').innerText = '₹' + tax.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.getElementById('disp_total').innerText = '₹' + grandTotal.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
}
</script>

<?php include 'includes/footer.php'; ?>
