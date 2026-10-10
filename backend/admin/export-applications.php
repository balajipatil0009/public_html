<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/auth.php';

$auth = new Auth();
$auth->requireLogin();

$database = new Database();
$db = $database->connect();

// Fetch all applications
$query = "SELECT 
            e.id,
            e.enrollment_date,
            e.enrollment_status,
            e.payment_status,
            e.payment_method,
            e.amount_paid,
            e.transaction_id,
            e.queries,
            e.admin_notes,
            i.full_name,
            i.email,
            i.phone,
            i.gender,
            i.college_name,
            i.academic_degree,
            i.current_year,
            i.country,
            i.source,
            p.title as internship_title,
            p.duration as internship_duration,
            p.fees as internship_fees,
            a.username as reviewed_by
          FROM internship_enrollments e
          JOIN interns i ON e.intern_id = i.id
          JOIN internships p ON e.internship_id = p.id
          LEFT JOIN admin_users a ON e.reviewed_by = a.id
          ORDER BY e.enrollment_date DESC";

$stmt = $db->query($query);
$applications = $stmt->fetchAll();

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=internship_applications_' . date('Y-m-d') . '.csv');

// Create output stream
$output = fopen('php://output', 'w');

// Add BOM for Excel UTF-8 support
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Add CSV headers
fputcsv($output, [
    'Application ID',
    'Application Date',
    'Student Name',
    'Email',
    'Phone',
    'Gender',
    'College Name',
    'Academic Degree',
    'Current Year',
    'Country',
    'Source',
    'Internship Program',
    'Duration',
    'Fees',
    'Amount Paid',
    'Payment Method',
    'Payment Status',
    'Transaction ID',
    'Enrollment Status',
    'Student Queries',
    'Admin Notes',
    'Reviewed By'
]);

// Add data rows
foreach ($applications as $app) {
    fputcsv($output, [
        $app['id'],
        date('Y-m-d H:i:s', strtotime($app['enrollment_date'])),
        $app['full_name'],
        $app['email'],
        $app['phone'],
        $app['gender'],
        $app['college_name'],
        $app['academic_degree'],
        $app['current_year'],
        $app['country'],
        $app['source'],
        $app['internship_title'],
        $app['internship_duration'],
        $app['internship_fees'],
        $app['amount_paid'],
        ucfirst($app['payment_method']),
        ucfirst($app['payment_status']),
        $app['transaction_id'],
        ucfirst($app['enrollment_status']),
        $app['queries'],
        $app['admin_notes'],
        $app['reviewed_by']
    ]);
}

fclose($output);
exit();
