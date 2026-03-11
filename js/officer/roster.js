const ROSTER_CTX = window.__ROSTER_CTX__ || {};
const AUTO_SAVE_DELAY = 900;

const state = {
  roster: null,
  dates: [],
  employees: [],
  holidays: [],
  holidayMeta: {},
  leaves: {},
  assignments: {},
  shifts: [],
  today: null,
  selectedShift: null,
  changedCells: {},
  pendingAiAssignments: {},
  autoSaveTimer: null,
  isSaving: false,
  searchTerm: ""
};

const $ = (id) => document.getElementById(id);

document.addEventListener("DOMContentLoaded", () => {
  bindEvents();
  loadRoster();
});

function bindEvents() {
  $("prevPeriod")?.addEventListener("click", () => changePeriod(-14));
  $("nextPeriod")?.addEventListener("click", () => changePeriod(14));

  $("btnFillAll")?.addEventListener("click", handleFillEditableRange);
  $("btnClearRange")?.addEventListener("click", handleClearEditableRange);
  $("btnAiSuggest")?.addEventListener("click", handleAiSuggestReview);

  $("btnDismissAiReview")?.addEventListener("click", () => {
    $("aiReviewPanel")?.classList.add("hidden");
  });

  $("submitToHR")?.addEventListener("click", submitRoster);

  $("searchInput")?.addEventListener("input", (e) => {
    state.searchTerm = (e.target.value || "").trim().toLowerCase();
    renderRosterTable();
  });

  window.addEventListener("beforeunload", (e) => {
    if (Object.keys(state.changedCells).length > 0 || state.isSaving) {
      e.preventDefault();
      e.returnValue = "";
    }
  });
}

function changePeriod(days) {
  if (!state.roster?.start_date) return;

  const d = new Date(state.roster.start_date + "T00:00:00");
  d.setDate(d.getDate() + days);

  const newStart = normalizeMonday(formatDate(d));
  loadRoster(newStart);
}

function normalizeMonday(dateStr) {
  const d = new Date(dateStr + "T00:00:00");
  const day = d.getDay();
  const diff = day === 0 ? -6 : 1 - day;
  d.setDate(d.getDate() + diff);
  return formatDate(d);
}

function formatDate(d) {
  const y = d.getFullYear();
  const m = String(d.getMonth() + 1).padStart(2, "0");
  const day = String(d.getDate()).padStart(2, "0");
  return `${y}-${m}-${day}`;
}

function escapeHtml(value) {
  return String(value ?? "")
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#039;");
}

function statusClass(status) {
  const s = String(status || "").toUpperCase();
  if (s === "FOR_REVIEW") return "for-review";
  if (s === "RETURNED") return "returned";
  if (s === "APPROVED") return "approved";
  if (s === "PUBLISHED") return "published";
  return "draft";
}

function statusText(status) {
  const s = String(status || "").toUpperCase();
  if (s === "FOR_REVIEW") return "For Review";
  if (s === "RETURNED") return "Returned";
  if (s === "APPROVED") return "Approved";
  if (s === "PUBLISHED") return "Published";
  return "Draft";
}

function isRosterLocked() {
  const s = String(state.roster?.status || "").toUpperCase();
  return ["FOR_REVIEW", "APPROVED", "PUBLISHED"].includes(s);
}

async function loadRoster(startDate = null) {
  try {
    const targetStart = normalizeMonday(startDate || state.roster?.start_date || formatDate(new Date()));

    setAutoSaveIndicator("Loading roster...", "loading");

    const res = await fetch(`includes/roster_api.php?action=load&start_date=${encodeURIComponent(targetStart)}`);
    const data = await res.json();

    if (!data.success) {
      Swal.fire("Error", data.message || "Failed to load roster.", "error");
      setAutoSaveIndicator("Load failed", "error");
      return;
    }

    state.roster = data.roster || null;
    state.dates = data.dates || [];
    state.employees = data.employees || [];
    state.holidays = data.holidays || [];
    state.holidayMeta = data.holiday_meta || {};
    state.leaves = data.leaves || {};
    state.assignments = data.assignments || {};
    state.shifts = data.shifts || [];
    state.today = data.today || null;
    state.changedCells = {};
    state.pendingAiAssignments = {};

    if (!state.selectedShift && state.shifts.length > 0) {
      state.selectedShift = state.shifts[0];
    } else if (state.selectedShift) {
      const found = state.shifts.find(s => s.code === state.selectedShift.code);
      state.selectedShift = found || state.shifts[0] || null;
    }

    renderHeader();
    renderStats();
    renderShiftOptions();
    renderRosterTable();
    updateAiReviewPanel();
    setAutoSaveIndicator("Auto-save ready", "ready");

    if (window.lucide) lucide.createIcons();
  } catch (error) {
    Swal.fire("Error", error.message || "Something went wrong while loading roster.", "error");
    setAutoSaveIndicator("Load failed", "error");
  }
}

function renderHeader() {
  const label = $("periodLabel");
  const badge = $("headerRosterStatus");
  const statStatus = $("statRosterStatus");

  if (label && state.roster) {
    label.textContent = `${state.roster.start_date} to ${state.roster.end_date}`;
  }

  if (badge) {
    badge.className = `status-badge ${statusClass(state.roster?.status)}`;
    badge.innerHTML = `
      <i data-lucide="file-clock" class="meta-icon"></i>
      ${escapeHtml(statusText(state.roster?.status))}
    `;
  }

  if (statStatus) {
    statStatus.textContent = statusText(state.roster?.status);
  }

  const submitBtn = $("submitToHR");
  if (submitBtn) {
    const locked = isRosterLocked();
    submitBtn.disabled = locked;
    submitBtn.classList.toggle("is-disabled", locked);

    const span = submitBtn.querySelector("span");
    if (span) {
      span.textContent = locked ? "Submitted / Locked" : "Submit to HR Manager";
    }
  }
}

function renderStats() {
  let unassigned = 0;

  for (const emp of state.employees) {
    for (const date of state.dates) {
      if (!isEditableCell(emp, date)) continue;
      const key = `${emp.EmployeeID}_${date}`;
      if (!state.assignments[key]) {
        unassigned++;
      }
    }
  }

  $("statEmployees").textContent = state.employees.length;
  $("statCoverage").textContent = `${state.dates.length} days`;
  $("statUnassigned").textContent = unassigned;
  $("statRosterStatus").textContent = statusText(state.roster?.status);
}

function renderShiftOptions() {
  const wrap = $("shiftSelector");
  if (!wrap) return;

  if (!state.shifts.length) {
    wrap.innerHTML = `<div class="shift-loading">No active shifts found.</div>`;
    return;
  }

  wrap.innerHTML = "";

  state.shifts.forEach((shift) => {
    const btn = document.createElement("button");
    btn.type = "button";
    btn.className = "shift-option" + (state.selectedShift?.code === shift.code ? " active" : "");
    btn.dataset.code = shift.code;

    btn.innerHTML = `
      <span class="shift-color"></span>
      <span class="shift-meta">
        <strong>${escapeHtml(shift.label)}</strong>
        <small>${escapeHtml(shift.time)}</small>
        <small>${escapeHtml(shift.break_label || ((shift.break_mins || 0) + " mins break"))}</small>
      </span>
    `;

    btn.addEventListener("click", () => {
      state.selectedShift = shift;
      document.querySelectorAll(".shift-option").forEach(el => el.classList.remove("active"));
      btn.classList.add("active");
    });

    wrap.appendChild(btn);
  });
}

function renderRosterTable() {
  const head = $("rosterHead");
  const body = $("rosterBody");
  if (!head || !body) return;

  const filteredEmployees = state.employees.filter(emp => {
    if (!state.searchTerm) return true;
    const hay = `${emp.name} ${emp.position || ""}`.toLowerCase();
    return hay.includes(state.searchTerm);
  });

  head.innerHTML = "";
  body.innerHTML = "";

  const trHead = document.createElement("tr");
  trHead.innerHTML =
    `<th class="emp-col">Employee</th>` +
    state.dates.map(date => {
      const holiday = state.holidays.includes(date)
        ? `<div class="holiday-badge">${escapeHtml(state.holidayMeta[date] || "Holiday")}</div>`
        : "";
      return `<th>${formatHeadDate(date)}${holiday}</th>`;
    }).join("");
  head.appendChild(trHead);

  if (!filteredEmployees.length) {
    body.innerHTML = `<tr><td colspan="${state.dates.length + 1}" style="padding:24px;text-align:center;">No employee matched your search.</td></tr>`;
    return;
  }

  filteredEmployees.forEach(emp => {
    const tr = document.createElement("tr");
    if (emp.is_self) tr.classList.add("row-self");

    let html = `
      <td class="emp-col">
        <div class="emp-name">
          ${escapeHtml(emp.name)}
          ${emp.is_self ? `<span class="badge-me">Me</span>` : ``}
        </div>
        <span class="emp-pos">${escapeHtml(emp.position || "Employee")}</span>
      </td>
    `;

    html += state.dates.map(date => renderCell(emp, date)).join("");
    tr.innerHTML = html;
    body.appendChild(tr);
  });

  bindCellEvents();
  if (window.lucide) lucide.createIcons();
}

function isEditableCell(emp, date) {
  if (isRosterLocked()) return false;
  if (emp.is_self && ROSTER_CTX.rules?.selfRowManualLocked) return false;

  const key = `${emp.EmployeeID}_${date}`;
  if (state.leaves[key] && ROSTER_CTX.rules?.leaveLocked) return false;
  if (state.holidays.includes(date) && ROSTER_CTX.rules?.holidayLocked) return false;

  return true;
}

function isAiAssignableCell(emp, date) {
  if (isRosterLocked()) return false;

  const key = `${emp.EmployeeID}_${date}`;
  if (state.leaves[key] && ROSTER_CTX.rules?.leaveLocked) return false;
  if (state.holidays.includes(date) && ROSTER_CTX.rules?.holidayLocked) return false;

  return true;
}

function renderCell(emp, date) {
  const key = `${emp.EmployeeID}_${date}`;
  const assignment = state.assignments[key];
  const leave = state.leaves[key];
  const isHoliday = state.holidays.includes(date);
  const editable = isEditableCell(emp, date);

  let classes = [];
  let content = "";

  if (emp.is_self) classes.push("cell-self-locked");
  if (leave) classes.push("cell-leave");
  if (isHoliday) classes.push("cell-holiday");
  if (!editable) classes.push("cell-locked");
  if (editable) classes.push("cell-editable");

  if (leave) {
    content = `
      <span class="pill leave">LEAVE</span>
      <div class="leave-badge">${escapeHtml(leave.type || "Approved Leave")}</div>
    `;
  } else if (isHoliday) {
    content = `<span class="pill holiday">HOLIDAY</span>`;
  } else if (assignment) {
    content = `
      <span class="shift-pill ${escapeHtml(assignment.class || "shift-custom")}"
            title="Break: ${Number(assignment.break_mins || 0)} mins | Grace: ${Number(assignment.grace_mins || 0)} mins">
        ${escapeHtml(assignment.label || assignment.shift_code || "Set")}
      </span>
      ${assignment.break_mins ? `<small>${assignment.break_mins} min break</small>` : ``}
    `;
  } else {
    content = `<span class="pill">Set</span>`;
  }

  const aiClass = assignment?.source === "ai" ? " ai-suggested" : "";

  return `
    <td class="${classes.join(" ")}${aiClass}"
        data-employee-id="${emp.EmployeeID}"
        data-date="${date}"
        data-editable="${editable ? "1" : "0"}">
      ${content}
    </td>
  `;
}

function bindCellEvents() {
  document.querySelectorAll('#rosterBody td[data-editable="1"]').forEach(td => {
    td.addEventListener("click", () => {
      if (!state.selectedShift) {
        Swal.fire("No shift selected", "Please select a shift first.", "info");
        return;
      }

      const employeeId = Number(td.dataset.employeeId);
      const date = td.dataset.date;
      assignShift(employeeId, date, state.selectedShift, "manual");
    });

    td.addEventListener("contextmenu", (e) => {
      e.preventDefault();
      const employeeId = Number(td.dataset.employeeId);
      const date = td.dataset.date;
      clearShift(employeeId, date);
    });
  });
}

function assignShift(employeeId, date, shift, source = "manual") {
  const key = `${employeeId}_${date}`;

  state.assignments[key] = {
    shift_code: shift.code,
    label: shift.label,
    class: shift.class,
    start_time: shift.start_time || null,
    end_time: shift.end_time || null,
    break_mins: Number(shift.break_mins || 0),
    grace_mins: Number(shift.grace_mins || 0),
    is_day_off: Number(shift.is_day_off || 0),
    source
  };

  state.changedCells[key] = {
    employee_id: employeeId,
    work_date: date,
    shift_code: shift.code,
    source
  };

  renderRosterTable();
  renderStats();
  updateAiReviewPanel();
  queueAutoSave();
}

function assignShiftSilently(employeeId, date, shift, source = "manual") {
  const key = `${employeeId}_${date}`;

  state.assignments[key] = {
    shift_code: shift.code,
    label: shift.label,
    class: shift.class,
    start_time: shift.start_time || null,
    end_time: shift.end_time || null,
    break_mins: Number(shift.break_mins || 0),
    grace_mins: Number(shift.grace_mins || 0),
    is_day_off: Number(shift.is_day_off || 0),
    source
  };

  state.changedCells[key] = {
    employee_id: employeeId,
    work_date: date,
    shift_code: shift.code,
    source
  };
}

function clearShift(employeeId, date) {
  const key = `${employeeId}_${date}`;

  delete state.assignments[key];
  state.changedCells[key] = {
    employee_id: employeeId,
    work_date: date,
    shift_code: "",
    source: "manual"
  };

  renderRosterTable();
  renderStats();
  updateAiReviewPanel();
  queueAutoSave();
}

function handleFillEditableRange() {
  if (isRosterLocked()) return;

  if (!state.selectedShift) {
    Swal.fire("No shift selected", "Please select a shift first.", "info");
    return;
  }

  let count = 0;

  state.employees.forEach(emp => {
    state.dates.forEach(date => {
      if (!isEditableCell(emp, date)) return;
      assignShiftSilently(emp.EmployeeID, date, state.selectedShift, "manual");
      count++;
    });
  });

  renderRosterTable();
  renderStats();
  updateAiReviewPanel();
  queueAutoSave();

  Swal.fire("Filled", `${count} editable cell(s) were updated.`, "success");
}

function handleClearEditableRange() {
  if (isRosterLocked()) return;

  Swal.fire({
    title: "Clear current roster range?",
    text: "This will clear all editable assignments, including AI-applied shifts and your officer row schedule for this period where applicable.",
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Yes, clear all",
    cancelButtonText: "Cancel"
  }).then(result => {
    if (!result.isConfirmed) return;

    let count = 0;

    state.employees.forEach(emp => {
      state.dates.forEach(date => {
        const key = `${emp.EmployeeID}_${date}`;
        const hasAssignment = !!state.assignments[key];
        if (!hasAssignment) return;

        const leave = state.leaves[key];
        const holiday = state.holidays.includes(date);

        if (leave && ROSTER_CTX.rules?.leaveLocked) return;
        if (holiday && ROSTER_CTX.rules?.holidayLocked) return;
        if (isRosterLocked()) return;

        delete state.assignments[key];
        state.changedCells[key] = {
          employee_id: emp.EmployeeID,
          work_date: date,
          shift_code: "",
          source: emp.is_self ? "clear_range" : "manual"
        };
        count++;
      });
    });

    renderRosterTable();
    renderStats();
    updateAiReviewPanel();
    queueAutoSave();

    Swal.fire("Cleared", `${count} assignment(s) were cleared from this period.`, "success");
  });
}

function queueAutoSave() {
  clearTimeout(state.autoSaveTimer);
  setAutoSaveIndicator("Unsaved changes...", "dirty");

  state.autoSaveTimer = setTimeout(() => {
    saveDraft(true);
  }, AUTO_SAVE_DELAY);
}

function setAutoSaveIndicator(text, mode = "ready") {
  const box = $("autoSaveIndicator");
  if (!box) return;

  box.className = `mini-info autosave-${mode}`;
  box.innerHTML = `<i data-lucide="save" class="meta-icon"></i> ${escapeHtml(text)}`;

  if (window.lucide) lucide.createIcons();
}

async function saveDraft(silent = false) {
  const cells = Object.values(state.changedCells);
  if (!state.roster?.id || cells.length === 0 || isRosterLocked()) return true;
  if (state.isSaving) return false;

  state.isSaving = true;
  setAutoSaveIndicator("Saving draft...", "saving");

  try {
    const res = await fetch("includes/roster_api.php?action=save", {
      method: "POST",
      headers: {
        "Content-Type": "application/json"
      },
      body: JSON.stringify({
        roster_id: state.roster.id,
        cells
      })
    });

    const data = await res.json();

    if (!data.success) {
      state.isSaving = false;
      setAutoSaveIndicator("Save failed", "error");
      if (!silent) {
        Swal.fire("Save failed", data.message || "Unable to save roster draft.", "error");
      }
      return false;
    }

    state.changedCells = {};
    state.isSaving = false;
    setAutoSaveIndicator("Draft auto-saved", "saved");

    if (!silent) {
      Swal.fire("Saved", data.message || "Roster draft saved.", "success");
    }

    return true;
  } catch (error) {
    state.isSaving = false;
    setAutoSaveIndicator("Save failed", "error");
    if (!silent) {
      Swal.fire("Error", error.message || "Failed to save draft.", "error");
    }
    return false;
  }
}

function detectDominantWeekShift(employeeId, weekDates) {
  const counts = { AM: 0, MD: 0, GY: 0 };

  weekDates.forEach(date => {
    const key = `${employeeId}_${date}`;
    const a = state.assignments[key];
    if (!a) return;
    const code = String(a.shift_code || "").toUpperCase();
    if (counts[code] !== undefined) counts[code]++;
  });

  let bestCode = null;
  let bestCount = 0;

  Object.keys(counts).forEach(code => {
    if (counts[code] > bestCount) {
      bestCode = code;
      bestCount = counts[code];
    }
  });

  if (!bestCode) return null;
  return state.shifts.find(s => String(s.code).toUpperCase() === bestCode) || null;
}

function buildAiSuggestions() {
  state.pendingAiAssignments = {};

  const workShiftPool = state.shifts.filter(s =>
    ["AM", "MD", "GY"].includes(String(s.code).toUpperCase())
  );

  if (!workShiftPool.length) return;

  const week1Dates = state.dates.slice(0, 6);
  const week2Dates = state.dates.slice(6, 12);

  const globalWeek1Count = { AM: 0, MD: 0, GY: 0 };
  const globalWeek2Count = { AM: 0, MD: 0, GY: 0 };

  state.employees.forEach(emp => {
    week1Dates.forEach(date => {
      const key = `${emp.EmployeeID}_${date}`;
      const a = state.assignments[key];
      if (!a) return;
      const code = String(a.shift_code || "").toUpperCase();
      if (globalWeek1Count[code] !== undefined) globalWeek1Count[code]++;
    });

    week2Dates.forEach(date => {
      const key = `${emp.EmployeeID}_${date}`;
      const a = state.assignments[key];
      if (!a) return;
      const code = String(a.shift_code || "").toUpperCase();
      if (globalWeek2Count[code] !== undefined) globalWeek2Count[code]++;
    });
  });

  state.employees.forEach(emp => {
    const empId = emp.EmployeeID;

    const week1EditableDates = week1Dates.filter(date => {
      const key = `${empId}_${date}`;
      if (!isAiAssignableCell(emp, date)) return false;
      return !state.assignments[key];
    });

    const week2EditableDates = week2Dates.filter(date => {
      const key = `${empId}_${date}`;
      if (!isAiAssignableCell(emp, date)) return false;
      return !state.assignments[key];
    });

    const existingWeek1 = detectDominantWeekShift(empId, week1Dates);
    const existingWeek2 = detectDominantWeekShift(empId, week2Dates);

    let week1Shift = existingWeek1;
    let week2Shift = existingWeek2;

    if (!week1Shift) {
      week1Shift = [...workShiftPool]
        .sort((a, b) => globalWeek1Count[a.code] - globalWeek1Count[b.code])[0];
      if (week1Shift) globalWeek1Count[week1Shift.code]++;
    }

    if (!week2Shift || (week1Shift && week2Shift.code === week1Shift.code)) {
      const choices = [...workShiftPool]
        .filter(s => !week1Shift || s.code !== week1Shift.code)
        .sort((a, b) => globalWeek2Count[a.code] - globalWeek2Count[b.code]);

      week2Shift = choices[0] || week2Shift || week1Shift;
      if (week2Shift) globalWeek2Count[week2Shift.code]++;
    }

    week1EditableDates.forEach(date => {
      if (!week1Shift) return;
      const key = `${empId}_${date}`;
      state.pendingAiAssignments[key] = {
        employee_id: empId,
        work_date: date,
        shift_code: week1Shift.code
      };
    });

    week2EditableDates.forEach(date => {
      if (!week2Shift) return;
      const key = `${empId}_${date}`;
      state.pendingAiAssignments[key] = {
        employee_id: empId,
        work_date: date,
        shift_code: week2Shift.code
      };
    });
  });
}

async function handleAiSuggestReview() {
  if (isRosterLocked()) {
    Swal.fire("Locked", "This roster can no longer be changed.", "info");
    return;
  }

  buildAiSuggestions();

  const entries = Object.values(state.pendingAiAssignments);
  if (!entries.length) {
    updateAiReviewPanel();
    $("aiReviewPanel")?.classList.remove("hidden");
    Swal.fire("No AI changes", "No editable empty cells were available for AI scheduling.", "info");
    return;
  }

  const result = await Swal.fire({
    title: "Apply AI schedule?",
    html: `
      <div style="text-align:left">
        <p style="margin-bottom:8px;">AI will apply weekly shift rotation to available cells in this 2-week period.</p>
        <ul style="text-align:left; padding-left:18px; margin:0;">
          <li>Only unlocked cells will be changed</li>
          <li>Leave dates will stay blocked</li>
          <li>Holiday locked dates will stay blocked</li>
          <li>Your officer row may also receive AI assignment</li>
        </ul>
      </div>
    `,
    icon: "question",
    showCancelButton: true,
    confirmButtonText: "Yes, apply AI",
    cancelButtonText: "Cancel"
  });

  if (!result.isConfirmed) {
    state.pendingAiAssignments = {};
    return;
  }

  let appliedCount = 0;

  for (const item of entries) {
    const shift = state.shifts.find(s => s.code === item.shift_code);
    if (!shift) continue;
    assignShiftSilently(item.employee_id, item.work_date, shift, "ai");
    appliedCount++;
  }

  state.pendingAiAssignments = {};
  renderRosterTable();
  renderStats();
  updateAiReviewPanel();
  $("aiReviewPanel")?.classList.remove("hidden");
  queueAutoSave();

  Swal.fire("AI Applied", `${appliedCount} schedule cell(s) were updated by AI. Review the summary below.`, "success");
}

function calculateAiReview() {
  let editableCells = 0;
  let assignedEditable = 0;
  let leaveConflicts = 0;
  let holidayConflicts = 0;

  const employeeShiftCounts = {};

  state.employees.forEach(emp => {
    employeeShiftCounts[emp.EmployeeID] = { AM: 0, MD: 0, GY: 0 };

    state.dates.forEach(date => {
      const key = `${emp.EmployeeID}_${date}`;
      const assignment = state.assignments[key];
      const leave = state.leaves[key];
      const holiday = state.holidays.includes(date);

      if (assignment && employeeShiftCounts[emp.EmployeeID][assignment.shift_code] !== undefined) {
        employeeShiftCounts[emp.EmployeeID][assignment.shift_code]++;
      }

      if (leave && assignment) {
        leaveConflicts++;
      }

      if (holiday && assignment) {
        holidayConflicts++;
      }

      if (isEditableCell(emp, date)) {
        editableCells++;
        if (assignment) assignedEditable++;
      }
    });
  });

  const unassignedRemaining = Math.max(0, editableCells - assignedEditable);
  const coverageScore = editableCells === 0 ? 100 : Math.round((assignedEditable / editableCells) * 100);

  let fairnessPenalty = 0;

  state.employees.forEach(emp => {
    const w1 = detectDominantWeekShift(emp.EmployeeID, state.dates.slice(0, 6));
    const w2 = detectDominantWeekShift(emp.EmployeeID, state.dates.slice(6, 12));

    if (w1 && w2 && w1.code === w2.code) {
      fairnessPenalty += 20;
    }

    if (!w1) fairnessPenalty += 10;
    if (!w2) fairnessPenalty += 10;
  });

  let fairnessScore = Math.max(0, 100 - fairnessPenalty);

  let complianceScore = 100;
  complianceScore -= leaveConflicts * 50;
  complianceScore -= holidayConflicts * 20;
  if (complianceScore < 0) complianceScore = 0;

  const warnings = [];
  const errors = [];

  if (unassignedRemaining > 0) {
    warnings.push(`${unassignedRemaining} editable slot(s) are still unassigned.`);
  }

  if (coverageScore < 100) {
    warnings.push(`Coverage is incomplete for this 2-week period.`);
  }

  state.employees.forEach(emp => {
    const w1 = detectDominantWeekShift(emp.EmployeeID, state.dates.slice(0, 6));
    const w2 = detectDominantWeekShift(emp.EmployeeID, state.dates.slice(6, 12));

    if (w1 && w2 && w1.code === w2.code) {
      warnings.push(`${emp.name} has the same shift for both weeks.`);
    }
  });

  if (leaveConflicts > 0) {
    errors.push(`${leaveConflicts} leave conflict(s) detected. Leave dates must remain unscheduled.`);
  }

  if (holidayConflicts > 0) {
    errors.push(`${holidayConflicts} holiday assignment(s) detected on locked holiday dates.`);
  }

  if (!state.employees.some(e => e.is_self)) {
    warnings.push(`Officer self row is not included in the loaded roster.`);
  }

  return {
    employeesIncluded: state.employees.length,
    selfIncluded: state.employees.some(e => e.is_self),
    fairnessScore,
    coverageScore,
    complianceScore,
    unassignedRemaining,
    warnings,
    errors
  };
}

function updateAiReviewPanel() {
  const review = calculateAiReview();

  $("aiEmployeesIncluded").textContent = review.employeesIncluded;
  $("aiSelfIncluded").textContent = review.selfIncluded ? "Yes" : "No";
  $("aiFairnessScore").textContent = `${review.fairnessScore}%`;
  $("aiCoverageScore").textContent = `${review.coverageScore}%`;
  $("aiComplianceScore").textContent = `${review.complianceScore}%`;
  $("aiUnassignedRemaining").textContent = review.unassignedRemaining;

  $("aiWarningsList").innerHTML = review.warnings.length
    ? review.warnings.map(item => `<li>${escapeHtml(item)}</li>`).join("")
    : `<li>No warnings found.</li>`;

  $("aiErrorsList").innerHTML = review.errors.length
    ? review.errors.map(item => `<li>${escapeHtml(item)}</li>`).join("")
    : `<li>No errors or conflicts found.</li>`;
}

async function submitRoster() {
  if (!state.roster?.id) {
    Swal.fire("Error", "Roster ID is missing.", "error");
    return;
  }

  if (isRosterLocked()) {
    Swal.fire("Locked", "This roster is already submitted or finalized.", "info");
    return;
  }

  const review = calculateAiReview();
  if (review.errors.length > 0) {
    $("aiReviewPanel")?.classList.remove("hidden");
    Swal.fire("Cannot submit", "Please fix the conflicts shown in AI Review first.", "error");
    return;
  }

  if (Object.keys(state.changedCells).length > 0) {
    const saved = await saveDraft(true);
    if (!saved) {
      Swal.fire("Save first", "Draft save failed. Please try again.", "error");
      return;
    }
  }

  if (review.unassignedRemaining > 0) {
    const result = await Swal.fire({
      title: "There are still unassigned slots",
      text: "Do you still want to submit this roster to HR Manager?",
      icon: "warning",
      showCancelButton: true,
      confirmButtonText: "Yes, submit"
    });

    if (!result.isConfirmed) return;
  }

  try {
    const res = await fetch("includes/roster_api.php?action=submit", {
      method: "POST",
      headers: {
        "Content-Type": "application/json"
      },
      body: JSON.stringify({
        roster_id: state.roster.id
      })
    });

    const data = await res.json();

    if (!data.success) {
      Swal.fire("Submit failed", data.message || "Unable to submit roster.", "error");
      return;
    }

    Swal.fire("Submitted", data.message || "Roster submitted to HR Manager.", "success");
    loadRoster(state.roster.start_date);
  } catch (error) {
    Swal.fire("Error", error.message || "Submit failed.", "error");
  }
}

function formatHeadDate(dateStr) {
  const d = new Date(dateStr + "T00:00:00");
  const weekday = d.toLocaleDateString("en-US", { weekday: "short" });
  const monthDay = d.toLocaleDateString("en-US", { month: "short", day: "2-digit" });
  return `${weekday}<br><small>${monthDay}</small>`;
}