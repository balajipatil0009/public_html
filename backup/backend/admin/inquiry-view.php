<?php
$pageTitle = 'View Inquiry';
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$auth = new Auth();
$auth->requireLogin();

$database = new Database();
$db = $database->connect();

// Get inquiry
if (!isset($_GET['id'])) {
    header('Location: inquiries.php');
    exit();
}

$id = (int)$_GET['id'];
$stmt = $db->prepare("SELECT * FROM contact_inquiries WHERE id = :id");
$stmt->bindParam(':id', $id);
$stmt->execute();
$inquiry = $stmt->fetch();

if (!$inquiry) {
    setErrorMessage('Inquiry not found');
    header('Location: inquiries.php');
    exit();
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status'])) {
    $status = sanitize($_POST['status']);
    $notes = sanitize($_POST['notes']);
    
    if (in_array($status, ['new', 'contacted', 'converted', 'closed'])) {
        $updateQuery = "UPDATE contact_inquiries SET status = :status, notes = :notes WHERE id = :id";
        $updateStmt = $db->prepare($updateQuery);
        $updateStmt->bindParam(':status', $status);
        $updateStmt->bindParam(':notes', $notes);
        $updateStmt->bindParam(':id', $id);
        
        if ($updateStmt->execute()) {
            setSuccessMessage('Inquiry updated successfully');
            // Refresh inquiry data
            $stmt->execute();
            $inquiry = $stmt->fetch();
        }
    }
}

include 'includes/header.php';
?>

<div class="content-section">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <h2>Inquiry Details</h2>
        <a href="inquiries.php" class="btn btn-primary">← Back to Inquiries</a>
    </div>
    
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem;">
        <!-- Main Details -->
        <div>
            <div style="background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 1.5rem;">
                <h3 style="color: #1e3a8a; margin-bottom: 1rem; font-size: 1.25rem;">Contact Information</h3>
                
                <div style="display: grid; gap: 1rem;">
                    <div>
                        <label style="font-weight: 600; color: #374151; display: block; margin-bottom: 0.25rem;">Name</label>
                        <p style="color: #1f2937; font-size: 1.1rem;"><?php echo htmlspecialchars($inquiry['name']); ?></p>
                    </div>
                    
                    <div>
                        <label style="font-weight: 600; color: #374151; display: block; margin-bottom: 0.25rem;">Email</label>
                        <p style="color: #1f2937;">
                            <a href="mailto:<?php echo htmlspecialchars($inquiry['email']); ?>" style="color: #1e3a8a; text-decoration: none;">
                                📧 <?php echo htmlspecialchars($inquiry['email']); ?>
                            </a>
                        </p>
                    </div>
                    
                    <?php if ($inquiry['phone']): ?>
                    <div>
                        <label style="font-weight: 600; color: #374151; display: block; margin-bottom: 0.25rem;">Phone</label>
                        <p style="color: #1f2937;">
                            <a href="tel:<?php echo htmlspecialchars($inquiry['phone']); ?>" style="color: #1e3a8a; text-decoration: none;">
                                📱 <?php echo htmlspecialchars($inquiry['phone']); ?>
                            </a>
                        </p>
                    </div>
                    <?php endif; ?>
                    
                    <div>
                        <label style="font-weight: 600; color: #374151; display: block; margin-bottom: 0.25rem;">Course Interest</label>
                        <p style="color: #1f2937;"><?php echo htmlspecialchars($inquiry['course_interest'] ?? 'General Inquiry'); ?></p>
                    </div>
                    
                    <div>
                        <label style="font-weight: 600; color: #374151; display: block; margin-bottom: 0.25rem;">Received On</label>
                        <p style="color: #1f2937;"><?php echo formatDate($inquiry['created_at'], 'd M Y, h:i A'); ?></p>
                    </div>
                </div>
            </div>
            
            <div style="background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                <h3 style="color: #1e3a8a; margin-bottom: 1rem; font-size: 1.25rem;">Message</h3>
                <div style="background: #f9fafb; padding: 1rem; border-radius: 6px; border-left: 4px solid #1e3a8a;">
                    <p style="color: #1f2937; line-height: 1.6; white-space: pre-wrap;"><?php echo htmlspecialchars($inquiry['message']); ?></p>
                </div>
            </div>
        </div>
        
        <!-- Status & Notes -->
        <div>
            <div style="background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                <h3 style="color: #1e3a8a; margin-bottom: 1rem; font-size: 1.25rem;">Update Status</h3>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status" class="form-control" required>
                            <option value="new" <?php echo $inquiry['status'] === 'new' ? 'selected' : ''; ?>>🔔 New</option>
                            <option value="contacted" <?php echo $inquiry['status'] === 'contacted' ? 'selected' : ''; ?>>📞 Contacted</option>
                            <option value="converted" <?php echo $inquiry['status'] === 'converted' ? 'selected' : ''; ?>>✅ Converted</option>
                            <option value="closed" <?php echo $inquiry['status'] === 'closed' ? 'selected' : ''; ?>>🔒 Closed</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="notes">Internal Notes</label>
                        <textarea id="notes" name="notes" class="form-control" rows="6" 
                                  placeholder="Add notes about follow-up, conversation details, etc."><?php echo htmlspecialchars($inquiry['notes'] ?? ''); ?></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" style="width: 100%;">Update Inquiry</button>
                </form>
            </div>
            
            <!-- Quick Actions -->
            <div style="background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-top: 1.5rem;">
                <h3 style="color: #1e3a8a; margin-bottom: 1rem; font-size: 1.25rem;">Quick Actions</h3>
                
                <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                    <a href="mailto:<?php echo htmlspecialchars($inquiry['email']); ?>?subject=Re: Your Inquiry at ITINFO ACADEMY" 
                       class="btn btn-success" style="width: 100%; text-align: center;">
                        📧 Send Email
                    </a>
                    
                    <?php if ($inquiry['phone']): ?>
                    <a href="tel:<?php echo htmlspecialchars($inquiry['phone']); ?>" 
                       class="btn btn-success" style="width: 100%; text-align: center;">
                        📱 Call Now
                    </a>
                    <?php endif; ?>
                    
                    <a href="inquiries.php?delete=<?php echo $inquiry['id']; ?>" 
                       class="btn btn-danger" style="width: 100%; text-align: center;"
                       onclick="return confirm('Are you sure you want to delete this inquiry?')">
                        🗑️ Delete Inquiry
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
