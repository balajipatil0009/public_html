<?php
$pageTitle = isset($_GET['id']) ? 'Edit About Section' : 'Add About Section';
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$auth = new Auth();
$auth->requireLogin();

$database = new Database();
$db = $database->connect();

$section = null;
$isEdit = false;

// Get section if editing
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $db->prepare("SELECT * FROM about_sections WHERE id = :id");
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $section = $stmt->fetch();
    $isEdit = true;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $section_key = generateSlug($_POST['section_key']);
    $title = sanitize($_POST['title']);
    $content = $_POST['content']; // Don't sanitize HTML content too aggressively
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $display_order = (int)$_POST['display_order'];
    
    $image_url = $section['image_url'] ?? null;
    
    // Handle file upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = uploadImage($_FILES['image'], 'about');
        
        if ($uploadResult['success']) {
            // Delete old image if editing
            if ($isEdit && $section['image_url']) {
                deleteImage($section['image_url']);
            }
            $image_url = $uploadResult['filename'];
        } else {
            setErrorMessage($uploadResult['message']);
        }
    }
    
    // Check for duplicate section_key
    if ($isEdit) {
        $checkQuery = "SELECT id FROM about_sections WHERE section_key = :section_key AND id != :id";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->bindParam(':section_key', $section_key);
        $checkStmt->bindParam(':id', $_GET['id']);
    } else {
        $checkQuery = "SELECT id FROM about_sections WHERE section_key = :section_key";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->bindParam(':section_key', $section_key);
    }
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() > 0) {
        setErrorMessage('Section key already exists. Please use a unique key.');
    } else {
        if ($isEdit) {
            $query = "UPDATE about_sections SET section_key = :section_key, title = :title, 
                      content = :content, image_url = :image_url, is_active = :is_active, 
                      display_order = :display_order WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $_GET['id']);
        } else {
            $query = "INSERT INTO about_sections (section_key, title, content, image_url, is_active, display_order) 
                      VALUES (:section_key, :title, :content, :image_url, :is_active, :display_order)";
            $stmt = $db->prepare($query);
        }
        
        $stmt->bindParam(':section_key', $section_key);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':content', $content);
        $stmt->bindParam(':image_url', $image_url);
        $stmt->bindParam(':is_active', $is_active);
        $stmt->bindParam(':display_order', $display_order);
        
        if ($stmt->execute()) {
            setSuccessMessage($isEdit ? 'Section updated successfully' : 'Section added successfully');
            header('Location: about.php');
            exit();
        } else {
            setErrorMessage('Failed to save section');
        }
    }
}

include 'includes/header.php';
?>

<div class="content-section">
    <h2><?php echo $isEdit ? 'Edit' : 'Add New'; ?> About Section</h2>
    
    <form method="POST" action="" enctype="multipart/form-data">
        <div class="form-group">
            <label for="section_key">Section Key * <small style="color: #6b7280;">(Unique identifier for this section)</small></label>
            <input type="text" id="section_key" name="section_key" class="form-control" 
                   value="<?php echo $section['section_key'] ?? ''; ?>" required
                   placeholder="e.g., company_overview, our_vision, history">
            <small style="color: #6b7280;">
                Use lowercase with underscores. This will be used to fetch the section in your frontend.
            </small>
        </div>
        
        <div class="form-group">
            <label for="title">Section Title *</label>
            <input type="text" id="title" name="title" class="form-control" 
                   value="<?php echo $section['title'] ?? ''; ?>" required
                   placeholder="e.g., Company Overview, Our Vision">
        </div>
        
        <div class="form-group">
            <label for="content">Content *</label>
            <textarea id="content" name="content" class="form-control" 
                      rows="12" required><?php echo $section['content'] ?? ''; ?></textarea>
            <small style="color: #6b7280;">
                You can use HTML tags for formatting (p, strong, em, ul, li, br, etc.)
            </small>
        </div>
        
        <?php if ($isEdit && $section['image_url']): ?>
            <div class="form-group">
                <label>Current Image</label>
                <div style="margin-top: 0.5rem;">
                    <img src="../../<?php echo htmlspecialchars($section['image_url']); ?>" 
                         alt="Current" style="max-width: 300px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                </div>
            </div>
        <?php endif; ?>
        
        <div class="form-group">
            <label for="image">
                <?php echo $isEdit ? 'Change Image (optional)' : 'Upload Image (optional)'; ?>
            </label>
            <input type="file" id="image" name="image" class="form-control" 
                   accept="image/jpeg,image/png,image/jpg,image/gif">
            <small style="color: #6b7280;">Optional: Add an image to accompany this section</small>
        </div>
        
        <div class="form-group">
            <label for="display_order">Display Order</label>
            <input type="number" id="display_order" name="display_order" class="form-control" 
                   value="<?php echo $section['display_order'] ?? 0; ?>">
            <small style="color: #6b7280;">Lower numbers appear first on the page</small>
        </div>
        
        <div class="form-group">
            <label>
                <input type="checkbox" name="is_active" <?php echo ($section['is_active'] ?? 1) ? 'checked' : ''; ?>>
                Active (Visible on website)
            </label>
        </div>
        
        <div class="action-buttons">
            <button type="submit" class="btn btn-primary">Save Section</button>
            <a href="about.php" class="btn btn-danger">Cancel</a>
        </div>
    </form>
</div>

<!-- Content Formatting Guide -->
<div class="content-section">
    <h3 style="color: #1e3a8a; margin-bottom: 1rem;">📝 Content Formatting Guide</h3>
    <div style="background: #f9fafb; padding: 1.5rem; border-radius: 6px;">
        <p style="margin-bottom: 1rem;"><strong>You can use these HTML tags:</strong></p>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; font-family: monospace; font-size: 0.875rem;">
            <div>
                <code>&lt;p&gt;Paragraph&lt;/p&gt;</code>
            </div>
            <div>
                <code>&lt;strong&gt;Bold&lt;/strong&gt;</code>
            </div>
            <div>
                <code>&lt;em&gt;Italic&lt;/em&gt;</code>
            </div>
            <div>
                <code>&lt;br&gt; Line break</code>
            </div>
            <div>
                <code>&lt;ul&gt;&lt;li&gt;List&lt;/li&gt;&lt;/ul&gt;</code>
            </div>
            <div>
                <code>&lt;h3&gt;Heading&lt;/h3&gt;</code>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-generate section_key from title if empty
document.getElementById('title').addEventListener('input', function() {
    const keyField = document.getElementById('section_key');
    if (!keyField.value || keyField.dataset.autoGenerated) {
        const key = this.value.toLowerCase()
            .replace(/[^a-z0-9]+/g, '_')
            .replace(/^_+|_+$/g, '');
        keyField.value = key;
        keyField.dataset.autoGenerated = 'true';
    }
});

document.getElementById('section_key').addEventListener('input', function() {
    if (this.value) {
        delete this.dataset.autoGenerated;
    }
});
</script>

<?php include 'includes/footer.php'; ?>
