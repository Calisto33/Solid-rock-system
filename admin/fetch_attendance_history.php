<?php
include '../config.php'; // Database connection

// Get student ID from the request
$student_id = $_GET['student_id'] ?? null;

if (!$student_id) {
    echo "Student ID is missing.";
    exit();
}

// Fetch attendance history for the selected student
$historyQuery = "
    SELECT s.username AS student_name, a.date, a.status
    FROM attendance a
    JOIN students s ON a.student_id = s.student_id
    WHERE a.student_id = ?
    ORDER BY a.date DESC";
$stmt = $conn->prepare($historyQuery);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$historyResult = $stmt->get_result();

// Display the attendance history
if ($historyResult->num_rows > 0) {
    echo "<table border='1' style='width:100%; margin-top:20px;'>
            <tr>
                <th>Student Name</th>
                <th>Date</th>
                <th>Status</th>
            </tr>";
    while ($row = $historyResult->fetch_assoc()) {
        echo "<tr>
                <td>" . htmlspecialchars($row['student_name']) . "</td>
                <td>" . htmlspecialchars($row['date']) . "</td>
                <td>" . htmlspecialchars($row['status']) . "</td>
              </tr>";
    }
    echo "</table>";
} else {
    echo "<p>No attendance history found for this student.</p>";
}

$stmt->close();
$conn->close();
?>
