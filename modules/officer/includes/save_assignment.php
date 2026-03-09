<?php
require_once __DIR__ . "/auth_officer.php";
require_once __DIR__ . "/../../../config/config.php";

header('Content-Type: application/json');

$deptId    = $_SESSION['department_id'] ?? null;
$accountId = $_SESSION['account_id'] ?? $_SESSION['AccountID'] ?? $_SESSION['user_id'] ?? null;
$myEmpId   = $_SESSION['employee_id'] ?? $_SESSION['EmployeeID'] ?? null;

$rosterId   = (int)($_POST['roster_id'] ?? 0);
$employeeId = (int)($_POST['employee_id'] ?? 0);
$workDate   = $_POST['work_date'] ?? null;
$shiftCode  = $_POST['shift_code'] ?? null;

if (!$deptId || !$accountId || !$rosterId || !$employeeId || !$workDate || !$shiftCode) {
  echo json_encode(["status" => "error", "message" => "Missing required fields"]);
  exit;
}

try {
  $stmt = $conn->prepare("
    SELECT DepartmentID, Status, WeekStart, WeekEnd
    FROM weekly_roster
    WHERE RosterID=? LIMIT 1
  ");
  $stmt->bind_param("i", $rosterId);
  $stmt->execute();
  $r = $stmt->get_result()->fetch_assoc();

  if (!$r) throw new Exception("Roster not found.");
  if ((int)$r["DepartmentID"] !== (int)$deptId) throw new Exception("Roster department mismatch.");

  $status = $r["Status"];
  if (!in_array($status, ["DRAFT", "RETURNED"], true)) {
    throw new Exception("Roster is locked (Status: $status).");
  }

  if ($workDate < $r["WeekStart"] || $workDate > $r["WeekEnd"]) {
    throw new Exception("Work date is outside the roster period.");
  }

  $dt = DateTime::createFromFormat("Y-m-d", $workDate);
  if ($dt && $dt->format("w") === "0") {
    throw new Exception("Sunday is a fixed day-off and cannot be scheduled.");
  }

  if ($myEmpId && $employeeId === (int)$myEmpId) {
    throw new Exception("You cannot schedule yourself.");
  }

  $map = $conn->prepare("
    SELECT 1
    FROM employmentinformation
    WHERE EmployeeID=? AND DepartmentID=?
    LIMIT 1
  ");
  $map->bind_param("ii", $employeeId, $deptId);
  $map->execute();
  if (!$map->get_result()->fetch_row()) {
    throw new Exception("You cannot schedule this employee (not in your department).");
  }

  $lv = $conn->prepare("
    SELECT lr.LeaveRequestID, lt.LeaveName
    FROM leave_requests lr
    INNER JOIN leave_types lt ON lt.LeaveTypeID = lr.LeaveTypeID
    WHERE lr.EmployeeID = ?
      AND lr.Status IN ('APPROVED_BY_OFFICER','APPROVED_BY_HR')
      AND ? BETWEEN lr.StartDate AND lr.EndDate
    LIMIT 1
  ");
  $lv->bind_param("is", $employeeId, $workDate);
  $lv->execute();
  $leaveRow = $lv->get_result()->fetch_assoc();
  if ($leaveRow) {
    throw new Exception("Employee is on approved leave ({$leaveRow['LeaveName']}) on this date.");
  }

  $sh = $conn->prepare("
    SELECT ShiftCode, StartTime, EndTime
    FROM shift_type
    WHERE ShiftCode=? AND IsActive=1
    LIMIT 1
  ");
  $sh->bind_param("s", $shiftCode);
  $sh->execute();
  $s = $sh->get_result()->fetch_assoc();
  if (!$s) throw new Exception("Invalid shift code.");

  $up = $conn->prepare("
    INSERT INTO roster_assignment (RosterID, EmployeeID, WorkDate, ShiftCode, UpdatedByAccountID)
    VALUES (?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE
      ShiftCode=VALUES(ShiftCode),
      UpdatedByAccountID=VALUES(UpdatedByAccountID),
      UpdatedAt=CURRENT_TIMESTAMP
  ");
  $up->bind_param("iissi", $rosterId, $employeeId, $workDate, $shiftCode, $accountId);
  $up->execute();

  $display = "OFF";
  if ($shiftCode !== "OFF" && !empty($s["StartTime"]) && !empty($s["EndTime"])) {
    $display = $shiftCode . " • " .
      date("h:i A", strtotime($s["StartTime"])) . "-" . date("h:i A", strtotime($s["EndTime"]));
  }

  echo json_encode(["status" => "success", "display" => $display]);

} catch (Exception $e) {
  echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}