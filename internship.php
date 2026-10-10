<?php
require_once 'backend/config/config.php';
require_once 'backend/config/database.php';

$database = new Database();
$db = $database->connect();

$settings = [];
if ($db) {
    try {
        $stmt = $db->query("SELECT setting_key, setting_value FROM site_settings");
        $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    } catch (Exception $e) {
        $settings = [];
    }
}

if (empty($settings)) {
    $settings = [
        'site_name' => 'ITINFO ACADEMY',
        'site_phone' => '9545214074',
        'facebook_url' => 'https://www.facebook.com/share/ciPrf9ixUD4eeeeT/',
        'instagram_url' => 'https://www.instagram.com/itinfoacademy?igsh=ZHRoYWN2d3o1aWdi',
        'linkedin_url' => 'https://www.linkedin.com/in/itinfo-academy-aa05a02a7'
    ];
}

$siteName = $settings['site_name'] ?? 'ITINFO ACADEMY';
$sitePhone = $settings['site_phone'] ?? '9545214074';
$facebookUrl = $settings['facebook_url'] ?? 'https://www.facebook.com/share/ciPrf9ixUD4eeeeT/';
$instagramUrl = $settings['instagram_url'] ?? 'https://www.instagram.com/itinfoacademy?igsh=ZHRoYWN2d3o1aWdi';
$linkedinUrl = $settings['linkedin_url'] ?? 'https://www.linkedin.com/in/itinfo-academy-aa05a02a7';

// SEO Configuration for Internship page
$seo = [
    'title' => 'Professional Internship Programs',
    'description' => 'Gain real-world IT experience with ITINFO ACADEMY internship programs. Work on live projects, receive expert mentorship, and boost your career with industry credentials.',
    'keywords' => 'IT internship, software development internship, web development internship, data science internship, paid internship India, tech internship program, coding internship',
    'type' => 'website',
    'canonical' => '/internship.php'
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Professional Internship Program - <?php echo htmlspecialchars($siteName); ?></title>
    <link rel="icon" type="image/x-icon" href="public/favicon.ico">
    <?php include 'backend/includes/seo.php'; ?>
    <link rel="stylesheet" href="css/styles.css?v=2026.10">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <div id="navbar"></div>

    <section class="internship-hero-premium">
        <div class="container relative">
            <div class="student-portal-link-wrapper">
                <a href="intern-login.php" class="btn btn-portal-login">Student Portal Login</a>
            </div>
            <span class="hero-badge text-green">// Elite Internship Program</span>
            <h1 class="hero-main-title font-playfair">
                Bridge the Gap Between <br><span class="text-green">Education & Industry</span></h1>
            <p class="hero-description max-w-600 mt-4">Develop
                industry-grade projects in a professional environment. Gain the
                experience that matters for your career.</p>
        </div>
    </section>

    <section class="internship-content-section">
        <div class="container">
            <div class="section-header center mb-5">
                <h2 class="section-title-large text-dark font-playfair">
                    Fuel Your <span class="text-green">Professional Growth</span></h2>
                <div class="section-divider divider-green mx-auto"></div>
                <p class="text-center text-muted max-w-800 mx-auto mt-4 line-height-18">
                    Our internship program
                    provides students and recent graduates with work experience, industry knowledge, and professional
                    growth opportunities designed for real-world application.</p>
            </div>

            <div class="info-grid-premium">
                <div class="info-card-premium">
                    <div class="info-card-icon">
                        <i class="fas fa-project-diagram"></i>
                    </div>
                    <h3 class="info-card-title">Real Projects</h3>
                    <p class="info-card-text">Work on
                        actual industry projects and gain hands-on experience with modern tech stacks.</p>
                </div>
                <div class="info-card-premium">
                    <div class="info-card-icon">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                    <h3 class="info-card-title">Expert Mentorship</h3>
                    <p class="info-card-text">Receive
                        guidance from experienced professionals who are leaders in their respective fields.</p>
                </div>
                <div class="info-card-premium">
                    <div class="info-card-icon">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <h3 class="info-card-title">Career Ready</h3>
                    <p class="info-card-text">Bridge the
                        gap between academic learning and the professional demands of the IT industry.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="internship-content-section" id="internship-programs">
        <div class="container">
            <div class="section-header mb-5">
                <h2 class="courses-category-title text-dark font-playfair font-size-25">
                    Available Programs</h2>
                <div class="section-divider divider-green my-3 w-60">
                </div>
                <p class="text-muted mb-5 font-size-11">Choose the path that
                    aligns with your career goals. Each program includes specialized training, hands-on projects, and
                    mentorship.</p>
            </div>

            <div class="internship-grid">
                <?php
                // Fetch active internships
                $stmt = $db->query("SELECT * FROM internships WHERE is_active = 1 ORDER BY display_order ASC");
                $internships = $stmt->fetchAll();

                if (count($internships) > 0):
                    foreach ($internships as $intern):
                        ?>
                        <div class="internship-card" onmouseover="this.classList.add('active')"
                            onmouseout="this.classList.remove('active')">
                            <div class="course-content p-4">
                                <span
                                    class="side-tag internship-tag"><?php echo htmlspecialchars($intern['duration']); ?></span>
                                <h3 class="course-title text-dark font-playfair font-size-15 mb-2">
                                    <?php echo htmlspecialchars($intern['title']); ?>
                                </h3>
                                <p class="course-description text-muted font-size-095 mb-4 line-height-16">
                                    <?php echo htmlspecialchars($intern['description']); ?>
                                </p>
                                <div class="course-meta mt-auto border-top-light pt-3">
                                    <span
                                        class="course-fee text-dark font-playfair font-bold font-size-15">â‚¹<?php echo number_format($intern['fees'], 2); ?></span>
                                </div>
                            </div>
                            <div class="p-4 pb-4 px-4 pt-0">
                                <a href="apply.php?id=<?php echo $intern['id']; ?>" class="btn btn-enroll-full">Enroll
                                    Now</a>
                            </div>
                        </div>
                        <?php
                    endforeach;
                else:
                    ?>
                    <p class="no-programs-msg">
                        Contact us for information on upcoming internship batches.</p>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <div class="container">
        <section class="internship-cta">
            <h2 class="cta-title">
                Ready to Accelerate Your Career?</h2>
            <p class="cta-text">
                Join our elite community of aspiring professionals and gain the industrial experience you need to
                dominate the IT sector.</p>
            <div class="cta-actions">
                <div class="cta-helpline">
                    <span class="helpline-label">Student
                        Helpline</span>
                    <span class="helpline-number"><?php echo htmlspecialchars($sitePhone); ?></span>
                </div>
                <div class="cta-button-wrapper">
                    <a href="#internship-programs" class="btn btn-explore">Explore
                        Programs</a>
                </div>
            </div>
            <div class="footer-social-links cta-socials">
                <a href="<?php echo htmlspecialchars($facebookUrl); ?>" target="_blank" class="social-icon"><i
                        class="fab fa-facebook-f"></i></a>
                <a href="<?php echo htmlspecialchars($instagramUrl); ?>" target="_blank" class="social-icon"><i
                        class="fab fa-instagram"></i></a>
                <a href="<?php echo htmlspecialchars($linkedinUrl); ?>" target="_blank" class="social-icon"><i
                        class="fab fa-linkedin-in"></i></a>
            </div>
            <p style="margin-top: 1.5rem; font-size: 0.9rem; color: rgba(255,255,255,0.8);">
                <a href="terms-and-conditions.php" target="_blank"
                    style="color: #3db166; text-decoration: underline; font-weight: 600;">
                    <i class="fas fa-file-contract"></i> View Terms and Conditions
                </a>
            </p>
        </section>
    </div>

    <!-- Enrollment Modal -->
    <div id="enrollmentModal" class="modal-overlay-premium">
        <div class="modal-premium">
            <button onclick="closeEnrollmentModal()" class="modal-close-btn">&times;</button>

            <div class="modal-premium-header">
                <span class="text-mono-premium modal-tag" id="modal-intern-tag">Program Selection</span>
                <h2 id="modal-intern-title" class="modal-title">Professional Internship</h2>
                <p class="modal-subtitle">Complete your secure enrollment to access the learning portal.</p>
            </div>

            <div class="modal-body">
                <form id="enrollmentForm" action="backend/api/enroll.php" method="POST">
                    <input type="hidden" id="internship_id" name="internship_id">

                    <div class="form-group mb-4">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="full_name" class="form-control" required
                            placeholder="Enter your official name">
                    </div>

                    <div class="form-group mb-4">
                        <label class="form-label">Corporate Email</label>
                        <input type="email" name="email" class="form-control" required placeholder="name@example.com">
                    </div>

                    <div class="form-group mb-5">
                        <label class="form-label">Payment Gateway</label>
                        <div class="payment-grid">
                            <label class="payment-label">
                                <input type="radio" name="payment_method" value="online" checked class="hidden-radio">
                                <div class="payment-card-premium active" onclick="selectPayment(this)">
                                    <div class="payment-title">Gateway</div>
                                    <div class="payment-subtitle text-green">Secure Online</div>
                                </div>
                            </label>
                            <label class="payment-label">
                                <input type="radio" name="payment_method" value="cash" class="hidden-radio">
                                <div class="payment-card-premium" onclick="selectPayment(this)">
                                    <div class="payment-title">Manual</div>
                                    <div class="payment-subtitle text-muted">Office Access</div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div class="text-center">
                        <div class="fee-display-box">
                            <span class="fee-label">Total Enrollment Fee</span>
                            <span id="modal-intern-fee" class="fee-amount">â‚¹0</span>
                        </div>
                        <button type="submit" class="btn-portal-primary">Submit Authorization</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="footer"></div>

    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <script src="js/components.js?v=2026.10"></script>
    <script src="js/main.js"></script>
    <script>
        function openEnrollmentModal(intern) {
            document.getElementById('modal-intern-title').innerText = intern.title;
            document.getElementById('modal-intern-tag').innerText = intern.duration;
            document.getElementById('modal-intern-fee').innerText = 'â‚¹' + parseFloat(intern.fees).toLocaleString();
            document.getElementById('internship_id').value = intern.id;
            document.getElementById('enrollmentModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeEnrollmentModal() {
            document.getElementById('enrollmentModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        function selectPayment(el) {
            document.querySelectorAll('.payment-card-premium').forEach(card => {
                card.classList.remove('active');
                card.style.borderColor = 'rgba(255,255,255,0.1)';
            });
            el.classList.add('active');
            el.style.borderColor = 'var(--primary)';
            el.previousElementSibling.checked = true;
        }

        // Modal click out to close
        window.onclick = function (event) {
            const modal = document.getElementById('enrollmentModal');
            if (event.target == modal) {
                closeEnrollmentModal();
            }
        }

        document.getElementById('enrollmentForm').addEventListener('submit', async function (e) {
            e.preventDefault();

            const formData = new FormData(this);
            const paymentMethod = formData.get('payment_method');
            const submitBtn = this.querySelector('button[type="submit"]');

            if (paymentMethod === 'online') {
                submitBtn.disabled = true;
                submitBtn.innerText = 'Processing...';

                try {
                    // 1. Create Order
                    const orderRes = await fetch('backend/api/create_order.php', {
                        method: 'POST',
                        body: JSON.stringify({
                            internship_id: formData.get('internship_id')
                        }),
                        headers: { 'Content-Type': 'application/json' }
                    });

                    let orderData;
                    const text = await orderRes.text();
                    try {
                        orderData = JSON.parse(text);
                    } catch (e) {
                        console.error('Invalid JSON response:', text);
                        alert('A server error occurred. Please check console for details.');
                        submitBtn.disabled = false;
                        submitBtn.innerText = 'Submit Authorization';
                        return;
                    }

                    if (!orderData.success) {
                        alert('Error creating order: ' + orderData.message);
                        submitBtn.disabled = false;
                        submitBtn.innerText = 'Submit Authorization';
                        return;
                    }

                    // 2. Open Razorpay
                    const options = {
                        "key": orderData.key_id,
                        "amount": orderData.amount,
                        "currency": "INR",
                        "name": orderData.name,
                        "description": orderData.description,
                        "order_id": orderData.order_id,
                        "handler": async function (response) {
                            // 3. Verify Payment
                            submitBtn.innerText = 'Verifying...';

                            const verifyRes = await fetch('backend/api/verify_payment.php', {
                                method: 'POST',
                                body: JSON.stringify({
                                    razorpay_payment_id: response.razorpay_payment_id,
                                    razorpay_order_id: response.razorpay_order_id,
                                    razorpay_signature: response.razorpay_signature,
                                    internship_id: formData.get('internship_id'),
                                    full_name: formData.get('full_name'),
                                    email: formData.get('email')
                                }),
                                headers: { 'Content-Type': 'application/json' }
                            });

                            let verifyData;
                            const vText = await verifyRes.text();
                            try {
                                verifyData = JSON.parse(vText);
                            } catch (e) {
                                console.error('Invalid Verification JSON:', vText);
                                alert('Verification error. Please check console.');
                                submitBtn.disabled = false;
                                submitBtn.innerText = 'Submit Authorization';
                                return;
                            }

                            if (verifyData.success) {
                                window.location.href = verifyData.redirect_url;
                            } else {
                                alert('Payment verification failed: ' + verifyData.message);
                                submitBtn.disabled = false;
                                submitBtn.innerText = 'Submit Authorization';
                            }
                        },
                        "prefill": {
                            "name": formData.get('full_name'),
                            "email": formData.get('email')
                        },
                        "theme": {
                            "color": "#3db166"
                        }
                    };

                    const rzp1 = new Razorpay(options);
                    rzp1.open();

                    rzp1.on('payment.failed', function (response) {
                        alert('Payment Failed: ' + response.error.description);
                        submitBtn.disabled = false;
                        submitBtn.innerText = 'Submit Authorization';
                    });

                } catch (err) {
                    console.error(err);
                    alert('An error occurred. Please try again.');
                    submitBtn.disabled = false;
                    submitBtn.innerText = 'Submit Authorization';
                }
            } else {
                // Manual submission
                this.submit();
            }
        });
    </script>

    <script src="js/components.js?v=2026.10"></script>
    <script src="js/main.js"></script>
</body>

</html>