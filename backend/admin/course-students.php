<?php
$pageTitle = 'Course Students';
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$auth = new Auth();
$auth->requireLogin();

$database = new Database();
$db = $database->connect();

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $stmt = $db->prepare("DELETE FROM course_students WHERE id = :id");
    $stmt->bindParam(':id', $id);
    if ($stmt->execute()) {
        setSuccessMessage('Student record deleted successfully');
    } else {
        setErrorMessage('Failed to delete student record');
    }
    header('Location: course-students.php');
    exit();
}

// Handle toggle active
if (isset($_GET['toggle'])) {
    $id = (int) $_GET['toggle'];
    $stmt = $db->prepare("UPDATE course_students SET is_active = NOT is_active WHERE id = :id");
    $stmt->bindParam(':id', $id);
    if ($stmt->execute()) {
        setSuccessMessage('Student status updated successfully');
    }
    header('Location: course-students.php');
    exit();
}

// Handle generate roll number
if (isset($_GET['generate_roll'])) {
    $id = (int) $_GET['generate_roll'];

    // Get student data
    $stmt = $db->prepare("SELECT cs.*, c.title as course_title 
                          FROM course_students cs
                          LEFT JOIN courses c ON cs.course_id = c.id
                          WHERE cs.id = :id AND cs.roll_no IS NULL");
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $student = $stmt->fetch();

    if ($student) {
        // Generate roll number
        $edate = strtotime($student['enrollment_date']);
        $seq = str_pad($student['id'], 3, '0', STR_PAD_LEFT);
        $day = date('d', $edate);
        $month = date('m', $edate);

        // Course code: First 3 letters of course title (or from other_course_name if Others)
        $title_for_code = ($student['course_id'] == -1) ? $student['other_course_name'] : ($student['course_title'] ?? 'CRS');
        $course_code = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $title_for_code), 0, 3));

        $roll_no = "IT-INFO-{$seq}-{$day}-{$month}-{$course_code}";

        // Update the record
        $updateStmt = $db->prepare("UPDATE course_students SET roll_no = :roll_no WHERE id = :id");
        $updateStmt->bindParam(':roll_no', $roll_no);
        $updateStmt->bindParam(':id', $id);

        if ($updateStmt->execute()) {
            setSuccessMessage("Roll number generated: {$roll_no}");
        } else {
            setErrorMessage('Failed to generate roll number');
        }
    } else {
        setErrorMessage('Student not found or roll number already exists');
    }
    header('Location: course-students.php');
    exit();
}

// Get filters
$courseFilter = $_GET['course'] ?? 'all';
$statusFilter = $_GET['status'] ?? 'all';

// Build query
$query = "SELECT cs.*, c.title as course_title, c.duration as course_duration 
          FROM course_students cs
          LEFT JOIN courses c ON cs.course_id = c.id
          WHERE 1=1";

$params = [];

if ($courseFilter !== 'all') {
    $query .= " AND cs.course_id = :course_id";
    $params['course_id'] = (int) $courseFilter;
}

if ($statusFilter !== 'all') {
    $query .= " AND cs.is_active = :is_active";
    $params['is_active'] = ($statusFilter === 'active') ? 1 : 0;
}

$query .= " ORDER BY cs.enrollment_date DESC";

$stmt = $db->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue(':' . $key, $value);
}
$stmt->execute();
$students = $stmt->fetchAll();

// Get all courses for filter dropdown
$coursesStmt = $db->query("SELECT id, title FROM courses WHERE is_active = 1 ORDER BY title ASC");
$courses = $coursesStmt->fetchAll();

// Get counts
$totalCount = $db->query("SELECT COUNT(*) FROM course_students")->fetchColumn();
$activeCount = $db->query("SELECT COUNT(*) FROM course_students WHERE is_active = 1")->fetchColumn();
$inactiveCount = $db->query("SELECT COUNT(*) FROM course_students WHERE is_active = 0")->fetchColumn();

include 'includes/header.php';
?>

<div class="content-section">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <h2>Manage Course Students</h2>
        <a href="course-student-form.php" class="btn btn-primary">+ Add New Student</a>
    </div>

    <!-- Filters -->
    <div style="display: flex; gap: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap; align-items: center;">
        <!-- Status Filter -->
        <div>
            <a href="?course=<?php echo $courseFilter; ?>&status=all"
                class="btn btn-sm <?php echo $statusFilter === 'all' ? 'btn-primary' : ''; ?>">
                All (<?php echo $totalCount; ?>)
            </a>
            <a href="?course=<?php echo $courseFilter; ?>&status=active"
                class="btn btn-sm <?php echo $statusFilter === 'active' ? 'btn-primary' : ''; ?>">
                Active (<?php echo $activeCount; ?>)
            </a>
            <a href="?course=<?php echo $courseFilter; ?>&status=inactive"
                class="btn btn-sm <?php echo $statusFilter === 'inactive' ? 'btn-primary' : ''; ?>">
                Inactive (<?php echo $inactiveCount; ?>)
            </a>
        </div>

        <!-- Course Filter -->
        <div style="margin-left: auto;">
            <select onchange="window.location.href='?course='+this.value+'&status=<?php echo $statusFilter; ?>'"
                class="form-control" style="min-width: 200px;">
                <option value="all" <?php echo $courseFilter === 'all' ? 'selected' : ''; ?>>All Courses</option>
                <?php foreach ($courses as $course): ?>
                    <option value="<?php echo $course['id']; ?>" <?php echo $courseFilter == $course['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($course['title']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Enrollment Date</th>
                    <th>Student Info</th>
                    <th>Course & Duration</th>
                    <th>Expected Completion</th>
                    <th>Roll Number</th>
                    <th>Payment Status</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($students) > 0): ?>
                    <?php foreach ($students as $student): ?>
                        <tr>
                            <td>
                                <?php echo date('d M Y', strtotime($student['enrollment_date'])); ?>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($student['full_name']); ?></strong><br>
                                <small style="color: #6b7280;">
                                    <?php echo htmlspecialchars($student['email']); ?> |
                                    <?php echo htmlspecialchars($student['phone']); ?>
                                </small><br>
                                <small style="color: #6b7280;">
                                    <?php echo htmlspecialchars($student['college_name']); ?>
                                    (<?php echo htmlspecialchars($student['academic_degree']); ?> -
                                    <?php echo htmlspecialchars($student['current_year']); ?>)
                                </small>
                            </td>
                            <td>
                                <?php if ($student['course_id'] == -1): ?>
                                    <span class="badge badge-primary">Others</span><br>
                                    <small><?php echo htmlspecialchars($student['other_course_name']); ?></small>
                                <?php else: ?>
                                    <strong><?php echo htmlspecialchars($student['course_title'] ?? 'N/A'); ?></strong><br>
                                    <small
                                        style="color: #6b7280;"><?php echo htmlspecialchars($student['course_duration'] ?? '1 Month'); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $dur = ($student['course_id'] == -1) ? '1 Month' : ($student['course_duration'] ?? '1 Month');
                                $start = new DateTime($student['enrollment_date']);
                                $end = clone $start;

                                $dur_clean = strtolower($dur);
                                if (strpos($dur_clean, 'week') !== false) {
                                    $w = (int) filter_var($dur_clean, FILTER_SANITIZE_NUMBER_INT) ?: 1;
                                    $end->modify("+{$w} weeks");
                                } elseif (strpos($dur_clean, 'month') !== false) {
                                    $m = (int) filter_var($dur_clean, FILTER_SANITIZE_NUMBER_INT) ?: 1;
                                    $end->modify("+{$m} months");
                                } elseif (strpos($dur_clean, 'day') !== false) {
                                    $d = (int) filter_var($dur_clean, FILTER_SANITIZE_NUMBER_INT) ?: 1;
                                    $end->modify("+{$d} days");
                                } else {
                                    $end->modify("+1 month");
                                }

                                echo $end->format('d M Y');

                                if (new DateTime() < $end): ?>
                                    <br><small class="badge badge-closed" style="font-size: 0.7rem; padding: 2px 5px;">In
                                        Progress</small>
                                <?php else: ?>
                                    <br><small class="badge badge-converted"
                                        style="font-size: 0.7rem; padding: 2px 5px;">Completed</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($student['roll_no'])): ?>
                                    <span style="font-family: 'Courier New', monospace; font-weight: 600; color: #002147;">
                                        <?php echo htmlspecialchars($student['roll_no']); ?>
                                    </span>
                                <?php else: ?>
                                    <a href="?generate_roll=<?php echo $student['id']; ?>" class="btn btn-sm btn-success"
                                        onclick="return confirm('Generate roll number for this student?')">
                                        Generate
                                    </a>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="font-size: 0.9rem;">
                                    Total: ₹<?php echo number_format($student['total_amount'], 2); ?><br>
                                    Paid: ₹<?php echo number_format($student['amount_paid'], 2); ?><br>
                                    <?php
                                    $pending = $student['total_amount'] - $student['amount_paid'];
                                    if ($pending > 0): ?>
                                        <span style="color: #ef4444; font-weight: 600;">
                                            Pending: ₹<?php echo number_format($pending, 2); ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="color: #3db166; font-weight: 600;">Fully Paid</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <span class="badge <?php echo $student['is_active'] ? 'badge-converted' : 'badge-closed'; ?>">
                                    <?php echo $student['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td class="action-buttons">
                                <a href="course-student-form.php?id=<?php echo $student['id']; ?>"
                                    class="btn btn-sm btn-primary">Edit</a>
                                <a href="?toggle=<?php echo $student['id']; ?>" class="btn btn-sm btn-success">Toggle</a>
                                <a href="?delete=<?php echo $student['id']; ?>" class="btn btn-sm btn-danger"
                                    onclick="return confirm('Are you sure you want to delete this student record?')">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align: center;">No course students found. <a
                                href="course-student-form.php">Add the first student</a></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>