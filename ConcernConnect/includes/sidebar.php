<style>
:root {
    --sidebar-w: 250px;
    --sidebar-collapsed-w: 70px;
    --transition: all 0.3s cubic-bezier(0.68, -0.55, 0.27, 1.55);
    --shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

/* --- SIDEBAR --- */
.sidebar {
    position: fixed;
    left: 0;
    top: 0;
    height: 100vh;
    width: var(--sidebar-w);
    background: #ffffff;
    color: #2d3748;
    border-top-right-radius: 60px;
    border-bottom-right-radius: 60px;
    padding: 25px 18px;
    box-shadow: var(--shadow);
    display: flex;
    flex-direction: column;
    gap: 18px;
    align-items: flex-start;
    transition: var(--transition);
    z-index: 40;
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
.sidebar-header h2 {
    font-size: 16px;
    font-weight: 700;
    margin-bottom: 25px;
    text-align: center;
    transition: opacity 0.3s ease;
    color: #764ba2;
    letter-spacing: 0.5px;
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
    box-shadow: 0 3px 8px rgba(0, 0, 0, 0.1);
    border: 3px solid #989898ff;
    transition: var(--transition);
}

.admin-avatar-img:hover {
    border-color: #d0d0d0ff;
}

.admin-name {
    color: #764ba2;
    font-weight: 700;
    font-size: 16px;
    margin: 0;
    letter-spacing: 0.3px;
}

/* --- NAVIGATION --- */
.sidebar-nav {
    flex: 0;
    width: 100%;
}

/* --- NAVIGATION ITEMS --- */
.nav-item {
    position: relative;
    display: flex;
    align-items: center;
    padding: 12px 15px;
    color: #764ba2;
    text-decoration: none;
    border-top-left-radius: 10px;
    border-bottom-left-radius: 10px;
    border-top-right-radius: 30px;
    border-bottom-right-radius: 30px;
    margin-bottom: 10px;
    width: calc(100% + 20px);
    right: -10px;
    font-weight: 500;
    font-size: 14px;
    transition: var(--transition);
}

/* Hover effect */
.nav-item:hover {
    background: #f0f4f8;
    color: #764ba2;
    transform: translateX(4px);
}

/* Active item */
.nav-item.active {
    background: #f0f4f8;
    color: #764ba2;
    font-weight: 600;
    box-shadow: 4px 0 12px rgba(0, 0, 0, 0.08);
    transform: translateX(6px);
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
    border-top: 1px solid #e2e8f0;
    margin-top: auto;
    width: 100%;
    padding-top: 12px;
}

/* --- TOGGLE BUTTON --- */
#sidebarToggle {
    position: absolute;
    top: 50px;
    right: 20px;
    background-color: #e8f0ff;
    color: #667eea;
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
    background-color: #d8e7ff;
    transform: translateX(2px);
}

#sidebarToggle i {
    font-size: 1.2rem;
    color: #667eea;
}

/* --- MAIN CONTENT --- */
.main-content {
    margin-left: var(--sidebar-w);
    padding: 20px;
    transition: margin-left 0.2s var(--transition);
    animation: slideIn 0.3s ease;
}

.sidebar.collapsed ~ .main-content {
    margin-left: var(--sidebar-collapsed-w);
}

/* --- ANIMATION --- */
@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateX(40px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
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

/* ✅ --- OPTIONAL TOOLTIP ON COLLAPSE --- */
.sidebar.collapsed .nav-item[title]:hover::after {
    content: attr(title);
    position: absolute;
    left: 70px;
    background: #2d3748;
    color: #ffffff;
    padding: 6px 10px;
    border-radius: 6px;
    white-space: nowrap;
    font-size: 12px;
    font-weight: 500;
    box-shadow: 0 3px 8px rgba(0, 0, 0, 0.12);
    transition: opacity 0.2s ease;
    opacity: 1;
    z-index: 99;
}
</style>
<?php
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
        <a href="dashboard.php" title="Home" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
            <span class="nav-icon"><i class="bi bi-house-door"></i></span> <span>Home</span>
        </a>
        <a href="profile.php" title="My Profile" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>">
            <span class="nav-icon"><i class="bi bi-person"></i></span> <span>My Profile</span>
        </a>
        <a href="tickets.php" title="Tickets" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'tickets.php' ? 'active' : ''; ?>">
            <span class="nav-icon"><i class="bi bi-ticket"></i></span> <span>Tickets</span>
        </a>
        <a href="chatbot.php" title="Chat with Bot" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'chatbot.php' ? 'active' : ''; ?>">
    <span class="nav-icon"><i class="bi bi-chat-dots"></i></span> <span>Chat with Bot</span>
        </a>
        <a href="status_update.php" title="Status Update" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'status_update.php' ? 'active' : ''; ?>">
            <span class="nav-icon"><i class="bi bi-bar-chart-steps"></i></span> <span>Status Update</span>
        </a>
    </nav>

    <div class="sidebar-footer">
        <a href="settings.php" title="Settings" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
            <span class="nav-icon"><i class="bi bi-gear"></i></span> <span>Settings</span>
        </a>
        <a href="logout.php" title="Logout" class="nav-item">
            <span class="nav-icon"><i class="bi bi-box-arrow-right"></i></span> <span>Logout</span>
        </a>
    </div>
</div>

<script>
const sidebar = document.getElementById('sidebar');
const toggleBtn = document.getElementById('sidebarToggle');

// Restore the sidebar state from localStorage
if (localStorage.getItem('sidebar-collapsed') === 'true') {
    sidebar.classList.add('collapsed');
}

// Toggle collapse state when button clicked
toggleBtn.addEventListener('click', (e) => {
    e.stopPropagation();
    sidebar.classList.toggle('collapsed');
    // Save state to localStorage
    localStorage.setItem('sidebar-collapsed', sidebar.classList.contains('collapsed'));
});

// Prevent sidebar from expanding when clicking nav links
sidebar.querySelectorAll('.nav-item').forEach(link => {
    link.addEventListener('click', (e) => {
        e.stopPropagation();
    });
});
</script>
