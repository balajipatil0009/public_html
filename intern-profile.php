<?php
require_once 'backend/config/config.php';
require_once 'backend/config/database.php';
require_once 'backend/includes/functions.php';

if (!isset($_SESSION['intern_id'])) {
    header('Location: intern-login.php');
    exit();
}

$intern_id = $_SESSION['intern_id'];
$database = new Database();
$db = $database->connect();

// Get intern details
$stmt = $db->prepare("SELECT * FROM interns WHERE id = :id");
$stmt->execute(['id' => $intern_id]);
$intern = $stmt->fetch();

// Get active enrollment
$stmt = $db->prepare("SELECT e.*, p.title as internship_title, p.description as internship_desc, p.duration
                      FROM internship_enrollments e
                      JOIN internships p ON e.internship_id = p.id
                      WHERE e.intern_id = :iid AND e.enrollment_status = 'active'
                      ORDER BY e.enrollment_date DESC LIMIT 1");
$stmt->execute(['iid' => $intern_id]);
$enrollment = $stmt->fetch();

if (!$enrollment) {
    $_SESSION['dashboard_error'] = 'No active internship found. Please enroll in a program or contact support.';
    header('Location: no-enrollment.php');
    exit();
}

$internship_id = $enrollment['internship_id'];

// Get all videos for this internship
$stmt = $db->prepare("SELECT * FROM internship_videos WHERE internship_id = :pid ORDER BY display_order ASC");
$stmt->execute(['pid' => $internship_id]);
$videos = $stmt->fetchAll();
$total_videos = count($videos);

// Get video progress for each video (only 2 columns for FETCH_KEY_PAIR)
$stmt = $db->prepare("SELECT video_id, is_completed FROM intern_video_progress WHERE intern_id = :iid");
$stmt->execute(['iid' => $intern_id]);
$progress_data = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$completed_videos = 0;
foreach ($progress_data as $vid => $is_completed) {
    if ($is_completed)
        $completed_videos++;
}

// Get quiz passes (only 2 columns for FETCH_KEY_PAIR)
$stmt = $db->prepare("SELECT q.video_id, qa.is_passed FROM internship_quizzes q 
                      JOIN intern_quiz_attempts qa ON q.id = qa.quiz_id 
                      WHERE qa.intern_id = :iid AND qa.is_passed = 1");
$stmt->execute(['iid' => $intern_id]);
$passed_quizzes = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Check for certificate eligibility
$stmt = $db->prepare("SELECT * FROM view_internship_progress WHERE intern_id = :iid AND internship_id = :pid");
$stmt->execute(['iid' => $intern_id, 'pid' => $internship_id]);
$prog = $stmt->fetch();

$is_eligible = ($prog && $prog['watched_videos'] == $prog['total_videos'] && $prog['quizzes_passed'] == $prog['total_quizzes']);
$progress_percent = ($total_videos > 0) ? round(($completed_videos / $total_videos) * 100) : 0;

// Calculate days since enrollment
$enrollment_date = new DateTime($enrollment['enrollment_date']);
$now = new DateTime();
$days_enrolled = $enrollment_date->diff($now)->days;

// Get quiz attempt history
$stmt = $db->prepare("SELECT qa.*, q.video_id, v.title as video_title 
                      FROM intern_quiz_attempts qa
                      JOIN internship_quizzes q ON qa.quiz_id = q.id
                      JOIN internship_videos v ON q.video_id = v.id
                      WHERE qa.intern_id = :iid
                      ORDER BY qa.attempted_at DESC
                      LIMIT 10");
$stmt->execute(['iid' => $intern_id]);
$quiz_history = $stmt->fetchAll();

// Get total time spent learning (sum of seconds_watched)
$stmt = $db->prepare("SELECT COALESCE(SUM(seconds_watched), 0) as total_seconds FROM intern_video_progress WHERE intern_id = :iid");
$stmt->execute(['iid' => $intern_id]);
$time_data = $stmt->fetch();
$total_seconds_watched = (int) $time_data['total_seconds'];
$hours_watched = floor($total_seconds_watched / 3600);
$minutes_watched = floor(($total_seconds_watched % 3600) / 60);

// Calculate estimated completion time
$remaining_videos = $total_videos - $completed_videos;
$avg_video_duration = 0;
if ($completed_videos > 0 && $total_seconds_watched > 0) {
    $avg_video_duration = $total_seconds_watched / $completed_videos; // Average seconds per video
} else {
    // Default to 15 minutes per video if no data
    $avg_video_duration = 15 * 60;
}
$estimated_remaining_seconds = $remaining_videos * $avg_video_duration;
$estimated_hours = floor($estimated_remaining_seconds / 3600);
$estimated_minutes = floor(($estimated_remaining_seconds % 3600) / 60);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile -
        <?php echo htmlspecialchars($enrollment['internship_title']); ?>
    </title>
    <link rel="stylesheet" href="css/styles.css?v=2026.10">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap"
        rel="stylesheet">
    <style>
        body {
            background: #f5f5f5;
            color: #333333;
            min-height: 100vh;
            font-family: 'Poppins', sans-serif;
        }

        .profile-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 4rem 2rem;
        }

        .profile-header {
            display: flex;
            align-items: center;
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .profile-avatar {
            width: 100px;
            height: 100px;
            background: rgba(0, 33, 71, 0.05);
            border: 2px solid #002147;
            border-radius: 2px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            font-weight: 700;
            color: #002147;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .profile-info h1 {
            font-family: 'Playfair Display', serif;
            font-size: 2.25rem;
            font-weight: 600;
            color: #002147;
            margin-bottom: 0.25rem;
        }

        .profile-info p {
            color: #3db166;
            font-family: 'Poppins', sans-serif;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.1em;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2.5rem;
        }

        .stat-card {
            background: #ffffff;
            border: 1px solid #eeeeee;
            border-bottom: 3px solid #002147;
            border-radius: 2px;
            padding: 1.75rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }

        .stat-label {
            font-size: 0.7rem;
            color: #999999;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .stat-value {
            font-family: 'Poppins', sans-serif;
            font-size: 1.75rem;
            font-weight: 700;
            color: #002147;
        }

        .stat-value.success {
            color: #3db166;
        }

        .progress-section {
            background: #ffffff;
            border: 1px solid #eeeeee;
            border-radius: 2px;
            padding: 2.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }

        .progress-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.75rem;
            font-weight: 600;
            color: #002147;
            margin-bottom: 0.5rem;
        }

        .progress-subtitle {
            color: #666666;
            font-size: 0.875rem;
            margin-bottom: 2rem;
        }

        .big-progress-bar {
            height: 10px;
            background: #f0f0f0;
            border-radius: 2px;
            overflow: hidden;
            margin-bottom: 1.5rem;
        }

        .big-progress-fill {
            height: 100%;
            background: #3db166;
            transition: width 1s ease;
        }

        .progress-stats {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .progress-percent {
            font-family: 'Poppins', sans-serif;
            font-size: 3rem;
            font-weight: 700;
            color: #3db166;
        }

        .progress-meta {
            text-align: right;
        }

        .progress-meta span {
            display: block;
            color: #666666;
            font-size: 0.875rem;
        }

        .progress-meta strong {
            color: #002147;
            font-weight: 600;
        }

        /* Certificate Section */
        .certificate-section {
            background: #ffffff;
            border: 1px solid #eeeeee;
            border-top: 5px solid #3db166;
            border-radius: 2px;
            padding: 3rem;
            text-align: center;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .certificate-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, transparent, #22c55e, transparent);
        }

        .certificate-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            color: #3db166;
        }

        .certificate-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.75rem;
            font-weight: 600;
            color: #002147;
            margin-bottom: 0.75rem;
        }

        .certificate-subtitle {
            color: #666666;
            margin-bottom: 2rem;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }

        .btn-certificate {
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            background: #3db166;
            color: white;
            padding: 1.25rem 2.5rem;
            border-radius: 2px;
            font-weight: 600;
            text-decoration: none;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            transition: all 0.3s ease;
        }

        .btn-certificate:hover {
            background: #2d9050;
            transform: translateY(-2px);
        }

        /* Incomplete Section */
        .incomplete-section {
            background: #ffffff;
            border: 1px solid #eeeeee;
            border-radius: 2px;
            padding: 3rem;
            text-align: center;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
        }

        .incomplete-icon {
            font-size: 3rem;
            margin-bottom: 1.5rem;
            color: #eeeeee;
        }

        .incomplete-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.75rem;
            font-weight: 600;
            color: #002147;
            margin-bottom: 0.75rem;
        }

        .incomplete-subtitle {
            color: #666666;
            margin-bottom: 2rem;
        }

        .btn-continue {
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            background: #002147;
            color: #ffffff;
            padding: 1rem 2rem;
            border-radius: 2px;
            font-weight: 600;
            text-decoration: none;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            transition: all 0.3s ease;
        }

        .btn-continue:hover {
            background: #3db166;
            transform: translateY(-2px);
        }

        /* Video List */
        .videos-section {
            margin-top: 3rem;
        }

        .section-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            font-weight: 600;
            color: #002147;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .section-title span {
            font-size: 0.75rem;
            color: #999999;
            font-family: 'Poppins', sans-serif;
            font-weight: 400;
        }

        .video-list {
            display: grid;
            gap: 1rem;
        }

        .video-item {
            background: #ffffff;
            border: 1px solid #eeeeee;
            border-radius: 2px;
            padding: 1.25rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: all 0.3s ease;
        }

        .video-item.completed {
            border-left: 4px solid #3db166;
        }

        .video-item:hover {
            border-color: #3db166;
        }

        .video-status {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.875rem;
            flex-shrink: 0;
        }

        .video-status.completed {
            background: rgba(61, 177, 102, 0.05);
            color: #3db166;
        }

        .video-status.pending {
            background: #f5f5f5;
            color: #cccccc;
        }

        .video-details {
            flex: 1;
        }

        .video-title {
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            font-size: 0.9375rem;
            color: #002147;
            margin-bottom: 0.2rem;
        }

        .video-meta {
            font-size: 0.7rem;
            color: #999999;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        /* Back Link */
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: #52525b;
            text-decoration: none;
            font-size: 0.875rem;
            margin-bottom: 2rem;
            transition: color 0.3s ease;
        }

        .back-link:hover {
            color: #3db166;
        }

        @media (max-width: 768px) {
            .profile-container {
                padding: 2rem 1rem;
            }

            .profile-header {
                flex-direction: column;
                text-align: center;
            }

            .progress-stats {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .progress-meta {
                text-align: center;
            }
        }
    </style>
</head>

<body>
    <div class="profile-container">
        <a href="intern-dashboard.php" class="back-link">â† Back to Learning Portal</a>

        <div class="profile-header">
            <div class="profile-avatar">
                <?php echo strtoupper(substr($intern['full_name'], 0, 1)); ?>
            </div>
            <div class="profile-info">
                <h1>
                    <?php echo htmlspecialchars($intern['full_name']); ?>
                </h1>
                <p>
                    <?php echo htmlspecialchars($enrollment['internship_title']); ?>
                </p>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Videos Completed</div>
                <div class="stat-value">
                    <?php echo $completed_videos; ?>/
                    <?php echo $total_videos; ?>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Quizzes Passed</div>
                <div class="stat-value">
                    <?php echo (int) ($prog['quizzes_passed'] ?? 0); ?>/
                    <?php echo (int) ($prog['total_quizzes'] ?? 0); ?>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Days Enrolled</div>
                <div class="stat-value">
                    <?php echo $days_enrolled; ?>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Status</div>
                <div class="stat-value <?php echo $is_eligible ? 'success' : ''; ?>">
                    <?php if ($is_eligible): ?>
                        <i class="fas fa-check-circle"></i> Complete
                    <?php else: ?>
                        In Progress
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div
            style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem; margin-bottom: 3rem;">
            <!-- Roll Number Card -->
            <div class="stat-card"
                style="border-bottom: 3px solid #3db166; display: flex; flex-direction: column; justify-content: center; text-align: center;">
                <div class="stat-label">Official Roll Number</div>
                <div
                    style="font-family: 'Space Mono', monospace; font-size: 1.5rem; font-weight: 700; color: #002147; letter-spacing: -1px; margin: 0.5rem 0;">
                    <?php echo htmlspecialchars($enrollment['roll_no'] ?: 'PENDING'); ?>
                </div>
                <div style="font-size: 0.7rem; color: #999; text-transform: uppercase;">Authenticated Identity</div>
            </div>

            <!-- Academic Info -->
            <div class="stat-card" style="border-bottom: 3px solid #3db166;">
                <div class="stat-label">Academic Profile</div>
                <div style="margin-top: 1rem;">
                    <div
                        style="display: flex; justify-content: space-between; margin-bottom: 0.5rem; font-size: 0.9rem;">
                        <span style="color: #666;">College:</span>
                        <strong
                            style="color: #002147;"><?php echo htmlspecialchars($intern['college_name']); ?></strong>
                    </div>
                    <div
                        style="display: flex; justify-content: space-between; margin-bottom: 0.5rem; font-size: 0.9rem;">
                        <span style="color: #666;">Degree:</span>
                        <strong
                            style="color: #002147;"><?php echo htmlspecialchars($intern['academic_degree']); ?></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 0.9rem;">
                        <span style="color: #666;">Year:</span>
                        <strong
                            style="color: #002147;"><?php echo htmlspecialchars($intern['current_year']); ?></strong>
                    </div>
                </div>
            </div>

            <!-- Personal Info -->
            <div class="stat-card" style="border-bottom: 3px solid #3db166;">
                <div class="stat-label">Personal Correspondence</div>
                <div style="margin-top: 1rem;">
                    <div
                        style="display: flex; justify-content: space-between; margin-bottom: 0.5rem; font-size: 0.9rem;">
                        <span style="color: #666;">Email:</span>
                        <strong style="color: #002147;"><?php echo htmlspecialchars($intern['email']); ?></strong>
                    </div>
                    <div
                        style="display: flex; justify-content: space-between; margin-bottom: 0.5rem; font-size: 0.9rem;">
                        <span style="color: #666;">Phone:</span>
                        <strong style="color: #002147;"><?php echo htmlspecialchars($intern['phone']); ?></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 0.9rem;">
                        <span style="color: #666;">Gender:</span>
                        <strong style="color: #002147;"><?php echo htmlspecialchars($intern['gender']); ?></strong>
                    </div>
                </div>
            </div>
        </div>

        <div class="progress-section">
            <h2 class="progress-title">
                <?php echo htmlspecialchars($enrollment['internship_title']); ?>
            </h2>
            <p class="progress-subtitle">Track your progress through the internship curriculum</p>

            <div class="big-progress-bar">
                <div class="big-progress-fill" style="width: <?php echo $progress_percent; ?>%;"></div>
            </div>

            <div class="progress-stats">
                <div class="progress-percent">
                    <?php echo $progress_percent; ?>%
                </div>
                <div class="progress-meta">
                    <span><strong>
                            <?php echo $completed_videos; ?>
                        </strong> of
                        <strong>
                            <?php echo $total_videos; ?>
                        </strong> modules completed</span>
                    <span>Duration:
                        <?php echo htmlspecialchars($enrollment['duration']); ?>
                    </span>
                </div>
            </div>
        </div>

        <?php if ($is_eligible): ?>
            <div class="certificate-section">
                <div class="certificate-icon"><i class="fas fa-graduation-cap"></i></div>
                <h2 class="certificate-title">Congratulations! You've Completed the Internship!</h2>
                <p class="certificate-subtitle">You have successfully completed all video modules and passed all
                    assessments. Your certificate is ready for download.</p>
                <a href="generate-certificate.php?id=<?php echo $enrollment['id']; ?>" target="_blank"
                    class="btn-certificate">
                    Download Certificate
                </a>
            </div>
        <?php else: ?>
            <div class="incomplete-section">
                <div class="incomplete-icon"><i class="fas fa-book-reader"></i></div>
                <h2 class="incomplete-title">Keep Going!</h2>
                <p class="incomplete-subtitle">Complete all video modules and pass the assessments to unlock your
                    certificate.</p>
                <a href="intern-dashboard.php" class="btn-continue">
                    Continue Learning
                </a>
            </div>
        <?php endif; ?>

        <div class="videos-section">
            <h3 class="section-title">
                Module Progress
                <span>
                    <?php echo $completed_videos; ?>/
                    <?php echo $total_videos; ?> COMPLETED
                </span>
            </h3>
            <div class="video-list">
                <?php foreach ($videos as $index => $video):
                    $is_completed = isset($progress_data[$video['id']]) && $progress_data[$video['id']];
                    $has_passed_quiz = isset($passed_quizzes[$video['id']]);
                    ?>
                    <div class="video-item <?php echo $is_completed ? 'completed' : ''; ?>">
                        <div class="video-status <?php echo $is_completed ? 'completed' : 'pending'; ?>">
                            <?php if ($is_completed): ?>
                                <i class="fas fa-check"></i>
                            <?php else: ?>
                                <?php echo ($index + 1); ?>
                            <?php endif; ?>
                        </div>
                        <div class="video-details">
                            <div class="video-title">
                                <?php echo htmlspecialchars($video['title']); ?>
                            </div>
                            <div class="video-meta">
                                <?php echo htmlspecialchars($video['duration']); ?> MINS
                                <?php if ($is_completed): ?>
                                    â€¢ VIDEO COMPLETED
                                <?php endif; ?>
                                <?php if ($has_passed_quiz): ?>
                                    â€¢ QUIZ PASSED
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Learning Analytics Section -->
        <div class="videos-section">
            <h3 class="section-title">
                Learning Analytics
                <span>YOUR STATS</span>
            </h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                <div class="stat-card" style="text-align: center;">
                    <div class="stat-label">Time Spent Learning</div>
                    <div class="stat-value" style="font-size: 1.5rem;">
                        <?php if ($hours_watched > 0): ?>
                            <?php echo $hours_watched; ?>h <?php echo $minutes_watched; ?>m
                        <?php else: ?>
                            <?php echo $minutes_watched; ?> mins
                        <?php endif; ?>
                    </div>
                </div>
                <div class="stat-card" style="text-align: center;">
                    <div class="stat-label">Days Active</div>
                    <div class="stat-value" style="font-size: 1.5rem;"><?php echo $days_enrolled; ?></div>
                </div>
                <div class="stat-card" style="text-align: center;">
                    <div class="stat-label">Est. Time to Complete</div>
                    <div class="stat-value" style="font-size: 1.5rem;">
                        <?php if ($remaining_videos == 0): ?>
                            <span style="color: #10b981;">Complete!</span>
                        <?php elseif ($estimated_hours > 0): ?>
                            ~<?php echo $estimated_hours; ?>h <?php echo $estimated_minutes; ?>m
                        <?php else: ?>
                            ~<?php echo $estimated_minutes; ?> mins
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quiz History Section -->
        <?php if (!empty($quiz_history)): ?>
        <div class="videos-section">
            <h3 class="section-title">
                Quiz History
                <span><?php echo count($quiz_history); ?> ATTEMPTS</span>
            </h3>
            <div class="video-list">
                <?php foreach ($quiz_history as $attempt): ?>
                    <div class="video-item <?php echo $attempt['is_passed'] ? 'completed' : ''; ?>">
                        <div class="video-status <?php echo $attempt['is_passed'] ? 'completed' : 'pending'; ?>">
                            <?php if ($attempt['is_passed']): ?>
                                <i class="fas fa-check"></i>
                            <?php else: ?>
                                <i class="fas fa-times" style="color: #ef4444;"></i>
                            <?php endif; ?>
                        </div>
                        <div class="video-details">
                            <div class="video-title"><?php echo htmlspecialchars($attempt['video_title']); ?></div>
                            <div class="video-meta">
                                <?php echo date('M d, Y \a\t h:i A', strtotime($attempt['attempted_at'])); ?>
                                â€¢ SCORE: <strong style="color: <?php echo $attempt['is_passed'] ? '#10b981' : '#ef4444'; ?>;"><?php echo $attempt['score']; ?>%</strong>
                                â€¢ <?php echo $attempt['is_passed'] ? 'PASSED' : 'FAILED'; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</body>

</html>
