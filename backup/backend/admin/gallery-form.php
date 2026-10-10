<?php
$pageTitle = isset($_GET['id']) ? 'Edit Gallery Image' : 'Upload Image';
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$auth = new Auth();
$auth->requireLogin();

$database = new Database();
$db = $database->connect();

$image = null;
$isEdit = false;

// Get image if editing
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $db->prepare("SELECT * FROM gallery WHERE id = :id");
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $image = $stmt->fetch();
    $isEdit = true;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($_POST['title']);
    $description = sanitize($_POST['description']);
    $category = sanitize($_POST['category']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $display_order = (int)$_POST['display_order'];
    
    $image_url = $image['image_url'] ?? null;
    $hasError = false;
    
    // Debug: Check if file was uploaded
    if (isset($_FILES['image'])) {
        error_log("File upload attempt - Error code: " . $_FILES['image']['error']);
        error_log("File name: " . $_FILES['image']['name']);
        error_log("File size: " . $_FILES['image']['size']);
        error_log("File type: " . $_FILES['image']['type']);
    }
    
    // Handle file upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = uploadImage($_FILES['image'], 'gallery');
        
        if ($uploadResult['success']) {
            // Delete old image if editing
            if ($isEdit && $image['image_url']) {
                deleteImage($image['image_url']);
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
    
    // Validate image URL - only require for new uploads
    if (!$image_url && !$isEdit) {
        setErrorMessage('Please upload an image');
        $hasError = true;
    }
    
    // Proceed with database operation if no errors
    if (!$hasError && $image_url) {
        if ($isEdit) {
            $query = "UPDATE gallery SET title = :title, description = :description, category = :category, 
                      image_url = :image_url, is_active = :is_active, display_order = :display_order 
                      WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $_GET['id']);
        } else {
            $query = "INSERT INTO gallery (title, description, category, image_url, is_active, display_order) 
                      VALUES (:title, :description, :category, :image_url, :is_active, :display_order)";
            $stmt = $db->prepare($query);
        }
        
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':category', $category);
        $stmt->bindParam(':image_url', $image_url);
        $stmt->bindParam(':is_active', $is_active);
        $stmt->bindParam(':display_order', $display_order);
        
        if ($stmt->execute()) {
            setSuccessMessage($isEdit ? 'Image updated successfully' : 'Image uploaded successfully');
            header('Location: gallery.php');
            exit();
        } else {
            setErrorMessage('Failed to save image to database');
        }
    }
}
?>

<div class="content-section">
    <h2><?php echo $isEdit ? 'Edit' : 'Upload'; ?> Gallery Image</h2>
    
    <form method="POST" action="" enctype="multipart/form-data">
        <?php if ($isEdit && $image['image_url']): ?>
            <div class="form-group">
                <label>Current Image</label>
                <div style="margin-top: 0.5rem;">
                    <img src="../../<?php echo htmlspecialchars($image['image_url']); ?>" 
                         alt="Current" style="max-width: 300px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                </div>
            </div>
        <?php endif; ?>
        
        <div class="form-group">
            <label for="image">
                <?php echo $isEdit ? 'Change Image (leave empty to keep current)' : 'Upload Image *'; ?>
            </label>
            <input type="file" id="image" name="image" class="form-control" 
                   accept="image/jpeg,image/png,image/jpg,image/gif" 
                   <?php echo !$isEdit ? 'required' : ''; ?>>
            <small style="color: #6b7280;">Allowed: JPG, PNG, GIF. Max size: 5MB</small>
        </div>
        
        <div class="form-group">
            <label for="title">Image Title *</label>
            <input type="text" id="title" name="title" class="form-control" 
                   value="<?php echo $image['title'] ?? ''; ?>" required>
        </div>
        
        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" class="form-control" 
                      rows="3"><?php echo $image['description'] ?? ''; ?></textarea>
        </div>
        
        <div class="form-group">
            <label for="category">Category</label>
            <input type="text" id="category" name="category" class="form-control" 
                   value="<?php echo $image['category'] ?? ''; ?>" 
                   placeholder="e.g., Events, Campus, Students, Infrastructure">
            <small style="color: #6b7280;">Optional: Group images by category</small>
        </div>
        
        <div class="form-group">
            <label for="display_order">Display Order</label>
            <input type="number" id="display_order" name="display_order" class="form-control" 
                   value="<?php echo $image['display_order'] ?? 0; ?>">
            <small style="color: #6b7280;">Lower numbers appear first</small>
        </div>
        
        <div class="form-group">
            <label>
                <input type="checkbox" name="is_active" <?php echo ($image['is_active'] ?? 1) ? 'checked' : ''; ?>>
                Active (Visible on website)
            </label>
        </div>
        
        <div class="action-buttons">
            <button type="submit" class="btn btn-primary">
                <?php echo $isEdit ? 'Update Image' : 'Upload Image'; ?>
            </button>
            <a href="gallery.php" class="btn btn-danger">Cancel</a>
        </div>
    </form>
</div>

<?php include 'includes/footer.php'; ?>
