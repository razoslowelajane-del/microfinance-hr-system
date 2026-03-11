<?php
require_once __DIR__ . "/includes/auth_hr_manager.php";

$deptName = $_SESSION['department_name'] ?? 'HR Manager';
$managerAccountId = $_SESSION['account_id'] ?? $_SESSION['AccountID'] ?? $_SESSION['user_id'] ?? null;
$rosterId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($rosterId <= 0) {
    header('Location: roster_view.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Roster Review | <?php echo htmlspecialchars($deptName); ?></title>

    <link rel="icon" type="image/png" href="../img/logo.png">
    <link rel="stylesheet" href="../../css/manager/roster_review.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body>

<div class="page-shell">
    <?php include('sidebar.php'); ?>

    <div class="page-main">
        <main class="main-content">
            <header class="page-header">
                <div class="header-left">
                    <div class="header-title-row">
                        <h1>Roster Review</h1>
                        <a href="roster_view.php" class="back-link">
                            <i class="fa-solid fa-arrow-left"></i>
                            Back to Roster List
                        </a>
                    </div>

                    <div class="page-top-meta" id="rosterMeta">
                        <span class="mini-info skeleton skeleton-pill"></span>
                        <span class="mini-info skeleton skeleton-pill"></span>
                        <span class="mini-info skeleton skeleton-pill"></span>
                    </div>
                </div>

                <div class="header-right">
                    <div class="theme-wrap">
                        <?php
                        if (file_exists(__DIR__ . '/theme.php')) {
                            include(__DIR__ . '/theme.php');
                        } elseif (file_exists(__DIR__ . '/includes/theme.php')) {
                            include(__DIR__ . '/includes/theme.php');
                        }
                        ?>
                    </div>

                    <span class="status-badge" id="statusBadge">Loading...</span>
                </div>
            </header>

            <div class="roster-layout">
                <section class="roster-stats" id="statsGrid">
                    <div class="stat-card skeleton-card"></div>
                    <div class="stat-card skeleton-card"></div>
                    <div class="stat-card skeleton-card"></div>
                    <div class="stat-card skeleton-card"></div>
                </section>

                <section class="content-card" id="aiSummaryCard">
                    <div class="card-header-block">
                        <div>
                            <h3 class="card-title">Groq AI Review</h3>
                            <p class="card-subtitle">AI-generated summary of conflicts, coverage, and recommendations.</p>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="helper-note">
                            <i class="fa-solid fa-robot"></i>
                            <span id="aiSummaryText">Analyzing roster conflicts...</span>
                        </div>
                    </div>
                </section>

                <section class="ai-review-panel">
                    <div class="validation-header">
                        <div class="validation-title-wrap">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                            <h4>Validation Checks</h4>
                        </div>
                        <span class="validation-badge" id="validationBadge">Checking...</span>
                    </div>

                    <div class="ai-review-columns">
                        <div class="review-list-card danger-card">
                            <h5>Critical Conflicts</h5>
                            <ul class="review-list" id="criticalList">
                                <li class="muted">Loading conflicts...</li>
                            </ul>
                        </div>

                        <div class="review-list-card warning-card">
                            <h5>Rule Warnings</h5>
                            <ul class="review-list" id="warningList">
                                <li class="muted">Loading warnings...</li>
                            </ul>
                        </div>
                    </div>
                </section>

                <section class="content-card">
                    <div class="card-header-block">
                        <div>
                            <h3 class="card-title">Schedule Preview</h3>
                            <p class="card-subtitle">Review-only table. HR Manager cannot edit roster assignments here.</p>
                        </div>
                    </div>

                    <div class="roster-table-wrapper">
                        <table class="roster-table">
                            <thead id="rosterTableHead">
                                <tr>
                                    <th class="emp-col">Employee</th>
                                </tr>
                            </thead>
                            <tbody id="rosterTableBody">
                                <tr>
                                    <td class="loading-cell">Loading roster schedule...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="action-card">
                    <h3 class="card-title">Approval Actions</h3>
                    <p class="card-subtitle action-note">
                        Add remarks for the officer. Use <strong>Return to Officer</strong> if fixes are needed, or
                        <strong>Approve & Publish</strong> to finalize this roster and generate its timesheet.
                    </p>

                    <textarea
                        id="managerRemarks"
                        class="manager-remarks"
                        placeholder="Enter feedback for the officer..."
                    ></textarea>

                    <div class="action-meta" id="reviewMeta"></div>

                    <div class="btn-group">
                        <button type="button" class="btn-secondary" id="btnReturn">
                            <i class="fa-solid fa-reply"></i>
                            Return to Officer
                        </button>

                        <button type="button" class="btn-primary" id="btnApprove">
                            <i class="fa-solid fa-cloud-arrow-up"></i>
                            Approve & Publish
                        </button>
                    </div>
                </section>
            </div>
        </main>
    </div>
</div>

<script>
window.ROSTER_REVIEW_CONFIG = {
    rosterId: <?php echo (int)$rosterId; ?>,
    managerAccountId: <?php echo (int)($managerAccountId ?? 0); ?>,
    apiUrl: 'includes/roster_review_api.php',
    aiUrl: 'includes/roster_review_ai.php'
};
</script>
<script src="../../js/manager/roster_review.js?v=<?php echo time(); ?>"></script>
</body>
</html>