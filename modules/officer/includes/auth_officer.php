<?php
// modules/officer/auth_officer.php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
  header("Location: ../../login.php");
  exit;
}

// department-based officer dashboard requires these
if (!isset($_SESSION['department_id']) || !isset($_SESSION['employee_id'])) {
  http_response_code(400);
  exit("Missing department session. Please re-login.");
}

// OPTIONAL role enforcement (safe)
$primary = $_SESSION['user_role'] ?? '';
$user_roles = $_SESSION['user_roles'] ?? [];

$ok = false;
if (is_string($primary) && strtolower($primary) === strtolower("Department Officer")) $ok = true;

if (!$ok && is_array($user_roles)) {
  foreach ($user_roles as $r) {
    if (is_string($r) && strtolower($r) === strtolower("Department Officer")) { $ok = true; break; }
  }
}

if (!$ok) {
  http_response_code(403);
  exit("Forbidden: Officer access only.");
}