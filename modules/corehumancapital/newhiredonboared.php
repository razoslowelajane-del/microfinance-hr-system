
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>New Hired Onboarding Management</title>
  <link rel="stylesheet" href="../../css/newhired.css?v=1.2">
  <script src="https://unpkg.com/lucide@latest"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <link rel="icon" type="image/png" href="../../img/logo.png">
</head>
<body>

  <!-- Sidebar -->
  <aside class="sidebar" id="sidebar">
    <!-- ... sidebar content remains same ... -->
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
          <i data-lucide="layout-dashboard"></i>
          <span>Dashboard</span>
        </a>

        <a href="newhiredonboared.php" class="nav-item active">
          <i data-lucide="user-plus"></i>
          <span>New Hired Onboard Management</span>
        </a>

         <a href="employeemaster.php" class="nav-item">
          <i data-lucide="file-user"></i>
          <span>Employee Master File</span>
        </a>
        <a href="rolemanagement.php" class="nav-item">
          <i data-lucide="user-cog"></i>
          <span>Role & Position Management</span>
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
       
       <a href="../../logout.php" class="nav-item" onclick="return confirm ('Are you sure you want to log out?')">
            <i data-lucide="log-out"></i>
            <span>Logout</span>
        </a>
      </div>
    </nav>

    <div class="sidebar-footer">
      <div class="user-profile">
        <div class="user-avatar">
          <img src="../img/profile.png" alt="User">
        </div>
        <div class="user-info">
          <span class="user-name">John Doe</span>
          <span class="user-role">Administrator</span>
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
          <p>Welcome back, John! Here's what's happening today.</p>
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
        <!-- Card 1 -->
        <div class="stat-card">
          <div class="stat-icon-wrapper blue">
            <i data-lucide="inbox"></i>
          </div>
          <div class="stat-content">
            <div class="stat-label">Pending Review</div>
            <div class="stat-value">3</div>
            <div class="stat-desc">Requires Attention</div>
          </div>
        </div>
        
        <!-- Card 2 -->
        <div class="stat-card">
          <div class="stat-icon-wrapper green">
            <i data-lucide="check-circle-2"></i>
          </div>
          <div class="stat-content">
            <div class="stat-label">Processed Today</div>
            <div class="stat-value">5</div>
            <div class="stat-desc">Completed</div>
          </div>
        </div>
      </div>

      <!-- Inbox Section -->
      <div class="content-card inbox-card">
        <div class="card-header inbox-header">
          <div class="inbox-title-wrapper">
            <div class="inbox-title">Inbox</div>
            <div class="inbox-subtitle">List of new hire packets from HR1</div>
          </div>
          <button class="btn filter-btn">
            <i data-lucide="filter"></i> Filter
          </button>
        </div>
        
        <div class="card-body card-body-nopad">
          <div class="data-table">
            <table class="role-table">
              <thead>
                <tr>
                  <th class="col-name">Candidate Name</th>
                  <th class="col-date">Join Date</th>
                  <th class="col-dept">Department / Position</th>
                  <th class="col-action">Action</th>
                </tr>
              </thead>
              <tbody>
                <!-- Row 1 -->
                <tr class="role-row-item">
                  <td>
                    <div class="client-info">
                      <div class="client-avatar bg-blue">AS</div>
                      <div>
                        <span class="client-name">Alex Smith</span>
                        <span class="client-detail">ID: REQ-2024-001</span>
                      </div>
                    </div>
                  </td>
                  <td class="text-cell-secondary">Oct 15, 2024</td>
                  <td>
                    <div class="cell-flex-col">
                      <span class="text-cell-primary-bold">Engineering</span>
                      <span class="text-cell-secondary-small">Senior Developer</span>
                    </div>
                  </td>
                  <td class="action-cell">
                    <button class="btn btn-primary make-master-file-btn review-btn" data-id="1" data-name="Alex Smith">
                      REVIEW PACKET
                    </button>
                  </td>
                </tr>

                <!-- Row 2 -->
                <tr class="role-row-item">
                  <td>
                    <div class="client-info">
                      <div class="client-avatar bg-red">MJ</div>
                      <div>
                        <span class="client-name">Mary Johnson</span>
                        <span class="client-detail">ID: REQ-2024-002</span>
                      </div>
                    </div>
                  </td>
                  <td class="text-cell-secondary">Oct 20, 2024</td>
                  <td>
                    <div class="cell-flex-col">
                      <span class="text-cell-primary-bold">Marketing</span>
                      <span class="text-cell-secondary-small">Marketing Specialist</span>
                    </div>
                  </td>
                  <td class="action-cell">
                    <button class="btn btn-primary make-master-file-btn review-btn" data-id="2" data-name="Mary Johnson">
                      REVIEW PACKET
                    </button>
                  </td>
                </tr>

                <!-- Row 3 -->
                <tr class="role-row-item">
                  <td>
                    <div class="client-info">
                      <div class="client-avatar bg-orange">DK</div>
                      <div>
                        <span class="client-name">David Kim</span>
                        <span class="client-detail">ID: REQ-2024-000</span>
                      </div>
                    </div>
                  </td>
                  <td class="text-cell-secondary">Oct 01, 2024</td>
                  <td>
                    <div class="cell-flex-col">
                      <span class="text-cell-primary-bold">Finance</span>
                      <span class="text-cell-secondary-small">Analyst</span>
                    </div>
                  </td>
                  <td class="action-cell">
                    <button class="btn btn-primary make-master-file-btn review-btn" data-id="3" data-name="David Kim">
                      REVIEW PACKET
                    </button>
                  </td>
                </tr>

              </tbody>
            </table>
          </div>
        </div>
      </div>

    </div>

    <!-- Redesigned Employee Master File Modal -->
    <div class="modal-overlay hidden" id="employeeModal">
      <div class="modal-content modal-content-styled">
        
        <!-- Modal Header -->
        <div class="modal-header-styled">
          <div>
            <h3 id="modalEmployeeName" class="modal-title-custom">New Hire Packet Details: Alex Smith</h3>
            <p class="modal-subtitle-custom">Review details and create employee master record</p>
          </div>
          <button id="btnCloseModal" class="close-modal-btn">
            <i data-lucide="x" class="icon-sm"></i> Close
          </button>
        </div>

        <!-- Scrollable Body -->
        <div class="modal-body-scroll">
          
          <div class="modal-grid-2">
            
            <!-- Personal Information -->
            <div>
              <h4 class="section-title text-purple">Personal Information</h4>
              <div class="info-group">
                <div class="info-row">
                  <span class="info-label">Full Name</span>
                  <span class="modal-val-name info-value">Alex Smith</span>
                </div>
                <!-- ... -->
                <div class="info-row">
                  <span class="info-label">Address</span>
                  <span class="modal-val-address info-value">123 Main St, Springfield</span>
                </div>
              </div>
            </div>

            <!-- Employment Information -->
            <div>
              <h4 class="section-title text-blue">Employment Information</h4>
              <div class="info-group">
                <!-- ... -->
                <div class="info-row">
                  <span class="info-label">Manager</span>
                  <span class="modal-val-manager info-value">Sarah Connor</span>
                </div>
              </div>
            </div>

          </div>

          <div class="modal-grid-2 no-margin">
            
            <!-- Offer Details -->
            <div>
              <h4 class="section-title text-yellow">Offer Details</h4>
              <div class="info-group">
                <!-- ... -->
                <div class="info-row">
                  <span class="info-label">Contract Type</span>
                  <span class="modal-val-contract info-value">Full-Time / Permanent</span>
                </div>
              </div>
            </div>

             <!-- Uploaded Documents -->
             <div>
              <h4 class="section-title text-green">Uploaded Documents</h4>
              <div class="info-group compact">
                <div class="info-row-center">
                  <span class="info-label">Resume</span>
                  <a href="#" class="doc-link">resume_asmith.pdf</a>
                </div>
                <div class="info-row-center">
                  <span class="info-label">Offer Letter</span>
                  <a href="#" class="doc-link">signed_offer.pdf</a>
                </div>
                <div class="info-row-center">
                  <span class="info-label">ID Proof</span>
                  <a href="#" class="doc-link">id_scan.jpg</a>
                </div>
              </div>
            </div>

          </div>

        </div>

        <!-- Modal Footer -->
        <div class="modal-footer-styled">
          <button id="btnReportMissing" class="btn-report">
            <i data-lucide="alert-triangle" class="icon-sm"></i> Mark Incomplete
          </button>
          <button id="btnCreateMaster" class="btn-create-master">
            <i data-lucide="check-circle" class="icon-sm"></i> Create Master Record
          </button>
        </div>

      </div>
    </div>
  <script src="../../js/newhired.js?v=2.2"></script>
  <script>
    lucide.createIcons();
  </script>
  
  <script src="../../js/user-menu.js"></script>
</body>
</html>


