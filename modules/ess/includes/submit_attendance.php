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

$latitude = isset($_POST['latitude']) && $_POST['latitude'] !== ''
    ? (float) $_POST['latitude']
    : null;

$longitude = isset($_POST['longitude']) && $_POST['longitude'] !== ''
    ? (float) $_POST['longitude']
    : null;

$locationId = isset($_POST['location_id']) && $_POST['location_id'] !== ''
    ? (int) $_POST['location_id']
    : null;

$distanceMeters = isset($_POST['distance_meters']) && $_POST['distance_meters'] !== ''
    ? (int) round((float) $_POST['distance_meters'])
    : null;

$faceImage = $_POST['face_image'] ?? '';
$faceStatus = strtoupper(trim($_POST['face_status'] ?? 'MATCH'));
$livenessStatus = strtoupper(trim($_POST['liveness_status'] ?? 'NOT_CHECKED'));

if ($latitude === null || $longitude === null || $faceImage === '') {
    respond(false, ['message' => 'Missing required attendance data.'], 422);
}

$allowedFaceStatuses = ['MATCH', 'NO_MATCH', 'NO_FACE', 'MULTIPLE_FACES', 'CAMERA_ERROR'];
if (!in_array($faceStatus, $allowedFaceStatuses, true)) {
    $faceStatus = 'CAMERA_ERROR';
}

$allowedLivenessStatuses = ['PASS', 'FAIL', 'NOT_CHECKED'];
if (!in_array($livenessStatus, $allowedLivenessStatuses, true)) {
    $livenessStatus = 'NOT_CHECKED';
}

$geoStatus = 'IN_GEOFENCE';

if (!preg_match('/^data:image\/(\w+);base64,/', $faceImage, $matches)) {
    respond(false, ['message' => 'Invalid face image format.'], 422);
}

$imageType = strtolower($matches[1]);
if (!in_array($imageType, ['jpg', 'jpeg', 'png'], true)) {
    respond(false, ['message' => 'Unsupported image type.'], 422);
}

$base64Data = substr($faceImage, strpos($faceImage, ',') + 1);
$decodedImage = base64_decode($base64Data, true);

if ($decodedImage === false) {
    respond(false, ['message' => 'Failed to decode face image.'], 422);
}

$uploadDir = __DIR__ . "/../../../uploads/attendance_capture/";
if (!is_dir($uploadDir) && !mkdir($uploadDir, 0777, true)) {
    respond(false, ['message' => 'Failed to create upload directory.'], 500);
}

$fileName = "emp_" . $employeeId . "_" . date("Ymd_His") . "." . $imageType;
$filePath = $uploadDir . $fileName;
$relativePath = "uploads/attendance_capture/" . $fileName;

if (!file_put_contents($filePath, $decodedImage)) {
    respond(false, ['message' => 'Failed to save face capture.'], 500);
}

$conn->begin_transaction();

try {
    $today = date('Y-m-d');

    /*
     |--------------------------------------------------------------
     | 1. Find today's roster assignment for the employee
     |--------------------------------------------------------------
     */
    $assignmentId = null;

    $assignmentSql = "
        SELECT AssignmentID
        FROM roster_assignment
        WHERE EmployeeID = ?
          AND WorkDate = ?
        LIMIT 1
    ";
    $assignmentStmt = $conn->prepare($assignmentSql);

    if (!$assignmentStmt) {
        throw new Exception("Failed to prepare roster assignment query: " . $conn->error);
    }

    $assignmentStmt->bind_param("is", $employeeId, $today);
    $assignmentStmt->execute();
    $assignmentResult = $assignmentStmt->get_result();
    $assignmentRow = $assignmentResult ? $assignmentResult->fetch_assoc() : null;
    $assignmentStmt->close();

    if ($assignmentRow) {
        $assignmentId = (int) $assignmentRow['AssignmentID'];
    }

    /*
     |--------------------------------------------------------------
     | 2. Prevent duplicate TIME_IN for the same open session today
     |--------------------------------------------------------------
     */
    $existingSql = "
        SELECT ae.EventID
        FROM attendance_event ae
        INNER JOIN attendance_session s ON s.SessionID = ae.SessionID
        WHERE s.EmployeeID = ?
          AND s.WorkDate = ?
          AND ae.EventType = 'TIME_IN'
        LIMIT 1
    ";
    $existingStmt = $conn->prepare($existingSql);

    if (!$existingStmt) {
        throw new Exception("Failed to prepare duplicate attendance check: " . $conn->error);
    }

    $existingStmt->bind_param("is", $employeeId, $today);
    $existingStmt->execute();
    $existingResult = $existingStmt->get_result();
    $existingRow = $existingResult ? $existingResult->fetch_assoc() : null;
    $existingStmt->close();

    if ($existingRow) {
        throw new Exception("You already submitted your time-in attendance for today.");
    }

    /*
     |--------------------------------------------------------------
     | 3. Create or reuse today's attendance session
     |--------------------------------------------------------------
     */
    $sessionId = null;

    $sessionFindSql = "
        SELECT SessionID, Status
        FROM attendance_session
        WHERE EmployeeID = ?
          AND WorkDate = ?
        LIMIT 1
    ";
    $sessionFindStmt = $conn->prepare($sessionFindSql);

    if (!$sessionFindStmt) {
        throw new Exception("Failed to prepare attendance session lookup: " . $conn->error);
    }

    $sessionFindStmt->bind_param("is", $employeeId, $today);
    $sessionFindStmt->execute();
    $sessionFindResult = $sessionFindStmt->get_result();
    $sessionRow = $sessionFindResult ? $sessionFindResult->fetch_assoc() : null;
    $sessionFindStmt->close();

    if ($sessionRow) {
        $sessionId = (int) $sessionRow['SessionID'];

        if (($sessionRow['Status'] ?? '') === 'CLOSED') {
            throw new Exception("Attendance session for today is already closed.");
        }
    } else {
        $sessionInsertSql = "
            INSERT INTO attendance_session
            (
                EmployeeID,
                WorkDate,
                AssignmentID,
                Status
            )
            VALUES
            (
                ?,
                ?,
                ?,
                'OPEN'
            )
        ";
        $sessionInsertStmt = $conn->prepare($sessionInsertSql);

        if (!$sessionInsertStmt) {
            throw new Exception("Failed to prepare attendance session insert: " . $conn->error);
        }

        $sessionInsertStmt->bind_param("isi", $employeeId, $today, $assignmentId);

        if (!$sessionInsertStmt->execute()) {
            throw new Exception("Failed to create attendance session: " . $sessionInsertStmt->error);
        }

        $sessionId = $sessionInsertStmt->insert_id;
        $sessionInsertStmt->close();
    }

    /*
     |--------------------------------------------------------------
     | 4. Insert attendance_event
     |--------------------------------------------------------------
     */
    $faceScore = null;

    $eventSql = "
        INSERT INTO attendance_event
        (
            SessionID,
            EventType,
            EventTime,
            Latitude,
            Longitude,
            LocationID,
            DistanceMeters,
            GeoStatus,
            FaceStatus,
            FaceScore,
            LivenessStatus
        )
        VALUES
        (
            ?,
            'TIME_IN',
            NOW(),
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?
        )
    ";

    $eventStmt = $conn->prepare($eventSql);

    if (!$eventStmt) {
        throw new Exception("Failed to prepare attendance_event insert: " . $conn->error);
    }

    $eventStmt->bind_param(
        "iddiissds",
        $sessionId,
        $latitude,
        $longitude,
        $locationId,
        $distanceMeters,
        $geoStatus,
        $faceStatus,
        $faceScore,
        $livenessStatus
    );

    if (!$eventStmt->execute()) {
        throw new Exception("Failed to insert attendance_event: " . $eventStmt->error);
    }

    $eventId = $eventStmt->insert_id;
    $eventStmt->close();

    /*
     |--------------------------------------------------------------
     | 5. Insert attendance_capture
     |--------------------------------------------------------------
     */
    $captureSql = "
        INSERT INTO attendance_capture
        (
            EventID,
            ImagePath
        )
        VALUES
        (
            ?,
            ?
        )
    ";

    $captureStmt = $conn->prepare($captureSql);

    if (!$captureStmt) {
        throw new Exception("Failed to prepare attendance_capture insert: " . $conn->error);
    }

    $captureStmt->bind_param("is", $eventId, $relativePath);

    if (!$captureStmt->execute()) {
        throw new Exception("Failed to insert attendance_capture: " . $captureStmt->error);
    }

    $captureStmt->close();

    $conn->commit();

    respond(true, [
        'message' => 'Attendance submitted successfully.',
        'session_id' => $sessionId,
        'event_id' => $eventId,
        'image_path' => $relativePath
    ]);
} catch (Throwable $e) {
    $conn->rollback();

    if (isset($filePath) && is_file($filePath)) {
        @unlink($filePath);
    }

    respond(false, [
        'message' => $e->getMessage()
    ], 500);
}