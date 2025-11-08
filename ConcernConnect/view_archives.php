<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

$db = new Database();
$conn = $db->connect();

$employee = getEmployeeData($conn, $_SESSION['employee_id']);

if ($employee['role'] !== 'hr_admin') {
    header('Location: dashboard.php');
    exit();
}

// Fetch archived tickets
$stmt = $conn->prepare("
    SELECT t.*, e.full_name, e.employee_id, e.department, e.photo, tc.category_name
    FROM tickets t
    JOIN employees e ON t.employee_id = e.employee_id
    LEFT JOIN ticket_categories tc ON t.category_id = tc.id
    WHERE t.is_archived = 1
    ORDER BY t.archived_at DESC
");
$stmt->execute();
$archived_tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Restore ticket
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['restore_id'])) {
    $restore_id = $_POST['restore_id'];
    $stmt = $conn->prepare("
        UPDATE tickets 
        SET is_archived = 0, archived_at = NULL 
        WHERE id = :id
    ");
    $stmt->execute(['id' => $restore_id]);
    
    header('Location: view_archives.php?success=1');
    exit();
}

// Delete permanently
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_id'])) {
    $delete_id = $_POST['delete_id'];
    
    try {
        $stmt = $conn->prepare("DELETE FROM ticket_responses WHERE ticket_id = :id");
        $stmt->execute(['id' => $delete_id]);
        
        $stmt = $conn->prepare("DELETE FROM status_history WHERE ticket_id = :id");
        $stmt->execute(['id' => $delete_id]);
        
        $stmt = $conn->prepare("DELETE FROM notifications WHERE ticket_id = :id");
        $stmt->execute(['id' => $delete_id]);
        
        $stmt = $conn->prepare("DELETE FROM tickets WHERE id = :id");
        $stmt->execute(['id' => $delete_id]);
        
        header('Location: view_archives.php?deleted=1');
        exit();
    } catch (PDOException $e) {
        $error = 'Error deleting ticket: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archived Tickets</title>
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

        .main-content {
            margin-left: 280px;
            padding: 2rem;
            min-height: 100vh;
        }

        .page-header {
            margin-bottom: 2rem;
        }

        .page-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }

        .page-subtitle {
            color: #64748b;
            font-size: 0.95rem;
        }

        .back-btn {
            display: inline-block;
            margin-bottom: 1.5rem;
            padding: 0.75rem 1.5rem;
            background: white;
            border: 2px solid #e2e8f0;
            color: #667eea;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .back-btn:hover {
            border-color: #667eea;
            color: #667eea;
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

        .archive-card {
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .archive-info h3 {
            margin-bottom: 0.5rem;
        }

        .archive-meta {
            color: #64748b;
            font-size: 0.9rem;
        }

        .archive-actions {
            display: flex;
            gap: 0.75rem;
        }

        .action-btn {
            padding: 0.75rem 1.5rem;
            border: 2px solid #e2e8f0;
            background: white;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .restore-btn {
            border-color: #10b981;
            color: #10b981;
        }

        .restore-btn:hover {
            background: #dcfce7;
        }

        .delete-btn {
            border-color: #ef4444;
            color: #ef4444;
        }

        .delete-btn:hover {
            background: #fee2e2;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: #94a3b8;
        }

        .empty-state i {
            font-size: 3rem;
            opacity: 0.3;
            display: block;
            margin-bottom: 1rem;
        }

        @media (max-width: 1200px) {
            .main-content {
                margin-left: 0;
            }

            .archive-card {
                flex-direction: column;
                align-items: flex-start;
            }

            .archive-actions {
                width: 100%;
                margin-top: 1rem;
            }
        }
    </style>
</head>
<body>

<?php include 'includes/hr_sidebar.php'; ?>

<div class="main-content">
    <?php include 'includes/hr_header.php'; ?>

    <div class="page-header">
        <a href="hr_tickets.php" class="back-btn">
            <i class="bi bi-arrow-left"></i> Back to Tickets
        </a>
        <h1 class="page-title"><i class="bi bi-archive"></i> Archived Tickets</h1>
        <p class="page-subtitle">Manage and restore archived support tickets</p>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">
            <i class="bi bi-check-circle-fill"></i> Ticket restored successfully!
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-success">
            <i class="bi bi-check-circle-fill"></i> Ticket permanently deleted!
        </div>
    <?php endif; ?>

    <?php if (empty($archived_tickets)): ?>
        <div class="empty-state">
            <i class="bi bi-archive"></i>
            <p>No archived tickets</p>
        </div>
    <?php else: ?>
        <div style="margin-bottom: 1.5rem; padding: 1rem; background: white; border-radius: 8px; border-left: 4px solid #667eea;">
            <strong><?php echo count($archived_tickets); ?></strong> archived ticket<?php echo count($archived_tickets) !== 1 ? 's' : ''; ?>
        </div>

        <?php foreach ($archived_tickets as $ticket): ?>
            <div class="archive-card">
                <div class="archive-info">
                    <h3>#<?php echo $ticket['id']; ?> - <?php echo htmlspecialchars($ticket['title']); ?></h3>
                    <div class="archive-meta">
                        <span><strong><?php echo htmlspecialchars($ticket['full_name']); ?></strong> • </span>
                        <span><?php echo htmlspecialchars($ticket['category_name'] ?? 'General'); ?> • </span>
                        <span>Archived: <?php echo date('M d, Y g:ia', strtotime($ticket['archived_at'])); ?></span>
                    </div>
                </div>
                <div class="archive-actions">
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="restore_id" value="<?php echo $ticket['id']; ?>">
                        <button type="submit" class="action-btn restore-btn">
                            <i class="bi bi-arrow-counterclockwise"></i> Restore
                        </button>
                    </form>
                    <form method="POST" style="display: inline;" onsubmit="return confirm('This will permanently delete the ticket. Continue?');">
                        <input type="hidden" name="delete_id" value="<?php echo $ticket['id']; ?>">
                        <button type="submit" class="action-btn delete-btn">
                            <i class="bi bi-trash"></i> Delete
                        </button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

</body>
</html>
