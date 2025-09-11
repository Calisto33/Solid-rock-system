<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <img src="https://i.pravatar.cc/40?u=daniel" alt="User Avatar">
        <div class="user-info">
            <span>Student</span>
        </div>
    </div>

    <nav class="sidebar-nav">
        <ul>
            <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a></li>
            <li><a href="student_profile.php"><i class="fas fa-user"></i><span>My Profile</span></a></li>
            
            <li style="height: 10px;"></li>
            <li style="padding: 0 1rem; font-size: 0.75rem; color: var(--secondary-text); text-transform: uppercase; font-weight: 700;">Academics</li>

            <li id="nav-assignments"><a href="view_assignment.php"><i class="fas fa-tasks"></i><span>Assignments</span><span class="notification-badge"></span></a></li>
            <li id="nav-results"><a href="student_results.php"><i class="fas fa-graduation-cap"></i><span>My Results</span><span class="notification-badge"></span></a></li>
            <li><a href="view_resources.php"><i class="fas fa-folder-open"></i><span>Resources</span></a></li>

            <li style="height: 10px;"></li>
            <li style="padding: 0 1rem; font-size: 0.75rem; color: var(--secondary-text); text-transform: uppercase; font-weight: 700;">Campus Life</li>

            <!-- <li id="nav-notices"><a href="view_notices.php"><i class="fas fa-bullhorn"></i><span>Notices</span><span class="notification-badge"></span></a></li> -->
            <li id="nav-news"><a href="../admin/view_news.php"><i class="fas fa-newspaper"></i><span>News</span><span class="notification-badge"></span></a></li>
            <li id="nav-events"><a href="student_events.php"><i class="fas fa-calendar-star"></i><span>Events</span><span class="notification-badge"></span></a></li>

            <li style="height: 10px;"></li>
            <li><a href="educational_games.php"><i class="fas fa-gamepad"></i><span>Edu-Games</span></a></li>
        </ul>
    </nav>
    
    <a href="../logout.php" class="logout-btn">
         <i class="fas fa-sign-out-alt"></i>
         <span>Logout</span>
    </a>
</aside>

<button class="menu-toggle" id="menuToggle" style="position: fixed; top: 20px; left: 20px; z-index: 101; background: #fff; border: 1px solid #eee; width:40px; height: 40px; border-radius: 8px; font-size: 1.2rem; cursor: pointer; display: none;">
    <i class="fas fa-bars"></i>
</button>