<?php
session_start();
include '../config.php';

// Security check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    header("Location: ../login.php");
    exit();
}

// Check if submission ID is provided
if (!isset($_GET['submission_id']) || !is_numeric($_GET['submission_id'])) {
    die("Error: Invalid submission ID.");
}

$submission_id = (int)$_GET['submission_id'];
$user_id = $_SESSION['user_id'];

// Get staff_id
$staffIdQuery = "SELECT staff_id FROM staff WHERE id = ?";
$stmt_staff = $conn->prepare($staffIdQuery);
$stmt_staff->bind_param("i", $user_id);
$stmt_staff->execute();
$result_staff = $stmt_staff->get_result();
$staffData = $result_staff->fetch_assoc();
$stmt_staff->close();

if (!$staffData) {
    die("Error: Staff record not found.");
}

$teacher_staff_id = $staffData['staff_id'];

// Handle form submission for grading
$message = "";
$message_type = "success";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $score = $_POST['score'];
    $feedback = trim($_POST['feedback']);
    $status = 'graded';
    
    // Validate score
    if (!is_numeric($score) || $score < 0 || $score > 100) {
        $message = "Please enter a valid score between 0 and 100.";
        $message_type = "error";
    } else {
        // Update submission with grade and feedback
        $updateQuery = "UPDATE submissions SET score = ?, feedback = ?, status = ? WHERE submission_id = ?";
        $stmt_update = $conn->prepare($updateQuery);
        $stmt_update->bind_param("dssi", $score, $feedback, $status, $submission_id);
        
        if ($stmt_update->execute()) {
            $message = "Assignment graded successfully!";
            $message_type = "success";
        } else {
            $message = "Error updating grade: " . $stmt_update->error;
            $message_type = "error";
        }
        $stmt_update->close();
    }
}

// Fetch submission details with student and assignment info
$submissionQuery = "SELECT 
                        s.submission_id,
                        s.submission_file,
                        s.comments,
                        s.submitted_at,
                        s.status,
                        s.score,
                        s.feedback,
                        s.student_id,
                        a.assignment_id,
                        a.assignment_title,
                        a.class,
                        a.staff_id,
                        ts.subject_name,
                        COALESCE(CONCAT(st.first_name, ' ', st.last_name), 'Unknown Student') as student_name,
                        st.username as student_username
                    FROM submissions s
                    JOIN assignments a ON s.assignment_id = a.assignment_id
                    JOIN table_subject ts ON a.subject_id = ts.subject_id
                    LEFT JOIN students st ON s.student_id = st.id
                    WHERE s.submission_id = ? AND a.staff_id = ?";

$stmt = $conn->prepare($submissionQuery);
$stmt->bind_param("is", $submission_id, $teacher_staff_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Error: Submission not found or you don't have permission to grade it.");
}

$submission = $result->fetch_assoc();
$pageTitle = "Grade Submission - " . htmlspecialchars($submission['assignment_title']);

include 'header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-8">
            <h1 class="page-title">Grade Submission</h1>
            
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?= $message_type == 'success' ? 'success' : 'danger' ?>" role="alert">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <!-- Assignment Details -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Assignment Details</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Title:</strong> <?= htmlspecialchars($submission['assignment_title']) ?></p>
                            <p><strong>Subject:</strong> <?= htmlspecialchars($submission['subject_name']) ?></p>
                            <p><strong>Class:</strong> <?= htmlspecialchars($submission['class']) ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Student:</strong> <?= htmlspecialchars($submission['student_name']) ?></p>
                            <p><strong>Submitted:</strong> <?= date('F j, Y, g:i a', strtotime($submission['submitted_at'])) ?></p>
                            <p><strong>Current Status:</strong> 
                                <span class="badge bg-<?= $submission['status'] == 'graded' ? 'success' : 'warning' ?>">
                                    <?= ucfirst(htmlspecialchars($submission['status'])) ?>
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Student's Work -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Student's Submission</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($submission['submission_file'])): ?>
                        <div class="mb-3">
                            <strong>Submitted File:</strong><br>
                            <a href="<?= htmlspecialchars($submission['submission_file']) ?>" class="btn btn-outline-primary btn-sm" download>
                                <i class="fas fa-download"></i> Download Student's Work
                            </a>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($submission['comments'])): ?>
                        <div class="mb-3">
                            <strong>Student's Comments:</strong>
                            <div class="bg-light p-3 rounded">
                                <p class="mb-0"><?= nl2br(htmlspecialchars($submission['comments'])) ?></p>
                            </div>
                        </div>
                    <?php else: ?>
                        <p><em>No comments provided by the student.</em></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Grading Panel -->
        <div class="col-md-4">
            <div class="card sticky-top">
                <div class="card-header">
                    <h5>Grade This Submission</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label for="score" class="form-label">Score (0-100)</label>
                            <input type="number" 
                                   class="form-control" 
                                   id="score" 
                                   name="score" 
                                   min="0" 
                                   max="100" 
                                   step="0.1"
                                   value="<?= htmlspecialchars($submission['score'] ?? '') ?>" 
                                   required>
                            <div class="form-text">Enter a score between 0 and 100</div>
                        </div>

                        <div class="mb-3">
                            <label for="feedback" class="form-label">Feedback</label>
                            <textarea class="form-control" 
                                      id="feedback" 
                                      name="feedback" 
                                      rows="6" 
                                      placeholder="Provide detailed feedback to help the student improve..."><?= htmlspecialchars($submission['feedback'] ?? '') ?></textarea>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> <?= !empty($submission['score']) ? 'Update Grade' : 'Submit Grade' ?>
                            </button>
                        </div>
                    </form>

                    <?php if (!empty($submission['score'])): ?>
                        <hr>
                        <div class="text-center">
                            <h6>Current Grade</h6>
                            <span class="badge bg-<?= $submission['score'] >= 70 ? 'success' : ($submission['score'] >= 50 ? 'warning' : 'danger') ?> fs-5">
                                <?= htmlspecialchars($submission['score']) ?>%
                            </span>
                        </div>
                    <?php endif; ?>

                    <hr>
                    <div class="d-grid">
                        <a href="view_submissions.php?assignment_id=<?= $submission['assignment_id'] ?>" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Back to All Submissions
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
include 'footer.php'; 
?>