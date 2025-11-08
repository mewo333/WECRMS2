<?php
require_once '../config/database.php';
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false]);
    exit;
}

$code = $_POST['code'] ?? '';
$action = $_POST['action'] ?? 'enable';

try {
    $db = new Database();
    $conn = $db->connect();
    
    $stmt = $conn->prepare("SELECT two_step_code, two_step_code_expires FROM employees WHERE employee_id = :employee_id");
    $stmt->execute(['employee_id' => $_SESSION['employee_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Verify code and expiration
    if ($user && $user['two_step_code'] === $code && strtotime($user['two_step_code_expires']) > time()) {
        // Update 2FA status
        $enabled = ($action === 'enable') ? 1 : 0;
        $stmt = $conn->prepare("UPDATE employees SET two_step_enabled = :enabled, two_step_code = NULL, two_step_code_expires = NULL WHERE employee_id = :employee_id");
        $stmt->execute([
            'enabled' => $enabled,
            'employee_id' => $_SESSION['employee_id']
        ]);
        
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid or expired code']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>
