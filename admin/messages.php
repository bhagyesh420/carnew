<?php
$page_title = 'Contact Inquiries - Admin Panel';
include 'includes/header.php';
include 'includes/sidebar.php';

// Handle Message Deletion
if (isset($_GET['delete_id'])) {
    $del_id = (int)$_GET['delete_id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM contacts WHERE id = ?");
        $stmt->execute([$del_id]);
        flash_message('success', 'Inquiry message deleted.');
    } catch (PDOException $e) {
        flash_message('danger', 'Error deleting message.');
    }
    header('Location: ' . base_url('admin/messages.php'));
    exit();
}

$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

$query = "SELECT * FROM contacts WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND (name LIKE ? OR email LIKE ? OR subject LIKE ? OR message LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$query .= " ORDER BY id DESC";

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $messages = $stmt->fetchAll();

    $total_msgs = $pdo->query("SELECT COUNT(*) FROM contacts")->fetchColumn();
} catch (PDOException $e) {
    $messages = [];
    $total_msgs = 0;
}
?>

<div class="admin-main-wrapper">
    <!-- Mobile Top Navigation Bar -->
    <div class="d-lg-none px-3 py-2.5 bg-dark border-bottom border-slate-800 d-flex align-items-center justify-content-between">
        <button id="adminSidebarToggle" class="btn btn-outline-secondary btn-sm"><i class="fas fa-bars"></i></button>
        <span class="text-white fw-bold small"><i class="fas fa-envelope text-cyan me-1"></i>Messages</span>
        <span class="badge bg-slate-800 text-cyan border border-cyan border-opacity-30 rounded-pill"><?php echo $total_msgs; ?></span>
    </div>

    <div class="admin-container pt-3">
        <?php render_flash_messages(); ?>

        <!-- Luxury Header Banner Card -->
        <div class="luxury-header-card p-3 p-md-4 mb-4">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 gap-md-4">
                <div class="d-flex align-items-center gap-3 gap-md-4">
                    <div class="rounded-circle border border-2 border-cyan shadow-lg d-flex align-items-center justify-content-center text-cyan flex-shrink-0" style="width: 66px; height: 66px; background: rgba(14, 165, 233, 0.15); font-size: 1.85rem; box-shadow: 0 0 24px rgba(14, 165, 233, 0.25);">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                            <h2 class="fw-bold mb-0 text-white fs-3">Concierge Contact Messages</h2>
                            <span class="badge rounded-pill bg-cyan bg-opacity-20 text-cyan border border-cyan border-opacity-30 px-2.5 py-1 micro-text fw-bold">
                                <?php echo $total_msgs; ?> Inquiries
                            </span>
                        </div>
                        <div class="text-slate-400 small d-flex align-items-center gap-2 flex-wrap">
                            <span>User inquiries, special requests, and luxury concierge communication logs</span>
                            <span>&bull;</span>
                            <span class="badge bg-dark text-slate-300 font-monospace border border-slate-700 text-nowrap py-1 px-2">
                                <i class="fas fa-comments text-cyan me-1"></i>Concierge Desk
                            </span>
                        </div>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-2.5 flex-wrap">
                    <div class="admin-hub-badge px-3.5 py-2">
                        <i class="fas fa-inbox me-1.5 text-cyan"></i>
                        <span class="fw-bold">Total Inquiries: <?php echo $total_msgs; ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search Bar -->
        <div class="admin-table-card p-3 mb-4">
            <form action="<?php echo base_url('admin/messages.php'); ?>" method="GET" class="row g-2 align-items-center">
                <div class="col-md-9">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="Search inquiry by sender name, email, subject, or message content..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-outline-primary w-100 rounded-pill"><i class="fas fa-filter me-1"></i>Search</button>
                    <?php if (!empty($search)): ?>
                        <a href="<?php echo base_url('admin/messages.php'); ?>" class="btn btn-outline-secondary rounded-pill" title="Reset Search"><i class="fas fa-times"></i></a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Messages Table Card -->
        <div class="admin-table-card p-4">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h5 class="fw-bold mb-0 text-white d-flex align-items-center gap-2">
                    <i class="fas fa-envelopes-bulk text-cyan"></i>
                    <span>Inquiry Messages (<?php echo count($messages); ?> Records)</span>
                </h5>
            </div>

            <div class="table-responsive">
                <table class="table table-dark-custom align-middle">
                    <thead>
                        <tr>
                            <th style="min-width: 150px;">Message Ref</th>
                            <th style="min-width: 220px;">Sender</th>
                            <th style="min-width: 180px;">Subject</th>
                            <th style="min-width: 300px;">Message Content</th>
                            <th style="min-width: 160px;">Timestamp</th>
                            <th class="text-end" style="min-width: 130px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($messages)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-secondary">
                                    <i class="fas fa-envelope-open fa-3x mb-3 opacity-30"></i>
                                    <div>No concierge contact inquiries recorded.</div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($messages as $m): ?>
                                <tr>
                                    <td>
                                        <span class="fw-bold text-accent font-monospace" style="font-size: 0.94rem;">#MSG-<?php echo str_pad($m['id'], 4, '0', STR_PAD_LEFT); ?></span>
                                    </td>
                                    <td>
                                        <strong class="text-white d-block text-truncate mb-1" style="font-size: 0.95rem;" title="<?php echo htmlspecialchars($m['name']); ?>"><?php echo htmlspecialchars($m['name']); ?></strong>
                                        <div>
                                            <a href="mailto:<?php echo htmlspecialchars($m['email']); ?>" class="text-cyan text-decoration-none d-block text-truncate mb-1" style="font-size: 0.86rem;" title="<?php echo htmlspecialchars($m['email']); ?>">
                                                <i class="fas fa-envelope me-1.5 text-cyan"></i><?php echo htmlspecialchars($m['email']); ?>
                                            </a>
                                            <a href="tel:<?php echo htmlspecialchars($m['phone']); ?>" class="text-decoration-none text-nowrap" style="font-size: 0.84rem; color: #CBD5E1;">
                                                <i class="fas fa-phone me-1.5 text-cyan"></i><?php echo htmlspecialchars($m['phone']); ?>
                                            </a>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge rounded-pill bg-slate-800 text-cyan border border-slate-700 text-truncate d-inline-block" style="max-width: 100%; font-size: 0.84rem; padding: 6px 14px;">
                                            <?php echo htmlspecialchars($m['subject']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="p-2.5 px-3 rounded-2 border border-slate-700" style="background: rgba(15, 23, 42, 0.6); line-height: 1.5; font-size: 0.88rem; color: #F1F5F9; max-height: 75px; overflow-y: auto;">
                                            <?php echo nl2br(htmlspecialchars($m['message'])); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="text-white fw-semibold text-nowrap mb-1" style="font-size: 0.90rem;"><?php echo date('M d, Y', strtotime($m['created_at'])); ?></div>
                                        <div class="small text-nowrap" style="font-size: 0.84rem; color: #CBD5E1;"><i class="far fa-clock text-slate-400 me-1"></i><?php echo date('h:i A', strtotime($m['created_at'])); ?></div>
                                    </td>
                                    <td class="text-end">
                                        <a href="<?php echo base_url('admin/messages.php?delete_id=' . $m['id']); ?>" class="btn btn-outline-danger btn-sm rounded-pill px-3 py-1.5 btn-confirm-delete text-nowrap fw-semibold" style="font-size: 0.82rem;" data-confirm="Delete this customer inquiry?">
                                            <i class="fas fa-trash-can me-1.5"></i>Delete
                                        </a>
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
