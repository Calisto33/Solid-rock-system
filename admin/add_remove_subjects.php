<?php
session_start();
include '../config.php'; // Database connection

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Function to find the subjects table name
function findSubjectsTable($conn) {
    $possibleTables = ['subjects', 'table_subject', 'subject', 'tbl_subjects'];
    
    foreach ($possibleTables as $table) {
        $checkQuery = "SHOW TABLES LIKE '$table'";
        $result = $conn->query($checkQuery);
        if ($result && $result->num_rows > 0) {
            return $table;
        }
    }
    return null;
}

// Find the correct subjects table
$subjectsTable = findSubjectsTable($conn);
$subjects = [];
$subjectsError = '';

if ($subjectsTable) {
    // Try to determine the column names for the subjects table
    $columnsQuery = "SHOW COLUMNS FROM $subjectsTable";
    $columnsResult = $conn->query($columnsQuery);
    $subjectColumns = [];
    
    if ($columnsResult) {
        while ($column = $columnsResult->fetch_assoc()) {
            $subjectColumns[] = $column['Field'];
        }
    }
    
    // Find ID and name columns
    $idColumn = null;
    $nameColumn = null;
    
    // Look for ID column
    $possibleIdColumns = ['subject_id', 'id', 'subjectId'];
    foreach ($possibleIdColumns as $col) {
        if (in_array($col, $subjectColumns)) {
            $idColumn = $col;
            break;
        }
    }
    
    // Look for name column
    $possibleNameColumns = ['subject_name', 'name', 'subjectName', 'title'];
    foreach ($possibleNameColumns as $col) {
        if (in_array($col, $subjectColumns)) {
            $nameColumn = $col;
            break;
        }
    }
    
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

// Function to check if column exists in table
function columnExists($conn, $table, $column) {
    $query = "SHOW COLUMNS FROM $table LIKE '$column'";
    $result = $conn->query($query);
    return $result && $result->num_rows > 0;
}

// Check what columns exist in users and students tables
$userColumns = [];
$studentColumns = [];

// Get users table columns
$usersColumnsQuery = "SHOW COLUMNS FROM users";
$usersColumnsResult = $conn->query($usersColumnsQuery);
if ($usersColumnsResult) {
    while ($row = $usersColumnsResult->fetch_assoc()) {
        $userColumns[] = $row['Field'];
    }
}

// Get students table columns
$studentsColumnsQuery = "SHOW COLUMNS FROM students";
$studentsColumnsResult = $conn->query($studentsColumnsQuery);
if ($studentsColumnsResult) {
    while ($row = $studentsColumnsResult->fetch_assoc()) {
        $studentColumns[] = $row['Field'];
    }
}

// Build dynamic query based on available columns
$userSelectColumns = ['u.username', 'u.email'];
$studentSelectColumns = ['s.student_id', 's.user_id'];

// Add optional user columns if they exist
$optionalUserColumns = [
    'first_name' => 'u.first_name',
    'last_name' => 'u.last_name', 
    'phone_number' => 'u.phone_number',
    'address' => 'u.address',
    'created_at' => 'u.created_at'
];

foreach ($optionalUserColumns as $column => $selectAs) {
    if (in_array($column, $userColumns)) {
        $userSelectColumns[] = $selectAs;
    }
}

// Add optional student columns if they exist
$optionalStudentColumns = [
    'class' => 's.class',
    'course' => 's.course', 
    'year' => 's.year',
    'date_of_birth' => 's.date_of_birth',
    'gender' => 's.gender',
    'enrollment_date' => 's.enrollment_date'
];

foreach ($optionalStudentColumns as $column => $selectAs) {
    if (in_array($column, $studentColumns)) {
        $studentSelectColumns[] = $selectAs;
    }
}

// Combine all columns for the SELECT clause
$allSelectColumns = array_merge($studentSelectColumns, $userSelectColumns);
$selectClause = implode(', ', $allSelectColumns);

// Build the final query
$studentsQuery = "SELECT $selectClause
                  FROM students s
                  JOIN users u ON s.user_id = u.id
                  WHERE u.role = 'student'
                  ORDER BY " . (in_array('class', $studentColumns) ? 's.class ASC, ' : '') . "u.username ASC";

$studentsResult = $conn->query($studentsQuery);

// Function to get current subjects for a student
function getCurrentSubjects($conn, $student_id) {
    // Try different possible table names for student-subject relationships
    $possibleTables = ['student_subject', 'student_subjects', 'enrollments', 'tbl_student_subject'];
    
    foreach ($possibleTables as $table) {
        $checkQuery = "SHOW TABLES LIKE '$table'";
        $result = $conn->query($checkQuery);
        if ($result && $result->num_rows > 0) {
            $query = "SELECT subject_id FROM $table WHERE student_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $student_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $subjects = [];
            while ($row = $result->fetch_assoc()) {
                $subjects[] = $row['subject_id'];
            }
            $stmt->close();
            return $subjects;
        }
    }
    return []; // Return empty array if no table found
}

// Handle form submissions
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_class'])) {
        $student_id = $_POST['student_id'];
        $class = $_POST['class'];
        
        $updateClassQuery = "UPDATE students SET class = ? WHERE student_id = ?";
        $stmt = $conn->prepare($updateClassQuery);
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
        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);
        $email = trim($_POST['email']);
        $phone_number = trim($_POST['phone_number']);
        $address = trim($_POST['address']);
        $date_of_birth = $_POST['date_of_birth'];
        $gender = $_POST['gender'];
        $course = trim($_POST['course']);
        
        if (!empty($email)) {
            // Start transaction
            $conn->begin_transaction();
            
            try {
                // Build dynamic update query for users table
                $userUpdateFields = ['email = ?'];
                $userUpdateValues = [$email];
                $userUpdateTypes = 's';
                
                // Add optional fields if columns exist
                if (in_array('first_name', $userColumns) && !empty($first_name)) {
                    $userUpdateFields[] = 'first_name = ?';
                    $userUpdateValues[] = $first_name;
                    $userUpdateTypes .= 's';
                }
                if (in_array('last_name', $userColumns) && !empty($last_name)) {
                    $userUpdateFields[] = 'last_name = ?';
                    $userUpdateValues[] = $last_name;
                    $userUpdateTypes .= 's';
                }
                if (in_array('phone_number', $userColumns)) {
                    $userUpdateFields[] = 'phone_number = ?';
                    $userUpdateValues[] = $phone_number;
                    $userUpdateTypes .= 's';
                }
                if (in_array('address', $userColumns)) {
                    $userUpdateFields[] = 'address = ?';
                    $userUpdateValues[] = $address;
                    $userUpdateTypes .= 's';
                }
                
                // Add user_id for WHERE clause
                $userUpdateValues[] = $user_id;
                $userUpdateTypes .= 'i';
                
                // Update users table if we have fields to update
                if (count($userUpdateFields) > 0) {
                    $updateUserQuery = "UPDATE users SET " . implode(', ', $userUpdateFields) . " WHERE id = ?";
                    $stmt = $conn->prepare($updateUserQuery);
                    $stmt->bind_param($userUpdateTypes, ...$userUpdateValues);
                    $stmt->execute();
                    $stmt->close();
                }
                
                // Build dynamic update query for students table
                $studentUpdateFields = [];
                $studentUpdateValues = [];
                $studentUpdateTypes = '';
                
                if (in_array('date_of_birth', $studentColumns) && !empty($date_of_birth)) {
                    $studentUpdateFields[] = 'date_of_birth = ?';
                    $studentUpdateValues[] = $date_of_birth;
                    $studentUpdateTypes .= 's';
                }
                if (in_array('gender', $studentColumns) && !empty($gender)) {
                    $studentUpdateFields[] = 'gender = ?';
                    $studentUpdateValues[] = $gender;
                    $studentUpdateTypes .= 's';
                }
                if (in_array('course', $studentColumns)) {
                    $studentUpdateFields[] = 'course = ?';
                    $studentUpdateValues[] = $course;
                    $studentUpdateTypes .= 's';
                }
                
                // Update students table if we have fields to update
                if (count($studentUpdateFields) > 0) {
                    $studentUpdateValues[] = $student_id;
                    $studentUpdateTypes .= 's';
                    
                    $updateStudentQuery = "UPDATE students SET " . implode(', ', $studentUpdateFields) . " WHERE student_id = ?";
                    $stmt = $conn->prepare($updateStudentQuery);
                    $stmt->bind_param($studentUpdateTypes, ...$studentUpdateValues);
                    $stmt->execute();
                    $stmt->close();
                }
                
                $conn->commit();
                $message = "Student profile updated successfully.";
                $message_type = "success";
                
            } catch (Exception $e) {
                $conn->rollback();
                $message = "Error updating student profile: " . $e->getMessage();
                $message_type = "error";
            }
        } else {
            $message = "Email is required.";
            $message_type = "error";
        }
        
    } elseif (isset($_POST['create_subject'])) {
        $subject_name = trim($_POST['new_subject_name']);
        $subject_code = trim($_POST['subject_code']);
        $description = trim($_POST['subject_description']);
        $credits = intval($_POST['credits']);
        
        if (!empty($subject_name) && $subjectsTable) {
            // Check if subject already exists
            $checkQuery = "SELECT COUNT(*) as count FROM $subjectsTable WHERE subject_name = ?";
            $stmt = $conn->prepare($checkQuery);
            $stmt->bind_param("s", $subject_name);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            
            if ($row['count'] == 0) {
                // Insert new subject
                $insertQuery = "INSERT INTO $subjectsTable (subject_name, subject_code, description, credits) VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($insertQuery);
                $stmt->bind_param("sssi", $subject_name, $subject_code, $description, $credits);
                
                if ($stmt->execute()) {
                    $message = "Successfully created subject: $subject_name";
                    $message_type = "success";
                    
                    // Refresh subjects array
                    $subjectsQuery = "SELECT subject_id, subject_name FROM $subjectsTable ORDER BY subject_name ASC";
                    $subjectsResult = $conn->query($subjectsQuery);
                    $subjects = [];
                    if ($subjectsResult) {
                        while ($subject = $subjectsResult->fetch_assoc()) {
                            $subjects[] = $subject;
                        }
                    }
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
        $student_id = $_POST['student_id'];
        $subject_id = $_POST['subject_to_add'];
        
        if (!empty($subject_id)) {
            // Find the student-subject relationship table
            $relationshipTable = null;
            $possibleTables = ['student_subject', 'student_subjects', 'enrollments', 'tbl_student_subject'];
            
            foreach ($possibleTables as $table) {
                $checkQuery = "SHOW TABLES LIKE '$table'";
                $result = $conn->query($checkQuery);
                if ($result && $result->num_rows > 0) {
                    $relationshipTable = $table;
                    break;
                }
            }
            
            if ($relationshipTable) {
                // Check if student is already enrolled in this subject
                $checkQuery = "SELECT COUNT(*) as count FROM $relationshipTable WHERE student_id = ? AND subject_id = ?";
                $stmt = $conn->prepare($checkQuery);
                $stmt->bind_param("ii", $student_id, $subject_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                
                if ($row['count'] == 0) {
                    // Insert new subject enrollment
                    $insertQuery = "INSERT INTO $relationshipTable (student_id, subject_id) VALUES (?, ?)";
                    $stmt = $conn->prepare($insertQuery);
                    $stmt->bind_param("ii", $student_id, $subject_id);
                    
                    if ($stmt->execute()) {
                        // Get subject name for success message
                        $subjectNameQuery = "SELECT subject_name FROM $subjectsTable WHERE subject_id = ?";
                        $stmt2 = $conn->prepare($subjectNameQuery);
                        $stmt2->bind_param("i", $subject_id);
                        $stmt2->execute();
                        $subjectResult = $stmt2->get_result();
                        $subjectData = $subjectResult->fetch_assoc();
                        $subjectName = $subjectData['subject_name'] ?? 'Subject';
                        
                        $message = "Successfully added $subjectName to student $student_id.";
                        $message_type = "success";
                        $stmt2->close();
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
                $message = "Error: Could not find student-subject relationship table.";
                $message_type = "error";
            }
        } else {
            $message = "Please select a subject to add.";
            $message_type = "error";
        }
        
    } elseif (isset($_POST['update_subjects'])) {
        $student_id = $_POST['student_id'];
        $subject_ids = isset($_POST['subjects']) ? $_POST['subjects'] : [];

        // Find the student-subject relationship table
        $relationshipTable = null;
        $possibleTables = ['student_subject', 'student_subjects', 'enrollments', 'tbl_student_subject'];
        
        foreach ($possibleTables as $table) {
            $checkQuery = "SHOW TABLES LIKE '$table'";
            $result = $conn->query($checkQuery);
            if ($result && $result->num_rows > 0) {
                $relationshipTable = $table;
                break;
            }
        }
        
        if ($relationshipTable) {
            // Start transaction
            $conn->begin_transaction();
            
            try {
                // Remove existing subjects
                $deleteQuery = "DELETE FROM $relationshipTable WHERE student_id = ?";
                $stmt = $conn->prepare($deleteQuery);
                $stmt->bind_param("i", $student_id);
                $stmt->execute();
                $stmt->close();

                // Insert new subjects
                if (!empty($subject_ids)) {
                    $insertQuery = "INSERT INTO $relationshipTable (student_id, subject_id) VALUES (?, ?)";
                    $stmt = $conn->prepare($insertQuery);
                    
                    foreach ($subject_ids as $subject_id) {
                        $stmt->bind_param("ii", $student_id, $subject_id);
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
            $message = "Error: Could not find student-subject relationship table.";
            $message_type = "error";
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <!-- Favicon - matching main site -->
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

        /* Alert Messages */
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

        /* Header Styles */
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

        /* Main Content Styles */
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

        .card:hover {
            transform: none;
            box-shadow: var(--shadow);
        }

        .card-header {
            background: linear-gradient(to right, var(--primary-color), var(--primary-dark));
            color: white;
            padding: 1rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-shrink: 0;
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

        /* Table Styles */
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

        /* Form Elements */
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

        /* Subject Assignment Styles */
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

        /* Modal Styles */
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

        /* Student Info */
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

        /* Footer */
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

        .student-details {
            background-color: #f8f9fa;
            border-left: 4px solid var(--primary-color);
            margin: 0.5rem 0;
            border-radius: 0 6px 6px 0;
            overflow: hidden;
        }
        
        .details-header {
            background-color: var(--primary-color);
            color: white;
            padding: 0.5rem 0.8rem;
            font-weight: 500;
            font-size: 0.85rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
        }
        
        .details-content {
            padding: 0.8rem;
            display: none;
            background-color: white;
            border-top: 1px solid rgba(0,0,0,0.1);
        }
        
        .details-content.active {
            display: block;
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

        /* Responsive Design */
        @media screen and (max-width: 768px) {
            .header-content {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
            
            .header h2 {
                font-size: 1.2rem;
            }

            .table th,
            .table td {
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

            .subject-form select,
            .subject-form button {
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
            
            .table th,
            .table td {
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
            <div class="alert alert-<?php echo $message_type; ?>">
                <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($subjectsError)): ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i>
                <div>
                    <strong>Database Configuration Issue:</strong> <?php echo htmlspecialchars($subjectsError); ?>
                    <br><small>Subject management features may not work properly until this is resolved.</small>
                </div>
            </div>
        <?php endif; ?>

        <div class="management-actions">
            <button onclick="openAddSubjectModal()" class="btn-add">
                <i class="fas fa-plus-circle"></i> Add New Subject to System
            </button>
            <div class="description">
                Add subjects to the system that can then be assigned to students
            </div>
        </div>
            <div class="card-header">
                <h3><i class="fas fa-user-graduate"></i> Assign Classes & Subjects</h3>
                <div style="display: flex; gap: 1rem; align-items: center;">
                    <?php if (!empty($subjects)): ?>
                        <span class="badge" style="background-color: var(--success-color);">
                            <?php echo count($subjects); ?> Subjects Available
                        </span>
                    <?php endif; ?>
                    <a href="add_subject.php" class="link-btn" style="background-color: var(--warning-color); border-color: var(--warning-color);">
                        <i class="fas fa-plus"></i> Add New Subject
                    </a>
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
                                    <th>Year</th>
                                    <th>Current Subjects</th>
                                    <th>Update Class</th>
                                    <th>Assign Subjects</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($student = $studentsResult->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($student['student_id'] ?? ''); ?></td>
                                        <td>
                                            <div>
                                                <strong><?php echo htmlspecialchars(($student['first_name'] ?? '') . ' ' . ($student['last_name'] ?? '')); ?></strong>
                                                <br><small>@<?php echo htmlspecialchars($student['username'] ?? ''); ?></small>
                                            </div>
                                            <div style="margin-top: 0.3rem;">
                                                <button onclick="openStudentDetails('<?php echo $student['student_id']; ?>', <?php echo htmlspecialchars(json_encode($student), ENT_QUOTES); ?>)" class="btn-view-details">
                                                    <i class="fas fa-eye"></i> View Details
                                                </button>
                                                <button onclick="openEditProfile('<?php echo $student['student_id']; ?>', <?php echo htmlspecialchars(json_encode($student), ENT_QUOTES); ?>)" class="btn-edit-profile">
                                                    <i class="fas fa-edit"></i> Edit Profile
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
                                        </td>
                                        <td><?php echo !empty($student['course']) ? htmlspecialchars($student['course']) : 'N/A'; ?></td>
                                        <td><?php echo !empty($student['year']) ? htmlspecialchars($student['year']) : 'N/A'; ?></td>
                                        <td>
                                            <?php 
                                            $currentSubjects = getCurrentSubjects($conn, $student['student_id']);
                                            if (!empty($currentSubjects) && !empty($subjects)): 
                                                $enrolledSubjects = [];
                                                foreach ($subjects as $subject) {
                                                    if (in_array($subject['subject_id'], $currentSubjects)) {
                                                        $enrolledSubjects[] = $subject['subject_name'];
                                                    }
                                                }
                                                if (!empty($enrolledSubjects)):
                                            ?>
                                                <div class="current-subjects">
                                                    <?php foreach ($enrolledSubjects as $subjectName): ?>
                                                        <span class="subject-badge">
                                                            <?php echo htmlspecialchars($subjectName); ?>
                                                        </span>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php else: ?>
                                                <span style="color: var(--text-light); font-style: italic;">No subjects assigned</span>
                                            <?php endif; ?>
                                            <?php else: ?>
                                                <span style="color: var(--text-light); font-style: italic;">No subjects assigned</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <td>
    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" class="class-form" style="display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center; min-width: 350px;">
        
        <input type="hidden" name="student_id" value="<?php echo $student['student_id']; ?>">
        
        <input type="hidden" name="class" id="final_class_<?php echo $student['student_id']; ?>">

        <select id="level_<?php echo $student['student_id']; ?>" onchange="updateClassDropdowns('<?php echo $student['student_id']; ?>')" required>
            <option value="">Select Level</option>
            <option value="ECD">ECD</option>
            <option value="Primary">Primary</option>
            <option value="High School">High School</option>
        </select>

        <select id="grade_form_<?php echo $student['student_id']; ?>" onchange="updateClassDropdowns('<?php echo $student['student_id']; ?>')" required style="display: none;">
            <option value="">Select Grade/Form</option>
        </select>

        <select id="mineral_class_<?php echo $student['student_id']; ?>" onchange="updateClassDropdowns('<?php echo $student['student_id']; ?>')" required style="display: none;">
            <option value="">Select Mineral</option>
            <option value="Gold">Gold</option>
            <option value="Silver">Silver</option>
            <option value="Platinum">Platinum</option>
            <option value="Diamond">Diamond</option>
            <option value="Bronze">Bronze</option>
        </select>

        <button type="submit" name="update_class" class="btn-update">
            <i class="fas fa-sync-alt"></i> Update
        </button>
    </form>
</td>
                                        <td>
                                            <?php if (!empty($subjects)): ?>
                                                <div class="subject-form">
                                                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" style="display: flex; gap: 0.5rem; align-items: center;">
                                                        <input type="hidden" name="student_id" value="<?php echo $student['student_id'] ?? ''; ?>">
                                                        <select name="subject_to_add" style="min-width: 150px;">
                                                            <option value="">Select Subject</option>
                                                            <?php 
                                                            $currentSubjects = getCurrentSubjects($conn, $student['student_id']);
                                                            foreach ($subjects as $subject): 
                                                                if (!in_array($subject['subject_id'], $currentSubjects)):
                                                            ?>
                                                                <option value="<?php echo $subject['subject_id']; ?>">
                                                                    <?php echo htmlspecialchars($subject['subject_name']); ?>
                                                                </option>
                                                            <?php 
                                                                endif;
                                                            endforeach; 
                                                            ?>
                                                        </select>
                                                        <button type="submit" name="add_subject" class="btn-add">
                                                            <i class="fas fa-plus"></i> Add
                                                        </button>
                                                    </form>
                                                    <button onclick="openModal('<?php echo $student['student_id'] ?? 0; ?>', '<?php echo htmlspecialchars($student['username'] ?? '', ENT_QUOTES); ?>')" class="btn-manage">
                                                        <i class="fas fa-book"></i> View/Remove
                                                    </button>
                                                </div>
                                            <?php else: ?>
                                                <button disabled class="btn-manage" title="No subjects available">
                                                    <i class="fas fa-book"></i> No Subjects
                                                </button>
                                            <?php endif; ?>
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
                <button type="button" class="close-btn" onclick="closeStudentDetailsModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="student-info">
                <p><strong>Student:</strong> <span id="detailsStudentName"></span></p>
                <p><strong>Student ID:</strong> <span id="detailsStudentId"></span></p>
                <p><strong>Username:</strong> <span id="detailsUsername"></span></p>
            </div>
            
            <div class="details-grid" id="studentDetailsGrid">
                <!-- Details will be populated by JavaScript -->
            </div>
        </div>
    </div>

    <!-- Edit Student Profile Modal -->
    <div class="modal" id="editProfileModal">
        <div class="modal-content" style="max-width: 800px;">
            <div class="modal-header">
                <h4><i class="fas fa-user-edit"></i> Edit Student Profile</h4>
                <button type="button" class="close-btn" onclick="closeEditProfileModal()">
                    <i class="fas fa-times"></i>
                </button>
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
                        <?php if (in_array('first_name', $userColumns)): ?>
                            <input type="text" name="first_name" id="editFirstName" required>
                        <?php else: ?>
                            <input type="text" value="Not available in database" disabled>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group-inline">
                        <label>Last Name <?php echo in_array('last_name', $userColumns) ? '*' : '(Not Available)'; ?></label>
                        <?php if (in_array('last_name', $userColumns)): ?>
                            <input type="text" name="last_name" id="editLastName" required>
                        <?php else: ?>
                            <input type="text" value="Not available in database" disabled>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group-inline">
                        <label>Email *</label>
                        <input type="email" name="email" id="editEmail" required>
                    </div>
                    
                    <div class="form-group-inline">
                        <label>Phone Number <?php echo !in_array('phone_number', $userColumns) ? '(Not Available)' : ''; ?></label>
                        <?php if (in_array('phone_number', $userColumns)): ?>
                            <input type="text" name="phone_number" id="editPhoneNumber">
                        <?php else: ?>
                            <input type="text" value="Not available in database" disabled>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group-inline">
                        <label>Date of Birth <?php echo !in_array('date_of_birth', $studentColumns) ? '(Not Available)' : ''; ?></label>
                        <?php if (in_array('date_of_birth', $studentColumns)): ?>
                            <input type="date" name="date_of_birth" id="editDateOfBirth">
                        <?php else: ?>
                            <input type="text" value="Not available in database" disabled>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group-inline">
                        <label>Gender <?php echo !in_array('gender', $studentColumns) ? '(Not Available)' : ''; ?></label>
                        <?php if (in_array('gender', $studentColumns)): ?>
                            <select name="gender" id="editGender">
                                <option value="">Select Gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        <?php else: ?>
                            <input type="text" value="Not available in database" disabled>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group-inline">
                        <label>Course <?php echo !in_array('course', $studentColumns) ? '(Not Available)' : ''; ?></label>
                        <?php if (in_array('course', $studentColumns)): ?>
                            <input type="text" name="course" id="editCourse">
                        <?php else: ?>
                            <input type="text" value="Not available in database" disabled>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group-inline" style="grid-column: 1 / -1;">
                        <label>Address <?php echo !in_array('address', $userColumns) ? '(Not Available)' : ''; ?></label>
                        <?php if (in_array('address', $userColumns)): ?>
                            <textarea name="address" rows="3" id="editAddress"></textarea>
                        <?php else: ?>
                            <textarea disabled>Not available in database</textarea>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="modal-actions">
                    <button type="button" onclick="closeEditProfileModal()" class="btn-cancel">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" name="update_student_profile" class="btn-update">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Subject Modal -->
    <div class="modal" id="addSubjectModal">
        <div class="modal-content">
            <div class="modal-header">
                <h4><i class="fas fa-plus-circle"></i> Add New Subject</h4>
                <button type="button" class="close-btn" onclick="closeAddSubjectModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
                <div style="margin-bottom: 1.5rem;">
                    <label for="new_subject_name" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Subject Name <span style="color: #e74c3c;">*</span></label>
                    <input type="text" id="new_subject_name" name="new_subject_name" required 
                           style="width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 8px; font-size: 1rem;"
                           placeholder="e.g., Advanced Mathematics, World History">
                </div>
                
                <div style="margin-bottom: 1.5rem;">
                    <label for="subject_code" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Subject Code (Optional)</label>
                    <input type="text" id="subject_code" name="subject_code" 
                           style="width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 8px; font-size: 1rem;"
                           placeholder="e.g., MATH201, HIST101">
                </div>
                
                <div style="margin-bottom: 1.5rem;">
                    <label for="subject_description" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Description (Optional)</label>
                    <textarea id="subject_description" name="subject_description" rows="3"
                              style="width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 8px; font-size: 1rem; resize: vertical;"
                              placeholder="Brief description of the subject..."></textarea>
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
                    <button type="button" onclick="closeAddSubjectModal()" class="btn-cancel">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" name="create_subject" class="btn-update">
                        <i class="fas fa-save"></i> Create Subject
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Subject Management Modal -->
    <div class="modal" id="subjectModal">
        <div class="modal-content">
            <div class="modal-header">
                <h4><i class="fas fa-book-open"></i> Manage Student Subjects</h4>
                <button type="button" class="close-btn" onclick="closeModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="student-info" id="studentInfo">
                <p><strong>Student:</strong> <span id="studentName"></span></p>
                <p><strong>Student ID:</strong> <span id="studentIdDisplay"></span></p>
            </div>
            
            <?php if (!empty($subjects)): ?>
                <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
                    <input type="hidden" name="student_id" id="student_id">
                    
                    <div class="subjects-container">
                        <?php foreach ($subjects as $subject): ?>
                            <div class="checkbox-group">
                                <input type="checkbox" name="subjects[]" value="<?php echo $subject['subject_id']; ?>" 
                                       id="subject_<?php echo $subject['subject_id']; ?>">
                                <label for="subject_<?php echo $subject['subject_id']; ?>">
                                    <?php echo htmlspecialchars($subject['subject_name']); ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="modal-actions">
                        <button type="button" onclick="closeModal()" class="btn-cancel">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <button type="submit" name="update_subjects" class="btn-update">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                    </div>
                </form>
            <?php else: ?>
                <div class="no-subjects-message">
                    <i class="fas fa-exclamation-circle" style="font-size: 2rem; margin-bottom: 1rem; color: var(--warning-color);"></i>
                    <p>No subjects are available in the system.</p>
                    <p>Please create subjects first before assigning them to students.</p>
                </div>
                
                <div class="modal-actions">
                    <button type="button" onclick="closeModal()" class="btn-cancel">
                        <i class="fas fa-times"></i> Close
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; <?php echo date("Y"); ?> Wisetech College Student Management Portal</p>
    </footer>

    <script>
        function openStudentDetails(studentId, studentData) {
            // Populate student info
            document.getElementById("detailsStudentName").textContent = 
                (studentData.first_name || '') + ' ' + (studentData.last_name || '');
            document.getElementById("detailsStudentId").textContent = studentId;
            document.getElementById("detailsUsername").textContent = studentData.username || '';
            
            // Populate details grid
            const detailsGrid = document.getElementById("studentDetailsGrid");
            detailsGrid.innerHTML = `
                <div class="detail-item">
                    <span class="detail-label">Full Name</span>
                    <span class="detail-value">${(studentData.first_name || '') + ' ' + (studentData.last_name || '') || 'Not provided'}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Email</span>
                    <span class="detail-value">${studentData.email || 'Not provided'}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Phone Number</span>
                    <span class="detail-value">${studentData.phone_number || 'Not provided'}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Date of Birth</span>
                    <span class="detail-value">${studentData.date_of_birth || 'Not provided'}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Gender</span>
                    <span class="detail-value">${studentData.gender || 'Not specified'}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Class</span>
                    <span class="detail-value">${studentData.class || 'Not assigned'}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Course</span>
                    <span class="detail-value">${studentData.course || 'Not specified'}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Year</span>
                    <span class="detail-value">${studentData.year || 'Not specified'}</span>
                </div>
                <div class="detail-item" style="grid-column: 1 / -1;">
                    <span class="detail-label">Address</span>
                    <span class="detail-value">${studentData.address || 'Not provided'}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Enrollment Date</span>
                    <span class="detail-value">${studentData.enrollment_date || studentData.created_at || 'Unknown'}</span>
                </div>
            `;
            
            // Show modal
            document.getElementById("studentDetailsModal").style.display = "block";
            setTimeout(() => {
                document.getElementById("studentDetailsModal").classList.add("active");
            }, 10);
        }
        
        function closeStudentDetailsModal() {
            const modal = document.getElementById("studentDetailsModal");
            modal.classList.remove("active");
            setTimeout(() => {
                modal.style.display = "none";
            }, 300);
        }
        
        function openEditProfile(studentId, studentData) {
            // Populate student info
            document.getElementById("editStudentName").textContent = 
                (studentData.first_name || '') + ' ' + (studentData.last_name || '');
            document.getElementById("editStudentId").textContent = studentId;
            
            // Populate form fields
            document.getElementById("editFormStudentId").value = studentId;
            document.getElementById("editFormUserId").value = studentData.user_id || '';
            
            // Populate form inputs if they exist
            const firstName = document.getElementById("editFirstName");
            if (firstName) firstName.value = studentData.first_name || '';
            
            const lastName = document.getElementById("editLastName");
            if (lastName) lastName.value = studentData.last_name || '';
            
            document.getElementById("editEmail").value = studentData.email || '';
            
            const phoneNumber = document.getElementById("editPhoneNumber");
            if (phoneNumber) phoneNumber.value = studentData.phone_number || '';
            
            const dateOfBirth = document.getElementById("editDateOfBirth");
            if (dateOfBirth) dateOfBirth.value = studentData.date_of_birth || '';
            
            const gender = document.getElementById("editGender");
            if (gender) gender.value = studentData.gender || '';
            
            const course = document.getElementById("editCourse");
            if (course) course.value = studentData.course || '';
            
            const address = document.getElementById("editAddress");
            if (address) address.value = studentData.address || '';
            
            // Show modal
            document.getElementById("editProfileModal").style.display = "block";
            setTimeout(() => {
                document.getElementById("editProfileModal").classList.add("active");
            }, 10);
        }
        
        function closeEditProfileModal() {
            const modal = document.getElementById("editProfileModal");
            modal.classList.remove("active");
            setTimeout(() => {
                modal.style.display = "none";
            }, 300);
        }
        
        function openAddSubjectModal() {
            document.getElementById("addSubjectModal").style.display = "block";
            
            // Add active class after a small delay for animation
            setTimeout(() => {
                document.getElementById("addSubjectModal").classList.add("active");
            }, 10);
        }
        
        function closeAddSubjectModal() {
            const modal = document.getElementById("addSubjectModal");
            modal.classList.remove("active");
            
            // Hide the modal after animation completes
            setTimeout(() => {
                modal.style.display = "none";
                // Reset form
                document.getElementById("new_subject_name").value = "";
                document.getElementById("subject_code").value = "";
                document.getElementById("subject_description").value = "";
                document.getElementById("credits").value = "3";
            }, 300);
        }
        
        function openModal(studentId, studentName) {
            document.getElementById("student_id").value = studentId;
            document.getElementById("studentName").textContent = studentName;
            document.getElementById("studentIdDisplay").textContent = studentId;
            
            // Clear all checkboxes first
            const checkboxes = document.querySelectorAll('input[name="subjects[]"]');
            checkboxes.forEach(cb => cb.checked = false);
            
            // Show modal
            document.getElementById("subjectModal").style.display = "block";
            
            // Add active class after a small delay for animation
            setTimeout(() => {
                document.getElementById("subjectModal").classList.add("active");
            }, 10);
            
            // Only fetch current subjects if subjects are available
            <?php if (!empty($subjects)): ?>
            // Fetch current subjects for this student
            fetch(`<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>?get_subjects=1&student_id=${studentId}`)
                .then(response => response.json())
                .then(currentSubjects => {
                    // Check the boxes for subjects this student is enrolled in
                    currentSubjects.forEach(subjectId => {
                        const checkbox = document.getElementById(`subject_${subjectId}`);
                        if (checkbox) {
                            checkbox.checked = true;
                        }
                    });
                })
                .catch(error => {
                    console.error('Error fetching subjects:', error);
                });
            <?php endif; ?>
        }
        
        function closeModal() {
            const modal = document.getElementById("subjectModal");
            modal.classList.remove("active");
            
            // Hide the modal after animation completes
            setTimeout(() => {
                modal.style.display = "none";
            }, 300);
        }
        
        // Close modal if user clicks outside the modal content
        window.onclick = function(event) {
            const subjectModal = document.getElementById("subjectModal");
            const addModal = document.getElementById("addSubjectModal");
            const detailsModal = document.getElementById("studentDetailsModal");
            const editModal = document.getElementById("editProfileModal");
            
            if (event.target == subjectModal) {
                closeModal();
            }
            if (event.target == addModal) {
                closeAddSubjectModal();
            }
            if (event.target == detailsModal) {
                closeStudentDetailsModal();
            }
            if (event.target == editModal) {
                closeEditProfileModal();
            }
        };
        
        // Auto-hide alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    setTimeout(() => {
                        alert.remove();
                    }, 300);
                }, 5000);
            });
        });

    const classStructure = {
        'ECD': {
            grades: ['ECD A', 'ECD B'],
            hasMinerals: false
        },
        'Primary': {
            grades: ['Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6', 'Grade 7'],
            hasMinerals: true
        },
        'High School': {
            grades: ['Form 1', 'Form 2', 'Form 3', 'Form 4', 'Form 5', 'Form 6'],
            hasMinerals: true
        }
    };

    function updateClassDropdowns(studentId) {
        // Get the dropdowns and hidden input for the specific student row
        const levelSelect = document.getElementById(`level_${studentId}`);
        const gradeFormSelect = document.getElementById(`grade_form_${studentId}`);
        const mineralSelect = document.getElementById(`mineral_class_${studentId}`);
        const finalClassInput = document.getElementById(`final_class_${studentId}`);

        const selectedLevel = levelSelect.value;
        const selectedGrade = gradeFormSelect.value;
        const selectedMineral = mineralSelect.value;
        
        // --- Logic for the Grade/Form Dropdown ---
        // Clear previous options and hide it
        const currentGradeValue = gradeFormSelect.value;
        gradeFormSelect.innerHTML = '<option value="">Select Grade/Form</option>';
        gradeFormSelect.style.display = 'none';

        if (selectedLevel && classStructure[selectedLevel]) {
            const levelData = classStructure[selectedLevel];
            // Add the new grade/form options
            levelData.grades.forEach(grade => {
                const option = new Option(grade, grade);
                gradeFormSelect.add(option);
            });
            // Restore previous selection if it exists in the new list
            if (levelData.grades.includes(currentGradeValue)) {
                gradeFormSelect.value = currentGradeValue;
            }
            // Show the dropdown
            gradeFormSelect.style.display = 'inline-block';
        }

        // --- Logic for the Mineral Dropdown ---
        if (selectedLevel && classStructure[selectedLevel].hasMinerals && selectedGrade) {
            mineralSelect.style.display = 'inline-block';
        } else {
            mineralSelect.style.display = 'none';
            mineralSelect.value = ''; // Reset mineral if not applicable
        }

        // --- Logic to set the final class value for submission ---
        finalClassInput.value = ''; // Clear final value
        if (selectedGrade) {
            if (classStructure[selectedLevel].hasMinerals) {
                // For Primary/High School, require a mineral to be selected
                if (selectedMineral) {
                    finalClassInput.value = `${selectedGrade} ${selectedMineral}`;
                }
            } else {
                // For ECD, the Grade/Form is the final class name
                finalClassInput.value = selectedGrade;
            }
        }
    }
    </script>
</body>
</html>