<?php
require_once 'backend/config/config.php';
require_once 'backend/config/database.php';

$database = new Database();
$db = $database->connect();

$settings = [
    'site_name' => 'ITINFO ACADEMY',
    'site_tagline' => 'Empowering Future IT Professionals'
];

$heroSlides = [
    [
        'title' => 'Transform Your Career in IT',
        'subtitle' => 'Join India\'s Leading IT Training Institute',
        'description' => 'Master cutting-edge technologies with hands-on training, expert mentorship, and guaranteed placement assistance.',
        'image_url' => 'public/main3.jpg',
        'button_text' => 'Explore Courses',
        'button_url' => 'courses.php'
    ]
];

$featuredCourses = [
    ['title' => 'Web Development', 'slug' => 'web-development', 'short_description' => 'Learn to build modern web applications with React and Node.js.'],
    ['title' => 'Data Science', 'slug' => 'data-science', 'short_description' => 'Master data analysis and machine learning with Python.'],
    ['title' => 'Cybersecurity', 'slug' => 'cyber-security', 'short_description' => 'Protect systems and networks from cyber threats.']
];

$testimonials = [];

if ($db !== null) {
    try {
        $stmt = $db->query("SELECT setting_key, setting_value FROM site_settings");
        $dbSettings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        if (!empty($dbSettings)) {
            $settings = array_merge($settings, $dbSettings);
        }
    } catch (Exception $e) {
    }

    try {
        $stmt = $db->query("SELECT * FROM hero_slides WHERE is_active = 1 ORDER BY display_order ASC");
        $dbSlides = $stmt->fetchAll();
        if (!empty($dbSlides)) {
            $heroSlides = $dbSlides;
        }
    } catch (Exception $e) {
    }

    try {
        $stmt = $db->query("SELECT * FROM courses WHERE is_active = 1 AND is_featured = 1 ORDER BY display_order ASC LIMIT 6");
        $dbCourses = $stmt->fetchAll();
        if (!empty($dbCourses)) {
            $featuredCourses = $dbCourses;
        }
    } catch (Exception $e) {
    }

    try {
        $stmt = $db->query("SELECT * FROM testimonials WHERE is_active = 1 AND is_featured = 1 ORDER BY display_order ASC LIMIT 6");
        $testimonials = $stmt->fetchAll();
    } catch (Exception $e) {
    }
}

$siteName = $settings['site_name'] ?? 'ITINFO ACADEMY';

// SEO Configuration for Homepage
$seo = [
    'title' => 'Premier IT Training Institute',
    'description' => 'ITINFO ACADEMY - Transform your career with India\'s leading IT training institute. Expert-led courses in Web Development, Data Science, Cybersecurity with 95% placement rate. Enroll now!',
    'keywords' => 'IT training institute, IT courses India, web development course, data science training, cybersecurity course, programming bootcamp, IT placement, coding classes, software training',
    'type' => 'website',
    'canonical' => '/'
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($siteName); ?> | Premier IT Training Institute</title>
    <link rel="icon" type="image/x-icon" href="public/favicon.ico">
    <?php include 'backend/includes/seo.php'; ?>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <div id="navbar"></div>

    <section class="hero-premium">
        <div class="hero-slider-container">
            <?php foreach ($heroSlides as $index => $slide): ?>
                <div class="hero-slide <?php echo $index === 0 ? 'active' : ''; ?>" data-index="<?php echo $index; ?>">
                    <div class="hero-bg-wrapper">
                        <img src="<?php echo htmlspecialchars($slide['image_url']); ?>" alt="Hero Background"
                            class="hero-bg-image">
                        <div class="hero-overlay-gradient"></div>
                    </div>
                    <div class="container">
                        <div class="hero-content-box">
                            <span class="hero-badge-green">// Excellence in Education</span>
                            <h1 class="hero-main-title"><?php echo htmlspecialchars($slide['title']); ?></h1>
                            <p class="hero-description"><?php echo htmlspecialchars($slide['description'] ?? ''); ?></p>
                            <div class="hero-actions">
                                <a href="<?php echo htmlspecialchars($slide['button_url'] ?? 'courses.php'); ?>"
                                    class="btn-primary"><?php echo htmlspecialchars($slide['button_text'] ?? 'Explore Courses'); ?></a>
                                <a href="about.php" class="btn-outline">About Us</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (count($heroSlides) > 1): ?>
            <div class="hero-slider-controls">
                <button class="slider-control prev" id="heroPrev">&#8592;</button>
                <div class="slider-dots">
                    <?php foreach ($heroSlides as $index => $slide): ?>
                        <span class="slider-dot <?php echo $index === 0 ? 'active' : ''; ?>"
                            data-index="<?php echo $index; ?>"></span>
                    <?php endforeach; ?>
                </div>
                <button class="slider-control next" id="heroNext">&#8594;</button>
            </div>
        <?php endif; ?>

        <div class="hero-static-overlay">
            <div class="container">
                <div class="hero-stats">
                    <div class="stat-card">
                        <span class="stat-number">5K+</span>
                        <span class="stat-label">Students</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-number">95%</span>
                        <span class="stat-label">Placement</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-number">50+</span>
                        <span class="stat-label">Courses</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="partners-section">
        <div class="container">
            <div class="partners-slider">
                <div class="partner-logo">Maestro Intellect</div>
               <div class="partner-logo">Techvant software technologies</div>
                <div class="partner-logo">Implisoft</div>
                <div class="partner-logo">InternMeets</div>
                <div class="partner-logo">OmegaSoft Technologies</div>
                <div class="partner-logo">Perpetual Innovation</div>
            </div>
        </div>
    </section>

    <section class="process-section">
        <div class="container">
            <div class="section-header center">
                <h2 class="section-title-large">Your Path to <span class="primary-text">Success</span></h2>
                <div class="section-divider"></div>
            </div>

            <div class="process-grid">
                <div class="process-item">
                    <div class="process-number">01</div>
                    <h3>Career Guidance</h3>
                    <p>Free career guidance to choose the right path for your future aspirations and goals.</p>
                </div>
                <div class="process-item accent-bottom">
                    <div class="process-number">02</div>
                    <h3>Expert Training</h3>
                    <p>Industry-led practical sessions and hands-on laboratory work with expert mentors.</p>
                </div>
                <div class="process-item">
                    <div class="process-number">03</div>
                    <h3>Live Projects</h3>
                    <p>Real-world industrial projects and building a professional portfolio for your career.</p>
                </div>
                <div class="process-item">
                    <div class="process-number">04</div>
                    <h3>Job Placement</h3>
                    <p>Dedicated support to land your dream job in top MNCs with interview prep.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="features-premium">
        <div class="container">
            <div class="section-header center">
                <h2 class="section-title-large">Why Choose <span
                        class="primary-text"><?php echo htmlspecialchars($siteName); ?></span></h2>
                <p class="section-subtitle">We don't just teach code; we build professionals.</p>
            </div>
            <div class="features-grid-premium">
                <div class="feature-card-premium">
                    <div class="feature-icon-box"><i class="fas fa-user-tie"></i></div>
                    <h3 class="feature-card-title">Expert Mentors</h3>
                    <p class="feature-card-text">Learn from veterans with decades of collective industry experience.</p>
                </div>
                <div class="feature-card-premium">
                    <div class="feature-icon-box"><i class="fas fa-laptop-code"></i></div>
                    <h3 class="feature-card-title">Modern Labs</h3>
                    <p class="feature-card-text">24/7 access to high-performance labs and latest software tools.</p>
                </div>
                <div class="feature-card-premium">
                    <div class="feature-icon-box"><i class="fas fa-briefcase"></i></div>
                    <h3 class="feature-card-title">Portfolio Build</h3>
                    <p class="feature-card-text">Graduate with a portfolio that speaks louder than your resume.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="courses-premium">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title-large">Featured <span class="primary-text">Courses</span></h2>
                <div class="section-divider"></div>
            </div>

            <div class="carousel-wrapper">
                <div class="carousel-nav">
                    <button class="carousel-nav-btn" id="scrollLeft">&#8592;</button>
                    <button class="carousel-nav-btn" id="scrollRight">&#8594;</button>
                </div>

                <div class="carousel-scroll-viewport" id="courseCarousel">
                    <?php foreach ($featuredCourses as $course): ?>
                        <div class="course-card-premium">
                            <div class="course-img-wrapper">
                                <img src="<?php echo !empty($course['image_url']) ? htmlspecialchars($course['image_url']) : 'public/langImages/full_web.jpg'; ?>"
                                    alt="<?php echo htmlspecialchars($course['title']); ?>" class="course-img">
                            </div>
                            <div class="course-info">
                                <span class="course-tag-premium">Academy Featured</span>
                                <h3 class="course-title"><?php echo htmlspecialchars($course['title']); ?></h3>
                                <p class="course-excerpt"><?php echo htmlspecialchars($course['short_description']); ?></p>
                                <a href="courses.php" class="course-action-link">View Syllabus <i
                                        class="fas fa-arrow-right"></i></a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>

    <section class="testimonials-section">
        <div class="container">
            <div class="section-header center">
                <span class="section-tag">Student Reviews</span>
                <h2 class="section-title-large">What Our <span class="primary-text">Students Say</span></h2>
                <div class="section-divider"></div>
            </div>

            <div class="carousel-wrapper">
                <div class="carousel-nav">
                    <button class="carousel-nav-btn" id="testiLeft">&#8592;</button>
                    <button class="carousel-nav-btn" id="testiRight">&#8594;</button>
                </div>

                <div class="carousel-scroll-viewport" id="testiCarousel">
                    <?php if (!empty($testimonials)): ?>
                        <?php foreach ($testimonials as $t): ?>
                            <div class="testimonial-card">
                                <div class="testimonial-rating">
                                    <?php for ($i = 0; $i < $t['rating']; $i++): ?>
                                        <i class="fas fa-star"></i>
                                    <?php endfor; ?>
                                </div>
                                <p class="testimonial-content">"<?php echo htmlspecialchars($t['testimonial']); ?>"</p>
                                <div class="testimonial-user">
                                    <div class="user-avatar">
                                        <?php echo strtoupper(substr($t['student_name'], 0, 1)); ?>
                                    </div>
                                    <div class="user-info">
                                        <h4><?php echo htmlspecialchars($t['student_name']); ?></h4>
                                        <span><?php echo htmlspecialchars($t['course']); ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="testimonials-empty">Working with hundreds of students to transform their careers.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <section class="faq-section">
        <div class="container">
            <div class="section-header center">
                <h2 class="section-title-large">Common <span class="primary-text">Questions</span></h2>
                <div class="section-divider"></div>
            </div>

            <div class="faq-grid">
                <div class="faq-item active">
                    <div class="faq-question">Do you offer 100% placement? <span>+</span></div>
                    <div class="faq-answer">Yes, we have a dedicated placement cell that works tirelessly with top MNCs
                        to ensure our students get placed in high-paying jobs.</div>
                </div>
                <div class="faq-item">
                    <div class="faq-question">Can I pay in installments? <span>+</span></div>
                    <div class="faq-answer">Certainly! We offer flexible payment plans and zero-cost EMI options to make
                        education accessible to everyone.</div>
                </div>
                <div class="faq-item">
                    <div class="faq-question">Is the training practical based? <span>+</span></div>
                    <div class="faq-answer">Our curriculum is 80% practical. We believe in learning by doing, which is
                        why you'll spend most of your time coding and building.</div>
                </div>
            </div>
        </div>
    </section>

    <section class="cta-premium">
        <div class="container">
            <div class="cta-banner">
                <h2 class="cta-banner-title">Begin Your Tech Journey Today</h2>
                <p class="cta-banner-text">Join the next generation of digital pioneers at India's premier IT academy.
                    Enroll now for upcoming sessions.</p>
                <div class="hero-actions center">
                    <a href="courses.php" class="btn-primary dark">Enroll Now</a>
                    <a href="about.php" class="btn-outline white">Meet Faculty</a>
                </div>
            </div>
        </div>
    </section>

    <div id="footer"></div>

    <script src="js/components.js"></script>
    <script src="js/main.js"></script>
    <script>
        document.querySelectorAll('.faq-question').forEach(q => {
            q.addEventListener('click', () => {
                const item = q.parentElement;
                document.querySelectorAll('.faq-item').forEach(i => {
                    if (i !== item) i.classList.remove('active');
                });
                item.classList.toggle('active');
            });
        });
    </script>
</body>

</html>