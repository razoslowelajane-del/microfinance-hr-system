<?php
require_once __DIR__ . "/auth_officer.php";
require_once __DIR__ . "/../../../config/config.php";

header('Content-Type: application/json');

function respond($ok, $data = [], $code = 200) {
    http_response_code($code);
    echo json_encode(array_merge(['ok' => $ok], $data));
    exit;
}

if (!isset($conn) || !($conn instanceof mysqli)) {
    respond(false, ['message' => 'Database connection not available.'], 500);
}

$accountId  = $_SESSION['user_id'] ?? null;
$deptId     = $_SESSION['department_id'] ?? null;
$employeeId = $_SESSION['employee_id'] ?? null;

if (!$accountId || !$deptId || !$employeeId) {
    respond(false, ['message' => 'Unauthorized session.'], 401);
}

function statusLabel($status) {
    return match($status) {
        'PENDING'              => 'Pending',
        'APPROVED_BY_OFFICER'  => 'Sent to HR',
        'APPROVED_BY_HR'       => 'Approved by HR',
        'PAID'                 => 'Paid',
        'REJECTED'             => 'Rejected',
        'CANCELLED'            => 'Cancelled',
        default                => $status
    };
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

/* =========================================================
   GET CLAIMS
   ========================================================= */
if ($method === 'GET') {
    $statusFilter = $_GET['status'] ?? 'PENDING';

    $allowedStatuses = [
        'PENDING',
        'APPROVED_BY_OFFICER',
        'REJECTED',
        'ALL'
    ];

    if (!in_array($statusFilter, $allowedStatuses, true)) {
        $statusFilter = 'PENDING';
    }

    $sql = "
        SELECT
            rc.ClaimID,
            rc.EmployeeID,
            rc.PeriodID,
            rc.ClaimDate,
            rc.Category,
            rc.Amount,
            rc.Description,
            rc.ReceiptImage,
            rc.Status,
            rc.OfficerApprovedBy,
            rc.HRApprovedBy,
            rc.OfficerNotes,
            rc.HRNotes,
            rc.CreatedAt,

            e.EmployeeCode,
            CONCAT(
                e.FirstName,
                IF(e.MiddleName IS NOT NULL AND e.MiddleName <> '', CONCAT(' ', e.MiddleName), ''),
                ' ',
                e.LastName
            ) AS EmployeeName,

            d.DepartmentName,
            p.PositionName,

            tp.StartDate,
            tp.EndDate

        FROM reimbursement_claims rc
        INNER JOIN employee e
            ON e.EmployeeID = rc.EmployeeID
        INNER JOIN supervisor_employees se
            ON se.EmployeeID = rc.EmployeeID
           AND se.SupervisorAccountID = ?
           AND se.DepartmentID = ?
           AND se.IsActive = 1
        LEFT JOIN employmentinformation ei
            ON ei.EmployeeID = e.EmployeeID
        LEFT JOIN department d
            ON d.DepartmentID = ei.DepartmentID
        LEFT JOIN positions p
            ON p.PositionID = ei.PositionID
        LEFT JOIN timesheet_period tp
            ON tp.PeriodID = rc.PeriodID
    ";

    if ($statusFilter !== 'ALL') {
        $sql .= " WHERE rc.Status = ? ";
    }

    $sql .= " ORDER BY rc.CreatedAt DESC, rc.ClaimID DESC ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        respond(false, ['message' => 'Failed to prepare query.', 'error' => $conn->error], 500);
    }

    if ($statusFilter !== 'ALL') {
        $stmt->bind_param("iis", $accountId, $deptId, $statusFilter);
    } else {
        $stmt->bind_param("ii", $accountId, $deptId);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $claims = [];
    while ($row = $result->fetch_assoc()) {
        $claims[] = $row;
    }

    $stmt->close();

    respond(true, ['claims' => $claims]);
}

/* =========================================================
   POST ACTIONS
   ========================================================= */
if ($method === 'POST') {
    $action  = $_POST['action'] ?? '';
    $claimId = (int)($_POST['claim_id'] ?? 0);
    $notes   = trim($_POST['notes'] ?? '');

    if ($claimId <= 0) {
        respond(false, ['message' => 'Invalid claim ID.'], 422);
    }

    $checkSql = "
        SELECT rc.ClaimID, rc.Status
        FROM reimbursement_claims rc
        INNER JOIN supervisor_employees se
            ON se.EmployeeID = rc.EmployeeID
           AND se.SupervisorAccountID = ?
           AND se.DepartmentID = ?
           AND se.IsActive = 1
        WHERE rc.ClaimID = ?
        LIMIT 1
    ";

    $stmtCheck = $conn->prepare($checkSql);
    if (!$stmtCheck) {
        respond(false, ['message' => 'Failed to prepare validation query.'], 500);
    }

    $stmtCheck->bind_param("iii", $accountId, $deptId, $claimId);
    $stmtCheck->execute();
    $res = $stmtCheck->get_result();
    $claim = $res->fetch_assoc();
    $stmtCheck->close();

    if (!$claim) {
        respond(false, ['message' => 'Claim not found or not under your supervision.'], 404);
    }

    if ($action === 'approve') {
        if ($claim['Status'] !== 'PENDING') {
            respond(false, ['message' => 'Only pending claims can be approved by officer.'], 422);
        }

        $newStatus = 'APPROVED_BY_OFFICER';

        $sql = "
            UPDATE reimbursement_claims
            SET Status = ?,
                OfficerApprovedBy = ?,
                OfficerNotes = ?
            WHERE ClaimID = ?
            LIMIT 1
        ";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            respond(false, ['message' => 'Failed to prepare approval query.'], 500);
        }

        $stmt->bind_param("sisi", $newStatus, $accountId, $notes, $claimId);

        if (!$stmt->execute()) {
            $stmt->close();
            respond(false, ['message' => 'Failed to approve claim.'], 500);
        }

        $stmt->close();

        respond(true, [
            'message' => 'Claim approved and sent to HR.',
            'new_status' => $newStatus,
            'new_label' => statusLabel($newStatus)
        ]);
    }

    if ($action === 'reject') {
        if ($claim['Status'] !== 'PENDING') {
            respond(false, ['message' => 'Only pending claims can be rejected by officer.'], 422);
        }

        $newStatus = 'REJECTED';

        $sql = "
            UPDATE reimbursement_claims
            SET Status = ?,
                OfficerApprovedBy = ?,
                OfficerNotes = ?
            WHERE ClaimID = ?
            LIMIT 1
        ";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            respond(false, ['message' => 'Failed to prepare rejection query.'], 500);
        }

        $stmt->bind_param("sisi", $newStatus, $accountId, $notes, $claimId);

        if (!$stmt->execute()) {
            $stmt->close();
            respond(false, ['message' => 'Failed to reject claim.'], 500);
        }

        $stmt->close();

        respond(true, [
            'message' => 'Claim rejected by officer.',
            'new_status' => $newStatus,
            'new_label' => statusLabel($newStatus)
        ]);
    }

    respond(false, ['message' => 'Invalid action.'], 400);
}

respond(false, ['message' => 'Method not allowed.'], 405);