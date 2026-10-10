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

// Get active enrollment
$stmt = $db->prepare("SELECT e.*, p.title as internship_title, p.description as internship_desc
                      FROM internship_enrollments e
                      JOIN internships p ON e.internship_id = p.id
                      WHERE e.intern_id = :iid AND e.enrollment_status = 'active'
                      ORDER BY e.enrollment_date DESC LIMIT 1");
$stmt->execute(['iid' => $intern_id]);
$enrollment = $stmt->fetch();

if (!$enrollment) {
    // Redirect to a styled error page instead of raw die()
    $_SESSION['dashboard_error'] = 'No active internship found. Please enroll in a program or contact support if you believe this is an error.';
    header('Location: no-enrollment.php');
    exit();
}

$internship_id = $enrollment['internship_id'];

// Get all videos
$stmt = $db->prepare("SELECT * FROM internship_videos WHERE internship_id = :pid ORDER BY display_order ASC");
$stmt->execute(['pid' => $internship_id]);
$videos = $stmt->fetchAll();

// Get progress
$stmt = $db->prepare("SELECT video_id, is_completed FROM intern_video_progress WHERE intern_id = :iid");
$stmt->execute(['iid' => $intern_id]);
$watched_videos = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Get quiz passes
$stmt = $db->prepare("SELECT q.video_id, qa.is_passed FROM internship_quizzes q 
                      JOIN intern_quiz_attempts qa ON q.id = qa.quiz_id 
                      WHERE qa.intern_id = :iid AND qa.is_passed = 1");
$stmt->execute(['iid' => $intern_id]);
$passed_quizzes = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Determine unlocked videos
$unlocked_videos = [];
$first_incomplete_found = false;

foreach ($videos as $index => $v) {
    if ($index === 0) {
        $unlocked_videos[$v['id']] = true; // Always unlock first video
    } else {
        $prev_v = $videos[$index - 1];
        // Unlock if previous video is watched AND (no quiz exists OR previous quiz passed)
        $prev_watched = isset($watched_videos[$prev_v['id']]) && $watched_videos[$prev_v['id']] == 1;

        // Check if prev video had a quiz
        $qStmt = $db->prepare("SELECT id FROM internship_quizzes WHERE video_id = :vid");
        $qStmt->execute(['vid' => $prev_v['id']]);
        $has_quiz = $qStmt->fetch();

        if ($prev_watched && (!$has_quiz || isset($passed_quizzes[$prev_v['id']]))) {
            $unlocked_videos[$v['id']] = true;
        } else {
            $unlocked_videos[$v['id']] = false;
        }
    }
}

$current_video_id = isset($_GET['video']) ? (int) $_GET['video'] : null;
$current_video = null;

if ($current_video_id) {
    foreach ($videos as $v) {
        if ($v['id'] == $current_video_id && ($unlocked_videos[$v['id']] ?? false)) {
            $current_video = $v;
            break;
        }
    }
}

if (!$current_video) {
    // Default to the first incomplete video that is unlocked
    foreach ($videos as $v) {
        if ($unlocked_videos[$v['id']] && (!isset($watched_videos[$v['id']]) || !$watched_videos[$v['id']])) {
            $current_video = $v;
            break;
        }
    }
    // If all completed, default to the first one
    if (!$current_video && count($videos) > 0) {
        $current_video = $videos[0];
    }
}

// Check for certificate eligibility
$stmt = $db->prepare("SELECT * FROM view_internship_progress WHERE intern_id = :iid AND internship_id = :pid");
$stmt->execute(['iid' => $intern_id, 'pid' => $internship_id]);
$prog = $stmt->fetch();
$eligible_for_cert = ($prog && $prog['watched_videos'] == $prog['total_videos'] && $prog['quizzes_passed'] == $prog['total_quizzes']);

// Pre-fetch current video progress if a video is selected
$prog_data = null;
$is_completed = false;
$saved_seconds = 0;
$total_seconds = 0;
$saved_percent = 0;

if ($current_video) {
    $v_progress = $db->prepare("SELECT is_completed, seconds_watched, total_seconds FROM intern_video_progress WHERE intern_id = :iid AND video_id = :vid");
    $v_progress->execute(['iid' => $intern_id, 'vid' => $current_video['id']]);
    $prog_data = $v_progress->fetch();
    $is_completed = $prog_data && $prog_data['is_completed'];

    // Pre-calculate progress for persistent UI
    $saved_seconds = $prog_data ? (int) $prog_data['seconds_watched'] : 0;
    $total_seconds = $prog_data ? (int) $prog_data['total_seconds'] : 0;
    $saved_percent = ($total_seconds > 0) ? min(100, round(($saved_seconds / $total_seconds) * 100)) : 0;
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Intern Dashboard -
        <?php echo htmlspecialchars($enrollment['internship_title']); ?>
    </title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --sidebar-width: 300px;
        }

        body {
            background: #f5f5f5;
            color: #333333;
        }

        .dashboard-wrapper {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar - Kingster Navy */
        .dash-sidebar {
            width: var(--sidebar-width);
            background: #002147;
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100vh;
        }

        .sidebar-header {
            padding: 1.75rem 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .video-list {
            flex: 1;
            overflow-y: auto;
            padding: 1rem;
        }

        .video-item {
            display: flex;
            align-items: center;
            padding: 1rem 1.25rem;
            border-radius: 2px;
            margin-bottom: 0.25rem;
            cursor: pointer;
            transition: 0.3s;
            text-decoration: none;
            color: rgba(255, 255, 255, 0.7);
            border-left: 3px solid transparent;
        }

        .video-item.active {
            background: rgba(61, 177, 102, 0.15);
            border-left-color: #3db166;
            color: #ffffff;
        }

        .video-item.locked {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .video-item:hover:not(.locked) {
            background: rgba(255, 255, 255, 0.05);
            color: #ffffff;
        }

        .v-icon {
            margin-right: 1rem;
            font-size: 1.25rem;
        }

        .v-info {
            flex: 1;
        }

        .v-title {
            display: block;
            font-weight: 500;
            font-size: 0.9rem;
            line-height: 1.3;
        }

        .v-meta {
            font-size: 0.75rem;
            color: rgba(255, 255, 255, 0.5);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        /* Main Content */
        .dash-main {
            margin-left: var(--sidebar-width);
            flex: 1;
            padding: 2.5rem;
        }

        .dash-content-grid {
            display: grid;
            grid-template-columns: 1fr 340px;
            gap: 2rem;
            max-width: 1400px;
            margin: 0 auto;
        }

        .dash-content-left {
            min-width: 0;
            /* Prevents grid blowout */
        }

        .dash-content-right {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .video-player-container {
            background: #000;
            border-radius: 2px;
            overflow: hidden;
            aspect-ratio: 16/9;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
            margin-bottom: 2rem;
            position: relative;
        }

        .video-player-container iframe,
        .video-player-container video {
            width: 100%;
            height: 100%;
            border: none;
        }

        .video-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 10;
            pointer-events: none;
        }

        .content-card {
            background: #ffffff;
            padding: 2rem;
            border-radius: 2px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            margin-bottom: 1.5rem;
        }

        .content-title {
            font-family: 'Playfair Display', Georgia, serif;
            font-size: 1.75rem;
            margin-bottom: 0.75rem;
            font-weight: 600;
            color: #002147;
        }

        .quiz-section {
            background: #e8f5e9;
            border: 1px solid #c8e6c9;
            border-left: 4px solid #3db166;
            padding: 2rem;
            border-radius: 2px;
            margin-top: 1.5rem;
            transition: 0.3s;
        }

        .quiz-section.locked {
            opacity: 0.5;
            filter: grayscale(1);
            pointer-events: none;
        }

        .btn-quiz {
            background: #3db166;
            color: white;
            padding: 0.875rem 2rem;
            border-radius: 2px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: 0.3s;
            display: inline-block;
            text-decoration: none;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            font-size: 0.8125rem;
        }

        .btn-quiz:hover {
            background: #2d9050;
        }

        .cert-card {
            background: linear-gradient(135deg, #3db166 0%, #2d9050 100%);
            color: white;
            padding: 1.75rem 2rem;
            border-radius: 2px;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .cert-btn {
            background: white;
            color: #2d9050;
            padding: 0.75rem 1.5rem;
            border-radius: 2px;
            font-weight: 600;
            text-decoration: none;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            font-size: 0.8125rem;
        }

        .glass-card {
            background: #ffffff;
            padding: 2.5rem;
            border-radius: 2px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
        }

        .text-mono-premium {
            font-family: 'Poppins', sans-serif;
            font-size: 0.75rem;
            font-weight: 600;
            color: #3db166;
            letter-spacing: 0.1em;
        }

        .portal-progress-bar {
            height: 8px;
            background: #e5e5e5;
            border-radius: 2px;
            overflow: hidden;
            margin-top: 1rem;
        }

        .portal-progress-fill {
            height: 100%;
            background: #3db166;
            transition: width 0.3s ease;
        }

        .btn-portal-primary {
            display: inline-block;
            background: #3db166;
            color: white;
            padding: 0.875rem 2rem;
            border-radius: 2px;
            text-decoration: none;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8125rem;
            letter-spacing: 0.03em;
            transition: 0.3s;
        }

        .btn-portal-primary:hover {
            background: #2d9050;
        }

        /* Mobile Responsive Enhancements */
        .mobile-header {
            display: none;
            background: #002147;
            padding: 1rem 1.5rem;
            align-items: center;
            justify-content: space-between;
            color: #ffffff;
            position: sticky;
            top: 0;
            z-index: 999;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .menu-toggle {
            background: none;
            border: none;
            color: #ffffff;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            backdrop-filter: blur(2px);
        }

        @media (max-width: 1024px) {
            .mobile-header {
                display: flex;
            }

            .dash-sidebar {
                transform: translateX(-100%);
                z-index: 1001;
                transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                box-shadow: 10px 0 25px rgba(0, 0, 0, 0.3);
            }

            .dash-sidebar.active {
                transform: translateX(0);
            }

            .sidebar-overlay.active {
                display: block;
            }

            .dash-main {
                margin-left: 0;
                padding: 1.5rem;
                padding-top: 2rem;
            }

            .dash-content-grid {
                grid-template-columns: 1fr;
            }

            .video-list {
                padding-bottom: 5rem;
                /* Ensure bottom items aren't cut off */
            }
        }
    </style>
</head>

<body>
    <!-- Video Policy Pop-up -->
    <div id="policyModal"
        style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.7); z-index: 9999; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
        <div
            style="background: #ffffff; padding: 3rem; border-radius: 8px; max-width: 600px; width: 90%; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3); animation: slideIn 0.3s ease-out;">
            <div style="text-align: center; margin-bottom: 2rem;">
                <div
                    style="width: 80px; height: 80px; margin: 0 auto 1.5rem; background: linear-gradient(135deg, #3db166 0%, #2d9050 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 8px 20px rgba(61, 177, 102, 0.3);">
                    <i class="fas fa-exclamation-circle" style="font-size: 2.5rem; color: #ffffff;"></i>
                </div>
                <h2
                    style="font-family: 'Playfair Display', serif; font-size: 1.75rem; color: #002147; margin-bottom: 0.5rem;">
                    Important Guidelines</h2>
                <p style="color: #666; font-size: 0.95rem; margin: 0;">Please read carefully before proceeding</p>
            </div>

            <div
                style="background: #f8f9fa; padding: 1.75rem; border-radius: 6px; border-left: 4px solid #3db166; margin-bottom: 2rem;">
                <h3 style="font-size: 1.1rem; color: #002147; margin: 0 0 1rem 0; font-weight: 600;">
                    <i class="fas fa-video" style="color: #3db166; margin-right: 0.5rem;"></i>Video Watching Policy
                </h3>
                <ul style="margin: 0; padding-left: 1.5rem; color: #333; line-height: 1.8; font-size: 0.95rem;">
                    <li style="margin-bottom: 0.75rem;">Please watch the <strong>entire video in one sitting</strong>
                    </li>
                    <li style="margin-bottom: 0.75rem;"><strong>Forward/Reverse seeking is not approved</strong> to
                        prevent functionality abuse</li>
                    <li style="margin-bottom: 0.75rem;">Watch each video <strong>completely in a single session</strong>
                        for best learning experience</li>
                    <li style="margin-bottom: 0.75rem;">Your progress is tracked to ensure course completion integrity
                    </li>
                </ul>
            </div>

            <div style="background: #e3f2fd; padding: 1.25rem; border-radius: 6px; margin-bottom: 2rem;">
                <p
                    style="margin: 0; font-size: 0.9rem; color: #1976d2; display: flex; align-items: flex-start; gap: 0.75rem;">
                    <i class="fas fa-info-circle" style="font-size: 1.2rem; margin-top: 0.1rem;"></i>
                    <span><strong>Need Help?</strong> For any queries or technical issues, reach out to: <a
                            href="mailto:<?php echo EnvLoader::get('MAIL_TO_ADMIN', 'validatoritinfoacademy@gmail.com'); ?>"
                            style="color: #1976d2; font-weight: 600; text-decoration: underline;"><?php echo EnvLoader::get('MAIL_TO_ADMIN', 'validatoritinfoacademy@gmail.com'); ?></a></span>
                </p>
            </div>

            <div style="text-align: center;">
                <button onclick="closePolicyModal()"
                    style="background: linear-gradient(135deg, #3db166 0%, #2d9050 100%); color: white; padding: 0.875rem 3rem; border: none; border-radius: 6px; font-weight: 600; font-size: 1rem; cursor: pointer; text-transform: uppercase; letter-spacing: 0.05em; box-shadow: 0 4px 12px rgba(61, 177, 102, 0.3); transition: all 0.3s;">
                    I Understand
                </button>
            </div>
        </div>
    </div>

    <style>
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="mobile-header">
        <div style="display: flex; align-items: center; gap: 1rem;">
            <button class="menu-toggle" id="menuToggle">
                <i class="fas fa-bars"></i>
            </button>
            <span style="font-family: 'Playfair Display', serif; font-size: 1.1rem; font-weight: 700;">Dashboard</span>
        </div>
        <div
            style="font-size: 0.7rem; color: #3db166; font-weight: 600; text-transform: uppercase; letter-spacing: 0.1em;">
            <?php echo strtoupper(substr($_SESSION['intern_name'], 0, 1)); ?> PORTAL
        </div>
    </div>
    <div class="dashboard-wrapper">
        <!-- Sidebar -->
        <aside class="dash-sidebar">
            <div class="sidebar-header">
                <a href="index.php" style="text-decoration: none;">
                    <h2
                        style="font-family: 'Playfair Display', Georgia, serif; font-size: 1.25rem; font-weight: 700; color: #ffffff; margin-bottom: 0.25rem;">
                        <?php echo SITE_NAME; ?>
                    </h2>
                </a>
                <p
                    style="font-size: 0.7rem; color: rgba(255,255,255,0.5); text-transform: uppercase; letter-spacing: 0.1em;">
                    <?php echo htmlspecialchars($enrollment['internship_title']); ?>
                </p>
            </div>

            <div class="video-list">
                <?php foreach ($videos as $index => $v):
                    $is_unlocked = $unlocked_videos[$v['id']];
                    $is_active = $current_video['id'] == $v['id'];
                    $is_watched = isset($watched_videos[$v['id']]) && $watched_videos[$v['id']];
                    $is_passed = isset($passed_quizzes[$v['id']]);
                    ?>
                    <a href="<?php echo $is_unlocked ? '?video=' . $v['id'] : '#'; ?>"
                        class="video-item <?php echo $is_active ? 'active' : ''; ?> <?php echo !$is_unlocked ? 'locked' : ''; ?>">
                        <div style="width: 8px; height: 8px; border-radius: 50%; margin-right: 12px; flex-shrink: 0;
                            background: <?php echo !$is_unlocked ? '#666' : ($is_watched ? '#3db166' : '#fff'); ?>;
                            <?php if ($is_watched): ?>box-shadow: 0 0 8px #3db166;<?php endif; ?>">
                        </div>
                        <div class="v-info">
                            <span class="v-title"><?php echo htmlspecialchars($v['title']); ?></span>
                            <span class="v-meta"><?php echo htmlspecialchars($v['duration']); ?> MINS</span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>

            <div
                style="padding: 1.25rem 1.5rem; border-top: 1px solid rgba(255,255,255,0.1); background: rgba(0,0,0,0.2);">
                <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                    <div
                        style="width: 40px; height: 40px; background: rgba(61,177,102,0.2); border: 1px solid rgba(61,177,102,0.4); border-radius: 2px; display: flex; align-items: center; justify-content: center; font-weight: 600; color: #3db166; font-size: 0.9rem;">
                        <?php echo strtoupper(substr($_SESSION['intern_name'], 0, 1)); ?>
                    </div>
                    <div style="flex: 1; overflow: hidden;">
                        <span
                            style="display: block; font-size: 0.85rem; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: #ffffff;"><?php echo htmlspecialchars($_SESSION['intern_name']); ?></span>
                        <span style="font-size: 0.7rem; color: rgba(255,255,255,0.5);">Intern</span>
                    </div>
                </div>
                <div style="display: flex; gap: 0.5rem;">
                    <a href="intern-profile.php"
                        style="flex: 1; padding: 0.5rem; background: #3db166; border: none; color: #fff; text-align: center; text-decoration: none; border-radius: 2px; font-size: 0.7rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.03em;">My
                        Profile</a>
                    <a href="backend/api/intern_logout.php"
                        style="flex: 1; padding: 0.5rem; background: rgba(220,53,69,0.2); border: 1px solid rgba(220,53,69,0.3); color: #ff6b6b; text-align: center; text-decoration: none; border-radius: 2px; font-size: 0.7rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.03em;">Logout</a>
                </div>
            </div>
        </aside>

        <!-- Main -->
        <main class="dash-main">
            <div class="dash-content-grid">
                <div class="dash-content-left">
                    <?php if ($current_video): ?>
                        <div class="protected-video-container" style="position: relative;">
                            <video id="protected-player" controls controlsList="nodownload" oncontextmenu="return false;"
                                style="width:100%; height:100%;">
                                <source src="<?php echo htmlspecialchars($current_video['local_path']); ?>"
                                    type="video/mp4">
                                Browser not compatible with secure playback.
                            </video>
                            <!-- Speed Control Button -->
                            <div id="speed-control" style="position: absolute; bottom: 50px; right: 15px; z-index: 100;">
                                <button id="speed-btn"
                                    style="background: rgba(0,0,0,0.7); color: #fff; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 0.8rem; font-weight: 600; font-family: 'Poppins', sans-serif;"
                                    title="Playback Speed (Shortcut: S)">1x</button>
                                <div id="speed-menu"
                                    style="display: none; position: absolute; bottom: 100%; right: 0; background: rgba(0,0,0,0.9); border-radius: 4px; padding: 0.5rem 0; min-width: 80px; margin-bottom: 5px;">
                                    <div class="speed-option" data-speed="0.5"
                                        style="padding: 0.5rem 1rem; color: #fff; cursor: pointer; font-size: 0.8rem; text-align: center;">
                                        0.5x</div>
                                    <div class="speed-option" data-speed="0.75"
                                        style="padding: 0.5rem 1rem; color: #fff; cursor: pointer; font-size: 0.8rem; text-align: center;">
                                        0.75x</div>
                                    <div class="speed-option active" data-speed="1"
                                        style="padding: 0.5rem 1rem; color: #3db166; cursor: pointer; font-size: 0.8rem; text-align: center; font-weight: 600;">
                                        1x</div>
                                    <div class="speed-option" data-speed="1.25"
                                        style="padding: 0.5rem 1rem; color: #fff; cursor: pointer; font-size: 0.8rem; text-align: center;">
                                        1.25x</div>
                                    <div class="speed-option" data-speed="1.5"
                                        style="padding: 0.5rem 1rem; color: #fff; cursor: pointer; font-size: 0.8rem; text-align: center;">
                                        1.5x</div>
                                    <div class="speed-option" data-speed="2"
                                        style="padding: 0.5rem 1rem; color: #fff; cursor: pointer; font-size: 0.8rem; text-align: center;">
                                        2x</div>
                                </div>
                            </div>
                        </div>

                        <div class="glass-card" style="margin-top: 2rem;">
                            <div
                                style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem;">
                                <div>
                                    <span class="text-mono-premium"
                                        style="display: block; margin-bottom: 0.5rem; text-transform: uppercase;">Module
                                        Performance</span>
                                    <h1
                                        style="font-family: 'Playfair Display', serif; font-size: 2rem; font-weight: 600; color: #002147; margin: 0;">
                                        <?php echo htmlspecialchars($current_video['title']); ?>
                                    </h1>
                                </div>
                                <div id="status-badge-container">
                                    <?php if ($is_completed): ?>
                                        <span
                                            style="background: rgba(16, 185, 129, 0.1); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.2); padding: 0.5rem 1rem; border-radius: 100px; font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;">Validated</span>
                                    <?php else: ?>
                                        <span
                                            style="background: rgba(14, 165, 233, 0.1); color: #0ea5e9; border: 1px solid rgba(14, 165, 233, 0.2); padding: 0.5rem 1rem; border-radius: 100px; font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;">In
                                            Progress</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div style="color: #666666; font-size: 0.95rem; line-height: 1.7; margin-bottom: 0;">
                                <?php echo $current_video['description']; ?>
                            </div>
                        </div>

                    <?php else: ?>
                        <div class="glass-card" style="padding: 3rem 2.5rem;">
                            <div
                                style="font-size: 0.8rem; margin-bottom: 1.5rem; color: #3db166; font-weight: 700; letter-spacing: 0.2em; text-transform: uppercase;">
                                Learning Portal</div>
                            <h2
                                style="font-family: 'Playfair Display', serif; font-size: 2.5rem; color: #002147; margin-bottom: 1rem;">
                                Welcome to <?php echo htmlspecialchars($enrollment['internship_title']); ?>
                            </h2>
                            <p style="color: #666666; font-size: 1.1rem; line-height: 1.6;">
                                Your journey starts here. Follow the curriculum below to master the skills needed for this
                                internship.
                            </p>

                            <div style="margin-top: 3rem;">
                                <h3 style="font-family: 'Playfair Display', serif; color: #002147; margin-bottom: 2rem;">
                                    Program Roadmap</h3>

                                <div style="position: relative; padding-left: 2rem; border-left: 2px solid #eee;">
                                    <div style="margin-bottom: 2.5rem; position: relative;">
                                        <div
                                            style="position: absolute; left: -2.6rem; top: 0; width: 1.2rem; height: 1.2rem; background: #3db166; border-radius: 50%; border: 4px solid #fff; box-shadow: 0 0 0 2px #3db166;">
                                        </div>
                                        <h4 style="margin: 0 0 0.5rem 0; color: #002147;">Phase 1: Foundations</h4>
                                        <p style="margin: 0; color: #666; font-size: 0.9rem;">Complete the first 5 modules
                                            to build your core understanding of the domain.</p>
                                    </div>
                                    <div style="margin-bottom: 2.5rem; position: relative;">
                                        <div
                                            style="position: absolute; left: -2.6rem; top: 0; width: 1.2rem; height: 1.2rem; background: #fff; border-radius: 50%; border: 4px solid #eee;">
                                        </div>
                                        <h4 style="margin: 0 0 0.5rem 0; color: #002147;">Phase 2: Hands-on Projects</h4>
                                        <p style="margin: 0; color: #666; font-size: 0.9rem;">Apply your learning to
                                            real-world scenarios through mid-program assessments.</p>
                                    </div>
                                    <div style="position: relative;">
                                        <div
                                            style="position: absolute; left: -2.6rem; top: 0; width: 1.2rem; height: 1.2rem; background: #fff; border-radius: 50%; border: 4px solid #eee;">
                                        </div>
                                        <h4 style="margin: 0 0 0.5rem 0; color: #002147;">Phase 3: Certification</h4>
                                        <p style="margin: 0; color: #666; font-size: 0.9rem;">Final assessments and issuance
                                            of your professional internship certificate.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="dash-content-right">
                    <?php if ($eligible_for_cert): ?>
                        <div class="cert-card" style="flex-direction: column; text-align: center; gap: 1rem;">
                            <div style="font-size: 2rem;"><i class="fas fa-trophy"></i></div>
                            <div style="font-size: 1.1rem; font-weight: 600;">Course Completed!</div>
                            <a href="generate-certificate.php?id=<?php echo $enrollment['id']; ?>" target="_blank"
                                class="cert-btn" style="width: 100%; box-sizing: border-box; text-align: center;">Get
                                Certificate</a>
                        </div>
                    <?php endif; ?>

                    <?php if ($current_video): ?>
                        <!-- Progress Tracking -->
                        <?php if (!$is_completed): ?>
                            <div class="glass-card" style="padding: 1.5rem; margin-bottom: 0;">
                                <div
                                    style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                    <span
                                        style="font-size: 0.7rem; font-weight: 600; color: #999; text-transform: uppercase;">Module
                                        Progress</span>
                                    <span id="watch-percent"
                                        style="font-size: 0.8rem; font-weight: 600; color: #3db166;"><?php echo $saved_percent; ?>%</span>
                                </div>
                                <div class="portal-progress-bar" style="margin-top: 0;">
                                    <div id="watch-progress-bar" class="portal-progress-fill"
                                        style="width: <?php echo $saved_percent; ?>%;"></div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Quiz Block -->
                        <?php
                        $qStmt = $db->prepare("SELECT id FROM internship_quizzes WHERE video_id = :vid");
                        $qStmt->execute(['vid' => $current_video['id']]);
                        $quiz = $qStmt->fetch();

                        if ($quiz):
                            $is_passed = isset($passed_quizzes[$current_video['id']]);
                            ?>
                            <div id="quiz-block" class="glass-card <?php echo (!$is_completed) ? 'locked' : ''; ?>"
                                style="margin-bottom: 0; padding: 1.5rem;">
                                <h4 style="font-family: 'Playfair Display', serif; color: #002147; margin: 0 0 1rem 0;">
                                    Assessment</h4>
                                <?php if ($is_passed): ?>
                                    <div style="color: #10b981; font-size: 0.85rem; font-weight: 600;">
                                        <i class="fas fa-check-circle"></i> Passed Successfully
                                    </div>
                                <?php else: ?>
                                    <p style="color: #666; font-size: 0.85rem; margin-bottom: 1.25rem;">Pass the questionnaire to
                                        authorize completion.</p>
                                    <a href="quiz.php?id=<?php echo $quiz['id']; ?>" class="btn-portal-primary"
                                        style="width: 100%; box-sizing: border-box; text-align: center; padding: 0.75rem;">Start
                                        Quiz</a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Resources Section - Hidden until real resources are added -->
                        <!-- Uncomment when you have actual resource files to link
                        <div class="glass-card" style="padding: 1.5rem; margin-bottom: 0;">
                            <h4 style="font-family: 'Playfair Display', serif; color: #002147; margin: 0 0 1rem 0;">
                                Resources</h4>
                            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                                <a href="#"
                                    style="color: #333; text-decoration: none; font-size: 0.85rem; display: flex; align-items: center; gap: 0.5rem;">
                                    <i class="fas fa-file-pdf" style="color: #3db166;"></i> Lecture Slides
                                </a>
                                <a href="#"
                                    style="color: #333; text-decoration: none; font-size: 0.85rem; display: flex; align-items: center; gap: 0.5rem;">
                                    <i class="fas fa-code" style="color: #3db166;"></i> Source Assets
                                </a>
                                <a href="#"
                                    style="color: #333; text-decoration: none; font-size: 0.85rem; display: flex; align-items: center; gap: 0.5rem;">
                                    <i class="fas fa-info-circle" style="color: #3db166;"></i> Documentation
                                </a>
                            </div>
                        </div>
                        -->

                    <?php else: ?>
                        <!-- Stats View for Welcome Screen -->
                        <div class="glass-card" style="padding: 1.5rem;">
                            <h4 style="font-family: 'Playfair Display', serif; color: #002147; margin: 0 0 1rem 0;">Your
                                Stats</h4>
                            <div style="display: grid; gap: 1rem;">
                                <div>
                                    <span
                                        style="display: block; font-size: 0.7rem; color: #999; text-transform: uppercase;">Curriculum
                                        Progress</span>
                                    <div style="display: flex; justify-content: space-between; margin-top: 0.25rem;">
                                        <strong
                                            style="font-size: 1.25rem; color: #002147;"><?php echo (int) $prog['watched_videos']; ?>/<?php echo (int) $prog['total_videos']; ?></strong>
                                        <span
                                            style="color: #3db166; font-weight: 600;"><?php echo round(($prog['watched_videos'] / $prog['total_videos']) * 100); ?>%</span>
                                    </div>
                                </div>
                                <div style="border-top: 1px solid #eee; padding-top: 0.75rem;">
                                    <span
                                        style="display: block; font-size: 0.7rem; color: #999; text-transform: uppercase;">Quizzes
                                        Passed</span>
                                    <strong
                                        style="display: block; font-size: 1.25rem; color: #002147; margin-top: 0.25rem;"><?php echo (int) $prog['quizzes_passed']; ?>/<?php echo (int) $prog['total_quizzes']; ?></strong>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="glass-card" style="padding: 1.5rem;">
                        <h4 style="font-family: 'Playfair Display', serif; color: #002147; margin: 0 0 1rem 0;">Support
                        </h4>
                        <p style="font-size: 0.8rem; color: #666; margin-bottom: 1rem;">Need help with the module or
                            platform?</p>
                        <a href="mailto:support@itinfoacademy.in"
                            style="color: #3db166; text-decoration: none; font-size: 0.85rem; font-weight: 600;">Contact
                            Faculty →</a>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        <?php if ($current_video): ?>
            const video = document.getElementById('protected-player');
            const progressBar = document.getElementById('watch-progress-bar');
            const percentText = document.getElementById('watch-percent');
            const quizBlock = document.getElementById('quiz-block');
            const videoId = <?php echo $current_video['id']; ?>;

            let maxWatchedTime = <?php echo $prog_data ? (int) $prog_data['seconds_watched'] : 0; ?>;
            let isCompleted = <?php echo $is_completed ? 'true' : 'false'; ?>;
            let isSeeking = false;
            let lastValidTime = 0;

            // Set initial position - WAIT for video to be ready
            video.addEventListener('loadedmetadata', function () {
                if (!isCompleted && maxWatchedTime > 0) {
                    video.currentTime = maxWatchedTime;
                    lastValidTime = maxWatchedTime;
                } else {
                    lastValidTime = 0;
                }
            });

            // SEEKER BLOCKER: Prevent forward seeking beyond watched content
            video.addEventListener('seeking', function () {
                isSeeking = true;

                // If video is completed, allow full seeking
                if (isCompleted) {
                    lastValidTime = video.currentTime;
                    return;
                }

                // Block forward seeking beyond maxWatchedTime (allow 2 second buffer for smooth playback)
                if (video.currentTime > maxWatchedTime + 2) {
                    console.log('Forward seeking blocked. Max watched:', maxWatchedTime, 'Attempted:', video.currentTime);
                    video.currentTime = maxWatchedTime;

                    // Show user feedback
                    showSeekBlockedMessage();
                } else {
                    // Allow backward seeking or small forward jumps
                    lastValidTime = video.currentTime;
                }
            });

            video.addEventListener('seeked', function () {
                isSeeking = false;
            });

            // Show message when seeking is blocked
            function showSeekBlockedMessage() {
                const existingMsg = document.getElementById('seek-blocked-msg');
                if (existingMsg) return; // Don't show multiple messages

                const msg = document.createElement('div');
                msg.id = 'seek-blocked-msg';
                msg.style.cssText = `
                    position: fixed;
                    top: 50%;
                    left: 50%;
                    transform: translate(-50%, -50%);
                    background: rgba(220, 53, 69, 0.95);
                    color: white;
                    padding: 1rem 2rem;
                    border-radius: 8px;
                    font-weight: 600;
                    z-index: 10000;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
                    animation: fadeInOut 2s ease-in-out;
                `;
                msg.innerHTML = '<i class="fas fa-lock"></i> Please watch the video sequentially';
                document.body.appendChild(msg);

                setTimeout(() => {
                    msg.remove();
                }, 2000);
            }

            // Add CSS animation for message
            const style = document.createElement('style');
            style.textContent = `
                @keyframes fadeInOut {
                    0% { opacity: 0; transform: translate(-50%, -50%) scale(0.9); }
                    20% { opacity: 1; transform: translate(-50%, -50%) scale(1); }
                    80% { opacity: 1; transform: translate(-50%, -50%) scale(1); }
                    100% { opacity: 0; transform: translate(-50%, -50%) scale(0.9); }
                }
            `;
            document.head.appendChild(style);

            // Update progress UI and track maximum watched position
            video.addEventListener('timeupdate', function () {
                if (isSeeking) return; // Don't update during seeking

                const currentTime = video.currentTime;

                // Update maxWatchedTime if user has progressed further
                if (currentTime > maxWatchedTime) {
                    maxWatchedTime = currentTime;
                }

                // Update UI
                const percent = Math.min(100, Math.round((currentTime / video.duration) * 100)) || 0;
                if (progressBar) progressBar.style.width = percent + '%';
                if (percentText) percentText.innerText = percent + '%';

                lastValidTime = currentTime;
            });

            // Ensure progress is synced when video ends
            video.addEventListener('ended', function () {
                maxWatchedTime = video.duration;
                syncProgress();
            });

            // Heartbeat to sync progress with server
            setInterval(() => {
                if (!video.paused && !video.ended) {
                    syncProgress();
                }
            }, 5000); // Every 5 seconds

            // Also sync when user pauses (to save progress immediately)
            video.addEventListener('pause', function () {
                if (!video.ended) {
                    syncProgress();
                }
            });

            async function syncProgress() {
                try {
                    const response = await fetch('backend/api/update_watch_progress.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            video_id: videoId,
                            seconds: Math.floor(maxWatchedTime),
                            total: Math.floor(video.duration)
                        })
                    });
                    const result = await response.json();
                    if (result.status === 'completed' && !isCompleted) {
                        isCompleted = true;
                        if (quizBlock) quizBlock.classList.remove('locked');

                        // Dynamically update UI without refresh
                        const badgeContainer = document.getElementById('status-badge-container');
                        if (badgeContainer) {
                            badgeContainer.innerHTML = `<span style="background: rgba(16, 185, 129, 0.1); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.2); padding: 0.5rem 1rem; border-radius: 100px; font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;">Validated</span>`;
                        }

                        // Remove progress section
                        const progSection = progressBar ? progressBar.closest('.glass-card') : null;
                        if (progSection) progSection.style.display = 'none';
                    }
                } catch (e) {
                    console.error('Sync failed', e);
                }
            }

            // Sync progress before page unload (when user navigates away)
            window.addEventListener('beforeunload', function () {
                // Use sendBeacon for reliable delivery even when page is closing
                const data = JSON.stringify({
                    video_id: videoId,
                    seconds: Math.floor(maxWatchedTime),
                    total: Math.floor(video.duration)
                });
                navigator.sendBeacon('backend/api/update_watch_progress.php', data);
            });
        <?php endif; ?>

        // Mobile Sidebar Toggle
        const menuToggle = document.getElementById('menuToggle');
        const dashSidebar = document.querySelector('.dash-sidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');

        if (menuToggle) {
            menuToggle.addEventListener('click', function () {
                dashSidebar.classList.add('active');
                sidebarOverlay.classList.add('active');
                document.body.style.overflow = 'hidden';
            });
        }

        if (sidebarOverlay) {
            sidebarOverlay.addEventListener('click', function () {
                dashSidebar.classList.remove('active');
                sidebarOverlay.classList.remove('active');
                document.body.style.overflow = '';
            });
        }

        // Close sidebar on link click (for mobile)
        const videoLinks = document.querySelectorAll('.video-item');
        videoLinks.forEach(link => {
            link.addEventListener('click', function () {
                if (window.innerWidth <= 1024) {
                    dashSidebar.classList.remove('active');
                    sidebarOverlay.classList.remove('active');
                    document.body.style.overflow = '';
                }
            });
        });

        // ============================================
        // PLAYBACK SPEED CONTROL
        // ============================================
        <?php if ($current_video): ?>
                (function () {
                    const speedBtn = document.getElementById('speed-btn');
                    const speedMenu = document.getElementById('speed-menu');
                    const speedOptions = document.querySelectorAll('.speed-option');
                    const videoPlayer = document.getElementById('protected-player');

                    if (!speedBtn || !speedMenu || !videoPlayer) return;

                    // Load saved speed from localStorage
                    const savedSpeed = localStorage.getItem('videoPlaybackSpeed') || '1';
                    videoPlayer.playbackRate = parseFloat(savedSpeed);
                    speedBtn.textContent = savedSpeed + 'x';

                    // Update active state
                    speedOptions.forEach(opt => {
                        opt.style.color = (opt.dataset.speed === savedSpeed) ? '#3db166' : '#fff';
                        opt.style.fontWeight = (opt.dataset.speed === savedSpeed) ? '600' : '400';
                    });

                    // Toggle menu
                    speedBtn.addEventListener('click', function (e) {
                        e.stopPropagation();
                        speedMenu.style.display = speedMenu.style.display === 'none' ? 'block' : 'none';
                    });

                    // Handle speed selection
                    speedOptions.forEach(opt => {
                        opt.addEventListener('click', function () {
                            const speed = parseFloat(this.dataset.speed);
                            videoPlayer.playbackRate = speed;
                            speedBtn.textContent = this.dataset.speed + 'x';
                            localStorage.setItem('videoPlaybackSpeed', this.dataset.speed);

                            // Update active state
                            speedOptions.forEach(o => {
                                o.style.color = '#fff';
                                o.style.fontWeight = '400';
                            });
                            this.style.color = '#3db166';
                            this.style.fontWeight = '600';

                            speedMenu.style.display = 'none';
                        });

                        // Hover effect
                        opt.addEventListener('mouseenter', function () {
                            if (this.style.color !== 'rgb(61, 177, 102)') {
                                this.style.background = 'rgba(255,255,255,0.1)';
                            }
                        });
                        opt.addEventListener('mouseleave', function () {
                            this.style.background = 'transparent';
                        });
                    });

                    // Close menu when clicking outside
                    document.addEventListener('click', function () {
                        speedMenu.style.display = 'none';
                    });
                })();
        <?php endif; ?>

        // ============================================
        // KEYBOARD SHORTCUTS
        // ============================================
        <?php if ($current_video): ?>
                (function () {
                    const videoPlayer = document.getElementById('protected-player');
                    const speedBtn = document.getElementById('speed-btn');
                    const speedMenu = document.getElementById('speed-menu');
                    const speeds = [0.5, 0.75, 1, 1.25, 1.5, 2];

                    if (!videoPlayer) return;

                    document.addEventListener('keydown', function (e) {
                        // Don't trigger if user is typing in an input
                        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;

                        switch (e.key.toLowerCase()) {
                            case ' ':
                            case 'k':
                                e.preventDefault();
                                if (videoPlayer.paused) {
                                    videoPlayer.play();
                                } else {
                                    videoPlayer.pause();
                                }
                                break;
                            case 'arrowleft':
                            case 'j':
                                e.preventDefault();
                                videoPlayer.currentTime = Math.max(0, videoPlayer.currentTime - 10);
                                break;
                            case 'arrowright':
                            case 'l':
                                e.preventDefault();
                                // Only allow forward seek if completed or within watched time
                                if (isCompleted || videoPlayer.currentTime + 10 <= maxWatchedTime + 2) {
                                    videoPlayer.currentTime = Math.min(videoPlayer.duration, videoPlayer.currentTime + 10);
                                }
                                break;
                            case 'arrowup':
                                e.preventDefault();
                                videoPlayer.volume = Math.min(1, videoPlayer.volume + 0.1);
                                break;
                            case 'arrowdown':
                                e.preventDefault();
                                videoPlayer.volume = Math.max(0, videoPlayer.volume - 0.1);
                                break;
                            case 'm':
                                e.preventDefault();
                                videoPlayer.muted = !videoPlayer.muted;
                                break;
                            case 'f':
                                e.preventDefault();
                                if (document.fullscreenElement) {
                                    document.exitFullscreen();
                                } else {
                                    videoPlayer.requestFullscreen();
                                }
                                break;
                            case 's':
                                e.preventDefault();
                                // Cycle through speeds
                                const currentSpeed = videoPlayer.playbackRate;
                                const currentIndex = speeds.indexOf(currentSpeed);
                                const nextIndex = (currentIndex + 1) % speeds.length;
                                const newSpeed = speeds[nextIndex];
                                videoPlayer.playbackRate = newSpeed;
                                if (speedBtn) speedBtn.textContent = newSpeed + 'x';
                                localStorage.setItem('videoPlaybackSpeed', newSpeed.toString());
                                break;
                        }
                    });
                })();
        <?php endif; ?>

        // ============================================
        // POLICY MODAL LOGIC
        // ============================================
        function closePolicyModal() {
            const modal = document.getElementById('policyModal');
            modal.style.display = 'none';
            localStorage.setItem('policyAcknowledged', 'true');
        }

        // Show policy modal on first login
        window.addEventListener('load', function () {
            const hasAcknowledged = localStorage.getItem('policyAcknowledged');
            if (!hasAcknowledged) {
                const modal = document.getElementById('policyModal');
                modal.style.display = 'flex';
            }
        });
    </script>
</body>

</html>