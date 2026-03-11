document.addEventListener("DOMContentLoaded", async () => {
    if (window.lucide) lucide.createIcons();

    const scheduleRangeText = document.getElementById("scheduleRangeText");
    const scheduleSummaryText = document.getElementById("scheduleSummaryText");
    const todayStatus = document.getElementById("todayStatus");
    const todayShift = document.getElementById("todayShift");
    const todayTime = document.getElementById("todayTime");
    const todayLocation = document.getElementById("todayLocation");
    const scheduleTableBody = document.getElementById("scheduleTableBody");

    function esc(value) {
        if (value === null || value === undefined) return "";
        return String(value)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function getStatusClass(status) {
        const key = String(status || "").toLowerCase();
        if (key.includes("working")) return "status-work";
        if (key.includes("rest")) return "status-rest";
        if (key.includes("holiday")) return "status-holiday";
        if (key.includes("off")) return "status-rest";
        return "status-none";
    }

    function renderRows(rows) {
        if (!Array.isArray(rows) || rows.length === 0) {
            scheduleTableBody.innerHTML = `
                <tr>
                    <td colspan="7" class="empty-cell">No schedule available.</td>
                </tr>
            `;
            return;
        }

        scheduleTableBody.innerHTML = rows.map(row => `
            <tr>
                <td>${esc(row.date)}</td>
                <td>${esc(row.day)}</td>
                <td>${esc(row.shift_name || "--")}</td>
                <td>${esc(row.shift_time || "--")}</td>
                <td>${esc(row.break_time || "--")}</td>
                <td>${esc(row.location_name || "--")}</td>
                <td>
                    <span class="status-pill ${getStatusClass(row.status)}">
                        ${esc(row.status || "No Schedule")}
                    </span>
                </td>
            </tr>
        `).join("");
    }

    try {
        const response = await fetch("includes/my_schedule_data.php", {
            credentials: "same-origin"
        });

        const rawText = await response.text();
        console.log("RAW RESPONSE:", rawText);

        let result;
        try {
            result = JSON.parse(rawText);
        } catch (e) {
            scheduleRangeText.textContent = "Invalid server response";
            scheduleSummaryText.textContent = rawText.substring(0, 300);
            todayStatus.textContent = "Error";
            todayShift.textContent = "--";
            todayTime.textContent = "--";
            todayLocation.textContent = "--";
            renderRows([]);
            return;
        }

        if (!result.ok) {
            scheduleRangeText.textContent = "Unable to load schedule";
            scheduleSummaryText.textContent = result.message || "There was a problem loading your schedule.";
            todayStatus.textContent = "Unavailable";
            todayShift.textContent = "--";
            todayTime.textContent = "--";
            todayLocation.textContent = "--";
            renderRows([]);
            return;
        }

        scheduleRangeText.textContent = result.period_label || "Current Schedule";
        scheduleSummaryText.textContent = result.summary || "Your assigned weekly schedule is shown below.";

        const today = result.today || {};
        todayStatus.textContent = today.status || "No Schedule";
        todayShift.textContent = today.shift_name || "--";
        todayTime.textContent = today.shift_time || "--";
        todayLocation.textContent = today.location_name || "--";

        renderRows(result.rows || []);
    } catch (error) {
        console.error("FETCH ERROR:", error);
        scheduleRangeText.textContent = "Unable to load schedule";
        scheduleSummaryText.textContent = String(error);
        todayStatus.textContent = "Error";
        todayShift.textContent = "--";
        todayTime.textContent = "--";
        todayLocation.textContent = "--";
        renderRows([]);
    }
});