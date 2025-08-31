<?php
// Set the content type to JSON for the response
header('Content-Type: application/json');

// --- STEP 1: DATABASE CONNECTION ---
// IMPORTANT: Replace with your actual database credentials
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'wisetech';

// Create a connection
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check for connection errors
if ($conn->connect_error) {
    // Output a JSON error message and exit
    echo json_encode(['error' => 'Database connection failed: ' . $conn->connect_error]);
    exit();
}

// --- STEP 2: SQL QUERY ---
// This query joins subjects, attendance, and results tables.
// ASSUMPTIONS:
// - You have a 'subjects' table with 'subject_id' and 'subject_name'.
// - You have an 'attendance' table with 'subject_id' to log attendance.
// - You have a 'results' table with 'subject_id' and 'score', where score >= 50 is a pass.
// You MUST adapt this query to your database schema.
$sql = "
    SELECT
        s.subject_name,
        COUNT(DISTINCT a.attendance_id) AS total_attendance,
        AVG(CASE WHEN r.score >= 50 THEN 100.0 ELSE 0.0 END) AS pass_rate
    FROM
        subjects s
    LEFT JOIN
        attendance a ON s.subject_id = a.subject_id
    LEFT JOIN
        results r ON s.subject_id = r.subject_id
    GROUP BY
        s.subject_id, s.subject_name
    ORDER BY
        s.subject_name;
";

$result = $conn->query($sql);

// Check for query errors
if (!$result) {
    echo json_encode(['error' => 'Query failed: ' . $conn->error]);
    $conn->close();
    exit();
}

// --- STEP 3: PROCESS DATA FOR CHART.JS ---
$labels = [];
$attendanceData = [];
$passRateData = [];

while ($row = $result->fetch_assoc()) {
    $labels[] = $row['subject_name'];
    $attendanceData[] = (int) $row['total_attendance'];
    // Round the pass rate to 2 decimal places
    $passRateData[] = round((float) $row['pass_rate'], 2);
}

// --- STEP 4: OUTPUT JSON ---
// Combine the processed data into a single object
$chartData = [
    'labels' => $labels,
    'attendance' => $attendanceData,
    'passRate' => $passRateData,
];

echo json_encode($chartData);

// Close the database connection
$conn->close();
?>