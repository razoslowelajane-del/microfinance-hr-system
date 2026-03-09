
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
  <title>Information Management</title>
  <link rel="stylesheet" href="../../css/informationmanagement.css?v=2.0">
  <link rel="stylesheet" href="../../css/sidebar-fix.css?v=1.0">
  <script src="https://unpkg.com/lucide@latest"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
        <a href="information_management.php" class="nav-item active">
          <i data-lucide="user-pen"></i>
          <span>Information Management</span>
        </a>
         <a href="applybank.php" class="nav-item">
              <i data-lucide="landmark"></i>
              <span>Apply Bank Account</span>
        </a>
        <a href="leave_apply.php" class="nav-item">
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
          <h1>Information Management</h1>
          <p>View and manage your personal profile details.</p>
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

      <!-- Hero Profile Banner -->
      <div class="im-hero">
        <div class="im-hero-inner">
          <div class="im-hero-left">
            <div class="im-avatar" id="avatarPlaceholder"></div>
            <div class="im-hero-info">
              <h2 class="im-hero-name" id="employeeName">Loading…</h2>
              <p class="im-hero-position" id="employeePosition">Loading…</p>
              <div class="im-hero-chips">
                <span class="im-chip">
                  <i data-lucide="building-2"></i>
                  <span id="employeeDepartment">Department</span>
                </span>
                <span class="im-chip">
                  <i data-lucide="hash"></i>
                  <span id="employeeCode">Code</span>
                </span>
              </div>
            </div>
          </div>
          <div class="im-hero-actions">
            <button type="submit" form="myInfoForm" class="im-btn-save">
              <i data-lucide="save"></i> Save Changes
            </button>
            <button type="button" class="im-btn-request" id="btnRequestEdit">
              <i data-lucide="pencil"></i> Request Edit
            </button>
          </div>
        </div>
      </div>

      <!-- Info Sections Grid -->
      <form id="myInfoForm">
        <div class="im-grid">

          <!-- Personal Information -->
          <div class="im-card">
            <div class="im-card-hdr im-hdr-blue">
              <div class="im-card-hdr-left">
                <i data-lucide="user"></i> Personal Information
              </div>
              <span class="im-badge-editable"><i data-lucide="pencil-line"></i> Editable</span>
            </div>
            <div class="im-fields">
              <div class="im-field">
                <label>First Name</label>
                <input type="text" name="FirstName" id="FirstName" class="im-input" required>
              </div>
              <div class="im-field">
                <label>Last Name</label>
                <input type="text" name="LastName" id="LastName" class="im-input" required>
              </div>
              <div class="im-field">
                <label>Middle Name</label>
                <input type="text" name="MiddleName" id="MiddleName" class="im-input">
              </div>
              <div class="im-field">
                <label>Date of Birth</label>
                <input type="date" name="DateOfBirth" id="DateOfBirth" class="im-input" readonly>
              </div>
              <div class="im-field">
                <label>Gender</label>
                <select name="Gender" id="Gender" class="im-input">
                  <option value="Male">Male</option>
                  <option value="Female">Female</option>
                </select>
              </div>
              <div class="im-field full" style="grid-column:1/-1">
                <label>Permanent Address</label>
                <input type="text" name="PermanentAddress" id="PermanentAddress" class="im-input">
              </div>
            </div>
          </div>

          <!-- Contact Information -->
          <div class="im-card">
            <div class="im-card-hdr im-hdr-green">
              <div class="im-card-hdr-left">
                <i data-lucide="phone"></i> Contact Information
              </div>
              <span class="im-badge-editable"><i data-lucide="pencil-line"></i> Editable</span>
            </div>
            <div class="im-fields">
              <div class="im-field">
                <label>Phone Number</label>
                <input type="text" name="PhoneNumber" id="PhoneNumber" class="im-input">
              </div>
              <div class="im-field">
                <label>Personal Email</label>
                <input type="email" name="PersonalEmail" id="PersonalEmail" class="im-input">
              </div>
            </div>
          </div>

          <!-- Emergency Contact -->
          <div class="im-card">
            <div class="im-card-hdr im-hdr-red">
              <div class="im-card-hdr-left">
                <i data-lucide="phone-call"></i> Emergency Contact
              </div>
              <span class="im-badge-editable"><i data-lucide="pencil-line"></i> Editable</span>
            </div>
            <div class="im-fields">
              <div class="im-field">
                <label>Contact Name</label>
                <input type="text" name="ContactName" id="ContactName" class="im-input">
              </div>
              <div class="im-field">
                <label>Relationship</label>
                <input type="text" name="Relationship" id="Relationship" class="im-input">
              </div>
              <div class="im-field" style="border-bottom:0">
                <label>Phone Number</label>
                <input type="text" name="EmergencyPhone" id="EmergencyPhone" class="im-input">
              </div>
            </div>
          </div>

          <!-- Employment Details -->
          <div class="im-card">
            <div class="im-card-hdr im-hdr-amber">
              <div class="im-card-hdr-left">
                <i data-lucide="briefcase"></i> Employment Details
              </div>
              <span class="im-badge-readonly"><i data-lucide="lock"></i> Read Only</span>
            </div>
            <div class="im-fields">
              <div class="im-field">
                <label>Employee Code</label>
                <input type="text" id="EmployeeCode" class="im-input" readonly>
              </div>
              <div class="im-field">
                <label>Date Hired</label>
                <input type="text" id="HiringDate" class="im-input" readonly>
              </div>
              <div class="im-field">
                <label>Work Email</label>
                <input type="text" id="WorkEmail" class="im-input" readonly>
              </div>
              <div class="im-field">
                <label>Digital Resume</label>
                <div class="im-readonly-val" id="DigitalResumeContainer">No resume uploaded</div>
              </div>
            </div>
          </div>

          <!-- Compensation -->
          <div class="im-card">
            <div class="im-card-hdr im-hdr-purple">
              <div class="im-card-hdr-left">
                <i data-lucide="wallet"></i> Compensation
              </div>
              <span class="im-badge-readonly"><i data-lucide="lock"></i> Read Only</span>
            </div>
            <div class="im-fields">
              <div class="im-field">
                <label>Salary Grade</label>
                <input type="text" id="GradeLevel" class="im-input" readonly>
              </div>
              <div class="im-field">
                <label>Salary Range</label>
                <input type="text" id="SalaryRange" class="im-input" readonly>
              </div>
            </div>
          </div>

          <!-- Bank Details -->
          <div class="im-card">
            <div class="im-card-hdr im-hdr-slate">
              <div class="im-card-hdr-left">
                <i data-lucide="landmark"></i> Bank Details
              </div>
              <span class="im-badge-readonly"><i data-lucide="lock"></i> Read Only</span>
            </div>
            <div class="im-fields">
              <div class="im-field">
                <label>Bank Name</label>
                <input type="text" id="BankName" class="im-input" readonly>
              </div>
              <div class="im-field">
                <label>Account Number</label>
                <input type="text" id="BankAccountNumber" class="im-input" readonly>
              </div>
              <div class="im-field" style="border-bottom:0">
                <label>Account Type</label>
                <input type="text" id="AccountType" class="im-input" readonly>
              </div>
            </div>
          </div>

          <!-- Government Numbers -->
          <div class="im-card" style="grid-column:1/-1">
            <div class="im-card-hdr im-hdr-purple">
              <div class="im-card-hdr-left">
                <i data-lucide="file-check"></i> Government Numbers
              </div>
              <span class="im-badge-readonly"><i data-lucide="lock"></i> Read Only</span>
            </div>
            <div class="im-fields" style="grid-template-columns:repeat(4,1fr)">
              <div class="im-field" style="border-bottom:0">
                <label>TIN</label>
                <input type="text" id="TINNumber" class="im-input" readonly>
              </div>
              <div class="im-field" style="border-bottom:0">
                <label>SSS</label>
                <input type="text" id="SSSNumber" class="im-input" readonly>
              </div>
              <div class="im-field" style="border-bottom:0">
                <label>PhilHealth</label>
                <input type="text" id="PhilHealthNumber" class="im-input" readonly>
              </div>
              <div class="im-field" style="border-bottom:0">
                <label>Pag-IBIG</label>
                <input type="text" id="PagIBIGNumber" class="im-input" readonly>
              </div>
            </div>
          </div>

        </div>
      </form>

    </div><!-- /content-wrapper -->

    <!-- ══════════════════════════════
         REQUEST EDIT MODAL
         ══════════════════════════════ -->
    <div class="modal-overlay hidden" id="requestEditModal">
      <div class="rem-dialog">

        <!-- Hero header -->
        <div class="rem-hero">
          <div class="rem-hero-inner">
            <div class="rem-icon-wrap">
              <i data-lucide="file-pen-line"></i>
            </div>
            <div class="rem-hero-text">
              <h3 class="rem-title">Request Information Update</h3>
              <p class="rem-subtitle">Fill in only the fields you want to change.</p>
            </div>
            <button class="rem-close" id="btnCloseRequestModal" title="Close">&times;</button>
          </div>
        </div>

        <!-- Notice -->
        <div class="rem-notice">
          <i data-lucide="info"></i>
          Changes will be reviewed and approved by HR before being applied to your record.
        </div>

        <!-- Body -->
        <div class="rem-body">
          <form id="requestEditForm">

            <!-- Bank & Compensation -->
            <div class="rem-section">
              <div class="rem-section-hdr rem-shdr-blue">
                <i data-lucide="landmark"></i> Bank & Compensation
              </div>
              <div class="rem-fields">
                <div class="rem-row">
                  <div class="rem-field">
                    <label>Bank Name</label>
                    <input type="text" name="BankName" class="rem-input" placeholder="e.g. BDO">
                  </div>
                  <div class="rem-field">
                    <label>Account Number</label>
                    <input type="text" name="BankAccountNumber" class="rem-input" placeholder="Enter account number">
                  </div>
                </div>
                <div class="rem-row">
                  <div class="rem-field" style="border-bottom:0">
                    <label>Account Type</label>
                    <select name="AccountType" class="rem-input">
                      <option value="">— Select —</option>
                      <option value="Savings">Savings</option>
                      <option value="Checking">Checking</option>
                      <option value="Payroll">Payroll</option>
                    </select>
                  </div>
                  <div class="rem-field" style="border-bottom:0"></div>
                </div>
              </div>
            </div>

            <!-- Government Numbers -->
            <div class="rem-section">
              <div class="rem-section-hdr rem-shdr-purple">
                <i data-lucide="file-check"></i> Government Numbers
              </div>
              <div class="rem-fields">
                <div class="rem-row">
                  <div class="rem-field">
                    <label>TIN</label>
                    <input type="text" name="TINNumber" class="rem-input" placeholder="000-000-000-000">
                  </div>
                  <div class="rem-field">
                    <label>SSS</label>
                    <input type="text" name="SSSNumber" class="rem-input" placeholder="00-0000000-0">
                  </div>
                </div>
                <div class="rem-row">
                  <div class="rem-field" style="border-bottom:0">
                    <label>PhilHealth</label>
                    <input type="text" name="PhilHealthNumber" class="rem-input" placeholder="00-000000000-0">
                  </div>
                  <div class="rem-field" style="border-bottom:0">
                    <label>Pag-IBIG</label>
                    <input type="text" name="PagIBIGNumber" class="rem-input" placeholder="0000-0000-0000">
                  </div>
                </div>
              </div>
            </div>

          </form>
        </div>

        <!-- Footer -->
        <div class="rem-footer">
          <div class="rem-footer-hint">
            <i data-lucide="clock"></i>
            Requests are usually processed within 1–2 business days.
          </div>
          <button type="submit" form="requestEditForm" class="rem-btn-send">
            <i data-lucide="send"></i> Send Request
          </button>
        </div>

      </div>
    </div>

  </main>

  <script src="../../js/sidebar-active.js"></script>
  <script src="../../js/chcdashboard.js"></script>
  <script src="../../js/hr1informationmanagement.js?v=<?php echo time(); ?>"></script>
  <script>
    lucide.createIcons();

    // Modal open/close
    document.getElementById('btnRequestEdit').addEventListener('click', function () {
      const m = document.getElementById('requestEditModal');
      m.classList.remove('hidden');
      m.classList.add('show');
      lucide.createIcons();
    });
    document.getElementById('btnCloseRequestModal').addEventListener('click', function () {
      const m = document.getElementById('requestEditModal');
      m.classList.add('hidden');
      m.classList.remove('show');
    });
    document.getElementById('requestEditModal').addEventListener('click', function (e) {
      if (e.target === this) {
        this.classList.add('hidden');
        this.classList.remove('show');
      }
    });
  </script>

  <script src="../../js/user-menu.js"></script>
</body>
</html>


