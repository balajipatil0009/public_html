<?php
/**
 * Job fetch endpoint used by the admin panel "Fetch jobs now" button.
 *
 * POST backend/api/fetch-jobs.php            -> run a fetch sweep
 * POST backend/api/fetch-jobs.php?action=purge&days=60 -> delete old listings
 * GET  backend/api/fetch-jobs.php?action=stats -> dashboard numbers
 *
 * Requires an authenticated admin session.
 */
header('Content-Type: application/json');

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/JobFetcher.php';

$auth = new Auth();

if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Session expired. Please log in again.']);
    exit();
}

$database = new Database();
$db = $database->connect();

if (!$db) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// POST actions: fetch | purge | stats
// GET is limited to read-only actions so a stray link (or crawler) can never
// trigger a 30-60 second API sweep.
$postAction = isset($_POST['action']) ? (string) $_POST['action'] : null;
$getAction  = isset($_GET['action']) ? (string) $_GET['action'] : null;

if ($postAction !== null) {
    $action = $postAction;
} elseif ($getAction !== null && $getAction === 'stats') {
    $action = 'stats';
} elseif ($method === 'POST') {
    $action = 'fetch';
} else {
    $action = 'stats';
}

try {
    $fetcher = new JobFetcher($db);

    if ($action === 'stats') {
        echo json_encode([
            'success' => true,
            'stats'   => $fetcher->getStats(),
        ]);
        exit();
    }

    if ($action === 'purge') {
        $days = isset($_POST['days']) && is_numeric($_POST['days']) ? (int) $_POST['days'] : 60;
        $result = $fetcher->purge($days);

        if ($result['error']) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $result['error']]);
            exit();
        }

        echo json_encode([
            'success' => true,
            'message' => $result['deleted'] . ' old job(s) removed (older than ' . $days . ' days).',
            'deleted' => $result['deleted'],
        ]);
        exit();
    }

    // Default: live fetch
    if (!$fetcher->getClient()->isConfigured()) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Adzuna credentials are missing. Add ADZUNA_APP_ID and ADZUNA_APP_KEY to your .env file.',
        ]);
        exit();
    }

    $stats = $fetcher->run('manual');

    $message = sprintf(
        '%d job(s) examined, %d new added (%d duplicates skipped).',
        $stats['fetched'],
        $stats['new'],
        $stats['duplicate']
    );

    if ($stats['status'] === 'failed') {
        $message = !empty($stats['errors']) ? $stats['errors'][0] : 'Fetch failed.';
        // A tripped daily quota guard is an operational state, not a server fault.
        http_response_code(stripos($message, 'quota') !== false ? 429 : 500);
    }

    echo json_encode([
        'success' => $stats['status'] !== 'failed',
        'message' => $message,
        'stats'   => $stats,
    ]);
} catch (Throwable $e) {
    error_log('Admin job fetch error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
