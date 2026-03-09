document.addEventListener("DOMContentLoaded", async () => {
    if (window.lucide) lucide.createIcons();

    const body = document.body;
    const themeToggle = document.getElementById("themeToggle");

    if (localStorage.getItem("theme") === "dark") {
        body.classList.add("dark-mode");
    }

    if (themeToggle) {
        themeToggle.addEventListener("click", () => {
            body.classList.toggle("dark-mode");
            localStorage.setItem("theme", body.classList.contains("dark-mode") ? "dark" : "light");
            if (window.lucide) lucide.createIcons();
        });
    }

    const checkLocationBtn = document.getElementById("checkLocationBtn");
    const startCameraBtn = document.getElementById("startCameraBtn");
    const captureFaceBtn = document.getElementById("captureFaceBtn");
    const submitAttendanceBtn = document.getElementById("submitAttendanceBtn");

    const geoBadge = document.getElementById("geoBadge");
    const geoHeadline = document.getElementById("geoHeadline");
    const geoMessage = document.getElementById("geoMessage");
    const geoCoordsText = document.getElementById("geoCoordsText");
    const geoAccuracyText = document.getElementById("geoAccuracyText");
    const geoLocationText = document.getElementById("geoLocationText");
    const geoDistanceText = document.getElementById("geoDistanceText");

    const faceHeadline = document.getElementById("faceHeadline");
    const faceMessage = document.getElementById("faceMessage");
    const cameraStatus = document.getElementById("cameraStatus");
    const faceDetectedStatus = document.getElementById("faceDetectedStatus");
    const captureStatus = document.getElementById("captureStatus");

    const readyGeo = document.getElementById("readyGeo");
    const readyFace = document.getElementById("readyFace");
    const readyOverall = document.getElementById("readyOverall");
    const attendanceNote = document.getElementById("attendanceNote");

    const video = document.getElementById("video");
    const captureCanvas = document.getElementById("captureCanvas");

    let modelsLoaded = false;
    let geoPassed = false;
    let facePassed = false;
    let latestGeo = null;
    let stream = null;
    let capturedImageBase64 = "";

    function updateOverall() {
        const ready = geoPassed && facePassed;
        readyOverall.textContent = ready ? "Ready" : "Waiting";
        submitAttendanceBtn.disabled = !ready;
    }

    async function loadModels() {
        try {
            await faceapi.nets.tinyFaceDetector.loadFromUri("../../models");
            await faceapi.nets.faceLandmark68Net.loadFromUri("../../models");
            await faceapi.nets.faceRecognitionNet.loadFromUri("../../models");
            modelsLoaded = true;
            console.log("Face models loaded");
        } catch (error) {
            console.error("Model loading failed:", error);
            modelsLoaded = false;
            faceHeadline.textContent = "Model loading failed";
            faceMessage.textContent = "Check if your ../../models path and filenames are correct.";
        }
    }

    await loadModels();

    checkLocationBtn?.addEventListener("click", async () => {
        if (!navigator.geolocation) {
            geoBadge.textContent = "Unsupported";
            geoHeadline.textContent = "Geolocation not supported";
            geoMessage.textContent = "Your browser does not support geolocation.";
            readyGeo.textContent = "Unsupported";
            updateOverall();
            return;
        }

        geoBadge.textContent = "Checking";
        geoHeadline.textContent = "Checking your location";
        geoMessage.textContent = "Please wait while we validate your position...";
        attendanceNote.textContent = "Checking geolocation...";

        navigator.geolocation.getCurrentPosition(
            async (position) => {
                const latitude = position.coords.latitude;
                const longitude = position.coords.longitude;
                const accuracy = position.coords.accuracy;

                geoCoordsText.textContent = `${latitude.toFixed(6)}, ${longitude.toFixed(6)}`;
                geoAccuracyText.textContent = `${accuracy.toFixed(2)} m`;

                try {
                    const formData = new FormData();
                    formData.append("latitude", latitude);
                    formData.append("longitude", longitude);

                    const response = await fetch("includes/check_geolocation.php", {
                        method: "POST",
                        body: formData
                    });

                    const result = await response.json();

                    if (!result.ok) {
                        geoPassed = false;
                        geoBadge.textContent = "Error";
                        geoHeadline.textContent = "Geolocation validation failed";
                        geoMessage.textContent = result.message || "Unable to validate your location.";
                        readyGeo.textContent = "Error";
                        geoLocationText.textContent = "--";
                        geoDistanceText.textContent = "--";
                        startCameraBtn.disabled = true;
                        captureFaceBtn.disabled = true;
                        updateOverall();
                        return;
                    }

                    latestGeo = result;

                    geoLocationText.textContent = result.location?.LocationName || "--";
                    geoDistanceText.textContent =
                        typeof result.distance_meters !== "undefined" && result.distance_meters !== null
                            ? `${Number(result.distance_meters).toFixed(2)} m`
                            : "--";

                    if (result.geo_status === "IN_GEOFENCE") {
                        geoPassed = true;
                        geoBadge.textContent = "Passed";
                        geoHeadline.textContent = "Location verified";
                        geoMessage.textContent = result.message || "You are inside the allowed work location.";
                        readyGeo.textContent = "Passed";

                        if (modelsLoaded) {
                            startCameraBtn.disabled = false;
                            cameraStatus.textContent = "Ready";
                            faceHeadline.textContent = "You may now open the camera";
                            faceMessage.textContent = "Start the camera and capture your face.";
                            attendanceNote.textContent = "Geolocation passed. Proceed to camera verification.";
                        } else {
                            startCameraBtn.disabled = true;
                            faceHeadline.textContent = "Models not loaded";
                            faceMessage.textContent = "Geolocation passed but face models failed to load.";
                            attendanceNote.textContent = "Fix face-api models first.";
                        }
                    } else {
                        geoPassed = false;
                        geoBadge.textContent = "Outside";
                        geoHeadline.textContent = "Outside geofence";
                        geoMessage.textContent = result.message || "You are outside the allowed work location.";
                        readyGeo.textContent = "Failed";
                        startCameraBtn.disabled = true;
                        captureFaceBtn.disabled = true;
                        cameraStatus.textContent = "Locked";
                        attendanceNote.textContent = "You must be inside the geofence before camera unlocks.";
                    }

                    updateOverall();
                } catch (error) {
                    console.error(error);
                    geoPassed = false;
                    geoBadge.textContent = "Error";
                    geoHeadline.textContent = "Server validation error";
                    geoMessage.textContent = "Unable to validate geolocation on server.";
                    readyGeo.textContent = "Error";
                    startCameraBtn.disabled = true;
                    captureFaceBtn.disabled = true;
                    attendanceNote.textContent = "Server error during geolocation validation.";
                    updateOverall();
                }
            },
            (error) => {
                console.error(error);
                geoPassed = false;
                geoBadge.textContent = "Denied";
                geoHeadline.textContent = "Location permission denied";
                geoMessage.textContent = error.message || "Unable to access your location.";
                readyGeo.textContent = "Denied";
                startCameraBtn.disabled = true;
                captureFaceBtn.disabled = true;
                attendanceNote.textContent = "Allow location permission first.";
                updateOverall();
            },
            {
                enableHighAccuracy: true,
                timeout: 15000,
                maximumAge: 0
            }
        );
    });

    startCameraBtn?.addEventListener("click", async () => {
        if (!geoPassed || !modelsLoaded) return;

        try {
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
            }

            stream = await navigator.mediaDevices.getUserMedia({
                video: {
                    facingMode: "user",
                    width: { ideal: 640 },
                    height: { ideal: 480 }
                },
                audio: false
            });

            video.srcObject = stream;
            video.play();

            cameraStatus.textContent = "On";
            captureFaceBtn.disabled = false;
            faceHeadline.textContent = "Camera active";
            faceMessage.textContent = "Center your face inside the frame, then click Capture Face.";
            attendanceNote.textContent = "Camera is active. Capture your face clearly.";
        } catch (error) {
            console.error(error);
            cameraStatus.textContent = "Denied";
            captureFaceBtn.disabled = true;
            faceHeadline.textContent = "Camera access failed";
            faceMessage.textContent = "Please allow camera permission.";
            attendanceNote.textContent = "Camera permission is required.";
        }
    });

    captureFaceBtn?.addEventListener("click", async () => {
        if (!stream || !modelsLoaded) return;

        if (!video.videoWidth || !video.videoHeight) {
            faceHeadline.textContent = "Camera not ready";
            faceMessage.textContent = "Wait a moment then try again.";
            return;
        }

        captureCanvas.width = video.videoWidth;
        captureCanvas.height = video.videoHeight;

        const ctx = captureCanvas.getContext("2d");
        ctx.drawImage(video, 0, 0, captureCanvas.width, captureCanvas.height);

        try {
            const detection = await faceapi
                .detectSingleFace(captureCanvas, new faceapi.TinyFaceDetectorOptions())
                .withFaceLandmarks()
                .withFaceDescriptor();

            if (!detection) {
                facePassed = false;
                faceDetectedStatus.textContent = "No";
                captureStatus.textContent = "Failed";
                readyFace.textContent = "Failed";
                faceHeadline.textContent = "No face detected";
                faceMessage.textContent = "Please center your face clearly and try again.";
                attendanceNote.textContent = "No face detected. Recapture.";
                updateOverall();
                return;
            }

            capturedImageBase64 = captureCanvas.toDataURL("image/jpeg", 0.90);
            facePassed = true;
            faceDetectedStatus.textContent = "Yes";
            captureStatus.textContent = "Done";
            readyFace.textContent = "Passed";
            faceHeadline.textContent = "Face captured successfully";
            faceMessage.textContent = "Facial verification passed. You may now submit attendance.";
            attendanceNote.textContent = "Face captured successfully. Ready to submit.";
            updateOverall();
        } catch (error) {
            console.error(error);
            facePassed = false;
            faceDetectedStatus.textContent = "Error";
            captureStatus.textContent = "Error";
            readyFace.textContent = "Error";
            faceHeadline.textContent = "Face detection error";
            faceMessage.textContent = "Unable to process the captured face.";
            attendanceNote.textContent = "Face detection failed.";
            updateOverall();
        }
    });

    submitAttendanceBtn?.addEventListener("click", async () => {
        if (!geoPassed || !facePassed || !latestGeo || !capturedImageBase64) {
            attendanceNote.textContent = "Complete geolocation and face capture first.";
            return;
        }

        submitAttendanceBtn.disabled = true;
        attendanceNote.textContent = "Submitting attendance...";

        try {
            const formData = new FormData();
            formData.append("latitude", latestGeo.input.latitude);
            formData.append("longitude", latestGeo.input.longitude);
            formData.append("location_id", latestGeo.location?.LocationID || "");
            formData.append("distance_meters", latestGeo.distance_meters ?? "");
            formData.append("face_image", capturedImageBase64);

            const response = await fetch("submit_attendance.php", {
                method: "POST",
                body: formData
            });

            const result = await response.json();

            if (result.ok) {
                readyOverall.textContent = "Submitted";
                attendanceNote.textContent = result.message || "Attendance submitted successfully.";

                if (stream) {
                    stream.getTracks().forEach(track => track.stop());
                }

                cameraStatus.textContent = "Done";
                startCameraBtn.disabled = true;
                captureFaceBtn.disabled = true;
                submitAttendanceBtn.disabled = true;
            } else {
                attendanceNote.textContent = result.message || "Attendance submission failed.";
                submitAttendanceBtn.disabled = false;
            }
        } catch (error) {
            console.error(error);
            attendanceNote.textContent = "Server error while submitting attendance.";
            submitAttendanceBtn.disabled = false;
        }
    });
});