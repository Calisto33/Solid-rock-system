<?php
session_start();
// Ensure this path is correct and config.php correctly sets up $conn
include '../config.php';

header('Content-Type: application/json'); // Set header for JSON response

$response = ['success' => false, 'message' => ''];

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'parent') {
    $response['message'] = 'Unauthorized access.';
    echo json_encode($response);
    exit();
}

$user_id = $_SESSION['user_id']; // Parent's user_id from the 'users' table

if (isset($_POST['notification_id'])) {
    $notification_id = $_POST['notification_id'];

    // First, find the student_id linked to the logged-in parent's user_id
    $student_id_linked_to_parent = null;
    $stmt_get_student_id = $conn->prepare("SELECT student_id FROM parents WHERE user_id = ?");  
    if ($stmt_get_student_id) {
        $stmt_get_student_id->bind_param("i", $user_id);
        $stmt_get_student_id->execute();
        $result_get_student_id = $stmt_get_student_id->get_result();
        if ($row = $result_get_student_id->fetch_assoc()) {
            $student_id_linked_to_parent = $row['student_id'];
        }
        $stmt_get_student_id->close();
    }

    if ($student_id_linked_to_parent) {
        // Now, update the notification status in the database
        // Crucially, verify it belongs to the *student* linked to this parent.
        $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ? AND user_type = 'student'");
        if ($stmt) {
            $stmt->bind_param("ii", $notification_id, $student_id_linked_to_parent);
            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    $response['success'] = true;
                    $response['message'] = 'Notification marked as read.';
                } else {
                    $response['message'] = 'Notification not found, already read, or not owned by this student.';
                }
            } else {
                $response['message'] = 'Database execution error: ' . $stmt->error;
            }
            $stmt->close();
        } else {
            $response['message'] = 'Database preparation error for update: ' . $conn->error;
        }
    } else {
        $response['message'] = 'Student ID linked to the current parent not found.';
    }
} else {
    $response['message'] = 'Invalid notification ID provided.';
}

$conn->close();
echo json_encode($response);
?>