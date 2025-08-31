<?php
session_start();
include '../config.php'; // Ensure this path is correct


if (!isset($_SESSION['super_admin_id']) || !isset($_SESSION['username'])) {
    header("Location: super_admin_login.php");
    exit();
}

$super_admin_username = $_SESSION['username'];

// Fetch total number of students
$query_students = "SELECT COUNT(student_id) AS total_students FROM students";
$result_students = $conn->query($query_students);
$total_students = 0;
if ($result_students && $result_students->num_rows > 0) {
    $row_students = $result_students->fetch_assoc();
    $total_students = $row_students['total_students'];
}

// Fetch total number of staff members
$query_staff = "SELECT COUNT(id) AS total_staff FROM users WHERE role = 'staff'";
$result_staff = $conn->query($query_staff);
$total_staff = 0;
if ($result_staff && $result_staff->num_rows > 0) {
    $row_staff = $result_staff->fetch_assoc();
    $total_staff = $row_staff['total_staff'];
}

// Fetch total number of classes
$query_classes = "SELECT COUNT(DISTINCT class) AS total_classes FROM students";
$result_classes = $conn->query($query_classes);
$total_classes = 0;
if ($result_classes && $result_classes->num_rows > 0) {
    $row_classes = $result_classes->fetch_assoc();
    $total_classes = $row_classes['total_classes'];
}

// Fetch total fees income
$query_fees = "SELECT SUM(amount_paid) AS total_fees_income FROM fees";
$result_fees = $conn->query($query_fees);
$total_fees_income = 0;
if ($result_fees && $result_fees->num_rows > 0) {
    $row_fees = $result_fees->fetch_assoc();
    $total_fees_income = $row_fees['total_fees_income'] ?? 0;
}

// Fetch total number of active parents
$query_parents = "SELECT COUNT(id) AS total_active_parents FROM users WHERE role = 'parent'";
$result_parents = $conn->query($query_parents);
$total_active_parents = 0;
if ($result_parents && $result_parents->num_rows > 0) {
    $row_parents = $result_parents->fetch_assoc();
    $total_active_parents = $row_parents['total_active_parents'];
}

// Placeholder for system efficiency
$system_efficiency = "95%";

// --- Prepare Sample Data for Line Chart ---
$chart_labels = [];
$monthly_income_data = [];
$monthly_students_data = [];
$monthly_staff_data = [];

for ($i = 5; $i >= 0; $i--) {
    $chart_labels[] = date('M Y', strtotime("-$i months"));
}

$current_month_income_estimate = $total_fees_income > 0 ? $total_fees_income / 6 : 5000;
$current_students_estimate = $total_students > 0 ? $total_students : 50;
$current_staff_estimate = $total_staff > 0 ? $total_staff : 5;

for ($i = 0; $i < 6; $i++) {
    $monthly_income_data[] = round(max(0, $current_month_income_estimate * (0.7 + ($i*0.1) + (rand(-15,15)/100) ) ));
    $monthly_students_data[] = round(max(0, $current_students_estimate * (0.8 + ($i*0.04) + (rand(-5,5)/100) ) ));
    $monthly_staff_data[] = round(max(0, $current_staff_estimate * (0.9 + ($i*0.02) + (rand(-10,10)/100) ) ));
}
$monthly_students_data[5] = $total_students;
$monthly_staff_data[5] = $total_staff;
if ($total_fees_income > 0) {
    $monthly_income_data[5] = round($total_fees_income > 0 ? $total_fees_income / max(1, count($monthly_income_data)) : $monthly_income_data[4] * 1.05);
}

$key_metrics_chart_data = [
    'labels' => $chart_labels,
    'datasets' => [
        [
            'label' => 'Fees Income ($)',
            'data' => $monthly_income_data,
            'borderColor' => '#d12c2c',
            'backgroundColor' => 'rgba(209, 44, 44, 0.1)',
            'fill' => true,
            'tension' => 0.2,
            'yAxisID' => 'yIncome',
        ],
        [
            'label' => 'Active Students',
            'data' => $monthly_students_data,
            'borderColor' => '#003366',
            'backgroundColor' => 'rgba(0, 51, 102, 0.1)',
            'fill' => true,
            'tension' => 0.2,
            'yAxisID' => 'yCount',
        ],
        [
            'label' => 'Staff Count',
            'data' => $monthly_staff_data,
            'borderColor' => '#48bb78',
            'backgroundColor' => 'rgba(72, 187, 120, 0.1)',
            'fill' => true,
            'tension' => 0.2,
            'yAxisID' => 'yCount',
        ]
    ]
];
$key_metrics_chart_data_json = json_encode($key_metrics_chart_data);

// --- Fetch and Prepare Data for Attendance Donut Chart ---
$total_present_records = 0;
$total_attendance_records = 0;

$query_present = "SELECT COUNT(*) AS total_present FROM attendance WHERE status = 'Present'";
$result_present = $conn->query($query_present);
if ($result_present && $result_present->num_rows > 0) {
    $row_present = $result_present->fetch_assoc();
    $total_present_records = (int)$row_present['total_present'];
}

$query_total_attendance = "SELECT COUNT(*) AS total_records FROM attendance";
$result_total_attendance = $conn->query($query_total_attendance);
if ($result_total_attendance && $result_total_attendance->num_rows > 0) {
    $row_total_attendance = $result_total_attendance->fetch_assoc();
    $total_attendance_records = (int)$row_total_attendance['total_records'];
}

$present_percentage = 0;
$absent_percentage = 100;

if ($total_attendance_records > 0) {
    $present_percentage = round(($total_present_records / $total_attendance_records) * 100, 2);
    $absent_percentage = 100 - $present_percentage;
} else {
    $present_percentage = 85;
    $absent_percentage = 15;
}

$attendance_chart_data = [
    'labels' => ['Present', 'Absent/Other'],
    'datasets' => [[
        'label' => 'Attendance Rate',
        'data' => [$present_percentage, $absent_percentage],
        'backgroundColor' => [
            '#48bb78',
            '#d12c2c'
        ],
        'borderColor' => [
            '#48bb78',
            '#d12c2c'
        ],
        'borderWidth' => 2
    ]]
];
$attendance_chart_data_json = json_encode($attendance_chart_data);

// --- Prepare Data for Student vs Parent Bar Chart ---
$student_parent_bar_chart_data = [
    'labels' => ['Students', 'Active Parents'],
    'datasets' => [[
        'data' => [$total_students, $total_active_parents],
        'backgroundColor' => [
            '#003366',
            '#d12c2c'
        ],
        'borderColor' => [
            '#003366',
            '#d12c2c'
        ],
        'borderWidth' => 1,
        'barPercentage' => 0.5,
        'categoryPercentage' => 0.8
    ]]
];
$student_parent_bar_chart_data_json = json_encode($student_parent_bar_chart_data);

// --- Prepare Data for Fees by Class (for the list) ---
$fees_by_class_data = [];
$query_fees_by_class = "
    SELECT
        s.class,
        SUM(f.amount_paid) AS total_fees
    FROM fees f
    JOIN students s ON f.student_id = s.student_id
    WHERE s.class IS NOT NULL AND s.class != ''
    GROUP BY s.class
    ORDER BY total_fees DESC
";
$result_fees_by_class = $conn->query($query_fees_by_class);
if ($result_fees_by_class && $result_fees_by_class->num_rows > 0) {
    while($row = $result_fees_by_class->fetch_assoc()) {
        $fees_by_class_data[] = $row;
    }
}

// --- RESTORED: Prepare Data for Pass Rate by Class (for the line chart) ---
$pass_rate_by_class_data = [];
$pass_mark = 50; // Define pass mark

$query_distinct_classes_pr = "SELECT DISTINCT class FROM students WHERE class IS NOT NULL AND class != '' ORDER BY class ASC";
$result_distinct_classes_pr = $conn->query($query_distinct_classes_pr);
$distinct_classes_pr = [];
if ($result_distinct_classes_pr && $result_distinct_classes_pr->num_rows > 0) {
    while ($row_class = $result_distinct_classes_pr->fetch_assoc()) {
        $distinct_classes_pr[] = $row_class['class'];
    }
}

foreach ($distinct_classes_pr as $class_name) {
    $stmt_pass_rate = $conn->prepare("
        SELECT
            COUNT(r.result_id) AS total_results,
            SUM(CASE WHEN r.final_mark >= ? THEN 1 ELSE 0 END) AS passed_results
        FROM results r
        JOIN students s ON r.student_id = s.student_id
        WHERE s.class = ?
    ");
    
    $class_pass_rate = 0;
    if ($stmt_pass_rate) {
        $stmt_pass_rate->bind_param("is", $pass_mark, $class_name);
        $stmt_pass_rate->execute();
        $result_pass_rate_query = $stmt_pass_rate->get_result();
        $data_pass_rate = $result_pass_rate_query->fetch_assoc();
        
        if ($data_pass_rate && $data_pass_rate['total_results'] > 0) {
            $class_pass_rate = round(($data_pass_rate['passed_results'] / $data_pass_rate['total_results']) * 100, 2);
        }
        $stmt_pass_rate->close();
    }
    $pass_rate_by_class_data[] = ['class_name' => $class_name, 'pass_rate' => $class_pass_rate];
}

usort($pass_rate_by_class_data, function($a, $b) {
    return $b['pass_rate'] <=> $a['pass_rate'];
});

// --- Prepare Data for Pass Rate Line Chart ---
$pass_rate_labels = [];
$pass_rate_data = [];
foreach ($pass_rate_by_class_data as $data) {
    $pass_rate_labels[] = $data['class_name'];
    $pass_rate_data[] = $data['pass_rate'];
}
$pass_rate_line_chart_data = [
    'labels' => $pass_rate_labels,
    'datasets' => [[
        'label' => 'Pass Rate (%)',
        'data' => $pass_rate_data,
        'borderColor' => '#003366',
        'backgroundColor' => 'rgba(0, 51, 102, 0.2)',
        'fill' => true,
        'tension' => 0.1
    ]]
];
$pass_rate_line_chart_data_json = json_encode($pass_rate_line_chart_data);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Dashboard - Solid Rock Group of Schools</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/jpeg" href="../images/logo.jpeg">
    <link rel="shortcut icon" type="image/jpeg" href="../images/logo.jpeg">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Updated color scheme to match Solid Rock branding */
        :root {
            --primary-bg: #f8fafc;
            --sidebar-bg: #FFFFFF;
            --card-bg: #FFFFFF;
            --primary-color: #003366;
            --secondary-color: #d12c2c;
            --accent-color: #f0f4f8;
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
            --secondary-light-bg: rgba(209, 44, 44, 0.1);
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
            min-height: 100vh;
            overflow-x: hidden;
            line-height: 1.6;
        }

        /* Sidebar Styles */
        .sidebar {
            width: var(--sidebar-width);
            background-color: var(--sidebar-bg);
            padding: 20px;
            display: flex;
            flex-direction: column;
            transition: transform 0.3s ease;
            border-right: 1px solid var(--border-color);
            position: fixed;
            height: 100%;
            z-index: 1000;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.05);
        }

        .sidebar.collapsed {
            transform: translateX(-100%);
        }

        .sidebar-header {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
            padding-left: 10px;
        }

        .sidebar-header .logo-icon {
            font-size: 24px;
            color: var(--primary-color);
            margin-right: 10px;
        }

        .sidebar-header .brand-name {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--primary-color);
        }

        .main-menu-title {
            font-size: 0.75rem;
            color: var(--text-light);
            text-transform: uppercase;
            margin-bottom: 10px;
            padding-left: 10px;
            font-weight: 600;
        }

        .nav-menu {
            list-style: none;
            flex-grow: 1;
            overflow-y: auto;
        }

        .nav-item {
            margin-bottom: 5px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            color: var(--text-light);
            text-decoration: none;
            border-radius: 8px;
            transition: var(--transition);
            font-size: 0.95rem;
            white-space: nowrap;
            font-weight: 500;
        }

        .nav-link i {
            min-width: 20px;
            margin-right: 15px;
            font-size: 16px;
            color: var(--text-light);
        }
        
        .nav-link span {
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .nav-link:hover,
        .nav-link.active {
            background-color: var(--primary-light-bg);
            color: var(--primary-color);
            transform: translateX(4px);
        }
        
        .nav-link.active i,
        .nav-link:hover i {
            color: var(--primary-color);
        }

        .sidebar-footer {
            margin-top: auto;
            padding-top: 15px;
            border-top: 1px solid var(--border-color);
        }

        .sidebar-footer .upgrade-section {
            padding: 15px;
            background: linear-gradient(135deg, var(--secondary-color), #b91c1c);
            border-radius: var(--border-radius);
            text-align: center;
            color: white;
            margin-bottom: 10px;
        }

        .sidebar-footer .upgrade-section p {
            font-size: 0.9rem;
            margin-bottom: 10px;
        }

        .upgrade-btn {
            display: inline-block;
            background-color: rgba(255, 255, 255, 0.2);
            color: white;
            padding: 8px 15px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.85rem;
            transition: var(--transition);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .upgrade-btn:hover {
            background-color: rgba(255, 255, 255, 0.3);
        }

        .main-wrapper {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            margin-left: var(--sidebar-width);
            transition: margin-left 0.3s ease;
        }

        body.sidebar-collapsed .main-wrapper {
            margin-left: 0;
        }

        .top-bar {
            background-color: var(--sidebar-bg);
            padding: 15px 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--border-color);
            height: 70px;
            box-shadow: var(--shadow);
        }

        .hamburger-menu {
            display: none;
            font-size: 24px;
            color: var(--primary-color);
            cursor: pointer;
            margin-right: 15px;
        }

        .search-bar {
            display: flex;
            align-items: center;
            background-color: var(--accent-color);
            border-radius: var(--border-radius);
            padding: 8px 12px;
            width: 300px;
        }

        .search-bar i {
            color: var(--text-light);
            margin-right: 8px;
        }

        .search-bar input {
            border: none;
            background: transparent;
            outline: none;
            width: 100%;
            font-size: 0.9rem;
            color: var(--text-dark);
        }

        .top-bar-actions {
            display: flex;
            align-items: center;
        }

        .action-icon {
            font-size: 20px;
            color: var(--text-light);
            margin-left: 20px;
            cursor: pointer;
            transition: var(--transition);
        }

        .action-icon:hover {
            color: var(--primary-color);
        }
        
        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), #004080);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin-left: 20px;
            font-size: 0.9rem;
        }

        .content-area {
            padding: 30px;
            flex-grow: 1;
            background-color: var(--primary-bg);
        }

        .content-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .content-header .title {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary-color);
        }

        .header-actions button {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            color: var(--text-dark);
            padding: 8px 15px;
            border-radius: var(--border-radius);
            margin-left: 10px;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 500;
            transition: var(--transition);
        }

        .header-actions button:hover {
            background-color: var(--primary-light-bg);
            color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .header-actions button i {
            margin-right: 5px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background-color: var(--card-bg);
            padding: 25px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
            transition: var(--transition);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .stat-card .label {
            font-size: 0.9rem;
            color: var(--text-light);
            margin-bottom: 8px;
            font-weight: 500;
        }

        .stat-card .value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 8px;
        }

        .stat-card .comparison {
            font-size: 0.8rem;
            color: var(--text-light);
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .stat-card .comparison .positive {
            color: var(--success);
            font-weight: 600;
        }

        .stat-card .comparison .negative {
            color: var(--error);
            font-weight: 600;
        }

        .stat-card .comparison i {
            margin-right: 3px;
        }

        .charts-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }

        .chart-container {
            background-color: var(--card-bg);
            padding: 25px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
            display: flex;
            flex-direction: column;
            min-height: 350px;
        }

        .chart-container .chart-title-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .chart-container .chart-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--primary-color);
        }

        .chart-container .chart-subtitle {
            font-size: 0.85rem;
            color: var(--text-light);
        }

        .chart-container .chart-options i {
            color: var(--text-light);
            cursor: pointer;
            transition: var(--transition);
        }

        .chart-container .chart-options i:hover {
            color: var(--primary-color);
        }
        
        .chart-canvas-container {
            position: relative;
            flex-grow: 1;
            width: 100%;
        }
        
        .charts-grid > .chart-container:nth-child(1) .chart-canvas-container {
            height: 280px;
        }

        .charts-grid > .chart-container:nth-child(2) {
            overflow-y: auto;
        }

        .donut-chart-container {
            position: relative;
            height: 200px;
            width: 100%;
            margin: auto;
            max-width: 220px;
            flex-grow: 1;
        }

        .data-list {
            list-style: none;
            padding: 0;
            flex-grow: 1;
            overflow-y: auto;
            max-height: 280px;
        }

        .data-list-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 8px;
            font-size: 0.9rem;
            border-bottom: 1px solid var(--border-color);
            transition: var(--transition);
        }

        .data-list-item:last-child {
            border-bottom: none;
        }

        .data-list-item:hover {
            background-color: var(--accent-color);
        }

        .data-list-item .class-name {
            color: var(--text-dark);
            font-weight: 500;
        }

        .data-list-item .class-value {
            color: var(--secondary-color);
            font-weight: 600;
        }

        .data-list .no-data {
            text-align: center;
            color: var(--text-light);
            padding: 30px 20px;
            font-style: italic;
        }

        .bottom-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }

        .bottom-card {
            background-color: var(--card-bg);
            padding: 25px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
            min-height: 280px;
            display: flex;
            flex-direction: column;
        }

        .bottom-card .card-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 20px;
            text-align: center;
            width: 100%;
        }
        
        .bottom-grid .bottom-card .chart-canvas-container {
            height: 200px;
        }

        .overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.4);
            z-index: 999;
        }

        .overlay.active {
            display: block;
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .charts-grid > .chart-container:nth-child(1) .chart-canvas-container { 
                height: 260px; 
            }
        }

        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.active {
                transform: translateX(0);
                box-shadow: 5px 0 15px rgba(0,0,0,0.1);
            }
            
            .main-wrapper {
                margin-left: 0;
            }
            
            .hamburger-menu {
                display: block;
            }
            
            .search-bar {
                width: auto;
                flex-grow: 1;
                margin: 0 15px;
            }
            
            .top-bar-actions .action-icon { 
                margin-left: 15px; 
            }
            
            .charts-grid {
                grid-template-columns: 1fr;
            }
            
            .charts-grid > .chart-container:nth-child(1) .chart-canvas-container { 
                height: 240px; 
            }
            
            .charts-grid > .chart-container:nth-child(2) { 
                min-height: 280px; 
            }
            
            .data-list { 
                max-height: 220px; 
            }
        }

        @media (max-width: 768px) {
            .content-area {
                padding: 20px;
            }
            
            .content-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .content-header .title {
                margin-bottom: 10px;
                font-size: 1.5rem;
            }
            
            .header-actions {
                width: 100%;
                display: flex;
                justify-content: flex-start;
            }
            
            .header-actions button { 
                margin-left: 0; 
                margin-right: 10px;
            }

            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            }
            
            .stat-card .value { 
                font-size: 1.7rem; 
            }
            
            .charts-grid > .chart-container:nth-child(1) .chart-canvas-container { 
                height: 220px; 
            }
            
            .donut-chart-container { 
                height: 160px; 
                max-width: 180px; 
            }
            
            .bottom-grid .bottom-card .chart-canvas-container { 
                height: 150px; 
            }
            
            .data-list { 
                max-height: 200px; 
            }
        }

        @media (max-width: 576px) {
            .top-bar {
                padding: 15px;
            }
            
            .search-bar { 
                display: none; 
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .bottom-grid {
                grid-template-columns: 1fr;
            }
            
            .sidebar { 
                width: 220px; 
            }
            
            .charts-grid > .chart-container:nth-child(1) .chart-canvas-container { 
                height: 200px; 
            }
            
            .donut-chart-container { 
                height: 150px; 
                max-width: 150px; 
            }
            
            .bottom-grid .bottom-card .chart-canvas-container { 
                height: 140px; 
            }
            
            .data-list { 
                max-height: 180px; 
                font-size: 0.8rem; 
            }
        }
    </style>
</head>
<body>
    <div class="overlay" id="overlay"></div>
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <i class="fas fa-graduation-cap logo-icon"></i> 
            <span class="brand-name">Solid Rock</span>
        </div>

        <div class="main-menu-title">Super Admin Menu</div>
        <ul class="nav-menu">
            <li class="nav-item">
                <a href="#" class="nav-link active">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Overview</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="fees.php" class="nav-link">
                    <i class="fas fa-file-invoice-dollar"></i>
                    <span>Fees Mgt</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="manage_staff.php" class="nav-link">
                    <i class="fas fa-user-tie"></i>
                    <span>Manage Staff</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="student_records.php" class="nav-link">
                    <i class="fas fa-address-book"></i>
                    <span>Student Records</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="manage_students.php" class="nav-link">
                    <i class="fas fa-users-cog"></i>
                    <span>Manage Students</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="manage_admins.php" class="nav-link">
                    <i class="fas fa-user-shield"></i>
                    <span>Manage Admins</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="manage_parents.php" class="nav-link">
                    <i class="fas fa-user-friends"></i>
                    <span>Manage Parents</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="manage_super_admins.php" class="nav-link">
                    <i class="fas fa-crown"></i> 
                    <span>Super Admins</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="system_analysis.php" class="nav-link">
                    <i class="fas fa-chart-pie"></i> 
                    <span>System Analysis</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="../logout.php" class="nav-link">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>

        <div class="sidebar-footer">
            <div class="upgrade-section">
                <p><strong>Super Admin Access</strong></p>
                <a href="system_analysis.php" class="upgrade-btn">
                    <i class="fas fa-analytics"></i> System Analytics
                </a>
            </div>
        </div>
    </aside>

    <div class="main-wrapper">
        <header class="top-bar">
            <div style="display: flex; align-items: center;">
                <i class="fas fa-bars hamburger-menu" id="hamburgerMenu"></i>
                <div class="search-bar">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Search dashboard...">
                </div>
            </div>
            <div class="top-bar-actions">
                <i class="fas fa-bell action-icon" title="Notifications"></i>
                <i class="fas fa-cog action-icon" title="Settings"></i>
                <div class="user-avatar" title="<?= htmlspecialchars($super_admin_username); ?>">
                    <?= strtoupper(substr(htmlspecialchars($super_admin_username), 0, 1)); ?>
                </div>
            </div>
        </header>

        <main class="content-area">
            <div class="content-header">
                <h1 class="title">Super Admin Dashboard</h1>
                <div class="header-actions">
                    <button><i class="fas fa-filter"></i> Filter</button>
                    <button><i class="fas fa-download"></i> Export</button>
                    <button><i class="fas fa-share-alt"></i> Share</button>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="label">Total Students</div>
                    <div class="value"><?= number_format($total_students); ?></div>
                    <div class="comparison"><span class="positive"><i class="fas fa-arrow-up"></i> 5.2%</span> vs last month</div>
                </div>
                <div class="stat-card">
                    <div class="label">Staff Members</div>
                    <div class="value"><?= number_format($total_staff); ?></div>
                    <div class="comparison"><span class="positive"><i class="fas fa-arrow-up"></i> 2.1%</span> vs last month</div>
                </div>
                <div class="stat-card">
                    <div class="label">Total Classes</div>
                    <div class="value"><?= number_format($total_classes); ?></div>
                    <div class="comparison"><span class="negative"><i class="fas fa-arrow-down"></i> 0.5%</span> vs last month</div>
                </div>
                <div class="stat-card">
                    <div class="label">System Efficiency</div>
                    <div class="value"><?= $system_efficiency; ?></div>
                    <div class="comparison"><span class="positive"><i class="fas fa-arrow-up"></i> 1.0%</span> vs last evaluation</div>
                </div>
            </div>

            <div class="charts-grid">
                <div class="chart-container">
                    <div class="chart-title-section">
                        <div>
                            <div class="chart-title">Key Metrics Over Time</div>
                            <div class="chart-subtitle">Growth trends for the past 6 months</div>
                        </div>
                        <div class="chart-options"><i class="fas fa-ellipsis-h"></i></div>
                    </div>
                    <div class="chart-canvas-container">
                        <canvas id="keyMetricsChart"></canvas>
                    </div>
                </div>
                <div class="chart-container">
                    <div class="chart-title-section">
                        <div>
                            <div class="chart-title">Fees by Class</div>
                            <div class="chart-subtitle">Total fees collected per class</div>
                        </div>
                        <div class="chart-options"><i class="fas fa-ellipsis-h"></i></div>
                    </div>
                    <ul class="data-list">
                        <?php if (!empty($fees_by_class_data)): ?>
                            <?php foreach ($fees_by_class_data as $data): ?>
                                <li class="data-list-item">
                                    <span class="class-name"><?= htmlspecialchars($data['class']); ?></span>
                                    <span class="class-value">$<?= number_format($data['total_fees'], 2); ?></span>
                                </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li class="no-data">No fee data available by class.</li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>

            <div class="bottom-grid">
                <div class="bottom-card">
                    <div class="card-title">Academic Performance by Class</div>
                    <div class="chart-canvas-container">
                        <canvas id="passRateLineChart"></canvas>
                    </div>
                </div>
                <div class="bottom-card">
                    <div class="card-title">Student Attendance Overview</div>
                    <div class="donut-chart-container">
                        <canvas id="attendanceDonutChart"></canvas>
                    </div>
                </div>
                <div class="bottom-card">
                    <div class="card-title">Students & Parents Comparison</div>
                    <div class="chart-canvas-container">
                        <canvas id="studentParentBarChart"></canvas>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const hamburgerMenu = document.getElementById('hamburgerMenu');
            const sidebar = document.getElementById('sidebar');
            const mainWrapper = document.querySelector('.main-wrapper');
            const overlay = document.getElementById('overlay');
            const body = document.body;

            if (hamburgerMenu) {
                hamburgerMenu.addEventListener('click', function() {
                    sidebar.classList.toggle('active');
                    body.classList.toggle('sidebar-collapsed');
                    overlay.classList.toggle('active');
                });
            }
            
            if (overlay) {
                overlay.addEventListener('click', function() {
                    sidebar.classList.remove('active');
                    body.classList.add('sidebar-collapsed');
                    overlay.classList.remove('active');
                });
            }

            window.addEventListener('resize', function() {
                if (window.innerWidth > 992) {
                    sidebar.classList.remove('active');
                    body.classList.remove('sidebar-collapsed');
                    overlay.classList.remove('active');
                } else {
                    if (!sidebar.classList.contains('active')) {
                        body.classList.add('sidebar-collapsed');
                    }
                }
            });

            if (window.innerWidth <= 992) {
                body.classList.add('sidebar-collapsed');
                sidebar.classList.remove('active');
            } else {
                body.classList.remove('sidebar-collapsed');
            }

            // --- Chart.js Line Graph for Key Metrics ---
            const ctxKeyMetrics = document.getElementById('keyMetricsChart');
            if (ctxKeyMetrics) {
                const keyMetricsChartData = JSON.parse('<?= $key_metrics_chart_data_json; ?>');
                new Chart(ctxKeyMetrics, {
                    type: 'line',
                    data: keyMetricsChartData,
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            mode: 'index',
                            intersect: false,
                        },
                        scales: {
                            yIncome: {
                                type: 'linear',
                                display: true,
                                position: 'left',
                                title: {
                                    display: true,
                                    text: 'Fees Income ($)',
                                    color: '#d12c2c'
                                },
                                grid: {
                                    drawOnChartArea: false,
                                }
                            },
                            yCount: {
                                type: 'linear',
                                display: true,
                                position: 'right',
                                title: {
                                    display: true,
                                    text: 'Count',
                                    color: '#003366'
                                },
                                grid: {
                                    drawOnChartArea: true,
                                }
                            },
                            x: {
                                title: {
                                    display: true,
                                    text: 'Month'
                                }
                            }
                        },
                        plugins: {
                            tooltip: {
                                mode: 'index',
                                intersect: false
                            },
                            legend: {
                                position: 'top',
                            }
                        }
                    }
                });
            }

            // --- Chart.js Donut Chart for Attendance ---
            const ctxAttendance = document.getElementById('attendanceDonutChart');
            if (ctxAttendance) {
                const attendanceChartData = JSON.parse('<?= $attendance_chart_data_json; ?>');
                new Chart(ctxAttendance, {
                    type: 'doughnut',
                    data: attendanceChartData,
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        let label = context.label || '';
                                        if (label) {
                                            label += ': ';
                                        }
                                        if (context.parsed !== null) {
                                            label += context.parsed + '%';
                                        }
                                        return label;
                                    }
                                }
                            }
                        }
                    }
                });
            }

            // --- Chart.js Bar Chart for Students vs Parents ---
            const ctxStudentParentBar = document.getElementById('studentParentBarChart');
            if (ctxStudentParentBar) {
                const studentParentBarData = JSON.parse('<?= $student_parent_bar_chart_data_json; ?>');
                new Chart(ctxStudentParentBar, {
                    type: 'bar',
                    data: studentParentBarData,
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Count'
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return context.label + ': ' + context.parsed.y;
                                    }
                                }
                            }
                        }
                    }
                });
            }

            // --- Chart.js Line Chart for Pass Rate by Class ---
            const ctxPassRateLine = document.getElementById('passRateLineChart');
            if (ctxPassRateLine) {
                const passRateLineData = JSON.parse('<?= $pass_rate_line_chart_data_json; ?>');
                new Chart(ctxPassRateLine, {
                    type: 'line',
                    data: passRateLineData,
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                max: 100,
                                title: {
                                    display: true,
                                    text: 'Pass Rate (%)'
                                }
                            },
                            x: {
                                title: {
                                    display: true,
                                    text: 'Classes (Sorted by Performance)'
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return context.dataset.label + ': ' + context.parsed.y + '%';
                                    }
                                }
                            }
                        }
                    }
                });
            }
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>