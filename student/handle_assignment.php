<?php
// Start the session to access logged-in user data
session_start();

// Include the database connection
include '../config.php';

// --- 1. VALIDATION AND SECURITY CHECKS ---

// Check if the user is logged in as a student
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'student') {
    // Redirect to login if not a student
    header("Location: ../login.php?error=unauthorized");
    exit();
}

// Check if the form was submitted via the POST method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // If not, redirect back to the assignments page
    header("Location: view_assignments.php?error=invalid_request");
    exit();
}

// Check if the required POST data and the file exist
if (!isset($_POST['assignment_id'], $_POST['user_id'], $_FILES['assignment_file'])) {
    die("Error: Missing form data.");
}

// Check if there was an upload error
if ($_FILES['assignment_file']['error'] !== UPLOAD_ERR_OK) {
    // Redirect with an error message based on the upload error code
    header("Location: submit_assignment.php?id=" . $_POST['assignment_id'] . "&error=upload_failed");
    exit();
}


// --- 2. FILE HANDLING AND UPLOADING ---

// Define the directory to store uploaded assignments.
// It's good practice to place this outside the public web root if possible, but for simplicity, we'll use a folder inside the project.
$uploadDirectory = '../uploads/assignments/';

// Create the directory if it doesn't exist
if (!is_dir($uploadDirectory)) {
    mkdir($uploadDirectory, 0755, true);
}

// Get the uploaded file's information
$fileName = basename($_FILES['assignment_file']['name']);
$fileTmpName = $_FILES['assignment_file']['tmp_name'];

// Create a unique filename to prevent overwriting existing files
// Format: studentID_assignmentID_timestamp_originalFileName.ext
$uniqueFileName = $_POST['user_id'] . '_' . $_POST['assignment_id'] . '_' . time() . '_' . $fileName;
$targetFilePath = $uploadDirectory . $uniqueFileName;


// Move the uploaded file from its temporary location to the target directory
if (move_uploaded_file($fileTmpName, $targetFilePath)) {
    
    // --- 3. DATABASE INSERTION ---

    // The file was uploaded successfully, now insert the record into the database.
    $assignment_id = $_POST['assignment_id'];
    $student_id = $_POST['user_id'];
    $comments = $_POST['comments'];
    // We store the path to the file, not the file itself, in the database.
    $filePathInDb = $targetFilePath; 

    // Use a prepared statement to prevent SQL injection
    $query = "INSERT INTO submissions (assignment_id, student_id, submission_file, comments) VALUES (?, ?, ?, ?)";
    
    $stmt = $conn->prepare($query);
    // 'iiss' means we are binding two integers and two strings
    $stmt->bind_param("iiss", $assignment_id, $student_id, $filePathInDb, $comments);

    if ($stmt->execute()) {
        // SUCCESS: The submission was saved successfully.
        // Redirect back to the assignments page with a success message.
        header("Location: view_assignments.php?success=submitted");
        exit();
    } else {
        // DATABASE ERROR: The database insertion failed.
        // It's good practice to log the detailed error for the developer.
        error_log("Database error: " . $stmt->error);
        // Redirect with a generic database error message.
        header("Location: submit_assignment.php?id=" . $assignment_id . "&error=db_error");
        exit();
    }

} else {
    // UPLOAD ERROR: The file could not be moved to the target directory.
    // This could be a permissions issue on the server.
    header("Location: submit_assignment.php?id=" . $_POST['assignment_id'] . "&error=move_failed");
    exit();
}
?>
