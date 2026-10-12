<?php
$page_title = 'Official Invoice - DriveRent';
include 'includes/header.php';
include 'includes/navbar.php';
require_once 'includes/auth-check.php';

$req_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$booking_code = isset($_GET['booking_id']) ? sanitize($_GET['booking_id']) : '';

try {
    if ($req_id > 0) {
        $stmt = $pdo->prepare("SELECT b.*, u.name as customer_name, u.email as customer_email, u.phone as customer_phone, c.brand, c.model, c.category, c.year, c.color, c.image, p.transaction_id, p.payment_method 
                               FROM bookings b 
                               JOIN users u ON b.user_id = u.id 
                               JOIN cars c ON b.car_id = c.id 
                               LEFT JOIN payments p ON b.id = p.booking_id 
                               WHERE b.id = ? AND (b.user_id = ? OR ? = 'admin')");
        $stmt->execute([$req_id, $_SESSION['user_id'], $_SESSION['user_role']]);
    } else {
        $stmt = $pdo->prepare("SELECT b.*, u.name as customer_name, u.email as customer_email, u.phone as customer_phone, c.brand, c.model, c.category, c.year, c.color, c.image, p.transaction_id, p.payment_method 
                               FROM bookings b 
                               JOIN users u ON b.user_id = u.id 
                               JOIN cars c ON b.car_id = c.id 
                               LEFT JOIN payments p ON b.id = p.booking_id 
                               WHERE b.booking_id = ? AND (b.user_id = ? OR ? = 'admin')");
        $stmt->execute([$booking_code, $_SESSION['user_id'], $_SESSION['user_role']]);
    }
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

<style>
@media print {
    .main-navbar, .main-footer, .no-print, #backToTopBtn, .toast-container { display: none !important; }
    body { background: #fff !important; color: #000 !important; }
    .invoice-card { background: #fff !important; color: #000 !important; border: 1px solid #ddd !important; box-shadow: none !important; }
    .text-light { color: #000 !important; }
    .text-secondary { color: #555 !important; }
    .badge { border: 1px solid #000 !important; color: #000 !important; }
}
</style>

<section class="bg-primary page-header-section">
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <!-- Action Buttons bar -->
                <div class="d-flex justify-content-between align-items-center mb-4 no-print">
                    <a href="<?php echo base_url('my-bookings.php'); ?>" class="btn btn-outline-light"><i class="fas fa-arrow-left me-2"></i>Back to My Bookings</a>
                    <button onclick="window.print()" class="btn btn-gradient px-4 py-2"><i class="fas fa-print me-2"></i>Print Official Invoice</button>
                </div>

                <!-- Invoice Document Card -->
                <div class="invoice-card glass-card p-5 border border-secondary shadow-lg">
                    <!-- Invoice Header -->
                    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center pb-4 mb-4 border-bottom border-dark-subtle">
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <div class="brand-logo-icon" style="width: 36px; height: 36px;">
                                    <i class="fas fa-road"></i>
                                    <i class="fas fa-car-side brand-car" style="font-size: 0.8rem;"></i>
                                </div>
                                <span class="brand-text fs-3">Drive<span class="text-accent">Rent</span></span>
                            </div>
                            <p class="text-secondary small mb-0">DriveRent Executive Car Rentals<br>DriveRent Hub, Vesu Main Road, Near VR Mall, Surat, Gujarat 395007</p>
                        </div>
                        <div class="text-sm-end mt-3 mt-sm-0">
                            <h3 class="fw-bold text-accent mb-1">TAX INVOICE</h3>
                            <div class="text-light fw-bold mb-1">Invoice #: <?php echo htmlspecialchars($booking['booking_id']); ?></div>
                            <small class="text-secondary">Issued: <?php echo date('M d, Y', strtotime($booking['created_at'])); ?></small>
                        </div>
                    </div>

                    <!-- Customer & Rental Details -->
                    <div class="row g-4 mb-4">
                        <div class="col-md-6">
                            <h6 class="fw-bold text-accent text-uppercase small tracking-wider mb-2">Billed To:</h6>
                            <h5 class="fw-bold text-light mb-1"><?php echo htmlspecialchars($booking['customer_name']); ?></h5>
                            <div class="text-secondary small mb-1"><i class="fas fa-envelope me-1 text-accent"></i><?php echo htmlspecialchars($booking['customer_email']); ?></div>
                            <div class="text-secondary small"><i class="fas fa-phone me-1 text-accent"></i><?php echo htmlspecialchars($booking['customer_phone']); ?></div>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <h6 class="fw-bold text-accent text-uppercase small tracking-wider mb-2">Payment Details:</h6>
                            <div class="text-light mb-1"><strong>Status:</strong> <span class="badge bg-success px-3 py-1"><?php echo strtoupper($booking['payment_status']); ?></span></div>
                            <div class="text-secondary small mb-1"><strong>Method:</strong> <?php echo htmlspecialchars($booking['payment_method'] ?: 'Credit / Debit Card'); ?></div>
                            <div class="text-secondary small"><strong>Transaction ID:</strong> <?php echo htmlspecialchars($booking['transaction_id'] ?: 'TXN-CONFIRMED'); ?></div>
                        </div>
                    </div>

                    <!-- Trip Details -->
                    <div class="bg-secondary-dark p-3 rounded-3 mb-4 border border-dark-subtle">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <small class="text-secondary d-block">Pickup Location & Time</small>
                                <strong class="text-light"><?php echo htmlspecialchars($booking['pickup_location']); ?></strong>
                                <div class="text-accent small"><?php echo date('M d, Y', strtotime($booking['pickup_date'])); ?> at <?php echo date('h:i A', strtotime($booking['pickup_time'])); ?></div>
                            </div>
                            <div class="col-md-6">
                                <small class="text-secondary d-block">Drop Location & Time</small>
                                <strong class="text-light"><?php echo htmlspecialchars($booking['drop_location']); ?></strong>
                                <div class="text-accent small"><?php echo date('M d, Y', strtotime($booking['return_date'])); ?> at <?php echo date('h:i A', strtotime($booking['return_time'])); ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- Line Items Table -->
                    <div class="table-responsive mb-4">
                        <table class="table-luxury align-middle">
                            <thead>
                                <tr>
                                    <th style="min-width: 260px;">Vehicle & Specification</th>
                                    <th style="min-width: 140px;">Duration</th>
                                    <th style="min-width: 150px;">Daily Rate</th>
                                    <th class="text-end pe-3" style="min-width: 160px;">Line Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <strong class="text-white d-block mb-1" style="font-size: 0.95rem;"><?php echo htmlspecialchars($booking['brand'] . ' ' . $booking['model']); ?></strong>
                                        <div class="small" style="font-size: 0.84rem; color: #CBD5E1;"><?php echo $booking['category']; ?> &bull; <?php echo $booking['year']; ?> &bull; <?php echo htmlspecialchars($booking['color']); ?></div>
                                    </td>
                                    <td class="text-white fw-semibold" style="font-size: 0.92rem;"><?php echo $booking['rental_days']; ?> Days</td>
                                    <td class="text-white fw-semibold" style="font-size: 0.92rem;"><?php echo format_currency($booking['price_per_day']); ?></td>
                                    <td class="text-end pe-3 fw-bold text-emerald" style="font-size: 1.05rem;"><?php echo format_currency($booking['subtotal']); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Financial Totals Ledger -->
                    <div class="row justify-content-end mb-4">
                        <div class="col-md-5">
                            <div class="d-flex justify-content-between mb-2 text-secondary">
                                <span>Subtotal:</span>
                                <span class="text-light"><?php echo format_currency($booking['subtotal']); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2 text-secondary">
                                <span>GST Tax (18%):</span>
                                <span class="text-light"><?php echo format_currency($booking['tax']); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2 text-secondary">
                                <span>Refundable Security Deposit:</span>
                                <span class="text-light"><?php echo format_currency($booking['security_deposit']); ?></span>
                            </div>
                            <hr class="border-secondary my-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-bold fs-5 text-light">Grand Total Paid:</span>
                                <span class="fs-4 fw-bold text-accent"><?php echo format_currency($booking['total_amount']); ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Footer Terms -->
                    <div class="pt-4 border-top border-dark-subtle text-secondary small">
                        <p class="fw-bold text-light mb-1">Terms & Security Deposit Notes:</p>
                        <p class="mb-0">The ₹5,000 security deposit is fully refundable to the original payment method upon vehicle return and inspection. Thank you for choosing DriveRent.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
