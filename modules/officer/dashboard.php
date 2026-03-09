<?php
// modules/officer/dashboard.php
require_once __DIR__ . "/includes/auth_officer.php"; // ✅ NEW: replaces basic guard

// Placeholder only (design first)
$userName = $_SESSION['user_name'] ?? $_SESSION['username'] ?? 'Officer';
$userRole = $_SESSION['user_role'] ?? 'Department Officer';
$deptName = $_SESSION['department_name'] ?? 'My Department';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Officer Dashboard</title>

  <link rel="stylesheet" href="../../css/officer/dashboard.css">
  <script src="https://unpkg.com/lucide@latest"></script>
  <link rel="icon" type="image/png" href="../../img/logo.png">
</head>
<body>
<?php include 'sidebar.php' ?>

  <!-- Main Content -->
  <main class="main-content">
    <header class="page-header">
      <div class="header-left">
        <button class="mobile-menu-btn" id="mobileMenuBtn" aria-label="Open menu">
          <i data-lucide="menu"></i>
        </button>

        <div class="header-title">
          <h1>Dashboard Overview</h1>
          <p>Welcome back, <?php echo htmlspecialchars($userName); ?>!</p> <!-- optional -->
        </div>
      </div>

      <div class="header-right">
        <div class="search-box">
          <i data-lucide="search"></i>
          <input type="search" placeholder="Search...">
        </div>
    
          <?php include 'theme.php' ?>
        

        <button class="icon-btn" aria-label="Notifications">
          <i data-lucide="bell"></i>
          <span class="badge">3</span>
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
            <span class="stat-label">Pending Timesheets</span>
            <h3 class="stat-value">0</h3>
            <div class="stat-trend positive">
              <i data-lucide="trending-up"></i>
              <span>For Review</span>
            </div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon" style="background: rgba(255, 193, 7, 0.1); color: var(--brand-yellow);">
            <i data-lucide="calendar-days"></i>
          </div>
          <div class="stat-content">
            <span class="stat-label">Attendance Today</span>
            <h3 class="stat-value">0</h3>
            <div class="stat-trend positive">
              <i data-lucide="trending-up"></i>
              <span>Live</span>
            </div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon" style="background: rgba(239, 68, 68, 0.1); color: #ef4444;">
            <i data-lucide="alert-circle"></i>
          </div>
          <div class="stat-content">
            <span class="stat-label">Pending Leaves</span>
            <h3 class="stat-value">0</h3>
            <div class="stat-trend negative">
              <i data-lucide="trending-down"></i>
              <span>For Review</span>
            </div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon" style="background: rgba(59, 130, 246, 0.1); color: #3b82f6;">
            <i data-lucide="file-text"></i>
          </div>
          <div class="stat-content">
            <span class="stat-label">Pending Claims</span>
            <h3 class="stat-value">0</h3>
            <div class="stat-trend positive">
              <i data-lucide="trending-up"></i>
              <span>For Review</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Content Grid -->
      <div class="content-grid">

        <!-- Left Card -->
        <div class="content-card">
          <div class="card-header">
            <div>
              <h3 class="card-title">Pending Approvals</h3>
              <p class="card-subtitle">Department-based items</p>
            </div>
            <button class="btn-text">View All</button>
          </div>
          <div class="card-body">
            <div class="data-table">
              <!-- ✅ This will be replaced by JS with real rows -->
              <?php for ($i=1; $i<=4; $i++): ?>
              <div class="table-row">
                <div class="table-cell">
                  <div class="client-info">
                    <div class="client-avatar" style="background: #2ca078;">OF</div>
                    <div>
                      <span class="client-name">Loading...</span>
                      <span class="client-detail">Please wait</span>
                    </div>
                  </div>
                </div>
                <div class="table-cell"><span class="amount">—</span></div>
                <div class="table-cell"><span class="badge-status pending">Pending</span></div>
              </div>
              <?php endfor; ?>
            </div>
          </div>
        </div>

        <!-- Quick Actions -->
        <div class="content-card">
          <div class="card-header">
            <div>
              <h3 class="card-title">Quick Actions</h3>
              <p class="card-subtitle">Go to modules</p>
            </div>
          </div>
          <div class="card-body">
            <div class="quick-actions">
              <button class="action-btn"><i data-lucide="calendar"></i><span>Weekly Roster</span></button>
              <button class="action-btn"><i data-lucide="file-text"></i><span>Timesheets</span></button>
              <button class="action-btn"><i data-lucide="calendar-clock"></i><span>Leave Requests</span></button>
              <button class="action-btn"><i data-lucide="receipt"></i><span>Claims</span></button>
              <button class="action-btn"><i data-lucide="scan-face"></i><span>Attendance</span></button>
              <button class="action-btn"><i data-lucide="users"></i><span>Employees</span></button>
            </div>
          </div>
        </div>

      </div>

      <!-- Bottom Grid -->
      <div class="bottom-grid">

        <!-- Left Bottom -->
        <div class="content-card">
          <div class="card-header">
            <div>
              <h3 class="card-title">Upcoming Holidays</h3>
              <p class="card-subtitle">From holidays table</p>
            </div>
            <button class="btn-text">View</button>
          </div>
          <div class="card-body">
            <div class="payment-list">
              <!-- ✅ replaced by JS -->
              <?php for ($i=1; $i<=3; $i++): ?>
              <div class="payment-item">
                <div class="payment-date">
                  <span class="date-day">—</span>
                  <span class="date-month">—</span>
                </div>
                <div class="payment-details">
                  <span class="payment-client">Loading...</span>
                  <span class="payment-type">Holiday</span>
                </div>
                <div class="payment-amount">—</div>
              </div>
              <?php endfor; ?>
            </div>
          </div>
        </div>

        <!-- Right Bottom -->
        <div class="content-card">
          <div class="card-header">
            <div>
              <h3 class="card-title">Recent Activity</h3>
              <p class="card-subtitle">System feed</p>
            </div>
          </div>
          <div class="card-body">
            <div class="activity-list">
              <!-- ✅ replaced by JS -->
              <?php for ($i=1; $i<=4; $i++): ?>
              <div class="activity-item">
                <div class="activity-icon" style="background: rgba(44, 160, 120, 0.1); color: var(--brand-green);">
                  <i data-lucide="check-circle"></i>
                </div>
                <div class="activity-content">
                  <p class="activity-text">Loading...</p>
                  <span class="activity-time">—</span>
                </div>
              </div>
              <?php endfor; ?>
            </div>
          </div>
        </div>

      </div>

    </div>
  </main>

  
  
  <script src="../../js/officer/dashboard.js?v=<?php echo time(); ?>"></script> <!-- ✅ will fetch dashboard_data.php -->


  <script> lucide.createIcons(); </script>
</body>
</html>


