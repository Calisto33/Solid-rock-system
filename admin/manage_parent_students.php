<?php
session_start();
include '../config.php';

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

// Check if parent_id is provided
if (!isset($_GET['parent_id']) || empty($_GET['parent_id'])) {
    $_SESSION['error_message'] = "Parent ID is required.";
    header("Location: parents.php");
    exit();
}

$parent_id = intval($_GET['parent_id']);

<<<<<<< HEAD
// Fetch parent information - simplified to match actual database structure
$parentQuery = "SELECT p.*, u.username, u.email FROM parents p JOIN users u ON p.user_id = u.id WHERE p.parent_id = ?";
=======
// FIRST: Check if student_parent_relationships table exists
$tableCheck = $conn->query("SHOW TABLES LIKE 'student_parent_relationships'");
$relationshipTableExists = $tableCheck->num_rows > 0;

// Check what columns exist in the parents table
$parentsColumnsQuery = "SHOW COLUMNS FROM parents";
$parentsColumnsResult = $conn->query($parentsColumnsQuery);
$existingParentColumns = [];

if ($parentsColumnsResult) {
    while ($row = $parentsColumnsResult->fetch_assoc()) {
        $existingParentColumns[] = $row['Field'];
    }
}

// Check what columns exist in the students table and find primary key
$studentsColumnsQuery = "SHOW COLUMNS FROM students";
$studentsColumnsResult = $conn->query($studentsColumnsQuery);
$studentsPrimaryKey = 'student_id'; // Default assumption
$existingStudentColumns = [];

if ($studentsColumnsResult) {
    while ($row = $studentsColumnsResult->fetch_assoc()) {
        $existingStudentColumns[] = $row['Field'];
        if ($row['Key'] === 'PRI') {
            $studentsPrimaryKey = $row['Field'];
        }
    }
}

// Build parent query based on available columns
$parentSelectColumns = ['p.parent_id', 'p.user_id'];
$optionalParentColumns = ['phone_number', 'relationship', 'address', 'first_name', 'last_name'];
foreach ($optionalParentColumns as $col) {
    if (in_array($col, $existingParentColumns)) {
        $parentSelectColumns[] = "p.$col";
    }
}
$parentSelectClause = implode(', ', $parentSelectColumns);

// Fetch parent information
$parentQuery = "SELECT $parentSelectClause, u.username, u.email FROM parents p LEFT JOIN users u ON p.user_id = u.id WHERE p.parent_id = ?";
>>>>>>> b291daf7f49078bb0cccb1439969ad4a74e2db38
$stmt = $conn->prepare($parentQuery);
$stmt->bind_param("i", $parent_id);
$stmt->execute();
$parentResult = $stmt->get_result();
$parent = $parentResult->fetch_assoc();
$stmt->close();

if (!$parent) {
    $_SESSION['error_message'] = "Parent not found.";
    header("Location: parents.php");
    exit();
}

<<<<<<< HEAD
// Get currently assigned students - using correct table name and column references
$assignedStudentsQuery = "
    SELECT s.student_id, s.first_name, s.last_name, s.username, spr.created_at as assigned_date
    FROM student_parent_relationships spr
    JOIN students s ON spr.student_id = s.student_id
    WHERE spr.parent_id = ?
    ORDER BY s.first_name, s.last_name";
=======
// Initialize variables for students
$assignedStudents = [];
$availableStudents = [];
>>>>>>> b291daf7f49078bb0cccb1439969ad4a74e2db38

if ($relationshipTableExists) {
    // Get currently assigned students using the correct table name
    $assignedStudentsQuery = "
        SELECT s.$studentsPrimaryKey as id, s.$studentsPrimaryKey as student_id, s.first_name, s.last_name, s.username, spr.created_at as assigned_date
        FROM student_parent_relationships spr
        JOIN students s ON spr.student_id = s.$studentsPrimaryKey
        WHERE spr.parent_id = ?
        ORDER BY s.first_name, s.last_name";

<<<<<<< HEAD
// Get all available students (not assigned to this parent) - using correct table name and column references
$availableStudentsQuery = "
    SELECT s.student_id, s.first_name, s.last_name, s.username
    FROM students s
    WHERE s.student_id NOT IN (
        SELECT spr.student_id 
        FROM student_parent_relationships spr 
        WHERE spr.parent_id = ?
    )
    ORDER BY s.first_name, s.last_name";
=======
    $stmt = $conn->prepare($assignedStudentsQuery);
    $stmt->bind_param("i", $parent_id);
    $stmt->execute();
    $assignedStudentsResult = $stmt->get_result();
    while ($row = $assignedStudentsResult->fetch_assoc()) {
        $assignedStudents[] = $row;
    }
    $stmt->close();
>>>>>>> b291daf7f49078bb0cccb1439969ad4a74e2db38

    // Get all available students (not assigned to this parent)
    $availableStudentsQuery = "
        SELECT s.$studentsPrimaryKey as id, s.$studentsPrimaryKey as student_id, s.first_name, s.last_name, s.username
        FROM students s
        WHERE s.$studentsPrimaryKey NOT IN (
            SELECT spr.student_id 
            FROM student_parent_relationships spr 
            WHERE spr.parent_id = ?
        )
        ORDER BY s.first_name, s.last_name";

    $stmt = $conn->prepare($availableStudentsQuery);
    $stmt->bind_param("i", $parent_id);
    $stmt->execute();
    $availableStudentsResult = $stmt->get_result();
    while ($row = $availableStudentsResult->fetch_assoc()) {
        $availableStudents[] = $row;
    }
    $stmt->close();
} else {
    // Fallback: Use simple parent-student relationship from parents table
    if (in_array('student_id', $existingParentColumns)) {
        // Check if this parent has a student assigned
        $currentStudentQuery = "SELECT student_id FROM parents WHERE parent_id = ? AND student_id IS NOT NULL AND student_id != ''";
        $stmt = $conn->prepare($currentStudentQuery);
        $stmt->bind_param("i", $parent_id);
        $stmt->execute();
        $currentStudentResult = $stmt->get_result();
        $currentStudent = $currentStudentResult->fetch_assoc();
        $stmt->close();
        
        if ($currentStudent) {
            // Get assigned student details
            $studentQuery = "SELECT $studentsPrimaryKey as id, $studentsPrimaryKey as student_id, first_name, last_name, username FROM students WHERE $studentsPrimaryKey = ?";
            $stmt = $conn->prepare($studentQuery);
            $stmt->bind_param("s", $currentStudent['student_id']);
            $stmt->execute();
            $studentResult = $stmt->get_result();
            if ($studentData = $studentResult->fetch_assoc()) {
                $studentData['assigned_date'] = 'Unknown'; // No timestamp in simple mode
                $assignedStudents[] = $studentData;
            }
            $stmt->close();
        }
        
        // Get available students (all students not assigned to this parent)
        $currentStudentId = $currentStudent['student_id'] ?? null;
        if ($currentStudentId) {
            $availableQuery = "SELECT $studentsPrimaryKey as id, $studentsPrimaryKey as student_id, first_name, last_name, username FROM students WHERE $studentsPrimaryKey != ? ORDER BY first_name, last_name";
            $stmt = $conn->prepare($availableQuery);
            $stmt->bind_param("s", $currentStudentId);
        } else {
            $availableQuery = "SELECT $studentsPrimaryKey as id, $studentsPrimaryKey as student_id, first_name, last_name, username FROM students ORDER BY first_name, last_name";
            $stmt = $conn->prepare($availableQuery);
        }
        $stmt->execute();
        $availableResult = $stmt->get_result();
        while ($row = $availableResult->fetch_assoc()) {
            $availableStudents[] = $row;
        }
        $stmt->close();
    } else {
        // No student relationship capability
        $availableQuery = "SELECT $studentsPrimaryKey as id, $studentsPrimaryKey as student_id, first_name, last_name, username FROM students ORDER BY first_name, last_name";
        $availableResult = $conn->query($availableQuery);
        if ($availableResult) {
            while ($row = $availableResult->fetch_assoc()) {
                $availableStudents[] = $row;
            }
        }
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'assign' && isset($_POST['student_ids'])) {
<<<<<<< HEAD
            // Assign selected students - using correct table name and student_id references
            $student_ids = $_POST['student_ids'];
            $insertQuery = "INSERT IGNORE INTO student_parent_relationships (parent_id, student_id, created_at) VALUES (?, ?, NOW())";
            $stmt = $conn->prepare($insertQuery);
            
            $success_count = 0;
            foreach ($student_ids as $student_id) {
                $stmt->bind_param("is", $parent_id, $student_id); // Changed to "is" since student_id might be varchar
                if ($stmt->execute()) {
                    $success_count++;
=======
            $student_ids = $_POST['student_ids'];
            $success_count = 0;
            
            if ($relationshipTableExists) {
                // Use relationship table
                $insertQuery = "INSERT IGNORE INTO student_parent_relationships (parent_id, student_id, relationship_type, created_at) VALUES (?, ?, 'other', NOW())";
                $stmt = $conn->prepare($insertQuery);
                
                foreach ($student_ids as $student_id) {
                    $stmt->bind_param("is", $parent_id, $student_id);
                    if ($stmt->execute()) {
                        $success_count++;
                    }
                }
                $stmt->close();
            } else {
                // Fallback: Update parents table (only one student allowed)
                if (in_array('student_id', $existingParentColumns) && count($student_ids) > 0) {
                    $updateQuery = "UPDATE parents SET student_id = ? WHERE parent_id = ?";
                    $stmt = $conn->prepare($updateQuery);
                    $stmt->bind_param("si", $student_ids[0], $parent_id);
                    if ($stmt->execute()) {
                        $success_count = 1;
                    }
                    $stmt->close();
>>>>>>> b291daf7f49078bb0cccb1439969ad4a74e2db38
                }
            }
            
            if ($success_count > 0) {
                $_SESSION['success_message'] = "$success_count student(s) assigned successfully!";
            } else {
                $_SESSION['error_message'] = "Failed to assign students.";
            }
            
        } elseif ($_POST['action'] == 'remove' && isset($_POST['remove_student_id'])) {
<<<<<<< HEAD
            // Remove student assignment - using correct table name and student_id references
            $student_id = $_POST['remove_student_id']; // Keep as string since it might be varchar
            $deleteQuery = "DELETE FROM student_parent_relationships WHERE parent_id = ? AND student_id = ?";
            $stmt = $conn->prepare($deleteQuery);
            $stmt->bind_param("is", $parent_id, $student_id); // Changed to "is"
=======
            $student_id = $_POST['remove_student_id'];
>>>>>>> b291daf7f49078bb0cccb1439969ad4a74e2db38
            
            if ($relationshipTableExists) {
                // Remove from relationship table
                $deleteQuery = "DELETE FROM student_parent_relationships WHERE parent_id = ? AND student_id = ?";
                $stmt = $conn->prepare($deleteQuery);
                $stmt->bind_param("is", $parent_id, $student_id);
                $success = $stmt->execute();
                $stmt->close();
            } else {
                // Fallback: Clear student_id in parents table
                if (in_array('student_id', $existingParentColumns)) {
                    $updateQuery = "UPDATE parents SET student_id = NULL WHERE parent_id = ? AND student_id = ?";
                    $stmt = $conn->prepare($updateQuery);
                    $stmt->bind_param("is", $parent_id, $student_id);
                    $success = $stmt->execute();
                    $stmt->close();
                } else {
                    $success = false;
                }
            }
            
            if ($success) {
                $_SESSION['success_message'] = "Student assignment removed successfully!";
            } else {
                $_SESSION['error_message'] = "Failed to remove student assignment.";
            }
        }
        
        // Redirect to refresh the page
        header("Location: manage_parent_students.php?parent_id=" . $parent_id);
        exit();
    }
}

// Determine the best name to display for the parent
$parentDisplayName = '';
<<<<<<< HEAD
if (!empty($parent['username'])) {
    // Use username from users table since we don't have first_name/last_name in parents table
=======
if (!empty($parent['first_name']) && !empty($parent['last_name']) && 
    $parent['first_name'] !== 'Father' && $parent['first_name'] !== 'Mother' && 
    $parent['first_name'] !== 'Guardian' && $parent['first_name'] !== 'Parent') {
    $parentDisplayName = trim($parent['first_name'] . ' ' . $parent['last_name']);
} elseif (!empty($parent['username'])) {
>>>>>>> b291daf7f49078bb0cccb1439969ad4a74e2db38
    $parentDisplayName = $parent['username'];
} else {
    $parentDisplayName = 'Parent ID: ' . $parent['parent_id'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Parent Students | Wisetech College</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2563eb;
            --primary-dark: #1d4ed8;
            --secondary-color: #f3f4f6;
            --accent-color: #3b82f6;
            --text-color: #1f2937;
            --muted-text: #6b7280;
            --white: #ffffff;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-800: #1f2937;
            --success: #10b981;
            --error: #ef4444;
            --warning: #f59e0b;
            --border-radius: 12px;
            --box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --box-shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--gray-50);
            color: var(--text-color);
            min-height: 100vh;
            line-height: 1.6;
        }

        .header {
            background-color: var(--white);
            padding: 1.25rem;
            box-shadow: var(--box-shadow);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
        }

        .logo h2 {
            color: var(--primary-color);
            font-weight: 700;
            font-size: 1.5rem;
            margin-left: 0.5rem;
        }

        .logo i {
            font-size: 1.75rem;
            color: var(--primary-color);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.625rem 1.25rem;
            border-radius: var(--border-radius);
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
            box-shadow: var(--box-shadow);
            border: none;
            cursor: pointer;
            font-size: 0.95rem;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: var(--white);
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: var(--box-shadow-lg);
        }

        .btn-success {
            background-color: var(--success);
            color: var(--white);
        }

        .btn-success:hover {
            background-color: #059669;
            transform: translateY(-2px);
            box-shadow: var(--box-shadow-lg);
        }

        .btn-error {
            background-color: var(--error);
            color: var(--white);
        }

        .btn-error:hover {
            background-color: #dc2626;
            transform: translateY(-2px);
            box-shadow: var(--box-shadow-lg);
        }

        .btn-sm {
            padding: 0.5rem 0.875rem;
            font-size: 0.875rem;
        }

        main {
            max-width: 1200px;
            width: 100%;
            margin: 2rem auto;
            padding: 0 1.5rem;
        }

        .page-header {
            background-color: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 2rem;
            margin-bottom: 2rem;
            text-align: center;
        }

        .page-header h1 {
            color: var(--primary-color);
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .parent-info {
            background-color: var(--primary-color);
            color: var(--white);
            padding: 1rem 1.5rem;
            border-radius: var(--border-radius);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .parent-details {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .parent-name {
            font-size: 1.25rem;
            font-weight: 600;
        }

        .parent-meta {
            font-size: 0.95rem;
            opacity: 0.9;
        }

        .alert {
            padding: 1rem;
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .alert-success {
            background-color: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #166534;
        }

        .alert-error {
            background-color: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
        }

        .alert-warning {
            background-color: #fef3c7;
            border: 1px solid #fcd34d;
            color: #92400e;
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }

        .card {
            background-color: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
        }

        .card-header {
            background-color: var(--gray-100);
            padding: 1.5rem;
            border-bottom: 1px solid var(--gray-200);
        }

        .card-header h3 {
            color: var(--text-color);
            font-size: 1.25rem;
            font-weight: 600;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .card-body {
            padding: 1.5rem;
        }

        .student-list {
            max-height: 400px;
            overflow-y: auto;
        }

        .student-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            border-bottom: 1px solid var(--gray-200);
            transition: var(--transition);
        }

        .student-item:hover {
            background-color: var(--gray-50);
        }

        .student-item:last-child {
            border-bottom: none;
        }

        .student-info h4 {
            margin: 0 0 0.25rem 0;
            color: var(--text-color);
            font-size: 1rem;
        }

        .student-info p {
            margin: 0;
            color: var(--muted-text);
            font-size: 0.875rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group:last-child {
            margin-bottom: 0;
        }

        .checkbox-list {
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid var(--gray-300);
            border-radius: var(--border-radius);
            padding: 0.5rem;
        }

        .checkbox-item {
            display: flex;
            align-items: center;
            padding: 0.75rem;
            border-radius: 6px;
            transition: var(--transition);
        }

        .checkbox-item:hover {
            background-color: var(--gray-50);
        }

        .checkbox-item input[type="checkbox"] {
            margin-right: 0.75rem;
            transform: scale(1.2);
        }

        .checkbox-item label {
            flex: 1;
            cursor: pointer;
            margin: 0;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: var(--muted-text);
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .empty-state h4 {
            margin-bottom: 0.5rem;
            color: var(--text-color);
        }

        @media screen and (max-width: 768px) {
            .grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }

            .header-container {
                flex-direction: column;
                gap: 1rem;
            }

            .parent-info {
                flex-direction: column;
                text-align: center;
            }

            .student-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
        }

        /* Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .card {
            animation: fadeIn 0.5s ease-out forwards;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-container">
            <div class="logo">
                <i class="fas fa-graduation-cap"></i>
                <h2>Wisetech College Portal</h2>
            </div>
            <a href="parents.php" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i> Back to Parents
            </a>
        </div>
    </header>

    <main>
        <div class="page-header">
            <h1><i class="fas fa-users-cog"></i> Manage Parent-Student Relationships</h1>
            <div class="parent-info">
                <i class="fas fa-user"></i>
                <div class="parent-details">
                    <div class="parent-name"><?= htmlspecialchars($parentDisplayName) ?></div>
                    <div class="parent-meta">
<<<<<<< HEAD
                        Email: <?= htmlspecialchars($parent['email']) ?> | 
=======
                        <?php if ($parentDisplayName !== ($parent['username'] ?? '')): ?>
                            Username: <?= htmlspecialchars($parent['username'] ?? 'N/A') ?> | 
                        <?php endif; ?>
>>>>>>> b291daf7f49078bb0cccb1439969ad4a74e2db38
                        Phone: <?= htmlspecialchars($parent['phone_number'] ?? 'N/A') ?> | 
                        <?= htmlspecialchars($parent['relationship'] ?? 'N/A') ?>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!$relationshipTableExists): ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i>
                <span><strong>Limited Mode:</strong> Advanced relationship features not available. Only one student can be assigned per parent.</span>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <span><?= htmlspecialchars($_SESSION['success_message']) ?></span>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-triangle"></i>
                <span><?= htmlspecialchars($_SESSION['error_message']) ?></span>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <div class="grid">
            <!-- Currently Assigned Students -->
            <div class="card">
                <div class="card-header">
                    <h3>
                        <i class="fas fa-user-graduate"></i>
                        Currently Assigned Students (<?= count($assignedStudents) ?>)
                    </h3>
                </div>
                <div class="card-body">
                    <?php if (count($assignedStudents) > 0): ?>
                        <div class="student-list">
                            <?php foreach ($assignedStudents as $student): ?>
                                <div class="student-item">
                                    <div class="student-info">
                                        <h4><?= htmlspecialchars(trim(($student['first_name'] ?? '') . ' ' . ($student['last_name'] ?? ''))) ?></h4>
                                        <p>ID: <?= htmlspecialchars($student['student_id'] ?? 'N/A') ?> | Username: <?= htmlspecialchars($student['username'] ?? 'N/A') ?></p>
                                        <?php if (isset($student['assigned_date']) && $student['assigned_date'] !== 'Unknown'): ?>
                                            <p>Assigned: <?= date('M d, Y', strtotime($student['assigned_date'])) ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to remove this student assignment?')">
                                        <input type="hidden" name="action" value="remove">
<<<<<<< HEAD
                                        <input type="hidden" name="remove_student_id" value="<?= $student['student_id'] ?>">
=======
                                        <input type="hidden" name="remove_student_id" value="<?= htmlspecialchars($student['student_id']) ?>">
>>>>>>> b291daf7f49078bb0cccb1439969ad4a74e2db38
                                        <button type="submit" class="btn btn-error btn-sm" title="Remove Assignment">
                                            <i class="fas fa-times"></i> Remove
                                        </button>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-user-graduate"></i>
                            <h4>No Students Assigned</h4>
                            <p>This parent has no students assigned yet. Use the form on the right to assign students.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Assign New Students -->
            <div class="card">
                <div class="card-header">
                    <h3>
                        <i class="fas fa-plus-circle"></i>
                        Assign New Students
                    </h3>
                </div>
                <div class="card-body">
                    <?php if (count($availableStudents) > 0): ?>
                        <form method="POST">
                            <input type="hidden" name="action" value="assign">
                            
                            <?php if (!$relationshipTableExists && count($assignedStudents) > 0): ?>
                                <div class="alert alert-warning">
                                    <i class="fas fa-info-circle"></i>
                                    <span>In basic mode, only one student can be assigned. Assigning a new student will replace the current assignment.</span>
                                </div>
                            <?php endif; ?>
                            
                            <div class="form-group">
                                <div class="checkbox-list">
                                    <?php foreach ($availableStudents as $student): ?>
                                        <div class="checkbox-item">
<<<<<<< HEAD
                                            <input type="checkbox" name="student_ids[]" value="<?= $student['student_id'] ?>" id="student_<?= $student['student_id'] ?>">
                                            <label for="student_<?= $student['student_id'] ?>">
                                                <strong><?= htmlspecialchars(trim($student['first_name'] . ' ' . $student['last_name'])) ?></strong><br>
                                                <small>ID: <?= htmlspecialchars($student['student_id']) ?> | Username: <?= htmlspecialchars($student['username']) ?></small>
=======
                                            <input type="checkbox" name="student_ids[]" value="<?= htmlspecialchars($student['student_id']) ?>" id="student_<?= htmlspecialchars($student['id']) ?>">
                                            <label for="student_<?= htmlspecialchars($student['id']) ?>">
                                                <strong><?= htmlspecialchars(trim(($student['first_name'] ?? '') . ' ' . ($student['last_name'] ?? ''))) ?></strong><br>
                                                <small>ID: <?= htmlspecialchars($student['student_id'] ?? 'N/A') ?> | Username: <?= htmlspecialchars($student['username'] ?? 'N/A') ?></small>
>>>>>>> b291daf7f49078bb0cccb1439969ad4a74e2db38
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-success" style="width: 100%;">
                                <i class="fas fa-plus"></i> Assign Selected Students
                            </button>
                        </form>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-check-circle"></i>
                            <h4>All Students Assigned</h4>
                            <p>This parent is already assigned to all available students in the system.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Select all functionality
        document.addEventListener('DOMContentLoaded', function() {
            const checkboxes = document.querySelectorAll('input[name="student_ids[]"]');
            
            if (checkboxes.length > 0) {
                // Add select all button
                const form = document.querySelector('form[method="POST"]');
                const checkboxList = document.querySelector('.checkbox-list');
                
                if (form && checkboxList) {
                    const selectAllDiv = document.createElement('div');
                    selectAllDiv.style.padding = '0.5rem 0.75rem';
                    selectAllDiv.style.borderBottom = '1px solid var(--gray-200)';
                    selectAllDiv.style.backgroundColor = 'var(--gray-50)';
                    selectAllDiv.innerHTML = `
                        <label style="display: flex; align-items: center; font-weight: 600; cursor: pointer;">
                            <input type="checkbox" id="selectAll" style="margin-right: 0.5rem; transform: scale(1.2);">
                            Select All Students
                        </label>
                    `;
                    
                    checkboxList.insertBefore(selectAllDiv, checkboxList.firstChild);
                    
                    const selectAllCheckbox = document.getElementById('selectAll');
                    
                    selectAllCheckbox.addEventListener('change', function() {
                        checkboxes.forEach(checkbox => {
                            checkbox.checked = this.checked;
                        });
                        updateCounter();
                    });
                    
                    // Update select all when individual checkboxes change
                    checkboxes.forEach(checkbox => {
                        checkbox.addEventListener('change', function() {
                            const checkedCount = document.querySelectorAll('input[name="student_ids[]"]:checked').length;
                            selectAllCheckbox.checked = checkedCount === checkboxes.length;
                            selectAllCheckbox.indeterminate = checkedCount > 0 && checkedCount < checkboxes.length;
                            updateCounter();
                        });
                    });
                }
            }
            
            function updateCounter() {
                const checkedCount = document.querySelectorAll('input[name="student_ids[]"]:checked').length;
                const submitButton = document.querySelector('button[type="submit"]');
                
                if (submitButton) {
                    if (checkedCount > 0) {
                        submitButton.innerHTML = `<i class="fas fa-plus"></i> Assign Selected Students (${checkedCount})`;
                        submitButton.disabled = false;
                    } else {
                        submitButton.innerHTML = `<i class="fas fa-plus"></i> Assign Selected Students`;
                        submitButton.disabled = false;
                    }
                }
            }
        });
    </script>
</body>
</html>