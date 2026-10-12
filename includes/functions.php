<?php
// DriveRent - Core Helper Functions

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
ob_start();

/**
 * Returns dynamic Base URL for clean path resolution across environments
 */
function base_url($path = '') {
    // Detect script path directory relative to web root
    $scriptName = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    // Clean up path if in root or subfolder
    $scriptDir = rtrim($scriptName, '/');
    
    // Remove /admin or /config or /includes if present in script execution path
    $base = preg_replace('#/(admin|includes|config)$#', '', $scriptDir);
    
    $cleanPath = ltrim($path, '/');
    return $base === '' || $base === '/' ? '/' . $cleanPath : $base . '/' . $cleanPath;
}

/**
 * Input Data Sanitization
 */
function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Currency Formatter
 */
function format_currency($amount) {
    return '₹' . number_format((float)$amount, 2);
}

/**
 * Check if user is logged in
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if user is administrator
 */
function is_admin() {
    return is_logged_in() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Get current logged in user details
 */
function get_logged_user($pdo) {
    if (!is_logged_in()) {
        return null;
    }
    $stmt = $pdo->prepare("SELECT id, name, email, phone, role, profile_image, created_at FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

/**
 * Get or create User Profile Extended Data
 */
function get_user_profile($pdo, $user_id) {
    if (empty($user_id)) {
        return [
            'dob' => null,
            'gender' => null,
            'address' => null,
            'city' => 'Surat',
            'state' => 'Gujarat',
            'pincode' => null,
            'driving_license' => null,
            'license_expiry' => null,
            'bio' => null
        ];
    }
    try {
        $stmt = $pdo->prepare("SELECT * FROM user_profiles WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $profile = $stmt->fetch();

        if (!$profile) {
            // Create blank profile row if not existing
            $insert = $pdo->prepare("INSERT INTO user_profiles (user_id) VALUES (?)");
            $insert->execute([$user_id]);

            $stmt->execute([$user_id]);
            $profile = $stmt->fetch();
        }
        return $profile ? $profile : [
            'dob' => null,
            'gender' => null,
            'address' => null,
            'city' => 'Surat',
            'state' => 'Gujarat',
            'pincode' => null,
            'driving_license' => null,
            'license_expiry' => null,
            'bio' => null
        ];
    } catch (Exception $e) {
        return [
            'dob' => null,
            'gender' => null,
            'address' => null,
            'city' => 'Surat',
            'state' => 'Gujarat',
            'pincode' => null,
            'driving_license' => null,
            'license_expiry' => null,
            'bio' => null
        ];
    }
}

/**
 * Calculate Profile Completion Percentage
 */
function calculate_profile_completion($user, $profile) {
    $fields = [
        'name' => !empty($user['name']),
        'email' => !empty($user['email']),
        'phone' => !empty($user['phone']),
        'profile_image' => !empty($user['profile_image']) && $user['profile_image'] !== 'default_avatar.png',
        'dob' => !empty($profile['dob']),
        'gender' => !empty($profile['gender']),
        'address' => !empty($profile['address']),
        'city' => !empty($profile['city']),
        'pincode' => !empty($profile['pincode']),
        'driving_license' => !empty($profile['driving_license']),
    ];

    $completed = 0;
    $total = count($fields);
    foreach ($fields as $is_filled) {
        if ($is_filled) $completed++;
    }

    return round(($completed / $total) * 100);
}

/**
 * Strong Password Strength Validation
 */
function validate_password_strength($password) {
    if (strlen($password) < 8) {
        return ['valid' => false, 'message' => 'Password must be at least 8 characters long.'];
    }
    if (!preg_match('/[A-Z]/', $password)) {
        return ['valid' => false, 'message' => 'Password must contain at least one uppercase letter.'];
    }
    if (!preg_match('/[a-z]/', $password)) {
        return ['valid' => false, 'message' => 'Password must contain at least one lowercase letter.'];
    }
    if (!preg_match('/[0-9]/', $password)) {
        return ['valid' => false, 'message' => 'Password must contain at least one number.'];
    }
    return ['valid' => true, 'message' => 'Password meets security guidelines.'];
}

/**
 * CSRF Protection Token Helpers
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Notifications Helper
 */
function create_notification($pdo, $user_id, $title, $message, $type = 'info') {
    try {
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type, is_read, created_at) VALUES (?, ?, ?, ?, 0, CURRENT_TIMESTAMP)");
        return $stmt->execute([$user_id, $title, $message, $type]);
    } catch (Exception $e) {
        return false;
    }
}

function get_unread_notifications_count($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$user_id]);
    $res = $stmt->fetch();
    return $res ? (int)$res['total'] : 0;
}

/**
 * Saved Cars / Favorites Toggle
 */
function is_car_saved($pdo, $user_id, $car_id) {
    $stmt = $pdo->prepare("SELECT id FROM saved_cars WHERE user_id = ? AND car_id = ?");
    $stmt->execute([$user_id, $car_id]);
    return (bool)$stmt->fetch();
}

/**
 * Remember Me Cookie Token Verification
 */
function check_remember_token($pdo) {
    if (isset($_COOKIE['driverent_remember']) && !is_logged_in()) {
        $token = $_COOKIE['driverent_remember'];
        $stmt = $pdo->prepare("SELECT s.*, u.name, u.email, u.role FROM sessions s JOIN users u ON s.user_id = u.id WHERE s.token = ? AND s.expires_at > CURRENT_TIMESTAMP");
        $stmt->execute([$token]);
        $session = $stmt->fetch();

        if ($session) {
            $_SESSION['user_id'] = $session['user_id'];
            $_SESSION['user_name'] = $session['name'];
            $_SESSION['user_email'] = $session['email'];
            $_SESSION['user_role'] = $session['role'];
            return true;
        }
    }
    return false;
}

function set_remember_token($pdo, $user_id) {
    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', time() + (86400 * 30)); // 30 Days

    $stmt = $pdo->prepare("INSERT INTO sessions (user_id, token, user_agent, ip_address, expires_at) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$user_id, $token, $_SERVER['HTTP_USER_AGENT'] ?? '', $_SERVER['REMOTE_ADDR'] ?? '', $expires]);

    setcookie('driverent_remember', $token, time() + (86400 * 30), '/', '', false, true);
}

function clear_remember_token($pdo, $user_id) {
    if (isset($_COOKIE['driverent_remember'])) {
        $token = $_COOKIE['driverent_remember'];
        $stmt = $pdo->prepare("DELETE FROM sessions WHERE token = ? OR user_id = ?");
        $stmt->execute([$token, $user_id]);
        setcookie('driverent_remember', '', time() - 3600, '/');
    }
}

/**
 * Secure Profile Photo Upload Handler
 */
function upload_profile_photo($file) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['status' => false, 'message' => 'No file uploaded or upload error.'];
    }

    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
    $max_size = 5 * 1024 * 1024; // 5MB

    $filename = $file['name'];
    $filesize = $file['size'];
    $tmp_name = $file['tmp_name'];

    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

    if (!in_array($ext, $allowed)) {
        return ['status' => false, 'message' => 'Invalid file format. Allowed formats: JPG, PNG, WEBP.'];
    }

    if ($filesize > $max_size) {
        return ['status' => false, 'message' => 'File size exceeds maximum limit of 5MB.'];
    }

    $upload_dir = __DIR__ . '/../assets/images/avatars/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $new_filename = 'avatar_' . uniqid() . '.' . $ext;
    $target_path = $upload_dir . $new_filename;

    if (move_uploaded_file($tmp_name, $target_path)) {
        return ['status' => true, 'filename' => $new_filename];
    } else {
        return ['status' => false, 'message' => 'Failed to save uploaded image file.'];
    }
}

/**
 * Set Session Flash Message
 */
function flash_message($type, $message) {
    $_SESSION['flash_msg'] = [
        'type' => $type, // success, danger, warning, info
        'text' => $message
    ];
}

/**
 * Render Session Flash Messages
 */
function render_flash_messages() {
    if (isset($_SESSION['flash_msg'])) {
        $msg = $_SESSION['flash_msg'];
        unset($_SESSION['flash_msg']);
        $icon = $msg['type'] === 'success' ? 'fa-check-circle' : ($msg['type'] === 'danger' ? 'fa-exclamation-triangle' : 'fa-info-circle');
        echo '<div class="alert alert-' . $msg['type'] . ' alert-dismissible fade show border-0 shadow-lg mb-4" role="alert">
                <i class="fas ' . $icon . ' me-2"></i>' . htmlspecialchars($msg['text']) . '
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>';
    }
}

/**
 * Unique Booking ID Generator
 */
function generate_booking_id() {
    return 'DR-' . date('Y') . '-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));
}

/**
 * Calculate Rental Price Breakdown
 */
function calculate_booking_price($price_per_day, $rental_days) {
    $subtotal = $price_per_day * $rental_days;
    $tax = round($subtotal * 0.18, 2); // 18% GST / Tax
    $security_deposit = 5000.00; // Fixed refundable deposit
    $total_amount = $subtotal + $tax + $security_deposit;

    return [
        'rental_days' => $rental_days,
        'price_per_day' => $price_per_day,
        'subtotal' => $subtotal,
        'tax' => $tax,
        'security_deposit' => $security_deposit,
        'total_amount' => $total_amount
    ];
}

/**
 * Surat City Locations & Major Hubs
 */
function get_surat_locations() {
    return [
        'Surat International Airport (STV)',
        'Vesu',
        'Adajan',
        'Piplod',
        'Surat Railway Station',
        'Ghod Dod Road',
        'Varachha & Katargam',
        'Pal & Althan',
        'Pal',
        'Althan',
        'Varachha',
        'Katargam',
        'City Light',
        'Dumas Road',
        'Ring Road (Textile Market)',
        'Udhna',
        'Majura Gate',
        'Bhatar',
        'Rander',
        'Dindoli'
    ];
}

/**
 * Resolves Car Image URL (supports JPG, PNG, WEBP, and base64 encoded SVG graphics)
 */
function get_car_image_url($image_filename = '') {
    if (empty($image_filename)) {
        $image_filename = 'bmw_5series.jpg';
    }
    $file_path = __DIR__ . '/../assets/images/cars/' . $image_filename;
    if (file_exists($file_path)) {
        $content = file_get_contents($file_path);
        if (strpos(trim($content), '<svg') === 0) {
            return 'data:image/svg+xml;base64,' . base64_encode($content);
        }
        return base_url('assets/images/cars/' . $image_filename);
    }
    return base_url('assets/images/hero/hero_car.jpg');
}

/**
 * Resolves Destination Image URL (supports JPG, PNG, WEBP, and base64 encoded SVG graphics)
 */
function get_destination_image_url($dest_filename = '') {
    if (empty($dest_filename)) {
        $dest_filename = 'surat.jpg';
    }
    $file_path = __DIR__ . '/../assets/images/destinations/' . $dest_filename;
    if (file_exists($file_path)) {
        $content = file_get_contents($file_path);
        if (strpos(trim($content), '<svg') === 0) {
            return 'data:image/svg+xml;base64,' . base64_encode($content);
        }
        return base_url('assets/images/destinations/' . $dest_filename);
    }
    return base_url('assets/images/hero/hero_car.jpg');
}

/**
 * Resolves User Profile Avatar URL (supports uploaded photo, SVG vector, PNG, and base64)
 */
function get_user_avatar_url($user = null) {
    if ($user && !empty($user['profile_image'])) {
        $img = basename($user['profile_image']);
        $candidates = [
            __DIR__ . '/../assets/images/avatars/' . $img,
            __DIR__ . '/../assets/images/' . $img,
        ];
        foreach ($candidates as $file_path) {
            if (file_exists($file_path)) {
                $content = @file_get_contents($file_path);
                if ($content && strpos(trim($content), '<svg') === 0) {
                    return 'data:image/svg+xml;base64,' . base64_encode($content);
                }
                return base_url('assets/images/' . (strpos($file_path, 'avatars') !== false ? 'avatars/' . $img : $img));
            }
        }
    }
    
    // Check default_avatar.svg first for high-res crisp rendering
    $defaultSvg = __DIR__ . '/../assets/images/default_avatar.svg';
    if (file_exists($defaultSvg)) {
        $content = @file_get_contents($defaultSvg);
        if ($content && strpos(trim($content), '<svg') === 0) {
            return 'data:image/svg+xml;base64,' . base64_encode($content);
        }
        return base_url('assets/images/default_avatar.svg');
    }
    
    return base_url('assets/images/default_avatar.png');
}
?>
