<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['user_role']) !== 'administrator') {
    header('Location: ../../login.php');
    exit;
}

require_once '../../config/config.php';

// Fetch all roles from database
$rolesSql = "SELECT * FROM roles ORDER BY RoleID ASC";
$rolesResult = $conn->query($rolesSql);
$roles = [];
if ($rolesResult) {
    while ($row = $rolesResult->fetch_assoc()) {
        $roles[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Roles & Permissions - Microfinance</title>
  <!-- Base styles from useraccount.css for layout consistency -->
  <link rel="stylesheet" href="../../css/useraccount.css?v=1.4"> 
  <!-- Specific styles for this page -->
  <link rel="stylesheet" href="../../css/rolespermission.css?v=1.0">
  <link rel="stylesheet" href="../../css/sidebar-fix.css?v=1.0">
  <script src="https://unpkg.com/lucide@0.474.0/dist/umd/lucide.js" crossorigin="anonymous"></script>
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
          <i data-lucide="layout-dashboard"></i>
          <span>Dashboard</span>
        </a>

        <div class="nav-item-group active">
          <button class="nav-item has-submenu" data-module="hr">
            <div class="nav-item-content">
              <i data-lucide="users"></i>
              <span>Account Management</span>
            </div>
            <i data-lucide="chevron-down" class="submenu-icon"></i>
          </button>
          <div class="submenu" id="submenu-hr">
            <a href="useraccount.php" class="submenu-item">
              <i data-lucide="user-plus"></i>
              <span>User Accounts</span>
            </a>
            <a href="rolespermission.php" class="submenu-item active">
              <i data-lucide="contact-round"></i>
              <span>Roles & Permissions</span>
            </a>
            <a href="securitysetting.php" class="submenu-item">
              <i data-lucide="user-cog"></i>
              <span>Security Settings</span>
            </a>
            <a href="auditlogs.php" class="submenu-item">
              <i data-lucide="book-user"></i>
              <span>Audit Logs</span>
            </a>
          </div>
        </div>
        
        <!-- Reuse other menus... -->
        <div class="nav-item-group">
          <button class="nav-item has-submenu" data-module="finance">
            <div class="nav-item-content">
              <i data-lucide="banknote"></i>
              <span>Finance</span>
            </div>
            <i data-lucide="chevron-down" class="submenu-icon"></i>
          </button>
          <div class="submenu" id="submenu-finance">
            <a href="#" class="submenu-item"><i data-lucide="receipt"></i><span>Accounting</span></a>
            <a href="#" class="submenu-item"><i data-lucide="file-text"></i><span>Invoicing</span></a>
            <a href="#" class="submenu-item"><i data-lucide="pie-chart"></i><span>Budget Planning</span></a>
          </div>
        </div>

        <div class="nav-item-group">
            <button class="nav-item has-submenu" data-module="loans">
            <div class="nav-item-content">
                <i data-lucide="hand-coins"></i>
                <span>Loan Management</span>
            </div>
            <i data-lucide="chevron-down" class="submenu-icon"></i>
            </button>
            <div class="submenu" id="submenu-loans">
            <a href="#" class="submenu-item"><i data-lucide="file-plus"></i><span>Applications</span></a>
            <a href="#" class="submenu-item"><i data-lucide="check-circle"></i><span>Approvals</span></a>
            <a href="#" class="submenu-item"><i data-lucide="calendar-clock"></i><span>Disbursements</span></a>
            <a href="#" class="submenu-item"><i data-lucide="coins"></i><span>Collections</span></a>
            </div>
        </div>

        <a href="#" class="nav-item"><i data-lucide="users-round"></i><span>Clients</span></a>
        <a href="#" class="nav-item"><i data-lucide="file-bar-chart"></i><span>Reports</span></a>
      </div>

      <div class="nav-section">
        <span class="nav-section-title">SETTINGS</span>
        <a href="#" class="nav-item"><i data-lucide="settings"></i><span>Configuration</span></a>
        <a href="#" class="nav-item"><i data-lucide="shield"></i><span>Security</span></a>
        <a href="../../logout.php" class="nav-item" onclick="return confirm ('Are you sure you want to log out?')">
            <i data-lucide="log-out"></i><span>Logout</span>
        </a>
      </div>
    </nav>

    <div class="sidebar-footer">
      <div class="user-profile">
        <div class="user-avatar">
          <img src="../../img/profile.png" alt="User">
        </div>
        <div class="user-info">
          <span class="user-name"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
          <span class="user-role"><?php echo htmlspecialchars($_SESSION['user_role']); ?></span>
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
          <h1>Roles & Permissions</h1>
          <p>Manage user roles and access rights.</p>
        </div>
      </div>
      <div class="header-right">
        <div class="search-box">
          <i data-lucide="search"></i>
          <input type="search" placeholder="Search roles...">
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

      <!-- Stats Bar -->
      <?php $totalRoles = count($roles); ?>
      <div class="rp-stats">
        <div class="rp-stat-card">
          <div class="rp-stat-icon indigo"><i data-lucide="shield"></i></div>
          <div class="rp-stat-info">
            <span class="rp-stat-value"><?php echo $totalRoles; ?></span>
            <span class="rp-stat-label">Total Roles</span>
          </div>
        </div>
        <div class="rp-stat-card">
          <div class="rp-stat-icon violet"><i data-lucide="shield-check"></i></div>
          <div class="rp-stat-info">
            <span class="rp-stat-value"><?php echo $totalRoles; ?></span>
            <span class="rp-stat-label">Active Roles</span>
          </div>
        </div>
        <div class="rp-stat-card">
          <div class="rp-stat-icon green"><i data-lucide="key-round"></i></div>
          <div class="rp-stat-info">
            <span class="rp-stat-value">—</span>
            <span class="rp-stat-label">Permissions</span>
          </div>
        </div>
      </div>

      <!-- Roles Table Card -->
      <section class="rp-panel">
        <div class="rp-panel-header">
          <div class="rp-panel-left">
            <div class="rp-panel-icon"><i data-lucide="contact-round"></i></div>
            <div class="rp-panel-titles">
              <h2>Defined Roles</h2>
              <div class="rp-panel-sub"><?php echo $totalRoles; ?> role<?php echo $totalRoles !== 1 ? 's' : ''; ?> configured</div>
            </div>
          </div>
          <div class="rp-panel-actions">
            <div class="rp-panel-search">
              <i data-lucide="search"></i>
              <input type="search" id="roleSearch" placeholder="Search roles…">
            </div>
            <button id="addRoleBtn" class="btn btn-primary">
              <i data-lucide="plus"></i> Add Role
            </button>
          </div>
        </div>

        <div class="panel-body">
          <div class="table-responsive">
            <table id="rolesTable" class="users-table">
              <thead>
                <tr>
                  <th>Role</th>
                  <th>Description</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($roles)): ?>
                <tr><td colspan="3" style="text-align:center;padding:32px;color:var(--text-tertiary);">No roles found.</td></tr>
                <?php else: ?>
                  <?php foreach ($roles as $role):
                    $initials = strtoupper(substr($role['RoleName'], 0, 2));
                  ?>
                  <tr>
                    <td>
                      <div class="rp-role-cell">
                        <div class="rp-role-avatar"><?php echo htmlspecialchars($initials); ?></div>
                        <div>
                          <div class="rp-role-name"><?php echo htmlspecialchars($role['RoleName']); ?></div>
                          <div class="rp-role-id">#<?php echo $role['RoleID']; ?></div>
                        </div>
                      </div>
                    </td>
                    <td><span class="rp-desc"><?php echo htmlspecialchars($role['Description'] ?? 'No description'); ?></span></td>
                    <td>
                      <div class="action-buttons">
                        <button class="btn btn-sm btn-edit" data-role-id="<?php echo $role['RoleID']; ?>" onclick="editRole(<?php echo $role['RoleID']; ?>, '<?php echo htmlspecialchars($role['RoleName']); ?>')">
                          <i data-lucide="edit-2"></i> Edit
                        </button>
                        <button class="btn btn-sm btn-delete" data-role-id="<?php echo $role['RoleID']; ?>" onclick="archiveRole(<?php echo $role['RoleID']; ?>)">
                          <i data-lucide="archive"></i> Archive
                        </button>
                        <button class="btn-permission">
                          <i data-lucide="shield-check"></i> Permission
                        </button>
                      </div>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </section>

      <!-- Add / Edit Role Modal -->
      <div id="roleModal" class="modal" aria-hidden="true">
        <div class="modal-dialog">

          <!-- Gradient hero -->
          <div class="rp-modal-hero">
            <div class="rp-modal-hero-inner">
              <div class="rp-modal-hero-icon"><i data-lucide="shield-plus"></i></div>
              <div class="rp-modal-hero-text">
                <h3 id="modalTitle">Add New Role</h3>
                <p>Define a role and its description below.</p>
              </div>
              <button class="rp-close-modal" id="closeModalBtn" title="Close">&times;</button>
            </div>
          </div>

          <div class="modal-body">
            <form id="roleForm">
              <input type="hidden" id="roleId" name="role_id" value="">

              <div class="form-row">
                <label for="roleName">Role Name <span class="required">*</span></label>
                <input id="roleName" name="role_name" type="text" placeholder="e.g. HR Manager" required />
              </div>

              <div class="form-row">
                <label for="roleDescription">Description</label>
                <textarea id="roleDescription" name="description" rows="3" placeholder="Describe what this role can do…"></textarea>
              </div>
            </form>
          </div>

          <!-- Sticky footer -->
          <div class="form-actions">
            <button type="button" id="cancelRole" class="btn-modal-cancel">Cancel</button>
            <button type="submit" form="roleForm" class="btn-rp-submit" id="modalSubmitBtn">
              <i data-lucide="save"></i> Save Role
            </button>
          </div>

        </div>
      </div>

      <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    </div>
  </main>
  <script src="../../js/sidebar-active.js"></script>
  <script src="../../js/rolespermission.js?v=<?php echo time(); ?>"></script>
  <script>
    if (window.lucide) window.lucide.createIcons();

    // Inline search filter
    document.addEventListener('DOMContentLoaded', function() {
      const s = document.getElementById('roleSearch');
      if (s) s.addEventListener('input', function() {
        const q = this.value.toLowerCase();
        document.querySelectorAll('#rolesTable tbody tr').forEach(r => {
          r.style.display = r.textContent.toLowerCase().includes(q) ? '' : 'none';
        });
      });
    });
  </script>
  <script src="../../js/user-menu.js"></script>
</body>
</html>


