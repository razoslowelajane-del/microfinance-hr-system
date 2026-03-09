<?php
require_once __DIR__ . "/includes/auth_officer.php";

$deptName = $_SESSION['department_name'] ?? 'HR Department';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Claims Review | <?php echo htmlspecialchars($deptName); ?></title>

    <link rel="icon" type="image/png" href="../../img/logo.png">
    <link rel="stylesheet" href="../../css/officer/claims.css?v=<?php echo time(); ?>">

    <script src="https://cdn.jsdelivr.net/npm/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        .swal2-container { z-index: 9999 !important; }

        .status-paid {
            background: rgba(99, 102, 241, 0.10);
            color: #6366f1;
        }

        .summary-mini-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .summary-mini-card {
            background: var(--surface);
            border: 1px solid var(--border-color);
            border-radius: 14px;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
        }

        .summary-mini-card .icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--background);
        }

        .summary-mini-card h3 {
            margin: 0;
            font-size: 24px;
            font-weight: 700;
            color: var(--text-primary);
        }

        .summary-mini-card p {
            margin: 4px 0 0;
            font-size: 13px;
            color: var(--text-secondary);
        }

        .modal-note-box {
            background: var(--background);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            padding: 12px 14px;
            font-size: 13px;
            color: var(--text-secondary);
            line-height: 1.5;
        }

        .note-label {
            display: block;
            font-size: 12px;
            color: var(--text-tertiary);
            margin-bottom: 6px;
        }

        .hidden {
            display: none !important;
        }
    </style>
</head>
<body>
<?php include __DIR__ . "/sidebar.php"; ?>

<main class="main-content">
    <div class="page-shell">
        <div class="page-header">
            <div class="page-title-wrap">
                <h1><i data-lucide="receipt-text"></i> Claims Review</h1>
                <p>HR Manager review and approval of reimbursement claims endorsed by department officers.</p>
            </div>

            <div class="header-actions">
                <div class="search-box">
                    <i data-lucide="search"></i>
                    <input type="text" id="globalSearch" placeholder="Search employee, code, department...">
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
                    <h3 id="countForApproval">0</h3>
                    <p>For Approval</p>
                </div>
                <div class="icon icon-warning"><i data-lucide="clipboard-check"></i></div>
            </div>

            <div class="summary-mini-card">
                <div>
                    <h3 id="countApproved">0</h3>
                    <p>Approved by HR Manager</p>
                </div>
                <div class="icon icon-success"><i data-lucide="badge-check"></i></div>
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
                    <h3 id="countPaid">0</h3>
                    <p>Paid</p>
                </div>
                <div class="icon icon-primary"><i data-lucide="wallet"></i></div>
            </div>
        </div>

        <section class="panel">
            <div class="tabs">
                <button class="tab-btn active" data-status="APPROVED_BY_OFFICER">For Approval</button>
                <button class="tab-btn" data-status="APPROVED_BY_HR">Approved</button>
                <button class="tab-btn" data-status="REJECTED">Rejected</button>
                <button class="tab-btn" data-status="PAID">Paid</button>
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
                <h2>Claim Review Details</h2>
                <p>Review officer-approved reimbursement request.</p>
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
                    <h3><i data-lucide="sticky-note"></i> Officer Notes</h3>
                    <div class="modal-note-box" id="mOfficerNotes">No officer notes.</div>
                </div>

                <div class="info-card">
                    <h3><i data-lucide="notebook-pen"></i> HR Manager Remarks</h3>
                    <textarea class="notes-textarea" id="hrRemarks" placeholder="Enter HR Manager remarks..."></textarea>
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

<script src="../../js/manager/claims.js?v=<?php echo time(); ?>"></script>
</body>
</html>