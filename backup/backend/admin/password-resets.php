<?php
$pageTitle = 'Password Reset Requests';
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$auth = new Auth();
$auth->requireLogin();

$database = new Database();
$db = $database->connect();

// Check if table exists
$table_exists = false;
try {
    $check = $db->query("SELECT 1 FROM password_reset_requests LIMIT 1");
    $table_exists = ($check !== false);
} catch (PDOException $e) {
    $table_exists = false;
}

$requests = [];
$statusCounts = [];

if ($table_exists) {
    // Handle approve action
    if (isset($_GET['approve']) && isset($_GET['id'])) {
        $id = (int) $_GET['id'];

        try {
            $stmt = $db->prepare("UPDATE password_reset_requests SET status = 'approved', approved_at = NOW() WHERE id = :id AND status = 'pending'");
            $stmt->execute(['id' => $id]);

            if ($stmt->rowCount() > 0) {
                setSuccessMessage('Password reset request approved. The intern can now reset their password.');
            } else {
                setErrorMessage('Failed to approve request or request already processed.');
            }
        } catch (PDOException $e) {
            setErrorMessage('Database error occurred.');
        }

        header('Location: password-resets.php');
        exit();
    }

    // Handle reject action
    if (isset($_GET['reject']) && isset($_GET['id'])) {
        $id = (int) $_GET['id'];

        try {
            $stmt = $db->prepare("UPDATE password_reset_requests SET status = 'rejected' WHERE id = :id AND status = 'pending'");
            $stmt->execute(['id' => $id]);

            if ($stmt->rowCount() > 0) {
                setSuccessMessage('Password reset request rejected.');
            } else {
                setErrorMessage('Failed to reject request or request already processed.');
            }
        } catch (PDOException $e) {
            setErrorMessage('Database error occurred.');
        }

        header('Location: password-resets.php');
        exit();
    }

    // Handle delete action
    if (isset($_GET['delete'])) {
        $id = (int) $_GET['delete'];
        try {
            $stmt = $db->prepare("DELETE FROM password_reset_requests WHERE id = :id");
            $stmt->execute(['id' => $id]);

            if ($stmt->rowCount() > 0) {
                setSuccessMessage('Request deleted successfully.');
            }
        } catch (PDOException $e) {
            setErrorMessage('Database error occurred.');
        }

        header('Location: password-resets.php');
        exit();
    }

    // Get filter
    $statusFilter = $_GET['filter'] ?? 'all';

    // Build query
    try {
        $query = "SELECT * FROM password_reset_requests";
        if ($statusFilter !== 'all') {
            $query .= " WHERE status = :status";
        }
        $query .= " ORDER BY created_at DESC";

        $stmt = $db->prepare($query);
        if ($statusFilter !== 'all') {
            $stmt->bindParam(':status', $statusFilter);
        }
        $stmt->execute();
        $requests = $stmt->fetchAll();

        // Get counts by status
        $countQuery = $db->query("SELECT status, COUNT(*) as count FROM password_reset_requests GROUP BY status");
        $statusCounts = $countQuery ? $countQuery->fetchAll(PDO::FETCH_KEY_PAIR) : [];
    } catch (PDOException $e) {
        $requests = [];
        $statusCounts = [];
    }
} else {
    $statusFilter = 'all';
}

include 'includes/header.php';
?>

<?php if (!$table_exists): ?>
    <div class="content-section">
        <div style="text-align: center; padding: 3rem;">
            <div style="font-size: 4rem; margin-bottom: 1rem;">⚙️</div>
            <h2 style="color: #002147; margin-bottom: 1rem;">Database Setup Required</h2>
            <p style="color: #6b7280; margin-bottom: 2rem;">
                The password reset feature requires a database table that hasn't been created yet.
            </p>
            <div
                style="background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 1.5rem; text-align: left; max-width: 600px; margin: 0 auto;">
                <p style="font-weight: 600; margin-bottom: 0.5rem;">Run this SQL to set up the table:</p>
                <code
                    style="display: block; background: #1f2937; color: #10b981; padding: 1rem; border-radius: 4px; font-size: 0.85rem; overflow-x: auto;">
                    SOURCE backend/database/password_reset_migration.sql;
                </code>
            </div>
        </div>
    </div>
<?php else: ?>


    <div class="content-section">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h2>🔐 Password Reset Requests</h2>
            <div>
                <span style="color: #6b7280; font-size: 0.9rem;">
                    Total:
                    <?php echo array_sum($statusCounts); ?> requests
                </span>
            </div>
        </div>

        <!-- Status Filter -->
        <div style="margin-bottom: 1.5rem;">
            <a href="?filter=all" class="btn btn-sm <?php echo $statusFilter === 'all' ? 'btn-primary' : ''; ?>">
                All (
                <?php echo array_sum($statusCounts); ?>)
            </a>
            <a href="?filter=pending" class="btn btn-sm <?php echo $statusFilter === 'pending' ? 'btn-primary' : ''; ?>">
                ⏳ Pending (
                <?php echo $statusCounts['pending'] ?? 0; ?>)
            </a>
            <a href="?filter=approved" class="btn btn-sm <?php echo $statusFilter === 'approved' ? 'btn-primary' : ''; ?>">
                ✅ Approved (
                <?php echo $statusCounts['approved'] ?? 0; ?>)
            </a>
            <a href="?filter=used" class="btn btn-sm <?php echo $statusFilter === 'used' ? 'btn-primary' : ''; ?>">
                🔄 Used (
                <?php echo $statusCounts['used'] ?? 0; ?>)
            </a>
            <a href="?filter=rejected" class="btn btn-sm <?php echo $statusFilter === 'rejected' ? 'btn-primary' : ''; ?>">
                ❌ Rejected (
                <?php echo $statusCounts['rejected'] ?? 0; ?>)
            </a>
        </div>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Intern Name</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($requests) > 0): ?>
                        <?php foreach ($requests as $request): ?>
                            <tr>
                                <td style="white-space: nowrap;">
                                    <?php echo formatDate($request['created_at'], 'd M Y'); ?><br>
                                    <small style="color: #6b7280;">
                                        <?php echo formatDate($request['created_at'], 'h:i A'); ?>
                                    </small>
                                </td>
                                <td>
                                    <strong>
                                        <?php echo htmlspecialchars($request['full_name']); ?>
                                    </strong>
                                </td>
                                <td>
                                    <div style="font-size: 0.875rem;">
                                        📧
                                        <?php echo htmlspecialchars($request['email']); ?>
                                    </div>
                                </td>
                                <td>
                                    <?php
                                    $badgeClass = match ($request['status']) {
                                        'pending' => 'badge-new',
                                        'approved' => 'badge-converted',
                                        'used' => 'badge-contacted',
                                        'rejected' => 'badge-closed',
                                        default => ''
                                    };
                                    ?>
                                    <span class="badge <?php echo $badgeClass; ?>">
                                        <?php echo ucfirst($request['status']); ?>
                                    </span>
                                    <?php if ($request['approved_at']): ?>
                                        <br><small style="color: #6b7280;">Approved:
                                            <?php echo formatDate($request['approved_at'], 'd M h:i A'); ?>
                                        </small>
                                    <?php endif; ?>
                                    <?php if ($request['used_at']): ?>
                                        <br><small style="color: #10b981;">Reset:
                                            <?php echo formatDate($request['used_at'], 'd M h:i A'); ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td class="action-buttons">
                                    <?php if ($request['status'] === 'pending'): ?>
                                        <a href="?approve=1&id=<?php echo $request['id']; ?>" class="btn btn-sm btn-success"
                                            onclick="return confirm('Approve password reset for <?php echo htmlspecialchars($request['full_name']); ?>?')">
                                            ✓ Approve
                                        </a>
                                        <a href="?reject=1&id=<?php echo $request['id']; ?>" class="btn btn-sm btn-warning"
                                            onclick="return confirm('Reject this password reset request?')">
                                            ✗ Reject
                                        </a>
                                    <?php endif; ?>
                                    <a href="?delete=<?php echo $request['id']; ?>" class="btn btn-sm btn-danger"
                                        onclick="return confirm('Delete this request permanently?')">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 2rem;">
                                <p style="color: #6b7280; font-size: 1.1rem;">📭 No password reset requests found</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php endif; ?>

<?php include 'includes/footer.php'; ?>