<?php
// API endpoint to fetch courses
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../config/config.php';
require_once '../config/database.php';

// Only accept GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

try {
    $database = new Database();
    $db = $database->connect();
    
    // Get specific course by slug or ID
    if (isset($_GET['slug'])) {
        $slug = $_GET['slug'];
        $query = "SELECT * FROM courses WHERE slug = :slug AND is_active = 1";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':slug', $slug);
        $stmt->execute();
        $course = $stmt->fetch();
        
        if ($course) {
            // Decode JSON syllabus
            if ($course['syllabus']) {
                $course['syllabus'] = json_decode($course['syllabus'], true);
            }
            echo json_encode(['success' => true, 'data' => $course]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Course not found']);
        }
    } elseif (isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        $query = "SELECT * FROM courses WHERE id = :id AND is_active = 1";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $course = $stmt->fetch();
        
        if ($course) {
            // Decode JSON syllabus
            if ($course['syllabus']) {
                $course['syllabus'] = json_decode($course['syllabus'], true);
            }
            echo json_encode(['success' => true, 'data' => $course]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Course not found']);
        }
    } else {
        // Get all courses or by category
        $query = "SELECT * FROM courses WHERE is_active = 1";
        
        if (isset($_GET['category'])) {
            $category = $_GET['category'];
            $query .= " AND category = :category";
        }
        
        if (isset($_GET['featured']) && $_GET['featured'] === 'true') {
            $query .= " AND is_featured = 1";
        }
        
        $query .= " ORDER BY display_order ASC, title ASC";
        
        // Limit results if specified
        if (isset($_GET['limit'])) {
            $limit = (int)$_GET['limit'];
            $query .= " LIMIT $limit";
        }
        
        $stmt = $db->prepare($query);
        if (isset($_GET['category'])) {
            $stmt->bindParam(':category', $category);
        }
        $stmt->execute();
        $courses = $stmt->fetchAll();
        
        // Decode JSON syllabus for each course
        foreach ($courses as &$course) {
            if ($course['syllabus']) {
                $course['syllabus'] = json_decode($course['syllabus'], true);
            }
        }
        
        echo json_encode(['success' => true, 'data' => $courses]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
?>
