document.addEventListener("DOMContentLoaded", () => {
    if (window.lucide) lucide.createIcons();

    const themeBtn = document.getElementById("themeToggle");
    if (themeBtn) {
        themeBtn.addEventListener("click", () => {
            document.documentElement.classList.toggle("dark-mode");
            document.body.classList.toggle("dark-mode");
        });
    }

    initClaimsModule();
});

function swalThemeOptions() {
    const isDark =
        document.documentElement.classList.contains("dark-mode") ||
        document.body.classList.contains("dark-mode");

    return isDark
        ? {
              background: "#1a1a1a",
              color: "#f9fafb",
              confirmButtonColor: "#2ca078",
              cancelButtonColor: "#6b7280"
          }
        : {
              confirmButtonColor: "#2ca078",
              cancelButtonColor: "#6b7280"
          };
}

let allRows = [];
let activeStatusTab = "APPROVED_BY_OFFICER";
let activeRow = null;

const tbody = document.getElementById("claimsTableBody");
const emptyState = document.getElementById("emptyState");

const countForApproval = document.getElementById("countForApproval");
const countApproved = document.getElementById("countApproved");
const countRejected = document.getElementById("countRejected");
const countPaid = document.getElementById("countPaid");

const tabs = document.querySelectorAll(".tab-btn");
const searchInput = document.getElementById("searchInput");
const globalSearch = document.getElementById("globalSearch");
const categoryFilter = document.getElementById("categoryFilter");
const fromDate = document.getElementById("fromDate");
const resetFiltersBtn = document.getElementById("resetFiltersBtn");
const refreshBtn = document.getElementById("refreshBtn");

const claimModal = document.getElementById("claimModal");
const closeModalBtn = document.getElementById("closeModalBtn");
const closeModalFooterBtn = document.getElementById("closeModalFooterBtn");
const approveBtn = document.getElementById("approveBtn");
const rejectBtn = document.getElementById("rejectBtn");
const modalActionButtons = document.getElementById("modalActionButtons");

function escapeHtml(value) {
    return String(value ?? "")
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

function formatMoney(value) {
    const num = Number(value || 0);
    return num.toLocaleString("en-PH", {
        style: "currency",
        currency: "PHP"
    });
}

function formatDate(dateStr) {
    if (!dateStr) return "-";
    const d = new Date(dateStr + "T00:00:00");
    return d.toLocaleDateString("en-US", {
        year: "numeric",
        month: "long",
        day: "numeric"
    });
}

function formatDateTime(dateStr) {
    if (!dateStr) return "-";
    const normalized = dateStr.replace(" ", "T");
    const d = new Date(normalized);
    return d.toLocaleString("en-US", {
        year: "numeric",
        month: "long",
        day: "numeric",
        hour: "numeric",
        minute: "2-digit"
    });
}

function badgeLabel(status) {
    switch (status) {
        case "APPROVED_BY_OFFICER":
            return "For Approval";
        case "APPROVED_BY_HR":
            return "Approved";
        case "REJECTED":
            return "Rejected";
        case "PAID":
            return "Paid";
        case "PENDING":
            return "Pending";
        case "CANCELLED":
            return "Cancelled";
        default:
            return status || "-";
    }
}

function statusClass(status) {
    switch (status) {
        case "APPROVED_BY_OFFICER":
            return "status-pending";
        case "APPROVED_BY_HR":
            return "status-approved";
        case "REJECTED":
            return "status-rejected";
        case "PAID":
            return "status-paid";
        default:
            return "status-cancelled";
    }
}

async function fetchClaimsData() {
    const res = await fetch("includes/claims_data.php", {
        method: "GET",
        headers: { Accept: "application/json" }
    });

    let data;
    try {
        data = await res.json();
    } catch (e) {
        throw new Error("Invalid server response.");
    }

    if (!res.ok || !data.ok) {
        throw new Error(data.message || "Failed to load claims data.");
    }

    return data;
}

function renderSummary(summary) {
    countForApproval.textContent = summary.for_approval ?? 0;
    countApproved.textContent = summary.approved ?? 0;
    countRejected.textContent = summary.rejected ?? 0;
    countPaid.textContent = summary.paid ?? 0;
}

function buildRow(row) {
    const tr = document.createElement("tr");

    tr.innerHTML = `
        <td>
            <div class="emp-name">${escapeHtml(row.EmployeeName || "-")}</div>
            <div class="subtext">${escapeHtml(row.EmployeeCode || "-")}</div>
        </td>
        <td>
            <strong>${escapeHtml(row.DepartmentName || "-")}</strong>
            <div class="subtext">${escapeHtml(row.PositionName || "-")}</div>
        </td>
        <td>
            <strong>${escapeHtml(row.Category || "-")}</strong>
            <div class="subtext">Officer: ${escapeHtml(row.OfficerApprovedByName || "-")}</div>
        </td>
        <td>
            <strong>${escapeHtml(formatMoney(row.Amount))}</strong>
        </td>
        <td>
            <strong>${escapeHtml(formatDate(row.ClaimDate))}</strong>
        </td>
        <td>
            <span class="status-badge ${statusClass(row.Status)}">
                ${escapeHtml(badgeLabel(row.Status))}
            </span>
        </td>
        <td>
            <strong>${escapeHtml(formatDate((row.CreatedAt || "").split(" ")[0] || ""))}</strong>
            <div class="subtext">${escapeHtml(formatDateTime(row.CreatedAt))}</div>
        </td>
        <td>
            <button class="btn-view" type="button">View</button>
        </td>
    `;

    tr.querySelector(".btn-view").addEventListener("click", () => openModal(row));
    return tr;
}

function passesDateFilter(rowDate, fromVal) {
    if (!fromVal) return true;
    const d = new Date((rowDate || "") + "T00:00:00");
    const from = new Date(fromVal + "T00:00:00");
    return d >= from;
}

function renderTable() {
    tbody.innerHTML = "";

    let rows = [...allRows];
    const searchVal = (searchInput.value || globalSearch.value || "").trim().toLowerCase();
    const categoryVal = categoryFilter.value;
    const fromVal = fromDate.value;

    rows = rows.filter((row) => {
        const matchesTab = activeStatusTab === "ALL" ? true : row.Status === activeStatusTab;

        const haystack = [
            row.EmployeeName,
            row.EmployeeCode,
            row.DepartmentName,
            row.PositionName,
            row.Category,
            row.Description,
            row.OfficerApprovedByName
        ].join(" ").toLowerCase();

        const matchesSearch = !searchVal || haystack.includes(searchVal);
        const matchesCategory = categoryVal === "ALL" ? true : row.Category === categoryVal;
        const matchesDate = passesDateFilter(row.ClaimDate, fromVal);

        return matchesTab && matchesSearch && matchesCategory && matchesDate;
    });

    rows.forEach((row) => tbody.appendChild(buildRow(row)));

    emptyState.style.display = rows.length === 0 ? "block" : "none";

    if (window.lucide) lucide.createIcons();
}

function openModal(data) {
    activeRow = data;

    document.getElementById("mEmployeeName").textContent = data.EmployeeName || "-";
    document.getElementById("mEmployeeCode").textContent = data.EmployeeCode || "-";
    document.getElementById("mDepartmentName").textContent = data.DepartmentName || "-";
    document.getElementById("mPositionName").textContent = data.PositionName || "-";

    document.getElementById("mCategory").textContent = data.Category || "-";
    document.getElementById("mAmount").textContent = formatMoney(data.Amount);
    document.getElementById("mClaimDate").textContent = formatDate(data.ClaimDate);
    document.getElementById("mStatusText").textContent = badgeLabel(data.Status);
    document.getElementById("mDescription").textContent = data.Description || "No description provided.";

    const officerNotesText = [
        data.OfficerApprovedByName ? `Approved by: ${data.OfficerApprovedByName}` : "Approved by: -",
        data.OfficerNotes ? `Notes: ${data.OfficerNotes}` : "Notes: No officer notes."
    ].join("\n");

    document.getElementById("mOfficerNotes").textContent = officerNotesText;
    document.getElementById("hrRemarks").value = data.HRNotes || "";

    const proofBox = document.getElementById("mProofBox");
    if (data.ReceiptImage && data.ReceiptImage.trim() !== "") {
        const imgSrc = "../../" + data.ReceiptImage.replace(/^\/+/, "");
        proofBox.innerHTML = `
            <div style="display:grid; gap:12px;">
                <img src="${encodeURI(imgSrc)}" alt="Claim proof"
                     style="width:100%; max-height:320px; object-fit:contain; border-radius:10px; border:1px solid var(--border-color); background:var(--surface);">
                <div style="display:flex; justify-content:flex-end;">
                    <a class="btn btn-soft" href="${encodeURI(imgSrc)}" target="_blank">Open Proof</a>
                </div>
            </div>
        `;
    } else {
        proofBox.innerHTML = `<span class="attachment-empty">No proof uploaded</span>`;
    }

    const canAct = data.Status === "APPROVED_BY_OFFICER";
    modalActionButtons.style.display = canAct ? "flex" : "none";
    document.getElementById("hrRemarks").disabled = !canAct;

    claimModal.classList.add("show");
    document.body.style.overflow = "hidden";

    if (window.lucide) lucide.createIcons();
}

function closeModal() {
    claimModal.classList.remove("show");
    document.body.style.overflow = "";
    activeRow = null;
}

async function updateClaimStatus(actionType) {
    if (!activeRow?.ClaimID) return;

    const remarks = document.getElementById("hrRemarks").value.trim();

    const formData = new FormData();
    formData.append("claim_id", activeRow.ClaimID);
    formData.append("action", actionType);
    formData.append("remarks", remarks);

    const res = await fetch("includes/claims_data.php", {
        method: "POST",
        body: formData
    });

    let data;
    try {
        data = await res.json();
    } catch (e) {
        throw new Error("Invalid server response.");
    }

    if (!res.ok || !data.ok) {
        throw new Error(data.message || "Failed to update claim.");
    }

    return data;
}

async function loadClaimsData() {
    const data = await fetchClaimsData();
    allRows = Array.isArray(data.rows) ? data.rows : [];
    renderSummary(data.summary || {});
    renderTable();
}

function bindEvents() {
    tabs.forEach((tab) => {
        tab.addEventListener("click", () => {
            tabs.forEach((t) => t.classList.remove("active"));
            tab.classList.add("active");
            activeStatusTab = tab.dataset.status;
            renderTable();
        });
    });

    [searchInput, globalSearch].forEach((input) => {
        input.addEventListener("input", () => {
            if (input === globalSearch) searchInput.value = globalSearch.value;
            else globalSearch.value = searchInput.value;
            renderTable();
        });
    });

    categoryFilter.addEventListener("change", renderTable);
    fromDate.addEventListener("change", renderTable);

    resetFiltersBtn.addEventListener("click", () => {
        searchInput.value = "";
        globalSearch.value = "";
        categoryFilter.value = "ALL";
        fromDate.value = "";
        activeStatusTab = "APPROVED_BY_OFFICER";

        tabs.forEach((t) => t.classList.remove("active"));
        document.querySelector('.tab-btn[data-status="APPROVED_BY_OFFICER"]')?.classList.add("active");

        renderTable();
    });

    refreshBtn.addEventListener("click", async () => {
        try {
            await loadClaimsData();
        } catch (err) {
            Swal.fire({
                ...swalThemeOptions(),
                icon: "error",
                title: "Load failed",
                text: err.message
            });
        }
    });

    closeModalBtn.addEventListener("click", closeModal);
    closeModalFooterBtn.addEventListener("click", closeModal);

    claimModal.addEventListener("click", (e) => {
        if (e.target === claimModal) closeModal();
    });

    approveBtn.addEventListener("click", async () => {
        if (!activeRow) return;

        const result = await Swal.fire({
            ...swalThemeOptions(),
            icon: "question",
            title: "Approve claim?",
            text: "This will approve the claim at HR Manager level.",
            showCancelButton: true,
            confirmButtonText: "Yes, approve",
            cancelButtonText: "Cancel"
        });

        if (!result.isConfirmed) return;

        try {
            await updateClaimStatus("approve");

            await Swal.fire({
                ...swalThemeOptions(),
                icon: "success",
                title: "Approved",
                text: "Claim has been approved by HR Manager."
            });

            closeModal();
            await loadClaimsData();
        } catch (err) {
            Swal.fire({
                ...swalThemeOptions(),
                icon: "error",
                title: "Update failed",
                text: err.message
            });
        }
    });

    rejectBtn.addEventListener("click", async () => {
        if (!activeRow) return;

        const result = await Swal.fire({
            ...swalThemeOptions(),
            icon: "warning",
            title: "Reject claim?",
            text: "This action cannot be undone.",
            showCancelButton: true,
            confirmButtonText: "Yes, reject",
            cancelButtonText: "Cancel"
        });

        if (!result.isConfirmed) return;

        try {
            await updateClaimStatus("reject");

            await Swal.fire({
                ...swalThemeOptions(),
                icon: "success",
                title: "Rejected",
                text: "Claim has been rejected by HR Manager."
            });

            closeModal();
            await loadClaimsData();
        } catch (err) {
            Swal.fire({
                ...swalThemeOptions(),
                icon: "error",
                title: "Update failed",
                text: err.message
            });
        }
    });
}

async function initClaimsModule() {
    bindEvents();

    try {
        await loadClaimsData();
    } catch (err) {
        Swal.fire({
            ...swalThemeOptions(),
            icon: "error",
            title: "Load failed",
            text: err.message
        });
    }
}