<?php
$pageTitle = isset($_GET['id']) ? 'Edit Internship' : 'Add Internship';
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$auth = new Auth();
$auth->requireLogin();

$database = new Database();
$db = $database->connect();

$internship = null;
$isEdit = false;

// Get internship if editing
if (isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    $stmt = $db->prepare("SELECT * FROM internships WHERE id = :id");
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $internship = $stmt->fetch();
    $isEdit = true;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($_POST['title']);
    $slug = generateSlug($_POST['slug'] ?: $title);
    $description = sanitize($_POST['description']);
    $duration = sanitize($_POST['duration']);
    $fees = (float) $_POST['fees'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $display_order = (int) $_POST['display_order'];

    if ($isEdit) {
        $query = "UPDATE internships SET title = :title, slug = :slug, description = :description, 
                  duration = :duration, fees = :fees, is_active = :is_active, 
                  display_order = :display_order WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $_GET['id']);
    } else {
        $query = "INSERT INTO internships (title, slug, description, duration, fees, is_active, display_order) 
                  VALUES (:title, :slug, :description, :duration, :fees, :is_active, :display_order)";
        $stmt = $db->prepare($query);
    }

    $stmt->bindParam(':title', $title);
    $stmt->bindParam(':slug', $slug);
    $stmt->bindParam(':description', $description);
    $stmt->bindParam(':duration', $duration);
    $stmt->bindParam(':fees', $fees);
    $stmt->bindParam(':is_active', $is_active);
    $stmt->bindParam(':display_order', $display_order);

    if ($stmt->execute()) {
        setSuccessMessage($isEdit ? 'Internship updated successfully' : 'Internship added successfully');
        header('Location: internships.php');
        exit();
    } else {
        setErrorMessage('Failed to save internship');
    }
}

include 'includes/header.php';
?>

<div class="content-section">
    <h2>
        <?php echo $isEdit ? 'Edit' : 'Add New'; ?> Internship
    </h2>

    <form method="POST" action="">
        <div class="form-group">
            <label for="title">Internship Title *</label>
            <input type="text" id="title" name="title" class="form-control"
                value="<?php echo $internship['title'] ?? ''; ?>" required>
        </div>

        <div class="form-group">
            <label for="slug">URL Slug *</label>
            <input type="text" id="slug" name="slug" class="form-control"
                value="<?php echo $internship['slug'] ?? ''; ?>" placeholder="Leave empty to auto-generate from title">
            <small style="color: #6b7280;">Used in URL: /internships/your-slug</small>
        </div>

        <div class="form-group">
            <label for="description">Description *</label>
            <textarea id="description" name="description" class="form-control" rows="6"
                required><?php echo $internship['description'] ?? ''; ?></textarea>
            <small style="color: #6b7280;">Detailed internship description</small>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div class="form-group">
                <label for="duration">Duration *</label>
                <input type="text" id="duration" name="duration" class="form-control"
                    value="<?php echo $internship['duration'] ?? ''; ?>" placeholder="e.g., 3 months, 6 months"
                    required>
            </div>

            <div class="form-group">
                <label for="fees">Fees (₹) *</label>
                <input type="number" id="fees" name="fees" class="form-control" step="0.01"
                    value="<?php echo $internship['fees'] ?? ''; ?>" placeholder="e.g., 5000" required>
            </div>
        </div>

        <div class="form-group">
            <label for="display_order">Display Order</label>
            <input type="number" id="display_order" name="display_order" class="form-control"
                value="<?php echo $internship['display_order'] ?? 0; ?>">
            <small style="color: #6b7280;">Lower numbers appear first</small>
        </div>

        <div class="form-group">
            <label>
                <input type="checkbox" name="is_active" <?php echo ($internship['is_active'] ?? 1) ? 'checked' : ''; ?>>
                Active (Visible on website)
            </label>
        </div>

        <div class="action-buttons" style="margin-top: 2rem;">
            <button type="submit" class="btn btn-primary">Save Internship</button>
            <a href="internships.php" class="btn btn-danger">Cancel</a>
        </div>
    </form>
</div>

<script>
    // Auto-generate slug from title
    document.getElementById('title').addEventListener('input', function () {
        const slugField = document.getElementById('slug');
        if (!slugField.value || slugField.dataset.autoGenerated) {
            const slug = this.value.toLowerCase()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '');
            slugField.value = slug;
            slugField.dataset.autoGenerated = 'true';
        }
    });

    document.getElementById('slug').addEventListener('input', function () {
        if (this.value) {
            delete this.dataset.autoGenerated;
        }
    });
</script>

<?php include 'includes/footer.php'; ?>