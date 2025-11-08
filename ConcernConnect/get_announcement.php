<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';
require_once 'includes/auth.php';

requireLogin();

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing announcement ID']);
    exit();
}

try {
    $db = new Database();
    $conn = $db->connect();
    
    $id = intval($_GET['id']);
    
    // Fetch announcement
    $stmt = $conn->prepare("SELECT * FROM announcements WHERE id = ?");
    $stmt->execute([$id]);
    $announcement = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$announcement) {
        echo json_encode(['success' => false, 'message' => 'Announcement not found']);
        exit();
    }
    
    // Fetch comments - NO JOIN, just get comments
    $comments = [];
    $stmt2 = $conn->prepare("SELECT * FROM announcement_comments WHERE announcement_id = ? ORDER BY created_at DESC");
    $stmt2->execute([$id]);
    $commentsList = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    
    // Get employee info for each comment separately
    foreach ($commentsList as $comment) {
        $empStmt = $conn->prepare("SELECT full_name, photo FROM employees WHERE employee_id = ?");
        $empStmt->execute([$comment['employee_id']]);
        $emp = $empStmt->fetch(PDO::FETCH_ASSOC);
        
        $comment['full_name'] = $emp['full_name'] ?? 'Unknown User';
        $comment['photo'] = $emp['photo'] ?? null;
        
        $comments[] = $comment;
    }
    
    echo json_encode([
        'success' => true,
        'announcement' => $announcement,
        'comments' => $comments
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
