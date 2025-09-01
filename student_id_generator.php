<?php
/**
 * Simple Student ID Generator - Minimal Working Version
 * Replace your existing student_id_generator.php with this
 */

function generateStudentId($conn, $year = null) {
    if ($year === null) {
        $year = date('Y');
    }
    
    $yearSuffix = substr($year, -2);
    
    // Simple count - just count all students
    $result = $conn->query("SELECT COUNT(*) as total FROM students");
    $row = $result->fetch_assoc();
    $count = $row['total'] + 1;
    
    $sequentialNumber = str_pad($count, 3, '0', STR_PAD_LEFT);
    $randomLetter = chr(rand(65, 90)); // A-Z
    
    return "WTC-{$yearSuffix}{$sequentialNumber}{$randomLetter}";
}

function createStudentRecord($conn, $user_id, $class = null, $course = null, $year = null) {
    try {
        // Generate student ID
        $student_id = generateStudentId($conn);
        
        // Simple insert - handle NULL values explicitly
        $class = $class ?: NULL;
        $course = $course ?: NULL;
        $year = $year ?: NULL;
        
        // Use a simple insert statement
        $query = "INSERT INTO students (student_id, user_id, class, course, year, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($query);
        
        if (!$stmt) {
            error_log("Prepare failed: " . $conn->error);
            return false;
        }
        
        // Bind parameters with proper types
        $stmt->bind_param("sissi", $student_id, $user_id, $class, $course, $year);
        
        if ($stmt->execute()) {
            $stmt->close();
            return $student_id;
        } else {
            error_log("Execute failed: " . $stmt->error);
            $stmt->close();
            return false;
        }
        
    } catch (Exception $e) {
        error_log("Exception: " . $e->getMessage());
        return false;
    }
}

function getStudentStats($conn, $year = null) {
    if ($year === null) {
        $year = date('Y');
    }
    
    $query = "SELECT 
                COUNT(*) as total_students,
                COUNT(CASE WHEN class IS NOT NULL THEN 1 END) as assigned_students,
                COUNT(CASE WHEN class IS NULL THEN 1 END) as unassigned_students
              FROM students s 
              JOIN users u ON s.user_id = u.id 
              WHERE u.role = 'student'";
    
    $result = $conn->query($query);
    if ($result) {
        return $result->fetch_assoc();
    }
    
    return ['total_students' => 0, 'assigned_students' => 0, 'unassigned_students' => 0];
}
?>