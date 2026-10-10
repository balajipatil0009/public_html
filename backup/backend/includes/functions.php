<?php
// Sanitize input
function sanitize($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Validate email
function validateEmail($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Generate CSRF token
function generateCSRFToken()
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verify CSRF token
function verifyCSRFToken($token)
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Upload image
function uploadImage($file, $directory = 'general')
{
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        error_log("Upload error - File error code: " . ($file['error'] ?? 'not set'));
        return ['success' => false, 'message' => 'No file uploaded or upload error (Code: ' . ($file['error'] ?? 'unknown') . ')'];
    }

    // Validate file type
    if (!in_array($file['type'], ALLOWED_IMAGE_TYPES)) {
        error_log("Upload error - Invalid file type: " . $file['type']);
        return ['success' => false, 'message' => 'Invalid file type: ' . $file['type']];
    }

    // Validate file size
    if ($file['size'] > MAX_FILE_SIZE) {
        error_log("Upload error - File too large: " . $file['size'] . " bytes");
        return ['success' => false, 'message' => 'File too large (' . round($file['size'] / 1024 / 1024, 2) . 'MB)'];
    }

    // Use public folder for images (matching existing structure)
    // From backend/admin or backend/api, we need to go up two levels to reach public
    $uploadPath = '../../public/' . $directory . '/';
    if (!file_exists($uploadPath)) {
        if (!mkdir($uploadPath, 0755, true)) {
            error_log("Upload error - Cannot create directory: " . $uploadPath);
            return ['success' => false, 'message' => 'Cannot create upload directory'];
        }
    }

    // Generate unique filename
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $destination = $uploadPath . $filename;

    error_log("Attempting to upload to: " . $destination);

    if (move_uploaded_file($file['tmp_name'], $destination)) {
        // Auto-resize hero images to fit properly
        if ($directory === 'hero') {
            $resized = resizeImage($destination, 1920, 1080);
            if ($resized) {
                error_log("Hero image resized successfully");
            }
        }

        $finalPath = 'public/' . $directory . '/' . $filename;
        error_log("Upload successful: " . $finalPath);
        return ['success' => true, 'filename' => $finalPath];
    }

    error_log("Upload error - Failed to move file from " . $file['tmp_name'] . " to " . $destination);
    return ['success' => false, 'message' => 'Failed to move uploaded file'];
}

// Resize image to fit within max dimensions while maintaining aspect ratio
function resizeImage($imagePath, $maxWidth = 1920, $maxHeight = 1080)
{
    if (!extension_loaded('gd')) {
        error_log("GD extension not loaded. Image resizing skipped.");
        return false;
    }

    if (!file_exists($imagePath)) {
        return false;
    }

    // Get image info
    $imageInfo = getimagesize($imagePath);
    if (!$imageInfo) {
        return false;
    }

    $width = $imageInfo[0];
    $height = $imageInfo[1];
    $mime = $imageInfo['mime'];

    // Check if resize is needed
    if ($width <= $maxWidth && $height <= $maxHeight) {
        return true; // No resize needed
    }

    // Calculate new dimensions maintaining aspect ratio
    $ratio = min($maxWidth / $width, $maxHeight / $height);
    $newWidth = (int) ($width * $ratio);
    $newHeight = (int) ($height * $ratio);

    // Create image resource based on type
    switch ($mime) {
        case 'image/jpeg':
            $srcImage = imagecreatefromjpeg($imagePath);
            break;
        case 'image/png':
            $srcImage = imagecreatefrompng($imagePath);
            break;
        case 'image/gif':
            $srcImage = imagecreatefromgif($imagePath);
            break;
        case 'image/webp':
            $srcImage = imagecreatefromwebp($imagePath);
            break;
        default:
            return false;
    }

    if (!$srcImage) {
        return false;
    }

    // Create new image
    $dstImage = imagecreatetruecolor($newWidth, $newHeight);

    // Preserve transparency for PNG and GIF
    if ($mime === 'image/png' || $mime === 'image/gif') {
        imagealphablending($dstImage, false);
        imagesavealpha($dstImage, true);
        $transparent = imagecolorallocatealpha($dstImage, 0, 0, 0, 127);
        imagefill($dstImage, 0, 0, $transparent);
    }

    // Resize
    imagecopyresampled($dstImage, $srcImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

    // Save resized image
    $success = false;
    switch ($mime) {
        case 'image/jpeg':
            $success = imagejpeg($dstImage, $imagePath, 90); // 90% quality
            break;
        case 'image/png':
            $success = imagepng($dstImage, $imagePath, 8); // Compression level 8
            break;
        case 'image/gif':
            $success = imagegif($dstImage, $imagePath);
            break;
        case 'image/webp':
            $success = imagewebp($dstImage, $imagePath, 90);
            break;
    }

    // Clean up
    imagedestroy($srcImage);
    imagedestroy($dstImage);

    return $success;
}


// Delete image
function deleteImage($filename)
{
    // From backend/admin or backend/api, we need to go up two levels to reach the file
    $filepath = '../../' . $filename; // Images are in public folder
    if (file_exists($filepath)) {
        return unlink($filepath);
    }
    return false;
}

// Format date
function formatDate($date, $format = 'd M Y')
{
    return date($format, strtotime($date));
}

// Truncate text
function truncateText($text, $length = 100)
{
    if (strlen($text) > $length) {
        return substr($text, 0, $length) . '...';
    }
    return $text;
}

// Generate slug
function generateSlug($text)
{
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9-]/', '-', $text);
    $text = preg_replace('/-+/', '-', $text);
    return trim($text, '-');
}

// Success message
function setSuccessMessage($message)
{
    $_SESSION['success_message'] = $message;
}

// Error message
function setErrorMessage($message)
{
    $_SESSION['error_message'] = $message;
}

// Get and clear success message
function getSuccessMessage()
{
    if (isset($_SESSION['success_message'])) {
        $message = $_SESSION['success_message'];
        unset($_SESSION['success_message']);
        return $message;
    }
    return null;
}

// Get and clear error message
function getErrorMessage()
{
    if (isset($_SESSION['error_message'])) {
        $message = $_SESSION['error_message'];
        unset($_SESSION['error_message']);
        return $message;
    }
    return null;
}
?>