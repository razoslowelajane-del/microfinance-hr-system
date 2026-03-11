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

    const capturedPreview = document.getElementById("capturedPreview");
    const noPreviewText = document.getElementById("noPreviewText");

    let stream = null;
    let modelsLoaded = false;
    let cameraReady = false;
    let isSaving = false;
    let autoLoopRunning = false;
    let faceAlreadyCaptured = false;

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

    function resetPreview() {
        if (capturedPreview) {
            capturedPreview.src = "";
            capturedPreview.style.display = "none";
        }
        if (noPreviewText) {
            noPreviewText.style.display = "inline";
        }
    }

    function showPreviewFromCanvas() {
        captureCanvas.width = video.videoWidth;
        captureCanvas.height = video.videoHeight;

        const ctx = captureCanvas.getContext("2d");
        ctx.drawImage(video, 0, 0, captureCanvas.width, captureCanvas.height);

        const imageData = captureCanvas.toDataURL("image/png");

        if (capturedPreview) {
            capturedPreview.src = imageData;
            capturedPreview.style.display = "block";
        }

        if (noPreviewText) {
            noPreviewText.style.display = "none";
        }
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
        autoLoopRunning = false;
        faceAlreadyCaptured = false;
        cameraStatus.textContent = "Off";
        faceDetectedStatus.textContent = "Pending";
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
            } else {
                profileStatusText.textContent = "Not Enrolled";
                profileAlgorithmText.textContent = "--";
                profileEnrolledAtText.textContent = "--";
                profileModeText.textContent = "New Enrollment";
            }
        } catch (err) {
            console.error(err);
            profileStatusText.textContent = "Error";
            profileAlgorithmText.textContent = "--";
            profileEnrolledAtText.textContent = "--";
            profileModeText.textContent = "--";
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
            resetPreview();

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
            faceAlreadyCaptured = false;
            cameraStatus.textContent = "On";
            faceDetectedStatus.textContent = "Scanning";
            setStartCameraButtonOpened();

            setBadge("success", "Camera Ready");
            cameraHeadline.textContent = "Camera is ready";
            cameraMessage.textContent = "Look straight at the camera. Face will be captured automatically.";
            enrollmentHeadline.textContent = "Auto face enrollment is active";
            enrollmentMessage.textContent = "Please hold still. We will automatically save once one clear face is detected.";
            enrollmentNote.textContent = "No need to click save. Just stay centered and wait for auto-capture.";

            autoDetectLoop();
        } catch (err) {
            console.error("Camera error:", err);
            cameraReady = false;
            cameraStatus.textContent = "Error";
            setStartCameraButtonClosed();

            setBadge("danger", "Camera Error");
            cameraHeadline.textContent = "Unable to start camera";
            cameraMessage.textContent = err.message || "Please allow camera permission.";
        }
    }

    async function detectFace() {
        return await faceapi
            .detectAllFaces(
                video,
                new faceapi.TinyFaceDetectorOptions({
                    inputSize: 416,
                    scoreThreshold: 0.5
                })
            )
            .withFaceLandmarks()
            .withFaceDescriptors();
    }

    async function saveEnrollmentAuto(descriptor) {
        const descriptorArray = Array.from(descriptor);

        const res = await fetch("includes/face_enrollment_save.php", {
            method: "POST",
            credentials: "same-origin",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                descriptor: descriptorArray,
                algorithm: "face-api.js-128d"
            })
        });

        const data = await res.json();

        if (!data.ok) {
            throw new Error(data.message || "Failed to save face enrollment.");
        }

        return data;
    }

    async function autoDetectLoop() {
        if (autoLoopRunning) return;
        autoLoopRunning = true;

        try {
            while (cameraReady && !faceAlreadyCaptured) {
                const detections = await detectFace();

                if (!cameraReady || faceAlreadyCaptured) break;

                if (!Array.isArray(detections) || detections.length === 0) {
                    faceDetectedStatus.textContent = "No Face";
                    enrollmentHeadline.textContent = "No face detected yet";
                    enrollmentMessage.textContent = "Please face the camera clearly.";
                    await new Promise(resolve => setTimeout(resolve, 700));
                    continue;
                }

                if (detections.length > 1) {
                    faceDetectedStatus.textContent = "Multiple Faces";
                    enrollmentHeadline.textContent = "Multiple faces detected";
                    enrollmentMessage.textContent = "Make sure only one face is visible.";
                    await new Promise(resolve => setTimeout(resolve, 700));
                    continue;
                }

                if (isSaving) {
                    await new Promise(resolve => setTimeout(resolve, 400));
                    continue;
                }

                const descriptor = detections[0].descriptor;

                if (!descriptor || descriptor.length !== 128) {
                    faceDetectedStatus.textContent = "Invalid";
                    enrollmentHeadline.textContent = "Invalid face descriptor";
                    enrollmentMessage.textContent = "Please stay still and try again.";
                    await new Promise(resolve => setTimeout(resolve, 700));
                    continue;
                }

                isSaving = true;
                faceDetectedStatus.textContent = "Detected";
                setBadge("neutral", "Capturing");
                enrollmentHeadline.textContent = "Face detected";
                enrollmentMessage.textContent = "Capturing and saving your face profile...";

                showPreviewFromCanvas();

                const data = await saveEnrollmentAuto(descriptor);

                faceAlreadyCaptured = true;
                faceDetectedStatus.textContent = "Saved";
                setBadge("success", "Saved");
                enrollmentHeadline.textContent = "Face enrollment successful";
                enrollmentMessage.textContent = data.message || "Your face profile has been saved.";
                enrollmentNote.textContent = "Captured face preview is shown on the right side. You may now proceed to attendance.";

                await loadEnrollmentStatus();
                stopCamera();
                break;
            }
        } catch (err) {
            console.error("Auto enrollment error:", err);
            setBadge("danger", "Failed");
            enrollmentHeadline.textContent = "Face enrollment failed";
            enrollmentMessage.textContent = err.message || "Please try again.";
        } finally {
            isSaving = false;
            autoLoopRunning = false;
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
            enrollmentNote.textContent = "No need to click save. The system will auto-capture when one face is detected.";
            return;
        }

        await startCamera();
    });

    setStartCameraButtonClosed();
    resetPreview();

    loadEnrollmentStatus();
    loadModels();

    window.addEventListener("beforeunload", stopCamera);
});