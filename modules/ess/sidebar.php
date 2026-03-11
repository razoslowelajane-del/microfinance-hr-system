<?php
// modules/ess/sidebar.php
require_once __DIR__ . "/includes/auth_employee.php";

$current_page = basename($_SERVER['PHP_SELF']);

$userName = $_SESSION['user_name'] ?? $_SESSION['username'] ?? 'Employee';
$userRole = $_SESSION['user_role'] ?? 'Staff';
$deptName = $_SESSION['department_name'] ?? 'General';

function isActive($page, $current) {
    return ($page === $current) ? 'active' : '';
}

$isAttendanceOpen = in_array($current_page, ['attendance.php', 'my_schedule.php']);
$isLeaveOpen  = in_array($current_page, ['leave_apply.php', 'leave_history.php']);
$isClaimsOpen = in_array($current_page, ['claims_apply.php', 'claims_history.php']);
?>

<link rel="stylesheet" href="../../css/officer/sidebar.css?v=1.0">
<script src="https://unpkg.com/lucide@latest"></script>

<style>
.sidebar-nav{
    overflow-y:auto;
    max-height:calc(100vh - 150px);
    padding-bottom:40px;
}
</style>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="logo-container">
            <div class="logo-wrapper">
                <img src="../../img/logo.png" alt="Logo" class="logo">
            </div>
            <div class="logo-text">
                <h2 class="app-name">Microfinance</h2>
                <span class="app-tagline">ESS Portal</span>
            </div>
        </div>

        <button class="sidebar-toggle" id="sidebarToggle" type="button">
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

            <div class="nav-item-group">
                <button class="nav-item has-submenu <?php echo $isAttendanceOpen ? 'active' : ''; ?>" data-module="attendance" type="button">
                    <div class="nav-item-content">
                        <i data-lucide="scan-face"></i>
                        <span>Time Attendance</span>
                    </div>
                    <i data-lucide="chevron-down" class="submenu-icon"></i>
                </button>
                <div class="submenu <?php echo $isAttendanceOpen ? 'active' : ''; ?>" id="submenu-attendance">
                    <a href="attendance.php" class="submenu-item <?php echo isActive('attendance.php', $current_page); ?>">
                        <i data-lucide="camera"></i>
                        <span>Attendance</span>
                    </a>
                    <a href="my_schedule.php" class="submenu-item <?php echo isActive('my_schedule.php', $current_page); ?>">
                        <i data-lucide="calendar-days"></i>
                        <span>My Schedule</span>
                    </a>
                </div>
            </div>

            <a href="information_management.php" class="nav-item <?php echo isActive('information_management.php', $current_page); ?>">
                <i data-lucide="user-pen"></i>
                <span>Information Management</span>
            </a>

            <div class="nav-item-group">
                <button class="nav-item has-submenu <?php echo $isLeaveOpen ? 'active' : ''; ?>" data-module="leave" type="button">
                    <div class="nav-item-content">
                        <i data-lucide="calendar-clock"></i>
                        <span>Leave Management</span>
                    </div>
                    <i data-lucide="chevron-down" class="submenu-icon"></i>
                </button>
                <div class="submenu <?php echo $isLeaveOpen ? 'active' : ''; ?>" id="submenu-leave">
                    <a href="leave_apply.php" class="submenu-item <?php echo isActive('leave_apply.php', $current_page); ?>">
                        <i data-lucide="file-plus"></i>
                        <span>Apply for Leave</span>
                    </a>
                    <a href="leave_history.php" class="submenu-item <?php echo isActive('leave_history.php', $current_page); ?>">
                        <i data-lucide="history"></i>
                        <span>Leave History</span>
                    </a>
                </div>
            </div>

            <div class="nav-item-group">
                <button class="nav-item has-submenu <?php echo $isClaimsOpen ? 'active' : ''; ?>" data-module="claims" type="button">
                    <div class="nav-item-content">
                        <i data-lucide="receipt"></i>
                        <span>Claim Management</span>
                    </div>
                    <i data-lucide="chevron-down" class="submenu-icon"></i>
                </button>
                <div class="submenu <?php echo $isClaimsOpen ? 'active' : ''; ?>" id="submenu-claims">
                    <a href="claims_apply.php" class="submenu-item <?php echo isActive('claims_apply.php', $current_page); ?>">
                        <i data-lucide="file-text"></i>
                        <span>Request Claim</span>
                    </a>
                    <a href="claims_history.php" class="submenu-item <?php echo isActive('claims_history.php', $current_page); ?>">
                        <i data-lucide="clipboard-list"></i>
                        <span>Claim History</span>
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

            <button class="user-menu-btn" id="userMenuBtn" type="button">
                <i data-lucide="more-vertical"></i>
            </button>

            <div class="user-menu-dropdown" id="userMenuDropdown">
                <div class="umd-header">
                    <div class="umd-avatar" id="umdAvatar"></div>
                    <div class="umd-info">
                        <span class="umd-signed">Signed in as</span>
                        <span class="umd-name" id="umdName"><?php echo htmlspecialchars($userName); ?></span>
                        <span class="umd-role" id="umdRole"><?php echo htmlspecialchars($userRole); ?></span>
                    </div>
                </div>

                <div class="umd-divider"></div>

                <a href="information_management.php" class="umd-item">
                    <i data-lucide="user-round"></i>
                    <span>Profile</span>
                </a>

                <div class="umd-divider"></div>

                <a href="../../logout.php" class="umd-item umd-item-danger umd-sign-out">
                    <i data-lucide="log-out"></i>
                    <span>Sign Out</span>
                </a>
            </div>
        </div>
    </div>
</aside>

<script src="../../js/manager/sidebar.js?v=<?php echo time(); ?>"></script>
<script src="../../js/user-menu.js?v=<?php echo time(); ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (window.lucide) lucide.createIcons();
});
</script>