<?php
$pageTitle = isset($_GET['id']) ? 'Edit Course' : 'Add Course';
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$auth = new Auth();
$auth->requireLogin();

$database = new Database();
$db = $database->connect();

$course = null;
$isEdit = false;

// Get course if editing
if (isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    $stmt = $db->prepare("SELECT * FROM courses WHERE id = :id");
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $course = $stmt->fetch();
    $isEdit = true;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($_POST['title']);
    $slug = generateSlug($_POST['slug'] ?: $title);
    $short_description = sanitize($_POST['short_description']);
    $full_description = sanitize($_POST['full_description']);
    $category = sanitize($_POST['category']);
    $duration = sanitize($_POST['duration']);
    $fees = !empty($_POST['fees']) ? (float) $_POST['fees'] : null;
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $display_order = (int) $_POST['display_order'];

    // Handle syllabus JSON
    $syllabus = null;
    if (!empty($_POST['syllabus'])) {
        $syllabus = $_POST['syllabus'];
        // Validate JSON
        json_decode($syllabus);
        if (json_last_error() !== JSON_ERROR_NONE) {
            setErrorMessage('Invalid JSON format in syllabus');
            $syllabus = null;
        }
    }

    // Handle image upload
    $image_url = $course['image_url'] ?? null; // Keep existing image by default
    if (isset($_FILES['course_image']) && $_FILES['course_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../../uploads/courses/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $fileInfo = pathinfo($_FILES['course_image']['name']);
        $extension = strtolower($fileInfo['extension']);
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (in_array($extension, $allowedTypes)) {
            $newFileName = $slug . '-' . time() . '.' . $extension;
            $targetPath = $uploadDir . $newFileName;

            if (move_uploaded_file($_FILES['course_image']['tmp_name'], $targetPath)) {
                $image_url = 'uploads/courses/' . $newFileName;
            }
        } else {
            setErrorMessage('Invalid image format. Allowed: JPG, PNG, GIF, WEBP');
        }
    }

    if ($isEdit) {
        $query = "UPDATE courses SET title = :title, slug = :slug, short_description = :short_description, 
                  full_description = :full_description, category = :category, duration = :duration, 
                  fees = :fees, syllabus = :syllabus, image_url = :image_url, is_featured = :is_featured, is_active = :is_active, 
                  display_order = :display_order WHERE id = :id";

        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $_GET['id']);
    } else {
        $query = "INSERT INTO courses (title, slug, short_description, full_description, category, duration, 
                  fees, syllabus, image_url, is_featured, is_active, display_order) 
                  VALUES (:title, :slug, :short_description, :full_description, :category, :duration, 
                  :fees, :syllabus, :image_url, :is_featured, :is_active, :display_order)";

        $stmt = $db->prepare($query);
    }

    $stmt->bindParam(':title', $title);
    $stmt->bindParam(':slug', $slug);
    $stmt->bindParam(':short_description', $short_description);
    $stmt->bindParam(':full_description', $full_description);
    $stmt->bindParam(':category', $category);
    $stmt->bindParam(':duration', $duration);
    $stmt->bindParam(':fees', $fees);
    $stmt->bindParam(':syllabus', $syllabus);
    $stmt->bindParam(':image_url', $image_url);
    $stmt->bindParam(':is_featured', $is_featured);

    $stmt->bindParam(':is_active', $is_active);
    $stmt->bindParam(':display_order', $display_order);

    if ($stmt->execute()) {
        setSuccessMessage($isEdit ? 'Course updated successfully' : 'Course added successfully');
        header('Location: courses.php');
        exit();
    } else {
        setErrorMessage('Failed to save course');
    }
}

include 'includes/header.php';
?>

<div class="content-section">
    <h2><?php echo $isEdit ? 'Edit' : 'Add New'; ?> Course</h2>

    <form method="POST" action="" enctype="multipart/form-data">

        <div class="form-group">
            <label for="title">Course Title *</label>
            <input type="text" id="title" name="title" class="form-control"
                value="<?php echo $course['title'] ?? ''; ?>" required>
        </div>

        <div class="form-group">
            <label for="slug">URL Slug *</label>
            <input type="text" id="slug" name="slug" class="form-control" value="<?php echo $course['slug'] ?? ''; ?>"
                placeholder="Leave empty to auto-generate from title">
            <small style="color: #6b7280;">Used in URL: /courses/your-slug</small>
        </div>

        <div class="form-group">
            <label for="category">Category *</label>
            <select id="category" name="category" class="form-control" required>
                <option value="">Select Category</option>
                <option value="job_role" <?php echo ($course['category'] ?? '') === 'job_role' ? 'selected' : ''; ?>>Job
                    Role</option>
                <option value="programming" <?php echo ($course['category'] ?? '') === 'programming' ? 'selected' : ''; ?>>Programming</option>
                <option value="web_dev" <?php echo ($course['category'] ?? '') === 'web_dev' ? 'selected' : ''; ?>>Web
                    Development</option>
                <option value="database" <?php echo ($course['category'] ?? '') === 'database' ? 'selected' : ''; ?>>
                    Database</option>
                <option value="design" <?php echo ($course['category'] ?? '') === 'design' ? 'selected' : ''; ?>>Design
                </option>
                <option value="other" <?php echo ($course['category'] ?? '') === 'other' ? 'selected' : ''; ?>>Other
                </option>
            </select>
        </div>

        <div class="form-group">
            <label for="course_image">Course Thumbnail</label>
            <?php if (!empty($course['image_url'])): ?>
                <div style="margin-bottom: 1rem;">
                    <img src="../../<?php echo htmlspecialchars($course['image_url']); ?>" alt="Current thumbnail"
                        style="max-width: 200px; max-height: 150px; border-radius: 8px; border: 1px solid #e5e7eb;">
                    <p style="color: #6b7280; font-size: 0.875rem; margin-top: 0.5rem;">Current image. Upload a new one to
                        replace it.</p>
                </div>
            <?php endif; ?>
            <input type="file" id="course_image" name="course_image" class="form-control"
                accept="image/jpeg,image/png,image/gif,image/webp"
                style="padding: 0.75rem; border: 2px dashed #d1d5db; border-radius: 8px; background: #f9fafb;">
            <small style="color: #6b7280;">Recommended: 800x600px, JPG/PNG/WEBP. Max 2MB.</small>
        </div>


        <div class="form-group">
            <label for="short_description">Short Description *</label>
            <textarea id="short_description" name="short_description" class="form-control" rows="3"
                required><?php echo $course['short_description'] ?? ''; ?></textarea>
            <small style="color: #6b7280;">Brief description for course cards (150-200 characters)</small>
        </div>

        <div class="form-group">
            <label for="full_description">Full Description</label>
            <textarea id="full_description" name="full_description" class="form-control"
                rows="6"><?php echo $course['full_description'] ?? ''; ?></textarea>
            <small style="color: #6b7280;">Detailed course description</small>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div class="form-group">
                <label for="duration">Duration</label>
                <input type="text" id="duration" name="duration" class="form-control"
                    value="<?php echo $course['duration'] ?? ''; ?>" placeholder="e.g., 3 months, 60 hours">
            </div>

            <div class="form-group">
                <label for="fees">Fees (₹)</label>
                <input type="number" id="fees" name="fees" class="form-control" step="0.01"
                    value="<?php echo $course['fees'] ?? ''; ?>" placeholder="e.g., 15000">
            </div>
        </div>

        <div class="form-group">
            <label>Syllabus Builder</label>
            <p style="color: #6b7280; font-size: 0.875rem; margin-bottom: 1rem;">Add modules and topics for this course.
                No coding required!</p>

            <div id="syllabusBuilder"
                style="border: 1px solid #e5e7eb; border-radius: 8px; padding: 1rem; background: #f9fafb;">
                <div id="modulesContainer"></div>
                <button type="button" onclick="addModule()" class="btn btn-primary" style="margin-top: 1rem;">
                    + Add Module
                </button>
            </div>

            <!-- Hidden field to store the JSON -->
            <input type="hidden" id="syllabus" name="syllabus"
                value="<?php echo htmlspecialchars($course['syllabus'] ?? ''); ?>">
        </div>


        <div class="form-group">
            <label for="display_order">Display Order</label>
            <input type="number" id="display_order" name="display_order" class="form-control"
                value="<?php echo $course['display_order'] ?? 0; ?>">
            <small style="color: #6b7280;">Lower numbers appear first</small>
        </div>

        <div class="form-group">
            <label>
                <input type="checkbox" name="is_featured" <?php echo ($course['is_featured'] ?? 0) ? 'checked' : ''; ?>>
                Featured Course (Show on homepage)
            </label>
        </div>

        <div class="form-group">
            <label>
                <input type="checkbox" name="is_active" <?php echo ($course['is_active'] ?? 1) ? 'checked' : ''; ?>>
                Active (Visible on website)
            </label>
        </div>

        <div class="action-buttons">
            <button type="submit" class="btn btn-primary">Save Course</button>
            <a href="courses.php" class="btn btn-danger">Cancel</a>
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

    // Syllabus Builder Functions
    let moduleCount = 0;

    function addModule(title = '', points = []) {
        moduleCount++;
        const container = document.getElementById('modulesContainer');

        const moduleDiv = document.createElement('div');
        moduleDiv.className = 'syllabus-module';
        moduleDiv.id = `module-${moduleCount}`;
        moduleDiv.style.cssText = 'background: white; border: 1px solid #d1d5db; border-radius: 6px; padding: 1rem; margin-bottom: 1rem;';

        moduleDiv.innerHTML = `
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
            <strong style="color: #002147;">Module ${moduleCount}</strong>
            <button type="button" onclick="removeModule(${moduleCount})" style="background: #ef4444; color: white; border: none; padding: 0.25rem 0.5rem; border-radius: 4px; cursor: pointer; font-size: 0.75rem;">
                Remove
            </button>
        </div>
        <div style="margin-bottom: 0.75rem;">
            <input type="text" class="form-control module-title" placeholder="Module Title (e.g., Introduction to Java)" 
                   value="${escapeHtml(title)}" onchange="updateSyllabusJSON()" style="margin-bottom: 0.5rem;">
        </div>
        <div class="topics-container" id="topics-${moduleCount}">
            <label style="font-size: 0.875rem; color: #6b7280; margin-bottom: 0.5rem; display: block;">Topics:</label>
        </div>
        <button type="button" onclick="addTopic(${moduleCount})" style="background: #3db166; color: white; border: none; padding: 0.35rem 0.75rem; border-radius: 4px; cursor: pointer; font-size: 0.8rem; margin-top: 0.5rem;">
            + Add Topic
        </button>
    `;

        container.appendChild(moduleDiv);

        // Add existing points if any
        if (points.length > 0) {
            points.forEach(point => addTopic(moduleCount, point));
        } else {
            // Add one empty topic field by default
            addTopic(moduleCount);
        }

        updateSyllabusJSON();
    }

    function addTopic(moduleId, value = '') {
        const container = document.getElementById(`topics-${moduleId}`);

        const topicDiv = document.createElement('div');
        topicDiv.style.cssText = 'display: flex; gap: 0.5rem; margin-bottom: 0.5rem;';

        topicDiv.innerHTML = `
        <input type="text" class="form-control topic-input" placeholder="Topic name" 
               value="${escapeHtml(value)}" onchange="updateSyllabusJSON()" style="flex: 1;">
        <button type="button" onclick="removeTopic(this)" style="background: #6b7280; color: white; border: none; padding: 0.25rem 0.5rem; border-radius: 4px; cursor: pointer;">
            ×
        </button>
    `;

        container.appendChild(topicDiv);
        updateSyllabusJSON();
    }

    function removeModule(moduleId) {
        const module = document.getElementById(`module-${moduleId}`);
        if (module) {
            module.remove();
            updateSyllabusJSON();
        }
    }

    function removeTopic(button) {
        button.parentElement.remove();
        updateSyllabusJSON();
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function updateSyllabusJSON() {
        const modules = document.querySelectorAll('.syllabus-module');
        const syllabusData = [];

        modules.forEach(module => {
            const title = module.querySelector('.module-title').value.trim();
            const topicInputs = module.querySelectorAll('.topic-input');
            const points = [];

            topicInputs.forEach(input => {
                const value = input.value.trim();
                if (value) {
                    points.push(value);
                }
            });

            if (title || points.length > 0) {
                syllabusData.push({
                    title: title || 'Untitled Module',
                    points: points
                });
            }
        });

        document.getElementById('syllabus').value = JSON.stringify(syllabusData, null, 2);
    }

    // Initialize from existing JSON on page load
    document.addEventListener('DOMContentLoaded', function () {
        const existingJSON = document.getElementById('syllabus').value;
        if (existingJSON) {
            try {
                const data = JSON.parse(existingJSON);
                if (Array.isArray(data)) {
                    data.forEach(module => {
                        addModule(module.title || '', module.points || []);
                    });
                }
            } catch (e) {
                console.log('No existing syllabus or invalid JSON');
            }
        }
    });
</script>

<?php include 'includes/footer.php'; ?>