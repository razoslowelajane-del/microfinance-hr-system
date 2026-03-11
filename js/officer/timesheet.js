const API_URL = "includes/timesheet_data.php";

let currentPeriodId = null;
let allRows = [];

document.addEventListener("DOMContentLoaded", async () => {
    bindEvents();
    lucide.createIcons();
    await loadPeriods();
});

function bindEvents() {
    const periodSelect = document.getElementById("periodSelect");
    const btnRefresh = document.getElementById("btnRefresh");
    const btnRecompute = document.getElementById("btnRecompute");
    const btnSendToHr = document.getElementById("btnSendToHr");
    const searchInput = document.getElementById("searchInput");
    const btnSelectAll = document.getElementById("btnSelectAll");
    const btnResetSelection = document.getElementById("btnResetSelection");
    const btnExportPdf = document.getElementById("btnExportPdf");

    if (periodSelect) {
        periodSelect.addEventListener("change", async function () {
            currentPeriodId = this.value;
            if (currentPeriodId) {
                await loadPeriodData(currentPeriodId);
            }
        });
    }

    if (btnRefresh) {
        btnRefresh.addEventListener("click", async () => {
            if (!currentPeriodId) return;
            await loadPeriodData(currentPeriodId);
        });
    }

    if (btnRecompute) {
        btnRecompute.addEventListener("click", async () => {
            if (!currentPeriodId) return;

            const confirm = await Swal.fire({
                title: "Recompute all summaries?",
                text: "This will rebuild the selected period summary from timesheet_daily records.",
                icon: "question",
                showCancelButton: true,
                confirmButtonText: "Yes, recompute"
            });

            if (!confirm.isConfirmed) return;

            const fd = new FormData();
            fd.append("action", "recompute_all");
            fd.append("period_id", currentPeriodId);

            try {
                const data = await fetchJson(API_URL, {
                    method: "POST",
                    body: fd
                });

                if (!data.success) throw new Error(data.message || "Recompute failed.");

                await Swal.fire("Success", data.message, "success");
                await loadPeriodData(currentPeriodId);
            } catch (err) {
                Swal.fire("Error", err.message, "error");
            }
        });
    }

    if (btnSendToHr) {
        btnSendToHr.addEventListener("click", async () => {
            if (!currentPeriodId) return;

            const confirm = await Swal.fire({
                title: "Send to HR Manager?",
                text: "The selected timesheet period will be moved to FOR REVIEW.",
                icon: "question",
                showCancelButton: true,
                confirmButtonText: "Send"
            });

            if (!confirm.isConfirmed) return;

            const fd = new FormData();
            fd.append("action", "send_to_hr");
            fd.append("period_id", currentPeriodId);

            try {
                const data = await fetchJson(API_URL, {
                    method: "POST",
                    body: fd
                });

                if (!data.success) throw new Error(data.message || "Unable to send.");

                await Swal.fire("Sent", data.message, "success");
                await loadPeriodData(currentPeriodId);
            } catch (err) {
                Swal.fire("Error", err.message, "error");
            }
        });
    }

    if (searchInput) {
        searchInput.addEventListener("input", filterTable);
    }

    if (btnSelectAll) {
        btnSelectAll.addEventListener("click", selectAll);
    }

    if (btnResetSelection) {
        btnResetSelection.addEventListener("click", resetSelection);
    }

    if (btnExportPdf) {
        btnExportPdf.addEventListener("click", () => {
            Swal.fire("Info", "PDF export can be added next using Dompdf or mPDF.", "info");
        });
    }
}

async function fetchJson(url, options = {}) {
    const res = await fetch(url, options);
    const text = await res.text();

    let data;
    try {
        data = JSON.parse(text);
    } catch (e) {
        console.error("Invalid JSON response:", text);
        throw new Error("Server returned invalid JSON.");
    }

    return data;
}

async function loadPeriods() {
    try {
        const data = await fetchJson(`${API_URL}?action=get_periods`);

        if (!data.success) throw new Error(data.message || "Failed to load periods.");

        const select = document.getElementById("periodSelect");
        if (!select) return;

        select.innerHTML = "";

        if (!data.periods || data.periods.length === 0) {
            select.innerHTML = `<option value="">No periods found</option>`;

            const tbody = document.getElementById("tsBody");
            if (tbody) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="12" style="text-align:center; padding:30px;">
                            No timesheet periods found.
                        </td>
                    </tr>
                `;
            }
            return;
        }

        data.periods.forEach((period, index) => {
            const option = document.createElement("option");
            option.value = period.period_id;
            option.textContent = `${period.label} (${period.status})`;
            if (index === 0) option.selected = true;
            select.appendChild(option);
        });

        currentPeriodId = data.periods[0].period_id;
        await loadPeriodData(currentPeriodId);
    } catch (err) {
        Swal.fire("Error", err.message, "error");
    }
}

async function loadPeriodData(periodId) {
    try {
        const data = await fetchJson(`${API_URL}?action=get_period_data&period_id=${encodeURIComponent(periodId)}`);

        if (!data.success) throw new Error(data.message || "Failed to load timesheet data.");

        renderPeriodHeader(data.period);
        renderStats(data.stats);
        renderTable(data.rows || []);
        renderIssues(data.issues || []);
        renderAiReview(data.ai_review || null);

        lucide.createIcons();
    } catch (err) {
        Swal.fire("Error", err.message, "error");
    }
}

function renderPeriodHeader(period) {
    const badge = document.getElementById("periodStatusBadge");
    if (badge) {
        badge.textContent = String(period.status || "").replaceAll("_", " ");
        badge.className = `status-badge ${period.status_class || "draft"}`;
    }

    const selectedPeriodLabel = document.getElementById("selectedPeriodLabel");
    if (selectedPeriodLabel) {
        selectedPeriodLabel.innerHTML = `
            <i data-lucide="calendar" style="width:14px;"></i> ${escapeHtml(period.label || "")}
        `;
    }
}

function renderStats(stats) {
    setText("statEmployees", stats?.employees ?? 0);
    setText("statOtHours", parseFloat(stats?.ot_hours ?? 0).toFixed(2));
    setText("statLateMinutes", stats?.late_minutes ?? 0);
    setText("statIssues", stats?.issues ?? 0);
}

function renderTable(rows) {
    allRows = rows;
    const tbody = document.getElementById("tsBody");
    if (!tbody) return;

    if (!rows.length) {
        tbody.innerHTML = `
            <tr>
                <td colspan="12" style="text-align:center; padding:30px;">
                    No timesheet summary found for this period.
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = rows.map(emp => {
        const statusHtml = getStatusBadge(emp.status);

        return `
            <tr onclick="this.classList.toggle('row-selected')">
                <td class="emp-col">
                    <span class="emp-name">
                        ${escapeHtml(emp.name)}
                        ${emp.is_self ? '<span style="margin-left:6px; font-size:10px; font-weight:800; color:#2563eb; background:rgba(37,99,235,.10); padding:2px 6px; border-radius:999px;">You</span>' : ''}
                    </span>
                    <span class="emp-meta">${escapeHtml(emp.code)} • ${escapeHtml(emp.position)}</span>
                </td>
                <td>${num(emp.reg)}</td>
                <td style="color:var(--brand-green); font-weight:700;">${num(emp.ot)}</td>
                <td style="color:#ef4444; font-weight:800;">${num(emp.late)}</td>
                <td>${num(emp.abs)}</td>
                <td>${num(emp.leave_credits)}</td>
                <td>${num(emp.paid_leave)}</td>
                <td>${num(emp.excused)}</td>
                <td>${num(emp.deduction)}</td>
                <td style="color:var(--brand-green); font-weight:900;">${num(emp.final)}</td>
                <td>${statusHtml}</td>
                <td style="text-align:right; padding-right:15px;">
                    <button class="action-btn-pill" onclick="event.stopPropagation(); viewLogs(${emp.employee_id});">
                        <i data-lucide="eye" style="width:13px; height:13px;"></i>
                        <span>Logs</span>
                    </button>
                </td>
            </tr>
        `;
    }).join("");

    lucide.createIcons();
    filterTable();
}

function renderIssues(issues) {
    const container = document.getElementById("issuesContainer");
    if (!container) return;

    if (!issues.length) {
        container.innerHTML = `
            <div class="review-list-card issues-block">
                <h5 style="margin:0 0 8px 0;">Detected Issues</h5>
                <ul class="review-list">
                    <li>No issues found for this period.</li>
                </ul>
            </div>
        `;
        return;
    }

    const midpoint = Math.ceil(issues.length / 2);
    const col1 = issues.slice(0, midpoint);
    const col2 = issues.slice(midpoint);

    container.innerHTML = `
        <div class="review-list-card issues-block">
            <h5 style="margin:0 0 8px 0;">Detected Issues</h5>
            <div class="ai-review-columns">
                <div class="review-list-card">
                    <ul class="review-list">
                        ${col1.map(i => `
                            <li>
                                <strong>${escapeHtml(i.employee_name)}:</strong>
                                ${escapeHtml(i.message)} <small>(${escapeHtml(i.work_date)})</small>
                            </li>
                        `).join("")}
                    </ul>
                </div>
                <div class="review-list-card">
                    <ul class="review-list">
                        ${col2.length ? col2.map(i => `
                            <li>
                                <strong>${escapeHtml(i.employee_name)}:</strong>
                                ${escapeHtml(i.message)} <small>(${escapeHtml(i.work_date)})</small>
                            </li>
                        `).join("") : `<li>No more issues.</li>`}
                    </ul>
                </div>
            </div>
        </div>
    `;
}

function renderAiReview(ai) {
    const container = document.getElementById("issuesContainer");
    if (!container || !ai) return;

    const summary = escapeHtml(ai.summary || "No AI summary available.");
    const items = Array.isArray(ai.items) ? ai.items : [];

    const aiHtml = `
        <div class="review-list-card ai-block" style="margin-top:12px;">
            <h5 style="margin:0 0 10px 0;">AI Review</h5>
            <div style="font-size:13px; color:#334155; line-height:1.6; margin-bottom:10px;">
                ${summary}
            </div>
            <ul class="review-list">
                ${items.length
                    ? items.map(item => `<li>${escapeHtml(item)}</li>`).join("")
                    : `<li>No AI recommendations returned.</li>`}
            </ul>
        </div>
    `;

    container.insertAdjacentHTML("beforeend", aiHtml);
}

async function viewLogs(employeeId) {
    try {
        const data = await fetchJson(
            `${API_URL}?action=employee_logs&period_id=${encodeURIComponent(currentPeriodId)}&employee_id=${encodeURIComponent(employeeId)}`
        );

        if (!data.success) throw new Error(data.message || "Unable to load logs.");

        if (!data.logs.length) {
            Swal.fire("Logs", "No daily logs found for this employee in the selected period.", "info");
            return;
        }

        const rows = data.logs.map(log => `
            <tr>
                <td>${escapeHtml(log.WorkDate ?? "")}</td>
                <td>${escapeHtml(log.ShiftCode ?? "-")}</td>
                <td>${escapeHtml(log.ScheduledStart ?? "-")} - ${escapeHtml(log.ScheduledEnd ?? "-")}</td>
                <td>${escapeHtml(log.ActualTimeIn ?? "-")}</td>
                <td>${escapeHtml(log.ActualTimeOut ?? "-")}</td>
                <td>${escapeHtml(log.DayStatus ?? "-")}</td>
                <td>${escapeHtml(log.Remarks ?? "-")}</td>
            </tr>
        `).join("");

        Swal.fire({
            title: "Employee Daily Logs",
            width: 1100,
            html: `
                <div style="max-height:420px; overflow:auto; text-align:left;">
                    <table style="width:100%; border-collapse:collapse; font-size:13px;">
                        <thead>
                            <tr>
                                <th style="padding:8px; border-bottom:1px solid #ddd;">Date</th>
                                <th style="padding:8px; border-bottom:1px solid #ddd;">Shift</th>
                                <th style="padding:8px; border-bottom:1px solid #ddd;">Schedule</th>
                                <th style="padding:8px; border-bottom:1px solid #ddd;">Time In</th>
                                <th style="padding:8px; border-bottom:1px solid #ddd;">Time Out</th>
                                <th style="padding:8px; border-bottom:1px solid #ddd;">Status</th>
                                <th style="padding:8px; border-bottom:1px solid #ddd;">Remarks</th>
                            </tr>
                        </thead>
                        <tbody>${rows}</tbody>
                    </table>
                </div>
            `,
            confirmButtonText: "Close"
        });
    } catch (err) {
        Swal.fire("Error", err.message, "error");
    }
}

function getStatusBadge(status) {
    if (status === "Review") {
        return `<span class="status-badge bg-pending" style="font-size:10px; font-weight:800; padding:4px 8px; border-radius:4px; background:rgba(245,158,11,0.1); color:#b45309;">Review</span>`;
    }
    if (status === "No Data") {
        return `<span class="status-badge" style="font-size:10px; font-weight:800; padding:4px 8px; border-radius:4px; background:rgba(107,114,128,0.1); color:#4b5563;">No Data</span>`;
    }
    return `<span class="status-badge" style="font-size:10px; font-weight:800; padding:4px 8px; border-radius:4px; background:rgba(44,160,120,0.1); color:#15803d;">Ready</span>`;
}

function selectAll() {
    document.querySelectorAll('#tsBody tr').forEach(tr => tr.classList.add('row-selected'));
}

function resetSelection() {
    document.querySelectorAll('#tsBody tr').forEach(tr => tr.classList.remove('row-selected'));
}

function filterTable() {
    const inputEl = document.getElementById("searchInput");
    const rows = document.querySelectorAll("#tsBody tr");
    const input = (inputEl?.value || "").toLowerCase();

    rows.forEach(row => {
        row.style.display = row.innerText.toLowerCase().includes(input) ? "" : "none";
    });
}

function num(v) {
    const n = parseFloat(v || 0);
    return Number.isInteger(n) ? n : n.toFixed(2);
}

function setText(id, value) {
    const el = document.getElementById(id);
    if (el) el.textContent = value;
}

function escapeHtml(str) {
    return String(str ?? "")
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}