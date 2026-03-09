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
                lr.LeaveRequestID,
                lr.EmployeeID,
                CONCAT_WS(' ', e.FirstName, e.MiddleName, e.LastName) AS EmployeeName,
                e.EmployeeCode,
                d.DepartmentName,
                p.PositionName,
                lt.LeaveTypeID,
                lt.LeaveName,
                lt.IsPaid,
                lr.StartDate,
                lr.EndDate,
                lr.TotalDays,
                lr.Reason,
                lr.Status,
                lr.CreatedAt,
                lr.UpdatedAt,
                lr.AttachmentPath,
                lr.OfficerApprovedBy,
                lr.HRApprovedBy,
                lr.OfficerNotes,
                lr.HRNotes,
                COALESCE(elb.TotalCredits, 0) AS TotalCredits,
                COALESCE(elb.UsedCredits, 0) AS UsedCredits,
                COALESCE(elb.RemainingCredits, 0) AS RemainingCredits,
                officerUA.Username AS OfficerApprovedByName,
                hrUA.Username AS HRApprovedByName
            FROM leave_requests lr
            INNER JOIN employee e
                ON e.EmployeeID = lr.EmployeeID
            LEFT JOIN employmentinformation ei
                ON ei.EmployeeID = lr.EmployeeID
            LEFT JOIN department d
                ON d.DepartmentID = ei.DepartmentID
            LEFT JOIN positions p
                ON p.PositionID = ei.PositionID
            INNER JOIN leave_types lt
                ON lt.LeaveTypeID = lr.LeaveTypeID
            LEFT JOIN employee_leave_balances elb
                ON elb.EmployeeID = lr.EmployeeID
               AND elb.LeaveTypeID = lr.LeaveTypeID
               AND elb.Year = YEAR(lr.StartDate)
            LEFT JOIN useraccounts officerUA
                ON officerUA.AccountID = lr.OfficerApprovedBy
            LEFT JOIN useraccounts hrUA
                ON hrUA.AccountID = lr.HRApprovedBy
            ORDER BY lr.CreatedAt DESC
        ";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            respond(false, ['message' => 'SQL prepare failed: ' . $conn->error], 500);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        $rows = [];
        $summary = [
            'pending'   => 0,
            'sent'      => 0,
            'approved'  => 0,
            'rejected'  => 0,
            'cancelled' => 0,
            'total'     => 0,
        ];

        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
            $summary['total']++;

            switch ($row['Status']) {
                case 'PENDING':
                    $summary['pending']++;
                    break;
                case 'APPROVED_BY_OFFICER':
                    $summary['sent']++;
                    break;
                case 'APPROVED_BY_HR':
                    $summary['approved']++;
                    break;
                case 'REJECTED':
                    $summary['rejected']++;
                    break;
                case 'CANCELLED':
                    $summary['cancelled']++;
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
        $leaveRequestId = isset($_POST['leave_request_id']) ? (int) $_POST['leave_request_id'] : 0;
        $action         = trim($_POST['action'] ?? '');
        $remarks        = trim($_POST['remarks'] ?? '');

        if ($leaveRequestId <= 0) {
            respond(false, ['message' => 'Invalid leave request ID.'], 422);
        }

        if (!in_array($action, ['approve', 'reject'], true)) {
            respond(false, ['message' => 'Invalid action.'], 422);
        }

        $conn->begin_transaction();

        $sql = "
            SELECT
                lr.LeaveRequestID,
                lr.EmployeeID,
                lr.LeaveTypeID,
                lr.TotalDays,
                lr.StartDate,
                lr.Status,
                lt.LeaveName,
                lt.IsPaid,
                COALESCE(elb.TotalCredits, 0) AS TotalCredits,
                COALESCE(elb.UsedCredits, 0) AS UsedCredits,
                COALESCE(elb.RemainingCredits, 0) AS RemainingCredits
            FROM leave_requests lr
            INNER JOIN leave_types lt
                ON lt.LeaveTypeID = lr.LeaveTypeID
            LEFT JOIN employee_leave_balances elb
                ON elb.EmployeeID = lr.EmployeeID
               AND elb.LeaveTypeID = lr.LeaveTypeID
               AND elb.Year = YEAR(lr.StartDate)
            WHERE lr.LeaveRequestID = ?
            LIMIT 1
        ";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("i", $leaveRequestId);
        $stmt->execute();
        $result = $stmt->get_result();
        $leave = $result->fetch_assoc();
        $stmt->close();

        if (!$leave) {
            throw new Exception("Leave request not found.");
        }

        if ($leave['Status'] !== 'APPROVED_BY_OFFICER') {
            throw new Exception("Only officer-approved leave requests can be updated by HR.");
        }

        if ($action === 'approve' && (int)$leave['IsPaid'] === 1) {
            $remaining = (float)$leave['RemainingCredits'];
            $requested = (float)$leave['TotalDays'];

            if ($remaining < $requested) {
                throw new Exception(
                    "Insufficient leave credits. Remaining: " .
                    number_format($remaining, 2) .
                    ", Requested: " .
                    number_format($requested, 2)
                );
            }
        }

        $newStatus = $action === 'approve' ? 'APPROVED_BY_HR' : 'REJECTED';

        $updateSql = "
            UPDATE leave_requests
            SET
                Status = ?,
                HRApprovedBy = ?,
                HRNotes = ?,
                UpdatedAt = CURRENT_TIMESTAMP
            WHERE LeaveRequestID = ?
              AND Status = 'APPROVED_BY_OFFICER'
            LIMIT 1
        ";

        $stmt = $conn->prepare($updateSql);
        if (!$stmt) {
            throw new Exception("Update prepare failed: " . $conn->error);
        }

        $stmt->bind_param("sisi", $newStatus, $accountId, $remarks, $leaveRequestId);
        $stmt->execute();

        if ($stmt->affected_rows <= 0) {
            $stmt->close();
            throw new Exception("No rows were updated. The request may already be processed.");
        }

        $stmt->close();

        if ($action === 'approve' && (int)$leave['IsPaid'] === 1) {
            $employeeId  = (int)$leave['EmployeeID'];
            $leaveTypeId = (int)$leave['LeaveTypeID'];
            $year        = (int)date('Y', strtotime($leave['StartDate']));
            $requested   = (float)$leave['TotalDays'];

            $balanceCheckSql = "
                SELECT BalanceID, UsedCredits, TotalCredits, RemainingCredits
                FROM employee_leave_balances
                WHERE EmployeeID = ?
                  AND LeaveTypeID = ?
                  AND Year = ?
                LIMIT 1
                FOR UPDATE
            ";

            $stmt = $conn->prepare($balanceCheckSql);
            if (!$stmt) {
                throw new Exception("Balance check prepare failed: " . $conn->error);
            }

            $stmt->bind_param("iii", $employeeId, $leaveTypeId, $year);
            $stmt->execute();
            $balanceResult = $stmt->get_result();
            $balance = $balanceResult->fetch_assoc();
            $stmt->close();

            if (!$balance) {
                throw new Exception("Leave balance record not found for this employee.");
            }

            $remaining = (float)$balance['RemainingCredits'];
            if ($remaining < $requested) {
                throw new Exception(
                    "Insufficient leave credits during final approval. Remaining: " .
                    number_format($remaining, 2) .
                    ", Requested: " .
                    number_format($requested, 2)
                );
            }

            $updateBalanceSql = "
                UPDATE employee_leave_balances
                SET UsedCredits = UsedCredits + ?
                WHERE BalanceID = ?
                LIMIT 1
            ";

            $stmt = $conn->prepare($updateBalanceSql);
            if (!$stmt) {
                throw new Exception("Balance update prepare failed: " . $conn->error);
            }

            $balanceId = (int)$balance['BalanceID'];
            $stmt->bind_param("di", $requested, $balanceId);
            $stmt->execute();

            if ($stmt->affected_rows <= 0) {
                $stmt->close();
                throw new Exception("Failed to update leave balance.");
            }

            $stmt->close();
        }

        $conn->commit();

        respond(true, [
            'message' => $action === 'approve'
                ? 'Leave request fully approved by HR.'
                : 'Leave request rejected by HR.',
            'new_status' => $newStatus,
            'leave_request_id' => $leaveRequestId
        ]);
    }

    respond(false, ['message' => 'Method not allowed.'], 405);

} catch (Throwable $e) {
    if (isset($conn) && method_exists($conn, 'rollback')) {
        $conn->rollback();
    }
    respond(false, ['message' => $e->getMessage()], 500);
}