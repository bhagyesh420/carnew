<?php
$page_title = 'System Settings - Admin Panel';
include 'includes/header.php';
include 'includes/sidebar.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    flash_message('success', 'System configuration parameters updated successfully.');
    header('Location: ' . base_url('admin/settings.php'));
    exit();
}
?>

<div class="admin-main-wrapper">
    <!-- Mobile Top Navigation Bar -->
    <div class="d-lg-none px-3 py-2.5 bg-dark border-bottom border-slate-800 d-flex align-items-center justify-content-between">
        <button id="adminSidebarToggle" class="btn btn-outline-secondary btn-sm"><i class="fas fa-bars"></i></button>
        <span class="text-white fw-bold small"><i class="fas fa-sliders text-cyan me-1"></i>Settings</span>
        <span class="badge bg-slate-800 text-emerald border border-emerald border-opacity-30 rounded-pill"><i class="fas fa-check"></i></span>
    </div>

    <div class="admin-container pt-3">
        <?php render_flash_messages(); ?>

        <!-- Luxury Header Banner Card -->
        <div class="luxury-header-card p-3 p-md-4 mb-4">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 gap-md-4">
                <div class="d-flex align-items-center gap-3 gap-md-4">
                    <div class="rounded-circle border border-2 border-cyan shadow-lg d-flex align-items-center justify-content-center text-cyan flex-shrink-0" style="width: 66px; height: 66px; background: rgba(14, 165, 233, 0.15); font-size: 1.85rem; box-shadow: 0 0 24px rgba(14, 165, 233, 0.25);">
                        <i class="fas fa-sliders"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                            <h2 class="fw-bold mb-0 text-white fs-3">System & Platform Configuration</h2>
                            <span class="badge rounded-pill bg-emerald bg-opacity-20 text-emerald border border-emerald border-opacity-30 px-2.5 py-1 micro-text fw-bold">
                                <i class="fas fa-circle-check me-1"></i>Active & Operational
                            </span>
                        </div>
                        <div class="text-slate-400 small d-flex align-items-center gap-2 flex-wrap">
                            <span>Operational defaults, tax parameters, branding identity, and central hub address</span>
                            <span>&bull;</span>
                            <span class="badge bg-dark text-slate-300 font-monospace border border-slate-700 text-nowrap py-1 px-2">
                                <i class="fas fa-server text-cyan me-1"></i>Master Settings
                            </span>
                        </div>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-2.5 flex-wrap">
                    <div class="admin-hub-badge px-3.5 py-2">
                        <span class="hub-pulse-dot"></span>
                        <span class="fw-bold">Platform Status: Online</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="admin-table-card p-4 p-md-5">
            <form action="<?php echo base_url('admin/settings.php'); ?>" method="POST">
                <!-- Section 1: Branding -->
                <div class="pb-4 mb-4 border-bottom border-dark-subtle">
                    <h5 class="fw-bold text-white mb-3 d-flex align-items-center gap-2">
                        <i class="fas fa-globe text-accent"></i>
                        <span>1. Platform Identity & Branding</span>
                    </h5>
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Platform Brand Name</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-car-side"></i></span>
                                <input type="text" class="form-control" value="DriveRent" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Concierge Support Email</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                <input type="email" class="form-control" value="support@driverent.com" required>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Pricing & Taxation -->
                <div class="pb-4 mb-4 border-bottom border-dark-subtle">
                    <h5 class="fw-bold text-white mb-3 d-flex align-items-center gap-2">
                        <i class="fas fa-receipt text-emerald"></i>
                        <span>2. Financial & Taxation Defaults</span>
                    </h5>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">GST Rate (%)</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-percent"></i></span>
                                <input type="number" class="form-control" value="18" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Standard Security Deposit (₹)</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-shield-halved"></i></span>
                                <input type="number" class="form-control" value="5000" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Currency Symbol</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-indian-rupee-sign"></i></span>
                                <input type="text" class="form-control" value="₹ (INR)" required>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 3: Surat Operations Hub -->
                <div class="pb-4 mb-4">
                    <h5 class="fw-bold text-white mb-3 d-flex align-items-center gap-2">
                        <i class="fas fa-location-dot text-rose"></i>
                        <span>3. Surat Central Operations Hub</span>
                    </h5>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Primary Operational Hub</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-building"></i></span>
                                <input type="text" class="form-control" value="DriveRent Executive Hub, Vesu Main Road, Surat 395007" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Doorstep Delivery Coverage</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-map"></i></span>
                                <input type="text" class="form-control" value="Vesu, Piplod, Adajan, Varachha, Dumas Road" required>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <button type="submit" class="btn btn-gradient rounded-pill px-4 py-2.5 fw-bold shadow-cyan">
                        <i class="fas fa-save me-1.5"></i> Save Configuration Settings
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
