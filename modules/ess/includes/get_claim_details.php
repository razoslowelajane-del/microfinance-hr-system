<?php
// modules/ess/includes/get_claim_details.php
require_once "../../../config/config.php";
session_start();

$claimID = $_GET['id'] ?? null;
$employeeID = $_SESSION['employee_id'] ?? null;

if (!$claimID || !$employeeID) {
    echo "<div class='error-msg'>Invalid request or session expired.</div>";
    exit;
}

// Fetch Claim Details joined with Period and Approvers
$sql = "SELECT rc.*, 
               tp.StartDate, tp.EndDate,
               ua1.Username as OfficerName, 
               ua2.Username as HRName
        FROM reimbursement_claims rc
        JOIN timesheet_period tp ON rc.PeriodID = tp.PeriodID
        LEFT JOIN useraccounts ua1 ON rc.OfficerApprovedBy = ua1.AccountID
        LEFT JOIN useraccounts ua2 ON rc.HRApprovedBy = ua2.AccountID
        WHERE rc.ClaimID = ? AND rc.EmployeeID = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $claimID, $employeeID);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

if ($data): ?>
    <div class="detail-section" style="display:flex; flex-direction:column; gap:1.2rem;">
        
        <div style="display:flex; justify-content:space-between; align-items:center; background:var(--background); padding:1rem; border-radius:12px; border-left:4px solid var(--brand-green);">
            <div>
                <label style="font-size:0.7rem; text-transform:uppercase; color:var(--text-muted); font-weight:700; display:block;">Current Status</label>
                <span class="status-badge status-<?php echo $data['Status']; ?>" style="margin-top:5px;">
                    <?php echo str_replace('_', ' ', $data['Status']); ?>
                </span>
            </div>
            <div style="text-align:right;">
                <label style="font-size:0.7rem; text-transform:uppercase; color:var(--text-muted); font-weight:700; display:block;">Amount</label>
                <strong style="font-size:1.2rem; color:var(--brand-green);">₱<?php echo number_format($data['Amount'], 2); ?></strong>
            </div>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
            <div>
                <label style="font-size:0.7rem; font-weight:700; color:var(--text-muted); text-transform:uppercase;">Category</label>
                <p style="font-weight:600;"><?php echo $data['Category']; ?></p>
            </div>
            <div>
                <label style="font-size:0.7rem; font-weight:700; color:var(--text-muted); text-transform:uppercase;">Expense Date</label>
                <p style="font-weight:600;"><?php echo date('M d, Y', strtotime($data['ClaimDate'])); ?></p>
            </div>
            <div style="grid-column: span 2;">
                <label style="font-size:0.7rem; font-weight:700; color:var(--text-muted); text-transform:uppercase;">For Cutoff Period</label>
                <p style="font-weight:600;"><?php echo date('M d', strtotime($data['StartDate'])) . " - " . date('M d, Y', strtotime($data['EndDate'])); ?></p>
            </div>
        </div>

        <div>
            <label style="font-size:0.7rem; font-weight:700; color:var(--text-muted); text-transform:uppercase;">Description / Purpose</label>
            <p style="margin-top:5px; line-height:1.5; background:var(--background); padding:10px; border-radius:8px; font-size:0.9rem;">
                <?php echo htmlspecialchars($data['Description']); ?>
            </p>
        </div>

        <div>
            <label style="font-size:0.7rem; font-weight:700; color:var(--text-muted); text-transform:uppercase;">Receipt Attachment</label>
            <div style="margin-top:10px; border:1px solid var(--border-color); border-radius:12px; overflow:hidden;">
                <img src="../../<?php echo $data['ReceiptImage']; ?>" alt="Receipt" style="width:100%; height:auto; display:block; cursor:zoom-in;" onclick="window.open(this.src)">
            </div>
        </div>

        <div style="border-top:1px solid var(--border-color); padding-top:1rem;">
            <label style="font-size:0.7rem; font-weight:700; color:var(--text-muted); text-transform:uppercase; display:block; margin-bottom:10px;">Approval Progress</label>
            <div style="display:flex; flex-direction:column; gap:8px;">
                <div style="display:flex; align-items:center; gap:10px; font-size:0.85rem;">
                    <i data-lucide="<?php echo $data['OfficerApprovedBy'] ? 'check-circle' : 'circle'; ?>" style="width:16px; color:<?php echo $data['OfficerApprovedBy'] ? 'var(--brand-green)' : 'var(--text-muted)'; ?>"></i>
                    <span>Officer Review: <strong><?php echo $data['OfficerName'] ?? 'Pending'; ?></strong></span>
                </div>
                <div style="display:flex; align-items:center; gap:10px; font-size:0.85rem;">
                    <i data-lucide="<?php echo $data['HRApprovedBy'] ? 'check-circle' : 'circle'; ?>" style="width:16px; color:<?php echo $data['HRApprovedBy'] ? 'var(--brand-green)' : 'var(--text-muted)'; ?>"></i>
                    <span>HR Approval: <strong><?php echo $data['HRName'] ?? 'Pending'; ?></strong></span>
                </div>
            </div>
        </div>

        <?php if ($data['Status'] == 'REJECTED'): ?>
            <div style="background:rgba(244, 63, 94, 0.1); border:1px solid var(--brand-red); padding:1rem; border-radius:12px; margin-top:5px;">
                <label style="color:var(--brand-red); font-weight:800; font-size:0.7rem; text-transform:uppercase;">Reason for Rejection</label>
                <p style="font-size:0.9rem; margin-top:5px;"><?php echo htmlspecialchars($data['HRNotes'] ?? $data['OfficerNotes'] ?? 'No notes provided.'); ?></p>
            </div>
        <?php endif; ?>

    </div>
<?php else: ?>
    <div style="text-align:center; padding:2rem;">
        <i data-lucide="alert-circle" style="width:48px; height:48px; color:var(--text-muted);"></i>
        <p>Claim details not found.</p>
    </div>
<?php endif; ?>