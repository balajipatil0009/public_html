<?php
// API endpoint to fetch gallery images
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
    
    // Build query
    $query = "SELECT * FROM gallery WHERE is_active = 1";
    
    // Filter by category if specified
    if (isset($_GET['category'])) {
        $category = $_GET['category'];
        $query .= " AND category = :category";
    }
    
    $query .= " ORDER BY display_order ASC, created_at DESC";
    
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
    $images = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'data' => $images]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
?>
