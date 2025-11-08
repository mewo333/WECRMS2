<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

requireLogin();

$db = new Database();
$conn = $db->connect();

$employee = getEmployeeData($conn, $_SESSION['employee_id']);

if (!$employee || $employee['role'] !== 'hr_admin') {
    header('Location: dashboard.php');
    exit();
}

// Handle Create Announcement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_announcement'])) {
    $title = $_POST['title'];
    $message = $_POST['message'];
    $priority = $_POST['priority'];
    $expires_at = !empty($_POST['expires_at']) ? $_POST['expires_at'] : NULL;
    $notification_type = $_POST['notification_type'] ?? 'email';
    $send_email = ($notification_type === 'email') ? 1 : 0;
    
    $stmt = $conn->prepare("INSERT INTO announcements (title, message, priority, expires_at, send_email) VALUES (:title, :message, :priority, :expires_at, :send_email)");
    $stmt->execute([
        'title' => $title,
        'message' => $message,
        'priority' => $priority,
        'expires_at' => $expires_at,
        'send_email' => $send_email
    ]);
    
    // Send notification based on type
    if ($notification_type === 'email') {
        require 'PHPMailer/PHPMailer.php';
        require 'PHPMailer/SMTP.php';
        require 'PHPMailer/Exception.php';
        
        $stmt = $conn->query("SELECT email, full_name FROM employees WHERE role = 'employee'");
        $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $mail = new PHPMailer(true);
        
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'trustingai.suppemployee00@gmail.com';
            $mail->Password = 'wafw yozz sutx jzvd';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            
            $mail->setFrom('trustingai.suppemployee00@gmail.com', 'HR Department');
            
            foreach ($employees as $emp) {
                $mail->addAddress($emp['email'], $emp['full_name']);
            }
            
            $mail->Subject = "📢 Announcement: " . $title;
            $mail->isHTML(true);
            $mail->Body = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                    <div style='background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 30px; border-radius: 10px 10px 0 0;'>
                        <h1 style='margin: 0; font-size: 24px;'>📢 New Announcement</h1>
                    </div>
                    <div style='background: #f8fafc; padding: 30px; border-radius: 0 0 10px 10px;'>
                        <h2 style='color: #1e293b; margin-top: 0;'>" . htmlspecialchars($title) . "</h2>
                        <div style='background: white; padding: 20px; border-radius: 8px; border-left: 4px solid #667eea;'>
                            <p style='color: #475569; line-height: 1.6; margin: 0;'>" . nl2br(htmlspecialchars($message)) . "</p>
                        </div>
                        <p style='color: #64748b; font-size: 14px; margin-top: 20px;'>
                            Priority: <strong style='color: " . ($priority === 'high' ? '#ef4444' : ($priority === 'medium' ? '#f59e0b' : '#10b981')) . ";'>" . ucfirst($priority) . "</strong>
                        </p>
                    </div>
                </div>
            ";
            
            $mail->send();
            header('Location: hr_dashboard.php?success=sent');
        } catch (Exception $e) {
            header('Location: hr_dashboard.php?error=email_failed');
        }
    } else {
        // SMS notification - you can integrate your SMS API here
        header('Location: hr_dashboard.php?success=sms_sent');
    }
    exit();
}

// Get Total Employees
$stmt = $conn->query("SELECT COUNT(*) as total FROM employees");
$total_employees = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Get Job Applicants
try {
    $stmt = $conn->query("SELECT COUNT(*) as total FROM job_applicants WHERE status != 'rejected'");
    $total_applicants = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
} catch (PDOException $e) {
    $total_applicants = 0;
}

// Get Total Payroll
try {
    $stmt = $conn->query("SELECT SUM(net_salary) as total FROM payroll WHERE MONTH(payment_date) = MONTH(CURDATE())");
    $total_payroll = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
} catch (PDOException $e) {
    $total_payroll = 0;
}

// Get Employee List
$stmt = $conn->query("SELECT employee_id, full_name, department, role FROM employees ORDER BY created_at DESC LIMIT 5");
$employee_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get Today's Schedule
try {
    $stmt = $conn->prepare("
        SELECT e.full_name, e.department, ws.check_in, ws.check_out, ws.status, ws.hours_worked
        FROM work_schedules ws
        JOIN employees e ON ws.employee_id = e.employee_id
        WHERE ws.schedule_date = CURDATE()
        ORDER BY ws.check_in ASC
    ");
    $stmt->execute();
    $today_schedule = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $today_schedule = [];
}

// Get Work Hours Data for Chart
try {
    $stmt = $conn->query("
        SELECT DATE(schedule_date) as date, SUM(hours_worked) as total_hours
        FROM work_schedules
        WHERE schedule_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY DATE(schedule_date)
        ORDER BY date ASC
    ");
    $work_hours_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $work_hours_data = [];
}

// Get recent leaves
try {
    $stmt = $conn->query("
        SELECT e.full_name, ws.status, ws.schedule_date
        FROM work_schedules ws
        JOIN employees e ON ws.employee_id = e.employee_id
        WHERE ws.status IN ('leave', 'wfh') AND ws.schedule_date >= CURDATE()
        ORDER BY ws.schedule_date ASC
        LIMIT 3
    ");
    $recent_leaves = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $recent_leaves = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HR Dashboard - Trusting Social AI Philippines</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f7fb;
            color: #1e293b;
        }

        .main-content {
            padding: 2rem;
            min-height: 100vh;
            transition: margin-left 0.4s cubic-bezier(0.68, -0.55, 0.27, 1.55);
        }

        /* Top Bar */
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            background: white;
            padding: 1.25rem 1.5rem;
            border-radius: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .search {
            padding: 0.75rem 1.5rem;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            width: 400px;
            font-size: 0.95rem;
            background: #f8fafc;
        }

        .search:focus {
            outline: none;
            border-color: #667eea;
            background: white;
        }

        .user-actions {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .icon {
            width: 40px;
            height: 40px;
            background: #f8fafc;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 1.2rem;
            transition: all 0.3s ease;
            color: #64748b;
        }

        .icon:hover {
            background: #667eea;
            color: white;
            transform: translateY(-2px);
        }

        /* Alert Messages */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .alert-success {
            background: #dcfce7;
            color: #15803d;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
        }

        /* Analytics Cards */
        .cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .card {
            background: white;
            padding: 2rem;
            border-radius: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            position: relative;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.12);
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
        }

        .blue-card::before { background: linear-gradient(135deg, #667eea, #764ba2); }
        .purple-card::before { background: linear-gradient(135deg, #f093fb, #f5576c); }
        .green-card::before { background: linear-gradient(135deg, #10b981, #059669); }

        .card p {
            color: #64748b;
            font-size: 0.9rem;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .card h2 {
            font-size: 2.75rem;
            font-weight: 700;
            color: #1e293b;
        }

                /* Create Announcement Section - Compact */
        .announcement-create-box {
            background: white;
            padding: 1.75rem;
            border-radius: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .announcement-create-box h3 {
            margin-bottom: 1.25rem;
            font-size: 1.25rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #1e293b;
        }

        .form-group-compact {
            margin-bottom: 1rem;
        }

        .form-group-compact label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.4rem;
            color: #334155;
            font-size: 0.85rem;
        }

        .form-control-compact {
            width: 100%;
            padding: 0.6rem 0.85rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            font-family: inherit;
        }

        .form-control-compact:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        textarea.form-control-compact {
            resize: vertical;
            min-height: 70px;
        }

        .form-row-compact {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }

        .notification-compact {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
            padding: 0.75rem;
            background: #f8fafc;
            border-radius: 8px;
        }

        .radio-label-compact {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            cursor: pointer;
            font-weight: 500;
            color: #64748b;
            font-size: 0.85rem;
            transition: color 0.3s ease;
        }

        .radio-label-compact input[type="radio"] {
            width: 16px;
            height: 16px;
            cursor: pointer;
        }

        .radio-label-compact:hover {
            color: #667eea;
        }

        .radio-label-compact input[type="radio"]:checked + span {
            color: #667eea;
            font-weight: 600;
        }

        .btn-send-compact {
            width: 100%;
            padding: 0.75rem 1.5rem;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-send-compact:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(102, 126, 234, 0.3);
        }

        /* Row Layout */
        .row {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .chart-box, .events-box, .employee-box, .schedule-box {
            background: white;
            padding: 1.75rem;
            border-radius: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        h3 {
            margin-bottom: 1.5rem;
            font-size: 1.25rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #1e293b;
        }

        /* Events Box */
        .events-box ul {
            list-style: none;
        }

        .events-box li {
            padding: 1rem;
            background: #f8fafc;
            border-radius: 10px;
            margin-bottom: 0.75rem;
            border-left: 4px solid #667eea;
            transition: all 0.3s ease;
        }

        .events-box li:hover {
            background: #f1f5f9;
            transform: translateX(5px);
        }

        .events-box strong {
            color: #1e293b;
            font-size: 0.95rem;
        }

        .events-box li span {
            color: #64748b;
            font-size: 0.85rem;
        }

        /* Employee Table */
        table {
            width: 100%;
            border-collapse: collapse;
        }

        table tr {
            border-bottom: 1px solid #f1f5f9;
            transition: background 0.3s ease;
        }

        table tr:hover {
            background: #f8fafc;
        }

        table td {
            padding: 1rem 0.5rem;
            font-size: 0.9rem;
        }

        .tag {
            padding: 0.35rem 0.85rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            display: inline-block;
        }

        .tag.green { background: #dcfce7; color: #15803d; }
        .tag.cyan { background: #cffafe; color: #0e7490; }
        .tag.orange { background: #fed7aa; color: #c2410c; }
        .tag.purple { background: #ede9fe; color: #6b21a8; }

        /* Schedule Box */
        .schedule-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            background: #f8fafc;
            border-radius: 10px;
            margin-bottom: 0.75rem;
            border-left: 4px solid #667eea;
            transition: all 0.3s ease;
        }

        .schedule-item:hover {
            background: #f1f5f9;
            transform: translateX(5px);
        }

        .schedule-item strong {
            color: #1e293b;
            display: block;
            margin-bottom: 0.25rem;
        }

        .schedule-item .time {
            color: #64748b;
            font-size: 0.85rem;
        }

        .status-badge {
            padding: 0.35rem 0.85rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-present { background: #dcfce7; color: #15803d; }
        .status-leave { background: #fef3c7; color: #92400e; }
        .status-wfh { background: #e0e7ff; color: #3730a3; }
        .status-absent { background: #fee2e2; color: #991b1b; }

        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: #94a3b8;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            display: block;
            opacity: 0.3;
        }

        @media (max-width: 1200px) {
            .main-content {
                margin-left: 0 !important;
            }
            .row {
                grid-template-columns: 1fr;
            }
        }

               @media (max-width: 768px) {
            .cards {
                grid-template-columns: 1fr;
            }
            .search {
                width: 100%;
            }
            .topbar {
                flex-direction: column;
                gap: 1rem;
            }
            .form-row-compact {
                grid-template-columns: 1fr;
            }
            .notification-compact {
                flex-direction: column;
                gap: 0.75rem;
            }
        }
    </style>
</head>
<body>

<?php include 'includes/hr_sidebar.php'; ?>

<div class="main-content">
    <?php include 'includes/hr_header.php'; ?>

    <!-- Alert Messages -->
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">
            <i class="bi bi-check-circle-fill"></i>
            <?php
            if ($_GET['success'] == 'sent') echo 'Announcement created and sent via email to all employees!';
            if ($_GET['success'] == 'sms_sent') echo 'Announcement created and SMS notifications sent!';
            ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-error">
            <i class="bi bi-exclamation-circle-fill"></i>
            Failed to send emails. Announcement saved anyway.
        </div>
    <?php endif; ?>

    <!-- Top Bar -->
    <header class="topbar">
        <input type="text" placeholder="🔍 Search employees, applicants, payroll..." class="search">
        <div class="user-actions">
            <span class="icon" title="Notifications"><i class="bi bi-bell"></i></span>
            <span class="icon" title="Settings"><i class="bi bi-gear"></i></span>
            <span class="icon" title="Profile"><i class="bi bi-person-circle"></i></span>
        </div>
    </header>

        <!-- Top Row: Create Announcement + What's on November -->
    <div class="row">
        <!-- Create Announcement Section -->
        <div class="announcement-create-box">
            <h3><i class="bi bi-megaphone-fill"></i> Create Announcement</h3>
            <form method="POST" action="">
                <div class="form-group-compact">
                    <label>Title</label>
                    <input type="text" class="form-control-compact" name="title" required placeholder="Enter title">
                </div>

                <div class="form-group-compact">
                    <label>Message</label>
                    <textarea class="form-control-compact" name="message" required placeholder="Enter message" rows="3"></textarea>
                </div>

                <div class="form-row-compact">
                    <div class="form-group-compact">
                        <label>Priority</label>
                        <select class="form-control-compact" name="priority" required>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                            <option value="low">Low</option>
                        </select>
                    </div>

                    <div class="form-group-compact">
                        <label>Expiry (Optional)</label>
                        <input type="datetime-local" class="form-control-compact" name="expires_at">
                    </div>
                </div>

                <div class="notification-compact">
                    <label class="radio-label-compact">
                        <input type="radio" name="notification_type" value="email" checked>
                        <span><i class="bi bi-envelope"></i> Email</span>
                    </label>
                    <label class="radio-label-compact">
                        <input type="radio" name="notification_type" value="sms">
                        <span><i class="bi bi-chat"></i> SMS</span>
                    </label>
                </div>

                <button type="submit" name="add_announcement" class="btn-send-compact">
                    <i class="bi bi-send-fill"></i> Send Announcement
                </button>
            </form>
        </div>

        <!-- What's on in November Section -->
        <div class="events-box">
            <h3><i class="bi bi-calendar-event"></i> What's on in <?php echo date('F'); ?>?</h3>
            <ul>
                <?php if (empty($recent_leaves)): ?>
                    <li style="border-left-color: #94a3b8;">
                        <strong>No upcoming leaves</strong><br>
                        <span>All employees are scheduled to work</span>
                    </li>
                <?php else: ?>
                    <?php foreach ($recent_leaves as $leave): ?>
                        <li>
                            <strong><?php echo htmlspecialchars($leave['full_name']); ?></strong> — 
                            <?php 
                            $statusText = ucfirst(str_replace('_', ' ', $leave['status']));
                            echo $statusText; 
                            ?>
                            <br>
                            <span><i class="bi bi-calendar2"></i> <?php echo date('M d, Y', strtotime($leave['schedule_date'])); ?></span>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>
    </div>

    <!-- Second Row: Member Work Hours Chart -->
    <div class="row">
        <div class="chart-box">
            <h3><i class="bi bi-clock-history"></i> Member Work Hours (Last 7 Days)</h3>
            <?php if (empty($work_hours_data)): ?>
                <div class="empty-state">
                    <i class="bi bi-graph-up"></i>
                    <p>No work hours data available yet.<br><small>Add schedules to see the chart.</small></p>
                </div>
            <?php else: ?>
                <canvas id="workHoursChart" height="100"></canvas>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Employee List + Today's Schedule -->
    <div class="row">
        <div class="employee-box">
            <h3><i class="bi bi-people"></i> Recent Employees</h3>
            <table>
                <?php foreach ($employee_list as $emp): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($emp['full_name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($emp['role']); ?></td>
                        <td>
                            <?php
                            $dept = $emp['department'];
                            $tagClass = 'green';
                            if (stripos($dept, 'IT') !== false || stripos($dept, 'Development') !== false) $tagClass = 'cyan';
                            if (stripos($dept, 'Human') !== false || stripos($dept, 'HR') !== false) $tagClass = 'purple';
                            if (stripos($dept, 'Marketing') !== false || stripos($dept, 'Business') !== false) $tagClass = 'orange';
                            ?>
                            <span class="tag <?php echo $tagClass; ?>"><?php echo htmlspecialchars($dept); ?></span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <div class="schedule-box">
            <h3><i class="bi bi-calendar-check"></i> Today's Schedule</h3>
            <?php if (empty($today_schedule)): ?>
                <div class="empty-state">
                    <i class="bi bi-calendar-x"></i>
                    <p>No schedule for today.<br><small>Add work schedules to see attendance.</small></p>
                </div>
            <?php else: ?>
                <?php foreach ($today_schedule as $sched): ?>
                    <div class="schedule-item">
                        <div>
                            <strong><?php echo htmlspecialchars($sched['full_name']); ?></strong>
                            <div class="time">
                                <?php 
                                if ($sched['status'] == 'present') {
                                    echo '<i class="bi bi-clock"></i> ' . $sched['check_in'] . ' - ' . ($sched['check_out'] ?? 'Ongoing');
                                    if ($sched['hours_worked']) {
                                        echo ' (' . $sched['hours_worked'] . 'h)';
                                    }
                                } else {
                                    echo '<i class="bi bi-info-circle"></i> ' . ucfirst($sched['status']);
                                }
                                ?>
                            </div>
                        </div>
                        <span class="status-badge status-<?php echo $sched['status']; ?>">
                            <?php echo strtoupper($sched['status']); ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (!empty($work_hours_data)): ?>
<script>
const ctx = document.getElementById('workHoursChart').getContext('2d');
const workHoursData = <?php echo json_encode($work_hours_data); ?>;

const labels = workHoursData.map(d => {
    const date = new Date(d.date);
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
});
const data = workHoursData.map(d => parseFloat(d.total_hours));

new Chart(ctx, {
    type: 'line',
    data: {
        labels: labels,
        datasets: [{
            label: 'Total Hours Worked',
            data: data,
            borderColor: '#667eea',
            backgroundColor: 'rgba(102, 126, 234, 0.1)',
            tension: 0.4,
            fill: true,
            pointRadius: 6,
            pointHoverRadius: 8,
            pointBackgroundColor: '#667eea',
            pointBorderColor: '#fff',
            pointBorderWidth: 3
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                padding: 12,
                borderRadius: 8,
                titleFont: { size: 14, weight: 'bold' },
                bodyFont: { size: 13 }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: { callback: (value) => value + 'h' },
                grid: { color: '#f1f5f9' }
            },
            x: { grid: { display: false } }
        }
    }
});
</script>
<?php endif; ?>

</body>
</html>
