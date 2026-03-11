<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HR Manager - Shift Approval</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --brand-green: #2ca078;
            --brand-green-dark: #228b67;
            --background: #f8fafc;
            --surface: #ffffff;
            --surface-hover: #f1f5f9;
            --card-bg: #ffffff;
            --text-primary: #0f172a;
            --text-secondary: #475569;
            --text-tertiary: #94a3b8;
            --border-color: #e2e8f0;
            --danger: #ef4444;
            --danger-soft: rgba(239, 68, 68, .12);
            --warning-soft: rgba(245, 158, 11, .14);
            --info-soft: rgba(59, 130, 246, .12);
            --night-soft: rgba(99, 102, 241, .12);
            --leave-soft: rgba(168, 85, 247, .14);
            --shadow: 0 10px 18px rgba(15, 23, 42, .06);
            --shadow-lg: 0 18px 30px rgba(15, 23, 42, .10);
            --sidebar-width: 280px;
            --radius: 16px;
            --transition-slow: all .25s cubic-bezier(.4, 0, .2, 1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: var(--background);
            color: var(--text-primary);
            line-height: 1.6;
        }

        /* Layout Structure */
        .main-content {
            min-height: 100vh;
            padding-bottom: 50px;
        }

        /* 1️⃣ Header Section */
        .page-header {
            position: sticky; top: 0; z-index: 100; min-height: 90px; padding: 18px 28px;
            display: flex; align-items: center; justify-content: space-between; gap: 20px;
            background: var(--surface); border-bottom: 1px solid var(--border-color);
        }
        .header-left h1 { font-size: 24px; font-weight: 900; letter-spacing: -.03em; }
        .page-top-meta { display: flex; gap: 10px; margin-top: 8px; flex-wrap: wrap; }
        
        .status-badge, .mini-info {
            display: flex; align-items: center; gap: 7px; padding: 6px 12px;
            border: 1px solid var(--border-color); border-radius: 999px;
            font-size: 11px; font-weight: 800; letter-spacing: .02em;
        }
        .status-badge.for-review { background: var(--info-soft); color: #2563eb; border-color: rgba(59,130,246,.28); }
        .mini-info { background: var(--background); color: var(--text-secondary); }

        /* 2️⃣ Stats Grid */
        .roster-layout { padding: 28px; display: flex; flex-direction: column; gap: 24px; }
        .roster-stats { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); 
            gap: 16px; 
        }
        .stat-card { 
            background: var(--surface); padding: 18px; border-radius: var(--radius);
            border: 1px solid var(--border-color); box-shadow: var(--shadow);
            transition: var(--transition-slow);
        }
        .stat-card:hover { transform: translateY(-2px); box-shadow: var(--shadow-lg); }
        .stat-top { display: flex; justify-content: space-between; margin-bottom: 10px; }
        .stat-label { font-size: 11px; font-weight: 900; text-transform: uppercase; color: var(--text-tertiary); }
        .stat-value { font-size: 26px; font-weight: 900; color: var(--text-primary); }

        /* 3️⃣ Conflict Section */
        .ai-review-panel {
            padding: 20px; border-radius: var(--radius); border: 1px solid rgba(239, 68, 68, 0.2);
            background: linear-gradient(to bottom, rgba(239, 68, 68, 0.04), rgba(239, 68, 68, 0.01));
        }
        .ai-review-columns { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-top: 15px; }
        .review-list-card { 
            background: var(--surface); padding: 16px; border-radius: 12px; 
            border: 1px solid var(--border-color);
        }
        .review-list-card h5 { font-size: 13px; font-weight: 900; margin-bottom: 10px; color: var(--danger); }
        .review-list { list-style: none; font-size: 12px; color: var(--text-secondary); font-weight: 600; }
        .review-list li { margin-bottom: 6px; display: flex; gap: 8px; }

        /* 4️⃣ Table Section */
        .content-card { 
            background: var(--surface); border: 1px solid var(--border-color); 
            border-radius: var(--radius); overflow: hidden; box-shadow: var(--shadow);
        }
        .card-header-block { padding: 18px; border-bottom: 1px solid var(--border-color); }
        .card-title { font-size: 16px; font-weight: 900; }
        
        .roster-table-wrapper { overflow-x: auto; }
        .roster-table { width: 100%; border-collapse: collapse; font-size: 13px; min-width: 800px; }
        .roster-table th { 
            background: var(--background); padding: 12px; text-align: center;
            font-size: 11px; font-weight: 900; text-transform: uppercase; color: var(--text-tertiary);
            border-bottom: 1px solid var(--border-color);
        }
        .roster-table td { padding: 12px; text-align: center; border-bottom: 1px solid var(--border-color); }
        .emp-col { text-align: left !important; position: sticky; left: 0; background: var(--surface); border-right: 1px solid var(--border-color); width: 250px; }
        .emp-name { display: block; font-weight: 800; color: var(--text-primary); }
        .emp-pos { font-size: 10px; color: var(--text-tertiary); text-transform: uppercase; }

        /* Shift Pills */
        .shift-pill {
            display: inline-flex; justify-content: center; padding: 6px 12px;
            border-radius: 999px; font-size: 11px; font-weight: 900; min-width: 60px;
        }
        .shift-morning { background: var(--info-soft); color: #1d4ed8; border: 1px solid rgba(59,130,246,.24); }
        .shift-afternoon { background: var(--warning-soft); color: #b45309; border: 1px solid rgba(245,158,11,.24); }
        .shift-off { background: var(--danger-soft); color: #dc2626; border: 1px solid rgba(239,68,68,.24); }

        /* 5️⃣ Actions Section */
        .action-card { padding: 24px; background: var(--surface); border-radius: var(--radius); border: 1px solid var(--border-color); }
        .manager-remarks {
            width: 100%; min-height: 100px; padding: 15px; border-radius: 12px;
            border: 1px solid var(--border-color); background: var(--background);
            font-family: inherit; margin-bottom: 20px; resize: vertical;
        }
        .btn-group { display: flex; justify-content: flex-end; gap: 12px; }
        
        .btn-primary, .btn-secondary, .btn-danger {
            padding: 12px 24px; border-radius: 12px; font-size: 14px; font-weight: 800;
            cursor: pointer; transition: var(--transition-slow); border: 1px solid transparent;
            display: flex; align-items: center; gap: 8px;
        }
        .btn-primary { background: var(--brand-green); color: white; box-shadow: 0 10px 18px rgba(44,160,120,.22); }
        .btn-primary:hover { background: var(--brand-green-dark); transform: translateY(-1px); }
        .btn-secondary { background: white; border-color: var(--border-color); color: var(--text-secondary); }
        .btn-secondary:hover { background: var(--surface-hover); color: var(--brand-green); }
        .btn-danger { background: white; border-color: var(--danger-soft); color: var(--danger); }
        .btn-danger:hover { background: var(--danger-soft); }

    </style>
</head>
<body>

<main class="main-content">
    <header class="page-header">
        <div class="header-left">
            <h1>Shift & Scheduling Approval</h1>
            <div class="page-top-meta">
                <span class="mini-info"><i class="fa-solid fa-building"></i> Operations</span>
                <span class="mini-info"><i class="fa-solid fa-calendar"></i> March 1 – March 12, 2026</span>
                <span class="mini-info"><i class="fa-solid fa-user-pen"></i> Officer Juan</span>
            </div>
        </div>
        <div class="header-right">
            <span class="status-badge for-review">FOR APPROVAL</span>
        </div>
    </header>

    <div class="roster-layout">
        
        <section class="roster-stats">
            <div class="stat-card">
                <div class="stat-top">
                    <span class="stat-label">Total Staff</span>
                    <i class="fa-solid fa-users" style="color: var(--brand-green)"></i>
                </div>
                <span class="stat-value">24</span>
            </div>
            <div class="stat-card">
                <div class="stat-top">
                    <span class="stat-label">AM Shift Avg</span>
                    <i class="fa-solid fa-sun" style="color: #3b82f6"></i>
                </div>
                <span class="stat-value">12</span>
            </div>
            <div class="stat-card">
                <div class="stat-top">
                    <span class="stat-label">PM Shift Avg</span>
                    <i class="fa-solid fa-moon" style="color: #f59e0b"></i>
                </div>
                <span class="stat-value">12</span>
            </div>
            <div class="stat-card">
                <div class="stat-top">
                    <span class="stat-label">Total Off Days</span>
                    <i class="fa-solid fa-couch" style="color: #ef4444"></i>
                </div>
                <span class="stat-value">24</span>
            </div>
        </section>

        <div class="ai-review-panel">
            <div style="display: flex; align-items: center; gap: 10px;">
                <i class="fa-solid fa-triangle-exclamation" style="color: var(--danger)"></i>
                <h4 style="font-weight: 900;">Validation Checks</h4>
            </div>
            <div class="ai-review-columns">
                <div class="review-list-card" style="border-left: 4px solid var(--danger);">
                    <h5>Critical Conflicts</h5>
                    <ul class="review-list">
                        <li><i class="fa-solid fa-circle-exclamation"></i> Employee 32: No schedule on March 5</li>
                        <li><i class="fa-solid fa-circle-exclamation"></i> Employee 22: Overlapping shifts (AM/GY)</li>
                    </ul>
                </div>
                <div class="review-list-card" style="border-left: 4px solid #f59e0b;">
                    <h5>Rule Warnings</h5>
                    <ul class="review-list">
                        <li><i class="fa-solid fa-clock"></i> Employee 40: Exceeds 6 workdays limit</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="content-card">
            <div class="card-header-block">
                <h3 class="card-title">Schedule Preview (Review Only)</h3>
            </div>
            <div class="roster-table-wrapper">
                <table class="roster-table">
                    <thead>
                        <tr>
                            <th class="emp-col">Employee Name</th>
                            <th>Mon (1)</th>
                            <th>Tue (2)</th>
                            <th>Wed (3)</th>
                            <th>Thu (4)</th>
                            <th>Fri (5)</th>
                            <th>Sat (6)</th>
                            <th>Sun (7)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="emp-col">
                                <span class="emp-name">Juan Dela Cruz</span>
                                <span class="emp-pos">Customer Support</span>
                            </td>
                            <td><span class="shift-pill shift-morning">AM</span></td>
                            <td><span class="shift-pill shift-morning">AM</span></td>
                            <td><span class="shift-pill shift-afternoon">PM</span></td>
                            <td><span class="shift-pill shift-afternoon">PM</span></td>
                            <td><span class="shift-pill shift-morning">AM</span></td>
                            <td><span class="shift-pill shift-off">OFF</span></td>
                            <td><span class="shift-pill shift-off">OFF</span></td>
                        </tr>
                        <tr>
                            <td class="emp-col">
                                <span class="emp-name">Maria Santos</span>
                                <span class="emp-pos">Billing Officer</span>
                            </td>
                            <td><span class="shift-pill shift-afternoon">PM</span></td>
                            <td><span class="shift-pill shift-afternoon">PM</span></td>
                            <td><span class="shift-pill shift-morning">AM</span></td>
                            <td><span class="shift-pill shift-morning">AM</span></td>
                            <td><span class="shift-pill shift-afternoon">PM</span></td>
                            <td><span class="shift-pill shift-off">OFF</span></td>
                            <td><span class="shift-pill shift-off">OFF</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="action-card">
            <h3 class="card-title" style="margin-bottom: 15px;">Approval Actions</h3>
            <textarea class="manager-remarks" placeholder="Enter feedback for the officer (e.g., Please fix conflicts for Employee 32...)"></textarea>
            
            <div class="btn-group">
                <button class="btn-danger"><i class="fa-solid fa-circle-xmark"></i> Reject</button>
                <button class="btn-secondary"><i class="fa-solid fa-reply"></i> Return to Officer</button>
                <button class="btn-primary"><i class="fa-solid fa-cloud-arrow-up"></i> Approve & Publish</button>
            </div>
        </div>

    </div>
</main>

</body>
</html>