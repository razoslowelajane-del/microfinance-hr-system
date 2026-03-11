<?php
// modules/ess/leave_apply.php
require_once "includes/auth_employee.php"; 
require_once "../../config/config.php";

$employeeID = $_SESSION['employee_id'] ?? null;
if (!$employeeID) { header("Location: ../../login.php?error=unauthorized"); exit; }

// 1. Check for existing active/pending requests
// Statuses that block new applications: PENDING and APPROVED_BY_OFFICER
$qActive = "SELECT LeaveRequestID FROM leave_requests 
            WHERE EmployeeID = ? AND Status IN ('PENDING', 'APPROVED_BY_OFFICER')";
$stmtActive = $conn->prepare($qActive);
$stmtActive->bind_param("i", $employeeID);
$stmtActive->execute();
$hasActiveRequest = $stmtActive->get_result()->num_rows > 0;

// 2. Fetch Leave Balances
$qBal = "SELECT lb.*, lt.LeaveName 
         FROM employee_leave_balances lb 
         JOIN leave_types lt ON lb.LeaveTypeID = lt.LeaveTypeID 
         WHERE lb.EmployeeID = ? AND lb.Year = YEAR(CURDATE())";
$stmtBal = $conn->prepare($qBal);
$stmtBal->bind_param("i", $employeeID);
$stmtBal->execute();
$balances = $stmtBal->get_result();

$jsCredits = [];
$balanceRows = [];
while($row = $balances->fetch_assoc()) {
    $balanceRows[] = $row;
    $jsCredits[$row['LeaveTypeID']] = ['name' => $row['LeaveName'], 'credits' => $row['RemainingCredits']];
}

// 3. Fetch Active Leave Types
$leaveTypes = $conn->query("SELECT * FROM leave_types");

// 4. POLICY: 3-Day Advance Notice
$minNoticeDate = date('Y-m-d', strtotime('+3 days')); 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Leave | ESS Portal</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../css/ess/leave_apply.css">
    
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
        (function() {
            const theme = localStorage.getItem("theme") || "dark";
            if (theme === "dark") document.documentElement.classList.add("dark-mode");
        })();
        const leaveCredits = <?php echo json_encode($jsCredits); ?>;
    </script>
</head>
<body class="theme-controlled">

    <?php include "sidebar.php"; ?>

    <main class="main-content">
        <header class="page-header">
            <div class="header-left">
                <div class="page-title-box">
                    <h1>Request Time Off</h1>
                    <p>Only one active request is allowed at a time.</p>
                </div>
            </div>
            <div class="header-right">
                <?php include "theme.php"; ?>
            </div>
        </header>

        <div class="content-wrapper">
            <div class="credits-container">
                <?php foreach($balanceRows as $b): ?>
                    <div class="credit-card">
                        <div class="credit-info">
                            <span class="credit-label"><?php echo htmlspecialchars($b['LeaveName']); ?></span>
                            <h3 class="credit-value"><?php echo number_format($b['RemainingCredits'], 1); ?> <small>days</small></h3>
                        </div>
                        <div class="credit-icon"><i data-lucide="calendar-check"></i></div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="form-grid">
                <?php if ($hasActiveRequest): ?>
                    <div class="form-card" style="display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; border: 2px dashed var(--brand-red);">
                        <i data-lucide="shield-alert" style="width: 64px; height: 64px; color: var(--brand-red); margin-bottom: 1.5rem;"></i>
                        <h2 style="color: var(--brand-red);">Application Locked</h2>
                        <p style="color: var(--text-muted); max-width: 400px; margin-top: 1rem;">
                            You currently have an <strong>active leave request</strong> being processed. 
                            Please wait for your existing request to be approved or rejected before filing a new one.
                        </p>
                        <a href="leave_history.php" class="submit-btn" style="margin-top: 2rem; text-decoration: none;">
                            <span>Check Status in History</span>
                            <i data-lucide="arrow-right"></i>
                        </a>
                    </div>
                <?php else: ?>
                    <div class="form-card">
                        <div class="card-header">
                            <i data-lucide="calendar-plus"></i>
                            <h2>New Leave Request</h2>
                        </div>
                        
                        <form id="leaveForm" enctype="multipart/form-data">
                            <div class="input-row">
                                <div class="input-group">
                                    <label style="display: flex; justify-content: space-between; align-items: center;">
                                        <span><i data-lucide="list-filter"></i> Leave Category</span>
                                        <span id="creditBadge" style="display:none; font-size: 0.7rem; background: var(--brand-green); color: white; padding: 2px 8px; border-radius: 20px; font-weight: 800;"></span>
                                    </label>
                                    <select name="leave_type" id="leave_type" required>
                                        <option value="" disabled selected>Select category...</option>
                                        <?php $leaveTypes->data_seek(0); while($lt = $leaveTypes->fetch_assoc()): ?>
                                            <option value="<?php echo $lt['LeaveTypeID']; ?>"><?php echo htmlspecialchars($lt['LeaveName']); ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                    <div id="typeHint" style="display:none; margin-top: 8px; font-size: 0.8rem; color: var(--text-muted); background: rgba(16, 185, 129, 0.05); padding: 8px 12px; border-radius: 10px; border-left: 3px solid var(--brand-green);">
                                        <i data-lucide="info" style="width: 14px; height: 14px; vertical-align: middle;"></i> 
                                        You have <strong id="hintValue">0</strong> days available.
                                    </div>
                                </div>
                            </div>

                            <div class="input-row split">
                                <div class="input-group">
                                    <label><i data-lucide="calendar-range"></i> Start Date</label>
                                    <input type="date" name="start_date" id="start_date" required min="<?php echo $minNoticeDate; ?>" onclick="this.showPicker()">
                                </div>
                                <div class="input-group">
                                    <label><i data-lucide="calendar-range"></i> End Date</label>
                                    <input type="date" name="end_date" id="end_date" required min="<?php echo $minNoticeDate; ?>" onclick="this.showPicker()">
                                </div>
                            </div>

                            <div class="input-row">
                                <div class="input-group">
                                    <label><i data-lucide="message-square"></i> Reason</label>
                                    <textarea name="reason" rows="3" placeholder="Tell us why you are taking a leave..." required></textarea>
                                </div>
                            </div>

                            <div class="input-row">
                                <div class="input-group">
                                    <label><i data-lucide="paperclip"></i> Support Files <small>(PDF/JPG/PNG)</small></label>
                                    <div class="file-upload-box">
                                        <input type="file" name="attachment" id="attachment" accept=".pdf,.jpg,.png" required>
                                        <p>Select attachment or drag here</p>
                                    </div>
                                </div>
                            </div>

                            <div id="previewBox" class="summary-box">
                                <div class="summary-item">
                                    <span>Leave Duration:</span>
                                    <strong id="totalDays">0 days</strong>
                                </div>
                                <div id="creditError" style="color: #f43f5e; font-size: 0.85rem; font-weight: 700; margin-top: 5px; display: none;">
                                    <i data-lucide="alert-triangle" style="width: 14px; height: 14px; display: inline-block; vertical-align: middle;"></i>
                                    Insufficient leave credits!
                                </div>
                            </div>

                            <button type="submit" class="submit-btn" id="submitBtn">
                                <span>Submit Application</span>
                                <i data-lucide="arrow-right"></i>
                            </button>
                        </form>
                    </div>
                <?php endif; ?>

                <div class="instruction-card">
                    <h3>Policy Reminders</h3>
                    <ul class="reminders-list">
                        <li><i data-lucide="alert-circle" style="color: var(--brand-red);"></i> <strong>One Request Rule:</strong> You cannot file multiple requests. Wait for your pending application to be finalized.</li>
                        <li><i data-lucide="calendar-days"></i> <strong>Early Booking:</strong> Earliest possible start is <b><?php echo date('F d, Y', strtotime($minNoticeDate)); ?></b>.</li>
                        <li><i data-lucide="shield-check"></i> <strong>Credits:</strong> Balances update only after official HR approval.</li>
                    </ul>
                </div>
            </div>
        </div>
    </main>

    <script>
        lucide.createIcons();
        <?php if (!$hasActiveRequest): ?>
        // Copy your existing JS validation logic here (startInput, endInput, validateCredits, etc.)
        const startInput = $('#start_date');
        const endInput = $('#end_date');
        const bufferDate = "<?php echo $minNoticeDate; ?>";

        $('#leave_type').on('change', function() {
            const typeID = $(this).val();
            if (leaveCredits[typeID]) {
                const available = leaveCredits[typeID].credits;
                $('#creditBadge').text(available + ' Left').fadeIn();
                $('#hintValue').text(available);
                $('#typeHint').slideDown();
            } else {
                $('#creditBadge').fadeOut();
                $('#typeHint').slideUp();
            }
            validateCredits();
        });

        function validateCredits() {
            const typeID = $('#leave_type').val();
            const s = startInput.val();
            const e = endInput.val();
            if (typeID && s && e) {
                const d1 = new Date(s);
                const d2 = new Date(e);
                const diff = Math.floor((d2 - d1) / (1000 * 60 * 60 * 24)) + 1;
                const available = (typeID == 5) ? 999 : parseFloat(leaveCredits[typeID]?.credits || 0);
                if (diff > available) { 
                    $('#creditError').fadeIn();
                    $('#submitBtn').prop('disabled', true).css('opacity', '0.5').css('cursor', 'not-allowed');
                } else {
                    $('#creditError').fadeOut();
                    $('#submitBtn').prop('disabled', false).css('opacity', '1').css('cursor', 'pointer');
                }
            }
        }

        startInput.on('change', function() {
            const selectedStart = $(this).val();
            endInput.attr('min', selectedStart);
            if (endInput.val() && endInput.val() < selectedStart) endInput.val(selectedStart);
            calculateDuration();
            validateCredits();
        });

        endInput.on('change', function() {
            const start = startInput.val();
            const end = $(this).val();
            if (!start) { Swal.fire({ title: 'Start Date First', text: 'Select a Start Date first.', icon: 'info' }); $(this).val(''); return; }
            if (end < start) { Swal.fire({ title: 'Invalid Range', text: 'End Date cannot be earlier than the Start Date.', icon: 'warning' }); $(this).val(start); }
            calculateDuration();
            validateCredits();
        });

        function calculateDuration() {
            const s = startInput.val();
            const e = endInput.val();
            if (s && e) {
                const d1 = new Date(s);
                const d2 = new Date(e);
                const diff = Math.floor((d2 - d1) / (1000 * 60 * 60 * 24)) + 1;
                $('#totalDays').text(diff + (diff > 1 ? " days" : " day"));
                $('#previewBox').addClass('show');
                lucide.createIcons();
            } else { $('#previewBox').removeClass('show'); }
        }

        $('#leaveForm').on('submit', function(e) {
            e.preventDefault();
            const isDark = document.body.classList.contains('dark-mode');
            if (startInput.val() < bufferDate) { Swal.fire('Policy Error', 'Invalid notice period.', 'error'); return; }
            Swal.fire({
                title: 'Submit Application?',
                text: "Forward this request for review?",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#10b981',
                background: isDark ? '#1e293b' : '#ffffff',
                color: isDark ? '#f8fafc' : '#1e293b'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'includes/leave_submit.php',
                        type: 'POST',
                        data: new FormData(this),
                        processData: false,
                        contentType: false,
                        dataType: 'json',
                        success: function(res) {
                            if(res.success) {
                                Swal.fire({ title: 'Submitted!', icon: 'success', background: isDark ? '#1e293b' : '#fff' })
                                .then(() => location.href='leave_history.php');
                            } else { Swal.fire('Error', res.error, 'error'); }
                        }
                    });
                }
            });
        });
        <?php endif; ?>
    </script>
</body>
</html>