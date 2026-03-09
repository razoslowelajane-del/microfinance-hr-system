<?php
require_once __DIR__ . "/auth_officer.php";
require_once __DIR__ . "/../../../config/config.php";

header('Content-Type: application/json');

$deptId    = $_SESSION['department_id'] ?? null;
$accountId = $_SESSION['user_id'] ?? null; // ✅ align with auth_officer.php

$rosterId = (int)($_POST["roster_id"] ?? 0);
$message  = trim($_POST["message"] ?? ""); // optional notes to HR

if (!$deptId || !$accountId || $rosterId <= 0) {
  echo json_encode(["status" => "error", "message" => "Missing parameters"]);
  exit;
}

try {
  // ✅ Validate roster exists + belongs to department + editable
  $stmt = $conn->prepare("
    SELECT RosterID, Status, DepartmentID, CreatedByAccountID
    FROM weekly_roster
    WHERE RosterID=? LIMIT 1
  ");
  $stmt->bind_param("i", $rosterId);
  $stmt->execute();
  $r = $stmt->get_result()->fetch_assoc();

  if (!$r) {
    echo json_encode(["status" => "error", "message" => "Roster not found"]);
    exit;
  }

  if ((int)$r["DepartmentID"] !== (int)$deptId) {
    echo json_encode(["status" => "error", "message" => "Roster department mismatch"]);
    exit;
  }

  // ✅ Optional: only creator can submit (recommended)
  if ((int)$r["CreatedByAccountID"] !== (int)$accountId) {
    echo json_encode(["status" => "error", "message" => "You are not allowed to submit this roster."]);
    exit;
  }

  $status = strtoupper((string)$r["Status"]);
  if (!in_array($status, ["DRAFT", "RETURNED"], true)) {
    echo json_encode(["status" => "error", "message" => "Roster already submitted/locked (Status: $status)."]);
    exit;
  }

  // ✅ Submit to HR for review
  // Use ReviewNotes as the optional message if your table has it
  // If your table does NOT have ReviewNotes, remove that line.
  $upd = $conn->prepare("
    UPDATE weekly_roster
    SET Status='FOR_REVIEW',
        ReviewNotes=?,
        UpdatedAt=CURRENT_TIMESTAMP
    WHERE RosterID=?
  ");
  $upd->bind_param("si", $message, $rosterId);
  $upd->execute();

  echo json_encode(["status" => "success", "message" => "Roster submitted to HR Manager for review."]);

} catch (Exception $e) {
  echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}