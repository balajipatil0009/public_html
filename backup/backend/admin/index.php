<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$auth = new Auth();
$auth->requireLogin();

$database = new Database();
$db = $database->connect();

// Get statistics
$stats = [
    'testimonials' => $db->query("SELECT COUNT(*) as count FROM testimonials")->fetch()['count'],
    'courses' => $db->query("SELECT COUNT(*) as count FROM courses")->fetch()['count'],
    'inquiries' => $db->query("SELECT COUNT(*) as count FROM contact_inquiries WHERE status = 'new'")->fetch()['count'],
    'gallery_images' => $db->query("SELECT COUNT(*) as count FROM gallery")->fetch()['count']
];

// Get recent inquiries
$recentInquiries = $db->query("SELECT * FROM contact_inquiries ORDER BY created_at DESC LIMIT 5")->fetchAll();

include 'includes/header.php';
?>

<div class="dashboard-grid">
    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(0, 33, 71, 0.05); color: #002147;"><i
                class="fas fa-quote-left"></i></div>
        <div class="stat-content">
            <h3><?php echo $stats['testimonials']; ?></h3>
            <p>Testimonials</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(61, 177, 102, 0.05); color: #3db166;"><i class="fas fa-book"></i>
        </div>
        <div class="stat-content">
            <h3><?php echo $stats['courses']; ?></h3>
            <p>Courses</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(0, 33, 71, 0.05); color: #002147;"><i
                class="fas fa-envelope"></i></div>
        <div class="stat-content">
            <h3><?php echo $stats['inquiries']; ?></h3>
            <p>New Inquiries</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(61, 177, 102, 0.05); color: #3db166;"><i
                class="fas fa-image"></i></div>
        <div class="stat-content">
            <h3><?php echo $stats['gallery_images']; ?></h3>
            <p>Gallery Images</p>
        </div>
    </div>
</div>

<div class="content-section">
    <h2>Recent Inquiries</h2>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Course Interest</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($recentInquiries) > 0): ?>
                    <?php foreach ($recentInquiries as $inquiry): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($inquiry['name']); ?></td>
                            <td><?php echo htmlspecialchars($inquiry['email']); ?></td>
                            <td><?php echo htmlspecialchars($inquiry['course_interest'] ?? 'N/A'); ?></td>
                            <td><span
                                    class="badge badge-<?php echo $inquiry['status']; ?>"><?php echo ucfirst($inquiry['status']); ?></span>
                            </td>
                            <td><?php echo formatDate($inquiry['created_at']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align: center;">No inquiries yet</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>