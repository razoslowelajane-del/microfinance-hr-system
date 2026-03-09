<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../../config/config.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ── UPLOAD MASTER FORM ─────────────────────────────────────────
if ($action === 'upload_master') {
    $formName = trim($_POST['form_name'] ?? '');
    if (!$formName) {
        echo json_encode(['success' => false, 'message' => 'Form name is required.']);
        exit;
    }

    if (!isset($_FILES['pdf_file']) || $_FILES['pdf_file']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error.']);
        exit;
    }

    $file = $_FILES['pdf_file'];

    // Validate MIME type
    $finfo    = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    if ($mimeType !== 'application/pdf') {
        echo json_encode(['success' => false, 'message' => 'Only PDF files are allowed.']);
        exit;
    }

    if ($file['size'] > 10 * 1024 * 1024) {
        echo json_encode(['success' => false, 'message' => 'File size must not exceed 10 MB.']);
        exit;
    }

    $uploadDir = __DIR__ . '/../../uploads/bank_forms/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    $safeName   = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $formName);
    $uniqueName = $safeName . '_' . time() . '.pdf';
    $destPath   = $uploadDir . $uniqueName;

    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        echo json_encode(['success' => false, 'message' => 'Failed to save file.']);
        exit;
    }

    $relPath    = 'uploads/bank_forms/' . $uniqueName;
    $uploadedBy = $_SESSION['username'];

    // Archive all previous active forms
    $conn->query("UPDATE bank_forms_master SET IsActive = 0");

    // Insert new active form
    $stmt = $conn->prepare("INSERT INTO bank_forms_master (FormName, FilePath, IsActive, UploadedBy) VALUES (?, ?, 1, ?)");
    $stmt->bind_param('sss', $formName, $relPath, $uploadedBy);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Form uploaded and set as active.']);
    } else {
        @unlink($destPath);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    }
    $stmt->close();
    exit;
}

// ── DELETE MASTER FORM ──────────────────────────────────────────
if ($action === 'delete_form') {
    $formId = intval($_POST['form_id'] ?? 0);
    if (!$formId) { echo json_encode(['success' => false, 'message' => 'Invalid ID.']); exit; }

    $res = $conn->query("SELECT FilePath FROM bank_forms_master WHERE FormID = $formId");
    if ($row = $res->fetch_assoc()) {
        $full = __DIR__ . '/../../' . $row['FilePath'];
        if (file_exists($full)) @unlink($full);
    }

    if ($conn->query("DELETE FROM bank_forms_master WHERE FormID = $formId")) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Delete failed.']);
    }
    exit;
}

// ── SET ACTIVE FORM ─────────────────────────────────────────────
if ($action === 'set_active') {
    $formId = intval($_POST['form_id'] ?? 0);
    $conn->query("UPDATE bank_forms_master SET IsActive = 0");
    $conn->query("UPDATE bank_forms_master SET IsActive = 1 WHERE FormID = $formId");
    echo json_encode(['success' => true]);
    exit;
}

// ── GET APP STATUS (for HR view) ────────────────────────────────
if ($action === 'update_status') {
    $appId  = intval($_POST['app_id']  ?? 0);
    $status = $_POST['status'] ?? '';
    $allowed = ['Pending', 'Sent to Bank', 'Confirmed'];
    if (!$appId || !in_array($status, $allowed)) {
        echo json_encode(['success' => false, 'message' => 'Invalid parameters.']); exit;
    }
    $stmt = $conn->prepare("UPDATE bank_applications SET Status = ? WHERE AppID = ?");
    $stmt->bind_param('si', $status, $appId);
    echo json_encode(['success' => $stmt->execute()]);
    $stmt->close();
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown action.']);
