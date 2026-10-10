<?php
/**
 * Public jobs API
 *
 * GET backend/api/jobs.php
 *
 * Filters:  q, city, state, category (tag), type (full_time|part_time), contract
 *           (permanent|contract), company, salary_min, remote, days, sort
 * Pagination: page, per_page (max 60)
 * Facets:   ?facets=1  -> city / category / type / contract / salary buckets
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../config/config.php';
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

$database = new Database();
$db = $database->connect();

if (!$db) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

try {
    $tableCheck = $db->prepare("SHOW TABLES LIKE 'jobs'");
    $tableCheck->execute();
    if ($tableCheck->fetchColumn() === false) {
        http_response_code(503);
        echo json_encode(['success' => false, 'message' => 'Jobs table is not set up yet']);
        exit();
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
    exit();
}

/* ---------------------------------------------------------------------
 * Read + sanitise inputs
 * ------------------------------------------------------------------ */
$q         = isset($_GET['q']) ? trim(mb_substr((string) $_GET['q'], 0, 80)) : '';
$city      = isset($_GET['city']) ? trim(mb_substr((string) $_GET['city'], 0, 80)) : '';
$state     = isset($_GET['state']) ? trim(mb_substr((string) $_GET['state'], 0, 80)) : '';
$category  = isset($_GET['category']) ? trim(mb_substr((string) $_GET['category'], 0, 60)) : '';
$type      = isset($_GET['type']) ? strtolower(trim((string) $_GET['type'])) : '';
$contract  = isset($_GET['contract']) ? strtolower(trim((string) $_GET['contract'])) : '';
$company   = isset($_GET['company']) ? trim(mb_substr((string) $_GET['company'], 0, 80)) : '';
$salaryMin = isset($_GET['salary_min']) && is_numeric($_GET['salary_min']) ? (int) $_GET['salary_min'] : 0;
$remote    = isset($_GET['remote']) && in_array((string) $_GET['remote'], ['1', 'true', 'yes'], true) ? 1 : 0;
$hideGuess = isset($_GET['exclude_predicted']) && in_array((string) $_GET['exclude_predicted'], ['1', 'true', 'yes'], true) ? 1 : 0;
$days      = isset($_GET['days']) && is_numeric($_GET['days']) ? (int) $_GET['days'] : 0;
$sort      = isset($_GET['sort']) ? strtolower(trim((string) $_GET['sort'])) : 'newest';
$page      = isset($_GET['page']) && is_numeric($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$perPage   = isset($_GET['per_page']) && is_numeric($_GET['per_page']) ? max(1, min(60, (int) $_GET['per_page'])) : 12;
$facetsOnly = isset($_GET['facets']) && in_array((string) $_GET['facets'], ['1', 'true', 'yes'], true);

$allowedTypes     = ['full_time', 'part_time', ''];
$allowedContracts = ['permanent', 'contract', ''];
$type     = in_array($type, $allowedTypes, true) ? $type : '';
$contract = in_array($contract, $allowedContracts, true) ? $contract : '';

$sortMap = [
    'newest'      => 'j.posted_date DESC, j.id DESC',
    'oldest'      => 'j.posted_date ASC, j.id ASC',
    'salary_desc' => '(j.salary_max IS NULL) ASC, j.salary_max DESC, j.id DESC',
    'salary_asc'  => '(j.salary_min IS NULL) ASC, j.salary_min ASC, j.id DESC',
    'company'     => 'j.company ASC',
];
$orderBy = $sortMap[$sort] ?? $sortMap['newest'];

/* ---------------------------------------------------------------------
 * Build WHERE clause (fully parameterised)
 * ------------------------------------------------------------------ */
$where  = [];
$params = [];

if ($q !== '') {
    $where[] = '(j.title LIKE :q OR j.description LIKE :q OR j.company LIKE :q)';
    $params[':q'] = '%' . $q . '%';
}
if ($city !== '') {
    $where[] = '(j.city = :city OR j.location LIKE :citylike)';
    $params[':city']      = $city;
    $params[':citylike']  = '%' . $city . '%';
}
if ($state !== '') {
    $where[] = '(j.state = :state OR j.location LIKE :statelike)';
    $params[':state']     = $state;
    $params[':statelike'] = '%' . $state . '%';
}
if ($category !== '') {
    $where[] = '(j.category_tag = :category OR j.category = :category)';
    $params[':category'] = $category;
}
if ($type !== '') {
    $where[] = 'j.contract_time = :type';
    $params[':type'] = $type;
}
if ($contract !== '') {
    $where[] = 'j.contract_type = :contract';
    $params[':contract'] = $contract;
}
if ($company !== '') {
    $where[] = 'j.company LIKE :company';
    $params[':company'] = '%' . $company . '%';
}
if ($salaryMin > 0) {
    $where[] = 'j.salary_min IS NOT NULL AND j.salary_min >= :salary_min';
    $params[':salary_min'] = $salaryMin;
}
if ($remote) {
    $where[] = 'j.is_remote = 1';
}
if ($hideGuess) {
    $where[] = 'j.salary_is_predicted = 0';
}
if ($days > 0) {
    $where[] = 'j.posted_date IS NOT NULL AND j.posted_date >= (CURDATE() - INTERVAL ' . $days . ' DAY)';
}

$whereSql = $where ? (' WHERE ' . implode(' AND ', $where)) : '';

/* ---------------------------------------------------------------------
 * Facets (drop-down values + salary buckets)
 * ------------------------------------------------------------------ */
function fetchJobsFacets($db, $whereSql, $params)
{
    $facets = ['cities' => [], 'categories' => [], 'types' => [], 'contracts' => [], 'salary' => []];

    // Facet queries run against `jobs` without the `j.` alias used by the main
    // query, so the same conditions have to be stripped of their alias.
    $facetWhere = '';
    if ($whereSql !== '') {
        $prefix = trim(str_replace('j.', '', substr($whereSql, 6))); // drop leading "WHERE "
        if ($prefix !== '') {
            $facetWhere = ' WHERE ' . $prefix;
        }
    }

    $queries = [
        'cities'     => "SELECT city AS value, COUNT(*) AS total FROM jobs
                         WHERE :where1 city IS NOT NULL AND city <> ''
                         GROUP BY city ORDER BY total DESC, city ASC LIMIT 40",
        'categories' => "SELECT category_tag AS value, MAX(category) AS label, COUNT(*) AS total FROM jobs
                         WHERE :where1 category_tag IS NOT NULL AND category_tag <> ''
                         GROUP BY category_tag ORDER BY total DESC LIMIT 20",
        'types'      => "SELECT contract_time AS value, COUNT(*) AS total FROM jobs
                         WHERE :where1 contract_time IS NOT NULL AND contract_time <> ''
                         GROUP BY contract_time ORDER BY total DESC",
        'contracts'  => "SELECT contract_type AS value, COUNT(*) AS total FROM jobs
                         WHERE :where1 contract_type IS NOT NULL AND contract_type <> ''
                         GROUP BY contract_type ORDER BY total DESC",
    ];

    // :where1 expands to (conditions AND) or nothing, so every query stays valid
    // whether or not the visitor has filters active.
    foreach ($queries as $key => $sql) {
        $sql = str_replace(':where1', $facetWhere === '' ? '' : (substr($facetWhere, 7) . ' AND'), $sql);
        try {
            $stmt = $db->prepare($sql);
            foreach ($params as $name => $value) {
                $stmt->bindValue($name, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }
            $stmt->execute();
            $rows = $stmt->fetchAll();

            if ($key === 'cities') {
                $facets['cities'] = array_map(function ($r) {
                    return ['value' => $r['value'], 'total' => (int) $r['total']];
                }, $rows);
            } elseif ($key === 'categories') {
                $facets['categories'] = array_map(function ($r) {
                    return ['value' => $r['value'], 'label' => $r['label'] ?: $r['value'], 'total' => (int) $r['total']];
                }, $rows);
            } else {
                $facets[$key] = array_map(function ($r) {
                    return ['value' => $r['value'], 'total' => (int) $r['total']];
                }, $rows);
            }
        } catch (Exception $e) {
            error_log('Jobs facet error (' . $key . '): ' . $e->getMessage());
        }
    }

    try {
        $stmt = $db->prepare("SELECT
                COALESCE(MIN(salary_min), 0) AS min_salary,
                COALESCE(MAX(salary_max), 0) AS max_salary,
                COUNT(*) AS total_with_salary
            FROM jobs$facetWhere");
        foreach ($params as $name => $value) {
            $stmt->bindValue($name, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();
        $row = $stmt->fetch();
        $facets['salary'] = [
            'min'      => (int) ($row['min_salary'] ?? 0),
            'max'      => (int) ($row['max_salary'] ?? 0),
            'with'     => (int) ($row['total_with_salary'] ?? 0),
            'buckets'  => [
                ['label' => '0 - 3 LPA',  'min' => 0,        'max' => 300000],
                ['label' => '3 - 6 LPA',  'min' => 300001,   'max' => 600000],
                ['label' => '6 - 10 LPA', 'min' => 600001,   'max' => 1000000],
                ['label' => '10 - 15 LPA', 'min' => 1000001, 'max' => 1500000],
                ['label' => '15 LPA +',   'min' => 1500001,  'max' => 0],
            ],
        ];
    } catch (Exception $e) {
        error_log('Jobs salary facet error: ' . $e->getMessage());
    }

    return $facets;
}

/* ---------------------------------------------------------------------
 * Run query
 * ------------------------------------------------------------------ */
try {
    $facets = fetchJobsFacets($db, $whereSql, $params);

    if ($facetsOnly) {
        echo json_encode(['success' => true, 'facets' => $facets]);
        exit();
    }

    $countStmt = $db->prepare("SELECT COUNT(*) FROM jobs j$whereSql");
    foreach ($params as $name => $value) {
        $countStmt->bindValue($name, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $countStmt->execute();
    $total = (int) $countStmt->fetchColumn();

    $totalPages = $perPage > 0 ? (int) ceil($total / $perPage) : 1;
    if ($page > max(1, $totalPages)) {
        $page = max(1, $totalPages);
    }
    $offset = ($page - 1) * $perPage;

    $sql = "SELECT j.id, j.adzuna_id, j.title, j.company, j.location, j.state, j.city,
                   j.category, j.category_tag, j.contract_time, j.contract_type,
                   j.salary_min, j.salary_max, j.salary_currency, j.salary_is_predicted,
                   j.is_remote, j.redirect_url, j.posted_date, j.created_at,
                   LEFT(j.description, 260) AS description
            FROM jobs j$whereSql
            ORDER BY $orderBy
            LIMIT :limit OFFSET :offset";

    $stmt = $db->prepare($sql);
    foreach ($params as $name => $value) {
        $stmt->bindValue($name, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $jobs = $stmt->fetchAll();

    // Tidy output for the frontend
    foreach ($jobs as &$job) {
        $job['id']                = (int) $job['id'];
        $job['salary_min']        = $job['salary_min'] !== null ? (int) $job['salary_min'] : null;
        $job['salary_max']        = $job['salary_max'] !== null ? (int) $job['salary_max'] : null;
        $job['is_remote']         = (int) $job['is_remote'];
        $job['salary_is_predicted'] = (int) $job['salary_is_predicted'];
        $job['posted_date']       = $job['posted_date'] ?: ($job['created_at'] ? substr($job['created_at'], 0, 10) : null);
        unset($job['created_at']);
    }
    unset($job);

    echo json_encode([
        'success'     => true,
        'total'       => $total,
        'page'        => $page,
        'per_page'    => $perPage,
        'total_pages' => $totalPages,
        'sort'        => $sort,
        'filters'     => [
            'q'        => $q,
            'city'     => $city,
            'state'    => $state,
            'category' => $category,
            'type'     => $type,
            'contract' => $contract,
            'company'  => $company,
            'salary_min' => $salaryMin,
            'remote'   => $remote,
            'days'     => $days,
        ],
        'facets'      => $facets,
        'data'        => $jobs,
    ]);
} catch (Exception $e) {
    error_log('Jobs API error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
