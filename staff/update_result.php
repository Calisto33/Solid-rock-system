<?php
// Page-specific security check
session_start();
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['staff', 'admin'])) {
    header("Location: ../login.php");
    exit();
}

// Set the page title for the header
$pageTitle = "Add/Update Result";

// Include the new header. It provides the visual layout and DB connection.
include 'header.php';

// --- PHP LOGIC SPECIFIC TO THIS PAGE ---

$student_id = $_GET['student_id'] ?? null;
if (!$student_id) {
    header("Location: student_list.php"); // Assuming you have a student list page
    exit();
}

// Fetch student information
$studentQuery = "SELECT student_id, username, class FROM students WHERE student_id = ?";
$stmtStudent = $conn->prepare($studentQuery);
$stmtStudent->bind_param("i", $student_id);
$stmtStudent->execute();
$studentResult = $stmtStudent->get_result();
$student = $studentResult->fetch_assoc();
$stmtStudent->close();

if (!$student) {
    header("Location: student_list.php"); // Redirect if student not found
    exit();
}

// Fetch subjects for dropdown
$subjectsQuery = "SELECT subject_id, subject_name FROM table_subject ORDER BY subject_name ASC";
$subjectsResult = $conn->query($subjectsQuery);
$subjects = [];
while ($row = $subjectsResult->fetch_assoc()) {
    $subjects[] = $row;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $subject_id = $_POST['subject_id'];
    $term = $_POST['term'];
    $year = $_POST['year'];
    $mark_percentage = !empty($_POST['mark_percentage']) ? $_POST['mark_percentage'] : null;
    $exam_grade = $_POST['exam_grade'];
    $target_grade = $_POST['target_grade'];
    $attitude_to_learning = !empty($_POST['attitude_to_learning']) ? $_POST['attitude_to_learning'] : null;
    $comments = trim($_POST['comments']);

    // Check if a result already exists to decide between INSERT and UPDATE
    $existingResultQuery = "SELECT result_id FROM results WHERE student_id = ? AND subject_id = ? AND term = ? AND year = ?";
    $resultStmt = $conn->prepare($existingResultQuery);
    $resultStmt->bind_param("iiss", $student_id, $subject_id, $term, $year);
    $resultStmt->execute();
    $existingResult = $resultStmt->get_result()->fetch_assoc();
    $resultStmt->close();

    if ($existingResult) {
        // UPDATE existing record
        $updateQuery = "UPDATE results SET mark_percentage = ?, exam_grade = ?, target_grade = ?, attitude_to_learning = ?, comments = ? WHERE result_id = ?";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bind_param("dssisi", $mark_percentage, $exam_grade, $target_grade, $attitude_to_learning, $comments, $existingResult['result_id']);
        if ($updateStmt->execute()) {
            $_SESSION['message'] = "Result updated successfully.";
            $_SESSION['message_type'] = "success";
        }
        $updateStmt->close();
    } else {
        // INSERT new record
        $insertQuery = "INSERT INTO results (student_id, subject_id, term, year, mark_percentage, exam_grade, target_grade, attitude_to_learning, comments) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $insertStmt = $conn->prepare($insertQuery);
        $insertStmt->bind_param("iissdssis", $student_id, $subject_id, $term, $year, $mark_percentage, $exam_grade, $target_grade, $attitude_to_learning, $comments);
        if ($insertStmt->execute()) {
            $_SESSION['message'] = "Result added successfully.";
            $_SESSION['message_type'] = "success";
        }
        $insertStmt->close();
    }
    header("Location: student_results_history.php?student_id=" . $student_id);
    exit();
}

// Retrieve flash message from session
$message = $_SESSION['message'] ?? null;
$message_type = $_SESSION['message_type'] ?? 'info';
unset($_SESSION['message'], $_SESSION['message_type']);
?>

<style>
    .student-info { background-color: #f1f5f9; padding: 1rem; border-radius: 10px; margin-bottom: 2rem; text-align: center; border-left: 4px solid var(--primary-color); }
    .student-name { font-size: 1.3rem; font-weight: 600; }
    form { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; }
    .form-group { display: flex; flex-direction: column; gap: 0.5rem; }
    .form-group.full-width { grid-column: 1 / -1; }
    label { font-weight: 500; }
    label i { margin-right: 0.5rem; color: var(--primary-color); }
    input, select, textarea { width: 100%; padding: 0.9rem 1rem; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 1rem; }
    textarea { min-height: 100px; resize: vertical; }
    .submit-btn {
        grid-column: 1 / -1; background-color: var(--primary-color); color: white; padding: 1rem;
        border: none; border-radius: 8px; cursor: pointer; font-size: 1.1rem;
    }
    .message { padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; }
    .message.success { background-color: #dcfce7; color: #166534; }
    .message.error { background-color: #fee2e2; color: #b91c1c; }
</style>

<div class="card">
    <h2 class="page-title">Add / Update Student Result</h2>
    <div class="student-info">
        <div class="student-name"><?= htmlspecialchars($student['username']) ?></div>
        <div>Class: <?= htmlspecialchars($student['class']) ?> | Student ID: <?= htmlspecialchars($student['student_id']) ?></div>
    </div>

    <?php if ($message): ?>
        <div class="message <?= htmlspecialchars($message_type) ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form action="update_result.php?student_id=<?= htmlspecialchars($student_id) ?>" method="POST">
        <div class="form-group">
            <label for="subject_id"><i class="fas fa-book"></i> Subject</label>
            <select name="subject_id" id="subject_id" required>
                <option value="">-- Select Subject --</option>
                <?php foreach ($subjects as $subject_item): ?>
                    <option value="<?= $subject_item['subject_id'] ?>"><?= htmlspecialchars($subject_item['subject_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="term"><i class="fas fa-calendar-alt"></i> Term</label>
            <input type="text" name="term" id="term" placeholder="e.g., Term 1, Mid-Term" required>
        </div>

        <div class="form-group">
            <label for="year"><i class="fas fa-calendar-day"></i> Year</label>
            <input type="number" name="year" id="year" placeholder="e.g., <?= date("Y") ?>" required value="<?= date("Y") ?>">
        </div>
        
        <div class="form-group">
            <label for="mark_percentage"><i class="fas fa-percentage"></i> Mark %</label>
            <input type="number" name="mark_percentage" id="mark_percentage" step="0.01" placeholder="e.g., 85.5">
        </div>

        <div class="form-group">
            <label for="exam_grade"><i class="fas fa-award"></i> Exam Grade (EG)</label>
            <input type="text" name="exam_grade" id="exam_grade" placeholder="e.g., A+, B, Pass">
        </div>

        <div class="form-group">
            <label for="target_grade"><i class="fas fa-bullseye"></i> Target Grade (TG)</label>
            <input type="text" name="target_grade" id="target_grade" placeholder="e.g., A, B">
        </div>

        <div class="form-group">
            <label for="attitude_to_learning"><i class="fas fa-brain"></i> Attitude to Learning (ATL)</label>
            <select name="attitude_to_learning" id="attitude_to_learning">
                <option value="">-- Select ATL --</option>
                <option value="5">5 - VERY GOOD</option>
                <option value="4">4 - GOOD</option>
                <option value="3">3 - AVERAGE</option>
                <option value="2">2 - POOR</option>
                <option value="1">1 - SERIOUS CONCERN</option>
            </select>
        </div>

        <div class="form-group full-width">
            <label for="comments"><i class="fas fa-comment-dots"></i> Comments</label>
            <textarea name="comments" id="comments" rows="3" placeholder="Enter comments here..."></textarea>
        </div>

        <button type="submit" class="submit-btn"><i class="fas fa-save"></i> Save Result</button>
    </form>
</div>

<?php
// Include the new footer to close the page layout
$conn->close();
include 'footer.php';
?>