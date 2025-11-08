<?php
require_once 'config/database.php';

header('Content-Type: application/json');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Manual installation - matching your setup
require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$email = trim($_POST['email'] ?? '');

if (empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Email is required']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit;
}

try {
    $db = new Database();
    $conn = $db->connect();

    if (!$conn) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit;
    }

    // Check if email exists in employees table
    $stmt = $conn->prepare("SELECT employee_id, email, full_name FROM employees WHERE email = ?");
    $stmt->execute([$email]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$employee) {
        echo json_encode(['success' => false, 'message' => 'Email not found in our system']);
        exit;
    }

    // Generate 6-digit verification code
    $token = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

    // Set expiration time (15 minutes from now)
    $expires_at = date('Y-m-d H:i:s', strtotime('+15 minutes'));

    // Delete old unused tokens for this email
    $stmt = $conn->prepare("DELETE FROM password_reset_tokens WHERE email = ? AND used = 0");
    $stmt->execute([$email]);

    // Insert new token
    $stmt = $conn->prepare("INSERT INTO password_reset_tokens (employee_id, email, token, expires_at) VALUES (?, ?, ?, ?)");
    $result = $stmt->execute([$employee['employee_id'], $email, $token, $expires_at]);

    if (!$result) {
        echo json_encode(['success' => false, 'message' => 'Failed to generate verification code']);
        exit;
    }

    // Send email using PHPMailer - using your existing configuration
    $mail = new PHPMailer(true);
    
    try {
        // SMTP Configuration - matching your setup
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'trustingai.suppemployee00@gmail.com'; // Your Gmail
        $mail->Password = 'wafw yozz sutx jzvd'; // Your App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        
        // Email content
        $mail->setFrom('trustingai.suppemployee00@gmail.com', 'Trusting Social AI Philippines HR');
        $mail->addAddress($email, $employee['full_name']);
        $mail->isHTML(true);
        $mail->Subject = 'Password Reset Verification Code';
        $mail->Body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0;'>
                    <h1 style='margin: 0;'>Password Reset Request</h1>
                </div>
                <div style='background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px;'>
                    <p>Hello <strong>" . htmlspecialchars($employee['full_name']) . "</strong>,</p>
                    <p>You have requested to reset your password. Please use the verification code below:</p>
                    <div style='background: #667eea; color: white; font-size: 32px; font-weight: bold; padding: 20px; text-align: center; border-radius: 10px; margin: 20px 0; letter-spacing: 5px;'>
                        {$token}
                    </div>
                    <p><strong>This code will expire in 15 minutes.</strong></p>
                    <p>If you did not request a password reset, please ignore this email or contact HR immediately.</p>
                    <p>Best regards,<br>Trusting Social AI Philippines<br></p>
                </div>
                <div style='text-align: center; margin-top: 20px; color: #666; font-size: 12px;'>
                    <p>© " . date('Y') . " Trusting Social AI Philippines. All rights reserved.</p>
                </div>
            </div>
        ";
        
        $mail->AltBody = "Hello " . $employee['full_name'] . ",\n\nYour password reset verification code is: " . $token . "\n\nThis code will expire in 15 minutes.\n\nIf you did not request a password reset, please ignore this email.\n\nBest regards,\nTrusting Social AI Philippines";
        
        $mail->send();
        
        echo json_encode([
            'success' => true,
            'message' => 'Verification code sent to your email successfully! Please check your inbox.'
        ]);
        
    } catch (Exception $e) {
        // Email failed but code is generated
        error_log("Email send failed: {$mail->ErrorInfo}");
        echo json_encode([
            'success' => true,
            'message' => 'Verification code: ' . $token . ' (Email service temporarily unavailable. Please use this code.)'
        ]);
    }

} catch (PDOException $e) {
    error_log("Password reset error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred. Please try again later.'
    ]);
} catch (Exception $e) {
    error_log("General error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred. Please try again later.'
    ]);
}
?>
