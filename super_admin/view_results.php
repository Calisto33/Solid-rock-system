<?php
session_start();
include '../config.php';

// Verify super admin session
if (!isset($_SESSION['super_admin_id'])) {
    header("Location: super_admin_login.php");
    exit();
}

// Fetch student ID
$student_id = $_GET['student_id'] ?? null;
if (!$student_id) {
    die("Error: Student ID is missing.");
}

// Function to get formatted student name (same as in other files)
function getFormattedStudentName($first_name, $last_name, $username, $student_id) {
    $first_name = trim($first_name ?? '');
    $last_name = trim($last_name ?? '');
    $username = trim($username ?? '');
    
    // If we have both first and last name
    if (!empty($first_name) && !empty($last_name)) {
        return $first_name . ' ' . $last_name;
    }
    // If we have only first name
    elseif (!empty($first_name)) {
        return $first_name;
    }
    // If we have only last name
    elseif (!empty($last_name)) {
        return $last_name;
    }
    // If we have username
    elseif (!empty($username)) {
        return $username;
    }
    // Fallback to student ID
    else {
        return 'Student ' . $student_id;
    }
}

// Fetch student details - UPDATED to get proper student name
$studentQuery = "SELECT student_id, first_name, last_name, username, class, status FROM students WHERE student_id = ?";
$stmt = $conn->prepare($studentQuery);
$stmt->bind_param("s", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
if (!$student) {
    die("Error: Student not found.");
}

// Format the student name
$student_display_name = getFormattedStudentName(
    $student['first_name'], 
    $student['last_name'], 
    $student['username'], 
    $student['student_id']
);

// Fetch results for the student - CORRECTED QUERY based on actual database structure
$resultsQuery = "
    SELECT 
        subject, 
        term, 
        academic_year,
        marks_obtained,
        total_marks,
        final_mark, 
        grade,
        exam_date,
        comments,
        exam_type
    FROM results 
    WHERE student_id = ?
    ORDER BY academic_year DESC, term, subject";

$stmt = $conn->prepare($resultsQuery);
$stmt->bind_param("s", $student_id);
$stmt->execute();
$results = $stmt->get_result();

// Function to calculate percentage
function calculatePercentage($marks_obtained, $total_marks) {
    if ($total_marks > 0) {
        return round(($marks_obtained / $total_marks) * 100, 1);
    }
    return 0;
}

// Function to determine grade color
function getGradeColor($grade) {
    switch (strtoupper(trim($grade))) {
        case 'A':
        case 'A+':
            return '#10b981'; // Green
        case 'B':
        case 'B+':
            return '#3b82f6'; // Blue
        case 'C':
        case 'C+':
            return '#f59e0b'; // Orange
        case 'D':
        case 'D+':
            return '#ef4444'; // Red
        case 'F':
            return '#dc2626'; // Dark red
        default:
            return '#6b7280'; // Gray
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<<<<<<< HEAD
    <link rel="icon" type="image/jpeg" href="../images/logo.jpeg">
    <link rel="shortcut icon" type="image/jpeg" href="../images/logo.jpeg">
    <title>Student Results | Solid Rock</title>
=======
    <title>Student Results - <?= htmlspecialchars($student_display_name) ?> | Wisetech College</title>
>>>>>>> b291daf7f49078bb0cccb1439969ad4a74e2db38
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2563eb;
            --primary-dark: #1e40af;
            --secondary-color: #0f172a;
            --accent-color: #3b82f6;
            --accent-light: #93c5fd;
            --text-color: #1e293b;
            --text-light: #64748b;
            --white: #ffffff;
            --off-white: #f8fafc;
            --gray-light: #f1f5f9;
            --gray-mid: #e2e8f0;
            --border-radius: 0.75rem;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --transition: all 0.3s ease;
            --font-main: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: var(--font-main);
            background: var(--off-white);
            color: var(--text-color);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            line-height: 1.6;
        }

        .header {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: var(--white);
            padding: 1.25rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--shadow-md);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .header-logo {
            height: 45px;
            width: auto;
            border-radius: 8px;
            box-shadow: var(--shadow-sm);
        }

        .header-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin: 0;
        }

        .header-nav {
            display: flex;
            gap: 1rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            font-weight: 500;
            padding: 0.6rem 1.25rem;
            border-radius: var(--border-radius);
            text-decoration: none;
            transition: var(--transition);
            cursor: pointer;
            border: none;
            font-size: 0.95rem;
        }

        .btn-primary {
            background-color: var(--white);
            color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: var(--gray-light);
            transform: translateY(-2px);
        }

        .btn-secondary {
            background-color: var(--accent-color);
            color: var(--white);
        }

        .btn-secondary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }

        .container {
            max-width: 1200px;
            width: 92%;
            margin: 2rem auto;
            flex: 1;
        }

        .card {
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-md);
            overflow: hidden;
            transition: var(--transition);
            margin-bottom: 2rem;
        }

        .card:hover {
            box-shadow: var(--shadow-lg);
        }

        .card-header {
            background: var(--gray-light);
            padding: 1.5rem 2rem;
            border-bottom: 1px solid var(--gray-mid);
        }

        .student-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .student-details {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .student-name {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--primary-dark);
            margin: 0;
        }

        .student-id {
            font-size: 0.9rem;
            color: var(--text-light);
            font-weight: 500;
        }

        .student-class {
            display: inline-block;
            padding: 0.5rem 1rem;
            background-color: var(--accent-light);
            color: var(--primary-dark);
            border-radius: var(--border-radius);
            font-weight: 500;
            font-size: 0.9rem;
        }

        .card-body {
            padding: 1.5rem 2rem;
        }

        .summary-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--gray-light);
            padding: 1.5rem;
            border-radius: var(--border-radius);
            text-align: center;
            border-left: 4px solid var(--primary-color);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
            display: block;
        }

        .stat-label {
            font-size: 0.9rem;
            color: var(--text-light);
            margin-top: 0.5rem;
        }

        .table-wrapper {
            overflow-x: auto;
            border-radius: calc(var(--border-radius) - 4px);
            box-shadow: var(--shadow-sm);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            white-space: nowrap;
            min-width: 800px;
        }

        th, td {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid var(--gray-mid);
        }

        th {
            background-color: var(--primary-color);
            color: var(--white);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.05em;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        th:first-child {
            border-top-left-radius: 0.5rem;
        }

        th:last-child {
            border-top-right-radius: 0.5rem;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tbody tr:hover {
            background-color: var(--gray-light);
        }

        .grade-cell {
            font-weight: 700;
            font-size: 1.1rem;
        }

        .percentage-cell {
            font-weight: 600;
        }

        .actions {
            margin-top: 2rem;
            display: flex;
            justify-content: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .no-results {
            text-align: center;
            padding: 3rem;
            color: var(--text-light);
        }

        .no-results i {
            font-size: 4rem;
            margin-bottom: 1rem;
            color: var(--gray-mid);
        }

        .footer {
            background-color: var(--secondary-color);
            color: var(--white);
            text-align: center;
            padding: 1.5rem;
            margin-top: 3rem;
        }

        .footer-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .footer-logo {
            height: 40px;
            border-radius: 8px;
        }

        .footer-text {
            opacity: 0.8;
            font-size: 0.9rem;
        }

        /* Mobile Menu */
        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            color: var(--white);
            font-size: 1.5rem;
            cursor: pointer;
        }

        /* Enhanced Print Styles */
        @media print {
            * {
                -webkit-print-color-adjust: exact !important;
                color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            
            @page {
                margin: 0.75in;
                size: A4;
            }
            
            body {
                background: white !important;
                font-size: 12pt;
                line-height: 1.4;
                color: #000 !important;
            }
            
            .header {
                background: linear-gradient(135deg, var(--primary-color), var(--primary-dark)) !important;
                color: white !important;
                page-break-inside: avoid;
                margin-bottom: 20pt;
                padding: 15pt 20pt;
                border-radius: 0;
                position: static;
                box-shadow: none;
                -webkit-print-color-adjust: exact;
            }
            
            .header-nav,
            .mobile-menu-btn {
                display: none !important;
            }
            
            .header-title {
                font-size: 18pt !important;
                font-weight: bold;
            }
            
            .container {
                width: 100% !important;
                max-width: none !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            
            .card {
                box-shadow: none !important;
                border: 1pt solid #ddd !important;
                border-radius: 0 !important;
                margin: 0 !important;
                page-break-inside: avoid;
                background: white !important;
            }
            
            .card-header {
                background: #f8f9fa !important;
                border-bottom: 2pt solid #e9ecef !important;
                padding: 15pt 20pt !important;
                page-break-inside: avoid;
                -webkit-print-color-adjust: exact;
            }
            
            .student-name {
                font-size: 16pt !important;
                font-weight: bold !important;
                color: #1e40af !important;
                margin-bottom: 5pt;
            }
            
            .student-id {
                font-size: 11pt !important;
                color: #666 !important;
                margin-bottom: 8pt;
            }
            
            .student-class {
                background: #e3f2fd !important;
                color: #1976d2 !important;
                padding: 5pt 10pt !important;
                border-radius: 4pt !important;
                font-size: 10pt !important;
                font-weight: bold !important;
                border: 1pt solid #bbdefb !important;
                -webkit-print-color-adjust: exact;
            }
            
            .card-body {
                padding: 15pt 20pt !important;
            }
            
            .summary-stats {
                display: grid !important;
                grid-template-columns: repeat(4, 1fr) !important;
                gap: 10pt !important;
                margin-bottom: 20pt !important;
                page-break-inside: avoid;
            }
            
            .stat-card {
                background: #f8fafc !important;
                border: 1pt solid #e2e8f0 !important;
                border-left: 4pt solid var(--primary-color) !important;
                padding: 12pt !important;
                text-align: center !important;
                border-radius: 4pt !important;
                -webkit-print-color-adjust: exact;
            }
            
            .stat-number {
                font-size: 18pt !important;
                font-weight: bold !important;
                color: #2563eb !important;
                display: block !important;
                margin-bottom: 3pt;
            }
            
            .stat-label {
                font-size: 9pt !important;
                color: #666 !important;
                text-transform: uppercase;
                letter-spacing: 0.5pt;
            }
            
            .table-wrapper {
                overflow: visible !important;
                border-radius: 0 !important;
                box-shadow: none !important;
                page-break-inside: auto;
            }
            
            table {
                width: 100% !important;
                border-collapse: collapse !important;
                min-width: auto !important;
                font-size: 10pt !important;
                page-break-inside: auto;
            }
            
            th {
                background: #2563eb !important;
                color: white !important;
                font-weight: bold !important;
                font-size: 9pt !important;
                padding: 8pt 6pt !important;
                text-align: center !important;
                border: 1pt solid #1e40af !important;
                page-break-inside: avoid;
                page-break-after: avoid;
                -webkit-print-color-adjust: exact;
            }
            
            td {
                padding: 6pt 4pt !important;
                font-size: 9pt !important;
                border: 1pt solid #ddd !important;
                text-align: center !important;
                page-break-inside: avoid;
                background: white !important;
            }
            
            tbody tr:nth-child(even) td {
                background: #fafbfc !important;
                -webkit-print-color-adjust: exact;
            }
            
            .grade-cell {
                font-weight: bold !important;
                font-size: 11pt !important;
            }
            
            .percentage-cell {
                font-weight: bold !important;
                font-size: 10pt !important;
            }
            
            .no-results {
                text-align: center !important;
                padding: 30pt !important;
                color: #666 !important;
                page-break-inside: avoid;
            }
            
            .no-results i {
                font-size: 24pt !important;
                margin-bottom: 10pt !important;
                color: #ccc !important;
            }
            
            .actions {
                display: none !important;
            }
            
            .footer {
                background: #0f172a !important;
                color: white !important;
                padding: 15pt !important;
                margin-top: 20pt !important;
                text-align: center !important;
                page-break-inside: avoid;
                -webkit-print-color-adjust: exact;
            }
            
            .footer-text {
                font-size: 10pt !important;
                opacity: 1 !important;
            }
            
            /* Ensure proper page breaks */
            .card-header {
                page-break-after: avoid;
            }
            
            .summary-stats {
                page-break-after: avoid;
            }
            
            thead {
                display: table-header-group;
                page-break-inside: avoid;
                page-break-after: avoid;
            }
            
            tbody {
                display: table-row-group;
            }
            
            tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }
            
            /* Specific adjustments for better print layout */
            .student-info {
                display: flex !important;
                justify-content: space-between !important;
                align-items: flex-start !important;
                flex-wrap: nowrap !important;
                gap: 15pt !important;
            }
            
            .student-details {
                flex: 1;
            }
            
            /* Hide elements that shouldn't print */
            .btn,
            button,
            .mobile-menu-btn,
            .header-nav {
                display: none !important;
            }
            
            /* Print-specific header layout */
            .header-left {
                display: flex !important;
                align-items: center !important;
                gap: 15pt !important;
                width: 100%;
                justify-content: center !important;
            }
            
            .header-logo {
                height: 35pt !important;
                width: auto !important;
            }
            
            /* Ensure colors print correctly */
            * {
                -webkit-print-color-adjust: exact !important;
                color-adjust: exact !important;
            }
        }

        /* Responsive Design */
        @media screen and (max-width: 992px) {
            th, td {
                padding: 0.8rem 1rem;
            }
            
            .summary-stats {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            }
        }

        @media screen and (max-width: 768px) {
            .header {
                padding: 1rem 1.5rem;
            }

            .header-title {
                font-size: 1.25rem;
            }

            .mobile-menu-btn {
                display: block;
            }

            .header-nav {
                position: fixed;
                top: 73px;
                right: -100%;
                width: 70%;
                height: calc(100vh - 73px);
                background-color: var(--secondary-color);
                flex-direction: column;
                padding: 2rem;
                transition: right 0.3s ease;
                z-index: 99;
            }

            .header-nav.active {
                right: 0;
            }

            .container {
                width: 95%;
                margin: 1rem auto;
            }

            .card-header,
            .card-body {
                padding: 1.25rem 1.5rem;
            }

            th, td {
                padding: 0.75rem 0.5rem;
                font-size: 0.9rem;
            }

            .student-name {
                font-size: 1.25rem;
            }

            .student-info {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
        }

        @media screen and (max-width: 576px) {
            .summary-stats {
                grid-template-columns: 1fr;
            }
            
            th, td {
                padding: 0.6rem 0.4rem;
                font-size: 0.85rem;
            }

            .actions {
                flex-direction: column;
                align-items: center;
            }

            .btn {
                width: 100%;
                max-width: 300px;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-left">
            <img src="../images/logo.jpeg" alt="Solid Rock Logo" class="header-logo">
            <h1 class="header-title">Student Results</h1>
        </div>
        
        <button class="mobile-menu-btn" id="mobileMenuBtn">
            <i class="fas fa-bars"></i>
        </button>
        
        <nav class="header-nav" id="headerNav">
            <a href="student_records.php" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i> Back to Records
            </a>
            <a href="super_admin_dashboard.php" class="btn btn-primary">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a href="../logout.php" class="btn btn-secondary">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </nav>
    </header>

    <div class="container">
        <div class="card">
            <div class="card-header">
                <div class="student-info">
                    <div class="student-details">
                        <h2 class="student-name">
                            <i class="fas fa-user-graduate"></i>
                            <?= htmlspecialchars($student_display_name) ?>
                        </h2>
                        <div class="student-id">
                            <i class="fas fa-id-card"></i>
                            Student ID: <?= htmlspecialchars($student['student_id']) ?>
                        </div>
                    </div>
                    <span class="student-class">
                        <i class="fas fa-users"></i>
                        Class: <?= htmlspecialchars($student['class'] ?? 'Unassigned') ?>
                    </span>
                </div>
            </div>
            
            <div class="card-body">
                <?php if ($results->num_rows > 0): ?>
                    <?php
                    // Calculate summary statistics
                    $total_subjects = 0;
                    $total_percentage = 0;
                    $grades = [];
                    $results_array = [];
                    
                    // Store results in array for processing
                    while ($row = $results->fetch_assoc()) {
                        $results_array[] = $row;
                        if ($row['total_marks'] > 0) {
                            $percentage = calculatePercentage($row['marks_obtained'], $row['total_marks']);
                            $total_percentage += $percentage;
                            $total_subjects++;
                        }
                        if (!empty($row['grade'])) {
                            $grades[] = $row['grade'];
                        }
                    }
                    
                    $average_percentage = $total_subjects > 0 ? round($total_percentage / $total_subjects, 1) : 0;
                    $unique_subjects = count(array_unique(array_column($results_array, 'subject')));
                    ?>
                    
                    <div class="summary-stats">
                        <div class="stat-card">
                            <span class="stat-number"><?= $total_subjects ?></span>
                            <div class="stat-label">Total Exams</div>
                        </div>
                        <div class="stat-card">
                            <span class="stat-number"><?= $unique_subjects ?></span>
                            <div class="stat-label">Subjects</div>
                        </div>
                        <div class="stat-card">
                            <span class="stat-number"><?= $average_percentage ?>%</span>
                            <div class="stat-label">Average Score</div>
                        </div>
                        <div class="stat-card">
                            <span class="stat-number"><?= count($grades) ?></span>
                            <div class="stat-label">Graded Exams</div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th><i class="fas fa-book"></i> Subject</th>
                                <th><i class="fas fa-calendar-alt"></i> Term</th>
                                <th><i class="fas fa-calendar-day"></i> Year</th>
                                <th><i class="fas fa-pencil-alt"></i> Exam Type</th>
                                <th><i class="fas fa-clipboard-check"></i> Marks</th>
                                <th><i class="fas fa-percentage"></i> Percentage</th>
                                <th><i class="fas fa-award"></i> Grade</th>
                                <th><i class="fas fa-calendar"></i> Date</th>
                                <th><i class="fas fa-comment"></i> Comments</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if ($results->num_rows > 0): 
                                foreach ($results_array as $row):
                                    $percentage = calculatePercentage($row['marks_obtained'], $row['total_marks']);
                                    $grade_color = getGradeColor($row['grade']);
                            ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($row['subject']) ?></strong></td>
                                    <td><?= htmlspecialchars($row['term'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($row['academic_year'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($row['exam_type'] ?? 'Final Exam') ?></td>
                                    <td>
                                        <?= htmlspecialchars($row['marks_obtained']) ?> / <?= htmlspecialchars($row['total_marks']) ?>
                                        <?php if ($row['final_mark']): ?>
                                            <br><small style="color: var(--text-light);">Final: <?= htmlspecialchars($row['final_mark']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="percentage-cell">
                                        <span style="color: <?= $percentage >= 70 ? '#10b981' : ($percentage >= 50 ? '#f59e0b' : '#ef4444') ?>">
                                            <?= $percentage ?>%
                                        </span>
                                    </td>
                                    <td class="grade-cell" style="color: <?= $grade_color ?>">
                                        <?= htmlspecialchars($row['grade'] ?: 'N/A') ?>
                                    </td>
                                    <td>
                                        <?= $row['exam_date'] ? date('M j, Y', strtotime($row['exam_date'])) : 'N/A' ?>
                                    </td>
                                    <td><?= htmlspecialchars($row['comments'] ?: 'No comments') ?></td>
                                </tr>
                            <?php 
                                endforeach;
                            else: 
                            ?>
                                <tr>
                                    <td colspan="9" class="no-results">
                                        <div>
                                            <i class="fas fa-chart-line"></i>
                                            <h3>No Results Found</h3>
                                            <p>No academic results have been recorded for this student yet.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="actions">
                    <a href="student_records.php" class="btn btn-primary">
                        <i class="fas fa-arrow-left"></i> Back to Student Records
                    </a>
                    <button onclick="window.print()" class="btn btn-secondary">
                        <i class="fas fa-print"></i> Print Results
                    </button>
                    <button onclick="downloadPDF()" class="btn btn-secondary">
                        <i class="fas fa-file-pdf"></i> Download PDF
                    </button>
                </div>
            </div>
        </div>
    </div>

    <footer class="footer">
        <div class="footer-content">
            <img src="../images/logo.jpg" alt="Solid Rock Logo" class="footer-logo">
            <p class="footer-text">&copy; <?php echo date("Y"); ?> Mirilax-Scales Portal. All rights reserved.</p>
        </div>
    </footer>

    <script>
        // Mobile menu toggle
        document.getElementById('mobileMenuBtn').addEventListener('click', function() {
            document.getElementById('headerNav').classList.toggle('active');
            
            // Change icon based on menu state
            const icon = this.querySelector('i');
            if (icon.classList.contains('fa-bars')) {
                icon.classList.remove('fa-bars');
                icon.classList.add('fa-times');
            } else {
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
            }
        });

        // Close mobile menu when clicking outside
        document.addEventListener('click', function(event) {
            const nav = document.getElementById('headerNav');
            const btn = document.getElementById('mobileMenuBtn');
            
            if (!nav.contains(event.target) && !btn.contains(event.target)) {
                nav.classList.remove('active');
                const icon = btn.querySelector('i');
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
            }
        });

        // Enhanced print function
        function printResults() {
            // Add print-specific classes
            document.body.classList.add('printing');
            
            // Trigger print
            window.print();
            
            // Remove print classes after printing
            setTimeout(() => {
                document.body.classList.remove('printing');
            }, 1000);
        }

        // PDF download function (requires html2pdf library)
        function downloadPDF() {
            // Check if html2pdf is available
            if (typeof html2pdf === 'undefined') {
                alert('PDF download feature requires additional setup. Please use the print function instead.');
                return;
            }

            const element = document.querySelector('.container');
            const studentName = '<?= addslashes($student_display_name) ?>';
            const filename = `${studentName.replace(/[^a-z0-9]/gi, '_')}_Results.pdf`;

            const opt = {
                margin: 0.5,
                filename: filename,
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2 },
                jsPDF: { unit: 'in', format: 'a4', orientation: 'portrait' }
            };

            html2pdf().set(opt).from(element).save();
        }

        // Print optimization
        window.addEventListener('beforeprint', function() {
            // Optimize layout for printing
            const tables = document.querySelectorAll('table');
            tables.forEach(table => {
                table.style.fontSize = '10pt';
            });
        });

        window.addEventListener('afterprint', function() {
            // Restore normal layout
            const tables = document.querySelectorAll('table');
            tables.forEach(table => {
                table.style.fontSize = '';
            });
        });
    </script>
</body>
</html>

<?php $stmt->close(); $conn->close(); ?>