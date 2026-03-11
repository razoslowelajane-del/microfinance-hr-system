<?php
require_once __DIR__ . "/includes/auth_employee.php";
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

  <?php include __DIR__ . "/sidebar.php"; ?>

  <main class="main-content">
    <header class="page-header">
      <div class="header-left">
        <button class="mobile-menu-btn" id="mobileMenuBtn" type="button">
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

        <?php
        $themeFile = __DIR__ . "/theme.php";
        if (file_exists($themeFile)) {
            include $themeFile;
        }
        ?>

        <button class="icon-btn" type="button">
          <i data-lucide="bell"></i>
        </button>
      </div>
    </header>

    <div class="content-wrapper">

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

      <form id="myInfoForm">
        <div class="im-grid">

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

    </div>

    <div class="modal-overlay hidden" id="requestEditModal">
      <div class="rem-dialog">

        <div class="rem-hero">
          <div class="rem-hero-inner">
            <div class="rem-icon-wrap">
              <i data-lucide="file-pen-line"></i>
            </div>
            <div class="rem-hero-text">
              <h3 class="rem-title">Request Information Update</h3>
              <p class="rem-subtitle">Fill in only the fields you want to change.</p>
            </div>
            <button class="rem-close" id="btnCloseRequestModal" title="Close" type="button">&times;</button>
          </div>
        </div>

        <div class="rem-notice">
          <i data-lucide="info"></i>
          Changes will be reviewed and approved by HR before being applied to your record.
        </div>

        <div class="rem-body">
          <form id="requestEditForm">

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
  <script src="../../js/user-menu.js"></script>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      if (window.lucide) lucide.createIcons();

      const openBtn = document.getElementById('btnRequestEdit');
      const closeBtn = document.getElementById('btnCloseRequestModal');
      const modal = document.getElementById('requestEditModal');

      if (openBtn && modal) {
        openBtn.addEventListener('click', function () {
          modal.classList.remove('hidden');
          modal.classList.add('show');
          if (window.lucide) lucide.createIcons();
        });
      }

      if (closeBtn && modal) {
        closeBtn.addEventListener('click', function () {
          modal.classList.add('hidden');
          modal.classList.remove('show');
        });
      }

      if (modal) {
        modal.addEventListener('click', function (e) {
          if (e.target === modal) {
            modal.classList.add('hidden');
            modal.classList.remove('show');
          }
        });
      }
    });
  </script>
</body>
</html>