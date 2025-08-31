<?php
// Set the page title for this specific page
$pageTitle = "My Subjects & Classes";

// Include the new header. It handles security, session, db connection, and the sidebar.
include 'header.php';

// --- PHP LOGIC SPECIFIC TO THIS PAGE ---

// Fetch the specific staff_id from the staff table based on the user_id from the session
$staffIdQuery = "SELECT staff_id FROM staff WHERE id = ?";
$stmt_staff = $conn->prepare($staffIdQuery);
if (!$stmt_staff) die("Prepare failed: " . $conn->error);
$stmt_staff->bind_param("i", $staff_id); // $staff_id comes from header.php
$stmt_staff->execute();
$result_staff = $stmt_staff->get_result();
$staffData = $result_staff->fetch_assoc();
$stmt_staff->close();

if (!$staffData) {
    die("Error: This user is not registered in the 'staff' table.");
}
$specific_staff_id = $staffData['staff_id'];


// This corrected query uses a subquery to accurately count students
// who are in the correct class AND enrolled in the correct subject.
$subjectsQuery = "
    SELECT 
        ss.class, 
        ts.subject_name, 
        ss.subject_id,
        (SELECT COUNT(DISTINCT s.id) 
         FROM students s
         JOIN student_subject st_sub ON s.id = st_sub.student_id
         WHERE s.class = ss.class AND st_sub.subject_id = ss.subject_id) AS student_count
    FROM staff_subject ss
    JOIN table_subject ts ON ss.subject_id = ts.subject_id
    WHERE ss.staff_id = ?
    ORDER BY ss.class, ts.subject_name
";
$stmt_subjects = $conn->prepare($subjectsQuery);

if (!$stmt_subjects) {
    die("Error preparing subjects query: " . $conn->error);
}

$stmt_subjects->bind_param("i", $specific_staff_id);
$stmt_subjects->execute();
$subjectsResult = $stmt_subjects->get_result();
?>

<style>
    .page-header { margin-bottom: 2rem; text-align: center; }
    .page-title { font-size: 1.75rem; color: var(--primary-dark); margin-bottom: 0.5rem; font-weight: 600; }
    .page-description { color: var(--text-light); max-width: 700px; margin: 0 auto; }
    .card-header { background-color: var(--primary-color); color: var(--white); padding: 1.25rem 1.5rem; }
    .card-title { font-size: 1.25rem; font-weight: 500; }
    .card-body { padding: 0; }
    .table-container { overflow-x: auto; }
    .table { width: 100%; border-collapse: collapse; }
    .table th, .table td { padding: 1rem 1.5rem; text-align: left; border-bottom: 1px solid #e2e8f0; }
    .table th { background-color: #f1f5f9; font-weight: 600; text-transform: uppercase; font-size: 0.875rem; }
    .table tr:last-child td { border-bottom: none; }
    .table tr:hover { background-color: #f8fafc; }
    .badge {
        display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.25rem 0.75rem;
        border-radius: 9999px; font-size: 0.8rem; font-weight: 600;
    }
    .badge-primary { background-color: #dbeafe; color: #1e40af; }
    .empty-state { text-align: center; padding: 3rem 1.5rem; }
    .empty-state i { font-size: 3rem; color: #cbd5e0; margin-bottom: 1rem; }
    .empty-state-text { font-size: 1.125rem; color: var(--light-text); }
</style>

<div class="page-header">
    <h1 class="page-title">Your Subjects & Classes</h1>
    <p class="page-description">View and manage all subjects and classes under your supervision.</p>
</div>

<div class="card">
    <div class="card-header"><h2 class="card-title">My Assigned Classes</h2></div>
    <div class="card-body">
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Class</th>
                        <th>Subject</th>
                        <th>Enrolled Students</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($subjectsResult->num_rows > 0): ?>
                        <?php while ($subject = $subjectsResult->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($subject['class']) ?></td>
                                <td><?= htmlspecialchars($subject['subject_name']) ?></td>
                                <td>
                                    <span class="badge badge-primary">
                                        <i class="fas fa-users"></i> <?= htmlspecialchars($subject['student_count']) ?>
                                    </span>
                                </td>
                                <td><span class="badge badge-primary">Active</span></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4">
                                <div class="empty-state">
                                    <i class="fas fa-book"></i>
                                    <p class="empty-state-text">You have not been assigned to any subjects yet.</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
// Include the new footer to close the page layout
$stmt_subjects->close();
$conn->close();
include 'footer.php';
?>