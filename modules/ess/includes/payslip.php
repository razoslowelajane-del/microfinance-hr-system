<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: ../../login.php');
    exit();
}

require_once __DIR__ . '/../../config/config.php';

$itemId = (int)($_GET['item_id'] ?? 0);
if ($itemId <= 0) {
    http_response_code(400);
    echo 'Invalid payslip.';
    exit;
}

$stmt = $conn->prepare(
    "SELECT i.id AS item_id, i.basic_pay, i.allowances_total, i.sss_regular_ee, i.sss_wisp_ee, i.philhealth_ee, i.pagibig_ee, i.deductions_total, i.net_pay,
            b.batch_code, b.period_start, b.period_end, b.pay_type,
            e.EmployeeCode, e.FirstName, e.LastName,
            ei.SalaryType
     FROM payroll_batch_items i
     INNER JOIN payroll_batches b ON b.id = i.batch_id
     INNER JOIN employee e ON e.EmployeeID = i.employee_id
     INNER JOIN employmentinformation ei ON ei.EmployeeID = i.employee_id
     WHERE i.id = ?"
);
$stmt->bind_param('i', $itemId);
$stmt->execute();
$res = $stmt->get_result();
$row = $res ? $res->fetch_assoc() : null;
$stmt->close();

if (!$row) {
    http_response_code(404);
    echo 'Payslip not found.';
    exit;
}

$compStmt = $conn->prepare("SELECT component_type, component_name, amount FROM payroll_item_components WHERE item_id=? ORDER BY component_type, component_name");
$compStmt->bind_param('i', $itemId);
$compStmt->execute();
$compRes = $compStmt->get_result();
$components = [];
while ($compRes && ($c = $compRes->fetch_assoc())) {
    $components[] = $c;
}
$compStmt->close();

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function peso($n) { return '₱' . number_format((float)$n, 2); }

$employeeName = trim($row['FirstName'] . ' ' . $row['LastName']);
$periodLabel = date('M d, Y', strtotime($row['period_start'])) . ' - ' . date('M d, Y', strtotime($row['period_end']));

$allowances = [];
$deductions = [];
foreach ($components as $c) {
    if ($c['component_type'] === 'Allowance') $allowances[] = $c;
    if ($c['component_type'] === 'Deduction') $deductions[] = $c;
}

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Payslip - <?= h($row['batch_code']) ?></title>
    <link rel="icon" type="image/png" href="../../img/logo.png">
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <link rel="stylesheet" href="../../css/paypayslip.css">
</head>
<body>
    <div class="page">
        <div class="toolbar">
            <button class="btn" onclick="window.close()"><i data-lucide="x"></i> Close</button>
            <button class="btn" onclick="window.print()"><i data-lucide="printer"></i> Print</button>
            <button class="btn btn-primary" onclick="downloadPDF()"><i data-lucide="download"></i> Download PDF</button>
        </div>

        <div class="card">
            <div class="header">
                <div class="company-section">
                    <div class="logo-wrapper">
                        <img src="../../img/logo.png" alt="Logo">
                    </div>
                    <div>
                        <div class="company-name">Microfinance</div>
                        <div class="company-tagline">Official Payslip Document</div>
                    </div>
                </div>
                <div class="batch-badge">
                    <div class="batch-label">Batch Code</div>
                    <div class="batch-code"><?= h($row['batch_code']) ?></div>
                </div>
            </div>

            <div class="meta-grid">
                <div class="field">
                    <div class="label">Employee Name</div>
                    <div class="value"><?= h($employeeName) ?></div>
                    <div class="sub">ID: <?= h($row['EmployeeCode']) ?></div>
                </div>
                <div class="field">
                    <div class="label">Payroll Period</div>
                    <div class="value"><?= h($periodLabel) ?></div>
                    <div class="sub"><?= h($row['pay_type']) ?> (<?= h($row['SalaryType'] ?? 'Monthly') ?>)</div>
                </div>
            </div>

            <div class="sections">
                <div class="section-card">
                    <div class="section-header">
                        <div class="section-title">
                            <i data-lucide="banknote"></i> Earnings
                        </div>                   
                     </div>
                    <table>
                        <thead>
                            <tr><th>Description</th><th class="amount">Amount</th></tr>
                        </thead>
                        <tbody>
                            <tr><td>Basic Pay</td><td class="amount earning"><?= h(peso($row['basic_pay'])) ?></td></tr>
                            <tr><td>Allowances</td><td class="amount earning"><?= h(peso($row['allowances_total'])) ?></td></tr>
                            <?php foreach ($allowances as $a): if ($a['component_name'] === 'Overtime Pay'): ?>
                                <tr><td><?= h($a['component_name']) ?></td><td class="amount earning"><?= h(peso($a['amount'])) ?></td></tr>
                            <?php endif; endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="section-card">
                    <div class="section-header deductions">
                        <div class="section-title">
                            <i data-lucide="trending-down"></i> Deductions
                        </div>
                    </div>
                    <table>
                        <thead>
                            <tr><th>Description</th><th class="amount">Amount</th></tr>
                        </thead>
                        <tbody>
                            <tr><td>SSS Regular (EE)</td><td class="amount deduction"><?= h(peso($row['sss_regular_ee'])) ?></td></tr>
                            <tr><td>SSS WISP (EE)</td><td class="amount deduction"><?= h(peso($row['sss_wisp_ee'])) ?></td></tr>
                            <tr><td>PhilHealth (EE)</td><td class="amount deduction"><?= h(peso($row['philhealth_ee'])) ?></td></tr>
                            <tr><td>Pag-IBIG (EE)</td><td class="amount deduction"><?= h(peso($row['pagibig_ee'])) ?></td></tr>
                            <?php foreach ($deductions as $d): if ($d['component_name'] === 'Late/Undertime'): ?>
                                <tr><td><?= h($d['component_name']) ?></td><td class="amount deduction"><?= h(peso($d['amount'])) ?></td></tr>
                            <?php endif; endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="totals">
                <div class="totalBox">
                    <div class="label">Gross Pay</div>
                    <div class="value" style="color: var(--text);">₱<?= h(number_format($row['basic_pay'] + $row['allowances_total'], 2)) ?></div>
                </div>
                <div class="totalBox">
                    <div class="label">Total Deductions</div>
                    <div class="value" style="color: var(--danger);">₱<?= h(number_format($row['deductions_total'], 2)) ?></div>
                </div>
                <div class="totalBox">
                    <div class="label">Generated</div>
                    <div class="value" style="color: var(--muted); font-size: 14px;"><?= h(date('M d, Y')) ?></div>
                </div>
            </div>

            <div class="net-pay-highlight">
                <div class="label">Net Pay</div>
                <div class="value"><?= h(peso($row['net_pay'])) ?></div>
            </div>

            <div class="footer">
                This is a computer-generated document. For verification, please contact HR Department.
            </div>
        </div>
    </div>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
      lucide.createIcons();
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script>
        function downloadPDF() {
            const card = document.querySelector('.card');
            const toolbar = document.querySelector('.toolbar');
            toolbar.style.display = 'none';
            
            html2canvas(card, { scale: 2, useCORS: true }).then(canvas => {
                const { jsPDF } = window.jspdf;
                const pdf = new jsPDF('p', 'mm', 'a4');
                const imgWidth = 210;
                const imgHeight = (canvas.height * imgWidth) / canvas.width;
                const imgData = canvas.toDataURL('image/png');
                pdf.addImage(imgData, 'PNG', 0, 0, imgWidth, imgHeight);
                pdf.save('payslip_<?= h($row['batch_code']) ?>_<?= h(preg_replace('/[^a-zA-Z0-9]/', '', $employeeName)) ?>.pdf');
                toolbar.style.display = '';
            });
        }
    </script>
</body>
</html>