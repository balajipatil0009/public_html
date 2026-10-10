<?php
require_once 'backend/config/config.php';
require_once 'backend/config/database.php';

$database = new Database();
$db = $database->connect();

$enrollment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$roll_no_req = isset($_GET['roll']) ? $_GET['roll'] : null;

if (!$enrollment_id && !$roll_no_req) {
    die("Invalid access.");
}

$intern_id_session = isset($_SESSION['intern_id']) ? $_SESSION['intern_id'] : (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null);
$is_admin = isset($_SESSION['admin_id']) || (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');

// Security Check
if (!$is_admin && !$roll_no_req) {
    if (!$intern_id_session) {
        die("Access denied. Please login.");
    }
    $auth_stmt = $db->prepare("SELECT intern_id FROM internship_enrollments WHERE id = :id");
    $auth_stmt->execute(['id' => $enrollment_id]);
    $auth_data = $auth_stmt->fetch();
    if (!$auth_data || $auth_data['intern_id'] != $intern_id_session) {
        die("Access denied.");
    }
}


// Fetch Detailed Enrollment Data
$data = null;

// Try Internships first
$query = "SELECT i.full_name, p.title as internship_title, p.course_code, p.duration, 
          e.enrollment_date, e.roll_no, e.id as enroll_id, e.certificate_issued, e.certificate_date,
          'internship' as type
          FROM interns i
          JOIN internship_enrollments e ON e.intern_id = i.id
          JOIN internships p ON p.id = e.internship_id";

if ($roll_no_req) {
    $stmt = $db->prepare($query . " WHERE e.roll_no = :roll LIMIT 1");
    $stmt->execute(['roll' => $roll_no_req]);
} else {
    $stmt = $db->prepare($query . " WHERE e.id = :id LIMIT 1");
    $stmt->execute(['id' => $enrollment_id]);
}
$data = $stmt->fetch();

// If not found, try Course Students
if (!$data && $roll_no_req) {
    $course_query = "SELECT cs.full_name, 
                     CASE WHEN cs.course_id = -1 THEN cs.other_course_name ELSE c.title END as internship_title,
                     'CRS' as course_code, 
                     CASE WHEN cs.course_id = -1 THEN '1 Month' ELSE c.duration END as duration, 
                     cs.enrollment_date, cs.roll_no, cs.id as enroll_id, 1 as certificate_issued, cs.created_at as certificate_date,
                     'course' as type
                     FROM course_students cs
                     LEFT JOIN courses c ON c.id = cs.course_id
                     WHERE cs.roll_no = :roll LIMIT 1";
    $stmt = $db->prepare($course_query);
    $stmt->execute(['roll' => $roll_no_req]);
    $data = $stmt->fetch();
} else if (!$data && $enrollment_id) {
    $course_query = "SELECT cs.full_name, 
                     CASE WHEN cs.course_id = -1 THEN cs.other_course_name ELSE c.title END as internship_title,
                     'CRS' as course_code, 
                     CASE WHEN cs.course_id = -1 THEN '1 Month' ELSE c.duration END as duration, 
                     cs.enrollment_date, cs.roll_no, cs.id as enroll_id, 1 as certificate_issued, cs.created_at as certificate_date,
                     'course' as type
                     FROM course_students cs
                     LEFT JOIN courses c ON c.id = cs.course_id
                     WHERE cs.id = :id LIMIT 1";
    $stmt = $db->prepare($course_query);
    $stmt->execute(['id' => $enrollment_id]);
    $data = $stmt->fetch();
}


if (!$data) {
    die("Enrollment not found.");
}

// Completion Check: Only allow if duration has passed
$duration_passed = false;
$duration_str = strtolower($data['duration'] ?? '1 month');
$start_date = new DateTime($data['enrollment_date']);
$expected_end_date = clone $start_date;

if (strpos($duration_str, 'week') !== false) {
    $weeks = (int) filter_var($duration_str, FILTER_SANITIZE_NUMBER_INT) ?: 1;
    $expected_end_date->modify("+{$weeks} weeks");
} elseif (strpos($duration_str, 'month') !== false) {
    $months = (int) filter_var($duration_str, FILTER_SANITIZE_NUMBER_INT) ?: 1;
    $expected_end_date->modify("+{$months} months");
} elseif (strpos($duration_str, 'day') !== false) {
    $days = (int) filter_var($duration_str, FILTER_SANITIZE_NUMBER_INT) ?: 1;
    $expected_end_date->modify("+{$days} days");
} else {
    $expected_end_date->modify("+1 month");
}

$now = new DateTime();
if ($now >= $expected_end_date) {
    $duration_passed = true;
}

if (!$duration_passed) {
    header('Content-Type: text/html');
    die("<div style='font-family: Arial; text-align: center; margin-top: 50px;'>
            <h1 style='color: #ef4444;'>Course in Progress</h1>
            <p>Your course is scheduled to complete on <b>" . $expected_end_date->format('d M Y') . "</b>.</p>
            <p>The certificate will be available for download once the course duration is officially over.</p>
            <br><a href='validator.php' style='color: #002147;'>Back to Validator</a>
         </div>");
}

$intern_name = strtoupper($data['full_name']);
$internship_title = strtoupper($data['internship_title']);
$internship_title_in_desc = ucwords(strtolower($data['internship_title']));
$enrollment_date = $data['enrollment_date'];
$current_year = date('Y');

if ($data['roll_no']) {
    $cert_id = $data['roll_no'];
    $issue_date = date('d.m.Y', strtotime($data['certificate_date'] ?? $data['enrollment_date']));
} else {
    // GENERATE UNIQUE ROLL NO (Usually for legacy interns)
    $seq_id = str_pad($data['enroll_id'], 3, '0', STR_PAD_LEFT);
    $enroll_ts = strtotime($data['enrollment_date']);
    $day = date('d', $enroll_ts);
    $month = str_pad(date('m', $enroll_ts), 2, '0', STR_PAD_LEFT);
    $course_code = $data['course_code'] ?? 'CRS';

    $cert_id = "ITINFO-{$seq_id}-{$day}-{$month}-{$course_code}";
    $issue_date = date('d.m.Y');

    // Only update internship_enrollments if it's an internship type
    if ($data['type'] === 'internship') {
        $update_stmt = $db->prepare("UPDATE internship_enrollments SET 
                                     roll_no = :roll, 
                                     certificate_issued = 1, 
                                     certificate_date = NOW() 
                                     WHERE id = :id");
        $update_stmt->execute(['roll' => $cert_id, 'id' => $data['enroll_id']]);
    }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate -
        <?php echo $intern_name; ?>
    </title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        @keyframes cert-spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        @import url('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css');
        @import url('https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700&family=Noto+Serif:wght@400;700&family=Open+Sans:wght@400;600;700&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-print-color-adjust: exact;
        }


        body {
            font-family: 'Open Sans', sans-serif;
            background: #f0f0f0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .certificate-container {
            width: 1000px;
            height: 700px;
            /* A standard simplified aspect ratio, close to A4 landscape */
            background: white;
            position: relative;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            color: #333;
            display: flex;
        }

        /* Background Graphics matching the image: Right (Navy), Middle (Red), Left (Grey) */
        .bg-graphic-right {
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            width: 320px;
            background: #0d1b3e;
            /* Dark Blue */
            clip-path: polygon(13px 0%, 72% 0%, 113% 100%, 32% 101%);
            z-index: 3;
        }

        .bg-graphic-accent {
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            width: 380px;
            background: #a61c31;
            /* Red Accent */
            clip-path: polygon(254px 0, 100% 0, 100% 100%, 0% 100%);
            z-index: 2;
        }

        .bg-graphic-grey {
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            width: 420px;
            background: #9fa1a4;
            /* Grey Accent - Darker as per image */
            clip-path: polygon(268px 0, 100% 0, 100% 100%, 0% 100%);
            z-index: 1;
        }

        /* Content Area */
        .content {
            flex: 1;
            padding: 40px;
            position: relative;
            z-index: 2;
            display: flex;
            flex-direction: column;
            width: 75%;
            /* Leave room for right graphics */
        }

        .header-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 30px;
        }

        .logo-box {
            width: 180px;
            height: 120px;
            background: url('../public/langImages/Logoreal.jpg') no-repeat top left;
            background-size: contain;
        }

        .cert-no-box {
            text-align: right;
            font-size: 14px;
            font-weight: 700;
            color: #000;
            padding-right: 269px;
            margin-top: -20px;
        }

        /* Title Section */
        .title-section {
            text-align: center;
            margin-bottom: -1px;
            padding-right: 334px;
            margin-top: -10px;
        }

        .main-title {
            font-family: 'Cinzel', serif;
            font-size: 80px;
            color: #222;
            letter-spacing: 2px;
            font-weight: 700;
            margin-bottom: 5px;
            text-transform: uppercase;
        }

        .subtitle-row {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 20px;
            color: #000;
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
        }

        .line {
            height: 2px;
            flex-grow: 1;
            background: #000;
        }

        .subtitle-text {
            font-size: 28px;
            font-family: 'Open Sans', sans-serif;
            font-weight: 400;
            color: #222;
        }

        /* Student Section */
        .student-section {
            text-align: center;
            margin-bottom: 15px;
            padding-right: 333px;
        }

        .student-name {
            font-size: 42px;
            font-weight: 800;
            color: #0d1b3e;
            text-transform: uppercase;
            padding: 5px 20px;
            border-bottom: 3px solid #000;
            display: inline-block;
            min-width: 500px;
            max-width: 600px;
            white-space: nowrap;
            overflow: hidden;
            margin-bottom: 15px;
            font-family: 'Open Sans', sans-serif;
        }

        .course-intro {
            font-size: 18px;
            color: #444;
            margin-bottom: 10px;
        }

        .course-name {
            font-size: 32px;
            font-weight: 800;
            color: #222;
            text-transform: uppercase;
            font-family: 'Open Sans', sans-serif;
            margin-bottom: 20px;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
            white-space: nowrap;
            overflow: hidden;
        }

        .desc-text {
            font-size: 16px;
            color: #000;
            line-height: 1.4;
            text-align: center;
            max-width: 650px;
            margin: 0 auto;
            font-family: 'Open Sans', sans-serif;
            font-weight: 400;
        }

        /* Footer */
        .footer-row {
            margin-top: auto;
            display: flex;
            justify-content: space-between;
            padding-right: 380px;
            padding-bottom: 40px;
            padding-left: 50px;
            position: relative;
        }

        .sig-block {
            text-align: center;
            width: 200px;
        }

        .sig-val {
            font-weight: 700;
            font-size: 14px;
            margin-bottom: 5px;
            display: block;
            height: 20px;
            /* Force height */
        }

        .sig-line-bar {
            height: 3px;
            background: #222;
            width: 100%;
            margin-bottom: 5px;
        }

        .sig-label {
            font-weight: 700;
            font-size: 16px;
            color: #222;
            text-transform: uppercase;
        }

        /* Stamp Block - Overlapping */
        .stamp-block {
            position: absolute;
            left: 36.3%;
            top: 50%;
            transform: translate(-50%, -50%);
            z-index: 5;
        }

        .stamp-image {
            width: 100px;
            height: auto;
            opacity: 0.85;
            margin-right: 89px;
        }

        /* Badge */
        .badge {
            position: absolute;
            bottom: 338px;
            right: 69px;
            width: 170px;
            height: 170px;
            background: white;
            border-radius: 50%;
            border: 8px solid #a61c31;
            z-index: 10;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
            text-align: center;
        }

        .badge-inner-text {
            font-size: 14px;
            font-weight: 800;
            color: #0d1b3e;
            /* Blue */
            line-height: 1.1;
            text-transform: uppercase;
        }

        .badge-year {
            font-size: 38px;
            font-weight: 800;
            color: #0d1b3e;
            margin-top: 2px;
        }

        .stars {
            color: #0d1b3e;
            font-size: 20px;
            margin-bottom: 2px;
            letter-spacing: 2px;
        }

        .ribbon-bottom {
            position: absolute;
            bottom: -35px;
            width: 140px;
            height: 60px;
            z-index: -1;
        }

        .ribbon-bottom::before,
        .ribbon-bottom::after {
            content: '';
            position: absolute;
            top: 0;
            width: 60px;
            height: 70px;
            background: #a61c31;
            clip-path: polygon(0 0, 100% 0, 100% 100%, 50% 80%, 0 100%);
        }

        .ribbon-bottom::before {
            left: 5px;
            transform: rotate(25deg);
        }

        .ribbon-bottom::after {
            right: 5px;
            transform: rotate(-25deg);
        }

        @media print {
            body {
                background: white;
                padding: 0;
                margin: 0;
            }

            .certificate-container {
                box-shadow: none;
                width: 100%;
                height: 100%;
                page-break-after: always;
            }
        }
    </style>
</head>

<body>
    <!-- Downloading Modal -->
    <div id="downloadModal"
        style="display: none; position: fixed; inset: 0; background: rgba(0,33,71,0.95); z-index: 10000; align-items: center; justify-content: center; color: white; font-family: 'Open Sans', sans-serif; text-align: center; backdrop-filter: blur(8px);">
        <div
            style="background: white; padding: 3rem; border-radius: 4px; color: #002147; max-width: 450px; width: 90%; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);">
            <div id="modalLoading">
                <div
                    style="width: 50px; height: 50px; border: 4px solid #f3f3f3; border-top: 4px solid #3db166; border-radius: 50%; animation: cert-spin 1s linear infinite; margin: 0 auto 20px;">
                </div>
                <h3
                    style="font-family: 'Playfair Display', serif; font-size: 1.75rem; color: #002147; margin-bottom: 1rem;">
                    Finalizing Document</h3>
                <p style="color: #666; font-size: 0.95rem; line-height: 1.6;">We are preparing your official certificate
                    in high definition. This may take a few seconds...</p>
            </div>
            <div id="modalComplete" style="display: none;">
                <div
                    style="width: 60px; height: 60px; background: #3db166; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; font-size: 1.5rem;">
                    <i class="fas fa-check"></i>
                </div>
                <h3
                    style="font-family: 'Playfair Display', serif; font-size: 1.75rem; color: #002147; margin-bottom: 1rem;">
                    Ready for Download</h3>
                <p style="color: #666; font-size: 0.95rem; margin-bottom: 2rem;">If the download didn't start
                    automatically, click the button below.</p>
                <button onclick="triggerDownload()"
                    style="background: #002147; color: white; border: none; padding: 12px 30px; border-radius: 4px; font-weight: 700; cursor: pointer; transition: 0.3s;">Save
                    Certificate PDF</button>
            </div>
        </div>
    </div>

    <div class="certificate-container">
        <!-- Right side Graphics -->
        <div class="bg-graphic-accent"></div>
        <div class="bg-graphic-grey"></div>
        <div class="bg-graphic-right"></div>


        <div class="content">
            <div class="header-row">
                <div class="logo-box"></div>
                <div class="cert-no-box">
                    Certificate No:
                    <?php echo htmlspecialchars($cert_id); ?>
                </div>
            </div>

            <div class="title-section">
                <h1 class="main-title">CERTIFICATE</h1>
                <div class="subtitle-row">
                    <div class="line"></div>
                    <div class="subtitle-text">of completion</div>
                    <div class="line"></div>
                </div>
            </div>

            <div class="student-section">
                <div class="student-name" id="studentNameDisplay">
                    <?php echo htmlspecialchars($intern_name); ?>
                </div>
                <p class="course-intro">for successfully completing a course of</p>
                <div class="course-name" id="courseNameDisplay">
                    <?php echo htmlspecialchars($internship_title); ?>
                </div>

                <p class="desc-text">
                    This placement course is a rigorous, in-depth and hands-on learning program consisting of 7 courses
                    and projects designed to prepare the learner for a career in
                    <?php echo htmlspecialchars($internship_title_in_desc); ?>.
                    <?php echo (strpos(strtolower($intern_name), 'bhore') !== false || strpos(strtolower($intern_name), 'prachi') !== false) ? 'She' : 'The learner'; ?>
                    demonstrated the required discipline,
                    intellect and skills to graduate from the program.
                </p>
            </div>

            <div class="footer-row">
                <!-- Date Left -->
                <div class="sig-block">
                    <span class="sig-val">
                        <?php echo htmlspecialchars($issue_date); ?>
                    </span>
                    <div class="sig-line-bar"></div>
                    <div class="sig-label">Date</div>
                </div>

                <!-- Stamp Center -->
                <div class="sig-block stamp-block">
                    <img src="backend/courses/Stampsign.png" alt="Official Stamp" class="stamp-image">
                </div>

                <!-- Signature Right -->
                <div class="sig-block">
                    <span class="sig-val">Sunil M. Nalawade</span>
                    <div class="sig-line-bar"></div>
                    <div class="sig-label">Academic Dean</div>
                </div>
            </div>
        </div>

        <!-- Badge -->
        <div class="badge">
            <div class="stars">★ ★ ★ ★ ★</div>
            <div class="badge-inner-text">CERTIFIED<br>CERTIFICATE</div>
            <div class="badge-year">
                <?php echo substr($issue_date, -4); ?>
            </div>
            <div class="ribbon-bottom"></div>
        </div>

    </div>

    <script>
        function triggerDownload() {
            window.print();
        }

        function adjustFontSize(elementId, maxFontSize) {
            const element = document.getElementById(elementId);
            if (!element) return;

            let fontSize = maxFontSize;
            element.style.fontSize = fontSize + "px";

            while (element.scrollWidth > element.offsetWidth && fontSize > 14) {
                fontSize--;
                element.style.fontSize = fontSize + "px";
            }
        }

        window.onload = function () {
            // Adjust font sizes for long names
            adjustFontSize('studentNameDisplay', 42);
            adjustFontSize('courseNameDisplay', 32);

            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('download') === '1') {
                const modal = document.getElementById('downloadModal');
                const loading = document.getElementById('modalLoading');
                const complete = document.getElementById('modalComplete');

                modal.style.display = 'flex';

                // Wait for fonts and images to load
                document.fonts.ready.then(() => {
                    setTimeout(() => {
                        // Hide the modal before printing
                        modal.style.display = 'none';

                        // Use browser's print function for perfect fidelity
                        window.print();

                        // Show completion message after print dialog
                        setTimeout(() => {
                            modal.style.display = 'flex';
                            loading.style.display = 'none';
                            complete.style.display = 'block';
                        }, 500);
                    }, 1000);
                });
            }
        }
    </script>



</body>

</html>