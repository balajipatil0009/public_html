<?php
$pageTitle = 'Testimonials';
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
    $id = (int)$_GET['delete'];
    $stmt = $db->prepare("DELETE FROM testimonials WHERE id = :id");
    $stmt->bindParam(':id', $id);
    if ($stmt->execute()) {
        setSuccessMessage('Testimonial deleted successfully');
    } else {
        setErrorMessage('Failed to delete testimonial');
    }
    header('Location: testimonials.php');
    exit();
}

// Handle toggle active
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $stmt = $db->prepare("UPDATE testimonials SET is_active = NOT is_active WHERE id = :id");
    $stmt->bindParam(':id', $id);
    if ($stmt->execute()) {
        setSuccessMessage('Status updated successfully');
    }
    header('Location: testimonials.php');
    exit();
}

// Get all testimonials
$testimonials = $db->query("SELECT * FROM testimonials ORDER BY display_order ASC, created_at DESC")->fetchAll();

include 'includes/header.php';
?>

<div class="content-section">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <h2>Manage Testimonials</h2>
        <a href="testimonial-form.php" class="btn btn-primary">+ Add New Testimonial</a>
    </div>
    
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Student Name</th>
                    <th>Course</th>
                    <th>Rating</th>
                    <th>Company</th>
                    <th>Featured</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($testimonials) > 0): ?>
                    <?php foreach ($testimonials as $testimonial): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($testimonial['student_name']); ?></td>
                            <td><?php echo htmlspecialchars($testimonial['course']); ?></td>
                            <td><?php echo str_repeat('⭐', $testimonial['rating']); ?></td>
                            <td><?php echo htmlspecialchars($testimonial['company'] ?? 'N/A'); ?></td>
                            <td><?php echo $testimonial['is_featured'] ? '✓ Yes' : 'No'; ?></td>
                            <td>
                                <span class="badge <?php echo $testimonial['is_active'] ? 'badge-converted' : 'badge-closed'; ?>">
                                    <?php echo $testimonial['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td class="action-buttons">
                                <a href="testimonial-form.php?id=<?php echo $testimonial['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                                <a href="?toggle=<?php echo $testimonial['id']; ?>" class="btn btn-sm btn-success">Toggle</a>
                                <a href="?delete=<?php echo $testimonial['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align: center;">No testimonials found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
