<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) {
    header('Location: ' . base_url('dashboard.php'));
    exit();
}

$token = sanitize($_GET['token'] ?? $_POST['token'] ?? '');
$error_msg = null;
$reset_record = null;
$csrf_token = generate_csrf_token();

if (!empty($token)) {
    $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE token = ? AND expires_at > CURRENT_TIMESTAMP");
    $stmt->execute([$token]);
    $reset_record = $stmt->fetch();
}

if (!$reset_record && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    flash_message('danger', 'Invalid or expired password reset link. Please request a new OTP.');
    header('Location: ' . base_url('forgot-password.php'));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error_msg = 'Security token expired. Please try again.';
    } else {
        $entered_otp = trim($_POST['otp']);
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        if (!$reset_record) {
            $error_msg = 'Reset token is invalid or expired. Please request a new OTP.';
        } elseif ($entered_otp !== $reset_record['otp']) {
            $error_msg = 'Invalid 6-digit OTP code. Please check and try again.';
        } elseif ($new_password !== $confirm_password) {
            $error_msg = 'New passwords do not match. Please verify.';
        } else {
            $pass_check = validate_password_strength($new_password);
            if (!$pass_check['valid']) {
                $error_msg = $pass_check['message'];
            } else {
                try {
                    $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                    $upd = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
                    $upd->execute([$hashed, $reset_record['email']]);

                    // Get user ID for notification
                    $u_stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                    $u_stmt->execute([$reset_record['email']]);
                    $u_row = $u_stmt->fetch();

                    if ($u_row) {
                        create_notification($pdo, $u_row['id'], 'Password Reset Success', 'Your password was updated successfully. If you did not make this change, contact support immediately.', 'warning');
                    }

                    // Delete reset token
                    $del = $pdo->prepare("DELETE FROM password_resets WHERE email = ?");
                    $del->execute([$reset_record['email']]);

                    flash_message('success', 'Password reset successfully! You can now log in with your new password.');
                    header('Location: ' . base_url('login.php'));
                    exit();
                } catch (PDOException $e) {
                    $error_msg = 'Database error resetting password.';
                }
            }
        }
    }
}

$page_title = 'Reset Password - DriveRent';
include 'includes/header.php';
include 'includes/navbar.php';
?>

<section class="py-5 bg-primary mt-5 min-vh-100 d-flex align-items-center">
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-8">
                <?php render_flash_messages(); ?>

                <div class="glass-card p-4 p-md-5 shadow-lg border-secondary">
                    <div class="text-center mb-4">
                        <div class="brand-logo-icon mx-auto mb-3" style="width: 52px; height: 52px; font-size: 1.5rem;">
                            <i class="fas fa-lock-open"></i>
                        </div>
                        <h3 class="fw-bold text-light">Set New Password</h3>
                        <p class="text-secondary small">Enter the 6-digit OTP sent to <strong class="text-accent"><?php echo htmlspecialchars($reset_record['email'] ?? ''); ?></strong> and your new password</p>
                    </div>

                    <?php if (isset($error_msg)): ?>
                        <div class="alert alert-danger border-0 shadow-sm mb-4"><i class="fas fa-exclamation-triangle me-2"></i><?php echo $error_msg; ?></div>
                    <?php endif; ?>

                    <form action="<?php echo base_url('reset-password.php?token=' . htmlspecialchars($token)); ?>" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                        <div class="mb-3">
                            <label class="form-label text-secondary small fw-semibold">Enter 6-Digit OTP Code <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-dark-subtle border-secondary text-secondary"><i class="fas fa-shield-alt"></i></span>
                                <input type="text" name="otp" class="form-control bg-secondary text-light border-secondary tracking-wider fw-bold text-center fs-5" placeholder="123456" maxlength="6" value="<?php echo htmlspecialchars($_GET['otp'] ?? $reset_record['otp'] ?? ''); ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-secondary small fw-semibold">New Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-dark-subtle border-secondary text-secondary"><i class="fas fa-key"></i></span>
                                <input type="password" name="new_password" id="reset_password" class="form-control bg-secondary text-light border-secondary" placeholder="At least 8 chars" required onkeyup="checkPasswordStrength(this.value)">
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassVisibility('reset_password', this)"><i class="fas fa-eye"></i></button>
                            </div>
                            <div class="progress mt-2" style="height: 5px;">
                                <div id="pass_strength_bar" class="progress-bar bg-danger" role="progressbar" style="width: 0%"></div>
                            </div>
                            <small id="pass_strength_text" class="text-secondary" style="font-size: 0.75rem;">Password must contain 8+ chars, uppercase, lowercase & numbers</small>
                        </div>

                        <div class="mb-4">
                            <label class="form-label text-secondary small fw-semibold">Confirm New Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-dark-subtle border-secondary text-secondary"><i class="fas fa-lock"></i></span>
                                <input type="password" name="confirm_password" id="reset_confirm_password" class="form-control bg-secondary text-light border-secondary" placeholder="Repeat new password" required>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-gradient w-100 py-3 fw-bold mb-3 shadow-cyan"><i class="fas fa-check-circle me-2"></i>Reset Password Now</button>

                        <div class="text-center text-secondary small">
                            <a href="<?php echo base_url('login.php'); ?>" class="text-accent fw-bold text-decoration-none"><i class="fas fa-arrow-left me-1"></i>Back to Sign In</a>
                        </div>
                    </form>
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

function checkPasswordStrength(val) {
    const bar = document.getElementById('pass_strength_bar');
    const text = document.getElementById('pass_strength_text');
    let score = 0;

    if (val.length >= 8) score += 25;
    if (/[A-Z]/.test(val)) score += 25;
    if (/[a-z]/.test(val)) score += 25;
    if (/[0-9]/.test(val)) score += 25;

    bar.style.width = score + '%';

    if (score < 50) {
        bar.className = 'progress-bar bg-danger';
        text.innerText = 'Weak Password';
        text.className = 'text-danger';
    } else if (score < 100) {
        bar.className = 'progress-bar bg-warning';
        text.innerText = 'Moderate Password';
        text.className = 'text-warning';
    } else {
        bar.className = 'progress-bar bg-success';
        text.innerText = 'Strong Password ✓';
        text.className = 'text-success';
    }
}
</script>

<?php include 'includes/footer.php'; ?>
