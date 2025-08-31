<?php
// This header file will be included at the top of all your staff pages.
// Check if session is not already started before calling session_start()
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include '../config.php'; // Database connection

// Check if the user is logged in and is staff
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'staff' || !isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// --- Get Staff Info and Current Page ---
$staff_id = $_SESSION['user_id'];
$staff_username = "Staff User";
$staff_role = "Teacher";
$currentPage = basename($_SERVER['SCRIPT_NAME']); // This gets the current file name, e.g., "staff_home.php"

$stmt = $conn->prepare("SELECT username, role FROM users WHERE id = ?");
if ($stmt) {
    $stmt->bind_param("i", $staff_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $user_data = $result->fetch_assoc();
        $staff_username = $user_data['username'];
        $staff_role = ucfirst($user_data['role']);
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Staff Portal'; ?> - Wisetech College</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2563eb;
            --primary-light: #3b82f6;
            --primary-dark: #1d4ed8;
            --secondary-color: #64748b;
            --accent-color: #0ea5e9;
            --text-color: #1e293b;
            --text-light: #64748b;
            --white: #ffffff;
            --light-bg: #f1f5f9;
            --sidebar-width: 280px;
            --sidebar-width-collapsed: 70px;
            --header-height: 70px;
            --shadow-sm: 0 1px 2px rgba(37, 99, 235, 0.05);
            --shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.1), 0 2px 4px -1px rgba(37, 99, 235, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(37, 99, 235, 0.1), 0 4px 6px -2px rgba(37, 99, 235, 0.05);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --gradient-bg: linear-gradient(135deg, #2563eb 0%, #3b82f6 50%, #0ea5e9 100%);
            --blue-gradient: linear-gradient(135deg, #1e40af 0%, #2563eb 100%);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--gradient-bg);
            color: var(--text-color);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
        }

        .layout-container {
            display: flex;
            flex: 1;
            position: relative;
        }

        /* Enhanced Sidebar Styles */
        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            border-right: 1px solid rgba(37, 99, 235, 0.1);
            box-shadow: var(--shadow-lg);
            position: fixed;
            left: 0;
            top: 0;
            z-index: 1000;
            transition: var(--transition);
            overflow-y: auto;
            overflow-x: hidden;
        }

        .sidebar.collapsed {
            width: var(--sidebar-width-collapsed);
        }

        .sidebar.mobile-hidden {
            transform: translateX(-100%);
        }

        /* Custom Scrollbar */
        .sidebar::-webkit-scrollbar {
            width: 6px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: transparent;
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: var(--gradient-bg);
            border-radius: 10px;
        }

        /* Logo Section */
        .logo-container {
            display: flex;
            align-items: center;
            padding: 1.5rem;
            background: var(--gradient-bg);
            height: var(--header-height);
            position: relative;
            overflow: hidden;
        }

        .logo-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="20" cy="20" r="1" fill="rgba(255,255,255,0.2)"/><circle cx="80" cy="80" r="1" fill="rgba(255,255,255,0.2)"/></svg>');
            opacity: 0.3;
        }

        .logo-container img {
            height: 40px;
            margin-right: 1rem;
            border-radius: 8px;
            position: relative;
            z-index: 1;
            transition: var(--transition);
            box-shadow: 0 4px 8px rgba(255, 255, 255, 0.2);
        }

        .logo-text {
            color: var(--white);
            font-size: 1.25rem;
            font-weight: 700;
            position: relative;
            z-index: 1;
            transition: var(--transition);
            white-space: nowrap;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .sidebar.collapsed .logo-text {
            opacity: 0;
            width: 0;
            margin: 0;
        }

        .sidebar.collapsed .logo-container img {
            margin-right: 0;
        }

        /* Teacher Profile */
        .teacher-profile {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 2rem 1rem;
            border-bottom: 1px solid rgba(37, 99, 235, 0.1);
            transition: var(--transition);
            background: rgba(37, 99, 235, 0.02);
        }

        .sidebar.collapsed .teacher-profile {
            padding: 1rem 0.5rem;
        }

        .teacher-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: var(--blue-gradient);
            color: var(--white);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin-bottom: 1rem;
            transition: var(--transition);
            box-shadow: 0 8px 25px rgba(37, 99, 235, 0.3);
            border: 3px solid rgba(255, 255, 255, 0.2);
        }

        .sidebar.collapsed .teacher-avatar {
            width: 50px;
            height: 50px;
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }

        .teacher-name {
            font-weight: 600;
            margin-bottom: 0.25rem;
            text-align: center;
            transition: var(--transition);
            color: var(--primary-color);
        }

        .teacher-role {
            color: var(--text-light);
            font-size: 0.875rem;
            text-align: center;
            transition: var(--transition);
        }

        .sidebar.collapsed .teacher-name,
        .sidebar.collapsed .teacher-role {
            opacity: 0;
            height: 0;
            margin: 0;
            overflow: hidden;
        }

        /* Navigation */
        .nav-list {
            list-style: none;
            padding: 1rem 0;
        }

        .nav-item {
            padding: 0 1rem;
            margin-bottom: 0.5rem;
        }

        .sidebar.collapsed .nav-item {
            padding: 0 0.5rem;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 0.875rem 1rem;
            color: var(--text-color);
            text-decoration: none;
            border-radius: 12px;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            font-weight: 500;
        }

        .nav-link:hover {
            background: var(--gradient-bg);
            color: var(--white);
            transform: translateX(5px);
            box-shadow: 0 4px 15px rgba(37, 99, 235, 0.25);
        }

        .nav-link.active {
            background: var(--blue-gradient);
            color: var(--white);
            box-shadow: 0 4px 15px rgba(37, 99, 235, 0.3);
            font-weight: 600;
        }

        .nav-icon {
            margin-right: 1rem;
            width: 1.25rem;
            text-align: center;
            font-size: 1.1rem;
            transition: var(--transition);
        }

        .sidebar.collapsed .nav-icon {
            margin-right: 0;
        }

        .nav-link span:not(.badge-menu) {
            flex-grow: 1;
            transition: var(--transition);
            white-space: nowrap;
        }

        .sidebar.collapsed .nav-link span:not(.badge-menu) {
            opacity: 0;
            width: 0;
            overflow: hidden;
        }

        .badge-menu {
            background-color: var(--accent-color);
            color: var(--white);
            border-radius: 12px;
            padding: 0.2rem 0.6rem;
            font-size: 0.7rem;
            font-weight: 600;
            display: none;
        }

        .badge-menu.show {
            display: inline-block;
        }

        /* Tooltip for collapsed sidebar */
        .sidebar.collapsed .nav-link {
            position: relative;
        }

        .sidebar.collapsed .nav-link::after {
            content: attr(data-tooltip);
            position: absolute;
            left: 100%;
            top: 50%;
            transform: translateY(-50%);
            background: var(--primary-dark);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.875rem;
            white-space: nowrap;
            opacity: 0;
            pointer-events: none;
            transition: var(--transition);
            margin-left: 10px;
            z-index: 1001;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }

        .sidebar.collapsed .nav-link:hover::after {
            opacity: 1;
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

        .sidebar.collapsed + .main-content {
            margin-left: var(--sidebar-width-collapsed);
        }

        /* Enhanced Header */
        .header {
            height: var(--header-height);
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(37, 99, 235, 0.1);
            box-shadow: var(--shadow);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 2rem;
            position: sticky;
            top: 0;
            z-index: 99;
            transition: var(--transition);
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .toggle-sidebar {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--primary-color);
            padding: 0.5rem;
            border-radius: 8px;
            transition: var(--transition);
        }

        .toggle-sidebar:hover {
            background: var(--light-bg);
            color: var(--primary-dark);
            transform: scale(1.1);
        }

        .header-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin: 0;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .logout-btn {
            background: var(--blue-gradient);
            color: var(--white);
            border: none;
            padding: 0.7rem 1.5rem;
            border-radius: 10px;
            cursor: pointer;
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
            box-shadow: 0 4px 15px rgba(37, 99, 235, 0.2);
            font-size: 0.9rem;
        }

        .logout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(37, 99, 235, 0.35);
            background: var(--primary-dark);
        }

        /* Page Content */
        .page-content {
            flex: 1;
            padding: 0;
            background: transparent;
        }

        /* Overlay for mobile */
        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(37, 99, 235, 0.1);
            backdrop-filter: blur(4px);
            z-index: 999;
            opacity: 0;
            visibility: hidden;
            transition: var(--transition);
        }

        .sidebar-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        /* Responsive Design */
        @media screen and (max-width: 1200px) {
            .main-content {
                margin-left: var(--sidebar-width-collapsed);
            }
            
            .sidebar {
                width: var(--sidebar-width-collapsed);
            }
            
            .sidebar .teacher-name,
            .sidebar .teacher-role,
            .sidebar .logo-text,
            .sidebar .nav-link span:not(.badge-menu) {
                opacity: 0;
                width: 0;
                overflow: hidden;
            }
            
            .sidebar .teacher-profile {
                padding: 1rem 0.5rem;
            }
            
            .sidebar .teacher-avatar {
                width: 50px;
                height: 50px;
                font-size: 1.5rem;
                margin-bottom: 0.5rem;
            }
            
            .sidebar .nav-item {
                padding: 0 0.5rem;
            }
            
            .sidebar .nav-icon {
                margin-right: 0;
            }
            
            .sidebar .logo-container img {
                margin-right: 0;
            }
            
            .header {
                padding: 0 1.5rem;
            }
        }

        @media screen and (max-width: 768px) {
            .sidebar {
                width: var(--sidebar-width);
                transform: translateX(-100%);
            }
            
            .sidebar.mobile-active {
                transform: translateX(0);
            }
            
            .sidebar.mobile-active .teacher-name,
            .sidebar.mobile-active .teacher-role,
            .sidebar.mobile-active .logo-text,
            .sidebar.mobile-active .nav-link span:not(.badge-menu) {
                opacity: 1;
                width: auto;
                overflow: visible;
            }
            
            .sidebar.mobile-active .teacher-profile {
                padding: 2rem 1rem;
            }
            
            .sidebar.mobile-active .teacher-avatar {
                width: 80px;
                height: 80px;
                font-size: 2rem;
                margin-bottom: 1rem;
            }
            
            .sidebar.mobile-active .nav-item {
                padding: 0 1rem;
            }
            
            .sidebar.mobile-active .nav-icon {
                margin-right: 1rem;
            }
            
            .sidebar.mobile-active .logo-container img {
                margin-right: 1rem;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .header {
                padding: 0 1rem;
            }
            
            .header-title {
                font-size: 1.25rem;
            }
        }

        @media screen and (max-width: 480px) {
            .header {
                padding: 0 0.75rem;
            }
            
            .header-title {
                font-size: 1rem;
            }
            
            .logout-btn {
                padding: 0.6rem 1rem;
                font-size: 0.8rem;
            }
            
            .toggle-sidebar {
                font-size: 1.3rem;
                padding: 0.4rem;
            }
        }

        @media screen and (max-width: 360px) {
            .header {
                padding: 0 0.5rem;
            }
            
            .header-title {
                display: none;
            }
            
            .logout-btn {
                padding: 0.5rem 0.8rem;
            }
        }

        /* Animation for smooth transitions */
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .nav-link {
            animation: slideIn 0.3s ease-out;
        }

        /* Enhanced focus states for accessibility */
        .nav-link:focus,
        .toggle-sidebar:focus,
        .logout-btn:focus {
            outline: 2px solid var(--primary-color);
            outline-offset: 2px;
        }
    </style>
</head>
<body>
    <div class="sidebar-overlay" id="sidebar-overlay"></div>
    
    <div class="layout-container">
        <aside class="sidebar" id="sidebar">
            <div class="logo-container">
                <img src="../images/logo.jpg" alt="Wisetech College Logo">
                <span class="logo-text">Wisetech College</span>
            </div>
            <div class="teacher-profile">
                <div class="teacher-avatar"><i class="fas fa-user"></i></div>
                <h3 class="teacher-name"><?= htmlspecialchars($staff_username) ?></h3>
                <p class="teacher-role"><?= htmlspecialchars($staff_role) ?></p>
            </div>
            
            <nav>
                <ul class="nav-list">
                    <li class="nav-item">
                        <a href="staff_home.php" class="nav-link <?= ($currentPage == 'staff_home.php') ? 'active' : '' ?>" data-tooltip="Dashboard">
                            <i class="fas fa-home nav-icon"></i><span>Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="mark_attendance.php" class="nav-link <?= ($currentPage == 'mark_attendance.php') ? 'active' : '' ?>" data-tooltip="Take Attendance">
                            <i class="fas fa-clipboard-check nav-icon"></i><span>Take Attendance</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="select_class.php" class="nav-link <?= ($currentPage == 'select_class.php' || $currentPage == 'add_results.php') ? 'active' : '' ?>" data-tooltip="Add Results">
                            <i class="fas fa-chart-bar nav-icon"></i><span>Add Results</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="add_notice.php" class="nav-link <?= ($currentPage == 'add_notice.php') ? 'active' : '' ?>" data-tooltip="Add Notice">
                            <i class="fas fa-bullhorn nav-icon"></i><span>Add Notice</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="staff_view_subjects.php" class="nav-link <?= ($currentPage == 'staff_view_subjects.php') ? 'active' : '' ?>" data-tooltip="My Subjects">
                            <i class="fas fa-book nav-icon"></i><span>My Subjects</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="post_assignments.php" class="nav-link <?= ($currentPage == 'post_assignments.php') ? 'active' : '' ?>" data-tooltip="Post Assignments">
                            <i class="fas fa-tasks nav-icon"></i><span>Post Assignments</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="review_assignments.php" class="nav-link <?= ($currentPage == 'review_assignments.php') ? 'active' : '' ?>" data-tooltip="Review Assignments">
                            <i class="fas fa-check-to-slot nav-icon"></i><span>Review Assignments</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="assign_class_subject.php" class="nav-link <?= ($currentPage == 'assign_class_subject.php') ? 'active' : '' ?>" data-tooltip="Assign Subject/Class">
                            <i class="fas fa-chalkboard-teacher nav-icon"></i><span>Assign Subject/Class</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="staff_database.php" class="nav-link <?= ($currentPage == 'staff_database.php') ? 'active' : '' ?>" data-tooltip="Staff Database">
                            <i class="fas fa-users nav-icon"></i><span>Staff Database</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="add_resource.php" class="nav-link <?= ($currentPage == 'add_resource.php') ? 'active' : '' ?>" data-tooltip="Add Resources">
                            <i class="fas fa-plus-circle nav-icon"></i><span>Add Resources</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="edit_resource.php" class="nav-link <?= ($currentPage == 'edit_resource.php') ? 'active' : '' ?>" data-tooltip="Edit Resources">
                            <i class="fas fa-edit nav-icon"></i><span>Edit Resources</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="../admin/view_news.php" class="nav-link <?= ($currentPage == 'view_news.php') ? 'active' : '' ?>" data-tooltip="View News">
                            <i class="fas fa-newspaper nav-icon"></i><span>View News</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="staff_events.php" class="nav-link <?= ($currentPage == 'staff_events.php') ? 'active' : '' ?>" data-tooltip="View Events">
                            <i class="fas fa-calendar-alt nav-icon"></i><span>View Events</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="student_list.php" class="nav-link <?= ($currentPage == 'student_list.php') ? 'active' : '' ?>" data-tooltip="Results History">
                            <i class="fas fa-history nav-icon"></i><span>Results History</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="staff_assistant.php" class="nav-link <?= ($currentPage == 'staff_assistant.php') ? 'active' : '' ?>" data-tooltip="AI Report Assistant">
                            <i class="fas fa-robot nav-icon"></i><span>AI Report Assistant</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <main class="main-content" id="main-content">
            <header class="header">
                <div class="header-left">
                    <button class="toggle-sidebar" id="toggle-sidebar">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h2 class="header-title"><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Staff Portal'; ?></h2>
                </div>
                <div class="header-actions">
                    <a href="../logout.php" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </header>

            <div class="page-content">

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const toggleBtn = document.getElementById('toggle-sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    const mainContent = document.getElementById('main-content');

    // Toggle sidebar functionality
    toggleBtn.addEventListener('click', function() {
        if (window.innerWidth <= 768) {
            // Mobile behavior
            sidebar.classList.toggle('mobile-active');
            overlay.classList.toggle('active');
        } else if (window.innerWidth <= 1200) {
            // Tablet behavior - already collapsed, do nothing
            return;
        } else {
            // Desktop behavior
            sidebar.classList.toggle('collapsed');
        }
    });

    // Close sidebar when clicking overlay (mobile)
    overlay.addEventListener('click', function() {
        sidebar.classList.remove('mobile-active');
        overlay.classList.remove('active');
    });

    // Handle window resize
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            sidebar.classList.remove('mobile-active');
            overlay.classList.remove('active');
        }
        
        if (window.innerWidth > 1200) {
            sidebar.classList.remove('collapsed');
        } else if (window.innerWidth > 768) {
            sidebar.classList.add('collapsed');
        }
    });

    // Initialize sidebar state based on screen size
    if (window.innerWidth <= 1200 && window.innerWidth > 768) {
        sidebar.classList.add('collapsed');
    }

    // Add smooth scroll behavior for better UX
    document.documentElement.style.scrollBehavior = 'smooth';
});
</script>