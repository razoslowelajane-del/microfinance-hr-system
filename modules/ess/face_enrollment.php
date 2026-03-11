<?php
require_once __DIR__ . "/includes/auth_employee.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Face Enrollment</title>
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
                        <h1>Face Enrollment</h1>
                        <p>Register your face profile first before using attendance verification.</p>
                    </div>

                    <div class="page-header-actions" style="display:flex; gap:10px; align-items:center;">
                        <a href="attendance.php" class="btn btn-muted">
                            <i data-lucide="arrow-left"></i>
                            Back to Attendance
                        </a>

                        <?php
                        $themeFile = __DIR__ . "/theme.php";
                        if (file_exists($themeFile)) {
                            include $themeFile;
                        }
                        ?>
                    </div>
                </header>

                <div class="attendance-grid">
                    <section class="card face-card">
                        <div class="card-head">
                            <div class="head-icon primary-soft">
                                <i data-lucide="scan-face"></i>
                            </div>
                            <div>
                                <h2>Enrollment Camera</h2>
                                <p>Use your front camera and make sure only one face is visible.</p>
                            </div>
                        </div>

                        <div class="hero-status">
                            <span class="status-badge neutral" id="enrollmentBadge">Loading</span>
                            <h3 id="enrollmentHeadline">Preparing face enrollment</h3>
                            <p id="enrollmentMessage">Please wait while local face models are loading.</p>
                        </div>

                        <div class="face-placeholder">
                            <div class="camera-frame">
                                <video id="video" autoplay muted playsinline></video>
                                <canvas id="captureCanvas" style="display:none;"></canvas>
                                <div class="face-outline"></div>
                            </div>

                            <div class="face-placeholder-text">
                                <h3 id="cameraHeadline">Camera not started</h3>
                                <p id="cameraMessage">
                                    Start the camera, face forward, and keep your face centered.
                                </p>
                            </div>

                            <div class="face-status-list">
                                <div class="mini-status">
                                    <span>Models</span>
                                    <strong id="modelStatus">Loading</strong>
                                </div>
                                <div class="mini-status">
                                    <span>Camera</span>
                                    <strong id="cameraStatus">Off</strong>
                                </div>
                                <div class="mini-status">
                                    <span>Face Check</span>
                                    <strong id="faceDetectedStatus">Pending</strong>
                                </div>
                            </div>

                            <div class="action-row action-row-wrap">
                                <button type="button" class="btn btn-muted" id="startCameraBtn">
                                    <i data-lucide="camera"></i>
                                    Start Camera
                                </button>

                                <button type="button" class="btn btn-primary" id="captureEnrollBtn" disabled>
                                    <i data-lucide="scan-face"></i>
                                    Save Face Enrollment
                                </button>
                            </div>
                        </div>
                    </section>

                    <section class="card summary-card">
                        <div class="card-head">
                            <div class="head-icon info-soft">
                                <i data-lucide="shield-check"></i>
                            </div>
                            <div>
                                <h2>Saved Face Profile</h2>
                                <p>Your current enrollment record from the database appears here.</p>
                            </div>
                        </div>

                        <div class="details-grid">
                            <div class="detail-box">
                                <span class="label">Profile Status</span>
                                <strong id="profileStatusText">Checking...</strong>
                            </div>

                            <div class="detail-box">
                                <span class="label">Algorithm</span>
                                <strong id="profileAlgorithmText">--</strong>
                            </div>

                            <div class="detail-box">
                                <span class="label">Enrolled At</span>
                                <strong id="profileEnrolledAtText">--</strong>
                            </div>

                            <div class="detail-box">
                                <span class="label">Mode</span>
                                <strong id="profileModeText">New Enrollment</strong>
                            </div>
                        </div>

                        <div class="note-box" id="enrollmentNote">
                            Tip: good lighting, no mask, no heavy blur, and only one face should appear on camera.
                        </div>

                        <div class="action-row submit-row">
                            <a href="attendance.php" class="btn btn-primary">
                                <i data-lucide="shield-check"></i>
                                Proceed to Attendance
                            </a>
                        </div>
                    </section>
                </div>
            </section>
        </section>
    </main>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            if (window.lucide) lucide.createIcons();
        });
    </script>
    <script src="../../js/ess/face_enrollment.js?v=<?php echo time(); ?>"></script>
</body>
</html>