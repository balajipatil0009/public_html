<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/config.php';
require_once '../config/database.php';

// Rate Limiting: 10 requests per minute (to protect free API tier)
$limit = 10;
$timeframe = 60; // seconds

if (!isset($_SESSION['chat_requests'])) {
    $_SESSION['chat_requests'] = [];
}

$currentTime = time();
$_SESSION['chat_requests'] = array_filter($_SESSION['chat_requests'], function ($timestamp) use ($currentTime, $timeframe) {
    return ($currentTime - $timestamp) < $timeframe;
});

if (count($_SESSION['chat_requests']) >= $limit) {
    http_response_code(429);
    echo json_encode([
        'success' => false,
        'message' => 'Chat busy. Please wait about 60 seconds before asking more questions.'
    ]);
    exit();
}

// Log this attempt
$_SESSION['chat_requests'][] = $currentTime;

// Fetch courses with pricing from database
$coursesInfo = "";
try {
    $database = new Database();
    $db = $database->connect();

    if ($db) {
        $stmt = $db->query("SELECT title, category, fees, duration FROM courses WHERE is_active = 1 ORDER BY category, title");
        $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $coursesByCategory = [];
        foreach ($courses as $course) {
            $cat = $course['category'];
            if (!isset($coursesByCategory[$cat])) {
                $coursesByCategory[$cat] = [];
            }
            $priceInfo = ($course['fees'] && $course['fees'] > 0)
                ? "₹" . number_format($course['fees'])
                : "Contact for pricing";
            $durationInfo = $course['duration'] ? " ({$course['duration']})" : "";
            $coursesByCategory[$cat][] = "{$course['title']}: {$priceInfo}{$durationInfo}";
        }

        $categoryLabels = [
            'job_role' => 'Job-Oriented Programs',
            'programming' => 'Programming Languages',
            'web_dev' => 'Web Development',
            'database' => 'Database',
            'design' => 'Design & Creative'
        ];

        $coursesInfo = "\n\nCOURSE PRICING (Live from database):\n";
        foreach ($coursesByCategory as $category => $courseList) {
            $label = $categoryLabels[$category] ?? ucwords(str_replace('_', ' ', $category));
            $coursesInfo .= "* {$label}:\n";
            foreach ($courseList as $courseItem) {
                $coursesInfo .= "  - {$courseItem}\n";
            }
        }
    }
} catch (Exception $e) {
    // Fallback to static info if database fails
    $coursesInfo = "";
}

// System context to be used on the client side
$systemPrompt = "You are 'Aria', a friendly and professional AI counselor at ITINFO ACADEMY. 
Your goal is to help visitors discover the right IT courses and answer questions about the academy.
Talk like a real human: be warm, helpful, and concise. Don't sound like a generic robot.

CONTEXT ABOUT ITINFO ACADEMY:
- Location:3 rd floor, Swadeshi heights, above IDFC first bank,  beside Vijaynagar bus stop, sangli.
- Contact: Email (validatoritinfoacademy@gmail.com), Phone (9545214074).
- Mission: To empower students with cutting-edge IT skills through practical training.
- Key Benefits: Experts-led training, 100% practicals, placement assistance, industry-aligned curriculum.
{$coursesInfo}
RULES:
1. Always be polite and human-like.
2. If you don't know something, ask them to contact the academy directly using the phone numbers provided.
3. BE EXTREMELY BRIEF. Maximum 1-2 short sentences. Never give long lists or huge paragraphs.
4. When asked about pricing, give the EXACT price from the course list above if available.
5. If a course says 'Contact for pricing', tell them to call 9545214074 for pricing details.
6. Do not mention you are an AI model unless asked.
7. Focus on converting the visitor's interest into an enquiry or enrollment.";

// Return the key and prompt
echo json_encode([
    'success' => true,
    'apiKey' => GROQ_API_KEY,
    'systemPrompt' => $systemPrompt
]);
?>