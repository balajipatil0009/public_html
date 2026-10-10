<?php
require_once 'backend/config/config.php';
require_once 'backend/config/database.php';

$courseSlug = isset($_GET['slug']) ? $_GET['slug'] : '';

if (empty($courseSlug)) {
    header('Location: courses.php');
    exit;
}

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

$course = null;
if ($db) {
    try {
        $stmt = $db->prepare("SELECT * FROM courses WHERE slug = :slug AND is_active = 1");
        $stmt->bindParam(':slug', $courseSlug);
        $stmt->execute();
        $course = $stmt->fetch();
    } catch (Exception $e) {
        $course = null;
    }
}

if (!$course) {
    // Course not found - redirect to courses page
    header('Location: courses.php');
    exit;
}

$siteName = $settings['site_name'] ?? 'ITINFO ACADEMY';
$courseTitle = $course['title'] ?? 'Course';
$courseDescription = $course['short_description'] ?? 'Learn with our comprehensive course.';
$courseCategory = $course['category'] ?? 'general';

$syllabusData = [];
if ($course && !empty($course['syllabus'])) {
    $syllabusData = json_decode($course['syllabus'], true) ?? [];
}

// Get category label
$categoryLabels = [
    'job_role' => 'Job Role',
    'programming' => 'Programming',
    'web_dev' => 'Web Development',
    'database' => 'Database',
    'design' => 'Design'
];
$categoryLabel = $categoryLabels[$courseCategory] ?? 'Course';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?php echo htmlspecialchars($courseTitle); ?> -
        <?php echo htmlspecialchars($siteName); ?>
    </title>
    <meta name="description" content="<?php echo htmlspecialchars($courseDescription); ?>">
    <link rel="icon" type="image/x-icon" href="public/favicon.ico">
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .course-detail-hero {
            min-height: 50vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--gradient-primary);
            padding: 8rem 0 4rem;
            text-align: center;
        }

        .course-detail-hero .hero-badge {
            background: rgba(61, 177, 102, 0.2);
            border: 1px solid rgba(61, 177, 102, 0.4);
            color: #3db166;
        }

        .course-detail-hero h1 {
            font-size: clamp(2rem, 4vw, 3rem);
            color: white;
            margin-bottom: 1rem;
        }

        .course-detail-hero p {
            font-size: 1.125rem;
            color: rgba(255, 255, 255, 0.8);
            max-width: 700px;
            margin: 0 auto;
        }

        .syllabus-section {
            padding: var(--section-padding);
            background: var(--surface-alt);
        }

        .syllabus-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .syllabus-module {
            background: white;
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 2rem;
            transition: var(--transition);
        }

        .syllabus-module:hover {
            border-color: var(--primary-light);
            box-shadow: var(--shadow-md);
        }

        .syllabus-module-number {
            width: 40px;
            height: 40px;
            background: var(--gradient-primary);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.875rem;
            margin-bottom: 1rem;
        }

        .syllabus-module h3 {
            font-size: 1.25rem;
            color: var(--text-main);
            margin-bottom: 1rem;
        }

        .syllabus-module ul {
            list-style: none;
            padding: 0;
        }

        .syllabus-module li {
            position: relative;
            padding-left: 1.5rem;
            margin-bottom: 0.5rem;
            color: var(--text-secondary);
            font-size: 0.9375rem;
        }

        .syllabus-module li::before {
            content: '→';
            position: absolute;
            left: 0;
            color: var(--secondary);
        }

        .course-cta {
            padding: 4rem 0;
            background: white;
            text-align: center;
        }

        .course-cta h2 {
            font-size: 1.75rem;
            margin-bottom: 1rem;
        }

        .course-cta p {
            color: var(--text-muted);
            margin-bottom: 2rem;
        }

        .no-syllabus {
            text-align: center;
            padding: 4rem 2rem;
        }

        .no-syllabus-card {
            max-width: 500px;
            margin: 0 auto;
            background: white;
            border: 1px solid var(--border);
            border-radius: var(--radius-xl);
            padding: 3rem;
        }

        .no-syllabus-card h3 {
            color: var(--text-main);
            margin-bottom: 1rem;
        }

        .no-syllabus-card p {
            color: var(--text-muted);
        }
    </style>
</head>



<body>
    <div id="navbar"></div>

    <section class="course-detail-hero">
        <div class="container">
            <span class="hero-badge">
                <?php echo htmlspecialchars($categoryLabel); ?>
            </span>
            <h1>
                <?php echo htmlspecialchars($courseTitle); ?>
            </h1>
            <p>
                <?php echo htmlspecialchars($courseDescription); ?>
            </p>
        </div>
    </section>

    <section class="syllabus-section">
        <div class="container">
            <div class="section-header center">
                <h2 class="section-title-large">Course <span class="primary-text">Syllabus</span></h2>
                <div class="section-divider"></div>
            </div>

            <?php if (count($syllabusData) > 0): ?>
                <div class="syllabus-grid">
                    <?php foreach ($syllabusData as $index => $module): ?>
                        <div class="syllabus-module">
                            <div class="syllabus-module-number">
                                <?php echo $index + 1; ?>
                            </div>
                            <h3>
                                <?php echo htmlspecialchars($module['title'] ?? 'Module ' . ($index + 1)); ?>
                            </h3>
                            <?php if (!empty($module['points'])): ?>
                                <ul>
                                    <?php foreach ($module['points'] as $point): ?>
                                        <li>
                                            <?php echo htmlspecialchars($point); ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-syllabus">
                    <div class="no-syllabus-card">
                        <h3>Syllabus Coming Soon</h3>
                        <p>The detailed syllabus for this course will be available soon. Please contact us for more
                            information about this course.</p>
                        <a href="courses.php" class="btn-primary" style="display: inline-block; margin-top: 1.5rem;">Browse
                            All Courses</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="course-cta">
        <div class="container">
            <h2>Ready to Start Learning?</h2>
            <p>Join thousands of students who have transformed their careers with our expert-led training.</p>
            <button class="btn-primary" id="courseEnrollBtn">Enroll Now</button>
        </div>
    </section>

    <div id="footer"></div>

    <script src="js/components.js"></script>
    <script src="js/main.js"></script>
    <script src="js/components.js"></script>
    <script src="js/main.js"></script>
    <script>
        // Make the course Enroll Now button open the enquiry modal
        document.addEventListener('DOMContentLoaded', function() {
            const courseEnrollBtn = document.getElementById('courseEnrollBtn');
            if (courseEnrollBtn) {
                courseEnrollBtn.addEventListener('click', function() {
                    const enquiryModal = document.getElementById('enquiryModalOverlay');
                    if (enquiryModal) {
                        enquiryModal.classList.add('active');
                        document.body.style.overflow = 'hidden';
                        
                        // Pre-fill the course interest if available
                        const courseTitle = <?php echo json_encode($courseTitle); ?>;
                        const courseSelect = document.getElementById('course_interest');
                        if (courseSelect && courseTitle) {
                            // Try to match course title or set to "Other Inquiry"
                            const matchingOption = Array.from(courseSelect.options).find(opt => 
                                opt.text.toLowerCase().includes(courseTitle.toLowerCase().split(' ')[0])
                            );
                            if (matchingOption) {
                                courseSelect.value = matchingOption.value;
                            }
                        }
                    }
                });
            }
        });
    </script>
</body>
</body>

</html>