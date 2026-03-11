document.addEventListener('DOMContentLoaded', () => {
    const config = window.ROSTER_VIEW_CONFIG || {};
    const dataUrl = config.dataUrl || 'includes/roster_view_data.php';
    const reviewBaseUrl = config.reviewBaseUrl || 'roster_review.php';

    const searchInput = document.getElementById('searchInput');
    const departmentFilter = document.getElementById('departmentFilter');
    const statusFilter = document.getElementById('statusFilter');
    const sortBy = document.getElementById('sortBy');

    const applyFiltersBtn = document.getElementById('applyFiltersBtn');
    const resetFiltersBtn = document.getElementById('resetFiltersBtn');
    const refreshBtn = document.getElementById('refreshBtn');

    const pendingCount = document.getElementById('pendingCount');
    const returnedCount = document.getElementById('returnedCount');
    const publishedCount = document.getElementById('publishedCount');
    const totalCount = document.getElementById('totalCount');

    const resultMeta = document.getElementById('resultMeta');
    const rosterTableBody = document.getElementById('rosterTableBody');

    let departmentsLoaded = false;

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function formatDate(dateStr) {
        if (!dateStr) return '-';

        const date = new Date(dateStr);
        if (Number.isNaN(date.getTime())) return dateStr;

        return date.toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric'
        });
    }

    function formatDateTime(dateStr) {
        if (!dateStr) return '-';

        const date = new Date(dateStr);
        if (Number.isNaN(date.getTime())) return dateStr;

        return date.toLocaleString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric',
            hour: 'numeric',
            minute: '2-digit'
        });
    }

    function formatDateRange(start, end) {
        if (!start || !end) return '-';

        const startDate = new Date(start);
        const endDate = new Date(end);

        if (Number.isNaN(startDate.getTime()) || Number.isNaN(endDate.getTime())) {
            return `${start} - ${end}`;
        }

        const sameMonth =
            startDate.getMonth() === endDate.getMonth() &&
            startDate.getFullYear() === endDate.getFullYear();

        if (sameMonth) {
            return `${startDate.toLocaleDateString('en-US', {
                month: 'long',
                day: 'numeric'
            })} - ${endDate.toLocaleDateString('en-US', {
                day: 'numeric',
                year: 'numeric'
            })}`;
        }

        return `${formatDate(start)} - ${formatDate(end)}`;
    }

    function getStatusClass(status) {
        switch (status) {
            case 'FOR_REVIEW':
                return 'for-review';
            case 'RETURNED':
                return 'returned';
            case 'APPROVED':
                return 'approved';
            case 'PUBLISHED':
                return 'published';
            case 'DRAFT':
            default:
                return 'draft';
        }
    }

    function setLoading(message = 'Loading roster queue...') {
        rosterTableBody.innerHTML = `
            <tr>
                <td colspan="6">${escapeHtml(message)}</td>
            </tr>
        `;
        resultMeta.textContent = message;
    }

    function renderSummary(summary = {}) {
        pendingCount.textContent = summary.pending ?? 0;
        returnedCount.textContent = summary.returned ?? 0;
        publishedCount.textContent = summary.published ?? 0;
        totalCount.textContent = summary.total ?? 0;
    }

    function populateDepartments(departments = []) {
        if (!departmentsLoaded) {
            const currentValue = departmentFilter.value || '';
            departmentFilter.innerHTML = `<option value="">All Departments</option>`;

            departments.forEach(dep => {
                const option = document.createElement('option');
                option.value = dep.DepartmentID;
                option.textContent = dep.DepartmentName;
                departmentFilter.appendChild(option);
            });

            if (currentValue) {
                departmentFilter.value = currentValue;
            }

            departmentsLoaded = true;
        }
    }

    function renderRows(rows = []) {
        if (!rows.length) {
            rosterTableBody.innerHTML = `
                <tr>
                    <td colspan="6">No rosters found for the selected filters.</td>
                </tr>
            `;
            return;
        }

        rosterTableBody.innerHTML = rows.map(row => {
            const reviewUrl = `${reviewBaseUrl}?roster_id=${encodeURIComponent(row.RosterID)}`;
            const statusClass = getStatusClass(row.Status);

            return `
                <tr>
                    <td class="emp-col">
                        <span class="roster-main">${escapeHtml(row.DepartmentName || '-')}</span>
                        <span class="roster-sub">Roster #${escapeHtml(row.RosterID)}</span>
                    </td>

                    <td>
                        <span class="roster-main">${escapeHtml(formatDateRange(row.WeekStart, row.WeekEnd))}</span>
                        <span class="roster-sub">${escapeHtml(row.WeekStart || '-')} to ${escapeHtml(row.WeekEnd || '-')}</span>
                    </td>

                    <td>
                        <span class="roster-main">${escapeHtml(row.OfficerFullName || row.Username || '-')}</span>
                        <span class="roster-sub">${escapeHtml(row.EmployeeCode || row.Username || '-')}</span>
                    </td>

                    <td>
                        <span class="roster-main">${escapeHtml(row.TotalEmployees)}</span>
                        <span class="roster-sub">employees</span>
                    </td>

                    <td>
                        <span class="status-badge ${escapeHtml(statusClass)}">
                            ${escapeHtml(row.Status)}
                        </span>
                    </td>

                    <td>
                        <span class="roster-main">${escapeHtml(formatDate(row.CreatedAt))}</span>
                        <span class="roster-sub">${escapeHtml(formatDateTime(row.CreatedAt))}</span>
                    </td>

                    <td>
                        <a class="action-link" href="${reviewUrl}">
                            <i class="fa-solid fa-eye"></i>
                            Review
                        </a>
                    </td>
                </tr>
            `;
        }).join('');
    }

    function getParams() {
        const params = new URLSearchParams();
        params.set('search', searchInput.value.trim());
        params.set('department', departmentFilter.value);
        params.set('status', statusFilter.value);
        params.set('sort', sortBy.value);
        return params;
    }

    async function loadRosterQueue() {
        try {
            setLoading();

            const params = getParams();
            const response = await fetch(`${dataUrl}?${params.toString()}`, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const rawText = await response.text();

            let payload;
            try {
                payload = JSON.parse(rawText);
            } catch (err) {
                console.error('Invalid JSON response:', rawText);
                throw new Error('Server did not return valid JSON. Check PHP warnings/errors.');
            }

            if (!response.ok || !payload.success) {
                throw new Error(payload.message || 'Failed to load roster queue.');
            }

            const data = payload.data || {};

            populateDepartments(data.departments || []);
            renderSummary(data.summary || {});
            renderRows(data.rosters || []);

            const count = (data.rosters || []).length;
            resultMeta.textContent = `${count} roster(s) found`;
        } catch (error) {
            console.error(error);
            rosterTableBody.innerHTML = `
                <tr>
                    <td colspan="6">Failed to load roster queue.</td>
                </tr>
            `;
            resultMeta.textContent = 'Failed to load roster queue';

            if (window.Swal) {
                Swal.fire({
                    icon: 'error',
                    title: 'Load Failed',
                    text: error.message || 'Unable to fetch roster queue.'
                });
            }
        }
    }

    function resetFilters() {
        searchInput.value = '';
        departmentFilter.value = '';
        statusFilter.value = 'FOR_REVIEW';
        sortBy.value = 'latest';
        loadRosterQueue();
    }

    applyFiltersBtn?.addEventListener('click', loadRosterQueue);
    resetFiltersBtn?.addEventListener('click', resetFilters);
    refreshBtn?.addEventListener('click', loadRosterQueue);

    searchInput?.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            loadRosterQueue();
        }
    });

    statusFilter?.addEventListener('change', loadRosterQueue);
    departmentFilter?.addEventListener('change', loadRosterQueue);
    sortBy?.addEventListener('change', loadRosterQueue);

    loadRosterQueue();
});