<?php
// This is includes/sidebar.php

// Get the current page name
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar">
    <div class="sidebar-header">
        <img src="../images/logo.jpg" alt="School Logo">
        <h1>Parent Portal</h1>
    </div>
    <nav class="sidebar-nav">
        <h3>Menu</h3>
        <ul>
            <li><a href="parent_home.php" class="<?php echo ($currentPage == 'parent_home.php') ? 'active' : ''; ?>"><i class="fas fa-home"></i> Home</a></li>
            <!-- <li><a href="my_profile.php" class="<?php echo ($currentPage == 'my_profile.php') ? 'active' : ''; ?>"><i class="fas fa-user-circle"></i> My Profile</a></li> -->
            <li><a href="parent_results.php" class="<?php echo ($currentPage == 'parent_results.php') ? 'active' : ''; ?>"><i class="fas fa-poll"></i> Check Result</a></li>
            <!-- <li><a href="#" class=""><i class="fas fa-history"></i> Registration History</a></li> -->
        </ul>
        
        <h3>School</h3>
        <ul>
            <li><a href="student_details.php"><i class="fas fa-user-graduate"></i> Student Details</a></li>
            <li><a href="teachers_profiles.php"><i class="fas fa-chalkboard-teacher"></i> Teachers</a></li>
            <li><a href="parent_notices.php"><i class="fas fa-bullhorn"></i> Notices</a></li>
            <li><a href="parent_events.php"><i class="fas fa-calendar-alt"></i> Events</a></li>
            <li><a href="parent_feedback.php"><i class="fas fa-comments"></i> Feedback</a></li>
        </ul>

        <h3>Finances</h3>
        <ul>
            <li><a href="parent_fees.php" class="<?php echo ($currentPage == 'parent_fees.php') ? 'active' : ''; ?>"><i class="fas fa-money-bill-wave"></i> My Fees</a></li>
            <!-- <li><a href="#"><i class="fas fa-history"></i> Payment History</a></li>
            <li><a href="#"><i class="fas fa-cogs"></i> Registration</a></li> -->
        </ul>
    </nav>
    <div class="sidebar-footer">
        <a href="../logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i>Log Out</a>
    </div>
</aside>