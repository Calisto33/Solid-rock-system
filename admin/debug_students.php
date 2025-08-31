<?php
// debug_students.php - Create this file to check your data
include '../config.php';

echo "<h2>Current Parent-Student Data:</h2>";

// Check current parent assignments
$parentQuery = "SELECT parent_id, student_id, first_name, last_name, relationship FROM parents ORDER BY parent_id";
$parentResult = $conn->query($parentQuery);

echo "<h3>Parents Table:</h3>";
echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
echo "<tr><th>Parent ID</th><th>Student ID</th><th>Name</th><th>Relationship</th></tr>";
while ($parent = $parentResult->fetch_assoc()) {
    echo "<tr>";
    echo "<td>{$parent['parent_id']}</td>";
    echo "<td>{$parent['student_id']}</td>";
    echo "<td>{$parent['first_name']} {$parent['last_name']}</td>";
    echo "<td>{$parent['relationship']}</td>";
    echo "</tr>";
}
echo "</table>";

// Check available students
$studentQuery = "SELECT student_id, username, first_name, last_name FROM students ORDER BY student_id";
$studentResult = $conn->query($studentQuery);

echo "<h3>Available Students:</h3>";
echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
echo "<tr><th>Student ID</th><th>Username</th><th>First Name</th><th>Last Name</th></tr>";
while ($student = $studentResult->fetch_assoc()) {
    echo "<tr>";
    echo "<td>{$student['student_id']}</td>";
    echo "<td>{$student['username']}</td>";
    echo "<td>{$student['first_name']}</td>";
    echo "<td>{$student['last_name']}</td>";
    echo "</tr>";
}
echo "</table>";

// Test the JOIN to see what's happening
echo "<h3>Current JOIN Results (What parents.php shows):</h3>";
$joinQuery = "
    SELECT p.parent_id, p.student_id, p.first_name as parent_first, p.last_name as parent_last,
           s.student_id as actual_student_id, s.username, s.first_name as student_first, s.last_name as student_last
    FROM parents p
    LEFT JOIN students s ON p.student_id = s.student_id
    ORDER BY p.parent_id";

$joinResult = $conn->query($joinQuery);

echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
echo "<tr><th>Parent ID</th><th>Parent Student ID</th><th>Parent Name</th><th>Actual Student ID</th><th>Student Username</th><th>Student Name</th></tr>";
while ($row = $joinResult->fetch_assoc()) {
    echo "<tr>";
    echo "<td>{$row['parent_id']}</td>";
    echo "<td>{$row['student_id']}</td>";
    echo "<td>{$row['parent_first']} {$row['parent_last']}</td>";
    echo "<td>" . ($row['actual_student_id'] ?? 'NULL') . "</td>";
    echo "<td>" . ($row['username'] ?? 'NULL') . "</td>";
    echo "<td>" . ($row['student_first'] . ' ' . $row['student_last']) . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h3>Fix Commands:</h3>";
echo "<p>Run these SQL commands to fix the assignments:</p>";
echo "<pre>";
echo "UPDATE parents SET student_id = 162 WHERE parent_id = 12; -- Assign to Muronzi Lynn\n";
echo "UPDATE parents SET student_id = 163 WHERE parent_id = 13; -- Assign to Muzvidziwa Panashe\n";
echo "UPDATE parents SET student_id = 170 WHERE parent_id = 14; -- Assign to Muzvidziwa Ruvarashe\n";
echo "</pre>";

$conn->close();
?>