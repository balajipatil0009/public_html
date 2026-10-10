<?php
ob_start();

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
$paymentConfig = require __DIR__ . '/../config/payment_config.php';

// Force error suppression again in case config.php enabled it
ini_set('display_errors', 0);
error_reporting(0);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$internship_id = isset($input['internship_id']) ? (int) $input['internship_id'] : 0;

if ($internship_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid internship ID']);
    exit;
}

$database = new Database();
$db = $database->connect();

if ($db === null) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

function httpPost($url, $data, $auth)
{
    // 1. Try cURL if available
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_USERPWD, $auth);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $result = curl_exec($ch);
        if (!curl_errno($ch)) {
            curl_close($ch);
            return $result;
        }
        curl_close($ch);
    }

    // 2. Try file_get_contents (Standard Stream Wrapper)
    $opts = [
        "http" => [
            "method" => "POST",
            "header" => "Content-Type: application/json\r\n" .
                "Authorization: Basic " . base64_encode($auth) . "\r\n" .
                "User-Agent: ITInfoAcademy/1.0\r\n",
            "content" => $data,
            "ignore_errors" => true
        ],
        "ssl" => [
            "verify_peer" => false,
            "verify_peer_name" => false
        ]
    ];

    $context = stream_context_create($opts);
    $result = @file_get_contents($url, false, $context);

    if ($result !== false) {
        return $result;
    }

    // 3. Fallback: System cURL (shell_exec) - Bypasses PHP SSL stack entirely
    if (function_exists('shell_exec')) {
        // Escape parameters for shell safety
        $cmd_url = escapeshellarg($url);
        $cmd_auth = escapeshellarg($auth);

        // Write data to temp file to avoid command line length limits/escaping issues
        $tmpFile = tempnam(sys_get_temp_dir(), 'rzp_');
        file_put_contents($tmpFile, $data);

        $cmd = "curl.exe -s -X POST $cmd_url -u $cmd_auth -H \"Content-Type: application/json\" --data @" . escapeshellarg($tmpFile);

        $result = shell_exec($cmd);
        @unlink($tmpFile);

        if ($result) {
            return $result;
        }
    }

    // 4. Fallback: Raw Socket (fsockopen) - Bypasses some stream wrapper restrictions
    $parsedUrl = parse_url($url);
    $host = $parsedUrl['host'];
    $path = $parsedUrl['path'] ?? '/';

    // Explicitly try ssl:// connection
    $fp = @fsockopen("ssl://" . $host, 443, $errno, $errstr, 30);

    // If ssl:// fails, try standard tcp:// (though Razorpay likely forces redirect, it's worth a shot if local certs are completely broken)
    if (!$fp) {
        $fp = @fsockopen("tcp://" . $host, 80, $errno, $errstr, 30);
    }

    if ($fp) {
        $request = "POST " . $path . " HTTP/1.1\r\n";
        $request .= "Host: " . $host . "\r\n";
        $request .= "Content-Type: application/json\r\n";
        $request .= "Authorization: Basic " . base64_encode($auth) . "\r\n";
        $request .= "User-Agent: ITInfoAcademy/1.0\r\n";
        $request .= "Content-Length: " . strlen($data) . "\r\n";
        $request .= "Connection: Close\r\n\r\n";
        $request .= $data;

        fwrite($fp, $request);

        $response = '';
        while (!feof($fp)) {
            $response .= fgets($fp, 128);
        }
        fclose($fp);

        // Separate headers and body
        $parts = explode("\r\n\r\n", $response, 2);
        if (isset($parts[1])) {
            // Handle chunked transfer encoding if present
            if (stripos($parts[0], 'Transfer-Encoding: chunked') !== false) {
                return decode_chunked($parts[1]);
            }
            return $parts[1];
        }
    }

    // Attempt final error capture if all failed
    $error = error_get_last();
    throw new Exception('All connection methods failed. Last error: ' . ($error['message'] ?? $errstr));
}

// Helper to decode chunked response (common with raw sockets)
function decode_chunked($str)
{
    for ($res = ''; !empty($str); $str = trim($str)) {
        $pos = strpos($str, "\r\n");
        $len = hexdec(substr($str, 0, $pos));
        $res .= substr($str, $pos + 2, $len);
        $str = substr($str, $pos + 2 + $len);
    }
    return $res;
}

try {
    // Fetch fee
    $stmt = $db->prepare("SELECT title, fees FROM internships WHERE id = :id");
    $stmt->execute(['id' => $internship_id]);
    $internship = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$internship) {
        throw new Exception("Internship not found");
    }

    $amount = (float) $internship['fees'] * 100; // Amount in paise
    $receipt_id = 'rcpt_' . uniqid();

    // Create Order via Razorpay API
    $key_id = $paymentConfig['key_id'];
    $key_secret = $paymentConfig['key_secret'];

    $data = json_encode([
        'amount' => $amount,
        'currency' => 'INR',
        'receipt' => $receipt_id,
        'payment_capture' => 1
    ]);

    $result = httpPost('https://api.razorpay.com/v1/orders', $data, $key_id . ':' . $key_secret);

    $order = json_decode($result, true);
    if (!$order || !isset($order['id'])) {
        throw new Exception('Invalid response: ' . $result);
    }

    ob_clean();
    echo json_encode([
        'success' => true,
        'order_id' => $order['id'],
        'amount' => $amount,
        'key_id' => $key_id,
        'name' => 'ITINFO ACADEMY',
        'description' => 'Enrollment for ' . $internship['title'],
        'prefill_name' => '',
        'prefill_email' => ''
    ]);

} catch (Exception $e) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>