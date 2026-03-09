document.addEventListener("DOMContentLoaded", () => {
  const rosterBody = document.getElementById("rosterBody");
  const rosterHead = document.getElementById("rosterHead");
  const periodLabel = document.getElementById("periodLabel");
  const shiftSelector = document.getElementById("shiftSelector");
  const searchInput = document.getElementById("searchInput");

  const btnAiSuggest = document.getElementById("btnAiSuggest");
  const prevBtn = document.getElementById("prevPeriod");
  const nextBtn = document.getElementById("nextPeriod");
  const submitBtn = document.getElementById("submitToHR");

  const btnFillAll = document.getElementById("btnFillAll");
  const btnClearRange = document.getElementById("btnClearRange");

  const statEmployees = document.getElementById("statEmployees");
  const statUnassigned = document.getElementById("statUnassigned");
  const statCoverage = document.getElementById("statCoverage");
  const statRosterStatus = document.getElementById("statRosterStatus");

  let selectedShift = null;
  let currentPeriodStart = null;
  let currentRosterId = null;
  let currentRosterStatus = "DRAFT";
  let currentDatesCache = [];
  let currentRowsCache = [];
  let loadedShifts = [];

  const toast = (icon, title) =>
    Swal.fire({
      toast: true,
      position: "top-end",
      icon,
      title,
      showConfirmButton: false,
      timer: 1800,
      timerProgressBar: true
    });

  const escapeHtml = (str) =>
    String(str ?? "").replace(/[&<>"']/g, (m) => ({
      "&": "&amp;",
      "<": "&lt;",
      ">": "&gt;",
      '"': "&quot;",
      "'": "&#039;"
    }[m]));

  const to12h = (timeStr = "") => {
    if (!timeStr.includes(":")) return "";
    const [hh, mm] = timeStr.split(":");
    const d = new Date();
    d.setHours(parseInt(hh, 10), parseInt(mm, 10), 0, 0);
    return d.toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" });
  };

  const toISO = (dt) => {
    const yyyy = dt.getFullYear();
    const mm = String(dt.getMonth() + 1).padStart(2, "0");
    const dd = String(dt.getDate()).padStart(2, "0");
    return `${yyyy}-${mm}-${dd}`;
  };

  const fmtLabel = (start, end) => {
    const s = new Date(`${start}T00:00:00`);
    const e = new Date(`${end}T00:00:00`);
    const opts = { month: "short", day: "2-digit" };
    return `${s.toLocaleDateString(undefined, opts)} - ${e.toLocaleDateString(undefined, opts)}, ${e.getFullYear()}`;
  };

  const isEditableStatus = (status) => {
    const s = String(status || "").toUpperCase();
    return s === "DRAFT" || s === "RETURNED";
  };

  const computePrevNextStart = (currentStart) => {
    const [y, m, d] = currentStart.split("-").map(Number);

    if (d === 1) {
      return {
        prev: toISO(new Date(y, m - 2, 25)),
        next: toISO(new Date(y, m - 1, 13))
      };
    }

    if (d === 13) {
      return {
        prev: toISO(new Date(y, m - 1, 1)),
        next: toISO(new Date(y, m - 1, 25))
      };
    }

    return {
      prev: toISO(new Date(y, m - 1, 13)),
      next: toISO(new Date(y, m, 1))
    };
  };

  const getShiftPillClass = (value) => {
    const v = String(value || "").trim().toUpperCase();

    if (v === "OFF") return "shift-off";
    if (v === "LEAVE") return "shift-leave";
    if (["M", "AM", "MORNING"].includes(v)) return "shift-morning";
    if (["A", "PM", "AFTERNOON", "MD"].includes(v)) return "shift-afternoon";
    if (["N", "NS", "NIGHT", "GY"].includes(v)) return "shift-night";

    return "shift-custom";
  };

  const formatCellHTML = (val) => {
    const v = String(val ?? "-").trim();
    if (!v || v === "-") return `<span style="opacity:.55;">-</span>`;
    if (v === "LEAVE") return `<span class="shift-pill shift-leave">LEAVE</span>`;
    return `<span class="shift-pill ${getShiftPillClass(v)}">${escapeHtml(v)}</span>`;
  };

  const updateStats = (rows = []) => {
    if (statEmployees) statEmployees.textContent = rows.length;

    if (statUnassigned) {
      let totalUnassigned = 0;

      rows.forEach((emp) => {
        (emp.days || []).forEach((day) => {
          const value = String(day.value ?? "").trim();
          if (!value || value === "-") totalUnassigned++;
        });
      });

      statUnassigned.textContent = totalUnassigned;
    }

    if (statCoverage && currentDatesCache.length) {
      statCoverage.textContent = `${currentDatesCache.length} Days`;
    }

    if (statRosterStatus) {
      statRosterStatus.textContent = currentRosterStatus || "Draft";
    }
  };

  const buildHeader = (dates) => {
    const tr1 = document.createElement("tr");
    const tr2 = document.createElement("tr");

    const thEmp = document.createElement("th");
    thEmp.className = "emp-col";
    thEmp.rowSpan = 2;
    thEmp.textContent = "Employee & Position";
    tr1.appendChild(thEmp);

    dates.forEach((d) => {
      const th1 = document.createElement("th");
      th1.textContent = d.dow;
      tr1.appendChild(th1);

      const th2 = document.createElement("th");
      th2.innerHTML =
        `<div>${escapeHtml(d.day)}</div>` +
        (d.holiday
          ? `<div class="holiday-badge" title="${escapeHtml(d.holiday.HolidayName)}">${escapeHtml(d.holiday.TypeCode)}: ${escapeHtml(d.holiday.HolidayName)}</div>`
          : "");
      tr2.appendChild(th2);
    });

    rosterHead.innerHTML = "";
    rosterHead.append(tr1, tr2);
  };

  const renderRows = (rows, dates) => {
    rosterBody.innerHTML = "";

    if (!rows.length) {
      rosterBody.innerHTML = `
        <tr>
          <td colspan="${1 + dates.length}" style="text-align:center;">
            No employees found for this department.
          </td>
        </tr>
      `;
      return;
    }

    const editable = isEditableStatus(currentRosterStatus);

    rows.forEach((emp) => {
      const tr = document.createElement("tr");
      if (emp.is_me) tr.classList.add("row-self");

      const tdEmp = document.createElement("td");
      tdEmp.className = "emp-col";
      tdEmp.innerHTML = `
        <div class="emp-info">
          <div class="emp-name">
            ${escapeHtml(emp.name)}
            ${emp.is_me ? `<span class="badge-me">(YOU)</span>` : ""}
          </div>
          <span class="emp-pos">${escapeHtml(emp.position || "")}</span>
        </div>
      `;
      tr.appendChild(tdEmp);

      (emp.days || []).forEach((dayObj) => {
        const td = document.createElement("td");
        td.dataset.empId = emp.EmployeeID;
        td.dataset.date = dayObj.date;
        td.innerHTML = formatCellHTML(dayObj.value);

        if (dayObj.is_leave) {
          td.className = "cell-leave";
          td.title = dayObj.leave_label || "Approved Leave";
        } else if (emp.is_me || !editable) {
          td.className = "cell-disabled";
        } else {
          td.className = "cell-editable";
          td.addEventListener("click", () => saveShift(td, emp.EmployeeID, dayObj.date));
        }

        if (dayObj.ai_suggested && !dayObj.is_leave) {
          td.classList.add("ai-suggested");
          td.title = dayObj.ai_reason || "AI suggestion";
        }

        tr.appendChild(td);
      });

      rosterBody.appendChild(tr);
    });
  };

  const renderShiftSelector = () => {
    if (!loadedShifts.length) {
      shiftSelector.innerHTML = `<div class="shift-loading">No shifts found.</div>`;
      return;
    }

    shiftSelector.innerHTML = "";

    loadedShifts.forEach((s) => {
      const isOff = String(s.ShiftCode).toUpperCase() === "OFF";
      const start = s.StartTime ? to12h(s.StartTime) : "";
      const end = s.EndTime ? to12h(s.EndTime) : "";

      const div = document.createElement("div");
      div.className = `shift-option${s.ShiftCode === selectedShift ? " active" : ""}`;

      div.innerHTML = `
        <span class="shift-color"></span>
        <div class="shift-meta">
          <strong>${escapeHtml(s.ShiftName)} (${escapeHtml(s.ShiftCode)})</strong>
          <small>${isOff ? "No Duty" : `${start} - ${end}`}</small>
        </div>
      `;

      if (isOff) {
        const colorEl = div.querySelector(".shift-color");
        if (colorEl) colorEl.style.background = "#ef4444";
      }

      div.addEventListener("click", () => {
        document.querySelectorAll(".shift-option").forEach((x) => x.classList.remove("active"));
        div.classList.add("active");
        selectedShift = s.ShiftCode;
      });

      shiftSelector.appendChild(div);
    });
  };

  const loadShifts = async () => {
    shiftSelector.innerHTML = `<div class="shift-loading">Loading shifts…</div>`;

    try {
      const res = await fetch("includes/shifts.php");
      const json = await res.json();

      if (json.error) {
        shiftSelector.innerHTML = `<div style="color:#ef4444;">${escapeHtml(json.error)}</div>`;
        return;
      }

      loadedShifts = json.shifts || [];
      const defaultShift = loadedShifts.find((s) => s.ShiftCode !== "OFF") || loadedShifts[0];
      selectedShift = defaultShift ? defaultShift.ShiftCode : null;

      renderShiftSelector();
    } catch (err) {
      console.error("loadShifts error:", err);
      shiftSelector.innerHTML = `<div style="color:#ef4444;">Failed to load shifts.</div>`;
    }
  };

  const loadRoster = async (periodStart = null) => {
    rosterBody.innerHTML = `<tr><td style="text-align:center;">Loading…</td></tr>`;

    try {
      const url = periodStart
        ? `includes/roster_data.php?period_start=${encodeURIComponent(periodStart)}`
        : "includes/roster_data.php";

      const res = await fetch(url);
      const json = await res.json();

      if (json.error) {
        rosterBody.innerHTML = `<tr><td style="color:#ef4444;text-align:center;">${escapeHtml(json.error)}</td></tr>`;
        return;
      }

      const roster = json.roster || {};
      const dates = json.dates || [];
      const rows = json.rows || [];

      currentPeriodStart = roster.StartDate || null;
      currentRosterId = roster.RosterID || null;
      currentRosterStatus = roster.Status || "DRAFT";
      currentDatesCache = dates;
      currentRowsCache = rows;

      if (roster.StartDate && roster.EndDate) {
        periodLabel.textContent = fmtLabel(roster.StartDate, roster.EndDate);
      } else {
        periodLabel.textContent = "No active period";
      }

      buildHeader(dates);
      renderRows(rows, dates);
      updateStats(rows);
    } catch (err) {
      console.error("loadRoster error:", err);
      rosterBody.innerHTML = `<tr><td style="color:#ef4444;text-align:center;">Failed to load roster.</td></tr>`;
    }
  };

  const saveShift = async (td, empId, workDate) => {
    if (!selectedShift) return;

    if (!isEditableStatus(currentRosterStatus)) {
      toast("warning", `Roster locked: ${currentRosterStatus}`);
      return;
    }

    const oldHTML = td.innerHTML;
    td.innerHTML = formatCellHTML(selectedShift);

    try {
      const form = new URLSearchParams();
      form.set("employee_id", empId);
      form.set("work_date", workDate);
      form.set("shift_code", selectedShift);
      form.set("roster_id", currentRosterId);

      const res = await fetch("includes/save_assignment.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: form.toString()
      });

      const json = await res.json();

      if (json.status !== "success") {
        td.innerHTML = oldHTML;
        Swal.fire({
          icon: "error",
          title: "Save failed",
          text: json.message || "Failed saving shift"
        });
        return;
      }

      td.innerHTML = formatCellHTML(json.display || selectedShift);
      td.classList.remove("ai-suggested");
      td.removeAttribute("title");

      toast("success", "Saved");

      const row = currentRowsCache.find((r) => String(r.EmployeeID) === String(empId));
      const day = row?.days?.find((d) => d.date === workDate);

      if (day) {
        day.value = json.display || selectedShift;
        day.ai_suggested = false;
        day.ai_reason = "";
      }

      updateStats(currentRowsCache);
    } catch (err) {
      console.error("saveShift error:", err);
      td.innerHTML = oldHTML;
      Swal.fire({
        icon: "error",
        title: "Network error",
        text: "Please try again."
      });
    }
  };

  const applySearch = (q) => {
    const query = String(q || "").toLowerCase().trim();

    if (!query) {
      renderRows(currentRowsCache, currentDatesCache);
      return;
    }

    const filtered = currentRowsCache.filter((r) =>
      (r.name || "").toLowerCase().includes(query) ||
      (r.position || "").toLowerCase().includes(query)
    );

    renderRows(filtered, currentDatesCache);
  };

  const askRangeModal = async () => {
    if (!currentDatesCache.length) return null;

    const first = currentDatesCache[0].date;
    const last = currentDatesCache[currentDatesCache.length - 1].date;

    const { value } = await Swal.fire({
      title: "Select date range",
      html: `
        <div style="display:flex;flex-direction:column;gap:10px;text-align:left;">
          <label>Start (min: ${first})</label>
          <input id="swStart" class="swal2-input" type="date" min="${first}" max="${last}" value="${first}">
          <label>End (max: ${last})</label>
          <input id="swEnd" class="swal2-input" type="date" min="${first}" max="${last}" value="${last}">
          <small style="opacity:.7;">Sundays are skipped automatically. Approved leave dates are skipped too.</small>
        </div>
      `,
      focusConfirm: false,
      showCancelButton: true,
      confirmButtonText: "Continue",
      preConfirm: () => {
        const start = document.getElementById("swStart")?.value.trim();
        const end = document.getElementById("swEnd")?.value.trim();

        if (!start || !end) {
          Swal.showValidationMessage("Start and End are required.");
          return null;
        }

        if (start > end) {
          Swal.showValidationMessage("Start date cannot be later than end date.");
          return null;
        }

        return { start, end };
      }
    });

    return value || null;
  };

  const doBulkRequest = async (payload) => {
    try {
      Swal.fire({
        title: payload.mode === "CLEAR" ? "Clearing..." : "Applying...",
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
      });

      const form = new URLSearchParams();
      form.set("roster_id", currentRosterId);
      form.set("mode", payload.mode);
      form.set("start_date", payload.start_date);
      form.set("end_date", payload.end_date);

      if (payload.mode === "FIXED") {
        form.set("shift_code", payload.shift_code);
      } else if (payload.mode === "RANDOM") {
        form.set("shift_pool", payload.shift_pool);
      }

      const res = await fetch("includes/bulk_assign.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: form.toString()
      });

      const raw = await res.text();

      if (!res.ok) {
        Swal.fire({
          icon: "error",
          title: `HTTP ${res.status}`,
          html: `<pre style="text-align:left;white-space:pre-wrap;max-height:260px;overflow:auto;">${escapeHtml(raw).slice(0, 1500)}</pre>`
        });
        return;
      }

      let json;
      try {
        json = JSON.parse(raw);
      } catch {
        Swal.fire({
          icon: "error",
          title: "Invalid JSON from server",
          html: `<pre style="text-align:left;white-space:pre-wrap;max-height:260px;overflow:auto;">${escapeHtml(raw).slice(0, 1500)}</pre>`
        });
        return;
      }

      if (json.status !== "success") {
        Swal.fire({
          icon: "error",
          title: payload.mode === "CLEAR" ? "Clear failed" : "Bulk failed",
          text: json.message || "Bulk operation failed."
        });
        return;
      }

      Swal.fire({
        icon: "success",
        title: payload.mode === "CLEAR" ? "Cleared" : "Done",
        html: `${escapeHtml(json.message || "Operation complete.")}<br><b>Updated cells:</b> ${json.updated_cells || 0}`
      });

      await loadRoster(currentPeriodStart);
    } catch (err) {
      console.error("doBulkRequest error:", err);
      Swal.fire({
        icon: "error",
        title: "Request failed",
        text: err?.message || "Unknown error"
      });
    }
  };

  const bulkAssignFixed = async () => {
    if (!currentRosterId) return;
    if (!isEditableStatus(currentRosterStatus)) {
      return toast("warning", `Roster locked: ${currentRosterStatus}`);
    }
    if (!selectedShift) return toast("info", "Select a shift first.");

    const range = await askRangeModal();
    if (!range) return;

    const ok = await Swal.fire({
      icon: "question",
      title: "Bulk Fill",
      html: `Apply <b>${escapeHtml(selectedShift)}</b> to <b>ALL department employees</b><br>from <b>${range.start}</b> to <b>${range.end}</b>?`,
      showCancelButton: true,
      confirmButtonText: "Apply",
      cancelButtonText: "Cancel"
    });

    if (!ok.isConfirmed) return;

    await doBulkRequest({
      mode: "FIXED",
      shift_code: selectedShift,
      start_date: range.start,
      end_date: range.end
    });
  };

  const clearRange = async () => {
    if (!currentRosterId) return;
    if (!isEditableStatus(currentRosterStatus)) {
      return toast("warning", `Roster locked: ${currentRosterStatus}`);
    }

    const range = await askRangeModal();
    if (!range) return;

    const ok = await Swal.fire({
      icon: "warning",
      title: "Clear Range",
      html: `Remove all assigned shifts for <b>ALL department employees</b><br>from <b>${range.start}</b> to <b>${range.end}</b>?`,
      showCancelButton: true,
      confirmButtonText: "Yes, clear",
      cancelButtonText: "Cancel"
    });

    if (!ok.isConfirmed) return;

    await doBulkRequest({
      mode: "CLEAR",
      start_date: range.start,
      end_date: range.end
    });
  };

  const getPeriodEndFromCache = () => {
    if (!currentDatesCache.length) return null;
    return currentDatesCache[currentDatesCache.length - 1].date;
  };

  const applyAiSuggestionsPreview = (suggestions = []) => {
    if (!Array.isArray(suggestions) || !suggestions.length) return 0;

    let appliedCount = 0;

    suggestions.forEach((item) => {
      const empId = String(item.employee_id ?? "");
      const workDate = String(item.work_date ?? "");
      const shiftCode = String(item.shift_code ?? "").trim();

      if (!empId || !workDate || !shiftCode) return;

      const row = currentRowsCache.find((r) => String(r.EmployeeID) === empId);
      if (!row) return;

      const day = row.days?.find((d) => d.date === workDate);
      if (!day) return;
      if (day.is_leave) return;

      const currentValue = String(day.value ?? "").trim();
      if (currentValue && currentValue !== "-") return;

      day.value = shiftCode;
      day.ai_reason = item.reason || "";
      day.ai_suggested = true;
      appliedCount++;
    });

    renderRows(currentRowsCache, currentDatesCache);
    updateStats(currentRowsCache);

    return appliedCount;
  };

  const requestAiSuggestions = async () => {
    if (!currentRosterId || !currentPeriodStart) {
      return toast("warning", "Roster is not fully loaded yet.");
    }

    if (!isEditableStatus(currentRosterStatus)) {
      return toast("warning", `Roster locked: ${currentRosterStatus}`);
    }

    const periodEnd = getPeriodEndFromCache();
    if (!periodEnd) {
      return toast("warning", "Could not detect current period end date.");
    }

    try {
      Swal.fire({
        title: "Generating AI suggestions...",
        text: "Groq is analyzing your current roster.",
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
      });

      const res = await fetch("includes/ai_suggest_roster.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json"
        },
        body: JSON.stringify({
          roster_id: currentRosterId,
          period_start: currentPeriodStart,
          period_end: periodEnd
        })
      });

      const json = await res.json();

      if (!json.ok) {
        throw new Error(json.message || "Failed to generate AI suggestions.");
      }

      Swal.close();

      const ai = json.ai || {};
      const suggestions = Array.isArray(ai.suggestions) ? ai.suggestions : [];
      const previewCount = applyAiSuggestionsPreview(suggestions);

      await Swal.fire({
        icon: "success",
        title: "AI Suggestions Ready",
        html: `
          <div style="text-align:left;">
            <p><b>Summary:</b> ${escapeHtml(ai.summary || "AI suggestions generated successfully.")}</p>
            <p><b>Previewed cells:</b> ${previewCount}</p>
            <p style="margin-top:8px;opacity:.8;">
              These are preview changes only. Leave dates are automatically skipped.
            </p>
          </div>
        `
      });
    } catch (err) {
      console.error("requestAiSuggestions error:", err);
      Swal.fire({
        icon: "error",
        title: "AI Error",
        text: err.message || "Something went wrong while generating AI suggestions."
      });
    }
  };

  searchInput?.addEventListener("input", () => applySearch(searchInput.value));

  submitBtn?.addEventListener("click", async () => {
    if (!currentRosterId) return;

    if (!isEditableStatus(currentRosterStatus)) {
      toast("warning", `Roster locked: ${currentRosterStatus}`);
      return;
    }

    const confirm = await Swal.fire({
      icon: "question",
      title: "Submit to HR Manager?",
      text: "After submit, editing will be locked.",
      showCancelButton: true,
      confirmButtonText: "Yes, submit",
      cancelButtonText: "Cancel"
    });

    if (!confirm.isConfirmed) return;

    try {
      const form = new URLSearchParams();
      form.set("roster_id", currentRosterId);

      const res = await fetch("includes/submit_roster.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: form.toString()
      });

      const json = await res.json();

      if (json.status !== "success") {
        Swal.fire({
          icon: "error",
          title: "Submit failed",
          text: json.message || "Submit failed"
        });
        return;
      }

      Swal.fire({
        icon: "success",
        title: "Submitted",
        text: "Sent to HR Manager for review."
      });

      await loadRoster(currentPeriodStart);
    } catch (err) {
      console.error("submit roster error:", err);
      Swal.fire({
        icon: "error",
        title: "Network error",
        text: "Please try again."
      });
    }
  });

  prevBtn?.addEventListener("click", () => {
    if (!currentPeriodStart) return;
    const { prev } = computePrevNextStart(currentPeriodStart);
    loadRoster(prev);
  });

  nextBtn?.addEventListener("click", () => {
    if (!currentPeriodStart) return;
    const { next } = computePrevNextStart(currentPeriodStart);
    loadRoster(next);
  });

  btnFillAll?.addEventListener("click", bulkAssignFixed);
  btnClearRange?.addEventListener("click", clearRange);
  btnAiSuggest?.addEventListener("click", requestAiSuggestions);

  (async () => {
    await loadShifts();
    await loadRoster();

    if (window.lucide) {
      lucide.createIcons();
    }
  })();
});