<?php
$page_title = 'Booking Confirmed - DriveRent';
include 'includes/header.php';
include 'includes/navbar.php';
require_once 'includes/auth-check.php';

$booking_id = isset($_GET['booking_id']) ? sanitize($_GET['booking_id']) : '';

try {
    $stmt = $pdo->prepare("SELECT b.*, c.brand, c.model, c.category, c.image, p.transaction_id, p.payment_method 
                           FROM bookings b 
                           JOIN cars c ON b.car_id = c.id 
                           LEFT JOIN payments p ON b.id = p.booking_id 
                           WHERE b.booking_id = ? AND b.user_id = ?");
    $stmt->execute([$booking_id, $_SESSION['user_id']]);
    $booking = $stmt->fetch();

    if (!$booking) {
        header('Location: ' . base_url('my-bookings.php'));
        exit();
    }
} catch (PDOException $e) {
    header('Location: ' . base_url('my-bookings.php'));
    exit();
}
?>

<section class="bg-primary page-header-section">
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-8 text-center">
                <!-- Success Animated Checkmark -->
                <div class="mb-4">
                    <div class="rounded-circle bg-success bg-opacity-20 p-4 d-inline-flex align-items-center justify-content-center border border-success border-opacity-50" style="width: 110px; height: 110px;">
                        <i class="fas fa-check-circle fa-4x text-success"></i>
                    </div>
                </div>

                <h1 class="display-5 fw-bold mb-2">Your Ride Is Confirmed!</h1>
                <p class="text-secondary max-w-600 mx-auto mb-5">Thank you for booking with DriveRent. A confirmation email and digital invoice voucher have been generated for your trip.</p>

                <!-- Receipt Card -->
                <div class="luxury-header-card p-4 p-md-5 text-start mb-5">
                    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center pb-4 mb-4 border-bottom border-dark-subtle">
                        <div>
                            <span class="text-secondary small">Booking Reference Code</span>
                            <h3 class="fw-bold text-accent mb-0"><?php echo htmlspecialchars($booking['booking_id']); ?></h3>
                        </div>
                        <div class="mt-2 mt-sm-0">
                            <span class="badge bg-success fs-6 px-3 py-2">STATUS: <?php echo strtoupper($booking['booking_status']); ?></span>
                        </div>
                    </div>

                    <div class="row g-4 mb-4">
                        <div class="col-md-6">
                            <div class="d-flex gap-3">
                                <img src="<?php echo get_car_image_url($booking['image']); ?>" alt="Car" style="width: 90px; height: 60px; object-fit: contain;">
                                <div>
                                    <h5 class="fw-bold mb-0 text-light"><?php echo htmlspecialchars($booking['brand'] . ' ' . $booking['model']); ?></h5>
                                    <small class="text-accent"><?php echo $booking['category']; ?> Series</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <span class="text-secondary small">Transaction ID:</span>
                            <div class="fw-mono text-light fw-bold"><?php echo htmlspecialchars($booking['transaction_id']); ?></div>
                            <small class="text-secondary">Method: <?php echo htmlspecialchars($booking['payment_method']); ?></small>
                        </div>
                    </div>

                    <div class="row g-3 bg-secondary-dark p-3 rounded mb-4 border border-dark-subtle">
                        <div class="col-md-6">
                            <small class="text-secondary d-block">Pickup Location & Date</small>
                            <strong class="text-light"><?php echo htmlspecialchars($booking['pickup_location']); ?></strong>
                            <div class="text-accent small"><i class="fas fa-calendar-alt me-1"></i><?php echo date('M d, Y', strtotime($booking['pickup_date'])); ?> at <?php echo date('h:i A', strtotime($booking['pickup_time'])); ?></div>
                        </div>
                        <div class="col-md-6">
                            <small class="text-secondary d-block">Drop Location & Date</small>
                            <strong class="text-light"><?php echo htmlspecialchars($booking['drop_location']); ?></strong>
                            <div class="text-accent small"><i class="fas fa-calendar-check me-1"></i><?php echo date('M d, Y', strtotime($booking['return_date'])); ?> at <?php echo date('h:i A', strtotime($booking['return_time'])); ?></div>
                        </div>
                    </div>

                    <!-- Financial Summary -->
                    <div class="border-top border-dark-subtle pt-3">
                        <div class="d-flex justify-content-between mb-2 text-secondary">
                            <span>Rental Duration:</span>
                            <strong class="text-light"><?php echo $booking['rental_days']; ?> Days @ <?php echo format_currency($booking['price_per_day']); ?>/day</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2 text-secondary">
                            <span>Subtotal Amount:</span>
                            <span class="text-light"><?php echo format_currency($booking['subtotal']); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2 text-secondary">
                            <span>GST Tax (18%):</span>
                            <span class="text-light"><?php echo format_currency($booking['tax']); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2 text-secondary">
                            <span>Security Deposit:</span>
                            <span class="text-light"><?php echo format_currency($booking['security_deposit']); ?></span>
                        </div>
                        <hr class="border-secondary my-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-bold fs-5 text-light">Total Paid:</span>
                            <span class="fs-4 fw-bold text-accent"><?php echo format_currency($booking['total_amount']); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="d-flex flex-wrap justify-content-center gap-3 no-print">
                    <a href="<?php echo base_url('my-bookings.php'); ?>" class="btn btn-gradient px-4 py-3 btn-pill"><i class="fas fa-calendar-check me-2"></i>View My Bookings</a>
                    <a href="<?php echo base_url('invoice.php?booking_id=' . $booking['booking_id']); ?>" class="btn btn-outline-light px-4 py-3 btn-pill"><i class="fas fa-file-invoice me-2"></i>View Official Invoice</a>
                    <a href="<?php echo base_url('index.php'); ?>" class="btn btn-link text-secondary text-decoration-none py-3">Back to Home</a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
