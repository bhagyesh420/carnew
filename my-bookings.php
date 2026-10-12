<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth-check.php';

$user = get_logged_user($pdo);
$status_filter = sanitize($_GET['status'] ?? 'all');
$csrf_token = generate_csrf_token();

// Handle Cancel Booking Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel_booking') {
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
            header('Location: ' . base_url('my-bookings.php'));
            exit();
        } else {
            flash_message('danger', 'This reservation cannot be cancelled at its current status.');
        }
    }
}

// Build Query
$sql = "SELECT b.*, c.brand, c.model, c.category, c.image, c.transmission, c.fuel_type 
        FROM bookings b 
        JOIN cars c ON b.car_id = c.id 
        WHERE b.user_id = ?";
$params = [$user['id']];

if ($status_filter === 'upcoming') {
    $sql .= " AND b.booking_status IN ('pending', 'confirmed', 'active')";
} elseif ($status_filter === 'completed') {
    $sql .= " AND b.booking_status = 'completed'";
} elseif ($status_filter === 'cancelled') {
    $sql .= " AND b.booking_status = 'cancelled'";
}

$sql .= " ORDER BY b.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

$page_title = 'My Vehicle Bookings - DriveRent';
include 'includes/header.php';
include 'includes/navbar.php';
?>

<section class="bg-secondary-dark border-bottom border-dark-subtle page-header-section">
    <div class="container-fluid px-3 px-md-4 px-xl-5" style="max-width: 1600px;">
        <div class="luxury-header-card p-3 p-md-4 p-xl-4">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                <div>
                    <h2 class="fw-bold text-white mb-1 fs-3"><i class="fas fa-calendar-check me-2 text-accent"></i>My Bookings</h2>
                    <p class="text-slate-400 mb-0 small">View active reservations, download invoices, and manage vehicle trips</p>
                </div>
                <a href="<?php echo base_url('cars.php'); ?>" class="btn btn-gradient px-4 py-2 btn-pill shadow-cyan fw-bold text-nowrap">
                    <i class="fas fa-plus me-1"></i>Book Another Vehicle
                </a>
            </div>
        </div>
    </div>
</section>

<section class="py-4 bg-primary min-vh-100">
    <div class="container-fluid px-3 px-md-4 px-xl-5" style="max-width: 1600px;">
        <?php render_flash_messages(); ?>

        <!-- Filter Status Tabs -->
        <div class="d-flex gap-2 mb-4 overflow-auto pb-2">
            <a href="<?php echo base_url('my-bookings.php?status=all'); ?>" class="btn btn-sm btn-pill <?php echo $status_filter === 'all' ? 'active btn-accent' : 'btn-outline-secondary'; ?>">All Bookings</a>
            <a href="<?php echo base_url('my-bookings.php?status=upcoming'); ?>" class="btn btn-sm btn-pill <?php echo $status_filter === 'upcoming' ? 'active btn-accent' : 'btn-outline-secondary'; ?>">Upcoming / Active</a>
            <a href="<?php echo base_url('my-bookings.php?status=completed'); ?>" class="btn btn-sm btn-pill <?php echo $status_filter === 'completed' ? 'active btn-accent' : 'btn-outline-secondary'; ?>">Completed Trips</a>
            <a href="<?php echo base_url('my-bookings.php?status=cancelled'); ?>" class="btn btn-sm btn-pill <?php echo $status_filter === 'cancelled' ? 'active btn-accent' : 'btn-outline-secondary'; ?>">Cancelled</a>
        </div>

        <?php if (empty($bookings)): ?>
            <div class="glass-card text-center py-5">
                <i class="fas fa-calendar-xmark fa-4x text-secondary mb-3"></i>
                <h4 class="fw-bold text-light">No Bookings Found</h4>
                <p class="text-secondary mb-4">No car rental reservations match your selected status filter.</p>
                <a href="<?php echo base_url('cars.php'); ?>" class="btn btn-gradient px-4 py-2.5">Browse Rental Fleet</a>
            </div>
        <?php else: ?>
            <div class="d-flex flex-column gap-4">
                <?php foreach ($bookings as $b): ?>
                    <div class="luxury-booking-card mb-4 reveal">
                        <!-- Top Tier: Vehicle Details & Tariff/Status Header -->
                        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 pb-3 mb-3 border-bottom border-slate-700">
                            <!-- Vehicle identity -->
                            <div class="d-flex align-items-center gap-3">
                                <img src="<?php echo get_car_image_url($b['image']); ?>" alt="Car" class="rounded-3 border border-secondary flex-shrink-0" style="width: 110px; height: 75px; object-fit: cover;">
                                <div>
                                    <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                                        <h4 class="fw-bold text-light mb-0 fs-5"><?php echo htmlspecialchars($b['brand'] . ' ' . $b['model']); ?></h4>
                                        <span class="badge bg-accent text-light micro-text px-2 py-0.5"><?php echo htmlspecialchars($b['category']); ?></span>
                                    </div>
                                    <p class="text-secondary small mb-0 d-flex align-items-center gap-2 flex-wrap">
                                        <span><i class="fas fa-cog me-1 text-accent"></i><?php echo $b['transmission']; ?></span>
                                        <span>&bull;</span>
                                        <span><i class="fas fa-gas-pump me-1 text-accent"></i><?php echo $b['fuel_type']; ?></span>
                                        <span>&bull;</span>
                                        <span class="text-cyan font-monospace fw-semibold"><i class="fas fa-receipt me-1"></i>REF: <?php echo htmlspecialchars($b['booking_id']); ?></span>
                                    </p>
                                </div>
                            </div>

                            <!-- Tariff & Status Badges -->
                            <div class="d-flex flex-column align-items-start align-items-md-end text-md-end flex-shrink-0">
                                <div class="mb-1">
                                    <span class="text-secondary micro-text d-block">Total Tariff</span>
                                    <strong class="text-emerald fs-4 text-nowrap"><?php echo format_currency($b['total_amount']); ?></strong>
                                </div>
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <span class="glow-status-pill text-nowrap <?php echo strtolower($b['booking_status']); ?>">
                                        <span class="glow-dot"></span>
                                        <?php echo $b['booking_status']; ?>
                                    </span>
                                    <span class="badge bg-secondary text-light px-2.5 py-1.5 text-uppercase micro-text text-nowrap"><?php echo $b['payment_status']; ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Bottom Tier: Pickup & Dropoff Schedule + Action Controls -->
                        <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 gap-lg-4">
                            <!-- Pickup & Dropoff Schedule (Spacious & Clean) -->
                            <div class="d-flex flex-column flex-sm-row gap-3 gap-md-4 flex-grow-1">
                                <div class="flex-fill">
                                    <span class="text-secondary micro-text d-block text-uppercase fw-semibold mb-1">
                                        <i class="fas fa-location-dot me-1 text-accent"></i>Pickup Location & Date
                                    </span>
                                    <strong class="text-light small d-block mb-1"><?php echo htmlspecialchars($b['pickup_location']); ?></strong>
                                    <span class="text-cyan micro-text text-nowrap">
                                        <i class="fas fa-calendar-day me-1"></i><?php echo date('d M Y', strtotime($b['pickup_date'])); ?> @ <?php echo date('h:i A', strtotime($b['pickup_time'])); ?>
                                    </span>
                                </div>
                                <div class="flex-fill">
                                    <span class="text-secondary micro-text d-block text-uppercase fw-semibold mb-1">
                                        <i class="fas fa-flag-checkered me-1 text-emerald"></i>Dropoff Location & Date
                                    </span>
                                    <strong class="text-light small d-block mb-1"><?php echo htmlspecialchars($b['drop_location']); ?></strong>
                                    <span class="text-emerald micro-text text-nowrap">
                                        <i class="fas fa-calendar-check me-1"></i><?php echo date('d M Y', strtotime($b['return_date'])); ?> @ <?php echo date('h:i A', strtotime($b['return_time'])); ?>
                                    </span>
                                </div>
                            </div>

                            <!-- Action Controls -->
                            <div class="d-flex align-items-center gap-2 flex-wrap flex-shrink-0">
                                <a href="<?php echo base_url('booking-details.php?id=' . $b['id']); ?>" class="btn btn-outline-cyan btn-sm rounded-pill px-3 py-1.5 text-nowrap fw-semibold">
                                    <i class="fas fa-eye me-1"></i>Details
                                </a>
                                <a href="<?php echo base_url('invoice.php?id=' . $b['id']); ?>" target="_blank" class="btn btn-outline-light btn-sm rounded-pill px-3 py-1.5 text-nowrap fw-semibold">
                                    <i class="fas fa-file-invoice me-1"></i>Invoice
                                </a>
                                <?php if (in_array($b['booking_status'], ['pending', 'confirmed'])): ?>
                                    <button type="button" class="btn btn-outline-danger btn-sm rounded-pill px-3 py-1.5 text-nowrap fw-semibold" onclick="openCancelBookingModal(<?php echo $b['id']; ?>, '<?php echo htmlspecialchars($b['booking_id'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($b['brand'] . ' ' . $b['model'], ENT_QUOTES); ?>')">
                                        <i class="fas fa-xmark me-1"></i>Cancel
                                    </button>
                                <?php else: ?>
                                    <a href="<?php echo base_url('booking.php?car_id=' . $b['car_id']); ?>" class="btn btn-outline-primary btn-sm rounded-pill px-3 py-1.5 text-nowrap fw-semibold">
                                        <i class="fas fa-rotate-right me-1"></i>Re-Book
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- GLOBAL CANCEL RESERVATION MODAL (Outside card/reveal containers to prevent backdrop z-index traps) -->
<div class="modal fade" id="globalCancelModal" tabindex="-1" aria-labelledby="cancelModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content text-bg-dark border-secondary shadow-lg" style="background: #111827; border-radius: 18px;">
            <div class="modal-header border-secondary py-3 px-4">
                <h5 class="modal-title fw-bold text-light" id="cancelModalLabel">
                    <i class="fas fa-triangle-exclamation text-warning me-2"></i>Cancel Reservation
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?php echo base_url('my-bookings.php'); ?>" method="POST" id="cancelBookingForm">
                <div class="modal-body p-4">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="action" value="cancel_booking">
                    <input type="hidden" name="booking_id" id="cancel_modal_booking_id" value="">

                    <div class="alert alert-warning border-0 small mb-3" style="background: rgba(234, 179, 8, 0.15); color: #FDE047;">
                        <i class="fas fa-info-circle me-1"></i> Are you sure you want to cancel this booking? Refund will be initiated to your original payment method.
                    </div>

                    <div class="p-3 rounded-3 border border-dark-subtle mb-3" style="background: #1E293B;">
                        <div class="small text-secondary mb-1">Vehicle:</div>
                        <h6 class="fw-bold text-light mb-2" id="cancel_modal_car_name">-</h6>
                        <div class="small text-secondary">Booking Reference:</div>
                        <span class="font-monospace text-cyan fw-bold" id="cancel_modal_booking_code">-</span>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-secondary small fw-semibold">Reason for Cancellation</label>
                        <select name="cancel_reason" class="form-select bg-dark text-light border-secondary">
                            <option value="Duplicate booking made by mistake">Duplicate booking made by mistake</option>
                            <option value="Change of travel plans">Change of travel plans</option>
                            <option value="Booked another vehicle">Booked another vehicle</option>
                            <option value="Emergency requirement">Emergency requirement</option>
                            <option value="Other">Other reason</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-secondary py-3 px-4 d-flex justify-content-between">
                    <button type="button" class="btn btn-secondary btn-sm btn-pill px-3" data-bs-dismiss="modal">Keep Booking</button>
                    <button type="submit" class="btn btn-danger btn-sm btn-pill px-4 fw-semibold" id="btnConfirmCancelModal">
                        <i class="fas fa-xmark me-1"></i>Confirm Cancellation
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openCancelBookingModal(bookingId, bookingCode, carName) {
    var idInput = document.getElementById('cancel_modal_booking_id');
    var codeSpan = document.getElementById('cancel_modal_booking_code');
    var nameSpan = document.getElementById('cancel_modal_car_name');

    if (idInput) idInput.value = bookingId;
    if (codeSpan) codeSpan.innerText = bookingCode;
    if (nameSpan) nameSpan.innerText = carName;

    var modalElem = document.getElementById('globalCancelModal');
    if (modalElem) {
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            var bsModal = bootstrap.Modal.getInstance(modalElem) || new bootstrap.Modal(modalElem);
            bsModal.show();
        } else {
            if (confirm('Are you sure you want to cancel booking ' + bookingCode + ' (' + carName + ')?')) {
                document.getElementById('cancelBookingForm').submit();
            }
        }
    }
}
</script>

<?php include 'includes/footer.php'; ?>
