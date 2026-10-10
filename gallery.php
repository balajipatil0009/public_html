<?php
require_once 'backend/config/config.php';
require_once 'backend/config/database.php';

$database = new Database();
$db = $database->connect();

$settings = [];
if ($db) {
    try {
        $stmt = $db->query("SELECT setting_key, setting_value FROM site_settings");
        $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    } catch (Exception $e) {
        $settings = [];
    }
}

if (empty($settings)) {
    $settings = ['site_name' => 'ITINFO ACADEMY'];
}

$galleryImages = [];
if ($db) {
    try {
        $stmt = $db->query("SELECT * FROM gallery WHERE is_active = 1 ORDER BY display_order ASC, created_at DESC");
        $galleryImages = $stmt->fetchAll();
    } catch (Exception $e) {
        $galleryImages = [];
    }
}

$siteName = $settings['site_name'] ?? 'ITINFO ACADEMY';
$imageGroups = array_chunk($galleryImages, 3);

// SEO Configuration for Gallery page
$seo = [
    'title' => 'Campus Gallery & Photos',
    'description' => 'Explore ITINFO ACADEMY campus life through our photo gallery. See our modern labs, student activities, workshops, and learning environment.',
    'keywords' => 'ITINFO ACADEMY gallery, campus photos, IT training center, student activities, tech workshops, coding lab, learning environment',
    'type' => 'website',
    'canonical' => '/gallery.php'
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campus Gallery - <?php echo htmlspecialchars($siteName); ?></title>
    <link rel="icon" type="image/x-icon" href="public/favicon.ico">
    <?php include 'backend/includes/seo.php'; ?>
    <link rel="stylesheet" href="css/styles.css?v=2026.10">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <div id="navbar"></div>

    <section class="gallery-hero-premium">
        <div class="container">
            <span class="hero-badge">Visual Journey</span>
            <h1 class="hero-main-title">Life at <span class="text-gradient">ITINFO ACADEMY</span>
            </h1>
            <p class="hero-description">Explore our campus, student projects, and the vibrant learning environment we
                cultivate.</p>
        </div>
    </section>

    <main class="gallery-container-premium">
        <div class="container">
            <div class="gallery-grid-premium">
                <?php if (count($galleryImages) > 0): ?>
                    <?php foreach ($galleryImages as $image): ?>
                        <div class="gallery-item-premium"
                            onclick="openLightbox('<?php echo htmlspecialchars($image['image_url']); ?>', '<?php echo htmlspecialchars($image['title']); ?>')">
                            <img src="<?php echo htmlspecialchars($image['image_url']); ?>"
                                alt="<?php echo htmlspecialchars($image['title']); ?>" loading="lazy">
                            <div class="gallery-overlay-premium">
                                <h3 class="gallery-item-title"><?php echo htmlspecialchars($image['title']); ?></h3>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <?php
                    $fallbacks = [
                        ['url' => 'public/itinfo/info1.jpeg', 'title' => 'Laboratory Session'],
                        ['url' => 'public/itinfo/info2.jpeg', 'title' => 'Expert Lectures'],
                        ['url' => 'public/itinfo/info4.jpeg', 'title' => 'Project Innovation'],
                        ['url' => 'public/itinfo/info6.jpeg', 'title' => 'Tech Workshop'],
                        ['url' => 'public/itinfo/info8.jpeg', 'title' => 'Student Collaboration'],
                        ['url' => 'public/itinfo/info12.jpeg', 'title' => 'Coding Competition'],
                        ['url' => 'public/itinfo/info17.jpeg', 'title' => 'Campus Infrastructure'],
                        ['url' => 'public/itinfo/info20.jpeg', 'title' => 'Certification Ceremony'],
                        ['url' => 'public/itinfo/info25.jpeg', 'title' => 'Industry Visit']
                    ];
                    foreach ($fallbacks as $item): ?>
                        <div class="gallery-item-premium"
                            onclick="openLightbox('<?php echo $item['url']; ?>', '<?php echo $item['title']; ?>')">
                            <img src="<?php echo $item['url']; ?>" alt="<?php echo $item['title']; ?>" loading="lazy">
                            <div class="gallery-overlay-premium">
                                <h3 class="gallery-item-title"><?php echo $item['title']; ?></h3>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <div id="lightbox" class="lightbox-overlay" onclick="closeLightbox()">
        <button class="lightbox-close">&times;</button>
        <div class="lightbox-content" onclick="event.stopPropagation()">
            <img id="lightbox-img" src="" alt="">
        </div>
    </div>

    <div id="footer"></div>

    <script src="js/components.js?v=2026.10"></script>
    <script src="js/main.js"></script>
    <script>
        function openLightbox(url, title) {
            const lightbox = document.getElementById('lightbox');
            const img = document.getElementById('lightbox-img');
            img.src = url;
            img.alt = title;
            lightbox.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeLightbox() {
            const lightbox = document.getElementById('lightbox');
            lightbox.classList.remove('active');
            document.body.style.overflow = 'auto';
        }

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeLightbox();
        });
    </script>
</body>

</html>