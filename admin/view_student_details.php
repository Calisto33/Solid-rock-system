<?php
session_start();
include '../config.php'; // Database connection

// Helper function to safely display values and handle NULL
function safe_display($value, $default = 'N/A') {
    if ($value === null || $value === '') {
        return htmlspecialchars($default);
    }
    return htmlspecialchars($value);
}

// Function to calculate grade points for GPA based on your grading system
function getGradePoints($grade) {
    switch (strtoupper($grade)) {
        case 'A***': return 4.0;  // 100%
        case 'A**': return 3.9;   // 95-99%
        case 'A*': return 3.7;    // 90-94%
        case 'AA': return 3.5;    // 88-89%
        case 'AB': return 3.3;    // 85-87%
        case 'AC': return 3.0;    // 80-84%
        case 'BA': return 2.8;    // 78-79%
        case 'BB': return 2.5;    // 75-77%
        case 'BC': return 2.3;    // 70-74%
        case 'CA': return 2.0;    // 68-69%
        case 'CB': return 1.8;    // 65-67%
        case 'CC': return 1.5;    // 60-64%
        case 'DA': return 1.3;    // 58-59%
        case 'DB': return 1.1;    // 55-57%
        case 'DC': return 1.0;    // 50-54%
        case 'EA': return 0.8;    // 48-49%
        case 'EB': return 0.6;    // 45-47%
        case 'EC': return 0.4;    // 40-44%
        case 'F': return 0.2;     // 30-39%
        case 'G': return 0.0;     // <30%
        default: return 0.0;
    }
}

// Function to determine if grade is passing (50% and above)
function isPassing($grade) {
    $passingGrades = ['A***', 'A**', 'A*', 'AA', 'AB', 'AC', 'BA', 'BB', 'BC', 'CA', 'CB', 'CC', 'DA', 'DB', 'DC'];
    return in_array(strtoupper($grade), $passingGrades);
}

// Function to get grade category for styling
function getGradeCategory($grade) {
    $grade = strtoupper($grade);
    if (in_array($grade, ['A***', 'A**', 'A*'])) return 'excellent';
    if (in_array($grade, ['AA', 'AB', 'AC'])) return 'very-good';
    if (in_array($grade, ['BA', 'BB', 'BC'])) return 'good';
    if (in_array($grade, ['CA', 'CB', 'CC'])) return 'satisfactory';
    if (in_array($grade, ['DA', 'DB', 'DC'])) return 'marginal';
    if (in_array($grade, ['EA', 'EB', 'EC'])) return 'poor';
    if (in_array($grade, ['F', 'G'])) return 'fail';
    return 'unknown';
}

// Function to get percentage range for grade
function getGradePercentage($grade) {
    switch (strtoupper($grade)) {
        case 'A***': return '100%';
        case 'A**': return '95-99%';
        case 'A*': return '90-94%';
        case 'AA': return '88-89%';
        case 'AB': return '85-87%';
        case 'AC': return '80-84%';
        case 'BA': return '78-79%';
        case 'BB': return '75-77%';
        case 'BC': return '70-74%';
        case 'CA': return '68-69%';
        case 'CB': return '65-67%';
        case 'CC': return '60-64%';
        case 'DA': return '58-59%';
        case 'DB': return '55-57%';
        case 'DC': return '50-54%';
        case 'EA': return '48-49%';
        case 'EB': return '45-47%';
        case 'EC': return '40-44%';
        case 'F': return '30-39%';
        case 'G': return '<30%';
        default: return 'Unknown';
    }
}

// Check if student_id is provided in the URL
if (isset($_GET['student_id'])) {
    $student_id = $_GET['student_id'];
} elseif (isset($_GET['id'])) {
    // Also check for 'id' parameter for compatibility
    $student_id = $_GET['id'];
} else {
    die("Student ID is missing.");
}

// Handle result deletion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_result'])) {
    $result_id = $_POST['result_id'];
    $deleteQuery = "DELETE FROM results WHERE result_id = ?";
    $deleteStmt = $conn->prepare($deleteQuery);
    $deleteStmt->bind_param("i", $result_id);

    if (!$deleteStmt->execute()) {
        die("Error deleting result: " . $conn->error);
    }
    $deleteStmt->close();
    header("Location: view_student_details.php?student_id=$student_id");
    exit();
}

// Fetch comprehensive student information
$studentInfoQuery = "
    SELECT 
        s.id,
        s.student_id, 
        s.username, 
        s.first_name,
        s.last_name,
        s.course, 
        s.year, 
        s.class,
        s.created_at,
        u.email,
        u.role,
        u.created_at as account_created
    FROM students s
    LEFT JOIN users u ON s.user_id = u.id
    WHERE s.student_id = ? OR s.id = ?";

$studentInfoStmt = $conn->prepare($studentInfoQuery);
$studentInfoStmt->bind_param("ss", $student_id, $student_id);

if (!$studentInfoStmt->execute()) {
    die("Error fetching student information: " . $conn->error);
}

$studentInfoResult = $studentInfoStmt->get_result();
$studentInfo = $studentInfoResult->fetch_assoc();

if (!$studentInfo) {
    die("Student not found.");
}

// Use the actual database ID for further queries
$actual_student_id = $studentInfo['id'];

// Fetch results - updated to work with your actual database structure
$resultsQuery = "
    SELECT 
        r.result_id,
        r.subject_id,
        r.final_mark,
        r.final_grade,
        r.comments
    FROM results r
    WHERE r.student_id = ?
    ORDER BY r.subject_id";

$resultsStmt = $conn->prepare($resultsQuery);
$resultsStmt->bind_param("i", $actual_student_id);

if (!$resultsStmt->execute()) {
    die("Error executing results query: " . $conn->error);
}

$results = $resultsStmt->get_result();

// Fetch individual assessments for each result
$assessmentsQuery = "
    SELECT 
        ta.result_id,
        ta.assessment_name,
        ta.mark,
        ta.grade
    FROM term_assessments ta
    WHERE ta.student_id = ?
    ORDER BY ta.result_id, ta.assessment_name";

$assessmentsStmt = $conn->prepare($assessmentsQuery);
$assessmentsStmt->bind_param("i", $actual_student_id);

if (!$assessmentsStmt->execute()) {
    die("Error executing assessments query: " . $conn->error);
}

$assessmentsResult = $assessmentsStmt->get_result();

// Group assessments by result_id
$assessmentsByResult = [];
while ($assessment = $assessmentsResult->fetch_assoc()) {
    $assessmentsByResult[$assessment['result_id']][] = $assessment;
}

// Calculate statistics
$totalSubjects = 0;
$passedSubjects = 0;
$totalGradePoints = 0;
$grades = [];

$resultsArray = [];
while ($row = $results->fetch_assoc()) {
    // Get assessments for this result
    $assessments = isset($assessmentsByResult[$row['result_id']]) ? $assessmentsByResult[$row['result_id']] : [];
    
    // Add assessment data to the result row
    $row['assessments'] = $assessments;
    
    $resultsArray[] = $row;
    $totalSubjects++;
    
    if ($row['final_grade']) {
        $grades[] = $row['final_grade'];
        $totalGradePoints += getGradePoints($row['final_grade']);
        
        if (isPassing($row['final_grade'])) {
            $passedSubjects++;
        }
    }
}

$gpa = $totalSubjects > 0 ? round($totalGradePoints / $totalSubjects, 2) : 0;
$passRate = $totalSubjects > 0 ? round(($passedSubjects / $totalSubjects) * 100, 1) : 0;

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Details - <?= safe_display($studentInfo['first_name'] . ' ' . $studentInfo['last_name']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --primary-light: #4895ef;
            --secondary-color: #3f37c9;
            --accent-color: #4cc9f0;
            --danger-color: #ef476f;
            --success-color: #06d6a0;
            --warning-color: #ffd166;
            --text-color: #2b2d42;
            --text-light: #8d99ae;
            --bg-color: #f8f9fa;
            --card-bg: #ffffff;
            --border-radius: 12px;
            --box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Header Styles */
        .header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 1.5rem 2rem;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .header-container {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .header h2 {
            margin: 0;
            font-size: 1.8rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .header .link-btn {
            background-color: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            transition: var(--transition);
        }

        .header .link-btn:hover {
            background-color: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }

        /* Main Content Styles */
        .container {
            max-width: 1400px;
            width: 100%;
            margin: 2rem auto;
            padding: 0 1.5rem;
            flex: 1;
        }

        .content-box {
            background: var(--card-bg);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 2rem;
            overflow: hidden;
        }

        .box-header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .box-header h3 {
            color: var(--primary-color);
            font-size: 1.4rem;
            font-weight: 600;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .box-content {
            padding: 2rem;
        }

        /* Student Info Grid */
        .student-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .info-card {
            background: linear-gradient(135deg, rgba(67, 97, 238, 0.05), rgba(67, 97, 238, 0.02));
            border-radius: var(--border-radius);
            padding: 1.5rem;
            transition: var(--transition);
            border-left: 4px solid var(--primary-color);
            position: relative;
            overflow: hidden;
        }

        .info-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .info-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 50px;
            height: 50px;
            background: linear-gradient(45deg, transparent, rgba(67, 97, 238, 0.1));
            border-radius: 0 0 0 50px;
        }

        .info-card h4 {
            font-size: 0.85rem;
            color: var(--text-light);
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 500;
        }

        .info-card p {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--text-color);
            margin: 0;
        }

        .info-card .icon {
            position: absolute;
            top: 1rem;
            right: 1rem;
            font-size: 1.5rem;
            color: var(--primary-color);
            opacity: 0.6;
        }

        /* Statistics Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--card-bg);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            text-align: center;
            box-shadow: var(--box-shadow);
            transition: var(--transition);
            border-top: 3px solid var(--primary-color);
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card.success {
            border-top-color: var(--success-color);
        }

        .stat-card.warning {
            border-top-color: var(--warning-color);
        }

        .stat-card.danger {
            border-top-color: var(--danger-color);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .stat-card.success .stat-number {
            color: var(--success-color);
        }

        .stat-card.warning .stat-number {
            color: var(--warning-color);
        }

        .stat-card.danger .stat-number {
            color: var(--danger-color);
        }

        .stat-card .stat-number {
            color: var(--primary-color);
        }

        .stat-label {
            color: var(--text-light);
            font-size: 0.9rem;
            font-weight: 500;
        }

        /* Table Styles */
        .table-responsive {
            overflow-x: auto;
            margin-top: 1rem;
            border-radius: var(--border-radius);
        }

        .table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            color: var(--text-color);
        }

        .table th, 
        .table td {
            padding: 1rem 1.5rem;
            text-align: left;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .table th {
            background: linear-gradient(135deg, rgba(67, 97, 238, 0.1), rgba(67, 97, 238, 0.05));
            color: var(--primary-color);
            font-weight: 600;
            position: sticky;
            top: 0;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .table tr {
            transition: var(--transition);
        }

        .table tbody tr:hover {
            background-color: rgba(67, 97, 238, 0.03);
            transform: scale(1.005);
        }

        .table td:first-child {
            font-weight: 500;
        }

        /* Button Styles */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.7rem 1.5rem;
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 500;
            transition: var(--transition);
            cursor: pointer;
            border: none;
            outline: none;
            text-align: center;
            font-size: 0.9rem;
        }

        .link-btn {
            background-color: var(--primary-color);
        }

        .link-btn:hover {
            background-color: var(--secondary-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(67, 97, 238, 0.3);
        }

        .delete-btn {
            background-color: var(--danger-color);
            width: 36px;
            height: 36px;
            border-radius: 50%;
            padding: 0;
            font-size: 0.9rem;
        }

        .delete-btn:hover {
            background-color: #e02c57;
            transform: scale(1.1);
        }

        /* Grade Pills - Updated for your grading system */
        .grade-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.4rem 1rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.85rem;
            min-width: 60px;
            text-transform: uppercase;
            position: relative;
        }

        /* Excellent Grades (A***, A**, A*) */
        .grade-excellent {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.2), rgba(16, 185, 129, 0.1));
            color: #059669;
            border: 1px solid rgba(16, 185, 129, 0.3);
            box-shadow: 0 2px 8px rgba(16, 185, 129, 0.2);
        }

        /* Very Good Grades (Aa, Ab, Ac) */
        .grade-very-good {
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.2), rgba(34, 197, 94, 0.1));
            color: #16a34a;
            border: 1px solid rgba(34, 197, 94, 0.3);
        }

        /* Good Grades (Ba, Bb, Bc) */
        .grade-good {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.2), rgba(59, 130, 246, 0.1));
            color: #2563eb;
            border: 1px solid rgba(59, 130, 246, 0.3);
        }

        /* Satisfactory Grades (Ca, Cb, Cc) */
        .grade-satisfactory {
            background: linear-gradient(135deg, rgba(168, 85, 247, 0.2), rgba(168, 85, 247, 0.1));
            color: #7c3aed;
            border: 1px solid rgba(168, 85, 247, 0.3);
        }

        /* Marginal Grades (Da, Db, Dc) */
        .grade-marginal {
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.2), rgba(245, 158, 11, 0.1));
            color: #d97706;
            border: 1px solid rgba(245, 158, 11, 0.3);
        }

        /* Poor Grades (Ea, Eb, Ec) */
        .grade-poor {
            background: linear-gradient(135deg, rgba(251, 146, 60, 0.2), rgba(251, 146, 60, 0.1));
            color: #ea580c;
            border: 1px solid rgba(251, 146, 60, 0.3);
        }

        /* Fail Grades (F, G) */
        .grade-fail {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.2), rgba(239, 68, 68, 0.1));
            color: #dc2626;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        /* Grade tooltip */
        .grade-pill::after {
            content: attr(data-percentage);
            position: absolute;
            top: -35px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 0.3rem 0.6rem;
            border-radius: 4px;
            font-size: 0.7rem;
            white-space: nowrap;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
            z-index: 10;
        }

        .grade-pill:hover::after {
            opacity: 1;
        }

        /* Progress Bar */
        .progress-bar {
            width: 100%;
            height: 8px;
            background-color: rgba(0, 0, 0, 0.1);
            border-radius: 4px;
            overflow: hidden;
            margin-top: 0.5rem;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--success-color), var(--primary-color));
            transition: width 0.6s ease;
        }

        /* Footer */
        .footer {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 1.5rem;
            text-align: center;
            margin-top: auto;
        }

        .footer p {
            margin: 0;
            opacity: 0.9;
            font-size: 0.95rem;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
        }

        .empty-state i {
            font-size: 3rem;
            color: var(--text-light);
            margin-bottom: 1rem;
        }

        .empty-state p {
            font-size: 1.1rem;
            color: var(--text-light);
            margin-bottom: 1rem;
        }

        /* Responsive Styles */
        @media screen and (max-width: 992px) {
            .header-container {
                flex-direction: column;
                align-items: flex-start;
            }

            .box-content {
                padding: 1.5rem;
            }

            .student-info-grid {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            }
        }

        @media screen and (max-width: 768px) {
            .container {
                margin: 1rem auto;
                padding: 0 1rem;
            }

            .table th, 
            .table td {
                padding: 0.8rem 1rem;
            }

            .student-info-grid {
                grid-template-columns: 1fr;
            }

            .box-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
        }

        @media screen and (max-width: 576px) {
            .header h2 {
                font-size: 1.4rem;
            }

            .box-content {
                padding: 1rem;
            }

            .table .hide-sm {
                display: none;
            }

            .table th, 
            .table td {
                padding: 0.8rem 0.6rem;
                font-size: 0.9rem;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-container">
            <h2>
                <i class="fas fa-user-graduate"></i>
                Student Details
            </h2>
            <a href="student_information.php" class="btn link-btn">
                <i class="fas fa-arrow-left"></i> Back to Student List
            </a>
        </div>
    </header>

    <div class="container">
        <!-- Student Profile Information -->
        <div class="content-box">
            <div class="box-header">
                <h3>
                    <i class="fas fa-id-card"></i>
                    Student Profile
                </h3>
            </div>
            <div class="box-content">
                <div class="student-info-grid">
                    <div class="info-card">
                        <i class="fas fa-user icon"></i>
                        <h4>Full Name</h4>
                        <p>
                            <?php 
                            $fullName = trim(($studentInfo['first_name'] ?: '') . ' ' . ($studentInfo['last_name'] ?: ''));
                            echo safe_display($fullName ?: $studentInfo['username'] ?: 'Unknown');
                            ?>
                        </p>
                    </div>
                    <div class="info-card">
                        <i class="fas fa-hashtag icon"></i>
                        <h4>Student ID</h4>
                        <p><?= safe_display($studentInfo['student_id']) ?></p>
                    </div>
                    <div class="info-card">
                        <i class="fas fa-users icon"></i>
                        <h4>Class</h4>
                        <p><?= safe_display($studentInfo['class']) ?></p>
                    </div>
                    <div class="info-card">
                        <i class="fas fa-graduation-cap icon"></i>
                        <h4>Course</h4>
                        <p><?= safe_display($studentInfo['course']) ?></p>
                    </div>
                    <div class="info-card">
                        <i class="fas fa-calendar icon"></i>
                        <h4>Academic Year</h4>
                        <p><?= safe_display($studentInfo['year']) ?></p>
                    </div>
                    <div class="info-card">
                        <i class="fas fa-envelope icon"></i>
                        <h4>Email</h4>
                        <p><?= safe_display($studentInfo['email']) ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Academic Performance Statistics -->
        <div class="content-box">
            <div class="box-header">
                <h3>
                    <i class="fas fa-chart-bar"></i>
                    Academic Performance Overview
                </h3>
            </div>
            <div class="box-content">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number"><?= $totalSubjects ?></div>
                        <div class="stat-label">Total Subjects</div>
                    </div>
                    <div class="stat-card success">
                        <div class="stat-number"><?= $passedSubjects ?></div>
                        <div class="stat-label">Subjects Passed</div>
                    </div>
                    <div class="stat-card <?= $passRate >= 70 ? 'success' : ($passRate >= 50 ? 'warning' : 'danger') ?>">
                        <div class="stat-number"><?= $passRate ?>%</div>
                        <div class="stat-label">Pass Rate</div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?= $passRate ?>%"></div>
                        </div>
                    </div>
                    <div class="stat-card <?= $gpa >= 3.0 ? 'success' : ($gpa >= 2.0 ? 'warning' : 'danger') ?>">
                        <div class="stat-number"><?= $gpa ?></div>
                        <div class="stat-label">GPA (4.0 Scale)</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Assessment Results Table -->
        <div class="content-box">
            <div class="box-header">
                <h3>
                    <i class="fas fa-clipboard-list"></i>
                    Assessment Results
                </h3>
            </div>
            <div class="box-content">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Subject</th>
                                <th class="hide-sm">Assessments</th>
                                <th>Final Mark</th>
                                <th>Final Grade</th>
                                <th>Pass/Fail</th>
                                <th class="hide-sm">Comments</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($resultsArray) > 0): ?>
                                <?php foreach ($resultsArray as $row): 
                                    $gradeCategory = getGradeCategory($row['final_grade']);
                                    $gradePercentage = getGradePercentage($row['final_grade']);
                                    $isPassingGrade = isPassing($row['final_grade']);
                                ?>
                                <tr>
                                    <td><strong><?= safe_display('Subject ' . $row['subject_id']) ?></strong></td>
                                    <td class="hide-sm">
                                        <?php if (!empty($row['assessments'])): ?>
                                            <div style="display: flex; flex-direction: column; gap: 0.3rem;">
                                                <?php foreach ($row['assessments'] as $assessment): ?>
                                                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.2rem 0.5rem; background: rgba(0,0,0,0.05); border-radius: 4px; font-size: 0.85rem;">
                                                        <span><?= safe_display($assessment['assessment_name']) ?></span>
                                                        <span style="font-weight: 600;"><?= safe_display($assessment['mark']) ?>%</span>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php else: ?>
                                            <span style="color: #999; font-style: italic;">No assessments</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong><?= safe_display($row['final_mark']) ?>%</strong></td>
                                    <td>
                                        <span class="grade-pill grade-<?= $gradeCategory ?>" data-percentage="<?= $gradePercentage ?>">
                                            <?= safe_display($row['final_grade']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($isPassingGrade): ?>
                                            <span class="grade-pill grade-excellent">
                                                <i class="fas fa-check"></i> PASS
                                            </span>
                                        <?php else: ?>
                                            <span class="grade-pill grade-fail">
                                                <i class="fas fa-times"></i> FAIL
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="hide-sm"><?= safe_display($row['comments']) ?></td>
                                    <td>
                                        <form action="view_student_details.php?student_id=<?= $student_id ?>" method="POST" onsubmit="return confirm('Are you sure you want to delete this result?');">
                                            <input type="hidden" name="result_id" value="<?= $row['result_id'] ?>">
                                            <button type="submit" name="delete_result" class="btn delete-btn" title="Delete Result">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7">
                                        <div class="empty-state">
                                            <i class="fas fa-clipboard"></i>
                                            <p>No assessment results found for this student.</p>
                                            <a href="add_results.php?student_id=<?= $student_id ?>" class="btn link-btn">
                                                <i class="fas fa-plus"></i> Add Results
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; <?php echo date("Y"); ?> Wisetech College Portal | All Rights Reserved</p>
    </footer>
</body>
</html>

<?php
// Close statements and connection
$studentInfoStmt->close();
$resultsStmt->close();
$assessmentsStmt->close();
$conn->close();
?>