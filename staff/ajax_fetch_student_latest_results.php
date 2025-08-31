<?php
// ajax_fetch_student_latest_results.php

include '../config.php'; // Your DB connection

header('Content-Type: application/json');

$student_id = filter_input(INPUT_GET, 'student_id', FILTER_SANITIZE_STRING);

$response = [
    'subject_id' => null,
    'mark_percentage' => null,
    'exam_grade' => null,
    'target_grade' => null,
    'attitude_to_learning' => null,
    'error' => null
];

if (!$student_id) {
    $response['error'] = 'Invalid student ID.';
    echo json_encode($response);
    exit;
}

// IMPORTANT: Adjust this query to match your actual results table schema.
// This is a placeholder query assuming you have a table like 'student_results'
// that links to students and subjects, and stores the latest results.
// You might need to join multiple tables or get the LATEST entry for each subject.
// For simplicity, this example tries to fetch *one* recent result for any subject.
// A more robust solution would allow fetching results for a specific subject or iterate through multiple.
$stmt = $conn->prepare("
    SELECT 
        sr.subject_id, 
        sr.mark_percentage, 
        sr.exam_grade, 
        sr.target_grade, 
        sr.attitude_to_learning
    FROM student_results sr
    WHERE sr.student_id = ?
    ORDER BY sr.result_date DESC, sr.result_id DESC -- Assuming a date or ID to get the 'latest'
    LIMIT 1
");

if ($stmt) {
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $response['subject_id'] = $row['subject_id'];
        $response['mark_percentage'] = $row['mark_percentage'];
        $response['exam_grade'] = $row['exam_grade'];
        $response['target_grade'] = $row['target_grade'];
        $response['attitude_to_learning'] = $row['attitude_to_learning'];
    } else {
        $response['error'] = 'No results found for this student.';
    }
    $stmt->close();
} else {
    $response['error'] = 'Database query preparation failed: ' . $conn->error;
}

echo json_encode($response);
$conn->close();
?>