<?php
ob_start();

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
$paymentConfig = require __DIR__ . '/../config/payment_config.php';

// Force error suppression again in case config.php enabled it
ini_set('display_errors', 0);
error_reporting(0);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$razorpay_payment_id = $input['razorpay_payment_id'] ?? '';
$razorpay_order_id = $input['razorpay_order_id'] ?? '';
$razorpay_signature = $input['razorpay_signature'] ?? '';
$internship_id = (int) ($input['internship_id'] ?? 0);
$full_name = sanitize($input['full_name'] ?? '');
$email = sanitize($input['email'] ?? '');
$gender = sanitize($input['gender'] ?? '');
$phone = sanitize($input['phone'] ?? '');
$college_name = sanitize($input['college_name'] ?? '');
$academic_degree = sanitize($input['academic_degree'] ?? '');
$current_year = sanitize($input['current_year'] ?? '');
$country = sanitize($input['country'] ?? 'INDIA');
$source = sanitize($input['source'] ?? '');
$queries = sanitize($input['queries'] ?? '');

if (empty($razorpay_payment_id) || empty($razorpay_order_id) || empty($razorpay_signature)) {
    echo json_encode(['success' => false, 'message' => 'Missing payment details']);
    exit;
}

// 1. Verify Signature
$key_secret = $paymentConfig['key_secret'];
$generated_signature = hash_hmac('sha256', $razorpay_order_id . "|" . $razorpay_payment_id, $key_secret);

if ($generated_signature !== $razorpay_signature) {
    echo json_encode(['success' => false, 'message' => 'Invalid payment signature']);
    exit;
}

$database = new Database();
$db = $database->connect();

if ($db === null) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

try {
    $db->beginTransaction();

    // 2. Fetch Fees
    $stmt = $db->prepare("SELECT fees FROM internships WHERE id = :id");
    $stmt->execute(['id' => $internship_id]);
    $internship = $stmt->fetch(PDO::FETCH_ASSOC);
    $amount = (float) $internship['fees'];

    // 3. Create or Get Intern
    $stmt = $db->prepare("SELECT id FROM interns WHERE email = :email");
    $stmt->execute(['email' => $email]);
    $intern = $stmt->fetch();

    if ($intern) {
        $intern_id = $intern['id'];
        // Update existing intern with new info
        $upd_stmt = $db->prepare("UPDATE interns SET 
                                  full_name = :name, 
                                  gender = :gender, 
                                  phone = :phone, 
                                  college_name = :college, 
                                  academic_degree = :degree, 
                                  current_year = :year, 
                                  country = :country, 
                                  source = :source 
                                  WHERE id = :id");
        $upd_stmt->execute([
            'name' => $full_name,
            'gender' => $gender,
            'phone' => $phone,
            'college' => $college_name,
            'degree' => $academic_degree,
            'year' => $current_year,
            'country' => $country,
            'source' => $source,
            'id' => $intern_id
        ]);
    } else {
        $placeholder = 'TEMP_' . bin2hex(random_bytes(8));
        $password = password_hash($placeholder, PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO interns (full_name, gender, email, phone, college_name, academic_degree, current_year, country, source, password) 
                              VALUES (:name, :gender, :email, :phone, :college, :degree, :year, :country, :source, :pass)");
        $stmt->execute([
            'name' => $full_name,
            'gender' => $gender,
            'email' => $email,
            'phone' => $phone,
            'college' => $college_name,
            'degree' => $academic_degree,
            'year' => $current_year,
            'country' => $country,
            'source' => $source,
            'pass' => $password
        ]);
        $intern_id = $db->lastInsertId();
    }

    // 4. Create Enrollment
    $stmt = $db->prepare("INSERT INTO internship_enrollments 
        (intern_id, internship_id, payment_method, payment_status, enrollment_status, amount_paid, transaction_id, queries) 
        VALUES (:iid, :pid, 'online', 'completed', 'active', :amount, :tid, :queries)");

    $stmt->execute([
        'iid' => $intern_id,
        'pid' => $internship_id,
        'amount' => $amount,
        'tid' => $razorpay_payment_id,
        'queries' => $queries
    ]);

    $db->commit();

    // Prepare redirect URL
    $redirect_url = 'complete-registration.php?email=' . urlencode($email);

    ob_clean();
    echo json_encode(['success' => true, 'redirect_url' => $redirect_url]);

} catch (Exception $e) {
    $db->rollBack();
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>