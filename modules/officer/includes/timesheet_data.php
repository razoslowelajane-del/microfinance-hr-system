<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);
error_reporting(E_ALL);

set_error_handler(function ($severity, $message, $file, $line) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => "PHP Error: $message in $file on line $line"
    ]);
    exit;
});

set_exception_handler(function ($e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Exception: ' . $e->getMessage()
    ]);
    exit;
});

register_shutdown_function(function () {
    $error = error_get_last();
    if ($error !== null) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Fatal Error: ' . $error['message']
        ]);
    }
});

/*
|--------------------------------------------------------------------------
| SESSION CHECK
|--------------------------------------------------------------------------
*/
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Session expired. Please login again.'
    ]);
    exit;
}

if (!isset($_SESSION['department_id']) || !isset($_SESSION['employee_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing department session. Please re-login.'
    ]);
    exit;
}

/*
|--------------------------------------------------------------------------
| CONFIG / DB
|--------------------------------------------------------------------------
| Ayusin mo lang ang path kung iba ang tunay mong structure.
|--------------------------------------------------------------------------
*/
require_once __DIR__ . '/../../../config/config.php';

if (!isset($conn) || !($conn instanceof mysqli)) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database connection not found. Check config.php path and $conn variable.'
    ]);
    exit;
}

/*
|--------------------------------------------------------------------------
| SESSION VALUES
|--------------------------------------------------------------------------
*/
$deptId       = (int)($_SESSION['department_id'] ?? 0);
$accountId    = (int)($_SESSION['account_id'] ?? $_SESSION['AccountID'] ?? $_SESSION['user_id'] ?? 0);
$myEmployeeId = (int)($_SESSION['employee_id'] ?? $_SESSION['EmployeeID'] ?? 0);

$action = $_GET['action'] ?? $_POST['action'] ?? '';

function respond($data)
{
    echo json_encode($data);
    exit;
}

function fmtPeriodLabel($start, $end)
{
    $s = date('M j, Y', strtotime($start));
    $e = date('M j, Y', strtotime($end));
    return $s . ' - ' . $e;
}

function getPeriodStatusBadgeClass($status)
{
    return match ($status) {
        'DRAFT'      => 'draft',
        'FOR_REVIEW' => 'bg-pending',
        'RETURNED'   => 'bg-danger',
        'APPROVED'   => 'bg-success',
        'FINALIZED'  => 'bg-success',
        default      => 'draft'
    };
}

function safeJsonDecode($json)
{
    $decoded = json_decode($json, true);
    return is_array($decoded) ? $decoded : null;
}

function getTimesheetAiReview(array $period, array $stats, array $rows, array $issues)
{
    if (!defined('GROQ_API_KEY') || !GROQ_API_KEY || GROQ_API_KEY === 'GROQ_API_KEY') {
        return [
            'enabled' => false,
            'summary' => 'Groq AI is not configured yet.',
            'items'   => [
                'Set a valid GROQ_API_KEY in config.php to enable AI review.'
            ]
        ];
    }

    $topRows   = array_slice($rows, 0, 12);
    $topIssues = array_slice($issues, 0, 12);

    $payloadData = [
        'period' => [
            'label'  => $period['label'] ?? '',
            'status' => $period['status'] ?? ''
        ],
        'stats' => $stats,
        'employees' => array_map(function ($r) {
            return [
                'name'        => $r['name'] ?? '',
                'is_self'     => $r['is_self'] ?? false,
                'position'    => $r['position'] ?? '',
                'reg'         => $r['reg'] ?? 0,
                'ot'          => $r['ot'] ?? 0,
                'late'        => $r['late'] ?? 0,
                'abs'         => $r['abs'] ?? 0,
                'paid_leave'  => $r['paid_leave'] ?? 0,
                'excused'     => $r['excused'] ?? 0,
                'final'       => $r['final'] ?? 0,
                'status'      => $r['status'] ?? '',
                'issue_count' => $r['issue_count'] ?? 0
            ];
        }, $topRows),
        'issues' => $topIssues
    ];

    $systemPrompt = <<<PROMPT
You are an HR timesheet review assistant for a microfinance HR system.
Analyze the provided timesheet period data and return STRICT JSON only.

Return JSON with this exact structure:
{
  "summary": "short paragraph",
  "items": [
    "bullet 1",
    "bullet 2",
    "bullet 3"
  ]
}

Rules:
- Focus on attendance risks, late minutes, absences, missing/incomplete logs, no-schedule issues, and records needing manual verification.
- Mention the logged-in officer row if it has issues.
- Keep summary concise.
- Keep items short and actionable.
- Do not return markdown.
- Do not include text outside JSON.
PROMPT;

    $userPrompt = "Analyze this timesheet dataset:\n" . json_encode($payloadData, JSON_UNESCAPED_UNICODE);

    $requestBody = [
        'model' => 'llama-3.3-70b-versatile',
        'temperature' => 0.2,
        'messages' => [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt]
        ]
    ];

    $ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . GROQ_API_KEY
        ],
        CURLOPT_POSTFIELDS => json_encode($requestBody),
        CURLOPT_TIMEOUT => 30
    ]);

    $raw = curl_exec($ch);

    if ($raw === false) {
        $err = curl_error($ch);
        curl_close($ch);

        return [
            'enabled' => false,
            'summary' => 'AI review unavailable.',
            'items'   => ["Groq request failed: {$err}"]
        ];
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $decoded = safeJsonDecode($raw);

    if ($httpCode < 200 || $httpCode >= 300 || !$decoded) {
        return [
            'enabled' => false,
            'summary' => 'AI review unavailable.',
            'items'   => ['Groq returned an invalid response.']
        ];
    }

    $content = $decoded['choices'][0]['message']['content'] ?? '';
    if (!$content) {
        return [
            'enabled' => false,
            'summary' => 'AI review unavailable.',
            'items'   => ['No AI content returned.']
        ];
    }

    $ai = safeJsonDecode(trim($content));

    if (!$ai) {
        return [
            'enabled' => false,
            'summary' => 'AI review returned a non-JSON response.',
            'items'   => [mb_substr(strip_tags($content), 0, 220)]
        ];
    }

    return [
        'enabled' => true,
        'summary' => $ai['summary'] ?? 'AI review generated.',
        'items'   => (isset($ai['items']) && is_array($ai['items']) && count($ai['items']) > 0)
            ? array_values($ai['items'])
            : ['No specific AI recommendations returned.']
    ];
}

/*
|--------------------------------------------------------------------------
| 1. GET PERIODS
|--------------------------------------------------------------------------
*/
if ($action === 'get_periods') {
    $sql = "SELECT PeriodID, StartDate, EndDate, Status
            FROM timesheet_period
            WHERE DepartmentID = ?
            ORDER BY StartDate DESC, EndDate DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $deptId);
    $stmt->execute();
    $res = $stmt->get_result();

    $periods = [];
    while ($row = $res->fetch_assoc()) {
        $periods[] = [
            'period_id' => (int)$row['PeriodID'],
            'label'     => fmtPeriodLabel($row['StartDate'], $row['EndDate']),
            'start'     => $row['StartDate'],
            'end'       => $row['EndDate'],
            'status'    => $row['Status']
        ];
    }

    respond([
        'success' => true,
        'periods' => $periods
    ]);
}

/*
|--------------------------------------------------------------------------
| 2. GET PERIOD DATA
|--------------------------------------------------------------------------
*/
if ($action === 'get_period_data') {
    $periodId = (int)($_GET['period_id'] ?? 0);

    if ($periodId <= 0) {
        respond([
            'success' => false,
            'message' => 'Invalid period.'
        ]);
    }

    $periodSql = "SELECT *
                  FROM timesheet_period
                  WHERE PeriodID = ? AND DepartmentID = ?
                  LIMIT 1";
    $stmt = $conn->prepare($periodSql);
    $stmt->bind_param("ii", $periodId, $deptId);
    $stmt->execute();
    $period = $stmt->get_result()->fetch_assoc();

    if (!$period) {
        respond([
            'success' => false,
            'message' => 'Timesheet period not found.'
        ]);
    }

    $sql = "
        SELECT
            e.EmployeeID,
            e.EmployeeCode,
            CONCAT(e.FirstName, ' ', e.LastName) AS EmployeeName,
            COALESCE(p.PositionName, 'N/A') AS PositionName,

            COALESCE(tes.RegularHours, 0) AS RegularHours,
            COALESCE(tes.OvertimeHours, 0) AS OvertimeHours,
            COALESCE(tes.LateMinutes, 0) AS LateMinutes,
            COALESCE(tes.AbsencesHours, 0) AS AbsencesHours,
            COALESCE(tes.PaidLeaveHours, 0) AS PaidLeaveHours,
            COALESCE(tes.UnpaidLeaveHours, 0) AS UnpaidLeaveHours,
            COALESCE(tes.UndertimeMinutes, 0) AS UndertimeMinutes,
            COALESCE(tes.TotalPayableHours, 0) AS TotalPayableHours,
            tes.Notes,

            COALESCE(lb.RemainingLeaveCredits, 0) AS RemainingLeaveCredits,
            COALESCE(issueAgg.IssueCount, 0) AS IssueCount,

            CASE
                WHEN e.EmployeeID = ? THEN 0
                ELSE 1
            END AS SortPriority

        FROM employmentinformation ei
        INNER JOIN employee e
            ON e.EmployeeID = ei.EmployeeID
        LEFT JOIN positions p
            ON p.PositionID = ei.PositionID
        LEFT JOIN timesheet_employee_summary tes
            ON tes.EmployeeID = ei.EmployeeID
           AND tes.PeriodID = ?
        LEFT JOIN (
            SELECT
                EmployeeID,
                SUM(RemainingCredits) AS RemainingLeaveCredits
            FROM employee_leave_balances
            WHERE Year = YEAR(CURDATE())
            GROUP BY EmployeeID
        ) lb
            ON lb.EmployeeID = ei.EmployeeID
        LEFT JOIN (
            SELECT
                td.EmployeeID,
                COUNT(*) AS IssueCount
            FROM timesheet_daily td
            WHERE td.PeriodID = ?
              AND td.DayStatus IN ('FLAGGED','INCOMPLETE','NO_SCHEDULE')
            GROUP BY td.EmployeeID
        ) issueAgg
            ON issueAgg.EmployeeID = ei.EmployeeID
        WHERE ei.DepartmentID = ?
          AND COALESCE(ei.EmploymentStatus, 'Regular') <> 'Inactive'
          AND (
                e.EmployeeID = ?
                OR e.EmployeeID IN (
                    SELECT se.EmployeeID
                    FROM supervisor_employees se
                    WHERE se.SupervisorAccountID = ?
                      AND se.DepartmentID = ?
                      AND se.IsActive = 1
                )
          )
        ORDER BY SortPriority ASC, e.FirstName ASC, e.LastName ASC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "iiiiiii",
        $myEmployeeId,
        $periodId,
        $periodId,
        $deptId,
        $myEmployeeId,
        $accountId,
        $deptId
    );
    $stmt->execute();
    $res = $stmt->get_result();

    $rows = [];
    $totalEmployees = 0;
    $totalOt = 0;
    $totalLate = 0;
    $totalIssues = 0;

    while ($row = $res->fetch_assoc()) {
        $totalEmployees++;
        $totalOt += (float)$row['OvertimeHours'];
        $totalLate += (int)$row['LateMinutes'];
        $totalIssues += (int)$row['IssueCount'];

        $deductionHours = ((float)$row['AbsencesHours']) + (((int)$row['UndertimeMinutes']) / 60);
        $status = 'Ready';

        if ((int)$row['IssueCount'] > 0) {
            $status = 'Review';
        } elseif ((float)$row['TotalPayableHours'] <= 0 && (float)$row['RegularHours'] <= 0) {
            $status = 'No Data';
        }

        $rows[] = [
            'employee_id'   => (int)$row['EmployeeID'],
            'name'          => $row['EmployeeName'],
            'code'          => $row['EmployeeCode'] ?? '-',
            'position'      => $row['PositionName'] ?? 'N/A',
            'reg'           => round((float)$row['RegularHours'], 2),
            'ot'            => round((float)$row['OvertimeHours'], 2),
            'late'          => (int)$row['LateMinutes'],
            'abs'           => round((float)$row['AbsencesHours'], 2),
            'leave_credits' => round((float)$row['RemainingLeaveCredits'], 2),
            'paid_leave'    => round((float)$row['PaidLeaveHours'], 2),
            'excused'       => round((float)$row['UnpaidLeaveHours'], 2),
            'deduction'     => round($deductionHours, 2),
            'final'         => round((float)$row['TotalPayableHours'], 2),
            'status'        => $status,
            'issue_count'   => (int)$row['IssueCount'],
            'notes'         => $row['Notes'] ?? '',
            'is_self'       => ((int)$row['EmployeeID'] === $myEmployeeId)
        ];
    }

    $issueSql = "
        SELECT
            td.EmployeeID,
            CONCAT(e.FirstName, ' ', e.LastName) AS EmployeeName,
            td.WorkDate,
            td.DayStatus,
            COALESCE(td.Remarks, '') AS Remarks,
            CASE
                WHEN td.EmployeeID = ? THEN 0
                ELSE 1
            END AS SortPriority
        FROM timesheet_daily td
        INNER JOIN employee e
            ON e.EmployeeID = td.EmployeeID
        INNER JOIN employmentinformation ei
            ON ei.EmployeeID = td.EmployeeID
        WHERE td.PeriodID = ?
          AND ei.DepartmentID = ?
          AND td.DayStatus IN ('FLAGGED','INCOMPLETE','NO_SCHEDULE')
          AND (
                td.EmployeeID = ?
                OR td.EmployeeID IN (
                    SELECT se.EmployeeID
                    FROM supervisor_employees se
                    WHERE se.SupervisorAccountID = ?
                      AND se.DepartmentID = ?
                      AND se.IsActive = 1
                )
          )
        ORDER BY SortPriority ASC, td.WorkDate ASC, EmployeeName ASC
        LIMIT 20
    ";

    $stmt = $conn->prepare($issueSql);
    $stmt->bind_param(
        "iiiiii",
        $myEmployeeId,
        $periodId,
        $deptId,
        $myEmployeeId,
        $accountId,
        $deptId
    );
    $stmt->execute();
    $issueRes = $stmt->get_result();

    $issues = [];
    while ($issue = $issueRes->fetch_assoc()) {
        $label = match ($issue['DayStatus']) {
            'FLAGGED'     => 'Flagged record',
            'INCOMPLETE'  => 'Incomplete logs',
            'NO_SCHEDULE' => 'No schedule but may have logs',
            default       => $issue['DayStatus']
        };

        $issues[] = [
            'employee_id'   => (int)$issue['EmployeeID'],
            'employee_name' => $issue['EmployeeName'],
            'work_date'     => $issue['WorkDate'],
            'status'        => $issue['DayStatus'],
            'message'       => $issue['Remarks'] !== '' ? $issue['Remarks'] : $label
        ];
    }

    $periodData = [
        'period_id'    => (int)$period['PeriodID'],
        'label'        => fmtPeriodLabel($period['StartDate'], $period['EndDate']),
        'start'        => $period['StartDate'],
        'end'          => $period['EndDate'],
        'status'       => $period['Status'],
        'status_class' => getPeriodStatusBadgeClass($period['Status'])
    ];

    $stats = [
        'employees'    => $totalEmployees,
        'ot_hours'     => round($totalOt, 2),
        'late_minutes' => $totalLate,
        'issues'       => $totalIssues
    ];

    $aiReview = getTimesheetAiReview($periodData, $stats, $rows, $issues);

    respond([
        'success'   => true,
        'period'    => $periodData,
        'stats'     => $stats,
        'rows'      => $rows,
        'issues'    => $issues,
        'ai_review' => $aiReview
    ]);
}

/*
|--------------------------------------------------------------------------
| 3. EMPLOYEE LOGS
|--------------------------------------------------------------------------
*/
if ($action === 'employee_logs') {
    $periodId   = (int)($_GET['period_id'] ?? 0);
    $employeeId = (int)($_GET['employee_id'] ?? 0);

    if ($periodId <= 0 || $employeeId <= 0) {
        respond([
            'success' => false,
            'message' => 'Invalid employee or period.'
        ]);
    }

    $sql = "
        SELECT
            td.WorkDate,
            td.ShiftCode,
            td.ScheduledStart,
            td.ScheduledEnd,
            td.ActualTimeIn,
            td.ActualTimeOut,
            td.RegularMinutes,
            td.OvertimeMinutes,
            td.LateMinutes,
            td.UndertimeMinutes,
            td.DayStatus,
            td.Remarks
        FROM timesheet_daily td
        INNER JOIN employmentinformation ei
            ON ei.EmployeeID = td.EmployeeID
        WHERE td.PeriodID = ?
          AND td.EmployeeID = ?
          AND ei.DepartmentID = ?
          AND (
                td.EmployeeID = ?
                OR td.EmployeeID IN (
                    SELECT se.EmployeeID
                    FROM supervisor_employees se
                    WHERE se.SupervisorAccountID = ?
                      AND se.DepartmentID = ?
                      AND se.IsActive = 1
                )
          )
        ORDER BY td.WorkDate ASC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "iiiiii",
        $periodId,
        $employeeId,
        $deptId,
        $myEmployeeId,
        $accountId,
        $deptId
    );
    $stmt->execute();
    $res = $stmt->get_result();

    $logs = [];
    while ($row = $res->fetch_assoc()) {
        $logs[] = $row;
    }

    respond([
        'success' => true,
        'logs'    => $logs
    ]);
}

/*
|--------------------------------------------------------------------------
| 4. SEND TO HR
|--------------------------------------------------------------------------
*/
if ($action === 'send_to_hr') {
    $periodId = (int)($_POST['period_id'] ?? 0);

    if ($periodId <= 0) {
        respond([
            'success' => false,
            'message' => 'Invalid period.'
        ]);
    }

    $sql = "
        UPDATE timesheet_period
        SET Status = 'FOR_REVIEW',
            PreparedByAccountID = ?,
            PreparedAt = NOW()
        WHERE PeriodID = ?
          AND DepartmentID = ?
          AND Status IN ('DRAFT', 'RETURNED')
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $accountId, $periodId, $deptId);
    $stmt->execute();

    if ($stmt->affected_rows <= 0) {
        respond([
            'success' => false,
            'message' => 'This period cannot be sent. It may already be under review or approved.'
        ]);
    }

    respond([
        'success' => true,
        'message' => 'Timesheet successfully sent to HR Manager.'
    ]);
}

/*
|--------------------------------------------------------------------------
| 5. RECOMPUTE ALL
|--------------------------------------------------------------------------
*/
if ($action === 'recompute_all') {
    $periodId = (int)($_POST['period_id'] ?? 0);

    if ($periodId <= 0) {
        respond([
            'success' => false,
            'message' => 'Invalid period.'
        ]);
    }

    $conn->begin_transaction();

    try {
        $check = $conn->prepare("
            SELECT PeriodID
            FROM timesheet_period
            WHERE PeriodID = ?
              AND DepartmentID = ?
            LIMIT 1
        ");
        $check->bind_param("ii", $periodId, $deptId);
        $check->execute();
        $found = $check->get_result()->fetch_assoc();

        if (!$found) {
            throw new Exception('Period not found for this department.');
        }

        $del = $conn->prepare("DELETE FROM timesheet_employee_summary WHERE PeriodID = ?");
        $del->bind_param("i", $periodId);
        $del->execute();

        $insertSql = "
            INSERT INTO timesheet_employee_summary (
                PeriodID,
                EmployeeID,
                DepartmentID,
                PositionID,
                IsEligibleForHolidayPay,
                RegularHours,
                OvertimeHours,
                NightDiffHours,
                RegHolidayHours,
                SpecHolidayHours,
                UnworkedHolidayHours,
                HolidayOvertimeHours,
                LateMinutes,
                UndertimeMinutes,
                AbsencesHours,
                PaidLeaveHours,
                UnpaidLeaveHours,
                TotalPayableHours,
                Notes,
                CreatedAt,
                UpdatedAt
            )
            SELECT
                td.PeriodID,
                td.EmployeeID,
                ei.DepartmentID,
                ei.PositionID,
                1 AS IsEligibleForHolidayPay,

                ROUND(SUM(td.RegularMinutes) / 60, 2) AS RegularHours,
                ROUND(SUM(td.OvertimeMinutes) / 60, 2) AS OvertimeHours,
                ROUND(SUM(td.NightDiffMinutes) / 60, 2) AS NightDiffHours,

                ROUND(SUM(CASE WHEN dc.PayCode = 'HOL_REG' THEN dc.Minutes ELSE 0 END) / 60, 2) AS RegHolidayHours,
                ROUND(SUM(CASE WHEN dc.PayCode = 'HOL_SPEC' THEN dc.Minutes ELSE 0 END) / 60, 2) AS SpecHolidayHours,
                ROUND(SUM(CASE WHEN dc.PayCode = 'HOL_UNWORKED' THEN dc.Minutes ELSE 0 END) / 60, 2) AS UnworkedHolidayHours,
                ROUND(SUM(CASE WHEN dc.PayCode = 'HOL_OT' THEN dc.Minutes ELSE 0 END) / 60, 2) AS HolidayOvertimeHours,

                SUM(td.LateMinutes) AS LateMinutes,
                SUM(td.UndertimeMinutes) AS UndertimeMinutes,

                ROUND(SUM(CASE WHEN td.DayStatus = 'ABSENT' THEN 480 ELSE 0 END) / 60, 2) AS AbsencesHours,
                ROUND(SUM(CASE WHEN dc.PayCode = 'LEAVE_PAID' THEN dc.Minutes ELSE 0 END) / 60, 2) AS PaidLeaveHours,
                ROUND(SUM(CASE WHEN dc.PayCode = 'LEAVE_UNPAID' THEN dc.Minutes ELSE 0 END) / 60, 2) AS UnpaidLeaveHours,

                ROUND(
                    (
                        SUM(td.RegularMinutes)
                        + SUM(td.OvertimeMinutes)
                        + SUM(td.NightDiffMinutes)
                        + SUM(CASE WHEN dc.PayCode IN ('HOL_REG','HOL_SPEC','HOL_UNWORKED','HOL_OT','LEAVE_PAID') THEN dc.Minutes ELSE 0 END)
                    ) / 60, 2
                ) AS TotalPayableHours,

                NULL AS Notes,
                NOW(),
                NOW()

            FROM timesheet_daily td
            INNER JOIN employmentinformation ei
                ON ei.EmployeeID = td.EmployeeID
            LEFT JOIN timesheet_daily_code dc
                ON dc.TimesheetDayID = td.TimesheetDayID

            WHERE td.PeriodID = ?
              AND ei.DepartmentID = ?
              AND (
                    td.EmployeeID = ?
                    OR td.EmployeeID IN (
                        SELECT se.EmployeeID
                        FROM supervisor_employees se
                        WHERE se.SupervisorAccountID = ?
                          AND se.DepartmentID = ?
                          AND se.IsActive = 1
                    )
              )

            GROUP BY td.PeriodID, td.EmployeeID, ei.DepartmentID, ei.PositionID
        ";

        $stmt = $conn->prepare($insertSql);
        $stmt->bind_param(
            "iiiii",
            $periodId,
            $deptId,
            $myEmployeeId,
            $accountId,
            $deptId
        );
        $stmt->execute();

        $conn->commit();

        respond([
            'success' => true,
            'message' => 'Timesheet summary recomputed successfully.'
        ]);
    } catch (Throwable $e) {
        $conn->rollback();
        respond([
            'success' => false,
            'message' => 'Recompute failed: ' . $e->getMessage()
        ]);
    }
}

respond([
    'success' => false,
    'message' => 'Invalid action.'
]);