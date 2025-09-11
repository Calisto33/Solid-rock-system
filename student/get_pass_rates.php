<?php
// get_pass_rates.php - API endpoint for student dashboard pass rate chart
session_start();
include '../config.php';

// Set JSON header
header('Content-Type: application/json');

// Check if user is logged in as student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    http_response_code(401);
    echo json_encode([
        'success' => false, 
        'error' => 'Unauthorized access',
        'details' => 'Student login required'
    ]);
    exit();
}

try {
    // Get user ID from session. This is assumed to be the ID from the 'users' table.
    $user_id = $_SESSION['user_id'];
    
    // This query calculates the pass rate for each subject for the logged-in student.
    // It assumes a 'students' table links the user_id to a student record which then links to results.
    $query = "
        SELECT 
            r.subject,
            AVG(CASE WHEN r.final_mark >= 50 THEN 1 ELSE 0 END * 100) as pass_rate
        FROM results r
        JOIN students s ON r.student_id = s.student_id 
        WHERE s.user_id = ? 
        AND r.academic_year = '2025'
        AND r.final_mark IS NOT NULL
        GROUP BY r.subject
        ORDER BY r.subject";
    
    $stmt = $conn->prepare($query);

    // DEVELOPER NOTE: Make sure the type here matches your 'users.user_id' column type.
    // Use "i" for integer, "s" for string.
    $stmt->bind_param("i", $user_id);

    $stmt->execute();
    $result = $stmt->get_result();
    
    $subjects = [];
    $rates = [];
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $subjects[] = ucfirst($row['subject']);
            $rates[] = round(floatval($row['pass_rate']), 1);
        }
    } else {
        // If no results are found, provide some sample data so the chart doesn't look empty.
        // You can remove this block if you prefer an empty chart for students with no results.
        $subjects = ['Mathematics', 'English', 'Science', 'History', 'Geography'];
        $rates = [78, 85, 72, 90, 68];
    }
    
    // Return a success response with the data
    echo json_encode([
        'success' => true,
        'subjects' => $subjects,
        'rates' => $rates,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    // If a database error occurs, send a structured error response.
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error',
        'details' => $e->getMessage()
    ]);
}

$stmt->close();
$conn->close();
?>
