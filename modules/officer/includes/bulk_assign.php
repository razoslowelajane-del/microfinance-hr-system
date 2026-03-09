<?php
require_once __DIR__ . "/auth_officer.php";
require_once __DIR__ . "/../../../config/config.php";

header('Content-Type: application/json');

function bind_params(mysqli_stmt $stmt, string $types, array &$params): void {
  $bind = [];
  $bind[] = $types;
  foreach ($params as $k => $v) $bind[] = &$params[$k];
  call_user_func_array([$stmt, 'bind_param'], $bind);
}

$deptId    = $_SESSION['department_id'] ?? null;
$accountId = $_SESSION['account_id'] ?? $_SESSION['AccountID'] ?? $_SESSION['user_id'] ?? null;
$myEmpId   = $_SESSION['employee_id'] ?? $_SESSION['EmployeeID'] ?? null;

$rosterId  = (int)($_POST['roster_id'] ?? 0);
$mode      = strtoupper(trim($_POST['mode'] ?? 'FIXED'));

$startDate = $_POST['start_date'] ?? null;
$endDate   = $_POST['end_date'] ?? null;

$shiftCode = $_POST['shift_code'] ?? null;

if (!$deptId || !$accountId || $rosterId <= 0 || !$startDate || !$endDate) {
  echo json_encode(["status" => "error", "message" => "Missing required fields"]);
  exit;
}

$txStarted = false;

try {
  $stmt = $conn->prepare("
    SELECT DepartmentID, Status, WeekStart, WeekEnd
    FROM weekly_roster
    WHERE RosterID=? LIMIT 1
  ");
  if (!$stmt) throw new Exception("Prepare failed (roster): " . $conn->error);

  $stmt->bind_param("i", $rosterId);
  $stmt->execute();
  $r = $stmt->get_result()->fetch_assoc();

  if (!$r) throw new Exception("Roster not found.");
  if ((int)$r["DepartmentID"] !== (int)$deptId) throw new Exception("Roster department mismatch.");

  $status = strtoupper((string)$r["Status"]);
  if (!in_array($status, ["DRAFT", "RETURNED"], true)) {
    throw new Exception("Roster is locked (Status: $status).");
  }

  $ws = $r["WeekStart"];
  $we = $r["WeekEnd"];
  if ($startDate < $ws) $startDate = $ws;
  if ($endDate > $we)   $endDate = $we;
  if ($startDate > $endDate) throw new Exception("Invalid date range.");

  if ($mode === "FIXED") {
    if (!$shiftCode) throw new Exception("Missing shift_code.");

    $sh = $conn->prepare("SELECT ShiftCode FROM shift_type WHERE ShiftCode=? AND IsActive=1 LIMIT 1");
    if (!$sh) throw new Exception("Prepare failed (shift check): " . $conn->error);

    $sh->bind_param("s", $shiftCode);
    $sh->execute();
    if (!$sh->get_result()->fetch_assoc()) throw new Exception("Invalid shift code.");
  } elseif ($mode !== "CLEAR") {
    throw new Exception("Invalid mode.");
  }

  $dates = [];
  $cur = DateTime::createFromFormat("Y-m-d", $startDate);
  $end = DateTime::createFromFormat("Y-m-d", $endDate);
  if (!$cur || !$end) throw new Exception("Invalid dates.");

  $cur->setTime(0, 0, 0);
  $end->setTime(0, 0, 0);

  while ($cur <= $end) {
    if ($cur->format("w") !== "0") $dates[] = $cur->format("Y-m-d");
    $cur->modify("+1 day");
  }

  if (!count($dates)) {
    echo json_encode([
      "status" => "success",
      "message" => "No schedulable dates (Sundays skipped).",
      "updated_cells" => 0
    ]);
    exit;
  }

  $empSql = "
    SELECT e.EmployeeID
    FROM employee e
    JOIN employmentinformation ei ON e.EmployeeID = ei.EmployeeID
    WHERE ei.DepartmentID = ?
  ";
  $empStmt = $conn->prepare($empSql);
  if (!$empStmt) throw new Exception("Prepare failed (employee list): " . $conn->error);

  $empStmt->bind_param("i", $deptId);
  $empStmt->execute();
  $empRes = $empStmt->get_result();

  $empIds = [];
  while ($row = $empRes->fetch_assoc()) {
    $eid = (int)$row["EmployeeID"];
    if ($myEmpId && $eid === (int)$myEmpId) continue;
    $empIds[] = $eid;
  }

  if (!count($empIds)) {
    echo json_encode([
      "status" => "success",
      "message" => "No employees to update.",
      "updated_cells" => 0
    ]);
    exit;
  }

  $conn->begin_transaction();
  $txStarted = true;

  $count = 0;

  if ($mode === "CLEAR") {
    $placeholders = implode(",", array_fill(0, count($empIds), "?"));
    $types = "iss" . str_repeat("i", count($empIds));

    $sql = "
      DELETE FROM roster_assignment
      WHERE RosterID = ?
        AND WorkDate BETWEEN ? AND ?
        AND EmployeeID IN ($placeholders)
    ";

    $stmtClear = $conn->prepare($sql);
    if (!$stmtClear) throw new Exception("Prepare failed (clear): " . $conn->error);

    $params = array_merge([$rosterId, $startDate, $endDate], $empIds);
    bind_params($stmtClear, $types, $params);
    $stmtClear->execute();

    $count = $stmtClear->affected_rows;

    $conn->commit();
    $txStarted = false;

    echo json_encode([
      "status" => "success",
      "message" => "Assignments cleared successfully.",
      "updated_cells" => $count
    ]);
    exit;
  }

  $up = $conn->prepare("
    INSERT INTO roster_assignment (RosterID, EmployeeID, WorkDate, ShiftCode, UpdatedByAccountID)
    VALUES (?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE
      ShiftCode=VALUES(ShiftCode),
      UpdatedByAccountID=VALUES(UpdatedByAccountID),
      UpdatedAt=CURRENT_TIMESTAMP
  ");
  if (!$up) throw new Exception("Prepare failed (upsert): " . $conn->error);

  $leaveCheck = $conn->prepare("
    SELECT 1
    FROM leave_requests
    WHERE EmployeeID = ?
      AND Status IN ('APPROVED_BY_OFFICER','APPROVED_BY_HR')
      AND ? BETWEEN StartDate AND EndDate
    LIMIT 1
  ");
  if (!$leaveCheck) throw new Exception("Prepare failed (leave check): " . $conn->error);

  foreach ($empIds as $eid) {
    foreach ($dates as $d) {
      $leaveCheck->bind_param("is", $eid, $d);
      $leaveCheck->execute();
      if ($leaveCheck->get_result()->fetch_row()) {
        continue;
      }

      $up->bind_param("iissi", $rosterId, $eid, $d, $shiftCode, $accountId);
      $up->execute();
      $count++;
    }
  }

  $conn->commit();
  $txStarted = false;

  echo json_encode([
    "status" => "success",
    "message" => "Bulk schedule applied.",
    "updated_cells" => $count
  ]);

} catch (Exception $e) {
  if ($txStarted) $conn->rollback();
  echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}