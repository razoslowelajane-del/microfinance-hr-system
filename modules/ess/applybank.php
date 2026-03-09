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
  <title>Apply Bank Account</title>
  <link rel="stylesheet" href="../../css/applybank.css?v=1.3">
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

        <a href="dashboard.php" class="nav-item">
          <i data-lucide="chart-no-axes-combined"></i>
          <span>Dashboard</span>
        </a>
        <a href="#" class="nav-item">
          <i data-lucide="file-clock"></i>
          <span>Time Attendance</span>
        </a>
        <a href="information_management.php" class="nav-item">
          <i data-lucide="user-pen"></i>
          <span>Information Management</span>
        </a>
        <a href="applybank.php" class="nav-item active">
          <i data-lucide="landmark"></i>
          <span>Apply Bank Account</span>
        </a>
        <a href="#" class="nav-item">
          <i data-lucide="tickets-plane"></i>
          <span>Leave Management</span>
        </a>
        <a href="#" class="nav-item">
          <i data-lucide="receipt-text"></i>
          <span>Claim Management</span>
        </a>
        <a href="#" class="nav-item">
          <i data-lucide="ticket-check"></i>
          <span>View Payslip</span>
        </a>
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
          <span class="user-role"><?php echo htmlspecialchars($_SESSION['user_role'] ?? 'Employee'); ?></span>
        </div>
        <button class="user-menu-btn" id="userMenuBtn">
          <i data-lucide="more-vertical"></i>
        </button>
        <!-- User dropdown -->
        <div class="user-menu-dropdown" id="userMenuDropdown">
          <div class="umd-header">
            <div class="umd-avatar"><?php echo strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1)); ?></div>
            <div class="umd-info">
              <span class="umd-signed">Signed in as</span>
              <span class="umd-name"><?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></span>
              <span class="umd-role"><?php echo htmlspecialchars($_SESSION['user_role'] ?? 'Employee'); ?></span>
            </div>
          </div>
          <div class="umd-divider"></div>
          <a href="profile.php" class="umd-item">
            <i data-lucide="user-round"></i>
            <span>Profile</span>
          </a>
          <div class="umd-divider"></div>
          <a href="../../login.php" class="umd-item umd-item-danger">
            <i data-lucide="log-out"></i>
            <span>Sign Out</span>
          </a>
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
          <h1>Apply Bank Account</h1>
          <p>Download the form, fill it out offline, then upload it here.</p>
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

// Active master form
$activeRes  = $conn->query("SELECT * FROM bank_forms_master WHERE IsActive = 1 LIMIT 1");
$activeForm = $activeRes ? $activeRes->fetch_assoc() : null;

// This employee's own submissions
$un   = $conn->real_escape_string($_SESSION['username']);
$empRes = $conn->query("SELECT e.EmployeeID FROM useraccounts ua LEFT JOIN employee e ON ua.EmployeeID = e.EmployeeID WHERE ua.Username = '$un' LIMIT 1");
$employeeId = $empRes && ($row = $empRes->fetch_assoc()) ? intval($row['EmployeeID']) : 0;

$myApps = [];
if ($employeeId) {
    $appRes = $conn->query("
      SELECT ba.*, bfm.FormName
      FROM bank_applications ba
      LEFT JOIN bank_forms_master bfm ON ba.FormID = bfm.FormID
      WHERE ba.EmployeeID = $employeeId
      ORDER BY ba.CreatedAt DESC
    ");
    if ($appRes) while ($r = $appRes->fetch_assoc()) $myApps[] = $r;
}
?>

    <div class="content-wrapper">

      <!-- How it works banner -->
      <div class="ab-banner">
        <div class="ab-banner-icon"><i data-lucide="info"></i></div>
        <div>
          <strong>How it works:</strong>
          <ol class="ab-steps">
            <li>Download the active form below.</li>
            <li>Fill it out and sign it.</li>
            <li>Upload the completed PDF using the form below.</li>
            <li>HR will forward it to the bank and update your status.</li>
          </ol>
        </div>
      </div>

      <!-- ══ Step 1: Download ══ -->
      <div class="ab-section-title"><span class="ab-step-num">1</span> Download the Form</div>

      <?php if ($activeForm): ?>
      <div class="ab-download-card">
        <div class="ab-download-left">
          <div class="ab-pdf-icon"><i data-lucide="file-text"></i></div>
          <div class="ab-download-info">
            <div class="ab-download-name"><?php echo htmlspecialchars($activeForm['FormName']); ?></div>
            <div class="ab-download-meta">PDF &bull; Uploaded <?php echo date('M d, Y', strtotime($activeForm['CreatedAt'])); ?></div>
          </div>
        </div>
        <div class="ab-download-actions">
          <a href="../../<?php echo htmlspecialchars($activeForm['FilePath']); ?>" target="_blank" class="ab-btn-view">
            <i data-lucide="eye"></i> Preview
          </a>
          <a href="../../<?php echo htmlspecialchars($activeForm['FilePath']); ?>" download class="ab-btn-download">
            <i data-lucide="download"></i> Download
          </a>
        </div>
      </div>
      <?php else: ?>
      <div class="ab-empty">
        <div class="ab-empty-icon"><i data-lucide="file-clock"></i></div>
        <h3>No form available yet</h3>
        <p>HR has not uploaded a bank form yet. Please check back later or contact your HR Data Specialist.</p>
      </div>
      <?php endif; ?>

      <!-- ══ Step 2: Upload ══ -->
      <div class="ab-section-title"><span class="ab-step-num">2</span> Upload Your Completed Form</div>

      <div class="ab-upload-card">
        <form id="submitForm" enctype="multipart/form-data">
          <?php if ($activeForm): ?>
          <input type="hidden" name="form_id" value="<?php echo $activeForm['FormID']; ?>">
          <?php endif; ?>
          <div class="ab-drop-zone" id="abDropZone">
            <input type="file" name="filled_pdf" id="filledPdf" accept=".pdf" required>
            <div class="ab-drop-content" id="abDropContent">
              <i data-lucide="upload-cloud"></i>
              <span class="ab-drop-main">Drag &amp; drop your completed PDF here</span>
              <span class="ab-drop-sub">or click to browse &mdash; max 15 MB</span>
            </div>
          </div>
          <div class="ab-file-preview" id="abFilePreview" style="display:none;"></div>
          <div class="ab-submit-row">
            <span class="ab-submit-note"><i data-lucide="shield-check"></i> Your file is securely stored and only visible to HR.</span>
            <button type="submit" class="ab-btn-submit" id="submitBtn">
              <i data-lucide="send"></i> Submit Form
            </button>
          </div>
        </form>
      </div>

      <!-- ══ My Submissions ══ -->
      <?php if (!empty($myApps)): ?>
      <div class="ab-section-title" style="margin-top:32px">
        <span class="ab-step-num"><i data-lucide="list-checks"></i></span> My Submissions
      </div>
      <div class="ab-submissions-list">
        <?php foreach ($myApps as $app):
          $pdfUrl = '../../' . $app['UploadedPDF'];
          $statusIcon  = match($app['Status']) {
            'Confirmed'    => 'badge-check',
            'Sent to Bank' => 'send',
            default        => 'clock',
          };
          $statusColor = match($app['Status']) {
            'Confirmed'    => 'confirmed',
            'Sent to Bank' => 'sent',
            default        => 'pending',
          };
          $statusDesc  = match($app['Status']) {
            'Confirmed'    => 'Your bank account has been confirmed and recorded.',
            'Sent to Bank' => 'Your form has been forwarded to BDO by HR.',
            default        => 'Awaiting HR review and processing.',
          };
        ?>
        <div class="ab-sub-card ab-sub-<?php echo $statusColor; ?>">
          <div class="ab-sub-icon-col">
            <div class="ab-sub-status-icon">
              <i data-lucide="<?php echo $statusIcon; ?>"></i>
            </div>
            <div class="ab-sub-line"></div>
          </div>
          <div class="ab-sub-content">
            <div class="ab-sub-top">
              <div class="ab-sub-form-name"><?php echo htmlspecialchars($app['FormName'] ?? 'BDO Bank Form'); ?></div>
              <span class="ab-sub-badge ab-sub-badge-<?php echo $statusColor; ?>"><?php echo htmlspecialchars($app['Status']); ?></span>
            </div>
            <div class="ab-sub-desc"><?php echo $statusDesc; ?></div>
            <div class="ab-sub-footer">
              <span class="ab-sub-date"><i data-lucide="calendar"></i> <?php echo date('M d, Y \a\t h:i A', strtotime($app['CreatedAt'])); ?></span>
              <a href="<?php echo htmlspecialchars($pdfUrl); ?>" target="_blank" class="ab-sub-view-btn">
                <i data-lucide="file-down"></i> View PDF
              </a>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>


    </div><!-- /.content-wrapper -->
  </main>

  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="../../js/user-menu.js"></script>
  <script src="../../js/applybank.js?v=<?php echo time(); ?>"></script>
  <script>
    lucide.createIcons();
    const _st = document.getElementById('sidebarToggle');
    const _sb = document.getElementById('sidebar');
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
