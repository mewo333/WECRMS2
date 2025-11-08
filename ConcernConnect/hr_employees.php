<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// PHPMailer imports at the top
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

// Handle Delete Employee
if (isset($_POST['delete_employee'])) {
    $emp_id = $_POST['employee_id'];
    $stmt = $conn->prepare("DELETE FROM employees WHERE employee_id = :id");
    $stmt->execute(['id' => $emp_id]);
    header('Location: hr_employees.php?success=deleted');
    exit();
}

// Handle Send Email
if (isset($_POST['send_email'])) {
    require 'PHPMailer/PHPMailer.php';
    require 'PHPMailer/SMTP.php';
    require 'PHPMailer/Exception.php';
    
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
        $mail->addAddress($_POST['employee_email'], $_POST['employee_name']);
        
        $mail->Subject = $_POST['email_subject'];
        $mail->isHTML(true);
        $mail->Body = nl2br($_POST['email_message']);
        
        $mail->send();
        header('Location: hr_employees.php?success=email_sent');
        exit();
    } catch (Exception $e) {
        $error_message = "Email failed: {$mail->ErrorInfo}";
    }
}

// Fetch all employees with ticket statistics
$stmt = $conn->prepare("
    SELECT e.*, 
           COUNT(t.id) AS total_tickets,
           SUM(CASE WHEN LOWER(t.status) = 'pending' THEN 1 ELSE 0 END) AS pending_tickets,
           SUM(CASE WHEN LOWER(t.status) = 'resolved' THEN 1 ELSE 0 END) AS resolved_tickets
    FROM employees e
    LEFT JOIN tickets t ON e.employee_id = t.employee_id
    WHERE e.role = 'employee'
    GROUP BY e.employee_id
    ORDER BY e.full_name
");
$stmt->execute();
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Monitoring</title>
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

        /* ✅ Changed to .main-content */
        .main-content {
            padding: 2rem;
            min-height: 100vh;
        }

        /* Top Actions Bar */
        .top-actions {
            background: white;
            padding: 1.25rem 1.5rem;
            border-radius: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .search-box {
            flex: 1;
            max-width: 400px;
            position: relative;
        }

        .search-box input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.5rem;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            font-size: 0.95rem;
            background: #f8fafc;
        }

        .search-box i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
        }

        .btn-add {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .btn-add:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        }

        /* Employees Table */
        .employees-table-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            overflow: hidden;
        }

        .table-header {
            padding: 1.5rem;
            border-bottom: 1px solid #f1f5f9;
        }

        .table-header h3 {
            font-size: 1.25rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: #f8fafc;
        }

        thead th {
            padding: 1rem 1.5rem;
            text-align: left;
            font-weight: 600;
            color: #64748b;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        tbody tr {
            border-bottom: 1px solid #f1f5f9;
            transition: background 0.2s ease;
        }

        tbody tr:hover {
            background: #f8fafc;
        }

        tbody td {
            padding: 1.25rem 1.5rem;
            font-size: 0.95rem;
        }

        .employee-profile {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .employee-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #e2e8f0;
        }

        .employee-name {
            font-weight: 600;
            color: #1e293b;
        }

        .employee-id {
            color: #64748b;
            font-size: 0.85rem;
        }

        .badge {
            padding: 0.35rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .badge-department {
            background: #e0e7ff;
            color: #4f46e5;
        }

        .badge-stats {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.25rem 0.65rem;
            background: #f0f9ff;
            color: #0369a1;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
            margin-right: 0.25rem;
        }

        .badge-warning {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-success {
            background: #dcfce7;
            color: #15803d;
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .btn-action {
            width: 36px;
            height: 36px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            font-size: 1rem;
        }

        .btn-email {
            background: #dbeafe;
            color: #1e40af;
        }

        .btn-email:hover {
            background: #3b82f6;
            color: white;
            transform: translateY(-2px);
        }

        .btn-edit {
            background: #fef3c7;
            color: #92400e;
        }

        .btn-edit:hover {
            background: #f59e0b;
            color: white;
            transform: translateY(-2px);
        }

        .btn-delete {
            background: #fee2e2;
            color: #991b1b;
        }

        .btn-delete:hover {
            background: #ef4444;
            color: white;
            transform: translateY(-2px);
        }

        /* Email Modal */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
            z-index: 9999;
            align-items: center;
            justify-content: center;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal-content {
            background: white;
            padding: 2rem;
            border-radius: 20px;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 25px 70px rgba(0, 0, 0, 0.3);
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
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .modal-header h3 {
            font-size: 1.5rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #64748b;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-close:hover {
            background: #f1f5f9;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #334155;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        textarea.form-control {
            resize: vertical;
            min-height: 150px;
        }

        .btn-submit {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        }

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

        @media (max-width: 1200px) {
            .main-content {
                margin-left: 0 !important;
            }
        }

        @media (max-width: 768px) {
            .top-actions {
                flex-direction: column;
                gap: 1rem;
            }

            .search-box {
                max-width: 100%;
            }

            .employees-table-container {
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>

<?php include 'includes/hr_sidebar.php'; ?>

<div class="main-content">
    <?php include 'includes/hr_header.php'; ?>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">
            <i class="bi bi-check-circle-fill"></i>
            <?php
            if ($_GET['success'] == 'email_sent') echo 'Email sent successfully!';
            if ($_GET['success'] == 'deleted') echo 'Employee deleted successfully!';
            ?>
        </div>
    <?php endif; ?>

    <?php if (isset($error_message)): ?>
        <div class="alert alert-error">
            <i class="bi bi-exclamation-circle-fill"></i>
            <?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>

    <!-- Top Actions -->
    <div class="top-actions">
        <div class="search-box">
            <i class="bi bi-search"></i>
            <input type="text" id="searchInput" placeholder="Search employees..." onkeyup="filterTable()">
        </div>
        <button class="btn-add" onclick="window.location.href='hr_add_employee.php'">
            <i class="bi bi-plus-circle"></i>
            Add New Employee
        </button>
    </div>

    <!-- Employees Table -->
    <div class="employees-table-container">
        <div class="table-header">
            <h3><i class="bi bi-people-fill"></i> All Employees (<?php echo count($employees); ?>)</h3>
        </div>
        
        <table id="employeesTable">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Department</th>
                    <th>Email</th>
                    <th>Tickets</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($employees as $emp): ?>
                    <tr>
                        <td>
                            <div class="employee-profile">
                                <img src="<?php echo !empty($emp['photo']) ? htmlspecialchars($emp['photo']) : 'assets/images/default.png'; ?>" alt="Avatar" class="employee-avatar">
                                <div>
                                    <div class="employee-name"><?php echo htmlspecialchars($emp['full_name']); ?></div>
                                    <div class="employee-id">ID: <?php echo htmlspecialchars($emp['employee_id']); ?></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge badge-department"><?php echo htmlspecialchars($emp['department']); ?></span>
                        </td>
                        <td><?php echo htmlspecialchars($emp['email']); ?></td>
                        <td>
                            <span class="badge badge-stats"><?php echo $emp['total_tickets']; ?> Total</span>
                            <span class="badge badge-stats badge-warning"><?php echo $emp['pending_tickets']; ?> Pending</span>
                            <span class="badge badge-stats badge-success"><?php echo $emp['resolved_tickets']; ?> Resolved</span>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn-action btn-email" onclick="openEmailModal('<?php echo htmlspecialchars($emp['full_name']); ?>', '<?php echo htmlspecialchars($emp['email']); ?>')" title="Send Email">
                                    <i class="bi bi-envelope-fill"></i>
                                </button>
                                <button class="btn-action btn-edit" onclick="window.location.href='hr_edit_employee.php?id=<?php echo urlencode($emp['employee_id']); ?>'" title="Edit">
                                    <i class="bi bi-pencil-fill"></i>
                                </button>
                                <button class="btn-action btn-delete" onclick="confirmDelete('<?php echo htmlspecialchars($emp['employee_id']); ?>', '<?php echo htmlspecialchars($emp['full_name']); ?>')" title="Delete">
                                    <i class="bi bi-trash-fill"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Email Modal -->
<div class="modal-overlay" id="emailModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="bi bi-envelope"></i> Send Email</h3>
            <button class="btn-close" onclick="closeEmailModal()">
                <i class="bi bi-x"></i>
            </button>
        </div>
        
        <form method="POST" action="">
            <input type="hidden" name="employee_name" id="modal_employee_name">
            <input type="hidden" name="employee_email" id="modal_employee_email">
            
            <div class="form-group">
                <label>To:</label>
                <input type="text" class="form-control" id="modal_display_name" readonly>
            </div>
            
            <div class="form-group">
                <label>Subject:</label>
                <input type="text" class="form-control" name="email_subject" required placeholder="Email subject...">
            </div>
            
            <div class="form-group">
                <label>Message:</label>
                <textarea class="form-control" name="email_message" required placeholder="Type your message here..."></textarea>
            </div>
            
            <button type="submit" name="send_email" class="btn-submit">
                <i class="bi bi-send-fill"></i> Send Email
            </button>
        </form>
    </div>
</div>

<!-- Delete Confirmation Form -->
<form id="deleteForm" method="POST" action="" style="display: none;">
    <input type="hidden" name="employee_id" id="delete_employee_id">
    <input type="hidden" name="delete_employee" value="1">
</form>

<script>
function openEmailModal(name, email) {
    document.getElementById('modal_employee_name').value = name;
    document.getElementById('modal_employee_email').value = email;
    document.getElementById('modal_display_name').value = name + ' (' + email + ')';
    document.getElementById('emailModal').classList.add('active');
}

function closeEmailModal() {
    document.getElementById('emailModal').classList.remove('active');
}

document.getElementById('emailModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeEmailModal();
    }
});

function confirmDelete(employeeId, employeeName) {
    if (confirm('Are you sure you want to delete ' + employeeName + '? This action cannot be undone.')) {
        document.getElementById('delete_employee_id').value = employeeId;
        document.getElementById('deleteForm').submit();
    }
}

function filterTable() {
    const input = document.getElementById('searchInput');
    const filter = input.value.toUpperCase();
    const table = document.getElementById('employeesTable');
    const tr = table.getElementsByTagName('tr');
    
    for (let i = 1; i < tr.length; i++) {
        const td = tr[i].getElementsByTagName('td');
        let found = false;
        
        for (let j = 0; j < td.length; j++) {
            if (td[j]) {
                const txtValue = td[j].textContent || td[j].innerText;
                if (txtValue.toUpperCase().indexOf(filter) > -1) {
                    found = true;
                    break;
                }
            }
        }
        
        tr[i].style.display = found ? '' : 'none';
    }
}
</script>

</body>
</html>
