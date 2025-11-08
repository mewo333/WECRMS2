<?php
require_once 'config/database.php';

$db = new Database();
$conn = $db->connect();
$employee_id = $_SESSION['employee_id'];

$stmt = $conn->prepare("SELECT * FROM employees WHERE employee_id = :id");
$stmt->execute(['id' => $employee_id]);
$employee = $stmt->fetch(PDO::FETCH_ASSOC);

$photoPath = (!empty($employee['photo']) && file_exists($employee['photo'])) 
    ? $employee['photo'] 
    : 'assets/images/default.png';
?>

<style>
:root {
    --sidebar-w: 280px;
    --sidebar-collapsed-w: 70px;
    --transition: all 0.4s cubic-bezier(0.68, -0.55, 0.27, 1.55);
    --shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

/* --- SIDEBAR --- */
.sidebar {
    position: fixed;
    left: 0;
    top: 0;
    height: 100vh;
    width: var(--sidebar-w);
    background: linear-gradient(180deg, #f3f6f8, #c5e0fb);
    color: #404040;
    border-top-right-radius: 60px;
    border-bottom-right-radius: 60px;
    padding: 25px 18px;
    box-shadow: var(--shadow);
    display: flex;
    flex-direction: column;
    gap: 18px;
    align-items: flex-start;
    transition: var(--transition);
    z-index: 1000;
    overflow: hidden;
}

/* --- CURVED EDGE EFFECT --- */
.sidebar::after {
    content: "";
    position: absolute;
    top: 0;
    right: -50px;
    width: 80px;
    height: 100%;
    border-top-left-radius: 60px;
    border-bottom-left-radius: 60px;
    filter: blur(10px);
}

/* --- COLLAPSED STATE --- */
.sidebar.collapsed {
    width: var(--sidebar-collapsed-w);
    padding: 25px 12px;
    border-top-right-radius: 40px;
    border-bottom-right-radius: 40px;
}

/* --- HEADER TITLE --- */
.sidebar-header {
    width: 100%;
}

.sidebar-header h2 {
    font-size: 18px;
    margin-bottom: 25px;
    text-align: center;
    transition: opacity 0.3s ease;
    color: #2c2c2c;
    font-weight: 700;
}

.sidebar.collapsed .sidebar-header h2 {
    opacity: 0;
    pointer-events: none;
}

/* --- PROFILE --- */
.admin-profile {
    text-align: center;
    padding: 1rem 0;
    width: 100%;
    transition: opacity 0.3s ease;
}

.sidebar.collapsed .admin-profile {
    opacity: 0;
    pointer-events: none;
}

.admin-avatar-img {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    object-fit: cover;
    margin-bottom: 0.75rem;
    box-shadow: 0 3px 8px rgba(0,0,0,0.1);
    border: 3px solid #fff;
}

.admin-name {
    color: #404040;
    font-weight: 600;
    font-size: 0.95rem;
    margin: 0;
}

/* --- NAVIGATION --- */
.sidebar-nav {
    flex: 1;
    width: 100%;
}

/* --- NAVIGATION ITEMS --- */
.nav-item {
    position: relative;
    display: flex;
    align-items: center;
    padding: 12px 15px;
    color: #404040;
    text-decoration: none;
    border-top-left-radius: 10px;
    border-bottom-left-radius: 10px;
    border-top-right-radius: 30px;
    border-bottom-right-radius: 30px;
    margin-bottom: 10px;
    width: calc(100% + 20px);
    right: -10px;
    font-weight: 500;
    transition: var(--transition);
}

/* Hover effect */
.nav-item:hover {
    transform: translateX(4px);
    background: rgba(255, 255, 255, 0.4);
}

/* Active item */
.nav-item.active {
    background: rgba(255, 255, 255, 1);
    box-shadow: 4px 0 15px rgba(0, 0, 0, 0.1);
    transform: translateX(6px);
    color: #2c2c2c;
    font-weight: 600;
}

/* Icon spacing */
.nav-icon {
    margin-right: 12px;
    font-size: 20px;
    flex-shrink: 0;
    width: 24px;
    text-align: center;
}

.sidebar.collapsed .nav-item span:not(.nav-icon) {
    display: none;
}

/* --- FOOTER --- */
.sidebar-footer {
    border-top: 1px solid rgba(0, 0, 0, 0.15);
    margin-top: auto;
    width: 100%;
    padding-top: 12px;
}

/* --- TOGGLE BUTTON --- */
#sidebarToggle {
    position: absolute;
    top: 50px;
    right: 20px;
    background-color: #b1d7ff;
    color: #808080;
    border: none;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    cursor: pointer;
    transition: var(--transition);
    z-index: 60;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: var(--shadow);
}

#sidebarToggle:hover {
    background-color: #9ec9f5;
    transform: scale(1.1);
}

#sidebarToggle i {
    font-size: 1.2rem;
}

/* ✅ --- MAKE NAV ITEMS CLICKABLE WHEN COLLAPSED --- */
.sidebar.collapsed {
    overflow: visible;
}

.sidebar.collapsed .nav-item {
    pointer-events: auto;
    opacity: 1;
    z-index: 5;
}

/* ✅ --- TOOLTIP ON COLLAPSE --- */
.sidebar.collapsed .nav-item[title]:hover::after {
    content: attr(title);
    position: absolute;
    left: 70px;
    background: #fff;
    color: #333;
    padding: 6px 10px;
    border-radius: 6px;
    white-space: nowrap;
    font-size: 0.85rem;
    box-shadow: 0 3px 8px rgba(0, 0, 0, 0.1);
    transition: opacity 0.2s ease;
    opacity: 1;
    z-index: 99;
}

/* ✅ --- MAIN CONTENT ADAPTIVE WIDTH --- */
.main-content {
    margin-left: var(--sidebar-w);
    transition: margin-left 0.4s cubic-bezier(0.68, -0.55, 0.27, 1.55);
}

.main-content.sidebar-collapsed {
    margin-left: var(--sidebar-collapsed-w);
}

/* Scrollbar */
.sidebar::-webkit-scrollbar {
    width: 6px;
}

.sidebar::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.3);
}

.sidebar::-webkit-scrollbar-thumb {
    background: rgba(0, 0, 0, 0.2);
    border-radius: 3px;
}

.sidebar::-webkit-scrollbar-thumb:hover {
    background: rgba(0, 0, 0, 0.3);
}

@media (max-width: 1200px) {
    .sidebar {
        transform: translateX(-100%);
    }
    
    .sidebar.active {
        transform: translateX(0);
    }
    
    .main-content {
        margin-left: 0 !important;
    }
}
</style>

<div class="sidebar" id="sidebar">
    <!-- Toggle Button -->
    <button id="sidebarToggle"><i class="bi bi-list"></i></button>

    <div class="sidebar-header">
        <h2>Trusting Social AI Philippines</h2>
        <div class="admin-profile">
            <img src="<?php echo htmlspecialchars($photoPath); ?>" alt="Employee" class="admin-avatar-img">
            <h6 class="admin-name"><?php echo htmlspecialchars($employee['full_name']); ?></h6>
        </div>
    </div>

    <nav class="sidebar-nav">
        <a href="hr_dashboard.php" title="Dashboard" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'hr_dashboard.php' ? 'active' : ''; ?>">
            <span class="nav-icon"><i class="bi bi-house-door"></i></span> <span>Dashboard</span>
        </a>
        <a href="hr_employees.php" title="Employee Monitoring" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'hr_employees.php' ? 'active' : ''; ?>">
            <span class="nav-icon"><i class="bi bi-people"></i></span> <span>Employees</span>
        </a>
        <a href="announcements.php" title="Announcements" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'announcements.php' ? 'active' : ''; ?>">
            <span class="nav-icon"><i class="bi bi-megaphone"></i></span> <span>Announcements</span>
        </a>
        <a href="hr_tickets.php" title="Tickets Management" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'hr_tickets.php' ? 'active' : ''; ?>">
            <span class="nav-icon"><i class="bi bi-ticket"></i></span> <span>Tickets</span>
        </a>
        <a href="hr_notifications.php" title="Notifications" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'hr_notifications.php' ? 'active' : ''; ?>">
            <span class="nav-icon"><i class="bi bi-bell"></i></span> <span>Notifications</span>
        </a>
    </nav>

    <div class="sidebar-footer">
        <a href="hr_settings.php" title="Settings" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'hr_settings.php' ? 'active' : ''; ?>">
            <span class="nav-icon"><i class="bi bi-gear"></i></span> <span>Settings</span>
        </a>
        <a href="logout.php" title="Logout" class="nav-item">
            <span class="nav-icon"><i class="bi bi-box-arrow-right"></i></span> <span>Logout</span>
        </a>
    </div>
</div>

<script>
// ✅ Wait for DOM to fully load
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const toggleBtn = document.getElementById('sidebarToggle');
    const mainContent = document.querySelector('.main-content');

    // Function to update main content margin
    function updateMainContentMargin() {
        if (mainContent) {
            if (sidebar.classList.contains('collapsed')) {
                mainContent.classList.add('sidebar-collapsed');
            } else {
                mainContent.classList.remove('sidebar-collapsed');
            }
        }
    }

    // Restore the sidebar state from localStorage
    if (localStorage.getItem('sidebar-collapsed') === 'true') {
        sidebar.classList.add('collapsed');
    }

    // Update main content on page load
    updateMainContentMargin();

    // Toggle collapse state when button clicked
    if (toggleBtn) {
        toggleBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            sidebar.classList.toggle('collapsed');
            
            // Save state to localStorage
            localStorage.setItem('sidebar-collapsed', sidebar.classList.contains('collapsed'));
            
            // Update main content margin
            updateMainContentMargin();
        });
    }

    // Prevent sidebar from expanding when clicking nav links
    sidebar.querySelectorAll('.nav-item').forEach(link => {
        link.addEventListener('click', (e) => {
            e.stopPropagation();
        });
    });
});
</script>
