<?php
// API endpoint to fetch about sections
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
    
    // Get specific section by key or all sections
    if (isset($_GET['key'])) {
        $key = $_GET['key'];
        $query = "SELECT * FROM about_sections WHERE section_key = :key AND is_active = 1";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':key', $key);
        $stmt->execute();
        $section = $stmt->fetch();
        
        if ($section) {
            echo json_encode(['success' => true, 'data' => $section]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Section not found']);
        }
    } else {
        // Get all active sections
        $query = "SELECT * FROM about_sections WHERE is_active = 1 ORDER BY display_order ASC";
        $stmt = $db->query($query);
        $sections = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'data' => $sections]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
?>
