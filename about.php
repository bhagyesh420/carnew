<?php
$page_title = 'About Us - DriveRent Surat Brand Story';
$meta_description = 'Learn about DriveRent journey, mission, values, and executive automotive fleet services exclusively across Surat City.';
include 'includes/header.php';
include 'includes/navbar.php';
?>

<!-- Banner Header -->
<section class="bg-secondary-dark border-bottom border-dark-subtle page-header-section">
    <div class="container text-center">
        <div class="luxury-header-card p-3 p-md-4 p-xl-4 text-center">
            <h1 class="display-6 fw-bold mb-2 text-white">Redefining Car Rentals in Surat</h1>
            <p class="text-slate-400 max-w-600 mx-auto mb-0 small">DriveRent delivers premium luxury self-drive vehicles across all neighborhoods in Surat City.</p>
        </div>
    </div>
</section>

<!-- Story Section -->
<section class="py-5 bg-primary">
    <div class="container py-4">
        <div class="row align-items-center g-5 mb-5">
            <div class="col-lg-6">
                <h2 class="section-title">The DriveRent Surat Story</h2>
                <p class="text-secondary leading-relaxed mb-4">Founded with a vision to eliminate tedious paperwork and hidden fees, DriveRent pioneered Surat's premium self-drive luxury rental experience. Whether for corporate delegations, weddings, airport transfers, or local travel in Surat, our fleet guarantees perfection on every kilometer.</p>
                <div class="row g-3">
                    <div class="col-6">
                        <div class="glass-card p-3">
                            <h3 class="fw-bold text-accent mb-1">100%</h3>
                            <small class="text-secondary">Surat Areas Covered</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="glass-card p-3">
                            <h3 class="fw-bold text-accent mb-1">24/7</h3>
                            <small class="text-secondary">Surat Doorstep Delivery</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="glass-card p-4">
                    <img src="<?php echo base_url('assets/images/hero/hero_car.jpg'); ?>" alt="DriveRent Experience" class="img-fluid rounded" loading="lazy" decoding="async">
                </div>
            </div>
        </div>

        <!-- Mission & Values -->
        <div class="row g-4 mb-5">
            <div class="col-md-4 reveal">
                <div class="feature-box">
                    <div class="feature-icon-wrapper"><i class="fas fa-bullseye"></i></div>
                    <h4 class="fw-bold mb-2">Our Mission</h4>
                    <p class="text-secondary">To provide effortless access to high-end automotive engineering with transparent pricing and zero compromise on safety across Surat.</p>
                </div>
            </div>
            <div class="col-md-4 reveal">
                <div class="feature-box">
                    <div class="feature-icon-wrapper"><i class="fas fa-eye"></i></div>
                    <h4 class="fw-bold mb-2">Our Vision</h4>
                    <p class="text-secondary">To be Surat's most admired, trusted, and reliable executive mobility service powered by luxury fleets.</p>
                </div>
            </div>
            <div class="col-md-4 reveal">
                <div class="feature-box">
                    <div class="feature-icon-wrapper"><i class="fas fa-heart"></i></div>
                    <h4 class="fw-bold mb-2">Customer First</h4>
                    <p class="text-secondary">24/7 dedicated concierge assistance ensuring every key handover is smooth, reliable, and memorable.</p>
                </div>
            </div>
        </div>

        <!-- Interactive Animated Timeline -->
        <div class="text-center mb-5 pt-4">
            <h2 class="section-title">DriveRent Evolution Timeline</h2>
        </div>

        <div class="row g-4 justify-content-center">
            <div class="col-lg-3 col-md-6 reveal">
                <div class="glass-card p-4 text-center h-100">
                    <div class="display-6 fw-bold text-accent mb-2">2020</div>
                    <h5 class="fw-bold mb-2">Surat Inception</h5>
                    <p class="text-secondary small mb-0">Launched with 20 executive sedans across Vesu and Piplod hubs.</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 reveal">
                <div class="glass-card p-4 text-center h-100">
                    <div class="display-6 fw-bold text-accent mb-2">2022</div>
                    <h5 class="fw-bold mb-2">All-Surat Coverage</h5>
                    <p class="text-secondary small mb-0">Expanded across all 18+ municipal zones including Adajan, Airport & Station.</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 reveal">
                <div class="glass-card p-4 text-center h-100">
                    <div class="display-6 fw-bold text-accent mb-2">2024</div>
                    <h5 class="fw-bold mb-2">Luxury & Supercars</h5>
                    <p class="text-secondary small mb-0">Introduced Porsche, Range Rover, and AMG sports lineup for Surat clients.</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 reveal">
                <div class="glass-card p-4 text-center h-100">
                    <div class="display-6 fw-bold text-accent mb-2">2026</div>
                    <h5 class="fw-bold mb-2">Surat's #1 Car Rental</h5>
                    <p class="text-secondary small mb-0">Proudly serving over 10,000 satisfied Surat drivers and business leaders.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
