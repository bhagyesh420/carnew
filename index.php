<?php
$page_title = 'DriveRent - Premium Car Rental in Surat | Drive Beyond Ordinary';
$meta_description = 'Rent luxury sedans, executive SUVs, and sports cars with DriveRent across Surat City. Instant booking, transparent pricing, 24/7 doorstep delivery in Surat.';
include 'includes/header.php';
include 'includes/navbar.php';

// Fetch Featured Cars from Database
try {
    $stmt = $pdo->query("SELECT * FROM cars WHERE status = 'available' ORDER BY featured DESC, id ASC LIMIT 6");
    $featured_cars = $stmt->fetchAll();

    // Fetch Category Counts
    $cat_stmt = $pdo->query("SELECT category, COUNT(*) as count FROM cars WHERE status = 'available' GROUP BY category");
    $cat_counts_raw = $cat_stmt->fetchAll();
    $cat_counts = [];
    foreach ($cat_counts_raw as $c) {
        $cat_counts[$c['category']] = (int)$c['count'];
    }

    // Fetch User's Saved Cars if logged in
    $saved_car_ids = [];
    if (is_logged_in() && isset($_SESSION['user_id'])) {
        $saved_stmt = $pdo->prepare("SELECT car_id FROM saved_cars WHERE user_id = ?");
        $saved_stmt->execute([$_SESSION['user_id']]);
        $saved_car_ids = $saved_stmt->fetchAll(PDO::FETCH_COLUMN);
    }
} catch (PDOException $e) {
    $featured_cars = [];
    $cat_counts = [];
    $saved_car_ids = [];
}
?>

<!-- ===================================================================
     1. HERO SECTION & INTEGRATED SEARCH
     =================================================================== -->
<section class="hero-section">
    <div class="hero-bg-glow"></div>
    <div class="container-fluid px-3 px-lg-5 hero-content-z" style="max-width: 1560px;">
        <div class="row align-items-center g-4 g-xl-5 mb-2 mb-lg-3">
            <div class="col-lg-6">
                <h1 class="hero-title mb-3">Drive Beyond Ordinary in <span class="text-gradient">Surat.</span></h1>
                <p class="hero-subtitle mb-4">Rent luxury sedans, executive SUVs, and sports cars across Surat City. Guaranteed 30-minute doorstep delivery to Vesu, Adajan, Piplod, and Surat Airport.</p>
                
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <a href="<?php echo base_url('cars.php'); ?>" class="btn btn-gradient btn-pill px-4 py-3 shadow-cyan fw-bold">
                        Explore Fleet <i class="fas fa-arrow-right ms-2"></i>
                    </a>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="hero-image-wrapper position-relative text-center">
                    <img src="<?php echo base_url('assets/images/hero/hero_car.jpg'); ?>" alt="DriveRent Luxury Car Surat" class="hero-car-img img-fluid" fetchpriority="high" decoding="async">
                </div>
            </div>
        </div>

        <!-- Floating Search Card (Properly fitted above fold) -->
        <div class="search-widget-card reveal" id="searchWidget">
            <form action="<?php echo base_url('cars.php'); ?>" method="GET" class="search-form">
                <div class="row g-2 g-md-3 align-items-end">
                    <div class="col-xl-3 col-lg-3 col-md-6">
                        <label class="form-label"><i class="fas fa-location-dot text-cyan me-1"></i> Surat Pickup Location</label>
                        <select name="pickup_location" class="form-select" required>
                            <option value="">Select Surat Location</option>
                            <?php foreach (get_surat_locations() as $loc): ?>
                                <option value="<?php echo htmlspecialchars($loc); ?>"><?php echo htmlspecialchars($loc); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-xl-3 col-lg-3 col-md-6">
                        <label class="form-label"><i class="fas fa-map-pin text-accent me-1"></i> Surat Drop Location</label>
                        <select name="drop_location" class="form-select" required>
                            <option value="">Select Surat Location</option>
                            <?php foreach (get_surat_locations() as $loc): ?>
                                <option value="<?php echo htmlspecialchars($loc); ?>"><?php echo htmlspecialchars($loc); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-xl-2 col-lg-2 col-md-6 col-sm-6">
                        <label class="form-label"><i class="fas fa-calendar-alt text-blue me-1"></i> Pickup Date</label>
                        <input type="date" name="pickup_date" id="pickup_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" min="<?php echo date('Y-m-d'); ?>" required>
                    </div>

                    <div class="col-xl-2 col-lg-2 col-md-6 col-sm-6">
                        <label class="form-label"><i class="fas fa-calendar-check text-blue me-1"></i> Return Date</label>
                        <input type="date" name="return_date" id="return_date" class="form-control" value="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" min="<?php echo date('Y-m-d'); ?>" required>
                    </div>

                    <div class="col-xl-2 col-lg-2 col-md-12">
                        <button type="submit" class="btn btn-gradient btn-search-submit w-100 fw-bold shadow-cyan">
                            <i class="fas fa-search me-1.5"></i>Find Cars
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</section>

<!-- Sleek Ambient Divider -->
<div class="glow-divider-wrap">
    <div class="glow-divider-line"></div>
</div>

<!-- ===================================================================
     2. CURATED FLEET (FEATURED CARS)
     =================================================================== -->
<section class="py-5" style="background-color: #0B0F19;">
    <div class="container-fluid px-3 px-lg-5 py-3" style="max-width: 1560px;">
        <div class="text-center mb-4">
            <h2 class="section-title">Choose Your Perfect Ride</h2>
            <p class="section-subtitle mx-auto mb-3">From everyday executive comfort to high-octane luxury sports vehicles.</p>
        </div>

        <!-- Filter Tabs -->
        <div class="fleet-filter-nav mb-4">
            <button type="button" class="fleet-filter-btn active" onclick="filterFleet('all', this)">
                All (<?php echo count($featured_cars); ?>)
            </button>
            <button type="button" class="fleet-filter-btn" onclick="filterFleet('Luxury', this)">
                Luxury
            </button>
            <button type="button" class="fleet-filter-btn" onclick="filterFleet('SUV', this)">
                SUV
            </button>
            <button type="button" class="fleet-filter-btn" onclick="filterFleet('Sedan', this)">
                Sedan
            </button>
            <button type="button" class="fleet-filter-btn" onclick="filterFleet('Sports', this)">
                Sports
            </button>
            <button type="button" class="fleet-filter-btn" onclick="filterFleet('Economy', this)">
                Economy
            </button>
        </div>

        <div class="row g-4" id="fleetGrid">
            <?php foreach ($featured_cars as $car): 
                $cat_class = 'cat-' . strtolower($car['category']);
                $is_saved = in_array($car['id'], $saved_car_ids);
            ?>
                <div class="col-lg-4 col-md-6 reveal fleet-item" data-category="<?php echo htmlspecialchars($car['category']); ?>">
                    <div class="car-card h-100 d-flex flex-column">
                        <div class="car-card-image-wrap position-relative">
                            <a href="<?php echo is_logged_in() ? base_url('saved-cars.php?toggle_id=' . $car['id']) : base_url('login.php'); ?>" class="car-card-heart-btn <?php echo $is_saved ? 'active' : ''; ?>" title="<?php echo $is_saved ? 'Remove from Saved' : 'Save Vehicle'; ?>">
                                <i class="fas fa-heart"></i>
                            </a>
                            <img src="<?php echo get_car_image_url($car['image']); ?>" alt="<?php echo htmlspecialchars($car['brand'] . ' ' . $car['model']); ?>" class="car-card-img" loading="lazy" decoding="async">
                        </div>
                        <div class="car-card-body d-flex flex-column flex-grow-1">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="text-cyan small fw-bold"><i class="fas fa-tag me-1"></i><?php echo htmlspecialchars($car['brand']); ?> • <?php echo htmlspecialchars($car['category']); ?></span>
                                <div class="rating-stars text-amber small"><i class="fas fa-star"></i> 4.9 (48)</div>
                            </div>
                            <h4 class="car-card-title text-white mb-2"><?php echo htmlspecialchars($car['brand'] . ' ' . $car['model']); ?></h4>
                            <div class="car-specs-grid-4 mb-3">
                                <div class="car-spec-item"><i class="fas fa-user"></i><span><?php echo $car['seats']; ?> Seats</span></div>
                                <div class="car-spec-item"><i class="fas fa-cog"></i><span><?php echo $car['transmission']; ?></span></div>
                                <div class="car-spec-item"><i class="fas fa-gas-pump"></i><span><?php echo $car['fuel_type']; ?></span></div>
                                <div class="car-spec-item"><i class="fas fa-tachometer-alt"></i><span><?php echo $car['mileage']; ?></span></div>
                            </div>
                            <div class="mt-auto pt-2.5 border-top border-dark-subtle">
                                <div class="d-flex align-items-baseline justify-content-between mb-2">
                                    <span class="text-secondary small" style="font-size: 0.72rem; text-transform: uppercase;">Daily Rate</span>
                                    <div class="text-end">
                                        <span class="fw-bold text-white fs-5">₹<?php echo number_format($car['price_per_day']); ?></span>
                                        <small class="text-secondary" style="font-size: 0.72rem;">/day</small>
                                    </div>
                                </div>
                                <div class="d-flex gap-2">
                                    <a href="<?php echo base_url('car-details.php?id=' . $car['id']); ?>" class="btn btn-outline-light w-50 btn-sm py-2 fw-semibold">Details</a>
                                    <a href="<?php echo base_url('booking.php?car_id=' . $car['id']); ?>" class="btn btn-gradient w-50 btn-sm py-2 fw-bold">Book Now</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="text-center mt-4">
            <a href="<?php echo base_url('cars.php'); ?>" class="btn btn-outline-light btn-pill px-5 py-2.5 fw-semibold">
                View All Vehicles <i class="fas fa-chevron-right ms-2"></i>
            </a>
        </div>
    </div>
</section>

<!-- ===================================================================
     3. FLEET CATEGORIES
     =================================================================== -->
<section class="py-5 bg-secondary-dark border-top border-bottom border-dark-subtle">
    <div class="container-fluid px-3 px-lg-5 py-3" style="max-width: 1560px;">
        <div class="text-center mb-4">
            <h2 class="section-title">Explore Fleet Categories</h2>
            <p class="section-subtitle mx-auto mb-3">Tailored vehicle selections designed to meet every travel purpose.</p>
        </div>

        <div class="row g-4">
            <?php 
            $categories_list = [
                ['name' => 'Economy', 'icon' => 'fa-car-side', 'desc' => 'Fuel efficient urban cruisers'],
                ['name' => 'Sedan', 'icon' => 'fa-car', 'desc' => 'Executive business sedans'],
                ['name' => 'SUV', 'icon' => 'fa-truck-monster', 'desc' => 'Spacious 7-seater lounge SUVs'],
                ['name' => 'Luxury', 'icon' => 'fa-gem', 'desc' => 'Chauffeur and VIP flagships'],
                ['name' => 'Sports', 'icon' => 'fa-tachometer-alt', 'desc' => 'Supercharged performance'],
                ['name' => 'Electric', 'icon' => 'fa-bolt', 'desc' => 'Zero-emission smart vehicles']
            ];

            foreach ($categories_list as $cat):
                $count = isset($cat_counts[$cat['name']]) ? $cat_counts[$cat['name']] : 4;
            ?>
                <div class="col-lg-2 col-md-4 col-6 reveal">
                    <a href="<?php echo base_url('cars.php?category=' . $cat['name']); ?>" class="category-card d-block text-decoration-none">
                        <div class="category-icon-box">
                            <i class="fas <?php echo $cat['icon']; ?>"></i>
                        </div>
                        <h5 class="fw-bold text-light mb-1"><?php echo $cat['name']; ?></h5>
                        <p class="text-secondary small mb-2"><?php echo $count; ?> Available</p>
                        <span class="text-accent small fw-bold">Explore <i class="fas fa-arrow-right ms-1"></i></span>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ===================================================================
     4. HOW IT WORKS (4 CONCISE STEPS)
     =================================================================== -->
<section class="py-5 bg-primary" id="how-it-works">
    <div class="container py-3">
        <div class="text-center mb-4">
            <h2 class="section-title">4 Simple Steps to Drive</h2>
            <p class="section-subtitle mx-auto mb-3">Quick and seamless rental process anywhere in Surat City.</p>
        </div>

        <div class="row g-4">
            <div class="col-lg-3 col-md-6 reveal">
                <div class="step-card">
                    <span class="step-num">01</span>
                    <div class="step-icon-wrapper"><i class="fas fa-location-dot"></i></div>
                    <h5 class="fw-bold text-white mb-2">Select Hub</h5>
                    <p class="text-secondary small mb-0">Choose pickup & drop from 18+ Surat locations or doorstep.</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 reveal">
                <div class="step-card">
                    <span class="step-num">02</span>
                    <div class="step-icon-wrapper"><i class="fas fa-car-side"></i></div>
                    <h5 class="fw-bold text-white mb-2">Pick Your Car</h5>
                    <p class="text-secondary small mb-0">Select from sanitized sedans, SUVs, luxury, or sports cars.</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 reveal">
                <div class="step-card">
                    <span class="step-num">03</span>
                    <div class="step-icon-wrapper"><i class="fas fa-id-card"></i></div>
                    <h5 class="fw-bold text-white mb-2">Quick KYC</h5>
                    <p class="text-secondary small mb-0">Instant paperless verification with your Driving License & Aadhaar.</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 reveal">
                <div class="step-card">
                    <span class="step-num">04</span>
                    <div class="step-icon-wrapper"><i class="fas fa-key"></i></div>
                    <h5 class="fw-bold text-white mb-2">Doorstep Delivery</h5>
                    <p class="text-secondary small mb-0">Car delivered spotless in 30 minutes with full fuel.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===================================================================
     5. POPULAR SURAT HUBS (4 TOP LOCATIONS)
     =================================================================== -->
<section class="py-5 bg-secondary-dark border-top border-bottom border-dark-subtle">
    <div class="container py-3">
        <div class="text-center mb-4">
            <h2 class="section-title">Popular Pickup Hubs</h2>
            <p class="section-subtitle mx-auto mb-3">Fast delivery across top commercial, residential, and transit zones.</p>
        </div>

        <div class="row g-4">
            <?php 
            $top_hubs = [
                ['name' => 'Surat Airport (STV)', 'subtitle' => 'Dumas Road Terminal', 'cars' => '45+ Cars', 'img' => 'surat_airport.jpg'],
                ['name' => 'Vesu', 'subtitle' => 'VIP Road Corridor', 'cars' => '38+ Cars', 'img' => 'vesu_surat.jpg'],
                ['name' => 'Adajan', 'subtitle' => 'Prime West Zone', 'cars' => '32+ Cars', 'img' => 'adajan_surat.jpg'],
                ['name' => 'Piplod', 'subtitle' => 'VR Mall & Dumas Corridor', 'cars' => '28+ Cars', 'img' => 'piplod_surat.jpg']
            ];
            foreach ($top_hubs as $hub):
            ?>
                <div class="col-lg-3 col-md-6 reveal">
                    <div class="destination-card">
                        <img src="<?php echo get_destination_image_url($hub['img']); ?>" alt="<?php echo htmlspecialchars($hub['name']); ?>" class="destination-img" loading="lazy" decoding="async">
                        <div class="destination-overlay">
                            <h5 class="fw-bold text-light mb-1"><?php echo htmlspecialchars($hub['name']); ?></h5>
                            <small class="text-white-50 d-block mb-1"><?php echo htmlspecialchars($hub['subtitle']); ?></small>
                            <small class="text-cyan fw-bold mb-3 d-block"><i class="fas fa-car-side me-1"></i><?php echo htmlspecialchars($hub['cars']); ?></small>
                            <a href="<?php echo base_url('cars.php?pickup_location=' . urlencode($hub['name'])); ?>" class="btn btn-outline-light btn-sm btn-pill w-100 fw-semibold">Explore Cars</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ===================================================================
     6. WHY CHOOSE US (4 CORE PILLARS)
     =================================================================== -->
<section class="py-5 bg-primary">
    <div class="container py-3">
        <div class="text-center mb-4">
            <h2 class="section-title">Why Drive With Us</h2>
            <p class="section-subtitle mx-auto mb-3">Service guarantees built for peace of mind on every trip.</p>
        </div>

        <div class="row g-4">
            <div class="col-lg-3 col-md-6 reveal">
                <div class="feature-box text-center">
                    <div class="feature-icon-wrapper mx-auto mb-3"><i class="fas fa-shield-halved"></i></div>
                    <h5 class="fw-bold text-white mb-2">Verified Cars</h5>
                    <p class="text-secondary small mb-0">Every car undergoes a 150-point mechanical inspection and deep cleaning.</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 reveal">
                <div class="feature-box text-center">
                    <div class="feature-icon-wrapper mx-auto mb-3"><i class="fas fa-truck-fast"></i></div>
                    <h5 class="fw-bold text-white mb-2">30-Min Delivery</h5>
                    <p class="text-secondary small mb-0">Guaranteed doorstep handover to your home, office, or airport terminal.</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 reveal">
                <div class="feature-box text-center">
                    <div class="feature-icon-wrapper mx-auto mb-3"><i class="fas fa-receipt"></i></div>
                    <h5 class="fw-bold text-white mb-2">Transparent Pricing</h5>
                    <p class="text-secondary small mb-0">Zero hidden fees. Clear upfront daily rates, GST invoice, and zero lock-in.</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 reveal">
                <div class="feature-box text-center">
                    <div class="feature-icon-wrapper mx-auto mb-3"><i class="fas fa-headset"></i></div>
                    <h5 class="fw-bold text-white mb-2">24/7 Roadside SOS</h5>
                    <p class="text-secondary small mb-0">Citywide breakdown backup and dedicated support helpline 24 hours a day.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===================================================================
     7. SURAT TRUST NUMBERS (METRICS RIBBON)
     =================================================================== -->
<section class="py-4 bg-secondary-dark border-top border-bottom border-dark-subtle">
    <div class="container">
        <div class="trust-metrics-ribbon reveal my-2">
            <div class="row g-3 align-items-center">
                <div class="col-lg-3 col-6">
                    <div class="metric-item">
                        <div class="metric-icon-wrap"><i class="fas fa-car-side"></i></div>
                        <div>
                            <div class="metric-number">150+</div>
                            <div class="metric-label">Verified Cars in Surat</div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="metric-item">
                        <div class="metric-icon-wrap"><i class="fas fa-location-dot"></i></div>
                        <div>
                            <div class="metric-number">18+</div>
                            <div class="metric-label">Surat Hubs & Zones</div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="metric-item">
                        <div class="metric-icon-wrap"><i class="fas fa-users"></i></div>
                        <div>
                            <div class="metric-number">10K+</div>
                            <div class="metric-label">Happy Surat Trips</div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="metric-item">
                        <div class="metric-icon-wrap"><i class="fas fa-star text-amber"></i></div>
                        <div>
                            <div class="metric-number">4.9★</div>
                            <div class="metric-label">Customer Rating</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===================================================================
     8. CUSTOMER REVIEWS (3 REAL SURAT REVIEWS)
     =================================================================== -->
<section class="py-5 bg-primary">
    <div class="container py-3">
        <div class="text-center mb-4">
            <h2 class="section-title">What Surat Drivers Say</h2>
            <p class="section-subtitle mx-auto mb-3">Genuine feedback from business leaders and road trip renters in Surat.</p>
        </div>

        <div class="row g-4">
            <div class="col-lg-4 col-md-6 reveal">
                <div class="testimonial-card">
                    <div>
                        <div class="rating-stars mb-3">
                            <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                        </div>
                        <p class="text-secondary mb-3">"Rented the BMW 5 Series from Surat Airport to Vesu. Spotless car, seamless terminal handover, and zero hassle. Truly 5-star service!"</p>
                    </div>
                    <div class="d-flex align-items-center gap-3 pt-3 border-top border-secondary border-opacity-25">
                        <div class="testimonial-avatar">AM</div>
                        <div>
                            <h6 class="fw-bold text-white mb-0">Alex Morgan</h6>
                            <small class="text-cyan">Surat Airport to Vesu</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6 reveal">
                <div class="testimonial-card">
                    <div>
                        <div class="rating-stars mb-3">
                            <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                        </div>
                        <p class="text-secondary mb-3">"Booked Fortuner Legender for a family drive from Adajan to Dumas Beach. Spacious 7-seater, powerful engine, and great pricing. Best rental in Surat!"</p>
                    </div>
                    <div class="d-flex align-items-center gap-3 pt-3 border-top border-secondary border-opacity-25">
                        <div class="testimonial-avatar">RS</div>
                        <div>
                            <h6 class="fw-bold text-white mb-0">Rohan Sharma</h6>
                            <small class="text-cyan">Adajan to Dumas Beach</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6 reveal">
                <div class="testimonial-card">
                    <div>
                        <div class="rating-stars mb-3">
                            <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                        </div>
                        <p class="text-secondary mb-3">"The Porsche 911 Carrera was an absolute thrill cruising on VIP Road corridor. Spotless delivery and super responsive concierge team!"</p>
                    </div>
                    <div class="d-flex align-items-center gap-3 pt-3 border-top border-secondary border-opacity-25">
                        <div class="testimonial-avatar">KP</div>
                        <div>
                            <h6 class="fw-bold text-white mb-0">Karan Patel</h6>
                            <small class="text-cyan">Piplod & VIP Road Renter</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===================================================================
     9. CALL TO ACTION BANNER
     =================================================================== -->
<section class="py-5 bg-secondary-dark">
    <div class="container">
        <div class="cta-banner text-center reveal">
            <h2 class="display-5 fw-bold mb-3">Ready to Drive in Surat?</h2>
            <p class="lead mb-4 mx-auto" style="max-width: 600px;">Your executive luxury car is waiting. Book your next drive across Surat City with DriveRent today.</p>
            <div class="d-flex flex-wrap justify-content-center gap-3">
                <a href="<?php echo base_url('cars.php'); ?>" class="btn btn-dark btn-pill px-5 py-3 fw-bold">
                    <i class="fas fa-car me-2"></i>Browse Surat Fleet
                </a>
                <a href="<?php echo base_url('register.php'); ?>" class="btn btn-outline-light btn-pill px-5 py-3 fw-bold">
                    Create Account
                </a>
            </div>
        </div>
    </div>
</section>

<!-- ===================================================================
     CLIENT-SIDE JAVASCRIPT
     =================================================================== -->
<script>
// Category Filter in Curated Fleet
function filterFleet(category, btnElement) {
    document.querySelectorAll('.fleet-filter-btn').forEach(btn => btn.classList.remove('active'));
    if (btnElement) {
        btnElement.classList.add('active');
    }

    const items = document.querySelectorAll('.fleet-item');
    items.forEach(item => {
        const itemCat = item.getAttribute('data-category');
        if (category === 'all' || itemCat.toLowerCase() === category.toLowerCase()) {
            item.style.display = 'block';
            item.style.opacity = '0';
            setTimeout(() => {
                item.style.transition = 'opacity 0.3s ease';
                item.style.opacity = '1';
            }, 30);
        } else {
            item.style.display = 'none';
        }
    });
}

// Return Date >= Pickup Date
document.addEventListener('DOMContentLoaded', function() {
    const pickupDate = document.getElementById('pickup_date');
    const returnDate = document.getElementById('return_date');
    if (pickupDate && returnDate) {
        pickupDate.addEventListener('change', function() {
            returnDate.min = this.value;
            if (returnDate.value < this.value) {
                returnDate.value = this.value;
            }
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>
