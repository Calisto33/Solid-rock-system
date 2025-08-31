<?php
session_start();
include '../config.php'; // Your database connection

// 1. SECURITY: Ensure a teacher is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    header("Location: ../login.php");
    exit();
}

// 2. VALIDATION: Check if an assignment ID was provided in the URL
if (!isset($_GET['assignment_id']) || !is_numeric($_GET['assignment_id'])) {
    die("Error: Invalid assignment ID.");
}

$assignment_id = (int)$_GET['assignment_id'];
$user_id = $_SESSION['user_id']; // This is the user ID from the session

// 3. FIX: Get the correct staff_id from the staff table
$staffIdQuery = "SELECT staff_id FROM staff WHERE id = ?";
$stmt_staff = $conn->prepare($staffIdQuery);

if(!$stmt_staff) {
    die("Prepare failed for staff query: ". $conn->error);
}

$stmt_staff->bind_param("i", $user_id);
$stmt_staff->execute();
$result_staff = $stmt_staff->get_result();
$staffData = $result_staff->fetch_assoc();
$stmt_staff->close();

if (!$staffData) {
    die("Error: This user is not registered correctly in the 'staff' table.");
}

$teacher_staff_id = $staffData['staff_id']; // This is the correct staff_id to use

// 4. DATABASE: Fetch assignment details (and verify it belongs to this teacher)
// FIXED: Use staff_id instead of teacher_id
$assignment_query = "SELECT assignment_title FROM assignments WHERE assignment_id = ? AND staff_id = ?";
$stmt = $conn->prepare($assignment_query);

if (!$stmt) {
    die("Prepare failed for assignment query: " . $conn->error);
}

$stmt->bind_param("is", $assignment_id, $teacher_staff_id); // staff_id is varchar, so use 's'
$stmt->execute();
$assignment_result = $stmt->get_result();

if ($assignment_result->num_rows === 0) {
    // This is a CRITICAL security check. It prevents a teacher from viewing another teacher's assignment submissions.
    die("Error: Assignment not found or you do not have permission to view it.");
}
$assignment = $assignment_result->fetch_assoc();
$pageTitle = "Submissions for " . htmlspecialchars($assignment['assignment_title']);
$stmt->close();

include 'header.php'; // Your teacher-side header

// 5. DATABASE: Fetch all submissions for this assignment, joining with the students table to get student names
// FIXED: Using the students table with first_name and last_name, with NULL handling
$submissions_query = "SELECT 
                        s.submission_id,
                        s.submission_file, 
                        s.comments, 
                        s.submitted_at,
                        COALESCE(CONCAT(st.first_name, ' ', st.last_name), st.username, st.first_name, st.last_name, 'Unknown Student') as full_name,
                        s.student_id,
                        s.status,
                        s.score,
                        s.feedback
                      FROM submissions s 
                      LEFT JOIN students st ON s.student_id = st.id 
                      WHERE s.assignment_id = ? 
                      ORDER BY s.submitted_at DESC";

$stmt = $conn->prepare($submissions_query);

if (!$stmt) {
    die("Prepare failed for submissions query: " . $conn->error);
}

$stmt->bind_param("i", $assignment_id);
$stmt->execute();
$submissions = $stmt->get_result();

?>

<h1 class="page-title">Student Submissions</h1>
<p style="margin-top:-1.5rem; margin-bottom: 2rem; color: var(--secondary-text);">
    Viewing submissions for: <strong><?= htmlspecialchars($assignment['assignment_title']) ?></strong>
</p>

<!-- DEBUG INFO - Remove this after testing -->
<div class="alert alert-info">
    <strong>Debug Info:</strong><br>
    Assignment ID: <?= $assignment_id ?><br>
    Assignment Title: <?= htmlspecialchars($assignment['assignment_title']) ?><br>
    Total Submissions Found: <?= $submissions->num_rows ?><br>
    Staff ID: <?= htmlspecialchars($teacher_staff_id) ?>
</div>

<?php if ($submissions->num_rows > 0): ?>
    <?php while($submission = $submissions->fetch_assoc()): ?>
        <div class="card" style="margin-bottom: 1.5rem;">
            <div class="card-body">
                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                    <div style="flex: 1;">
                        <h3 style="margin-top:0; margin-bottom: 0.5rem;">
                            <?= htmlspecialchars($submission['full_name']) ?>
                            <small style="color: #666; font-size: 0.8em;">(Student ID: <?= $submission['student_id'] ?>)</small>
                        </h3>
                        <p style="font-size: 0.9rem; color: var(--secondary-text); margin-top:0;">
                            Submitted on: <?= date('F j, Y, g:i a', strtotime($submission['submitted_at'])) ?>
                        </p>
                        <?php if (!empty($submission['status'])): ?>
                            <span class="badge bg-<?= $submission['status'] == 'submitted' ? 'success' : 'warning' ?>">
                                <?= ucfirst(htmlspecialchars($submission['status'])) ?>
                            </span>
                        <?php endif; ?>
                        <?php if (!empty($submission['score'])): ?>
                            <span class="badge bg-info ms-2">Score: <?= htmlspecialchars($submission['score']) ?></span>
                        <?php endif; ?>
                    </div>
                    <div>
                        <?php if (!empty($submission['submission_file']) && file_exists($submission['submission_file'])): ?>
                            <a href="<?= htmlspecialchars($submission['submission_file']) ?>" class="btn btn-success btn-sm me-2" download>
                                <i class="fas fa-download"></i> Download Work
                            </a>
                        <?php else: ?>
                            <span class="btn btn-secondary btn-sm me-2" style="opacity: 0.6;">
                                <i class="fas fa-file-slash"></i> No File
                            </span>
                        <?php endif; ?>
                        
                        <a href="grade_submission.php?submission_id=<?= $submission['submission_id'] ?>" class="btn btn-primary btn-sm">
                            <i class="fas fa-edit"></i> Grade
                        </a>
                    </div>
                </div>

                <hr style="margin: 1.5rem 0;">

                <h4>Student's Comments:</h4>
                <?php if (!empty($submission['comments'])): ?>
                    <div style="padding: 1rem; background-color: #f8f9fa; border-radius: var(--rounded-lg); border: 1px solid var(--border-color);">
                        <p style="margin:0; white-space: pre-wrap;"><?= htmlspecialchars($submission['comments']) ?></p>
                    </div>
                <?php else: ?>
                    <p><i>No comments were provided by the student.</i></p>
                <?php endif; ?>

                <?php if (!empty($submission['feedback'])): ?>
                    <h4 style="margin-top: 1.5rem;">Teacher's Feedback:</h4>
                    <div style="padding: 1rem; background-color: #e8f4fd; border-radius: var(--rounded-lg); border: 1px solid #b8daff;">
                        <p style="margin:0; white-space: pre-wrap;"><?= htmlspecialchars($submission['feedback']) ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endwhile; ?>
<?php else: ?>
    <div class="card">
        <div class="card-body">
            <p>No submissions have been made for this assignment yet.</p>
        </div>
    </div>
<?php endif; ?>

<?php 
$stmt->close();
include 'footer.php'; 
?>