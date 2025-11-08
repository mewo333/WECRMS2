<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

$db = new Database();
$conn = $db->connect();

$employee = getEmployeeData($conn, $_SESSION['employee_id']);

if (!$employee || $employee['role'] !== 'hr_admin') {
    header('Location: dashboard.php');
    exit();
}

// Get employee photo path
$photoPath = (!empty($employee['photo']) && file_exists($employee['photo'])) 
    ? $employee['photo'] 
    : 'assets/images/default.png';

// Helper function to safely escape and display text with special characters
function displayText($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

// Function to get emoji based on priority
function getPriorityEmoji($priority) {
    $emojis = [
        'high' => '🔴',
        'medium' => '🟠',
        'low' => '🟢'
    ];
    return $emojis[$priority] ?? '◯';
}

// Function to format announcement meta
function formatAnnouncementMeta($announcement) {
    $html = '';
    
    if ($announcement['send_email']) {
        $html .= '<span style="color: #667eea;"><i class="bi bi-envelope"></i> Email</span>';
    } else {
        $html .= '<span style="color: #667eea;"><i class="bi bi-chat"></i> 💬 SMS</span>';
    }
    
    return $html;
}

// Function to get photo path for employees
function getEmployeePhotoPath($photo) {
    if (!empty($photo) && file_exists($photo)) {
        return $photo;
    }
    return 'assets/images/default.png';
}

// Handle Add Comment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_comment'])) {
    $announcement_id = $_POST['announcement_id'];
    $comment = $_POST['comment'];
    $employee_id = $_SESSION['employee_id'];
    
    try {
        $stmt = $conn->prepare("INSERT INTO announcement_comments (announcement_id, employee_id, comment, created_at) VALUES (:announcement_id, :employee_id, :comment, NOW())");
        $stmt->execute([
            ':announcement_id' => $announcement_id,
            ':employee_id' => $employee_id,
            ':comment' => $comment
        ]);
        
        // Return JSON for AJAX
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit();
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit();
    }
}

// Handle Delete Announcement
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $stmt = $conn->prepare("DELETE FROM announcements WHERE id = :id");
    $stmt->execute(['id' => $delete_id]);
    header('Location: announcements.php?success=deleted');
    exit();
}

// Handle Edit/Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_announcement'])) {
    $id = $_POST['announcement_id'];
    $title = $_POST['title'];
    $message = $_POST['message'];
    $priority = $_POST['priority'];
    $expires_at = !empty($_POST['expires_at']) ? $_POST['expires_at'] : NULL;
    
    $stmt = $conn->prepare("UPDATE announcements SET title = :title, message = :message, priority = :priority, expires_at = :expires_at WHERE id = :id");
    $stmt->execute([
        'title' => $title,
        'message' => $message,
        'priority' => $priority,
        'expires_at' => $expires_at,
        'id' => $id
    ]);
    
    header('Location: announcements.php?success=updated');
    exit();
}

// Search and Filter
$search = $_GET['search'] ?? '';
$filter = $_GET['filter'] ?? 'all';

$sql = "SELECT * FROM announcements WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (title LIKE :search OR message LIKE :search)";
    $params['search'] = '%' . $search . '%';
}

if ($filter === 'active') {
    $sql .= " AND (expires_at IS NULL OR expires_at > NOW())";
} elseif ($filter === 'expired') {
    $sql .= " AND expires_at IS NOT NULL AND expires_at <= NOW()";
} elseif ($filter === 'high') {
    $sql .= " AND priority = 'high'";
} elseif ($filter === 'medium') {
    $sql .= " AND priority = 'medium'";
} elseif ($filter === 'low') {
    $sql .= " AND priority = 'low'";
}

$sql .= " ORDER BY created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcements - HR Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
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

        .page-header {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .page-header h1 {
            font-size: 1.75rem;
            font-weight: 700;
            color: #1e293b;
        }

        .page-header .icon-badge {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
        }

        /* Alert Messages */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .alert-success {
            background: #dcfce7;
            color: #15803d;
            border-left: 4px solid #15803d;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #991b1b;
        }

        /* Filter and Search Bar */
        .filter-bar {
            background: white;
            padding: 1.5rem;
            border-radius: 16px;
            margin-bottom: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .search-box {
            flex: 1;
            min-width: 300px;
            position: relative;
        }

        .search-box input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 3rem;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            font-size: 0.95rem;
            background: #f8fafc;
            transition: all 0.3s ease;
        }

        .search-box input:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .search-box i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            font-size: 1.1rem;
        }

        .filter-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 0.6rem 1.25rem;
            border: 2px solid #e2e8f0;
            background: white;
            border-radius: 10px;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            color: #64748b;
            text-decoration: none;
            display: inline-block;
        }

        .filter-btn:hover {
            border-color: #667eea;
            color: #667eea;
            transform: translateY(-2px);
        }

        .filter-btn.active {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-color: #667eea;
        }

        /* Announcements Grid */
        .announcements-grid {
            display: grid;
            gap: 1.5rem;
        }

        .announcement-card {
            background: white;
            border-radius: 16px;
            padding: 1.75rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            border-left: 5px solid #667eea;
            position: relative;
        }

        .announcement-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.12);
        }

        .announcement-card.priority-high {
            border-left-color: #ef4444;
        }

        .announcement-card.priority-medium {
            border-left-color: #f59e0b;
        }

        .announcement-card.priority-low {
            border-left-color: #10b981;
        }

        .announcement-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .announcement-title {
            font-size: 1.35rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .priority-badge {
            padding: 0.35rem 0.85rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .priority-high {
            background: #fee2e2;
            color: #991b1b;
        }

        .priority-medium {
            background: #fed7aa;
            color: #c2410c;
        }

        .priority-low {
            background: #dcfce7;
            color: #15803d;
        }

        .announcement-message {
            color: #475569;
            line-height: 1.7;
            margin-bottom: 1.25rem;
            font-size: 0.95rem;
        }

        .announcement-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 1.5rem;
            font-size: 0.85rem;
            color: #64748b;
            padding-top: 1rem;
            border-top: 1px solid #f1f5f9;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .meta-item i {
            color: #667eea;
        }

        .expired-badge {
            background: #fee2e2;
            color: #991b1b;
            padding: 0.35rem 0.85rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .active-badge {
            background: #dcfce7;
            color: #15803d;
            padding: 0.35rem 0.85rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 0.75rem;
            margin-top: 1rem;
        }

        .btn {
            padding: 0.6rem 1.25rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }

        .btn-view {
            background: #dbeafe;
            color: #1e40af;
        }

        .btn-view:hover {
            background: #bfdbfe;
            transform: translateY(-2px);
        }

        .btn-edit {
            background: #e0e7ff;
            color: #4338ca;
        }

        .btn-edit:hover {
            background: #c7d2fe;
            transform: translateY(-2px);
        }

        .btn-delete {
            background: #fee2e2;
            color: #991b1b;
        }

        .btn-delete:hover {
            background: #fecaca;
            transform: translateY(-2px);
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .empty-state i {
            font-size: 4rem;
            color: #cbd5e1;
            margin-bottom: 1rem;
        }

        .empty-state h3 {
            color: #64748b;
            margin-bottom: 0.5rem;
        }

        .empty-state p {
            color: #94a3b8;
        }

        /* Modal for Edit */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .modal-header h2 {
            font-size: 1.5rem;
            color: #1e293b;
        }

        .close-modal {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #64748b;
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .close-modal:hover {
            background: #f1f5f9;
            color: #1e293b;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #334155;
            font-size: 0.9rem;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem;
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

        .btn-save {
            width: 100%;
            padding: 0.85rem;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(102, 126, 234, 0.3);
        }

        /* Comment Form Styles */
        .comment-form {
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 2px solid #e2e8f0;
        }

        .comment-input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            font-size: 0.9rem;
            resize: vertical;
            min-height: 80px;
            font-family: inherit;
        }

        .comment-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .btn-comment {
            margin-top: 0.75rem;
            padding: 0.6rem 1.5rem;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-comment:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        @media (max-width: 768px) {
            .filter-bar {
                flex-direction: column;
                align-items: stretch;
            }

            .search-box {
                min-width: 100%;
            }

            .filter-buttons {
                width: 100%;
                justify-content: center;
            }

            .announcement-title {
                font-size: 1.15rem;
            }
        }
    </style>
</head>
<body>

<?php include 'includes/hr_sidebar.php'; ?>

<div class="main-content">
    <?php include 'includes/hr_header.php'; ?>

    <!-- Page Header -->
    <div class="topbar">
        <div class="page-header">
            <div class="icon-badge">
                <i class="bi bi-megaphone-fill"></i>
            </div>
            <div>
                <h1>All Announcements</h1>
                <p style="color: #64748b; font-size: 0.9rem; margin-top: 0.25rem;">
                    Manage and view all company announcements
                </p>
            </div>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">
            <i class="bi bi-check-circle-fill"></i>
            <?php
            if ($_GET['success'] == 'deleted') echo 'Announcement deleted successfully!';
            if ($_GET['success'] == 'updated') echo 'Announcement updated successfully!';
            if ($_GET['success'] == 'commented') echo 'Comment added successfully!';
            ?>
        </div>
    <?php endif; ?>

    <!-- Filter and Search Bar -->
    <div class="filter-bar">
        <div class="search-box">
            <i class="bi bi-search"></i>
            <input type="text" id="searchInput" placeholder="Search announcements by title or message..." value="<?php echo htmlspecialchars($search); ?>">
        </div>
        <div class="filter-buttons">
            <a href="announcements.php?filter=all" class="filter-btn <?php echo $filter === 'all' ? 'active' : ''; ?>">
                <i class="bi bi-list"></i> All
            </a>
            <a href="announcements.php?filter=active" class="filter-btn <?php echo $filter === 'active' ? 'active' : ''; ?>">
                <i class="bi bi-check-circle"></i> Active
            </a>
            <a href="announcements.php?filter=expired" class="filter-btn <?php echo $filter === 'expired' ? 'active' : ''; ?>">
                <i class="bi bi-x-circle"></i> Expired
            </a>
            <a href="announcements.php?filter=high" class="filter-btn <?php echo $filter === 'high' ? 'active' : ''; ?>">
                High Priority
            </a>
            <a href="announcements.php?filter=medium" class="filter-btn <?php echo $filter === 'medium' ? 'active' : ''; ?>">
                Medium Priority
            </a>
            <a href="announcements.php?filter=low" class="filter-btn <?php echo $filter === 'low' ? 'active' : ''; ?>">
                Low Priority
            </a>
        </div>
    </div>

    <!-- Announcements Grid -->
    <div class="announcements-grid">
        <?php if (empty($announcements)): ?>
            <div class="empty-state">
                <i class="bi bi-inbox"></i>
                <h3>No Announcements Found</h3>
                <p>There are no announcements matching your criteria.</p>
            </div>
        <?php else: ?>
            <?php foreach ($announcements as $announcement): ?>
                <?php
                $isExpired = !empty($announcement['expires_at']) && strtotime($announcement['expires_at']) < time();
                ?>
                <div class="announcement-card priority-<?php echo $announcement['priority']; ?>">
                    <div class="announcement-header">
                        <div style="flex: 1;">
                            <div class="announcement-title">
                                <i class="bi bi-megaphone-fill"></i>
                                <?php echo htmlspecialchars($announcement['title']); ?>
                                <span class="priority-badge priority-<?php echo $announcement['priority']; ?>">
                                    <?php echo strtoupper($announcement['priority']); ?>
                                </span>
                            </div>
                        </div>
                        <?php if ($isExpired): ?>
                            <span class="expired-badge">
                                <i class="bi bi-clock-history"></i> Expired
                            </span>
                        <?php else: ?>
                            <span class="active-badge">
                                <i class="bi bi-check-circle-fill"></i> Active
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="announcement-message">
                        <?php echo nl2br(htmlspecialchars($announcement['message'])); ?>
                    </div>

                    <div class="announcement-meta">
                        <div class="meta-item">
                            <i class="bi bi-calendar"></i>
                            Posted: <?php echo date('M d, Y g:i A', strtotime($announcement['created_at'])); ?>
                        </div>
                        <?php if (!empty($announcement['expires_at'])): ?>
                            <div class="meta-item">
                                <i class="bi bi-hourglass-split"></i>
                                Expires: <?php echo date('M d, Y g:i A', strtotime($announcement['expires_at'])); ?>
                            </div>
                        <?php endif; ?>
                        <div class="meta-item">
                            <i class="bi bi-send"></i>
                            Sent via: <?php echo $announcement['send_email'] ? 'Email' : 'SMS'; ?>
                        </div>
                    </div>

                    <div class="action-buttons">
                        <button class="btn btn-view" onclick="viewDetails(<?php echo $announcement['id']; ?>)">
                            <i class="bi bi-eye"></i> View Details
                        </button>
                        <button class="btn btn-edit" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($announcement)); ?>)">
                            <i class="bi bi-pencil"></i> Edit
                        </button>
                        <a href="announcements.php?delete_id=<?php echo $announcement['id']; ?>" 
                           class="btn btn-delete" 
                           onclick="return confirm('Are you sure you want to delete this announcement?')">
                            <i class="bi bi-trash"></i> Delete
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal" id="editModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="bi bi-pencil-square"></i> Edit Announcement</h2>
            <button class="close-modal" onclick="closeEditModal()">
                <i class="bi bi-x"></i>
            </button>
        </div>
        <form method="POST" action="">
            <input type="hidden" name="announcement_id" id="edit_id">
            
            <div class="form-group">
                <label>Title</label>
                <input type="text" class="form-control" name="title" id="edit_title" required>
            </div>

            <div class="form-group">
                <label>Message</label>
                <textarea class="form-control" name="message" id="edit_message" required></textarea>
            </div>

            <div class="form-group">
                <label>Priority</label>
                <select class="form-control" name="priority" id="edit_priority" required>
                    <option value="low">Low</option>
                    <option value="medium">Medium</option>
                    <option value="high">High</option>
                </select>
            </div>

            <div class="form-group">
                <label>Expiry Date (Optional)</label>
                <input type="datetime-local" class="form-control" name="expires_at" id="edit_expires_at">
            </div>

            <button type="submit" name="edit_announcement" class="btn-save">
                <i class="bi bi-check-circle"></i> Save Changes
            </button>
        </form>
    </div>
</div>

<!-- View Details Modal -->
<div class="modal" id="viewModal">
    <div class="modal-content" style="max-width: 900px;">
        <div class="modal-header">
            <h2><i class="bi bi-info-circle"></i> Announcement Details</h2>
            <button class="close-modal" onclick="closeViewModal()">
                <i class="bi bi-x"></i>
            </button>
        </div>
        <div id="viewModalContent">
            <p style="text-align: center; color: #64748b;">Loading...</p>
        </div>
    </div>
</div>
<script>
// Search functionality
document.getElementById('searchInput').addEventListener('keyup', function(e) {
    if (e.key === 'Enter') {
        const search = this.value;
        const filter = new URLSearchParams(window.location.search).get('filter') || 'all';
        window.location.href = `announcements.php?filter=${filter}&search=${encodeURIComponent(search)}`;
    }
});

// Edit Modal Functions
function openEditModal(announcement) {
    document.getElementById('edit_id').value = announcement.id;
    document.getElementById('edit_title').value = announcement.title;
    document.getElementById('edit_message').value = announcement.message;
    document.getElementById('edit_priority').value = announcement.priority;
    
    if (announcement.expires_at) {
        const date = new Date(announcement.expires_at);
        const localDate = new Date(date.getTime() - date.getTimezoneOffset() * 60000).toISOString().slice(0, 16);
        document.getElementById('edit_expires_at').value = localDate;
    } else {
        document.getElementById('edit_expires_at').value = '';
    }
    
    document.getElementById('editModal').classList.add('active');
}

function closeEditModal() {
    document.getElementById('editModal').classList.remove('active');
}

// Helper Functions
function closeViewModal() {
    document.getElementById('viewModal').classList.remove('active');
    currentAnnouncementId = null;
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// View Details Modal Functions
let currentAnnouncementId = null;

function viewDetails(id) {
    currentAnnouncementId = id;
    document.getElementById('viewModal').classList.add('active');
    loadAnnouncementDetails(id);
}

function loadAnnouncementDetails(id) {
    document.getElementById('viewModalContent').innerHTML = '<p style="text-align: center; color: #64748b; padding: 2rem;">Loading...</p>';
    
    fetch(`get_announcement.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const ann = data.announcement;
                const comments = data.comments;
                
                let html = `
                    <!-- Announcement Header -->
                    <div style="background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 2rem; border-radius: 12px; margin-bottom: 2rem;">
                        <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem;">
                            <div>
                                <h2 style="margin: 0; margin-bottom: 0.5rem; font-size: 1.75rem;">${escapeHtml(ann.title)}</h2>
                                <span class="priority-badge" style="background: rgba(255,255,255,0.3); color: white; display: inline-block;">
                                    ${ann.priority.toUpperCase()} PRIORITY
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Announcement Content -->
                    <div style="background: white; padding: 1.5rem; border-radius: 12px; margin-bottom: 2rem; border-left: 4px solid #667eea;">
                        <p style="color: #475569; line-height: 1.8; white-space: pre-wrap; margin: 0; font-size: 1rem;">${escapeHtml(ann.message)}</p>
                    </div>
                    
                    <!-- Metadata -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 2rem;">
                        <div style="background: #f8fafc; padding: 1rem; border-radius: 10px; border-left: 3px solid #10b981;">
                            <p style="margin: 0; color: #64748b; font-size: 0.85rem; margin-bottom: 0.25rem;"><i class="bi bi-calendar"></i> Posted</p>
                            <p style="margin: 0; color: #1e293b; font-weight: 600;">${new Date(ann.created_at).toLocaleString()}</p>
                        </div>
                        <div style="background: #f8fafc; padding: 1rem; border-radius: 10px; border-left: 3px solid #f59e0b;">
                            <p style="margin: 0; color: #64748b; font-size: 0.85rem; margin-bottom: 0.25rem;"><i class="bi bi-send"></i> Sent via</p>
                            <p style="margin: 0; color: #1e293b; font-weight: 600;">${ann.send_email ? ' Email' : '💬 SMS'}</p>
                        </div>
                        ${ann.expires_at ? `
                        <div style="background: #f8fafc; padding: 1rem; border-radius: 10px; border-left: 3px solid #ef4444; grid-column: 1 / -1;">
                            <p style="margin: 0; color: #64748b; font-size: 0.85rem; margin-bottom: 0.25rem;"><i class="bi bi-hourglass-split"></i> Expires</p>
                            <p style="margin: 0; color: #1e293b; font-weight: 600;">${new Date(ann.expires_at).toLocaleString()}</p>
                        </div>
                        ` : ''}
                    </div>
                    
                    <!-- Comments Section -->
                    <div>
                        <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 2px solid #e2e8f0;">
                            <i class="bi bi-chat-dots-fill" style="color: #667eea; font-size: 1.5rem;"></i>
                            <h3 style="margin: 0; color: #1e293b; font-size: 1.25rem;">Comments <span style="background: #667eea; color: white; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.85rem; margin-left: 0.5rem;">${comments.length}</span></h3>
                        </div>
                        
                        <div id="commentsContainer" style="margin-bottom: 2rem;">
                `;
                
                if (comments.length > 0) {
                    comments.forEach(comment => {
                        // Proper photo path handling
                        let photoUrl = 'https://via.placeholder.com/40?text=User';
                        
                        if (comment.photo) {
                            // If photo path exists, check if it needs assets/images prefix
                            if (comment.photo.startsWith('assets/') || comment.photo.startsWith('/')) {
                                photoUrl = comment.photo;
                            } else {
                                photoUrl = 'assets/images/' + comment.photo;
                            }
                        }
                        
                        html += `
                            <div style="display: flex; gap: 1rem; margin-bottom: 1.25rem; padding: 1rem; background: #f8fafc; border-radius: 12px; border-left: 3px solid #667eea; transition: all 0.3s ease;" onmouseover="this.style.background='#f1f5f9'; this.style.transform='translateX(5px)'" onmouseout="this.style.background='#f8fafc'; this.style.transform='translateX(0)'">
                                <img src="${photoUrl}" onerror="this.src='https://via.placeholder.com/40?text=${escapeHtml(comment.full_name).charAt(0)}'" alt="${escapeHtml(comment.full_name)}" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; background: #e2e8f0; flex-shrink: 0; border: 2px solid #667eea; cursor: pointer;" title="${escapeHtml(comment.full_name)}">
                                <div style="flex: 1; min-width: 0;">
                                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.5rem; margin-bottom: 0.5rem;">
                                        <strong style="color: #1e293b; font-size: 0.95rem;">${escapeHtml(comment.full_name)}</strong>
                                        <span style="color: #94a3b8; font-size: 0.75rem; white-space: nowrap;"><i class="bi bi-clock"></i> ${new Date(comment.created_at).toLocaleString()}</span>
                                    </div>
                                    <p style="color: #475569; margin: 0; line-height: 1.6; word-wrap: break-word;">${escapeHtml(comment.comment)}</p>
                                </div>
                            </div>
                        `;
                    });
                } else {
                    html += '<div style="text-align: center; padding: 2rem; background: #f8fafc; border-radius: 12px; color: #94a3b8;"><i class="bi bi-chat-left" style="font-size: 2.5rem; margin-bottom: 0.5rem; display: block;"></i>No comments yet. Be the first to comment!</div>';
                }
                
                html += `
                        </div>
                    </div>
                    
                    <!-- Add Comment Form -->
                    <div style="background: linear-gradient(135deg, #f8fafc, #f1f5f9); padding: 1.5rem; border-radius: 12px; border: 1px solid #e2e8f0;">
                        <h4 style="color: #1e293b; margin: 0 0 1rem 0; display: flex; align-items: center; gap: 0.5rem;">
                            <i class="bi bi-pencil-square" style="color: #667eea;"></i> Add Your Comment
                        </h4>
                        <form onsubmit="submitComment(event, ${id})" style="display: flex; flex-direction: column; gap: 0.75rem;">
                            <textarea class="comment-input" id="commentText" placeholder="Share your thoughts... (max 500 characters)" maxlength="500" required style="border: 1px solid #e2e8f0; padding: 0.75rem; border-radius: 10px; font-size: 0.9rem; resize: vertical; min-height: 80px; font-family: inherit; transition: all 0.3s ease;" onmouseover="this.style.borderColor='#667eea'" onmouseout="this.style.borderColor='#e2e8f0'"></textarea>
                            <button type="submit" style="align-self: flex-start; padding: 0.65rem 1.5rem; background: linear-gradient(135deg, #667eea, #764ba2); color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 0.5rem;">
                                <i class="bi bi-send-fill"></i> Post Comment
                            </button>
                        </form>
                    </div>
                `;
                
                document.getElementById('viewModalContent').innerHTML = html;
            } else {
                document.getElementById('viewModalContent').innerHTML = '<p style="text-align: center; color: #ef4444; padding: 2rem;"><i class="bi bi-exclamation-circle" style="font-size: 2rem; display: block; margin-bottom: 0.5rem;"></i>Error loading announcement</p>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('viewModalContent').innerHTML = '<p style="text-align: center; color: #ef4444; padding: 2rem;"><i class="bi bi-exclamation-circle" style="font-size: 2rem; display: block; margin-bottom: 0.5rem;"></i>Error loading announcement</p>';
        });
}

function submitComment(event, announcementId) {
    event.preventDefault();
    const comment = document.getElementById('commentText').value;
    
    if (!comment.trim()) {
        alert('Please write a comment');
        return;
    }
    
    const formData = new FormData();
    formData.append('add_comment', '1');
    formData.append('announcement_id', announcementId);
    formData.append('comment', comment);
    
    fetch('announcements.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('commentText').value = '';
            setTimeout(() => {
                loadAnnouncementDetails(announcementId);
            }, 500);
        } else {
            alert('Error posting comment: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error posting comment. Please try again.');
    });
}

// Close modal when clicking outside
window.onclick = function(event) {
    const editModal = document.getElementById('editModal');
    const viewModal = document.getElementById('viewModal');
    
    if (event.target === editModal) {
        closeEditModal();
    }
    if (event.target === viewModal) {
        closeViewModal();
    }
}

// Auto-open view modal if redirected after commenting
window.onload = function() {
    const urlParams = new URLSearchParams(window.location.search);
    const viewId = urlParams.get('view');
    if (viewId) {
        viewDetails(viewId);
    }
}
</script>
</body>
</html>
