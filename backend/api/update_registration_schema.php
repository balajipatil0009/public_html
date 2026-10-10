<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$database = new Database();
$db = $database->connect();

if (!$db) {
    // Try port 3307 which was seen in netstat
    try {
        $db = new PDO("mysql:host=localhost;port=3307;dbname=itinfo_academy", "root", "");
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        echo "Connected using port 3307.<br>";
    } catch (PDOException $e) {
        die("Could not connect to database on 3306 or 3307.");
    }
}
try {
    $db->exec("ALTER TABLE interns 
               ADD COLUMN IF NOT EXISTS gender VARCHAR(20) NULL AFTER full_name,
               ADD COLUMN IF NOT EXISTS phone VARCHAR(20) NULL AFTER email,
               ADD COLUMN IF NOT EXISTS college_name VARCHAR(255) NULL AFTER phone,
               ADD COLUMN IF NOT EXISTS academic_degree VARCHAR(100) NULL AFTER college_name,
               ADD COLUMN IF NOT EXISTS current_year VARCHAR(20) NULL AFTER academic_degree,
               ADD COLUMN IF NOT EXISTS country VARCHAR(100) DEFAULT 'INDIA' AFTER current_year,
               ADD COLUMN IF NOT EXISTS source VARCHAR(100) NULL AFTER country");
    echo "1. interns table updated with registration fields.<br>";

    // 2. Update internship_enrollments table
    $db->exec("ALTER TABLE internship_enrollments 
               ADD COLUMN IF NOT EXISTS roll_no VARCHAR(50) NULL AFTER transaction_id,
               ADD COLUMN IF NOT EXISTS queries TEXT NULL AFTER roll_no");
    echo "2. internship_enrollments table updated with roll_no and queries.<br>";

    echo "Registration schema migration completed successfully!";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>