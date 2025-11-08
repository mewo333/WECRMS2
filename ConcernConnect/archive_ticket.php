<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

header('Content-Type: application/json');

requireLogin();

$db = new Database();
$conn = $db->connect();

$employee = getEmployeeData($conn, $_SESSION['employee_id']);

if ($employee['role'] !== 'hr_admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$ticket_id = $_POST['id'] ?? 0;
$action = $_POST['action'] ?? 'archive';

if (!$ticket_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid ticket ID']);
    exit();
}

try {
    if ($action === 'archive') {
        // Archive the ticket
        $stmt = $conn->prepare("
            UPDATE tickets 
            SET is_archived = 1, archived_at = NOW() 
            WHERE id = :id
        ");
        $stmt->execute(['id' => $ticket_id]);
        echo json_encode(['success' => true, 'message' => 'Ticket archived successfully']);
    }
    elseif ($action === 'restore') {
        // Restore the ticket
        $stmt = $conn->prepare("
            UPDATE tickets 
            SET is_archived = 0, archived_at = NULL 
            WHERE id = :id
        ");
        $stmt->execute(['id' => $ticket_id]);
        echo json_encode(['success' => true, 'message' => 'Ticket restored successfully']);
    }
    elseif ($action === 'delete') {
        // Delete permanently
        $stmt = $conn->prepare("DELETE FROM ticket_responses WHERE ticket_id = :id");
        $stmt->execute(['id' => $ticket_id]);
        
        $stmt = $conn->prepare("DELETE FROM status_history WHERE ticket_id = :id");
        $stmt->execute(['id' => $ticket_id]);
        
        $stmt = $conn->prepare("DELETE FROM notifications WHERE ticket_id = :id");
        $stmt->execute(['id' => $ticket_id]);
        
        $stmt = $conn->prepare("DELETE FROM tickets WHERE id = :id");
        $stmt->execute(['id' => $ticket_id]);
        
        echo json_encode(['success' => true, 'message' => 'Ticket permanently deleted']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
