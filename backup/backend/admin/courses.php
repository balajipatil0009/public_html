<?php
$pageTitle = 'Courses';
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
    $stmt = $db->prepare("DELETE FROM courses WHERE id = :id");
    $stmt->bindParam(':id', $id);
    if ($stmt->execute()) {
        setSuccessMessage('Course deleted successfully');
    } else {
        setErrorMessage('Failed to delete course');
    }
    header('Location: courses.php');
    exit();
}

// Handle toggle active
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $stmt = $db->prepare("UPDATE courses SET is_active = NOT is_active WHERE id = :id");
    $stmt->bindParam(':id', $id);
    if ($stmt->execute()) {
        setSuccessMessage('Course status updated successfully');
    }
    header('Location: courses.php');
    exit();
}

// Get filter
$categoryFilter = $_GET['category'] ?? 'all';

// Build query
$query = "SELECT * FROM courses";
if ($categoryFilter !== 'all') {
    $query .= " WHERE category = :category";
}
$query .= " ORDER BY display_order ASC, title ASC";

$stmt = $db->prepare($query);
if ($categoryFilter !== 'all') {
    $stmt->bindParam(':category', $categoryFilter);
}
$stmt->execute();
$courses = $stmt->fetchAll();

// Get course counts by category
$categoryCounts = $db->query("SELECT category, COUNT(*) as count FROM courses GROUP BY category")->fetchAll(PDO::FETCH_KEY_PAIR);

include 'includes/header.php';
?>

<div class="content-section">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <h2>Manage Courses</h2>
        <a href="course-form.php" class="btn btn-primary">+ Add New Course</a>
    </div>
    
    <!-- Category Filter -->
    <div style="margin-bottom: 1.5rem;">
        <a href="?category=all" class="btn btn-sm <?php echo $categoryFilter === 'all' ? 'btn-primary' : ''; ?>">
            All (<?php echo array_sum($categoryCounts); ?>)
        </a>
        <a href="?category=job_role" class="btn btn-sm <?php echo $categoryFilter === 'job_role' ? 'btn-primary' : ''; ?>">
            Job Role (<?php echo $categoryCounts['job_role'] ?? 0; ?>)
        </a>
        <a href="?category=programming" class="btn btn-sm <?php echo $categoryFilter === 'programming' ? 'btn-primary' : ''; ?>">
            Programming (<?php echo $categoryCounts['programming'] ?? 0; ?>)
        </a>
        <a href="?category=web_dev" class="btn btn-sm <?php echo $categoryFilter === 'web_dev' ? 'btn-primary' : ''; ?>">
            Web Dev (<?php echo $categoryCounts['web_dev'] ?? 0; ?>)
        </a>
        <a href="?category=database" class="btn btn-sm <?php echo $categoryFilter === 'database' ? 'btn-primary' : ''; ?>">
            Database (<?php echo $categoryCounts['database'] ?? 0; ?>)
        </a>
        <a href="?category=design" class="btn btn-sm <?php echo $categoryFilter === 'design' ? 'btn-primary' : ''; ?>">
            Design (<?php echo $categoryCounts['design'] ?? 0; ?>)
        </a>
    </div>
    
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Duration</th>
                    <th>Fees</th>
                    <th>Featured</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($courses) > 0): ?>
                    <?php foreach ($courses as $course): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($course['title']); ?></strong><br>
                                <small style="color: #6b7280;"><?php echo truncateText($course['short_description'], 60); ?></small>
                            </td>
                            <td><?php echo ucfirst(str_replace('_', ' ', $course['category'])); ?></td>
                            <td><?php echo htmlspecialchars($course['duration'] ?? 'N/A'); ?></td>
                            <td><?php echo $course['fees'] ? '₹' . number_format($course['fees'], 2) : 'N/A'; ?></td>
                            <td><?php echo $course['is_featured'] ? '⭐ Yes' : 'No'; ?></td>
                            <td>
                                <span class="badge <?php echo $course['is_active'] ? 'badge-converted' : 'badge-closed'; ?>">
                                    <?php echo $course['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td class="action-buttons">
                                <a href="course-form.php?id=<?php echo $course['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                                <a href="?toggle=<?php echo $course['id']; ?>" class="btn btn-sm btn-success">Toggle</a>
                                <a href="?delete=<?php echo $course['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align: center;">No courses found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
