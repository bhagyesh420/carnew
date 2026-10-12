<?php
// DriveRent - Footer Component
?>
<footer class="main-footer pt-5 pb-4">
    <div class="container-fluid px-3 px-md-4 px-xl-5" style="max-width: 1600px;">
        <div class="row g-4 pb-4 border-bottom border-dark-subtle">
            <!-- Brand Info -->
            <div class="col-lg-4 col-md-6">
                <a class="navbar-brand d-flex align-items-center gap-2 mb-3" href="<?php echo base_url('index.php'); ?>">
                    <div class="brand-logo-icon">
                        <i class="fas fa-car-side"></i>
                    </div>
                    <span class="brand-text">Drive<span class="text-accent">Rent</span></span>
                    <span class="surat-location-badge d-inline-flex align-items-center gap-1 ms-1">
                        <i class="fas fa-location-dot text-cyan fs-7"></i>
                        <span class="text-secondary fs-7 fw-semibold">Surat</span>
                    </span>
                </a>
                <p class="text-secondary mb-4 pe-lg-4">Your Journey. Your Car. Your Freedom. DriveRent offers executive vehicle rentals, hourly or daily trips, and transparent luxury service across all areas of Surat City.</p>
                <div class="social-links d-flex gap-2">
                    <a href="#" class="social-icon" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="social-icon" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="social-icon" aria-label="Twitter"><i class="fab fa-x-twitter"></i></a>
                    <a href="#" class="social-icon" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="col-lg-2 col-md-6">
                <h6 class="footer-heading mb-3">Quick Links</h6>
                <ul class="list-unstyled footer-links">
                    <li><a href="<?php echo base_url('index.php'); ?>"><i class="fas fa-chevron-right me-2 text-accent"></i>Home</a></li>
                    <li><a href="<?php echo base_url('cars.php'); ?>"><i class="fas fa-chevron-right me-2 text-accent"></i>Browse Fleet</a></li>
                    <li><a href="<?php echo base_url('about.php'); ?>"><i class="fas fa-chevron-right me-2 text-accent"></i>About Us</a></li>
                    <li><a href="<?php echo base_url('contact.php'); ?>"><i class="fas fa-chevron-right me-2 text-accent"></i>Contact Us</a></li>
                    <li><a href="<?php echo base_url('terms.php'); ?>"><i class="fas fa-chevron-right me-2 text-accent"></i>Terms & Conditions</a></li>
                    <li><a href="<?php echo base_url('privacy.php'); ?>"><i class="fas fa-chevron-right me-2 text-accent"></i>Privacy Policy</a></li>
                </ul>
            </div>

            <!-- Categories -->
            <div class="col-lg-3 col-md-6">
                <h6 class="footer-heading mb-3">Car Categories</h6>
                <ul class="list-unstyled footer-links">
                    <li><a href="<?php echo base_url('cars.php?category=Luxury'); ?>"><i class="fas fa-car me-2 text-accent"></i>Luxury Sedans</a></li>
                    <li><a href="<?php echo base_url('cars.php?category=SUV'); ?>"><i class="fas fa-truck-monster me-2 text-accent"></i>Executive SUVs</a></li>
                    <li><a href="<?php echo base_url('cars.php?category=Sports'); ?>"><i class="fas fa-tachometer-alt me-2 text-accent"></i>Sports Supercars</a></li>
                    <li><a href="<?php echo base_url('cars.php?category=Economy'); ?>"><i class="fas fa-gas-pump me-2 text-accent"></i>Economy Cruisers</a></li>
                    <li><a href="<?php echo base_url('cars.php?category=Electric'); ?>"><i class="fas fa-bolt me-2 text-accent"></i>Electric Fleet</a></li>
                </ul>
            </div>

            <!-- Contact & Hours -->
            <div class="col-lg-3 col-md-6">
                <h6 class="footer-heading mb-3">Surat Concierge</h6>
                <ul class="list-unstyled footer-contact">
                    <li class="d-flex gap-3 mb-2"><i class="fas fa-map-marker-alt text-accent mt-1"></i> <span>DriveRent Hub, Vesu Main Road, Near VR Mall, Surat 395007</span></li>
                    <li class="d-flex gap-3 mb-2"><i class="fas fa-phone-alt text-accent mt-1"></i> <span>+91 98765 43210 / +91 0261 2890000</span></li>
                    <li class="d-flex gap-3 mb-2"><i class="fas fa-envelope text-accent mt-1"></i> <span>surat@driverent.com</span></li>
                    <li class="d-flex gap-3"><i class="fas fa-clock text-accent mt-1"></i> <span>24/7 Surat Doorstep Delivery & Support</span></li>
                </ul>
            </div>
        </div>

        <div class="d-flex flex-column flex-md-row align-items-center justify-content-between pt-4 text-secondary small">
            <p class="mb-2 mb-md-0">&copy; <?php echo date('Y'); ?> <strong>DriveRent</strong>. All Rights Reserved. Crafted for Ultimate Driving Freedom.</p>
            <div class="d-flex gap-3">
                <a href="<?php echo base_url('terms.php'); ?>" class="text-secondary text-decoration-none hover-accent">Terms</a>
                <a href="<?php echo base_url('privacy.php'); ?>" class="text-secondary text-decoration-none hover-accent">Privacy</a>
                <a href="<?php echo base_url('contact.php'); ?>" class="text-secondary text-decoration-none hover-accent">Support</a>
            </div>
        </div>
    </div>
</footer>

<!-- Toast Notifications -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1100;">
    <div id="appToast" class="toast text-bg-dark border-0 shadow-lg" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header text-bg-dark border-bottom border-secondary">
            <i class="fas fa-bell me-2 text-accent" id="toastIcon"></i>
            <strong class="me-auto text-light" id="toastTitle">DriveRent Notification</strong>
            <small class="text-secondary">Just now</small>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body" id="toastBody">
            Welcome to DriveRent!
        </div>
    </div>
</div>

<!-- Back to Top Button -->
<button id="backToTopBtn" class="back-to-top-btn" title="Back to Top" aria-label="Back to Top">
    <i class="fas fa-arrow-up"></i>
</button>

<!-- Bootstrap 5.3 Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/bootstrap.bundle.min.js" defer></script>

<!-- Main JS -->
<script src="<?php echo base_url('assets/js/main.js?v=' . filemtime(__DIR__ . '/../assets/js/main.js')); ?>" defer></script>
<?php if (isset($extra_js)): ?>
    <script src="<?php echo base_url('assets/js/' . $extra_js . '?v=' . filemtime(__DIR__ . '/../assets/js/' . $extra_js)); ?>" defer></script>
<?php endif; ?>
</body>
</html>
