<?php
// utils/notification_functions.php

/**
 * Sends an in-app notification to a user.
 *
 * @param int $user_id The ID of the user (student or admin).
 * @param string $user_type The type of user ('student', 'admin', 'super_admin').
 * @param string $message The notification message.
 * @param string $notification_type A category for the notification (e.g., 'fee_payment', 'overdue_fee').
 * @param mysqli $conn The database connection object.
 * @return bool True on success, false on failure.
 */
function send_notification($user_id, $user_type, $message, $notification_type, $conn) {
    $insert_query = "INSERT INTO notifications (user_id, user_type, message, notification_type) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($insert_query);
    if ($stmt === false) {
        error_log("Failed to prepare notification insert statement: " . $conn->error);
        return false;
    }

    $stmt->bind_param("isss", $user_id, $user_type, $message, $notification_type);
    if ($stmt->execute()) {
        $stmt->close();
        return true;
    } else {
        error_log("Failed to execute notification insert statement: " . $stmt->error);
        $stmt->close();
        return false;
    }
}

/**
 * Triggers a notification for a fee payment received.
 * This is a wrapper for send_notification for specific use case.
 *
 * @param int $student_id The ID of the student.
 * @param float $amount_paid The amount that was just paid.
 * @param mysqli $conn The database connection object.
 * @return bool True on success, false on failure.
 */
function trigger_payment_received_notification($student_id, $amount_paid, $conn) {
    // Fetch student's name for a more personalized message
    $student_name = "Student"; // Default
    $student_query = "SELECT first_name, last_name FROM students WHERE student_id = ?";
    $stmt_student = $conn->prepare($student_query);
    if ($stmt_student) {
        $stmt_student->bind_param("i", $student_id);
        $stmt_student->execute();
        $result_student = $stmt_student->get_result();
        if ($result_student->num_rows > 0) {
            $row = $result_student->fetch_assoc();
            $student_name = htmlspecialchars($row['first_name'] . ' ' . $row['last_name']);
        }
        $stmt_student->close();
    }

    $message = "Dear " . $student_name . ", a payment of $" . number_format($amount_paid, 2) . " has been successfully recorded for your fees.";
    $notification_type = "fee_payment";

    return send_notification($student_id, 'student', $message, $notification_type, $conn);
}

// You can add more notification functions here as needed:
// function trigger_overdue_fee_notification($student_id, $conn) { ... }
// function trigger_admin_alert($admin_id, $message, $conn) { ... }

?>