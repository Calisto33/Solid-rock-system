<?php
session_start();
include '../config.php';

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $phone_number = trim($_POST['phone_number']);
    $relationship = $_POST['relationship'];
    $address = trim($_POST['address']);
    $role = 'parent'; // Fixed role for parents
    $student_ids = isset($_POST['student_ids']) ? $_POST['student_ids'] : [];

    // Validate required fields
    if (empty($first_name) || empty($last_name) || empty($username) || empty($email) || 
        empty($password) || empty($phone_number) || empty($relationship) || empty($address)) {
        $_SESSION['error_message'] = "All fields are required.";
        header("Location: add_parent.php");
        exit();
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error_message'] = "Invalid email format.";
        header("Location: add_parent.php");
        exit();
    }

    // Validate password length
    if (strlen($password) < 6) {
        $_SESSION['error_message'] = "Password must be at least 6 characters long.";
        header("Location: add_parent.php");
        exit();
    }

    // Check if username already exists (using correct column name: id)
    $checkUsernameQuery = "SELECT id FROM users WHERE username = ?";
    $stmt = $conn->prepare($checkUsernameQuery);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $_SESSION['error_message'] = "Username already exists. Please choose a different username.";
        $stmt->close();
        header("Location: add_parent.php");
        exit();
    }
    $stmt->close();

    // Check if email already exists (using correct column name: id)
    $checkEmailQuery = "SELECT id FROM users WHERE email = ?";
    $stmt = $conn->prepare($checkEmailQuery);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $_SESSION['error_message'] = "Email already exists. Please use a different email address.";
        $stmt->close();
        header("Location: add_parent.php");
        exit();
    }
    $stmt->close();

    // Start transaction
    $conn->begin_transaction();

    try {
        // Hash the password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Insert into users table (with correct column structure)
        $userQuery = "INSERT INTO users (username, email, password, first_name, last_name, role, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($userQuery);
        
        if (!$stmt) {
            throw new Exception("Failed to prepare user query: " . $conn->error);
        }
        
        $stmt->bind_param("ssssss", $username, $email, $hashedPassword, $first_name, $last_name, $role);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to create user account: " . $stmt->error);
        }
        
        $user_id = $conn->insert_id;
        $stmt->close();

        // Insert into parents table (based on your actual structure)
        // Assuming parents table doesn't have first_name, last_name (they're in users table)
        $parentQuery = "INSERT INTO parents (user_id, phone_number, relationship, address, created_at) VALUES (?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($parentQuery);
        
        if (!$stmt) {
            throw new Exception("Failed to prepare parent query: " . $conn->error);
        }
        
        $stmt->bind_param("isss", $user_id, $phone_number, $relationship, $address);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to create parent record: " . $stmt->error);
        }
        
        $parent_id = $conn->insert_id;
        $stmt->close();

        // Insert student relationships if any students were selected
        $successful_assignments = 0;
        $failed_assignments = 0;
        
        if (!empty($student_ids)) {
            // Use correct table name: student_parent_relationships (not parent_student_relationships)
            // Based on your database structure shown earlier
            $relationshipQuery = "INSERT INTO student_parent_relationships (student_id, parent_id, relationship_type, is_primary_contact, is_emergency_contact, can_pick_up, created_at, created_by) VALUES (?, ?, ?, 0, 0, 1, NOW(), ?)";
            $stmt = $conn->prepare($relationshipQuery);
            
            if (!$stmt) {
                throw new Exception("Failed to prepare relationship query: " . $conn->error);
            }
            
            foreach ($student_ids as $student_id) {
                $student_id = intval($student_id); // Ensure it's an integer
                
                // Validate that the student exists (using correct column name from your students table)
                $checkStudentQuery = "SELECT student_id FROM students WHERE student_id = ?";
                $checkStmt = $conn->prepare($checkStudentQuery);
                $checkStmt->bind_param("i", $student_id);
                $checkStmt->execute();
                $studentResult = $checkStmt->get_result();
                
                if ($studentResult->num_rows > 0) {
                    $stmt->bind_param("sisi", $student_id, $parent_id, $relationship, $_SESSION['user_id']);
                    if ($stmt->execute()) {
                        $successful_assignments++;
                    } else {
                        $failed_assignments++;
                        error_log("Failed to assign student ID $student_id to parent ID $parent_id: " . $stmt->error);
                    }
                } else {
                    $failed_assignments++;
                    error_log("Student ID $student_id does not exist");
                }
                $checkStmt->close();
            }
            
            $stmt->close();
        }

        // Commit transaction
        $conn->commit();

        // Set success message
        $student_count = $successful_assignments;
        if ($student_count > 0) {
            if ($failed_assignments > 0) {
                $_SESSION['success_message'] = "Parent account created successfully! $student_count student(s) assigned ($failed_assignments failed).";
            } else {
                $_SESSION['success_message'] = "Parent account created successfully with $student_count student(s) assigned!";
            }
        } else {
            $_SESSION['success_message'] = "Parent account created successfully! You can assign students from the parent management page.";
        }
        
        header("Location: parents.php");
        exit();

    } catch (Exception $e) {
        // Rollback transaction
        $conn->rollback();
        
        // Log the error
        error_log("Parent registration failed: " . $e->getMessage());
        
        $_SESSION['error_message'] = "Failed to create parent account. Error: " . $e->getMessage();
        header("Location: add_parent.php");
        exit();
    }

} else {
    // If not a POST request, redirect to add parent page
    header("Location: add_parent.php");
    exit();
}
?>