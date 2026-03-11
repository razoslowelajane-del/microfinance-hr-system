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
    let liveDescriptor = null;

    const hasStoredFaceProfile =
        typeof HAS_FACE_PROFILE !== "undefined" ? !!HAS_FACE_PROFILE : false;

    let faceMode = hasStoredFaceProfile ? "verify" : "enroll";

    function updateFaceModeUI() {
        if (!captureFaceBtn) return;

        if (faceMode === "enroll") {
            captureFaceBtn.innerHTML = `<i data-lucide="scan-face"></i> Enroll Face`;
            readyFace.textContent = facePassed ? "Enrolled" : "Enrollment Required";
            if (!facePassed) {
                faceHeadline.textContent = geoPassed
                    ? "Face enrollment required"
                    : "Waiting for location approval";
                faceMessage.textContent = geoPassed
                    ? "Start the camera and enroll your face once."
                    : "Complete geolocation first before camera access is enabled.";
            }
        } else {
            captureFaceBtn.innerHTML = `<i data-lucide="scan-face"></i> Verify Face`;
            readyFace.textContent = facePassed ? "Passed" : "Pending";
            if (!facePassed) {
                faceHeadline.textContent = geoPassed
                    ? "You may now verify your face"
                    : "Waiting for location approval";
                faceMessage.textContent = geoPassed
                    ? "Start the camera and verify your face."
                    : "Complete geolocation first before camera access is enabled.";
            }
        }

        if (window.lucide) lucide.createIcons();
    }

    function updateOverall() {
        const ready = geoPassed && facePassed;
        readyOverall.textContent = ready ? "Ready" : "Waiting";
        submitAttendanceBtn.disabled = !ready;
    }

    function averageDescriptors(descriptorList) {
        if (!descriptorList.length) return null;

        const length = descriptorList[0].length;
        const avg = new Float32Array(length);

        for (let i = 0; i < length; i++) {
            let sum = 0;
            for (const desc of descriptorList) {
                sum += desc[i];
            }
            avg[i] = sum / descriptorList.length;
        }

        return Array.from(avg);
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

    async function getFaceDetectionWithDescriptor() {
        return await faceapi
            .detectSingleFace(captureCanvas, new faceapi.TinyFaceDetectorOptions())
            .withFaceLandmarks()
            .withFaceDescriptor();
    }

    async function enrollFaceProfile() {
        const samples = [];
        const totalSamples = 5;

        faceHeadline.textContent = "Face enrollment started";
        faceMessage.textContent = "Stay still while we capture multiple face samples.";
        captureStatus.textContent = "Processing";
        attendanceNote.textContent = "Capturing face enrollment samples...";

        for (let i = 0; i < totalSamples; i++) {
            const ctx = captureCanvas.getContext("2d");
            ctx.drawImage(video, 0, 0, captureCanvas.width, captureCanvas.height);

            const detection = await getFaceDetectionWithDescriptor();

            if (!detection) {
                throw new Error(`No face detected on sample ${i + 1}. Please keep your face centered.`);
            }

            samples.push(Array.from(detection.descriptor));
            faceDetectedStatus.textContent = "Yes";
            faceMessage.textContent = `Captured sample ${i + 1} of ${totalSamples}...`;

            await new Promise(resolve => setTimeout(resolve, 700));
        }

        const averagedDescriptor = averageDescriptors(samples);

        if (!averagedDescriptor || averagedDescriptor.length !== 128) {
            throw new Error("Invalid enrollment face descriptor.");
        }

        const finalCtx = captureCanvas.getContext("2d");
        finalCtx.drawImage(video, 0, 0, captureCanvas.width, captureCanvas.height);
        capturedImageBase64 = captureCanvas.toDataURL("image/jpeg", 0.90);

        const formData = new FormData();
        formData.append("embedding", JSON.stringify(averagedDescriptor));

        const response = await fetch("includes/save_face_profile.php", {
            method: "POST",
            body: formData
        });

        const result = await response.json();

        if (!result.ok) {
            throw new Error(result.message || "Failed to save face profile.");
        }

        liveDescriptor = averagedDescriptor;
        facePassed = true;
        faceMode = "verify";

        faceDetectedStatus.textContent = "Yes";
        captureStatus.textContent = "Enrolled";
        readyFace.textContent = "Enrolled";
        faceHeadline.textContent = "Face enrolled successfully";
        faceMessage.textContent = "Your face profile has been saved. You may now submit attendance.";
        attendanceNote.textContent = "Face enrollment complete. Attendance is ready.";
    }

    async function verifyFaceProfile() {
        const ctx = captureCanvas.getContext("2d");
        ctx.drawImage(video, 0, 0, captureCanvas.width, captureCanvas.height);

        const detection = await getFaceDetectionWithDescriptor();

        if (!detection) {
            throw new Error("No face detected. Please center your face clearly and try again.");
        }

        const descriptorArray = Array.from(detection.descriptor);
        capturedImageBase64 = captureCanvas.toDataURL("image/jpeg", 0.90);

        const formData = new FormData();
        formData.append("descriptor", JSON.stringify(descriptorArray));

        const response = await fetch("includes/verify_face_profile.php", {
            method: "POST",
            body: formData
        });

        const result = await response.json();

        if (!result.ok) {
            throw new Error(result.message || "Face verification failed.");
        }

        liveDescriptor = descriptorArray;
        facePassed = true;

        faceDetectedStatus.textContent = "Yes";
        captureStatus.textContent = "Done";
        readyFace.textContent = "Passed";
        faceHeadline.textContent = "Face verified successfully";
        faceMessage.textContent = `Facial verification passed. Distance: ${result.distance}`;
        attendanceNote.textContent = "Face verified successfully. Ready to submit.";
    }

    await loadModels();
    updateFaceModeUI();

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
                            captureFaceBtn.disabled = true;

                            if (faceMode === "enroll") {
                                faceHeadline.textContent = "Face enrollment required";
                                faceMessage.textContent = "Start the camera and enroll your face.";
                                attendanceNote.textContent = "Geolocation passed. Proceed to face enrollment.";
                            } else {
                                faceHeadline.textContent = "You may now open the camera";
                                faceMessage.textContent = "Start the camera and verify your face.";
                                attendanceNote.textContent = "Geolocation passed. Proceed to face verification.";
                            }
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

                    updateFaceModeUI();
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
            await video.play();

            cameraStatus.textContent = "On";
            captureFaceBtn.disabled = false;

            if (faceMode === "enroll") {
                faceHeadline.textContent = "Camera active for enrollment";
                faceMessage.textContent = "Center your face inside the frame, then click Enroll Face.";
                attendanceNote.textContent = "Camera is active. Enroll your face clearly.";
            } else {
                faceHeadline.textContent = "Camera active";
                faceMessage.textContent = "Center your face inside the frame, then click Verify Face.";
                attendanceNote.textContent = "Camera is active. Verify your face clearly.";
            }
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

        try {
            facePassed = false;
            faceDetectedStatus.textContent = "Checking";
            captureStatus.textContent = "Processing";
            readyFace.textContent = "Processing";

            if (faceMode === "enroll") {
                await enrollFaceProfile();
            } else {
                await verifyFaceProfile();
            }

            updateFaceModeUI();
            updateOverall();
        } catch (error) {
            console.error(error);
            facePassed = false;
            faceDetectedStatus.textContent = "No";
            captureStatus.textContent = "Failed";
            readyFace.textContent = "Failed";
            faceHeadline.textContent = faceMode === "enroll"
                ? "Face enrollment failed"
                : "Face verification failed";
            faceMessage.textContent = error.message || "Unable to process the captured face.";
            attendanceNote.textContent = faceMode === "enroll"
                ? "Face enrollment failed. Try again."
                : "Face verification failed.";
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
            formData.append("face_status", "MATCH");
            formData.append("liveness_status", "NOT_CHECKED");

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