<?php
require_once __DIR__ . "/includes/auth_employee.php";

$employeeId = $_SESSION['employee_id'] ?? $_SESSION['EmployeeID'] ?? null;
$hasFaceProfile = false;

if ($employeeId && isset($conn) && $conn instanceof mysqli) {
    $stmt = $conn->prepare("
        SELECT Embedding
        FROM employee_face_profile
        WHERE EmployeeID = ?
          AND IsActive = 1
        LIMIT 1
    ");

    if ($stmt) {
        $stmt->bind_param("i", $employeeId);
        $stmt->execute();
        $result = $stmt->get_result();
        $profile = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        if ($profile && isset($profile['Embedding'])) {
            $embedding = json_decode($profile['Embedding'], true);
            $hasFaceProfile = is_array($embedding) && count($embedding) === 128;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Attendance</title>
    <link rel="stylesheet" href="../../css/ess/attendance.css?v=<?php echo time(); ?>">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
</head>
<body>
    <?php include __DIR__ . "/sidebar.php"; ?>

    <main class="page-content">
        <section class="attendance-page">
            <section class="attendance-shell">
                <header class="page-header">
                    <div class="page-header-text">
                        <h1>Attendance Verification</h1>
                        <p>Geolocation first, then facial verification before attendance submit.</p>
                    </div>

                    <div class="page-header-actions">
                        <?php
                        $themeFile = __DIR__ . "/theme.php";
                        if (file_exists($themeFile)) {
                            include $themeFile;
                        }
                        ?>
                    </div>
                </header>

                <div class="attendance-grid">
                    <!-- LEFT: GEOLOCATION -->
                    <section class="card geo-card">
                        <div class="card-head">
                            <div class="head-icon primary-soft">
                                <i data-lucide="map-pinned"></i>
                            </div>
                            <div>
                                <h2>Location Verification</h2>
                                <p>Check if your current location is inside the company geofence.</p>
                            </div>
                        </div>

                        <div class="hero-status">
                            <span class="status-badge neutral" id="geoBadge">Not Checked</span>
                            <h3 id="geoHeadline">Waiting for geolocation check</h3>
                            <p id="geoMessage">Allow location access and tap the button below.</p>
                        </div>

                        <div class="details-grid">
                            <div class="detail-box">
                                <span class="label">Your Coordinates</span>
                                <strong id="geoCoordsText">--</strong>
                            </div>

                            <div class="detail-box">
                                <span class="label">GPS Accuracy</span>
                                <strong id="geoAccuracyText">--</strong>
                            </div>

                            <div class="detail-box">
                                <span class="label">Nearest Work Location</span>
                                <strong id="geoLocationText">--</strong>
                            </div>

                            <div class="detail-box">
                                <span class="label">Distance From Site</span>
                                <strong id="geoDistanceText">--</strong>
                            </div>
                        </div>

                        <div class="action-row">
                            <button type="button" class="btn btn-primary" id="checkLocationBtn">
                                <i data-lucide="locate-fixed"></i>
                                Check My Location
                            </button>
                        </div>

                        <div class="company-site-box">
                            <h4>Registered Work Location</h4>
                            <p><strong id="registeredLocationName">Not yet loaded</strong></p>
                            <p id="registeredLocationMeta">Your assigned work location will appear here after validation.</p>
                        </div>
                    </section>

                    <!-- RIGHT: FACIAL RECOGNITION -->
                    <section class="card face-card">
                        <div class="card-head">
                            <div class="head-icon neutral-soft">
                                <i data-lucide="scan-face"></i>
                            </div>
                            <div>
                                <h2>Facial Verification</h2>
                                <p>Camera unlocks only after geolocation passes.</p>
                            </div>
                        </div>

                        <div class="face-placeholder">
                            <div class="camera-frame">
                                <video id="video" autoplay muted playsinline></video>
                                <canvas id="captureCanvas" style="display:none;"></canvas>
                                <div class="face-outline"></div>
                            </div>

                            <div class="face-placeholder-text">
                                <h3 id="faceHeadline">Waiting for location approval</h3>
                                <p id="faceMessage">
                                    Complete geolocation first before camera access is enabled.
                                </p>
                            </div>

                            <div class="face-status-list">
                                <div class="mini-status">
                                    <span>Camera</span>
                                    <strong id="cameraStatus">Locked</strong>
                                </div>
                                <div class="mini-status">
                                    <span>Face Detected</span>
                                    <strong id="faceDetectedStatus">Pending</strong>
                                </div>
                                <div class="mini-status">
                                    <span>Capture</span>
                                    <strong id="captureStatus">Pending</strong>
                                </div>
                            </div>

                            <div class="action-row action-row-wrap">
                                <button type="button" class="btn btn-muted" id="startCameraBtn" disabled>
                                    <i data-lucide="camera"></i>
                                    Start Camera
                                </button>

                                <button type="button" class="btn btn-primary" id="captureFaceBtn" disabled>
                                    <i data-lucide="scan-face"></i>
                                    Capture Face
                                </button>
                            </div>
                        </div>
                    </section>
                </div>

                <section class="card summary-card">
                    <div class="card-head">
                        <div class="head-icon info-soft">
                            <i data-lucide="shield-check"></i>
                        </div>
                        <div>
                            <h2>Attendance Readiness</h2>
                            <p>Attendance is ready only after geolocation and face capture both pass.</p>
                        </div>
                    </div>

                    <div class="readiness-grid">
                        <div class="readiness-item">
                            <span>Geolocation</span>
                            <strong id="readyGeo">Pending</strong>
                        </div>
                        <div class="readiness-item">
                            <span>Face Verification</span>
                            <strong id="readyFace">Pending</strong>
                        </div>
                        <div class="readiness-item">
                            <span>Overall</span>
                            <strong id="readyOverall">Waiting</strong>
                        </div>
                    </div>

                    <div class="action-row submit-row">
                        <button type="button" class="btn btn-primary" id="submitAttendanceBtn" disabled>
                            <i data-lucide="check-check"></i>
                            Submit Attendance
                        </button>
                    </div>

                    <div class="note-box" id="attendanceNote">
                        Complete geolocation first, then capture your face to continue.
                    </div>
                </section>
            </section>
        </section>
    </main>

    <script>
        const HAS_FACE_PROFILE = <?php echo $hasFaceProfile ? 'true' : 'false'; ?>;

        document.addEventListener('DOMContentLoaded', function () {
            if (window.lucide) lucide.createIcons();
        });
    </script>
    <script src="../../js/ess/attendance.js?v=<?php echo time(); ?>"></script>
</body>
</html>