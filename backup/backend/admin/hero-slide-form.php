<?php
$pageTitle = isset($_GET['id']) ? 'Edit Hero Slide' : 'Add Hero Slide';
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$auth = new Auth();
$auth->requireLogin();

$database = new Database();
$db = $database->connect();

$slide = null;
$isEdit = false;

// Get slide if editing
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $db->prepare("SELECT * FROM hero_slides WHERE id = :id");
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $slide = $stmt->fetch();
    $isEdit = true;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($_POST['title']);
    $subtitle = sanitize($_POST['subtitle']);
    $description = sanitize($_POST['description']);
    $button_text = sanitize($_POST['button_text']);
    $button_url = sanitize($_POST['button_url']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $display_order = (int)$_POST['display_order'];
    
    $image_url = $slide['image_url'] ?? null;
    $hasError = false;
    
    // Handle file upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = uploadImage($_FILES['image'], 'hero');
        
        if ($uploadResult['success']) {
            // Delete old image if editing and not a default image
            if ($isEdit && $slide['image_url'] && !in_array($slide['image_url'], ['public/main3.jpg', 'public/mainImg.jpg', 'public/mainImg2.jpg'])) {
                deleteImage($slide['image_url']);
            }
            $image_url = $uploadResult['filename'];
        } else {
            setErrorMessage('Upload failed: ' . $uploadResult['message']);
            $hasError = true;
        }
    } elseif (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        // Handle upload errors
        $uploadErrors = [
            UPLOAD_ERR_INI_SIZE => 'File too large (server limit)',
            UPLOAD_ERR_FORM_SIZE => 'File too large (form limit)',
            UPLOAD_ERR_PARTIAL => 'File partially uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'No temporary directory',
            UPLOAD_ERR_CANT_WRITE => 'Cannot write to disk',
            UPLOAD_ERR_EXTENSION => 'Upload stopped by extension'
        ];
        
        $errorMsg = $uploadErrors[$_FILES['image']['error']] ?? 'Unknown upload error';
        setErrorMessage('Upload error: ' . $errorMsg);
        $hasError = true;
    }
    
    // Validate required fields
    if (empty($title)) {
        setErrorMessage('Title is required');
        $hasError = true;
    }
    
    if (!$image_url && !$isEdit) {
        setErrorMessage('Please upload an image');
        $hasError = true;
    }
    
    // Proceed with database operation if no errors
    if (!$hasError && $image_url) {
        if ($isEdit) {
            $query = "UPDATE hero_slides SET title = :title, subtitle = :subtitle, description = :description, 
                      image_url = :image_url, button_text = :button_text, button_url = :button_url, 
                      is_active = :is_active, display_order = :display_order WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $_GET['id']);
        } else {
            $query = "INSERT INTO hero_slides (title, subtitle, description, image_url, button_text, button_url, is_active, display_order) 
                      VALUES (:title, :subtitle, :description, :image_url, :button_text, :button_url, :is_active, :display_order)";
            $stmt = $db->prepare($query);
        }
        
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':subtitle', $subtitle);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':image_url', $image_url);
        $stmt->bindParam(':button_text', $button_text);
        $stmt->bindParam(':button_url', $button_url);
        $stmt->bindParam(':is_active', $is_active);
        $stmt->bindParam(':display_order', $display_order);
        
        if ($stmt->execute()) {
            setSuccessMessage($isEdit ? 'Hero slide updated successfully' : 'Hero slide created successfully');
            header('Location: hero-slides.php');
            exit();
        } else {
            setErrorMessage('Failed to save hero slide');
        }
    }
}

include 'includes/header.php';
?>

<div class="content-section">
    <h2><?php echo $isEdit ? 'Edit' : 'Add'; ?> Hero Slide</h2>
    
    <form method="POST" action="" enctype="multipart/form-data">
        <?php if ($isEdit && $slide['image_url']): ?>
            <div class="form-group">
                <label>Current Image</label>
                <div style="margin-top: 0.5rem;">
                    <img src="../../<?php echo htmlspecialchars($slide['image_url']); ?>" 
                         alt="Current" style="max-width: 400px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                </div>
            </div>
        <?php endif; ?>
        
        <div class="form-group">
            <label for="image">
                <?php echo $isEdit ? 'Change Image (leave empty to keep current)' : 'Hero Image *'; ?>
            </label>
            <input type="file" id="image" name="image" class="form-control" 
                   accept="image/jpeg,image/png,image/jpg,image/gif" 
                   <?php echo !$isEdit ? 'required' : ''; ?>>
            <small style="color: #6b7280;">Recommended: 1920x800px or larger. Allowed: JPG, PNG, GIF. Max size: 5MB</small>
        </div>
        
        <div class="form-group">
            <label for="title">Title *</label>
            <input type="text" id="title" name="title" class="form-control" 
                   value="<?php echo $slide['title'] ?? ''; ?>" required
                   placeholder="e.g., Transform Your Career in IT">
        </div>
        
        <div class="form-group">
            <label for="subtitle">Subtitle</label>
            <input type="text" id="subtitle" name="subtitle" class="form-control" 
                   value="<?php echo $slide['subtitle'] ?? ''; ?>"
                   placeholder="e.g., Join India's Leading IT Training Institute">
        </div>
        
        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" class="form-control" 
                      rows="4" placeholder="Detailed description of the slide content"><?php echo $slide['description'] ?? ''; ?></textarea>
        </div>
        
        <div class="form-row">
            <div class="form-group" style="flex: 1;">
                <label for="button_text">Button Text</label>
                <input type="text" id="button_text" name="button_text" class="form-control" 
                       value="<?php echo $slide['button_text'] ?? ''; ?>"
                       placeholder="e.g., Explore Courses">
            </div>
            <div class="form-group" style="flex: 2; margin-left: 1rem;">
                <label for="button_url">Button URL</label>
                <input type="text" id="button_url" name="button_url" class="form-control" 
                       value="<?php echo $slide['button_url'] ?? ''; ?>"
                       placeholder="e.g., courses.php or https://example.com">
            </div>
        </div>
        
        <div class="form-group">
            <label for="display_order">Display Order</label>
            <input type="number" id="display_order" name="display_order" class="form-control" 
                   value="<?php echo $slide['display_order'] ?? 1; ?>" min="0">
            <small style="color: #6b7280;">Lower numbers appear first in the slider</small>
        </div>
        
        <div class="form-group">
            <label>
                <input type="checkbox" name="is_active" <?php echo ($slide['is_active'] ?? 1) ? 'checked' : ''; ?>>
                Active (Visible on website)
            </label>
        </div>
        
        <div class="action-buttons">
            <button type="submit" class="btn btn-primary">
                <?php echo $isEdit ? 'Update Slide' : 'Create Slide'; ?>
            </button>
            <a href="hero-slides.php" class="btn btn-danger">Cancel</a>
        </div>
    </form>
</div>

<style>
.form-row {
    display: flex;
    align-items: end;
}

.form-row .form-group {
    margin-bottom: 1rem;
}
</style>

<?php include 'includes/footer.php'; ?>