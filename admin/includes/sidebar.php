<?php
// DriveRent - Admin Sidebar Component
$admin_current = basename($_SERVER['PHP_SELF']);
?>
<aside class="admin-sidebar">
    <div class="sidebar-header d-flex align-items-center gap-3">
        <div class="brand-logo-icon">
            <i class="fas fa-car-side"></i>
        </div>
        <div>
            <div class="brand-text">Drive<span class="text-accent">Rent</span></div>
            <span class="badge bg-cyan text-dark fw-bold px-2 py-0.5" style="font-size: 0.65rem; letter-spacing: 0.5px;">SURAT ADMIN</span>
        </div>
    </div>

<?php
// Query live counts for sidebar badges
$side_pending_count = 0;
$side_cars_count = 0;
$side_msgs_count = 0;
if (isset($pdo)) {
    try {
        $side_pending_count = (int)$pdo->query("SELECT COUNT(*) FROM bookings WHERE booking_status = 'pending'")->fetchColumn();
        $side_cars_count = (int)$pdo->query("SELECT COUNT(*) FROM cars")->fetchColumn();
        $side_msgs_count = (int)$pdo->query("SELECT COUNT(*) FROM contacts")->fetchColumn();
    } catch (Exception $e) {}
}
?>
    <div class="sidebar-nav">
        <a href="<?php echo base_url('admin/index.php'); ?>" class="sidebar-link <?php echo $admin_current === 'index.php' ? 'active' : ''; ?>">
            <i class="fas fa-gauge-high"></i> <span>Dashboard</span>
        </a>
        <a href="<?php echo base_url('admin/cars.php'); ?>" class="sidebar-link <?php echo in_array($admin_current, ['cars.php', 'add-car.php', 'edit-car.php']) ? 'active' : ''; ?>">
            <i class="fas fa-car-rear"></i> <span>Fleet Management</span>
            <?php if ($side_cars_count > 0): ?>
                <span class="badge bg-slate-800 text-cyan border border-slate-700 rounded-pill ms-auto" style="font-size: 0.72rem; padding: 2px 7px;"><?php echo $side_cars_count; ?></span>
            <?php endif; ?>
        </a>
        <a href="<?php echo base_url('admin/bookings.php'); ?>" class="sidebar-link <?php echo in_array($admin_current, ['bookings.php', 'booking-details.php']) ? 'active' : ''; ?>">
            <i class="fas fa-calendar-check"></i> <span>Reservations</span>
            <?php if ($side_pending_count > 0): ?>
                <span class="badge bg-amber text-dark rounded-pill ms-auto fw-bold" style="font-size: 0.72rem; padding: 2px 7px;" title="<?php echo $side_pending_count; ?> Pending Approval"><?php echo $side_pending_count; ?> new</span>
            <?php endif; ?>
        </a>
        <a href="<?php echo base_url('admin/customers.php'); ?>" class="sidebar-link <?php echo $admin_current === 'customers.php' ? 'active' : ''; ?>">
            <i class="fas fa-users-gear"></i> <span>Customer Accounts</span>
        </a>
        <a href="<?php echo base_url('admin/payments.php'); ?>" class="sidebar-link <?php echo $admin_current === 'payments.php' ? 'active' : ''; ?>">
            <i class="fas fa-wallet"></i> <span>Revenue & Payments</span>
        </a>
        <a href="<?php echo base_url('admin/reviews.php'); ?>" class="sidebar-link <?php echo $admin_current === 'reviews.php' ? 'active' : ''; ?>">
            <i class="fas fa-star-half-stroke"></i> <span>Reviews Moderation</span>
        </a>
        <a href="<?php echo base_url('admin/messages.php'); ?>" class="sidebar-link <?php echo $admin_current === 'messages.php' ? 'active' : ''; ?>">
            <i class="fas fa-envelope"></i> <span>Contact Messages</span>
            <?php if ($side_msgs_count > 0): ?>
                <span class="badge bg-slate-800 text-slate-300 border border-slate-700 rounded-pill ms-auto" style="font-size: 0.72rem; padding: 2px 7px;"><?php echo $side_msgs_count; ?></span>
            <?php endif; ?>
        </a>
        <a href="<?php echo base_url('admin/settings.php'); ?>" class="sidebar-link <?php echo $admin_current === 'settings.php' ? 'active' : ''; ?>">
            <i class="fas fa-sliders"></i> <span>Settings</span>
        </a>
    </div>

    <div class="p-2.5 px-3 border-top border-dark-subtle mt-auto">
        <a href="<?php echo base_url('index.php'); ?>" target="_blank" class="btn btn-outline-light btn-sm w-100 mb-2 rounded-pill" style="font-size: 0.83rem; padding: 6px 12px;">
            <i class="fas fa-arrow-up-right-from-square me-1"></i> View Live Site
        </a>
        <a href="<?php echo base_url('admin/logout.php'); ?>" class="btn btn-outline-danger btn-sm w-100 rounded-pill" style="font-size: 0.83rem; padding: 6px 12px;">
            <i class="fas fa-right-from-bracket me-1"></i> Sign Out
        </a>
    </div>
</aside>
