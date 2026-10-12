<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

// Check if Remember Me cookie is active
check_remember_token($pdo);

// Capture redirect intent from GET if present
if (isset($_GET['redirect']) && !empty($_GET['redirect'])) {
    $_SESSION['redirect_after_login'] = sanitize($_GET['redirect']);
}

// Check if booking intent exists
$intent_car = null;
if (!empty($_SESSION['redirect_after_login'])) {
    $target_url = $_SESSION['redirect_after_login'];
    parse_str(parse_url($target_url, PHP_URL_QUERY) ?? '', $url_query);
    $target_car_id = isset($url_query['car_id']) ? (int)$url_query['car_id'] : (isset($url_query['id']) ? (int)$url_query['id'] : 0);
    if ($target_car_id > 0) {
        try {
            $c_stmt = $pdo->prepare("SELECT id, brand, model, category, price_per_day, image FROM cars WHERE id = ?");
            $c_stmt->execute([$target_car_id]);
            $intent_car = $c_stmt->fetch();
        } catch (PDOException $e) {}
    }
}

// Redirect if already logged in
if (is_logged_in()) {
    $redirect = !empty($_SESSION['redirect_after_login']) ? $_SESSION['redirect_after_login'] : (is_admin() ? base_url('admin/index.php') : base_url('index.php'));
    unset($_SESSION['redirect_after_login']);
    header('Location: ' . $redirect);
    exit();
}

$error_msg = null;
$csrf_token = generate_csrf_token();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Verification
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error_msg = 'Security token expired. Please reload the page and try again.';
    } else {
        $login_identity = strtolower(trim($_POST['login_identity']));
        $password = $_POST['password'];
        $remember = isset($_POST['remember']);

        try {
            // Find user by Email OR Phone
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR phone = ?");
            $stmt->execute([$login_identity, $login_identity]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['last_activity'] = time();

                if ($remember) {
                    set_remember_token($pdo, $user['id']);
                }

                // Welcome back notification
                create_notification($pdo, $user['id'], 'New Login Session', 'Logged in successfully on ' . date('d M Y, h:i A'), 'info');

                flash_message('success', 'Welcome back, ' . htmlspecialchars($user['name']) . '!');

                $redirect = !empty($_SESSION['redirect_after_login']) ? $_SESSION['redirect_after_login'] : ($user['role'] === 'admin' ? base_url('admin/index.php') : base_url('index.php'));
                unset($_SESSION['redirect_after_login']);

                header('Location: ' . $redirect);
                exit();
            } else {
                $error_msg = 'Invalid email/phone or password combination.';
            }
        } catch (PDOException $e) {
            $error_msg = 'Database authentication error occurred.';
        }
    }
}

$page_title = 'Sign In - DriveRent';
include 'includes/header.php';
include 'includes/navbar.php';
?>

<section class="py-5 bg-primary mt-5 min-vh-100 d-flex align-items-center">
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-5 col-md-8">
                <?php render_flash_messages(); ?>

                <div class="glass-card p-4 p-md-5 shadow-lg border-secondary">
                    <div class="text-center mb-4">
                        <div class="brand-logo-icon mx-auto mb-3" style="width: 52px; height: 52px; font-size: 1.5rem;">
                            <i class="fas fa-car-side"></i>
                        </div>
                        <h3 class="fw-bold text-light">Sign In to <span class="text-accent">DriveRent</span></h3>
                        <p class="text-secondary small">Access your executive rental dashboard & active bookings</p>
                    </div>

                    <?php if (isset($intent_car) && $intent_car): ?>
                        <!-- Booking in Progress Preview Box -->
                        <div class="p-3 mb-4 rounded-3 border" style="background: rgba(14, 165, 233, 0.08); border-color: rgba(14, 165, 233, 0.3) !important;">
                            <div class="d-flex align-items-center gap-3">
                                <img src="<?php echo get_car_image_url($intent_car['image']); ?>" alt="<?php echo htmlspecialchars($intent_car['brand'] . ' ' . $intent_car['model']); ?>" style="width: 76px; height: 50px; object-fit: cover; border-radius: 8px;" class="border border-slate-700">
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="badge bg-slate-800 text-cyan border border-slate-700" style="font-size: 0.68rem;"><i class="fas fa-lock me-1"></i>Sign In to Book</span>
                                        <span class="text-white fw-bold small">₹<?php echo number_format($intent_car['price_per_day']); ?>/day</span>
                                    </div>
                                    <h6 class="text-white fw-bold mb-0 mt-0.5" style="font-size: 0.95rem;"><?php echo htmlspecialchars($intent_car['brand'] . ' ' . $intent_car['model']); ?></h6>
                                    <span class="text-slate-400 small" style="font-size: 0.74rem;">Sign in below or create an account to finalize booking.</span>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($error_msg)): ?>
                        <div class="alert alert-danger border-0 shadow-sm mb-4"><i class="fas fa-exclamation-triangle me-2"></i><?php echo $error_msg; ?></div>
                    <?php endif; ?>

                    <form action="<?php echo base_url('login.php'); ?>" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                        <div class="mb-3">
                            <label class="form-label">Email or Mobile Phone Number</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user-circle"></i></span>
                                <input type="text" name="login_identity" id="login_email" class="form-control" placeholder="name@example.com or +91 98765..." value="<?php echo isset($_POST['login_identity']) ? htmlspecialchars($_POST['login_identity']) : ''; ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label mb-0">Password</label>
                                <a href="<?php echo base_url('forgot-password.php'); ?>" class="text-accent small text-decoration-none hover-underline">Forgot Password?</a>
                            </div>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-key"></i></span>
                                <input type="password" name="password" id="login_password" class="form-control" placeholder="••••••••" required>
                                <button class="btn btn-toggle-eye" type="button" onclick="togglePassVisibility('login_password', this)" tabindex="-1"><i class="fas fa-eye"></i></button>
                            </div>
                        </div>

                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox" name="remember" id="rememberCheck" checked>
                            <label class="form-check-label text-secondary small" for="rememberCheck">
                                Keep me signed in (Remember Me for 30 days)
                            </label>
                        </div>

                        <button type="submit" class="btn btn-gradient w-100 py-2.5 fw-bold mb-3 shadow-cyan"><i class="fas fa-right-to-bracket me-2"></i>Sign In</button>

                        <div class="text-center text-secondary small">
                            Don't have an account? <a href="<?php echo base_url('register.php' . (!empty($_SESSION['redirect_after_login']) ? '?redirect=' . urlencode($_SESSION['redirect_after_login']) : '')); ?>" class="text-accent fw-bold text-decoration-none">Create Free Account</a>
                        </div>
                    </form>

                    <!-- Quick Demo Credentials Helper -->
                    <div class="mt-4 pt-3 border-top border-dark-subtle">
                        <small class="text-secondary d-block text-center mb-2 fw-semibold">Quick Demo Login Shortcuts:</small>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-secondary btn-sm flex-fill" onclick="document.getElementById('login_email').value='customer@driverent.com'; document.getElementById('login_password').value='user123';">
                                <i class="fas fa-user me-1 text-accent"></i>Customer
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm flex-fill" onclick="document.getElementById('login_email').value='admin@driverent.com'; document.getElementById('login_password').value='admin123';">
                                <i class="fas fa-user-shield me-1 text-warning"></i>Admin
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
function togglePassVisibility(inputId, btn) {
    const input = document.getElementById(inputId);
    const icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'fas fa-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'fas fa-eye';
    }
}
</script>

<?php include 'includes/footer.php'; ?>
