<?php
require_once "../../../config/config.php";
session_start();

$rid = $_GET['id'] ?? null;
$empId = $_SESSION['employee_id'];

$sql = "SELECT lr.*, lt.LeaveName, ua1.Username as OfficerName, ua2.Username as HRName
        FROM leave_requests lr 
        JOIN leave_types lt ON lr.LeaveTypeID = lt.LeaveTypeID 
        LEFT JOIN useraccounts ua1 ON lr.OfficerApprovedBy = ua1.AccountID
        LEFT JOIN useraccounts ua2 ON lr.HRApprovedBy = ua2.AccountID
        WHERE lr.LeaveRequestID = ? AND lr.EmployeeID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $rid, $empId);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

if(!$data) { echo "Details not found."; exit; }
?>

<div class="detail-section" style="display:flex; flex-direction:column; gap:1.5rem;">
    <div>
        <label style="font-size:0.75rem; text-transform:uppercase; color:var(--text-muted); font-weight:700;">Application Status</label>
        <div style="margin-top:5px;"><span class="status-badge status-<?php echo $data['Status']; ?>"><?php echo str_replace('_', ' ', $data['Status']); ?></span></div>
    </div>
    
    <div>
        <label style="font-size:0.75rem; text-transform:uppercase; color:var(--text-muted); font-weight:700;">Reason</label>
        <p style="margin-top:5px; font-weight:500; line-height:1.6;"><?php echo htmlspecialchars($data['Reason']); ?></p>
    </div>

    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; background:var(--background); padding:1rem; border-radius:12px;">
        <div><small style="color:var(--text-muted);">Duration</small><div style="font-weight:700;"><?php echo $data['TotalDays']; ?> Days</div></div>
        <div><small style="color:var(--text-muted);">Filed Date</small><div style="font-weight:700;"><?php echo date('M d, Y', strtotime($data['CreatedAt'])); ?></div></div>
    </div>

    <div>
        <label style="font-size:0.75rem; text-transform:uppercase; color:var(--text-muted); font-weight:700;">Approval Trail</label>
        <ul style="list-style:none; padding:0; margin-top:10px; display:flex; flex-direction:column; gap:10px;">
            <li style="display:flex; align-items:center; gap:10px; font-size:0.9rem;">
                <i data-lucide="check-circle-2" style="width:16px; color:<?php echo $data['OfficerApprovedBy'] ? 'var(--brand-green)' : 'var(--text-muted)'; ?>"></i>
                <span>Officer: <strong><?php echo $data['OfficerName'] ?? 'Pending Review'; ?></strong></span>
            </li>
            <li style="display:flex; align-items:center; gap:10px; font-size:0.9rem;">
                <i data-lucide="check-circle-2" style="width:16px; color:<?php echo $data['HRApprovedBy'] ? 'var(--brand-green)' : 'var(--text-muted)'; ?>"></i>
                <span>HR Manager: <strong><?php echo $data['HRName'] ?? 'Pending Review'; ?></strong></span>
            </li>
        </ul>
    </div>
</div>