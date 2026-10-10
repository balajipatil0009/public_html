<?php
require_once __DIR__ . '/../config/database.php';

$database = new Database();
$db = $database->connect();

if (!$db) {
    die("Database connection failed.");
}

$sqlFile = __DIR__ . '/../database/internship_schema.sql';
if (!file_exists($sqlFile)) {
    die("SQL file not found.");
}

$sql = file_get_contents($sqlFile);

try {
    $db->exec($sql);
    echo "Migration successful!";
} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage();
}
?>