<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) {
    header('Location: ' . base_url('dashboard.php'));
    exit();
}

$error_msg = null;
$success_msg = null;
$active_reset = null;
$csrf_token = generate_csrf_token();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error_msg = 'Security token expired. Please try again.';
    } else {
        $identity = strtolower(trim($_POST['identity']));

        try {
            $stmt = $pdo->prepare("SELECT id, name, email, phone FROM users WHERE email = ? OR phone = ?");
            $stmt->execute([$identity, $identity]);
            $user = $stmt->fetch();

            if ($user) {
                $otp = sprintf("%06d", mt_rand(100000, 999999));
                $token = bin2hex(random_bytes(20));
                $expires = date('Y-m-d H:i:s', time() + (15 * 60)); // 15 Minutes

                // Clear previous resets for this email
                $del = $pdo->prepare("DELETE FROM password_resets WHERE email = ?");
                $del->execute([$user['email']]);

                // Insert new reset record
                $ins = $pdo->prepare("INSERT INTO password_resets (email, token, otp, expires_at) VALUES (?, ?, ?, ?)");
                $ins->execute([$user['email'], $token, $otp, $expires]);

                $active_reset = [
                    'email' => $user['email'],
                    'token' => $token,
                    'otp' => $otp
                ];

                $success_msg = 'Password reset OTP has been generated for ' . htmlspecialchars($user['email']) . '.';
            } else {
                $error_msg = 'No registered account found with that email address or phone number.';
            }
        } catch (PDOException $e) {
            $error_msg = 'Database error requesting password reset.';
        }
    }
}

$page_title = 'Forgot Password - DriveRent';
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
                            <i class="fas fa-key"></i>
                        </div>
                        <h3 class="fw-bold text-light">Forgot Password</h3>
                        <p class="text-secondary small">Enter your registered email or phone number to receive a verification OTP code</p>
                    </div>

                    <?php if (isset($error_msg)): ?>
                        <div class="alert alert-danger border-0 shadow-sm mb-4"><i class="fas fa-exclamation-triangle me-2"></i><?php echo $error_msg; ?></div>
                    <?php endif; ?>

                    <?php if (isset($success_msg) && $active_reset): ?>
                        <div class="alert alert-success border-0 shadow-sm mb-4">
                            <h6 class="fw-bold mb-1"><i class="fas fa-check-circle me-2"></i>OTP Code Sent Successfully!</h6>
                            <p class="small mb-2"><?php echo $success_msg; ?></p>
                            <div class="p-3 bg-dark rounded border border-success text-center mb-3">
                                <span class="text-secondary small d-block mb-1">Your 6-Digit Demo Verification OTP:</span>
                                <strong class="fs-3 text-accent tracking-wider"><?php echo $active_reset['otp']; ?></strong>
                            </div>
                            <a href="<?php echo base_url('reset-password.php?token=' . $active_reset['token']); ?>" class="btn btn-gradient w-100 py-2.5 fw-bold text-decoration-none">
                                Proceed to Enter OTP & Reset Password <i class="fas fa-arrow-right ms-1"></i>
                            </a>
                        </div>
                    <?php else: ?>
                        <form action="<?php echo base_url('forgot-password.php'); ?>" method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                            <div class="mb-4">
                                <label class="form-label text-secondary small fw-semibold">Registered Email or Mobile Number</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-dark-subtle border-secondary text-secondary"><i class="fas fa-envelope"></i></span>
                                    <input type="text" name="identity" class="form-control bg-secondary text-light border-secondary" placeholder="customer@driverent.com or +91 98765..." required>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-gradient w-100 py-3 fw-bold mb-3 shadow-cyan"><i class="fas fa-paper-plane me-2"></i>Send Verification Code</button>

                            <div class="text-center text-secondary small">
                                Remembered your password? <a href="<?php echo base_url('login.php'); ?>" class="text-accent fw-bold text-decoration-none">Back to Sign In</a>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
