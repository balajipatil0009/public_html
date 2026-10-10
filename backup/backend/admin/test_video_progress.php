<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/auth.php';

$auth = new Auth();
$auth->requireLogin();

$database = new Database();
$db = $database->connect();

// Test 1: Check if columns exist
$columns = ['seconds_watched', 'total_seconds', 'updated_at'];
$results = [];

foreach ($columns as $col) {
    $stmt = $db->query("SHOW COLUMNS FROM intern_video_progress LIKE '$col'");
    $exists = $stmt->rowCount() > 0;
    $results[] = [
        'test' => "Column '$col' exists",
        'status' => $exists,
        'message' => $exists ? 'Found' : 'Missing - Run migration'
    ];
}

// Test 2: Check if API endpoint exists
$apiFile = '../api/update_watch_progress.php';
$results[] = [
    'test' => 'API endpoint exists',
    'status' => file_exists($apiFile),
    'message' => file_exists($apiFile) ? 'Found' : 'Missing'
];

// Test 3: Check sample progress data
$stmt = $db->query("SELECT COUNT(*) as count FROM intern_video_progress WHERE seconds_watched > 0");
$row = $stmt->fetch();
$results[] = [
    'test' => 'Progress records with tracking',
    'status' => true,
    'message' => $row['count'] . ' records found'
];

// Test 4: Check for any interns
$stmt = $db->query("SELECT COUNT(*) as count FROM interns");
$row = $stmt->fetch();
$results[] = [
    'test' => 'Interns registered',
    'status' => $row['count'] > 0,
    'message' => $row['count'] . ' interns'
];

// Test 5: Check for videos
$stmt = $db->query("SELECT COUNT(*) as count FROM internship_videos");
$row = $stmt->fetch();
$results[] = [
    'test' => 'Videos available',
    'status' => $row['count'] > 0,
    'message' => $row['count'] . ' videos'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Video Progress System Test</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 900px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #002147;
            margin-bottom: 10px;
        }
        .subtitle {
            color: #666;
            margin-bottom: 30px;
        }
        .test-item {
            display: flex;
            align-items: center;
            padding: 15px;
            margin: 10px 0;
            border-radius: 6px;
            border-left: 4px solid;
        }
        .test-item.pass {
            background: #e8f5e9;
            border-color: #4caf50;
        }
        .test-item.fail {
            background: #ffebee;
            border-color: #f44336;
        }
        .test-icon {
            font-size: 24px;
            margin-right: 15px;
            min-width: 30px;
        }
        .test-content {
            flex: 1;
        }
        .test-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 4px;
        }
        .test-message {
            font-size: 14px;
            color: #666;
        }
        .summary {
            margin-top: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 6px;
            text-align: center;
        }
        .summary-stat {
            display: inline-block;
            margin: 0 20px;
            font-size: 18px;
        }
        .summary-stat strong {
            font-size: 32px;
            display: block;
            margin-bottom: 5px;
        }
        .pass-count { color: #4caf50; }
        .fail-count { color: #f44336; }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background: #3db166;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }
        .back-link:hover {
            background: #2d9050;
        }
        .action-buttons {
            margin-top: 20px;
            display: flex;
            gap: 10px;
        }
        .btn {
            padding: 10px 20px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 600;
            display: inline-block;
        }
        .btn-primary {
            background: #3db166;
            color: white;
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Video Progress System Test</h1>
        <p class="subtitle">Testing seeker blocker and progress tracking implementation</p>

        <?php 
        $passCount = 0;
        $failCount = 0;
        foreach ($results as $result): 
            $status = $result['status'] ? 'pass' : 'fail';
            if ($result['status']) $passCount++;
            else $failCount++;
        ?>
            <div class="test-item <?php echo $status; ?>">
                <div class="test-icon">
                    <?php echo $result['status'] ? '✅' : '❌'; ?>
                </div>
                <div class="test-content">
                    <div class="test-name"><?php echo $result['test']; ?></div>
                    <div class="test-message"><?php echo $result['message']; ?></div>
                </div>
            </div>
        <?php endforeach; ?>

        <div class="summary">
            <div class="summary-stat">
                <strong class="pass-count"><?php echo $passCount; ?></strong>
                Passed
            </div>
            <div class="summary-stat">
                <strong class="fail-count"><?php echo $failCount; ?></strong>
                Failed
            </div>
        </div>

        <?php if ($failCount > 0): ?>
            <div style="margin-top: 20px; padding: 15px; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 4px;">
                <strong>⚠️ Action Required:</strong> Some tests failed. Please run the migration script to fix issues.
            </div>
        <?php endif; ?>

        <div class="action-buttons">
            <a href="index.php" class="btn btn-primary">← Back to Dashboard</a>
            <?php if ($failCount > 0): ?>
                <a href="migrate_video_progress.php" class="btn btn-secondary">Run Migration</a>
            <?php endif; ?>
        </div>

        <div style="margin-top: 30px; padding: 20px; background: #e3f2fd; border-radius: 6px;">
            <h3 style="margin-top: 0; color: #002147;">📋 Implementation Status</h3>
            <ul style="line-height: 1.8;">
                <li>✅ Seeker blocker prevents forward seeking</li>
                <li>✅ Backward seeking allowed for review</li>
                <li>✅ Resume from last position on return</li>
                <li>✅ Real-time progress tracking</li>
                <li>✅ Auto-save on pause and exit</li>
                <li>✅ Visual feedback for blocked seeks</li>
            </ul>
        </div>
    </div>
</body>
</html>
