<?php
function generateTicketNumber() {
    return 'TKT-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid()), 0, 6));
}

function getTicketStats($conn, $employee_id) {
    $stats = [
        'total' => 0,
        'pending' => 0,
        'in_progress' => 0,
        'resolved' => 0
    ];
    
    $stmt = $conn->prepare("SELECT status, COUNT(*) as count FROM tickets WHERE employee_id = :employee_id GROUP BY status");
    $stmt->execute(['employee_id' => $employee_id]);
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $status_key = strtolower(str_replace(' ', '_', $row['status']));
        $stats[$status_key] = $row['count'];
        $stats['total'] += $row['count'];
    }
    
    return $stats;
}

function getStatusCounts($conn, $employee_id) {
    $stmt = $conn->prepare("SELECT COUNT(DISTINCT DATE(created_at)) as days FROM tickets WHERE employee_id = :employee_id AND created_at >= CURRENT_DATE - INTERVAL 30 DAY");
    $stmt->execute(['employee_id' => $employee_id]);
    $days_result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM tickets WHERE employee_id = :employee_id AND LOWER(status) IN ('resolved', 'closed')");
    $stmt->execute(['employee_id' => $employee_id]);
    $completed_result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM tickets WHERE employee_id = :employee_id AND LOWER(status) = 'in progress'");
    $stmt->execute(['employee_id' => $employee_id]);
    $in_progress_result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return [
        'days' => max(1, $days_result['days'] ?? 1),
        'completed' => $completed_result['total'] ?? 0,
        'in_progress' => $in_progress_result['total'] ?? 0
    ];
}

function updateStatusTracking($conn, $employee_id, $old_status, $new_status) {
    $status_type = strtolower(str_replace(' ', '_', $new_status));
    
    $stmt = $conn->prepare("
        INSERT INTO status_updates (employee_id, status_type, status_count, status_date) 
        VALUES (:employee_id, :status_type, 1, CURRENT_DATE) 
        ON DUPLICATE KEY UPDATE status_count = status_count + 1
    ");
    $stmt->execute([
        'employee_id' => $employee_id,
        'status_type' => $status_type
    ]);
}

function getRecentTickets($conn, $employee_id, $limit = 5) {
    $stmt = $conn->prepare("SELECT * FROM tickets WHERE employee_id = :employee_id ORDER BY created_at DESC LIMIT :limit");
    $stmt->bindValue(':employee_id', $employee_id, PDO::PARAM_STR);
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getChatHistory($conn, $employee_id, $limit = 10) {
    $stmt = $conn->prepare("SELECT * FROM chat_messages WHERE employee_id = :employee_id ORDER BY created_at DESC LIMIT :limit");
    $stmt->bindValue(':employee_id', $employee_id, PDO::PARAM_STR);
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->execute();
    return array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));
}

function getBotResponse($conn, $message) {
    $message = strtolower(trim($message));
    
    $stmt = $conn->prepare("SELECT response_text FROM chatbot_responses WHERE LOWER(trigger_keyword) = :keyword");
    $stmt->execute(['keyword' => $message]);
    $response = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($response) {
        return $response['response_text'];
    }
    
    foreach (['leave', 'payroll', 'benefits', 'equipment', 'help', 'ticket'] as $keyword) {
        if (strpos($message, $keyword) !== false) {
            $stmt = $conn->prepare("SELECT response_text FROM chatbot_responses WHERE LOWER(trigger_keyword) = :keyword");
            $stmt->execute(['keyword' => $keyword]);
            $response = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($response) {
                return $response['response_text'];
            }
        }
    }
    
    return "I couldn't find a specific answer to your question. Please create a ticket or contact HR directly at hr@company.com for personalized assistance.";
}

function getPriorityClass($priority) {
    switch(strtolower($priority)) {
        case 'high': return 'badge-danger';
        case 'medium': return 'badge-warning';
        case 'low': return 'badge-info';
        default: return 'badge-secondary';
    }
}

function getStatusClass($status) {
    switch(strtolower($status)) {
        case 'resolved': return 'badge-success';
        case 'in progress': return 'badge-warning';
        case 'pending': return 'badge-secondary';
        case 'closed': return 'badge-dark';
        default: return 'badge-secondary';
    }
}
?>
