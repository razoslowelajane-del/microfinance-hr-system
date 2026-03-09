<?php
require_once __DIR__ . "/includes/auth_officer.php";

$deptName = $_SESSION['department_name'] ?? 'My Department';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Claims Requests | <?php echo htmlspecialchars($deptName); ?></title>

    <link rel="icon" type="image/png" href="../../img/logo.png">
    <link rel="stylesheet" href="../../css/officer/claims.css?v=<?php echo time(); ?>">

    <script src="https://cdn.jsdelivr.net/npm/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

   
</head>
<body>
<?php include __DIR__ . "/sidebar.php"; ?>

<main class="main-content">
    <div class="page-shell">
        <div class="page-header">
            <div class="page-title-wrap">
                <h1><i data-lucide="receipt-text"></i> Claims Requests</h1>
                <p>Review and endorse employee reimbursement claims for <?php echo htmlspecialchars($deptName); ?>.</p>
            </div>

            <div class="header-actions">
                <div class="search-box">
                    <i data-lucide="search"></i>
                    <input type="text" id="globalSearch" placeholder="Search employee, code, category...">
                </div>

                <button class="btn btn-soft" id="refreshBtn" type="button">
                    <i data-lucide="refresh-cw"></i> Refresh
                </button>

                <?php include __DIR__ . "/theme.php"; ?>
            </div>
        </div>

        <div class="summary-mini-grid">
            <div class="summary-mini-card">
                <div>
                    <h3 id="countPending">0</h3>
                    <p>Pending</p>
                </div>
                <div class="icon icon-warning"><i data-lucide="clock-3"></i></div>
            </div>

            <div class="summary-mini-card">
                <div>
                    <h3 id="countSent">0</h3>
                    <p>Sent to HR</p>
                </div>
                <div class="icon icon-primary"><i data-lucide="send"></i></div>
            </div>

            <div class="summary-mini-card">
                <div>
                    <h3 id="countRejected">0</h3>
                    <p>Rejected</p>
                </div>
                <div class="icon icon-danger"><i data-lucide="x-circle"></i></div>
            </div>

            <div class="summary-mini-card">
                <div>
                    <h3 id="countAll">0</h3>
                    <p>Total Visible Scope</p>
                </div>
                <div class="icon icon-success"><i data-lucide="files"></i></div>
            </div>
        </div>

        <section class="panel">
            <div class="tabs">
                <button class="tab-btn active" data-status="PENDING">Pending</button>
                <button class="tab-btn" data-status="APPROVED_BY_OFFICER">Sent to HR</button>
                <button class="tab-btn" data-status="REJECTED">Rejected</button>
                <button class="tab-btn" data-status="ALL">All</button>
            </div>

            <div class="filters">
                <input type="text" class="filter-control" id="searchInput" placeholder="Search employee name, code, category...">

                <select class="filter-control" id="categoryFilter">
                    <option value="ALL">All Claim Types</option>
                    <option value="GAS">Gas</option>
                    <option value="LOAD">Load</option>
                    <option value="TRAVEL">Travel</option>
                    <option value="SUPPLIES">Supplies</option>
                    <option value="OTHERS">Others</option>
                </select>

                <input type="date" class="filter-control" id="fromDate">

                <button class="btn btn-soft" id="resetFiltersBtn" type="button">
                    <i data-lucide="rotate-ccw"></i> Reset
                </button>
            </div>

            <div class="table-wrap">
                <table id="claimsTable">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Department</th>
                            <th>Claim Type</th>
                            <th>Amount</th>
                            <th>Claim Date</th>
                            <th>Status</th>
                            <th>Submitted</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="claimsTableBody"></tbody>
                </table>

                <div class="empty-state" id="emptyState">
                    <i data-lucide="inbox" style="width:42px;height:42px;margin-bottom:10px;"></i>
                    <div style="font-weight:700;margin-bottom:6px;">No claims found</div>
                    <div>There are no records matching your current filter.</div>
                </div>
            </div>
        </section>
    </div>
</main>

<div class="modal-overlay" id="claimModal">
    <div class="modal">
        <div class="modal-header">
            <div>
                <h2>Claim Request Details</h2>
                <p>Review and endorse reimbursement request.</p>
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
                    <h3><i data-lucide="file-text"></i> Claim Details</h3>
                    <div class="detail-grid">
                        <div class="detail-item">
                            <div class="label">Category</div>
                            <div class="value" id="mCategory">-</div>
                        </div>
                        <div class="detail-item">
                            <div class="label">Amount</div>
                            <div class="value" id="mAmount">-</div>
                        </div>
                        <div class="detail-item">
                            <div class="label">Claim Date</div>
                            <div class="value" id="mClaimDate">-</div>
                        </div>
                        <div class="detail-item">
                            <div class="label">Status</div>
                            <div class="value" id="mStatusText">-</div>
                        </div>
                    </div>
                </div>

                <div class="info-card">
                    <h3><i data-lucide="message-square-text"></i> Description</h3>
                    <div class="reason-box" id="mDescription">-</div>
                </div>
            </div>

            <div style="display:grid; gap:18px;">
                <div class="info-card">
                    <h3><i data-lucide="image"></i> Proof</h3>
                    <div class="proof-box" id="mProofBox"></div>
                </div>

                <div class="info-card">
                    <h3><i data-lucide="sticky-note"></i> Officer Remarks</h3>
                    <textarea class="notes-textarea" id="officerRemarks" placeholder="Enter officer remarks..."></textarea>
                </div>

                <div class="info-card">
                    <h3><i data-lucide="scroll-text"></i> HR Notes</h3>
                    <div class="modal-note-box" id="mHrNotes">No HR notes yet.</div>
                </div>
            </div>
        </div>

        <div class="modal-footer">
            <button class="btn btn-soft" id="closeModalFooterBtn" type="button">Close</button>

            <div class="footer-right" id="modalActionButtons">
                <button class="btn btn-danger" id="rejectBtn" type="button">Reject</button>
                <button class="btn btn-success" id="approveBtn" type="button">Approve</button>
            </div>
        </div>
    </div>
</div>

<script src="../../js/officer/claims.js?v=<?php echo time(); ?>"></script>
</body>
</html>