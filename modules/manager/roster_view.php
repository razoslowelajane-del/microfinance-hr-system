<?php
require_once __DIR__ . "/includes/auth_hr_manager.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$current_page = 'roster_view';
$deptName = $_SESSION['department_name'] ?? 'HR Department';

include('sidebar.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Roster View | <?php echo htmlspecialchars($deptName); ?></title>

    <link rel="icon" type="image/png" href="../../img/logo.png">
    <link rel="stylesheet" href="../../css/manager/roster_view.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<main class="main-content">
    <header class="page-header">
        <div class="header-left">
            <h1>Roster Review Queue</h1>
            <div class="page-top-meta">
                <span class="mini-info">
                    <i class="fa-solid fa-building meta-icon"></i>
                    <?php echo htmlspecialchars($deptName); ?>
                </span>
                <span class="mini-info">
                    <i class="fa-solid fa-clipboard-check meta-icon"></i>
                    HR Manager Review
                </span>
            </div>
        </div>

        <div class="header-right">
            <?php include('theme.php'); ?>

            <button type="button" class="btn-secondary" id="refreshBtn">
                <i class="fa-solid fa-rotate-right"></i>
                Refresh
            </button>
        </div>
    </header>

    <div class="roster-layout">

        <section class="roster-stats">
            <div class="stat-card">
                <div class="stat-top">
                    <span class="stat-label">Pending Review</span>
                    <i class="fa-solid fa-hourglass-half stat-icon"></i>
                </div>
                <span class="stat-value" id="pendingCount">0</span>
                <span class="stat-subtext">Rosters waiting for HR review</span>
            </div>

            <div class="stat-card">
                <div class="stat-top">
                    <span class="stat-label">Returned</span>
                    <i class="fa-solid fa-reply stat-icon"></i>
                </div>
                <span class="stat-value" id="returnedCount">0</span>
                <span class="stat-subtext">Returned back to officers</span>
            </div>

            <div class="stat-card">
                <div class="stat-top">
                    <span class="stat-label">Published</span>
                    <i class="fa-solid fa-circle-check stat-icon"></i>
                </div>
                <span class="stat-value" id="publishedCount">0</span>
                <span class="stat-subtext">Already published rosters</span>
            </div>

            <div class="stat-card">
                <div class="stat-top">
                    <span class="stat-label">Total Rosters</span>
                    <i class="fa-solid fa-table-list stat-icon"></i>
                </div>
                <span class="stat-value" id="totalCount">0</span>
                <span class="stat-subtext">All roster records found</span>
            </div>
        </section>

        <section class="content-card">
            <div class="card-header-block">
                <div class="card-header-top">
                    <div>
                        <h3 class="card-title">Roster Filters</h3>
                        <p class="card-subtitle">Find rosters by department, officer, period, or status.</p>
                    </div>
                </div>

                <div class="card-toolbar">
                    <div class="toolbar-left">
                        <div class="search-box">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input
                                type="text"
                                id="searchInput"
                                placeholder="Search by department, officer, roster ID..."
                            >
                        </div>

                        <select id="departmentFilter" class="toolbar-select">
                            <option value="">All Departments</option>
                        </select>

                        <select id="statusFilter" class="toolbar-select">
                            <option value="FOR_REVIEW">For Review</option>
                            <option value="">All Status</option>
                            <option value="RETURNED">Returned</option>
                            <option value="PUBLISHED">Published</option>
                            <option value="APPROVED">Approved</option>
                            <option value="DRAFT">Draft</option>
                        </select>

                        <select id="sortBy" class="toolbar-select">
                            <option value="latest">Latest Created</option>
                            <option value="oldest">Oldest Created</option>
                            <option value="period_asc">Period Start (Oldest)</option>
                            <option value="period_desc">Period Start (Newest)</option>
                            <option value="department_asc">Department A-Z</option>
                            <option value="department_desc">Department Z-A</option>
                        </select>
                    </div>

                    <div class="header-right">
                        <button type="button" class="btn-primary" id="applyFiltersBtn">
                            <i class="fa-solid fa-filter"></i>
                            Apply
                        </button>

                        <button type="button" class="btn-secondary" id="resetFiltersBtn">
                            <i class="fa-solid fa-filter-circle-xmark"></i>
                            Reset
                        </button>
                    </div>
                </div>
            </div>

            <div class="card-body">
                <div class="helper-note">
                    <i class="fa-solid fa-circle-info"></i>
                    <span id="resultMeta">Loading roster queue...</span>
                </div>

                <div class="roster-table-wrapper">
                    <table class="roster-table">
                        <thead>
                            <tr>
                                <th class="emp-col">Department</th>
                                <th>Period</th>
                                <th>Submitted By</th>
                                <th>Status</th>
                                <th>Submitted</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="rosterTableBody">
                            <tr>
                                <td colspan="7">Loading roster queue...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

    </div>
</main>

<script>
window.ROSTER_VIEW_CONFIG = {
    dataUrl: 'includes/roster_view_data.php',
    reviewBaseUrl: 'roster_review.php'
};
</script>
<script src="../../js/manager/roster_view.js?v=<?php echo time(); ?>"></script>

</body>
</html>