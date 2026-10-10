<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

session_start();
if (!isset($_SESSION['intern_id'])) {
    http_response_code(403);
    die(json_encode(['error' => 'Unauthorized']));
}

// Handle both regular POST and sendBeacon requests
$rawData = file_get_contents('php://input');
$data = json_decode($rawData, true);

if (!$data) {
    http_response_code(400);
    die(json_encode(['error' => 'Invalid data']));
}

$intern_id = $_SESSION['intern_id'];
$video_id = (int) $data['video_id'];
$seconds = (int) $data['seconds']; // Current time in video
$total = (int) $data['total'];     // Total duration

$database = new Database();
$db = $database->connect();

// Verify video ownership/enrollment
$stmt = $db->prepare("SELECT v.id FROM internship_videos v 
                      JOIN internship_enrollments e ON v.internship_id = e.internship_id 
                      WHERE v.id = :vid AND e.intern_id = :iid AND e.enrollment_status = 'active'");
$stmt->execute(['vid' => $video_id, 'iid' => $intern_id]);

if ($stmt->fetch()) {
    // Update progress - track the maximum watched position
    // This ensures we always know the furthest point the user has reached
    $stmt = $db->prepare("INSERT INTO intern_video_progress (intern_id, video_id, seconds_watched, total_seconds) 
                          VALUES (:iid, :vid, :sec, :tot) 
                          ON DUPLICATE KEY UPDATE 
                          seconds_watched = GREATEST(seconds_watched, VALUES(seconds_watched)),
                          total_seconds = VALUES(total_seconds),
                          updated_at = CURRENT_TIMESTAMP");

    if ($stmt->execute(['iid' => $intern_id, 'vid' => $video_id, 'sec' => $seconds, 'tot' => $total])) {
        // If watched more than 98% of the video, mark as completed
        if ($total > 0 && ($seconds / $total) >= 0.98) {
            $db->prepare("UPDATE intern_video_progress SET is_completed = 1 WHERE intern_id = :iid AND video_id = :vid")
                ->execute(['iid' => $intern_id, 'vid' => $video_id]);
            echo json_encode(['status' => 'completed', 'seconds' => $seconds, 'total' => $total]);
        } else {
            echo json_encode(['status' => 'updated', 'progress' => round(($seconds / $total) * 100) . '%', 'seconds' => $seconds]);
        }
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update progress']);
    }
} else {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
}
?>