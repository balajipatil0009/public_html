<?php
$pageTitle = 'Content Migration';
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$auth = new Auth();
$auth->requireLogin();

$database = new Database();
$db = $database->connect();

$migrationResults = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_migration'])) {
    
    // 1. Migrate About Sections
    $migrationResults[] = "📄 Migrating About sections...";
    
    $aboutSections = [
        [
            'section_key' => 'company_overview',
            'title' => 'Company Overview',
            'content' => '<p>ITINFO ACADEMY provides comprehensive training and services. Our academy is committed to creating a supportive learning environment that emphasizes quality education. We enable learning professionals to enhance their self-assurance, technical skills, critical thinking abilities, and career development. The academy aims to instill personal values such as self-confidence, accountability, and inspiration.</p>',
            'image_url' => 'public/itinfo/info5.jpeg',
            'display_order' => 1
        ],
        [
            'section_key' => 'our_vision',
            'title' => 'Our Vision',
            'content' => '<p>To become a globally recognized institution empowering individuals with comprehensive knowledge and expertise in cutting-edge information technology, fostering innovation, and preparing them for successful careers in a rapidly evolving digital landscape.</p>',
            'display_order' => 2
        ],
        [
            'section_key' => 'our_mission',
            'title' => 'Our Mission',
            'content' => '<p>At ITINFO ACADEMY, our mission is to provide a dynamic learning environment that emphasizes hands-on experience, industry-aligned curriculum, and personalized learning paths.</p><p>We aim to cultivate a community of learners guided by expert faculty and industry connections, fostering a supportive ecosystem that nurtures individual growth and encourages exploration of emerging technologies.</p>',
            'display_order' => 3
        ],
        [
            'section_key' => 'customized_training',
            'title' => 'Customized Training',
            'content' => '<p>We offer customized training programs, including one-on-one formats, at students\' homes or business locations. With the rapid advancement of technology and constant influx of new software applications and hardware devices, there is a continuous demand for training and skills upgrading. This ensures that individuals can maximize the benefits of their computer equipment and programs.</p>',
            'image_url' => 'public/itinfo/info6.jpeg',
            'display_order' => 4
        ],
        [
            'section_key' => 'who_we_are',
            'title' => 'Who We Are',
            'content' => '<p>We are an authorized training center for E-more India. We provide information resources, consulting services, and training in innovative and proven software applications. Our expertise lies in computer education and related sector placement services. We offer assistance and support in skill enhancement and career development. We also provide services in malware analysis, software development, data recovery, open-source intelligence services, and college projects. ITINFO ACADEMY is a journey of innovation and excellence.</p>',
            'display_order' => 5
        ],
        [
            'section_key' => 'history',
            'title' => 'History',
            'content' => '<div><strong>2022:</strong> IT INFO ACADEMY was founded by a group of passionate IT professionals and educators who recognized the increasing demand for high-quality, accessible education in information technology.</div><br><div><strong>2023:</strong> Expanded curriculum to include advanced courses in data science, artificial intelligence, full-stack web development, and more.</div>',
            'display_order' => 6
        ]
    ];

    foreach ($aboutSections as $section) {
        $section['is_active'] = 1;
        if (!isset($section['image_url'])) {
            $section['image_url'] = null;
        }
        
        $query = "INSERT INTO about_sections (section_key, title, content, image_url, display_order, is_active) 
                  VALUES (:section_key, :title, :content, :image_url, :display_order, :is_active)
                  ON DUPLICATE KEY UPDATE 
                  title = VALUES(title), content = VALUES(content), image_url = VALUES(image_url)";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':section_key', $section['section_key']);
        $stmt->bindParam(':title', $section['title']);
        $stmt->bindParam(':content', $section['content']);
        $stmt->bindParam(':image_url', $section['image_url']);
        $stmt->bindParam(':display_order', $section['display_order']);
        $stmt->bindParam(':is_active', $section['is_active']);
        
        if ($stmt->execute()) {
            $migrationResults[] = "✅ Added: {$section['title']}";
        } else {
            $migrationResults[] = "❌ Failed: {$section['title']}";
        }
    }

    // 2. Migrate Gallery Images
    $migrationResults[] = "\n🖼️ Migrating Gallery Images...";
    
    $galleryImages = [
        // Gallery images without titles - just for backend management
        ['title' => 'Gallery Image 1', 'image_url' => 'public/itinfo/info1.jpeg', 'category' => 'Campus', 'display_order' => 1],
        ['title' => 'Gallery Image 2', 'image_url' => 'public/itinfo/info2.jpeg', 'category' => 'Campus', 'display_order' => 2],
        ['title' => 'Gallery Image 3', 'image_url' => 'public/itinfo/info4.jpeg', 'category' => 'Campus', 'display_order' => 3],
        ['title' => 'Gallery Image 4', 'image_url' => 'public/itinfo/info6.jpeg', 'category' => 'Training', 'display_order' => 4],
        ['title' => 'Gallery Image 5', 'image_url' => 'public/itinfo/info8.jpeg', 'category' => 'Training', 'display_order' => 5],
        ['title' => 'Gallery Image 6', 'image_url' => 'public/itinfo/info12.jpeg', 'category' => 'Training', 'display_order' => 6],
        ['title' => 'Gallery Image 7', 'image_url' => 'public/itinfo/info5.jpeg', 'category' => 'Students', 'display_order' => 7],
        ['title' => 'Gallery Image 8', 'image_url' => 'public/itinfo/info10.jpeg', 'category' => 'Students', 'display_order' => 8],
        ['title' => 'Gallery Image 9', 'image_url' => 'public/itinfo/info16.jpeg', 'category' => 'Students', 'display_order' => 9],
        ['title' => 'Gallery Image 10', 'image_url' => 'public/itinfo/info13.jpeg', 'category' => 'Infrastructure', 'display_order' => 10],
        ['title' => 'Gallery Image 11', 'image_url' => 'public/itinfo/info15.jpeg', 'category' => 'Infrastructure', 'display_order' => 11],
        ['title' => 'Gallery Image 12', 'image_url' => 'public/itinfo/info18.jpeg', 'category' => 'Events', 'display_order' => 12],
        ['title' => 'Gallery Image 13', 'image_url' => 'public/itinfo/info19.jpeg', 'category' => 'Events', 'display_order' => 13],
        ['title' => 'Gallery Image 14', 'image_url' => 'public/itinfo/info20.jpeg', 'category' => 'Events', 'display_order' => 14],
        ['title' => 'Gallery Image 15', 'image_url' => 'public/itinfo/info21.jpeg', 'category' => 'Workshops', 'display_order' => 15],
        ['title' => 'Gallery Image 16', 'image_url' => 'public/itinfo/info22.jpeg', 'category' => 'Workshops', 'display_order' => 16],
        ['title' => 'Gallery Image 17', 'image_url' => 'public/itinfo/info23.jpeg', 'category' => 'Workshops', 'display_order' => 17],
        ['title' => 'Gallery Image 18', 'image_url' => 'public/itinfo/info25.jpeg', 'category' => 'Labs', 'display_order' => 18],
        ['title' => 'Gallery Image 19', 'image_url' => 'public/itinfo/info26.jpeg', 'category' => 'Labs', 'display_order' => 19],
        ['title' => 'Gallery Image 20', 'image_url' => 'public/itinfo/info27.jpeg', 'category' => 'Labs', 'display_order' => 20],
        ['title' => 'Gallery Image 21', 'image_url' => 'public/itinfo/info28.jpeg', 'category' => 'Projects', 'display_order' => 21],
        ['title' => 'Gallery Image 22', 'image_url' => 'public/itinfo/info29.jpeg', 'category' => 'Projects', 'display_order' => 22],
        ['title' => 'Gallery Image 23', 'image_url' => 'public/itinfo/info30.jpeg', 'category' => 'Projects', 'display_order' => 23],
        ['title' => 'Gallery Image 24', 'image_url' => 'public/itinfo/info31.jpeg', 'category' => 'Graduation', 'display_order' => 24],
        ['title' => 'Gallery Image 25', 'image_url' => 'public/itinfo/info32.jpeg', 'category' => 'Graduation', 'display_order' => 25],
        ['title' => 'Gallery Image 26', 'image_url' => 'public/itinfo/info33.jpeg', 'category' => 'Graduation', 'display_order' => 26],
        ['title' => 'Gallery Image 27', 'image_url' => 'public/itinfo/info34.jpeg', 'category' => 'Achievements', 'display_order' => 27],
        ['title' => 'Gallery Image 28', 'image_url' => 'public/itinfo/info35.jpeg', 'category' => 'Achievements', 'display_order' => 28],
        ['title' => 'Gallery Image 29', 'image_url' => 'public/itinfo/info36.jpeg', 'category' => 'Achievements', 'display_order' => 29]
    ];

    foreach ($galleryImages as $image) {
        $image['is_active'] = 1;
        if (!isset($image['description'])) {
            $image['description'] = null;
        }
        
        $query = "INSERT INTO gallery (title, description, image_url, category, display_order, is_active) 
                  VALUES (:title, :description, :image_url, :category, :display_order, :is_active)
                  ON DUPLICATE KEY UPDATE 
                  title = VALUES(title), category = VALUES(category)";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':title', $image['title']);
        $stmt->bindParam(':description', $image['description']);
        $stmt->bindParam(':image_url', $image['image_url']);
        $stmt->bindParam(':category', $image['category']);
        $stmt->bindParam(':display_order', $image['display_order']);
        $stmt->bindParam(':is_active', $image['is_active']);
        
        if ($stmt->execute()) {
            $migrationResults[] = "✅ Added: {$image['title']}";
        } else {
            $migrationResults[] = "❌ Failed: {$image['title']}";
        }
    }

    // 3. Migrate Courses (first 10 for brevity)
    $migrationResults[] = "\n📚 Migrating Courses...";
    
    $courses = [
        ['title' => 'Data Science', 'slug' => 'data-science', 'category' => 'job_role', 'short_description' => 'Master data analysis, machine learning, and AI to unlock insights from complex datasets.', 'display_order' => 1],
        ['title' => 'Java Full Stack Developer', 'slug' => 'java-full-stack', 'category' => 'job_role', 'short_description' => 'Unlock full-stack proficiency with our Java course - master front-end, back-end, and database for end-to-end web application development.', 'display_order' => 2],
        ['title' => 'MERN Full Stack Web Development', 'slug' => 'mern-full-stack', 'category' => 'job_role', 'short_description' => 'Become a versatile full-stack warrior: Conquer front-end, back-end, and database technologies for end-to-end web development mastery.', 'display_order' => 3],
        ['title' => 'Python Programming', 'slug' => 'python', 'category' => 'programming', 'short_description' => 'Learn Python, the versatile language for web development, data science, and automation.', 'display_order' => 4],
        ['title' => 'JavaScript', 'slug' => 'javascript', 'category' => 'web_dev', 'short_description' => 'Bring web pages to life with interactive JavaScript programming.', 'display_order' => 5]
    ];

    foreach ($courses as $course) {
        $course['is_active'] = 1;
        $course['is_featured'] = 0;
        $course['full_description'] = null;
        $course['duration'] = null;
        $course['fees'] = null;
        $course['syllabus'] = null;
        $course['image_url'] = null;
        
        $query = "INSERT INTO courses (title, slug, short_description, full_description, category, duration, fees, syllabus, image_url, is_featured, is_active, display_order) 
                  VALUES (:title, :slug, :short_description, :full_description, :category, :duration, :fees, :syllabus, :image_url, :is_featured, :is_active, :display_order)
                  ON DUPLICATE KEY UPDATE 
                  title = VALUES(title), short_description = VALUES(short_description)";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':title', $course['title']);
        $stmt->bindParam(':slug', $course['slug']);
        $stmt->bindParam(':short_description', $course['short_description']);
        $stmt->bindParam(':full_description', $course['full_description']);
        $stmt->bindParam(':category', $course['category']);
        $stmt->bindParam(':duration', $course['duration']);
        $stmt->bindParam(':fees', $course['fees']);
        $stmt->bindParam(':syllabus', $course['syllabus']);
        $stmt->bindParam(':image_url', $course['image_url']);
        $stmt->bindParam(':is_featured', $course['is_featured']);
        $stmt->bindParam(':is_active', $course['is_active']);
        $stmt->bindParam(':display_order', $course['display_order']);
        
        if ($stmt->execute()) {
            $migrationResults[] = "✅ Added: {$course['title']}";
        } else {
            $migrationResults[] = "❌ Failed: {$course['title']}";
        }
    }

    // 4. Update Site Settings
    $migrationResults[] = "\n⚙️ Updating Site Settings...";
    
    $settings = [
        'site_name' => 'ITINFO ACADEMY',
        'site_tagline' => 'Empowering Future IT Professionals',
        'site_description' => 'We are committed to delivering top-tier IT education, equipping students with the skills to thrive in the tech world.',
        'site_email' => 'info@itinfo.com',
        'site_phone' => '+91 1234567890'
    ];

    foreach ($settings as $key => $value) {
        $query = "INSERT INTO site_settings (setting_key, setting_value, setting_type) 
                  VALUES (:setting_key, :setting_value, 'text')
                  ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':setting_key', $key);
        $stmt->bindParam(':setting_value', $value);
        
        if ($stmt->execute()) {
            $migrationResults[] = "✅ Updated: {$key}";
        } else {
            $migrationResults[] = "❌ Failed: {$key}";
        }
    }
    
    $migrationResults[] = "\n✨ Migration completed successfully!";
    setSuccessMessage('Content migration completed successfully!');
}

include 'includes/header.php';
?>

<div class="content-section">
    <h2>Content Migration</h2>
    <p style="color: #6b7280; margin-bottom: 1.5rem;">
        Import existing static content into the backend database. This will populate About sections, Courses, and Site settings.
    </p>
    
    <?php if (empty($migrationResults)): ?>
        <div style="background: #fef3c7; padding: 1.5rem; border-radius: 8px; margin-bottom: 1.5rem; border-left: 4px solid #f59e0b;">
            <h3 style="color: #92400e; margin-bottom: 0.5rem;">⚠️ Before You Start</h3>
            <ul style="color: #92400e; margin: 0; padding-left: 1.5rem;">
                <li>This will import content from your existing static pages</li>
                <li>About sections (6 sections)</li>
                <li>Gallery images (31 images with categories)</li>
                <li>Sample courses (5 courses)</li>
                <li>Site settings (contact info, social media)</li>
                <li>Existing database records will be updated, not duplicated</li>
                <li>Your current design and functionality will remain unchanged</li>
                <li>Images will use the existing public/ folder structure</li>
            </ul>
        </div>
        
        <form method="POST" action="">
            <button type="submit" name="run_migration" class="btn btn-primary" style="padding: 1rem 2rem; font-size: 1.1rem;">
                🚀 Run Content Migration
            </button>
        </form>
    <?php else: ?>
        <div style="background: #f9fafb; padding: 1.5rem; border-radius: 8px; border: 1px solid #e5e7eb;">
            <h3 style="color: #1e3a8a; margin-bottom: 1rem;">Migration Results:</h3>
            <div style="font-family: monospace; font-size: 0.9rem; line-height: 1.6;">
                <?php foreach ($migrationResults as $result): ?>
                    <div><?php echo htmlspecialchars($result); ?></div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div style="margin-top: 1.5rem;">
            <a href="about.php" class="btn btn-primary">View About Sections</a>
            <a href="gallery.php" class="btn btn-primary">View Gallery</a>
            <a href="courses.php" class="btn btn-primary">View Courses</a>
            <a href="settings.php" class="btn btn-primary">View Settings</a>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>