<?php
session_start();
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['staff', 'admin'])) {
    header("Location: ../login.php");
    exit();
}

include '../config.php'; // Your DB connection

// Get the logged-in user's role
$user_role = $_SESSION['role'];
$teacher_id = $_SESSION['user_id'];

// Get IDs and term/year from the request
$class_id = $_REQUEST['class_id'] ?? null;
$subject_id = $_REQUEST['subject_id'] ?? null;
$class_name = $_REQUEST['class_name'] ?? null;
$subject_name = $_REQUEST['subject_name'] ?? null;
$term = $_REQUEST['term'] ?? null;
$year = $_REQUEST['year'] ?? null;
$assessment_type = $_REQUEST['assessment_type'] ?? 'Continuous Assessment';

if ((!$class_id || !$subject_id) && (!$class_name || !$subject_name)) {
    header("Location: select_class.php");
    exit();
}

// If we have names but not IDs, get the IDs
if ($class_name && !$class_id) {
    $class_query = "SELECT class_id FROM classes WHERE class_name = ?";
    $stmt = $conn->prepare($class_query);
    $stmt->bind_param("s", $class_name);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $class_id = $row['class_id'];
    }
    $stmt->close();
}

if ($subject_name && !$subject_id) {
    $subject_query = "SELECT subject_id FROM subjects WHERE subject_name = ?";
    $stmt = $conn->prepare($subject_query);
    $stmt->bind_param("s", $subject_name);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $subject_id = $row['subject_id'];
    }
    $stmt->close();
}

// SECURITY CHECK: Verify teacher has permission for this class-subject combination
// First, check if teacher_subjects table exists and has the required columns
$permission_granted = false;
if ($user_role === 'admin') {
    $permission_granted = true; // Admins always have permission
} else {
    try {
        // Check if teacher_subjects table exists
        $table_check = $conn->query("SHOW TABLES LIKE 'teacher_subjects'");
        if ($table_check && $table_check->num_rows > 0) {
            // Table exists, now check if it has the required columns
            $columns_check = $conn->query("SHOW COLUMNS FROM teacher_subjects");
            $has_class_id = false;
            $has_subject_id = false;
            $has_teacher_id = false;
            
            while ($col = $columns_check->fetch_assoc()) {
                if ($col['Field'] === 'class_id') $has_class_id = true;
                if ($col['Field'] === 'subject_id') $has_subject_id = true;
                if ($col['Field'] === 'teacher_id') $has_teacher_id = true;
            }
            
            if ($has_class_id && $has_subject_id && $has_teacher_id && $class_id && $subject_id) {
                $permission_check = "
                    SELECT COUNT(*) as has_permission 
                    FROM teacher_subjects 
                    WHERE teacher_id = ? AND subject_id = ? AND class_id = ?
                ";
                $stmt_permission = $conn->prepare($permission_check);
                if ($stmt_permission) {
                    $stmt_permission->bind_param("iii", $_SESSION['user_id'], $subject_id, $class_id);
                    $stmt_permission->execute();
                    $permission_result = $stmt_permission->get_result();
                    $permission_data = $permission_result->fetch_assoc();
                    $stmt_permission->close();
                    
                    $permission_granted = ($permission_data['has_permission'] > 0);
                } else {
                    $permission_granted = true; // If query fails, grant permission
                }
            } else {
                $permission_granted = true; // If columns don't exist, grant permission
            }
        } else {
            $permission_granted = true; // If table doesn't exist, grant permission
        }
    } catch (Exception $e) {
        $permission_granted = true; // If any error occurs, grant permission to avoid blocking
        $debug_info[] = "Permission check error: " . $e->getMessage();
    }
}

// If permission is not granted, redirect
if (!$permission_granted) {
    header("Location: select_class.php?error=no_permission");
    exit();
}

// Function to calculate grade based on your advanced grading system
function calculateGrade($mark) {
    if ($mark >= 100) return 'A***';
    if ($mark >= 95) return 'A**';
    if ($mark >= 90) return 'A*';
    if ($mark >= 88) return 'Aa';
    if ($mark >= 85) return 'Ab';
    if ($mark >= 80) return 'Ac';
    if ($mark >= 78) return 'Ba';
    if ($mark >= 75) return 'Bb';
    if ($mark >= 70) return 'Bc';
    if ($mark >= 68) return 'Ca';
    if ($mark >= 65) return 'Cb';
    if ($mark >= 60) return 'Cc';
    if ($mark >= 58) return 'Da';
    if ($mark >= 55) return 'Db';
    if ($mark >= 50) return 'Dc';
    if ($mark >= 48) return 'Ea';
    if ($mark >= 45) return 'Eb';
    if ($mark >= 40) return 'Ec';
    if ($mark >= 30) return 'F';
    return 'G';
}

// Simplified Zimbabwe grading for compatibility
function calculateGradeSimple($mark) {
    if ($mark >= 80) return 'A';
    if ($mark >= 70) return 'B';
    if ($mark >= 60) return 'C';
    if ($mark >= 50) return 'D';
    if ($mark >= 40) return 'E';
    return 'F';
}

// Get teacher information
$teacherQuery = "SELECT first_name, last_name, username FROM users WHERE id = ?";
$teacherStmt = $conn->prepare($teacherQuery);
$teacherStmt->bind_param("i", $teacher_id);
$teacherStmt->execute();
$teacherResult = $teacherStmt->get_result();
$teacher = $teacherResult->fetch_assoc();
$teacherName = trim(($teacher['first_name'] ?? '') . ' ' . ($teacher['last_name'] ?? '')) ?: $teacher['username'];

// Fetch class and subject names for display if we have IDs
if ($class_id && !$class_name) {
    $stmt_class = $conn->prepare("SELECT class_name FROM classes WHERE class_id = ?");
    $stmt_class->bind_param("i", $class_id);
    $stmt_class->execute();
    $class_result = $stmt_class->get_result();
    $class_name = $class_result->num_rows > 0 ? $class_result->fetch_assoc()['class_name'] : 'Unknown Class';
    $stmt_class->close();
}

if ($subject_id && !$subject_name) {
    $stmt_subject = $conn->prepare("SELECT subject_name FROM subjects WHERE subject_id = ?");
    $stmt_subject->bind_param("i", $subject_id);
    $stmt_subject->execute();
    $subject_result = $stmt_subject->get_result();
    $subject_name = $subject_result->num_rows > 0 ? $subject_result->fetch_assoc()['subject_name'] : 'Unknown Subject';
    $stmt_subject->close();
}

$students_with_marks = [];
$debug_info = [];
$message = '';
$message_type = '';

// Handle marks submission (Enhanced version from first code)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['marks'])) {
    $marks_data = $_POST['marks'] ?? [];
    
    $conn->begin_transaction();
    
    try {
        $success_count = 0;
        
        foreach ($marks_data as $student_id => $data) {
            // Handle both systems - advanced assessments and simple marks
            if (isset($data['ass1']) || isset($data['ass2']) || isset($data['exam'])) {
                // Advanced assessment system
                $ass1 = floatval($data['ass1'] ?? 0);
                $ass2 = floatval($data['ass2'] ?? 0);
                $exam = floatval($data['exam'] ?? 0);
                $final_mark = floatval($data['final_mark'] ?? 0);
                $final_grade = $data['final_grade'] ?? '';
                $target_grade = $data['target_grade'] ?? '';
                $attitude = intval($data['attitude'] ?? 0);
                $comments = trim($data['comments'] ?? '');
                
                // Calculate final mark if not provided
                if (!$final_mark && ($ass1 || $ass2 || $exam)) {
                    $final_mark = ($ass1 * 0.2) + ($ass2 * 0.2) + ($exam * 0.6);
                    $final_mark = round($final_mark, 2);
                }
                
                // Calculate grade if not provided
                if (!$final_grade && $final_mark) {
                    $final_grade = calculateGrade($final_mark);
                }
                
                // Save to advanced results table
                $checkQuery = "
                    SELECT result_id FROM results 
                    WHERE student_id = ? AND subject_id = ? AND class_id = ? AND term = ? AND year = ?
                ";
                $checkStmt = $conn->prepare($checkQuery);
                $checkStmt->bind_param("iiiis", $student_id, $subject_id, $class_id, $term, $year);
                $checkStmt->execute();
                $checkResult = $checkStmt->get_result();
                
                if ($checkResult->num_rows > 0) {
                    // Update existing record
                    $row = $checkResult->fetch_assoc();
                    $result_id = $row['result_id'];
                    
                    $updateQuery = "
                        UPDATE results 
                        SET final_mark = ?, final_grade = ?, target_grade = ?, attitude_to_learning = ?, comments = ?, updated_at = NOW()
                        WHERE result_id = ?
                    ";
                    $updateStmt = $conn->prepare($updateQuery);
                    $updateStmt->bind_param("dssisi", $final_mark, $final_grade, $target_grade, $attitude, $comments, $result_id);
                    $updateStmt->execute();
                    $updateStmt->close();
                    
                    // Update or insert assessments
if ($ass1) {
    updateOrInsertAssessment($conn, $result_id, 'Assessment 1', $ass1);
}
if ($ass2) {
    updateOrInsertAssessment($conn, $result_id, 'Assessment 2', $ass2);
}
if ($exam) {
    updateOrInsertAssessment($conn, $result_id, 'End of Term Exam', $exam);
}
                } else {
                    // Insert new record
                    $insertQuery = "
                        INSERT INTO results (student_id, subject_id, class_id, term, year, final_mark, final_grade, target_grade, attitude_to_learning, comments, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                    ";
                    $insertStmt = $conn->prepare($insertQuery);
                    $insertStmt->bind_param("iiiisdssss", $student_id, $subject_id, $class_id, $term, $year, $final_mark, $final_grade, $target_grade, $attitude, $comments);
                    $insertStmt->execute();
                    $result_id = $conn->insert_id;
                    $insertStmt->close();
                    
                   // Insert assessments
if ($ass1) {
    insertAssessment($conn, $result_id, 'Assessment 1', $ass1);
}
if ($ass2) {
    insertAssessment($conn, $result_id, 'Assessment 2', $ass2);
}
if ($exam) {
    insertAssessment($conn, $result_id, 'End of Term Exam', $exam);
}
                }
                $checkStmt->close();
                
            } else {
                // Simple marks system (compatibility with second code)
                $marks_obtained = floatval($data['marks_obtained'] ?? 0);
                $total_marks = floatval($data['total_marks'] ?? 100);
                $comments = trim($data['comments'] ?? '');
                
                // Skip if no marks entered
                if ($marks_obtained <= 0) continue;
                
                // Calculate percentage and grade
                $percentage = ($marks_obtained / $total_marks) * 100;
                $grade = calculateGradeSimple($percentage);
                
                // Check if mark already exists
                $checkQuery = "
                    SELECT result_id FROM results 
                    WHERE student_id = ? AND subject = ? AND term = ? AND academic_year = ? AND exam_type = ?
                ";
                $checkStmt = $conn->prepare($checkQuery);
                $checkStmt->bind_param("sssss", $student_id, $subject_name, $term, $year, $assessment_type);
                $checkStmt->execute();
                $checkResult = $checkStmt->get_result();
                
                if ($checkResult->num_rows > 0) {
                    // Update existing record
                    $row = $checkResult->fetch_assoc();
                    $result_id = $row['result_id'];
                    
                    $updateQuery = "
                        UPDATE results 
                        SET marks_obtained = ?, total_marks = ?, final_mark = ?, grade = ?, comments = ?, updated_at = NOW()
                        WHERE result_id = ?
                    ";
                    $updateStmt = $conn->prepare($updateQuery);
                    $updateStmt->bind_param("dddssi", $marks_obtained, $total_marks, $marks_obtained, $grade, $comments, $result_id);
                    $updateStmt->execute();
                    $updateStmt->close();
                } else {
                    // Insert new record
                    $insertQuery = "
                        INSERT INTO results (student_id, subject, exam_type, term, academic_year, marks_obtained, total_marks, final_mark, grade, exam_date, teacher_id, comments)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), ?, ?)
                    ";
                    $insertStmt = $conn->prepare($insertQuery);
                    $insertStmt->bind_param("sssssddssss", $student_id, $subject_name, $assessment_type, $term, $year, $marks_obtained, $total_marks, $marks_obtained, $grade, $teacher_id, $comments);
                    $insertStmt->execute();
                    $insertStmt->close();
                }
                $checkStmt->close();
            }
            
            $success_count++;
        }
        
        $conn->commit();
        $message = "Successfully saved marks for $success_count students in $subject_name - $class_name.";
        $message_type = "success";
        
    } catch (Exception $e) {
        $conn->rollback();
        $message = "Error saving marks: " . $e->getMessage();
        $message_type = "error";
    }
}

// Helper functions for assessment management
function updateOrInsertAssessment($conn, $result_id, $assessment_name, $mark) {
    $checkAssessment = "SELECT id FROM term_assessments WHERE result_id = ? AND assessment_name = ?";
    $checkStmt = $conn->prepare($checkAssessment);
    $checkStmt->bind_param("is", $result_id, $assessment_name);
    $checkStmt->execute();
    $assessmentResult = $checkStmt->get_result();
    
    if ($assessmentResult->num_rows > 0) {
        $assessmentRow = $assessmentResult->fetch_assoc();
        $updateAssessment = "UPDATE term_assessments SET mark = ? WHERE id = ?";
        $updateStmt = $conn->prepare($updateAssessment);
        $updateStmt->bind_param("di", $mark, $assessmentRow['id']);
        $updateStmt->execute();
        $updateStmt->close();
    } else {
        $insertAssessment = "INSERT INTO term_assessments (result_id, assessment_name, mark) VALUES (?, ?, ?)";
        $insertStmt = $conn->prepare($insertAssessment);
        $insertStmt->bind_param("isd", $result_id, $assessment_name, $mark);
        $insertStmt->execute();
        $insertStmt->close();
    }
    $checkStmt->close();
}

function insertAssessment($conn, $result_id, $assessment_name, $mark) {
    $insertAssessment = "INSERT INTO term_assessments (result_id, assessment_name, mark) VALUES (?, ?, ?)";
    $insertStmt = $conn->prepare($insertAssessment);
    $insertStmt->bind_param("isd", $result_id, $assessment_name, $mark);
    $insertStmt->execute();
    $insertStmt->close();
}

// Only fetch student marks if term and year are provided
if ($term && $year) {
    // Enhanced student fetching logic - check database structure first
    $students = [];
    $successful_query = '';
    
    try {
        // First, determine the database structure
        $students_table_info = $conn->query("DESCRIBE students");
        $has_class_id_column = false;
        $has_class_column = false;
        $available_columns = [];
        
        if ($students_table_info) {
            while ($column = $students_table_info->fetch_assoc()) {
                $col_name = $column['Field'];
                $available_columns[] = $col_name;
                if ($col_name === 'class_id') $has_class_id_column = true;
                if ($col_name === 'class') $has_class_column = true;
            }
        }
        
        $debug_info[] = "Available columns in students table: " . implode(', ', $available_columns);
        
        // Try different query strategies based on available columns and data
        if ($has_class_id_column && $class_id) {
            // Strategy 1: Using class_id column
            $students_query = "SELECT id as student_id, username, first_name, last_name FROM students WHERE class_id = ? ORDER BY username ASC";
            $stmt = $conn->prepare($students_query);
            
            if ($stmt) {
                $stmt->bind_param("i", $class_id);
                if ($stmt->execute()) {
                    $result = $stmt->get_result();
                    $students = $result->fetch_all(MYSQLI_ASSOC);
                    $stmt->close();
                    
                    if (!empty($students)) {
                        $successful_query = "Query using class_id column succeeded";
                        $debug_info[] = "Found " . count($students) . " students using class_id $class_id";
                    }
                }
            }
        }
        
        // Strategy 2: Using class column with class name
        if (empty($students) && $has_class_column && $class_name) {
            $students_query_alt = "SELECT student_id, username, first_name, last_name, class FROM students WHERE class = ? ORDER BY first_name, last_name";
            $stmt_alt = $conn->prepare($students_query_alt);
            
            if ($stmt_alt) {
                $stmt_alt->bind_param("s", $class_name);
                if ($stmt_alt->execute()) {
                    $result_alt = $stmt_alt->get_result();
                    $students = $result_alt->fetch_all(MYSQLI_ASSOC);
                    $stmt_alt->close();
                    
                    if (!empty($students)) {
                        $successful_query = "Query using class column with class name succeeded";
                        $debug_info[] = "Found " . count($students) . " students in class named '$class_name'";
                    }
                }
            }
        }
        
        // Strategy 3: Using class column with class_id as string
        if (empty($students) && $has_class_column && $class_id) {
            $students_query_alt2 = "SELECT student_id, username, first_name, last_name, class FROM students WHERE class = ? ORDER BY first_name, last_name";
            $stmt_alt2 = $conn->prepare($students_query_alt2);
            
            if ($stmt_alt2) {
                $class_id_string = (string)$class_id;
                $stmt_alt2->bind_param("s", $class_id_string);
                if ($stmt_alt2->execute()) {
                    $result_alt2 = $stmt_alt2->get_result();
                    $students = $result_alt2->fetch_all(MYSQLI_ASSOC);
                    $stmt_alt2->close();
                    
                    if (!empty($students)) {
                        $successful_query = "Query using class column with class_id as string succeeded";
                        $debug_info[] = "Found " . count($students) . " students in class '$class_id_string'";
                    }
                }
            }
        }
        
        // Strategy 4: Get all students if we still have none (fallback)
        if (empty($students)) {
            $students_query_fallback = "SELECT student_id, username, first_name, last_name FROM students ORDER BY first_name, last_name LIMIT 50";
            $stmt_fallback = $conn->prepare($students_query_fallback);
            
            if ($stmt_fallback) {
                if ($stmt_fallback->execute()) {
                    $result_fallback = $stmt_fallback->get_result();
                    $students = $result_fallback->fetch_all(MYSQLI_ASSOC);
                    $stmt_fallback->close();
                    
                    if (!empty($students)) {
                        $successful_query = "Fallback query (all students) succeeded";
                        $debug_info[] = "Using fallback: Found " . count($students) . " students (showing first 50)";
                    }
                }
            }
        }
        
        // Enhanced debug information
        if (isset($_GET['debug']) && $_GET['debug'] === '1') {
            // Check total students
            $total_check = $conn->query("SELECT COUNT(*) as total FROM students");
            if ($total_check) {
                $total_result = $total_check->fetch_assoc();
                $debug_info[] = "Total students in database: " . $total_result['total'];
            }
            
            // Check class distribution
            if ($has_class_id_column) {
                $class_check = $conn->query("SELECT class_id, COUNT(*) as count FROM students GROUP BY class_id ORDER BY class_id");
                if ($class_check) {
                    $debug_info[] = "Students by class_id:";
                    while ($row = $class_check->fetch_assoc()) {
                        $class_display = $row['class_id'] ?? 'NULL';
                        $debug_info[] = "  Class $class_display: " . $row['count'] . " students";
                    }
                }
            }
            
            if ($has_class_column) {
                $class_check2 = $conn->query("SELECT class, COUNT(*) as count FROM students GROUP BY class ORDER BY class");
                if ($class_check2) {
                    $debug_info[] = "Students by class name:";
                    while ($row = $class_check2->fetch_assoc()) {
                        $class_display = $row['class'] ?? 'NULL';
                        $debug_info[] = "  Class '$class_display': " . $row['count'] . " students";
                    }
                }
            }
        }
        
    } catch (Exception $e) {
        $debug_info[] = "Error in student query: " . $e->getMessage();
    }
    
    // If we have students, get their marks
    if (!empty($students)) {
        foreach ($students as $student) {
            $student_id = $student['student_id'];
            
            // First check if we have the advanced results table structure
            $has_advanced_results = false;
            try {
                $results_check = $conn->query("SHOW TABLES LIKE 'results'");
                if ($results_check && $results_check->num_rows > 0) {
                    $results_columns = $conn->query("DESCRIBE results");
                    $has_subject_id = false;
                    $has_class_id_col = false;
                    
                    while ($col = $results_columns->fetch_assoc()) {
                        if ($col['Field'] === 'subject_id') $has_subject_id = true;
                        if ($col['Field'] === 'class_id') $has_class_id_col = true;
                    }
                    
                    $has_advanced_results = ($has_subject_id && $has_class_id_col);
                }
            } catch (Exception $e) {
                $debug_info[] = "Error checking results table structure: " . $e->getMessage();
            }
            
            // Try advanced marks system first if we have the right structure
            if ($has_advanced_results && $class_id && $subject_id) {
                try {
                    $marks_query = "
                        SELECT
                            r.attitude_to_learning,
                            r.comments,
                            r.final_mark,
                            r.final_grade,
                            r.target_grade,
                            GROUP_CONCAT(
                                CASE 
                                    WHEN ta.assessment_name = 'Assessment 1' THEN ta.mark 
                                END
                            ) AS assessment_1_mark,
                            GROUP_CONCAT(
                                CASE 
                                    WHEN ta.assessment_name = 'Assessment 2' THEN ta.mark 
                                END
                            ) AS assessment_2_mark,
                            GROUP_CONCAT(
                                CASE 
                                    WHEN ta.assessment_name = 'End of Term Exam' THEN ta.mark 
                                END
                            ) AS exam_mark
                        FROM results r
                        LEFT JOIN term_assessments ta ON r.result_id = ta.result_id
                        WHERE r.student_id = ? AND r.subject_id = ? AND r.class_id = ? AND r.term = ? AND r.year = ?
                        GROUP BY r.result_id, r.student_id, r.attitude_to_learning, r.comments, r.final_mark, r.final_grade, r.target_grade
                    ";
                    
                    $stmt_marks = $conn->prepare($marks_query);
                    if ($stmt_marks) {
                        $stmt_marks->bind_param("iiiis", $student_id, $subject_id, $class_id, $term, $year);
                        if ($stmt_marks->execute()) {
                            $marks_result = $stmt_marks->get_result();
                            $marks_data = $marks_result->fetch_assoc();
                            
                            // Clean up the grouped results
                            if ($marks_data) {
                                $marks_data['assessment_1_mark'] = !empty($marks_data['assessment_1_mark']) ? $marks_data['assessment_1_mark'] : null;
                                $marks_data['assessment_2_mark'] = !empty($marks_data['assessment_2_mark']) ? $marks_data['assessment_2_mark'] : null;
                                $marks_data['exam_mark'] = !empty($marks_data['exam_mark']) ? $marks_data['exam_mark'] : null;
                            }
                            
                            // Merge student data with marks data
                            $student_with_marks = array_merge($student, $marks_data ?: []);
                            $students_with_marks[] = $student_with_marks;
                        } else {
                            $students_with_marks[] = $student;
                        }
                        $stmt_marks->close();
                    } else {
                        $students_with_marks[] = $student;
                    }
                } catch (Exception $e) {
                    $debug_info[] = "Error in advanced marks query: " . $e->getMessage();
                    $students_with_marks[] = $student;
                }
            } else {
                // Fallback to simple marks system
                try {
                    $marksQuery = "
                        SELECT marks_obtained, total_marks, final_mark, grade, comments
                        FROM results 
                        WHERE student_id = ? AND subject = ? AND term = ? AND academic_year = ? AND exam_type = ?
                    ";
                    
                    $marksStmt = $conn->prepare($marksQuery);
                    if ($marksStmt) {
                        $marksStmt->bind_param("sssss", $student_id, $subject_name, $term, $year, $assessment_type);
                        $marksStmt->execute();
                        $marksResult = $marksStmt->get_result();
                        $marksData = $marksResult->fetch_assoc();
                        
                        // Merge student data with marks data
                        $student_with_marks = array_merge($student, $marksData ?: []);
                        $students_with_marks[] = $student_with_marks;
                        
                        $marksStmt->close();
                    } else {
                        $students_with_marks[] = $student;
                    }
                } catch (Exception $e) {
                    $debug_info[] = "Error in simple marks query: " . $e->getMessage();
                    $students_with_marks[] = $student;
                }
            }
        }
    } else {
        $debug_info[] = "No students found for class: $class_name / class_id: $class_id";
    }
}

$pageTitle = "Enter & Edit Marks";
include 'header.php';
?>

<style>
    /* Enhanced CSS combining both systems */
    :root {
        --primary-color: #007bff;
        --success-color: #28a745;
        --warning-color: #ffc107;
        --danger-color: #dc3545;
        --background-color: #f4f7f6;
        --card-background: #ffffff;
        --text-color: #333;
        --border-color: #e0e0e0;
    }
    
    body {
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        background-color: var(--background-color);
        color: var(--text-color);
        line-height: 1.6;
    }
    
    .page-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 2rem;
        border-radius: 12px;
        margin-bottom: 2rem;
        text-align: center;
    }
    
    .page-header h1 {
        margin: 0 0 0.5rem 0;
        font-size: 1.8rem;
    }
    
    .page-header p {
        margin: 0;
        opacity: 0.9;
    }
    
    .context-info {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 1rem;
        margin-bottom: 2rem;
    }
    
    .info-card {
        background: white;
        padding: 1rem;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        text-align: center;
    }
    
    .info-label {
        font-size: 0.8rem;
        color: #6c757d;
        text-transform: uppercase;
        margin-bottom: 0.5rem;
    }
    
    .info-value {
        font-size: 1.1rem;
        font-weight: 600;
        color: var(--primary-color);
    }
    
    .card { 
        background-color: #fff; 
        border-radius: 12px; 
        box-shadow: 0 5px 15px rgba(0,0,0,0.05); 
    }
    .card-header { 
        padding: 1.5rem 2rem; 
        border-bottom: 1px solid #e0e0e0; 
        background: #f8f9fa;
    }
    .card-title { 
        font-size: 1.5rem; 
        font-weight: 600; 
        margin: 0; 
    }
    .card-subtitle { 
        font-size: 1rem; 
        color: #555; 
        margin-top: 0.25rem; 
    }
    .card-body { 
        padding: 2rem; 
    }
    
    table { 
        width: 100%; 
        border-collapse: collapse; 
    }
    th, td { 
        padding: 0.8rem 1rem; 
        text-align: left; 
        border-bottom: 1px solid #e0e0e0; 
    }
    th { 
        font-weight: 600; 
        background-color: #f8f9fa; 
        color: #495057;
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    td input, td textarea, td select { 
        width: 100%; 
        padding: 0.5rem; 
        border: 1px solid #ccc; 
        border-radius: 6px; 
        box-sizing: border-box;
    }
    
    .marks-table tbody tr:hover {
        background-color: #f8faff;
    }
    
    .student-info {
        font-weight: 600;
        color: var(--text-color);
    }
    
    .student-id {
        font-size: 0.8rem;
        color: #6c757d;
        margin-top: 0.2rem;
    }
    
    .mark-input {
        width: 80px;
        padding: 0.5rem;
        border: 1px solid #ddd;
        border-radius: 4px;
        text-align: center;
        font-size: 0.9rem;
    }
    
    .mark-input:focus {
        outline: none;
        border-color: var(--primary-color);
        box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.2);
    }
    
    .comments-input {
        width: 100%;
        padding: 0.5rem;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 0.9rem;
        resize: vertical;
        min-height: 60px;
    }
    
    .submit-btn, .load-btn { 
        display: block; 
        width: 250px; 
        margin: 2rem auto 0; 
        background-color: #007bff; 
        color: white; 
        padding: 1rem; 
        border: none; 
        border-radius: 8px; 
        cursor: pointer; 
        font-size: 1.1rem; 
        font-weight: 600; 
        text-align: center; 
    }
    .submit-btn { 
        background-color: #28a745; 
    }
    .submit-btn:hover, .load-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    }
    
    .term-year-form { 
        display: flex; 
        flex-direction: column; 
        gap: 1.5rem; 
        max-width: 500px; 
        margin: 0 auto; 
        padding: 2rem;
        background: white;
        border-radius: 12px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    }
    .term-year-form .form-group { 
        flex: 1; 
    }
    .term-year-form label { 
        font-weight: 600; 
        margin-bottom: 0.5rem; 
        display: block; 
    }
    .term-year-form input, .term-year-form select { 
        width: 100%; 
        padding: 0.8rem; 
        border-radius: 6px; 
        border: 1px solid #ccc; 
    }

    /* --- READ-ONLY FIELDS --- */
    input[readonly], textarea[readonly], select[disabled] {
        background-color: #f3f4f6;
        cursor: not-allowed;
        color: #6b7280;
    }

    /* AI Comment Button */
    .btn-ai-comment {
        background-color: #6c757d;
        color: white;
        padding: 0.5rem 0.8rem;
        border-radius: 6px;
        cursor: pointer;
        font-size: 0.9rem;
        border: none;
        transition: background-color 0.2s ease;
        white-space: nowrap;
        margin-top: 5px;
    }
    .btn-ai-comment:hover {
        background-color: #5a6268;
    }

    /* Grade display styles */
    .grade-display {
        background-color: #e9ecef;
        padding: 0.5rem;
        border-radius: 6px;
        text-align: center;
        font-weight: 600;
        color: #495057;
        border: 1px solid #dee2e6;
        min-width: 50px;
        display: inline-block;
    }
    
    .grade-a { background: #d1e7dd; color: #0f5132; }
    .grade-b { background: #d1ecf1; color: #0c5460; }
    .grade-c { background: #fff3cd; color: #664d03; }
    .grade-d { background: #f8d7da; color: #842029; }
    .grade-e { background: #f8d7da; color: #842029; }
    .grade-f { background: #f5c6cb; color: #721c24; }

    /* Target grade select styling */
    .target-grade-select {
        background-color: #fff;
        font-weight: 600;
    }

    /* Grading system info */
    .grading-info {
        background-color: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 1rem;
        font-size: 0.9rem;
    }
    .grading-info h4 {
        margin: 0 0 0.5rem 0;
        color: #495057;
    }
    .grading-info .grade-range {
        display: inline-block;
        margin: 0.2rem 0.5rem;
        padding: 0.2rem 0.4rem;
        background-color: #e9ecef;
        border-radius: 4px;
    }

    /* Debug info styles */
    .debug-info {
        background-color: #f1f5f9;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 1rem;
        font-family: monospace;
        font-size: 0.8rem;
    }
    .debug-info h4 {
        margin: 0 0 0.5rem 0;
        color: #374151;
    }
    
    .message {
        padding: 1rem 1.5rem;
        margin-bottom: 1.5rem;
        border-radius: 8px;
        font-weight: 500;
    }
    
    .message.success {
        background: #d1e7dd;
        color: #0f5132;
        border-left: 4px solid var(--success-color);
    }
    
    .message.error {
        background: #f8d7da;
        color: #842029;
        border-left: 4px solid var(--danger-color);
    }
    
    .empty-state {
        text-align: center;
        padding: 3rem;
        color: #6c757d;
    }
    
    .empty-state i {
        font-size: 3rem;
        margin-bottom: 1rem;
        opacity: 0.5;
    }
    
    .form-actions {
        display: flex;
        gap: 1rem;
        justify-content: center;
        align-items: center;
        margin-top: 2rem;
        padding-top: 2rem;
        border-top: 1px solid #eee;
    }
    
    .btn {
        padding: 0.75rem 1.5rem;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        text-decoration: none;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.2s ease;
        font-size: 1rem;
    }
    
    .btn-primary {
        background: var(--primary-color);
        color: white;
    }
    
    .btn-primary:hover {
        background: #0056b3;
        transform: translateY(-1px);
    }
    
    .btn-secondary {
        background: #6c757d;
        color: white;
    }
    
    .btn-secondary:hover {
        background: #545b62;
    }
    
    .form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin-bottom: 1.5rem;
    }
    
    .form-group {
        display: flex;
        flex-direction: column;
    }
    
    .form-group input:focus,
    .form-group select:focus {
        outline: none;
        border-color: var(--primary-color);
        box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.2);
    }
    
    @media (max-width: 768px) {
        .context-info {
            grid-template-columns: 1fr;
        }
        
        table {
            font-size: 0.8rem;
        }
        
        th, td {
            padding: 0.7rem 0.5rem;
        }
        
        .form-actions {
            flex-direction: column;
        }
        
        .form-grid {
            grid-template-columns: 1fr;
        }
        
        .mark-input {
            width: 100%;
        }
        
        .page-header h1 {
            font-size: 1.4rem;
        }
    }
</style>

<div class="page-header">
    <h1><i class="fas fa-edit"></i> Enter & Edit Marks</h1>
    <p>Teacher: <?= htmlspecialchars($teacherName) ?> | Role: <?= htmlspecialchars(ucfirst($user_role)) ?></p>
</div>

<?php if (!empty($message)): ?>
    <div class="message <?= htmlspecialchars($message_type) ?>">
        <i class="fas <?= $message_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle' ?>"></i>
        <?= htmlspecialchars($message) ?>
    </div>
<?php endif; ?>

<!-- Debug information (add ?debug=1 to URL to see) -->
<?php if (isset($_GET['debug']) && $_GET['debug'] === '1'): ?>
<div class="debug-info">
    <h4>Debug Information:</h4>
    <p><strong>Class ID:</strong> <?= htmlspecialchars($class_id) ?></p>
    <p><strong>Subject ID:</strong> <?= htmlspecialchars($subject_id) ?></p>
    <p><strong>Class Name:</strong> <?= htmlspecialchars($class_name) ?></p>
    <p><strong>Subject Name:</strong> <?= htmlspecialchars($subject_name) ?></p>
    <p><strong>Term:</strong> <?= htmlspecialchars($term) ?></p>
    <p><strong>Year:</strong> <?= htmlspecialchars($year) ?></p>
    <p><strong>Successful Query:</strong> <?= htmlspecialchars($successful_query) ?></p>
    <p><strong>Students Found:</strong> <?= count($students_with_marks) ?></p>
    <?php foreach ($debug_info as $info): ?>
        <p><?= htmlspecialchars($info) ?></p>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if (!$term || !$year): ?>
    <div class="term-year-form">
        <h3 style="text-align: center; margin-bottom: 1.5rem;">Complete Mark Entry Setup</h3>
        <p style="text-align:center;">Select the term and year to enter or edit marks for.</p>
        <form method="POST">
            <input type="hidden" name="class_id" value="<?= htmlspecialchars($class_id) ?>">
            <input type="hidden" name="subject_id" value="<?= htmlspecialchars($subject_id) ?>">
            <input type="hidden" name="class_name" value="<?= htmlspecialchars($class_name) ?>">
            <input type="hidden" name="subject_name" value="<?= htmlspecialchars($subject_name) ?>">
            <input type="hidden" name="assessment_type" value="<?= htmlspecialchars($assessment_type) ?>">
            
            <div class="form-grid">
                <div class="form-group">
                    <label for="term">Term</label>
                    <select name="term" id="term" required>
                        <option value="">Select Term</option>
                        <option value="Term 1" <?= $term === 'Term 1' ? 'selected' : '' ?>>Term 1</option>
                        <option value="Term 2" <?= $term === 'Term 2' ? 'selected' : '' ?>>Term 2</option>
                        <option value="Term 3" <?= $term === 'Term 3' ? 'selected' : '' ?>>Term 3</option>
                        <option value="1" <?= $term === '1' ? 'selected' : '' ?>>1</option>
                        <option value="2" <?= $term === '2' ? 'selected' : '' ?>>2</option>
                        <option value="3" <?= $term === '3' ? 'selected' : '' ?>>3</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="year">Academic Year</label>
                    <input type="number" name="year" id="year" value="<?= htmlspecialchars($year ?: date('Y')) ?>" min="2020" max="2030" required>
                </div>
            </div>
            
            <button type="submit" class="load-btn">
                <i class="fas fa-list-ol"></i> Load Student Mark Sheet
            </button>
        </form>
    </div>
<?php endif; ?>

<?php if ($term && $year): ?>
    <div class="context-info">
        <div class="info-card">
            <div class="info-label">Class</div>
            <div class="info-value"><?= htmlspecialchars($class_name) ?></div>
        </div>
        <div class="info-card">
            <div class="info-label">Subject</div>
            <div class="info-value"><?= htmlspecialchars($subject_name) ?></div>
        </div>
        <div class="info-card">
            <div class="info-label">Term & Year</div>
            <div class="info-value"><?= htmlspecialchars($term . ' ' . $year) ?></div>
        </div>
        <div class="info-card">
            <div class="info-label">Assessment Type</div>
            <div class="info-value"><?= htmlspecialchars($assessment_type) ?></div>
        </div>
        <div class="info-card">
            <div class="info-label">Students</div>
            <div class="info-value"><?= count($students_with_marks) ?></div>
        </div>
    </div>

    <!-- Enhanced Grading System Information -->
    <div class="grading-info">
        <h4><i class="fas fa-info-circle"></i> Enhanced Grading System Reference:</h4>
        <span class="grade-range">100%: A***</span>
        <span class="grade-range">95-99%: A**</span>
        <span class="grade-range">90-94%: A*</span>
        <span class="grade-range">88-89%: Aa</span>
        <span class="grade-range">85-87%: Ab</span>
        <span class="grade-range">80-84%: Ac</span>
        <span class="grade-range">78-79%: Ba</span>
        <span class="grade-range">75-77%: Bb</span>
        <span class="grade-range">70-74%: Bc</span>
        <span class="grade-range">68-69%: Ca</span>
        <span class="grade-range">65-67%: Cb</span>
        <span class="grade-range">60-64%: Cc</span>
        <span class="grade-range">58-59%: Da</span>
        <span class="grade-range">55-57%: Db</span>
        <span class="grade-range">50-54%: Dc</span>
        <span class="grade-range">48-49%: Ea</span>
        <span class="grade-range">45-47%: Eb</span>
        <span class="grade-range">40-44%: Ec</span>
        <span class="grade-range">30-39%: F</span>
        <span class="grade-range">&lt;30%: G</span>
        <br><strong>Zimbabwe System:</strong>
        <span class="grade-range">A: 80-100%</span>
        <span class="grade-range">B: 70-79%</span>
        <span class="grade-range">C: 60-69%</span>
        <span class="grade-range">D: 50-59%</span>
        <span class="grade-range">E: 40-49%</span>
        <span class="grade-range">F: Below 40%</span>
    </div>

    <?php if (empty($students_with_marks)): ?>
        <div class="empty-state">
            <i class="fas fa-users-slash"></i>
            <h3>No Students Found</h3>
            <p>No students found in <?= htmlspecialchars($class_name) ?>.</p>
            <p style="margin-top: 1rem;">
                <a href="?class_id=<?= htmlspecialchars($class_id) ?>&subject_id=<?= htmlspecialchars($subject_id) ?>&class_name=<?= htmlspecialchars($class_name) ?>&subject_name=<?= htmlspecialchars($subject_name) ?>&term=<?= htmlspecialchars($term) ?>&year=<?= htmlspecialchars($year) ?>&debug=1" 
                   style="color: #856404; text-decoration: underline;">
                    Click here to see debug information
                </a>
            </p>
            <a href="select_class.php" class="btn btn-primary" style="margin-top: 1rem;">
                <i class="fas fa-arrow-left"></i> Select Different Class
            </a>
        </div>
    <?php else: ?>
        <div class="card">
            <div class="card-header">
                <h2 class="card-title"><?= htmlspecialchars($subject_name) ?> - <?= htmlspecialchars($class_name) ?></h2>
                <p class="card-subtitle">Mark Sheet for Term: <?= htmlspecialchars($term) ?>, Year: <?= htmlspecialchars($year) ?></p>
            </div>
            <div class="card-body">
                
                <form method="POST">
                    <input type="hidden" name="class_id" value="<?= htmlspecialchars($class_id) ?>">
                    <input type="hidden" name="subject_id" value="<?= htmlspecialchars($subject_id) ?>">
                    <input type="hidden" name="class_name" value="<?= htmlspecialchars($class_name) ?>">
                    <input type="hidden" name="subject_name" value="<?= htmlspecialchars($subject_name) ?>">
                    <input type="hidden" name="term" value="<?= htmlspecialchars($term) ?>">
                    <input type="hidden" name="year" value="<?= htmlspecialchars($year) ?>">
                    <input type="hidden" name="assessment_type" value="<?= htmlspecialchars($assessment_type) ?>">

                    <div style="overflow-x:auto;">
                        <?php 
                        // Determine if we should use advanced or simple interface
                        $use_advanced = false;
                        foreach ($students_with_marks as $student) {
                            if (isset($student['assessment_1_mark']) || isset($student['assessment_2_mark']) || isset($student['exam_mark']) || isset($student['final_grade'])) {
                                $use_advanced = true;
                                break;
                            }
                        }
                        ?>
                        
                        <?php if ($use_advanced || ($class_id && $subject_id)): ?>
                            <!-- Advanced Assessment System -->
                            <table class="marks-table">
                                <thead>
                                    <tr>
                                        <th>Student Name</th>
                                        <th>Assessment 1 (%)</th>
                                        <th>Assessment 2 (%)</th>
                                        <th>End of Term Exam (%)</th>
                                        <th>Final Mark (%)</th>
                                        <th>Current Grade</th>
                                        <th>Exam Grade</th>
                                        <th>Target Grade</th>
                                        <th>Attitude (1-5)</th>
                                        <th>Comments <br> <small>(Click button to generate)</small></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($students_with_marks as $student): ?>
                                        <?php
                                        // Logic to set read-only attribute
                                        if ($user_role !== 'admin') {
                                            $readonly_ass1 = isset($student['assessment_1_mark']) ? 'readonly' : '';
                                            $readonly_ass2 = isset($student['assessment_2_mark']) ? 'readonly' : '';
                                            $readonly_exam = isset($student['exam_mark']) ? 'readonly' : '';
                                            $readonly_att = isset($student['attitude_to_learning']) ? 'readonly' : '';
                                            $readonly_com = isset($student['comments']) && $student['comments'] !== '' ? 'readonly' : '';
                                            $readonly_target = isset($student['target_grade']) && $student['target_grade'] !== '' ? 'disabled' : '';
                                        } else {
                                            $readonly_ass1 = $readonly_ass2 = $readonly_exam = $readonly_att = $readonly_com = $readonly_target = '';
                                        }

                                        // Calculate final mark and grades
                                        $final_mark_calculated = '';
                                        $current_grade_calculated = '';
                                        $exam_grade_calculated = '';
                                        
                                        // Convert final_mark to proper number
                                        if (isset($student['final_mark']) && $student['final_mark'] !== null) {
                                            $final_mark_raw = $student['final_mark'];
                                            if (is_string($final_mark_raw)) {
                                                $final_mark_raw = preg_replace('/[^0-9.]/', '', $final_mark_raw);
                                            }
                                            $final_mark_calculated = floatval($final_mark_raw);
                                            if ($final_mark_calculated == 0) {
                                                $final_mark_calculated = '';
                                            }
                                        }
                                        
                                        // If no existing final mark, calculate from assessments
                                        if (empty($final_mark_calculated)) {
                                            $ass1 = floatval($student['assessment_1_mark'] ?? 0);
                                            $ass2 = floatval($student['assessment_2_mark'] ?? 0);
                                            $exam = floatval($student['exam_mark'] ?? 0);
                                            if ($ass1 > 0 || $ass2 > 0 || $exam > 0) {
                                                $final_mark_calculated = ($ass1 * 0.2) + ($ass2 * 0.2) + ($exam * 0.6);
                                                $final_mark_calculated = round($final_mark_calculated, 2);
                                            }
                                        }
                                        
                                        // Calculate current grade
                                        if ($final_mark_calculated > 0) {
                                            if (isset($student['final_grade']) && $student['final_grade'] !== null && $student['final_grade'] !== '') {
                                                $current_grade_calculated = $student['final_grade'];
                                            } else {
                                                $current_grade_calculated = calculateGrade($final_mark_calculated);
                                            }
                                        }
                                        
                                        // Calculate exam grade
                                        $exam_mark = floatval($student['exam_mark'] ?? 0);
                                        if ($exam_mark > 0) {
                                            $exam_grade_calculated = calculateGrade($exam_mark);
                                        }
                                        
                                        $target_grade_value = $student['target_grade'] ?? '';
                                        $available_grades = ['A***', 'A**', 'A*', 'Aa', 'Ab', 'Ac', 'Ba', 'Bb', 'Bc', 'Ca', 'Cb', 'Cc', 'Da', 'Db', 'Dc', 'Ea', 'Eb', 'Ec', 'F', 'G'];
                                        
                                        // Display student name
                                        $display_name = '';
                                        
                                        if (!empty($student['username']) && 
                                            $student['username'] !== 'username' && 
                                            !preg_match('/^(first|last)[\s_-]?name$/i', $student['username'])) {
                                            $display_name = trim($student['username']);
                                        } elseif (!empty($student['first_name']) && !empty($student['last_name']) && 
                                                 $student['first_name'] !== 'First Name' && $student['last_name'] !== 'Last Name') {
                                            $display_name = trim($student['first_name'] . ' ' . $student['last_name']);
                                        } else {
                                            $display_name = "Student ID: " . $student['student_id'];
                                        }
                                        ?>
                                        <tr id="student-row-<?= htmlspecialchars($student['student_id']) ?>">
                                            <td>
                                                <div class="student-info"><?= htmlspecialchars($display_name) ?></div>
                                                <div class="student-id"><?= htmlspecialchars($student['student_id']) ?></div>
                                            </td>
                                            <td><input type="number" name="marks[<?= $student['student_id'] ?>][ass1]" value="<?= htmlspecialchars($student['assessment_1_mark'] ?? '') ?>" step="0.5" placeholder="Enter mark" <?= $readonly_ass1 ?> oninput="updateFinalMark(<?= $student['student_id'] ?>)" onchange="updateFinalMark(<?= $student['student_id'] ?>)"></td>
                                            <td><input type="number" name="marks[<?= $student['student_id'] ?>][ass2]" value="<?= htmlspecialchars($student['assessment_2_mark'] ?? '') ?>" step="0.5" placeholder="Enter mark" <?= $readonly_ass2 ?> oninput="updateFinalMark(<?= $student['student_id'] ?>)" onchange="updateFinalMark(<?= $student['student_id'] ?>)"></td>
                                            <td><input type="number" name="marks[<?= $student['student_id'] ?>][exam]" value="<?= htmlspecialchars($student['exam_mark'] ?? '') ?>" step="0.5" placeholder="Enter mark" <?= $readonly_exam ?> oninput="updateFinalMark(<?= $student['student_id'] ?>)" onchange="updateFinalMark(<?= $student['student_id'] ?>)"></td>
                                            <td>
                                                <div class="grade-display" id="final-mark-<?= $student['student_id'] ?>">
                                                    <?= $final_mark_calculated ? htmlspecialchars($final_mark_calculated) . '%' : '-' ?>
                                                </div>
                                                <input type="hidden" name="marks[<?= $student['student_id'] ?>][final_mark]" id="final-mark-input-<?= $student['student_id'] ?>" value="<?= htmlspecialchars($final_mark_calculated) ?>">
                                            </td>
                                            <td>
                                                <div class="grade-display" id="current-grade-<?= $student['student_id'] ?>">
                                                    <?= $current_grade_calculated ? htmlspecialchars($current_grade_calculated) : '-' ?>
                                                </div>
                                                <input type="hidden" name="marks[<?= $student['student_id'] ?>][final_grade]" id="current-grade-input-<?= $student['student_id'] ?>" value="<?= htmlspecialchars($current_grade_calculated) ?>">
                                            </td>
                                            <td>
                                                <div class="grade-display" id="exam-grade-<?= $student['student_id'] ?>">
                                                    <?= $exam_grade_calculated ? htmlspecialchars($exam_grade_calculated) : '-' ?>
                                                </div>
                                                <input type="hidden" name="marks[<?= $student['student_id'] ?>][exam_grade]" id="exam-grade-input-<?= $student['student_id'] ?>" value="<?= htmlspecialchars($exam_grade_calculated) ?>">
                                            </td>
                                            <td>
                                                <select name="marks[<?= $student['student_id'] ?>][target_grade]" class="target-grade-select" <?= $readonly_target ?>>
                                                    <option value="">Select Target Grade</option>
                                                    <?php foreach ($available_grades as $grade): ?>
                                                        <option value="<?= htmlspecialchars($grade) ?>" <?= $target_grade_value === $grade ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($grade) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </td>
                                            <td><input type="number" name="marks[<?= $student['student_id'] ?>][attitude]" value="<?= htmlspecialchars($student['attitude_to_learning'] ?? '') ?>" min="1" max="5" <?= $readonly_att ?>></td>
                                            <td>
                                                <textarea name="marks[<?= $student['student_id'] ?>][comments]" rows="3" class="comments-input comment-textarea" id="comment-<?= htmlspecialchars($student['student_id']) ?>" <?= $readonly_com ?>><?= htmlspecialchars($student['comments'] ?? '') ?></textarea>
                                                <?php if ($user_role == 'admin' || empty($readonly_com)): ?>
                                                    <button type="button" class="btn-ai-comment"
                                                            onclick="generateCommentInline(
                                                                '<?= htmlspecialchars($student['student_id']) ?>',
                                                                '<?= htmlspecialchars($display_name) ?>',
                                                                '<?= htmlspecialchars($subject_id) ?>',
                                                                document.getElementById('final-mark-input-<?= htmlspecialchars($student['student_id']) ?>').value,
                                                                document.getElementById('current-grade-input-<?= htmlspecialchars($student['student_id']) ?>').value,
                                                                document.querySelector('#student-row-<?= htmlspecialchars($student['student_id']) ?> select[name*=\'[target_grade]\']').value,
                                                                document.querySelector('#student-row-<?= htmlspecialchars($student['student_id']) ?> input[name*=\'[attitude]\']').value
                                                            )">
                                                        <i class="fas fa-robot"></i> AI Comment
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            
                        <?php else: ?>
                            <!-- Simple Marks System -->
                            <table class="marks-table">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Marks Obtained</th>
                                        <th>Total Marks</th>
                                        <th>Percentage</th>
                                        <th>Grade</th>
                                        <th>Comments</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($students_with_marks as $student): ?>
                                        <?php
                                            $marks_obtained = $student['marks_obtained'] ?? '';
                                            $total_marks = $student['total_marks'] ?? 100;
                                            $grade = $student['grade'] ?? '';
                                            $comments = $student['comments'] ?? '';
                                            
                                            // Calculate percentage for display
                                            $percentage = ($marks_obtained && $total_marks) ? round(($marks_obtained / $total_marks) * 100, 1) : '';
                                            
                                            // Determine student name
                                            $display_name = trim(($student['first_name'] ?? '') . ' ' . ($student['last_name'] ?? ''));
                                            if (empty($display_name) || $display_name === ' ') {
                                                $display_name = $student['username'] ?? $student['student_id'];
                                            }
                                        ?>
                                        <tr id="student-row-<?= htmlspecialchars($student['student_id']) ?>">
                                            <td>
                                                <div class="student-info"><?= htmlspecialchars($display_name) ?></div>
                                                <div class="student-id"><?= htmlspecialchars($student['student_id']) ?></div>
                                            </td>
                                            <td>
                                                <input type="number" 
                                                       name="marks[<?= htmlspecialchars($student['student_id']) ?>][marks_obtained]" 
                                                       class="mark-input" 
                                                       value="<?= htmlspecialchars($marks_obtained) ?>"
                                                       min="0" 
                                                       max="<?= htmlspecialchars($total_marks) ?>" 
                                                       step="0.5"
                                                       placeholder="0"
                                                       onchange="updateGradeSimple('<?= htmlspecialchars($student['student_id']) ?>')">
                                            </td>
                                            <td>
                                                <input type="number" 
                                                       name="marks[<?= htmlspecialchars($student['student_id']) ?>][total_marks]" 
                                                       class="mark-input" 
                                                       value="<?= htmlspecialchars($total_marks) ?>"
                                                       min="1" 
                                                       max="200" 
                                                       step="1"
                                                       onchange="updateGradeSimple('<?= htmlspecialchars($student['student_id']) ?>')">
                                            </td>
                                            <td>
                                                <span class="percentage-display" id="percentage-<?= htmlspecialchars($student['student_id']) ?>">
                                                    <?= $percentage ? $percentage . '%' : '-' ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="grade-display grade-<?= strtolower($grade) ?>" id="grade-<?= htmlspecialchars($student['student_id']) ?>">
                                                    <?= htmlspecialchars($grade ?: '-') ?>
                                                </span>
                                            </td>
                                            <td>
                                                <textarea name="marks[<?= htmlspecialchars($student['student_id']) ?>][comments]" 
                                                          class="comments-input" 
                                                          placeholder="Optional comments..."><?= htmlspecialchars($comments) ?></textarea>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-actions">
                        <a href="select_class.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Selection
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save All Marks
                        </button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>

<script>
    // Enhanced JavaScript combining both systems
    
    // Function to calculate grade based on advanced grading system
    function calculateGradeJS(mark) {
        if (mark >= 100) return 'A***';
        if (mark >= 95) return 'A**';
        if (mark >= 90) return 'A*';
        if (mark >= 88) return 'Aa';
        if (mark >= 85) return 'Ab';
        if (mark >= 80) return 'Ac';
        if (mark >= 78) return 'Ba';
        if (mark >= 75) return 'Bb';
        if (mark >= 70) return 'Bc';
        if (mark >= 68) return 'Ca';
        if (mark >= 65) return 'Cb';
        if (mark >= 60) return 'Cc';
        if (mark >= 58) return 'Da';
        if (mark >= 55) return 'Db';
        if (mark >= 50) return 'Dc';
        if (mark >= 48) return 'Ea';
        if (mark >= 45) return 'Eb';
        if (mark >= 40) return 'Ec';
        if (mark >= 30) return 'F';
        return 'G';
    }

    // Function to calculate simple Zimbabwe grade
    function calculateGradeSimple(percentage) {
        if (percentage >= 80) return { grade: 'A', class: 'grade-a' };
        if (percentage >= 70) return { grade: 'B', class: 'grade-b' };
        if (percentage >= 60) return { grade: 'C', class: 'grade-c' };
        if (percentage >= 50) return { grade: 'D', class: 'grade-d' };
        if (percentage >= 40) return { grade: 'E', class: 'grade-e' };
        return { grade: 'F', class: 'grade-f' };
    }

    // Function to update final mark and grades when assessment marks change (Advanced system)
    function updateFinalMark(studentId) {
        console.log('Updating final mark for student:', studentId); // Debug log
        
        const ass1Input = document.querySelector(`#student-row-${studentId} input[name*='[ass1]']`);
        const ass2Input = document.querySelector(`#student-row-${studentId} input[name*='[ass2]']`);
        const examInput = document.querySelector(`#student-row-${studentId} input[name*='[exam]']`);
        
        if (!ass1Input || !ass2Input || !examInput) {
            console.log('Could not find assessment inputs for student:', studentId);
            return;
        }
        
        const ass1 = parseFloat(ass1Input.value) || 0;
        const ass2 = parseFloat(ass2Input.value) || 0;
        const exam = parseFloat(examInput.value) || 0;
        
        console.log('Assessment marks:', { ass1, ass2, exam }); // Debug log
        
        // Calculate final mark (20% A1, 20% A2, 60% Exam)
        let finalMark = 0;
        if (ass1 > 0 || ass2 > 0 || exam > 0) {
            finalMark = (ass1 * 0.2) + (ass2 * 0.2) + (exam * 0.6);
            finalMark = Math.round(finalMark * 100) / 100; // Round to 2 decimal places
        }
        
        // Calculate current grade (based on final mark)
        const currentGrade = finalMark > 0 ? calculateGradeJS(finalMark) : '';
        
        // Calculate exam grade (based on exam mark only)
        const examGrade = exam > 0 ? calculateGradeJS(exam) : '';
        
        console.log('Calculated values:', { finalMark, currentGrade, examGrade }); // Debug log
        
        // Update displays
        const finalMarkDisplay = document.getElementById(`final-mark-${studentId}`);
        const currentGradeDisplay = document.getElementById(`current-grade-${studentId}`);
        const examGradeDisplay = document.getElementById(`exam-grade-${studentId}`);
        const finalMarkInput = document.getElementById(`final-mark-input-${studentId}`);
        const currentGradeInput = document.getElementById(`current-grade-input-${studentId}`);
        const examGradeInput = document.getElementById(`exam-grade-input-${studentId}`);
        
        if (finalMarkDisplay) {
            finalMarkDisplay.textContent = finalMark > 0 ? finalMark + '%' : '-';
            console.log('Updated final mark display');
        }
        if (currentGradeDisplay) {
            currentGradeDisplay.textContent = currentGrade || '-';
            console.log('Updated current grade display');
        }
        if (examGradeDisplay) {
            examGradeDisplay.textContent = examGrade || '-';
            console.log('Updated exam grade display');
        }
        if (finalMarkInput) finalMarkInput.value = finalMark;
        if (currentGradeInput) currentGradeInput.value = currentGrade;
        if (examGradeInput) examGradeInput.value = examGrade;
    }

    // Function to update grade for simple marks system
    function updateGradeSimple(studentId) {
        console.log('Updating simple grade for student:', studentId); // Debug log
        
        const marksInput = document.querySelector(`#student-row-${studentId} input[name*="[marks_obtained]"]`);
        const totalInput = document.querySelector(`#student-row-${studentId} input[name*="[total_marks]"]`);
        const percentageDisplay = document.getElementById(`percentage-${studentId}`);
        const gradeDisplay = document.getElementById(`grade-${studentId}`);
        
        if (!marksInput || !totalInput) {
            console.log('Could not find marks inputs for student:', studentId);
            return;
        }
        
        const marks = parseFloat(marksInput.value) || 0;
        const total = parseFloat(totalInput.value) || 100;
        
        console.log('Marks obtained:', marks, 'Total marks:', total); // Debug log
        
        if (marks > 0 && total > 0) {
            const percentage = Math.round((marks / total) * 100 * 10) / 10;
            console.log('Calculated percentage:', percentage); // Debug log
            
            if (percentageDisplay) {
                percentageDisplay.textContent = percentage + '%';
                console.log('Updated percentage display');
            }
            
            const gradeInfo = calculateGradeSimple(percentage);
            console.log('Calculated grade info:', gradeInfo); // Debug log
            
            if (gradeDisplay) {
                gradeDisplay.textContent = gradeInfo.grade;
                gradeDisplay.className = 'grade-display ' + gradeInfo.class;
                console.log('Updated grade display');
            }
        } else {
            if (percentageDisplay) percentageDisplay.textContent = '-';
            if (gradeDisplay) {
                gradeDisplay.textContent = '-';
                gradeDisplay.className = 'grade-display';
            }
        }
    }

    // Initialize calculations for existing data and set up event listeners
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM loaded, initializing calculations...'); // Debug log
        
        // Find all student rows
        const studentRows = document.querySelectorAll('[id^="student-row-"]');
        console.log('Found', studentRows.length, 'student rows'); // Debug log
        
        studentRows.forEach(row => {
            const studentId = row.id.replace('student-row-', '');
            console.log('Processing student:', studentId); // Debug log
            
            // Check if this row has advanced system elements
            const ass1Input = row.querySelector('input[name*="[ass1]"]');
            const ass2Input = row.querySelector('input[name*="[ass2]"]');
            const examInput = row.querySelector('input[name*="[exam]"]');
            
            if (ass1Input || ass2Input || examInput) {
                console.log('Setting up advanced system for student:', studentId);
                
                // Add event listeners for advanced system
                if (ass1Input) {
                    ass1Input.addEventListener('input', function() {
                        console.log('Assessment 1 changed for student:', studentId);
                        updateFinalMark(studentId);
                    });
                    ass1Input.addEventListener('change', function() {
                        updateFinalMark(studentId);
                    });
                }
                
                if (ass2Input) {
                    ass2Input.addEventListener('input', function() {
                        console.log('Assessment 2 changed for student:', studentId);
                        updateFinalMark(studentId);
                    });
                    ass2Input.addEventListener('change', function() {
                        updateFinalMark(studentId);
                    });
                }
                
                if (examInput) {
                    examInput.addEventListener('input', function() {
                        console.log('Exam mark changed for student:', studentId);
                        updateFinalMark(studentId);
                    });
                    examInput.addEventListener('change', function() {
                        updateFinalMark(studentId);
                    });
                }
                
                // Initialize calculation
                updateFinalMark(studentId);
            } else {
                // Check if this row has simple system elements
                const marksInput = row.querySelector('input[name*="[marks_obtained]"]');
                const totalInput = row.querySelector('input[name*="[total_marks]"]');
                
                if (marksInput || totalInput) {
                    console.log('Setting up simple system for student:', studentId);
                    
                    // Add event listeners for simple system
                    if (marksInput) {
                        marksInput.addEventListener('input', function() {
                            console.log('Marks obtained changed for student:', studentId);
                            updateGradeSimple(studentId);
                        });
                        marksInput.addEventListener('change', function() {
                            updateGradeSimple(studentId);
                        });
                    }
                    
                    if (totalInput) {
                        totalInput.addEventListener('input', function() {
                            console.log('Total marks changed for student:', studentId);
                            updateGradeSimple(studentId);
                        });
                        totalInput.addEventListener('change', function() {
                            updateGradeSimple(studentId);
                        });
                    }
                    
                    // Initialize calculation
                    updateGradeSimple(studentId);
                }
            }
        });
        
        console.log('Initialization complete'); // Debug log
    });

    // Enhanced AI comment generation function with proper error handling
    async function generateCommentInline(studentId, studentName, subjectId, finalMark, finalGrade, targetGrade, attitude) {
        const commentTextarea = document.getElementById(`comment-${studentId}`);
        if (!commentTextarea) {
            console.error('Comment textarea not found for student:', studentId);
            return;
        }
        
        const originalComment = commentTextarea.value; // Store current comment
        commentTextarea.value = 'Generating AI comment...'; // Provide feedback
        commentTextarea.style.backgroundColor = '#e6f7ff'; // Light blue for loading

        try {
            const requestData = {
                student_name: studentName,
                subject_id: parseInt(subjectId),
                final_mark: finalMark !== '' && finalMark !== null ? parseFloat(finalMark) : null,
                final_grade: finalGrade,
                target_grade: targetGrade,
                attitude_to_learning: attitude
            };

            console.log('Sending request data:', requestData); // Debug log

            const response = await fetch('ajax_generate_comment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(requestData)
            });

            console.log('Response status:', response.status); // Debug log

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const responseText = await response.text();
            console.log('Raw response:', responseText); // Debug log

            let data;
            try {
                // Clean the response text by extracting JSON part (in case of PHP warnings)
                let cleanResponseText = responseText;
                const jsonStart = responseText.indexOf('{');
                if (jsonStart > 0) {
                    cleanResponseText = responseText.substring(jsonStart);
                    console.log('Cleaned response text:', cleanResponseText);
                }
                
                data = JSON.parse(cleanResponseText);
            } catch (parseError) {
                console.error('JSON parse error:', parseError);
                console.error('Response text:', responseText);
                throw new Error('Server returned invalid JSON. Check browser console for details.');
            }

            if (data.error) {
                throw new Error(data.message || 'Unknown error occurred');
            }

            if (data.comment) {
                commentTextarea.value = data.comment;
            } else {
                commentTextarea.value = 'AI could not generate a comment.';
            }
        } catch (error) {
            console.error('Error generating comment:', error);
            commentTextarea.value = originalComment; // Restore original comment on error
            alert('Error generating comment: ' + error.message); // Show user-friendly error
        } finally {
            commentTextarea.style.backgroundColor = ''; // Reset background
        }
    }
</script>

<?php
$teacherStmt->close();
$conn->close();
include 'footer.php';
?>