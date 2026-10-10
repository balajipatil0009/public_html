<?php
/**
 * Security Helper Functions
 * Provides common security utilities for XSS prevention and data sanitization
 */

/**
 * Escape HTML output to prevent XSS attacks
 * 
 * @param mixed $data Data to escape
 * @param string $encoding Character encoding (default: UTF-8)
 * @return string Escaped data
 */
function e($data, $encoding = 'UTF-8')
{
    if ($data === null || $data === '') {
        return '';
    }
    return htmlspecialchars((string) $data, ENT_QUOTES | ENT_HTML5, $encoding);
}

/**
 * Escape HTML attribute value
 * 
 * @param mixed $data Data to escape
 * @return string Escaped data
 */
function attr($data)
{
    return e($data);
}

/**
 * Escape JavaScript string
 * 
 * @param mixed $data Data to escape
 * @return string Escaped data
 */
function js($data)
{
    if ($data === null || $data === '') {
        return '';
    }
    return json_encode((string) $data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
}

/**
 * Escape URL parameter
 * 
 * @param mixed $data Data to escape
 * @return string Escaped data
 */
function url($data)
{
    if ($data === null || $data === '') {
        return '';
    }
    return urlencode((string) $data);
}


/**
 * Sanitize input with type-specific filtering
 * Note: Basic sanitize() function exists in functions.php
 * This provides additional type-specific sanitization
 * 
 * @param mixed $data Data to sanitize
 * @param string $type Type of sanitization (email, int, float, url)
 * @return mixed Sanitized data
 */
function sanitize_typed($data, $type)
{
    if ($data === null) {
        return null;
    }

    switch ($type) {
        case 'email':
            return filter_var($data, FILTER_SANITIZE_EMAIL);
        case 'int':
            return filter_var($data, FILTER_SANITIZE_NUMBER_INT);
        case 'float':
            return filter_var($data, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        case 'url':
            return filter_var($data, FILTER_SANITIZE_URL);
        default:
            return trim(strip_tags($data));
    }
}


/**
 * Generate CSRF token
 * 
 * @return string CSRF token
 */
function csrf_token()
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 * 
 * @param string $token Token to verify
 * @return bool True if valid
 */
function csrf_verify($token)
{
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Generate CSRF token field for forms
 * 
 * @return string HTML hidden input field
 */
function csrf_field()
{
    $token = csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . e($token) . '">';
}

/**
 * Check if request is POST and has valid CSRF token
 * 
 * @return bool True if valid POST request
 */
function is_post_valid()
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return false;
    }

    $token = $_POST['csrf_token'] ?? '';
    return csrf_verify($token);
}

/**
 * Prevent clickjacking by setting X-Frame-Options header
 */
function prevent_clickjacking()
{
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('X-XSS-Protection: 1; mode=block');
}

/**
 * Secure session configuration
 */
function secure_session()
{
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.use_strict_mode', 1);
}
