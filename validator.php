<?php
require_once 'backend/config/config.php';
require_once 'backend/config/database.php';

$database = new Database();
$db = $database->connect();

$settings = [];
if ($db) {
    try {
        $stmt = $db->query("SELECT setting_key, setting_value FROM site_settings");
        $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    } catch (Exception $e) {
        $settings = [];
    }
}

if (empty($settings)) {
    $settings = ['site_name' => 'ITINFO ACADEMY'];
}

$siteName = $settings['site_name'] ?? 'ITINFO ACADEMY';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Certificate | <?php echo htmlspecialchars($siteName); ?></title>
    <link rel="icon" type="image/x-icon" href="public/favicon.ico">
    <link rel="stylesheet" href="css/styles.css?v=2026.10">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <style>
        :root {
            --validator-width: 680px;
        }

        .minimal-header {
            padding: 120px 0 60px;
            background: var(--primary);
            color: white;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .minimal-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 50% 50%, rgba(61, 177, 102, 0.1) 0%, transparent 70%);
            pointer-events: none;
        }

        .validator-container {
            margin-top: -60px;
            padding-bottom: 100px;
            position: relative;
            z-index: 10;
        }

        .sophisticated-card {
            background: white;
            padding: 4rem;
            border-radius: 4px;
            box-shadow: 0 40px 100px rgba(0, 0, 0, 0.08);
            max-width: var(--validator-width);
            margin: 0 auto;
            border-top: 4px solid var(--secondary);
        }

        .validator-title-box {
            text-align: center;
            margin-bottom: 3rem;
        }

        .validator-tag {
            font-family: 'JetBrains Mono', monospace;
            color: var(--secondary);
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.2em;
            display: block;
            margin-bottom: 1rem;
        }

        .validator-input-wrapper {
            position: relative;
            margin-bottom: 2rem;
        }

        .form-label-elegant {
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.1em;
            margin-bottom: 0.75rem;
            display: block;
        }

        .sophisticated-input {
            width: 100%;
            padding: 1.25rem 1.5rem;
            border: 1px solid var(--border);
            background: #fafafa;
            font-size: 1.1rem;
            transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
            border-radius: 2px;
            color: var(--primary);
        }

        .sophisticated-input:focus {
            outline: none;
            background: white;
            border-color: var(--primary);
            box-shadow: 0 10px 30px rgba(0, 33, 71, 0.05);
        }

        .btn-sophisticated {
            width: 100%;
            background: var(--primary);
            color: white;
            padding: 1.25rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            font-size: 0.9rem;
        }

        .btn-sophisticated:hover {
            background: var(--primary-light);
            transform: translateY(-2px);
            box-shadow: 0 15px 30px rgba(0, 33, 71, 0.2);
        }

        .result-panel {
            margin-top: 3rem;
            border-top: 1px solid var(--border);
            padding-top: 3rem;
            display: none;
            animation: fadeInSlideUp 0.6s ease forwards;
        }

        @keyframes fadeInSlideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .verified-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(61, 177, 102, 0.1);
            color: var(--secondary-dark);
            padding: 0.5rem 1rem;
            border-radius: 100px;
            font-weight: 600;
            font-size: 0.8rem;
            margin-bottom: 1.5rem;
        }

        .data-row {
            display: flex;
            justify-content: space-between;
            padding: 1rem 0;
            border-bottom: 1px solid #f9f9f9;
        }

        .data-label {
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        .data-value {
            font-weight: 500;
            color: var(--primary);
            text-align: right;
        }

        @media (max-width: 768px) {
            .sophisticated-card {
                padding: 2rem;
            }
            .minimal-header {
                padding: 100px 0 80px;
            }
        }
    </style>
</head>
<body>
    <div id="navbar"></div>

    <header class="minimal-header">
        <div class="container">
            <span class="validator-tag">// Verification Gateway</span>
            <h1 class="hero-main-title" style="font-size: 2.5rem; margin-bottom: 0;">Certificate Validation</h1>
            <p style="color: rgba(255,255,255,0.7); max-width: 600px; margin: 1rem auto 0; font-size: 0.95rem;">Authenticate professional credentials and verify student achievement through our secure database.</p>
        </div>
    </header>

    <div class="validator-container">
        <div class="container">
            <div class="sophisticated-card animate-fade-in">
                <div class="validator-title-box">
                    <h2 class="section-title-large" style="font-size: 1.75rem;">Digital Archive Check</h2>
                    <p style="color: var(--text-muted); font-size: 0.9rem;">Please enter the unique identifier found on the document.</p>
                </div>

                <div class="validator-input-wrapper">
                    <label class="form-label-elegant" for="certificateId">Input Reference ID</label>
                    <input type="text" id="certificateId" class="sophisticated-input" placeholder="IT-INFO-XXXX-XXXX">
                </div>

                <button onclick="validateCertificate()" class="btn-sophisticated">
                    Initialize Verification <i class="fas fa-arrow-right"></i>
                </button>

                <div id="validationResult" class="result-panel">
                    <!-- Results dynamically injected -->
                </div>
            </div>
        </div>
    </div>

    <section class="process-section" style="background: white; border-top: 1px solid var(--border);">
        <div class="container">
            <div class="section-header center">
                <h2 class="section-title-large">Security <span class="primary-text">Protocol</span></h2>
                <div class="section-divider"></div>
            </div>
            
            <div class="process-grid">
                <div class="process-item" style="border: none; background: transparent;">
                    <div class="process-number">01</div>
                    <h3 style="font-family: 'Poppins', sans-serif; font-size: 1rem; font-weight: 600;">Data Integrity</h3>
                    <p>All verification records are synchronized with our official academic archives for 100% accuracy.</p>
                </div>
                <div class="process-item" style="border: none; background: transparent;">
                    <div class="process-number">02</div>
                    <h3 style="font-family: 'Poppins', sans-serif; font-size: 1rem; font-weight: 600;">Secure Hash</h3>
                    <p>Unique identifiers ensure that each certificate remains tamper-proof and verifiable worldwide.</p>
                </div>
                <div class="process-item" style="border: none; background: transparent;">
                    <div class="process-number">03</div>
                    <h3 style="font-family: 'Poppins', sans-serif; font-size: 1rem; font-weight: 600;">Public Trust</h3>
                    <p>Maintaining institutional transparency by providing employers an instant way to confirm expertise.</p>
                </div>
            </div>
        </div>
    </section>

    <div id="footer"></div>

    <script src="js/components.js?v=2026.10"></script>
    <script src="js/validator.js"></script>
</body>
</html>