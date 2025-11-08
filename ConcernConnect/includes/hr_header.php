<?php
// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Get notifications from database
require_once 'config/database.php';
$db = new Database();
$conn = $db->connect();

// Get current logged-in user's employee_id
$current_employee_id = $_SESSION['employee_id'] ?? null;

// Initialize variables
$notifications = [];
$unread_count = 0;
$employee = [];

if ($current_employee_id) {
    // Check if current user has role = 'hr_admin'
    $stmt = $conn->prepare("SELECT role FROM employees WHERE employee_id = ?");
    $stmt->execute([$current_employee_id]);
    $user_role = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Only show notifications if user role is 'hr_admin'
    if ($user_role && $user_role['role'] === 'hr_admin') {
        try {
            // Fetch notifications for this user
            $stmt = $conn->prepare("
                SELECT 
                    n.id, 
                    n.hr_id, 
                    n.ticket_id, 
                    n.notification_type, 
                    n.message, 
                    n.is_read, 
                    n.created_at,
                    t.ticket_number, 
                    t.title as ticket_title,
                    t.status as ticket_status,
                    t.priority,
                    e.full_name as employee_name
                FROM hr_notifications n
                LEFT JOIN tickets t ON n.ticket_id = t.id
                LEFT JOIN employees e ON t.employee_id = e.employee_id
                WHERE n.hr_id = ?
                ORDER BY n.created_at DESC
                LIMIT 15
            ");
            $stmt->execute([$current_employee_id]);
            $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Count unread notifications
            $stmt = $conn->prepare("
                SELECT COUNT(*) as unread 
                FROM hr_notifications 
                WHERE hr_id = ? AND is_read = 0
            ");
            $stmt->execute([$current_employee_id]);
            $unread_count = $stmt->fetch(PDO::FETCH_ASSOC)['unread'] ?? 0;
            
        } catch (Exception $e) {
            error_log("HR Notification Error: " . $e->getMessage());
        }
    }
}

// Get employee data
if ($current_employee_id) {
    $stmt = $conn->prepare("SELECT full_name, photo FROM employees WHERE employee_id = ?");
    $stmt->execute([$current_employee_id]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get employee photo path
$photoPath = (!empty($employee['photo']) && file_exists($employee['photo'])) 
    ? $employee['photo'] 
    : 'assets/images/default.png';

// Detect current page
$currentPage = basename($_SERVER['PHP_SELF'], ".php");

// Map page titles
$pageNames = [
    'hr_dashboard'      => 'HR Dashboard',
    'hr_employees'      => 'Employee',
    'hr_ticket_detail'  => 'Tickets Management',
    'hr_notifications'  => 'Notifications',
    'hr_settings'       => 'Settings'
];

$pageTitle = $pageNames[$currentPage] ?? ucfirst(str_replace('_', ' ', $currentPage));
?>

<!-- Keep all the CSS and HTML from before... -->


<style>
.hr-header {
    background: #fff;
    border: 1px solid #d0d0d0;
    padding: 1rem 1.5rem;
    border-radius: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 2px 6px rgba(0,0,0,0.05);
    margin-bottom: 2rem;
    position: relative;
    z-index: 100;
}

.hr-header h4 {
    margin: 0;
    font-weight: 600;
    color: #333;
    font-size: 1.5rem;
}

.hr-header h4 strong {
    color: #667eea;
}

.header-actions {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    position: relative;
    z-index: 101;
}

.notification-wrapper {
    position: relative;
    margin-right: 0.5rem;
}

.btn-notification {
    background: transparent;
    border: none;
    color: #666;
    font-size: 1.35rem;
    padding: 0.5rem;
    cursor: pointer;
    border-radius: 8px;
    transition: all 0.3s ease;
    position: relative;
}

.btn-notification:hover {
    background: #f0f0f0;
    color: #667eea;
}

.notification-badge {
    position: absolute;
    top: 2px;
    right: 2px;
    background: #ef4444;
    color: white;
    font-size: 0.7rem;
    font-weight: 700;
    padding: 2px 6px;
    border-radius: 10px;
    min-width: 18px;
    text-align: center;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
}

.notification-dropdown {
    position: absolute;
    top: calc(100% + 10px);
    right: 0;
    background: white;
    border-radius: 12px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.15);
    width: 380px;
    max-height: 500px;
    overflow: hidden;
    display: none;
    z-index: 9999;
    animation: slideDown 0.3s ease;
}

@keyframes slideDown {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.notification-dropdown.show {
    display: block;
}

.notification-header {
    padding: 1rem 1.25rem;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.notification-header h5 {
    margin: 0;
    font-size: 1rem;
    font-weight: 700;
    color: #1e293b;
}

.mark-all-read {
    font-size: 0.85rem;
    color: #667eea;
    cursor: pointer;
    text-decoration: none;
    transition: color 0.3s ease;
}

.mark-all-read:hover {
    color: #5f4ef7;
    text-decoration: underline;
}

.notification-list {
    max-height: 400px;
    overflow-y: auto;
}

.notification-item {
    padding: 1rem 1.25rem;
    border-bottom: 1px solid #f1f5f9;
    cursor: pointer;
    transition: background 0.2s ease;
}

.notification-item:hover {
    background: #f8fafc;
}

.notification-item.unread {
    background: #eff6ff;
    border-left: 4px solid #667eea;
}

.notification-content {
    display: flex;
    gap: 1rem;
}

.notification-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea, #764ba2);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.1rem;
    flex-shrink: 0;
}

.notification-icon.new-ticket {
    background: linear-gradient(135deg, #10b981, #059669);
}

.notification-icon.urgent-ticket {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    animation: shake 0.5s infinite;
}

@keyframes shake {
    0%, 100% { transform: rotate(0deg); }
    25% { transform: rotate(-5deg); }
    75% { transform: rotate(5deg); }
}

.notification-text {
    flex: 1;
}

.notification-title {
    font-weight: 600;
    color: #1e293b;
    font-size: 0.9rem;
    margin-bottom: 0.25rem;
}

.notification-message {
    color: #64748b;
    font-size: 0.85rem;
    margin-bottom: 0.25rem;
    line-height: 1.4;
}

.notification-time {
    color: #94a3b8;
    font-size: 0.75rem;
}

.notification-footer {
    padding: 0.75rem 1.25rem;
    text-align: center;
    border-top: 1px solid #e2e8f0;
}

.view-all-link {
    color: #667eea;
    font-size: 0.9rem;
    font-weight: 600;
    text-decoration: none;
}

.view-all-link:hover {
    color: #5f4ef7;
}

.no-notifications {
    padding: 3rem 1.5rem;
    text-align: center;
    color: #94a3b8;
}

.no-notifications i {
    font-size: 3rem;
    opacity: 0.3;
    display: block;
    margin-bottom: 1rem;
}

.notification-modal-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(5px);
    z-index: 10000;
    align-items: center;
    justify-content: center;
}

.notification-modal-overlay.show {
    display: flex;
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.notification-modal {
    background: white;
    border-radius: 20px;
    max-width: 600px;
    width: 90%;
    max-height: 80vh;
    overflow: hidden;
    box-shadow: 0 25px 70px rgba(0, 0, 0, 0.3);
    animation: modalSlideUp 0.3s ease;
}

@keyframes modalSlideUp {
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
}

.modal-header-section {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    padding: 2rem;
    display: flex;
    justify-content: space-between;
    align-items: start;
}

.modal-header-section.urgent {
    background: linear-gradient(135deg, #ef4444, #dc2626);
}

.modal-header-content { flex: 1; }

.modal-header-section h3 {
    margin: 0 0 0.5rem 0;
    font-size: 1.5rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.modal-ticket-number {
    font-size: 0.9rem;
    opacity: 0.9;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.modal-close-btn {
    background: rgba(255, 255, 255, 0.2);
    border: none;
    color: white;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    transition: all 0.3s ease;
}

.modal-close-btn:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: rotate(90deg);
}

.modal-body-section {
    padding: 2rem;
    max-height: 60vh;
    overflow-y: auto;
}

.modal-info-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
    margin-bottom: 1.5rem;
    padding: 1.25rem;
    background: #f8fafc;
    border-radius: 12px;
}

.modal-info-item {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.modal-info-label {
    font-size: 0.8rem;
    color: #64748b;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.modal-info-value {
    font-size: 1rem;
    color: #1e293b;
    font-weight: 600;
}

.modal-message-section { margin-bottom: 1.5rem; }

.modal-section-title {
    font-size: 1rem;
    font-weight: 700;
    margin-bottom: 1rem;
    color: #1e293b;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.modal-message-box {
    padding: 1.5rem;
    background: linear-gradient(135deg, #e0e7ff, #f0f9ff);
    border-left: 4px solid #667eea;
    border-radius: 12px;
    line-height: 1.7;
    color: #334155;
    font-size: 0.95rem;
}

.modal-footer-section {
    padding: 1.5rem 2rem;
    background: #f8fafc;
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
    border-top: 1px solid #e2e8f0;
}

.modal-btn {
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
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
}

.modal-btn-secondary {
    background: white;
    color: #64748b;
    border: 1px solid #e2e8f0;
}

.modal-btn-secondary:hover { background: #f8fafc; }

.hr-dropdown { position: relative; }

.hr-dropdown .btn-dropdown {
    color: #333;
    font-weight: 500;
    border: none;
    background: transparent;
    padding: 6px 12px;
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    border-radius: 10px;
    transition: all 0.3s ease;
}

.hr-dropdown .btn-dropdown:hover { background: #f8f9fa; }

.hr-dropdown .btn-dropdown img {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    border: 2px solid #e2e8f0;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    object-fit: cover;
}

.dropdown-arrow {
    font-size: 0.75rem;
    margin-left: 5px;
    transition: transform 0.3s ease;
}

.hr-dropdown.active .dropdown-arrow { transform: rotate(180deg); }

.hr-dropdown-menu {
    position: absolute;
    top: calc(100% + 10px);
    right: 0;
    background: white;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 4px 16px rgba(0,0,0,0.1);
    min-width: 200px;
    padding: 0.5rem 0;
    display: none;
    z-index: 9999;
}

.hr-dropdown-menu.show {
    display: block;
    animation: dropdownFadeIn 0.2s ease;
}

@keyframes dropdownFadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.dropdown-item {
    display: block;
    padding: 10px 15px;
    color: #333;
    text-decoration: none;
    font-size: 0.95rem;
    transition: all 0.2s ease;
}

.dropdown-item:hover {
    background-color: #f2f2ff;
    color: #667eea;
}

.dropdown-divider {
    height: 1px;
    background: #e2e8f0;
    margin: 0.5rem 0;
}

.dropdown-item.text-danger { color: #dc3545; }
.dropdown-item.text-danger:hover { background-color: #ffeaea; }

.dropdown-item i {
    margin-right: 8px;
    width: 18px;
}

.btn-mobile-toggle {
    display: none;
    background: transparent;
    border: none;
    font-size: 1.5rem;
    color: #666;
    cursor: pointer;
}

@media (max-width: 1200px) {
    .btn-mobile-toggle { display: block; }
}

@media (max-width: 768px) {
    .hr-header { flex-direction: column; gap: 1rem; }
    .notification-dropdown { width: 320px; }
}
</style>

<div class="hr-header">
    <h4>
        <?php if ($currentPage === 'hr_dashboard'): ?>
            <?php echo $pageTitle; ?> |
            Hello <strong><?php echo htmlspecialchars($employee['full_name'] ?? 'HR Admin'); ?></strong>,
            <span id="greeting">Good Day!</span>
        <?php else: ?>
            <?php echo $pageTitle; ?>
        <?php endif; ?>
    </h4>

    <div class="header-actions">
        <button class="btn-mobile-toggle" onclick="toggleSidebar()">
            <i class="bi bi-list"></i>
        </button>

        <div class="notification-wrapper">
            <button class="btn-notification" onclick="toggleNotifications()">
                <i class="bi bi-bell"></i>
                <?php if ($unread_count > 0): ?>
                    <span class="notification-badge"><?php echo $unread_count; ?></span>
                <?php endif; ?>
            </button>

            <div class="notification-dropdown" id="notificationDropdown">
                <div class="notification-header">
                    <h5><i class="bi bi-bell"></i> Notifications</h5>
                    <?php if ($unread_count > 0): ?>
                        <a href="hr_mark_notifications_read.php" class="mark-all-read">Mark all as read</a>
                    <?php endif; ?>
                </div>

                <div class="notification-list">
                    <?php if (empty($notifications)): ?>
                        <div class="no-notifications">
                            <i class="bi bi-inbox"></i>
                            <p>No notifications yet</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($notifications as $notif): 
                            $time = strtotime($notif['created_at']);
                            $diff = time() - $time;
                            if ($diff < 60) {
                                $timeAgo = 'Just now';
                            } elseif ($diff < 3600) {
                                $timeAgo = floor($diff / 60) . ' min ago';
                            } elseif ($diff < 86400) {
                                $timeAgo = floor($diff / 3600) . ' hours ago';
                            } else {
                                $timeAgo = date('M d, g:ia', $time);
                            }
                            
                            $isUrgent = ($notif['notification_type'] == 'urgent_ticket');
                        ?>
                            <div class="notification-item <?php echo $notif['is_read'] == 0 ? 'unread' : ''; ?>"
                                 data-ticket-id="<?php echo $notif['ticket_id']; ?>"
                                 data-ticket-number="<?php echo htmlspecialchars($notif['ticket_number'] ?? 'N/A'); ?>"
                                 data-employee-name="<?php echo htmlspecialchars($notif['employee_name'] ?? 'Unknown'); ?>"
                                 data-time="<?php echo $timeAgo; ?>"
                                 data-message="<?php echo htmlspecialchars($notif['message']); ?>"
                                 data-notif-id="<?php echo $notif['id']; ?>"
                                 data-is-urgent="<?php echo $isUrgent ? '1' : '0'; ?>"
                                 onclick="showNotificationModal(this)">
                                <div class="notification-content">
                                    <div class="notification-icon <?php echo $isUrgent ? 'urgent-ticket' : 'new-ticket'; ?>">
                                        <i class="bi bi-<?php echo $isUrgent ? 'exclamation-triangle-fill' : 'file-earmark-plus'; ?>"></i>
                                    </div>
                                    <div class="notification-text">
                                        <div class="notification-title">
                                            <?php echo $isUrgent ? '🚨 Urgent Ticket' : '📋 New Ticket'; ?>
                                        </div>
                                        <div class="notification-message">
                                            <?php echo htmlspecialchars($notif['message']); ?>
                                        </div>
                                        <div class="notification-time">
                                            <i class="bi bi-clock"></i> <?php echo $timeAgo; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <?php if (!empty($notifications)): ?>
                    <div class="notification-footer">
                        <a href="hr_notifications.php" class="view-all-link">View All Notifications</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="hr-dropdown" id="hrDropdown">
            <button class="btn-dropdown" onclick="toggleDropdown(event)">
                <img src="<?php echo htmlspecialchars($photoPath); ?>" alt="HR">
                <span><?php echo htmlspecialchars($employee['full_name'] ?? 'HR Admin'); ?></span>
                <i class="bi bi-chevron-down dropdown-arrow"></i>
            </button>
            
            <div class="hr-dropdown-menu" id="dropdownMenu">
                <a class="dropdown-item" href="hr_settings.php">
                    <i class="bi bi-person"></i> Profile
                </a>
                <a class="dropdown-item" href="hr_settings.php">
                    <i class="bi bi-gear"></i> Settings
                </a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item text-danger" href="logout.php">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </div>
        </div>
    </div>
</div>

<div class="notification-modal-overlay" id="notificationModal" onclick="closeNotificationModal(event)">
    <div class="notification-modal" onclick="event.stopPropagation()">
        <div class="modal-header-section" id="modalHeader">
    <div class="modal-header-content">
        <h3 id="modalTitle">New Ticket Submitted</h3>
        <div class="modal-ticket-number">
            <i class="bi bi-ticket-perforated"></i>
            <span id="modalTicketNumber"></span>
        </div>
    </div>
    <button class="modal-close-btn" onclick="closeNotificationModal()">
        <i class="bi bi-x"></i>
    </button>
</div>


        <div class="modal-body-section">
            <div class="modal-info-grid">
                <div class="modal-info-item">
                    <span class="modal-info-label">Employee</span>
                    <span class="modal-info-value" id="modalEmployee"></span>
                </div>
                <div class="modal-info-item">
                    <span class="modal-info-label">Time</span>
                    <span class="modal-info-value" id="modalTime"></span>
                </div>
            </div>

            <div class="modal-message-section">
                <div class="modal-section-title">
                    <i class="bi bi-envelope-open"></i>
                    Notification Message
                </div>
                <div class="modal-message-box" id="modalMessage"></div>
            </div>
        </div>

        <div class="modal-footer-section">
            <button class="modal-btn modal-btn-secondary" onclick="closeNotificationModal()">
                <i class="bi bi-x-circle"></i>
                Close
            </button>
            <button class="modal-btn modal-btn-primary" onclick="viewTicketDetails()">
                <i class="bi bi-eye"></i>
                View Full Ticket
            </button>
        </div>
    </div>
</div>

<script>
let currentTicketId = null;

function showNotificationModal(element) {
    const data = element.dataset;
    currentTicketId = data.ticketId;
    const isUrgent = data.isUrgent === '1';
    
    // Update modal header style for urgent tickets
    const modalHeader = document.getElementById('modalHeader');
    if (isUrgent) {
        modalHeader.classList.add('urgent');
        document.getElementById('modalTitle').textContent = '🚨 Urgent Ticket Submitted';
    } else {
        modalHeader.classList.remove('urgent');
        document.getElementById('modalTitle').textContent = '📋 New Ticket Submitted';
    }
    
    document.getElementById('modalTicketNumber').textContent = data.ticketNumber;
    document.getElementById('modalEmployee').textContent = data.employeeName;
    document.getElementById('modalTime').textContent = data.time;
    document.getElementById('modalMessage').textContent = data.message;
    
    document.getElementById('notificationModal').classList.add('show');
    document.getElementById('notificationDropdown').classList.remove('show');
    document.body.style.overflow = 'hidden';
    
    markAsRead(data.notifId);
}

function closeNotificationModal(event) {
    if (event && event.target !== document.getElementById('notificationModal')) {
        return;
    }
    document.getElementById('notificationModal').classList.remove('show');
    document.body.style.overflow = 'auto';
}

function viewTicketDetails() {
    if (currentTicketId) {
        window.location.href = 'hr_ticket_detail.php?id=' + currentTicketId;
    }
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeNotificationModal();
    }
});

function toggleNotifications() {
    const dropdown = document.getElementById('notificationDropdown');
    dropdown.classList.toggle('show');
    
    const userMenu = document.getElementById('dropdownMenu');
    if (userMenu && userMenu.classList.contains('show')) {
        userMenu.classList.remove('show');
        document.getElementById('hrDropdown').classList.remove('active');
    }
}

document.addEventListener('click', (e) => {
    const wrapper = document.querySelector('.notification-wrapper');
    const dropdown = document.getElementById('notificationDropdown');
    
    if (wrapper && !wrapper.contains(e.target)) {
        dropdown.classList.remove('show');
    }
});

function markAsRead(notificationId) {
    fetch('hr_mark_notification_read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'notification_id=' + notificationId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const badge = document.querySelector('.notification-badge');
            if (badge) {
                const currentCount = parseInt(badge.textContent);
                if (currentCount > 1) {
                    badge.textContent = currentCount - 1;
                } else {
                    badge.remove();
                }
            }
            
            // Remove unread class from notification item
            const items = document.querySelectorAll('.notification-item');
            items.forEach(item => {
                if (item.dataset.notifId == notificationId) {
                    item.classList.remove('unread');
                }
            });
        }
    })
    .catch(error => console.error('Error marking notification as read:', error));
}

function toggleDropdown(e) {
    e.stopPropagation();
    const dropdown = document.getElementById('hrDropdown');
    const menu = document.getElementById('dropdownMenu');
    
    dropdown.classList.toggle('active');
    menu.classList.toggle('show');
    
    const notificationDropdown = document.getElementById('notificationDropdown');
    if (notificationDropdown && notificationDropdown.classList.contains('show')) {
        notificationDropdown.classList.remove('show');
    }
}

document.addEventListener('click', (e) => {
    const dropdown = document.getElementById('hrDropdown');
    const menu = document.getElementById('dropdownMenu');
    
    if (!dropdown.contains(e.target)) {
        dropdown.classList.remove('active');
        menu.classList.remove('show');
    }
});

function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    if (sidebar) {
        sidebar.classList.toggle('active');
    }
}

<?php if ($currentPage === 'hr_dashboard'): ?>
function updateGreeting() {
    const now = new Date();
    const hour = now.getHours();
    let greetingText = "Good Day";

    if (hour >= 5 && hour < 12) greetingText = "Good Morning";
    else if (hour >= 12 && hour < 18) greetingText = "Good Afternoon";
    else greetingText = "Good Evening";

    const greetingEl = document.getElementById('greeting');
    if (greetingEl) {
        greetingEl.textContent = greetingText + "!";
    }
}
updateGreeting();
<?php endif; ?>
</script>
