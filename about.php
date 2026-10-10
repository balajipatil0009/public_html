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
    $settings = ['site_name' => 'ITINFO ACADEMY'];
}

$aboutSections = [];
if ($db) {
    try {
        $stmt = $db->query("SELECT * FROM about_sections WHERE is_active = 1 ORDER BY display_order ASC");
        $dbAbout = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($dbAbout as $row) {
            $aboutSections[$row['section_key']] = $row['content'];
        }
    } catch (Exception $e) {
        $aboutSections = [];
    }
}

$siteName = $settings['site_name'] ?? 'ITINFO ACADEMY';

// SEO Configuration for About page
$seo = [
    'title' => 'About Us',
    'description' => 'Learn about ITINFO ACADEMY - India\'s premier IT training institute. Our mission, vision, expert faculty, and commitment to student success with 95% placement rate.',
    'keywords' => 'about ITINFO ACADEMY, IT training institute India, IT education, tech academy, programming courses, IT mentors, career training',
    'type' => 'website',
    'canonical' => '/about.php'
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us | <?php echo htmlspecialchars($siteName); ?></title>
    <link rel="icon" type="image/x-icon" href="public/favicon.ico">
    <?php include 'backend/includes/seo.php'; ?>
    <link rel="stylesheet" href="css/styles.css?v=2026.10">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <div id="navbar"></div>

    <section class="about-hero-premium">
        <div class="container text-center">
            <div class="animate-fade-in">
                <span class="hero-badge">Our Legacy</span>
                <h1 class="hero-main-title">Pioneering Excellence in <span class="text-gradient">IT
                        Education</span></h1>
                <p class="hero-description mx-auto max-w-700">Since our
                    inception, ITINFO ACADEMY has been at the forefront of digital transformation, nurturing thousands
                    of students into successful IT leaders.</p>
            </div>
        </div>
    </section>

    <section class="split-section">
        <div class="split-container">
            <div class="split-side mission-side">
                <div class="split-content">
                    <span class="side-tag">The Goal</span>
                    <h2 class="side-title">Our Mission</h2>
                    <div class="side-text">
                        <?php echo $aboutSections['our_mission'] ?? 'To empower students with cutting-edge IT skills through practical, industry-aligned training.'; ?>
                    </div>
                </div>
            </div>
            <div class="split-side vision-side">
                <div class="split-content">
                    <span class="side-tag">The Future</span>
                    <h2 class="side-title">Our Vision</h2>
                    <div class="side-text">
                        <?php echo $aboutSections['our_vision'] ?? 'To be the global benchmark for technical education, fostering a community of innovators.'; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="about-details-premium">
        <div class="container">
            <div class="details-grid">
                <div class="details-text animate-fade-in">
                    <span class="side-tag">Company Overview</span>
                    <h2 class="section-title-large">Why We <span class="primary-text">Started</span></h2>
                    <div class="hero-description" style="color: var(--text-muted);">
                        <?php echo $aboutSections['company_overview'] ?? 'ITINFO ACADEMY was born out of a simple observation: the tech industry moves faster than traditional education.'; ?>
                    </div>

                    <div class="core-values-grid">
                        <div class="value-item">
                            <span class="value-icon">âœ“</span>
                            <span>Industry-Led Curriculum</span>
                        </div>
                        <div class="value-item">
                            <span class="value-icon">âœ“</span>
                            <span>24/7 Mentorship Support</span>
                        </div>
                        <div class="value-item">
                            <span class="value-icon">âœ“</span>
                            <span>Direct Placement Assistance</span>
                        </div>
                    </div>
                </div>
                <div class="details-image animate-slide-up">
                    <div class="image-stack">
                        <img src="public/langImages/full_web.jpg" alt="Learning" class="stack-img main">
                        <div class="image-accent-box"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="process-section" style="background: var(--surface-alt);">
        <div class="container">
            <div class="section-header center">
                <h2 class="section-title-large">Who We <span class="primary-text">Are</span></h2>
                <div class="section-divider"></div>
            </div>
            <div class="hero-description text-center mx-auto text-muted max-w-800 mt-2">
                <?php echo $aboutSections['who_we_are'] ?? 'We are an authorized training center providing information resources and consulting services.'; ?>
            </div>
        </div>
    </section>

    <section class="about-details-premium">
        <div class="container">
            <div class="section-header center mb-5">
                <h2 class="section-title-large">Our <span class="primary-text">Journey</span></h2>
                <div class="section-divider"></div>
            </div>

            <div class="about-grid-2">
                <div class="about-card">
                    <h3 class="feature-card-title">History</h3>
                    <p class="about-text">
                        <?php echo $aboutSections['history'] ?? 'Founded in 2022, we started with a mission to bridge the gap between academia and industry.'; ?>
                    </p>
                </div>
                <div class="about-card">
                    <h3 class="feature-card-title">Growth & Expansion</h3>
                    <p class="about-text">
                        <?php echo $aboutSections['growth_expansion'] ?? 'From a single classroom to a global online presence, our growth has been driven by student success.'; ?>
                    </p>
                </div>
            </div>
        </div>
    </section>

    <section class="features-premium" style="background: var(--surface-alt);">
        <div class="container">
            <div class="section-header center">
                <h2 class="section-title-large">Premium <span class="primary-text">Features</span></h2>
                <div class="section-divider"></div>
            </div>
            <div class="features-grid-premium" style="grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));">
                <div class="feature-card-premium">
                    <div class="feature-icon-box"><i class="fas fa-laptop-code"></i></div>
                    <h3 class="feature-card-title">Customized Training</h3>
                    <p class="feature-card-text">
                        <?php echo $aboutSections['customized_training'] ?? 'Tailored learning paths designed to meet specific career goals and industry requirements.'; ?>
                    </p>
                </div>
                <div class="feature-card-premium">
                    <div class="feature-icon-box"><i class="fas fa-lightbulb"></i></div>
                    <h3 class="feature-card-title">Innovation</h3>
                    <p class="feature-card-text">
                        <?php echo $aboutSections['innovation'] ?? 'Constantly updating our curriculum with the latest technologies and teaching methodologies.'; ?>
                    </p>
                </div>
            </div>
        </div>
    </section>

    <section class="partners-section">
        <div class="container">
            <div class="section-header center mb-4">
                <h2 class="section-title-large">Strategic <span class="primary-text">Partnerships</span></h2>
                <div class="section-divider"></div>
                <p class="text-center text-muted max-w-800 mx-auto mt-4">
                    <?php echo $aboutSections['partnerships'] ?? 'Collaborating with industry leaders to provide the best opportunities for our students.'; ?>
                </p>
            </div>
            <div class="partners-slider animate-in">
                 <div class="partner-logo">Maestro Intellect</div>
                <div class="partner-logo">ALPHASEAM Enterprises LLP</div>
                <div class="partner-logo">Gtastrix IT PVT LTD</div>
                <div class="partner-logo">InternMeets</div>
                <div class="partner-logo">OmegaSoft Technologies</div>
                <div class="partner-logo">Perpetual Innovation</div>
            </div>
        </div>
    </section>

    <div id="footer"></div>

    <script src="js/components.js?v=2026.10"></script>
    <script src="js/main.js"></script>
</body>

</html>