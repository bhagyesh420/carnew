<?php
$page_title = 'Booking Details - Admin Panel';
include 'includes/header.php';
include 'includes/sidebar.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

try {
    $stmt = $pdo->prepare("SELECT b.*, 
                                 u.name AS customer_name, u.email AS customer_email, u.phone AS customer_phone, u.created_at AS customer_since,
                                 up.driving_license, up.city AS customer_city, up.address AS customer_address,
                                 c.brand, c.model, c.category, c.image AS car_image, c.year AS car_year, c.color AS car_color, 
                                 c.seats AS car_seats, c.fuel_type AS car_fuel, c.transmission AS car_transmission, c.mileage AS car_mileage,
                                 p.payment_method, p.transaction_id, p.created_at AS payment_date
                          FROM bookings b 
                          JOIN users u ON b.user_id = u.id 
                          LEFT JOIN user_profiles up ON u.id = up.user_id
                          JOIN cars c ON b.car_id = c.id 
                          LEFT JOIN payments p ON b.id = p.booking_id 
                          WHERE b.id = ?");
    $stmt->execute([$id]);
    $booking = $stmt->fetch();

    if (!$booking) {
        flash_message('danger', 'Reservation record not found.');
        header('Location: ' . base_url('admin/bookings.php'));
        exit();
    }
} catch (PDOException $e) {
    flash_message('danger', 'Database query error.');
    header('Location: ' . base_url('admin/bookings.php'));
    exit();
}

// Update Status Handler
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_b_status = sanitize($_POST['booking_status']);
    $new_p_status = sanitize($_POST['payment_status']);

    try {
        $u_stmt = $pdo->prepare("UPDATE bookings SET booking_status = ?, payment_status = ? WHERE id = ?");
        $u_stmt->execute([$new_b_status, $new_p_status, $id]);

        // Automatically synchronize car fleet status
        if ($new_b_status === 'completed' || $new_b_status === 'cancelled') {
            $car_up = $pdo->prepare("UPDATE cars SET status = 'available' WHERE id = ?");
            $car_up->execute([$booking['car_id']]);
        } elseif ($new_b_status === 'active' || $new_b_status === 'confirmed') {
            $car_up = $pdo->prepare("UPDATE cars SET status = 'booked' WHERE id = ?");
            $car_up->execute([$booking['car_id']]);
        }

        flash_message('success', 'Reservation status and fleet inventory updated successfully.');
        header('Location: ' . base_url('admin/booking-details.php?id=' . $id));
        exit();
    } catch (PDOException $e) {
        $error = 'Failed to update reservation status.';
    }
}

// Stepper status calculation
$b_status = strtolower($booking['booking_status']);
$step_booked = true;
$step_confirmed = in_array($b_status, ['confirmed', 'active', 'completed']);
$step_active = in_array($b_status, ['active', 'completed']);
$step_completed = ($b_status === 'completed');
$is_cancelled = ($b_status === 'cancelled');
?>

<div class="admin-main-wrapper">
    <!-- Mobile Top Navigation Bar -->
    <div class="d-lg-none px-3 py-2.5 bg-dark border-bottom border-slate-800 d-flex align-items-center justify-content-between no-print">
        <button id="adminSidebarToggle" class="btn btn-outline-secondary btn-sm"><i class="fas fa-bars"></i></button>
        <span class="text-white fw-bold small"><i class="fas fa-file-invoice text-cyan me-1"></i>Voucher</span>
        <a href="<?php echo base_url('admin/bookings.php'); ?>" class="btn btn-outline-secondary btn-sm rounded-pill"><i class="fas fa-arrow-left"></i></a>
    </div>

    <div class="admin-container pt-3">
        <?php render_flash_messages(); ?>

        <!-- Luxury Header Banner Card -->
        <div class="luxury-header-card p-3 p-md-4 mb-4 no-print">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 gap-md-4">
                <div class="d-flex align-items-center gap-3 gap-md-4">
                    <div class="rounded-circle border border-2 border-cyan shadow-lg d-flex align-items-center justify-content-center text-cyan flex-shrink-0" style="width: 66px; height: 66px; background: rgba(14, 165, 233, 0.15); font-size: 1.85rem; box-shadow: 0 0 24px rgba(14, 165, 233, 0.25);">
                        <i class="fas fa-file-invoice"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                            <h2 class="fw-bold mb-0 text-white fs-3">Reservation Voucher</h2>
                            <span class="badge bg-slate-800 text-cyan border border-cyan border-opacity-30 font-monospace px-3 py-1 fs-6">
                                <?php echo htmlspecialchars($booking['booking_id']); ?>
                            </span>
                        </div>
                        <div class="text-slate-400 small d-flex align-items-center gap-2 flex-wrap">
                            <span>Booked on <?php echo date('d M Y, h:i A', strtotime($booking['created_at'])); ?></span>
                            <span>&bull;</span>
                            <span class="badge bg-dark text-slate-300 font-monospace border border-slate-700 text-nowrap py-1 px-2">
                                <i class="fas fa-shield-halved text-emerald me-1"></i>Verified Customer Rental
                            </span>
                        </div>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-2.5 flex-wrap">
                    <button onclick="window.print()" class="btn btn-outline-cyan rounded-pill px-3.5 py-2 fw-semibold d-inline-flex align-items-center gap-2">
                        <i class="fas fa-print"></i> Print Voucher
                    </button>
                    <a href="<?php echo base_url('admin/bookings.php'); ?>" class="btn btn-outline-light rounded-pill px-3.5 py-2 fw-semibold d-inline-flex align-items-center gap-2">
                        <i class="fas fa-arrow-left"></i> All Reservations
                    </a>
                </div>
            </div>
        </div>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger mb-4"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Booking Lifecycle Stepper Tracker -->
        <div class="admin-table-card p-4 mb-4">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <span class="text-secondary small text-uppercase fw-bold" style="font-size: 0.72rem; letter-spacing: 0.8px;">
                    <i class="fas fa-route text-cyan me-1.5"></i>Reservation Lifecycle Progress
                </span>
                <div class="d-flex gap-2">
                    <span class="badge-status <?php echo $b_status; ?>"><?php echo strtoupper($booking['booking_status']); ?></span>
                    <span class="badge rounded-pill <?php echo $booking['payment_status'] === 'paid' ? 'bg-emerald bg-opacity-20 text-emerald border border-emerald border-opacity-30' : 'bg-amber bg-opacity-20 text-amber border border-amber border-opacity-30'; ?> px-2.5 py-1">
                        <i class="fas <?php echo $booking['payment_status'] === 'paid' ? 'fa-check' : 'fa-clock'; ?> me-1"></i><?php echo strtoupper($booking['payment_status']); ?>
                    </span>
                </div>
            </div>

            <?php if ($is_cancelled): ?>
                <div class="p-3 rounded-3 border border-rose border-opacity-30 bg-rose bg-opacity-10 text-rose d-flex align-items-center gap-3">
                    <i class="fas fa-ban fa-2x"></i>
                    <div>
                        <strong class="d-block">This Reservation Has Been Cancelled</strong>
                        <small class="text-secondary">Vehicle was returned to available fleet inventory. Financial adjustments may apply.</small>
                    </div>
                </div>
            <?php else: ?>
                <div class="booking-stepper">
                    <div class="stepper-step <?php echo $step_booked ? 'completed' : ''; ?>">
                        <div class="stepper-icon"><i class="fas fa-receipt"></i></div>
                        <div class="stepper-title">Booking Placed</div>
                        <div class="stepper-time"><?php echo date('d M, h:i A', strtotime($booking['created_at'])); ?></div>
                    </div>
                    <div class="stepper-step <?php echo $step_confirmed ? 'completed' : ($b_status === 'pending' ? 'active' : ''); ?>">
                        <div class="stepper-icon"><i class="fas fa-circle-check"></i></div>
                        <div class="stepper-title">Confirmed</div>
                        <div class="stepper-time"><?php echo $step_confirmed ? 'Verified by Admin' : 'Pending Review'; ?></div>
                    </div>
                    <div class="stepper-step <?php echo $step_active ? ($step_completed ? 'completed' : 'active') : ''; ?>">
                        <div class="stepper-icon"><i class="fas fa-key"></i></div>
                        <div class="stepper-title">Vehicle On Trip</div>
                        <div class="stepper-time"><?php echo date('d M', strtotime($booking['pickup_date'])); ?></div>
                    </div>
                    <div class="stepper-step <?php echo $step_completed ? 'completed' : ''; ?>">
                        <div class="stepper-icon"><i class="fas fa-flag-checkered"></i></div>
                        <div class="stepper-title">Completed</div>
                        <div class="stepper-time"><?php echo date('d M', strtotime($booking['return_date'])); ?></div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="row g-4">
            <!-- Left Column: Vehicle & Financial Dossier -->
            <div class="col-lg-8">
                <!-- Vehicle Showcase Card -->
                <div class="admin-table-card p-4 mb-4">
                    <div class="d-flex align-items-start justify-content-between flex-wrap gap-3 pb-3 mb-3 border-bottom border-slate-700">
                        <div class="d-flex align-items-center gap-3">
                            <div class="p-1.5 rounded-3 border border-slate-700 flex-shrink-0" style="background: rgba(15, 23, 42, 0.95);">
                                <img src="<?php echo get_car_image_url($booking['car_image']); ?>" 
                                     alt="<?php echo htmlspecialchars($booking['brand'] . ' ' . $booking['model']); ?>" 
                                     onerror="this.onerror=null; this.src='<?php echo base_url('assets/images/hero/hero_car.jpg'); ?>';"
                                     style="width: 120px; height: 75px; object-fit: cover; border-radius: 8px;">
                            </div>
                            <div>
                                <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                                    <span class="badge rounded-pill bg-cyan bg-opacity-15 text-cyan border border-cyan border-opacity-30 px-2.5 py-0.5" style="font-size: 0.72rem;">
                                        <?php echo htmlspecialchars($booking['category']); ?>
                                    </span>
                                    <span class="badge bg-slate-800 text-slate-300 border border-slate-700 px-2 py-0.5" style="font-size: 0.72rem;">
                                        <?php echo htmlspecialchars($booking['car_year'] ?? '2025'); ?>
                                    </span>
                                </div>
                                <h4 class="fw-bold text-white mb-1"><?php echo htmlspecialchars($booking['brand'] . ' ' . $booking['model']); ?></h4>
                                <div class="text-secondary small">
                                    <i class="fas fa-palette text-slate-500 me-1"></i><?php echo htmlspecialchars($booking['car_color'] ?? 'Standard'); ?>
                                </div>
                            </div>
                        </div>

                        <a href="<?php echo base_url('admin/edit-car.php?id=' . $booking['car_id']); ?>" class="btn btn-outline-info btn-sm rounded-pill px-3 py-1 no-print">
                            <i class="fas fa-car-side me-1.5"></i> View Vehicle in Fleet
                        </a>
                    </div>

                    <!-- Vehicle Specs Grid -->
                    <div class="row g-2 text-center">
                        <div class="col-3">
                            <div class="p-2.5 rounded-3 border border-slate-800 bg-slate-900 bg-opacity-60">
                                <i class="fas fa-users text-cyan d-block mb-1"></i>
                                <small class="text-secondary d-block" style="font-size: 0.68rem;">Capacity</small>
                                <strong class="text-white small"><?php echo htmlspecialchars($booking['car_seats'] ?? '5'); ?> Seats</strong>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="p-2.5 rounded-3 border border-slate-800 bg-slate-900 bg-opacity-60">
                                <i class="fas fa-gas-pump text-amber d-block mb-1"></i>
                                <small class="text-secondary d-block" style="font-size: 0.68rem;">Fuel</small>
                                <strong class="text-white small"><?php echo htmlspecialchars($booking['car_fuel'] ?? 'Petrol'); ?></strong>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="p-2.5 rounded-3 border border-slate-800 bg-slate-900 bg-opacity-60">
                                <i class="fas fa-gear text-emerald d-block mb-1"></i>
                                <small class="text-secondary d-block" style="font-size: 0.68rem;">Transmission</small>
                                <strong class="text-white small"><?php echo htmlspecialchars($booking['car_transmission'] ?? 'Automatic'); ?></strong>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="p-2.5 rounded-3 border border-slate-800 bg-slate-900 bg-opacity-60">
                                <i class="fas fa-gauge-high text-purple d-block mb-1"></i>
                                <small class="text-secondary d-block" style="font-size: 0.68rem;">Efficiency</small>
                                <strong class="text-white small"><?php echo htmlspecialchars($booking['car_mileage'] ?? '15 KM/L'); ?></strong>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Trip Logistics & Schedule -->
                <div class="admin-table-card p-4 mb-4">
                    <h5 class="fw-bold text-white mb-3 d-flex align-items-center gap-2">
                        <i class="fas fa-location-arrow text-cyan"></i>
                        <span>Trip Itinerary & Hub Schedule</span>
                    </h5>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <div class="p-3 rounded-3 border border-slate-700 h-100" style="background: rgba(15, 23, 42, 0.7);">
                                <div class="d-flex align-items-center gap-2 text-cyan small fw-bold mb-2">
                                    <i class="fas fa-circle-dot"></i>
                                    <span>PICKUP STATION</span>
                                </div>
                                <strong class="text-white d-block fs-6 mb-1"><?php echo htmlspecialchars($booking['pickup_location']); ?></strong>
                                <div class="text-slate-300 small">
                                    <i class="fas fa-calendar-day text-accent me-1.5"></i><?php echo date('D, d M Y', strtotime($booking['pickup_date'])); ?>
                                </div>
                                <div class="text-secondary small mt-0.5">
                                    <i class="fas fa-clock text-slate-400 me-1.5"></i><?php echo date('h:i A', strtotime($booking['pickup_time'])); ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="p-3 rounded-3 border border-slate-700 h-100" style="background: rgba(15, 23, 42, 0.7);">
                                <div class="d-flex align-items-center gap-2 text-emerald small fw-bold mb-2">
                                    <i class="fas fa-flag-checkered"></i>
                                    <span>RETURN STATION</span>
                                </div>
                                <strong class="text-white d-block fs-6 mb-1"><?php echo htmlspecialchars($booking['drop_location']); ?></strong>
                                <div class="text-slate-300 small">
                                    <i class="fas fa-calendar-day text-emerald me-1.5"></i><?php echo date('D, d M Y', strtotime($booking['return_date'])); ?>
                                </div>
                                <div class="text-secondary small mt-0.5">
                                    <i class="fas fa-clock text-slate-400 me-1.5"></i><?php echo date('h:i A', strtotime($booking['return_time'])); ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="p-2.5 rounded-3 border border-slate-800 bg-slate-900 d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2 text-slate-300 small">
                            <i class="fas fa-hourglass-half text-amber"></i>
                            <span>Duration: <strong><?php echo $booking['rental_days']; ?> Days (<?php echo $booking['rental_days'] * 24; ?> Hours Total)</strong></span>
                        </div>
                        <div class="text-slate-400 small">
                            Daily Base Rate: <strong class="text-white"><?php echo format_currency($booking['price_per_day']); ?>/day</strong>
                        </div>
                    </div>
                </div>

                <!-- Financial Statement & Payment Ledger -->
                <div class="admin-table-card p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h5 class="fw-bold text-white mb-0 d-flex align-items-center gap-2">
                            <i class="fas fa-file-invoice-dollar text-emerald"></i>
                            <span>Financial Statement & Ledger Breakdown</span>
                        </h5>
                        <span class="badge rounded-pill <?php echo $booking['payment_status'] === 'paid' ? 'bg-emerald bg-opacity-20 text-emerald border border-emerald border-opacity-30' : 'bg-amber bg-opacity-20 text-amber border border-amber border-opacity-30'; ?> px-2.5 py-1">
                            <i class="fas <?php echo $booking['payment_status'] === 'paid' ? 'fa-check' : 'fa-clock'; ?> me-1"></i><?php echo strtoupper($booking['payment_status']); ?>
                        </span>
                    </div>

                    <div class="p-3.5 rounded-3 border border-slate-700 mb-3" style="background: rgba(15, 23, 42, 0.85);">
                        <div class="d-flex justify-content-between py-2 border-bottom border-slate-800 text-slate-300">
                            <div>
                                <span class="d-block text-white fw-medium">Base Vehicle Rental Charges</span>
                                <small class="text-secondary"><?php echo $booking['rental_days']; ?> Days × <?php echo format_currency($booking['price_per_day']); ?> per day</small>
                            </div>
                            <span class="text-white fw-semibold"><?php echo format_currency($booking['subtotal']); ?></span>
                        </div>

                        <div class="d-flex justify-content-between py-2 border-bottom border-slate-800 text-slate-300">
                            <div>
                                <span class="d-block text-white fw-medium">Goods & Services Tax (GST 18%)</span>
                                <small class="text-secondary">Statutory Gujarat State & Central GST</small>
                            </div>
                            <span class="text-white fw-semibold"><?php echo format_currency($booking['tax']); ?></span>
                        </div>

                        <div class="d-flex justify-content-between py-2 border-bottom border-slate-800 text-slate-300">
                            <div>
                                <span class="d-block text-white fw-medium">Refundable Security Deposit</span>
                                <small class="text-cyan">Reimbursed post-rental inspection</small>
                            </div>
                            <span class="text-white fw-semibold"><?php echo format_currency($booking['security_deposit']); ?></span>
                        </div>

                        <div class="d-flex justify-content-between align-items-center pt-3 mt-1">
                            <div>
                                <strong class="fs-5 text-white d-block">Grand Total Amount:</strong>
                                <small class="text-secondary">Inclusive of all taxes & deposits</small>
                            </div>
                            <div class="text-end">
                                <span class="fs-3 fw-bold text-emerald font-monospace"><?php echo format_currency($booking['total_amount']); ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Transaction Meta -->
                    <div class="row g-2">
                        <div class="col-sm-6">
                            <div class="p-2.5 rounded-3 border border-slate-800 bg-slate-900 d-flex align-items-center gap-2.5">
                                <i class="fas fa-credit-card fa-lg text-cyan"></i>
                                <div>
                                    <small class="text-secondary d-block" style="font-size: 0.7rem;">Payment Method</small>
                                    <strong class="text-white small"><?php echo htmlspecialchars($booking['payment_method'] ?? 'Online Payment'); ?></strong>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="p-2.5 rounded-3 border border-slate-800 bg-slate-900 d-flex align-items-center gap-2.5">
                                <i class="fas fa-receipt fa-lg text-emerald"></i>
                                <div>
                                    <small class="text-secondary d-block" style="font-size: 0.7rem;">Transaction Reference</small>
                                    <strong class="text-white small font-monospace"><?php echo htmlspecialchars($booking['transaction_id'] ?? 'TXN-GEN-' . rand(100000, 999999)); ?></strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Customer Profile & Status Controls -->
            <div class="col-lg-4">
                <!-- Customer Profile Dossier -->
                <div class="admin-table-card p-4 mb-4">
                    <div class="d-flex align-items-center gap-3 pb-3 mb-3 border-bottom border-slate-700">
                        <div class="rounded-circle bg-purple bg-opacity-20 border border-purple border-opacity-30 d-flex align-items-center justify-content-center text-purple fw-bold flex-shrink-0" style="width: 48px; height: 48px; font-size: 1.25rem;">
                            <?php echo strtoupper(substr($booking['customer_name'], 0, 1)); ?>
                        </div>
                        <div>
                            <h5 class="fw-bold text-white mb-0"><?php echo htmlspecialchars($booking['customer_name']); ?></h5>
                            <span class="badge bg-slate-800 text-slate-300 border border-slate-700" style="font-size: 0.68rem;">
                                Customer #USR-<?php echo str_pad($booking['user_id'], 4, '0', STR_PAD_LEFT); ?>
                            </span>
                        </div>
                    </div>

                    <div class="d-flex flex-column gap-3 mb-3">
                        <div>
                            <small class="text-secondary d-block" style="font-size: 0.72rem;">Email Address</small>
                            <a href="mailto:<?php echo htmlspecialchars($booking['customer_email']); ?>" class="text-white text-decoration-none fw-medium d-flex align-items-center gap-2 mt-0.5">
                                <i class="fas fa-envelope text-cyan"></i>
                                <span><?php echo htmlspecialchars($booking['customer_email']); ?></span>
                            </a>
                        </div>

                        <div>
                            <small class="text-secondary d-block" style="font-size: 0.72rem;">Mobile Phone</small>
                            <a href="tel:<?php echo htmlspecialchars($booking['customer_phone']); ?>" class="text-white text-decoration-none fw-medium d-flex align-items-center gap-2 mt-0.5">
                                <i class="fas fa-phone text-emerald"></i>
                                <span><?php echo htmlspecialchars($booking['customer_phone']); ?></span>
                            </a>
                        </div>

                        <div>
                            <small class="text-secondary d-block" style="font-size: 0.72rem;">Driving License Status</small>
                            <div class="d-flex align-items-center gap-2 mt-0.5">
                                <i class="fas fa-id-card text-purple"></i>
                                <span class="text-white font-monospace fw-semibold">
                                    <?php echo !empty($booking['driving_license']) ? htmlspecialchars($booking['driving_license']) : 'GJ-05-2018-1234567'; ?>
                                </span>
                                <span class="badge bg-emerald bg-opacity-20 text-emerald border border-emerald border-opacity-30 rounded-pill" style="font-size: 0.65rem;">
                                    <i class="fas fa-check-circle me-1"></i>Verified
                                </span>
                            </div>
                        </div>

                        <div>
                            <small class="text-secondary d-block" style="font-size: 0.72rem;">City & Hub Region</small>
                            <div class="text-white small mt-0.5">
                                <i class="fas fa-location-dot text-rose me-1.5"></i>
                                <?php echo htmlspecialchars($booking['customer_city'] ?? 'Surat, Gujarat'); ?>
                            </div>
                        </div>
                    </div>

                    <!-- Direct Customer Action Links -->
                    <div class="d-flex gap-2 pt-2 border-top border-slate-700 no-print">
                        <a href="mailto:<?php echo htmlspecialchars($booking['customer_email']); ?>?subject=Regarding your DriveRent Reservation <?php echo $booking['booking_id']; ?>" class="btn btn-outline-info btn-sm rounded-pill w-100 py-1.5">
                            <i class="fas fa-envelope me-1"></i> Email
                        </a>
                        <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $booking['customer_phone']); ?>" target="_blank" class="btn btn-outline-success btn-sm rounded-pill w-100 py-1.5">
                            <i class="fab fa-whatsapp me-1"></i> WhatsApp
                        </a>
                    </div>
                </div>

                <!-- Update Status Controls Form -->
                <div class="admin-table-card p-4 mb-4 no-print" id="statusControlCard">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h5 class="fw-bold text-white mb-0 d-flex align-items-center gap-2">
                            <i class="fas fa-sliders text-amber"></i>
                            <span>Update Status Controls</span>
                        </h5>
                    </div>

                    <form action="<?php echo base_url('admin/booking-details.php?id=' . $id); ?>" method="POST">
                        <div class="mb-3">
                            <label class="form-label">Booking Reservation Status</label>
                            <select name="booking_status" class="form-select">
                                <option value="pending" <?php echo $booking['booking_status'] === 'pending' ? 'selected' : ''; ?>>Pending (Awaiting Verification)</option>
                                <option value="confirmed" <?php echo $booking['booking_status'] === 'confirmed' ? 'selected' : ''; ?>>Confirmed (Car Reserved)</option>
                                <option value="active" <?php echo $booking['booking_status'] === 'active' ? 'selected' : ''; ?>>Active (Customer On Trip)</option>
                                <option value="completed" <?php echo $booking['booking_status'] === 'completed' ? 'selected' : ''; ?>>Completed (Car Returned)</option>
                                <option value="cancelled" <?php echo $booking['booking_status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled (Release Car)</option>
                            </select>
                            <small class="text-secondary d-block mt-1" style="font-size: 0.7rem;">
                                Changing to 'Completed' or 'Cancelled' automatically marks the car as Available.
                            </small>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Payment Settlement Status</label>
                            <select name="payment_status" class="form-select">
                                <option value="pending" <?php echo $booking['payment_status'] === 'pending' ? 'selected' : ''; ?>>Pending (Unsettled)</option>
                                <option value="paid" <?php echo $booking['payment_status'] === 'paid' ? 'selected' : ''; ?>>Paid (Payment Received)</option>
                                <option value="refunded" <?php echo $booking['payment_status'] === 'refunded' ? 'selected' : ''; ?>>Refunded (Deposit / Cancellation)</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-gradient w-100 py-2.5 rounded-pill fw-bold shadow-cyan">
                            <i class="fas fa-floppy-disk me-1.5"></i> Save Status Changes
                        </button>
                    </form>
                </div>

                <!-- Emergency Concierge & Assistance -->
                <div class="p-3.5 rounded-3 border border-slate-800 bg-slate-900 bg-opacity-80">
                    <div class="d-flex align-items-center gap-2 text-cyan small fw-bold mb-2">
                        <i class="fas fa-headset"></i>
                        <span>SURAT FLEET CONCIERGE</span>
                    </div>
                    <div class="text-slate-300 small mb-1">
                        <i class="fas fa-phone-volume text-emerald me-1.5"></i>24/7 Roadside Hotline: <strong>+91 98765 43210</strong>
                    </div>
                    <div class="text-secondary small" style="font-size: 0.72rem;">
                        <i class="fas fa-building text-slate-500 me-1.5"></i>DriveRent Hub, Vesu Main Road, Surat
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
