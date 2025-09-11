<?php
    // This is header.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parent Portal</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/jpeg" href="../images/logo.jpeg">
    <style>
        /* --- Base Variables & Styles --- */
        :root {
            --primary-color: #3b82f6;
            --background-light: #f8f9fa;
            --background-white: #ffffff;
            --text-dark: #212529;
            --text-muted: #6c757d;
            --border-color: #dee2e6;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--background-light);
            color: var(--text-dark);
            display: flex;
            height: 100vh;
            overflow: hidden;
        }

        .dashboard-wrapper { display: flex; width: 100%; height: 100%; }

        /* --- Sidebar (Desktop First) --- */
        .sidebar {
            width: 260px;
            background-color: var(--background-white);
            border-right: 1px solid var(--border-color);
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            transition: transform 0.3s ease-in-out; /* Added for smooth transition */
            z-index: 1000;
        }
        /* Sidebar content styles remain the same... */
        .sidebar-header { display: flex; align-items: center; gap: 0.75rem; margin-bottom: 2.5rem; }
        .sidebar-header img { width: 40px; height: 40px; }
        .sidebar-header h1 { font-size: 1.25rem; font-weight: 700; }
        .sidebar-nav { flex-grow: 1; }
        .sidebar-nav h3 { font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; margin: 1.5rem 0 0.5rem; }
        .sidebar-nav ul { list-style: none; }
        .sidebar-nav a { display: flex; align-items: center; padding: 0.8rem; border-radius: 0.5rem; text-decoration: none; color: var(--text-muted); font-weight: 500; }
        .sidebar-nav a:hover { background-color: var(--background-light); }
        .sidebar-nav a.active { background-color: var(--primary-color); color: white; }
        .sidebar-nav a i { font-size: 1.1rem; width: 24px; text-align: center; margin-right: 0.75rem; }
        .sidebar-footer { margin-top: auto; padding-top: 1.5rem; border-top: 1px solid var(--border-color); }


        /* --- Main Content --- */
        .main-content {
            flex: 1;
            padding: 2rem;
            overflow-y: auto;
            background-color: var(--background-white);
        }

        .main-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        .header-title h2 { font-weight: 700; font-size: 1.75rem; }
        .header-title p { color: var(--text-muted); }
        .header-actions { display: flex; align-items: center; gap: 1rem; }
        .user-profile { display: flex; align-items: center; gap: 1rem; }
        .user-profile .icon-button { background: none; border: none; font-size: 1.25rem; color: var(--text-muted); cursor: pointer; }

        /* NEW: Hamburger Menu Button */
        .menu-toggle {
            display: none; /* Hidden on desktop */
            background: none;
            border: none;
            font-size: 1.5rem;
            color: var(--text-dark);
            cursor: pointer;
        }

        /* NEW: Overlay for mobile view */
        .overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
        }
        .overlay.active { display: block; }


        /* --- Responsive Styles --- */

        /* Tablets and below (e.g., screen width <= 992px) */
        @media (max-width: 992px) {
            .sidebar {
                position: fixed;
                top: 0;
                left: 0;
                height: 100%;
                transform: translateX(-100%); /* Hide sidebar off-screen */
                box-shadow: 0 0 15px rgba(0,0,0,0.1);
            }
            .sidebar.active {
                transform: translateX(0); /* Show sidebar */
            }
            .main-header {
                justify-content: space-between;
            }
            .menu-toggle {
                display: block; /* Show hamburger menu */
            }
             .main-content {
                padding: 1.5rem;
            }
        }

        /* Mobile phones (e.g., screen width <= 576px) */
        @media (max-width: 576px) {
            .main-content {
                padding: 1rem;
            }
            .header-title h2 {
                font-size: 1.3rem;
            }
            .header-actions {
                /* You might want to hide or shrink some actions on very small screens */
            }
        }

        /* All other existing styles from your header file... */

    </style>
</head>
<body>
    <div class="dashboard-wrapper">
