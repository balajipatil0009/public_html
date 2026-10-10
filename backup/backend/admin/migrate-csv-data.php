<?php
/**
 * Data Migration Script: CSV to Database
 * 
 * This script migrates old student data from CSV into the new system.
 * - Courses go to `course_students` table
 * - Internships go to `interns` and `internship_enrollments` tables
 * 
 * Access via: http://localhost:8080/backend/admin/migrate-csv-data.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$pageTitle = 'Data Migration';
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$auth = new Auth();
$auth->requireLogin();

$database = new Database();
$db = $database->connect();

// Define course mappings (CSV name => system slug/category)
$courseMappings = [
    'Embedded C Programming' => ['slug' => 'embedded-c-programming', 'category' => 'programming'],
    'Emebeded C Program' => ['slug' => 'embedded-c-programming', 'category' => 'programming'],
    'Web Development Workshop' => ['slug' => 'web-development-workshop', 'category' => 'web_dev'],
    'Digital marketing & graphic design' => ['slug' => 'digital-marketing-graphic', 'category' => 'design'],
    'Digital marketing' => ['slug' => 'digital-marketing', 'category' => 'design'],
    'Graphic design' => ['slug' => 'graphic-design', 'category' => 'design'],
    'Graphic Design' => ['slug' => 'graphic-design', 'category' => 'design'],
    'Frotend Development' => ['slug' => 'frontend-development', 'category' => 'web_dev'],
    'Frontend Development' => ['slug' => 'frontend-development', 'category' => 'web_dev'],
    'Core Java' => ['slug' => 'core-java', 'category' => 'programming'],
    'Graphic design+VFX' => ['slug' => 'graphic-design-vfx', 'category' => 'design'],
    'Premiere pro + photoshop' => ['slug' => 'premiere-pro-photoshop', 'category' => 'design'],
    'Data Science' => ['slug' => 'data-science', 'category' => 'database'],
    'Data Analytics' => ['slug' => 'data-analytics', 'category' => 'database'],
    'C Language' => ['slug' => 'c-language', 'category' => 'programming'],
    'Hardware Networking-CCNA' => ['slug' => 'hardware-networking-ccna', 'category' => 'other'],
    'C++ Language' => ['slug' => 'cpp-language', 'category' => 'programming'],
    'c++ Language' => ['slug' => 'cpp-language', 'category' => 'programming'],
    'Goggle Autocad' => ['slug' => 'google-autocad', 'category' => 'design'],
    'Advance Excel' => ['slug' => 'advance-excel', 'category' => 'other'],
    'Full Stack Java' => ['slug' => 'full-stack-java', 'category' => 'programming'],
    'MS-OFFICE' => ['slug' => 'ms-office', 'category' => 'other'],
    'Electrical AutoCAD' => ['slug' => 'electrical-autocad', 'category' => 'design'],
    'Python + AI' => ['slug' => 'python-ai', 'category' => 'programming'],
    'Cyber Security' => ['slug' => 'cyber-security', 'category' => 'other'],
];

// Define internship mappings
$internshipMappings = [
    'Frotend Development Internship' => ['slug' => 'frontend-development-internship', 'title' => 'Frontend Development Internship'],
    'Data science Internship' => ['slug' => 'data-science-internship', 'title' => 'Data Science Internship'],
    'Data Analytics Internship' => ['slug' => 'data-analytics-internship', 'title' => 'Data Analytics Internship'],
    'Full Stack Java Internship' => ['slug' => 'full-stack-java-internship', 'title' => 'Full Stack Java Internship'],
    'Internship of AI-ML' => ['slug' => 'ai-ml-internship', 'title' => 'AI-ML Internship'],
    'Internship of cyber security' => ['slug' => 'cyber-security-internship', 'title' => 'Cyber Security Internship'],
    'Internship of web development' => ['slug' => 'web-development-internship', 'title' => 'Web Development Internship'],
];

$csvPath = __DIR__ . '/../../sunilnalawade2001@gmail.com - Sheet1.csv';

$migrationResults = [
    'courses_added' => 0,
    'internships_added' => 0,
    'course_students_migrated' => 0,
    'interns_migrated' => 0,
    'errors' => [],
    'skipped' => []
];

// Only run migration when form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_migration'])) {

    try {
        $db->beginTransaction();

        // Read CSV file
        if (!file_exists($csvPath)) {
            throw new Exception("CSV file not found at: $csvPath");
        }

        $csvData = [];
        if (($handle = fopen($csvPath, "r")) !== FALSE) {
            $lineNumber = 0;
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $lineNumber++;
                // Skip header rows (first 2 lines)
                if ($lineNumber <= 2)
                    continue;

                // Skip empty or malformed rows
                if (count($data) < 6 || empty(trim($data[1])))
                    continue;

                $csvData[] = [
                    'sr_no' => trim($data[0]),
                    'candidate_name' => trim($data[1]),
                    'course_name' => trim($data[2]),
                    'candidate_number' => trim($data[3]),
                    'certificate_date' => trim($data[4]),
                    'certificate_number' => trim($data[5])
                ];
            }
            fclose($handle);
        }

        // Step 1: Add missing courses
        foreach ($courseMappings as $courseName => $info) {
            $stmt = $db->prepare("SELECT id FROM courses WHERE slug = :slug");
            $stmt->execute([':slug' => $info['slug']]);
            if (!$stmt->fetch()) {
                $insertStmt = $db->prepare("INSERT INTO courses (slug, title, short_description, category, duration, is_active) VALUES (:slug, :title, :desc, :cat, :dur, 1)");
                $insertStmt->execute([
                    ':slug' => $info['slug'],
                    ':title' => $courseName,
                    ':desc' => 'Migrated course from legacy data',
                    ':cat' => $info['category'],
                    ':dur' => '1 Month'
                ]);
                $migrationResults['courses_added']++;
            }
        }

        // Step 2: Add missing internships
        foreach ($internshipMappings as $intName => $info) {
            $stmt = $db->prepare("SELECT id FROM internships WHERE slug = :slug");
            $stmt->execute([':slug' => $info['slug']]);
            if (!$stmt->fetch()) {
                $insertStmt = $db->prepare("INSERT INTO internships (slug, title, description, duration, fees, is_active) VALUES (:slug, :title, :desc, :dur, :fees, 1)");
                $insertStmt->execute([
                    ':slug' => $info['slug'],
                    ':title' => $info['title'],
                    ':desc' => 'Migrated internship from legacy data',
                    ':dur' => '1 Month',
                    ':fees' => 0
                ]);
                $migrationResults['internships_added']++;
            }
        }

        // Create cache of course/internship IDs
        $courseCache = [];
        $stmt = $db->query("SELECT id, slug, title FROM courses");
        foreach ($stmt->fetchAll() as $row) {
            $courseCache[strtolower($row['slug'])] = $row['id'];
            $courseCache[strtolower($row['title'])] = $row['id'];
        }

        $internshipCache = [];
        $stmt = $db->query("SELECT id, slug, title FROM internships");
        foreach ($stmt->fetchAll() as $row) {
            $internshipCache[strtolower($row['slug'])] = $row['id'];
            $internshipCache[strtolower($row['title'])] = $row['id'];
        }

        // Step 3: Migrate data
        foreach ($csvData as $row) {
            $courseName = $row['course_name'];
            $candidateName = $row['candidate_name'];
            $candidateNumber = $row['candidate_number'];
            $certDate = $row['certificate_date'];
            $certNumber = $row['certificate_number'];

            // Determine if this is an internship
            $isInternship = (stripos($courseName, 'Internship') !== false);

            if ($isInternship) {
                // Handle internship migration
                $intInfo = $internshipMappings[$courseName] ?? null;
                if (!$intInfo) {
                    $migrationResults['skipped'][] = "Unknown internship: $courseName for $candidateName";
                    continue;
                }

                $internshipId = $internshipCache[strtolower($intInfo['slug'])] ?? null;
                if (!$internshipId) {
                    $migrationResults['errors'][] = "Internship not found: {$intInfo['slug']} for $candidateName";
                    continue;
                }

                // Generate placeholder email from name
                $emailSlug = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $candidateName));
                $email = $emailSlug . '_' . substr(md5($candidateNumber), 0, 6) . '@placeholder.itinfo.local';

                // Check if intern exists
                $stmt = $db->prepare("SELECT id FROM interns WHERE email = :email");
                $stmt->execute([':email' => $email]);
                $intern = $stmt->fetch();

                if (!$intern) {
                    // Create intern
                    $insertStmt = $db->prepare("INSERT INTO interns (full_name, email, password, phone, is_active) VALUES (:name, :email, :pass, :phone, 1)");
                    $insertStmt->execute([
                        ':name' => $candidateName,
                        ':email' => $email,
                        ':pass' => password_hash('changeme123', PASSWORD_DEFAULT),
                        ':phone' => '0000000000'
                    ]);
                    $internId = $db->lastInsertId();
                } else {
                    $internId = $intern['id'];
                }

                // Parse certificate date
                $certDateParsed = null;
                if (!empty($certDate) && $certDate !== 'C') {
                    $certDateParsed = date('Y-m-d', strtotime(str_replace('-', ' ', $certDate)));
                }

                // Create enrollment
                $enrollStmt = $db->prepare("INSERT INTO internship_enrollments 
                    (intern_id, internship_id, payment_method, payment_status, enrollment_status, amount_paid, certificate_issued, certificate_date) 
                    VALUES (:intern_id, :int_id, 'cash', 'completed', 'completed', 0, :cert_issued, :cert_date)
                    ON DUPLICATE KEY UPDATE internship_id = internship_id");
                $enrollStmt->execute([
                    ':intern_id' => $internId,
                    ':int_id' => $internshipId,
                    ':cert_issued' => !empty($certDateParsed) ? 1 : 0,
                    ':cert_date' => $certDateParsed
                ]);

                $migrationResults['interns_migrated']++;

            } else {
                // Handle course migration
                $courseInfo = $courseMappings[$courseName] ?? null;
                if (!$courseInfo) {
                    $migrationResults['skipped'][] = "Unknown course: $courseName for $candidateName";
                    continue;
                }

                $courseId = $courseCache[strtolower($courseInfo['slug'])] ?? null;
                if (!$courseId) {
                    $migrationResults['errors'][] = "Course not found: {$courseInfo['slug']} for $candidateName";
                    continue;
                }

                // Generate placeholder email
                $emailSlug = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $candidateName));
                $email = $emailSlug . '_' . substr(md5($candidateNumber), 0, 6) . '@placeholder.itinfo.local';

                // Parse enrollment date from candidate number or use current
                $enrollmentDate = date('Y-m-d');
                if (preg_match('/ITINFO-(\d{2})-(\d{2})-/', $candidateNumber, $matches)) {
                    $year = (int) $matches[1] < 50 ? '20' . $matches[1] : '19' . $matches[1];
                    $month = $matches[2];
                    $enrollmentDate = "$year-$month-01";
                } elseif (preg_match('/ITINFO-(\d{4})-(\d{2})-/', $candidateNumber, $matches)) {
                    $enrollmentDate = "{$matches[1]}-{$matches[2]}-01";
                }

                // Generate new roll number format
                $seq = str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
                $edate = strtotime($enrollmentDate);
                $day = date('d', $edate);
                $month = date('m', $edate);
                $courseCode = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $courseInfo['slug']), 0, 3));
                $rollNo = "IT-INFO-{$seq}-{$day}-{$month}-{$courseCode}";

                // Check for duplicate roll number
                $checkStmt = $db->prepare("SELECT id FROM course_students WHERE roll_no = :roll");
                $checkStmt->execute([':roll' => $rollNo]);
                if ($checkStmt->fetch()) {
                    $rollNo .= '-' . substr(md5($candidateNumber), 0, 4);
                }

                // Insert course student
                $insertStmt = $db->prepare("INSERT INTO course_students 
                    (full_name, email, phone, gender, college_name, academic_degree, current_year, course_id, enrollment_date, total_amount, amount_paid, roll_no, is_active)
                    VALUES (:name, :email, :phone, :gender, :college, :degree, :year, :course_id, :enroll_date, 0, 0, :roll_no, 1)");

                try {
                    $insertStmt->execute([
                        ':name' => $candidateName,
                        ':email' => $email,
                        ':phone' => '0000000000',
                        ':gender' => 'Not Specified',
                        ':college' => 'Migrated - Not Specified',
                        ':degree' => 'Not Specified',
                        ':year' => 'N/A',
                        ':course_id' => $courseId,
                        ':enroll_date' => $enrollmentDate,
                        ':roll_no' => $rollNo
                    ]);
                    $migrationResults['course_students_migrated']++;
                } catch (PDOException $e) {
                    if (strpos($e->getMessage(), 'Duplicate') !== false) {
                        $migrationResults['skipped'][] = "Duplicate: $candidateName ($rollNo)";
                    } else {
                        $migrationResults['errors'][] = "Error inserting $candidateName: " . $e->getMessage();
                    }
                }
            }
        }

        $db->commit();
        $migrationSuccess = true;

    } catch (Exception $e) {
        $db->rollBack();
        $migrationSuccess = false;
        $migrationResults['errors'][] = "Fatal error: " . $e->getMessage();
    }
}

include 'includes/header.php';
?>

<div class="content-section">
    <h2>CSV Data Migration Tool</h2>

    <div style="background: #f3f4f6; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">
        <p><strong>CSV File:</strong>
            <?php echo htmlspecialchars($csvPath); ?>
        </p>
        <p><strong>File Exists:</strong>
            <?php echo file_exists($csvPath) ? '✓ Yes' : '✗ No'; ?>
        </p>
    </div>

    <?php if (isset($migrationSuccess)): ?>
        <div
            style="background: <?php echo $migrationSuccess ? '#d1fae5' : '#fee2e2'; ?>; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">
            <h3 style="margin-top: 0;">
                <?php echo $migrationSuccess ? '✓ Migration Complete' : '✗ Migration Failed'; ?>
            </h3>

            <ul>
                <li><strong>Courses Added:</strong>
                    <?php echo $migrationResults['courses_added']; ?>
                </li>
                <li><strong>Internships Added:</strong>
                    <?php echo $migrationResults['internships_added']; ?>
                </li>
                <li><strong>Course Students Migrated:</strong>
                    <?php echo $migrationResults['course_students_migrated']; ?>
                </li>
                <li><strong>Interns Migrated:</strong>
                    <?php echo $migrationResults['interns_migrated']; ?>
                </li>
            </ul>

            <?php if (!empty($migrationResults['errors'])): ?>
                <h4>Errors:</h4>
                <ul style="color: #dc2626;">
                    <?php foreach ($migrationResults['errors'] as $err): ?>
                        <li>
                            <?php echo htmlspecialchars($err); ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <?php if (!empty($migrationResults['skipped'])): ?>
                <h4>Skipped (
                    <?php echo count($migrationResults['skipped']); ?>):
                </h4>
                <details>
                    <summary>Click to expand</summary>
                    <ul style="color: #d97706; max-height: 300px; overflow-y: auto;">
                        <?php foreach ($migrationResults['skipped'] as $skip): ?>
                            <li>
                                <?php echo htmlspecialchars($skip); ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </details>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <form method="POST"
        onsubmit="return confirm('Are you sure you want to run the migration? This will add records to the database.');">
        <button type="submit" name="run_migration" class="btn btn-primary"
            style="padding: 1rem 2rem; font-size: 1.1rem;">
            🚀 Run Migration
        </button>
    </form>

    <div style="margin-top: 2rem;">
        <h3>Migration Details</h3>
        <p>This migration will:</p>
        <ol>
            <li>Add missing courses to the <code>courses</code> table</li>
            <li>Add missing internships to the <code>internships</code> table</li>
            <li>Migrate course completion records to <code>course_students</code></li>
            <li>Migrate internship completion records to <code>interns</code> and <code>internship_enrollments</code>
            </li>
        </ol>

        <p><strong>Placeholder values used:</strong></p>
        <ul>
            <li>Email: <code>{name}_{hash}@placeholder.itinfo.local</code></li>
            <li>Phone: <code>0000000000</code></li>
            <li>College: <code>Migrated - Not Specified</code></li>
            <li>Gender: <code>Not Specified</code></li>
        </ul>
    </div>
</div>

<?php include 'includes/footer.php'; ?>