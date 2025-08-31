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
    $parent_id = intval($_POST['parent_id']);
    $user_id = intval($_POST['user_id']);
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $phone_number = trim($_POST['phone_number']);
    $relationship = $_POST['relationship'];
    $address = trim($_POST['address']);
    $new_password = trim($_POST['new_password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    // Validate required fields
    if (empty($parent_id) || empty($user_id) || empty($first_name) || empty($last_name) || 
        empty($username) || empty($email) || empty($phone_number) || empty($relationship) || empty($address)) {
        $_SESSION['error_message'] = "All fields are required.";
        header("Location: edit_parent_information.php?parent_id=" . $parent_id);
        exit();
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error_message'] = "Please enter a valid email address.";
        header("Location: edit_parent_information.php?parent_id=" . $parent_id);
        exit();
    }

    // Password validation (only if password is being changed)
    if (!empty($new_password) || !empty($confirm_password)) {
        if ($new_password !== $confirm_password) {
            $_SESSION['error_message'] = "New password and confirm password do not match.";
            header("Location: edit_parent_information.php?parent_id=" . $parent_id);
            exit();
        }
        if (strlen($new_password) < 6) {
            $_SESSION['error_message'] = "Password must be at least 6 characters long.";
            header("Location: edit_parent_information.php?parent_id=" . $parent_id);
            exit();
        }
    }

    // Check for duplicate username/email (excluding current user)
    $checkDuplicateQuery = "SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?";
    $stmt = $conn->prepare($checkDuplicateQuery);
    $stmt->bind_param("ssi", $username, $email, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $_SESSION['error_message'] = "Username or email already exists for another user.";
        $stmt->close();
        header("Location: edit_parent_information.php?parent_id=" . $parent_id);
        exit();
    }
    $stmt->close();

    // Check if parent exists
    $checkParentQuery = "SELECT parent_id FROM parents WHERE parent_id = ?";
    $stmt = $conn->prepare($checkParentQuery);
    $stmt->bind_param("i", $parent_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        $_SESSION['error_message'] = "Parent not found.";
        $stmt->close();
        header("Location: parents.php");
        exit();
    }
    $stmt->close();

    // Start transaction
    $conn->begin_transaction();

    try {
        // Update users table
        if (!empty($new_password)) {
            // Update with new password
            $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);
            $userUpdateQuery = "UPDATE users SET first_name = ?, last_name = ?, username = ?, email = ?, password = ? WHERE id = ?";
            $userStmt = $conn->prepare($userUpdateQuery);
            $userStmt->bind_param("sssssi", $first_name, $last_name, $username, $email, $hashedPassword, $user_id);
        } else {
            // Update without changing password
            $userUpdateQuery = "UPDATE users SET first_name = ?, last_name = ?, username = ?, email = ? WHERE id = ?";
            $userStmt = $conn->prepare($userUpdateQuery);
            $userStmt->bind_param("ssssi", $first_name, $last_name, $username, $email, $user_id);
        }

        if (!$userStmt->execute()) {
            throw new Exception("Failed to update user information: " . $userStmt->error);
        }
        $userStmt->close();

        // Update parents table (only the fields that exist in parents table)
        $parentUpdateQuery = "UPDATE parents SET phone_number = ?, relationship = ?, address = ? WHERE parent_id = ?";
        $parentStmt = $conn->prepare($parentUpdateQuery);
        $parentStmt->bind_param("sssi", $phone_number, $relationship, $address, $parent_id);
        
        if (!$parentStmt->execute()) {
            throw new Exception("Failed to update parent information: " . $parentStmt->error);
        }
        $parentStmt->close();

        // Commit transaction
        $conn->commit();

        $_SESSION['success_message'] = "Parent information updated successfully!";
        header("Location: edit_parent_information.php?parent_id=" . $parent_id);
        exit();

    } catch (Exception $e) {
        // Rollback transaction
        $conn->rollback();
        
        // Log the error
        error_log("Parent update failed: " . $e->getMessage());
        
        $_SESSION['error_message'] = "Failed to update parent information: " . $e->getMessage();
        header("Location: edit_parent_information.php?parent_id=" . $parent_id);
        exit();
    }

} else {
    // If not a POST request, redirect to parents page
    header("Location: parents.php");
    exit();
}
?>