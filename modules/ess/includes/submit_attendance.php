<?php
require_once __DIR__ . "/auth_employee.php";

header('Content-Type: application/json; charset=utf-8');

function respond(bool $ok, array $data = [], int $code = 200): void
{
    http_response_code($code);
    echo json_encode(array_merge(['ok' => $ok], $data));
    exit;
}

function nextAttendanceEventType(array $events): string
{
    $hasTimeIn = false;
    $hasBreakIn = false;
    $hasBreakOut = false;
    $hasTimeOut = false;

    foreach ($events as $type) {
        $type = strtoupper((string) $type);
        if ($type === 'TIME_IN') $hasTimeIn = true;
        if ($type === 'BREAK_IN') $hasBreakIn = true;
        if ($type === 'BREAK_OUT') $hasBreakOut = true;
        if ($type === 'TIME_OUT') $hasTimeOut = true;
    }

    if (!$hasTimeIn) return 'TIME_IN';
    if (!$hasBreakIn) return 'BREAK_IN';
    if (!$hasBreakOut) return 'BREAK_OUT';
    if (!$hasTimeOut) return 'TIME_OUT';
    return 'COMPLETED';
}

function minutesBetween(?string $start, ?string $end): int
{
    if (!$start || !$end) return 0;

    $startTs = strtotime($start);
    $endTs = strtotime($end);

    if ($startTs === false || $endTs === false) return 0;
    if ($endTs < $startTs) return 0;

    return (int) floor(($endTs - $startTs) / 60);
}

function combineWorkDateTime(string $workDate, ?string $timeValue): ?string
{
    if (!$timeValue) return null;
    return $workDate . ' ' . $timeValue;
}

function computeScheduledDateTimes(string $workDate, ?string $startTime, ?string $endTime): array
{
    if (!$startTime || !$endTime) {
        return [null, null];
    }

    $start = combineWorkDateTime($workDate, $startTime);
    $end = combineWorkDateTime($workDate, $endTime);

    if (!$start || !$end) {
        return [null, null];
    }

    $startTs = strtotime($start);
    $endTs = strtotime($end);

    if ($startTs === false || $endTs === false) {
        return [null, null];
    }

    if ($endTs <= $startTs) {
        $end = date('Y-m-d H:i:s', strtotime($end . ' +1 day'));
    } else {
        $end = date('Y-m-d H:i:s', $endTs);
    }

    return [date('Y-m-d H:i:s', $startTs), $end];
}

function overlapMinutes(int $aStart, int $aEnd, int $bStart, int $bEnd): int
{
    $start = max($aStart, $bStart);
    $end = min($aEnd, $bEnd);

    if ($end <= $start) return 0;

    return (int) floor(($end - $start) / 60);
}

function computeNightMinutes(?string $actualIn, ?string $actualOut, ?string $breakIn = null, ?string $breakOut = null): int
{
    if (!$actualIn || !$actualOut) return 0;

    $inTs = strtotime($actualIn);
    $outTs = strtotime($actualOut);

    if ($inTs === false || $outTs === false || $outTs <= $inTs) {
        return 0;
    }

    $segments = [
        [$inTs, $outTs]
    ];

    if ($breakIn && $breakOut) {
        $breakInTs = strtotime($breakIn);
        $breakOutTs = strtotime($breakOut);

        if ($breakInTs !== false && $breakOutTs !== false && $breakOutTs > $breakInTs) {
            $newSegments = [];

            foreach ($segments as [$segStart, $segEnd]) {
                if ($breakOutTs <= $segStart || $breakInTs >= $segEnd) {
                    $newSegments[] = [$segStart, $segEnd];
                    continue;
                }

                if ($breakInTs > $segStart) {
                    $newSegments[] = [$segStart, $breakInTs];
                }

                if ($breakOutTs < $segEnd) {
                    $newSegments[] = [$breakOutTs, $segEnd];
                }
            }

            $segments = $newSegments;
        }
    }

    $nightMinutes = 0;

    $firstDay = strtotime(date('Y-m-d 00:00:00', $inTs) . ' -1 day');
    $lastDay = strtotime(date('Y-m-d 00:00:00', $outTs) . ' +1 day');

    for ($dayTs = $firstDay; $dayTs <= $lastDay; $dayTs += 86400) {
        $window1Start = strtotime(date('Y-m-d 22:00:00', $dayTs));
        $window1End = strtotime(date('Y-m-d 23:59:59', $dayTs)) + 1;

        $window2Start = strtotime(date('Y-m-d 00:00:00', $dayTs));
        $window2End = strtotime(date('Y-m-d 06:00:00', $dayTs));

        foreach ($segments as [$segStart, $segEnd]) {
            $nightMinutes += overlapMinutes($segStart, $segEnd, $window1Start, $window1End);
            $nightMinutes += overlapMinutes($segStart, $segEnd, $window2Start, $window2End);
        }
    }

    return $nightMinutes;
}

function fetchLatestEventTime(mysqli $conn, int $sessionId, string $eventType): ?string
{
    $sql = "
        SELECT EventTime
        FROM attendance_event
        WHERE SessionID = ?
          AND EventType = ?
        ORDER BY EventTime DESC, EventID DESC
        LIMIT 1
    ";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return null;
    }

    $stmt->bind_param("is", $sessionId, $eventType);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    return $row['EventTime'] ?? null;
}

function recomputeEmployeeSummary(mysqli $conn, int $periodId, int $employeeId): void
{
    $empSql = "
        SELECT DepartmentID, PositionID
        FROM employmentinformation
        WHERE EmployeeID = ?
        ORDER BY EmploymentID DESC
        LIMIT 1
    ";
    $empStmt = $conn->prepare($empSql);

    if (!$empStmt) {
        throw new Exception("Failed to prepare employment lookup: " . $conn->error);
    }

    $empStmt->bind_param("i", $employeeId);
    $empStmt->execute();
    $empResult = $empStmt->get_result();
    $empRow = $empResult ? $empResult->fetch_assoc() : null;
    $empStmt->close();

    if (!$empRow) {
        throw new Exception("Employment information not found for employee summary.");
    }

    $departmentId = (int) ($empRow['DepartmentID'] ?? 0);
    $positionId = (int) ($empRow['PositionID'] ?? 0);

    $sumSql = "
        SELECT
            COALESCE(SUM(RegularMinutes), 0) AS TotalRegularMinutes,
            COALESCE(SUM(OvertimeMinutes), 0) AS TotalOvertimeMinutes,
            COALESCE(SUM(NightDiffMinutes), 0) AS TotalNightDiffMinutes,
            COALESCE(SUM(LateMinutes), 0) AS TotalLateMinutes,
            COALESCE(SUM(UndertimeMinutes), 0) AS TotalUndertimeMinutes
        FROM timesheet_daily
        WHERE PeriodID = ?
          AND EmployeeID = ?
    ";
    $sumStmt = $conn->prepare($sumSql);

    if (!$sumStmt) {
        throw new Exception("Failed to prepare timesheet summary totals query: " . $conn->error);
    }

    $sumStmt->bind_param("ii", $periodId, $employeeId);
    $sumStmt->execute();
    $sumResult = $sumStmt->get_result();
    $sumRow = $sumResult ? $sumResult->fetch_assoc() : null;
    $sumStmt->close();

    $regularHours = round(((int) ($sumRow['TotalRegularMinutes'] ?? 0)) / 60, 2);
    $overtimeHours = round(((int) ($sumRow['TotalOvertimeMinutes'] ?? 0)) / 60, 2);
    $nightDiffHours = round(((int) ($sumRow['TotalNightDiffMinutes'] ?? 0)) / 60, 2);
    $lateMinutes = (int) ($sumRow['TotalLateMinutes'] ?? 0);
    $undertimeMinutes = (int) ($sumRow['TotalUndertimeMinutes'] ?? 0);

    $regHolidayHours = 0.00;
    $specHolidayHours = 0.00;
    $unworkedHolidayHours = 0.00;
    $holidayOvertimeHours = 0.00;
    $absencesHours = 0.00;
    $paidLeaveHours = 0.00;
    $unpaidLeaveHours = 0.00;
    $totalPayableHours = round(
        $regularHours +
        $overtimeHours +
        $nightDiffHours +
        $regHolidayHours +
        $specHolidayHours +
        $unworkedHolidayHours +
        $holidayOvertimeHours +
        $paidLeaveHours,
        2
    );

    $existingSql = "
        SELECT SummaryID
        FROM timesheet_employee_summary
        WHERE PeriodID = ?
          AND EmployeeID = ?
        LIMIT 1
    ";
    $existingStmt = $conn->prepare($existingSql);

    if (!$existingStmt) {
        throw new Exception("Failed to prepare summary existence check: " . $conn->error);
    }

    $existingStmt->bind_param("ii", $periodId, $employeeId);
    $existingStmt->execute();
    $existingResult = $existingStmt->get_result();
    $existingRow = $existingResult ? $existingResult->fetch_assoc() : null;
    $existingStmt->close();

    if ($existingRow) {
        $summaryId = (int) $existingRow['SummaryID'];

        $updateSql = "
            UPDATE timesheet_employee_summary
            SET
                DepartmentID = ?,
                PositionID = ?,
                IsEligibleForHolidayPay = 1,
                RegularHours = ?,
                OvertimeHours = ?,
                NightDiffHours = ?,
                RegHolidayHours = ?,
                SpecHolidayHours = ?,
                UnworkedHolidayHours = ?,
                HolidayOvertimeHours = ?,
                LateMinutes = ?,
                UndertimeMinutes = ?,
                AbsencesHours = ?,
                PaidLeaveHours = ?,
                UnpaidLeaveHours = ?,
                TotalPayableHours = ?,
                Notes = ?
            WHERE SummaryID = ?
        ";
        $updateStmt = $conn->prepare($updateSql);

        if (!$updateStmt) {
            throw new Exception("Failed to prepare summary update: " . $conn->error);
        }

        $notes = "Auto-recomputed from attendance and timesheet daily.";
        $updateStmt->bind_param(
            "iiiidddddddiidddds",
            $departmentId,
            $positionId,
            $regularHours,
            $overtimeHours,
            $nightDiffHours,
            $regHolidayHours,
            $specHolidayHours,
            $unworkedHolidayHours,
            $holidayOvertimeHours,
            $lateMinutes,
            $undertimeMinutes,
            $absencesHours,
            $paidLeaveHours,
            $unpaidLeaveHours,
            $totalPayableHours,
            $notes,
            $summaryId
        );

        if (!$updateStmt->execute()) {
            throw new Exception("Failed to update timesheet employee summary: " . $updateStmt->error);
        }

        $updateStmt->close();
    } else {
        $insertSql = "
            INSERT INTO timesheet_employee_summary
            (
                PeriodID,
                EmployeeID,
                DepartmentID,
                PositionID,
                IsEligibleForHolidayPay,
                RegularHours,
                OvertimeHours,
                NightDiffHours,
                RegHolidayHours,
                SpecHolidayHours,
                UnworkedHolidayHours,
                HolidayOvertimeHours,
                LateMinutes,
                UndertimeMinutes,
                AbsencesHours,
                PaidLeaveHours,
                UnpaidLeaveHours,
                TotalPayableHours,
                Notes
            )
            VALUES
            (
                ?,
                ?,
                ?,
                ?,
                1,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
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
        $insertStmt = $conn->prepare($insertSql);

        if (!$insertStmt) {
            throw new Exception("Failed to prepare summary insert: " . $conn->error);
        }

        $notes = "Auto-created from attendance and timesheet daily.";
        $insertStmt->bind_param(
            "iiiisssssiissssss",
            $periodId,
            $employeeId,
            $departmentId,
            $positionId,
            $regularHours,
            $overtimeHours,
            $nightDiffHours,
            $regHolidayHours,
            $specHolidayHours,
            $unworkedHolidayHours,
            $holidayOvertimeHours,
            $lateMinutes,
            $undertimeMinutes,
            $absencesHours,
            $paidLeaveHours,
            $unpaidLeaveHours,
            $totalPayableHours,
            $notes
        );

        if (!$insertStmt->execute()) {
            throw new Exception("Failed to insert timesheet employee summary: " . $insertStmt->error);
        }

        $insertStmt->close();
    }
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

$eventType = strtoupper(trim($_POST['event_type'] ?? 'TIME_IN'));

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
$faceScore = isset($_POST['face_score']) && $_POST['face_score'] !== ''
    ? round((float) $_POST['face_score'], 2)
    : null;

if ($latitude === null || $longitude === null || $faceImage === '') {
    respond(false, ['message' => 'Missing required attendance data.'], 422);
}

$allowedEventTypes = ['TIME_IN', 'BREAK_IN', 'BREAK_OUT', 'TIME_OUT'];
if (!in_array($eventType, $allowedEventTypes, true)) {
    respond(false, ['message' => 'Invalid attendance event type.'], 422);
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
    $eventTime = date('Y-m-d H:i:s');

    $assignmentId = null;
    $departmentId = null;
    $shiftCode = null;
    $scheduledStart = null;
    $scheduledEnd = null;
    $breakMinutesPlanned = 0;
    $graceMinutes = 0;

    $assignmentSql = "
        SELECT
            ra.AssignmentID,
            ra.ShiftCode,
            wr.DepartmentID,
            st.StartTime,
            st.EndTime,
            st.BreakMinutes,
            st.GraceMinutes
        FROM roster_assignment ra
        INNER JOIN weekly_roster wr ON wr.RosterID = ra.RosterID
        LEFT JOIN shift_type st ON st.ShiftCode = ra.ShiftCode
        WHERE ra.EmployeeID = ?
          AND ra.WorkDate = ?
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
        $departmentId = isset($assignmentRow['DepartmentID']) ? (int) $assignmentRow['DepartmentID'] : null;
        $shiftCode = $assignmentRow['ShiftCode'] ?? null;
        $scheduledStart = $assignmentRow['StartTime'] ?? null;
        $scheduledEnd = $assignmentRow['EndTime'] ?? null;
        $breakMinutesPlanned = isset($assignmentRow['BreakMinutes']) ? (int) $assignmentRow['BreakMinutes'] : 0;
        $graceMinutes = isset($assignmentRow['GraceMinutes']) ? (int) $assignmentRow['GraceMinutes'] : 0;
    } else {
        $deptSql = "
            SELECT DepartmentID
            FROM employmentinformation
            WHERE EmployeeID = ?
            ORDER BY EmploymentID DESC
            LIMIT 1
        ";
        $deptStmt = $conn->prepare($deptSql);

        if (!$deptStmt) {
            throw new Exception("Failed to prepare department lookup: " . $conn->error);
        }

        $deptStmt->bind_param("i", $employeeId);
        $deptStmt->execute();
        $deptResult = $deptStmt->get_result();
        $deptRow = $deptResult ? $deptResult->fetch_assoc() : null;
        $deptStmt->close();

        if ($deptRow) {
            $departmentId = (int) ($deptRow['DepartmentID'] ?? 0);
        }
    }

    $existingEventTypes = [];

    $existingSql = "
        SELECT ae.EventType
        FROM attendance_event ae
        INNER JOIN attendance_session s ON s.SessionID = ae.SessionID
        WHERE s.EmployeeID = ?
          AND s.WorkDate = ?
        ORDER BY ae.EventTime ASC, ae.EventID ASC
    ";
    $existingStmt = $conn->prepare($existingSql);

    if (!$existingStmt) {
        throw new Exception("Failed to prepare attendance sequence check: " . $conn->error);
    }

    $existingStmt->bind_param("is", $employeeId, $today);
    $existingStmt->execute();
    $existingResult = $existingStmt->get_result();

    while ($row = $existingResult->fetch_assoc()) {
        $existingEventTypes[] = strtoupper((string) ($row['EventType'] ?? ''));
    }
    $existingStmt->close();

    $expectedEventType = nextAttendanceEventType($existingEventTypes);

    if ($expectedEventType === 'COMPLETED') {
        throw new Exception("Attendance for today is already completed.");
    }

    if ($eventType !== $expectedEventType) {
        throw new Exception("Invalid attendance sequence. Expected next event: " . $expectedEventType . ".");
    }

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
            ?,
            ?,
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
        "issddiissds",
        $sessionId,
        $eventType,
        $eventTime,
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

    $timesheetUpdated = false;
    $timesheetSummaryUpdated = false;
    $timesheetMessage = "No matching timesheet period found for today.";
    $summaryMessage = "No summary update performed.";

    if ($departmentId) {
        $periodId = null;

        $periodSql = "
            SELECT PeriodID
            FROM timesheet_period
            WHERE DepartmentID = ?
              AND ? BETWEEN StartDate AND EndDate
            ORDER BY PeriodID DESC
            LIMIT 1
        ";
        $periodStmt = $conn->prepare($periodSql);

        if (!$periodStmt) {
            throw new Exception("Failed to prepare timesheet period lookup: " . $conn->error);
        }

        $periodStmt->bind_param("is", $departmentId, $today);
        $periodStmt->execute();
        $periodResult = $periodStmt->get_result();
        $periodRow = $periodResult ? $periodResult->fetch_assoc() : null;
        $periodStmt->close();

        if ($periodRow) {
            $periodId = (int) $periodRow['PeriodID'];

            $daySql = "
                SELECT *
                FROM timesheet_daily
                WHERE PeriodID = ?
                  AND EmployeeID = ?
                  AND WorkDate = ?
                LIMIT 1
            ";
            $dayStmt = $conn->prepare($daySql);

            if (!$dayStmt) {
                throw new Exception("Failed to prepare timesheet daily lookup: " . $conn->error);
            }

            $dayStmt->bind_param("iis", $periodId, $employeeId, $today);
            $dayStmt->execute();
            $dayResult = $dayStmt->get_result();
            $dayRow = $dayResult ? $dayResult->fetch_assoc() : null;
            $dayStmt->close();

            if (!$dayRow) {
                $actualTimeIn = $eventType === 'TIME_IN' ? $eventTime : null;
                $actualTimeOut = $eventType === 'TIME_OUT' ? $eventTime : null;
                $dayStatus = ($shiftCode && $scheduledStart && $scheduledEnd) ? 'INCOMPLETE' : 'NO_SCHEDULE';

                $insertDaySql = "
                    INSERT INTO timesheet_daily
                    (
                        PeriodID,
                        EmployeeID,
                        WorkDate,
                        AssignmentID,
                        SessionID,
                        ShiftCode,
                        ScheduledStart,
                        ScheduledEnd,
                        BreakMinutesPlanned,
                        ActualTimeIn,
                        ActualTimeOut,
                        BreakMinutesActual,
                        RegularMinutes,
                        OvertimeMinutes,
                        NightDiffMinutes,
                        LateMinutes,
                        UndertimeMinutes,
                        DayStatus
                    )
                    VALUES
                    (
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        0,
                        0,
                        0,
                        0,
                        0,
                        ?
                    )
                ";
                $insertDayStmt = $conn->prepare($insertDaySql);

                if (!$insertDayStmt) {
                    throw new Exception("Failed to prepare timesheet daily insert: " . $conn->error);
                }

                $breakActualNull = null;

                $insertDayStmt->bind_param(
                    "sssssssssssss",
                    $periodId,
                    $employeeId,
                    $today,
                    $assignmentId,
                    $sessionId,
                    $shiftCode,
                    $scheduledStart,
                    $scheduledEnd,
                    $breakMinutesPlanned,
                    $actualTimeIn,
                    $actualTimeOut,
                    $breakActualNull,
                    $dayStatus
                );

                if (!$insertDayStmt->execute()) {
                    throw new Exception("Failed to insert timesheet_daily: " . $insertDayStmt->error);
                }

                $timesheetDayId = $insertDayStmt->insert_id;
                $insertDayStmt->close();

                $dayRow = [
                    'TimesheetDayID' => $timesheetDayId,
                    'ActualTimeIn' => $actualTimeIn,
                    'ActualTimeOut' => $actualTimeOut,
                    'BreakMinutesActual' => null
                ];
            } else {
                $timesheetDayId = (int) $dayRow['TimesheetDayID'];
            }

            if (!isset($timesheetDayId)) {
                $timesheetDayId = (int) $dayRow['TimesheetDayID'];
            }

            if ($eventType === 'TIME_IN') {
                $updateSql = "
                    UPDATE timesheet_daily
                    SET
                        AssignmentID = ?,
                        SessionID = ?,
                        ShiftCode = ?,
                        ScheduledStart = ?,
                        ScheduledEnd = ?,
                        BreakMinutesPlanned = ?,
                        ActualTimeIn = COALESCE(ActualTimeIn, ?),
                        DayStatus = ?
                    WHERE TimesheetDayID = ?
                ";
                $updateStmt = $conn->prepare($updateSql);

                if (!$updateStmt) {
                    throw new Exception("Failed to prepare TIME_IN timesheet update: " . $conn->error);
                }

                $dayStatus = ($shiftCode && $scheduledStart && $scheduledEnd) ? 'INCOMPLETE' : 'NO_SCHEDULE';

                $updateStmt->bind_param(
                    "ssssssssi",
                    $assignmentId,
                    $sessionId,
                    $shiftCode,
                    $scheduledStart,
                    $scheduledEnd,
                    $breakMinutesPlanned,
                    $eventTime,
                    $dayStatus,
                    $timesheetDayId
                );

                if (!$updateStmt->execute()) {
                    throw new Exception("Failed to update timesheet for TIME_IN: " . $updateStmt->error);
                }

                $updateStmt->close();
            }

            if ($eventType === 'BREAK_IN' || $eventType === 'BREAK_OUT') {
                $updateSql = "
                    UPDATE timesheet_daily
                    SET
                        AssignmentID = ?,
                        SessionID = ?,
                        ShiftCode = ?,
                        ScheduledStart = ?,
                        ScheduledEnd = ?,
                        BreakMinutesPlanned = ?,
                        DayStatus = ?
                    WHERE TimesheetDayID = ?
                ";
                $updateStmt = $conn->prepare($updateSql);

                if (!$updateStmt) {
                    throw new Exception("Failed to prepare break timesheet update: " . $conn->error);
                }

                $dayStatus = ($shiftCode && $scheduledStart && $scheduledEnd) ? 'INCOMPLETE' : 'NO_SCHEDULE';

                $updateStmt->bind_param(
                    "sssssssi",
                    $assignmentId,
                    $sessionId,
                    $shiftCode,
                    $scheduledStart,
                    $scheduledEnd,
                    $breakMinutesPlanned,
                    $dayStatus,
                    $timesheetDayId
                );

                if (!$updateStmt->execute()) {
                    throw new Exception("Failed to update timesheet during break event: " . $updateStmt->error);
                }

                $updateStmt->close();
            }

            if ($eventType === 'BREAK_OUT') {
                $breakInTime = fetchLatestEventTime($conn, $sessionId, 'BREAK_IN');
                $breakOutTime = $eventTime;

                if ($breakInTime && $breakOutTime) {
                    $breakMinutesActual = minutesBetween($breakInTime, $breakOutTime);

                    $breakUpdateSql = "
                        UPDATE timesheet_daily
                        SET BreakMinutesActual = ?
                        WHERE TimesheetDayID = ?
                    ";
                    $breakUpdateStmt = $conn->prepare($breakUpdateSql);

                    if (!$breakUpdateStmt) {
                        throw new Exception("Failed to prepare BreakMinutesActual update: " . $conn->error);
                    }

                    $breakUpdateStmt->bind_param("ii", $breakMinutesActual, $timesheetDayId);

                    if (!$breakUpdateStmt->execute()) {
                        throw new Exception("Failed to update BreakMinutesActual: " . $breakUpdateStmt->error);
                    }

                    $breakUpdateStmt->close();
                }
            }

            if ($eventType === 'TIME_OUT') {
                $loadDaySql = "
                    SELECT *
                    FROM timesheet_daily
                    WHERE TimesheetDayID = ?
                    LIMIT 1
                ";
                $loadDayStmt = $conn->prepare($loadDaySql);

                if (!$loadDayStmt) {
                    throw new Exception("Failed to prepare final timesheet load: " . $conn->error);
                }

                $loadDayStmt->bind_param("i", $timesheetDayId);
                $loadDayStmt->execute();
                $loadDayResult = $loadDayStmt->get_result();
                $loadedDay = $loadDayResult ? $loadDayResult->fetch_assoc() : null;
                $loadDayStmt->close();

                if (!$loadedDay) {
                    throw new Exception("Timesheet daily row not found during final computation.");
                }

                $actualTimeIn = $loadedDay['ActualTimeIn'] ?? null;
                $actualTimeOut = $eventTime;
                $breakMinutesActual = isset($loadedDay['BreakMinutesActual']) ? (int) $loadedDay['BreakMinutesActual'] : 0;

                [$scheduledStartDT, $scheduledEndDT] = computeScheduledDateTimes($today, $scheduledStart, $scheduledEnd);

                $regularMinutes = 0;
                $overtimeMinutes = 0;
                $nightDiffMinutes = 0;
                $lateMinutes = 0;
                $undertimeMinutes = 0;
                $dayStatus = 'NO_SCHEDULE';

                if ($shiftCode && $scheduledStart && $scheduledEnd && $actualTimeIn && $actualTimeOut) {
                    $scheduledGrossMinutes = minutesBetween($scheduledStartDT, $scheduledEndDT);
                    $scheduledNetMinutes = max(0, $scheduledGrossMinutes - max(0, (int) $breakMinutesPlanned));

                    $actualWorkedGrossMinutes = minutesBetween($actualTimeIn, $actualTimeOut);
                    $actualWorkedNetMinutes = max(0, $actualWorkedGrossMinutes - max(0, $breakMinutesActual));

                    $graceAdjustedStart = $scheduledStartDT
                        ? date('Y-m-d H:i:s', strtotime($scheduledStartDT . " +{$graceMinutes} minutes"))
                        : null;

                    if ($graceAdjustedStart && $actualTimeIn) {
                        $lateMinutes = max(0, minutesBetween($graceAdjustedStart, $actualTimeIn));
                    }

                    if ($scheduledEndDT && $actualTimeOut) {
                        $actualOutTs = strtotime($actualTimeOut);
                        $schedEndTs = strtotime($scheduledEndDT);

                        if ($actualOutTs !== false && $schedEndTs !== false) {
                            if ($actualOutTs < $schedEndTs) {
                                $undertimeMinutes = (int) floor(($schedEndTs - $actualOutTs) / 60);
                            }
                        }
                    }

                    $regularMinutes = min($actualWorkedNetMinutes, $scheduledNetMinutes);
                    $overtimeMinutes = max(0, $actualWorkedNetMinutes - $scheduledNetMinutes);

                    $breakInTime = fetchLatestEventTime($conn, $sessionId, 'BREAK_IN');
                    $breakOutTime = fetchLatestEventTime($conn, $sessionId, 'BREAK_OUT');

                    $nightDiffMinutes = computeNightMinutes($actualTimeIn, $actualTimeOut, $breakInTime, $breakOutTime);

                    $dayStatus = 'OK';
                } elseif ($actualTimeIn && $actualTimeOut) {
                    $dayStatus = 'NO_SCHEDULE';
                } else {
                    $dayStatus = 'INCOMPLETE';
                }

                $finalUpdateSql = "
                    UPDATE timesheet_daily
                    SET
                        AssignmentID = ?,
                        SessionID = ?,
                        ShiftCode = ?,
                        ScheduledStart = ?,
                        ScheduledEnd = ?,
                        BreakMinutesPlanned = ?,
                        ActualTimeOut = ?,
                        BreakMinutesActual = ?,
                        RegularMinutes = ?,
                        OvertimeMinutes = ?,
                        NightDiffMinutes = ?,
                        LateMinutes = ?,
                        UndertimeMinutes = ?,
                        DayStatus = ?
                    WHERE TimesheetDayID = ?
                ";
                $finalUpdateStmt = $conn->prepare($finalUpdateSql);

                if (!$finalUpdateStmt) {
                    throw new Exception("Failed to prepare final timesheet update: " . $conn->error);
                }

                $finalUpdateStmt->bind_param(
                    "ssssssiiiiiiisi",
                    $assignmentId,
                    $sessionId,
                    $shiftCode,
                    $scheduledStart,
                    $scheduledEnd,
                    $breakMinutesPlanned,
                    $actualTimeOut,
                    $breakMinutesActual,
                    $regularMinutes,
                    $overtimeMinutes,
                    $nightDiffMinutes,
                    $lateMinutes,
                    $undertimeMinutes,
                    $dayStatus,
                    $timesheetDayId
                );

                if (!$finalUpdateStmt->execute()) {
                    throw new Exception("Failed to finalize timesheet daily: " . $finalUpdateStmt->error);
                }

                $finalUpdateStmt->close();
            }

            $timesheetUpdated = true;
            $timesheetMessage = "Timesheet daily updated successfully.";

            recomputeEmployeeSummary($conn, $periodId, $employeeId);
            $timesheetSummaryUpdated = true;
            $summaryMessage = "Timesheet employee summary updated successfully.";
        }
    }

    if ($eventType === 'TIME_OUT') {
        $closeSql = "
            UPDATE attendance_session
            SET
                Status = 'CLOSED',
                ClosedAt = ?
            WHERE SessionID = ?
        ";
        $closeStmt = $conn->prepare($closeSql);

        if (!$closeStmt) {
            throw new Exception("Failed to prepare attendance session close: " . $conn->error);
        }

        $closeStmt->bind_param("si", $eventTime, $sessionId);

        if (!$closeStmt->execute()) {
            throw new Exception("Failed to close attendance session: " . $closeStmt->error);
        }

        $closeStmt->close();
    }

    $conn->commit();

    respond(true, [
        'message' => $eventType . ' submitted successfully.',
        'session_id' => $sessionId,
        'event_id' => $eventId,
        'image_path' => $relativePath,
        'timesheet_updated' => $timesheetUpdated,
        'timesheet_message' => $timesheetMessage,
        'timesheet_summary_updated' => $timesheetSummaryUpdated,
        'summary_message' => $summaryMessage
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