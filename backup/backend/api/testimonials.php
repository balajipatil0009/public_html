<?php
// API endpoint to fetch testimonials
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
    
    // Get featured or all testimonials
    $featured = isset($_GET['featured']) && $_GET['featured'] === 'true';
    
    $query = "SELECT * FROM testimonials WHERE is_active = 1";
    if ($featured) {
        $query .= " AND is_featured = 1";
    }
    $query .= " ORDER BY display_order ASC, created_at DESC";
    
    // Limit results if specified
    if (isset($_GET['limit'])) {
        $limit = (int)$_GET['limit'];
        $query .= " LIMIT $limit";
    }
    
    $stmt = $db->query($query);
    $testimonials = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'data' => $testimonials]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
?>
