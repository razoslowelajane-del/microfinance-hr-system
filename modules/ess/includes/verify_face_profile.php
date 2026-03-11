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

function euclideanDistance(array $a, array $b): float {
    $sum = 0.0;
    $count = min(count($a), count($b));

    for ($i = 0; $i < $count; $i++) {
        $diff = ((float)$a[$i]) - ((float)$b[$i]);
        $sum += $diff * $diff;
    }

    return sqrt($sum);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Invalid request method.', [], 405);
}

$employeeId = $_SESSION['employee_id'] ?? $_SESSION['EmployeeID'] ?? null;
$descriptorJson = $_POST['descriptor'] ?? null;

if (!$employeeId) {
    respond(false, 'Session not found.', [], 401);
}

if (!$descriptorJson) {
    respond(false, 'Live descriptor is required.', [], 422);
}

$liveDescriptor = json_decode($descriptorJson, true);

if (!is_array($liveDescriptor) || count($liveDescriptor) !== 128) {
    respond(false, 'Invalid live descriptor format.', [], 422);
}

$stmt = $conn->prepare("
    SELECT Embedding
    FROM employee_face_profile
    WHERE EmployeeID = ? AND IsActive = 1
    LIMIT 1
");
$stmt->bind_param("i", $employeeId);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
    respond(false, 'No enrolled face profile found.', [], 404);
}

$storedDescriptor = json_decode($row['Embedding'], true);

if (!is_array($storedDescriptor) || count($storedDescriptor) !== 128) {
    respond(false, 'Stored face profile is invalid.', [], 500);
}

$distance = euclideanDistance($liveDescriptor, $storedDescriptor);
$threshold = 0.50;

if ($distance > $threshold) {
    respond(false, 'Face does not match enrolled profile.', [
        'distance' => round($distance, 4),
        'threshold' => $threshold,
        'face_status' => 'NO_MATCH'
    ], 403);
}

respond(true, 'Face matched successfully.', [
    'distance' => round($distance, 4),
    'threshold' => $threshold,
    'face_status' => 'MATCH'
]);