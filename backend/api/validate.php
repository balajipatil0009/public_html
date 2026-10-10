<?php
header('Content-Type: application/json');
require_once '../config/config.php';
require_once '../config/database.php';

$certificateId = $_GET['id'] ?? '';

if (empty($certificateId)) {
    echo json_encode(['success' => false, 'message' => 'No certificate ID provided']);
    exit();
}

try {
    $database = new Database();
    $db = $database->connect();

    if (!$db) {
        throw new Exception("Database connection failed");
    }

    // Search for certificate in internship_enrollments
    $stmt = $db->prepare("SELECT e.id as enroll_id, e.roll_no as id, i.full_name as name, p.title as course, e.certificate_date as date 
                          FROM internship_enrollments e
                          JOIN interns i ON e.intern_id = i.id
                          JOIN internships p ON e.internship_id = p.id
                          WHERE e.roll_no = :roll AND e.certificate_issued = 1
                          LIMIT 1");
    $stmt->execute(['roll' => $certificateId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$result) {
        // Search in course_students
        $stmt = $db->prepare("SELECT cs.id as enroll_id, cs.roll_no as id, cs.full_name as name, 
                              CASE WHEN cs.course_id = -1 THEN cs.other_course_name ELSE c.title END as course, 
                              cs.enrollment_date as date 
                              FROM course_students cs
                              LEFT JOIN courses c ON cs.course_id = c.id
                              WHERE cs.roll_no = :roll
                              LIMIT 1");
        $stmt->execute(['roll' => $certificateId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    if ($result) {
        echo json_encode([
            'success' => true,
            'data' => [
                'enroll_id' => $result['enroll_id'],
                'id' => $result['id'],
                'name' => $result['name'],
                'course' => $result['course'],
                'date' => $result['date'] ? date('d M Y', strtotime($result['date'])) : 'N/A'
            ]
        ]);

    } else {
        echo json_encode(['success' => false, 'message' => 'No matching record found in our database.']);
    }

} catch (Exception $e) {
    error_log("Validation API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An internal error occurred. Please try again later.']);
}
