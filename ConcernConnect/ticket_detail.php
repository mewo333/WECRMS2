<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

$db = new Database();
$conn = $db->connect();

$ticket_id = $_GET['id'] ?? 0;
$user = getEmployeeData($conn, $_SESSION['employee_id']);
$success = '';
$error = '';

$stmt = $conn->prepare("SELECT t.*, tc.category_name FROM tickets t LEFT JOIN ticket_categories tc ON t.category_id = tc.id WHERE t.id = :id AND t.employee_id = :employee_id");
$stmt->execute(['id' => $ticket_id, 'employee_id' => $_SESSION['employee_id']]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ticket) {
    header('Location: tickets.php');
    exit();
}

// Fetch ticket responses with HR admin details (CORRECTED FOR YOUR DATABASE)
$stmt = $conn->prepare("
    SELECT tr.*, e.full_name as responder_name, e.photo
    FROM ticket_responses tr
    LEFT JOIN employees e ON tr.employee_id = e.employee_id
    WHERE tr.ticket_id = :ticket_id AND tr.is_internal = 0
    ORDER BY tr.created_at ASC
");
$stmt->execute(['ticket_id' => $ticket_id]);
$responses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get status color
function getStatusColor($status) {
    switch(strtolower($status)) {
        case 'pending': return '#fbbf24';
        case 'in progress': return '#3b82f6';
        case 'resolved': return '#10b981';
        case 'closed': return '#6b7280';
        default: return '#9ca3af';
    }
}

// Get priority color
function getPriorityColor($priority) {
    switch(strtolower($priority)) {
        case 'high': return '#ef4444';
        case 'medium': return '#f97316';
        case 'low': return '#10b981';
        default: return '#9ca3af';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            color: #333;
        }

        .main-content {
            padding: 0;
            min-height: 100vh;
        }

        .ticket-container {
            padding: 2rem 3rem;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 2px solid #e2e8f0;
        }

        .page-title {
            font-size: 28px;
            font-weight: 700;
            color: #2d3748;
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0;
        }

        .back-btn {
            background: white;
            border: 2px solid #e2e8f0;
            color: #667eea;
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .back-btn:hover {
            background: #f0f4ff;
            border-color: #667eea;
            color: #667eea;
        }

        .alert {
            border-radius: 12px;
            border: none;
            margin-bottom: 2rem;
            padding: 1rem 1.5rem;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
        }

        /* Ticket Detail Card */
        .ticket-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            overflow: hidden;
            margin-bottom: 2rem;
        }

        .ticket-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: start;
        }

        .ticket-header-left h2 {
            margin: 0 0 0.5rem 0;
            font-size: 24px;
            font-weight: 700;
        }

        .ticket-number {
            font-size: 14px;
            opacity: 0.9;
        }

        .ticket-badges {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .badge-custom {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .status-badge {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        /* Ticket Content */
        .ticket-body {
            padding: 2rem;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
            padding-bottom: 2rem;
            border-bottom: 2px solid #e2e8f0;
        }

        .info-item {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .info-label {
            font-size: 12px;
            text-transform: uppercase;
            color: #718096;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .info-value {
            font-size: 16px;
            font-weight: 600;
            color: #2d3748;
        }

        .priority-high {
            color: #ef4444;
        }

        .priority-medium {
            color: #f97316;
        }

        .priority-low {
            color: #10b981;
        }

        /* Description Section */
        .description-section {
            margin-bottom: 2rem;
        }

        .section-title {
            font-size: 18px;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .description-box {
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 1.5rem;
            line-height: 1.7;
            color: #475569;
        }

        /* HR Responses Section */
        .responses-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            overflow: hidden;
        }

        .responses-header {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 1.5rem 2rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .responses-header h3 {
            margin: 0;
            font-size: 20px;
            font-weight: 700;
        }

        .responses-count {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }

        .responses-body {
            padding: 2rem;
        }

        .response-item {
            display: flex;
            gap: 1.5rem;
            margin-bottom: 2rem;
            padding-bottom: 2rem;
            border-bottom: 2px solid #e2e8f0;
            animation: fadeInUp 0.5s ease;
        }

        .response-item:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .response-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
            font-weight: 700;
            flex-shrink: 0;
            overflow: hidden;
        }

        .response-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .response-content {
            flex: 1;
        }

        .response-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.75rem;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .response-author {
            font-size: 16px;
            font-weight: 700;
            color: #2d3748;
        }

        .response-role {
            font-size: 12px;
            color: #718096;
            background: #e2e8f0;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-weight: 600;
            margin-left: 0.5rem;
        }

        .response-date {
            font-size: 13px;
            color: #94a3b8;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .response-message {
            background: #f8fafc;
            border-left: 4px solid #667eea;
            padding: 1.25rem;
            border-radius: 8px;
            line-height: 1.7;
            color: #475569;
            font-size: 15px;
        }

        .empty-responses {
            text-align: center;
            padding: 3rem 2rem;
            color: #94a3b8;
        }

        .empty-responses i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .empty-responses p {
            font-size: 16px;
            margin: 0;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .ticket-container {
                padding: 1.5rem;
            }

            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            .ticket-header {
                flex-direction: column;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .response-item {
                flex-direction: column;
            }

            .response-avatar {
                width: 40px;
                height: 40px;
                font-size: 16px;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="ticket-container">
            <!-- Page Header -->
            <div class="page-header">
                <h1 class="page-title">
                    <i class="bi bi-ticket-detailed"></i> Ticket Details
                </h1>
                <a href="status_update.php" class="back-btn">
                    <i class="bi bi-arrow-left"></i> Back to Status Update
                </a>
            </div>

            <!-- Success Alert -->
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="bi bi-check-circle-fill"></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <!-- Ticket Card -->
            <div class="ticket-card">
                <!-- Header -->
                <div class="ticket-header">
                    <div class="ticket-header-left">
                        <h2><?php echo htmlspecialchars($ticket['title']); ?></h2>
                        <p class="ticket-number">
                            <i class="bi bi-tag"></i> <?php echo htmlspecialchars($ticket['ticket_number']); ?>
                        </p>
                    </div>
                    <div class="ticket-badges">
                        <div class="badge-custom status-badge">
                            <i class="bi bi-circle-fill"></i>
                            <?php echo strtoupper(str_replace('_', ' ', $ticket['status'])); ?>
                        </div>
                    </div>
                </div>

                <!-- Body -->
                <div class="ticket-body">
                    <!-- Info Grid -->
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">Ticket Number</span>
                            <span class="info-value"><?php echo htmlspecialchars($ticket['ticket_number']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Category</span>
                            <span class="info-value"><?php echo htmlspecialchars($ticket['category_name'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Priority</span>
                            <span class="info-value priority-<?php echo strtolower($ticket['priority']); ?>">
                                <i class="bi bi-exclamation-circle"></i>
                                <?php echo htmlspecialchars($ticket['priority']); ?>
                            </span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Status</span>
                            <span class="info-value"><?php echo htmlspecialchars(str_replace('_', ' ', $ticket['status'])); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Created Date</span>
                            <span class="info-value"><?php echo date('M d, Y g:ia', strtotime($ticket['created_at'])); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Last Updated</span>
                            <span class="info-value"><?php echo date('M d, Y g:ia', strtotime($ticket['updated_at'])); ?></span>
                        </div>
                    </div>

                    <!-- Description Section -->
                    <div class="description-section">
                        <div class="section-title">
                            <i class="bi bi-file-text"></i> Description
                        </div>
                        <div class="description-box">
                            <?php echo nl2br(htmlspecialchars($ticket['description'])); ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- HR Responses Section -->
            <div class="responses-card">
                <div class="responses-header">
                    <h3><i class="bi bi-chat-left-text"></i> HR Responses</h3>
                    <span class="responses-count"><?php echo count($responses); ?> Response<?php echo count($responses) != 1 ? 's' : ''; ?></span>
                </div>
                <div class="responses-body">
                    <?php if (empty($responses)): ?>
                        <div class="empty-responses">
                            <i class="bi bi-chat-left-dots"></i>
                            <p>No responses yet. HR will respond to your ticket soon.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($responses as $response): ?>
                            <div class="response-item">
                                <div class="response-avatar">
                                    <?php if (!empty($response['photo']) && file_exists('assets/images/' . $response['photo'])): ?>
                                        <img src="assets/images/<?php echo htmlspecialchars($response['photo']); ?>" alt="<?php echo htmlspecialchars($response['responder_name'] ?? 'HR'); ?>">
                                    <?php else: ?>
                                        <?php echo strtoupper(substr($response['responder_name'] ?? 'HR', 0, 1)); ?>
                                    <?php endif; ?>
                                </div>
                                <div class="response-content">
                                    <div class="response-header">
                                        <div>
                                            <span class="response-author">
                                                <?php echo htmlspecialchars($response['responder_name'] ?? 'HR Admin'); ?>
                                            </span>
                                            <span class="response-role">HR Staff</span>
                                        </div>
                                        <span class="response-date">
                                            <i class="bi bi-clock"></i>
                                            <?php echo date('M d, Y g:ia', strtotime($response['created_at'])); ?>
                                        </span>
                                    </div>
                                    <div class="response-message">
                                        <?php echo nl2br(htmlspecialchars($response['response_text'])); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</body>
</html>
