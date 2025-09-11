<?php
session_start();
$pageTitle = "My Results";
include 'header.php';
include '../config.php';

// Check if user is logged in as student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}

$logged_in_user_id = $_SESSION['user_id'];

// FIXED: Use user_id instead of id to get student information
$studentIdStmt = $conn->prepare("SELECT student_id, username, class FROM students WHERE user_id = ?");
$studentIdStmt->bind_param("i", $logged_in_user_id);
$studentIdStmt->execute();
$studentInfo = $studentIdStmt->get_result()->fetch_assoc();
$studentIdStmt->close();

if (!$studentInfo) {
    die("Student information not found for user ID: " . $logged_in_user_id);
}

$student_id_for_results = $studentInfo['student_id'];

function getGradeNumericValue($grade) {
    if (is_numeric($grade)) {
        return floatval($grade);
    }
    
    $grade = strtoupper(trim($grade ?? ''));
    $gradeMap = [
        'A+' => 5.3, 'A' => 5, 'A-' => 4.7,
        'B+' => 4.3, 'B' => 4, 'B-' => 3.7,
        'C+' => 3.3, 'C' => 3, 'C-' => 2.7,
        'D+' => 2.3, 'D' => 2, 'D-' => 1.7,
        'E' => 1, 'F' => 0,
        'DISTINCTION' => 5, 'MERIT' => 4, 'PASS' => 3, 'FAIL' => 0,
        '5' => 5, '4' => 4, '3' => 3, '2' => 2, '1' => 1, '0' => 0
    ];
    
    return $gradeMap[$grade] ?? null;
}

function format_mark($mark, $decimals = 0) {
    if (is_null($mark) || $mark === '') return '-';
    return htmlspecialchars(number_format((float)$mark, $decimals));
}

// SIMPLIFIED: Query using only columns that exist in the results table
$resultsQuery = "
    SELECT
        r.result_id,
        r.subject,
        r.term,
        r.academic_year as year,
        r.final_mark,
        r.grade,
        r.comments,
        r.marks_obtained,
        r.total_marks
    FROM results r
    WHERE r.student_id = ?
    ORDER BY r.academic_year DESC, r.term DESC, r.subject ASC
";

$resultsStmt = $conn->prepare($resultsQuery);
$resultsStmt->bind_param("s", $student_id_for_results); // student_id is VARCHAR
$resultsStmt->execute();
$results = $resultsStmt->get_result();

$results_by_year_term = [];
while ($row = $results->fetch_assoc()) {
    $year_key = $row['year'];
    $term_key = $row['term'];
    $results_by_year_term["Progress Report: Term: {$term_key} - {$year_key}"][] = $row;
}

$selected_report_key = isset($_GET['report']) ? $_GET['report'] : null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --widget-bg: #FFFFFF;
            --primary-text: #333;
            --secondary-text: #666;
            --border-color: #E8EDF3;
            --shadow: 0 4px 15px rgba(0, 0, 0, 0.07);
            --rounded-xl: 12px;
            --rounded-lg: 8px;
            --bg-color: #F7F9FC;
            --accent-purple: #6D28D9;
            --accent-blue: #2563EB;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --transition: all 0.3s ease-in-out;
        }

        body {
            background-color: var(--bg-color);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            color: var(--primary-text);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .page-title {
            margin-bottom: 1.5rem;
            font-weight: 700;
            color: var(--primary-text);
            font-size: 1.8rem;
        }

        .report-card {
            background: var(--widget-bg);
            border-radius: var(--rounded-xl);
            box-shadow: var(--shadow);
            margin-bottom: 2.5rem;
            padding: 2rem;
        }

        .report-header {
            padding-bottom: 1.5rem;
            border-bottom: 1px solid var(--border-color);
            margin-bottom: 1.5rem;
        }

        .report-header h2 {
            font-size: 1.6rem;
            font-weight: 700;
            color: var(--primary-text);
            margin-bottom: 0.25rem;
        }

        .report-header p {
            font-size: 1rem;
            color: var(--secondary-text);
        }

        .table-container {
            overflow-x: auto;
        }

        .results-table {
            width: 100%;
            border-collapse: collapse;
            white-space: nowrap;
        }

        .results-table th,
        .results-table td {
            padding: 0.9rem 1rem;
            border: 1px solid var(--border-color);
            vertical-align: middle;
            text-align: center;
            font-size: 0.95rem;
        }

        .results-table thead th {
            background-color: #f3f4f6;
            font-weight: 600;
            color: var(--primary-text);
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }

        .results-table tbody td:first-child {
            text-align: left;
            font-weight: 600;
            color: var(--primary-text);
        }

        .results-table tbody tr:nth-child(even) {
            background-color: #f9fafb;
        }

        .status-pass {
            color: var(--success-color);
            font-weight: bold;
        }

        .status-fail {
            color: var(--danger-color);
            font-weight: bold;
        }

        .comment-cell {
            min-width: 250px;
            white-space: normal;
            text-align: left;
            font-size: 0.9rem;
            line-height: 1.5;
            max-width: 300px;
        }

        .report-key {
            margin-top: 1.5rem;
            padding: 1.25rem 1.5rem;
            background-color: var(--bg-color);
            border: 1px solid var(--border-color);
            border-radius: var(--rounded-lg);
            font-size: 0.9rem;
        }

        .report-key h4 {
            margin-bottom: 0.75rem;
            color: var(--accent-purple);
            font-weight: 600;
        }

        .report-key ul {
            list-style: none;
            padding-left: 0;
            margin-bottom: 0;
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem 1.5rem;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: var(--secondary-text);
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .report-list {
            list-style: none;
            padding: 0;
        }

        .report-list-item a {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1.5rem;
            margin-bottom: 1rem;
            background-color: var(--widget-bg);
            border-radius: var(--rounded-lg);
            box-shadow: var(--shadow);
            text-decoration: none;
            color: var(--primary-text);
            transition: var(--transition);
            border-left: 5px solid var(--accent-blue);
        }

        .report-list-item a:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
            border-left-color: var(--accent-purple);
        }

        .report-list-item .report-title {
            font-size: 1.2rem;
            font-weight: 600;
        }

        .report-list-item .report-icon {
            font-size: 1.2rem;
            color: var(--secondary-text);
            transition: var(--transition);
        }

        .report-list-item a:hover .report-icon {
            transform: translateX(5px);
            color: var(--accent-purple);
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            color: var(--accent-purple);
            text-decoration: none;
            font-weight: 600;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .percentage-display {
            font-weight: 600;
        }

        .grade-display {
            font-size: 1.1rem;
            font-weight: 700;
            padding: 0.25rem 0.75rem;
            border-radius: 4px;
            background-color: var(--bg-color);
        }

        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            
            .results-table {
                font-size: 0.8rem;
            }
            
            .results-table th,
            .results-table td {
                padding: 0.5rem;
            }
            
            .comment-cell {
                min-width: 150px;
                max-width: 200px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="page-title">My Academic Results</h1>
        
        <?php if ($selected_report_key && array_key_exists($selected_report_key, $results_by_year_term)): ?>
            <?php $results_data = $results_by_year_term[$selected_report_key]; ?>
            <a href="student_results.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Back to All Reports
            </a>
            
            <div class="report-card">
                <div class="report-header">
                    <h2><?= htmlspecialchars($selected_report_key) ?></h2>
                    <p>Student: <strong><?= htmlspecialchars($studentInfo['username']) ?></strong> | 
                       Class: <strong><?= htmlspecialchars($studentInfo['class']) ?></strong></p>
                </div>
                
                <div class="table-container">
                    <table class="results-table">
                        <thead>
                            <tr>
                                <th>Subject</th>
                                <th>Marks Obtained</th>
                                <th>Total Marks</th>
                                <th>Percentage</th>
                                <th>Final Grade</th>
                                <th>Status</th>
                                <th>Teacher's Comment</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($results_data as $row): ?>
                                <?php
                                // Calculate percentage
                                $percentage = '';
                                $status_class = '';
                                $status_text = '-';
                                
                                if (!empty($row['marks_obtained']) && !empty($row['total_marks']) && $row['total_marks'] > 0) {
                                    $calc_percentage = ($row['marks_obtained'] / $row['total_marks']) * 100;
                                    $percentage = number_format($calc_percentage, 1) . '%';
                                    
                                    if ($calc_percentage >= 50) {
                                        $status_class = 'status-pass';
                                        $status_text = 'Pass';
                                    } else {
                                        $status_class = 'status-fail';
                                        $status_text = 'Fail';
                                    }
                                } elseif (!empty($row['final_mark'])) {
                                    $percentage = number_format($row['final_mark'], 1) . '%';
                                    if ($row['final_mark'] >= 50) {
                                        $status_class = 'status-pass';
                                        $status_text = 'Pass';
                                    } else {
                                        $status_class = 'status-fail';
                                        $status_text = 'Fail';
                                    }
                                }
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['subject']) ?></td>
                                    <td><?= htmlspecialchars($row['marks_obtained'] ?: '-') ?></td>
                                    <td><?= htmlspecialchars($row['total_marks'] ?: '-') ?></td>
                                    <td class="percentage-display"><?= $percentage ?: '-' ?></td>
                                    <td class="grade-display"><?= htmlspecialchars($row['grade'] ?: '-') ?></td>
                                    <td class="<?= $status_class ?>"><?= $status_text ?></td>
                                    <td class="comment-cell"><?= htmlspecialchars($row['comments'] ?: 'No comments') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="report-key">
                    <h4>Key:</h4>
                    <ul>
                        <li><strong>Percentage:</strong> (Marks Obtained ÷ Total Marks) × 100</li>
                        <li><strong>Status:</strong> <span class="status-pass">Pass</span> (≥50%) / <span class="status-fail">Fail</span> (<50%)</li>
                        <li><strong>Grade:</strong> Final letter grade assigned</li>
                    </ul>
                </div>
            </div>
            
        <?php elseif (!empty($results_by_year_term)): ?>
            <div class="report-card">
                <div style="padding: 1.5rem 1.5rem 0.5rem 1.5rem;">
                    <ul class="report-list">
                        <?php foreach (array_keys($results_by_year_term) as $term_key): ?>
                            <li class="report-list-item">
                                <a href="student_results.php?report=<?= urlencode($term_key) ?>">
                                    <span class="report-title">
                                        <i class="fas fa-file-invoice" style="margin-right: 1rem; color: var(--accent-blue);"></i>
                                        <?= htmlspecialchars($term_key) ?>
                                    </span>
                                    <span class="report-icon">
                                        <i class="fas fa-chevron-right"></i>
                                    </span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            
        <?php else: ?>
            <div class="report-card">
                <div class="empty-state">
                    <i class="fas fa-folder-open"></i>
                    <h3>No Results Available</h3>
                    <p>No results have been published yet. Please check back later.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php
    $conn->close();
    include 'footer.php';
    ?>
</body>
</html>