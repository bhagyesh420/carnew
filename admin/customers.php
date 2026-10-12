<?php
$page_title = 'Customer Accounts - Admin Panel';
include 'includes/header.php';
include 'includes/sidebar.php';

$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

$query = "SELECT u.*, p.city, p.driving_license, COUNT(b.id) as total_bookings, COALESCE(SUM(b.total_amount), 0) as total_spent
          FROM users u 
          LEFT JOIN user_profiles p ON u.id = p.user_id
          LEFT JOIN bookings b ON u.id = b.user_id 
          WHERE u.role = 'customer'";
$params = [];

if (!empty($search)) {
    $query .= " AND (u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$query .= " GROUP BY u.id ORDER BY u.id DESC";

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $customers = $stmt->fetchAll();

    $total_cust_count = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn();
    $total_cust_bookings = $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
} catch (PDOException $e) {
    $customers = [];
    $total_cust_count = $total_cust_bookings = 0;
}
?>

<div class="admin-main-wrapper">
    <!-- Mobile Top Navigation Bar -->
    <div class="d-lg-none px-3 py-2.5 bg-dark border-bottom border-slate-800 d-flex align-items-center justify-content-between">
        <button id="adminSidebarToggle" class="btn btn-outline-secondary btn-sm"><i class="fas fa-bars"></i></button>
        <span class="text-white fw-bold small"><i class="fas fa-users-gear text-cyan me-1"></i>Customers</span>
        <span class="badge bg-slate-800 text-cyan border border-cyan border-opacity-30 rounded-pill"><?php echo $total_cust_count; ?></span>
    </div>

    <div class="admin-container pt-3">
        <?php render_flash_messages(); ?>

        <!-- Luxury Header Banner Card -->
        <div class="luxury-header-card p-3 p-md-4 mb-4">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 gap-md-4">
                <div class="d-flex align-items-center gap-3 gap-md-4">
                    <div class="rounded-circle border border-2 border-cyan shadow-lg d-flex align-items-center justify-content-center text-cyan flex-shrink-0" style="width: 66px; height: 66px; background: rgba(14, 165, 233, 0.15); font-size: 1.85rem; box-shadow: 0 0 24px rgba(14, 165, 233, 0.25);">
                        <i class="fas fa-users-gear"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                            <h2 class="fw-bold mb-0 text-white fs-3">Customer Account Directory</h2>
                            <span class="badge rounded-pill bg-cyan bg-opacity-20 text-cyan border border-cyan border-opacity-30 px-2.5 py-1 micro-text fw-bold">
                                <?php echo $total_cust_count; ?> Users
                            </span>
                        </div>
                        <div class="text-slate-400 small d-flex align-items-center gap-2 flex-wrap">
                            <span>Verified customer profiles, rental histories, and driver credentials</span>
                            <span>&bull;</span>
                            <span class="badge bg-dark text-slate-300 font-monospace border border-slate-700 text-nowrap py-1 px-2">
                                <i class="fas fa-id-card text-cyan me-1"></i>Verified Profiles
                            </span>
                        </div>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-2.5 flex-wrap">
                    <div class="admin-hub-badge px-3.5 py-2">
                        <i class="fas fa-user-check me-1.5 text-cyan"></i>
                        <span class="fw-bold">Total Accounts: <?php echo $total_cust_count; ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Summary Cards Ribbon -->
        <div class="row g-3 mb-4">
            <div class="col-sm-6">
                <div class="p-3 rounded-3 border border-slate-700 d-flex align-items-center justify-content-between" style="background: linear-gradient(145deg, #1E293B, #131E30);">
                    <div>
                        <span class="text-secondary small text-uppercase fw-bold" style="font-size: 0.72rem;">Verified Customer Accounts</span>
                        <div class="fs-4 fw-bold text-white"><?php echo $total_cust_count; ?></div>
                    </div>
                    <div class="stat-icon-wrapper stat-icon-purple" style="width: 44px; height: 44px; font-size: 1.15rem;">
                        <i class="fas fa-user-shield"></i>
                    </div>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="p-3 rounded-3 border border-slate-700 d-flex align-items-center justify-content-between" style="background: linear-gradient(145deg, #1E293B, #131E30);">
                    <div>
                        <span class="text-secondary small text-uppercase fw-bold" style="font-size: 0.72rem;">Customer Trip Orders</span>
                        <div class="fs-4 fw-bold text-cyan"><?php echo $total_cust_bookings; ?></div>
                    </div>
                    <div class="stat-icon-wrapper stat-icon-blue" style="width: 44px; height: 44px; font-size: 1.15rem;">
                        <i class="fas fa-road"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search Bar -->
        <div class="admin-table-card p-3 mb-4">
            <form action="<?php echo base_url('admin/customers.php'); ?>" method="GET" class="row g-2 align-items-center">
                <div class="col-md-9">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="Search customer name, email address, or mobile phone..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-outline-primary w-100 rounded-pill"><i class="fas fa-filter me-1"></i>Search</button>
                    <?php if (!empty($search)): ?>
                        <a href="<?php echo base_url('admin/customers.php'); ?>" class="btn btn-outline-secondary rounded-pill" title="Reset Search"><i class="fas fa-times"></i></a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Customer Directory Table -->
        <div class="admin-table-card p-4">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h5 class="fw-bold mb-0 text-white d-flex align-items-center gap-2">
                    <i class="fas fa-address-book text-cyan"></i>
                    <span>Customer List (<?php echo count($customers); ?> Accounts)</span>
                </h5>
            </div>

            <div class="table-responsive">
                <table class="table table-dark-custom align-middle">
                    <thead>
                        <tr>
                            <th style="min-width: 160px;">User ID</th>
                            <th style="min-width: 220px;">Customer Name</th>
                            <th style="min-width: 230px;">Contact Info</th>
                            <th style="min-width: 160px;">Location</th>
                            <th style="min-width: 170px;">Driving License</th>
                            <th style="min-width: 170px;">Trips & Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($customers)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-secondary">
                                    <i class="fas fa-users-slash fa-3x mb-3 opacity-30"></i>
                                    <div>No customer accounts match your search query.</div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($customers as $c): ?>
                                <tr>
                                    <td>
                                        <span class="fw-bold text-accent font-monospace" style="font-size: 0.94rem;">#USR-<?php echo str_pad($c['id'], 4, '0', STR_PAD_LEFT); ?></span>
                                        <div class="small text-nowrap mt-1.5" style="font-size: 0.84rem; color: #CBD5E1;">Joined <?php echo date('M Y', strtotime($c['created_at'])); ?></div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="rounded-circle bg-purple bg-opacity-20 border border-purple border-opacity-30 d-flex align-items-center justify-content-center text-purple fw-bold flex-shrink-0" style="width: 42px; height: 42px; font-size: 1rem;">
                                                <?php echo strtoupper(substr($c['name'], 0, 1)); ?>
                                            </div>
                                            <div style="min-width: 0;">
                                                <strong class="text-white d-block mb-1" style="font-size: 0.95rem;"><?php echo htmlspecialchars($c['name']); ?></strong>
                                                <span class="badge bg-slate-800 text-slate-300 border border-slate-700 px-2.5 py-0.5 rounded" style="font-size: 0.82rem;">ID #<?php echo $c['id']; ?></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="text-white small mb-1.5 text-nowrap" style="font-size: 0.88rem;">
                                            <i class="fas fa-envelope text-cyan me-1.5"></i><?php echo htmlspecialchars($c['email']); ?>
                                        </div>
                                        <div class="small text-nowrap" style="font-size: 0.86rem; color: #E2E8F0;">
                                            <i class="fas fa-phone text-cyan me-1.5"></i><?php echo htmlspecialchars($c['phone']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-white text-nowrap" style="font-size: 0.90rem;">
                                            <i class="fas fa-location-dot text-rose me-1.5"></i><?php echo !empty($c['city']) ? htmlspecialchars($c['city']) : 'Surat'; ?>
                                        </div>
                                        <div class="small text-nowrap mt-1" style="font-size: 0.82rem; color: #CBD5E1;">India</div>
                                    </td>
                                    <td>
                                        <?php if (!empty($c['driving_license'])): ?>
                                            <span class="badge rounded-pill bg-cyan bg-opacity-15 text-cyan border border-cyan border-opacity-30 font-monospace text-nowrap" style="font-size: 0.84rem;">
                                                <i class="fas fa-id-card me-1.5"></i><?php echo htmlspecialchars($c['driving_license']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="small" style="font-size: 0.84rem; color: #94A3B8;">Pending Upload</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="mb-1.5">
                                            <span class="badge rounded-pill bg-slate-800 text-cyan border border-slate-700 text-nowrap" style="font-size: 0.84rem;">
                                                <i class="fas fa-key me-1.5"></i><?php echo $c['total_bookings']; ?> Trips
                                            </span>
                                        </div>
                                        <div>
                                            <span class="badge rounded-pill bg-emerald bg-opacity-20 text-emerald border border-emerald border-opacity-30 text-nowrap" style="font-size: 0.84rem;">
                                                <i class="fas fa-check-circle me-1.5"></i>VERIFIED
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
