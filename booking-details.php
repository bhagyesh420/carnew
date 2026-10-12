<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth-check.php';

$user = get_logged_user($pdo);
$booking_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $pdo->prepare("SELECT b.*, c.brand, c.model, c.category, c.year, c.image, c.transmission, c.fuel_type, c.seats, c.color 
    FROM bookings b 
    JOIN cars c ON b.car_id = c.id 
    WHERE b.id = ? AND (b.user_id = ? OR ? = 'admin')");
$stmt->execute([$booking_id, $user['id'], $user['role']]);
$booking = $stmt->fetch();

if (!$booking) {
    flash_message('danger', 'Reservation not found or access restricted.');
    header('Location: ' . base_url('my-bookings.php'));
    exit();
}

$user_profile = get_user_profile($pdo, $user['id']);

$page_title = 'Booking Details ' . $booking['booking_id'] . ' - DriveRent';
include 'includes/header.php';
include 'includes/navbar.php';
?>

<section class="bg-secondary-dark border-bottom border-dark-subtle page-header-section">
    <div class="container-fluid px-3 px-md-4 px-xl-5" style="max-width: 1600px;">
        <div class="luxury-header-card p-3 p-md-4 p-xl-4">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                <div>
                    <span class="text-accent micro-text fw-bold text-uppercase d-block mb-1">Reservation Reference</span>
                    <h2 class="fw-bold text-white mb-1 fs-3"><?php echo htmlspecialchars($booking['booking_id']); ?></h2>
                    <p class="text-slate-400 mb-0 small">Booked on <?php echo date('d M Y, h:i A', strtotime($booking['created_at'])); ?></p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="<?php echo base_url('invoice.php?id=' . $booking['id']); ?>" target="_blank" class="btn btn-gradient px-4 py-2 btn-pill shadow-cyan fw-bold text-nowrap"><i class="fas fa-file-invoice me-2"></i>Download Invoice</a>
                    <a href="<?php echo base_url('my-bookings.php'); ?>" class="btn btn-outline-light px-3 py-2 btn-pill fw-semibold text-nowrap"><i class="fas fa-arrow-left me-1"></i>Back</a>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-4 bg-primary min-vh-100">
    <div class="container-fluid px-3 px-md-4 px-xl-5" style="max-width: 1600px;">
        <?php render_flash_messages(); ?>

        <div class="row g-4">
            <!-- Left Column: Vehicle & Itinerary -->
            <div class="col-lg-8">
                <!-- Vehicle Overview Card -->
                <div class="glass-card p-4 mb-4">
                    <h5 class="fw-bold text-light mb-3 pb-2 border-bottom border-dark-subtle"><i class="fas fa-car-side text-accent me-2"></i>Reserved Vehicle Overview</h5>

                    <div class="d-flex flex-column flex-md-row gap-4 align-items-center">
                        <img src="<?php echo get_car_image_url($booking['image']); ?>" alt="Car" class="rounded border border-secondary img-fluid" style="max-width: 240px; height: 160px; object-fit: cover;">

                        <div class="flex-grow-1">
                            <span class="badge bg-accent text-light mb-2"><?php echo htmlspecialchars($booking['category']); ?></span>
                            <h3 class="fw-bold text-light mb-2"><?php echo htmlspecialchars($booking['brand'] . ' ' . $booking['model']); ?> (<?php echo $booking['year']; ?>)</h3>

                            <div class="row g-2 text-secondary small py-2 border-top border-dark-subtle">
                                <div class="col-6"><i class="fas fa-cog text-accent me-1"></i><?php echo $booking['transmission']; ?></div>
                                <div class="col-6"><i class="fas fa-gas-pump text-accent me-1"></i><?php echo $booking['fuel_type']; ?></div>
                                <div class="col-6"><i class="fas fa-users text-accent me-1"></i><?php echo $booking['seats']; ?> Executive Seats</div>
                                <div class="col-6"><i class="fas fa-palette text-accent me-1"></i><?php echo $booking['color']; ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Itinerary Details Card -->
                <div class="glass-card p-4">
                    <h5 class="fw-bold text-light mb-3 pb-2 border-bottom border-dark-subtle"><i class="fas fa-route text-accent me-2"></i>Rental Itinerary & Location Schedule</h5>

                    <div class="row g-4">
                        <div class="col-md-6 border-end-md border-dark-subtle">
                            <div class="p-3 bg-secondary-dark rounded border border-secondary">
                                <span class="text-accent micro-text text-uppercase fw-bold d-block mb-1"><i class="fas fa-location-dot me-1"></i>Pickup Location</span>
                                <h6 class="fw-bold text-light mb-2"><?php echo htmlspecialchars($booking['pickup_location']); ?></h6>
                                <p class="text-secondary small mb-0">
                                    <i class="fas fa-calendar me-1 text-accent"></i><?php echo date('d M Y (l)', strtotime($booking['pickup_date'])); ?><br>
                                    <i class="fas fa-clock me-1 text-accent"></i><?php echo date('h:i A', strtotime($booking['pickup_time'])); ?>
                                </p>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="p-3 bg-secondary-dark rounded border border-secondary">
                                <span class="text-cyan micro-text text-uppercase fw-bold d-block mb-1"><i class="fas fa-flag-checkered me-1"></i>Dropoff Location</span>
                                <h6 class="fw-bold text-light mb-2"><?php echo htmlspecialchars($booking['drop_location']); ?></h6>
                                <p class="text-secondary small mb-0">
                                    <i class="fas fa-calendar me-1 text-cyan"></i><?php echo date('d M Y (l)', strtotime($booking['return_date'])); ?><br>
                                    <i class="fas fa-clock me-1 text-cyan"></i><?php echo date('h:i A', strtotime($booking['return_time'])); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Status & Price Breakdown -->
            <div class="col-lg-4">
                <!-- Status Card -->
                <div class="glass-card p-4 mb-4">
                    <h6 class="fw-bold text-light mb-3"><i class="fas fa-info-circle text-accent me-2"></i>Reservation Status</h6>

                    <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom border-dark-subtle">
                        <span class="text-secondary small">Booking Status</span>
                        <?php 
                        $b_badge = $booking['booking_status'] === 'confirmed' ? 'bg-success' : 
                            ($booking['booking_status'] === 'completed' ? 'bg-primary' : 
                            ($booking['booking_status'] === 'cancelled' ? 'bg-danger' : 'bg-warning text-dark'));
                        ?>
                        <span class="badge <?php echo $b_badge; ?> px-3 py-1.5 text-uppercase fw-bold"><?php echo $booking['booking_status']; ?></span>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-secondary small">Payment Status</span>
                        <span class="badge bg-secondary text-light px-3 py-1.5 text-uppercase micro-text"><?php echo $booking['payment_status']; ?></span>
                    </div>

                    <div class="p-3 bg-secondary-dark rounded border border-secondary micro-text text-secondary">
                        <i class="fas fa-circle-check text-success me-1"></i> Doorstep delivery verification completed for Surat City.
                    </div>
                </div>

                <!-- Financial Breakdown Card -->
                <div class="glass-card p-4">
                    <h6 class="fw-bold text-light mb-3 pb-2 border-bottom border-dark-subtle"><i class="fas fa-receipt text-accent me-2"></i>Payment Summary</h6>

                    <div class="d-flex justify-content-between text-secondary small mb-2">
                        <span>Daily Rental Rate</span>
                        <span><?php echo format_currency($booking['price_per_day']); ?> / day</span>
                    </div>

                    <div class="d-flex justify-content-between text-secondary small mb-2">
                        <span>Total Rental Days</span>
                        <span><?php echo $booking['rental_days']; ?> Days</span>
                    </div>

                    <div class="d-flex justify-content-between text-secondary small mb-2">
                        <span>Rental Subtotal</span>
                        <span><?php echo format_currency($booking['subtotal']); ?></span>
                    </div>

                    <div class="d-flex justify-content-between text-secondary small mb-2">
                        <span>GST / Taxes (18%)</span>
                        <span><?php echo format_currency($booking['tax']); ?></span>
                    </div>

                    <div class="d-flex justify-content-between text-secondary small mb-3 pb-2 border-bottom border-dark-subtle">
                        <span>Refundable Security Deposit</span>
                        <span><?php echo format_currency($booking['security_deposit']); ?></span>
                    </div>

                    <div class="d-flex justify-content-between text-light fw-bold fs-5 mb-4">
                        <span>Total Amount</span>
                        <span class="text-accent"><?php echo format_currency($booking['total_amount']); ?></span>
                    </div>

                    <a href="<?php echo base_url('invoice.php?id=' . $booking['id']); ?>" target="_blank" class="btn btn-gradient w-100 py-3 fw-bold shadow-cyan"><i class="fas fa-file-pdf me-2"></i>Print Tax Invoice</a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
