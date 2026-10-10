<?php
$pageTitle = 'Internships';
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
    $stmt = $db->prepare("DELETE FROM internships WHERE id = :id");
    $stmt->bindParam(':id', $id);
    if ($stmt->execute()) {
        setSuccessMessage('Internship deleted successfully');
    } else {
        setErrorMessage('Failed to delete internship');
    }
    header('Location: internships.php');
    exit();
}

// Handle toggle active
if (isset($_GET['toggle'])) {
    $id = (int) $_GET['toggle'];
    $stmt = $db->prepare("UPDATE internships SET is_active = NOT is_active WHERE id = :id");
    $stmt->bindParam(':id', $id);
    if ($stmt->execute()) {
        setSuccessMessage('Internship status updated successfully');
    }
    header('Location: internships.php');
    exit();
}

// Build query
$query = "SELECT * FROM internships ORDER BY display_order ASC, title ASC";
$stmt = $db->prepare($query);
$stmt->execute();
$internships = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="content-section">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <h2>Manage Internships</h2>
        <a href="internship-form.php" class="btn btn-primary">+ Add New Internship</a>
    </div>

    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Duration</th>
                    <th>Fees</th>
                    <th>Videos</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($internships) > 0): ?>
                    <?php foreach ($internships as $internship):
                        // Count videos for this internship
                        $vStmt = $db->prepare("SELECT COUNT(*) FROM internship_videos WHERE internship_id = :id");
                        $vStmt->execute(['id' => $internship['id']]);
                        $videoCount = $vStmt->fetchColumn();
                        ?>
                        <tr>
                            <td>
                                <strong>
                                    <?php echo htmlspecialchars($internship['title']); ?>
                                </strong><br>
                                <small style="color: #6b7280;">
                                    <?php echo truncateText($internship['description'], 60); ?>
                                </small>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($internship['duration']); ?>
                            </td>
                            <td>₹
                                <?php echo number_format($internship['fees'], 2); ?>
                            </td>
                            <td>
                                <a href="internship-videos.php?internship_id=<?php echo $internship['id']; ?>"
                                    class="btn btn-sm btn-outline">
                                    📹
                                    <?php echo $videoCount; ?> Videos
                                </a>
                            </td>
                            <td>
                                <span
                                    class="badge <?php echo $internship['is_active'] ? 'badge-converted' : 'badge-closed'; ?>">
                                    <?php echo $internship['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td class="action-buttons">
                                <a href="internship-details.php?id=<?php echo $internship['id']; ?>"
                                    class="btn btn-sm btn-success">View Panel</a>
                                <a href="internship-form.php?id=<?php echo $internship['id']; ?>"
                                    class="btn btn-sm btn-primary">Edit</a>
                                <a href="?delete=<?php echo $internship['id']; ?>" class="btn btn-sm btn-danger"
                                    onclick="return confirm('Are you sure?')">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align: center;">No internships found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>