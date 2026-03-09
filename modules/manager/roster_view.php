<?php
require_once "includes/auth_hr_manager.php";
require_once "../../config/config.php";

$rosterID = $_GET['id'] ?? null;
if (!$rosterID) { header("Location: roster_review.php"); exit; }

/** 1. FETCH ROSTER METADATA **/
$stmt = $conn->prepare("
    SELECT wr.*, d.DepartmentName, ua.Username as PreparedBy 
    FROM weekly_roster wr 
    JOIN department d ON wr.DepartmentID = d.DepartmentID 
    JOIN useraccounts ua ON wr.CreatedByAccountID = ua.AccountID
    WHERE wr.RosterID = ?
");
$stmt->bind_param("i", $rosterID);
$stmt->execute();
$roster = $stmt->get_result()->fetch_assoc();
if (!$roster) die("Roster not found.");

/** 2. FETCH DATES **/
$stmtDates = $conn->prepare("SELECT DISTINCT WorkDate FROM roster_assignment WHERE RosterID = ? ORDER BY WorkDate ASC");
$stmtDates->bind_param("i", $rosterID);
$stmtDates->execute();
$resDates = $stmtDates->get_result();
$dates = [];
while($d = $resDates->fetch_assoc()) { $dates[] = $d['WorkDate']; }

/** 3. FETCH ASSIGNMENTS WITH EMPLOYEE INFO **/
$qAssign = "
    SELECT ra.*, e.FirstName, e.LastName, e.EmployeeCode, p.PositionName
    FROM roster_assignment ra 
    JOIN employee e ON ra.EmployeeID = e.EmployeeID 
    JOIN employmentinformation ei ON e.EmployeeID = ei.EmployeeID
    JOIN positions p ON ei.PositionID = p.PositionID
    WHERE ra.RosterID = ? 
    ORDER BY e.LastName ASC, ra.WorkDate ASC";
$stmt2 = $conn->prepare($qAssign);
$stmt2->bind_param("i", $rosterID);
$stmt2->execute();
$resAssign = $stmt2->get_result();

$scheduleMap = []; $empInfo = [];
while($row = $resAssign->fetch_assoc()) {
    $empID = $row['EmployeeID'];
    $empInfo[$empID] = [
        'name' => $row['FirstName'] . ' ' . $row['LastName'],
        'code' => $row['EmployeeCode'],
        'pos' => $row['PositionName']
    ];
    $scheduleMap[$empID][$row['WorkDate']] = $row['ShiftCode'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Roster #<?php echo $rosterID; ?> | HR3</title>
    
    <link rel="stylesheet" href="../../css/manager/roster_view.css">
    
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="theme-controlled">

    <?php include "sidebar.php"; ?>

    <main class="main-content">
        <header class="page-header">
            <div class="header-left">
                <a href="roster_review.php" class="back-link">
                    <i data-lucide="arrow-left"></i> Back to List
                </a>
                <div class="header-title-group">
                    <h1>Schedule Review <span>#<?php echo $rosterID; ?></span></h1>
                    <p><?php echo htmlspecialchars($roster['DepartmentName']); ?> • Prepared by <?php echo htmlspecialchars($roster['PreparedBy']); ?></p>
                </div>
            </div>
            
            <div class="header-right">
                <?php include "theme.php"; ?>
                
                <button class="btn btn-return" onclick="processStatus('RETURNED')">
                    <i data-lucide="rotate-ccw"></i> Return
                </button>
                <button class="btn btn-approve" onclick="processStatus('PUBLISHED')">
                    <i data-lucide="check-circle"></i> Approve & Publish
                </button>
            </div>
        </header>

        <div class="content-wrapper">
            <div class="content-card">
                <div class="table-container">
                    <table class="grid-table">
                        <thead>
                            <tr>
                                <th class="staff-col">Personnel Details</th>
                                <?php foreach($dates as $date): ?>
                                    <th>
                                        <div class="day-label"><?php echo date('D', strtotime($date)); ?></div>
                                        <div class="date-label"><?php echo date('M d', strtotime($date)); ?></div>
                                    </th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($empInfo as $id => $info): ?>
                            <tr>
                                <td class="staff-col" onclick="viewFullSched(<?php echo $id; ?>, <?php echo $rosterID; ?>)">
                                    <div class="staff-name"><?php echo htmlspecialchars($info['name']); ?></div>
                                    <div class="staff-meta"><?php echo htmlspecialchars($info['code']); ?> • <?php echo htmlspecialchars($info['pos']); ?></div>
                                </td>
                                <?php foreach($dates as $date): ?>
                                    <td>
                                        <?php 
                                            $s = $scheduleMap[$id][$date] ?? 'OFF'; 
                                            echo "<span class='tag tag-$s'>$s</span>"; 
                                        ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <div id="drawerOverlay" class="drawer-overlay" onclick="closeDrawer()">
        <div class="drawer-content" onclick="event.stopPropagation()">
            <div class="drawer-header">
                <div class="drawer-title-group">
                    <h2 id="dName">Staff Schedule</h2>
                    <p id="dMeta"></p>
                </div>
                <button onclick="closeDrawer()" class="close-icon-btn">
                    <i data-lucide="x"></i>
                </button>
            </div>
            <div class="drawer-table-box">
                <table class="list-table">
                    <thead>
                        <tr>
                            <th style="text-align:left;">Date Assignment</th>
                            <th>Shift</th>
                        </tr>
                    </thead>
                    <tbody id="dBody"></tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();

        // Sidebar active fix
        $(document).ready(function() {
            const rosterLink = $('a[href="roster_review.php"]');
            rosterLink.addClass('active');
            rosterLink.closest('.submenu').addClass('active');
            rosterLink.closest('.nav-item-group').addClass('active');
        });

        function viewFullSched(empId, rosterId) {
            $('#drawerOverlay').css('display', 'flex');
            $('#dBody').html('<tr><td colspan="2" class="loading-msg">Fetching details...</td></tr>');
            
            $.getJSON('includes/get_employee_schedule.php', { employee_id: empId, roster_id: rosterId }, function(data) {
                $('#dName').text(data.employee.FullName);
                $('#dMeta').text(data.employee.EmployeeCode + ' • ' + data.employee.Dept);
                let rows = '';
                data.days.forEach(day => {
                    rows += `<tr>
                        <td style="text-align:left;">
                            <div class="row-date">${day.WorkDate}</div>
                            <small class="row-day">${day.DayName}</small>
                        </td>
                        <td><span class="tag tag-${day.Shift}">${day.Shift}</span></td>
                    </tr>`;
                });
                $('#dBody').html(rows);
            });
        }

        function closeDrawer() { $('#drawerOverlay').hide(); }

        function processStatus(status) {
            const isDark = document.body.classList.contains('dark-mode');
            const isApprove = status === 'PUBLISHED';
            
            Swal.fire({
                title: isApprove ? 'Approve & Publish?' : 'Return Roster?',
                text: isApprove ? "This will notify the department and finalize the schedule." : "Please provide a reason for return:",
                input: 'textarea',
                inputPlaceholder: 'Type review notes here...',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: isApprove ? '#10b981' : '#f43f5e',
                confirmButtonText: isApprove ? 'Confirm Publish' : 'Confirm Return',
                background: isDark ? '#1e293b' : '#ffffff',
                color: isDark ? '#f8fafc' : '#1e293b',
                preConfirm: (value) => {
                    if (!isApprove && !value) {
                        Swal.showValidationMessage('Notes are required for returning a roster.');
                    }
                    return value;
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'includes/update_roster_status.php',
                        method: 'POST',
                        data: { 
                            roster_id: <?php echo $rosterID; ?>, 
                            status: status,
                            notes: result.value 
                        },
                        dataType: 'json',
                        success: function(res) {
                            if(res.success) {
                                Swal.fire({ 
                                    title: 'Success!', 
                                    text: 'Roster status updated successfully.',
                                    icon: 'success', 
                                    background: isDark ? '#1e293b' : '#ffffff', 
                                    color: isDark ? '#f8fafc' : '#1e293b' 
                                }).then(() => location.href='roster_review.php');
                            } else {
                                Swal.fire('Error', res.error, 'error');
                            }
                        },
                        error: function(err) {
                            Swal.fire('Error', 'Server communication failed.', 'error');
                        }
                    });
                }
            });
        }
    </script>
</body>
</html>