<?php
require_once __DIR__ . "/includes/auth_officer.php";

$deptName   = $_SESSION['department_name'] ?? 'Logistics';
$deptId     = (int)($_SESSION['department_id'] ?? 3);
$accountId  = (int)($_SESSION['account_id'] ?? $_SESSION['AccountID'] ?? $_SESSION['user_id'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Timesheet Review | <?php echo htmlspecialchars($deptName); ?></title>

    <link rel="stylesheet" href="../../css/officer/timesheet.css?v=<?php echo time(); ?>">
    <script src="https://cdn.jsdelivr.net/npm/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<?php include 'sidebar.php'; ?>

<main class="main-content">
    <header class="page-header">
        <div class="header-left">
            <h1>Timesheet Review</h1>
            <div class="page-top-meta" style="display:flex; gap:12px; margin-top:10px; flex-wrap:wrap;">
                <span class="status-badge draft" id="periodStatusBadge">Loading...</span>
                <span class="mini-info">
                    <i data-lucide="building-2" style="width:14px;"></i>
                    <?php echo htmlspecialchars($deptName); ?>
                </span>
                <span class="mini-info" id="selectedPeriodLabel">
                    <i data-lucide="calendar" style="width:14px;"></i>
                    Loading period...
                </span>
            </div>
        </div>

        <div class="header-right" style="display:flex; gap:12px; flex-wrap:wrap;">
            <button class="btn-secondary" id="btnExportPdf">
                <i data-lucide="file-down"></i> Export PDF
            </button>
            <button class="btn-primary" id="btnSendToHr">
                <i data-lucide="send"></i> Send to HR Manager
            </button>
            <?php include 'theme.php'; ?>
        </div>
    </header>

    <div class="control-panel-custom">
        <div style="display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
            <select class="select-styled" id="periodSelect"></select>

            <button class="btn-secondary" id="btnRefresh" style="height:42px; width:42px; padding:0; justify-content:center;">
                <i data-lucide="refresh-cw" style="width:16px;"></i>
            </button>

            <button class="btn-secondary" id="btnRecompute" style="height:42px;">
                Recompute All
            </button>
        </div>

        <div class="search-wrapper">
            <i data-lucide="search"></i>
            <input type="text" id="searchInput" class="search-input-styled" placeholder="Search employee or code...">
        </div>
    </div>

    <div class="roster-stats" style="margin-bottom:25px;">
        <div class="stat-card">
            <span class="stat-label">Employees</span>
            <strong class="stat-value" id="statEmployees">0</strong>
            <p class="stat-subtext">Total active in department</p>
        </div>

        <div class="stat-card">
            <span class="stat-label">OT Hours</span>
            <strong class="stat-value" id="statOtHours">0.00</strong>
            <p class="stat-subtext">Total for period</p>
        </div>

        <div class="stat-card">
            <span class="stat-label">Late (m)</span>
            <strong class="stat-value" id="statLateMinutes" style="color:#ef4444;">0</strong>
            <p class="stat-subtext">Total tardiness</p>
        </div>

        <div class="stat-card" style="border-left:4px solid #ef4444;">
            <span class="stat-label">Issues</span>
            <strong class="stat-value" id="statIssues" style="color:#ef4444;">0</strong>
            <p class="stat-subtext">Needs verification</p>
        </div>
    </div>

    <div class="content-card">
        <div class="card-header-block">
            <h3 class="card-title">Employee Performance Summary</h3>
            <p class="card-subtitle">Showing totals for the selected cutoff period.</p>
        </div>

        <div class="card-body" style="padding:0;">
            <div class="roster-table-wrapper">
                <table class="roster-table" id="timesheetTable">
                    <thead>
                        <tr>
                            <th class="col-emp">Employee Details</th>
                            <th class="col-num">Reg</th>
                            <th class="col-num">OT</th>
                            <th class="col-num">Late</th>
                            <th class="col-num">Abs</th>
                            <th class="col-leave">L.Cr</th>
                            <th class="col-leave">P.Lve</th>
                            <th class="col-leave">Excs</th>
                            <th class="col-num">Ded</th>
                            <th class="col-num">Final</th>
                            <th class="col-status">Status</th>
                            <th class="col-action">Action</th>
                        </tr>
                    </thead>
                    <tbody id="tsBody"></tbody>
                </table>
            </div>

            <div class="table-controls" style="padding:15px; display:flex; gap:10px; background:var(--bg-main);">
                <button class="btn-secondary" style="font-size:11px; padding:5px 15px;" id="btnSelectAll">Select All</button>
                <button class="btn-secondary" style="font-size:11px; padding:5px 15px;" id="btnResetSelection">Reset</button>
            </div>
        </div>
    </div>

    <div class="ai-review-panel" style="margin-top:25px;">
        <div class="ai-review-head">
            <h4>
                <i data-lucide="alert-triangle" style="color:#ef4444; vertical-align:middle;"></i>
                Verification Required
            </h4>
            <p>Issues detected that must be reviewed before submission.</p>
        </div>

        <div class="ai-review-columns" id="issuesContainer">
            <div class="review-list-card">
                <ul class="review-list">
                    <li>No issues found for this period.</li>
                </ul>
            </div>
        </div>
    </div>
</main>

<script>
    window.TIMESHEET_CTX = {
        deptId: <?php echo (int)$deptId; ?>,
        accountId: <?php echo (int)$accountId; ?>,
        deptName: <?php echo json_encode($deptName); ?>
    };
</script>
<script src="../../js/officer/timesheet.js?v=<?php echo time(); ?>"></script>
</body>
</html>