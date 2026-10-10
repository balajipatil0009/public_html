<?php
/**
 * JobFetcher
 *
 * Pulls IT job ads from Adzuna (India) and stores them in the `jobs` table.
 * Used by:
 *   - cron/fetch-jobs.php      (scheduled daily run)
 *   - backend/api/fetch-jobs.php (admin "Fetch jobs now" button)
 *
 * Design notes:
 *   - De-duplication by Adzuna ad id (UNIQUE key + INSERT IGNORE)
 *   - Daily API quota is respected using job_fetch_logs (calls used today)
 *   - Every run is logged so the admin panel can show real history
 */
class JobFetcher
{
    /** @var PDO */
    private $db;

    /** @var AdzunaClient */
    private $client;

    /** @var array Runtime statistics */
    private $stats = [
        'run_type'      => 'manual',
        'status'        => 'success',
        'calls'         => 0,
        'fetched'       => 0,
        'new'           => 0,
        'duplicate'     => 0,
        'skipped'       => 0,
        'queries'       => 0,
        'errors'        => [],
        'started_at'    => null,
        'duration'      => 0,
    ];

    public function __construct($db)
    {
        require_once __DIR__ . '/AdzunaClient.php';

        $this->db     = $db;
        $this->client = new AdzunaClient();
        $this->stats['started_at'] = microtime(true);
    }

    /* ---------------------------------------------------------------------
     * Public API
     * ------------------------------------------------------------------ */

    public function getClient()
    {
        return $this->client;
    }

    /**
     * Run a full fetch sweep.
     *
     * @param string $runType cron | manual | api
     * @return array stats
     */
    public function run($runType = 'manual')
    {
        $this->stats['run_type'] = in_array($runType, ['cron', 'manual', 'api'], true) ? $runType : 'manual';

        if (!$this->client->isConfigured()) {
            return $this->finish('Adzuna credentials are missing. Add ADZUNA_APP_ID / ADZUNA_APP_KEY to .env');
        }

        if (!$this->tableExists('jobs')) {
            return $this->finish('Table `jobs` does not exist. Import backend/database/jobs_schema.sql');
        }

        $perRunCap  = max(1, (int) EnvLoader::get('ADZUNA_MAX_CALLS_PER_RUN', 70));
        $dailyCap   = max(1, (int) EnvLoader::get('ADZUNA_MAX_CALLS_PER_DAY', 120));
        $usedToday  = $this->callsUsedToday();
        $remaining  = max(0, $dailyCap - $usedToday);

        if ($remaining <= 0) {
            return $this->finish(sprintf(
                'Daily Adzuna quota guard tripped (%d calls already used today).', $usedToday
            ));
        }

        $budget = min($perRunCap, $remaining);
        $maxDaysOld = $this->sanitizeMaxDaysOld((int) EnvLoader::get('ADZUNA_MAX_DAYS_OLD', 30));
        $category   = (string) EnvLoader::get('ADZUNA_CATEGORY', 'it-jobs');

        // 1) Category sweep - the newest IT jobs across India
        $pages = max(1, (int) EnvLoader::get('ADZUNA_PAGES', 5));
        for ($page = 1; $page <= $pages; $page++) {
            if (!$this->hasBudget($budget)) {
                break;
            }
            $this->stats['queries']++;
            $result = $this->client->search([
                'category'      => $category,
                'sort_by'       => 'date',
                'max_days_old'  => $maxDaysOld,
            ], $page);
            $this->stats['calls'] = $this->client->getCallsMade();
            $this->consume($result);
        }

        // 2) Keyword sweeps - wider coverage of IT roles
        foreach ($this->csvList(EnvLoader::get('ADZUNA_QUERIES', '')) as $keyword) {
            if (!$this->hasBudget($budget)) {
                break;
            }
            $this->stats['queries']++;
            $result = $this->client->search([
                'what'          => $keyword,
                'category'      => $category,
                'sort_by'       => 'date',
                'max_days_old'  => $maxDaysOld,
            ], 1);
            $this->stats['calls'] = $this->client->getCallsMade();
            $this->consume($result);
        }

        // 3) City sweeps - keeps metro/city coverage deep even when newer ads rank lower
        foreach ($this->csvList(EnvLoader::get('ADZUNA_CITIES', '')) as $city) {
            if (!$this->hasBudget($budget)) {
                break;
            }
            $this->stats['queries']++;
            $result = $this->client->search([
                'category'      => $category,
                'where'         => $city,
                'sort_by'       => 'date',
                'max_days_old'  => $maxDaysOld,
            ], 1);
            $this->stats['calls'] = $this->client->getCallsMade();
            $this->consume($result);
        }

        if ($this->stats['fetched'] === 0) {
            $reason = $this->stats['errors'] ? implode(' | ', $this->stats['errors']) : 'Adzuna returned no results';
            return $this->finish('No jobs returned. ' . $reason, 'failed');
        }

        return $this->finish(null);
    }

    /**
     * Delete listings older than N days (housekeeping).
     *
     * @param int $days
     * @return array{deleted:int, error:?string}
     */
    public function purge($days = 60)
    {
        $days = max(1, (int) $days);
        try {
            $stmt = $this->db->prepare(
                "DELETE FROM jobs WHERE posted_date IS NOT NULL AND posted_date < (CURDATE() - INTERVAL " . $days . " DAY)"
            );
            $stmt->execute();
            return ['deleted' => $stmt->rowCount(), 'error' => null];
        } catch (Exception $e) {
            error_log('Job purge error: ' . $e->getMessage());
            return ['deleted' => 0, 'error' => 'Could not delete old jobs'];
        }
    }

    /**
     * Calls already made today (from the log table).
     */
    public function callsUsedToday()
    {
        try {
            $stmt = $this->db->query("SELECT COALESCE(SUM(calls_made),0) FROM job_fetch_logs WHERE run_date = CURDATE()");
            return (int) $stmt->fetchColumn();
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Aggregated numbers for the admin dashboard.
     */
    public function getStats()
    {
        $out = [
            'total'        => 0,
            'today_new'    => 0,
            'last_fetch'   => null,
            'last_new'     => 0,
            'calls_today'  => $this->callsUsedToday(),
            'cities'       => 0,
            'companies'    => 0,
            'remote'       => 0,
            'with_salary'  => 0,
            'last_run'     => null,
        ];

        try {
            $out['total']       = (int) $this->db->query("SELECT COUNT(*) FROM jobs")->fetchColumn();
            $out['today_new']   = (int) $this->db->query("SELECT COUNT(*) FROM jobs WHERE DATE(created_at) = CURDATE()")->fetchColumn();
            $out['cities']      = (int) $this->db->query("SELECT COUNT(DISTINCT city) FROM jobs WHERE city IS NOT NULL AND city <> ''")->fetchColumn();
            $out['companies']   = (int) $this->db->query("SELECT COUNT(DISTINCT company) FROM jobs WHERE company IS NOT NULL AND company <> ''")->fetchColumn();
            $out['remote']      = (int) $this->db->query("SELECT COUNT(*) FROM jobs WHERE is_remote = 1")->fetchColumn();
            $out['with_salary'] = (int) $this->db->query("SELECT COUNT(*) FROM jobs WHERE salary_min IS NOT NULL")->fetchColumn();

            $last = $this->db->query(
                "SELECT status, jobs_new, calls_made, created_at, error_message FROM job_fetch_logs ORDER BY id DESC LIMIT 1"
            )->fetch();
            if ($last) {
                $out['last_run'] = $last;
                $out['last_fetch'] = $last['created_at'];
                $out['last_new'] = (int) $last['jobs_new'];
            }
        } catch (Exception $e) {
            error_log('Job stats error: ' . $e->getMessage());
        }

        return $out;
    }

    /**
     * Recent fetch log rows.
     */
    public function getLogs($limit = 15)
    {
        $limit = max(1, min(100, (int) $limit));
        try {
            return $this->db->query(
                "SELECT * FROM job_fetch_logs ORDER BY id DESC LIMIT " . $limit
            )->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Stored jobs for the admin table (newest first, paginated).
     */
    public function getJobsForAdmin($page = 1, $perPage = 25, $search = '')
    {
        $page    = max(1, (int) $page);
        $perPage = max(1, min(100, (int) $perPage));
        $offset  = ($page - 1) * $perPage;
        $search  = trim((string) $search);

        $where  = '';
        $params = [];
        if ($search !== '') {
            $where = "WHERE title LIKE :s OR company LIKE :s OR city LIKE :s";
            $params[':s'] = '%' . $search . '%';
        }

        try {
            $countStmt = $this->db->prepare("SELECT COUNT(*) FROM jobs $where");
            $countStmt->execute($params);
            $total = (int) $countStmt->fetchColumn();

            $stmt = $this->db->prepare(
                "SELECT * FROM jobs $where ORDER BY COALESCE(posted_date, DATE(created_at)) DESC, id DESC LIMIT :limit OFFSET :offset"
            );
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value, PDO::PARAM_STR);
            }
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            return ['rows' => $stmt->fetchAll(), 'total' => $total, 'page' => $page, 'per_page' => $perPage];
        } catch (Exception $e) {
            error_log('Admin jobs query error: ' . $e->getMessage());
            return ['rows' => [], 'total' => 0, 'page' => $page, 'per_page' => $perPage];
        }
    }

    /**
     * Delete a single stored job.
     */
    public function deleteJob($id)
    {
        $id = (int) $id;
        if ($id <= 0) {
            return ['deleted' => 0, 'error' => 'Invalid job id'];
        }
        try {
            $stmt = $this->db->prepare("DELETE FROM jobs WHERE id = :id");
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            return ['deleted' => $stmt->rowCount(), 'error' => null];
        } catch (Exception $e) {
            return ['deleted' => 0, 'error' => 'Could not delete job'];
        }
    }

    /* ---------------------------------------------------------------------
     * Internals
     * ------------------------------------------------------------------ */

    /**
     * Is there room left in this run's API budget?
     */
    private function hasBudget($budget)
    {
        if ($this->stats['calls'] >= $budget) {
            $this->stats['errors'][] = sprintf('Run budget of %d API calls reached; sweep stopped early.', $budget);
            return false;
        }
        return true;
    }

    /**
     * Handle one Adzuna response: count it, store what is new.
     */
    private function consume($result)
    {
        if (!$result['ok']) {
            if (!empty($result['error'])) {
                $this->stats['errors'][] = $result['error'];
            }
            $this->stats['status'] = 'partial';
            return;
        }

        if (empty($result['jobs'])) {
            return;
        }

        $this->stats['fetched'] += count($result['jobs']);
        $this->storeBatch($result['jobs']);
    }

    /**
     * Insert one batch of raw Adzuna ads.
     *
     * @param array $jobs
     */
    private function storeBatch(array $jobs)
    {
        try {
            $this->db->beginTransaction();

            $insert = $this->db->prepare(
                "INSERT IGNORE INTO jobs
                    (adzuna_id, title, company, location, state, city, area_json,
                     category, category_tag, contract_time, contract_type,
                     salary_min, salary_max, salary_currency, salary_is_predicted,
                     is_remote, description, redirect_url, posted_date, adzuna_created)
                 VALUES
                    (:adzuna_id, :title, :company, :location, :state, :city, :area_json,
                     :category, :category_tag, :contract_time, :contract_type,
                     :salary_min, :salary_max, :salary_currency, :salary_is_predicted,
                     :is_remote, :description, :redirect_url, :posted_date, :adzuna_created)"
            );

            foreach ($jobs as $job) {
                if (!is_array($job) || empty($job['id'])) {
                    $this->stats['skipped']++;
                    continue;
                }

                $row = $this->normalize($job);

                $insert->bindValue(':adzuna_id', $row['adzuna_id'], PDO::PARAM_STR);
                $insert->bindValue(':title', $row['title'], PDO::PARAM_STR);
                $insert->bindValue(':company', $row['company'], PDO::PARAM_STR);
                $insert->bindValue(':location', $row['location'], PDO::PARAM_STR);
                $insert->bindValue(':state', $row['state'], PDO::PARAM_STR);
                $insert->bindValue(':city', $row['city'], PDO::PARAM_STR);
                $insert->bindValue(':area_json', $row['area_json'], PDO::PARAM_STR);
                $insert->bindValue(':category', $row['category'], PDO::PARAM_STR);
                $insert->bindValue(':category_tag', $row['category_tag'], PDO::PARAM_STR);
                $insert->bindValue(':contract_time', $row['contract_time'], PDO::PARAM_STR);
                $insert->bindValue(':contract_type', $row['contract_type'], PDO::PARAM_STR);
                $insert->bindValue(':salary_min', $row['salary_min'], $row['salary_min'] === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
                $insert->bindValue(':salary_max', $row['salary_max'], $row['salary_max'] === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
                $insert->bindValue(':salary_currency', $row['salary_currency'], PDO::PARAM_STR);
                $insert->bindValue(':salary_is_predicted', $row['salary_is_predicted'], PDO::PARAM_INT);
                $insert->bindValue(':is_remote', $row['is_remote'], PDO::PARAM_INT);
                $insert->bindValue(':description', $row['description'], PDO::PARAM_STR);
                $insert->bindValue(':redirect_url', $row['redirect_url'], PDO::PARAM_STR);
                $insert->bindValue(':posted_date', $row['posted_date'], PDO::PARAM_STR);
                $insert->bindValue(':adzuna_created', $row['adzuna_created'], $row['adzuna_created'] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);

                $insert->execute();
                $affected = $insert->rowCount();

                if ($affected > 0) {
                    $this->stats['new']++;
                } else {
                    $this->stats['duplicate']++;
                }
            }

            $this->db->commit();
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $this->stats['skipped'] += count($jobs);
            $this->stats['status'] = 'partial';
            $this->stats['errors'][] = 'Insert failed: ' . $e->getMessage();
            error_log('Job store error: ' . $e->getMessage());
        }
    }

    /**
     * Map a raw Adzuna ad to our `jobs` columns.
     */
    private function normalize(array $job)
    {
        $area = [];
        if (isset($job['location']['area']) && is_array($job['location']['area'])) {
            $area = array_values(array_filter($job['location']['area'], 'strlen'));
        }

        // For India Adzuna returns area like ["India", "Maharashtra", "Pune"]
        $country = isset($area[0]) ? trim($area[0]) : '';
        $state   = isset($area[1]) ? trim($area[1]) : '';
        $city    = isset($area[2]) ? trim($area[2]) : '';

        $display = isset($job['location']['display_name'])
            ? trim((string) $job['location']['display_name'])
            : '';

        // "Pune, Maharashtra" -> city = Pune, state = Maharashtra
        $parts = $display === '' ? [] : array_map('trim', explode(',', $display));

        if ($city === '') {
            // Fall back to the first part of the display name
            $city = isset($parts[0]) ? $parts[0] : '';
        }
        if ($state === '' && count($parts) > 1) {
            $state = $parts[1];
        }

        // Country-wide listings carry no real city - keep them NULL so they do
        // not show up as a "city" option in the filters.
        if ($city === '' || ($country !== '' && strcasecmp($city, $country) === 0)) {
            $city = null;
        }
        if ($state === '' || ($country !== '' && strcasecmp($state, $country) === 0)) {
            $state = null;
        }

        $created = isset($job['created']) ? (string) $job['created'] : '';
        $postedDate = null;
        $adzunaCreated = null;
        if ($created !== '') {
            $ts = strtotime($created);
            if ($ts !== false) {
                $adzunaCreated = date('Y-m-d H:i:s', $ts);
                $postedDate    = date('Y-m-d', $ts);
            }
        }

        $title = isset($job['title']) ? (string) $job['title'] : 'Untitled role';
        $description = isset($job['description']) ? (string) $job['description'] : '';
        $company = isset($job['company']['display_name']) ? (string) $job['company']['display_name'] : '';

        return [
            'adzuna_id'           => substr((string) $job['id'], 0, 50),
            'title'               => mb_substr($title, 0, 250),
            'company'             => mb_substr($company, 0, 190),
            'location'            => $display === '' ? null : mb_substr($display, 0, 250),
            'state'               => $state === null ? null : mb_substr($state, 0, 100),
            'city'                => $city === null ? null : mb_substr($city, 0, 100),
            'area_json'           => !empty($area) ? mb_substr(json_encode($area), 0, 500) : null,
            'category'            => isset($job['category']['label']) ? mb_substr((string) $job['category']['label'], 0, 100) : null,
            'category_tag'        => isset($job['category']['tag']) ? mb_substr((string) $job['category']['tag'], 0, 100) : null,
            'contract_time'       => isset($job['contract_time']) ? mb_substr((string) $job['contract_time'], 0, 20) : null,
            'contract_type'       => isset($job['contract_type']) ? mb_substr((string) $job['contract_type'], 0, 20) : null,
            'salary_min'          => isset($job['salary_min']) && is_numeric($job['salary_min']) ? (int) $job['salary_min'] : null,
            'salary_max'          => isset($job['salary_max']) && is_numeric($job['salary_max']) ? (int) $job['salary_max'] : null,
            'salary_currency'     => isset($job['salary_currency']) ? mb_substr((string) $job['salary_currency'], 0, 10) : 'INR',
            'salary_is_predicted' => !empty($job['salary_is_predicted']) ? 1 : 0,
            'is_remote'           => $this->looksRemote($title . ' ' . $description . ' ' . $display),
            'description'         => $description,
            'redirect_url'        => isset($job['redirect_url']) ? mb_substr((string) $job['redirect_url'], 0, 500) : null,
            'posted_date'         => $postedDate,
            'adzuna_created'      => $adzunaCreated,
        ];
    }

    private function looksRemote($haystack)
    {
        $haystack = strtolower($haystack);
        $needles = ['remote', 'work from home', 'wfh', 'work remotely'];
        foreach ($needles as $needle) {
            if (strpos($haystack, $needle) !== false) {
                return 1;
            }
        }
        return 0;
    }

    /**
     * Adzuna only accepts max_days_old of 1, 7 or 30.
     */
    private function sanitizeMaxDaysOld($days)
    {
        $allowed = [1, 7, 30];
        if (in_array($days, $allowed, true)) {
            return $days;
        }
        $best = 30;
        foreach ($allowed as $value) {
            if ($value <= $days) {
                $best = $value;
            }
        }
        return $best;
    }

    private function csvList($value)
    {
        if (!is_string($value) || trim($value) === '') {
            return [];
        }
        $items = array_map('trim', explode(',', $value));
        return array_values(array_filter($items, 'strlen'));
    }

    private function tableExists($table)
    {
        try {
            $stmt = $this->db->prepare("SHOW TABLES LIKE :t");
            $stmt->bindValue(':t', $table, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetchColumn() !== false;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Close the run: persist the log row and return stats.
     */
    private function finish($error = null, $status = null)
    {
        require_once __DIR__ . '/../config/EnvLoader.php';

        if ($status !== null) {
            $this->stats['status'] = $status;
        } elseif ($error !== null) {
            $this->stats['status'] = 'failed';
        } elseif (!empty($this->stats['errors'])) {
            $this->stats['status'] = 'partial';
        }

        $this->stats['duration'] = round(microtime(true) - $this->stats['started_at'], 2);
        $this->stats['calls']   = $this->client->getCallsMade();
        $this->stats['errors']  = array_merge($this->stats['errors'], $this->client->getErrors());
        if ($error !== null) {
            $this->stats['errors'][] = $error;
        }

        try {
            $stmt = $this->db->prepare(
                "INSERT INTO job_fetch_logs
                    (run_type, status, calls_made, jobs_fetched, jobs_new, jobs_duplicate, jobs_skipped,
                     error_message, duration_sec, run_date)
                 VALUES
                    (:run_type, :status, :calls_made, :jobs_fetched, :jobs_new, :jobs_duplicate, :jobs_skipped,
                     :error_message, :duration_sec, CURDATE())"
            );
            $stmt->execute([
                ':run_type'       => $this->stats['run_type'],
                ':status'         => $this->stats['status'],
                ':calls_made'     => $this->stats['calls'],
                ':jobs_fetched'   => $this->stats['fetched'],
                ':jobs_new'       => $this->stats['new'],
                ':jobs_duplicate' => $this->stats['duplicate'],
                ':jobs_skipped'   => $this->stats['skipped'],
                ':error_message'  => $this->stats['errors'] ? mb_substr(implode(' | ', $this->stats['errors']), 0, 4000) : null,
                ':duration_sec'   => $this->stats['duration'],
            ]);
        } catch (Exception $e) {
            error_log('Job fetch log insert failed: ' . $e->getMessage());
        }

        return $this->stats;
    }
}
