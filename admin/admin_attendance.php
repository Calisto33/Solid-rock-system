<?php
session_start();
include '../config.php'; // Ensure this path is correct

// Ensure the user is logged in as admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

// Get class parameter from GET or set default
$class = $_GET['class'] ?? 'f_1';

// Validate the $class input
$allowed_classes = ['f_1', 'f_2', 'f_3', 'f_4', 'f_5', 'f_6'];
if (!in_array($class, $allowed_classes)) {
    $class = 'f_1';
}

// Fetch students and their attendance counts based on selected class
$studentsQuery = "
    SELECT 
        s.student_id, 
        s.username,
        COUNT(a.attendance_id) as total_days,
        SUM(CASE WHEN a.status = 'Present' THEN 1 ELSE 0 END) as present_days
    FROM 
        students s
    LEFT JOIN 
        attendance a ON s.student_id = a.student_id
    WHERE 
        s.class = ?
    GROUP BY 
        s.student_id, s.username
    ORDER BY
        s.username
";
$stmt = $conn->prepare($studentsQuery);

if ($stmt === false) {
    die("Error preparing statement (Student List): " . $conn->error);
}

$stmt->bind_param("s", $class);
$stmt->execute();
$studentsResult = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin View Attendance</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #3a86ff;
            --primary-dark: #2667cc;
            --secondary-color: #8338ec;
            --accent-color: #ff006e;
            --success-color: #38b000;
            --warning-color: #ffbe0b;
            --text-color: #333;
            --text-light: #6c757d;
            --white: #fff;
            --light-bg: #f8f9fa;
            --card-bg: #ffffff;
            --border-radius: 12px;
            --shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Roboto, -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--light-bg);
            color: var(--text-color);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Navigation */
        .navbar {
            background-color: var(--white);
            box-shadow: var(--shadow);
            padding: 0;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .navbar-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 2rem;
            max-width: 1400px;
            margin: 0 auto;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }

        .logo-icon {
            background-color: var(--primary-color);
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }

        .logo-text {
            color: var(--text-color);
            font-weight: 700;
            font-size: 1.2rem;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .nav-link {
            color: var(--text-color);
            text-decoration: none;
            font-weight: 500;
            padding: 0.5rem 0.8rem;
            border-radius: 6px;
            transition: var(--transition);
        }

        .nav-link:hover {
            background-color: rgba(58, 134, 255, 0.1);
            color: var(--primary-color);
        }

        .nav-link.active {
            background-color: rgba(58, 134, 255, 0.1);
            color: var(--primary-color);
        }
        
        /* Add logout link style (If you want a different style, add it here) */
        .nav-link.logout {
            color: var(--accent-color);
        }
        .nav-link.logout:hover {
            background-color: rgba(255, 0, 110, 0.1);
            color: var(--accent-color);
        }

        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            font-size: 1.5rem;
            color: var(--text-color);
            cursor: pointer;
        }

        /* Main content */
        .container {
            max-width: 1200px;
            width: 100%;
            margin: 2rem auto;
            padding: 0 1.5rem;
            flex: 1;
        }

        .page-header {
            margin-bottom: 2rem;
        }

        .page-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--text-color);
            margin-bottom: 0.5rem;
        }

        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--text-light);
            font-size: 0.9rem;
        }

        .breadcrumb i {
            font-size: 0.7rem;
        }

        .breadcrumb a {
            color: var(--text-light);
            text-decoration: none;
            transition: var(--transition);
        }

        .breadcrumb a:hover {
            color: var(--primary-color);
        }

        .card {
            background: var(--card-bg);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
            overflow: hidden;
            transition: var(--transition);
        }

        .card:hover {
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.12);
        }

        .card-header {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-color);
            margin: 0;
        }

        .card-body {
            padding: 1.5rem;
        }

        /* Form elements */
        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-color);
        }

        .form-select {
            display: block;
            width: 100%;
            max-width: 300px;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            font-weight: 400;
            line-height: 1.5;
            color: var(--text-color);
            background-color: var(--white);
            background-clip: padding-box;
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            transition: var(--transition);
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 16 16'%3E%3Cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 16px 12px;
        }

        .form-select:focus {
            border-color: var(--primary-color);
            outline: 0;
            box-shadow: 0 0 0 0.25rem rgba(58, 134, 255, 0.25);
        }

        /* Table */
        .table-container {
            overflow-x: auto;
            border-radius: var(--border-radius);
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        table th, table td {
            padding: 1rem 1.5rem;
            text-align: left;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        table th {
            background-color: rgba(58, 134, 255, 0.05);
            font-weight: 600;
            color: var(--text-color);
            white-space: nowrap;
        }

        table tr:last-child td {
            border-bottom: none;
        }

        table tbody tr {
            transition: var(--transition);
        }

        table tbody tr:hover {
            background-color: rgba(58, 134, 255, 0.03);
        }

        /* Badge */
        .badge {
            display: inline-block;
            padding: 0.25em 0.6em;
            font-size: 0.75em;
            font-weight: 600;
            line-height: 1;
            text-align: center;
            white-space: nowrap;
            vertical-align: baseline;
            border-radius: 50rem;
        }

        .badge-blue {
            color: #1a56db;
            background-color: #e1effe;
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            font-weight: 500;
            font-size: 0.875rem;
            padding: 0.625rem 1rem;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
        }

        .btn-outline {
            background-color: transparent;
            border: 1px solid rgba(0, 0, 0, 0.1);
            color: var(--text-color);
        }

        .btn-outline:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }

        .btn-sm {
            padding: 0.4rem 0.75rem;
            font-size: 0.8rem;
        }

        /* Attendance history */
        .attendance-history {
            overflow: hidden;
        }

        .attendance-placeholder {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 3rem 1.5rem;
            text-align: center;
            color: var(--text-light);
        }

        .placeholder-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: rgba(0, 0, 0, 0.1);
        }

        .attendance-data {
            display: none;
        }

        .attendance-header {
            display: flex;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .student-avatar {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background-color: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: 700;
            margin-right: 1rem;
            text-transform: uppercase;
        }

        .student-info h3 {
            font-size: 1.2rem;
            margin-bottom: 0.25rem;
        }

        .student-info p {
            color: var(--text-light);
            font-size: 0.9rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .stat-card {
            background-color: var(--light-bg);
            border-radius: 8px;
            padding: 1rem;
        }

        .stat-title {
            font-size: 0.9rem;
            color: var(--text-light);
            margin-bottom: 0.5rem;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
        }

        .stat-value.good {
            color: var(--success-color);
        }

        .stat-value.warning {
            color: var(--warning-color);
        }

        .stat-value.danger { 
            color: var(--accent-color);
        }

        .attendance-chart {
            height: 200px;
            margin-bottom: 1rem;
            display: flex;
            align-items: flex-end;
            gap: 8px;
            padding-top: 1rem;
            border-bottom: 1px solid rgba(0,0,0,0.1); 
            padding-bottom: 2rem; 
        }

        .chart-bar {
            flex: 1;
            min-width: 24px;
            background-color: rgba(58, 134, 255, 0.2);
            border-radius: 4px 4px 0 0;
            position: relative;
            transition: var(--transition);
        }

        .chart-bar:hover {
            background-color: rgba(58, 134, 255, 0.4);
        }

        .chart-bar::after {
            content: attr(data-value);
            position: absolute;
            top: -24px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 0.75rem;
            font-weight: 600;
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .chart-bar:hover::after {
            opacity: 1;
        }

        .chart-bar::before {
            content: attr(data-label);
            position: absolute;
            bottom: -24px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 0.75rem;
            color: var(--text-light);
        }

        .chart-legend {
            display: flex;
            justify-content: center;
            gap: 1.5rem;
            margin-top: 2rem;
            margin-bottom: 1rem;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.9rem;
        }

        .legend-color {
            width: 12px;
            height: 12px;
            border-radius: 3px;
        }

        .legend-present {
            background-color: var(--primary-color);
        }

        .legend-absent {
            background-color: var(--accent-color);
        }

        /* Alert */
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            border-left: 4px solid;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-info {
            background-color: rgba(58, 134, 255, 0.1);
            border-left-color: var(--primary-color);
        }

        /* Responsive */
        @media (max-width: 991px) {
            .navbar-container { padding: 1rem; }
            .container { padding: 0 1rem; margin: 1.5rem auto; }
            .card-header, .card-body { padding: 1.25rem; }
            table th, table td { padding: 0.75rem 1rem; }
        }

        @media (max-width: 768px) {
            .nav-links {
                display: none;
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                flex-direction: column;
                gap: 0;
                background-color: var(--white);
                box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
            }
            .nav-links.active { display: flex; }
            .nav-link {
                padding: 1rem 2rem;
                width: 100%;
                border-radius: 0;
                border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            }
            .mobile-menu-btn { display: block; }
            .page-title { font-size: 1.5rem; }
            .stats-grid { grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); }
        }

        @media (max-width: 576px) {
            .logo-text { display: none; }
            table th, table td { padding: 0.75rem; font-size: 0.9rem; }
            .btn { font-size: 0.8rem; padding: 0.5rem 0.75rem; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .attendance-chart { height: 150px; }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-container">
            <a href="admin_home.php" class="logo">
                <div class="logo-icon"><i class="fas fa-school"></i></div>
                <div class="logo-text">Admin Portal</div>
            </a>
            
            <button class="mobile-menu-btn" id="menuToggle">
                <i class="fas fa-bars"></i>
            </button>
            
            <div class="nav-links" id="navLinks">
                <a href="admin_home.php" class="nav-link"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        </div>
    </nav>

    <div class="container">
        <div class="page-header">
            <h1 class="page-title">Attendance Management</h1>
            <div class="breadcrumb">
                <a href="admin_home.php">Dashboard</a>
                <i class="fas fa-chevron-right"></i>
                <span>Attendance</span>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">View Attendance by Class</h2>
                <a href="mark_attendance.php" class="btn btn-outline">
                    <i class="fas fa-plus"></i> Mark Attendance
                </a>
            </div>
            <div class="card-body">
                <form method="GET" action="">
                    <div class="form-group">
                        <label for="class" class="form-label">Select Class:</label>
                        <select name="class" id="class" class="form-select" onchange="this.form.submit()">
                            <option value="f_1" <?= $class == 'f_1' ? 'selected' : '' ?>>Form 1</option>
                            <option value="f_2" <?= $class == 'f_2' ? 'selected' : '' ?>>Form 2</option>
                            <option value="f_3" <?= $class == 'f_3' ? 'selected' : '' ?>>Form 3</option>
                            <option value="f_4" <?= $class == 'f_4' ? 'selected' : '' ?>>Form 4</option>
                            <option value="f_5" <?= $class == 'f_5' ? 'selected' : '' ?>>Form 5</option>
                            <option value="f_6" <?= $class == 'f_6' ? 'selected' : '' ?>>Form 6</option>
                        </select>
                    </div>
                </form>

                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> Showing students for <strong><?= htmlspecialchars(ucwords(str_replace('_', ' ', $class))) ?></strong>. Click "View Details" for individual history.
                </div>

                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Student ID</th>
                                <th>Name</th>
                                <th>Status</th> 
                                <th>Attendance Rate</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody> 
                            <?php if ($studentsResult->num_rows > 0): ?>
                                <?php while ($student = $studentsResult->fetch_assoc()): ?>
                                    <?php
                                        // Calculate Attendance Rate
                                        $totalDays = (int)($student['total_days'] ?? 0);
                                        $presentDays = (int)($student['present_days'] ?? 0);
                                        $attendanceRate = ($totalDays > 0) 
                                                          ? round(($presentDays / $totalDays) * 100) . '%' 
                                                          : 'N/A';
                                    ?>
                                    <tr>
                                        <td>
                                            <span class="badge badge-blue"><?= htmlspecialchars($student['student_id']) ?></span>
                                        </td>
                                        <td><?= htmlspecialchars($student['username']) ?></td>
                                        <td>
                                            <span class="badge badge-blue">Active</span> 
                                        </td>
                                        <td>
                                            <?= $attendanceRate ?> 
                                        </td>
                                        <td>
                                            <button class="btn btn-primary btn-sm" 
                                                    onclick="fetchAttendanceHistory('<?= $student['student_id'] ?>', '<?= htmlspecialchars($student['username']) ?>', '<?= $class ?>')">
                                                <i class="fas fa-eye"></i> View Details
                                            </button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; padding: 2rem;">
                                        <div style="color: var(--text-light);">
                                            <i class="fas fa-user-slash" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                                            <p>No students found in this class.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card attendance-history">
            <div class="card-header">
                <h2 class="card-title">Attendance History</h2>
                <button class="btn btn-outline btn-sm" id="exportBtn" style="display: none;" onclick="exportReport()">
                    <i class="fas fa-file-export"></i> Export Report
                </button>
            </div>
            <div class="card-body">
                <div class="attendance-placeholder" id="attendancePlaceholder">
                    <i class="fas fa-clipboard-list placeholder-icon"></i>
                    <h3>No Student Selected</h3>
                    <p>Select a student and click "View Details" to view attendance history.</p>
                </div>
                
                <div class="attendance-data" id="attendanceData">
                    <div class="attendance-header">
                        <div class="student-avatar" id="studentAvatar"></div>
                        <div class="student-info">
                            <h3 id="studentName"></h3>
                            <p id="studentDetails"></p>
                        </div>
                    </div>
                    
                    <div class="stats-grid" id="statsGrid"></div>
                    
                    <h3 style="margin-bottom: 1rem;">Monthly Attendance</h3>
                    <div class="attendance-chart" id="attendanceChart"></div>
                    
                    <div class="chart-legend" id="chartLegend">
                        <div class="legend-item">
                            <div class="legend-color legend-present"></div>
                            <span>Present</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color legend-absent"></div>
                            <span>Absent</span>
                        </div>
                    </div>
                    
                    <h3 style="margin: 1.5rem 0 1rem;">Recent Attendance Records</h3>
                    <div class="table-container">
                        <table id="recentAttendanceTable">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Marked By</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script> 
        document.getElementById('menuToggle').addEventListener('click', function() {
            document.getElementById('navLinks').classList.toggle('active');
        });

        let currentStudentId = null;
        let currentStudentName = null;

        function fetchAttendanceHistory(studentId, studentName, studentClass) {
            const placeholder = document.getElementById('attendancePlaceholder');
            const dataSection = document.getElementById('attendanceData');
            const exportButton = document.getElementById('exportBtn');
            
            placeholder.style.display = 'block';
            dataSection.style.display = 'none';
            exportButton.style.display = 'none';
            placeholder.innerHTML = '<i class="fas fa-spinner fa-spin placeholder-icon"></i><h3>Loading Data...</h3>';

            fetch(`Workspace_attendance.php?student_id=${studentId}`)
                .then(response => {
                    if (!response.ok) { throw new Error(`HTTP error! status: ${response.status}`); }
                    return response.json();
                })
                .then(data => {
                    if (data.error) { throw new Error(data.error); }
                    
                    placeholder.style.display = 'none';
                    dataSection.style.display = 'block';
                    exportButton.style.display = 'inline-flex';
                    currentStudentId = studentId;
                    currentStudentName = studentName;
                    populateAttendanceData(data, studentName, studentClass); 
                })
                .catch(error => {
                    console.error('Error fetching attendance data:', error);
                    placeholder.innerHTML = `<i class="fas fa-exclamation-triangle placeholder-icon" style="color:var(--accent-color);"></i><h3>Failed to Load</h3><p>${error.message}. Please check console or try again.</p>`;
                    placeholder.style.display = 'block';
                    dataSection.style.display = 'none';
                    exportButton.style.display = 'none';
                });
        }

        function populateAttendanceData(data, studentName, studentClass) {
            document.getElementById('studentAvatar').textContent = studentName.substring(0, 2).toUpperCase();
            document.getElementById('studentName').textContent = studentName;
            document.getElementById('studentDetails').textContent = `Student ID: ${data.studentId} • ${studentClass.replace('_', ' ')}`;

            const statsGrid = document.getElementById('statsGrid');
            statsGrid.innerHTML = `
                <div class="stat-card">
                    <div class="stat-title">Present Days</div>
                    <div class="stat-value good">${data.stats.present || 0}</div>
                </div>
                <div class="stat-card">
                    <div class="stat-title">Absent Days</div>
                    <div class="stat-value warning">${data.stats.absent || 0}</div>
                </div>
                <div class="stat-card">
                    <div class="stat-title">Attendance Rate</div>
                    <div class="stat-value">${data.stats.rate || 'N/A'}</div>
                </div>
                <div class="stat-card">
                    <div class="stat-title">Last Absent</div>
                    <div class="stat-value" style="font-size: 1rem;">${data.stats.lastAbsent || 'N/A'}</div>
                </div>`;

            const chart = document.getElementById('attendanceChart');
            chart.innerHTML = ''; 
            const monthlyValues = Object.values(data.monthly);
            const maxVal = monthlyValues.length > 0 ? Math.max(...monthlyValues) : 0;
            
            if (monthlyValues.length === 0) {
                chart.innerHTML = '<p style="text-align:center; color: var(--text-light); width: 100%;">No monthly data available.</p>';
            } else {
                 for (const [month, value] of Object.entries(data.monthly)) {
                    const heightPercent = maxVal > 0 ? (value / maxVal) * 90 : 0;
                    const bar = document.createElement('div');
                    bar.className = 'chart-bar';
                    bar.style.height = `${heightPercent}%`;
                    bar.setAttribute('data-value', value);
                    bar.setAttribute('data-label', month);
                    bar.style.backgroundColor = value > 0 ? 'var(--primary-color)' : 'rgba(58, 134, 255, 0.2)'; 
                    chart.appendChild(bar);
                }
            }

            const tableBody = document.getElementById('recentAttendanceTable').getElementsByTagName('tbody')[0];
            tableBody.innerHTML = '';
            if (data.recent.length === 0) {
                 tableBody.innerHTML = '<tr><td colspan="4" style="text-align:center; color: var(--text-light); padding: 1rem;">No recent records found.</td></tr>';
            } else {
                data.recent.forEach(record => {
                    let statusHtml = '';
                    switch (record.status.toLowerCase()) {
                        case 'present':
                            statusHtml = `<span style="color: var(--success-color);"><i class="fas fa-check-circle"></i> Present</span>`;
                            break;
                        case 'absent':
                            statusHtml = `<span style="color: var(--accent-color);"><i class="fas fa-times-circle"></i> Absent</span>`;
                            break;
                        default:
                             statusHtml = `<span>${record.status}</span>`;
                    }

                    const row = tableBody.insertRow();
                    row.innerHTML = `
                        <td>${record.date}</td>
                        <td>${statusHtml}</td>
                        <td>${record.markedBy}</td>
                        <td>${record.notes}</td>
                    `;
                });
            }
        }
        
        function exportReport() {
            if (currentStudentId) {
                alert(`Exporting report for ${currentStudentName} (ID: ${currentStudentId}). This needs a backend script.`);
            } else {
                alert("Please select a student first.");
            }
        }
    </script>
</body>
</html>
<?php
// Close the statement and connection
if (isset($stmt)) {
    $stmt->close();
}
if (isset($conn)) {
    $conn->close();
}
?>