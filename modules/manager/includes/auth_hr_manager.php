<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . "/../../../config/config.php";

$accountId = $_SESSION['account_id'] ?? $_SESSION['user_id'] ?? $_SESSION['AccountID'] ?? null;

if (!$accountId) {
    header("Location: ../../login.php");
    exit;
}

$stmt = $conn->prepare("
    SELECT 1
    FROM useraccountroles
    WHERE AccountID = ? AND RoleID = 2
    LIMIT 1
");
$stmt->bind_param("i", $accountId);
$stmt->execute();
$ok = $stmt->get_result()->fetch_row();

if (!$ok) {
    http_response_code(403);
    die("Access denied: HR Manager only.");
}