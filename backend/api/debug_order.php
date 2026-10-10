<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "Step 1: start<br>";

try {
    $configPath = __DIR__ . '/../config/config.php';
    echo "Step 2: Checking config at $configPath <br>";
    if (!file_exists($configPath)) {
        die("Config file missing");
    }
    require_once $configPath;
    echo "Step 3: Config loaded<br>";

    $dbPath = __DIR__ . '/../config/database.php';
    echo "Step 4: Checking db at $dbPath <br>";
    if (!file_exists($dbPath)) {
        die("DB file missing");
    }
    require_once $dbPath;
    echo "Step 5: DB loaded<br>";

    $payPath = __DIR__ . '/../config/payment_config.php';
    echo "Step 6: Checking payment at $payPath <br>";
    if (!file_exists($payPath)) {
        die("Payment config missing");
    }
    $paymentConfig = require $payPath;
    echo "Step 7: Payment config loaded. Key: " . ($paymentConfig['key_id'] ?? 'MISSING') . "<br>";

    $database = new Database();
    echo "Step 8: Database instantiated<br>";

    $db = $database->connect();
    echo "Step 9: Database connected? " . ($db ? 'YES' : 'NO') . "<br>";

    if (!$db) {
        die("DB Connection failed");
    }

    $stmt = $db->query("SELECT count(*) FROM internships");
    echo "Step 10: Query executed. Rows: " . $stmt->fetchColumn() . "<br>";

    if (function_exists('curl_init')) {
        echo "Step 11: cURL is enabled<br>";
    } else {
        echo "Step 11: cURL is MISSING<br>";
    }

    echo "Step 12: allow_url_fopen: " . (ini_get('allow_url_fopen') ? 'ON' : 'OFF') . "<br>";
    echo "Step 13: php.ini: " . php_ini_loaded_file() . "<br>";

} catch (Throwable $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "<br>";
}
?>