<?php
$pageTitle = isset($_GET['id']) ? 'Edit Course Student' : 'Add Course Student';
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$auth = new Auth();
$auth->requireLogin();

$database = new Database();
$db = $database->connect();

$student = null;
$isEdit = false;

// Get student if editing
if (isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    $stmt = $db->prepare("SELECT * FROM course_students WHERE id = :id");
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $student = $stmt->fetch();
    $isEdit = true;
}

// Get all active courses for dropdown
$coursesStmt = $db->query("SELECT id, title FROM courses WHERE is_active = 1 ORDER BY title ASC");
$courses = $coursesStmt->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = sanitize($_POST['full_name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $gender = sanitize($_POST['gender']);
    $college_name = sanitize($_POST['college_name']);
    $academic_degree = sanitize($_POST['academic_degree']);
    $current_year = sanitize($_POST['current_year']);
    $course_id = (int) $_POST['course_id'];
    $other_course_name = sanitize($_POST['other_course_name'] ?? '');
    $amount_paid = (float) $_POST['amount_paid'];
    $total_amount = (float) $_POST['total_amount'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    // Validation
    $errors = [];
    if (empty($full_name))
        $errors[] = "Full name is required";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL))
        $errors[] = "Valid email is required";
    if (empty($phone))
        $errors[] = "Phone is required";
    if (empty($gender))
        $errors[] = "Gender is required";
    if (empty($college_name))
        $errors[] = "College name is required";
    if (empty($academic_degree))
        $errors[] = "Academic degree is required";
    if (empty($current_year))
        $errors[] = "Current year is required";
    if (empty($course_id))
        $errors[] = "Course selection is required";
    if ($course_id === -1 && empty($other_course_name))
        $errors[] = "Please specify the course name";
    if ($amount_paid < 0)
        $errors[] = "Amount paid cannot be negative";
    if ($total_amount <= 0)
        $errors[] = "Total amount must be greater than 0";
    if ($amount_paid > $total_amount)
        $errors[] = "Amount paid cannot be greater than total amount";

    if (empty($errors)) {
        if ($isEdit) {
            // Update existing student
            $query = "UPDATE course_students SET 
                      full_name = :full_name, 
                      email = :email, 
                      phone = :phone, 
                      gender = :gender, 
                      college_name = :college_name, 
                      academic_degree = :academic_degree, 
                      current_year = :current_year, 
                      course_id = :course_id, 
                      other_course_name = :other_course_name,
                      total_amount = :total_amount,
                      amount_paid = :amount_paid, 
                      is_active = :is_active 
                      WHERE id = :id";

            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $_GET['id']);
        } else {
            // Insert new student
            $query = "INSERT INTO course_students 
                      (full_name, email, phone, gender, college_name, academic_degree, current_year, course_id, other_course_name, total_amount, amount_paid, is_active) 
                      VALUES 
                      (:full_name, :email, :phone, :gender, :college_name, :academic_degree, :current_year, :course_id, :other_course_name, :total_amount, :amount_paid, :is_active)";

            $stmt = $db->prepare($query);
        }

        $stmt->bindParam(':full_name', $full_name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':phone', $phone);
        $stmt->bindParam(':gender', $gender);
        $stmt->bindParam(':college_name', $college_name);
        $stmt->bindParam(':academic_degree', $academic_degree);
        $stmt->bindParam(':current_year', $current_year);
        $stmt->bindParam(':course_id', $course_id);
        $stmt->bindParam(':other_course_name', $other_course_name);
        $stmt->bindParam(':total_amount', $total_amount);
        $stmt->bindParam(':amount_paid', $amount_paid);
        $stmt->bindParam(':is_active', $is_active);

        if ($stmt->execute()) {
            // If new student, generate roll number immediately
            if (!$isEdit) {
                $newId = $db->lastInsertId();

                // Get course title for roll number
                $courseStmt = $db->prepare("SELECT title FROM courses WHERE id = :id");
                $courseStmt->bindParam(':id', $course_id);
                $courseStmt->execute();
                $courseData = $courseStmt->fetch();

                // Generate roll number
                $seq = str_pad($newId, 3, '0', STR_PAD_LEFT);
                $day = date('d');
                $month = date('m');

                $title_for_code = ($course_id === -1) ? $other_course_name : ($courseData['title'] ?? 'CRS');
                $course_code = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $title_for_code), 0, 3));

                $roll_no = "IT-INFO-{$seq}-{$day}-{$month}-{$course_code}";

                // Update with roll number
                $updateRoll = $db->prepare("UPDATE course_students SET roll_no = :roll_no WHERE id = :id");
                $updateRoll->bindParam(':roll_no', $roll_no);
                $updateRoll->bindParam(':id', $newId);
                $updateRoll->execute();

                setSuccessMessage("Student added successfully. Roll Number: {$roll_no}");
            } else {
                setSuccessMessage('Student updated successfully');
            }
            header('Location: course-students.php');
            exit();
        } else {
            setErrorMessage('Failed to save student record');
        }
    } else {
        setErrorMessage(implode('<br>', $errors));
    }
}

include 'includes/header.php';
?>

<div class="content-section">
    <h2>
        <?php echo $isEdit ? 'Edit' : 'Add New'; ?> Course Student
    </h2>

    <form method="POST" action="">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div class="form-group">
                <label for="full_name">Full Name *</label>
                <input type="text" id="full_name" name="full_name" class="form-control"
                    value="<?php echo htmlspecialchars($student['full_name'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="email">Email *</label>
                <input type="email" id="email" name="email" class="form-control"
                    value="<?php echo htmlspecialchars($student['email'] ?? ''); ?>" required>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div class="form-group">
                <label for="phone">Phone *</label>
                <input type="tel" id="phone" name="phone" class="form-control"
                    value="<?php echo htmlspecialchars($student['phone'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="gender">Gender *</label>
                <select id="gender" name="gender" class="form-control" required>
                    <option value="">Select Gender</option>
                    <option value="Male" <?php echo ($student['gender'] ?? '') === 'Male' ? 'selected' : ''; ?>>Male
                    </option>
                    <option value="Female" <?php echo ($student['gender'] ?? '') === 'Female' ? 'selected' : ''; ?>>Female
                    </option>
                    <option value="Other" <?php echo ($student['gender'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other
                    </option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label for="college_name">College Name *</label>
            <input type="text" id="college_name" name="college_name" class="form-control"
                value="<?php echo htmlspecialchars($student['college_name'] ?? ''); ?>" required>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div class="form-group">
                <label for="academic_degree">Academic Degree *</label>
                <select id="academic_degree" name="academic_degree" class="form-control" required>
                    <option value="">Select Degree</option>
                    <option value="B.Tech" <?php echo ($student['academic_degree'] ?? '') === 'B.Tech' ? 'selected' : ''; ?>>B.Tech</option>
                    <option value="B.E." <?php echo ($student['academic_degree'] ?? '') === 'B.E.' ? 'selected' : ''; ?>>
                        B.E.</option>
                    <option value="BCA" <?php echo ($student['academic_degree'] ?? '') === 'BCA' ? 'selected' : ''; ?>>BCA
                    </option>
                    <option value="B.Sc" <?php echo ($student['academic_degree'] ?? '') === 'B.Sc' ? 'selected' : ''; ?>>
                        B.Sc</option>
                    <option value="MCA" <?php echo ($student['academic_degree'] ?? '') === 'MCA' ? 'selected' : ''; ?>>MCA
                    </option>
                    <option value="M.Tech" <?php echo ($student['academic_degree'] ?? '') === 'M.Tech' ? 'selected' : ''; ?>>M.Tech</option>
                    <option value="MBA" <?php echo ($student['academic_degree'] ?? '') === 'MBA' ? 'selected' : ''; ?>>MBA
                    </option>
                    <option value="B.Com" <?php echo ($student['academic_degree'] ?? '') === 'B.Com' ? 'selected' : ''; ?>>B.Com</option>
                    <option value="Other" <?php echo ($student['academic_degree'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
                </select>
            </div>

            <div class="form-group">
                <label for="current_year">Current Year *</label>
                <select id="current_year" name="current_year" class="form-control" required>
                    <option value="">Select Year</option>
                    <option value="1st Year" <?php echo ($student['current_year'] ?? '') === '1st Year' ? 'selected' : ''; ?>>1st Year</option>
                    <option value="2nd Year" <?php echo ($student['current_year'] ?? '') === '2nd Year' ? 'selected' : ''; ?>>2nd Year</option>
                    <option value="3rd Year" <?php echo ($student['current_year'] ?? '') === '3rd Year' ? 'selected' : ''; ?>>3rd Year</option>
                    <option value="4th Year" <?php echo ($student['current_year'] ?? '') === '4th Year' ? 'selected' : ''; ?>>4th Year</option>
                    <option value="Passed Out" <?php echo ($student['current_year'] ?? '') === 'Passed Out' ? 'selected' : ''; ?>>Passed Out</option>
                </select>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 1rem;">
            <div class="form-group">
                <label for="course_id">Course *</label>
                <select id="course_id" name="course_id" class="form-control" required
                    onchange="toggleOtherCourse(this.value)">
                    <option value="">Select Course</option>
                    <?php foreach ($courses as $course): ?>
                        <option value="<?php echo $course['id']; ?>" <?php echo ($student['course_id'] ?? '') == $course['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($course['title']); ?>
                        </option>
                    <?php endforeach; ?>
                    <option value="-1" <?php echo ($student['course_id'] ?? '') == -1 ? 'selected' : ''; ?>>Others
                    </option>
                </select>
            </div>

            <div class="form-group">
                <label for="total_amount">Total Amount (₹) *</label>
                <input type="number" id="total_amount" name="total_amount" class="form-control" step="0.01" min="0"
                    value="<?php echo htmlspecialchars($student['total_amount'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="amount_paid">Amount Paid (₹) *</label>
                <input type="number" id="amount_paid" name="amount_paid" class="form-control" step="0.01" min="0"
                    value="<?php echo htmlspecialchars($student['amount_paid'] ?? ''); ?>" required>
            </div>
        </div>

        <div id="other_course_group" class="form-group"
            style="display: <?php echo ($student['course_id'] ?? '') == -1 ? 'block' : 'none'; ?>;">
            <label for="other_course_name">Specify Course Name *</label>
            <input type="text" id="other_course_name" name="other_course_name" class="form-control"
                value="<?php echo htmlspecialchars($student['other_course_name'] ?? ''); ?>">
        </div>

        <?php if ($isEdit && !empty($student['roll_no'])): ?>
            <div class="form-group">
                <label>Roll Number</label>
                <div style="background: #f8fcf9; border: 1px dashed #3db166; padding: 1rem; border-radius: 4px;">
                    <span
                        style="font-family: 'Courier New', monospace; font-weight: 600; font-size: 1.25rem; color: #002147;">
                        <?php echo htmlspecialchars($student['roll_no']); ?>
                    </span>
                </div>
            </div>
        <?php endif; ?>

        <div class="form-group">
            <label>
                <input type="checkbox" name="is_active" <?php echo ($student['is_active'] ?? 1) ? 'checked' : ''; ?>>
                Active (Student is currently enrolled)
            </label>
        </div>

        <div class="action-buttons" style="margin-top: 2rem;">
            <button type="submit" class="btn btn-primary">
                <?php echo $isEdit ? 'Update Student' : 'Register Student'; ?>
            </button>
            <a href="course-students.php" class="btn btn-danger">Cancel</a>
        </div>
    </form>
</div>

<script>
    function toggleOtherCourse(val) {
        const otherGroup = document.getElementById('other_course_group');
        const otherInput = document.getElementById('other_course_name');
        if (val == -1) {
            otherGroup.style.display = 'block';
            otherInput.required = true;
        } else {
            otherGroup.style.display = 'none';
            otherInput.required = false;
        }
    }
</script>

<?php include 'includes/footer.php'; ?>