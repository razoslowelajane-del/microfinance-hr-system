document.addEventListener("DOMContentLoaded", () => {
    if (window.lucide) lucide.createIcons();

    const themeBtn = document.getElementById("themeToggle");
    if (themeBtn) {
        themeBtn.addEventListener("click", () => {
            document.documentElement.classList.toggle("dark-mode");
            document.body.classList.toggle("dark-mode");
        });
    }

    initLeaveModule();
});

function swalThemeOptions() {
    const isDark = document.documentElement.classList.contains("dark-mode") || document.body.classList.contains("dark-mode");
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
let activeTab = "ALL";
let activeRow = null;

const tbody = document.getElementById("leaveTableBody");
const emptyState = document.getElementById("emptyState");

const countPending = document.getElementById("countPending");
const countSent = document.getElementById("countSent");
const countApproved = document.getElementById("countApproved");
const countRejected = document.getElementById("countRejected");

const tabs = document.querySelectorAll(".tab-btn");
const searchInput = document.getElementById("searchInput");
const globalSearch = document.getElementById("globalSearch");
const leaveTypeFilter = document.getElementById("leaveTypeFilter");
const sortOrder = document.getElementById("sortOrder");
const fromDate = document.getElementById("fromDate");
const toDate = document.getElementById("toDate");
const resetFiltersBtn = document.getElementById("resetFiltersBtn");
const refreshPreviewBtn = document.getElementById("refreshPreviewBtn");

const leaveModal = document.getElementById("leaveModal");
const closeModalBtn = document.getElementById("closeModalBtn");
const closeModalFooterBtn = document.getElementById("closeModalFooterBtn");
const approveBtn = document.getElementById("approveBtn");
const rejectBtn = document.getElementById("rejectBtn");
const decisionActions = document.getElementById("decisionActions");
const decisionLockedNote = document.getElementById("decisionLockedNote");

function badgeLabel(status) {
    switch (status) {
        case "PENDING":
            return "Pending";
        case "APPROVED_BY_OFFICER":
            return "For HR Review";
        case "APPROVED_BY_HR":
            return "Approved";
        case "REJECTED":
            return "Rejected";
        case "CANCELLED":
            return "Cancelled";
        default:
            return status || "-";
    }
}

function statusClass(status) {
    switch (status) {
        case "PENDING":
            return "status-pending";
        case "APPROVED_BY_OFFICER":
            return "status-sent";
        case "APPROVED_BY_HR":
            return "status-approved";
        case "REJECTED":
            return "status-rejected";
        default:
            return "status-cancelled";
    }
}

function escapeHtml(value) {
    return String(value ?? "")
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
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

async function fetchLeaveData() {
    const res = await fetch("includes/leave_data.php", {
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
        throw new Error(data.message || "Failed to load leave data.");
    }

    return data;
}

function renderSummary(summary) {
    countPending.textContent = summary.pending ?? 0;
    countSent.textContent = summary.sent ?? 0;
    countApproved.textContent = summary.approved ?? 0;
    countRejected.textContent = summary.rejected ?? 0;
}

function buildRow(row) {
    const tr = document.createElement("tr");
    tr.dataset.id = row.LeaveRequestID;
    tr.dataset.created = String(Date.parse(row.CreatedAt || "") || 0);
    tr.dataset.name = (row.EmployeeName || "").toLowerCase();
    tr.dataset.code = (row.EmployeeCode || "").toLowerCase();
    tr.dataset.status = row.Status || "";
    tr.dataset.leavetype = row.LeaveName || "";
    tr.dataset.start = row.StartDate || "";
    tr.dataset.end = row.EndDate || "";
    tr.dataset.json = JSON.stringify(row);

    const isPaid = Number(row.IsPaid) === 1;

    tr.innerHTML = `
        <td>
            <div class="emp-name">${escapeHtml(row.EmployeeName || "-")}</div>
            <div class="subtext">${escapeHtml(row.EmployeeCode || "-")}</div>
        </td>

        <td>
            <div class="type-stack">
                <strong>${escapeHtml(row.LeaveName || "-")}</strong>
                <span class="mini-badge ${isPaid ? "mini-paid" : "mini-unpaid"}">
                    ${isPaid ? "Paid" : "Unpaid"}
                </span>
            </div>
        </td>

        <td>
            <div>${escapeHtml(formatDate(row.StartDate))}</div>
            <div class="subtext">to ${escapeHtml(formatDate(row.EndDate))}</div>
        </td>

        <td>
            <strong>${Number(row.TotalDays || 0).toFixed(2)}</strong>
            <div class="subtext">${Number(row.TotalDays || 0) === 1 ? "day" : "days"}</div>
        </td>

        <td>
            ${
                isPaid
                    ? `<strong>${Number(row.RemainingCredits || 0).toFixed(2)}</strong>
                       <div class="subtext">remaining credits</div>`
                    : `<strong>No credit needed</strong>
                       <div class="subtext">unpaid leave type</div>`
            }
        </td>

        <td>
    <strong>${escapeHtml(row.OfficerApprovedByName || "-")}</strong>
    <div class="subtext">${row.Status === "APPROVED_BY_OFFICER" || row.Status === "APPROVED_BY_HR" ? "Officer reviewed" : "Not yet endorsed"}</div>
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

        <td class="table-action">
            <button class="btn-view" type="button">View</button>
        </td>
    `;

    tr.querySelector(".btn-view").addEventListener("click", () => openModal(row));

    return tr;
}

function passesDateFilter(rowStart, rowEnd, fromVal, toVal) {
    if (!fromVal && !toVal) return true;

    const start = new Date(rowStart + "T00:00:00");
    const end = new Date(rowEnd + "T00:00:00");

    if (fromVal && end < new Date(fromVal + "T00:00:00")) return false;
    if (toVal && start > new Date(toVal + "T23:59:59")) return false;

    return true;
}

function renderTable() {
    tbody.innerHTML = "";

    let rows = [...allRows];

    const searchVal = (searchInput.value || globalSearch.value || "").trim().toLowerCase();
    const leaveTypeVal = leaveTypeFilter.value;
    const fromVal = fromDate.value;
    const toVal = toDate.value;
    const order = sortOrder.value;

    rows = rows.filter((row) => {
        const name = (row.EmployeeName || "").toLowerCase();
        const code = (row.EmployeeCode || "").toLowerCase();

        const matchesTab = activeTab === "ALL" || row.Status === activeTab;
        const matchesSearch = !searchVal || name.includes(searchVal) || code.includes(searchVal);
        const matchesType = leaveTypeVal === "ALL" || row.LeaveName === leaveTypeVal;
        const matchesDate = passesDateFilter(row.StartDate || "", row.EndDate || "", fromVal, toVal);

        return matchesTab && matchesSearch && matchesType && matchesDate;
    });

    rows.sort((a, b) => {
        const timeA = Date.parse(a.CreatedAt || "") || 0;
        const timeB = Date.parse(b.CreatedAt || "") || 0;
        return order === "newest" ? timeB - timeA : timeA - timeB;
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

    document.getElementById("mLeaveType").textContent = data.LeaveName || "-";
    document.getElementById("mLeaveCategory").textContent = Number(data.IsPaid) === 1 ? "Paid Leave" : "Unpaid Leave";
    document.getElementById("mStartDate").textContent = formatDate(data.StartDate);
    document.getElementById("mEndDate").textContent = formatDate(data.EndDate);
    document.getElementById("mTotalDays").textContent = `${parseFloat(data.TotalDays || 0)} ${parseFloat(data.TotalDays || 0) === 1 ? "day" : "days"}`;
    document.getElementById("mCreatedAt").textContent = formatDateTime(data.CreatedAt);
    document.getElementById("mReason").textContent = data.Reason || "No reason provided.";
   document.getElementById("mOfficerApprovedBy").textContent =
    data.OfficerApprovedByName || "Not yet approved by officer";

document.getElementById("mOfficerRemarksText").textContent =
    data.OfficerNotes || "No officer remarks provided.";
    document.getElementById("hrRemarks").value = data.HRNotes || "";

    const paidCreditsGrid = document.getElementById("paidCreditsGrid");
    const unpaidNote = document.getElementById("unpaidNote");

    if (Number(data.IsPaid) === 1) {
        paidCreditsGrid.style.display = "grid";
        unpaidNote.style.display = "none";
        document.getElementById("mTotalCredits").textContent = Number(data.TotalCredits || 0).toFixed(2);
        document.getElementById("mUsedCredits").textContent = Number(data.UsedCredits || 0).toFixed(2);
        document.getElementById("mRemainingCredits").textContent = Number(data.RemainingCredits || 0).toFixed(2);
    } else {
        paidCreditsGrid.style.display = "none";
        unpaidNote.style.display = "block";
    }

    const attachmentBox = document.getElementById("mAttachmentBox");
    if (data.AttachmentPath && data.AttachmentPath.trim() !== "") {
        attachmentBox.innerHTML = `
            <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
                <div>
                    <div style="font-weight:700;margin-bottom:4px;">${escapeHtml(data.AttachmentPath)}</div>
                    <div class="subtext">Attachment uploaded by employee</div>
                </div>
                <a class="btn btn-soft" href="../../${encodeURI(data.AttachmentPath)}" target="_blank">View</a>
            </div>
        `;
    } else {
        attachmentBox.innerHTML = `<span class="attachment-empty">No attachment uploaded</span>`;
    }

    const canAct = data.Status === "APPROVED_BY_OFFICER";
    const remaining = Number(data.RemainingCredits || 0);
    const requested = Number(data.TotalDays || 0);
    const isPaid = Number(data.IsPaid) === 1;
    const insufficientCredits = canAct && isPaid && remaining < requested;

    decisionActions.style.display = canAct ? "flex" : "none";
    decisionLockedNote.style.display = canAct ? "none" : "block";

    approveBtn.disabled = insufficientCredits;
    approveBtn.style.opacity = insufficientCredits ? ".6" : "1";
    approveBtn.style.cursor = insufficientCredits ? "not-allowed" : "pointer";

    rejectBtn.disabled = false;
    rejectBtn.style.opacity = "1";
    rejectBtn.style.cursor = "pointer";

    leaveModal.classList.add("show");
    document.body.style.overflow = "hidden";

    if (window.lucide) lucide.createIcons();
}

function closeModal() {
    leaveModal.classList.remove("show");
    document.body.style.overflow = "";
    activeRow = null;
}

async function updateLeaveStatus(actionType) {
    if (!activeRow?.LeaveRequestID) return;

    const remarks = document.getElementById("hrRemarks").value.trim();

    const formData = new FormData();
    formData.append("leave_request_id", activeRow.LeaveRequestID);
    formData.append("action", actionType);
    formData.append("remarks", remarks);

    const res = await fetch("includes/leave_data.php", {
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
        throw new Error(data.message || "Failed to update leave request.");
    }

    return data;
}

async function loadLeaveData() {
    const data = await fetchLeaveData();
    allRows = Array.isArray(data.rows) ? data.rows : [];
    renderSummary(data.summary || {});
    renderTable();
}

function bindEvents() {
    tabs.forEach((tab) => {
        tab.addEventListener("click", () => {
            tabs.forEach((t) => t.classList.remove("active"));
            tab.classList.add("active");
            activeTab = tab.dataset.tab;
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

    leaveTypeFilter.addEventListener("change", renderTable);
    sortOrder.addEventListener("change", renderTable);
    fromDate.addEventListener("change", renderTable);
    toDate.addEventListener("change", renderTable);

    resetFiltersBtn.addEventListener("click", () => {
        searchInput.value = "";
        globalSearch.value = "";
        leaveTypeFilter.value = "ALL";
        sortOrder.value = "newest";
        fromDate.value = "";
        toDate.value = "";
        activeTab = "ALL";

        tabs.forEach((t) => t.classList.remove("active"));
        document.querySelector('.tab-btn[data-tab="ALL"]').classList.add("active");

        renderTable();
    });

    refreshPreviewBtn.addEventListener("click", async () => {
        try {
            await loadLeaveData();
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

    leaveModal.addEventListener("click", (e) => {
        if (e.target === leaveModal) closeModal();
    });

    approveBtn.addEventListener("click", async () => {
        if (approveBtn.disabled || !activeRow) return;

        const result = await Swal.fire({
            ...swalThemeOptions(),
            icon: "question",
            title: "Approve leave request?",
            text: "This will fully approve the leave request.",
            showCancelButton: true,
            confirmButtonText: "Yes, approve",
            cancelButtonText: "Cancel"
        });

        if (!result.isConfirmed) return;

        try {
            await updateLeaveStatus("approve");

            await Swal.fire({
                ...swalThemeOptions(),
                icon: "success",
                title: "Approved",
                text: "Request has been fully approved by HR."
            });

            closeModal();
            await loadLeaveData();
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
            title: "Reject leave request?",
            text: "This action cannot be undone.",
            showCancelButton: true,
            confirmButtonText: "Yes, reject",
            cancelButtonText: "Cancel"
        });

        if (!result.isConfirmed) return;

        try {
            await updateLeaveStatus("reject");

            await Swal.fire({
                ...swalThemeOptions(),
                icon: "success",
                title: "Rejected",
                text: "Request has been rejected by HR."
            });

            closeModal();
            await loadLeaveData();
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

async function initLeaveModule() {
    bindEvents();

    try {
        await loadLeaveData();
    } catch (err) {
        Swal.fire({
            ...swalThemeOptions(),
            icon: "error",
            title: "Load failed",
            text: err.message
        });
    }
}