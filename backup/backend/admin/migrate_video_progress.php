<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/auth.php';

$auth = new Auth();
$auth->requireLogin();

// Only super_admin can run migrations
if (!$auth->hasRole('super_admin')) {
    die('Access denied. Only super administrators can run migrations.');
}

$database = new Database();
$db = $database->connect();

if (!$db) {
    die('Database connection failed.');
}

$success = true;
$messages = [];

try {
    // Check if columns already exist
    $stmt = $db->query("SHOW COLUMNS FROM intern_video_progress LIKE 'seconds_watched'");
    $hasSecondsWatched = $stmt->rowCount() > 0;

    if (!$hasSecondsWatched) {
        // Add seconds_watched column
        $db->exec("ALTER TABLE intern_video_progress 
                   ADD COLUMN seconds_watched INT DEFAULT 0 COMMENT 'Maximum seconds watched in the video'");
        $messages[] = "✓ Added 'seconds_watched' column";
    } else {
        $messages[] = "• Column 'seconds_watched' already exists";
    }

    // Check for total_seconds
    $stmt = $db->query("SHOW COLUMNS FROM intern_video_progress LIKE 'total_seconds'");
    $hasTotalSeconds = $stmt->rowCount() > 0;

    if (!$hasTotalSeconds) {
        // Add total_seconds column
        $db->exec("ALTER TABLE intern_video_progress 
                   ADD COLUMN total_seconds INT DEFAULT 0 COMMENT 'Total video duration in seconds'");
        $messages[] = "✓ Added 'total_seconds' column";
    } else {
        $messages[] = "• Column 'total_seconds' already exists";
    }

    // Check for updated_at
    $stmt = $db->query("SHOW COLUMNS FROM intern_video_progress LIKE 'updated_at'");
    $hasUpdatedAt = $stmt->rowCount() > 0;

    if (!$hasUpdatedAt) {
        // Add updated_at column
        $db->exec("ALTER TABLE intern_video_progress 
                   ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP 
                   COMMENT 'Last progress update time'");
        $messages[] = "✓ Added 'updated_at' column";
    } else {
        $messages[] = "• Column 'updated_at' already exists";
    }

    // Update existing records
    $db->exec("UPDATE intern_video_progress 
               SET seconds_watched = COALESCE(seconds_watched, 0), 
                   total_seconds = COALESCE(total_seconds, 0) 
               WHERE seconds_watched IS NULL OR total_seconds IS NULL");
    $messages[] = "✓ Updated existing records with default values";

    $messages[] = "<br><strong>Migration completed successfully!</strong>";
    $messages[] = "Video progress tracking with resume functionality is now enabled.";

} catch (Exception $e) {
    $success = false;
    $messages[] = "✗ Error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Video Progress Migration</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            max-width: 800px;
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
            margin-bottom: 20px;
        }
        .message {
            padding: 10px 15px;
            margin: 10px 0;
            border-radius: 4px;
            background: <?php echo $success ? '#e8f5e9' : '#ffebee'; ?>;
            border-left: 4px solid <?php echo $success ? '#4caf50' : '#f44336'; ?>;
        }
        .message-item {
            margin: 8px 0;
            color: #333;
        }
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
    </style>
</head>
<body>
    <div class="container">
        <h1>Video Progress Tracking Migration</h1>
        <div class="message">
            <?php foreach ($messages as $msg): ?>
                <div class="message-item"><?php echo $msg; ?></div>
            <?php endforeach; ?>
        </div>
        <a href="index.php" class="back-link">← Back to Dashboard</a>
    </div>
</body>
</html>
