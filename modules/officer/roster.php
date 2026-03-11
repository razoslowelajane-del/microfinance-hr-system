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
        <span class="status-badge draft" id="headerRosterStatus">
          <i data-lucide="file-clock" class="meta-icon"></i>
          Draft Roster
        </span>

        <span class="mini-info">
          <i data-lucide="building-2" class="meta-icon"></i>
          <?php echo htmlspecialchars($deptName); ?> Department
        </span>

        <span class="mini-info">
          <i data-lucide="calendar-range" class="meta-icon"></i>
          2 full work weeks (Mon–Sat)
        </span>

        <span class="mini-info autosave-ready" id="autoSaveIndicator">
          <i data-lucide="save" class="meta-icon"></i>
          Auto-save ready
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
        <p class="stat-subtext">Employees under your department roster scope</p>
      </div>

      <div class="stat-card">
        <div class="stat-top">
          <span class="stat-label">Coverage Period</span>
          <i data-lucide="calendar-days" class="stat-icon"></i>
        </div>
        <strong class="stat-value" id="statCoverage">--</strong>
        <p class="stat-subtext">Fixed 2-week Monday–Saturday schedule</p>
      </div>

      <div class="stat-card">
        <div class="stat-top">
          <span class="stat-label">Unassigned Slots</span>
          <i data-lucide="alert-circle" class="stat-icon"></i>
        </div>
        <strong class="stat-value" id="statUnassigned">--</strong>
        <p class="stat-subtext">Editable cells still needing assignment</p>
      </div>

      <div class="stat-card">
        <div class="stat-top">
          <span class="stat-label">Roster Status</span>
          <i data-lucide="clipboard-check" class="stat-icon"></i>
        </div>
        <strong class="stat-value" id="statRosterStatus">Draft</strong>
        <p class="stat-subtext">Draft, return, submit, and review flow</p>
      </div>
    </div>

    <aside class="shift-sidebar">
      <div class="shift-sidebar-head">
        <div>
          <h3>Available Shifts</h3>
          <p>Select one shift, then click editable cells to assign faster.</p>
        </div>

        <div class="sidebar-mini-note">
          <i data-lucide="shield-check"></i>
          <span>Leave, holiday, and self-locked cells are protected.</span>
        </div>
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
            <p class="card-subtitle">
              Manage the department roster for a fixed 2-week Monday–Saturday period.
            </p>
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
              <span>Fill Editable Range</span>
            </button>

            <button class="btn-secondary" id="btnAiSuggest" type="button">
              <i data-lucide="sparkles"></i>
              <span>AI Apply &amp; Review</span>
            </button>

            <button class="btn-secondary" id="btnClearRange" type="button">
              <i data-lucide="eraser"></i>
              <span>Clear Editable Range</span>
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
            <span class="legend-item">
              <span class="legend-dot ai"></span>
              AI Suggested
            </span>
          </div>
        </div>
      </div>

      <div class="card-body">
        <div id="aiReviewPanel" class="ai-review-panel hidden">
          <div class="ai-review-head">
            <div>
              <h4>AI Post-Apply Review</h4>
              <p>This summary reflects the schedule after AI suggestions were applied.</p>
            </div>

            <div class="ai-review-actions">
              <button type="button" class="btn-secondary" id="btnDismissAiReview">
                <i data-lucide="x"></i>
                <span>Dismiss</span>
              </button>
            </div>
          </div>

          <div class="ai-review-grid">
            <div class="review-metric">
              <span class="review-label">Employees Included</span>
              <strong id="aiEmployeesIncluded">--</strong>
            </div>
            <div class="review-metric">
              <span class="review-label">Officer Self Included</span>
              <strong id="aiSelfIncluded">--</strong>
            </div>
            <div class="review-metric">
              <span class="review-label">Fairness Score</span>
              <strong id="aiFairnessScore">--</strong>
            </div>
            <div class="review-metric">
              <span class="review-label">Coverage Score</span>
              <strong id="aiCoverageScore">--</strong>
            </div>
            <div class="review-metric">
              <span class="review-label">Compliance Score</span>
              <strong id="aiComplianceScore">--</strong>
            </div>
            <div class="review-metric">
              <span class="review-label">Unassigned Remaining</span>
              <strong id="aiUnassignedRemaining">--</strong>
            </div>
          </div>

          <div class="ai-review-columns">
            <div class="review-list-card">
              <h5>Warnings</h5>
              <ul id="aiWarningsList" class="review-list">
                <li>No AI review data yet.</li>
              </ul>
            </div>

            <div class="review-list-card">
              <h5>Errors / Conflicts</h5>
              <ul id="aiErrorsList" class="review-list">
                <li>No AI review data yet.</li>
              </ul>
            </div>
          </div>
        </div>

        <div class="roster-table-wrapper">
          <table class="roster-table">
            <thead id="rosterHead"></thead>
            <tbody id="rosterBody">
              <tr>
                <td style="text-align:center; padding:30px;">Loading schedules…</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="helper-note">
          <i data-lucide="info"></i>
          <span>
            Your own row is included in the roster. It is locked for manual editing, but AI may assign your shift automatically. Sundays are skipped. Approved leave dates appear as LEAVE and cannot be scheduled.
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
    myEmpId: <?php echo $myEmpId ? (int)$myEmpId : 'null'; ?>,
    rules: {
      fixedPeriodDays: 12,
      workDays: ["MON", "TUE", "WED", "THU", "FRI", "SAT"],
      skipSunday: true,
      selfRowManualLocked: true,
      leaveLocked: true,
      holidayLocked: true
    }
  };

  document.addEventListener("DOMContentLoaded", function () {
    if (window.lucide) lucide.createIcons();
  });
</script>

<script src="../../js/officer/roster.js?v=<?php echo time(); ?>"></script>
</body>
</html>