<?php
// Use the "smart" session start from the header file
// This prevents the "session already active" notice.
include 'header.php'; 

// The security check is likely handled in header.php now, but it's good practice
// to ensure the specific role is checked on the page itself too.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    header("Location: ../login.php");
    exit();
}

// FIX: Get the correct staff_id from the staff table
$user_id = $_SESSION['user_id']; // This is the ID from the users table
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

// This is the correct staff_id to use for finding assignments
$staff_id = $staffData['staff_id'];

// Set the page title
$pageTitle = "My Assignments";

// --- DATABASE QUERY ---
// This query has been corrected to match your table structure exactly.
$query = "SELECT 
            a.assignment_id, 
            a.assignment_title, 
            ts.subject_name,
            a.class,
            a.created_at,
            (SELECT COUNT(*) FROM submissions s WHERE s.assignment_id = a.assignment_id) AS submission_count
          FROM assignments a
          JOIN table_subject ts ON a.subject_id = ts.subject_id
          WHERE a.staff_id = ?  -- Using the correct staff_id
          ORDER BY a.created_at DESC";

$stmt = $conn->prepare($query);
if (!$stmt) {
    // This will show a detailed error if the query preparation fails
    die("Database query failed: " . $conn->error);
}

// FIXED: Use string parameter since staff_id is varchar(255)
$stmt->bind_param("s", $staff_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<div class="container-fluid">
    <h1 class="page-title">My Assignments Dashboard</h1>
    <p class="page-subtitle">Here are all the assignments you have created. Click "View Submissions" to see student work.</p>

    <!-- DEBUG INFO - Remove this after testing
    <div class="alert alert-info">
        <strong>Debug Info:</strong> 
        User ID: <?= $_SESSION['user_id'] ?> | 
        Staff ID: <?= htmlspecialchars($staff_id) ?> | 
        Total Assignments Found: <?= $result->num_rows ?>
    </div> -->

    <div class="card">
        <div class="card-body">
            <?php if ($result->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Assignment Title</th>
                                <th>Subject</th>
                                <th>Class</th>
                                <th>Date Posted</th>
                                <th>Submissions Received</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($assignment = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($assignment['assignment_title']) ?></td>
                                    <td><?= htmlspecialchars($assignment['subject_name']) ?></td>
                                    <td><?= htmlspecialchars($assignment['class']) ?></td>
                                    <td><?= date('F j, Y', strtotime($assignment['created_at'])) ?></td>
                                    <td>
                                        <span class="badge bg-primary rounded-pill">
                                            <?= $assignment['submission_count'] ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <a href="view_submissions.php?assignment_id=<?= $assignment['assignment_id'] ?>" class="btn btn-primary btn-sm">
                                            <i class="fas fa-eye"></i> View Submissions
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center p-4">
                    <p class="mb-0">You have not created any assignments yet.</p>
                    <a href="post_assignments.php" class="btn btn-success mt-3">Create Your First Assignment</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>