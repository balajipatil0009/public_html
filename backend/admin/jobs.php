<?php
$pageTitle = 'Job Board (Adzuna)';
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../includes/JobFetcher.php';

$auth = new Auth();
$auth->requireLogin();

$database = new Database();
$db = $database->connect();

/* ---------------------------------------------------------------------
 * Actions
 * ------------------------------------------------------------------ */

// Delete a single stored job
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    if ($id > 0) {
        try {
            $stmt = $db->prepare("DELETE FROM jobs WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            setSuccessMessage('Job removed from the board');
        } catch (Exception $e) {
            setErrorMessage('Could not delete that job');
        }
    }
    header('Location: jobs.php');
    exit();
}

// Purge old listings
if (isset($_GET['purge'])) {
    $days = max(1, min(365, (int) $_GET['purge']));
    try {
        $stmt = $db->prepare("DELETE FROM jobs WHERE posted_date IS NOT NULL AND posted_date < (CURDATE() - INTERVAL " . $days . " DAY)");
        $stmt->execute();
        $removed = $stmt->rowCount();
        setSuccessMessage($removed . ' job(s) older than ' . $days . ' days removed');
    } catch (Exception $e) {
        setErrorMessage('Could not remove old jobs');
    }
    header('Location: jobs.php');
    exit();
}

/* ---------------------------------------------------------------------
 * Data
 * ------------------------------------------------------------------ */

$fetcher = new JobFetcher($db);
$stats   = $fetcher->getStats();
$logs    = $fetcher->getLogs(10);

$search = isset($_GET['q']) ? trim(mb_substr((string) $_GET['q'], 0, 80)) : '';
$page   = isset($_GET['page']) && is_numeric($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$listing = $fetcher->getJobsForAdmin($page, 25, $search);

$totalPages = $listing['per_page'] > 0 ? (int) ceil($listing['total'] / $listing['per_page']) : 1;
$configured = $fetcher->getClient()->isConfigured();

include 'includes/header.php';
?>

<div class="dashboard-grid">
    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(0, 33, 71, 0.05); color: #002147;"><i class="fas fa-briefcase"></i></div>
        <div class="stat-content">
            <h3><?php echo number_format($stats['total']); ?></h3>
            <p>Jobs in board</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(61, 177, 102, 0.05); color: #3db166;"><i class="fas fa-plus-circle"></i></div>
        <div class="stat-content">
            <h3><?php echo number_format($stats['today_new']); ?></h3>
            <p>Added today</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(0, 33, 71, 0.05); color: #002147;"><i class="fas fa-map-marker-alt"></i></div>
        <div class="stat-content">
            <h3><?php echo number_format($stats['cities']); ?></h3>
            <p>Cities covered</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(61, 177, 102, 0.05); color: #3db166;"><i class="fas fa-cloud-download-alt"></i></div>
        <div class="stat-content">
            <h3><?php echo number_format($stats['calls_today']); ?></h3>
            <p>Adzuna calls today</p>
        </div>
    </div>
</div>

<div class="content-section">
    <div style="display: flex; flex-wrap: wrap; gap: 0.75rem; justify-content: space-between; align-items: center; margin-bottom: 1.25rem;">
        <div>
            <h2>Adzuna India feed</h2>
            <p style="color: #666; font-size: 0.9rem; margin-top: 0.35rem;">
                Pulls fresh IT jobs from Adzuna (India) into the public job board page.
                <?php if ($stats['last_run']): ?>
                    Last run: <strong><?php echo htmlspecialchars($stats['last_run']['created_at']); ?></strong>
                    &middot; <?php echo (int) $stats['last_run']['jobs_new']; ?> new
                    &middot; <?php echo (int) $stats['last_run']['calls_made']; ?> API calls
                <?php else: ?>
                    No fetch has run yet.
                <?php endif; ?>
            </p>
        </div>
        <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
            <button type="button" class="btn btn-primary" id="fetchJobsBtn">
                <i class="fas fa-sync-alt"></i> Fetch jobs now
            </button>
            <a href="../job.php" target="_blank" class="btn btn-success" style="background:#3db166;color:#fff;">
                <i class="fas fa-external-link-alt"></i> View public page
            </a>
            <button type="button" class="btn" style="background:#c0392b;color:#fff;" id="purgeJobsBtn">
                <i class="fas fa-broom"></i> Purge old listings
            </button>
        </div>
    </div>

    <?php if (!$configured): ?>
        <div class="alert alert-error">
            <strong>Adzuna credentials are missing.</strong>
            Add <code>ADZUNA_APP_ID</code> and <code>ADZUNA_APP_KEY</code> to your <code>.env</code> file
            (free keys: <a href="https://developer.adzuna.com" target="_blank" rel="noopener">developer.adzuna.com</a>).
        </div>
    <?php endif; ?>

    <div id="fetchResult" class="alert" style="display: none;"></div>
</div>

<div class="content-section">
    <h2 style="margin-bottom: 1rem;">Stored jobs <span style="font-weight: 400; color:#666; font-size: 0.95rem;">(<?php echo number_format($listing['total']); ?>)</span></h2>

    <form method="GET" action="jobs.php" style="margin-bottom: 1rem; display: flex; gap: 0.5rem; flex-wrap: wrap;">
        <input type="text" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search title, company or city"
            style="padding: 0.55rem 0.75rem; border: 1px solid #ddd; border-radius: 2px; min-width: 260px; font-family: inherit;">
        <button type="submit" class="btn btn-primary">Search</button>
        <?php if ($search !== ''): ?>
            <a href="jobs.php" class="btn">Clear</a>
        <?php endif; ?>
    </form>

    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Company</th>
                    <th>Location</th>
                    <th>Type</th>
                    <th>Salary</th>
                    <th>Posted</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($listing['rows'])): ?>
                    <?php foreach ($listing['rows'] as $job): ?>
                        <tr>
                            <td>
                                <?php echo htmlspecialchars(mb_strimwidth($job['title'], 0, 55, '...')); ?>
                                <?php if ((int) $job['is_remote'] === 1): ?>
                                    <span class="badge badge-active">Remote</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($job['company'] ?: 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($job['city'] ?: ($job['location'] ?: 'N/A')); ?></td>
                            <td><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', (string) $job['contract_time'])) ?: 'N/A'); ?></td>
                            <td>
                                <?php if (!empty($job['salary_min'])): ?>
                                    &#8377;<?php echo number_format((int) $job['salary_min']); ?><?php echo !empty($job['salary_max']) ? ' - &#8377;' . number_format((int) $job['salary_max']) : ''; ?>
                                    <?php if ((int) $job['salary_is_predicted'] === 1): ?><small style="color:#999;">(est.)</small><?php endif; ?>
                                <?php else: ?>
                                    Not stated
                                <?php endif; ?>
                            </td>
                            <td><?php echo !empty($job['posted_date']) ? date('d M Y', strtotime($job['posted_date'])) : 'N/A'; ?></td>
                            <td><?php echo date('d M Y', strtotime($job['created_at'])); ?></td>
                            <td class="action-buttons">
                                <?php if (!empty($job['redirect_url'])): ?>
                                    <a href="<?php echo htmlspecialchars($job['redirect_url']); ?>" target="_blank" rel="noopener" class="btn btn-sm btn-success">Open</a>
                                <?php endif; ?>
                                <a href="?delete=<?php echo (int) $job['id']; ?>" class="btn btn-sm" style="background:#c0392b;color:#fff;"
                                    onclick="return confirm('Remove this job from the board?')">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" style="text-align: center;">
                            No jobs stored yet. Use <strong>Fetch jobs now</strong> to pull the first batch from Adzuna.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalPages > 1): ?>
        <div style="margin-top: 1rem; display: flex; gap: 0.35rem; flex-wrap: wrap;">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?php echo $i; ?><?php echo $search !== '' ? '&q=' . urlencode($search) : ''; ?>"
                   style="padding: 0.4rem 0.7rem; border: 1px solid #ddd; border-radius: 2px; text-decoration: none; color: <?php echo $i === $page ? '#fff' : '#333'; ?>; background: <?php echo $i === $page ? '#002147' : '#fff'; ?>;">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>

<div class="content-section">
    <h2 style="margin-bottom: 1rem;">Fetch history</h2>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Run</th>
                    <th>Status</th>
                    <th>Calls</th>
                    <th>Examined</th>
                    <th>New</th>
                    <th>Duplicates</th>
                    <th>Duration</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($logs)): ?>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($log['run_type']); ?><br>
                                <small style="color:#999;"><?php echo htmlspecialchars($log['created_at']); ?></small>
                            </td>
                            <td>
                                <span class="badge <?php echo $log['status'] === 'success' ? 'badge-converted' : ($log['status'] === 'partial' ? 'badge-pending' : 'badge-closed'); ?>">
                                    <?php echo htmlspecialchars($log['status']); ?>
                                </span>
                            </td>
                            <td><?php echo (int) $log['calls_made']; ?></td>
                            <td><?php echo (int) $log['jobs_fetched']; ?></td>
                            <td><?php echo (int) $log['jobs_new']; ?></td>
                            <td><?php echo (int) $log['jobs_duplicate']; ?></td>
                            <td><?php echo number_format((float) $log['duration_sec'], 2); ?>s</td>
                            <td style="max-width: 320px; font-size: 0.8rem; color:#666;">
                                <?php echo htmlspecialchars(mb_strimwidth((string) $log['error_message'], 0, 120, '...')); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" style="text-align: center;">No fetch history yet.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <p style="margin-top: 1rem; color:#666; font-size: 0.85rem;">
        <strong>Cron:</strong> the daily fetch is scheduled separately on the server, e.g.
        <code>0 2 * * * php /home/YOUR_USER/public_html/cron/fetch-jobs.php</code>.
        This button runs the exact same fetch on demand.
    </p>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var fetchBtn = document.getElementById('fetchJobsBtn');
    var resultBox = document.getElementById('fetchResult');
    var purgeBtn = document.getElementById('purgeJobsBtn');

    function showResult(type, message) {
        resultBox.style.display = 'block';
        resultBox.className = 'alert ' + (type === 'success' ? 'alert-success' : 'alert-error');
        resultBox.textContent = message;
    }

    if (fetchBtn) {
        fetchBtn.addEventListener('click', function () {
            var original = fetchBtn.innerHTML;

            fetchBtn.disabled = true;
            fetchBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Fetching...';
            resultBox.style.display = 'block';
            resultBox.className = 'alert';
            resultBox.textContent = 'Contacting Adzuna - this can take up to a minute...';

            fetch('../api/fetch-jobs.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ action: 'fetch' }),
                credentials: 'same-origin'
            })
                .then(function (response) { return response.json(); })
                .then(function (payload) {
                    showResult(payload && payload.success ? 'success' : 'error',
                        (payload && payload.message) || 'Request finished.');

                    if (payload && payload.success) {
                        setTimeout(function () { window.location.reload(); }, 1200);
                    } else {
                        fetchBtn.disabled = false;
                        fetchBtn.innerHTML = original;
                    }
                })
                .catch(function () {
                    showResult('error', 'Network error while fetching jobs. Please try again.');
                    fetchBtn.disabled = false;
                    fetchBtn.innerHTML = original;
                });
        });
    }

    if (purgeBtn) {
        purgeBtn.addEventListener('click', function () {
            var days = prompt('Delete jobs older than how many days?', '60');
            if (days === null) {
                return;
            }
            days = parseInt(days, 10);
            if (!days || days < 1) {
                alert('Please enter a valid number of days.');
                return;
            }
            window.location.href = 'jobs.php?purge=' + encodeURIComponent(days);
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>
