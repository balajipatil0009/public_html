<?php
/**
 * Dynamic Sitemap Generator for ITINFO ACADEMY
 * Generates XML sitemap for search engines
 */

header('Content-Type: application/xml; charset=utf-8');

// Base URL - update this for production
$baseUrl = 'https://itinfoacademy.in';

// Get current date for lastmod
$today = date('Y-m-d');

// Define pages with their priorities and change frequencies
$pages = [
    ['url' => '/', 'priority' => '1.0', 'changefreq' => 'weekly', 'lastmod' => $today],
    ['url' => '/index.php', 'priority' => '1.0', 'changefreq' => 'weekly', 'lastmod' => $today],
    ['url' => '/about.php', 'priority' => '0.8', 'changefreq' => 'monthly', 'lastmod' => $today],
    ['url' => '/courses.php', 'priority' => '0.9', 'changefreq' => 'weekly', 'lastmod' => $today],
    ['url' => '/internship.php', 'priority' => '0.9', 'changefreq' => 'weekly', 'lastmod' => $today],
    ['url' => '/gallery.php', 'priority' => '0.6', 'changefreq' => 'monthly', 'lastmod' => $today],
    ['url' => '/apply.php', 'priority' => '0.8', 'changefreq' => 'monthly', 'lastmod' => $today],
    ['url' => '/terms-and-conditions.php', 'priority' => '0.3', 'changefreq' => 'yearly', 'lastmod' => $today],
];

// Try to get courses from database for dynamic sitemap
try {
    require_once 'backend/config/database.php';
    $database = new Database();
    $db = $database->connect();

    if ($db) {
        // Add course pages
        $stmt = $db->query("SELECT slug FROM courses WHERE is_active = 1");
        $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($courses as $course) {
            $pages[] = [
                'url' => '/course.php?slug=' . urlencode($course['slug']),
                'priority' => '0.7',
                'changefreq' => 'monthly',
                'lastmod' => $today
            ];
        }
    }
} catch (Exception $e) {
    // Continue without dynamic pages
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9
        http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">
    <?php foreach ($pages as $page): ?>
        <url>
            <loc>
                <?php echo htmlspecialchars($baseUrl . $page['url']); ?>
            </loc>
            <lastmod>
                <?php echo $page['lastmod']; ?>
            </lastmod>
            <changefreq>
                <?php echo $page['changefreq']; ?>
            </changefreq>
            <priority>
                <?php echo $page['priority']; ?>
            </priority>
        </url>
    <?php endforeach; ?>
</urlset>