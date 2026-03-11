<?php
require_once __DIR__ . '/../../../config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

if (!isset($conn) || !($conn instanceof mysqli)) {
    if (isset($mysqli) && $mysqli instanceof mysqli) {
        $conn = $mysqli;
    } else {
        $conn = new mysqli("localhost", "root", "", "microfinance");
    }
}

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Database connection failed."
    ]);
    exit;
}
$conn->set_charset("utf8mb4");

function respond(array $data, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($data);
    exit;
}

function get_json_body(): array
{
    $raw = file_get_contents("php://input");
    if (!$raw) return [];
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

function session_value(array $keys, $default = null)
{
    foreach ($keys as $key) {
        if (isset($_SESSION[$key]) && $_SESSION[$key] !== '') {
            return $_SESSION[$key];
        }
    }
    return $default;
}

function officer_context(): array
{
    return [
        "department_id"   => (int) session_value(['department_id', 'DepartmentID', 'dept_id'], 0),
        "department_name" => (string) session_value(['department_name', 'DepartmentName'], 'My Department'),
        "account_id"      => (int) session_value(['account_id', 'AccountID', 'user_id'], 0),
        "employee_id"     => (int) session_value(['employee_id', 'EmployeeID'], 0),
    ];
}

function ensure_officer_context(array $ctx): void
{
    if (empty($ctx['department_id']) || empty($ctx['account_id'])) {
        respond([
            "success" => false,
            "message" => "Officer department/account session is missing."
        ], 403);
    }
}

function normalize_monday(string $date): string
{
    $dt = new DateTime($date);
    if ((int)$dt->format('N') !== 1) {
        $dt->modify('monday this week');
    }
    return $dt->format('Y-m-d');
}

function end_from_monday(string $monday): string
{
    $dt = new DateTime($monday);
    $dt->modify('+12 days');
    return $dt->format('Y-m-d');
}

function work_dates(string $start, string $end): array
{
    $dates = [];
    $cur   = new DateTime($start);
    $last  = new DateTime($end);

    while ($cur <= $last) {
        if ((int)$cur->format('N') !== 7) {
            $dates[] = $cur->format('Y-m-d');
        }
        $cur->modify('+1 day');
    }

    return $dates;
}

function build_full_name(array $row): string
{
    $parts = [];
    if (!empty($row['FirstName']))  $parts[] = trim($row['FirstName']);
    if (!empty($row['MiddleName'])) $parts[] = trim($row['MiddleName']);
    if (!empty($row['LastName']))   $parts[] = trim($row['LastName']);
    return $parts ? implode(' ', $parts) : 'Employee';
}

function shift_class(string $shiftCode): string
{
    $code = strtoupper(trim($shiftCode));
    return match ($code) {
        'AM'  => 'shift-morning',
        'MD'  => 'shift-afternoon',
        'GY'  => 'shift-night',
        'OFF' => 'shift-off',
        default => 'shift-custom',
    };
}

function roster_status_text(string $status): string
{
    $status = strtoupper(trim($status));
    return match ($status) {
        'FOR_REVIEW' => 'For Review',
        'RETURNED'   => 'Returned',
        'APPROVED'   => 'Approved',
        'PUBLISHED'  => 'Published',
        default      => 'Draft',
    };
}

function roster_is_editable(string $status): bool
{
    $status = strtoupper(trim($status));
    return in_array($status, ['DRAFT', 'RETURNED'], true);
}

function get_or_create_roster(mysqli $conn, int $departmentId, int $accountId, string $start, string $end): array
{
    $sql = "SELECT RosterID, Status, WeekStart, WeekEnd
            FROM weekly_roster
            WHERE DepartmentID = ? AND WeekStart = ? AND WeekEnd = ?
            LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iss", $departmentId, $start, $end);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();

    if ($res) {
        return [
            "RosterID" => (int)$res['RosterID'],
            "Status"   => (string)$res['Status'],
            "WeekStart"=> (string)$res['WeekStart'],
            "WeekEnd"  => (string)$res['WeekEnd'],
        ];
    }

    $status = 'DRAFT';
    $insert = $conn->prepare("
        INSERT INTO weekly_roster
            (DepartmentID, WeekStart, WeekEnd, Status, CreatedByAccountID)
        VALUES
            (?, ?, ?, ?, ?)
    ");
    $insert->bind_param("isssi", $departmentId, $start, $end, $status, $accountId);
    $insert->execute();

    return [
        "RosterID" => (int)$conn->insert_id,
        "Status"   => $status,
        "WeekStart"=> $start,
        "WeekEnd"  => $end,
    ];
}

function get_allowed_employee_ids(mysqli $conn, array $ctx): array
{
    $allowed = [];

    $stmt = $conn->prepare("
        SELECT DISTINCT se.EmployeeID
        FROM supervisor_employees se
        INNER JOIN employmentinformation ei
            ON ei.EmployeeID = se.EmployeeID
        WHERE se.SupervisorAccountID = ?
          AND se.DepartmentID = ?
          AND se.IsActive = 1
          AND ei.DepartmentID = ?
    ");
    $stmt->bind_param("iii", $ctx['account_id'], $ctx['department_id'], $ctx['department_id']);
    $stmt->execute();
    $res = $stmt->get_result();

    while ($row = $res->fetch_assoc()) {
        $allowed[] = (int)$row['EmployeeID'];
    }

    if (!empty($ctx['employee_id'])) {
        $selfCheck = $conn->prepare("
            SELECT EmployeeID
            FROM employmentinformation
            WHERE EmployeeID = ? AND DepartmentID = ?
            LIMIT 1
        ");
        $selfCheck->bind_param("ii", $ctx['employee_id'], $ctx['department_id']);
        $selfCheck->execute();
        $self = $selfCheck->get_result()->fetch_assoc();

        if ($self) {
            $allowed[] = (int)$ctx['employee_id'];
        }
    }

    $allowed = array_values(array_unique(array_filter($allowed)));
    return $allowed;
}

function get_employees(mysqli $conn, array $ctx): array
{
    $ids = get_allowed_employee_ids($conn, $ctx);
    if (empty($ids)) return [];

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types = str_repeat('i', count($ids));

    $sql = "
        SELECT
            e.EmployeeID,
            e.FirstName,
            e.MiddleName,
            e.LastName,
            p.PositionName
        FROM employee e
        INNER JOIN employmentinformation ei
            ON ei.EmployeeID = e.EmployeeID
        LEFT JOIN positions p
            ON p.PositionID = ei.PositionID
        WHERE e.EmployeeID IN ($placeholders)
          AND ei.DepartmentID = ?
        ORDER BY
            CASE WHEN e.EmployeeID = ? THEN 0 ELSE 1 END,
            e.LastName ASC,
            e.FirstName ASC
    ";

    $stmt = $conn->prepare($sql);

    $bindValues = [];
    $bindValues[] = $types . 'ii';

    foreach ($ids as $k => $id) {
        $bindValues[] = &$ids[$k];
    }

    $deptId = $ctx['department_id'];
    $selfId = $ctx['employee_id'];
    $bindValues[] = &$deptId;
    $bindValues[] = &$selfId;

    call_user_func_array([$stmt, 'bind_param'], $bindValues);
    $stmt->execute();
    $res = $stmt->get_result();

    $employees = [];
    while ($row = $res->fetch_assoc()) {
        $empId = (int)$row['EmployeeID'];
        $employees[] = [
            "EmployeeID" => $empId,
            "name"       => build_full_name($row),
            "position"   => $row['PositionName'] ?: 'Employee',
            "is_self"    => ($empId === (int)$ctx['employee_id'])
        ];
    }

    return $employees;
}

function get_shift_catalog(mysqli $conn): array
{
    $shifts = [];

    $sql = "SELECT ShiftCode, ShiftName, StartTime, EndTime, BreakMinutes, GraceMinutes
            FROM shift_type
            WHERE IsActive = 1
            ORDER BY FIELD(ShiftCode,'AM','MD','GY','OFF'), ShiftCode";
    $res = $conn->query($sql);

    while ($row = $res->fetch_assoc()) {
        $code = strtoupper(trim($row['ShiftCode']));
        $start = $row['StartTime'];
        $end   = $row['EndTime'];

        $timeLabel = 'Rest Day';
        if ($code !== 'OFF' && $start && $end) {
            $timeLabel = date('h:i A', strtotime($start)) . ' - ' . date('h:i A', strtotime($end));
        }

        $label = $row['ShiftName'] ?: $code;

        $shifts[] = [
            "code"        => $code,
            "label"       => $label,
            "time"        => $timeLabel,
            "class"       => shift_class($code),
            "start_time"  => $start,
            "end_time"    => $end,
            "is_day_off"  => ($code === 'OFF' ? 1 : 0),
            "break_mins"  => (int)$row['BreakMinutes'],
            "grace_mins"  => (int)$row['GraceMinutes'],
            "break_label" => ($code === 'OFF' ? 'No break' : ((int)$row['BreakMinutes'] . ' mins break'))
        ];
    }

    return $shifts;
}

function get_holidays(mysqli $conn, string $start, string $end): array
{
    $holidays = [];
    $meta = [];

    $stmt = $conn->prepare("
        SELECT HolidayDate, HolidayName
        FROM holidays
        WHERE IsActive = 1
          AND HolidayDate BETWEEN ? AND ?
        ORDER BY HolidayDate ASC
    ");
    $stmt->bind_param("ss", $start, $end);
    $stmt->execute();
    $res = $stmt->get_result();

    while ($row = $res->fetch_assoc()) {
        $date = $row['HolidayDate'];
        $holidays[] = $date;
        $meta[$date] = $row['HolidayName'];
    }

    return [
        "dates" => $holidays,
        "meta"  => $meta
    ];
}

function get_approved_leaves(mysqli $conn, array $employeeIds, string $start, string $end): array
{
    if (empty($employeeIds)) return [];

    $placeholders = implode(',', array_fill(0, count($employeeIds), '?'));
    $types = str_repeat('i', count($employeeIds));

    $sql = "
        SELECT
            lr.EmployeeID,
            lr.StartDate,
            lr.EndDate,
            lt.LeaveName
        FROM leave_requests lr
        LEFT JOIN leave_types lt
            ON lt.LeaveTypeID = lr.LeaveTypeID
        WHERE lr.EmployeeID IN ($placeholders)
          AND lr.StartDate <= ?
          AND lr.EndDate >= ?
          AND lr.Status IN ('APPROVED_BY_OFFICER', 'APPROVED_BY_HR')
    ";

    $stmt = $conn->prepare($sql);

    $bindValues = [];
    $bindValues[] = $types . 'ss';

    foreach ($employeeIds as $k => $id) {
        $bindValues[] = &$employeeIds[$k];
    }

    $bindValues[] = &$end;
    $bindValues[] = &$start;

    call_user_func_array([$stmt, 'bind_param'], $bindValues);
    $stmt->execute();
    $res = $stmt->get_result();

    $leaves = [];
    while ($row = $res->fetch_assoc()) {
        $empId = (int)$row['EmployeeID'];
        $cur   = new DateTime($row['StartDate']);
        $last  = new DateTime($row['EndDate']);

        while ($cur <= $last) {
            if ((int)$cur->format('N') !== 7) {
                $d = $cur->format('Y-m-d');
                if ($d >= $start && $d <= $end) {
                    $leaves[$empId . '_' . $d] = [
                        "type" => $row['LeaveName'] ?: 'Approved Leave'
                    ];
                }
            }
            $cur->modify('+1 day');
        }
    }

    return $leaves;
}

function get_assignments(mysqli $conn, int $rosterId): array
{
    $assignments = [];

    $stmt = $conn->prepare("
        SELECT
            ra.EmployeeID,
            ra.WorkDate,
            ra.ShiftCode,
            st.ShiftName,
            st.StartTime,
            st.EndTime,
            st.BreakMinutes,
            st.GraceMinutes
        FROM roster_assignment ra
        LEFT JOIN shift_type st
            ON st.ShiftCode = ra.ShiftCode
        WHERE ra.RosterID = ?
    ");
    $stmt->bind_param("i", $rosterId);
    $stmt->execute();
    $res = $stmt->get_result();

    while ($row = $res->fetch_assoc()) {
        $code = strtoupper(trim($row['ShiftCode']));
        $key  = (int)$row['EmployeeID'] . '_' . $row['WorkDate'];

        $assignments[$key] = [
            "shift_code" => $code,
            "label"      => $row['ShiftName'] ?: $code,
            "class"      => shift_class($code),
            "start_time" => $row['StartTime'],
            "end_time"   => $row['EndTime'],
            "break_mins" => (int)$row['BreakMinutes'],
            "grace_mins" => (int)$row['GraceMinutes'],
            "is_day_off" => ($code === 'OFF' ? 1 : 0),
            "source"     => 'saved'
        ];
    }

    return $assignments;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'load':
        handle_load($conn);
        break;
    case 'save':
        handle_save($conn);
        break;
    case 'submit':
        handle_submit($conn);
        break;
    default:
        respond([
            "success" => false,
            "message" => "Invalid action."
        ], 400);
}

function handle_load(mysqli $conn): void
{
    $ctx = officer_context();
    ensure_officer_context($ctx);

    $start = $_GET['start_date'] ?? date('Y-m-d');
    $start = normalize_monday($start);
    $end   = end_from_monday($start);

    $roster = get_or_create_roster($conn, $ctx['department_id'], $ctx['account_id'], $start, $end);
    $employees = get_employees($conn, $ctx);
    $employeeIds = array_map(fn($e) => (int)$e['EmployeeID'], $employees);
    $holidayData = get_holidays($conn, $start, $end);
    $leaves = get_approved_leaves($conn, $employeeIds, $start, $end);
    $assignments = get_assignments($conn, (int)$roster['RosterID']);
    $shifts = get_shift_catalog($conn);

    respond([
        "success" => true,
        "roster" => [
            "id"         => (int)$roster['RosterID'],
            "status"     => $roster['Status'],
            "statusText" => roster_status_text($roster['Status']),
            "start_date" => $start,
            "end_date"   => $end
        ],
        "dates"        => work_dates($start, $end),
        "employees"    => $employees,
        "holidays"     => $holidayData['dates'],
        "holiday_meta" => $holidayData['meta'],
        "leaves"       => $leaves,
        "assignments"  => $assignments,
        "shifts"       => $shifts,
        "today"        => date('Y-m-d')
    ]);
}

function handle_save(mysqli $conn): void
{
    $ctx = officer_context();
    ensure_officer_context($ctx);

    $body = get_json_body();

    $rosterId = (int)($body['roster_id'] ?? 0);
    $cells    = $body['cells'] ?? [];

    if ($rosterId <= 0) {
        respond(["success" => false, "message" => "Invalid roster ID."], 400);
    }

    if (!is_array($cells) || empty($cells)) {
        respond(["success" => false, "message" => "No changes to save."], 400);
    }

    $stmtRoster = $conn->prepare("
        SELECT RosterID, DepartmentID, Status
        FROM weekly_roster
        WHERE RosterID = ?
        LIMIT 1
    ");
    $stmtRoster->bind_param("i", $rosterId);
    $stmtRoster->execute();
    $roster = $stmtRoster->get_result()->fetch_assoc();

    if (!$roster) {
        respond(["success" => false, "message" => "Roster not found."], 404);
    }

    if ((int)$roster['DepartmentID'] !== (int)$ctx['department_id']) {
        respond(["success" => false, "message" => "You cannot edit this roster."], 403);
    }

    if (!roster_is_editable($roster['Status'])) {
        respond(["success" => false, "message" => "This roster can no longer be edited."], 403);
    }

    $allowedEmployeeIds = get_allowed_employee_ids($conn, $ctx);
    $allowedMap = array_fill_keys($allowedEmployeeIds, true);

    $validShiftCodes = [];
    $resShift = $conn->query("SELECT ShiftCode FROM shift_type WHERE IsActive = 1");
    while ($row = $resShift->fetch_assoc()) {
        $validShiftCodes[strtoupper(trim($row['ShiftCode']))] = true;
    }

    $conn->begin_transaction();

    try {
        foreach ($cells as $cell) {
            $employeeId = (int)($cell['employee_id'] ?? 0);
            $workDate   = trim((string)($cell['work_date'] ?? ''));
            $shiftCode  = strtoupper(trim((string)($cell['shift_code'] ?? '')));
            $source     = strtolower(trim((string)($cell['source'] ?? 'manual')));

            if ($employeeId <= 0 || $workDate === '') {
                continue;
            }

            if (!isset($allowedMap[$employeeId])) {
                continue;
            }

            $selfEmployeeId = (int)($ctx['employee_id'] ?? 0);
            $isSelfRow = ($selfEmployeeId > 0 && $employeeId === $selfEmployeeId);

            /*
                Rules:
                - self row cannot be manually assigned by normal click/fill
                - but self row may be changed by:
                    1) AI
                    2) clear-range/system clear
            */
            if ($isSelfRow && !in_array($source, ['ai', 'clear_range', 'system_clear'], true)) {
                continue;
            }

            if ($shiftCode === '') {
                $stmtDelete = $conn->prepare("
                    DELETE FROM roster_assignment
                    WHERE RosterID = ? AND EmployeeID = ? AND WorkDate = ?
                ");
                $stmtDelete->bind_param("iis", $rosterId, $employeeId, $workDate);
                $stmtDelete->execute();
                continue;
            }

            if (!isset($validShiftCodes[$shiftCode])) {
                continue;
            }

            $stmtCheck = $conn->prepare("
                SELECT AssignmentID
                FROM roster_assignment
                WHERE RosterID = ? AND EmployeeID = ? AND WorkDate = ?
                LIMIT 1
            ");
            $stmtCheck->bind_param("iis", $rosterId, $employeeId, $workDate);
            $stmtCheck->execute();
            $existing = $stmtCheck->get_result()->fetch_assoc();

            if ($existing) {
                $assignmentId = (int)$existing['AssignmentID'];

                $stmtUpdate = $conn->prepare("
                    UPDATE roster_assignment
                    SET ShiftCode = ?, UpdatedByAccountID = ?
                    WHERE AssignmentID = ?
                ");
                $stmtUpdate->bind_param("sii", $shiftCode, $ctx['account_id'], $assignmentId);
                $stmtUpdate->execute();
            } else {
                $stmtInsert = $conn->prepare("
                    INSERT INTO roster_assignment
                        (RosterID, EmployeeID, WorkDate, ShiftCode, UpdatedByAccountID)
                    VALUES
                        (?, ?, ?, ?, ?)
                ");
                $stmtInsert->bind_param("iissi", $rosterId, $employeeId, $workDate, $shiftCode, $ctx['account_id']);
                $stmtInsert->execute();
            }
        }

        $conn->commit();
        respond([
            "success" => true,
            "message" => "Roster draft saved successfully."
        ]);
    } catch (Throwable $e) {
        $conn->rollback();
        respond([
            "success" => false,
            "message" => "Save failed: " . $e->getMessage()
        ], 500);
    }
}

function handle_submit(mysqli $conn): void
{
    $ctx = officer_context();
    ensure_officer_context($ctx);

    $body = get_json_body();
    $rosterId = (int)($body['roster_id'] ?? 0);

    if ($rosterId <= 0) {
        respond(["success" => false, "message" => "Invalid roster ID."], 400);
    }

    $stmtRoster = $conn->prepare("
        SELECT RosterID, DepartmentID, Status
        FROM weekly_roster
        WHERE RosterID = ?
        LIMIT 1
    ");
    $stmtRoster->bind_param("i", $rosterId);
    $stmtRoster->execute();
    $roster = $stmtRoster->get_result()->fetch_assoc();

    if (!$roster) {
        respond(["success" => false, "message" => "Roster not found."], 404);
    }

    if ((int)$roster['DepartmentID'] !== (int)$ctx['department_id']) {
        respond(["success" => false, "message" => "You cannot submit this roster."], 403);
    }

    $status = strtoupper(trim((string)$roster['Status']));
    if (in_array($status, ['FOR_REVIEW', 'APPROVED', 'PUBLISHED'], true)) {
        respond([
            "success" => false,
            "message" => "This roster is already under review or finalized."
        ], 403);
    }

    $stmtCount = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM roster_assignment
        WHERE RosterID = ?
    ");
    $stmtCount->bind_param("i", $rosterId);
    $stmtCount->execute();
    $count = (int)($stmtCount->get_result()->fetch_assoc()['total'] ?? 0);

    if ($count <= 0) {
        respond([
            "success" => false,
            "message" => "Cannot submit an empty roster."
        ], 400);
    }

    $newStatus = 'FOR_REVIEW';
    $stmt = $conn->prepare("
        UPDATE weekly_roster
        SET Status = ?
        WHERE RosterID = ?
    ");
    $stmt->bind_param("si", $newStatus, $rosterId);
    $stmt->execute();

    respond([
        "success" => true,
        "message" => "Roster submitted to HR Manager."
    ]);
}