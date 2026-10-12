<?php
$page_title = 'Review Moderation - Admin Panel';
include 'includes/header.php';
include 'includes/sidebar.php';

// Handle Review Deletion
if (isset($_GET['delete_id'])) {
    $del_id = (int)$_GET['delete_id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM reviews WHERE id = ?");
        $stmt->execute([$del_id]);
        flash_message('success', 'Customer review deleted successfully.');
    } catch (PDOException $e) {
        flash_message('danger', 'Error deleting review.');
    }
    header('Location: ' . base_url('admin/reviews.php'));
    exit();
}

$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

$query = "SELECT r.*, u.name as customer_name, u.email as customer_email, c.brand, c.model, c.image 
          FROM reviews r 
          JOIN users u ON r.user_id = u.id 
          JOIN cars c ON r.car_id = c.id WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND (u.name LIKE ? OR c.brand LIKE ? OR c.model LIKE ? OR r.review LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$query .= " ORDER BY r.id DESC";

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $reviews = $stmt->fetchAll();

    $total_reviews = $pdo->query("SELECT COUNT(*) FROM reviews")->fetchColumn();
    $avg_rating = $pdo->query("SELECT AVG(rating) FROM reviews")->fetchColumn() ?: 5.0;
    $five_star = $pdo->query("SELECT COUNT(*) FROM reviews WHERE rating = 5")->fetchColumn();
} catch (PDOException $e) {
    $reviews = [];
    $total_reviews = $five_star = 0;
    $avg_rating = 5.0;
}
?>

<div class="admin-main-wrapper">
    <!-- Mobile Top Navigation Bar -->
    <div class="d-lg-none px-3 py-2.5 bg-dark border-bottom border-slate-800 d-flex align-items-center justify-content-between">
        <button id="adminSidebarToggle" class="btn btn-outline-secondary btn-sm"><i class="fas fa-bars"></i></button>
        <span class="text-white fw-bold small"><i class="fas fa-star text-amber me-1"></i>Reviews</span>
        <span class="badge bg-slate-800 text-amber border border-amber border-opacity-30 rounded-pill">★ <?php echo number_format($avg_rating, 1); ?></span>
    </div>

    <div class="admin-container pt-3">
        <?php render_flash_messages(); ?>

        <!-- Luxury Header Banner Card -->
        <div class="luxury-header-card p-3 p-md-4 mb-4">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 gap-md-4">
                <div class="d-flex align-items-center gap-3 gap-md-4">
                    <div class="rounded-circle border border-2 border-amber shadow-lg d-flex align-items-center justify-content-center text-amber flex-shrink-0" style="width: 66px; height: 66px; background: rgba(245, 158, 11, 0.15); font-size: 1.85rem; box-shadow: 0 0 24px rgba(245, 158, 11, 0.25);">
                        <i class="fas fa-star-half-stroke"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                            <h2 class="fw-bold mb-0 text-white fs-3">Customer Feedback & Reviews Moderation</h2>
                            <span class="badge rounded-pill bg-amber bg-opacity-20 text-amber border border-amber border-opacity-30 px-2.5 py-1 micro-text fw-bold">
                                ★ <?php echo number_format($avg_rating, 1); ?> Rating
                            </span>
                        </div>
                        <div class="text-slate-400 small d-flex align-items-center gap-2 flex-wrap">
                            <span>Customer testimonial monitoring, vehicle ratings, and sentiment moderation</span>
                            <span>&bull;</span>
                            <span class="badge bg-dark text-slate-300 font-monospace border border-slate-700 text-nowrap py-1 px-2">
                                <i class="fas fa-certificate text-amber me-1"></i>Verified Reviews
                            </span>
                        </div>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-2.5 flex-wrap">
                    <div class="admin-hub-badge px-3.5 py-2">
                        <i class="fas fa-star me-1.5 text-amber"></i>
                        <span class="fw-bold">Avg Rating: <?php echo number_format($avg_rating, 1); ?> / 5.0</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Sentiment Ribbon -->
        <div class="row g-3 mb-4">
            <div class="col-sm-4">
                <div class="p-3 rounded-3 border border-slate-700 d-flex align-items-center justify-content-between" style="background: linear-gradient(145deg, #1E293B, #131E30);">
                    <div>
                        <span class="text-secondary small text-uppercase fw-bold" style="font-size: 0.72rem;">Total Reviews</span>
                        <div class="fs-4 fw-bold text-white"><?php echo $total_reviews; ?></div>
                    </div>
                    <div class="stat-icon-wrapper stat-icon-blue" style="width: 44px; height: 44px; font-size: 1.15rem;">
                        <i class="fas fa-comments"></i>
                    </div>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="p-3 rounded-3 border border-slate-700 d-flex align-items-center justify-content-between" style="background: linear-gradient(145deg, #1E293B, #131E30);">
                    <div>
                        <span class="text-secondary small text-uppercase fw-bold" style="font-size: 0.72rem;">Average Satisfaction</span>
                        <div class="fs-4 fw-bold text-amber d-flex align-items-center gap-1.5">
                            <?php echo number_format($avg_rating, 1); ?>
                            <i class="fas fa-star fs-6"></i>
                        </div>
                    </div>
                    <div class="stat-icon-wrapper stat-icon-amber" style="width: 44px; height: 44px; font-size: 1.15rem;">
                        <i class="fas fa-award"></i>
                    </div>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="p-3 rounded-3 border border-slate-700 d-flex align-items-center justify-content-between" style="background: linear-gradient(145deg, #1E293B, #131E30);">
                    <div>
                        <span class="text-secondary small text-uppercase fw-bold" style="font-size: 0.72rem;">5-Star Top Ratings</span>
                        <div class="fs-4 fw-bold text-emerald"><?php echo $five_star; ?></div>
                    </div>
                    <div class="stat-icon-wrapper stat-icon-emerald" style="width: 44px; height: 44px; font-size: 1.15rem;">
                        <i class="fas fa-thumbs-up"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search Bar -->
        <div class="admin-table-card p-3 mb-4">
            <form action="<?php echo base_url('admin/reviews.php'); ?>" method="GET" class="row g-2 align-items-center">
                <div class="col-md-9">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="Search by customer name, vehicle model, or review keywords..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-outline-primary w-100 rounded-pill"><i class="fas fa-filter me-1"></i>Search</button>
                    <?php if (!empty($search)): ?>
                        <a href="<?php echo base_url('admin/reviews.php'); ?>" class="btn btn-outline-secondary rounded-pill" title="Reset Search"><i class="fas fa-times"></i></a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Reviews Table -->
        <div class="admin-table-card p-4">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h5 class="fw-bold mb-0 text-white d-flex align-items-center gap-2">
                    <i class="fas fa-star text-amber"></i>
                    <span>Reviews List (<?php echo count($reviews); ?> Submissions)</span>
                </h5>
            </div>

            <div class="table-responsive">
                <table class="table table-dark-custom align-middle">
                    <thead>
                        <tr>
                            <th style="min-width: 160px;">Review Ref</th>
                            <th style="min-width: 200px;">Customer</th>
                            <th style="min-width: 210px;">Vehicle</th>
                            <th style="min-width: 160px;">Rating</th>
                            <th style="min-width: 280px;">Feedback Comments</th>
                            <th class="text-end" style="min-width: 140px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($reviews)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-secondary">
                                    <i class="fas fa-star fa-3x mb-3 opacity-30"></i>
                                    <div>No customer reviews recorded yet.</div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($reviews as $rev): ?>
                                <tr>
                                    <td>
                                        <span class="fw-bold text-accent font-monospace" style="font-size: 0.94rem;">#REV-<?php echo str_pad($rev['id'], 4, '0', STR_PAD_LEFT); ?></span>
                                        <div class="small text-nowrap mt-1.5" style="font-size: 0.84rem; color: #CBD5E1;">
                                            <i class="far fa-clock text-slate-400 me-1"></i><?php echo date('d M Y', strtotime($rev['created_at'])); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <strong class="text-white d-block mb-1" style="font-size: 0.95rem;"><?php echo htmlspecialchars($rev['customer_name']); ?></strong>
                                        <div class="small text-nowrap" style="font-size: 0.88rem; color: #E2E8F0;">
                                            <i class="fas fa-envelope text-cyan me-1.5"></i><?php echo htmlspecialchars($rev['customer_email']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="p-1 rounded bg-slate-900 border border-slate-700 flex-shrink-0" style="width: 54px; height: 36px;">
                                                <img src="<?php echo get_car_image_url($rev['image']); ?>" alt="Car" style="width: 100%; height: 100%; object-fit: contain;">
                                            </div>
                                            <div style="min-width: 0;">
                                                <span class="text-white fw-bold d-block mb-1" style="font-size: 0.95rem;"><?php echo htmlspecialchars($rev['brand'] . ' ' . $rev['model']); ?></span>
                                                <span class="badge bg-slate-800 text-cyan border border-slate-700 px-2 py-0.5 rounded" style="font-size: 0.82rem;"><?php echo htmlspecialchars($rev['category'] ?? 'Rental Fleet'); ?></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-inline-flex align-items-center gap-1.5 bg-slate-900 px-3 py-1.5 rounded-pill border border-slate-700 text-nowrap" style="font-size: 0.86rem;">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="fas fa-star <?php echo $i <= $rev['rating'] ? 'text-amber' : 'text-slate-600'; ?>" style="font-size: 0.82rem;"></i>
                                            <?php endfor; ?>
                                            <span class="ms-1 fw-bold text-white"><?php echo $rev['rating']; ?>.0</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="text-slate-200 p-2.5 px-3 rounded-2 border border-slate-700" style="background: rgba(15, 23, 42, 0.6); font-size: 0.88rem; line-height: 1.5; color: #F1F5F9;">
                                            "<?php echo htmlspecialchars($rev['review']); ?>"
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <a href="<?php echo base_url('admin/reviews.php?delete_id=' . $rev['id']); ?>" class="btn btn-outline-danger btn-sm rounded-pill px-3 py-1.5 btn-confirm-delete text-nowrap fw-semibold" style="font-size: 0.82rem;" data-confirm="Are you sure you want to delete this customer review?">
                                            <i class="fas fa-trash-can me-1.5"></i>Delete
                                        </a>
                                    </td>
                                </tr>
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
