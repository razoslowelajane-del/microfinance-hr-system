document.addEventListener("DOMContentLoaded", () => {
    const lucide = window.lucide;
    const body = document.body;
    const themeToggle = document.getElementById("themeToggle");
    const sidebarToggle = document.getElementById("sidebarToggle");
    const sidebar = document.getElementById("sidebar");
    const mobileMenuBtn = document.getElementById("mobileMenuBtn");

    // 1. Theme Logic
    const savedTheme = localStorage.getItem("theme");
    if (savedTheme === "dark") body.classList.add("dark-mode");

    themeToggle.addEventListener("click", () => {
        body.classList.toggle("dark-mode");
        localStorage.setItem("theme", body.classList.contains("dark-mode") ? "dark" : "light");
    });

    // 2. Sidebar & Mobile Logic
    sidebarToggle.addEventListener("click", () => {
        sidebar.classList.toggle("collapsed");
        localStorage.setItem("sidebarCollapsed", sidebar.classList.contains("collapsed"));
    });

    if (localStorage.getItem("sidebarCollapsed") === "true") sidebar.classList.add("collapsed");

    mobileMenuBtn.addEventListener("click", () => sidebar.classList.toggle("mobile-open"));

    // 3. Submenu Logic
    document.querySelectorAll(".nav-item.has-submenu").forEach((item) => {
        item.addEventListener("click", (e) => {
            const module = item.getAttribute("data-module");
            const submenu = document.getElementById(`submenu-${module}`);
            submenu.classList.toggle("active");
            item.classList.toggle("active");
        });
    });

    // 4. Table Selection & Search Filter
    const selectAll = document.getElementById("selectAll");
    const rowCheckboxes = document.querySelectorAll(".row-checkbox");
    const searchInput = document.getElementById("roleSearch");
    const tableRows = document.querySelectorAll(".role-row-item");

    if (selectAll) {
        selectAll.addEventListener("change", () => {
            rowCheckboxes.forEach(cb => {
                if (cb.closest('tr').style.display !== 'none') {
                    cb.checked = selectAll.checked;
                }
            });
        });
    }

    if (searchInput) {
        searchInput.addEventListener("keyup", () => {
            const query = searchInput.value.toLowerCase();
            tableRows.forEach(row => {
                const text = row.innerText.toLowerCase();
                row.style.display = text.includes(query) ? "" : "none";
            });
        });
    }

    // 5. Modal Logic
    const modal = document.getElementById("marketModal");
    const marketBtns = document.querySelectorAll(".market-salary-btn");
    const closeModal = document.getElementById("closeModal");
    const confirmSync = document.getElementById("confirmSync");
    let currentRole = "";

    marketBtns.forEach(btn => {
        btn.addEventListener("click", (e) => {
            const row = e.target.closest("tr");
            currentRole = row.querySelector(".client-name").innerText;
            document.getElementById("modalTitle").innerText = `Sync ${currentRole}`;
            modal.style.display = "flex";
        });
    });

    if (closeModal) closeModal.addEventListener("click", () => modal.style.display = "none");
    if (confirmSync) {
        confirmSync.addEventListener("click", () => {
            alert(`Success: ${currentRole} queued for analysis.`);
            modal.style.display = "none";
        });
    }

    // 6. Packet Review Modal Logic
    const employeeModal = document.getElementById("employeeModal");
    const reviewPacketBtns = document.querySelectorAll(".make-master-file-btn");
    console.log("Packet Review Logic Loaded. Found buttons:", reviewPacketBtns.length);

    const closeEmployeeModal = document.getElementById("btnCloseModal");
    const btnCreateMaster = document.getElementById("btnCreateMaster");
    const btnReportMissing = document.getElementById("btnReportMissing");

    // Mock Data for "Review Packet"
    const mockCandidates = {
        "Alex Smith": {
            email: "alex.smith@example.com",
            phone: "+1 (555) 0123-4567",
            address: "123 Main St, Springfield",
            start: "Oct 15, 2024",
            dept: "Engineering",
            pos: "Senior Developer",
            manager: "Sarah Connor",
            salary: "$120,000 / Year",
            pay: "Salaried",
            contract: "Full-Time / Permanent"
        },
        "Mary Johnson": {
            email: "mary.j@example.com",
            phone: "+1 (555) 9876-5432",
            address: "456 Oak Ave, Metropolis",
            start: "Oct 20, 2024",
            dept: "Marketing",
            pos: "Marketing Specialist",
            manager: "Don Draper",
            salary: "$85,000 / Year",
            pay: "Salaried",
            contract: "Full-Time / Permanent"
        },
        "David Kim": {
            email: "david.kim@example.com",
            phone: "+1 (555) 555-0199",
            address: "789 Pine Ln, Gotham",
            start: "Oct 01, 2024",
            dept: "Finance",
            pos: "Analyst",
            manager: "Bruce Wayne",
            salary: "$95,000 / Year",
            pay: "Salaried",
            contract: "Contract (1 Year)"
        }
    };

    // Close modal function
    const closeEmpModal = () => {
        if (employeeModal) employeeModal.style.display = "none";
    };

    // Open modal on button click
    reviewPacketBtns.forEach(btn => {
        btn.addEventListener("click", (e) => {
            console.log("Review Packet Clicked:", btn);
            const name = btn.getAttribute("data-name");
            const data = mockCandidates[name] || mockCandidates["Alex Smith"]; // Fallback

            // Populate Modal Fields
            const titleEl = document.getElementById("modalEmployeeName");
            if (titleEl) titleEl.innerText = `New Hire Packet Details: ${name}`;

            // Helper to safely set text
            const setTxt = (sel, txt) => {
                const el = employeeModal.querySelector(sel);
                if (el) el.innerText = txt;
            };

            if (employeeModal.querySelector(".modal-val-name")) {
                setTxt(".modal-val-name", name);
                setTxt(".modal-val-email", data.email);
                setTxt(".modal-val-phone", data.phone);
                setTxt(".modal-val-address", data.address);
                setTxt(".modal-val-date", data.start);
                setTxt(".modal-val-dept", data.dept);
                setTxt(".modal-val-pos", data.pos);
                setTxt(".modal-val-manager", data.manager);
                setTxt(".modal-val-salary", data.salary);
                setTxt(".modal-val-pay", data.pay);
                setTxt(".modal-val-contract", data.contract);
            }

            // Show modal
            employeeModal.style.display = "flex";
        });
    });

    // Close buttons
    if (closeEmployeeModal) {
        closeEmployeeModal.addEventListener("click", closeEmpModal);
    }

    // Action buttons
    if (btnCreateMaster) {
        btnCreateMaster.addEventListener("click", () => {
            // Visual feedback
            const originalText = btnCreateMaster.innerHTML;
            btnCreateMaster.innerHTML = `<i data-lucide="loader" class="animate-spin" style="width:16px; margin-right:8px;"></i> Creating...`;
            if (window.lucide) window.lucide.createIcons();

            setTimeout(() => {
                Swal.fire({
                    title: "Success!",
                    text: "Master Record Created Successfully!",
                    icon: "success",
                    background: "#1a1a1a",
                    color: "#ffffff",
                    confirmButtonColor: "#2ca078"
                });
                btnCreateMaster.innerHTML = originalText;
                closeEmpModal();
            }, 1000);
        });
    }

    if (btnReportMissing) {
        btnReportMissing.addEventListener("click", async () => {
            const { value: reason } = await Swal.fire({
                title: "Report Missing Information",
                input: "text",
                inputLabel: "Please specify what information is missing",
                inputPlaceholder: "Enter details here...",
                showCancelButton: true,
                background: "#1a1a1a",
                color: "#ffffff",
                confirmButtonColor: "#2ca078",
                cancelButtonColor: "#d33"
            });

            if (reason) {
                Swal.fire({
                    title: "Report Sent!",
                    text: `Report sent to HR1: ${reason}`,
                    icon: "success",
                    background: "#1a1a1a",
                    color: "#ffffff",
                    confirmButtonColor: "#2ca078"
                });
                closeEmpModal();
            }
        });
    }

    // Close on outside click
    window.addEventListener("click", (e) => {
        if (e.target === employeeModal) {
            closeEmpModal();
        }
        if (e.target === modal) { // Existing market modal
            modal.style.display = "none";
        }
    });

    if (typeof lucide !== "undefined") lucide.createIcons();
});