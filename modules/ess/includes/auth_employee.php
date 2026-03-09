<?php
// modules/ess/includes/auth_employee.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * ESS GATEKEEPER LOGIC
 * 1. Dapat may active session (user_id/account_id).
 * 2. Dapat may employee_id (kasi ESS ito, kailangan ng link sa employee table).
 * 3. Dapat valid ang role (Employee o kaya naman Manager/Officer na gumagamit ng ESS).
 */

$isLoggedIn = isset($_SESSION['user_id']) || isset($_SESSION['account_id']);
$hasEmployeeID = isset($_SESSION['employee_id']);

if (!$isLoggedIn || !$hasEmployeeID) {
    // Kung hindi naka-login, itapon sa login page
    header("Location: ../../login.php?error=session_expired");
    exit();
}

// Opsyonal: I-verify kung 'Active' pa ang account sa database para iwas-hack
 require_once __DIR__ . "/../../../config/config.php";
 $checkStmt = $conn->prepare("SELECT AccountStatus FROM useraccounts WHERE AccountID = ?");
 $checkStmt->bind_param("i", $_SESSION['user_id']);
 $checkStmt->execute();
 $accStatus = $checkStmt->get_result()->fetch_assoc();
 if ($accStatus['AccountStatus'] !== 'Active') {
     session_destroy();
     header("Location: ../../login.php?error=account_disabled");
     exit();
 }
?>