<?php
header('Content-Type: application/json');
session_start();

require_once 'config/database.php';
require_once 'includes/auth.php';

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$db = new Database();
$conn = $db->connect();

$employee = getEmployeeData($conn, $_SESSION['employee_id']);

if ($user['role'] !== 'hr_admin') {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $ticket_id = $input['ticket_id'] ?? 0;
    $assign_to = $input['assign_to'] ?? '';
    
    if ($ticket_id && $assign_to) {
        $stmt = $conn->prepare("UPDATE tickets SET assigned_to = :assigned_to, updated_at = CURRENT_TIMESTAMP WHERE id = :id");
        $stmt->execute(['assigned_to' => $assign_to, 'id' => $ticket_id]);
        
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Invalid data']);
    }
}
?>
