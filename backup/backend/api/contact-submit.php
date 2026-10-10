<?php
// API endpoint for contact form submission
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate required fields
$name = sanitize($input['name'] ?? '');
$email = sanitize($input['email'] ?? '');
$message = sanitize($input['message'] ?? '');
$phone = sanitize($input['phone'] ?? '');
$course_interest = sanitize($input['course_interest'] ?? '');

// Validation
$errors = [];

if (empty($name)) {
    $errors[] = 'Name is required';
}

if (empty($email)) {
    $errors[] = 'Email is required';
} elseif (!validateEmail($email)) {
    $errors[] = 'Invalid email format';
}

if (empty($message)) {
    $errors[] = 'Message is required';
}

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
    exit();
}

// Insert into database
try {
    $database = new Database();
    $db = $database->connect();

    $query = "INSERT INTO contact_inquiries (name, email, phone, course_interest, message, status) 
              VALUES (:name, :email, :phone, :course_interest, :message, 'new')";

    $stmt = $db->prepare($query);
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':phone', $phone);
    $stmt->bindParam(':course_interest', $course_interest);
    $stmt->bindParam(':message', $message);

    if ($stmt->execute()) {
        // Send email notification
        require_once '../includes/Mailer.php';
        $mailer = new Mailer();
        $mailer->sendEnquiryNotification($name, $email, $phone, $course_interest, $message);

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Thank you for contacting us! We will get back to you soon.'
        ]);
    } else {
        throw new Exception('Failed to save inquiry');
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred. Please try again later.'
    ]);
}
?>