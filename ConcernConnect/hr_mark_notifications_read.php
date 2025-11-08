<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in and is HR admin
if (!isset($_SESSION['employee_id'])) {
    header('Location: login.php');
    exit;
}

$db = new Database();
$conn = $db->connect();

// Verify user is HR admin
$stmt = $conn->prepare("SELECT role FROM employees WHERE employee_id = ?");
$stmt->execute([$_SESSION['employee_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || $user['role'] !== 'hr_admin') {
    header('Location: dashboard.php');
    exit;
}

$hr_id = $_SESSION['employee_id'];

try {
    // Mark all HR notifications as read
    $stmt = $conn->prepare("
        UPDATE hr_notifications 
        SET is_read = 1 
        WHERE hr_id = :hr_id AND is_read = 0
    ");
    
    $result = $stmt->execute(['hr_id' => $hr_id]);
    
    if ($result) {
        // Redirect back to the previous page or HR dashboard
        $redirect = $_SERVER['HTTP_REFERER'] ?? 'hr_dashboard.php';
        header('Location: ' . $redirect . '?success=notifications_read');
        exit;
    } else {
        // Redirect back with error
        $redirect = $_SERVER['HTTP_REFERER'] ?? 'hr_dashboard.php';
        header('Location: ' . $redirect . '?error=mark_read_failed');
        exit;
    }
} catch (Exception $e) {
    error_log("Error marking HR notifications as read: " . $e->getMessage());
    $redirect = $_SERVER['HTTP_REFERER'] ?? 'hr_dashboard.php';
    header('Location: ' . $redirect . '?error=mark_read_failed');
    exit;
}
?>
