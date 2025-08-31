<?php
// Set the page title for this specific page
$pageTitle = "Review Assignments";

// Include the new header. It handles security, session, db connection, and the sidebar.
include 'header.php';

// --- PHP LOGIC SPECIFIC TO THIS PAGE ---

// Get the teacher ID from session (assuming it's stored as user_id)
$teacher_id = $_SESSION['user_id'];

// Handle the form submission when a mark is saved from the modal
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['mark_submission'])) {
    $submission_id = (int)$_POST['submission_id'];
    $score = $_POST['score'];
    $feedback = trim($_POST['feedback']);

    // Note: We use the table name 'submissions' as previously established
    $updateStmt = $conn->prepare("UPDATE submissions SET score = ?, feedback = ?, status = 'Marked' WHERE submission_id = ?");
    $updateStmt->bind_param("dsi", $score, $feedback, $submission_id);
    
    if ($updateStmt->execute()) {
        // Redirect to the same page with a success message
        header("Location: review_assignments.php?status=marked");
        exit();
    } else {
        $error_message = "Error: Could not save the mark.";
    }
    $updateStmt->close();
}

// First, we need to add subject_id to assignments table if it doesn't exist
// Check if assignments table has subject_id column
$checkColumnQuery = "SHOW COLUMNS FROM assignments LIKE 'subject_id'";
$checkResult = $conn->query($checkColumnQuery);

if ($checkResult->num_rows == 0) {
    // Add subject_id column to assignments table
    $addColumnQuery = "ALTER TABLE assignments ADD COLUMN subject_id INT(11) AFTER assignment_id";
    $conn->query($addColumnQuery);
}

// Fetch all submissions for the subjects taught by the logged-in teacher
$submissionsQuery = "
    SELECT 
        sub.submission_id,
        sub.submission_file,
        sub.comments AS student_comments,
        sub.status,
        sub.score,
        sub.feedback,
        sub.submitted_at,
        u.username AS student_name,
        a.title AS assignment_title,
        a.description AS assignment_description,
        s.subject_name
    FROM submissions sub
    JOIN users u ON sub.student_id = u.id
    JOIN assignments a ON sub.assignment_id = a.assignment_id
    LEFT JOIN subjects s ON a.subject_id = s.subject_id
    WHERE a.subject_id IN (
        SELECT subject_id FROM teacher_subjects WHERE teacher_id = ?
    )
    ORDER BY sub.status ASC, sub.submitted_at DESC
";

$stmt = $conn->prepare($submissionsQuery);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$submissionsResult = $stmt->get_result();
?>

<style>
    .table-container { overflow-x: auto; }
    .results-table { width: 100%; border-collapse: collapse; }
    .results-table th, .results-table td { padding: 0.9rem 1rem; border: 1px solid #e5e7eb; text-align: left; }
    .results-table thead th { background-color: #f9fafb; font-weight: 600; }
    .status-submitted { background-color: #fefce8; color: #a16207; }
    .status-marked { background-color: #dcfce7; color: #166534; }
    .status-cell { text-align: center; font-weight: bold; border-radius: 99px; padding: 0.25rem 0.75rem; font-size: 0.8rem; }
    .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5); }
    .modal-content { background-color: #fff; margin: 10% auto; padding: 2rem; border-radius: 0.5rem; max-width: 500px; box-shadow: var(--shadow-lg); }
    .modal-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e5e7eb; padding-bottom: 1rem; margin-bottom: 1rem; }
    .modal-title { font-size: 1.5rem; }
    .close-button { color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer; }
    .form-group { margin-bottom: 1rem; }
    .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 600; }
    .form-group input, .form-group textarea { width: 100%; padding: 0.75rem; border: 1px solid #e5e7eb; border-radius: 0.25rem; }
    .btn { padding: 0.5rem 1rem; border-radius: 0.25rem; text-decoration: none; display: inline-block; border: 1px solid transparent; cursor: pointer; }
    .btn-primary { background-color: var(--primary-color); color: var(--white); }
    .alert-success { padding: 1rem; background-color: #dcfce7; border: 1px solid #166534; color: #166534; border-radius: 0.5rem; margin-bottom: 1.5rem; }
</style>

<h1 class="page-title">Review Student Assignments</h1>

<?php if(isset($_GET['status']) && $_GET['status'] == 'marked'): ?>
    <div class="alert-success">Mark and feedback saved successfully!</div>
<?php endif; ?>

<div class="card">
    <div class="table-container">
        <table class="results-table">
            <thead>
                <tr>
                    <th>Subject</th>
                    <th>Assignment</th>
                    <th>Student</th>
                    <th>Submitted On</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($submissionsResult->num_rows > 0): ?>
                    <?php while ($row = $submissionsResult->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['subject_name'] ?? 'No Subject') ?></td>
                            <td>
                                <strong><?= htmlspecialchars($row['assignment_title'] ?? 'Untitled') ?></strong><br>
                                <small><?= htmlspecialchars($row['assignment_description'] ?? '') ?></small>
                            </td>
                            <td><?= htmlspecialchars($row['student_name']) ?></td>
                            <td><?= date("d M Y, H:i", strtotime($row['submitted_at'])) ?></td>
                            <td>
                                <span class="status-cell <?= $row['status'] === 'Marked' ? 'status-marked' : 'status-submitted' ?>">
                                    <?= htmlspecialchars($row['status']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($row['submission_file']): ?>
                                    <a href="../<?= htmlspecialchars($row['submission_file']) ?>" class="btn" download>
                                        <i class="fas fa-download"></i> Download
                                    </a>
                                <?php endif; ?>
                                <button class="btn btn-primary mark-btn" 
                                        data-submission-id="<?= $row['submission_id'] ?>"
                                        data-student-name="<?= htmlspecialchars($row['student_name']) ?>"
                                        data-current-score="<?= htmlspecialchars($row['score'] ?? '') ?>"
                                        data-current-feedback="<?= htmlspecialchars($row['feedback'] ?? '') ?>">
                                    <i class="fas fa-edit"></i> <?= $row['status'] === 'Marked' ? 'Edit Mark' : 'Mark' ?>
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 2rem;">No assignments have been submitted for your subjects yet.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="markingModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title">Mark Assignment</h2>
            <span class="close-button">&times;</span>
        </div>
        <p><strong>Student:</strong> <span id="modalStudentName"></span></p>
        <form action="review_assignments.php" method="post">
            <input type="hidden" id="modalSubmissionId" name="submission_id">
            <div class="form-group">
                <label for="modalScore">Score / Mark</label>
                <input type="number" step="0.01" id="modalScore" name="score" class="form-control" placeholder="e.g., 85.5" required>
            </div>
            <div class="form-group">
                <label for="modalFeedback">Feedback for Student</label>
                <textarea id="modalFeedback" name="feedback" class="form-control" rows="5" placeholder="Provide constructive feedback..."></textarea>
            </div>
            <button type="submit" name="mark_submission" class="btn btn-primary">Save Mark & Feedback</button>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('markingModal');
    const closeBtn = document.querySelector('.close-button');
    const markButtons = document.querySelectorAll('.mark-btn');

    markButtons.forEach(button => {
        button.addEventListener('click', function() {
            const submissionId = this.dataset.submissionId;
            const studentName = this.dataset.studentName;
            const currentScore = this.dataset.currentScore;
            const currentFeedback = this.dataset.currentFeedback;

            document.getElementById('modalSubmissionId').value = submissionId;
            document.getElementById('modalStudentName').textContent = studentName;
            document.getElementById('modalScore').value = currentScore;
            document.getElementById('modalFeedback').value = currentFeedback;
            
            modal.style.display = 'block';
        });
    });

    closeBtn.onclick = function() {
        modal.style.display = 'none';
    }
    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }
});
</script>

<?php
// Include the new footer to close the page layout
$stmt->close();
$conn->close();
include 'footer.php';
?>