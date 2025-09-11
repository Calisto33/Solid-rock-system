<?php
session_start();
include '../config.php';

// Security check - ensure student is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}

$student_user_id = $_SESSION['user_id'];

// Get student details using user_id (corrected approach)
$studentData = null;
$student_id = null;

$studentQuery = "SELECT student_id, user_id, first_name, last_name, username, class, course, year FROM students WHERE user_id = ?";
$stmt_student = $conn->prepare($studentQuery);
$stmt_student->bind_param("i", $student_user_id);
$stmt_student->execute();
$studentResult = $stmt_student->get_result();
$studentData = $studentResult->fetch_assoc();
$stmt_student->close();

if (!$studentData) {
    die("Error: Student data not found. Please contact administrator.");
}

// Use the student_id from the students table
$student_id = $studentData['student_id'];

// Check what tables exist for submissions
$submissionsTableExists = $conn->query("SHOW TABLES LIKE 'submissions'")->num_rows > 0;
$studentReadAssignmentsExists = $conn->query("SHOW TABLES LIKE 'student_read_assignments'")->num_rows > 0;
$assignmentsTableExists = $conn->query("SHOW TABLES LIKE 'assignments'")->num_rows > 0;
$subjectsTableExists = $conn->query("SHOW TABLES LIKE 'subjects'")->num_rows > 0;

$submissions = [];

if ($submissionsTableExists) {
    // Build comprehensive query for submissions
    $submissionsQuery = "SELECT 
                            s.*,
                            CASE 
                                WHEN s.score IS NULL THEN 'pending'
                                WHEN s.score >= 80 THEN 'excellent'
                                WHEN s.score >= 70 THEN 'good' 
                                WHEN s.score >= 60 THEN 'satisfactory'
                                WHEN s.score >= 50 THEN 'needs_improvement'
                                WHEN s.score < 50 THEN 'unsatisfactory'
                                ELSE 'not_graded'
                            END as grade_category
                         FROM submissions s
                         WHERE s.student_id = ?
                         ORDER BY s.submitted_at DESC";

    // Try to get assignment and subject info if tables exist
    if ($assignmentsTableExists) {
        if ($subjectsTableExists) {
            // Check if assignments table has subject_id column
            $columnCheck = $conn->query("SHOW COLUMNS FROM assignments LIKE 'subject_id'");
            if ($columnCheck->num_rows > 0) {
                // Use subjects table with proper join
                $submissionsQuery = "SELECT 
                                        s.*,
                                        a.title as assignment_title,
                                        a.description as assignment_description,
                                        a.due_date,
                                        subj.subject_name,
                                        CASE 
                                            WHEN s.score IS NULL THEN 'pending'
                                            WHEN s.score >= 80 THEN 'excellent'
                                            WHEN s.score >= 70 THEN 'good' 
                                            WHEN s.score >= 60 THEN 'satisfactory'
                                            WHEN s.score >= 50 THEN 'needs_improvement'
                                            WHEN s.score < 50 THEN 'unsatisfactory'
                                            ELSE 'not_graded'
                                        END as grade_category
                                     FROM submissions s
                                     LEFT JOIN assignments a ON s.assignment_id = a.assignment_id
                                     LEFT JOIN subjects subj ON a.subject_id = subj.subject_id
                                     WHERE s.student_id = ?
                                     ORDER BY s.submitted_at DESC";
            } else {
                // assignments table doesn't have subject_id, use just assignments
                $submissionsQuery = "SELECT 
                                        s.*,
                                        a.title as assignment_title,
                                        a.description as assignment_description,
                                        a.due_date,
                                        'General' as subject_name,
                                        CASE 
                                            WHEN s.score IS NULL THEN 'pending'
                                            WHEN s.score >= 80 THEN 'excellent'
                                            WHEN s.score >= 70 THEN 'good' 
                                            WHEN s.score >= 60 THEN 'satisfactory'
                                            WHEN s.score >= 50 THEN 'needs_improvement'
                                            WHEN s.score < 50 THEN 'unsatisfactory'
                                            ELSE 'not_graded'
                                        END as grade_category
                                     FROM submissions s
                                     LEFT JOIN assignments a ON s.assignment_id = a.assignment_id
                                     WHERE s.student_id = ?
                                     ORDER BY s.submitted_at DESC";
            }
        } else {
            // Just assignments table, no subjects table
            $submissionsQuery = "SELECT 
                                    s.*,
                                    a.title as assignment_title,
                                    a.description as assignment_description,
                                    a.due_date,
                                    'General' as subject_name,
                                    CASE 
                                        WHEN s.score IS NULL THEN 'pending'
                                        WHEN s.score >= 80 THEN 'excellent'
                                        WHEN s.score >= 70 THEN 'good' 
                                        WHEN s.score >= 60 THEN 'satisfactory'
                                        WHEN s.score >= 50 THEN 'needs_improvement'
                                        WHEN s.score < 50 THEN 'unsatisfactory'
                                        ELSE 'not_graded'
                                    END as grade_category
                                 FROM submissions s
                                 LEFT JOIN assignments a ON s.assignment_id = a.assignment_id
                                 WHERE s.student_id = ?
                                 ORDER BY s.submitted_at DESC";
        }
    }

    $stmt = $conn->prepare($submissionsQuery);
    if ($stmt) {
        $stmt->bind_param("s", $student_id);
        if ($stmt->execute()) {
            $submissionsResult = $stmt->get_result();
            while ($row = $submissionsResult->fetch_assoc()) {
                // Add default values for missing data
                $row['assignment_title'] = $row['assignment_title'] ?? 'Assignment #' . $row['assignment_id'];
                $row['subject_name'] = $row['subject_name'] ?? 'General';
                $submissions[] = $row;
            }
        }
        $stmt->close();
    }
} 

// If no submissions found, let's check student_read_assignments as fallback
if (empty($submissions) && $studentReadAssignmentsExists) {
    $readAssignmentsQuery = "SELECT 
                                sra.*,
                                'viewed' as status,
                                NULL as score,
                                NULL as feedback,
                                'not_submitted' as grade_category,
                                sra.read_at as submitted_at
                             FROM student_read_assignments sra
                             WHERE sra.user_id = ?
                             ORDER BY sra.read_at DESC";

    $stmt = $conn->prepare($readAssignmentsQuery);
    if ($stmt) {
        $stmt->bind_param("i", $student_user_id);
        if ($stmt->execute()) {
            $readResult = $stmt->get_result();
            while ($row = $readResult->fetch_assoc()) {
                $row['assignment_title'] = 'Assignment #' . ($row['assignment_id'] ?? 'Unknown');
                $row['subject_name'] = 'General';
                $row['submission_text'] = 'Assignment viewed on ' . date('F j, Y', strtotime($row['read_at']));
                $submissions[] = $row;
            }
        }
        $stmt->close();
    }
}

// No sample data - only show real database submissions
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Submissions - Wisetech College</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --bg-color: #f4f7fe;
            --primary-text: #27272a;
            --secondary-text: #6b7280;
            --accent-purple: #7c3aed;
            --accent-blue: #3b82f6;
            --border-color: #eef2f9;
            --success-color: #166534;
            --warning-color: #a16207;
            --error-color: #dc2626;
            --rounded-lg: 0.75rem;
            --rounded-xl: 1rem;
            --shadow: 0 4px 15px -3px rgba(0, 0, 0, 0.05);
            --shadow-lg: 0 10px 25px -5px rgba(0, 0, 0, 0.07), 0 5px 10px -5px rgba(0, 0, 0, 0.04);
            --transition: all 0.3s ease;
        }

        body {
            background-color: var(--bg-color);
            font-family: 'Segoe UI', system-ui, -apple-system, BlinkMacSystemFont, sans-serif;
            color: var(--primary-text);
            line-height: 1.6;
        }

        .navbar {
            background: linear-gradient(135deg, var(--accent-purple), var(--accent-blue));
            box-shadow: var(--shadow-lg);
        }

        .navbar-brand {
            font-weight: 700;
            color: white !important;
        }

        .navbar-nav .nav-link {
            color: rgba(255, 255, 255, 0.9) !important;
            font-weight: 500;
            transition: var(--transition);
        }

        .navbar-nav .nav-link:hover {
            color: white !important;
            transform: translateY(-1px);
        }

        .page-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-text);
            margin-bottom: 0.5rem;
        }

        .page-subtitle {
            color: var(--secondary-text);
            font-size: 1.1rem;
            margin-bottom: 2rem;
        }

        .container-fluid {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }

        .card {
            border: none;
            border-radius: var(--rounded-xl);
            box-shadow: var(--shadow);
            transition: var(--transition);
            overflow: hidden;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .card-header {
            background: linear-gradient(135deg, var(--accent-purple), var(--accent-blue));
            color: white;
            border: none;
            padding: 1.25rem;
        }

        .card-title {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .badge {
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.875rem;
        }

        .badge.bg-success {
            background-color: var(--success-color) !important;
        }

        .badge.bg-warning {
            background-color: var(--warning-color) !important;
        }

        .badge.bg-danger {
            background-color: var(--error-color) !important;
        }

        .badge.bg-primary {
            background-color: var(--accent-blue) !important;
        }

        .bg-light-custom {
            background-color: #f8f9fa;
            border-radius: var(--rounded-lg);
            padding: 1rem;
            margin-top: 0.5rem;
        }

        .btn-outline-primary {
            color: var(--accent-purple);
            border-color: var(--accent-purple);
        }

        .btn-outline-primary:hover {
            background-color: var(--accent-purple);
            border-color: var(--accent-purple);
        }

        .btn-primary {
            background-color: var(--accent-purple);
            border-color: var(--accent-purple);
        }

        .btn-primary:hover {
            background-color: var(--accent-blue);
            border-color: var(--accent-blue);
        }

        .text-muted {
            color: var(--secondary-text) !important;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
        }

        .empty-state i {
            font-size: 4rem;
            color: var(--border-color);
            margin-bottom: 1rem;
        }

        .back-button {
            position: fixed;
            top: 20px;
            left: 20px;
            background: var(--accent-purple);
            color: white;
            padding: 0.75rem 1rem;
            border-radius: var(--rounded-lg);
            text-decoration: none;
            font-weight: 600;
            box-shadow: var(--shadow);
            transition: var(--transition);
            z-index: 1000;
        }

        .back-button:hover {
            background: var(--accent-blue);
            color: white;
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        /* Student Info Card */
        .student-info-card {
            background: linear-gradient(135deg, rgba(124, 58, 237, 0.1), rgba(59, 130, 246, 0.1));
            border: 1px solid rgba(124, 58, 237, 0.2);
        }

        .student-info-card .card-body {
            padding: 1.5rem;
        }

        .student-info-card h5 {
            color: var(--accent-purple);
            font-weight: 700;
            margin-bottom: 1rem;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .page-title {
                font-size: 2rem;
            }

            .container-fluid {
                padding: 1rem 0.5rem;
            }

            .card-body {
                padding: 1rem;
            }

            .back-button {
                position: static;
                margin-bottom: 1rem;
                width: 100%;
                text-align: center;
            }
        }

        @media (max-width: 576px) {
            .col-md-6 {
                margin-bottom: 1rem;
            }

            .badge {
                font-size: 0.75rem;
                padding: 0.375rem 0.75rem;
            }
        }

        /* Print styles */
        @media print {
            .back-button,
            .btn {
                display: none !important;
            }

            .card {
                box-shadow: none;
                border: 1px solid #ccc;
            }

            .card-header {
                background: #f8f9fa !important;
                color: #333 !important;
            }
        }
    </style>
</head>
<body>
    <a href="student_dashboard.php" class="back-button">
        <i class="fas fa-arrow-left"></i> Back to Dashboard
    </a>

    <div class="container-fluid">
        <h1 class="page-title">My Submissions</h1>
        <p class="page-subtitle">Track your assignment submissions and grades</p>

        <!-- Student Info -->
        <div class="card mb-4 student-info-card">
            <div class="card-body">
                <h5><i class="fas fa-user-graduate"></i> Student Information</h5>
                <div class="row">
                    <div class="col-md-4">
                        <p><strong>Name:</strong> 
                            <?php 
                            $displayName = trim(($studentData['first_name'] ?? '') . ' ' . ($studentData['last_name'] ?? ''));
                            if (empty($displayName)) {
                                $displayName = $studentData['username'] ?? 'Unknown Student';
                            }
                            echo htmlspecialchars($displayName);
                            ?>
                        </p>
                    </div>
                    <div class="col-md-4">
                        <p><strong>Student ID:</strong> <?= htmlspecialchars($studentData['student_id'] ?? 'N/A') ?></p>
                    </div>
                    <div class="col-md-4">
                        <p><strong>Class:</strong> <?= htmlspecialchars($studentData['class'] ?? 'N/A') ?></p>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!empty($submissions)): ?>
            <div class="row">
                <?php foreach ($submissions as $submission): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <?= htmlspecialchars($submission['assignment_title'] ?? 'Assignment #' . ($submission['assignment_id'] ?? $submission['submission_id'])) ?>
                                </h5>
                                <small class="text-light">
                                    <?= htmlspecialchars($submission['subject_name'] ?? 'General') ?>
                                </small>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <strong>Status:</strong>
                                    <?php 
                                    $status = $submission['status'] ?? 'submitted';
                                    $badgeClass = 'bg-secondary';
                                    
                                    switch (strtolower($status)) {
                                        case 'submitted':
                                            $badgeClass = 'bg-success';
                                            break;
                                        case 'graded':
                                        case 'marked':
                                            $badgeClass = 'bg-primary';
                                            break;
                                        case 'pending':
                                            $badgeClass = 'bg-warning';
                                            break;
                                        case 'viewed':
                                            $badgeClass = 'bg-info';
                                            break;
                                    }
                                    ?>
                                    <span class="badge <?= $badgeClass ?>">
                                        <?= ucfirst(htmlspecialchars($status)) ?>
                                    </span>
                                </div>

                                <div class="mb-3">
                                    <strong>Submitted:</strong><br>
                                    <small class="text-muted">
                                        <?php 
                                        $submitDate = $submission['submitted_at'] ?? $submission['read_at'] ?? date('Y-m-d H:i:s');
                                        echo date('F j, Y, g:i a', strtotime($submitDate));
                                        ?>
                                    </small>
                                </div>

                                <?php if (!empty($submission['teacher_name'])): ?>
                                <div class="mb-3">
                                    <strong>Teacher:</strong><br>
                                    <small class="text-muted"><?= htmlspecialchars($submission['teacher_name']) ?></small>
                                </div>
                                <?php endif; ?>

                                <?php if (!empty($submission['score'])): ?>
                                <div class="mb-3">
                                    <strong>Score:</strong>
                                    <?php 
                                    $score = (float)$submission['score'];
                                    $maxScore = (float)($submission['max_score'] ?? 100);
                                    $percentage = ($score / $maxScore) * 100;
                                    $gradeCategory = $submission['grade_category'] ?? 'not_graded';
                                    
                                    $scoreClass = 'bg-secondary';
                                    switch ($gradeCategory) {
                                        case 'excellent':
                                            $scoreClass = 'bg-success';
                                            break;
                                        case 'good':
                                            $scoreClass = 'bg-info';
                                            break;
                                        case 'satisfactory':
                                            $scoreClass = 'bg-warning';
                                            break;
                                        case 'needs_improvement':
                                        case 'unsatisfactory':
                                            $scoreClass = 'bg-danger';
                                            break;
                                    }
                                    ?>
                                    <span class="badge <?= $scoreClass ?> fs-6">
                                        <?= number_format($score, 1) ?>/<?= number_format($maxScore, 0) ?> 
                                        (<?= number_format($percentage, 1) ?>%)
                                    </span>
                                    <div class="mt-1">
                                        <small class="text-muted">
                                            Grade: <?= ucfirst(str_replace('_', ' ', $gradeCategory)) ?>
                                        </small>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <?php if (!empty($submission['submission_text'])): ?>
                                <div class="mb-3">
                                    <strong>My Submission:</strong>
                                    <div class="bg-light-custom">
                                        <small><?= nl2br(htmlspecialchars(substr($submission['submission_text'], 0, 200))) ?>
                                        <?= strlen($submission['submission_text']) > 200 ? '...' : '' ?>
                                        </small>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <?php if (!empty($submission['feedback'])): ?>
                                <div class="mb-3">
                                    <strong>Teacher Feedback:</strong>
                                    <div class="bg-light-custom">
                                        <small><?= nl2br(htmlspecialchars($submission['feedback'])) ?></small>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <?php if (!empty($submission['comments'])): ?>
                                <div class="mb-3">
                                    <strong>My Comments:</strong>
                                    <div class="bg-light-custom">
                                        <small><?= nl2br(htmlspecialchars($submission['comments'])) ?></small>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="card-footer bg-transparent">
                                <?php if (!empty($submission['submission_file']) && file_exists($submission['submission_file'])): ?>
                                    <a href="<?= htmlspecialchars($submission['submission_file']) ?>" class="btn btn-sm btn-outline-primary" download>
                                        <i class="fas fa-download"></i> Download My Work
                                    </a>
                                <?php else: ?>
                                    <small class="text-muted">
                                        <i class="fas fa-info-circle"></i> No file attachment
                                    </small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-body empty-state">
                    <i class="fas fa-inbox"></i>
                    <h5>No Submissions Yet</h5>
                    <p class="text-muted">You haven't submitted any assignments yet.</p>
                    <a href="view_assignment.php" class="btn btn-primary">
                        <i class="fas fa-tasks"></i> View Available Assignments
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php 
$conn->close();
?>