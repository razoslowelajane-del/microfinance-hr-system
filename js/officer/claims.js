document.addEventListener("DOMContentLoaded", () => {
    if (window.lucide) lucide.createIcons();

    const tableBody = document.getElementById("claimsTableBody");
    const emptyState = document.getElementById("emptyState");
    const modal = document.getElementById("claimModal");

    const globalSearch = document.getElementById("globalSearch");
    const searchInput = document.getElementById("searchInput");
    const categoryFilter = document.getElementById("categoryFilter");
    const fromDate = document.getElementById("fromDate");
    const resetFiltersBtn = document.getElementById("resetFiltersBtn");
    const refreshBtn = document.getElementById("refreshBtn");

    const mEmployeeName = document.getElementById("mEmployeeName");
    const mEmployeeCode = document.getElementById("mEmployeeCode");
    const mDepartmentName = document.getElementById("mDepartmentName");
    const mPositionName = document.getElementById("mPositionName");
    const mCategory = document.getElementById("mCategory");
    const mAmount = document.getElementById("mAmount");
    const mClaimDate = document.getElementById("mClaimDate");
    const mStatusText = document.getElementById("mStatusText");
    const mDescription = document.getElementById("mDescription");
    const mProofBox = document.getElementById("mProofBox");
    const officerRemarks = document.getElementById("officerRemarks");
    const mHrNotes = document.getElementById("mHrNotes");

    const approveBtn = document.getElementById("approveBtn");
    const rejectBtn = document.getElementById("rejectBtn");
    const closeModalBtn = document.getElementById("closeModalBtn");
    const closeModalFooterBtn = document.getElementById("closeModalFooterBtn");
    const modalActionButtons = document.getElementById("modalActionButtons");

    const countPending = document.getElementById("countPending");
    const countSent = document.getElementById("countSent");
    const countRejected = document.getElementById("countRejected");
    const countAll = document.getElementById("countAll");

    let currentStatusFilter = "PENDING";
    let activeClaimId = null;
    let allClaims = [];
    let visibleClaims = [];

    function formatMoney(value) {
        return "₱" + Number(value || 0).toLocaleString("en-PH", {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function escapeHtml(text) {
        const div = document.createElement("div");
        div.textContent = text ?? "";
        return div.innerHTML;
    }

    function statusLabel(status) {
        switch (status) {
            case "PENDING": return "Pending";
            case "APPROVED_BY_OFFICER": return "Sent to HR";
            case "APPROVED_BY_HR": return "Approved by HR";
            case "REJECTED": return "Rejected";
            case "PAID": return "Paid";
            case "CANCELLED": return "Cancelled";
            default: return status;
        }
    }

    function statusClass(status) {
        switch (status) {
            case "PENDING": return "status-pending";
            case "APPROVED_BY_OFFICER": return "status-sent";
            case "APPROVED_BY_HR": return "status-approved";
            case "REJECTED": return "status-rejected";
            case "PAID": return "status-paid";
            default: return "status-cancelled";
        }
    }

    function categoryLabel(category) {
        switch (category) {
            case "GAS": return "Gas";
            case "LOAD": return "Load";
            case "TRAVEL": return "Travel";
            case "SUPPLIES": return "Supplies";
            case "OTHERS": return "Others";
            default: return category;
        }
    }

    function categoryClass(category) {
        switch (category) {
            case "GAS": return "cat-gas";
            case "LOAD": return "cat-load";
            case "TRAVEL": return "cat-travel";
            case "SUPPLIES": return "cat-supplies";
            default: return "cat-others";
        }
    }

    async function fetchClaims(status = "PENDING") {
        try {
            const res = await fetch("includes/claims_data.php?status=" + encodeURIComponent(status));
            const data = await res.json();

            if (!data.ok) {
                Swal.fire("Error", data.message || "Failed to load claims.", "error");
                return;
            }

            allClaims = data.claims || [];
            applyFilters();
            updateCounters();
        } catch (error) {
            console.error(error);
            Swal.fire("Error", "Unable to fetch claims.", "error");
        }
    }

    function renderTable(rows) {
        tableBody.innerHTML = "";

        if (!rows.length) {
            emptyState.style.display = "block";
            return;
        }

        emptyState.style.display = "none";

        rows.forEach(claim => {
            const tr = document.createElement("tr");
            tr.innerHTML = `
                <td>
                    <div class="emp-name">${escapeHtml(claim.EmployeeName)}</div>
                    <div class="subtext">${escapeHtml(claim.EmployeeCode || "-")}</div>
                </td>
                <td>
                    <div class="emp-name">${escapeHtml(claim.DepartmentName || "-")}</div>
                    <div class="subtext">${escapeHtml(claim.PositionName || "-")}</div>
                </td>
                <td>
                    <span class="category-badge ${categoryClass(claim.Category)}">
                        ${escapeHtml(categoryLabel(claim.Category))}
                    </span>
                </td>
                <td><strong>${formatMoney(claim.Amount)}</strong></td>
                <td>${escapeHtml(claim.ClaimDate || "-")}</td>
                <td>
                    <span class="status-badge ${statusClass(claim.Status)}">
                        ${escapeHtml(statusLabel(claim.Status))}
                    </span>
                </td>
                <td>${escapeHtml(claim.CreatedAt || "-")}</td>
                <td>
                    <button class="btn-view" type="button">View</button>
                </td>
            `;

            tr.querySelector(".btn-view").addEventListener("click", () => openModal(claim));
            tableBody.appendChild(tr);
        });

        if (window.lucide) lucide.createIcons();
    }

    function applyFilters() {
        const textA = (searchInput.value || "").trim().toLowerCase();
        const textB = (globalSearch.value || "").trim().toLowerCase();
        const searchText = textA || textB;
        const category = categoryFilter.value;
        const dateValue = fromDate.value;

        visibleClaims = allClaims.filter(claim => {
            const haystack = [
                claim.EmployeeName,
                claim.EmployeeCode,
                claim.DepartmentName,
                claim.PositionName,
                claim.Category,
                claim.Description
            ].join(" ").toLowerCase();

            const matchSearch = !searchText || haystack.includes(searchText);
            const matchCategory = category === "ALL" || claim.Category === category;
            const matchDate = !dateValue || claim.ClaimDate === dateValue;

            return matchSearch && matchCategory && matchDate;
        });

        renderTable(visibleClaims);
    }

    function updateCounters() {
        let pending = 0;
        let sent = 0;
        let rejected = 0;
        let total = 0;

        allClaims.forEach(claim => {
            total++;
            if (claim.Status === "PENDING") pending++;
            if (claim.Status === "APPROVED_BY_OFFICER") sent++;
            if (claim.Status === "REJECTED") rejected++;
        });

        countPending.textContent = pending;
        countSent.textContent = sent;
        countRejected.textContent = rejected;
        countAll.textContent = total;
    }

    function openModal(claim) {
        activeClaimId = claim.ClaimID;

        mEmployeeName.textContent = claim.EmployeeName || "-";
        mEmployeeCode.textContent = claim.EmployeeCode || "-";
        mDepartmentName.textContent = claim.DepartmentName || "-";
        mPositionName.textContent = claim.PositionName || "-";
        mCategory.textContent = categoryLabel(claim.Category);
        mAmount.textContent = formatMoney(claim.Amount);
        mClaimDate.textContent = claim.ClaimDate || "-";
        mStatusText.textContent = statusLabel(claim.Status);
        mDescription.textContent = claim.Description || "-";
        officerRemarks.value = claim.OfficerNotes || "";
        mHrNotes.textContent = claim.HRNotes?.trim() || "No HR notes yet.";

        if (claim.ReceiptImage) {
            mProofBox.innerHTML = `
                <div class="proof-preview">
                    <div>
                        <div style="font-weight:700;">${escapeHtml(claim.ReceiptImage)}</div>
                    </div>
                    <a class="btn btn-soft" href="../../${encodeURI(claim.ReceiptImage)}" target="_blank">View</a>
                </div>
            `;
        } else {
            mProofBox.innerHTML = `<span class="proof-empty">No proof uploaded</span>`;
        }

        if (claim.Status === "PENDING") {
            modalActionButtons.style.display = "flex";
        } else {
            modalActionButtons.style.display = "none";
        }

        modal.classList.add("show");
        if (window.lucide) lucide.createIcons();
    }

    async function submitAction(action) {
        if (!activeClaimId) return;

        const formData = new URLSearchParams();
        formData.append("action", action);
        formData.append("claim_id", activeClaimId);
        formData.append("notes", officerRemarks.value.trim());

        try {
            const res = await fetch("includes/claims_data.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8"
                },
                body: formData.toString()
            });

            const data = await res.json();

            if (!data.ok) {
                Swal.fire("Error", data.message || "Action failed.", "error");
                return;
            }

            Swal.fire("Success", data.message, "success");
            modal.classList.remove("show");
            fetchClaims(currentStatusFilter);
        } catch (error) {
            console.error(error);
            Swal.fire("Error", "Unable to process request.", "error");
        }
    }

    approveBtn?.addEventListener("click", async () => {
        const result = await Swal.fire({
            title: "Approve claim?",
            text: "This will send the claim to HR.",
            icon: "question",
            showCancelButton: true,
            confirmButtonText: "Approve"
        });

        if (result.isConfirmed) {
            submitAction("approve");
        }
    });

    rejectBtn?.addEventListener("click", async () => {
        const result = await Swal.fire({
            title: "Reject claim?",
            text: "This will reject the selected claim.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Reject"
        });

        if (result.isConfirmed) {
            submitAction("reject");
        }
    });

    closeModalBtn?.addEventListener("click", () => modal.classList.remove("show"));
    closeModalFooterBtn?.addEventListener("click", () => modal.classList.remove("show"));

    document.querySelectorAll(".tab-btn").forEach(btn => {
        btn.addEventListener("click", () => {
            document.querySelectorAll(".tab-btn").forEach(b => b.classList.remove("active"));
            btn.classList.add("active");
            currentStatusFilter = btn.dataset.status || "PENDING";
            fetchClaims(currentStatusFilter);
        });
    });

    [searchInput, globalSearch].forEach(input => {
        input?.addEventListener("input", applyFilters);
    });

    categoryFilter?.addEventListener("change", applyFilters);
    fromDate?.addEventListener("change", applyFilters);

    resetFiltersBtn?.addEventListener("click", () => {
        searchInput.value = "";
        globalSearch.value = "";
        categoryFilter.value = "ALL";
        fromDate.value = "";
        applyFilters();
    });

    refreshBtn?.addEventListener("click", () => {
        fetchClaims(currentStatusFilter);
    });

    fetchClaims(currentStatusFilter);
});