<?php

session_start();
include '../config.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    header("Location: ../login.php");
    exit();
}

// Set dynamic variables for the page
$page_title = $page_title ?? "Admin Portal";
$active_page = $active_page ?? '';

// Check if current user is a super admin based on email
$is_super_admin = false;
if (isset($_SESSION['email'])) {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM super_admins WHERE email = ? AND status = 'active'");
    $stmt->bind_param("s", $_SESSION['email']);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_row();
    $is_super_admin = $row[0] > 0;
}

// --- NEW: LOGIC FOR DYNAMIC PROFILE PICTURE ---
// Set the path to your default avatar
$default_avatar = '../images/logo.jpeg';

// Check if a profile image is set in the session and is not empty
if (!empty($_SESSION['profile_image'])) {
    // IMPORTANT: Assuming user-uploaded images are in a folder like '/uploads/'
    // You can change this path if needed.
    $avatar_path = '../uploads/' . htmlspecialchars($_SESSION['profile_image']);
} else {
    $avatar_path = $default_avatar;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - Solid Rock </title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/jpeg" href="../images/logo.jpeg">
    <link rel="shortcut icon" type="image/jpeg" href="../images/logo.jpeg">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        /* Updated color scheme to match Solid Rock  Rock branding */
        :root {
            --primary-bg: #f8fafc;
            --sidebar-bg: #FFFFFF;
            --card-bg: #FFFFFF;
            --primary-color: #003366; /* Deep blue from your brand */
            --secondary-color: #d12c2c; /* Red from your brand */
            --accent-color: #f0f4f8; /* Light blue accent */
            --text-dark: #2d3748;
            --text-light: #718096;
            --border-color: #e2e8f0;
            --success: #48bb78;
            --warning: #ed8936;
            --error: #f56565;
            --shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            --border-radius: 12px;
            --sidebar-width: 260px;
            --primary-light-bg: rgba(0, 51, 102, 0.1);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background-color: var(--primary-bg);
            color: var(--text-dark);
            display: flex;
            line-height: 1.6;
        }
        
        /* Sidebar Styles */
        .sidebar {
            width: var(--sidebar-width);
            background-color: var(--sidebar-bg);
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            border-right: 1px solid var(--border-color);
            z-index: 1000;
            transition: transform 0.3s ease-in-out;
            display: flex;
            flex-direction: column;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.05);
        }

        .main-container {
            flex-grow: 1;
            margin-left: var(--sidebar-width);
            padding: 1.5rem;
            transition: margin-left 0.3s ease-in-out;
            min-height: 100vh;
        }

        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-color);
            background: linear-gradient(135deg, var(--primary-color), #004080);
            color: white;
            text-align: center;
        }

        .sidebar-logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            font-size: 1.3rem;
            font-weight: 700;
            color: white;
        }

        .sidebar-logo img {
            width: 35px;
            height: 35px;
            border-radius: 6px;
            object-fit: cover;
        }

        .sidebar-menu {
            list-style: none;
            padding-top: 1rem;
            overflow-y: auto;
            flex-grow: 1;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 1.5rem;
            text-decoration: none;
            color: var(--text-light);
            font-weight: 500;
            border-right: 3px solid transparent;
            transition: var(--transition);
            border-radius: 0 8px 8px 0;
            margin: 2px 0;
            margin-right: 8px;
        }

        .sidebar-menu a.active,
        .sidebar-menu a:hover {
            background-color: var(--primary-light-bg);
            color: var(--primary-color);
            border-right-color: var(--primary-color);
            transform: translateX(4px);
        }

        .sidebar-menu i {
            width: 20px;
            text-align: center;
            font-size: 1.1rem;
        }

        /* Super Admin Button */
        .super-admin-section {
            padding: 1rem 1.5rem;
            border-top: 1px solid var(--border-color);
            border-bottom: 1px solid var(--border-color);
            background: linear-gradient(135deg, var(--secondary-color), #b91c1c);
        }

        .super-admin-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            padding: 0.875rem;
            width: 100%;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            text-decoration: none;
            border-radius: var(--border-radius);
            font-weight: 600;
            font-size: 0.95rem;
            transition: var(--transition);
            cursor: pointer;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .super-admin-btn:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
        }

        .super-admin-btn i {
            width: 16px;
            text-align: center;
            font-size: 1rem;
        }

        /* Logout button in sidebar */
        .sidebar-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid var(--border-color);
            margin-top: auto;
        }
        
        .logout-btn {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.875rem 1rem;
            width: 100%;
            background-color: transparent;
            border: 1px solid var(--error);
            color: var(--error);
            text-decoration: none;
            border-radius: var(--border-radius);
            font-weight: 500;
            transition: var(--transition);
            cursor: pointer;
        }
        
        .logout-btn:hover {
            background-color: var(--error);
            color: white;
            transform: translateY(-2px);
        }
        
        .logout-btn i {
            width: 18px;
            text-align: center;
        }
        
        /* Page Header */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
        }

        .page-header h1 {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary-color);
            margin: 0;
        }

        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--primary-color);
            padding: 0.5rem;
            border-radius: 8px;
            transition: var(--transition);
        }

        .mobile-menu-btn:hover {
            background-color: var(--primary-light-bg);
        }
        
        /* User profile section */
        .user-profile { 
            display: flex; 
            align-items: center; 
            gap: 1rem; 
            position: relative;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .user-profile img { 
            width: 40px; 
            height: 40px; 
            border-radius: 50%; 
            object-fit: cover; 
            border: 2px solid var(--border-color);
        }
        
        .user-name {
            font-weight: 600;
            color: var(--text-dark);
        }
        
        /* Dropdown for user profile */
        .profile-dropdown {
            position: relative;
            display: inline-block;
        }
        
        .profile-toggle {
            background: none;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.5rem;
            border-radius: var(--border-radius);
            transition: var(--transition);
        }
        
        .profile-toggle:hover {
            background-color: var(--primary-light-bg);
        }
        
        .profile-menu {
            position: absolute;
            top: 100%;
            right: 0;
            background: white;
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            min-width: 200px;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: var(--transition);
        }
        
        .profile-menu.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        
        .profile-menu a {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.875rem 1rem;
            color: var(--text-dark);
            text-decoration: none;
            transition: var(--transition);
            font-weight: 500;
        }
        
        .profile-menu a:hover {
            background-color: var(--primary-light-bg);
            color: var(--primary-color);
        }
        
        .profile-menu a.logout-option {
            color: var(--error);
            border-top: 1px solid var(--border-color);
        }
        
        .profile-menu a.logout-option:hover {
            background-color: rgba(245, 101, 101, 0.1);
        }

        /* Role badge */
        .role-badge {
            background: var(--primary-color);
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .role-badge.super {
            background: linear-gradient(135deg, var(--secondary-color), #b91c1c);
        }

        /* Responsive Styles */
        @media (max-width: 1024px) {
            .sidebar {
                width: 240px;
            }
            --sidebar-width: 240px;
        }

        @media (max-width: 991px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.is-open {
                transform: translateX(0);
            }
            
            .main-container {
                margin-left: 0;
            }
            
            .mobile-menu-btn {
                display: block;
            }
            
            .user-profile {
                gap: 0.5rem;
            }
            
            .user-name {
                display: none;
            }
        }
        
        @media (max-width: 576px) {
            .page-header {
                flex-wrap: wrap;
                gap: 1rem;
                padding: 1rem;
            }
            
            .page-header h1 {
                font-size: 1.4rem;
            }

            .main-container {
                padding: 1rem;
            }
        }

        /* Loading animation */
        .loading {
            opacity: 0.7;
            pointer-events: none;
        }

        /* Notification styles */
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 1rem 1.5rem;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            border-left: 4px solid var(--success);
            z-index: 9999;
            transform: translateX(100%);
            transition: transform 0.3s ease;
        }

        .notification.show {
            transform: translateX(0);
        }

        .notification.error {
            border-left-color: var(--error);
        }
    </style>
</head>
<body>
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <img src="../images/logo.jpeg" alt="Logo" onerror="this.onerror=null;this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzUiIGhlaWdodD0iMzUiIHZpZXdCb3g9IjAgMCAzNSAzNSIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjM1IiBoZWlnaHQ9IjM1IiByeD0iNiIgZmlsbD0iIzAwMzM2NiIvPgo8dGV4dCB4PSIxNy41IiB5PSIyMiIgZm9udC1mYW1pbHk9IkFyaWFsIiBmb250LXNpemU9IjE0IiBmb250LXdlaWdodD0iYm9sZCIgZmlsbD0id2hpdGUiIHRleHQtYW5jaG9yPSJtaWRkbGUiPlM8L3RleHQ+Cjwvc3ZnPg==';">
                Solid Rock 
            </div>
        </div>
        
        <nav class="sidebar-menu">
            <a href="admin_home.php" class="<?= ($active_page == 'dashboard') ? 'active' : '' ?>">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a href="add_remove_subjects.php" class="<?= ($active_page == 'students') ? 'active' : '' ?>">
                <i class="fas fa-user-graduate"></i> Manage Students
            </a>
            <a href="admin_view_attendance_log.php" class="<?= ($active_page == 'attendance') ? 'active' : '' ?>">
                <i class="fas fa-calendar-check"></i> Check Attendance
            </a>
            <a href="manage_staff.php" class="<?= ($active_page == 'staff') ? 'active' : '' ?>">
                <i class="fas fa-chalkboard-teacher"></i> Manage Staff
            </a>
            <a href="parents.php" class="<?= ($active_page == 'parents') ? 'active' : '' ?>">
                <i class="fas fa-users"></i> Manage Parents
            </a>
            <a href="../register.php" class="<?= ($active_page == 'add_user') ? 'active' : '' ?>">
                <i class="fas fa-user-plus"></i> Add New User
            </a>
            <a href="post_news.php" class="<?= ($active_page == 'news') ? 'active' : '' ?>">
                <i class="fas fa-newspaper"></i> Post News
            </a>
            <a href="admin_add_event.php" class="<?= ($active_page == 'events') ? 'active' : '' ?>">
                <i class="fas fa-calendar-alt"></i> Post Events
            </a>
            <a href="approve_users.php" class="<?= ($active_page == 'approve') ? 'active' : '' ?>">
                <i class="fas fa-user-check"></i> Approve Users
            </a>
            <a href="add_games.php" class="<?= ($active_page == 'games') ? 'active' : '' ?>">
                <i class="fas fa-gamepad"></i> Add Games
            </a>
        </nav>

        <!-- Super Admin Section (only show if user's email is in super_admins table) -->
        <?php if ($is_super_admin): ?>
        <div class="super-admin-section">
            <a href="../super_admin_login.php" class="super-admin-btn">
                <i class="fas fa-crown"></i>
                Super Admin
            </a>
        </div>
        <?php endif; ?>
        
        <!-- Logout button in sidebar footer -->
        <div class="sidebar-footer">
            <a href="../logout.php" class="logout-btn" onclick="return confirmLogout()">
                <i class="fas fa-sign-out-alt"></i>
                Logout
            </a>
        </div>
    </aside>

    <div class="main-container">
        <header class="page-header">
            <div style="display: flex; align-items: center; gap: 1rem;">
                <button class="mobile-menu-btn" id="mobileMenuBtn">
                    <i class="fas fa-bars"></i>
                </button>
                <h1><?= htmlspecialchars($page_title) ?></h1>
            </div>
            
            <div class="user-profile">
                <div class="profile-dropdown">
                    <button class="profile-toggle" id="profileToggle">
                        <div style="text-align: right;">
                            <div class="user-name"><?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?></div>
                            <div class="role-badge <?= $is_super_admin ? 'super' : '' ?>">
                                <?= $is_super_admin ? 'Super Admin' : htmlspecialchars(ucfirst($_SESSION['role'] ?? 'Admin')) ?>
                            </div>
                        </div>
                        <img src="<?= $avatar_path ?>" alt="User Avatar" onerror="this.onerror=null;this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHZpZXdCb3g9IjAgMCA0MCA0MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPGNpcmNsZSBjeD0iMjAiIGN5PSIyMCIgcj0iMjAiIGZpbGw9IiMwMDMzNjYiLz4KPHN2ZyB4PSI4IiB5PSI4IiB3aWR0aD0iMjQiIGhlaWdodD0iMjQiIGZpbGw9IndoaXRlIj4KPHA+VXNlcjwvcD4KPC9zdmc+Cjwvc3ZnPg==';">
                        <i class="fas fa-chevron-down" style="font-size: 0.8rem; color: var(--text-light);"></i>
                    </button>
                    
                    <div class="profile-menu" id="profileMenu">
                        <a href="#">
                            <i class="fas fa-user"></i>
                            My Profile
                        </a>
                        <?php if ($is_super_admin): ?>
                        <a href="#">
                            <i class="fas fa-server"></i>
                            System Settings
                        </a>
                        <?php endif; ?>
                        <a href="../logout.php" class="logout-option" onclick="return confirmLogout()">
                            <i class="fas fa-sign-out-alt"></i>
                            Logout
                        </a>
                    </div>
                </div>
            </div>
        </header>
        
        <script>
            // Mobile menu toggle
            document.getElementById('mobileMenuBtn').addEventListener('click', function() {
                document.getElementById('sidebar').classList.toggle('is-open');
            });
            
            // Profile dropdown toggle
            document.getElementById('profileToggle').addEventListener('click', function(e) {
                e.stopPropagation();
                document.getElementById('profileMenu').classList.toggle('show');
            });
            
            // Close dropdown when clicking outside
            document.addEventListener('click', function(e) {
                const profileMenu = document.getElementById('profileMenu');
                const profileToggle = document.getElementById('profileToggle');
                
                if (!profileToggle.contains(e.target)) {
                    profileMenu.classList.remove('show');
                }
            });
            
            // Close sidebar when clicking outside on mobile
            document.addEventListener('click', function(e) {
                const sidebar = document.getElementById('sidebar');
                const mobileMenuBtn = document.getElementById('mobileMenuBtn');
                
                if (window.innerWidth <= 991 && 
                    !sidebar.contains(e.target) && 
                    !mobileMenuBtn.contains(e.target) && 
                    sidebar.classList.contains('is-open')) {
                    sidebar.classList.remove('is-open');
                }
            });
            
            // Logout confirmation
            function confirmLogout() {
                return confirm('Are you sure you want to logout?');
            }
            
            // Handle window resize
            window.addEventListener('resize', function() {
                if (window.innerWidth > 991) {
                    document.getElementById('sidebar').classList.remove('is-open');
                }
            });

            // Show notification function
            function showNotification(message, type = 'success') {
                const notification = document.createElement('div');
                notification.className = `notification ${type}`;
                notification.innerHTML = `
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
                        <span>${message}</span>
                    </div>
                `;
                
                document.body.appendChild(notification);
                
                setTimeout(() => notification.classList.add('show'), 100);
                setTimeout(() => {
                    notification.classList.remove('show');
                    setTimeout(() => notification.remove(), 300);
                }, 3000);
            }
        </script>