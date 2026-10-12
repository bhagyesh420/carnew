<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth-check.php';

$user = get_logged_user($pdo);

// Mark all as read
if (isset($_GET['mark_read'])) {
    $upd = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    $upd->execute([$user['id']]);
    flash_message('success', 'All notifications marked as read.');
    header('Location: ' . base_url('notifications.php'));
    exit();
}

// Clear all
if (isset($_GET['clear_all'])) {
    $del = $pdo->prepare("DELETE FROM notifications WHERE user_id = ?");
    $del->execute([$user['id']]);
    flash_message('info', 'Notifications cleared.');
    header('Location: ' . base_url('notifications.php'));
    exit();
}

// Fetch User Notifications
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user['id']]);
$notifications = $stmt->fetchAll();

$page_title = 'Account Notifications - DriveRent';
include 'includes/header.php';
include 'includes/navbar.php';
?>

<section class="bg-secondary-dark border-bottom border-dark-subtle page-header-section">
    <div class="container-fluid px-3 px-md-4 px-xl-5" style="max-width: 1600px;">
        <div class="luxury-header-card p-3 p-md-4 p-xl-4">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div>
                    <h2 class="fw-bold text-white mb-1 fs-3"><i class="fas fa-bell me-2 text-accent"></i>Account Notifications</h2>
                    <p class="text-slate-400 mb-0 small">Stay updated on your reservations, vehicle dispatches, and account alerts</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="<?php echo base_url('notifications.php?mark_read=1'); ?>" class="btn btn-outline-light btn-sm btn-pill fw-semibold text-nowrap"><i class="fas fa-check-double me-1"></i>Mark Read</a>
                    <a href="<?php echo base_url('notifications.php?clear_all=1'); ?>" class="btn btn-outline-danger btn-sm btn-pill fw-semibold text-nowrap" onclick="return confirm('Clear all notifications?');"><i class="fas fa-trash me-1"></i>Clear All</a>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-4 bg-primary min-vh-100">
    <div class="container-fluid px-3 px-md-4 px-xl-5" style="max-width: 1600px;">
        <?php render_flash_messages(); ?>

        <?php if (empty($notifications)): ?>
            <div class="glass-card text-center py-5">
                <i class="fas fa-bell-slash fa-4x text-secondary mb-3"></i>
                <h4 class="fw-bold text-light">No Notifications</h4>
                <p class="text-secondary mb-0">You have no active notification alerts.</p>
            </div>
        <?php else: ?>
            <div class="d-flex flex-column gap-3">
                <?php foreach ($notifications as $n): ?>
                    <?php 
                    $type_icon = $n['type'] === 'success' ? 'fa-check-circle text-success' : 
                        ($n['type'] === 'danger' ? 'fa-exclamation-triangle text-danger' : 
                        ($n['type'] === 'warning' ? 'fa-exclamation-circle text-warning' : 'fa-info-circle text-accent'));
                    ?>
                    <div class="glass-card p-4 d-flex align-items-start gap-3 <?php echo $n['is_read'] ? 'opacity-75' : ''; ?>">
                        <div class="rounded-circle bg-primary p-3 border border-secondary">
                            <i class="fas <?php echo $type_icon; ?> fa-lg"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <h6 class="fw-bold text-light mb-0"><?php echo htmlspecialchars($n['title']); ?></h6>
                                <small class="text-secondary micro-text"><?php echo date('d M Y, h:i A', strtotime($n['created_at'])); ?></small>
                            </div>
                            <p class="text-secondary small mb-0"><?php echo htmlspecialchars($n['message']); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
