<?php
// get_badge_counts.php - API endpoint for notification badges
session_start();
include '../config.php';

// Set JSON header
header('Content-Type: application/json');

// Check if user is logged in as student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    http_response_code(401);
    echo json_encode([
        'error' => 'Unauthorized access',
        'status' => 'error'
    ]);
    exit();
}

try {
    $student_id = $_SESSION['user_id'];
    $counts = [];
    
    // Get student's actual student_id from students table
    $studentQuery = "SELECT student_id FROM students WHERE user_id = ?";
    $stmt = $conn->prepare($studentQuery);
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $studentResult = $stmt->get_result();
    
    if ($studentResult->num_rows > 0) {
        $studentData = $studentResult->fetch_assoc();
        $student_number = $studentData['student_id'];
        
        // Count assignments (assuming you have an assignments table)
        $assignmentQuery = "SELECT COUNT(*) as count FROM assignments WHERE target_class IN (
            SELECT class FROM students WHERE user_id = ?
        ) AND due_date >= CURDATE()";
        $stmt = $conn->prepare($assignmentQuery);
        $stmt->bind_param("i", $student_id);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $counts['assignments'] = $result->fetch_assoc()['count'] ?? 0;
        } else {
            $counts['assignments'] = 0;
        }
        
        // Count new results
        $resultsQuery = "SELECT COUNT(*) as count FROM results WHERE student_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        $stmt = $conn->prepare($resultsQuery);
        $stmt->bind_param("s", $student_number);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $counts['results'] = $result->fetch_assoc()['count'] ?? 0;
        } else {
            $counts['results'] = 0;
        }
        
    } else {
        // Student not found, set all counts to 0
        $counts['assignments'] = 0;
        $counts['results'] = 0;
    }
    
    // Count notices (assuming you have a notices table)
    $noticesQuery = "SELECT COUNT(*) as count FROM notices WHERE created_at >= DATE_SUB(NOW(), INTERVAL 3 DAY) AND status = 'active'";
    $result = $conn->query($noticesQuery);
    if ($result) {
        $counts['notices'] = $result->fetch_assoc()['count'] ?? 0;
    } else {
        $counts['notices'] = 0;
    }
    
    // Count news (assuming you have a news table)
    $newsQuery = "SELECT COUNT(*) as count FROM news WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
    $result = $conn->query($newsQuery);
    if ($result) {
        $counts['news'] = $result->fetch_assoc()['count'] ?? 0;
    } else {
        $counts['news'] = 0;
    }
    
    // Count events (assuming you have an events table)
    $eventsQuery = "SELECT COUNT(*) as count FROM events WHERE event_date >= CURDATE() AND event_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)";
    $result = $conn->query($eventsQuery);
    if ($result) {
        $counts['events'] = $result->fetch_assoc()['count'] ?? 0;
    } else {
        $counts['events'] = 0;
    }
    
    // Add status and timestamp
    $counts['status'] = 'success';
    $counts['timestamp'] = date('Y-m-d H:i:s');
    
    echo json_encode($counts);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error: ' . $e->getMessage(),
        'status' => 'error',
        'assignments' => 0,
        'results' => 0,
        'notices' => 0,
        'news' => 0,
        'events' => 0
    ]);
}

$conn->close();
?>