<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$auth = new Auth();
$auth->requireLogin();

$database = new Database();
$db = $database->connect();

if (!isset($_GET['id'])) {
    header('Location: intern-enrollments.php');
    exit();
}

$enrollment_id = (int) $_GET['id'];

// Get enrollment, intern and internship details
$stmt = $db->prepare("SELECT e.*, i.full_name, i.email, p.title as internship_title, p.id as internship_id
                      FROM internship_enrollments e
                      JOIN interns i ON e.intern_id = i.id
                      JOIN internships p ON e.internship_id = p.id
                      WHERE e.id = :id");
$stmt->execute(['id' => $enrollment_id]);
$data = $stmt->fetch();

if (!$data) {
    header('Location: intern-enrollments.php');
    exit();
}

$pageTitle = 'Progress Track - ' . $data['full_name'];

// Get all videos for this internship
$stmt = $db->prepare("SELECT * FROM internship_videos WHERE internship_id = :id ORDER BY display_order ASC");
$stmt->execute(['id' => $data['internship_id']]);
$videos = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="content-section">
    <div style="margin-bottom: 2rem;">
        <a href="intern-enrollments.php" style="color: var(--primary); text-decoration: none; font-size: 0.9rem;">&larr;
            Back to Enrollments</a>
        <h2 style="margin-top: 0.5rem;">Intern Progress Track</h2>
        <div
            style="background: #f8fafc; padding: 1rem; border-radius: 8px; margin-top: 1rem; border: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
            <div>
                <strong>
                    <?php echo htmlspecialchars($data['full_name']); ?>
                </strong> (
                <?php echo htmlspecialchars($data['email']); ?>)<br>
                <span>Internship:
                    <?php echo htmlspecialchars($data['internship_title']); ?>
                </span>
            </div>
            <div style="text-align: right;">
                <span class="badge badge-converted">
                    <?php echo ucfirst($data['enrollment_status']); ?>
                </span>
            </div>
        </div>
    </div>

    <h3>Learning Checklist</h3>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Video Order</th>
                    <th>Video Title</th>
                    <th>Watch Status</th>
                    <th>Quiz Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($videos) > 0): ?>
                    <?php foreach ($videos as $video):
                        // Check watch status
                        $wStmt = $db->prepare("SELECT is_completed FROM intern_video_progress WHERE intern_id = :iid AND video_id = :vid");
                        $wStmt->execute(['iid' => $data['intern_id'], 'vid' => $video['id']]);
                        $watched = $wStmt->fetch();

                        // Check quiz status
                        $quizStmt = $db->prepare("SELECT q.id, qa.score, qa.is_passed FROM internship_quizzes q 
                                                  LEFT JOIN intern_quiz_attempts qa ON q.id = qa.quiz_id AND qa.intern_id = :iid
                                                  WHERE q.video_id = :vid ORDER BY qa.attempted_at DESC LIMIT 1");
                        $quizStmt->execute(['iid' => $data['intern_id'], 'vid' => $video['id']]);
                        $quizResult = $quizStmt->fetch();
                        ?>
                        <tr>
                            <td>
                                <?php echo $video['display_order']; ?>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($video['title']); ?>
                            </td>
                            <td>
                                <?php
                                $wStmt = $db->prepare("SELECT is_completed, seconds_watched, total_seconds FROM intern_video_progress WHERE intern_id = :iid AND video_id = :vid");
                                $wStmt->execute(['iid' => $data['intern_id'], 'vid' => $video['id']]);
                                $watched = $wStmt->fetch();

                                if ($watched):
                                    $percent = ($watched['total_seconds'] > 0) ? round(($watched['seconds_watched'] / $watched['total_seconds']) * 100) : 0;
                                    if ($watched['is_completed']): ?>
                                        <span style="color: #10b981; font-weight: 600;">✅ Completed (100%)</span>
                                    <?php else: ?>
                                        <span style="color: #f59e0b; font-weight: 600;">⏳ <?php echo $percent; ?>% Watched</span>
                                        <br><small style="color: #64748b;"><?php echo floor($watched['seconds_watched'] / 60); ?> /
                                            <?php echo floor($watched['total_seconds'] / 60); ?> mins</small>
                                    <?php endif;
                                else: ?>
                                    <span style="color: #6b7280;">⏳ Pending</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!$quizResult): ?>
                                    <span style="color: #6b7280;">- No Quiz -</span>
                                <?php elseif ($quizResult['is_passed'] === null): ?>
                                    <span style="color: #6b7280;">⌛ Waiting</span>
                                <?php elseif ($quizResult['is_passed']): ?>
                                    <span style="color: #10b981; font-weight: 600;">✅ Passed (
                                        <?php echo $quizResult['score']; ?>%)
                                    </span>
                                <?php else: ?>
                                    <span style="color: #ef4444; font-weight: 600;">❌ Failed (
                                        <?php echo $quizResult['score']; ?>%)
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" style="text-align: center;">No videos found for this internship.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php
    // Aggregate data for certificate check
    $stmt = $db->prepare("SELECT * FROM view_internship_progress WHERE intern_id = :iid AND internship_id = :pid");
    $stmt->execute(['iid' => $data['intern_id'], 'pid' => $data['internship_id']]);
    $progress = $stmt->fetch();

    if ($progress && $progress['watched_videos'] == $progress['total_videos'] && $progress['quizzes_passed'] == $progress['total_videos']):
        ?>
        <div
            style="margin-top: 2rem; background: #ecfdf5; padding: 2rem; border-radius: 12px; border: 1px solid #10b981; text-align: center;">
            <h2 style="color: #065f46;">🎉 All Requirements Completed!</h2>
            <p style="color: #047857; margin-bottom: 1.5rem;">This intern has watched all videos and passed all quizzes.</p>
            <a href="generate-certificate.php?id=<?php echo $enrollment_id; ?>" class="btn btn-success"
                style="padding: 0.75rem 2rem;">Preview & Issue Certificate</a>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>