<?php
// Always start the session at the very top of every page.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is logged in as a student
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'student') {
    header("Location: ../login.php"); // Redirect to the main login if not authorized
    exit();
}

// Get the name of the current page to set the 'active' link
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- The page title will be set by each individual page -->
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Solid Rock '; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
     <!-- Favicon -->
    <link rel="icon" type="image/jpeg" href="../images/logo.jpeg">
    <link rel="shortcut icon" type="image/jpeg" href="../images/logo.jpeg">
    
    <!-- UNIFIED STYLESHEET FOR ALL STUDENT PAGES -->
    <style>
        :root {
            --bg-color: #f4f7fe;
            --sidebar-bg: #ffffff;
            --widget-bg: #ffffff;
            --primary-text: #27272a;
            --secondary-text: #6b7280;
            --accent-purple: #7c3aed;
            --accent-blue: #3b82f6;
            --accent-pink: #ec4899;
            --notification-red: #ef4444;
            --border-color: #eef2f9;
            --shadow: 0 4px 15px -3px rgba(0, 0, 0, 0.05);
            --shadow-lg: 0 10px 25px -5px rgba(0, 0, 0, 0.07);
            --rounded-lg: 0.75rem;
            --rounded-xl: 1rem;
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', system-ui, -apple-system, BlinkMacSystemFont, sans-serif;
        }

        body {
            background-color: var(--bg-color);
            color: var(--primary-text);
            display: flex;
            min-height: 100vh;
        }

        /* --- UNIFIED SIDEBAR --- */
        .sidebar {
            width: 260px;
            background-color: var(--sidebar-bg);
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            border-right: 1px solid var(--border-color);
            z-index: 1000;
            transition: transform 0.3s ease;
        }

        .sidebar-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 2.5rem;
        }
        .sidebar-header img {
            height: 40px;
            width: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        .sidebar-header .user-info h4 {
            font-size: 1rem;
            font-weight: 600;
            color: var(--primary-text);
        }
        .sidebar-header .user-info span {
            font-size: 0.875rem;
            color: var(--secondary-text);
        }

        .sidebar-nav {
            flex-grow: 1;
        }
        .sidebar-nav ul {
            list-style: none;
        }
        .sidebar-nav a {
            position: relative;
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.9rem 1.2rem;
            margin-bottom: 0.5rem;
            border-radius: var(--rounded-lg);
            text-decoration: none;
            color: var(--secondary-text);
            font-weight: 500;
            transition: var(--transition);
        }
        .sidebar-nav a i {
            width: 20px;
            text-align: center;
            font-size: 1.1rem;
        }
        .sidebar-nav a:hover {
            background-color: var(--bg-color);
            color: var(--accent-purple);
        }
        .sidebar-nav a.active {
            background-color: var(--accent-purple);
            color: #ffffff;
            transform: translateX(5px);
            box-shadow: 0 4px 10px -2px rgba(124, 58, 237, 0.4);
        }
        .sidebar-nav .nav-section-title {
            padding: 0 1.2rem;
            margin: 1.5rem 0 0.5rem 0;
            font-size: 0.75rem;
            color: var(--secondary-text);
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        
        .logout-btn {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.9rem 1.2rem;
            border-radius: var(--rounded-lg);
            text-decoration: none;
            color: var(--secondary-text);
            font-weight: 500;
            transition: var(--transition);
            margin-top: 1rem;
            background-color: #f3f4f6;
        }
        .logout-btn:hover {
            background-color: var(--accent-pink);
            color: #fff;
        }

        /* --- MAIN CONTENT & SHARED ELEMENTS --- */
        .main-content {
            flex: 1;
            padding: 2.5rem;
            overflow-y: auto;
        }
        .page-title {
             margin-bottom: 1.5rem;
             font-weight: 700;
             color: var(--primary-text);
             font-size: 1.8rem;
        }
        .card {
            background: var(--widget-bg);
            border-radius: var(--rounded-xl);
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
            overflow: hidden;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 0.6rem 1.2rem;
            border-radius: 8px;
            font-weight: 600;
            transition: var(--transition);
            text-decoration: none;
            border: none;
            cursor: pointer;
        }
        .btn-primary {
            background-color: var(--accent-purple);
            color: white;
        }
        .btn-primary:hover {
            background-color: #6d28d9;
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        /* Mobile Menu Toggle */
        .menu-toggle {
            display: none; /* Hidden by default */
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1001;
            background: #fff;
            border: 1px solid var(--border-color);
            width: 45px;
            height: 45px;
            border-radius: 8px;
            font-size: 1.2rem;
            cursor: pointer;
            box-shadow: var(--shadow);
        }

        /* Responsive Design */
        @media (max-width: 992px) {
            .sidebar {
                position: fixed;
                left: 0;
                transform: translateX(-100%);
                box-shadow: 0 0 60px rgba(0,0,0,0.2);
            }
            .sidebar.active {
                transform: translateX(0);
            }
            .menu-toggle {
                display: grid;
                place-items: center;
            }
        }
    </style>
</head>
<body>
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <!-- You can make the image src dynamic later -->
            <img src="../images/logo.jpeg" alt="User Avatar">
            <div class="user-info">
                <!-- You can fetch the student's name from session -->
                <h4><?php echo htmlspecialchars($_SESSION['username'] ?? 'Student'); ?></h4>
                <span>Student Portal</span>
            </div>
        </div>

        <nav class="sidebar-nav">
            <ul>
                <!-- This PHP makes the 'active' class dynamic -->
                <li><a href="student_home.php" class="<?= $currentPage == 'student_home.php' ? 'active' : '' ?>"><i class="fas fa-tachometer-alt fa-fw"></i><span>Dashboard</span></a></li>
                <li><a href="student_profile.php" class="<?= $currentPage == 'student_profile.php' ? 'active' : '' ?>"><i class="fas fa-user-circle fa-fw"></i><span>My Profile</span></a></li>
                
                <li><div class="nav-section-title">Academics</div></li>
                
                <li><a href="view_assignment.php" class="<?= ($currentPage == 'view_assignment.php' || $currentPage == 'submit_assignment.php') ? 'active' : '' ?>"><i class="fas fa-tasks fa-fw"></i><span>Assignments</span></a></li>
                <li><a href="student_results.php" class="<?= $currentPage == 'student_results.php' ? 'active' : '' ?>"><i class="fas fa-poll fa-fw"></i><span>My Results</span></a></li>
                <li><a href="view_resources.php" class="<?= $currentPage == 'view_resources.php' ? 'active' : '' ?>"><i class="fas fa-folder-open fa-fw"></i><span>Resources</span></a></li>

                <li><div class="nav-section-title">Campus Life</div></li>

                <li><a href="view_notices.php" class="<?= $currentPage == 'view_notices.php' ? 'active' : '' ?>"><i class="fas fa-bullhorn fa-fw"></i><span>Notices</span></a></li>
            </ul>
        </nav>
        
        <a href="../logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt fa-fw"></i>
            <span>Logout</span>
        </a>
    </aside>

    <!-- Mobile Menu Button -->
    <button class="menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>

    <main class="main-content">
        <!-- The content of each specific page will start here -->
