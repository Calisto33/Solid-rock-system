<?php
// Set the page title for the header
$pageTitle = "Staff File Database";

// Include the new header. It handles security, session, db connection, and the sidebar.
include 'header.php';

// --- PHP LOGIC SPECIFIC TO THIS PAGE ---

// Fetch the specific staff_id from the staff table based on the user_id from the session
$staffIdQuery = "SELECT staff_id FROM staff WHERE id = ?";
$stmt_staff = $conn->prepare($staffIdQuery);
if(!$stmt_staff) die("Prepare failed: ". $conn->error);
$stmt_staff->bind_param("i", $staff_id); // $staff_id is from header.php
$stmt_staff->execute();
$result_staff = $stmt_staff->get_result();
$staffData = $result_staff->fetch_assoc();
$stmt_staff->close();

if (!$staffData) {
    die("Error: This user is not registered in the 'staff' table.");
}
$specific_staff_id = $staffData['staff_id'];

// Handle file upload
$message = "";
$message_type = "success";
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_file'])) {
    $description = trim($_POST['description']);
    $file = $_FILES['file'];

    if ($file['error'] == 0) {
        $uploadDir = "../uploads/staff_files/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileName = uniqid() . "_" . basename($file["name"]);
        $originalName = basename($file["name"]);
        $targetFilePath = $uploadDir . $fileName;

        if (move_uploaded_file($file["tmp_name"], $targetFilePath)) {
            $insertFileQuery = "INSERT INTO staff_files (staff_id, file_name, original_name, description) VALUES (?, ?, ?, ?)";
            $stmt_insert = $conn->prepare($insertFileQuery);
            if ($stmt_insert) {
                $stmt_insert->bind_param("isss", $specific_staff_id, $fileName, $originalName, $description);
                if ($stmt_insert->execute()) {
                    $message = "File uploaded successfully.";
                } else {
                    $message = "Error saving file details: " . $stmt_insert->error;
                    $message_type = "error";
                }
                $stmt_insert->close();
            }
        } else {
            $message = "Error moving the uploaded file.";
            $message_type = "error";
        }
    } else {
        $message = "No file selected or file upload error. Error Code: " . $file['error'];
        $message_type = "error";
    }
}

// Fetch all files uploaded by this staff member
$filesQuery = "SELECT * FROM staff_files WHERE staff_id = ?";
$stmt_files = $conn->prepare($filesQuery);
$filesResult = null;
if ($stmt_files) {
    $stmt_files->bind_param("i", $specific_staff_id);
    $stmt_files->execute();
    $filesResult = $stmt_files->get_result();
}
?>

<style>
    .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
    .page-title { font-size: 1.75rem; font-weight: 600; }
    .card-header { padding: 1.5rem 2rem; border-bottom: 1px solid #e2e8f0; background-color: #f8fafc; }
    .card-header h3 { font-size: 1.25rem; font-weight: 600; display: flex; align-items: center; gap: 0.75rem; }
    .card-body { padding: 2rem; }
    .message { padding: 1rem 1.5rem; border-radius: 0.5rem; margin-bottom: 2rem; display: flex; align-items: center; gap: 0.75rem; }
    .success { background-color: #dcfce7; color: #166534; }
    .error { background-color: #fee2e2; color: #b91c1c; }
    form { max-width: 700px; margin: 0 auto; }
    .form-group { margin-bottom: 1.5rem; }
    label { display: block; margin-bottom: 0.5rem; font-weight: 500; }
    textarea, input[type="file"] { width: 100%; padding: 0.75rem 1rem; border: 1px solid #e2e8f0; border-radius: 0.5rem; }
    textarea { min-height: 120px; resize: vertical; }
    .file-input-container { position: relative; }
    .file-input-label {
        display: flex; align-items: center; justify-content: center; gap: 0.75rem; padding: 2rem;
        border: 2px dashed #e2e8f0; border-radius: 0.5rem; cursor: pointer; text-align: center;
    }
    .file-input-label i { font-size: 1.5rem; color: var(--primary-color); }
    input[type="file"] { position: absolute; top: 0; left: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer; }
    .file-name { margin-top: 0.75rem; font-size: 0.875rem; text-align: center; }
    .btn-block { display: block; width: 100%; }
    .table-container { overflow-x: auto; }
    table { width: 100%; border-collapse: collapse; }
    th, td { padding: 1rem 1.5rem; text-align: left; border-bottom: 1px solid #e2e8f0; }
    th { background-color: #f8fafc; font-weight: 600; }
    .download-link { color: var(--primary-color); text-decoration: none; font-weight: 500; }
    .empty-state { text-align: center; padding: 2rem; }
</style>

<div class="page-header">
    <h1 class="page-title">My File Database</h1>
</div>

<div class="card">
    <div class="card-header"><h3><i class="fas fa-cloud-upload-alt"></i> Upload New File</h3></div>
    <div class="card-body">
        <?php if ($message): ?>
            <div class="message <?= $message_type ?>">
                <i class="fas <?= $message_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
                <span><?= htmlspecialchars($message) ?></span>
            </div>
        <?php endif; ?>

        <form action="staff_database.php" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="description">File Description</label>
                <textarea name="description" id="description" placeholder="Enter a detailed description of the file..." required></textarea>
            </div>
            <div class="form-group">
                <label>Upload File</label>
                <div class="file-input-container">
                    <label for="file" class="file-input-label">
                        <i class="fas fa-file-upload"></i>
                        <span>Click to browse or drag & drop</span>
                    </label>
                    <input type="file" name="file" id="file" accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.jpg,.jpeg,.png" required>
                    <div class="file-name" id="file-name">No file selected</div>
                </div>
            </div>
            <button type="submit" name="upload_file" class="btn btn-primary btn-block"><i class="fas fa-upload"></i> Upload File</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header"><h3><i class="fas fa-file-alt"></i> My Uploaded Files</h3></div>
    <div class="card-body">
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Description</th>
                        <th>Filename</th>
                        <th>Date Uploaded</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($filesResult && $filesResult->num_rows > 0): ?>
                        <?php while ($file = $filesResult->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($file['description']) ?></td>
                                <td><?= htmlspecialchars($file['original_name']) ?></td>
                                <td><?= date("d M, Y", strtotime($file['upload_date'])) ?></td>
                                <td>
                                    <a href="../uploads/staff_files/<?= htmlspecialchars($file['file_name']) ?>" class="download-link" download>
                                        <i class="fas fa-download"></i> Download
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4"><div class="empty-state">No files have been uploaded yet.</div></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    document.getElementById('file').addEventListener('change', function(e) {
        const fileName = e.target.files[0] ? e.target.files[0].name : 'No file selected';
        document.getElementById('file-name').textContent = fileName;
    });
</script>

<?php
// Include the new footer to close the page layout
if(isset($stmt_files)) $stmt_files->close();
$conn->close();
include 'footer.php';
?>