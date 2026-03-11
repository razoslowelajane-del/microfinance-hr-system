document.addEventListener("DOMContentLoaded", () => {
  if (window.lucide) lucide.createIcons();

  const apiUrl = "./api/roster_api.php";

  const els = {
    rosterHead: document.getElementById("rosterHead"),
    rosterBody: document.getElementById("rosterBody"),
    periodLabel: document.getElementById("periodLabel"),
    prevPeriod: document.getElementById("prevPeriod"),
    nextPeriod: document.getElementById("nextPeriod"),
    searchInput: document.getElementById("searchInput"),
    shiftSelector: document.getElementById("shiftSelector"),
    btnFillAll: document.getElementById("btnFillAll"),
    btnAiSuggest: document.getElementById("btnAiSuggest"),
    btnClearRange: document.getElementById("btnClearRange"),
    submitToHR: document.getElementById("submitToHR"),
    statEmployees: document.getElementById("statEmployees"),
    statCoverage: document.getElementById("statCoverage"),
    statUnassigned: document.getElementById("statUnassigned"),
    statRosterStatus: document.getElementById("statRosterStatus"),
    headerRosterStatus: document.getElementById("headerRosterStatus"),
    aiReviewPanel: document.getElementById("aiReviewPanel"),
    aiEmployeesIncluded: document.getElementById("aiEmployeesIncluded"),
    aiSelfIncluded: document.getElementById("aiSelfIncluded"),
    aiFairnessScore: document.getElementById("aiFairnessScore"),
    aiCoverageScore: document.getElementById("aiCoverageScore"),
    aiComplianceScore: document.getElementById("aiComplianceScore"),
    aiUnassignedRemaining: document.getElementById("aiUnassignedRemaining"),
    aiWarningsList: document.getElementById("aiWarningsList"),
    aiErrorsList: document.getElementById("aiErrorsList"),
    btnDismissAiReview: document.getElementById("btnDismissAiReview"),
    btnApplyAiSuggestions: document.getElementById("btnApplyAiSuggestions")
  };

  const state = {
    anchorDate: toYmd(new Date()),
    selectedShiftCode: null,
    data: null,
    aiSuggestions: []
  };

  function toYmd(dateObj) {
    const y = dateObj.getFullYear();
    const m = String(dateObj.getMonth() + 1).padStart(2, "0");
    const d = String(dateObj.getDate()).padStart(2, "0");
    return `${y}-${m}-${d}`;
  }

  function addDays(ymd, days) {
    const dt = new Date(ymd + "T00:00:00");
    dt.setDate(dt.getDate() + days);
    return toYmd(dt);
  }

  function prettyDate(ymd) {
    const dt = new Date(ymd + "T00:00:00");
    return dt.toLocaleDateString("en-PH", {
      month: "short",
      day: "numeric",
      year: "numeric"
    });
  }

  function escapeHtml(str) {
    return String(str ?? "")
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");
  }

  async function postJSON(payload) {
    const res = await fetch(apiUrl, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload || {})
    });

    const data = await res.json();

    if (!res.ok || !data.ok) {
      throw new Error(data.message || "Request failed.");
    }

    return data;
  }

  function getShiftPillClass(code) {
    const c = String(code || "").toUpperCase();
    if (["AM", "MORNING"].includes(c)) return "shift-morning";
    if (["MD", "MID", "MIDDAY", "AFTERNOON", "PM"].includes(c)) return "shift-afternoon";
    if (["NS", "NIGHT"].includes(c)) return "shift-night";
    if (["OFF", "REST"].includes(c)) return "shift-off";
    return "shift-custom";
  }

  function clearAiPanel() {
    state.aiSuggestions = [];
    els.aiReviewPanel.classList.add("hidden");
    els.aiWarningsList.innerHTML = "<li>No AI review data yet.</li>";
    els.aiErrorsList.innerHTML = "<li>No AI review data yet.</li>";
  }

  function renderShifts(shifts) {
    if (!Array.isArray(shifts) || shifts.length === 0) {
      els.shiftSelector.innerHTML = `<div class="shift-loading">No active shifts found.</div>`;
      state.selectedShiftCode = null;
      return;
    }

    if (!state.selectedShiftCode) {
      state.selectedShiftCode = shifts[0].ShiftCode;
    }

    els.shiftSelector.innerHTML = shifts.map(shift => {
      const active = shift.ShiftCode === state.selectedShiftCode ? "active" : "";
      return `
        <button type="button" class="shift-option ${active}" data-shift="${escapeHtml(shift.ShiftCode)}">
          <span class="shift-color"></span>
          <span class="shift-meta">
            <strong>${escapeHtml(shift.ShiftCode)}</strong>
            <small>${escapeHtml(shift.ShiftName || shift.ShiftCode)}</small>
          </span>
        </button>
      `;
    }).join("");

    els.shiftSelector.querySelectorAll(".shift-option").forEach(btn => {
      btn.addEventListener("click", () => {
        state.selectedShiftCode = btn.dataset.shift;
        renderShifts(shifts);
      });
    });
  }

  function renderStats(data) {
    const stats = data.stats || {};
    els.statCoverage.textContent = stats.coverage_label ?? "--";
    els.statUnassigned.textContent = stats.unassigned ?? "--";
    els.statRosterStatus.textContent = data.roster?.Status || "Draft";

    const status = String(data.roster?.Status || "DRAFT").toUpperCase();
    els.headerRosterStatus.className = "status-badge";
    if (status === "DRAFT" || status === "RETURNED") {
      els.headerRosterStatus.classList.add("draft");
    }

    els.headerRosterStatus.innerHTML = `<i data-lucide="file-clock" class="meta-icon"></i> ${escapeHtml(status.replaceAll("_", " "))}`;
  }

  function renderRoster(data) {
    const days = data.days || [];
    const employees = data.employees || [];
    const assignments = data.assignments || {};
    const holidays = data.holidays || {};
    const leaves = data.leaves || {};
    const today = toYmd(new Date());

    els.periodLabel.textContent = `${prettyDate(data.period.start_date)} – ${prettyDate(data.period.end_date)}`;

    let head = `<tr><th class="emp-col">Employee</th>`;
    days.forEach(day => {
      const holiday = holidays[day.date];
      head += `
        <th>
          <div>${escapeHtml(day.label)}</div>
          <div>${escapeHtml(day.short_date)}</div>
          ${holiday ? `<div class="holiday-badge">${escapeHtml(holiday.HolidayName || "HOLIDAY")}</div>` : ""}
        </th>
      `;
    });
    head += `</tr>`;
    els.rosterHead.innerHTML = head;

    let rows = "";

    employees.forEach(emp => {
      const rowClass = emp.IsSelf ? "row-self" : "";
      rows += `<tr class="${rowClass}" data-name="${escapeHtml((emp.FullName || "").toLowerCase())}">`;
      rows += `
        <td class="emp-col">
          <div class="emp-name">
            ${escapeHtml(emp.FullName)}
            ${emp.IsSelf ? `<span class="badge-me">Your Row</span>` : ""}
          </div>
          <span class="emp-pos">${escapeHtml(emp.PositionName || "Employee")}</span>
        </td>
      `;

      days.forEach(day => {
        const key = `${emp.EmployeeID}|${day.date}`;
        const assignment = assignments[key] || null;
        const holiday = holidays[day.date] || null;
        const leave = leaves[key] || null;

        let cellClass = "";
        let content = "";
        let editable = false;

        if (day.date < today) {
          cellClass = "cell-past cell-locked";
          content = assignment
            ? `<span class="shift-pill ${getShiftPillClass(assignment.ShiftCode)}">${escapeHtml(assignment.ShiftCode)}</span>`
            : `<span class="pill off">--</span>`;
        } else if (holiday) {
          cellClass = "cell-holiday cell-locked";
          content = `<span class="pill holiday">HOLIDAY</span>`;
        } else if (leave) {
          cellClass = "cell-leave cell-locked";
          content = `<span class="pill leave">LEAVE</span>`;
        } else if (emp.IsSelf) {
          cellClass = "cell-self-locked cell-locked";
          content = assignment
            ? `<span class="shift-pill ${getShiftPillClass(assignment.ShiftCode)}">${escapeHtml(assignment.ShiftCode)}</span>`
            : `<span class="pill off">--</span>`;
        } else {
          editable = true;
          cellClass = "cell-editable";
          content = assignment
            ? `<span class="shift-pill ${getShiftPillClass(assignment.ShiftCode)}">${escapeHtml(assignment.ShiftCode)}</span>`
            : `<span class="pill off">--</span>`;
        }

        const aiClass = assignment && assignment.Source === "AI" ? "ai-suggested" : "";

        rows += `
          <td
            class="${cellClass} ${aiClass}"
            data-employee-id="${emp.EmployeeID}"
            data-date="${day.date}"
            data-editable="${editable ? 1 : 0}"
          >
            ${content}
          </td>
        `;
      });

      rows += `</tr>`;
    });

    els.rosterBody.innerHTML = rows || `<tr><td style="padding:30px;text-align:center;">No employees found.</td></tr>`;
    bindEditableCells();
  }

  function bindEditableCells() {
    els.rosterBody.querySelectorAll("td[data-editable='1']").forEach(td => {
      td.addEventListener("click", async () => {
        if (!state.selectedShiftCode) {
          Swal.fire("No Shift Selected", "Please select a shift first.", "warning");
          return;
        }

        const employeeId = parseInt(td.dataset.employeeId, 10);
        const workDate = td.dataset.date;

        try {
          await saveCells([
            {
              employee_id: employeeId,
              work_date: workDate,
              shift_code: state.selectedShiftCode
            }
          ]);
        } catch (err) {
          Swal.fire("Error", err.message || "Failed to save assignment.", "error");
        }
      });
    });
  }

  async function loadRoster() {
    try {
      els.rosterBody.innerHTML = `<tr><td style="text-align:center;padding:30px;">Loading schedules…</td></tr>`;

      const data = await postJSON({
        action: "load",
        anchor_date: state.anchorDate
      });

      state.data = data;
      state.aiSuggestions = [];

      renderShifts(data.shifts || []);
      renderRoster(data);
      renderStats(data);
      clearAiPanel();

      if (window.lucide) lucide.createIcons();
    } catch (err) {
      console.error(err);
      Swal.fire("Error", err.message || "Failed to load roster.", "error");
    }
  }

  async function saveCells(cells, source = "MANUAL") {
    const data = await postJSON({
      action: "save",
      anchor_date: state.anchorDate,
      cells,
      source
    });

    state.data = data;
    renderRoster(data);
    renderStats(data);

    Swal.fire({
      toast: true,
      position: "top-end",
      icon: "success",
      title: "Roster updated",
      showConfirmButton: false,
      timer: 1400
    });
  }

  function filterRows() {
    const q = (els.searchInput.value || "").trim().toLowerCase();
    els.rosterBody.querySelectorAll("tr[data-name]").forEach(tr => {
      tr.style.display = !q || tr.dataset.name.includes(q) ? "" : "none";
    });
  }

  async function fillEditableRange() {
    if (!state.selectedShiftCode) {
      Swal.fire("No Shift Selected", "Please select a shift first.", "warning");
      return;
    }

    const cells = [];
    els.rosterBody.querySelectorAll("td[data-editable='1']").forEach(td => {
      cells.push({
        employee_id: parseInt(td.dataset.employeeId, 10),
        work_date: td.dataset.date,
        shift_code: state.selectedShiftCode
      });
    });

    if (!cells.length) {
      Swal.fire("Nothing to Fill", "No editable cells found.", "info");
      return;
    }

    const res = await Swal.fire({
      title: "Fill all editable cells?",
      text: `Apply ${state.selectedShiftCode} to all editable cells in this roster period?`,
      icon: "question",
      showCancelButton: true,
      confirmButtonText: "Yes, apply"
    });

    if (!res.isConfirmed) return;
    await saveCells(cells, "MANUAL");
  }

  async function clearEditableRange() {
    const cells = [];
    els.rosterBody.querySelectorAll("td[data-editable='1']").forEach(td => {
      cells.push({
        employee_id: parseInt(td.dataset.employeeId, 10),
        work_date: td.dataset.date,
        shift_code: ""
      });
    });

    if (!cells.length) {
      Swal.fire("Nothing to Clear", "No editable cells found.", "info");
      return;
    }

    const res = await Swal.fire({
      title: "Clear editable range?",
      text: "This will remove assignments from editable cells only.",
      icon: "warning",
      showCancelButton: true,
      confirmButtonText: "Yes, clear"
    });

    if (!res.isConfirmed) return;
    await saveCells(cells, "MANUAL");
  }

  function renderAiReview(review, suggestions) {
    state.aiSuggestions = suggestions || [];

    els.aiEmployeesIncluded.textContent = review.employees_included ?? "--";
    els.aiSelfIncluded.textContent = review.self_included ? "YES" : "NO";
    els.aiFairnessScore.textContent = review.fairness_score ?? "--";
    els.aiCoverageScore.textContent = review.coverage_score ?? "--";
    els.aiComplianceScore.textContent = review.rule_compliance_score ?? "--";
    els.aiUnassignedRemaining.textContent = review.unassigned_remaining ?? "--";

    const warnings = Array.isArray(review.warnings) && review.warnings.length
      ? review.warnings
      : ["No warnings."];

    const errors = Array.isArray(review.errors) && review.errors.length
      ? review.errors
      : ["No blocking conflicts."];

    els.aiWarningsList.innerHTML = warnings.map(item => `<li>${escapeHtml(item)}</li>`).join("");
    els.aiErrorsList.innerHTML = errors.map(item => `<li>${escapeHtml(item)}</li>`).join("");
    els.aiReviewPanel.classList.remove("hidden");

    if (window.lucide) lucide.createIcons();
  }

  async function aiSuggestReview() {
    try {
      const data = await postJSON({
        action: "ai_suggest",
        anchor_date: state.anchorDate
      });

      renderAiReview(data.review || {}, data.suggestions || []);

      if (Array.isArray(data.preview_cells)) {
        data.preview_cells.forEach(preview => {
          const selector = `td[data-employee-id="${preview.employee_id}"][data-date="${preview.work_date}"]`;
          const td = document.querySelector(selector);
          if (td) {
            td.classList.add("ai-suggested");
            td.innerHTML = `<span class="shift-pill ${getShiftPillClass(preview.shift_code)}">${escapeHtml(preview.shift_code)}</span>`;
          }
        });
      }
    } catch (err) {
      Swal.fire("AI Review Failed", err.message || "Unable to generate AI suggestions.", "error");
    }
  }

  async function applyAiSuggestions() {
    if (!Array.isArray(state.aiSuggestions) || !state.aiSuggestions.length) {
      Swal.fire("Nothing to Apply", "No AI suggestions available.", "info");
      return;
    }

    const blockingErrors = Array.from(els.aiErrorsList.querySelectorAll("li"))
      .map(li => li.textContent.trim())
      .filter(text => text && text !== "No blocking conflicts.");

    if (blockingErrors.length) {
      Swal.fire("Cannot Apply", "Please resolve the AI review errors first.", "warning");
      return;
    }

    const res = await Swal.fire({
      title: "Apply AI suggestions?",
      text: `This will save ${state.aiSuggestions.length} suggested assignments.`,
      icon: "question",
      showCancelButton: true,
      confirmButtonText: "Apply"
    });

    if (!res.isConfirmed) return;

    await saveCells(
      state.aiSuggestions.map(item => ({
        employee_id: item.employee_id,
        work_date: item.work_date,
        shift_code: item.shift_code
      })),
      "AI"
    );

    clearAiPanel();
  }

  async function submitToHR() {
    const res = await Swal.fire({
      title: "Submit roster to HR Manager?",
      text: "Once submitted, this draft will move to FOR_REVIEW.",
      icon: "question",
      showCancelButton: true,
      confirmButtonText: "Submit"
    });

    if (!res.isConfirmed) return;

    try {
      const data = await postJSON({
        action: "submit",
        anchor_date: state.anchorDate
      });

      Swal.fire("Submitted", data.message || "Roster submitted to HR Manager.", "success");
      state.data = data;
      renderRoster(data);
      renderStats(data);
    } catch (err) {
      Swal.fire("Cannot Submit", err.message || "Submission failed.", "error");
    }
  }

  els.prevPeriod.addEventListener("click", () => {
    state.anchorDate = addDays(state.anchorDate, -14);
    loadRoster();
  });

  els.nextPeriod.addEventListener("click", () => {
    state.anchorDate = addDays(state.anchorDate, 14);
    loadRoster();
  });

  els.searchInput.addEventListener("input", filterRows);
  els.btnFillAll.addEventListener("click", fillEditableRange);
  els.btnClearRange.addEventListener("click", clearEditableRange);
  els.btnAiSuggest.addEventListener("click", aiSuggestReview);
  els.btnDismissAiReview.addEventListener("click", clearAiPanel);
  els.btnApplyAiSuggestions.addEventListener("click", applyAiSuggestions);
  els.submitToHR.addEventListener("click", submitToHR);

  loadRoster();
});