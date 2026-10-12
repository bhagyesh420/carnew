<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth-check.php';

$user = get_logged_user($pdo);
if (!$user) {
    header('Location: ' . base_url('login.php'));
    exit();
}
$profile = get_user_profile($pdo, $user['id']);
$completion = calculate_profile_completion($user, $profile);
$csrf_token = generate_csrf_token();

// Handle Cancel Booking Request directly from User Dashboard
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel_booking') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        flash_message('danger', 'Security token expired. Please try again.');
    } else {
        $booking_id = (int)$_POST['booking_id'];
        $reason = sanitize($_POST['cancel_reason'] ?? 'User requested cancellation');

        $c_stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ? AND user_id = ?");
        $c_stmt->execute([$booking_id, $user['id']]);
        $bk = $c_stmt->fetch();

        if ($bk && in_array($bk['booking_status'], ['pending', 'confirmed'])) {
            $upd = $pdo->prepare("UPDATE bookings SET booking_status = 'cancelled', payment_status = 'refunded' WHERE id = ?");
            $upd->execute([$booking_id]);

            // Release car availability if no other active/confirmed bookings remain
            $chk_car = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE car_id = ? AND booking_status IN ('pending', 'confirmed', 'active')");
            $chk_car->execute([$bk['car_id']]);
            if ($chk_car->fetchColumn() == 0) {
                $pdo->prepare("UPDATE cars SET status = 'available' WHERE id = ?")->execute([$bk['car_id']]);
            }

            create_notification($pdo, $user['id'], 'Booking Cancelled', 'Your reservation ' . $bk['booking_id'] . ' has been cancelled. Refund processing initiated.', 'danger');
            flash_message('info', 'Booking ' . $bk['booking_id'] . ' has been cancelled successfully.');
            header('Location: ' . base_url('dashboard.php'));
            exit();
        } else {
            flash_message('danger', 'This reservation cannot be cancelled at its current status.');
        }
    }
}

// Fetch Detailed Booking Stats
$stmt = $pdo->prepare("SELECT 
    COUNT(*) as total_bookings,
    SUM(CASE WHEN booking_status IN ('confirmed', 'active', 'pending') THEN 1 ELSE 0 END) as upcoming_bookings,
    SUM(CASE WHEN booking_status = 'completed' THEN 1 ELSE 0 END) as completed_bookings,
    SUM(CASE WHEN booking_status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_bookings,
    SUM(CASE WHEN booking_status != 'cancelled' THEN total_amount ELSE 0 END) as total_spent
    FROM bookings WHERE user_id = ?");
$stmt->execute([$user['id']]);
$stats = $stmt->fetch();

// Fetch Saved Cars Count
$saved_count = 0;
try {
    $saved_stmt = $pdo->prepare("SELECT COUNT(*) as total_saved FROM saved_cars WHERE user_id = ?");
    $saved_stmt->execute([$user['id']]);
    $saved_res = $saved_stmt->fetch();
    if ($saved_res && isset($saved_res['total_saved'])) {
        $saved_count = (int)$saved_res['total_saved'];
    }
} catch (Exception $ex) {
    $saved_count = 0;
}

// Fetch Active / Next Upcoming Trip Spotlight
$spotlight_stmt = $pdo->prepare("SELECT b.*, c.brand, c.model, c.category, c.image, c.transmission, c.fuel_type, c.seats 
    FROM bookings b 
    JOIN cars c ON b.car_id = c.id 
    WHERE b.user_id = ? AND b.booking_status IN ('active', 'confirmed', 'pending') 
    ORDER BY b.pickup_date ASC LIMIT 1");
$spotlight_stmt->execute([$user['id']]);
$active_trip = $spotlight_stmt->fetch();


// Fetch Recent Bookings (Limit 6)
$rec_stmt = $pdo->prepare("SELECT b.*, c.brand, c.model, c.category, c.image, c.transmission, c.fuel_type 
    FROM bookings b 
    JOIN cars c ON b.car_id = c.id 
    WHERE b.user_id = ? 
    ORDER BY b.created_at DESC LIMIT 6");
$rec_stmt->execute([$user['id']]);
$recent_bookings = $rec_stmt->fetchAll();

// Fetch Unread Notifications (Limit 4)
$notif_stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 4");
$notif_stmt->execute([$user['id']]);
$recent_notifs = $notif_stmt->fetchAll();

$page_title = 'Executive VIP Dashboard - DriveRent Surat';
include 'includes/header.php';
include 'includes/navbar.php';
?>

<!-- ==========================================
     EXECUTIVE VIP MEMBER BANNER
========================================== -->
<section class="bg-secondary-dark border-bottom border-dark-subtle page-header-section">
    <div class="container-fluid px-3 px-md-4 px-xl-5" style="max-width: 1600px;">
        <!-- Main VIP Hero Card Frame -->
        <div class="luxury-vip-banner p-3 p-md-4 p-xl-4">
            <div class="row align-items-center g-3 g-xl-4">
                <!-- User Profile & VIP Identity -->
                <div class="col-12 col-xl-7">
                    <div class="d-flex align-items-center gap-3 gap-md-4">
                        <div class="vip-avatar-wrap">
                            <img src="<?php echo get_user_avatar_url($user); ?>" alt="<?php echo htmlspecialchars($user['name']); ?>" class="vip-avatar-img" onerror="this.onerror=null; this.src='<?php echo base_url('assets/images/default_avatar.png'); ?>';">
                            <span class="vip-pulse-dot" title="Account Active & Verified"></span>
                        </div>
                        <div>
                            <h2 class="fw-bold mb-1 text-white fs-3">Welcome back, <span class="text-accent"><?php echo htmlspecialchars($user['name']); ?></span>! 👋</h2>
                            <div class="text-slate-400 mb-0 small d-flex align-items-center flex-wrap gap-2 gap-md-3">
                                <span class="text-nowrap"><i class="fas fa-envelope me-1 text-accent"></i><?php echo htmlspecialchars($user['email']); ?></span>
                                <span class="text-nowrap"><i class="fas fa-phone me-1 text-accent"></i><?php echo htmlspecialchars($user['phone'] ?? '+91 Not Provided'); ?></span>
                                <span class="text-nowrap"><i class="fas fa-location-dot me-1 text-cyan"></i>Surat, Gujarat</span>
                                <span class="badge bg-dark text-slate-300 font-monospace border border-slate-700 text-nowrap py-1 px-2" style="font-size: 0.78rem;">
                                    <i class="fas fa-id-badge text-cyan me-1"></i>ID: #DR-CUST-<?php echo str_pad($user['id'], 4, '0', STR_PAD_LEFT); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Executive Quick Action Buttons -->
                <div class="col-12 col-xl-5">
                    <div class="d-flex flex-wrap align-items-center justify-content-start justify-content-xl-end gap-2">
                        <a href="<?php echo base_url('cars.php'); ?>" class="btn btn-gradient px-3 px-sm-4 py-2 btn-pill shadow-cyan fw-bold text-nowrap">
                            <i class="fas fa-car me-1"></i>Reserve Vehicle
                        </a>
                        <a href="<?php echo base_url('my-bookings.php'); ?>" class="btn btn-outline-info px-3 py-2 btn-pill fw-semibold text-nowrap">
                            <i class="fas fa-calendar-check me-1"></i>My Bookings
                        </a>
                        <a href="<?php echo base_url('profile.php'); ?>" class="btn btn-outline-light px-3 py-2 btn-pill fw-semibold text-nowrap">
                            <i class="fas fa-user-gear me-1"></i>Edit Profile
                        </a>
                    </div>
                </div>
            </div>

            <!-- Profile Completion Progress & Checklist Bar -->
            <?php if ($completion < 100): ?>
                <div class="mt-3 pt-2.5 border-top border-slate-800">
                    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2.5">
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="text-white fw-bold small d-flex align-items-center gap-2">
                                    <i class="fas fa-list-check text-cyan"></i>
                                    <span>KYC Verification & Profile Completion:</span>
                                    <span class="text-cyan fw-bold"><?php echo $completion; ?>%</span>
                                </span>
                                <span class="text-slate-400 micro-text">Complete verification for 100% contactless car unlock</span>
                            </div>
                            <div class="progress bg-slate-900 rounded-pill" style="height: 8px; border: 1px solid rgba(255,255,255,0.08);">
                                <div class="progress-bar" role="progressbar" style="width: <?php echo $completion; ?>%; background: linear-gradient(90deg, #0EA5E9, #10B981);" aria-valuenow="<?php echo $completion; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                        <a href="<?php echo base_url('profile.php'); ?>" class="btn btn-sm btn-outline-cyan rounded-pill px-3 py-1.5 text-nowrap fw-semibold">
                            <i class="fas fa-id-card me-1.5"></i>Upload License / KYC
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- ==========================================
     DASHBOARD MAIN METRICS & CONTENT
========================================== -->
<section class="py-4 bg-primary min-vh-100">
    <div class="container-fluid px-3 px-md-4 px-xl-5" style="max-width: 1600px;">
        <?php render_flash_messages(); ?>

        <!-- 4 Ultra-Luxury Stat Cards -->
        <div class="row g-3 g-xl-4 mb-4">
            <!-- Total Reservations -->
            <div class="col-xl-3 col-md-6">
                <a href="<?php echo base_url('my-bookings.php'); ?>" class="text-decoration-none">
                    <div class="luxury-stat-box cyan">
                        <div class="d-flex align-items-start justify-content-between">
                            <div>
                                <span class="text-uppercase fw-bold text-accent" style="font-size: 0.8rem; letter-spacing: 0.5px;">Total Reservations</span>
                                <div class="stat-metric-number"><?php echo number_format($stats['total_bookings'] ?? 0); ?></div>
                                <span class="badge rounded-pill bg-cyan bg-opacity-15 text-cyan border border-cyan border-opacity-30 px-2.5 py-1" style="font-size: 0.78rem;">
                                    <i class="fas fa-road me-1"></i>All-Time Bookings
                                </span>
                            </div>
                            <div class="stat-icon-circle cyan">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Upcoming / Active Trips -->
            <div class="col-xl-3 col-md-6">
                <a href="<?php echo base_url('my-bookings.php?status=upcoming'); ?>" class="text-decoration-none">
                    <div class="luxury-stat-box amber">
                        <div class="d-flex align-items-start justify-content-between">
                            <div>
                                <span class="text-uppercase fw-bold text-amber" style="font-size: 0.8rem; letter-spacing: 0.5px;">Active / Upcoming</span>
                                <div class="stat-metric-number text-amber"><?php echo number_format($stats['upcoming_bookings'] ?? 0); ?></div>
                                <span class="badge rounded-pill bg-amber bg-opacity-15 text-amber border border-amber border-opacity-30 px-2.5 py-1" style="font-size: 0.78rem;">
                                    <i class="fas fa-clock me-1"></i>Trip Ready
                                </span>
                            </div>
                            <div class="stat-icon-circle amber">
                                <i class="fas fa-key"></i>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Total Luxury Spend -->
            <div class="col-xl-3 col-md-6">
                <div class="luxury-stat-box emerald">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <span class="text-uppercase fw-bold text-emerald" style="font-size: 0.8rem; letter-spacing: 0.5px;">Luxury Travel Spend</span>
                            <div class="stat-metric-number text-emerald" style="font-size: clamp(1.4rem, 1.8vw, 1.95rem);">
                                <?php echo format_currency($stats['total_spent'] ?? 0); ?>
                            </div>
                            <span class="badge rounded-pill bg-emerald bg-opacity-15 text-emerald border border-emerald border-opacity-30 px-2.5 py-1" style="font-size: 0.78rem;">
                                <i class="fas fa-shield-check me-1"></i><?php echo number_format($stats['completed_bookings'] ?? 0); ?> Completed Trips
                            </span>
                        </div>
                        <div class="stat-icon-circle emerald">
                            <i class="fas fa-wallet"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Saved Luxury Garage -->
            <div class="col-xl-3 col-md-6">
                <a href="<?php echo base_url('saved-cars.php'); ?>" class="text-decoration-none">
                    <div class="luxury-stat-box purple">
                        <div class="d-flex align-items-start justify-content-between">
                            <div>
                                <span class="text-uppercase fw-bold text-purple" style="font-size: 0.8rem; letter-spacing: 0.5px;">Saved Garage</span>
                                <div class="stat-metric-number text-purple"><?php echo number_format($saved_count); ?></div>
                                <span class="badge rounded-pill bg-purple bg-opacity-15 text-purple border border-purple border-opacity-30 px-2.5 py-1" style="font-size: 0.78rem;">
                                    <i class="fas fa-heart me-1"></i>Wishlist Fleet
                                </span>
                            </div>
                            <div class="stat-icon-circle purple">
                                <i class="fas fa-heart"></i>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        </div>

        <!-- ==========================================
             ACTIVE TRIP SPOTLIGHT OR FLAGSHIP RECOMMENDATION
        ========================================== -->
        <?php if ($active_trip): ?>
            <div class="luxury-spotlight-card mb-4">
                <!-- Top Tier: Active Vehicle & Total Tariff/Status -->
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 pb-3 mb-3 border-bottom border-slate-700">
                    <div class="d-flex align-items-center gap-3">
                        <div class="position-relative flex-shrink-0" style="width: 120px; height: 80px;">
                            <img src="<?php echo get_car_image_url($active_trip['image']); ?>" alt="Active Car" class="w-100 h-100 rounded-3 border border-secondary" style="object-fit: cover;">
                            <span class="badge bg-accent position-absolute top-0 start-0 m-1 micro-text"><?php echo htmlspecialchars($active_trip['category']); ?></span>
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                                <h4 class="fw-bold text-white mb-0"><?php echo htmlspecialchars($active_trip['brand'] . ' ' . $active_trip['model']); ?></h4>
                                <span class="badge bg-accent text-light micro-text px-2 py-0.5"><?php echo htmlspecialchars($active_trip['category']); ?></span>
                                <span class="badge bg-warning text-dark fw-bold micro-text text-uppercase"><i class="fas fa-bell me-1"></i>Current Itinerary</span>
                            </div>
                            <p class="text-slate-400 small mb-0 d-flex align-items-center gap-2 flex-wrap">
                                <span><i class="fas fa-gas-pump me-1 text-accent"></i><?php echo $active_trip['fuel_type']; ?></span>
                                <span>&bull;</span>
                                <span><i class="fas fa-cog me-1 text-accent"></i><?php echo $active_trip['transmission']; ?></span>
                                <span>&bull;</span>
                                <span><i class="fas fa-users me-1 text-accent"></i><?php echo $active_trip['seats']; ?> Seats</span>
                                <span>&bull;</span>
                                <span class="text-cyan font-monospace fw-semibold">REF: <?php echo htmlspecialchars($active_trip['booking_id']); ?></span>
                                <button type="button" class="btn-copy-code" onclick="copyBookingRef('<?php echo htmlspecialchars($active_trip['booking_id']); ?>')" title="Copy Reference ID">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </p>
                        </div>
                    </div>

                    <div class="d-flex flex-column align-items-start align-items-md-end text-md-end flex-shrink-0">
                        <div class="mb-1">
                            <span class="text-slate-400 micro-text d-block">Total Tariff</span>
                            <span class="fs-4 fw-bold text-accent text-nowrap"><?php echo format_currency($active_trip['total_amount']); ?></span>
                        </div>
                        <span class="glow-status-pill text-nowrap <?php echo strtolower($active_trip['booking_status']); ?>">
                            <span class="glow-dot"></span>
                            <?php echo $active_trip['booking_status']; ?>
                        </span>
                    </div>
                </div>

                <!-- Bottom Tier: Schedule Details + Action Controls -->
                <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 gap-lg-4">
                    <div class="d-flex flex-column flex-sm-row gap-3 gap-md-4 flex-grow-1">
                        <div class="flex-fill">
                            <span class="text-slate-400 micro-text text-uppercase fw-bold d-block mb-1">
                                <i class="fas fa-location-dot me-1 text-accent"></i>Pickup Schedule
                            </span>
                            <strong class="text-white small d-block mb-1"><?php echo htmlspecialchars($active_trip['pickup_location']); ?></strong>
                            <span class="text-cyan small fw-semibold text-nowrap"><?php echo date('d M Y', strtotime($active_trip['pickup_date'])); ?> @ <?php echo date('h:i A', strtotime($active_trip['pickup_time'])); ?></span>
                        </div>
                        <div class="flex-fill">
                            <span class="text-slate-400 micro-text text-uppercase fw-bold d-block mb-1">
                                <i class="fas fa-flag-checkered me-1 text-emerald"></i>Dropoff Schedule
                            </span>
                            <strong class="text-white small d-block mb-1"><?php echo htmlspecialchars($active_trip['drop_location']); ?></strong>
                            <span class="text-emerald small fw-semibold text-nowrap"><?php echo date('d M Y', strtotime($active_trip['return_date'])); ?> @ <?php echo date('h:i A', strtotime($active_trip['return_time'])); ?></span>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-2 flex-wrap flex-shrink-0">
                        <a href="<?php echo base_url('booking-details.php?id=' . $active_trip['id']); ?>" class="btn btn-sm btn-cyan rounded-pill px-3 py-1.5 fw-semibold text-nowrap">
                            <i class="fas fa-eye me-1"></i>Trip Details
                        </a>
                        <a href="<?php echo base_url('invoice.php?id=' . $active_trip['id']); ?>" target="_blank" class="btn btn-sm btn-outline-light rounded-pill px-3 py-1.5 fw-semibold text-nowrap">
                            <i class="fas fa-file-invoice me-1"></i>Invoice
                        </a>
                        <?php if (in_array($active_trip['booking_status'], ['pending', 'confirmed'])): ?>
                            <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3 py-1.5 text-nowrap fw-semibold" onclick="openCancelModal(<?php echo $active_trip['id']; ?>, '<?php echo htmlspecialchars($active_trip['booking_id'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($active_trip['brand'] . ' ' . $active_trip['model'], ENT_QUOTES); ?>')">
                                <i class="fas fa-xmark me-1"></i>Cancel
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- ==========================================
             MAIN CONTENT: FULL-WIDTH BOOKINGS TABLE + CONCIERGE DECK
        ========================================== -->
        <!-- Recent Bookings Ledger Table (Full Width - Zero Truncation) -->
        <div class="row g-4 mb-4">
            <div class="col-12">
                <div class="luxury-table-card">
                    <div class="d-flex align-items-center justify-content-between pb-3 mb-3 border-bottom border-slate-700 flex-wrap gap-2">
                        <div>
                            <h5 class="fw-bold mb-0 text-white d-flex align-items-center gap-2">
                                <i class="fas fa-list-check text-cyan"></i>
                                <span>Recent Vehicle Reservations</span>
                            </h5>
                            <span class="text-slate-400 micro-text">Manage your active, confirmed, and previous Surat drives</span>
                        </div>
                        <a href="<?php echo base_url('my-bookings.php'); ?>" class="btn btn-outline-cyan btn-sm rounded-pill px-3 py-1.5 fw-semibold">
                            View All Bookings <i class="fas fa-arrow-right ms-1"></i>
                        </a>
                    </div>

                    <?php if (empty($recent_bookings)): ?>
                        <div class="text-center py-5">
                            <div class="stat-icon-circle cyan mx-auto mb-3" style="width: 64px; height: 64px; font-size: 1.8rem;">
                                <i class="fas fa-car-side"></i>
                            </div>
                            <h5 class="fw-bold text-white mb-2">No Active Reservations</h5>
                            <p class="text-slate-400 small mb-4">Discover our premier collection of 60+ luxury sedans, sports coupes, and executive SUVs in Surat.</p>
                            <a href="<?php echo base_url('cars.php'); ?>" class="btn btn-gradient px-4 py-2.5 rounded-pill shadow-cyan fw-bold">
                                <i class="fas fa-compass me-2"></i>Explore Luxury Fleet
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table-luxury">
                                <thead>
                                    <tr>
                                        <th style="min-width: 260px;">Vehicle</th>
                                        <th style="min-width: 170px;">Reference ID</th>
                                        <th style="min-width: 200px;">Rental Period</th>
                                        <th style="min-width: 160px;">Tariff</th>
                                        <th style="min-width: 150px;">Status</th>
                                        <th class="text-end pe-3" style="min-width: 200px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_bookings as $b): ?>
                                        <tr>
                                            <!-- Vehicle info -->
                                            <td class="vehicle-cell">
                                                <div class="d-flex align-items-center gap-3">
                                                    <img src="<?php echo get_car_image_url($b['image']); ?>" alt="Car" class="rounded-2 border border-slate-700 flex-shrink-0" style="width: 62px; height: 42px; object-fit: cover;">
                                                    <div>
                                                        <strong class="text-white d-block mb-1" style="font-size: 0.95rem;"><?php echo htmlspecialchars($b['brand'] . ' ' . $b['model']); ?></strong>
                                                        <span class="text-slate-300 small"><?php echo htmlspecialchars($b['category']); ?> &bull; <?php echo $b['transmission']; ?></span>
                                                    </div>
                                                </div>
                                            </td>

                                            <!-- Booking ID -->
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="font-monospace text-accent fw-bold" style="font-size: 0.92rem;"><?php echo htmlspecialchars($b['booking_id']); ?></span>
                                                    <button type="button" class="btn-copy-code" onclick="copyBookingRef('<?php echo htmlspecialchars($b['booking_id']); ?>')" title="Copy Reference ID">
                                                        <i class="fas fa-copy"></i>
                                                    </button>
                                                </div>
                                                <div class="text-slate-400 small mt-1">
                                                    <i class="far fa-clock me-1 text-slate-500"></i><?php echo date('d M Y, h:i A', strtotime($b['created_at'])); ?>
                                                </div>
                                            </td>

                                            <!-- Dates -->
                                            <td>
                                                <div class="text-white fw-semibold mb-1" style="font-size: 0.90rem;">
                                                    <i class="fas fa-calendar-day text-cyan me-1.5"></i><?php echo date('d M Y', strtotime($b['pickup_date'])); ?> &rarr; <?php echo date('d M Y', strtotime($b['return_date'])); ?>
                                                </div>
                                                <div class="mt-1">
                                                    <span class="badge bg-slate-800 text-slate-300 border border-slate-700 px-2 py-0.5 rounded-pill small">
                                                        <i class="fas fa-clock text-amber me-1"></i><?php echo $b['rental_days']; ?> Day<?php echo $b['rental_days'] > 1 ? 's' : ''; ?>
                                                    </span>
                                                </div>
                                            </td>

                                            <!-- Amount -->
                                            <td>
                                                <div class="fw-bold text-emerald mb-1" style="font-size: 1.05rem;"><?php echo format_currency($b['total_amount']); ?></div>
                                                <div>
                                                    <span class="badge rounded-pill <?php echo strtolower($b['payment_status']) === 'paid' ? 'bg-emerald bg-opacity-20 text-emerald border-emerald' : 'bg-amber bg-opacity-20 text-amber border-amber'; ?> border border-opacity-30 small">
                                                        <i class="fas <?php echo strtolower($b['payment_status']) === 'paid' ? 'fa-check' : 'fa-clock'; ?> me-1"></i><?php echo strtoupper($b['payment_status']); ?>
                                                    </span>
                                                </div>
                                            </td>

                                            <!-- Status -->
                                            <td>
                                                <span class="glow-status-pill <?php echo strtolower($b['booking_status']); ?>">
                                                    <span class="glow-dot"></span>
                                                    <?php echo strtoupper($b['booking_status']); ?>
                                                </span>
                                            </td>

                                            <!-- Actions -->
                                            <td class="text-end pe-3">
                                                <div class="d-flex flex-column align-items-end">
                                                    <a href="<?php echo base_url('booking-details.php?id=' . $b['id']); ?>" class="btn btn-outline-cyan btn-sm rounded-pill px-3 py-1 mb-1.5 text-nowrap fw-semibold d-inline-flex align-items-center" style="font-size: 0.82rem;" title="View Details">
                                                        <i class="fas fa-eye me-1.5"></i>Trip Details
                                                    </a>
                                                    <div class="d-flex align-items-center gap-1.5 text-nowrap">
                                                        <a href="<?php echo base_url('invoice.php?id=' . $b['id']); ?>" target="_blank" class="btn btn-outline-light btn-sm rounded-pill px-2.5 py-1 text-nowrap" style="font-size: 0.78rem;" title="Official Invoice">
                                                            <i class="fas fa-file-invoice me-1"></i>Invoice
                                                        </a>
                                                        <?php if (in_array($b['booking_status'], ['pending', 'confirmed'])): ?>
                                                            <button type="button" class="btn btn-outline-danger btn-sm rounded-pill px-2.5 py-1 text-nowrap" style="font-size: 0.78rem;" title="Cancel Reservation" onclick="openCancelModal(<?php echo $b['id']; ?>, '<?php echo htmlspecialchars($b['booking_id'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($b['brand'] . ' ' . $b['model'], ENT_QUOTES); ?>')">
                                                                <i class="fas fa-xmark me-1"></i>Cancel
                                                            </button>
                                                        <?php else: ?>
                                                            <a href="<?php echo base_url('booking.php?car_id=' . $b['car_id']); ?>" class="btn btn-outline-primary btn-sm rounded-pill px-2.5 py-1 text-nowrap" style="font-size: 0.78rem;" title="Re-Book Vehicle">
                                                                <i class="fas fa-rotate-right me-1"></i>Re-Book
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- 2-Column Deck: Recent Alerts + VIP Account Concierge -->
        <div class="row g-4">
            <!-- Left: Notifications Card -->
            <div class="col-lg-6">
                <div class="luxury-table-card h-100">
                    <div class="d-flex align-items-center justify-content-between pb-3 mb-3 border-bottom border-slate-700">
                        <h6 class="fw-bold mb-0 text-white d-flex align-items-center gap-2">
                            <i class="fas fa-bell text-amber"></i>
                            <span>Recent Alerts & Activity</span>
                        </h6>
                        <a href="<?php echo base_url('notifications.php'); ?>" class="text-cyan small text-decoration-none fw-semibold">
                            View All <i class="fas fa-chevron-right micro-text"></i>
                        </a>
                    </div>

                    <?php if (empty($recent_notifs)): ?>
                        <div class="text-center py-4 text-slate-400 small">
                            <i class="fas fa-bell-slash fa-2x mb-2 opacity-40"></i>
                            <div>No unread notifications at this time.</div>
                        </div>
                    <?php else: ?>
                        <div class="d-flex flex-column gap-2.5">
                            <?php foreach ($recent_notifs as $n): ?>
                                <div class="p-3 rounded-3 border border-slate-700 d-flex align-items-start gap-2.5" style="background: rgba(15, 23, 42, 0.7);">
                                    <div class="stat-icon-circle cyan flex-shrink-0" style="width: 32px; height: 32px; font-size: 0.85rem; border-radius: 8px;">
                                        <i class="fas fa-info"></i>
                                    </div>
                                    <div class="flex-grow-1 overflow-hidden">
                                        <div class="fw-bold text-white small text-truncate"><?php echo htmlspecialchars($n['title']); ?></div>
                                        <p class="text-slate-400 micro-text mb-1" style="line-height: 1.35;"><?php echo htmlspecialchars($n['message']); ?></p>
                                        <span class="text-slate-500" style="font-size: 0.68rem;"><i class="fas fa-clock me-1"></i><?php echo date('d M, h:i A', strtotime($n['created_at'])); ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right: VIP Concierge Navigation Card -->
            <div class="col-lg-6">
                <div class="luxury-table-card h-100 d-flex flex-column justify-content-between">
                    <div>
                        <h6 class="fw-bold text-white mb-3 pb-2 border-bottom border-slate-700 d-flex align-items-center gap-2">
                            <i class="fas fa-compass text-cyan"></i>
                            <span>VIP Account Navigation</span>
                        </h6>

                        <div class="row g-2">
                            <div class="col-sm-6">
                                <a href="<?php echo base_url('cars.php'); ?>" class="p-2.5 rounded-3 border border-slate-800 d-flex align-items-center justify-content-between text-decoration-none hover-accent" style="background: rgba(15, 23, 42, 0.6);">
                                    <span class="small text-slate-300"><i class="fas fa-car-rear me-2 text-accent"></i>Browse 60+ Fleet</span>
                                    <i class="fas fa-chevron-right micro-text text-slate-500"></i>
                                </a>
                            </div>
                            <div class="col-sm-6">
                                <a href="<?php echo base_url('my-bookings.php'); ?>" class="p-2.5 rounded-3 border border-slate-800 d-flex align-items-center justify-content-between text-decoration-none hover-accent" style="background: rgba(15, 23, 42, 0.6);">
                                    <span class="small text-slate-300"><i class="fas fa-calendar-check me-2 text-emerald"></i>Trip History</span>
                                    <i class="fas fa-chevron-right micro-text text-slate-500"></i>
                                </a>
                            </div>
                            <div class="col-sm-6">
                                <a href="<?php echo base_url('saved-cars.php'); ?>" class="p-2.5 rounded-3 border border-slate-800 d-flex align-items-center justify-content-between text-decoration-none hover-accent" style="background: rgba(15, 23, 42, 0.6);">
                                    <span class="small text-slate-300"><i class="fas fa-heart me-2 text-amber"></i>Saved Cars (<?php echo $saved_count; ?>)</span>
                                    <i class="fas fa-chevron-right micro-text text-slate-500"></i>
                                </a>
                            </div>
                            <div class="col-sm-6">
                                <a href="<?php echo base_url('profile.php'); ?>" class="p-2.5 rounded-3 border border-slate-800 d-flex align-items-center justify-content-between text-decoration-none hover-accent" style="background: rgba(15, 23, 42, 0.6);">
                                    <span class="small text-slate-300"><i class="fas fa-id-card me-2 text-cyan"></i>License & KYC</span>
                                    <i class="fas fa-chevron-right micro-text text-slate-500"></i>
                                </a>
                            </div>
                            <div class="col-sm-6">
                                <a href="<?php echo base_url('contact.php'); ?>" class="p-2.5 rounded-3 border border-slate-800 d-flex align-items-center justify-content-between text-decoration-none hover-accent" style="background: rgba(15, 23, 42, 0.6);">
                                    <span class="small text-slate-300"><i class="fas fa-headset me-2 text-info"></i>24/7 Concierge</span>
                                    <i class="fas fa-chevron-right micro-text text-slate-500"></i>
                                </a>
                            </div>
                            <div class="col-sm-6">
                                <a href="<?php echo base_url('logout.php'); ?>" class="p-2.5 rounded-3 border border-slate-800 d-flex align-items-center justify-content-between text-decoration-none hover-accent text-rose" style="background: rgba(15, 23, 42, 0.6);">
                                    <span class="small"><i class="fas fa-right-from-bracket me-2"></i>Sign Out</span>
                                    <i class="fas fa-chevron-right micro-text"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- 24/7 Roadside Assistance Hotline Box -->
                    <div class="p-3 rounded-3 mt-3 border border-slate-700 d-flex align-items-center gap-3" style="background: rgba(15, 23, 42, 0.9);">
                        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 40px; height: 40px; background: rgba(245, 158, 11, 0.15); color: #FBBF24;">
                            <i class="fas fa-phone-volume"></i>
                        </div>
                        <div>
                            <div class="fw-bold text-white small">Surat 24/7 Roadside Support</div>
                            <div class="text-amber font-monospace fw-bold" style="font-size: 0.85rem;">+91 98765 43210</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ==========================================
     GLOBAL CANCEL RESERVATION MODAL
========================================== -->
<div class="modal fade" id="dashboardCancelModal" tabindex="-1" aria-labelledby="dashCancelLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content text-bg-dark border-secondary shadow-lg" style="background: #0F172A; border-radius: 20px; border: 1px solid rgba(255,255,255,0.12);">
            <div class="modal-header border-slate-700 py-3 px-4">
                <h5 class="modal-title fw-bold text-white d-flex align-items-center gap-2" id="dashCancelLabel">
                    <i class="fas fa-triangle-exclamation text-amber"></i>
                    <span>Cancel Reservation</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?php echo base_url('dashboard.php'); ?>" method="POST" id="dashCancelForm">
                <div class="modal-body p-4">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="action" value="cancel_booking">
                    <input type="hidden" name="booking_id" id="dash_cancel_booking_id" value="">

                    <div class="alert alert-warning border-0 small mb-3" style="background: rgba(245, 158, 11, 0.15); color: #FDE047; border: 1px solid rgba(245, 158, 11, 0.3) !important;">
                        <i class="fas fa-info-circle me-1"></i> Are you sure you wish to cancel this reservation? Full refund will be automatically initiated to your original payment method.
                    </div>

                    <div class="p-3 rounded-3 border border-slate-700 mb-3" style="background: #1E293B;">
                        <div class="small text-slate-400 mb-1">Vehicle:</div>
                        <h6 class="fw-bold text-white mb-2" id="dash_cancel_car_name">-</h6>
                        <div class="small text-slate-400">Reference ID:</div>
                        <span class="font-monospace text-cyan fw-bold" id="dash_cancel_booking_code">-</span>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-slate-300 small fw-semibold">Reason for Cancellation</label>
                        <select name="cancel_reason" class="form-select bg-dark text-white border-slate-700">
                            <option value="Change of travel plans">Change of travel plans</option>
                            <option value="Duplicate booking by mistake">Duplicate booking by mistake</option>
                            <option value="Booked another vehicle">Booked another vehicle</option>
                            <option value="Emergency requirement">Emergency requirement</option>
                            <option value="Other reason">Other reason</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-slate-700 py-3 px-4 d-flex justify-content-between">
                    <button type="button" class="btn btn-secondary btn-sm rounded-pill px-3.5" data-bs-dismiss="modal">Keep Reservation</button>
                    <button type="submit" class="btn btn-danger btn-sm rounded-pill px-4 fw-semibold">
                        <i class="fas fa-xmark me-1"></i>Confirm Cancellation
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Quick Toast for Copying Reference ID -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1090;">
    <div id="copyToast" class="toast align-items-center text-bg-dark border border-cyan" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body small fw-semibold text-cyan">
                <i class="fas fa-check-circle me-1.5"></i> Reference ID copied to clipboard!
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

<script>
function openCancelModal(bookingId, bookingCode, carName) {
    var idInput = document.getElementById('dash_cancel_booking_id');
    var codeSpan = document.getElementById('dash_cancel_booking_code');
    var nameSpan = document.getElementById('dash_cancel_car_name');

    if (idInput) idInput.value = bookingId;
    if (codeSpan) codeSpan.innerText = bookingCode;
    if (nameSpan) nameSpan.innerText = carName;

    var modalElem = document.getElementById('dashboardCancelModal');
    if (modalElem && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        var bsModal = bootstrap.Modal.getInstance(modalElem) || new bootstrap.Modal(modalElem);
        bsModal.show();
    } else {
        if (confirm('Cancel reservation ' + bookingCode + ' (' + carName + ')?')) {
            document.getElementById('dashCancelForm').submit();
        }
    }
}

function copyBookingRef(ref) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(ref).then(function() {
            showCopyToast();
        });
    } else {
        var tempInput = document.createElement('input');
        tempInput.value = ref;
        document.body.appendChild(tempInput);
        tempInput.select();
        document.execCommand('copy');
        document.body.removeChild(tempInput);
        showCopyToast();
    }
}

function showCopyToast() {
    var toastElem = document.getElementById('copyToast');
    if (toastElem && typeof bootstrap !== 'undefined' && bootstrap.Toast) {
        var toast = new bootstrap.Toast(toastElem, { delay: 2500 });
        toast.show();
    }
}
</script>

<?php include 'includes/footer.php'; ?>
