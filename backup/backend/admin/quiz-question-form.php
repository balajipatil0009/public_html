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

// Get Quiz ID
$stmt = $db->prepare("SELECT id FROM internship_quizzes WHERE video_id = :vid");
$stmt->execute(['vid' => $video_id]);
$quiz = $stmt->fetch();
if (!$quiz) {
    header("Location: internship-quizzes.php?video_id=$video_id");
    exit();
}
$quiz_id = $quiz['id'];

$question = null;
$options = [];
$isEdit = false;

if (isset($_GET['id'])) {
    $q_id = (int) $_GET['id'];
    $stmt = $db->prepare("SELECT * FROM internship_questions WHERE id = :id AND quiz_id = :qid");
    $stmt->execute(['id' => $q_id, 'qid' => $quiz_id]);
    $question = $stmt->fetch();

    if ($question) {
        $isEdit = true;
        $stmt = $db->prepare("SELECT * FROM internship_question_options WHERE question_id = :qid");
        $stmt->execute(['qid' => $q_id]);
        $options = $stmt->fetchAll();
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $text = sanitize($_POST['question_text']);
    $type = sanitize($_POST['question_type']);

    $db->beginTransaction();

    try {
        if ($isEdit) {
            $stmt = $db->prepare("UPDATE internship_questions SET question_text = :text, question_type = :type WHERE id = :id");
            $stmt->execute(['text' => $text, 'type' => $type, 'id' => $_GET['id']]);
            $current_q_id = $_GET['id'];
        } else {
            $stmt = $db->prepare("INSERT INTO internship_questions (quiz_id, question_text, question_type) VALUES (:qid, :text, :type)");
            $stmt->execute(['qid' => $quiz_id, 'text' => $text, 'type' => $type]);
            $current_q_id = $db->lastInsertId();
        }

        // Handle Options for MCQ
        if ($type === 'mcq') {
            // Delete existing options if editing
            if ($isEdit) {
                $db->prepare("DELETE FROM internship_question_options WHERE question_id = :qid")->execute(['qid' => $current_q_id]);
            }

            $opt_texts = $_POST['options'];
            $correct_idx = (int) $_POST['correct_option'];

            foreach ($opt_texts as $index => $opt_text) {
                if (trim($opt_text) === '')
                    continue;
                $is_correct = ($index === $correct_idx) ? 1 : 0;
                $stmt = $db->prepare("INSERT INTO internship_question_options (question_id, option_text, is_correct) VALUES (:qid, :text, :correct)");
                $stmt->execute(['qid' => $current_q_id, 'text' => sanitize($opt_text), 'correct' => $is_correct]);
            }
        }

        $db->commit();
        setSuccessMessage('Question saved successfully');
        header("Location: internship-quizzes.php?video_id=$video_id");
        exit();
    } catch (Exception $e) {
        $db->rollBack();
        setErrorMessage('Error: ' . $e->getMessage());
    }
}

$pageTitle = ($isEdit ? 'Edit' : 'Add') . ' Question';
include 'includes/header.php';
?>

<div class="content-section">
    <h2>
        <?php echo $isEdit ? 'Edit' : 'Add'; ?> Question
    </h2>

    <form method="POST" action="">
        <div class="form-group">
            <label>Question Text *</label>
            <textarea name="question_text" class="form-control" rows="3"
                required><?php echo $question['question_text'] ?? ''; ?></textarea>
        </div>

        <div class="form-group">
            <label>Question Type</label>
            <select name="question_type" id="question_type" class="form-control" onchange="toggleOptions()">
                <option value="mcq" <?php echo ($question['question_type'] ?? '') === 'mcq' ? 'selected' : ''; ?>
                    >Multiple Choice (MCQ)</option>
                <option value="tf" <?php echo ($question['question_type'] ?? '') === 'tf' ? 'selected' : ''; ?>>True /
                    False</option>
            </select>
        </div>

        <div id="options_container"
            style="background: #f9fafb; padding: 1.5rem; border-radius: 8px; border: 1px solid #e5e7eb; margin-bottom: 1.5rem;">
            <h3>Options</h3>
            <p style="font-size: 0.85rem; color: #6b7280; margin-bottom: 1rem;">Enter the choices and select the radio
                button for the correct answer.</p>

            <div id="options_list">
                <?php if ($isEdit && count($options) > 0): ?>
                    <?php foreach ($options as $index => $opt): ?>
                        <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 0.75rem;">
                            <input type="radio" name="correct_option" value="<?php echo $index; ?>" <?php echo $opt['is_correct'] ? 'checked' : ''; ?>>
                            <input type="text" name="options[]" class="form-control"
                                value="<?php echo htmlspecialchars($opt['option_text']); ?>" placeholder="Option text">
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- Default for new MCQ -->
                    <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 0.75rem;">
                        <input type="radio" name="correct_option" value="0" checked>
                        <input type="text" name="options[]" class="form-control" placeholder="Option 1">
                    </div>
                    <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 0.75rem;">
                        <input type="radio" name="correct_option" value="1">
                        <input type="text" name="options[]" class="form-control" placeholder="Option 2">
                    </div>
                <?php endif; ?>
            </div>

            <button type="button" onclick="addOption()" class="btn btn-sm btn-outline" id="addOptionBtn">+ Add More
                Options</button>
        </div>

        <div class="action-buttons">
            <button type="submit" class="btn btn-primary">Save Question</button>
            <a href="internship-quizzes.php?video_id=<?php echo $video_id; ?>" class="btn btn-danger">Cancel</a>
        </div>
    </form>
</div>

<script>
    function toggleOptions() {
        const type = document.getElementById('question_type').value;
        const container = document.getElementById('options_container');
        const optionsList = document.getElementById('options_list');
        const addBtn = document.getElementById('addOptionBtn');

        if (type === 'tf') {
            optionsList.innerHTML = `
            <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 0.75rem;">
                <input type="radio" name="correct_option" value="0" checked>
                <input type="text" name="options[]" class="form-control" value="True" readonly>
            </div>
            <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 0.75rem;">
                <input type="radio" name="correct_option" value="1">
                <input type="text" name="options[]" class="form-control" value="False" readonly>
            </div>
        `;
            addBtn.style.display = 'none';
        } else {
            // Reset to MCQ style if it was TF or empty
            if (type === 'mcq' && optionsList.querySelectorAll('input[type="text"][readonly]').length > 0) {
                optionsList.innerHTML = `
                <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 0.75rem;">
                    <input type="radio" name="correct_option" value="0" checked>
                    <input type="text" name="options[]" class="form-control" placeholder="Option 1">
                </div>
                <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 0.75rem;">
                    <input type="radio" name="correct_option" value="1">
                    <input type="text" name="options[]" class="form-control" placeholder="Option 2">
                </div>
            `;
            }
            addBtn.style.display = 'block';
        }
    }

    function addOption() {
        const optionsList = document.getElementById('options_list');
        const count = optionsList.children.length;
        const div = document.createElement('div');
        div.style = "display: flex; align-items: center; gap: 1rem; margin-bottom: 0.75rem;";
        div.innerHTML = `
        <input type="radio" name="correct_option" value="${count}">
        <input type="text" name="options[]" class="form-control" placeholder="Option ${count + 1}">
    `;
        optionsList.appendChild(div);
    }

    // Run once on load to set initial state
    window.onload = toggleOptions;
</script>

<?php include 'includes/footer.php'; ?>