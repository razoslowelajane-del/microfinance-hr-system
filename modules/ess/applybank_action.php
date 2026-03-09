<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../../config/config.php';

$action = $_POST['action'] ?? '';

// ── EMPLOYEE SUBMITS FILLED PDF ─────────────────────────────────
if ($action === 'submit_application') {
    // Get EmployeeID from session or look up by username
    $employeeId = intval($_SESSION['employee_id'] ?? 0);
    if (!$employeeId) {
        // Look up via useraccounts → employee
        $un   = $conn->real_escape_string($_SESSION['username']);
        $res  = $conn->query("SELECT e.EmployeeID FROM useraccounts ua LEFT JOIN employee e ON ua.EmployeeID = e.EmployeeID WHERE ua.Username = '$un' LIMIT 1");
        if ($row = $res->fetch_assoc()) $employeeId = intval($row['EmployeeID']);
    }

    if (!$employeeId) {
        echo json_encode(['success' => false, 'message' => 'Could not identify your employee record. Please contact HR.']);
        exit;
    }

    $formId = intval($_POST['form_id'] ?? 0);

    if (!isset($_FILES['filled_pdf']) || $_FILES['filled_pdf']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'No file uploaded.']);
        exit;
    }

    $file     = $_FILES['filled_pdf'];
    $finfo    = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if ($mimeType !== 'application/pdf') {
        echo json_encode(['success' => false, 'message' => 'Only PDF files are accepted.']);
        exit;
    }

    if ($file['size'] > 15 * 1024 * 1024) {
        echo json_encode(['success' => false, 'message' => 'File size must not exceed 15 MB.']);
        exit;
    }

    $uploadDir = __DIR__ . '/../../uploads/bank_submissions/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    $unique   = 'emp' . $employeeId . '_' . time() . '.pdf';
    $destPath = $uploadDir . $unique;

    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        echo json_encode(['success' => false, 'message' => 'Failed to save file.']);
        exit;
    }

    $relPath = 'uploads/bank_submissions/' . $unique;
    $stmt    = $conn->prepare("INSERT INTO bank_applications (EmployeeID, FormID, UploadedPDF, Status) VALUES (?, ?, ?, 'Pending')");
    $nullFormId = $formId ?: null;
    $stmt->bind_param('iis', $employeeId, $nullFormId, $relPath);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Your form has been submitted successfully. HR will review it shortly.']);
    } else {
        @unlink($destPath);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    }
    $stmt->close();
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown action.']);
