<?php
$pageTitle = 'Internship Applications';
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$auth = new Auth();
$auth->requireLogin();

$database = new Database();
$db = $database->connect();

if (!$db) {
    die('Database connection failed. Please check your database configuration.');
}

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $enrollment_id = (int)$_POST['enrollment_id'];
    $action = $_POST['action'];
    
    if ($action === 'approve') {
        $stmt = $db->prepare("UPDATE internship_enrollments 
                              SET enrollment_status = 'active', 
                                  payment_status = 'completed',
                                  reviewed_by = :admin_id,
                                  reviewed_at = NOW()
                              WHERE id = :id");
        $stmt->execute(['admin_id' => $_SESSION['admin_id'], 'id' => $enrollment_id]);
        $success_msg = "Application approved successfully!";
    } elseif ($action === 'reject') {
        $stmt = $db->prepare("UPDATE internship_enrollments 
                              SET enrollment_status = 'cancelled',
                                  reviewed_by = :admin_id,
                                  reviewed_at = NOW()
                              WHERE id = :id");
        $stmt->execute(['admin_id' => $_SESSION['admin_id'], 'id' => $enrollment_id]);
        $success_msg = "Application rejected.";
    } elseif ($action === 'add_note') {
        $note = $_POST['admin_notes'] ?? '';
        $stmt = $db->prepare("UPDATE internship_enrollments 
                              SET admin_notes = :notes,
                                  reviewed_by = :admin_id,
                                  reviewed_at = NOW()
                              WHERE id = :id");
        $stmt->execute(['notes' => $note, 'admin_id' => $_SESSION['admin_id'], 'id' => $enrollment_id]);
        $success_msg = "Note added successfully!";
    }
}

// Filters
$status_filter = $_GET['status'] ?? 'all';
$payment_filter = $_GET['payment'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build query
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
            p.title as internship_title,
            p.fees as internship_fees,
            a.username as reviewed_by_name
          FROM internship_enrollments e
          JOIN interns i ON e.intern_id = i.id
          JOIN internships p ON e.internship_id = p.id
          LEFT JOIN admin_users a ON e.reviewed_by = a.id
          WHERE 1=1";

$params = [];

if ($status_filter !== 'all') {
    $query .= " AND e.enrollment_status = :status";
    $params['status'] = $status_filter;
}

if ($payment_filter !== 'all') {
    $query .= " AND e.payment_status = :payment";
    $params['payment'] = $payment_filter;
}

if (!empty($search)) {
    $query .= " AND (i.full_name LIKE :search OR i.email LIKE :search OR i.phone LIKE :search)";
    $params['search'] = "%$search%";
}

$query .= " ORDER BY e.enrollment_date DESC";

try {
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $applications = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Applications query error: " . $e->getMessage());
    $applications = [];
}

// Get statistics
try {
    $stats_query = "SELECT 
                      COUNT(*) as total,
                      SUM(CASE WHEN enrollment_status = 'pending' THEN 1 ELSE 0 END) as pending,
                      SUM(CASE WHEN enrollment_status = 'active' THEN 1 ELSE 0 END) as active,
                      SUM(CASE WHEN enrollment_status = 'completed' THEN 1 ELSE 0 END) as completed,
                      SUM(CASE WHEN enrollment_status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
                      SUM(CASE WHEN payment_status = 'pending' THEN 1 ELSE 0 END) as payment_pending,
                      SUM(amount_paid) as total_revenue
                    FROM internship_enrollments";
    $stats = $db->query($stats_query)->fetch();
} catch (Exception $e) {
    error_log("Statistics query error: " . $e->getMessage());
    $stats = [
        'total' => 0,
        'pending' => 0,
        'active' => 0,
        'completed' => 0,
        'cancelled' => 0,
        'payment_pending' => 0,
        'total_revenue' => 0
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Admin Panel</title>
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-left: 4px solid #3db166;
        }
        .stat-card.pending { border-left-color: #ffc107; }
        .stat-card.active { border-left-color: #3db166; }
        .stat-card.completed { border-left-color: #2196f3; }
        .stat-card.cancelled { border-left-color: #f44336; }
        .stat-card.revenue { border-left-color: #9c27b0; }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #002147;
            margin-bottom: 0.5rem;
        }
        .stat-label {
            font-size: 0.875rem;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .filters {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            align-items: center;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .filter-group label {
            font-size: 0.875rem;
            font-weight: 600;
            color: #333;
        }
        
        .filter-group select,
        .filter-group input {
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 0.875rem;
        }
        
        .applications-table {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .applications-table table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .applications-table th {
            background: #f8f9fa;
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #dee2e6;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .applications-table td {
            padding: 1rem;
            border-bottom: 1px solid #dee2e6;
            font-size: 0.875rem;
        }
        
        .applications-table tr:hover {
            background: #f8f9fa;
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
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
        
        .payment-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .payment-badge.completed {
            background: #d4edda;
            color: #155724;
        }
        
        .payment-badge.pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .payment-badge.failed {
            background: #f8d7da;
            color: #721c24;
        }
        
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.75rem;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-weight: 600;
            transition: 0.3s;
        }
        
        .btn-view {
            background: #2196f3;
            color: white;
        }
        
        .btn-view:hover {
            background: #1976d2;
        }
        
        .btn-approve {
            background: #4caf50;
            color: white;
        }
        
        .btn-approve:hover {
            background: #388e3c;
        }
        
        .btn-reject {
            background: #f44336;
            color: white;
        }
        
        .btn-reject:hover {
            background: #d32f2f;
        }
        
        .export-btn {
            background: #9c27b0;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
        }
        
        .export-btn:hover {
            background: #7b1fa2;
        }
        
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
            border-left: 4px solid #28a745;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #666;
        }
        
        .empty-state i {
            font-size: 3rem;
            color: #ddd;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="admin-container">
        <div class="page-header">
            <h1><i class="fas fa-file-alt"></i> Internship Applications</h1>
            <div>
                <a href="export-applications.php" class="export-btn">
                    <i class="fas fa-download"></i> Export to Excel
                </a>
            </div>
        </div>
        
        <?php if (isset($success_msg)): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i> <?php echo $success_msg; ?>
            </div>
        <?php endif; ?>
        
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['total']; ?></div>
                <div class="stat-label">Total Applications</div>
            </div>
            <div class="stat-card pending">
                <div class="stat-value"><?php echo $stats['pending']; ?></div>
                <div class="stat-label">Pending Review</div>
            </div>
            <div class="stat-card active">
                <div class="stat-value"><?php echo $stats['active']; ?></div>
                <div class="stat-label">Active</div>
            </div>
            <div class="stat-card completed">
                <div class="stat-value"><?php echo $stats['completed']; ?></div>
                <div class="stat-label">Completed</div>
            </div>
            <div class="stat-card cancelled">
                <div class="stat-value"><?php echo $stats['cancelled']; ?></div>
                <div class="stat-label">Cancelled</div>
            </div>
            <div class="stat-card revenue">
                <div class="stat-value">₹<?php echo number_format($stats['total_revenue'], 2); ?></div>
                <div class="stat-label">Total Revenue</div>
            </div>
        </div>
        
        <!-- Filters -->
        <form method="GET" class="filters">
            <div class="filter-group">
                <label>Status</label>
                <select name="status" onchange="this.form.submit()">
                    <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label>Payment</label>
                <select name="payment" onchange="this.form.submit()">
                    <option value="all" <?php echo $payment_filter === 'all' ? 'selected' : ''; ?>>All Payments</option>
                    <option value="pending" <?php echo $payment_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="completed" <?php echo $payment_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="failed" <?php echo $payment_filter === 'failed' ? 'selected' : ''; ?>>Failed</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label>Search</label>
                <input type="text" name="search" placeholder="Name, email, phone..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            
            <div class="filter-group" style="justify-content: flex-end;">
                <label>&nbsp;</label>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Filter
                </button>
            </div>
        </form>
        
        <!-- Applications Table -->
        <div class="applications-table">
            <?php if (count($applications) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Student Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Internship</th>
                            <th>Applied On</th>
                            <th>Status</th>
                            <th>Payment</th>
                            <th>Amount</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($applications as $app): ?>
                            <tr>
                                <td>#<?php echo $app['id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($app['full_name']); ?></strong>
                                    <?php if ($app['gender']): ?>
                                        <br><small style="color: #666;"><?php echo htmlspecialchars($app['gender']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($app['email']); ?></td>
                                <td><?php echo htmlspecialchars($app['phone']); ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($app['internship_title']); ?></strong>
                                    <?php if ($app['college_name']): ?>
                                        <br><small style="color: #666;"><?php echo htmlspecialchars($app['college_name']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($app['enrollment_date'])); ?></td>
                                <td>
                                    <span class="status-badge <?php echo $app['enrollment_status']; ?>">
                                        <?php echo ucfirst($app['enrollment_status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="payment-badge <?php echo $app['payment_status']; ?>">
                                        <?php echo ucfirst($app['payment_status']); ?>
                                    </span>
                                    <br><small style="color: #666;"><?php echo ucfirst($app['payment_method']); ?></small>
                                </td>
                                <td><strong>₹<?php echo number_format($app['amount_paid'], 2); ?></strong></td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="application-details.php?id=<?php echo $app['id']; ?>" class="btn-sm btn-view">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                        <?php if ($app['enrollment_status'] === 'pending'): ?>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Approve this application?');">
                                                <input type="hidden" name="enrollment_id" value="<?php echo $app['id']; ?>">
                                                <input type="hidden" name="action" value="approve">
                                                <button type="submit" class="btn-sm btn-approve">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Reject this application?');">
                                                <input type="hidden" name="enrollment_id" value="<?php echo $app['id']; ?>">
                                                <input type="hidden" name="action" value="reject">
                                                <button type="submit" class="btn-sm btn-reject">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h3>No Applications Found</h3>
                    <p>No applications match your current filters.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="assets/js/admin.js"></script>
</body>
</html>
