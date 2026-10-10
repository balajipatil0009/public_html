<?php
require_once 'backend/config/config.php';
require_once 'backend/config/database.php';
require_once 'backend/includes/functions.php';

if (isset($_SESSION['intern_id'])) {
    header('Location: intern-dashboard.php');
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];

    $database = new Database();
    $db = $database->connect();

    $stmt = $db->prepare("SELECT * FROM interns WHERE email = :email");
    $stmt->execute(['email' => $email]);
    $intern = $stmt->fetch();

    if ($intern && password_verify($password, $intern['password'])) {
        if ($intern['is_active']) {
            $_SESSION['intern_id'] = $intern['id'];
            $_SESSION['intern_name'] = $intern['full_name'];

            // Update last login
            $db->prepare("UPDATE interns SET last_login = NOW() WHERE id = :id")->execute(['id' => $intern['id']]);

            header('Location: intern-dashboard.php');
            exit();
        } else {
            $error = 'Access restricted. Please contact administrative support.';
        }
    } else {
        $error = 'Authentication failed. Invalid credentials.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Portal Login - ITINFO ACADEMY</title>
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

        .back-link {
            font-size: 0.85rem;
            color: #999999;
            text-decoration: none;
            transition: 0.3s;
        }

        .back-link:hover {
            color: #002147;
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="login-header">
            <span class="login-subtitle">Student Portal Login</span>
            <h1 class="login-title">LOGIN</h1>
        </div>

        <?php if ($error): ?>
            <div class="error-box">
                <i class="fas fa-exclamation-circle" style="margin-right: 5px;"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control" required placeholder="name@email.com">
            </div>
            <div class="form-group" style="margin-bottom: 2rem;">
                <label class="form-label">Access Password</label>
                <input type="password" name="password" class="form-control" required placeholder="â€¢â€¢â€¢â€¢â€¢â€¢â€¢â€¢">
            </div>
            <button type="submit" class="btn-login">Submit Authorization</button>
        </form>

        <div class="login-footer">
            <p>
                New student? <a href="internship.php#internship-programs">Enroll in Program</a>
            </p>
            <p style="margin-bottom: 1rem;">
                <a href="forgot-password.php" style="color: #999999; text-decoration: none; font-size: 0.85rem;">Forgot
                    your password?</a>
            </p>
            <a href="index.php" class="back-link">
                <i class="fas fa-chevron-left" style="margin-right: 5px; font-size: 0.75rem;"></i> Return to Academy
                Homepage
            </a>
        </div>
    </div>
</body>

</html>

</html>