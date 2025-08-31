<?php
// We removed session_start() from this file to prevent the error.
// It will be placed on the main page instead.

// The config include and auth check can stay if you want
// But it's often better to have it on the main page before the include.
// For now, let's keep the username part.
if (session_status() == PHP_SESSION_NONE) {
    // Fallback if session is not started on parent page
    session_start();
}
include_once '../config.php'; // Using include_once is safer

// Check if the user is logged in as a super admin
if (!isset($_SESSION['super_admin_id']) || !isset($_SESSION['username'])) {
    header("Location: super_admin_login.php");
    exit();
}

$super_admin_username = $_SESSION['username'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Super Admin'; ?> | Solid Rock</title>
     <!-- Favicon -->
    <link rel="icon" type="image/jpeg" href="../images/logo.jpeg">
    <link rel="shortcut icon" type="image/jpeg" href="../images/logo.jpeg">
    
    
    <link rel="stylesheet" href="styles.css">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="overlay" id="overlay"></div>
    
    <?php include 'sa_sidebar.php'; ?>

    <div class="main-wrapper">
        <header class="top-bar">
            <div style="display: flex; align-items: center;">
                <i class="fas fa-bars hamburger-menu" id="hamburgerMenu"></i>
                <div class="search-bar">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Search anything here...">
                </div>
            </div>
            <div class="top-bar-actions">
                <i class="fas fa-th action-icon" title="Customize Widget"></i>
                <i class="fas fa-bell action-icon" title="Notifications"></i>
                <div class="user-avatar" title="<?= htmlspecialchars($super_admin_username); ?>">
                    <?= strtoupper(substr(htmlspecialchars($super_admin_username), 0, 1)); ?>
                </div>
            </div>
        </header>
        <main class="content-area">