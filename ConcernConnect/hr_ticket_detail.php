<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// PHPMailer imports
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

requireLogin();

$db = new Database();
$conn = $db->connect();

$employee = getEmployeeData($conn, $_SESSION['employee_id']);

if ($employee['role'] !== 'hr_admin') {
    header('Location: dashboard.php');
    exit();
}

$ticket_id = $_GET['id'] ?? 0;
$success = '';
$error = '';

// Fetch ticket details with employee photo
$stmt = $conn->prepare("
    SELECT t.*, e.full_name, e.employee_id, e.email, e.department, e.photo
    FROM tickets t 
    JOIN employees e ON t.employee_id = e.employee_id 
    WHERE t.id = :id
");
$stmt->execute(['id' => $ticket_id]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ticket) {
    header('Location: hr_tickets.php');
    exit();
}

// Get photo path with fallback
$photoPath = (!empty($ticket['photo']) && file_exists($ticket['photo'])) 
    ? htmlspecialchars($ticket['photo']) 
    : 'assets/images/default.png';

// Get attachment path
$attachmentPath = null;
if (!empty($ticket['attachment'])) {
    $attachmentPath = 'assets/TicketImages/' . $ticket['attachment'];
}

// Get file extension and type
function getFileType($filename) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $image_exts = ['jpg', 'jpeg', 'png', 'gif'];
    return in_array($ext, $image_exts) ? 'image' : 'file';
}

function getFileIcon($filename) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $icons = [
        'pdf' => 'bi-file-earmark-pdf',
        'doc' => 'bi-file-earmark-word',
        'docx' => 'bi-file-earmark-word',
        'jpg' => 'bi-file-earmark-image',
        'jpeg' => 'bi-file-earmark-image',
        'png' => 'bi-file-earmark-image',
        'gif' => 'bi-file-earmark-image'
    ];
    return $icons[$ext] ?? 'bi-file-earmark';
}

// Handle Status Update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $new_status = $_POST['status'] ?? '';
    
    if ($new_status) {
        $stmt = $conn->prepare("UPDATE tickets SET status = :status, updated_at = CURRENT_TIMESTAMP WHERE id = :id");
        $stmt->execute(['status' => $new_status, 'id' => $ticket_id]);
        
        $success = 'Ticket status updated successfully!';
        
        $stmt = $conn->prepare("SELECT t.*, e.full_name, e.employee_id, e.email, e.department, e.photo FROM tickets t JOIN employees e ON t.employee_id = e.employee_id WHERE t.id = :id");
        $stmt->execute(['id' => $ticket_id]);
        $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

// Handle HR response submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_response'])) {
    $response_text = trim($_POST['response_text'] ?? '');
    $is_internal = isset($_POST['is_internal']) ? 1 : 0;
    $send_email = isset($_POST['send_email']) ? 1 : 0;
    $send_sms = isset($_POST['send_sms']) ? 1 : 0;

    if ($response_text !== '') {
        // Insert response
        $stmt = $conn->prepare("
            INSERT INTO ticket_responses (ticket_id, employee_id, response_text, is_internal) 
            VALUES (:ticket_id, :employee_id, :response_text, :is_internal)
        ");
        $stmt->execute([
            'ticket_id' => $ticket_id,
            'employee_id' => $_SESSION['employee_id'],
            'response_text' => $response_text,
            'is_internal' => $is_internal
        ]);

        // Notify employee (only if not internal)
        if (!$is_internal) {
            $notification_message = "HR responded to your ticket: " . $ticket['ticket_number'];
            $sent_via = [];
            
            // Send Email Notification
            if ($send_email) {
                require 'PHPMailer/PHPMailer.php';
                require 'PHPMailer/SMTP.php';
                require 'PHPMailer/Exception.php';
                
                $mail = new PHPMailer(true);
                
                try {
                    // SMTP Configuration
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'trustingai.suppemployee00@gmail.com';
                    $mail->Password = 'wafw yozz sutx jzvd';
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 587;
                    
                    // Email Content
                    $mail->setFrom('trustingai.suppemployee00@gmail.com', 'HR Department');
                    $mail->addAddress($ticket['email'], $ticket['full_name']);
                    
                    $mail->Subject = 'HR Response to Ticket #' . $ticket['ticket_number'];
                    $mail->isHTML(true);
                    
                    $emailBody = "
                    <html>
                    <head>
                        <style>
                            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                            .header { background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
                            .content { background: #f8f9fa; padding: 20px; border: 1px solid #e2e8f0; }
                            .ticket-info { background: white; padding: 15px; border-radius: 8px; margin: 15px 0; }
                            .response-box { background: #e0e7ff; padding: 15px; border-left: 4px solid #667eea; border-radius: 5px; margin: 15px 0; }
                            .footer { text-align: center; padding: 20px; color: #64748b; font-size: 0.9rem; }
                            .btn { display: inline-block; padding: 12px 30px; background: #667eea; color: white; text-decoration: none; border-radius: 8px; margin-top: 15px; }
                        </style>
                    </head>
                    <body>
                        <div class='container'>
                            <div class='header'>
                                <h2>HR Response Received</h2>
                            </div>
                            <div class='content'>
                                <p>Hello <strong>" . htmlspecialchars($ticket['full_name']) . "</strong>,</p>
                                <p>HR has responded to your ticket:</p>
                                
                                <div class='ticket-info'>
                                    <strong>Ticket Number:</strong> " . htmlspecialchars($ticket['ticket_number']) . "<br>
                                    <strong>Title:</strong> " . htmlspecialchars($ticket['title']) . "<br>
                                    <strong>Status:</strong> " . htmlspecialchars($ticket['status']) . "
                                </div>
                                
                                <div class='response-box'>
                                    <strong>HR Response:</strong><br><br>
                                    " . nl2br(htmlspecialchars($response_text)) . "
                                </div>
                                
                                <a href='http://localhost/concernconnect/tickets.php' class='btn'>View Ticket Details</a>
                            </div>
                            <div class='footer'>
                                <p>This is an automated message from the Trusting Social AI Philippines. Please do not reply to this email.</p>
                                <p>&copy; " . date('Y') . " Trusting Social AI Philippines. All rights reserved.</p>
                            </div>
                        </div>
                    </body>
                    </html>
                    ";
                    
                    $mail->Body = $emailBody;
                    $mail->AltBody = strip_tags($emailBody);
                    
                    $mail->send();
                    $sent_via[] = 'email';
                    
                } catch (Exception $e) {
                    $error = "Email could not be sent. Error: {$mail->ErrorInfo}";
                }
            }
            
            // SMS would go here (if send_sms is checked)
            if ($send_sms) {
                $sent_via[] = 'sms';
                // TODO: Implement SMS sending with Semaphore API
            }
            
            // Insert notification into database
            $stmt = $conn->prepare("
                INSERT INTO notifications (employee_id, ticket_id, notification_type, message, sent_via, created_at) 
                VALUES (:employee_id, :ticket_id, :type, :message, :sent_via, NOW())
            ");
            $stmt->execute([
                'employee_id' => $ticket['employee_id'],
                'ticket_id' => $ticket_id,
                'type' => 'ticket_response',
                'message' => $notification_message,
                'sent_via' => implode(',', $sent_via)
            ]);
        }

        $success = 'Response sent successfully!' . (!$is_internal && $send_email ? ' Email notification sent.' : '');
        
        // Refresh page to show new response
        header("Location: hr_ticket_detail.php?id=$ticket_id&success=1");
        exit();
    }
}

// Check for success message
if (isset($_GET['success'])) {
    $success = 'Response sent successfully! Employee has been notified.';
}

// Fetch responses
$stmt = $conn->prepare("
    SELECT r.*, e.full_name 
    FROM ticket_responses r 
    JOIN employees e ON r.employee_id = e.employee_id 
    WHERE r.ticket_id = :ticket_id 
    ORDER BY r.created_at ASC
");
$stmt->execute(['ticket_id' => $ticket_id]);
$responses = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #f5f7fb;
            color: #1e293b;
        }

        .dashboard-content {
            margin-left: 280px;
            padding: 2rem;
            min-height: 100vh;
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
        }

        .back-button:hover {
            transform: translateX(-5px);
        }

        .alert {
            padding: 1rem 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            animation: slideDown 0.3s ease;
        }

        .alert-success {
            background: #dcfce7;
            color: #15803d;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .ticket-layout {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 1.5rem;
        }

        .ticket-main, .ticket-sidebar {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .ticket-header {
            margin-bottom: 2rem;
        }

        .ticket-header h1 {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .ticket-number {
            color: #64748b;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .badge {
            padding: 0.4rem 0.9rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-block;
        }

        .badge-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-in-progress {
            background: #dbeafe;
            color: #1e40af;
        }

        .badge-resolved {
            background: #dcfce7;
            color: #15803d;
        }

        .badge-closed {
            background: #f1f5f9;
            color: #475569;
        }

        .badge-high {
            background: #fee2e2;
            color: #991b1b;
        }

        .badge-medium {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-low {
            background: #dbeafe;
            color: #1e40af;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: #f8fafc;
            border-radius: 12px;
        }

        .info-item {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .info-label {
            font-size: 0.85rem;
            color: #64748b;
            font-weight: 600;
        }

        .info-value {
            font-size: 1rem;
            color: #1e293b;
            font-weight: 500;
        }

        .section-title {
            font-size: 1.125rem;
            font-weight: 700;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .ticket-description {
            padding: 1.5rem;
            background: #f8fafc;
            border-radius: 12px;
            margin-bottom: 2rem;
            line-height: 1.6;
        }

        /* Attachment Section */
        .attachment-section {
            background: #f8fafc;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .attachment-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem;
            background: white;
            border-radius: 10px;
            border-left: 4px solid #667eea;
        }

        .attachment-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex: 1;
        }

        .attachment-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
        }

        .attachment-details {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .attachment-name {
            font-weight: 600;
            color: #1e293b;
            word-break: break-all;
        }

        .attachment-size {
            font-size: 0.85rem;
            color: #64748b;
        }

        .attachment-action {
            display: flex;
            gap: 0.5rem;
        }

        .btn-view-file {
            padding: 0.5rem 1rem;
            background: #dbeafe;
            color: #1e40af;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }

        .btn-view-file:hover {
            background: #bfdbfe;
        }

        .btn-download-file {
            padding: 0.5rem 1rem;
            background: #dbeafe;
            color: #1e40af;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }

        .btn-download-file:hover {
            background: #bfdbfe;
        }

        .attachment-preview {
            margin-top: 1rem;
            text-align: center;
        }

        .attachment-preview img {
            max-width: 100%;
            max-height: 300px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .responses-container {
            margin-bottom: 2rem;
        }

        .response-item {
            padding: 1.25rem;
            background: #f8fafc;
            border-radius: 12px;
            margin-bottom: 1rem;
            border-left: 4px solid #667eea;
        }

        .response-item.internal-response {
            background: #fef3c7;
            border-left-color: #f59e0b;
        }

        .response-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.75rem;
        }

        .response-author {
            font-weight: 600;
            color: #1e293b;
        }

        .response-time {
            font-size: 0.85rem;
            color: #64748b;
        }

        .response-text {
            color: #475569;
            line-height: 1.6;
        }

        .response-form {
            background: #f8fafc;
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
        }

        .status-update-section {
            background: #f8fafc;
            border: 2px solid #e5e7eb;
            border-radius: 16px;
            padding: 2rem;
        }

        .status-update-section .section-title {
            margin-bottom: 1.5rem;
            color: #2d3748;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #334155;
        }

        .form-control {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            font-family: inherit;
        }

        .form-control:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        textarea.form-control {
            resize: vertical;
            min-height: 120px;
        }

        .checkbox-group {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            font-size: 0.95rem;
        }

        .checkbox-label input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        .status-form-group {
            display: flex;
            gap: 1rem;
            align-items: flex-end;
        }

        .status-select {
            flex: 1;
            max-width: 300px;
        }

        .status-select select {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            color: #2d3748;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
        }

        .status-select select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .btn-submit, .update-btn {
            padding: 0.75rem 2rem;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-submit {
            width: 100%;
        }

        .update-btn:hover, .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        }

        .status-info {
            background: white;
            border-left: 4px solid #667eea;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-top: 1.5rem;
            font-size: 14px;
            color: #4a5568;
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
        }

        /* Employee Card */
        .admin-avatar-img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            margin: 0 auto 1rem;
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.1);
            border: 3px solid #667eea;
            display: block;
            transition: all 0.3s ease;
        }

        .admin-avatar-img:hover {
            transform: scale(1.05);
            box-shadow: 0 6px 16px rgba(102, 126, 234, 0.3);
        }

        .employee-card {
            padding: 1.5rem;
            background: #f8fafc;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .employee-header {
            margin-bottom: 1rem;
        }

        .employee-name {
            font-weight: 700;
            font-size: 1.125rem;
            color: #1e293b;
            margin-bottom: 0.25rem;
        }

        .employee-id {
            color: #64748b;
            font-size: 0.9rem;
        }

        .employee-info-list {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            border-top: 1px solid #e2e8f0;
            padding-top: 1rem;
        }

        .info-row {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            color: #64748b;
            justify-content: center;
        }

        .no-responses {
            text-align: center;
            padding: 3rem 1rem;
            color: #94a3b8;
        }

        @media (max-width: 1200px) {
            .dashboard-content {
                margin-left: 0;
            }

            .ticket-layout {
                grid-template-columns: 1fr;
            }

            .status-form-group {
                flex-direction: column;
                align-items: stretch;
            }

            .status-select {
                max-width: 100%;
            }
        }
    </style>
</head>
<body>

<?php include 'includes/hr_sidebar.php'; ?>

<div class="dashboard-content">
    <?php include 'includes/hr_header.php'; ?>

    <a href="hr_tickets.php" class="back-button">
        <i class="bi bi-arrow-left"></i> Back to All Tickets
    </a>

    <?php if ($success): ?>
        <div class="alert alert-success">
            <i class="bi bi-check-circle-fill"></i>
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-error">
            <i class="bi bi-exclamation-circle-fill"></i>
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <div class="ticket-layout">
        <!-- Main Content -->
        <div class="ticket-main">
            <div class="ticket-header">
                <h1><?php echo htmlspecialchars($ticket['title']); ?></h1>
                <div class="ticket-number">
                    <i class="bi bi-ticket-perforated"></i>
                    <?php echo htmlspecialchars($ticket['ticket_number']); ?>
                </div>
            </div>

            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Status</span>
                    <span class="info-value">
                        <span class="badge badge-<?php echo strtolower(str_replace(' ', '-', $ticket['status'])); ?>">
                            <?php echo htmlspecialchars($ticket['status']); ?>
                        </span>
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label">Priority</span>
                    <span class="info-value">
                        <span class="badge badge-<?php echo strtolower($ticket['priority']); ?>">
                            <?php echo htmlspecialchars($ticket['priority']); ?>
                        </span>
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label">Category</span>
                    <span class="info-value"><?php echo htmlspecialchars($ticket['category_id']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Created</span>
                    <span class="info-value"><?php echo date('M d, Y g:ia', strtotime($ticket['created_at'])); ?></span>
                </div>
            </div>

            <div class="section-title">
                <i class="bi bi-file-text"></i>
                Employee's Request
            </div>
            <div class="ticket-description">
                <?php echo nl2br(htmlspecialchars($ticket['description'])); ?>
            </div>

            <!-- Attachment Section -->
            <?php if ($attachmentPath && file_exists($attachmentPath)): ?>
                <div class="attachment-section">
                    <div class="section-title">
                        <i class="bi bi-paperclip"></i>
                        Attached Files
                    </div>
                    
                    <div class="attachment-item">
                        <div class="attachment-info">
                            <div class="attachment-icon">
                                <i class="bi <?php echo getFileIcon($ticket['attachment']); ?>"></i>
                            </div>
                            <div class="attachment-details">
                                <div class="attachment-name"><?php echo htmlspecialchars($ticket['attachment']); ?></div>
                                <div class="attachment-size"><?php echo number_format(filesize($attachmentPath) / 1024, 2); ?> KB</div>
                            </div>
                        </div>
                        <div class="attachment-action">
                            <a href="<?php echo htmlspecialchars($attachmentPath); ?>" class="btn-view-file" target="_blank">
                                <i class="bi bi-eye"></i> View
                            </a>
                            <a href="<?php echo htmlspecialchars($attachmentPath); ?>" class="btn-download-file" download>
                                <i class="bi bi-download"></i> Download
                            </a>
                        </div>
                    </div>

                    <!-- Image Preview -->
                    <?php if (getFileType($ticket['attachment']) === 'image'): ?>
                        <div class="attachment-preview">
                            <img src="<?php echo htmlspecialchars($attachmentPath); ?>" alt="Attachment">
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="responses-container">
                <div class="section-title">
                    <i class="bi bi-chat-dots"></i>
                    Response History (<?php echo count($responses); ?>)
                </div>

                <?php if (empty($responses)): ?>
                    <div class="no-responses">
                        <i class="bi bi-inbox" style="font-size: 3rem; opacity: 0.3; display: block; margin-bottom: 1rem;"></i>
                        <p>No responses yet. Be the first to respond!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($responses as $response): ?>
                        <div class="response-item <?php echo $response['is_internal'] ? 'internal-response' : ''; ?>">
                            <div class="response-header">
                                <span class="response-author">
                                    <?php echo htmlspecialchars($response['full_name']); ?>
                                    <?php if ($response['is_internal']): ?>
                                        <span class="badge badge-medium">Internal</span>
                                    <?php endif; ?>
                                </span>
                                <span class="response-time">
                                    <i class="bi bi-clock"></i>
                                    <?php echo date('M d, Y g:ia', strtotime($response['created_at'])); ?>
                                </span>
                            </div>
                            <div class="response-text">
                                <?php echo nl2br(htmlspecialchars($response['response_text'])); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="response-form">
                <div class="section-title">
                    <i class="bi bi-send"></i>
                    Send Response
                </div>
                <form method="POST" action="">
                    <div class="form-group">
                        <label class="form-label">Your Response</label>
                        <textarea name="response_text" class="form-control" placeholder="Type your response here..." required></textarea>
                    </div>

                    <div class="form-group">
                        <div class="checkbox-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="is_internal">
                                <span><i class="bi bi-lock"></i> Internal Note (not visible to employee)</span>
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Notify Employee</label>
                        <div class="checkbox-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="send_email" checked>
                                <span><i class="bi bi-envelope"></i> Send Email Notification</span>
                            </label>
                            <label class="checkbox-label">
                                <input type="checkbox" name="send_sms">
                                <span><i class="bi bi-phone"></i> Send SMS Notification (Coming Soon)</span>
                            </label>
                        </div>
                    </div>

                    <button type="submit" name="send_response" class="btn-submit">
                        <i class="bi bi-send-fill"></i>
                        Send Response
                    </button>
                </form>
            </div>

            <!-- Status Update Section -->
            <div class="status-update-section">
                <div class="section-title">
                    <i class="bi bi-arrow-left-right"></i> Update Status
                </div>
                <form method="POST" action="">
                    <div class="status-form-group">
                        <div class="status-select">
                            <select name="status" required>
                                <option value="">-- Select Status --</option>
                                <option value="pending" <?php echo strtolower($ticket['status']) == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="in progress" <?php echo strtolower($ticket['status']) == 'in progress' ? 'selected' : ''; ?>>In Progress</option>
                                <option value="resolved" <?php echo strtolower($ticket['status']) == 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                                <option value="closed" <?php echo strtolower($ticket['status']) == 'closed' ? 'selected' : ''; ?>>Closed</option>
                            </select>
                        </div>
                        <button type="submit" name="update_status" class="update-btn">
                            <i class="bi bi-check-circle"></i> Update Status
                        </button>
                    </div>
                    <div class="status-info">
                        <i class="bi bi-info-circle"></i>
                        <span>Updating the status will notify the employee.</span>
                    </div>
                </form>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="ticket-sidebar">
            <div class="section-title">
                <i class="bi bi-person-circle"></i>
                Employee Information
            </div>

            <div class="employee-card">
                <!-- Employee Photo as Avatar -->
                <img src="<?php echo htmlspecialchars($photoPath); ?>" alt="<?php echo htmlspecialchars($ticket['full_name']); ?>" class="admin-avatar-img" onerror="this.src='assets/images/default.png';">

                <!-- Employee Details -->
                <div class="employee-header">
                    <div class="employee-name"><?php echo htmlspecialchars($ticket['full_name']); ?></div>
                    <div class="employee-id">ID: <?php echo htmlspecialchars($ticket['employee_id']); ?></div>
                </div>

                <div class="employee-info-list">
                    <div class="info-row">
                        <i class="bi bi-building"></i>
                        <?php echo htmlspecialchars($ticket['department']); ?>
                    </div>
                    <div class="info-row">
                        <i class="bi bi-envelope"></i>
                        <?php echo htmlspecialchars($ticket['email']); ?>
                    </div>
                </div>
            </div>

            <div class="section-title" style="margin-top: 1.5rem;">
                <i class="bi bi-person-badge"></i>
                Assignment
            </div>

            <div class="info-item">
                <span class="info-label">Assigned To</span>
                <span class="info-value"><?php echo htmlspecialchars($ticket['assigned_to'] ?? 'Unassigned'); ?></span>
            </div>
        </div>
    </div>
</div>

</body>
</html>
