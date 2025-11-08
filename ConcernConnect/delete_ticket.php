<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

header('Content-Type: application/json');

requireLogin();

$db = new Database();
$conn = $db->connect();

$employee = getEmployeeData($conn, $_SESSION['employee_id']);

// Only HR admin can delete
if ($employee['role'] !== 'hr_admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$ticket_id = $_POST['id'] ?? 0;

if (!$ticket_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid ticket ID']);
    exit();
}

try {
    // Delete ticket responses first (foreign key constraint)
    $stmt = $conn->prepare("DELETE FROM ticket_responses WHERE ticket_id = :id");
    $stmt->execute(['id' => $ticket_id]);
    
    // Delete status history
    $stmt = $conn->prepare("DELETE FROM status_history WHERE ticket_id = :id");
    $stmt->execute(['id' => $ticket_id]);
    
    // Delete notifications
    $stmt = $conn->prepare("DELETE FROM notifications WHERE ticket_id = :id");
    $stmt->execute(['id' => $ticket_id]);
    
    // Delete the ticket
    $stmt = $conn->prepare("DELETE FROM tickets WHERE id = :id");
    $stmt->execute(['id' => $ticket_id]);
    
    echo json_encode(['success' => true, 'message' => 'Ticket deleted successfully']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
