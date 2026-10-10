<?php
/**
 * Job fetch cron - pulls fresh IT jobs from Adzuna India into the `jobs` table.
 *
 * Local (Windows / XAMPP) - Task Scheduler:
 *   C:\xampp\php\php.exe C:\xampp\htdocs\public_html\cron\fetch-jobs.php
 *
 * Shared hosting (hPanel/cPanel -> Cron Jobs):
 *   0 2 * * * php /home/USER/public_html/cron/fetch-jobs.php
 *
 * A lock file stops two runs from overlapping.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/../backend/config/config.php';
require_once __DIR__ . '/../backend/config/database.php';
require_once __DIR__ . '/../backend/includes/JobFetcher.php';

$lockPath = __DIR__ . '/jobs.lock';
$lockHandle = fopen($lockPath, 'c');
if ($lockHandle === false) {
    fwrite(STDERR, "Cannot open lock file: $lockPath\n");
    exit(1);
}

if (!flock($lockHandle, LOCK_EX | LOCK_NB)) {
    echo "Another job fetch is already running. Exiting.\n";
    fclose($lockHandle);
    exit(0);
}

ftruncate($lockHandle, 0);
fwrite($lockHandle, (string) getmypid());

$started = date('Y-m-d H:i:s');
echo "[$started] Job fetch started\n";

$db = (new Database())->connect();
if (!$db) {
    echo "Database connection failed. Check .env credentials and that MySQL is running.\n";
    flock($lockHandle, LOCK_UN);
    fclose($lockHandle);
    exit(1);
}

try {
    $fetcher = new JobFetcher($db);
    $stats   = $fetcher->run('cron');

    printf(
        "Status: %s | Adzuna calls: %d | Ad results: %d | new: %d | duplicates: %d | %.2fs\n",
        $stats['status'],
        $stats['calls'],
        $stats['fetched'],
        $stats['new'],
        $stats['duplicate'],
        $stats['duration']
    );

    if (!empty($stats['errors'])) {
        foreach (array_slice($stats['errors'], 0, 5) as $message) {
            echo "  - $message\n";
        }
    }

    $exitCode = ($stats['status'] === 'failed') ? 1 : 0;
} catch (Throwable $e) {
    echo "Fatal error: " . $e->getMessage() . "\n";
    error_log('cron/fetch-jobs.php fatal: ' . $e->getMessage());
    $exitCode = 1;
}

flock($lockHandle, LOCK_UN);
fclose($lockHandle);

exit($exitCode);
