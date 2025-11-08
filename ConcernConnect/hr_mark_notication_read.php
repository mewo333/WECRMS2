<?php
session_start();
require_once 'config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['employee_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if (isset($_POST['notification_id'])) {
    $db = new Database();
    $conn = $db->connect();
    
    try {
        $stmt = $conn->prepare("
            UPDATE hr_notifications 
            SET is_read = 1 
            WHERE id = :id AND hr_id = :hr_id
        ");
        
        $stmt->execute([
            'id' => $_POST['notification_id'],
            'hr_id' => $_SESSION['employee_id']
        ]);
        
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Missing notification ID']);
}
?>
