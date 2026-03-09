<?php
// modules/ess/sidebar.php
require_once __DIR__ . "/includes/auth_employee.php"; 

$current_page = basename($_SERVER['PHP_SELF']);

$userName = $_SESSION['user_name'] ?? $_SESSION['username'] ?? 'Employee';
$userRole = $_SESSION['user_role'] ?? 'Staff';
$deptName = $_SESSION['department_name'] ?? 'General';

function isActive($page, $current) {
    return ($page == $current) ? 'active' : '';
}

// Initial State check
$isLeaveOpen = in_array($current_page, ['leave_apply.php', 'leave_history.php']);
$isClaimsOpen = in_array($current_page, ['claims_apply.php', 'claims_history.php']);
?>

<style>
    /* Force Sidebar Scroll if many items */
    .sidebar-nav {
        overflow-y: auto !important;
        max-height: calc(100vh - 150px);
        padding-bottom: 50px;
    }
    
    /* Removed box-shadow from main sidebar through external CSS override if needed */
    .sidebar {
        box-shadow: none !important;
    }

    /* Submenu - Removed Gray Background */
    .submenu {
        display: none; 
        padding-left: 20px;
        background: transparent !important; /* Inalis ang gray background */
        list-style: none;
    }
    
    /* Show if active */
    .submenu.active {
        display: block !important;
    }
    
    /* Rotation ng arrow */
    .has-submenu.active .submenu-icon {
        transform: rotate(180deg);
    }
    
    .nav-item-group {
        width: 100%;
    }
    
    /* Minimal border for separation instead of shadow */
    .sidebar {
        border-right: 1px solid var(--border-color);
    }
</style>

<link rel="stylesheet" href="../../css/manager/sidebar.css?v=1.5">
<script src="https://unpkg.com/lucide@latest"></script>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="logo-container">
            <div class="logo-wrapper"><img src="../../img/logo.png" alt="Logo" class="logo"></div>
            <div class="logo-text">
                <h2 class="app-name">Microfinance</h2>
                <span class="app-tagline">ESS Portal</span>
            </div>
        </div>
        <button class="sidebar-toggle" id="sidebarToggle">
            <i data-lucide="panel-left-close"></i>
        </button>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section">
            <span class="nav-section-title">MAIN MENU</span>

            <a href="dashboard.php" class="nav-item <?php echo isActive('dashboard.php', $current_page); ?>">
                <i data-lucide="chart-no-axes-combined"></i>
                <span>Dashboard</span>
            </a>

            <a href="attendance.php" class="nav-item <?php echo isActive('attendance.php', $current_page); ?>">
                <i data-lucide="file-clock"></i>
                <span>Time Attendance</span>
            </a>

            <a href="information_management.php" class="nav-item <?php echo isActive('information_management.php', $current_page); ?>">
                <i data-lucide="user-pen"></i>
                <span>Information Management</span>
            </a>

            <a href="applybank.php" class="nav-item <?php echo isActive('applybank.php', $current_page); ?>">
                <i data-lucide="landmark"></i>
                <span>Apply Bank Account</span>
            </a>

            <div class="nav-item-group">
                <div class="nav-item has-submenu <?php echo $isLeaveOpen ? 'active' : ''; ?>" 
                     onclick="toggleMySubmenu('leave', this)" 
                     style="cursor: pointer;">
                    <div class="nav-item-content">
                        <i data-lucide="tickets-plane"></i>
                        <span>Leave Management</span>
                    </div>
                    <i data-lucide="chevron-down" class="submenu-icon"></i>
                </div>
                <div class="submenu <?php echo $isLeaveOpen ? 'active' : ''; ?>" id="ess-leave">
                    <a href="leave_apply.php" class="submenu-item <?php echo isActive('leave_apply.php', $current_page); ?>">
                        <i data-lucide="file-plus" style="width:14px;"></i> <span>Apply for Leave</span>
                    </a>
                    <a href="leave_history.php" class="submenu-item <?php echo isActive('leave_history.php', $current_page); ?>">
                        <i data-lucide="history" style="width:14px;"></i> <span>Leave History</span>
                    </a>
                </div>
            </div>

            <div class="nav-item-group">
                <div class="nav-item has-submenu <?php echo $isClaimsOpen ? 'active' : ''; ?>" 
                     onclick="toggleMySubmenu('claims', this)" 
                     style="cursor: pointer;">
                    <div class="nav-item-content">
                        <i data-lucide="receipt-text"></i>
                        <span>Claim Management</span>
                    </div>
                    <i data-lucide="chevron-down" class="submenu-icon"></i>
                </div>
                <div class="submenu <?php echo $isClaimsOpen ? 'active' : ''; ?>" id="ess-claims">
                    <a href="claims_apply.php" class="submenu-item <?php echo isActive('claims_apply.php', $current_page); ?>">
                        <i data-lucide="file-text" style="width:14px;"></i> <span>Request Claim</span>
                    </a>
                    <a href="claims_history.php" class="submenu-item <?php echo isActive('claims_history.php', $current_page); ?>">
                        <i data-lucide="clipboard-list" style="width:14px;"></i> <span>Claim History</span>
                    </a>
                </div>
            </div>

            <a href="payslip.php" class="nav-item <?php echo isActive('payslip.php', $current_page); ?>">
                <i data-lucide="ticket-check"></i>
                <span>View Payslip</span>
            </a>
        </div>

        <div class="nav-section">
            <span class="nav-section-title">SETTINGS</span>
            <a href="security.php" class="nav-item <?php echo isActive('security.php', $current_page); ?>">
                <i data-lucide="shield"></i> <span>Security</span>
            </a>
        </div>
    </nav>

    <div class="sidebar-footer">
        <div class="user-profile">
            <div class="user-avatar"><img src="../../img/profile.png" alt="User"></div>
            <div class="user-info">
                <span class="user-name"><?php echo htmlspecialchars($userName); ?></span>
                <span class="user-role"><?php echo htmlspecialchars($userRole); ?></span>
            </div>
            <button class="user-menu-btn" id="userMenuBtn"><i data-lucide="more-vertical"></i></button>
            <div class="user-menu-dropdown" id="userMenuDropdown">
                <a href="../../logout.php" class="umd-item umd-item-danger">
                    <i data-lucide="log-out"></i> <span>Sign Out</span>
                </a>
            </div>
        </div>
    </div>
</aside>

<script src="../../js/manager/sidebar.js"></script>
<script>
    lucide.createIcons();

    // Independent Toggle Function
    function toggleMySubmenu(module, element) {
        const targetId = 'ess-' + module;
        const target = document.getElementById(targetId);
        
        // Close all other submenus
        document.querySelectorAll('.submenu').forEach(sub => {
            if (sub.id !== targetId) {
                sub.classList.remove('active');
                sub.style.display = 'none';
                sub.previousElementSibling.classList.remove('active');
            }
        });

        // Toggle current
        const isActive = target.classList.contains('active');
        if (isActive) {
            target.classList.remove('active');
            target.style.display = 'none';
            element.classList.remove('active');
        } else {
            target.classList.add('active');
            target.style.display = 'block';
            element.classList.add('active');
        }
    }

    // User Menu
    const userBtn = document.getElementById('userMenuBtn');
    if(userBtn) {
        userBtn.onclick = (e) => {
            e.stopPropagation();
            document.getElementById('userMenuDropdown').classList.toggle('active');
        }
    }
    window.onclick = () => {
        const dropdown = document.getElementById('userMenuDropdown');
        if (dropdown) dropdown.classList.remove('active');
    };
</script>