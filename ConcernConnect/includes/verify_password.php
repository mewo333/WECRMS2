<?php
require_once '../config/database.php';
session_start();

header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$password = $_POST['password'] ?? '';

if (empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Password required']);
    exit;
}

if (!isset($_SESSION['employee_id'])) {
    echo json_encode(['success' => false, 'message' => 'Session expired. Please login again.']);
    exit;
}

try {
    $db = new Database();
    $conn = $db->connect();
    
    $stmt = $conn->prepare("SELECT password, email FROM employees WHERE employee_id = :employee_id");
    $stmt->execute(['employee_id' => $_SESSION['employee_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }
    
    if (password_verify($password, $user['password'])) {
        echo json_encode([
            'success' => true,
            'email' => $user['email']
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Incorrect password']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
