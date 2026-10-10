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

$jobCourses = [];
$langCourses = [];
$otherCourses = [];

if ($db) {
    try {
        $stmt = $db->query("SELECT * FROM courses WHERE category = 'job_role' AND is_active = 1 ORDER BY display_order ASC");
        $jobCourses = $stmt->fetchAll();

        $stmt = $db->query("SELECT * FROM courses WHERE category IN ('programming', 'web_dev', 'database') AND is_active = 1 ORDER BY display_order ASC");
        $langCourses = $stmt->fetchAll();

        $stmt = $db->query("SELECT * FROM courses WHERE category = 'design' AND is_active = 1 ORDER BY display_order ASC");
        $otherCourses = $stmt->fetchAll();

    } catch (Exception $e) {
        $jobCourses = [];
        $langCourses = [];
        $otherCourses = [];
    }
}

if (empty($jobCourses) && empty($langCourses) && empty($otherCourses)) {
    $jobCourses = [
        ['title' => 'Data Science', 'slug' => 'data-science'],
        ['title' => 'Java Full Stack Developer', 'slug' => 'java-full-stack'],
        ['title' => 'MERN Full Stack Web Development', 'slug' => 'mern-full-stack'],
        ['title' => 'Flutter Development', 'slug' => 'flutter'],
        ['title' => 'Cyber Security', 'slug' => 'cyber-security'],
        ['title' => 'Ethical Hacking', 'slug' => 'ethical-hacking'],
        ['title' => 'Software Testing', 'slug' => 'software-testing'],
        ['title' => 'Hardware and Networking', 'slug' => 'hardware-networking']
    ];

    $langCourses = [
        ['title' => 'C Programming', 'slug' => 'c-programming'],
        ['title' => 'C++ Programming', 'slug' => 'cpp-programming'],
        ['title' => 'Python Programming', 'slug' => 'python'],
        ['title' => 'Core Java', 'slug' => 'core-java'],
        ['title' => 'C# Programming', 'slug' => 'csharp'],
        ['title' => 'HTML', 'slug' => 'html'],
        ['title' => 'CSS', 'slug' => 'css'],
        ['title' => 'JavaScript', 'slug' => 'javascript'],
        ['title' => 'ReactJS', 'slug' => 'react'],
        ['title' => 'NodeJS', 'slug' => 'nodejs'],
        ['title' => 'Angular', 'slug' => 'angular'],
        ['title' => 'PHP', 'slug' => 'php'],
        ['title' => 'MongoDB', 'slug' => 'mongodb'],
        ['title' => 'MySQL', 'slug' => 'mysql'],
        ['title' => 'SQL', 'slug' => 'sql'],
        ['title' => 'ASP.NET', 'slug' => 'aspnet']
    ];

    $otherCourses = [
        ['title' => 'Adobe Photoshop', 'slug' => 'photoshop'],
        ['title' => 'Adobe Illustrator', 'slug' => 'illustrator'],
        ['title' => 'Adobe InDesign', 'slug' => 'indesign'],
        ['title' => 'CorelDRAW', 'slug' => 'coreldraw'],
        ['title' => 'AutoCAD', 'slug' => 'autocad'],
        ['title' => 'SketchUp', 'slug' => 'sketchup']
    ];
}

$siteName = $settings['site_name'] ?? 'ITINFO ACADEMY';

// SEO Configuration for Courses page
$seo = [
    'title' => 'IT Courses & Training Programs',
    'description' => 'Explore industry-ready IT courses at ITINFO ACADEMY. Full Stack Development, Data Science, Cybersecurity, Python, Java, and more. Expert training with placement support.',
    'keywords' => 'IT courses, web development course, data science training, cybersecurity course, Python programming, Java full stack, MERN stack, coding bootcamp, software training India',
    'type' => 'website',
    'canonical' => '/courses.php'
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Professional Courses - <?php echo htmlspecialchars($siteName); ?></title>
    <link rel="icon" type="image/x-icon" href="public/favicon.ico">
    <?php include 'backend/includes/seo.php'; ?>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <div id="navbar"></div>

    <section class="courses-hero-premium">
        <div class="container">
            <span class="hero-badge">Our Curriculum</span>
            <h1 class="hero-main-title">Master the Most In-Demand <br><span class="text-gradient">Tech
                    Skills</span></h1>
            <p class="hero-description">Transform your career with our comprehensive, industry-focused
                training programs
                led by experts.</p>
        </div>
    </section>

    <main class="courses-section">
        <div class="container">

            <?php if (!empty($jobCourses)): ?>
                <div class="course-category-wrapper">
                    <h2 class="courses-category-title">Job-Oriented Programs</h2>
                    <div class="courses-grid-layout">
                        <?php foreach ($jobCourses as $course): ?>
                            <div class="course-card-premium list-view">
                                <div class="course-img-wrapper">
                                    <img src="<?php echo !empty($course['image_url']) ? htmlspecialchars($course['image_url']) : 'public/courses/' . ($course['slug'] ?? 'default') . '.jpg'; ?>"
                                        alt="<?php echo htmlspecialchars($course['title']); ?>" class="course-img"
                                        onerror="this.src='https://images.unsplash.com/photo-1498050108023-c5249f4df085?auto=format&fit=crop&w=800&q=80'">
                                </div>
                                <div class="course-info">
                                    <div class="course-meta-row">
                                        <span class="course-tag">Job Role</span>
                                        <?php if (!empty($course['fees']) && $course['fees'] > 0): ?>
                                            <span class="course-price">₹<?php echo number_format($course['fees']); ?></span>
                                        <?php else: ?>
                                            <span class="course-price contact">Contact for pricing</span>
                                        <?php endif; ?>
                                    </div>
                                    <h3 class="course-title"><?php echo htmlspecialchars($course['title']); ?></h3>
                                    <p class="course-excerpt">
                                        <?php
                                        $desc = $course['description'] ?? 'Comprehensive professional training designed for immediate industry placement and career growth.';
                                        echo htmlspecialchars(strlen($desc) > 100 ? substr($desc, 0, 100) . "..." : $desc);
                                        ?>
                                    </p>
                                    <a href="course.php?slug=<?php echo $course['slug']; ?>" class="btn-text">
                                        Explore Syllabus
                                        <svg class="arrow-icon-small" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd"
                                                d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($langCourses)): ?>
                <div class="course-category-wrapper">
                    <h2 class="courses-category-title">Programming & Technologies</h2>
                    <div class="courses-grid-layout">
                        <?php foreach ($langCourses as $course): ?>
                            <div class="course-card-premium list-view">
                                <div class="course-img-wrapper">
                                    <img src="<?php echo !empty($course['image_url']) ? htmlspecialchars($course['image_url']) : 'public/courses/' . ($course['slug'] ?? 'default') . '.jpg'; ?>"
                                        alt="<?php echo htmlspecialchars($course['title']); ?>" class="course-img"
                                        onerror="this.src='https://images.unsplash.com/photo-1517694712202-14dd9538aa97?auto=format&fit=crop&w=800&q=80'">
                                </div>
                                <div class="course-info">
                                    <div class="course-meta-row">
                                        <span class="course-tag">Technical</span>
                                        <?php if (!empty($course['fees']) && $course['fees'] > 0): ?>
                                            <span class="course-price">₹<?php echo number_format($course['fees']); ?></span>
                                        <?php else: ?>
                                            <span class="course-price contact">Contact for pricing</span>
                                        <?php endif; ?>
                                    </div>
                                    <h3 class="course-title"><?php echo htmlspecialchars($course['title']); ?></h3>
                                    <p class="course-excerpt">
                                        <?php
                                        $desc = $course['description'] ?? 'Master the fundamentals and advanced concepts of this technology with hands-on projects.';
                                        echo htmlspecialchars(strlen($desc) > 100 ? substr($desc, 0, 100) . "..." : $desc);
                                        ?>
                                    </p>
                                    <a href="course.php?slug=<?php echo $course['slug']; ?>" class="btn-text">
                                        Course Details
                                        <svg class="arrow-icon-small" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd"
                                                d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($otherCourses)): ?>
                <div class="course-category-wrapper">
                    <h2 class="courses-category-title">Design & Creative Arts</h2>
                    <div class="courses-grid-layout">
                        <?php foreach ($otherCourses as $course): ?>
                            <div class="course-card-premium list-view">
                                <div class="course-img-wrapper">
                                    <img src="<?php echo !empty($course['image_url']) ? htmlspecialchars($course['image_url']) : 'public/courses/' . ($course['slug'] ?? 'default') . '.jpg'; ?>"
                                        alt="<?php echo htmlspecialchars($course['title']); ?>" class="course-img"
                                        onerror="this.src='https://images.unsplash.com/photo-1558655146-d09347e92766?auto=format&fit=crop&w=800&q=80'">
                                </div>
                                <div class="course-info">
                                    <div class="course-meta-row">
                                        <span class="course-tag">Creative</span>
                                        <?php if (!empty($course['fees']) && $course['fees'] > 0): ?>
                                            <span class="course-price">₹<?php echo number_format($course['fees']); ?></span>
                                        <?php else: ?>
                                            <span class="course-price contact">Contact for pricing</span>
                                        <?php endif; ?>
                                    </div>
                                    <h3 class="course-title"><?php echo htmlspecialchars($course['title']); ?></h3>
                                    <p class="course-excerpt">
                                        <?php
                                        $desc = $course['description'] ?? 'Professional design training with industry-standard tools and portfolio development.';
                                        echo htmlspecialchars(strlen($desc) > 100 ? substr($desc, 0, 100) . "..." : $desc);
                                        ?>
                                    </p>
                                    <a href="course.php?slug=<?php echo $course['slug']; ?>" class="btn-text">
                                        View Syllabus
                                        <svg class="arrow-icon-small" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd"
                                                d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </main>

    <div id="footer"></div>

    <script src="js/components.js"></script>
    <script src="js/main.js"></script>
</body>

</html>