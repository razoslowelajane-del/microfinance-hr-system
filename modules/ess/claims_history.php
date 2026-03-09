<?php
// modules/ess/claims_history.php
require_once "includes/auth_employee.php"; 
require_once "../../config/config.php";

$employeeID = $_SESSION['employee_id'] ?? null;
if (!$employeeID) { header("Location: ../../login.php"); exit; }

$qHistory = "SELECT rc.*, tp.StartDate, tp.EndDate 
             FROM reimbursement_claims rc
             JOIN timesheet_period tp ON rc.PeriodID = tp.PeriodID
             WHERE rc.EmployeeID = ? 
             ORDER BY rc.CreatedAt DESC";
$stmt = $conn->prepare($qHistory);
$stmt->bind_param("i", $employeeID);
$stmt->execute();
$history = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Claims History | ESS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../css/ess/leave_history.css">
    
    <style>
        /* Force Drawer to be scrollable */
        .side-drawer {
            position: fixed;
            inset: 0;
            z-index: 9999;
            visibility: hidden;
            pointer-events: none;
        }
        .side-drawer.active {
            visibility: visible;
            pointer-events: all;
        }
        .drawer-overlay {
            position: absolute;
            inset: 0;
            background: rgba(0,0,0,0.5);
            backdrop-filter: blur(4px);
            opacity: 0;
            transition: 0.3s;
        }
        .side-drawer.active .drawer-overlay { opacity: 1; }
        
        .drawer-panel {
            position: absolute;
            right: -500px;
            top: 0;
            bottom: 0;
            width: 500px;
            background: var(--surface);
            box-shadow: -5px 0 30px rgba(0,0,0,0.2);
            transition: 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            flex-direction: column; /* Para hiwalay header sa body */
        }
        .side-drawer.active .drawer-panel { right: 0; }

        .drawer-header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: var(--surface);
            z-index: 10;
        }

        /* Dito ang magic para sa scroll */
        .drawer-body {
            flex: 1;
            overflow-y: auto; /* Enable scroll */
            padding: 2rem;
            scrollbar-width: thin;
            scrollbar-color: var(--brand-green) transparent;
        }
        
        .drawer-body::-webkit-scrollbar { width: 6px; }
        .drawer-body::-webkit-scrollbar-thumb { background: var(--brand-green); border-radius: 10px; }

        .close-btn {
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
        }

        /* Image styling inside drawer */
        .receipt-preview-wrapper {
            margin-top: 15px;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid var(--border-color);
            background: #000;
        }
        .receipt-preview-wrapper img {
            width: 100%;
            height: auto;
            display: block;
            transition: 0.3s;
        }
        .receipt-preview-wrapper img:hover {
            transform: scale(1.02);
            cursor: zoom-in;
        }
    </style>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="theme-controlled">
    <?php include "sidebar.php"; ?>
    
    <main class="main-content">
        <header class="page-header">
            <div class="header-left">
                <div class="page-title-box">
                    <h1>My Claims History</h1>
                    <p>Track your submitted reimbursement requests.</p>
                </div>
            </div>
            <div class="header-right"><?php include "theme.php"; ?></div>
        </header>

        <div class="content-wrapper">
            <div class="table-card">
                <div class="table-header">
                    <div class="th-left">
                        <i data-lucide="receipt" class="text-green"></i>
                        <h2>Expense Log</h2>
                    </div>
                    <a href="claims_apply.php" class="btn-new-request"><i data-lucide="plus"></i> New Claim</a>
                </div>
                <div class="table-responsive">
                    <table class="history-table">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Expense Date</th>
                                <th>Amount</th>
                                <th>Cutoff Period</th>
                                <th>Status</th>
                                <th style="text-align:center;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($history->num_rows > 0): ?>
                                <?php while($row = $history->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong><?php echo $row['Category']; ?></strong></td>
                                        <td><?php echo date('M d, Y', strtotime($row['ClaimDate'])); ?></td>
                                        <td><span style="font-weight:800; color:var(--brand-green);">₱<?php echo number_format($row['Amount'], 2); ?></span></td>
                                        <td><small><?php echo date('M d', strtotime($row['StartDate'])) . " - " . date('M d', strtotime($row['EndDate'])); ?></small></td>
                                        <td>
                                            <span class="status-badge status-<?php echo $row['Status']; ?>">
                                                <?php echo str_replace('_', ' ', $row['Status']); ?>
                                            </span>
                                        </td>
                                        <td style="text-align:center;">
                                            <button class="btn-view" onclick="viewClaim(<?php echo $row['ClaimID']; ?>)">
                                                <i data-lucide="eye"></i> Details
                                            </button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="6" class="empty-row" style="text-align:center; padding: 4rem;">No records found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <div id="requestDrawer" class="side-drawer">
        <div class="drawer-overlay" onclick="closeRequestDetails()"></div>
        <div class="drawer-panel">
            <div class="drawer-header">
                <h3>Request Details</h3>
                <button class="close-btn" onclick="closeRequestDetails()"><i data-lucide="x"></i></button>
            </div>
            <div id="drawerContent" class="drawer-body">
                </div>
        </div>
    </div>

    <script>
        lucide.createIcons();

        function viewClaim(claimId) {
            $('#requestDrawer').addClass('active');
            $('#drawerContent').html('<div style="text-align:center; padding:2rem;">Fetching data...</div>');
            
            $.get('includes/get_claim_details.php', { id: claimId }, function(res) {
                $('#drawerContent').html(res);
                lucide.createIcons(); 
            });
        }

        function closeRequestDetails() {
            $('#requestDrawer').removeClass('active');
        }
    </script>
</body>
</html>