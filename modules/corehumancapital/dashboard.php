<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: ../../login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard</title>
  <link rel="stylesheet" href="../../css/chcdashboard.css?v=1.2">
  <link rel="stylesheet" href="../../css/sidebar-fix.css?v=1.0">
  <script src="https://unpkg.com/lucide@latest"></script>
  <link rel="icon" type="image/png" href="../../img/logo.png">
</head>
<body>

  <!-- Sidebar -->
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
      <div class="logo-container">
        <div class="logo-wrapper">
          <img src="../../img/logo.png" alt="Logo" class="logo">
        </div>
        <div class="logo-text">
          <h2 class="app-name">Microfinance</h2>
          <span class="app-tagline">32005</span>
        </div>
      </div>
      <button class="sidebar-toggle" id="sidebarToggle">
        <i data-lucide="panel-left-close"></i>
      </button>
    </div>

    <nav class="sidebar-nav">
      <div class="nav-section">
        <span class="nav-section-title">MAIN MENU</span>
        
        <a href="dashboard.php" class="nav-item <?php echo ($page === 'dashboard') ? 'active' : ''; ?>">
          <i data-lucide="chart-no-axes-combined"></i>
          <span>HR ANALYTICS</span>
        </a>

        <div class="nav-item-group <?php echo ($module === 'hr') ? 'active' : ''; ?>">
          <button class="nav-item has-submenu" data-module="hr">
            <div class="nav-item-content">
              <i data-lucide="book-user"></i>
              <span>Core Human Capital</span>
            </div>
            <i data-lucide="chevron-down" class="submenu-icon"></i>
          </button>
          <div class="submenu" id="submenu-hr">
            <a href="" class="submenu-item">
              <i data-lucide="user-plus"></i>
              <span>New Hired Onboard Request</span>
            </a>
            <a href="employeemaster.php" class="submenu-item <?php echo ($page === 'employeemaster') ? 'active' : ''; ?>">
              <i data-lucide="file-user"></i>
              <span>Employee Master Files</span>
            </a>
             <a href="informationrq.php" class="submenu-item <?php echo ($page === 'informationrq') ? 'active' : ''; ?>">
              <i data-lucide="user-round-pen"></i>
              <span>Information Request</span>
            </a>
            <a href="bankform.php" class="submenu-item <?php echo ($page === 'bankform') ? 'active' : ''; ?>">
              <i data-lucide="file-text"></i>
              <span>Bank Form Management</span>
            </a>
            <a href="" class="submenu-item">
              <i data-lucide="user-cog"></i>
              <span>Security Settings</span>
            </a>
            <a href="auditlogs.php" class="submenu-item <?php echo ($page === 'auditlogs') ? 'active' : ''; ?>">
              <i data-lucide="book-user"></i>
              <span>Audit Logs</span>
            </a>
          </div>
        </div>

          <div class="nav-item-group <?php echo ($module === 'planning') ? 'active' : ''; ?>">
          <button class="nav-item has-submenu" data-module="planning">
            <div class="nav-item-content">
              <i data-lucide="circle-pile"></i>
              <span>Compensation Planning</span>
            </div>
            <i data-lucide="chevron-down" class="submenu-icon"></i>
          </button>
          <div class="submenu" id="submenu-planning">
            <a href="#" class="submenu-item">
              <i data-lucide="file-plus"></i>
              <span>Applications</span>
            </a>
            <a href="#" class="submenu-item">
              <i data-lucide="check-circle"></i>
              <span>Approvals</span>
            </a>
            <a href="#" class="submenu-item">
              <i data-lucide="calendar-clock"></i>
              <span>Disbursements</span>
            </a>
            <a href="#" class="submenu-item">
              <i data-lucide="coins"></i>
              <span>Collections</span>
            </a>
          </div>
        </div>

           <div class="nav-item-group <?php echo ($module === 'payroll') ? 'active' : ''; ?>">
          <button class="nav-item has-submenu" data-module="payroll">
            <div class="nav-item-content">
              <i data-lucide="banknote-arrow-down"></i>
              <span>Payroll</span>
            </div>
            <i data-lucide="chevron-down" class="submenu-icon"></i>
          </button>
          <div class="submenu" id="submenu-payroll">
            <a href="#" class="submenu-item">
              <i data-lucide="file-plus"></i>
              <span>Applications</span>
            </a>
            <a href="#" class="submenu-item">
              <i data-lucide="check-circle"></i>
              <span>Approvals</span>
            </a>
            <a href="#" class="submenu-item">
              <i data-lucide="calendar-clock"></i>
              <span>Disbursements</span>
            </a>
            <a href="#" class="submenu-item">
              <i data-lucide="coins"></i>
              <span>Collections</span>
            </a>
          </div>
        </div>
      </div>

      <div class="nav-section">
        <span class="nav-section-title">SETTINGS</span>
        
        <a href="#" class="nav-item">
          <i data-lucide="settings"></i>
          <span>Configuration</span>
        </a>

        <a href="#" class="nav-item">
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
          <span class="user-name"><?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></span>
          <span class="user-role"><?php echo htmlspecialchars($_SESSION['user_role'] ?? 'Employee'); ?></span>
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
          <a href="profile.php" class="umd-item"><i data-lucide="user-round"></i><span>Profile</span></a>
          <div class="umd-divider"></div>
          <a href="../../login.php" class="umd-item umd-item-danger umd-sign-out"><i data-lucide="log-out"></i><span>Sign Out</span></a>
        </div>
      </div>
    </div>
  </aside>

  <!-- Main Content -->
  <main class="main-content">
    <header class="page-header">
      <div class="header-left">
        <button class="mobile-menu-btn" id="mobileMenuBtn">
          <i data-lucide="menu"></i>
        </button>
         <div class="header-title">
          <h1>Dashboard Overview</h1>
          <p>Welcome back, <?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?>! Here's what's happening today.</p>
        </div>
      </div>
      <div class="header-right">
        <div class="search-box">
          <i data-lucide="search"></i>
          <input type="search" placeholder="Search...">
        </div>
        <button class="theme-toggle" id="themeToggle" aria-label="Toggle theme">
          <i data-lucide="sun" class="sun-icon"></i>
          <i data-lucide="moon" class="moon-icon"></i>
        </button>
        <button class="icon-btn">
          <i data-lucide="bell"></i>
        </button>
      </div>
    </header>

    <div class="content-wrapper">
      <!-- Stats Grid -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon" style="background: rgba(44, 160, 120, 0.1); color: var(--brand-green);">
            <i data-lucide="users"></i>
          </div>
          <div class="stat-content">
            <span class="stat-label">Total Clients</span>
            <h3 class="stat-value">2,847</h3>
            <div class="stat-trend positive">
              <i data-lucide="trending-up"></i>
              <span>+12.5% from last month</span>
            </div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon" style="background: rgba(255, 193, 7, 0.1); color: var(--brand-yellow);">
            <i data-lucide="banknote"></i>
          </div>
          <div class="stat-content">
            <span class="stat-label">Active Loans</span>
            <h3 class="stat-value">1,234</h3>
            <div class="stat-trend positive">
              <i data-lucide="trending-up"></i>
              <span>+8.3% from last month</span>
            </div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon" style="background: rgba(239, 68, 68, 0.1); color: #ef4444;">
            <i data-lucide="alert-circle"></i>
          </div>
          <div class="stat-content">
            <span class="stat-label">Overdue Payments</span>
            <h3 class="stat-value">89</h3>
            <div class="stat-trend negative">
              <i data-lucide="trending-down"></i>
              <span>-3.2% from last month</span>
            </div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon" style="background: rgba(59, 130, 246, 0.1); color: #3b82f6;">
            <i data-lucide="wallet"></i>
          </div>
          <div class="stat-content">
            <span class="stat-label">Total Portfolio</span>
            <h3 class="stat-value">$4.2M</h3>
            <div class="stat-trend positive">
              <i data-lucide="trending-up"></i>
              <span>+15.7% from last month</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Content Grid -->
      <div class="content-grid">
        <!-- Recent Applications -->
        <div class="content-card">
          <div class="card-header">
            <div>
              <h3 class="card-title">Recent Loan Applications</h3>
              <p class="card-subtitle">Latest applications requiring review</p>
            </div>
            <button class="btn-text">View All</button>
          </div>
          <div class="card-body">
            <div class="data-table">
              <div class="table-row">
                <div class="table-cell">
                  <div class="client-info">
                    <div class="client-avatar" style="background: #2ca078;">JD</div>
                    <div>
                      <span class="client-name">John Doe</span>
                      <span class="client-detail">Personal Loan</span>
                    </div>
                  </div>
                </div>
                <div class="table-cell">
                  <span class="amount">$15,000</span>
                </div>
                <div class="table-cell">
                  <span class="badge-status pending">Pending</span>
                </div>
              </div>

              <div class="table-row">
                <div class="table-cell">
                  <div class="client-info">
                    <div class="client-avatar" style="background: #ffc107;">SM</div>
                    <div>
                      <span class="client-name">Sarah Miller</span>
                      <span class="client-detail">Business Loan</span>
                    </div>
                  </div>
                </div>
                <div class="table-cell">
                  <span class="amount">$25,000</span>
                </div>
                <div class="table-cell">
                  <span class="badge-status approved">Approved</span>
                </div>
              </div>

              <div class="table-row">
                <div class="table-cell">
                  <div class="client-info">
                    <div class="client-avatar" style="background: #3b82f6;">RJ</div>
                    <div>
                      <span class="client-name">Robert Johnson</span>
                      <span class="client-detail">Agricultural Loan</span>
                    </div>
                  </div>
                </div>
                <div class="table-cell">
                  <span class="amount">$8,500</span>
                </div>
                <div class="table-cell">
                  <span class="badge-status review">Under Review</span>
                </div>
              </div>

              <div class="table-row">
                <div class="table-cell">
                  <div class="client-info">
                    <div class="client-avatar" style="background: #ef4444;">LW</div>
                    <div>
                      <span class="client-name">Lisa Williams</span>
                      <span class="client-detail">Education Loan</span>
                    </div>
                  </div>
                </div>
                <div class="table-cell">
                  <span class="amount">$12,000</span>
                </div>
                <div class="table-cell">
                  <span class="badge-status pending">Pending</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Quick Actions -->
        <div class="content-card">
          <div class="card-header">
            <div>
              <h3 class="card-title">Quick Actions</h3>
              <p class="card-subtitle">Common tasks and shortcuts</p>
            </div>
          </div>
          <div class="card-body">
            <div class="quick-actions">
              <button class="action-btn">
                <i data-lucide="user-plus"></i>
                <span>Add New Client</span>
              </button>
              <button class="action-btn">
                <i data-lucide="file-plus"></i>
                <span>New Loan Application</span>
              </button>
              <button class="action-btn">
                <i data-lucide="receipt"></i>
                <span>Record Payment</span>
              </button>
              <button class="action-btn">
                <i data-lucide="file-text"></i>
                <span>Generate Report</span>
              </button>
              <button class="action-btn">
                <i data-lucide="calendar"></i>
                <span>Schedule Meeting</span>
              </button>
              <button class="action-btn">
                <i data-lucide="send"></i>
                <span>Send Notification</span>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Bottom Grid -->
      <div class="bottom-grid">
        <!-- Upcoming Payments -->
        <div class="content-card">
          <div class="card-header">
            <div>
              <h3 class="card-title">Upcoming Payments</h3>
              <p class="card-subtitle">Payments due in the next 7 days</p>
            </div>
            <button class="btn-text">View Calendar</button>
          </div>
          <div class="card-body">
            <div class="payment-list">
              <div class="payment-item">
                <div class="payment-date">
                  <span class="date-day">15</span>
                  <span class="date-month">Dec</span>
                </div>
                <div class="payment-details">
                  <span class="payment-client">Michael Chen</span>
                  <span class="payment-type">Monthly Installment</span>
                </div>
                <div class="payment-amount">$850</div>
              </div>

              <div class="payment-item">
                <div class="payment-date">
                  <span class="date-day">16</span>
                  <span class="date-month">Dec</span>
                </div>
                <div class="payment-details">
                  <span class="payment-client">Emma Davis</span>
                  <span class="payment-type">Loan Payment</span>
                </div>
                <div class="payment-amount">$1,200</div>
              </div>

              <div class="payment-item">
                <div class="payment-date">
                  <span class="date-day">18</span>
                  <span class="date-month">Dec</span>
                </div>
                <div class="payment-details">
                  <span class="payment-client">James Wilson</span>
                  <span class="payment-type">Interest Payment</span>
                </div>
                <div class="payment-amount">$450</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Activity Feed -->
        <div class="content-card">
          <div class="card-header">
            <div>
              <h3 class="card-title">Recent Activity</h3>
              <p class="card-subtitle">Latest system activities</p>
            </div>
          </div>
          <div class="card-body">
            <div class="activity-list">
              <div class="activity-item">
                <div class="activity-icon" style="background: rgba(44, 160, 120, 0.1); color: var(--brand-green);">
                  <i data-lucide="check-circle"></i>
                </div>
                <div class="activity-content">
                  <p class="activity-text"><strong>Loan Approved</strong> for Sarah Miller</p>
                  <span class="activity-time">2 minutes ago</span>
                </div>
              </div>

              <div class="activity-item">
                <div class="activity-icon" style="background: rgba(255, 193, 7, 0.1); color: var(--brand-yellow);">
                  <i data-lucide="dollar-sign"></i>
                </div>
                <div class="activity-content">
                  <p class="activity-text"><strong>Payment Received</strong> from John Doe ($850)</p>
                  <span class="activity-time">15 minutes ago</span>
                </div>
              </div>

              <div class="activity-item">
                <div class="activity-icon" style="background: rgba(59, 130, 246, 0.1); color: #3b82f6;">
                  <i data-lucide="user-plus"></i>
                </div>
                <div class="activity-content">
                  <p class="activity-text"><strong>New Client</strong> registered: Lisa Williams</p>
                  <span class="activity-time">1 hour ago</span>
                </div>
              </div>

              <div class="activity-item">
                <div class="activity-icon" style="background: rgba(239, 68, 68, 0.1); color: #ef4444;">
                  <i data-lucide="alert-triangle"></i>
                </div>
                <div class="activity-content">
                  <p class="activity-text"><strong>Payment Overdue</strong> for Michael Chen</p>
                  <span class="activity-time">3 hours ago</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

<?php 
// No specific scripts for dashboard in the original include, but we can check.
// Actually footer.php includes sidebar-active.js and chcdashboard.js for everything in this module.
?>
  </main>
  
  <script src="../../js/sidebar-active.js"></script>
  <script src="../../js/chcdashboard.js"></script>
  <?php if (isset($extraScripts)) echo $extraScripts; ?>
  <script>
    lucide.createIcons();
  </script>
  
  <script src="../../js/user-menu.js"></script>
</body>
</html>


