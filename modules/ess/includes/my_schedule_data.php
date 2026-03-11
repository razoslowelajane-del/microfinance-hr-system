<?php
require_once __DIR__ . "/auth_employee.php";
require_once __DIR__ . "/../../../config/config.php";

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function json_out(array $data, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($data);
    exit;
}

function day_name_from_date(?string $date): string
{
    if (!$date) return '--';
    $ts = strtotime($date);
    return $ts ? date('l', $ts) : '--';
}

function fmt_time(?string $value): string
{
    if (!$value) return '--';
    $ts = strtotime($value);
    return $ts ? date('g:i A', $ts) : $value;
}

function time_range(?string $start, ?string $end): string
{
    if (!$start && !$end) return '--';
    return fmt_time($start) . ' - ' . fmt_time($end);
}

try {
    if (!isset($conn) || !($conn instanceof mysqli)) {
        json_out([
            'ok' => false,
            'message' => 'Database connection not available.'
        ], 500);
    }

    $conn->set_charset('utf8mb4');

    $employeeId = $_SESSION['employee_id'] ?? $_SESSION['EmployeeID'] ?? null;

    if (!$employeeId) {
        json_out([
            'ok' => false,
            'message' => 'Employee session not found.'
        ], 401);
    }

    $defaultLocation = '--';
    $locRes = $conn->query("SELECT LocationName FROM work_locations WHERE IsActive = 1 ORDER BY LocationID ASC LIMIT 1");
    if ($locRes && $locRow = $locRes->fetch_assoc()) {
        $defaultLocation = $locRow['LocationName'] ?: '--';
    }

    $rosterSql = "
        SELECT DISTINCT
            wr.RosterID,
            wr.WeekStart,
            wr.WeekEnd,
            wr.Status
        FROM weekly_roster wr
        INNER JOIN roster_assignment ra
            ON ra.RosterID = wr.RosterID
        WHERE ra.EmployeeID = ?
          AND CURDATE() BETWEEN wr.WeekStart AND wr.WeekEnd
        ORDER BY wr.WeekStart DESC, wr.RosterID DESC
        LIMIT 1
    ";

    $stmt = $conn->prepare($rosterSql);
    $stmt->bind_param("i", $employeeId);
    $stmt->execute();
    $rosterRes = $stmt->get_result();
    $roster = $rosterRes->fetch_assoc();
    $stmt->close();

    if (!$roster) {
        json_out([
            'ok' => true,
            'period_label' => 'No Active Schedule',
            'summary' => 'No assigned roster was found for your account.',
            'today' => [
                'status' => 'No Schedule',
                'shift_name' => '--',
                'shift_time' => '--',
                'location_name' => '--'
            ],
            'rows' => []
        ]);
    }

    $rosterId = (int)$roster['RosterID'];

    $rowSql = "
        SELECT
            ra.WorkDate,
            ra.ShiftCode,
            st.ShiftName,
            st.StartTime,
            st.EndTime,
            st.BreakMinutes
        FROM roster_assignment ra
        LEFT JOIN shift_type st
            ON st.ShiftCode = ra.ShiftCode
        WHERE ra.EmployeeID = ?
          AND ra.RosterID = ?
        ORDER BY ra.WorkDate ASC
    ";

    $stmt = $conn->prepare($rowSql);
    $stmt->bind_param("ii", $employeeId, $rosterId);
    $stmt->execute();
    $res = $stmt->get_result();

    $rows = [];

    while ($r = $res->fetch_assoc()) {
        $shiftCode = strtoupper(trim((string)($r['ShiftCode'] ?? '')));
        $isOff = ($shiftCode === 'OFF');

        $rows[] = [
            'date' => $r['WorkDate'] ?? '--',
            'day' => day_name_from_date($r['WorkDate'] ?? null),
            'shift_name' => $isOff ? 'Day Off' : ($r['ShiftName'] ?? $shiftCode ?: '--'),
            'shift_time' => $isOff ? '--' : time_range($r['StartTime'] ?? null, $r['EndTime'] ?? null),
            'break_time' => $isOff ? '--' : (($r['BreakMinutes'] ?? null) !== null ? ((int)$r['BreakMinutes']) . ' min' : '--'),
            'location_name' => $defaultLocation,
            'status' => $isOff ? 'Rest Day' : 'Working Day'
        ];
    }

    $stmt->close();

    $todayDate = date('Y-m-d');
    $todayRow = [
        'status' => 'No Schedule',
        'shift_name' => '--',
        'shift_time' => '--',
        'location_name' => '--'
    ];

    foreach ($rows as $row) {
        if ($row['date'] === $todayDate) {
            $todayRow = $row;
            break;
        }
    }

    $periodLabel = date('M d, Y', strtotime($roster['WeekStart'])) . ' - ' . date('M d, Y', strtotime($roster['WeekEnd']));

    json_out([
        'ok' => true,
        'period_label' => $periodLabel,
        'summary' => 'Here is your assigned schedule for the active roster period.',
        'today' => $todayRow,
        'rows' => $rows
    ]);

} catch (Throwable $e) {
    json_out([
        'ok' => false,
        'message' => $e->getMessage()
    ], 500);
}