<?php
require_once 'backend/config/config.php';
require_once 'backend/config/database.php';
require_once 'backend/includes/functions.php';

if (!isset($_SESSION['intern_id'])) {
    header('Location: intern-login.php');
    exit();
}

// Check if we have quiz results
if (!isset($_SESSION['quiz_result'])) {
    header('Location: intern-dashboard.php');
    exit();
}

$result = $_SESSION['quiz_result'];
// Don't clear the session immediately - keep it for one page view
// We'll clear it on redirect to dashboard

$database = new Database();
$db = $database->connect();

// Get quiz and video details
$stmt = $db->prepare("SELECT q.*, v.title as video_title, v.id as video_id 
                      FROM internship_quizzes q 
                      JOIN internship_videos v ON q.video_id = v.id 
                      WHERE q.id = :id");
$stmt->execute(['id' => $result['quiz_id']]);
$quiz = $stmt->fetch();

// Get questions with all options for review
$stmt = $db->prepare("SELECT * FROM internship_questions WHERE quiz_id = :qid ORDER BY id ASC");
$stmt->execute(['qid' => $result['quiz_id']]);
$questions = $stmt->fetchAll();

foreach ($questions as &$q) {
    $stmt = $db->prepare("SELECT * FROM internship_question_options WHERE question_id = :qid");
    $stmt->execute(['qid' => $q['id']]);
    $q['options'] = $stmt->fetchAll();
}
unset($q);

// Clear the session result after fetching
unset($_SESSION['quiz_result']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Results -
        <?php echo htmlspecialchars($quiz['video_title']); ?>
    </title>
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
            padding: 3rem 2rem;
        }

        .results-container {
            max-width: 800px;
            margin: 0 auto;
        }

        /* Score Card */
        .score-card {
            background: #ffffff;
            border-radius: 2px;
            padding: 3rem;
            text-align: center;
            margin-bottom: 2rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .score-card.passed {
            border-top: 5px solid #10b981;
        }

        .score-card.failed {
            border-top: 5px solid #ef4444;
        }

        .score-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 2.5rem;
        }

        .score-icon.passed {
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
        }

        .score-icon.failed {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }

        .score-title {
            font-family: 'Playfair Display', serif;
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .score-title.passed {
            color: #10b981;
        }

        .score-title.failed {
            color: #ef4444;
        }

        .score-subtitle {
            color: #666666;
            font-size: 0.95rem;
            margin-bottom: 2rem;
        }

        .score-display {
            font-size: 4rem;
            font-weight: 700;
            color: #002147;
            line-height: 1;
            margin-bottom: 0.5rem;
        }

        .score-label {
            font-size: 0.85rem;
            color: #999999;
            text-transform: uppercase;
            letter-spacing: 0.1em;
        }

        .score-stats {
            display: flex;
            justify-content: center;
            gap: 3rem;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #eeeeee;
        }

        .stat-item {
            text-align: center;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #002147;
        }

        .stat-label {
            font-size: 0.75rem;
            color: #999999;
            text-transform: uppercase;
        }

        /* Review Section */
        .review-section {
            background: #ffffff;
            border-radius: 2px;
            padding: 2.5rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
        }

        .review-header {
            margin-bottom: 2rem;
        }

        .review-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            font-weight: 600;
            color: #002147;
            margin-bottom: 0.5rem;
        }

        .review-subtitle {
            font-size: 0.85rem;
            color: #666666;
        }

        /* Question Review */
        .question-review {
            padding: 1.5rem 0;
            border-bottom: 1px solid #eeeeee;
        }

        .question-review:last-child {
            border-bottom: none;
        }

        .question-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }

        .question-status {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
        }

        .question-status.correct {
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
        }

        .question-status.incorrect {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }

        .question-number {
            font-size: 0.75rem;
            font-weight: 600;
            color: #999999;
            text-transform: uppercase;
        }

        .question-text {
            font-size: 1rem;
            color: #002147;
            font-weight: 500;
            margin-bottom: 1rem;
            line-height: 1.5;
        }

        .option-review {
            padding: 0.75rem 1rem;
            border-radius: 4px;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .option-review.user-selected {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .option-review.correct-answer {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .option-review.user-correct {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .option-review.neutral {
            background: #f9f9f9;
            border: 1px solid #eeeeee;
        }

        .option-indicator {
            font-size: 0.75rem;
            font-weight: 600;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 1rem 2rem;
            border-radius: 2px;
            font-weight: 600;
            text-decoration: none;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            transition: 0.3s;
        }

        .btn-primary-action {
            background: #002147;
            color: #ffffff;
        }

        .btn-primary-action:hover {
            background: #001835;
            transform: translateY(-2px);
        }

        .btn-secondary-action {
            background: transparent;
            color: #002147;
            border: 1px solid #e5e5e5;
        }

        .btn-secondary-action:hover {
            border-color: #3db166;
            color: #3db166;
        }

        @media (max-width: 600px) {
            .score-stats {
                flex-direction: column;
                gap: 1rem;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn-action {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>

<body>
    <div class="results-container">
        <!-- Score Card -->
        <div class="score-card <?php echo $result['is_passed'] ? 'passed' : 'failed'; ?>">
            <div class="score-icon <?php echo $result['is_passed'] ? 'passed' : 'failed'; ?>">
                <?php if ($result['is_passed']): ?>
                    <i class="fas fa-check"></i>
                <?php else: ?>
                    <i class="fas fa-times"></i>
                <?php endif; ?>
            </div>

            <h1 class="score-title <?php echo $result['is_passed'] ? 'passed' : 'failed'; ?>">
                <?php echo $result['is_passed'] ? 'Assessment Passed!' : 'Assessment Not Passed'; ?>
            </h1>

            <p class="score-subtitle">
                <?php echo htmlspecialchars($quiz['video_title']); ?>
            </p>

            <div class="score-display">
                <?php echo $result['score']; ?>%
            </div>
            <div class="score-label">Your Score</div>

            <div class="score-stats">
                <div class="stat-item">
                    <div class="stat-value">
                        <?php echo $result['correct_count']; ?>/
                        <?php echo $result['total_questions']; ?>
                    </div>
                    <div class="stat-label">Correct Answers</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">
                        <?php echo $result['passing_score']; ?>%
                    </div>
                    <div class="stat-label">Passing Score</div>
                </div>
            </div>
        </div>

        <!-- Answer Review Section -->
        <div class="review-section">
            <div class="review-header">
                <h2 class="review-title">Answer Review</h2>
                <p class="review-subtitle">Review your answers below. Correct answers are highlighted in green.</p>
            </div>

            <?php foreach ($questions as $index => $q):
                $user_answer = $result['user_answers'][$q['id']] ?? null;
                $correct_answer = $result['correct_answers'][$q['id']] ?? null;
                $is_correct = $user_answer == $correct_answer;
                ?>
                <div class="question-review">
                    <div class="question-header">
                        <div class="question-status <?php echo $is_correct ? 'correct' : 'incorrect'; ?>">
                            <i class="fas fa-<?php echo $is_correct ? 'check' : 'times'; ?>"></i>
                        </div>
                        <span class="question-number">Question
                            <?php echo $index + 1; ?>
                        </span>
                    </div>

                    <div class="question-text">
                        <?php echo htmlspecialchars($q['question_text']); ?>
                    </div>

                    <div class="options-list">
                        <?php foreach ($q['options'] as $opt):
                            $is_user_selected = ($opt['id'] == $user_answer);
                            $is_correct_answer = $opt['is_correct'];

                            // Determine class
                            if ($is_user_selected && $is_correct_answer) {
                                $class = 'user-correct';
                            } elseif ($is_user_selected && !$is_correct_answer) {
                                $class = 'user-selected';
                            } elseif ($is_correct_answer) {
                                $class = 'correct-answer';
                            } else {
                                $class = 'neutral';
                            }
                            ?>
                            <div class="option-review <?php echo $class; ?>">
                                <?php if ($is_user_selected && $is_correct_answer): ?>
                                    <span class="option-indicator" style="color: #10b981;"><i class="fas fa-check-circle"></i> Your
                                        Answer (Correct)</span>
                                <?php elseif ($is_user_selected): ?>
                                    <span class="option-indicator" style="color: #ef4444;"><i class="fas fa-times-circle"></i> Your
                                        Answer</span>
                                <?php elseif ($is_correct_answer): ?>
                                    <span class="option-indicator" style="color: #10b981;"><i class="fas fa-check"></i> Correct
                                        Answer</span>
                                <?php endif; ?>
                                <span>
                                    <?php echo htmlspecialchars($opt['option_text']); ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Action Buttons -->
        <div class="action-buttons">
            <a href="intern-dashboard.php?video=<?php echo $result['video_id']; ?>"
                class="btn-action btn-primary-action">
                <i class="fas fa-arrow-left"></i> Continue Learning
            </a>
            <?php if (!$result['is_passed']): ?>
                <a href="quiz.php?id=<?php echo $result['quiz_id']; ?>" class="btn-action btn-secondary-action">
                    <i class="fas fa-redo"></i> Retry Quiz
                </a>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>