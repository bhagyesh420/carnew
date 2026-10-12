<?php
$page_title = 'Admin Sign In - DriveRent Panel';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (is_admin()) {
    header('Location: ' . base_url('admin/index.php'));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin'");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = 'admin';

            flash_message('success', 'Admin session authenticated.');
            header('Location: ' . base_url('admin/index.php'));
            exit();
        } else {
            $error = 'Invalid administrator credentials.';
        }
    } catch (PDOException $e) {
        $error = 'Database error authenticating admin.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - DriveRent</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="<?php echo base_url('assets/css/admin.css'); ?>">
</head>
<body class="admin-body d-flex align-items-center justify-content-center min-vh-100 p-3">
    <div class="admin-table-card p-4 p-md-5 shadow-lg" style="max-width: 440px; width: 100%;">
        <div class="text-center mb-4">
            <div class="d-inline-flex align-items-center justify-content-center bg-primary bg-opacity-20 text-accent p-3 rounded-circle mb-3 border border-primary border-opacity-30">
                <i class="fas fa-user-shield fa-2x"></i>
            </div>
            <h3 class="fw-bold text-white mb-1">Drive<span class="text-accent">Rent</span> <span class="badge bg-danger text-white fs-7 ms-1">ADMIN</span></h3>
            <p class="text-slate-400 small">Sign in to access management console</p>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger small p-3 mb-4 rounded-3 text-center"><?php echo $error; ?></div>
        <?php endif; ?>

        <form action="<?php echo base_url('admin/login.php'); ?>" method="POST">
            <div class="mb-4">
                <label class="form-label text-slate-300 fw-bold">Admin Email</label>
                <div class="input-group">
                    <span class="input-group-text bg-slate-900 border-slate-700 text-slate-400"><i class="fas fa-envelope"></i></span>
                    <input type="email" name="email" class="form-control" value="admin@driverent.com" required>
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label text-slate-300 fw-bold">Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-slate-900 border-slate-700 text-slate-400"><i class="fas fa-lock"></i></span>
                    <input type="password" name="password" class="form-control" value="admin123" required>
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-100 py-3 rounded-pill fw-bold shadow"><i class="fas fa-key me-2"></i>Authenticate Panel</button>
        </form>
    </div>
</body>
</html>
