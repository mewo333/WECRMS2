<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['employee_id'])) {
    header('Location: login.php');
    exit;
}

$db = new Database();
$conn = $db->connect();

$employee_id = $_SESSION['employee_id'];

try {
    // Mark all notifications as read for this employee
    $stmt = $conn->prepare("
        UPDATE notifications 
        SET is_read = 1 
        WHERE employee_id = :employee_id AND is_read = 0
    ");
    
    $result = $stmt->execute(['employee_id' => $employee_id]);
    
    if ($result) {
        // Redirect back to the previous page or dashboard
        $redirect = $_SERVER['HTTP_REFERER'] ?? 'dashboard.php';
        header('Location: ' . $redirect . '?success=notifications_read');
        exit;
    } else {
        // Redirect back with error
        $redirect = $_SERVER['HTTP_REFERER'] ?? 'dashboard.php';
        header('Location: ' . $redirect . '?error=mark_read_failed');
        exit;
    }
} catch (Exception $e) {
    error_log("Error marking notifications as read: " . $e->getMessage());
    $redirect = $_SERVER['HTTP_REFERER'] ?? 'dashboard.php';
    header('Location: ' . $redirect . '?error=mark_read_failed');
    exit;
}
?>
