<?php
header('Content-Type: application/json');
require_once "../../../config/config.php";
session_start();

// 1. Session Check
$employeeID = $_SESSION['employee_id'] ?? null;
if (!$employeeID) { 
    echo json_encode(["success" => false, "error" => "Session expired. Please login again."]); 
    exit; 
}

// 2. Get POST Data
$periodID = $_POST['period_id'] ?? null;
$category = $_POST['category'] ?? null;
$claimDate = $_POST['claim_date'] ?? null;
$amount = $_POST['amount'] ?? 0;
$description = $_POST['description'] ?? '';

// 3. Basic Validation
if (!$periodID || !$category || !$claimDate || $amount <= 0 || empty($description)) {
    echo json_encode(["success" => false, "error" => "Please complete all required fields."]);
    exit;
}

// 4. Receipt Upload Handling
$receiptPath = null;
if (isset($_FILES['receipt']) && $_FILES['receipt']['error'] == 0) {
    $dir = "../../../uploads/claims/";
    
    // Create directory if not exists
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    
    $ext = pathinfo($_FILES['receipt']['name'], PATHINFO_EXTENSION);
    // Use timestamp + employeeID for unique filename
    $filename = "claim_" . time() . "_" . $employeeID . "." . $ext;
    
    if (move_uploaded_file($_FILES['receipt']['tmp_name'], $dir . $filename)) {
        // Path to be stored in DB (relative to your root usually)
        $receiptPath = "uploads/claims/" . $filename;
    }
}

if (!$receiptPath) {
    echo json_encode(["success" => false, "error" => "Receipt image is required for reimbursement."]);
    exit;
}

// 5. Duplicate Check (Optional but Recommended)
$qCheck = "SELECT ClaimID FROM reimbursement_claims 
           WHERE EmployeeID = ? AND ClaimDate = ? AND Amount = ? AND Category = ? AND Status != 'REJECTED'";
$stmtC = $conn->prepare($qCheck);
$stmtC->bind_param("isds", $employeeID, $claimDate, $amount, $category);
$stmtC->execute();
if ($stmtC->get_result()->num_rows > 0) {
    echo json_encode(["success" => false, "error" => "You have already submitted a similar claim."]);
    exit;
}

// 6. Insert Claim to DB
// Matching your table: EmployeeID, PeriodID, ClaimDate, Category, Amount, Description, ReceiptImage, Status, CreatedAt
$sql = "INSERT INTO reimbursement_claims 
        (EmployeeID, PeriodID, ClaimDate, Category, Amount, Description, ReceiptImage, Status, CreatedAt) 
        VALUES (?, ?, ?, ?, ?, ?, ?, 'PENDING', NOW())";

$stmt = $conn->prepare($sql);

/**
 * ✅ DATA TYPES FOR BIND_PARAM:
 * i - EmployeeID (int)
 * i - PeriodID (int)
 * s - ClaimDate (string/date)
 * s - Category (enum/string)
 * d - Amount (decimal/double)
 * s - Description (text/string)
 * s - ReceiptImage (string)
 */
$stmt->bind_param("iissdss", $employeeID, $periodID, $claimDate, $category, $amount, $description, $receiptPath);

if ($stmt->execute()) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "error" => "Database Error: " . $conn->error]);
}

$stmt->close();
$conn->close();