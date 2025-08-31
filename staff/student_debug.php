<?php
// Quick debug tool to check student data
session_start();
include '../config.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['staff', 'admin'])) {
    header("Location: ../login.php");
    exit();
}

$class_id = $_GET['class_id'] ?? 5; // Default to class 5 for testing

echo "<h2>Student Data Debug for Class ID: $class_id</h2>";

// Check students table structure
echo "<h3>Students Table Structure:</h3>";
$structure_query = "DESCRIBE students";
$structure_result = $conn->query($structure_query);
if ($structure_result) {
    echo "<table border='1' style='border-collapse: collapse; margin-bottom: 20px;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while ($row = $structure_result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Default']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Check all students in the specific class
echo "<h3>All Students in Class $class_id:</h3>";
$students_query = "SELECT * FROM students WHERE class_id = ? ORDER BY id";
$stmt = $conn->prepare($students_query);
$stmt->bind_param("i", $class_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; margin-bottom: 20px;'>";
    echo "<tr><th>ID</th><th>Username</th><th>First Name</th><th>Last Name</th><th>Class ID</th><th>Other Fields</th></tr>";
    while ($student = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($student['id']) . "</td>";
        echo "<td>" . htmlspecialchars($student['username'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($student['first_name'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($student['last_name'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($student['class_id'] ?? 'NULL') . "</td>";
        
        // Show other fields
        $other_fields = [];
        foreach ($student as $key => $value) {
            if (!in_array($key, ['id', 'username', 'first_name', 'last_name', 'class_id'])) {
                $other_fields[] = "$key: " . htmlspecialchars($value ?? 'NULL');
            }
        }
        echo "<td>" . implode('<br>', $other_fields) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No students found in class $class_id</p>";
}

// Check if there are students in other classes
echo "<h3>Students by Class Distribution:</h3>";
$distribution_query = "SELECT class_id, COUNT(*) as count FROM students GROUP BY class_id ORDER BY class_id";
$distribution_result = $conn->query($distribution_query);
if ($distribution_result) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Class ID</th><th>Student Count</th></tr>";
    while ($row = $distribution_result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['class_id'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($row['count']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Sample student data for different class IDs
echo "<h3>Sample Students from Each Class:</h3>";
$sample_query = "SELECT id, username, first_name, last_name, class_id FROM students ORDER BY class_id, id LIMIT 20";
$sample_result = $conn->query($sample_query);
if ($sample_result) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Username</th><th>First Name</th><th>Last Name</th><th>Class ID</th></tr>";
    while ($row = $sample_result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['username'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($row['first_name'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($row['last_name'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($row['class_id'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

$conn->close();
?>

<style>
table { border-collapse: collapse; margin: 10px 0; }
th, td { padding: 8px; border: 1px solid #ddd; text-align: left; }
th { background-color: #f2f2f2; }
</style>

<p><a href="javascript:history.back()">&larr; Go Back</a></p>