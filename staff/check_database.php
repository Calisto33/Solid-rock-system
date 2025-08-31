<?php
// Database diagnostic script
// Save this as 'check_database.php' and run it to diagnose your database

// Include your database connection
include 'header.php'; // or wherever your database connection is

echo "<h2>Database Diagnostic Report</h2>";

// Check database connection
if ($conn) {
    echo "<p style='color: green;'>✓ Database connection successful</p>";
} else {
    echo "<p style='color: red;'>✗ Database connection failed</p>";
    exit;
}

// Check classes table
echo "<h3>Classes Table:</h3>";
$classesQuery = "SELECT class_id, class_name FROM classes ORDER BY class_name ASC";
$classesResult = $conn->query($classesQuery);

if ($classesResult) {
    echo "<p>Classes found: " . $classesResult->num_rows . "</p>";
    if ($classesResult->num_rows > 0) {
        echo "<ul>";
        while ($class = $classesResult->fetch_assoc()) {
            echo "<li>ID: " . $class['class_id'] . " - Name: " . $class['class_name'] . "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: orange;'>⚠ No classes found in database</p>";
    }
} else {
    echo "<p style='color: red;'>✗ Error querying classes: " . $conn->error . "</p>";
}

// Check subjects table
echo "<h3>Subjects Table:</h3>";
$subjectsQuery = "SELECT subject_id, subject_name FROM subjects ORDER BY subject_name ASC";
$subjectsResult = $conn->query($subjectsQuery);

if ($subjectsResult) {
    echo "<p>Subjects found: " . $subjectsResult->num_rows . "</p>";
    if ($subjectsResult->num_rows > 0) {
        echo "<ul>";
        while ($subject = $subjectsResult->fetch_assoc()) {
            echo "<li>ID: " . $subject['subject_id'] . " - Name: " . $subject['subject_name'] . "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: orange;'>⚠ No subjects found in database</p>";
    }
} else {
    echo "<p style='color: red;'>✗ Error querying subjects: " . $conn->error . "</p>";
}

// Check teacher_subjects table
echo "<h3>Teacher Subjects Table:</h3>";
$teacherSubjectsQuery = "SELECT assignment_id, teacher_id, subject_id, class_id FROM teacher_subjects";
$teacherSubjectsResult = $conn->query($teacherSubjectsQuery);

if ($teacherSubjectsResult) {
    echo "<p>Teacher assignments found: " . $teacherSubjectsResult->num_rows . "</p>";
    if ($teacherSubjectsResult->num_rows > 0) {
        echo "<ul>";
        while ($assignment = $teacherSubjectsResult->fetch_assoc()) {
            echo "<li>Assignment ID: " . $assignment['assignment_id'] . 
                 " - Teacher: " . $assignment['teacher_id'] . 
                 " - Subject: " . $assignment['subject_id'] . 
                 " - Class: " . $assignment['class_id'] . "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: orange;'>⚠ No teacher assignments found</p>";
    }
} else {
    echo "<p style='color: red;'>✗ Error querying teacher_subjects: " . $conn->error . "</p>";
}

// Check table structures
echo "<h3>Table Structures:</h3>";
$tables = ['classes', 'subjects', 'teacher_subjects'];

foreach ($tables as $table) {
    echo "<h4>Structure of '$table' table:</h4>";
    $structureQuery = "DESCRIBE $table";
    $structureResult = $conn->query($structureQuery);
    
    if ($structureResult) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        while ($row = $structureResult->fetch_assoc()) {
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>" . htmlspecialchars($value ?? '') . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>✗ Error describing table '$table': " . $conn->error . "</p>";
    }
}

$conn->close();
?>

<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    h2 { color: #333; }
    h3 { color: #666; border-bottom: 1px solid #ddd; padding-bottom: 5px; }
    h4 { color: #888; }
    table { width: 100%; max-width: 600px; }
    th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
    th { background-color: #f2f2f2; }
    ul { margin: 10px 0; }
    li { margin: 5px 0; }
</style>