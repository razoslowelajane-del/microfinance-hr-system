<?php
// modules/ess/leave_history.php
require_once "includes/auth_employee.php"; 
require_once "../../config/config.php";

// Lowercase 'employee_id' based on your login action
$employeeID = $_SESSION['employee_id'] ?? null;
if (!$employeeID) { header("Location: ../../login.php"); exit; }

// Fetch Leave History joined with Leave Types
$query = "SELECT lr.*, lt.LeaveName 
          FROM leave_requests lr 
          JOIN leave_types lt ON lr.LeaveTypeID = lt.LeaveTypeID 
          WHERE lr.EmployeeID = ? 
          ORDER BY lr.CreatedAt DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $employeeID);
$stmt->execute();
$history = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave History | ESS Portal</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../css/ess/leave_history.css">
    
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Anti-flash Dark Mode Default
        (function() {
            const theme = localStorage.getItem("theme") || "dark";
            if (theme === "dark") document.documentElement.classList.add("dark-mode");
        })();
    </script>
</head>
<body class="theme-controlled">

    <?php include "sidebar.php"; ?>

    <main class="main-content">
        <header class="page-header">
            <div class="header-left">
                <div class="page-title-box">
                    <h1>My Leave History</h1>
                    <p>Track and manage your submitted time-off requests.</p>
                </div>
            </div>
            <div class="header-right">
                <?php include "theme.php"; ?>
            </div>
        </header>

        <div class="content-wrapper">
            <div class="table-card">
                <div class="table-header">
                    <div class="th-left">
                        <i data-lucide="history" class="text-green"></i>
                        <h2>Recent Applications</h2>
                    </div>
                    <a href="leave_apply.php" class="btn-new-request">
                        <i data-lucide="plus"></i> File New Leave
                    </a>
                </div>
                
                <div class="table-responsive">
                    <table class="history-table">
                        <thead>
                            <tr>
                                <th>Type & ID</th>
                                <th>Inclusive Dates</th>
                                <th>Days</th>
                                <th>Status</th>
                                <th>Filed On</th>
                                <th style="text-align: center;">Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($history->num_rows > 0): ?>
                                <?php while($row = $history->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <div class="type-cell">
                                                <span class="type-name"><?php echo htmlspecialchars($row['LeaveName']); ?></span>
                                                <small class="type-id">#REQ-<?php echo $row['LeaveRequestID']; ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="date-info">
                                                <i data-lucide="calendar"></i>
                                                <?php echo date('M d', strtotime($row['StartDate'])); ?> - <?php echo date('M d, Y', strtotime($row['EndDate'])); ?>
                                            </div>
                                        </td>
                                        <td><span class="day-val"><?php echo $row['TotalDays']; ?> days</span></td>
                                        <td>
                                            <span class="status-badge status-<?php echo $row['Status']; ?>">
                                                <?php echo str_replace('_', ' ', $row['Status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($row['CreatedAt'])); ?></td>
                                        <td style="text-align: center;">
                                            <button class="btn-view" onclick="openRequestDetails(<?php echo $row['LeaveRequestID']; ?>)">
                                                <i data-lucide="eye"></i> View
                                            </button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="empty-row">
                                        <i data-lucide="file-x-2"></i>
                                        <p>No leave requests found.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <div id="requestDrawer" class="side-drawer">
        <div class="drawer-overlay" onclick="closeRequestDetails()"></div>
        <div class="drawer-panel">
            <div class="drawer-header">
                <h3>Application Details</h3>
                <button class="close-btn" onclick="closeRequestDetails()"><i data-lucide="x"></i></button>
            </div>
            <div id="drawerContent" class="drawer-body">
                </div>
        </div>
    </div>

    <script>
        lucide.createIcons();

        function openRequestDetails(id) {
            $('#requestDrawer').addClass('active');
            $('#drawerContent').html('<div class="loader">Loading details...</div>');
            
            $.get('includes/get_leave_details.php', { id: id }, function(res) {
                $('#drawerContent').html(res);
                lucide.createIcons();
            });
        }

        function closeRequestDetails() {
            $('#requestDrawer').removeClass('active');
        }
    </script>
</body>
</html>