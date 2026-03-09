<?php
require_once __DIR__ . "/auth_officer.php";
require_once __DIR__ . "/../../../config/config.php";

header('Content-Type: application/json');

$deptId    = $_SESSION['department_id'] ?? null;
$accountId = $_SESSION['account_id'] ?? $_SESSION['AccountID'] ?? $_SESSION['user_id'] ?? null;
$myEmpId   = $_SESSION['employee_id'] ?? $_SESSION['EmployeeID'] ?? null;

if (!$deptId || !$accountId) {
  echo json_encode(["error" => "Missing department/account in session. Please re-login."]);
  exit;
}

$periodStartParam = $_GET['period_start'] ?? null;

function computeCutoffStartEnd(?string $startOverride): array {
  $now = new DateTime("now");

  if ($startOverride) {
    $s = DateTime::createFromFormat("Y-m-d", $startOverride);
    if ($s) {
      $s->setTime(0, 0, 0);

      $day = (int)$s->format("d");
      $y = (int)$s->format("Y");
      $m = (int)$s->format("m");

      if ($day <= 12) {
        $start = new DateTime("$y-$m-01");
        $end   = new DateTime("$y-$m-12");
      } elseif ($day <= 24) {
        $start = new DateTime("$y-$m-13");
        $end   = new DateTime("$y-$m-24");
      } else {
        $start = new DateTime("$y-$m-25");
        $end   = (new DateTime("$y-$m-01"))->modify("last day of this month");
      }
      return [$start, $end];
    }
  }

  $y = (int)$now->format("Y");
  $m = (int)$now->format("m");
  $d = (int)$now->format("d");

  if ($d <= 12) {
    $start = new DateTime("$y-$m-01");
    $end   = new DateTime("$y-$m-12");
  } elseif ($d <= 24) {
    $start = new DateTime("$y-$m-13");
    $end   = new DateTime("$y-$m-24");
  } else {
    $start = new DateTime("$y-$m-25");
    $end   = (new DateTime("$y-$m-01"))->modify("last day of this month");
  }

  return [$start, $end];
}

[$start, $end] = computeCutoffStartEnd($periodStartParam);
$startStr = $start->format("Y-m-d");
$endStr   = $end->format("Y-m-d");

$conn->begin_transaction();

try {
  $stmt = $conn->prepare("
    SELECT RosterID, Status
    FROM weekly_roster
    WHERE DepartmentID=? AND WeekStart=? AND WeekEnd=?
    LIMIT 1
  ");
  $stmt->bind_param("iss", $deptId, $startStr, $endStr);
  $stmt->execute();
  $rosterRow = $stmt->get_result()->fetch_assoc();

  if (!$rosterRow) {
    $ins = $conn->prepare("
      INSERT INTO weekly_roster (DepartmentID, WeekStart, WeekEnd, Status, CreatedByAccountID)
      VALUES (?, ?, ?, 'DRAFT', ?)
    ");
    $ins->bind_param("issi", $deptId, $startStr, $endStr, $accountId);
    $ins->execute();
    $rosterId = (int)$ins->insert_id;
    $status = "DRAFT";
  } else {
    $rosterId = (int)$rosterRow["RosterID"];
    $status   = $rosterRow["Status"];
  }

  $hol = $conn->prepare("
    SELECT h.HolidayDate, h.HolidayName, ht.TypeCode, ht.TypeName
    FROM holidays h
    JOIN holiday_type ht ON h.HolidayTypeID = ht.HolidayTypeID
    WHERE h.IsActive=1 AND h.HolidayDate BETWEEN ? AND ?
  ");
  $hol->bind_param("ss", $startStr, $endStr);
  $hol->execute();
  $holRes = $hol->get_result();

  $holidays = [];
  while ($h = $holRes->fetch_assoc()) {
    $holidays[$h["HolidayDate"]] = $h;
  }

  $empSql = "
    SELECT 
      e.EmployeeID,
      e.EmployeeCode,
      e.FirstName,
      e.MiddleName,
      e.LastName,
      p.PositionName
    FROM employee e
    INNER JOIN employmentinformation ei ON e.EmployeeID = ei.EmployeeID
    LEFT JOIN positions p ON ei.PositionID = p.PositionID
    WHERE ei.DepartmentID = ?
    ORDER BY e.LastName ASC, e.FirstName ASC
  ";
  $empStmt = $conn->prepare($empSql);
  $empStmt->bind_param("i", $deptId);
  $empStmt->execute();
  $empRes = $empStmt->get_result();

  $employees = [];
  while ($e = $empRes->fetch_assoc()) {
    $employees[] = $e;
  }

  $as = $conn->prepare("
    SELECT ra.EmployeeID, ra.WorkDate, ra.ShiftCode,
           st.StartTime, st.EndTime
    FROM roster_assignment ra
    LEFT JOIN shift_type st ON ra.ShiftCode = st.ShiftCode
    WHERE ra.RosterID=? AND ra.WorkDate BETWEEN ? AND ?
  ");
  $as->bind_param("iss", $rosterId, $startStr, $endStr);
  $as->execute();
  $asRes = $as->get_result();

  $assignMap = [];
  while ($r = $asRes->fetch_assoc()) {
    $empId = (int)$r["EmployeeID"];
    $date  = $r["WorkDate"];

    if ($r["ShiftCode"] === "OFF" || empty($r["StartTime"])) {
      $assignMap[$empId][$date] = "OFF";
    } else {
      $startT = date("h:i A", strtotime($r["StartTime"]));
      $endT   = date("h:i A", strtotime($r["EndTime"]));
      $assignMap[$empId][$date] = $r["ShiftCode"] . " • " . $startT . "-" . $endT;
    }
  }

  $leaveStmt = $conn->prepare("
    SELECT 
      lr.EmployeeID,
      lr.StartDate,
      lr.EndDate,
      lt.LeaveName
    FROM leave_requests lr
    INNER JOIN leave_types lt ON lt.LeaveTypeID = lr.LeaveTypeID
    INNER JOIN employmentinformation ei ON ei.EmployeeID = lr.EmployeeID
    WHERE ei.DepartmentID = ?
      AND lr.Status IN ('APPROVED_BY_OFFICER','APPROVED_BY_HR')
      AND lr.StartDate <= ?
      AND lr.EndDate >= ?
    ORDER BY lr.EmployeeID, lr.StartDate
  ");
  $leaveStmt->bind_param("iss", $deptId, $endStr, $startStr);
  $leaveStmt->execute();
  $leaveRes = $leaveStmt->get_result();

  $leaveMap = [];
  while ($lr = $leaveRes->fetch_assoc()) {
    $empId = (int)$lr["EmployeeID"];
    $ls = new DateTime($lr["StartDate"]);
    $le = new DateTime($lr["EndDate"]);

    if ($ls->format("Y-m-d") < $startStr) $ls = new DateTime($startStr);
    if ($le->format("Y-m-d") > $endStr)   $le = new DateTime($endStr);

    while ($ls <= $le) {
      if ($ls->format("w") !== "0") {
        $leaveMap[$empId][$ls->format("Y-m-d")] = $lr["LeaveName"];
      }
      $ls->modify("+1 day");
    }
  }

  $dates = [];
  $cursor = clone $start;

  while ($cursor <= $end) {
    if ($cursor->format("w") === "0") {
      $cursor->modify("+1 day");
      continue;
    }

    $ds = $cursor->format("Y-m-d");
    $dates[] = [
      "date"    => $ds,
      "dow"     => $cursor->format("D"),
      "day"     => $cursor->format("d"),
      "holiday" => $holidays[$ds] ?? null
    ];
    $cursor->modify("+1 day");
  }

  $rows = [];
  foreach ($employees as $e) {
    $empId = (int)$e["EmployeeID"];
    $fullName = trim($e["FirstName"] . " " . $e["LastName"]);

    $days = [];
    $leaveDaysCount = 0;

    foreach ($dates as $d) {
      $ds = $d["date"];
      $isLeave = isset($leaveMap[$empId][$ds]);

      if ($isLeave) {
        $leaveDaysCount++;
      }

      $days[] = [
        "date"        => $ds,
        "value"       => $isLeave ? "LEAVE" : ($assignMap[$empId][$ds] ?? "-"),
        "is_leave"    => $isLeave,
        "leave_label" => $isLeave ? $leaveMap[$empId][$ds] : "",
      ];
    }

    if (count($dates) > 0 && $leaveDaysCount === count($dates)) {
      continue;
    }

    $rows[] = [
      "EmployeeID" => $empId,
      "name"       => $fullName,
      "position"   => $e["PositionName"] ?? "",
      "is_me"      => ($myEmpId && $empId === (int)$myEmpId),
      "days"       => $days
    ];
  }

  $conn->commit();

  echo json_encode([
    "roster" => [
      "RosterID"   => $rosterId,
      "Status"     => $status,
      "StartDate"  => $startStr,
      "EndDate"    => $endStr
    ],
    "dates" => $dates,
    "rows"  => $rows
  ]);

} catch (Exception $ex) {
  $conn->rollback();
  echo json_encode(["error" => $ex->getMessage()]);
}