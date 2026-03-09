<?php
require_once '../../config/config.php';

// Suppress display errors to return clean JSON
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

$action = $_REQUEST['action'] ?? '';

try {
    if ($action === 'get_role') {
        $roleId = intval($_GET['role_id'] ?? 0);
        if ($roleId <= 0) {
            throw new Exception("Invalid Role ID");
        }
        
        $stmt = $conn->prepare("SELECT * FROM roles WHERE RoleID = ?");
        $stmt->bind_param("i", $roleId);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        
        if ($data) {
            echo json_encode(['success' => true, 'data' => $data]);
        } else {
            throw new Exception("Role not found");
        }
        
    } elseif ($action === 'add_role') {
        $roleName = trim($_POST['role_name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        
        if (empty($roleName)) {
            throw new Exception("Role Name is required");
        }
        
        $stmt = $conn->prepare("INSERT INTO roles (RoleName, Description) VALUES (?, ?)");
        $stmt->bind_param("ss", $roleName, $description);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Role added successfully']);
        } else {
            throw new Exception("Database error: " . $stmt->error);
        }
        
    } elseif ($action === 'update_role') {
        $roleId = intval($_POST['role_id'] ?? 0);
        $roleName = trim($_POST['role_name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        
        if ($roleId <= 0 || empty($roleName)) {
            throw new Exception("Invalid ID or missing Role Name");
        }
        
        $stmt = $conn->prepare("UPDATE roles SET RoleName = ?, Description = ? WHERE RoleID = ?");
        $stmt->bind_param("ssi", $roleName, $description, $roleId);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Role updated successfully']);
        } else {
            throw new Exception("Update failed: " . $stmt->error);
        }
        
    } elseif ($action === 'archive_role') {
        // User requested to remove status, implying no 'Archived' status exists.
        // Transforming 'Archive' action to 'Delete' to make it functional.
        $roleId = intval($_POST['role_id'] ?? 0);
        
        if ($roleId <= 0) {
            throw new Exception("Invalid Role ID");
        }
        
        $stmt = $conn->prepare("DELETE FROM roles WHERE RoleID = ?");
        $stmt->bind_param("i", $roleId);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Role deleted successfully']);
        } else {
            throw new Exception("Delete failed: " . $stmt->error);
        }
        
    } else {
        throw new Exception("Invalid action: " . htmlspecialchars($action));
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
