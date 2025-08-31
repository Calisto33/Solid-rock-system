<?php
session_start();
include '../config.php'; // Database connection

// Get class and date from the form submission
$class = $_POST['class'];
$date = $_POST['date'];
$statusData = $_POST['status']; // Array of attendance statuses keyed by student_id

// Check if the current user is a student or staff
$user_role = $_SESSION['role']; // Assuming 'student' or 'staff' is stored in session
$updated_by = $user_role; // Track who updated the attendance

foreach ($statusData as $student_id => $status) {
    // Check if there's already an attendance record for this student on this date
    $checkQuery = "SELECT * FROM attendance WHERE student_id = ? AND date = ?";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param("is", $student_id, $date);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Update existing record if it exists
        $updateQuery = "UPDATE attendance SET status = ?, updated_by = ?, last_updated = NOW() WHERE student_id = ? AND date = ?";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bind_param("ssis", $status, $updated_by, $student_id, $date);
        $updateStmt->execute();
        $updateStmt->close();
    } else {
        // Insert a new attendance record if none exists
        $insertQuery = "INSERT INTO attendance (student_id, date, status, class, updated_by) VALUES (?, ?, ?, ?, ?)";
        $insertStmt = $conn->prepare($insertQuery);
        $insertStmt->bind_param("issss", $student_id, $date, $status, $class, $updated_by);
        $insertStmt->execute();
        $insertStmt->close();
    }
}

$conn->close();

// Redirect back to the attendance form or staff view
header("Location: staff_attendance.php?class=$class&date=$date");
exit();
?>
