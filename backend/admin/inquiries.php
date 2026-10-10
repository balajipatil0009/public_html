<?php
$pageTitle = 'Contact Inquiries';
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
    $stmt = $db->prepare("DELETE FROM contact_inquiries WHERE id = :id");
    $stmt->bindParam(':id', $id);
    if ($stmt->execute()) {
        setSuccessMessage('Inquiry deleted successfully');
    } else {
        setErrorMessage('Failed to delete inquiry');
    }
    header('Location: inquiries.php');
    exit();
}

// Handle status update
if (isset($_GET['status']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $status = sanitize($_GET['status']);
    
    if (in_array($status, ['new', 'contacted', 'converted', 'closed'])) {
        $stmt = $db->prepare("UPDATE contact_inquiries SET status = :status WHERE id = :id");
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':id', $id);
        
        if ($stmt->execute()) {
            setSuccessMessage('Status updated successfully');
        }
    }
    
    header('Location: inquiries.php');
    exit();
}

// Get filter
$statusFilter = $_GET['filter'] ?? 'all';

// Build query
$query = "SELECT * FROM contact_inquiries";
if ($statusFilter !== 'all') {
    $query .= " WHERE status = :status";
}
$query .= " ORDER BY created_at DESC";

$stmt = $db->prepare($query);
if ($statusFilter !== 'all') {
    $stmt->bindParam(':status', $statusFilter);
}
$stmt->execute();
$inquiries = $stmt->fetchAll();

// Get counts by status
$statusCounts = $db->query("SELECT status, COUNT(*) as count FROM contact_inquiries GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);

include 'includes/header.php';
?>

<div class="content-section">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <h2>Contact Inquiries</h2>
        <div>
            <span style="color: #6b7280; font-size: 0.9rem;">
                Total: <?php echo array_sum($statusCounts); ?> inquiries
            </span>
        </div>
    </div>
    
    <!-- Status Filter -->
    <div style="margin-bottom: 1.5rem;">
        <a href="?filter=all" class="btn btn-sm <?php echo $statusFilter === 'all' ? 'btn-primary' : ''; ?>">
            All (<?php echo array_sum($statusCounts); ?>)
        </a>
        <a href="?filter=new" class="btn btn-sm <?php echo $statusFilter === 'new' ? 'btn-primary' : ''; ?>">
            🔔 New (<?php echo $statusCounts['new'] ?? 0; ?>)
        </a>
        <a href="?filter=contacted" class="btn btn-sm <?php echo $statusFilter === 'contacted' ? 'btn-primary' : ''; ?>">
            📞 Contacted (<?php echo $statusCounts['contacted'] ?? 0; ?>)
        </a>
        <a href="?filter=converted" class="btn btn-sm <?php echo $statusFilter === 'converted' ? 'btn-primary' : ''; ?>">
            ✅ Converted (<?php echo $statusCounts['converted'] ?? 0; ?>)
        </a>
        <a href="?filter=closed" class="btn btn-sm <?php echo $statusFilter === 'closed' ? 'btn-primary' : ''; ?>">
            🔒 Closed (<?php echo $statusCounts['closed'] ?? 0; ?>)
        </a>
    </div>
    
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Name</th>
                    <th>Contact</th>
                    <th>Course Interest</th>
                    <th>Message</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($inquiries) > 0): ?>
                    <?php foreach ($inquiries as $inquiry): ?>
                        <tr>
                            <td style="white-space: nowrap;">
                                <?php echo formatDate($inquiry['created_at'], 'd M Y'); ?><br>
                                <small style="color: #6b7280;"><?php echo formatDate($inquiry['created_at'], 'h:i A'); ?></small>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($inquiry['name']); ?></strong>
                            </td>
                            <td>
                                <div style="font-size: 0.875rem;">
                                    📧 <?php echo htmlspecialchars($inquiry['email']); ?><br>
                                    <?php if ($inquiry['phone']): ?>
                                        📱 <?php echo htmlspecialchars($inquiry['phone']); ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($inquiry['course_interest'] ?? 'General'); ?></td>
                            <td>
                                <div style="max-width: 300px;">
                                    <?php echo truncateText($inquiry['message'], 100); ?>
                                </div>
                            </td>
                            <td>
                                <span class="badge badge-<?php echo $inquiry['status']; ?>">
                                    <?php echo ucfirst($inquiry['status']); ?>
                                </span>
                            </td>
                            <td class="action-buttons">
                                <a href="inquiry-view.php?id=<?php echo $inquiry['id']; ?>" class="btn btn-sm btn-primary">View</a>
                                <a href="?delete=<?php echo $inquiry['id']; ?>" class="btn btn-sm btn-danger" 
                                   onclick="return confirm('Are you sure?')">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 2rem;">
                            <p style="color: #6b7280; font-size: 1.1rem;">📭 No inquiries found</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
