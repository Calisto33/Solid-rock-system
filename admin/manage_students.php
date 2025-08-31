<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        :root {
            --primary-color: #2563eb;
            --primary-light: #3b82f6;
            --primary-dark: #1d4ed8;
            --accent-color: #f59e0b;
            --accent-light: #fbbf24;
            --text-dark: #1e293b;
            --text-light: #64748b;
            --text-white: #f8fafc;
            --bg-light: #f1f5f9;
            --bg-white: #ffffff;
            --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --radius-sm: 0.25rem;
            --radius-md: 0.5rem;
            --radius-lg: 0.75rem;
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', 'Roboto', sans-serif;
            background: var(--bg-light);
            color: var(--text-dark);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            line-height: 1.6;
        }

        /* Header Styles */
        .header {
            background-color: var(--bg-white);
            color: var(--text-dark);
            box-shadow: var(--shadow-md);
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1.5rem;
        }

        .header-logo {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .header-logo img {
            height: 40px;
            width: auto;
            border-radius: var(--radius-sm);
        }

        .header-logo h1 {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--primary-color);
        }

        .header-nav {
            display: flex;
            align-items: center;
        }

        .nav-link {
            color: var(--text-dark);
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: var(--radius-md);
            font-weight: 500;
            transition: var(--transition);
        }

        .nav-link:hover {
            background-color: var(--bg-light);
            color: var(--primary-color);
        }

        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            color: var(--text-dark);
            font-size: 1.5rem;
            cursor: pointer;
        }

        /* Main Content Styles */
        .main-container {
            max-width: 1200px;
            margin: 115px;
            padding: 0 15px;
            flex: 1;
        }

        .page-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            color: var(--text-dark);
            text-align: center;
        }

        /* Notification Styles */
        .notification {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background-color: var(--accent-light);
            color: var(--text-dark);
            padding: 1rem 1.5rem;
            border-radius: var(--radius-md);
            margin-bottom: 2rem;
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
            transform: translateY(-10px);
            opacity: 0;
        }

        .notification.show {
            transform: translateY(0);
            opacity: 1;
        }

        .notification i {
            margin-right: 0.5rem;
            color: var(--accent-color);
        }

        .notification-close {
            background: none;
            border: none;
            color: var(--text-dark);
            cursor: pointer;
            font-size: 1.25rem;
        }

        /* Dashboard Grid Styles */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            /* align-items: stretch; (Default, so cards in a row should be same height) */
        }

        .dashboard-card {
            background-color: var(--bg-white);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            padding: 1.5rem;
            transition: var(--transition);
            text-decoration: none;
            color: var(--text-dark);
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .dashboard-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background-color: var(--primary-color);
            transition: var(--transition);
        }

        .dashboard-card:hover::before {
            height: 8px;
        }

        .card-icon {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }

        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .card-description {
            color: var(--text-light);
            font-size: 0.9rem;
            flex-grow: 1; /* Helps with vertical alignment if descriptions vary */
        }

        /* Footer Styles */
        .footer {
            background-color: var(--text-dark);
            color: var(--text-white);
            padding: 2rem 0;
            margin-top: auto;
        }

        .footer-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .footer-logo {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .footer-logo img {
            height: 40px;
            width: auto;
            border-radius: var(--radius-sm);
        }

        .footer-info {
            text-align: right;
        }

        .footer-info p {
            margin: 0;
        }

        /* Responsive Design */
        @media screen and (max-width: 768px) {
            .header-container {
                flex-direction: column;
                align-items: flex-start;
                padding: 1rem;
            }

            .header-nav {
                display: none;
                width: 100%;
                flex-direction: column;
                margin-top: 1rem;
            }

            .header-nav.active {
                display: flex;
            }

            .nav-link {
                padding: 0.75rem 0;
                width: 100%;
                text-align: center;
            }

            .mobile-menu-btn {
                display: block;
                position: absolute;
                top: 1.5rem;
                right: 1.5rem;
            }

            .footer-container {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .footer-logo {
                justify-content: center;
            }

            .footer-info {
                text-align: center;
            }
        }

        @media screen and (max-width: 480px) {
            .page-title {
                font-size: 1.5rem;
            }

            .dashboard-grid {
                grid-template-columns: 1fr; /* Ensures 1 card per row on small screens */
            }

            .notification {
                padding: 0.75rem;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-container">
            <div class="header-logo">
                <img src="../images/logo.jpg" alt="Wisetech Logo"> <h1>Student Management</h1>
            </div>
            <button class="mobile-menu-btn" id="mobile-menu-toggle">
                <i class="fas fa-bars"></i>
            </button>
            <nav class="header-nav" id="header-nav">
                <a href="admin_home.php" class="nav-link active"> <i class="fas fa-home"></i> Dashboard
                </a>
                </nav>
        </div>
    </header>

    <main class="main-container">
        <h2 class="page-title">Admin Dashboard</h2>
        
        <div id="notification" class="notification">
            <div>
                <i class="fas fa-exclamation-circle"></i>
                <span>New student registration pending approval</span> </div>
            <button class="notification-close" id="close-notification">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="dashboard-grid">
            <!-- <a href="admin_attendance.php" class="dashboard-card">
                <div class="card-icon">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <h3 class="card-title">Student Attendance</h3>
                <p class="card-description">Track and manage daily student attendance records</p>
            </a> -->
            
            <a href="add_remove_subjects.php" class="dashboard-card">
                <div class="card-icon">
                    <i class="fas fa-book-open"></i>
                </div>
                <h3 class="card-title">Assign Class and Subjects</h3>
                <p class="card-description">Assign students subject for current Term</p>
            </a>
            
            <a href="add_subject.php" class="dashboard-card">
                <div class="card-icon">
                    <i class="fas fa-plus-circle"></i>
                </div>
                <h3 class="card-title">Add Subject</h3>
                <p class="card-description">Create new subjects in the academic curriculum</p>
            </a>
            
            <a href="student_information.php" class="dashboard-card"> <div class="card-icon">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <h3 class="card-title">Student Details</h3>
                <p class="card-description">Access detailed student profiles and academic history</p>
            </a>
            
            <a href="update_student_profile.php" class="dashboard-card">
                <div class="card-icon">
                    <i class="fas fa-user-edit"></i>
                </div>
                <h3 class="card-title">Update Student Profile</h3>
                <p class="card-description">Edit student details and contact information</p>
            </a>
        </div>
    </main>

    <script>
        // Set current year in footer
        document.getElementById('current-year').textContent = new Date().getFullYear();
        
        // Mobile menu toggle
        const mobileMenuBtn = document.getElementById('mobile-menu-toggle');
        const headerNav = document.getElementById('header-nav');
        
        mobileMenuBtn.addEventListener('click', () => {
            headerNav.classList.toggle('active');
            const icon = mobileMenuBtn.querySelector('i');
            if (headerNav.classList.contains('active')) {
                icon.classList.remove('fa-bars');
                icon.classList.add('fa-times');
            } else {
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
            }
        });
        
        // Notification handling
        const notification = document.getElementById('notification');
        const closeNotificationBtn = document.getElementById('close-notification');
        
        // Check if there are new registrations
        const hasNewRegistrations = true; // This should be fetched from the database
        
        if (hasNewRegistrations && notification) {
            setTimeout(() => {
                notification.classList.add('show');
            }, 300);
        } else if(notification) {
            notification.style.display = 'none';
        }
        
        if(closeNotificationBtn) {
            closeNotificationBtn.addEventListener('click', () => {
                if(notification) {
                    notification.classList.remove('show');
                    setTimeout(() => {
                        notification.style.display = 'none';
                    }, 300);
                }
            });
        }
    </script>
</body>
</html>