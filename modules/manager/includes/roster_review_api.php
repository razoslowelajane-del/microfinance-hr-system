<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

require_once __DIR__ . '/auth_hr_manager.php';
require_once __DIR__ . '/../../../config/config.php';

$mysqli = null;

if (isset($conn) && $conn instanceof mysqli) {
    $mysqli = $conn;
} elseif (isset($db) && $db instanceof mysqli) {
    $mysqli = $db;
}

if (!$mysqli instanceof mysqli) {
    echo json_encode([
        'success' => false,
        'message' => 'Database connection not found.'
    ]);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'get_roster':
            getRoster($mysqli);
            break;
        case 'approve':
            approveRoster($mysqli);
            break;
        case 'return':
            returnRoster($mysqli);
            break;
        default:
            throw new Exception('Invalid action.');
    }
} catch (Throwable $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
exit;

function getRoster(mysqli $db): void
{
    $rosterId = (int)($_POST['RosterID'] ?? $_GET['RosterID'] ?? 0);
    if ($rosterId <= 0) {
        throw new Exception('Invalid roster ID.');
    }

    $roster = fetchRosterHeader($db, $rosterId);
    if (!$roster) {
        throw new Exception('Roster not found.');
    }

    $days = buildRosterDays($roster['WeekStart'], $roster['WeekEnd']);
    $employees = fetchRosterAssignments($db, $rosterId);
    $coverage = buildCoverageSummary($employees, $days);
    $validation = buildValidationChecks($employees, $days);

    echo json_encode([
        'success' => true,
        'roster' => [
            'roster_id'        => (int)$roster['RosterID'],
            'department_id'    => (int)$roster['DepartmentID'],
            'department_name'  => $roster['DepartmentName'] ?? 'N/A',
            'week_start'       => $roster['WeekStart'],
            'week_end'         => $roster['WeekEnd'],
            'period_label'     => formatPeriod($roster['WeekStart'], $roster['WeekEnd']),
            'submitted_by'     => $roster['CreatedByName'] ?? 'Unknown',
            'status'           => strtoupper((string)($roster['Status'] ?? 'UNKNOWN')),
            'review_notes'     => $roster['ReviewNotes'] ?? '',
            'reviewed_at'      => !empty($roster['ReviewedAt']) ? date('M d, Y h:i A', strtotime($roster['ReviewedAt'])) : null,
            'reviewed_by_name' => cleanName($roster['ReviewedByName'] ?? '')
        ],
        'days' => $days,
        'employees' => array_values($employees),
        'coverage' => $coverage,
        'validation' => $validation
    ]);
}

function returnRoster(mysqli $db): void
{
    $rosterId = (int)($_POST['RosterID'] ?? 0);
    $reviewNotes = trim($_POST['ReviewNotes'] ?? '');
    $managerAccountId = getSessionAccountId();

    if ($managerAccountId <= 0) {
        throw new Exception('Invalid HR Manager session.');
    }

    if ($rosterId <= 0) {
        throw new Exception('Invalid roster ID.');
    }

    if ($reviewNotes === '') {
        throw new Exception('Remarks are required when returning roster.');
    }

    $db->begin_transaction();

    try {
        $roster = fetchRosterHeader($db, $rosterId, true);
        if (!$roster) {
            throw new Exception('Roster not found.');
        }

        $currentStatus = strtoupper((string)($roster['Status'] ?? ''));

        if ($currentStatus === 'PUBLISHED') {
            throw new Exception('Published roster can no longer be returned.');
        }

        $stmt = $db->prepare("
            UPDATE weekly_roster
            SET Status = 'RETURNED',
                ReviewNotes = ?,
                ReviewedByAccountID = ?,
                ReviewedAt = NOW(),
                UpdatedAt = NOW()
            WHERE RosterID = ?
        ");

        if (!$stmt) {
            throw new Exception('Failed to prepare return update.');
        }

        $stmt->bind_param('sii', $reviewNotes, $managerAccountId, $rosterId);

        if (!$stmt->execute()) {
            throw new Exception('Failed to return roster.');
        }

        $db->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Roster returned successfully.'
        ]);
    } catch (Throwable $e) {
        $db->rollback();
        throw $e;
    }
}

function approveRoster(mysqli $db): void
{
    $rosterId = (int)($_POST['RosterID'] ?? 0);
    $reviewNotes = trim($_POST['ReviewNotes'] ?? '');
    $managerAccountId = getSessionAccountId();

    if ($managerAccountId <= 0) {
        throw new Exception('Invalid HR Manager session.');
    }

    if ($rosterId <= 0) {
        throw new Exception('Invalid roster ID.');
    }

    $db->begin_transaction();

    try {
        $roster = fetchRosterHeader($db, $rosterId, true);
        if (!$roster) {
            throw new Exception('Roster not found.');
        }

        $currentStatus = strtoupper((string)($roster['Status'] ?? ''));

        if ($currentStatus === 'PUBLISHED') {
            throw new Exception('Roster is already published.');
        }

        $assignments = fetchRosterAssignmentsFlat($db, $rosterId);
        if (empty($assignments)) {
            throw new Exception('Cannot publish an empty roster.');
        }

        $periodId = findExistingTimesheetPeriod(
            $db,
            (int)$roster['DepartmentID'],
            $roster['WeekStart'],
            $roster['WeekEnd']
        );

        if ($periodId <= 0) {
            $periodId = createTimesheetPeriod(
                $db,
                (int)$roster['DepartmentID'],
                $roster['WeekStart'],
                $roster['WeekEnd'],
                $managerAccountId,
                $reviewNotes
            );
        }

        insertTimesheetDailyRows($db, $periodId, $assignments);

        $stmt = $db->prepare("
            UPDATE weekly_roster
            SET Status = 'PUBLISHED',
                ReviewNotes = ?,
                ReviewedByAccountID = ?,
                ReviewedAt = NOW(),
                PublishedByAccountID = ?,
                PublishedAt = NOW(),
                UpdatedAt = NOW()
            WHERE RosterID = ?
        ");

        if (!$stmt) {
            throw new Exception('Failed to prepare publish update.');
        }

        $stmt->bind_param('siii', $reviewNotes, $managerAccountId, $managerAccountId, $rosterId);

        if (!$stmt->execute()) {
            throw new Exception('Failed to publish roster.');
        }

        $db->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Roster published successfully.',
            'PeriodID' => $periodId
        ]);
    } catch (Throwable $e) {
        $db->rollback();
        throw $e;
    }
}

function fetchRosterHeader(mysqli $db, int $rosterId, bool $forUpdate = false): ?array
{
    $lock = $forUpdate ? ' FOR UPDATE' : '';

    $sql = "
        SELECT
            wr.RosterID,
            wr.DepartmentID,
            wr.WeekStart,
            wr.WeekEnd,
            wr.Status,
            wr.CreatedByAccountID,
            wr.ReviewNotes,
            wr.ReviewedByAccountID,
            wr.ReviewedAt,
            wr.PublishedByAccountID,
            wr.PublishedAt,
            d.DepartmentName,
            CONCAT(
                COALESCE(cEmp.FirstName, ''), ' ',
                COALESCE(cEmp.MiddleName, ''), ' ',
                COALESCE(cEmp.LastName, '')
            ) AS CreatedByName,
            CONCAT(
                COALESCE(rEmp.FirstName, ''), ' ',
                COALESCE(rEmp.MiddleName, ''), ' ',
                COALESCE(rEmp.LastName, '')
            ) AS ReviewedByName
        FROM weekly_roster wr
        LEFT JOIN department d
            ON d.DepartmentID = wr.DepartmentID
        LEFT JOIN useraccounts cUa
            ON cUa.AccountID = wr.CreatedByAccountID
        LEFT JOIN employee cEmp
            ON cEmp.EmployeeID = cUa.EmployeeID
        LEFT JOIN useraccounts rUa
            ON rUa.AccountID = wr.ReviewedByAccountID
        LEFT JOIN employee rEmp
            ON rEmp.EmployeeID = rUa.EmployeeID
        WHERE wr.RosterID = ?
        LIMIT 1
        {$lock}
    ";

    $stmt = $db->prepare($sql);
    if (!$stmt) {
        throw new Exception('Failed to prepare roster header query: ' . $db->error);
    }

    $stmt->bind_param('i', $rosterId);
    $stmt->execute();

    $row = $stmt->get_result()->fetch_assoc();

    if ($row) {
        $row['CreatedByName'] = cleanName($row['CreatedByName'] ?? '');
        $row['ReviewedByName'] = cleanName($row['ReviewedByName'] ?? '');
    }

    return $row ?: null;
}

function fetchRosterAssignments(mysqli $db, int $rosterId): array
{
    $sql = "
        SELECT
            ra.AssignmentID,
            ra.EmployeeID,
            ra.WorkDate,
            ra.ShiftCode,
            st.ShiftName,
            e.EmployeeCode,
            CONCAT(
                COALESCE(e.FirstName, ''), ' ',
                COALESCE(e.MiddleName, ''), ' ',
                COALESCE(e.LastName, '')
            ) AS EmployeeName,
            p.PositionName
        FROM roster_assignment ra
        INNER JOIN employee e
            ON e.EmployeeID = ra.EmployeeID
        LEFT JOIN employmentinformation ei
            ON ei.EmployeeID = e.EmployeeID
        LEFT JOIN positions p
            ON p.PositionID = ei.PositionID
        LEFT JOIN shift_type st
            ON st.ShiftCode = ra.ShiftCode
        WHERE ra.RosterID = ?
        ORDER BY EmployeeName ASC, ra.WorkDate ASC
    ";

    $stmt = $db->prepare($sql);
    if (!$stmt) {
        throw new Exception('Failed to prepare roster assignments query: ' . $db->error);
    }

    $stmt->bind_param('i', $rosterId);
    $stmt->execute();

    $result = $stmt->get_result();
    $employees = [];

    while ($row = $result->fetch_assoc()) {
        $empId = (int)$row['EmployeeID'];

        if (!isset($employees[$empId])) {
            $employees[$empId] = [
                'employee_id'   => $empId,
                'employee_code' => $row['EmployeeCode'] ?? '',
                'employee_name' => cleanName($row['EmployeeName'] ?? 'Unknown'),
                'position_name' => $row['PositionName'] ?? '',
                'schedule'      => []
            ];
        }

        $employees[$empId]['schedule'][$row['WorkDate']] = trim((string)($row['ShiftCode'] ?? ''));
    }

    return $employees;
}

function fetchRosterAssignmentsFlat(mysqli $db, int $rosterId): array
{
    $sql = "
        SELECT
            ra.AssignmentID,
            ra.RosterID,
            ra.EmployeeID,
            ra.WorkDate,
            ra.ShiftCode,
            st.StartTime,
            st.EndTime,
            st.BreakMinutes
        FROM roster_assignment ra
        LEFT JOIN shift_type st
            ON st.ShiftCode = ra.ShiftCode
        WHERE ra.RosterID = ?
        ORDER BY ra.EmployeeID ASC, ra.WorkDate ASC
    ";

    $stmt = $db->prepare($sql);
    if (!$stmt) {
        throw new Exception('Failed to prepare flat roster assignment query: ' . $db->error);
    }

    $stmt->bind_param('i', $rosterId);
    $stmt->execute();

    $result = $stmt->get_result();
    $rows = [];

    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }

    return $rows;
}

function findExistingTimesheetPeriod(mysqli $db, int $departmentId, string $startDate, string $endDate): int
{
    $stmt = $db->prepare("
        SELECT PeriodID
        FROM timesheet_period
        WHERE DepartmentID = ?
          AND StartDate = ?
          AND EndDate = ?
        LIMIT 1
    ");

    if (!$stmt) {
        throw new Exception('Failed to check existing timesheet period.');
    }

    $stmt->bind_param('iss', $departmentId, $startDate, $endDate);
    $stmt->execute();

    $row = $stmt->get_result()->fetch_assoc();
    return $row ? (int)$row['PeriodID'] : 0;
}

function createTimesheetPeriod(
    mysqli $db,
    int $departmentId,
    string $startDate,
    string $endDate,
    int $preparedByAccountId,
    string $reviewNotes = ''
): int {
    $stmt = $db->prepare("
        INSERT INTO timesheet_period
        (
            DepartmentID,
            StartDate,
            EndDate,
            Status,
            PreparedByAccountID,
            PreparedAt,
            ReviewNotes
        )
        VALUES (?, ?, ?, 'DRAFT', ?, NOW(), ?)
    ");

    if (!$stmt) {
        throw new Exception('Failed to prepare timesheet period insert.');
    }

    $stmt->bind_param('issis', $departmentId, $startDate, $endDate, $preparedByAccountId, $reviewNotes);

    if (!$stmt->execute()) {
        throw new Exception('Failed to create timesheet period.');
    }

    return (int)$db->insert_id;
}

function insertTimesheetDailyRows(mysqli $db, int $periodId, array $assignments): void
{
    $checkStmt = $db->prepare("
        SELECT TimesheetDayID
        FROM timesheet_daily
        WHERE PeriodID = ? AND EmployeeID = ? AND WorkDate = ?
        LIMIT 1
    ");

    if (!$checkStmt) {
        throw new Exception('Failed to prepare timesheet duplicate check.');
    }

    $insertStmt = $db->prepare("
        INSERT INTO timesheet_daily
        (
            PeriodID,
            EmployeeID,
            WorkDate,
            AssignmentID,
            ShiftCode,
            ScheduledStart,
            ScheduledEnd,
            BreakMinutesPlanned,
            DayStatus,
            Remarks
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    if (!$insertStmt) {
        throw new Exception('Failed to prepare timesheet insert.');
    }

    foreach ($assignments as $row) {
        $periodIdVal = $periodId;
        $employeeId = (int)$row['EmployeeID'];
        $workDate = (string)$row['WorkDate'];
        $assignmentId = !empty($row['AssignmentID']) ? (int)$row['AssignmentID'] : null;
        $shiftCode = strtoupper(trim((string)($row['ShiftCode'] ?? '')));
        $scheduledStart = !empty($row['StartTime']) ? $row['StartTime'] : null;
        $scheduledEnd = !empty($row['EndTime']) ? $row['EndTime'] : null;
        $breakMinutes = isset($row['BreakMinutes']) ? (int)$row['BreakMinutes'] : 0;

        if ($shiftCode === 'OFF') {
            $dayStatus = 'OFF';
        } elseif ($shiftCode === '') {
            $dayStatus = 'NO_SCHEDULE';
        } else {
            $dayStatus = 'OK';
        }

        $remarks = 'Seeded from published roster';

        $checkStmt->bind_param('iis', $periodIdVal, $employeeId, $workDate);
        $checkStmt->execute();
        $existing = $checkStmt->get_result()->fetch_assoc();

        if ($existing) {
            continue;
        }

        $insertStmt->bind_param(
            'iisisssiss',
            $periodIdVal,
            $employeeId,
            $workDate,
            $assignmentId,
            $shiftCode,
            $scheduledStart,
            $scheduledEnd,
            $breakMinutes,
            $dayStatus,
            $remarks
        );

        if (!$insertStmt->execute()) {
            throw new Exception("Failed to insert timesheet row for EmployeeID {$employeeId} on {$workDate}.");
        }
    }
}

function buildRosterDays(string $start, string $end): array
{
    $days = [];

    $startDate = new DateTime($start);
    $endDate = new DateTime($end);
    $endDate->modify('+1 day');

    $period = new DatePeriod($startDate, new DateInterval('P1D'), $endDate);

    foreach ($period as $date) {
        if ($date->format('w') == 0) {
            continue; // skip Sunday
        }

        $days[] = [
            'full_date' => $date->format('Y-m-d'),
            'day_short' => $date->format('D'),
            'day_date'  => $date->format('M j')
        ];
    }

    return $days;
}

function buildCoverageSummary(array $employees, array $days): array
{
    $am = 0;
    $md = 0;
    $gy = 0;
    $off = 0;
    $scheduled = 0;
    $noSchedule = 0;

    foreach ($employees as $emp) {
        foreach ($days as $day) {
            $shift = strtoupper(trim((string)($emp['schedule'][$day['full_date']] ?? '')));

            if ($shift === 'AM') {
                $am++;
                $scheduled++;
            } elseif ($shift === 'MD') {
                $md++;
                $scheduled++;
            } elseif ($shift === 'GY') {
                $gy++;
                $scheduled++;
            } elseif ($shift === 'OFF') {
                $off++;
            } else {
                $noSchedule++;
            }
        }
    }

    return [
        'total_employees'   => count($employees),
        'total_days'        => count($days),
        'am_count'          => $am,
        'md_count'          => $md,
        'gy_count'          => $gy,
        'off_count'         => $off,
        'scheduled_count'   => $scheduled,
        'no_schedule_count' => $noSchedule
    ];
}

function buildValidationChecks(array $employees, array $days): array
{
    $critical = [];
    $warnings = [];

    foreach ($employees as $emp) {
        $name = $emp['employee_name'] ?? 'Employee';
        $schedule = $emp['schedule'] ?? [];
        $assignedCount = 0;
        $nonOffStreak = 0;
        $maxNonOffStreak = 0;

        foreach ($days as $day) {
            $date = $day['full_date'];
            $shift = strtoupper(trim((string)($schedule[$date] ?? '')));

            if ($shift === '') {
                $critical[] = "{$name}: No schedule on {$date}";
                $nonOffStreak = 0;
                continue;
            }

            $assignedCount++;

            if ($shift !== 'OFF') {
                $nonOffStreak++;
                $maxNonOffStreak = max($maxNonOffStreak, $nonOffStreak);
            } else {
                $nonOffStreak = 0;
            }
        }

        if ($assignedCount === 0) {
            $critical[] = "{$name}: Employee has no assignments for the entire roster period";
        }

        if ($maxNonOffStreak > 6) {
            $warnings[] = "{$name}: Exceeds 6 consecutive workdays";
        }
    }

    return [
        'critical' => array_values(array_unique($critical)),
        'warnings' => array_values(array_unique($warnings))
    ];
}

function getSessionAccountId(): int
{
    return (int)($_SESSION['account_id'] ?? $_SESSION['AccountID'] ?? $_SESSION['user_id'] ?? 0);
}

function formatPeriod(string $start, string $end): string
{
    return date('F j, Y', strtotime($start)) . ' - ' . date('F j, Y', strtotime($end));
}

function cleanName(string $name): string
{
    return preg_replace('/\s+/', ' ', trim($name));
}