<?php
/**
 * SEO Component for ITINFO ACADEMY
 * Include this file in the <head> section of each page
 * 
 * Usage:
 * $seo = [
 *     'title' => 'Page Title',
 *     'description' => 'Page description for search engines',
 *     'keywords' => 'keyword1, keyword2',
 *     'type' => 'website', // or 'article', 'course', etc.
 *     'image' => '/public/og-image.jpg',
 *     'canonical' => '/page.php'
 * ];
 * include 'backend/includes/seo.php';
 */

// Default values
$siteUrl = 'https://itinfoacademy.in';
$siteName = 'ITINFO ACADEMY';
$defaultImage = $siteUrl . '/public/og-image.jpg';
$twitterHandle = '@itinfoacademy';

// Get current page URL
$currentUrl = $siteUrl . ($_SERVER['REQUEST_URI'] ?? '/');
$canonicalUrl = isset($seo['canonical']) ? $siteUrl . $seo['canonical'] : $currentUrl;

// SEO data with defaults
$pageTitle = isset($seo['title']) ? $seo['title'] . ' | ' . $siteName : $siteName . ' | Premier IT Training Institute';
$pageDescription = $seo['description'] ?? 'ITINFO ACADEMY - Premier IT training institute offering professional courses in Web Development, Data Science, Cybersecurity. 95% placement rate. Join now!';
$pageKeywords = $seo['keywords'] ?? 'IT training, IT courses, web development, data science, cybersecurity, programming, coding bootcamp, IT institute, placement, career';
$ogType = $seo['type'] ?? 'website';
$ogImage = isset($seo['image']) ? $siteUrl . $seo['image'] : $defaultImage;
?>
<!-- Primary Meta Tags -->
<meta name="title" content="<?php echo htmlspecialchars($pageTitle); ?>">
<meta name="description" content="<?php echo htmlspecialchars($pageDescription); ?>">
<meta name="keywords" content="<?php echo htmlspecialchars($pageKeywords); ?>">
<meta name="author" content="ITINFO ACADEMY">
<meta name="robots" content="index, follow">
<meta name="language" content="English">
<meta name="revisit-after" content="7 days">

<!-- Canonical URL -->
<link rel="canonical" href="<?php echo htmlspecialchars($canonicalUrl); ?>">

<!-- Open Graph / Facebook -->
<meta property="og:type" content="<?php echo htmlspecialchars($ogType); ?>">
<meta property="og:url" content="<?php echo htmlspecialchars($currentUrl); ?>">
<meta property="og:title" content="<?php echo htmlspecialchars($pageTitle); ?>">
<meta property="og:description" content="<?php echo htmlspecialchars($pageDescription); ?>">
<meta property="og:image" content="<?php echo htmlspecialchars($ogImage); ?>">
<meta property="og:site_name" content="ITINFO ACADEMY">
<meta property="og:locale" content="en_IN">

<!-- Twitter Card -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:url" content="<?php echo htmlspecialchars($currentUrl); ?>">
<meta name="twitter:title" content="<?php echo htmlspecialchars($pageTitle); ?>">
<meta name="twitter:description" content="<?php echo htmlspecialchars($pageDescription); ?>">
<meta name="twitter:image" content="<?php echo htmlspecialchars($ogImage); ?>">
<meta name="twitter:site" content="<?php echo $twitterHandle; ?>">

<!-- Additional SEO Meta -->
<meta name="theme-color" content="#002147">
<meta name="msapplication-TileColor" content="#002147">

<!-- Geo Tags (for local SEO) -->
<meta name="geo.region" content="IN">
<meta name="geo.placename" content="India">

<!-- Link to manifest -->
<link rel="manifest" href="/manifest.json">