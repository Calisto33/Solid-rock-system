<?php
session_start();
include '../config.php'; 

// Security Checks
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['staff', 'admin'])) {
    header("Location: ../login.php");
    exit();
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit('Invalid request method.');
}

// Get context from form - handle both systems
$class_id = $_POST['class_id'] ?? null;
$subject_id = $_POST['subject_id'] ?? null;
$class_name = $_POST['class_name'] ?? null;
$subject_name = $_POST['subject_name'] ?? null;
$term = $_POST['term'] ?? null;
$year = $_POST['year'] ?? null;
$assessment_type = $_POST['assessment_type'] ?? 'Continuous Assessment';
$all_student_marks = $_POST['marks'] ?? [];

if ((!$class_id && !$class_name) || (!$subject_id && !$subject_name) || !$term || !$year) {
    header("Location: select_class.php");
    exit();
}

// Define assessment weights
define('WEIGHT_ASS1', 0.2);  // 20%
define('WEIGHT_ASS2', 0.2);  // 20%
define('WEIGHT_EXAM', 0.6);  // 60%

// Function to calculate advanced grade
function calculateAdvancedGrade($mark) {
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

// Function to calculate simple grade
function calculateSimpleGrade($mark) {
    if ($mark >= 80) return 'A';
    if ($mark >= 70) return 'B';
    if ($mark >= 60) return 'C';
    if ($mark >= 50) return 'D';
    if ($mark >= 40) return 'E';
    return 'F';
}

// Detect database structure more accurately
$database_info = [];
$use_advanced_system = false;

try {
    // Check if results table exists and get its structure
    $results_check = $conn->query("SHOW TABLES LIKE 'results'");
    if ($results_check && $results_check->num_rows > 0) {
        $results_columns = $conn->query("DESCRIBE results");
        $database_info['results_columns'] = [];
        while ($col = $results_columns->fetch_assoc()) {
            $database_info['results_columns'][] = $col['Field'];
        }
        
        // More precise detection logic
        $has_subject_id = in_array('subject_id', $database_info['results_columns']);
        $has_class_id = in_array('class_id', $database_info['results_columns']);
        $has_simple_subject = in_array('subject', $database_info['results_columns']);
        $has_academic_year = in_array('academic_year', $database_info['results_columns']);
        
        // Check for term_assessments table
        $assessments_check = $conn->query("SHOW TABLES LIKE 'term_assessments'");
        $has_term_assessments = ($assessments_check && $assessments_check->num_rows > 0);
        
        // FIXED LOGIC: Only use advanced system if we have ALL advanced requirements AND no simple columns
        $use_advanced_system = (
            $has_subject_id && 
            $has_class_id && 
            $has_term_assessments &&
            !$has_simple_subject &&  // Key fix: ensure we don't have simple system columns
            !$has_academic_year &&   // Key fix: ensure we don't have simple system columns
            $class_id && 
            $subject_id
        );
        
        // Debug info (remove in production)
        error_log("Database detection: subject_id=$has_subject_id, class_id=$has_class_id, subject=$has_simple_subject, academic_year=$has_academic_year, term_assessments=$has_term_assessments, use_advanced=$use_advanced_system");
    }
} catch (Exception $e) {
    $database_info['error'] = $e->getMessage();
    error_log("Database detection error: " . $e->getMessage());
}

$conn->begin_transaction(); 

try {
    $success_count = 0;
    
    foreach ($all_student_marks as $student_id => $data) {
        
        if ($use_advanced_system) {
            // --- ADVANCED SYSTEM (Only if we have proper advanced structure) ---
            
            // 1. Find or Create the main 'results' record
            $result_id = null;
            $findResultStmt = $conn->prepare("SELECT result_id FROM results WHERE student_id = ? AND subject_id = ? AND class_id = ? AND term = ? AND year = ?");
            $findResultStmt->bind_param("iiiss", $student_id, $subject_id, $class_id, $term, $year);
            $findResultStmt->execute();
            $result = $findResultStmt->get_result();
            if ($result->num_rows > 0) {
                $result_id = $result->fetch_assoc()['result_id'];
            } else {
                // Create new result record
                $insertResultStmt = $conn->prepare("INSERT INTO results (student_id, subject_id, class_id, term, year, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
                $insertResultStmt->bind_param("iiiss", $student_id, $subject_id, $class_id, $term, $year);
                $insertResultStmt->execute();
                $result_id = $conn->insert_id;
                $insertResultStmt->close();
            }
            $findResultStmt->close();

            if (!$result_id) continue;

            // 2. Save individual assessments to term_assessments table
            $assessment_names = [
                'ass1' => 'Assessment 1',
                'ass2' => 'Assessment 2',
                'exam' => 'End of Term Exam'
            ];

            $upsertAssessmentStmt = $conn->prepare(
                "INSERT INTO term_assessments (result_id, assessment_name, mark) VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE mark = VALUES(mark)"
            );

            foreach($assessment_names as $key => $name) {
                if (isset($data[$key]) && $data[$key] !== '') {
                    $mark = (float)$data[$key];
                    $upsertAssessmentStmt->bind_param("isd", $result_id, $name, $mark);
                    $upsertAssessmentStmt->execute();
                }
            }
            $upsertAssessmentStmt->close();

            // 3. Recalculate final mark and grade
            $current_marks = ['Assessment 1' => 0, 'Assessment 2' => 0, 'End of Term Exam' => 0];
            
            $getMarksStmt = $conn->prepare("SELECT assessment_name, mark FROM term_assessments WHERE result_id = ?");
            $getMarksStmt->bind_param("i", $result_id);
            $getMarksStmt->execute();
            $marksResult = $getMarksStmt->get_result();
            while($row = $marksResult->fetch_assoc()){
                $current_marks[$row['assessment_name']] = (float)$row['mark'];
            }
            $getMarksStmt->close();

            // Calculate final mark
            $final_mark = ($current_marks['Assessment 1'] * WEIGHT_ASS1) + 
                          ($current_marks['Assessment 2'] * WEIGHT_ASS2) + 
                          ($current_marks['End of Term Exam'] * WEIGHT_EXAM);

            // Determine grade using advanced system
            $final_grade = calculateAdvancedGrade($final_mark);
            
            // Get additional data
            $target_grade = trim($data['target_grade'] ?? '');
            $attitude = !empty($data['attitude']) ? (int)$data['attitude'] : null;
            $comments = trim($data['comments'] ?? '');

            // Update the results record
            $updateFields = [];
            $updateTypes = "";
            $updateValues = [];
            
            $updateFields[] = "final_mark = ?";
            $updateTypes .= "d";
            $updateValues[] = $final_mark;
            
            $updateFields[] = "final_grade = ?";
            $updateTypes .= "s";
            $updateValues[] = $final_grade;
            
            if ($target_grade) {
                $updateFields[] = "target_grade = ?";
                $updateTypes .= "s";
                $updateValues[] = $target_grade;
            }
            
            if ($attitude) {
                $updateFields[] = "attitude_to_learning = ?";
                $updateTypes .= "i";
                $updateValues[] = $attitude;
            }
            
            if ($comments) {
                $updateFields[] = "comments = ?";
                $updateTypes .= "s";
                $updateValues[] = $comments;
            }
            
            $updateFields[] = "updated_at = NOW()";
            
            $updateTypes .= "i";
            $updateValues[] = $result_id;
            
            $updateSQL = "UPDATE results SET " . implode(", ", $updateFields) . " WHERE result_id = ?";
            $updateFinalStmt = $conn->prepare($updateSQL);
            $updateFinalStmt->bind_param($updateTypes, ...$updateValues);
            $updateFinalStmt->execute();
            $updateFinalStmt->close();
            
        } else {
            // --- SIMPLE SYSTEM (Default - matches your actual database structure) ---
            
            // Handle both simple marks and advanced marks in simple system
            if (isset($data['marks_obtained']) && isset($data['total_marks'])) {
                // Traditional simple marks system
                $marks_obtained = (float)$data['marks_obtained'];
                $total_marks = (float)$data['total_marks'];
                $comments = trim($data['comments'] ?? '');
                
                if ($marks_obtained <= 0) continue; // Skip empty marks
                
                $percentage = ($marks_obtained / $total_marks) * 100;
                $grade = calculateSimpleGrade($percentage);
                
                // Check if record exists (using simple system columns)
                $checkQuery = "
                    SELECT result_id FROM results 
                    WHERE student_id = ? AND subject = ? AND term = ? AND academic_year = ? AND exam_type = ?
                ";
                $checkStmt = $conn->prepare($checkQuery);
                $checkStmt->bind_param("sssss", $student_id, $subject_name, $term, $year, $assessment_type);
                $checkStmt->execute();
                $checkResult = $checkStmt->get_result();
                
                if ($checkResult->num_rows > 0) {
                    // Update existing
                    $row = $checkResult->fetch_assoc();
                    $result_id = $row['result_id'];
                    
                    $updateQuery = "
                        UPDATE results 
                        SET marks_obtained = ?, total_marks = ?, final_mark = ?, grade = ?, comments = ?
                        WHERE result_id = ?
                    ";
                    $updateStmt = $conn->prepare($updateQuery);
                    $updateStmt->bind_param("dddssi", $marks_obtained, $total_marks, $marks_obtained, $grade, $comments, $result_id);
                    $updateStmt->execute();
                    $updateStmt->close();
                } else {
                    // Insert new
                    $insertQuery = "
                        INSERT INTO results (student_id, subject, exam_type, term, academic_year, marks_obtained, total_marks, final_mark, grade, exam_date, teacher_id, comments)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), ?, ?)
                    ";
                    $insertStmt = $conn->prepare($insertQuery);
                    $insertStmt->bind_param("sssssddssss", $student_id, $subject_name, $assessment_type, $term, $year, $marks_obtained, $total_marks, $marks_obtained, $grade, $_SESSION['user_id'], $comments);
                    $insertStmt->execute();
                    $insertStmt->close();
                }
                $checkStmt->close();
                
            } else {
                // Handle advanced assessment inputs but save in simple system format
                $ass1 = (float)($data['ass1'] ?? 0);
                $ass2 = (float)($data['ass2'] ?? 0);
                $exam = (float)($data['exam'] ?? 0);
                $comments = trim($data['comments'] ?? '');
                
                // Skip if no marks entered
                if ($ass1 <= 0 && $ass2 <= 0 && $exam <= 0) continue;
                
                // Calculate final mark using weights
                $final_mark = ($ass1 * WEIGHT_ASS1) + ($ass2 * WEIGHT_ASS2) + ($exam * WEIGHT_EXAM);
                $final_mark = round($final_mark, 2);
                
                // Use advanced grading for calculated marks
                $grade = calculateAdvancedGrade($final_mark);
                
                // Check if record exists for this specific assessment type
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
                        SET marks_obtained = ?, total_marks = ?, final_mark = ?, grade = ?, comments = ?
                        WHERE result_id = ?
                    ";
                    $updateStmt = $conn->prepare($updateQuery);
                    $total_marks = 100; // Default total for calculated marks
                    $updateStmt->bind_param("dddssi", $final_mark, $total_marks, $final_mark, $grade, $comments, $result_id);
                    $updateStmt->execute();
                    $updateStmt->close();
                } else {
                    // Insert new record
                    $insertQuery = "
                        INSERT INTO results (student_id, subject, exam_type, term, academic_year, marks_obtained, total_marks, final_mark, grade, exam_date, teacher_id, comments)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), ?, ?)
                    ";
                    $insertStmt = $conn->prepare($insertQuery);
                    $total_marks = 100; // Default total for calculated marks
                    $insertStmt->bind_param("sssssddssss", $student_id, $subject_name, $assessment_type, $term, $year, $final_mark, $total_marks, $final_mark, $grade, $_SESSION['user_id'], $comments);
                    $insertStmt->execute();
                    $insertStmt->close();
                }
                $checkStmt->close();
            }
        }
        
        $success_count++;
    }
    
    $conn->commit();
    $_SESSION['message'] = "Successfully saved marks for $success_count students using " . ($use_advanced_system ? 'advanced' : 'simple') . " system!";
    $_SESSION['message_type'] = "success";

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['message'] = "Error saving marks: " . $e->getMessage();
    $_SESSION['message_type'] = "error";
    
    // Additional debug info
    error_log("Save marks error: " . $e->getMessage());
    error_log("Use advanced system: " . ($use_advanced_system ? 'true' : 'false'));
}

// Build redirect URL with proper parameters
$redirect_params = [];
if ($class_id) $redirect_params[] = "class_id=" . urlencode($class_id);
if ($subject_id) $redirect_params[] = "subject_id=" . urlencode($subject_id);
if ($class_name) $redirect_params[] = "class_name=" . urlencode($class_name);
if ($subject_name) $redirect_params[] = "subject_name=" . urlencode($subject_name);
if ($term) $redirect_params[] = "term=" . urlencode($term);
if ($year) $redirect_params[] = "year=" . urlencode($year);

$redirect_url = "enter_marks.php?" . implode("&", $redirect_params);

// Redirect back to show updated marks
header("Location: " . $redirect_url);
exit();
?>