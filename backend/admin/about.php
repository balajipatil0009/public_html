<?php
$pageTitle = 'About Us Sections';
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
    $stmt = $db->prepare("DELETE FROM about_sections WHERE id = :id");
    $stmt->bindParam(':id', $id);
    if ($stmt->execute()) {
        setSuccessMessage('Section deleted successfully');
    } else {
        setErrorMessage('Failed to delete section');
    }
    header('Location: about.php');
    exit();
}

// Handle toggle active
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $stmt = $db->prepare("UPDATE about_sections SET is_active = NOT is_active WHERE id = :id");
    $stmt->bindParam(':id', $id);
    if ($stmt->execute()) {
        setSuccessMessage('Section status updated successfully');
    }
    header('Location: about.php');
    exit();
}

// Get all sections
$sections = $db->query("SELECT * FROM about_sections ORDER BY display_order ASC, created_at ASC")->fetchAll();

include 'includes/header.php';
?>

<div class="content-section">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <h2>About Us Sections</h2>
        <a href="about-form.php" class="btn btn-primary">+ Add New Section</a>
    </div>
    
    <div style="background: #fef3c7; padding: 1rem; border-radius: 6px; margin-bottom: 1.5rem; border-left: 4px solid #f59e0b;">
        <p style="color: #92400e; margin: 0;">
            <strong>💡 Tip:</strong> These sections will appear on your About Us page. Use section_key to identify sections in your frontend code.
        </p>
    </div>
    
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Order</th>
                    <th>Section Key</th>
                    <th>Title</th>
                    <th>Content Preview</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($sections) > 0): ?>
                    <?php foreach ($sections as $section): ?>
                        <tr>
                            <td>
                                <span style="background: #dbeafe; color: #1e40af; padding: 0.25rem 0.75rem; border-radius: 12px; font-weight: 600;">
                                    <?php echo $section['display_order']; ?>
                                </span>
                            </td>
                            <td>
                                <code style="background: #f3f4f6; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.875rem;">
                                    <?php echo htmlspecialchars($section['section_key']); ?>
                                </code>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($section['title']); ?></strong>
                            </td>
                            <td>
                                <div style="max-width: 400px;">
                                    <?php echo truncateText($section['content'], 120); ?>
                                </div>
                            </td>
                            <td>
                                <span class="badge <?php echo $section['is_active'] ? 'badge-converted' : 'badge-closed'; ?>">
                                    <?php echo $section['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td class="action-buttons">
                                <a href="about-form.php?id=<?php echo $section['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                                <a href="?toggle=<?php echo $section['id']; ?>" class="btn btn-sm btn-success">Toggle</a>
                                <a href="?delete=<?php echo $section['id']; ?>" class="btn btn-sm btn-danger" 
                                   onclick="return confirm('Are you sure?')">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 2rem;">
                            <p style="color: #6b7280; font-size: 1.1rem; margin-bottom: 1rem;">📄 No sections created yet</p>
                            <a href="about-form.php" class="btn btn-primary">Create Your First Section</a>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Section Key Reference -->
<div class="content-section">
    <h3 style="color: #1e3a8a; margin-bottom: 1rem;">Common Section Keys</h3>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
        <div style="background: #f9fafb; padding: 1rem; border-radius: 6px; border-left: 3px solid #1e3a8a;">
            <strong>company_overview</strong>
            <p style="color: #6b7280; font-size: 0.875rem; margin: 0.25rem 0 0 0;">Main company introduction</p>
        </div>
        <div style="background: #f9fafb; padding: 1rem; border-radius: 6px; border-left: 3px solid #1e3a8a;">
            <strong>our_vision</strong>
            <p style="color: #6b7280; font-size: 0.875rem; margin: 0.25rem 0 0 0;">Company vision statement</p>
        </div>
        <div style="background: #f9fafb; padding: 1rem; border-radius: 6px; border-left: 3px solid #1e3a8a;">
            <strong>our_mission</strong>
            <p style="color: #6b7280; font-size: 0.875rem; margin: 0.25rem 0 0 0;">Company mission statement</p>
        </div>
        <div style="background: #f9fafb; padding: 1rem; border-radius: 6px; border-left: 3px solid #1e3a8a;">
            <strong>history</strong>
            <p style="color: #6b7280; font-size: 0.875rem; margin: 0.25rem 0 0 0;">Company history timeline</p>
        </div>
        <div style="background: #f9fafb; padding: 1rem; border-radius: 6px; border-left: 3px solid #1e3a8a;">
            <strong>team</strong>
            <p style="color: #6b7280; font-size: 0.875rem; margin: 0.25rem 0 0 0;">Team members information</p>
        </div>
        <div style="background: #f9fafb; padding: 1rem; border-radius: 6px; border-left: 3px solid #1e3a8a;">
            <strong>achievements</strong>
            <p style="color: #6b7280; font-size: 0.875rem; margin: 0.25rem 0 0 0;">Awards and milestones</p>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
