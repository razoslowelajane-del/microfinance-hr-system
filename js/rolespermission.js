/**
 * Roles & Permissions Management
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

function initRolesPermission() {
    const lucide = window.lucide;

    // Modal Elements
    const modal = document.getElementById("roleModal");
    const addRoleBtn = document.getElementById("addRoleBtn");
    const closeModalBtn = document.getElementById("closeModalBtn");
    const cancelRole = document.getElementById("cancelRole");
    const roleForm = document.getElementById("roleForm");

    // Theme Toggle
    const themeToggle = document.getElementById("themeToggle");
    const body = document.body;
    if (themeToggle) {
        themeToggle.addEventListener("click", () => {
            body.classList.toggle("dark-mode");
            localStorage.setItem("theme", body.classList.contains("dark-mode") ? "dark" : "light");
        });
        // Check saved theme
        if (localStorage.getItem("theme") === "dark") body.classList.add("dark-mode");
    }

    // Sidebar Toggle Logic
    const sidebarToggle = document.getElementById("sidebarToggle");
    const sidebar = document.getElementById("sidebar");
    const mobileMenuBtn = document.getElementById("mobileMenuBtn");

    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener("click", () => {
            sidebar.classList.toggle("collapsed");
            localStorage.setItem("sidebarCollapsed", sidebar.classList.contains("collapsed"));
        });

        // Restore state
        if (localStorage.getItem("sidebarCollapsed") === "true") {
            sidebar.classList.add("collapsed");
        }
    }

    if (mobileMenuBtn && sidebar) {
        mobileMenuBtn.addEventListener("click", () => sidebar.classList.toggle("mobile-open"));
    }

    // Modal Logic
    const openModal = (isEdit = false) => {
        if (!modal) return;

        modal.style.display = "flex";
        modal.classList.add("show");
        modal.setAttribute("aria-hidden", "false");

        const title = document.getElementById("modalTitle");
        const btn = document.getElementById("modalSubmitBtn");

        if (!isEdit) {
            if (roleForm) roleForm.reset();
            document.getElementById("roleId").value = "";
            title.textContent = "Add New Role";
            btn.textContent = "Save Role";
        } else {
            title.textContent = "Edit Role";
            btn.textContent = "Update Role";
        }
    };

    const closeModal = () => {
        if (!modal) return;
        modal.style.display = "none";
        modal.classList.remove("show");
        modal.setAttribute("aria-hidden", "true");
        if (roleForm) roleForm.reset();
    };

    if (addRoleBtn) addRoleBtn.addEventListener("click", () => openModal(false));
    if (closeModalBtn) closeModalBtn.addEventListener("click", closeModal);
    if (cancelRole) cancelRole.addEventListener("click", closeModal);

    // Close on outside click
    window.addEventListener("click", (e) => {
        if (e.target === modal) closeModal();
    });

    // Edit Role Function (Global to be called from HTML onclick)
    window.editRole = function (id, name, desc = "") {
        // We can fetch details or just set them if we pass them. 
        // For now, let's fetch to be safe and get description if it wasn't passed fully.
        // Actually, let's just use what we have or fetch if needed.
        // The HTML onclick passes ID and Name. Description might be missing or long.
        // Let's first open modal and populate what we know, then fetch the rest if needed.

        openModal(true);
        document.getElementById("roleId").value = id;
        document.getElementById("roleName").value = name;

        // Optional: Fetch full details if description is not passed or if we want fresh data
        fetch(`roles_action.php?action=get_role&role_id=${id}`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    document.getElementById("roleName").value = data.data.RoleName;
                    document.getElementById("roleDescription").value = data.data.Description || "";
                }
            })
            .catch(err => console.error("Error fetching role details:", err));
    };

    // Archive Role Function (Now behaves as Delete)
    window.archiveRole = async function (id) {
        const result = await Swal.fire({
            title: 'Are you sure?',
            text: "Do you want to delete this role?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        });

        if (result.isConfirmed) {
            try {
                const params = new URLSearchParams();
                params.append("action", "archive_role"); // Keeping action name same for backend compatibility
                params.append("role_id", id);

                const response = await fetch("roles_action.php", {
                    method: "POST",
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: params
                });

                const data = await response.json();

                if (data.success) {
                    await Swal.fire('Deleted!', 'Role has been deleted.', 'success');
                    location.reload();
                } else {
                    Swal.fire('Error!', data.message || 'Failed to archive role.', 'error');
                }
            } catch (error) {
                console.error(error);
                Swal.fire('Error!', 'Something went wrong.', 'error');
            }
        }
    };

    // Form Submission
    if (roleForm) {
        roleForm.addEventListener("submit", async (e) => {
            e.preventDefault();

            const roleId = document.getElementById("roleId").value;
            const roleName = document.getElementById("roleName").value;
            const description = document.getElementById("roleDescription").value;
            const isEdit = !!roleId;

            // Show loading
            Swal.fire({
                title: isEdit ? "Updating Role..." : "Saving Role...",
                text: "Please wait",
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            try {
                const params = new URLSearchParams();
                params.append("action", isEdit ? "update_role" : "add_role");
                if (isEdit) params.append("role_id", roleId);
                params.append("role_name", roleName);
                params.append("description", description);

                const response = await fetch("roles_action.php", {
                    method: "POST",
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: params
                });

                // Check for HTML response error
                const text = await response.text();
                let data;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    console.error("Invalid JSON:", text);
                    throw new Error("Server returned invalid response.");
                }

                if (data.success) {
                    closeModal(); // Close immediately
                    await Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: isEdit ? 'Role updated successfully' : 'Role created successfully',
                        confirmButtonColor: "#2ca078"
                    });
                    location.reload();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'Operation failed'
                    });
                }

            } catch (error) {
                console.error("Submit error:", error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error.message || 'An unexpected error occurred.'
                });
            }
        });
    }

    if (window.lucide) window.lucide.createIcons();
}

document.addEventListener('DOMContentLoaded', initRolesPermission);