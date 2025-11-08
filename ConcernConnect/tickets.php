<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

$db = new Database();
$conn = $db->connect();

$user = getEmployeeData($conn, $_SESSION['employee_id']);

// Get ticket statistics for the employee
$stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN LOWER(status) = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN LOWER(status) = 'in progress' OR LOWER(status) = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
        SUM(CASE WHEN LOWER(status) = 'resolved' THEN 1 ELSE 0 END) as resolved
    FROM tickets 
    WHERE employee_id = :employee_id
");
$stmt->execute(['employee_id' => $_SESSION['employee_id']]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Initialize variables to avoid undefined warnings
$filter = $_GET['filter'] ?? 'all';
$search = $_GET['search'] ?? '';

// Handle AJAX requests
if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
    $query = "SELECT * FROM tickets WHERE employee_id = :employee_id";
    $params = ['employee_id' => $_SESSION['employee_id']];
    
    if ($filter !== 'all') {
        $query .= " AND LOWER(status) = :status";
        $params['status'] = strtolower($filter);
    }
    
    if ($search) {
        $query .= " AND (LOWER(title) LIKE :search OR LOWER(ticket_number) LIKE :search)";
        $params['search'] = '%' . strtolower($search) . '%';
    }
    
    $query .= " ORDER BY created_at DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode($tickets);
    exit;
}

// Initial page load - fetch all tickets
$query = "SELECT * FROM tickets WHERE employee_id = :employee_id ORDER BY created_at DESC";
$stmt = $conn->prepare($query);
$stmt->execute(['employee_id' => $_SESSION['employee_id']]);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Tickets</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        /* Base Layout */
        body {
            font-family: "Segoe UI", Roboto, Arial, sans-serif;
            background: #f5f7fa;
            color: #333;
            margin: 0;
            padding: 0;
        }

        /* Main Content */
        .main-content {
            padding: 0;
            background: transparent;
            border-radius: 0;
            min-height: 100vh;
        }

        /* Header Padding */
        .tickets-header-section {
            padding: 2rem 3rem;
            margin-bottom: 1rem;
        }

        /* Stats Section */
        .ticket-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin: 0 3rem 2rem 3rem;
        }

        .stat-card {
            background: #ffffff;
            padding: 1.5rem;
            border-radius: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            text-align: center;
            transition: transform 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-4px);
        }

        .stat-label {
            font-size: 0.85rem;
            color: #718096;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 600;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            margin-top: 0.75rem;
            color: #2d3748;
        }

        /* Filters */
        .ticket-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 2rem;
            align-items: center;
            padding: 0 3rem;
        }

        .search-input, .filter-select {
            padding: 0.75rem 1rem;
            border-radius: 12px;
            border: 2px solid #e2e8f0;
            background: #fff;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .search-input {
            flex: 1;
            min-width: 250px;
        }

        .search-input:focus, .filter-select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        /* Ticket List */
        .tickets-list {
            display: flex;
            flex-direction: column;
            gap: 1.2rem;
            padding: 0 3rem 2rem 3rem;
        }

        .ticket-item {
            background: #ffffff;
            border-radius: 16px;
            padding: 1.5rem 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            transition: all 0.2s;
            border-left: 4px solid #667eea;
        }

        .ticket-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .ticket-info {
            flex: 1;
        }

        .ticket-info h3 {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #2d3748;
        }

        .ticket-info h3 a {
            color: #2d3748;
            text-decoration: none;
            transition: color 0.2s;
        }

        .ticket-info h3 a:hover {
            color: #667eea;
        }

        .ticket-info p {
            color: #718096;
            font-size: 0.95rem;
            margin-bottom: 0.75rem;
        }

        .ticket-meta {
            font-size: 0.85rem;
            color: #64748b;
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-top: 0.75rem;
        }

        .ticket-meta span {
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .ticket-assigned {
            font-size: 0.9rem;
            color: #4a5568;
            margin-top: 0.5rem;
        }

        .ticket-status {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 0.75rem;
            margin-left: 2rem;
        }

        .ticket-status .badge {
            font-size: 0.8rem;
            padding: 0.5rem 0.75rem;
            border-radius: 20px;
            font-weight: 600;
            text-transform: uppercase;
        }

        /* Badge Colors */
        .badge-low {
            background: #d4edda;
            color: #155724;
        }

        .badge-medium {
            background: #fff3cd;
            color: #856404;
        }

        .badge-high {
            background: #f8d7da;
            color: #721c24;
        }

        .badge-pending {
            background: #fff3cd;
            color: #856404;
        }

        .badge-in-progress, .badge-in_progress {
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

        .btn-view-details {
            background: #667eea;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s;
        }

        .btn-view-details:hover {
            background: #5568d3;
            transform: translateY(-1px);
        }

        .no-tickets {
            text-align: center;
            padding: 3rem 2rem;
            color: #94a3b8;
            background: #ffffff;
            border-radius: 16px;
            font-size: 1rem;
            margin: 2rem 3rem;
        }

        .ticket-header {
            padding: 0 3rem;
            margin-bottom: 2rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #5568d3 0%, #6a3f95 100%);
            transform: translateY(-2px);
        }

        /* Responsive */
        @media (max-width: 992px) {
            .tickets-header-section,
            .ticket-stats,
            .ticket-filters,
            .tickets-list,
            .ticket-header {
                padding-left: 1.5rem;
                padding-right: 1.5rem;
            }

            .ticket-stats {
                grid-template-columns: repeat(2, 1fr);
                margin: 0 1.5rem 2rem 1.5rem;
            }

            .ticket-item {
                flex-direction: column;
                gap: 1rem;
            }

            .ticket-status {
                align-items: flex-start;
                margin-left: 0;
            }

            .no-tickets {
                margin: 2rem 1.5rem;
            }
        }

        @media (max-width: 768px) {
            .ticket-stats {
                grid-template-columns: 1fr;
            }

            .ticket-filters {
                flex-direction: column;
            }

            .search-input {
                width: 100%;
            }

            .ticket-item {
                padding: 1.25rem;
            }

            .ticket-meta {
                flex-direction: column;
                gap: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <?php include_once 'includes/sidebar.php'; ?>

    <div class="main-content">
        <!-- Top Header -->
        <?php include_once 'includes/employee_header.php'; ?>
        
        <div class="ticket-header">
            <a href="create_ticket.php" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Create New Ticket
            </a>
        </div>
        
        <div class="ticket-stats">
            <div class="stat-card">
                <div class="stat-label">Total Tickets</div>
                <div class="stat-value"><?php echo $stats['total'] ?? 0; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Pending</div>
                <div class="stat-value" style="color: #f59e0b;"><?php echo $stats['pending'] ?? 0; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">In Progress</div>
                <div class="stat-value" style="color: #3b82f6;"><?php echo $stats['in_progress'] ?? 0; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Resolved</div>
                <div class="stat-value" style="color: #10b981;"><?php echo $stats['resolved'] ?? 0; ?></div>
            </div>
        </div>
        
        <div class="ticket-filters">
            <input type="text" id="search-tickets" placeholder="🔍 Search tickets..." value="<?php echo htmlspecialchars($search); ?>" class="search-input">
            <select id="filter-status" class="filter-select">
                <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                <option value="pending" <?php echo $filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="in progress" <?php echo $filter === 'in progress' ? 'selected' : ''; ?>>In Progress</option>
                <option value="resolved" <?php echo $filter === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                <option value="closed" <?php echo $filter === 'closed' ? 'selected' : ''; ?>>Closed</option>
            </select>
        </div>
        
        <div class="tickets-list">
            <?php if (empty($tickets)): ?>
                <div class="no-tickets">
                    <i class="bi bi-inbox" style="font-size: 2.5rem; margin-bottom: 1rem; opacity: 0.5; display: block;"></i>
                    <p>No tickets found.</p>
                </div>
            <?php else: ?>
                <?php foreach ($tickets as $ticket): ?>
                    <div class="ticket-item">
                        <div class="ticket-info">
                            <h3>
                                <a href="ticket_detail.php?id=<?php echo $ticket['id']; ?>">
                                    <?php echo htmlspecialchars($ticket['title']); ?>
                                </a>
                            </h3>
                            <p><?php echo htmlspecialchars(substr($ticket['description'], 0, 100)); ?>...</p>
                            <div class="ticket-meta">
                                <span><i class="bi bi-hash"></i> <?php echo htmlspecialchars($ticket['ticket_number']); ?></span>
                                <span class="badge <?php echo getPriorityClass($ticket['priority']); ?>">
                                    <i class="bi bi-flag-fill"></i> <?php echo htmlspecialchars($ticket['priority']); ?>
                                </span>
                                <span><i class="bi bi-folder"></i> <?php echo htmlspecialchars($ticket['category_id']); ?></span>
                                <span><i class="bi bi-calendar"></i> <?php echo date('M d, Y', strtotime($ticket['created_at'])); ?></span>
                            </div>
                            <div class="ticket-assigned">
                                <i class="bi bi-person-check-fill"></i> Assigned to: <?php echo !empty($ticket['assigned_to']) ? htmlspecialchars($ticket['assigned_to']) : 'Not assigned'; ?>
                            </div>
                        </div>
                        <div class="ticket-status">
                            <span class="badge <?php echo getStatusClass($ticket['status']); ?>">
                                <?php echo strtoupper(str_replace('_', ' ', $ticket['status'])); ?>
                            </span>
                            <a href="ticket_detail.php?id=<?php echo $ticket['id']; ?>" class="btn-view-details">
                                <i class="bi bi-eye-fill"></i> View Details
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <button class="chatbot-toggle" id="chatbot-toggle">💬 Help</button>
    <div class="chatbot-window" id="chatbot-window">
        <div class="chatbot-header">
            <span>🤖Chatbot</span>
            <button id="close-chatbot">✕</button>
        </div>
        <div class="chatbot-messages" id="chatbot-messages"></div>
        <div class="chatbot-input">
            <input type="text" id="chatbot-input" placeholder="Type your question...">
            <button id="send-message">Send</button>
        </div>
    </div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="assets/js/dashboard.js?v=<?php echo time(); ?>"></script>
<script src="assets/js/chatbot.js?v=<?php echo time(); ?>"></script>
<script src="assets/js/tickets.js?v=<?php echo time(); ?>"></script>

</body>
</html>
