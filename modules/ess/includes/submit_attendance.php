<?php
require_once __DIR__ . "/../../../config/config.php";
require_once __DIR__ . "/auth_employee.php";

header('Content-Type: application/json');

function respond($ok, $data = [], $code = 200) {
    http_response_code($code);
    echo json_encode(array_merge(['ok' => $ok], $data));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, ['message' => 'Invalid request method.'], 405);
}

$employeeId = $_SESSION['employee_id'] ?? $_SESSION['EmployeeID'] ?? null;

if (!$employeeId) {
    respond(false, ['message' => 'Employee session not found.'], 401);
}

$latitude       = isset($_POST['latitude']) ? (float)$_POST['latitude'] : null;
$longitude      = isset($_POST['longitude']) ? (float)$_POST['longitude'] : null;
$locationId     = isset($_POST['location_id']) ? (int)$_POST['location_id'] : 0;
$distanceMeters = isset($_POST['distance_meters']) ? (float)$_POST['distance_meters'] : null;
$faceImage      = $_POST['face_image'] ?? '';

if ($latitude === null || $longitude === null || !$faceImage) {
    respond(false, ['message' => 'Missing required attendance data.'], 422);
}

if (!preg_match('/^data:image\/(\w+);base64,/', $faceImage, $matches)) {
    respond(false, ['message' => 'Invalid face image format.'], 422);
}

$imageType = strtolower($matches[1]);
if (!in_array($imageType, ['jpg', 'jpeg', 'png'], true)) {
    respond(false, ['message' => 'Unsupported image type.'], 422);
}

$base64Data = substr($faceImage, strpos($faceImage, ',') + 1);
$decodedImage = base64_decode($base64Data);

if ($decodedImage === false) {
    respond(false, ['message' => 'Failed to decode face image.'], 422);
}

$uploadDir = __DIR__ . "/../../../uploads/attendance_capture/";
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$fileName = "emp_" . $employeeId . "_" . date("Ymd_His") . "." . $imageType;
$filePath = $uploadDir . $fileName;
$relativePath = "uploads/attendance_capture/" . $fileName;

if (!file_put_contents($filePath, $decodedImage)) {
    respond(false, ['message' => 'Failed to save face capture.'], 500);
}

/*
|--------------------------------------------------------------------------
| IMPORTANT
| Adjust these INSERTs based on your exact HR4 attendance schema.
|--------------------------------------------------------------------------
*/

$conn->begin_transaction();

try {
    $sql1 = "INSERT INTO attendance_event
            (EmployeeID, EventType, EventDateTime, LocationID, Latitude, Longitude, DistanceMeters, VerificationStatus)
            VALUES (?, 'TIME_IN', NOW(), ?, ?, ?, ?, 'VERIFIED')";

    $stmt1 = $conn->prepare($sql1);
    if (!$stmt1) {
        throw new Exception("Failed to prepare attendance_event insert: " . $conn->error);
    }

    $stmt1->bind_param("iiddd", $employeeId, $locationId, $latitude, $longitude, $distanceMeters);
    if (!$stmt1->execute()) {
        throw new Exception("Failed to insert attendance_event: " . $stmt1->error);
    }

    $eventId = $stmt1->insert_id;
    $stmt1->close();

    $sql2 = "INSERT INTO attendance_capture
            (EventID, ImagePath, CapturedAt, CaptureType)
            VALUES (?, ?, NOW(), 'FACE')";

    $stmt2 = $conn->prepare($sql2);
    if (!$stmt2) {
        throw new Exception("Failed to prepare attendance_capture insert: " . $conn->error);
    }

    $stmt2->bind_param("is", $eventId, $relativePath);
    if (!$stmt2->execute()) {
        throw new Exception("Failed to insert attendance_capture: " . $stmt2->error);
    }

    $stmt2->close();

    $conn->commit();

    respond(true, [
        'message' => 'Attendance submitted successfully.',
        'event_id' => $eventId,
        'image_path' => $relativePath
    ]);
} catch (Exception $e) {
    $conn->rollback();
    respond(false, ['message' => $e->getMessage()], 500);
}