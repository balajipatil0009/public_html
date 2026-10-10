<?php
require_once 'backend/config/config.php';
require_once 'backend/config/database.php';
require_once 'backend/includes/functions.php';

// Already logged in? Redirect to dashboard
if (isset($_SESSION['intern_id'])) {
    header('Location: intern-dashboard.php');
    exit();
}

$message = '';
$message_type = '';
$show_form = true;
$request_status = null;
$can_reset = false;

$database = new Database();
$db = $database->connect();

// Check if table exists (handles case where migration hasn't been run yet)
$table_exists = false;
try {
    $check = $db->query("SELECT 1 FROM password_reset_requests LIMIT 1");
    $table_exists = ($check !== false);
} catch (PDOException $e) {
    $table_exists = false;
}

// Check if user is checking their request status
$check_email = $_GET['email'] ?? $_POST['email'] ?? '';

if (!empty($check_email) && $table_exists) {
    try {
        // Check if there's an approved request for this email
        $stmt = $db->prepare("SELECT * FROM password_reset_requests WHERE email = :email ORDER BY created_at DESC LIMIT 1");
        $stmt->execute(['email' => $check_email]);
        $existing_request = $stmt->fetch();

        if ($existing_request) {
            $request_status = $existing_request['status'];

            if ($request_status === 'approved') {
                $can_reset = true;
                $show_form = false;
            } elseif ($request_status === 'pending') {
                $message = 'Your password reset request is pending admin approval. Please check back later or contact support.';
                $message_type = 'info';
                $show_form = false;
            } elseif ($request_status === 'rejected') {
                $message = 'Your previous request was rejected. Please contact support for assistance.';
                $message_type = 'error';
                $show_form = true; // Allow them to submit a new request
            } elseif ($request_status === 'used') {
                $message = 'Your password has already been reset. If you need to reset again, please submit a new request.';
                $message_type = 'info';
                $show_form = true;
            }
        }
    } catch (PDOException $e) {
        // Table doesn't exist or other DB error - show form normally
    }
}


// Handle new password submission (when approved)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_password']) && $table_exists) {
    $email = sanitize($_POST['email']);
    $password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    try {
        // Verify there's an approved request
        $stmt = $db->prepare("SELECT id FROM password_reset_requests WHERE email = :email AND status = 'approved' LIMIT 1");
        $stmt->execute(['email' => $email]);
        $approved = $stmt->fetch();

        if (!$approved) {
            $message = 'No approved reset request found. Please check your email or submit a new request.';
            $message_type = 'error';
            $show_form = true;
            $can_reset = false;
        } elseif (strlen($password) < 6) {
            $message = 'Password must be at least 6 characters long.';
            $message_type = 'error';
            $can_reset = true;
            $show_form = false;
        } elseif ($password !== $confirm_password) {
            $message = 'Passwords do not match.';
            $message_type = 'error';
            $can_reset = true;
            $show_form = false;
        } else {
            // Update password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE interns SET password = :password WHERE email = :email");
            $stmt->execute([
                'password' => $hashed_password,
                'email' => $email
            ]);

            // Mark request as used
            $stmt = $db->prepare("UPDATE password_reset_requests SET status = 'used', used_at = NOW() WHERE email = :email AND status = 'approved'");
            $stmt->execute(['email' => $email]);

            $message = 'Password updated successfully! You can now log in with your new password.';
            $message_type = 'success';
            $can_reset = false;
            $show_form = false;
        }
    } catch (PDOException $e) {
        $message = 'An error occurred. Please try again later.';
        $message_type = 'error';
    }
}

// Handle new request submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_reset']) && !isset($_POST['new_password'])) {
    $email = sanitize($_POST['email']);

    if (!$table_exists) {
        $message = 'Password reset feature is currently being set up. Please contact support or try again later.';
        $message_type = 'error';
    } elseif (!validateEmail($email)) {
        $message = 'Please enter a valid email address.';
        $message_type = 'error';
    } else {
        try {
            // Check if intern exists
            $stmt = $db->prepare("SELECT id, full_name FROM interns WHERE email = :email AND is_active = 1");
            $stmt->execute(['email' => $email]);
            $intern = $stmt->fetch();

            if ($intern) {
                // Check for existing pending request
                $stmt = $db->prepare("SELECT id FROM password_reset_requests WHERE email = :email AND status = 'pending'");
                $stmt->execute(['email' => $email]);
                $pending = $stmt->fetch();

                if ($pending) {
                    $message = 'You already have a pending request. Please wait for admin approval.';
                    $message_type = 'info';
                    $show_form = false;
                } else {
                    // Create new request
                    $stmt = $db->prepare("INSERT INTO password_reset_requests (intern_id, email, full_name, status) VALUES (:intern_id, :email, :full_name, 'pending')");
                    $stmt->execute([
                        'intern_id' => $intern['id'],
                        'email' => $email,
                        'full_name' => $intern['full_name']
                    ]);

                    $message = 'Password reset request submitted! Please check back later for approval.';
                    $message_type = 'success';
                    $show_form = false;
                }
            } else {
                // Security: Don't reveal if email exists or not
                $message = 'If an account exists with this email, your request has been submitted.';
                $message_type = 'success';
                $show_form = false;
            }
        } catch (PDOException $e) {
            $message = 'Password reset feature is being set up. Please contact support.';
            $message_type = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - ITINFO ACADEMY</title>
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
            padding: 1rem;
        }

        .forgot-container {
            width: 100%;
            max-width: 450px;
            background: #ffffff;
            padding: 3rem;
            border-radius: 2px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.05);
            border-top: 5px solid #3db166;
        }

        .forgot-header {
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .forgot-subtitle {
            display: block;
            font-size: 0.75rem;
            font-weight: 600;
            color: #3db166;
            text-transform: uppercase;
            letter-spacing: 0.15em;
            margin-bottom: 0.75rem;
        }

        .forgot-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.75rem;
            font-weight: 700;
            color: #002147;
            margin: 0;
        }

        .forgot-description {
            color: #666666;
            font-size: 0.875rem;
            text-align: center;
            margin-bottom: 1.5rem;
            line-height: 1.6;
        }

        .message-box {
            padding: 1rem;
            border-radius: 2px;
            margin-bottom: 1.5rem;
            font-size: 0.85rem;
            text-align: center;
        }

        .message-box.success {
            background: #f0fdf4;
            color: #16a34a;
            border: 1px solid #bbf7d0;
        }

        .message-box.error {
            background: #fff5f5;
            color: #e53e3e;
            border: 1px solid #feb2b2;
        }

        .message-box.info {
            background: #eff6ff;
            color: #2563eb;
            border: 1px solid #bfdbfe;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-size: 0.75rem;
            color: #999999;
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.05em;
        }

        .form-control {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 1px solid #eeeeee;
            border-radius: 2px;
            background: #fdfdfd;
            font-family: 'Poppins', sans-serif;
            font-size: 0.9rem;
            box-sizing: border-box;
            transition: 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: #3db166;
            background: #ffffff;
        }

        .btn-submit {
            width: 100%;
            padding: 1rem;
            background: #002147;
            color: #ffffff;
            border: none;
            border-radius: 2px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            cursor: pointer;
            font-size: 0.85rem;
            margin-top: 0.75rem;
            transition: 0.3s;
        }

        .btn-submit:hover {
            background: #001835;
            transform: translateY(-2px);
        }

        .btn-check {
            background: #3db166;
        }

        .btn-check:hover {
            background: #2d9050;
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.85rem;
            color: #999999;
            text-decoration: none;
            transition: 0.3s;
        }

        .back-link:hover {
            color: #002147;
        }

        .password-hint {
            font-size: 0.7rem;
            color: #999999;
            margin-top: 0.375rem;
        }

        .check-status-link {
            display: block;
            text-align: center;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #eee;
            font-size: 0.85rem;
            color: #3db166;
            text-decoration: none;
        }

        .check-status-link:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <div class="forgot-container">
        <div class="forgot-header">
            <span class="forgot-subtitle">Password Recovery</span>
            <h1 class="forgot-title">
                <?php if ($can_reset): ?>
                    Reset Password
                <?php else: ?>
                    Forgot Password
                <?php endif; ?>
            </h1>
        </div>

        <?php if (!$can_reset && $show_form): ?>
            <p class="forgot-description">
                Enter your registered email address. Once admin approves your request, you can set a new password.
            </p>
        <?php endif; ?>

        <?php if ($message): ?>
            <div class="message-box <?php echo $message_type; ?>">
                <?php if ($message_type === 'success'): ?>
                    <i class="fas fa-check-circle" style="margin-right: 5px;"></i>
                <?php elseif ($message_type === 'info'): ?>
                    <i class="fas fa-info-circle" style="margin-right: 5px;"></i>
                <?php else: ?>
                    <i class="fas fa-exclamation-circle" style="margin-right: 5px;"></i>
                <?php endif; ?>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if ($can_reset): ?>
            <!-- Password Reset Form (when approved) -->
            <form method="POST">
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($check_email); ?>">
                <div class="form-group" style="margin-bottom: 1rem;">
                    <label class="form-label">New Password</label>
                    <input type="password" name="new_password" class="form-control" required placeholder="â€¢â€¢â€¢â€¢â€¢â€¢â€¢â€¢"
                        minlength="6">
                    <p class="password-hint">Minimum 6 characters</p>
                </div>
                <div class="form-group" style="margin-bottom: 1rem;">
                    <label class="form-label">Confirm Password</label>
                    <input type="password" name="confirm_password" class="form-control" required placeholder="â€¢â€¢â€¢â€¢â€¢â€¢â€¢â€¢">
                </div>
                <button type="submit" class="btn-submit btn-check">Update Password</button>
            </form>
        <?php elseif ($show_form): ?>
            <!-- Request Form -->
            <form method="POST">
                <input type="hidden" name="request_reset" value="1">
                <div class="form-group" style="margin-bottom: 1rem;">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-control" required placeholder="name@email.com"
                        value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
                <button type="submit" class="btn-submit">Submit Reset Request</button>
            </form>

            <a href="?email=" class="check-status-link"
                onclick="event.preventDefault(); document.getElementById('check-form').style.display='block'; this.style.display='none';">
                Already submitted a request? Check status â†’
            </a>

            <form id="check-form" method="GET" style="display: none; margin-top: 1rem;">
                <div class="form-group" style="margin-bottom: 0.75rem;">
                    <label class="form-label">Check Request Status</label>
                    <input type="email" name="email" class="form-control" required placeholder="Enter your email">
                </div>
                <button type="submit" class="btn-submit btn-check">Check Status</button>
            </form>
        <?php else: ?>
            <!-- Status check or completed -->
            <?php if ($request_status === 'pending'): ?>
                <p style="text-align: center; color: #666; font-size: 0.9rem;">
                    <i class="fas fa-clock" style="font-size: 2rem; color: #f59e0b; display: block; margin-bottom: 1rem;"></i>
                    Your request is awaiting admin approval. Please refresh this page or check back later.
                </p>
                <form method="GET" style="margin-top: 1rem;">
                    <input type="hidden" name="email" value="<?php echo htmlspecialchars($check_email); ?>">
                    <button type="submit" class="btn-submit btn-check">
                        <i class="fas fa-sync-alt" style="margin-right: 5px;"></i> Check Again
                    </button>
                </form>
            <?php endif; ?>
        <?php endif; ?>

        <a href="intern-login.php" class="back-link">
            <i class="fas fa-chevron-left" style="margin-right: 5px; font-size: 0.75rem;"></i> Back to Login
        </a>
    </div>
</body>

</html>