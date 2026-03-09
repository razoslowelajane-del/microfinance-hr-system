<?php
require_once "includes/auth_hr_manager.php";
require_once "../../config/config.php";

// Fetch all rosters waiting for HR Manager action
$query = "SELECT wr.*, d.DepartmentName, ua.Username as CreatedBy 
          FROM weekly_roster wr 
          JOIN department d ON wr.DepartmentID = d.DepartmentID 
          JOIN useraccounts ua ON wr.CreatedByAccountID = ua.AccountID
          WHERE wr.Status = 'FOR_REVIEW' 
          ORDER BY wr.CreatedAt DESC";
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Roster Review Queue | HR3</title>
    
    <link rel="stylesheet" href="../../css/manager/roster_review.css">
    
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="theme-controlled">

    <?php include "sidebar.php"; ?>

    <main class="main-content">
        <header class="page-header">
            <div class="header-left">
                <h1>Roster Review Queue</h1>
                <p>Approve or return department schedules for the current period</p>
            </div>
            <div class="header-right">
                <?php include "theme.php"; ?>
            </div>
        </header>

        <div class="content-wrapper">
            <div class="content-card">
                <div class="card-header">
                    <h3 class="card-title">Pending Submissions</h3>
                </div>
                <div class="card-body" style="padding: 0;">
                    <div class="data-table">
                        <?php if($result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                                <div class="table-row" style="grid-template-columns: 2fr 1fr 1fr 1fr;">
                                    <div class="client-info">
                                        <div class="client-avatar" style="background: var(--brand-green);">
                                            <?php echo substr($row['DepartmentName'], 0, 1); ?>
                                        </div>
                                        <div>
                                            <p class="client-name"><?php echo htmlspecialchars($row['DepartmentName']); ?></p>
                                            <p class="client-detail">
                                                <i data-lucide="calendar" style="width:12px; display:inline; margin-bottom:-2px;"></i>
                                                <?php echo date('M d', strtotime($row['WeekStart'])); ?> - <?php echo date('M d, Y', strtotime($row['WeekEnd'])); ?>
                                            </p>
                                        </div>
                                    </div>
                                    
                                    <div>
                                        <p class="client-detail">Submitted By</p>
                                        <p class="client-name" style="font-size: 13px;">
                                            <i data-lucide="user-check" style="width:12px; display:inline; margin-bottom:-2px;"></i>
                                            <?php echo htmlspecialchars($row['CreatedBy']); ?>
                                        </p>
                                    </div>

                                    <div style="display:flex; justify-content:center;">
                                        <span class="badge-status review">FOR REVIEW</span>
                                    </div>

                                    <div style="display:flex; justify-content:flex-end;">
                                        <button class="action-btn" onclick="location.href='roster_view.php?id=<?php echo $row['RosterID']; ?>'">
                                            <i data-lucide="eye"></i> Open Review
                                        </button>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="empty-msg">
                                <i data-lucide="clipboard-check" style="width:48px; height:48px; margin-bottom:1rem; opacity:0.3;"></i>
                                <p>All caught up! No rosters are pending for review.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        lucide.createIcons();
        
        // Anti-Flash logic is already in sidebar/theme inclusion
    </script>
</body>
</html>