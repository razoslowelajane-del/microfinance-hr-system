lucide.createIcons();

// --- DRAWER LOGIC (View Employee Logs) ---
async function viewEmployeeLogs(empId, rosterId) {
    const overlay = document.getElementById('drawerOverlay');
    const body = document.getElementById('dBody');
    const stats = document.getElementById('dStats');
    
    overlay.style.display = 'flex';
    body.innerHTML = '<tr><td colspan="5" style="padding:20px;">Loading employee logs...</td></tr>';

    try {
        const response = await fetch(`includes/update_roster_status.php?employee_id=${empId}&roster_id=${rosterId}`);
        const res = await response.json();

        if (res.status === 'success') {
            document.getElementById('dName').innerText = res.employee.FullName;
            document.getElementById('dMeta').innerText = `${res.employee.EmployeeCode} • ${res.employee.PositionName}`;
            
            stats.innerHTML = `
                <div class="stat-card" style="padding:10px; border:1px solid var(--border-color); text-align:center;">
                    <small>Work/OFF</small><br><strong>${res.summary.WorkDays}/${res.summary.OffDays}</strong>
                </div>
                <div class="stat-card" style="padding:10px; border:1px solid var(--border-color); text-align:center;">
                    <small>Avg Break</small><br><strong>${res.summary.AvgBreak}m</strong>
                </div>
                <div class="stat-card" style="padding:10px; border:1px solid var(--border-color); text-align:center;">
                    <small>Flags</small><br><strong style="color:red;">${res.summary.TotalFlags}</strong>
                </div>
            `;

            let html = '';
            res.days.forEach(day => {
                const flagStyle = day.Flags > 0 ? 'color:red; font-weight:bold;' : '';
                html += `
                    <tr>
                        <td>${day.WorkDate}</td>
                        <td><span class="tag tag-${day.Shift}">${day.Shift}</span></td>
                        <td>${day.ActualIn} - ${day.ActualOut}</td>
                        <td>${day.BreakRange}<br><small>(${day.ActualBreakMins}m)</small></td>
                        <td style="${flagStyle}">${day.Flags}</td>
                    </tr>
                `;
            });
            body.innerHTML = html;
            lucide.createIcons();
        }
    } catch (e) {
        body.innerHTML = '<tr><td colspan="5" style="color:red; padding:20px;">Error loading logs. Please try again.</td></tr>';
    }
}

function closeDrawer() {
    document.getElementById('drawerOverlay').style.display = 'none';
}

// --- ROSTER ACTIONS (Approve/Return) ---
function processRoster(status) {
    const isReturn = status === 'RETURNED';
    Swal.fire({
        title: isReturn ? 'Return to Officer?' : 'Approve & Publish?',
        text: isReturn ? 'Provide a reason for revision.' : 'This will make the roster official and visible to staff.',
        icon: isReturn ? 'warning' : 'question',
        input: isReturn ? 'textarea' : null,
        inputPlaceholder: 'Required notes...',
        showCancelButton: true,
        confirmButtonColor: isReturn ? '#ef4444' : '#2ca078',
        confirmButtonText: isReturn ? 'Confirm Return' : 'Publish Roster',
        preConfirm: (inputValue) => {
            if (isReturn && !inputValue) {
                Swal.showValidationMessage('Notes are required for returning a roster.');
            }
            return inputValue;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('roster_id', currentRosterID);
            formData.append('status', status);
            formData.append('notes', result.value || '');

            fetch('includes/update_roster_status.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    Swal.fire('Roster ' + status, '', 'success').then(() => location.href='dashboard.php');
                } else {
                    Swal.fire('Error', data.message || 'Update failed', 'error');
                }
            });
        }
    });
}