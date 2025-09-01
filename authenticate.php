<?php
session_start();
include 'config.php'; // Make sure this path is correct for your database connection

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $role = $_POST['role']; // Get the selected role from the form

    // --- Super Admin Section (This part remains the same) ---
    if ($role === 'super_admin') {
        $stmt = $conn->prepare("SELECT * FROM super_admins WHERE email = ? AND status = 'active'");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $superAdmin = $result->fetch_assoc();

        if (!$superAdmin) {
            echo "No super admin found with that email.";
            exit();
        }

        if (!password_verify($password, $superAdmin['password'])) {
            echo "Incorrect super admin password.";
            exit();
        }

        // Store session data for Super Admin
        $_SESSION['user_id'] = $superAdmin['super_admin_id'];
        $_SESSION['email'] = $superAdmin['email'];
        $_SESSION['username'] = $superAdmin['username'];
        $_SESSION['role'] = 'super_admin';
        header("Location: super_admin_dashboard.php");
        exit();

    } else {
        // --- CORRECTED LOGIC FOR ALL OTHER USERS (Student, Staff, Admin, Parent) ---

        // STEP 1: Find the user in the 'users' table based on email and role
        $sql = "SELECT * FROM users WHERE email = ? AND role = ?";
        $stmt = $conn->prepare($sql);

        // Check if the SQL prepare statement failed
        if ($stmt === false) {
            // Log the actual database error for debugging instead of showing it to the user
            error_log("Prepare failed: (" . $conn->errno . ") " . $conn->error);
            echo "An error occurred on the server. Please try again later.";
            exit();
        }

        $stmt->bind_param("ss", $email, $role);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        // STEP 2: Verify that a user was found and the password is correct
        if (!$user || !password_verify($password, $user['password'])) {
            // Use a generic message for security
            echo "Invalid login credentials. Please check your email, password, and role.";
            exit();
        }

        // STEP 3: Store the basic user data in the session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];

        // STEP 4: If the user is a 'student', get their specific class information
        if ($user['role'] === 'student') {
            // ** FIX APPLIED HERE **
            // The query now uses `id` in the WHERE clause, which matches your database structure.
            // Change 'id' to the correct column name from your 'students' table (e.g., 'user_id')
            $studentSql = "SELECT class FROM students WHERE user_id = ?";
            $studentStmt = $conn->prepare($studentSql);
            
            if ($studentStmt === false) {
                error_log("Student query prepare failed: (" . $conn->errno . ") " . $conn->error);
                echo "An error occurred while fetching student details.";
                exit();
            }

            $studentStmt->bind_param("i", $user['id']); // 'i' for integer ID
            $studentStmt->execute();
            $studentResult = $studentStmt->get_result();
            $studentData = $studentResult->fetch_assoc();

            if ($studentData) {
                $_SESSION['class_id'] = $studentData['class'];
            }
        }

        // STEP 5: Redirect the user to their respective dashboard
        switch ($user['role']) {
            case 'student':
                header("Location: student/student_home.php");
                break;
            case 'staff':
                header("Location: staff/staff_home.php");
                break;
            case 'admin':
                header("Location: admin/admin_home.php");
                break;
            case 'parent':
                header("Location: parents/parent_home.php");
                break;
            default:
                // A fallback redirect in case of an unexpected role
                header("Location: index.php");
                break;
        }
        exit(); // Important: Stop script execution after redirect
    }
}
?>