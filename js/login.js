// Initialize Lucide icons
console.log('Login.js loaded');
lucide.createIcons();

// Theme Toggle
// Theme Toggle
const themeToggle = document.getElementById("themeToggle");
const body = document.body;

// Check for saved theme preference
const savedTheme = localStorage.getItem("theme");
if (savedTheme === "dark") {
    body.classList.add("dark-mode");
}

if (themeToggle) {
    themeToggle.addEventListener("click", () => {
        body.classList.toggle("dark-mode");
        const isDark = body.classList.contains("dark-mode");
        localStorage.setItem("theme", isDark ? "dark" : "light");
    });
}




// Password Toggle
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const button = input.parentElement.querySelector(".toggle-password");
    const icon = button.querySelector(".eye-icon");

    if (input.type === "password") {
        input.type = "text";
        icon.setAttribute("data-lucide", "eye-off");
    } else {
        input.type = "password";
        icon.setAttribute("data-lucide", "eye");
    }

    window.lucide.createIcons();
}

// OTP Functionality
let otpTimerInterval;
let isSubmitting = false;  // Prevent double submission

// Generate OTP inputs
function generateOtpInputs() {
    const otpInputsContainer = document.getElementById('otpInputs');
    if (!otpInputsContainer) return;

    otpInputsContainer.innerHTML = '';
    for (let i = 0; i < 6; i++) {
        const input = document.createElement('input');
        input.type = 'text';
        input.maxLength = '1';
        input.className = 'otp-input';
        input.dataset.index = i;
        input.inputMode = 'numeric';
        input.autocomplete = 'off';

        // Handle input
        input.addEventListener('input', (e) => {
            // Only allow numbers
            e.target.value = e.target.value.replace(/[^0-9]/g, '');

            if (e.target.value.length === 1) {
                e.target.classList.add('filled');
                const nextInput = e.target.nextElementSibling;
                if (nextInput && nextInput.classList.contains('otp-input')) {
                    nextInput.focus();
                }
            } else if (e.target.value.length === 0) {
                e.target.classList.remove('filled');
            }
        });

        // Handle backspace
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Backspace') {
                if (e.target.value === '') {
                    e.preventDefault();
                    const prevInput = e.target.previousElementSibling;
                    if (prevInput && prevInput.classList.contains('otp-input')) {
                        prevInput.focus();
                        prevInput.value = '';
                        prevInput.classList.remove('filled');
                    }
                } else {
                    e.target.value = '';
                    e.target.classList.remove('filled');
                }
            } else if (e.key === 'ArrowLeft') {
                const prevInput = e.target.previousElementSibling;
                if (prevInput && prevInput.classList.contains('otp-input')) {
                    prevInput.focus();
                }
            } else if (e.key === 'ArrowRight') {
                const nextInput = e.target.nextElementSibling;
                if (nextInput && nextInput.classList.contains('otp-input')) {
                    nextInput.focus();
                }
            }
        });

        // Handle keypress to allow only numbers
        input.addEventListener('keypress', (e) => {
            if (!/[0-9]/.test(e.key)) {
                e.preventDefault();
            }
        });

        // Paste handling
        input.addEventListener('paste', (e) => {
            e.preventDefault();
            const pasteData = e.clipboardData.getData('text').replace(/[^0-9]/g, '');
            if (pasteData) {
                const inputs = document.querySelectorAll('.otp-input');
                let currentIndex = parseInt(input.dataset.index);
                for (let j = 0; j < pasteData.length && currentIndex < 6; j++, currentIndex++) {
                    inputs[currentIndex].value = pasteData[j];
                    inputs[currentIndex].classList.add('filled');
                    if (currentIndex < 5) {
                        inputs[currentIndex + 1].focus();
                    }
                }
            }
        });

        otpInputsContainer.appendChild(input);
    }

    // Focus first input
    setTimeout(() => {
        const firstInput = otpInputsContainer.querySelector('.otp-input');
        if (firstInput) {
            firstInput.focus();
        }
    }, 100);
}

// Show OTP popup
function showOtpPopup() {
    const otpOverlay = document.getElementById('otpOverlay');
    if (!otpOverlay) return;

    isSubmitting = false;  // Reset flag when showing OTP popup
    otpOverlay.classList.add('active');
    document.body.style.overflow = 'hidden';
    generateOtpInputs();
    startOtpTimer();
}

// Hide OTP popup
function hideOtpPopup() {
    const otpOverlay = document.getElementById('otpOverlay');
    if (!otpOverlay) return;

    isSubmitting = false;  // Reset flag when hiding OTP popup
    otpOverlay.classList.remove('active');
    document.body.style.overflow = '';

    if (otpTimerInterval) {
        clearInterval(otpTimerInterval);
    }
}

// Start OTP timer
function startOtpTimer() {
    const otpTimer = document.getElementById('otpTimer');
    const resendOtp = document.getElementById('resendOtp');
    if (!otpTimer || !resendOtp) return;

    if (otpTimerInterval) {
        clearInterval(otpTimerInterval);
    }

    let timeLeft = 3600; // 1 hour = 3600 seconds
    otpTimer.textContent = `(60:00)`;
    resendOtp.style.display = 'none';

    otpTimerInterval = setInterval(() => {
        timeLeft--;
        const minutes = Math.floor(timeLeft / 60);
        const seconds = timeLeft % 60;
        otpTimer.textContent = `(${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')})`;

        if (timeLeft <= 0) {
            clearInterval(otpTimerInterval);
            otpTimer.textContent = '';
            resendOtp.style.display = 'inline';
        }
    }, 1000);
}

// Handle Login
async function handleLogin(e) {
    e.preventDefault();

    // Prevent double submission
    if (isSubmitting) {
        return;
    }
    isSubmitting = true;

    const email = document.getElementById("loginEmail").value.trim();
    const password = document.getElementById("loginPassword").value;

    if (!email || !password) {
        isSubmitting = false;
        Swal.fire({
            icon: "error",
            title: "Missing Fields",
            text: "Please enter email/username and password",
            confirmButtonColor: "#2ca078"
        });
        return;
    }

    Swal.fire({
        title: "Signing in...",
        text: "Please wait",
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => Swal.showLoading()
    });

    try {
        const formData = new FormData();
        formData.append("action", "login");
        formData.append("email", email);
        formData.append("password", password);

        // Append portal type
        const portal = document.getElementById('loginPortal')?.value || 'workforce';
        formData.append("login_portal", portal);

        const response = await fetch("login_action.php", {
            method: "POST",
            body: formData
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const result = await response.json();

        if (result.success && result.requires_otp) {
            isSubmitting = false;
            Swal.close();
            showOtpPopup();
        } else if (result.success) {
            isSubmitting = false;
            await Swal.fire({
                icon: "success",
                title: "Login successful!",
                text: "Redirecting...",
                timer: 1000,
                showConfirmButton: false
            });
            window.location.href = result.redirect;
        } else {
            isSubmitting = false;
            await Swal.fire({
                icon: "error",
                title: "Login failed",
                text: result.message || "Invalid credentials",
                confirmButtonColor: "#2ca078"
            });
        }
    } catch (error) {
        isSubmitting = false;
        console.error("Login error:", error);
        await Swal.fire({
            icon: "error",
            title: "Error",
            text: error.message || "Something went wrong. Please try again.",
            confirmButtonColor: "#2ca078"
        });
    }
}

// Handle OTP Verification
async function handleOtpVerification(e) {
    e.preventDefault();

    // Prevent double submission
    if (isSubmitting) {
        return;
    }
    isSubmitting = true;

    const otpInputsContainer = document.getElementById('otpInputs');
    if (!otpInputsContainer) {
        isSubmitting = false;
        return;
    }

    const inputs = otpInputsContainer.querySelectorAll('.otp-input');
    let otpCode = '';
    let isValid = true;

    inputs.forEach(input => {
        if (input.value === '') {
            isValid = false;
            input.classList.add('error');
        } else {
            input.classList.remove('error');
            otpCode += input.value;
        }
    });

    if (!isValid) {
        isSubmitting = false;
        Swal.fire({
            icon: 'error',
            title: 'Invalid OTP',
            text: 'Please fill in all digits',
            confirmButtonColor: '#2ca078'
        });
        return;
    }

    try {
        const formData = new FormData();
        formData.append("action", "verify_otp");
        formData.append("otp", otpCode);

        console.log("Submitting OTP:", otpCode, "Length:", otpCode.length);

        const response = await fetch("login_action.php", {
            method: "POST",
            body: formData
        });

        const result = await response.json();
        console.log("OTP verification response:", result);

        if (result.success) {
            isSubmitting = false;
            hideOtpPopup();
            await Swal.fire({
                icon: "success",
                title: "Login successful!",
                text: "Redirecting...",
                timer: 1000,
                showConfirmButton: false
            });
            window.location.href = result.redirect;
        } else {
            isSubmitting = false;
            await Swal.fire({
                icon: "error",
                title: "Verification failed",
                text: result.message || "Invalid OTP",
                confirmButtonColor: "#2ca078"
            });
        }
    } catch (error) {
        isSubmitting = false;
        await Swal.fire({
            icon: "error",
            title: "Error",
            text: "Something went wrong. Please try again.",
            confirmButtonColor: "#2ca078"
        });
    }
}

// Handle Resend OTP
async function handleResendOtp(e) {
    e.preventDefault();

    // Prevent double submission
    if (isSubmitting) {
        return;
    }
    isSubmitting = true;

    try {
        const formData = new FormData();
        formData.append("action", "resend_otp");

        const response = await fetch("login_action.php", {
            method: "POST",
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            isSubmitting = false;
            startOtpTimer();
            generateOtpInputs();

            Swal.fire({
                icon: "success",
                title: "OTP Resent",
                text: "A new verification code has been sent to your email",
                confirmButtonColor: "#2ca078",
                timer: 3000,
                timerProgressBar: true
            });

            // Show debug OTP in development
            if (result.debug_otp) {
                setTimeout(() => {
                    Swal.fire({
                        icon: 'info',
                        title: 'Debug OTP',
                        text: `Your new OTP code is: ${result.debug_otp}`,
                        confirmButtonColor: '#2ca078',
                        timer: 5000,
                        timerProgressBar: true
                    });
                }, 3500);
            }
        } else {
            isSubmitting = false;
            await Swal.fire({
                icon: "error",
                title: "Resend failed",
                text: result.message || "Failed to resend OTP",
                confirmButtonColor: "#2ca078"
            });
        }
    } catch (error) {
        isSubmitting = false;
        await Swal.fire({
            icon: "error",
            title: "Error",
            text: "Something went wrong. Please try again.",
            confirmButtonColor: "#2ca078"
        });
    }
}

// Initialize everything when DOM is loaded
document.addEventListener("DOMContentLoaded", () => {
    window.lucide.createIcons();

    // Login form
    const loginForm = document.querySelector('form[onsubmit="handleLogin(event)"]');
    if (loginForm) {
        loginForm.addEventListener('submit', handleLogin);
    }

    // OTP form
    const otpForm = document.getElementById('otpForm');
    if (otpForm) {
        otpForm.addEventListener('submit', handleOtpVerification);
    }

    // Close OTP popup
    const closeOtpPopup = document.getElementById('closeOtpPopup');
    if (closeOtpPopup) {
        closeOtpPopup.addEventListener('click', hideOtpPopup);
    }

    // Resend OTP
    const resendOtp = document.getElementById('resendOtp');
    if (resendOtp) {
        resendOtp.addEventListener('click', handleResendOtp);
    }


    // Portal Switcher Link Logic (Directly inline)
    const portalSwitchLink = document.getElementById('portalSwitchLink');
    if (portalSwitchLink) {
        console.log('Portal switch link found, attaching listener');
        portalSwitchLink.addEventListener('click', function (e) {
            e.preventDefault();
            console.log("Portal link clicked!");

            const loginPortalInput = document.getElementById('loginPortal');
            const formTitle = document.querySelector('.form-title');
            const formSubtitle = document.querySelector('.form-subtitle');

            if (!loginPortalInput) {
                console.error("Login portal input not found!");
                return;
            }

            // Toggle state
            const isCurrentlyWorkforce = loginPortalInput.value === 'workforce';
            const newPortal = isCurrentlyWorkforce ? 'ess' : 'workforce';
            console.log(`Switching to: ${newPortal}`);

            // Update input
            loginPortalInput.value = newPortal;

            // Update UI
            if (newPortal === 'ess') {
                if (formTitle) formTitle.textContent = 'Employee Portal';
                if (formSubtitle) formSubtitle.textContent = 'Sign in to access self-service features';
                this.textContent = 'Switch to Workforce System';
            } else {
                if (formTitle) formTitle.textContent = 'Welcome back';
                if (formSubtitle) formSubtitle.textContent = 'Sign in to continue to your account';
                this.textContent = 'Switch to Employee Portal';
            }
        });
    } else {
        console.error('Portal switch link NOT found in DOM');
    }
});



