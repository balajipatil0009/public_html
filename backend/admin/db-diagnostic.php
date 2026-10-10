<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$auth = new Auth();
$auth->requireLogin();

$pageTitle = 'Database Diagnostic';
include 'includes/header.php';

$database = new Database();
$db = $database->connect();

echo "<div class='content-section'>";
echo "<h2>Database Diagnostic Tool</h2>";

if (!$db) {
    echo "<div class='alert alert-error'>❌ Failed to connect to the database server. Check your config/database.php settings.</div>";
} else {
    try {
        // Get current database name
        $dbName = $db->query("SELECT DATABASE()")->fetchColumn();

        // List all tables
        $stmt = $db->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

        echo "<div style='background: #fdfdfd; padding: 2rem; border: 1px solid #eeeeee; border-radius: 4px;'>";
        echo "<p><strong>Current Database:</strong> <span style='color: #002147; font-family: monospace;'>$dbName</span></p>";
        echo "<p><strong>Tables found in this database:</strong></p>";

        if (empty($tables)) {
            echo "<p style='color: #999;'>No tables found.</p>";
        } else {
            echo "<ul style='columns: 2;'>";
            foreach ($tables as $table) {
                $statusIcon = ($table === 'course_students') ? '✅' : '•';
                $style = ($table === 'course_students') ? 'font-weight: bold; color: #3db166;' : '';
                echo "<li style='$style'>$statusIcon " . htmlspecialchars($table) . "</li>";
            }
            echo "</ul>";
        }

        if (in_array('course_students', $tables)) {
            echo "<div class='alert alert-success' style='margin-top: 2rem;'>✅ The table <b>course_students</b> is correctly present in this database.</div>";
        } else {
            echo "<div class='alert alert-error' style='margin-top: 2rem;'>❌ The table <b>course_students</b> is MISSING from this database.</div>";
            echo "<p style='margin-top: 1rem; color: #666;'><b>Likely Issue:</b> Your application is pointing to a remote database (FR-PARI1.WASMERNET.COM), but you might have run the SQL script against a local database (localhost).</p>";
            echo "<p><b>Solution:</b> Ensure your database tool (like phpMyAdmin or MySQL Workbench) is connected to the host specified in <code>config/database.php</code> before running the SQL.</p>";
        }
        echo "</div>";

    } catch (PDOException $e) {
        echo "<div class='alert alert-error'>❌ Error querying database: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

echo "</div>";
include 'includes/footer.php';
?>