<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

session_start();
if (!isset($_SESSION['intern_id'])) {
    http_response_code(403);
    die("Unauthorized");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $intern_id = $_SESSION['intern_id'];
    $quiz_id = (int) $_POST['quiz_id'];
    $video_id = (int) $_POST['video_id'];
    $user_answers = $_POST['answers'] ?? [];

    $database = new Database();
    $db = $database->connect();

    // Get quiz details
    $stmt = $db->prepare("SELECT passing_score FROM internship_quizzes WHERE id = :id");
    $stmt->execute(['id' => $quiz_id]);
    $quiz = $stmt->fetch();

    // SECURITY CHECK: Must have completed video
    $prog_stmt = $db->prepare("SELECT is_completed FROM intern_video_progress WHERE intern_id = :iid AND video_id = :vid");
    $prog_stmt->execute(['iid' => $intern_id, 'vid' => $video_id]);
    $prog = $prog_stmt->fetch();

    if (!$prog || !$prog['is_completed']) {
        http_response_code(403);
        die("Security verification failed. Module not completed.");
    }

    // Get questions and correct answers
    $stmt = $db->prepare("SELECT q.id, qo.id as correct_option_id 
                          FROM internship_questions q 
                          JOIN internship_question_options qo ON q.id = qo.question_id 
                          WHERE q.quiz_id = :qid AND qo.is_correct = 1");
    $stmt->execute(['qid' => $quiz_id]);
    $correct_answers = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    $total_questions = count($correct_answers);
    $correct_count = 0;

    foreach ($correct_answers as $q_id => $correct_opt_id) {
        if (isset($user_answers[$q_id]) && $user_answers[$q_id] == $correct_opt_id) {
            $correct_count++;
        }
    }

    $score = ($total_questions > 0) ? round(($correct_count / $total_questions) * 100) : 100;
    $is_passed = ($score >= $quiz['passing_score']) ? 1 : 0;

    // Record attempt
    $stmt = $db->prepare("INSERT INTO intern_quiz_attempts (intern_id, quiz_id, score, is_passed) VALUES (:iid, :qid, :score, :passed)");
    $stmt->execute([
        'iid' => $intern_id,
        'qid' => $quiz_id,
        'score' => $score,
        'passed' => $is_passed
    ]);

    // Get the attempt ID
    $attempt_id = $db->lastInsertId();

    // Store quiz results in session for the results page
    $_SESSION['quiz_result'] = [
        'attempt_id' => $attempt_id,
        'quiz_id' => $quiz_id,
        'video_id' => $video_id,
        'score' => $score,
        'is_passed' => $is_passed,
        'passing_score' => $quiz['passing_score'],
        'correct_count' => $correct_count,
        'total_questions' => $total_questions,
        'user_answers' => $user_answers,
        'correct_answers' => $correct_answers
    ];

    // Redirect to quiz results page instead of dashboard
    header('Location: ../../quiz-results.php');
    exit();
}
?>