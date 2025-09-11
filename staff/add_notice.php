<?php
// Set the page title for the header
$pageTitle = "Add Notice";

// Include the new header. It handles security, session, db connection, and the sidebar.
include 'header.php';

// --- PHP LOGIC SPECIFIC TO THE ADD NOTICE PAGE ---

// NOTE: header.php already provides $staff_id as the user's ID from the session.
// This page uses a specific staff_id from the `staff` table, so we fetch it here.
$staffIdQuery = "SELECT staff_id FROM staff WHERE id = ?";
$stmt_staff = $conn->prepare($staffIdQuery);
$stmt_staff->bind_param("i", $staff_id); // $staff_id comes from header.php
$stmt_staff->execute();
$result_staff = $stmt_staff->get_result();
$staffData = $result_staff->fetch_assoc();
$stmt_staff->close();

if (!$staffData) {
    die("Error: Staff member not found in the staff table.");
}
$specific_staff_id = $staffData['staff_id']; // Use this for notice queries
$assignedClassesQuery = "
    SELECT ss.class_id AS class, ts.subject_name, ss.subject_id
    FROM teacher_subjects ss
    JOIN table_subject ts ON ss.subject_id = ts.subject_id
    WHERE ss.teacher_id = ?";
$stmt_assigned = $conn->prepare($assignedClassesQuery);
$stmt_assigned->bind_param("i", $specific_staff_id);
$stmt_assigned->execute();
$assignedClassesResult = $stmt_assigned->get_result();
$assignedClasses = [];
while ($row = $assignedClassesResult->fetch_assoc()) {
    $assignedClasses[] = $row;
}
$stmt_assigned->close();

// Handle form submission to create a notice
$message = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $class = $_POST['class'];
    $subject_id = $_POST['subject_id'];
    $notice_content = trim($_POST['notice_content']);

    $insertNoticeQuery = "INSERT INTO notices (staff_id, class, subject_id, notice_content) VALUES (?, ?, ?, ?)";
    $insertStmt = $conn->prepare($insertNoticeQuery);
    $insertStmt->bind_param("isss", $specific_staff_id, $class, $subject_id, $notice_content);

    if ($insertStmt->execute()) {
        $message = "Notice sent successfully!";
    } else {
        $message = "Error sending notice: " . $conn->error;
    }
    $insertStmt->close();
}
?>

<style>
    .card-header {
        background-color: var(--primary-light);
        color: white;
        padding: 1.5rem;
    }
    .card-title {
        font-size: 1.5rem;
        font-weight: 600;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    .card-body {
        padding: 2rem;
    }
    .alert-success {
        padding: 1rem 1.5rem;
        border-radius: 0.5rem;
        margin-bottom: 1.5rem;
        background-color: #dcfce7;
        color: #16a34a;
        border-left: 4px solid #16a34a;
    }
    .form-container {
        display: grid;
        gap: 1.5rem;
    }
    .form-group {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }
    .form-label {
        font-weight: 500;
        color: var(--text-secondary);
    }
    .form-control {
        padding: 0.75rem 1rem;
        border: 1px solid #e2e8f0;
        border-radius: 0.5rem;
        font-size: 1rem;
        width: 100%;
        background-color: #f8fafc;
    }
    textarea.form-control {
        min-height: 180px;
        resize: vertical;
    }
    .submit-container {
        margin-top: 1rem;
        display: flex;
        justify-content: flex-end;
    }
    .btn-success {
        background-color: #059669;
        color: white;
    }
</style>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-bullhorn"></i>
            <span>Send Notice to Students</span>
        </h3>
    </div>
    <div class="card-body">
        <?php if ($message): ?>
            <div class="alert-success">
                <i class="fas fa-check-circle"></i>
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form action="add_notice.php" method="POST" class="form-container">
            <div class="form-group">
                <label for="class" class="form-label"><i class="fas fa-users"></i> Select Class</label>
                <select name="class" id="class" class="form-control" required>
                    <option value="">-- Select Class --</option>
                    <?php
                    // To avoid duplicate class names in the dropdown
                    $displayed_classes = [];
                    foreach ($assignedClasses as $classSubject):
                        if (!in_array($classSubject['class'], $displayed_classes)):
                            $displayed_classes[] = $classSubject['class'];
                    ?>
                        <option value="<?= htmlspecialchars($classSubject['class']) ?>">
                            <?= strtoupper(htmlspecialchars($classSubject['class'])) ?>
                        </option>
                    <?php 
                        endif;
                    endforeach; 
                    ?>
                </select>
            </div>

            <div class="form-group">
                <label for="subject_id" class="form-label"><i class="fas fa-book"></i> Select Subject</label>
                <select name="subject_id" id="subject_id" class="form-control" required>
                    <option value="">-- Select Subject --</option>
                    <?php foreach ($assignedClasses as $classSubject): ?>
                        <option value="<?= htmlspecialchars($classSubject['subject_id']) ?>">
                            <?= htmlspecialchars($classSubject['subject_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="notice_content" class="form-label"><i class="fas fa-edit"></i> Notice Content</label>
                <textarea name="notice_content" id="notice_content" class="form-control" required placeholder="Type your notice here..."></textarea>
            </div>

            <div class="submit-container">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-paper-plane"></i> Send Notice
                </button>
            </div>
        </form>
    </div>
</div>

<?php
// Include the new footer to close the page layout
$conn->close();
include 'footer.php';
?>