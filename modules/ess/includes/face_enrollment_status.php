<?php
require_once __DIR__ . "/../../../config/config.php";
require_once __DIR__ . "/auth_employee.php";

header('Content-Type: application/json; charset=utf-8');

function respond(bool $ok, array $data = [], int $code = 200): void
{
    http_response_code($code);
    echo json_encode(array_merge(['ok' => $ok], $data));
    exit;
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($conn) || !($conn instanceof mysqli)) {
    respond(false, ['message' => 'Database connection is not available.'], 500);
}

$employeeId = $_SESSION['employee_id'] ?? $_SESSION['EmployeeID'] ?? null;
$accountId  = $_SESSION['account_id'] ?? $_SESSION['user_id'] ?? $_SESSION['AccountID'] ?? null;

if (!$employeeId && $accountId) {
    $stmt = $conn->prepare("SELECT EmployeeID FROM useraccounts WHERE AccountID = ? LIMIT 1");
    $stmt->bind_param("i", $accountId);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();

    if ($row && !empty($row['EmployeeID'])) {
        $employeeId = (int)$row['EmployeeID'];
    }
}

if (!$employeeId) {
    respond(false, ['message' => 'Employee session not found.'], 401);
}

$stmt = $conn->prepare("
    SELECT FaceProfileID, EmployeeID, Algorithm, EnrolledAt, IsActive, UpdatedAt
    FROM employee_face_profile
    WHERE EmployeeID = ?
    LIMIT 1
");
$stmt->bind_param("i", $employeeId);
$stmt->execute();
$res = $stmt->get_result();
$profile = $res ? $res->fetch_assoc() : null;
$stmt->close();

respond(true, [
    'has_profile' => $profile ? true : false,
    'profile' => $profile
]);