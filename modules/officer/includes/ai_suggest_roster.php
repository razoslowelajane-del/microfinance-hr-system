<?php
require_once __DIR__ . "/auth_officer.php";
require_once __DIR__ . "/../../../config/config.php";

header('Content-Type: application/json');

function respond($ok, $data = [], $code = 200) {
    http_response_code($code);
    echo json_encode(array_merge(['ok' => $ok], $data));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, ['message' => 'Invalid request method.'], 405);
}

$deptId = null;
foreach (['department_id', 'DepartmentID', 'dept_id'] as $key) {
    if (!empty($_SESSION[$key])) {
        $deptId = (int) $_SESSION[$key];
        break;
    }
}

$accountId = null;
foreach (['account_id', 'AccountID', 'user_id', 'UserID'] as $key) {
    if (!empty($_SESSION[$key])) {
        $accountId = (int) $_SESSION[$key];
        break;
    }
}

$myEmpId = null;
foreach (['employee_id', 'EmployeeID'] as $key) {
    if (!empty($_SESSION[$key])) {
        $myEmpId = (int) $_SESSION[$key];
        break;
    }
}

if (!$deptId || !$accountId) {
    respond(false, [
        'message' => 'Unauthorized session context.',
        'debug' => [
            'deptId' => $deptId,
            'accountId' => $accountId,
            'session_keys' => array_keys($_SESSION)
        ]
    ], 401);
}

$raw = file_get_contents("php://input");
$input = json_decode($raw, true);

$rosterId    = isset($input['roster_id']) ? (int) $input['roster_id'] : 0;
$periodStart = trim($input['period_start'] ?? '');
$periodEnd   = trim($input['period_end'] ?? '');

if (!$rosterId || !$periodStart || !$periodEnd) {
    respond(false, ['message' => 'Missing roster_id, period_start, or period_end.'], 422);
}

$groqApiKey = defined('GROQ_API_KEY') ? GROQ_API_KEY : null;

if (!$groqApiKey) {
    respond(false, ['message' => 'Groq API key is not configured.'], 500);
}

$shifts = [];
$qShifts = $conn->query("
    SELECT ShiftCode, ShiftName, StartTime, EndTime, BreakMinutes, GraceMinutes
    FROM shift_type
    WHERE IsActive = 1
    ORDER BY ShiftCode
");

if (!$qShifts) {
    respond(false, ['message' => 'Failed to fetch shift types.'], 500);
}

while ($row = $qShifts->fetch_assoc()) {
    $shifts[] = $row;
}

if (!$shifts) {
    respond(false, ['message' => 'No active shifts found.'], 404);
}

$holidays = [];
$stmt = $conn->prepare("
    SELECT 
        h.HolidayDate,
        h.HolidayName,
        ht.TypeCode,
        ht.TypeName
    FROM holidays h
    INNER JOIN holiday_type ht ON ht.HolidayTypeID = h.HolidayTypeID
    WHERE h.IsActive = 1
      AND h.HolidayDate BETWEEN ? AND ?
    ORDER BY h.HolidayDate
");

if (!$stmt) {
    respond(false, ['message' => 'Failed to prepare holiday query.', 'error' => $conn->error], 500);
}

$stmt->bind_param("ss", $periodStart, $periodEnd);
$stmt->execute();
$res = $stmt->get_result();

while ($row = $res->fetch_assoc()) {
    $holidays[] = $row;
}
$stmt->close();

$employees = [];
$stmt = $conn->prepare("
    SELECT
        e.EmployeeID,
        e.EmployeeCode,
        CONCAT(e.FirstName, ' ', e.LastName) AS EmployeeName,
        p.PositionName
    FROM employee e
    LEFT JOIN employmentinformation ei
        ON ei.EmployeeID = e.EmployeeID
    LEFT JOIN positions p
        ON p.PositionID = ei.PositionID
    WHERE ei.DepartmentID = ?
    ORDER BY e.LastName, e.FirstName
");

if (!$stmt) {
    respond(false, ['message' => 'Failed to prepare employee query.', 'error' => $conn->error], 500);
}

$stmt->bind_param("i", $deptId);
$stmt->execute();
$res = $stmt->get_result();

while ($row = $res->fetch_assoc()) {
    if ($myEmpId && (int)$row['EmployeeID'] === (int)$myEmpId) {
        continue;
    }
    $employees[] = $row;
}
$stmt->close();

if (!$employees) {
    respond(false, [
        'message' => 'No employees found in this department.',
        'debug' => [
            'deptId' => $deptId
        ]
    ], 404);
}

$assignments = [];
$stmt = $conn->prepare("
    SELECT
        ra.AssignmentID,
        ra.EmployeeID,
        ra.WorkDate,
        ra.ShiftCode
    FROM roster_assignment ra
    INNER JOIN weekly_roster wr
        ON wr.RosterID = ra.RosterID
    WHERE wr.RosterID = ?
      AND wr.DepartmentID = ?
      AND ra.WorkDate BETWEEN ? AND ?
    ORDER BY ra.EmployeeID, ra.WorkDate
");

if (!$stmt) {
    respond(false, ['message' => 'Failed to prepare assignments query.', 'error' => $conn->error], 500);
}

$stmt->bind_param("iiss", $rosterId, $deptId, $periodStart, $periodEnd);
$stmt->execute();
$res = $stmt->get_result();

while ($row = $res->fetch_assoc()) {
    $assignments[] = $row;
}
$stmt->close();

$approvedLeaves = [];
$stmt = $conn->prepare("
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

if (!$stmt) {
    respond(false, ['message' => 'Failed to prepare leave query.', 'error' => $conn->error], 500);
}

$stmt->bind_param("iss", $deptId, $periodEnd, $periodStart);
$stmt->execute();
$res = $stmt->get_result();

while ($row = $res->fetch_assoc()) {
    $approvedLeaves[] = $row;
}
$stmt->close();

$allowedShiftCodes = array_values(array_map(function ($s) {
    return $s['ShiftCode'];
}, $shifts));

$payloadForAI = [
    'department_id' => $deptId,
    'roster_id' => $rosterId,
    'period_start' => $periodStart,
    'period_end' => $periodEnd,
    'employees' => $employees,
    'shifts' => $shifts,
    'holidays' => $holidays,
    'approved_leaves' => $approvedLeaves,
    'existing_assignments' => $assignments,
    'rules' => [
        'fill_empty_cells_only' => true,
        'do_not_edit_employee_id' => $myEmpId,
        'allowed_shift_codes' => $allowedShiftCodes,
        'prefer_balanced_distribution' => true,
        'avoid_long_same_shift_streaks' => true,
        'prefer_keep_existing_assignments' => true,
        'return_json_only' => true
    ]
];

$systemPrompt = <<<EOT
You are an AI roster assistant for an HR scheduling system.

Task:
- Suggest shift assignments only for EMPTY or missing cells.
- Do NOT overwrite existing assignments.
- Use only allowed shift codes.
- Never suggest a shift on dates covered by approved leave.
- If an employee is on approved leave for a date, skip that date.
- Try to distribute shifts fairly.
- Avoid too many consecutive same-shift assignments if possible.
- Sundays should usually remain OFF if operationally reasonable.
- Respect holiday dates and consider lighter scheduling when appropriate.
- Return STRICT JSON only. No markdown. No explanation outside JSON.

Required JSON format:
{
  "summary": "short summary",
  "suggestions": [
    {
      "employee_id": 30,
      "work_date": "2026-03-13",
      "shift_code": "MD",
      "reason": "Balanced schedule and fills empty slot"
    }
  ]
}
EOT;

$requestBody = [
    'model' => 'llama-3.3-70b-versatile',
    'temperature' => 0.2,
    'messages' => [
        ['role' => 'system', 'content' => $systemPrompt],
        ['role' => 'user', 'content' => json_encode($payloadForAI, JSON_UNESCAPED_SLASHES)]
    ]
];

$ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $groqApiKey
    ],
    CURLOPT_POSTFIELDS => json_encode($requestBody),
    CURLOPT_TIMEOUT => 60
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    respond(false, ['message' => 'cURL error: ' . $curlError], 500);
}

$data = json_decode($response, true);

if ($httpCode >= 400) {
    respond(false, [
        'message' => 'Groq API error.',
        'http_code' => $httpCode,
        'error' => $data['error'] ?? $data
    ], 500);
}

$content = $data['choices'][0]['message']['content'] ?? '';

if (!$content) {
    respond(false, ['message' => 'Empty AI response.'], 500);
}

$ai = json_decode($content, true);

if (!is_array($ai)) {
    respond(false, [
        'message' => 'AI response is not valid JSON.',
        'raw' => $content
    ], 500);
}

respond(true, [
    'message' => 'AI suggestions generated.',
    'ai' => $ai
]);