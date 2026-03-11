<?php
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

if (!isset($conn) || !($conn instanceof mysqli)) {
    respond(false, ['message' => 'Database connection not available.'], 500);
}

$employeeId = $_SESSION['employee_id'] ?? $_SESSION['EmployeeID'] ?? null;
if (!$employeeId) {
    respond(false, ['message' => 'Employee session not found.'], 401);
}

$descriptorJson = $_POST['descriptor'] ?? '';
if ($descriptorJson === '') {
    respond(false, ['message' => 'Face descriptor is required.'], 422);
}

$liveDescriptor = json_decode($descriptorJson, true);

if (!is_array($liveDescriptor) || count($liveDescriptor) !== 128) {
    respond(false, ['message' => 'Invalid live face descriptor. Expected 128 values.'], 422);
}

$stmt = $conn->prepare("
    SELECT FaceProfileID, Embedding, Algorithm, IsActive, UpdatedAt
    FROM employee_face_profile
    WHERE EmployeeID = ?
      AND IsActive = 1
    ORDER BY UpdatedAt DESC, FaceProfileID DESC
    LIMIT 1
");

if (!$stmt) {
    respond(false, ['message' => 'Failed to prepare face profile query.'], 500);
}

$stmt->bind_param("i", $employeeId);
$stmt->execute();
$result = $stmt->get_result();
$profile = $result ? $result->fetch_assoc() : null;
$stmt->close();

if (!$profile) {
    respond(false, ['message' => 'No active face profile found. Please enroll your face first.'], 404);
}

$storedDescriptor = json_decode($profile['Embedding'], true);

if (!is_array($storedDescriptor) || count($storedDescriptor) !== 128) {
    respond(false, ['message' => 'Stored face profile is invalid. Please re-enroll your face.'], 422);
}

function euclideanDistance(array $a, array $b): float
{
    $sum = 0.0;
    $length = min(count($a), count($b));

    for ($i = 0; $i < $length; $i++) {
        $diff = (float)$a[$i] - (float)$b[$i];
        $sum += $diff * $diff;
    }

    return sqrt($sum);
}

$distance = euclideanDistance($storedDescriptor, $liveDescriptor);
$threshold = 0.45;
$isMatch = $distance < $threshold;

if (!$isMatch) {
    respond(false, [
        'message' => 'Face verification failed. Face does not match enrolled profile.',
        'distance' => round($distance, 4),
        'threshold' => $threshold,
        'face_status' => 'NO_MATCH'
    ]);
}

respond(true, [
    'message' => 'Face verified successfully.',
    'distance' => round($distance, 4),
    'threshold' => $threshold,
    'face_status' => 'MATCH'
]);