<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$database = new Database();
$db = $database->connect();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $internship_id = (int) $_POST['internship_id'];
    $full_name = sanitize($_POST['full_name']);
    $email = sanitize($_POST['email']);
    $payment_method = sanitize($_POST['payment_method']);

    // New fields
    $gender = sanitize($_POST['gender'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $college_name = sanitize($_POST['college_name'] ?? '');
    $academic_degree = sanitize($_POST['academic_degree'] ?? '');
    $current_year = sanitize($_POST['current_year'] ?? '');
    $country = sanitize($_POST['country'] ?? 'INDIA');
    $source = sanitize($_POST['source'] ?? '');
    $queries = sanitize($_POST['queries'] ?? '');

    // Find internship fees
    $stmt = $db->prepare("SELECT title, fees FROM internships WHERE id = :id");
    $stmt->execute(['id' => $internship_id]);
    $internship = $stmt->fetch();

    if (!$internship) {
        setErrorMessage('Invalid internship selected.');
        header('Location: ../../internship.php');
        exit();
    }

    $amount = (float) $internship['fees'];

    // 1. Check if intern exists, else create
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
        // Create new intern with a placeholder password that MUST be changed
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

    // 2. Create Enrollment
    $payment_status = ($payment_method === 'online') ? 'completed' : 'pending';
    $enrollment_status = ($payment_method === 'online') ? 'active' : 'pending';

    $stmt = $db->prepare("INSERT INTO internship_enrollments (intern_id, internship_id, payment_method, payment_status, enrollment_status, amount_paid, queries) 
                          VALUES (:iid, :pid, :method, :pstatus, :estatus, :amount, :queries)");

    if (
        $stmt->execute([
            'iid' => $intern_id,
            'pid' => $internship_id,
            'method' => $payment_method,
            'pstatus' => $payment_status,
            'estatus' => $enrollment_status,
            'amount' => $amount,
            'queries' => $queries
        ])
    ) {
        if ($payment_method === 'online') {
            setSuccessMessage("Payment Successful! Now please set your password to complete your account.");
            header('Location: ../../complete-registration.php?email=' . urlencode($email));
        } else {
            setSuccessMessage("Application received! Once our admin approves your cash payment, you can set your password at the 'Member Login' section using your email.");
            header('Location: ../../internship.php');
        }
    } else {
        setErrorMessage('Failed to process enrollment. Please try again.');
        header('Location: ../../internship.php');
    }
    exit();
}
?>