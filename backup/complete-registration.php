<?php
require_once 'backend/config/config.php';
require_once 'backend/config/database.php';
require_once 'backend/includes/functions.php';

$database = new Database();
$db = $database->connect();

$email = sanitize($_GET['email'] ?? '');
$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {
        // Find intern
        $stmt = $db->prepare("SELECT i.*, e.payment_status FROM interns i 
                              JOIN internship_enrollments e ON i.id = e.intern_id 
                              WHERE i.email = :email AND (e.payment_status = 'completed' OR e.enrollment_status = 'active')
                              LIMIT 1");
        $stmt->execute(['email' => $email]);
        $intern = $stmt->fetch();

        if ($intern) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $update = $db->prepare("UPDATE interns SET password = :pass WHERE id = :id");
            if ($update->execute(['pass' => $hashed_password, 'id' => $intern['id']])) {
                $success = true;

                // --- Generate Roll No Logic ---
                $enroll_stmt = $db->prepare("SELECT e.id, e.enrollment_date, e.roll_no, i.title 
                                             FROM internship_enrollments e 
                                             JOIN internships i ON e.internship_id = i.id 
                                             WHERE e.intern_id = :iid 
                                             ORDER BY e.enrollment_date DESC LIMIT 1");
                $enroll_stmt->execute(['iid' => $intern['id']]);
                $enrollment = $enroll_stmt->fetch();

                if ($enrollment && empty($enrollment['roll_no'])) {
                    $eid = $enrollment['id'];
                    $edate = strtotime($enrollment['enrollment_date']);
                    $seq = str_pad($eid, 3, '0', STR_PAD_LEFT);
                    $day = date('d', $edate);
                    $month = date('m', $edate);

                    // Simple logic for Course Code: First 3 letters of title
                    // e.g., "Web Development" -> "WEB", "Data Science" -> "DAT"
                    $course_code = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $enrollment['title']), 0, 3));

                    $roll_no = "IT-INFO-{$seq}-{$day}-{$month}-{$course_code}";

                    $upd_roll = $db->prepare("UPDATE internship_enrollments SET roll_no = :roll WHERE id = :eid");
                    $upd_roll->execute(['roll' => $roll_no, 'eid' => $eid]);
                } elseif ($enrollment) {
                    $roll_no = $enrollment['roll_no'];
                } else {
                    $roll_no = "PENDING";
                }
                // -----------------------------

            } else {
                $error = "Failed to update password. Please contact support.";
            }
        } else {
            $error = "No verified enrollment found for this email. If you paid by cash, please wait for admin approval.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Identity Configuration - ITINFO ACADEMY</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&family=Space+Mono:wght@400;700&display=swap"
        rel="stylesheet">
    <style>
        body {
            background: #f5f5f5;
            color: #333333;
            font-family: 'Poppins', sans-serif;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
        }

        .login-container {
            width: 100%;
            max-width: 450px;
            background: #ffffff;
            padding: 4rem;
            border-radius: 2px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.05);
            border-top: 5px solid #3db166;
        }

        .login-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .login-subtitle {
            display: block;
            font-size: 0.75rem;
            font-weight: 600;
            color: #3db166;
            text-transform: uppercase;
            letter-spacing: 0.15em;
            margin-bottom: 1rem;
        }

        .login-title {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            font-weight: 700;
            color: #002147;
            margin: 0;
        }

        .error-box {
            background: #fff5f5;
            color: #e53e3e;
            padding: 1rem;
            border-radius: 2px;
            margin-bottom: 2rem;
            font-size: 0.85rem;
            border: 1px solid #feb2b2;
            text-align: center;
        }

        .form-label {
            display: block;
            margin-bottom: 0.75rem;
            font-size: 0.75rem;
            color: #999999;
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.05em;
        }

        .form-control {
            width: 100%;
            padding: 1rem 1.25rem;
            border: 1px solid #eeeeee;
            border-radius: 2px;
            background: #fdfdfd;
            font-family: 'Poppins', sans-serif;
            font-size: 0.95rem;
            box-sizing: border-box;
            transition: 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: #3db166;
            background: #ffffff;
        }

        .form-control[readonly] {
            background: #f9f9f9;
            color: #666;
            cursor: not-allowed;
        }

        .btn-login {
            width: 100%;
            padding: 1.25rem;
            background: #002147;
            color: #ffffff;
            border: none;
            border-radius: 2px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            cursor: pointer;
            font-size: 0.9rem;
            margin-top: 1rem;
            transition: 0.3s;
        }

        .btn-login:hover {
            background: #001835;
            transform: translateY(-2px);
        }

        .login-footer {
            margin-top: 2.5rem;
            text-align: center;
            border-top: 1px solid #eeeeee;
            padding-top: 2rem;
        }

        .login-footer p {
            font-size: 0.85rem;
            color: #999999;
            margin-bottom: 1rem;
        }

        .login-footer a {
            color: #3db166;
            text-decoration: none;
            font-weight: 600;
        }

        .welcome-icon-box {
            width: 70px;
            height: 70px;
            background: rgba(61, 177, 102, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
        }

        .roll-no-box {
            background: #f8fcf9;
            border: 1px dashed #3db166;
            padding: 1.5rem;
            border-radius: 4px;
            margin: 2rem 0;
            text-align: center;
        }

        .roll-label {
            display: block;
            font-size: 0.7rem;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .roll-value {
            display: block;
            font-size: 1.5rem;
            font-weight: 700;
            color: #002147;
            font-family: 'Space Mono', monospace;
            letter-spacing: -0.5px;
        }
    </style>
</head>

<body>
    <div class="login-container">

        <?php if ($success): ?>
            <div style="text-align: center;">
                <div class="welcome-icon-box">
                    <i class="fas fa-check" style="font-size: 2rem; color: #3db166;"></i>
                </div>

                <div class="login-header">
                    <span class="login-subtitle">Registration Complete</span>
                    <h1 class="login-title">Welcome</h1>
                    <p style="color: #666; font-size: 0.9rem; margin-top: 1rem;">Your account has been successfully secured.
                    </p>
                </div>

                <div class="roll-no-box">
                    <span class="roll-label">Your Intern Roll No</span>
                    <span class="roll-value"><?php echo htmlspecialchars($roll_no); ?></span>
                </div>

                <a href="intern-login.php" class="btn-login" style="text-decoration: none; display: inline-block;">
                    Access Dashboard
                </a>
            </div>

        <?php else: ?>
            <div class="login-header">
                <span class="login-subtitle">Security Protocol</span>
                <h1 class="login-title">SETUP</h1>
            </div>

            <?php if ($error): ?>
                <div class="error-box">
                    <i class="fas fa-exclamation-circle" style="margin-right: 5px;"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label class="form-label">Authorized Email</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" class="form-control"
                        readonly required>
                </div>
                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label class="form-label">Create Password</label>
                    <input type="password" name="password" class="form-control" required minlength="6"
                        placeholder="••••••••">
                </div>
                <div class="form-group" style="margin-bottom: 2rem;">
                    <label class="form-label">Confirm Password</label>
                    <input type="password" name="confirm_password" class="form-control" required placeholder="••••••••">
                </div>
                <button type="submit" class="btn-login">Finalize Configuration</button>
            </form>
        <?php endif; ?>
    </div>
</body>

</html>