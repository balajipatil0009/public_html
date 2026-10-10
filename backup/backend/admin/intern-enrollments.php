<?php
$pageTitle = 'Intern Enrollments';
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$auth = new Auth();
$auth->requireLogin();

$database = new Database();
$db = $database->connect();

// Handle enrollment activation (for cash payments)
if (isset($_GET['activate'])) {
    $id = (int) $_GET['activate'];
    $stmt = $db->prepare("UPDATE internship_enrollments SET 
                          payment_status = 'completed', 
                          enrollment_status = 'active' 
                          WHERE id = :id");
    if ($stmt->execute(['id' => $id])) {
        setSuccessMessage('Enrollment activated successfully');
    }
    header('Location: intern-enrollments.php');
    exit();
}

// Handle delete enrollment
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $stmt = $db->prepare("DELETE FROM internship_enrollments WHERE id = :id");
    if ($stmt->execute(['id' => $id])) {
        setSuccessMessage('Enrollment removed');
    }
    header('Location: intern-enrollments.php');
    exit();
}

// Get filter
$statusFilter = $_GET['status'] ?? 'all';

// Build query
$query = "SELECT e.*, i.full_name, i.email, i.phone, i.college_name, i.academic_degree, i.current_year, 
          p.title as internship_title,
          vp.watched_videos, vp.total_videos
          FROM internship_enrollments e
          JOIN interns i ON e.intern_id = i.id
          JOIN internships p ON e.internship_id = p.id
          LEFT JOIN view_internship_progress vp ON e.intern_id = vp.intern_id AND e.internship_id = vp.internship_id";

if ($statusFilter !== 'all') {
    $query .= " WHERE e.enrollment_status = :status";
}
$query .= " ORDER BY e.enrollment_date DESC";

$stmt = $db->prepare($query);
if ($statusFilter !== 'all') {
    $stmt->bindParam(':status', $statusFilter);
}
$stmt->execute();
$enrollments = $stmt->fetchAll();

// Get counts
$counts = $db->query("SELECT enrollment_status, COUNT(*) as count FROM internship_enrollments GROUP BY enrollment_status")->fetchAll(PDO::FETCH_KEY_PAIR);

include 'includes/header.php';
?>

<div class="content-section">
    <h2>Manage Intern Enrollments</h2>

    <!-- Status Filter -->
    <div style="margin: 1.5rem 0;">
        <a href="?status=all" class="btn btn-sm <?php echo $statusFilter === 'all' ? 'btn-primary' : ''; ?>">All (
            <?php echo array_sum($counts); ?>)
        </a>
        <a href="?status=pending"
            class="btn btn-sm <?php echo $statusFilter === 'pending' ? 'btn-primary' : ''; ?>">Pending (
            <?php echo $counts['pending'] ?? 0; ?>)
        </a>
        <a href="?status=active"
            class="btn btn-sm <?php echo $statusFilter === 'active' ? 'btn-primary' : ''; ?>">Active (
            <?php echo $counts['active'] ?? 0; ?>)
        </a>
        <a href="?status=completed"
            class="btn btn-sm <?php echo $statusFilter === 'completed' ? 'btn-primary' : ''; ?>">Completed (
            <?php echo $counts['completed'] ?? 0; ?>)
        </a>
    </div>

    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Intern</th>
                    <th>Internship</th>
                    <th>Video Progress</th>
                    <th>Method</th>
                    <th>Payment</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($enrollments) > 0): ?>
                    <?php foreach ($enrollments as $e): ?>
                        <tr>
                            <td>
                                <?php echo date('d M Y', strtotime($e['enrollment_date'])); ?>
                            </td>
                            <td>
                                <strong>
                                    <?php echo htmlspecialchars($e['full_name'] ?? ''); ?>
                                </strong><br>
                                <small style="color: #6b7280;">
                                    <?php echo htmlspecialchars($e['email'] ?? ''); ?> |
                                    <?php echo htmlspecialchars($e['phone'] ?? ''); ?>
                                </small><br>
                                <small style="color: #6b7280;">
                                    <?php echo htmlspecialchars($e['college_name'] ?? 'N/A'); ?>
                                    (<?php echo htmlspecialchars($e['academic_degree'] ?? 'N/A'); ?>)
                                </small>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($e['internship_title']); ?>
                            </td>
                            <td>
                                <div
                                    style="width: 100px; background: #e5e7eb; height: 8px; border-radius: 4px; overflow: hidden; margin-bottom: 4px;">
                                    <?php
                                    $percent = ($e['total_videos'] > 0) ? ($e['watched_videos'] / $e['total_videos']) * 100 : 0;
                                    ?>
                                    <div style="width: <?php echo $percent; ?>%; background: #3db166; height: 100%;"></div>
                                </div>
                                <small style="color: #6b7280; font-weight: 500;">
                                    <?php echo (int) $e['watched_videos']; ?> / <?php echo (int) $e['total_videos']; ?> Videos
                                </small>
                            </td>
                            <td><span class="badge" style="background: #f3f4f6; color: #1f2937;">
                                    <?php echo strtoupper($e['payment_method']); ?>
                                </span></td>
                            <td>
                                <span
                                    class="badge <?php echo $e['payment_status'] === 'completed' ? 'badge-converted' : ($e['payment_status'] === 'pending' ? 'badge-new' : 'badge-closed'); ?>">
                                    <?php echo ucfirst($e['payment_status']); ?>
                                </span>
                            </td>
                            <td>
                                <span
                                    class="badge <?php echo $e['enrollment_status'] === 'active' ? 'badge-converted' : ($e['enrollment_status'] === 'pending' ? 'badge-new' : 'badge-closed'); ?>">
                                    <?php echo ucfirst($e['enrollment_status']); ?>
                                </span>
                            </td>
                            <td class="action-buttons">
                                <?php if ($e['enrollment_status'] === 'pending' && $e['payment_method'] === 'cash'): ?>
                                    <a href="?activate=<?php echo $e['id']; ?>" class="btn btn-sm btn-success"
                                        onclick="return confirm('Verify money received and activate enrollment?')">Verify
                                        payment</a>
                                <?php endif; ?>
                                <a href="intern-progress.php?id=<?php echo $e['id']; ?>"
                                    class="btn btn-sm btn-outline">Progress</a>
                                <a href="?delete=<?php echo $e['id']; ?>" class="btn btn-sm btn-danger"
                                    onclick="return confirm('Are you sure?')">Remove</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align: center;">No enrollments found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>