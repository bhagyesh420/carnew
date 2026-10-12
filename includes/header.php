<?php
// DriveRent - Public Header Component
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

$page_title = isset($page_title) ? $page_title . ' - DriveRent' : 'DriveRent - Premium Car Rental | Your Journey. Your Car. Your Freedom.';
$meta_description = isset($meta_description) ? $meta_description : 'Experience executive car rentals with DriveRent. Rent premium SUVs, sports cars, and luxury sedans with transparent pricing and 24/7 support.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo htmlspecialchars($meta_description); ?>">
    <meta name="theme-color" content="#0B0F19">
    
    <!-- Open Graph SEO -->
    <meta property="og:title" content="<?php echo htmlspecialchars($page_title); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($meta_description); ?>">
    <meta property="og:type" content="website">
    
    <title><?php echo htmlspecialchars($page_title); ?></title>

    <!-- DNS Preconnect Hints -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>

    <!-- Google Fonts: Plus Jakarta Sans, Outfit & Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Outfit:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5.3+ CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo base_url('assets/css/style.css?v=' . filemtime(__DIR__ . '/../assets/css/style.css')); ?>">
    <link rel="stylesheet" href="<?php echo base_url('assets/css/responsive.css?v=' . filemtime(__DIR__ . '/../assets/css/responsive.css')); ?>">

    <?php if (basename($_SERVER['PHP_SELF']) === 'index.php'): ?>
    <!-- Preload Critical Hero LCP Image -->
    <link rel="preload" as="image" href="<?php echo base_url('assets/images/hero/hero_car.jpg'); ?>" fetchpriority="high">
    <?php endif; ?>
</head>
<body>
    <!-- Preloader Animation -->
    <div id="preloader">
        <div class="loader-content text-center">
            <div class="loader-car">
                <i class="fas fa-car-side fa-3x text-gradient mb-2"></i>
            </div>
            <div class="loader-pulse"></div>
            <h5 class="fw-bold tracking-wider mt-3">DRIVE<span class="text-accent">RENT</span></h5>
        </div>
    </div>
