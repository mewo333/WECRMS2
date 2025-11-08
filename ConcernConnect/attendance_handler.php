<?php
session_start();
require_once 'config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['employee_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$db = new Database();
$conn = $db->connect();

$employee_id = $_SESSION['employee_id'];
$action = $_POST['action'] ?? '';

try {
    if ($action === 'check_in') {
        // Check if already checked in (and not checked out)
        $stmt = $conn->prepare("
            SELECT id FROM work_schedules 
            WHERE employee_id = :employee_id 
            AND schedule_date = CURDATE()
            AND check_in IS NOT NULL
            AND check_out IS NULL
        ");
        $stmt->execute(['employee_id' => $employee_id]);
        
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'You are already checked in. Please check out first.']);
            exit;
        }
        
        // Insert new check-in record
        $stmt = $conn->prepare("
            INSERT INTO work_schedules (employee_id, schedule_date, check_in, status) 
            VALUES (:employee_id, CURDATE(), NOW(), 'present')
        ");
        
        $result = $stmt->execute(['employee_id' => $employee_id]);
        
        if ($result) {
            // Get the check-in time
            $stmt = $conn->prepare("
                SELECT check_in, UNIX_TIMESTAMP(check_in) as timestamp 
                FROM work_schedules 
                WHERE employee_id = :employee_id 
                AND schedule_date = CURDATE()
                ORDER BY id DESC
                LIMIT 1
            ");
            $stmt->execute(['employee_id' => $employee_id]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true, 
                'message' => 'Checked in successfully',
                'check_in_time' => $data['check_in'],
                'timestamp' => $data['timestamp']
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to check in']);
        }
        
    } elseif ($action === 'check_out') {
        // Find the most recent check-in without check-out
        $stmt = $conn->prepare("
            SELECT id FROM work_schedules 
            WHERE employee_id = :employee_id 
            AND schedule_date = CURDATE()
            AND check_in IS NOT NULL
            AND check_out IS NULL
            ORDER BY id DESC
            LIMIT 1
        ");
        $stmt->execute(['employee_id' => $employee_id]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$record) {
            echo json_encode(['success' => false, 'message' => 'No active check-in found']);
            exit;
        }
        
        // Update check-out time
        $stmt = $conn->prepare("
            UPDATE work_schedules 
            SET check_out = NOW(),
                hours_worked = TIMESTAMPDIFF(HOUR, check_in, NOW())
            WHERE id = :id
        ");
        
        $result = $stmt->execute(['id' => $record['id']]);
        
        if ($result) {
            // Get the work summary
            $stmt = $conn->prepare("
                SELECT check_in, check_out, hours_worked,
                       TIME_FORMAT(TIMEDIFF(check_out, check_in), '%H:%i:%s') as duration
                FROM work_schedules 
                WHERE id = :id
            ");
            $stmt->execute(['id' => $record['id']]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'message' => 'Checked out successfully',
                'check_in_time' => $data['check_in'],
                'check_out_time' => $data['check_out'],
                'hours_worked' => $data['hours_worked'],
                'duration' => $data['duration']
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to check out']);
        }
        
    } elseif ($action === 'get_status') {
        // Get today's active check-in
        $stmt = $conn->prepare("
            SELECT 
                check_in, 
                check_out, 
                UNIX_TIMESTAMP(check_in) as timestamp
            FROM work_schedules 
            WHERE employee_id = :employee_id 
            AND schedule_date = CURDATE()
            AND check_out IS NULL
            ORDER BY id DESC
            LIMIT 1
        ");
        $stmt->execute(['employee_id' => $employee_id]);
        $activeSession = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get total hours worked today
        $stmt = $conn->prepare("
            SELECT COALESCE(SUM(hours_worked), 0) as total_hours
            FROM work_schedules 
            WHERE employee_id = :employee_id 
            AND schedule_date = CURDATE()
            AND check_out IS NOT NULL
        ");
        $stmt->execute(['employee_id' => $employee_id]);
        $totalData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($activeSession) {
            // Currently checked in
            echo json_encode([
                'success' => true,
                'checked_in' => true,
                'checked_out' => false,
                'check_in_time' => $activeSession['check_in'],
                'timestamp' => $activeSession['timestamp'],
                'total_hours' => $totalData['total_hours']
            ]);
        } else {
            // Not currently checked in
            echo json_encode([
                'success' => true,
                'checked_in' => false,
                'checked_out' => false,
                'total_hours' => $totalData['total_hours']
            ]);
        }
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
