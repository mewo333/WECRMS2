<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

$db = new Database();
$conn = $db->connect();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['login'])) {
        $employee_id = $_POST['employee_id'] ?? '';
        $password = $_POST['password'] ?? '';

        if (login($conn, $employee_id, $password)) {
            $employee = getEmployeeData($conn, $_SESSION['employee_id']);

            if ($employee && isset($employee['role']) && $employee['role'] === 'hr_admin') {
                header('Location: hr_dashboard.php');
            } else {
                header('Location: dashboard.php');
            }
            exit();
        } else {
            $error = 'Invalid Email or Password';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            height: 100vh;
            overflow: hidden;
            position: relative;
        }

        /* Video Background */
        .video-background {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            overflow: hidden;
        }

        .video-background video {
            position: absolute;
            top: 50%;
            left: 50%;
            min-width: 100%;
            min-height: 100%;
            width: auto;
            height: auto;
            transform: translate(-50%, -50%);
            object-fit: cover;
        }

        .video-background::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 25%, #4facfe 75%, #00f2fe 100%);
            z-index: -1;
        }

        .login-wrapper {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            padding: 20px;
            position: relative;
            z-index: 1;
        }

        .login-container {
            display: flex;
            width: 100%;
            max-width: 1100px;
            height: 600px;
            background: transparent;
            border-radius: 30px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            position: relative;
        }

        /* Left Side - Welcome Panel */
        .welcome-panel {
            flex: 1;
            background: rgba(255, 255, 255, 0.9);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 40px;
            position: relative;
            border-radius: 30px 0 0 30px;
            background-image: url('assets/images/welcome-bg.jpg');
            background-size: cover;
            background-position: center;
        }

        .welcome-panel::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.85);
            z-index: 1;
            border-radius: 30px 0 0 30px;
        }

        .welcome-content {
            position: relative;
            z-index: 2;
            text-align: center;
        }

        .decorative-circles {
            position: relative;
            width: 200px;
            height: 200px;
            margin-bottom: 20px;
        }

        .circle {
            position: absolute;
            border-radius: 50%;
        }

        .circle-1 {
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            top: 0;
            left: 20px;
            opacity: 0.8;
        }

        .circle-2 {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #4facfe, #00f2fe);
            top: 20px;
            right: 10px;
        }

        .circle-3 {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #a8edea, #fed6e3);
            bottom: 30px;
            left: 50px;
            opacity: 0.7;
        }

        .circle-4 {
            width: 90px;
            height: 90px;
            background: linear-gradient(135deg, #e0c3fc, #8ec5fc);
            bottom: 20px;
            right: 40px;
            opacity: 0.6;
        }

        .circle-5 {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #fbc2eb, #a6c1ee);
            top: 80px;
            left: 0;
        }

        .circle-outline {
            width: 110px;
            height: 110px;
            border: 3px solid #667eea;
            bottom: 40px;
            right: 20px;
            opacity: 0.4;
        }

        .welcome-title {
            font-size: 48px;
            font-weight: 900;
            color: #000;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .welcome-subtitle {
            font-size: 16px;
            color: #333;
            line-height: 1.6;
            margin-top: 20px;
        }

        /* Right Side - Form Panel Container */
        .form-panel-container {
            flex: 1.2;
            position: relative;
            overflow: hidden;
        }

        /* Sliding Forms */
        .form-slider {
            display: flex;
            transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            height: 100%;
        }

        .form-slider.show-forgot {
            transform: translateX(-100%);
        }

        .login-panel,
        .forgot-panel {
            min-width: 100%;
            background: rgba(100, 100, 100, 0.15);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            padding: 50px 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .login-panel {
            border-radius: 0 30px 30px 0;
        }

        .form-title {
            font-size: 32px;
            font-weight: 700;
            color: #000;
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 1px;
            text-shadow: 0 2px 4px rgba(255, 255, 255, 0.3);
        }

        .form-subtitle {
            font-size: 14px;
            color: #000;
            margin-bottom: 30px;
            opacity: 0.8;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #000;
            margin-bottom: 8px;
            text-transform: capitalize;
        }

        .form-input {
            width: 100%;
            padding: 15px 20px;
            font-size: 15px;
            border: none;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.4);
            color: #000;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.4);
        }

        .form-input:focus {
            outline: none;
            background: rgba(255, 255, 255, 0.6);
            box-shadow: 0 0 0 3px rgba(79, 172, 254, 0.3);
            border-color: rgba(255, 255, 255, 0.6);
        }

        .form-input::placeholder {
            color: rgba(0, 0, 0, 0.5);
        }

        .forgot-password {
            text-align: right;
            margin-top: 8px;
            margin-bottom: 30px;
        }

        .forgot-password a {
            color: #000;
            font-size: 15px;
            text-decoration: none;
            transition: color 0.3s ease;
            cursor: pointer;
        }

        .forgot-password a:hover {
            color: #4facfe;
        }

        .back-to-login {
            text-align: left;
            margin-bottom: 30px;
        }

        .back-to-login a {
            color: #000;
            font-size: 14px;
            text-decoration: none;
            transition: color 0.3s ease;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .back-to-login a:hover {
            color: #4facfe;
        }

        .form-button {
            width: 100%;
            padding: 15px;
            font-size: 16px;
            font-weight: 600;
            color: #fff;
            background: rgba(50, 50, 50, 0.7);
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s ease;
            margin-top: 10px;
        }

        .form-button:hover:not(:disabled) {
            background: rgba(30, 30, 30, 0.85);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
        }

        .form-button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .footer-text {
            text-align: center;
            margin-top: 40px;
            font-size: 12px;
            color: rgba(0, 0, 0, 0.8);
        }

        .alert-error,
        .alert-success {
            padding: 12px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            animation: slideDown 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .alert-error {
            background: rgba(255, 71, 87, 0.9);
            color: white;
        }

        .alert-success {
            background: rgba(72, 187, 120, 0.9);
            color: white;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsive Design */
        @media (max-width: 968px) {
            .login-container {
                flex-direction: column;
                height: auto;
                max-width: 500px;
            }

            .welcome-panel {
                padding: 30px;
                min-height: 300px;
                border-radius: 30px 30px 0 0;
            }

            .welcome-panel::before {
                border-radius: 30px 30px 0 0;
            }

            .decorative-circles {
                width: 150px;
                height: 150px;
            }

            .welcome-title {
                font-size: 36px;
            }

            .login-panel,
            .forgot-panel {
                padding: 40px 30px;
                border-radius: 0 0 30px 30px;
            }
        }

        @media (max-width: 480px) {
            .login-wrapper {
                padding: 10px;
            }

            .login-container {
                border-radius: 20px;
            }

            .welcome-title {
                font-size: 28px;
            }

            .form-title {
                font-size: 24px;
            }

            .login-panel,
            .forgot-panel {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Video Background -->
    <div class="video-background">
        <video autoplay muted loop playsinline>
            <source src="assets/videos/background.mp4" type="video/mp4">
            <source src="assets/videos/background.webm" type="video/webm">
        </video>
    </div>

    <div class="login-wrapper">
        <div class="login-container">
            <!-- Left Side - Welcome Panel -->
            <div class="welcome-panel">
                <div class="welcome-content">
                    <div class="decorative-circles">
                        <div class="circle circle-1"></div>
                        <div class="circle circle-2"></div>
                        <div class="circle circle-3"></div>
                        <div class="circle circle-4"></div>
                        <div class="circle circle-5"></div>
                        <div class="circle circle-outline"></div>
                    </div>
                    <h1 class="welcome-title">WELCOME,<br>USER!</h1>
                    <p class="welcome-subtitle">
                        We're happy to see you here.<br>
                        Please log in to continue.
                    </p>
                </div>
            </div>

            <!-- Right Side - Form Container with Slider -->
            <div class="form-panel-container">
                <div class="form-slider" id="formSlider">
                    
                    <!-- Login Form -->
                    <div class="login-panel">
                        <h2 class="form-title">LOGIN ACCOUNT</h2>

                        <?php if ($error): ?>
                            <div class="alert-error">
                                <i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <input type="hidden" name="login" value="1">
                            <div class="form-group">
                                <label for="employee_id" class="form-label">Email Address</label>
                                <input
                                    type="text"
                                    id="employee_id"
                                    name="employee_id"
                                    class="form-input"
                                    placeholder="Enter your email"
                                    required
                                    value="<?= isset($_POST['employee_id']) ? htmlspecialchars($_POST['employee_id']) : '' ?>"
                                />
                            </div>

                            <div class="form-group">
                                <label for="password" class="form-label">Password</label>
                                <input
                                    type="password"
                                    id="password"
                                    name="password"
                                    class="form-input"
                                    placeholder="Enter your password"
                                    required
                                />
                            </div>

                            <div class="forgot-password">
                                <a onclick="showForgotPassword(); return false;">Forgot Password?</a>
                            </div>

                            <button type="submit" class="form-button">Login</button>
                        </form>

                        <div class="footer-text">
                            © Trusting Social AI Philippines
                        </div>
                    </div>

                    <!-- Forgot Password Form -->
                    <div class="forgot-panel">
                        <div class="back-to-login">
                            <a onclick="showLogin(); return false;">
                                <i class="bi bi-arrow-left"></i> Back to Login
                            </a>
                        </div>

                        <h2 class="form-title">FORGOT PASSWORD</h2>
                        <p class="form-subtitle">Enter your email to receive a verification code</p>

                        <div id="forgotMessage"></div>

                        <form id="forgotPasswordForm">
                            <div class="form-group">
                                <label for="forgot_email" class="form-label">Email Address</label>
                                <input
                                    type="email"
                                    id="forgot_email"
                                    name="email"
                                    class="form-input"
                                    placeholder="Enter your email"
                                    required
                                />
                            </div>

                            <button type="submit" class="form-button" id="submitBtn">Send Verification Code</button>
                        </form>

                        <div class="footer-text">
                            © Trusting Social AI Philippines
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <script>
        function showForgotPassword() {
            document.getElementById('formSlider').classList.add('show-forgot');
            document.getElementById('forgotMessage').innerHTML = '';
            document.getElementById('forgot_email').value = '';
        }

        function showLogin() {
            document.getElementById('formSlider').classList.remove('show-forgot');
            document.getElementById('forgotMessage').innerHTML = '';
        }

        // Handle forgot password form submission
        document.getElementById('forgotPasswordForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const email = document.getElementById('forgot_email').value.trim();
            const messageDiv = document.getElementById('forgotMessage');
            const submitButton = document.getElementById('submitBtn');
            
            if (!email) {
                messageDiv.innerHTML = '<div class="alert-error"><i class="bi bi-exclamation-circle"></i> Please enter your email address</div>';
                return;
            }
            
            submitButton.disabled = true;
            submitButton.textContent = 'Sending...';
            messageDiv.innerHTML = '';
            
            const formData = new FormData();
            formData.append('email', email);
            
            fetch('process_forgot_password.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    throw new Error('Server error. Please try again.');
                }
                return response.json();
            })
            .then(data => {
                submitButton.disabled = false;
                submitButton.textContent = 'Send Verification Code';
                
                if (data.success) {
                    messageDiv.innerHTML = '<div class="alert-success"><i class="bi bi-check-circle"></i> ' + data.message + '</div>';
                    setTimeout(() => {
                        window.location.href = 'reset_password.php?email=' + encodeURIComponent(email);
                    }, 3000);
                } else {
                    messageDiv.innerHTML = '<div class="alert-error"><i class="bi bi-exclamation-circle"></i> ' + data.message + '</div>';
                }
            })
            .catch(error => {
                submitButton.disabled = false;
                submitButton.textContent = 'Send Verification Code';
                messageDiv.innerHTML = '<div class="alert-error"><i class="bi bi-exclamation-circle"></i> ' + error.message + '</div>';
            });
        });
    </script>
</body>
</html>
