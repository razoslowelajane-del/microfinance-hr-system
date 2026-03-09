<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../../config/config.php';

$action = $_POST['action'] ?? '';

// ── Mark Sent to Bank ───────────────────────────────────────────────────
if ($action === 'send_to_bank') {
    $appId = intval($_POST['app_id'] ?? 0);
    if (!$appId) { echo json_encode(['success' => false, 'message' => 'Invalid application ID.']); exit; }

    $stmt = $conn->prepare("UPDATE bank_applications SET Status='Sent to Bank' WHERE AppID=? AND Status='Pending'");
    $stmt->bind_param('i', $appId);
    $stmt->execute();
    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Application marked as Sent to Bank.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'No update made. Record may already be updated.']);
    }
    exit;
}

// ── Mark Confirmed + Save Bank Details ─────────────────────────────────
if ($action === 'confirm_bank') {
    $appId      = intval($_POST['app_id']        ?? 0);
    $empId      = intval($_POST['employee_id']   ?? 0);
    $accNumber  = trim($_POST['account_number']  ?? '');
    // Hardcoded per business rules — BDO Payroll only
    $bankName   = 'BDO';
    $accType    = 'Payroll';


    if (!$appId || !$empId || !$bankName || !$accNumber || !$accType) {
        echo json_encode(['success' => false, 'message' => 'All fields are required.']);
        exit;
    }

    // 1. Update application status
    $s1 = $conn->prepare("UPDATE bank_applications SET Status='Confirmed' WHERE AppID=? AND Status='Sent to Bank'");
    $s1->bind_param('i', $appId);
    $s1->execute();

    // 2. INSERT or UPDATE bankdetails (prefer REPLACE for simplicity)
    // Check for existing record
    $chk = $conn->prepare("SELECT BankDetailID FROM bankdetails WHERE EmployeeID=? LIMIT 1");
    $chk->bind_param('i', $empId);
    $chk->execute();
    $chkRes = $chk->get_result();
    
    if ($chkRes->num_rows > 0) {
        // UPDATE existing
        $row = $chkRes->fetch_assoc();
        $s2 = $conn->prepare("UPDATE bankdetails SET BankName=?, AccountNumber=?, AccountType=? WHERE BankDetailID=?");
        $s2->bind_param('sssi', $bankName, $accNumber, $accType, $row['BankDetailID']);
        $s2->execute();
        $action_taken = 'updated';
    } else {
        // INSERT new
        $s2 = $conn->prepare("INSERT INTO bankdetails (EmployeeID, BankName, AccountNumber, AccountType) VALUES (?,?,?,?)");
        $s2->bind_param('isss', $empId, $bankName, $accNumber, $accType);
        $s2->execute();
        $action_taken = 'created';
    }

    echo json_encode([
        'success' => true,
        'message' => "Bank details {$action_taken}. Application marked as Confirmed.",
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown action.']);
