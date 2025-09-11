<?php
// Start session with secure settings
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
session_start();

// Include configuration
include '../config.php';

// Security helper functions (embedded for compatibility)
class StaffSecurityHelper {
    public static function validateSession() {
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'staff' || !isset($_SESSION['user_id'])) {
            self::redirectToLogin();
        }
        
        // Check session timeout (30 minutes)
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
            session_destroy();
            self::redirectToLogin();
        }
        $_SESSION['last_activity'] = time();
    }
    
    public static function redirectToLogin() {
        header("Location: ../login.php");
        exit();
    }
    
    public static function sanitizeOutput($data) {
        if ($data === null) {
            return '';
        }
        return htmlspecialchars((string)$data, ENT_QUOTES, 'UTF-8');
    }
    
    public static function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    public static function validateCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}

// Database operations class - UPDATED for your table structure
class StaffDashboard {
    private $conn;
    private $staff_id;
    
    public function __construct($connection, $staff_id) {
        $this->conn = $connection;
        $this->staff_id = (int) $staff_id;
    }
    
    public function getStaffDetails() {
        $stmt = $this->conn->prepare("SELECT username, department, position, phone_number, email FROM staff WHERE id = ?");
        if (!$stmt) {
            error_log("Database error: " . $this->conn->error);
            return ['username' => 'Staff User', 'role' => 'Teacher', 'email' => '', 'phone' => ''];
        }
        
        $stmt->bind_param("i", $this->staff_id);
        if (!$stmt->execute()) {
            error_log("Query execution failed: " . $stmt->error);
            $stmt->close();
            return ['username' => 'Staff User', 'role' => 'Teacher', 'email' => '', 'phone' => ''];
        }
        
        $result = $stmt->get_result();
        if ($result->num_rows === 1) {
            $user_data = $result->fetch_assoc();
            $stmt->close();
            return [
                'username' => $user_data['username'] ?? 'Staff User',
                'role' => $user_data['position'] ?? 'Teacher',
                'email' => $user_data['email'] ?? '',
                'phone' => $user_data['phone_number'] ?? '',
                'department' => $user_data['department'] ?? ''
            ];
        }
        
        $stmt->close();
        return ['username' => 'Staff User', 'role' => 'Teacher', 'email' => '', 'phone' => '', 'department' => ''];
    }
    
    public function getDashboardStats() {
        $stats = [
            'students_under_subjects' => 0,
            'my_subjects_count' => 0,
            'my_assignments_count' => 0,
            'pending_submissions_count' => 0
        ];
        
        try {
            // 1. Get count of my subjects from teacher_subjects table
            $stmt = $this->conn->prepare("
                SELECT COUNT(DISTINCT ts.subject_id) as subject_count 
                FROM teacher_subjects ts
                WHERE ts.teacher_id = ?
            ");
            if ($stmt) {
                $stmt->bind_param("i", $this->staff_id);
                if ($stmt->execute()) {
                    $result = $stmt->get_result();
                    $row = $result->fetch_assoc();
                    $stats['my_subjects_count'] = (int) ($row['subject_count'] ?? 0);
                }
                $stmt->close();
            }
            
            // 2. Get students under my subjects (from teacher_subjects + enrollments/class assignments)
            // First, try with enrollments table
            $stmt = $this->conn->prepare("
                SELECT COUNT(DISTINCT e.student_id) as student_count 
                FROM teacher_subjects ts
                JOIN enrollments e ON (ts.subject_id = e.subject_id AND ts.class_id = e.class_id)
                WHERE ts.teacher_id = ?
            ");
            if ($stmt) {
                $stmt->bind_param("i", $this->staff_id);
                if ($stmt->execute()) {
                    $result = $stmt->get_result();
                    $row = $result->fetch_assoc();
                    $stats['students_under_subjects'] = (int) ($row['student_count'] ?? 0);
                }
                $stmt->close();
            } else {
                // Alternative: Try with students table if they have class_id
                $stmt = $this->conn->prepare("
                    SELECT COUNT(DISTINCT s.id) as student_count 
                    FROM teacher_subjects ts
                    JOIN students s ON ts.class_id = s.class_id
                    WHERE ts.teacher_id = ?
                ");
                if ($stmt) {
                    $stmt->bind_param("i", $this->staff_id);
                    if ($stmt->execute()) {
                        $result = $stmt->get_result();
                        $row = $result->fetch_assoc();
                        $stats['students_under_subjects'] = (int) ($row['student_count'] ?? 0);
                    }
                    $stmt->close();
                }
            }
            
            // 3. Get my assignments count - try different possible column names
            $assignment_queries = [
                "SELECT COUNT(*) as assignment_count FROM assignments WHERE teacher_id = ?",
                "SELECT COUNT(*) as assignment_count FROM assignments WHERE staff_id = ?",
                "SELECT COUNT(*) as assignment_count FROM assignments WHERE created_by = ?"
            ];
            
            foreach ($assignment_queries as $query) {
                $stmt = $this->conn->prepare($query);
                if ($stmt) {
                    $stmt->bind_param("i", $this->staff_id);
                    if ($stmt->execute()) {
                        $result = $stmt->get_result();
                        $row = $result->fetch_assoc();
                        $stats['my_assignments_count'] = (int) ($row['assignment_count'] ?? 0);
                        $stmt->close();
                        break; // Stop on first successful query
                    }
                    $stmt->close();
                }
            }
            
            // 4. Get pending submissions count - try different table structures
            $pending_queries = [
                // Query 1: Standard structure
                "SELECT COUNT(s.id) as pending_count 
                 FROM submissions s
                 JOIN assignments a ON s.assignment_id = a.id
                 WHERE a.teacher_id = ? 
                 AND (s.status = 'submitted' OR s.status IS NULL)
                 AND (s.grade IS NULL OR s.grade = '' OR s.grade = 0)",
                 
                // Query 2: Alternative structure
                "SELECT COUNT(s.submission_id) as pending_count 
                 FROM submissions s
                 JOIN assignments a ON s.assignment_id = a.assignment_id
                 WHERE a.teacher_id = ? 
                 AND (s.status = 'submitted' OR s.status IS NULL)
                 AND (s.grade IS NULL OR s.grade = '' OR s.graded = 0)",
                 
                // Query 3: With staff_id column
                "SELECT COUNT(s.id) as pending_count 
                 FROM submissions s
                 JOIN assignments a ON s.assignment_id = a.id
                 WHERE a.staff_id = ? 
                 AND (s.status = 'submitted' OR s.status IS NULL)
                 AND (s.grade IS NULL OR s.grade = '')",
                 
                // Query 4: Simple count of all submissions for teacher's assignments
                "SELECT COUNT(s.submission_id) as pending_count 
                 FROM submissions s
                 JOIN assignments a ON s.assignment_id = a.assignment_id
                 WHERE a.teacher_id = ?"
            ];
            
            foreach ($pending_queries as $query) {
                $stmt = $this->conn->prepare($query);
                if ($stmt) {
                    $stmt->bind_param("i", $this->staff_id);
                    if ($stmt->execute()) {
                        $result = $stmt->get_result();
                        $row = $result->fetch_assoc();
                        $stats['pending_submissions_count'] = (int) ($row['pending_count'] ?? 0);
                        $stmt->close();
                        break; // Stop on first successful query
                    }
                    $stmt->close();
                }
            }
            
        } catch (Exception $e) {
            error_log("Error fetching dashboard stats: " . $e->getMessage());
        }
        
        return $stats;
    }
    
    public function getRecentActivities() {
        $activities = [];
        
        try {
            // Get recent assignments - try different column names
            $activity_queries = [
                "SELECT title, created_at, 'assignment' as type 
                 FROM assignments 
                 WHERE teacher_id = ? 
                 ORDER BY created_at DESC 
                 LIMIT 5",
                 
                "SELECT title, created_at, 'assignment' as type 
                 FROM assignments 
                 WHERE staff_id = ? 
                 ORDER BY created_at DESC 
                 LIMIT 5",
                 
                "SELECT assignment_title as title, created_at, 'assignment' as type 
                 FROM assignments 
                 WHERE teacher_id = ? 
                 ORDER BY created_at DESC 
                 LIMIT 5"
            ];
            
            foreach ($activity_queries as $query) {
                $stmt = $this->conn->prepare($query);
                if ($stmt) {
                    $stmt->bind_param("i", $this->staff_id);
                    if ($stmt->execute()) {
                        $result = $stmt->get_result();
                        while ($row = $result->fetch_assoc()) {
                            $activities[] = $row;
                        }
                        $stmt->close();
                        break; // Stop on first successful query
                    }
                    $stmt->close();
                }
            }
        } catch (Exception $e) {
            error_log("Error fetching recent activities: " . $e->getMessage());
        }
        
        return $activities;
    }
    
    // New method to debug table structures
    public function debugTableStructures() {
        $debug_info = [];
        
        // Check what tables exist
        $tables_to_check = ['teacher_subjects', 'assignments', 'submissions', 'students', 'enrollments', 'subjects', 'classes'];
        
        foreach ($tables_to_check as $table) {
            $result = $this->conn->query("SHOW TABLES LIKE '$table'");
            if ($result && $result->num_rows > 0) {
                $debug_info[$table] = 'exists';
                
                // Get column structure
                $columns_result = $this->conn->query("DESCRIBE $table");
                if ($columns_result) {
                    $columns = [];
                    while ($row = $columns_result->fetch_assoc()) {
                        $columns[] = $row['Field'];
                    }
                    $debug_info[$table . '_columns'] = $columns;
                }
            } else {
                $debug_info[$table] = 'not found';
            }
        }
        
        return $debug_info;
    }
}

// Initialize security check
StaffSecurityHelper::validateSession();

// Initialize dashboard
$staff_id = $_SESSION['user_id'];
$dashboard = new StaffDashboard($conn, $staff_id);

// Get staff details and stats
$staff_details = $dashboard->getStaffDetails();
$stats = $dashboard->getDashboardStats();
$recent_activities = $dashboard->getRecentActivities();

// Debug info (remove this in production)
$debug_info = $dashboard->debugTableStructures();

// Generate CSRF token
$csrf_token = StaffSecurityHelper::generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Staff Portal Dashboard - Wisetech College">
    <meta name="robots" content="noindex, nofollow">
    <title>Staff Portal - Wisetech College</title>
    
    <!-- Preload critical resources -->
    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" as="style">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer">
    
    <!-- Add CSP for security -->
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; font-src 'self' https://cdnjs.cloudflare.com; script-src 'self' 'unsafe-inline'; img-src 'self' data:;">
    
    <style>
        :root {
            --primary-color: #2563eb;
            --primary-light: #3b82f6;
            --primary-dark: #1d4ed8;
            --secondary-color: #6366f1;
            --accent-color: #f59e0b;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --error-color: #ef4444;
            --text-color: #1e293b;
            --text-light: #64748b;
            --text-muted: #94a3b8;
            --white: #ffffff;
            --light-bg: #f8fafc;
            --border-color: #e2e8f0;
            --sidebar-width: 280px;
            --header-height: 70px;
            --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.05);
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --border-radius: 0.5rem;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: var(--light-bg);
            color: var(--text-color);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .layout-container {
            display: flex;
            flex: 1;
            position: relative;
        }

        /* Sidebar Styles */
        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            background-color: var(--white);
            box-shadow: var(--shadow);
            position: fixed;
            left: 0;
            top: 0;
            z-index: 1000;
            transition: var(--transition);
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: var(--border-color) transparent;
        }

        .sidebar::-webkit-scrollbar {
            width: 6px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: transparent;
        }

        .sidebar::-webkit-scrollbar-thumb {
            background-color: var(--border-color);
            border-radius: 3px;
        }

        .sidebar-collapsed {
            transform: translateX(-100%);
        }

        .logo-container {
            display: flex;
            align-items: center;
            padding: 1.5rem;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
            height: var(--header-height);
            border-bottom: 1px solid var(--border-color);
        }

        .logo-container img {
            height: 40px;
            margin-right: 1rem;
            border-radius: 4px;
        }

        .logo-text {
            color: var(--white);
            font-size: 1.25rem;
            font-weight: 600;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
        }

        .teacher-profile {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 2rem 1rem;
            border-bottom: 1px solid var(--border-color);
            background: linear-gradient(to bottom, var(--white), var(--light-bg));
        }

        .teacher-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: var(--white);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin-bottom: 1rem;
            box-shadow: var(--shadow);
        }

        .teacher-name {
            font-weight: 600;
            margin-bottom: 0.25rem;
            text-align: center;
        }

        .teacher-role {
            color: var(--text-light);
            font-size: 0.875rem;
            text-align: center;
        }

        /* Quick Actions */
        .quick-actions-sidebar {
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
        }

        .quick-actions-title {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--text-color);
            display: flex;
            align-items: center;
        }

        .quick-actions-title i {
            margin-right: 0.5rem;
            color: var(--primary-color);
        }

        .quick-actions-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.75rem;
        }

        .quick-action-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            background-color: var(--light-bg);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            padding: 0.75rem 0.5rem;
            text-align: center;
            transition: var(--transition);
            text-decoration: none;
            color: var(--text-color);
        }

        .quick-action-item:hover {
            background-color: var(--primary-color);
            color: var(--white);
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        .quick-action-item:hover i {
            color: var(--white);
        }

        .quick-action-item i {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            color: var(--primary-color);
            transition: var(--transition);
        }

        .quick-action-text {
            font-size: 0.75rem;
            font-weight: 500;
        }

        /* Navigation */
        .nav-list {
            list-style: none;
            padding: 1rem 0;
        }

        .nav-item {
            padding: 0 1rem;
            margin-bottom: 0.25rem;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 0.875rem 1rem;
            color: var(--text-color);
            text-decoration: none;
            border-radius: var(--border-radius);
            transition: var(--transition);
            position: relative;
        }

        .nav-link:hover {
            background-color: var(--light-bg);
            color: var(--primary-color);
        }

        .nav-link.active {
            background-color: var(--primary-color);
            color: var(--white);
        }

        .nav-icon {
            margin-right: 1rem;
            width: 1.25rem;
            text-align: center;
            flex-shrink: 0;
        }

        .badge-menu {
            background-color: var(--error-color);
            color: var(--white);
            border-radius: 10px;
            padding: 0.1rem 0.5rem;
            font-size: 0.7rem;
            font-weight: 600;
            line-height: 1;
            text-align: center;
            min-width: 18px;
            margin-left: auto;
            opacity: 0;
            transform: scale(0.8);
            transition: var(--transition);
        }

        .badge-menu.show {
            opacity: 1;
            transform: scale(1);
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            transition: var(--transition);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .main-content-expanded {
            margin-left: 0;
        }

        .header {
            height: var(--header-height);
            background-color: var(--white);
            box-shadow: var(--shadow-sm);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 2rem;
            position: sticky;
            top: 0;
            z-index: 999;
            border-bottom: 1px solid var(--border-color);
        }

        .toggle-sidebar {
            background: none;
            border: none;
            color: var(--text-color);
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: var(--border-radius);
            transition: var(--transition);
            display: none;
        }

        .toggle-sidebar:hover {
            background-color: var(--light-bg);
        }

        .header-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-color);
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .notification-badge {
            position: relative;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: var(--border-radius);
            transition: var(--transition);
        }

        .notification-badge:hover {
            background-color: var(--light-bg);
        }

        .notification-badge .badge {
            position: absolute;
            top: 0;
            right: 0;
            background-color: var(--error-color);
            color: var(--white);
            border-radius: 50%;
            width: 18px;
            height: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: 600;
            opacity: 0;
            transform: scale(0.8);
            transition: var(--transition);
        }

        .notification-badge .badge.show {
            opacity: 1;
            transform: scale(1);
        }

        .header-icon {
            font-size: 1.25rem;
            color: var(--text-light);
        }

        .logout-btn {
            background-color: var(--primary-color);
            color: var(--white);
            border: none;
            padding: 0.625rem 1.5rem;
            border-radius: var(--border-radius);
            cursor: pointer;
            font-weight: 500;
            font-size: 0.875rem;
            transition: var(--transition);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .logout-btn:hover {
            background-color: var(--primary-dark);
            transform: translateY(-1px);
        }

        /* Dashboard Content */
        .dashboard {
            padding: 2rem;
            flex: 1;
        }

        .welcome-message {
            margin-bottom: 2rem;
        }

        .welcome-message h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            color: var(--text-color);
        }

        .welcome-message p {
            color: var(--text-light);
            font-size: 1.125rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background-color: var(--white);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            transition: var(--transition);
            text-decoration: none;
            color: inherit;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: var(--border-radius);
            background-color: rgba(37, 99, 235, 0.1);
            color: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-right: 1rem;
            flex-shrink: 0;
        }

        .stat-content h3 {
            font-size: 2rem;
            margin-bottom: 0.25rem;
            font-weight: 700;
            color: var(--text-color);
        }

        .stat-content p {
            color: var(--text-light);
            font-size: 0.875rem;
            font-weight: 500;
        }

        /* Alert Messages */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: var(--border-radius);
            border: 1px solid;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .alert-info {
            background-color: rgba(37, 99, 235, 0.1);
            border-color: var(--primary-color);
            color: var(--primary-dark);
        }

        .alert-success {
            background-color: rgba(16, 185, 129, 0.1);
            border-color: var(--success-color);
            color: #065f46;
        }

        .alert-warning {
            background-color: rgba(245, 158, 11, 0.1);
            border-color: var(--warning-color);
            color: #92400e;
        }

        .alert-error {
            background-color: rgba(239, 68, 68, 0.1);
            border-color: var(--error-color);
            color: #991b1b;
        }

        /* Debug info styles */
        .debug-info {
            background-color: #f1f5f9;
            border: 1px solid #cbd5e1;
            border-radius: var(--border-radius);
            padding: 1rem;
            margin-bottom: 1rem;
            font-family: monospace;
            font-size: 0.8rem;
        }

        /* Loading States */
        .loading {
            opacity: 0.6;
            pointer-events: none;
        }

        /* Responsive Design */
        @media screen and (max-width: 1024px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media screen and (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar-active {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .toggle-sidebar {
                display: block;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .header {
                padding: 0 1rem;
            }

            .dashboard {
                padding: 1rem;
            }

            .welcome-message h1 {
                font-size: 1.5rem;
            }

            .header-actions {
                gap: 0.5rem;
            }

            .logout-btn {
                padding: 0.5rem 1rem;
                font-size: 0.8rem;
            }
        }

        @media screen and (max-width: 480px) {
            .quick-actions-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Accessibility */
        @media (prefers-reduced-motion: reduce) {
            * {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }

        /* Focus styles for keyboard navigation */
        .nav-link:focus,
        .quick-action-item:focus,
        .logout-btn:focus,
        .toggle-sidebar:focus {
            outline: 2px solid var(--primary-color);
            outline-offset: 2px;
        }
    </style>
</head>
<body>
    <div class="layout-container">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar" role="navigation" aria-label="Main navigation">
            <div class="logo-container">
                <img src="../images/logo.jpeg" alt="Solid Rock  Logo" onerror="this.style.display='none'">
                <span class="logo-text">Solid Rock </span>
            </div>

            <div class="teacher-profile">
                <div class="teacher-avatar" role="img" aria-label="Profile picture">
                    <i class="fas fa-user" aria-hidden="true"></i>
                </div>
                <h3 class="teacher-name"><?= StaffSecurityHelper::sanitizeOutput($staff_details['username']) ?></h3>
                <p class="teacher-role"><?= StaffSecurityHelper::sanitizeOutput($staff_details['role']) ?></p>
            </div>

            <div class="quick-actions-sidebar">
                <h3 class="quick-actions-title">
                    <i class="fas fa-bolt" aria-hidden="true"></i> Quick Actions
                </h3>
                <div class="quick-actions-grid">
                    <a href="mark_attendance.php" class="quick-action-item">
                        <i class="fas fa-clipboard-check" aria-hidden="true"></i>
                        <span class="quick-action-text">Take Attendance</span>
                    </a>
                    <a href="select_class.php" class="quick-action-item">
                        <i class="fas fa-chart-bar" aria-hidden="true"></i>
                        <span class="quick-action-text">Add Results</span>
                    </a>
                    <a href="add_notice.php" class="quick-action-item">
                        <i class="fas fa-bullhorn" aria-hidden="true"></i>
                        <span class="quick-action-text">Add Notice</span>
                    </a>
                    <a href="post_assignments.php" class="quick-action-item">
                        <i class="fas fa-tasks" aria-hidden="true"></i>
                        <span class="quick-action-text">Assignments</span>
                    </a>
                    <a href="review_assignments.php" class="quick-action-item">
                        <i class="fas fa-check-to-slot" aria-hidden="true"></i>
                        <span class="quick-action-text">Review Work</span>
                    </a>
                </div>
            </div>

            <nav>
                <ul class="nav-list" role="menubar">
                    <li class="nav-item" role="none">
                        <a href="staff_home.php" class="nav-link active" role="menuitem">
                            <i class="fas fa-home nav-icon" aria-hidden="true"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item" role="none">
                        <a href="mark_attendance.php" class="nav-link" role="menuitem">
                            <i class="fas fa-clipboard-check nav-icon" aria-hidden="true"></i>
                            <span>Take Attendance</span>
                        </a>
                    </li>
                    <li class="nav-item" role="none">
                        <a href="select_class.php" class="nav-link" role="menuitem">
                            <i class="fas fa-chart-bar nav-icon" aria-hidden="true"></i>
                            <span>Add Results</span>
                        </a>
                    </li>
                    <li class="nav-item" role="none">
                        <a href="add_notice.php" class="nav-link" role="menuitem">
                            <i class="fas fa-bullhorn nav-icon" aria-hidden="true"></i>
                            <span>Add Notice</span>
                        </a>
                    </li>
                    <li class="nav-item" role="none">
                        <a href="post_assignments.php" class="nav-link" role="menuitem">
                            <i class="fas fa-plus-circle nav-icon" aria-hidden="true"></i>
                            <span>Post Assignments</span>
                        </a>
                    </li>
                    <li class="nav-item" role="none">
                        <a href="review_assignments.php" class="nav-link" role="menuitem">
                            <i class="fas fa-inbox nav-icon" aria-hidden="true"></i>
                            <span>View Assignments</span>
                            <span class="badge-menu <?= $stats['pending_submissions_count'] > 0 ? 'show' : '' ?>" id="badge-assignments">
                                <?= $stats['pending_submissions_count'] ?>
                            </span>
                        </a>
                    </li>
                    <li class="nav-item" role="none">
                        <a href="assign_class_subject.php" class="nav-link" role="menuitem">
                            <i class="fas fa-chalkboard-teacher nav-icon" aria-hidden="true"></i>
                            <span>Assign Subject/Class</span>
                        </a>
                    </li>
                    <li class="nav-item" role="none">
                        <a href="add_resource.php" class="nav-link" role="menuitem">
                            <i class="fas fa-plus-circle nav-icon" aria-hidden="true"></i>
                            <span>Add Resources</span>
                        </a>
                    </li>
                    <li class="nav-item" role="none">
                        <a href="edit_resource.php" class="nav-link" role="menuitem">
                            <i class="fas fa-edit nav-icon" aria-hidden="true"></i>
                            <span>Edit Resources</span>
                        </a>
                    </li>
                    <li class="nav-item" role="none">
                        <a href="../admin/view_news.php" class="nav-link" role="menuitem">
                            <i class="fas fa-newspaper nav-icon" aria-hidden="true"></i>
                            <span>View News</span>
                            <span class="badge-menu" id="badge-news">0</span>
                        </a>
                    </li>
                    <li class="nav-item" role="none">
                        <a href="staff_events.php" class="nav-link" role="menuitem">
                            <i class="fas fa-calendar-alt nav-icon" aria-hidden="true"></i>
                            <span>View Events</span>
                            <span class="badge-menu" id="badge-events">0</span>
                        </a>
                    </li>
                    <li class="nav-item" role="none">
                        <a href="student_list.php" class="nav-link" role="menuitem">
                            <i class="fas fa-history nav-icon" aria-hidden="true"></i>
                            <span>Results History</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content" id="main-content">
            <header class="header">
                <button class="toggle-sidebar" id="toggle-sidebar" aria-label="Toggle sidebar navigation">
                    <i class="fas fa-bars" aria-hidden="true"></i>
                </button>
                <h1 class="header-title">Teacher Dashboard</h1>
                <div class="header-actions">
                    <div class="notification-badge" role="button" tabindex="0" aria-label="View notifications">
                        <a href="view_news.php" aria-label="View news and notifications">
                            <i class="fas fa-bell header-icon" aria-hidden="true"></i>
                            <span class="badge" id="notificationBadgeCount">0</span>
                        </a>
                    </div>
                    <a href="../logout.php" class="logout-btn" onclick="return confirm('Are you sure you want to logout?')">
                        <i class="fas fa-sign-out-alt" aria-hidden="true"></i>
                        Logout
                    </a>
                </div>
            </header>

            <section class="dashboard">
                <div class="welcome-message">
                    <h1>Welcome back, <?= StaffSecurityHelper::sanitizeOutput($staff_details['username']) ?>!</h1>
                    <p>Here's what's happening at Solid Rock today.</p>
                </div>

                <!-- Dynamic notifications area -->
                <div id="dynamic-notification" class="alert alert-info" style="display:none;" role="alert">
                    <i class="fas fa-info-circle" aria-hidden="true"></i>
                    <span id="dynamic-notification-message"></span>
                </div>

                <!-- Dashboard Statistics -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon" style="background-color: rgba(16, 185, 129, 0.1); color: var(--success-color);">
                            <i class="fas fa-users" aria-hidden="true"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?= StaffSecurityHelper::sanitizeOutput($stats['students_under_subjects']) ?></h3>
                            <p>Students Under My Subjects</p>
                        </div>
                    </div>

                    <a href="assign_class_subject.php" class="stat-card" style="text-decoration: none; color: inherit;">
                        <div class="stat-icon" style="background-color: rgba(37, 99, 235, 0.1); color: var(--primary-color);">
                            <i class="fas fa-book-open" aria-hidden="true"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?= StaffSecurityHelper::sanitizeOutput($stats['my_subjects_count']) ?></h3>
                            <p>My Subjects</p>
                        </div>
                    </a>

                    <a href="view_my_assignments.php" class="stat-card" style="text-decoration: none; color: inherit;">
                        <div class="stat-icon" style="background-color: rgba(99, 102, 241, 0.1); color: var(--secondary-color);">
                            <i class="fas fa-folder-open" aria-hidden="true"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?= StaffSecurityHelper::sanitizeOutput($stats['my_assignments_count']) ?></h3>
                            <p>My Assignments</p>
                        </div>
                    </a>

                    <a href="view_my_assignments.php" class="stat-card" style="text-decoration: none; color: inherit;">
                        <div class="stat-icon" style="background-color: rgba(245, 158, 11, 0.1); color: var(--accent-color);">
                            <i class="fas fa-inbox" aria-hidden="true"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?= StaffSecurityHelper::sanitizeOutput($stats['pending_submissions_count']) ?></h3>
                            <p>Pending Reviews</p>
                        </div>
                    </a>
                </div>

                <!-- Getting Started Guide (if teacher has no subjects assigned) -->
                <?php if ($stats['my_subjects_count'] === 0): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle" aria-hidden="true"></i>
                    <div>
                        <strong>Getting Started:</strong> You haven't assigned yourself to any subjects yet. 
                        <a href="assign_class_subject.php" style="color: var(--primary-dark); font-weight: 600;">Click here to assign yourself to classes and subjects</a> 
                        to start using the teacher portal features.
                    </div>
                </div>
                <?php endif; ?>

                <!-- Recent Activities Section -->
                <?php if (!empty($recent_activities)): ?>
                <div class="recent-activities" style="background-color: var(--white); border-radius: var(--border-radius); padding: 1.5rem; box-shadow: var(--shadow-sm); border: 1px solid var(--border-color);">
                    <h2 style="margin-bottom: 1rem; color: var(--text-color); font-size: 1.25rem;">Recent Activities</h2>
                    <div class="activities-list">
                        <?php foreach ($recent_activities as $activity): ?>
                        <div class="activity-item" style="display: flex; align-items: center; padding: 0.75rem 0; border-bottom: 1px solid var(--border-color);">
                            <div class="activity-icon" style="width: 40px; height: 40px; border-radius: 50%; background-color: rgba(37, 99, 235, 0.1); color: var(--primary-color); display: flex; align-items: center; justify-content: center; margin-right: 1rem;">
                                <i class="fas fa-<?= $activity['type'] === 'assignment' ? 'tasks' : 'file-alt' ?>" aria-hidden="true"></i>
                            </div>
                            <div class="activity-content" style="flex: 1;">
                                <h4 style="margin: 0; font-size: 0.875rem; color: var(--text-color);">
                                    <?= StaffSecurityHelper::sanitizeOutput($activity['title']) ?>
                                </h4>
                                <p style="margin: 0; font-size: 0.75rem; color: var(--text-light);">
                                    <?= date('M j, Y \a\t g:i A', strtotime($activity['created_at'])) ?>
                                </p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </section>
        </main>
    </div>

    <!-- Hidden CSRF token for AJAX requests -->
    <input type="hidden" id="csrf-token" value="<?= StaffSecurityHelper::sanitizeOutput($csrf_token) ?>">

    <script>
        'use strict';

        class DashboardManager {
            constructor() {
                this.initializeEventListeners();
                this.initializeBadgeUpdates();
                this.handleResponsiveDesign();
            }

            initializeEventListeners() {
                // Sidebar toggle functionality
                const toggleBtn = document.getElementById('toggle-sidebar');
                const sidebar = document.getElementById('sidebar');
                
                if (toggleBtn && sidebar) {
                    toggleBtn.addEventListener('click', (e) => {
                        e.preventDefault();
                        sidebar.classList.toggle('sidebar-active');
                    });

                    // Close sidebar when clicking outside on mobile
                    document.addEventListener('click', (e) => {
                        if (window.innerWidth <= 768 && 
                            !sidebar.contains(e.target) && 
                            !toggleBtn.contains(e.target) &&
                            sidebar.classList.contains('sidebar-active')) {
                            sidebar.classList.remove('sidebar-active');
                        }
                    });
                }

                // Keyboard navigation for accessibility
                document.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape' && sidebar && sidebar.classList.contains('sidebar-active')) {
                        sidebar.classList.remove('sidebar-active');
                        toggleBtn?.focus();
                    }
                });
            }

            handleResponsiveDesign() {
                const handleResize = () => {
                    const sidebar = document.getElementById('sidebar');
                    if (window.innerWidth > 768 && sidebar) {
                        sidebar.classList.remove('sidebar-active');
                    }
                };

                window.addEventListener('resize', this.debounce(handleResize, 250));
                handleResize(); // Initial call
            }

            async initializeBadgeUpdates() {
                await this.fetchMenuBadgeCounts();
                // Update badges every 60 seconds
                setInterval(() => this.fetchMenuBadgeCounts(), 60000);
            }

            async fetchMenuBadgeCounts() {
                try {
                    const csrfToken = document.getElementById('csrf-token')?.value;
                    if (!csrfToken) {
                        console.warn('CSRF token not found');
                        return;
                    }

                    const response = await fetch('get_menu_badges.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({
                            csrf_token: csrfToken
                        })
                    });

                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }

                    const data = await response.json();
                    
                    if (data.success) {
                        this.updateBadgeCounts(data.data);
                    } else {
                        console.warn('Badge update failed:', data.message);
                    }
                } catch (error) {
                    console.error('Error fetching menu badge counts:', error);
                    this.showNotification('Failed to update notification counts', 'error');
                }
            }

            updateBadgeCounts(data) {
                const badges = {
                    'badge-news': data.news || 0,
                    'badge-events': data.events || 0,
                    'badge-assignments': data.pending_submissions || 0
                };

                Object.entries(badges).forEach(([id, count]) => {
                    this.updateBadge(id, count);
                });

                // Update main notification badge
                const notificationBadge = document.getElementById('notificationBadgeCount');
                if (notificationBadge) {
                    const totalCount = (data.news || 0) + (data.events || 0);
                    notificationBadge.textContent = totalCount;
                    notificationBadge.classList.toggle('show', totalCount > 0);
                }
            }

            updateBadge(elementId, count) {
                const badge = document.getElementById(elementId);
                if (badge) {
                    const numCount = parseInt(count, 10) || 0;
                    badge.textContent = numCount;
                    badge.classList.toggle('show', numCount > 0);
                }
            }

            showNotification(message, type = 'info', duration = 5000) {
                const notification = document.getElementById('dynamic-notification');
                const messageElement = document.getElementById('dynamic-notification-message');
                
                if (notification && messageElement) {
                    messageElement.textContent = message;
                    notification.className = `alert alert-${type}`;
                    notification.style.display = 'flex';
                    
                    // Auto hide after duration
                    setTimeout(() => {
                        notification.style.display = 'none';
                    }, duration);
                }
            }

            debounce(func, wait) {
                let timeout;
                return function executedFunction(...args) {
                    const later = () => {
                        clearTimeout(timeout);
                        func(...args);
                    };
                    clearTimeout(timeout);
                    timeout = setTimeout(later, wait);
                };
            }
        }

        // Initialize dashboard when DOM is loaded
        document.addEventListener('DOMContentLoaded', () => {
            new DashboardManager();
        });

        // Service Worker registration for offline capabilities (optional)
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js')
                    .then(registration => {
                        console.log('SW registered: ', registration);
                    })
                    .catch(registrationError => {
                        console.log('SW registration failed: ', registrationError);
                    });
            });
        }
    </script>
</body>
</html>