<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

$db = new Database();
$conn = $db->connect();

$user = getEmployeeData($conn, $_SESSION['employee_id']);
$stats = getTicketStats($conn, $_SESSION['employee_id']);
$status_counts = getStatusCounts($conn, $_SESSION['employee_id']);
$recent_tickets = getRecentTickets($conn, $_SESSION['employee_id'], 3);
$chat_history = getChatHistory($conn, $_SESSION['employee_id'], 5);

// Handle Add Comment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_comment'])) {
    $announcement_id = $_POST['announcement_id'];
    $comment = $_POST['comment'];
    
    $stmt = $conn->prepare("INSERT INTO announcement_comments (announcement_id, employee_id, comment) VALUES (:announcement_id, :employee_id, :comment)");
    $stmt->execute([
        'announcement_id' => $announcement_id,
        'employee_id' => $_SESSION['employee_id'],
        'comment' => $comment
    ]);
    
    header('Location: dashboard.php?success=commented');
    exit();
}
// Fetch announcements with comments
try {
    $stmt = $conn->query("SELECT * FROM announcements WHERE expires_at IS NULL OR expires_at > NOW() ORDER BY created_at DESC LIMIT 3");
    $announcements = $stmt->fetchAll(mode: PDO::FETCH_ASSOC);
    
    // Fetch comments for each announcement
    foreach ($announcements as &$announcement) {
        $stmt = $conn->prepare(query: "
            SELECT ac.*, e.full_name, e.photo
            FROM announcement_comments ac
            JOIN employees e ON ac.employee_id = e.employee_id
            WHERE ac.announcement_id = :id
            ORDER BY ac.created_at DESC
            LIMIT 10
        ");
        $stmt->execute(params: ['id' => $announcement['id']]);
        $announcement['comments'] = $stmt->fetchAll(mode: PDO::FETCH_ASSOC);
    }
    unset($announcement); // ← ADD THIS LINE HERE (after line 50)
} catch (PDOException $e) {
    $announcements = [];
}

$chatbot_reply = '';

if (!isset($_SESSION)) session_start();

if (!isset($_SESSION['chat_history'])) {
    $_SESSION['chat_history'] = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['chat_message'])) {
    $message = strtolower(trim($_POST['chat_message']));

    $responses = [
        'leave' => "You can request leave under the HR Portal → Leave Requests section.",
        'payroll' => "Payroll is processed every 15th and 30th of the month.",
        'benefits' => "We offer healthcare, paid leave, and annual bonuses.",
        'hi' => "Hello! How can I help you today?",
        'hello' => "Hi there! What do you need help with?",
        'thank you' => "You're welcome! 😊 Anything else you'd like to know?",
        'bye' => "Goodbye! Have a great day!"
    ];

    if (array_key_exists($message, $responses)) {
        $reply = $responses[$message];
    } else {
        $reply = "Sorry, I'm not sure how to answer that. Please contact HR for assistance.";
    }

    $_SESSION['chat_history'][] = ['sender' => 'user', 'message' => $_POST['chat_message']];
    $_SESSION['chat_history'][] = ['sender' => 'bot', 'message' => $reply];

    if (isset($_POST['ajax'])) {
        header('Content-Type: application/json');
        echo json_encode(['reply' => $reply, 'history' => $_SESSION['chat_history']]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary: #667eea;
            --secondary: #764ba2;
            --accent: #4facfe;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --light-bg: #f8fafc;
            --card-bg: #ffffff;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --border-color: #e2e8f0;
            --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.1);
            --shadow-md: 0 4px 6px rgba(0, 0, 0, 0.07);
            --shadow-lg: 0 10px 15px rgba(0, 0, 0, 0.1);
            --radius: 16px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: var(--light-bg);
            color: var(--text-primary);
            overflow-x: hidden;
            line-height: 1.6;
        }

        .page-container {
            display: flex;
            min-height: 100vh;
        }

        .main-content {
            flex: 1;
            padding: 2rem;
            overflow-y: auto;
            max-height: 100vh;
        }

        .dashboard-header {
            background: var(--card-bg);
            padding: 1.5rem;
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .dashboard-header h1 {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--text-primary);
            margin: 0;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: 1.5fr 1fr;
            gap: 2rem;
            margin-top: 2rem;
        }

        .card {
            background: var(--card-bg);
            border-radius: var(--radius);
            padding: 1.5rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }

        .card:hover {
            box-shadow: var(--shadow-md);
        }

        .card-header {
            margin-bottom: 1.5rem;
        }

        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-primary);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .card-subtitle {
            color: var(--text-secondary);
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }

        .company-card {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 1.75rem;
            border-radius: var(--radius);
            box-shadow: var(--shadow-lg);
            margin-bottom: 2rem;
            border: none;
        }

        .company-card h3 {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 1.25rem;
            opacity: 0.95;
        }

        .time-display {
            text-align: center;
            padding: 1rem 0;
        }

        .current-time {
            font-size: 2.5rem;
            font-weight: 700;
            margin: 0.75rem 0;
            letter-spacing: -0.02em;
            font-variant-numeric: tabular-nums;
        }

        .shift-info {
            font-size: 0.9rem;
            opacity: 0.9;
            margin: 0.75rem 0;
        }

        .checkout-btn {
            background: rgba(255, 255, 255, 0.25);
            border: 2px solid rgba(255, 255, 255, 0.5);
            color: white;
            padding: 0.625rem 1.75rem;
            border-radius: 50px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 0.75rem;
            font-size: 0.9rem;
        }

        .checkout-btn:hover {
            background: rgba(255, 255, 255, 0.35);
            transform: translateY(-2px);
        }

        .status-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
        }

        .status-item {
            text-align: center;
            padding: 1.25rem 1rem;
            border-radius: 12px;
            background: linear-gradient(135deg, #f0f9ff, #e0f2fe);
            border: 1px solid #bae6fd;
        }

        .status-item.completed {
            background: linear-gradient(135deg, #f0fdf4, #dcfce7);
            border-color: #bbf7d0;
        }

        .status-item.in-progress {
            background: linear-gradient(135deg, #fffbeb, #fef3c7);
            border-color: #fde68a;
        }

        .status-count {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .status-label {
            font-size: 0.875rem;
            color: var(--text-secondary);
            font-weight: 500;
        }

        .chat-history {
            max-height: 300px;
            overflow-y: auto;
            padding-right: 0.5rem;
        }

        .chat-history::-webkit-scrollbar {
            width: 6px;
        }

        .chat-history::-webkit-scrollbar-track {
            background: var(--light-bg);
            border-radius: 3px;
        }

        .chat-history::-webkit-scrollbar-thumb {
            background: var(--border-color);
            border-radius: 3px;
        }

        .chat-message {
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 0.75rem;
            animation: fadeIn 0.3s ease;
        }

        .chat-message.bot {
            background: linear-gradient(135deg, #ede9fe, #ddd6fe);
            border-left: 3px solid var(--primary);
        }

        .chat-message.user {
            background: linear-gradient(135deg, #e0f2fe, #bae6fd);
            border-left: 3px solid var(--accent);
        }

        .chat-message strong {
            display: block;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.5rem;
            color: var(--text-secondary);
        }

        .team-member-card {
            text-align: center;
            padding: 2rem 1.5rem;
        }

        .member-profile {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .avatar-image {
            width: 96px;
            height: 96px;
            min-width: 96px;
            min-height: 96px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 1.5rem;
            box-shadow: 0 8px 24px rgba(124, 58, 237, 0.3);
            border: 3px solid white;
        }

        .team-member-card .avatar-circle {
            width: 96px;
            height: 96px;
            min-width: 96px;
            min-height: 96px;
            border-radius: 50%;
            background: linear-gradient(135deg, #7c3aed, #4f46e5);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 2rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 8px 24px rgba(124, 58, 237, 0.3);
            flex-shrink: 0;
        }

        .member-name {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
            margin: 0 0 0.5rem 0;
        }

        .member-role {
            color: var(--text-secondary);
            font-size: 0.95rem;
            margin: 0 0 1rem 0;
        }

        .member-email {
            color: var(--text-secondary);
            font-size: 0.875rem;
            text-decoration: underline;
            margin-bottom: 1rem;
            display: inline-block;
            transition: color 0.3s ease;
        }

        .member-email:hover {
            color: var(--primary);
        }

        .member-joined {
            color: var(--text-secondary);
            font-size: 0.875rem;
            margin: 0.5rem 0 0 0;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .member-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border-color);
        }

        .action-btn {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            border: none;
            background: var(--light-bg);
            color: var(--text-secondary);
            font-size: 1.25rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .action-btn:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .calendar-header {
            display: flex;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }

        .calendar-header select {
            flex: 1;
            padding: 0.625rem 1rem;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 0.875rem;
            background: white;
            cursor: pointer;
        }

        #calendar {
            background: var(--light-bg);
            padding: 1rem;
            border-radius: 8px;
            min-height: 200px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-secondary);
        }

        .event-item {
            display: flex;
            gap: 1rem;
            padding: 1rem;
            background: var(--light-bg);
            border-radius: 12px;
            margin-bottom: 0.75rem;
            border-left: 3px solid var(--primary);
            transition: all 0.3s ease;
        }

        .event-item:hover {
            background: #f1f5f9;
            transform: translateX(5px);
        }

        .event-date {
            text-align: center;
            font-weight: 700;
            color: var(--primary);
            min-width: 60px;
            font-size: 0.875rem;
        }

        .event-details strong {
            display: block;
            margin-bottom: 0.25rem;
            color: var(--text-primary);
        }

        .event-details div {
            font-size: 0.875rem;
            color: var(--text-secondary);
        }

        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(8px);
            z-index: 2000;
            align-items: center;
            justify-content: center;
        }

        .modal-overlay.active {
            display: flex;
            animation: fadeIn 0.3s ease;
        }

        .modal-content {
            background: white;
            padding: 2.5rem;
            border-radius: 24px;
            box-shadow: 0 25px 70px rgba(0, 0, 0, 0.3);
            max-width: 480px;
            width: 90%;
            animation: slideUp 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .modal-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .modal-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 1.5rem;
            background: linear-gradient(135deg, #b14affff, #27d0ffff);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            color: white;
            box-shadow: 0 8px 24px rgba(245, 158, 11, 0.3);
            animation: iconPulse 2s ease-in-out infinite;
        }

        @keyframes iconPulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
        }

        .modal-header h3 {
            font-size: 1.75rem;
            font-weight: 700;
            margin: 0 0 0.75rem 0;
        }

        .modal-header p {
            color: var(--text-secondary);
            font-size: 1rem;
            margin: 0;
        }

        .shift-summary {
            background: linear-gradient(135deg, #f8fafc, #f1f5f9);
            padding: 1.5rem;
            border-radius: 16px;
            margin: 2rem 0;
            border: 1px solid var(--border-color);
        }

        .shift-summary div {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            color: var(--text-secondary);
        }

        .shift-summary div:not(:last-child) {
            border-bottom: 1px solid #e2e8f0;
        }

        .shift-summary div strong {
            color: var(--text-primary);
            font-weight: 700;
        }

        .modal-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }

        .modal-btn {
            flex: 1;
            padding: 1rem 1.5rem;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
        }

        .btn-cancel {
            background: white;
            color: var(--text-primary);
            border: 2px solid var(--border-color);
        }

        .btn-cancel:hover {
            background: var(--light-bg);
        }

        .btn-confirm {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
        }

        .btn-confirm:hover {
            transform: translateY(-2px);
        }

        /* Announcement Popup Styles */
        .announcement-popup-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.75);
            backdrop-filter: blur(8px);
            z-index: 9999;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.4s ease;
        }

        .announcement-popup-overlay.active {
            display: flex;
        }

        .announcement-popup-container {
            background: white;
            border-radius: 24px;
            max-width: 650px;
            width: 90%;
            max-height: 85vh;
            overflow-y: auto;
            position: relative;
            box-shadow: 0 25px 70px rgba(0, 0, 0, 0.4);
            animation: slideUp 0.5s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .popup-close-btn {
            position: absolute;
            top: 20px;
            right: 20px;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: none;
            background: rgba(0, 0, 0, 0.1);
            color: var(--text-secondary);
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10;
        }

        .popup-close-btn:hover {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            transform: rotate(90deg);
        }

        .popup-header {
            text-align: center;
            padding: 3rem 2rem 2rem;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-radius: 24px 24px 0 0;
        }

        .popup-icon {
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            margin-bottom: 1rem;
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        .popup-header h2 {
            font-size: 1.75rem;
            margin-bottom: 0.5rem;
            font-weight: 700;
        }

        .popup-header p {
            font-size: 0.95rem;
            opacity: 0.9;
        }

        .announcements-slider {
            position: relative;
            padding: 2rem;
        }

        .announcement-slide {
            display: none;
            animation: fadeInSlide 0.5s ease;
        }

        .announcement-slide.active {
            display: block;
        }

        @keyframes fadeInSlide {
            from {
                opacity: 0;
                transform: translateX(20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .announcement-priority-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-size: 0.75rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            letter-spacing: 0.5px;
        }

        .announcement-priority-badge.priority-high {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            color: #991b1b;
        }

        .announcement-priority-badge.priority-medium {
            background: linear-gradient(135deg, #fed7aa, #fde68a);
            color: #c2410c;
        }

        .announcement-priority-badge.priority-low {
            background: linear-gradient(135deg, #dcfce7, #d1fae5);
            color: #15803d;
        }

        .announcement-popup-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 1rem;
            line-height: 1.3;
        }

        .announcement-popup-message {
            color: #475569;
            line-height: 1.8;
            font-size: 1rem;
            margin-bottom: 1.5rem;
            padding: 1.25rem;
            background: #f8fafc;
            border-radius: 12px;
            border-left: 4px solid #667eea;
        }

        .announcement-popup-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 1.5rem;
            padding: 1rem 0;
            border-top: 1px solid #e2e8f0;
            border-bottom: 1px solid #e2e8f0;
            margin-bottom: 1.5rem;
        }

        .announcement-popup-meta .meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #64748b;
            font-size: 0.85rem;
        }

        .announcement-popup-meta .meta-item i {
            color: #667eea;
            font-size: 1rem;
        }

        .popup-comments-section {
            margin-bottom: 1.5rem;
        }

        .popup-comments-section h4 {
            font-size: 1rem;
            color: #1e293b;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .popup-comments-section h4 i {
            color: #667eea;
        }

        .popup-comments-list {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            max-height: 300px;
            overflow-y: auto;
            padding-right: 0.5rem;
        }

        .popup-comments-list::-webkit-scrollbar {
            width: 5px;
        }

        .popup-comments-list::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 3px;
        }

        .popup-comment-item {
            display: flex;
            gap: 0.75rem;
            padding: 0.75rem;
            background: #f8fafc;
            border-radius: 10px;
        }

        .comment-avatar {
            flex-shrink: 0;
        }

        .comment-avatar img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid white;
        }

        .avatar-placeholder {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.85rem;
            font-weight: 700;
        }

        .comment-content {
            flex: 1;
        }

        .comment-content strong {
            color: #1e293b;
            font-size: 0.9rem;
            display: block;
            margin-bottom: 0.25rem;
        }

        .comment-content p {
            color: #64748b;
            font-size: 0.85rem;
            margin: 0;
            line-height: 1.5;
        }

        .popup-comment-form {
            margin-top: 1.5rem;
        }

        .comment-input-group {
            display: flex;
            gap: 0.75rem;
        }

        .comment-input-group input {
            flex: 1;
            padding: 0.75rem 1rem;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .comment-input-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .comment-input-group button {
            padding: 0.75rem 1.5rem;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .comment-input-group button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(102, 126, 234, 0.3);
        }

        .slider-controls {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 1.5rem;
            padding: 1.5rem 2rem;
            border-top: 1px solid #e2e8f0;
        }

        .slider-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 2px solid #e2e8f0;
            background: white;
            color: var(--text-secondary);
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
        }

        .slider-btn:hover {
            background: #667eea;
            color: white;
            border-color: #667eea;
            transform: scale(1.1);
        }

        .slider-dots {
            display: flex;
            gap: 0.5rem;
        }

        .dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #e2e8f0;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .dot.active {
            background: #667eea;
            width: 30px;
            border-radius: 5px;
        }

        .popup-footer {
            padding: 1.5rem 2rem;
            background: #f8fafc;
            border-radius: 0 0 24px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .dont-show-again {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            font-size: 0.85rem;
            color: var(--text-secondary);
        }

        .dont-show-again input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        .popup-action-btn {
            padding: 0.75rem 2rem;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .popup-action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(102, 126, 234, 0.3);
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @media (max-width: 992px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }

            .main-content {
                padding: 1rem;
                max-height: none;
            }

            .announcement-popup-container {
                width: 95%;
                max-height: 90vh;
            }

            .slider-controls {
                flex-wrap: wrap;
            }
        }
    </style>
</head>
<body>
    <?php include_once 'includes/sidebar.php'; ?>

    <div class="page-container">
        <div class="main-content">
            <?php include_once 'includes/employee_header.php'; ?>

            <div class="dashboard-grid">
                <div class="dashboard-left">
                <div class="company-card">
    <div class="company-header">
        <i class="bi bi-building-fill"></i>
        <h3>Trusting Social AI Philippines</h3>
    </div>
    
    <div class="time-display">
        <div class="time-section">
            <div class="time-label">Current Time</div>
            <div class="current-time" id="current-time">--:--:--</div>
        </div>
        
        <div class="duration-section">
            <div class="shift-info">
                <i class="bi bi-clock-history"></i>
                <span id="duration-text">Shift Duration: 00h 00m 00s</span>
            </div>
        </div>
        
        <button class="checkout-btn" id="attendance-btn" type="button">
            <i class="bi bi-box-arrow-in-right"></i> Loading...
        </button>
    </div>
</div>

                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="bi bi-graph-up"></i>
                                Status Report
                            </h3>
                            <p class="card-subtitle">Your activity overview</p>
                        </div>
                        <div class="status-grid">
                            <div class="status-item">
                                <div class="status-count"><?php echo $status_counts['days'] ?? 0; ?></div>
                                <div class="status-label">Days Active</div>
                            </div>
                            <div class="status-item completed">
                                <div class="status-count"><?php echo $status_counts['completed'] ?? 0; ?></div>
                                <div class="status-label">Completed</div>
                            </div>
                            <div class="status-item in-progress">
                                <div class="status-count"><?php echo $status_counts['in_progress'] ?? 0; ?></div>
                                <div class="status-label">In Progress</div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="bi bi-chat-dots"></i>
                                Previous Conversations
                            </h3>
                            <p class="card-subtitle">Recent chat with AI assistant</p>
                        </div>
                        <div class="chat-history" id="chat-history">
                            <?php if (!empty($chat_history)): ?>
                                <?php foreach ($chat_history as $msg): ?>
                                    <div class="chat-message <?php echo $msg['is_bot'] ? 'bot' : 'user'; ?>">
                                        <strong><?php echo $msg['is_bot'] ? 'AI Assistant' : 'You'; ?></strong>
                                        <?php echo htmlspecialchars($msg['message']); ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="chat-message bot">
                                    <strong>AI Assistant</strong>
                                    No previous conversations. Start chatting with the AI assistant!
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="dashboard-right">
                    <div class="card team-member-card">
                        <div class="member-profile">
                            <?php if (!empty($user['photo'])): ?>
                                <img src="<?php echo htmlspecialchars($user['photo']); ?>" alt="<?php echo htmlspecialchars($user['full_name'] ?? 'Employee'); ?>" class="avatar-image">
                            <?php else: ?>
                                <div class="avatar-circle">
                                    <?php echo strtoupper(substr($user['full_name'] ?? 'AA', 0, 2)); ?>
                                </div>
                            <?php endif; ?>
                            
                            <h3 class="member-name"><?php echo htmlspecialchars($user['full_name'] ?? 'Employee Name'); ?></h3>
                            <p class="member-role"><?php echo htmlspecialchars($user['department'] ?? 'Department'); ?></p>
                            <a href="mailto:<?php echo htmlspecialchars($user['email'] ?? ''); ?>" class="member-email">
                                <?php echo htmlspecialchars($user['email'] ?? 'email@example.com'); ?>
                            </a>
                            <p class="member-joined">
                                <i class="bi bi-calendar-event"></i>
                                Joined: <?php echo date('F Y', strtotime($user['created_at'] ?? 'now')); ?>
                            </p>
                        </div>
                        
                        <div class="member-actions">
                            <button class="action-btn" onclick="window.location.href='mailto:<?php echo htmlspecialchars($user['email'] ?? ''); ?>'">
                                <i class="bi bi-envelope-fill"></i>
                            </button>
                            <button class="action-btn" onclick="alert('Message feature coming soon!')">
                                <i class="bi bi-chat-dots-fill"></i>
                            </button>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="bi bi-megaphone-fill"></i>
                                Announcements
                            </h3>
                            <p class="card-subtitle">Latest updates from HR</p>
                        </div>
                        <?php if (!empty($announcements)): ?>
                            <?php foreach ($announcements as $announcement): ?>
                                <div class="event-item" onclick="openAnnouncementModal(<?php echo htmlspecialchars(json_encode($announcement)); ?>)" style="cursor: pointer; border-left-color: <?php echo $announcement['priority'] === 'high' ? '#ef4444' : ($announcement['priority'] === 'medium' ? '#f59e0b' : '#10b981'); ?>;">
                                    <strong style="display: block; color: var(--text-primary); margin-bottom: 0.25rem;">
                                        <?php echo htmlspecialchars($announcement['title']); ?>
                                    </strong>
                                    <div style="color: var(--text-secondary); font-size: 0.85rem; margin-bottom: 0.5rem;">
                                        <?php echo htmlspecialchars(substr($announcement['message'], 0, 80)); ?>...
                                    </div>
                                    <div style="color: var(--text-secondary); font-size: 0.75rem;">
                                        <i class="bi bi-calendar"></i> <?php echo date('M d, Y g:ia', strtotime($announcement['created_at'])); ?>
                                        <?php if (!empty($announcement['comments'])): ?>
                                            <span style="margin-left: 1rem;"><i class="bi bi-chat-dots"></i> <?php echo count($announcement['comments']); ?> comments</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="text-align: center; color: var(--text-secondary); padding: 2rem 0;">
                                <i class="bi bi-inbox" style="font-size: 2rem; display: block; margin-bottom: 0.5rem; opacity: 0.3;"></i>
                                No announcements
                            </p>
                        <?php endif; ?>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="bi bi-calendar3"></i>
                                Calendar
                            </h3>
                        </div>
                        <div class="calendar-header">
                            <select id="month-select">
                                <option>November</option>
                            </select>
                            <select id="year-select">
                                <option>2025</option>
                            </select>
                        </div>
                        <div id="calendar">
                            <i class="bi bi-calendar-week" style="font-size: 2rem; opacity: 0.3;"></i>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="bi bi-bell"></i>
                                Upcoming Events
                            </h3>
                            <p class="card-subtitle">Recent tickets and tasks</p>
                        </div>
                        <?php if (!empty($recent_tickets)): ?>
                            <?php foreach ($recent_tickets as $ticket): ?>
                                <div class="event-item">
                                    <div class="event-date">
                                        <?php echo date('M d', strtotime($ticket['created_at'])); ?><br>
                                        <small><?php echo date('g:ia', strtotime($ticket['created_at'])); ?></small>
                                    </div>
                                    <div class="event-details">
                                        <strong><?php echo htmlspecialchars($ticket['title']); ?></strong>
                                        <div><?php echo htmlspecialchars($ticket['category_id'] ?? 'General'); ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="text-align: center; color: var(--text-secondary); padding: 2rem 0;">
                                <i class="bi bi-inbox" style="font-size: 2rem; display: block; margin-bottom: 0.5rem;"></i>
                                No upcoming events
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Logout Modal -->
    <div class="modal-overlay" id="logoutModal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-icon">
                    <i class="bi bi-box-arrow-right"></i>
                </div>
                <h3>Confirm Check Out</h3>
                <p>Are you sure you want to end your shift and log out?</p>
            </div>

            <div class="shift-summary">
                <div>
                    <span><i class="bi bi-clock-history"></i> Login Time</span>
                    <strong id="login-time-display">--:--:--</strong>
                </div>
                <div>
                    <span><i class="bi bi-clock"></i> Current Time</span>
                    <strong id="current-time-display">--:--:--</strong>
                </div>
                <div>
                    <span><i class="bi bi-stopwatch"></i> Total Duration</span>
                    <strong id="total-duration-display">--h --m --s</strong>
                </div>
            </div>

            <div class="modal-actions">
                <button class="modal-btn btn-cancel" onclick="hideLogoutModal()">
                    <i class="bi bi-x-circle"></i> Cancel
                </button>
                <button class="modal-btn btn-confirm" onclick="confirmLogout()">
                    <i class="bi bi-check-circle"></i> Confirm Check Out
                </button>
            </div>
        </div>
    </div>

    <!-- Auto-Popup Announcement Modal (Page Load) -->
    <?php if (!empty($announcements)): ?>
    <div class="announcement-popup-overlay" id="announcementPopup">
        <div class="announcement-popup-container">
            <button class="popup-close-btn" onclick="closeAnnouncementPopup()">
                <i class="bi bi-x-lg"></i>
            </button>
            
            <div class="popup-header">
                <div class="popup-icon">
                    <i class="bi bi-megaphone-fill"></i>
                </div>
                <h2>Latest Announcements</h2>
                <p>Stay updated with the latest news from HR</p>
            </div>

            <div class="announcements-slider">
                <?php foreach ($announcements as $index => $announcement): ?>
                    <div class="announcement-slide <?php echo $index === 0 ? 'active' : ''; ?>" data-slide="<?php echo $index; ?>">
                        <div class="announcement-priority-badge priority-<?php echo $announcement['priority']; ?>">
                            <i class="bi bi-<?php echo $announcement['priority'] === 'high' ? 'exclamation-triangle-fill' : ($announcement['priority'] === 'medium' ? 'info-circle-fill' : 'check-circle-fill'); ?>"></i>
                            <?php echo strtoupper($announcement['priority']); ?> PRIORITY
                        </div>
                        
                        <h3 class="announcement-popup-title">
                            <?php echo htmlspecialchars($announcement['title']); ?>
                        </h3>
                        
                        <div class="announcement-popup-message">
                            <?php echo nl2br(htmlspecialchars($announcement['message'])); ?>
                        </div>
                        
                        <div class="announcement-popup-meta">
                            <div class="meta-item">
                                <i class="bi bi-calendar3"></i>
                                <?php echo date('F d, Y', strtotime($announcement['created_at'])); ?>
                            </div>
                            <div class="meta-item">
                                <i class="bi bi-clock"></i>
                                <?php echo date('g:i A', strtotime($announcement['created_at'])); ?>
                            </div>
                            <?php if (!empty($announcement['expires_at'])): ?>
                                <div class="meta-item">
                                    <i class="bi bi-hourglass-split"></i>
                                    Expires: <?php echo date('M d, Y', strtotime($announcement['expires_at'])); ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($announcement['comments'])): ?>
                            <div class="popup-comments-section">
                                <h4><i class="bi bi-chat-dots-fill"></i> Recent Comments</h4>
                                <div class="popup-comments-list">
                                    <?php foreach (array_slice($announcement['comments'], 0, 2) as $comment): ?>
                                        <div class="popup-comment-item">
                                            <div class="comment-avatar">
                                                <?php if (!empty($comment['photo'])): ?>
                                                    <img src="<?php echo htmlspecialchars($comment['photo']); ?>" alt="<?php echo htmlspecialchars($comment['full_name']); ?>">
                                                <?php else: ?>
                                                    <div class="avatar-placeholder">
                                                        <?php echo strtoupper(substr($comment['full_name'], 0, 2)); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="comment-content">
                                                <strong><?php echo htmlspecialchars($comment['full_name']); ?></strong>
                                                <p><?php echo htmlspecialchars($comment['comment']); ?></p>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="popup-comment-form">
                            <form method="POST" action="">
                                <input type="hidden" name="announcement_id" value="<?php echo $announcement['id']; ?>">
                                <div class="comment-input-group">
                                    <input type="text" name="comment" placeholder="Add your comment..." required>
                                    <button type="submit" name="add_comment">
                                        <i class="bi bi-send-fill"></i>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if (count($announcements) > 1): ?>
                <div class="slider-controls">
                    <button class="slider-btn prev" onclick="previousSlide()">
                        <i class="bi bi-chevron-left"></i>
                    </button>
                    <div class="slider-dots">
                        <?php foreach ($announcements as $index => $announcement): ?>
                            <span class="dot <?php echo $index === 0 ? 'active' : ''; ?>" onclick="goToSlide(<?php echo $index; ?>)"></span>
                        <?php endforeach; ?>
                    </div>
                    <button class="slider-btn next" onclick="nextSlide()">
                        <i class="bi bi-chevron-right"></i>
                    </button>
                </div>
            <?php endif; ?>

            <div class="popup-footer">
                <label class="dont-show-again">
                    <input type="checkbox" id="dontShowAgain">
                    <span>Don't show this again today</span>
                </label>
                <button class="popup-action-btn" onclick="closeAnnouncementPopup()">
                    Got it!
                </button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Click to View Announcement Modal -->
    <div class="announcement-popup-overlay" id="clickAnnouncementModal" style="display: none;">
        <div class="announcement-popup-container">
            <button class="popup-close-btn" onclick="closeClickModal()">
                <i class="bi bi-x-lg"></i>
            </button>
            
            <div class="popup-header">
                <div class="popup-icon">
                    <i class="bi bi-megaphone-fill"></i>
                </div>
                <h2 id="modal-announcement-title"></h2>
            </div>

            <div class="announcements-slider" style="padding: 2rem;">
                <div id="modal-priority-badge"></div>
                
                <div class="announcement-popup-message" id="modal-announcement-message"></div>
                
                <div class="announcement-popup-meta" id="modal-announcement-meta"></div>

                <div class="popup-comments-section" id="modal-comments-section"></div>

                <div class="popup-comment-form">
                    <form method="POST" action="">
                        <input type="hidden" name="announcement_id" id="modal-announcement-id">
                        <div class="comment-input-group">
                            <input type="text" name="comment" placeholder="Add your comment..." required>
                            <button type="submit" name="add_comment">
                                <i class="bi bi-send-fill"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="popup-footer" style="justify-content: flex-end;">
                <button class="popup-action-btn" onclick="closeClickModal()">
                    Close
                </button>
            </div>
        </div>
    </div>
    <script>
// ==================== ATTENDANCE SYSTEM ====================
let isCheckedIn = false;
let loginTime = null;
let totalHoursToday = 0;
let timeInterval = null;

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    console.log('Page loaded, initializing attendance...');
    checkAttendanceStatus();
});

// Check attendance status
function checkAttendanceStatus() {
    fetch('attendance_handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=get_status'
    })
    .then(response => response.json())
    .then(data => {
        console.log('Status:', data);
        
        totalHoursToday = parseFloat(data.total_hours) || 0;
        
        if (data.success && data.checked_in) {
            // Currently checked in
            isCheckedIn = true;
            loginTime = data.timestamp * 1000;
            showCheckOutButton();
            startTimeTracking();
        } else {
            // Not checked in
            showCheckInButton();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showCheckInButton();
    });
}

// Show Check In button
function showCheckInButton() {
    const btn = document.getElementById('attendance-btn');
    btn.innerHTML = '<i class="bi bi-box-arrow-in-right"></i> Check In';
    btn.onclick = handleCheckIn;
    
    // Show current time
    updateClock();
    if (timeInterval) clearInterval(timeInterval);
    timeInterval = setInterval(updateClock, 1000);
    
    // ALWAYS show time duration format (not "Not checked in yet")
    const durationText = document.getElementById('duration-text');
    if (totalHoursToday > 0) {
        const hours = Math.floor(totalHoursToday);
        const minutes = Math.floor((totalHoursToday - hours) * 60);
        durationText.textContent = `Total Duration: ${String(hours).padStart(2, '0')}h ${String(minutes).padStart(2, '0')}m 00s`;
    } else {
        durationText.textContent = 'Shift Duration: 00h 00m 00s';
    }
}

// Show Check Out button
function showCheckOutButton() {
    const btn = document.getElementById('attendance-btn');
    btn.innerHTML = '<i class="bi bi-box-arrow-right"></i> Check Out';
    btn.onclick = showLogoutModal;
}

// Handle Check In
function handleCheckIn() {
    console.log('Checking in...');
    
    fetch('attendance_handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=check_in'
    })
    .then(response => response.json())
    .then(data => {
        console.log('Check-in response:', data);
        
        if (data.success) {
            isCheckedIn = true;
            loginTime = data.timestamp * 1000;
            
            const time = new Date(loginTime).toLocaleTimeString();
            alert(`✅ Checked In Successfully!\n\nTime: ${time}\n\nHave a productive day!`);
            
            showCheckOutButton();
            startTimeTracking();
        } else {
            alert('❌ ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to check in. Please try again.');
    });
}

// Start time tracking
function startTimeTracking() {
    if (timeInterval) clearInterval(timeInterval);
    updateTime();
    timeInterval = setInterval(updateTime, 1000);
}

// Update clock only
function updateClock() {
    const now = new Date();
    const h = String(now.getHours()).padStart(2, '0');
    const m = String(now.getMinutes()).padStart(2, '0');
    const s = String(now.getSeconds()).padStart(2, '0');
    document.getElementById('current-time').textContent = `${h}:${m}:${s}`;
}

// Update time and duration
function updateTime() {
    updateClock();
    
    if (loginTime && isCheckedIn) {
        const now = Date.now();
        const diff = now - loginTime;
        
        const hours = Math.floor(diff / (1000 * 60 * 60));
        const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((diff % (1000 * 60)) / 1000);
        
        document.getElementById('duration-text').textContent = 
            `Shift Duration: ${String(hours).padStart(2, '0')}h ${String(minutes).padStart(2, '0')}m ${String(seconds).padStart(2, '0')}s`;
    }
}

// Show logout modal
function showLogoutModal() {
    if (!loginTime) {
        alert('No check-in record found!');
        return;
    }
    
    const modal = document.getElementById('logoutModal');
    const checkInTime = new Date(loginTime);
    const now = new Date();
    const diff = now - loginTime;
    
    const h1 = String(checkInTime.getHours()).padStart(2, '0');
    const m1 = String(checkInTime.getMinutes()).padStart(2, '0');
    const s1 = String(checkInTime.getSeconds()).padStart(2, '0');
    document.getElementById('login-time-display').textContent = `${h1}:${m1}:${s1}`;
    
    const h2 = String(now.getHours()).padStart(2, '0');
    const m2 = String(now.getMinutes()).padStart(2, '0');
    const s2 = String(now.getSeconds()).padStart(2, '0');
    document.getElementById('current-time-display').textContent = `${h2}:${m2}:${s2}`;
    
    const hours = Math.floor(diff / (1000 * 60 * 60));
    const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
    const seconds = Math.floor((diff % (1000 * 60)) / 1000);
    document.getElementById('total-duration-display').textContent = 
        `${String(hours).padStart(2, '0')}h ${String(minutes).padStart(2, '0')}m ${String(seconds).padStart(2, '0')}s`;
    
    modal.classList.add('active');
}

// Hide logout modal
function hideLogoutModal() {
    document.getElementById('logoutModal').classList.remove('active');
}

// Confirm checkout
function confirmLogout() {
    fetch('attendance_handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=check_out'
    })
    .then(response => response.json())
    .then(data => {
        console.log('Check-out response:', data);
        
        if (data.success) {
            hideLogoutModal();
            
            alert(`✅ Checked Out Successfully!\n\nCheck-in: ${data.check_in_time}\nCheck-out: ${data.check_out_time}\nDuration: ${data.duration}\nHours: ${data.hours_worked}h\n\nYou can check in again anytime!`);
            
            totalHoursToday = parseFloat(data.hours_worked) + totalHoursToday;
            isCheckedIn = false;
            loginTime = null;
            
            showCheckInButton();
        } else {
            alert('❌ ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to check out.');
    });
}

// Close modal on outside click
document.getElementById('logoutModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        hideLogoutModal();
    }
});

    // Notification Helper
    function showNotification(title, message) {
        alert(title + '\n\n' + message);
    }

    // Initialize on page load
    initializeAttendance();

    // Notification Helper
    function showNotification(title, message) {
        // You can implement a toast notification here
        alert(title + '\n\n' + message);
    }

    // Initialize on page load
    initializeAttendance();

    // Auto-Popup Announcement Functions (Page Load)
    let currentSlide = 0;
    const totalSlides = <?php echo count($announcements); ?>;

    window.addEventListener('load', function() {
        const dontShow = localStorage.getItem('hideAnnouncementsToday');
        const today = new Date().toDateString();
        
        if (dontShow !== today && totalSlides > 0) {
            setTimeout(() => {
                document.getElementById('announcementPopup').classList.add('active');
            }, 1000);
        }
    });

    function closeAnnouncementPopup() {
        const dontShow = document.getElementById('dontShowAgain').checked;
        if (dontShow) {
            localStorage.setItem('hideAnnouncementsToday', new Date().toDateString());
        }
        document.getElementById('announcementPopup').classList.remove('active');
    }

    function goToSlide(index) {
        const slides = document.querySelectorAll('.announcement-slide');
        const dots = document.querySelectorAll('.dot');
        
        slides[currentSlide].classList.remove('active');
        dots[currentSlide].classList.remove('active');
        
        currentSlide = index;
        
        slides[currentSlide].classList.add('active');
        dots[currentSlide].classList.add('active');
    }

    function nextSlide() {
        goToSlide((currentSlide + 1) % totalSlides);
    }

    function previousSlide() {
        goToSlide((currentSlide - 1 + totalSlides) % totalSlides);
    }

    document.getElementById('announcementPopup')?.addEventListener('click', function(e) {
        if (e.target === this) {
            closeAnnouncementPopup();
        }
    });

    // Click to View Announcement Modal
    function openAnnouncementModal(announcement) {
        const modal = document.getElementById('clickAnnouncementModal');
        
        document.getElementById('modal-announcement-title').textContent = announcement.title;
        
        const priorityColors = {
            'high': 'priority-high',
            'medium': 'priority-medium',
            'low': 'priority-low'
        };
        const priorityIcons = {
            'high': 'exclamation-triangle-fill',
            'medium': 'info-circle-fill',
            'low': 'check-circle-fill'
        };
        
        document.getElementById('modal-priority-badge').innerHTML = `
            <div class="announcement-priority-badge ${priorityColors[announcement.priority]}">
                <i class="bi bi-${priorityIcons[announcement.priority]}"></i>
                ${announcement.priority.toUpperCase()} PRIORITY
            </div>
        `;
        
        document.getElementById('modal-announcement-message').innerHTML = announcement.message.replace(/\n/g, '<br>');
        
        let metaHTML = `
            <div class="meta-item">
                <i class="bi bi-calendar3"></i>
                Posted: ${new Date(announcement.created_at).toLocaleDateString('en-US', {
                    month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: 'numeric'
                })}
            </div>
        `;
        
        if (announcement.expires_at) {
            metaHTML += `
                <div class="meta-item">
                    <i class="bi bi-hourglass-split"></i>
                    Expires: ${new Date(announcement.expires_at).toLocaleDateString('en-US', {
                        month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: 'numeric'
                    })}
                </div>
            `;
        }
        
        metaHTML += `
            <div class="meta-item">
                <i class="bi bi-send"></i>
                Sent via: ${announcement.send_email ? 'Email' : 'SMS'}
            </div>
        `;
        
        document.getElementById('modal-announcement-meta').innerHTML = metaHTML;
        
        const commentsSection = document.getElementById('modal-comments-section');
        if (announcement.comments && announcement.comments.length > 0) {
            let commentsHTML = '<h4><i class="bi bi-chat-dots-fill"></i> Comments</h4><div class="popup-comments-list">';
            
            announcement.comments.forEach(comment => {
                const avatarHTML = comment.photo ? 
                    `<img src="${comment.photo}" alt="${comment.full_name}">` :
                    `<div class="avatar-placeholder">${comment.full_name.substring(0, 2).toUpperCase()}</div>`;
                
                commentsHTML += `
                    <div class="popup-comment-item">
                        <div class="comment-avatar">
                            ${avatarHTML}
                        </div>
                        <div class="comment-content">
                            <strong>${comment.full_name}</strong>
                            <p>${comment.comment}</p>
                        </div>
                    </div>
                `;
            });
            
            commentsHTML += '</div>';
            commentsSection.innerHTML = commentsHTML;
        } else {
            commentsSection.innerHTML = '<p style="text-align: center; color: var(--text-secondary); padding: 1rem;">No comments yet. Be the first to comment!</p>';
        }
        
        document.getElementById('modal-announcement-id').value = announcement.id;
        modal.style.display = 'flex';
    }

    function closeClickModal() {
        document.getElementById('clickAnnouncementModal').style.display = 'none';
    }

    document.getElementById('clickAnnouncementModal')?.addEventListener('click', function(e) {
        if (e.target === this) {
            closeClickModal();
        }
    });

    // Chatbot Functions
    document.getElementById('chatbot-toggle')?.addEventListener('click', function() {
        document.getElementById('chatbot-window').classList.add('active');
    });

    document.getElementById('close-chatbot')?.addEventListener('click', function() {
        document.getElementById('chatbot-window').classList.remove('active');
    });

    document.getElementById('send-message')?.addEventListener('click', sendMessage);
    document.getElementById('chatbot-input')?.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') sendMessage();
    });

    function sendMessage() {
        const input = document.getElementById('chatbot-input');
        const message = input.value.trim();
        if (!message) return;

        const formData = new FormData();
        formData.append('chat_message', message);
        formData.append('ajax', '1');

        fetch('', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            const messagesDiv = document.getElementById('chatbot-messages');
            messagesDiv.innerHTML = '';
            data.history.forEach(msg => {
                const div = document.createElement('div');
                div.className = `chat-message ${msg.sender === 'bot' ? 'bot' : 'user'}`;
                div.innerHTML = `<strong>${msg.sender === 'bot' ? 'AI Assistant' : 'You'}</strong>${msg.message}`;
                messagesDiv.appendChild(div);
            });
            messagesDiv.scrollTop = messagesDiv.scrollHeight;
            input.value = '';
        });
    }

    document.querySelectorAll('.quick-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('chatbot-input').value = this.dataset.msg;
            sendMessage();
        });
    });
</script>

    <script src="assets/js/dashboard.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</body>
</html>
