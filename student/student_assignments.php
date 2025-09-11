<?php
session_start();
include '../config.php'; // Your database connection

// --- Authentication: Ensure student is logged in ---
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'student' || !isset($_SESSION['user_id'])) {
    header("Location: ../login.php"); // Adjust path to your login page
    exit();
}
$logged_in_student_id = $_SESSION['user_id'];

// --- Calculate Unread Assignments Count (for the badge) ---
$unread_assignments_count = 0;
// This query counts assignments that are NOT in student_read_assignments for this student
$sql_count_assignments = "
    SELECT COUNT(a.assignment_id) AS count
    FROM assignments a
    LEFT JOIN student_read_assignments sra ON a.assignment_id = sra.assignment_id AND sra.student_id = ?
    WHERE sra.id IS NULL"; // sra.id IS NULL means no matching read record was found

$stmt_count = $conn->prepare($sql_count_assignments);
if ($stmt_count) {
    $stmt_count->bind_param("i", $logged_in_student_id);
    $stmt_count->execute();
    $result_count = $stmt_count->get_result()->fetch_assoc();
    $unread_assignments_count = $result_count['count'] ?? 0;
    $stmt_count->close();
} else {
    // Log error or handle appropriately
    error_log("Error preparing unread assignment count: " . $conn->error);
}


// --- Fetch Assignments to Display ---
// This is an example query. Adjust to your actual assignments table and desired ordering.
$stmt_fetch_assignments = $conn->prepare("SELECT assignment_id, title, description, due_date FROM assignments ORDER BY due_date DESC");
if (!$stmt_fetch_assignments) {
    die("Error preparing assignments query: " . $conn->error);
}
$stmt_fetch_assignments->execute();
$assignments_result = $stmt_fetch_assignments->get_result();

$assignments_to_display = [];
$assignment_ids_to_mark_read = [];

while ($row = $assignments_result->fetch_assoc()) {
    $assignments_to_display[] = $row;
    // Collect IDs of all assignments being fetched for display
    $assignment_ids_to_mark_read[] = $row['assignment_id'];
}
$stmt_fetch_assignments->close();


// --- Mark Fetched Assignments as Read ---
if (!empty($assignment_ids_to_mark_read)) {
    $insert_read_sql = "INSERT IGNORE INTO student_read_assignments (student_id, assignment_id) VALUES (?, ?)";
    $stmt_mark_read = $conn->prepare($insert_read_sql);

    if ($stmt_mark_read) {
        foreach ($assignment_ids_to_mark_read as $assignment_id) {
            $stmt_mark_read->bind_param("ii", $logged_in_student_id, $assignment_id);
            if (!$stmt_mark_read->execute()) {
                // Log error if a specific insert fails (though IGNORE usually suppresses errors for duplicates)
                error_log("Error marking assignment_id " . $assignment_id . " as read: " . $stmt_mark_read->error);
            }
        }
        $stmt_mark_read->close();
        // After marking, the badge count for *this specific page load* might still show the old count
        // because it was calculated *before* marking. The *next* time the page/badge is loaded, it will be updated.
        // To show the updated count immediately on this page, you would recalculate $unread_assignments_count here.
        // For simplicity, this example relies on the next page load showing the updated badge.
    } else {
        error_log("Error preparing statement to mark assignments as read: " . $conn->error);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Assignments</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f4f4f4; }
        .container { background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h1, h2 { color: #333; }
        nav a { text-decoration: none; color: #007bff; margin-right: 15px; font-size: 1.2em; }
        nav .badge { background-color: red; color: white; padding: 2px 6px; border-radius: 10px; font-size: 0.8em; margin-left: 4px; }
        .assignment-list { list-style: none; padding: 0; }
        .assignment-list li { background-color: #f9f9f9; border: 1px solid #ddd; margin-bottom: 10px; padding: 15px; border-radius: 5px; }
        .assignment-list h3 { margin-top: 0; }
        .assignment-list p { margin-bottom: 5px; }
        .assignment-list small { color: #555; }
        .no-items { color: #777; }
    </style>
</head>
<body>
    <div class="container">
        <nav>
            <a href="student_dashboard.php">Dashboard</a>
            <a href="student_assignments.php">
                Assignments
                <?php if ($unread_assignments_count > 0): ?>
                    <span class="badge"><?= $unread_assignments_count ?></span>
                <?php endif; ?>
            </a>
            <a href="student_news.php">
                News 
                <?php /* Replace with actual $unread_news_count logic */ ?>
                </a>
            <a href="student_events.php">
                Events
                <?php /* Replace with actual $unread_events_count logic */ ?>
                </a>
            <a href="../logout.php">Logout</a>
        </nav>

        <hr>

        <h2>My Assignments</h2>
        <?php if (!empty($assignments_to_display)): ?>
            <ul class="assignment-list">
                <?php foreach ($assignments_to_display as $assignment): ?>
                    <li>
                        <h3><?= htmlspecialchars($assignment['title']) ?></h3>
                        <p><?= nl2br(htmlspecialchars($assignment['description'] ?? '')) ?></p>
                        <small>Due Date: <?= htmlspecialchars(date("F j, Y", strtotime($assignment['due_date']))) ?></small>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p class="no-items">No assignments found at this time.</p>
        <?php endif; ?>
    </div>

    <?php
    // It's good practice to close the connection if it's not persistent
    // However, if config.php closes it or you have other script parts, manage accordingly.
    // $conn->close();
    ?>
</body>
</html>