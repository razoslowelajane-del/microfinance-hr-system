<?php
require_once "includes/auth_hr_manager.php";
require_once "../../config/config.php";

$managerName = $_SESSION['Username'] ?? 'HR Manager';

// Database Counts
$rosterCount = $conn->query("SELECT COUNT(*) as total FROM weekly_roster WHERE Status = 'FOR_REVIEW'")->fetch_assoc()['total'] ?? 0;
$leaveCount  = $conn->query("SELECT COUNT(*) as total FROM leave_requests WHERE Status = 'PENDING'")->fetch_assoc()['total'] ?? 0;
$claimCount  = $conn->query("SELECT COUNT(*) as total FROM reimbursement_claims WHERE Status = 'PENDING'")->fetch_assoc()['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager Dashboard | HR3</title>
    <link rel="stylesheet" href="../../css/officer/dashboard.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="theme-controlled">

    <?php include "sidebar.php"; ?>

    <main class="main-content">
        <header class="page-header">
            <div class="header-left">
                <div class="header-title">
                    <h1>Manager Dashboard</h1>
                    <p>Mabuhay, <?php echo htmlspecialchars($managerName); ?>!</p>
                </div>
            </div>
            <div class="header-right">
                <?php include "theme.php"; ?>
            </div>
        </header>

        <div class="content-wrapper">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="background: rgba(44, 160, 120, 0.1); color: var(--brand-green);"><i data-lucide="calendar-check"></i></div>
                    <div class="stat-content">
                        <span class="stat-label">Rosters for Review</span>
                        <span class="stat-value"><?php echo $rosterCount; ?></span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background: rgba(59, 130, 246, 0.1); color: #3b82f6;"><i data-lucide="user-plus"></i></div>
                    <div class="stat-content">
                        <span class="stat-label">Pending Leaves</span>
                        <span class="stat-value"><?php echo $leaveCount; ?></span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background: rgba(255, 193, 7, 0.1); color: var(--brand-yellow);"><i data-lucide="banknote"></i></div>
                    <div class="stat-content">
                        <span class="stat-label">Pending Claims</span>
                        <span class="stat-value"><?php echo $claimCount; ?></span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background: rgba(239, 68, 68, 0.1); color: #ef4444;"><i data-lucide="alert-octagon"></i></div>
                    <div class="stat-content">
                        <span class="stat-label">Geo/Face Flags</span>
                        <span class="stat-value">0</span>
                    </div>
                </div>
            </div>

            <div class="content-grid">
                <div class="content-card">
                    <div class="card-header">
                        <h3 class="card-title">Pending Roster Reviews</h3>
                    </div>
                    <div class="card-body">
                        <div class="data-table">
                            <?php
                            $resList = $conn->query("SELECT wr.*, d.DepartmentName FROM weekly_roster wr JOIN department d ON wr.DepartmentID = d.DepartmentID WHERE wr.Status = 'FOR_REVIEW' LIMIT 5");
                            if ($resList->num_rows > 0):
                                while($row = $resList->fetch_assoc()): ?>
                                    <div class="table-row">
                                        <div class="client-info">
                                            <div class="client-avatar" style="background: var(--brand-green);"><?php echo substr($row['DepartmentName'],0,1); ?></div>
                                            <div>
                                                <p class="client-name"><?php echo $row['DepartmentName']; ?></p>
                                                <p class="client-detail">Coverage: <?php echo date('M d', strtotime($row['WeekStart'])); ?> - <?php echo date('d', strtotime($row['WeekEnd'])); ?></p>
                                            </div>
                                        </div>
                                        <span class="badge-status review">Reviewing</span>
                                        <button class="btn-text" onclick="location.href='detailed_review.php?id=<?php echo $row['RosterID']; ?>'">Review Schedule</button>
                                    </div>
                                <?php endwhile; 
                            else: ?>
                                <p style="text-align:center; color: var(--text-muted); padding: 20px;">All rosters are cleared.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="content-card">
                    <div class="card-header"><h3 class="card-title">Quick Actions</h3></div>
                    <div class="card-body">
                        <div class="quick-actions">
                            <button class="action-btn" onclick="location.href='employees.php'"><i data-lucide="users"></i> Employees</button>
                            <button class="action-btn" onclick="location.href='roster_review.php'"><i data-lucide="calendar"></i> Rosters</button>
                            <button class="action-btn" onclick="location.href='settings.php'"><i data-lucide="settings"></i> Policies</button>
                            <button class="action-btn" onclick="location.href='security.php'"><i data-lucide="lock"></i> Security</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <script>lucide.createIcons();</script>
</body>
</html>