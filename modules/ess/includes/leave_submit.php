<?php
header('Content-Type: application/json');
require_once "../../../config/config.php";
session_start();

// ✅ Lowercase 'employee_id' based on your login action
$employeeID = $_SESSION['employee_id'] ?? null;

if (!$employeeID) { 
    echo json_encode(["success" => false, "error" => "Session expired. Please login again."]); 
    exit; 
}

// Check for existing active/pending requests (Security Block)
$qActive = "SELECT LeaveRequestID FROM leave_requests 
            WHERE EmployeeID = ? AND Status IN ('PENDING', 'APPROVED_BY_OFFICER')";
$stmtCheck = $conn->prepare($qActive);
$stmtCheck->bind_param("i", $employeeID);
$stmtCheck->execute();
if ($stmtCheck->get_result()->num_rows > 0) {
    echo json_encode(["success" => false, "error" => "You have a pending request. Please wait for it to be processed."]);
    exit;
}

// Get POST data
$leaveType = $_POST['leave_type'] ?? null;
$start = $_POST['start_date'] ?? null;
$end = $_POST['end_date'] ?? null;
$reason = $_POST['reason'] ?? '';

if (!$leaveType || !$start || !$end) {
    echo json_encode(["success" => false, "error" => "Please fill in all required fields."]);
    exit;
}

// 1. Compute Days
$d1 = new DateTime($start);
$d2 = new DateTime($end);
$totalDays = $d1->diff($d2)->days + 1;

// 2. Validate Credits
$qCheck = "SELECT RemainingCredits FROM employee_leave_balances 
           WHERE EmployeeID = ? AND LeaveTypeID = ? AND Year = YEAR(?)";
$stmt = $conn->prepare($qCheck);
$stmt->bind_param("iis", $employeeID, $leaveType, $start);
$stmt->execute();
$bal = $stmt->get_result()->fetch_assoc();

// Allow LWOP (Type 5) even if credits are 0
if ($leaveType != 5) {
    if (!$bal || $bal['RemainingCredits'] < $totalDays) {
        $available = $bal['RemainingCredits'] ?? 0;
        echo json_encode(["success" => false, "error" => "Insufficient leave credits. Available: " . $available]);
        exit;
    }
}

// 3. Overlap Check
$qOverlap = "SELECT LeaveRequestID FROM leave_requests 
             WHERE EmployeeID = ? AND Status IN ('PENDING','APPROVED_BY_OFFICER','APPROVED_BY_HR')
             AND ((StartDate <= ? AND EndDate >= ?) OR (StartDate <= ? AND EndDate >= ?))";
$stmtO = $conn->prepare($qOverlap);
$stmtO->bind_param("issss", $employeeID, $end, $start, $start, $end);
$stmtO->execute();
if ($stmtO->get_result()->num_rows > 0) {
    echo json_encode(["success" => false, "error" => "Overlapping leave request detected."]);
    exit;
}

// 4. File Upload (Optional)
$attachmentPath = null;
if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] == 0) {
    $dir = "../../../uploads/leaves/";
    if (!is_dir($dir)) mkdir($dir, 0777, true);
    $ext = pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION);
    $filename = "leave_" . time() . "_" . $employeeID . "." . $ext;
    if (move_uploaded_file($_FILES['attachment']['tmp_name'], $dir . $filename)) {
        $attachmentPath = "uploads/leaves/" . $filename;
    }
}

// 5. Insert to DB
// Question marks: 1.EmpID, 2.Type, 3.Start, 4.End, 5.Total, 6.Reason, 7.Status, 8.Attach
$sql = "INSERT INTO leave_requests (EmployeeID, LeaveTypeID, StartDate, EndDate, TotalDays, Reason, Status, AttachmentPath, CreatedAt) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";

$stmtIns = $conn->prepare($sql);
$status = 'PENDING';

/** * ✅ FIX: 8 params needed for 8 question marks 
 * Types: i (EmpID), i (Type), s (Start), s (End), d (Total), s (Reason), s (Status), s (Attach)
 */
$stmtIns->bind_param("iissdsss", $employeeID, $leaveType, $start, $end, $totalDays, $reason, $status, $attachmentPath);

if ($stmtIns->execute()) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "error" => "Database Error: " . $conn->error]);
}

$stmtIns->close();
$conn->close();