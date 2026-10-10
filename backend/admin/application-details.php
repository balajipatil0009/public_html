<?php
$pageTitle = 'Application Details';
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$auth = new Auth();
$auth->requireLogin();

$database = new Database();
$db = $database->connect();

$enrollment_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_status') {
        $new_status = $_POST['enrollment_status'];
        $payment_status = $_POST['payment_status'];
        $admin_notes = $_POST['admin_notes'] ?? '';
        
        $stmt = $db->prepare("UPDATE internship_enrollments 
                              SET enrollment_status = :status,
                                  payment_status = :payment,
                                  admin_notes = :notes,
                                  reviewed_by = :admin_id,
                                  reviewed_at = NOW()
                              WHERE id = :id");
        $stmt->execute([
            'status' => $new_status,
            'payment' => $payment_status,
            'notes' => $admin_notes,
            'admin_id' => $_SESSION['admin_id'],
            'id' => $enrollment_id
        ]);
        
        $success_msg = "Application updated successfully!";
    } elseif ($action === 'send_email') {
        // Email functionality can be added here
        $success_msg = "Email sent successfully!";
    }
}

// Fetch application details
$query = "SELECT 
            e.*,
            i.full_name,
            i.email,
            i.phone,
            i.gender,
            i.college_name,
            i.academic_degree,
            i.current_year,
            i.country,
            i.source,
            i.created_at as intern_created_at,
            p.title as internship_title,
            p.description as internship_description,
            p.duration as internship_duration,
            p.fees as internship_fees,
            a.username as reviewed_by_name,
            a.full_name as reviewed_by_fullname
          FROM internship_enrollments e
          JOIN interns i ON e.intern_id = i.id
          JOIN internships p ON e.internship_id = p.id
          LEFT JOIN admin_users a ON e.reviewed_by = a.id
          WHERE e.id = :id";

$stmt = $db->prepare($query);
$stmt->execute(['id' => $enrollment_id]);
$app = $stmt->fetch();

if (!$app) {
    header('Location: applications.php');
    exit();
}

// Get intern's progress if enrolled
$progress = null;
if ($app['enrollment_status'] === 'active' || $app['enrollment_status'] === 'completed') {
    $prog_stmt = $db->prepare("SELECT * FROM view_internship_progress 
                                WHERE intern_id = :iid AND internship_id = :pid");
    $prog_stmt->execute(['iid' => $app['intern_id'], 'pid' => $app['internship_id']]);
    $progress = $prog_stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Details - <?php echo htmlspecialchars($app['full_name']); ?></title>
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .details-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .details-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
            margin-top: 2rem;
        }
        
        .details-card {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .details-card h2 {
            margin-top: 0;
            margin-bottom: 1.5rem;
            color: #002147;
            font-size: 1.25rem;
            border-bottom: 2px solid #3db166;
            padding-bottom: 0.5rem;
        }
        
        .detail-row {
            display: grid;
            grid-template-columns: 150px 1fr;
            gap: 1rem;
            padding: 0.75rem 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .detail-row:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            font-weight: 600;
            color: #666;
            font-size: 0.875rem;
        }
        
        .detail-value {
            color: #333;
            font-size: 0.875rem;
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.375rem 0.875rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .status-badge.pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-badge.active {
            background: #d4edda;
            color: #155724;
        }
        
        .status-badge.completed {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .status-badge.cancelled {
            background: #f8d7da;
            color: #721c24;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
            font-size: 0.875rem;
        }
        
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 0.875rem;
            font-family: inherit;
        }
        
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .btn-primary {
            background: #3db166;
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.875rem;
        }
        
        .btn-primary:hover {
            background: #2d9050;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.875rem;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .progress-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
        
        .progress-stat {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .progress-stat:last-child {
            margin-bottom: 0;
        }
        
        .progress-label {
            font-size: 0.875rem;
            opacity: 0.9;
        }
        
        .progress-value {
            font-size: 1.5rem;
            font-weight: 700;
        }
        
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
            border-left: 4px solid #28a745;
        }
        
        .timeline {
            position: relative;
            padding-left: 2rem;
        }
        
        .timeline::before {
            content: '';
            position: absolute;
            left: 0.5rem;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #ddd;
        }
        
        .timeline-item {
            position: relative;
            margin-bottom: 1.5rem;
        }
        
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -1.5rem;
            top: 0.25rem;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #3db166;
            border: 2px solid white;
            box-shadow: 0 0 0 2px #3db166;
        }
        
        .timeline-date {
            font-size: 0.75rem;
            color: #666;
            margin-bottom: 0.25rem;
        }
        
        .timeline-content {
            font-size: 0.875rem;
            color: #333;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="admin-container">
        <div class="details-container">
            <div class="page-header">
                <h1>
                    <i class="fas fa-file-alt"></i> Application Details
                    <span class="status-badge <?php echo $app['enrollment_status']; ?>">
                        <?php echo ucfirst($app['enrollment_status']); ?>
                    </span>
                </h1>
                <div>
                    <a href="applications.php" class="btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </a>
                </div>
            </div>
            
            <?php if (isset($success_msg)): ?>
                <div class="success-message">
                    <i class="fas fa-check-circle"></i> <?php echo $success_msg; ?>
                </div>
            <?php endif; ?>
            
            <div class="details-grid">
                <!-- Main Details -->
                <div>
                    <!-- Student Information -->
                    <div class="details-card">
                        <h2><i class="fas fa-user"></i> Student Information</h2>
                        <div class="detail-row">
                            <div class="detail-label">Full Name:</div>
                            <div class="detail-value"><strong><?php echo htmlspecialchars($app['full_name']); ?></strong></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Email:</div>
                            <div class="detail-value"><?php echo htmlspecialchars($app['email']); ?></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Phone:</div>
                            <div class="detail-value"><?php echo htmlspecialchars($app['phone']); ?></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Gender:</div>
                            <div class="detail-value"><?php echo htmlspecialchars($app['gender'] ?: 'Not specified'); ?></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Country:</div>
                            <div class="detail-value"><?php echo htmlspecialchars($app['country']); ?></div>
                        </div>
                    </div>
                    
                    <!-- Academic Information -->
                    <div class="details-card" style="margin-top: 2rem;">
                        <h2><i class="fas fa-graduation-cap"></i> Academic Information</h2>
                        <div class="detail-row">
                            <div class="detail-label">College:</div>
                            <div class="detail-value"><?php echo htmlspecialchars($app['college_name'] ?: 'Not specified'); ?></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Degree:</div>
                            <div class="detail-value"><?php echo htmlspecialchars($app['academic_degree'] ?: 'Not specified'); ?></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Current Year:</div>
                            <div class="detail-value"><?php echo htmlspecialchars($app['current_year'] ?: 'Not specified'); ?></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Source:</div>
                            <div class="detail-value"><?php echo htmlspecialchars($app['source'] ?: 'Not specified'); ?></div>
                        </div>
                    </div>
                    
                    <!-- Internship Details -->
                    <div class="details-card" style="margin-top: 2rem;">
                        <h2><i class="fas fa-briefcase"></i> Internship Details</h2>
                        <div class="detail-row">
                            <div class="detail-label">Program:</div>
                            <div class="detail-value"><strong><?php echo htmlspecialchars($app['internship_title']); ?></strong></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Duration:</div>
                            <div class="detail-value"><?php echo htmlspecialchars($app['internship_duration']); ?></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Fees:</div>
                            <div class="detail-value"><strong>₹<?php echo number_format($app['internship_fees'], 2); ?></strong></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Amount Paid:</div>
                            <div class="detail-value"><strong>₹<?php echo number_format($app['amount_paid'], 2); ?></strong></div>
                        </div>
                    </div>
                    
                    <!-- Student Queries -->
                    <?php if ($app['queries']): ?>
                        <div class="details-card" style="margin-top: 2rem;">
                            <h2><i class="fas fa-question-circle"></i> Student Queries</h2>
                            <p style="color: #666; font-size: 0.875rem; line-height: 1.6;">
                                <?php echo nl2br(htmlspecialchars($app['queries'])); ?>
                            </p>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Progress (if active) -->
                    <?php if ($progress): ?>
                        <div class="details-card" style="margin-top: 2rem;">
                            <h2><i class="fas fa-chart-line"></i> Learning Progress</h2>
                            <div class="progress-card">
                                <div class="progress-stat">
                                    <span class="progress-label">Videos Watched</span>
                                    <span class="progress-value"><?php echo $progress['watched_videos']; ?>/<?php echo $progress['total_videos']; ?></span>
                                </div>
                                <div class="progress-stat">
                                    <span class="progress-label">Quizzes Passed</span>
                                    <span class="progress-value"><?php echo $progress['quizzes_passed']; ?>/<?php echo $progress['total_videos']; ?></span>
                                </div>
                                <div class="progress-stat">
                                    <span class="progress-label">Completion</span>
                                    <span class="progress-value">
                                        <?php 
                                        $completion = $progress['total_videos'] > 0 
                                            ? round(($progress['watched_videos'] / $progress['total_videos']) * 100) 
                                            : 0;
                                        echo $completion . '%';
                                        ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Sidebar -->
                <div>
                    <!-- Update Status -->
                    <div class="details-card">
                        <h2><i class="fas fa-edit"></i> Update Application</h2>
                        <form method="POST">
                            <input type="hidden" name="action" value="update_status">
                            
                            <div class="form-group">
                                <label>Enrollment Status</label>
                                <select name="enrollment_status" required>
                                    <option value="pending" <?php echo $app['enrollment_status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="active" <?php echo $app['enrollment_status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="completed" <?php echo $app['enrollment_status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                    <option value="cancelled" <?php echo $app['enrollment_status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Payment Status</label>
                                <select name="payment_status" required>
                                    <option value="pending" <?php echo $app['payment_status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="completed" <?php echo $app['payment_status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                    <option value="failed" <?php echo $app['payment_status'] === 'failed' ? 'selected' : ''; ?>>Failed</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Admin Notes</label>
                                <textarea name="admin_notes" placeholder="Add notes about this application..."><?php echo htmlspecialchars($app['admin_notes'] ?: ''); ?></textarea>
                            </div>
                            
                            <button type="submit" class="btn-primary" style="width: 100%;">
                                <i class="fas fa-save"></i> Update Application
                            </button>
                        </form>
                    </div>
                    
                    <!-- Timeline -->
                    <div class="details-card" style="margin-top: 2rem;">
                        <h2><i class="fas fa-history"></i> Timeline</h2>
                        <div class="timeline">
                            <div class="timeline-item">
                                <div class="timeline-date"><?php echo date('M d, Y H:i', strtotime($app['enrollment_date'])); ?></div>
                                <div class="timeline-content">Application submitted</div>
                            </div>
                            
                            <?php if ($app['reviewed_at']): ?>
                                <div class="timeline-item">
                                    <div class="timeline-date"><?php echo date('M d, Y H:i', strtotime($app['reviewed_at'])); ?></div>
                                    <div class="timeline-content">
                                        Reviewed by <?php echo htmlspecialchars($app['reviewed_by_fullname'] ?: $app['reviewed_by_name']); ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($app['certificate_issued']): ?>
                                <div class="timeline-item">
                                    <div class="timeline-date"><?php echo date('M d, Y H:i', strtotime($app['certificate_date'])); ?></div>
                                    <div class="timeline-content">Certificate issued</div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Payment Info -->
                    <div class="details-card" style="margin-top: 2rem;">
                        <h2><i class="fas fa-credit-card"></i> Payment Information</h2>
                        <div class="detail-row">
                            <div class="detail-label">Method:</div>
                            <div class="detail-value"><?php echo ucfirst($app['payment_method']); ?></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Status:</div>
                            <div class="detail-value">
                                <span class="status-badge <?php echo $app['payment_status']; ?>">
                                    <?php echo ucfirst($app['payment_status']); ?>
                                </span>
                            </div>
                        </div>
                        <?php if ($app['transaction_id']): ?>
                            <div class="detail-row">
                                <div class="detail-label">Transaction ID:</div>
                                <div class="detail-value"><code><?php echo htmlspecialchars($app['transaction_id']); ?></code></div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="assets/js/admin.js"></script>
</body>
</html>
