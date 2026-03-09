<?php
// modules/ess/claims_apply.php
require_once "includes/auth_employee.php"; 
require_once "../../config/config.php";

$employeeID = $_SESSION['employee_id'] ?? null;
$deptID = $_SESSION['department_id'] ?? null;

if (!$employeeID) { header("Location: ../../login.php"); exit; }

// 1. Fetch Active Cutoff Periods for the Employee's Department
$qPeriods = "SELECT PeriodID, StartDate, EndDate FROM timesheet_period 
             WHERE DepartmentID = ? AND Status != 'FINALIZED' 
             ORDER BY StartDate DESC LIMIT 5";
$stmtP = $conn->prepare($qPeriods);
$stmtP->bind_param("i", $deptID);
$stmtP->execute();
$periods = $stmtP->get_result();

// 2. Quick Stats for the current employee
$qStats = "SELECT 
            SUM(CASE WHEN Status = 'PENDING' THEN 1 ELSE 0 END) as pending_count,
            SUM(CASE WHEN Status = 'PAID' THEN Amount ELSE 0 END) as total_paid
           FROM reimbursement_claims WHERE EmployeeID = ?";
$stmtS = $conn->prepare($qStats);
$stmtS->bind_param("i", $employeeID);
$stmtS->execute();
$stats = $stmtS->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reimbursement Claim | ESS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../css/ess/leave_apply.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        (function() {
            const theme = localStorage.getItem("theme") || "dark";
            if (theme === "dark") document.documentElement.classList.add("dark-mode");
        })();
    </script>
</head>
<body class="theme-controlled">

    <?php include "sidebar.php"; ?>

    <main class="main-content">
        <header class="page-header">
            <div class="header-left">
                <div class="page-title-box">
                    <h1>Expense Claims</h1>
                    <p>Submit your reimbursement requests for approval.</p>
                </div>
            </div>
            <div class="header-right">
                <?php include "theme.php"; ?>
            </div>
        </header>

        <div class="content-wrapper">
            <div class="credits-container">
                <div class="credit-card">
                    <div class="credit-info">
                        <span class="credit-label">Pending Claims</span>
                        <h3 class="credit-value"><?php echo $stats['pending_count'] ?? 0; ?> <small>requests</small></h3>
                    </div>
                    <div class="credit-icon" style="background: rgba(245, 158, 11, 0.1); color: var(--brand-yellow);">
                        <i data-lucide="clock"></i>
                    </div>
                </div>
                <div class="credit-card">
                    <div class="credit-info">
                        <span class="credit-label">Total Reimbursed</span>
                        <h3 class="credit-value" style="color: var(--brand-green);">₱<?php echo number_format($stats['total_paid'] ?? 0, 2); ?></h3>
                    </div>
                    <div class="credit-icon">
                        <i data-lucide="banknote"></i>
                    </div>
                </div>
            </div>

            <div class="form-grid">
                <div class="form-card">
                    <div class="card-header">
                        <i data-lucide="receipt-text"></i>
                        <h2>New Reimbursement</h2>
                    </div>
                    
                    <form id="claimForm" enctype="multipart/form-data">
                        <div class="input-row split">
                            <div class="input-group">
                                <label><i data-lucide="calendar-days"></i> Cutoff Period</label>
                                <select name="period_id" id="period_id" required>
                                    <option value="" disabled selected>Select period...</option>
                                    <?php while($p = $periods->fetch_assoc()): ?>
                                        <option value="<?php echo $p['PeriodID']; ?>" 
                                                data-start="<?php echo $p['StartDate']; ?>" 
                                                data-end="<?php echo $p['EndDate']; ?>">
                                            <?php echo date('M d', strtotime($p['StartDate'])) . " - " . date('M d', strtotime($p['EndDate'])); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="input-group">
                                <label><i data-lucide="tag"></i> Category</label>
                                <select name="category" required>
                                    <option value="" disabled selected>Select category...</option>
                                    <option value="GAS">Gasoline / Fuel</option>
                                    <option value="LOAD">Communication / Load</option>
                                    <option value="TRAVEL">Travel / Fare</option>
                                    <option value="SUPPLIES">Office Supplies</option>
                                    <option value="OTHERS">Others</option>
                                </select>
                            </div>
                        </div>

                        <div class="input-row split">
                            <div class="input-group">
                                <label><i data-lucide="calendar"></i> Expense Date</label>
                                <input type="date" name="claim_date" id="claim_date" required onclick="this.showPicker()">
                            </div>
                            <div class="input-group">
                                <label><i data-lucide=" PhilippinePeso"></i> Amount</label>
                                <input type="number" name="amount" step="0.01" min="1" placeholder="0.00" required>
                            </div>
                        </div>

                        <div class="input-row">
                            <div class="input-group">
                                <label><i data-lucide="align-left"></i> Description / Purpose</label>
                                <textarea name="description" rows="3" placeholder="Explain the purpose of this expense..." required></textarea>
                            </div>
                        </div>

                        <div class="input-row">
                            <div class="input-group">
                                <label><i data-lucide="image"></i> Receipt Image <small>(Required)</small></label>
                                <div class="file-upload-box">
                                    <input type="file" name="receipt" id="receipt" accept="image/*,.pdf" required>
                                    <p id="fileName">Click to upload photo of receipt</p>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="submit-btn" id="submitBtn">
                            <span>Submit Claim</span>
                            <i data-lucide="send"></i>
                        </button>
                    </form>
                </div>

                <div class="instruction-card">
                    <h3>Reminders</h3>
                    <ul class="reminders-list">
                        <li><i data-lucide="info"></i> Expense date must be within the selected cutoff period.</li>
                        <li><i data-lucide="camera"></i> Ensure the receipt photo is clear and readable.</li>
                        <li><i data-lucide="shield-check"></i> Claims are subject to internal audit and manager approval.</li>
                    </ul>
                </div>
            </div>
        </div>
    </main>

    <script>
        lucide.createIcons();

        // UI Logic: Restrict Claim Date based on Period
        $('#period_id').on('change', function() {
            const start = $(this).find(':selected').data('start');
            const end = $(this).find(':selected').data('end');
            $('#claim_date').attr('min', start).attr('max', end).val(start);
        });

        $('#receipt').on('change', function() {
            const name = $(this).val().split('\\').pop();
            $('#fileName').text(name || "Click to upload photo of receipt");
        });

        $('#claimForm').on('submit', function(e) {
            e.preventDefault();
            const isDark = document.body.classList.contains('dark-mode');

            Swal.fire({
                title: 'Submit Expense Claim?',
                text: "Please double check the amount and details.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#10b981',
                background: isDark ? '#1e293b' : '#ffffff',
                color: isDark ? '#f8fafc' : '#1e293b'
            }).then((result) => {
                if (result.isConfirmed) {
                    let formData = new FormData(this);
                    $.ajax({
                        url: 'includes/claim_submit.php',
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        dataType: 'json',
                        success: function(res) {
                            if(res.success) {
                                Swal.fire({ title: 'Submitted!', text: 'Claim request sent.', icon: 'success', background: isDark ? '#1e293b' : '#fff' })
                                .then(() => location.href='claims_history.php');
                            } else {
                                Swal.fire('Error', res.error, 'error');
                            }
                        }
                    });
                }
            });
        });
    </script>
</body>
</html>