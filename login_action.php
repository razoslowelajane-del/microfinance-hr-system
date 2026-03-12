<?php
require_once 'config/config.php';

header('Content-Type: application/json; charset=utf-8');

// Start secure session
if (session_status() === PHP_SESSION_NONE) {
    $isHttps = (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
        (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
    );

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $isHttps,   // localhost usually false
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    session_start();
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'login') {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($email) || empty($password)) {
            echo json_encode(['success' => false, 'message' => 'Please enter email and password']);
            exit;
        }
        
        // Get user account by email
        $sql = "SELECT * FROM useraccounts WHERE Email = ? AND AccountStatus = 'Active' LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Verify password
            if (password_verify($password, $user['PasswordHash'])) {
                // Generate OTP
                $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                $otpExpiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));
                
                // Store OTP in database
                $updateSql = "UPDATE useraccounts SET OTP_Code = ?, OTP_Expiry = ? WHERE AccountID = ?";
                $updateStmt = $conn->prepare($updateSql);
                $updateStmt->bind_param("ssi", $otp, $otpExpiry, $user['AccountID']);
                $updateStmt->execute();
                
                // Store in session for verification
                $_SESSION['pending_login'] = [
                    'account_id'  => (int)$user['AccountID'],
                    'email'       => $user['Email'],
                    'created_at'  => time()
                ];
                
                // Store portal preference
                if (isset($_POST['login_portal'])) {
                    $_SESSION['login_portal'] = trim((string)$_POST['login_portal']);
                }
                
                // Send OTP email
                $emailSent = sendOtpEmail($user['Email'], $otp, $user['Username']);
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'OTP sent to your email',
                    'requires_otp' => true
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid password']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'User not found or account inactive']);
        }
        exit;
    }
    
    if ($action === 'verify_otp') {
        $otp = trim($_POST['otp'] ?? '');
        
        if (empty($otp)) {
            echo json_encode(['success' => false, 'message' => 'Please enter OTP']);
            exit;
        }
        
        if (!isset($_SESSION['pending_login']) || !is_array($_SESSION['pending_login'])) {
            echo json_encode(['success' => false, 'message' => 'Session expired. Please login again.']);
            exit;
        }
        
        $pending = $_SESSION['pending_login'];

        if (
            !isset($pending['account_id']) ||
            !is_numeric($pending['account_id']) ||
            (int)$pending['account_id'] <= 0
        ) {
            unset($_SESSION['pending_login']);
            echo json_encode(['success' => false, 'message' => 'Session expired. Please login again.']);
            exit;
        }
        
        // Verify OTP - check matching code first, then check expiry in PHP
        $sql = "SELECT AccountID, Username, Email, OTP_Code, OTP_Expiry FROM useraccounts WHERE AccountID = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $pending['account_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Check if OTP code matches (secure comparison)
            if (!empty($user['OTP_Code']) && hash_equals((string)$user['OTP_Code'], (string)$otp)) {
                // Check if OTP is expired
                $expiryTime = strtotime((string)$user['OTP_Expiry']);
                $currentTime = time();
                
                if ($expiryTime !== false && $currentTime <= $expiryTime) {
                    // OTP is valid! Clear it and log in
                    $updateSql = "UPDATE useraccounts SET OTP_Code = NULL, OTP_Expiry = NULL WHERE AccountID = ?";
                    $updateStmt = $conn->prepare($updateSql);
                    $updateStmt->bind_param("i", $pending['account_id']);
                    $updateStmt->execute();
                    
                    // Get user roles (ALL roles) - include RoleID for officer detection
                    $rolesSql = "SELECT r.RoleID, r.RoleName
                                FROM roles r
                                JOIN useraccountroles ur ON r.RoleID = ur.RoleID
                                WHERE ur.AccountID = ?";
                    $rolesStmt = $conn->prepare($rolesSql);
                    $rolesStmt->bind_param("i", $pending['account_id']);
                    $rolesStmt->execute();
                    $rolesResult = $rolesStmt->get_result();

                    $roles = [];
                    $roleIDs = [];
                    while ($roleRow = $rolesResult->fetch_assoc()) {
                        $roles[] = $roleRow['RoleName'];
                        $roleIDs[] = (int)$roleRow['RoleID'];
                    }

                    // Priority for landing
                    $priority = [
                        'Administrator',
                        'HR Manager',
                        'General Manager',
                        'Department Officer',
                        'HR Data Specialist',
                        'HR Staff',
                        'Employee'
                    ];

                    $primaryRole = 'Employee';
                    foreach ($priority as $p) {
                        if (in_array($p, $roles, true)) {
                            $primaryRole = $p;
                            break;
                        }
                    }

                    // Load employee + department info
                    $empSql = "SELECT 
                                    ua.AccountID,
                                    ua.EmployeeID,
                                    ua.Username,
                                    ua.Email,
                                    e.FirstName,
                                    e.LastName,
                                    ei.DepartmentID AS EmpDepartmentID,
                                    d.DepartmentName AS EmpDepartmentName,
                                    ei.PositionID
                               FROM useraccounts ua
                               LEFT JOIN employee e ON e.EmployeeID = ua.EmployeeID
                               LEFT JOIN employmentinformation ei ON ei.EmployeeID = ua.EmployeeID
                               LEFT JOIN department d ON d.DepartmentID = ei.DepartmentID
                               WHERE ua.AccountID = ?
                               LIMIT 1";
                    $empStmt = $conn->prepare($empSql);
                    $empStmt->bind_param("i", $pending['account_id']);
                    $empStmt->execute();
                    $empRes = $empStmt->get_result();
                    $empRow = $empRes->fetch_assoc();

                    // Default department (from employment info)
                    $deptId = $empRow['EmpDepartmentID'] ?? null;
                    $deptName = $empRow['EmpDepartmentName'] ?? null;

                    // If Department Officer (RoleID=7), get handled department from department_officers
                    $isOfficer = in_array(7, $roleIDs, true);
                    if ($isOfficer) {
                        $offSql = "SELECT d.DepartmentID, d.DepartmentName
                                   FROM department_officers dof
                                   JOIN department d ON d.DepartmentID = dof.DepartmentID
                                   WHERE dof.AccountID = ?
                                     AND dof.IsActive = 1
                                   ORDER BY dof.IsPrimary DESC
                                   LIMIT 1";
                        $offStmt = $conn->prepare($offSql);
                        $offStmt->bind_param("i", $pending['account_id']);
                        $offStmt->execute();
                        $offRes = $offStmt->get_result();
                        $offRow = $offRes->fetch_assoc();

                        if ($offRow) {
                            $deptId = $offRow['DepartmentID'];
                            $deptName = $offRow['DepartmentName'];
                        } else {
                            echo json_encode([
                                'success' => false,
                                'message' => 'Officer account has no department assignment. Please contact admin.'
                            ]);
                            exit;
                        }
                    }

                    // Regenerate session ID after successful OTP login
                    session_regenerate_id(true);

                    // Set session variables
                    $_SESSION['user_id'] = (int)$user['AccountID'];
                    $_SESSION['username'] = $user['Username'];
                    $_SESSION['user_email'] = $user['Email'];
                    $_SESSION['user_name'] = $user['Username'];
                    $_SESSION['user_role'] = $primaryRole;
                    $_SESSION['user_roles'] = $roles;
                    $_SESSION['role_ids'] = $roleIDs;

                    // Department-based access control
                    $_SESSION['employee_id'] = !empty($empRow['EmployeeID']) ? (int)$empRow['EmployeeID'] : null;
                    $_SESSION['department_id'] = !empty($deptId) ? (int)$deptId : null;
                    $_SESSION['department_name'] = $deptName;

                    // Officer-specific
                    $_SESSION['is_officer'] = $isOfficer ? 1 : 0;
                    $_SESSION['officer_department_id'] = $isOfficer && !empty($deptId) ? (int)$deptId : null;
                    $_SESSION['officer_department_name'] = $isOfficer ? $deptName : null;

                    // Session security metadata for auth files
                    $_SESSION['login_time'] = time();
                    $_SESSION['last_activity'] = time();
                    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
                    $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? '';

                    // Clear pending login
                    unset($_SESSION['pending_login']);

                    // Redirect based on role and portal preference
                    $roleKey = strtolower(trim($primaryRole));
                    $portalInfo = $_SESSION['login_portal'] ?? 'workforce';

                    if ($portalInfo === 'ess') {
                        $redirectUrl = 'modules/ess/dashboard.php';
                    } else {
                        if ($roleKey === 'administrator') {
                            $redirectUrl = 'modules/admin/dashboard.php';

                        } elseif ($roleKey === 'hr manager') {
                            $redirectUrl = 'modules/manager/dashboard.php';

                        } elseif ($roleKey === 'general manager') {
                            $redirectUrl = 'modules/general_manager/dashboard.php';

                        } elseif ($roleKey === 'department officer') {
                            $redirectUrl = 'modules/officer/dashboard.php';

                        } elseif ($roleKey === 'hr data specialist') {
                            $redirectUrl = 'modules/corehumancapital/dashboard.php';

                        } elseif ($roleKey === 'hr staff') {
                            $redirectUrl = 'modules/hr1staff/dashboard.php';

                        } else {
                            $redirectUrl = 'dashboard.php';
                        }
                    }

                    // Cleanup portal session
                    unset($_SESSION['login_portal']);

                    echo json_encode([
                        'success' => true,
                        'message' => 'Login successful',
                        'redirect' => $redirectUrl,
                        'user_role' => $primaryRole,
                        'department' => $_SESSION['department_name']
                    ]);
                    exit;
                } else {
                    echo json_encode(['success' => false, 'message' => 'OTP has expired. Please login again.']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid OTP code']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'No OTP found. Please login again.']);
        }
        exit;
    }
    
    if ($action === 'resend_otp') {
        if (!isset($_SESSION['pending_login']) || !is_array($_SESSION['pending_login'])) {
            echo json_encode(['success' => false, 'message' => 'Session expired. Please login again.']);
            exit;
        }
        
        $pending = $_SESSION['pending_login'];

        if (
            !isset($pending['account_id'], $pending['email']) ||
            !is_numeric($pending['account_id']) ||
            (int)$pending['account_id'] <= 0
        ) {
            unset($_SESSION['pending_login']);
            echo json_encode(['success' => false, 'message' => 'Session expired. Please login again.']);
            exit;
        }
        
        // Generate new OTP
        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $otpExpiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));
        
        // Update OTP in database
        $updateSql = "UPDATE useraccounts SET OTP_Code = ?, OTP_Expiry = ? WHERE AccountID = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param("ssi", $otp, $otpExpiry, $pending['account_id']);
        $updateStmt->execute();
        
        // Send new OTP email
        $emailSent = sendOtpEmail($pending['email'], $otp);
        
        echo json_encode([
            'success' => true, 
            'message' => 'New OTP sent to your email',
            'email_sent' => $emailSent
        ]);
        exit;
    }
}

echo json_encode(['success' => false, 'message' => 'Invalid request']);
?>