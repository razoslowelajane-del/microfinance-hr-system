<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: ../../login.php");
    exit();
}
require_once '../../config/config.php';

// Stats
$pendingCnt = intval($conn->query("SELECT COUNT(*) FROM bank_applications WHERE Status='Pending'")->fetch_row()[0] ?? 0);
$sentCnt    = intval($conn->query("SELECT COUNT(*) FROM bank_applications WHERE Status='Sent to Bank'")->fetch_row()[0] ?? 0);
$doneCnt    = intval($conn->query("SELECT COUNT(*) FROM bank_applications WHERE Status='Confirmed'")->fetch_row()[0] ?? 0);

// All submissions
$subsRes = $conn->query("
  SELECT ba.AppID, ba.EmployeeID, ba.UploadedPDF, ba.Status, ba.CreatedAt,
         e.FirstName, e.LastName, e.EmployeeCode,
         bfm.FormName
  FROM bank_applications ba
  LEFT JOIN employee e ON ba.EmployeeID = e.EmployeeID
  LEFT JOIN bank_forms_master bfm ON ba.FormID = bfm.FormID
  ORDER BY FIELD(ba.Status,'Pending','Sent to Bank','Confirmed'), ba.CreatedAt DESC
");
$submissions = [];
if ($subsRes) while ($r = $subsRes->fetch_assoc()) $submissions[] = $r;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Bank Verification</title>
  <link rel="stylesheet" href="../../css/bankverification.css?v=1.3">
  <link rel="stylesheet" href="../../css/sidebar-fix.css?v=1.0">
  <script src="https://unpkg.com/lucide@latest"></script>
  <link rel="icon" type="image/png" href="../../img/logo.png">
</head>
<body>

  <!-- Sidebar — same structure & CSS as bankform.php -->
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

        <a href="dashboard.php" class="nav-item">
          <i data-lucide="chart-no-axes-combined"></i>
          <span>HR Analytics</span>
        </a>

        <div class="nav-item-group active">
          <button class="nav-item has-submenu active" data-module="banking">
            <div class="nav-item-content">
              <i data-lucide="landmark"></i>
              <span>Banking</span>
            </div>
            <i data-lucide="chevron-down" class="submenu-icon"></i>
          </button>
          <div class="submenu active" id="submenu-banking">
            <a href="Bankverification.php" class="submenu-item active">
              <i data-lucide="shield-check"></i>
              <span>Bank Verification</span>
            </a>
          </div>
        </div>

        <div class="nav-item-group">
          <button class="nav-item has-submenu" data-module="planning">
            <div class="nav-item-content">
              <i data-lucide="circle-pile"></i>
              <span>Compensation Planning</span>
            </div>
            <i data-lucide="chevron-down" class="submenu-icon"></i>
          </button>
          <div class="submenu" id="submenu-planning">
            <a href="#" class="submenu-item"><i data-lucide="file-plus"></i><span>Applications</span></a>
            <a href="#" class="submenu-item"><i data-lucide="check-circle"></i><span>Approvals</span></a>
          </div>
        </div>

        <div class="nav-item-group">
          <button class="nav-item has-submenu" data-module="payroll">
            <div class="nav-item-content">
              <i data-lucide="banknote-arrow-down"></i>
              <span>Payroll</span>
            </div>
            <i data-lucide="chevron-down" class="submenu-icon"></i>
          </button>
          <div class="submenu" id="submenu-payroll">
            <a href="#" class="submenu-item"><i data-lucide="file-plus"></i><span>Applications</span></a>
            <a href="#" class="submenu-item"><i data-lucide="check-circle"></i><span>Approvals</span></a>
          </div>
        </div>
      </div>

      <div class="nav-section">
        <span class="nav-section-title">SETTINGS</span>
        <a href="#" class="nav-item"><i data-lucide="settings"></i><span>Configuration</span></a>
        <a href="#" class="nav-item"><i data-lucide="shield"></i><span>Security</span></a>
      </div>
    </nav>

    <div class="sidebar-footer">
      <div class="user-profile">
        <div class="user-avatar">
          <img src="../../img/profile.png" alt="User">
        </div>
        <div class="user-info">
          <span class="user-name"><?php echo htmlspecialchars($_SESSION['username'] ?? 'Manager'); ?></span>
          <span class="user-role"><?php echo htmlspecialchars($_SESSION['user_role'] ?? 'HR Manager'); ?></span>
        </div>
        <button class="user-menu-btn" id="userMenuBtn"><i data-lucide="more-vertical"></i></button>
        <div class="user-menu-dropdown" id="userMenuDropdown">
          <div class="umd-header">
            <div class="umd-avatar"><?php echo strtoupper(substr($_SESSION['username'] ?? 'M', 0, 1)); ?></div>
            <div class="umd-info">
              <span class="umd-signed">Signed in as</span>
              <span class="umd-name"><?php echo htmlspecialchars($_SESSION['username'] ?? 'Manager'); ?></span>
              <span class="umd-role"><?php echo htmlspecialchars($_SESSION['user_role'] ?? 'HR Manager'); ?></span>
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
        <button class="mobile-menu-btn" id="mobileMenuBtn"><i data-lucide="menu"></i></button>
        <div class="header-title">
          <h1>Bank Verification</h1>
          <p>Review employee submissions, mark as Sent to Bank, and record confirmed account details.</p>
        </div>
      </div>
      <div class="header-right">
        <div class="search-box">
          <i data-lucide="search"></i>
          <input type="search" id="tableSearch" placeholder="Search employee...">
        </div>
        <button class="theme-toggle" id="themeToggle" aria-label="Toggle theme">
          <i data-lucide="sun" class="sun-icon"></i>
          <i data-lucide="moon" class="moon-icon"></i>
        </button>
        <button class="icon-btn"><i data-lucide="bell"></i></button>
      </div>
    </header>

    <div class="content-wrapper">

      <!-- Stats -->
      <div class="bv-stats">
        <div class="bv-stat-card">
          <div class="bv-stat-icon amber"><i data-lucide="clock"></i></div>
          <div class="bv-stat-info">
            <span class="bv-stat-value"><?php echo $pendingCnt; ?></span>
            <span class="bv-stat-label">Pending Review</span>
          </div>
        </div>
        <div class="bv-stat-card">
          <div class="bv-stat-icon blue"><i data-lucide="send"></i></div>
          <div class="bv-stat-info">
            <span class="bv-stat-value"><?php echo $sentCnt; ?></span>
            <span class="bv-stat-label">Sent to Bank</span>
          </div>
        </div>
        <div class="bv-stat-card">
          <div class="bv-stat-icon green"><i data-lucide="check-circle"></i></div>
          <div class="bv-stat-info">
            <span class="bv-stat-value"><?php echo $doneCnt; ?></span>
            <span class="bv-stat-label">Confirmed</span>
          </div>
        </div>
      </div>

      <!-- Submissions Panel -->
      <div class="bv-panel">
        <div class="bv-panel-header">
          <div class="bv-panel-left">
            <div class="bv-panel-icon"><i data-lucide="inbox"></i></div>
            <div>
              <h2 class="bv-panel-title">Employee Bank Form Submissions</h2>
              <div class="bv-panel-sub"><?php echo count($submissions); ?> total submission<?php echo count($submissions) !== 1 ? 's' : ''; ?></div>
            </div>
          </div>
          <div class="bv-filter-tabs">
            <button class="bv-tab active" data-filter="all">All</button>
            <button class="bv-tab" data-filter="Pending">Pending</button>
            <button class="bv-tab" data-filter="Sent to Bank">Sent to Bank</button>
            <button class="bv-tab" data-filter="Confirmed">Confirmed</button>
          </div>
        </div>

        <?php if (empty($submissions)): ?>
        <div class="bv-empty">
          <div class="bv-empty-icon"><i data-lucide="inbox"></i></div>
          <h3>No submissions yet</h3>
          <p>Employee-submitted bank forms will appear here once they upload them from the ESS portal.</p>
        </div>
        <?php else: ?>
        <div class="table-responsive">
          <table class="bv-table" id="submissionsTable">
            <thead>
              <tr>
                <th>Employee</th>
                <th>Form</th>
                <th>Submitted</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($submissions as $sub):
                $name = trim(($sub['FirstName'] ?? '') . ' ' . ($sub['LastName'] ?? '')) ?: ('Employee #' . $sub['EmployeeID']);
                $code = $sub['EmployeeCode'] ?? '';
                $pdfUrl = '../../' . $sub['UploadedPDF'];
                $statusClass = match($sub['Status']) {
                  'Confirmed'    => 'bv-badge-confirmed',
                  'Sent to Bank' => 'bv-badge-sent',
                  default        => 'bv-badge-pending',
                };
                $ini = strtoupper(substr(trim($sub['FirstName'] ?? $name), 0, 1) . substr(trim($sub['LastName'] ?? ''), 0, 1));
              ?>
              <tr data-status="<?php echo htmlspecialchars($sub['Status']); ?>"
                  data-search="<?php echo strtolower(htmlspecialchars($name . ' ' . $code)); ?>">
                <td>
                  <div class="bv-emp-cell">
                    <div class="bv-emp-avatar"><?php echo $ini ?: strtoupper(substr($name,0,2)); ?></div>
                    <div>
                      <div class="bv-emp-name"><?php echo htmlspecialchars($name); ?></div>
                      <?php if ($code): ?><div class="bv-emp-code"><?php echo htmlspecialchars($code); ?></div><?php endif; ?>
                    </div>
                  </div>
                </td>
                <td><?php echo htmlspecialchars($sub['FormName'] ?? 'BDO Bank Form'); ?></td>
                <td><?php echo date('M d, Y', strtotime($sub['CreatedAt'])); ?></td>
                <td><span class="bv-badge <?php echo $statusClass; ?>"><?php echo htmlspecialchars($sub['Status']); ?></span></td>
                <td>
                  <div class="bv-actions">
                    <a href="<?php echo htmlspecialchars($pdfUrl); ?>" target="_blank" class="bv-btn-view">
                      <i data-lucide="file-down"></i> View PDF
                    </a>
                    <?php if ($sub['Status'] === 'Pending'): ?>
                    <button class="bv-btn-send"
                            data-app-id="<?php echo $sub['AppID']; ?>"
                            data-emp-name="<?php echo htmlspecialchars($name); ?>">
                      <i data-lucide="send"></i> Sent to Bank
                    </button>
                    <?php endif; ?>
                    <?php if ($sub['Status'] === 'Sent to Bank'): ?>
                    <button class="bv-btn-confirm"
                            data-app-id="<?php echo $sub['AppID']; ?>"
                            data-emp-id="<?php echo $sub['EmployeeID']; ?>"
                            data-emp-name="<?php echo htmlspecialchars($name); ?>">
                      <i data-lucide="check-circle"></i> Mark Confirmed
                    </button>
                    <?php endif; ?>
                    <?php if ($sub['Status'] === 'Confirmed'): ?>
                    <span class="bv-done-label"><i data-lucide="badge-check"></i> Account Recorded</span>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>

      <!-- Confirm Modal -->
      <div id="confirmModal" class="bv-modal" aria-hidden="true">
        <div class="bv-modal-dialog">
          <div class="bv-modal-hero">
            <div class="bv-modal-hero-inner">
              <div class="bv-modal-hero-icon"><i data-lucide="badge-check"></i></div>
              <div class="bv-modal-hero-text">
                <h3>Record Bank Account</h3>
                <p>Enter the account details received from the bank's confirmation email.</p>
              </div>
              <button class="bv-close-btn" id="closeConfirmModal">&times;</button>
            </div>
          </div>
          <form id="confirmForm">
            <input type="hidden" id="confirmAppId" name="app_id">
            <input type="hidden" id="confirmEmpId" name="employee_id">
            <div class="bv-modal-body">
              <div class="bv-modal-emp-badge" id="confirmEmpBadge"></div>
              <div class="bv-form-row">
                <label>Bank Name</label>
                <div class="bv-fixed-field">
                  <i data-lucide="landmark"></i>
                  <span>BDO</span>
                  <i data-lucide="lock" class="bv-lock-icon"></i>
                </div>
                <input type="hidden" name="bank_name" value="BDO">
              </div>
              <div class="bv-form-row">
                <label>Account Number <span class="required">*</span></label>
                <input type="text" name="account_number" id="accountNumberInput" placeholder="e.g. 001234567890" required>
              </div>
              <div class="bv-form-row">
                <label>Account Type</label>
                <div class="bv-fixed-field">
                  <i data-lucide="wallet"></i>
                  <span>Payroll</span>
                  <i data-lucide="lock" class="bv-lock-icon"></i>
                </div>
                <input type="hidden" name="account_type" value="Payroll">
              </div>
            </div>
            <div class="bv-modal-footer">
              <button type="button" id="cancelConfirm" class="bv-btn-cancel">Cancel</button>
              <button type="submit" class="bv-btn-submit">
                <i data-lucide="save"></i> Save &amp; Confirm
              </button>
            </div>
          </form>
        </div>
      </div>

    </div><!-- /.content-wrapper -->
  </main>

  <script src="../../js/sidebar-active.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="../../js/user-menu.js"></script>
  <script src="../../js/bankverification.js?v=<?php echo time(); ?>"></script>
  <script>
    lucide.createIcons();
    // Sidebar toggle
    const _st = document.getElementById('sidebarToggle'), _sb = document.getElementById('sidebar');
    if (_st && _sb) {
      _st.addEventListener('click', () => { _sb.classList.toggle('collapsed'); localStorage.setItem('sidebarCollapsed', _sb.classList.contains('collapsed')); });
      if (localStorage.getItem('sidebarCollapsed') === 'true') _sb.classList.add('collapsed');
    }
    // Dark mode
    const _tt = document.getElementById('themeToggle');
    if (_tt) {
      if (localStorage.getItem('theme') === 'dark') document.body.classList.add('dark-mode');
      _tt.addEventListener('click', () => { document.body.classList.toggle('dark-mode'); localStorage.setItem('theme', document.body.classList.contains('dark-mode') ? 'dark' : 'light'); });
    }
    // User menu dropdown + sign-out handled by user-menu.js
  </script>
</body>
</html>
