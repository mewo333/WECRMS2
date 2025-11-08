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

// Get statistics (EXCLUDING ARCHIVED)
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM tickets WHERE is_archived = 0");
$stmt->execute();
$total_tickets = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $conn->prepare("SELECT COUNT(*) as open FROM tickets WHERE status = 'pending' AND is_archived = 0");
$stmt->execute();
$open_tickets = $stmt->fetch(PDO::FETCH_ASSOC)['open'];

$stmt = $conn->prepare("SELECT COUNT(*) as in_progress FROM tickets WHERE status = 'in progress' AND is_archived = 0");
$stmt->execute();
$in_progress_tickets = $stmt->fetch(PDO::FETCH_ASSOC)['in_progress'];

$stmt = $conn->prepare("SELECT COUNT(*) as completed FROM tickets WHERE status IN ('resolved', 'closed') AND is_archived = 0");
$stmt->execute();
$completed_tickets = $stmt->fetch(PDO::FETCH_ASSOC)['completed'];

$stmt = $conn->prepare("SELECT COUNT(*) as `high_priority` FROM tickets WHERE `priority` = 'high' AND is_archived = 0");
$stmt->execute();
$high_priority = $stmt->fetch(PDO::FETCH_ASSOC)['high_priority'];

// Get category breakdown
$stmt = $conn->prepare("
    SELECT tc.category_name, COUNT(*) as count 
    FROM tickets t 
    LEFT JOIN ticket_categories tc ON t.category_id = tc.id 
    WHERE t.is_archived = 0
    GROUP BY t.category_id 
    ORDER BY count DESC
");
$stmt->execute();
$category_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all tickets with employee photos (EXCLUDING ARCHIVED)
$stmt = $conn->prepare("
    SELECT t.*, e.full_name, e.employee_id, e.department, e.photo, tc.category_name
    FROM tickets t
    JOIN employees e ON t.employee_id = e.employee_id
    LEFT JOIN ticket_categories tc ON t.category_id = tc.id
    WHERE t.is_archived = 0
    ORDER BY t.created_at DESC
");
$stmt->execute();
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Sort by priority in PHP
usort($tickets, function($a, $b) {
    $priority_order = ['high' => 1, 'medium' => 2, 'low' => 3];
    $a_priority = $priority_order[strtolower($a['priority'])] ?? 4;
    $b_priority = $priority_order[strtolower($b['priority'])] ?? 4;
    
    if ($a_priority !== $b_priority) {
        return $a_priority <=> $b_priority;
    }
    
    return strtotime($b['created_at']) <=> strtotime($a['created_at']);
});

// Calculate average resolution time
$stmt = $conn->prepare("
    SELECT AVG(DATEDIFF(updated_at, created_at)) as avg_days
    FROM tickets 
    WHERE status IN ('resolved', 'closed') AND is_archived = 0
");
$stmt->execute();
$avg_resolution = $stmt->fetch(PDO::FETCH_ASSOC)['avg_days'] ?? 0;

// Resolution rate
$resolution_rate = $total_tickets > 0 ? round(($completed_tickets / $total_tickets) * 100, 0) : 0;

// Calculate percentages by status and priority
$pending_percent = $total_tickets > 0 ? round(($open_tickets / $total_tickets) * 100, 0) : 0;
$in_progress_percent = $total_tickets > 0 ? round(($in_progress_tickets / $total_tickets) * 100, 0) : 0;
$high_priority_percent = $total_tickets > 0 ? round(($high_priority / $total_tickets) * 100, 0) : 0;

// Get ticket progress based on status
function getTicketProgress($status) {
    $progress_map = [
        'pending' => 25,
        'in progress' => 50,
        'resolved' => 75,
        'closed' => 100
    ];
    return $progress_map[strtolower($status)] ?? 0;
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tickets Management - HR System</title>
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

        .tab-navigation {
            display: flex;
            gap: 0;
            margin-bottom: 2rem;
            background: white;
            border-radius: 12px;
            padding: 0.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            flex-wrap: wrap;
        }

        .tab-btn {
            flex: 1;
            padding: 1rem;
            border: none;
            background: transparent;
            color: #64748b;
            font-weight: 600;
            cursor: pointer;
            border-radius: 8px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            justify-content: center;
            min-width: 100px;
        }

        .tab-btn.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .tab-btn:hover {
            background: #f0f4ff;
        }

        .tab-btn.active:hover {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #64748b;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .stat-icon {
            font-size: 1.5rem;
            color: #667eea;
        }

        .table-section {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            overflow: hidden;
        }

        .table-header {
            padding: 1.5rem;
            border-bottom: 2px solid #f1f5f9;
        }

        .table-filters {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 0.5rem 1rem;
            border: 2px solid #e2e8f0;
            background: white;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            color: #64748b;
            font-size: 0.9rem;
        }

        .filter-btn:hover {
            border-color: #667eea;
            color: #667eea;
        }

        .tickets-table {
            width: 100%;
            border-collapse: collapse;
        }

        .table-head {
            background: #f8fafc;
            border-bottom: 2px solid #e2e8f0;
        }

        .table-head th {
            padding: 1rem 1.5rem;
            text-align: left;
            font-weight: 600;
            color: #64748b;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .table-body td {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid #f1f5f9;
        }

        .table-body tr:hover {
            background: #f8fafc;
        }

        .table-body tr:last-child td {
            border-bottom: none;
        }

        .action-btn {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            border: none;
            background: #f1f5f9;
            color: #64748b;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            font-size: 0.95rem;
            text-decoration: none;
        }

        .action-btn:hover {
            background: #667eea;
            color: white;
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

        .ticket-card {
    background: white;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 1rem;
    transition: all 0.3s ease;
}

.ticket-card:hover {
    border-color: #667eea;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.1);
    background: #f8fafc;
    transform: translateY(-2px);
}


        .ticket-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
        }

        .ticket-title {
            font-weight: 700;
            color: #1e293b;
            font-size: 1.05rem;
        }

        .ticket-manage-btn {
            background: white;
            border: 2px solid #e2e8f0;
            color: #1e293b;
            padding: 0.5rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .ticket-manage-btn:hover {
            border-color: #667eea;
            color: #667eea;
        }

        .ticket-manage-btn.archive-btn {
            border-color: #10b981;
            color: #10b981;
        }

        .ticket-manage-btn.archive-btn:hover {
            background: #dcfce7;
        }

        .ticket-meta {
            display: flex;
            align-items: center;
            gap: 1rem;
            font-size: 0.9rem;
            color: #64748b;
            margin-bottom: 0.75rem;
            flex-wrap: wrap;
        }

        .ticket-meta-tag {
            display: inline-block;
            padding: 0.3rem 0.8rem;
            background: #f0f4ff;
            color: #667eea;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .priority-high-tag {
            background: #fee2e2;
            color: #991b1b;
        }

        .priority-medium-tag {
            background: #fef3c7;
            color: #92400e;
        }

        .ticket-description {
            color: #475569;
            font-size: 0.9rem;
            margin-bottom: 1rem;
            line-height: 1.5;
        }

        .ticket-dates {
            color: #94a3b8;
            font-size: 0.85rem;
            margin-bottom: 1rem;
        }

        .progress-bar-thin {
            flex: 1;
            height: 8px;
            background: #e2e8f0;
            border-radius: 4px;
            overflow: hidden;
        }

        .progress-fill-thin {
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .progress-percent {
            font-weight: 700;
            color: #1e293b;
            min-width: 60px;
            text-align: right;
        }

        .analytics-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .analytics-card {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            text-align: center;
        }

        .analytics-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 0.5rem;
        }

        .analytics-label {
            color: #64748b;
            font-size: 0.9rem;
        }

        .insights-box {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .insights-title {
            font-size: 1.125rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
        }

        .insight-item {
            padding: 1.5rem;
            background: #f8fafc;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            border-left: 4px solid #667eea;
        }

        .insight-title {
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 0.5rem;
        }

        .insight-text {
            color: #64748b;
            font-size: 0.9rem;
            line-height: 1.5;
        }

        .analytics-section {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            margin-top: 2rem;
        }

        .analytics-section-title {
            font-size: 1.125rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
        }

        .analytics-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .analytics-item-name {
            font-weight: 600;
            color: #1e293b;
            min-width: 150px;
        }

        .analytics-item-bar {
            flex: 1;
            height: 12px;
            background: #e2e8f0;
            border-radius: 6px;
            margin: 0 1.5rem;
            overflow: hidden;
        }

        .analytics-item-fill {
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 6px;
        }

        .analytics-item-percent {
            font-weight: 700;
            color: #667eea;
            min-width: 50px;
            text-align: right;
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
    transition: all 0.3s ease;
    }

    .archive-card:hover {
    border-color: #667eea;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.1);
    background: #f8fafc;
    }


        .restore-btn {
            padding: 0.75rem 1.5rem;
            background: #dcfce7;
            color: #15803d;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
        }

        .restore-btn:hover {
            background: #bbf7d0;
        }

        .delete-btn {
            padding: 0.75rem 1.5rem;
            background: #fee2e2;
            color: #991b1b;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
        }

        .delete-btn:hover {
            background: #fecaca;
        }

        @media (max-width: 1200px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .analytics-grid {
                grid-template-columns: 1fr;
            }

            .tab-btn {
                min-width: 90px;
                padding: 0.75rem 0.5rem;
                font-size: 0.9rem;
            }

            .archive-card {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>

<?php include 'includes/hr_sidebar.php'; ?>

<div class="main-content">
    <?php include 'includes/hr_header.php'; ?>

    <div class="page-header">
        <h1 class="page-title">Tickets Management</h1>
        <p class="page-subtitle">Manage employee concerns and requests with priority tracking</p>
    </div>

    <div class="tab-navigation">
        <button class="tab-btn active" onclick="switchTab(event, 'overview')">
            <i class="bi bi-speedometer2"></i> Overview
        </button>
        <button class="tab-btn" onclick="switchTab(event, 'tickets')">
            <i class="bi bi-ticket"></i> Tickets
        </button>
        <button class="tab-btn" onclick="switchTab(event, 'analytics')">
            <i class="bi bi-bar-chart"></i> Analytics
        </button>
        <button class="tab-btn" onclick="switchTab(event, 'archives')">
            <i class="bi bi-archive"></i> Archives
        </button>
        <button class="tab-btn" onclick="switchTab(event, 'trends')">
            <i class="bi bi-graph-up"></i> Trends
        </button>
    </div>

    <!-- OVERVIEW TAB -->
    <div id="overview" class="tab-content active">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?php echo $total_tickets; ?></div>
                <div class="stat-label">
                    <span class="stat-icon"><i class="bi bi-ticket"></i></span>
                    Total Tickets
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-value"><?php echo $open_tickets; ?></div>
                <div class="stat-label">
                    <span class="stat-icon"><i class="bi bi-bookmark"></i></span>
                    Pending
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-value"><?php echo $in_progress_tickets; ?></div>
                <div class="stat-label">
                    <span class="stat-icon"><i class="bi bi-hourglass-split"></i></span>
                    In Progress
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-value"><?php echo $completed_tickets; ?></div>
                <div class="stat-label">
                    <span class="stat-icon"><i class="bi bi-check-circle"></i></span>
                    Completed
                </div>
            </div>
        </div>

        <div class="table-section">
            <div class="table-header">
                <div class="table-filters">
                    <button class="filter-btn">All Status</button>
                    <button class="filter-btn">All Priority</button>
                    <button class="filter-btn">All Categories</button>
                </div>
            </div>

            <?php if (empty($tickets)): ?>
                <div class="empty-state">
                    <i class="bi bi-inbox"></i>
                    <p>No tickets found</p>
                </div>
            <?php else: ?>
                <table class="tickets-table">
                    <thead class="table-head">
                        <tr>
                            <th>Ticket ID</th>
                            <th>Requester</th>
                            <th>Subject</th>
                            <th>Category</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody class="table-body">
                        <?php foreach ($tickets as $ticket): ?>
                            <tr>
                                <td><span style="font-weight: 700; color: #667eea;">#<?php echo $ticket['id']; ?></span></td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                                        <?php 
                                        $photoPath = (!empty($ticket['photo']) && file_exists($ticket['photo'])) 
                                            ? htmlspecialchars($ticket['photo']) 
                                            : '';
                                        
                                        if ($photoPath): 
                                        ?>
                                            <img src="<?php echo $photoPath; ?>" alt="<?php echo htmlspecialchars($ticket['full_name']); ?>" style="width: 36px; height: 36px; border-radius: 50%; object-fit: cover; border: 2px solid #e2e8f0;" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                        <?php endif; ?>
                                        
                                        <div style="width: 36px; height: 36px; border-radius: 50%; background: #e0e7ff; display: <?php echo !$photoPath ? 'flex' : 'none'; ?>; align-items: center; justify-content: center; font-weight: 600; color: #4f46e5; font-size: 0.85rem; border: 2px solid #e2e8f0; flex-shrink: 0;">
                                            <?php echo strtoupper(substr($ticket['full_name'], 0, 2)); ?>
                                        </div>
                                        
                                        <div>
                                            <div style="font-weight: 600; color: #1e293b;"><?php echo htmlspecialchars($ticket['full_name']); ?></div>
                                            <div style="color: #94a3b8; font-size: 0.85rem;"><?php echo htmlspecialchars($ticket['employee_id']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars(substr($ticket['title'], 0, 50)); ?></td>
                                <td><span style="display: inline-block; padding: 0.35rem 0.75rem; border-radius: 20px; font-size: 0.8rem; font-weight: 600; background: #e0e7ff; color: #667eea;"><?php echo htmlspecialchars($ticket['category_name'] ?? 'General'); ?></span></td>
                                <td><span style="display: inline-block; padding: 0.35rem 0.75rem; border-radius: 20px; font-size: 0.8rem; font-weight: 600; background: <?php echo strtolower($ticket['priority']) === 'high' ? '#fee2e2' : (strtolower($ticket['priority']) === 'medium' ? '#fef3c7' : '#dbeafe'); ?>; color: <?php echo strtolower($ticket['priority']) === 'high' ? '#991b1b' : (strtolower($ticket['priority']) === 'medium' ? '#92400e' : '#1e40af'); ?>;"><?php echo htmlspecialchars($ticket['priority']); ?></span></td>
                                <td><span style="display: inline-block; padding: 0.35rem 0.75rem; border-radius: 20px; font-size: 0.8rem; font-weight: 600; background: <?php echo strtolower($ticket['status']) === 'pending' ? '#fef3c7' : (strtolower($ticket['status']) === 'in progress' ? '#dbeafe' : (strtolower($ticket['status']) === 'resolved' ? '#dcfce7' : '#f1f5f9')); ?>; color: <?php echo strtolower($ticket['status']) === 'pending' ? '#92400e' : (strtolower($ticket['status']) === 'in progress' ? '#1e40af' : (strtolower($ticket['status']) === 'resolved' ? '#15803d' : '#475569')); ?>;"><?php echo htmlspecialchars($ticket['status']); ?></span></td>
                                <td><?php echo date('m/d/y', strtotime($ticket['created_at'])); ?></td>
                                <td><a href="hr_ticket_detail.php?id=<?php echo $ticket['id']; ?>" class="action-btn" title="View"><i class="bi bi-eye"></i></a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

   <!-- TICKETS TAB -->
<div id="tickets" class="tab-content">
    <?php 
    $tickets_by_date = [];
    foreach ($tickets as $ticket) {
        $ticket_date = date('Y-m-d', strtotime($ticket['created_at']));
        if (!isset($tickets_by_date[$ticket_date])) {
            $tickets_by_date[$ticket_date] = [];
        }
        $tickets_by_date[$ticket_date][] = $ticket;
    }
    krsort($tickets_by_date);
    ?>

    <?php if (empty($tickets)): ?>
        <div class="empty-state">
            <i class="bi bi-inbox"></i>
            <p>No tickets found</p>
        </div>
    <?php else: ?>
        <?php foreach ($tickets_by_date as $date => $date_tickets): 
            $formatted_date = date('l, F j, Y', strtotime($date));
            $ticket_count = count($date_tickets);
        ?>
            <div style="margin-bottom: 2.5rem;">
                <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 2px solid #e2e8f0;">
                    <i class="bi bi-calendar3" style="font-size: 1.5rem; color: #667eea;"></i>
                    <div>
                        <div style="font-weight: 700; font-size: 1.05rem; color: #1e293b;"><?php echo $formatted_date; ?></div>
                        <div style="color: #64748b; font-size: 0.9rem;"><?php echo $ticket_count; ?> ticket<?php echo $ticket_count !== 1 ? 's' : ''; ?></div>
                    </div>
                </div>

                <?php foreach ($date_tickets as $ticket): 
                    $progress = getTicketProgress($ticket['status']);
                ?>
                    <div class="ticket-card" style="cursor: pointer;" onclick="window.location.href='hr_ticket_detail.php?id=<?php echo $ticket['id']; ?>'">
                        <div class="ticket-header">
                            <div style="flex: 1;">
                                <div class="ticket-title">#<?php echo $ticket['id']; ?> - <?php echo htmlspecialchars(substr($ticket['title'], 0, 50)); ?></div>
                                <div class="ticket-meta">
                                    <span><?php echo htmlspecialchars($ticket['full_name']); ?></span>
                                    <span>•</span>
                                    <span class="ticket-meta-tag"><?php echo htmlspecialchars($ticket['category_name'] ?? 'General'); ?></span>
                                    <span class="ticket-meta-tag <?php echo strtolower($ticket['priority']) === 'high' ? 'priority-high-tag' : (strtolower($ticket['priority']) === 'medium' ? 'priority-medium-tag' : ''); ?>"><?php echo htmlspecialchars($ticket['priority']); ?></span>
                                </div>
                            </div>
                            <?php if (strtolower($ticket['status']) === 'closed'): ?>
                                <button class="ticket-manage-btn archive-btn" onclick="event.stopPropagation(); archiveTicket(<?php echo $ticket['id']; ?>)">
                                    <i class="bi bi-archive"></i> Archive
                                </button>
                            <?php else: ?>
                                <button class="ticket-manage-btn" onclick="event.stopPropagation(); window.location.href='hr_ticket_detail.php?id=<?php echo $ticket['id']; ?>'">
                                    <i class="bi bi-pencil"></i> Manage
                                </button>
                            <?php endif; ?>
                        </div>

                        <div class="ticket-description">
                            <?php echo htmlspecialchars(substr($ticket['description'], 0, 100)); ?>...
                        </div>

                        <div class="ticket-dates">
                            Created: <?php echo date('M d, Y \a\t g:ia', strtotime($ticket['created_at'])); ?> | Updated: <?php echo date('M d, Y \a\t g:ia', strtotime($ticket['updated_at'])); ?>
                        </div>

                        <div style="display: flex; align-items: center; gap: 1rem; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #f1f5f9;">
                            <div style="flex: 1;">
                                <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.5rem;">
                                    <i class="bi bi-hourglass-split" style="color: #667eea;"></i>
                                    <span style="font-size: 0.85rem; color: #64748b; font-weight: 600;">
                                        <?php 
                                        $status_labels = [
                                            'pending' => 'Pending',
                                            'in progress' => 'In Progress',
                                            'resolved' => 'Resolved',
                                            'closed' => 'Completed'
                                        ];
                                        echo $status_labels[strtolower($ticket['status'])] ?? ucfirst($ticket['status']);
                                        ?>
                                    </span>
                                </div>
                                <div class="progress-bar-thin">
                                    <div class="progress-fill-thin" style="width: <?php echo $progress; ?>%; background: linear-gradient(90deg, 
                                        <?php 
                                        if ($progress === 25) echo '#fbbf24, #f59e0b';
                                        elseif ($progress === 50) echo '#3b82f6, #1d4ed8';
                                        elseif ($progress === 75) echo '#10b981, #059669';
                                        else echo '#667eea, #764ba2';
                                        ?>
                                    )"></div>
                                </div>
                            </div>
                            <div class="progress-percent">
                                <div style="font-size: 1.25rem; font-weight: 700; color: #667eea;"><?php echo $progress; ?>%</div>
                                <div style="font-size: 0.75rem; color: #94a3b8;">Complete</div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>


    <!-- ANALYTICS TAB -->
    <div id="analytics" class="tab-content">
        <div class="analytics-grid">
            <div class="analytics-card">
                <div class="analytics-value"><?php echo round($avg_resolution, 1); ?></div>
                <div class="analytics-label">Average Resolution Time (days)</div>
            </div>
            <div class="analytics-card">
                <div class="analytics-value"><?php echo $resolution_rate; ?>%</div>
                <div class="analytics-label">Resolution Rate</div>
            </div>
            <div class="analytics-card">
                <div class="analytics-value">4.2/5</div>
                <div class="analytics-label">Employee Satisfaction</div>
            </div>
        </div>

        <div class="analytics-section">
            <div class="analytics-section-title">Ticket Distribution</div>

            <div class="analytics-item">
                <div class="analytics-item-name">Pending</div>
                <div class="analytics-item-bar">
                    <div class="analytics-item-fill" style="width: <?php echo $pending_percent; ?>%"></div>
                </div>
                <div class="analytics-item-percent"><?php echo $pending_percent; ?>%</div>
            </div>

            <div class="analytics-item">
                <div class="analytics-item-name">In Progress</div>
                <div class="analytics-item-bar">
                    <div class="analytics-item-fill" style="width: <?php echo $in_progress_percent; ?>%"></div>
                </div>
                <div class="analytics-item-percent"><?php echo $in_progress_percent; ?>%</div>
            </div>

            <div class="analytics-item">
                <div class="analytics-item-name">Completed</div>
                <div class="analytics-item-bar">
                    <div class="analytics-item-fill" style="width: <?php echo $resolution_rate; ?>%"></div>
                </div>
                <div class="analytics-item-percent"><?php echo $resolution_rate; ?>%</div>
            </div>

            <div class="analytics-item">
                <div class="analytics-item-name">High Priority</div>
                <div class="analytics-item-bar">
                    <div class="analytics-item-fill" style="width: <?php echo $high_priority_percent; ?>%"></div>
                </div>
                <div class="analytics-item-percent"><?php echo $high_priority_percent; ?>%</div>
            </div>
        </div>

        <div class="analytics-section">
            <div class="analytics-section-title">Request by Category</div>

            <?php 
            if (!empty($category_stats)) {
                $max_count = max(array_column($category_stats, 'count')) ?: 1;
                foreach ($category_stats as $stat): 
                    $percentage = ($stat['count'] / $max_count) * 100;
                    $category_percent = $total_tickets > 0 ? round(($stat['count'] / $total_tickets) * 100, 0) : 0;
                ?>
                    <div class="analytics-item">
                        <div class="analytics-item-name"><?php echo htmlspecialchars($stat['category_name'] ?? 'Other'); ?></div>
                        <div class="analytics-item-bar">
                            <div class="analytics-item-fill" style="width: <?php echo $percentage; ?>%"></div>
                        </div>
                        <div class="analytics-item-percent"><?php echo $category_percent; ?>%</div>
                    </div>
                <?php 
                endforeach;
            }
            ?>
        </div>
    </div>

<!-- ARCHIVES TAB -->
<div id="archives" class="tab-content">
    <div style="background: white; border-radius: 12px; padding: 2rem; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
        <?php if (empty($archived_tickets)): ?>
            <div class="empty-state">
                <i class="bi bi-archive"></i>
                <p>No archived tickets</p>
            </div>
        <?php else: ?>
            <div style="margin-bottom: 1.5rem; padding: 1rem; background: #f0f4ff; border-radius: 8px; border-left: 4px solid #667eea;">
                <strong style="color: #667eea;"><?php echo count($archived_tickets); ?></strong> <span style="color: #64748b;">archived ticket<?php echo count($archived_tickets) !== 1 ? 's' : ''; ?></span>
            </div>

            <?php foreach ($archived_tickets as $ticket): ?>
                <div class="archive-card" style="cursor: pointer;" onclick="window.location.href='hr_ticket_detail.php?id=<?php echo $ticket['id']; ?>&archived=1'">
                    <div style="flex: 1;">
                        <div style="font-weight: 700; color: #1e293b; font-size: 1.05rem; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.75rem;">
                            #<?php echo $ticket['id']; ?> - <?php echo htmlspecialchars($ticket['title']); ?>
                            <span style="background: #6c757d; color: white; padding: 0.25rem 0.6rem; border-radius: 6px; font-size: 0.75rem; font-weight: 600;">ARCHIVED</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 1rem; font-size: 0.9rem; color: #64748b; flex-wrap: wrap;">
                            <span><strong><?php echo htmlspecialchars($ticket['full_name']); ?></strong></span>
                            <span>•</span>
                            <span style="display: inline-block; padding: 0.3rem 0.8rem; background: #f0f4ff; color: #667eea; border-radius: 20px; font-size: 0.8rem; font-weight: 600;"><?php echo htmlspecialchars($ticket['category_name'] ?? 'General'); ?></span>
                            <span>•</span>
                            <span style="display: inline-block; padding: 0.3rem 0.8rem; background: #fee2e2; color: #991b1b; border-radius: 20px; font-size: 0.8rem; font-weight: 600;"><?php echo htmlspecialchars($ticket['priority']); ?></span>
                            <span>•</span>
                            <span style="color: #94a3b8; font-size: 0.85rem;">Archived: <?php echo date('M d, Y g:ia', strtotime($ticket['archived_at'])); ?></span>
                        </div>
                    </div>
                    <div style="display: flex; gap: 0.75rem; margin-left: 1rem;" onclick="event.stopPropagation();">
                        <button class="restore-btn" onclick="restoreTicket(<?php echo $ticket['id']; ?>)">
                            <i class="bi bi-arrow-counterclockwise"></i> Restore
                        </button>
                        <button class="delete-btn" onclick="deleteArchiveTicket(<?php echo $ticket['id']; ?>)">
                            <i class="bi bi-trash"></i> Delete
                        </button>
                        <button class="ticket-manage-btn" onclick="window.location.href='hr_ticket_detail.php?id=<?php echo $ticket['id']; ?>&archived=1'" style="border-color: #667eea; color: #667eea;">
                            <i class="bi bi-eye"></i> View
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

    <!-- TRENDS TAB -->
    <div id="trends" class="tab-content">
        <div class="analytics-grid">
            <div class="analytics-card">
                <div class="analytics-value">+15%</div>
                <div class="analytics-label">This month vs last</div>
            </div>
            <div class="analytics-card">
                <div class="analytics-value"><?php echo $resolution_rate; ?>%</div>
                <div class="analytics-label">Resolution rate</div>
            </div>
            <div class="analytics-card">
                <div class="analytics-value">4.2/5</div>
                <div class="analytics-label">Employee satisfaction</div>
            </div>
        </div>

        <div class="insights-box">
            <div class="insights-title">Common Issues & Recommendations</div>

            <?php 
            if (!empty($category_stats)) {
                $recommendations = [
                    'Frequent requests for replacement cards. Consider improving card durability.',
                    'Increasing demand for design software. Consider bulk licensing.',
                    'Streamlined process has reduced processing time by 40%.'
                ];
                
                foreach (array_slice($category_stats, 0, 3) as $index => $stat):
                ?>
                    <div class="insight-item">
                        <div class="insight-title"><?php echo htmlspecialchars($stat['category_name'] ?? 'General'); ?> Issues</div>
                        <div class="insight-text"><?php echo $recommendations[$index] ?? 'Monitor for trends and patterns.'; ?></div>
                    </div>
                <?php 
                endforeach;
            }
            ?>
        </div>
    </div>
</div>

<script>
    function switchTab(e, tabName) {
        e.preventDefault();
        const tabs = document.querySelectorAll('.tab-content');
        tabs.forEach(tab => tab.classList.remove('active'));
        const buttons = document.querySelectorAll('.tab-btn');
        buttons.forEach(btn => btn.classList.remove('active'));
        document.getElementById(tabName).classList.add('active');
        e.target.closest('.tab-btn').classList.add('active');
    }

    function archiveTicket(ticketId) {
        if (confirm('Are you sure you want to archive this ticket? You can restore it later from the Archives.')) {
            const formData = new FormData();
            formData.append('id', ticketId);
            formData.append('action', 'archive');
            
            fetch('archive_ticket.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Ticket archived successfully', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showNotification('Error: ' + (data.message || 'Failed to archive ticket'), 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Connection error. Please try again.', 'error');
            });
        }
    }

    function restoreTicket(ticketId) {
        if (confirm('Are you sure you want to restore this ticket?')) {
            const formData = new FormData();
            formData.append('id', ticketId);
            formData.append('action', 'restore');
            
            fetch('archive_ticket.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Ticket restored successfully', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showNotification('Error: ' + (data.message || 'Failed to restore ticket'), 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Connection error. Please try again.', 'error');
            });
        }
    }

    function deleteArchiveTicket(ticketId) {
        if (confirm('This will permanently delete the ticket. This action cannot be undone. Continue?')) {
            const formData = new FormData();
            formData.append('id', ticketId);
            formData.append('action', 'delete');
            
            fetch('archive_ticket.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Ticket permanently deleted', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showNotification('Error: ' + (data.message || 'Failed to delete ticket'), 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Connection error. Please try again.', 'error');
            });
        }
    }

    function showNotification(message, type = 'success') {
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-' + type;
        alertDiv.innerHTML = '<i class="bi bi-' + (type === 'success' ? 'check-circle-fill' : 'exclamation-circle-fill') + '"></i> ' + message;
        alertDiv.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px; animation: slideDown 0.3s ease; box-shadow: 0 4px 12px rgba(0,0,0,0.1); padding: 1rem 1.5rem; border-radius: 12px; background: ' + (type === 'success' ? '#dcfce7' : '#fee2e2') + '; color: ' + (type === 'success' ? '#15803d' : '#991b1b') + '; border: 1px solid ' + (type === 'success' ? '#86efac' : '#fecaca') + ';';
        
        document.body.appendChild(alertDiv);
        
        setTimeout(() => {
            alertDiv.style.transition = 'all 0.3s ease';
            alertDiv.style.opacity = '0';
            setTimeout(() => alertDiv.remove(), 300);
        }, 4000);
    }

    document.addEventListener('DOMContentLoaded', function() {
        console.log('Tickets management page loaded');
    });
</script>

</body>
</html>
