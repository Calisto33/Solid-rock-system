<?php
// session_start() MUST be the very first thing in your file.
session_start();

// Include the database configuration.
include '../config.php';

// --- SECURITY: Check if the user is logged in and is an admin ---
// This is a crucial step for any processing page.
// You should have a session variable that confirms the user's role.
/*
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    // If not an admin, set an error message and redirect.
    $_SESSION['message'] = ['type' => 'error', 'text' => 'You do not have permission to perform this action.'];
    header('Location: fees_management.php');
    exit();
}
*/


// --- 1. VALIDATE THE INCOMING REQUEST ---

// Check if the form was submitted correctly.
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['bulk_action']) || !isset($_POST['selected_ids'])) {
    $_SESSION['message'] = ['type' => 'error', 'text' => 'Invalid request. Please select students and an action.'];
    header('Location: fees_management.php');
    exit();
}

$bulk_action = $_POST['bulk_action'];
$selected_ids = $_POST['selected_ids'];

// Ensure at least one student was selected.
if (empty($selected_ids) || !is_array($selected_ids)) {
    $_SESSION['message'] = ['type' => 'error', 'text' => 'No students were selected.'];
    header('Location: fees_management.php');
    exit();
}

// Sanitize the selected IDs to ensure they are all integers.
$sanitized_ids = array_map('intval', $selected_ids);


// --- 2. PROCESS THE REQUESTED ACTION ---

switch ($bulk_action) {
    // --- CASE 1: EXPORT TO CSV ---
    case 'export_csv':
        // Create a string of placeholders for the IN clause (e.g., "?,?,?")
        $placeholders = implode(',', array_fill(0, count($sanitized_ids), '?'));
        
        // Define data types for bind_param (one 'i' for each integer ID)
        $types = str_repeat('i', count($sanitized_ids));

        $query = "
            SELECT
                s.username AS student_name, s.class, u.username AS parent_name,
                COALESCE(f.total_fee, 0.00) AS total_fee,
                COALESCE(f.amount_paid, 0.00) AS amount_paid,
                (COALESCE(f.total_fee, 0) - COALESCE(f.amount_paid, 0)) as amount_due,
                COALESCE(f.status, 'No Fee Assigned') AS status, f.due_date
            FROM students s
            LEFT JOIN fees f ON s.student_id = f.student_id
            LEFT JOIN parents p ON s.student_id = p.student_id
            LEFT JOIN users u ON p.user_id = u.id
            WHERE s.student_id IN ($placeholders)
            ORDER BY s.class, s.username";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$sanitized_ids);
        $stmt->execute();
        $result = $stmt->get_result();

        // Set headers to force a file download
        $filename = "fees_export_" . date('Y-m-d') . ".csv";
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        // Open output stream
        $output = fopen('php://output', 'w');

        // Write the CSV header row
        fputcsv($output, ['Student Name', 'Class', 'Parent Name', 'Total Fee', 'Amount Paid', 'Amount Due', 'Status', 'Due Date']);

        // Write data rows
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, $row);
        }

        fclose($output);
        $stmt->close();
        exit(); // Stop script execution after generating the file

    // --- CASE 2: SEND REMINDER EMAILS (SIMULATION) ---
    case 'send_reminder':
        $placeholders = implode(',', array_fill(0, count($sanitized_ids), '?'));
        $types = str_repeat('i', count($sanitized_ids));

        // We need the parent's email for this query.
        // **IMPORTANT**: This assumes you have an 'email' column in your 'users' table for parents.
        $query = "
            SELECT s.username AS student_name, u.username AS parent_name, u.email AS parent_email,
                   (f.total_fee - f.amount_paid) as amount_due
            FROM students s
            JOIN fees f ON s.student_id = f.student_id
            LEFT JOIN parents p ON s.student_id = p.student_id
            LEFT JOIN users u ON p.user_id = u.id
            WHERE s.student_id IN ($placeholders)
            AND (f.total_fee - f.amount_paid) > 0"; // Only select students with a balance
            
        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$sanitized_ids);
        $stmt->execute();
        $result = $stmt->get_result();

        $reminders_sent = 0;
        $reminders_failed = 0;
        
        while ($student = $result->fetch_assoc()) {
            if (!empty($student['parent_email'])) {
                // In a real application, you would call your email function here.
                // sendActualReminderEmail($student['parent_email'], $student['parent_name'], $student['student_name'], $student['amount_due']);
                $reminders_sent++;
            } else {
                $reminders_failed++;
            }
        }
        $stmt->close();

        // Set a success message and redirect back.
        $_SESSION['message'] = ['type' => 'success', 'text' => "Reminder simulation complete. Sent: $reminders_sent. Failed (no email): $reminders_failed."];
        header('Location: fees_management.php');
        exit();

    default:
        // Handle any other unknown action
        $_SESSION['message'] = ['type' => 'error', 'text' => 'Invalid bulk action specified.'];
        header('Location: fees_management.php');
        exit();
}
?>