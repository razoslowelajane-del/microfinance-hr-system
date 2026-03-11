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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, ['message' => 'Invalid request method.'], 405);
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

$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

if (!is_array($data)) {
    respond(false, ['message' => 'Invalid JSON payload.'], 422);
}

$descriptor = $data['descriptor'] ?? null;
$algorithm  = trim((string)($data['algorithm'] ?? 'face-api.js-128d'));

if (!is_array($descriptor)) {
    respond(false, ['message' => 'Descriptor payload is required.'], 422);
}

if (count($descriptor) !== 128) {
    respond(false, ['message' => 'Descriptor must contain exactly 128 values.'], 422);
}

$cleanDescriptor = [];
foreach ($descriptor as $value) {
    if (!is_numeric($value)) {
        respond(false, ['message' => 'Descriptor contains non-numeric value.'], 422);
    }
    $cleanDescriptor[] = (float)$value;
}

$embeddingJson = json_encode($cleanDescriptor);
if ($embeddingJson === false) {
    respond(false, ['message' => 'Failed to encode descriptor.'], 500);
}

$conn->begin_transaction();

try {
    $checkStmt = $conn->prepare("
        SELECT FaceProfileID
        FROM employee_face_profile
        WHERE EmployeeID = ?
        LIMIT 1
    ");
    $checkStmt->bind_param("i", $employeeId);
    $checkStmt->execute();
    $checkRes = $checkStmt->get_result();
    $existing = $checkRes ? $checkRes->fetch_assoc() : null;
    $checkStmt->close();

    if ($existing) {
        $faceProfileId = (int)$existing['FaceProfileID'];

        $updateStmt = $conn->prepare("
            UPDATE employee_face_profile
            SET Embedding = ?,
                Algorithm = ?,
                EnrolledAt = NOW(),
                EnrolledByAccountID = ?,
                IsActive = 1
            WHERE FaceProfileID = ?
        ");
        $updateStmt->bind_param("ssii", $embeddingJson, $algorithm, $accountId, $faceProfileId);
        $updateStmt->execute();
        $updateStmt->close();

        $action = 'updated';
    } else {
        $insertStmt = $conn->prepare("
            INSERT INTO employee_face_profile
                (EmployeeID, Embedding, Algorithm, EnrolledAt, EnrolledByAccountID, IsActive)
            VALUES
                (?, ?, ?, NOW(), ?, 1)
        ");
        $insertStmt->bind_param("issi", $employeeId, $embeddingJson, $algorithm, $accountId);
        $insertStmt->execute();
        $insertStmt->close();

        $action = 'created';
    }

    $conn->commit();

    respond(true, [
        'message' => $action === 'created'
            ? 'Face enrollment saved successfully.'
            : 'Face enrollment updated successfully.',
        'action' => $action
    ]);
} catch (Throwable $e) {
    $conn->rollback();
    respond(false, [
        'message' => 'Failed to save face enrollment.',
        'error' => $e->getMessage()
    ], 500);
}