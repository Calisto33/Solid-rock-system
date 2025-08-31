<?php
include '../config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $student_id = $_POST['student_id'];
    $subject_id = $_POST['subject_id'];
    $action = $_POST['action'];

    if ($action == 'add') {
        // Add subject to student
        $stmt = $conn->prepare("INSERT INTO student_subject (student_id, subject_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $student_id, $subject_id);
        $stmt->execute();
        $stmt->close();
        echo "Subject added successfully!";
    } elseif ($action == 'drop') {
        // Drop subject from student
        $stmt = $conn->prepare("DELETE FROM student_subject WHERE student_id = ? AND subject_id = ?");
        $stmt->bind_param("ii", $student_id, $subject_id);
        $stmt->execute();
        $stmt->close();
        echo "Subject dropped successfully!";
    }
}
$conn->close();
header("Location: add_remove_subjects.php");
?>
