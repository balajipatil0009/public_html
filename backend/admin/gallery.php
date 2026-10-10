<?php
$pageTitle = 'Gallery';
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
    $stmt = $db->prepare("SELECT image_url FROM gallery WHERE id = :id");
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $image = $stmt->fetch();
    
    if ($image) {
        // Delete from database
        $deleteStmt = $db->prepare("DELETE FROM gallery WHERE id = :id");
        $deleteStmt->bindParam(':id', $id);
        
        if ($deleteStmt->execute()) {
            // Delete physical file
            deleteImage($image['image_url']);
            setSuccessMessage('Image deleted successfully');
        } else {
            setErrorMessage('Failed to delete image');
        }
    }
    
    header('Location: gallery.php');
    exit();
}

// Handle toggle active
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $stmt = $db->prepare("UPDATE gallery SET is_active = NOT is_active WHERE id = :id");
    $stmt->bindParam(':id', $id);
    if ($stmt->execute()) {
        setSuccessMessage('Image status updated successfully');
    }
    header('Location: gallery.php');
    exit();
}

// Get filter
$categoryFilter = $_GET['category'] ?? 'all';

// Build query
$query = "SELECT * FROM gallery";
if ($categoryFilter !== 'all') {
    $query .= " WHERE category = :category";
}
$query .= " ORDER BY display_order ASC, created_at DESC";

$stmt = $db->prepare($query);
if ($categoryFilter !== 'all') {
    $stmt->bindParam(':category', $categoryFilter);
}
$stmt->execute();
$images = $stmt->fetchAll();

// Get unique categories
$categories = $db->query("SELECT DISTINCT category FROM gallery WHERE category IS NOT NULL ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);

include 'includes/header.php';
?>

<div class="content-section">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <h2>Manage Gallery</h2>
        <a href="gallery-form.php" class="btn btn-primary">+ Upload New Image</a>
    </div>
    
    <!-- Category Filter -->
    <?php if (count($categories) > 0): ?>
    <div style="margin-bottom: 1.5rem;">
        <a href="?category=all" class="btn btn-sm <?php echo $categoryFilter === 'all' ? 'btn-primary' : ''; ?>">
            All Images
        </a>
        <?php foreach ($categories as $cat): ?>
            <a href="?category=<?php echo urlencode($cat); ?>" class="btn btn-sm <?php echo $categoryFilter === $cat ? 'btn-primary' : ''; ?>">
                <?php echo htmlspecialchars(ucfirst($cat)); ?>
            </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
    <!-- Gallery Grid -->
    <div class="gallery-grid">
        <?php if (count($images) > 0): ?>
            <?php foreach ($images as $image): ?>
                <div class="gallery-item">
                    <div class="gallery-image">
                        <img src="../../<?php echo htmlspecialchars($image['image_url']); ?>" 
                             alt="<?php echo htmlspecialchars($image['title']); ?>">
                        <?php if (!$image['is_active']): ?>
                            <div class="gallery-inactive-badge">Inactive</div>
                        <?php endif; ?>
                    </div>
                    <div class="gallery-info">
                        <h4><?php echo htmlspecialchars($image['title']); ?></h4>
                        <?php if ($image['category']): ?>
                            <span class="badge badge-new"><?php echo htmlspecialchars($image['category']); ?></span>
                        <?php endif; ?>
                        <?php if ($image['description']): ?>
                            <p><?php echo truncateText($image['description'], 80); ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="gallery-actions">
                        <a href="gallery-form.php?id=<?php echo $image['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                        <a href="?toggle=<?php echo $image['id']; ?>" class="btn btn-sm btn-success">
                            <?php echo $image['is_active'] ? 'Hide' : 'Show'; ?>
                        </a>
                        <a href="?delete=<?php echo $image['id']; ?>" class="btn btn-sm btn-danger" 
                           onclick="return confirm('Are you sure? This will delete the image file.')">Delete</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="grid-column: 1/-1; text-align: center; padding: 3rem; color: #6b7280;">
                <p style="font-size: 1.25rem; margin-bottom: 1rem;">📷 No images in gallery</p>
                <a href="gallery-form.php" class="btn btn-primary">Upload Your First Image</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
