<?php
require_once __DIR__ . "/auth_officer.php";
require_once __DIR__ . "/../../../config/config.php";

header('Content-Type: application/json');

// Siguraduhin na may session deptId, kung wala ay i-set sa 0 o mag-error
$deptId = isset($_SESSION['department_id']) ? (int)$_SESSION['department_id'] : 0;

/**
 * Nagbibilang ng records base sa Department at Status.
 * Gumagamit ng UPPER() para hindi mag-error sa 'Pending' vs 'PENDING'.
 */
function safe_count_department($conn, $table, $employeeCol, $statusCol = null, $statusValue = null) {
    global $deptId;
    if (!$deptId) return 0;

    try {
        $sql = "SELECT COUNT(*) c
                FROM `$table` t
                JOIN employmentinformation ei ON ei.EmployeeID = t.`$employeeCol`
                WHERE ei.DepartmentID = ?";

        $types = "i";
        $params = [$deptId];

        if ($statusCol && $statusValue !== null) {
            $sql .= " AND UPPER(t.`$statusCol`) = UPPER(?)";
            $types .= "s";
            $params[] = $statusValue;
        }

        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return (int)($row['c'] ?? 0);
    } catch (Throwable $e) { return 0; }
}

/**
 * Binibilang ang unique na empleyadong pumasok ngayong araw sa departamento.
 * Ang attendance_event ay walang EmployeeID, kaya dadaan tayo sa attendance_session.
 */
function safe_count_attendance_today($conn) {
    global $deptId;
    if (!$deptId) return 0;

    try {
        $sql = "SELECT COUNT(DISTINCT s.EmployeeID) c
                FROM attendance_event ae
                JOIN attendance_session s ON ae.SessionID = s.SessionID
                JOIN employmentinformation ei ON s.EmployeeID = ei.EmployeeID
                WHERE ei.DepartmentID = ? 
                  AND DATE(ae.EventTime) = CURDATE()";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $deptId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return (int)($row['c'] ?? 0);
    } catch (Throwable $e) { return 0; }
}

/**
 * Kumukuha ng listahan ng mga pending na request (Leave at Claim).
 */
function pending_items($conn, $limit = 4) {
    global $deptId;
    if (!$deptId) return [];
    
    $items = [];
    $tables = [
        ["table" => "leave_requests", "label" => "Leave"],
        ["table" => "reimbursement_claims", "label" => "Claim"]
    ];

    foreach ($tables as $cfg) {
        if (count($items) >= $limit) break;
        $remaining = $limit - count($items);
        
        try {
            $sql = "SELECT e.FirstName, e.LastName, t.Status
                    FROM `{$cfg['table']}` t
                    JOIN employee e ON e.EmployeeID = t.EmployeeID
                    JOIN employmentinformation ei ON ei.EmployeeID = e.EmployeeID
                    WHERE ei.DepartmentID = ? AND UPPER(t.Status) = 'PENDING'
                    LIMIT $remaining";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $deptId);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $items[] = [
                    "type" => $cfg['label'],
                    "name" => trim($row['FirstName'] . ' ' . $row['LastName']),
                    "detail" => "Waiting approval",
                    "status" => "Pending"
                ];
            }
        } catch (Throwable $e) {}
    }

    // Punuin ang listahan kung kulang sa 4
    while (count($items) < $limit) {
        $items[] = ["type" => "—", "name" => "No pending items", "detail" => "All caught up", "status" => "—"];
    }
    return $items;
}

/**
 * Listahan ng mga susunod na holidays.
 */
function upcoming_holidays($conn, $limit = 3) {
    try {
        $sql = "SELECT HolidayDate, HolidayName FROM holidays 
                WHERE HolidayDate >= CURDATE() AND IsActive = 1 
                ORDER BY HolidayDate ASC LIMIT $limit";
        $res = $conn->query($sql);
        $out = [];
        while ($row = $res->fetch_assoc()) {
            $out[] = ["date" => $row["HolidayDate"], "name" => $row["HolidayName"]];
        }
        return $out;
    } catch (Throwable $e) { return []; }
}

// Output as JSON
echo json_encode([
    "kpis" => [
        // Ang timesheet_employee_summary ay walang status, 
        // kaya ang 'timesheet_period' ang dapat nating i-check (Departmental status)
        "pending_timesheets" => safe_count_department($conn, "timesheet_period", "DepartmentID", "Status", "FOR_REVIEW"),
        "attendance_today"   => safe_count_attendance_today($conn),
        "pending_leaves"     => safe_count_department($conn, "leave_requests", "EmployeeID", "Status", "PENDING"),
        "pending_claims"     => safe_count_department($conn, "reimbursement_claims", "EmployeeID", "Status", "PENDING"),
    ],
    "table_items" => pending_items($conn, 4),
    "upcoming"    => upcoming_holidays($conn, 3),
    "activity"    => [
        ["text" => "Dashboard refreshed for " . ($_SESSION['department_name'] ?? 'Department'), "time" => date("Y-m-d H:i")]
    ]
]);