<?php
$pageTitle = isset($_GET['id']) ? 'Edit Testimonial' : 'Add Testimonial';
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$auth = new Auth();
$auth->requireLogin();

$database = new Database();
$db = $database->connect();

$testimonial = null;
$isEdit = false;

// Get testimonial if editing
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $db->prepare("SELECT * FROM testimonials WHERE id = :id");
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $testimonial = $stmt->fetch();
    $isEdit = true;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_name = sanitize($_POST['student_name']);
    $course = sanitize($_POST['course']);
    $rating = (int)$_POST['rating'];
    $testimonial_text = sanitize($_POST['testimonial']);
    $company = sanitize($_POST['company']);
    $designation = sanitize($_POST['designation']);
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $display_order = (int)$_POST['display_order'];
    
    if ($isEdit) {
        $query = "UPDATE testimonials SET student_name = :student_name, course = :course, rating = :rating, 
                  testimonial = :testimonial, company = :company, designation = :designation, 
                  is_featured = :is_featured, is_active = :is_active, display_order = :display_order 
                  WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $_GET['id']);
    } else {
        $query = "INSERT INTO testimonials (student_name, course, rating, testimonial, company, designation, 
                  is_featured, is_active, display_order) VALUES (:student_name, :course, :rating, :testimonial, 
                  :company, :designation, :is_featured, :is_active, :display_order)";
        $stmt = $db->prepare($query);
    }
    
    $stmt->bindParam(':student_name', $student_name);
    $stmt->bindParam(':course', $course);
    $stmt->bindParam(':rating', $rating);
    $stmt->bindParam(':testimonial', $testimonial_text);
    $stmt->bindParam(':company', $company);
    $stmt->bindParam(':designation', $designation);
    $stmt->bindParam(':is_featured', $is_featured);
    $stmt->bindParam(':is_active', $is_active);
    $stmt->bindParam(':display_order', $display_order);
    
    if ($stmt->execute()) {
        setSuccessMessage($isEdit ? 'Testimonial updated successfully' : 'Testimonial added successfully');
        header('Location: testimonials.php');
        exit();
    } else {
        setErrorMessage('Failed to save testimonial');
    }
}

include 'includes/header.php';
?>

<div class="content-section">
    <h2><?php echo $isEdit ? 'Edit' : 'Add New'; ?> Testimonial</h2>
    
    <form method="POST" action="">
        <div class="form-group">
            <label for="student_name">Student Name *</label>
            <input type="text" id="student_name" name="student_name" class="form-control" 
                   value="<?php echo $testimonial['student_name'] ?? ''; ?>" required>
        </div>
        
        <div class="form-group">
            <label for="course">Course *</label>
            <input type="text" id="course" name="course" class="form-control" 
                   value="<?php echo $testimonial['course'] ?? ''; ?>" required>
        </div>
        
        <div class="form-group">
            <label for="rating">Rating *</label>
            <select id="rating" name="rating" class="form-control" required>
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <option value="<?php echo $i; ?>" <?php echo ($testimonial['rating'] ?? 5) == $i ? 'selected' : ''; ?>>
                        <?php echo $i; ?> Star<?php echo $i > 1 ? 's' : ''; ?>
                    </option>
                <?php endfor; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label for="testimonial">Testimonial *</label>
            <textarea id="testimonial" name="testimonial" class="form-control" required><?php echo $testimonial['testimonial'] ?? ''; ?></textarea>
        </div>
        
        <div class="form-group">
            <label for="company">Company</label>
            <input type="text" id="company" name="company" class="form-control" 
                   value="<?php echo $testimonial['company'] ?? ''; ?>">
        </div>
        
        <div class="form-group">
            <label for="designation">Designation</label>
            <input type="text" id="designation" name="designation" class="form-control" 
                   value="<?php echo $testimonial['designation'] ?? ''; ?>">
        </div>
        
        <div class="form-group">
            <label for="display_order">Display Order</label>
            <input type="number" id="display_order" name="display_order" class="form-control" 
                   value="<?php echo $testimonial['display_order'] ?? 0; ?>">
        </div>
        
        <div class="form-group">
            <label>
                <input type="checkbox" name="is_featured" <?php echo ($testimonial['is_featured'] ?? 0) ? 'checked' : ''; ?>>
                Featured Testimonial
            </label>
        </div>
        
        <div class="form-group">
            <label>
                <input type="checkbox" name="is_active" <?php echo ($testimonial['is_active'] ?? 1) ? 'checked' : ''; ?>>
                Active
            </label>
        </div>
        
        <div class="action-buttons">
            <button type="submit" class="btn btn-primary">Save Testimonial</button>
            <a href="testimonials.php" class="btn btn-danger">Cancel</a>
        </div>
    </form>
</div>

<?php include 'includes/footer.php'; ?>
