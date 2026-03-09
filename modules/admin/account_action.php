<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['user_role']) !== 'administrator') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

require_once '../../config/config.php';

// Allow GET for fetching data, POST for updates
$action = $_REQUEST['action'] ?? '';

if ($action === 'add_account') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $account_status = $_POST['account_status'] ?? 'Active';
    $roles = $_POST['roles'] ?? [];

    // Validate inputs
    if (empty($username) || empty($email) || empty($password) || empty($roles)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        exit;
    }

    if ($password !== $confirm_password) {
        echo json_encode(['success' => false, 'message' => 'Passwords do not match']);
        exit;
    }

    if (strlen($password) < 6) {
        echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters']);
        exit;
    }

    // Check if username already exists
    $checkUserSql = "SELECT AccountID FROM useraccounts WHERE Username = ? OR Email = ?";
    $checkStmt = $conn->prepare($checkUserSql);
    $checkStmt->bind_param("ss", $username, $email);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    if ($checkResult->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Username or email already exists']);
        exit;
    }

    // Hash password
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    // Insert user account
    $insertSql = "INSERT INTO useraccounts (Username, Email, PasswordHash, AccountStatus, IsVerified) 
                  VALUES (?, ?, ?, ?, 1)";
    $insertStmt = $conn->prepare($insertSql);
    $insertStmt->bind_param("ssss", $username, $email, $passwordHash, $account_status);

    if (!$insertStmt->execute()) {
        echo json_encode(['success' => false, 'message' => 'Failed to create account: ' . $insertStmt->error]);
        exit;
    }

    $accountId = $conn->insert_id;

    // Assign roles
    if (!empty($roles)) {
        $roleInsertSql = "INSERT INTO useraccountroles (AccountID, RoleID) VALUES (?, ?)";
        $roleStmt = $conn->prepare($roleInsertSql);

        foreach ($roles as $roleId) {
            $roleId = intval($roleId);
            $roleStmt->bind_param("ii", $accountId, $roleId);
            if (!$roleStmt->execute()) {
                echo json_encode(['success' => false, 'message' => 'Failed to assign roles']);
                exit;
            }
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Account created successfully',
        'account_id' => $accountId
    ]);
    exit;
}

if ($action === 'get_account') {
    $accountId = intval($_GET['account_id'] ?? 0);

    if ($accountId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid account ID']);
        exit;
    }

    $stmt = $conn->prepare("SELECT AccountID, Username, Email, AccountStatus FROM useraccounts WHERE AccountID = ?");
    $stmt->bind_param("i", $accountId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        // Get roles
        $roleStmt = $conn->prepare("SELECT RoleID FROM useraccountroles WHERE AccountID = ?");
        $roleStmt->bind_param("i", $accountId);
        $roleStmt->execute();
        $roleResult = $roleStmt->get_result();
        $roles = [];
        while ($roleRow = $roleResult->fetch_assoc()) {
            $roles[] = $roleRow['RoleID'];
        }
        $row['Roles'] = $roles;

        echo json_encode(['success' => true, 'data' => $row]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Account not found']);
    }
    exit;
}

if ($action === 'update_account') {
    $accountId = intval($_POST['account_id'] ?? 0);
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    // Confirmation password check should be done on client side or if password is set
    $account_status = $_POST['account_status'] ?? 'Active';
    $roles = $_POST['roles'] ?? [];

    if ($accountId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid account ID']);
        exit;
    }

    // Check if username/email exists for OTHER accounts
    $checkStmt = $conn->prepare("SELECT AccountID FROM useraccounts WHERE (Username = ? OR Email = ?) AND AccountID != ?");
    $checkStmt->bind_param("ssi", $username, $email, $accountId);
    $checkStmt->execute();
    if ($checkStmt->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Username or Email already exists']);
        exit;
    }

    // Update basic info
    if (!empty($password)) {
        if (strlen($password) < 6) {
             echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters']);
             exit;
        }
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $updateStmt = $conn->prepare("UPDATE useraccounts SET Username=?, Email=?, AccountStatus=?, PasswordHash=? WHERE AccountID=?");
        $updateStmt->bind_param("ssssi", $username, $email, $account_status, $passwordHash, $accountId);
    } else {
        $updateStmt = $conn->prepare("UPDATE useraccounts SET Username=?, Email=?, AccountStatus=? WHERE AccountID=?");
        $updateStmt->bind_param("sssi", $username, $email, $account_status, $accountId);
    }

    if (!$updateStmt->execute()) {
        echo json_encode(['success' => false, 'message' => 'Failed to update account']);
        exit;
    }

    // Update roles
    $conn->query("DELETE FROM useraccountroles WHERE AccountID = $accountId");
    if (!empty($roles)) {
        $roleInsertSql = "INSERT INTO useraccountroles (AccountID, RoleID) VALUES (?, ?)";
        $roleStmt = $conn->prepare($roleInsertSql);
        foreach ($roles as $roleId) {
             $roleStmt->bind_param("ii", $accountId, $roleId);
             $roleStmt->execute();
        }
    }

    echo json_encode(['success' => true, 'message' => 'Account updated successfully']);
    exit;
}

if ($action === 'delete_account') {
    $accountId = intval($_POST['account_id'] ?? 0);

    if ($accountId === 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid account ID']);
        exit;
    }

    // Don't allow deleting admin yourself
    if ($accountId === $_SESSION['user_id']) {
        echo json_encode(['success' => false, 'message' => 'Cannot delete your own account']);
        exit;
    }

    // Delete user roles first
    $deleteRolesSql = "DELETE FROM useraccountroles WHERE AccountID = ?";
    $deleteRolesStmt = $conn->prepare($deleteRolesSql);
    $deleteRolesStmt->bind_param("i", $accountId);
    $deleteRolesStmt->execute();

    // Delete user account
    $deleteUserSql = "DELETE FROM useraccounts WHERE AccountID = ?";
    $deleteUserStmt = $conn->prepare($deleteUserSql);
    $deleteUserStmt->bind_param("i", $accountId);

    if ($deleteUserStmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Account deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete account']);
    }
    exit;
}

// Debugging: Return what was received if action is invalid
echo json_encode([
    'success' => false, 
    'message' => 'Invalid action. Received: ' . ($action ? $action : 'NULL') . '. POST: ' . json_encode($_POST) . '. RAW: ' . file_get_contents('php://input')
]);
?>
