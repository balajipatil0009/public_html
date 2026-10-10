<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/auth.php';

$auth = new Auth();
$auth->requireLogin();

// Only super_admin can run migrations
if (!$auth->hasRole('super_admin')) {
    die('Access denied. Only super administrators can run migrations.');
}

$database = new Database();
$db = $database->connect();

if (!$db) {
    die('Database connection failed.');
}

$success = true;
$messages = [];

try {
    // Check and add queries field to internship_enrollments
    $stmt = $db->query("SHOW COLUMNS FROM internship_enrollments LIKE 'queries'");
    if ($stmt->rowCount() == 0) {
        $db->exec("ALTER TABLE internship_enrollments 
                   ADD COLUMN queries TEXT NULL COMMENT 'Student queries from application form' AFTER amount_paid");
        $messages[] = "✓ Added 'queries' column to internship_enrollments";
    } else {
        $messages[] = "• Column 'queries' already exists in internship_enrollments";
    }

    // Check and add admin_notes field
    $stmt = $db->query("SHOW COLUMNS FROM internship_enrollments LIKE 'admin_notes'");
    if ($stmt->rowCount() == 0) {
        $db->exec("ALTER TABLE internship_enrollments 
                   ADD COLUMN admin_notes TEXT NULL COMMENT 'Admin notes about the application' AFTER queries");
        $messages[] = "✓ Added 'admin_notes' column to internship_enrollments";
    } else {
        $messages[] = "• Column 'admin_notes' already exists in internship_enrollments";
    }

    // Check and add reviewed_by field
    $stmt = $db->query("SHOW COLUMNS FROM internship_enrollments LIKE 'reviewed_by'");
    if ($stmt->rowCount() == 0) {
        $db->exec("ALTER TABLE internship_enrollments 
                   ADD COLUMN reviewed_by INT NULL COMMENT 'Admin user who reviewed' AFTER admin_notes");
        $messages[] = "✓ Added 'reviewed_by' column to internship_enrollments";
    } else {
        $messages[] = "• Column 'reviewed_by' already exists in internship_enrollments";
    }

    // Check and add reviewed_at field
    $stmt = $db->query("SHOW COLUMNS FROM internship_enrollments LIKE 'reviewed_at'");
    if ($stmt->rowCount() == 0) {
        $db->exec("ALTER TABLE internship_enrollments 
                   ADD COLUMN reviewed_at DATETIME NULL COMMENT 'When application was reviewed' AFTER reviewed_by");
        $messages[] = "✓ Added 'reviewed_at' column to internship_enrollments";
    } else {
        $messages[] = "• Column 'reviewed_at' already exists in internship_enrollments";
    }

    // Add fields to interns table
    $internsFields = [
        'gender' => "VARCHAR(20) NULL AFTER full_name",
        'college_name' => "VARCHAR(255) NULL AFTER phone",
        'academic_degree' => "VARCHAR(100) NULL AFTER college_name",
        'current_year' => "VARCHAR(20) NULL AFTER academic_degree",
        'country' => "VARCHAR(100) DEFAULT 'INDIA' AFTER current_year",
        'source' => "VARCHAR(100) NULL AFTER country"
    ];

    foreach ($internsFields as $field => $definition) {
        $stmt = $db->query("SHOW COLUMNS FROM interns LIKE '$field'");
        if ($stmt->rowCount() == 0) {
            $db->exec("ALTER TABLE interns ADD COLUMN $field $definition");
            $messages[] = "✓ Added '$field' column to interns";
        } else {
            $messages[] = "• Column '$field' already exists in interns";
        }
    }

    // Create indexes for better performance
    try {
        $db->exec("CREATE INDEX IF NOT EXISTS idx_enrollment_status ON internship_enrollments(enrollment_status, payment_status)");
        $messages[] = "✓ Created index on enrollment_status and payment_status";
    } catch (Exception $e) {
        $messages[] = "• Index on enrollment_status already exists";
    }

    try {
        $db->exec("CREATE INDEX IF NOT EXISTS idx_enrollment_date ON internship_enrollments(enrollment_date DESC)");
        $messages[] = "✓ Created index on enrollment_date";
    } catch (Exception $e) {
        $messages[] = "• Index on enrollment_date already exists";
    }

    $messages[] = "<br><strong>Migration completed successfully!</strong>";
    $messages[] = "Application management system is now ready to use.";

} catch (Exception $e) {
    $success = false;
    $messages[] = "✗ Error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enrollment Fields Migration</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #002147;
            margin-bottom: 20px;
        }
        .message {
            padding: 10px 15px;
            margin: 10px 0;
            border-radius: 4px;
            background: <?php echo $success ? '#e8f5e9' : '#ffebee'; ?>;
            border-left: 4px solid <?php echo $success ? '#4caf50' : '#f44336'; ?>;
        }
        .message-item {
            margin: 8px 0;
            color: #333;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background: #3db166;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }
        .back-link:hover {
            background: #2d9050;
        }
        .action-links {
            margin-top: 20px;
            display: flex;
            gap: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📋 Enrollment Fields Migration</h1>
        <div class="message">
            <?php foreach ($messages as $msg): ?>
                <div class="message-item"><?php echo $msg; ?></div>
            <?php endforeach; ?>
        </div>
        <div class="action-links">
            <a href="index.php" class="back-link">← Back to Dashboard</a>
            <a href="applications.php" class="back-link">View Applications</a>
        </div>
    </div>
</body>
</html>
