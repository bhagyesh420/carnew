<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth-check.php'; // Require customer login

$car_id = isset($_REQUEST['car_id']) ? (int)$_REQUEST['car_id'] : 1;

try {
    $stmt = $pdo->prepare("SELECT * FROM cars WHERE id = ?");
    $stmt->execute([$car_id]);
    $car = $stmt->fetch();

    if (!$car) {
        header('Location: ' . base_url('cars.php'));
        exit();
    }
} catch (PDOException $e) {
    header('Location: ' . base_url('cars.php'));
    exit();
}

$user = get_logged_user($pdo);
$csrf_token = generate_csrf_token();

// Handle Form Post Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Prevent duplicate multi-bookings if user clicked multiple times or refreshed
    $dup_check = $pdo->prepare("SELECT id, booking_id FROM bookings 
        WHERE user_id = ? AND car_id = ? AND booking_status IN ('confirmed', 'pending') 
        AND created_at >= datetime('now', '-60 seconds') 
        ORDER BY id DESC LIMIT 1");
    $dup_check->execute([$user['id'], $car['id']]);
    $existing_dup = $dup_check->fetch();
    if ($existing_dup) {
        // Redirect to existing confirmation immediately
        header('Location: ' . base_url('booking-success.php?booking_id=' . $existing_dup['booking_id']));
        exit();
    }

    $pickup_location = sanitize($_POST['pickup_location']);
    $drop_location = sanitize($_POST['drop_location']);
    $pickup_date = sanitize($_POST['pickup_date']);
    $pickup_time = sanitize($_POST['pickup_time']);
    $return_date = sanitize($_POST['return_date']);
    $return_time = sanitize($_POST['return_time']);
    $payment_method = sanitize($_POST['payment_method']);

    $rental_days = (int)$_POST['rental_days'];
    if ($rental_days < 1) $rental_days = 1;

    $price_calc = calculate_booking_price($car['price_per_day'], $rental_days);
    $booking_code = generate_booking_id();

    try {
        $pdo->beginTransaction();

        // 1. Insert into bookings
        $b_stmt = $pdo->prepare("INSERT INTO bookings 
            (booking_id, user_id, car_id, pickup_location, drop_location, pickup_date, pickup_time, return_date, return_time, rental_days, price_per_day, subtotal, tax, security_deposit, total_amount, payment_status, booking_status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'paid', 'confirmed')");
        
        $b_stmt->execute([
            $booking_code,
            $user['id'],
            $car['id'],
            $pickup_location,
            $drop_location,
            $pickup_date,
            $pickup_time,
            $return_date,
            $return_time,
            $rental_days,
            $car['price_per_day'],
            $price_calc['subtotal'],
            $price_calc['tax'],
            $price_calc['security_deposit'],
            $price_calc['total_amount']
        ]);

        $inserted_booking_id = $pdo->lastInsertId();

        // 2. Insert into payments ledger
        $txn_id = 'TXN-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 10));
        $p_stmt = $pdo->prepare("INSERT INTO payments (booking_id, user_id, amount, payment_method, transaction_id, payment_status) VALUES (?, ?, ?, ?, ?, 'paid')");
        $p_stmt->execute([
            $inserted_booking_id,
            $user['id'],
            $price_calc['total_amount'],
            $payment_method,
            $txn_id
        ]);

        // 3. Mark car status as booked
        $c_stmt = $pdo->prepare("UPDATE cars SET status = 'booked' WHERE id = ?");
        $c_stmt->execute([$car['id']]);

        $pdo->commit();

        header('Location: ' . base_url('booking-success.php?booking_id=' . $booking_code));
        exit();
    } catch (Exception $ex) {
        $pdo->rollBack();
        $error_msg = "Booking failed: " . $ex->getMessage();
    }
}

// Prefill values from URL GET if present
$default_pickup_loc = isset($_GET['pickup_location']) ? sanitize($_GET['pickup_location']) : 'Surat International Airport (STV)';
$default_drop_loc = isset($_GET['drop_location']) ? sanitize($_GET['drop_location']) : 'Vesu';
$default_pickup_date = isset($_GET['pickup_date']) ? sanitize($_GET['pickup_date']) : date('Y-m-d', strtotime('+1 day'));
$default_return_date = isset($_GET['return_date']) ? sanitize($_GET['return_date']) : date('Y-m-d', strtotime('+4 days'));

$page_title = 'Complete Booking - DriveRent';
$extra_js = 'booking.js';
include 'includes/header.php';
include 'includes/navbar.php';
?>

<!-- Header Banner -->
<section class="border-bottom border-dark-subtle page-header-section" style="background: #0F172A;">
    <div class="container text-center">
        <div class="luxury-header-card p-3 p-md-4 p-xl-4 text-center">
            <h1 class="h2 fw-bold mb-1 text-white">Complete Your Surat Booking</h1>
            <p class="text-slate-400 small mb-0">Reserving <span class="text-accent fw-semibold"><?php echo htmlspecialchars($car['brand'] . ' ' . $car['model']); ?></span> with doorstep delivery in Surat City.</p>
        </div>
    </div>
</section>

<!-- Stepper Container -->
<section class="booking-checkout-section py-5 position-relative">
    <div class="container">
        <!-- Progress Stepper Header -->
        <div class="booking-stepper max-w-700 mx-auto mb-4" style="max-width: 700px;">
            <div class="step-item active" data-step-num="1">
                <div class="step-icon-btn"><i class="fas fa-map-marked-alt"></i></div>
                <div class="step-label">1. Trip Details</div>
            </div>
            <div class="step-item" data-step-num="2">
                <div class="step-icon-btn"><i class="fas fa-user-edit"></i></div>
                <div class="step-label">2. Driver Info</div>
            </div>
            <div class="step-item" data-step-num="3">
                <div class="step-icon-btn"><i class="fas fa-receipt"></i></div>
                <div class="step-label">3. Pricing</div>
            </div>
            <div class="step-item" data-step-num="4">
                <div class="step-icon-btn"><i class="fas fa-credit-card"></i></div>
                <div class="step-label">4. Payment</div>
            </div>
            <div class="step-item" data-step-num="5">
                <div class="step-icon-btn"><i class="fas fa-check-circle"></i></div>
                <div class="step-label">5. Review</div>
            </div>
        </div>

        <?php if (isset($error_msg)): ?>
            <div class="alert alert-danger shadow-lg max-w-800 mx-auto mb-4"><?php echo $error_msg; ?></div>
        <?php endif; ?>

        <!-- Multi-Step Form Wrapper -->
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <form action="<?php echo base_url('booking.php'); ?>" method="POST" id="multiStepBookingForm" class="glass-card p-4 p-md-5">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="car_id" value="<?php echo $car['id']; ?>">
                    <input type="hidden" id="car_price_per_day" value="<?php echo $car['price_per_day']; ?>">
                    
                    <input type="hidden" name="rental_days" id="input_rental_days" value="3">
                    <input type="hidden" name="subtotal" id="input_subtotal" value="0">
                    <input type="hidden" name="tax" id="input_tax" value="0">
                    <input type="hidden" name="security_deposit" id="input_deposit" value="5000">
                    <input type="hidden" name="total_amount" id="input_total_amount" value="0">

                    <!-- STEP 1: TRIP DETAILS -->
                    <div class="booking-step-pane" id="step-pane-1">
                        <h4 class="fw-bold mb-4 text-light"><i class="fas fa-route text-accent me-2"></i>Step 1: Surat Trip Details</h4>
                        
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label text-secondary small fw-semibold"><i class="fas fa-location-dot text-cyan me-1"></i> Surat Pickup Location</label>
                                <select name="pickup_location" class="form-select" required>
                                    <?php foreach (get_surat_locations() as $loc): ?>
                                        <option value="<?php echo htmlspecialchars($loc); ?>" <?php echo $default_pickup_loc === $loc ? 'selected' : ''; ?>><?php echo htmlspecialchars($loc); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-secondary small fw-semibold"><i class="fas fa-map-pin text-cyan me-1"></i> Surat Drop Location</label>
                                <select name="drop_location" class="form-select" required>
                                    <?php foreach (get_surat_locations() as $loc): ?>
                                        <option value="<?php echo htmlspecialchars($loc); ?>" <?php echo $default_drop_loc === $loc ? 'selected' : ''; ?>><?php echo htmlspecialchars($loc); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-secondary small fw-semibold">Pickup Date</label>
                                <input type="date" name="pickup_date" id="pickup_date" class="form-control" value="<?php echo $default_pickup_date; ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-secondary small fw-semibold">Pickup Time</label>
                                <input type="time" name="pickup_time" class="form-control" value="10:00" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-secondary small fw-semibold">Return Date</label>
                                <input type="date" name="return_date" id="return_date" class="form-control" value="<?php echo $default_return_date; ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-secondary small fw-semibold">Return Time</label>
                                <input type="time" name="return_time" class="form-control" value="10:00" required>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="button" class="btn btn-gradient px-4 btn-step-next" data-step="1">Next: Driver Info <i class="fas fa-arrow-right ms-2"></i></button>
                        </div>
                    </div>

                    <!-- STEP 2: DRIVER INFO -->
                    <div class="booking-step-pane d-none" id="step-pane-2">
                        <h4 class="fw-bold mb-4 text-light"><i class="fas fa-user text-accent me-2"></i>Step 2: Primary Driver Information</h4>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label text-secondary small fw-semibold">Full Name</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['name']); ?>" required readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-secondary small fw-semibold">Email Address</label>
                                <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-secondary small fw-semibold">Mobile Phone</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-secondary small fw-semibold">Driving License Number</label>
                                <input type="text" name="driver_license" class="form-control" placeholder="e.g. GJ-05-2023-0012345" required>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <button type="button" class="btn btn-outline-light px-4 btn-step-prev" data-step="2"><i class="fas fa-arrow-left me-2"></i>Previous</button>
                            <button type="button" class="btn btn-gradient px-4 btn-step-next" data-step="2">Next: Pricing Summary <i class="fas fa-arrow-right ms-2"></i></button>
                        </div>
                    </div>

                    <!-- STEP 3: PRICING BREAKDOWN -->
                    <div class="booking-step-pane d-none" id="step-pane-3">
                        <h4 class="fw-bold mb-4 text-light"><i class="fas fa-calculator text-accent me-2"></i>Step 3: Pricing Summary</h4>

                        <div class="glass-card p-4 rounded-3 border border-dark-subtle mb-4" style="background: #0F172A !important;">
                            <div class="d-flex align-items-center gap-3 mb-3 pb-3 border-bottom border-dark-subtle">
                                <img src="<?php echo get_car_image_url($car['image']); ?>" alt="Car" style="width: 100px; height: 60px; object-fit: contain;">
                                <div>
                                    <h5 class="fw-bold mb-0 text-light"><?php echo htmlspecialchars($car['brand'] . ' ' . $car['model']); ?></h5>
                                    <small class="text-accent"><?php echo format_currency($car['price_per_day']); ?> / day</small>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between mb-2 small text-secondary">
                                <span>Total Rental Duration:</span>
                                <strong class="text-light" id="disp_rental_days">3 Days</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2 small text-secondary">
                                <span>Subtotal:</span>
                                <span class="text-light" id="disp_subtotal">₹0.00</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2 small text-secondary">
                                <span>GST Tax (18%):</span>
                                <span class="text-light" id="disp_tax">₹0.00</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2 small text-secondary">
                                <span>Refundable Deposit:</span>
                                <span class="text-light" id="disp_deposit">₹5,000.00</span>
                            </div>
                            <hr class="border-secondary my-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-bold text-light">Grand Total Payable:</span>
                                <span class="fs-4 fw-bold text-accent" id="disp_total">₹0.00</span>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <button type="button" class="btn btn-outline-light px-4 btn-step-prev" data-step="3"><i class="fas fa-arrow-left me-2"></i>Previous</button>
                            <button type="button" class="btn btn-gradient px-4 btn-step-next" data-step="3">Next: Select Payment <i class="fas fa-arrow-right ms-2"></i></button>
                        </div>
                    </div>

                    <!-- STEP 4: PAYMENT SELECTION -->
                    <div class="booking-step-pane d-none" id="step-pane-4">
                        <h4 class="fw-bold mb-4 text-light"><i class="fas fa-credit-card text-accent me-2"></i>Step 4: Select Payment Method</h4>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="p-3 rounded border border-dark-subtle d-flex align-items-center gap-3 w-100 cursor-pointer payment-option-card">
                                    <input type="radio" name="payment_method" value="UPI Instant" checked class="form-check-input me-2 payment-radio">
                                    <div>
                                        <div class="fw-bold text-light"><i class="fas fa-qrcode text-accent me-2"></i>Instant UPI & QR Code</div>
                                        <small class="text-secondary">Google Pay, PhonePe, Paytm, BHIM</small>
                                    </div>
                                </label>
                            </div>
                            <div class="col-md-6">
                                <label class="p-3 rounded border border-dark-subtle d-flex align-items-center gap-3 w-100 cursor-pointer payment-option-card">
                                    <input type="radio" name="payment_method" value="Credit / Debit Card" class="form-check-input me-2 payment-radio">
                                    <div>
                                        <div class="fw-bold text-light"><i class="fas fa-credit-card text-accent me-2"></i>Credit / Debit Card</div>
                                        <small class="text-secondary">Visa, Mastercard, RuPay</small>
                                    </div>
                                </label>
                            </div>
                            <div class="col-md-6">
                                <label class="p-3 rounded border border-dark-subtle d-flex align-items-center gap-3 w-100 cursor-pointer payment-option-card">
                                    <input type="radio" name="payment_method" value="NetBanking" class="form-check-input me-2 payment-radio">
                                    <div>
                                        <div class="fw-bold text-light"><i class="fas fa-university text-accent me-2"></i>Net Banking</div>
                                        <small class="text-secondary">HDFC, ICICI, SBI, Axis</small>
                                    </div>
                                </label>
                            </div>
                            <div class="col-md-6">
                                <label class="p-3 rounded border border-dark-subtle d-flex align-items-center gap-3 w-100 cursor-pointer payment-option-card">
                                    <input type="radio" name="payment_method" value="Pay at Pickup" class="form-check-input me-2 payment-radio">
                                    <div>
                                        <div class="fw-bold text-light"><i class="fas fa-wallet text-accent me-2"></i>Pay at Counter</div>
                                        <small class="text-secondary">Pay cash/card upon vehicle delivery</small>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- DYNAMIC PAYMENT DETAILS PANELS -->
                        <!-- 1. UPI & QR CODE CONTAINER -->
                        <div class="payment-details-panel p-4 rounded-3 border border-dark-subtle mb-4" id="panel-upi" style="background: #0F172A;">
                            <div class="row align-items-center g-4">
                                <div class="col-md-5 text-center border-end-md border-dark-subtle">
                                    <div class="p-3 bg-white rounded-3 shadow-lg d-inline-block position-relative">
                                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=upi%3A%2F%2Fpay%3Fpa%3Ddriverent%40okaxis%26pn%3DDriveRent%2BAuto%26cu%3DINR" id="upi_qr_image" alt="UPI Scan QR Code" class="img-fluid rounded" style="width: 180px; height: 180px;">
                                        <div class="position-absolute top-50 start-50 translate-middle text-cyan rounded-circle p-2 shadow border border-cyan" style="width: 42px; height: 42px; background: #0B0F19; display: flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-qrcode fa-lg"></i>
                                        </div>
                                    </div>
                                    <div class="mt-2 text-center">
                                        <span class="badge bg-danger text-light px-3 py-1.5 rounded-pill micro-text"><i class="fas fa-clock me-1"></i>QR Expires in <span id="qr_timer">04:59</span></span>
                                    </div>
                                </div>
                                <div class="col-md-7">
                                    <h5 class="fw-bold text-light mb-2"><i class="fas fa-mobile-screen text-accent me-2"></i>Scan & Pay with Any UPI App</h5>
                                    <p class="text-secondary small mb-3">Scan this QR Code using Google Pay, PhonePe, Paytm, or BHIM to pay instantly.</p>
                                    
                                    <div class="d-flex align-items-center gap-2 mb-3 p-2.5 rounded border border-secondary" style="background: #1E293B;">
                                        <div class="flex-grow-1">
                                            <small class="text-secondary d-block micro-text">Merchant UPI ID</small>
                                            <strong class="text-accent font-monospace" id="merchant_upi_id">driverent@okaxis</strong>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-light px-3" onclick="copyUpiId()"><i class="fas fa-copy me-1"></i>Copy</button>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label text-secondary small fw-semibold">Or Enter Your VPA / UPI ID</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-at"></i></span>
                                            <input type="text" id="user_vpa_input" class="form-control" placeholder="e.g. 9876543210@paytm / name@upi">
                                            <button type="button" class="btn btn-outline-accent" onclick="verifyVpa()"><i class="fas fa-check-circle me-1"></i>Verify</button>
                                        </div>
                                    </div>

                                    <div class="d-flex align-items-center gap-3 pt-2">
                                        <span class="text-secondary micro-text">Supported Apps:</span>
                                        <div class="d-flex gap-2 text-light fs-5">
                                            <i class="fab fa-google-pay text-accent" title="Google Pay"></i>
                                            <i class="fas fa-mobile-alt text-info" title="PhonePe"></i>
                                            <i class="fas fa-wallet text-cyan" title="Paytm"></i>
                                            <i class="fas fa-university text-warning" title="BHIM UPI"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 2. CARD DETAILS PANEL -->
                        <div class="payment-details-panel p-4 rounded-3 border border-dark-subtle mb-4 d-none" id="panel-card" style="background: #0F172A;">
                            <h5 class="fw-bold text-light mb-3"><i class="fas fa-credit-card text-accent me-2"></i>Enter Card Details</h5>
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label text-secondary small">Card Number</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" placeholder="4532 •••• •••• 8921" maxlength="19">
                                        <span class="input-group-text"><i class="fab fa-cc-visa me-1 text-primary fs-5"></i><i class="fab fa-cc-mastercard text-danger fs-5"></i></span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-secondary small">Cardholder Name</label>
                                    <input type="text" class="form-control" placeholder="Full Name as on Card">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label text-secondary small">Expiry Date</label>
                                    <input type="text" class="form-control" placeholder="MM/YY" maxlength="5">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label text-secondary small">CVV / CVC</label>
                                    <input type="password" class="form-control" placeholder="•••" maxlength="4">
                                </div>
                            </div>
                        </div>

                        <!-- 3. NET BANKING PANEL -->
                        <div class="payment-details-panel p-4 rounded-3 border border-dark-subtle mb-4 d-none" id="panel-netbanking" style="background: #0F172A;">
                            <h5 class="fw-bold text-light mb-3"><i class="fas fa-university text-accent me-2"></i>Select Your Bank</h5>
                            <select class="form-select py-2.5">
                                <option value="HDFC">HDFC Bank NetBanking</option>
                                <option value="ICICI">ICICI Bank Internet Banking</option>
                                <option value="SBI">State Bank of India (SBI)</option>
                                <option value="AXIS">Axis Bank Retail Banking</option>
                                <option value="KOTAK">Kotak Mahindra Bank</option>
                            </select>
                        </div>

                        <!-- 4. PAY AT COUNTER PANEL -->
                        <div class="payment-details-panel p-4 rounded-3 border border-dark-subtle mb-4 d-none" id="panel-counter" style="background: #0F172A;">
                            <div class="alert alert-warning border-0 mb-0 d-flex align-items-center gap-3" style="background: rgba(234, 179, 8, 0.15); border: 1px solid rgba(234, 179, 8, 0.3) !important;">
                                <i class="fas fa-store-slash fa-2x text-warning"></i>
                                <div>
                                    <h6 class="fw-bold text-light mb-1">Pay at Delivery Counter</h6>
                                    <p class="mb-0 small text-secondary">Pay the total amount in cash or card upon doorstep delivery in Surat. Refundable deposit must be paid at pickup.</p>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <button type="button" class="btn btn-outline-light px-4 btn-step-prev" data-step="4"><i class="fas fa-arrow-left me-2"></i>Previous</button>
                            <button type="button" class="btn btn-gradient px-4 btn-step-next" data-step="4">Next: Final Review <i class="fas fa-arrow-right ms-2"></i></button>
                        </div>
                    </div>

                    <!-- STEP 5: CONFIRMATION & SUBMIT -->
                    <div class="booking-step-pane d-none" id="step-pane-5">
                        <h4 class="fw-bold mb-4 text-light"><i class="fas fa-check-circle text-success me-2"></i>Step 5: Review & Confirm Reservation</h4>
                        
                        <div class="alert alert-info border-0 shadow-sm mb-4" style="background: rgba(14, 165, 233, 0.15); border: 1px solid rgba(14, 165, 233, 0.3) !important; color: #7DD3FC;">
                            <i class="fas fa-info-circle me-2"></i>By clicking <strong>Confirm & Pay Now</strong>, secure payment gateway will process your transaction immediately.
                        </div>

                        <!-- Summary Box -->
                        <div class="glass-card p-4 rounded-3 border border-dark-subtle mb-4" style="background: #0F172A !important;">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-secondary small">Selected Payment Method:</span>
                                <span class="fw-bold text-accent" id="summary_payment_method">Instant UPI & QR Code</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-secondary small">Total Payable Amount:</span>
                                <span class="fs-4 fw-bold text-success" id="summary_total_amount">₹0.00</span>
                            </div>
                        </div>

                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox" id="termsCheck" required checked>
                            <label class="form-check-label text-secondary small" for="termsCheck">
                                I agree to the <a href="<?php echo base_url('terms.php'); ?>" target="_blank" class="text-accent">Terms & Conditions</a> and vehicle rental agreement policy.
                            </label>
                        </div>

                        <div class="d-flex justify-content-between">
                            <button type="button" class="btn btn-outline-light px-4 btn-step-prev" data-step="5"><i class="fas fa-arrow-left me-2"></i>Previous</button>
                            <button type="button" class="btn btn-gradient px-5 py-3 fw-bold shadow-cyan" id="btnConfirmPayNow"><i class="fas fa-lock me-2"></i>Confirm & Pay Now</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<!-- INTERACTIVE PAYMENT GATEWAY MODAL -->
<div class="modal fade" id="paymentGatewayModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content glass-card border border-cyan text-light" style="background: #111827; border-radius: 20px;">
            <div class="modal-header border-bottom border-dark-subtle py-3 px-4">
                <div class="d-flex align-items-center gap-2">
                    <i class="fas fa-shield-halved text-cyan fs-4"></i>
                    <div>
                        <h6 class="fw-bold mb-0 text-light">DriveRent Secure Payment Gateway</h6>
                        <small class="text-secondary micro-text"><i class="fas fa-lock text-success me-1"></i>256-Bit SSL Encrypted Payment</small>
                    </div>
                </div>
            </div>
            <div class="modal-body p-4 text-center">
                <!-- STEP A: PAYMENT PROCESSING ANIMATION -->
                <div id="modal_payment_processing">
                    <div class="py-3">
                        <div class="spinner-border text-cyan mb-3" style="width: 3.5rem; height: 3.5rem;" role="status">
                            <span class="visually-hidden">Processing...</span>
                        </div>
                        <h5 class="fw-bold text-light mb-1" id="payment_status_text">Connecting to Payment Gateway...</h5>
                        <p class="text-secondary small mb-3">Please do not refresh or close this window.</p>
                        
                        <div class="bg-secondary-dark p-3 rounded-3 border border-dark-subtle max-w-400 mx-auto text-start mb-3">
                            <div class="d-flex justify-content-between small text-secondary mb-1">
                                <span>Selected Method:</span>
                                <strong class="text-light" id="modal_pay_method">Instant UPI</strong>
                            </div>
                            <div class="d-flex justify-content-between small text-secondary">
                                <span>Payable Amount:</span>
                                <strong class="text-success fs-6" id="modal_pay_amount">₹0.00</strong>
                            </div>
                        </div>

                        <!-- Progress Bar -->
                        <div class="progress bg-secondary-dark mb-2" style="height: 8px;">
                            <div class="progress-bar bg-gradient progress-bar-striped progress-bar-animated" id="paymentProgressBar" role="progressbar" style="width: 25%;"></div>
                        </div>
                        <span class="micro-text text-secondary" id="payment_step_desc">Authorizing transaction with bank servers...</span>
                    </div>
                </div>

                <!-- STEP B: SUCCESS CHECKMARK ANIMATION -->
                <div id="modal_payment_success" class="d-none py-3">
                    <div class="text-success mb-3">
                        <i class="fas fa-check-circle fa-5x animate-bounce text-success"></i>
                    </div>
                    <h4 class="fw-bold text-light mb-1">Payment Successful!</h4>
                    <p class="text-secondary small mb-3">Transaction Authorized: <span class="font-monospace text-cyan" id="modal_txn_id">TXN-984210</span></p>
                    <div class="alert alert-success border-0 small py-2">
                        <i class="fas fa-file-circle-check me-1"></i> Generating your vehicle reservation voucher...
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
