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
    if ($action === 'fetch_employees') {
        // Fetch employees with their position, department, and salary grade
        $sql = "SELECT 
                    e.EmployeeID, 
                    e.EmployeeCode,
                    e.FirstName, 
                    e.LastName, 
                    ei.EmploymentStatus, 
                    d.DepartmentName, 
                    p.PositionName,
                    sg.GradeLevel
                FROM employee e
                LEFT JOIN employmentinformation ei ON e.EmployeeID = ei.EmployeeID
                LEFT JOIN department d ON ei.DepartmentID = d.DepartmentID
                LEFT JOIN positions p ON ei.PositionID = p.PositionID
                LEFT JOIN salary_grades sg ON p.SalaryGradeID = sg.SalaryGradeID
                ORDER BY e.LastName ASC";
        
        $result = $conn->query($sql);
        
        $employees = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $employees[] = $row;
            }
        }
        
        echo json_encode(['success' => true, 'data' => $employees]);
        exit;
    } elseif ($action === 'get_employee_details') {
        $employeeId = $_GET['id'] ?? 0;
        
        if (!$employeeId) {
            echo json_encode(['success' => false, 'message' => 'Invalid Employee ID']);
            exit;
        }

        // Fetch all details
        $sql = "SELECT 
                    e.*,
                    e.EmployeeCode,
                    ei.*,
                    d.DepartmentName,
                    p.PositionName,
                    sg.GradeLevel, sg.MinSalary, sg.MaxSalary,
                    bd.BankName, bd.AccountNumber as BankAccountNumber, bd.AccountType,
                    tb.TINNumber, tb.SSSNumber, tb.PhilHealthNumber, tb.PagIBIGNumber, tb.TaxStatus,
                    ec.ContactName, ec.Relationship, ec.PhoneNumber as EmergencyPhone
                FROM employee e
                LEFT JOIN employmentinformation ei ON e.EmployeeID = ei.EmployeeID
                LEFT JOIN department d ON ei.DepartmentID = d.DepartmentID
                LEFT JOIN positions p ON ei.PositionID = p.PositionID
                LEFT JOIN salary_grades sg ON p.SalaryGradeID = sg.SalaryGradeID
                LEFT JOIN bankdetails bd ON e.EmployeeID = bd.EmployeeID
                LEFT JOIN taxbenefits tb ON e.EmployeeID = tb.EmployeeID
                LEFT JOIN emergency_contacts ec ON e.EmployeeID = ec.EmployeeID AND ec.IsPrimary = 1
                WHERE e.EmployeeID = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $employeeId);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        
        if ($data) {
            echo json_encode(['success' => true, 'data' => $data]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Employee not found']);
        }
        exit;
    } elseif ($action === 'update_employee') {
        // Collect POST data
        $employeeId = $_POST['EmployeeID'] ?? 0;
        $firstName = $_POST['FirstName'] ?? '';
        $lastName = $_POST['LastName'] ?? '';
        $middleName = $_POST['MiddleName'] ?? '';
        $dob = $_POST['DateOfBirth'] ?? null;
        $gender = $_POST['Gender'] ?? '';
        $phone = $_POST['PhoneNumber'] ?? '';
        $personalEmail = $_POST['PersonalEmail'] ?? '';
        $address = $_POST['PermanentAddress'] ?? '';
        
        $hiringDate = $_POST['HiringDate'] ?? null;
        $workEmail = $_POST['WorkEmail'] ?? '';
        $empStatus = $_POST['EmploymentStatus'] ?? '';
        
        $tin = $_POST['TINNumber'] ?? '';
        $sss = $_POST['SSSNumber'] ?? '';
        $philhealth = $_POST['PhilHealthNumber'] ?? '';
        $pagibig = $_POST['PagIBIGNumber'] ?? '';

        $conn->begin_transaction();

        try {
            // Update Employee Table
            $sqlEmp = "UPDATE employee SET FirstName=?, LastName=?, MiddleName=?, DateOfBirth=?, Gender=?, PhoneNumber=?, PersonalEmail=?, PermanentAddress=? WHERE EmployeeID=?";
            $stmtEmp = $conn->prepare($sqlEmp);
            $stmtEmp->bind_param("ssssssssi", $firstName, $lastName, $middleName, $dob, $gender, $phone, $personalEmail, $address, $employeeId);
            $stmtEmp->execute();

            // Update Employment Information
            $sqlInfo = "UPDATE employmentinformation SET HiringDate=?, WorkEmail=?, EmploymentStatus=? WHERE EmployeeID=?";
            $stmtInfo = $conn->prepare($sqlInfo);
            $stmtInfo->bind_param("sssi", $hiringDate, $workEmail, $empStatus, $employeeId);
            $stmtInfo->execute();

            // Update Tax Benefits (Check if exists first, or use INSERT ON DUPLICATE if supported/configured, assuming simple UPDATE for now or checking)
            // Simplified: Try UPDATE, if 0 affected and rows didn't exist, might need INSERT. 
            // For now assuming rows exist or strict update.
            $sqlTax = "UPDATE taxbenefits SET TINNumber=?, SSSNumber=?, PhilHealthNumber=?, PagIBIGNumber=? WHERE EmployeeID=?";
            $stmtTax = $conn->prepare($sqlTax);
            $stmtTax->bind_param("ssssi", $tin, $sss, $philhealth, $pagibig, $employeeId);
            $stmtTax->execute();

            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Employee updated successfully']);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
        exit;
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
