<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

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
    $redirect = !empty($_SESSION['redirect_after_login']) ? $_SESSION['redirect_after_login'] : base_url('index.php');
    unset($_SESSION['redirect_after_login']);
    header('Location: ' . $redirect);
    exit();
}

$error_msg = null;
$csrf_token = generate_csrf_token();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Check
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error_msg = 'Security token expired. Please reload and try again.';
    } else {
        $name = sanitize($_POST['name']);
        $email = strtolower(trim($_POST['email']));
        $phone = sanitize($_POST['phone']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $terms = isset($_POST['terms']);

        // Profile Details
        $dob = sanitize($_POST['dob'] ?? '');
        $gender = sanitize($_POST['gender'] ?? '');
        $address = sanitize($_POST['address'] ?? '');
        $city = sanitize($_POST['city'] ?? 'Surat');
        $state = sanitize($_POST['state'] ?? 'Gujarat');
        $pincode = sanitize($_POST['pincode'] ?? '');
        $driving_license = sanitize($_POST['driving_license'] ?? '');
        $license_expiry = sanitize($_POST['license_expiry'] ?? '');
        $bio = sanitize($_POST['bio'] ?? '');

        if (!$terms) {
            $error_msg = 'You must agree to the Terms & Conditions to register.';
        } elseif ($password !== $confirm_password) {
            $error_msg = 'Passwords do not match. Please verify and try again.';
        } else {
            // Password Strength Check
            $pass_check = validate_password_strength($password);
            if (!$pass_check['valid']) {
                $error_msg = $pass_check['message'];
            } else {
                try {
                    // Check if email or mobile exists
                    $check_stmt = $pdo->prepare("SELECT id, email, phone FROM users WHERE email = ? OR phone = ?");
                    $check_stmt->execute([$email, $phone]);
                    $existing = $check_stmt->fetch();

                    if ($existing) {
                        if ($existing['email'] === $email) {
                            $error_msg = 'An account with this email address already exists.';
                        } else {
                            $error_msg = 'An account with this mobile phone number already exists.';
                        }
                    } else {
                        // Handle Profile Image Upload
                        $profile_image = 'default_avatar.png';
                        if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
                            $upload_result = upload_profile_photo($_FILES['profile_photo']);
                            if ($upload_result['status']) {
                                $profile_image = $upload_result['filename'];
                            }
                        }

                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("INSERT INTO users (name, email, phone, password, role, profile_image, created_at) VALUES (?, ?, ?, ?, 'customer', ?, CURRENT_TIMESTAMP)");
                        $stmt->execute([$name, $email, $phone, $hashed_password, $profile_image]);

                        $new_id = $pdo->lastInsertId();

                        // Save complete user_profiles details
                        $p_stmt = $pdo->prepare("INSERT INTO user_profiles (user_id, dob, gender, address, city, state, pincode, driving_license, license_expiry, bio, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)");
                        $p_stmt->execute([$new_id, $dob, $gender, $address, $city, $state, $pincode, $driving_license, $license_expiry, $bio]);

                        // Create welcome notification
                        create_notification($pdo, $new_id, 'Welcome to DriveRent!', 'Your account and driver profile have been created successfully.', 'success');

                        // Set session
                        $_SESSION['user_id'] = $new_id;
                        $_SESSION['user_name'] = $name;
                        $_SESSION['user_email'] = $email;
                        $_SESSION['user_role'] = 'customer';
                        $_SESSION['last_activity'] = time();

                        flash_message('success', 'Account & profile created successfully! Welcome to DriveRent, ' . htmlspecialchars($name) . '.');
                        $redirect = !empty($_SESSION['redirect_after_login']) ? $_SESSION['redirect_after_login'] : base_url('index.php');
                        unset($_SESSION['redirect_after_login']);
                        header('Location: ' . $redirect);
                        exit();
                    }
                } catch (PDOException $e) {
                    $error_msg = 'Database error occurred while creating your account.';
                }
            }
        }
    }
}

$page_title = 'Complete Registration & Driver Profile - DriveRent';
include 'includes/header.php';
include 'includes/navbar.php';
?>

<section class="py-5 bg-primary mt-4 min-vh-100 d-flex align-items-center">
    <div class="container py-3">
        <div class="row justify-content-center">
            <div class="col-lg-7 col-md-9">
                <?php render_flash_messages(); ?>

                <div class="glass-card p-4 p-md-4 shadow-lg border-secondary" style="border-radius: 20px;">
                    <!-- Header -->
                    <div class="text-center mb-3">
                        <div class="brand-logo-icon mx-auto mb-2" style="width: 48px; height: 48px; font-size: 1.35rem;">
                            <i class="fas fa-car-side"></i>
                        </div>
                        <h3 class="fw-bold text-light mb-1">Create Your <span class="text-accent">DriveRent</span> Account</h3>
                        <p class="text-secondary small mb-0">Join DriveRent for executive luxury car rentals across Surat City</p>
                    </div>

                    <?php if (isset($intent_car) && $intent_car): ?>
                        <!-- Booking in Progress Preview Box -->
                        <div class="p-3 mb-3 rounded-3 border" style="background: rgba(14, 165, 233, 0.08); border-color: rgba(14, 165, 233, 0.3) !important;">
                            <div class="d-flex align-items-center gap-3">
                                <img src="<?php echo get_car_image_url($intent_car['image']); ?>" alt="<?php echo htmlspecialchars($intent_car['brand'] . ' ' . $intent_car['model']); ?>" style="width: 68px; height: 44px; object-fit: cover; border-radius: 8px;" class="border border-slate-700">
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="badge bg-slate-800 text-cyan border border-slate-700" style="font-size: 0.68rem;"><i class="fas fa-user-plus me-1"></i>Register to Book</span>
                                        <span class="text-white fw-bold small">₹<?php echo number_format($intent_car['price_per_day']); ?>/day</span>
                                    </div>
                                    <h6 class="text-white fw-bold mb-0 mt-0.5" style="font-size: 0.9rem;"><?php echo htmlspecialchars($intent_car['brand'] . ' ' . $intent_car['model']); ?></h6>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($error_msg)): ?>
                        <div class="alert alert-danger border-0 shadow-sm mb-3 py-2 px-3 small" id="serverAlert">
                            <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error_msg; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Wizard Stepper Indicator -->
                    <div class="reg-stepper mb-3">
                        <div class="reg-step-item active" id="stepDot1" onclick="switchStep(1)">
                            <div class="reg-step-dot"><i class="fas fa-user-lock"></i></div>
                            <div class="reg-step-title">1. Account</div>
                        </div>
                        <div class="reg-step-item" id="stepDot2" onclick="switchStep(2)">
                            <div class="reg-step-dot"><i class="fas fa-id-card"></i></div>
                            <div class="reg-step-title">2. Driving License</div>
                        </div>
                        <div class="reg-step-item" id="stepDot3" onclick="switchStep(3)">
                            <div class="reg-step-dot"><i class="fas fa-map-marker-alt"></i></div>
                            <div class="reg-step-title">3. Address & Photo</div>
                        </div>
                    </div>

                    <form action="<?php echo base_url('register.php'); ?>" method="POST" enctype="multipart/form-data" id="registerForm">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                        <!-- STEP 1: Account Credentials & Contact -->
                        <div class="reg-step-pane active" id="stepPane1">
                            <div class="mb-3">
                                <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" name="name" id="reg_name" class="form-control" placeholder="e.g. Bhargav Mungra" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" required>
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Email Address <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                        <input type="email" name="email" id="reg_email" class="form-control" placeholder="name@example.com" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Mobile Phone <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                        <input type="tel" name="phone" id="reg_phone" class="form-control" placeholder="+91 98765 43210" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Password <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                        <input type="password" name="password" id="reg_password" class="form-control" placeholder="At least 8 characters" required onkeyup="checkPasswordStrength(this.value); checkPasswordMatch();">
                                        <button class="btn btn-toggle-eye" type="button" onclick="togglePassVisibility('reg_password', this)" tabindex="-1"><i class="fas fa-eye"></i></button>
                                    </div>
                                    <div class="progress mt-2" style="height: 5px;">
                                        <div id="pass_strength_bar" class="progress-bar bg-danger" role="progressbar" style="width: 0%"></div>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mt-1">
                                        <small id="pass_strength_text" class="text-secondary" style="font-size: 0.73rem;">8+ chars, upper, lower & numbers</small>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Confirm Password <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-shield-alt"></i></span>
                                        <input type="password" name="confirm_password" id="reg_confirm_password" class="form-control" placeholder="Re-enter password" required onkeyup="checkPasswordMatch()">
                                        <button class="btn btn-toggle-eye" type="button" onclick="togglePassVisibility('reg_confirm_password', this)" tabindex="-1"><i class="fas fa-eye"></i></button>
                                    </div>
                                    <div class="mt-2" style="height: 5px;"></div>
                                    <div class="mt-1">
                                        <small id="pass_match_text" class="text-secondary" style="font-size: 0.73rem;"><i class="fas fa-check-circle me-1 opacity-50"></i>Must match password</small>
                                    </div>
                                </div>
                            </div>

                            <!-- Terms Checkbox -->
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" name="terms" id="termsCheck" required <?php echo isset($_POST['terms']) ? 'checked' : ''; ?>>
                                <label class="form-check-label text-secondary small" for="termsCheck">
                                    I agree to DriveRent's <a href="<?php echo base_url('terms.php'); ?>" target="_blank" class="text-accent text-decoration-none fw-semibold">Terms & Conditions</a> & <a href="<?php echo base_url('privacy.php'); ?>" target="_blank" class="text-accent text-decoration-none fw-semibold">Privacy Policy</a>.
                                </label>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="button" class="btn btn-gradient py-2.5 fw-bold shadow-cyan" onclick="validateAndGoToStep(2)">
                                    Continue to Driver License <i class="fas fa-arrow-right ms-2"></i>
                                </button>
                                <button type="button" class="btn btn-outline-secondary py-2 text-secondary small" onclick="submitQuickRegister()">
                                    <i class="fas fa-bolt text-warning me-1"></i>Quick Register with Account Info Only
                                </button>
                            </div>
                        </div>

                        <!-- STEP 2: Driving License & Identity (Optional) -->
                        <div class="reg-step-pane" id="stepPane2">
                            <div class="d-flex align-items-center justify-content-between p-2 mb-3 rounded-2" style="background: rgba(14, 165, 233, 0.08); border: 1px solid rgba(14, 165, 233, 0.2);">
                                <span class="small text-cyan"><i class="fas fa-info-circle me-1"></i>Optional: You can provide driver details now or in your profile later.</span>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Driving License Number</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                                        <input type="text" name="driving_license" id="reg_dl" class="form-control text-uppercase" placeholder="e.g. GJ-05-2018-1234567" value="<?php echo isset($_POST['driving_license']) ? htmlspecialchars($_POST['driving_license']) : ''; ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">License Expiry Date</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                                        <input type="date" name="license_expiry" id="reg_dl_expiry" class="form-control" value="<?php echo isset($_POST['license_expiry']) ? htmlspecialchars($_POST['license_expiry']) : ''; ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label">Date of Birth</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-birthday-cake"></i></span>
                                        <input type="date" name="dob" id="reg_dob" class="form-control" value="<?php echo isset($_POST['dob']) ? htmlspecialchars($_POST['dob']) : ''; ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Gender</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-venus-mars"></i></span>
                                        <select name="gender" class="form-select">
                                            <option value="">Select Gender</option>
                                            <option value="Male" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'Male') ? 'selected' : ''; ?>>Male</option>
                                            <option value="Female" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'Female') ? 'selected' : ''; ?>>Female</option>
                                            <option value="Other" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'Other') ? 'selected' : ''; ?>>Other</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex gap-2 mb-2">
                                <button type="button" class="btn btn-outline-secondary px-3" onclick="switchStep(1)">
                                    <i class="fas fa-arrow-left me-1"></i>Back
                                </button>
                                <button type="button" class="btn btn-gradient flex-grow-1 fw-bold shadow-cyan" onclick="switchStep(3)">
                                    Continue to Address <i class="fas fa-arrow-right ms-2"></i>
                                </button>
                            </div>
                            <div class="text-center mt-2">
                                <button type="button" class="btn btn-link text-secondary text-decoration-none small" onclick="submitQuickRegister()">
                                    Skip this step & Complete Registration →
                                </button>
                            </div>
                        </div>

                        <!-- STEP 3: Delivery Address & Photo (Optional) -->
                        <div class="reg-step-pane" id="stepPane3">
                            <div class="d-flex align-items-center justify-content-between p-2 mb-3 rounded-2" style="background: rgba(14, 165, 233, 0.08); border: 1px solid rgba(14, 165, 233, 0.2);">
                                <span class="small text-cyan"><i class="fas fa-map-marker-alt me-1"></i>Optional: Your delivery address for doorstep vehicle handover in Surat.</span>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Street Address</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-home"></i></span>
                                    <input type="text" name="address" class="form-control" placeholder="House/Flat No., Road, Area (e.g. Vesu, Piplod)..." value="<?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?>">
                                </div>
                            </div>

                            <div class="row g-2 mb-3">
                                <div class="col-md-4">
                                    <label class="form-label">City</label>
                                    <input type="text" name="city" class="form-control" value="<?php echo isset($_POST['city']) ? htmlspecialchars($_POST['city']) : 'Surat'; ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">State</label>
                                    <input type="text" name="state" class="form-control" value="<?php echo isset($_POST['state']) ? htmlspecialchars($_POST['state']) : 'Gujarat'; ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Pincode</label>
                                    <input type="text" name="pincode" class="form-control" placeholder="395007" value="<?php echo isset($_POST['pincode']) ? htmlspecialchars($_POST['pincode']) : ''; ?>">
                                </div>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label">Profile Photo</label>
                                    <input type="file" name="profile_photo" class="form-control" accept="image/png, image/jpeg, image/webp">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Car Preference / Notes</label>
                                    <input type="text" name="bio" class="form-control" placeholder="e.g. Prefer luxury SUVs, automatic transmission..." value="<?php echo isset($_POST['bio']) ? htmlspecialchars($_POST['bio']) : ''; ?>">
                                </div>
                            </div>

                            <div class="d-flex gap-2 mb-2">
                                <button type="button" class="btn btn-outline-secondary px-3" onclick="switchStep(2)">
                                    <i class="fas fa-arrow-left me-1"></i>Back
                                </button>
                                <button type="submit" class="btn btn-gradient flex-grow-1 py-2.5 fw-bold shadow-cyan">
                                    <i class="fas fa-user-check me-2"></i>Complete Registration
                                </button>
                            </div>
                        </div>

                        <div class="text-center text-secondary small mt-3">
                            Already have an account? <a href="<?php echo base_url('login.php' . (!empty($_SESSION['redirect_after_login']) ? '?redirect=' . urlencode($_SESSION['redirect_after_login']) : '')); ?>" class="text-accent fw-bold text-decoration-none">Sign In Here</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
let currentStep = 1;

function switchStep(step) {
    if (step > currentStep) {
        if (!validateStep(currentStep)) return;
    }
    
    // Update panes
    document.querySelectorAll('.reg-step-pane').forEach((pane, idx) => {
        pane.classList.toggle('active', (idx + 1) === step);
    });

    // Update dots
    for (let i = 1; i <= 3; i++) {
        const dot = document.getElementById('stepDot' + i);
        if (i === step) {
            dot.className = 'reg-step-item active';
        } else if (i < step) {
            dot.className = 'reg-step-item completed';
        } else {
            dot.className = 'reg-step-item';
        }
    }

    currentStep = step;
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function validateAndGoToStep(nextStep) {
    if (validateStep(currentStep)) {
        switchStep(nextStep);
    }
}

function validateStep(step) {
    if (step === 1) {
        const name = document.getElementById('reg_name').value.trim();
        const email = document.getElementById('reg_email').value.trim();
        const phone = document.getElementById('reg_phone').value.trim();
        const pass = document.getElementById('reg_password').value;
        const confirmPass = document.getElementById('reg_confirm_password').value;
        const terms = document.getElementById('termsCheck').checked;

        if (!name) {
            alert('Please enter your full name.');
            document.getElementById('reg_name').focus();
            return false;
        }
        if (!email || !email.includes('@') || !email.includes('.')) {
            alert('Please enter a valid email address.');
            document.getElementById('reg_email').focus();
            return false;
        }
        if (!phone || phone.length < 10) {
            alert('Please enter a valid mobile phone number (at least 10 digits).');
            document.getElementById('reg_phone').focus();
            return false;
        }
        if (!pass || pass.length < 8) {
            alert('Password must be at least 8 characters long.');
            document.getElementById('reg_password').focus();
            return false;
        }
        if (pass !== confirmPass) {
            alert('Passwords do not match. Please verify and try again.');
            document.getElementById('reg_confirm_password').focus();
            return false;
        }
        if (!terms) {
            alert('Please agree to the Terms & Conditions and Privacy Policy to proceed.');
            document.getElementById('termsCheck').focus();
            return false;
        }
    }
    return true;
}

function submitQuickRegister() {
    if (validateStep(1)) {
        document.getElementById('registerForm').submit();
    }
}

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

    if (score === 0) {
        bar.className = 'progress-bar bg-danger';
        text.innerText = '8+ chars, upper, lower & numbers';
        text.className = 'text-secondary';
    } else if (score < 50) {
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

function checkPasswordMatch() {
    const pass = document.getElementById('reg_password').value;
    const confirmPass = document.getElementById('reg_confirm_password').value;
    const matchText = document.getElementById('pass_match_text');

    if (!confirmPass) {
        matchText.innerHTML = '<i class="fas fa-shield-alt me-1 opacity-50"></i>Must match password';
        matchText.className = 'text-secondary';
    } else if (pass === confirmPass) {
        matchText.innerHTML = '<i class="fas fa-check-circle me-1 text-success"></i>Passwords match ✓';
        matchText.className = 'text-success';
    } else {
        matchText.innerHTML = '<i class="fas fa-times-circle me-1 text-danger"></i>Passwords do not match';
        matchText.className = 'text-danger';
    }
}
</script>

<?php include 'includes/footer.php'; ?>
