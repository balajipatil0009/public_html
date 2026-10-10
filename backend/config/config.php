<?php
// Load environment variables
require_once __DIR__ . '/EnvLoader.php';
EnvLoader::load(__DIR__ . '/../../.env');

// Load security helpers
require_once __DIR__ . '/../includes/security.php';

// Secure session configuration
secure_session();

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set security headers
prevent_clickjacking();

// Site configuration
define('SITE_NAME', EnvLoader::get('SITE_NAME', 'ITINFO ACADEMY'));
define('SITE_URL', EnvLoader::get('SITE_URL', 'https://itinfoacademy.in/'));
define('ADMIN_URL', SITE_URL . '/backend/admin');

// AI Chatbot
define('GROQ_API_KEY', EnvLoader::require('GROQ_API_KEY'));

// Security
define('HASH_ALGO', PASSWORD_BCRYPT);
define('SESSION_TIMEOUT', (int) EnvLoader::get('SESSION_TIMEOUT', 3600));

// File upload settings
define('UPLOAD_DIR', EnvLoader::get('UPLOAD_DIR', '../uploads/'));
define('MAX_FILE_SIZE', (int) EnvLoader::get('MAX_FILE_SIZE', 5242880)); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/jpg', 'image/gif']);

// Timezone
date_default_timezone_set('Asia/Kolkata');

// Error reporting (disable in production)
$debugMode = filter_var(EnvLoader::get('DEBUG_MODE', 'false'), FILTER_VALIDATE_BOOLEAN);
error_reporting(E_ALL);
ini_set('display_errors', $debugMode ? '1' : '0'); // Show errors only in debug mode
