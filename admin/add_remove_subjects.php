<?php
// PHP SCRIPT STARTS HERE
session_start();
include '../config.php'; // Database connection

// Set a default empty message and message type
$message = '';
$message_type = '';

// Check database connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

/**
 * Functions are now defined only once at the top of the file.
 * This prevents the "Cannot redeclare" fatal error.
 */

// Function to find the subjects table name dynamically
function findTable($conn, $possibleTables) {
    foreach ($possibleTables as $table) {
        $checkQuery = "SHOW TABLES LIKE '$table'";
        $result = $conn->query($checkQuery);
        if ($result && $result->num_rows > 0) {
            return $table;
        }
    }
    return null;
}

// Function to get current subjects for a student from the relationship table
function getCurrentSubjects($conn, $student_id) {
    $relationshipTable = findTable($conn, ['student_subject', 'student_subjects', 'enrollments', 'tbl_student_subject']);
    if ($relationshipTable) {
        $query = "SELECT subject_id FROM $relationshipTable WHERE student_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $student_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $subjects = [];
        while ($row = $result->fetch_assoc()) {
            $subjects[] = $row['subject_id'];
        }
        $stmt->close();
        return $subjects;
    }
    return [];
}

// Find the correct subjects table and relationship table once at the beginning
$subjectsTable = findTable($conn, ['subjects', 'table_subject', 'subject', 'tbl_subjects']);
$relationshipTable = findTable($conn, ['student_subject', 'student_subjects', 'enrollments', 'tbl_student_subject']);

$subjects = [];
$subjectsError = '';
$relationshipError = '';

if ($subjectsTable) {
    $columnsQuery = "SHOW COLUMNS FROM $subjectsTable";
    $columnsResult = $conn->query($columnsQuery);
    $subjectColumns = [];
    if ($columnsResult) {
        while ($column = $columnsResult->fetch_assoc()) {
            $subjectColumns[] = $column['Field'];
        }
    }
    $idColumn = in_array('subject_id', $subjectColumns) ? 'subject_id' : (in_array('id', $subjectColumns) ? 'id' : (in_array('subjectId', $subjectColumns) ? 'subjectId' : null));
    $nameColumn = in_array('subject_name', $subjectColumns) ? 'subject_name' : (in_array('name', $subjectColumns) ? 'name' : (in_array('subjectName', $subjectColumns) ? 'subjectName' : (in_array('title', $subjectColumns) ? 'title' : null)));

    if ($idColumn && $nameColumn) {
        $subjectsQuery = "SELECT $idColumn as subject_id, $nameColumn as subject_name FROM $subjectsTable ORDER BY $nameColumn ASC";
        $subjectsResult = $conn->query($subjectsQuery);
        if ($subjectsResult) {
            while ($subject = $subjectsResult->fetch_assoc()) {
                $subjects[] = $subject;
            }
        }
    } else {
        $subjectsError = "Could not determine column structure for subjects table '$subjectsTable'. Found columns: " . implode(', ', $subjectColumns);
    }
} else {
    $subjectsError = "No subjects table found. Looked for: subjects, table_subject, subject, tbl_subjects";
}

if (!$relationshipTable) {
    $relationshipError = "Could not find student-subject relationship table. Looked for: student_subject, student_subjects, enrollments, tbl_student_subject. Subject management features are disabled.";
}

// Handle POST requests for various forms
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_class'])) {
        $student_id = $_POST['student_id'];
        $class = $_POST['class'];

        $updateQuery = "UPDATE students SET class = ? WHERE student_id = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("ss", $class, $student_id);

        if ($stmt->execute()) {
            $message = "Class updated successfully.";
            $message_type = "success";
        } else {
            $message = "Error updating class: " . $stmt->error;
            $message_type = "error";
        }
        $stmt->close();
    } elseif (isset($_POST['update_student_profile'])) {
        $student_id = $_POST['student_id'];
        $user_id = $_POST['user_id'];
        
        $conn->begin_transaction();
        try {
            // Update users table - FIXED BINDING
            if (isset($_POST['email']) && !empty($_POST['email'])) {
                $userFields = ['email = ?'];
                $userParams = [$_POST['email']];
                $userTypes = 's';
                
                // Add optional fields conditionally
                if (isset($_POST['first_name']) && !empty($_POST['first_name'])) {
                    $userFields[] = 'first_name = ?';
                    $userParams[] = $_POST['first_name'];
                    $userTypes .= 's';
                }
                if (isset($_POST['last_name']) && !empty($_POST['last_name'])) {
                    $userFields[] = 'last_name = ?';
                    $userParams[] = $_POST['last_name'];
                    $userTypes .= 's';
                }
                if (isset($_POST['phone_number']) && !empty($_POST['phone_number'])) {
                    $userFields[] = 'phone_number = ?';
                    $userParams[] = $_POST['phone_number'];
                    $userTypes .= 's';
                }
                if (isset($_POST['address']) && !empty($_POST['address'])) {
                    $userFields[] = 'address = ?';
                    $userParams[] = $_POST['address'];
                    $userTypes .= 's';
                }
                
                $userParams[] = $user_id;
                $userTypes .= 'i';
                
                $userQuery = 'UPDATE users SET ' . implode(', ', $userFields) . ' WHERE id = ?';
                $stmt = $conn->prepare($userQuery);
                
                // FIXED: Handle different parameter counts manually
                switch (count($userParams)) {
                    case 2:
                        $stmt->bind_param($userTypes, $userParams[0], $userParams[1]);
                        break;
                    case 3:
                        $stmt->bind_param($userTypes, $userParams[0], $userParams[1], $userParams[2]);
                        break;
                    case 4:
                        $stmt->bind_param($userTypes, $userParams[0], $userParams[1], $userParams[2], $userParams[3]);
                        break;
                    case 5:
                        $stmt->bind_param($userTypes, $userParams[0], $userParams[1], $userParams[2], $userParams[3], $userParams[4]);
                        break;
                    case 6:
                        $stmt->bind_param($userTypes, $userParams[0], $userParams[1], $userParams[2], $userParams[3], $userParams[4], $userParams[5]);
                        break;
                    default:
                        throw new Exception("Too many parameters for user update");
                }
                
                if (!$stmt->execute()) {
                    throw new Exception("Error updating user data: " . $stmt->error);
                }
                $stmt->close();
            }

            // Update students table - FIXED BINDING
            $studentFields = [];
            $studentParams = [];
            $studentTypes = "";
            
            if (isset($_POST['date_of_birth']) && !empty($_POST['date_of_birth'])) {
                $studentFields[] = "date_of_birth = ?";
                $studentParams[] = $_POST['date_of_birth'];
                $studentTypes .= "s";
            }
            if (isset($_POST['gender']) && !empty($_POST['gender'])) {
                $studentFields[] = "gender = ?";
                $studentParams[] = $_POST['gender'];
                $studentTypes .= "s";
            }
            if (isset($_POST['course']) && !empty($_POST['course'])) {
                $studentFields[] = "course = ?";
                $studentParams[] = $_POST['course'];
                $studentTypes .= "s";
            }
            
            if (count($studentFields) > 0) {
                $studentParams[] = $student_id;
                $studentTypes .= "s";
                
                $studentQuery = "UPDATE students SET " . implode(", ", $studentFields) . " WHERE student_id = ?";
                $stmt = $conn->prepare($studentQuery);
                
                // FIXED: Handle different parameter counts manually
                switch (count($studentParams)) {
                    case 2:
                        $stmt->bind_param($studentTypes, $studentParams[0], $studentParams[1]);
                        break;
                    case 3:
                        $stmt->bind_param($studentTypes, $studentParams[0], $studentParams[1], $studentParams[2]);
                        break;
                    case 4:
                        $stmt->bind_param($studentTypes, $studentParams[0], $studentParams[1], $studentParams[2], $studentParams[3]);
                        break;
                    default:
                        throw new Exception("Too many parameters for student update");
                }
                
                if (!$stmt->execute()) {
                    throw new Exception("Error updating student data: " . $stmt->error);
                }
                $stmt->close();
            }
            
            $conn->commit();
            $message = "Student profile updated successfully.";
            $message_type = "success";
        } catch (Exception $e) {
            $conn->rollback();
            $message = "Error updating profile: " . $e->getMessage();
            $message_type = "error";
        }
    } elseif (isset($_POST['create_subject'])) {
        if (!empty($subjectsTable) && !empty($_POST['new_subject_name'])) {
            $subject_name = trim($_POST['new_subject_name']);
            $checkQuery = "SELECT COUNT(*) as count FROM $subjectsTable WHERE subject_name = ?";
            $stmt = $conn->prepare($checkQuery);
            $stmt->bind_param("s", $subject_name);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            
            if ($row['count'] == 0) {
                $insertQuery = "INSERT INTO $subjectsTable (subject_name, subject_code, description, credits) VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($insertQuery);
                $credits = intval($_POST['credits']);
                $stmt->bind_param("sssi", $subject_name, $_POST['subject_code'], $_POST['subject_description'], $credits);
                if ($stmt->execute()) {
                    $message = "Successfully created subject: $subject_name";
                    $message_type = "success";
                } else {
                    $message = "Error creating subject: " . $stmt->error;
                    $message_type = "error";
                }
            } else {
                $message = "Subject '$subject_name' already exists.";
                $message_type = "error";
            }
            $stmt->close();
        } else {
            $message = "Please provide a subject name.";
            $message_type = "error";
        }
        
    } elseif (isset($_POST['add_subject'])) {
        if (!$relationshipTable) {
            $message = "Error: Could not find student-subject relationship table. Subject features are disabled.";
            $message_type = "error";
        } else {
            $student_id = $_POST['student_id'];
            $subject_id = $_POST['subject_to_add'];
    
            if (!empty($student_id) && !empty($subject_id)) {
                $checkQuery = "SELECT COUNT(*) FROM $relationshipTable WHERE student_id = ? AND subject_id = ?";
                $stmt = $conn->prepare($checkQuery);
                $stmt->bind_param("si", $student_id, $subject_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $count = $result->fetch_row()[0];
    
                if ($count == 0) {
                    $insertQuery = "INSERT INTO $relationshipTable (student_id, subject_id) VALUES (?, ?)";
                    $stmt = $conn->prepare($insertQuery);
                    $stmt->bind_param("si", $student_id, $subject_id);
                    if ($stmt->execute()) {
                        $message = "Subject added successfully.";
                        $message_type = "success";
                    } else {
                        $message = "Error adding subject: " . $stmt->error;
                        $message_type = "error";
                    }
                } else {
                    $message = "Student is already enrolled in this subject.";
                    $message_type = "error";
                }
                $stmt->close();
            } else {
                $message = "Error: Student ID or subject ID is missing.";
                $message_type = "error";
            }
        }
    } elseif (isset($_POST['update_subjects'])) {
        if (!$relationshipTable) {
            $message = "Error: Could not find student-subject relationship table. Subject features are disabled.";
            $message_type = "error";
        } else {
            $student_id = $_POST['student_id'];
            $subject_ids = isset($_POST['subjects']) ? $_POST['subjects'] : [];

            if (!empty($student_id)) {
                $conn->begin_transaction();
                try {
                    $deleteQuery = "DELETE FROM $relationshipTable WHERE student_id = ?";
                    $stmt = $conn->prepare($deleteQuery);
                    $stmt->bind_param("s", $student_id);
                    $stmt->execute();
                    $stmt->close();
                    
                    if (!empty($subject_ids)) {
                        $insertQuery = "INSERT INTO $relationshipTable (student_id, subject_id) VALUES (?, ?)";
                        $stmt = $conn->prepare($insertQuery);
                        foreach ($subject_ids as $subject_id) {
                            $stmt->bind_param("si", $student_id, $subject_id);
                            $stmt->execute();
                        }
                        $stmt->close();
                    }
                    $conn->commit();
                    $message = "Subjects updated successfully.";
                    $message_type = "success";
                } catch (Exception $e) {
                    $conn->rollback();
                    $message = "Error updating subjects: " . $e->getMessage();
                    $message_type = "error";
                }
            } else {
                $message = "Error: Student ID is missing.";
                $message_type = "error";
            }
        }
    }
}

// Get current subjects for AJAX
if (isset($_GET['get_subjects']) && isset($_GET['student_id'])) {
    $student_id = $_GET['student_id'];
    $current_subjects = getCurrentSubjects($conn, $student_id);
    header('Content-Type: application/json');
    echo json_encode($current_subjects);
    exit;
}

// Get column names for dynamic form generation
$userColumns = [];
$studentsColumns = [];
if ($usersColumnsResult = $conn->query("SHOW COLUMNS FROM users")) { while ($row = $usersColumnsResult->fetch_assoc()) { $userColumns[] = $row['Field']; } }
if ($studentsColumnsResult = $conn->query("SHOW COLUMNS FROM students")) { while ($row = $studentsColumnsResult->fetch_assoc()) { $studentsColumns[] = $row['Field']; } }

// Build the final query to fetch all students
$userSelectColumns = ['u.username', 'u.email'];
$studentSelectColumns = ['s.student_id', 's.user_id'];
$optionalUserColumns = ['first_name', 'last_name', 'phone_number', 'address', 'created_at'];
$optionalStudentColumns = ['class', 'course', 'year', 'date_of_birth', 'gender', 'enrollment_date'];

foreach ($optionalUserColumns as $col) { if (in_array($col, $userColumns)) { $userSelectColumns[] = "u.$col"; } }
foreach ($optionalStudentColumns as $col) { if (in_array($col, $studentsColumns)) { $studentSelectColumns[] = "s.$col"; } }

$selectClause = implode(', ', array_merge($studentSelectColumns, $userSelectColumns));
$studentsQuery = "SELECT $selectClause FROM students s JOIN users u ON s.user_id = u.id WHERE u.role = 'student' ORDER BY " . (in_array('class', $studentsColumns) ? 's.class ASC, ' : '') . "u.username ASC";
$studentsResult = $conn->query($studentsQuery);

// PHP SCRIPT ENDS HERE
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="icon" type="image/ico" href="images/favicon.ico">
    <link rel="shortcut icon" type="image/jpeg" href="images/logo.jpeg">
    <style>
        :root {
            --primary-color: #3498db;
            --primary-dark: #2980b9;
            --accent-color: #e74c3c;
            --success-color: #2ecc71;
            --warning-color: #f39c12;
            --text-color: #2c3e50;
            --text-light: #7f8c8d;
            --bg-color: #ecf0f1;
            --card-bg: #ffffff;
            --border-radius: 12px;
            --shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--bg-color);
            color: var(--text-color);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .alert {
            padding: 0.8rem 1rem;
            margin: 0.5rem 0;
            border-radius: 6px;
            border: 1px solid transparent;
            position: relative;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            opacity: 1;
            transition: opacity 0.3s ease;
        }

        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }

        .alert-error {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }

        .alert-warning {
            color: #856404;
            background-color: #fff3cd;
            border-color: #ffeaa7;
        }

        .badge {
            display: inline-block;
            padding: 0.25em 0.6em;
            font-size: 0.75em;
            font-weight: 700;
            line-height: 1;
            text-align: center;
            white-space: nowrap;
            vertical-align: baseline;
            border-radius: 0.25rem;
            background-color: var(--primary-color);
            color: white;
        }

        .header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 1rem;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: var(--shadow);
        }

        .header-content {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .header h2 {
            font-weight: 600;
            font-size: 1.4rem;
            margin: 0;
        }

        .link-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background-color: rgba(255, 255, 255, 0.2);
            color: white;
            padding: 0.7rem 1.2rem;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .link-btn:hover {
            background-color: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }

        .container {
            max-width: 1600px;
            width: 100%;
            margin: 1rem auto;
            padding: 0 1rem;
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 0;
        }

        .card {
            background: var(--card-bg);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            overflow: hidden;
            transition: var(--transition);
            margin-bottom: 1rem;
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 0;
        }

        .card-header {
            background: linear-gradient(to right, var(--primary-color), var(--primary-dark));
            color: white;
            padding: 1rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-shrink: 0;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .card-header h3 {
            font-weight: 600;
            margin: 0;
            font-size: 1.3rem;
        }
        
        .card-body {
            padding: 1rem;
            flex: 1;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .table-responsive {
            overflow: auto;
            flex: 1;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.05);
            min-height: 0;
        }

        .table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background: var(--card-bg);
            font-size: 0.85rem;
        }

        .table th,
        .table td {
            padding: 0.6rem 0.8rem;
            text-align: left;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            vertical-align: top;
        }

        .table th {
            background-color: var(--primary-color);
            color: white;
            font-weight: 500;
            position: sticky;
            top: 0;
            z-index: 10;
            font-size: 0.8rem;
        }

        .table tr:last-child td {
            border-bottom: none;
        }

        .table tr:hover {
            background-color: rgba(0, 0, 0, 0.02);
        }

        select {
            padding: 0.5rem 0.7rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            background-color: white;
            font-family: inherit;
            font-size: 0.8rem;
            color: var(--text-color);
            margin-right: 0.3rem;
            outline: none;
            transition: var(--transition);
        }

        select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        }

        button {
            background-color: var(--primary-color);
            color: white;
            padding: 0.5rem 0.8rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-family: inherit;
            font-size: 0.8rem;
            font-weight: 500;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            margin: 0.1rem;
        }

        button:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }

        button:disabled {
            background-color: #bdc3c7;
            cursor: not-allowed;
            transform: none;
        }

        .btn-manage {
            background-color: var(--accent-color);
        }

        .btn-manage:hover:not(:disabled) {
            background-color: #c0392b;
        }

        .btn-update {
            background-color: var(--success-color);
        }

        .btn-update:hover:not(:disabled) {
            background-color: #27ae60;
        }

        .btn-add {
            background-color: var(--warning-color);
            font-size: 0.75rem;
            padding: 0.4rem 0.6rem;
        }

        .btn-add:hover:not(:disabled) {
            background-color: #e67e22;
        }

        .subject-form {
            display: flex;
            flex-direction: column;
            gap: 0.3rem;
            margin-bottom: 0.3rem;
        }

        .subject-form select {
            min-width: 120px;
            margin-right: 0;
        }

        .current-subjects {
            display: flex;
            flex-wrap: wrap;
            gap: 0.2rem;
            margin-top: 0.3rem;
            max-height: 60px;
            overflow-y: auto;
        }

        .subject-badge {
            background-color: var(--success-color);
            color: white;
            padding: 0.15em 0.4em;
            border-radius: 10px;
            font-size: 0.65em;
            font-weight: 500;
            white-space: nowrap;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            overflow-y: auto;
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background-color: var(--card-bg);
            margin: 5% auto;
            padding: 2rem;
            width: 90%;
            max-width: 550px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            transform: translateY(20px);
            opacity: 0;
            transition: all 0.3s ease-out;
        }

        .modal.active .modal-content {
            transform: translateY(0);
            opacity: 1;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
        }

        .modal-header h4 {
            font-size: 1.5rem;
            color: var(--primary-color);
            font-weight: 600;
            margin: 0;
        }

        .close-btn {
            background: none;
            border: none;
            color: var(--text-light);
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0;
        }

        .close-btn:hover {
            color: var(--text-color);
            transform: none;
        }

        .subjects-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .checkbox-group {
            position: relative;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem;
            border-radius: 8px;
            transition: var(--transition);
        }

        .checkbox-group:hover {
            background-color: rgba(0, 0, 0, 0.02);
        }

        .checkbox-group input[type="checkbox"] {
            appearance: none;
            -webkit-appearance: none;
            height: 20px;
            width: 20px;
            border: 2px solid var(--primary-color);
            border-radius: 5px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            outline: none;
            transition: var(--transition);
        }

        .checkbox-group input[type="checkbox"]::after {
            content: "\f00c";
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            font-size: 12px;
            color: white;
            display: none;
        }

        .checkbox-group input[type="checkbox"]:checked {
            background-color: var(--primary-color);
        }

        .checkbox-group input[type="checkbox"]:checked::after {
            display: block;
        }

        .checkbox-group label {
            font-size: 1rem;
            color: var(--text-color);
            cursor: pointer;
        }

        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 2rem;
        }

        .btn-cancel {
            background-color: transparent;
            color: var(--text-color);
            border: 1px solid #ddd;
        }

        .btn-cancel:hover {
            background-color: rgba(0, 0, 0, 0.05);
            color: var(--text-color);
        }

        .student-info {
            background-color: rgba(52, 152, 219, 0.1);
            border-left: 4px solid var(--primary-color);
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 0 8px 8px 0;
        }

        .student-info p {
            margin: 0;
            color: var(--text-color);
        }

        .student-info strong {
            font-weight: 600;
            color: var(--primary-color);
        }

        .footer {
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary-color) 100%);
            color: white;
            text-align: center;
            padding: 0.8rem;
            margin-top: auto;
            flex-shrink: 0;
        }

        .footer p {
            margin: 0;
            font-size: 0.8rem;
            opacity: 0.9;
        }

        .details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .detail-item {
            display: flex;
            flex-direction: column;
            gap: 0.3rem;
            padding: 0.8rem;
            background-color: #f8f9fa;
            border-radius: 6px;
            border-left: 3px solid var(--primary-color);
        }

        .detail-label {
            font-weight: 600;
            color: var(--primary-color);
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .detail-value {
            color: var(--text-color);
            font-size: 0.9rem;
            line-height: 1.4;
        }

        .btn-view-details {
            background-color: var(--primary-color);
            font-size: 0.7rem;
            padding: 0.3rem 0.6rem;
            margin: 0.1rem;
        }

        .btn-edit-profile {
            background-color: #17a2b8;
            font-size: 0.7rem;
            padding: 0.3rem 0.6rem;
            margin: 0.1rem;
        }

        .btn-edit-profile:hover:not(:disabled) {
            background-color: #138496;
        }

        .edit-form {
            display: none;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 0.8rem;
            margin-top: 1rem;
            padding: 1rem;
            background-color: #f8f9fa;
            border-radius: 6px;
        }

        .edit-form.active {
            display: grid;
        }

        .form-group-inline {
            display: flex;
            flex-direction: column;
            gap: 0.3rem;
        }

        .form-group-inline label {
            font-weight: 500;
            font-size: 0.75rem;
            color: var(--text-color);
        }

        .form-group-inline input,
        .form-group-inline select,
        .form-group-inline textarea {
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 0.8rem;
        }

        .form-actions-inline {
            grid-column: 1 / -1;
            display: flex;
            gap: 0.5rem;
            justify-content: flex-end;
            margin-top: 0.5rem;
        }

        @media screen and (max-width: 768px) {
            .header-content {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
            .header h2 {
                font-size: 1.2rem;
            }
            .table th, .table td {
                padding: 0.4rem 0.3rem;
                font-size: 0.75rem;
            }
            .modal-content {
                margin: 5% auto;
                padding: 1rem;
                width: 95%;
            }
            .subjects-container {
                grid-template-columns: 1fr;
            }
            .subject-form {
                align-items: stretch;
            }
            .subject-form select, .subject-form button {
                width: 100%;
                margin: 0.1rem 0;
                font-size: 0.75rem;
            }
            .management-actions {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
            .card-header {
                padding: 0.8rem 1rem;
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
            .card-header h3 {
                font-size: 1.1rem;
            }
        }

        @media screen and (max-width: 480px) {
            .container {
                padding: 0 0.5rem;
            }
            .table th, .table td {
                padding: 0.3rem 0.2rem;
                font-size: 0.7rem;
            }
            button {
                padding: 0.4rem 0.6rem;
                font-size: 0.7rem;
            }
            select {
                padding: 0.4rem 0.5rem;
                font-size: 0.7rem;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <h2><i class="fas fa-users"></i> Student Management Portal</h2>
            <a href="admin_home.php" class="link-btn">
                <i class="fas fa-home"></i> Management Dashboard
            </a>
        </div>
    </header>

    <div class="container">
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo htmlspecialchars($message_type); ?>">
                <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($subjectsError) || !empty($relationshipError)): ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i>
                <div>
                    <strong>Database Configuration Issue:</strong> 
                    <?php echo htmlspecialchars($subjectsError); ?>
                    <?php echo htmlspecialchars($relationshipError); ?>
                    <br><small>Subject management features may not work properly until this is resolved.</small>
                </div>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-user-graduate"></i> Assign Classes & Subjects</h3>
                <div style="display: flex; gap: 1rem; align-items: center;">
                    <?php if (!empty($subjects)): ?>
                        <span class="badge" style="background-color: var(--success-color);">
                            <?php echo count($subjects); ?> Subjects Available
                        </span>
                    <?php endif; ?>
                    <button onclick="openAddSubjectModal()" class="link-btn" style="background-color: var(--warning-color); border-color: var(--warning-color);">
                        <i class="fas fa-plus"></i> Add New Subject
                    </button>
                    
                </div>
            </div>
            <div class="card-body">
                <?php if ($studentsResult && $studentsResult->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Student ID</th>
                                    <th>Student Info</th>
                                    <th>Email</th>
                                    <th>Current Class</th>
                                    <?php if (in_array('course', $studentsColumns)): ?><th>Course</th><?php endif; ?>
                                    <?php if (in_array('year', $studentsColumns)): ?><th>Year</th><?php endif; ?>
                                    <th>Current Subjects</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($student = $studentsResult->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($student['student_id'] ?? ''); ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars(($student['first_name'] ?? '') . ' ' . ($student['last_name'] ?? '')); ?></strong>
                                            <br><small>@<?php echo htmlspecialchars($student['username'] ?? ''); ?></small>
                                            <div style="margin-top: 0.3rem;">
                                                <button onclick='openStudentDetails(<?php echo json_encode($student); ?>)' class="btn-view-details">
                                                    <i class="fas fa-eye"></i> Details
                                                </button>
                                                <button onclick='openEditProfile(<?php echo json_encode($student); ?>)' class="btn-edit-profile">
                                                    <i class="fas fa-edit"></i> Edit
                                                </button>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($student['email'] ?? ''); ?></td>
                                        <td>
                                            <?php if (!empty($student['class'])): ?>
                                                <span class="badge"><?php echo htmlspecialchars(strtoupper($student['class'])); ?></span>
                                            <?php else: ?>
                                                <span class="badge" style="background-color: #95a5a6;">Not Assigned</span>
                                            <?php endif; ?>
                                            <div style="margin-top: 0.5rem;">
                                                <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" style="display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center;">
                                                    <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($student['student_id']); ?>">
                                                    <input type="hidden" name="class" id="final_class_<?php echo htmlspecialchars($student['student_id']); ?>">
                                                    <select id="level_<?php echo htmlspecialchars($student['student_id']); ?>" onchange="updateClassDropdowns('<?php echo htmlspecialchars($student['student_id']); ?>')" required>
                                                        <option value="">Select Level</option>
                                                        <option value="ECD">ECD</option>
                                                        <option value="Primary">Primary</option>
                                                        <option value="High School">High School</option>
                                                    </select>
                                                    <select id="grade_form_<?php echo htmlspecialchars($student['student_id']); ?>" onchange="updateClassDropdowns('<?php echo htmlspecialchars($student['student_id']); ?>')" required style="display: none;"><option value="">Select Grade/Form</option></select>
                                                    <select id="mineral_class_<?php echo htmlspecialchars($student['student_id']); ?>" onchange="updateClassDropdowns('<?php echo htmlspecialchars($student['student_id']); ?>')" required style="display: none;"><option value="Gold">Gold</option><option value="Silver">Silver</option><option value="Platinum">Platinum</option><option value="Diamond">Diamond</option><option value="Bronze">Bronze</option></select>
                                                    <button type="submit" name="update_class" class="btn-update"><i class="fas fa-sync-alt"></i> Update</button>
                                                </form>
                                            </div>
                                        </td>
                                        <?php if (in_array('course', $studentsColumns)): ?><td><?php echo htmlspecialchars($student['course'] ?? 'N/A'); ?></td><?php endif; ?>
                                        <?php if (in_array('year', $studentsColumns)): ?><td><?php echo htmlspecialchars($student['year'] ?? 'N/A'); ?></td><?php endif; ?>
                                        <td>
                                            <?php
                                                $currentSubjects = getCurrentSubjects($conn, $student['student_id']);
                                                if (!empty($currentSubjects) && !empty($subjects)):
                                                    $enrolledSubjectNames = array_map(function($subject_id) use ($subjects) {
                                                        $key = array_search($subject_id, array_column($subjects, 'subject_id'));
                                                        return $key !== false ? $subjects[$key]['subject_name'] : null;
                                                    }, $currentSubjects);
                                                    $enrolledSubjectNames = array_filter($enrolledSubjectNames);
                                            ?>
                                                <div class="current-subjects">
                                                    <?php foreach ($enrolledSubjectNames as $subjectName): ?>
                                                        <span class="subject-badge"><?php echo htmlspecialchars($subjectName); ?></span>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php else: ?>
                                                <span style="color: var(--text-light); font-style: italic;">No subjects assigned</span>
                                            <?php endif; ?>
                                            <div style="margin-top: 0.5rem;">
                                                <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" style="display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center;">
                                                    <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($student['student_id']); ?>">
                                                    <select name="subject_to_add" style="min-width: 150px;">
                                                        <option value="">Select Subject</option>
                                                        <?php
                                                            $allSubjectsIds = array_column($subjects, 'subject_id');
                                                            $unassignedSubjects = array_diff($allSubjectsIds, $currentSubjects);
                                                            foreach ($subjects as $subject):
                                                                if (in_array($subject['subject_id'], $unassignedSubjects)):
                                                        ?>
                                                                    <option value="<?php echo htmlspecialchars($subject['subject_id']); ?>"><?php echo htmlspecialchars($subject['subject_name']); ?></option>
                                                        <?php
                                                                endif;
                                                            endforeach;
                                                        ?>
                                                    </select>
                                                    <button type="submit" name="add_subject" class="btn-add" <?php echo empty($relationshipTable) ? 'disabled' : ''; ?>><i class="fas fa-plus"></i> Add</button>
                                                </form>
                                                <button onclick="manageSubjects('<?php echo htmlspecialchars($student['student_id']); ?>', '<?php echo htmlspecialchars($student['username'] ?? ''); ?>')" class="btn-manage" <?php echo empty($subjects) || empty($relationshipTable) ? 'disabled' : ''; ?>>
                                                    <i class="fas fa-book"></i> View/Update All
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-triangle"></i> No students found. Please check your database configuration or register some students first.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Student Details Modal -->
    <div class="modal" id="studentDetailsModal">
        <div class="modal-content" style="max-width: 700px;">
            <div class="modal-header">
                <h4><i class="fas fa-user-circle"></i> Student Profile Details</h4>
                <button type="button" class="close-btn" onclick="closeModal('studentDetailsModal')"><i class="fas fa-times"></i></button>
            </div>
            <div class="student-info">
                <p><strong>Student:</strong> <span id="detailsStudentName"></span></p>
                <p><strong>Student ID:</strong> <span id="detailsStudentId"></span></p>
                <p><strong>Username:</strong> <span id="detailsUsername"></span></p>
            </div>
            <div class="details-grid" id="studentDetailsGrid"></div>
        </div>
    </div>

    <!-- Edit Profile Modal -->
    <div class="modal" id="editProfileModal">
        <div class="modal-content" style="max-width: 800px;">
            <div class="modal-header">
                <h4><i class="fas fa-user-edit"></i> Edit Student Profile</h4>
                <button type="button" class="close-btn" onclick="closeModal('editProfileModal')"><i class="fas fa-times"></i></button>
            </div>
            <div class="student-info">
                <p><strong>Student:</strong> <span id="editStudentName"></span></p>
                <p><strong>Student ID:</strong> <span id="editStudentId"></span></p>
            </div>
            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" id="editProfileForm">
                <input type="hidden" name="student_id" id="editFormStudentId">
                <input type="hidden" name="user_id" id="editFormUserId">
                <div class="edit-form active">
                    <div class="form-group-inline">
                        <label>First Name <?php echo in_array('first_name', $userColumns) ? '*' : '(Not Available)'; ?></label>
                        <input type="text" name="first_name" id="editFirstName" <?php echo in_array('first_name', $userColumns) ? 'required' : 'disabled'; ?>>
                    </div>
                    <div class="form-group-inline">
                        <label>Last Name <?php echo in_array('last_name', $userColumns) ? '*' : '(Not Available)'; ?></label>
                        <input type="text" name="last_name" id="editLastName" <?php echo in_array('last_name', $userColumns) ? 'required' : 'disabled'; ?>>
                    </div>
                    <div class="form-group-inline">
                        <label>Email *</label>
                        <input type="email" name="email" id="editEmail" required>
                    </div>
                    <div class="form-group-inline">
                        <label>Phone Number <?php echo !in_array('phone_number', $userColumns) ? '(Not Available)' : ''; ?></label>
                        <input type="text" name="phone_number" id="editPhoneNumber" <?php echo !in_array('phone_number', $userColumns) ? 'disabled' : ''; ?>>
                    </div>
                    <div class="form-group-inline">
                        <label>Date of Birth <?php echo !in_array('date_of_birth', $studentsColumns) ? '(Not Available)' : ''; ?></label>
                        <input type="date" name="date_of_birth" id="editDateOfBirth" <?php echo !in_array('date_of_birth', $studentsColumns) ? 'disabled' : ''; ?>>
                    </div>
                    <div class="form-group-inline">
                        <label>Gender <?php echo !in_array('gender', $studentsColumns) ? '(Not Available)' : ''; ?></label>
                        <select name="gender" id="editGender" <?php echo !in_array('gender', $studentsColumns) ? 'disabled' : ''; ?>>
                            <option value="">Select Gender</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="form-group-inline">
                        <label>Course <?php echo !in_array('course', $studentsColumns) ? '(Not Available)' : ''; ?></label>
                        <input type="text" name="course" id="editCourse" <?php echo !in_array('course', $studentsColumns) ? 'disabled' : ''; ?>>
                    </div>
                    <div class="form-group-inline" style="grid-column: 1 / -1;">
                        <label>Address <?php echo !in_array('address', $userColumns) ? '(Not Available)' : ''; ?></label>
                        <textarea name="address" rows="3" id="editAddress" <?php echo !in_array('address', $userColumns) ? 'disabled' : ''; ?>></textarea>
                    </div>
                </div>
                <div class="modal-actions">
                    <button type="button" onclick="closeModal('editProfileModal')" class="btn-cancel"><i class="fas fa-times"></i> Cancel</button>
                    <button type="submit" name="update_student_profile" class="btn-update"><i class="fas fa-save"></i> Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Subject Modal -->
    <div class="modal" id="addSubjectModal">
        <div class="modal-content">
            <div class="modal-header">
                <h4><i class="fas fa-plus-circle"></i> Add New Subject</h4>
                <button type="button" class="close-btn" onclick="closeModal('addSubjectModal')"><i class="fas fa-times"></i></button>
            </div>
            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
                <div style="margin-bottom: 1.5rem;">
                    <label for="new_subject_name" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Subject Name <span style="color: #e74c3c;">*</span></label>
                    <input type="text" id="new_subject_name" name="new_subject_name" required style="width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 8px; font-size: 1rem;" placeholder="e.g., Advanced Mathematics, World History">
                </div>
                <div style="margin-bottom: 1.5rem;">
                    <label for="subject_code" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Subject Code (Optional)</label>
                    <input type="text" id="subject_code" name="subject_code" style="width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 8px; font-size: 1rem;" placeholder="e.g., MATH201, HIST101">
                </div>
                <div style="margin-bottom: 1.5rem;">
                    <label for="subject_description" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Description (Optional)</label>
                    <textarea id="subject_description" name="subject_description" rows="3" style="width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 8px; font-size: 1rem; resize: vertical;" placeholder="Brief description of the subject..."></textarea>
                </div>
                <div style="margin-bottom: 1.5rem;">
                    <label for="credits" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Credits</label>
                    <select id="credits" name="credits" style="width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 8px; font-size: 1rem;">
                        <option value="1">1 Credit</option>
                        <option value="2">2 Credits</option>
                        <option value="3" selected>3 Credits</option>
                        <option value="4">4 Credits</option>
                        <option value="5">5 Credits</option>
                    </select>
                </div>
                <div class="modal-actions">
                    <button type="button" onclick="closeModal('addSubjectModal')" class="btn-cancel"><i class="fas fa-times"></i> Cancel</button>
                    <button type="submit" name="create_subject" class="btn-update"><i class="fas fa-save"></i> Create Subject</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Subject Management Modal -->
    <div class="modal" id="subjectModal">
        <div class="modal-content">
            <div class="modal-header">
                <h4><i class="fas fa-book-open"></i> Manage Student Subjects</h4>
                <button type="button" class="close-btn" onclick="closeModal('subjectModal')"><i class="fas fa-times"></i></button>
            </div>
            <div class="student-info">
                <p><strong>Student:</strong> <span id="studentName"></span></p>
                <p><strong>Student ID:</strong> <span id="studentIdDisplay"></span></p>
            </div>
            <?php if (!empty($subjects)): ?>
                <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
                    <input type="hidden" name="student_id" id="student_id">
                    <div class="subjects-container">
                        <?php foreach ($subjects as $subject): ?>
                            <div class="checkbox-group">
                                <input type="checkbox" name="subjects[]" value="<?php echo htmlspecialchars($subject['subject_id']); ?>" id="subject_<?php echo htmlspecialchars($subject['subject_id']); ?>">
                                <label for="subject_<?php echo htmlspecialchars($subject['subject_id']); ?>"><?php echo htmlspecialchars($subject['subject_name']); ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="modal-actions">
                        <button type="button" onclick="closeModal('subjectModal')" class="btn-cancel"><i class="fas fa-times"></i> Cancel</button>
                        <button type="submit" name="update_subjects" class="btn-update"><i class="fas fa-save"></i> Save Changes</button>
                    </div>
                </form>
            <?php else: ?>
                <div class="no-subjects-message">
                    <i class="fas fa-exclamation-circle" style="font-size: 2rem; margin-bottom: 1rem; color: var(--warning-color);"></i>
                    <p>No subjects are available in the system.</p>
                    <p>Please create subjects first before assigning them to students.</p>
                </div>
                <div class="modal-actions">
                    <button type="button" onclick="closeModal('subjectModal')" class="btn-cancel"><i class="fas fa-times"></i> Close</button>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; <?php echo date("Y"); ?> Wisetech College Student Management Portal</p>
    </footer>

    <script>
        // Centralized modal management functions
        function openModal(modalId, data = null) {
            const modal = document.getElementById(modalId);
            if (!modal) return;

            // Close any other open modals first
            document.querySelectorAll('.modal').forEach(m => m.classList.remove('active'));
            
            // Handle specific modal data population
            if (modalId === 'studentDetailsModal' && data) {
                document.getElementById("detailsStudentName").textContent = (data.first_name || '') + ' ' + (data.last_name || '');
                document.getElementById("detailsStudentId").textContent = data.student_id;
                document.getElementById("detailsUsername").textContent = data.username || '';
                const detailsGrid = document.getElementById("studentDetailsGrid");
                detailsGrid.innerHTML = `
                    <div class="detail-item"><span class="detail-label">Full Name</span><span class="detail-value">${(data.first_name || '') + ' ' + (data.last_name || '') || 'N/A'}</span></div>
                    <div class="detail-item"><span class="detail-label">Email</span><span class="detail-value">${data.email || 'N/A'}</span></div>
                    <div class="detail-item"><span class="detail-label">Phone Number</span><span class="detail-value">${data.phone_number || 'N/A'}</span></div>
                    <div class="detail-item"><span class="detail-label">Date of Birth</span><span class="detail-value">${data.date_of_birth || 'N/A'}</span></div>
                    <div class="detail-item"><span class="detail-label">Gender</span><span class="detail-value">${data.gender || 'N/A'}</span></div>
                    <div class="detail-item"><span class="detail-label">Class</span><span class="detail-value">${data.class || 'N/A'}</span></div>
                    <div class="detail-item"><span class="detail-label">Course</span><span class="detail-value">${data.course || 'N/A'}</span></div>
                    <div class="detail-item"><span class="detail-label">Year</span><span class="detail-value">${data.year || 'N/A'}</span></div>
                    <div class="detail-item" style="grid-column: 1 / -1;"><span class="detail-label">Address</span><span class="detail-value">${data.address || 'N/A'}</span></div>
                    <div class="detail-item"><span class="detail-label">Enrollment Date</span><span class="detail-value">${data.enrollment_date || data.created_at || 'N/A'}</span></div>
                `;
            } else if (modalId === 'editProfileModal' && data) {
                document.getElementById("editStudentName").textContent = (data.first_name || '') + ' ' + (data.last_name || '');
                document.getElementById("editStudentId").textContent = data.student_id;
                document.getElementById("editFormStudentId").value = data.student_id;
                document.getElementById("editFormUserId").value = data.user_id || '';
                const formFields = ['editFirstName', 'editLastName', 'editEmail', 'editPhoneNumber', 'editDateOfBirth', 'editGender', 'editCourse', 'editAddress'];
                formFields.forEach(fieldId => {
                    const field = document.getElementById(fieldId);
                    if (field) {
                        const key = field.name;
                        if (field.type === 'select-one' || field.tagName.toLowerCase() === 'textarea' || field.type === 'date') {
                            field.value = data[key] || '';
                        } else {
                            field.value = data[key] || '';
                        }
                    }
                });
            } else if (modalId === 'subjectModal' && data) {
                document.getElementById("student_id").value = data.student_id;
                document.getElementById("studentName").textContent = data.username;
                document.getElementById("studentIdDisplay").textContent = data.student_id;
                const checkboxes = document.querySelectorAll('input[name="subjects[]"]');
                checkboxes.forEach(cb => cb.checked = false);
                fetch(`<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>?get_subjects=1&student_id=${data.student_id}`)
                    .then(response => response.json())
                    .then(currentSubjects => {
                        currentSubjects.forEach(subjectId => {
                            const checkbox = document.getElementById(`subject_${subjectId}`);
                            if (checkbox) checkbox.checked = true;
                        });
                    })
                    .catch(error => console.error('Error fetching subjects:', error));
            }
            
            modal.style.display = "block";
            setTimeout(() => modal.classList.add("active"), 10);
        }

        // Close a specific modal
        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (!modal) return;
            modal.classList.remove("active");
            setTimeout(() => modal.style.display = "none", 300);
        }

        // Event listeners for opening modals
        function openStudentDetails(data) { openModal('studentDetailsModal', data); }
        function openEditProfile(data) { openModal('editProfileModal', data); }
        function openAddSubjectModal() { openModal('addSubjectModal'); }
        
        // This function is still needed to manage the subject modal specifically due to its unique data fetching
        function manageSubjects(studentId, studentName) {
            console.log('Managing subjects for student ID:', studentId); // Debug line to verify correct ID
            openModal('subjectModal', { student_id: studentId, username: studentName });
        }

        window.onclick = function(event) {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                if (event.target === modal) {
                    closeModal(modal.id);
                }
            });
        };

        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.alert').forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 300);
                }, 5000);
            });
            initializeClassDropdowns();
        });

        // Class dropdown logic
        const classStructure = {
            'ECD': { grades: ['ECD A', 'ECD B'], hasMinerals: false },
            'Primary': { grades: ['Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6', 'Grade 7'], hasMinerals: true },
            'High School': { grades: ['Form 1', 'Form 2', 'Form 3', 'Form 4', 'Form 5', 'Form 6'], hasMinerals: true }
        };

        function updateClassDropdowns(studentId) {
            const levelSelect = document.getElementById(`level_${studentId}`);
            const gradeFormSelect = document.getElementById(`grade_form_${studentId}`);
            const mineralSelect = document.getElementById(`mineral_class_${studentId}`);
            const finalClassInput = document.getElementById(`final_class_${studentId}`);

            const selectedLevel = levelSelect.value;
            let selectedGrade = gradeFormSelect.value;
            let selectedMineral = mineralSelect.value;

            // Save the current selection to restore after update
            const currentGradeValue = gradeFormSelect.value;
            const currentMineralValue = mineralSelect.value;

            gradeFormSelect.innerHTML = '<option value="">Select Grade/Form</option>';
            gradeFormSelect.style.display = 'none';

            if (selectedLevel && classStructure[selectedLevel]) {
                const levelData = classStructure[selectedLevel];
                levelData.grades.forEach(grade => {
                    gradeFormSelect.add(new Option(grade, grade));
                });
                gradeFormSelect.style.display = 'inline-block';
                // Restore the previous selection if it's still a valid option
                if (levelData.grades.includes(currentGradeValue)) {
                    gradeFormSelect.value = currentGradeValue;
                    selectedGrade = currentGradeValue;
                }
            }

            if (selectedLevel && classStructure[selectedLevel].hasMinerals && selectedGrade) {
                mineralSelect.style.display = 'inline-block';
                // Restore the previous selection for mineral
                if (currentMineralValue) {
                    mineralSelect.value = currentMineralValue;
                    selectedMineral = currentMineralValue;
                }
            } else {
                mineralSelect.style.display = 'none';
                mineralSelect.value = '';
                selectedMineral = '';
            }

            // Set the final class value for the hidden input field
            let finalClassValue = '';
            if (selectedGrade) {
                if (selectedLevel === 'ECD' || !classStructure[selectedLevel].hasMinerals) {
                    finalClassValue = selectedGrade;
                } else if (selectedMineral) {
                    finalClassValue = `${selectedGrade} ${selectedMineral}`;
                }
            }
            finalClassInput.value = finalClassValue;
        }

        function initializeClassDropdowns() {
            const studentRows = document.querySelectorAll('tbody tr');
            studentRows.forEach(row => {
                const studentIdInput = row.querySelector('input[name="student_id"]');
                if (!studentIdInput) return;
                
                const studentId = studentIdInput.value;
                const currentClassSpan = row.querySelector('.badge')?.textContent.trim() || '';
                const parts = currentClassSpan.split(' ');
                
                const levelSelect = document.getElementById(`level_${studentId}`);
                const gradeFormSelect = document.getElementById(`grade_form_${studentId}`);
                const mineralSelect = document.getElementById(`mineral_class_${studentId}`);

                if (currentClassSpan.includes('ECD')) {
                    levelSelect.value = 'ECD';
                    gradeFormSelect.value = currentClassSpan;
                } else if (currentClassSpan.includes('Grade')) {
                    levelSelect.value = 'Primary';
                    gradeFormSelect.value = parts[0] + ' ' + parts[1];
                    if (parts.length > 2) {
                        mineralSelect.value = parts[2];
                    }
                } else if (currentClassSpan.includes('Form')) {
                    levelSelect.value = 'High School';
                    gradeFormSelect.value = parts[0] + ' ' + parts[1];
                    if (parts.length > 2) {
                        mineralSelect.value = parts[2];
                    }
                }

                updateClassDropdowns(studentId);
            });
        }
    </script>
</body>
</html>