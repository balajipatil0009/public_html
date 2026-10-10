<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Dashboard'; ?> - Admin Panel</title>
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <div class="admin-wrapper">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2><?php echo SITE_NAME; ?></h2>
                <p>Admin Panel</p>
            </div>

            <nav class="sidebar-nav">
                <a href="index.php"
                    class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-line"></i> Dashboard
                </a>
                <a href="hero-slides.php"
                    class="nav-item <?php echo in_array(basename($_SERVER['PHP_SELF']), ['hero-slides.php', 'hero-slide-form.php']) ? 'active' : ''; ?>">
                    <i class="fas fa-images"></i> Hero Slider
                </a>
                <a href="testimonials.php"
                    class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'testimonials.php' ? 'active' : ''; ?>">
                    <i class="fas fa-comment-dots"></i> Testimonials
                </a>
                <a href="courses.php"
                    class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'courses.php' ? 'active' : ''; ?>">
                    <i class="fas fa-book-open"></i> Courses
                </a>
                <a href="jobs.php"
                    class="nav-item <?php echo in_array(basename($_SERVER['PHP_SELF']), ['jobs.php']) ? 'active' : ''; ?>">
                    <i class="fas fa-briefcase"></i> Job Board
                </a>
                <a href="gallery.php"
                    class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'gallery.php' ? 'active' : ''; ?>">
                    <i class="fas fa-photo-video"></i> Gallery
                </a>
                <a href="about.php"
                    class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'about.php' ? 'active' : ''; ?>">
                    <i class="fas fa-info-circle"></i> About Us
                </a>
                <a href="inquiries.php"
                    class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'inquiries.php' ? 'active' : ''; ?>">
                    <i class="fas fa-envelope-open-text"></i> Inquiries
                </a>
                <a href="internships.php"
                    class="nav-item <?php echo in_array(basename($_SERVER['PHP_SELF']), ['internships.php', 'internship-form.php', 'internship-videos.php', 'internship-quizzes.php', 'quiz-question-form.php']) ? 'active' : ''; ?>">
                    <i class="fas fa-graduation-cap"></i> Internships
                </a>
                <a href="intern-enrollments.php"
                    class="nav-item <?php echo in_array(basename($_SERVER['PHP_SELF']), ['intern-enrollments.php', 'intern-progress.php']) ? 'active' : ''; ?>">
                    <i class="fas fa-user-graduate"></i> Intern Enrollments
                </a>
                <a href="password-resets.php"
                    class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'password-resets.php' ? 'active' : ''; ?>">
                    <i class="fas fa-key"></i> Password Resets
                </a>
                <a href="applications.php"
                    class="nav-item <?php echo in_array(basename($_SERVER['PHP_SELF']), ['applications.php', 'application-details.php']) ? 'active' : ''; ?>">
                    <i class="fas fa-file-alt"></i> Applications
                </a>
                <a href="course-students.php"
                    class="nav-item <?php echo in_array(basename($_SERVER['PHP_SELF']), ['course-students.php', 'course-student-form.php']) ? 'active' : ''; ?>">
                    <i class="fas fa-user-plus"></i> Course Students
                </a>
                <a href="migrate.php"
                    class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'migrate.php' ? 'active' : ''; ?>">
                    <i class="fas fa-rocket"></i> Migration
                </a>
                <a href="settings.php"
                    class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
                    <i class="fas fa-cog"></i> Settings
                </a>
            </nav>

            <div class="sidebar-footer">
                <div class="user-info">
                    <strong><?php echo $_SESSION['admin_name']; ?></strong>
                    <small><?php echo ucfirst($_SESSION['admin_role']); ?></small>
                </div>
                <a href="logout.php" class="btn-logout">Logout</a>
            </div>
        </aside>

        <main class="main-content">
            <header class="content-header">
                <h1><?php echo $pageTitle ?? 'Dashboard'; ?></h1>
                <div class="header-actions">
                    <span class="welcome-text">Welcome, <?php echo $_SESSION['admin_name']; ?></span>
                </div>
            </header>

            <div class="content-body">
                <?php
                $successMsg = getSuccessMessage();
                $errorMsg = getErrorMessage();
                if ($successMsg):
                    ?>
                    <div class="alert alert-success"><?php echo $successMsg; ?></div>
                <?php endif; ?>

                <?php if ($errorMsg): ?>
                    <div class="alert alert-error"><?php echo $errorMsg; ?></div>
                <?php endif; ?>