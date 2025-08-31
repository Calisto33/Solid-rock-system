-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 29, 2025 at 04:16 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `wisetech`
--

-- --------------------------------------------------------

--
-- Table structure for table `assignments`
--

CREATE TABLE `assignments` (
  `assignment_id` int(11) NOT NULL,
  `subject_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `due_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `attendance_id` int(11) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `class` varchar(50) NOT NULL,
  `attendance_date` date NOT NULL,
  `status` varchar(20) DEFAULT 'Present',
  `period` varchar(20) DEFAULT 'Full Day',
  `subject` varchar(100) DEFAULT 'General',
  `teacher_id` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `classes`
--

CREATE TABLE `classes` (
  `class_id` int(11) NOT NULL,
  `class_name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `educational_games`
--

CREATE TABLE `educational_games` (
  `game_id` int(11) NOT NULL,
  `game_name` varchar(255) NOT NULL,
  `game_description` text DEFAULT NULL,
  `game_link` varchar(255) NOT NULL,
  `access_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `educational_games`
--

INSERT INTO `educational_games` (`game_id`, `game_name`, `game_description`, `game_link`, `access_count`, `created_at`, `updated_at`) VALUES
(1, 'Computer Hardware.', 'a quizz on computer hardware componets and functions ', 'https://quizizz.com/admin/quiz/5dcb1b7c11f3d3001ba97705?source=quiz_share', 4, '2024-11-04 09:14:56', '2024-11-12 06:54:24');

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `event_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `attachment_type` varchar(50) DEFAULT NULL,
  `attachment_link` varchar(255) DEFAULT NULL,
  `target_audience` enum('students','staff','parents','all') DEFAULT 'all',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `event_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fees`
--

CREATE TABLE `fees` (
  `fee_id` int(11) NOT NULL,
  `student_id` varchar(20) DEFAULT NULL,
  `total_fee` decimal(10,2) NOT NULL,
  `amount_paid` decimal(10,2) NOT NULL DEFAULT 0.00,
  `due_date` date NOT NULL,
  `payment_plan` enum('One-Time','Monthly','Quarterly','Semi-Annual','Annual','Weekly','Bi-Weekly','Term','Custom') DEFAULT 'One-Time',
  `status` enum('Cleared','Pending','Overdue','No Fee Assigned') DEFAULT 'Pending',
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `fees`
--

INSERT INTO `fees` (`fee_id`, `student_id`, `total_fee`, `amount_paid`, `due_date`, `payment_plan`, `status`, `last_updated`) VALUES
(1, '1', 0.00, 0.00, '2025-02-03', 'Monthly', 'Pending', '2025-08-26 05:39:18'),
(6, '6', 0.00, 0.00, '2025-05-03', 'Monthly', 'Pending', '2025-08-26 05:37:32'),
(7, '5', 0.00, 0.00, '2025-07-03', 'Monthly', 'Pending', '2025-08-26 05:38:00'),
(8, '10', 0.00, 0.00, '2025-07-04', 'Monthly', 'Pending', '2025-08-26 05:39:08'),
(9, '100', 0.00, 0.00, '2025-07-04', 'One-Time', 'Pending', '2025-08-26 05:38:58'),
(10, '101', 0.00, 0.00, '2025-06-04', 'Monthly', 'Pending', '2025-08-26 05:38:41'),
(11, '16', 0.00, 0.00, '2025-07-04', 'Monthly', 'Pending', '2025-08-26 05:38:11'),
(12, '15', 0.00, 0.00, '2025-07-04', 'One-Time', 'Pending', '2025-08-26 05:38:31'),
(13, '7', 0.00, 0.00, '2025-07-04', 'One-Time', 'Pending', '2025-08-26 05:40:12'),
(14, '8', 0.00, 0.00, '2025-07-04', 'One-Time', 'Pending', '2025-08-26 05:40:02'),
(15, '9', 0.00, 0.00, '2025-07-04', 'One-Time', 'Pending', '2025-08-26 05:39:35'),
(16, '66', 0.00, 0.00, '2025-07-04', 'One-Time', 'Pending', '2025-08-26 05:43:11'),
(17, '90', 0.00, 0.00, '2025-07-12', 'One-Time', 'Pending', '2025-08-26 05:39:45'),
(18, '0', 500.00, 620.00, '2025-09-15', 'Monthly', 'Cleared', '2025-08-26 09:07:05'),
(34, 'WTC-25198A', 500.00, 100.00, '2025-09-20', 'One-Time', 'Pending', '2025-08-26 07:54:41'),
(35, 'STU013', 600.00, 600.00, '2025-05-20', 'One-Time', 'Cleared', '2025-08-26 09:36:45'),
(36, 'STU014', 100.00, 50.00, '2025-09-26', 'One-Time', 'Pending', '2025-08-26 05:41:06'),
(37, 'STU019', 1000.00, 0.00, '2025-09-26', 'One-Time', 'Pending', '2025-08-26 05:34:38'),
(38, 'STU015', 700.00, 700.00, '2025-04-26', 'Monthly', 'Cleared', '2025-08-26 09:35:59'),
(39, 'STU020', 1000.00, 0.00, '2025-09-26', 'One-Time', 'Pending', '2025-08-26 05:34:50'),
(40, 'STU006', 1000.00, 0.00, '2025-09-26', 'One-Time', 'Pending', '2025-08-26 05:34:54'),
(41, 'STU007', 1000.00, 0.00, '2025-09-26', 'One-Time', 'Pending', '2025-08-26 05:42:18'),
(42, 'STU008', 1000.00, 0.00, '2025-09-26', 'One-Time', 'Pending', '2025-08-26 05:42:23'),
(43, 'STU017', 1000.00, 0.00, '2025-09-26', 'One-Time', 'Pending', '2025-08-26 05:42:32'),
(44, 'STU010', 1000.00, 0.00, '2025-09-26', 'One-Time', 'Pending', '2025-08-26 05:42:44'),
(45, 'STU018', 1000.00, 0.00, '2025-09-26', 'One-Time', 'Pending', '2025-08-26 05:42:51'),
(46, 'STU009', 1000.00, 0.00, '2025-09-26', 'One-Time', 'Pending', '2025-08-26 05:44:02'),
(47, 'STU011', 1000.00, 0.00, '2025-09-26', 'One-Time', 'Pending', '2025-08-26 05:44:07');

-- --------------------------------------------------------

--
-- Table structure for table `marks`
--

CREATE TABLE `marks` (
  `mark_id` int(11) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `term` varchar(20) NOT NULL DEFAULT 'Term 1',
  `year` int(4) NOT NULL,
  `assessment_type` varchar(50) DEFAULT 'Continuous Assessment',
  `marks_obtained` decimal(5,2) NOT NULL,
  `total_marks` decimal(5,2) NOT NULL DEFAULT 100.00,
  `percentage` decimal(5,2) GENERATED ALWAYS AS (`marks_obtained` / `total_marks` * 100) STORED,
  `grade` varchar(5) DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `news`
--

CREATE TABLE `news` (
  `news_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `news_content` text DEFAULT NULL,
  `audience` enum('students','staff','parents','all') DEFAULT 'all',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notices`
--

CREATE TABLE `notices` (
  `notice_id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `class` varchar(10) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `notice_content` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notices`
--

INSERT INTO `notices` (`notice_id`, `staff_id`, `class`, `subject_id`, `notice_content`, `created_at`) VALUES
(1, 2, 'f_1', 1, 'hie james', '2024-11-02 09:02:11'),
(2, 4, 'f_1', 2, 'ouypy', '2025-05-26 13:00:24'),
(3, 4, 'f_1', 2, 'taes', '2025-06-15 16:23:33');

-- --------------------------------------------------------

--
-- Table structure for table `parents`
--

CREATE TABLE `parents` (
  `parent_id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `user_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `phone_number` varchar(15) DEFAULT NULL,
  `relationship` enum('Father','Mother','Guardian') NOT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `parents`
--

INSERT INTO `parents` (`parent_id`, `first_name`, `last_name`, `username`, `email`, `password`, `user_id`, `student_id`, `phone_number`, `relationship`, `address`, `created_at`, `updated_at`) VALUES
(13, '', '', '', '', '', 207, 0, '0718450013', 'Father', '11Awratham rd MT Pleasant', '2025-08-27 08:29:55', '2025-08-27 08:29:55');

-- --------------------------------------------------------

--
-- Table structure for table `parent_feedback`
--

CREATE TABLE `parent_feedback` (
  `feedback_id` int(11) NOT NULL,
  `parent_id` int(11) NOT NULL,
  `feedback` text NOT NULL,
  `status` enum('Pending','Reviewed','Resolved') DEFAULT 'Pending',
  `admin_response` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `parent_feedback`
--

INSERT INTO `parent_feedback` (`feedback_id`, `parent_id`, `feedback`, `status`, `admin_response`, `created_at`, `updated_at`) VALUES
(1, 1, 'when l xuls opening', 'Pending', '14 January 2025', '2024-12-09 13:39:09', '2025-01-16 13:25:07'),
(2, 2, 'test hie', 'Reviewed', 'hie back test', '2025-06-04 17:58:54', '2025-06-04 18:44:42'),
(3, 2, 'TEST', 'Pending', NULL, '2025-06-21 14:00:20', '2025-06-21 14:00:20'),
(4, 2, 'TEST', 'Pending', NULL, '2025-06-21 14:04:31', '2025-06-21 14:04:31');

-- --------------------------------------------------------

--
-- Table structure for table `parent_fee_status`
--

CREATE TABLE `parent_fee_status` (
  `parent_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `fee_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL,
  `student_id` varchar(20) DEFAULT NULL,
  `fee_id` int(11) NOT NULL,
  `payment_date` date NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `received_by` varchar(100) NOT NULL,
  `reference_number` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`payment_id`, `student_id`, `fee_id`, `payment_date`, `amount`, `payment_method`, `received_by`, `reference_number`, `notes`, `created_at`) VALUES
(1, 'STU001', 18, '2025-08-01', 400.00, 'Bank Transfer', 'Admin User', NULL, NULL, '2025-08-13 21:31:38'),
(2, 'STU001', 18, '2025-08-05', 400.00, 'Mobile Money', 'Admin User', NULL, NULL, '2025-08-13 21:31:38'),
(3, 'STU002', 18, '2025-08-01', 1200.00, 'Cash', 'Admin User', NULL, NULL, '2025-08-13 21:31:38'),
(4, 'STU003', 18, '2025-07-15', 300.00, 'Card Payment', 'Admin User', NULL, NULL, '2025-08-13 21:31:38'),
(5, 'STU003', 18, '2025-07-20', 300.00, 'Bank Transfer', 'Admin User', NULL, NULL, '2025-08-13 21:31:38'),
(6, 'STU004', 18, '2025-08-10', 250.00, 'Mobile Money', 'Admin User', NULL, NULL, '2025-08-13 21:31:38'),
(8, 'STU011', 18, '2025-08-01', 400.00, 'Bank Transfer', 'Admin User', NULL, NULL, '2025-08-13 21:35:28'),
(9, 'STU011', 18, '2025-08-15', 400.00, 'Mobile Money', 'Admin User', NULL, NULL, '2025-08-13 21:35:28'),
(10, 'STU012', 18, '2025-08-10', 2200.00, 'Bank Transfer', 'Admin User', NULL, NULL, '2025-08-13 21:35:28'),
(11, 'STU013', 18, '2025-07-20', 200.00, 'Cash', 'Admin User', NULL, NULL, '2025-08-13 21:35:28'),
(12, 'STU014', 18, '2025-08-25', 300.00, 'Mobile Money', 'Admin User', NULL, NULL, '2025-08-13 21:35:28'),
(13, 'STU014', 18, '2025-08-28', 300.00, 'Cash', 'Admin User', NULL, NULL, '2025-08-13 21:35:28'),
(14, 'STU015', 18, '2025-09-01', 350.00, 'Card Payment', 'Admin User', NULL, NULL, '2025-08-13 21:35:28'),
(15, 'STU013', 18, '2025-08-19', 100.00, 'Cash', 'Calisto', NULL, NULL, '2025-08-19 20:43:14'),
(16, 'STU013', 18, '2025-08-19', 120.00, 'Cash', 'Calisto', NULL, NULL, '2025-08-19 20:52:45'),
(17, '0', 18, '2025-08-26', 10.00, 'Cash', 'Calisto', NULL, NULL, '2025-08-26 07:52:10'),
(18, 'STU013', 35, '2025-08-26', 100.00, 'Cash', 'Calisto', NULL, NULL, '2025-08-26 07:53:42'),
(19, 'WTC-25198A', 34, '2025-08-26', 100.00, 'Cash', 'Calisto', NULL, NULL, '2025-08-26 07:54:41'),
(20, 'STU013', 35, '2025-08-26', 100.00, 'Cash', 'Calisto', NULL, NULL, '2025-08-26 08:04:56'),
(21, '0', 18, '2025-08-26', 10.00, 'Cash', 'Calisto', NULL, NULL, '2025-08-26 08:32:37'),
(22, '0', 18, '2025-08-26', 100.00, 'Cash', 'Calisto', NULL, NULL, '2025-08-26 08:32:52'),
(23, 'STU013', 35, '2025-08-26', 10.00, 'Cash', 'Calisto', NULL, NULL, '2025-08-26 08:33:27'),
(24, 'STU013', 35, '2025-08-26', 100.00, 'Cash', 'Calisto', NULL, NULL, '2025-08-26 08:34:35'),
(25, '0', 18, '2025-08-26', 100.00, 'Cash', 'Calisto', NULL, NULL, '2025-08-26 09:06:26'),
(26, '0', 18, '2025-08-26', 100.00, 'Bank Transfer', 'Calisto', NULL, NULL, '2025-08-26 09:07:05'),
(27, 'STU013', 35, '2025-08-26', 100.00, 'Cash', 'Calisto', NULL, NULL, '2025-08-26 09:07:40');

-- --------------------------------------------------------

--
-- Table structure for table `results`
--

CREATE TABLE `results` (
  `result_id` int(11) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `subject` varchar(100) NOT NULL,
  `subject_id` int(11) DEFAULT NULL,
  `class_id` int(11) DEFAULT NULL,
  `exam_type` varchar(50) DEFAULT 'Final Exam',
  `term` varchar(20) DEFAULT 'Term 1',
  `academic_year` varchar(10) DEFAULT '2025',
  `year` varchar(10) DEFAULT NULL,
  `marks_obtained` decimal(5,2) NOT NULL,
  `total_marks` decimal(5,2) NOT NULL DEFAULT 100.00,
  `final_mark` decimal(5,2) NOT NULL,
  `grade` varchar(5) NOT NULL,
  `final_grade` varchar(10) DEFAULT NULL,
  `target_grade` varchar(10) DEFAULT NULL,
  `attitude_to_learning` int(1) DEFAULT NULL,
  `exam_date` date NOT NULL,
  `teacher_id` varchar(50) DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `results`
--

INSERT INTO `results` (`result_id`, `student_id`, `subject`, `subject_id`, `class_id`, `exam_type`, `term`, `academic_year`, `year`, `marks_obtained`, `total_marks`, `final_mark`, `grade`, `final_grade`, `target_grade`, `attitude_to_learning`, `exam_date`, `teacher_id`, `comments`, `created_at`, `updated_at`) VALUES
(61, 'STU001', 'Mathematics', NULL, NULL, 'Final Exam', 'Term 1', '2025', NULL, 78.50, 100.00, 78.50, 'B', NULL, NULL, NULL, '2025-04-15', 'TCH001', 'Good performance in algebra', '2025-08-13 21:56:45', '2025-08-13 21:56:45'),
(62, 'STU001', 'English', NULL, NULL, 'Final Exam', 'Term 1', '2025', NULL, 82.00, 100.00, 82.00, 'A', NULL, NULL, NULL, '2025-04-16', 'TCH002', 'Excellent essay writing', '2025-08-13 21:56:45', '2025-08-13 21:56:45'),
(63, 'STU001', 'Science', NULL, NULL, 'Final Exam', 'Term 1', '2025', NULL, 75.50, 100.00, 75.50, 'B', NULL, NULL, NULL, '2025-04-17', 'TCH003', 'Strong understanding of concepts', '2025-08-13 21:56:45', '2025-08-13 21:56:45'),
(64, 'STU002', 'Mathematics', NULL, NULL, 'Final Exam', 'Term 1', '2025', NULL, 85.00, 100.00, 85.00, 'A', NULL, NULL, NULL, '2025-04-15', 'TCH001', 'Outstanding problem solving', '2025-08-13 21:56:45', '2025-08-13 21:56:45'),
(65, 'STU002', 'English', NULL, NULL, 'Final Exam', 'Term 1', '2025', NULL, 88.50, 100.00, 88.50, 'A', NULL, NULL, NULL, '2025-04-16', 'TCH002', 'Creative writing skills', '2025-08-13 21:56:45', '2025-08-13 21:56:45'),
(66, 'STU002', 'Science', NULL, NULL, 'Final Exam', 'Term 1', '2025', NULL, 91.00, 100.00, 91.00, 'A', NULL, NULL, NULL, '2025-04-17', 'TCH003', 'Excellent lab work', '2025-08-13 21:56:45', '2025-08-13 21:56:45'),
(67, 'STU003', 'Mathematics', NULL, NULL, 'Final Exam', 'Term 1', '2025', NULL, 76.00, 100.00, 76.00, 'B', NULL, NULL, NULL, '2025-04-15', 'TCH001', 'Consistent performer', '2025-08-13 21:56:45', '2025-08-13 21:56:45'),
(68, 'STU003', 'English', NULL, NULL, 'Final Exam', 'Term 1', '2025', NULL, 79.50, 100.00, 79.50, 'B', NULL, NULL, NULL, '2025-04-16', 'TCH002', 'Good analytical skills', '2025-08-13 21:56:45', '2025-08-13 21:56:45'),
(69, 'STU003', 'Geography', NULL, NULL, 'Final Exam', 'Term 1', '2025', NULL, 84.00, 100.00, 84.00, 'A', NULL, NULL, NULL, '2025-04-18', 'TCH004', 'Excellent map work', '2025-08-13 21:56:45', '2025-08-13 21:56:45'),
(70, 'STU004', 'Mathematics', NULL, NULL, 'Final Exam', 'Term 1', '2025', NULL, 71.50, 100.00, 71.50, 'B', NULL, NULL, NULL, '2025-04-15', 'TCH001', 'Improving steadily', '2025-08-13 21:56:45', '2025-08-13 21:56:45'),
(71, 'STU004', 'English', NULL, NULL, 'Final Exam', 'Term 1', '2025', NULL, 86.00, 100.00, 86.00, 'A', NULL, NULL, NULL, '2025-04-16', 'TCH002', 'Strong language skills', '2025-08-13 21:56:45', '2025-08-13 21:56:45'),
(72, 'STU004', 'Geography', NULL, NULL, 'Final Exam', 'Term 1', '2025', NULL, 77.50, 100.00, 77.50, 'B', NULL, NULL, NULL, '2025-04-18', 'TCH004', 'Good field work', '2025-08-13 21:56:45', '2025-08-13 21:56:45'),
(73, 'STU005', 'Mathematics', NULL, NULL, 'Final Exam', 'Term 1', '2025', NULL, 82.50, 100.00, 82.50, 'A', NULL, NULL, NULL, '2025-04-15', 'TCH001', 'Excellent trigonometry', '2025-08-13 21:56:45', '2025-08-13 21:56:45'),
(74, 'STU005', 'English', NULL, NULL, 'Final Exam', 'Term 1', '2025', NULL, 85.50, 100.00, 85.50, 'A', NULL, NULL, NULL, '2025-04-16', 'TCH002', 'Outstanding literature analysis', '2025-08-13 21:56:45', '2025-08-13 21:56:45'),
(75, 'STU005', 'History', NULL, NULL, 'Final Exam', 'Term 1', '2025', NULL, 88.00, 100.00, 88.00, 'A', NULL, NULL, NULL, '2025-04-19', 'TCH005', 'Excellent essay writing', '2025-08-13 21:56:45', '2025-08-13 21:56:45'),
(76, 'STU006', 'Mathematics', NULL, NULL, 'Final Exam', 'Term 1', '2025', NULL, 74.50, 100.00, 74.50, 'B', NULL, NULL, NULL, '2025-04-15', 'TCH001', 'Good progress made', '2025-08-13 21:56:45', '2025-08-13 21:56:45'),
(77, 'STU006', 'English', NULL, NULL, 'Final Exam', 'Term 1', '2025', NULL, 81.00, 100.00, 81.00, 'A', NULL, NULL, NULL, '2025-04-16', 'TCH002', 'Creative writing talent', '2025-08-13 21:56:45', '2025-08-13 21:56:45'),
(78, 'STU006', 'History', NULL, NULL, 'Final Exam', 'Term 1', '2025', NULL, 76.50, 100.00, 76.50, 'B', NULL, NULL, NULL, '2025-04-19', 'TCH005', 'Good historical analysis', '2025-08-13 21:56:45', '2025-08-13 21:56:45'),
(79, 'STU007', 'Mathematics', NULL, NULL, 'Final Exam', 'Term 1', '2025', NULL, 88.50, 100.00, 88.50, 'A', NULL, NULL, NULL, '2025-04-15', 'TCH001', 'Excellent O-Level preparation', '2025-08-13 21:56:45', '2025-08-13 21:56:45'),
(80, 'STU007', 'English', NULL, NULL, 'Final Exam', 'Term 1', '2025', NULL, 86.00, 100.00, 86.00, 'A', NULL, NULL, NULL, '2025-04-16', 'TCH002', 'Ready for O-Level', '2025-08-13 21:56:45', '2025-08-13 21:56:45'),
(81, 'STU007', 'Physics', NULL, NULL, 'Final Exam', 'Term 1', '2025', NULL, 91.50, 100.00, 91.50, 'A', NULL, NULL, NULL, '2025-04-20', 'TCH006', 'Outstanding practical work', '2025-08-13 21:56:45', '2025-08-13 21:56:45'),
(82, 'STU008', 'Mathematics', NULL, NULL, 'Final Exam', 'Term 1', '2025', NULL, 93.50, 100.00, 93.50, 'A', NULL, NULL, NULL, '2025-04-15', 'TCH001', 'Exceptional mathematical ability', '2025-08-13 21:56:45', '2025-08-13 21:56:45'),
(83, 'STU008', 'English', NULL, NULL, 'Final Exam', 'Term 1', '2025', NULL, 89.00, 100.00, 89.00, 'A', NULL, NULL, NULL, '2025-04-16', 'TCH002', 'Excellent communication', '2025-08-13 21:56:45', '2025-08-13 21:56:45'),
(84, 'STU008', 'Physics', NULL, NULL, 'Final Exam', 'Term 1', '2025', NULL, 87.50, 100.00, 87.50, 'A', NULL, NULL, NULL, '2025-04-20', 'TCH006', 'Strong theoretical knowledge', '2025-08-13 21:56:45', '2025-08-13 21:56:45'),
(85, 'STU009', 'Pure Mathematics', NULL, NULL, 'Final Exam', 'Term 1', '2025', NULL, 85.00, 100.00, 85.00, 'A', NULL, NULL, NULL, '2025-04-15', 'TCH007', 'Excellent calculus work', '2025-08-13 21:56:45', '2025-08-13 21:56:45'),
(86, 'STU009', 'Physics', NULL, NULL, 'Final Exam', 'Term 1', '2025', NULL, 88.50, 100.00, 88.50, 'A', NULL, NULL, NULL, '2025-04-20', 'TCH006', 'Outstanding A-Level standard', '2025-08-13 21:56:45', '2025-08-13 21:56:45'),
(87, 'STU009', 'Chemistry', NULL, NULL, 'Final Exam', 'Term 1', '2025', NULL, 82.00, 100.00, 82.00, 'A', NULL, NULL, NULL, '2025-04-21', 'TCH008', 'Good organic chemistry', '2025-08-13 21:56:45', '2025-08-13 21:56:45'),
(88, 'STU010', 'Economics', NULL, NULL, 'Final Exam', 'Term 1', '2025', NULL, 91.50, 100.00, 91.50, 'A', NULL, NULL, NULL, '2025-04-23', 'TCH010', 'Excellent economic analysis', '2025-08-13 21:56:45', '2025-08-13 21:56:45'),
(89, 'STU010', 'Business Studies', NULL, NULL, 'Final Exam', 'Term 1', '2025', NULL, 89.00, 100.00, 89.00, 'A', NULL, NULL, NULL, '2025-04-24', 'TCH011', 'Outstanding business acumen', '2025-08-13 21:56:45', '2025-08-13 21:56:45'),
(90, 'STU010', 'Accounting', NULL, NULL, 'Final Exam', 'Term 1', '2025', NULL, 94.50, 100.00, 94.50, 'A', NULL, NULL, NULL, '2025-04-25', 'TCH012', 'Exceptional accounting skills', '2025-08-13 21:56:45', '2025-08-13 21:56:45'),
(91, 'STU011', 'Pure Mathematics', NULL, NULL, 'Final Exam', 'Term 1', '2025', NULL, 79.50, 100.00, 79.50, 'B', NULL, NULL, NULL, '2025-04-15', 'TCH007', 'Good analytical skills', '2025-08-13 21:56:45', '2025-08-13 21:56:45'),
(92, 'STU011', 'Physics', NULL, NULL, 'Final Exam', 'Term 1', '2025', NULL, 84.00, 100.00, 84.00, 'A', NULL, NULL, NULL, '2025-04-20', 'TCH006', 'Strong mechanics understanding', '2025-08-13 21:56:45', '2025-08-13 21:56:45'),
(93, 'STU011', 'Chemistry', NULL, NULL, 'Final Exam', 'Term 1', '2025', NULL, 86.50, 100.00, 86.50, 'A', NULL, NULL, NULL, '2025-04-21', 'TCH008', 'Excellent practical work', '2025-08-13 21:56:45', '2025-08-13 21:56:45'),
(94, 'STU012', 'Economics', NULL, NULL, 'Final Exam', 'Term 1', '2025', NULL, 87.00, 100.00, 87.00, 'A', NULL, NULL, NULL, '2025-04-23', 'TCH010', 'Strong theoretical knowledge', '2025-08-13 21:56:45', '2025-08-13 21:56:45'),
(95, 'STU012', 'Business Studies', NULL, NULL, 'Final Exam', 'Term 1', '2025', NULL, 85.50, 100.00, 85.50, 'A', NULL, NULL, NULL, '2025-04-24', 'TCH011', 'Good case study analysis', '2025-08-13 21:56:45', '2025-08-13 21:56:45'),
(96, 'STU012', 'Accounting', NULL, NULL, 'Final Exam', 'Term 1', '2025', NULL, 88.50, 100.00, 88.50, 'A', NULL, NULL, NULL, '2025-04-25', 'TCH012', 'Accurate financial reporting', '2025-08-13 21:56:45', '2025-08-13 21:56:45'),
(97, 'STU013', 'Mathematics', NULL, NULL, 'Final Exam', 'Term 1', '2025', NULL, 65.00, 100.00, 65.00, 'C', NULL, NULL, NULL, '2025-04-15', 'TCH001', 'Needs improvement in geometry', '2025-08-13 21:56:45', '2025-08-13 21:56:45'),
(98, 'STU013', 'English', NULL, NULL, 'Final Exam', 'Term 1', '2025', NULL, 72.50, 100.00, 72.50, 'B', NULL, NULL, NULL, '2025-04-16', 'TCH002', 'Good comprehension skills', '2025-08-13 21:56:45', '2025-08-13 21:56:45'),
(99, 'STU013', 'Science', NULL, NULL, 'Final Exam', 'Term 1', '2025', NULL, 68.00, 100.00, 68.00, 'C', NULL, NULL, NULL, '2025-04-17', 'TCH003', 'Average performance', '2025-08-13 21:56:45', '2025-08-13 21:56:45'),
(100, 'STU014', 'Mathematics', NULL, NULL, 'Final Exam', 'Term 1', '2025', NULL, 58.50, 100.00, 58.50, 'D', NULL, NULL, NULL, '2025-04-15', 'TCH001', 'Needs extra support', '2025-08-13 21:56:45', '2025-08-13 21:56:45'),
(101, 'STU014', 'English', NULL, NULL, 'Final Exam', 'Term 1', '2025', NULL, 74.00, 100.00, 74.00, 'B', NULL, NULL, NULL, '2025-04-16', 'TCH002', 'Good effort shown', '2025-08-13 21:56:45', '2025-08-13 21:56:45'),
(102, 'STU014', 'Geography', NULL, NULL, 'Final Exam', 'Term 1', '2025', NULL, 69.50, 100.00, 69.50, 'C', NULL, NULL, NULL, '2025-04-18', 'TCH004', 'Satisfactory work', '2025-08-13 21:56:45', '2025-08-13 21:56:45'),
(103, 'STU015', 'Mathematics', NULL, NULL, 'Final Exam', 'Term 1', '2025', NULL, 67.00, 100.00, 67.00, 'C', NULL, NULL, NULL, '2025-04-15', 'TCH001', 'Steady improvement', '2025-08-13 21:56:45', '2025-08-13 21:56:45'),
(104, 'STU015', 'English', NULL, NULL, 'Final Exam', 'Term 1', '2025', NULL, 73.50, 100.00, 73.50, 'B', NULL, NULL, NULL, '2025-04-16', 'TCH002', 'Good vocabulary', '2025-08-13 21:56:45', '2025-08-13 21:56:45'),
(105, 'STU015', 'History', NULL, NULL, 'Final Exam', 'Term 1', '2025', NULL, 70.50, 100.00, 70.50, 'B', NULL, NULL, NULL, '2025-04-19', 'TCH005', 'Satisfactory work', '2025-08-13 21:56:45', '2025-08-13 21:56:45'),
(106, 'STU016', 'Mathematics', NULL, NULL, 'Final Exam', 'Term 1', '2025', NULL, 75.50, 100.00, 75.50, 'B', NULL, NULL, NULL, '2025-04-15', 'TCH001', 'Good O-Level progress', '2025-08-13 21:56:45', '2025-08-13 21:56:45'),
(107, 'STU016', 'English', NULL, NULL, 'Final Exam', 'Term 1', '2025', NULL, 78.00, 100.00, 78.00, 'B', NULL, NULL, NULL, '2025-04-16', 'TCH002', 'Steady improvement', '2025-08-13 21:56:45', '2025-08-13 21:56:45'),
(108, 'STU016', 'Physics', NULL, NULL, 'Final Exam', 'Term 1', '2025', NULL, 72.50, 100.00, 72.50, 'B', NULL, NULL, NULL, '2025-04-20', 'TCH006', 'Needs more practice', '2025-08-13 21:56:45', '2025-08-13 21:56:45'),
(109, 'STU017', 'Biology', NULL, NULL, 'Final Exam', 'Term 1', '2025', NULL, 88.00, 100.00, 88.00, 'A', NULL, NULL, NULL, '2025-04-22', 'TCH009', 'Outstanding biology knowledge', '2025-08-13 21:56:45', '2025-08-13 21:56:45'),
(110, 'STU017', 'Chemistry', NULL, NULL, 'Final Exam', 'Term 1', '2025', NULL, 85.50, 100.00, 85.50, 'A', NULL, NULL, NULL, '2025-04-21', 'TCH008', 'Strong biochemistry', '2025-08-13 21:56:45', '2025-08-13 21:56:45'),
(111, 'STU017', 'Physics', NULL, NULL, 'Final Exam', 'Term 1', '2025', NULL, 81.00, 100.00, 81.00, 'A', NULL, NULL, NULL, '2025-04-20', 'TCH006', 'Good biophysics understanding', '2025-08-13 21:56:45', '2025-08-13 21:56:45'),
(112, 'STU018', 'History', NULL, NULL, 'Final Exam', 'Term 1', '2025', NULL, 92.00, 100.00, 92.00, 'A', NULL, NULL, NULL, '2025-04-19', 'TCH005', 'Exceptional historical analysis', '2025-08-13 21:56:45', '2025-08-13 21:56:45'),
(113, 'STU018', 'Geography', NULL, NULL, 'Final Exam', 'Term 1', '2025', NULL, 89.50, 100.00, 89.50, 'A', NULL, NULL, NULL, '2025-04-18', 'TCH004', 'Outstanding fieldwork', '2025-08-13 21:56:45', '2025-08-13 21:56:45'),
(114, 'STU018', 'Literature', NULL, NULL, 'Final Exam', 'Term 1', '2025', NULL, 93.50, 100.00, 93.50, 'A', NULL, NULL, NULL, '2025-04-26', 'TCH013', 'Excellent literary criticism', '2025-08-13 21:56:45', '2025-08-13 21:56:45'),
(115, 'STU019', 'Mathematics', NULL, NULL, 'Final Exam', 'Term 1', '2025', NULL, 63.00, 100.00, 63.00, 'C', NULL, NULL, NULL, '2025-04-15', 'TCH001', 'Shows potential', '2025-08-13 21:56:45', '2025-08-13 21:56:45'),
(116, 'STU019', 'English', NULL, NULL, 'Final Exam', 'Term 1', '2025', NULL, 78.50, 100.00, 78.50, 'B', NULL, NULL, NULL, '2025-04-16', 'TCH002', 'Excellent presentation', '2025-08-13 21:56:45', '2025-08-13 21:56:45'),
(117, 'STU019', 'Geography', NULL, NULL, 'Final Exam', 'Term 1', '2025', NULL, 72.00, 100.00, 72.00, 'B', NULL, NULL, NULL, '2025-04-18', 'TCH004', 'Good understanding', '2025-08-13 21:56:45', '2025-08-13 21:56:45'),
(118, 'STU020', 'Mathematics', NULL, NULL, 'Final Exam', 'Term 1', '2025', NULL, 79.00, 100.00, 79.00, 'B', NULL, NULL, NULL, '2025-04-15', 'TCH001', 'Strong problem solving', '2025-08-13 21:56:45', '2025-08-13 21:56:45'),
(119, 'STU020', 'English', NULL, NULL, 'Final Exam', 'Term 1', '2025', NULL, 82.50, 100.00, 82.50, 'A', NULL, NULL, NULL, '2025-04-16', 'TCH002', 'Excellent grammar', '2025-08-13 21:56:45', '2025-08-13 21:56:45'),
(120, 'STU020', 'History', NULL, NULL, 'Final Exam', 'Term 1', '2025', NULL, 84.50, 100.00, 84.50, 'A', NULL, NULL, NULL, '2025-04-19', 'TCH005', 'Outstanding research', '2025-08-13 21:56:45', '2025-08-13 21:56:45'),
(123, '0', '', 1, 1, 'Final Exam', '0', '2025', '2025', 0.00, 100.00, 77.00, '', 'Bb', 'Ab', 3, '0000-00-00', NULL, '', '2025-08-20 05:57:41', '2025-08-20 05:57:41'),
(124, '0', '', 2, 1, 'Final Exam', '0', '2025', '2025', 0.00, 100.00, 56.00, '', 'Db', '', 2, '0000-00-00', NULL, 'Grace Evans has demonstrated a strong commitment to her English Language studies. You consistently put forth considerable effort, and your understanding of literary concepts is evident. While your performance currently sits slightly below your target of B, your dedication to learning is truly commendable. Keep up the excellent work – you’re already exceeding expectations!', '2025-08-20 05:59:06', '2025-08-20 05:59:25'),
(125, '0', '', 13, 1, 'Final Exam', '0', '2025', '2025', 0.00, 100.00, 62.00, '', 'Cc', 'Cb', 4, '0000-00-00', NULL, '', '2025-08-26 07:05:20', '2025-08-26 07:06:31');

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `staff_id` int(11) NOT NULL,
  `id` int(11) DEFAULT NULL,
  `id_number` varchar(50) NOT NULL,
  `username` varchar(100) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `phone_number` varchar(15) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`staff_id`, `id`, `id_number`, `username`, `department`, `position`, `phone_number`, `email`) VALUES
(2, 8, '63-2520789z90', 'David', 'sci', 'staff member', '77678', NULL),
(3, 14, '63-2567t56', 'munya', 'General worker', 'Staff Member', '87654', NULL),
(4, 20, '', 'calisto', 'General', 'Staff Member', NULL, NULL),
(5, 23, '', 'Mr Matarirano', 'General', 'Staff Member', NULL, NULL),
(6, 24, '', 'Mr Manuwha', 'General', 'Staff Member', NULL, NULL),
(7, 25, '', 'Mr Jangara', 'General', 'Staff Member', NULL, NULL),
(8, 26, '', 'Mr Nhepera', 'General', 'Staff Member', NULL, NULL),
(9, 27, '', 'Mr Zhuwaki', 'General', 'Staff Member', NULL, NULL),
(10, 28, '', 'Mr Kufakwedeke', 'General', 'Staff Member', NULL, NULL),
(11, 29, '', 'Mrs Sshumba', 'General', 'Staff Member', NULL, NULL),
(12, 30, '', 'P Mushoni', 'General', 'Staff Member', NULL, NULL),
(13, 32, '', 'Chapeyema', 'General', 'Staff Member', NULL, NULL),
(14, 33, '', 'Madondo', 'General', 'Staff Member', NULL, NULL),
(15, 34, '', 'Chizemo', 'General', 'Staff Member', NULL, NULL),
(16, 35, '', 'Mr TTsvarisayi', 'General', 'Staff Member', NULL, NULL),
(17, 36, '', 'Mrs Muchetu', 'General', 'Staff Member', NULL, NULL),
(18, 37, '', 'Mr Takawira', 'General', 'Staff Member', NULL, NULL),
(19, 201, 'WTC-0201', 'Jane Smith', 'Not Assigned', 'Staff Member', NULL, 'jane.smith@example.com');

-- --------------------------------------------------------

--
-- Table structure for table `staff_files`
--

CREATE TABLE `staff_files` (
  `file_id` int(11) NOT NULL,
  `staff_id` int(11) DEFAULT NULL,
  `file_name` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `upload_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff_profile`
--

CREATE TABLE `staff_profile` (
  `staff_id` int(11) NOT NULL,
  `department` varchar(50) DEFAULT NULL,
  `position` varchar(50) DEFAULT NULL,
  `social_description` text DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff_profile`
--

INSERT INTO `staff_profile` (`staff_id`, `department`, `position`, `social_description`, `profile_picture`) VALUES
(2, 'sci', 'staff member', '                    like Coca Cola, Apple,Amazon,Tesla and so on ...Frank_lucus is a brand name                ', '1728276337996.jpg'),
(3, 'General', 'Staff Member', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `staff_subject`
--

CREATE TABLE `staff_subject` (
  `id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `class` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `student_id` varchar(50) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `class` varchar(50) DEFAULT NULL,
  `course` varchar(100) DEFAULT 'Unassigned',
  `year` varchar(10) DEFAULT '2025',
  `date_of_birth` date DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `enrollment_date` date DEFAULT curdate(),
  `status` enum('Active','Inactive','Graduated','Transferred') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `username` varchar(100) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`student_id`, `user_id`, `class`, `course`, `year`, `date_of_birth`, `phone`, `address`, `enrollment_date`, `status`, `created_at`, `updated_at`, `first_name`, `last_name`, `username`, `email`) VALUES
('WTC-25208A', 208, 'Grade 1 Silver', 'Unassigned', '2025', NULL, NULL, NULL, '2025-08-27', 'Active', '2025-08-27 08:40:23', '2025-08-29 09:41:30', NULL, NULL, 'Calisto Panganayi', NULL),
('WTC-25214A', 214, 'Grade 7 Platinum', 'Unassigned', '2025', NULL, NULL, NULL, '2025-08-28', 'Active', '2025-08-28 05:45:28', '2025-08-29 10:27:57', NULL, NULL, 'test', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `student_marks`
--

CREATE TABLE `student_marks` (
  `mark_id` int(11) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `subject_name` varchar(100) NOT NULL,
  `class_name` varchar(50) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `term` varchar(20) NOT NULL DEFAULT 'Term 1',
  `year` int(4) NOT NULL,
  `assessment_type` varchar(50) DEFAULT 'Continuous Assessment',
  `marks_obtained` decimal(5,2) NOT NULL,
  `total_marks` decimal(5,2) NOT NULL DEFAULT 100.00,
  `percentage` decimal(5,2) GENERATED ALWAYS AS (`marks_obtained` / `total_marks` * 100) STORED,
  `grade` varchar(5) DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_notices`
--

CREATE TABLE `student_notices` (
  `id` int(11) NOT NULL,
  `notice_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_notices`
--

INSERT INTO `student_notices` (`id`, `notice_id`, `student_id`) VALUES
(12, 2, 22),
(13, 1, 22),
(14, 2, 22),
(15, 1, 22);

-- --------------------------------------------------------

--
-- Table structure for table `student_parent_relationships`
--

CREATE TABLE `student_parent_relationships` (
  `id` int(11) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `parent_id` int(11) NOT NULL,
  `relationship_type` varchar(50) NOT NULL DEFAULT 'other',
  `is_primary_contact` tinyint(1) DEFAULT 0,
  `is_emergency_contact` tinyint(1) DEFAULT 0,
  `can_pick_up` tinyint(1) DEFAULT 1,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_parent_relationships`
--

INSERT INTO `student_parent_relationships` (`id`, `student_id`, `parent_id`, `relationship_type`, `is_primary_contact`, `is_emergency_contact`, `can_pick_up`, `notes`, `created_at`, `updated_at`, `created_by`) VALUES
(11, 'WTC-25208A', 13, 'father', 1, 1, 1, '', '2025-08-27 08:41:42', '2025-08-27 08:41:42', 21);

-- --------------------------------------------------------

--
-- Table structure for table `student_profile`
--

CREATE TABLE `student_profile` (
  `student_id` int(11) NOT NULL,
  `social_description` text DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_profile`
--

INSERT INTO `student_profile` (`student_id`, `social_description`, `profile_picture`) VALUES
(1, 'good studnet hjkkl james', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `student_read_assignments`
--

CREATE TABLE `student_read_assignments` (
  `user_id` int(11) NOT NULL,
  `assignment_id` int(11) NOT NULL,
  `read_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_read_results`
--

CREATE TABLE `student_read_results` (
  `user_id` int(11) NOT NULL,
  `result_id` int(11) NOT NULL,
  `read_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_read_results`
--

INSERT INTO `student_read_results` (`user_id`, `result_id`, `read_at`) VALUES
(22, 24, '2025-06-02 10:49:04'),
(22, 25, '2025-06-02 13:39:52'),
(22, 26, '2025-06-02 14:13:34'),
(22, 27, '2025-06-02 14:22:25'),
(22, 42, '2025-06-04 09:13:09'),
(22, 43, '2025-06-04 09:14:50'),
(22, 44, '2025-06-04 09:14:50'),
(22, 45, '2025-06-04 09:14:50'),
(22, 46, '2025-06-04 09:19:30'),
(22, 47, '2025-06-04 09:21:33');

-- --------------------------------------------------------

--
-- Table structure for table `student_resources`
--

CREATE TABLE `student_resources` (
  `resource_id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `department` enum('Arts','Sciences','Commercial') NOT NULL,
  `description` text NOT NULL,
  `resource_link` varchar(255) NOT NULL,
  `upload_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `download_count` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_resources`
--

INSERT INTO `student_resources` (`resource_id`, `staff_id`, `department`, `description`, `resource_link`, `upload_date`, `download_count`) VALUES
(2, 2, 'Arts', 'Shakespeare’s The Merchant of Venice plot summary..', 'https://www.bbc.co.uk/bitesize/articles/z77g4xs', '2024-11-11 16:32:02', 3);

-- --------------------------------------------------------

--
-- Table structure for table `student_subject`
--

CREATE TABLE `student_subject` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_subject`
--

INSERT INTO `student_subject` (`id`, `student_id`, `subject_id`) VALUES
(107, 0, 32),
(108, 0, 30);

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `subject_id` int(11) NOT NULL,
  `subject_name` varchar(100) NOT NULL,
  `subject_code` varchar(20) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `credits` int(11) DEFAULT 3
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`subject_id`, `subject_name`, `subject_code`, `description`, `created_at`, `updated_at`, `credits`) VALUES
(30, 'Mathematics', 'MTS0922', '', '2025-08-28 14:52:39', '2025-08-28 14:52:39', 3),
(31, 'Geography', 'Geo1222', '', '2025-08-29 07:17:16', '2025-08-29 07:17:16', 3),
(32, 'Chemisty', NULL, NULL, '2025-08-29 10:16:30', '2025-08-29 10:16:30', 3);

-- --------------------------------------------------------

--
-- Table structure for table `submissions`
--

CREATE TABLE `submissions` (
  `submission_id` int(11) NOT NULL,
  `assignment_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `submission_file` varchar(255) DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `status` enum('Submitted','Marked') DEFAULT 'Submitted',
  `score` decimal(5,2) DEFAULT NULL,
  `feedback` text DEFAULT NULL,
  `submitted_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `super_admins`
--

CREATE TABLE `super_admins` (
  `super_admin_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` enum('active','inactive') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `super_admins`
--

INSERT INTO `super_admins` (`super_admin_id`, `username`, `password`, `email`, `created_at`, `updated_at`, `status`) VALUES
(2, 'default_admin', '$2y$10$eR6HLTP3gbA2tTjjCsc5leHLr9O.tzTwbvPRgCbBcmTrXlmLF5fE2', 'superadmin@school.com', '2024-12-13 07:02:58', '2025-06-09 14:57:59', 'active'),
(3, 'default_admin2', '$2y$10$MTrQyGiCr/ct3nSE74pIX.jn.OLjatkR.0HPAxKkvZjvZr5AQ4uI.', 'superadmin2@school.com', '2024-12-13 07:11:28', '2024-12-13 07:11:28', 'active'),
(4, 'Calisto', '$2y$10$ckBUFlY9lIzImuiRFRMArORMkrRv21jUzyL6HMpcu31d2K.O40bOq', 'calisto.admin@wisetech', '2025-05-08 12:33:54', '2025-05-08 12:33:54', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `setting_id` int(11) NOT NULL,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `table_subject`
--

CREATE TABLE `table_subject` (
  `subject_id` int(11) NOT NULL,
  `subject_name` varchar(100) NOT NULL,
  `subject_code` varchar(20) DEFAULT NULL,
  `class` enum('Form 1','Form 2','Form 3','Form 4','Form 5','Form 6') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `table_subject`
--

INSERT INTO `table_subject` (`subject_id`, `subject_name`, `subject_code`, `class`) VALUES
(1, 'computer science', 'SCI', ''),
(2, 'English', 'ENG', ''),
(3, 'Shona', NULL, ''),
(4, 'Biology', NULL, ''),
(5, 'physics', NULL, ''),
(6, 'Chemisty', NULL, ''),
(7, 'History', 'HIST', ''),
(8, 'Geography', 'GEO', ''),
(9, 'French', NULL, ''),
(10, 'Sociology', NULL, ''),
(11, 'Mathematics', 'MATH', '');

-- --------------------------------------------------------

--
-- Table structure for table `teacher_subjects`
--

CREATE TABLE `teacher_subjects` (
  `assignment_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `term_assessments`
--

CREATE TABLE `term_assessments` (
  `id` int(11) NOT NULL,
  `result_id` int(11) NOT NULL,
  `assessment_name` varchar(100) NOT NULL,
  `mark` decimal(5,2) NOT NULL,
  `max_mark` decimal(5,2) DEFAULT 100.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `term_assessments`
--

INSERT INTO `term_assessments` (`id`, `result_id`, `assessment_name`, `mark`, `max_mark`, `created_at`, `updated_at`) VALUES
(1, 123, 'Assessment 1', 90.00, 100.00, '2025-08-20 05:57:41', '2025-08-20 05:57:41'),
(2, 123, 'Assessment 2', 58.00, 100.00, '2025-08-20 05:57:41', '2025-08-20 05:57:41'),
(3, 123, 'End of Term Exam', 79.00, 100.00, '2025-08-20 05:57:41', '2025-08-20 05:57:41'),
(4, 124, 'Assessment 1', 60.00, 100.00, '2025-08-20 05:59:06', '2025-08-20 05:59:06'),
(5, 124, 'Assessment 2', 70.00, 100.00, '2025-08-20 05:59:06', '2025-08-20 05:59:06'),
(6, 124, 'End of Term Exam', 50.00, 100.00, '2025-08-20 05:59:06', '2025-08-20 05:59:06'),
(7, 125, 'Assessment 1', 50.00, 100.00, '2025-08-26 07:05:20', '2025-08-26 07:05:20'),
(8, 125, 'Assessment 2', 50.00, 100.00, '2025-08-26 07:05:20', '2025-08-26 07:05:20'),
(9, 125, 'End of Term Exam', 70.00, 100.00, '2025-08-26 07:05:20', '2025-08-26 07:05:20');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(250) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(100) NOT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `role` enum('student','staff','admin','parent') NOT NULL,
  `status` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `first_name`, `last_name`, `phone`, `created_at`, `updated_at`, `role`, `status`) VALUES
(2, 'James Gunn', 'jamesgun.student@wise.tech', '$2y$10$Zi8cDDca/g8qtmL67nmGWOJlcVemZewu/JiI2lifChh2uu.y0jjW.', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'student', 'yes'),
(3, 'tino sadza', 'tinosadza.student@wise.tech', '$2y$10$f./7U.xUfllALCfjP5lb9e3lv5yLg2/RyW9CKKAIPE6OCAmQCXoJW', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'student', 'yes'),
(8, 'David', 'david.staff@wise.tech', '$2y$10$JYeWX6J1UzUJFoq67PHSsuR8498ZeegEWAaRRmjyYIVpZO0Oktmg.', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'staff', 'yes'),
(13, 'p1', 'p1.parent@wise.tech', '$2y$10$i1eINdS3LPOJjVKELcmQCuwiYwwxft1IfHJWpDhu68s9zaleNJ/hC', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'parent', 'yes'),
(14, 'munya', 'munya.staff@wise.tech', '$2y$10$WJju28mxfA/lxZAfg/20T.hew50KwHX46se5d.XXaSBvpeMbb2YVm', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'staff', 'yes'),
(15, 'Parent One', 'parent@wisetech', '$2y$10$F5vq2/EtclTOPPiZGsCNUeXw/L/Nb6dwtO0CucVnT/1r1r2O9DRiq', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'parent', 'yes'),
(16, 'munhu', 'munhu@wise.tech.com', '$2y$10$ymAUg7pJ5wzTPIQNs3JkZudTyjDc/Sm9al4RD0E7zhcRCDCnzAqE2', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'student', 'yes'),
(17, 'boy', 'boy@wise', '$2y$10$Oond7dvNJH3V4Wrn6BTMWe790eq/WjQLdbpEaqqqeFVu5aUwi5tgy', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'student', 'no'),
(19, 'tawanda', 'tawanda@wisetech.com', '$2y$10$pi9QFne7.f9qp606ecIz6Oesf03BMKvYWYDeAi/981Dd64lOIZtNe', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'student', 'yes'),
(20, 'calisto', 'Calisto.staff@wisetech', '$2y$10$RhlwHriflc0lCuyt6lr/W.EdnWa/rcJKmeqAyDIukR7Z1S7MrOLlq', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'staff', 'yes'),
(21, 'calisto', 'Calisto.admin@wisetech', '$2y$10$x.pjomE95J6KJRyx9KDv8.pRrckQkzNjcw2WQ.jKDfpzX6Jixwrsy', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'admin', 'yes'),
(23, 'Mr Matarirano', 'matariranoa@wisetech', '$2y$10$7X8BTaXY/YgwkSqn5qzayOz6F5rI.ZDdlMD95/jNVT7RA.xcZfZPK', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'staff', 'yes'),
(24, 'Mr Manuwha', 'manuhwat@wisetech', '$2y$10$AK9xIOjUUkMCGwrhEiZzP.C2MrH8IvwCDwp9ARIODXEpxKlWdQlE.', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'staff', 'yes'),
(25, 'Mr Jangara', 'jangarag@wisetech', '$2y$10$i8hRMaxXPP66bvFOZVlOgOrHM6/qvuJmhLTJtC0XwVup77OzOwKAu', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'staff', 'yes'),
(26, 'Mr Nhepera', 'nhepera@wisetech', '$2y$10$9dz5gGwvUeJ.olOSHQgEAe7JDUps/7JbfXOffWCuyvAz1NudczKym', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'staff', 'yes'),
(27, 'Mr Zhuwaki', 'zhuwakiw@wisetech', '$2y$10$NPoKkTFCVGPs/Ms7SwUyquWeOxLlXe5Zi2z8TT1VMiM./dv77oSrm', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'staff', 'yes'),
(28, 'Mr Kufakwedeke', 'kufakwedekea@wisetech', '$2y$10$5BlU8Q514xRue/RVIy.6POBUGTZOmga1taISZNWepLDnLbx9rb3mC', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'staff', 'yes'),
(29, 'Mrs Sshumba', 'sshumba@wisetech', '$2y$10$WXoVIBN1nCfS2Y.ub2UKQeNuikrQn0QgRfktw4K873eA7lP03fZxy', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'staff', 'yes'),
(30, 'P Mushoni', 'pmishoni@wisetech', '$2y$10$On1Jz6mV7ocXBPJRLhFGgO8y.zsUswPwGyHTNu0OkfpvO3mPSZE3C', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'staff', 'yes'),
(31, 'Mrs Sshumba', 'sshumba.admin@wisetech', '$2y$10$1pPf7xIbYSLY1hvxa2HGGuIpL3nsjfD47zrpU8M.MTdqme1PJ8/xS', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'admin', 'yes'),
(32, 'Chapeyema', 'chapeyemap@wisetech', '$2y$10$06mW0qqX/ffvS8lPoXpX3ejau6SFRTQIFv5D3EafjI1YeabHfjAam', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'staff', 'yes'),
(33, 'Madondo', 'madondoj@wisetech', '$2y$10$QFmt.cABUouG926VwfOEJ.Cf5Ce6RSXxqerUb5ZJL9c3YrgrfkNV6', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'staff', 'yes'),
(34, 'Chizemo', 'chizemo@wisetech', '$2y$10$QNGlZXG438l40T3YJ9LcDetVlH7O0y0o5HqLGAhqRo7qHaY7MCkmS', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'staff', 'yes'),
(35, 'Mr TTsvarisayi', 'ttsvarisai@wisetech', '$2y$10$Gr215KxNXwaMYPpdCfRCCO8xQO4l63F4kyBD0/.BawrwS/IwGehGC', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'staff', 'yes'),
(36, 'Mrs Muchetu', 'muchetu@wisetech', '$2y$10$Pkvv.53EhJka20Hd/qzKeu9bniAAxhud4nG5kUQJW.tzOjqC.00RC', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'staff', 'yes'),
(37, 'Mr Takawira', 'takawira@wisetech', '$2y$10$H6YV.mTSUyg.Z49n9AYDyOGrcn2LrRLIvkOn7jJ.fcjVQBiSehPLC', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'staff', 'yes'),
(38, 'Abraaribrahim', 'abraaribrahim@wisetech', '$2y$10$ljkfrGISXLjanM/RxLekOuOiYP8kEEt1p0/M0W2Bs5jXVz2HQx5pu', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'student', 'yes'),
(39, 'Aimee Makamanzi', 'aimeemakamanzi.student@wisetech', '$2y$10$L52OGbOmLWLTLMkDyCEU6e2VU2gjv6bEex97APkXxvh06JBRrzlkO', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'student', 'yes'),
(40, 'Annabella Robertson', 'annabellarobertson.student@wisetech', '$2y$10$1Z5YNiUxWRWL7UU2DH0XKO8B1AjbcCcsw99IdS55seL/5lPkTDbVi', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'student', 'yes'),
(42, 'Anna Changachirere', 'annachangachirere.student@wisetech', '$2y$10$5BaMkNHYZVQ.9zX3oEqiYOWNlVQN6slo0RUzojnBj0GHxFCCg7yl.', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'student', 'yes'),
(43, 'Anotida Masunda', 'anotidamasunda.student@wisetech', '$2y$10$h1i3ERdk49ELkOXgLp.tY.zpbRdCL6HZf2OBU6WFXPPZn99k3gFkO', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'student', 'yes'),
(44, 'Ashley Bere', 'ashleybere.student@wisetech', '$2y$10$7b/LpNl.nPyzwWr0hIIeEuZdlGSgRHEhM8DAv4hLfcP.fOlS6UB4a', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'student', 'yes'),
(45, 'Chademana Emmanuel', 'chademanahemmanuel.student@wisetech', '$2y$10$blXzGux3pGQiGqX/j/7wAeuvGB7SeJ8Bkq.Czx19Z9K7Vjs7D4VN.', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'student', 'yes'),
(46, 'Charlize Mutasa', 'charlizemutasa.student@wisetech', '$2y$10$ZQX2wylMbPpHKCjW6kCRle1r6.A.GGZlctrVxgGyzrBux/pFJCHhK', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'student', 'yes'),
(47, 'Charmaine Mafurirano', 'charmainemafurirano.student@wisetech', '$2y$10$NEuZUsN2LA.rENs3DKwTpOc5nSHTBkhs8w0w3DeE.2.lvA5OMjQD6', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'student', 'yes'),
(48, 'Charmillar Chirashi', 'charmillarchirashi.student@wisetech', '$2y$10$GcqrwBqS0qVkdZpo7UAJs.zAH2bZr8z5qP7qeruplwSLUBNotUK9a', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'student', 'yes'),
(49, 'Chigaga Rose', 'chigagarose.student@wisetech', '$2y$10$zZ1R.vgQP8xUSitPQDajreHL0Rpgv.Nz9ygZ.I9UgrfCeDsQrL1VG', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'student', 'yes'),
(50, 'Chikoho Charmaine', 'chikohocharmaine.student@wisetech', '$2y$10$GG.uLyYUNcJ/ZCvFOF3PXeSzkDSMyiphSeJfM0h7kBQ0jxvgvJxUG', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'student', 'yes'),
(51, 'Chirokote Gladys', 'chirokotegladys.student@wisetech', '$2y$10$H2K3Zm6JNFUvdgKZfSmRK.2wHVp0KQThN8AKtaD/aLN2djTny2nt2', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'student', 'yes'),
(52, 'Claire Tusaumwe', 'clairetusaumwe.student@wisetech', '$2y$10$xTCWIyUbcbJWHEFP0TkpW.2pWBeRAPC3K67TxKJfEaxZyaMNsoWv.', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'student', 'yes'),
(53, 'Darren Zvinodavanhu', 'darrenzvinodavanhu.student@wisetech', '$2y$10$6O5b8Caf4wvv1x1fef0c4uUB0P1trSslFACS0.XQ.PUes0AdrbN1a', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'student', 'yes'),
(54, 'Denford Mudzova', 'denfordmudzvova.student@wisetech', '$2y$10$AL2NFoZz2190xEiWE6XxYuzAjdneb7MvBILDgAtHhOq6fHlwmsb2m', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'student', 'yes'),
(55, 'Edmore Mutambisi', 'edmoremutambisi.student@wisetech', '$2y$10$VcACcj3rEz8nfynvQFoU3OHH5q14deGybhu61TJy19zFnIsMfnIJG', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'student', 'yes'),
(56, 'Emmanuel Songore', 'emmanuelsongore.student@wisetech', '$2y$10$8ZJt6yi50Nof6bDvA7HMD.KBW9ygfZVLD9UmKfzi6Mz54ELnnugQ.', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'student', 'yes'),
(57, 'Ester Mukuwe', 'esterukuwe.student@wisetech', '$2y$10$bQPGoPGoSXZ/nAdw/NN/bOshnPI5LdfJNqs.j9mW0VAcIGQp.kpMC', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'student', 'yes'),
(58, 'Esther Govere', 'Esthergovere.student@wisetech', '$2y$10$qbB0CdQVJ3jn7RGEPM9Mie/B1anmBI4u2g4aJEgPUSd1OzSLZSUGW', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'student', 'yes'),
(59, 'Gandidzanwa Tavimba', 'gandidzanwatavimba.student@wisetech', '$2y$10$r.IzZNbzbI1YcB6Ja6DK8uyosbUqJMJxX.0q/XDr781ZIWt.JBdzq', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'student', 'yes'),
(60, 'Mandima Gerald', 'geraldmandima.student@wisetech', '$2y$10$WM1q88X8KyZ4ntTyVd1vF.gPde4neQje2srDi7H6Kbr8YbXwlsYBW', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'student', 'yes'),
(61, 'Goliath Alexander', 'goliathalalexander.student@wisetech', '$2y$10$hSAaHrU/WtFDKnbdTUYM6enofIQwSOPVfHdaC3at82BAs4sKhXrle', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'student', 'yes'),
(62, 'Gwatidzo Tyler', 'gwatidzotyler.student@wisetech', '$2y$10$WO.U/sqSffRK9.lg24gSUujFJag/8F46BdGTi5A.l3TjH8Av8g3Be', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'student', 'yes'),
(63, 'Heather Muchati', 'heathermuchati.student@wisetech', '$2y$10$kZPj1llQ9EUHd/l26mwWpOmiB6NtzC6auVEB6gA3mP/W9RbV9UzjS', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'student', 'yes'),
(64, 'Humaira Hussein Ibrahim', 'humairaHusseni.student@wisetech', '$2y$10$KL2Ny3NCOZQrGqhQu3cU4uStvudj1lAyUXH4rlATkpebQhpjDQfx.', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'student', 'yes'),
(65, 'Muparadzi Jada', 'jadamuparadzi.student@wisetech', '$2y$10$CAxqDsEo.JtlxCbEueXMWu6zETlYBgj.DKaICLwOlYYnspML53vaa', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'student', 'yes'),
(66, 'Kahari Joseph ', 'joshuakanduani.student@wisetech', '$2y$10$up3I4nGJa3ZQycFi9ZkxxugPk8kB4oz7uTFyQmtFx5DO3aK4M1Mx.', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'student', 'yes'),
(71, 'Kahari David', 'kaharidavid.student@wisetech', '$2y$10$zcuPDhrtec8jYpvJNWFajOz4mfjgS64vUh1pLK2uWUlFjsm3gzgQe', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'student', 'yes'),
(72, 'Kanjanda Tamirira', 'kanjandatamiriraishe.student@wisetech', '$2y$10$QaSCKUD6zsRv4qt6KrYLB.Liekc4KAtrOj57Wge04qP0DC.mHMrba', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'student', 'yes'),
(73, 'Karidza Anashe ', 'karidzaanashe.student@wisetech', '$2y$10$w97B1b4FWhS3Zas7FAOFb.HtK87GmBlRQhUW9uuAv5Ov7upDAFj7K', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'student', 'yes'),
(74, 'Karidza Dante', 'karidzadante.student@wisetech', '$2y$10$Kk4oPTkZ5JQGOLKDuXLUJOlprhtzXS/NKmcClWjYH9SHrluM2f.gW', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'student', 'yes'),
(75, 'Kaseke Makanaka', 'kasekemakanaka.student@wisetech', '$2y$10$aeSgjbp9txxNwXoLky/8bu5FbD7FYC/NxagqJG0MT6SZb0DQBFQSK', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'student', 'yes'),
(76, 'Taruza Kayla', 'kaylataruza.student@wisetech', '$2y$10$CMP5j4hmFRKdR54EbF4BXuY2ImDl6r3OWER6IdsYRxHeCLN1bXgda', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'student', 'yes'),
(77, 'Mahovo Keyshia', 'keyshiamahovo.student@wisetech', '$2y$10$ZHjQxB54XAH9ixBEOcsjNO4MOmlfHNgylbdyeVfw4ELnJJL0bU1KO', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'student', 'yes'),
(78, 'parent1', 'parent1@wisetech', '$2y$10$IgxiFBwzQoJkJPLickghkeGSc8uXU0IEa6YAWE2ONL.2LdYR3TCky', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'parent', 'yes'),
(79, 'Mahunze Leone', 'leonemahunze.student@wisetech', '$2y$10$foUXJQrxmyhBNDf3wB.fG.OMZ7xXu9l.CnzQyYJusTuLcaGGkksD.', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'student', 'yes'),
(80, 'Muronzi Lynn', 'lynnmuronzi.student@wisetech', '$2y$10$CibP0FhFHnIc5I64HrUrMuAgCN3OQi/z83VtU1jN2nEzpXUOl9fMy', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'student', 'yes'),
(81, 'Madowe David', 'madowedavid.student@wisetech', '$2y$10$WiXdMhWrUWqPY9GhnSVsEOYcvcYfU8X0M63vGvH0Td3v9aoUXEufW', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'student', 'yes'),
(82, 'Magara Kudakwashe ', 'magarakudakwashe.student@wisetech', '$2y$10$RRNPZitBFaIFEi05efAfhODf8jiMwGNYoA3SZ4.DHqZieecz4T3g.', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'student', 'yes'),
(83, 'Majuru Matida', 'majurumatida.student@wisetech', '$2y$10$BL8WDNuO368VUxa3QVj0xudC/YurUrklDC9sTNkJSCoMMOCMqy6Oy', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'student', 'yes'),
(84, 'Makoni Tanaka ', 'makonitanaka.student@wisetech', '$2y$10$QZi1UWDoE742IDNrzXwj7uii2uFnwe/tjVvVaTazi2eoNSOAjXHDi', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'student', 'yes'),
(87, 'Mandima Jordan', 'mandimajordan.student@wisetech', '$2y$10$ONKSlNIY8Pt36Xw8kC.Wi.JGK.EjCoXeJC0tuP5hHXhXk.o3oCxla', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'student', 'yes'),
(88, 'Mandizvidza Kudakwashe ', 'mandizvidzakudakwashe.student@wisetech', '$2y$10$0wug6fwqYkhusVIkQwKP/.rzqyzbyU1ZWjevY5K3A/96Mm.t3MeXK', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'student', 'yes'),
(89, 'Mandizvidza Kunashe', 'mandizvidzakunashe.student@wisetech', '$2y$10$V.3Mep9VVqta2JjcecGcdeIeUHWxvvJaZNAmOy9MIZw.XVASF8Vii', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'student', NULL),
(90, 'Mavondo Nicole ', 'mavondonicole.student@wisetech', '$2y$10$h/JW9ooceZxX2/Z9nh1HZeLmQXvvPX6PlAvhwLOVTJVIX/jnURa0m', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'student', NULL),
(91, 'Murungu Mercy ', 'mercymurungu.student@wisetech', '$2y$10$RGI.qg.npVmlzXjMkiwaSOs5/eeiO7QvY4u.laSbk3OJWuh9chunK', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'student', NULL),
(92, 'Chisveto Michael', 'michaelchisveto.student@wisetech', '$2y$10$9Dq9XWkgd/kBAarC014G8uAgZOZRogJWVSDGaq46AXBiOQ6l6/D4W', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'student', NULL),
(93, 'Station Micahel', 'michaelstation.student@wisetech', '$2y$10$FcjdgVqB3T13H/MopCQojeEyBGkMArLipq7NndeQXlX4F7IeDjmf6', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'student', NULL),
(94, 'Kamwanza Miguel', 'miguelkamwaza.student@wisetech', '$2y$10$lNHZaZKWkSiOus84Jg/1EuKYT7T.qsLWjW5j7S4DZpuxkAE.n0Raa', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'student', NULL),
(95, 'Moyo Brayden ', 'moyobrayden.student@wisetech', '$2y$10$VY4FWvPhI5p5Nv/fdeVoi.b0uddQdHaRXbDu8K74BF0Q.B.9BbWpK', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'student', NULL),
(96, 'Moyo Precious', 'moyoprecious.student@wisetech', '$2y$10$nzdC3ijFSw6.1zGiqsg3AOlWGwrOrjnBrToUup3dHEpA4nxHD2.BC', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'student', NULL),
(97, 'Mukori Rumbidzai', 'mukorirumbidzai.student@wisetech', '$2y$10$dsyGTa3IzaQNIektwBoyeuRQ8LUsbXyJna8wW.hAXE60FYFV.dR8W', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'student', NULL),
(98, 'Mukute Tanatswa', 'mukutetanatswa.student@wisetech', '$2y$10$IZZYCeBwcsq5lW8G7.5uAOewY4ZffZxAs/rMrUIXaiS661fCe7j/u', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'student', NULL),
(99, 'Mukwananzi Bongiwe', 'mukwananzibongiwe.student@wisetech', '$2y$10$udov38DKkrl8BT/3xnj.Ju2yNzEl58luLublTACKcrR/D9t/UwhIa', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'student', NULL),
(100, 'Mugayi Munana', 'munanamugayi.student@wisetech', '$2y$10$qC3KqW9yyv5ihMsIFdhWV.y25gh8qgmi/FZstWCRKnOUy3b5sHtpW', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'student', NULL),
(101, 'Munyaradzi Tinaye', 'munyaradzitinaye.student@wisetech', '$2y$10$v9oJmQk3wdMEvNW.nUUBV.8toybAO8CTAlg7OzdtZBMaYkUul4be6', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'student', NULL),
(102, 'Murungu Glory', 'murunguglory.student@wisetech', '$2y$10$rgWtehXlZt6MRZ/7MDX1jO94LCzxz2x9YV3dT4wcpScRJFpnBMRIq', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'student', NULL),
(103, 'Mutasa Emmanuel', 'mutasaemmanuel.student@wisetech', '$2y$10$mIchVoA5PqyG8C.BNushSu1BxpMNKPs8gYkWpIXbTlCKaH238NCky', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'student', NULL),
(104, 'Muzvidziwa Panashe', 'muzvidziwapanashe.student@wisetech', '$2y$10$Z6NMe2AknNAYpujCHlPOdOwOkGQBMooBLtoVFb5d3/xsmvZNOGGua', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'student', NULL),
(105, 'Chingwena Nicole', 'nicolechingwena.student@wisetech', '$2y$10$rnyAtgmIQxyDBH/0joAAAuDd3z8Ye4BkWmRNGxAPZZlLqC8Uiride', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'student', NULL),
(106, 'Nyapadi Devine', 'nyapadidevine.student@wisetech', '$2y$10$LczY594c2rDX7e99medaNeKGC9CqPg7CkA8yZCVeQNUEIhzQznjme', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'student', NULL),
(107, 'Nyovhi Mukudzei', 'nyovhimukudzei.student@wisetech', '$2y$10$zCKpm.5J48oUUZSFNdxdT.nk8Pr39CYqKHjeNL6QzHBbmcoMEWmNG', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'student', NULL),
(108, 'Panganai Darryl', 'panganaidarryl.student@wisetech', '$2y$10$GIge.Hwwz/DV3Q2.UKzEP.kZDU9/SVfdhupCsmdz.NKaNi5HkGBCm', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'student', NULL),
(109, 'Chuma Patrick', 'patrickchuma.student@wisetech', '$2y$10$1iqc0OXmoaaVY8pvKbtw4e5vuEsVJ4Bjz53z.CXk3RboNg85BsgxG', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'student', NULL),
(110, 'Kemorwale Prince ', 'princekemorwale.student@wisetech', '$2y$10$TzZPUsuG5mEbQr8v5gMQkOZ1oGOAfh2sc7Ecya8TLqhNBU9xSP.Xi', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'student', NULL),
(111, 'Mazorodze Rejoice ', 'rejoicemazorodze.student@wisetech', '$2y$10$F2vul5Fz.c/Y5aJiJjIq0u6PKxqlo7za0TDSYY8odTjDCap5wMKd2', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'student', NULL),
(112, 'Kaseke Ropafadzo', 'ropafadzokaseke.student@wisetech', '$2y$10$Kk.Bp5pmEwNBoB8IvYGkaOM3Keo2tOei7wlfrMQdUTzc5JM3FMJ1a', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'student', NULL),
(113, 'Nyasha Rufaro ', 'rufaronyasha.student@wisetech', '$2y$10$XLjpEBmwgmm8kVQF5f1o8.Cr0q9V8fv9.rqCXo0ph2jgJpWXvBqk.', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'student', NULL),
(114, 'Nyathi Russel', 'russelnyathi.student@wisetech', '$2y$10$.G5vR2a1d.2F7Z3wX8L/peq5DMTDqdLU.5OsBknbQLdLyi76jgUv6', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'student', NULL),
(115, 'Gaka Ruvarashe', 'ruvarashegaka.student@wisetech', '$2y$10$.1EpbszdUyRA1D7uh.PxV.FU77aO2xCsZr36FpR4vRxgGTQRl70ea', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'student', NULL),
(116, 'Kaseke Ruvarashe', 'ruvarashekaseke.student@wisetech', '$2y$10$FxuGyviPqbvPMKUTT.2ZmOL9kjKnN0C3MNptODewZMDncME4sY5KC', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'student', NULL),
(117, 'Muzvidziwa Ruvarashe ', 'ruvarashemuzvidziwa.student@wisetech', '$2y$10$ib8Hw.e55pEtlshWhpeAu.8AV732094EHKqgnBdA2ov6ITvjC/CZy', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'student', NULL),
(118, 'Nyasha Ruvarashe', 'ruvarashenyasha.student@wisetech', '$2y$10$ZKTMfO19DaLDleGnKxg7W.MnhLAQOulU5mLXn//6XW3.vR0HUzh8q', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'student', NULL),
(119, 'Nyasha Ruvimbo', 'ruvimbonyasha.student@wisetech', '$2y$10$5DWTYJYG76nv7fnQA7IW1emJqnudBa8jliWCMzwmEZ4hZYpM0OH0K', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'student', NULL),
(120, 'Rwodzi Alicia', 'rwodzialicia.student@wisetech', '$2y$10$Fh47Vrdde656nimopqPDquXt/uXyHUsIPiA3zUf8QaTWOBV7ULZUO', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'student', NULL),
(121, 'Rwodzi Chipo', 'rwodzichipo.student@wisetech', '$2y$10$vTkBJJ2coy6amIWaRXFzmuMQv4qvHCtTkLjVn7b8Cn/B1Hf/7WfJe', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'student', NULL),
(122, 'Shoniwa Shalom', 'shalomshonhiwa.student@wisetech', '$2y$10$8lCnil02Z8HrH/nMeW4YGOD5zkEwuQZHFJPthPY4eavl2qiE/iStG', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'student', NULL),
(123, 'Taruza simmeon', 'simmeontaruza.student@wisetech', '$2y$10$ouqc4uOhA1j4eNpvTXaR7umyWjkJNOz4qJRbpfBsvuaYgclnp4t1O', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'student', NULL),
(128, 'Musiwarwo Taona', 'taonamusiwarwo.student@wisetech', '$2y$10$hcfxtAiwhKTfJVyT3r1yf.65Khr7zYTotcGYVoqiSQtXdUCXPYD7S', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'student', NULL),
(129, 'Nambiri Tariro', 'tarironambiri.student@wisetech', '$2y$10$TUnzl4ExoLqrIukdg4c.h.N2lquWtFLY/h21RHQg2XAxlSFDa4bhS', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'student', NULL),
(130, 'Muzorori Tasara', 'tasaramuzorori.student@wisetech', '$2y$10$doCNZDSWCrcTzkYnRHKwxOwPWGPHFbuKLrDM2Jic.rkdKOh7irQii', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'student', NULL),
(131, 'Shamu Tavonga', 'tavongashamu.student@wisetech', '$2y$10$gXoJ/9WohZ5fyl1f8BTJke1hZ7LTpomP2X4TwqpKcePW5KdX042za', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'student', NULL),
(132, 'Farirai Tawananyasha', 'tawananyashafarirai.student@wisetech', '$2y$10$HegHhMPE.gpZfYP7Tbili.Gfu3ficiQjOomBYtcLQwq23Q/rKLFR.', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'student', NULL),
(133, 'Paradza Tawanda', 'tawandaparadzai.student@wisetech', '$2y$10$SR/Imk9OT3WWeVglnkO96.31fanMaTKF5jvX0512UdSd6xTVICM3a', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'student', NULL),
(134, 'Kondo Thabani', 'thabanikondo.student@wisetech', '$2y$10$VUv9rPBY2.0lPwvEZaJc/uNcEEMvwQexPbC9jDgeDpM.YYoN.zCJq', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'student', NULL),
(135, 'Munyaradzi Tinaye', 'tinayeunyaradzi.student@wisetech', '$2y$10$LSLhePoCae0abH4lPeTfOemrHwQDWgkEdRdyo.xDPa0elTJ7rmoBC', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'student', NULL),
(136, 'Nyakura Troy', 'troynyakura.student@wisetech', '$2y$10$6aJPfbfriGJtxQvnRymezeHWngEpeWPREWHrmBcWezvrezzTBOXJy', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'student', NULL),
(137, 'Mandimutsire Yvonne', 'yvonnemandimutsire.student@wisetech', '$2y$10$ssVw8pzBPoranktsW18BI.wno5QqLmWNm6h1AOoL/Qc/TwGX3undK', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'student', NULL),
(138, 'Zinhu Tinotenda', 'zinhutinotenda.student@wisetech', '$2y$10$1511OH/1W.ooYk2ZiVD4MO/CkdQk/CYI.gb69tt4KvkmTP0l15IIO', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'student', NULL),
(139, 'Manene Panashe ', 'manenepanashe.student@wisetech', '$2y$10$wXa3MDrHjLCRWzV9.eFMrOeG6mExGZF8hdAjnUag.Z5mgozqzGFFu', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'student', NULL),
(140, 'Marisa Joshua', 'marisajoshua.student@wisetech', '$2y$10$BV.XoZ9fl1fV/GwrmIQBA.j5Rp91CIG2tE59u2e6j7rL.AFLIYzRK', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'student', NULL),
(141, 'Mupuriri Munashe', 'mupuririmunashe.student@wisetech', '$2y$10$egKYtJverTbasDL4vlDrnuz25sY9ATa1WHQdgoweb1c4cIEq.SASW', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'student', NULL),
(142, 'Songore Michael', 'songoremichael.student@wisetech', '$2y$10$K11LWFRGE8Dv4wjTu3hqOu4PDE8YNJUbhqE86lAYdneiT9aXiTCyq', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'student', NULL),
(143, 'Banda Dan', 'danbanda.student@wisetech', '$2y$10$pmB9r4DStzJ5XcNIyfz.9.yEIxega.jZUs7JA75FPSUeeDj.L6dya', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'student', NULL),
(144, 'Changachirere Alicia', 'changachirerealicia.student@wisetech', '$2y$10$.bcpUfGaZ6YVffYlvSjWved3x13r4tbcuhAU1L/xNw9.G0Bf6IntW', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'student', NULL),
(145, 'Ronald', 'ronaldbvirinyangwe@icloud.com', '$2y$10$Crk3KmSq/TxrTt2m6Bb3.OjUD.Eqhef6wWQ98mj834zSGt5915oJO', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'parent', NULL),
(146, 'Scales', 'scaleszw@gmail.com', '$2y$10$HEA5vhPBc9hOftZSUIu3YOs9MXIDPsi.ySj/9wmOsxghFFTzctwwO', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'parent', NULL),
(147, 'Tafara Kupara', 'ronaldbvirinyangwe5005@gmail.com', '$2y$10$RM.vSccUDmzNNgPSr2TQbuu.CPdbyaVXqMbtMK/v2QWsf2K5LC3/S', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'parent', NULL),
(148, 'Anna Mugwaku', 'annamugwaku@wisetech', '$2y$10$Lv9NwDuCpZc82/h7udQVo.6mgrzwmXRuEMNuQ4lvR6EVOh9b8SkKm', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'parent', NULL),
(149, 'Thomas Gumbo', 'tgumbo@wisetech', '$2y$10$zulEDNC7fRs9ug57b.Dy.OQS0yiX7sU8OU2JBDofDXXXaS06Lm7j6', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'parent', NULL),
(150, 'Tawanda Hapwa', 'thapwa@wisetech', '$2y$10$M5bC5rHKkkAiprrKXPaL4e2Iv4tLYcZeJzFy9Iu0HU5T0AC3NKOpG', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'parent', NULL),
(151, 'Magret Mutsetse', 'mmutsetse@wisetech', '$2y$10$Z9hCDVgJ3O6rXnMwVpQPKO9i1xAiEJjo1Se.vZKCxjg3VNpzYHyJe', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'parent', NULL),
(152, 'Donnnell Gombakomba', 'dgombakomba@wisetech', '$2y$10$/qIsZ9WEjJLfNAwQujsIWeJxmuAOAiTRrlPFFlxbVudQcV9VSxg7S', NULL, NULL, NULL, '2025-08-13 21:31:38', '2025-08-13 21:31:38', 'parent', NULL),
(198, 'Ester Tumdove', 'estertumdove@gmail.com', '$2y$10$HJPVnM.cG/phHaLhNJy0C.264x8yPPkAAGRmCDvXcVY8mIqhm7m2.', NULL, NULL, NULL, '2025-08-14 05:06:27', '2025-08-14 05:06:43', 'student', 'yes'),
(200, 'John Doe', 'john.doe@example.com', '$2y$10$jBtiL8n2E5ZLQyWik3JRHufu7j8kBKDXCmtzm6kicAu6Z19zUvUS.', NULL, NULL, NULL, '2025-08-26 20:16:25', '2025-08-26 20:16:25', 'student', 'active'),
(201, 'Jane Smith', 'jane.smith@example.com', '$2y$10$ykuPGu1u/oRHB7o20A0NF.IQCQYDm41gg8ISVimAIsnnJDveTdNrW', NULL, NULL, NULL, '2025-08-26 20:16:25', '2025-08-28 11:13:52', 'staff', 'yes'),
(202, 'Mike Johnson', 'mike.j@example.com', '$2y$10$IGhpwNRjn1CaeVO8TgHjGObI8YqwK3U7ZA8uWP9vONUKnceSNx2l2', NULL, NULL, NULL, '2025-08-26 20:16:25', '2025-08-26 20:16:25', 'student', 'active'),
(203, 'Sarah Wilson', 'sarah.w@example.com', '$2y$10$pNq191Y96m5BtWQclvbCuO1nWSpzQrrO9fK7db4PF1prvoLHKwYmW', NULL, NULL, NULL, '2025-08-26 20:16:25', '2025-08-26 20:16:25', 'admin', 'active'),
(204, 'Tom Brown', 'tom.brown@example.com', '$2y$10$drhMZHbUIi7cIGvyXih.Su9eeeLFKVx4IKgZ64V90g9tuWEBd752.', NULL, NULL, NULL, '2025-08-26 20:16:25', '2025-08-26 20:18:34', 'parent', 'no'),
(207, 'Baba juju', 'panganayidaniel0@gmail.com', '$2y$10$MvlM47RvefL8s4BDNYzpDujymn4YSdzPzkQvZaXH0.PB4.JXt1vP2', 'Calisto', 'daniel', NULL, '2025-08-27 08:29:55', '2025-08-27 08:29:55', 'parent', NULL),
(208, 'Calisto Panganayi', 'calisto.student@wisetech', '$2y$10$4topv1hnfJ11yuajQDEEDOw76y199BPG/Wnmeh4DdR67wUMgFVDQu', NULL, NULL, NULL, '2025-08-27 08:40:23', '2025-08-27 09:56:12', 'student', 'yes'),
(214, 'test', 'test@gmail.com', '$2y$10$1maQ/mGqRt41VVJ1nS12L.go.w8pzo2XPUJfyqWdrMOQ.CquCppQ.', NULL, NULL, NULL, '2025-08-28 05:45:28', '2025-08-28 11:12:56', 'student', 'yes'),
(215, 'Ronald Mugoti', 'ronaldbvirinyangwe@gmail.com', '$2y$10$t74u8gX0phIFVeHZl2MhROaD1OvHmZoYV8MIm3dGAj90sBN/t7.lq', NULL, NULL, NULL, '2025-08-28 11:12:33', '2025-08-28 11:12:52', 'admin', 'yes');

-- --------------------------------------------------------

--
-- Table structure for table `user_preferences`
--

CREATE TABLE `user_preferences` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `notifications_email` tinyint(4) DEFAULT 1,
  `notifications_sms` tinyint(4) DEFAULT 0,
  `two_factor` tinyint(4) DEFAULT 0,
  `theme` varchar(20) DEFAULT 'light',
  `language` varchar(10) DEFAULT 'en',
  `auto_save` tinyint(4) DEFAULT 1,
  `compact_mode` tinyint(4) DEFAULT 0,
  `animations` tinyint(4) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_read_events`
--

CREATE TABLE `user_read_events` (
  `user_id` int(250) NOT NULL,
  `event_id` int(11) NOT NULL,
  `read_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_read_events`
--

INSERT INTO `user_read_events` (`user_id`, `event_id`, `read_at`) VALUES
(20, 1, '2025-05-26 10:06:34'),
(20, 2, '2025-05-26 10:06:34'),
(20, 3, '2025-05-26 10:06:34'),
(20, 4, '2025-05-26 10:06:34'),
(29, 1, '2025-05-28 07:27:50'),
(29, 2, '2025-05-28 07:27:50'),
(29, 3, '2025-05-28 07:27:50'),
(29, 4, '2025-05-28 07:27:50');

-- --------------------------------------------------------

--
-- Table structure for table `user_read_news`
--

CREATE TABLE `user_read_news` (
  `user_id` int(250) NOT NULL,
  `news_id` int(11) NOT NULL,
  `read_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_read_news`
--

INSERT INTO `user_read_news` (`user_id`, `news_id`, `read_at`) VALUES
(20, 1, '2025-05-26 10:44:33'),
(20, 2, '2025-05-26 10:44:33'),
(20, 3, '2025-05-26 10:44:33'),
(20, 4, '2025-05-26 10:45:47'),
(20, 5, '2025-05-27 13:59:46'),
(29, 1, '2025-05-28 07:27:35'),
(29, 2, '2025-05-28 07:27:35'),
(29, 3, '2025-05-28 07:27:35'),
(29, 4, '2025-05-28 07:27:35'),
(29, 5, '2025-05-28 07:27:35');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `assignments`
--
ALTER TABLE `assignments`
  ADD PRIMARY KEY (`assignment_id`),
  ADD KEY `fk_assignments_subject` (`subject_id`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`attendance_id`),
  ADD KEY `fk_attendance_student` (`student_id`);

--
-- Indexes for table `classes`
--
ALTER TABLE `classes`
  ADD PRIMARY KEY (`class_id`),
  ADD UNIQUE KEY `class_name` (`class_name`);

--
-- Indexes for table `educational_games`
--
ALTER TABLE `educational_games`
  ADD PRIMARY KEY (`game_id`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`event_id`),
  ADD KEY `idx_target_audience` (`target_audience`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_event_date` (`event_date`);

--
-- Indexes for table `fees`
--
ALTER TABLE `fees`
  ADD PRIMARY KEY (`fee_id`),
  ADD UNIQUE KEY `unique_student_fee` (`student_id`);

--
-- Indexes for table `marks`
--
ALTER TABLE `marks`
  ADD PRIMARY KEY (`mark_id`),
  ADD UNIQUE KEY `unique_student_subject_term` (`student_id`,`subject_id`,`class_id`,`term`,`year`,`assessment_type`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `class_id` (`class_id`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `news`
--
ALTER TABLE `news`
  ADD PRIMARY KEY (`news_id`),
  ADD KEY `idx_audience` (`audience`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `notices`
--
ALTER TABLE `notices`
  ADD PRIMARY KEY (`notice_id`),
  ADD KEY `staff_id` (`staff_id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `parents`
--
ALTER TABLE `parents`
  ADD PRIMARY KEY (`parent_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `parent_feedback`
--
ALTER TABLE `parent_feedback`
  ADD PRIMARY KEY (`feedback_id`),
  ADD KEY `parent_id` (`parent_id`);

--
-- Indexes for table `parent_fee_status`
--
ALTER TABLE `parent_fee_status`
  ADD PRIMARY KEY (`parent_id`,`student_id`,`fee_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `fee_id` (`fee_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`);

--
-- Indexes for table `results`
--
ALTER TABLE `results`
  ADD PRIMARY KEY (`result_id`),
  ADD KEY `fk_results_student` (`student_id`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`staff_id`),
  ADD KEY `id` (`id`);

--
-- Indexes for table `staff_files`
--
ALTER TABLE `staff_files`
  ADD PRIMARY KEY (`file_id`),
  ADD KEY `staff_id` (`staff_id`);

--
-- Indexes for table `staff_profile`
--
ALTER TABLE `staff_profile`
  ADD PRIMARY KEY (`staff_id`);

--
-- Indexes for table `staff_subject`
--
ALTER TABLE `staff_subject`
  ADD PRIMARY KEY (`id`),
  ADD KEY `staff_id` (`staff_id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`student_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `student_marks`
--
ALTER TABLE `student_marks`
  ADD PRIMARY KEY (`mark_id`);

--
-- Indexes for table `student_notices`
--
ALTER TABLE `student_notices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `notice_id` (`notice_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `student_parent_relationships`
--
ALTER TABLE `student_parent_relationships`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_student_parent_relationship` (`student_id`,`parent_id`,`relationship_type`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_parent_id` (`parent_id`),
  ADD KEY `idx_relationship_type` (`relationship_type`);

--
-- Indexes for table `student_profile`
--
ALTER TABLE `student_profile`
  ADD PRIMARY KEY (`student_id`);

--
-- Indexes for table `student_read_assignments`
--
ALTER TABLE `student_read_assignments`
  ADD PRIMARY KEY (`user_id`,`assignment_id`),
  ADD KEY `assignment_id` (`assignment_id`);

--
-- Indexes for table `student_read_results`
--
ALTER TABLE `student_read_results`
  ADD PRIMARY KEY (`user_id`,`result_id`),
  ADD KEY `result_id` (`result_id`);

--
-- Indexes for table `student_resources`
--
ALTER TABLE `student_resources`
  ADD PRIMARY KEY (`resource_id`),
  ADD KEY `staff_id` (`staff_id`);

--
-- Indexes for table `student_subject`
--
ALTER TABLE `student_subject`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`subject_id`),
  ADD UNIQUE KEY `subject_name` (`subject_name`),
  ADD UNIQUE KEY `subject_code` (`subject_code`);

--
-- Indexes for table `submissions`
--
ALTER TABLE `submissions`
  ADD PRIMARY KEY (`submission_id`),
  ADD KEY `idx_assignment_id` (`assignment_id`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `super_admins`
--
ALTER TABLE `super_admins`
  ADD PRIMARY KEY (`super_admin_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`setting_id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `table_subject`
--
ALTER TABLE `table_subject`
  ADD PRIMARY KEY (`subject_id`);

--
-- Indexes for table `teacher_subjects`
--
ALTER TABLE `teacher_subjects`
  ADD PRIMARY KEY (`assignment_id`),
  ADD UNIQUE KEY `unique_teacher_subject` (`teacher_id`,`subject_id`),
  ADD UNIQUE KEY `unique_teacher_subject_class` (`teacher_id`,`subject_id`,`class_id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `term_assessments`
--
ALTER TABLE `term_assessments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_result_assessment` (`result_id`,`assessment_name`),
  ADD KEY `idx_result_id` (`result_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `password` (`password`),
  ADD KEY `id` (`id`),
  ADD KEY `id_2` (`id`);

--
-- Indexes for table `user_preferences`
--
ALTER TABLE `user_preferences`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user_read_events`
--
ALTER TABLE `user_read_events`
  ADD PRIMARY KEY (`user_id`,`event_id`),
  ADD KEY `event_id` (`event_id`);

--
-- Indexes for table `user_read_news`
--
ALTER TABLE `user_read_news`
  ADD PRIMARY KEY (`user_id`,`news_id`),
  ADD KEY `news_id` (`news_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `assignments`
--
ALTER TABLE `assignments`
  MODIFY `assignment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `attendance_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `classes`
--
ALTER TABLE `classes`
  MODIFY `class_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `educational_games`
--
ALTER TABLE `educational_games`
  MODIFY `game_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `event_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fees`
--
ALTER TABLE `fees`
  MODIFY `fee_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `marks`
--
ALTER TABLE `marks`
  MODIFY `mark_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `news`
--
ALTER TABLE `news`
  MODIFY `news_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notices`
--
ALTER TABLE `notices`
  MODIFY `notice_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `parents`
--
ALTER TABLE `parents`
  MODIFY `parent_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `parent_feedback`
--
ALTER TABLE `parent_feedback`
  MODIFY `feedback_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `results`
--
ALTER TABLE `results`
  MODIFY `result_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=126;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `staff_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `staff_files`
--
ALTER TABLE `staff_files`
  MODIFY `file_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staff_subject`
--
ALTER TABLE `staff_subject`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `student_marks`
--
ALTER TABLE `student_marks`
  MODIFY `mark_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_notices`
--
ALTER TABLE `student_notices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `student_parent_relationships`
--
ALTER TABLE `student_parent_relationships`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `student_resources`
--
ALTER TABLE `student_resources`
  MODIFY `resource_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `student_subject`
--
ALTER TABLE `student_subject`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=109;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `subject_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `submissions`
--
ALTER TABLE `submissions`
  MODIFY `submission_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `super_admins`
--
ALTER TABLE `super_admins`
  MODIFY `super_admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `setting_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `table_subject`
--
ALTER TABLE `table_subject`
  MODIFY `subject_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `teacher_subjects`
--
ALTER TABLE `teacher_subjects`
  MODIFY `assignment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `term_assessments`
--
ALTER TABLE `term_assessments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(250) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=216;

--
-- AUTO_INCREMENT for table `user_preferences`
--
ALTER TABLE `user_preferences`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `assignments`
--
ALTER TABLE `assignments`
  ADD CONSTRAINT `fk_assignments_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`subject_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `fk_attendance_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `marks`
--
ALTER TABLE `marks`
  ADD CONSTRAINT `marks_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `marks_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`subject_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `marks_ibfk_3` FOREIGN KEY (`class_id`) REFERENCES `classes` (`class_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `marks_ibfk_4` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `teacher_subjects`
--
ALTER TABLE `teacher_subjects`
  ADD CONSTRAINT `teacher_subjects_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `teacher_subjects_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`subject_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `term_assessments`
--
ALTER TABLE `term_assessments`
  ADD CONSTRAINT `term_assessments_ibfk_1` FOREIGN KEY (`result_id`) REFERENCES `results` (`result_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
