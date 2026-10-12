<?php
// DriveRent - Global Navigation Header
$current_page = basename($_SERVER['PHP_SELF']);
?>
<nav class="navbar navbar-expand-lg fixed-top navbar-dark main-navbar" id="mainNavbar">
    <div class="container-fluid px-3 px-md-4 px-xl-5" style="max-width: 1600px;">
        <!-- Brand Logo -->
        <a class="navbar-brand d-flex align-items-center gap-2" href="<?php echo base_url('index.php'); ?>">
            <div class="brand-logo-icon">
                <i class="fas fa-car-side"></i>
            </div>
            <span class="brand-text">Drive<span class="text-accent">Rent</span></span>
        </a>

        <!-- Mobile Toggler -->
        <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#navbarOffcanvas" aria-controls="navbarOffcanvas" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Navbar Offcanvas / Links -->
        <div class="offcanvas offcanvas-end text-bg-dark" tabindex="-1" id="navbarOffcanvas">
            <div class="offcanvas-header border-bottom border-dark-subtle">
                <h5 class="offcanvas-title fw-bold">Drive<span class="text-accent">Rent</span></h5>
                <button type="button" class="btn-close btn-close-white shadow-none" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body">
                <ul class="navbar-nav mx-auto mb-2 mb-lg-0 align-items-lg-center">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'index.php' ? 'active' : ''; ?>" href="<?php echo base_url('index.php'); ?>">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'cars.php' || $current_page === 'car-details.php' ? 'active' : ''; ?>" href="<?php echo base_url('cars.php'); ?>">Cars</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'about.php' ? 'active' : ''; ?>" href="<?php echo base_url('about.php'); ?>">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'contact.php' ? 'active' : ''; ?>" href="<?php echo base_url('contact.php'); ?>">Contact</a>
                    </li>
                </ul>

                <div class="d-flex align-items-center gap-3 mt-3 mt-lg-0">
                    <?php if (is_logged_in()): ?>
                        <div class="dropdown position-relative">
                            <button class="btn btn-outline-light dropdown-toggle d-flex align-items-center gap-2 user-btn-pill" type="button" id="userProfileDropdown" data-bs-toggle="dropdown" aria-expanded="false" onclick="toggleUserDropdown(event)">
                                <i class="fas fa-user-circle text-accent fs-5"></i>
                                <span><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end shadow-lg border-secondary mt-2" id="userProfileMenu" aria-labelledby="userProfileDropdown">
                                <?php if (is_admin()): ?>
                                    <li><a class="dropdown-item py-2" href="<?php echo base_url('admin/index.php'); ?>"><i class="fas fa-chart-line me-2 text-accent"></i>Admin Panel</a></li>
                                    <li><a class="dropdown-item py-2" href="<?php echo base_url('dashboard.php'); ?>"><i class="fas fa-gauge-high me-2 text-accent"></i>User Dashboard</a></li>
                                    <li><a class="dropdown-item py-2" href="<?php echo base_url('profile.php'); ?>"><i class="fas fa-user-gear me-2 text-accent"></i>Profile Settings</a></li>
                                    <li><hr class="dropdown-divider border-secondary my-1"></li>
                                <?php else: ?>
                                    <li><a class="dropdown-item py-2" href="<?php echo base_url('dashboard.php'); ?>"><i class="fas fa-gauge-high me-2 text-accent"></i>User Dashboard</a></li>
                                    <li><a class="dropdown-item py-2" href="<?php echo base_url('my-bookings.php'); ?>"><i class="fas fa-calendar-check me-2 text-accent"></i>My Bookings</a></li>
                                    <li><a class="dropdown-item py-2" href="<?php echo base_url('saved-cars.php'); ?>"><i class="fas fa-heart me-2 text-warning"></i>Saved Vehicles</a></li>
                                    <li><a class="dropdown-item py-2" href="<?php echo base_url('notifications.php'); ?>"><i class="fas fa-bell me-2 text-info"></i>Notifications</a></li>
                                    <li><a class="dropdown-item py-2" href="<?php echo base_url('profile.php'); ?>"><i class="fas fa-user-gear me-2 text-accent"></i>Profile Settings</a></li>
                                    <li><hr class="dropdown-divider border-secondary my-1"></li>
                                <?php endif; ?>
                                <li><a class="dropdown-item text-danger py-2" href="<?php echo base_url('logout.php'); ?>"><i class="fas fa-sign-out-alt me-2"></i>Sign Out</a></li>
                            </ul>
                        </div>
                        <script>
                        function toggleUserDropdown(e) {
                            if (e) {
                                e.preventDefault();
                                e.stopPropagation();
                            }
                            var btn = document.getElementById('userProfileDropdown');
                            var menu = document.getElementById('userProfileMenu');
                            if (btn && menu) {
                                var isOpen = menu.classList.contains('show') || menu.style.display === 'block';
                                if (isOpen) {
                                    menu.classList.remove('show');
                                    menu.style.display = 'none';
                                    btn.classList.remove('show');
                                    btn.setAttribute('aria-expanded', 'false');
                                } else {
                                    menu.classList.add('show');
                                    menu.style.display = 'block';
                                    btn.classList.add('show');
                                    btn.setAttribute('aria-expanded', 'true');
                                }
                            }
                        }

                        document.addEventListener('click', function(e) {
                            var btn = document.getElementById('userProfileDropdown');
                            var menu = document.getElementById('userProfileMenu');
                            if (btn && menu && !btn.contains(e.target) && !menu.contains(e.target)) {
                                menu.classList.remove('show');
                                menu.style.display = 'none';
                                btn.classList.remove('show');
                                btn.setAttribute('aria-expanded', 'false');
                            }
                        });
                        </script>
                    <?php else: ?>
                        <a href="<?php echo base_url('login.php'); ?>" class="btn btn-link text-light text-decoration-none px-2.5 py-1.5 fw-semibold me-1">Log In</a>
                        <a href="<?php echo base_url('register.php'); ?>" class="btn btn-outline-light px-3 py-1.5 btn-pill fw-semibold me-1">Register</a>
                        <a href="<?php echo base_url('cars.php'); ?>" class="btn btn-gradient px-3.5 py-1.5 btn-pill shadow-cyan fw-bold">Book Now</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</nav>
