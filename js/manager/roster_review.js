(() => {
    const config = window.ROSTER_REVIEW_CONFIG || {};
    const rosterId = Number(config.rosterId || 0);
    const apiUrl = config.apiUrl || 'includes/roster_review_api.php';

    const statusBadge = document.getElementById('statusBadge');
    const rosterMeta = document.getElementById('rosterMeta');
    const statsGrid = document.getElementById('statsGrid');
    const criticalList = document.getElementById('criticalList');
    const warningList = document.getElementById('warningList');
    const validationBadge = document.getElementById('validationBadge');
    const reviewMeta = document.getElementById('reviewMeta');
    const remarksEl = document.getElementById('managerRemarks');
    const tableHead = document.getElementById('rosterTableHead');
    const tableBody = document.getElementById('rosterTableBody');
    const btnReturn = document.getElementById('btnReturn');
    const btnApprove = document.getElementById('btnApprove');

    let actionBusy = false;

    document.addEventListener('DOMContentLoaded', init);

    async function init() {
        if (!rosterId) {
            showFatal('Invalid roster ID.');
            return;
        }

        btnReturn?.addEventListener('click', handleReturn);
        btnApprove?.addEventListener('click', handleApprove);

        await loadRoster();

        if (window.lucide) {
            window.lucide.createIcons();
        }
    }

    async function loadRoster() {
        try {
            const form = new FormData();
            form.append('action', 'get_roster');
            form.append('RosterID', rosterId);

            const res = await fetch(apiUrl, {
                method: 'POST',
                body: form
            });

            const data = await res.json();

            if (!data.success) {
                throw new Error(data.message || 'Failed to load roster.');
            }

            renderRoster(data);
        } catch (err) {
            showFatal(err.message || 'Unable to load roster.');
        }
    }

    function renderRoster(data) {
        renderHeader(data.roster || {});
        renderStats(data.coverage || {});
        renderValidation(data.validation || {});
        renderTable(data.days || [], data.employees || []);
        renderActionMeta(data.roster || {});

        if (remarksEl) {
            remarksEl.value = data.roster?.review_notes || '';
        }

        const status = String(data.roster?.status || '').toUpperCase();
        const locked = status === 'PUBLISHED';

        if (btnApprove) btnApprove.disabled = locked;
        if (btnReturn) btnReturn.disabled = locked;
        if (remarksEl) remarksEl.disabled = locked;
    }

    function renderHeader(roster) {
        const statusText = String(roster.status || 'UNKNOWN').toUpperCase();
        const statusClass = getStatusClass(statusText);

        if (statusBadge) {
            statusBadge.className = `status-badge ${statusClass}`.trim();
            statusBadge.textContent = statusText;
        }

        if (rosterMeta) {
            rosterMeta.innerHTML = `
                <span class="mini-info"><i class="fa-solid fa-building"></i> ${escapeHtml(roster.department_name || 'N/A')}</span>
                <span class="mini-info"><i class="fa-solid fa-calendar"></i> ${escapeHtml(roster.period_label || 'N/A')}</span>
                <span class="mini-info"><i class="fa-solid fa-user-pen"></i> ${escapeHtml(roster.submitted_by || 'N/A')}</span>
            `;
        }
    }

    function renderStats(coverage) {
        if (!statsGrid) return;

        statsGrid.innerHTML = `
            <div class="stat-card">
                <div class="stat-top">
                    <span class="stat-label">Total Staff</span>
                    <i class="fa-solid fa-users" style="color: var(--brand-green)"></i>
                </div>
                <span class="stat-value">${num(coverage.total_employees)}</span>
            </div>

            <div class="stat-card">
                <div class="stat-top">
                    <span class="stat-label">AM Shift</span>
                    <i class="fa-solid fa-sun" style="color:#3b82f6"></i>
                </div>
                <span class="stat-value">${num(coverage.am_count)}</span>
            </div>

            <div class="stat-card">
                <div class="stat-top">
                    <span class="stat-label">MD / GY Shift</span>
                    <i class="fa-solid fa-moon" style="color:#f59e0b"></i>
                </div>
                <span class="stat-value">${num((Number(coverage.md_count || 0) + Number(coverage.gy_count || 0)))}</span>
            </div>

            <div class="stat-card">
                <div class="stat-top">
                    <span class="stat-label">Off Days</span>
                    <i class="fa-solid fa-couch" style="color:#ef4444"></i>
                </div>
                <span class="stat-value">${num(coverage.off_count)}</span>
            </div>
        `;
    }

    function renderValidation(validation) {
        const critical = Array.isArray(validation.critical) ? validation.critical : [];
        const warnings = Array.isArray(validation.warnings) ? validation.warnings : [];

        if (validationBadge) {
            validationBadge.textContent = `${critical.length} conflict(s), ${warnings.length} warning(s)`;
        }

        if (criticalList) {
            criticalList.innerHTML = critical.length
                ? critical.map(item => `
                    <li>
                        <i class="fa-solid fa-circle-exclamation"></i>
                        <span>${escapeHtml(item)}</span>
                    </li>
                `).join('')
                : `<li class="muted"><i class="fa-solid fa-circle-check"></i><span>No critical conflicts found.</span></li>`;
        }

        if (warningList) {
            warningList.innerHTML = warnings.length
                ? warnings.map(item => `
                    <li>
                        <i class="fa-solid fa-clock"></i>
                        <span>${escapeHtml(item)}</span>
                    </li>
                `).join('')
                : `<li class="muted"><i class="fa-solid fa-circle-check"></i><span>No rule warnings found.</span></li>`;
        }
    }

    function renderTable(days, employees) {
        if (!tableHead || !tableBody) return;

        let headHtml = `<tr><th class="emp-col">Employee</th>`;

        (days || []).forEach(day => {
            headHtml += `
                <th>
                    <div class="day-head">
                        <span class="day-name">${escapeHtml(day.day_short)}</span>
                        <span class="day-date">${escapeHtml(day.day_date)}</span>
                    </div>
                </th>
            `;
        });

        headHtml += `</tr>`;
        tableHead.innerHTML = headHtml;

        if (!employees.length) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="${(days?.length || 0) + 1}" class="loading-cell">No employee assignments found.</td>
                </tr>
            `;
            return;
        }

        let bodyHtml = '';

        employees.forEach(emp => {
            bodyHtml += `
                <tr>
                    <td class="emp-col">
                        <span class="emp-name">${escapeHtml(emp.employee_name || '')}</span>
                        <span class="emp-sub">${escapeHtml(emp.employee_code || '')}${emp.position_name ? ' • ' + escapeHtml(emp.position_name) : ''}</span>
                    </td>
            `;

            (days || []).forEach(day => {
                const shiftText = emp.schedule?.[day.full_date] || '';
                bodyHtml += `<td>${renderShiftPill(shiftText)}</td>`;
            });

            bodyHtml += `</tr>`;
        });

        tableBody.innerHTML = bodyHtml;
    }

    function renderShiftPill(shiftText) {
        const value = String(shiftText || '').trim().toUpperCase();

        if (!value) {
            return `<span class="shift-pill shift-empty">—</span>`;
        }

        let cls = 'shift-empty';
        if (value === 'AM') cls = 'shift-am';
        else if (value === 'MD') cls = 'shift-md';
        else if (value === 'GY') cls = 'shift-gy';
        else if (value === 'OFF') cls = 'shift-off';

        return `<span class="shift-pill ${cls}">${escapeHtml(value)}</span>`;
    }

    function renderActionMeta(roster) {
        if (!reviewMeta) return;

        const reviewedBy = roster.reviewed_by_name
            ? `Reviewed by: ${roster.reviewed_by_name}`
            : 'Not yet reviewed';

        const reviewedAt = roster.reviewed_at
            ? ` | Reviewed at: ${roster.reviewed_at}`
            : '';

        reviewMeta.textContent = `${reviewedBy}${reviewedAt}`;
    }

    async function handleReturn() {
        if (actionBusy) return;

        const remarks = remarksEl?.value.trim() || '';
        if (!remarks) {
            Swal.fire({
                icon: 'warning',
                title: 'Remarks required',
                text: 'Please enter remarks before returning this roster to the officer.'
            });
            return;
        }

        const confirmed = await Swal.fire({
            icon: 'question',
            title: 'Return roster to officer?',
            text: 'This will mark the roster as RETURNED so the officer can edit and resubmit it.',
            showCancelButton: true,
            confirmButtonText: 'Yes, return it'
        });

        if (!confirmed.isConfirmed) return;

        await submitAction('return', remarks, 'Roster returned successfully.');
    }

    async function handleApprove() {
        if (actionBusy) return;

        const remarks = remarksEl?.value.trim() || '';

        const confirmed = await Swal.fire({
            icon: 'question',
            title: 'Approve and publish roster?',
            html: `
                <div style="text-align:left">
                    <p>This will:</p>
                    <ul style="margin:8px 0 0 18px;text-align:left;">
                        <li>mark the roster as <b>PUBLISHED</b></li>
                        <li>create a matching <b>timesheet period</b> if none exists</li>
                        <li>seed <b>timesheet_daily</b> from roster assignments</li>
                    </ul>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Yes, publish it'
        });

        if (!confirmed.isConfirmed) return;

        await submitAction('approve', remarks, 'Roster approved and published successfully.');
    }

    async function submitAction(action, remarks, successMessage) {
        try {
            actionBusy = true;
            setButtonsBusy(true);

            const form = new FormData();
            form.append('action', action);
            form.append('RosterID', rosterId);
            form.append('ReviewNotes', remarks);

            const res = await fetch(apiUrl, {
                method: 'POST',
                body: form
            });

            const data = await res.json();

            if (!data.success) {
                throw new Error(data.message || 'Action failed.');
            }

            await Swal.fire({
                icon: 'success',
                title: 'Success',
                text: successMessage
            });

            await loadRoster();
        } catch (err) {
            Swal.fire({
                icon: 'error',
                title: 'Action failed',
                text: err.message || 'Something went wrong.'
            });
        } finally {
            actionBusy = false;
            setButtonsBusy(false);
        }
    }

    function setButtonsBusy(state) {
        if (btnReturn) {
            btnReturn.disabled = state;
            btnReturn.innerHTML = state
                ? `<i class="fa-solid fa-spinner fa-spin"></i> Processing...`
                : `<i class="fa-solid fa-reply"></i> Return to Officer`;
        }

        if (btnApprove) {
            btnApprove.disabled = state;
            btnApprove.innerHTML = state
                ? `<i class="fa-solid fa-spinner fa-spin"></i> Processing...`
                : `<i class="fa-solid fa-cloud-arrow-up"></i> Approve & Publish`;
        }
    }

    function getStatusClass(status) {
        switch (status) {
            case 'FOR_REVIEW':
                return 'status-for-review';
            case 'RETURNED':
                return 'status-returned';
            case 'PUBLISHED':
                return 'status-published';
            case 'APPROVED':
                return 'status-approved';
            default:
                return '';
        }
    }

    function showFatal(message) {
        if (statusBadge) {
            statusBadge.textContent = 'ERROR';
            statusBadge.className = 'status-badge';
        }

        if (rosterMeta) {
            rosterMeta.innerHTML = `<span class="mini-info"><i class="fa-solid fa-circle-exclamation"></i> ${escapeHtml(message)}</span>`;
        }

        if (statsGrid) statsGrid.innerHTML = '';
        if (criticalList) criticalList.innerHTML = `<li class="muted">${escapeHtml(message)}</li>`;
        if (warningList) warningList.innerHTML = `<li class="muted">Unable to load warnings.</li>`;
        if (tableBody) tableBody.innerHTML = `<tr><td class="loading-cell">${escapeHtml(message)}</td></tr>`;
        if (btnReturn) btnReturn.disabled = true;
        if (btnApprove) btnApprove.disabled = true;
    }

    function num(value) {
        return Number(value || 0).toLocaleString();
    }

    function escapeHtml(str) {
        return String(str ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
})();