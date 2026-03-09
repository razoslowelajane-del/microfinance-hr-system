document.addEventListener('DOMContentLoaded', () => {
    fetchPendingRequests();
    lucide.createIcons();

    // Modal Elements
    const modal = document.getElementById('requestActionModal');
    const btnClose = document.getElementById('btnCloseActionModal');
    const btnApprove = document.getElementById('btnApprove');
    const btnReject = document.getElementById('btnReject');
    let currentRequestId = null;

    if (btnClose) {
        btnClose.addEventListener('click', () => {
            modal.classList.add('hidden');
        });
    }

    if (btnApprove) {
        btnApprove.addEventListener('click', () => processRequest(currentRequestId, 'approve_request'));
    }

    if (btnReject) {
        btnReject.addEventListener('click', () => processRequest(currentRequestId, 'reject_request'));
    }

    window.viewRequest = function (requestId, dataStr) {
        currentRequestId = requestId;
        const data = JSON.parse(decodeURIComponent(dataStr));
        const container = document.getElementById('requestDetailsBody');

        let html = '<div class="info-grid-modal">';
        for (const [key, value] of Object.entries(data)) {
            // Skip empty values for cleaner look
            if (!value) continue;

            html += `
                <div class="info-item-modal">
                    <label>${formatLabel(key)}</label>
                    <div class="value">${value}</div>
                </div>
            `;
        }
        html += '</div>';

        if (Object.keys(data).length === 0) {
            html = '<div style="text-align:center; padding: 20px; color: var(--text-secondary);">No changes requested.</div>';
        }

        container.innerHTML = html;
        modal.classList.remove('hidden');
    };
});

async function fetchPendingRequests() {
    const tableBody = document.getElementById('requestsTableBody');
    try {
        // Load stats separately
        fetch('be_Informationrq.php?action=fetch_stats')
            .then(r => r.json())
            .then(s => {
                if (s.success) {
                    const el = id => document.getElementById(id);
                    if (el('statPending')) el('statPending').textContent = s.data.Pending;
                    if (el('statApproved')) el('statApproved').textContent = s.data.Approved;
                    if (el('statRejected')) el('statRejected').textContent = s.data.Rejected;
                }
            });

        const response = await fetch('be_Informationrq.php?action=fetch_pending_requests');
        const result = await response.json();

        if (result.success) {
            const requests = result.data;

            if (requests.length === 0) {
                tableBody.innerHTML = `<tr><td colspan="5">
                    <div class="empty-state">
                        <i data-lucide="inbox"></i>
                        <p>No requests found</p>
                        <span>No information update requests yet.</span>
                    </div></td></tr>`;
                lucide.createIcons();
                return;
            }

            tableBody.innerHTML = requests.map(req => {
                const initials = (req.FirstName[0] + req.LastName[0]).toUpperCase();
                const date = new Date(req.RequestDate).toLocaleDateString('en-PH', {
                    year: 'numeric', month: 'short', day: 'numeric'
                });
                const badgeClass = req.Status === 'Pending' ? 'badge-warning'
                    : req.Status === 'Approved' ? 'badge-success' : 'badge-danger';

                // Show Review button only for Pending
                const actionBtn = req.Status === 'Pending'
                    ? `<button class="btn-review" onclick="viewRequest(${req.RequestID}, '${encodeURIComponent(req.RequestData)}')">
                            <i data-lucide="eye"></i> Review
                       </button>`
                    : `<span style="font-size:13px;color:var(--text-tertiary);">—</span>`;

                return `
                <tr class="req-row">
                    <td>
                        <div class="emp-cell">
                            <div class="emp-avatar">${initials}</div>
                            <div>
                                <div class="emp-name">${req.FirstName} ${req.LastName}</div>
                                <div class="emp-dept">${req.DepartmentName || '—'}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="type-pill">
                            <i data-lucide="file-pen-line"></i>
                            ${req.RequestType}
                        </span>
                    </td>
                    <td style="color: var(--text-secondary); font-size:13px;">${date}</td>
                    <td><span class="badge ${badgeClass}">${req.Status}</span></td>
                    <td>${actionBtn}</td>
                </tr>`;
            }).join('');

            lucide.createIcons();

            // Inline search
            const searchInput = document.getElementById('tableSearch');
            if (searchInput) {
                searchInput.removeEventListener('input', searchInput._handler);
                searchInput._handler = () => {
                    const q = searchInput.value.toLowerCase();
                    document.querySelectorAll('.req-row').forEach(row => {
                        row.style.display = row.innerText.toLowerCase().includes(q) ? '' : 'none';
                    });
                };
                searchInput.addEventListener('input', searchInput._handler);
            }

        } else {
            tableBody.innerHTML = `<tr><td colspan="5" style="text-align:center;color:var(--text-secondary);padding:32px;">Error: ${result.message}</td></tr>`;
        }
    } catch (error) {
        console.error('Error fetching requests:', error);
        tableBody.innerHTML = `<tr><td colspan="5">
            <div class="empty-state">
                <i data-lucide="wifi-off"></i>
                <p>Failed to load requests</p>
                <span>Check your connection and try refreshing.</span>
            </div></td></tr>`;
        lucide.createIcons();
    }
}

async function processRequest(requestId, action) {
    if (!requestId) return;

    const actionText = action === 'approve_request' ? 'Approve' : 'Reject';
    const confirmColor = action === 'approve_request' ? '#10b981' : '#ef4444';

    const result = await Swal.fire({
        title: `Confirm ${actionText}?`,
        text: `Are you sure you want to ${actionText.toLowerCase()} this request?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: confirmColor,
        cancelButtonColor: '#6b7280',
        confirmButtonText: `Yes, ${actionText} it!`
    });

    if (!result.isConfirmed) return;

    try {
        const response = await fetch('be_Informationrq.php?action=' + action, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ request_id: requestId })
        });
        const res = await response.json();

        if (res.success) {
            Swal.fire({
                title: 'Success!',
                text: res.message,
                icon: 'success',
                confirmButtonColor: '#2ca078'
            });
            document.getElementById('requestActionModal').classList.add('hidden');
            fetchPendingRequests(); // Refresh table
        } else {
            Swal.fire({
                title: 'Error!',
                text: res.message || 'Action failed.',
                icon: 'error',
                confirmButtonColor: '#d33'
            });
        }
    } catch (error) {
        console.error('Error processing request:', error);
        Swal.fire({
            title: 'Error!',
            text: 'An error occurred while processing the request.',
            icon: 'error',
            confirmButtonColor: '#d33'
        });
    }
}

function formatLabel(key) {
    // Convert camelCase or PascalCase to Title Case with spaces
    return key.replace(/([A-Z])/g, ' $1').trim();
}
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

    if (typeof lucide !== "undefined") lucide.createIcons();
});