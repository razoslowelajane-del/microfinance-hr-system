<?php
require_once __DIR__ . "/auth_employee.php";
require_once __DIR__ . "/../../../config/config.php";

header('Content-Type: application/json');

function respond($ok, $message, $extra = [], $code = 200) {
    http_response_code($code);
    echo json_encode(array_merge([
        'ok' => $ok,
        'message' => $message
    ], $extra));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Invalid request method.', [], 405);
}

$employeeId = $_SESSION['employee_id'] ?? $_SESSION['EmployeeID'] ?? null;
$accountId  = $_SESSION['account_id'] ?? $_SESSION['AccountID'] ?? $_SESSION['user_id'] ?? null;
$embeddingJson = $_POST['embedding'] ?? null;

if (!$employeeId || !$accountId) {
    respond(false, 'Session not found.', [], 401);
}

if (!$embeddingJson) {
    respond(false, 'Face embedding is required.', [], 422);
}

$embedding = json_decode($embeddingJson, true);

if (!is_array($embedding) || count($embedding) !== 128) {
    respond(false, 'Invalid face embedding format.', [], 422);
}

$cleanEmbedding = json_encode(array_map('floatval', $embedding));

$stmt = $conn->prepare("
    SELECT FaceProfileID
    FROM employee_face_profile
    WHERE EmployeeID = ?
    LIMIT 1
");
$stmt->bind_param("i", $employeeId);
$stmt->execute();
$existing = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($existing) {
    $stmt = $conn->prepare("
        UPDATE employee_face_profile
        SET Embedding = ?,
            Algorithm = 'face-api.js-128d',
            EnrolledAt = NOW(),
            EnrolledByAccountID = ?,
            IsActive = 1
        WHERE EmployeeID = ?
    ");
    $stmt->bind_param("sii", $cleanEmbedding, $accountId, $employeeId);
} else {
    $stmt = $conn->prepare("
        INSERT INTO employee_face_profile
        (EmployeeID, Embedding, Algorithm, EnrolledAt, EnrolledByAccountID, IsActive)
        VALUES (?, ?, 'face-api.js-128d', NOW(), ?, 1)
    ");
    $stmt->bind_param("isi", $employeeId, $cleanEmbedding, $accountId);
}

if (!$stmt->execute()) {
    respond(false, 'Failed to save face profile: ' . $stmt->error, [], 500);
}

$stmt->close();

respond(true, 'Face profile saved successfully.');