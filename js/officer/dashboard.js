
document.addEventListener("DOMContentLoaded", () => {
    
    const fetchDashboardData = async () => {
        try {
            const response = await fetch("includes/dashboard_data.php");
            if (!response.ok) throw new Error("Failed to fetch dashboard data");
            
            const data = await response.json();
            updateKPIs(data.kpis);
            updateApprovalsTable(data.table_items);
            updateHolidays(data.upcoming);
            updateActivity(data.activity);

            // Re-initialize Lucide icons for any newly injected HTML
            if (window.lucide) {
                window.lucide.createIcons();
            }

        } catch (error) {
            console.error("Dashboard Fetch Error:", error);
        }
    };

    // --- HELPER FUNCTIONS ---

    function updateKPIs(kpis) {
        const statValues = document.querySelectorAll(".stat-value");
        if (statValues.length >= 4) {
            statValues[0].innerText = kpis.pending_timesheets || 0;
            statValues[1].innerText = kpis.attendance_today || 0;
            statValues[2].innerText = kpis.pending_leaves || 0;
            statValues[3].innerText = kpis.pending_claims || 0;
        }
    }

    function updateApprovalsTable(items) {
        const tableContainer = document.querySelector(".data-table");
        if (!tableContainer) return;

        tableContainer.innerHTML = items.map(item => `
            <div class="table-row">
                <div class="table-cell">
                    <div class="client-info">
                        <div class="client-avatar" style="background: ${item.type === 'Leave' ? '#ef4444' : '#2ca078'};">
                            ${item.type === '—' ? '?' : item.type.charAt(0)}
                        </div>
                        <div>
                            <span class="client-name">${item.name}</span>
                            <span class="client-detail">${item.detail}</span>
                        </div>
                    </div>
                </div>
                <div class="table-cell"><span class="amount">${item.type}</span></div>
                <div class="table-cell">
                    <span class="badge-status ${item.status.toLowerCase()}">${item.status}</span>
                </div>
            </div>
        `).join('');
    }

    function updateHolidays(upcoming) {
        const holidayList = document.querySelector(".payment-list");
        if (!holidayList) return;

        holidayList.innerHTML = upcoming.map(h => {
            const dateObj = new Date(h.date);
            const day = dateObj.getDate();
            const month = dateObj.toLocaleString('default', { month: 'short' });
            
            return `
                <div class="payment-item">
                    <div class="payment-date">
                        <span class="date-day">${day}</span>
                        <span class="date-month">${month}</span>
                    </div>
                    <div class="payment-details">
                        <span class="payment-client">${h.name}</span>
                        <span class="payment-type">Holiday</span>
                    </div>
                </div>
            `;
        }).join('');
    }

    function updateActivity(activity) {
        const activityList = document.querySelector(".activity-list");
        if (!activityList) return;

        activityList.innerHTML = activity.map(act => `
            <div class="activity-item">
                <div class="activity-icon" style="background: rgba(44, 160, 120, 0.1); color: var(--brand-green);">
                    <i data-lucide="check-circle"></i>
                </div>
                <div class="activity-content">
                    <p class="activity-text">${act.text}</p>
                    <span class="activity-time">${act.time}</span>
                </div>
            </div>
        `).join('');
    }

    // Initial load
    fetchDashboardData();

    // Auto-refresh every 5 minutes
    setInterval(fetchDashboardData, 300000);
});