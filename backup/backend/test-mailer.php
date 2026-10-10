<?php
/**
 * Test script to debug SMTP email sending
 * Access this via: http://localhost:8000/backend/test-mailer.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/includes/Mailer.php';

echo "<h2>ITINFO Mailer Test</h2>";
echo "<pre>";

// Check PHP extensions
echo "=== PHP Extension Check ===\n";
echo "OpenSSL: " . (extension_loaded('openssl') ? "✓ Loaded" : "✗ NOT LOADED - Required for TLS") . "\n";
echo "Sockets: " . (extension_loaded('sockets') ? "✓ Loaded" : "Not loaded (optional)") . "\n";
if (extension_loaded('openssl')) {
    echo "OpenSSL Version: " . OPENSSL_VERSION_TEXT . "\n";
}
echo "PHP Version: " . PHP_VERSION . "\n\n";

if (!extension_loaded('openssl')) {
    echo "\n⚠️ CRITICAL: OpenSSL extension is NOT loaded!\n";
    echo "This is required for sending emails via Gmail SMTP.\n\n";
    echo "To fix this on Windows:\n";
    echo "1. Open your php.ini file\n";
    echo "2. Find the line: ;extension=openssl\n";
    echo "3. Remove the semicolon to enable it: extension=openssl\n";
    echo "4. Restart your web server\n";
    echo "</pre>";
    exit;
}

// Test SMTP connection
echo "=== Testing SMTP Connection ===\n";

// Test port 465 (SSL)
echo "Testing smtp.gmail.com:465 (SSL)... ";
$socket = @fsockopen('ssl://smtp.gmail.com', 465, $errno, $errstr, 10);
if ($socket) {
    echo "✓ Connected!\n";
    $response = fgets($socket, 515);
    echo "Server Response: $response";
    fclose($socket);
} else {
    echo "✗ Failed: $errstr ($errno)\n";
}

// Test port 587 (STARTTLS)
echo "\nTesting smtp.gmail.com:587 (STARTTLS)... ";
$socket = @fsockopen('smtp.gmail.com', 587, $errno, $errstr, 10);
if ($socket) {
    echo "✓ Connected!\n";
    $response = fgets($socket, 515);
    echo "Server Response: $response";
    fclose($socket);
} else {
    echo "✗ Failed: $errstr ($errno)\n";
}

echo "\n=== Sending Test Email ===\n";
echo "Sending to: srijeetstdptfe@gmail.com\n\n";

$mailer = new Mailer();
$result = $mailer->sendEnquiryNotification(
    'Test User',
    'test@example.com',
    '1234567890',
    'Test Course',
    'This is a test message from the ITINFO Mailer Test script.'
);

echo "\n=== Result ===\n";
if ($result) {
    echo "✓ Email sent successfully!\n";
} else {
    echo "✗ Email sending FAILED!\n";
    echo "Last Error: " . $mailer->getLastError() . "\n";
}

echo "</pre>";
?>