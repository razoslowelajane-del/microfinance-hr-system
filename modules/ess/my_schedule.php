<?php
require_once __DIR__ . "/includes/auth_employee.php";

$pageTitle = "My Schedule";
$cssPath = "../../css/ess/my_schedule.css?v=" . time();
$jsPath  = "../../js/ess/my_schedule.js?v=" . time();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="<?= htmlspecialchars($cssPath) ?>">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body>
    <?php include __DIR__ . "/sidebar.php"; ?>

    <main class="page-content">
        <section class="schedule-page">
            <div class="schedule-shell">
                <header class="page-header">
                    <div class="page-header-text">
                        <h1>My Schedule</h1>
                        <p>View your assigned work schedule, shift details, and location.</p>
                    </div>

                    <div class="page-header-actions">
                        <?php
                        $themeFile = __DIR__ . "/theme.php";
                        if (file_exists($themeFile)) {
                            include $themeFile;
                        }
                        ?>
                    </div>
                </header>

                <section class="schedule-top-grid">
                    <article class="card hero-card">
                        <div class="hero-left">
                            <div class="hero-icon" aria-hidden="true">
                                <i data-lucide="calendar-days"></i>
                            </div>

                            <div>
                                <h2 id="scheduleRangeText">Loading schedule...</h2>
                                <p id="scheduleSummaryText">Please wait while we load your assigned schedule.</p>
                            </div>
                        </div>

                        <a href="attendance.php" class="btn btn-primary">
                            <i data-lucide="scan-face"></i>
                            <span>Go to Attendance</span>
                        </a>
                    </article>

                    <article class="card today-card">
                        <div class="card-head">
                            <div class="head-icon primary-soft" aria-hidden="true">
                                <i data-lucide="clock-3"></i>
                            </div>
                            <div>
                                <h2>Today's Shift</h2>
                                <p>Your current duty information for today.</p>
                            </div>
                        </div>

                        <div class="today-info">
                            <div class="today-row">
                                <span>Status</span>
                                <strong id="todayStatus">Loading...</strong>
                            </div>
                            <div class="today-row">
                                <span>Shift</span>
                                <strong id="todayShift">--</strong>
                            </div>
                            <div class="today-row">
                                <span>Time</span>
                                <strong id="todayTime">--</strong>
                            </div>
                            <div class="today-row">
                                <span>Location</span>
                                <strong id="todayLocation">--</strong>
                            </div>
                        </div>
                    </article>
                </section>

                <section class="card schedule-table-card">
                    <div class="card-head">
                        <div class="head-icon info-soft" aria-hidden="true">
                            <i data-lucide="table-properties"></i>
                        </div>
                        <div>
                            <h2>Schedule List</h2>
                            <p>Your assigned schedule for the active roster period.</p>
                        </div>
                    </div>

                    <div class="table-wrap">
                        <table class="schedule-table">
                            <thead>
                                <tr>
                                    <th scope="col">Date</th>
                                    <th scope="col">Day</th>
                                    <th scope="col">Shift</th>
                                    <th scope="col">Time</th>
                                    <th scope="col">Break</th>
                                    <th scope="col">Location</th>
                                    <th scope="col">Status</th>
                                </tr>
                            </thead>
                            <tbody id="scheduleTableBody">
                                <tr>
                                    <td colspan="7" class="empty-cell">Loading schedule...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </section>
    </main>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            if (window.lucide) {
                lucide.createIcons();
            }
        });
    </script>
    <script src="<?= htmlspecialchars($jsPath) ?>"></script>
</body>
</html>