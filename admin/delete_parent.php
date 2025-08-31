<?php
session_start();
include '../config.php';

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

// Only allow POST requests for security
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error_message'] = "Invalid request method. Delete operations must be performed via POST.";
    header("Location: parents.php");
    exit();
}

// Check if parent_id is provided
if (!isset($_POST['parent_id']) || empty($_POST['parent_id'])) {
    $_SESSION['error_message'] = "Parent ID is required for deletion.";
    header("Location: parents.php");
    exit();
}

$parent_id = intval($_POST['parent_id']);

// Validate that the parent exists and get parent information
$checkParentQuery = "
    SELECT p.parent_id, p.user_id, p.first_name, p.last_name, p.phone_number, p.relationship,
           u.username, u.email,
           COUNT(psr.student_id) as student_count
    FROM parents p 
    LEFT JOIN users u ON p.user_id = u.id
    LEFT JOIN parent_student_relationships psr ON p.parent_id = psr.parent_id
    WHERE p.parent_id = ?
    GROUP BY p.parent_id";

$stmt = $conn->prepare($checkParentQuery);
if (!$stmt) {
    $_SESSION['error_message'] = "Database error: Failed to prepare parent check query.";
    header("Location: parents.php");
    exit();
}

$stmt->bind_param("i", $parent_id);
$stmt->execute();
$parentResult = $stmt->get_result();
$parentData = $parentResult->fetch_assoc();
$stmt->close();

if (!$parentData) {
    $_SESSION['error_message'] = "Parent not found. The parent may have already been deleted.";
    header("Location: parents.php");
    exit();
}

// Store parent information for logging and success message
$parentName = '';
if (!empty($parentData['first_name']) && !empty($parentData['last_name'])) {
    $parentName = trim($parentData['first_name'] . ' ' . $parentData['last_name']);
} elseif (!empty($parentData['username'])) {
    $parentName = $parentData['username'];
} else {
    $parentName = 'Parent ID: ' . $parent_id;
}

$user_id = $parentData['user_id'];
$student_count = $parentData['student_count'];

// Start transaction for safe deletion
$conn->begin_transaction();

try {
    // Step 1: Delete all parent-student relationships
    if ($student_count > 0) {
        $deleteRelationshipsQuery = "DELETE FROM parent_student_relationships WHERE parent_id = ?";
        $stmt = $conn->prepare($deleteRelationshipsQuery);
        if (!$stmt) {
            throw new Exception("Failed to prepare student relationships deletion query: " . $conn->error);
        }
        $stmt->bind_param("i", $parent_id);
        if (!$stmt->execute()) {
            throw new Exception("Failed to delete parent-student relationships: " . $stmt->error);
        }
        $stmt->close();
    }

    // Step 2: Delete any notifications related to this parent (if they exist)
    $deleteNotificationsQuery = "DELETE FROM notifications WHERE user_id = ? AND (user_type = 'parent' OR user_type = 'user')";
    $stmt = $conn->prepare($deleteNotificationsQuery);
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute(); // Don't fail if this doesn't work, it's not critical
        $stmt->close();
    }

    // Step 3: Delete any other related records (fees, etc.) if needed
    // Note: You might want to keep fee records for historical purposes
    // Uncomment these if you want to delete related records:
    
    /*
    // Delete fees related to this parent's children (optional)
    $deleteFeesQuery = "DELETE f FROM fees f 
                        INNER JOIN parent_student_relationships psr ON f.student_id = psr.student_id 
                        WHERE psr.parent_id = ?";
    $stmt = $conn->prepare($deleteFeesQuery);
    if ($stmt) {
        $stmt->bind_param("i", $parent_id);
        $stmt->execute();
        $stmt->close();
    }
    */

    // Step 4: Delete from parents table
    $deleteParentQuery = "DELETE FROM parents WHERE parent_id = ?";
    $stmt = $conn->prepare($deleteParentQuery);
    if (!$stmt) {
        throw new Exception("Failed to prepare parent deletion query: " . $conn->error);
    }
    $stmt->bind_param("i", $parent_id);
    if (!$stmt->execute()) {
        throw new Exception("Failed to delete parent record: " . $stmt->error);
    }
    $stmt->close();

    // Step 5: Delete from users table (this will cascade to other related records)
    $deleteUserQuery = "DELETE FROM users WHERE id = ?";
    $stmt = $conn->prepare($deleteUserQuery);
    if (!$stmt) {
        throw new Exception("Failed to prepare user deletion query: " . $conn->error);
    }
    $stmt->bind_param("i", $user_id);
    if (!$stmt->execute()) {
        throw new Exception("Failed to delete user account: " . $stmt->error);
    }
    $stmt->close();

    // Commit the transaction
    $conn->commit();

    // Log the deletion for security/audit purposes
    error_log("PARENT DELETED: Admin User ID " . $_SESSION['user_id'] . " deleted parent '$parentName' (ID: $parent_id, User ID: $user_id) with $student_count student relationship(s)");

    // Set success message
    $message = "Parent '$parentName' has been successfully deleted.";
    if ($student_count > 0) {
        $message .= " $student_count student relationship(s) were also removed.";
    }
    $_SESSION['success_message'] = $message;

} catch (Exception $e) {
    // Rollback the transaction
    $conn->rollback();
    
    // Log the error
    error_log("PARENT DELETION FAILED: " . $e->getMessage() . " (Parent ID: $parent_id, Admin: " . $_SESSION['user_id'] . ")");
    
    $_SESSION['error_message'] = "Failed to delete parent. Error: " . $e->getMessage();
}

// Redirect back to parents page
header("Location: parents.php");
exit();
?>