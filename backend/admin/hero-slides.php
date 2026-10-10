<?php
$pageTitle = 'Hero Slider';
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$auth = new Auth();
$auth->requireLogin();

$database = new Database();
$db = $database->connect();

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    // Get image URL to delete file
    $stmt = $db->prepare("SELECT image_url FROM hero_slides WHERE id = :id");
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $slide = $stmt->fetch();
    
    if ($slide) {
        // Delete from database
        $deleteStmt = $db->prepare("DELETE FROM hero_slides WHERE id = :id");
        $deleteStmt->bindParam(':id', $id);
        
        if ($deleteStmt->execute()) {
            // Delete physical file if it's not a default image
            if (!in_array($slide['image_url'], ['public/main3.jpg', 'public/mainImg.jpg', 'public/mainImg2.jpg'])) {
                deleteImage($slide['image_url']);
            }
            setSuccessMessage('Hero slide deleted successfully');
        } else {
            setErrorMessage('Failed to delete hero slide');
        }
    }
    
    header('Location: hero-slides.php');
    exit();
}

// Handle toggle active
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $stmt = $db->prepare("UPDATE hero_slides SET is_active = NOT is_active WHERE id = :id");
    $stmt->bindParam(':id', $id);
    if ($stmt->execute()) {
        setSuccessMessage('Hero slide status updated successfully');
    }
    header('Location: hero-slides.php');
    exit();
}

// Get all hero slides
$stmt = $db->query("SELECT * FROM hero_slides ORDER BY display_order ASC, created_at DESC");
$slides = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="content-section">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <h2>Manage Hero Slider</h2>
        <a href="hero-slide-form.php" class="btn btn-primary">+ Add New Slide</a>
    </div>
    
    <div class="hero-slides-grid">
        <?php if (count($slides) > 0): ?>
            <?php foreach ($slides as $slide): ?>
                <div class="hero-slide-card">
                    <div class="hero-slide-image">
                        <img src="../../<?php echo htmlspecialchars($slide['image_url']); ?>" 
                             alt="<?php echo htmlspecialchars($slide['title']); ?>">
                        <?php if (!$slide['is_active']): ?>
                            <div class="slide-inactive-badge">Inactive</div>
                        <?php endif; ?>
                        <div class="slide-order-badge"><?php echo $slide['display_order']; ?></div>
                    </div>
                    <div class="hero-slide-content">
                        <h3><?php echo htmlspecialchars($slide['title']); ?></h3>
                        <?php if ($slide['subtitle']): ?>
                            <h4><?php echo htmlspecialchars($slide['subtitle']); ?></h4>
                        <?php endif; ?>
                        <?php if ($slide['description']): ?>
                            <p><?php echo truncateText($slide['description'], 100); ?></p>
                        <?php endif; ?>
                        <?php if ($slide['button_text'] && $slide['button_url']): ?>
                            <div class="slide-button-info">
                                <strong>Button:</strong> <?php echo htmlspecialchars($slide['button_text']); ?> 
                                → <?php echo htmlspecialchars($slide['button_url']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="hero-slide-actions">
                        <a href="hero-slide-form.php?id=<?php echo $slide['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                        <a href="?toggle=<?php echo $slide['id']; ?>" class="btn btn-sm btn-secondary">
                            <?php echo $slide['is_active'] ? 'Hide' : 'Show'; ?>
                        </a>
                        <a href="?delete=<?php echo $slide['id']; ?>" class="btn btn-sm btn-danger" 
                           onclick="return confirm('Are you sure? This will delete the slide and its image.')">Delete</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="grid-column: 1/-1; text-align: center; padding: 3rem; color: #6b7280;">
                <p style="font-size: 1.25rem; margin-bottom: 1rem;">🎬 No hero slides found</p>
                <a href="hero-slide-form.php" class="btn btn-primary">Create Your First Slide</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>