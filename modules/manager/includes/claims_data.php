<?php
require_once __DIR__ . "/auth_hr_manager.php";
require_once __DIR__ . "/../../../config/config.php";

header('Content-Type: application/json');

function respond($ok, $data = [], $code = 200) {
    http_response_code($code);
    echo json_encode(array_merge(['ok' => $ok], $data));
    exit;
}

$accountId = $_SESSION['account_id'] ?? $_SESSION['user_id'] ?? null;

if (!$accountId) {
    respond(false, ['message' => 'Unauthorized access. Missing HR manager account.'], 401);
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
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

                CONCAT_WS(' ', e.FirstName, e.MiddleName, e.LastName) AS EmployeeName,
                e.EmployeeCode,

                d.DepartmentName,
                p.PositionName,

                officerUA.Username AS OfficerApprovedByName,
                hrUA.Username AS HRApprovedByName,

                tp.StartDate AS PeriodStartDate,
                tp.EndDate AS PeriodEndDate
            FROM reimbursement_claims rc
            INNER JOIN employee e
                ON e.EmployeeID = rc.EmployeeID
            LEFT JOIN employmentinformation ei
                ON ei.EmployeeID = rc.EmployeeID
            LEFT JOIN department d
                ON d.DepartmentID = ei.DepartmentID
            LEFT JOIN positions p
                ON p.PositionID = ei.PositionID
            LEFT JOIN useraccounts officerUA
                ON officerUA.AccountID = rc.OfficerApprovedBy
            LEFT JOIN useraccounts hrUA
                ON hrUA.AccountID = rc.HRApprovedBy
            LEFT JOIN timesheet_period tp
                ON tp.PeriodID = rc.PeriodID
            ORDER BY rc.CreatedAt DESC, rc.ClaimID DESC
        ";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            respond(false, ['message' => 'SQL prepare failed: ' . $conn->error], 500);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        $rows = [];
        $summary = [
            'for_approval' => 0,
            'approved'     => 0,
            'rejected'     => 0,
            'paid'         => 0,
            'total'        => 0,
        ];

        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
            $summary['total']++;

            switch ($row['Status']) {
                case 'APPROVED_BY_OFFICER':
                    $summary['for_approval']++;
                    break;
                case 'APPROVED_BY_HR':
                    $summary['approved']++;
                    break;
                case 'REJECTED':
                    $summary['rejected']++;
                    break;
                case 'PAID':
                    $summary['paid']++;
                    break;
            }
        }

        $stmt->close();

        respond(true, [
            'rows' => $rows,
            'summary' => $summary
        ]);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $claimId  = isset($_POST['claim_id']) ? (int) $_POST['claim_id'] : 0;
        $action   = trim($_POST['action'] ?? '');
        $remarks  = trim($_POST['remarks'] ?? '');

        if ($claimId <= 0) {
            respond(false, ['message' => 'Invalid claim ID.'], 422);
        }

        if (!in_array($action, ['approve', 'reject'], true)) {
            respond(false, ['message' => 'Invalid action.'], 422);
        }

        $conn->begin_transaction();

        $checkSql = "
            SELECT
                rc.ClaimID,
                rc.Status,
                rc.EmployeeID,
                rc.Amount,
                rc.Category
            FROM reimbursement_claims rc
            WHERE rc.ClaimID = ?
            LIMIT 1
        ";

        $stmt = $conn->prepare($checkSql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("i", $claimId);
        $stmt->execute();
        $result = $stmt->get_result();
        $claim = $result->fetch_assoc();
        $stmt->close();

        if (!$claim) {
            throw new Exception("Claim record not found.");
        }

        if ($claim['Status'] !== 'APPROVED_BY_OFFICER') {
            throw new Exception("Only officer-approved claims can be updated by HR Manager.");
        }

        $newStatus = $action === 'approve' ? 'APPROVED_BY_HR' : 'REJECTED';

        $updateSql = "
            UPDATE reimbursement_claims
            SET
                Status = ?,
                HRApprovedBy = ?,
                HRNotes = ?,
                CreatedAt = CreatedAt
            WHERE ClaimID = ?
              AND Status = 'APPROVED_BY_OFFICER'
            LIMIT 1
        ";

        $stmt = $conn->prepare($updateSql);
        if (!$stmt) {
            throw new Exception("Update prepare failed: " . $conn->error);
        }

        $stmt->bind_param("sisi", $newStatus, $accountId, $remarks, $claimId);
        $stmt->execute();

        if ($stmt->affected_rows <= 0) {
            $stmt->close();
            throw new Exception("No rows were updated. The claim may already be processed.");
        }

        $stmt->close();
        $conn->commit();

        respond(true, [
            'message' => $action === 'approve'
                ? 'Claim has been approved by HR Manager.'
                : 'Claim has been rejected by HR Manager.',
            'new_status' => $newStatus,
            'claim_id' => $claimId
        ]);
    }

    respond(false, ['message' => 'Method not allowed.'], 405);

} catch (Throwable $e) {
    if (isset($conn) && method_exists($conn, 'rollback')) {
        $conn->rollback();
    }
    respond(false, ['message' => $e->getMessage()], 500);
}