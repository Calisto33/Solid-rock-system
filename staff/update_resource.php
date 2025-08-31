<?php
// Set the page title for the header
$pageTitle = "Update Resource";

// --- SECURITY AND PREPARATION ---
// We need to start the session and do the security check BEFORE including the header,
// because this page relies on GET parameters that must be validated first.
session_start();
include '../config.php';

// Check if the user is logged in as a staff member
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'staff') {
    header("Location: ../login.php");
    exit();
}

// Check if the resource_id is provided in the URL
if (!isset($_GET['resource_id']) || empty($_GET['resource_id'])) {
    die("Error: Resource ID is missing.");
}
$resource_id = $_GET['resource_id'];

// Now include the visual header and sidebar
include 'header.php';

// --- PHP LOGIC SPECIFIC TO THIS PAGE ---

// Fetch the staff_id using the user_id from the session
$staffIdQuery = "SELECT staff_id FROM staff WHERE id = ?";
$stmt_staff = $conn->prepare($staffIdQuery);
$stmt_staff->bind_param("i", $staff_id); // $staff_id is from header.php
$stmt_staff->execute();
$result_staff = $stmt_staff->get_result();
$staffData = $result_staff->fetch_assoc();
$stmt_staff->close();

if (!$staffData) {
    die("Error: Staff member not found.");
}
$specific_staff_id = $staffData['staff_id'];

// Handle form submission for update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $department = $_POST['department'];
    $description = trim($_POST['description']);
    $resource_link = trim($_POST['resource_link']);

    $updateQuery = "UPDATE student_resources SET department = ?, description = ?, resource_link = ? WHERE resource_id = ? AND staff_id = ?";
    $stmt_update = $conn->prepare($updateQuery);
    $stmt_update->bind_param("sssii", $department, $description, $resource_link, $resource_id, $specific_staff_id);

    if ($stmt_update->execute()) {
        header("Location: edit_resource.php?status=updated"); // Redirect to the list of resources
        exit();
    } else {
        $errorMessage = "Error updating resource: " . $stmt_update->error;
    }
    $stmt_update->close();
}

// Fetch the existing resource details to pre-fill the form
$fetchQuery = "SELECT department, description, resource_link FROM student_resources WHERE resource_id = ? AND staff_id = ?";
$stmt_fetch = $conn->prepare($fetchQuery);
$stmt_fetch->bind_param("ii", $resource_id, $specific_staff_id);
$stmt_fetch->execute();
$result = $stmt_fetch->get_result();
$resource = $result->fetch_assoc();
$stmt_fetch->close();

if (!$resource) {
    die("Error: Resource not found or you do not have permission to edit it.");
}
?>

<style>
    .card { background: #ffffff; border-radius: 12px; box-shadow: var(--shadow-md); padding: 2rem; }
    .card-header { text-align: center; margin-bottom: 1.5rem; }
    .card-header h2 { font-size: 1.75rem; font-weight: 600; color: #1e293b; }
    .card-header p { color: #64748b; }
    .form-container { max-width: 650px; margin: 0 auto; }
    .form-group { margin-bottom: 1.5rem; }
    label { display: block; font-weight: 500; margin-bottom: 0.5rem; }
    .input-group { position: relative; }
    .input-group i { position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: #64748b; }
    select, input[type="url"], textarea {
        width: 100%; padding: 0.875rem 1rem 0.875rem 2.5rem; border: 1px solid #e2e8f0;
        border-radius: 12px; font-size: 1rem;
    }
    textarea { min-height: 150px; resize: vertical; }
    .btn-group { display: flex; gap: 1rem; margin-top: 1.5rem; justify-content: center; }
    .btn {
        display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem;
        padding: 0.875rem 1.5rem; font-size: 1rem; font-weight: 500; border-radius: 12px;
        cursor: pointer; text-decoration: none; border: none;
    }
    .btn-primary { background: linear-gradient(135deg, #2563eb, #1d4ed8); color: white; }
    .btn-outline { background-color: transparent; border: 1px solid #2563eb; color: #2563eb; }
</style>

<div class="card">
    <div class="card-header">
        <h2>Update Resource</h2>
        <p>Modify the resource details below</p>
    </div>
    <div class="form-container">
        <form action="update_resource.php?resource_id=<?= $resource_id ?>" method="POST">
            <div class="form-group">
                <label for="department">Department</label>
                <div class="input-group">
                    <i class="fas fa-building"></i>
                    <select name="department" id="department" required>
                        <option value="Arts" <?= $resource['department'] === 'Arts' ? 'selected' : '' ?>>Arts</option>
                        <option value="Sciences" <?= $resource['department'] === 'Sciences' ? 'selected' : '' ?>>Sciences</option>
                        <option value="Commercial" <?= $resource['department'] === 'Commercial' ? 'selected' : '' ?>>Commercial</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <div class="input-group">
                    <i class="fas fa-align-left"></i>
                    <textarea name="description" id="description" required><?= htmlspecialchars($resource['description']) ?></textarea>
                </div>
            </div>

            <div class="form-group">
                <label for="resource_link">Resource Link</label>
                <div class="input-group">
                    <i class="fas fa-link"></i>
                    <input type="url" name="resource_link" id="resource_link" value="<?= htmlspecialchars($resource['resource_link']) ?>" required>
                </div>
            </div>

            <div class="btn-group">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Resource
                </button>
                <a href="edit_resource.php" class="btn btn-outline">
                    <i class="fas fa-arrow-left"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<?php
// Include the new footer to close the page layout
$conn->close();
include 'footer.php';
?>