<?php
// modules/officer/employees.php
require_once __DIR__ . "/auth_officer.php"; 
$current_page = 'employees';
include('sidebar.php'); // Siguraduhin na ang sidebar ay walang echo bago ang session_start
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Directory | Officer</title>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../css/officer/dashboard.css">
    <link rel="stylesheet" href="../../css/sidebar-fix.css?v=1.0">
    <link rel="stylesheet" href="../../css/officer/roster.css"> 
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        /* ================= CSS CORE ================= */
        :root {
            --brand-green: #2CA078;
            --brand-dark: #f4f7f6;
            --card-bg: #ffffff;
            --text-main: #1a1a1a;
            --text-muted: #64748b;
            --border-subtle: rgba(0, 0, 0, 0.08);
            --bg-surface: #f1f5f9;
            --radius-xl: 14px;
            --transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .dark-mode {
            --brand-dark: #101111;
            --card-bg: #1b1c1d;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --border-subtle: rgba(255, 255, 255, 0.06);
            --bg-surface: #1b1c1d;
        }

        body {
            background-color: var(--brand-dark);
            color: var(--text-main);
            font-family: 'Inter', sans-serif;
            margin: 0;
        }

        .main-content {
            margin-left: 260px; /* Aligned with sidebar */
            padding: 2.5rem;
            width: calc(100% - 260px);
            box-sizing: border-box;
        }

        /* --- Page Header --- */
        .page-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2.5rem;
        }

        .kicker {
            font-size: 0.7rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: var(--brand-green);
        }

        .page-title {
            font-size: 2rem;
            font-weight: 800;
            margin: 5px 0;
        }

        /* --- KPI Ribbon --- */
        .kpi-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
            margin-bottom: 2.5rem;
        }

        .kpi-card {
            background: var(--card-bg);
            padding: 1.5rem;
            border-radius: var(--radius-xl);
            border: 1px solid var(--border-subtle);
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .kpi-icon {
            width: 45px;
            height: 45px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(5, 5, 5, 0.1);
            color: var(--brand-green);
        }

        /* --- Buttons --- */
        .btn {
            padding: 0.8rem 1.5rem;
            border-radius: 10px;
            border: none;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary { background: var(--brand-green); color: white; }

        /* --- Tabs --- */
        .tabs {
            display: inline-flex;
            background: var(--bg-surface);
            padding: 5px;
            border-radius: 12px;
            margin-bottom: 2rem;
        }

        .tab {
            padding: 0.6rem 1.25rem;
            border: none;
            background: transparent;
            color: var(--text-muted);
            font-weight: 700;
            cursor: pointer;
            border-radius: 8px;
        }

        .tab.active {
            background: var(--card-bg);
            color: var(--brand-green);
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        }
    </style>
    
</head>
<body class="<?php echo (isset($_SESSION['theme']) && $_SESSION['theme'] == 'dark') ? 'dark-mode' : ''; ?>">

<div class="main-content">
    <div class="page-head">
        <div>
<button class="theme-toggle" id="themeToggle" aria-label="Toggle theme">
          <i data-lucide="sun" class="sun-icon"></i>
          <i data-lucide="moon" class="moon-icon"></i>
        </button>
            <div class="kicker">Workforce Management</div>
            <h1 class="page-title">Employee Directory</h1>
            <p class="page-sub">Managing staff under <?php echo htmlspecialchars($deptName); ?></p>
        </div>
        
        <div class="page-actions">
            <button class="btn btn-primary">
                <i class="fas fa-plus"></i> Add Employee
</button>

        
        </div>
    </div>

    <div class="kpi-row">
        <div class="kpi-card">
            <div class="kpi-icon icon-team"><i class="fas fa-user-tie"></i></div>
            <div>
                <span class="kpi-val">42</span>
                <span class="kpi-lab">Active Staff</span>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon icon-today"><i class="fas fa-user-check"></i></div>
            <div>
                <span class="kpi-val">38</span>
                <span class="kpi-lab">Present Today</span>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon icon-pending"><i class="fas fa-user-clock"></i></div>
            <div>
                <span class="kpi-val">4</span>
                <span class="kpi-lab">On Leave</span>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="toolbar">
            
            
            <div class="tabs" style="margin-bottom: 0;">
                <button class="tab active">All</button>
                <button class="tab">Full-time</button>
                <button class="tab">Part-time</button>
            </div>
        </div>

        <table class="data-table" id="employeeTable">
            <thead>
                <tr>
                    <th>Employee Name</th>
                    <th>Employee ID</th>
                    <th>Position</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <div class="emp-info">
                            <div class="emp-avatar">JD</div>
                            <div>
                                <div style="font-weight: 700;">John Doe</div>
                                <div style="font-size: 0.75rem; color: var(--text-muted);">john.doe@company.com</div>
                            </div>
                        </div>
                    </td>
                    <td><code style="background: var(--bg-surface); padding: 2px 6px; border-radius: 4px;">EMP-2026-001</code></td>
                    <td>Senior Loan Officer</td>
                    <td><span class="status-badge bg-pending" style="background: rgba(44, 160, 120, 0.1); color: var(--brand-green); border-color: transparent;">Active</span></td>
                    <td>
                        <button class="btn-outline" style="padding: 6px 10px; border-radius: 6px;"><i class="fas fa-eye"></i></button>
                        <button class="btn-outline" style="padding: 6px 10px; border-radius: 6px;"><i class="fas fa-edit"></i></button>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div class="emp-info">
                            <div class="emp-avatar" style="color: #f59e0b;">AS</div>
                            <div>
                                <div style="font-weight: 700;">Alice Smith</div>
                                <div style="font-size: 0.75rem; color: var(--text-muted);">alice.s@company.com</div>
                            </div>
                        </div>
                    </td>
                    <td><code style="background: var(--bg-surface); padding: 2px 6px; border-radius: 4px;">EMP-2026-014</code></td>
                    <td>Credit Analyst</td>
                    <td><span class="status-badge bg-pending">On Leave</span></td>
                    <td>
                        <button class="btn-outline" style="padding: 6px 10px; border-radius: 6px;"><i class="fas fa-eye"></i></button>
                        <button class="btn-outline" style="padding: 6px 10px; border-radius: 6px;"><i class="fas fa-edit"></i></button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script>
    // Initialize Lucide Icons
    lucide.createIcons();

    function filterEmployees() {
        const input = document.getElementById('empSearch').value.toLowerCase();
        const rows = document.querySelectorAll('#employeeTable tbody tr');
        
        rows.forEach(row => {
            const text = row.innerText.toLowerCase();
            row.style.display = text.includes(input) ? '' : 'none';
        });
    }

    function toggleTheme() {
        document.body.classList.toggle('dark-mode');
        const isDark = document.body.classList.contains('dark-mode');
        document.getElementById('themeIcon').className = isDark ? 'fas fa-sun' : 'fas fa-moon';
        
        fetch('update_theme.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'theme=' + (isDark ? 'dark' : 'light')
        });
    }
</script>
<script src="../../js/officer/roster.js"></script>
    <script src="../../js/sidebar-active.js"></script>
    <script src="../../js/officer/dashboard.js"></script> 
    <script src="../../js/user-menu.js"></script>
</body>
</html>