<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$auth = new Auth();
$auth->requireLogin();

$database = new Database();
$db = $database->connect();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id <= 0) {
    header('Location: internships.php');
    exit();
}

// Fetch Internship Details
$stmt = $db->prepare("SELECT * FROM internships WHERE id = :id");
$stmt->execute(['id' => $id]);
$internship = $stmt->fetch();

if (!$internship) {
    header('Location: internships.php');
    exit();
}

$pageTitle = $internship['title'] . ' Panel';

// Stats for this internship
$stats = [
    'total_enrollments' => 0,
    'active_interns' => 0,
    'pending_payments' => 0,
    'completed_internships' => 0
];

$stmt = $db->prepare("SELECT enrollment_status, COUNT(*) as count FROM internship_enrollments WHERE internship_id = :id GROUP BY enrollment_status");
$stmt->execute(['id' => $id]);
$status_counts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$stats['total_enrollments'] = array_sum($status_counts);
$stats['active_interns'] = $status_counts['active'] ?? 0;
$stats['completed_internships'] = $status_counts['completed'] ?? 0;

$stmt = $db->prepare("SELECT COUNT(*) FROM internship_enrollments WHERE internship_id = :id AND payment_status = 'pending'");
$stmt->execute(['id' => $id]);
$stats['pending_payments'] = $stmt->fetchColumn();

// Fetch Enrollments for THIS internship
$query = "SELECT e.*, i.full_name, i.email, i.phone, i.college_name, i.academic_degree, i.current_year,
          vp.watched_videos, vp.total_videos
          FROM internship_enrollments e
          JOIN interns i ON e.intern_id = i.id
          LEFT JOIN view_internship_progress vp ON e.intern_id = vp.intern_id AND e.internship_id = vp.internship_id
          WHERE e.internship_id = :id
          ORDER BY e.enrollment_date DESC";

$stmt = $db->prepare($query);
$stmt->execute(['id' => $id]);
$enrollments = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="content-section">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <a href="internships.php"
                style="color: #6b7280; text-decoration: none; font-size: 0.9rem; margin-bottom: 0.5rem; display: block;">
                <i class="fas fa-arrow-left"></i> Back to Internships
            </a>
            <h2 style="margin: 0;">
                <?php echo htmlspecialchars($internship['title']); ?> Dashboard
            </h2>
        </div>
        <div class="action-buttons">
            <a href="internship-videos.php?internship_id=<?php echo $id; ?>" class="btn btn-primary">
                <i class="fas fa-video"></i> Manage Videos
            </a>
            <a href="internship-form.php?id=<?php echo $id; ?>" class="btn btn-outline">
                <i class="fas fa-edit"></i> Edit Details
            </a>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="stats-grid"
        style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2.5rem;">
        <div class="stat-card"
            style="background: #fff; padding: 1.5rem; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
            <div
                style="color: #6b7280; font-size: 0.85rem; text-transform: uppercase; font-weight: 600; margin-bottom: 0.5rem;">
                Total Applications</div>
            <div style="font-size: 1.75rem; font-weight: 700; color: #111827;">
                <?php echo $stats['total_enrollments']; ?>
            </div>
        </div>
        <div class="stat-card"
            style="background: #fff; padding: 1.5rem; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); border-left: 4px solid #3db166;">
            <div
                style="color: #6b7280; font-size: 0.85rem; text-transform: uppercase; font-weight: 600; margin-bottom: 0.5rem;">
                Active Interns</div>
            <div style="font-size: 1.75rem; font-weight: 700; color: #3db166;">
                <?php echo $stats['active_interns']; ?>
            </div>
        </div>
        <div class="stat-card"
            style="background: #fff; padding: 1.5rem; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); border-left: 4px solid #ef4444;">
            <div
                style="color: #6b7280; font-size: 0.85rem; text-transform: uppercase; font-weight: 600; margin-bottom: 0.5rem;">
                Pending Payments</div>
            <div style="font-size: 1.75rem; font-weight: 700; color: #ef4444;">
                <?php echo $stats['pending_payments']; ?>
            </div>
        </div>
    </div>

    <h3>Manage Enrollments</h3>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Intern Info</th>
                    <th>Academic Details</th>
                    <th>Progress</th>
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
                                <strong>
                                    <?php echo htmlspecialchars($e['full_name']); ?>
                                </strong><br>
                                <small style="color: #6b7280;">
                                    <?php echo htmlspecialchars($e['email']); ?>
                                </small><br>
                                <small style="color: #6b7280;">
                                    <?php echo htmlspecialchars($e['phone']); ?>
                                </small>
                            </td>
                            <td>
                                <small><strong>College:</strong>
                                    <?php echo htmlspecialchars($e['college_name']); ?>
                                </small><br>
                                <small><strong>Degree:</strong>
                                    <?php echo htmlspecialchars($e['academic_degree']); ?> (Year:
                                    <?php echo htmlspecialchars($e['current_year']); ?>)
                                </small>
                            </td>
                            <td>
                                <div
                                    style="width: 100px; background: #e5e7eb; height: 8px; border-radius: 4px; overflow: hidden; margin-bottom: 4px;">
                                    <?php
                                    $percent = ($e['total_videos'] > 0) ? ($e['watched_videos'] / $e['total_videos']) * 100 : 0;
                                    ?>
                                    <div style="width: <?php echo $percent; ?>%; background: #3db166; height: 100%;"></div>
                                </div>
                                <small style="color: #6b7280;">
                                    <?php echo (int) $e['watched_videos']; ?>/
                                    <?php echo (int) $e['total_videos']; ?> Videos
                                </small>
                            </td>
                            <td>
                                <span class="badge"
                                    style="background: #f3f4f6; color: #1f2937; margin-bottom: 4px; display: inline-block;">
                                    <?php echo strtoupper($e['payment_method']); ?>
                                </span><br>
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
                                    <a href="intern-enrollments.php?activate=<?php echo $e['id']; ?>"
                                        class="btn btn-sm btn-success">Verify</a>
                                <?php endif; ?>
                                <a href="intern-progress.php?id=<?php echo $e['id']; ?>"
                                    class="btn btn-sm btn-outline">Details</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align: center;">No enrollments found for this internship.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>