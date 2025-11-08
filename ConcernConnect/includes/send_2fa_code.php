<?php
require_once '../config/database.php';
session_start();

header('Content-Type: application/json');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Manual installation - NO COMPOSER NEEDED
require '../PHPMailer/Exception.php';
require '../PHPMailer/PHPMailer.php';
require '../PHPMailer/SMTP.php';

try {
    $db = new Database();
    $conn = $db->connect();
    
    // Generate 6-digit code
    $code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    $expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));
    
    // Store code in database
    $stmt = $conn->prepare("UPDATE employees SET two_step_code = :code, two_step_code_expires = :expires WHERE employee_id = :employee_id");
    $stmt->execute([
        'code' => $code,
        'expires' => $expires,
        'employee_id' => $_SESSION['employee_id']
    ]);
    
    // Get user email
    $stmt = $conn->prepare("SELECT email, full_name FROM employees WHERE employee_id = :employee_id");
    $stmt->execute(['employee_id' => $_SESSION['employee_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Send email using PHPMailer
    $mail = new PHPMailer(true);
    
    // SMTP Configuration - UPDATE THESE WITH YOUR GMAIL SETTINGS
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'trustingai.suppemployee00@gmail.com'; // Your Gmail address
    $mail->Password = 'wafw yozz sutx jzvd'; // Gmail App Password (not regular password)
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;
    
    // Email content
    $mail->setFrom('employeesupport@trusting.ai', 'Trusting Social AI Philippines');
    $mail->addAddress($user['email']);
    $mail->isHTML(true);
    $mail->Subject = 'Two-Factor Authentication Code';
    $mail->Body = "
        <h2>Two-Factor Authentication</h2>
        <p>Hello {$user['full_name']},</p>
        <p>Your verification code is:</p>
        <h1 style='color: #007bff; font-size: 2.5rem; letter-spacing: 0.5rem;'>{$code}</h1>
        <p>This code will expire in 10 minutes.</p>
        <p>If you didn't request this code, please ignore this email.</p>
    ";
    
    $mail->send();
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to send email: ' . $e->getMessage()]);
}
?>
