<?php
// modules/ess/includes/auth_employee.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isLoggedIn = isset($_SESSION['user_id']) || isset($_SESSION['account_id']);
$hasEmployeeID = isset($_SESSION['employee_id']) || isset($_SESSION['EmployeeID']);

if (!$isLoggedIn || !$hasEmployeeID) {
    header("Location: ../../login.php?error=session_expired");
    exit();
}

require_once __DIR__ . "/../../../config/config.php";

$accountId = $_SESSION['user_id'] ?? $_SESSION['account_id'] ?? null;

if (!$accountId || !isset($conn) || !($conn instanceof mysqli)) {
    session_destroy();
    header("Location: ../../login.php?error=session_invalid");
    exit();
}

$checkStmt = $conn->prepare("SELECT AccountStatus FROM useraccounts WHERE AccountID = ? LIMIT 1");

if (!$checkStmt) {
    session_destroy();
    header("Location: ../../login.php?error=auth_query_failed");
    exit();
}

$checkStmt->bind_param("i", $accountId);
$checkStmt->execute();

$result = $checkStmt->get_result();
$accStatus = $result ? $result->fetch_assoc() : null;
$checkStmt->close();

if (!$accStatus || ($accStatus['AccountStatus'] ?? '') !== 'Active') {
    session_destroy();
    header("Location: ../../login.php?error=account_disabled");
    exit();
}
?>