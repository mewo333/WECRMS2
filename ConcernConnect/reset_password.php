<?php
require_once 'config/database.php';

$email = $_GET['email'] ?? '';
$error = '';
$success = '';

if (empty($email)) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = trim($_POST['token'] ?? '');
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($token) || empty($new_password) || empty($confirm_password)) {
        $error = 'All fields are required';
    } elseif (!preg_match('/^\d{6}$/', $token)) {
        $error = 'Verification code must be 6 digits';
    } elseif ($new_password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (strlen($new_password) < 6) {
        $error = 'Password must be at least 6 characters';
    } else {
        try {
            $db = new Database();
            $conn = $db->connect();

            // Verify token
            $stmt = $conn->prepare("
                SELECT employee_id, used, expires_at 
                FROM password_reset_tokens 
                WHERE email = ? AND token = ?
            ");
            $stmt->execute([$email, $token]);
            $reset_token = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$reset_token) {
                $error = 'Invalid verification code';
            } elseif ($reset_token['used'] == 1) {
                $error = 'This verification code has already been used';
            } elseif (strtotime($reset_token['expires_at']) < time()) {
                $error = 'Verification code has expired. Please request a new one.';
            } else {
                // Update password - hash it for security
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE employees SET password = ? WHERE employee_id = ?");
                
                if ($stmt->execute([$hashed_password, $reset_token['employee_id']])) {
                    // Mark token as used
                    $stmt = $conn->prepare("UPDATE password_reset_tokens SET used = 1 WHERE email = ? AND token = ?");
                    $stmt->execute([$email, $token]);

                    $success = 'Password reset successfully! Redirecting to login page...';
                    header("refresh:3;url=login.php");
                } else {
                    $error = 'Failed to update password. Please try again.';
                }
            }
        } catch (PDOException $e) {
            $error = 'Database error occurred. Please try again later.';
            error_log("Password reset error: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Reset Password</title>
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

        .reset-wrapper {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            padding: 20px;
        }

        .reset-container {
            width: 100%;
            max-width: 500px;
            background: rgba(100, 100, 100, 0.15);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            padding: 50px;
            border-radius: 30px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .reset-title {
            font-size: 32px;
            font-weight: 700;
            color: #000;
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 1px;
            text-shadow: 0 2px 4px rgba(255, 255, 255, 0.3);
            text-align: center;
        }

        .reset-subtitle {
            font-size: 14px;
            color: #000;
            margin-bottom: 30px;
            opacity: 0.8;
            text-align: center;
            line-height: 1.5;
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

        .form-button:hover {
            background: rgba(30, 30, 30, 0.85);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
        }

        .back-link {
            text-align: center;
            margin-top: 20px;
        }

        .back-link a {
            color: #000;
            text-decoration: none;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .back-link a:hover {
            color: #4facfe;
        }

        .alert-error,
        .alert-success {
            padding: 12px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            animation: slideDown 0.3s ease;
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

        .password-requirements {
            font-size: 12px;
            color: rgba(0, 0, 0, 0.7);
            margin-top: 5px;
        }

        @media (max-width: 480px) {
            .reset-container {
                padding: 30px 20px;
            }

            .reset-title {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="video-background">
        <video autoplay muted loop playsinline>
            <source src="assets/videos/background.mp4" type="video/mp4">
            <source src="assets/videos/background.webm" type="video/webm">
        </video>
    </div>

    <div class="reset-wrapper">
        <div class="reset-container">
            <h2 class="reset-title">RESET PASSWORD</h2>
            <p class="reset-subtitle">
                Enter the 6-digit verification code sent to<br>
                <strong><?= htmlspecialchars($email) ?></strong>
            </p>

            <?php if ($error): ?>
                <div class="alert-error">
                    <i class="bi bi-exclamation-circle"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert-success">
                    <i class="bi bi-check-circle"></i>
                    <span><?= htmlspecialchars($success) ?></span>
                </div>
            <?php else: ?>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="token" class="form-label">Verification Code</label>
                        <input
                            type="text"
                            id="token"
                            name="token"
                            class="form-input"
                            placeholder="Enter 6-digit code"
                            maxlength="6"
                            pattern="\d{6}"
                            required
                            autofocus
                        />
                    </div>

                    <div class="form-group">
                        <label for="new_password" class="form-label">New Password</label>
                        <input
                            type="password"
                            id="new_password"
                            name="new_password"
                            class="form-input"
                            placeholder="Enter new password"
                            minlength="6"
                            required
                        />
                        <div class="password-requirements">
                            Minimum 6 characters
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <input
                            type="password"
                            id="confirm_password"
                            name="confirm_password"
                            class="form-input"
                            placeholder="Confirm new password"
                            minlength="6"
                            required
                        />
                    </div>

                    <button type="submit" class="form-button">Reset Password</button>
                </form>
            <?php endif; ?>

            <div class="back-link">
                <a href="login.php">
                    <i class="bi bi-arrow-left"></i> Back to Login
                </a>
            </div>
        </div>
    </div>

    <script>
        // Auto-format verification code input to numbers only
        document.getElementById('token')?.addEventListener('input', function(e) {
            this.value = this.value.replace(/\D/g, '');
        });

        // Password match validation
        const newPassword = document.getElementById('new_password');
        const confirmPassword = document.getElementById('confirm_password');

        if (newPassword && confirmPassword) {
            confirmPassword.addEventListener('input', function() {
                if (newPassword.value !== confirmPassword.value) {
                    confirmPassword.setCustomValidity('Passwords do not match');
                } else {
                    confirmPassword.setCustomValidity('');
                }
            });

            newPassword.addEventListener('input', function() {
                if (confirmPassword.value && newPassword.value !== confirmPassword.value) {
                    confirmPassword.setCustomValidity('Passwords do not match');
                } else {
                    confirmPassword.setCustomValidity('');
                }
            });
        }
    </script>
</body>
</html>
