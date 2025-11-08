<?php
// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Get notifications from database
require_once 'config/database.php';
$db = new Database();
$conn = $db->connect();

// Make sure employee_id is set
$current_employee_id = $_SESSION['employee_id'] ?? null;

if ($current_employee_id) {
    // Fetch notifications with HR response
    try {
        $stmt = $conn->prepare("
            SELECT n.id, n.employee_id, n.ticket_id, n.notification_type, n.message, n.sent_via, n.is_read, n.created_at,
                   t.ticket_number, t.title as ticket_title,
                   (SELECT response_text FROM ticket_responses WHERE ticket_id = n.ticket_id ORDER BY created_at DESC LIMIT 1) as hr_response
            FROM notifications n
            LEFT JOIN tickets t ON n.ticket_id = t.id
            WHERE n.employee_id = :employee_id
            ORDER BY n.created_at DESC
            LIMIT 10
        ");
        $stmt->execute(['employee_id' => $current_employee_id]);
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Count unread
        $stmt = $conn->prepare("SELECT COUNT(*) as unread FROM notifications WHERE employee_id = :employee_id AND is_read = 0");
        $stmt->execute(['employee_id' => $current_employee_id]);
        $unread_count = $stmt->fetch(PDO::FETCH_ASSOC)['unread'] ?? 0;
    } catch (Exception $e) {
        $notifications = [];
        $unread_count = 0;
    }
} else {
    $notifications = [];
    $unread_count = 0;
}

// Get employee photo
$stmt = $conn->prepare("SELECT photo FROM employees WHERE employee_id = :employee_id");
$stmt->execute(['employee_id' => $current_employee_id]);
$employee_data = $stmt->fetch(PDO::FETCH_ASSOC);
$photoPath = (!empty($employee_data['photo']) && file_exists($employee_data['photo'])) 
    ? $employee_data['photo'] 
    : 'assets/images/default.png';

// Page detection
$currentPage = basename($_SERVER['PHP_SELF'], ".php");
$pageNames = [
    'dashboard'      => 'Dashboard',
    'profile'        => 'My Profile',
    'tickets'        => 'Tickets',
    'chatbot'        => 'Chat with Bot',
    'status_update'  => 'Status Update',
    'settings'       => 'Settings'
];
$pageTitle = $pageNames[$currentPage] ?? ucfirst($currentPage);
?>

<style>
.header {
    background: #fff;
    border: 1px solid #d0d0d0;
    padding: 1rem 1.5rem;
    border-radius: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 2px 6px rgba(0,0,0,0.05);
    position: relative;
    z-index: 100;
}

.header h4 {
    margin: 0;
    font-weight: 600;
    color: #333;
}

/* Notification Bell */
.notification-wrapper {
    position: relative;
    margin-right: 1rem;
}

.btn-notification {
    background: transparent;
    border: none;
    color: #666;
    font-size: 1.35rem;
    cursor: pointer;
    padding: 0.5rem;
    border-radius: 8px;
    transition: all 0.3s ease;
    position: relative;
}

.btn-notification:hover {
    background: #f0f0f0;
    color: #5f4ef7;
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

/* Notification Dropdown */
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
    text-decoration: none;
    display: block;
    color: inherit;
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
    transition: color 0.3s ease;
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

/* Notification Modal */
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

.modal-response-box {
    padding: 1.5rem;
    background: #f8fafc;
    border-radius: 12px;
    margin-top: 1rem;
}

.response-header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #e2e8f0;
}

.response-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea, #764ba2);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 700;
}

.response-meta h4 {
    margin: 0;
    font-size: 0.95rem;
    color: #1e293b;
}

.response-time {
    font-size: 0.8rem;
    color: #64748b;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.response-text {
    color: #475569;
    line-height: 1.7;
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

.modal-btn-secondary:hover {
    background: #f8fafc;
}

/* Header Actions */
.header-actions {
    position: relative;
    z-index: 101;
    display: flex;
    align-items: center;
    gap: 1rem;
}

/* Employee Dropdown */
.dropdown {
    position: relative;
}

.dropdown .btn {
    color: #333;
    font-weight: 500;
    border: none;
    background: transparent;
    padding: 8px 12px;
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    text-decoration: none;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.dropdown .btn:hover {
    background: #f0f0f0;
    color: #5f4ef7;
}

.dropdown .btn::after {
    content: '';
    width: 0;
    height: 0;
    border-left: 5px solid transparent;
    border-right: 5px solid transparent;
    border-top: 5px solid currentColor;
    transition: transform 0.3s ease;
    margin-left: 4px;
}

.dropdown .btn:hover::after {
    transform: scaleY(1.2);
}

.dropdown .btn.show::after {
    transform: rotate(180deg);
}

.dropdown .btn img {
    border-radius: 50%;
    border: 2px solid #e2e8f0;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}

.dropdown .btn:hover img {
    border-color: #667eea;
    box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
}

.dropdown-menu {
    position: absolute;
    top: calc(100% + 8px);
    right: 0;
    left: auto;
    margin-top: 0;
    border-radius: 12px;
    border: none;
    box-shadow: 0 4px 16px rgba(0,0,0,0.15);
    min-width: 220px;
    background: white;
    z-index: 9999;
    padding: 8px 0;
    display: none;
    animation: dropdownSlideIn 0.2s ease;
}

@keyframes dropdownSlideIn {
    from { opacity: 0; transform: translateY(-10px) scale(0.95); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}

.dropdown-menu.show {
    display: block !important;
}

.dropdown-menu li {
    list-style: none;
}

.dropdown-item {
    font-size: 15px;
    color: #333;
    padding: 10px 20px;
    transition: background 0.2s ease, color 0.2s ease;
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    text-decoration: none;
    background: transparent;
    border: none;
    width: 100%;
    text-align: left;
}

.dropdown-item:hover {
    background-color: #f2f2ff;
    color: #5f4ef7;
}

.dropdown-divider {
    margin: 0.5rem 0;
    border-color: #e2e8f0;
    height: 1px;
    border: none;
    border-top: 1px solid #e2e8f0;
}

.dropdown-item.text-danger {
    color: #dc3545;
}

.dropdown-item.text-danger:hover {
    background-color: #ffeaea;
    color: #dc3545;
}

@media (max-width: 768px) {
    .header {
        flex-direction: column;
        gap: 1rem;
    }
    
    .notification-dropdown {
        width: 320px;
    }

    .dropdown-menu {
        right: -20px;
    }
}
</style>

<div class="header mb-4 d-flex justify-content-between align-items-center">
    <h4 class="m-4">
        <?php if ($currentPage === 'dashboard'): ?>
            <?php echo $pageTitle; ?> |
            Hello <strong><?php echo $_SESSION['full_name']; ?></strong>,
            <span id="greeting">Good Day!</span>
        <?php else: ?>
            <?php echo $pageTitle; ?>
        <?php endif; ?>
    </h4>

    <div class="d-flex align-items-center">
        <button class="btn btn-link d-lg-none" id="sidebarToggle">
            <i class="bi bi-list"></i>
        </button>

        <div class="header-actions ms-auto">
            <!-- Notification Bell -->
            <div class="notification-wrapper">
                <button class="btn-notification" id="notificationBtn" onclick="toggleNotifications()">
                    <i class="bi bi-bell"></i>
                    <?php if ($unread_count > 0): ?>
                        <span class="notification-badge"><?php echo $unread_count; ?></span>
                    <?php endif; ?>
                </button>

                <div class="notification-dropdown" id="notificationDropdown">
                    <div class="notification-header">
                        <h5><i class="bi bi-bell"></i> Notifications</h5>
                        <?php if ($unread_count > 0): ?>
                            <a href="mark_notifications_read.php" class="mark-all-read">Mark all as read</a>
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
                                
                                $notifType = htmlspecialchars($notif['notification_type'], ENT_QUOTES);
                                $ticketNum = htmlspecialchars($notif['ticket_number'] ?? 'N/A', ENT_QUOTES);
                                $message = htmlspecialchars($notif['message'], ENT_QUOTES);
                                $response = htmlspecialchars($notif['hr_response'] ?? '', ENT_QUOTES);
                            ?>
                                <div class="notification-item <?php echo $notif['is_read'] == 0 ? 'unread' : ''; ?>"
                                     data-type="<?php echo $notifType; ?>"
                                     data-ticket-id="<?php echo $notif['ticket_id']; ?>"
                                     data-ticket-number="<?php echo $ticketNum; ?>"
                                     data-message="<?php echo $message; ?>"
                                     data-time="<?php echo $timeAgo; ?>"
                                     data-response="<?php echo $response; ?>"
                                     data-notif-id="<?php echo $notif['id']; ?>"
                                     onclick="showNotificationModal(this)">
                                    <div class="notification-content">
                                        <div class="notification-icon">
                                            <i class="bi bi-chat-dots"></i>
                                        </div>
                                        <div class="notification-text">
                                            <div class="notification-title">
                                                <?php echo $notif['notification_type'] == 'ticket_response' ? 'HR Response' : 'Ticket Update'; ?>
                                            </div>
                                            <div class="notification-message">
                                                <?php echo htmlspecialchars($notif['message']); ?>
                                            </div>
                                            <div class="notification-time">
                                                <i class="bi bi-clock"></i>
                                                <?php echo $timeAgo; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($notifications)): ?>
                        <div class="notification-footer">
                            <a href="notifications.php" class="view-all-link">View All Notifications</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- User Dropdown -->
            <div class="dropdown">
                <button class="btn" type="button" id="userDropdown">
                    <img src="<?php echo htmlspecialchars($photoPath); ?>" alt="Employee"
                         class="rounded-circle" width="32" height="32">
                    <span><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                </button>
                <ul class="dropdown-menu" id="userDropdownMenu">
                    <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person"></i> Profile</a></li>
                    <li><a class="dropdown-item" href="settings.php"><i class="bi bi-gear"></i> Settings</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Notification Modal -->
<div class="notification-modal-overlay" id="notificationModal" onclick="closeNotificationModal(event)">
    <div class="notification-modal" onclick="event.stopPropagation()">
        <div class="modal-header-section">
            <div class="modal-header-content">
                <h3>
                    <i class="bi bi-chat-dots-fill"></i>
                    <span id="modalTitle">HR Response</span>
                </h3>
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
                    <span class="modal-info-label">Notification Type</span>
                    <span class="modal-info-value" id="modalType"></span>
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

            <div id="modalResponseSection" style="display: none;">
                <div class="modal-section-title">
                    <i class="bi bi-reply-fill"></i>
                    HR Response
                </div>
                <div class="modal-response-box">
                    <div class="response-header">
                        <div class="response-avatar">HR</div>
                        <div class="response-meta">
                            <h4>HR Department</h4>
                            <div class="response-time">
                                <i class="bi bi-clock"></i>
                                <span id="modalResponseTime"></span>
                            </div>
                        </div>
                    </div>
                    <div class="response-text" id="modalResponse"></div>
                </div>
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

// ===== SHOW NOTIFICATION MODAL =====
function showNotificationModal(element) {
    const data = element.dataset;
    currentTicketId = data.ticketId;
    
    document.getElementById('modalTitle').textContent = data.type === 'ticket_response' ? 'HR Response' : 'Ticket Update';
    document.getElementById('modalTicketNumber').textContent = data.ticketNumber;
    document.getElementById('modalType').textContent = data.type === 'ticket_response' ? 'HR Response' : 'Ticket Update';
    document.getElementById('modalTime').textContent = data.time;
    document.getElementById('modalMessage').textContent = data.message;
    
    // Show HR response if available
    if (data.response && data.response.trim() !== '') {
        document.getElementById('modalResponseSection').style.display = 'block';
        document.getElementById('modalResponse').innerHTML = data.response.replace(/\n/g, '<br>');
        document.getElementById('modalResponseTime').textContent = data.time;
    } else {
        document.getElementById('modalResponseSection').style.display = 'none';
    }
    
    document.getElementById('notificationModal').classList.add('show');
    document.getElementById('notificationDropdown').classList.remove('show');
    document.body.style.overflow = 'hidden';
    
    // Mark notification as read
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
        // Redirect to ticket_detail.php
        window.location.href = 'ticket_detail.php?id=' + currentTicketId;
    } else {
        alert('Ticket ID not found!');
    }
}

// Close modal on ESC key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeNotificationModal();
    }
});

// ===== NOTIFICATION DROPDOWN =====
function toggleNotifications() {
    const dropdown = document.getElementById('notificationDropdown');
    dropdown.classList.toggle('show');
    
    // Close user dropdown if open
    const userMenu = document.getElementById('userDropdownMenu');
    if (userMenu && userMenu.classList.contains('show')) {
        userMenu.classList.remove('show');
        document.getElementById('userDropdown').classList.remove('show');
    }
}

// Close notification dropdown on outside click
document.addEventListener('click', (e) => {
    const wrapper = document.querySelector('.notification-wrapper');
    const dropdown = document.getElementById('notificationDropdown');
    
    if (wrapper && !wrapper.contains(e.target)) {
        dropdown.classList.remove('show');
    }
});

// Mark notification as read
function markAsRead(notificationId) {
    if (!notificationId) return;
    
    fetch('mark_notification_read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'notification_id=' + notificationId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update badge count
            const badge = document.querySelector('.notification-badge');
            if (badge) {
                const currentCount = parseInt(badge.textContent);
                if (currentCount > 1) {
                    badge.textContent = currentCount - 1;
                } else {
                    badge.remove();
                }
            }
            
            // Remove unread class
            const items = document.querySelectorAll('.notification-item');
            items.forEach(item => {
                if (item.dataset.notifId == notificationId) {
                    item.classList.remove('unread');
                }
            });
        }
    })
    .catch(error => console.error('Error:', error));
}

// ===== USER DROPDOWN TOGGLE =====
document.addEventListener('DOMContentLoaded', function() {
    const dropdownToggle = document.getElementById('userDropdown');
    const dropdownMenu = document.getElementById('userDropdownMenu');
    
    if (dropdownToggle && dropdownMenu) {
        dropdownToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Close notification dropdown if open
            const notificationDropdown = document.getElementById('notificationDropdown');
            if (notificationDropdown && notificationDropdown.classList.contains('show')) {
                notificationDropdown.classList.remove('show');
            }
            
            // Toggle user dropdown
            dropdownMenu.classList.toggle('show');
            dropdownToggle.classList.toggle('show');
        });
        
        // Close on outside click
        document.addEventListener('click', function(e) {
            if (dropdownToggle && dropdownMenu && 
                !dropdownToggle.contains(e.target) && 
                !dropdownMenu.contains(e.target)) {
                dropdownMenu.classList.remove('show');
                dropdownToggle.classList.remove('show');
            }
        });
        
        // Close when clicking dropdown items
        if (dropdownMenu) {
            dropdownMenu.querySelectorAll('.dropdown-item').forEach(item => {
                item.addEventListener('click', function(e) {
                    dropdownMenu.classList.remove('show');
                    dropdownToggle.classList.remove('show');
                });
            });
        }
    }
});

// ===== TIME-BASED GREETING =====
<?php if ($currentPage === 'dashboard'): ?>
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
