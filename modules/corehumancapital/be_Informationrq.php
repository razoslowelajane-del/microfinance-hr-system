<?php
require_once '../../config/config.php';

header('Content-Type: application/json');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check authentication
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    if ($action === 'fetch_pending_requests') {
        $sql = "SELECT 
                    r.RequestID, r.EmployeeID, r.RequestType, r.RequestDate, r.Status, r.RequestData,
                    e.FirstName, e.LastName, d.DepartmentName
                FROM employee_update_requests r
                JOIN employee e ON r.EmployeeID = e.EmployeeID
                LEFT JOIN employmentinformation ei ON e.EmployeeID = ei.EmployeeID
                LEFT JOIN department d ON ei.DepartmentID = d.DepartmentID
                ORDER BY r.RequestDate DESC";
        
        $result = $conn->query($sql);
        
        $requests = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $requests[] = $row;
            }
        }
        
        echo json_encode(['success' => true, 'data' => $requests]);
        exit;

    } elseif ($action === 'fetch_stats') {
        $stats = ['Pending' => 0, 'Approved' => 0, 'Rejected' => 0];
        $result = $conn->query("SELECT Status, COUNT(*) as cnt FROM employee_update_requests GROUP BY Status");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                if (isset($stats[$row['Status']])) $stats[$row['Status']] = (int)$row['cnt'];
            }
        }
        echo json_encode(['success' => true, 'data' => $stats]);
        exit;

    } elseif ($action === 'approve_request') {
        $input = json_decode(file_get_contents('php://input'), true);
        $requestId = $input['request_id'] ?? null;
        $reviewerId = $_SESSION['user_id']; 

        if (!$requestId) {
            echo json_encode(['success' => false, 'message' => 'Request ID required']);
            exit;
        }

        $conn->begin_transaction();

        try {
            // 1. Fetch Request Data
            $stmt = $conn->prepare("SELECT EmployeeID, RequestData FROM employee_update_requests WHERE RequestID = ?");
            $stmt->bind_param("i", $requestId);
            $stmt->execute();
            $res = $stmt->get_result();
            $request = $res->fetch_assoc();

            if (!$request) {
                throw new Exception("Request not found");
            }

            $employeeId = $request['EmployeeID'];
            $changes = json_decode($request['RequestData'], true);

            $bankFields = [];
            $taxFields  = [];

            foreach ($changes as $key => $value) {
                if (in_array($key, ['BankName', 'BankAccountNumber', 'AccountType'])) {
                    $dbKey = ($key === 'BankAccountNumber') ? 'AccountNumber' : $key;
                    $bankFields[$dbKey] = $value;
                } elseif (in_array($key, ['TINNumber', 'SSSNumber', 'PhilHealthNumber', 'PagIBIGNumber', 'TaxStatus'])) {
                    $taxFields[$key] = $value;
                } elseif (in_array($key, ['FirstName', 'LastName', 'MiddleName', 'DateOfBirth', 'Gender', 'PhoneNumber', 'PersonalEmail', 'PermanentAddress', 'CivilStatus'])) {
                    $stmtUpdate = $conn->prepare("UPDATE employee SET $key = ? WHERE EmployeeID = ?");
                    $stmtUpdate->bind_param("si", $value, $employeeId);
                    $stmtUpdate->execute();
                }
            }
            
            // Upsert bankdetails
            if (!empty($bankFields)) {
                $cols         = array_keys($bankFields);
                $vals         = array_values($bankFields);
                $types        = str_repeat('s', count($vals));
                $setCols      = implode(', ', array_map(fn($c) => "`$c` = ?", $cols));
                $insertCols   = '`EmployeeID`, `' . implode('`, `', $cols) . '`';
                $placeholders = '?, ' . implode(', ', array_fill(0, count($cols), '?'));
                $sql  = "INSERT INTO bankdetails ($insertCols) VALUES ($placeholders) ON DUPLICATE KEY UPDATE $setCols";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('i' . $types . $types, ...array_merge([$employeeId], $vals, $vals));
                $stmt->execute();
            }

            // Upsert taxbenefits
            if (!empty($taxFields)) {
                $cols         = array_keys($taxFields);
                $vals         = array_values($taxFields);
                $types        = str_repeat('s', count($vals));
                $setCols      = implode(', ', array_map(fn($c) => "`$c` = ?", $cols));
                $insertCols   = '`EmployeeID`, `' . implode('`, `', $cols) . '`';
                $placeholders = '?, ' . implode(', ', array_fill(0, count($cols), '?'));
                $sql  = "INSERT INTO taxbenefits ($insertCols) VALUES ($placeholders) ON DUPLICATE KEY UPDATE $setCols";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('i' . $types . $types, ...array_merge([$employeeId], $vals, $vals));
                $stmt->execute();
            }

            // Update Request Status
            $stmt = $conn->prepare("UPDATE employee_update_requests SET Status = 'Approved', ReviewedBy = ?, ReviewDate = NOW() WHERE RequestID = ?");
            $stmt->bind_param("ii", $reviewerId, $requestId);
            $stmt->execute();

            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Request approved and changes applied.']);

        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;

    } elseif ($action === 'reject_request') {
        $input = json_decode(file_get_contents('php://input'), true);
        $requestId = $input['request_id'] ?? null;
        $reviewerId = $_SESSION['user_id'];

        if (!$requestId) {
            echo json_encode(['success' => false, 'message' => 'Request ID required']);
            exit;
        }

        $stmt = $conn->prepare("UPDATE employee_update_requests SET Status = 'Rejected', ReviewedBy = ?, ReviewDate = NOW() WHERE RequestID = ?");
        $stmt->bind_param("ii", $reviewerId, $requestId);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Request rejected.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error']);
        }
        exit;
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
