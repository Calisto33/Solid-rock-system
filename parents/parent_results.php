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

// Check fee status
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
    // More robust query that handles your data structure better:

$resultsQuery = "
    SELECT
        r.result_id, 
        COALESCE(NULLIF(s.subject_name, ''), NULLIF(r.subject, ''), 'Unknown Subject') as subject_name, 
        r.final_mark, 
        COALESCE(NULLIF(r.final_grade, ''), r.grade) as final_grade,  -- Fallback to 'grade' column if final_grade is NULL
        r.target_grade, 
        r.attitude_to_learning, 
        r.comments, 
        r.term, 
        r.year,
        MAX(CASE WHEN ta.assessment_name = 'Assessment 1' THEN ta.mark END) AS assessment_1_mark,
        MAX(CASE WHEN ta.assessment_name = 'Assessment 2' THEN ta.mark END) AS assessment_2_mark,
        MAX(CASE WHEN ta.assessment_name = 'End of Term Exam' THEN ta.mark END) AS exam_mark
    FROM results r
    LEFT JOIN subjects s ON r.subject_id = s.subject_id
    LEFT JOIN term_assessments ta ON r.result_id = ta.result_id
    WHERE r.student_id = ?
    GROUP BY r.result_id, s.subject_name, r.subject, r.final_mark, r.final_grade, r.grade, r.target_grade, 
             r.attitude_to_learning, r.comments, r.term, r.year
    ORDER BY r.year DESC, r.term DESC, COALESCE(NULLIF(s.subject_name, ''), NULLIF(r.subject, ''), 'Unknown Subject') ASC";

$stmt_results = $conn->prepare($resultsQuery);
if (!$stmt_results) { 
    die("Error preparing results query: " . $conn->error); 
}
$stmt_results->bind_param("s", $student_id);
$stmt_results->execute();
$results = $stmt_results->get_result();

// Add some debugging
echo "<!-- DEBUG: Querying for student_id: " . $student_id . " -->";
if ($results) {
    echo "<!-- DEBUG: Number of results found: " . $results->num_rows . " -->";
    
    while ($row = $results->fetch_assoc()) {
        // Generate exam grade from final_mark if not present
        if (empty($row['final_grade']) && !empty($row['final_mark'])) {
            $row['final_grade'] = markToGrade($row['final_mark']);
        }
        
        if (empty($row['exam_grade'])) {
            $row['exam_grade'] = markToGrade($row['exam_mark']);
        }
        
        $report_key = "{$row['term']}|{$row['year']}";
        $all_results_by_term[$report_key][] = $row;
        
        // Debug output
        echo "<!-- DEBUG: Found record - Term: {$row['term']}, Year: {$row['year']}, Subject: {$row['subject_name']} -->";
    }
} else {
    echo "<!-- DEBUG: Query failed or returned no results -->";
}
$stmt_results->close();

    $stmt_results = $conn->prepare($resultsQuery);
    if (!$stmt_results) { 
        die("Error preparing results query: " . $conn->error); 
    }
    $stmt_results->bind_param("s", $student_id);
    $stmt_results->execute();
    $results = $stmt_results->get_result();

    if ($results) {
        while ($row = $results->fetch_assoc()) {
            if (empty($row['exam_grade'])) {
                $row['exam_grade'] = markToGrade($row['exam_mark']);
            }
            
            $report_key = "{$row['term']}|{$row['year']}";
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

// Include header
include 'header.php';
?>

<style>
    /* Additional styles for the results page */
    .child-selector {
        background: var(--background-white);
        padding: 1.5rem;
        border-radius: 0.5rem;
        margin-bottom: 2rem;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }

    .child-selector h3 {
        color: var(--primary-color);
        margin-bottom: 1rem;
        font-size: 1.1rem;
        font-weight: 600;
    }

    .child-tabs {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
    }

    .child-tab {
        padding: 0.75rem 1.5rem;
        background: var(--background-light);
        color: var(--text-dark);
        text-decoration: none;
        border-radius: 0.375rem;
        border: 2px solid transparent;
        font-weight: 500;
        transition: all 0.2s ease;
    }

    .child-tab.active {
        background: var(--primary-color);
        color: white;
        border-color: #2563eb;
    }

    .child-tab:hover:not(.active) {
        background: #e5e7eb;
    }

    .content-box {
        background: var(--background-white);
        border-radius: 0.5rem;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    .content-header {
        background: linear-gradient(135deg, var(--primary-color), #2563eb);
        color: white;
        padding: 2rem;
        text-align: center;
    }

    .content-header h2 {
        font-size: 1.75rem;
        margin-bottom: 0.5rem;
        font-weight: 700;
    }

    .content-body {
        padding: 2rem;
    }

    .report-info {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1.25rem 2rem;
        background-color: #f8f9fa;
        border-bottom: 1px solid var(--border-color);
        font-weight: 600;
    }

    .back-link {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 1.5rem;
        color: var(--primary-color);
        text-decoration: none;
        font-weight: 500;
        padding: 0.5rem 1rem;
        border-radius: 0.375rem;
        border: 1px solid var(--primary-color);
        transition: all 0.2s ease;
    }

    .back-link:hover {
        background: var(--primary-color);
        color: white;
    }

    .report-list {
        list-style: none;
        padding: 0;
        margin-top: 1.5rem;
    }

    .report-list-item {
        margin-bottom: 1rem;
    }

    .report-list-item a {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1.5rem;
        background-color: var(--background-white);
        border-radius: 0.5rem;
        text-decoration: none;
        color: var(--text-dark);
        border-left: 4px solid var(--primary-color);
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        transition: all 0.2s ease;
    }

    .report-list-item a:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }

    .table-responsive {
        overflow-x: auto;
        margin-top: 1.5rem;
        border-radius: 0.5rem;
        border: 1px solid var(--border-color);
    }

    table {
        width: 100%;
        border-collapse: collapse;
        min-width: 800px;
    }

    th {
        background-color: #f8f9fa;
        padding: 1rem;
        text-align: center;
        font-weight: 600;
        font-size: 0.875rem;
        color: var(--text-muted);
        border-bottom: 2px solid var(--border-color);
        white-space: nowrap;
    }

    th:first-child {
        text-align: left;
    }

    td {
        padding: 1rem;
        border-bottom: 1px solid #f1f3f4;
        text-align: center;
        font-size: 0.875rem;
    }

    td:first-child {
        text-align: left;
        font-weight: 500;
    }

    tbody tr:hover {
        background-color: #f8f9fa;
    }

    .comment-cell {
        min-width: 200px;
        text-align: left;
        font-size: 0.875rem;
        color: var(--text-muted);
        max-width: 250px;
        word-wrap: break-word;
    }

    .icon-success {
        color: #10b981;
        font-size: 1.125rem;
    }

    .icon-danger {
        color: #ef4444;
        font-size: 1.125rem;
    }

    .message {
        text-align: center;
        padding: 3rem 2rem;
        border-radius: 0.5rem;
        background-color: #fef3c7;
        color: #92400e;
    }

    .message.fees-outstanding {
        background-color: #fecaca;
        color: #991b1b;
    }

    .message i {
        font-size: 3rem;
        margin-bottom: 1rem;
        opacity: 0.7;
        display: block;
    }

    .message p {
        font-size: 1.125rem;
    }

    .message p:first-of-type {
        font-weight: 600;
        margin-bottom: 0.5rem;
    }

    .key-section {
        background-color: #f8f9fa;
        border: 1px solid var(--border-color);
        border-radius: 0.5rem;
        padding: 1.5rem;
        margin-top: 2rem;
    }

    .key-section h4 {
        margin-bottom: 1rem;
        color: var(--text-dark);
        font-weight: 600;
    }

    .key-section ul {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 0.75rem;
        list-style: none;
    }

    .key-section li {
        font-size: 0.875rem;
    }

    .debug-info {
        background-color: #f8f9fa;
        border: 1px solid var(--border-color);
        border-radius: 0.375rem;
        padding: 1rem;
        margin: 1.5rem 0;
        font-family: 'Courier New', monospace;
        font-size: 0.75rem;
    }

    /* Responsive Design */
    @media (max-width: 992px) {
        .content-body {
            padding: 1.5rem;
        }
        
        .content-header {
            padding: 1.5rem;
        }
        
        .report-info {
            flex-direction: column;
            gap: 0.5rem;
            align-items: flex-start;
            padding: 1rem 1.5rem;
        }
        
        .key-section ul {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 768px) {
        .child-tabs {
            flex-direction: column;
        }
        
        .child-tab {
            text-align: center;
        }
        
        .content-header h2 {
            font-size: 1.5rem;
        }
        
        .table-responsive {
            border-radius: 0;
            margin-left: -1.5rem;
            margin-right: -1.5rem;
            border-left: none;
            border-right: none;
        }
        
        th, td {
            padding: 0.75rem 0.5rem;
            font-size: 0.8rem;
        }
        
        .comment-cell {
            min-width: 150px;
            max-width: 180px;
        }
        
        .message {
            padding: 2rem 1rem;
        }
        
        .message i {
            font-size: 2.5rem;
        }
    }

    @media (max-width: 576px) {
        .content-body {
            padding: 1rem;
        }
        
        .content-header {
            padding: 1rem;
        }
        
        .content-header h2 {
            font-size: 1.25rem;
        }
        
        .report-info {
            padding: 0.75rem 1rem;
        }
        
        th, td {
            padding: 0.5rem 0.25rem;
            font-size: 0.75rem;
        }
        
        .back-link {
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
        }
    }
</style>

<?php
// Include sidebar
include 'sidebar.php';
?>

    <!-- Page header content -->
    <div class="header-title">
        <h2>Student Results</h2>
        <p>View academic progress and reports</p>
    </div>

    <!-- Main Content -->
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
                    <p>Academic Results Unavailable</p>
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
                                    <th>Assessment 1 (%)</th>
                                    <th>Assessment 2 (%)</th>
                                    <th>End of Term Exam (%)</th>
                                    <th>Exam Grade</th>
                                    <th>Target Grade</th>
                                    <th>On Target</th>
                                    <th>Attitude (1-5)</th>
                                    <th>Comments</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($selected_report_data as $row): ?>
                                    <?php
                                    $otg_display = '<span style="color: #999;">-</span>';
                                    $final_grade_value = getGradeNumericValue($row['final_grade']);
                                    $target_grade_value = getGradeNumericValue($row['target_grade']);

                                    if (!is_null($final_grade_value) && !is_null($target_grade_value)) {
                                        $otg_display = ($final_grade_value >= $target_grade_value) ? 
                                            '<span class="icon-success">✔</span>' : 
                                            '<span class="icon-danger">✖</span>';
                                    }
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['subject_name'] ?? 'Unknown Subject') ?></td>
                                        <td><?= format_mark($row['assessment_1_mark']) ?></td>
                                        <td><?= format_mark($row['assessment_2_mark']) ?></td>
                                        <td><?= format_mark($row['exam_mark']) ?></td>
                                        <td><?= htmlspecialchars($row['exam_grade'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($row['target_grade'] ?? '-') ?></td>
                                        <td><?= $otg_display ?></td>
                                        <td><?= htmlspecialchars($row['attitude_to_learning'] ?? '-') ?></td>
                                        <td class="comment-cell"><?= htmlspecialchars($row['comments'] ?? '') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="key-section">
                        <h4>Key Definitions</h4>
                        <ul>
                            <li><strong>Assessment 1/2:</strong> Coursework Assessment Mark</li>
                            <li><strong>End of Term Exam:</strong> Final Exam Mark</li>
                            <li><strong>Exam Grade:</strong> Grade for the End of Term Exam</li>
                            <li><strong>Target Grade:</strong> Student's goal for the subject</li>
                            <li><strong>On Target:</strong> <span class="icon-success">✔</span> Met / <span class="icon-danger">✖</span> Below Target</li>
                            <li><strong>Attitude:</strong> Attitude to Learning (1-5)</li>
                        </ul>
                    </div>
                <?php else: ?>
                    <div class="message">
                        <i class="fas fa-folder-open"></i>
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
                <p style="margin-bottom: 1.5rem;">Please select a report to view the detailed progress for <strong><?= htmlspecialchars($student_name) ?></strong>.</p>
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

<?php
// Include footer
include 'footer.php';

if (isset($conn)) $conn->close();
?>