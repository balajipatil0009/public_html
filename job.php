<?php
/**
 * Job Board - public listing page
 *
 * Data source: backend/api/jobs.php (MySQL `jobs` table, fed by Adzuna India)
 * Example URL: https://itinfoacademy.in/job.php?city=Pune&type=full_time
 */
require_once 'backend/config/config.php';
require_once 'backend/config/database.php';

$database = new Database();
$db = $database->connect();

$jobsReady = false;
$totalJobs = 0;

if ($db) {
    try {
        $stmt = $db->query("SHOW TABLES LIKE 'jobs'");
        if ($stmt->fetchColumn() !== false) {
            $jobsReady = true;
            $totalJobs = (int) $db->query("SELECT COUNT(*) FROM jobs")->fetchColumn();
        }
    } catch (Exception $e) {
        $jobsReady = false;
    }
}

$siteName = 'ITINFO ACADEMY';
if ($db) {
    try {
        $stmt = $db->query("SELECT setting_value FROM site_settings WHERE setting_key = 'site_name'");
        $value = $stmt->fetchColumn();
        if ($value) {
            $siteName = $value;
        }
    } catch (Exception $e) {
        // keep default
    }
}

$seo = [
    'title' => 'IT Jobs in India - Freshers & Experienced | ' . $siteName,
    'description' => 'Browse daily-updated IT and software jobs across India. Filter by city, role, salary, job type and experience. Apply to developer, data, network, testing and support roles.',
    'keywords' => 'IT jobs India, software developer jobs, fresher IT jobs, jobs in Pune, jobs in Mumbai, data analyst jobs, network engineer jobs, testing jobs, remote IT jobs',
    'type' => 'website',
    'canonical' => '/job.php'
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($seo['title']); ?></title>
    <link rel="icon" type="image/x-icon" href="public/favicon.ico">
    <?php include 'backend/includes/seo.php'; ?>
    <link rel="stylesheet" href="css/styles.css?v=2026.10">
    <link rel="stylesheet" href="css/jobs.css?v=2026.10">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <div id="navbar"></div>

    <section class="jobs-hero-premium">
        <div class="container">
            <span class="hero-badge">Career Board</span>
            <h1 class="hero-main-title">IT Jobs Across <span class="text-gradient">India</span></h1>
            <p class="hero-description">
                Developer, data, networking, testing, cloud and support openings collected from across the country.
                Filter by city, skill, salary and fresher/experienced level - updated every single day.
            </p>
            <div class="jobs-hero-stats" id="jobsHeroStats">
                <div class="jobs-hero-stat">
                    <i class="fas fa-briefcase"></i>
                    <strong id="jobsHeroTotal"><?php echo number_format($totalJobs); ?></strong>
                    <span>jobs listed</span>
                </div>
                <div class="jobs-hero-stat">
                    <i class="fas fa-location-dot"></i>
                    <span>All India coverage</span>
                </div>
                <div class="jobs-hero-stat">
                    <i class="fas fa-rotate"></i>
                    <span>Refreshed daily</span>
                </div>
            </div>
        </div>
    </section>

    <main class="jobs-board">
        <div class="container">

            <!-- ---------------- filters ---------------- -->
            <aside class="jobs-filters" id="jobsFilters">
                <h3>
                    <span><i class="fas fa-sliders"></i> Filters</span>
                    <button type="button" class="job-btn job-btn-ghost" id="jobReset" style="padding: 0.3rem 0.6rem; font-size: 0.75rem;">Reset</button>
                </h3>

                <div class="job-filter-group">
                    <label for="jobSearch">Keyword or skill</label>
                    <input type="text" id="jobSearch" placeholder="e.g. PHP developer, data analyst" autocomplete="off">
                </div>

                <div class="job-filter-group">
                    <label for="jobCity">City</label>
                    <select id="jobCity">
                        <option value="">All cities</option>
                    </select>
                </div>

                <div class="job-filter-group">
                    <label for="jobCategory">Category</label>
                    <select id="jobCategory">
                        <option value="">All IT categories</option>
                    </select>
                </div>

                <div class="job-filter-group">
                    <label for="jobType">Job type</label>
                    <select id="jobType">
                        <option value="">Any type</option>
                        <option value="full_time">Full time</option>
                        <option value="part_time">Part time</option>
                    </select>
                </div>

                <div class="job-filter-group">
                    <label for="jobContract">Contract</label>
                    <select id="jobContract">
                        <option value="">Any contract</option>
                        <option value="permanent">Permanent</option>
                        <option value="contract">Contract</option>
                    </select>
                </div>

                <div class="job-filter-group">
                    <label for="jobSalary">Minimum salary</label>
                    <select id="jobSalary">
                        <option value="0">Any salary</option>
                        <option value="300000">3 LPA +</option>
                        <option value="600000">6 LPA +</option>
                        <option value="1000000">10 LPA +</option>
                        <option value="1500000">15 LPA +</option>
                    </select>
                </div>

                <div class="job-filter-group">
                    <label for="jobDays">Posted within</label>
                    <select id="jobDays">
                        <option value="0">Any time</option>
                        <option value="1">Last 24 hours</option>
                        <option value="7">Last 7 days</option>
                        <option value="30">Last 30 days</option>
                    </select>
                </div>

                <div class="job-filter-group">
                    <label for="jobCompany">Company</label>
                    <input type="text" id="jobCompany" placeholder="e.g. TCS, Infosys" autocomplete="off">
                </div>

                <label class="job-filter-inline">
                    <input type="checkbox" id="jobRemote">
                    <span>Remote / work from home only</span>
                </label>

                <div class="job-filter-actions">
                    <a href="courses.php" class="job-btn job-btn-ghost"><i class="fas fa-graduation-cap"></i> Upskill first</a>
                    <a href="internship.php" class="job-btn job-btn-ghost"><i class="fas fa-laptop-code"></i> Internships</a>
                </div>
            </aside>

            <!-- ---------------- results ---------------- -->
            <section>
                <div class="jobs-toolbar">
                    <div class="jobs-result-count">
                        <strong id="jobsCount"><?php echo $jobsReady ? '0' : '0'; ?></strong>
                        <span id="jobsRange">jobs found</span>
                    </div>
                    <div class="jobs-toolbar-right">
                        <button type="button" class="job-btn job-btn-ghost job-filter-mobile-toggle" id="jobsFilterToggle">
                            <i class="fas fa-sliders"></i> Filters
                        </button>
                        <label for="jobSort">Sort by</label>
                        <select id="jobSort">
                            <option value="newest">Newest first</option>
                            <option value="oldest">Oldest first</option>
                            <option value="salary_desc">Salary: high to low</option>
                            <option value="salary_asc">Salary: low to high</option>
                            <option value="company">Company (A-Z)</option>
                        </select>
                    </div>
                </div>

                <div class="jobs-active-filters" id="jobsActiveFilters"></div>

                <div id="jobsState" class="jobs-state" style="display: none;"></div>
                <div id="jobsGrid" class="jobs-grid"></div>

                <div class="jobs-pagination" id="jobsPagination"></div>

                <p class="jobs-credit">
                    Job listings sourced from <a href="https://www.adzuna.in/" target="_blank" rel="noopener nofollow">Adzuna</a>
                    and refreshed every day. Always verify details on the employer's own website before applying.
                </p>
            </section>
        </div>
    </main>

    <div id="footer"></div>

    <script src="js/components.js?v=2026.10"></script>
    <script src="js/main.js"></script>
    <script src="js/jobs.js?v=2026.10"></script>
</body>

</html>
