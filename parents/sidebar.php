<?php
// This is includes/sidebar.php
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar">
    <div class="sidebar-header">
        <img src="../images/logo.jpeg" alt="Solid Rock  Logo">
        <h1>Parent Portal</h1>
    </div>
    <nav class="sidebar-nav">
        <h3>Menu</h3>
        <ul>
            <li><a href="parent_home.php" class="<?= ($currentPage == 'parent_home.php') ? 'active' : '' ?>"><i class="fas fa-home"></i> Home</a></li>
            <li><a href="parent_results.php" class="<?= ($currentPage == 'parent_results.php') ? 'active' : '' ?>"><i class="fas fa-poll"></i> Check Result</a></li>
        </ul>

        <h3>School</h3>
        <ul>
            <li><a href="student_details.php" class="<?= ($currentPage == 'student_details.php') ? 'active' : '' ?>"><i class="fas fa-user-graduate"></i> Student Details</a></li>
            <li><a href="teachers_profiles.php" class="<?= ($currentPage == 'teachers_profiles.php') ? 'active' : '' ?>"><i class="fas fa-chalkboard-teacher"></i> Teachers</a></li>
            <li><a href="parent_notices.php" class="<?= ($currentPage == 'parent_notices.php') ? 'active' : '' ?>"><i class="fas fa-bullhorn"></i> Notices</a></li>
            <li><a href="parent_events.php" class="<?= ($currentPage == 'parent_events.php') ? 'active' : '' ?>"><i class="fas fa-calendar-alt"></i> Events</a></li>
            <li><a href="parent_feedback.php" class="<?= ($currentPage == 'parent_feedback.php') ? 'active' : '' ?>"><i class="fas fa-comments"></i> Feedback</a></li>
        </ul>

        <h3>Finances</h3>
        <ul>
            <li><a href="parent_fees.php" class="<?= ($currentPage == 'parent_fees.php') ? 'active' : '' ?>"><i class="fas fa-money-bill-wave"></i> My Fees</a></li>
        </ul>
    </nav>
    <div class="sidebar-footer">
        <a href="../logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i>Log Out</a>
    </div>
</aside>

<div class="overlay"></div>

<main class="main-content">
    <header class="main-header">
         <!-- Hamburger Menu Button -->
        <button class="menu-toggle" id="menu-toggle-btn">
            <i class="fas fa-bars"></i>
        </button>
        <div class="header-title">
            <!-- This part will be populated by each page -->
        </div>
        <div class="header-actions">
            <div class="user-profile">
                 <!-- User profile icons etc. -->
            </div>
        </div>
    </header>
