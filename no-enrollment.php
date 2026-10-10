<?php
require_once 'backend/config/config.php';
require_once 'backend/config/database.php';
require_once 'backend/includes/functions.php';

// Get error message from session
$error_message = $_SESSION['dashboard_error'] ?? 'No active internship found.';
unset($_SESSION['dashboard_error']);

// Get intern info if logged in
$intern_name = $_SESSION['intern_name'] ?? 'Student';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>No Active Enrollment - ITINFO ACADEMY</title>
    <link rel="stylesheet" href="css/styles.css?v=2026.10">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap"
        rel="stylesheet">
    <style>
        body {
            background: #f5f5f5;
            color: #333333;
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 2rem;
        }

        .error-container {
            width: 100%;
            max-width: 550px;
            background: #ffffff;
            padding: 4rem;
            border-radius: 2px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.05);
            text-align: center;
        }

        .error-icon {
            width: 80px;
            height: 80px;
            background: rgba(239, 68, 68, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem;
        }

        .error-icon i {
            font-size: 2.5rem;
            color: #ef4444;
        }

        .error-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.75rem;
            font-weight: 700;
            color: #002147;
            margin: 0 0 1rem;
        }

        .error-message {
            color: #666666;
            font-size: 0.95rem;
            line-height: 1.7;
            margin-bottom: 2rem;
        }

        .error-greeting {
            color: #3db166;
            font-weight: 600;
        }

        .action-buttons {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .btn-primary-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 1rem 2rem;
            background: #002147;
            color: #ffffff;
            border: none;
            border-radius: 2px;
            font-weight: 600;
            text-decoration: none;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-size: 0.85rem;
            transition: 0.3s;
        }

        .btn-primary-action:hover {
            background: #001835;
            transform: translateY(-2px);
        }

        .btn-secondary-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 1rem 2rem;
            background: transparent;
            color: #002147;
            border: 1px solid #e5e5e5;
            border-radius: 2px;
            font-weight: 600;
            text-decoration: none;
            font-size: 0.85rem;
            transition: 0.3s;
        }

        .btn-secondary-action:hover {
            border-color: #3db166;
            color: #3db166;
        }

        .support-info {
            margin-top: 2.5rem;
            padding-top: 2rem;
            border-top: 1px solid #eeeeee;
        }

        .support-info p {
            font-size: 0.85rem;
            color: #999999;
            margin-bottom: 0.5rem;
        }

        .support-info a {
            color: #3db166;
            text-decoration: none;
            font-weight: 600;
        }

        .support-info a:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <div class="error-container">
        <div class="error-icon">
            <i class="fas fa-graduation-cap"></i>
        </div>

        <h1 class="error-title">No Active Enrollment</h1>

        <p class="error-message">
            Hello <span class="error-greeting">
                <?php echo htmlspecialchars($intern_name); ?>
            </span>,<br><br>
            <?php echo htmlspecialchars($error_message); ?>
        </p>

        <div class="action-buttons">
            <a href="internship.php#internship-programs" class="btn-primary-action">
                <i class="fas fa-book-open"></i> Browse Internship Programs
            </a>
            <a href="index.php" class="btn-secondary-action">
                <i class="fas fa-home"></i> Return to Homepage
            </a>
        </div>

        <div class="support-info">
            <p>Need help? Contact our support team</p>
            <a href="mailto:support@itinfoacademy.in">
                <i class="fas fa-envelope" style="margin-right: 5px;"></i> support@itinfoacademy.in
            </a>
        </div>
    </div>
</body>

</html>