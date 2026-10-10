<?php
require_once 'backend/config/config.php';
require_once 'backend/config/database.php';
require_once 'backend/includes/functions.php';

if (!isset($_SESSION['intern_id'])) {
    header('Location: intern-login.php');
    exit();
}

if (!isset($_GET['id'])) {
    header('Location: intern-dashboard.php');
    exit();
}

$quiz_id = (int) $_GET['id'];
$database = new Database();
$db = $database->connect();

// Get quiz and video details
$stmt = $db->prepare("SELECT q.*, v.title as video_title, v.id as video_id 
                      FROM internship_quizzes q 
                      JOIN internship_videos v ON q.video_id = v.id 
                      WHERE q.id = :id");
$stmt->execute(['id' => $quiz_id]);
$quiz = $stmt->fetch();

if (!$quiz) {
    header('Location: intern-dashboard.php');
    exit();
}

// SECURITY CHECK: Must have completed video
$prog_stmt = $db->prepare("SELECT is_completed FROM intern_video_progress WHERE intern_id = :iid AND video_id = :vid");
$prog_stmt->execute(['iid' => $_SESSION['intern_id'], 'vid' => $quiz['video_id']]);
$prog = $prog_stmt->fetch();

if (!$prog || !$prog['is_completed']) {
    header('Location: intern-dashboard.php?video=' . $quiz['video_id']);
    exit();
}

// Get questions and options
$stmt = $db->prepare("SELECT * FROM internship_questions WHERE quiz_id = :qid ORDER BY id ASC");
$stmt->execute(['qid' => $quiz_id]);
$questions = $stmt->fetchAll();

foreach ($questions as &$q) {
    $stmt = $db->prepare("SELECT * FROM internship_question_options WHERE question_id = :qid");
    $stmt->execute(['qid' => $q['id']]);
    $q['options'] = $stmt->fetchAll();
}
unset($q);

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assessment: <?php echo htmlspecialchars($quiz['video_title']); ?></title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap"
        rel="stylesheet">
    <style>
        body {
            background: #f5f5f5;
            color: #333333;
            font-family: 'Poppins', sans-serif;
            padding: 4rem 2rem;
        }

        .quiz-container {
            max-width: 800px;
            margin: 0 auto;
            background: #ffffff;
            padding: 4rem;
            border-radius: 2px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .quiz-header {
            text-align: center;
            margin-bottom: 4rem;
        }

        .quiz-subtitle {
            display: block;
            font-size: 0.75rem;
            font-weight: 600;
            color: #3db166;
            text-transform: uppercase;
            letter-spacing: 0.15em;
            margin-bottom: 1rem;
        }

        .quiz-title {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            font-weight: 600;
            color: #002147;
            margin: 0;
            line-height: 1.2;
        }

        .passing-score {
            color: #666666;
            margin-top: 1rem;
            font-size: 1rem;
        }

        .question-block {
            margin-bottom: 3.5rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid #eeeeee;
        }

        .question-number {
            font-size: 0.75rem;
            font-weight: 600;
            color: #999999;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            margin-bottom: 1rem;
            display: block;
        }

        .question-text {
            font-size: 1.25rem;
            margin-bottom: 2rem;
            line-height: 1.5;
            color: #002147;
            font-weight: 500;
        }

        .option-label {
            display: flex;
            align-items: center;
            padding: 1.25rem 1.5rem;
            background: #f9f9f9;
            border: 1px solid #eeeeee;
            border-radius: 2px;
            margin-bottom: 1rem;
            cursor: pointer;
            transition: 0.3s;
        }

        .option-label:hover {
            border-color: #3db166;
            background: #fdfdfd;
        }

        .option-label input {
            margin-right: 1.25rem;
            width: 1.1rem;
            height: 1.1rem;
            accent-color: #3db166;
        }

        .option-text {
            color: #333333;
            font-weight: 400;
        }

        .btn-submit {
            width: 100%;
            max-width: 400px;
            padding: 1.25rem;
            background: #3db166;
            color: white;
            border: none;
            border-radius: 2px;
            font-size: 0.875rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            cursor: pointer;
            transition: 0.3s;
        }

        .btn-submit:hover {
            background: #2d9050;
        }

        .submission-note {
            margin-top: 1.5rem;
            font-size: 0.8rem;
            color: #999999;
        }

        .back-link {
            display: inline-block;
            margin-top: 3rem;
            color: #999999;
            text-decoration: none;
            font-size: 0.875rem;
            transition: 0.3s;
        }

        .back-link:hover {
            color: #002147;
        }
    </style>
</head>

<body>
    <div class="quiz-container">
        <div class="quiz-header">
            <span class="quiz-subtitle">Academic Assessment</span>
            <h1 class="quiz-title"><?php echo htmlspecialchars($quiz['video_title']); ?></h1>
            <p class="passing-score">
                <i class="fas fa-info-circle" style="margin-right: 5px;"></i> Passing Requirement:
                <?php echo $quiz['passing_score']; ?>% accuracy
            </p>
        </div>

        <form id="quizForm" method="POST" action="backend/api/submit_quiz.php">
            <input type="hidden" name="quiz_id" value="<?php echo $quiz_id; ?>">
            <input type="hidden" name="video_id" value="<?php echo $quiz['video_id']; ?>">

            <?php foreach ($questions as $index => $q): ?>
                <div class="question-block">
                    <span class="question-number">Question <?php echo sprintf("%02d", $index + 1); ?></span>
                    <h3 class="question-text">
                        <?php echo htmlspecialchars($q['question_text']); ?>
                    </h3>

                    <div class="options-group">
                        <?php foreach ($q['options'] as $opt): ?>
                            <label class="option-label">
                                <input type="radio" name="answers[<?php echo $q['id']; ?>]" value="<?php echo $opt['id']; ?>"
                                    required>
                                <span class="option-text"><?php echo htmlspecialchars($opt['option_text']); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <div style="text-align: center; margin-top: 4rem;">
                <button type="submit" class="btn-submit">
                    Submit Assessment
                </button>
                <p class="submission-note">Your responses will be validated against the curriculum standards.</p>
            </div>
        </form>
    </div>

    <div style="text-align: center;">
        <a href="intern-dashboard.php?video=<?php echo $quiz['video_id']; ?>" class="back-link">
            <i class="fas fa-chevron-left" style="margin-right: 5px;"></i> Abort and Return to Module
        </a>
    </div>
</body>

</html>

</html>