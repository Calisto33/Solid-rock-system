<?php
// --- SETUP AND SECURITY ---
ini_set('display_errors', 1);
error_reporting(E_ALL);

$pageTitle = "Student Progress Report";
include 'header.php';

// --- DATABASE LOGIC ---

// 1. Validate Student ID
if (!isset($_GET['student_id']) || !is_numeric($_GET['student_id'])) {
    echo "<div class='card'><p><strong>Error:</strong> Invalid Student ID.</p></div>";
    include 'footer.php'; exit();
}
$student_id = (int)$_GET['student_id'];

// 2. Fetch student details
$stmtStudent = $conn->prepare("SELECT username, class FROM students WHERE student_id = ?");
$stmtStudent->bind_param("i", $student_id);
$stmtStudent->execute();
$studentResult = $stmtStudent->get_result();
if ($studentResult->num_rows === 0) {
    echo "<div class='card'><p><strong>Error:</strong> No student found.</p></div>";
    include 'footer.php'; exit();
}
$student = $studentResult->fetch_assoc();

// --- DATA PROCESSING ---

// 3. Fetch main subject results
$stmtResults = $conn->prepare("
    SELECT r.result_id, r.term, r.year, r.target_grade, r.final_grade, r.comments, s.subject_name
    FROM results AS r
    LEFT JOIN subjects AS s ON r.subject_id = s.subject_id
    WHERE r.student_id = ?
");
$stmtResults->bind_param("i", $student_id);
$stmtResults->execute();
$main_results = $stmtResults->get_result()->fetch_all(MYSQLI_ASSOC);

// 4. Fetch ALL assessments for that student from the correct table
$stmtAssessments = $conn->prepare("
    SELECT result_id, assessment_name, mark
    FROM term_assessments -- <-- THIS WAS THE FIX
    WHERE student_id = ?
");
$stmtAssessments->bind_param("i", $student_id);
$stmtAssessments->execute();
$all_assessments = $stmtAssessments->get_result()->fetch_all(MYSQLI_ASSOC);

// 5. Build the final report data structure (This is the "pivot" part)
$report_data = [];
foreach ($main_results as $result) {
    $report_data[$result['result_id']] = [
        'subject_name' => $result['subject_name'], 'term' => $result['term'], 'year' => $result['year'],
        'target_grade' => $result['target_grade'], 'final_grade' => $result['final_grade'],
        'comments' => $result['comments'],
        'ASS 1' => null, // Use the exact names from your 'assessment_name' column
        'ASS 2' => null,
        'EXAM'   => null
    ];
}

foreach ($all_assessments as $assessment) {
    if (isset($report_data[$assessment['result_id']])) {
        $assessment_key = trim($assessment['assessment_name']);
        if (array_key_exists($assessment_key, $report_data[$assessment['result_id']])) {
            $report_data[$assessment['result_id']][$assessment_key] = $assessment['mark'];
        }
    }
}

$report_term = $main_results[0]['term'] ?? 'N/A';
$report_year = $main_results[0]['year'] ?? 'N/A';

?>

<style>
    .report-container{background-color:#fff;padding:2rem;border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,0.08)}.report-header{margin-bottom:1.5rem}.report-header h1{font-size:2rem;margin:0}.report-header p{font-size:1.1rem;color:#555}.table-responsive{overflow-x:auto}.report-table{width:100%;border-collapse:collapse;font-size:.95rem}.report-table th,.report-table td{padding:12px 15px;text-align:left;border-bottom:1px solid #e0e0e0}.report-table th{background-color:#f8f9fa;font-weight:600;text-transform:uppercase;font-size:.85em;color:#333}.report-table td{vertical-align:middle}.report-table .final-mark{font-weight:700}.comments-section{margin-top:2.5rem}.comments-section h2{margin-bottom:1rem;border-bottom:2px solid #eee;padding-bottom:.5rem}.comment-item{margin-bottom:1rem}.comment-item strong{color:var(--primary-color)}
</style>

<div class="report-container">
    <div class="report-header">
        <h1>Progress Report: Term <?= htmlspecialchars($report_term) ?> - <?= htmlspecialchars($report_year) ?></h1>
        <p><strong>Student:</strong> <?= htmlspecialchars($student['username']) ?> | <strong>Class:</strong> <?= htmlspecialchars($student['class']) ?></p>
    </div>

    <div class="table-responsive">
        <table class="report-table">
            <thead>
                <tr>
                    <th>Subject</th>
                    <th>ASS 1 (%)</th>
                    <th>ASS 2 (%)</th>
                    <th>Exam (%)</th>
                    <th>Final Mark (%)</th>
                    <th>Final Grade</th>
                    <th>Target Grade</th>
                    <th>On Target</th>
                    <th>Attitude</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($report_data)): ?>
                    <?php foreach ($report_data as $data): ?>
                        <?php
                            $ass1 = (float)($data['ASS 1'] ?? 0);
                            $ass2 = (float)($data['ASS 2'] ?? 0);
                            $exam = (float)($data['EXAM'] ?? 0);
                            $final_mark = ($ass1 + $ass2 + $exam > 0) ? ($ass1 * 0.25) + ($ass2 * 0.25) + ($exam * 0.5) : 0;
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($data['subject_name']) ?></td>
                            <td><?= htmlspecialchars($data['ASS 1'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($data['ASS 2'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($data['EXAM'] ?? '-') ?></td>
                            <td class="final-mark"><?= $final_mark > 0 ? number_format($final_mark, 2) : '-' ?></td>
                            <td><?= htmlspecialchars($data['final_grade'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($data['target_grade'] ?? '-') ?></td>
                            <td>-</td>
                            <td>-</td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" style="text-align:center; padding: 2rem;">No results found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="comments-section">
        <h2>Teacher's Comments</h2>
        <?php
            $has_comments = false;
            foreach($report_data as $data) {
                if (!empty($data['comments'])) {
                    echo '<div class="comment-item"><p><strong>'.htmlspecialchars($data['subject_name']).':</strong> '.nl2br(htmlspecialchars($data['comments'])).'</p></div>';
                    $has_comments = true;
                }
            }
            if (!$has_comments) { echo '<p>No comments available for this term.</p>'; }
        ?>
    </div>
</div>

<?php
// --- CLEANUP ---
$stmtStudent->close();
$stmtResults->close();
$stmtAssessments->close();
include 'footer.php';
?>