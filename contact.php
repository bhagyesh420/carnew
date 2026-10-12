<?php
$page_title = 'Contact Us - DriveRent Customer Support';
include 'includes/header.php';
include 'includes/navbar.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $subject = sanitize($_POST['subject']);
    $message = sanitize($_POST['message']);

    try {
        $stmt = $pdo->prepare("INSERT INTO contacts (name, email, phone, subject, message) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $email, $phone, $subject, $message]);
        
        flash_message('success', 'Thank you! Your message has been received. Our concierge team will contact you shortly.');
        header('Location: ' . base_url('contact.php'));
        exit();
    } catch (PDOException $e) {
        $error_msg = 'Database error submitting message.';
    }
}
?>

<section class="bg-secondary-dark border-bottom border-dark-subtle page-header-section">
    <div class="container text-center">
        <div class="luxury-header-card p-3 p-md-4 p-xl-4 text-center">
            <h1 class="display-6 fw-bold mb-2 text-white">Get in Touch With DriveRent Surat</h1>
            <p class="text-slate-400 max-w-600 mx-auto mb-0 small">Have questions regarding Surat doorstep delivery, long-term rentals, or corporate bookings? Our local team is available 24/7.</p>
        </div>
    </div>
</section>

<section class="py-5 bg-primary">
    <div class="container py-4">
        <div class="row g-5">
            <!-- Contact Info -->
            <div class="col-lg-5">
                <h3 class="fw-bold mb-4">Surat Office Information</h3>
                
                <div class="d-flex align-items-start gap-3 mb-4">
                    <div class="contact-icon-circle"><i class="fas fa-map-marker-alt"></i></div>
                    <div>
                        <h6 class="fw-bold text-light mb-1">Surat Hub Location</h6>
                        <p class="text-secondary small mb-0">DriveRent Hub, Vesu Main Road, Near VR Mall, Surat, Gujarat 395007, India</p>
                    </div>
                </div>

                <div class="d-flex align-items-start gap-3 mb-4">
                    <div class="contact-icon-circle"><i class="fas fa-phone-alt"></i></div>
                    <div>
                        <h6 class="fw-bold text-light mb-1">Surat Helpline & Hotline</h6>
                        <p class="text-secondary small mb-0">+91 98765 43210 (Mobile & WhatsApp)<br>+91 0261 2890000 (Surat Landline)</p>
                    </div>
                </div>

                <div class="d-flex align-items-start gap-3 mb-4">
                    <div class="contact-icon-circle"><i class="fas fa-envelope"></i></div>
                    <div>
                        <h6 class="fw-bold text-light mb-1">Email Inquiry</h6>
                        <p class="text-secondary small mb-0">surat@driverent.com<br>support@driverent.com</p>
                    </div>
                </div>

                <div class="d-flex align-items-start gap-3">
                    <div class="contact-icon-circle"><i class="fas fa-clock"></i></div>
                    <div>
                        <h6 class="fw-bold text-light mb-1">Surat Doorstep Service Hours</h6>
                        <p class="text-secondary small mb-0">24 Hours a Day, 7 Days a Week<br>Instant delivery across all areas of Surat</p>
                    </div>
                </div>
            </div>

            <!-- Contact Form -->
            <div class="col-lg-7">
                <?php render_flash_messages(); ?>
                <?php if (isset($error_msg)): ?>
                    <div class="alert alert-danger mb-4"><?php echo $error_msg; ?></div>
                <?php endif; ?>

                <div class="glass-card p-4 p-md-5">
                    <h4 class="fw-bold mb-4"><i class="fas fa-paper-plane text-accent me-2"></i>Send Us a Message</h4>

                    <form action="<?php echo base_url('contact.php'); ?>" method="POST">
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label text-secondary small fw-semibold">Your Name</label>
                                <input type="text" name="name" class="form-control bg-secondary text-light border-secondary" placeholder="e.g. David Miller" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-secondary small fw-semibold">Email Address</label>
                                <input type="email" name="email" class="form-control bg-secondary text-light border-secondary" placeholder="david@example.com" required>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label text-secondary small fw-semibold">Phone Number</label>
                                <input type="text" name="phone" class="form-control bg-secondary text-light border-secondary" placeholder="+91 99887 76655" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-secondary small fw-semibold">Subject</label>
                                <input type="text" name="subject" class="form-control bg-secondary text-light border-secondary" placeholder="e.g. Corporate Inquiry" required>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label text-secondary small fw-semibold">Message</label>
                            <textarea name="message" rows="5" class="form-control bg-secondary text-light border-secondary" placeholder="How can our concierge team assist you?" required></textarea>
                        </div>

                        <button type="submit" class="btn btn-gradient px-5 py-3 fw-bold"><i class="fas fa-paper-plane me-2"></i>Send Message</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
