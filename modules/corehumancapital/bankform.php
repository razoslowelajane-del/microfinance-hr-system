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
  <title>Bank Form Management</title>
  <link rel="stylesheet" href="../../css/bankform.css?v=1.3">
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

        <div class="nav-item-group active">
          <button class="nav-item has-submenu active" data-module="hr">
            <div class="nav-item-content">
              <i data-lucide="book-user"></i>
              <span>Core Human Capital</span>
            </div>
            <i data-lucide="chevron-down" class="submenu-icon"></i>
          </button>
          <div class="submenu active" id="submenu-hr">
            <a href="" class="submenu-item">
              <i data-lucide="user-plus"></i>
              <span>New Hired Onboard Request</span>
            </a>
            <a href="employeemaster.php" class="submenu-item">
              <i data-lucide="file-user"></i>
              <span>Employee Master Files</span>
            </a>
            <a href="informationrq.php" class="submenu-item">
              <i data-lucide="user-round-pen"></i>
              <span>Information Request</span>
            </a>
            <a href="bankform.php" class="submenu-item active">
              <i data-lucide="file-text"></i>
              <span>Bank Form Management</span>
            </a>
            <a href="" class="submenu-item">
              <i data-lucide="user-cog"></i>
              <span>Security Settings</span>
            </a>
            <a href="auditlogs.php" class="submenu-item">
              <i data-lucide="book-user"></i>
              <span>Audit Logs</span>
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
            <a href="#" class="submenu-item"><i data-lucide="calendar-clock"></i><span>Disbursements</span></a>
            <a href="#" class="submenu-item"><i data-lucide="coins"></i><span>Collections</span></a>
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
            <a href="#" class="submenu-item"><i data-lucide="calendar-clock"></i><span>Disbursements</span></a>
            <a href="#" class="submenu-item"><i data-lucide="coins"></i><span>Collections</span></a>
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
          <span class="user-name"><?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></span>
          <span class="user-role"><?php echo htmlspecialchars($_SESSION['user_role'] ?? 'HR Specialist'); ?></span>
        </div>
        <button class="user-menu-btn" id="userMenuBtn">
          <i data-lucide="more-vertical"></i>
        </button>
        <div class="user-menu-dropdown" id="userMenuDropdown">
          <div class="umd-header">
            <div class="umd-avatar"><?php echo strtoupper(substr($_SESSION['username'] ?? 'H', 0, 1)); ?></div>
            <div class="umd-info">
              <span class="umd-signed">Signed in as</span>
              <span class="umd-name"><?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></span>
              <span class="umd-role"><?php echo htmlspecialchars($_SESSION['user_role'] ?? 'HR Specialist'); ?></span>
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
          <h1>Bank Form Management</h1>
          <p>Upload the master blank PDF and track employee submissions.</p>
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

<?php
require_once '../../config/config.php';

$masterRes  = $conn->query("SELECT * FROM bank_forms_master ORDER BY CreatedAt DESC");
$masters    = [];
if ($masterRes) while ($r = $masterRes->fetch_assoc()) $masters[] = $r;
$activeForm = null;
foreach ($masters as $m) { if ($m['IsActive']) { $activeForm = $m; break; } }

$pendingRes = $conn->query("SELECT COUNT(*) AS cnt FROM bank_applications WHERE Status='Pending'");
$pendingCnt = $pendingRes ? intval($pendingRes->fetch_assoc()['cnt']) : 0;


?>

    <div class="content-wrapper">

      <!-- ══ Stats ══ -->
      <div class="bf-stats">
        <div class="bf-stat-card">
          <div class="bf-stat-icon blue"><i data-lucide="file-text"></i></div>
          <div class="bf-stat-info">
            <span class="bf-stat-value"><?php echo count($masters); ?></span>
            <span class="bf-stat-label">Master Forms</span>
          </div>
        </div>
        <div class="bf-stat-card">
          <div class="bf-stat-icon green"><i data-lucide="check-circle"></i></div>
          <div class="bf-stat-info">
            <span class="bf-stat-value"><?php echo $activeForm ? '1' : '0'; ?></span>
            <span class="bf-stat-label">Active Form</span>
          </div>
        </div>
        <div class="bf-stat-card">
          <div class="bf-stat-icon amber"><i data-lucide="clock"></i></div>
          <div class="bf-stat-info">
            <span class="bf-stat-value"><?php echo $pendingCnt; ?></span>
            <span class="bf-stat-label">Pending Submissions</span>
          </div>
        </div>
      </div>

      <!-- ══ Master Forms Panel ══ -->
      <div class="bf-panel">
        <div class="bf-panel-header">
          <div class="bf-panel-left">
            <div class="bf-panel-icon"><i data-lucide="upload-cloud"></i></div>
            <div>
              <h2 class="bf-panel-title">Master Bank Forms</h2>
              <div class="bf-panel-sub">Only the <strong>Active</strong> form is shown to employees for download</div>
            </div>
          </div>
          <button class="bf-btn-primary" id="openUploadBtn">
            <i data-lucide="upload"></i> Upload New Form
          </button>
        </div>

        <?php if (empty($masters)): ?>
        <div class="bf-empty">
          <div class="bf-empty-icon"><i data-lucide="file-plus"></i></div>
          <h3>No forms yet</h3>
          <p>Upload the blank bank enrollment PDF. Employees will download and fill it out.</p>
        </div>
        <?php else: ?>
        <div class="table-responsive">
          <table class="bf-table">
            <thead>
              <tr>
                <th>Form</th>
                <th>Uploaded By</th>
                <th>Date</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($masters as $m):
                $ini = strtoupper(substr($m['FormName'], 0, 2));
                $url = '../../' . $m['FilePath'];
              ?>
              <tr>
                <td>
                  <div class="bf-bank-cell">
                    <div class="bf-bank-avatar<?php echo $m['IsActive'] ? ' bf-av-active' : ''; ?>"><?php echo htmlspecialchars($ini); ?></div>
                    <div>
                      <div class="bf-bank-name"><?php echo htmlspecialchars($m['FormName']); ?></div>
                      <div class="bf-form-sub">ID #<?php echo $m['FormID']; ?></div>
                    </div>
                  </div>
                </td>
                <td><?php echo htmlspecialchars($m['UploadedBy'] ?? '—'); ?></td>
                <td><?php echo date('M d, Y', strtotime($m['CreatedAt'])); ?></td>
                <td>
                  <span class="bf-badge <?php echo $m['IsActive'] ? 'bf-badge-active' : 'bf-badge-archived'; ?>">
                    <?php echo $m['IsActive'] ? 'Active' : 'Archived'; ?>
                  </span>
                </td>
                <td>
                  <div class="bf-actions">
                    <a href="<?php echo htmlspecialchars($url); ?>" target="_blank" class="bf-btn-view">
                      <i data-lucide="eye"></i> View
                    </a>
                    <?php if (!$m['IsActive']): ?>
                    <button class="bf-btn-setactive" data-id="<?php echo $m['FormID']; ?>">
                      <i data-lucide="check"></i> Set Active
                    </button>
                    <?php endif; ?>
                    <button class="bf-btn-delete" data-id="<?php echo $m['FormID']; ?>">
                      <i data-lucide="trash-2"></i>
                    </button>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>



      <!-- ══ Upload Modal ══ -->
      <div id="uploadModal" class="bf-modal" aria-hidden="true">
        <div class="bf-modal-dialog">
          <div class="bf-modal-hero">
            <div class="bf-modal-hero-inner">
              <div class="bf-modal-hero-icon"><i data-lucide="upload-cloud"></i></div>
              <div class="bf-modal-hero-text">
                <h3>Upload Master Form</h3>
                <p>Uploading a new form will archive the current active form and set this as the new active one.</p>
              </div>
              <button class="bf-close-btn" id="closeUploadModal">&times;</button>
            </div>
          </div>
          <form id="uploadForm" enctype="multipart/form-data">
            <div class="bf-modal-body">
              <div class="bf-form-row">
                <label>Form Name <span class="required">*</span></label>
                <input type="text" name="form_name" id="formName" placeholder="e.g. BDO Account Opening Form 2025" required>
              </div>
              <div class="bf-form-row">
                <label>PDF File <span class="required">*</span></label>
                <div class="bf-drop-zone" id="dropZone">
                  <input type="file" name="pdf_file" id="pdfFile" accept=".pdf" required>
                  <div class="bf-drop-content" id="dropContent">
                    <i data-lucide="file-up"></i>
                    <span class="bf-drop-main">Drag &amp; drop PDF here</span>
                    <span class="bf-drop-sub">or click to browse &mdash; max 10 MB</span>
                  </div>
                </div>
                <div class="bf-file-preview" id="filePreview" style="display:none;"></div>
              </div>
            </div>
            <div class="bf-modal-footer">
              <button type="button" id="cancelUpload" class="bf-btn-cancel">Cancel</button>
              <button type="submit" class="bf-btn-submit">
                <i data-lucide="upload"></i> Upload &amp; Set Active
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
  <script src="../../js/bankform.js?v=<?php echo time(); ?>"></script>
  <script>
    lucide.createIcons();
    // Sidebar toggle
    const _st = document.getElementById('sidebarToggle');
    const _sb = document.getElementById('sidebar');
    if (_st && _sb) {
      _st.addEventListener('click', () => {
        _sb.classList.toggle('collapsed');
        localStorage.setItem('sidebarCollapsed', _sb.classList.contains('collapsed'));
      });
      if (localStorage.getItem('sidebarCollapsed') === 'true') _sb.classList.add('collapsed');
    }
    // Dark mode
    const _tt = document.getElementById('themeToggle');
    if (_tt) {
      if (localStorage.getItem('theme') === 'dark') document.body.classList.add('dark-mode');
      _tt.addEventListener('click', () => {
        document.body.classList.toggle('dark-mode');
        localStorage.setItem('theme', document.body.classList.contains('dark-mode') ? 'dark' : 'light');
      });
    }
    // User menu dropdown + sign-out handled by user-menu.js
  </script>
</body>
</html>
