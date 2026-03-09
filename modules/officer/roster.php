<?php
require_once __DIR__ . "/includes/auth_officer.php";

$deptName = $_SESSION['department_name'] ?? 'My Department';
$deptId   = $_SESSION['department_id'] ?? null;

$accountId  = $_SESSION['account_id'] ?? $_SESSION['AccountID'] ?? $_SESSION['user_id'] ?? null;
$myEmpId    = $_SESSION['employee_id'] ?? $_SESSION['EmployeeID'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Roster Scheduler | <?php echo htmlspecialchars($deptName); ?></title>

  <link rel="icon" type="image/png" href="../../img/logo.png">
  <link rel="stylesheet" href="../../css/officer/roster.css?v=<?php echo time(); ?>">

  <script src="https://cdn.jsdelivr.net/npm/lucide@latest"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<?php include 'sidebar.php'; ?>

<main class="main-content">
  <header class="page-header">
    <div class="header-left">
      <h1>Roster Scheduler</h1>

      <div class="page-top-meta">
        <span class="status-badge draft">
          <i data-lucide="file-clock" class="meta-icon"></i>
          Draft Roster
        </span>

        <span class="mini-info">
          <i data-lucide="building-2" class="meta-icon"></i>
          <?php echo htmlspecialchars($deptName); ?> Department
        </span>

        <span class="mini-info">
          <i data-lucide="calendar-range" class="meta-icon"></i>
          12-day / month-end cutoff
        </span>
      </div>
    </div>

    <div class="header-right action-buttons">
      <button class="btn-secondary" type="button" onclick="location.href='past_rosters.php'">
        <i data-lucide="history"></i>
        <span>Past Schedules</span>
      </button>

      <div class="search-box">
        <i data-lucide="search"></i>
        <input id="searchInput" type="search" placeholder="Search employee...">
      </div>

      <button class="btn-primary" id="submitToHR" type="button">
        <i data-lucide="send"></i>
        <span>Submit to HR Manager</span>
      </button>

      <?php include 'theme.php'; ?>
    </div>
  </header>

  <section class="roster-layout">
    <div class="roster-stats">
      <div class="stat-card">
        <div class="stat-top">
          <span class="stat-label">Total Employees</span>
          <i data-lucide="users" class="stat-icon"></i>
        </div>
        <strong class="stat-value" id="statEmployees">--</strong>
        <p class="stat-subtext">Employees included in this roster</p>
      </div>

      <div class="stat-card">
        <div class="stat-top">
          <span class="stat-label">Coverage</span>
          <i data-lucide="calendar-days" class="stat-icon"></i>
        </div>
        <strong class="stat-value" id="statCoverage">--</strong>
        <p class="stat-subtext">Current scheduling cutoff period</p>
      </div>

      <div class="stat-card">
        <div class="stat-top">
          <span class="stat-label">Unassigned Slots</span>
          <i data-lucide="alert-circle" class="stat-icon"></i>
        </div>
        <strong class="stat-value" id="statUnassigned">--</strong>
        <p class="stat-subtext">Cells still needing assignment</p>
      </div>

      <div class="stat-card">
        <div class="stat-top">
          <span class="stat-label">Roster Status</span>
          <i data-lucide="clipboard-check" class="stat-icon"></i>
        </div>
        <strong class="stat-value" id="statRosterStatus">Draft</strong>
        <p class="stat-subtext">Not yet submitted to HR Manager</p>
      </div>
    </div>

    <aside class="shift-sidebar">
      <div class="shift-sidebar-head">
        <h3>Available Shifts</h3>
        <p>Select one shift, then click cells to assign faster.</p>
      </div>

      <div id="shiftSelector" class="shift-selector">
        <div class="shift-loading">Loading shifts…</div>
      </div>
    </aside>

    <section class="content-card">
      <div class="card-header-block">
        <div class="card-header-top">
          <div>
            <h3 class="card-title">Duty Assignments</h3>
            <p class="card-subtitle">Manage department schedule for the current cutoff period.</p>
          </div>

          <div class="roster-controls">
            <button class="icon-btn" id="prevPeriod" type="button" title="Previous Period">
              <i data-lucide="chevron-left"></i>
            </button>

            <span class="week-range" id="periodLabel">Loading…</span>

            <button class="icon-btn" id="nextPeriod" type="button" title="Next Period">
              <i data-lucide="chevron-right"></i>
            </button>
          </div>
        </div>

        <div class="card-toolbar">
          <div class="toolbar-left">
            <button class="btn-secondary" id="btnFillAll" type="button">
              <i data-lucide="wand-2"></i>
              <span>Fill All (Range)</span>
            </button>

            <button class="btn-secondary" id="btnAiSuggest" type="button">
              <i data-lucide="sparkles"></i>
              <span>AI Suggest</span>
            </button>

            <button class="btn-secondary" id="btnClearRange" type="button">
              <i data-lucide="eraser"></i>
              <span>Clear Range</span>
            </button>
          </div>

          <div class="toolbar-legend">
            <span class="legend-item">
              <span class="legend-dot editable"></span>
              Editable
            </span>
            <span class="legend-item">
              <span class="legend-dot locked"></span>
              Locked
            </span>
            <span class="legend-item">
              <span class="legend-dot me"></span>
              Your Row
            </span>
            <span class="legend-item">
              <span class="legend-dot holiday"></span>
              Holiday
            </span>
            <span class="legend-item">
              <span class="legend-dot leave"></span>
              Leave
            </span>
          </div>
        </div>
      </div>

      <div class="card-body">
        <div class="roster-table-wrapper">
          <table class="roster-table">
            <thead id="rosterHead"></thead>
            <tbody id="rosterBody">
              <tr>
                <td style="text-align:center; padding: 30px;">Loading schedules…</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="helper-note">
          <i data-lucide="info"></i>
          <span>
            Note: Your own row is locked. Sundays are skipped. Approved leave dates appear as LEAVE and cannot be scheduled.
          </span>
        </div>
      </div>
    </section>
  </section>
</main>

<script>
  window.__ROSTER_CTX__ = {
    deptId: <?php echo $deptId ? (int)$deptId : 'null'; ?>,
    accountId: <?php echo $accountId ? (int)$accountId : 'null'; ?>,
    myEmpId: <?php echo $myEmpId ? (int)$myEmpId : 'null'; ?>
  };

  document.addEventListener("DOMContentLoaded", function () {
    if (window.lucide) lucide.createIcons();
  });
</script>

<script src="../../js/officer/roster.js?v=<?php echo time(); ?>"></script>
</body>
</html>