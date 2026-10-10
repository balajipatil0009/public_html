<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

session_start();
if (!isset($_SESSION['intern_id'])) {
    http_response_code(403);
    die("Unauthorized");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $intern_id = $_SESSION['intern_id'];
    $video_id = (int) $_POST['video_id'];

    $database = new Database();
    $db = $database->connect();

    // Verify video belongs to intern's internship
    $stmt = $db->prepare("SELECT v.id FROM internship_videos v 
                          JOIN internship_enrollments e ON v.internship_id = e.internship_id 
                          WHERE v.id = :vid AND e.intern_id = :iid AND e.enrollment_status = 'active'");
    $stmt->execute(['vid' => $video_id, 'iid' => $intern_id]);

    if ($stmt->fetch()) {
        $stmt = $db->prepare("INSERT INTO intern_video_progress (intern_id, video_id, is_completed) 
                              VALUES (:iid, :vid, 1) 
                              ON DUPLICATE KEY UPDATE is_completed = 1");
        if ($stmt->execute(['iid' => $intern_id, 'vid' => $video_id])) {
            setSuccessMessage("Video marked as completed!");
        }
    }

    header('Location: ../../intern-dashboard.php?video=' . $video_id);
    exit();
}
?>