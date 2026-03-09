<?php
require_once __DIR__ . "/auth_officer.php";
require_once __DIR__ . "/../../../config/config.php";

header('Content-Type: application/json');

try {
    $result = $conn->query("
        SELECT ShiftCode, ShiftName, StartTime, EndTime, BreakMinutes, GraceMinutes
        FROM shift_type
        WHERE IsActive = 1
        ORDER BY ShiftCode
    ");

    if (!$result) {
        throw new Exception("Failed to fetch shifts.");
    }

    $shifts = [];
    while ($row = $result->fetch_assoc()) {
        $shifts[] = $row;
    }

    echo json_encode([
        "shifts" => $shifts
    ]);
} catch (Exception $e) {
    echo json_encode([
        "error" => $e->getMessage()
    ]);
}