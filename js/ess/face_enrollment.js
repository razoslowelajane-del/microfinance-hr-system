document.addEventListener("DOMContentLoaded", () => {
    const MODEL_URL = "/Microfinance/models";

    const video = document.getElementById("video");
    const captureCanvas = document.getElementById("captureCanvas");

    const enrollmentBadge = document.getElementById("enrollmentBadge");
    const enrollmentHeadline = document.getElementById("enrollmentHeadline");
    const enrollmentMessage = document.getElementById("enrollmentMessage");
    const enrollmentNote = document.getElementById("enrollmentNote");

    const cameraHeadline = document.getElementById("cameraHeadline");
    const cameraMessage = document.getElementById("cameraMessage");

    const modelStatus = document.getElementById("modelStatus");
    const cameraStatus = document.getElementById("cameraStatus");
    const faceDetectedStatus = document.getElementById("faceDetectedStatus");

    const profileStatusText = document.getElementById("profileStatusText");
    const profileAlgorithmText = document.getElementById("profileAlgorithmText");
    const profileEnrolledAtText = document.getElementById("profileEnrolledAtText");
    const profileModeText = document.getElementById("profileModeText");

    const startCameraBtn = document.getElementById("startCameraBtn");
    const captureEnrollBtn = document.getElementById("captureEnrollBtn");

    let stream = null;
    let modelsLoaded = false;
    let cameraReady = false;
    let isSaving = false;

    function refreshIcons() {
        if (window.lucide) lucide.createIcons();
    }

    function setBadge(type, text) {
        enrollmentBadge.className = "status-badge";

        if (type === "success") enrollmentBadge.classList.add("success");
        else if (type === "danger") enrollmentBadge.classList.add("danger");
        else if (type === "warning") enrollmentBadge.classList.add("warning");
        else enrollmentBadge.classList.add("neutral");

        enrollmentBadge.textContent = text;
    }

    function formatDateTime(value) {
        if (!value) return "--";
        const dt = new Date(String(value).replace(" ", "T"));
        if (isNaN(dt.getTime())) return value;
        return dt.toLocaleString();
    }

    function setStartCameraButtonOpened() {
        startCameraBtn.classList.remove("btn-muted");
        startCameraBtn.classList.add("btn-primary");
        startCameraBtn.innerHTML = `
            <i data-lucide="camera-off"></i>
            Stop Camera
        `;
        refreshIcons();
    }

    function setStartCameraButtonClosed() {
        startCameraBtn.classList.remove("btn-primary");
        startCameraBtn.classList.add("btn-muted");
        startCameraBtn.innerHTML = `
            <i data-lucide="camera"></i>
            Start Camera
        `;
        refreshIcons();
    }

    function setCaptureButtonEnabled(enabled) {
        captureEnrollBtn.disabled = !enabled;

        if (enabled) {
            captureEnrollBtn.classList.remove("btn-muted");
            captureEnrollBtn.classList.add("btn-primary");
        } else {
            captureEnrollBtn.classList.remove("btn-primary");
            captureEnrollBtn.classList.add("btn-muted");
        }
    }

    function resetFaceStatus() {
        faceDetectedStatus.textContent = "Pending";
    }

    function stopCamera() {
        if (stream) {
            stream.getTracks().forEach(track => track.stop());
            stream = null;
        }

        if (video) {
            video.pause();
            video.srcObject = null;
        }

        cameraReady = false;
        cameraStatus.textContent = "Off";
        resetFaceStatus();
        setCaptureButtonEnabled(false);
        setStartCameraButtonClosed();
    }

    async function loadEnrollmentStatus() {
        try {
            const res = await fetch("includes/face_enrollment_status.php", {
                method: "GET",
                credentials: "same-origin"
            });

            const data = await res.json();

            if (!data.ok) {
                throw new Error(data.message || "Failed to load enrollment status.");
            }

            if (data.has_profile && data.profile) {
                profileStatusText.textContent = data.profile.IsActive == 1 ? "Enrolled" : "Inactive";
                profileAlgorithmText.textContent = data.profile.Algorithm || "--";
                profileEnrolledAtText.textContent = formatDateTime(data.profile.EnrolledAt);
                profileModeText.textContent = "Re-enrollment";

                setBadge("success", "Profile Found");
                enrollmentHeadline.textContent = "Existing face profile detected";
                enrollmentMessage.textContent = "You can re-enroll to replace your current saved face profile.";
            } else {
                profileStatusText.textContent = "Not Enrolled";
                profileAlgorithmText.textContent = "--";
                profileEnrolledAtText.textContent = "--";
                profileModeText.textContent = "New Enrollment";

                setBadge("warning", "No Profile");
                enrollmentHeadline.textContent = "No face profile yet";
                enrollmentMessage.textContent = "Start camera and save your first face enrollment.";
            }
        } catch (err) {
            console.error(err);
            profileStatusText.textContent = "Error";
            setBadge("danger", "Error");
            enrollmentHeadline.textContent = "Could not load enrollment status";
            enrollmentMessage.textContent = err.message || "Please refresh the page.";
        }
    }

    async function loadModels() {
        try {
            modelStatus.textContent = "Loading";
            setBadge("neutral", "Loading Models");
            enrollmentHeadline.textContent = "Loading local face models";
            enrollmentMessage.textContent = "Please wait while models are loaded from /Microfinance/models.";

            await Promise.all([
                faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL),
                faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL),
                faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL)
            ]);

            modelsLoaded = true;
            modelStatus.textContent = "Ready";
            setBadge("success", "Models Ready");
            enrollmentHeadline.textContent = "Models loaded successfully";
            enrollmentMessage.textContent = "You may now start the camera.";
            enrollmentNote.textContent = "Local face models loaded from /Microfinance/models.";
        } catch (err) {
            console.error("Model loading failed:", err);
            modelStatus.textContent = "Failed";
            setBadge("danger", "Model Error");
            enrollmentHeadline.textContent = "Failed to load face models";
            enrollmentMessage.textContent = "Check if /Microfinance/models contains all required model files.";
            enrollmentNote.textContent = "Required: tinyFaceDetector, faceLandmark68, faceRecognition models.";
        }
    }

    async function startCamera() {
        try {
            if (!modelsLoaded) {
                throw new Error("Face models are not loaded yet.");
            }

            stopCamera();

            stream = await navigator.mediaDevices.getUserMedia({
                video: {
                    facingMode: "user",
                    width: { ideal: 640 },
                    height: { ideal: 480 }
                },
                audio: false
            });

            video.srcObject = stream;

            await new Promise((resolve) => {
                video.onloadedmetadata = () => resolve();
            });

            await video.play();

            cameraReady = true;
            cameraStatus.textContent = "On";
            setCaptureButtonEnabled(true);
            setStartCameraButtonOpened();

            setBadge("success", "Camera Ready");
            cameraHeadline.textContent = "Camera is ready";
            cameraMessage.textContent = "Make sure only one face is visible and centered in the frame.";
            enrollmentHeadline.textContent = "Camera opened successfully";
            enrollmentMessage.textContent = "You can now click Save Face Enrollment.";
            enrollmentNote.textContent = "When your face is stable and clear, click Save Face Enrollment.";
        } catch (err) {
            console.error("Camera error:", err);
            cameraReady = false;
            cameraStatus.textContent = "Error";
            setCaptureButtonEnabled(false);
            setStartCameraButtonClosed();

            setBadge("danger", "Camera Error");
            cameraHeadline.textContent = "Unable to start camera";
            cameraMessage.textContent = err.message || "Please allow camera permission.";
        }
    }

    async function detectFace() {
        return await faceapi
            .detectAllFaces(video, new faceapi.TinyFaceDetectorOptions({
                inputSize: 416,
                scoreThreshold: 0.5
            }))
            .withFaceLandmarks()
            .withFaceDescriptors();
    }

    async function saveEnrollment() {
        try {
            if (isSaving) return;

            if (!modelsLoaded) {
                throw new Error("Face models are not ready.");
            }

            if (!cameraReady || !stream) {
                throw new Error("Camera is not started.");
            }

            isSaving = true;
            setCaptureButtonEnabled(false);

            faceDetectedStatus.textContent = "Checking";
            setBadge("neutral", "Checking Face");
            enrollmentHeadline.textContent = "Validating face";
            enrollmentMessage.textContent = "Please hold still while we validate your face.";

            const detections = await detectFace();

            if (!Array.isArray(detections) || detections.length === 0) {
                faceDetectedStatus.textContent = "No Face";
                throw new Error("No face detected. Please face the camera clearly.");
            }

            if (detections.length > 1) {
                faceDetectedStatus.textContent = "Multiple";
                throw new Error("Multiple faces detected. Only one face should be visible.");
            }

            faceDetectedStatus.textContent = "Detected";

            const descriptor = Array.from(detections[0].descriptor);

            if (!Array.isArray(descriptor) || descriptor.length !== 128) {
                faceDetectedStatus.textContent = "Invalid";
                throw new Error("Generated face descriptor is invalid.");
            }

            const res = await fetch("includes/face_enrollment_save.php", {
                method: "POST",
                credentials: "same-origin",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    descriptor,
                    algorithm: "face-api.js-128d"
                })
            });

            const data = await res.json();

            if (!data.ok) {
                throw new Error(data.message || "Failed to save face enrollment.");
            }

            faceDetectedStatus.textContent = "Saved";
            setBadge("success", "Saved");
            enrollmentHeadline.textContent = "Face enrollment successful";
            enrollmentMessage.textContent = data.message || "Your face profile has been saved.";
            enrollmentNote.textContent = "You may now use attendance verification with this enrolled face profile.";

            await loadEnrollmentStatus();
            setCaptureButtonEnabled(true);
        } catch (err) {
            console.error("Enrollment save error:", err);
            setBadge("danger", "Failed");
            enrollmentHeadline.textContent = "Face enrollment failed";
            enrollmentMessage.textContent = err.message || "Please try again.";
        } finally {
            isSaving = false;
            if (cameraReady) {
                setCaptureButtonEnabled(true);
            }
        }
    }

    startCameraBtn.addEventListener("click", async () => {
        if (cameraReady) {
            stopCamera();
            setBadge("warning", "Camera Stopped");
            enrollmentHeadline.textContent = "Camera stopped";
            enrollmentMessage.textContent = "Click Start Camera to open it again.";
            cameraHeadline.textContent = "Camera not started";
            cameraMessage.textContent = "Start the camera, face forward, and keep your face centered.";
            return;
        }

        await startCamera();
    });

    captureEnrollBtn.addEventListener("click", saveEnrollment);

    setStartCameraButtonClosed();
    setCaptureButtonEnabled(false);

    loadEnrollmentStatus();
    loadModels();

    window.addEventListener("beforeunload", stopCamera);
});