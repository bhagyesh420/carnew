<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth-check.php';

$user = get_logged_user($pdo);
if (!$user) {
    header('Location: ' . base_url('login.php'));
    exit();
}
$profile = get_user_profile($pdo, $user['id']);
$completion = calculate_profile_completion($user, $profile);
$csrf_token = generate_csrf_token();
$active_tab = sanitize($_GET['tab'] ?? 'overview');

$error_msg = null;
$success_msg = null;

// Handle Profile Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error_msg = 'Security token expired. Please try again.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'update_profile') {
            $name = sanitize($_POST['name']);
            $phone = sanitize($_POST['phone']);
            $dob = sanitize($_POST['dob']);
            $gender = sanitize($_POST['gender']);
            $address = sanitize($_POST['address']);
            $city = sanitize($_POST['city']);
            $state = sanitize($_POST['state']);
            $pincode = sanitize($_POST['pincode']);
            $driving_license = sanitize($_POST['driving_license']);
            $license_expiry = sanitize($_POST['license_expiry']);
            $bio = sanitize($_POST['bio']);

            try {
                // Profile Photo Upload if attached
                $profile_image = $user['profile_image'];
                if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
                    $upload_result = upload_profile_photo($_FILES['profile_photo']);
                    if ($upload_result['status']) {
                        $profile_image = $upload_result['filename'];
                    } else {
                        $error_msg = $upload_result['message'];
                    }
                }

                if (!$error_msg) {
                    // Update Users table
                    $u_stmt = $pdo->prepare("UPDATE users SET name = ?, phone = ?, profile_image = ? WHERE id = ?");
                    $u_stmt->execute([$name, $phone, $profile_image, $user['id']]);

                    // Update User Profiles table
                    $p_stmt = $pdo->prepare("UPDATE user_profiles SET dob = ?, gender = ?, address = ?, city = ?, state = ?, pincode = ?, driving_license = ?, license_expiry = ?, bio = ?, updated_at = CURRENT_TIMESTAMP WHERE user_id = ?");
                    $p_stmt->execute([$dob, $gender, $address, $city, $state, $pincode, $driving_license, $license_expiry, $bio, $user['id']]);

                    $_SESSION['user_name'] = $name;
                    flash_message('success', 'Profile and driving license details updated successfully.');
                    header('Location: ' . base_url('profile.php?tab=overview'));
                    exit();
                }
            } catch (PDOException $e) {
                $error_msg = 'Database error updating profile details.';
            }
        } elseif ($action === 'change_password') {
            $current_password = $_POST['current_password'];
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];

            // Verify current password
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$user['id']]);
            $current_hash = $stmt->fetch()['password'];

            if (!password_verify($current_password, $current_hash)) {
                $error_msg = 'Incorrect current password.';
                $active_tab = 'password';
            } elseif ($new_password !== $confirm_password) {
                $error_msg = 'New password and confirm password do not match.';
                $active_tab = 'password';
            } else {
                $pass_check = validate_password_strength($new_password);
                if (!$pass_check['valid']) {
                    $error_msg = $pass_check['message'];
                    $active_tab = 'password';
                } else {
                    $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                    $upd = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $upd->execute([$new_hash, $user['id']]);

                    create_notification($pdo, $user['id'], 'Password Changed', 'Your account password was updated successfully.', 'info');

                    flash_message('success', 'Password changed successfully.');
                    header('Location: ' . base_url('profile.php?tab=password'));
                    exit();
                }
            }
        }
    }
}

$page_title = 'User Profile & Settings - DriveRent';
include 'includes/header.php';
include 'includes/navbar.php';
?>

<!-- Header Banner -->
<section class="bg-secondary-dark border-bottom border-dark-subtle page-header-section">
    <div class="container-fluid px-3 px-md-4 px-xl-5" style="max-width: 1600px;">
        <div class="luxury-header-card p-3 p-md-4 p-xl-4">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-4">
                <div class="d-flex align-items-center gap-3 gap-md-4">
                    <img src="<?php echo get_user_avatar_url($user); ?>" alt="Avatar" class="rounded-circle border border-2 border-accent shadow-lg" style="width: 80px; height: 80px; object-fit: cover;" onerror="this.onerror=null; this.src='<?php echo base_url('assets/images/default_avatar.png'); ?>';">
                    <div>
                        <h2 class="fw-bold mb-1 text-white fs-3"><?php echo htmlspecialchars($user['name']); ?></h2>
                        <div class="text-slate-400 mb-0 small d-flex align-items-center gap-2 flex-wrap">
                            <span>Member since <?php echo date('F Y', strtotime($user['created_at'])); ?></span>
                            <span>&bull;</span>
                            <span class="badge bg-dark text-slate-300 font-monospace border border-slate-700 text-nowrap py-1 px-2" style="font-size: 0.78rem;">
                                <i class="fas fa-id-badge text-cyan me-1"></i>ID: #DR-CUST-<?php echo str_pad($user['id'], 4, '0', STR_PAD_LEFT); ?>
                            </span>
                        </div>
                    </div>
                </div>

                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="text-slate-400 small">Profile Completion:</span>
                        <strong class="text-accent small"><?php echo $completion; ?>%</strong>
                    </div>
                    <div class="progress bg-slate-900 rounded-pill" style="width: 180px; height: 8px; border: 1px solid rgba(255,255,255,0.08);">
                        <div class="progress-bar rounded-pill" role="progressbar" style="width: <?php echo $completion; ?>%; background: linear-gradient(90deg, #0EA5E9, #10B981);"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Profile Body with Navigation Tabs -->
<section class="py-4 bg-primary min-vh-100">
    <div class="container-fluid px-3 px-md-4 px-xl-5" style="max-width: 1600px;">
        <?php render_flash_messages(); ?>
        <?php if (isset($error_msg)): ?>
            <div class="alert alert-danger border-0 shadow-sm mb-4"><i class="fas fa-exclamation-triangle me-2"></i><?php echo $error_msg; ?></div>
        <?php endif; ?>

        <!-- Nav Tabs -->
        <ul class="nav nav-pills custom-profile-tabs mb-4 gap-2 border-bottom border-dark-subtle pb-3">
            <li class="nav-item">
                <a class="nav-link btn-pill <?php echo $active_tab === 'overview' ? 'active btn-accent' : 'text-light'; ?>" href="<?php echo base_url('profile.php?tab=overview'); ?>"><i class="fas fa-id-card me-2"></i>Profile Overview</a>
            </li>
            <li class="nav-item">
                <a class="nav-link btn-pill <?php echo $active_tab === 'edit' ? 'active btn-accent' : 'text-light'; ?>" href="<?php echo base_url('profile.php?tab=edit'); ?>"><i class="fas fa-user-pen me-2"></i>Edit Profile</a>
            </li>
            <li class="nav-item">
                <a class="nav-link btn-pill <?php echo $active_tab === 'password' ? 'active btn-accent' : 'text-light'; ?>" href="<?php echo base_url('profile.php?tab=password'); ?>"><i class="fas fa-key me-2"></i>Security & Password</a>
            </li>
            <li class="nav-item">
                <a class="nav-link btn-pill <?php echo $active_tab === 'settings' ? 'active btn-accent' : 'text-light'; ?>" href="<?php echo base_url('profile.php?tab=settings'); ?>"><i class="fas fa-sliders-h me-2"></i>Account Settings</a>
            </li>
        </ul>

        <!-- Tab 1: Overview -->
        <?php if ($active_tab === 'overview'): ?>
            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="glass-card p-4 h-100">
                        <div class="d-flex align-items-center justify-content-between pb-3 mb-3 border-bottom border-dark-subtle">
                            <h5 class="fw-bold mb-0 text-light"><i class="fas fa-user me-2 text-accent"></i>Personal Details</h5>
                            <a href="<?php echo base_url('profile.php?tab=edit'); ?>" class="btn btn-outline-accent btn-sm btn-pill px-3"><i class="fas fa-pencil me-1"></i>Edit Profile</a>
                        </div>
                        <div class="row g-3 text-light">
                            <div class="col-sm-6">
                                <span class="text-secondary small fw-semibold d-block">Full Name</span>
                                <strong class="text-light fs-6"><?php echo htmlspecialchars($user['name']); ?></strong>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-secondary small fw-semibold d-block">Email Address</span>
                                <strong class="text-light fs-6"><?php echo htmlspecialchars($user['email']); ?></strong>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-secondary small fw-semibold d-block">Mobile Phone</span>
                                <strong class="text-light fs-6"><?php echo htmlspecialchars($user['phone']); ?></strong>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-secondary small fw-semibold d-block">Date of Birth</span>
                                <strong class="text-light fs-6"><?php echo !empty($profile['dob']) ? date('d M Y', strtotime($profile['dob'])) : '<span class="text-secondary opacity-75 fw-normal">Not specified</span>'; ?></strong>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-secondary small fw-semibold d-block">Gender</span>
                                <strong class="text-light fs-6"><?php echo !empty($profile['gender']) ? htmlspecialchars($profile['gender']) : '<span class="text-secondary opacity-75 fw-normal">Not specified</span>'; ?></strong>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-secondary small fw-semibold d-block">Bio / Notes</span>
                                <span class="text-light"><?php echo !empty($profile['bio']) ? htmlspecialchars($profile['bio']) : '<span class="text-secondary opacity-75 fw-normal">No bio added</span>'; ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="glass-card p-4 h-100">
                        <div class="d-flex align-items-center justify-content-between pb-3 mb-3 border-bottom border-dark-subtle">
                            <h5 class="fw-bold mb-0 text-light"><i class="fas fa-location-dot me-2 text-accent"></i>Address & License Details</h5>
                            <a href="<?php echo base_url('profile.php?tab=edit'); ?>" class="btn btn-outline-accent btn-sm btn-pill px-3"><i class="fas fa-pencil me-1"></i>Edit Profile</a>
                        </div>
                        <div class="row g-3 text-light">
                            <div class="col-sm-12">
                                <span class="text-secondary small fw-semibold d-block">Street Address</span>
                                <strong class="text-light fs-6"><?php echo !empty($profile['address']) ? htmlspecialchars($profile['address']) : '<span class="text-secondary opacity-75 fw-normal">Not specified</span>'; ?></strong>
                            </div>
                            <div class="col-sm-4">
                                <span class="text-secondary small fw-semibold d-block">City</span>
                                <strong class="text-light fs-6"><?php echo htmlspecialchars($profile['city'] ?? 'Surat'); ?></strong>
                            </div>
                            <div class="col-sm-4">
                                <span class="text-secondary small fw-semibold d-block">State</span>
                                <strong class="text-light fs-6"><?php echo htmlspecialchars($profile['state'] ?? 'Gujarat'); ?></strong>
                            </div>
                            <div class="col-sm-4">
                                <span class="text-secondary small fw-semibold d-block">Pincode</span>
                                <strong class="text-light fs-6"><?php echo !empty($profile['pincode']) ? htmlspecialchars($profile['pincode']) : '<span class="text-secondary opacity-75 fw-normal">Not set</span>'; ?></strong>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-secondary small fw-semibold d-block">Driving License No.</span>
                                <strong class="text-accent fs-6"><?php echo !empty($profile['driving_license']) ? htmlspecialchars($profile['driving_license']) : '<span class="text-secondary opacity-75 fw-normal">Pending Upload</span>'; ?></strong>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-secondary small fw-semibold d-block">License Expiry</span>
                                <strong class="text-light fs-6"><?php echo !empty($profile['license_expiry']) ? date('d M Y', strtotime($profile['license_expiry'])) : '<span class="text-secondary opacity-75 fw-normal">Not specified</span>'; ?></strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        <!-- Tab 2: Edit Profile -->
        <?php elseif ($active_tab === 'edit'): ?>
            <div class="glass-card p-4 p-md-5">
                <h4 class="fw-bold mb-4 text-light"><i class="fas fa-user-pen text-accent me-2"></i>Edit Profile & Identification</h4>

                <form action="<?php echo base_url('profile.php?tab=edit'); ?>" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="action" value="update_profile">

                    <div class="row g-3 mb-4">
                        <div class="col-md-4 text-center border-end border-dark-subtle pe-md-4">
                            <label class="form-label text-secondary small fw-semibold d-block mb-3">Profile Photo</label>
                            <img src="<?php echo $avatar_path; ?>" alt="Avatar" class="rounded-circle border border-2 border-accent mb-3 shadow" style="width: 110px; height: 110px; object-fit: cover;">
                            <input type="file" name="profile_photo" class="form-control form-control-sm bg-secondary text-light border-secondary mb-2" accept="image/png, image/jpeg, image/webp">
                            <small class="text-secondary micro-text">Upload new picture (JPG, PNG, WEBP max 5MB)</small>
                        </div>

                        <div class="col-md-8 ps-md-4">
                            <div class="mb-3">
                                <label class="form-label text-secondary small fw-semibold">Full Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control bg-secondary text-light border-secondary" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label text-secondary small fw-semibold">Email Address (Primary)</label>
                                    <input type="email" class="form-control bg-dark text-light border-secondary" value="<?php echo htmlspecialchars($user['email']); ?>" readonly disabled>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-secondary small fw-semibold">Mobile Phone <span class="text-danger">*</span></label>
                                    <input type="tel" name="phone" class="form-control bg-secondary text-light border-secondary" value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr class="border-secondary my-4">
                    <h5 class="fw-bold text-light mb-3"><i class="fas fa-id-badge text-accent me-2"></i>Personal & License Info</h5>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label text-secondary small fw-semibold">Date of Birth</label>
                            <input type="date" name="dob" class="form-control bg-secondary text-light border-secondary" value="<?php echo htmlspecialchars($profile['dob'] ?? ''); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-secondary small fw-semibold">Gender</label>
                            <select name="gender" class="form-select bg-secondary text-light border-secondary">
                                <option value="">Select Gender</option>
                                <option value="Male" <?php echo ($profile['gender'] ?? '') === 'Male' ? 'selected' : ''; ?>>Male</option>
                                <option value="Female" <?php echo ($profile['gender'] ?? '') === 'Female' ? 'selected' : ''; ?>>Female</option>
                                <option value="Other" <?php echo ($profile['gender'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-secondary small fw-semibold">Driving License No.</label>
                            <input type="text" name="driving_license" class="form-control bg-secondary text-light border-secondary text-uppercase" placeholder="e.g. GJ-05-2018-1234567" value="<?php echo htmlspecialchars($profile['driving_license'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label text-secondary small fw-semibold">License Expiry Date</label>
                            <input type="date" name="license_expiry" class="form-control bg-secondary text-light border-secondary" value="<?php echo htmlspecialchars($profile['license_expiry'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-secondary small fw-semibold">Pincode</label>
                            <input type="text" name="pincode" class="form-control bg-secondary text-light border-secondary" placeholder="395007" value="<?php echo htmlspecialchars($profile['pincode'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label text-secondary small fw-semibold">Street Address</label>
                            <textarea name="address" class="form-control bg-secondary text-light border-secondary" rows="2" placeholder="House no, Society, Area..."><?php echo htmlspecialchars($profile['address'] ?? ''); ?></textarea>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label text-secondary small fw-semibold">City</label>
                            <input type="text" name="city" class="form-control bg-secondary text-light border-secondary" value="<?php echo htmlspecialchars($profile['city'] ?? 'Surat'); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label text-secondary small fw-semibold">State</label>
                            <input type="text" name="state" class="form-control bg-secondary text-light border-secondary" value="<?php echo htmlspecialchars($profile['state'] ?? 'Gujarat'); ?>">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label text-secondary small fw-semibold">Bio / Personal Notes</label>
                        <textarea name="bio" class="form-control bg-secondary text-light border-secondary" rows="2" placeholder="Tell us a bit about your rental preferences..."><?php echo htmlspecialchars($profile['bio'] ?? ''); ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-gradient px-5 py-3 fw-bold shadow-cyan"><i class="fas fa-save me-2"></i>Save Profile Changes</button>
                </form>
            </div>

        <!-- Tab 3: Security & Password -->
        <?php elseif ($active_tab === 'password'): ?>
            <div class="glass-card p-4 p-md-5">
                <h4 class="fw-bold mb-4 text-light"><i class="fas fa-shield-halved text-accent me-2"></i>Change Security Password</h4>

                <form action="<?php echo base_url('profile.php?tab=password'); ?>" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="action" value="change_password">

                    <div class="mb-3">
                        <label class="form-label text-secondary small fw-semibold">Current Password <span class="text-danger">*</span></label>
                        <input type="password" name="current_password" class="form-control bg-secondary text-light border-secondary" placeholder="Enter current password" required>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label text-secondary small fw-semibold">New Password <span class="text-danger">*</span></label>
                            <input type="password" name="new_password" id="new_pass" class="form-control bg-secondary text-light border-secondary" placeholder="At least 8 characters" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-secondary small fw-semibold">Confirm New Password <span class="text-danger">*</span></label>
                            <input type="password" name="confirm_password" class="form-control bg-secondary text-light border-secondary" placeholder="Repeat new password" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-gradient px-4 py-3 fw-bold"><i class="fas fa-key me-2"></i>Update Security Password</button>
                </form>
            </div>

        <!-- Tab 4: Account Settings -->
        <?php elseif ($active_tab === 'settings'): ?>
            <div class="glass-card p-4 p-md-5">
                <h4 class="fw-bold mb-4 text-light"><i class="fas fa-sliders-h text-accent me-2"></i>Account & Notification Settings</h4>

                <div class="d-flex flex-column gap-3 mb-4">
                    <div class="p-3 bg-secondary-dark rounded border border-secondary d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="fw-bold text-light mb-1">Email Booking Notifications</h6>
                            <small class="text-secondary">Receive booking confirmation receipts and trip reminders via email.</small>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" checked>
                        </div>
                    </div>

                    <div class="p-3 bg-secondary-dark rounded border border-secondary d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="fw-bold text-light mb-1">SMS & WhatsApp Alerts</h6>
                            <small class="text-secondary">Get instant vehicle dispatch & driver contact details on your phone.</small>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" checked>
                        </div>
                    </div>
                </div>

                <hr class="border-secondary my-4">
                <h5 class="fw-bold text-danger mb-3"><i class="fas fa-user-xmark me-2"></i>Account Actions</h5>
                <p class="text-secondary small mb-3">Signing out will end your current active browser session.</p>
                <a href="<?php echo base_url('logout.php'); ?>" class="btn btn-outline-danger px-4 py-2 btn-pill"><i class="fas fa-sign-out-alt me-2"></i>Sign Out Account</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
