<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

$db = new Database();
$conn = $db->connect();

$employee = getEmployeeData($conn, $_SESSION['employee_id']);

if ($user['role'] !== 'hr_admin') {
    header('Location: dashboard.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $ticket_id = $_POST['ticket_id'] ?? 0;
    
    if (isset($_POST['update_status'])) {
        $new_status = $_POST['new_status'] ?? '';
        if ($new_status && $ticket_id) {
            $stmt = $conn->prepare("UPDATE tickets SET status = :status, updated_at = CURRENT_TIMESTAMP WHERE id = :id");
            $stmt->execute(['status' => $new_status, 'id' => $ticket_id]);
            
            $stmt = $conn->prepare("SELECT user_id, ticket_number FROM tickets WHERE id = :id");
            $stmt->execute(['id' => $ticket_id]);
            $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($ticket) {
                $message = "Your ticket " . $ticket['ticket_number'] . " status has been updated to: " . $new_status;
                $stmt = $conn->prepare("INSERT INTO notifications (user_id, ticket_id, notification_type, message, sent_via) VALUES (:user_id, :ticket_id, 'status_update', :message, 'email')");
                $stmt->execute([
                    'user_id' => $ticket['user_id'],
                    'ticket_id' => $ticket_id,
                    'message' => $message
                ]);
            }
        }
    }
}

header('Location: hr_dashboard.php');
exit();
?>
