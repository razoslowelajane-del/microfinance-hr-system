<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: ../../login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard</title>
  <link rel="stylesheet" href="../../css/ess/payrollreceipt.css?v=1.2">
  <script src="https://unpkg.com/lucide@latest"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <link rel="icon" type="image/png" href="../../img/logo.png">
</head>
<body>
  <?php include __DIR__ . "/sidebar.php"; ?>

  <!-- Main Content -->
  <main class="main-content">
    <header class="page-header">
      <div class="header-left">
        <button class="mobile-menu-btn" id="mobileMenuBtn">
          <i data-lucide="menu"></i>
        </button>
        <div class="header-title">
          <h1>Dashboard Overview</h1>
          <p>Welcome back, <?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?>! Here's what's happening today.</p>
        </div>
      </div>
      <div class="header-right">
                        <div class="header-clock">
          <span id="realTimeClock"></span>
        </div>
        <button class="theme-toggle" id="themeToggle" aria-label="Toggle theme">
          <i data-lucide="sun" class="sun-icon"></i>
          <i data-lucide="moon" class="moon-icon"></i>
        </button>
        <button class="icon-btn">
          <i data-lucide="bell"></i>
        </button>
      </div>
    </header>

    <div class="content-wrapper">
      <!-- Page Title -->
      <div style="margin-bottom: 24px;">
        <h2 style="font-size: 20px; font-weight: 600; color: var(--text-primary);">My Payslips</h2>
        <p style="font-size: 14px; color: var(--text-secondary);">View your approved payroll receipts</p>
      </div>

      <!-- Stats Cards -->
      <div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 24px; margin-bottom: 32px;">
        <div class="stat-card-premium">
          <div class="stat-icon-wrapper" style="background: linear-gradient(135deg, rgba(44, 160, 120, 0.15), rgba(16, 185, 129, 0.1)); color: var(--brand-green);">
            <i data-lucide="wallet"></i>
          </div>
          <div class="stat-info">
            <span class="stat-label">Total Net Pay (YTD)</span>
            <h3 class="stat-value" id="statTotalNet" style="color: var(--brand-green);">&#8369;0.00</h3>
          </div>
        </div>
        <div class="stat-card-premium">
          <div class="stat-icon-wrapper" style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.15), rgba(99, 102, 241, 0.1)); color: #3b82f6;">
            <i data-lucide="file-text"></i>
          </div>
          <div class="stat-info">
            <span class="stat-label">Payslips Received</span>
            <h3 class="stat-value" id="statPayslipCount">0</h3>
          </div>
        </div>
        <div class="stat-card-premium">
          <div class="stat-icon-wrapper" style="background: linear-gradient(135deg, rgba(168, 85, 247, 0.15), rgba(139, 92, 246, 0.1)); color: #a855f7;">
            <i data-lucide="trending-up"></i>
          </div>
          <div class="stat-info">
            <span class="stat-label">Average Net Pay</span>
            <h3 class="stat-value" id="statAvgNet">&#8369;0.00</h3>
          </div>
        </div>
      </div>

      <!-- Payslips Table -->
      <div class="table-card" style="background: var(--surface); border-radius: 16px; border: 1px solid var(--border-color); overflow: hidden;">
        <div style="padding: 16px 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
          <h3 style="font-size: 16px; font-weight: 600; color: var(--text-primary);">Payroll History</h3>
          <button class="btn-refresh" id="btnRefresh" style="padding: 8px 12px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--surface); cursor: pointer; color: var(--text-secondary);">
            <i data-lucide="refresh-cw" style="width: 16px; height: 16px;"></i>
          </button>
        </div>
        <table class="payslip-table" style="width: 100%; border-collapse: collapse;">
          <thead>
            <tr style="background: var(--background);">
              <th style="padding: 12px 16px; text-align: left; font-size: 12px; font-weight: 600; color: var(--text-tertiary); text-transform: uppercase;">Batch</th>
              <th style="padding: 12px 16px; text-align: left; font-size: 12px; font-weight: 600; color: var(--text-tertiary); text-transform: uppercase;">Period</th>
              <th style="padding: 12px 16px; text-align: left; font-size: 12px; font-weight: 600; color: var(--text-tertiary); text-transform: uppercase;">Pay Type</th>
              <th style="padding: 12px 16px; text-align: right; font-size: 12px; font-weight: 600; color: var(--text-tertiary); text-transform: uppercase;">Basic Pay</th>
              <th style="padding: 12px 16px; text-align: right; font-size: 12px; font-weight: 600; color: var(--text-tertiary); text-transform: uppercase;">Deductions</th>
              <th style="padding: 12px 16px; text-align: right; font-size: 12px; font-weight: 600; color: var(--text-tertiary); text-transform: uppercase;">Net Pay</th>
              <th style="padding: 12px 16px; text-align: center; font-size: 12px; font-weight: 600; color: var(--text-tertiary); text-transform: uppercase;">Action</th>
            </tr>
          </thead>
          <tbody id="payslipsBody">
            <tr>
              <td colspan="7" style="padding: 24px; text-align: center; color: var(--text-secondary);">Loading payslips...</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </main>
  <script src="../../js/ess/payrollreceipt.js?v=<?php echo time(); ?>"></script>
  <script>
    lucide.createIcons();
  </script>
</body>
</html>