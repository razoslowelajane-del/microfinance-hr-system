<?php
// modules/manager/sidebar.php
require_once __DIR__ . "/includes/auth_hr_manager.php";
$current_page = basename($_SERVER['PHP_SELF']);

$userName = $_SESSION['user_name'] ?? $_SESSION['username'] ?? 'HR Manager';
$userRole = $_SESSION['user_role'] ?? 'HR Manager';
$deptName = 'HR Department';

// Helper function para sa Active Class
function isActive($page, $current) {
    return ($page == $current) ? 'active' : '';
}

// Helper para i-check kung dapat nakabukas ang Submenu
$isWorkforceOpen = in_array($current_page, ['roster.php']);
$isRequestsOpen = in_array($current_page, ['leave.php', 'claims.php']);
?>

<link rel="stylesheet" href="../../css/manager/sidebar.css?v=1.0">
<script src="https://unpkg.com/lucide@latest"></script>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="logo-container">
            <div class="logo-wrapper">
                <img src="../../img/logo.png" alt="Logo" class="logo">
            </div>
            <div class="logo-text">
                <h2 class="app-name">Microfinance</h2>
                <span class="app-tagline"><?php echo htmlspecialchars($deptName); ?></span>
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
                <i data-lucide="layout-dashboard"></i>
                <span>Dashboard</span>
            </a>

            <!-- HR Manager Workforce Review -->
            <div class="nav-item-group">
                <button class="nav-item has-submenu <?php echo $isWorkforceOpen ? 'active' : ''; ?>" data-module="workforce">
                    <div class="nav-item-content">
                        <i data-lucide="calendar-days"></i>
                        <span>Roster Management</span>
                    </div>
                    <i data-lucide="chevron-down" class="submenu-icon"></i>
                </button>

                <div class="submenu <?php echo $isWorkforceOpen ? 'active' : ''; ?>" id="submenu-workforce">

                    <a href="roster.php" class="submenu-item <?php echo isActive('roster.php', $current_page); ?>">
                        <i data-lucide="calendar-check"></i>
                        <span>Review Rosters</span>
                    </a>

                </div>
            </div>

            <!-- HR Approvals -->
            <div class="nav-item-group">
                <button class="nav-item has-submenu <?php echo $isRequestsOpen ? 'active' : ''; ?>" data-module="requests">
                    <div class="nav-item-content">
                        <i data-lucide="clipboard-check"></i>
                        <span>Approvals</span>
                    </div>
                    <i data-lucide="chevron-down" class="submenu-icon"></i>
                </button>

                <div class="submenu <?php echo $isRequestsOpen ? 'active' : ''; ?>" id="submenu-requests">

                    <a href="leave.php" class="submenu-item <?php echo isActive('leave.php', $current_page); ?>">
                        <i data-lucide="calendar-clock"></i>
                        <span>Leave Requests</span>
                    </a>

                    <a href="claims.php" class="submenu-item <?php echo isActive('claims.php', $current_page); ?>">
                        <i data-lucide="receipt"></i>
                        <span>Reimbursement Claims</span>
                    </a>

                </div>
            </div>

            <!-- Employee Records -->
            <a href="employees.php" class="nav-item <?php echo isActive('employees.php', $current_page); ?>">
                <i data-lucide="users"></i>
                <span>Employee Records</span>
            </a>

        </div>

        <div class="nav-section">
            <span class="nav-section-title">SETTINGS</span>

            <a href="settings.php" class="nav-item <?php echo isActive('settings.php', $current_page); ?>">
                <i data-lucide="settings"></i>
                <span>Settings</span>
            </a>

            <a href="security.php" class="nav-item <?php echo isActive('security.php', $current_page); ?>">
                <i data-lucide="shield"></i>
                <span>Security</span>
            </a>

        </div>
    </nav>

    <div class="sidebar-footer">
        <div class="user-profile">
            <div class="user-avatar">
                <img src="../../img/profile.png" alt="User">
            </div>

            <div class="user-info">
                <span class="user-name"><?php echo htmlspecialchars($userName); ?></span>
                <span class="user-role"><?php echo htmlspecialchars($userRole); ?></span>
            </div>

            <button class="user-menu-btn" id="userMenuBtn">
                <i data-lucide="more-vertical"></i>
            </button>

        <div class="user-menu-dropdown" id="userMenuDropdown">

          <div class="umd-header">
            <div class="umd-avatar" id="umdAvatar"></div>
            <div class="umd-info">
              <span class="umd-signed">Signed in as</span>
              <span class="umd-name" id="umdName"></span>
              <span class="umd-role" id="umdRole"></span>
            </div>
          </div>

          <div class="umd-divider"></div>

          <a href="profile.php" class="umd-item">
            <i data-lucide="user-round"></i>
            <span>Profile</span>
          </a>

          <div class="umd-divider"></div>

          <a href="../../login.php" class="umd-item umd-item-danger umd-sign-out">
            <i data-lucide="log-out"></i>
            <span>Sign Out</span>
          </a>

        </div>
      </div>
    </div>
    
</aside>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../../js/manager/sidebar.js"></script>
<script src="../../js/user-menu.js"></script>
<script>lucide.createIcons();</script>