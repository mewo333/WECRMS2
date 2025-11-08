<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

$db = new Database();
$conn = $db->connect();

$user = getEmployeeData($conn, $_SESSION['employee_id']);

// Check if viewing archived tickets
$view_archived = isset($_GET['view']) && $_GET['view'] === 'archived';

// Fetch tickets based on view type
if ($view_archived) {
    // Fetch archived tickets
    $stmt = $conn->prepare("
        SELECT t.*, tc.category_name
        FROM tickets t
        LEFT JOIN ticket_categories tc ON t.category_id = tc.id
        WHERE t.employee_id = :employee_id AND t.is_archived = 1
        ORDER BY t.archived_at DESC
    ");
} else {
    // Fetch active tickets
    $stmt = $conn->prepare("
        SELECT t.*, tc.category_name
        FROM tickets t
        LEFT JOIN ticket_categories tc ON t.category_id = tc.id
        WHERE t.employee_id = :employee_id AND t.is_archived = 0
        ORDER BY t.created_at DESC
    ");
}
$stmt->execute(['employee_id' => $_SESSION['employee_id']]);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status Update</title>
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

        .status-container {
            padding: 2rem 3rem;
        }

        .page-title {
            font-size: 28px;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* ===== TOGGLE BUTTONS ===== */
        .view-toggle {
            display: flex;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
        }

        .toggle-btn {
            padding: 0.75rem 1.5rem;
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            color: #64748b;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .toggle-btn:hover {
            border-color: #667eea;
            color: #667eea;
            background: #f0f4ff;
        }

        .toggle-btn.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: #667eea;
        }

        /* ===== TICKETS LIST SECTION ===== */
        .tickets-selector {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .tickets-selector h4 {
            font-size: 18px;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 1.5rem;
        }

        .tickets-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
        }

        .ticket-card {
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 1.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            color: inherit;
            position: relative;
        }

        .ticket-card:hover {
            background: #f0f4ff;
            border-color: #667eea;
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.25);
        }

        .ticket-card.archived {
            background: #f8f9fa;
            border-color: #dee2e6;
            opacity: 0.9;
        }

        .ticket-card.archived:hover {
            background: #e9ecef;
            border-color: #adb5bd;
        }

        .archived-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: #6c757d;
            color: white;
            padding: 0.25rem 0.6rem;
            border-radius: 6px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .ticket-card h5 {
            font-size: 16px;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 0.75rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            padding-right: 60px;
        }

        .ticket-card p {
            font-size: 13px;
            color: #718096;
            margin: 0.5rem 0;
        }

        .ticket-meta {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #e2e8f0;
        }

        .ticket-meta p {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin: 0;
        }

        .ticket-badge {
            display: inline-block;
            padding: 0.4rem 0.85rem;
            border-radius: 8px;
            font-size: 11px;
            font-weight: 700;
            margin-top: 1rem;
            text-transform: uppercase;
        }

        .badge-pending {
            background: #fff3cd;
            color: #856404;
        }

        .badge-in-progress,
        .badge-in_progress {
            background: #cfe2ff;
            color: #084298;
        }

        .badge-resolved {
            background: #d4edda;
            color: #155724;
        }

        .badge-closed {
            background: #e2e8f0;
            color: #475569;
        }

        /* ===== EMPTY STATE ===== */
        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: #94a3b8;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
            display: block;
        }

        .empty-state p {
            font-size: 16px;
            margin: 0;
        }

        @media (max-width: 768px) {
            .status-container {
                padding: 1.5rem;
            }

            .tickets-grid {
                grid-template-columns: 1fr;
            }

            .view-toggle {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <?php include_once 'includes/sidebar.php'; ?>

    <div class="main-content">
        <?php include_once 'includes/employee_header.php'; ?>

        <div class="status-container">
            <h1 class="page-title">
                <i class="bi bi-clipboard-check"></i> Status Update
            </h1>

            <!-- View Toggle -->
            <div class="view-toggle">
                <a href="status_update.php" class="toggle-btn <?php echo !$view_archived ? 'active' : ''; ?>">
                    <i class="bi bi-ticket-detailed"></i> Active Tickets
                </a>
                <a href="status_update.php?view=archived" class="toggle-btn <?php echo $view_archived ? 'active' : ''; ?>">
                    <i class="bi bi-archive"></i> Archived Tickets
                </a>
            </div>

            <!-- Tickets Selector -->
            <div class="tickets-selector">
                <h4>
                    <i class="bi bi-<?php echo $view_archived ? 'archive' : 'list-check'; ?>"></i> 
                    <?php echo $view_archived ? 'Archived Tickets' : 'Your Active Tickets'; ?>
                </h4>
                <?php if (empty($tickets)): ?>
                    <div class="empty-state">
                        <i class="bi bi-<?php echo $view_archived ? 'archive' : 'inbox'; ?>"></i>
                        <p><?php echo $view_archived ? 'No archived tickets' : 'No active tickets found'; ?></p>
                    </div>
                <?php else: ?>
                    <div class="tickets-grid">
                        <?php foreach ($tickets as $ticket): ?>
                            <a href="ticket_detail.php?id=<?php echo $ticket['id']; ?>" class="ticket-card <?php echo $view_archived ? 'archived' : ''; ?>">
                                <?php if ($view_archived): ?>
                                    <span class="archived-badge">Archived</span>
                                <?php endif; ?>
                                <h5><?php echo htmlspecialchars($ticket['title']); ?></h5>
                                
                                <div class="ticket-meta">
                                    <p>
                                        <i class="bi bi-hash"></i>
                                        <strong>Ticket:</strong> <?php echo htmlspecialchars($ticket['ticket_number']); ?>
                                    </p>
                                    <p>
                                        <i class="bi bi-folder"></i>
                                        <strong>Category:</strong> <?php echo htmlspecialchars($ticket['category_name'] ?? 'General'); ?>
                                    </p>
                                    <p>
                                        <i class="bi bi-exclamation-triangle"></i>
                                        <strong>Priority:</strong> <?php echo ucfirst($ticket['priority']); ?>
                                    </p>
                                    <p>
                                        <i class="bi bi-calendar3"></i>
                                        <strong>Created:</strong> <?php echo date('M d, Y', strtotime($ticket['created_at'])); ?>
                                    </p>
                                    <?php if ($view_archived && !empty($ticket['archived_at'])): ?>
                                        <p style="font-size: 11px; color: #6c757d;">
                                            <i class="bi bi-calendar-x"></i>
                                            <strong>Archived:</strong> <?php echo date('M d, Y', strtotime($ticket['archived_at'])); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                                
                                <span class="ticket-badge badge-<?php echo strtolower(str_replace(' ', '-', $ticket['status'])); ?>">
                                    <?php echo str_replace('_', ' ', $ticket['status']); ?>
                                </span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</body>
</html>
