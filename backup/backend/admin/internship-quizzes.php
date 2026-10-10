<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$auth = new Auth();
$auth->requireLogin();

$database = new Database();
$db = $database->connect();

if (!isset($_GET['video_id'])) {
    header('Location: internships.php');
    exit();
}

$video_id = (int) $_GET['video_id'];

// Get video details
$stmt = $db->prepare("SELECT v.*, i.title as internship_title FROM internship_videos v 
                      JOIN internships i ON v.internship_id = i.id 
                      WHERE v.id = :id");
$stmt->execute(['id' => $video_id]);
$video = $stmt->fetch();

if (!$video) {
    header('Location: internships.php');
    exit();
}

$pageTitle = 'Manage Quiz - ' . $video['title'];

// Get or Create Quiz
$stmt = $db->prepare("SELECT * FROM internship_quizzes WHERE video_id = :vid");
$stmt->execute(['vid' => $video_id]);
$quiz = $stmt->fetch();

if (!$quiz) {
    $stmt = $db->prepare("INSERT INTO internship_quizzes (video_id) VALUES (:vid)");
    $stmt->execute(['vid' => $video_id]);
    $quiz_id = $db->lastInsertId();
    $quiz = ['id' => $quiz_id, 'passing_score' => 70];
} else {
    $quiz_id = $quiz['id'];
}

// Handle passing score update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
    $score = (int) $_POST['passing_score'];
    $stmt = $db->prepare("UPDATE internship_quizzes SET passing_score = :score WHERE id = :id");
    if ($stmt->execute(['score' => $score, 'id' => $quiz_id])) {
        setSuccessMessage('Quiz settings updated');
        header("Location: internship-quizzes.php?video_id=$video_id");
        exit();
    }
}

// Handle question deletion
if (isset($_GET['delete_question'])) {
    $q_id = (int) $_GET['delete_question'];
    $stmt = $db->prepare("DELETE FROM internship_questions WHERE id = :id AND quiz_id = :qid");
    if ($stmt->execute(['id' => $q_id, 'qid' => $quiz_id])) {
        setSuccessMessage('Question deleted');
    }
    header("Location: internship-quizzes.php?video_id=$video_id");
    exit();
}

// Get questions
$stmt = $db->prepare("SELECT * FROM internship_questions WHERE quiz_id = :qid ORDER BY id ASC");
$stmt->execute(['qid' => $quiz_id]);
$questions = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="content-section">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <a href="internship-videos.php?internship_id=<?php echo $video['internship_id']; ?>"
                style="color: var(--primary); text-decoration: none; font-size: 0.9rem;">&larr; Back to Videos</a>
            <h2 style="margin-top: 0.5rem;">Quiz:
                <?php echo htmlspecialchars($video['title']); ?>
            </h2>
            <p style="color: #6b7280;">Internship:
                <?php echo htmlspecialchars($video['internship_title']); ?>
            </p>
        </div>
        <a href="quiz-question-form.php?video_id=<?php echo $video_id; ?>" class="btn btn-primary">+ Add Question</a>
    </div>

    <div
        style="background: white; padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem; border: 1px solid var(--border);">
        <h3>Quiz Settings</h3>
        <form method="POST" action="" style="display: flex; align-items: flex-end; gap: 1rem; margin-top: 1rem;">
            <div class="form-group" style="margin-bottom: 0;">
                <label>Passing Score (%)</label>
                <input type="number" name="passing_score" class="form-control"
                    value="<?php echo $quiz['passing_score']; ?>" min="0" max="100" style="width: 150px;">
            </div>
            <button type="submit" name="update_settings" class="btn btn-success">Save Settings</button>
        </form>
    </div>

    <h3>Questions (
        <?php echo count($questions); ?>)
    </h3>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Question Text</th>
                    <th>Type</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($questions) > 0): ?>
                    <?php foreach ($questions as $index => $q): ?>
                        <tr>
                            <td>
                                <?php echo $index + 1; ?>
                            </td>
                            <td>
                                <strong>
                                    <?php echo htmlspecialchars($q['question_text']); ?>
                                </strong>
                            </td>
                            <td>
                                <?php echo strtoupper($q['question_type']); ?>
                            </td>
                            <td class="action-buttons">
                                <a href="quiz-question-form.php?video_id=<?php echo $video_id; ?>&id=<?php echo $q['id']; ?>"
                                    class="btn btn-sm btn-primary">Edit</a>
                                <a href="?video_id=<?php echo $video_id; ?>&delete_question=<?php echo $q['id']; ?>"
                                    class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" style="text-align: center;">No questions added yet.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>