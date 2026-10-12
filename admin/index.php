<?php
$page_title = 'Executive Operations Command Dashboard';
include 'includes/header.php';
include 'includes/sidebar.php';

// Quick Booking Status Update Action Handler
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['action']) && $_POST['action'] === 'quick_status_update') {
    $booking_id = (int)($_POST['booking_id'] ?? 0);
    $new_status = sanitize($_POST['new_status'] ?? '');
    $valid_statuses = ['pending', 'confirmed', 'active', 'completed', 'cancelled'];

    if ($booking_id > 0 && in_array($new_status, $valid_statuses)) {
        try {
            // Fetch current booking info
            $b_stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ?");
            $b_stmt->execute([$booking_id]);
            $bk = $b_stmt->fetch();

            if ($bk) {
                // Update booking status
                $u_stmt = $pdo->prepare("UPDATE bookings SET booking_status = ? WHERE id = ?");
                $u_stmt->execute([$new_status, $booking_id]);

                // Synchronize car status
                if (in_array($new_status, ['completed', 'cancelled'])) {
                    $pdo->prepare("UPDATE cars SET status = 'available' WHERE id = ?")->execute([$bk['car_id']]);
                } elseif (in_array($new_status, ['active', 'confirmed'])) {
                    $pdo->prepare("UPDATE cars SET status = 'booked' WHERE id = ?")->execute([$bk['car_id']]);
                }

                // If approved or confirmed, notify user
                $notif_title = 'Booking Status: ' . ucfirst($new_status);
                $notif_msg = 'Your reservation ' . $bk['booking_id'] . ' status has been updated to ' . strtoupper($new_status) . '.';
                create_notification($pdo, $bk['user_id'], $notif_title, $notif_msg, 'info');

                flash_message('success', 'Reservation ' . $bk['booking_id'] . ' updated to ' . strtoupper($new_status) . ' successfully.');
            }
        } catch (PDOException $e) {
            flash_message('danger', 'Error updating reservation status: ' . $e->getMessage());
        }
    }
    header('Location: ' . base_url('admin/index.php'));
    exit();
}

// Calculate Comprehensive Operations Metrics
try {
    $total_cars = (int)$pdo->query("SELECT COUNT(*) FROM cars")->fetchColumn();
    $avail_cars = (int)$pdo->query("SELECT COUNT(*) FROM cars WHERE status = 'available'")->fetchColumn();
    $booked_cars = (int)$pdo->query("SELECT COUNT(*) FROM cars WHERE status = 'booked'")->fetchColumn();
    $maint_cars = (int)$pdo->query("SELECT COUNT(*) FROM cars WHERE status = 'maintenance'")->fetchColumn();

    $total_cust = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn();
    $total_book = (int)$pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();

    $pending_book = (int)$pdo->query("SELECT COUNT(*) FROM bookings WHERE booking_status = 'pending'")->fetchColumn();
    $confirmed_book = (int)$pdo->query("SELECT COUNT(*) FROM bookings WHERE booking_status = 'confirmed'")->fetchColumn();
    $active_book = (int)$pdo->query("SELECT COUNT(*) FROM bookings WHERE booking_status = 'active'")->fetchColumn();
    $completed_book = (int)$pdo->query("SELECT COUNT(*) FROM bookings WHERE booking_status = 'completed'")->fetchColumn();

    $total_rev = (float)$pdo->query("SELECT SUM(total_amount) FROM bookings WHERE payment_status = 'paid'")->fetchColumn() ?: 0;
    $pending_rev = (float)$pdo->query("SELECT SUM(total_amount) FROM bookings WHERE payment_status = 'pending'")->fetchColumn() ?: 0;

    // Recent 7 Bookings
    $recent_stmt = $pdo->query("SELECT b.*, u.name as customer_name, u.email as customer_email, u.phone as customer_phone, c.brand, c.model, c.image, c.category 
                                FROM bookings b 
                                JOIN users u ON b.user_id = u.id 
                                JOIN cars c ON b.car_id = c.id 
                                ORDER BY b.id DESC LIMIT 7");
    $recent_bookings = $recent_stmt->fetchAll();

    // Recent 4 Contact Inquiries
    $inq_stmt = $pdo->query("SELECT * FROM contacts ORDER BY id DESC LIMIT 4");
    $recent_inquiries = $inq_stmt->fetchAll();
    $total_inquiries = (int)$pdo->query("SELECT COUNT(*) FROM contacts")->fetchColumn();

} catch (PDOException $e) {
    $total_cars = $avail_cars = $booked_cars = $maint_cars = $total_cust = $total_book = 0;
    $pending_book = $confirmed_book = $active_book = $completed_book = 0;
    $total_rev = $pending_rev = 0;
    $recent_bookings = [];
    $recent_inquiries = [];
    $total_inquiries = 0;
}

$utilization_rate = $total_cars > 0 ? round(($booked_cars / $total_cars) * 100) : 0;
?>

<div class="admin-main-wrapper">
    <!-- Mobile Top Navigation Bar -->
    <div class="d-lg-none px-3 py-2.5 bg-dark border-bottom border-slate-800 d-flex align-items-center justify-content-between">
        <button id="adminSidebarToggle" class="btn btn-outline-secondary btn-sm"><i class="fas fa-bars"></i></button>
        <span class="text-white fw-bold small"><i class="fas fa-gauge-high text-cyan me-1"></i>Command Center</span>
        <a href="<?php echo base_url('admin/logout.php'); ?>" class="btn btn-outline-danger btn-sm rounded-pill py-1 px-2.5" style="font-size: 0.76rem;"><i class="fas fa-right-from-bracket"></i></a>
    </div>

    <div class="admin-container pt-3">
        <?php render_flash_messages(); ?>

        <!-- Executive Command Center Luxury Card (Image 2 Signature Style) -->
        <div class="luxury-header-card p-3 p-md-4 mb-4">
            <div class="d-flex flex-column flex-xl-row align-items-xl-center justify-content-between gap-3 gap-md-4">
                <!-- Left: Circular Avatar & Identity -->
                <div class="d-flex align-items-center gap-3 gap-md-4">
                    <div class="position-relative flex-shrink-0">
                        <img src="<?php echo get_user_avatar_url($admin_user); ?>" alt="Avatar" class="rounded-circle border border-2 shadow-lg" style="width: 76px; height: 76px; object-fit: cover; border-color: #38BDF8 !important; box-shadow: 0 0 24px rgba(56, 189, 248, 0.25);" onerror="this.onerror=null; this.src='<?php echo base_url('assets/images/default_avatar.svg'); ?>';">
                        <span class="position-absolute bottom-0 end-0 p-1 bg-emerald border border-2 border-dark rounded-circle" style="width: 15px; height: 15px;" title="Fleet Dispatch Online"></span>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                            <h2 class="fw-bold mb-0 text-white fs-3"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'DriverRent Administrator'); ?></h2>
                            <span class="badge rounded-pill bg-cyan bg-opacity-20 text-cyan border border-cyan border-opacity-30 px-2.5 py-1 micro-text fw-bold">
                                <i class="fas fa-shield-halved me-1"></i>CHIEF OPERATIONS
                            </span>
                        </div>
                        <div class="text-slate-400 small d-flex align-items-center gap-2 flex-wrap">
                            <span><i class="fas fa-gauge-high text-cyan me-1"></i>Executive Command Center</span>
                            <span>&bull;</span>
                            <span class="badge bg-dark text-slate-300 font-monospace border border-slate-700 text-nowrap py-1 px-2.5" style="font-size: 0.78rem;">
                                <i class="fas fa-id-badge text-cyan me-1"></i>ID: #DR-ADM-<?php echo str_pad($admin_user['id'] ?? 1, 4, '0', STR_PAD_LEFT); ?>
                            </span>
                            <span class="d-none d-sm-inline">&bull;</span>
                            <span class="text-slate-400 d-none d-sm-inline"><i class="fas fa-location-dot text-cyan me-1"></i>Surat Fleet Command &bull; 4 Hubs Active</span>
                        </div>
                    </div>
                </div>

                <!-- Right: Fleet Status & Executive Actions -->
                <div class="d-flex flex-wrap align-items-center justify-content-start justify-content-xl-end gap-2.5 gap-md-3">
                    <div class="admin-hub-badge px-3 py-2">
                        <span class="hub-pulse-dot"></span>
                        <span class="fw-bold">SURAT FLEET ONLINE &bull; 4 HUBS ACTIVE</span>
                    </div>
                    <a href="<?php echo base_url('index.php'); ?>" target="_blank" class="btn btn-outline-cyan rounded-pill px-3.5 py-2 fw-semibold d-inline-flex align-items-center gap-2">
                        <i class="fas fa-arrow-up-right-from-square"></i> Live Site
                    </a>
                    <a href="<?php echo base_url('admin/logout.php'); ?>" class="btn btn-outline-danger rounded-pill px-3.5 py-2 fw-semibold d-inline-flex align-items-center gap-2">
                        <i class="fas fa-right-from-bracket"></i> Sign Out
                    </a>
                </div>
            </div>
        </div>

        <!-- Pending Approval Alert Banner (If Any) -->
        <?php if ($pending_book > 0): ?>
            <div class="admin-priority-alert mb-4">
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stat-icon-circle amber flex-shrink-0" style="width: 44px; height: 44px; font-size: 1.2rem;">
                            <i class="fas fa-bell"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold text-white mb-0">Action Required: <?php echo $pending_book; ?> Reservation<?php echo $pending_book > 1 ? 's' : ''; ?> Pending Fleet Dispatch</h5>
                            <span class="text-amber small">Customer bookings awaiting administrative approval and vehicle assignment.</span>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <a href="<?php echo base_url('admin/bookings.php?status=pending'); ?>" class="btn btn-warning btn-sm rounded-pill px-4 py-2 fw-bold text-dark shadow-sm">
                            <i class="fas fa-list-check me-1.5"></i>Review & Approve Now (<?php echo $pending_book; ?>)
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- 4 High-Impact KPI Stat Cards -->
        <div class="row g-3 g-xl-4 mb-4">
            <!-- Total Fleet Inventory -->
            <div class="col-xxl-3 col-xl-6 col-md-6 col-12">
                <a href="<?php echo base_url('admin/cars.php'); ?>" class="text-decoration-none">
                    <div class="admin-stat-card stat-card-blue">
                        <div class="d-flex align-items-start justify-content-between">
                            <div class="flex-grow-1" style="min-width: 0;">
                                <span class="text-uppercase fw-bold text-accent" style="letter-spacing: 0.6px; font-size: 0.82rem;">Fleet Inventory</span>
                                <div class="admin-stat-number"><?php echo number_format($total_cars); ?></div>
                                <div class="d-flex align-items-center gap-1.5 flex-wrap mt-1">
                                    <span class="badge rounded-pill bg-emerald bg-opacity-20 text-emerald border border-emerald border-opacity-30 px-2 py-0.5 text-nowrap">
                                        <i class="fas fa-circle-check me-1"></i><?php echo $avail_cars; ?> Ready
                                    </span>
                                    <span class="badge rounded-pill bg-amber bg-opacity-20 text-amber border border-amber border-opacity-30 px-2 py-0.5 text-nowrap">
                                        <i class="fas fa-road me-1"></i><?php echo $booked_cars; ?> On Trip
                                    </span>
                                </div>
                            </div>
                            <div class="stat-icon-wrapper stat-icon-blue">
                                <i class="fas fa-car-rear"></i>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Active & Upcoming Reservations -->
            <div class="col-xxl-3 col-xl-6 col-md-6 col-12">
                <a href="<?php echo base_url('admin/bookings.php'); ?>" class="text-decoration-none">
                    <div class="admin-stat-card stat-card-amber">
                        <div class="d-flex align-items-start justify-content-between">
                            <div class="flex-grow-1" style="min-width: 0;">
                                <span class="text-uppercase fw-bold text-amber" style="letter-spacing: 0.6px; font-size: 0.82rem;">Total Reservations</span>
                                <div class="admin-stat-number text-amber"><?php echo number_format($total_book); ?></div>
                                <div class="d-flex align-items-center gap-1.5 flex-wrap mt-1">
                                    <span class="badge rounded-pill bg-cyan bg-opacity-20 text-cyan border border-cyan border-opacity-30 px-2 py-0.5 text-nowrap">
                                        <i class="fas fa-calendar-check me-1"></i><?php echo $confirmed_book; ?> Confirmed
                                    </span>
                                    <span class="badge rounded-pill bg-amber bg-opacity-20 text-amber border border-amber border-opacity-30 px-2 py-0.5 text-nowrap">
                                        <i class="fas fa-clock me-1"></i><?php echo $active_book; ?> Active
                                    </span>
                                </div>
                            </div>
                            <div class="stat-icon-wrapper stat-icon-amber">
                                <i class="fas fa-calendar-days"></i>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Gross Revenue -->
            <div class="col-xxl-3 col-xl-6 col-md-6 col-12">
                <a href="<?php echo base_url('admin/payments.php'); ?>" class="text-decoration-none">
                    <div class="admin-stat-card stat-card-emerald">
                        <div class="d-flex align-items-start justify-content-between">
                            <div class="flex-grow-1" style="min-width: 0;">
                                <span class="text-uppercase fw-bold text-emerald" style="letter-spacing: 0.6px; font-size: 0.82rem;">Gross Revenue</span>
                                <div class="admin-stat-number text-emerald">
                                    <?php echo format_currency($total_rev); ?>
                                </div>
                                <span class="badge rounded-pill bg-emerald bg-opacity-20 text-emerald border border-emerald border-opacity-30 px-2 py-0.5 text-nowrap">
                                    <i class="fas fa-shield-halved me-1"></i>Verified Collections
                                </span>
                            </div>
                            <div class="stat-icon-wrapper stat-icon-emerald">
                                <i class="fas fa-wallet"></i>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Registered Customer Base -->
            <div class="col-xxl-3 col-xl-6 col-md-6 col-12">
                <a href="<?php echo base_url('admin/customers.php'); ?>" class="text-decoration-none">
                    <div class="admin-stat-card stat-card-purple">
                        <div class="d-flex align-items-start justify-content-between">
                            <div class="flex-grow-1" style="min-width: 0;">
                                <span class="text-uppercase fw-bold text-purple" style="letter-spacing: 0.6px; font-size: 0.82rem;">Customer Accounts</span>
                                <div class="admin-stat-number"><?php echo number_format($total_cust); ?></div>
                                <span class="badge rounded-pill bg-purple bg-opacity-20 text-purple border border-purple border-opacity-30 px-2 py-0.5 text-nowrap">
                                    <i class="fas fa-users-gear me-1"></i>Active VIP Accounts
                                </span>
                            </div>
                            <div class="stat-icon-wrapper stat-icon-purple">
                                <i class="fas fa-users-gear"></i>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        </div>

        <!-- Executive Operations Quick Commands Banner -->
        <div class="admin-table-card p-4 mb-4">
            <div class="d-flex align-items-center justify-content-between mb-3.5 flex-wrap gap-2">
                <h5 class="fw-bold mb-0 text-white d-flex align-items-center gap-2">
                    <i class="fas fa-bolt-lightning text-amber"></i>
                    <span>Operations Quick Commands</span>
                </h5>
                <span class="badge bg-slate-900 text-cyan border border-slate-700 px-3 py-1.5 rounded-pill" style="font-size: 0.8rem; font-weight: 600;">
                    <i class="fas fa-city me-1"></i> Surat Operations Center
                </span>
            </div>

            <div class="row g-3">
                <!-- Add Car -->
                <div class="col-xl-2 col-md-4 col-6">
                    <a href="<?php echo base_url('admin/add-car.php'); ?>" class="admin-quick-action-btn">
                        <div class="quick-action-icon" style="background: rgba(14, 165, 233, 0.16); color: #38BDF8; border: 1px solid rgba(14, 165, 233, 0.35);">
                            <i class="fas fa-plus"></i>
                        </div>
                        <div>
                            <div class="action-title">Add Vehicle</div>
                            <div class="action-desc">Upload new fleet</div>
                        </div>
                    </a>
                </div>

                <!-- Reservations -->
                <div class="col-xl-2 col-md-4 col-6">
                    <a href="<?php echo base_url('admin/bookings.php'); ?>" class="admin-quick-action-btn">
                        <div class="quick-action-icon" style="background: rgba(59, 130, 246, 0.16); color: #60A5FA; border: 1px solid rgba(59, 130, 246, 0.35);">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div>
                            <div class="action-title">Manage Trips</div>
                            <div class="action-desc">Vouchers & dispatch</div>
                        </div>
                    </a>
                </div>

                <!-- Fleet -->
                <div class="col-xl-2 col-md-4 col-6">
                    <a href="<?php echo base_url('admin/cars.php'); ?>" class="admin-quick-action-btn">
                        <div class="quick-action-icon" style="background: rgba(16, 185, 129, 0.16); color: #34D399; border: 1px solid rgba(16, 185, 129, 0.35);">
                            <i class="fas fa-car-rear"></i>
                        </div>
                        <div>
                            <div class="action-title">Fleet Status</div>
                            <div class="action-desc">60+ Luxury Cars</div>
                        </div>
                    </a>
                </div>

                <!-- Payments -->
                <div class="col-xl-2 col-md-4 col-6">
                    <a href="<?php echo base_url('admin/payments.php'); ?>" class="admin-quick-action-btn">
                        <div class="quick-action-icon" style="background: rgba(16, 185, 129, 0.16); color: #34D399; border: 1px solid rgba(16, 185, 129, 0.35);">
                            <i class="fas fa-file-invoice-dollar"></i>
                        </div>
                        <div>
                            <div class="action-title">Revenue Ledger</div>
                            <div class="action-desc">Invoices & payments</div>
                        </div>
                    </a>
                </div>

                <!-- Inquiries -->
                <div class="col-xl-2 col-md-4 col-6">
                    <a href="<?php echo base_url('admin/messages.php'); ?>" class="admin-quick-action-btn">
                        <div class="quick-action-icon" style="background: rgba(245, 158, 11, 0.16); color: #FBBF24; border: 1px solid rgba(245, 158, 11, 0.35);">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div>
                            <div class="action-title">Inquiries (<?php echo $total_inquiries; ?>)</div>
                            <div class="action-desc">Concierge messages</div>
                        </div>
                    </a>
                </div>

                <!-- Customers -->
                <div class="col-xl-2 col-md-4 col-6">
                    <a href="<?php echo base_url('admin/customers.php'); ?>" class="admin-quick-action-btn">
                        <div class="quick-action-icon" style="background: rgba(139, 92, 246, 0.16); color: #A78BFA; border: 1px solid rgba(139, 92, 246, 0.35);">
                            <i class="fas fa-users-gear"></i>
                        </div>
                        <div>
                            <div class="action-title">Customer Base</div>
                            <div class="action-desc">Accounts & KYC</div>
                        </div>
                    </a>
                </div>
            </div>
        </div>

        <!-- Recent Reservations Full Table -->
        <div class="admin-table-card p-4 mb-4">
            <div class="d-flex align-items-center justify-content-between mb-3.5 flex-wrap gap-2">
                <div>
                    <h5 class="fw-bold mb-1 text-white d-flex align-items-center gap-2">
                        <i class="fas fa-clock-rotate-left text-cyan"></i>
                        <span>Recent Rental Dispatches & Live Bookings</span>
                    </h5>
                    <span class="text-slate-400 small">Real-time fleet assignments, status management, and customer rentals</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <a href="<?php echo base_url('admin/bookings.php'); ?>" class="btn btn-outline-cyan btn-sm rounded-pill px-3 py-1.5 fw-semibold">
                        View All Reservations <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-dark-custom align-middle">
                    <thead>
                        <tr>
                            <th style="min-width: 160px;">Ref ID</th>
                            <th style="min-width: 200px;">Customer</th>
                            <th style="min-width: 230px;">Vehicle</th>
                            <th style="min-width: 190px;">Rental Period</th>
                            <th style="min-width: 180px;">Tariff & Payment</th>
                            <th style="min-width: 150px;">Trip Status</th>
                            <th class="text-end" style="min-width: 180px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent_bookings)): ?>
                            <tr><td colspan="7" class="text-center py-5 text-slate-400">No rental transactions recorded yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($recent_bookings as $b): ?>
                                <tr>
                                    <!-- Booking ID -->
                                    <td>
                                        <div class="d-inline-flex align-items-center gap-2 text-nowrap">
                                            <span class="fw-bold text-accent font-monospace" style="font-size: 0.94rem; letter-spacing: 0.5px;"><?php echo htmlspecialchars($b['booking_id']); ?></span>
                                            <button type="button" class="btn-copy-code" onclick="copyRef('<?php echo htmlspecialchars($b['booking_id']); ?>')" title="Copy Reference ID">
                                                <i class="fas fa-copy"></i>
                                            </button>
                                        </div>
                                        <div class="small mt-1.5 text-nowrap" style="font-size: 0.84rem; color: #CBD5E1;">
                                            <i class="far fa-clock me-1 text-slate-400"></i><?php echo date('d M, h:i A', strtotime($b['created_at'])); ?>
                                        </div>
                                    </td>

                                    <!-- Customer -->
                                    <td>
                                        <strong class="text-white d-block mb-1" style="font-size: 0.95rem;"><?php echo htmlspecialchars($b['customer_name']); ?></strong>
                                        <div class="small text-nowrap" style="font-size: 0.88rem; color: #E2E8F0;">
                                            <i class="fas fa-phone me-1.5 text-cyan"></i><?php echo htmlspecialchars($b['customer_phone'] ?? 'N/A'); ?>
                                        </div>
                                    </td>

                                    <!-- Vehicle -->
                                    <td>
                                        <div class="d-flex align-items-center gap-3">
                                            <img src="<?php echo get_car_image_url($b['image']); ?>" alt="Car" class="rounded border border-slate-700 flex-shrink-0" style="width: 54px; height: 36px; object-fit: cover;">
                                            <div style="min-width: 0;">
                                                <strong class="text-white d-block mb-1" style="font-size: 0.95rem;"><?php echo htmlspecialchars($b['brand'] . ' ' . $b['model']); ?></strong>
                                                <span class="badge bg-slate-800 text-cyan border border-slate-700 px-2 py-0.5 rounded" style="font-size: 0.82rem;"><?php echo htmlspecialchars($b['category']); ?></span>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Dates -->
                                    <td>
                                        <div class="text-white fw-semibold text-nowrap mb-1" style="font-size: 0.90rem;">
                                            <i class="fas fa-calendar-day text-cyan me-1.5"></i><?php echo date('M d', strtotime($b['pickup_date'])); ?> &rarr; <?php echo date('M d', strtotime($b['return_date'])); ?>
                                        </div>
                                        <div class="mt-1">
                                            <span class="badge bg-slate-800 text-slate-200 border border-slate-700 px-2.5 py-0.5 rounded-pill text-nowrap" style="font-size: 0.82rem;">
                                                <i class="fas fa-clock text-amber me-1"></i><?php echo $b['rental_days']; ?> Day<?php echo $b['rental_days'] > 1 ? 's' : ''; ?>
                                            </span>
                                        </div>
                                    </td>

                                    <!-- Tariff & Payment (Two Tiers) -->
                                    <td>
                                        <div class="fw-bold text-emerald text-nowrap mb-1.5" style="font-size: 1.05rem;"><?php echo format_currency($b['total_amount']); ?></div>
                                        <div>
                                            <?php 
                                             $p_status = strtolower($b['payment_status']);
                                             $p_class = 'bg-amber bg-opacity-20 text-amber border-amber';
                                             $p_icon = 'fa-clock';
                                             if ($p_status === 'paid') {
                                                 $p_class = 'bg-emerald bg-opacity-20 text-emerald border-emerald';
                                                 $p_icon = 'fa-check';
                                             } elseif ($p_status === 'refunded') {
                                                 $p_class = 'bg-rose-500 bg-opacity-20 text-danger border-danger';
                                                 $p_icon = 'fa-rotate-left';
                                             }
                                             ?>
                                            <span class="badge rounded-pill <?php echo $p_class; ?> border border-opacity-30 text-nowrap" style="font-size: 0.84rem;">
                                                <i class="fas <?php echo $p_icon; ?> me-1"></i><?php echo strtoupper($b['payment_status']); ?>
                                            </span>
                                        </div>
                                    </td>

                                    <!-- Trip Status -->
                                    <td>
                                        <span class="badge-status <?php echo strtolower($b['booking_status']); ?> text-nowrap">
                                            <?php echo strtoupper($b['booking_status']); ?>
                                        </span>
                                    </td>

                                    <!-- Action Buttons (Clean Two Tiers) -->
                                    <td class="text-end">
                                        <div class="d-flex flex-column align-items-end">
                                            <a href="<?php echo base_url('admin/booking-details.php?id=' . $b['id']); ?>" class="btn btn-outline-cyan btn-sm rounded-pill px-3 py-1 mb-1.5 text-nowrap fw-semibold d-inline-flex align-items-center" style="font-size: 0.82rem;" title="Manage Voucher">
                                                <i class="fas fa-sliders me-1.5"></i>Manage
                                            </a>
                                            <div class="d-flex align-items-center gap-1.5 text-nowrap">
                                                <a href="<?php echo base_url('invoice.php?id=' . $b['id']); ?>" target="_blank" class="btn btn-outline-light btn-sm rounded-pill px-2.5 py-1 text-nowrap" style="font-size: 0.78rem;" title="Print Invoice">
                                                    <i class="fas fa-file-invoice me-1"></i>Invoice
                                                </a>
                                                <button type="button" class="btn btn-outline-warning btn-sm rounded-pill px-2.5 py-1 text-nowrap" style="font-size: 0.78rem;" title="Quick Status Change" onclick="openAdminStatusModal(<?php echo $b['id']; ?>, '<?php echo htmlspecialchars($b['booking_id'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($b['customer_name'], ENT_QUOTES); ?>', '<?php echo strtolower($b['booking_status']); ?>')">
                                                    <i class="fas fa-pen-to-square"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Operations Terminals & Concierge Inquiries Row -->
        <div class="row g-4 mb-4">
            <!-- Surat 4-Hub Operations & Fleet Gauge -->
            <div class="col-lg-6 col-12">
                <div class="admin-table-card p-4 h-100">
                    <div class="d-flex align-items-center justify-content-between mb-3 border-bottom border-slate-700 pb-2">
                        <h5 class="fw-bold mb-0 text-white d-flex align-items-center gap-2">
                            <i class="fas fa-chart-pie text-cyan"></i>
                            <span>Fleet Utilization & Hubs</span>
                        </h5>
                        <span class="badge bg-slate-900 text-cyan border border-slate-700 px-2.5 py-1 rounded-pill micro-text">
                            4 Live Terminals
                        </span>
                    </div>

                    <!-- Fleet Utilization Progress -->
                    <div class="d-flex justify-content-between small mb-1">
                        <span class="text-slate-400 fw-medium">Available Inventory</span>
                        <strong class="text-emerald"><?php echo $avail_cars; ?> Cars (<?php echo $total_cars > 0 ? round(($avail_cars / $total_cars) * 100) : 0; ?>%)</strong>
                    </div>
                    <div class="progress mb-3 bg-slate-900 rounded-pill" style="height: 8px;">
                        <div class="progress-bar" style="width: <?php echo $total_cars > 0 ? ($avail_cars / $total_cars) * 100 : 0; ?>%; background: linear-gradient(90deg, #10B981, #06B6D4);"></div>
                    </div>

                    <div class="d-flex justify-content-between small mb-1">
                        <span class="text-slate-400 fw-medium">Active on Trip</span>
                        <strong class="text-amber"><?php echo $booked_cars; ?> Cars (<?php echo $utilization_rate; ?>%)</strong>
                    </div>
                    <div class="progress mb-4 bg-slate-900 rounded-pill" style="height: 8px;">
                        <div class="progress-bar" style="width: <?php echo $utilization_rate; ?>%; background: linear-gradient(90deg, #F59E0B, #EF4444);"></div>
                    </div>

                    <!-- Surat 4 Hub Locations Status -->
                    <div class="p-3 rounded-3 border border-slate-700" style="background: rgba(15, 23, 42, 0.75);">
                        <div class="text-slate-400 micro-text text-uppercase fw-bold mb-2">Live Surat Dispatch Terminals:</div>
                        <div class="d-flex flex-column gap-2 small">
                            <div class="d-flex align-items-center justify-content-between">
                                <span class="text-white"><i class="fas fa-location-dot me-1.5 text-cyan"></i>Vesu VIP Terminal</span>
                                <span class="badge bg-emerald bg-opacity-20 text-emerald border border-emerald border-opacity-30 rounded-pill px-2 py-0.5 micro-text">100% Live</span>
                            </div>
                            <div class="d-flex align-items-center justify-content-between">
                                <span class="text-white"><i class="fas fa-location-dot me-1.5 text-cyan"></i>Adajan Central Hub</span>
                                <span class="badge bg-emerald bg-opacity-20 text-emerald border border-emerald border-opacity-30 rounded-pill px-2 py-0.5 micro-text">100% Live</span>
                            </div>
                            <div class="d-flex align-items-center justify-content-between">
                                <span class="text-white"><i class="fas fa-plane-departure me-1.5 text-amber"></i>Surat Airport (STV)</span>
                                <span class="badge bg-emerald bg-opacity-20 text-emerald border border-emerald border-opacity-30 rounded-pill px-2 py-0.5 micro-text">Meet & Greet</span>
                            </div>
                            <div class="d-flex align-items-center justify-content-between">
                                <span class="text-white"><i class="fas fa-location-dot me-1.5 text-cyan"></i>Piplod Luxury Lounge</span>
                                <span class="badge bg-emerald bg-opacity-20 text-emerald border border-emerald border-opacity-30 rounded-pill px-2 py-0.5 micro-text">100% Live</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Customer Inquiries Widget -->
            <div class="col-lg-6 col-12">
                <div class="admin-table-card p-4 h-100">
                    <div class="d-flex align-items-center justify-content-between mb-3 border-bottom border-slate-700 pb-2">
                        <h5 class="fw-bold mb-0 text-white d-flex align-items-center gap-2">
                            <i class="fas fa-envelope text-amber"></i>
                            <span>Recent Concierge Messages</span>
                        </h5>
                        <a href="<?php echo base_url('admin/messages.php'); ?>" class="text-cyan small text-decoration-none fw-semibold">
                            View All <i class="fas fa-arrow-right micro-text ms-1"></i>
                        </a>
                    </div>

                    <?php if (empty($recent_inquiries)): ?>
                        <div class="text-center py-4 text-slate-400 small">
                            <i class="fas fa-inbox fa-2x mb-2 opacity-30"></i>
                            <div>No customer inquiries received yet.</div>
                        </div>
                    <?php else: ?>
                        <div class="d-flex flex-column gap-2.5">
                            <?php foreach ($recent_inquiries as $inq): ?>
                                <div class="admin-inquiry-item">
                                    <div class="d-flex align-items-center justify-content-between mb-1">
                                        <strong class="text-white small text-truncate" style="max-width: 200px;">
                                            <i class="fas fa-user text-cyan me-1"></i><?php echo htmlspecialchars($inq['name']); ?>
                                        </strong>
                                        <span class="text-slate-500 micro-text"><?php echo date('d M, h:i A', strtotime($inq['created_at'])); ?></span>
                                    </div>
                                    <div class="text-amber micro-text fw-bold mb-1 text-truncate"><?php echo htmlspecialchars($inq['subject']); ?></div>
                                    <p class="text-slate-400 micro-text mb-1" style="line-height: 1.35; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                        <?php echo htmlspecialchars($inq['message']); ?>
                                    </p>
                                    <div class="d-flex justify-content-end">
                                        <a href="mailto:<?php echo htmlspecialchars($inq['email']); ?>" class="text-cyan micro-text text-decoration-none fw-semibold">
                                            <i class="fas fa-reply me-1"></i>Reply Email
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ==========================================
     ADMIN QUICK STATUS CHANGE MODAL
========================================== -->
<div class="modal fade" id="adminQuickStatusModal" tabindex="-1" aria-labelledby="statusModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content text-bg-dark border-secondary shadow-lg" style="background: #0F172A; border-radius: 20px; border: 1px solid rgba(255,255,255,0.12);">
            <div class="modal-header border-slate-700 py-3 px-4">
                <h5 class="modal-title fw-bold text-white d-flex align-items-center gap-2" id="statusModalLabel">
                    <i class="fas fa-sliders text-cyan"></i>
                    <span>Update Reservation Status</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?php echo base_url('admin/index.php'); ?>" method="POST" id="adminStatusForm">
                <div class="modal-body p-4">
                    <input type="hidden" name="action" value="quick_status_update">
                    <input type="hidden" name="booking_id" id="quick_status_booking_id" value="">

                    <div class="p-3 rounded-3 border border-slate-700 mb-3" style="background: #1E293B;">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-slate-400 small">Booking Reference:</span>
                            <span class="font-monospace text-cyan fw-bold" id="quick_status_booking_code">-</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-slate-400 small">Customer:</span>
                            <strong class="text-white" id="quick_status_customer">-</strong>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-slate-300 small fw-semibold">Select New Status</label>
                        <select name="new_status" id="quick_status_select" class="form-select bg-dark text-white border-slate-700" required>
                            <option value="pending">PENDING (Awaiting Review)</option>
                            <option value="confirmed">CONFIRMED (Fleet Assigned & Ready)</option>
                            <option value="active">ACTIVE (Vehicle On Road with Customer)</option>
                            <option value="completed">COMPLETED (Vehicle Returned & Verified)</option>
                            <option value="cancelled">CANCELLED (Trip Voided & Refunded)</option>
                        </select>
                        <div class="text-slate-400 micro-text mt-1.5">
                            <i class="fas fa-info-circle me-1 text-cyan"></i>Changing status will automatically synchronize fleet availability in the garage.
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-slate-700 py-3 px-4 d-flex justify-content-between">
                    <button type="button" class="btn btn-secondary btn-sm rounded-pill px-3.5" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-cyan btn-sm rounded-pill px-4 fw-semibold">
                        <i class="fas fa-check me-1"></i>Save Status
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openAdminStatusModal(bookingId, bookingCode, customerName, currentStatus) {
    document.getElementById('quick_status_booking_id').value = bookingId;
    document.getElementById('quick_status_booking_code').innerText = bookingCode;
    document.getElementById('quick_status_customer').innerText = customerName;

    var select = document.getElementById('quick_status_select');
    if (select) {
        select.value = currentStatus;
    }

    var modalElem = document.getElementById('adminQuickStatusModal');
    if (modalElem && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        var bsModal = bootstrap.Modal.getInstance(modalElem) || new bootstrap.Modal(modalElem);
        bsModal.show();
    }
}

function copyRef(ref) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(ref).then(function() {
            alert('Reference ID ' + ref + ' copied to clipboard!');
        });
    } else {
        prompt('Copy reference code:', ref);
    }
}
</script>

<?php include 'includes/footer.php'; ?>
