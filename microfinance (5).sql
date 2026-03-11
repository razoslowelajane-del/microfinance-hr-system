-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 11, 2026 at 02:43 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `microfinance`
--

-- --------------------------------------------------------

--
-- Table structure for table `attendance_capture`
--

CREATE TABLE `attendance_capture` (
  `CaptureID` int(11) NOT NULL,
  `EventID` int(11) NOT NULL,
  `ImagePath` varchar(500) NOT NULL,
  `CapturedAt` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance_event`
--

CREATE TABLE `attendance_event` (
  `EventID` int(11) NOT NULL,
  `SessionID` int(11) NOT NULL,
  `EventType` enum('TIME_IN','TIME_OUT','BREAK_OUT','BREAK_IN') NOT NULL,
  `EventTime` datetime NOT NULL,
  `Latitude` decimal(10,7) DEFAULT NULL,
  `Longitude` decimal(10,7) DEFAULT NULL,
  `LocationID` int(11) DEFAULT NULL,
  `DistanceMeters` int(11) DEFAULT NULL,
  `GeoStatus` enum('IN_GEOFENCE','OUT_GEOFENCE','GPS_UNAVAILABLE') NOT NULL DEFAULT 'GPS_UNAVAILABLE',
  `FaceStatus` enum('MATCH','NO_MATCH','NO_FACE','MULTIPLE_FACES','CAMERA_ERROR') NOT NULL DEFAULT 'CAMERA_ERROR',
  `FaceScore` decimal(5,2) DEFAULT NULL,
  `LivenessStatus` enum('PASS','FAIL','NOT_CHECKED') NOT NULL DEFAULT 'NOT_CHECKED',
  `CapturedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance_session`
--

CREATE TABLE `attendance_session` (
  `SessionID` int(11) NOT NULL,
  `EmployeeID` int(11) NOT NULL,
  `WorkDate` date NOT NULL,
  `AssignmentID` int(11) DEFAULT NULL,
  `Status` enum('OPEN','CLOSED','FLAGGED') NOT NULL DEFAULT 'OPEN',
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `ClosedAt` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bankdetails`
--

CREATE TABLE `bankdetails` (
  `BankDetailID` int(11) NOT NULL,
  `EmployeeID` int(11) DEFAULT NULL,
  `BankName` varchar(100) DEFAULT NULL,
  `AccountNumber` varchar(50) DEFAULT NULL,
  `AccountType` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bankdetails`
--

INSERT INTO `bankdetails` (`BankDetailID`, `EmployeeID`, `BankName`, `AccountNumber`, `AccountType`) VALUES
(1, 1, 'BDO', '001234567890', 'Savings'),
(2, 2, 'BDO', '230-31005-2026', 'Payroll'),
(3, 3, 'BDO', '222-444-332-222', 'Payroll'),
(10, 30, 'BDO', 'LOG-ACC-00030', 'Payroll'),
(11, 31, 'BDO', 'LOG-ACC-00031', 'Payroll'),
(12, 32, 'BDO', 'LOG-ACC-00032', 'Payroll'),
(13, 33, 'BDO', 'FIN-ACC-00033', 'Payroll'),
(14, 34, 'BDO', 'FIN-ACC-00034', 'Payroll'),
(15, 35, 'BDO', 'FIN-ACC-00035', 'Payroll'),
(16, 36, 'BDO', 'HR-ACC-00036', 'Payroll');

-- --------------------------------------------------------

--
-- Table structure for table `bank_applications`
--

CREATE TABLE `bank_applications` (
  `AppID` int(11) NOT NULL,
  `EmployeeID` int(11) NOT NULL,
  `FormID` int(11) DEFAULT NULL,
  `UploadedPDF` varchar(500) NOT NULL,
  `Status` enum('Pending','Sent to Bank','Confirmed') NOT NULL DEFAULT 'Pending',
  `Notes` text DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `UpdatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bank_applications`
--

INSERT INTO `bank_applications` (`AppID`, `EmployeeID`, `FormID`, `UploadedPDF`, `Status`, `Notes`, `CreatedAt`, `UpdatedAt`) VALUES
(1, 3, 1, 'uploads/bank_submissions/emp3_1771691387.pdf', 'Confirmed', NULL, '2026-02-21 16:29:47', '2026-02-21 16:31:02'),
(2, 2, 1, 'uploads/bank_submissions/emp2_1771694089.pdf', 'Confirmed', NULL, '2026-02-21 17:14:49', '2026-02-21 17:33:11');

-- --------------------------------------------------------

--
-- Table structure for table `bank_forms_master`
--

CREATE TABLE `bank_forms_master` (
  `FormID` int(11) NOT NULL,
  `FormName` varchar(255) NOT NULL,
  `FilePath` varchar(500) NOT NULL,
  `IsActive` tinyint(1) NOT NULL DEFAULT 1,
  `UploadedBy` varchar(100) DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bank_forms_master`
--

INSERT INTO `bank_forms_master` (`FormID`, `FormName`, `FilePath`, `IsActive`, `UploadedBy`, `CreatedAt`) VALUES
(1, 'BDO', 'uploads/bank_forms/BDO_1771691221.pdf', 1, 'Red Gin Baldon', '2026-02-21 16:27:01');

-- --------------------------------------------------------

--
-- Table structure for table `department`
--

CREATE TABLE `department` (
  `DepartmentID` int(11) NOT NULL,
  `DepartmentName` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `department`
--

INSERT INTO `department` (`DepartmentID`, `DepartmentName`) VALUES
(1, 'Administration'),
(2, 'HR Department'),
(3, 'Logistics'),
(4, 'Finance');

-- --------------------------------------------------------

--
-- Table structure for table `department_officers`
--

CREATE TABLE `department_officers` (
  `DeptOfficerID` int(11) NOT NULL,
  `DepartmentID` int(11) NOT NULL,
  `AccountID` int(11) NOT NULL,
  `IsPrimary` tinyint(1) NOT NULL DEFAULT 1,
  `IsActive` tinyint(1) NOT NULL DEFAULT 1,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `UpdatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `department_officers`
--

INSERT INTO `department_officers` (`DeptOfficerID`, `DepartmentID`, `AccountID`, `IsPrimary`, `IsActive`, `CreatedAt`, `UpdatedAt`) VALUES
(1, 3, 7, 0, 0, '2026-02-25 10:19:30', '2026-03-09 17:48:34'),
(2, 4, 8, 1, 1, '2026-02-25 10:19:30', '2026-02-25 10:19:30'),
(3, 3, 9, 1, 1, '2026-02-25 10:19:30', '2026-02-25 10:19:30');

-- --------------------------------------------------------

--
-- Table structure for table `emergency_contacts`
--

CREATE TABLE `emergency_contacts` (
  `ContactID` int(11) NOT NULL,
  `EmployeeID` int(11) NOT NULL,
  `ContactName` varchar(200) NOT NULL,
  `Relationship` varchar(50) DEFAULT NULL,
  `PhoneNumber` varchar(20) NOT NULL,
  `IsPrimary` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `emergency_contacts`
--

INSERT INTO `emergency_contacts` (`ContactID`, `EmployeeID`, `ContactName`, `Relationship`, `PhoneNumber`, `IsPrimary`) VALUES
(1, 1, 'Andrie Suruiz', 'Father', '09223344556', 1),
(2, 2, 'Hero Baldon', 'Father', '09334455667', 1),
(3, 3, 'Daniela Magtangob', 'Wife', '09445566778', 1),
(10, 30, 'Maria Reyes', 'Mother', '09220000030', 1),
(11, 31, 'Lito Dela Cruz', 'Brother', '09220000031', 1),
(12, 32, 'Jose Mendoza', 'Father', '09220000032', 1),
(13, 33, 'Ryan Tan', 'Father', '09220000033', 1),
(14, 34, 'Elaine Garcia', 'Mother', '09220000034', 1),
(15, 35, 'Cathy Lopez', 'Sister', '09220000035', 1),
(16, 36, 'Marco Reyes', 'Spouse', '09220009999', 1);

-- --------------------------------------------------------

--
-- Table structure for table `employee`
--

CREATE TABLE `employee` (
  `EmployeeID` int(11) NOT NULL,
  `EmployeeCode` varchar(20) DEFAULT NULL,
  `FirstName` varchar(100) NOT NULL,
  `MiddleName` varchar(100) DEFAULT NULL,
  `LastName` varchar(100) NOT NULL,
  `DateOfBirth` date NOT NULL,
  `Gender` varchar(20) DEFAULT NULL,
  `PersonalEmail` varchar(150) DEFAULT NULL,
  `PhoneNumber` varchar(20) DEFAULT NULL,
  `PermanentAddress` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employee`
--

INSERT INTO `employee` (`EmployeeID`, `EmployeeCode`, `FirstName`, `MiddleName`, `LastName`, `DateOfBirth`, `Gender`, `PersonalEmail`, `PhoneNumber`, `PermanentAddress`) VALUES
(1, 'ADM20261001', 'Joshua', 'Rivero', 'Suruiz', '2004-04-06', 'Male', 'suruizandrie@gmail.com', '09111223344', 'Quezon City'),
(2, 'ADM20261002', 'Red Gin', 'B', 'Baldon', '2005-04-06', 'Male', 'red@gmail.comm', '09111223344', 'Quezon City'),
(3, 'HRDS20261003', 'Noriel', 'M', 'Dimailig', '2004-05-06', 'Male', 'riverojosh19@gmail.com', '09555223344', 'Quezon City'),
(4, 'LOG20261004', 'S Visor', 'Juan Miguel', 'Padre', '2005-02-10', 'Male', 'juanmiguelerdap69@gmail.com', '09271608518', 'Caloocan City'),
(5, 'FIN20261005', 'wela', 'g', 'razos', '2005-06-07', 'Female', 'razoslowelajane@gmail.com', '09170001111', 'Quezon City'),
(6, 'LOG20261006', 'Linda', 'M', 'walker', '2003-03-14', 'Female', 'lindawalker@gmail.com', '09171231234', 'Caloocan City'),
(30, 'LOG202630', 'Jessa', 'B', 'Reyes', '1998-08-19', 'Female', 'jessa.reyes@gmail.com', '09170000030', 'Caloocan City'),
(31, 'LOG202631', 'Boss', 'C', 'Atan', '1997-01-22', 'Male', 'paolo.delacruz@gmail.com', '09170000031', 'Quezon City'),
(32, 'LOG202632', 'Carlo', 'D', 'Mendoza', '1996-05-12', 'Male', 'carlo.mendoza@gmail.com', '09170000032', 'Quezon City'),
(33, 'FIN202633', 'Kevin', 'E', 'Tan', '1994-03-15', 'Male', 'kevin.tan@gmail.com', '09170000033', 'Taguig City'),
(34, 'FIN202634', 'Mika', 'F', 'Garcia', '1999-09-09', 'Female', 'mika.garcia@gmail.com', '09170000034', 'Pasig City'),
(35, 'FIN202635', 'Anna', 'G', 'Lopez', '1995-11-03', 'Female', 'anna.lopez@gmail.com', '09170000035', 'Makati City'),
(36, 'HRM2026036', 'rendon', 'L', 'labrador', '1993-07-18', 'Female', 'hr.manager36@company.com', '09170009999', 'Quezon City'),
(38, 'LOGTEST001', 'Test', 'Auto', 'Employee', '2000-01-01', 'Male', 'test.employee@company.com', '09170000000', 'Quezon City');

-- --------------------------------------------------------

--
-- Table structure for table `employee_face_profile`
--

CREATE TABLE `employee_face_profile` (
  `FaceProfileID` int(11) NOT NULL,
  `EmployeeID` int(11) NOT NULL,
  `Embedding` longtext NOT NULL,
  `Algorithm` varchar(50) NOT NULL DEFAULT 'face-api.js-128d',
  `EnrolledAt` datetime NOT NULL DEFAULT current_timestamp(),
  `EnrolledByAccountID` int(11) DEFAULT NULL,
  `IsActive` tinyint(1) NOT NULL DEFAULT 1,
  `UpdatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employee_face_profile`
--

INSERT INTO `employee_face_profile` (`FaceProfileID`, `EmployeeID`, `Embedding`, `Algorithm`, `EnrolledAt`, `EnrolledByAccountID`, `IsActive`, `UpdatedAt`) VALUES
(7, 30, '\0', 'face-api.js-128d', '2026-03-01 23:04:21', NULL, 1, '2026-03-01 15:04:21'),
(8, 31, '\0', 'face-api.js-128d', '2026-03-01 23:04:21', NULL, 1, '2026-03-01 15:04:21'),
(9, 32, '\0', 'face-api.js-128d', '2026-03-01 23:04:21', NULL, 1, '2026-03-01 15:04:21'),
(10, 33, '\0', 'face-api.js-128d', '2026-03-01 23:04:21', NULL, 1, '2026-03-01 15:04:21'),
(11, 34, '\0', 'face-api.js-128d', '2026-03-01 23:04:21', NULL, 1, '2026-03-01 15:04:21'),
(12, 35, '\0', 'face-api.js-128d', '2026-03-01 23:04:21', NULL, 1, '2026-03-01 15:04:21');

-- --------------------------------------------------------

--
-- Table structure for table `employee_leave_balances`
--

CREATE TABLE `employee_leave_balances` (
  `BalanceID` int(11) NOT NULL,
  `EmployeeID` int(11) NOT NULL,
  `LeaveTypeID` int(11) NOT NULL,
  `Year` int(4) NOT NULL,
  `TotalCredits` decimal(5,2) NOT NULL DEFAULT 0.00,
  `UsedCredits` decimal(5,2) NOT NULL DEFAULT 0.00,
  `RemainingCredits` decimal(5,2) GENERATED ALWAYS AS (`TotalCredits` - `UsedCredits`) STORED
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employee_leave_balances`
--

INSERT INTO `employee_leave_balances` (`BalanceID`, `EmployeeID`, `LeaveTypeID`, `Year`, `TotalCredits`, `UsedCredits`) VALUES
(25, 30, 1, 2026, 15.00, 0.00),
(26, 30, 2, 2026, 15.00, 0.00),
(27, 30, 3, 2026, 3.00, 0.00),
(28, 30, 5, 2026, 0.00, 0.00),
(29, 31, 1, 2026, 15.00, 0.00),
(30, 31, 2, 2026, 15.00, 0.00),
(31, 31, 3, 2026, 3.00, 0.00),
(32, 31, 5, 2026, 0.00, 0.00),
(33, 32, 1, 2026, 15.00, 0.00),
(34, 32, 2, 2026, 15.00, 0.00),
(35, 32, 3, 2026, 3.00, 0.00),
(36, 32, 5, 2026, 0.00, 0.00),
(37, 33, 1, 2026, 15.00, 0.00),
(38, 33, 2, 2026, 15.00, 0.00),
(39, 33, 3, 2026, 3.00, 0.00),
(40, 33, 5, 2026, 0.00, 0.00),
(41, 34, 1, 2026, 15.00, 0.00),
(42, 34, 2, 2026, 15.00, 0.00),
(43, 34, 3, 2026, 3.00, 0.00),
(44, 34, 5, 2026, 0.00, 0.00),
(45, 35, 1, 2026, 15.00, 0.00),
(46, 35, 2, 2026, 15.00, 0.00),
(47, 35, 3, 2026, 3.00, 0.00),
(48, 35, 5, 2026, 0.00, 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `employee_update_requests`
--

CREATE TABLE `employee_update_requests` (
  `RequestID` int(11) NOT NULL,
  `EmployeeID` int(11) NOT NULL,
  `RequestType` varchar(100) NOT NULL DEFAULT 'Update Information',
  `RequestData` text NOT NULL,
  `Status` enum('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
  `RequestDate` datetime NOT NULL DEFAULT current_timestamp(),
  `ReviewedBy` int(11) DEFAULT NULL,
  `ReviewDate` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employee_update_requests`
--

INSERT INTO `employee_update_requests` (`RequestID`, `EmployeeID`, `RequestType`, `RequestData`, `Status`, `RequestDate`, `ReviewedBy`, `ReviewDate`) VALUES
(1, 3, 'Update Information', '{\"BankName\":\"BDO\",\"BankAccountNumber\":\"222-444-332-222\"}', 'Approved', '2026-02-20 23:03:38', 3, '2026-02-20 23:13:16'),
(2, 3, 'Update Information', '{\"BankName\":\"BDO\",\"BankAccountNumber\":\"222-444-332-222\"}', 'Approved', '2026-02-21 01:08:30', 3, '2026-02-21 01:09:23'),
(3, 3, 'Update Information', '{\"BankName\":\"BDO\",\"BankAccountNumber\":\"222-444-332-222\",\"AccountType\":\"Payroll\"}', 'Approved', '2026-02-21 01:19:33', 3, '2026-02-21 01:22:16');

-- --------------------------------------------------------

--
-- Table structure for table `employmentinformation`
--

CREATE TABLE `employmentinformation` (
  `EmploymentID` int(11) NOT NULL,
  `EmployeeID` int(11) DEFAULT NULL,
  `DepartmentID` int(11) DEFAULT NULL,
  `PositionID` int(11) DEFAULT NULL,
  `HiringDate` date NOT NULL,
  `WorkEmail` varchar(150) DEFAULT NULL,
  `EmploymentStatus` varchar(50) DEFAULT NULL,
  `DigitalResume` varchar(255) DEFAULT NULL,
  `IDPicture` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employmentinformation`
--

INSERT INTO `employmentinformation` (`EmploymentID`, `EmployeeID`, `DepartmentID`, `PositionID`, `HiringDate`, `WorkEmail`, `EmploymentStatus`, `DigitalResume`, `IDPicture`) VALUES
(1, 1, 1, 1, '2026-02-08', 'suruiz.joshuabcp@gmail.com', 'Regular', NULL, NULL),
(2, 2, 2, 1, '2026-02-09', 'suruizandrie@gmail.com', 'Regular', NULL, NULL),
(3, 3, 2, 2, '2026-02-09', 'riverojosh19@gmail.com', 'Regular', NULL, NULL),
(4, 4, 3, 3, '2026-02-08', 'juanmiguelerdap69@gmail.com', 'Regular', NULL, NULL),
(5, 5, 4, 4, '2026-02-25', 'razoslowelajane@gmail.com', 'Regular', NULL, NULL),
(6, 6, 3, 3, '2026-02-25', 'lindawalker@company.com', 'Regular', NULL, NULL),
(25, 30, 3, 7, '2026-03-01', 'jessa.reyes@company.com', 'Regular', NULL, NULL),
(26, 31, 3, 7, '2026-03-01', 'bossatan@gmail.com', 'Regular', NULL, NULL),
(27, 32, 3, 7, '2026-03-01', 'carlo.mendoza@company.com', 'Regular', NULL, NULL),
(28, 33, 4, 8, '2026-03-01', 'kevin.tan@company.com', 'Regular', NULL, NULL),
(29, 34, 4, 8, '2026-03-01', 'mika.garcia@company.com', 'Regular', NULL, NULL),
(30, 35, 4, 8, '2026-03-01', 'anna.lopez@company.com', 'Regular', NULL, NULL),
(31, 36, 2, 9, '2026-03-04', 'hr.manager36@company.com', 'Regular', NULL, NULL),
(33, 38, 3, 7, '2026-03-10', 'test.employee@company.com', 'Regular', NULL, NULL);

--
-- Triggers `employmentinformation`
--
DELIMITER $$
CREATE TRIGGER `trg_auto_assign_supervisor_after_employment_insert` AFTER INSERT ON `employmentinformation` FOR EACH ROW BEGIN
    DECLARE vSupervisorAccountID INT DEFAULT NULL;
    DECLARE vEmployeeAccountID INT DEFAULT NULL;
    DECLARE vIsOfficer INT DEFAULT 0;

    /* Kunin ang account ng employee */
    SELECT ua.AccountID
      INTO vEmployeeAccountID
      FROM useraccounts ua
     WHERE ua.EmployeeID = NEW.EmployeeID
     LIMIT 1;

    /* Check kung officer ang employee */
    IF vEmployeeAccountID IS NOT NULL THEN
        SELECT COUNT(*)
          INTO vIsOfficer
          FROM useraccountroles uar
          INNER JOIN roles r ON r.RoleID = uar.RoleID
         WHERE uar.AccountID = vEmployeeAccountID
           AND r.RoleName = 'Department Officer';
    END IF;

    /* Hanapin ang active supervisor/officer ng same department */
    SELECT dof.AccountID
      INTO vSupervisorAccountID
      FROM department_officers dof
     WHERE dof.DepartmentID = NEW.DepartmentID
       AND dof.IsActive = 1
     ORDER BY dof.IsPrimary DESC, dof.UpdatedAt DESC, dof.DeptOfficerID DESC
     LIMIT 1;

    /* Insert lang kung:
       - may active supervisor
       - hindi officer ang employee
       - hindi sariling account ng supervisor
       - wala pang same active mapping
    */
    IF vSupervisorAccountID IS NOT NULL
       AND vIsOfficer = 0
       AND (vEmployeeAccountID IS NULL OR vSupervisorAccountID <> vEmployeeAccountID)
       AND NOT EXISTS (
            SELECT 1
              FROM supervisor_employees se
             WHERE se.EmployeeID = NEW.EmployeeID
               AND se.DepartmentID = NEW.DepartmentID
               AND se.IsActive = 1
       )
    THEN
        INSERT INTO supervisor_employees
            (SupervisorAccountID, EmployeeID, DepartmentID, IsActive, CreatedAt)
        VALUES
            (vSupervisorAccountID, NEW.EmployeeID, NEW.DepartmentID, 1, NOW());
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_auto_reassign_supervisor_after_employment_update` AFTER UPDATE ON `employmentinformation` FOR EACH ROW BEGIN
    DECLARE vSupervisorAccountID INT DEFAULT NULL;
    DECLARE vEmployeeAccountID INT DEFAULT NULL;
    DECLARE vIsOfficer INT DEFAULT 0;

    /* Gawin lang kapag nagbago ang department */
    IF NOT (OLD.DepartmentID <=> NEW.DepartmentID) THEN

        /* Deactivate lumang mapping */
        UPDATE supervisor_employees
           SET IsActive = 0
         WHERE EmployeeID = NEW.EmployeeID
           AND DepartmentID = OLD.DepartmentID
           AND IsActive = 1;

        /* Kunin ang account ng employee */
        SELECT ua.AccountID
          INTO vEmployeeAccountID
          FROM useraccounts ua
         WHERE ua.EmployeeID = NEW.EmployeeID
         LIMIT 1;

        /* Check kung officer ang employee */
        IF vEmployeeAccountID IS NOT NULL THEN
            SELECT COUNT(*)
              INTO vIsOfficer
              FROM useraccountroles uar
              INNER JOIN roles r ON r.RoleID = uar.RoleID
             WHERE uar.AccountID = vEmployeeAccountID
               AND r.RoleName = 'Department Officer';
        END IF;

        /* Hanapin active supervisor ng bagong department */
        SELECT dof.AccountID
          INTO vSupervisorAccountID
          FROM department_officers dof
         WHERE dof.DepartmentID = NEW.DepartmentID
           AND dof.IsActive = 1
         ORDER BY dof.IsPrimary DESC, dof.UpdatedAt DESC, dof.DeptOfficerID DESC
         LIMIT 1;

        /* Insert new active mapping kung valid */
        IF vSupervisorAccountID IS NOT NULL
           AND vIsOfficer = 0
           AND (vEmployeeAccountID IS NULL OR vSupervisorAccountID <> vEmployeeAccountID)
           AND NOT EXISTS (
                SELECT 1
                  FROM supervisor_employees se
                 WHERE se.EmployeeID = NEW.EmployeeID
                   AND se.DepartmentID = NEW.DepartmentID
                   AND se.IsActive = 1
           )
        THEN
            INSERT INTO supervisor_employees
                (SupervisorAccountID, EmployeeID, DepartmentID, IsActive, CreatedAt)
            VALUES
                (vSupervisorAccountID, NEW.EmployeeID, NEW.DepartmentID, 1, NOW());
        END IF;

    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `holidays`
--

CREATE TABLE `holidays` (
  `HolidayID` int(11) NOT NULL,
  `HolidayDate` date NOT NULL,
  `HolidayName` varchar(150) NOT NULL,
  `HolidayTypeID` int(11) NOT NULL,
  `IsActive` tinyint(1) NOT NULL DEFAULT 1,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `holidays`
--

INSERT INTO `holidays` (`HolidayID`, `HolidayDate`, `HolidayName`, `HolidayTypeID`, `IsActive`, `CreatedAt`) VALUES
(1, '2026-03-01', 'Demo Special Non-Working', 2, 1, '2026-02-25 13:34:42'),
(2, '2026-03-08', 'Demo Regular Holiday', 1, 1, '2026-02-25 13:34:42'),
(3, '2026-03-15', 'Demo Special Holiday', 2, 1, '2026-02-25 13:34:42');

-- --------------------------------------------------------

--
-- Table structure for table `holiday_type`
--

CREATE TABLE `holiday_type` (
  `HolidayTypeID` int(11) NOT NULL,
  `TypeCode` varchar(20) NOT NULL,
  `TypeName` varchar(100) NOT NULL,
  `PayMultiplier` decimal(5,2) NOT NULL DEFAULT 0.00,
  `IsPaid` tinyint(1) NOT NULL DEFAULT 0,
  `Description` text DEFAULT NULL,
  `IsActive` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `holiday_type`
--

INSERT INTO `holiday_type` (`HolidayTypeID`, `TypeCode`, `TypeName`, `PayMultiplier`, `IsPaid`, `Description`, `IsActive`) VALUES
(1, 'REG', 'Regular Holiday', 2.00, 1, 'Legal regular holiday', 1),
(2, 'SPEC', 'Special Non-Working', 1.30, 1, 'Special non-working holiday', 1),
(3, 'UNWRK', 'Unworked Regular Holiday', 1.00, 1, 'Paid holiday not worked', 1),
(4, 'FORCE', 'Force No Work', 0.00, 0, 'No work due to disaster/company declaration', 1);

-- --------------------------------------------------------

--
-- Table structure for table `leave_requests`
--

CREATE TABLE `leave_requests` (
  `LeaveRequestID` int(11) NOT NULL,
  `EmployeeID` int(11) NOT NULL,
  `LeaveTypeID` int(11) NOT NULL,
  `StartDate` date NOT NULL,
  `EndDate` date NOT NULL,
  `TotalDays` decimal(5,2) NOT NULL,
  `Reason` text DEFAULT NULL,
  `Status` enum('PENDING','APPROVED_BY_OFFICER','APPROVED_BY_HR','REJECTED','CANCELLED') NOT NULL DEFAULT 'PENDING',
  `OfficerApprovedBy` int(11) DEFAULT NULL,
  `HRApprovedBy` int(11) DEFAULT NULL,
  `OfficerNotes` text DEFAULT NULL,
  `HRNotes` text DEFAULT NULL,
  `AttachmentPath` varchar(500) DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `UpdatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `leave_requests`
--

INSERT INTO `leave_requests` (`LeaveRequestID`, `EmployeeID`, `LeaveTypeID`, `StartDate`, `EndDate`, `TotalDays`, `Reason`, `Status`, `OfficerApprovedBy`, `HRApprovedBy`, `OfficerNotes`, `HRNotes`, `AttachmentPath`, `CreatedAt`, `UpdatedAt`) VALUES
(3, 31, 2, '2026-03-07', '2026-03-17', 11.00, 'nilalagnat po covid ', 'REJECTED', 9, 26, '', '', NULL, '2026-03-04 19:27:45', '2026-03-09 14:29:53');

-- --------------------------------------------------------

--
-- Table structure for table `leave_types`
--

CREATE TABLE `leave_types` (
  `LeaveTypeID` int(11) NOT NULL,
  `LeaveName` varchar(50) NOT NULL,
  `IsPaid` tinyint(1) NOT NULL DEFAULT 1,
  `DefaultCredits` decimal(5,2) NOT NULL DEFAULT 0.00,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `leave_types`
--

INSERT INTO `leave_types` (`LeaveTypeID`, `LeaveName`, `IsPaid`, `DefaultCredits`, `CreatedAt`) VALUES
(1, 'Vacation Leave', 1, 15.00, '2026-02-25 01:02:36'),
(2, 'Sick Leave', 1, 15.00, '2026-02-25 01:02:36'),
(3, 'Emergency Leave', 1, 3.00, '2026-02-25 01:02:36'),
(4, 'Maternity/Paternity Leave', 1, 105.00, '2026-02-25 01:02:36'),
(5, 'Leave Without Pay', 0, 0.00, '2026-02-25 01:02:36');

-- --------------------------------------------------------

--
-- Table structure for table `positions`
--

CREATE TABLE `positions` (
  `PositionID` int(11) NOT NULL,
  `PositionName` varchar(100) NOT NULL,
  `PositionCode` varchar(10) DEFAULT NULL,
  `DepartmentID` int(11) DEFAULT NULL,
  `SalaryGradeID` int(11) DEFAULT NULL,
  `AuthorizedHeadcount` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `positions`
--

INSERT INTO `positions` (`PositionID`, `PositionName`, `PositionCode`, `DepartmentID`, `SalaryGradeID`, `AuthorizedHeadcount`) VALUES
(1, 'Administrator', 'ADM', 1, 6, 1),
(2, 'HR Data Specialist', 'HRDS', 2, 2, 1),
(3, 'Logistics Officer', 'LOGOFF', 3, 3, 1),
(4, 'Finance Officer', 'FINOFF', 4, 3, 1),
(7, 'Logistics Staff', 'LOGSTF', 3, 1, 20),
(8, 'Finance Staff', 'FINSTF', 4, 1, 20),
(9, 'HR Manager', 'HRMGR', 2, 5, 1);

-- --------------------------------------------------------

--
-- Table structure for table `reimbursement_claims`
--

CREATE TABLE `reimbursement_claims` (
  `ClaimID` int(11) NOT NULL,
  `EmployeeID` int(11) NOT NULL,
  `PeriodID` int(11) NOT NULL,
  `ClaimDate` date NOT NULL,
  `Category` enum('GAS','LOAD','TRAVEL','SUPPLIES','OTHERS') NOT NULL,
  `Amount` decimal(15,2) NOT NULL,
  `Description` text NOT NULL,
  `ReceiptImage` varchar(500) DEFAULT NULL,
  `Status` enum('PENDING','APPROVED_BY_OFFICER','APPROVED_BY_HR','PAID','REJECTED','CANCELLED') NOT NULL DEFAULT 'PENDING',
  `OfficerApprovedBy` int(11) DEFAULT NULL,
  `HRApprovedBy` int(11) DEFAULT NULL,
  `OfficerNotes` text DEFAULT NULL,
  `HRNotes` text DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reimbursement_claims`
--

INSERT INTO `reimbursement_claims` (`ClaimID`, `EmployeeID`, `PeriodID`, `ClaimDate`, `Category`, `Amount`, `Description`, `ReceiptImage`, `Status`, `OfficerApprovedBy`, `HRApprovedBy`, `OfficerNotes`, `HRNotes`, `CreatedAt`) VALUES
(7, 31, 1, '2026-02-17', 'GAS', 500.00, 'nawlan ng gas', 'uploads/claims/claim_1772656783_31.png', 'APPROVED_BY_HR', 9, 26, '', '', '2026-03-04 20:39:43');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `RoleID` int(11) NOT NULL,
  `RoleName` varchar(50) NOT NULL,
  `Description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`RoleID`, `RoleName`, `Description`) VALUES
(1, 'Administrator', 'System Administrator with full access'),
(2, 'HR Manager', 'Oversees the implementation, data integrity, and daily operation of Human Resources Information Systems'),
(3, 'HR Data Specialist', 'maintains, cleanses, and analyzes employee information'),
(4, 'HR Staff', 'provide essential operational support by managing the employee lifecycle, including recruiting, onboarding, payroll administration, and record-keeping'),
(5, 'General Manager', 'Top executive authority; final approver for organizational decisions'),
(7, 'Department Officer', 'Operational scheduler/validator for a department'),
(9, 'Employee', 'Standard employee self-service access');

-- --------------------------------------------------------

--
-- Table structure for table `roster_assignment`
--

CREATE TABLE `roster_assignment` (
  `AssignmentID` int(11) NOT NULL,
  `RosterID` int(11) NOT NULL,
  `EmployeeID` int(11) NOT NULL,
  `WorkDate` date NOT NULL,
  `ShiftCode` varchar(10) NOT NULL,
  `UpdatedByAccountID` int(11) DEFAULT NULL,
  `UpdatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roster_assignment`
--

INSERT INTO `roster_assignment` (`AssignmentID`, `RosterID`, `EmployeeID`, `WorkDate`, `ShiftCode`, `UpdatedByAccountID`, `UpdatedAt`) VALUES
(303, 14, 30, '2026-03-13', 'MD', 9, '2026-03-04 15:58:14'),
(304, 14, 30, '2026-03-14', 'MD', 9, '2026-03-04 15:58:14'),
(305, 14, 30, '2026-03-16', 'MD', 9, '2026-03-04 15:58:14'),
(306, 14, 30, '2026-03-17', 'MD', 9, '2026-03-04 15:58:14'),
(307, 14, 30, '2026-03-18', 'MD', 9, '2026-03-04 15:58:14'),
(308, 14, 30, '2026-03-19', 'MD', 9, '2026-03-04 15:58:14'),
(309, 14, 30, '2026-03-20', 'MD', 9, '2026-03-04 15:58:14'),
(310, 14, 30, '2026-03-21', 'MD', 9, '2026-03-04 15:58:14'),
(311, 14, 30, '2026-03-23', 'MD', 9, '2026-03-04 15:58:14'),
(312, 14, 30, '2026-03-24', 'MD', 9, '2026-03-04 15:58:14'),
(313, 14, 31, '2026-03-13', 'MD', 9, '2026-03-04 15:58:14'),
(314, 14, 31, '2026-03-14', 'MD', 9, '2026-03-04 15:58:14'),
(315, 14, 31, '2026-03-16', 'MD', 9, '2026-03-04 15:58:14'),
(316, 14, 31, '2026-03-17', 'MD', 9, '2026-03-04 15:58:14'),
(317, 14, 31, '2026-03-18', 'MD', 9, '2026-03-04 15:58:14'),
(318, 14, 31, '2026-03-19', 'MD', 9, '2026-03-04 15:58:14'),
(319, 14, 31, '2026-03-20', 'MD', 9, '2026-03-04 15:58:14'),
(320, 14, 31, '2026-03-21', 'MD', 9, '2026-03-04 15:58:14'),
(321, 14, 31, '2026-03-23', 'MD', 9, '2026-03-04 15:58:14'),
(322, 14, 31, '2026-03-24', 'MD', 9, '2026-03-04 15:58:14'),
(323, 14, 32, '2026-03-13', 'MD', 9, '2026-03-04 15:58:14'),
(324, 14, 32, '2026-03-14', 'MD', 9, '2026-03-04 15:58:14'),
(325, 14, 32, '2026-03-16', 'MD', 9, '2026-03-04 15:58:14'),
(326, 14, 32, '2026-03-17', 'MD', 9, '2026-03-04 15:58:14'),
(327, 14, 32, '2026-03-18', 'MD', 9, '2026-03-04 15:58:14'),
(328, 14, 32, '2026-03-19', 'MD', 9, '2026-03-04 15:58:14'),
(329, 14, 32, '2026-03-20', 'MD', 9, '2026-03-04 15:58:14'),
(330, 14, 32, '2026-03-21', 'MD', 9, '2026-03-04 15:58:14'),
(331, 14, 32, '2026-03-23', 'MD', 9, '2026-03-04 15:58:14'),
(332, 14, 32, '2026-03-24', 'MD', 9, '2026-03-04 15:58:14'),
(415, 15, 4, '2026-03-02', 'AM', 9, '2026-03-09 10:06:37'),
(416, 15, 4, '2026-03-03', 'AM', 9, '2026-03-09 10:06:37'),
(417, 15, 4, '2026-03-04', 'AM', 9, '2026-03-09 10:06:37'),
(418, 15, 4, '2026-03-05', 'AM', 9, '2026-03-09 10:06:37'),
(419, 15, 4, '2026-03-06', 'AM', 9, '2026-03-09 10:06:37'),
(420, 15, 4, '2026-03-07', 'AM', 9, '2026-03-09 10:06:37'),
(421, 15, 4, '2026-03-09', 'AM', 9, '2026-03-09 10:06:37'),
(422, 15, 4, '2026-03-10', 'AM', 9, '2026-03-09 10:06:37'),
(423, 15, 4, '2026-03-11', 'AM', 9, '2026-03-09 10:06:37'),
(424, 15, 4, '2026-03-12', 'AM', 9, '2026-03-09 10:06:37'),
(425, 15, 30, '2026-03-02', 'AM', 9, '2026-03-09 10:06:37'),
(426, 15, 30, '2026-03-03', 'AM', 9, '2026-03-09 10:06:37'),
(427, 15, 30, '2026-03-04', 'AM', 9, '2026-03-09 10:06:37'),
(428, 15, 30, '2026-03-05', 'AM', 9, '2026-03-09 10:06:37'),
(429, 15, 30, '2026-03-06', 'AM', 9, '2026-03-09 10:06:37'),
(430, 15, 30, '2026-03-07', 'AM', 9, '2026-03-09 10:06:37'),
(431, 15, 30, '2026-03-09', 'AM', 9, '2026-03-09 10:06:37'),
(432, 15, 30, '2026-03-10', 'AM', 9, '2026-03-09 10:06:37'),
(433, 15, 30, '2026-03-11', 'AM', 9, '2026-03-09 10:06:37'),
(434, 15, 30, '2026-03-12', 'AM', 9, '2026-03-09 10:06:37'),
(435, 15, 31, '2026-03-02', 'AM', 9, '2026-03-09 10:06:37'),
(436, 15, 31, '2026-03-03', 'AM', 9, '2026-03-09 10:06:37'),
(437, 15, 31, '2026-03-04', 'AM', 9, '2026-03-09 10:06:37'),
(438, 15, 31, '2026-03-05', 'AM', 9, '2026-03-09 10:06:37'),
(439, 15, 31, '2026-03-06', 'AM', 9, '2026-03-09 10:06:37'),
(440, 15, 32, '2026-03-02', 'AM', 9, '2026-03-09 10:06:37'),
(441, 15, 32, '2026-03-03', 'AM', 9, '2026-03-09 10:06:37'),
(442, 15, 32, '2026-03-04', 'AM', 9, '2026-03-09 10:06:37'),
(443, 15, 32, '2026-03-05', 'AM', 9, '2026-03-09 10:06:37'),
(444, 15, 32, '2026-03-06', 'AM', 9, '2026-03-09 10:06:37'),
(445, 15, 32, '2026-03-07', 'AM', 9, '2026-03-09 10:06:37'),
(446, 15, 32, '2026-03-09', 'AM', 9, '2026-03-09 10:06:37'),
(447, 15, 32, '2026-03-10', 'AM', 9, '2026-03-09 10:06:37'),
(448, 15, 32, '2026-03-11', 'AM', 9, '2026-03-09 10:06:37'),
(449, 15, 32, '2026-03-12', 'AM', 9, '2026-03-09 10:06:37');

-- --------------------------------------------------------

--
-- Table structure for table `salary_grades`
--

CREATE TABLE `salary_grades` (
  `SalaryGradeID` int(11) NOT NULL,
  `GradeLevel` varchar(10) NOT NULL,
  `MinSalary` decimal(15,2) NOT NULL,
  `MaxSalary` decimal(15,2) NOT NULL,
  `Description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `salary_grades`
--

INSERT INTO `salary_grades` (`SalaryGradeID`, `GradeLevel`, `MinSalary`, `MaxSalary`, `Description`) VALUES
(1, 'SG-1', 15000.00, 19000.00, 'Entry Support (HR Staff, Finance Assistants)'),
(2, 'SG-2', 21000.00, 30000.00, 'Professional I (Payroll Processor, HR Data Specialist)'),
(3, 'SG-3', 28000.00, 42000.00, 'Professional II (HR Analyst, Finance Officer)'),
(4, 'SG-4', 40000.00, 55000.00, 'Senior Specialist (Compensation Analyst, Senior Finance)'),
(5, 'SG-5', 53000.00, 75000.00, 'Management (HR Manager, Finance Manager)'),
(6, 'SG-6', 80000.00, 120000.00, 'Executive (Administrator, Director)');

-- --------------------------------------------------------

--
-- Table structure for table `shift_type`
--

CREATE TABLE `shift_type` (
  `ShiftCode` varchar(10) NOT NULL,
  `ShiftName` varchar(100) NOT NULL,
  `StartTime` time DEFAULT NULL,
  `EndTime` time DEFAULT NULL,
  `BreakMinutes` int(11) NOT NULL DEFAULT 0,
  `GraceMinutes` int(11) NOT NULL DEFAULT 0,
  `IsActive` tinyint(1) NOT NULL DEFAULT 1,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `UpdatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `shift_type`
--

INSERT INTO `shift_type` (`ShiftCode`, `ShiftName`, `StartTime`, `EndTime`, `BreakMinutes`, `GraceMinutes`, `IsActive`, `CreatedAt`, `UpdatedAt`) VALUES
('AM', 'Morning', '06:00:00', '14:00:00', 60, 5, 1, '2026-02-22 18:33:20', '2026-02-22 18:33:20'),
('GY', 'Graveyard', '22:00:00', '06:00:00', 60, 5, 1, '2026-02-22 18:33:20', '2026-02-22 18:33:20'),
('MD', 'Mid-Day', '14:00:00', '22:00:00', 60, 5, 1, '2026-02-22 18:33:20', '2026-02-22 18:33:20'),
('OFF', 'Day Off', NULL, NULL, 0, 0, 1, '2026-02-22 18:33:20', '2026-02-22 18:33:20');

-- --------------------------------------------------------

--
-- Table structure for table `supervisor_employees`
--

CREATE TABLE `supervisor_employees` (
  `MapID` int(11) NOT NULL,
  `SupervisorAccountID` int(11) NOT NULL,
  `EmployeeID` int(11) NOT NULL,
  `DepartmentID` int(11) NOT NULL,
  `IsActive` tinyint(1) NOT NULL DEFAULT 1,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `supervisor_employees`
--

INSERT INTO `supervisor_employees` (`MapID`, `SupervisorAccountID`, `EmployeeID`, `DepartmentID`, `IsActive`, `CreatedAt`) VALUES
(7, 9, 30, 3, 1, '2026-03-01 15:04:20'),
(8, 9, 31, 3, 1, '2026-03-01 15:04:20'),
(9, 9, 32, 3, 1, '2026-03-01 15:04:20'),
(10, 8, 33, 4, 1, '2026-03-01 15:04:20'),
(11, 8, 34, 4, 1, '2026-03-01 15:04:20'),
(12, 8, 35, 4, 1, '2026-03-01 15:04:20'),
(13, 9, 38, 3, 1, '2026-03-10 13:07:42');

-- --------------------------------------------------------

--
-- Table structure for table `taxbenefits`
--

CREATE TABLE `taxbenefits` (
  `BenefitID` int(11) NOT NULL,
  `EmployeeID` int(11) DEFAULT NULL,
  `TINNumber` varchar(50) DEFAULT NULL,
  `SSSNumber` varchar(50) DEFAULT NULL,
  `PhilHealthNumber` varchar(50) DEFAULT NULL,
  `PagIBIGNumber` varchar(50) DEFAULT NULL,
  `TaxStatus` varchar(50) DEFAULT NULL,
  `VerificationStatus` varchar(20) DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `taxbenefits`
--

INSERT INTO `taxbenefits` (`BenefitID`, `EmployeeID`, `TINNumber`, `SSSNumber`, `PhilHealthNumber`, `PagIBIGNumber`, `TaxStatus`, `VerificationStatus`) VALUES
(1, 1, '123-456-789-000', '34-1234567-8', '12-050123456-7', '1212-3434-5656', 'S', 'Verified'),
(2, 2, '321-654-987-000', '54-1234567-8', '14-050123456-7', '1414-3434-5656', 'S', 'Verified'),
(3, 3, '321-456-789-000', '65-1234567-8', '21-050123456-7', '1312-3434-5656', 'S', 'Verified'),
(10, 30, '111-111-111-030', '33-0000030-0', '12-0000030-0', '1200-0000-0030', 'S', 'Verified'),
(11, 31, '111-111-111-031', '33-0000031-0', '12-0000031-0', '1200-0000-0031', 'S', 'Verified'),
(12, 32, '111-111-111-032', '33-0000032-0', '12-0000032-0', '1200-0000-0032', 'S', 'Verified'),
(13, 33, '111-111-111-033', '33-0000033-0', '12-0000033-0', '1200-0000-0033', 'S', 'Verified'),
(14, 34, '111-111-111-034', '33-0000034-0', '12-0000034-0', '1200-0000-0034', 'S', 'Verified'),
(15, 35, '111-111-111-035', '33-0000035-0', '12-0000035-0', '1200-0000-0035', 'S', 'Verified'),
(16, 36, '222-222-222-036', '44-0000036-0', '12-0000036-0', '1200-0000-0036', 'S', 'Verified');

-- --------------------------------------------------------

--
-- Table structure for table `timesheet_daily`
--

CREATE TABLE `timesheet_daily` (
  `TimesheetDayID` int(11) NOT NULL,
  `PeriodID` int(11) NOT NULL,
  `EmployeeID` int(11) NOT NULL,
  `WorkDate` date NOT NULL,
  `AssignmentID` int(11) DEFAULT NULL,
  `SessionID` int(11) DEFAULT NULL,
  `ShiftCode` varchar(20) DEFAULT NULL,
  `ScheduledStart` time DEFAULT NULL,
  `ScheduledEnd` time DEFAULT NULL,
  `BreakMinutesPlanned` int(11) NOT NULL DEFAULT 0,
  `ActualTimeIn` datetime DEFAULT NULL,
  `ActualTimeOut` datetime DEFAULT NULL,
  `BreakMinutesActual` int(11) DEFAULT NULL,
  `RegularMinutes` int(11) NOT NULL DEFAULT 0,
  `OvertimeMinutes` int(11) NOT NULL DEFAULT 0,
  `NightDiffMinutes` int(11) NOT NULL DEFAULT 0,
  `LateMinutes` int(11) NOT NULL DEFAULT 0,
  `UndertimeMinutes` int(11) NOT NULL DEFAULT 0,
  `DayStatus` enum('OK','INCOMPLETE','FLAGGED','NO_SCHEDULE','OFF','ABSENT','LEAVE','HOLIDAY') NOT NULL DEFAULT 'OK',
  `Remarks` varchar(255) DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `UpdatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `timesheet_daily_code`
--

CREATE TABLE `timesheet_daily_code` (
  `DayCodeID` int(11) NOT NULL,
  `TimesheetDayID` int(11) NOT NULL,
  `PayCode` varchar(30) NOT NULL,
  `Minutes` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `timesheet_day_holiday`
--

CREATE TABLE `timesheet_day_holiday` (
  `DayHolidayID` int(11) NOT NULL,
  `TimesheetDayID` int(11) NOT NULL,
  `HolidayID` int(11) NOT NULL,
  `AppliedMultiplier` decimal(5,2) NOT NULL DEFAULT 0.00,
  `IsPaid` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `timesheet_employee_summary`
--

CREATE TABLE `timesheet_employee_summary` (
  `SummaryID` int(11) NOT NULL,
  `PeriodID` int(11) NOT NULL,
  `EmployeeID` int(11) NOT NULL,
  `DepartmentID` int(11) NOT NULL,
  `PositionID` int(11) NOT NULL,
  `IsEligibleForHolidayPay` tinyint(1) NOT NULL DEFAULT 1,
  `RegularHours` decimal(8,2) NOT NULL DEFAULT 0.00,
  `OvertimeHours` decimal(8,2) NOT NULL DEFAULT 0.00,
  `NightDiffHours` decimal(8,2) NOT NULL DEFAULT 0.00,
  `RegHolidayHours` decimal(8,2) NOT NULL DEFAULT 0.00,
  `SpecHolidayHours` decimal(8,2) NOT NULL DEFAULT 0.00,
  `UnworkedHolidayHours` decimal(8,2) NOT NULL DEFAULT 0.00,
  `HolidayOvertimeHours` decimal(8,2) NOT NULL DEFAULT 0.00,
  `LateMinutes` int(11) NOT NULL DEFAULT 0,
  `UndertimeMinutes` int(11) NOT NULL DEFAULT 0,
  `AbsencesHours` decimal(8,2) NOT NULL DEFAULT 0.00,
  `PaidLeaveHours` decimal(8,2) NOT NULL DEFAULT 0.00,
  `UnpaidLeaveHours` decimal(8,2) NOT NULL DEFAULT 0.00,
  `TotalPayableHours` decimal(8,2) NOT NULL DEFAULT 0.00,
  `Notes` varchar(255) DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `UpdatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `timesheet_employee_summary`
--

INSERT INTO `timesheet_employee_summary` (`SummaryID`, `PeriodID`, `EmployeeID`, `DepartmentID`, `PositionID`, `IsEligibleForHolidayPay`, `RegularHours`, `OvertimeHours`, `NightDiffHours`, `RegHolidayHours`, `SpecHolidayHours`, `UnworkedHolidayHours`, `HolidayOvertimeHours`, `LateMinutes`, `UndertimeMinutes`, `AbsencesHours`, `PaidLeaveHours`, `UnpaidLeaveHours`, `TotalPayableHours`, `Notes`, `CreatedAt`, `UpdatedAt`) VALUES
(1, 1, 4, 3, 3, 1, 40.00, 2.00, 0.00, 0.00, 0.00, 0.00, 0.00, 5, 0, 0.00, 0.00, 0.00, 0.00, 'Logistics summary demo', '2026-02-25 13:34:42', '2026-02-25 13:34:42'),
(2, 1, 6, 3, 3, 1, 38.00, 1.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 10, 0.00, 0.00, 0.00, 0.00, 'Logistics summary demo', '2026-02-25 13:34:42', '2026-02-25 13:34:42'),
(5, 2, 5, 4, 4, 1, 40.00, 1.50, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0.00, 0.00, 0.00, 0.00, 'Finance summary demo', '2026-02-25 13:34:42', '2026-02-25 13:34:42');

-- --------------------------------------------------------

--
-- Table structure for table `timesheet_pay_code`
--

CREATE TABLE `timesheet_pay_code` (
  `PayCode` varchar(30) NOT NULL,
  `PayCodeName` varchar(100) NOT NULL,
  `Category` enum('REGULAR','OT','NIGHTDIFF','HOLIDAY_REG','HOLIDAY_SPEC','HOLIDAY_UNWORKED','HOLIDAY_OT','LEAVE_PAID','LEAVE_UNPAID','ABSENCE','OTHER') NOT NULL,
  `IsActive` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `timesheet_pay_code`
--

INSERT INTO `timesheet_pay_code` (`PayCode`, `PayCodeName`, `Category`, `IsActive`) VALUES
('ABSENCE', 'Absence', 'ABSENCE', 1),
('HOL_OT', 'Holiday Overtime', 'HOLIDAY_OT', 1),
('HOL_REG', 'Regular Holiday Worked', 'HOLIDAY_REG', 1),
('HOL_SPEC', 'Special Holiday Worked', 'HOLIDAY_SPEC', 1),
('HOL_UNWORKED', 'Unworked Paid Holiday', 'HOLIDAY_UNWORKED', 1),
('LEAVE_PAID', 'Paid Leave', 'LEAVE_PAID', 1),
('LEAVE_UNPAID', 'Unpaid Leave', 'LEAVE_UNPAID', 1),
('ND', 'Night Differential', 'NIGHTDIFF', 1),
('OT', 'Overtime Hours', 'OT', 1),
('REG', 'Regular Hours', 'REGULAR', 1);

-- --------------------------------------------------------

--
-- Table structure for table `timesheet_period`
--

CREATE TABLE `timesheet_period` (
  `PeriodID` int(11) NOT NULL,
  `DepartmentID` int(11) NOT NULL,
  `StartDate` date NOT NULL,
  `EndDate` date NOT NULL,
  `Status` enum('DRAFT','FOR_REVIEW','RETURNED','APPROVED','FINALIZED') NOT NULL DEFAULT 'DRAFT',
  `PreparedByAccountID` int(11) NOT NULL,
  `PreparedAt` datetime DEFAULT NULL,
  `ReviewedByAccountID` int(11) DEFAULT NULL,
  `ReviewedAt` datetime DEFAULT NULL,
  `ReviewNotes` text DEFAULT NULL,
  `FinalizedByAccountID` int(11) DEFAULT NULL,
  `FinalizedAt` datetime DEFAULT NULL,
  `IsArchived` tinyint(1) NOT NULL DEFAULT 0,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `UpdatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `timesheet_period`
--

INSERT INTO `timesheet_period` (`PeriodID`, `DepartmentID`, `StartDate`, `EndDate`, `Status`, `PreparedByAccountID`, `PreparedAt`, `ReviewedByAccountID`, `ReviewedAt`, `ReviewNotes`, `FinalizedByAccountID`, `FinalizedAt`, `IsArchived`, `CreatedAt`, `UpdatedAt`) VALUES
(1, 3, '2026-02-17', '2026-02-23', 'FOR_REVIEW', 9, '2026-02-25 21:34:42', NULL, NULL, NULL, NULL, NULL, 0, '2026-02-25 13:34:42', '2026-03-03 14:42:47'),
(2, 4, '2026-02-17', '2026-02-23', 'FOR_REVIEW', 8, '2026-02-25 21:34:42', NULL, NULL, NULL, NULL, NULL, 0, '2026-02-25 13:34:42', '2026-02-25 13:34:42');

-- --------------------------------------------------------

--
-- Table structure for table `useraccountroles`
--

CREATE TABLE `useraccountroles` (
  `UserRoleID` int(11) NOT NULL,
  `AccountID` int(11) DEFAULT NULL,
  `RoleID` int(11) DEFAULT NULL,
  `AssignedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `useraccountroles`
--

INSERT INTO `useraccountroles` (`UserRoleID`, `AccountID`, `RoleID`, `AssignedAt`) VALUES
(2, 1, 1, '2026-02-08 16:34:53'),
(7, 2, 1, '2026-02-09 01:58:28'),
(8, 3, 3, '2026-02-09 07:19:29'),
(9, 4, 4, '2026-02-20 09:26:26'),
(13, 6, 7, '2026-02-21 14:00:20'),
(14, 7, 7, '2026-02-23 09:05:18'),
(15, 8, 7, '2026-02-25 05:53:02'),
(17, 9, 7, '2026-02-25 07:03:45'),
(24, 20, 9, '2026-03-01 15:04:20'),
(25, 21, 9, '2026-03-01 15:04:20'),
(26, 22, 9, '2026-03-01 15:04:20'),
(27, 23, 9, '2026-03-01 15:04:20'),
(28, 24, 9, '2026-03-01 15:04:20'),
(29, 25, 9, '2026-03-01 15:04:20'),
(30, 26, 2, '2026-03-04 08:42:20');

-- --------------------------------------------------------

--
-- Table structure for table `useraccounts`
--

CREATE TABLE `useraccounts` (
  `AccountID` int(11) NOT NULL,
  `EmployeeID` int(11) DEFAULT NULL,
  `Username` varchar(50) NOT NULL,
  `Email` varchar(150) DEFAULT NULL,
  `PasswordHash` varchar(255) NOT NULL,
  `OTP_Code` varchar(6) DEFAULT NULL,
  `OTP_Expiry` datetime DEFAULT NULL,
  `IsVerified` tinyint(1) DEFAULT 0,
  `AccountStatus` enum('Active','Inactive','Suspended') DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `useraccounts`
--

INSERT INTO `useraccounts` (`AccountID`, `EmployeeID`, `Username`, `Email`, `PasswordHash`, `OTP_Code`, `OTP_Expiry`, `IsVerified`, `AccountStatus`) VALUES
(1, 1, 'Joshua Suruiz', 'suruiz.joshuabcp@gmail.com', '$2y$10$MW7j07pxzC/nS6nNW2gt2efiw8hHy0OifrVMDTgnJ5PJVw/1i4uGa', NULL, NULL, 1, 'Active'),
(2, 2, 'Red Gin Baldon', 'suruizandrie@gmail.com', '$2y$10$Xqmv8TP/YYiax3DseufwDOmKYC4CRdqmf4hd2ASgMcwttHL2HT4.K', NULL, NULL, 1, 'Active'),
(3, 3, 'Noriel Dimailig', 'riverojosh19@gmail.com', '$2y$10$h7FqYl3dpl5lxi9M.1MROe7mKykN0xiBfZ5qtbLrnwczzqMQV.6dK', NULL, NULL, 1, 'Active'),
(4, NULL, 'Earl Laurence Alarcon', 'earl@gmail.com', '$2y$10$pNvPeIuYaJbrX1p6J.DC1uBfmkl.9LPpmpgEgLtvlH8n7Y.98Evqy', NULL, NULL, 1, 'Active'),
(6, NULL, 'Glory Job', 'glory@gmail.com', '$2y$10$YobyvYhmp2hYgDAfhc0jvOImU.ue3DEh5mL9.KGzMKQiZ08ouN9ma', NULL, NULL, 1, 'Active'),
(7, 4, 'juanmiguel', 'juanmiguelerdap69@gmail.com', '$2y$10$xCwzIeCPgZ7X8yDBFZZQOuLGvaHAZYujmlcKaa57sZjePHYM58ELS', NULL, NULL, 1, 'Active'),
(8, 5, 'wela ', 'razoslowelajane@gmail.com', '$2y$10$omRTVdYZDdypTaLUvo.i5u6K0xFkgEAabGazdWPVJXgnXmnqTD8q6', NULL, NULL, 1, 'Active'),
(9, 6, 'linda', 'lindawalker@gmail.com', '$2y$10$X7es6DOsUEU.X1heXHJSDevjXUFEIdfIVdSAXtEoWMT5jp0wHlINC', NULL, NULL, 1, 'Active'),
(20, 30, 'jessa.reyes', 'jessa.reyes@company.com', '$2y$10$MW7j07pxzC/nS6nNW2gt2efiw8hHy0OifrVMDTgnJ5PJVw/1i4uGa', NULL, NULL, 1, 'Active'),
(21, 31, 'boss atan', 'bossatan@gmail.com', '$2y$10$Warbr3rA93egHCkki1ZpR.G7Y327jjcdF5La0T1EeAzs9VFG1TjsW', NULL, NULL, 1, 'Active'),
(22, 32, 'carlo.mendoza', 'carlo.mendoza@company.com', '$2y$10$MW7j07pxzC/nS6nNW2gt2efiw8hHy0OifrVMDTgnJ5PJVw/1i4uGa', NULL, NULL, 1, 'Active'),
(23, 33, 'kevin.tan', 'kevin.tan@company.com', '$2y$10$MW7j07pxzC/nS6nNW2gt2efiw8hHy0OifrVMDTgnJ5PJVw/1i4uGa', NULL, NULL, 1, 'Active'),
(24, 34, 'mika.garcia', 'mika.garcia@company.com', '$2y$10$MW7j07pxzC/nS6nNW2gt2efiw8hHy0OifrVMDTgnJ5PJVw/1i4uGa', NULL, NULL, 1, 'Active'),
(25, 35, 'anna.lopez', 'anna.lopez@company.com', '$2y$10$MW7j07pxzC/nS6nNW2gt2efiw8hHy0OifrVMDTgnJ5PJVw/1i4uGa', NULL, NULL, 1, 'Active'),
(26, 36, 'rendon', 'rendon@gmail.com', '$2y$10$qKL9YAgjzMXiQDdQKsS5oeWxKBqgu77AO4xpUI6Bt6DbNRRMe6NFm', NULL, NULL, 1, 'Active');

-- --------------------------------------------------------

--
-- Table structure for table `weekly_roster`
--

CREATE TABLE `weekly_roster` (
  `RosterID` int(11) NOT NULL,
  `DepartmentID` int(11) NOT NULL,
  `WeekStart` date NOT NULL,
  `WeekEnd` date NOT NULL,
  `Status` enum('DRAFT','FOR_REVIEW','RETURNED','APPROVED','PUBLISHED') NOT NULL DEFAULT 'DRAFT',
  `CreatedByAccountID` int(11) NOT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `UpdatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `ReviewedByAccountID` int(11) DEFAULT NULL,
  `ReviewedAt` datetime DEFAULT NULL,
  `ReviewNotes` text DEFAULT NULL,
  `PublishedByAccountID` int(11) DEFAULT NULL,
  `PublishedAt` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `weekly_roster`
--

INSERT INTO `weekly_roster` (`RosterID`, `DepartmentID`, `WeekStart`, `WeekEnd`, `Status`, `CreatedByAccountID`, `CreatedAt`, `UpdatedAt`, `ReviewedByAccountID`, `ReviewedAt`, `ReviewNotes`, `PublishedByAccountID`, `PublishedAt`) VALUES
(14, 3, '2026-03-13', '2026-03-24', 'FOR_REVIEW', 9, '2026-03-04 15:54:57', '2026-03-09 06:56:43', 26, '2026-03-05 00:18:13', '', 26, '2026-03-05 00:18:13'),
(15, 3, '2026-03-01', '2026-03-12', 'DRAFT', 9, '2026-03-04 16:09:41', '2026-03-04 16:09:41', NULL, NULL, NULL, NULL, NULL),
(16, 3, '2026-02-25', '2026-02-28', 'DRAFT', 9, '2026-03-04 22:19:06', '2026-03-04 22:19:06', NULL, NULL, NULL, NULL, NULL),
(17, 3, '2026-03-25', '2026-03-31', 'DRAFT', 9, '2026-03-07 11:11:58', '2026-03-07 11:11:58', NULL, NULL, NULL, NULL, NULL),
(18, 3, '2026-04-01', '2026-04-12', 'DRAFT', 9, '2026-03-07 11:12:02', '2026-03-07 11:12:02', NULL, NULL, NULL, NULL, NULL),
(19, 4, '2026-03-01', '2026-03-12', 'DRAFT', 8, '2026-03-09 06:52:47', '2026-03-09 06:52:47', NULL, NULL, NULL, NULL, NULL),
(20, 4, '2026-02-25', '2026-02-28', 'DRAFT', 8, '2026-03-09 06:52:50', '2026-03-09 06:52:50', NULL, NULL, NULL, NULL, NULL),
(21, 4, '2026-02-13', '2026-02-24', 'DRAFT', 8, '2026-03-09 06:52:51', '2026-03-09 06:52:51', NULL, NULL, NULL, NULL, NULL),
(22, 4, '2026-02-01', '2026-02-12', 'DRAFT', 8, '2026-03-09 06:52:52', '2026-03-09 06:52:52', NULL, NULL, NULL, NULL, NULL),
(23, 4, '2026-03-13', '2026-03-24', 'DRAFT', 8, '2026-03-09 06:52:54', '2026-03-09 06:52:54', NULL, NULL, NULL, NULL, NULL),
(24, 4, '2026-03-25', '2026-03-31', 'DRAFT', 8, '2026-03-09 06:52:54', '2026-03-09 06:52:54', NULL, NULL, NULL, NULL, NULL),
(25, 4, '2026-04-01', '2026-04-12', 'DRAFT', 8, '2026-03-09 06:52:54', '2026-03-09 06:52:54', NULL, NULL, NULL, NULL, NULL),
(26, 3, '2026-02-13', '2026-02-24', 'DRAFT', 9, '2026-03-09 06:54:20', '2026-03-09 06:54:20', NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `work_locations`
--

CREATE TABLE `work_locations` (
  `LocationID` int(11) NOT NULL,
  `LocationName` varchar(150) NOT NULL,
  `Latitude` decimal(10,7) NOT NULL,
  `Longitude` decimal(10,7) NOT NULL,
  `RadiusMeters` int(11) NOT NULL DEFAULT 0,
  `IsActive` tinyint(1) NOT NULL DEFAULT 1,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `UpdatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `work_locations`
--

INSERT INTO `work_locations` (`LocationID`, `LocationName`, `Latitude`, `Longitude`, `RadiusMeters`, `IsActive`, `CreatedAt`, `UpdatedAt`) VALUES
(2, 'Bestlink College of the Philippines - QC', 14.7285800, 121.0416100, 100, 1, '2026-03-09 09:56:16', '2026-03-09 09:56:16');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attendance_capture`
--
ALTER TABLE `attendance_capture`
  ADD PRIMARY KEY (`CaptureID`),
  ADD UNIQUE KEY `uq_ac_event` (`EventID`);

--
-- Indexes for table `attendance_event`
--
ALTER TABLE `attendance_event`
  ADD PRIMARY KEY (`EventID`),
  ADD KEY `idx_ae_session_time` (`SessionID`,`EventTime`),
  ADD KEY `idx_ae_type` (`EventType`),
  ADD KEY `idx_ae_location` (`LocationID`),
  ADD KEY `idx_ae_geostatus` (`GeoStatus`),
  ADD KEY `idx_ae_facestatus` (`FaceStatus`);

--
-- Indexes for table `attendance_session`
--
ALTER TABLE `attendance_session`
  ADD PRIMARY KEY (`SessionID`),
  ADD UNIQUE KEY `uq_as_emp_date` (`EmployeeID`,`WorkDate`),
  ADD KEY `idx_as_date` (`WorkDate`),
  ADD KEY `idx_as_status` (`Status`),
  ADD KEY `idx_as_assignment` (`AssignmentID`);

--
-- Indexes for table `bankdetails`
--
ALTER TABLE `bankdetails`
  ADD PRIMARY KEY (`BankDetailID`),
  ADD KEY `EmployeeID` (`EmployeeID`);

--
-- Indexes for table `bank_applications`
--
ALTER TABLE `bank_applications`
  ADD PRIMARY KEY (`AppID`),
  ADD KEY `fk_ba_form` (`FormID`);

--
-- Indexes for table `bank_forms_master`
--
ALTER TABLE `bank_forms_master`
  ADD PRIMARY KEY (`FormID`);

--
-- Indexes for table `department`
--
ALTER TABLE `department`
  ADD PRIMARY KEY (`DepartmentID`);

--
-- Indexes for table `department_officers`
--
ALTER TABLE `department_officers`
  ADD PRIMARY KEY (`DeptOfficerID`),
  ADD UNIQUE KEY `uq_dept_account` (`DepartmentID`,`AccountID`),
  ADD KEY `idx_dept_active` (`DepartmentID`,`IsActive`),
  ADD KEY `idx_account_active` (`AccountID`,`IsActive`);

--
-- Indexes for table `emergency_contacts`
--
ALTER TABLE `emergency_contacts`
  ADD PRIMARY KEY (`ContactID`),
  ADD KEY `EmployeeID` (`EmployeeID`);

--
-- Indexes for table `employee`
--
ALTER TABLE `employee`
  ADD PRIMARY KEY (`EmployeeID`),
  ADD UNIQUE KEY `PersonalEmail` (`PersonalEmail`);

--
-- Indexes for table `employee_face_profile`
--
ALTER TABLE `employee_face_profile`
  ADD PRIMARY KEY (`FaceProfileID`),
  ADD UNIQUE KEY `uq_efp_employee` (`EmployeeID`),
  ADD KEY `idx_efp_active` (`IsActive`),
  ADD KEY `idx_efp_enrolled_by` (`EnrolledByAccountID`);

--
-- Indexes for table `employee_leave_balances`
--
ALTER TABLE `employee_leave_balances`
  ADD PRIMARY KEY (`BalanceID`),
  ADD UNIQUE KEY `uq_emp_leave_year` (`EmployeeID`,`LeaveTypeID`,`Year`),
  ADD KEY `fk_elb_leavetype` (`LeaveTypeID`);

--
-- Indexes for table `employee_update_requests`
--
ALTER TABLE `employee_update_requests`
  ADD PRIMARY KEY (`RequestID`),
  ADD KEY `EmployeeID` (`EmployeeID`);

--
-- Indexes for table `employmentinformation`
--
ALTER TABLE `employmentinformation`
  ADD PRIMARY KEY (`EmploymentID`),
  ADD UNIQUE KEY `WorkEmail` (`WorkEmail`),
  ADD KEY `EmployeeID` (`EmployeeID`),
  ADD KEY `DepartmentID` (`DepartmentID`),
  ADD KEY `fk_employment_position` (`PositionID`);

--
-- Indexes for table `holidays`
--
ALTER TABLE `holidays`
  ADD PRIMARY KEY (`HolidayID`),
  ADD UNIQUE KEY `uq_holiday_date` (`HolidayDate`),
  ADD KEY `idx_holidays_type` (`HolidayTypeID`),
  ADD KEY `idx_holidays_active_date` (`IsActive`,`HolidayDate`);

--
-- Indexes for table `holiday_type`
--
ALTER TABLE `holiday_type`
  ADD PRIMARY KEY (`HolidayTypeID`),
  ADD UNIQUE KEY `uq_ht_typecode` (`TypeCode`);

--
-- Indexes for table `leave_requests`
--
ALTER TABLE `leave_requests`
  ADD PRIMARY KEY (`LeaveRequestID`),
  ADD KEY `fk_lr_leavetype` (`LeaveTypeID`),
  ADD KEY `fk_lr_officer` (`OfficerApprovedBy`),
  ADD KEY `fk_lr_hr` (`HRApprovedBy`),
  ADD KEY `idx_lr_employee_status` (`EmployeeID`,`Status`),
  ADD KEY `idx_lr_dates` (`StartDate`,`EndDate`);

--
-- Indexes for table `leave_types`
--
ALTER TABLE `leave_types`
  ADD PRIMARY KEY (`LeaveTypeID`);

--
-- Indexes for table `positions`
--
ALTER TABLE `positions`
  ADD PRIMARY KEY (`PositionID`),
  ADD KEY `DepartmentID` (`DepartmentID`),
  ADD KEY `fk_position_salary_grade` (`SalaryGradeID`);

--
-- Indexes for table `reimbursement_claims`
--
ALTER TABLE `reimbursement_claims`
  ADD PRIMARY KEY (`ClaimID`),
  ADD KEY `fk_rc_officer` (`OfficerApprovedBy`),
  ADD KEY `fk_rc_hr` (`HRApprovedBy`),
  ADD KEY `idx_rc_employee_status` (`EmployeeID`,`Status`),
  ADD KEY `idx_rc_period` (`PeriodID`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`RoleID`);

--
-- Indexes for table `roster_assignment`
--
ALTER TABLE `roster_assignment`
  ADD PRIMARY KEY (`AssignmentID`),
  ADD UNIQUE KEY `uq_ra_roster_emp_date` (`RosterID`,`EmployeeID`,`WorkDate`),
  ADD KEY `idx_ra_emp_date` (`EmployeeID`,`WorkDate`),
  ADD KEY `idx_ra_shift` (`ShiftCode`),
  ADD KEY `fk_ra_updated_by` (`UpdatedByAccountID`);

--
-- Indexes for table `salary_grades`
--
ALTER TABLE `salary_grades`
  ADD PRIMARY KEY (`SalaryGradeID`);

--
-- Indexes for table `shift_type`
--
ALTER TABLE `shift_type`
  ADD PRIMARY KEY (`ShiftCode`);

--
-- Indexes for table `supervisor_employees`
--
ALTER TABLE `supervisor_employees`
  ADD PRIMARY KEY (`MapID`),
  ADD UNIQUE KEY `uq_supervisor_emp_dept` (`SupervisorAccountID`,`EmployeeID`,`DepartmentID`),
  ADD UNIQUE KEY `uq_supervisor_employee_department` (`SupervisorAccountID`,`EmployeeID`,`DepartmentID`),
  ADD KEY `idx_supervisor_active` (`SupervisorAccountID`,`IsActive`),
  ADD KEY `fk_se_employee` (`EmployeeID`),
  ADD KEY `fk_se_dept` (`DepartmentID`);

--
-- Indexes for table `taxbenefits`
--
ALTER TABLE `taxbenefits`
  ADD PRIMARY KEY (`BenefitID`),
  ADD KEY `EmployeeID` (`EmployeeID`);

--
-- Indexes for table `timesheet_daily`
--
ALTER TABLE `timesheet_daily`
  ADD PRIMARY KEY (`TimesheetDayID`),
  ADD UNIQUE KEY `uq_tsd_period_emp_date` (`PeriodID`,`EmployeeID`,`WorkDate`),
  ADD KEY `idx_tsd_emp_date` (`EmployeeID`,`WorkDate`),
  ADD KEY `idx_tsd_period_date` (`PeriodID`,`WorkDate`),
  ADD KEY `idx_tsd_assignment` (`AssignmentID`),
  ADD KEY `idx_tsd_session` (`SessionID`),
  ADD KEY `idx_tsd_status` (`DayStatus`);

--
-- Indexes for table `timesheet_daily_code`
--
ALTER TABLE `timesheet_daily_code`
  ADD PRIMARY KEY (`DayCodeID`),
  ADD UNIQUE KEY `uq_tdc_day_paycode` (`TimesheetDayID`,`PayCode`),
  ADD KEY `idx_tdc_paycode` (`PayCode`);

--
-- Indexes for table `timesheet_day_holiday`
--
ALTER TABLE `timesheet_day_holiday`
  ADD PRIMARY KEY (`DayHolidayID`),
  ADD UNIQUE KEY `uq_tdh_day` (`TimesheetDayID`),
  ADD KEY `idx_tdh_holiday` (`HolidayID`);

--
-- Indexes for table `timesheet_employee_summary`
--
ALTER TABLE `timesheet_employee_summary`
  ADD PRIMARY KEY (`SummaryID`),
  ADD UNIQUE KEY `uq_tes_period_emp` (`PeriodID`,`EmployeeID`),
  ADD KEY `idx_tes_department` (`DepartmentID`),
  ADD KEY `idx_tes_position` (`PositionID`),
  ADD KEY `fk_tes_employee` (`EmployeeID`);

--
-- Indexes for table `timesheet_pay_code`
--
ALTER TABLE `timesheet_pay_code`
  ADD PRIMARY KEY (`PayCode`),
  ADD KEY `idx_tpc_category` (`Category`);

--
-- Indexes for table `timesheet_period`
--
ALTER TABLE `timesheet_period`
  ADD PRIMARY KEY (`PeriodID`),
  ADD UNIQUE KEY `uq_tp_dept_cutoff` (`DepartmentID`,`StartDate`,`EndDate`),
  ADD KEY `idx_tp_status` (`Status`),
  ADD KEY `idx_tp_prepared_by` (`PreparedByAccountID`),
  ADD KEY `idx_tp_reviewed_by` (`ReviewedByAccountID`),
  ADD KEY `idx_tp_finalized_by` (`FinalizedByAccountID`);

--
-- Indexes for table `useraccountroles`
--
ALTER TABLE `useraccountroles`
  ADD PRIMARY KEY (`UserRoleID`),
  ADD KEY `AccountID` (`AccountID`),
  ADD KEY `RoleID` (`RoleID`);

--
-- Indexes for table `useraccounts`
--
ALTER TABLE `useraccounts`
  ADD PRIMARY KEY (`AccountID`),
  ADD UNIQUE KEY `Username` (`Username`),
  ADD UNIQUE KEY `EmployeeID` (`EmployeeID`),
  ADD KEY `idx_email` (`Email`);

--
-- Indexes for table `weekly_roster`
--
ALTER TABLE `weekly_roster`
  ADD PRIMARY KEY (`RosterID`),
  ADD UNIQUE KEY `uq_wr_dept_week` (`DepartmentID`,`WeekStart`,`WeekEnd`),
  ADD KEY `idx_wr_status` (`Status`),
  ADD KEY `idx_wr_created_by` (`CreatedByAccountID`),
  ADD KEY `idx_wr_reviewed_by` (`ReviewedByAccountID`),
  ADD KEY `idx_wr_published_by` (`PublishedByAccountID`);

--
-- Indexes for table `work_locations`
--
ALTER TABLE `work_locations`
  ADD PRIMARY KEY (`LocationID`),
  ADD KEY `idx_wl_active` (`IsActive`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attendance_capture`
--
ALTER TABLE `attendance_capture`
  MODIFY `CaptureID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attendance_event`
--
ALTER TABLE `attendance_event`
  MODIFY `EventID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `attendance_session`
--
ALTER TABLE `attendance_session`
  MODIFY `SessionID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `bankdetails`
--
ALTER TABLE `bankdetails`
  MODIFY `BankDetailID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `bank_applications`
--
ALTER TABLE `bank_applications`
  MODIFY `AppID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `bank_forms_master`
--
ALTER TABLE `bank_forms_master`
  MODIFY `FormID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `department`
--
ALTER TABLE `department`
  MODIFY `DepartmentID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `department_officers`
--
ALTER TABLE `department_officers`
  MODIFY `DeptOfficerID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `emergency_contacts`
--
ALTER TABLE `emergency_contacts`
  MODIFY `ContactID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `employee`
--
ALTER TABLE `employee`
  MODIFY `EmployeeID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `employee_face_profile`
--
ALTER TABLE `employee_face_profile`
  MODIFY `FaceProfileID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `employee_leave_balances`
--
ALTER TABLE `employee_leave_balances`
  MODIFY `BalanceID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `employee_update_requests`
--
ALTER TABLE `employee_update_requests`
  MODIFY `RequestID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `employmentinformation`
--
ALTER TABLE `employmentinformation`
  MODIFY `EmploymentID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `holidays`
--
ALTER TABLE `holidays`
  MODIFY `HolidayID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `holiday_type`
--
ALTER TABLE `holiday_type`
  MODIFY `HolidayTypeID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `leave_requests`
--
ALTER TABLE `leave_requests`
  MODIFY `LeaveRequestID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `leave_types`
--
ALTER TABLE `leave_types`
  MODIFY `LeaveTypeID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `positions`
--
ALTER TABLE `positions`
  MODIFY `PositionID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `reimbursement_claims`
--
ALTER TABLE `reimbursement_claims`
  MODIFY `ClaimID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `RoleID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `roster_assignment`
--
ALTER TABLE `roster_assignment`
  MODIFY `AssignmentID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=450;

--
-- AUTO_INCREMENT for table `salary_grades`
--
ALTER TABLE `salary_grades`
  MODIFY `SalaryGradeID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `supervisor_employees`
--
ALTER TABLE `supervisor_employees`
  MODIFY `MapID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `taxbenefits`
--
ALTER TABLE `taxbenefits`
  MODIFY `BenefitID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `timesheet_daily`
--
ALTER TABLE `timesheet_daily`
  MODIFY `TimesheetDayID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `timesheet_daily_code`
--
ALTER TABLE `timesheet_daily_code`
  MODIFY `DayCodeID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `timesheet_day_holiday`
--
ALTER TABLE `timesheet_day_holiday`
  MODIFY `DayHolidayID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `timesheet_employee_summary`
--
ALTER TABLE `timesheet_employee_summary`
  MODIFY `SummaryID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `timesheet_period`
--
ALTER TABLE `timesheet_period`
  MODIFY `PeriodID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `useraccountroles`
--
ALTER TABLE `useraccountroles`
  MODIFY `UserRoleID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `useraccounts`
--
ALTER TABLE `useraccounts`
  MODIFY `AccountID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `weekly_roster`
--
ALTER TABLE `weekly_roster`
  MODIFY `RosterID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `work_locations`
--
ALTER TABLE `work_locations`
  MODIFY `LocationID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance_capture`
--
ALTER TABLE `attendance_capture`
  ADD CONSTRAINT `fk_ac_event` FOREIGN KEY (`EventID`) REFERENCES `attendance_event` (`EventID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `attendance_event`
--
ALTER TABLE `attendance_event`
  ADD CONSTRAINT `fk_ae_location` FOREIGN KEY (`LocationID`) REFERENCES `work_locations` (`LocationID`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ae_session` FOREIGN KEY (`SessionID`) REFERENCES `attendance_session` (`SessionID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `attendance_session`
--
ALTER TABLE `attendance_session`
  ADD CONSTRAINT `fk_as_assignment` FOREIGN KEY (`AssignmentID`) REFERENCES `roster_assignment` (`AssignmentID`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_as_employee` FOREIGN KEY (`EmployeeID`) REFERENCES `employee` (`EmployeeID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `bankdetails`
--
ALTER TABLE `bankdetails`
  ADD CONSTRAINT `bankdetails_ibfk_1` FOREIGN KEY (`EmployeeID`) REFERENCES `employee` (`EmployeeID`) ON DELETE CASCADE;

--
-- Constraints for table `bank_applications`
--
ALTER TABLE `bank_applications`
  ADD CONSTRAINT `fk_ba_form` FOREIGN KEY (`FormID`) REFERENCES `bank_forms_master` (`FormID`) ON DELETE SET NULL;

--
-- Constraints for table `department_officers`
--
ALTER TABLE `department_officers`
  ADD CONSTRAINT `fk_do_account` FOREIGN KEY (`AccountID`) REFERENCES `useraccounts` (`AccountID`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_do_department` FOREIGN KEY (`DepartmentID`) REFERENCES `department` (`DepartmentID`) ON DELETE CASCADE;

--
-- Constraints for table `emergency_contacts`
--
ALTER TABLE `emergency_contacts`
  ADD CONSTRAINT `emergency_contacts_ibfk_1` FOREIGN KEY (`EmployeeID`) REFERENCES `employee` (`EmployeeID`) ON DELETE CASCADE;

--
-- Constraints for table `employee_face_profile`
--
ALTER TABLE `employee_face_profile`
  ADD CONSTRAINT `fk_efp_employee` FOREIGN KEY (`EmployeeID`) REFERENCES `employee` (`EmployeeID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_efp_enrolled_by` FOREIGN KEY (`EnrolledByAccountID`) REFERENCES `useraccounts` (`AccountID`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `employee_leave_balances`
--
ALTER TABLE `employee_leave_balances`
  ADD CONSTRAINT `fk_elb_employee` FOREIGN KEY (`EmployeeID`) REFERENCES `employee` (`EmployeeID`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_elb_leavetype` FOREIGN KEY (`LeaveTypeID`) REFERENCES `leave_types` (`LeaveTypeID`) ON DELETE CASCADE;

--
-- Constraints for table `employee_update_requests`
--
ALTER TABLE `employee_update_requests`
  ADD CONSTRAINT `employee_update_requests_ibfk_1` FOREIGN KEY (`EmployeeID`) REFERENCES `employee` (`EmployeeID`) ON DELETE CASCADE;

--
-- Constraints for table `employmentinformation`
--
ALTER TABLE `employmentinformation`
  ADD CONSTRAINT `employmentinformation_ibfk_1` FOREIGN KEY (`EmployeeID`) REFERENCES `employee` (`EmployeeID`) ON DELETE CASCADE,
  ADD CONSTRAINT `employmentinformation_ibfk_2` FOREIGN KEY (`DepartmentID`) REFERENCES `department` (`DepartmentID`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_employment_position` FOREIGN KEY (`PositionID`) REFERENCES `positions` (`PositionID`) ON DELETE SET NULL;

--
-- Constraints for table `holidays`
--
ALTER TABLE `holidays`
  ADD CONSTRAINT `fk_holidays_type` FOREIGN KEY (`HolidayTypeID`) REFERENCES `holiday_type` (`HolidayTypeID`) ON UPDATE CASCADE;

--
-- Constraints for table `leave_requests`
--
ALTER TABLE `leave_requests`
  ADD CONSTRAINT `fk_lr_employee` FOREIGN KEY (`EmployeeID`) REFERENCES `employee` (`EmployeeID`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_lr_hr` FOREIGN KEY (`HRApprovedBy`) REFERENCES `useraccounts` (`AccountID`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_lr_leavetype` FOREIGN KEY (`LeaveTypeID`) REFERENCES `leave_types` (`LeaveTypeID`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_lr_officer` FOREIGN KEY (`OfficerApprovedBy`) REFERENCES `useraccounts` (`AccountID`) ON DELETE SET NULL;

--
-- Constraints for table `positions`
--
ALTER TABLE `positions`
  ADD CONSTRAINT `fk_position_salary_grade` FOREIGN KEY (`SalaryGradeID`) REFERENCES `salary_grades` (`SalaryGradeID`) ON DELETE SET NULL,
  ADD CONSTRAINT `positions_ibfk_1` FOREIGN KEY (`DepartmentID`) REFERENCES `department` (`DepartmentID`);

--
-- Constraints for table `reimbursement_claims`
--
ALTER TABLE `reimbursement_claims`
  ADD CONSTRAINT `fk_rc_employee` FOREIGN KEY (`EmployeeID`) REFERENCES `employee` (`EmployeeID`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_rc_hr` FOREIGN KEY (`HRApprovedBy`) REFERENCES `useraccounts` (`AccountID`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_rc_officer` FOREIGN KEY (`OfficerApprovedBy`) REFERENCES `useraccounts` (`AccountID`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_rc_period` FOREIGN KEY (`PeriodID`) REFERENCES `timesheet_period` (`PeriodID`) ON DELETE CASCADE;

--
-- Constraints for table `roster_assignment`
--
ALTER TABLE `roster_assignment`
  ADD CONSTRAINT `fk_ra_employee` FOREIGN KEY (`EmployeeID`) REFERENCES `employee` (`EmployeeID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ra_roster` FOREIGN KEY (`RosterID`) REFERENCES `weekly_roster` (`RosterID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ra_shift` FOREIGN KEY (`ShiftCode`) REFERENCES `shift_type` (`ShiftCode`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ra_updated_by` FOREIGN KEY (`UpdatedByAccountID`) REFERENCES `useraccounts` (`AccountID`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `supervisor_employees`
--
ALTER TABLE `supervisor_employees`
  ADD CONSTRAINT `fk_se_dept` FOREIGN KEY (`DepartmentID`) REFERENCES `department` (`DepartmentID`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_se_employee` FOREIGN KEY (`EmployeeID`) REFERENCES `employee` (`EmployeeID`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_se_supervisor_acc` FOREIGN KEY (`SupervisorAccountID`) REFERENCES `useraccounts` (`AccountID`) ON DELETE CASCADE;

--
-- Constraints for table `taxbenefits`
--
ALTER TABLE `taxbenefits`
  ADD CONSTRAINT `taxbenefits_ibfk_1` FOREIGN KEY (`EmployeeID`) REFERENCES `employee` (`EmployeeID`) ON DELETE CASCADE;

--
-- Constraints for table `timesheet_daily`
--
ALTER TABLE `timesheet_daily`
  ADD CONSTRAINT `fk_tsd_assignment` FOREIGN KEY (`AssignmentID`) REFERENCES `roster_assignment` (`AssignmentID`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tsd_employee` FOREIGN KEY (`EmployeeID`) REFERENCES `employee` (`EmployeeID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tsd_period` FOREIGN KEY (`PeriodID`) REFERENCES `timesheet_period` (`PeriodID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tsd_session` FOREIGN KEY (`SessionID`) REFERENCES `attendance_session` (`SessionID`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `timesheet_daily_code`
--
ALTER TABLE `timesheet_daily_code`
  ADD CONSTRAINT `fk_tdc_day` FOREIGN KEY (`TimesheetDayID`) REFERENCES `timesheet_daily` (`TimesheetDayID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tdc_paycode` FOREIGN KEY (`PayCode`) REFERENCES `timesheet_pay_code` (`PayCode`) ON UPDATE CASCADE;

--
-- Constraints for table `timesheet_day_holiday`
--
ALTER TABLE `timesheet_day_holiday`
  ADD CONSTRAINT `fk_tdh_day` FOREIGN KEY (`TimesheetDayID`) REFERENCES `timesheet_daily` (`TimesheetDayID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tdh_holiday` FOREIGN KEY (`HolidayID`) REFERENCES `holidays` (`HolidayID`) ON UPDATE CASCADE;

--
-- Constraints for table `timesheet_employee_summary`
--
ALTER TABLE `timesheet_employee_summary`
  ADD CONSTRAINT `fk_tes_department` FOREIGN KEY (`DepartmentID`) REFERENCES `department` (`DepartmentID`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tes_employee` FOREIGN KEY (`EmployeeID`) REFERENCES `employee` (`EmployeeID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tes_period` FOREIGN KEY (`PeriodID`) REFERENCES `timesheet_period` (`PeriodID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tes_position` FOREIGN KEY (`PositionID`) REFERENCES `positions` (`PositionID`) ON UPDATE CASCADE;

--
-- Constraints for table `timesheet_period`
--
ALTER TABLE `timesheet_period`
  ADD CONSTRAINT `fk_tp_department` FOREIGN KEY (`DepartmentID`) REFERENCES `department` (`DepartmentID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tp_finalized_by` FOREIGN KEY (`FinalizedByAccountID`) REFERENCES `useraccounts` (`AccountID`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tp_prepared_by` FOREIGN KEY (`PreparedByAccountID`) REFERENCES `useraccounts` (`AccountID`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tp_reviewed_by` FOREIGN KEY (`ReviewedByAccountID`) REFERENCES `useraccounts` (`AccountID`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `useraccountroles`
--
ALTER TABLE `useraccountroles`
  ADD CONSTRAINT `useraccountroles_ibfk_1` FOREIGN KEY (`AccountID`) REFERENCES `useraccounts` (`AccountID`) ON DELETE CASCADE,
  ADD CONSTRAINT `useraccountroles_ibfk_2` FOREIGN KEY (`RoleID`) REFERENCES `roles` (`RoleID`) ON DELETE CASCADE;

--
-- Constraints for table `useraccounts`
--
ALTER TABLE `useraccounts`
  ADD CONSTRAINT `useraccounts_ibfk_1` FOREIGN KEY (`EmployeeID`) REFERENCES `employee` (`EmployeeID`) ON DELETE CASCADE;

--
-- Constraints for table `weekly_roster`
--
ALTER TABLE `weekly_roster`
  ADD CONSTRAINT `fk_wr_created_by` FOREIGN KEY (`CreatedByAccountID`) REFERENCES `useraccounts` (`AccountID`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_wr_department` FOREIGN KEY (`DepartmentID`) REFERENCES `department` (`DepartmentID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_wr_published_by` FOREIGN KEY (`PublishedByAccountID`) REFERENCES `useraccounts` (`AccountID`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_wr_reviewed_by` FOREIGN KEY (`ReviewedByAccountID`) REFERENCES `useraccounts` (`AccountID`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
