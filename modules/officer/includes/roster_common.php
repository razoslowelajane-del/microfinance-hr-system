<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . "/../includes/auth_officer.php";
require_once __DIR__ . "/../../../config/config.php";

header('Content-Type: application/json');

function respond($ok, $data = [], $code = 200) {
    http_response_code($code);
    echo json_encode(array_merge(['ok' => $ok], $data));
    exit;
}

function get_json_input() {
    $raw = file_get_contents("php://input");
    if (!$raw) return [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function ctx() {
    return [
        'department_id' => $_SESSION['department_id'] ?? null,
        'employee_id'   => $_SESSION['employee_id'] ?? $_SESSION['EmployeeID'] ?? null,
        'account_id'    => $_SESSION['account_id'] ?? $_SESSION['AccountID'] ?? $_SESSION['user_id'] ?? null
    ];
}

function require_ctx() {
    $c = ctx();
    if (!$c['department_id'] || !$c['employee_id'] || !$c['account_id']) {
        respond(false, ['message' => 'Missing department/account session context.'], 400);
    }
    return $c;
}

function is_valid_date($date) {
    return preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1;
}

function monday_of_week($date) {
    $dt = new DateTime($date);
    $dayNum = (int)$dt->format('N');
    $dt->modify('-' . ($dayNum - 1) . ' days');
    return $dt->format('Y-m-d');
}

function get_roster_period($anchorDate = null) {
    $anchorDate = $anchorDate && is_valid_date($anchorDate) ? $anchorDate : date('Y-m-d');
    $start = monday_of_week($anchorDate);
    $startDt = new DateTime($start);
    $endDt = new DateTime($start);
    $endDt->modify('+12 days');

    return [
        'start_date' => $startDt->format('Y-m-d'),
        'end_date'   => $endDt->format('Y-m-d')
    ];
}

function build_work_dates($startDate) {
    $dates = [];
    $dt = new DateTime($startDate);

    for ($i = 0; $i <= 12; $i++) {
        $cur = clone $dt;
        $cur->modify("+{$i} days");
        $n = (int)$cur->format('N');
        if ($n === 7) continue;

        $dates[] = [
            'date' => $cur->format('Y-m-d'),
            'label' => strtoupper($cur->format('D')),
            'short_date' => $cur->format('M d')
        ];
    }

    return $dates;
}

function detect_shift_bucket($code, $name = '') {
    $s = strtoupper(trim($code . ' ' . $name));
    if (strpos($s, 'OFF') !== false || strpos($s, 'REST') !== false) return 'OFF';
    if (strpos($s, 'AM') !== false || strpos($s, 'MORNING') !== false) return 'AM';
    if (strpos($s, 'MD') !== false || strpos($s, 'MID') !== false || strpos($s, 'PM') !== false || strpos($s, 'AFTERNOON') !== false) return 'MD';
    if (strpos($s, 'NS') !== false || strpos($s, 'NIGHT') !== false) return 'NS';
    return 'OTHER';
}

function require_active_officer(mysqli $conn, $departmentId, $employeeId) {
    $sql = "
        SELECT EmployeeID
        FROM department_officers
        WHERE DepartmentID = ?
          AND IsActive = 1
        LIMIT 1
    ";
    $stmt = $conn->prepare($sql);
    if (!$stmt) respond(false, ['message' => 'Failed to prepare active officer query.'], 500);

    $stmt->bind_param("i", $departmentId);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();

    if (!$row) {
        respond(false, ['message' => 'No active department officer is configured for this department.'], 403);
    }

    if ((int)$row['EmployeeID'] !== (int)$employeeId) {
        respond(false, ['message' => 'Only the active department officer may manage this roster.'], 403);
    }
}

function get_shifts(mysqli $conn) {
    $shifts = [];

    $sql = "
        SELECT ShiftCode, ShiftName
        FROM shifts
        WHERE COALESCE(IsActive, 1) = 1
        ORDER BY ShiftCode
    ";

    $res = $conn->query($sql);
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $shifts[] = $row;
        }
    }

    if (!$shifts) {
        $shifts = [
            ['ShiftCode' => 'AM', 'ShiftName' => 'Morning'],
            ['ShiftCode' => 'MD', 'ShiftName' => 'Midday'],
            ['ShiftCode' => 'OFF', 'ShiftName' => 'Off']
        ];
    }

    return $shifts;
}

function get_shift_codes(mysqli $conn) {
    $rows = get_shifts($conn);
    $codes = [];
    foreach ($rows as $row) {
        $codes[strtoupper($row['ShiftCode'])] = $row;
    }
    return $codes;
}

function get_preferred_shift_codes(mysqli $conn) {
    $shifts = get_shifts($conn);

    $preferred = [
        'AM' => null,
        'MD' => null,
        'OFF' => null,
        'NS' => null
    ];

    foreach ($shifts as $shift) {
        $bucket = detect_shift_bucket($shift['ShiftCode'], $shift['ShiftName'] ?? '');
        if (empty($preferred[$bucket])) {
            $preferred[$bucket] = strtoupper($shift['ShiftCode']);
        }
    }

    if (!$preferred['AM'])  $preferred['AM']  = isset($shifts[0]) ? strtoupper($shifts[0]['ShiftCode']) : 'AM';
    if (!$preferred['MD'])  $preferred['MD']  = isset($shifts[1]) ? strtoupper($shifts[1]['ShiftCode']) : $preferred['AM'];
    if (!$preferred['OFF']) $preferred['OFF'] = 'OFF';

    return $preferred;
}

function get_employees_in_scope(mysqli $conn, $departmentId, $myEmployeeId) {
    $sql = "
        SELECT 
            e.EmployeeID,
            TRIM(CONCAT(
                COALESCE(e.FirstName, ''),
                CASE WHEN COALESCE(e.MiddleName, '') <> '' THEN CONCAT(' ', e.MiddleName) ELSE '' END,
                CASE WHEN COALESCE(e.LastName, '') <> '' THEN CONCAT(' ', e.LastName) ELSE '' END
            )) AS FullName,
            COALESCE(p.PositionName, 'Employee') AS PositionName
        FROM employee e
        INNER JOIN employmentinformation ei
            ON ei.EmployeeID = e.EmployeeID
        LEFT JOIN positions p
            ON p.PositionID = ei.PositionID
        WHERE ei.DepartmentID = ?
          AND (
                COALESCE(ei.EmploymentStatus, 'Active') = 'Active'
                OR COALESCE(ei.EmploymentStatus, 'ACTIVE') = 'ACTIVE'
                OR ei.EmploymentStatus IS NULL
          )
        ORDER BY 
            CASE WHEN e.EmployeeID = ? THEN 0 ELSE 1 END,
            FullName ASC
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) respond(false, ['message' => 'Failed to prepare employee scope query.'], 500);

    $stmt->bind_param("ii", $departmentId, $myEmployeeId);
    $stmt->execute();
    $res = $stmt->get_result();

    $raw = [];
    while ($row = $res->fetch_assoc()) {
        $row['EmployeeID'] = (int)$row['EmployeeID'];
        $row['IsSelf'] = ((int)$row['EmployeeID'] === (int)$myEmployeeId) ? 1 : 0;
        $raw[] = $row;
    }
    $stmt->close();

    if (!$raw) return [];

    $employees = [];
    $idList = implode(',', array_map(fn($e) => (int)$e['EmployeeID'], $raw));
    $roleMap = [];

    if ($idList !== '') {
        $roleSql = "
            SELECT a.EmployeeID, r.RoleName
            FROM accounts a
            INNER JOIN account_roles ar ON ar.AccountID = a.AccountID
            INNER JOIN roles r ON r.RoleID = ar.RoleID
            WHERE a.EmployeeID IN ($idList)
        ";
        $roleRes = $conn->query($roleSql);
        if ($roleRes) {
            while ($rr = $roleRes->fetch_assoc()) {
                $eid = (int)$rr['EmployeeID'];
                $roleMap[$eid][] = strtoupper(trim($rr['RoleName']));
            }
        }
    }

    $forbidden = [
        'DEPARTMENT OFFICER',
        'HR MANAGER',
        'ADMINISTRATOR',
        'GENERAL MANAGER',
        'OPERATIONAL MANAGER',
        'SUPERVISOR'
    ];

    foreach ($raw as $row) {
        $eid = (int)$row['EmployeeID'];
        $roles = $roleMap[$eid] ?? [];
        $isForbidden = false;

        foreach ($roles as $roleName) {
            if (in_array($roleName, $forbidden, true)) {
                $isForbidden = true;
                break;
            }
        }

        if ($row['IsSelf']) {
            $employees[] = $row;
            continue;
        }

        if ($isForbidden) {
            continue;
        }

        $employees[] = $row;
    }

    return $employees;
}

function get_or_create_roster(mysqli $conn, $departmentId, $startDate, $endDate, $accountId) {
    $sql = "
        SELECT RosterID, DepartmentID, StartDate, EndDate, Status
        FROM weekly_roster
        WHERE DepartmentID = ?
          AND StartDate = ?
          AND EndDate = ?
        ORDER BY RosterID DESC
        LIMIT 1
    ";
    $stmt = $conn->prepare($sql);
    if (!$stmt) respond(false, ['message' => 'Failed to prepare roster lookup.'], 500);

    $stmt->bind_param("iss", $departmentId, $startDate, $endDate);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();

    if ($row) {
      return $row;
    }

    $status = 'DRAFT';
    $sql = "
        INSERT INTO weekly_roster
        (DepartmentID, StartDate, EndDate, Status, CreatedByAccountID, CreatedAt, UpdatedAt)
        VALUES (?, ?, ?, ?, ?, NOW(), NOW())
    ";
    $stmt = $conn->prepare($sql);
    if (!$stmt) respond(false, ['message' => 'Failed to prepare roster insert.'], 500);

    $stmt->bind_param("isssi", $departmentId, $startDate, $endDate, $status, $accountId);
    if (!$stmt->execute()) {
        respond(false, ['message' => 'Failed to create roster: ' . $stmt->error], 500);
    }

    $rosterId = $stmt->insert_id;
    $stmt->close();

    return [
        'RosterID' => $rosterId,
        'DepartmentID' => $departmentId,
        'StartDate' => $startDate,
        'EndDate' => $endDate,
        'Status' => $status
    ];
}

function get_assignments(mysqli $conn, $rosterId) {
    $rows = [];

    $sql = "
        SELECT RosterID, EmployeeID, WorkDate, ShiftCode, COALESCE(Source, 'MANUAL') AS Source
        FROM weekly_roster_assignment
        WHERE RosterID = ?
    ";
    $stmt = $conn->prepare($sql);
    if (!$stmt) respond(false, ['message' => 'Failed to prepare assignment query.'], 500);

    $stmt->bind_param("i", $rosterId);
    $stmt->execute();
    $res = $stmt->get_result();

    while ($row = $res->fetch_assoc()) {
        $key = (int)$row['EmployeeID'] . '|' . $row['WorkDate'];
        $rows[$key] = [
            'RosterID' => (int)$row['RosterID'],
            'EmployeeID' => (int)$row['EmployeeID'],
            'WorkDate' => $row['WorkDate'],
            'ShiftCode' => strtoupper($row['ShiftCode']),
            'Source' => strtoupper($row['Source'])
        ];
    }
    $stmt->close();

    return $rows;
}

function get_holidays(mysqli $conn, $startDate, $endDate) {
    $rows = [];

    $sql = "
        SELECT HolidayDate, HolidayName
        FROM holidays
        WHERE HolidayDate BETWEEN ? AND ?
    ";
    $stmt = $conn->prepare($sql);
    if (!$stmt) return $rows;

    $stmt->bind_param("ss", $startDate, $endDate);
    $stmt->execute();
    $res = $stmt->get_result();

    while ($row = $res->fetch_assoc()) {
        $rows[$row['HolidayDate']] = $row;
    }
    $stmt->close();

    return $rows;
}

function get_approved_leaves(mysqli $conn, array $employees, $startDate, $endDate) {
    $rows = [];
    if (!$employees) return $rows;

    $ids = array_map(fn($e) => (int)$e['EmployeeID'], $employees);
    $idList = implode(',', $ids);

    $sql = "
        SELECT EmployeeID, StartDate, EndDate
        FROM leave_requests
        WHERE Status = 'APPROVED'
          AND EmployeeID IN ($idList)
          AND StartDate <= ?
          AND EndDate >= ?
    ";
    $stmt = $conn->prepare($sql);
    if (!$stmt) return $rows;

    $stmt->bind_param("ss", $endDate, $startDate);
    $stmt->execute();
    $res = $stmt->get_result();

    while ($row = $res->fetch_assoc()) {
        $empId = (int)$row['EmployeeID'];
        $cur = new DateTime($row['StartDate']);
        $end = new DateTime($row['EndDate']);

        while ($cur <= $end) {
            if ((int)$cur->format('N') !== 7) {
                $key = $empId . '|' . $cur->format('Y-m-d');
                $rows[$key] = [
                    'EmployeeID' => $empId,
                    'WorkDate' => $cur->format('Y-m-d')
                ];
            }
            $cur->modify('+1 day');
        }
    }
    $stmt->close();

    return $rows;
}

function is_sunday($date) {
    $dt = new DateTime($date);
    return ((int)$dt->format('N') === 7);
}

function compute_stats($employees, $days, $assignments, $holidays, $leaves) {
    $totalEmployees = count($employees);
    $unassigned = 0;

    foreach ($employees as $emp) {
        foreach ($days as $day) {
            $key = $emp['EmployeeID'] . '|' . $day['date'];

            if (!empty($holidays[$day['date']])) continue;
            if (!empty($leaves[$key])) continue;
            if (!empty($emp['IsSelf'])) continue;

            if (!isset($assignments[$key]) || trim((string)$assignments[$key]['ShiftCode']) === '') {
                $unassigned++;
            }
        }
    }

    $start = $days[0]['date'] ?? null;
    $end = $days[count($days) - 1]['date'] ?? null;
    $coverageLabel = $start && $end ? ($start . ' to ' . $end) : '--';

    return [
        'total_employees' => $totalEmployees,
        'unassigned' => $unassigned,
        'coverage_label' => $coverageLabel
    ];
}

function roster_payload(mysqli $conn, $departmentId, $myEmployeeId, $accountId, $anchorDate) {
    require_active_officer($conn, $departmentId, $myEmployeeId);

    $period = get_roster_period($anchorDate);
    $days = build_work_dates($period['start_date']);
    $roster = get_or_create_roster($conn, $departmentId, $period['start_date'], $period['end_date'], $accountId);
    $employees = get_employees_in_scope($conn, $departmentId, $myEmployeeId);
    $assignments = get_assignments($conn, (int)$roster['RosterID']);
    $holidays = get_holidays($conn, $period['start_date'], $period['end_date']);
    $leaves = get_approved_leaves($conn, $employees, $period['start_date'], $period['end_date']);
    $stats = compute_stats($employees, $days, $assignments, $holidays, $leaves);

    return [
        'period' => $period,
        'days' => $days,
        'roster' => $roster,
        'employees' => $employees,
        'assignments' => $assignments,
        'holidays' => $holidays,
        'leaves' => $leaves,
        'shifts' => get_shifts($conn),
        'stats' => $stats
    ];
}

function validate_cell_for_save($cell, $payload, mysqli $conn, $allowSelfAi = false) {
    $employeeId = (int)($cell['employee_id'] ?? 0);
    $workDate = trim((string)($cell['work_date'] ?? ''));
    $shiftCode = strtoupper(trim((string)($cell['shift_code'] ?? '')));

    if (!$employeeId || !is_valid_date($workDate)) {
        return 'Invalid employee/date.';
    }

    if (is_sunday($workDate)) {
        return 'Sunday cannot be scheduled.';
    }

    $validEmployeeIds = array_map(fn($e) => (int)$e['EmployeeID'], $payload['employees']);
    if (!in_array($employeeId, $validEmployeeIds, true)) {
        return 'Employee is outside the valid department roster scope.';
    }

    $isSelf = false;
    foreach ($payload['employees'] as $e) {
        if ((int)$e['EmployeeID'] === $employeeId) {
            $isSelf = !empty($e['IsSelf']);
            break;
        }
    }

    $key = $employeeId . '|' . $workDate;

    if (!$allowSelfAi && $isSelf) {
        return 'Your own row is locked for manual editing.';
    }

    if (!empty($payload['holidays'][$workDate])) {
        return 'Holiday cells cannot be edited.';
    }

    if (!empty($payload['leaves'][$key])) {
        return 'Employee is on approved leave on this date.';
    }

    if ($shiftCode !== '') {
        $shiftCodes = get_shift_codes($conn);
        if (!isset($shiftCodes[$shiftCode])) {
            return 'Invalid shift code: ' . $shiftCode;
        }
    }

    $periodStart = $payload['period']['start_date'];
    $periodEnd = $payload['period']['end_date'];
    if ($workDate < $periodStart || $workDate > $periodEnd) {
        return 'Date is outside the current roster period.';
    }

    return null;
}

function upsert_assignment(mysqli $conn, $rosterId, $employeeId, $workDate, $shiftCode, $source = 'MANUAL') {
    $shiftCode = strtoupper(trim((string)$shiftCode));
    $source = strtoupper(trim((string)$source));

    if ($shiftCode === '') {
        $sql = "DELETE FROM weekly_roster_assignment WHERE RosterID = ? AND EmployeeID = ? AND WorkDate = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) respond(false, ['message' => 'Failed to prepare delete assignment.'], 500);

        $stmt->bind_param("iis", $rosterId, $employeeId, $workDate);
        if (!$stmt->execute()) {
            respond(false, ['message' => 'Failed to clear assignment: ' . $stmt->error], 500);
        }
        $stmt->close();
        return;
    }

    $checkSql = "SELECT RosterID FROM weekly_roster_assignment WHERE RosterID = ? AND EmployeeID = ? AND WorkDate = ? LIMIT 1";
    $stmt = $conn->prepare($checkSql);
    if (!$stmt) respond(false, ['message' => 'Failed to prepare assignment check.'], 500);

    $stmt->bind_param("iis", $rosterId, $employeeId, $workDate);
    $stmt->execute();
    $exists = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($exists) {
        $sql = "
            UPDATE weekly_roster_assignment
            SET ShiftCode = ?, Source = ?, UpdatedAt = NOW()
            WHERE RosterID = ? AND EmployeeID = ? AND WorkDate = ?
        ";
        $stmt = $conn->prepare($sql);
        if (!$stmt) respond(false, ['message' => 'Failed to prepare assignment update.'], 500);

        $stmt->bind_param("ssiis", $shiftCode, $source, $rosterId, $employeeId, $workDate);
    } else {
        $sql = "
            INSERT INTO weekly_roster_assignment
            (RosterID, EmployeeID, WorkDate, ShiftCode, Source, CreatedAt, UpdatedAt)
            VALUES (?, ?, ?, ?, ?, NOW(), NOW())
        ";
        $stmt = $conn->prepare($sql);
        if (!$stmt) respond(false, ['message' => 'Failed to prepare assignment insert.'], 500);

        $stmt->bind_param("iisss", $rosterId, $employeeId, $workDate, $shiftCode, $source);
    }

    if (!$stmt->execute()) {
        respond(false, ['message' => 'Failed to save assignment: ' . $stmt->error], 500);
    }
    $stmt->close();
}

function compute_employee_metrics($employees, $days, $assignments, $leaves) {
    $metrics = [];

    foreach ($employees as $emp) {
        $eid = (int)$emp['EmployeeID'];
        $metrics[$eid] = [
            'employee_id' => $eid,
            'employee_name' => $emp['FullName'],
            'am' => 0,
            'md' => 0,
            'off' => 0,
            'ns' => 0,
            'duty_days' => 0,
            'leave_days' => 0
        ];

        foreach ($days as $day) {
            $key = $eid . '|' . $day['date'];

            if (!empty($leaves[$key])) {
                $metrics[$eid]['leave_days']++;
                continue;
            }

            if (!empty($assignments[$key])) {
                $bucket = detect_shift_bucket($assignments[$key]['ShiftCode']);

                if ($bucket === 'AM') $metrics[$eid]['am']++;
                elseif ($bucket === 'MD') $metrics[$eid]['md']++;
                elseif ($bucket === 'OFF') $metrics[$eid]['off']++;
                elseif ($bucket === 'NS') $metrics[$eid]['ns']++;

                if ($bucket !== 'OFF') {
                    $metrics[$eid]['duty_days']++;
                }
            }
        }
    }

    return $metrics;
}