<?php
session_start();
include '../config.php';

// Check if the user is logged in and is a parent
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'parent') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// --- FETCH ALL CHILDREN ASSIGNED TO THIS PARENT ---
<<<<<<< HEAD
// Updated to use the correct table name: student_parent_relationships (not parent_student_relationships)
=======
// Fixed table name: student_parent_relationships instead of parent_student_relationships
>>>>>>> b291daf7f49078bb0cccb1439969ad4a74e2db38
$childrenQuery = "
    SELECT DISTINCT
        s.student_id as student_internal_id,
        s.student_id,
        COALESCE(NULLIF(CONCAT_WS(' ', s.first_name, s.last_name), ''), s.username) AS student_name,
        s.first_name,
        s.last_name,
        s.username,
        s.class
    FROM student_parent_relationships spr
    INNER JOIN parents p ON spr.parent_id = p.parent_id
    INNER JOIN students s ON spr.student_id = s.student_id
    WHERE p.user_id = ?
    ORDER BY s.first_name, s.last_name";

$stmt = $conn->prepare($childrenQuery);
if (!$stmt) {
    die("Error preparing children query: " . $conn->error);
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$childrenResult = $stmt->get_result();
$children = [];
while ($child = $childrenResult->fetch_assoc()) {
    $children[] = $child;
}
$stmt->close();

if (empty($children)) {
    die("No children found for this parent account. Please contact the administrator.");
}

// --- DETERMINE WHICH CHILD TO DISPLAY ---
$selectedChildIndex = 0;
if (isset($_GET['child']) && is_numeric($_GET['child'])) {
    $requestedIndex = intval($_GET['child']);
    if ($requestedIndex >= 0 && $requestedIndex < count($children)) {
        $selectedChildIndex = $requestedIndex;
    }
}

$currentChild = $children[$selectedChildIndex];
$student_id = $currentChild['student_id'];
$student_name = $currentChild['student_name'];
$student_class = $currentChild['class'] ?? 'N/A';

// Check fee status - Updated to use student_id instead of int
$feeQuery = "SELECT status FROM fees WHERE student_id = ? LIMIT 1";
$stmt = $conn->prepare($feeQuery);
if (!$stmt) {
    die("Error preparing fee query: " . $conn->error);
}

$stmt->bind_param("s", $student_id);
$stmt->execute();
$feeResult = $stmt->get_result();
$feeData = $feeResult->fetch_assoc();
$fees_cleared = ($feeData && $feeData['status'] == 'Cleared');
$stmt->close();

$all_results_by_term = [];
$selected_report_data = [];
$selected_term = $_GET['term'] ?? null;
$selected_year = $_GET['year'] ?? null;

function getGradeNumericValue($grade) { 
    if (is_numeric($grade)) { 
        return floatval($grade); 
    } 
    $grade = strtoupper(trim($grade ?? '')); 
    $gradeMap = [ 
        'A+' => 5.3, 'A' => 5, 'A-' => 4.7, 'B+' => 4.3, 'B' => 4, 'B-' => 3.7, 
        'C+' => 3.3, 'C' => 3, 'C-' => 2.7, 'D+' => 2.3, 'D' => 2, 'D-' => 1.7, 
        'E'  => 1, 'F'  => 0, 'DISTINCTION' => 5, 'MERIT' => 4, 'PASS' => 3, 'FAIL' => 0,
        '5' => 5, '4' => 4, '3' => 3, '2' => 2, '1' => 1, '0' => 0,
    ]; 
    return $gradeMap[$grade] ?? null; 
}

function format_mark($mark, $decimals = 0) { 
    if (is_null($mark) || $mark === '') { 
        return '<span style="color: #999;">-</span>'; 
    } 
    return htmlspecialchars(number_format((float)$mark, $decimals)); 
}

function markToGrade($mark) {
    if (is_null($mark) || $mark === '') return '-';
    $mark = floatval($mark);
    if ($mark >= 90) return 'A+';
    if ($mark >= 85) return 'A';
    if ($mark >= 80) return 'A-';
    if ($mark >= 75) return 'B+';
    if ($mark >= 70) return 'B';
    if ($mark >= 65) return 'B-';
    if ($mark >= 60) return 'C+';
    if ($mark >= 55) return 'C';
    if ($mark >= 50) return 'C-';
    if ($mark >= 45) return 'D+';
    if ($mark >= 40) return 'D';
    if ($mark >= 35) return 'D-';
    if ($mark >= 30) return 'E';
    return 'F';
}

if ($fees_cleared) {
    // Updated query to match your actual results table structure
    $resultsQuery = "
        SELECT
            r.result_id, 
<<<<<<< HEAD
            COALESCE(s.subject_name, r.subject) as subject_name, 
            r.final_mark, 
            r.final_grade, 
            r.target_grade, 
            r.attitude_to_learning, 
            r.comments, 
            r.term, 
            r.year,
            MAX(CASE WHEN ta.assessment_name = 'Assessment 1' THEN ta.mark END) AS assessment_1_mark,
            MAX(CASE WHEN ta.assessment_name = 'Assessment 2' THEN ta.mark END) AS assessment_2_mark,
            MAX(CASE WHEN ta.assessment_name = 'End of Term Exam' THEN ta.mark END) AS exam_mark
=======
            COALESCE(s.subject_name, r.subject, 'Unknown Subject') as subject_name,
            r.final_mark, 
            r.grade as final_grade,
            r.term, 
            r.academic_year as year,
            r.marks_obtained,
            r.total_marks,
            r.comments,
            r.created_at
>>>>>>> b291daf7f49078bb0cccb1439969ad4a74e2db38
        FROM results r
        LEFT JOIN subjects s ON r.subject = s.subject_name OR r.subject = s.subject_id
        WHERE r.student_id = ?
<<<<<<< HEAD
        GROUP BY r.result_id, s.subject_name, r.subject, r.final_mark, r.final_grade, r.target_grade, 
                 r.attitude_to_learning, r.comments, r.term, r.year
        ORDER BY r.year DESC, r.term DESC, COALESCE(s.subject_name, r.subject) ASC";
=======
        ORDER BY r.academic_year DESC, r.term DESC, r.subject ASC";
>>>>>>> b291daf7f49078bb0cccb1439969ad4a74e2db38

    $stmt_results = $conn->prepare($resultsQuery);
    if (!$stmt_results) { 
        die("Error preparing results query: " . $conn->error); 
    }
    $stmt_results->bind_param("s", $student_id);
    $stmt_results->execute();
    $results = $stmt_results->get_result();

    if ($results) {
        while ($row = $results->fetch_assoc()) {
<<<<<<< HEAD
            // Convert exam mark to grade if not available
            if (empty($row['exam_grade'])) {
                $row['exam_grade'] = markToGrade($row['exam_mark']);
            }
            
            $report_key = "{$row['term']}|{$row['year']}";
=======
            // Use academic_year from your table structure
            $year = $row['year'] ?? date('Y');
            $term = $row['term'] ?? 'Term 1';
            $report_key = "{$term}|{$year}";
            
            // Calculate percentage if we have marks
            if ($row['marks_obtained'] && $row['total_marks'] && $row['total_marks'] > 0) {
                $row['percentage'] = round(($row['marks_obtained'] / $row['total_marks']) * 100, 1);
            } else {
                $row['percentage'] = $row['final_mark'] ?? 0;
            }
            
>>>>>>> b291daf7f49078bb0cccb1439969ad4a74e2db38
            $all_results_by_term[$report_key][] = $row;
        }
    }
    $stmt_results->close();

    if ($selected_term && $selected_year) {
        $current_report_key = "{$selected_term}|{$selected_year}";
        if (isset($all_results_by_term[$current_report_key])) {
            $selected_report_data = $all_results_by_term[$current_report_key];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Progress Report</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            color: #333;
            line-height: 1.6;
        }

        .header {
            background: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #ddd;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .brand img {
            height: 50px;
            border-radius: 50%;
        }

        .brand h1 {
            font-size: 24px;
            color: #4A90E2;
        }

        .nav-actions {
            display: flex;
            gap: 15px;
        }

        .nav-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background-color: transparent;
            color: #4A90E2;
            border: 2px solid #4A90E2;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
        }

        .nav-btn:hover {
            background-color: #4A90E2;
            color: white;
        }

        .nav-btn.primary {
            background-color: #4A90E2;
            color: white;
        }

        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .child-selector {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .child-selector h3 {
            color: #4A90E2;
            margin-bottom: 15px;
        }

        .child-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .child-tab {
            padding: 10px 20px;
            background: #f0f0f0;
            color: #333;
            text-decoration: none;
            border-radius: 5px;
            border: 2px solid transparent;
        }

        .child-tab.active {
            background: #4A90E2;
            color: white;
            border-color: #357ABD;
        }

        .child-tab:hover {
            background: #e0e0e0;
        }

        .child-tab.active:hover {
            background: #357ABD;
        }

        .content-box {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .content-header {
            background: linear-gradient(135deg, #4A90E2, #357ABD);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .content-header h2 {
            font-size: 28px;
            margin-bottom: 10px;
        }

        .content-body {
            padding: 30px;
        }

        .report-info {
            display: flex;
            justify-content: space-between;
            padding: 20px 30px;
            background-color: #f9f9f9;
            border-bottom: 1px solid #ddd;
            font-weight: bold;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
            color: #4A90E2;
            text-decoration: none;
            font-weight: bold;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .report-list {
            list-style: none;
            padding: 0;
        }

        .report-list-item {
            margin-bottom: 15px;
        }

        .report-list-item a {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px;
            background-color: white;
            border-radius: 8px;
            text-decoration: none;
            color: #333;
            border-left: 5px solid #4A90E2;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .report-list-item a:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
        }

        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th {
            background-color: #f9f9f9;
            padding: 15px;
            text-align: center;
            font-weight: bold;
            font-size: 12px;
            text-transform: uppercase;
            color: #666;
            border-bottom: 2px solid #ddd;
        }

        th:first-child {
            text-align: left;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #eee;
            text-align: center;
        }

        td:first-child {
            text-align: left;
            font-weight: bold;
        }

        tbody tr:hover {
            background-color: #f9f9f9;
        }

        .comment-cell {
            min-width: 200px;
            text-align: left;
            font-size: 14px;
            color: #666;
        }

        .icon-success {
            color: #10B981;
            font-size: 18px;
        }

        .icon-danger {
            color: #EF4444;
            font-size: 18px;
        }

        .message {
            text-align: center;
            padding: 50px 30px;
            border-radius: 8px;
            background-color: #fff3cd;
            color: #856404;
        }

        .message.fees-outstanding {
            background-color: #f8d7da;
            color: #721c24;
        }

        .message i {
            font-size: 48px;
            margin-bottom: 20px;
            opacity: 0.7;
        }

        .key-section {
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            margin-top: 30px;
        }

        .key-section h4 {
            margin-bottom: 15px;
            color: #333;
        }

        .key-section ul {
            display: flex;
            flex-wrap: wrap;
            gap: 15px 30px;
            list-style: none;
        }

        .footer {
            text-align: center;
            padding: 30px;
            margin-top: 30px;
            color: #666;
        }

        .debug-info {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            margin: 20px 0;
            font-family: monospace;
            font-size: 12px;
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 15px;
            }

            .nav-actions {
                flex-direction: column;
                width: 100%;
            }

            .report-info {
                flex-direction: column;
                gap: 10px;
            }

            .child-tabs {
                flex-direction: column;
            }

            table {
                font-size: 14px;
            }

            th, td {
                padding: 10px 8px;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="brand">
            <img src="../images/logo.jpg" alt="Wisetech College Logo">
            <h1>Student Portal</h1>
        </div>
        <div class="nav-actions">
            <a href="parent_home.php?child=<?= $selectedChildIndex ?>" class="nav-btn">
                <i class="fas fa-home"></i> Home
            </a>
            <a href="../logout.php" class="nav-btn primary">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </header>

    <div class="container">
        <?php if (count($children) > 1): ?>
        <div class="child-selector">
            <h3><i class="fas fa-users"></i> Select Child</h3>
            <div class="child-tabs">
                <?php foreach ($children as $index => $child): ?>
                    <a href="?child=<?= $index ?><?= $selected_term && $selected_year ? '&term='.urlencode($selected_term).'&year='.urlencode($selected_year) : '' ?>" 
                       class="child-tab <?= $index === $selectedChildIndex ? 'active' : '' ?>">
                        <?= htmlspecialchars($child['student_name']) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="content-box">
            <?php if (!$fees_cleared): ?>
                <div class="content-body">
                    <div class="message fees-outstanding">
                        <i class="fas fa-exclamation-triangle"></i>
                        <p style="font-weight: bold; font-size: 18px;">Academic Results Unavailable</p>
                        <p>Please ensure all outstanding school fees are cleared to view <?= htmlspecialchars($student_name) ?>'s progress report.</p>
                    </div>
                </div>
            <?php elseif ($selected_term && $selected_year): ?>
                <div class="content-header">
                    <h2>Progress Report: Term <?= htmlspecialchars($selected_term) ?> - <?= htmlspecialchars($selected_year) ?></h2>
                    <p>Viewing results for <?= htmlspecialchars($student_name) ?></p>
                </div>
                <div class="report-info">
                    <span>Student: <strong><?= htmlspecialchars($student_name) ?></strong></span>
                    <span>Class: <strong><?= htmlspecialchars($student_class) ?></strong></span>
                </div>
                <div class="content-body">
                    <a href="parent_results.php?child=<?= $selectedChildIndex ?>" class="back-link">
                        <i class="fas fa-arrow-left"></i> View Other Reports
                    </a>
                    <?php if (!empty($selected_report_data)): ?>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Subject</th>
                                        <th>Marks Obtained</th>
                                        <th>Total Marks</th>
                                        <th>Percentage (%)</th>
                                        <th>Grade</th>
                                        <th>Comments</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($selected_report_data as $row): ?>
                                        <tr>
<<<<<<< HEAD
                                            <td><?= htmlspecialchars($row['subject_name'] ?? 'Unknown Subject') ?></td>
                                            <td><?= format_mark($row['assessment_1_mark']) ?></td>
                                            <td><?= format_mark($row['assessment_2_mark']) ?></td>
                                            <td><?= format_mark($row['exam_mark']) ?></td>
                                            <td><?= htmlspecialchars($row['exam_grade'] ?? '-') ?></td>
                                            <td><?= htmlspecialchars($row['target_grade'] ?? '-') ?></td>
                                            <td><?= $otg_display ?></td>
                                            <td><?= htmlspecialchars($row['attitude_to_learning'] ?? '-') ?></td>
=======
                                            <td><?= htmlspecialchars($row['subject_name']) ?></td>
                                            <td><?= format_mark($row['marks_obtained']) ?></td>
                                            <td><?= format_mark($row['total_marks']) ?></td>
                                            <td><?= format_mark($row['percentage'], 1) ?>%</td>
                                            <td><?= htmlspecialchars($row['final_grade'] ?? '-') ?></td>
>>>>>>> b291daf7f49078bb0cccb1439969ad4a74e2db38
                                            <td class="comment-cell"><?= htmlspecialchars($row['comments'] ?? '') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="key-section">
                            <h4>Key Definitions</h4>
                            <ul>
                                <li><strong>Marks Obtained:</strong> Actual marks scored by student</li>
                                <li><strong>Total Marks:</strong> Maximum possible marks</li>
                                <li><strong>Percentage:</strong> Performance percentage</li>
                                <li><strong>Grade:</strong> Letter grade assigned</li>
                                <li><strong>Comments:</strong> Teacher feedback and remarks</li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <div class="message">
                            <p>No progress report data found for this period.</p>
                        </div>
                    <?php endif; ?>
                </div>

            <?php elseif (!empty($all_results_by_term)): ?>
                <div class="content-header">
                    <h2>View Progress Report</h2>
                    <p>Select a report for <?= htmlspecialchars($student_name) ?></p>
                </div>
                <div class="content-body">
                    <p>Please select a report to view the detailed progress for <strong><?= htmlspecialchars($student_name) ?></strong>.</p>
                    <ul class="report-list">
                        <?php foreach ($all_results_by_term as $key => $results):
                            list($term, $year) = explode('|', $key);
                        ?>
                            <li class="report-list-item">
                                <a href="parent_results.php?child=<?= $selectedChildIndex ?>&term=<?= urlencode($term) ?>&year=<?= urlencode($year) ?>">
                                    <span>
                                        <i class="fas fa-file-alt" style="margin-right: 12px;"></i>
                                        Progress Report: Term <?= htmlspecialchars($term) ?> - <?= htmlspecialchars($year) ?>
                                    </span>
                                    <span><i class="fas fa-chevron-right"></i></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php else: ?>
                <div class="content-header">
                    <h2>Progress Report</h2>
                    <p>Results for <?= htmlspecialchars($student_name) ?></p>
                </div>
                <div class="content-body">
                    <div class="message">
                        <i class="fas fa-folder-open"></i>
                        <p>No progress report data has been published for <?= htmlspecialchars($student_name) ?> yet.</p>
                    </div>
                    
                    <!-- Debug information -->
                    <?php if (isset($_GET['debug'])): ?>
                    <div class="debug-info">
                        <h4>Debug Information:</h4>
                        <p><strong>User ID:</strong> <?= $user_id ?></p>
                        <p><strong>Selected Student ID:</strong> <?= $student_id ?></p>
                        <p><strong>Student Name:</strong> <?= $student_name ?></p>
                        <p><strong>Fees Cleared:</strong> <?= $fees_cleared ? 'Yes' : 'No' ?></p>
                        <p><strong>Children Found:</strong> <?= count($children) ?></p>
                        <p><strong>Results Found:</strong> <?= count($all_results_by_term) ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; <?php echo date("Y"); ?> Wisetech College Portal | All Rights Reserved</p>
    </footer>
</body>
</html>
<?php
if (isset($conn)) $conn->close();
?>