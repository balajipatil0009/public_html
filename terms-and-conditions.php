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

$siteName = $settings['site_name'] ?? 'ITINFO ACADEMY';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms and Conditions - <?php echo htmlspecialchars($siteName); ?></title>
    <link rel="icon" type="image/x-icon" href="public/favicon.ico">
    <link rel="stylesheet" href="css/styles.css?v=2026.10">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .terms-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 3rem 2rem;
            background: white;
        }

        .terms-header {
            text-align: center;
            margin-bottom: 3rem;
            padding-bottom: 2rem;
            border-bottom: 3px solid #3db166;
        }

        .terms-header h1 {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            color: #002147;
            margin-bottom: 1rem;
        }

        .terms-header p {
            color: #666;
            font-size: 1.1rem;
            line-height: 1.6;
        }

        .terms-content {
            color: #333;
            line-height: 1.8;
        }

        .terms-section {
            margin-bottom: 2.5rem;
        }

        .terms-section h2 {
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            color: #002147;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .section-number {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 35px;
            height: 35px;
            background: #3db166;
            color: white;
            border-radius: 50%;
            font-size: 1rem;
            font-weight: 700;
            flex-shrink: 0;
        }

        .terms-section p {
            margin-bottom: 1rem;
            font-size: 1rem;
        }

        .terms-section ul {
            margin: 1rem 0;
            padding-left: 2rem;
        }

        .terms-section li {
            margin-bottom: 0.75rem;
        }

        .highlight-box {
            background: #e8f5e9;
            border-left: 4px solid #3db166;
            padding: 1.5rem;
            margin: 1.5rem 0;
            border-radius: 4px;
        }

        .highlight-box h3 {
            color: #002147;
            margin-bottom: 1rem;
            font-size: 1.2rem;
        }

        .highlight-box ul {
            margin: 0;
            padding-left: 1.5rem;
        }

        .warning-box {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 1.5rem;
            margin: 1.5rem 0;
            border-radius: 4px;
        }

        .info-box {
            background: #d1ecf1;
            border-left: 4px solid #2196f3;
            padding: 1.5rem;
            margin: 1.5rem 0;
            border-radius: 4px;
        }

        .terms-footer {
            margin-top: 3rem;
            padding-top: 2rem;
            border-top: 2px solid #eee;
            text-align: center;
        }

        .terms-footer p {
            color: #666;
            margin-bottom: 1rem;
        }

        .contact-info {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 4px;
            margin-top: 1.5rem;
        }

        .contact-info strong {
            color: #002147;
        }

        .back-button {
            display: inline-block;
            background: #3db166;
            color: white;
            padding: 0.75rem 2rem;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 600;
            margin-top: 1rem;
            transition: 0.3s;
        }

        .back-button:hover {
            background: #2d9050;
        }

        @media print {
            .back-button {
                display: none;
            }
        }
    </style>
</head>

<body>
    <div id="navbar"></div>

    <div class="terms-container">
        <div class="terms-header">
            <h1><?php echo htmlspecialchars($siteName); ?> Internship<br>Terms and Conditions</h1>
            <p>Welcome to the <?php echo htmlspecialchars($siteName); ?> Internship Program. This document explains the
                rules and guidelines for participating in our internship. By joining, you agree to follow these terms.
            </p>
        </div>

        <div class="terms-content">
            <!-- Section 1 -->
            <div class="terms-section">
                <h2><span class="section-number">1</span> Acceptance of Offer</h2>
                <p>If you receive an internship offer, you must confirm it within the given time. If you don't reply, we
                    will assume that you have accepted the offer.</p>
            </div>

            <!-- Section 2 -->
            <div class="terms-section">
                <h2><span class="section-number">2</span> Duration of Internship</h2>
                <p>The time period of your internship will be mentioned in your offer mail.</p>
            </div>

            <!-- Section 3 -->
            <div class="terms-section">
                <h2><span class="section-number">3</span> Responsibilities</h2>
                <p>You are expected to complete all tasks and projects given during the internship.</p>
            </div>

            <!-- Section 4 -->
            <div class="terms-section">
                <h2><span class="section-number">4</span> Code of Conduct</h2>
                <p>You must behave professionally, be respectful, and follow
                    <?php echo htmlspecialchars($siteName); ?>'s rules and values.</p>
            </div>

            <!-- Section 5 -->
            <div class="terms-section">
                <h2><span class="section-number">5</span> Confidentiality</h2>
                <p>Do not share any private or confidential information outside the organization without permission.</p>
            </div>

            <!-- Section 6 -->
            <div class="terms-section">
                <h2><span class="section-number">6</span> Performance Review</h2>
                <p>We will review your work based on your effort, quality, and consistency. Feedback may be given to
                    help you improve.</p>
            </div>

            <!-- Section 7 -->
            <div class="terms-section">
                <h2><span class="section-number">7</span> Ownership of Work</h2>
                <p>All work and ideas created during the internship belong to
                    <?php echo htmlspecialchars($siteName); ?>.</p>
            </div>

            <!-- Section 8 -->
            <div class="terms-section">
                <h2><span class="section-number">8</span> Attendance and Timeliness</h2>
                <p>You must follow the schedule, attend sessions regularly, and submit tasks on time.</p>
            </div>

            <!-- Section 9 -->
            <div class="terms-section">
                <h2><span class="section-number">9</span> Communication</h2>
                <p>You should stay in regular contact with your mentor or supervisor and update your progress.</p>
            </div>

            <!-- Section 10 -->
            <div class="terms-section">
                <h2><span class="section-number">10</span> Feedback and Learning</h2>
                <p>We will provide feedback to help you grow. Be open to learning and making improvements.</p>
            </div>

            <!-- Section 11 -->
            <div class="terms-section">
                <h2><span class="section-number">11</span> Internship Termination</h2>
                <p>We may end your internship if you break the rules or do not participate actively.</p>
            </div>

            <!-- Section 12 -->
            <div class="terms-section">
                <h2><span class="section-number">12</span> Certificate and Task Submission</h2>
                <p>If you complete all tasks, your internship will be considered complete. You have two options for how
                    to submit your work:</p>

                <div class="highlight-box">
                    <h3><i class="fas fa-certificate"></i> Option 1: You want a certificate</h3>
                    <ul>
                        <li>Submit your work using the official form.</li>
                        <li>You will need to pay a small certificate fee when you submit.</li>
                        <li>You will get a digital certificate within 10 working days after review.</li>
                    </ul>
                </div>

                <div class="info-box">
                    <h3><i class="fas fa-envelope"></i> Option 2: You do not want a certificate</h3>
                    <ul>
                        <li>Submit your work by email to <strong>validatoritinfoacademy@gmail.com</strong>.</li>
                        <li>You will receive a confirmation email saying your internship is complete.</li>
                        <li>No fee is required, and no certificate will be given.</li>
                        <li>No other documents or support will be provided after submission.</li>
                    </ul>
                </div>
            </div>

            <!-- Section 13 -->
            <div class="terms-section">
                <h2><span class="section-number">13</span> Stipend or Payment</h2>
                <div class="warning-box">
                    <p><strong><i class="fas fa-info-circle"></i> Important:</strong> This internship is unpaid. You
                        will not receive any salary or stipend. It is only for learning and gaining experience.</p>
                </div>
            </div>

            <!-- Section 14 -->
            <div class="terms-section">
                <h2><span class="section-number">14</span> No Job Guarantee</h2>
                <p>Completing the internship does not mean you will be offered a job or future work with
                    <?php echo htmlspecialchars($siteName); ?> or any other company.</p>
            </div>

            <!-- Section 15 -->
            <div class="terms-section">
                <h2><span class="section-number">15</span> Documentation Charges</h2>
                <p>Participation in the internship program is free for all, with no registration cost. Interns may
                    choose to avail additional services like certificates for a nominal fee. If you do not want a
                    certificate, you will still get a free confirmation email after submitting your work.</p>
            </div>

            <!-- Section 16 -->
            <div class="terms-section">
                <h2><span class="section-number">16</span> ISO Certification</h2>
                <p><?php echo htmlspecialchars($siteName); ?> is ISO 9001:2015 certified by TSN Certification Private
                    Limited. This means we follow quality management standards in our work areas like education,
                    training, and IT services.</p>
            </div>

            <!-- Section 17 -->
            <div class="terms-section">
                <h2><span class="section-number">17</span> Legal Compliance</h2>
                <ul>
                    <li><strong>MSME Registration:</strong> We are registered as an MSME under Indian law.</li>
                    <li><strong>Disputes:</strong> Any legal issues will be handled under Indian law and in Indian
                        courts.</li>
                    <li><strong>Following Laws:</strong> All interns must follow laws related to internships, data
                        privacy, intellectual property, and confidentiality.</li>
                </ul>

                <div class="contact-info">
                    <p><strong>For legal or compliance queries, contact:</strong> validatoritinfoacademy@gmail.com</p>
                </div>
            </div>

            <!-- Section 18 -->
            <div class="terms-section">
                <h2><span class="section-number">18</span> Independent Ownership and No Third-Party Association</h2>
                <p>The internship program offered by <?php echo htmlspecialchars($siteName); ?> is an independently
                    owned and operated initiative. It is designed, managed, and executed solely by the internal team of
                    <?php echo htmlspecialchars($siteName); ?>.</p>

                <p>The program is <strong>not affiliated with, endorsed by, or conducted in partnership with any
                        university, government body, college, corporation, or third-party organization</strong> unless
                    explicitly stated otherwise in writing.</p>

                <p>Any learning materials, certifications, and communications are solely issued by
                    <?php echo htmlspecialchars($siteName); ?> under its own brand and quality framework.</p>

                <p>Interns are advised not to assume any formal association with external educational institutions,
                    councils or certifying bodies unless officially declared by
                    <?php echo htmlspecialchars($siteName); ?>.</p>
            </div>

            <!-- Agreement Section -->
            <div class="terms-section">
                <h2><span class="section-number"><i class="fas fa-handshake"></i></span> Agreement</h2>
                <p>By applying to and participating in this internship, you confirm that:</p>
                <ul>
                    <li>You have read and understood all the terms.</li>
                    <li>You agree to follow these rules during your internship.</li>
                    <li>You understand the certificate is optional and paid, while participation is free.</li>
                </ul>
            </div>
        </div>

        <div class="terms-footer">
            <p><strong>Last Updated:</strong> January 15, 2026</p>
            <p>Â© <?php echo date('Y'); ?> <?php echo htmlspecialchars($siteName); ?>. All rights reserved.</p>
            <a href="apply.php" class="back-button">
                <i class="fas fa-arrow-left"></i> Back to Application Form
            </a>
        </div>
    </div>

    <div id="footer"></div>

    <script src="js/components.js?v=2026.10"></script>
    <script src="js/main.js"></script>
</body>

</html>