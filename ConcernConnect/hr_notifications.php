<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

$db = new Database();
$conn = $db->connect();

$user = getEmployeeData($conn, $_SESSION['employee_id']);

if (!$user || $user['role'] !== 'hr_admin') {
    header('Location: dashboard.php');
    exit();
}

$success = '';
$error = '';

if (isset($_POST['send_notification'])) {
    $employee_id = $_POST['employee_id'] ?? '';
    $message = trim($_POST['message'] ?? '');
    $notification_type = $_POST['notification_type'] ?? 'general';
    $send_email = isset($_POST['send_email']) ? 1 : 0;
    $send_sms = isset($_POST['send_sms']) ? 1 : 0;
    
    if ($employee_id && $message) {
        try {
            $sent_via = [];
            if ($send_email) $sent_via[] = 'email';
            if ($send_sms) $sent_via[] = 'sms';
            
            $stmt = $conn->prepare("
                INSERT INTO notifications (employee_id, notification_type, message, sent_via, created_at) 
                VALUES (:employee_id, :type, :message, :sent_via, NOW())
            ");
            $stmt->execute([
                ':employee_id' => $employee_id,
                ':type' => $notification_type,
                ':message' => $message,
                ':sent_via' => implode(',', $sent_via)
            ]);
            
            $success = '✅ Notification sent successfully!';
            $_POST = [];
        } catch (Exception $e) {
            $error = '❌ Error sending notification: ' . $e->getMessage();
        }
    } else {
        $error = '⚠️ Please fill in all required fields.';
    }
}

// Get employees
$stmt = $conn->prepare("SELECT employee_id, full_name FROM employees WHERE role = 'employee' ORDER BY full_name");
$stmt->execute();
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get notification history from hr_notifications table with employee name
$stmt = $conn->prepare("
    SELECT n.id, n.hr_id, n.ticket_id, n.notification_type, n.message, n.is_read, n.created_at,
           t.ticket_number, t.title, t.description, t.priority, t.status, t.id as ticket_id_full, t.employee_id,
           e.full_name as hr_name,
           emp.full_name as employee_name
    FROM hr_notifications n
    LEFT JOIN tickets t ON n.ticket_id = t.id
    LEFT JOIN employees e ON n.hr_id = e.employee_id
    LEFT JOIN employees emp ON t.employee_id = emp.employee_id
    ORDER BY n.created_at DESC 
    LIMIT 100
");
$stmt->execute();
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send Notifications - HR Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
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
            margin-left: 260px;
            padding: 20px;
            min-height: 100vh;
        }

        .notification-container {
            display: grid;
            grid-template-columns: 1fr 1.5fr;
            gap: 25px;
            margin-top: 30px;
        }

        .notification-form-card {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            height: fit-content;
            position: sticky;
            top: 20px;
        }

        .notification-form-card h2 {
            font-size: 22px;
            font-weight: 600;
            margin-bottom: 25px;
            color: #2d3748;
            border-bottom: 3px solid #667eea;
            padding-bottom: 10px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-weight: 500;
            margin-bottom: 8px;
            color: #4a5568;
            font-size: 14px;
        }

        .form-control,
        select.form-control,
        textarea.form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            transition: all 0.3s ease;
        }

        .form-control:focus,
        select.form-control:focus,
        textarea.form-control:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-check-group {
            display: flex;
            flex-direction: column;
            gap: 12px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .form-check-group label {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            font-weight: 400;
            margin: 0;
            color: #4a5568;
        }

        .form-check-group input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: #667eea;
        }

        .btn-primary {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            font-weight: 600;
            font-size: 16px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .notification-history {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
        }

        .notification-history h2 {
            font-size: 22px;
            font-weight: 600;
            margin-bottom: 25px;
            color: #2d3748;
            border-bottom: 3px solid #667eea;
            padding-bottom: 10px;
        }

        .notifications-list {
            max-height: 700px;
            overflow-y: auto;
            padding-right: 10px;
        }

        .notifications-list::-webkit-scrollbar {
            width: 8px;
        }

        .notifications-list::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .notifications-list::-webkit-scrollbar-thumb {
            background: #cbd5e0;
            border-radius: 10px;
        }

        .notifications-list::-webkit-scrollbar-thumb:hover {
            background: #a0aec0;
        }

        .notification-item {
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 15px;
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .notification-item:hover {
            transform: translateX(5px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            background: #fff;
        }

        .notification-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .notification-header strong {
            font-size: 15px;
            color: #2d3748;
        }

        .notification-date {
            font-size: 13px;
            color: #718096;
        }

        .notification-item p {
            color: #4a5568;
            line-height: 1.6;
            margin-bottom: 12px;
            font-size: 14px;
        }

        .notification-meta {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .badge-info {
            background: #e0e7ff;
            color: #5a67d8;
        }

        .badge-secondary {
            background: #e2e8f0;
            color: #4a5568;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
            border-left: 4px solid;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }

        .empty-state {
            text-align: center;
            color: #718096;
            padding: 40px 20px;
        }

        .empty-state i {
            font-size: 48px;
            opacity: 0.3;
            display: block;
            margin-bottom: 15px;
        }

        /* Modal Styles */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            z-index: 10000;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-overlay.show {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 15px;
            max-width: 600px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: slideUp 0.3s ease;
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

        .modal-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: start;
            border-radius: 15px 15px 0 0;
        }

        .modal-header.urgent {
            background: linear-gradient(135deg, #ef4444, #dc2626);
        }

        .modal-header h2 {
            margin: 0 0 0.5rem 0;
            font-size: 1.5rem;
            font-weight: 700;
        }

        .modal-ticket-id {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .modal-employee-name {
            font-size: 0.85rem;
            opacity: 0.95;
            margin-top: 0.3rem;
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }

        .modal-close {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 1.5rem;
            transition: all 0.3s ease;
        }

        .modal-close:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: rotate(90deg);
        }

        .modal-body {
            padding: 2rem;
        }

        .ticket-info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: #f8fafc;
            border-radius: 12px;
            border-left: 4px solid #667eea;
        }

        .ticket-info-item {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .ticket-info-label {
            font-size: 0.75rem;
            color: #64748b;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .ticket-info-value {
            font-size: 1.1rem;
            color: #1e293b;
            font-weight: 600;
        }

        .ticket-description {
            margin-bottom: 2rem;
        }

        .ticket-description h3 {
            font-size: 1rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: #1e293b;
        }

        .ticket-description-text {
            padding: 1.5rem;
            background: linear-gradient(135deg, #f0f4ff 0%, #f9f5ff 100%);
            border-left: 4px solid #667eea;
            border-radius: 10px;
            line-height: 1.8;
            color: #334155;
            white-space: pre-wrap;
            word-wrap: break-word;
        }

        .modal-footer {
            padding: 1.5rem 2rem;
            background: #f8fafc;
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            border-top: 1px solid #e2e8f0;
            border-radius: 0 0 15px 15px;
        }

        .modal-btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .modal-btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .modal-btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
            color: white;
            text-decoration: none;
        }

        .modal-btn-secondary {
            background: white;
            color: #64748b;
            border: 1px solid #e2e8f0;
        }

        .modal-btn-secondary:hover {
            background: #f1f5f9;
        }

        .priority-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.85rem;
        }

        .priority-high {
            background: #fee2e2;
            color: #991b1b;
        }

        .priority-medium {
            background: #fef3c7;
            color: #92400e;
        }

        .priority-low {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.85rem;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-in-progress {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-resolved {
            background: #dcfce7;
            color: #166534;
        }

        .status-closed {
            background: #f3f4f6;
            color: #4b5563;
        }

        @media (max-width: 1024px) {
            .notification-container {
                grid-template-columns: 1fr;
            }

            .notification-form-card {
                position: static;
            }

            .main-content {
                margin-left: 0;
                padding: 15px;
            }
        }

        @media (max-width: 768px) {
            .notification-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .notification-date {
                width: 100%;
            }

            .modal-content {
                width: 95%;
            }

            .ticket-info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include_once 'includes/hr_sidebar.php'; ?>
    
    <div class="main-content">
        <?php include_once 'includes/hr_header.php'; ?>
        
        <div class="notification-container">
            <div class="notification-form-card">
                <h2><i class="bi bi-send-fill"></i> Send Notification</h2>
                
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle-fill"></i> <?php echo $success; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($error)): ?>
                    <div class="alert alert-error">
                        <i class="bi bi-exclamation-triangle-fill"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="employee_id"><i class="bi bi-person-fill"></i> Select Employee</label>
                        <select name="employee_id" id="employee_id" class="form-control" required>
                            <option value="">Choose an employee...</option>
                            <?php foreach ($employees as $emp): ?>
                                <option value="<?php echo htmlspecialchars($emp['employee_id']); ?>">
                                    <?php echo htmlspecialchars($emp['full_name'] . ' (' . $emp['employee_id'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="notification_type"><i class="bi bi-tag-fill"></i> Notification Type</label>
                        <select name="notification_type" id="notification_type" class="form-control">
                            <option value="general">📢 General</option>
                            <option value="announcement">📣 Announcement</option>
                            <option value="reminder">⏰ Reminder</option>
                            <option value="urgent">🚨 Urgent</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="message"><i class="bi bi-chat-dots-fill"></i> Message</label>
                        <textarea name="message" id="message" class="form-control" rows="5" placeholder="Type your message here..." required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Delivery Options</label>
                        <div class="form-check-group">
                            <label>
                                <input type="checkbox" name="send_email" value="1" checked>
                                <i class="bi bi-envelope-fill"></i> Send Email Notification
                            </label>
                            <label>
                                <input type="checkbox" name="send_sms" value="1">
                                <i class="bi bi-phone-fill"></i> Send SMS Notification
                            </label>
                        </div>
                    </div>
                    
                    <button type="submit" name="send_notification" class="btn-primary">
                        <i class="bi bi-send-fill"></i> Send Notification
                    </button>
                </form>
            </div>
            
            <div class="notification-history">
                <h2><i class="bi bi-clock-history"></i> Ticket Notifications</h2>
                <div class="notifications-list">
                    <?php if (empty($notifications)): ?>
                        <div class="empty-state">
                            <i class="bi bi-inbox"></i>
                            <p>No ticket notifications yet.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($notifications as $notif): 
                            $notification_type = $notif['notification_type'] ?? 'new_ticket';
                            $message = htmlspecialchars($notif['message'] ?? '');
                            $hr_name = htmlspecialchars($notif['hr_name'] ?? 'HR Admin');
                            $employee_name = htmlspecialchars($notif['employee_name'] ?? 'Unknown Employee');
                            $ticket_number = htmlspecialchars($notif['ticket_number'] ?? 'N/A');
                            $ticket_title = htmlspecialchars($notif['title'] ?? 'N/A');
                            $created_at = date('M d, Y g:ia', strtotime($notif['created_at'] ?? date('Y-m-d H:i:s')));
                            
                            $type_icons = [
                                'new_ticket' => '📋 New Ticket',
                                'urgent_ticket' => '🚨 Urgent Ticket'
                            ];
                            $type_display = $type_icons[$notification_type] ?? ucfirst($notification_type);
                            
                            $unique_id = 'notif_' . $notif['id'];
                        ?>
                            <div class="notification-item" onclick="openTicketModal('<?php echo $unique_id; ?>')">
                                <div class="notification-header">
                                    <div>
                                        <strong style="font-size: 15px; color: #2d3748;"><?php echo $type_display; ?></strong>
                                        <div style="font-size: 13px; color: #718096; margin-top: 5px;">
                                            <i class="bi bi-calendar3"></i> <?php echo $created_at; ?>
                                        </div>
                                    </div>
                                </div>
                                <p style="margin: 12px 0; color: #4a5568; font-size: 14px;"><strong>Ticket:</strong> <?php echo $ticket_number; ?></p>
                                <p style="color: #4a5568; font-size: 14px; margin: 8px 0;"><strong>From:</strong> <?php echo $employee_name; ?></p>
                                <p style="color: #4a5568; font-size: 14px; margin: 8px 0;"><?php echo $ticket_title; ?></p>
                                <p style="margin: 12px 0; color: #4a5568; font-size: 14px;"><?php echo $message; ?></p>
                                <div class="notification-meta" style="margin-top: 10px;">
                                    <span class="badge badge-info"><?php echo $type_display; ?></span>
                                    <span class="badge badge-secondary"><?php echo $employee_name; ?></span>
                                </div>
                            </div>

                            <!-- Modal for this ticket -->
                            <div class="modal-overlay" id="<?php echo $unique_id; ?>" onclick="closeTicketModal('<?php echo $unique_id; ?>', event)">
                                <div class="modal-content" onclick="event.stopPropagation()">
                                    <div class="modal-header <?php echo (isset($notif['priority']) && strtolower($notif['priority']) === 'high') ? 'urgent' : ''; ?>">
                                        <div>
                                            <h2><?php echo htmlspecialchars($notif['title'] ?? $ticket_title); ?></h2>
                                            <div class="modal-ticket-id">
                                                <i class="bi bi-ticket-perforated"></i> <?php echo htmlspecialchars($notif['ticket_number'] ?? $ticket_number); ?>
                                            </div>
                                            <div class="modal-employee-name">
                                                <i class="bi bi-person-fill"></i> <?php echo $employee_name; ?>
                                            </div>
                                        </div>
                                        <button class="modal-close" onclick="closeTicketModal('<?php echo $unique_id; ?>')">×</button>
                                    </div>

                                    <div class="modal-body">
                                        <div class="ticket-info-grid">
                                            <div class="ticket-info-item">
                                                <span class="ticket-info-label">🔖 Ticket ID</span>
                                                <span class="ticket-info-value"><?php echo htmlspecialchars($notif['ticket_number'] ?? 'N/A'); ?></span>
                                            </div>
                                            <div class="ticket-info-item">
                                                <span class="ticket-info-label">👤 Employee</span>
                                                <span class="ticket-info-value"><?php echo $employee_name; ?></span>
                                            </div>
                                            <div class="ticket-info-item">
                                                <span class="ticket-info-label">⚡ Priority</span>
                                                <span class="ticket-info-value">
                                                    <?php 
                                                    $priority = strtolower($notif['priority'] ?? 'low');
                                                    $priority_class = 'priority-' . $priority;
                                                    ?>
                                                    <span class="priority-badge <?php echo $priority_class; ?>">
                                                        <?php echo ucfirst($priority); ?>
                                                    </span>
                                                </span>
                                            </div>
                                            <div class="ticket-info-item">
                                                <span class="ticket-info-label">📊 Status</span>
                                                <span class="ticket-info-value">
                                                    <?php 
                                                    $status = strtolower($notif['status'] ?? 'pending');
                                                    $status = str_replace('_', ' ', $status);
                                                    $status_class = 'status-' . str_replace(' ', '-', $status);
                                                    ?>
                                                    <span class="status-badge <?php echo $status_class; ?>">
                                                        <?php echo ucfirst($status); ?>
                                                    </span>
                                                </span>
                                            </div>
                                            <div class="ticket-info-item">
                                                <span class="ticket-info-label">📅 Created</span>
                                                <span class="ticket-info-value"><?php echo date('M d, Y', strtotime($notif['created_at'] ?? date('Y-m-d'))); ?></span>
                                            </div>
                                        </div>

                                        <div class="ticket-description">
                                            <h3><i class="bi bi-file-text"></i> Description</h3>
                                            <div class="ticket-description-text">
                                                <?php echo htmlspecialchars($notif['description'] ?? 'No description provided'); ?>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="modal-footer">
                                        <button class="modal-btn modal-btn-secondary" onclick="closeTicketModal('<?php echo $unique_id; ?>')">
                                            <i class="bi bi-x-circle"></i> Close
                                        </button>
                                        <a href="hr_ticket_detail.php?id=<?php echo $notif['ticket_id_full'] ?? '#'; ?>" class="modal-btn modal-btn-primary">
                                            <i class="bi bi-eye"></i> View Full Ticket
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        function openTicketModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.add('show');
                document.body.style.overflow = 'hidden';
            }
        }

        function closeTicketModal(modalId, event = null) {
            if (event) {
                event.preventDefault();
            }
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.remove('show');
                document.body.style.overflow = 'auto';
            }
        }

        // Close modal when pressing Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const modals = document.querySelectorAll('.modal-overlay.show');
                modals.forEach(modal => {
                    modal.classList.remove('show');
                });
                document.body.style.overflow = 'auto';
            }
        });
    </script>
</body>
</html>
