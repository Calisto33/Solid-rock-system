<?php
// Set the page title for the header
$pageTitle = "Post Assignment";

// Include the header. It handles security, session, db connection, and the sidebar.
include 'header.php'; // This provides the initial $staff_id from the session ($_SESSION['user_id'])

// --- PHP LOGIC SPECIFIC TO THIS PAGE ---

// --- FIX: We MUST restore the logic to get the specific staff_id for your database design ---
$staffIdQuery = "SELECT staff_id FROM staff WHERE id = ?";
$stmt_staff = $conn->prepare($staffIdQuery);

if(!$stmt_staff) {
    die("Prepare failed for staff query: ". $conn->error);
}

$stmt_staff->bind_param("i", $staff_id); // $staff_id is from header.php (it's $_SESSION['user_id'])
$stmt_staff->execute();
$result_staff = $stmt_staff->get_result();
$staffData = $result_staff->fetch_assoc();
$stmt_staff->close();

if (!$staffData) {
    // This can happen if there's no entry in the 'staff' table that corresponds to the user in the 'users' table
    die("Error: This user is not registered correctly in the 'staff' table.");
}
// This is the correct ID to use for finding classes and saving assignments
$specific_staff_id_for_relations = $staffData['staff_id'];


// --- Fetch classes and subjects assigned to this staff member for the dropdowns ---
// This now uses the correct, specific staff ID
$assignedClassesQuery = "
    SELECT DISTINCT ss.class, ts.subject_name, ss.subject_id
    FROM staff_subject ss
    JOIN table_subject ts ON ss.subject_id = ts.subject_id
    WHERE ss.staff_id = ?"; // Using the ID from the `staff` table
$stmt_assigned = $conn->prepare($assignedClassesQuery);
$stmt_assigned->bind_param("i", $specific_staff_id_for_relations);
$stmt_assigned->execute();
$assignedClassesResult = $stmt_assigned->get_result();
$assignedClasses = [];
while ($row = $assignedClassesResult->fetch_assoc()) {
    $assignedClasses[] = $row;
}
$stmt_assigned->close();

// Handle form submission to post the assignment
$message = "";
$message_type = "success";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $assignment_title = trim($_POST['assignment_title']);
    $class = $_POST['class'];
    $subject_id = $_POST['subject_id'];
    $assignment_description = trim($_POST['assignment_description']);
    $assignment_link = trim($_POST['assignment_link'] ?? '');
    
    // Use the correct staff ID when saving the assignment
    $staff_id_to_save = $specific_staff_id_for_relations;

    // Handle file upload
    $assignment_file_path = null;
    if (isset($_FILES['assignment_file']) && $_FILES['assignment_file']['error'] == 0) {
        $targetDir = "../uploads/assignments/";
        $fileName = time() . '_' . basename($_FILES['assignment_file']['name']);
        $targetFilePath = $targetDir . $fileName;
        
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        if (move_uploaded_file($_FILES['assignment_file']['tmp_name'], $targetFilePath)) {
            $assignment_file_path = $targetFilePath;
        } else {
            $message = "Error uploading file.";
            $message_type = "error";
        }
    }

    if (empty($message)) { 
        // CORRECTED: Using the actual column names from your table
        $insertAssignmentQuery = "
            INSERT INTO assignments (staff_id, class, subject_id, assignment_title, assignment_description, assignment_file, assignment_link)
            VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt_insert = $conn->prepare($insertAssignmentQuery);
        
        if (!$stmt_insert) {
            die("Prepare failed for assignment insertion: " . $conn->error);
        }
        
        // Bind the correct staff ID and use the correct types (s, s, i, s, s, s, s)
        // Note: staff_id is varchar(255) in your table, so use 's' not 'i'
        // 7 parameters: staff_id, class, subject_id, assignment_title, assignment_description, assignment_file, assignment_link
        $stmt_insert->bind_param("ssissss", $staff_id_to_save, $class, $subject_id, $assignment_title, $assignment_description, $assignment_file_path, $assignment_link);

        if ($stmt_insert->execute()) {
            $message = "Assignment posted successfully.";
        } else {
            $message = "Error posting assignment: " . $stmt_insert->error;
            $message_type = "error";
        }
        $stmt_insert->close();
    }
}
?>

<style>
    /* Your CSS - Unchanged */
    .card-header { background-color: var(--primary-light); color: var(--primary-dark); padding: 1.5rem; }
    .card-title { font-size: 1.5rem; font-weight: 600; margin: 0; }
    .card-body { padding: 2rem; }
    .message { padding: 1rem; border-radius: 8px; margin-bottom: 2rem; display: flex; align-items: center; gap: 0.5rem; }
    .success-message { background-color: #dcfce7; color: #16a34a; }
    .error-message { background-color: #fee2e2; color: #ef4444; }
    .form-group { display: flex; flex-direction: column; gap: 0.6rem; }
    label { font-weight: 500; display: flex; align-items: center; gap: 0.5rem; }
    label i { color: var(--primary-color); }
    select, input[type="text"], input[type="url"], textarea { padding: 0.9rem 1rem; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 1rem; width: 100%; background-color: #f9fafc; }
    textarea { min-height: 150px; resize: vertical; }
    .file-input-container { position: relative; }
    .file-input-label { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 2rem; border: 2px dashed #cbd5e1; border-radius: 8px; background-color: #f9fafb; cursor: pointer; }
    .file-input-label:hover { border-color: var(--primary-light); }
    .file-input-label i { font-size: 2rem; color: var(--primary-color); margin-bottom: 0.5rem; }
    input[type="file"] { position: absolute; width: 100%; height: 100%; top: 0; left: 0; opacity: 0; cursor: pointer; }
    .divider { display: flex; align-items: center; text-align: center; color: #64748b; margin: 1.5rem 0; }
    .divider::before, .divider::after { content: ""; flex: 1; border-bottom: 1px solid #e2e8f0; }
    .divider::before { margin-right: 1rem; }
    .divider::after { margin-left: 1rem; }
    button[type="submit"] { background: linear-gradient(135deg, var(--primary-color), var(--primary-dark)); color: var(--white); padding: 1rem; border: none; border-radius: 8px; cursor: pointer; font-size: 1rem; font-weight: 500; }
</style>

<div class="card">
    <div class="card-header"><h3 class="card-title">Create New Assignment</h3></div>
    <div class="card-body">
        <?php if ($message): ?>
            <div class="message <?= $message_type === 'success' ? 'success-message' : 'error-message' ?>">
                <i class="fas <?= $message_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form action="post_assignments.php" method="POST" enctype="multipart/form-data" style="display: grid; gap: 2rem;">
            
            <div class="form-group">
                <label for="class"><i class="fas fa-users"></i> Select Class</label>
                <select name="class" id="class" class="form-control" required>
                    <option value="">-- Select a Class --</option>
                    <?php
                    $displayed_classes = [];
                    foreach ($assignedClasses as $item):
                        if (!in_array($item['class'], $displayed_classes)):
                            $displayed_classes[] = $item['class'];
                    ?>
                        <option value="<?= htmlspecialchars($item['class']) ?>"><?= strtoupper(htmlspecialchars($item['class'])) ?></option>
                    <?php
                        endif;
                    endforeach;
                    ?>
                </select>
            </div>

            <div class="form-group">
                <label for="assignment_title"><i class="fas fa-heading"></i> Assignment Title</label>
                <input type="text" name="assignment_title" id="assignment_title" placeholder="e.g., Chapter 5 Essay" required>
            </div>

            <div class="form-group">
                <label for="subject_id"><i class="fas fa-book"></i> Select Subject</label>
                <select name="subject_id" id="subject_id" class="form-control" required>
                    <option value="">-- Select a Subject --</option>
                     <?php foreach ($assignedClasses as $item): ?>
                         <option value="<?= htmlspecialchars($item['subject_id']) ?>"><?= htmlspecialchars($item['subject_name']) ?></option>
                     <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="assignment_description"><i class="fas fa-edit"></i> Assignment Description</label>
                <textarea name="assignment_description" id="assignment_description" class="form-control" placeholder="Enter detailed instructions..." required></textarea>
            </div>

            <div class="form-group">
                <label for="assignment_file" class="file-input-container">
                    <div class="file-input-label">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <span>Upload Assignment Document</span>
                        <small>Optional: Drag and drop or click to browse</small>
                    </div>
                    <input type="file" name="assignment_file" id="assignment_file">
                </label>
            </div>

            <div class="divider">OR</div>

            <div class="form-group">
                <label for="assignment_link"><i class="fas fa-link"></i> Add Link to Online Resource</label>
                <input type="url" name="assignment_link" id="assignment_link" class="form-control" placeholder="https://example.com/assignment-details">
            </div>

            <button type="submit"><i class="fas fa-paper-plane"></i> Post Assignment</button>
        </form>
    </div>
</div>

<script>
    document.getElementById('assignment_file').addEventListener('change', function(e) {
        const fileName = e.target.files[0]?.name || 'No file selected';
        const label = document.querySelector('.file-input-label span');
        label.textContent = fileName;
    });
</script>

<?php
// Include the new footer to close the page layout
$conn->close();
include 'footer.php';
?>