<?php
require_once '../config/config.php';
require_once '../config/database.php';

$database = new Database();
$db = $database->connect();

try {
    // 1. Update internship_videos table
    $db->exec("ALTER TABLE internship_videos 
               ADD COLUMN video_type ENUM('youtube', 'local') DEFAULT 'local' AFTER internship_id,
               ADD COLUMN local_path VARCHAR(255) NULL AFTER video_url,
               MODIFY COLUMN video_url VARCHAR(255) NULL");
    echo "1. internship_videos table updated.<br>";

    // 2. Update intern_video_progress table
    $db->exec("ALTER TABLE intern_video_progress 
               ADD COLUMN seconds_watched INT DEFAULT 0 AFTER watched_at,
               ADD COLUMN total_seconds INT DEFAULT 0 AFTER seconds_watched");
    echo "2. intern_video_progress table updated.<br>";

    echo "Migration completed successfully!";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>