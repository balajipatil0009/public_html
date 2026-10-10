<?php
$pageTitle = 'Site Settings';
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$auth = new Auth();
$auth->requireLogin();

$database = new Database();
$db = $database->connect();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $updated = 0;

    foreach ($_POST as $key => $value) {
        if ($key === 'csrf_token')
            continue;

        $value = sanitize($value);

        // Check if setting exists
        $checkStmt = $db->prepare("SELECT id FROM site_settings WHERE setting_key = :key");
        $checkStmt->bindParam(':key', $key);
        $checkStmt->execute();

        if ($checkStmt->rowCount() > 0) {
            // Update existing
            $updateStmt = $db->prepare("UPDATE site_settings SET setting_value = :value WHERE setting_key = :key");
            $updateStmt->bindParam(':value', $value);
            $updateStmt->bindParam(':key', $key);
            if ($updateStmt->execute()) {
                $updated++;
            }
        } else {
            // Insert new
            $insertStmt = $db->prepare("INSERT INTO site_settings (setting_key, setting_value, setting_type) VALUES (:key, :value, 'text')");
            $insertStmt->bindParam(':key', $key);
            $insertStmt->bindParam(':value', $value);
            if ($insertStmt->execute()) {
                $updated++;
            }
        }
    }

    if ($updated > 0) {
        setSuccessMessage("Settings updated successfully ($updated settings changed)");
    } else {
        setErrorMessage('No changes were made');
    }

    header('Location: settings.php');
    exit();
}

// Get all settings
$settings = [];
try {
    $stmt = $db->query("SELECT setting_key, setting_value FROM site_settings ORDER BY setting_key ASC");
    $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (PDOException $e) {
    // Table may not exist yet - show empty settings
    $settings = [];
}

// Helper function to get setting value
function getSetting($settings, $key, $default = '')
{
    return $settings[$key] ?? $default;
}

include 'includes/header.php';
?>

<div class="content-section">
    <h2>Site Settings</h2>
    <p style="color: #6b7280; margin-bottom: 1.5rem;">
        Configure global settings for your website. These settings will be available throughout your site.
    </p>

    <form method="POST" action="">
        <!-- General Settings -->
        <div
            style="background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 1.5rem;">
            <h3
                style="color: #1e3a8a; margin-bottom: 1rem; font-size: 1.25rem; border-bottom: 2px solid #dbeafe; padding-bottom: 0.5rem;">
                🏢 General Information
            </h3>

            <div class="form-group">
                <label for="site_name">Site Name</label>
                <input type="text" id="site_name" name="site_name" class="form-control"
                    value="<?php echo getSetting($settings, 'site_name', 'ITINFO ACADEMY'); ?>">
            </div>

            <div class="form-group">
                <label for="site_tagline">Site Tagline</label>
                <input type="text" id="site_tagline" name="site_tagline" class="form-control"
                    value="<?php echo getSetting($settings, 'site_tagline', 'Empowering Future IT Professionals'); ?>"
                    placeholder="Your site's tagline or slogan">
            </div>

            <div class="form-group">
                <label for="site_description">Site Description</label>
                <textarea id="site_description" name="site_description" class="form-control" rows="3"
                    placeholder="Brief description of your academy"><?php echo getSetting($settings, 'site_description'); ?></textarea>
            </div>
        </div>

        <!-- Contact Information -->
        <div
            style="background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 1.5rem;">
            <h3
                style="color: #1e3a8a; margin-bottom: 1rem; font-size: 1.25rem; border-bottom: 2px solid #dbeafe; padding-bottom: 0.5rem;">
                📞 Contact Information
            </h3>

            <div class="form-group">
                <label for="site_email">Email Address</label>
                <input type="email" id="site_email" name="site_email" class="form-control"
                    value="<?php echo getSetting($settings, 'site_email', 'info@itinfo.com'); ?>">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label for="site_phone">Phone Number</label>
                    <input type="text" id="site_phone" name="site_phone" class="form-control"
                        value="<?php echo getSetting($settings, 'site_phone', '+91 1234567890'); ?>">
                </div>

                <div class="form-group">
                    <label for="site_phone_alt">Alternate Phone</label>
                    <input type="text" id="site_phone_alt" name="site_phone_alt" class="form-control"
                        value="<?php echo getSetting($settings, 'site_phone_alt'); ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="site_address">Address</label>
                <textarea id="site_address" name="site_address" class="form-control"
                    rows="3"><?php echo getSetting($settings, 'site_address', 'Your Address Here'); ?></textarea>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label for="site_city">City</label>
                    <input type="text" id="site_city" name="site_city" class="form-control"
                        value="<?php echo getSetting($settings, 'site_city'); ?>">
                </div>

                <div class="form-group">
                    <label for="site_state">State</label>
                    <input type="text" id="site_state" name="site_state" class="form-control"
                        value="<?php echo getSetting($settings, 'site_state'); ?>">
                </div>

                <div class="form-group">
                    <label for="site_pincode">Pincode</label>
                    <input type="text" id="site_pincode" name="site_pincode" class="form-control"
                        value="<?php echo getSetting($settings, 'site_pincode'); ?>">
                </div>
            </div>
        </div>

        <!-- Social Media Links -->
        <div
            style="background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 1.5rem;">
            <h3
                style="color: #1e3a8a; margin-bottom: 1rem; font-size: 1.25rem; border-bottom: 2px solid #dbeafe; padding-bottom: 0.5rem;">
                🌐 Social Media Links
            </h3>

            <div class="form-group">
                <label for="facebook_url">Facebook Page URL</label>
                <input type="url" id="facebook_url" name="facebook_url" class="form-control"
                    value="<?php echo getSetting($settings, 'facebook_url', '#'); ?>"
                    placeholder="https://facebook.com/yourpage">
            </div>

            <div class="form-group">
                <label for="instagram_url">Instagram Profile URL</label>
                <input type="url" id="instagram_url" name="instagram_url" class="form-control"
                    value="<?php echo getSetting($settings, 'instagram_url', '#'); ?>"
                    placeholder="https://instagram.com/yourprofile">
            </div>

            <div class="form-group">
                <label for="linkedin_url">LinkedIn Profile URL</label>
                <input type="url" id="linkedin_url" name="linkedin_url" class="form-control"
                    value="<?php echo getSetting($settings, 'linkedin_url', '#'); ?>"
                    placeholder="https://linkedin.com/company/yourcompany">
            </div>

            <div class="form-group">
                <label for="twitter_url">Twitter/X Profile URL</label>
                <input type="url" id="twitter_url" name="twitter_url" class="form-control"
                    value="<?php echo getSetting($settings, 'twitter_url'); ?>"
                    placeholder="https://twitter.com/yourprofile">
            </div>

            <div class="form-group">
                <label for="youtube_url">YouTube Channel URL</label>
                <input type="url" id="youtube_url" name="youtube_url" class="form-control"
                    value="<?php echo getSetting($settings, 'youtube_url'); ?>"
                    placeholder="https://youtube.com/@yourchannel">
            </div>
        </div>

        <!-- Business Hours -->
        <div
            style="background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 1.5rem;">
            <h3
                style="color: #1e3a8a; margin-bottom: 1rem; font-size: 1.25rem; border-bottom: 2px solid #dbeafe; padding-bottom: 0.5rem;">
                🕐 Business Hours
            </h3>

            <div class="form-group">
                <label for="business_hours_weekday">Weekday Hours</label>
                <input type="text" id="business_hours_weekday" name="business_hours_weekday" class="form-control"
                    value="<?php echo getSetting($settings, 'business_hours_weekday', 'Mon - Fri: 9:00 AM - 6:00 PM'); ?>"
                    placeholder="e.g., Mon - Fri: 9:00 AM - 6:00 PM">
            </div>

            <div class="form-group">
                <label for="business_hours_weekend">Weekend Hours</label>
                <input type="text" id="business_hours_weekend" name="business_hours_weekend" class="form-control"
                    value="<?php echo getSetting($settings, 'business_hours_weekend', 'Sat: 10:00 AM - 4:00 PM'); ?>"
                    placeholder="e.g., Sat: 10:00 AM - 4:00 PM, Sun: Closed">
            </div>
        </div>

        <!-- SEO Settings -->
        <div
            style="background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 1.5rem;">
            <h3
                style="color: #1e3a8a; margin-bottom: 1rem; font-size: 1.25rem; border-bottom: 2px solid #dbeafe; padding-bottom: 0.5rem;">
                🔍 SEO Settings
            </h3>

            <div class="form-group">
                <label for="meta_keywords">Meta Keywords</label>
                <input type="text" id="meta_keywords" name="meta_keywords" class="form-control"
                    value="<?php echo getSetting($settings, 'meta_keywords'); ?>"
                    placeholder="IT training, programming courses, web development">
                <small style="color: #6b7280;">Comma-separated keywords for search engines</small>
            </div>

            <div class="form-group">
                <label for="google_analytics_id">Google Analytics ID</label>
                <input type="text" id="google_analytics_id" name="google_analytics_id" class="form-control"
                    value="<?php echo getSetting($settings, 'google_analytics_id'); ?>" placeholder="G-XXXXXXXXXX">
            </div>
        </div>

        <!-- System Settings -->
        <div
            style="background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 1.5rem;">
            <h3
                style="color: #1e3a8a; margin-bottom: 1rem; font-size: 1.25rem; border-bottom: 2px solid #dbeafe; padding-bottom: 0.5rem;">
                ⚙️ System Settings
            </h3>

            <div class="form-group">
                <label>
                    <input type="checkbox" name="maintenance_mode" value="1" <?php echo getSetting($settings, 'maintenance_mode', '0') == '1' ? 'checked' : ''; ?>>
                    Enable Maintenance Mode
                </label>
                <small style="display: block; color: #6b7280; margin-top: 0.25rem;">
                    When enabled, visitors will see a maintenance message
                </small>
            </div>

            <div class="form-group">
                <label for="maintenance_message">Maintenance Message</label>
                <textarea id="maintenance_message" name="maintenance_message" class="form-control"
                    rows="3"><?php echo getSetting($settings, 'maintenance_message', 'We are currently performing maintenance. Please check back soon.'); ?></textarea>
            </div>
        </div>

        <div class="action-buttons">
            <button type="submit" class="btn btn-primary" style="padding: 0.875rem 2rem;">
                💾 Save All Settings
            </button>
        </div>
    </form>
</div>

<?php include 'includes/footer.php'; ?>