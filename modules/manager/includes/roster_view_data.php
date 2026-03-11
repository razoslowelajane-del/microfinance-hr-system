<?php
require_once __DIR__ . "/auth_hr_manager.php";
require_once __DIR__ . "/../../../config/config.php";

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function respond($success, $message = '', $data = [], $code = 200) {
    http_response_code($code);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data'    => $data
    ]);
    exit;
}

try {
    $search     = trim($_GET['search'] ?? '');
    $department = trim($_GET['department'] ?? '');
    $status     = trim($_GET['status'] ?? 'FOR_REVIEW');
    $sortBy     = trim($_GET['sort'] ?? 'latest');

    /*
    |--------------------------------------------------------------------------
    | Summary
    |--------------------------------------------------------------------------
    */
    $summary = [
        'pending'   => 0,
        'returned'  => 0,
        'published' => 0,
        'total'     => 0,
    ];

    $summarySql = "
        SELECT
            SUM(CASE WHEN Status = 'FOR_REVIEW' THEN 1 ELSE 0 END) AS pending_count,
            SUM(CASE WHEN Status = 'RETURNED' THEN 1 ELSE 0 END) AS returned_count,
            SUM(CASE WHEN Status = 'PUBLISHED' THEN 1 ELSE 0 END) AS published_count,
            COUNT(*) AS total_count
        FROM weekly_roster
    ";
    $summaryRes = $conn->query($summarySql);
    if ($summaryRes && $row = $summaryRes->fetch_assoc()) {
        $summary['pending']   = (int)($row['pending_count'] ?? 0);
        $summary['returned']  = (int)($row['returned_count'] ?? 0);
        $summary['published'] = (int)($row['published_count'] ?? 0);
        $summary['total']     = (int)($row['total_count'] ?? 0);
    }

    /*
    |--------------------------------------------------------------------------
    | Departments
    |--------------------------------------------------------------------------
    */
    $departments = [];
    $deptSql = "SELECT DepartmentID, DepartmentName FROM department ORDER BY DepartmentName ASC";
    $deptRes = $conn->query($deptSql);
    if ($deptRes) {
        while ($d = $deptRes->fetch_assoc()) {
            $departments[] = [
                'DepartmentID'   => (int)$d['DepartmentID'],
                'DepartmentName' => $d['DepartmentName']
            ];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Sort Mapping
    |--------------------------------------------------------------------------
    */
    $orderBy = "wr.CreatedAt DESC, wr.RosterID DESC";
    switch ($sortBy) {
        case 'oldest':
            $orderBy = "wr.CreatedAt ASC, wr.RosterID ASC";
            break;
        case 'period_asc':
            $orderBy = "wr.WeekStart ASC, wr.RosterID ASC";
            break;
        case 'period_desc':
            $orderBy = "wr.WeekStart DESC, wr.RosterID DESC";
            break;
        case 'department_asc':
            $orderBy = "d.DepartmentName ASC, wr.WeekStart DESC";
            break;
        case 'department_desc':
            $orderBy = "d.DepartmentName DESC, wr.WeekStart DESC";
            break;
        case 'latest':
        default:
            $orderBy = "wr.CreatedAt DESC, wr.RosterID DESC";
            break;
    }

    /*
    |--------------------------------------------------------------------------
    | Main Query
    |--------------------------------------------------------------------------
    */
    $sql = "
        SELECT
            wr.RosterID,
            wr.DepartmentID,
            wr.WeekStart,
            wr.WeekEnd,
            wr.Status,
            wr.CreatedByAccountID,
            wr.CreatedAt,
            wr.UpdatedAt,
            wr.ReviewedAt,
            wr.ReviewNotes,

            d.DepartmentName,

            ua.Username,

            e.EmployeeID AS OfficerEmployeeID,
            e.EmployeeCode,
            CONCAT(
                e.FirstName,
                ' ',
                IFNULL(CONCAT(LEFT(e.MiddleName, 1), '. '), ''),
                e.LastName
            ) AS OfficerFullName,

            COUNT(DISTINCT ra.EmployeeID) AS TotalEmployees,
            COUNT(ra.AssignmentID) AS TotalAssignments
        FROM weekly_roster wr
        INNER JOIN department d
            ON d.DepartmentID = wr.DepartmentID
        INNER JOIN useraccounts ua
            ON ua.AccountID = wr.CreatedByAccountID
        LEFT JOIN employee e
            ON e.EmployeeID = ua.EmployeeID
        LEFT JOIN roster_assignment ra
            ON ra.RosterID = wr.RosterID
        WHERE 1=1
    ";

    $params = [];
    $types  = '';

    if ($status !== '') {
        $sql .= " AND wr.Status = ? ";
        $params[] = $status;
        $types .= 's';
    }

    if ($department !== '') {
        $sql .= " AND wr.DepartmentID = ? ";
        $params[] = (int)$department;
        $types .= 'i';
    }

    if ($search !== '') {
        $like = "%{$search}%";
        $sql .= " AND (
            d.DepartmentName LIKE ?
            OR ua.Username LIKE ?
            OR CONCAT(e.FirstName, ' ', e.LastName) LIKE ?
            OR e.EmployeeCode LIKE ?
            OR CAST(wr.RosterID AS CHAR) LIKE ?
        ) ";
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $types .= 'sssss';
    }

    $sql .= "
        GROUP BY
            wr.RosterID,
            wr.DepartmentID,
            wr.WeekStart,
            wr.WeekEnd,
            wr.Status,
            wr.CreatedByAccountID,
            wr.CreatedAt,
            wr.UpdatedAt,
            wr.ReviewedAt,
            wr.ReviewNotes,
            d.DepartmentName,
            ua.Username,
            e.EmployeeID,
            e.EmployeeCode,
            e.FirstName,
            e.MiddleName,
            e.LastName
        ORDER BY {$orderBy}
    ";

    $rows = [];
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        respond(false, 'Failed to prepare roster query.', [], 500);
    }

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $rows[] = [
            'RosterID'         => (int)$row['RosterID'],
            'DepartmentID'     => (int)$row['DepartmentID'],
            'DepartmentName'   => $row['DepartmentName'],
            'WeekStart'        => $row['WeekStart'],
            'WeekEnd'          => $row['WeekEnd'],
            'Status'           => $row['Status'],
            'CreatedByAccountID'=> (int)$row['CreatedByAccountID'],
            'CreatedAt'        => $row['CreatedAt'],
            'UpdatedAt'        => $row['UpdatedAt'],
            'ReviewedAt'       => $row['ReviewedAt'],
            'ReviewNotes'      => $row['ReviewNotes'] ?? '',
            'Username'         => $row['Username'],
            'OfficerEmployeeID'=> $row['OfficerEmployeeID'] ? (int)$row['OfficerEmployeeID'] : null,
            'EmployeeCode'     => $row['EmployeeCode'] ?? '',
            'OfficerFullName'  => $row['OfficerFullName'] ?: $row['Username'],
            'TotalEmployees'   => (int)$row['TotalEmployees'],
            'TotalAssignments' => (int)$row['TotalAssignments']
        ];
    }

    $stmt->close();

    respond(true, 'Roster queue loaded successfully.', [
        'summary'     => $summary,
        'departments' => $departments,
        'rosters'     => $rows
    ]);

} catch (Throwable $e) {
    respond(false, 'Server error: ' . $e->getMessage(), [], 500);
}