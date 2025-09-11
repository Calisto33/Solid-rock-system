<?php
// Start the session at the very top.
session_start();

// Include your database connection
include '../config.php';

// Get username and password from the login form submission
$username = $_POST['username'];
$password = $_POST['password'];

// Use a prepared statement to prevent SQL injection
$stmt = $conn->prepare("SELECT id, username, password FROM students WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $student = $result->fetch_assoc();

    // IMPORTANT: Verify the hashed password
    // Your passwords in the database should be hashed using password_hash()
    if (password_verify($password, $student['password'])) {
        // Password is correct!
        // Store the student's primary key `id` in the session. THIS IS THE KEY STEP.
        $_SESSION['student_id'] = $student['id'];
        $_SESSION['username'] = $student['username'];

        // Redirect to the student profile page
        header("Location: student_profile.php");
        exit();
    }
}

// If login fails, redirect back to the login page with an error
header("Location: login.php?error=1");
exit();

?>