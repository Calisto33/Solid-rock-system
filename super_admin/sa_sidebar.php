<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <i class="fas fa-graduation-cap logo-icon"></i> <span class="brand-name">Solid Rock</span>
    </div>

    <div class="main-menu-title">Main Menu</div>
    <ul class="nav-menu">
        <?php
        // The $currentPage variable should be defined on each page before including the header
        // For example, on dashboard.php, you'd set: $currentPage = 'overview';
        if (!isset($currentPage)) {
            $currentPage = ''; // Default to empty if not set
        }
        ?>
        <li class="nav-item">
            <a href="super_admin_dashboard.php" class="nav-link <?= ($currentPage === 'overview') ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt"></i>
                <span>Overview</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="fees.php" class="nav-link <?= ($currentPage === 'fees') ? 'active' : ''; ?>">
                <i class="fas fa-file-invoice-dollar"></i>
                <span>Fees Mgt.</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="manage_staff.php" class="nav-link <?= ($currentPage === 'staff') ? 'active' : ''; ?>">
                <i class="fas fa-user-tie"></i>
                <span>Manage Staff</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="student_records.php" class="nav-link <?= ($currentPage === 'records') ? 'active' : ''; ?>">
                <i class="fas fa-address-book"></i>
                <span>Student Records</span>
            </a>
        </li>
         <li class="nav-item">
            <a href="manage_students.php" class="nav-link <?= ($currentPage === 'students') ? 'active' : ''; ?>">
                <i class="fas fa-users-cog"></i>
                <span>Manage Students</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="manage_admins.php" class="nav-link <?= ($currentPage === 'admins') ? 'active' : ''; ?>">
                <i class="fas fa-user-shield"></i>
                <span>Manage Admins</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="manage_parents.php" class="nav-link <?= ($currentPage === 'parents') ? 'active' : ''; ?>">
                <i class="fas fa-user-friends"></i>
                <span>Manage Parents</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="manage_super_admins.php" class="nav-link <?= ($currentPage === 'super_admins') ? 'active' : ''; ?>">
                <i class="fas fa-user-cog"></i> <span>Manage Super Admins</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="system_analysis.php" class="nav-link <?= ($currentPage === 'analysis') ? 'active' : ''; ?>">
                <i class="fas fa-chart-pie"></i> <span>System Analysis</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="../logout.php" class="nav-link"> <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </li>
    </ul>
</aside>