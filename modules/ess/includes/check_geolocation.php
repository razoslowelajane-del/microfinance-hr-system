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

$latitude  = isset($_POST['latitude']) ? (float) $_POST['latitude'] : null;
$longitude = isset($_POST['longitude']) ? (float) $_POST['longitude'] : null;

if ($latitude === null || $longitude === null) {
    respond(false, [
        'message' => 'Latitude and longitude are required.',
        'geo_status' => 'GPS_UNAVAILABLE'
    ], 422);
}

if (!isset($conn) || !($conn instanceof mysqli)) {
    respond(false, ['message' => 'Database connection is not available.'], 500);
}

function calculateDistanceMeters($lat1, $lon1, $lat2, $lon2) {
    $earthRadius = 6371000;

    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);

    $a = sin($dLat / 2) * sin($dLat / 2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon / 2) * sin($dLon / 2);

    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

    return $earthRadius * $c;
}

$sql = "SELECT LocationID, LocationName, Latitude, Longitude, RadiusMeters
        FROM work_locations
        WHERE IsActive = 1";

$result = $conn->query($sql);

if (!$result) {
    respond(false, ['message' => 'Failed to fetch work locations.'], 500);
}

if ($result->num_rows === 0) {
    respond(false, [
        'message' => 'No active work locations found.',
        'geo_status' => 'GPS_UNAVAILABLE'
    ], 404);
}

$nearestLocation = null;
$nearestDistance = null;
$matchedLocation = null;

while ($row = $result->fetch_assoc()) {
    $distance = calculateDistanceMeters(
        $latitude,
        $longitude,
        (float)$row['Latitude'],
        (float)$row['Longitude']
    );

    if ($nearestDistance === null || $distance < $nearestDistance) {
        $nearestDistance = $distance;
        $nearestLocation = $row;
    }

    if ($distance <= (float)$row['RadiusMeters']) {
        $matchedLocation = $row;
        $nearestDistance = $distance;
        break;
    }
}

if ($matchedLocation) {
    respond(true, [
        'message' => 'You are inside the allowed work location.',
        'geo_status' => 'IN_GEOFENCE',
        'input' => [
            'latitude' => round($latitude, 7),
            'longitude' => round($longitude, 7)
        ],
        'location' => [
            'LocationID' => (int)$matchedLocation['LocationID'],
            'LocationName' => $matchedLocation['LocationName'],
            'Latitude' => (float)$matchedLocation['Latitude'],
            'Longitude' => (float)$matchedLocation['Longitude'],
            'RadiusMeters' => (float)$matchedLocation['RadiusMeters']
        ],
        'distance_meters' => round($nearestDistance, 2)
    ]);
}

respond(true, [
    'message' => 'You are outside the allowed work location.',
    'geo_status' => 'OUT_GEOFENCE',
    'input' => [
        'latitude' => round($latitude, 7),
        'longitude' => round($longitude, 7)
    ],
    'location' => $nearestLocation ? [
        'LocationID' => (int)$nearestLocation['LocationID'],
        'LocationName' => $nearestLocation['LocationName'],
        'Latitude' => (float)$nearestLocation['Latitude'],
        'Longitude' => (float)$nearestLocation['Longitude'],
        'RadiusMeters' => (float)$nearestLocation['RadiusMeters']
    ] : null,
    'distance_meters' => $nearestDistance !== null ? round($nearestDistance, 2) : null
]);