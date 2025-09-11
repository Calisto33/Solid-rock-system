<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - Solid Rock </title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Favicon -->
    <link rel="icon" type="image/jpeg" href="../images/logo.jpeg">
    <link rel="shortcut icon" type="image/jpeg" href="../images/logo.jpeg">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            --shadow-lg: 0 10px 25px -5px rgba(0, 0, 0, 0.07), 0 5px 10px -5px rgba(0, 0, 0, 0.04);
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
            overflow-x: hidden;
        }

        /* --- Sidebar Navigation --- */
        .sidebar {
            width: 260px;
            background-color: var(--sidebar-bg);
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            border-right: 1px solid var(--border-color);
            z-index: 100;
            transition: var(--transition);
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }

        .sidebar-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 2.5rem;
            flex-shrink: 0;
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
            line-height: 1.2;
        }
        
        .sidebar-header .user-info span { 
            font-size: 0.875rem; 
            color: var(--secondary-text); 
            line-height: 1.2;
        }
        
        .sidebar-nav ul { 
            list-style: none; 
            flex-grow: 1; 
        }

        .sidebar-nav a {
            position: relative;
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.8rem 1rem;
            border-radius: var(--rounded-lg);
            text-decoration: none;
            color: var(--secondary-text);
            font-weight: 500;
            transition: var(--transition);
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .sidebar-nav a i { 
            width: 20px; 
            text-align: center; 
            flex-shrink: 0;
        }

        .sidebar-nav a.active,
        .sidebar-nav a:hover {
            background-color: var(--accent-purple);
            color: #ffffff;
            transform: translateX(5px);
            box-shadow: 0 4px 10px -2px rgba(124, 58, 237, 0.4);
        }
        
        .notification-badge {
            position: absolute;
            top: 8px;
            right: 8px;
            background-color: var(--notification-red);
            color: #fff;
            min-width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            font-weight: 700;
            box-shadow: 0 0 0 2px var(--sidebar-bg);
            transform: scale(0);
            transition: transform 0.3s ease-in-out, opacity 0.3s ease;
            opacity: 0;
            padding: 0 4px;
        }

        .notification-badge.show {
            transform: scale(1);
            opacity: 1;
        }

        .notification-badge.pulse {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }

        .logout-btn {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.8rem 1rem;
            border-radius: var(--rounded-lg);
            text-decoration: none;
            color: var(--secondary-text);
            font-weight: 500;
            transition: var(--transition);
            margin-top: 1rem;
            background-color: #f3f4f6;
            flex-shrink: 0;
        }
        
        .logout-btn:hover { 
            background-color: var(--accent-pink); 
            color: #fff; 
        }

        /* --- Main Content Area --- */
        .main-content {
            flex: 1;
            padding: 2rem;
            overflow-y: auto;
            margin-left: 260px;
            transition: var(--transition);
            min-height: 100vh;
        }

        .main-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .main-header h1 { 
            font-size: 1.75rem; 
            font-weight: 700; 
        }
        
        .main-header p {
            color: var(--secondary-text);
            margin-top: 0.25rem;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            grid-template-areas:
                "passrate passrate"
                "events   news"
                "links    links";
        }

        .widget {
            background-color: var(--widget-bg);
            padding: 1.5rem;
            border-radius: var(--rounded-xl);
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
            transition: var(--transition);
            overflow: hidden;
        }
        
        .widget:hover { 
            transform: translateY(-3px); 
            box-shadow: var(--shadow-lg); 
        }
        
        .widget-header { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 1.5rem; 
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        
        .widget-title { 
            font-size: 1.1rem; 
            font-weight: 600; 
        }

        .widget.subject-passrate-graph { grid-area: passrate; }
        .widget.upcoming-events { grid-area: events; }
        .widget.latest-news { grid-area: news; }
        .widget.quick-links { grid-area: links; }

        .chart-container { 
            position: relative; 
            height: 280px; 
            width: 100%;
        }

        .list { 
            list-style: none; 
        }
        
        .list-item { 
            display: flex; 
            align-items: center; 
            margin-bottom: 1.25rem; 
        }
        
        .list-item:last-child { 
            margin-bottom: 0; 
        }
        
        .list-item .icon { 
            width: 38px; 
            height: 38px; 
            border-radius: 50%; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            margin-right: 1rem; 
            font-size: 1rem; 
            flex-shrink: 0;
        }
        
        .list-item .text h5 { 
            font-weight: 600; 
            font-size: 0.9rem; 
            line-height: 1.3;
        }
        
        .list-item .text span { 
            font-size: 0.8rem; 
            color: var(--secondary-text); 
            line-height: 1.3;
        }

        .list-item a {
            text-decoration: none;
            color: inherit;
            display: flex;
            align-items: center;
            width: 100%;
            transition: var(--transition);
            padding: 0.5rem;
            border-radius: 0.5rem;
            margin: -0.5rem;
        }

        .list-item a:hover {
            background-color: var(--bg-color);
        }

        /* Status indicator for connection */
        .connection-status {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 600;
            z-index: 1000;
            transition: var(--transition);
        }

        .connection-status.online {
            background-color: #10b981;
            color: white;
        }

        .connection-status.offline {
            background-color: #ef4444;
            color: white;
        }

        /* Menu Toggle Button */
        .menu-toggle {
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 101;
            background: #fff;
            border: 1px solid #eee;
            width: 40px;
            height: 40px;
            border-radius: 8px;
            font-size: 1.2rem;
            cursor: pointer;
            display: none;
            align-items: center;
            justify-content: center;
            box-shadow: var(--shadow);
            transition: var(--transition);
        }

        .menu-toggle:hover {
            background-color: var(--accent-purple);
            color: white;
        }

        /* Sidebar Overlay for Mobile */
        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 99;
            opacity: 0;
            visibility: hidden;
            transition: var(--transition);
        }

        .sidebar-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        
        /* Responsive Design */
        @media (max-width: 1200px) {
            .main-content {
                padding: 1.5rem;
            }
            
            .chart-container {
                height: 250px;
            }
        }
        
        @media (max-width: 992px) {
            .sidebar {
                position: fixed;
                left: -280px;
                width: 280px;
                transition: var(--transition);
            }
            
            .sidebar.active {
                left: 0;
                box-shadow: 0 0 60px rgba(0,0,0,0.25);
            }
            
            .main-content {
                margin-left: 0;
                padding: 1.5rem 1rem;
            }
            
            .menu-toggle {
                display: flex !important;
            }
            
            .dashboard-grid {
                gap: 1rem;
            }
            
            .widget {
                padding: 1.25rem;
            }
            
            .chart-container {
                height: 220px;
            }
        }
        
        @media (max-width: 768px) {
            .main-content {
                padding: 1rem 0.75rem;
            }
            
            .main-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
                margin-bottom: 1.5rem;
            }
            
            .main-header h1 {
                font-size: 1.5rem;
            }
            
            .dashboard-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
                grid-template-areas:
                    "passrate"
                    "events"
                    "news"
                    "links";
            }
            
            .widget {
                padding: 1rem;
            }
            
            .widget-title {
                font-size: 1rem;
            }
            
            .chart-container {
                height: 200px;
            }
            
            .list-item {
                margin-bottom: 1rem;
            }
            
            .list-item .icon {
                width: 32px;
                height: 32px;
                font-size: 0.9rem;
            }
            
            .list-item .text h5 {
                font-size: 0.85rem;
            }
            
            .list-item .text span {
                font-size: 0.75rem;
            }
            
            .sidebar {
                width: 100%;
                left: -100%;
            }
            
            .sidebar.active {
                left: 0;
            }
            
            .sidebar-nav a {
                padding: 1rem;
                font-size: 0.95rem;
            }
            
            .connection-status {
                top: 10px;
                right: 10px;
                font-size: 0.75rem;
                padding: 6px 10px;
            }
        }
        
        @media (max-width: 480px) {
            .main-content {
                padding: 0.75rem 0.5rem;
            }
            
            .main-header h1 {
                font-size: 1.25rem;
            }
            
            .main-header p {
                font-size: 0.9rem;
            }
            
            .widget {
                padding: 0.875rem;
            }
            
            .widget-title {
                font-size: 0.95rem;
            }
            
            .chart-container {
                height: 180px;
            }
            
            .sidebar-header {
                margin-bottom: 2rem;
            }
            
            .sidebar-header .user-info h4 {
                font-size: 0.95rem;
            }
            
            .sidebar-header .user-info span {
                font-size: 0.8rem;
            }
            
            .menu-toggle {
                top: 15px;
                left: 15px;
                width: 36px;
                height: 36px;
                font-size: 1.1rem;
            }
        }

        /* Touch-friendly improvements */
        @media (hover: none) and (pointer: coarse) {
            .sidebar-nav a {
                padding: 1rem;
                margin-bottom: 0.25rem;
            }
            
            .widget:hover {
                transform: none;
            }
            
            .list-item a:hover {
                background-color: transparent;
            }
        }

        /* High contrast mode support */
        @media (prefers-contrast: high) {
            :root {
                --border-color: #000;
                --shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
            }
            
            .widget {
                border: 2px solid var(--border-color);
            }
        }

        /* Reduced motion support */
        @media (prefers-reduced-motion: reduce) {
            * {
                transition: none !important;
                animation: none !important;
            }
            
            .notification-badge.pulse {
                animation: none;
            }
        }

        /* Print styles */
        @media print {
            .sidebar,
            .menu-toggle,
            .connection-status {
                display: none !important;
            }
            
            .main-content {
                margin-left: 0;
                padding: 0;
            }
            
            .widget {
                break-inside: avoid;
                box-shadow: none;
                border: 1px solid #ccc;
            }
            
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Landscape orientation on mobile */
        @media (max-width: 768px) and (orientation: landscape) {
            .sidebar {
                width: 280px;
            }
            
            .chart-container {
                height: 160px;
            }
        }

        /* Very small screens */
        @media (max-width: 320px) {
            .main-header h1 {
                font-size: 1.1rem;
            }
            
            .widget {
                padding: 0.75rem;
            }
            
            .chart-container {
                height: 150px;
            }
            
            .list-item .icon {
                width: 28px;
                height: 28px;
                font-size: 0.8rem;
            }
        }
    </style>
</head>
<body>
    
    <!-- Connection Status Indicator -->
    <div class="connection-status offline" id="connectionStatus">Connecting...</div>
    
    <!-- Sidebar Overlay for Mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <img src="../images/logo.jpeg" alt="User Avatar">
            <div class="user-info">
                <h4>Solid Rock  </h4>
                <span>Student</span>
            </div>
        </div>

        <nav class="sidebar-nav">
            <ul>
                <li><a href="#" class="active"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a></li>
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
    
    <button class="menu-toggle" id="menuToggle">
        <i class="fas fa-bars"></i>
    </button>

    <main class="main-content">
        <header class="main-header">
            <div>
                <h1>Dashboard</h1>
                <p>Welcome back! Here's your overview for today.</p>
            </div>
        </header>

        <div class="dashboard-grid">
            
            <div class="widget subject-passrate-graph">
                <div class="widget-header"><h3 class="widget-title">Pass Rate per Subject</h3></div>
                <div class="chart-container">
                    <canvas id="passRateChart"></canvas>
                </div>
            </div>

            <div class="widget upcoming-events">
                <div class="widget-header"><h3 class="widget-title">Upcoming Events</h3></div>
                <ul class="list" id="upcoming-events-list">
                </ul>
            </div>

            <div class="widget latest-news">
                <div class="widget-header"><h3 class="widget-title">Latest News</h3></div>
                <ul class="list" id="latest-news-list">
                </ul>
            </div>
            
            <div class="widget quick-links">
                <div class="widget-header"><h3 class="widget-title">Quick Links</h3></div>
                <ul class="list">
                    <li class="list-item">
                        <a href="view_assignment.php">
                            <div class="icon" style="background-color: #ecfeff; color: #0891b2;"><i class="fas fa-book"></i></div>
                            <div class="text"><h5>View Assignments</h5></div>
                        </a>
                    </li>
                    <li class="list-item">
                        <a href="view_notices.php">
                            <div class="icon" style="background-color: #fffbeb; color: #f59e0b;"><i class="fas fa-calendar-alt"></i></div>
                            <div class="text"><h5>Notices</h5></div>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </main>

    <script>
        // Configuration
        const CONFIG = {
            FETCH_INTERVAL: 60000,
            RETRY_DELAY: 10000,
            MAX_RETRIES: 5
        };

        // State management
        let lastBadgeCounts = {};
        let fetchIntervalId = null;
        let retryCount = 0;

        // --- NEW: ROBUST FETCH HELPER ---
        /**
         * A helper function to fetch data and handle JSON parsing errors gracefully.
         * @param {string} url The URL to fetch.
         * @returns {Promise<object>} A promise that resolves with the JSON data.
         */
        async function fetchJSON(url) {
            const response = await fetch(url);

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            // Try to parse as JSON, but if it fails, show the text content for debugging.
            const responseText = await response.text();
            try {
                return JSON.parse(responseText);
            } catch (error) {
                // The response was not valid JSON. Throw an error with the server's actual response.
                throw new Error(`Invalid JSON response from server: ${responseText}`);
            }
        }


        // --- 1. CHART RENDERING ---
        function renderPassRateChart(chartData) {
            const passRateCtx = document.getElementById('passRateChart').getContext('2d');
            new Chart(passRateCtx, {
                type: 'bar',
                data: {
                    labels: chartData.subjects,
                    datasets: [{
                        label: 'Pass Rate %',
                        data: chartData.rates,
                        backgroundColor: [
                            'rgba(59, 130, 246, 0.6)',
                            'rgba(236, 72, 153, 0.6)',
                            'rgba(34, 197, 94, 0.6)',
                            'rgba(124, 58, 237, 0.6)',
                            'rgba(245, 158, 11, 0.6)'
                        ],
                        borderColor: [
                            'rgba(59, 130, 246, 1)',
                            'rgba(236, 72, 153, 1)',
                            'rgba(34, 197, 94, 1)',
                            'rgba(124, 58, 237, 1)',
                            'rgba(245, 158, 11, 1)'
                        ],
                        borderWidth: 2,
                        borderRadius: 8,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { 
                            beginAtZero: true, 
                            max: 100, 
                            ticks: { 
                                callback: function(value) { return value + '%'; }, 
                                color: 'var(--secondary-text)' 
                            }
                        },
                        x: { ticks: { color: 'var(--secondary-text)' }}
                    }
                }
            });
        }

        // --- 2. CONNECTION STATUS MANAGEMENT ---
        function updateConnectionStatus(isOnline) {
            const statusEl = document.getElementById('connectionStatus');
            if (isOnline) {
                statusEl.textContent = 'Online';
                statusEl.className = 'connection-status online';
                setTimeout(() => statusEl.style.display = 'none', 2000);
            } else {
                statusEl.textContent = 'Connection Error';
                statusEl.className = 'connection-status offline';
                statusEl.style.display = 'block';
            }
        }

        // --- 3. DYNAMIC DATA FETCHING (UPDATED) ---
        async function fetchPassRateData() {
            const container = document.querySelector('.subject-passrate-graph .chart-container');
            try {
                const data = await fetchJSON('get_pass_rates.php');
                if (!data.success) {
                    throw new Error(data.details || 'No data received');
                }
                container.innerHTML = '<canvas id="passRateChart"></canvas>';
                renderPassRateChart(data);
                updateConnectionStatus(true);
            } catch (error) {
                console.error('Error fetching pass rate data:', error);
                container.innerHTML = `<p style="padding: 2rem; color: var(--notification-red); text-align: center; word-break: break-all;"><b>Chart Error:</b><br>${error.message}</p>`;
                updateConnectionStatus(false);
            }
        }

        async function fetchUpcomingEvents() {
            const list = document.getElementById('upcoming-events-list');
            try {
                const events = await fetchJSON('get_upcoming_events.php');
                list.innerHTML = '';
                if (events.length === 0) {
                    list.innerHTML = '<p style="color: var(--secondary-text); padding: 1rem;">No upcoming events.</p>';
                    return;
                }
                events.forEach(event => {
                    list.insertAdjacentHTML('beforeend', `
                        <li class="list-item">
                            <div class="icon" style="background-color: #fef2f2; color: #ef4444;">
                                <i class="fas fa-calendar-star"></i>
                            </div>
                            <div class="text">
                                <h5>${event.name}</h5>
                                <span>${event.formatted_date}</span>
                            </div>
                        </li>
                    `);
                });
            } catch (error) {
                console.error('Error fetching upcoming events:', error);
                list.innerHTML = `<p style="padding: 1rem; color: var(--notification-red);"><b>Error:</b> Could not load events.</p>`;
            }
        }

        async function fetchLatestNews() {
            const list = document.getElementById('latest-news-list');
            try {
                const newsItems = await fetchJSON('get_latest_news.php');
                list.innerHTML = '';
                if (newsItems.length === 0) {
                    list.innerHTML = '<p style="color: var(--secondary-text); padding: 1rem;">No recent news.</p>';
                    return;
                }
                newsItems.forEach(item => {
                    const listItem = `
                        <li class="list-item">
                            <div class="icon" style="background-color: #f5f3ff; color: #7c3aed;">
                                <i class="fas fa-newspaper"></i>
                            </div>
                            <div class="text">
                                <h5>${item.news_title}</h5>
                                <span>${item.posted_info}</span>
                            </div>
                        </li>`;
                    list.insertAdjacentHTML('beforeend', listItem);
                });
            } catch (error) {
                console.error('Error fetching latest news:', error);
                list.innerHTML = `<p style="padding: 1rem; color: var(--notification-red);"><b>Error:</b> Could not load news.</p>`;
            }
        }

        // --- 4. ENHANCED BADGE NOTIFICATION LOGIC (UPDATED) ---
        function updateBadge(navItemId, count, isNewNotification = false) {
            const badge = document.querySelector(`#${navItemId} .notification-badge`);
            if (!badge) return;

            if (sessionStorage.getItem(navItemId + '_badgeHidden')) {
                badge.classList.remove('show', 'pulse');
                return;
            }

            if (count > 0) {
                badge.textContent = count > 99 ? '99+' : count;
                badge.classList.add('show');
                
                if (isNewNotification) {
                    badge.classList.add('pulse');
                    setTimeout(() => badge.classList.remove('pulse'), 6000);
                }
            } else {
                badge.classList.remove('show', 'pulse');
            }
        }

        async function fetchBadgeCounts() {
            try {
                const counts = await fetchJSON('get_badge_counts.php');
                console.log('Badge counts received:', counts);
                
                if (counts && typeof counts === 'object') {
                    const badgeTypes = ['assignments', 'results', 'notices', 'news', 'events'];
                    
                    badgeTypes.forEach(type => {
                        const currentCount = parseInt(counts[type]) || 0;
                        const lastCount = lastBadgeCounts[type] || 0;
                        const isNewNotification = currentCount > lastCount;
                        
                        updateBadge(`nav-${type}`, currentCount, isNewNotification);
                    });

                    lastBadgeCounts = { ...counts };
                    updateConnectionStatus(true);
                    retryCount = 0; // Reset retry count on success
                    
                    console.log('Badge update successful, status:', counts.status);
                } else {
                    console.warn('Invalid badge counts data received:', counts);
                    updateConnectionStatus(false);
                }
            } catch (error) {
                console.error('Error fetching badge counts:', error);
                updateConnectionStatus(false);
                
                retryCount++;
                if (retryCount <= CONFIG.MAX_RETRIES) {
                    const delay = CONFIG.RETRY_DELAY * Math.pow(1.5, retryCount - 1);
                    console.log(`Retrying badge counts fetch in ${delay}ms (attempt ${retryCount})`);
                    setTimeout(fetchBadgeCounts, delay);
                } else {
                    console.log('Max retries reached, will try again on next interval');
                    retryCount = 0;
                }
            }
        }


        function initializeBadgeClickListeners() {
            const navItemsWithBadges = document.querySelectorAll('#nav-assignments, #nav-results, #nav-notices, #nav-news, #nav-events');
            
            navItemsWithBadges.forEach(item => {
                item.addEventListener('click', function() {
                    const badge = item.querySelector('.notification-badge');
                    if (badge && badge.classList.contains('show')) {
                        badge.classList.remove('show', 'pulse');
                        sessionStorage.setItem(item.id + '_badgeHidden', 'true');
                        
                        // Failsafe to re-enable badge visibility later
                        setTimeout(() => {
                            sessionStorage.removeItem(item.id + '_badgeHidden');
                        }, 1000);
                    }
                });
            });
        }

        // --- 5. PERIODIC UPDATES ---
        function startPeriodicUpdates() {
            if (fetchIntervalId) {
                clearInterval(fetchIntervalId);
            }
            
            fetchIntervalId = setInterval(() => {
                fetchBadgeCounts();
            }, CONFIG.FETCH_INTERVAL);
        }

        function stopPeriodicUpdates() {
            if (fetchIntervalId) {
                clearInterval(fetchIntervalId);
                fetchIntervalId = null;
            }
        }

        // --- 6. PAGE VISIBILITY HANDLING ---
        function handleVisibilityChange() {
            if (document.hidden) {
                stopPeriodicUpdates();
            } else {
                fetchBadgeCounts();
                startPeriodicUpdates();
            }
        }

        // --- 7. MOBILE MENU HANDLING ---
        function initializeMobileMenu() {
            const menuToggle = document.getElementById('menuToggle');
            const sidebar = document.getElementById('sidebar');
            const sidebarOverlay = document.getElementById('sidebarOverlay');
            
            function toggleSidebar() {
                sidebar.classList.toggle('active');
                sidebarOverlay.classList.toggle('active');
                
                // Prevent body scroll when sidebar is open
                document.body.style.overflow = sidebar.classList.contains('active') ? 'hidden' : '';
            }
            
            function closeSidebar() {
                sidebar.classList.remove('active');
                sidebarOverlay.classList.remove('active');
                document.body.style.overflow = '';
            }
            
            if (menuToggle) {
                menuToggle.addEventListener('click', toggleSidebar);
            }
            
            if (sidebarOverlay) {
                sidebarOverlay.addEventListener('click', closeSidebar);
            }
            
            const navLinks = sidebar.querySelectorAll('.sidebar-nav a');
            navLinks.forEach(link => {
                link.addEventListener('click', () => {
                    if (window.innerWidth <= 992) {
                        closeSidebar();
                    }
                });
            });
            
            window.addEventListener('resize', () => {
                if (window.innerWidth > 992) {
                    closeSidebar();
                }
            });
            
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && sidebar.classList.contains('active')) {
                    closeSidebar();
                }
            });
        }

        // --- 8. TOUCH GESTURE SUPPORT ---
        function initializeTouchGestures() {
            let startX = 0;
            let startY = 0;
            let isScrolling = false;
            
            document.addEventListener('touchstart', (e) => {
                startX = e.touches[0].clientX;
                startY = e.touches[0].clientY;
                isScrolling = false;
            });
            
            document.addEventListener('touchmove', (e) => {
                if (!startX || !startY || isScrolling) return;
                
                const diffX = e.touches[0].clientX - startX;
                const diffY = e.touches[0].clientY - startY;
                
                if (Math.abs(diffY) > Math.abs(diffX)) {
                    isScrolling = true;
                }
            });
            
            document.addEventListener('touchend', (e) => {
                if (isScrolling || !startX) return;
                
                const diffX = e.changedTouches[0].clientX - startX;
                const sidebar = document.getElementById('sidebar');
                
                if (diffX > 50 && startX < 50 && !sidebar.classList.contains('active')) {
                    sidebar.classList.add('active');
                    document.getElementById('sidebarOverlay').classList.add('active');
                    document.body.style.overflow = 'hidden';
                }
                
                if (diffX < -50 && sidebar.classList.contains('active')) {
                    sidebar.classList.remove('active');
                    document.getElementById('sidebarOverlay').classList.remove('active');
                    document.body.style.overflow = '';
                }
                
                startX = 0;
                startY = 0;
            });
        }

        // --- 9. PAGE INITIALIZATION ---
        window.onload = function() {
            console.log('Initializing student dashboard...');
            
            // Initialize all components
            fetchPassRateData();
            fetchBadgeCounts();
            fetchUpcomingEvents();
            fetchLatestNews();
            initializeBadgeClickListeners();
            initializeMobileMenu();
            initializeTouchGestures();
            startPeriodicUpdates();
            
            // Set up visibility change listener
            document.addEventListener('visibilitychange', handleVisibilityChange);
            
            console.log('Dashboard initialized successfully!');
        };

        // Clean up on page unload
        window.addEventListener('beforeunload', () => {
            stopPeriodicUpdates();
        });

        // Handle online/offline events
        window.addEventListener('online', () => {
            console.log('Connection restored');
            updateConnectionStatus(true);
            fetchBadgeCounts();
            startPeriodicUpdates();
        });

        window.addEventListener('offline', () => {
            console.log('Connection lost');
            updateConnectionStatus(false);
            stopPeriodicUpdates();
        });
    </script>

</body>
</html>
