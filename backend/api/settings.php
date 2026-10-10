<?php
// API endpoint to fetch site settings
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
    
    // Get specific setting by key or all settings
    if (isset($_GET['key'])) {
        $key = $_GET['key'];
        $query = "SELECT setting_value FROM site_settings WHERE setting_key = :key";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':key', $key);
        $stmt->execute();
        $setting = $stmt->fetch();
        
        if ($setting) {
            echo json_encode(['success' => true, 'data' => $setting['setting_value']]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Setting not found']);
        }
    } else {
        // Get all settings as key-value pairs
        $query = "SELECT setting_key, setting_value FROM site_settings";
        $stmt = $db->query($query);
        $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        echo json_encode(['success' => true, 'data' => $settings]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
?>
