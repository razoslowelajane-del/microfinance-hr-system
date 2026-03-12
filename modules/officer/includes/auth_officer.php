<?php
// modules/officer/auth_officer.php

// -----------------------------
// Secure session bootstrap
// -----------------------------
if (session_status() === PHP_SESSION_NONE) {
    $isHttps = (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
        (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
    );

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $isHttps,   // true only on HTTPS
        'httponly' => true,       // JS cannot read session cookie
        'samesite' => 'Lax'       // helps reduce CSRF risk
    ]);

    session_start();
}

// -----------------------------
// Security headers
// -----------------------------
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

// -----------------------------
// Session timeout settings
// -----------------------------
$inactiveLimit = 900;   // 15 minutes inactivity
$absoluteLimit = 28800; // 8 hours total session lifetime

// inactivity timeout
if (isset($_SESSION['last_activity']) && is_numeric($_SESSION['last_activity'])) {
    if ((time() - (int)$_SESSION['last_activity']) > $inactiveLimit) {
        session_unset();
        session_destroy();
        header("Location: ../../login.php?expired=1");
        exit;
    }
}
$_SESSION['last_activity'] = time();

// absolute session lifetime
if (isset($_SESSION['login_time']) && is_numeric($_SESSION['login_time'])) {
    if ((time() - (int)$_SESSION['login_time']) > $absoluteLimit) {
        session_unset();
        session_destroy();
        header("Location: ../../login.php?expired=1");
        exit;
    }
}

// basic user-agent binding
$currentUserAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
if (!isset($_SESSION['user_agent'])) {
    $_SESSION['user_agent'] = $currentUserAgent;
} elseif (!hash_equals((string)$_SESSION['user_agent'], (string)$currentUserAgent)) {
    session_unset();
    session_destroy();
    header("Location: ../../login.php?expired=1");
    exit;
}

// -----------------------------
// ORIGINAL PROCESS STARTS HERE
// -----------------------------
if (!isset($_SESSION['user_id'])) {
  header("Location: ../../login.php");
  exit;
}

// extra validation for session values
if (!is_numeric($_SESSION['user_id']) || (int)$_SESSION['user_id'] <= 0) {
  session_unset();
  session_destroy();
  header("Location: ../../login.php");
  exit;
}

// department-based officer dashboard requires these
if (!isset($_SESSION['department_id']) || !isset($_SESSION['employee_id'])) {
  http_response_code(400);
  exit("Missing department session. Please re-login.");
}

// extra validation for department/employee ids
if (
    !is_numeric($_SESSION['department_id']) || (int)$_SESSION['department_id'] <= 0 ||
    !is_numeric($_SESSION['employee_id']) || (int)$_SESSION['employee_id'] <= 0
) {
    session_unset();
    session_destroy();
    header("Location: ../../login.php");
    exit;
}

// OPTIONAL role enforcement (safe)
$primary = $_SESSION['user_role'] ?? '';
$user_roles = $_SESSION['user_roles'] ?? [];

$ok = false;
if (is_string($primary) && strtolower(trim($primary)) === strtolower("Department Officer")) $ok = true;

if (!$ok && is_array($user_roles)) {
  foreach ($user_roles as $r) {
    if (is_string($r) && strtolower(trim($r)) === strtolower("Department Officer")) { $ok = true; break; }
  }
}

if (!$ok) {
  http_response_code(403);
  exit("Forbidden: Officer access only.");
}