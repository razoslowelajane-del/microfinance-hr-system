<?php 
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$current_page = 'timesheets'; 
include('sidebar.php'); 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HR4 Payroll Timesheet | HR3 System</title>
    
    <link rel="stylesheet" href="../../css/officer/dashboard.css">
    <link rel="stylesheet" href="../../css/sidebar-fix.css?v=1.0">
    <link rel="stylesheet" href="../../css/officer/roster.css"> 
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        /* --- GLOBAL FIXES & VARIABLES --- */
* { box-sizing: border-box; }

:root {
    --bg-main: #f9f9f9;
    --card-bg: #ffffff;
    --brand-green: #2CA078;
    --brand-green-light: rgba(44, 160, 120, 0.15);
    --text-main: #111111;
    --text-muted: #777777;
    --border-color: rgba(0, 0, 0, 0.08);
}

.dark-mode {
    --bg-main: #121212;
    --card-bg: #181818;
    --text-main: #ffffff;
    --text-muted: #aaaaaa;
    --border-color: rgba(255, 255, 255, 0.06);
    --brand-green-light: rgba(44, 160, 120, 0.25);
}

body { margin: 0; background: var(--bg-main); color: var(--text-main); font-family: 'Inter', sans-serif; }

.main-content { margin-left: 260px; width: calc(100% - 260px); padding: 2.5rem; min-height: 100vh; }

/* --- HEADER & CONTROLS --- */
.page-header { display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem; margin-bottom: 2rem; }
.page-header h1 { font-size: 1.8rem; font-weight: 800; margin: 0 0 5px 0; }
.subtitle { color: var(--text-muted); font-size: 0.9rem; margin: 0; }

.dept-selector { display: flex; align-items: center; gap: 10px; background: var(--card-bg); padding: 10px 15px; border-radius: 8px; border: 1px solid var(--border-color); }
.form-select { padding: 6px 10px; border-radius: 6px; border: 1px solid var(--border-color); background: var(--bg-main); color: var(--text-main); font-family: 'Inter', sans-serif; font-size: 0.9rem; outline: none; cursor: pointer; }

/* --- KPIs --- */
.kpi-row { display: flex; gap: 20px; margin-bottom: 1.5rem; }
.kpi-card { background: var(--card-bg); padding: 1.2rem 1.5rem; border-radius: 12px; box-shadow: 0 2px 10px var(--border-color); flex: 1; display: flex; align-items: center; gap: 15px; }
.kpi-icon { width: 45px; height: 45px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; }
.icon-blue { background: rgba(37, 99, 235, 0.1); color: #2563eb; }
.icon-green { background: rgba(44, 160, 120, 0.1); color: var(--brand-green); }
.kpi-val { display: block; font-size: 1.4rem; font-weight: 800; }
.kpi-lab { font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; }

/* --- TOOLBAR & FILTER DROPDOWN --- */
.toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.2rem; flex-wrap: wrap; gap: 15px; }
.search-box-wrapper { position: relative; width: 320px; }
.search-icon { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 0.85rem; }
.search-box { width: 100%; padding: 10px 10px 10px 35px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--card-bg); color: var(--text-main); font-size: 0.9rem; outline: none; }
.action-buttons { display: flex; gap: 10px; align-items: center; }

/* Dropdown Container */
.filter-container { position: relative; display: inline-block; }
.filter-dropdown {
    display: none; position: absolute; top: 110%; right: 0; 
    background-color: var(--card-bg); min-width: 220px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.12); border-radius: 10px; 
    padding: 15px; z-index: 1000; border: 1px solid var(--border-color);
}
.filter-dropdown.show { display: block; animation: fadeIn 0.2s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(-5px); } to { opacity: 1; transform: translateY(0); } }

.filter-checkbox { display: flex; align-items: center; gap: 8px; font-size: 0.8rem; color: var(--text-main); margin-bottom: 8px; cursor: pointer; user-select: none; }
.filter-checkbox input[type="checkbox"] { accent-color: var(--brand-green); width: 15px; height: 15px; cursor: pointer; }
.filter-checkbox input:disabled { opacity: 0.5; cursor: not-allowed; }

/* --- BUTTONS --- */
.btn { padding: 10px 16px; border-radius: 8px; border: none; cursor: pointer; font-weight: 600; font-size: 0.85rem; transition: all 0.2s ease; display: flex; align-items: center; gap: 8px; }
.btn-sm { padding: 6px 12px; font-size: 0.8rem; }
.btn-primary { background: var(--brand-green); color: #fff; }
.btn-outline { background: transparent; border: 1px solid var(--border-color); color: var(--text-main); }
.btn:hover { opacity: 0.9; transform: translateY(-1px); box-shadow: 0 4px 10px var(--border-color); }

/* --- TABLE --- */
.table-card { background: var(--card-bg); border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.03); overflow-x: auto; flex-grow: 1; }
table { width: 100%; border-collapse: collapse; }
th { background: var(--bg-main); text-align: left; padding: 15px 15px; font-size: 0.7rem; text-transform: uppercase; color: var(--text-muted); border-bottom: 1px solid var(--border-color); position: sticky; top: 0; }
td { padding: 15px 15px; font-size: 0.85rem; border-bottom: 1px solid var(--border-color); transition: background 0.2s ease; }

/* Interactive Rows */
#timesheetTable tbody tr { cursor: pointer; user-select: none; }
tr:hover { background: rgba(44, 160, 120, 0.04); }
tr.row-selected td { background-color: var(--brand-green-light); border-bottom-color: rgba(44, 160, 120, 0.2); }

.table-controls { padding: 15px 20px; border-top: 1px solid var(--border-color); display: flex; gap: 10px; background: var(--bg-main); border-radius: 0 0 12px 12px; }

/* Badges */
.badge-yes { font-size: 0.75rem; font-weight: 700; color: var(--brand-green); background: rgba(44, 160, 120, 0.1); padding: 4px 8px; border-radius: 6px; }
.badge-no { font-size: 0.75rem; font-weight: 700; color: var(--text-muted); background: var(--bg-main); padding: 4px 8px; border-radius: 6px; }
.text-green { color: var(--brand-green); }
.status-badge { padding: 4px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 700; }
.bg-approved { background: rgba(44, 160, 120, 0.12); color: var(--brand-green); }
.bg-pending { background: rgba(245, 158, 11, 0.12); color: #f59e0b; }

@media (max-width: 992px) { .main-content { margin-left: 0; width: 100%; padding: 1.5rem; } .page-header { flex-direction: column; } .search-box-wrapper { width: 100%; } .filter-dropdown { right: auto; left: 0; } }
    </style>
</head>
<body class="<?php echo (isset($_SESSION['theme']) && $_SESSION['theme'] == 'dark') ? 'dark-mode' : ''; ?>">

<div class="main-content">
    
    <div class="page-header">
        <div>
            <h1>Department Timesheets</h1>
            <p class="subtitle">Period: Feb 01 - Feb 28, 2026</p>
        </div>
        
        <div class="dept-selector">
            <label for="deptFilter" style="font-size: 0.85rem; color: var(--text-muted); font-weight: 600;"> Department:</label>
            <select id="deptFilter" class="form-select" onchange="filterTimesheet()">

                <option value="logistics" selected>Logistics</option>
                
            </select>
        </div>
        <button class="theme-toggle" id="themeToggle">
                    <i data-lucide="sun" class="sun-icon"></i>
                    <i data-lucide="moon" class="moon-icon"></i>
                </button>
    </div>

    <div class="kpi-row">
        <div class="kpi-card">
            <div class="kpi-icon icon-blue"><i class="fas fa-users"></i></div>
            <div>
                <span class="kpi-val" id="totalEmployees">0</span>
                <span class="kpi-lab">Filtered Employees</span>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon icon-green"><i class="fas fa-stopwatch"></i></div>
            <div>
                <span class="kpi-val" id="totalHours">0.00</span>
                <span class="kpi-lab">Filtered Payable Hours</span>
            </div>
        </div>
    </div>

    <div class="toolbar">
        <div class="search-box-wrapper">
            <i class="fas fa-search search-icon"></i>
            <input type="text" id="searchInput" class="search-box" placeholder="Search employee..." onkeyup="filterTimesheet()">
        </div>
        <div class="action-buttons">
            
            <div class="filter-container">
                <button class="btn btn-outline" onclick="toggleFilterMenu()">
                    <i class="fas fa-filter"></i> Columns <i class="fas fa-chevron-down" style="font-size: 0.7rem; margin-left: 5px;"></i>
                </button>
                
                <div class="filter-dropdown" id="filterMenu">
                    <h3 style="margin: 0 0 10px 0; font-size: 0.85rem; font-weight: 700; border-bottom: 1px solid var(--border-color); padding-bottom: 8px;">Displayed Data</h3>
                    
                    <label class="filter-checkbox"><input type="checkbox" data-col="col-role" checked> Role</label>
                    <label class="filter-checkbox"><input type="checkbox" data-col="col-reg" checked> Regular Hours</label>
                    <label class="filter-checkbox"><input type="checkbox" data-col="col-ot" checked> Overtime</label>
                    <label class="filter-checkbox"><input type="checkbox" data-col="col-abs" checked> Absences</label>
                    
                    <h4 style="font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; margin: 12px 0 8px;">Leave Tracking</h4>
                    <label class="filter-checkbox"><input type="checkbox" data-col="col-lc" checked> Leave Credit</label>
                    <label class="filter-checkbox"><input type="checkbox" data-col="col-pl" checked> Paid Leave Usage</label>
                    <label class="filter-checkbox"><input type="checkbox" data-col="col-el" checked> Excess Leave (Unpaid)</label>
                    
                    <h4 style="font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; margin: 12px 0 8px;">Final Computation</h4>
                    <label class="filter-checkbox"><input type="checkbox" data-col="col-ded" checked> Deductions</label>
                    <label class="filter-checkbox"><input type="checkbox" checked disabled> Final Hours (Required)</label>
                    <label class="filter-checkbox"><input type="checkbox" checked disabled> Status (Required)</label>

                    <button class="btn btn-primary" style="width: 100%; justify-content: center; margin-top: 15px; padding: 8px;" onclick="applyFilters()">
                        Apply View
                    </button>
                </div>
            </div>

            <button class="btn btn-outline" onclick="exportTable()">
                <i class="fas fa-file-excel"></i> Convert to Excel
            </button>
            <button class="btn btn-primary" onclick="archiveSelected()">
                <i class="fas fa-archive"></i> Archive Selected
            </button>
        </div>
    </div>



    <div class="table-card">
        <table id="timesheetTable">
            <thead>
                <tr>
                    <th class="col-emp">Employee</th>
                    <th class="col-role">Role</th>
                    <th class="col-reg">Regular</th>
                    <th class="col-ot">Overtime</th>
                    <th class="col-abs">Absences</th>
                    <th class="col-lc">Leave Credit</th>
                    <th class="col-pl">Paid Leave</th>
                    <th class="col-el">Excess Leave</th>
                    <th class="col-ded">Deductions</th>
                    <th class="col-fh">Final Hours</th>
                    <th class="col-stat">Status</th>
                </tr>
            </thead>
            <tbody id="tsBody">
                <tr data-department="logistics" onclick="toggleRowSelection(this)">
                    <td class="col-emp"><strong>Bob Logistics</strong></td>
                    <td class="col-role">Warehouse Supervisor</td>
                    <td class="col-reg">72.00</td>
                    <td class="col-ot">0.00</td>
                    <td class="col-abs">12.00</td>
                    <td class="col-lc">8.00</td>
                    <td class="col-pl"><span class="badge-yes">Yes (8h)</span></td>
                    <td class="col-el"><strong style="color: #e74c3c;">4.00</strong></td>
                    <td class="col-ded">4.00</td>
                    <td class="col-fh"><strong>76.00</strong></td>
                    <td class="col-stat"><span class="status-badge bg-pending">Pending</span></td>
                </tr>
                <tr data-department="it" onclick="toggleRowSelection(this)">
                    <td class="col-emp"><strong>Alice Dev</strong></td>
                    <td class="col-role">Software Engineer</td>
                    <td class="col-reg">80.00</td>
                    <td class="col-ot">5.00</td>
                    <td class="col-abs">0.00</td>
                    <td class="col-lc">16.00</td>
                    <td class="col-pl"><span class="badge-no">No</span></td>
                    <td class="col-el">0.00</td>
                    <td class="col-ded">0.00</td>
                    <td class="col-fh"><strong class="text-green">85.00</strong></td>
                    <td class="col-stat"><span class="status-badge bg-approved">Review</span></td>
                </tr>
            </tbody>
        </table>
        
        <div class="table-controls">
            <button class="btn btn-outline btn-sm" onclick="selectAllVisible()">Select All</button>
            <button class="btn btn-outline btn-sm" onclick="resetSelection()">Reset</button>
        </div>
    </div>
</div>

<script>
    const tbody = document.getElementById('tsBody');

    // Populate extra demo rows dynamically
    const departments = ['logistics', 'it', 'finance'];
    for(let i=3; i<=12; i++){
        const dept = departments[i % 3];
        const regular = 80;
        
        // Simulating random leave data for demo purposes
        const abs = (i % 4 === 0) ? 16 : 0; 
        const lc = 8.00;
        const paidLve = (abs > 0 && abs <= lc) ? abs : (abs > lc ? lc : 0);
        const excess = (abs > lc) ? (abs - lc) : 0;
        const ded = excess; // Deduct only the excess
        const finalHours = regular - ded;

        const row = document.createElement('tr');
        row.setAttribute('data-department', dept);
        row.setAttribute('onclick', 'toggleRowSelection(this)');
        row.innerHTML = `
            <td class="col-emp"><strong>Employee ${i}</strong></td>
            <td class="col-role">${dept.charAt(0).toUpperCase() + dept.slice(1)} Staff</td>
            <td class="col-reg">${regular.toFixed(2)}</td>
            <td class="col-ot">0.00</td>
            <td class="col-abs">${abs.toFixed(2)}</td>
            <td class="col-lc">${lc.toFixed(2)}</td>
            <td class="col-pl">${paidLve > 0 ? `<span class="badge-yes">Yes (${paidLve}h)</span>` : `<span class="badge-no">No</span>`}</td>
            <td class="col-el"><strong style="color: ${excess > 0 ? '#e74c3c' : 'inherit'}">${excess.toFixed(2)}</strong></td>
            <td class="col-ded">${ded.toFixed(2)}</td>
            <td class="col-fh"><strong>${finalHours.toFixed(2)}</strong></td>
            <td class="col-stat"><span class="status-badge bg-approved">Reviewed</span></td>
        `;
        tbody.appendChild(row);
    }

    // --- Interactive Functions ---
    
    function toggleRowSelection(row) {
        row.classList.toggle('row-selected');
    }

    function selectAllVisible() {
        const rows = tbody.getElementsByTagName('tr');
        for(let row of rows) {
            if (row.style.display !== 'none') {
                row.classList.add('row-selected');
            }
        }
    }

    function resetSelection() {
        const rows = tbody.getElementsByTagName('tr');
        for(let row of rows) {
            row.classList.remove('row-selected');
        }
    }

    // --- Dropdown Filter Logic ---
    
    function toggleFilterMenu() {
        document.getElementById('filterMenu').classList.toggle('show');
    }

    // Close dropdown if clicked outside
    window.onclick = function(event) {
        if (!event.target.closest('.filter-container')) {
            const dropdowns = document.getElementsByClassName("filter-dropdown");
            for (let i = 0; i < dropdowns.length; i++) {
                let openDropdown = dropdowns[i];
                if (openDropdown.classList.contains('show')) {
                    openDropdown.classList.remove('show');
                }
            }
        }
    }

    function toggleColumn(className, isVisible) {
        const cells = document.querySelectorAll('.' + className);
        cells.forEach(cell => {
            cell.style.display = isVisible ? '' : 'none';
        });
    }

    function applyFilters() {
        const checkboxes = document.querySelectorAll('.filter-dropdown input[type="checkbox"][data-col]');
        checkboxes.forEach(cb => {
            toggleColumn(cb.getAttribute('data-col'), cb.checked);
        });
        document.getElementById('filterMenu').classList.remove('show');
    }

    // --- Core Features ---

    function filterTimesheet() {
        const searchInput = document.getElementById('searchInput').value.toLowerCase();
        const deptFilter = document.getElementById('deptFilter').value;
        const rows = tbody.getElementsByTagName('tr');

        for(let row of rows) {
            const text = row.innerText.toLowerCase();
            const dept = row.getAttribute('data-department');
            const matchesSearch = text.includes(searchInput);
            const matchesDept = (deptFilter === 'all' || dept === deptFilter);

            if (matchesSearch && matchesDept) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
                row.classList.remove('row-selected');
            }
        }
        calculateTotals();
    }

    function calculateTotals(){
        let total = 0;
        let visibleCount = 0;
        for(let row of tbody.rows){
            if (row.style.display !== 'none') {
                const finalHourText = row.querySelector('.col-fh').innerText;
                total += parseFloat(finalHourText); 
                visibleCount++;
            }
        }
        document.getElementById('totalEmployees').innerText = visibleCount;
        document.getElementById('totalHours').innerText = total.toLocaleString(undefined, {minimumFractionDigits: 2});
    }

    filterTimesheet();

    function exportTable(){
        let tableClone = document.getElementById('timesheetTable').cloneNode(true);
        
        let rows = tableClone.getElementsByTagName('tr');
        for(let row of rows) { row.classList.remove('row-selected'); }
        
        let cells = tableClone.querySelectorAll('th, td');
        cells.forEach(cell => { cell.style.display = ''; });
        
        let tableHTML = tableClone.outerHTML;
        let a = document.createElement('a');
        a.href = 'data:application/vnd.ms-excel,' + encodeURIComponent(tableHTML);
        a.download = 'hr4_department_timesheet_converted.xls';
        a.click();
    }

    function archiveSelected(){
        const selectedRows = document.querySelectorAll('.row-selected');
        if(selectedRows.length === 0) return alert("Please select records to archive.");
        selectedRows.forEach(row => row.remove());
        calculateTotals();
    }
</script>
<script src="../../js/officer/roster.js"></script>
    <script src="../../js/sidebar-active.js"></script>
    <script src="../../js/officer/dashboard.js"></script> 
    <script src="../../js/user-menu.js"></script>
</body>
</html>