<?php
/**
 * Syllabus Migration Script
 * Migrates syllabus data from old HTML files to the courses table
 * 
 * Usage: Access this file through browser when logged in as admin
 * Source: C:\Users\Admin\Downloads\Itinfo-main\Itinfo-main\courses
 */

// Enable error display for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/auth.php';

$auth = new Auth();
$auth->requireLogin();

$database = new Database();
$db = $database->connect();

if (!$db) {
    die("Database connection failed. Please check your database configuration.");
}

// Source folder containing HTML files
$sourceFolder = 'C:/Users/Admin/Downloads/Itinfo-main/Itinfo-main/courses';

// Mapping: HTML filename (without .html) => [slug, title, short_description, category]
// Slugs are matched to existing database entries
$courseMapping = [
    'illustrator' => [
        'slug' => 'illustrator',  // Matches existing DB
        'title' => 'Adobe Illustrator',
        'short_description' => 'Create stunning vector graphics and illustrations with Adobe Illustrator.',
        'category' => 'design'
    ],
    'photoshop' => [
        'slug' => 'photoshop',  // Matches existing DB
        'title' => 'Adobe Photoshop',
        'short_description' => 'Master photo editing and digital image manipulation with Photoshop.',
        'category' => 'design'
    ],
    'indesign' => [
        'slug' => 'indesign',  // Matches existing DB
        'title' => 'Adobe InDesign',
        'short_description' => 'Design professional layouts for print and digital publishing with InDesign.',
        'category' => 'design'
    ],
    'coreldraw' => [
        'slug' => 'coreldraw',
        'title' => 'CorelDRAW',
        'short_description' => 'Create professional vector graphics and designs with CorelDRAW.',
        'category' => 'design'
    ],
    'autocad' => [
        'slug' => 'autocad',
        'title' => 'AutoCAD',
        'short_description' => 'Master 2D and 3D design with industry-standard AutoCAD software.',
        'category' => 'design'
    ],
    'sketchup' => [
        'slug' => 'sketchup',
        'title' => 'SketchUp',
        'short_description' => 'Create 3D models and architectural designs with SketchUp.',
        'category' => 'design'
    ],
    'javaFullStack' => [
        'slug' => 'java-full-stack',
        'title' => 'Java Full Stack Developer',
        'short_description' => 'Unlock full-stack proficiency with Java - master front-end, back-end, and database for end-to-end web application development.',
        'category' => 'job_role'
    ],
    'MERN-full-stack' => [
        'slug' => 'mern-full-stack',
        'title' => 'MERN Full Stack Developer',
        'short_description' => 'Build modern web applications with MongoDB, Express.js, React, and Node.js.',
        'category' => 'job_role'
    ],
    'python' => [
        'slug' => 'python',
        'title' => 'Python Programming',
        'short_description' => 'Learn Python, the versatile language for web development, data science, and automation.',
        'category' => 'programming'
    ],
    'react' => [
        'slug' => 'react',
        'title' => 'React.js',
        'short_description' => 'Build modern, interactive user interfaces with React.js library.',
        'category' => 'web_dev'
    ],
    'angular' => [
        'slug' => 'angular',
        'title' => 'Angular',
        'short_description' => 'Build powerful single-page applications with Angular framework.',
        'category' => 'web_dev'
    ],
    'nodejs' => [
        'slug' => 'nodejs',
        'title' => 'Node.js',
        'short_description' => 'Build scalable server-side applications with Node.js runtime.',
        'category' => 'web_dev'
    ],
    'flutter' => [
        'slug' => 'flutter',
        'title' => 'Flutter',
        'short_description' => 'Build beautiful native mobile apps for iOS and Android with Flutter.',
        'category' => 'programming'
    ],
    'c' => [
        'slug' => 'c-language',  // Matches existing DB
        'title' => 'C Language',
        'short_description' => 'Learn the fundamentals of programming with C language.',
        'category' => 'programming'
    ],
    'cpp' => [
        'slug' => 'cpp-language',  // Matches existing DB
        'title' => 'C++ Language',
        'short_description' => 'Master object-oriented programming with C++.',
        'category' => 'programming'
    ],
    'csharp' => [
        'slug' => 'csharp',
        'title' => 'C# Programming',
        'short_description' => 'Learn C# for Windows application and game development.',
        'category' => 'programming'
    ],
    'core-java' => [
        'slug' => 'core-java',
        'title' => 'Core Java',
        'short_description' => 'Master the fundamentals of Java programming language.',
        'category' => 'programming'
    ],
    'javascript' => [
        'slug' => 'javascript',
        'title' => 'JavaScript',
        'short_description' => 'Learn JavaScript for interactive web development.',
        'category' => 'programming'
    ],
    'html' => [
        'slug' => 'html',
        'title' => 'HTML',
        'short_description' => 'Learn the foundational markup language for web pages.',
        'category' => 'web_dev'
    ],
    'css' => [
        'slug' => 'css',
        'title' => 'CSS',
        'short_description' => 'Master styling and layout techniques for modern web design.',
        'category' => 'web_dev'
    ],
    'php' => [
        'slug' => 'php',
        'title' => 'PHP',
        'short_description' => 'Build dynamic websites and web applications with PHP.',
        'category' => 'web_dev'
    ],
    'aspnet' => [
        'slug' => 'aspnet',
        'title' => 'ASP.NET',
        'short_description' => 'Build enterprise web applications with Microsoft ASP.NET framework.',
        'category' => 'web_dev'
    ],
    'mysql' => [
        'slug' => 'mysql',
        'title' => 'MySQL',
        'short_description' => 'Master relational database management with MySQL.',
        'category' => 'database'
    ],
    'mongodb' => [
        'slug' => 'mongodb',
        'title' => 'MongoDB',
        'short_description' => 'Learn NoSQL database management with MongoDB.',
        'category' => 'database'
    ],
    'sql' => [
        'slug' => 'sql',
        'title' => 'SQL',
        'short_description' => 'Master structured query language for database management.',
        'category' => 'database'
    ],
    'dataSci' => [
        'slug' => 'data-science',
        'title' => 'Data Science',
        'short_description' => 'Learn data analysis, visualization, and machine learning techniques.',
        'category' => 'job_role'
    ],
    'cyber-security' => [
        'slug' => 'cyber-security',
        'title' => 'Cyber Security',
        'short_description' => 'Learn to protect systems and networks from cyber threats.',
        'category' => 'job_role'
    ],
    'ethical-hacking' => [
        'slug' => 'ethical-hacking',
        'title' => 'Ethical Hacking',
        'short_description' => 'Learn penetration testing and ethical hacking techniques.',
        'category' => 'job_role'
    ],
    'software-testing' => [
        'slug' => 'software-testing',
        'title' => 'Software Testing',
        'short_description' => 'Master manual and automated software testing methodologies.',
        'category' => 'job_role'
    ],
    'hardware-networking' => [
        'slug' => 'hardware-networking',
        'title' => 'Hardware & Networking',
        'short_description' => 'Learn computer hardware and network administration.',
        'category' => 'job_role'
    ]
];

$results = [];
$errors = [];
$successCount = 0;
$skipCount = 0;
$errorCount = 0;

/**
 * Extract syllabus JSON from HTML file
 */
function extractSyllabusFromHtml($filePath)
{
    $content = file_get_contents($filePath);
    if ($content === false) {
        return null;
    }

    // Find the syllabusData array in the script
    // Pattern matches: const syllabusData = [...];
    if (preg_match('/const\s+syllabusData\s*=\s*(\[[\s\S]*?\]);/m', $content, $matches)) {
        $jsonString = $matches[1];

        // Clean up the JSON string (handle any JS-specific syntax)
        // Replace single quotes with double quotes if any
        // Note: The source files already use proper JSON format

        $data = json_decode($jsonString, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $data;
        }
    }

    return null;
}

// Process migration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_migration'])) {

    foreach ($courseMapping as $fileName => $courseInfo) {
        $htmlFile = $sourceFolder . '/' . $fileName . '.html';

        if (!file_exists($htmlFile)) {
            $errors[] = "File not found: {$fileName}.html";
            $errorCount++;
            continue;
        }

        $syllabusData = extractSyllabusFromHtml($htmlFile);

        if ($syllabusData === null) {
            $errors[] = "Could not extract syllabus from: {$fileName}.html";
            $errorCount++;
            continue;
        }

        $syllabusJson = json_encode($syllabusData, JSON_UNESCAPED_UNICODE);

        try {
            // Check if course exists
            $checkStmt = $db->prepare("SELECT id, title FROM courses WHERE slug = :slug");
            $checkStmt->bindParam(':slug', $courseInfo['slug']);
            $checkStmt->execute();
            $existingCourse = $checkStmt->fetch();

            if ($existingCourse) {
                // Update existing course syllabus
                $updateStmt = $db->prepare("UPDATE courses SET syllabus = :syllabus WHERE slug = :slug");
                $updateStmt->bindParam(':syllabus', $syllabusJson);
                $updateStmt->bindParam(':slug', $courseInfo['slug']);
                $updateStmt->execute();

                $results[] = [
                    'status' => 'updated',
                    'file' => $fileName . '.html',
                    'course' => $existingCourse['title'],
                    'slug' => $courseInfo['slug'],
                    'modules' => count($syllabusData)
                ];
                $successCount++;
            } else {
                // Insert new course
                $insertStmt = $db->prepare("
                    INSERT INTO courses (slug, title, short_description, category, syllabus, is_active) 
                    VALUES (:slug, :title, :short_description, :category, :syllabus, 1)
                ");
                $insertStmt->bindParam(':slug', $courseInfo['slug']);
                $insertStmt->bindParam(':title', $courseInfo['title']);
                $insertStmt->bindParam(':short_description', $courseInfo['short_description']);
                $insertStmt->bindParam(':category', $courseInfo['category']);
                $insertStmt->bindParam(':syllabus', $syllabusJson);
                $insertStmt->execute();

                $results[] = [
                    'status' => 'created',
                    'file' => $fileName . '.html',
                    'course' => $courseInfo['title'],
                    'slug' => $courseInfo['slug'],
                    'modules' => count($syllabusData)
                ];
                $successCount++;
            }
        } catch (PDOException $e) {
            $errors[] = "Database error for {$courseInfo['title']}: " . $e->getMessage();
            $errorCount++;
        }
    }
}

// Get existing courses for preview
$existingCourses = [];
try {
    $stmt = $db->query("SELECT slug, title, syllabus FROM courses ORDER BY title");
    while ($row = $stmt->fetch()) {
        $existingCourses[$row['slug']] = [
            'title' => $row['title'],
            'has_syllabus' => !empty($row['syllabus'])
        ];
    }
} catch (PDOException $e) {
    $errors[] = "Could not fetch existing courses: " . $e->getMessage();
}

// Count available source files
$availableFiles = [];
foreach ($courseMapping as $fileName => $info) {
    $filePath = $sourceFolder . '/' . $fileName . '.html';
    $availableFiles[$fileName] = file_exists($filePath);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Syllabus Migration - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            padding: 2rem;
            color: #333;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        h1 {
            color: #002147;
            margin-bottom: 0.5rem;
        }

        .subtitle {
            color: #666;
            margin-bottom: 2rem;
        }

        .card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .card h2 {
            color: #002147;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #3db166;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .stat-box {
            background: linear-gradient(135deg, #002147, #003366);
            color: white;
            padding: 1.5rem;
            border-radius: 8px;
            text-align: center;
        }

        .stat-box.success {
            background: linear-gradient(135deg, #3db166, #2d9254);
        }

        .stat-box.warning {
            background: linear-gradient(135deg, #f59e0b, #d97706);
        }

        .stat-box.error {
            background: linear-gradient(135deg, #ef4444, #dc2626);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
        }

        .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #002147;
        }

        tr:hover {
            background: #f8f9fa;
        }

        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .badge-success {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-warning {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-error {
            background: #fee2e2;
            color: #991b1b;
        }

        .badge-info {
            background: #dbeafe;
            color: #1e40af;
        }

        .badge-new {
            background: #e0e7ff;
            color: #3730a3;
        }

        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s;
        }

        .btn-primary {
            background: #3db166;
            color: white;
        }

        .btn-primary:hover {
            background: #2d9254;
        }

        .btn-secondary {
            background: #6b7280;
            color: white;
        }

        .btn-secondary:hover {
            background: #4b5563;
        }

        .alert {
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .alert-warning {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #fcd34d;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: #002147;
            text-decoration: none;
            margin-bottom: 1rem;
        }

        .back-link:hover {
            color: #3db166;
        }

        .icon-check {
            color: #3db166;
        }

        .icon-cross {
            color: #ef4444;
        }

        .icon-warning {
            color: #f59e0b;
        }
    </style>
</head>

<body>
    <div class="container">
        <a href="courses.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Courses
        </a>

        <h1><i class="fas fa-database"></i> Syllabus Migration Tool</h1>
        <p class="subtitle">Migrate syllabus data from HTML files to the database</p>

        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_migration'])): ?>
            <!-- Migration Results -->
            <div class="stats">
                <div class="stat-box success">
                    <div class="stat-number">
                        <?php echo $successCount; ?>
                    </div>
                    <div class="stat-label">Successful</div>
                </div>
                <div class="stat-box warning">
                    <div class="stat-number">
                        <?php echo $skipCount; ?>
                    </div>
                    <div class="stat-label">Skipped</div>
                </div>
                <div class="stat-box error">
                    <div class="stat-number">
                        <?php echo $errorCount; ?>
                    </div>
                    <div class="stat-label">Errors</div>
                </div>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="card">
                    <h2><i class="fas fa-exclamation-triangle"></i> Errors</h2>
                    <?php foreach ($errors as $error): ?>
                        <div class="alert alert-error">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($results)): ?>
                <div class="card">
                    <h2><i class="fas fa-check-circle"></i> Migration Results</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Status</th>
                                <th>Source File</th>
                                <th>Course Title</th>
                                <th>Slug</th>
                                <th>Modules</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($results as $result): ?>
                                <tr>
                                    <td>
                                        <?php if ($result['status'] === 'created'): ?>
                                            <span class="badge badge-new"><i class="fas fa-plus"></i> Created</span>
                                        <?php else: ?>
                                            <span class="badge badge-success"><i class="fas fa-sync"></i> Updated</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($result['file']); ?>
                                    </td>
                                    <td><strong>
                                            <?php echo htmlspecialchars($result['course']); ?>
                                        </strong></td>
                                    <td><code><?php echo htmlspecialchars($result['slug']); ?></code></td>
                                    <td>
                                        <?php echo $result['modules']; ?> modules
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <div style="margin-top: 1.5rem;">
                <a href="courses.php" class="btn btn-primary">
                    <i class="fas fa-list"></i> View All Courses
                </a>
                <a href="migrate-syllabus.php" class="btn btn-secondary">
                    <i class="fas fa-redo"></i> Run Again
                </a>
            </div>

        <?php else: ?>
            <!-- Preview Mode -->
            <div class="card">
                <h2><i class="fas fa-info-circle"></i> Migration Preview</h2>
                <p style="margin-bottom: 1rem;">
                    This tool will migrate syllabus data from <strong>30 HTML files</strong> to the courses database.
                </p>
                <div class="alert alert-warning">
                    <strong><i class="fas fa-exclamation-triangle"></i> Important:</strong>
                    This will update existing courses and create new ones if they don't exist.
                    Please review the mapping below before proceeding.
                </div>
            </div>

            <div class="card">
                <h2><i class="fas fa-exchange-alt"></i> Course Mapping (30 Courses)</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Source File</th>
                            <th>Course Title</th>
                            <th>Slug</th>
                            <th>Category</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($courseMapping as $fileName => $info): ?>
                            <?php
                            $fileExists = $availableFiles[$fileName];
                            $courseExists = isset($existingCourses[$info['slug']]);
                            $hasSyllabus = $courseExists && $existingCourses[$info['slug']]['has_syllabus'];
                            ?>
                            <tr>
                                <td>
                                    <?php if ($fileExists): ?>
                                        <i class="fas fa-check-circle icon-check"></i>
                                    <?php else: ?>
                                        <i class="fas fa-times-circle icon-cross"></i>
                                    <?php endif; ?>
                                    <?php echo htmlspecialchars($fileName); ?>.html
                                </td>
                                <td><strong>
                                        <?php echo htmlspecialchars($info['title']); ?>
                                    </strong></td>
                                <td><code><?php echo htmlspecialchars($info['slug']); ?></code></td>
                                <td>
                                    <span class="badge badge-info">
                                        <?php echo htmlspecialchars($info['category']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (!$fileExists): ?>
                                        <span class="badge badge-error">File Missing</span>
                                    <?php elseif (!$courseExists): ?>
                                        <span class="badge badge-new">Will Create</span>
                                    <?php elseif ($hasSyllabus): ?>
                                        <span class="badge badge-warning">Will Update</span>
                                    <?php else: ?>
                                        <span class="badge badge-success">Will Add Syllabus</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="card">
                <h2><i class="fas fa-rocket"></i> Start Migration</h2>
                <form method="post"
                    onsubmit="return confirm('Are you sure you want to proceed with the migration? This will update/create 30 courses.');">
                    <input type="hidden" name="confirm_migration" value="1">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-play"></i> Run Migration
                    </button>
                    <a href="courses.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </form>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>