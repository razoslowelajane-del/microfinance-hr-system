/**
 * User Account Management - Fix Version
 * Handles modal, theme toggle, sidebar, and account actions (Edit/Delete)
 * Uses URLSearchParams to avoid server-side multipart/form-data issues
 */

// Fallback for SweetAlert2
if (typeof window.Swal === 'undefined') {
    window.Swal = {
        fire: (opts) => {
            alert((opts.title || '') + '\n' + (opts.text || ''));
            return Promise.resolve({ isConfirmed: true });
        },
        showLoading: () => { },
        close: () => { }
    };
}

// Fallback for Lucide icons
if (typeof window.lucide === 'undefined') {
    window.lucide = { createIcons: () => { } };
}

function initUserAccount() {
    const body = document.body;
    const lucide = window.lucide;

    // =====================
    // 1. THEME TOGGLE
    // =====================
    const themeToggle = document.getElementById("themeToggle");
    if (themeToggle) {
        const savedTheme = localStorage.getItem("theme");
        if (savedTheme === "dark") body.classList.add("dark-mode");

        themeToggle.addEventListener("click", () => {
            body.classList.toggle("dark-mode");
            localStorage.setItem("theme", body.classList.contains("dark-mode") ? "dark" : "light");
        });
    }

    // =====================
    // 2. SIDEBAR TOGGLE
    // =====================
    const sidebarToggle = document.getElementById("sidebarToggle");
    const sidebar = document.getElementById("sidebar");
    const mobileMenuBtn = document.getElementById("mobileMenuBtn");

    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener("click", () => {
            sidebar.classList.toggle("collapsed");
            localStorage.setItem("sidebarCollapsed", sidebar.classList.contains("collapsed"));
        });

        if (localStorage.getItem("sidebarCollapsed") === "true") {
            sidebar.classList.add("collapsed");
        }
    }

    if (mobileMenuBtn && sidebar) {
        mobileMenuBtn.addEventListener("click", () => sidebar.classList.toggle("mobile-open"));
    }

    // =====================
    // 3. SUBMENU TOGGLE
    // =====================
    document.querySelectorAll(".nav-item.has-submenu").forEach((item) => {
        item.addEventListener("click", () => {
            const module = item.getAttribute("data-module");
            const submenu = document.getElementById(`submenu-${module}`);
            if (submenu) {
                submenu.classList.toggle("active");
                item.classList.toggle("active");
            }
        });
    });

    // =====================
    // 4. MODAL MANAGEMENT
    // =====================
    const modal = document.getElementById("addUserModal");
    const addUserBtn = document.getElementById("addUserBtn");
    const closeModalBtn = document.getElementById("closeModalBtn");
    const cancelCreate = document.getElementById("cancelCreate");
    const createUserForm = document.getElementById("createUserForm");

    // Helper to open modal
    const openModal = (shouldReset = true) => {
        if (!modal || !createUserForm) {
            console.error("Modal or form not found");
            return;
        }
        if (shouldReset) {
            createUserForm.reset();
            const accIdInput = document.getElementById("accountId");
            if (accIdInput) accIdInput.value = "";
        }
        modal.style.display = "flex";
        modal.classList.add("show");
        modal.setAttribute("aria-hidden", "false");
        console.log("Modal opened (Fix)");
    };

    // Helper to close modal
    const closeModal = () => {
        if (!modal || !createUserForm) return;
        modal.style.display = "none";
        modal.classList.remove("show");
        modal.setAttribute("aria-hidden", "true");
        createUserForm.reset();
        const accIdInput = document.getElementById("accountId");
        if (accIdInput) accIdInput.value = "";
    };

    // Global fallback for inline onclick
    window.openAddAccountModal = openModal;

    // Close buttons
    if (closeModalBtn) closeModalBtn.addEventListener("click", closeModal);
    if (cancelCreate) cancelCreate.addEventListener("click", closeModal);

    // Close when clicking outside modal
    if (modal) {
        modal.addEventListener("click", (e) => {
            if (e.target === modal) closeModal();
        });
    }

    // =====================
    // 5. FORM SUBMISSION (FIXED)
    // =====================
    if (createUserForm) {
        // Remove existing listeners by cloning (if any)
        // Note: We don't need to clone if we replace the file, but just in case of double-loading

        createUserForm.addEventListener("submit", async (e) => {
            e.preventDefault();

            const username = document.getElementById("username").value.trim();
            const email = document.getElementById("email").value.trim();
            const password = document.getElementById("password").value;
            const confirmPassword = document.getElementById("confirmPassword").value;
            const rolesSelect = document.getElementById("roles");
            const accountStatus = document.getElementById("accountStatus").value;

            const accountId = document.getElementById("accountId").value;
            const isEdit = !!accountId;
            console.log("Form Submit (Fix): AccountID:", accountId, "isEdit:", isEdit);

            // Validate passwords
            if ((!isEdit || password) && password !== confirmPassword) {
                await Swal.fire({
                    icon: "error",
                    title: "Password Mismatch",
                    text: "Passwords do not match",
                    confirmButtonColor: "#2ca078"
                });
                return;
            }

            // Validate roles
            const roles = Array.from(rolesSelect.selectedOptions).map(option => option.value);
            if (roles.length === 0) {
                await Swal.fire({
                    icon: "error",
                    title: "Roles Required",
                    text: "Please select at least one role",
                    confirmButtonColor: "#2ca078"
                });
                return;
            }

            // Show loading
            Swal.fire({
                title: isEdit ? "Updating Account..." : "Creating Account...",
                text: "Please wait",
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => Swal.showLoading()
            });

            try {
                // Use URLSearchParams instead of FormData
                const params = new URLSearchParams();

                const actionType = isEdit ? "update_account" : "add_account";
                params.append("action", actionType);

                if (isEdit) {
                    params.append("account_id", accountId);
                }

                params.append("username", username);
                params.append("email", email);
                if (password) {
                    params.append("password", password);
                    params.append("confirm_password", confirmPassword);
                }
                params.append("account_status", accountStatus);
                roles.forEach(roleId => {
                    params.append("roles[]", roleId);
                });

                console.log("Submitting with URLSearchParams:", params.toString());

                const response = await fetch("account_action.php", {
                    method: "POST",
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: params
                });

                console.log("Response Status:", response.status, response.statusText);
                const responseText = await response.text();
                console.log("Response Text:", responseText);

                let result;
                try {
                    result = JSON.parse(responseText);
                } catch (e) {
                    console.error("JSON Parse Error:", e);
                    throw new Error("Server returned invalid JSON: " + responseText.substring(0, 100));
                }

                if (result.success) {
                    closeModal(); // Close modal immediately on success
                    await Swal.fire({
                        icon: "success",
                        title: isEdit ? "Account Updated" : "Account Created",
                        text: isEdit ? "Account has been updated successfully" : "New account has been created successfully",
                        confirmButtonColor: "#2ca078"
                    });
                    location.reload();
                } else {
                    await Swal.fire({
                        icon: "error",
                        title: "Error",
                        text: result.message || "Failed to create/update account",
                        confirmButtonColor: "#2ca078"
                    });
                }
            } catch (error) {
                console.error("Form submission error:", error);
                await Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: "Something went wrong: " + error.message,
                    confirmButtonColor: "#2ca078"
                });
            } finally {
                // Always close loading spinner
                // invalid Swal.close() might be too aggressive if we just showed an error, 
                // but Swal.fire replacing the loading one is fine.
                // If success/error Swal was fired, we don't need to do anything.
                // But if an error occurred in the try block before Swal, we need to make sure loading is gone.
                // Actually Swal.fire automatically closes the previous one.
            }
        });
    }

    // =====================
    // 6. PASSWORD TOGGLE
    // =====================
    window.togglePassword = function (inputId) {
        const input = document.getElementById(inputId);
        if (!input) return;

        const parent = input.parentElement;
        const icon = parent?.querySelector(".eye-icon");

        if (input.type === "password") {
            input.type = "text";
            if (icon) icon.setAttribute("data-lucide", "eye-off");
        } else {
            input.type = "password";
            if (icon) icon.setAttribute("data-lucide", "eye");
        }

        lucide.createIcons();
    };

    // =====================
    // 7. EDIT & DELETE
    // =====================
    window.editUser = async function (accountId) {
        try {
            const response = await fetch(`account_action.php?action=get_account&account_id=${accountId}`);
            const result = await response.json();

            if (result.success) {
                const data = result.data;

                // Populate form
                document.getElementById("accountId").value = data.AccountID;
                document.getElementById("username").value = data.Username;
                document.getElementById("email").value = data.Email;
                document.getElementById("accountStatus").value = data.AccountStatus;

                const rolesSelect = document.getElementById("roles");
                Array.from(rolesSelect.options).forEach(option => {
                    option.selected = data.Roles.includes(parseInt(option.value));
                });

                document.querySelector(".modal-header h3").textContent = "Edit Account";
                document.querySelector("#createUserForm button[type='submit']").textContent = "Update Account";
                document.getElementById("password").required = false;
                document.getElementById("confirmPassword").required = false;

                openModal(false);
            } else {
                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: result.message || "Failed to fetch account details",
                    confirmButtonColor: "#2ca078"
                });
            }
        } catch (error) {
            console.error("Error fetching account:", error);
            Swal.fire({
                icon: "error",
                title: "Error",
                text: "An error occurred while fetching account details",
                confirmButtonColor: "#2ca078"
            });
        }
    };

    // Update openModal to reset to Add mode
    const openAddModal = () => {
        document.getElementById("accountId").value = "";
        document.querySelector(".modal-header h3").textContent = "Add New Account";
        document.querySelector("#createUserForm button[type='submit']").textContent = "Create Account";
        document.getElementById("password").required = true;
        document.getElementById("confirmPassword").required = true;
        openModal(true);
    };

    // Add button override
    if (addUserBtn) {
        const newBtn = addUserBtn.cloneNode(true);
        addUserBtn.parentNode.replaceChild(newBtn, addUserBtn);
        newBtn.addEventListener("click", openAddModal);
    }

    async function performDelete(id, username) {
        const confirmed = await Swal.fire({
            icon: "warning",
            title: "Delete Account",
            text: `Are you sure you want to delete the account "${username}"? This action cannot be undone.`,
            showCancelButton: true,
            confirmButtonColor: "#dc3545",
            cancelButtonColor: "#6c757d",
            confirmButtonText: "Delete",
            cancelButtonText: "Cancel"
        });

        if (!confirmed.isConfirmed) return;

        try {
            // Delete also needs to use URLSearchParams if server is strict
            const params = new URLSearchParams();
            params.append("action", "delete_account");
            params.append("account_id", id);

            const response = await fetch("account_action.php", {
                method: "POST",
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: params
            });

            const result = await response.json();

            if (result.success) {
                await Swal.fire({
                    icon: "success",
                    title: "Account Deleted",
                    text: "Account has been deleted successfully",
                    confirmButtonColor: "#2ca078"
                });
                location.reload();
            } else {
                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: result.message || "Failed to delete account",
                    confirmButtonColor: "#2ca078"
                });
            }
        } catch (error) {
            console.error("Delete error:", error);
            Swal.fire({
                icon: "error",
                title: "Error",
                text: "Something went wrong. Please try again.",
                confirmButtonColor: "#2ca078"
            });
        }
    }

    // Table delegated events
    const usersTable = document.getElementById("usersTable");
    if (usersTable) {
        usersTable.addEventListener("click", (e) => {
            const editBtn = e.target.closest(".btn-edit");
            if (editBtn) {
                const id = editBtn.getAttribute("data-account-id");
                editUser(parseInt(id, 10));
                return;
            }

            const delBtn = e.target.closest(".btn-delete");
            if (delBtn) {
                const id = delBtn.getAttribute("data-account-id");
                const username = delBtn.getAttribute("data-username");
                performDelete(id, username);
                return;
            }
        });
    }

    lucide.createIcons();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initUserAccount);
} else {
    initUserAccount();
}
