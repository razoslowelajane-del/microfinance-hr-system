<?php
require_once __DIR__ . "/includes/auth_officer.php";

$deptName = $_SESSION['department_name'] ?? 'My Department';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Requests | <?php echo htmlspecialchars($deptName); ?></title>

    <link rel="icon" type="image/png" href="../../img/logo.png">
    <link rel="stylesheet" href="../../css/officer/leave.css?v=<?php echo time(); ?>">

    <script src="https://cdn.jsdelivr.net/npm/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        .swal2-container { z-index: 9999 !important; }
    </style>

    <script>
        (function () {
            try {
                const savedTheme = localStorage.getItem("theme");
                if (savedTheme === "dark") {
                    document.documentElement.classList.add("dark-mode");
                    document.body.classList.add("dark-mode");
                }
            } catch (e) {}
        })();
    </script>
</head>
<body>
<?php include __DIR__ . "/sidebar.php"; ?>

<main class="main-content">
    <div class="page-shell">
        <div class="page-header">
            <div class="page-title-wrap">
                <h1>
                    <i data-lucide="calendar-days"></i>
                    Leave Requests
                </h1>
                <p>Review and endorse employee leave applications for <?php echo htmlspecialchars($deptName); ?>.</p>
            </div>

            <div class="header-actions">
                <div class="search-box">
                    <i data-lucide="search"></i>
                    <input type="text" id="globalSearch" placeholder="Search employee name or code">
                </div>

                <button class="btn btn-soft" id="refreshPreviewBtn" type="button">
                    <i data-lucide="refresh-cw"></i>
                    Refresh
                </button>

                <?php
                $themePath = __DIR__ . "/theme.php";
                if (file_exists($themePath)) include $themePath;
                ?>
            </div>
        </div>

        <div class="cards-grid">
            <div class="summary-card">
                <div class="summary-content">
                    <h2 id="countPending">0</h2>
                    <h4>Pending Requests</h4>
                    <p>Awaiting officer review</p>
                </div>
                <div class="icon icon-warning">
                    <i data-lucide="clock-3"></i>
                </div>
            </div>

            <div class="summary-card">
                <div class="summary-content">
                    <h2 id="countSent">0</h2>
                    <h4>Sent to HR</h4>
                    <p>Already endorsed by officer</p>
                </div>
                <div class="icon icon-primary">
                    <i data-lucide="send"></i>
                </div>
            </div>

            <div class="summary-card">
                <div class="summary-content">
                    <h2 id="countApproved">0</h2>
                    <h4>Approved</h4>
                    <p>Fully approved requests</p>
                </div>
                <div class="icon icon-success">
                    <i data-lucide="check-circle-2"></i>
                </div>
            </div>

            <div class="summary-card">
                <div class="summary-content">
                    <h2 id="countRejected">0</h2>
                    <h4>Rejected</h4>
                    <p>Requests that were denied</p>
                </div>
                <div class="icon icon-danger">
                    <i data-lucide="x-circle"></i>
                </div>
            </div>
        </div>

        <section class="panel">
            <div class="tabs">
                <button class="tab-btn active" data-tab="ALL">All</button>
                <button class="tab-btn" data-tab="PENDING">Pending</button>
                <button class="tab-btn" data-tab="APPROVED_BY_OFFICER">Sent to HR</button>
                <button class="tab-btn" data-tab="APPROVED_BY_HR">Approved</button>
                <button class="tab-btn" data-tab="REJECTED">Rejected</button>
            </div>

            <div class="filters" style="grid-template-columns: 2fr 1.2fr 1.2fr 1fr 1fr auto;">
                <input type="text" class="filter-control" id="searchInput" placeholder="Search employee name or code...">

                <select class="filter-control" id="leaveTypeFilter">
                    <option value="ALL">All Leave Types</option>
                    <option value="Vacation Leave">Vacation Leave</option>
                    <option value="Sick Leave">Sick Leave</option>
                    <option value="Emergency Leave">Emergency Leave</option>
                    <option value="Maternity/Paternity Leave">Maternity/Paternity Leave</option>
                    <option value="Leave Without Pay">Leave Without Pay</option>
                </select>

                <select class="filter-control" id="sortOrder">
                    <option value="newest">Newest First</option>
                    <option value="oldest">Oldest First</option>
                </select>

                <input type="date" class="filter-control" id="fromDate" title="Start Date">
                <input type="date" class="filter-control" id="toDate" title="End Date">

                <button class="btn btn-soft" id="resetFiltersBtn" type="button">
                    <i data-lucide="rotate-ccw"></i>
                    Reset
                </button>
            </div>

            <div class="table-wrap">
                <table id="leaveTable">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Leave Type</th>
                            <th>Leave Dates</th>
                            <th>Duration</th>
                            <th>Balance</th>
                            <th>Status</th>
                            <th>Submitted</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="leaveTableBody"></tbody>
                </table>

                <div class="empty-state" id="emptyState" style="display:none;">
                    <i data-lucide="calendar-x" style="width:42px;height:42px;margin-bottom:10px;"></i>
                    <div style="font-weight:700;margin-bottom:6px;">No leave requests found</div>
                    <div>There are currently no leave applications matching your filters.</div>
                </div>
            </div>
        </section>
    </div>
</main>

<div class="modal-overlay" id="leaveModal">
    <div class="modal">
        <div class="modal-header">
            <div>
                <h2>Leave Request Details</h2>
                <p>Review employee leave application before forwarding to HR.</p>
            </div>
            <button class="modal-close" id="closeModalBtn" type="button">
                <i data-lucide="x"></i>
            </button>
        </div>

        <div class="modal-body">
            <div style="display:grid; gap:18px;">
                <div class="info-card">
                    <h3><i data-lucide="user"></i> Employee Information</h3>
                    <div class="detail-grid">
                        <div class="detail-item">
                            <div class="label">Full Name</div>
                            <div class="value" id="mEmployeeName">-</div>
                        </div>
                        <div class="detail-item">
                            <div class="label">Employee Code</div>
                            <div class="value" id="mEmployeeCode">-</div>
                        </div>
                        <div class="detail-item">
                            <div class="label">Department</div>
                            <div class="value" id="mDepartmentName">-</div>
                        </div>
                        <div class="detail-item">
                            <div class="label">Position</div>
                            <div class="value" id="mPositionName">-</div>
                        </div>
                    </div>
                </div>

                <div class="info-card">
                    <h3><i data-lucide="file-text"></i> Leave Details</h3>
                    <div class="detail-grid">
                        <div class="detail-item">
                            <div class="label">Leave Type</div>
                            <div class="value" id="mLeaveType">-</div>
                        </div>
                        <div class="detail-item">
                            <div class="label">Category</div>
                            <div class="value" id="mLeaveCategory">-</div>
                        </div>
                        <div class="detail-item">
                            <div class="label">Start Date</div>
                            <div class="value" id="mStartDate">-</div>
                        </div>
                        <div class="detail-item">
                            <div class="label">End Date</div>
                            <div class="value" id="mEndDate">-</div>
                        </div>
                        <div class="detail-item">
                            <div class="label">Total Duration</div>
                            <div class="value" id="mTotalDays">-</div>
                        </div>
                        <div class="detail-item">
                            <div class="label">Date Submitted</div>
                            <div class="value" id="mCreatedAt">-</div>
                        </div>
                    </div>
                </div>

                <div class="info-card">
                    <h3><i data-lucide="wallet"></i> Leave Credit Balance</h3>
                    <div class="detail-grid" id="paidCreditsGrid">
                        <div class="detail-item">
                            <div class="label">Total Credits</div>
                            <div class="value" id="mTotalCredits">-</div>
                        </div>
                        <div class="detail-item">
                            <div class="label">Used Credits</div>
                            <div class="value" id="mUsedCredits">-</div>
                        </div>
                        <div class="detail-item">
                            <div class="label">Remaining Credits</div>
                            <div class="value" id="mRemainingCredits">-</div>
                        </div>
                    </div>
                    <div class="muted-note" id="unpaidNote" style="display:none;">
                        This leave type does not consume paid leave credits.
                    </div>
                </div>
            </div>

            <div style="display:grid; gap:18px;">
                <div class="info-card">
                    <h3><i data-lucide="message-square-text"></i> Reason for Leave</h3>
                    <div class="reason-box" id="mReason">-</div>
                </div>

                <div class="info-card">
                    <h3><i data-lucide="paperclip"></i> Attachment</h3>
                    <div class="attachment-box" id="mAttachmentBox">
                        <span class="attachment-empty">No attachment uploaded</span>
                    </div>
                </div>

                <div class="info-card">
                    <h3><i data-lucide="sticky-note"></i> Officer Remarks</h3>
                    <textarea class="notes-textarea" id="officerRemarks" placeholder="Enter remarks for approval or rejection..."></textarea>
                </div>
            </div>
        </div>

        <div class="modal-footer">
            <button class="btn btn-soft" id="closeModalFooterBtn" type="button">
                Close
            </button>

            <div class="footer-right" id="decisionActions">
                <button class="btn btn-danger" id="rejectBtn" type="button">
                    <i data-lucide="x-circle"></i>
                    Reject
                </button>
                <button class="btn btn-success" id="approveBtn" type="button">
                    <i data-lucide="check-circle-2"></i>
                    Approve &amp; Send to HR
                </button>
            </div>

            <div id="decisionLockedNote" style="display:none; color: var(--text-secondary); font-size: 13px; font-weight: 500;">
                This request has already been decided.
            </div>
        </div>
    </div>
</div>

<script src="../../js/officer/leave.js?v=<?php echo time(); ?>"></script>
</body>
</html>