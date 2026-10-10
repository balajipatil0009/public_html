<?php
require_once 'backend/config/config.php';
require_once 'backend/config/database.php';

$database = new Database();
$db = $database->connect();

$internship_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$selected_internship = null;

if ($internship_id > 0) {
    $stmt = $db->prepare("SELECT * FROM internships WHERE id = :id AND is_active = 1");
    $stmt->execute(['id' => $internship_id]);
    $selected_internship = $stmt->fetch();
}

// Fetch all active internships for the dropdown/selector
$stmt = $db->query("SELECT id, title FROM internships WHERE is_active = 1 ORDER BY display_order ASC");
$all_internships = $stmt->fetchAll();

$siteName = SITE_NAME ?? 'ITINFO ACADEMY';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Internship Registration - <?php echo htmlspecialchars($siteName); ?></title>
    <link rel="stylesheet" href="css/styles.css?v=2026.10">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --google-blue: #4285f4;
            --google-red: #ea4335;
            --google-yellow: #fbbc05;
            --google-green: #34a853;
            --form-bg: #f0f2f5;
            --card-shadow: 0 1px 3px rgba(0,0,0,0.12), 0 1px 2px rgba(0,0,0,0.24);
        }

        body {
            background-color: var(--form-bg);
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            color: #202124;
        }

        .registration-wrapper {
            max-width: 770px;
            margin: 6rem auto;
            padding: 0 1rem;
        }

        .form-header-card {
            background: #fff;
            border-radius: 8px;
            border-top: 10px solid #3db166;
            padding: 1.5rem 2rem;
            margin-bottom: 1rem;
            box-shadow: var(--card-shadow);
        }

        .form-header-card h1 {
            font-size: 2rem;
            margin: 0 0 1rem 0;
            font-weight: 500;
        }

        .form-header-card .description {
            font-size: 0.95rem;
            line-height: 1.5;
            color: #202124;
        }

        .form-header-card .terms-list {
            padding-left: 1.5rem;
            margin-top: 1rem;
            font-size: 0.9rem;
        }

        .form-header-card .terms-list li {
            margin-bottom: 0.5rem;
        }

        .form-section {
            background: #fff;
            border-radius: 8px;
            padding: 1.5rem 2rem;
            margin-bottom: 1rem;
            box-shadow: var(--card-shadow);
            transition: border 0.3s;
        }

        .form-section:has(input:focus), .form-section:has(select:focus), .form-section:has(textarea:focus) {
            border-left: 5px solid #3db166;
        }

        .question-label {
            display: block;
            font-size: 1rem;
            font-weight: 500;
            margin-bottom: 1.5rem;
            color: #202124;
        }

        .question-label.required::after {
            content: " *";
            color: var(--google-red);
        }

        .input-text {
            width: 100%;
            border: none;
            border-bottom: 1px solid #dadce0;
            padding: 0.5rem 0;
            font-size: 1rem;
            font-family: inherit;
            transition: border-bottom 0.3s;
            outline: none;
        }

        .input-text:focus {
            border-bottom: 2px solid #3db166;
        }

        .radio-group, .checkbox-group {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .radio-item, .checkbox-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            cursor: pointer;
            font-size: 0.95rem;
        }

        .radio-item input, .checkbox-item input {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: #3db166;
        }

        .form-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 2rem;
            margin-bottom: 4rem;
        }

        .btn-submit {
            background-color: #3db166;
            color: white;
            border: none;
            padding: 0.6rem 1.5rem;
            border-radius: 4px;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.2s;
        }

        .btn-submit:hover {
            background-color: #2e8b4e;
        }

        .btn-clear {
            background: none;
            border: none;
            color: #3db166;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
        }

        .progress-bar {
            height: 10px;
            background: #e0e0e0;
            border-radius: 5px;
            margin-bottom: 2rem;
            overflow: hidden;
            display: none; /* Only show if multi-page */
        }

        .progress-fill {
            height: 100%;
            background: #3db166;
            width: 0%;
        }

        .error-message {
            color: var(--google-red);
            font-size: 0.8rem;
            margin-top: 0.5rem;
            display: none;
        }
    </style>
</head>
<body>
    <div id="navbar"></div>

    <div class="registration-wrapper">
        <form id="applyForm" action="backend/api/enroll.php" method="POST">
            <div class="form-header-card">
                <h1>Registration Form</h1>
                <div class="description">
                    <p><strong>ITINFO ACADEMY Internship Program</strong></p>
                    <p>Congratulations! You have taken the first step towards a successful career. Please fill out the form below to register for our 30-day virtual internship program.</p>
                    <p><strong>Terms and Conditions:</strong></p>
                    <ul class="terms-list">
                        <li>The duration of the internship is 30 days.</li>
                        <li>Interns will be assigned real-world projects based on their domain.</li>
                        <li>Certificate will be issued only upon successful completion of all tasks.</li>
                        <li>Maintain professional decorum throughout the program.</li>
                        <li>The registration fee is non-refundable.</li>
                    </ul>
                </div>
            </div>

            <!-- Email -->
            <div class="form-section">
                <label class="question-label required">Email</label>
                <input type="email" name="email" class="input-text" placeholder="Your email" required>
            </div>

            <!-- Internship Domain -->
            <div class="form-section">
                <label class="question-label required">Internship Domain</label>
                <div class="radio-group">
                    <?php foreach ($all_internships as $intern): ?>
                        <label class="radio-item">
                            <input type="radio" name="internship_id" value="<?php echo $intern['id']; ?>" 
                                <?php echo ($selected_internship && $selected_internship['id'] == $intern['id']) ? 'checked' : ''; ?> required>
                            <?php echo htmlspecialchars($intern['title']); ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Name -->
            <div class="form-section">
                <label class="question-label required">Name</label>
                <input type="text" name="full_name" class="input-text" placeholder="Your name" required>
            </div>

            <!-- Gender -->
            <div class="form-section">
                <label class="question-label required">Gender</label>
                <div class="radio-group">
                    <label class="radio-item"><input type="radio" name="gender" value="Male" required> Male</label>
                    <label class="radio-item"><input type="radio" name="gender" value="Female" required> Female</label>
                    <label class="radio-item"><input type="radio" name="gender" value="Prefer not to say" required> Prefer not to say</label>
                </div>
            </div>

            <!-- Contact No -->
            <div class="form-section">
                <label class="question-label required">Contact No</label>
                <input type="tel" name="phone" class="input-text" placeholder="Your phone number" required>
            </div>

            <!-- College Name -->
            <div class="form-section">
                <label class="question-label required">Name of College</label>
                <input type="text" name="college_name" class="input-text" placeholder="Your college name" required>
            </div>

            <!-- Academic Degree -->
            <div class="form-section">
                <label class="question-label required">Currently Pursuing Academic Degree</label>
                <input type="text" name="academic_degree" class="input-text" placeholder="e.g. B.Tech, BCA, MCA" required>
            </div>

            <!-- Current Year -->
            <div class="form-section">
                <label class="question-label required">Current Year of your Course?</label>
                <div class="radio-group">
                    <label class="radio-item"><input type="radio" name="current_year" value="1" required> 1</label>
                    <label class="radio-item"><input type="radio" name="current_year" value="2" required> 2</label>
                    <label class="radio-item"><input type="radio" name="current_year" value="3" required> 3</label>
                    <label class="radio-item"><input type="radio" name="current_year" value="4" required> 4</label>
                    <label class="radio-item"><input type="radio" name="current_year" value="Other" required> Other</label>
                </div>
            </div>

            <!-- Source -->
            <div class="form-section">
                <label class="question-label required">Where did you learn about IT INFO ACADEMY</label>
                <div class="radio-group">
                    <label class="radio-item"><input type="radio" name="source" value="Social Media" required> Social Media</label>
                    <label class="radio-item"><input type="radio" name="source" value="Referral" required> Referral</label>
                    <label class="radio-item"><input type="radio" name="source" value="Campus Placement Cell" required> Campus Placement Cell</label>
                    <label class="radio-item"><input type="radio" name="source" value="Other" required> Other</label>
                </div>
            </div>

            <!-- Country -->
            <div class="form-section">
                <label class="question-label required">Country</label>
                <div class="radio-group">
                    <label class="radio-item"><input type="radio" name="country" value="INDIA" checked required> INDIA</label>
                    <label class="radio-item"><input type="radio" name="country" value="Other" required> Other</label>
                </div>
            </div>

            <!-- T&C -->
            <div class="form-section">
                <label class="question-label required">Terms and Conditions</label>
                <label class="checkbox-item">
                    <input type="checkbox" name="accept_terms" required> 
                    I have read and accept all <a href="terms-and-conditions.php" target="_blank" style="color: #3db166; text-decoration: underline; font-weight: 600;">Terms and Conditions</a>
                </label>
            </div>

            <!-- Queries -->
            <div class="form-section">
                <label class="question-label">Queries (if Any)</label>
                <textarea name="queries" class="input-text" rows="3" placeholder="Your message"></textarea>
            </div>

            <!-- Payment Method -->
            <div class="form-section">
                <label class="question-label required">Payment Method</label>
                <div class="radio-group">
                    <label class="radio-item"><input type="radio" name="payment_method" value="online" checked required> Secure Online Payment (Razorpay)</label>
                    <label class="radio-item"><input type="radio" name="payment_method" value="cash" required> Manual Payment (Office/Bank Transfer)</label>
                </div>
            </div>

            <div class="form-footer">
                <button type="submit" class="btn-submit">Submit Application</button>
                <button type="reset" class="btn-clear">Clear form</button>
            </div>
        </form>
    </div>

    <div id="footer"></div>

    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <script src="js/components.js?v=2026.10"></script>
    <script src="js/main.js"></script>
    <script>
        document.getElementById('applyForm').addEventListener('submit', async function(e) {
            const formData = new FormData(this);
            const paymentMethod = formData.get('payment_method');
            const submitBtn = this.querySelector('button[type="submit"]');

            if (paymentMethod === 'online') {
                e.preventDefault();
                submitBtn.disabled = true;
                submitBtn.innerText = 'Processing Payment...';

                try {
                    // 1. Create Order
                    const orderRes = await fetch('backend/api/create_order.php', {
                        method: 'POST',
                        body: JSON.stringify({
                            internship_id: formData.get('internship_id')
                        }),
                        headers: { 'Content-Type': 'application/json' }
                    });

                    const orderData = await orderRes.json();

                    if (!orderData.success) {
                        alert('Error creating order: ' + orderData.message);
                        submitBtn.disabled = false;
                        submitBtn.innerText = 'Submit Application';
                        return;
                    }

                    // 2. Open Razorpay
                    const options = {
                        "key": orderData.key_id,
                        "amount": orderData.amount,
                        "currency": "INR",
                        "name": orderData.name,
                        "description": orderData.description,
                        "order_id": orderData.order_id,
                        "handler": async function (response) {
                            // 3. Verify Payment
                            submitBtn.innerText = 'Verifying...';

                            const verifyRes = await fetch('backend/api/verify_payment.php', {
                                method: 'POST',
                                body: JSON.stringify({
                                    razorpay_payment_id: response.razorpay_payment_id,
                                    razorpay_order_id: response.razorpay_order_id,
                                    razorpay_signature: response.razorpay_signature,
                                    internship_id: formData.get('internship_id'),
                                    full_name: formData.get('full_name'),
                                    email: formData.get('email'),
                                    gender: formData.get('gender'),
                                    phone: formData.get('phone'),
                                    college_name: formData.get('college_name'),
                                    academic_degree: formData.get('academic_degree'),
                                    current_year: formData.get('current_year'),
                                    country: formData.get('country'),
                                    source: formData.get('source'),
                                    queries: formData.get('queries')
                                }),
                                headers: { 'Content-Type': 'application/json' }
                            });

                            const verifyData = await verifyRes.json();

                            if (verifyData.success) {
                                window.location.href = verifyData.redirect_url;
                            } else {
                                alert('Payment verification failed: ' + verifyData.message);
                                submitBtn.disabled = false;
                                submitBtn.innerText = 'Submit Application';
                            }
                        },
                        "prefill": {
                            "name": formData.get('full_name'),
                            "email": formData.get('email'),
                            "contact": formData.get('phone')
                        },
                        "theme": {
                            "color": "#3db166"
                        },
                        "modal": {
                            "ondismiss": function() {
                                submitBtn.disabled = false;
                                submitBtn.innerText = 'Submit Application';
                            }
                        }
                    };

                    const rzp1 = new Razorpay(options);
                    rzp1.open();

                } catch (err) {
                    console.error(err);
                    alert('An error occurred. Please try again.');
                    submitBtn.disabled = false;
                    submitBtn.innerText = 'Submit Application';
                }
            } else {
                // Manual submission - let the form submit normally to enroll.php
                submitBtn.disabled = true;
                submitBtn.innerText = 'Submitting...';
            }
        });
    </script>
</body>
</html>
